<?php
include '../config/db.php';
include '../lib/wa_api.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

$qr_code = mysqli_real_escape_string($conn, $_POST['qr_code']);
$today = date('Y-m-d');
$now = date('H:i:s');

// =========================================================
// FUNGSI ANTI-SPAM & PESAN PERSONAL
// =========================================================
function buatPesanPersonal($nama, $jenis, $waktu, $status_ket = '') {
    // 1. Variasi Salam (Lebih santai tapi sopan)
    $salam_array = [
        "Assalamualaikum Ayah/Bunda,", 
        "Selamat Pagi Bapak/Ibu,", 
        "Halo Wali Murid,", 
        "Salam sejahtera,",
        "Selamat Pagi,"
    ];
    $salam = $salam_array[array_rand($salam_array)];

    // 2. Variasi Kalimat Inti (Bahasa natural)
    // $jenis = 'MASUK' atau 'PULANG'
    $pesan_inti = "";
    
    if ($jenis == 'MASUK') {
        $kalimat_masuk = [
            "Alhamdulillah, ananda *$nama* sudah sampai di sekolah dengan selamat pada pukul $waktu.",
            "Mengabarkan bahwa ananda *$nama* telah tiba di sekolah jam $waktu.",
            "Ananda *$nama* baru saja melakukan absen masuk sekolah pukul $waktu.",
            "Laporan kehadiran: Ananda *$nama* sudah siap belajar di sekolah (masuk pukul $waktu)."
        ];
        $pesan_inti = $kalimat_masuk[array_rand($kalimat_masuk)];

        // Tambahan Status Telat yang lebih halus
        if (strtolower($status_ket) == 'terlambat') {
            $pesan_inti .= "\n\nCatatan: Sedikit terlambat datang hari ini. Mohon diingatkan untuk berangkat lebih awal besok ya. Semangat!";
        } elseif (strtolower($status_ket) == 'hadir') {
             $pesan_inti .= "\n\nDatang tepat waktu. Terima kasih atas dukungannya.";
        }

    } else { // PULANG
        $kalimat_pulang = [
            "Kegiatan belajar mengajar telah selesai. Ananda *$nama* sudah absen pulang pada pukul $waktu.",
            "Waktunya pulang! Ananda *$nama* sudah meninggalkan sekolah jam $waktu.",
            "Ananda *$nama* telah menyelesaikan sekolah hari ini dan absen pulang pukul $waktu.",
            "Menginformasikan ananda *$nama* sudah pulang sekolah jam $waktu. Mohon dipantau kepulangannya."
        ];
        $pesan_inti = $kalimat_pulang[array_rand($kalimat_pulang)];
        $pesan_inti .= "\n\nHati-hati di jalan dan selamat beristirahat.";
    }

    // 3. Variasi Penutup (Tanpa label kaku "Hormat Kami")
    $tutup_array = [
        "Terima kasih.",
        "Semoga harinya menyenangkan.",
        "Salam hangat dari sekolah.",
        "SMK Al-Huda Bumiayu."
    ];
    $tutup = $tutup_array[array_rand($tutup_array)];

    // 4. Kode Unik (Disembunyikan/Kecil agar tidak mengganggu estetika)
    // Gunakan karakter tak terlihat (Zero Width Space) atau format footer kecil
    $kode_unik = substr(md5(uniqid()), 0, 5); 

    // Gabungkan
    return "$salam\n\n$pesan_inti\n\n$tutup\n\n----------------\nRef: $kode_unik"; 
}

// =========================================================
// 1. VALIDASI HARI LIBUR
// =========================================================
$cek_libur = mysqli_query($conn, "SELECT keterangan FROM hari_libur WHERE tanggal = '$today'");
if (mysqli_num_rows($cek_libur) > 0) {
    $data_libur = mysqli_fetch_assoc($cek_libur);
    echo json_encode(['status' => 'error', 'message' => 'Gagal Absen! Hari ini LIBUR: ' . $data_libur['keterangan']]);
    exit;
}

// =========================================================
// 2. CEK DATA SISWA
// =========================================================
$query_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE qr_code = '$qr_code'");
$siswa = mysqli_fetch_assoc($query_siswa);

if (!$siswa) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code tidak dikenali']);
    exit;
}

// =========================================================
// 3. VALIDASI IZIN / SAKIT (YANG SUDAH DI-APPROVE)
// =========================================================
$cek_izin = mysqli_query($conn, "SELECT status, alasan FROM perizinan 
                                 WHERE siswa_id = '{$siswa['id']}' 
                                 AND status = 'disetujui' 
                                 AND '$today' BETWEEN tanggal_mulai AND tanggal_selesai");

if (mysqli_num_rows($cek_izin) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Anda sedang status IZIN/SAKIT hari ini. Tidak perlu absen.']);
    exit;
}

// =========================================================
// 4. LOGIKA ABSENSI
// =========================================================
$query_setting = mysqli_query($conn, "SELECT * FROM pengaturan_jam LIMIT 1");
$setting = mysqli_fetch_assoc($query_setting);

$cek_absen = mysqli_query($conn, "SELECT * FROM absensi WHERE siswa_id = '{$siswa['id']}' AND tanggal = '$today'");
$data_absen = mysqli_fetch_assoc($cek_absen);

if (!$data_absen) {
    // --- ABSEN MASUK ---
    
    $status_kehadiran = ($now <= $setting['batas_terlambat']) ? 'hadir' : 'terlambat';
    $ket = ($status_kehadiran == 'terlambat') ? 'Telat (Masuk jam ' . $now . ')' : 'Tepat Waktu';

    $insert = mysqli_query($conn, "INSERT INTO absensi (siswa_id, tanggal, jam_masuk, status_masuk, keterangan) 
                                   VALUES ('{$siswa['id']}', '$today', '$now', '$status_kehadiran', '$ket')");

    if ($insert) {
        // Kirim WA Notifikasi (Masuk) - Pesan Personal
        $kirim_wa = true;
        
        // OPSI HEMAT SPAM: Matikan notifikasi jika tepat waktu (Opsional, uncomment baris bawah)
        // if ($status_kehadiran == 'hadir') { $kirim_wa = false; } 

        if ($kirim_wa) {
            $pesan_wa = buatPesanPersonal($siswa['nama'], "MASUK", $now, $status_kehadiran);
            kirimNotifikasiWA($siswa['no_hp_ortu'], $pesan_wa);
        }

        echo json_encode(['status' => 'success', 'message' => "Halo {$siswa['nama']}, Selamat Pagi! Absen Masuk Berhasil."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data DB']);
    }

} else {
    // --- ABSEN PULANG ---
    
    if ($data_absen['jam_keluar'] != NULL && $data_absen['jam_keluar'] != '00:00:00') {
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah absen pulang hari ini!']);
        exit;
    }

    // Cek Jam Pulang
    if ($now < $setting['jam_pulang']) {
         echo json_encode(['status' => 'error', 'message' => 'Belum waktunya pulang! Jam pulang: ' . $setting['jam_pulang']]);
         exit;
    }

    $update = mysqli_query($conn, "UPDATE absensi SET jam_keluar = '$now' WHERE id = '{$data_absen['id']}'");

    if ($update) {
        // Kirim WA Notifikasi (Pulang) - Pesan Personal
        $pesan_wa = buatPesanPersonal($siswa['nama'], "PULANG", $now);
        kirimNotifikasiWA($siswa['no_hp_ortu'], $pesan_wa);

        echo json_encode(['status' => 'success', 'message' => "Halo {$siswa['nama']}, Absen Pulang Berhasil. Hati-hati di jalan!"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update data DB']);
    }
}
?>
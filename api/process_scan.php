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
$cek_izin = mysqli_query($conn, "SELECT status FROM perizinan 
                                 WHERE siswa_id = '{$siswa['id']}' 
                                 AND status = 'disetujui' 
                                 AND '$today' BETWEEN tanggal_mulai AND tanggal_selesai");

if (mysqli_num_rows($cek_izin) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Anda sedang status IZIN/SAKIT hari ini. Tidak perlu absen.']);
    exit;
}

// =========================================================
// 4. LOGIKA ABSENSI & PESAN ANTI-SPAM
// =========================================================

// Fungsi untuk membuat pesan yang SANGAT BERBEDA setiap kali kirim
function buatPesanUnik($nama, $jenis, $waktu, $status = '') {
    $status_info = ($status != '') ? " ($status)" : "";
    
    // Pola 1: Formal
    $pola1 = "Yth. Wali Murid,\nSiswa a.n *$nama* terpantau melakukan absen *$jenis*$status_info pada pukul $waktu WIB.";
    
    // Pola 2: Singkat / Informatif
    $pola2 = "Laporan Kehadiran:\nNama: $nama\nAktivitas: Absen $jenis\nWaktu: $waktu\nStatus: $status";
    
    // Pola 3: Ramah
    $pola3 = "Halo Bapak/Ibu,\nKami menginfokan bahwa ananda *$nama* baru saja scan kartu untuk absen *$jenis* di sekolah jam $waktu.";
    
    // Pola 4: To the point
    $pola4 = "Notifikasi Sistem Absensi:\nSiswa *$nama* -> $jenis ($waktu). Terima kasih.";

    // Pilih 1 pola secara acak
    $pilihan = [$pola1, $pola2, $pola3, $pola4];
    $pesan_jadi = $pilihan[array_rand($pilihan)];

    // Tambahkan Kode Unik di bawah (WAJIB ADA)
    // uniqid() menghasilkan string acak berdasarkan waktu mikrodetik
    $kode_unik = uniqid(); 
    return "$pesan_jadi\n\nRef ID: #$kode_unik"; 
}

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
        // --- STRATEGI ANTI SPAM: FILTER NOTIFIKASI ---
        // Jika Anda sering kena SPAM, saya sarankan HANYA kirim notifikasi jika TERLAMBAT.
        // Jika Hadir Tepat Waktu, tidak perlu kirim WA (Hemat kuota & aman dari banned).
        
        $kirim_wa = true;
        
        // HAPUS tanda komentar (//) di baris bawah ini untuk mengaktifkan fitur hemat:
        // if ($status_kehadiran == 'hadir') { $kirim_wa = false; } 

        if ($kirim_wa) {
            $pesan_wa = buatPesanUnik($siswa['nama'], "MASUK", $now, strtoupper($status_kehadiran));
            kirimNotifikasiWA($siswa['no_hp_ortu'], $pesan_wa);
        }

        echo json_encode(['status' => 'success', 'message' => "Halo {$siswa['nama']}, Absen Masuk Berhasil ($status_kehadiran)"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data DB']);
    }

} else {
    // --- ABSEN PULANG ---
    
    if ($data_absen['jam_keluar'] != NULL && $data_absen['jam_keluar'] != '00:00:00') {
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah absen pulang hari ini!']);
        exit;
    }

    if ($now < $setting['jam_pulang']) {
         echo json_encode(['status' => 'error', 'message' => 'Belum waktunya pulang! Jam pulang: ' . $setting['jam_pulang']]);
         exit;
    }

    $update = mysqli_query($conn, "UPDATE absensi SET jam_keluar = '$now' WHERE id = '{$data_absen['id']}'");

    if ($update) {
        // Pulang biasanya aman untuk dikirim karena jam pulangnya variatif (tidak sepadat jam masuk)
        $pesan_wa = buatPesanUnik($siswa['nama'], "PULANG", $now, "SELESAI");
        kirimNotifikasiWA($siswa['no_hp_ortu'], $pesan_wa);

        echo json_encode(['status' => 'success', 'message' => "Halo {$siswa['nama']}, Absen Pulang Berhasil. Hati-hati!"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update data DB']);
    }
}
?>
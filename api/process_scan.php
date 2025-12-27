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

// FUNGSI BANTUAN: GENERATE PESAN ACAK (ANTI-SPAM)
function buatPesan($nama, $jenis, $waktu, $status = '') {
    $salam_list = ["Assalamu'alaikum", "Selamat Pagi", "Halo", "Yth. Wali Murid", "Laporan Sekolah"];
    $salam = $salam_list[array_rand($salam_list)];
    
    $tutup_list = ["Terima Kasih.", "Salam hangat.", "Hormat kami.", "Semoga hari Anda menyenangkan."];
    $tutup = $tutup_list[array_rand($tutup_list)];
    
    // Kode unik agar hash pesan selalu berbeda (Anti-Spam WA)
    $kode_unik = date('His') . "-" . rand(100,999); 
    
    $status_text = ($status != '') ? "\nStatus: *" . strtoupper($status) . "*" : "";

    return "$salam,\n\n" .
           "Memberitahukan bahwa siswa:\n" .
           "Nama: *{$nama}*\n" .
           "Telah absen *$jenis* pada pukul $waktu.$status_text\n\n" .
           "$tutup\n" .
           "Ref ID: #$kode_unik"; 
}

if (!$data_absen) {
    // --- ABSEN MASUK ---
    
    $status_kehadiran = ($now <= $setting['batas_terlambat']) ? 'hadir' : 'terlambat';
    $ket = ($status_kehadiran == 'terlambat') ? 'Telat (Masuk jam ' . $now . ')' : 'Tepat Waktu';

    $insert = mysqli_query($conn, "INSERT INTO absensi (siswa_id, tanggal, jam_masuk, status_masuk, keterangan) 
                                   VALUES ('{$siswa['id']}', '$today', '$now', '$status_kehadiran', '$ket')");

    if ($insert) {
        // Kirim WA Notifikasi (Masuk)
        $pesan_wa = buatPesan($siswa['nama'], "MASUK", $now, $status_kehadiran);
        kirimNotifikasiWA($siswa['no_hp_ortu'], $pesan_wa);

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

    // Cek Jam Pulang
    if ($now < $setting['jam_pulang']) {
         echo json_encode(['status' => 'error', 'message' => 'Belum waktunya pulang! Jam pulang: ' . $setting['jam_pulang']]);
         exit;
    }

    $update = mysqli_query($conn, "UPDATE absensi SET jam_keluar = '$now' WHERE id = '{$data_absen['id']}'");

    if ($update) {
        // Kirim WA Notifikasi (Pulang)
        $pesan_wa = buatPesan($siswa['nama'], "PULANG", $now);
        kirimNotifikasiWA($siswa['no_hp_ortu'], $pesan_wa);

        echo json_encode(['status' => 'success', 'message' => "Halo {$siswa['nama']}, Absen Pulang Berhasil. Hati-hati!"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update data DB']);
    }
}
?>
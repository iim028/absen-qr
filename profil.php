<?php
session_start();
include 'config/db.php';

// Cek Login
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$id_user = $_SESSION['user_id'];
$pesan = "";

// Hitung Notifikasi (Untuk Sidebar)
$izin_pending = 0;
if($role != 'siswa') {
    $q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'");
    $izin_pending = mysqli_fetch_assoc($q_notif)['total'];
}

// AMBIL DATA USER
$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_user'");
$d_user = mysqli_fetch_assoc($q_user);
$nisn_siswa = $d_user['username']; // Username siswa adalah NISN

// LOGIC GANTI PASSWORD
if (isset($_POST['ganti_password'])) {
    $pass_lama = md5($_POST['pass_lama']); // Enkripsi MD5
    $pass_baru = $_POST['pass_baru'];
    $konfirmasi = $_POST['konfirmasi'];

    if ($pass_lama == $d_user['password']) {
        if ($pass_baru == $konfirmasi) {
            $pass_hash = md5($pass_baru);
            $update = mysqli_query($conn, "UPDATE users SET password = '$pass_hash' WHERE id = '$id_user'");
            
            if ($update) {
                $pesan = "<div class='alert alert-success'>Password berhasil diubah!</div>";
            } else {
                $pesan = "<div class='alert alert-danger'>Gagal mengupdate database.</div>";
            }
        } else {
            $pesan = "<div class='alert alert-danger'>Password Baru dan Konfirmasi tidak cocok!</div>";
        }
    } else {
        $pesan = "<div class='alert alert-danger'>Password Lama salah!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Profil Saya - Absensi QR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="d-flex" id="wrapper">
    
    <!-- SIDEBAR -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading"><i class="fa-solid fa-qrcode me-2"></i>ABSENSI</div>
        <div class="list-group list-group-flush mt-3">
            <a href="dashboard.php" class="list-group-item list-group-item-action">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>

            <!-- Menu Admin & Petugas -->
            <?php if ($role == 'admin' || $role == 'petugas'): ?>
                <?php if ($role == 'admin'): ?>
                <a href="manajemen_user.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-users-gear"></i> Manajemen User
                </a>
                <?php endif; ?>
                <a href="siswa.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-user-graduate"></i> Data Siswa
                </a>
                <a href="scan.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-camera"></i> Scan QR
                </a>
                <a href="rekap.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-file-excel"></i> Rekap Absensi
                </a>
                
                <a href="data_absensi.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Absensi
                </a>
                <a href="libur.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-calendar-xmark"></i> Atur Libur
                </a>

                <a href="izin.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-envelope-open-text"></i> Approval Izin
                    <?php if($izin_pending > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-2"><?= $izin_pending ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($role == 'admin'): ?>
                <a href="pengaturan.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-clock"></i> Atur Jam
                </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Menu Guru -->
            <?php if ($role == 'guru'): ?>
                <a href="siswa.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-user-graduate"></i> Data Siswa
                </a>
                <a href="rekap.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-file-excel"></i> Rekap Absensi
                </a>
                <a href="data_absensi.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Absensi
                </a>
                <a href="izin.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-envelope-open-text"></i> Approval Izin
                    <?php if($izin_pending > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-2"><?= $izin_pending ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <!-- Menu Siswa -->
            <?php if ($role == 'siswa'): ?>
                <a href="izin.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-paper-plane"></i> Ajukan Izin
                </a>
                <a href="riwayat.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Absen
                </a>
            <?php endif; ?>

            <div class="mt-4 border-top border-secondary pt-2">
                <!-- Menu Aktif -->
                <a href="profil.php" class="list-group-item list-group-item-action active">
                    <i class="fa-solid fa-user-circle"></i> Profil Saya
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- CONTENT WRAPPER -->
    <div id="page-content-wrapper">
        
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4">
            <button class="btn btn-dark" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                    <li class="nav-item">
                        <span class="nav-link fw-bold text-secondary">
                            Halo, <?= $_SESSION['nama'] ?> (<?= strtoupper($role) ?>)
                        </span>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid px-4 mt-4">
            <div class="row justify-content-center">
                
                <!-- KOLOM KIRI: INFO PROFIL -->
                <div class="col-md-5 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <h5 class="mb-0"><i class="fa-solid fa-id-card me-2"></i>Profil Pengguna</h5>
                        </div>
                        <div class="card-body p-4 text-center">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=random&size=128" class="rounded-circle mb-3 border border-3 shadow-sm" width="100">
                            <h4><?= $_SESSION['nama'] ?></h4>
                            <span class="badge bg-secondary px-3 py-2 rounded-pill mb-3"><?= strtoupper($role) ?></span>
                            
                            <?php if ($role == 'siswa'): ?>
                                <hr>
                                <div class="bg-light p-3 rounded border">
                                    <h6 class="text-muted mb-3">Kartu Identitas Digital</h6>
                                    <!-- QR Code Image -->
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $nisn_siswa ?>" 
                                         alt="QR Code" 
                                         class="img-thumbnail mb-3" 
                                         id="qrCodeImage">
                                    <p class="small text-muted mb-2">NISN: <b><?= $nisn_siswa ?></b></p>
                                    
                                    <div class="d-grid gap-2">
                                        <!-- Tombol Download Image -->
                                        <button onclick="downloadQR()" class="btn btn-outline-primary btn-sm">
                                            <i class="fa-solid fa-download me-2"></i>Simpan Gambar QR
                                        </button>
                                        
                                        <!-- Tombol Cetak Kartu -->
                                        <a href="cetak_kartu.php?id=<?= isset($_SESSION['siswa_id']) ? $_SESSION['siswa_id'] : '' ?>" target="_blank" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-print me-2"></i>Cetak Kartu Pelajar
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: GANTI PASSWORD -->
                <div class="col-md-7 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 text-dark"><i class="fa-solid fa-lock me-2"></i>Keamanan Akun</h5>
                        </div>
                        <div class="card-body p-4">
                            <?= $pesan ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Password Lama</label>
                                    <input type="password" name="pass_lama" class="form-control" required placeholder="Masukkan password saat ini">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" name="pass_baru" class="form-control" required placeholder="Minimal 6 karakter">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" name="konfirmasi" class="form-control" required placeholder="Ulangi password baru">
                                </div>
                                <button type="submit" name="ganti_password" class="btn btn-success w-100 py-2">
                                    <i class="fa-solid fa-save me-2"></i>Simpan Password Baru
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Sidebar
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");
    toggleButton.onclick = function () {
        el.classList.toggle("toggled");
    };

    // Fungsi Download QR Code (Hanya untuk Siswa)
    function downloadQR() {
        const imageSrc = document.getElementById('qrCodeImage').src;
        const nisn = "<?= isset($nisn_siswa) ? $nisn_siswa : 'Siswa' ?>";
        
        fetch(imageSrc)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                // Nama file saat didownload
                a.download = 'QR_Absensi_' + nisn + '.png';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(() => alert('Gagal mendownload gambar.'));
    }
</script>

</body>
</html>
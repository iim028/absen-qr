<?php
session_start();
include 'config/db.php';

// Cek hanya admin yang boleh akses halaman ini
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$role = $_SESSION['role'];

// Hitung Notifikasi Izin Pending (Untuk Sidebar)
$q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'");
$izin_pending = mysqli_fetch_assoc($q_notif)['total'];

// Proses Update Pengaturan
if (isset($_POST['update'])) {
    $masuk = $_POST['masuk'];
    $pulang = $_POST['pulang'];
    $batas = $_POST['batas'];

    // Update id=1 (asumsi hanya ada 1 record pengaturan)
    $sql = "UPDATE pengaturan_jam SET jam_masuk='$masuk', jam_pulang='$pulang', batas_terlambat='$batas' WHERE id=1";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Pengaturan Jam Berhasil Diupdate!'); window.location='pengaturan.php';</script>";
    }
}

// Ambil Data Lama
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan_jam LIMIT 1"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Atur Jam - Absensi QR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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

                <!-- Menu Aktif -->
                <?php if ($role == 'admin'): ?>
                <a href="pengaturan.php" class="list-group-item list-group-item-action active">
                    <i class="fa-solid fa-clock"></i> Atur Jam
                </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Menu Guru (Tidak akses halaman ini, tapi sidebar tetap konsisten jika di-include) -->
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

            <!-- Menu Siswa (Tidak akses halaman ini) -->
            <?php if ($role == 'siswa'): ?>
                <a href="izin.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-paper-plane"></i> Ajukan Izin
                </a>
                <a href="riwayat.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Absen
                </a>
            <?php endif; ?>

            <div class="mt-4 border-top border-secondary pt-2">
                <a href="profil.php" class="list-group-item list-group-item-action">
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
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4">
            <button class="btn btn-dark" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                    <li class="nav-item">
                        <span class="nav-link fw-bold text-secondary">
                            Halo, <?= $_SESSION['nama'] ?> (ADMIN)
                        </span>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="container-fluid px-4 mt-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fa-solid fa-cog me-2"></i>Atur Jam Operasional</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info border-0 shadow-sm mb-4">
                                <small><i class="fa-solid fa-info-circle me-1"></i> Pengaturan ini mempengaruhi validasi jam saat siswa melakukan Scan QR.</small>
                            </div>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Jam Mulai Absen Masuk</label>
                                    <input type="time" name="masuk" class="form-control" value="<?= $data['jam_masuk'] ?>" required>
                                    <small class="text-muted">Siswa bisa mulai scan masuk.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Batas Terlambat</label>
                                    <input type="time" name="batas" class="form-control" value="<?= $data['batas_terlambat'] ?>" required>
                                    <small class="text-danger">Lewat jam ini status jadi "Terlambat".</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Jam Mulai Absen Pulang</label>
                                    <input type="time" name="pulang" class="form-control" value="<?= $data['jam_pulang'] ?>" required>
                                    <small class="text-muted">Siswa bisa scan pulang mulai jam ini.</small>
                                </div>
                                <hr>
                                <button type="submit" name="update" class="btn btn-primary w-100">
                                    <i class="fa-solid fa-save me-2"></i>Simpan Perubahan
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
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");

    toggleButton.onclick = function () {
        el.classList.toggle("toggled");
    };
</script>

</body>
</html>
<?php
session_start();
include 'config/db.php';

// Cek akses: Admin & Petugas (Guru bisa lihat? PDF D.1 biasanya admin/petugas)
if (!isset($_SESSION['login']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'petugas')) {
    header("Location: dashboard.php");
    exit;
}

$role = $_SESSION['role'];

// Hitung Notifikasi Izin Pending (Untuk Sidebar)
$q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'");
$izin_pending = mysqli_fetch_assoc($q_notif)['total'];

// Tambah Libur
if (isset($_POST['tambah'])) {
    $tgl = $_POST['tanggal'];
    $ket = $_POST['keterangan'];
    mysqli_query($conn, "INSERT INTO hari_libur (tanggal, keterangan) VALUES ('$tgl', '$ket')");
    header("Location: libur.php");
}

// Hapus Libur
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM hari_libur WHERE id='$id'");
    header("Location: libur.php");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Atur Jadwal Libur - Absensi QR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                
                <!-- Menu Aktif -->
                <a href="libur.php" class="list-group-item list-group-item-action active">
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

            <!-- Menu Guru (Jika guru boleh akses, tambahkan disini. Saat ini dibatasi di PHP atas) -->
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
                            Halo, <?= $_SESSION['nama'] ?> (<?= strtoupper($role) ?>)
                        </span>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="container-fluid px-4 mt-4">
            <h3 class="mb-4"><i class="fa-solid fa-calendar-xmark text-primary me-2"></i>Mengelola Jadwal Libur</h3>
            
            <div class="row">
                <!-- Form Input -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Tambah Libur Nasional</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal</label>
                                    <input type="date" name="tanggal" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Hari Raya Idul Fitri" required>
                                </div>
                                <button type="submit" name="tambah" class="btn btn-success w-100">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Daftar Hari Libur</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Keterangan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $q = mysqli_query($conn, "SELECT * FROM hari_libur ORDER BY tanggal DESC");
                                        $no=1;
                                        while($r=mysqli_fetch_assoc($q)){
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= date('d-m-Y', strtotime($r['tanggal'])) ?></td>
                                            <td><?= $r['keterangan'] ?></td>
                                            <td class="text-center">
                                                <a href="libur.php?hapus=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus hari libur ini?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
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
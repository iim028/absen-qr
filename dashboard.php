<?php
session_start();
include 'config/db.php'; 

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- LOGIC STATISTIK DASHBOARD & CHART ---
$total_siswa = 0; $hadir_today = 0; $izin_pending = 0; $terlambat_today = 0;
$labels = []; $data_hadir = []; $data_terlambat = []; // Array untuk grafik

if ($role == 'admin' || $role == 'petugas' || $role == 'guru') {
    // 1. Statistik Card
    $q1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa"); 
    $total_siswa = mysqli_fetch_assoc($q1)['total'];

    $q2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensi WHERE tanggal = '$today' AND status_masuk IN ('hadir', 'terlambat')"); 
    $hadir_today = mysqli_fetch_assoc($q2)['total'];

    $q3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'"); 
    $izin_pending = mysqli_fetch_assoc($q3)['total'];

    $q4 = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensi WHERE tanggal = '$today' AND status_masuk = 'terlambat'"); 
    $terlambat_today = mysqli_fetch_assoc($q4)['total'];

    // 2. Logic Grafik (7 Hari Terakhir)
    for ($i = 6; $i >= 0; $i--) {
        $tgl_cek = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d/m', strtotime($tgl_cek)); // Label Tanggal (ex: 24/12)
        
        // Hitung Hadir per tanggal
        $q_h = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensi WHERE tanggal='$tgl_cek' AND status_masuk='hadir'");
        $data_hadir[] = mysqli_fetch_assoc($q_h)['total'];
        
        // Hitung Terlambat per tanggal
        $q_t = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensi WHERE tanggal='$tgl_cek' AND status_masuk='terlambat'");
        $data_terlambat[] = mysqli_fetch_assoc($q_t)['total'];
    }

} elseif ($role == 'siswa') {
    // Statistik Khusus Siswa
    $siswa_id = $_SESSION['siswa_id'];
    $q_status = mysqli_query($conn, "SELECT status_masuk, jam_masuk FROM absensi WHERE siswa_id='$siswa_id' AND tanggal='$today'");
    $status_today = mysqli_fetch_assoc($q_status);
    
    // Hitung Total Hadir Saya
    $q_my_hadir = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensi WHERE siswa_id='$siswa_id' AND status_masuk='hadir'");
    $hadir_today = mysqli_fetch_assoc($q_my_hadir)['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard - Absensi QR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Style Custom -->
    <link rel="stylesheet" href="assets/style.css">
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="d-flex" id="wrapper">
    
    <!-- ================= SIDEBAR ================= -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading"><i class="fa-solid fa-qrcode me-2"></i>ABSENSI</div>
        <div class="list-group list-group-flush mt-3">
            
            <a href="dashboard.php" class="list-group-item list-group-item-action active">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>

            <!-- Menu Admin & Petugas -->
            <?php if ($role == 'admin' || $role == 'petugas'): ?>
                
                <!-- HANYA ADMIN yang melihat Manajemen User -->
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
                
                <!-- NEW: MENU TAMBAHAN UNTUK ADMIN/PETUGAS -->
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
                
                <!-- HANYA ADMIN yang melihat Atur Jam -->
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
                <!-- GURU JUGA BISA LIHAT LIBUR & EDIT ABSEN (OPSIONAL) -->
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
                <a href="profil.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-user-circle"></i> Profil Saya
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- ================= CONTENT WRAPPER ================= -->
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

        <!-- Main Content -->
        <div class="container-fluid px-4 mt-4">
            
            <!-- STATISTIC CARDS (WIDGETS) -->
            <?php if ($role == 'admin' || $role == 'petugas' || $role == 'guru'): ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-stat bg-primary text-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6 class="mb-0">Total Siswa</h6><h2 class="mb-0"><?= $total_siswa ?></h2></div>
                            <i class="fa-solid fa-users icon-box"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-success text-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6 class="mb-0">Hadir Hari Ini</h6><h2 class="mb-0"><?= $hadir_today ?></h2></div>
                            <i class="fa-solid fa-check-circle icon-box"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-warning text-dark p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6 class="mb-0">Terlambat</h6><h2 class="mb-0"><?= $terlambat_today ?></h2></div>
                            <i class="fa-solid fa-person-running icon-box"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-danger text-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6 class="mb-0">Permintaan Izin</h6><h2 class="mb-0"><?= $izin_pending ?></h2></div>
                            <i class="fa-solid fa-envelope icon-box"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- KOLOM KIRI: GRAFIK -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-0">
                            <h5 class="fw-bold"><i class="fa-solid fa-chart-bar me-2"></i>Statistik Kehadiran (7 Hari Terakhir)</h5>
                        </div>
                        <div class="card-body">
                            <!-- Canvas untuk Grafik -->
                            <canvas id="absensiChart" style="min-height: 300px;"></canvas>
                        </div>
                    </div>

                    <?php if ($role == 'admin' || $role == 'petugas'): ?>
                    <!-- Tombol Aksi Cepat (Admin/Petugas) -->
                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <a href="scan.php" class="btn btn-primary w-100 py-3"><i class="fa-solid fa-qrcode fa-lg me-2"></i> Scan QR</a>
                        </div>
                        <div class="col-md-4">
                            <a href="siswa.php" class="btn btn-outline-dark w-100 py-3"><i class="fa-solid fa-user-plus fa-lg me-2"></i> Siswa</a>
                        </div>
                        <div class="col-md-4">
                            <a href="rekap.php" class="btn btn-outline-success w-100 py-3"><i class="fa-solid fa-file-excel fa-lg me-2"></i> Laporan</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($role == 'guru'): ?>
                    <!-- Tombol Aksi Cepat (Guru) -->
                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <a href="siswa.php" class="btn btn-outline-dark w-100 py-3"><i class="fa-solid fa-users fa-lg me-2"></i> Data Siswa</a>
                        </div>
                        <div class="col-md-6">
                            <a href="rekap.php" class="btn btn-outline-success w-100 py-3"><i class="fa-solid fa-file-excel fa-lg me-2"></i> Laporan Absensi</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- KOLOM KANAN: JAM -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-dark text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0">Jam Server</h4>
                            <p class="small text-white-50">WIB (Asia/Jakarta)</p>
                            <h1 class="display-3 fw-bold my-3" id="jam-digital">00:00</h1>
                            <div class="border-top border-secondary pt-2">
                                <i class="fa-regular fa-calendar me-2"></i> <?= date('l, d F Y') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif ($role == 'siswa'): ?>
            <!-- DASHBOARD SISWA -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-primary shadow-sm border-0">
                        <h4 class="alert-heading">Selamat Datang, <?= $_SESSION['nama'] ?>!</h4>
                        <p>Jangan lupa melakukan absensi Masuk dan Pulang sesuai jadwal yang ditentukan.</p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body text-center p-5">
                            <h5 class="text-muted">Status Absen Hari Ini</h5>
                            <?php if($status_today): ?>
                                <h2 class="text-success fw-bold mt-3"><?= strtoupper($status_today['status_masuk']) ?></h2>
                                <p>Jam Masuk: <?= $status_today['jam_masuk'] ?></p>
                            <?php else: ?>
                                <h2 class="text-danger fw-bold mt-3">BELUM ABSEN</h2>
                                <p class="text-muted">Silakan scan QR Code Anda.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Menu Cepat</h5>
                            <div class="d-grid gap-2 mt-4">
                                <a href="riwayat.php" class="btn btn-outline-primary"><i class="fa-solid fa-list me-2"></i> Lihat Riwayat Kehadiran</a>
                                <a href="izin.php" class="btn btn-outline-warning"><i class="fa-solid fa-paper-plane me-2"></i> Ajukan Izin / Sakit</a>
                                <a href="profil.php" class="btn btn-outline-secondary"><i class="fa-solid fa-key me-2"></i> Ganti Password</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div> <!-- End Container -->
    </div> <!-- End Page Content -->
</div> <!-- End Wrapper -->

<!-- Bootstrap & Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Sidebar Script
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");
    toggleButton.onclick = function () { el.classList.toggle("toggled"); };

    // Jam Digital Sederhana
    function updateJam() {
        const now = new Date();
        const jam = String(now.getHours()).padStart(2, '0');
        const menit = String(now.getMinutes()).padStart(2, '0');
        const detik = String(now.getSeconds()).padStart(2, '0');
        if(document.getElementById('jam-digital')) {
            document.getElementById('jam-digital').innerText = jam + ':' + menit + ':' + detik;
        }
    }
    setInterval(updateJam, 1000);
    updateJam();

    // --- SCRIPT CHART.JS (Hanya render jika canvas ada / Role Admin) ---
    <?php if ($role == 'admin' || $role == 'petugas' || $role == 'guru'): ?>
    const ctx = document.getElementById('absensiChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar', // Tipe Grafik: 'bar', 'line', 'pie', dll
            data: {
                labels: <?= json_encode($labels) ?>, // Ambil label tanggal dari PHP
                datasets: [
                    {
                        label: 'Hadir',
                        data: <?= json_encode($data_hadir) ?>, // Data Hadir
                        backgroundColor: 'rgba(25, 135, 84, 0.7)', // Warna Hijau
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    },
                    {
                        label: 'Terlambat',
                        data: <?= json_encode($data_terlambat) ?>, // Data Terlambat
                        backgroundColor: 'rgba(255, 193, 7, 0.7)', // Warna Kuning
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 } // Agar sumbu Y bilangan bulat
                    }
                },
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
    }
    <?php endif; ?>
</script>

</body>
</html>
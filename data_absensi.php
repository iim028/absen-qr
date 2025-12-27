<?php
session_start();
include 'config/db.php';

// Cek akses: Siswa tidak boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] == 'siswa') {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$pesan = "";

// Hitung Notifikasi Izin Pending (Untuk Sidebar)
$q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'");
$izin_pending = mysqli_fetch_assoc($q_notif)['total'];

// PROSES UPDATE ABSENSI
if (isset($_POST['update_absen'])) {
    $id_siswa = $_POST['id_siswa'];
    $tanggal  = $_POST['tanggal'];
    $status   = $_POST['status'];
    $ket      = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // Cek apakah data di tanggal itu sudah ada
    $cek = mysqli_query($conn, "SELECT id FROM absensi WHERE siswa_id='$id_siswa' AND tanggal='$tanggal'");
    
    if (mysqli_num_rows($cek) > 0) {
        // Update data lama
        $q = "UPDATE absensi SET status_masuk='$status', keterangan='$ket' WHERE siswa_id='$id_siswa' AND tanggal='$tanggal'";
    } else {
        // Insert Baru (Jika belum ada data sama sekali)
        $jam = date('H:i:s'); // Jam saat ini sebagai default
        $q = "INSERT INTO absensi (siswa_id, tanggal, jam_masuk, status_masuk, keterangan) 
              VALUES ('$id_siswa', '$tanggal', '$jam', '$status', '$ket')";
    }

    if (mysqli_query($conn, $q)) {
        $pesan = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-check-circle me-2'></i>Data absensi berhasil diperbarui.
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal: " . mysqli_error($conn) . "</div>";
    }
}

// Filter Tanggal (Default hari ini)
$tgl_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kelola Data Absensi - Absensi QR</title>
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
                
                <!-- Menu Aktif -->
                <a href="data_absensi.php" class="list-group-item list-group-item-action active">
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
                <!-- Menu Aktif -->
                <a href="data_absensi.php" class="list-group-item list-group-item-action active">
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
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Koreksi Data Absensi</h3>
            </div>

            <div class="alert alert-info border-0 shadow-sm">
                <i class="fa-solid fa-info-circle me-2"></i>
                Halaman ini digunakan untuk mengubah status kehadiran siswa secara manual (misal: Siswa lupa bawa kartu, atau izin lisan).
            </div>
            
            <?= $pesan ?>

            <!-- Filter Tanggal -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-auto">
                            <label class="form-label fw-bold">Pilih Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= $tgl_filter ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-2"></i>Cari Data</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Data -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Data Absensi Tanggal: <b><?= date('d-m-Y', strtotime($tgl_filter)) ?></b></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Status Saat Ini</th>
                                    <th>Aksi (Update)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Query: Ambil semua siswa, Join dengan data absensi pada tanggal yang dipilih
                                $sql = "SELECT s.id as sid, s.nisn, s.nama, s.kelas, a.status_masuk, a.keterangan 
                                        FROM siswa s 
                                        LEFT JOIN absensi a ON s.id = a.siswa_id AND a.tanggal = '$tgl_filter'
                                        ORDER BY s.kelas ASC, s.nama ASC";
                                $q = mysqli_query($conn, $sql);
                                
                                if(mysqli_num_rows($q) > 0) {
                                    while($row = mysqli_fetch_assoc($q)) {
                                        $status = $row['status_masuk'] ? $row['status_masuk'] : 'alpa'; // Default 'alpa' jika belum ada data
                                        
                                        // Warna badge status
                                        $badge_color = 'danger';
                                        if($status == 'hadir') $badge_color = 'success';
                                        elseif($status == 'izin') $badge_color = 'info';
                                        elseif($status == 'sakit') $badge_color = 'primary';
                                        elseif($status == 'terlambat') $badge_color = 'warning';
                                ?>
                                <tr>
                                    <td><?= $row['nisn'] ?></td>
                                    <td><?= $row['nama'] ?></td>
                                    <td><?= $row['kelas'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $badge_color ?>">
                                            <?= strtoupper($status) ?>
                                        </span>
                                        <?php if(!empty($row['keterangan'])): ?>
                                            <br><small class="text-muted fst-italic"><?= $row['keterangan'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Form Update Inline -->
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="id_siswa" value="<?= $row['sid'] ?>">
                                            <input type="hidden" name="tanggal" value="<?= $tgl_filter ?>">
                                            
                                            <select name="status" class="form-select form-select-sm" style="width: 110px;">
                                                <option value="hadir" <?= $status=='hadir'?'selected':'' ?>>Hadir</option>
                                                <option value="sakit" <?= $status=='sakit'?'selected':'' ?>>Sakit</option>
                                                <option value="izin" <?= $status=='izin'?'selected':'' ?>>Izin</option>
                                                <option value="alpa" <?= $status=='alpa'?'selected':'' ?>>Alpa</option>
                                            </select>
                                            
                                            <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Keterangan..." value="<?= $row['keterangan'] ?>">
                                            
                                            <button type="submit" name="update_absen" class="btn btn-sm btn-success" title="Simpan Perubahan">
                                                <i class="fa-solid fa-floppy-disk"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Belum ada data siswa.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
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
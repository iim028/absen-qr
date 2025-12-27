<?php
session_start();
include 'config/db.php';

// Cek akses: Hanya admin, petugas, dan guru yang boleh akses
if (!isset($_SESSION['login']) || $_SESSION['role'] == 'siswa') { 
    header("Location: dashboard.php"); 
    exit; 
}

$role = $_SESSION['role'];
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// --- FILTER KELAS & JURUSAN ---
$f_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$f_jurusan = isset($_GET['jurusan']) ? $_GET['jurusan'] : '';

// Hitung Notifikasi Izin Pending (Untuk Sidebar)
$q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'");
$izin_pending = mysqli_fetch_assoc($q_notif)['total'];

// Logic Export Excel
if (isset($_GET['export'])) {
    header("Content-type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=Rekap_Absensi_$bulan-$tahun.xls");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Rekap Absensi - Absensi QR</title>
    <?php if(!isset($_GET['export'])) { ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="assets/style.css">
    <?php } ?>
</head>
<body>

<?php if(!isset($_GET['export'])) { ?>
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
                
                <!-- Menu Aktif -->
                <a href="rekap.php" class="list-group-item list-group-item-action active">
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
                <!-- Menu Aktif -->
                <a href="rekap.php" class="list-group-item list-group-item-action active">
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
            
            <!-- FILTER CARD -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filter Laporan</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <!-- Filter Bulan -->
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Bulan</label>
                            <select name="bulan" class="form-select">
                                <?php
                                $bln = array(1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
                                for($m=1; $m<=12; $m++){
                                    $selected = ($m == $bulan) ? 'selected' : '';
                                    echo "<option value='$m' $selected>".$bln[$m]."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <!-- Filter Tahun -->
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Tahun</label>
                            <input type="number" name="tahun" value="<?= $tahun ?>" class="form-control" placeholder="Tahun">
                        </div>
                        <!-- Filter Kelas -->
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Kelas</label>
                            <select name="kelas" class="form-select">
                                <option value="">Semua</option>
                                <option value="X" <?= $f_kelas=='X'?'selected':'' ?>>X</option>
                                <option value="XI" <?= $f_kelas=='XI'?'selected':'' ?>>XI</option>
                                <option value="XII" <?= $f_kelas=='XII'?'selected':'' ?>>XII</option>
                            </select>
                        </div>
                        <!-- Filter Jurusan -->
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Jurusan</label>
                            <select name="jurusan" class="form-select">
                                <option value="">Semua</option>
                                <option value="RPL" <?= $f_jurusan=='RPL'?'selected':'' ?>>RPL</option>
                                <option value="TKJ" <?= $f_jurusan=='TKJ'?'selected':'' ?>>TKJ</option>
                                <option value="MM" <?= $f_jurusan=='MM'?'selected':'' ?>>MM</option>
                            </select>
                        </div>
                        <!-- Tombol Aksi -->
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Cari</button>
                            <button type="submit" name="export" value="true" class="btn btn-success"><i class="fa-solid fa-file-excel"></i> Export Excel</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
<?php } // End If Not Export ?>

                    <?php if(!isset($_GET['export'])) { echo "<h5 class='mb-3'>Data Absensi</h5>"; } else { echo "<h3>Laporan Absensi Periode $bulan-$tahun</h3>"; } ?>
                    
                    <div class="table-responsive">
                        <table border="1" class="table table-bordered table-striped table-hover align-middle w-100">
                            <thead class="<?= isset($_GET['export']) ? '' : 'table-dark' ?>">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Jurusan</th>
                                    <th>Masuk</th>
                                    <th>Keluar</th>
                                    <th>Status</th>
                                    <th>Ket</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // --- QUERY DINAMIS BERDASARKAN FILTER ---
                                $sql = "SELECT a.*, s.nama, s.nisn, s.kelas, s.jurusan 
                                          FROM absensi a 
                                          JOIN siswa s ON a.siswa_id = s.id 
                                          WHERE MONTH(a.tanggal) = '$bulan' AND YEAR(a.tanggal) = '$tahun'";
                                
                                // Tambahkan kondisi jika filter kelas dipilih
                                if (!empty($f_kelas)) {
                                    $sql .= " AND s.kelas = '$f_kelas'";
                                }
                                // Tambahkan kondisi jika filter jurusan dipilih
                                if (!empty($f_jurusan)) {
                                    $sql .= " AND s.jurusan = '$f_jurusan'";
                                }

                                $sql .= " ORDER BY a.tanggal DESC, s.kelas ASC";
                                
                                $exec = mysqli_query($conn, $sql);
                                $no = 1;
                                if(mysqli_num_rows($exec) > 0) {
                                    while ($r = mysqli_fetch_assoc($exec)) {
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $r['tanggal'] ?></td>
                                    <td><?= $r['nisn'] ?></td>
                                    <td><?= $r['nama'] ?></td>
                                    <td><?= $r['kelas'] ?></td>
                                    <td><?= $r['jurusan'] ?></td>
                                    <td><?= $r['jam_masuk'] ?></td>
                                    <td><?= $r['jam_keluar'] ?></td>
                                    <td><?= strtoupper($r['status_masuk']) ?></td>
                                    <td><?= $r['keterangan'] ?></td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='10' align='center'>Data tidak ditemukan</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

<?php if(!isset($_GET['export'])) { ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");
    toggleButton.onclick = function () { el.classList.toggle("toggled"); };
</script>
<?php } ?>

</body>
</html>
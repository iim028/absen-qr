<?php
session_start();
include 'config/db.php';
include 'lib/wa_api.php'; 

// Cek Login
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];

// --- LOGIC NOTIFIKASI SIDEBAR ---
$izin_pending = 0;
if($role != 'siswa'){
    $q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'");
    $izin_pending = mysqli_fetch_assoc($q_notif)['total'];
}

// --- LOGIC SISWA: MENGIRIM IZIN ---
if (isset($_POST['kirim_izin'])) {
    
    // VALIDASI PENTING: Cek apakah ID Siswa terdeteksi dalam sesi
    if (!isset($_SESSION['siswa_id']) || empty($_SESSION['siswa_id'])) {
        echo "<script>
            alert('ERROR FATAL: Data Siswa tidak ditemukan! \\n\\nKemungkinan penyebab:\\n1. Akun login ini dibuat manual tapi data siswanya belum diinput.\\n2. NISN di data user tidak cocok dengan data siswa.\\n\\nSolusi: Hubungi Admin untuk memperbaiki data siswa Anda.');
            window.location='dashboard.php';
        </script>";
        exit;
    }

    $siswa_id = $_SESSION['siswa_id'];
    $tgl_mulai = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $tgl_selesai = mysqli_real_escape_string($conn, $_POST['tgl_selesai']);
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan']);
    
    // Upload Bukti
    $bukti = $_FILES['bukti']['name'];
    if ($bukti != "") {
        $target = "assets/uploads/" . basename($bukti);
        // Pastikan folder ada
        if (!file_exists('assets/uploads')) { mkdir('assets/uploads', 0777, true); }
        move_uploaded_file($_FILES['bukti']['tmp_name'], $target);
    } else {
        $bukti = "no-image.jpg"; // Default jika kosong (walau required)
    }

    // Query Insert
    $sql = "INSERT INTO perizinan (siswa_id, tanggal_mulai, tanggal_selesai, alasan, bukti_foto, status) 
            VALUES ('$siswa_id', '$tgl_mulai', '$tgl_selesai', '$alasan', '$bukti', 'pending')";
    
    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Izin berhasil diajukan. Menunggu persetujuan Admin/Guru.'); window.location='izin.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim data: " . mysqli_error($conn) . "');</script>";
    }
}

// --- LOGIC ADMIN/GURU/PETUGAS: APPROVE/REJECT ---
if (isset($_GET['aksi']) && ($role == 'admin' || $role == 'guru' || $role == 'petugas')) {
    $id_izin = $_GET['id'];
    $status_baru = $_GET['aksi']; 
    
    $update = mysqli_query($conn, "UPDATE perizinan SET status = '$status_baru' WHERE id = '$id_izin'");
    
    if ($status_baru == 'disetujui') {
        // Ambil detail izin untuk dimasukkan ke tabel absensi
        $q_izin = mysqli_query($conn, "SELECT * FROM perizinan JOIN siswa ON perizinan.siswa_id = siswa.id WHERE perizinan.id = '$id_izin'");
        $d_izin = mysqli_fetch_assoc($q_izin);
        
        $tgl = $d_izin['tanggal_mulai'];
        $sid = $d_izin['siswa_id'];
        $ket = "Izin: " . $d_izin['alasan'];
        
        // Cek agar tidak duplikat absen di tanggal yang sama
        $cek_absen = mysqli_query($conn, "SELECT * FROM absensi WHERE siswa_id='$sid' AND tanggal='$tgl'");
        if(mysqli_num_rows($cek_absen) == 0){
             mysqli_query($conn, "INSERT INTO absensi (siswa_id, tanggal, jam_masuk, status_masuk, keterangan) 
                                  VALUES ('$sid', '$tgl', '00:00:00', 'izin', '$ket')");
        }
    }
    
    if($update) {
        echo "<script>window.location='izin.php';</script>";
    } else {
        echo "Gagal update status.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Manajemen Izin - Absensi QR</title>
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
                <a href="libur.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-calendar-xmark"></i> Atur Libur
                </a>

                <!-- Menu Aktif -->
                <a href="izin.php" class="list-group-item list-group-item-action active">
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
                <!-- Menu Aktif -->
                <a href="izin.php" class="list-group-item list-group-item-action active">
                    <i class="fa-solid fa-envelope-open-text"></i> Approval Izin
                    <?php if($izin_pending > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-2"><?= $izin_pending ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <!-- Menu Siswa -->
            <?php if ($role == 'siswa'): ?>
                <!-- Menu Aktif -->
                <a href="izin.php" class="list-group-item list-group-item-action active">
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
            
            <!-- TAMPILAN SISWA -->
            <?php if ($role == 'siswa'): ?>
            <div class="row">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa-solid fa-pen-to-square me-2"></i>Form Pengajuan Izin</h5>
                        </div>
                        <div class="card-body">
                            <!-- Pesan Peringatan jika ID Siswa Hilang -->
                            <?php if (!isset($_SESSION['siswa_id'])): ?>
                                <div class="alert alert-danger">
                                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                    <b>Akun Bermasalah!</b> Data siswa Anda tidak terhubung dengan akun login ini. Silakan lapor ke Admin untuk memperbaiki data "NISN" Anda.
                                </div>
                            <?php else: ?>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Dari Tanggal</label>
                                    <input type="date" name="tgl_mulai" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sampai Tanggal</label>
                                    <input type="date" name="tgl_selesai" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alasan</label>
                                    <textarea name="alasan" class="form-control" rows="3" required placeholder="Sakit / Izin Keluarga / Lainnya..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Bukti Foto (Surat Dokter/dll)</label>
                                    <input type="file" name="bukti" class="form-control" required>
                                </div>
                                <button type="submit" name="kirim_izin" class="btn btn-primary w-100"><i class="fa-solid fa-paper-plane me-2"></i>Kirim Pengajuan</button>
                            </form>
                            
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fa-solid fa-history me-2"></i>Riwayat Izin Saya</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Alasan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Cegah error jika siswa_id kosong di query riwayat
                                        $sid_safe = isset($_SESSION['siswa_id']) ? $_SESSION['siswa_id'] : 0;
                                        
                                        $q = mysqli_query($conn, "SELECT * FROM perizinan WHERE siswa_id='$sid_safe' ORDER BY id DESC");
                                        if (mysqli_num_rows($q) > 0) {
                                            while($r=mysqli_fetch_assoc($q)){
                                                $bg = ($r['status']=='pending')?'secondary':(($r['status']=='disetujui')?'success':'danger');
                                                echo "<tr>
                                                    <td>{$r['tanggal_mulai']}</td>
                                                    <td>{$r['alasan']}</td>
                                                    <td><span class='badge bg-$bg'>".strtoupper($r['status'])."</span></td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='3' class='text-center text-muted'>Belum ada riwayat izin.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN ADMIN / GURU / PETUGAS -->
            <?php else: ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa-solid fa-check-double me-2"></i>Daftar Pengajuan Izin</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Siswa</th>
                                    <th>Tanggal</th>
                                    <th>Alasan</th>
                                    <th>Bukti</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = mysqli_query($conn, "SELECT p.*, s.nama FROM perizinan p JOIN siswa s ON p.siswa_id = s.id ORDER BY field(p.status, 'pending') DESC, p.id DESC");
                                while($row = mysqli_fetch_assoc($q)){
                                ?>
                                <tr>
                                    <td><span class="fw-bold"><?= $row['nama'] ?></span></td>
                                    <td><?= $row['tanggal_mulai'] ?> s.d <?= $row['tanggal_selesai'] ?></td>
                                    <td><?= $row['alasan'] ?></td>
                                    <td><a href="assets/uploads/<?= $row['bukti_foto'] ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fa-solid fa-image me-1"></i>Lihat</a></td>
                                    <td>
                                        <span class="badge bg-<?= ($row['status'] == 'pending') ? 'warning' : (($row['status'] == 'disetujui') ? 'success' : 'danger') ?>">
                                            <?= strtoupper($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'pending'): ?>
                                            <div class="btn-group" role="group">
                                                <a href="izin.php?aksi=disetujui&id=<?= $row['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Setujui?')"><i class="fa-solid fa-check"></i></a>
                                                <a href="izin.php?aksi=ditolak&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tolak?')"><i class="fa-solid fa-times"></i></a>
                                            </div>
                                        <?php else: ?>
                                            <i class="fa-solid fa-lock text-muted"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
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
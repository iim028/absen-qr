<?php 
session_start();
include 'config/db.php';

// Cek keamanan: Admin, Petugas, dan Guru boleh akses
if (!isset($_SESSION['login']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'petugas' && $_SESSION['role'] != 'guru')) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$pesan = "";
$edit_mode = false;
$data_edit = null;

// Hitung Notifikasi Izin Pending (Untuk Sidebar)
$q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'");
$izin_pending = mysqli_fetch_assoc($q_notif)['total'];

// --- LOGIC HAPUS SISWA ---
if (isset($_GET['hapus'])) {
    $id_siswa = $_GET['hapus'];
    $q_cek = mysqli_query($conn, "SELECT nisn FROM siswa WHERE id='$id_siswa'");
    if(mysqli_num_rows($q_cek) > 0) {
        $d_siswa = mysqli_fetch_assoc($q_cek);
        $nisn_hapus = $d_siswa['nisn'];
        mysqli_query($conn, "DELETE FROM absensi WHERE siswa_id='$id_siswa'");
        mysqli_query($conn, "DELETE FROM perizinan WHERE siswa_id='$id_siswa'");
        mysqli_query($conn, "DELETE FROM users WHERE username='$nisn_hapus'");
        mysqli_query($conn, "DELETE FROM siswa WHERE id='$id_siswa'");
        echo "<script>alert('Data Siswa & Akun Login berhasil dihapus.'); window.location='siswa.php';</script>";
    }
}

// --- LOGIC RESET PASSWORD ---
if (isset($_GET['reset'])) {
    $id_siswa = $_GET['reset'];
    $q_cari = mysqli_query($conn, "SELECT nisn, nama FROM siswa WHERE id='$id_siswa'");
    if (mysqli_num_rows($q_cari) > 0) {
        $d_siswa = mysqli_fetch_assoc($q_cari);
        $nisn = $d_siswa['nisn'];
        $pass_default = md5($nisn);
        $reset = mysqli_query($conn, "UPDATE users SET password='$pass_default' WHERE username='$nisn'");
        if ($reset) {
            echo "<script>alert('BERHASIL! Password siswa a.n {$d_siswa['nama']} direset ke NISN.'); window.location='siswa.php';</script>";
        }
    }
}

// --- LOGIC IMPORT (CSV) ---
if (isset($_POST['import_siswa'])) {
    $file = $_FILES['file_siswa']['tmp_name'];
    $ext = pathinfo($_FILES['file_siswa']['name'], PATHINFO_EXTENSION);
    if ($ext != 'csv') {
        $pesan = "<div class='alert alert-danger'>Format file harus .CSV!</div>";
    } else {
        $handle = fopen($file, "r");
        $sukses = 0; $gagal = 0; $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row++; if ($row == 1) continue; 
            $nisn = mysqli_real_escape_string($conn, $data[0]);
            $nama = mysqli_real_escape_string($conn, $data[1]);
            $jk = mysqli_real_escape_string($conn, strtoupper($data[2]));
            $kelas = mysqli_real_escape_string($conn, $data[3]);
            $jurusan = mysqli_real_escape_string($conn, $data[4]);
            $hp = mysqli_real_escape_string($conn, $data[5]);
            $qr_code = $nisn;
            
            $cek = mysqli_query($conn, "SELECT id FROM siswa WHERE nisn='$nisn'");
            if (mysqli_num_rows($cek) == 0 && !empty($nisn)) {
                $sql_siswa = "INSERT INTO siswa (nisn, nama, jenis_kelamin, kelas, jurusan, no_hp_ortu, qr_code) VALUES ('$nisn', '$nama', '$jk', '$kelas', '$jurusan', '$hp', '$qr_code')";
                if (mysqli_query($conn, $sql_siswa)) {
                    $pass_default = md5($nisn);
                    mysqli_query($conn, "INSERT INTO users (username, password, role, nama_lengkap) VALUES ('$nisn', '$pass_default', 'siswa', '$nama')");
                    $sukses++;
                } else { $gagal++; }
            } else { $gagal++; }
        }
        fclose($handle);
        $pesan = "<div class='alert alert-success'>Import Selesai!<br>Sukses: <b>$sukses</b>.<br>Gagal: <b>$gagal</b>.</div>";
    }
}

// --- LOGIC SIMPAN / UPDATE ---
if (isset($_POST['simpan']) || isset($_POST['update'])) {
    $nisn = mysqli_real_escape_string($conn, $_POST['nisn']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jk = mysqli_real_escape_string($conn, $_POST['jk']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $hp = mysqli_real_escape_string($conn, $_POST['hp']);
    $qr_code = $nisn; 

    if (isset($_POST['simpan'])) {
        $cek_nisn = mysqli_query($conn, "SELECT nisn FROM siswa WHERE nisn = '$nisn'");
        if (mysqli_num_rows($cek_nisn) > 0) {
            $pesan = "<div class='alert alert-danger'>Gagal! NISN $nisn sudah terdaftar.</div>";
        } else {
            $sql = "INSERT INTO siswa (nisn, nama, jenis_kelamin, kelas, jurusan, no_hp_ortu, qr_code) VALUES ('$nisn', '$nama', '$jk', '$kelas', '$jurusan', '$hp', '$qr_code')";
            if (mysqli_query($conn, $sql)) {
                $pass_default = md5($nisn); 
                mysqli_query($conn, "INSERT INTO users (username, password, role, nama_lengkap) VALUES ('$nisn', '$pass_default', 'siswa', '$nama')");
                $pesan = "<div class='alert alert-success'>Sukses! Data Siswa ditambahkan.</div>";
            }
        }
    } elseif (isset($_POST['update'])) {
        $id_lama = $_POST['id_siswa']; $nisn_lama = $_POST['nisn_lama'];
        $sql_update = "UPDATE siswa SET nisn='$nisn', nama='$nama', jenis_kelamin='$jk', kelas='$kelas', jurusan='$jurusan', no_hp_ortu='$hp', qr_code='$nisn' WHERE id='$id_lama'";
        if (mysqli_query($conn, $sql_update)) {
            if ($nisn != $nisn_lama) { mysqli_query($conn, "UPDATE users SET username='$nisn' WHERE username='$nisn_lama'"); }
            mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama' WHERE username='$nisn'");
            echo "<script>alert('Data Siswa Berhasil Diupdate!'); window.location='siswa.php';</script>";
        }
    }
}

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_edit = $_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM siswa WHERE id='$id_edit'");
    $data_edit = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Manajemen Siswa - Absensi QR</title>
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
            
            <!-- MENU KHUSUS ADMIN & PETUGAS -->
            <?php if ($role == 'admin' || $role == 'petugas'): ?>
                
                <?php if ($role == 'admin'): ?>
                <a href="manajemen_user.php" class="list-group-item list-group-item-action">
                    <i class="fa-solid fa-users-gear"></i> Manajemen User
                </a>
                <?php endif; ?>

                <!-- Menu Siswa Aktif -->
                <a href="siswa.php" class="list-group-item list-group-item-action active">
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

            <!-- MENU KHUSUS GURU (TANPA Scan QR & Atur Libur) -->
            <?php if ($role == 'guru'): ?>
                <!-- Menu Siswa Aktif -->
                <a href="siswa.php" class="list-group-item list-group-item-action active">
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
            <div class="row">
                <!-- FORM INPUT (TAMBAH / EDIT) -->
                <div class="col-md-4">
                    
                    <!-- KOTAK IMPORT EXCEL -->
                    <?php if(!$edit_mode): ?>
                    <div class="card shadow-sm border-success mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fa-solid fa-file-import me-2"></i>Import Siswa (Excel/CSV)</h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-2">Upload data siswa sekaligus (.csv)</p>
                            <a href="assets/template_siswa.csv" class="btn btn-outline-success btn-sm w-100 mb-3" download>
                                <i class="fa-solid fa-download me-1"></i> Download Template
                            </a>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3"><input type="file" name="file_siswa" class="form-control" required accept=".csv"></div>
                                <button type="submit" name="import_siswa" class="btn btn-success w-100"><i class="fa-solid fa-upload me-2"></i>Import Data</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- FORM MANUAL -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header <?= $edit_mode ? 'bg-warning text-dark' : 'bg-primary text-white' ?>">
                            <h5 class="mb-0">
                                <i class="fa-solid <?= $edit_mode ? 'fa-pen-to-square' : 'fa-user-plus' ?> me-2"></i>
                                <?= $edit_mode ? 'Edit Data Siswa' : 'Tambah Siswa Baru' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?= $pesan ?>
                            <form method="POST" action="siswa.php">
                                <?php if($edit_mode): ?>
                                    <input type="hidden" name="id_siswa" value="<?= $data_edit['id'] ?>">
                                    <input type="hidden" name="nisn_lama" value="<?= $data_edit['nisn'] ?>">
                                <?php endif; ?>

                                <div class="mb-2">
                                    <label class="form-label">NISN</label>
                                    <input type="number" name="nisn" class="form-control" required placeholder="12345" value="<?= $edit_mode ? $data_edit['nisn'] : '' ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" required value="<?= $edit_mode ? $data_edit['nama'] : '' ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <select name="jk" class="form-select" required>
                                        <option value="">Pilih...</option>
                                        <option value="L" <?= ($edit_mode && $data_edit['jenis_kelamin'] == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= ($edit_mode && $data_edit['jenis_kelamin'] == 'P') ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Kelas</label>
                                    <select name="kelas" class="form-select" required>
                                        <option value="">Pilih</option>
                                        <?php 
                                            $kel = ['X','XI','XII'];
                                            foreach($kel as $k) {
                                                $sel = ($edit_mode && $data_edit['kelas'] == $k) ? 'selected' : '';
                                                echo "<option value='$k' $sel>$k</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Jurusan</label>
                                    <select name="jurusan" class="form-select" required>
                                        <option value="">Pilih</option>
                                        <?php 
                                            $jur = ['RPL','TKJ','MM'];
                                            foreach($jur as $j) {
                                                $sel = ($edit_mode && $data_edit['jurusan'] == $j) ? 'selected' : '';
                                                echo "<option value='$j' $sel>$j</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">No HP Ortu</label>
                                    <input type="text" name="hp" class="form-control" required value="<?= $edit_mode ? $data_edit['no_hp_ortu'] : '' ?>">
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="<?= $edit_mode ? 'update' : 'simpan' ?>" class="btn <?= $edit_mode ? 'btn-warning' : 'btn-primary' ?>">
                                        <?= $edit_mode ? 'Simpan Perubahan' : 'Simpan Data' ?>
                                    </button>
                                    
                                    <?php if($edit_mode): ?>
                                        <a href="siswa.php" class="btn btn-secondary">Batal Edit</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TABLE DATA -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Data Siswa Terdaftar</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>NISN</th><th>Nama</th><th>L/P</th><th>Kelas</th><th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $tampil = mysqli_query($conn, "SELECT * FROM siswa ORDER BY id DESC");
                                        while ($r = mysqli_fetch_array($tampil)) {
                                        ?>
                                        <tr class="<?= ($edit_mode && $data_edit['id'] == $r['id']) ? 'table-warning' : '' ?>">
                                            <td><?= $r['nisn'] ?></td>
                                            <td><?= $r['nama'] ?></td>
                                            <td><?= $r['jenis_kelamin'] ?></td>
                                            <td><?= $r['kelas'] ?> - <?= $r['jurusan'] ?></td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Edit -->
                                                    <a href="siswa.php?edit=<?= $r['id'] ?>" class="btn btn-outline-primary" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                                    
                                                    <!-- Cetak -->
                                                    <a href="cetak_kartu.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-outline-info" title="Cetak"><i class="fa-solid fa-id-card"></i></a>
                                                    
                                                    <!-- Reset Password -->
                                                    <a href="siswa.php?reset=<?= $r['id'] ?>" class="btn btn-outline-warning text-dark" onclick="return confirm('Reset password?')" title="Reset Pass"><i class="fa-solid fa-key"></i></a>
                                                    
                                                    <!-- Hapus -->
                                                    <a href="siswa.php?hapus=<?= $r['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin hapus siswa ini? \nSemua data absen & akun login juga akan terhapus!')" title="Hapus"><i class="fa-solid fa-trash"></i></a>
                                                </div>
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
    toggleButton.onclick = function () { el.classList.toggle("toggled"); };
</script>
</body>
</html>
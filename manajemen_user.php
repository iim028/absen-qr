<?php
session_start();
include 'config/db.php';

// Cek akses: Hanya Admin Utama
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$role = $_SESSION['role'];
$pesan = "";
$edit_mode = false;
$data_edit = null;

// Hitung Notifikasi (Sidebar)
$q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status = 'pending'");
$izin_pending = mysqli_fetch_assoc($q_notif)['total'];

// 1. PROSES TAMBAH USER
if (isset($_POST['tambah_user'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $role_input = $_POST['role'];
    $is_active = 1; // Default aktif

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "<div class='alert alert-danger'>Username '$username' sudah digunakan!</div>";
    } else {
        $sql = "INSERT INTO users (username, password, role, nama_lengkap, is_active) VALUES ('$username', '$password', '$role_input', '$nama', '$is_active')";
        if (mysqli_query($conn, $sql)) {
            $pesan = "<div class='alert alert-success'>User berhasil ditambahkan.</div>";
        } else {
            $pesan = "<div class='alert alert-danger'>Gagal: " . mysqli_error($conn) . "</div>";
        }
    }
}

// 2. PROSES UPDATE USER (EDIT DATA)
if (isset($_POST['update_user'])) {
    $id_user  = $_POST['id_user'];
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role_input = $_POST['role'];
    
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $sql = "UPDATE users SET nama_lengkap='$nama', username='$username', password='$password', role='$role_input' WHERE id='$id_user'";
    } else {
        $sql = "UPDATE users SET nama_lengkap='$nama', username='$username', role='$role_input' WHERE id='$id_user'";
    }

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data user diperbarui!'); window.location='manajemen_user.php';</script>";
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal update.</div>";
    }
}

// 3. PROSES GANTI STATUS (AKTIF/NONAKTIF) - FITUR DARI PDF
if (isset($_GET['status'])) {
    $id = $_GET['id'];
    $val = $_GET['status']; // 1 atau 0
    mysqli_query($conn, "UPDATE users SET is_active='$val' WHERE id='$id'");
    header("Location: manajemen_user.php");
}

// 4. PROSES HAPUS
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('Tidak bisa menghapus akun sendiri!');</script>";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id = '$id'");
        header("Location: manajemen_user.php");
    }
}

// 5. GENERATE AKUN SISWA
if (isset($_POST['sync_siswa'])) {
    $jumlah_dibuat = 0;
    $q_siswa = mysqli_query($conn, "SELECT * FROM siswa");
    while ($s = mysqli_fetch_assoc($q_siswa)) {
        $nisn = $s['nisn'];
        $nama = mysqli_real_escape_string($conn, $s['nama']);
        
        $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$nisn'");
        if (mysqli_num_rows($cek_user) == 0) {
            $pass_default = md5($nisn);
            // Default siswa aktif (is_active = 1)
            $sql_insert = "INSERT INTO users (username, password, role, nama_lengkap, is_active) 
                           VALUES ('$nisn', '$pass_default', 'siswa', '$nama', 1)";
            mysqli_query($conn, $sql_insert);
            $jumlah_dibuat++;
        }
    }
    $pesan = ($jumlah_dibuat > 0) ? 
             "<div class='alert alert-success'>Berhasil generate <b>$jumlah_dibuat</b> akun siswa.</div>" : 
             "<div class='alert alert-info'>Semua siswa sudah punya akun.</div>";
}

// 6. MODE EDIT
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_edit = $_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id='$id_edit'");
    $data_edit = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Manajemen User - Absensi QR</title>
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
            <a href="dashboard.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            
            <!-- Menu Admin Aktif -->
            <a href="manajemen_user.php" class="list-group-item list-group-item-action active"><i class="fa-solid fa-users-gear"></i> Manajemen User</a>
            <a href="siswa.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-user-graduate"></i> Data Siswa</a>
            <a href="scan.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-camera"></i> Scan QR</a>
            <a href="rekap.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-file-excel"></i> Rekap Absensi</a>
            <a href="data_absensi.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-pen-to-square"></i> Edit Absensi</a>
            <a href="libur.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-calendar-xmark"></i> Atur Libur</a>
            <a href="izin.php" class="list-group-item list-group-item-action">
                <i class="fa-solid fa-envelope-open-text"></i> Approval Izin
                <?php if($izin_pending > 0): ?><span class="badge bg-danger rounded-pill ms-2"><?= $izin_pending ?></span><?php endif; ?>
            </a>
            <a href="pengaturan.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-clock"></i> Atur Jam</a>

            <div class="mt-4 border-top border-secondary pt-2">
                <a href="profil.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-user-circle"></i> Profil Saya</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4">
            <button class="btn btn-dark" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                    <li class="nav-item">
                        <span class="nav-link fw-bold text-secondary">Halo, <?= $_SESSION['nama'] ?> (ADMIN)</span>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="container-fluid px-4 mt-4">
            <div class="row">
                <!-- FORM -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header <?= $edit_mode ? 'bg-warning text-dark' : 'bg-primary text-white' ?>">
                            <h5 class="mb-0">
                                <i class="fa-solid <?= $edit_mode ? 'fa-pen-to-square' : 'fa-user-plus' ?> me-2"></i>
                                <?= $edit_mode ? 'Edit Data User' : 'Tambah User Manual' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?= $pesan ?>
                            <form method="POST" action="manajemen_user.php">
                                <?php if($edit_mode): ?>
                                    <input type="hidden" name="id_user" value="<?= $data_edit['id'] ?>">
                                <?php endif; ?>

                                <div class="mb-2">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" required value="<?= $edit_mode ? $data_edit['nama_lengkap'] : '' ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required value="<?= $edit_mode ? $data_edit['username'] : '' ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Password <?= $edit_mode ? '<small class="text-danger">(Isi jika ingin ubah)</small>' : '' ?></label>
                                    <input type="password" name="password" class="form-control" <?= $edit_mode ? '' : 'required' ?>>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-select">
                                        <?php 
                                        $roles = ['admin', 'petugas', 'guru', 'siswa'];
                                        foreach($roles as $r) {
                                            $selected = ($edit_mode && $data_edit['role'] == $r) ? 'selected' : '';
                                            echo "<option value='$r' $selected>" . ucfirst($r) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="<?= $edit_mode ? 'update_user' : 'tambah_user' ?>" class="btn <?= $edit_mode ? 'btn-warning' : 'btn-primary' ?>">
                                        <?= $edit_mode ? 'Simpan Perubahan' : 'Simpan User' ?>
                                    </button>
                                    <?php if($edit_mode): ?>
                                        <a href="manajemen_user.php" class="btn btn-secondary">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if(!$edit_mode): ?>
                    <div class="card shadow-sm border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fa-solid fa-sync me-2"></i>Generate Akun Siswa</h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">Cek tabel siswa dan buatkan akun login jika belum ada.</p>
                            <form method="POST">
                                <button type="submit" name="sync_siswa" class="btn btn-warning w-100">
                                    <i class="fa-solid fa-rotate me-2"></i>Sinkronisasi Akun
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- TABLE -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white"><h5 class="mb-0"><i class="fa-solid fa-users me-2"></i>Daftar Pengguna</h5></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nama</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $q = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, id DESC");
                                        while($r = mysqli_fetch_assoc($q)) {
                                            $is_active = isset($r['is_active']) ? $r['is_active'] : 1;
                                        ?>
                                        <tr class="<?= ($edit_mode && $data_edit['id'] == $r['id']) ? 'table-warning' : '' ?>">
                                            <td><?= $r['nama_lengkap'] ?></td>
                                            <td><?= $r['username'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($r['role']=='admin')?'danger':(($r['role']=='siswa')?'secondary':'success') ?>">
                                                    <?= strtoupper($r['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <!-- Tombol Toggle Status -->
                                                <?php if($is_active == 1): ?>
                                                    <a href="manajemen_user.php?id=<?= $r['id'] ?>&status=0" class="badge bg-success text-decoration-none" title="Klik untuk Nonaktifkan">AKTIF</a>
                                                <?php else: ?>
                                                    <a href="manajemen_user.php?id=<?= $r['id'] ?>&status=1" class="badge bg-secondary text-decoration-none" title="Klik untuk Aktifkan">NONAKTIF</a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="manajemen_user.php?edit=<?= $r['id'] ?>" class="btn btn-outline-primary" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                                    <?php if($r['id'] != $_SESSION['user_id']): ?>
                                                        <a href="manajemen_user.php?hapus=<?= $r['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus user ini?')" title="Hapus"><i class="fa-solid fa-trash"></i></a>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-secondary" disabled><i class="fa-solid fa-ban"></i></button>
                                                    <?php endif; ?>
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
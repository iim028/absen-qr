<?php
session_start();
include 'config/db.php';

if (isset($_SESSION['login'])) {
    header("Location: dashboard.php");
    exit;
}

$pesan = "";

if (isset($_POST['register'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']); // Email/Username
    $password = md5($_POST['password']);
    
    // Sesuai PDF: Default Role = Petugas (Role 2 dalam konteks PDF, di sini kita pakai string 'petugas')
    $role = 'petugas'; 
    $is_active = 1; // Default aktif (bisa diubah admin nanti)

    // Cek duplikat
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "<div class='alert alert-danger'>Username/Email sudah terdaftar!</div>";
    } else {
        $sql = "INSERT INTO users (username, password, role, nama_lengkap, is_active) 
                VALUES ('$username', '$password', '$role', '$nama', '$is_active')";
        if (mysqli_query($conn, $sql)) {
            $pesan = "<div class='alert alert-success'>Registrasi Berhasil! Silakan <a href='index.php'>Login</a>.</div>";
        } else {
            $pesan = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Registrasi Staf - Absensi QR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css"> <!-- Pakai style yang sudah ada -->
</head>
<body class="login-body">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 450px; border-radius: 15px;">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary"><i class="fa-solid fa-user-plus me-2"></i>Registrasi Staf</h3>
            <p class="text-muted">Buat akun untuk Guru atau Petugas</p>
        </div>

        <?= $pesan ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username / Email</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" name="register" class="btn btn-primary btn-lg">Daftar Sekarang</button>
            </div>
        </form>
        
        <div class="text-center mt-3">
            <small>Sudah punya akun? <a href="index.php">Login disini</a></small>
        </div>
    </div>
</div>

</body>
</html>

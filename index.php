<?php
session_start();
include 'config/db.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['login'])) {
    header("Location: dashboard.php");
    exit;
}

// Logic Login
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); 

    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        
        // Cek status aktif (Sesuai PDF Hal 15 - is_active)
        // Jika kolom is_active ada di database, kita cek. Jika belum ada, anggap aktif.
        $is_active = isset($data['is_active']) ? $data['is_active'] : 1;

        if ($is_active == 0) {
            $error = "Akun Anda dinonaktifkan. Hubungi Admin.";
        } else {
            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['role'] = $data['role'];
            $_SESSION['nama'] = $data['nama_lengkap'];

            if($data['role'] == 'siswa'){
                $q_siswa = mysqli_query($conn, "SELECT id FROM siswa WHERE nisn='$username'");
                $d_siswa = mysqli_fetch_assoc($q_siswa);
                $_SESSION['siswa_id'] = $d_siswa['id'];
            }

            header("Location: dashboard.php");
            exit;
        }
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login - Sistem Absensi QR</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">

<div class="container-fluid p-0 overflow-hidden">
    <div class="row g-0 login-container">
        
        <!-- BAGIAN KIRI: GAMBAR -->
        <div class="col-lg-7 login-sidebar">
            <div class="sidebar-content">
                <h1 class="display-4 fw-bold mb-3">E-Absensi Tadika Mesra</h1>
                <p class="lead mb-4" style="opacity: 0.9;">Sistem absensi modern berbasis QR Code terintegrasi dengan WhatsApp Gateway untuk kemudahan monitoring kehadiran siswa.</p>
                <div class="d-flex gap-3">
                    <div class="d-flex align-items-center"><i class="fa-solid fa-check-circle fa-2x me-2 text-warning"></i><span>Realtime</span></div>
                    <div class="d-flex align-items-center"><i class="fa-solid fa-shield-alt fa-2x me-2 text-warning"></i><span>Aman</span></div>
                    <div class="d-flex align-items-center"><i class="fa-solid fa-bolt fa-2x me-2 text-warning"></i><span>Cepat</span></div>
                </div>
            </div>
        </div>

        <!-- BAGIAN KANAN: FORM LOGIN -->
        <div class="col-lg-5 login-section">
            <div class="login-card">
                <div class="login-header mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary text-white rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <h5 class="m-0 fw-bold text-primary">Absensi App</h5>
                    </div>
                    <h3>Selamat Datang! 👋</h3>
                    <p>Silakan login atau gunakan menu scan di bawah.</p>
                </div>

                <?php if (isset($error)) echo "<div class='alert alert-danger alert-custom d-flex align-items-center mb-4'><i class='fa-solid fa-circle-exclamation me-2'></i> $error</div>"; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 0.75rem;">Username / NISN</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="fa-regular fa-user"></i></span>
                            <input type="text" name="username" class="form-control-custom" placeholder="Ketik username anda" required autocomplete="off">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 0.75rem;">Password</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" class="form-control-custom" placeholder="Ketik password anda" required>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary w-100 btn-primary-custom mt-2">
                        Masuk ke Dashboard <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </form>

                <!-- TOMBOL SCAN CEPAT (MODE KIOSK) -->
                <div class="mt-3">
                    <a href="scan_public.php" class="btn btn-outline-dark w-100" style="height: 50px; border-radius: 12px; font-weight: 600; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-camera me-2"></i> Buka Kamera Absensi
                    </a>
                </div>
                
                <div class="text-center mt-4 text-muted small">
                    <p class="mb-0">Belum punya akun?</p>
                    <a href="register.php" class="text-decoration-none fw-bold">Daftar sebagai Staf</a>
                    <p class="mt-3 text-primary fw-bold">&copy; <?= date('Y') ?> Sekolah Digital</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
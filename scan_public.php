<?php 
session_start();
include 'config/db.php';
// Tidak ada cek login, karena ini untuk umum/kiosk mode
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Absensi - Mode Kiosk</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/style.css">
    
    <style>
        body {
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .scan-container {
            width: 100%;
            max-width: 500px;
            padding: 15px;
        }
        .camera-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .camera-header {
            background: linear-gradient(135deg, #0d6efd, #0043a8);
            color: white;
            padding: 20px;
            text-align: center;
        }
        #reader {
            width: 100%;
            border-radius: 0 0 20px 20px;
        }
        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 100;
            background: rgba(255,255,255,0.8);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
            color: #333;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back:hover {
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<!-- Tombol Kembali ke Login -->
<a href="index.php" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Kembali
</a>

<div class="scan-container">
    <div class="camera-card bg-white">
        <div class="camera-header">
            <h4 class="mb-1"><i class="fa-solid fa-qrcode me-2"></i>Absensi Digital</h4>
            <p class="mb-0 small opacity-75">Arahkan QR Code Kartu Pelajar ke Kamera</p>
        </div>
        <div class="card-body p-0">
            <!-- Area Kamera -->
            <div id="reader"></div>
        </div>
        <div class="p-3 text-center bg-light border-top">
            <small class="text-muted" id="jam-realtime">--:--:--</small>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Jam Realtime
    setInterval(() => {
        const now = new Date();
        document.getElementById('jam-realtime').innerText = now.toLocaleTimeString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }, 1000);

    // Logic QR Code
    function onScanSuccess(decodedText, decodedResult) {
        // Hentikan scan sejenak agar tidak spamming
        html5QrcodeScanner.clear();

        // Tampilkan loading
        Swal.fire({
            title: 'Memproses...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() }
        });

        // Kirim data ke Server (AJAX)
        fetch('api/process_scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'qr_code=' + decodedText
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => { 
                    location.reload(); // Reload halaman untuk scan orang berikutnya
                });
            } else {
                Swal.fire({
                    title: 'Gagal',
                    text: data.message,
                    icon: 'error',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => { 
                    location.reload(); 
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Terjadi kesalahan koneksi', 'error').then(() => { location.reload(); });
        });
    }

    var html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>
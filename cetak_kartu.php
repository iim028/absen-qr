<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$query = mysqli_query($conn, "SELECT * FROM siswa WHERE id='$id'");
$d = mysqli_fetch_assoc($query);

if (!$d) { echo "Data siswa tidak ditemukan."; exit; }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cetak Kartu - <?= $d['nama'] ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #eee;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center; /* Tengah Horizontal */
            align-items: center; /* Tengah Vertikal */
            min-height: 100vh;
        }

        .id-card {
            width: 85.6mm; /* Ukuran Standar ISO ID Card */
            height: 53.98mm;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
            border: 1px solid #000; /* Border tipis untuk panduan potong */
        }

        /* Desain Background Modern */
        .header-bg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, #0d6efd 30%, #f8f9fa 30%);
            z-index: 0;
        }

        .card-content {
            position: relative;
            z-index: 1;
            padding: 15px;
            height: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
        }

        .qr-area {
            width: 80px;
            height: 80px;
            background: white;
            padding: 5px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .qr-area img {
            width: 100%;
            height: 100%;
        }

        .info-area {
            margin-left: 15px;
            flex: 1;
        }

        .school-name {
            font-size: 10px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px; /* Jarak agar turun dari area biru */
        }

        .student-name {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .student-detail {
            font-size: 10px;
            color: #555;
            line-height: 1.4;
        }

        .footer-tag {
            position: absolute;
            bottom: 10px;
            right: 15px;
            font-size: 7px;
            color: #888;
            font-weight: 600;
        }

        .no-print {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            z-index: 1000;
            font-family: sans-serif;
            border: none;
        }

        @media print {
            body { background: none; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
            .id-card { 
                box-shadow: none; 
                border: 1px dashed #ccc; /* Garis potong saat print */
                margin: 0; /* Reset margin */
                /* Posisi saat print di kertas A4 biasanya di pojok kiri atas */
                position: absolute;
                top: 20px;
                left: 20px;
            }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print">🖨️ Cetak Kartu</button>

    <div class="id-card">
        <div class="header-bg"></div>
        
        <div class="card-content">
            <!-- Sisi Kiri: Logo/Text di area Biru -->
            <!-- Kita kosongkan agar layout bersih, text ada di kanan -->
            
            <div class="qr-area">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $d['qr_code'] ?>" alt="QR">
            </div>

            <div class="info-area">
                <div class="school-name">SMK DIGITAL ABSENSI</div>
                <table>
                    <tr>
                        <td width="50"><strong>NISN</strong></td>
                        <td width="5">:</td>
                        <td><?= $d['nisn'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nama</strong></td>
                        <td>:</td>
                        <td><?= strtoupper($d['nama']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Gender</strong></td>
                        <td>:</td>
                        <td><?= ($d['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan' ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kelas</strong></td>
                        <td>:</td>
                        <td><?= $d['kelas'] ?> - <?= $d['jurusan'] ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer-tag">KARTU PELAJAR DIGITAL</div>
    </div>

</body>
</html>
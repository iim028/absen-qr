<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db-absensi";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

date_default_timezone_set('Asia/Jakarta');
?>
<?php
session_start();

// Menghapus semua variabel sesi
$_SESSION = [];

// Menghapus sesi dari penyimpanan
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: index.php");
exit;
?>
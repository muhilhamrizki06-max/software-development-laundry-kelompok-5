<?php
ob_start();
session_start();
date_default_timezone_set('Asia/Jakarta');

$host = 'localhost';
$db   = 'laundria2';
$user = 'root';
$pass = 'root';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    exit('Koneksi database gagal: ' . $e->getMessage());
}

function money($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function cek_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}
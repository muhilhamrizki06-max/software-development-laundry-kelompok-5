<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$user = 'root';
$pass = 'root';
$db   = 'laundria2';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function cek_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function money($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
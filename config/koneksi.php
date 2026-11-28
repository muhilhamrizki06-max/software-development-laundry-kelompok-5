<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$user = 'root';
$pass = 'root';
$db   = 'laundria2';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (Throwable $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    die("Koneksi database gagal. Silakan hubungi administrator.");
}

function cek_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function cek_owner_api() {
    cek_login();
    if (($_SESSION['user']['role'] ?? '') !== 'owner') {
        http_response_code(403);
        exit('Akses ditolak: halaman ini khusus untuk Owner.');
    }
}

function require_owner_view($role) {
    if ($role !== 'owner') {
        echo '<div class="page"><div class="alert alert-danger">Halaman ini khusus untuk Owner.</div></div>';
        include __DIR__ . '/../public/includes/footer.php';
        exit;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf() {
    $token = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function require_csrf_page() {
    if (!verify_csrf()) {
        http_response_code(403);
        echo '<div class="page"><div class="alert alert-danger">Permintaan tidak valid atau sesi formulir kedaluwarsa. Silakan muat ulang halaman dan coba lagi.</div></div>';
        include __DIR__ . '/../public/includes/footer.php';
        exit;
    }
}

function csv_safe($value) {
    $str = (string)$value;
    if ($str !== '' && preg_match('/^[=+\-@\t\r]/', $str)) {
        return "'" . $str;
    }
    return $str;
}

function money($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

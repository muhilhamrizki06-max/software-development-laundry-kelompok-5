<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
$user = require_role('admin');
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Admin - Laundry AI</title><link rel="stylesheet" href="/public/css/style.css"></head>
<body><main class="container"><h1>Admin Sistem</h1><p>Halo, <?= e($user['nama']) ?>.</p>
<p>Modul admin digunakan untuk menerbitkan kode lisensi dan menetapkan paket gratis/premium serta masa berlaku.</p>
<ul><li><a href="licenses.php">Kelola lisensi</a></li><li><a href="/auth/logout.php">Keluar</a></li></ul></main></body></html>

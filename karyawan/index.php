<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
$user = require_role('karyawan');
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Operasional Karyawan - Laundry AI</title><link rel="stylesheet" href="/public/css/style.css"></head>
<body><main class="container"><h1>Operasional Karyawan</h1><p>Halo, <?= e($user['nama']) ?>.</p>
<ul><li><a href="orders.php">Daftar order</a></li><li><a href="order_create.php">Input order baru</a></li><li><a href="customers.php">Data pelanggan</a></li></ul>
<a href="/auth/logout.php">Keluar</a></main></body></html>

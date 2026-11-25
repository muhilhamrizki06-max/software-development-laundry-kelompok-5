<?php
cek_login();
require_once __DIR__ . '/ui.php';

$user = $_SESSION['user'];
$role = $user['role'];
$current = basename($_SERVER['PHP_SELF']);

$menu = [
    ['href' => '../karyawan/index.php',   'file' => 'index.php',     'label' => 'Dashboard',          'icon' => 'dashboard', 'owner' => false],
    ['href' => '../karyawan/orders.php',  'file' => 'orders.php',    'label' => 'Input Order',        'icon' => 'orders',    'owner' => false],
    ['href' => '../karyawan/nota.php',    'file' => 'nota.php',      'label' => 'Daftar Nota',        'icon' => 'reports',   'owner' => false],
    ['href' => '../karyawan/cash.php',    'file' => 'cash.php',      'label' => 'Shift & Kas Harian', 'icon' => 'money',     'owner' => false],
    ['href' => '../karyawan/stock.php',   'file' => 'stock.php',     'label' => 'Manajemen Stok',     'icon' => 'box',       'owner' => false],
    ['href' => '../owner/expenses.php',   'file' => 'expenses.php',  'label' => 'Pengeluaran',        'icon' => 'money',     'owner' => true],
    ['href' => '../owner/reports.php',    'file' => 'reports.php',   'label' => 'Laporan',            'icon' => 'reports',   'owner' => true],
    ['href' => '../owner/employees.php',  'file' => 'employees.php', 'label' => 'Karyawan',           'icon' => 'users',     'owner' => true],
    ['href' => '../ai/ai.php',            'file' => 'ai.php',        'label' => 'Analisis & AI',      'icon' => 'sparkle',   'owner' => true],
    ['href' => '../owner/license.php',    'file' => 'license.php',   'label' => 'Lisensi',            'icon' => 'check',     'owner' => true],
    ['href' => '../owner/settings.php',   'file' => 'settings.php',  'label' => 'Pengaturan Toko',    'icon' => 'laundry',   'owner' => true],
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laundria — Manajemen Laundry</title>
  <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <aside class="sidebar no-print">
    <div style="padding:24px 20px 18px;"><?= logo() ?></div>
    <nav class="nav-list">
      <?php foreach ($menu as $m): if ($m['owner'] && $role !== 'owner') continue; ?>
        <a href="<?= $m['href'] ?>" class="nav-item <?= $current === $m['file'] ? 'active' : '' ?>">
          <?= icon($m['icon'], 16) ?> <span><?= $m['label'] ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <div style="font-size:12px; font-weight:800; color:#1c4738;"><?= htmlspecialchars($user['name']) ?></div>
      <div style="font-size:10px; color:#84988c; text-transform:uppercase; letter-spacing:.06em; margin-top:2px;"><?= $role === 'owner' ? 'Owner' : 'Karyawan' ?></div>
      <a href="../auth/logout.php" class="btn-secondary btn-block" style="margin-top:12px; height:36px;">
        <?= icon('logout', 14) ?> Keluar
      </a>
    </div>
  </aside>
  <main class="main-content">
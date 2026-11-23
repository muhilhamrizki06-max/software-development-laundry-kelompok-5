<?php
require_once 'config/koneksi.php';
include 'includes/header.php';

$today = date('Y-m-d');
$revToday = $conn->query("SELECT COALESCE(SUM(amount),0) total, COUNT(*) cnt FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc();
$costToday = $conn->query("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE date = '$today'")->fetch_assoc()['total'];
$activeCnt = $conn->query("SELECT COUNT(*) cnt FROM orders WHERE status != 'Selesai'")->fetch_assoc()['cnt'];
$procCnt   = $conn->query("SELECT COUNT(*) cnt FROM orders WHERE status = 'Diproses'")->fetch_assoc()['cnt'];
$readyCnt  = $conn->query("SELECT COUNT(*) cnt FROM orders WHERE status = 'Siap Diambil'")->fetch_assoc()['cnt'];

$recentOrders = $conn->query("
    SELECT o.*, c.name AS customer_name, GROUP_CONCAT(s.name SEPARATOR ', ') AS layanan_list
    FROM orders o JOIN customers c ON o.customer_id = c.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN services s ON oi.service_id = s.id
    GROUP BY o.id ORDER BY o.created_at DESC LIMIT 8
");
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Ruang kerja / Dashboard</p>
      <h2 class="page-title">Selamat datang, <?= explode(' ', $user['name'])[0] ?>.</h2>
      <p class="page-desc"><?= $role === 'owner' ? 'Bisnis yang rapi dimulai dari ringkasan yang jelas.' : 'Mari pastikan setiap cucian selesai tepat waktu.' ?></p>
    </div>
    <a href="orders.php" class="btn-primary"><?= icon('plus', 16) ?> Order baru</a>
  </div>

  <div class="grid-stats">
    <?php if ($role === 'owner'): ?>
      <div class="panel stat-card">
        <div class="stat-top"><span class="stat-label">Omzet hari ini</span><span class="stat-icon"><?= icon('money', 17) ?></span></div>
        <div class="stat-val"><?= money($revToday['total']) ?></div>
        <p class="stat-detail"><?= $revToday['cnt'] ?> order masuk hari ini</p>
      </div>
    <?php endif; ?>

    <div class="panel stat-card">
      <div class="stat-top"><span class="stat-label">Order aktif</span><span class="stat-icon warm"><?= icon('orders', 17) ?></span></div>
      <div class="stat-val"><?= $activeCnt ?></div>
      <p class="stat-detail">Menunggu, diproses, dan siap diambil</p>
    </div>

    <div class="panel stat-card">
      <div class="stat-top"><span class="stat-label"><?= $role === 'owner' ? 'Estimasi laba hari ini' : 'Sedang diproses' ?></span><span class="stat-icon"><?= icon($role === 'owner' ? 'trend' : 'laundry', 17) ?></span></div>
      <div class="stat-val"><?= $role === 'owner' ? money($revToday['total'] - $costToday) : $procCnt ?></div>
      <p class="stat-detail"><?= $role === 'owner' ? 'Omzet dikurangi pengeluaran' : 'Tahap pencucian & setrika' ?></p>
    </div>

    <div class="panel stat-card">
      <div class="stat-top"><span class="stat-label">Siap diambil</span><span class="stat-icon warm"><?= icon('check', 17) ?></span></div>
      <div class="stat-val"><?= $readyCnt ?></div>
      <p class="stat-detail">Siap diserahkan ke pelanggan</p>
    </div>
  </div>

  <section class="panel">
    <div class="panel-pad" style="display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h3 class="section-title">Order terbaru</h3>
        <p class="section-sub">Klik ID order untuk membuka nota & ubah status pengerjaan</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>ID Order</th><th>Pelanggan</th><th>Layanan</th><th>Total</th><th>Status</th><th>Estimasi</th><th>Nota</th></tr>
        </thead>
        <tbody>
          <?php while ($o = $recentOrders->fetch_assoc()): ?>
            <tr>
              <td><a href="nota.php?id=<?= $o['id'] ?>" style="font-weight:800; color:#28795e;"><?= $o['id'] ?></a></td>
              <td style="font-weight:700;"><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= htmlspecialchars($o['layanan_list']) ?></td>
              <td style="font-weight:700;"><?= money($o['amount']) ?></td>
              <td><?= badge_status($o['status']) ?></td>
              <td><?= date('d M Y', strtotime($o['due_date'])) ?></td>
              <td><a href="nota.php?id=<?= $o['id'] ?>" class="icon-btn"><?= icon('chevron', 14) ?></a></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php include 'includes/footer.php'; ?>
<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

require_owner_view($role);

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$period = $_GET['period'] ?? 'all';

$where = ["1=1"];
$params = [];
$types = "";

if ($q !== '') {
    $where[] = "(o.id LIKE ? OR c.name LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like;
    $types .= "ss";
}
if ($status !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($period !== 'all') {
    $where[] = "DATE_FORMAT(o.created_at, '%Y-%m') = ?";
    $params[] = $period;
    $types .= "s";
}

$sql = "SELECT o.*, c.name AS customer_name, GROUP_CONCAT(s.name SEPARATOR ', ') AS layanan_list
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN services s ON oi.service_id = s.id
        WHERE " . implode(" AND ", $where) . "
        GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalRev = array_sum(array_column($orders, 'amount'));
$cntOrders = count($orders);
$avgOrder = $cntOrders > 0 ? $totalRev / $cntOrders : 0;

if ($period === 'all') {
    $totalCosts = (int)$conn->query("SELECT COALESCE(SUM(amount),0) t FROM expenses")->fetch_assoc()['t'];
} else {
    $stmtExp = $conn->prepare("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = ?");
    $stmtExp->bind_param("s", $period);
    $stmtExp->execute();
    $totalCosts = (int)$stmtExp->get_result()->fetch_assoc()['t'];
}

$months = $conn->query("SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') m FROM orders ORDER BY m DESC");
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Keuangan / Laporan</p>
      <h2 class="page-title">Angka yang bercerita.</h2>
      <p class="page-desc">Pencarian, filter, dan ekspor menggunakan data transaksi dari database.</p>
    </div>
    <a href="export_csv.php?period=<?= urlencode($period) ?>&status=<?= urlencode($status) ?>&q=<?= urlencode($q) ?>" class="btn-secondary">
      <?= icon('download', 16) ?> Ekspor CSV
    </a>
  </div>

  <div class="grid-stats" style="grid-template-columns: repeat(3, 1fr);">
    <div class="panel stat-card">
      <div class="stat-top"><span class="stat-label">Nilai order terfilter</span><span class="stat-icon"><?= icon('money', 17) ?></span></div>
      <div class="stat-val"><?= money($totalRev) ?></div>
      <p class="stat-detail"><?= $cntOrders ?> transaksi sesuai filter</p>
    </div>
    <div class="panel stat-card">
      <div class="stat-top"><span class="stat-label">Pengeluaran periode</span><span class="stat-icon warm"><?= icon('reports', 17) ?></span></div>
      <div class="stat-val"><?= money($totalCosts) ?></div>
      <p class="stat-detail">Total biaya tercatat pada periode ini</p>
    </div>
    <div class="panel stat-card">
      <div class="stat-top"><span class="stat-label">Rata-rata order</span><span class="stat-icon"><?= icon('chart', 17) ?></span></div>
      <div class="stat-val"><?= money($avgOrder) ?></div>
      <p class="stat-detail">Nilai order dibagi jumlah transaksi</p>
    </div>
  </div>

  <section class="panel">
    <form method="GET" class="panel-pad" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:12px;">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari pelanggan atau ID order..." class="form-input" style="max-width:280px;">
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <select name="status" class="form-input" style="width:auto;" onchange="this.form.submit()">
          <option value="all">Semua status</option>
          <?php foreach (['Menunggu', 'Diproses', 'Siap Diambil', 'Selesai'] as $st): ?>
            <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
        <select name="period" class="form-input" style="width:auto;" onchange="this.form.submit()">
          <option value="all">Semua periode</option>
          <?php while ($m = $months->fetch_assoc()): ?>
            <option value="<?= $m['m'] ?>" <?= $period === $m['m'] ? 'selected' : '' ?>><?= date('F Y', strtotime($m['m'] . '-01')) ?></option>
          <?php endwhile; ?>
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
      </div>
    </form>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>ID Order</th><th>Pelanggan</th><th>Layanan</th><th>Total</th><th>Status</th><th>Estimasi</th><th>Nota</th></tr>
        </thead>
        <tbody>
          <?php if (!$orders): ?>
            <tr><td colspan="7" style="text-align:center; padding:32px; color:#81958b;">Tidak ada transaksi yang sesuai filter.</td></tr>
          <?php else: foreach ($orders as $o): ?>
            <tr>
              <td><a href="../karyawan/nota.php?id=<?= urlencode($o['id']) ?>" style="font-weight:800; color:#28795e;"><?= htmlspecialchars($o['id']) ?></a></td>
              <td style="font-weight:700;"><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= htmlspecialchars($o['layanan_list']) ?></td>
              <td style="font-weight:700;"><?= money($o['amount']) ?></td>
              <td><?= badge_status($o['status']) ?></td>
              <td><?= date('d M Y', strtotime($o['due_date'])) ?></td>
              <td><a href="../karyawan/nota.php?id=<?= urlencode($o['id']) ?>" class="icon-btn"><?= icon('chevron', 14) ?></a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php include '../public/includes/footer.php'; ?>
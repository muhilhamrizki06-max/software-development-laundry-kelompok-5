<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

$today = date('Y-m-d');
$revToday  = $conn->query("SELECT COALESCE(SUM(amount),0) total, COUNT(*) cnt FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc();
$costToday = $conn->query("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE date = '$today'")->fetch_assoc()['total'];
$activeCnt = $conn->query("SELECT COUNT(*) cnt FROM orders WHERE status != 'Selesai'")->fetch_assoc()['cnt'];
$procCnt   = $conn->query("SELECT COUNT(*) cnt FROM orders WHERE status = 'Diproses'")->fetch_assoc()['cnt'];
$readyCnt  = $conn->query("SELECT COUNT(*) cnt FROM orders WHERE status = 'Siap Diambil'")->fetch_assoc()['cnt'];

$chartLabels = [];
$chartValues = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d M', strtotime($tgl));
    $val = (int)$conn->query("SELECT COALESCE(SUM(amount),0) t FROM orders WHERE DATE(created_at) = '$tgl'")->fetch_assoc()['t'];
    $chartValues[] = $val;
}
$maxChart = max(array_merge($chartValues, [10000]));

$omzet7HariLalu = array_sum($chartValues);
$omzet7HariPrev = (int)$conn->query("SELECT COALESCE(SUM(amount),0) t FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetch_assoc()['t'];
$omzetTurun = ($omzet7HariPrev > 0 && $omzet7HariLalu < $omzet7HariPrev);
$persenTurun = $omzetTurun ? round((($omzet7HariPrev - $omzet7HariLalu) / $omzet7HariPrev) * 100, 1) : 0;

$lateCount = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status != 'Selesai' AND due_date <= '$today'")->fetch_assoc()['c'];
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Ruang kerja / Dashboard</p>
      <h2 class="page-title">Selamat datang, <?= explode(' ', $user['name'])[0] ?>.</h2>
      <p class="page-desc"><?= $role === 'owner' ? 'Bisnis yang rapi dimulai dari ringkasan yang jelas.' : 'Mari pastikan setiap cucian selesai tepat waktu.' ?></p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a href="nota.php" class="btn-secondary"><?= icon('reports', 16) ?> Daftar Nota</a>
      <a href="orders.php" class="btn-primary"><?= icon('plus', 16) ?> Order baru</a>
    </div>
  </div>

  <div class="grid-stats" style="<?= $role !== 'owner' ? 'grid-template-columns: repeat(3, 1fr);' : '' ?>">
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

  <div class="grid-2-col">
    <?php if ($role === 'owner'): ?>
      <section class="panel panel-pad">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
          <div>
            <h3 class="section-title">Grafik omzet 7 hari terakhir</h3>
            <p class="section-sub">Total periode ini: <b><?= money($omzet7HariLalu) ?></b></p>
          </div>
          <span class="stat-icon"><?= icon('chart', 17) ?></span>
        </div>

        <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap:10px; align-items:end; height:180px; padding:16px 8px 8px; background:#f8fbf9; border-radius:12px; border:1px solid #e6efe9;">
          <?php foreach ($chartValues as $idx => $val):
            $pct = $maxChart > 0 ? max(6, round(($val / $maxChart) * 100)) : 6;
            $isToday = ($idx === 6);
          ?>
            <div style="display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end; gap:6px;" title="<?= $chartLabels[$idx] ?>: <?= money($val) ?>">
              <span style="font-size:9.5px; font-weight:700; color:#5e786d;"><?= $val > 0 ? round($val / 1000) . 'k' : '0' ?></span>
              <div style="width:100%; max-width:34px; height:<?= $pct ?>%; border-radius:8px 8px 4px 4px; background:<?= $isToday ? '#1f654f' : '#8ec3ad' ?>; transition:height .3s ease;"></div>
              <span style="font-size:10px; font-weight:<?= $isToday ? '800' : '600' ?>; color:<?= $isToday ? '#1f654f' : '#7b9187' ?>;"><?= $chartLabels[$idx] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="panel panel-pad" style="<?= $role !== 'owner' ? 'grid-column: 1 / -1;' : '' ?>">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <div>
          <p class="eyebrow" style="color:#b58048;">Laundria Intelligence</p>
          <h3 class="section-title">Peringatan & Rekomendasi AI</h3>
        </div>
        <span class="stat-icon warm"><?= icon('sparkle', 17) ?></span>
      </div>

      <?php if ($omzetTurun): ?>
        <div class="alert alert-danger" style="margin-bottom:12px;">
          <b>Peringatan AI:</b> Omzet 7 hari terakhir turun <b><?= $persenTurun ?>%</b> dibanding total 7 hari sebelumnya.
        </div>
      <?php else: ?>
        <div class="alert alert-success" style="margin-bottom:12px;">
          <b>Kondisi Omzet Aman:</b> Performa omzet 7 hari terakhir stabil dan tidak mengalami penurunan.
        </div>
      <?php endif; ?>

      <?php if ($lateCount > 0): ?>
        <div class="alert alert-warning" style="margin-bottom:16px;">
          <b>Perhatian Operasional:</b> Terdapat <b><?= $lateCount ?> cucian</b> yang sudah masuk batas tanggal estimasi selesai.
        </div>
      <?php else: ?>
        <div class="alert alert-success" style="margin-bottom:16px;">
          <b>Jadwal Terkendali:</b> Tidak ada cucian yang melewati estimasi tanggal pengambilan.
        </div>
      <?php endif; ?>

      <?php if ($role === 'owner'): ?>
        <a href="../ai/ai.php" class="btn-secondary btn-block">
          <?= icon('sparkle', 15) ?> Buka Analisis Lengkap & Draf Broadcast
        </a>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php include '../public/includes/footer.php'; ?>
<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

require_owner_view($role);

$cfg = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
$isPremium = (($cfg['plan'] ?? 'basic') === 'premium');

$omzet7HariLalu = (int)$conn->query("SELECT COALESCE(SUM(amount),0) t FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetch_assoc()['t'];
$omzet7HariPrev = (int)$conn->query("SELECT COALESCE(SUM(amount),0) t FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetch_assoc()['t'];

$persenTurun = 0;
$omzetTurun = false;
if ($omzet7HariPrev > 0 && $omzet7HariLalu < $omzet7HariPrev) {
    $omzetTurun = true;
    $persenTurun = round((($omzet7HariPrev - $omzet7HariLalu) / $omzet7HariPrev) * 100, 1);
}

$today = date('Y-m-d');
$lateOrders = $conn->query("SELECT o.*, c.name AS cname FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.status != 'Selesai' AND o.due_date <= '$today' ORDER BY o.due_date ASC");

$avgHarian = (float)$conn->query("SELECT COALESCE(AVG(harian),0) a FROM (SELECT DATE(created_at) d, SUM(amount) harian FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY d) t")->fetch_assoc()['a'];
$prediksiBulanDepan = round($avgHarian * 30);

$topService = $conn->query("SELECT s.name, COUNT(oi.id) AS cnt FROM order_items oi JOIN services s ON oi.service_id = s.id GROUP BY s.id ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$bestName = $topService['name'] ?? 'Cuci Komplit Reguler';

$audience = $_GET['audience'] ?? 'consented';
if ($audience === 'ready') {
    $custRes = $conn->query("SELECT DISTINCT c.* FROM customers c JOIN orders o ON o.customer_id = c.id WHERE c.consent = 1 AND o.status = 'Siap Diambil'");
} else {
    $custRes = $conn->query("SELECT * FROM customers WHERE consent = 1");
}
$eligible = $custRes->fetch_all(MYSQLI_ASSOC);

$draft = $_POST['draft'] ?? '';
$sentMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_page();
    if (isset($_POST['generate']) && $isPremium) {
        if ($audience === 'ready') {
            $draft = "Halo Kak {nama}, cucian Anda di Laundria sudah bersih dan SIAP DIAMBIL. Silakan kunjungi outlet dengan membawa nota. Terima kasih!";
        } elseif ($omzetTurun) {
            $draft = "Halo Kak {nama}, spesial minggu ini di Laundria! Dapatkan diskon 10% untuk layanan $bestName agar cucian menumpuk langsung beres. Balas pesan ini untuk booking antar-jemput!";
        } else {
            $draft = "Halo Kak {nama}, terima kasih telah mempercayakan cucian di Laundria. Coba layanan $bestName untuk hasil lebih bersih, wangi, dan rapi!";
        }

        $stmtIns = $conn->prepare("INSERT INTO ai_insight (tipe, judul, isi) VALUES ('broadcast', ?, ?)");
        $judulIns = "Draf Broadcast (" . ($audience === 'ready' ? 'Siap Diambil' : 'Promo Pelanggan') . ")";
        $stmtIns->bind_param("ss", $judulIns, $draft);
        $stmtIns->execute();
    } elseif (isset($_POST['send_sim']) && $isPremium && !empty($cfg['messaging']) && trim($draft) !== '') {
        $sentMsg = "Broadcast berhasil dikirim (simulasi) ke " . count($eligible) . " pelanggan yang menyetujui pesan.";
    }
}

$riwayatInsight = $conn->query("SELECT * FROM ai_insight ORDER BY id DESC LIMIT 5");
?>
<div class="page">
  <div style="background:radial-gradient(circle at 85% 20%, rgba(237,177,113,.22), transparent 28%), linear-gradient(120deg, #173e34, #236a53); border-radius:24px; padding:32px; color:#fff; margin-bottom:20px;">
    <span style="display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:99px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); font-size:10px; font-weight:700; letter-spacing:.1em;">
      <?= icon('sparkle', 13) ?> LAUNDRIA INTELLIGENCE
    </span>
    <h2 style="font-size:28px; font-weight:800; line-height:1.15; margin-top:16px;">
      Bukan sekadar data.<br>
      <span style="font-family:'DM Serif Display', serif; font-style:italic; color:#edbc89; font-weight:400;">Langkah berikutnya</span> untuk bisnis Anda.
    </h2>
    <p style="font-size:12px; color:rgba(255,255,255,.65); margin-top:12px; max-width:580px;">
      Deteksi keterlambatan & penurunan omzet tersedia untuk semua paket. Prediksi omzet, rekomendasi keputusan, dan draf broadcast otomatis terbuka pada paket Premium.
    </p>
  </div>

  <section class="panel panel-pad" style="margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <div>
        <h3 class="section-title">Peringatan AI & Operasional</h3>
        <p class="section-sub">Deteksi otomatis tren omzet 7 hari terakhir dan order mendekati batas estimasi</p>
      </div>
      <span class="badge badge-green">Aktif</span>
    </div>

    <?php if ($omzetTurun): ?>
      <div class="alert alert-danger" style="margin-bottom:10px;">
        <b>Peringatan Penurunan Omzet:</b> Omzet 7 hari terakhir (<?= money($omzet7HariLalu) ?>) turun <b><?= $persenTurun ?>%</b> dibanding 7 hari sebelumnya (<?= money($omzet7HariPrev) ?>). Segera jalankan promo broadcast ke pelanggan!
      </div>
    <?php else: ?>
      <div class="alert alert-success" style="margin-bottom:10px;">
        <b>Tren Omzet Stabil:</b> Omzet 7 hari terakhir tercatat <b><?= money($omzet7HariLalu) ?></b> (tidak mengalami penurunan dibanding minggu lalu).
      </div>
    <?php endif; ?>

    <?php if ($lateOrders->num_rows === 0): ?>
      <div class="alert alert-success" style="margin:0;">Semua order aktif sesuai jadwal estimasi selesai.</div>
    <?php else: while ($lo = $lateOrders->fetch_assoc()): ?>
      <div class="alert alert-warning" style="margin-bottom:8px;">
        <b><?= htmlspecialchars($lo['id']) ?> (<?= htmlspecialchars($lo['cname']) ?>)</b> — Status: <?= htmlspecialchars($lo['status']) ?>, estimasi selesai <?= date('d M Y', strtotime($lo['due_date'])) ?>.
      </div>
    <?php endwhile; endif; ?>
  </section>

  <?php if (!$isPremium): ?>
    <div class="panel panel-pad" style="background:#fffaf4; border-color:#ecddc9;">
      <h3 style="font-size:16px; font-weight:800; color:#73512e;">Fitur Khusus Lisensi Premium *</h3>
      <p style="font-size:12px; color:#9a856e; margin:8px 0 16px;">Aktifkan paket Premium di halaman Lisensi untuk membuka Prediksi Omzet, Saran Bisnis, dan Draf Broadcast otomatis.</p>
      <a href="../owner/license.php" class="btn-secondary">Buka Pengaturan Lisensi <?= icon('arrow', 14) ?></a>
    </div>
  <?php else: ?>
    <div class="grid-2-left" style="margin-bottom:20px;">
      <section class="panel panel-pad">
        <p class="eyebrow" style="color:#b58048;">Prediksi & Saran AI *</p>
        <h3 style="font-size:18px; font-weight:800; margin-bottom:10px;">Estimasi omzet 30 hari ke depan: <span style="color:#1f7559;"><?= money($prediksiBulanDepan) ?></span></h3>
        <p style="font-size:12px; color:#83988b; line-height:1.6; margin-bottom:12px;">
          Dihitung dari rata-rata transaksi harian aktual (<b><?= money($avgHarian) ?>/hari</b>). Layanan terlaris saat ini adalah <b style="color:#1f7559;"><?= htmlspecialchars($bestName) ?></b>.
        </p>
        <div class="alert alert-warning" style="font-size:11px; margin-bottom:16px;">
          <b>Rekomendasi Keputusan:</b> Pastikan stok deterjen dan parfum mencukupi untuk proyeksi omzet bulan depan, serta kirim pesan pengingat ke pelanggan lama.
        </div>
        <form method="POST">
          <?= csrf_field() ?>
          <button type="submit" name="generate" class="btn-secondary btn-block"><?= icon('sparkle', 15) ?> Generate Draf Broadcast AI</button>
        </form>
      </section>

      <section class="panel panel-pad">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
          <h3 class="section-title">Draf Broadcast Customer *</h3>
          <span class="badge <?= !empty($cfg['messaging']) ? 'badge-green' : 'badge-orange' ?>"><?= !empty($cfg['messaging']) ? 'Gateway Aktif' : 'Gateway Nonaktif' ?></span>
        </div>

        <form method="GET" class="form-group">
          <label class="form-label">Target Penerima</label>
          <select name="audience" class="form-input" onchange="this.form.submit()">
            <option value="consented" <?= $audience==='consented'?'selected':'' ?>>Pelanggan Setuju Promo (<?= count($eligible) ?> orang)</option>
            <option value="ready" <?= $audience==='ready'?'selected':'' ?>>Pelanggan Order Siap Diambil</option>
          </select>
        </form>

        <form method="POST">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Isi Pesan ({nama} otomatis diganti nama pelanggan)</label>
            <textarea name="draft" rows="4" class="form-input" placeholder="Klik 'Generate Draf Broadcast AI' atau tulis pesan Anda..."><?= htmlspecialchars($draft) ?></textarea>
          </div>
          <?php if (empty($cfg['messaging'])): ?>
            <div class="alert alert-warning">Aktifkan layanan pesan di halaman <a href="../owner/license.php" style="text-decoration:underline;">Lisensi</a> untuk mengirim broadcast.</div>
          <?php endif; ?>
          <?php if ($sentMsg): ?><div class="alert alert-success"><?= htmlspecialchars($sentMsg) ?></div><?php endif; ?>
          <div style="display:grid; grid-template-columns: 110px 1fr; gap:8px;">
            <button type="button" id="btn-salin-draft" class="btn-secondary">Salin</button>
            <button type="submit" name="send_sim" class="btn-primary" <?= (empty($cfg['messaging']) || empty($eligible) || trim($draft)==='') ? 'disabled' : '' ?>>
              <?= icon('arrow', 16) ?> Kirim Broadcast
            </button>
          </div>
        </form>
      </section>
    </div>

    <section class="panel">
      <div class="panel-pad"><h3 class="section-title">Riwayat Insight & Draf AI</h3></div>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Waktu</th><th>Judul</th><th>Isi Rekomendasi / Draf</th></tr></thead>
          <tbody>
            <?php if ($riwayatInsight->num_rows === 0): ?>
              <tr><td colspan="3" style="text-align:center; padding:24px; color:#81958b;">Belum ada riwayat insight AI yang tersimpan.</td></tr>
            <?php else: while ($ri = $riwayatInsight->fetch_assoc()): ?>
              <tr>
                <td style="white-space:nowrap;"><?= date('d M Y H:i', strtotime($ri['dibuat_pada'])) ?></td>
                <td style="font-weight:700;"><?= htmlspecialchars($ri['judul']) ?></td>
                <td><?= htmlspecialchars($ri['isi']) ?></td>
              </tr>
            <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</div>
<?php include '../public/includes/footer.php'; ?>
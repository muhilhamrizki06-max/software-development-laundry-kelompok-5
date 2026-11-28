<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

require_owner_view($role);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_page();
    if (isset($_POST['set_plan'])) {
        $p = $_POST['set_plan'] === 'premium' ? 'premium' : 'basic';
        $stmt = $conn->prepare("UPDATE settings SET plan = ? WHERE id = 1");
        $stmt->bind_param("s", $p);
        $stmt->execute();
    } elseif (isset($_POST['toggle_msg'])) {
        $conn->query("UPDATE settings SET messaging = IF(messaging=1, 0, 1) WHERE id = 1");
    }
    header("Location: license.php?saved=1");
    exit;
}

$cfg = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Pengaturan / Lisensi</p>
      <h2 class="page-title">Paket yang mengikuti bisnis Anda.</h2>
      <p class="page-desc">Kelola paket langganan untuk membuka fitur Analisis AI, Prediksi Omzet, dan Broadcast.</p>
    </div>
  </div>

  <?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Pengaturan lisensi berhasil diperbarui.</div>
  <?php endif; ?>

  <div class="grid-form-2" style="margin-bottom:20px;">
    <?php foreach (['basic' => 'Basic', 'premium' => 'Premium'] as $key => $label):
      $active = (($cfg['plan'] ?? 'basic') === $key);
    ?>
      <section class="panel panel-pad" style="<?= $active ? 'border-color:#43856a; background:#f5fbf7;' : '' ?>">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <span class="stat-icon"><?= icon($key === 'basic' ? 'laundry' : 'sparkle', 20) ?></span>
          <?php if ($active): ?><span class="badge badge-green">Paket aktif</span><?php endif; ?>
        </div>
        <h3 style="font-size:22px; font-weight:800; color:#224b39;"><?= $label ?></h3>
        <p style="font-size:12px; color:#8a9e8e; margin:6px 0 18px;">
          <?= $key === 'basic' ? 'Fondasi operasional harian yang Anda perlukan.' : 'Ruang lebih luas untuk analisis AI, prediksi omzet, dan broadcast.' ?>
        </p>
        <ul style="list-style:none; display:flex; flex-direction:column; gap:10px; margin-bottom:22px; font-size:12px; color:#4c6961;">
          <li>✓ Input order, paket kecepatan & cetak nota</li>
          <li>✓ Shift kasir, stok, pengeluaran & laporan</li>
          <li>✓ Peringatan operasional & deteksi penurunan omzet</li>
          <?php if ($key === 'premium'): ?>
            <li>✓ Prediksi omzet & saran keputusan AI *</li>
            <li>✓ Draf & broadcast pesan pelanggan otomatis *</li>
          <?php endif; ?>
        </ul>
        <form method="POST">
          <?= csrf_field() ?>
          <button type="submit" name="set_plan" value="<?= $key ?>" class="<?= $key === 'premium' ? 'btn-primary' : 'btn-secondary' ?> btn-block" <?= $active ? 'disabled' : '' ?>>
            <?= $active ? 'Sedang digunakan' : "Gunakan paket $label" ?>
          </button>
        </form>
      </section>
    <?php endforeach; ?>
  </div>

  <section class="panel panel-pad" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px;">
    <div>
      <h3 class="section-title">Layanan gateway pesan (WhatsApp / SMS)</h3>
      <p class="section-sub">Aktifkan untuk mengizinkan fitur kirim broadcast pada halaman Analisis & AI.</p>
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <button type="submit" name="toggle_msg" class="<?= !empty($cfg['messaging']) ? 'btn-primary' : 'btn-secondary' ?>">
        <?= !empty($cfg['messaging']) ? '● Layanan Pesan Aktif (Klik untuk Nonaktifkan)' : 'Aktifkan Layanan Pesan' ?>
      </button>
    </form>
  </section>
</div>
<?php include '../public/includes/footer.php'; ?>
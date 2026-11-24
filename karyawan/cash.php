<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

$shift = $conn->query("SELECT * FROM cash_shifts WHERE closed_at IS NULL ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'open' && !$shift) {
        $modal = (int)$_POST['opening_balance'];
        $stmt = $conn->prepare("INSERT INTO cash_shifts (opened_by, opened_at, opening_balance) VALUES (?, NOW(), ?)");
        $stmt->bind_param("si", $user['name'], $modal);
        $stmt->execute();
    } elseif ($act === 'move' && $shift) {
        $stmt = $conn->prepare("INSERT INTO cash_movements (shift_id, type, amount, category, note, actor) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisss", $shift['id'], $_POST['type'], $_POST['amount'], $_POST['category'], $_POST['note'], $user['name']);
        $stmt->execute();
    } elseif ($act === 'close' && $shift) {
        $expected = (int)$_POST['expected_cash'];
        $counted  = (int)$_POST['counted_cash'];
        $diff     = $counted - $expected;
        $stmt = $conn->prepare("UPDATE cash_shifts SET closed_at=NOW(), expected_cash=?, counted_cash=?, difference=?, closing_note=? WHERE id=?");
        $stmt->bind_param("iiisi", $expected, $counted, $diff, $_POST['closing_note'], $shift['id']);
        $stmt->execute();
    }
    header("Location: cash.php");
    exit;
}

$salesTunai = 0; $kasIn = 0; $kasOut = 0;
if ($shift) {
    $openedAt = $shift['opened_at'];
    $sid = $shift['id'];
    $salesTunai = (int)$conn->query("SELECT COALESCE(SUM(paid_amount),0) t FROM orders WHERE payment='Tunai' AND created_at >= '$openedAt'")->fetch_assoc()['t'];
    $kasIn      = (int)$conn->query("SELECT COALESCE(SUM(amount),0) t FROM cash_movements WHERE shift_id=$sid AND type='in'")->fetch_assoc()['t'];
    $kasOut     = (int)$conn->query("SELECT COALESCE(SUM(amount),0) t FROM cash_movements WHERE shift_id=$sid AND type='out'")->fetch_assoc()['t'];
}
$expectedCash = $shift ? ($shift['opening_balance'] + $salesTunai + $kasIn - $kasOut) : 0;
$history = $conn->query("SELECT * FROM cash_shifts ORDER BY id DESC LIMIT 10");
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Operasional / Kasir</p>
      <h2 class="page-title">Kas terkendali di setiap shift.</h2>
      <p class="page-desc">Catat modal awal, arus uang di laci, dan cocokkan kas fisik saat tutup shift.</p>
    </div>
    <span class="badge <?= $shift ? 'badge-green' : 'badge-orange' ?>">
      <span class="badge-dot"></span><?= $shift ? 'Shift aktif · sejak ' . date('H:i', strtotime($shift['opened_at'])) : 'Belum ada shift aktif' ?>
    </span>
  </div>

  <?php if (!$shift): ?>
    <div class="grid-2-col">
      <section class="panel" style="overflow:hidden;">
        <div style="background:linear-gradient(125deg,#173f34,#267157); color:#fff; padding:32px;">
          <p class="eyebrow" style="color:rgba(255,255,255,.6);">Opening kasir</p>
          <h3 style="font-size:22px; font-weight:800; margin-top:8px;">Hitung uang di laci sebelum transaksi pertama.</h3>
          <p style="font-size:12px; color:rgba(255,255,255,.7); margin-top:10px;">Setelah shift dibuka, pembayaran Tunai dan arus kas manual otomatis masuk perhitungan saldo sistem.</p>
        </div>
      </section>
      <form method="POST" class="panel panel-pad">
        <input type="hidden" name="act" value="open">
        <h3 class="section-title">Buka shift baru</h3>
        <p class="section-sub">Petugas: <?= htmlspecialchars($user['name']) ?></p>
        <div class="form-group" style="margin-top:16px;">
          <label class="form-label">Modal awal di laci (Rp)</label>
          <input type="number" name="opening_balance" value="300000" min="0" class="form-input" required>
        </div>
        <button type="submit" class="btn-primary btn-block"><?= icon('check', 16) ?> Konfirmasi & buka shift</button>
      </form>
    </div>
  <?php else: ?>
    <div class="grid-stats">
      <div class="panel stat-card"><div class="stat-top"><span class="stat-label">Modal awal</span></div><div class="stat-val"><?= money($shift['opening_balance']) ?></div></div>
      <div class="panel stat-card"><div class="stat-top"><span class="stat-label">Penjualan tunai</span></div><div class="stat-val"><?= money($salesTunai) ?></div></div>
      <div class="panel stat-card"><div class="stat-top"><span class="stat-label">Kas masuk / keluar</span></div><div class="stat-val" style="font-size:18px;"><?= money($kasIn) ?> / <?= money($kasOut) ?></div></div>
      <div class="panel stat-card"><div class="stat-top"><span class="stat-label">Kas sistem (Laci)</span></div><div class="stat-val" style="color:#1f7559;"><?= money($expectedCash) ?></div></div>
    </div>

    <div class="grid-2-col">
      <form method="POST" class="panel panel-pad">
        <input type="hidden" name="act" value="move">
        <h3 class="section-title">Catat kas manual</h3>
        <p class="section-sub">Untuk uang masuk/keluar di luar transaksi order.</p>
        <div class="grid-form-2" style="margin-top:16px;">
          <div class="form-group">
            <label class="form-label">Jenis</label>
            <select name="type" class="form-input"><option value="in">+ Kas masuk</option><option value="out">− Kas keluar</option></select>
          </div>
          <div class="form-group">
            <label class="form-label">Nominal (Rp)</label>
            <input type="number" name="amount" min="1" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Kategori</label>
            <input type="text" name="category" value="Belanja operasional" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Catatan</label>
            <input type="text" name="note" placeholder="Keterangan..." class="form-input" required>
          </div>
        </div>
        <button type="submit" class="btn-secondary"><?= icon('plus', 15) ?> Simpan arus kas</button>
      </form>

      <form method="POST" class="panel" style="overflow:hidden;">
        <input type="hidden" name="act" value="close">
        <input type="hidden" name="expected_cash" value="<?= $expectedCash ?>">
        <div style="background:#203f37; color:#fff; padding:20px;">
          <h3 style="font-size:16px; font-weight:800;">Tutup shift</h3>
          <p style="font-size:11px; opacity:0.65;">Kas sistem: <?= money($expectedCash) ?></p>
        </div>
        <div class="panel-pad">
          <div class="form-group">
            <label class="form-label">Uang fisik hasil hitung (Rp)</label>
            <input type="number" name="counted_cash" min="0" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Catatan closing</label>
            <textarea name="closing_note" rows="2" class="form-input" placeholder="Wajib jika ada selisih"></textarea>
          </div>
          <button type="submit" class="btn-primary btn-warm btn-block" onclick="return confirm('Tutup shift sekarang?')"><?= icon('check', 16) ?> Rekonsiliasi & tutup shift</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <section class="panel" style="margin-top:20px;">
    <div class="panel-pad"><h3 class="section-title">Riwayat shift kasir</h3></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Waktu Buka</th><th>Petugas</th><th>Modal</th><th>Kas Sistem</th><th>Kas Fisik</th><th>Selisih</th><th>Status</th></tr></thead>
        <tbody>
          <?php while ($h = $history->fetch_assoc()): ?>
            <tr>
              <td><?= date('d M Y H:i', strtotime($h['opened_at'])) ?></td>
              <td><?= htmlspecialchars($h['opened_by']) ?></td>
              <td><?= money($h['opening_balance']) ?></td>
              <td><?= $h['expected_cash'] !== null ? money($h['expected_cash']) : '—' ?></td>
              <td><?= $h['counted_cash'] !== null ? money($h['counted_cash']) : '—' ?></td>
              <td style="font-weight:700;"><?= $h['difference'] !== null ? money($h['difference']) : '—' ?></td>
              <td><?= $h['closed_at'] ? '<span class="badge badge-gray">Ditutup</span>' : '<span class="badge badge-green">Aktif</span>' ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php include '../public/includes/footer.php'; ?>
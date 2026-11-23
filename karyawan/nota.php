<?php
require_once 'config/koneksi.php';
include 'includes/header.php';

$id = $_GET['id'] ?? '';
$updated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("ss", $_POST['status'], $id);
    $stmt->execute();
    $updated = true;
}

$stmt = $conn->prepare("SELECT o.*, c.name AS cname, c.phone FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo '<div class="page"><div class="alert alert-danger">Order tidak ditemukan.</div><a href="index.php" class="btn-secondary">Kembali ke Dashboard</a></div>';
    include 'includes/footer.php';
    exit;
}

$stmtItems = $conn->prepare("SELECT oi.*, s.name, s.price, s.unit FROM order_items oi JOIN services s ON oi.service_id = s.id WHERE oi.order_id = ?");
$stmtItems->bind_param("s", $id);
$stmtItems->execute();
$items = $stmtItems->get_result();
?>
<div class="page">
  <div class="no-print" style="max-width:480px; margin:0 auto 16px; display:flex; justify-content:space-between; align-items:center;">
    <a href="index.php" class="btn-secondary">← Kembali</a>
    <?php if (isset($_GET['baru'])): ?>
      <span class="badge badge-green">Order baru tersimpan</span>
    <?php elseif ($updated): ?>
      <span class="badge badge-green">Status diperbarui</span>
    <?php endif; ?>
  </div>

  <div class="panel receipt-paper" style="max-width:480px; margin:0 auto; overflow:hidden;">
    <div style="padding:28px;">
      <div style="display:flex; justify-content:center; margin-bottom:18px;"><?= logo() ?></div>
      <p style="text-align:center; font-size:10px; font-weight:700; letter-spacing:0.15em; color:#8c9c92;">NOTA LAUNDRY</p>
      <h2 style="text-align:center; font-size:18px; font-weight:800; margin-top:4px;"><?= htmlspecialchars($order['id']) ?></h2>

      <div style="margin:20px 0; padding:14px 0; border-top:1px dashed #d5e1d8; border-bottom:1px dashed #d5e1d8; font-size:12px; display:flex; flex-direction:column; gap:8px;">
        <div style="display:flex; justify-content:space-between;"><span style="color:#8a9d90;">Pelanggan</span><b><?= htmlspecialchars($order['cname']) ?></b></div>
        <div style="display:flex; justify-content:space-between;"><span style="color:#8a9d90;">WhatsApp</span><span><?= htmlspecialchars($order['phone']) ?></span></div>
        <div style="display:flex; justify-content:space-between;"><span style="color:#8a9d90;">Tanggal masuk</span><span><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span></div>
        <div style="display:flex; justify-content:space-between;"><span style="color:#8a9d90;">Estimasi selesai</span><b><?= date('d M Y', strtotime($order['due_date'])) ?></b></div>
      </div>

      <div style="display:flex; flex-direction:column; gap:12px;">
        <?php while ($i = $items->fetch_assoc()): ?>
          <div style="display:flex; justify-content:space-between; font-size:12px;">
            <div>
              <b><?= htmlspecialchars($i['name']) ?></b>
              <p style="font-size:10px; color:#8a9d90;"><?= (float)$i['quantity'] ?> <?= $i['unit'] ?> × <?= money($i['price']) ?></p>
            </div>
            <b><?= money(round($i['quantity'] * $i['price'])) ?></b>
          </div>
        <?php endwhile; ?>
      </div>

      <div style="margin-top:20px; padding-top:16px; border-top:1px dashed #d5e1d8; display:flex; justify-content:space-between; font-size:18px; font-weight:800;">
        <span>Total</span><span style="color:#1f7559;"><?= money($order['amount']) ?></span>
      </div>

      <p style="font-size:12px; color:#809489; margin-top:14px;">Pembayaran: <b><?= htmlspecialchars($order['payment']) ?></b></p>
      <?php if (!empty($order['notes'])): ?>
        <p style="font-size:12px; color:#809489; margin-top:4px;">Catatan: <?= htmlspecialchars($order['notes']) ?></p>
      <?php endif; ?>

      <div style="text-align:center; margin-top:18px;"><?= badge_status($order['status']) ?></div>

      <p style="text-align:center; font-size:10px; color:#8a9d90; margin-top:20px; line-height:1.6;">
        Terima kasih telah mempercayakan cucian Anda.<br>
        Simpan nota ini sebagai bukti pengambilan.
      </p>
    </div>

    <div class="no-print" style="padding:20px 28px; border-top:1px solid #e4ece6; background:#fafcfb;">
      <form method="POST" style="margin-bottom:12px;">
        <label class="form-label">Update status pengerjaan</label>
        <div style="display:flex; gap:8px;">
          <select name="status" class="form-input">
            <?php foreach (['Menunggu', 'Diproses', 'Siap Diambil', 'Selesai'] as $st): ?>
              <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-secondary">Simpan</button>
        </div>
      </form>
      <button type="button" onclick="window.print()" class="btn-primary btn-block">
        <?= icon('reports', 16) ?> Cetak nota / simpan PDF
      </button>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
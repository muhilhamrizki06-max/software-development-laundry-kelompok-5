<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

require_owner_view($role);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_page();
    $label = trim($_POST['label'] ?? '');
    $amount = (int)($_POST['amount'] ?? 0);
    $category = $_POST['category'] ?? 'Lainnya';
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($label !== '' && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO expenses (label, amount, category, date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $label, $amount, $category, $date);
        $stmt->execute();
        header("Location: expenses.php?msg=ok");
        exit;
    }
}

$expenses = $conn->query("SELECT * FROM expenses ORDER BY date DESC, id DESC");
$totalExp = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM expenses")->fetch_assoc()['t'];
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Keuangan / Pengeluaran</p>
      <h2 class="page-title">Catat biaya, pahami laba.</h2>
      <p class="page-desc">Pengeluaran tercatat langsung memengaruhi estimasi laba di dashboard owner.</p>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">Pengeluaran berhasil dicatat. Estimasi laba telah diperbarui.</div>
  <?php endif; ?>

  <div class="grid-2-left">
    <form method="POST" class="panel panel-pad">
      <?= csrf_field() ?>
      <h3 class="section-title">Tambah pengeluaran</h3>
      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Keterangan</label>
        <input type="text" name="label" class="form-input" placeholder="Contoh: Pembelian deterjen" maxlength="100" required>
      </div>
      <div class="form-group">
        <label class="form-label">Jumlah (Rp)</label>
        <input type="number" name="amount" min="1" class="form-input" placeholder="0" required>
      </div>
      <div class="form-group">
        <label class="form-label">Kategori</label>
        <select name="category" class="form-input">
          <?php foreach (['Operasional', 'Bahan baku', 'Perlengkapan', 'Gaji', 'Lainnya'] as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Tanggal</label>
        <input type="date" name="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" class="form-input" required>
      </div>
      <button type="submit" class="btn-primary btn-block"><?= icon('plus', 16) ?> Catat pengeluaran</button>
    </form>

    <section class="panel">
      <div class="panel-pad" style="display:flex; justify-content:space-between; align-items:center;">
        <h3 class="section-title">Riwayat pengeluaran</h3>
        <strong style="font-size:14px; color:#28795e;"><?= money($totalExp) ?></strong>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>Keterangan</th><th>Kategori</th><th>Tanggal</th><th>Jumlah</th></tr>
          </thead>
          <tbody>
            <?php while ($e = $expenses->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:700;"><?= htmlspecialchars($e['label']) ?></td>
                <td><?= htmlspecialchars($e['category']) ?></td>
                <td><?= date('d M Y', strtotime($e['date'])) ?></td>
                <td style="font-weight:700;"><?= money($e['amount']) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</div>
<?php include '../public/includes/footer.php'; ?>
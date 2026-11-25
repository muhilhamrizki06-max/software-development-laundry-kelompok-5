<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'new') {
        $stmt = $conn->prepare("INSERT INTO inventory_items (name, category, unit, quantity, minimum) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdd", $_POST['name'], $_POST['category'], $_POST['unit'], $_POST['quantity'], $_POST['minimum']);
        $stmt->execute();
        header("Location: stock.php?msg=added");
        exit;
    } elseif ($act === 'move') {
        $id   = (int)$_POST['item_id'];
        $type = $_POST['type'];
        $qty  = (float)$_POST['quantity'];
        $item = $conn->query("SELECT * FROM inventory_items WHERE id=$id")->fetch_assoc();
        if ($item) {
            $before = (float)$item['quantity'];
            $after  = $type === 'in' ? $before + $qty : max(0, $before - $qty);
            $conn->query("UPDATE inventory_items SET quantity=$after WHERE id=$id");
            $stmt = $conn->prepare("INSERT INTO stock_movements (item_id, type, quantity, before_qty, after_qty, note, actor, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdddsss", $id, $type, $qty, $before, $after, $_POST['note'], $user['name'], $role);
            $stmt->execute();
        }
        header("Location: stock.php?msg=moved");
        exit;
    } elseif ($act === 'delete') {
        $id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: stock.php?msg=deleted");
        exit;
    }
}

$items = $conn->query("SELECT * FROM inventory_items ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$logs  = $conn->query("SELECT sm.*, ii.name AS item_name, ii.unit FROM stock_movements sm JOIN inventory_items ii ON sm.item_id = ii.id ORDER BY sm.id DESC LIMIT 15");
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Operasional / Persediaan</p>
      <h2 class="page-title">Stok terjaga, layanan lancar.</h2>
      <p class="page-desc">Pantau persediaan bahan cuci, catat mutasi masuk/keluar, atau hapus item yang tidak dipakai.</p>
    </div>
  </div>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-warning">Barang persediaan beserta riwayat mutasinya berhasil dihapus.</div>
  <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
    <div class="alert alert-success">Barang persediaan baru berhasil ditambahkan.</div>
  <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'moved'): ?>
    <div class="alert alert-success">Mutasi stok berhasil dicatat.</div>
  <?php endif; ?>

  <div class="grid-2-col">
    <div style="display:flex; flex-direction:column; gap:20px;">
      <section class="panel">
        <div class="panel-pad"><h3 class="section-title">Daftar barang persediaan</h3></div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>Nama Barang</th><th>Kategori</th><th>Stok Tersedia</th><th>Batas Min.</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="6" style="text-align:center; padding:24px; color:#81958b;">Belum ada data barang persediaan.</td></tr>
              <?php else: foreach ($items as $i):
                $st = $i['quantity'] <= 0 ? 'Habis' : ($i['quantity'] <= $i['minimum'] ? 'Menipis' : 'Aman');
              ?>
                <tr>
                  <td style="font-weight:800;"><?= htmlspecialchars($i['name']) ?></td>
                  <td><?= htmlspecialchars($i['category']) ?></td>
                  <td><b><?= (float)$i['quantity'] ?></b> <?= $i['unit'] ?></td>
                  <td><?= (float)$i['minimum'] ?> <?= $i['unit'] ?></td>
                  <td><?= badge_status($st) ?></td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Hapus permanen barang <?= htmlspecialchars(addslashes($i['name'])) ?>? Riwayat mutasi barang ini juga akan terhapus.')">
                      <input type="hidden" name="act" value="delete">
                      <input type="hidden" name="item_id" value="<?= $i['id'] ?>">
                      <button type="submit" class="icon-btn" style="width:32px; height:32px;" title="Hapus barang">
                        <?= icon('close', 14) ?>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="panel">
        <div class="panel-pad"><h3 class="section-title">Riwayat mutasi stok terbaru</h3></div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Barang</th><th>Mutasi</th><th>Stok Akhir</th><th>Catatan</th><th>Petugas</th></tr></thead>
            <tbody>
              <?php if ($logs->num_rows === 0): ?>
                <tr><td colspan="5" style="text-align:center; padding:24px; color:#81958b;">Belum ada riwayat mutasi stok.</td></tr>
              <?php else: while ($l = $logs->fetch_assoc()): ?>
                <tr>
                  <td style="font-weight:700;"><?= htmlspecialchars($l['item_name']) ?></td>
                  <td style="font-weight:800; color:<?= $l['type']==='in' ? '#1f7559' : '#b46d32' ?>;">
                    <?= $l['type']==='in' ? '+' : '−' ?><?= (float)$l['quantity'] ?> <?= $l['unit'] ?>
                  </td>
                  <td><?= (float)$l['after_qty'] ?> <?= $l['unit'] ?></td>
                  <td><?= htmlspecialchars($l['note']) ?></td>
                  <td><?= htmlspecialchars($l['actor']) ?></td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <aside style="display:flex; flex-direction:column; gap:20px;">
      <form method="POST" class="panel panel-pad">
        <input type="hidden" name="act" value="move">
        <h3 class="section-title">Catat mutasi stok</h3>
        <div class="form-group" style="margin-top:14px;">
          <label class="form-label">Jenis mutasi</label>
          <select name="type" class="form-input"><option value="in">+ Tambah stok (Masuk)</option><option value="out">− Kurangi stok (Pemakaian)</option></select>
        </div>
        <div class="form-group">
          <label class="form-label">Pilih barang</label>
          <select name="item_id" class="form-input" <?= empty($items) ? 'disabled' : '' ?>>
            <?php foreach ($items as $i): ?>
              <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['name']) ?> (Sisa: <?= (float)$i['quantity'] ?> <?= $i['unit'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Jumlah</label>
          <input type="number" step="0.1" min="0.1" name="quantity" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Catatan</label>
          <input type="text" name="note" placeholder="Contoh: Beli dari supplier / Dipakai cuci" class="form-input" required>
        </div>
        <button type="submit" class="btn-primary btn-block" <?= empty($items) ? 'disabled' : '' ?>><?= icon('check', 16) ?> Simpan mutasi stok</button>
      </form>

      <form method="POST" class="panel panel-pad">
        <input type="hidden" name="act" value="new">
        <h3 class="section-title">Tambah barang baru</h3>
        <div class="form-group" style="margin-top:14px;"><label class="form-label">Nama barang</label><input type="text" name="name" class="form-input" required></div>
        <div class="grid-form-2">
          <div class="form-group"><label class="form-label">Kategori</label><input type="text" name="category" value="Bahan cuci" class="form-input" required></div>
          <div class="form-group"><label class="form-label">Satuan</label><select name="unit" class="form-input"><option>liter</option><option>kg</option><option>pack</option><option>pcs</option></select></div>
          <div class="form-group"><label class="form-label">Stok awal</label><input type="number" step="0.1" value="0" name="quantity" class="form-input" required></div>
          <div class="form-group"><label class="form-label">Batas min.</label><input type="number" step="0.1" value="5" name="minimum" class="form-input" required></div>
        </div>
        <button type="submit" class="btn-secondary btn-block"><?= icon('plus', 15) ?> Simpan barang baru</button>
      </form>
    </aside>
  </div>
</div>
<?php include '../public/includes/footer.php'; ?>
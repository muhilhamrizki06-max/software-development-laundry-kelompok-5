<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

if ($role !== 'owner') {
    exit('<div class="page"><div class="alert alert-danger">Halaman ini khusus untuk Owner.</div></div>');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'profil') {
        $nama   = trim($_POST['shop_name']);
        $alamat = trim($_POST['shop_address']);
        $telp   = trim($_POST['shop_phone']);
        $footer = trim($_POST['receipt_footer']);

        $stmt = $conn->prepare("UPDATE settings SET shop_name = ?, shop_address = ?, shop_phone = ?, receipt_footer = ? WHERE id = 1");
        $stmt->bind_param("ssss", $nama, $alamat, $telp, $footer);
        $stmt->execute();
        $msg = "Profil toko dan catatan struk berhasil diperbarui.";
    }

    elseif ($act === 'add_service') {
        $sName  = trim($_POST['name']);
        $sUnit  = $_POST['unit'];
        $sPrice = max(0, (int)$_POST['price']);

        if ($sName !== '' && $sPrice > 0) {
            $stmt = $conn->prepare("INSERT INTO services (name, unit, price) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $sName, $sUnit, $sPrice);
            $stmt->execute();
            $msg = "Layanan baru berhasil ditambahkan.";
        }
    }

    elseif ($act === 'edit_service') {
        $sid    = (int)$_POST['id'];
        $sName  = trim($_POST['name']);
        $sUnit  = $_POST['unit'];
        $sPrice = max(0, (int)$_POST['price']);

        $stmt = $conn->prepare("UPDATE services SET name = ?, unit = ?, price = ? WHERE id = ?");
        $stmt->bind_param("ssii", $sName, $sUnit, $sPrice, $sid);
        $stmt->execute();
        $msg = "Tarif layanan berhasil diperbarui.";
    }

    elseif ($act === 'delete_service') {
        $sid = (int)$_POST['id'];
        $cek = $conn->query("SELECT COUNT(*) c FROM order_items WHERE service_id = $sid")->fetch_assoc()['c'];
        if ($cek > 0) {
            $msg = "Layanan tidak dapat dihapus karena sudah memiliki riwayat transaksi.";
        } else {
            $conn->query("DELETE FROM services WHERE id = $sid");
            $msg = "Layanan berhasil dihapus.";
        }
    }
}

$cfg = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
$services = $conn->query("SELECT * FROM services ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Pengaturan / Toko & Tarif</p>
      <h2 class="page-title">Profil usaha & daftar layanan.</h2>
      <p class="page-desc">Identitas toko otomatis tampil pada nota cetak dan pesan WhatsApp pelanggan.</p>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="grid-2-left">

  <form method="POST" class="panel panel-pad">
      <input type="hidden" name="act" value="profil">
      <h3 class="section-title">Profil outlet & struk</h3>
      <p class="section-sub">Informasi yang dicetak di bagian atas dan bawah nota.</p>

      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Nama usaha laundry</label>
        <input type="text" name="shop_name" value="<?= htmlspecialchars($cfg['shop_name'] ?? 'Laundria') ?>" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat outlet</label>
        <input type="text" name="shop_address" value="<?= htmlspecialchars($cfg['shop_address'] ?? '') ?>" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Nomor WhatsApp / Telepon</label>
        <input type="text" name="shop_phone" value="<?= htmlspecialchars($cfg['shop_phone'] ?? '') ?>" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Catatan kaki (footer) nota</label>
        <textarea name="receipt_footer" rows="3" class="form-input"><?= htmlspecialchars($cfg['receipt_footer'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn-primary btn-block"><?= icon('check', 16) ?> Simpan profil toko</button>
    </form>

    <div style="display:flex; flex-direction:column; gap:20px;">
      <section class="panel">
        <div class="panel-pad">
          <h3 class="section-title">Daftar layanan & harga</h3>
          <p class="section-sub">Ubah nama, satuan, atau harga layanan secara langsung.</p>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>Nama Layanan</th><th>Satuan</th><th>Harga (Rp)</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach ($services as $s): ?>
                <tr>
                  <form method="POST">
                    <input type="hidden" name="act" value="edit_service">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <td>
                      <input type="text" name="name" value="<?= htmlspecialchars($s['name']) ?>" class="form-input" style="height:34px;" required>
                    </td>
                    <td style="width:105px;">
                      <select name="unit" class="form-input" style="height:34px;">
                        <?php foreach (['kg', 'pcs', 'm2'] as $u): ?>
                          <option value="<?= $u ?>" <?= $s['unit'] === $u ? 'selected' : '' ?>>per <?= $u ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td style="width:130px;">
                      <input type="number" name="price" value="<?= $s['price'] ?>" min="0" step="500" class="form-input" style="height:34px;" required>
                    </td>
                    <td style="white-space:nowrap;">
                      <button type="submit" class="btn-secondary" style="height:34px; padding:0 12px;">Simpan</button>
                  </form>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus layanan <?= htmlspecialchars(addslashes($s['name'])) ?>?')">
                        <input type="hidden" name="act" value="delete_service">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="icon-btn" style="height:34px; width:34px;" title="Hapus layanan">
                          <?= icon('close', 14) ?>
                        </button>
                      </form>
                    </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <form method="POST" class="panel panel-pad">
        <input type="hidden" name="act" value="add_service">
        <h3 class="section-title">Tambah layanan baru</h3>
        <div class="grid-form-2" style="margin-top:14px;">
          <div class="form-group">
            <label class="form-label">Nama layanan</label>
            <input type="text" name="name" placeholder="Contoh: Cuci Karpet / Jas" class="form-input" required>
          </div>
          <div style="display:grid; grid-template-columns: 100px 1fr; gap:10px;">
            <div class="form-group">
              <label class="form-label">Satuan</label>
              <select name="unit" class="form-input">
                <option value="kg">per kg</option>
                <option value="pcs">per pcs</option>
                <option value="m2">per m2</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Harga (Rp)</label>
              <input type="number" name="price" min="500" step="500" placeholder="15000" class="form-input" required>
            </div>
          </div>
        </div>
        <button type="submit" class="btn-primary"><?= icon('plus', 15) ?> Tambah layanan</button>
      </form>
    </div>
  </div>
</div>
<?php include '../public/includes/footer.php'; ?>
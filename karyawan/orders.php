<?php
require_once 'config/koneksi.php';
include 'includes/header.php';

$customers = $conn->query("SELECT * FROM customers ORDER BY name ASC");
$services  = $conn->query("SELECT * FROM services ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$shiftOpen = $conn->query("SELECT id FROM cash_shifts WHERE closed_at IS NULL LIMIT 1")->fetch_assoc();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $custId  = $_POST['customer_id'];
    $dueDate = $_POST['due_date'];
    $payment = $_POST['payment'];
    $notes   = trim($_POST['notes']);
    $svcIds  = $_POST['service_id'];
    $qtys    = $_POST['quantity'];

    $conn->begin_transaction();
    try {
        if ($custId === 'new') {
            $newName  = trim($_POST['new_name']);
            $newPhone = trim($_POST['new_phone']);
            $consent  = isset($_POST['consent']) ? 1 : 0;

            $stmtC = $conn->prepare("INSERT INTO customers (name, phone, consent) VALUES (?, ?, ?)");
            $stmtC->bind_param("ssi", $newName, $newPhone, $consent);
            $stmtC->execute();
            $custId = $conn->insert_id;
        }

        $total = 0;
        $hargaMap = [];
        foreach ($services as $s) {
            $hargaMap[$s['id']] = $s['price'];
        }
        foreach ($svcIds as $idx => $sid) {
            $total += round($hargaMap[$sid] * (float)$qtys[$idx]);
        }

        $orderId = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $stmtO = $conn->prepare("INSERT INTO orders (id, customer_id, amount, status, due_date, payment, notes) VALUES (?, ?, ?, 'Menunggu', ?, ?, ?)");
        $stmtO->bind_param("siisss", $orderId, $custId, $total, $dueDate, $payment, $notes);
        $stmtO->execute();

        $stmtI = $conn->prepare("INSERT INTO order_items (order_id, service_id, quantity) VALUES (?, ?, ?)");
        foreach ($svcIds as $idx => $sid) {
            $q = (float)$qtys[$idx];
            $stmtI->bind_param("sid", $orderId, $sid, $q);
            $stmtI->execute();
        }

        $conn->commit();
        header("Location: nota.php?id=$orderId&baru=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $err = "Gagal menyimpan order: " . $e->getMessage();
    }
}
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Operasional / Input order</p>
      <h2 class="page-title">Cucian baru, layanan terbaik.</h2>
      <p class="page-desc">Pilih pelanggan, tambahkan layanan, lalu simpan order dan cetak nota.</p>
    </div>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <form method="POST" class="grid-2-col">
    <div style="display:flex; flex-direction:column; gap:20px;">
      <!-- 01 Pelanggan -->
      <section class="panel panel-pad">
        <h3 class="section-title">01 · Pelanggan</h3>
        <p class="section-sub">Pilih pelanggan terdaftar atau tambahkan pelanggan baru.</p>
        <div class="form-group" style="margin-top:14px;">
          <label class="form-label">Pilih pelanggan</label>
          <select name="customer_id" id="sel-cust" class="form-input" onchange="togglePelangganBaru()">
            <?php while ($c = $customers->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> · <?= htmlspecialchars($c['phone']) ?></option>
            <?php endwhile; ?>
            <option value="new">+ Tambah pelanggan baru</option>
          </select>
        </div>
        <div id="new-cust-box" style="display:none; margin-top:14px;">
          <div class="grid-form-2">
            <div class="form-group">
              <label class="form-label">Nama lengkap</label>
              <input type="text" name="new_name" id="inp-new-name" class="form-input" placeholder="Nama pelanggan">
            </div>
            <div class="form-group">
              <label class="form-label">Nomor WhatsApp</label>
              <input type="text" name="new_phone" id="inp-new-phone" class="form-input" placeholder="081234567890">
            </div>
          </div>
          <label style="display:flex; align-items:center; gap:8px; font-size:11px; color:#7e9487; cursor:pointer;">
            <input type="checkbox" name="consent" checked> Pelanggan menyetujui menerima info / promo WhatsApp.
          </label>
        </div>
      </section>

      <!-- 02 Layanan Laundry -->
      <section class="panel panel-pad">
        <h3 class="section-title">02 · Layanan laundry</h3>
        <p class="section-sub">Satu order dapat berisi beberapa jenis layanan sekaligus.</p>
        <div id="items-container" style="margin-top:14px; display:flex; flex-direction:column; gap:10px;">
          <div class="item-row" style="display:grid; grid-template-columns: 1fr 110px 36px; gap:10px; align-items:end;">
            <div>
              <label class="form-label">Jenis Layanan</label>
              <select name="service_id[]" class="form-input svc-select" onchange="hitungTotal()">
                <?php foreach ($services as $s): ?>
                  <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>">
                    <?= htmlspecialchars($s['name']) ?> · <?= money($s['price']) ?>/<?= $s['unit'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Jumlah</label>
              <input type="number" name="quantity[]" class="form-input svc-qty" value="1" min="0.1" step="0.1" oninput="hitungTotal()" required>
            </div>
            <button type="button" class="icon-btn" onclick="hapusBaris(this)" title="Hapus layanan">
              <?= icon('close', 15) ?>
            </button>
          </div>
        </div>
        <button type="button" class="btn-secondary btn-block" style="margin-top:14px; border-style:dashed;" onclick="tambahBaris()">
          <?= icon('plus', 15) ?> Tambah layanan
        </button>
      </section>

      <!-- 03 Penyelesaian & Pembayaran -->
      <section class="panel panel-pad">
        <h3 class="section-title">03 · Penyelesaian & pembayaran</h3>
        <p class="section-sub">Informasi ini akan dicetak pada nota pelanggan.</p>
        <div class="grid-form-2" style="margin-top:14px;">
          <div class="form-group">
            <label class="form-label">Estimasi selesai</label>
            <input type="date" name="due_date" class="form-input" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" min="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Metode pembayaran</label>
            <select name="payment" class="form-input">
              <option value="Bayar saat ambil">Bayar saat ambil</option>
              <option value="Tunai" <?= !$shiftOpen ? 'disabled' : '' ?>>
                Tunai <?= !$shiftOpen ? '(Buka shift kas dulu)' : '' ?>
              </option>
              <option value="QRIS">QRIS</option>
              <option value="Transfer">Transfer</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Catatan khusus (opsional)</label>
          <textarea name="notes" rows="2" class="form-input" placeholder="Contoh: Pisahkan pakaian putih / jangan disetrika terlalu panas"></textarea>
        </div>
      </section>
    </div>

    <!-- Ringkasan Kanan -->
    <aside class="panel" style="overflow:hidden; position:sticky; top:20px;">
      <div style="background:#1f654f; color:#fff; padding:24px;">
        <div style="color:#e9ba88; margin-bottom:12px;"><?= icon('laundry', 26) ?></div>
        <h3 style="font-size:18px; font-weight:800;">Ringkasan order</h3>
        <p style="font-size:11px; opacity:0.7;">Periksa total sebelum menyimpan</p>
      </div>
      <div class="panel-pad">
        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; border-bottom:1px dashed #dce6df;">
          <span style="font-size:12px; font-weight:700;">Total biaya</span>
          <strong id="txt-total" style="font-size:22px; color:#1f7057;">Rp 7.000</strong>
        </div>
        <button type="submit" class="btn-primary btn-block" style="margin-top:18px;">
          <?= icon('reports', 16) ?> Simpan & cetak nota
        </button>
      </div>
    </aside>
  </form>
</div>

<script>
function togglePelangganBaru() {
  const isNew = document.getElementById('sel-cust').value === 'new';
  document.getElementById('new-cust-box').style.display = isNew ? 'block' : 'none';
  document.getElementById('inp-new-name').required = isNew;
  document.getElementById('inp-new-phone').required = isNew;
}

function tambahBaris() {
  const container = document.getElementById('items-container');
  const clone = container.firstElementChild.cloneNode(true);
  clone.querySelector('.svc-qty').value = 1;
  container.appendChild(clone);
  hitungTotal();
}

function hapusBaris(btn) {
  const container = document.getElementById('items-container');
  if (container.children.length > 1) {
    btn.parentElement.remove();
    hitungTotal();
  }
}

function hitungTotal() {
  let total = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const sel = row.querySelector('.svc-select');
    const price = Number(sel.options[sel.selectedIndex].dataset.price);
    const qty = Number(row.querySelector('.svc-qty').value) || 0;
    total += Math.round(price * qty);
  });
  document.getElementById('txt-total').innerText = 'Rp ' + total.toLocaleString('id-ID');
}

togglePelangganBaru();
hitungTotal();
</script>
<?php include 'includes/footer.php'; ?>
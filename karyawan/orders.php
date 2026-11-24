<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

$customers = $conn->query("SELECT * FROM customers ORDER BY name ASC");
$services  = $conn->query("SELECT * FROM services ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$shiftOpen = $conn->query("SELECT id FROM cash_shifts WHERE closed_at IS NULL LIMIT 1")->fetch_assoc();

$paketList = [
    'Reguler' => ['persen' => 0,   'hari' => 3],
    'Kilat'   => ['persen' => 50,  'hari' => 1],
    'Express' => ['persen' => 100, 'hari' => 0],
];

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $custId  = $_POST['customer_id'];
    $dueDate = $_POST['due_date'];
    $payment = $_POST['payment'];
    $dpInput = max(0, (int)($_POST['paid_amount'] ?? 0));
    $paket   = $_POST['paket'] ?? 'Reguler';
    $parfum  = $_POST['parfum'] ?? 'Lavender';
    $rawNote = trim($_POST['notes']);
    $svcIds  = $_POST['service_id'];
    $qtys    = $_POST['quantity'];

    $persen = $paketList[$paket]['persen'] ?? 0;
    $infoTag = "Paket: $paket" . ($persen > 0 ? " (+{$persen}%)" : "") . " · Parfum: $parfum";
    $notes = $rawNote !== '' ? "$infoTag | $rawNote" : $infoTag;

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

        $subtotal = 0;
        $hargaMap = [];
        foreach ($services as $s) {
            $hargaMap[$s['id']] = $s['price'];
        }
        foreach ($svcIds as $idx => $sid) {
            $subtotal += round($hargaMap[$sid] * (float)$qtys[$idx]);
        }

        $biayaPaket = round(($subtotal * $persen / 100) / 100) * 100;
        $total = $subtotal + $biayaPaket;
        $paidAmount = ($payment === 'Bayar saat ambil') ? 0 : ($dpInput > 0 ? min($dpInput, $total) : $total);

        $orderId = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $stmtO = $conn->prepare("INSERT INTO orders (id, customer_id, amount, paid_amount, status, due_date, payment, notes) VALUES (?, ?, ?, ?, 'Menunggu', ?, ?, ?)");
        $stmtO->bind_param("siiisss", $orderId, $custId, $total, $paidAmount, $dueDate, $payment, $notes);
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
      <p class="page-desc">Pilih pelanggan, layanan, paket kecepatan, aroma parfum, dan pembayaran.</p>
    </div>
  </div>

  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="POST" class="grid-2-col">
    <div style="display:flex; flex-direction:column; gap:20px;">
      <section class="panel panel-pad">
        <h3 class="section-title">01 · Pelanggan</h3>
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

      <section class="panel panel-pad">
        <h3 class="section-title">02 · Layanan laundry</h3>
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

      <section class="panel panel-pad">
        <h3 class="section-title">03 · Paket kecepatan, parfum & pembayaran</h3>
        <div class="grid-form-2" style="margin-top:14px;">
          <div class="form-group">
            <label class="form-label">Paket kecepatan</label>
            <select name="paket" id="sel-paket" class="form-input" onchange="ubahPaket()">
              <option value="Reguler" data-persen="0" data-hari="3">Reguler (3 Hari · Normal)</option>
              <option value="Kilat" data-persen="50" data-hari="1">Kilat (1 Hari · +50%)</option>
              <option value="Express" data-persen="100" data-hari="0">Express (Hari Ini · +100%)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Pilihan parfum</label>
            <select name="parfum" class="form-input">
              <option value="Lavender">Lavender</option>
              <option value="Sakura">Sakura</option>
              <option value="Ocean Fresh">Ocean Fresh</option>
              <option value="Tanpa parfum">Tanpa parfum</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Estimasi selesai</label>
            <input type="date" name="due_date" id="inp-due" class="form-input" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" min="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Metode pembayaran</label>
            <select name="payment" id="sel-pay" class="form-input" onchange="hitungTotal()">
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
          <textarea name="notes" rows="2" class="form-input" placeholder="Contoh: Pisahkan pakaian putih"></textarea>
        </div>
      </section>
    </div>

    <aside class="panel" style="overflow:hidden; position:sticky; top:20px;">
      <div style="background:#1f654f; color:#fff; padding:24px;">
        <div style="color:#e9ba88; margin-bottom:12px;"><?= icon('laundry', 26) ?></div>
        <h3 style="font-size:18px; font-weight:800;">Ringkasan order</h3>
        <p style="font-size:11px; opacity:0.7;">Periksa rincian biaya & DP sebelum menyimpan</p>
      </div>
      <div class="panel-pad">
        <div style="display:flex; justify-content:space-between; font-size:12px; color:#7b9187; margin-bottom:8px;">
          <span>Subtotal layanan</span>
          <b id="txt-sub">Rp 7.000</b>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:12px; color:#7b9187; margin-bottom:14px;">
          <span id="lbl-paket">Paket Reguler</span>
          <b id="txt-paket">Rp 0</b>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; padding-top:14px; border-top:1px dashed #dce6df;">
          <span style="font-size:12px; font-weight:700;">Total biaya</span>
          <strong id="txt-total" style="font-size:22px; color:#1f7057;">Rp 7.000</strong>
        </div>
        <div class="form-group" style="margin-top:14px;">
          <label class="form-label">Bayar sekarang / DP (Rp)</label>
          <input type="number" name="paid_amount" id="inp-dp" min="0" step="1000" class="form-input" placeholder="Kosongkan jika lunas / bayar nanti" oninput="hitungTotal()">
        </div>
        <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:14px;">
          <span style="color:#7b9187;">Sisa tagihan</span>
          <b id="txt-sisa" style="color:#b66560;">Rp 7.000</b>
        </div>
        <button type="submit" class="btn-primary btn-block">
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

function ubahPaket() {
  const sel = document.getElementById('sel-paket');
  const hari = Number(sel.options[sel.selectedIndex].dataset.hari);
  const tgl = new Date();
  tgl.setDate(tgl.getDate() + hari);
  document.getElementById('inp-due').value = tgl.toISOString().split('T')[0];
  hitungTotal();
}

function hitungTotal() {
  let subtotal = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const sel = row.querySelector('.svc-select');
    const price = Number(sel.options[sel.selectedIndex].dataset.price);
    const qty = Number(row.querySelector('.svc-qty').value) || 0;
    subtotal += Math.round(price * qty);
  });

  const selPaket = document.getElementById('sel-paket');
  const persen = Number(selPaket.options[selPaket.selectedIndex].dataset.persen);
  const tambahan = Math.round((subtotal * persen / 100) / 100) * 100;
  const total = subtotal + tambahan;

  const payMethod = document.getElementById('sel-pay').value;
  const bayar = Math.min(Number(document.getElementById('inp-dp').value) || 0, total);
  const sisa = payMethod === 'Bayar saat ambil' ? total : (bayar > 0 ? total - bayar : 0);

  document.getElementById('txt-sub').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
  document.getElementById('lbl-paket').innerText = 'Paket ' + selPaket.value + (persen > 0 ? ' (+' + persen + '%)' : '');
  document.getElementById('txt-paket').innerText = 'Rp ' + tambahan.toLocaleString('id-ID');
  document.getElementById('txt-total').innerText = 'Rp ' + total.toLocaleString('id-ID');
  document.getElementById('txt-sisa').innerText = sisa > 0 ? 'Rp ' + sisa.toLocaleString('id-ID') : 'Lunas';
  document.getElementById('txt-sisa').style.color = sisa > 0 ? '#b66560' : '#1f7559';
}

togglePelangganBaru();
hitungTotal();
</script>
<?php include '../public/includes/footer.php'; ?>
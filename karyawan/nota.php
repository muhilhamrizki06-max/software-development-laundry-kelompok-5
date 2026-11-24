<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

$id = trim($_GET['id'] ?? '');

function format_wa($phone) {
    $p = preg_replace('/\D+/', '', (string)$phone);
    if (strpos($p, '0') === 0) $p = '62' . substr($p, 1);
    elseif (strpos($p, '8') === 0) $p = '62' . $p;
    return $p;
}

if ($id === '') {
    $q = trim($_GET['q'] ?? '');
    $stFilter = $_GET['status'] ?? 'all';

    $where = ["1=1"];
    $params = [];
    $types = "";

    if ($q !== '') {
        $where[] = "(o.id LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
        $like = "%$q%";
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types .= "sss";
    }
    if ($stFilter !== 'all') {
        $where[] = "o.status = ?";
        $params[] = $stFilter;
        $types .= "s";
    }

    $sql = "SELECT o.*, c.name AS cname, c.phone, GROUP_CONCAT(s.name SEPARATOR ', ') AS layanan_list
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN services s ON oi.service_id = s.id
            WHERE " . implode(" AND ", $where) . "
            GROUP BY o.id ORDER BY o.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($types !== "") $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $list = $stmt->get_result();
    ?>
    <div class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">Operasional / Nota</p>
          <h2 class="page-title">Daftar nota & status cucian.</h2>
          <p class="page-desc">Pilih transaksi untuk memperbarui status, mengirim nota WA, atau mencetak struk.</p>
        </div>
        <a href="orders.php" class="btn-primary"><?= icon('plus', 16) ?> Order baru</a>
      </div>

      <section class="panel">
        <form method="GET" class="panel-pad" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:12px;">
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari ID order, nama, atau no. WA..." class="form-input" style="max-width:300px;">
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <select name="status" class="form-input" style="width:auto;" onchange="this.form.submit()">
              <option value="all">Semua status</option>
              <?php foreach (['Menunggu', 'Diproses', 'Siap Diambil', 'Selesai'] as $st): ?>
                <option value="<?= $st ?>" <?= $stFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-secondary">Cari</button>
          </div>
        </form>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>ID Nota</th><th>Pelanggan</th><th>Layanan</th><th>Total</th><th>Status</th><th>Estimasi</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php if ($list->num_rows === 0): ?>
                <tr><td colspan="7" style="text-align:center; padding:32px; color:#81958b;">Belum ada nota yang sesuai pencarian.</td></tr>
              <?php else: while ($row = $list->fetch_assoc()): ?>
                <tr>
                  <td><a href="nota.php?id=<?= $row['id'] ?>" style="font-weight:800; color:#28795e;"><?= $row['id'] ?></a></td>
                  <td>
                    <div style="font-weight:700;"><?= htmlspecialchars($row['cname']) ?></div>
                    <div style="font-size:10px; color:#8a9d90;"><?= htmlspecialchars($row['phone']) ?></div>
                  </td>
                  <td><?= htmlspecialchars($row['layanan_list']) ?></td>
                  <td style="font-weight:700;"><?= money($row['amount']) ?></td>
                  <td><?= badge_status($row['status']) ?></td>
                  <td><?= date('d M Y', strtotime($row['due_date'])) ?></td>
                  <td>
                    <a href="nota.php?id=<?= $row['id'] ?>" class="btn-secondary" style="height:32px; padding:0 12px;">
                      <?= icon('reports', 14) ?> Buka Nota
                    </a>
                  </td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
    <?php
    include '../public/includes/footer.php';
    exit;
}

$updated = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['status'])) {
        $stBaru = $_POST['status'];
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("ss", $stBaru, $id);
        $stmt->execute();

        if (isset($_POST['ajax'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'status' => $stBaru,
                'badge_html' => badge_status($stBaru)
            ]);
            exit;
        }
        $updated = true;
    } elseif (isset($_POST['lunasi'])) {
        $stmt = $conn->prepare("UPDATE orders SET paid_amount = amount WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $updated = true;
    }
}

$stmt = $conn->prepare("SELECT o.*, c.name AS cname, c.phone FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo '<div class="page"><div class="alert alert-danger">Nota tidak ditemukan.</div><a href="nota.php" class="btn-secondary">Lihat Daftar Nota</a></div>';
    include '../public/includes/footer.php';
    exit;
}

$stmtItems = $conn->prepare("SELECT oi.*, s.name, s.price, s.unit FROM order_items oi JOIN services s ON oi.service_id = s.id WHERE oi.order_id = ?");
$stmtItems->bind_param("s", $id);
$stmtItems->execute();
$itemsList = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

$sisaTagihan = max(0, $order['amount'] - (int)$order['paid_amount']);
$waNum = format_wa($order['phone']);
$barisItemWa = [];
foreach ($itemsList as $it) {
    $subItem = money(round($it['quantity'] * $it['price']));
    $barisItemWa[] = "- {$it['name']} (" . (float)$it['quantity'] . " {$it['unit']}) = {$subItem}";
}

$statusBayarWa = $sisaTagihan > 0 ? "Dibayar: " . money($order['paid_amount']) . " | Sisa: " . money($sisaTagihan) : "LUNAS";

$teksNotaWa = "*LAUNDRIA*\n"
    . "Nota: *{$order['id']}*\n"
    . "Halo Kak *{$order['cname']}*, terima kasih sudah mempercayakan cucian Anda kepada kami.\n\n"
    . implode("\n", $barisItemWa) . "\n"
    . "Total: *" . money($order['amount']) . "* ({$statusBayarWa})\n"
    . "Estimasi selesai: " . date('d M Y', strtotime($order['due_date'])) . "\n"
    . (!empty($order['notes']) ? "Info: {$order['notes']}\n" : "")
    . "\nSimpan pesan ini sebagai bukti pengambilan. Terima kasih!";

$teksSiapWa = "Halo Kak *{$order['cname']}*, cucian Anda dengan nomor nota *{$order['id']}* sudah *SIAP DIAMBIL* di Laundria.\n"
    . "Total: *" . money($order['amount']) . "* ({$statusBayarWa}).\n"
    . "Silakan kunjungi outlet kami dengan menunjukkan nota ini. Terima kasih!";

$linkWaNota = "https://wa.me/{$waNum}?text=" . rawurlencode($teksNotaWa);
$linkWaSiap = "https://wa.me/{$waNum}?text=" . rawurlencode($teksSiapWa);
?>
<div class="page">
  <div class="no-print" style="max-width:480px; margin:0 auto 16px; display:flex; justify-content:space-between; align-items:center;">
    <a href="nota.php" class="btn-secondary">← Daftar Nota</a>
    <?php if (isset($_GET['baru'])): ?>
      <span class="badge badge-green">Order baru tersimpan</span>
    <?php elseif ($updated): ?>
      <span class="badge badge-green">Data diperbarui</span>
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
        <?php
        $subLayanan = 0;
        foreach ($itemsList as $i):
          $subItem = round($i['quantity'] * $i['price']);
          $subLayanan += $subItem;
        ?>
          <div style="display:flex; justify-content:space-between; font-size:12px;">
            <div>
              <b><?= htmlspecialchars($i['name']) ?></b>
              <p style="font-size:10px; color:#8a9d90;"><?= (float)$i['quantity'] ?> <?= $i['unit'] ?> × <?= money($i['price']) ?></p>
            </div>
            <b><?= money($subItem) ?></b>
          </div>
        <?php endforeach; ?>

        <?php if ($order['amount'] > $subLayanan): ?>
          <div style="display:flex; justify-content:space-between; font-size:12px; color:#9a5b25;">
            <span>Biaya tambahan paket kecepatan</span>
            <b><?= money($order['amount'] - $subLayanan) ?></b>
          </div>
        <?php endif; ?>
      </div>

      <div style="margin-top:20px; padding-top:16px; border-top:1px dashed #d5e1d8; display:flex; justify-content:space-between; font-size:18px; font-weight:800;">
        <span>Total</span><span style="color:#1f7559;"><?= money($order['amount']) ?></span>
      </div>

      <div style="display:flex; justify-content:space-between; font-size:12px; margin-top:8px; color:#536b63;">
        <span>Dibayar (<?= htmlspecialchars($order['payment']) ?>)</span><b><?= money($order['paid_amount']) ?></b>
      </div>
      <div style="display:flex; justify-content:space-between; font-size:13px; margin-top:4px;">
        <span>Status Bayar</span>
        <b style="color:<?= $sisaTagihan > 0 ? '#b66560' : '#1f7559' ?>;">
          <?= $sisaTagihan > 0 ? 'Sisa ' . money($sisaTagihan) : 'LUNAS' ?>
        </b>
      </div>

      <?php if (!empty($order['notes'])): ?>
        <p style="font-size:11px; color:#809489; margin-top:12px;">Info: <?= htmlspecialchars($order['notes']) ?></p>
      <?php endif; ?>

      <div id="nota-badge-status" style="text-align:center; margin-top:18px;"><?= badge_status($order['status']) ?></div>

      <p style="text-align:center; font-size:10px; color:#8a9d90; margin-top:20px; line-height:1.6;">
        Terima kasih telah mempercayakan cucian Anda.<br>
        Simpan nota ini sebagai bukti pengambilan.
      </p>
    </div>

    <div class="no-print" style="padding:20px 28px; border-top:1px solid #e4ece6; background:#fafcfb;">
      <form id="form-ajax-status" method="POST" style="margin-bottom:12px;">
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

      <?php if ($sisaTagihan > 0): ?>
        <form method="POST" style="margin-bottom:10px;">
          <button type="submit" name="lunasi" class="btn-secondary btn-block" style="border-color:#1f7559; color:#1f7559; background:#edf6f1;">
            <?= icon('check', 15) ?> Terima Pelunasan (<?= money($sisaTagihan) ?>)
          </button>
        </form>
      <?php endif; ?>

      <?php if (!empty($waNum)): ?>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; margin-bottom:10px;">
          <a href="<?= $linkWaNota ?>" target="_blank" rel="noopener" class="btn-secondary" style="border-color:#25a265; color:#1f7559; background:#eef9f3;">
            <?= icon('arrow', 14) ?> WA Nota
          </a>
          <a href="<?= $linkWaSiap ?>" target="_blank" rel="noopener" class="btn-secondary" style="border-color:#e39b55; color:#9a5b25; background:#fff8ef;">
            <?= icon('check', 14) ?> WA Siap Ambil
          </a>
        </div>
      <?php endif; ?>

      <button type="button" onclick="window.print()" class="btn-primary btn-block">
        <?= icon('reports', 16) ?> Cetak nota / simpan PDF
      </button>
    </div>
  </div>
</div>
<?php include '../public/includes/footer.php'; ?>
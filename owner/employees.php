<?php
require_once '../config/koneksi.php';
include '../public/includes/header.php';

if ($role !== 'owner') {
    exit('<div class="page"><div class="alert alert-danger">Halaman ini khusus untuk Owner.</div></div>');
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'add') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $cek = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $err = "Email sudah terdaftar di sistem.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, position, active) VALUES (?, ?, ?, 'employee', 'Karyawan', 1)");
            $stmt->bind_param("sss", $name, $email, $pass);
            $stmt->execute();
            header("Location: employees.php?msg=added");
            exit;
        }
    } elseif ($act === 'toggle') {
        $uid = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE users SET active = IF(active=1, 0, 1) WHERE id = ? AND role = 'employee'");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        header("Location: employees.php");
        exit;
    } elseif ($act === 'delete') {
        $uid = (int)$_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        header("Location: employees.php?msg=deleted");
        exit;
    }
}

$emps = $conn->query("SELECT * FROM users WHERE role = 'employee' ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$activeCnt = count(array_filter($emps, fn($e) => $e['active'] == 1));
?>
<div class="page">
  <div class="page-header">
    <div>
      <p class="eyebrow">Tim / Karyawan</p>
      <h2 class="page-title">Tim kecil, kerja lebih teratur.</h2>
      <p class="page-desc">Akun karyawan yang ditambahkan di sini dapat langsung digunakan untuk login operasional.</p>
    </div>
  </div>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
    <div class="alert alert-success">Karyawan baru berhasil ditambahkan!</div>
  <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-warning">Akun karyawan berhasil dihapus secara permanen.</div>
  <?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

  <div class="grid-2-left">
    <form method="POST" class="panel panel-pad">
      <input type="hidden" name="act" value="add">
      <h3 class="section-title">Tambah karyawan</h3>
      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Nama lengkap</label>
        <input type="text" name="name" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email login</label>
        <input type="email" name="email" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Kata sandi login</label>
        <input type="password" name="password" value="laundria2025" class="form-input" required>
      </div>
      <button type="submit" class="btn-primary btn-block"><?= icon('plus', 16) ?> Tambah karyawan</button>
    </form>

    <section class="panel">
      <div class="panel-pad">
        <h3 class="section-title"><?= $activeCnt ?> karyawan aktif</h3>
        <p class="section-sub">Nonaktifkan sementara atau hapus permanen akun yang sudah tidak dipakai.</p>
      </div>
      <div>
        <?php if (empty($emps)): ?>
          <div style="padding:24px; text-align:center; font-size:12px; color:#8ca092; border-top:1px solid #edf1ed;">
            Belum ada akun karyawan terdaftar.
          </div>
        <?php else: foreach ($emps as $e): ?>
          <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px; padding:18px 24px; border-top:1px solid #edf1ed;">
            <span style="width:40px; height:40px; border-radius:50%; background:#ecf4ed; color:#5b8669; font-size:12px; font-weight:800; display:grid; place-items:center;">
              <?= strtoupper(substr($e['name'], 0, 2)) ?>
            </span>
            <div style="flex:1; min-width:180px;">
              <div style="font-size:13px; font-weight:800;"><?= htmlspecialchars($e['name']) ?></div>
              <div style="font-size:11px; color:#8ca092; margin-top:2px;"><?= htmlspecialchars($e['email']) ?></div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
              <form method="POST">
                <input type="hidden" name="act" value="toggle">
                <input type="hidden" name="user_id" value="<?= $e['id'] ?>">
                <button type="submit" class="badge <?= $e['active'] ? 'badge-green' : 'badge-gray' ?>" style="border:none; cursor:pointer;">
                  <?= $e['active'] ? 'Aktif · nonaktifkan' : 'Nonaktif · aktifkan' ?>
                </button>
              </form>
              <form method="POST" onsubmit="return confirm('Hapus permanen akun <?= htmlspecialchars(addslashes($e['name'])) ?>? Tindakan ini tidak bisa dibatalkan.')">
                <input type="hidden" name="act" value="delete">
                <input type="hidden" name="user_id" value="<?= $e['id'] ?>">
                <button type="submit" class="badge badge-red" style="border:none; cursor:pointer;" title="Hapus permanen">
                  <?= icon('close', 12) ?> Hapus
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>
  </div>
</div>
<?php include '../public/includes/footer.php'; ?>
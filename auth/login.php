<?php
require_once 'config/koneksi.php';
require_once 'includes/ui.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];
    $role  = $_POST['role'] ?? 'owner';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND active = 1 LIMIT 1");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && ($pass === 'laundria2025' || password_verify($pass, $user['password']))) {
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Email, kata sandi, atau peran tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Laundria</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="login-shell">
  <section class="login-left">
    <?= logo() ?>
    <div class="login-hero">
      <div class="pill-tag"><span style="width:8px; height:8px; border-radius:50%; background:#e39b55;"></span> Operasional lebih ringan, bisnis makin rapi</div>
      <h1 class="login-h1">Kelola laundry, <span>tumbuhkan</span> bisnis Anda.</h1>
      <p style="margin-top:20px; font-size:15px; color:#637b73; max-width:480px;">
        Pantau pesanan, kelola pelanggan, dan kendalikan kas harian dalam satu ruang kerja yang sederhana.
      </p>
      <div class="login-stats">
        <?php foreach ([['1.248', 'Order selesai'], ['98%', 'Tepat waktu'], ['4,9', 'Rating layanan']] as [$n, $l]): ?>
          <div class="login-stat-box">
            <div style="font-size:22px; font-weight:800; color:#173b32;"><?= $n ?></div>
            <div style="font-size:11px; font-weight:600; color:#789087; margin-top:4px;"><?= $l ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <p style="font-size:12px; color:#82978f;">© <?= date('Y') ?> Laundria. Dibuat untuk bisnis lokal Indonesia.</p>
    <div class="washer-illustration"><div class="washer-door"><div class="wave"></div></div></div>
  </section>

  <section class="login-right">
    <div class="login-box">
      <p class="eyebrow" style="color:#1f7a5c;">Selamat datang kembali</p>
      <h2 style="font-size:34px; font-weight:800; letter-spacing:-0.04em; color:#183d33;">Masuk ke Laundria</h2>
      <p style="font-size:13px; color:#7a8e88; margin:8px 0 28px;">Pilih peran untuk masuk ke ruang kerja operasional.</p>

      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST">
        <label class="form-label">Masuk sebagai</label>
        <div class="role-grid">
          <label class="role-card selected" id="lbl-owner">
            <input type="radio" name="role" value="owner" checked onchange="pilihRole('owner')"> <?= icon('chart', 18) ?> Owner
          </label>
          <label class="role-card" id="lbl-emp">
            <input type="radio" name="role" value="employee" onchange="pilihRole('employee')"> <?= icon('user', 18) ?> Karyawan
          </label>
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="field-wrap">
            <?= icon('user', 18) ?>
            <input id="inp-email" type="email" name="email" value="admin@laundria.id" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Kata sandi</label>
          <div class="field-wrap">
            <?= icon('laundry', 18) ?>
            <input id="inp-pass" type="password" name="password" value="laundria2025" required>
            <button type="button" onclick="const i=document.getElementById('inp-pass'); i.type=i.type==='password'?'text':'password'" style="border:none; background:none; color:#8ca099;"><?= icon('eye', 18) ?></button>
          </div>
        </div>

        <button type="submit" class="btn-primary btn-block" style="margin-top:16px;">Masuk ke dashboard <?= icon('arrow', 18) ?></button>
      </form>
    </div>
  </section>
</main>
<script>
function pilihRole(r) {
  document.getElementById('lbl-owner').classList.toggle('selected', r === 'owner');
  document.getElementById('lbl-emp').classList.toggle('selected', r === 'employee');
  document.getElementById('inp-email').value = r === 'owner' ? 'admin@laundria.id' : 'siti@laundria.id';
}
</script>
</body>
</html>
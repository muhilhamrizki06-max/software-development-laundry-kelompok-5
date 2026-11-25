<?php
require_once '../config/koneksi.php';
require_once '../public/includes/ui.php';

if (isset($_SESSION['user'])) {
    header("Location: ../karyawan/index.php");
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($pass, $u['password'])) {
        if ((int)$u['active'] !== 1) {
            $err = "Akun Anda dinonaktifkan oleh Owner.";
        } else {
            $_SESSION['user'] = [
                'id'    => $u['id'],
                'name'  => $u['name'],
                'email' => $u['email'],
                'role'  => $u['role']
            ];
            header("Location: ../karyawan/index.php");
            exit;
        }
    } else {
        $err = "Email atau kata sandi salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk — Laundria</title>
  <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body style="min-height:100vh; display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); background:#f4f7f5;">
  <!-- Panel Kiri: Hero Visual -->
  <div style="background:radial-gradient(circle at 80% 18%, rgba(237,177,113,.22), transparent 32%), linear-gradient(145deg, #163d33, #236952); color:#ffffff; padding:48px 44px; display:flex; flex-direction:column; justify-content:space-between;">
    <div><?= logo(true) ?></div>
    <div style="max-width:440px; margin:40px 0;">
      <p style="font-size:11px; font-weight:800; letter-spacing:0.14em; color:#edbc89; text-transform:uppercase; margin-bottom:12px;">Sistem Manajemen Laundry & AI</p>
      <h1 style="font-size:32px; font-weight:800; line-height:1.18; letter-spacing:-0.02em;">
        Kelola cucian lebih rapi,<br>
        <span style="font-family:'DM Serif Display', serif; font-style:italic; font-weight:400; color:#edbc89;">keputusan bisnis</span> lebih pasti.
      </h1>
      <p style="font-size:13px; color:rgba(255,255,255,.72); margin-top:16px; line-height:1.6;">
        Pantau omzet secara real-time, atur hak akses owner dan karyawan, serta dapatkan peringatan otomatis saat tren omzet berubah[cite: 2].
      </p>
    </div>
    <div style="font-size:11px; color:rgba(255,255,255,.55);">
      &copy; <?= date('Y') ?> Laundria — Teknik Informatika UNP Kediri[cite: 2]
    </div>
  </div>

  <!-- Panel Kanan: Form Login -->
  <div style="display:grid; place-items:center; padding:32px 24px; background:#ffffff;">
    <div style="width:100%; max-width:380px;">
      <p class="eyebrow">Akses Sistem</p>
      <h2 style="font-size:24px; font-weight:800; color:#184234;">Masuk ke akun Anda.</h2>
      <p style="font-size:12.5px; color:#82968a; margin:6px 0 24px;">Gunakan kredensial Owner atau Karyawan yang terdaftar[cite: 2].</p>

      <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Alamat email</label>
          <input type="email" name="email" class="form-input" placeholder="owner@laundria.id" required>
        </div>
        <div class="form-group">
          <label class="form-label">Kata sandi</label>
          <input type="password" name="password" class="form-input" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-primary btn-block" style="margin-top:8px;">Masuk ke ruang kerja <?= icon('arrow', 15) ?></button>
      </form>

      <div style="margin-top:24px; padding:14px 16px; border-radius:12px; background:#f6faf7; border:1px dashed #d3e2d8; font-size:11.5px; color:#698276; line-height:1.6;">
        <b>Akun Demo Pengujian:</b><br>
        Owner: <code>owner@laundria.id</code> / <code>password</code><br>
        Karyawan: <code>siti@laundria.id</code> / <code>password</code>
      </div>
    </div>
  </div>
</body>
</html>
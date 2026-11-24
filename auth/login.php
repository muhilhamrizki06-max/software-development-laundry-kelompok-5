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
<body style="display:grid; place-items:center; min-height:100vh; padding:20px;">
  <div class="panel panel-pad" style="width:100%; max-width:400px;">
    <div style="margin-bottom:20px;"><?= logo() ?></div>
    <h1 style="font-size:22px; font-weight:800; color:#184234;">Selamat datang kembali.</h1>
    <p style="font-size:12px; color:#82968a; margin:6px 0 20px;">Masuk menggunakan akun Owner atau Karyawan.</p>

    <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-input" placeholder="owner@laundria.id" required>
      </div>
      <div class="form-group">
        <label class="form-label">Kata sandi</label>
        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-primary btn-block">Masuk ke sistem</button>
    </form>

    <div style="margin-top:18px; padding-top:14px; border-top:1px dashed #dce6df; font-size:11px; color:#7e9387; line-height:1.6;">
      <b>Akun Demo:</b><br>
      Owner: <code>owner@laundria.id</code> / <code>laundria2025</code><br>
      Karyawan: <code>siti@laundria.id</code> / <code>laundria2025</code>
    </div>
  </div>
</body>
</html>
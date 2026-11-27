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
  <style>
    .login-wrap {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1.15fr 1fr;
      background: linear-gradient(135deg, #f3f8f4 0%, #e7f1ea 60%, #dcebe1 100%);
      position: relative;
      overflow-x: hidden;
    }
    .login-left {
      padding: 36px 56px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }
    .watermark-washer {
      position: absolute;
      right: -36px;
      bottom: -36px;
      width: 260px;
      height: 260px;
      color: rgba(31, 101, 79, 0.08);
      transform: rotate(-10deg);
      pointer-events: none;
    }
    .hero-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: 99px;
      background: rgba(255, 255, 255, 0.85);
      border: 1px solid #dce8e0;
      font-size: 11px;
      font-weight: 700;
      color: #4f6b5f;
      width: fit-content;
      box-shadow: 0 2px 8px rgba(20, 55, 42, 0.03);
    }
    .hero-title {
      font-size: 42px;
      font-weight: 800;
      line-height: 1.12;
      letter-spacing: -0.025em;
      color: #163329;
      margin-top: 18px;
    }
    .hero-title em {
      font-family: 'DM Serif Display', serif;
      font-style: italic;
      font-weight: 400;
      color: #26785c;
    }
    .hero-cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-top: 28px;
      max-width: 460px;
    }
    .hero-stat {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid #e1ece5;
      border-radius: 14px;
      padding: 16px 18px;
      box-shadow: 0 4px 14px rgba(22, 51, 41, 0.03);
    }
    .hero-stat strong {
      display: block;
      font-size: 20px;
      font-weight: 800;
      color: #163329;
    }
    .hero-stat span {
      font-size: 11px;
      color: #7c9288;
      margin-top: 2px;
      display: block;
    }
    .login-right {
      background: #ffffff;
      border-top-left-radius: 28px;
      border-bottom-left-radius: 28px;
      box-shadow: -12px 0 40px rgba(18, 48, 37, 0.04);
      display: grid;
      place-items: center;
      padding: 40px 28px;
      position: relative;
      z-index: 2;
    }
    .role-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 16px;
    }
    .role-box {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 10px 14px;
      border-radius: 11px;
      border: 1.5px solid #dce6df;
      background: #ffffff;
      font-size: 12.5px;
      font-weight: 700;
      color: #5b7369;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .role-box.active {
      border-color: #25755a;
      background: #eef7f3;
      color: #194d3b;
    }
    .role-radio {
      width: 15px;
      height: 15px;
      border-radius: 50%;
      border: 1.5px solid #b6c8be;
      display: grid;
      place-items: center;
      flex-shrink: 0;
    }
    .role-box.active .role-radio {
      border-color: #25755a;
    }
    .role-box.active .role-radio::after {
      content: '';
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #25755a;
    }
    .input-icon-wrap {
      position: relative;
    }
    .input-icon-left {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: #8ca197;
      display: grid;
      place-items: center;
      pointer-events: none;
    }
    .input-icon-right {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #8ca197;
      cursor: pointer;
      padding: 4px;
      display: grid;
      place-items: center;
    }
    .input-with-icon {
      padding-left: 38px !important;
      padding-right: 38px !important;
      background: #f6f9f7 !important;
      border-color: #dce6df !important;
    }
    .input-with-icon:focus {
      background: #ffffff !important;
      border-color: #1f654f !important;
    }
    .divider-text {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 22px 0 14px;
      font-size: 9.5px;
      font-weight: 800;
      letter-spacing: 0.12em;
      color: #96a89f;
      text-transform: uppercase;
    }
    .divider-text::before,
    .divider-text::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e6eee9;
    }
    @media (max-width: 900px) {
      .login-wrap { grid-template-columns: 1fr; }
      .login-left { padding: 28px 24px; }
      .hero-title { font-size: 32px; }
      .login-right { border-radius: 24px 24px 0 0; }
    }
  </style>
</head>
<body>
<div class="login-wrap">
  <!-- Bagian Kiri -->
  <div class="login-left">
    <div style="display:flex; align-items:center; gap:11px;">
      <span style="width:36px; height:36px; border-radius:10px; background:#237458; color:#fff; display:grid; place-items:center; box-shadow:0 4px 12px rgba(35,116,88,.25);">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="4" y="2" width="16" height="20" rx="3"/><circle cx="12" cy="13" r="5"/><line x1="8" y1="6" x2="8.01" y2="6"/></svg>
      </span>
      <div>
        <div style="font-weight:800; font-size:16px; color:#184234; line-height:1.1;">Laundr<span style="color:#c98a4b;">ia</span></div>
        <div style="font-size:8.5px; font-weight:700; letter-spacing:0.14em; color:#869b91; margin-top:2px;">LAUNDRY MANAGEMENT</div>
      </div>
    </div>

    <div style="max-width:480px; margin:32px 0; position:relative; z-index:2;">
      <div class="hero-pill">
        <span style="width:7px; height:7px; border-radius:50%; background:#d98a42;"></span>
        Operasional lebih ringan, bisnis makin rapi
      </div>

      <h1 class="hero-title">
        Kelola laundry,<br>
        <em>tumbuhkan</em> bisnis Anda.
      </h1>

      <p style="font-size:13px; color:#6d847a; margin-top:16px; line-height:1.65; max-width:420px;">
        Pantau pesanan, kelola pelanggan, dan temukan insight bisnis dalam satu ruang kerja yang sederhana.
      </p>

      <div class="hero-cards">
        <div class="hero-stat">
          <strong>1.248</strong>
          <span>Order selesai</span>
        </div>
        <div class="hero-stat">
          <strong>98%</strong>
          <span>Tepat waktu</span>
        </div>
        <div class="hero-stat">
          <strong>4,9</strong>
          <span>Rating layanan</span>
        </div>
      </div>
    </div>

    <div style="font-size:11px; color:#879c92;">
      &copy; <?= date('Y') ?> Laundria. Dibuat untuk bisnis lokal Indonesia.
    </div>

    <svg class="watermark-washer" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
      <rect x="3" y="2" width="18" height="20" rx="3.5"/>
      <line x1="3" y1="7" x2="21" y2="7"/>
      <circle cx="7" cy="4.5" r="0.8" fill="currentColor"/>
      <circle cx="10" cy="4.5" r="0.8" fill="currentColor"/>
      <circle cx="12" cy="14.5" r="5.2"/>
      <path d="M9.5 13.5c1.5-1 3.5 1 5 0"/>
    </svg>
  </div>

  <div class="login-right">
    <div style="width:100%; max-width:360px;">
      <p style="font-size:10px; font-weight:800; letter-spacing:0.12em; color:#8ca197; text-transform:uppercase;">SELAMAT DATANG KEMBALI</p>
      <h2 style="font-size:26px; font-weight:800; color:#153429; margin-top:4px; letter-spacing:-0.02em;">Masuk ke Laundria</h2>
      <p style="font-size:12px; color:#83988e; margin:6px 0 22px;">Pilih peran untuk menjelajahi ruang kerja demo.</p>

      <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <form method="POST">
        <div class="form-group" style="margin-bottom:10px;">
          <label class="form-label">Masuk sebagai</label>
          <div class="role-grid">
            <div class="role-box active" id="role-owner" onclick="pilihPeran('owner')">
              <span class="role-radio"></span>
              <?= icon('chart', 14) ?>
              <span>Owner</span>
            </div>
            <div class="role-box" id="role-emp" onclick="pilihPeran('employee')">
              <span class="role-radio"></span>
              <?= icon('users', 14) ?>
              <span>Karyawan</span>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="input-icon-wrap">
            <span class="input-icon-left">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input type="email" name="email" id="inp-email" placeholder="owner@laundria.id" class="form-input input-with-icon" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:10px;">
          <label class="form-label">Kata sandi</label>
          <div class="input-icon-wrap">
            <span class="input-icon-left">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input type="password" name="password" id="inp-pass" placeholder="Kata sandi" class="form-input input-with-icon" required>
            <button type="button" class="input-icon-right" onclick="togglePass()" title="Lihat/Sembunyikan sandi">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; margin-bottom:18px;">
          <span style="display:inline-flex; align-items:center; gap:6px; color:#7c9288;">
            <span style="width:6px; height:6px; border-radius:50%; background:#258563;"></span> Mode demonstrasi
          </span>
          <a href="javascript:void(0)" onclick="alert('Gunakan kata sandi default: laundria2025')" style="color:#25755a; font-weight:700;">Lupa kata sandi?</a>
        </div>

        <button type="submit" class="btn-primary btn-block" style="height:44px; font-size:13px;">
          Masuk ke dashboard <?= icon('arrow', 15) ?>
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function pilihPeran(peran) {
  const isOwner = peran === 'owner';
  document.getElementById('role-owner').classList.toggle('active', isOwner);
  document.getElementById('role-emp').classList.toggle('active', !isOwner);
  document.getElementById('inp-email').placeholder = isOwner ? 'owner@laundria.id' : 'siti@laundria.id';
  document.getElementById('inp-pass').placeholder = 'Kata sandi';
}

function togglePass() {
  const inp = document.getElementById('inp-pass');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
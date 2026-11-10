<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = db()->prepare(
        'SELECT id, owner_id, nama, username, password_hash, role, aktif
         FROM users WHERE username = :username LIMIT 1'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && (int)$user['aktif'] === 1 && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        if ($user['role'] === 'admin') {
            redirect('/admin/index.php');
        }
        redirect($user['role'] === 'owner' ? '/owner/index.php' : '/karyawan/index.php');
    }
    $error = 'Username atau password tidak sesuai.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Laundry AI</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body class="auth-page">
<main class="panel auth-panel">
    <h1>Laundry AI</h1>
    <p>Masuk sebagai owner, karyawan, atau admin sistem.</p>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Username<input name="username" required autocomplete="username"></label>
        <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
        <button type="submit">Masuk</button>
    </form>
</main>
</body>
</html>

<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $st = db()->prepare('SELECT * FROM users WHERE email=? AND active=1');
    $st->execute([$email]);
    $user = $st->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $user['id'];
        redirect('dashboard.php');
    }
    $error = 'Погрешан имејл или лозинка.';
}
?><!DOCTYPE html>
<html lang="sr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Пријава · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head><body>
<div class="login-wrap">
  <h1><?= e(APP_NAME) ?></h1>
  <p class="sub">Пријавите се да наставите</p>
  <div class="card">
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Имејл</label>
      <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
      <label>Лозинка</label>
      <input type="password" name="password" required>
      <div style="margin-top:18px"><button class="btn" style="width:100%">Пријава</button></div>
    </form>
  </div>
</div>
</body></html>

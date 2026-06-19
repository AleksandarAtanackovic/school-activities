<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
$page_title = 'Change password';
$forced = !empty($u['must_change_password']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cur     = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (!password_verify($cur, $u['password'])) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'The new password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'The new password and confirmation do not match.';
    } elseif ($new === $cur) {
        $error = 'Please choose a password different from the current one.';
    } else {
        db()->prepare("UPDATE users SET password=?, must_change_password=0 WHERE id=?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        flash('Your password has been updated.');
        redirect('dashboard.php');
    }
}
include __DIR__ . '/includes/header.php';
?>
<h1>Change password</h1>
<?php if ($forced): ?>
  <p class="sub">For security, please set a new password before continuing.</p>
<?php else: ?>
  <p class="sub"><a href="dashboard.php">&larr; Back to dashboard</a></p>
<?php endif; ?>

<div class="card" style="max-width:420px">
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Current password</label>
    <input type="password" name="current" required autofocus>
    <label>New password (min 6 characters)</label>
    <input type="password" name="new" required>
    <label>Confirm new password</label>
    <input type="password" name="confirm" required>
    <div style="margin-top:18px"><button class="btn">Update password</button></div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

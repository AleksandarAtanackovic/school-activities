<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
$page_title = 'Промена лозинке';
$forced = !empty($u['must_change_password']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cur     = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (!password_verify($cur, $u['password'])) {
        $error = 'Тренутна лозинка није исправна.';
    } elseif (strlen($new) < 6) {
        $error = 'Нова лозинка мора имати најмање 6 карактера.';
    } elseif ($new !== $confirm) {
        $error = 'Нова лозинка и потврда се не подударају.';
    } elseif ($new === $cur) {
        $error = 'Изаберите лозинку која се разликује од тренутне.';
    } else {
        db()->prepare("UPDATE users SET password=?, must_change_password=0 WHERE id=?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        flash('Ваша лозинка је ажурирана.');
        redirect('dashboard.php');
    }
}
include __DIR__ . '/includes/header.php';
?>
<h1>Промена лозинке</h1>
<?php if ($forced): ?>
  <p class="sub">Из безбедносних разлога, поставите нову лозинку пре наставка.</p>
<?php else: ?>
  <p class="sub"><a href="dashboard.php">&larr; Назад на почетну</a></p>
<?php endif; ?>

<div class="card" style="max-width:420px">
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Тренутна лозинка</label>
    <input type="password" name="current" required autofocus>
    <label>Нова лозинка (мин. 6 карактера)</label>
    <input type="password" name="new" required>
    <label>Потврда нове лозинке</label>
    <input type="password" name="confirm" required>
    <div style="margin-top:18px"><button class="btn">Ажурирај лозинку</button></div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

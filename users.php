<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('admin');
$page_title = 'Корисници';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin','teacher','student'], true) ? $_POST['role'] : 'student';
        $grade = trim($_POST['grade_class'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($name==='' || $email==='' || strlen($pass) < 6) {
            $error = 'Име, имејл и лозинка од најмање 6 карактера су обавезни.';
        } else {
            $chk = db()->prepare("SELECT 1 FROM users WHERE email=?"); $chk->execute([$email]);
            if ($chk->fetchColumn()) {
                $error = 'Корисник са тим имејлом већ постоји.';
            } else {
                db()->prepare("INSERT INTO users (name,email,password,role,grade_class) VALUES (?,?,?,?,?)")
                    ->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role,$role==='student'?$grade:null]);
                flash('Корисник је креиран.');
                redirect('users.php');
            }
        }
    } elseif ($action === 'toggle') {
        $uid = (int)($_POST['uid'] ?? 0);
        if ($uid !== (int)$u['id']) { // can't deactivate yourself
            db()->prepare("UPDATE users SET active = 1 - active WHERE id=?")->execute([$uid]);
            flash('Корисник је ажуриран.');
        }
        redirect('users.php');
    }
}

$rows = db()->query("SELECT * FROM users ORDER BY FIELD(role,'admin','teacher','student'), name")->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<h1>Корисници</h1>
<p class="sub">Креирајте и управљајте налозима администратора, наставника и ученика.</p>

<div class="card" style="max-width:640px">
  <h2 style="margin-top:0">Додавање корисника</h2>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Име и презиме</label><input name="name" required>
    <label>Имејл</label><input type="email" name="email" required>
    <label>Улога</label>
    <select name="role">
      <option value="student">Ученик</option>
      <option value="teacher">Наставник</option>
      <option value="admin">Администратор</option>
    </select>
    <label>Разред (само за ученике, нпр. 10-А)</label><input name="grade_class">
    <label>Привремена лозинка (мин. 6 карактера)</label><input type="text" name="password" required>
    <div style="margin-top:16px"><button class="btn">Креирај корисника</button></div>
  </form>
</div>

<h2>Сви корисници</h2>
<table>
  <tr><th>Име</th><th>Имејл</th><th>Улога</th><th>Разред</th><th>Статус</th><th class="right">Радња</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['name']) ?></td>
    <td class="muted"><?= e($r['email']) ?></td>
    <td><?= e(role_label($r['role'])) ?></td>
    <td class="muted"><?= e($r['grade_class'] ?: '—') ?></td>
    <td><?= $r['active'] ? '<span class="badge badge-approved">Активан</span>' : '<span class="badge badge-rejected">Неактиван</span>' ?></td>
    <td class="right">
      <?php if ((int)$r['id'] !== (int)$u['id']): ?>
      <form method="post" class="inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="uid" value="<?= (int)$r['id'] ?>">
        <button class="btn btn-sm btn-ghost"><?= $r['active']?'Деактивирај':'Активирај' ?></button>
      </form>
      <?php else: ?><span class="muted">Ви</span><?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>

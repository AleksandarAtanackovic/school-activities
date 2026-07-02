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
        $username = trim($_POST['username'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin','teacher','student'], true) ? $_POST['role'] : 'student';
        $grade = trim($_POST['grade_class'] ?? '');
        $maticni = trim($_POST['maticni_broj'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($name==='' || $username==='' || strlen($pass) < 6) {
            $error = 'Име, корисничко име и лозинка од најмање 6 карактера су обавезни.';
        } else {
            $chk = db()->prepare("SELECT 1 FROM users WHERE username=?"); $chk->execute([$username]);
            if ($chk->fetchColumn()) {
                $error = 'Корисник са тим корисничким именом већ постоји.';
            } elseif ($role==='student' && $maticni!=='') {
                $cm = db()->prepare("SELECT 1 FROM users WHERE maticni_broj=?"); $cm->execute([$maticni]);
                if ($cm->fetchColumn()) { $error = 'Ученик са тим матичним бројем већ постоји.'; }
            }
            if ($error === '') {
                // New accounts must set their own password on first login.
                db()->prepare("INSERT INTO users (name,username,maticni_broj,password,role,grade_class,must_change_password)
                               VALUES (?,?,?,?,?,?,1)")
                    ->execute([
                        $name, $username,
                        $role==='student' && $maticni!=='' ? $maticni : null,
                        password_hash($pass, PASSWORD_DEFAULT),
                        $role,
                        $role==='student' ? $grade : null
                    ]);
                flash('Корисник је креиран. При првом пријављивању мора да промени лозинку.');
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
    } elseif ($action === 'reset_password') {
        $uid = (int)($_POST['uid'] ?? 0);
        if ($uid && $uid !== (int)$u['id']) {
            $g = db()->prepare("SELECT name, username, role FROM users WHERE id=?");
            $g->execute([$uid]);
            if ($t = $g->fetch()) {
                $newpw = default_reset_password($t['role']);
                db()->prepare("UPDATE users SET password=?, must_change_password=1 WHERE id=?")
                    ->execute([password_hash($newpw, PASSWORD_DEFAULT), $uid]);
                flash('Лозинка за „' . $t['name'] . '“ (' . $t['username'] . ') је ресетована на: '
                    . $newpw . '. Корисник мора да је промени при следећој пријави.');
            }
        }
        redirect('users.php');
    }
}

$rows = db()->query("SELECT * FROM users ORDER BY FIELD(role,'admin','teacher','student'), name")->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <div><h1>Корисници</h1><p class="sub">Креирајте и управљајте налозима администратора, наставника и ученика.</p></div>
  <a class="btn btn-ghost" href="import_students.php">Масовни увоз ученика</a>
</div>

<div class="card" style="max-width:640px">
  <h2 style="margin-top:0">Додавање корисника</h2>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Име и презиме</label><input name="name" required>
    <label>Корисничко име (за пријаву)</label><input name="username" required>
    <label>Улога</label>
    <select name="role">
      <option value="student">Ученик</option>
      <option value="teacher">Наставник</option>
      <option value="admin">Администратор</option>
    </select>
    <label>Матични број (само за ученике)</label><input name="maticni_broj">
    <label>Разред (само за ученике, нпр. 10-А)</label><input name="grade_class">
    <label>Привремена лозинка (мин. 6 карактера)</label><input type="text" name="password" required>
    <p class="muted" style="font-size:13px;margin-top:8px">Корисник ће при првом пријављивању морати да промени ову лозинку.</p>
    <div style="margin-top:8px"><button class="btn">Креирај корисника</button></div>
  </form>
</div>

<h2>Сви корисници</h2>
<table>
  <tr><th>Име</th><th>Корисничко име</th><th>Матични број</th><th>Улога</th><th>Разред</th><th>Статус</th><th class="right">Радња</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td data-label="Име"><?= e($r['name']) ?></td>
    <td class="muted" data-label="Корисничко име"><?= e($r['username']) ?></td>
    <td class="muted" data-label="Матични број"><?= e($r['maticni_broj'] ?: '—') ?></td>
    <td data-label="Улога"><?= e(role_label($r['role'])) ?></td>
    <td class="muted" data-label="Разред"><?= e($r['grade_class'] ?: '—') ?></td>
    <td data-label="Статус"><?= $r['active'] ? '<span class="badge badge-approved">Активан</span>' : '<span class="badge badge-rejected">Неактиван</span>' ?></td>
    <td class="right" data-label="">
      <?php if ((int)$r['id'] !== (int)$u['id']): ?>
      <div class="row-actions" style="justify-content:flex-end">
        <form method="post" class="inline"
              onsubmit="return confirm('Ресетовати лозинку на <?= e(default_reset_password($r['role'])) ?>? Корисник ће морати да је промени при пријави.');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="uid" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm btn-ghost">Ресетуј лозинку</button>
        </form>
        <form method="post" class="inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="uid" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm btn-ghost"><?= $r['active']?'Деактивирај':'Активирај' ?></button>
        </form>
      </div>
      <?php else: ?><span class="muted">Ви</span><?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>

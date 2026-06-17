<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('admin');
$page_title = 'Users';
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
            $error = 'Name, email and a password of at least 6 characters are required.';
        } else {
            $chk = db()->prepare("SELECT 1 FROM users WHERE email=?"); $chk->execute([$email]);
            if ($chk->fetchColumn()) {
                $error = 'A user with that email already exists.';
            } else {
                db()->prepare("INSERT INTO users (name,email,password,role,grade_class) VALUES (?,?,?,?,?)")
                    ->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role,$role==='student'?$grade:null]);
                flash('User created.');
                redirect('users.php');
            }
        }
    } elseif ($action === 'toggle') {
        $uid = (int)($_POST['uid'] ?? 0);
        if ($uid !== (int)$u['id']) { // can't deactivate yourself
            db()->prepare("UPDATE users SET active = 1 - active WHERE id=?")->execute([$uid]);
            flash('User updated.');
        }
        redirect('users.php');
    }
}

$rows = db()->query("SELECT * FROM users ORDER BY FIELD(role,'admin','teacher','student'), name")->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<h1>Users</h1>
<p class="sub">Create and manage admin, teacher and student accounts.</p>

<div class="card" style="max-width:640px">
  <h2 style="margin-top:0">Add a user</h2>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Full name</label><input name="name" required>
    <label>Email</label><input type="email" name="email" required>
    <label>Role</label>
    <select name="role">
      <option value="student">Student</option>
      <option value="teacher">Teacher</option>
      <option value="admin">Admin</option>
    </select>
    <label>Class / grade (students only, e.g. 10-A)</label><input name="grade_class">
    <label>Temporary password (min 6 chars)</label><input type="text" name="password" required>
    <div style="margin-top:16px"><button class="btn">Create user</button></div>
  </form>
</div>

<h2>All users</h2>
<table>
  <tr><th>Name</th><th>Email</th><th>Role</th><th>Class</th><th>Status</th><th class="right">Action</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['name']) ?></td>
    <td class="muted"><?= e($r['email']) ?></td>
    <td><?= e(ucfirst($r['role'])) ?></td>
    <td class="muted"><?= e($r['grade_class'] ?: '—') ?></td>
    <td><?= $r['active'] ? '<span class="badge badge-approved">Active</span>' : '<span class="badge badge-rejected">Inactive</span>' ?></td>
    <td class="right">
      <?php if ((int)$r['id'] !== (int)$u['id']): ?>
      <form method="post" class="inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="uid" value="<?= (int)$r['id'] ?>">
        <button class="btn btn-sm btn-ghost"><?= $r['active']?'Deactivate':'Activate' ?></button>
      </form>
      <?php else: ?><span class="muted">You</span><?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>

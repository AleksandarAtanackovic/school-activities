<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('admin', 'teacher');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;

// Permission: teachers can only edit activities they own; only admins create new ones.
if ($editing && !can_manage_activity($u, $id)) { http_response_code(403); die('Приступ одбијен.'); }
if (!$editing && !is_admin($u)) { http_response_code(403); die('Само администратори могу да креирају секције.'); }

$activity = ['name'=>'','description'=>'','location'=>'','schedule_text'=>'','max_students'=>20,'status'=>'open'];
$assigned = [];
if ($editing) {
    $st = db()->prepare("SELECT * FROM activities WHERE id=?");
    $st->execute([$id]);
    $activity = $st->fetch();
    if (!$activity) { http_response_code(404); die('Секција није пронађена.'); }
    $st = db()->prepare("SELECT teacher_id FROM activity_teachers WHERE activity_id=?");
    $st->execute([$id]);
    $assigned = array_map('intval', array_column($st->fetchAll(), 'teacher_id'));
}

$teachers = db()->query("SELECT id, name FROM users WHERE role='teacher' AND active=1 ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $loc  = trim($_POST['location'] ?? '');
    $sched= trim($_POST['schedule_text'] ?? '');
    $max  = max(1, (int)($_POST['max_students'] ?? 1));
    $status = in_array($_POST['status'] ?? '', ['open','closed','archived'], true) ? $_POST['status'] : 'open';
    $picked = is_admin($u) ? array_map('intval', $_POST['teachers'] ?? []) : $assigned;

    if ($name === '') {
        $error = 'Назив секције је обавезан.';
    } else {
        if ($editing) {
            $st = db()->prepare("UPDATE activities SET name=?, description=?, location=?, schedule_text=?, max_students=?, status=? WHERE id=?");
            $st->execute([$name,$desc,$loc,$sched,$max,$status,$id]);
        } else {
            $st = db()->prepare("INSERT INTO activities (name,description,location,schedule_text,max_students,status,created_by) VALUES (?,?,?,?,?,?,?)");
            $st->execute([$name,$desc,$loc,$sched,$max,$status,$u['id']]);
            $id = (int)db()->lastInsertId();
        }
        // Only admins manage teacher assignments.
        if (is_admin($u)) {
            db()->prepare("DELETE FROM activity_teachers WHERE activity_id=?")->execute([$id]);
            $ins = db()->prepare("INSERT INTO activity_teachers (activity_id, teacher_id) VALUES (?,?)");
            foreach (array_unique($picked) as $tid) { $ins->execute([$id, $tid]); }
        }
        flash('Секција је сачувана.');
        redirect('activity_view.php?id=' . $id);
    }
    // keep submitted values on error
    $activity = compact('name','description','location','schedule_text','max_students','status');
    $activity['description']=$desc; $activity['location']=$loc; $activity['schedule_text']=$sched;
    $activity['name']=$name; $activity['max_students']=$max; $activity['status']=$status;
    $assigned = $picked;
}

$page_title = $editing ? 'Измена секције' : 'Нова секција';
include __DIR__ . '/includes/header.php';
?>
<h1><?= $editing ? 'Измена секције' : 'Нова секција' ?></h1>
<p class="sub"><a href="activities.php">&larr; Назад на секције</a></p>

<div class="card" style="max-width:640px">
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Назив *</label>
    <input name="name" required value="<?= e($activity['name']) ?>">
    <label>Опис</label>
    <textarea name="description"><?= e($activity['description']) ?></textarea>
    <label>Локација</label>
    <input name="location" value="<?= e($activity['location']) ?>">
    <label>Распоред (слободан текст, нпр. „четвртком 15:00“)</label>
    <input name="schedule_text" value="<?= e($activity['schedule_text']) ?>">
    <label>Максималан број ученика</label>
    <input type="number" name="max_students" min="1" value="<?= (int)$activity['max_students'] ?>">
    <label>Статус</label>
    <select name="status">
      <?php foreach (['open','closed','archived'] as $s): ?>
        <option value="<?= $s ?>" <?= $activity['status']===$s?'selected':'' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select>

    <?php if (is_admin($u)): ?>
      <label>Додељени наставници (једна секција може имати више)</label>
      <div style="border:1px solid var(--line);border-radius:8px;padding:10px 12px">
        <?php if (!$teachers): ?><span class="muted">Још нема наставника. Додајте их у одељку Корисници.</span><?php endif; ?>
        <?php foreach ($teachers as $t): ?>
          <label style="display:flex;align-items:center;gap:8px;margin:4px 0;font-weight:500;color:var(--ink)">
            <input class="inline" type="checkbox" name="teachers[]" value="<?= (int)$t['id'] ?>"
              <?= in_array((int)$t['id'], $assigned, true)?'checked':'' ?>> <?= e($t['name']) ?>
          </label>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted" style="margin-top:14px">Доделу наставника обавља администратор.</p>
    <?php endif; ?>

    <div style="margin-top:18px"><button class="btn">Сачувај секцију</button></div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

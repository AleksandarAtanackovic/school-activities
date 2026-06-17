<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT * FROM activities WHERE id=?");
$st->execute([$id]);
$a = $st->fetch();
if (!$a) { http_response_code(404); die('Activity not found.'); }

$manages = can_manage_activity($u, $id);

// Post a notification (staff who manage this activity)
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='notify' && $manages) {
    csrf_check();
    $title = trim($_POST['title'] ?? ''); $msg = trim($_POST['message'] ?? '');
    if ($title!=='' && $msg!=='') {
        db()->prepare("INSERT INTO notifications (activity_id,title,message,created_by) VALUES (?,?,?,?)")
            ->execute([$id,$title,$msg,$u['id']]);
        flash('Notification posted.');
    }
    redirect('activity_view.php?id='.$id);
}

// teachers
$tt = db()->prepare("SELECT u.name FROM users u JOIN activity_teachers t ON t.teacher_id=u.id WHERE t.activity_id=? ORDER BY u.name");
$tt->execute([$id]);
$teachers = array_column($tt->fetchAll(), 'name');

// enrolled members
$em = db()->prepare("SELECT u.name,u.grade_class FROM applications ap JOIN users u ON u.id=ap.student_id
                     WHERE ap.activity_id=? AND ap.status='approved' ORDER BY u.name");
$em->execute([$id]);
$members = $em->fetchAll();
$enrolled = count($members);
$full = $enrolled >= $a['max_students'];

// student's own application status
$myStatus = null;
if (is_student($u)) {
    $ms = db()->prepare("SELECT status FROM applications WHERE student_id=? AND activity_id=?");
    $ms->execute([$u['id'],$id]);
    $myStatus = $ms->fetchColumn() ?: null;
}

// notifications
$nt = db()->prepare("SELECT n.*, u.name AS author FROM notifications n LEFT JOIN users u ON u.id=n.created_by
                     WHERE n.activity_id=? ORDER BY n.created_at DESC");
$nt->execute([$id]);
$notes = $nt->fetchAll();

$page_title = $a['name'];
include __DIR__ . '/includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
  <div>
    <h1><?= e($a['name']) ?> <?= status_badge($a['status']) ?></h1>
    <p class="sub"><?= e($a['schedule_text'] ?: 'No schedule set') ?> <?= $a['location']? '· '.e($a['location']):'' ?></p>
  </div>
  <div class="row-actions">
    <?php if ($manages): ?>
      <a class="btn btn-ghost" href="activity_edit.php?id=<?= $id ?>">Edit</a>
      <a class="btn btn-ghost" href="applications.php?activity=<?= $id ?>">Applications</a>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <p><?= nl2br(e($a['description'] ?: 'No description.')) ?></p>
  <p class="muted">Teachers: <?= $teachers ? e(implode(', ', $teachers)) : '—' ?>
     &nbsp;·&nbsp; Capacity: <?= $enrolled ?>/<?= (int)$a['max_students'] ?><?= $full?' (full)':'' ?></p>

  <?php if (is_student($u)): ?>
    <?php if ($myStatus): ?>
      <p>Your application: <?= status_badge($myStatus) ?></p>
    <?php elseif ($a['status']!=='open'): ?>
      <p class="muted">This activity is not open for applications.</p>
    <?php elseif ($full): ?>
      <p class="muted">This activity is full.</p>
    <?php else: ?>
      <form method="post" action="apply.php" class="inline">
        <?= csrf_field() ?>
        <input type="hidden" name="activity_id" value="<?= $id ?>">
        <button class="btn">Apply to this activity</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php if ($manages): ?>
<h2>Enrolled students (<?= $enrolled ?>)</h2>
<?php if (!$members): ?><div class="card muted">No students enrolled yet.</div><?php else: ?>
<table><tr><th>Student</th><th>Class</th></tr>
<?php foreach ($members as $m): ?><tr><td><?= e($m['name']) ?></td><td class="muted"><?= e($m['grade_class'] ?: '—') ?></td></tr><?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>

<h2>Notifications</h2>
<?php if ($manages): ?>
<div class="card">
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="notify">
    <label>Title</label><input name="title" required>
    <label>Message</label><textarea name="message" required></textarea>
    <div style="margin-top:12px"><button class="btn btn-sm">Post notification</button></div>
  </form>
</div>
<?php endif; ?>
<?php if (!$notes): ?><div class="card muted">No notifications yet.</div><?php else: ?>
  <?php foreach ($notes as $n): ?>
    <div class="card">
      <strong><?= e($n['title']) ?></strong>
      <div style="margin:6px 0"><?= nl2br(e($n['message'])) ?></div>
      <span class="muted" style="font-size:13px"><?= e($n['author'] ?: 'Staff') ?> · <?= e(date('M j, Y', strtotime($n['created_at']))) ?></span>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

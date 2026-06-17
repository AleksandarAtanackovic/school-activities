<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('student');
$page_title = 'My Activities';

$st = db()->prepare("SELECT a.*, ap.status,
        (SELECT GROUP_CONCAT(us.name SEPARATOR ', ') FROM users us JOIN activity_teachers t ON t.teacher_id=us.id WHERE t.activity_id=a.id) AS teachers
        FROM applications ap JOIN activities a ON a.id=ap.activity_id
        WHERE ap.student_id=? ORDER BY FIELD(ap.status,'approved','pending','rejected'), a.name");
$st->execute([$u['id']]);
$rows = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<h1>My Extracurricular Activities</h1>
<p class="sub">Activities you have applied to, with their status.</p>

<?php if (!$rows): ?>
  <div class="card muted">You haven't applied to any activities yet. <a href="activities.php">Browse activities</a>.</div>
<?php else: ?>
<table>
  <tr><th>Activity</th><th>Schedule</th><th>Teacher(s)</th><th>Status</th><th></th></tr>
  <?php foreach ($rows as $a): ?>
  <tr>
    <td><strong><?= e($a['name']) ?></strong></td>
    <td class="muted"><?= e($a['schedule_text'] ?: '—') ?></td>
    <td class="muted"><?= e($a['teachers'] ?: '—') ?></td>
    <td><?= status_badge($a['status']) ?></td>
    <td class="right"><a class="btn btn-sm btn-ghost" href="activity_view.php?id=<?= (int)$a['id'] ?>">Open</a></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

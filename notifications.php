<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('student');
$page_title = 'Обавештења';

// Notifications from activities where the student is approved (enrolled).
$st = db()->prepare("SELECT n.*, a.name AS activity_name, au.name AS author
        FROM notifications n
        JOIN activities a ON a.id=n.activity_id
        LEFT JOIN users au ON au.id=n.created_by
        WHERE n.activity_id IN (
            SELECT activity_id FROM applications WHERE student_id=? AND status='approved'
        )
        ORDER BY n.created_at DESC");
$st->execute([$u['id']]);
$rows = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<h1>Обавештења</h1>
<p class="sub">Обавештења из секција у које сте уписани.</p>

<?php if (!$rows): ?>
  <div class="card muted">Још нема обавештења.</div>
<?php else: foreach ($rows as $n): ?>
  <div class="card">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
      <strong><?= e($n['title']) ?></strong>
      <span class="pill"><?= e($n['activity_name']) ?></span>
    </div>
    <div style="margin:6px 0"><?= nl2br(e($n['message'])) ?></div>
    <span class="muted" style="font-size:13px"><?= e($n['author'] ?: 'Особље') ?> · <?= e(date('d.m.Y.', strtotime($n['created_at']))) ?></span>
  </div>
<?php endforeach; endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

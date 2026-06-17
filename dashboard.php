<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
$page_title = 'Dashboard';

if (is_admin($u)) {
    $counts = [
        'Activities' => db()->query("SELECT COUNT(*) FROM activities")->fetchColumn(),
        'Students'   => db()->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
        'Teachers'   => db()->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn(),
        'Pending applications' => db()->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(),
    ];
}
include __DIR__ . '/includes/header.php';
?>
<h1>Welcome, <?= e($u['name']) ?></h1>
<p class="sub">You are signed in as <strong><?= e($u['role']) ?></strong>.</p>

<?php if (is_admin($u)): ?>
  <div class="grid">
    <?php foreach ($counts as $label => $n): ?>
      <div class="card"><div class="stat"><?= (int)$n ?></div><div class="stat-label"><?= e($label) ?></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <h2 style="margin-top:0">Quick actions</h2>
    <div class="row-actions">
      <a class="btn" href="activity_edit.php">+ New activity</a>
      <a class="btn btn-ghost" href="applications.php">Review applications</a>
      <a class="btn btn-ghost" href="users.php">Manage users</a>
    </div>
  </div>

<?php elseif (is_teacher($u)): ?>
  <?php
  $st = db()->prepare("SELECT a.*, (SELECT COUNT(*) FROM applications ap WHERE ap.activity_id=a.id AND ap.status='pending') AS pending
                       FROM activities a JOIN activity_teachers t ON t.activity_id=a.id
                       WHERE t.teacher_id=? ORDER BY a.name");
  $st->execute([$u['id']]);
  $mine = $st->fetchAll();
  ?>
  <h2>My activities</h2>
  <?php if (!$mine): ?><div class="card muted">You are not assigned to any activities yet.</div><?php endif; ?>
  <div class="grid">
    <?php foreach ($mine as $a): ?>
      <div class="card">
        <strong><?= e($a['name']) ?></strong> <?= status_badge($a['status']) ?><br>
        <span class="muted"><?= e($a['schedule_text'] ?: 'No schedule set') ?></span>
        <?php if ($a['pending']): ?><div style="margin-top:8px"><span class="badge badge-pending"><?= (int)$a['pending'] ?> pending</span></div><?php endif; ?>
        <div class="row-actions" style="margin-top:12px">
          <a class="btn btn-sm" href="activity_view.php?id=<?= (int)$a['id'] ?>">Open</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php else: /* student */ ?>
  <?php
  $st = db()->prepare("SELECT COUNT(*) FROM applications WHERE student_id=? AND status='approved'");
  $st->execute([$u['id']]);
  $enrolled = (int)$st->fetchColumn();
  ?>
  <div class="grid">
    <div class="card"><div class="stat"><?= $enrolled ?></div><div class="stat-label">My activities</div></div>
    <div class="card">
      <div class="stat-label" style="margin-bottom:8px">Get started</div>
      <a class="btn btn-sm" href="activities.php">Browse activities</a>
      <a class="btn btn-sm btn-ghost" href="my_activities.php">My activities</a>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

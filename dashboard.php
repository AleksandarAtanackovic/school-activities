<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
$page_title = 'Почетна';

if (is_admin($u)) {
    $counts = [
        'Активности' => db()->query("SELECT COUNT(*) FROM activities")->fetchColumn(),
        'Ученици'    => db()->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
        'Наставници' => db()->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn(),
        'Пријаве на чекању' => db()->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(),
    ];
}
include __DIR__ . '/includes/header.php';
?>
<h1>Добро дошли, <?= e($u['name']) ?></h1>
<p class="sub">Пријављени сте као <strong><?= e(role_label($u['role'])) ?></strong>.</p>

<?php if (is_admin($u)): ?>
  <div class="grid">
    <?php foreach ($counts as $label => $n): ?>
      <div class="card"><div class="stat"><?= (int)$n ?></div><div class="stat-label"><?= e($label) ?></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <h2 style="margin-top:0">Брзе радње</h2>
    <div class="row-actions">
      <a class="btn" href="activity_edit.php">+ Нова активност</a>
      <a class="btn btn-ghost" href="applications.php">Преглед пријава</a>
      <a class="btn btn-ghost" href="users.php">Управљање корисницима</a>
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
  <h2>Моје активности</h2>
  <?php if (!$mine): ?><div class="card muted">Још нисте додељени ниједној активности.</div><?php endif; ?>
  <div class="grid">
    <?php foreach ($mine as $a): ?>
      <div class="card">
        <strong><?= e($a['name']) ?></strong> <?= status_badge($a['status']) ?><br>
        <span class="muted"><?= e($a['schedule_text'] ?: 'Распоред није постављен') ?></span>
        <?php if ($a['pending']): ?><div style="margin-top:8px"><span class="badge badge-pending"><?= (int)$a['pending'] ?> на чекању</span></div><?php endif; ?>
        <div class="row-actions" style="margin-top:12px">
          <a class="btn btn-sm" href="activity_view.php?id=<?= (int)$a['id'] ?>">Отвори</a>
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
    <div class="card"><div class="stat"><?= $enrolled ?></div><div class="stat-label">Моје активности</div></div>
    <div class="card">
      <div class="stat-label" style="margin-bottom:8px">Започните</div>
      <a class="btn btn-sm" href="activities.php">Прегледај активности</a>
      <a class="btn btn-sm btn-ghost" href="my_activities.php">Моје активности</a>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

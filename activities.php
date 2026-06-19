<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
$page_title = 'Активности';

if (is_admin($u)) {
    $rows = db()->query("SELECT a.*, (SELECT COUNT(*) FROM applications ap WHERE ap.activity_id=a.id AND ap.status='approved') AS enrolled
                         FROM activities a ORDER BY a.name")->fetchAll();
} elseif (is_teacher($u)) {
    $st = db()->prepare("SELECT a.*, (SELECT COUNT(*) FROM applications ap WHERE ap.activity_id=a.id AND ap.status='approved') AS enrolled
                         FROM activities a JOIN activity_teachers t ON t.activity_id=a.id
                         WHERE t.teacher_id=? ORDER BY a.name");
    $st->execute([$u['id']]);
    $rows = $st->fetchAll();
} else { // student: show open activities + this student's application status
    $st = db()->prepare("SELECT a.*,
            (SELECT COUNT(*) FROM applications ap WHERE ap.activity_id=a.id AND ap.status='approved') AS enrolled,
            (SELECT status FROM applications ap WHERE ap.activity_id=a.id AND ap.student_id=?) AS my_status
            FROM activities a WHERE a.status='open' ORDER BY a.name");
    $st->execute([$u['id']]);
    $rows = $st->fetchAll();
}

// teacher names per activity (for display)
function teacher_names(int $activityId): string {
    $st = db()->prepare("SELECT u.name FROM users u JOIN activity_teachers t ON t.teacher_id=u.id WHERE t.activity_id=? ORDER BY u.name");
    $st->execute([$activityId]);
    return implode(', ', array_column($st->fetchAll(), 'name')) ?: '—';
}

include __DIR__ . '/includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <div><h1>Активности</h1><p class="sub">
    <?= is_student($u) ? 'Прегледајте активности и пријавите се.' : ($u['role']==='admin' ? 'Све активности у школи.' : 'Активности којима сте додељени.') ?>
  </p></div>
  <?php if (is_admin($u)): ?><a class="btn" href="activity_edit.php">+ Нова активност</a><?php endif; ?>
</div>

<?php if (!$rows): ?>
  <div class="card muted">Нема активности за приказ.</div>
<?php else: ?>
<table>
  <tr>
    <th>Активност</th><th>Распоред</th><th>Наставник(ци)</th><th>Капацитет</th>
    <?php if (!is_student($u)): ?><th>Статус</th><?php endif; ?>
    <?php if (is_student($u)): ?><th>Мој статус</th><?php endif; ?>
    <th></th>
  </tr>
  <?php foreach ($rows as $a): $full = $a['enrolled'] >= $a['max_students']; ?>
  <tr>
    <td><strong><?= e($a['name']) ?></strong><div class="muted" style="font-size:13px"><?= e($a['location']) ?></div></td>
    <td><?= e($a['schedule_text'] ?: '—') ?></td>
    <td class="muted"><?= e(teacher_names((int)$a['id'])) ?></td>
    <td><?= (int)$a['enrolled'] ?>/<?= (int)$a['max_students'] ?><?php if($full):?> <span class="badge badge-closed">Попуњено</span><?php endif;?></td>
    <?php if (!is_student($u)): ?><td><?= status_badge($a['status']) ?></td><?php endif; ?>
    <?php if (is_student($u)): ?>
      <td><?= $a['my_status'] ? status_badge($a['my_status']) : '<span class="muted">—</span>' ?></td>
    <?php endif; ?>
    <td class="right">
      <div class="row-actions" style="justify-content:flex-end">
        <a class="btn btn-sm btn-ghost" href="activity_view.php?id=<?= (int)$a['id'] ?>">Прикажи</a>
        <?php if (can_manage_activity($u, (int)$a['id'])): ?>
          <a class="btn btn-sm" href="activity_edit.php?id=<?= (int)$a['id'] ?>">Измени</a>
        <?php endif; ?>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

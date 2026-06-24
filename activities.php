<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
$page_title = 'Секције';

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

// render a full table for a set of rows
function activity_table(array $rows, array $u): void { ?>
<table>
  <tr>
    <th>Секција</th><th>Распоред</th><th>Наставник(ци)</th><th>Капацитет</th>
    <?php if (!is_student($u)): ?><th>Статус</th><?php endif; ?>
    <?php if (is_student($u)): ?><th>Мој статус</th><?php endif; ?>
    <th></th>
  </tr>
  <?php foreach ($rows as $a): $full = $a['enrolled'] >= $a['max_students']; ?>
  <tr>
    <td data-label="Секција"><strong><?= e($a['name']) ?></strong><div class="muted" style="font-size:13px"><?= e($a['location']) ?></div></td>
    <td data-label="Распоред"><?= e($a['schedule_text'] ?: '—') ?></td>
    <td class="muted" data-label="Наставник(ци)"><?= e(teacher_names((int)$a['id'])) ?></td>
    <td data-label="Капацитет"><?= (int)$a['enrolled'] ?>/<?= (int)$a['max_students'] ?><?php if($full):?> <span class="badge badge-closed">Попуњено</span><?php endif;?></td>
    <?php if (!is_student($u)): ?><td data-label="Статус"><?= status_badge($a['status']) ?></td><?php endif; ?>
    <?php if (is_student($u)): ?>
      <td data-label="Мој статус"><?= $a['my_status'] ? status_badge($a['my_status']) : '<span class="muted">—</span>' ?></td>
    <?php endif; ?>
    <td class="right" data-label="">
      <div class="row-actions" style="justify-content:flex-end">
        <a class="btn btn-sm btn-ghost" href="activity_view.php?id=<?= (int)$a['id'] ?>">Прикажи</a>
        <?php if (is_student($u) && !$a['my_status'] && !$full): ?>
          <form method="post" action="apply.php" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="activity_id" value="<?= (int)$a['id'] ?>">
            <input type="hidden" name="back" value="list">
            <button class="btn btn-sm">Пријави се</button>
          </form>
        <?php endif; ?>
        <?php if (can_manage_activity($u, (int)$a['id'])): ?>
          <a class="btn btn-sm" href="activity_edit.php?id=<?= (int)$a['id'] ?>">Измени</a>
        <?php endif; ?>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php }

// split into active (open) and inactive (closed/archived) for staff
$active = $rows; $inactive = [];
if (!is_student($u)) {
    $active = array_values(array_filter($rows, fn($a) => $a['status'] === 'open'));
    $inactive = array_values(array_filter($rows, fn($a) => $a['status'] !== 'open'));
}

include __DIR__ . '/includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <div><h1>Секције</h1><p class="sub">
    <?= is_student($u) ? 'Прегледајте секције и пријавите се.' : ($u['role']==='admin' ? 'Све секције у школи.' : 'Секције којима сте додељени.') ?>
  </p></div>
  <?php if (is_admin($u)): ?><a class="btn" href="activity_edit.php">+ Нова секција</a><?php endif; ?>
</div>

<?php if (!$rows): ?>
  <div class="card muted">Нема секција за приказ.</div>
<?php else: ?>
  <?php if (!is_student($u) && !$active): ?>
    <div class="card muted">Нема активних секција.</div>
  <?php elseif ($active): ?>
    <?php activity_table($active, $u); ?>
  <?php endif; ?>

  <?php if ($inactive): ?>
    <h2>Неактивне секције</h2>
    <p class="sub" style="margin-top:-6px">Затворене и архивиране секције.</p>
    <?php activity_table($inactive, $u); ?>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

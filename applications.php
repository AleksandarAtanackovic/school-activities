<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('admin', 'teacher');
$page_title = 'Пријаве';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $appId = (int)($_POST['app_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $st = db()->prepare("SELECT * FROM applications WHERE id=?");
    $st->execute([$appId]);
    $app = $st->fetch();
    if ($app && in_array($decision, ['approved','rejected'], true) && can_manage_activity($u, (int)$app['activity_id'])) {
        if ($decision === 'approved') {
            $act = db()->prepare("SELECT max_students FROM activities WHERE id=?");
            $act->execute([$app['activity_id']]);
            $max = (int)$act->fetchColumn();
            if (approved_count((int)$app['activity_id']) >= $max) {
                flash('Није могуће одобрити: секција је већ попуњена ('.$max.').');
                redirect('applications.php');
            }
        }
        db()->prepare("UPDATE applications SET status=?, decided_at=NOW(), decided_by=? WHERE id=?")
            ->execute([$decision, $u['id'], $appId]);
        flash($decision === 'approved' ? 'Пријава је одобрена.' : 'Пријава је одбијена.');
    } else {
        flash('Не можете да обрадите ту пријаву.');
    }
    redirect('applications.php' . (isset($_POST['activity']) ? '?activity='.(int)$_POST['activity'] : ''));
}

$filterActivity = (int)($_GET['activity'] ?? 0);

// Which activities can this user act on?
$params = [];
$where = "1=1";
if (is_teacher($u)) {
    $where .= " AND ap.activity_id IN (SELECT activity_id FROM activity_teachers WHERE teacher_id=?)";
    $params[] = $u['id'];
}
if ($filterActivity) { $where .= " AND ap.activity_id=?"; $params[] = $filterActivity; }

$sql = "SELECT ap.*, s.name AS student_name, s.grade_class, a.name AS activity_name, a.max_students,
        (SELECT COUNT(*) FROM applications x WHERE x.activity_id=ap.activity_id AND x.status='approved') AS approved_now
        FROM applications ap
        JOIN users s ON s.id=ap.student_id
        JOIN activities a ON a.id=ap.activity_id
        WHERE $where
        ORDER BY (ap.status='pending') DESC, ap.applied_at DESC";
$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<h1>Пријаве</h1>
<p class="sub"><?= is_admin($u) ? 'Све пријаве ученика.' : 'Пријаве за ваше секције.' ?></p>

<?php if (!$rows): ?>
  <div class="card muted">Нема пријава за приказ.</div>
<?php else: ?>
<table>
  <tr><th>Ученик</th><th>Разред</th><th>Секција</th><th>Пријављен</th><th>Статус</th><th class="right">Радња</th></tr>
  <?php foreach ($rows as $r): $full = $r['approved_now'] >= $r['max_students']; ?>
  <tr>
    <td data-label="Ученик"><?= e($r['student_name']) ?></td>
    <td class="muted" data-label="Разред"><?= e($r['grade_class'] ?: '—') ?></td>
    <td data-label="Секција"><?= e($r['activity_name']) ?> <span class="muted">(<?= (int)$r['approved_now'] ?>/<?= (int)$r['max_students'] ?>)</span></td>
    <td class="muted" data-label="Пријављен"><?= e(date('d.m.Y.', strtotime($r['applied_at']))) ?></td>
    <td data-label="Статус"><?= status_badge($r['status']) ?></td>
    <td class="right" data-label="">
      <?php if ($r['status']==='pending'): ?>
      <form method="post" class="inline">
        <?= csrf_field() ?>
        <input type="hidden" name="app_id" value="<?= (int)$r['id'] ?>">
        <?php if ($filterActivity): ?><input type="hidden" name="activity" value="<?= $filterActivity ?>"><?php endif; ?>
        <button class="btn btn-sm btn-ok" name="decision" value="approved" <?= $full?'disabled title="Секција је попуњена"':'' ?>>Одобри</button>
        <button class="btn btn-sm btn-bad" name="decision" value="rejected">Одбиј</button>
      </form>
      <?php else: ?><span class="muted">—</span><?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('admin', 'teacher');
$page_title = 'Присуство';

$activityId = (int)($_GET['activity'] ?? 0);

// delete a session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_session') {
    csrf_check();
    $sid = (int)($_POST['session_id'] ?? 0);
    $st = db()->prepare("SELECT activity_id FROM sessions WHERE id=?");
    $st->execute([$sid]);
    $aid = (int)$st->fetchColumn();
    if ($aid && can_manage_activity($u, $aid)) {
        db()->prepare("DELETE FROM sessions WHERE id=?")->execute([$sid]);
        flash('Термин је обрисан.');
    }
    redirect('attendance.php?activity=' . $aid);
}

// ---- CHOOSER: no section selected ----
if (!$activityId) {
    if (is_admin($u)) {
        $sections = db()->query("SELECT a.*,
            (SELECT COUNT(*) FROM applications ap WHERE ap.activity_id=a.id AND ap.status='approved') AS enrolled,
            (SELECT COUNT(*) FROM sessions s WHERE s.activity_id=a.id) AS sessions
            FROM activities a ORDER BY a.status='archived', a.name")->fetchAll();
    } else {
        $st = db()->prepare("SELECT a.*,
            (SELECT COUNT(*) FROM applications ap WHERE ap.activity_id=a.id AND ap.status='approved') AS enrolled,
            (SELECT COUNT(*) FROM sessions s WHERE s.activity_id=a.id) AS sessions
            FROM activities a JOIN activity_teachers t ON t.activity_id=a.id
            WHERE t.teacher_id=? ORDER BY a.name");
        $st->execute([$u['id']]);
        $sections = $st->fetchAll();
    }
    include __DIR__ . '/includes/header.php';
    ?>
    <h1>Присуство</h1>
    <p class="sub">Изаберите секцију да водите присуство.</p>
    <?php if (!$sections): ?>
      <div class="card muted">Нема секција.</div>
    <?php else: ?>
    <table>
      <tr><th>Секција</th><th>Број уписаних ученика</th><th>Број одржаних термина</th><th></th></tr>
      <?php foreach ($sections as $a): ?>
      <tr>
        <td data-label="Секција"><strong><?= e($a['name']) ?></strong> <?= status_badge($a['status']) ?></td>
        <td data-label="Број уписаних ученика"><?= (int)$a['enrolled'] ?></td>
        <td data-label="Број одржаних термина"><?= (int)$a['sessions'] ?></td>
        <td class="right" data-label=""><a class="btn btn-sm" href="attendance.php?activity=<?= (int)$a['id'] ?>">Отвори</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php
    exit;
}

// ---- SECTION SELECTED: list sessions ----
if (!can_manage_activity($u, $activityId)) { http_response_code(403); die('Приступ одбијен.'); }
$st = db()->prepare("SELECT * FROM activities WHERE id=?");
$st->execute([$activityId]);
$a = $st->fetch();
if (!$a) { http_response_code(404); die('Секција није пронађена.'); }

$enrolled = approved_count($activityId);

$ss = db()->prepare("SELECT s.*,
        (SELECT COUNT(*) FROM attendance x WHERE x.session_id=s.id AND x.status='present') AS present,
        (SELECT COUNT(*) FROM attendance x WHERE x.session_id=s.id) AS recorded
        FROM sessions s WHERE s.activity_id=? ORDER BY s.session_date ASC");
$ss->execute([$activityId]);
$sessions = $ss->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
  <div>
    <h1>Присуство · <?= e($a['name']) ?></h1>
    <p class="sub"><a href="attendance.php">&larr; Све секције</a> &nbsp;·&nbsp; Уписаних ученика: <?= $enrolled ?></p>
  </div>
  <a class="btn" href="attendance_take.php?activity=<?= $activityId ?>">+ Забележи присуство</a>
</div>

<?php if (!$sessions): ?>
  <div class="card muted">Још нема унетих термина. Кликните „Забележи присуство“ да унесете први.</div>
<?php else: ?>
<table>
  <tr><th>Датум</th><th>Присутно</th><th class="right">Радња</th></tr>
  <?php foreach ($sessions as $s): ?>
  <tr>
    <td data-label="Датум"><strong><?= e(date('d.m.Y.', strtotime($s['session_date']))) ?></strong></td>
    <td data-label="Присутно"><?= (int)$s['present'] ?></td>
    <td class="right" data-label="">
      <div class="row-actions" style="justify-content:flex-end">
        <a class="btn btn-sm" href="attendance_take.php?session=<?= (int)$s['id'] ?>">Измени</a>
        <form method="post" class="inline" onsubmit="return confirm('Обрисати овај термин и сво присуство за тај датум?');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_session">
          <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
          <button class="btn btn-sm btn-bad">Обриши</button>
        </form>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

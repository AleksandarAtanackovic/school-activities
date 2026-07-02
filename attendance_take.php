<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('admin', 'teacher');

$sessionId  = (int)($_GET['session'] ?? $_POST['session_id'] ?? 0);
$activityId = (int)($_GET['activity'] ?? $_POST['activity_id'] ?? 0);

// If editing, derive activity from the session.
$session = null;
if ($sessionId) {
    $st = db()->prepare("SELECT * FROM sessions WHERE id=?");
    $st->execute([$sessionId]);
    $session = $st->fetch();
    if (!$session) { http_response_code(404); die('Термин није пронађен.'); }
    $activityId = (int)$session['activity_id'];
}
if (!can_manage_activity($u, $activityId)) { http_response_code(403); die('Приступ одбијен.'); }

$st = db()->prepare("SELECT * FROM activities WHERE id=?");
$st->execute([$activityId]);
$a = $st->fetch();
if (!$a) { http_response_code(404); die('Секција није пронађена.'); }

// enrolled (approved) students
$es = db()->prepare("SELECT u.id, u.name, u.grade_class FROM applications ap JOIN users u ON u.id=ap.student_id
                     WHERE ap.activity_id=? AND ap.status='approved' ORDER BY u.name");
$es->execute([$activityId]);
$students = $es->fetchAll();

// existing attendance map (when editing)
$existing = [];
if ($sessionId) {
    $ax = db()->prepare("SELECT student_id, status FROM attendance WHERE session_id=?");
    $ax->execute([$sessionId]);
    foreach ($ax->fetchAll() as $r) { $existing[(int)$r['student_id']] = $r['status']; }
}

$valid = ['present','absent','excused'];
$error = '';
$date = $session['session_date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $date = trim($_POST['date'] ?? '');
    $statuses = $_POST['status'] ?? [];
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        $error = 'Унесите исправан датум.';
    } else {
        try {
            if ($sessionId) {
                // update date if changed (unique per activity+date)
                if ($date !== $session['session_date']) {
                    db()->prepare("UPDATE sessions SET session_date=? WHERE id=?")->execute([$date, $sessionId]);
                }
                $sid = $sessionId;
            } else {
                // find existing session for this date, else create
                $f = db()->prepare("SELECT id FROM sessions WHERE activity_id=? AND session_date=?");
                $f->execute([$activityId, $date]);
                $sid = (int)$f->fetchColumn();
                if (!$sid) {
                    db()->prepare("INSERT INTO sessions (activity_id, session_date, created_by) VALUES (?,?,?)")
                        ->execute([$activityId, $date, $u['id']]);
                    $sid = (int)db()->lastInsertId();
                }
            }
            // upsert attendance for each enrolled student
            $ins = db()->prepare("INSERT INTO attendance (session_id, student_id, status, recorded_by)
                                  VALUES (?,?,?,?)
                                  ON DUPLICATE KEY UPDATE status=VALUES(status), recorded_by=VALUES(recorded_by), recorded_at=NOW()");
            foreach ($students as $s) {
                $val = $statuses[$s['id']] ?? 'present';
                if (!in_array($val, $valid, true)) $val = 'present';
                $ins->execute([$sid, (int)$s['id'], $val, $u['id']]);
            }
            flash('Присуство је сачувано.');
            redirect('attendance.php?activity=' . $activityId);
        } catch (PDOException $ex) {
            $error = 'Већ постоји термин за тај датум. Изаберите други датум или измените постојећи термин.';
        }
    }
    // keep submitted statuses on error
    foreach ($students as $s) { $existing[(int)$s['id']] = $statuses[$s['id']] ?? 'present'; }
}

$page_title = 'Забележи присуство';
include __DIR__ . '/includes/header.php';
?>
<h1><?= $sessionId ? 'Измена присуства' : 'Забележи присуство' ?> · <?= e($a['name']) ?></h1>
<p class="sub"><a href="attendance.php?activity=<?= $activityId ?>">&larr; Назад на термине</a></p>

<?php if (!$students): ?>
  <div class="card muted">Нема уписаних ученика у овој секцији, па нема за кога водити присуство.</div>
<?php else: ?>
<div class="card">
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="activity_id" value="<?= (int)$activityId ?>">
    <input type="hidden" name="session_id" value="<?= (int)$sessionId ?>">
    <div style="max-width:240px">
      <label>Датум</label>
      <input type="date" name="date" value="<?= e($date) ?>" required>
    </div>

    <div class="att-quick">
      <span class="muted" style="font-size:13px">Брзо означи све:</span>
      <button type="button" class="btn btn-sm btn-ghost" onclick="attAll('present')">Сви присутни</button>
      <button type="button" class="btn btn-sm btn-ghost" onclick="attAll('absent')">Сви одсутни</button>
      <button type="button" class="btn btn-sm btn-ghost" onclick="attAll('excused')">Сви оправдани</button>
    </div>

    <div class="att-list">
      <?php foreach ($students as $s): $cur = $existing[(int)$s['id']] ?? 'present'; ?>
      <div class="att-row">
        <div class="att-name"><?= e($s['name']) ?> <span class="att-class"><?= e($s['grade_class'] ?: '') ?></span></div>
        <div class="att-opts">
          <?php foreach (['present'=>'Присутан','absent'=>'Одсутан','excused'=>'Оправдано'] as $val=>$lbl): ?>
            <label><input type="radio" name="status[<?= (int)$s['id'] ?>]" value="<?= $val ?>" <?= $cur===$val?'checked':'' ?>> <?= $lbl ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:16px"><button class="btn">Сачувај присуство</button></div>
  </form>
</div>
<script>
function attAll(v){
  document.querySelectorAll('.att-opts input[type=radio][value="'+v+'"]').forEach(function(r){ r.checked = true; });
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

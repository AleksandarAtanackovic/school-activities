<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('student');
csrf_check();

$activityId = (int)($_POST['activity_id'] ?? 0);
$back = ($_POST['back'] ?? '') === 'list' ? 'activities.php' : 'activity_view.php?id='.$activityId;
$st = db()->prepare("SELECT * FROM activities WHERE id=?");
$st->execute([$activityId]);
$a = $st->fetch();
if (!$a) { http_response_code(404); die('Секција није пронађена.'); }

if ($a['status'] !== 'open') { flash('Ова секција није отворена за пријаве.'); redirect($back); }
if (approved_count($activityId) >= $a['max_students']) { flash('Ова секција је попуњена.'); redirect($back); }

// One application per student per activity (DB also enforces this).
$ex = db()->prepare("SELECT id FROM applications WHERE student_id=? AND activity_id=?");
$ex->execute([$u['id'], $activityId]);
if ($ex->fetchColumn()) {
    flash('Већ сте се пријавили на ову секцију.');
} else {
    db()->prepare("INSERT INTO applications (student_id, activity_id, status) VALUES (?,?, 'pending')")
        ->execute([$u['id'], $activityId]);
    flash('Пријава је послата. Бићете обавештени када буде разматрана.');
}
redirect($back);

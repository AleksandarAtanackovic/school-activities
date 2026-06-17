<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('student');
csrf_check();

$activityId = (int)($_POST['activity_id'] ?? 0);
$st = db()->prepare("SELECT * FROM activities WHERE id=?");
$st->execute([$activityId]);
$a = $st->fetch();
if (!$a) { http_response_code(404); die('Activity not found.'); }

if ($a['status'] !== 'open') { flash('This activity is not open for applications.'); redirect('activity_view.php?id='.$activityId); }
if (approved_count($activityId) >= $a['max_students']) { flash('This activity is full.'); redirect('activity_view.php?id='.$activityId); }

// One application per student per activity (DB also enforces this).
$ex = db()->prepare("SELECT id FROM applications WHERE student_id=? AND activity_id=?");
$ex->execute([$u['id'], $activityId]);
if ($ex->fetchColumn()) {
    flash('You have already applied to this activity.');
} else {
    db()->prepare("INSERT INTO applications (student_id, activity_id, status) VALUES (?,?, 'pending')")
        ->execute([$u['id'], $activityId]);
    flash('Application submitted. You will be notified once it is reviewed.');
}
redirect('activity_view.php?id='.$activityId);

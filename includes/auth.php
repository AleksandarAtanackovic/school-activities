<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    static $u = null;
    if ($u === null) {
        $st = db()->prepare('SELECT * FROM users WHERE id=? AND active=1');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
        if ($u === null) { // user removed/deactivated mid-session
            session_destroy();
        }
    }
    return $u;
}

function require_login(): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}

// Pass one or more allowed roles. Aborts with 403 if not allowed.
function require_role(string ...$roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied: you do not have permission to view this page.');
    }
    return $u;
}

function is_admin(array $u): bool   { return $u['role'] === 'admin'; }
function is_teacher(array $u): bool { return $u['role'] === 'teacher'; }
function is_student(array $u): bool { return $u['role'] === 'student'; }

// Admin sees all activities; teacher sees only assigned ones.
function can_manage_activity(array $u, int $activityId): bool {
    if (is_admin($u)) return true;
    if (is_teacher($u)) return teacher_owns((int)$u['id'], $activityId);
    return false;
}

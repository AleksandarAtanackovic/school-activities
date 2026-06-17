<?php
// Shared helper functions.

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function flash(?string $msg = null): ?string {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $m;
    }
    return null;
}

// --- CSRF protection for POST forms ---
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
            http_response_code(419);
            die('Session expired or invalid request. Please go back and try again.');
        }
    }
}

// Number of approved students in an activity.
function approved_count(int $activityId): int {
    $st = db()->prepare("SELECT COUNT(*) FROM applications WHERE activity_id=? AND status='approved'");
    $st->execute([$activityId]);
    return (int)$st->fetchColumn();
}

// Does a teacher own (is assigned to) this activity?
function teacher_owns(int $teacherId, int $activityId): bool {
    $st = db()->prepare("SELECT 1 FROM activity_teachers WHERE teacher_id=? AND activity_id=?");
    $st->execute([$teacherId, $activityId]);
    return (bool)$st->fetchColumn();
}

function status_badge(string $status): string {
    return '<span class="badge badge-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

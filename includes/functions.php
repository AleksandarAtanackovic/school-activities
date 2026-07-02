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
            die('Сесија је истекла или је захтев неисправан. Вратите се назад и покушајте поново.');
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

// --- Serbian Cyrillic labels for stored (English) enum values ---
function status_label(string $s): string {
    static $m = [
        'pending'  => 'На чекању', 'approved' => 'Одобрено', 'rejected' => 'Одбијено',
        'open'     => 'Отворено',  'closed'   => 'Затворено', 'archived' => 'Архивирано',
        'present'  => 'Присутан',  'absent'   => 'Одсутан',   'excused'  => 'Оправдано',
    ];
    return $m[$s] ?? $s;
}
function role_label(string $r): string {
    static $m = ['admin' => 'Администратор', 'teacher' => 'Наставник', 'student' => 'Ученик'];
    return $m[$r] ?? $r;
}
function status_badge(string $status): string {
    return '<span class="badge badge-' . e($status) . '">' . e(status_label($status)) . '</span>';
}

// Cyrillic (Serbian) -> Latin transliteration, for building usernames.
function cyr_to_lat(string $s): string {
    static $m = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','ђ'=>'dj','е'=>'e','ж'=>'z','з'=>'z',
        'и'=>'i','ј'=>'j','к'=>'k','л'=>'l','љ'=>'lj','м'=>'m','н'=>'n','њ'=>'nj','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','ћ'=>'c','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c',
        'ч'=>'c','џ'=>'dz','ш'=>'s',
    ];
    $s = mb_strtolower($s, 'UTF-8');
    $out = '';
    $len = mb_strlen($s, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $ch = mb_substr($s, $i, 1, 'UTF-8');
        $out .= $m[$ch] ?? $ch;
    }
    return $out;
}

// Build a username: first letter of first name + surname + last 4 digits of maticni broj.
// e.g. "Ана Новак" + "...7118" -> "anovak7118"
function build_username(string $name, string $maticni): string {
    $parts = preg_split('/\s+/u', trim($name));
    $first = $parts[0] ?? '';
    $last  = count($parts) > 1 ? $parts[count($parts) - 1] : '';
    $digits = preg_replace('/\D/', '', $maticni);
    $last4  = strlen($digits) >= 4 ? substr($digits, -4) : $digits;
    $base = cyr_to_lat(mb_substr($first, 0, 1, 'UTF-8')) . cyr_to_lat($last) . $last4;
    // keep only safe characters
    return preg_replace('/[^a-z0-9]/', '', mb_strtolower($base, 'UTF-8'));
}

// Append the file's modification time to an asset URL so browsers fetch a fresh
// copy whenever the file changes (prevents stale cached CSS/JS after updates).
function asset_ver(string $relPath): string {
    $abs = __DIR__ . '/../' . $relPath;
    $v = @filemtime($abs) ?: time();
    return $relPath . '?v=' . $v;
}

// Default reset password for a given role (see config.php).
function default_reset_password(string $role): string {
    switch ($role) {
        case 'student': return RESET_PW_STUDENT;
        case 'teacher': return RESET_PW_TEACHER;
        default:        return RESET_PW_ADMIN;
    }
}

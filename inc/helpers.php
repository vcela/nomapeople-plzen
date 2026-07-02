<?php
// Small shared utilities used by both api/ and admin/.

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data);
    exit;
}

function esc(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
    return false;
}

// Redirects to another script in admin/, as an absolute path built from the
// current request rather than a bare relative Location header. A relative
// `header('Location: login.php')` resolves against the URL's *directory* —
// for a request to the bare directory URL "/admin" (no trailing slash) that
// directory is "/", not "/admin/", so the browser ends up at "/login.php"
// (404) instead of "/admin/login.php". Using $_SERVER['SCRIPT_NAME'] instead
// sidesteps that regardless of trailing slash or deployment subpath
// (e.g. "/plzen/admin/..." on the live site vs "/admin/..." locally).
function admin_redirect(string $file): void {
    $adminDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $adminDir . '/' . ltrim($file, '/'));
    exit;
}

// ISO-8601 weekday number (1=pondělí..7=neděle, matches PHP's date('N')) to
// its Czech name. Used by admin/settings.php's picker and admin/index.php's
// lesson-week group headers.
function czech_weekday_name(int $isoWeekday): string {
    $names = [
        1 => 'Pondělí', 2 => 'Úterý', 3 => 'Středa', 4 => 'Čtvrtek',
        5 => 'Pátek', 6 => 'Sobota', 7 => 'Neděle',
    ];
    return $names[$isoWeekday] ?? '?';
}

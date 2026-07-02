<?php
// Session bootstrap + persistent "remember me" login.
//
// Every successful admin login issues a remember-me cookie automatically
// (no opt-in checkbox) — the user asked that being logged in should just be
// remembered. The cookie carries a random selector (used to look up the
// token row) and a random validator (never stored in the DB, only its
// sha256 hash is) — the classic split-token pattern, which lets us find a
// candidate row by selector without an equality check ever touching the
// secret half directly, and lets us compare the secret half in constant
// time. A token is rotated (deleted + reissued) every time it's used, and
// a validator mismatch on an existing selector is treated as a stolen
// cookie: every token for that admin is wiped and the cookie cleared.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

const REMEMBER_COOKIE = 'nm_remember';
const REMEMBER_DAYS = 30;

function auth_bootstrap(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => COOKIE_PATH,
        'domain' => '',
        'secure' => FORCE_HTTPS_COOKIES && is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    if (empty($_SESSION['admin_id'])) {
        try_remember_login();
    }
}

function current_admin(): ?array {
    static $cached = false;
    static $cachedValue = null;
    if ($cached) return $cachedValue;
    $cached = true;
    if (empty($_SESSION['admin_id'])) return $cachedValue = null;
    $stmt = db()->prepare('SELECT id, email FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch();
    return $cachedValue = ($row ?: null);
}

function require_admin(): array {
    $admin = current_admin();
    if (!$admin) {
        header('Location: login.php');
        exit;
    }
    return $admin;
}

function login_admin(int $adminId): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    issue_remember_cookie($adminId);
}

function issue_remember_cookie(int $adminId): void {
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $hash = hash('sha256', $validator);
    $expiresAt = time() + REMEMBER_DAYS * 24 * 3600;

    db()->prepare('INSERT INTO admin_remember_tokens (admin_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)')
        ->execute([$adminId, $selector, $hash, date('Y-m-d H:i:s', $expiresAt)]);

    setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires' => $expiresAt,
        'path' => COOKIE_PATH,
        'domain' => '',
        'secure' => FORCE_HTTPS_COOKIES && is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function try_remember_login(): bool {
    if (empty($_COOKIE[REMEMBER_COOKIE]) || strpos($_COOKIE[REMEMBER_COOKIE], ':') === false) {
        return false;
    }
    [$selector, $validator] = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);

    $stmt = db()->prepare('SELECT * FROM admin_remember_tokens WHERE selector = ? AND expires_at > NOW()');
    $stmt->execute([$selector]);
    $row = $stmt->fetch();

    if (!$row) {
        clear_remember_cookie();
        return false;
    }
    if (!hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        // Selector matched but validator didn't — likely a stolen/replayed
        // cookie. Fail closed: revoke every token for this admin.
        forget_all_admin_tokens((int) $row['admin_id']);
        clear_remember_cookie();
        return false;
    }

    db()->prepare('DELETE FROM admin_remember_tokens WHERE id = ?')->execute([$row['id']]);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $row['admin_id'];
    issue_remember_cookie((int) $row['admin_id']);
    return true;
}

function logout_admin(): void {
    if (!empty($_COOKIE[REMEMBER_COOKIE]) && strpos($_COOKIE[REMEMBER_COOKIE], ':') !== false) {
        [$selector] = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
        db()->prepare('DELETE FROM admin_remember_tokens WHERE selector = ?')->execute([$selector]);
    }
    clear_remember_cookie();
    $_SESSION = [];
    session_destroy();
}

function forget_all_admin_tokens(int $adminId): void {
    db()->prepare('DELETE FROM admin_remember_tokens WHERE admin_id = ?')->execute([$adminId]);
}

function clear_remember_cookie(): void {
    setcookie(REMEMBER_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => COOKIE_PATH,
        'domain' => '',
        'secure' => FORCE_HTTPS_COOKIES && is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

<?php
// Public, read-only login-status probe. index.html polls this on mount to
// decide whether to show the "Administrace" nav link. Deliberately returns
// only a boolean — never the admin's email/id — so nobody inspecting the
// network tab learns who, if anyone, is logged in.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['isAdmin' => false], 405);
}

header('Cache-Control: no-store');

try {
    auth_bootstrap();
    $isAdmin = current_admin() !== null;
} catch (Throwable $e) {
    error_log('[session] auth check failed: ' . $e->getMessage());
    $isAdmin = false;
}

json_response(['isAdmin' => $isAdmin]);

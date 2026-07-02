<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';

auth_bootstrap();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf'] ?? null)) {
    header('Location: index.php');
    exit;
}

logout_admin();
header('Location: login.php');

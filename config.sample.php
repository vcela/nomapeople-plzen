<?php
// Copy this file to config.php (gitignored) and fill in real values
// before uploading to the host. Never commit config.php.

define('DB_HOST', 'localhost');
define('DB_NAME', 'REPLACE_ME');
define('DB_USER', 'REPLACE_ME');
define('DB_PASS', 'REPLACE_ME');

define('SMTP_HOST', 'smtp.webglobe.cz');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_USER', 'REPLACE_ME');
define('SMTP_PASS', 'REPLACE_ME');
define('SMTP_FROM_EMAIL', 'info@nomapeople.com');
define('SMTP_FROM_NAME', 'NŌMA people');

// Session/remember-me cookie path. '/' covers the whole domain regardless
// of where this site is deployed (e.g. under /plzen/), so it normally does
// not need to change — only narrow it if the host's PHP default already
// scopes cookies below this path.
define('COOKIE_PATH', '/');

// Set to false only if the site is temporarily served over plain HTTP
// (e.g. local testing without TLS) — must be true in production.
define('FORCE_HTTPS_COOKIES', true);

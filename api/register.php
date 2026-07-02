<?php
// Public, POST-only registration endpoint. No session/CSRF here — there is
// no ambient authority to protect (anyone could already POST here directly,
// same as any public contact form), so authoritative validation happens
// entirely server-side below regardless of what the client already checked.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

// Cheap same-host defense-in-depth. Browsers send Origin on POST fetches
// even for same-origin requests, so this doesn't affect the real form; it
// only rejects obvious cross-site browser submissions. Skipped silently
// when Origin is absent (e.g. some non-browser or same-origin edge cases).
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $originHost = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    if ($originHost && $originHost !== $_SERVER['HTTP_HOST']) {
        json_response(['ok' => false, 'error' => 'Forbidden'], 403);
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'Neplatná data formuláře.'], 400);
}

$name = trim((string) ($input['name'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$note = trim((string) ($input['note'] ?? ''));
$gdpr = ($input['gdpr'] ?? false) === true;
$photo = ($input['photo'] ?? false) === true;

if ($name === '' || mb_strlen($name) > 190) {
    json_response(['ok' => false, 'error' => 'Vyplň prosím jméno a příjmení.'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    json_response(['ok' => false, 'error' => 'Zkontroluj prosím e-mailovou adresu.'], 400);
}
if (mb_strlen($phone) > 40) {
    json_response(['ok' => false, 'error' => 'Telefonní číslo je příliš dlouhé.'], 400);
}
if (mb_strlen($note) > 2000) {
    json_response(['ok' => false, 'error' => 'Poznámka je příliš dlouhá.'], 400);
}
if (!$gdpr) {
    json_response(['ok' => false, 'error' => 'Pro registraci potřebujeme souhlas se zpracováním údajů.'], 400);
}

try {
    $stmt = db()->prepare(
        'INSERT INTO registrations (name, email, phone, note, gdpr_consent, photo_consent) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $phone ?: null, $note ?: null, $gdpr ? 1 : 0, $photo ? 1 : 0]);
} catch (Throwable $e) {
    error_log('[register] DB insert failed: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Něco se nepovedlo, zkus to prosím znovu.'], 500);
}

$reg = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'note' => $note,
    'gdpr_consent' => $gdpr,
    'photo_consent' => $photo,
];

// Registration is already stored — a failed email must never undo that.
send_admin_notification($reg);
send_registrant_confirmation($reg);

json_response(['ok' => true]);

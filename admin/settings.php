<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';

auth_bootstrap();
$admin = require_admin();
$pageTitle = 'Nastavení';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'Neplatný nebo vypršelý formulář, zkus to prosím znovu.';
    } else {
        $email = trim((string) ($_POST['notification_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Zadej prosím platnou e-mailovou adresu.';
        } else {
            db()->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?')
                ->execute([$email, 'notification_email']);
            $success = 'Notifikační e-mail byl uložen.';
        }
    }
}

$stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
$stmt->execute(['notification_email']);
$current = $stmt->fetch();
$currentEmail = $current ? $current['setting_value'] : '';

require __DIR__ . '/inc/header.php';
?>
<div class="card" style="max-width:420px;">
  <?php if ($error): ?><div class="msg-error"><?= esc($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg-ok"><?= esc($success) ?></div><?php endif; ?>
  <form method="post" action="settings.php">
    <?php csrf_field(); ?>
    <label for="notification_email">Notifikační e-mail</label>
    <div class="hint">Na tuto adresu chodí upozornění o nové přihlášce.</div>
    <input type="email" id="notification_email" name="notification_email" value="<?= esc($currentEmail) ?>" required>
    <button type="submit">Uložit</button>
  </form>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>

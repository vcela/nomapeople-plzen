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
    } elseif (($_POST['form'] ?? '') === 'notification_email') {
        $email = trim((string) ($_POST['notification_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Zadej prosím platnou e-mailovou adresu.';
        } else {
            db()->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?')
                ->execute([$email, 'notification_email']);
            $success = 'Notifikační e-mail byl uložen.';
        }
    } elseif (($_POST['form'] ?? '') === 'lesson_weekday') {
        $weekday = (int) ($_POST['lesson_weekday'] ?? 0);
        if ($weekday < 1 || $weekday > 7) {
            $error = 'Vyber prosím platný den v týdnu.';
        } else {
            db()->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?')
                ->execute([(string) $weekday, 'lesson_weekday']);
            $success = 'Den konání lekce byl uložen.';
        }
    }
}

$stmt = db()->prepare('SELECT setting_key, setting_value FROM settings WHERE setting_key IN (?, ?)');
$stmt->execute(['notification_email', 'lesson_weekday']);
$rows = $stmt->fetchAll();
$currentEmail = '';
$currentWeekday = 1;
foreach ($rows as $row) {
    if ($row['setting_key'] === 'notification_email') $currentEmail = $row['setting_value'];
    if ($row['setting_key'] === 'lesson_weekday') $currentWeekday = (int) $row['setting_value'];
}

require __DIR__ . '/inc/header.php';
?>
<div class="card" style="max-width:420px;">
  <?php if ($error): ?><div class="msg-error"><?= esc($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg-ok"><?= esc($success) ?></div><?php endif; ?>
  <form method="post" action="settings.php">
    <?php csrf_field(); ?>
    <input type="hidden" name="form" value="notification_email">
    <label for="notification_email">Notifikační e-mail</label>
    <div class="hint">Na tuto adresu chodí upozornění o nové přihlášce.</div>
    <input type="email" id="notification_email" name="notification_email" value="<?= esc($currentEmail) ?>" required>
    <button type="submit">Uložit</button>
  </form>
</div>
<div class="card" style="max-width:420px;">
  <form method="post" action="settings.php">
    <?php csrf_field(); ?>
    <input type="hidden" name="form" value="lesson_weekday">
    <label for="lesson_weekday">Den konání lekce</label>
    <div class="hint">Podle tohoto dne se přihlášení ve výpisu seskupují do jednotlivých lekcí.</div>
    <select id="lesson_weekday" name="lesson_weekday" style="width:100%; max-width:360px; padding:10px 12px; border:1.5px solid rgba(136,72,88,0.25); border-radius:10px; font-size:15px; margin-bottom:16px;">
      <?php for ($num = 1; $num <= 7; $num++): ?>
      <option value="<?= $num ?>"<?= $num === $currentWeekday ? ' selected' : '' ?>><?= esc(czech_weekday_name($num)) ?></option>
      <?php endfor; ?>
    </select>
    <button type="submit">Uložit</button>
  </form>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>

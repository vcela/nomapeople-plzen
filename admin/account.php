<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';

auth_bootstrap();
$admin = require_admin();
$pageTitle = 'Můj účet';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'Neplatný nebo vypršelý formulář, zkus to prosím znovu.';
    } else {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        $stmt = db()->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
        $stmt->execute([$admin['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = 'Současné heslo není správně.';
        } elseif (mb_strlen($new) < 8) {
            $error = 'Nové heslo musí mít alespoň 8 znaků.';
        } elseif ($new !== $confirm) {
            $error = 'Nová hesla se neshodují.';
        } else {
            db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);

            // Changing the password should sign out every other
            // session/device; keep the current browser logged in by
            // issuing it a fresh remember-me token right after.
            forget_all_admin_tokens($admin['id']);
            issue_remember_cookie($admin['id']);

            $success = 'Heslo bylo změněno. Ostatní přihlášená zařízení byla odhlášena.';
        }
    }
}

require __DIR__ . '/inc/header.php';
?>
<div class="card" style="max-width:420px;">
  <p style="margin-top:0;">Přihlášen jako <strong><?= esc($admin['email']) ?></strong>.</p>
  <?php if ($error): ?><div class="msg-error"><?= esc($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg-ok"><?= esc($success) ?></div><?php endif; ?>
  <form method="post" action="account.php">
    <?php csrf_field(); ?>
    <label for="current_password">Současné heslo</label>
    <input type="password" id="current_password" name="current_password" required>
    <label for="new_password">Nové heslo</label>
    <input type="password" id="new_password" name="new_password" required minlength="8">
    <label for="new_password_confirm">Nové heslo znovu</label>
    <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="8">
    <button type="submit">Změnit heslo</button>
  </form>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>

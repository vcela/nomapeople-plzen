<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';

auth_bootstrap();
$admin = require_admin();
$pageTitle = 'Uživatelé';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'Neplatný nebo vypršelý formulář, zkus to prosím znovu.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Zadej prosím platnou e-mailovou adresu.';
        } elseif (mb_strlen($password) < 6) {
            $error = 'Heslo musí mít alespoň 6 znaků.';
        } else {
            try {
                db()->prepare('INSERT INTO admin_users (email, password_hash) VALUES (?, ?)')
                    ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                $success = 'Uživatel byl přidán.';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = 'Tento e-mail už existuje.';
                } else {
                    throw $e;
                }
            }
        }
    }
}

$users = db()->query('SELECT id, email, created_at FROM admin_users ORDER BY created_at ASC')->fetchAll();

require __DIR__ . '/inc/header.php';
?>
<div class="card">
  <table>
    <thead><tr><th>E-mail</th><th>Vytvořen</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= esc($u['email']) ?></td>
        <td><?= esc(date('d.m.Y H:i', strtotime($u['created_at']))) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card" style="max-width:420px;">
  <h2 style="font-size:18px; margin-top:0; color:#884858;">Přidat uživatele</h2>
  <?php if ($error): ?><div class="msg-error"><?= esc($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg-ok"><?= esc($success) ?></div><?php endif; ?>
  <form method="post" action="users.php">
    <?php csrf_field(); ?>
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required>
    <label for="password">Heslo</label>
    <input type="password" id="password" name="password" required minlength="6">
    <button type="submit">Přidat</button>
  </form>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>

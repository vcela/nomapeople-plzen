<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';

auth_bootstrap();

if (current_admin()) {
    admin_redirect('index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'Neplatný nebo vypršelý formulář, zkus to prosím znovu.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = db()->prepare('SELECT id, password_hash FROM admin_users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        // Always run password_verify against *some* hash (a static dummy
        // one when the email doesn't exist) so response timing doesn't
        // reveal whether the address is registered.
        $hashToCheck = $row['password_hash'] ?? '$2y$10$usxHf4nB8HqjX8y3aM0EIOe6Bq0F1S8w2m8m1o9m0N0eZ8kQvQe0e';
        $ok = password_verify($password, $hashToCheck) && $row !== false;

        if ($ok) {
            login_admin((int) $row['id']);
            admin_redirect('index.php');
        }

        usleep(random_int(150000, 350000));
        $error = 'Nesprávný e-mail nebo heslo.';
    }
}

$admin = null;
$pageTitle = 'Přihlášení';
require __DIR__ . '/inc/header.php';
?>
<div class="card" style="max-width:400px; margin:40px auto 0;">
  <?php if ($error): ?><div class="msg-error"><?= esc($error) ?></div><?php endif; ?>
  <form method="post" action="login.php">
    <?php csrf_field(); ?>
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required autofocus>
    <label for="password">Heslo</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Přihlásit se</button>
  </form>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>

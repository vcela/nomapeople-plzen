<?php
// Shared chrome for admin/*.php. The including page must already have
// required config.php + inc/db.php + inc/helpers.php + inc/auth.php +
// inc/csrf.php, called auth_bootstrap(), and set:
//   $pageTitle (string)
//   $admin     (array from current_admin()/require_admin(), or null on login.php)
// before requiring this file. Auth itself is NOT enforced here — pages that
// need a login call require_admin() themselves before including this, so an
// unauthenticated hit never even renders this shell; login.php reuses the
// same look with $admin = null instead.
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($pageTitle) ?> · Administrace · NŌMA people</title>
<style>
  *{ box-sizing:border-box; }
  body{ margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; background:#ece5d8; color:#2a2320; }
  a{ color:inherit; }
  .admin-header{ background:#884858; color:#f4ecc6; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
  .admin-header a.brand{ font-weight:700; text-decoration:none; letter-spacing:0.02em; }
  .admin-nav{ display:flex; align-items:center; gap:6px; flex-wrap:wrap; font-size:14px; }
  .admin-nav a{ text-decoration:none; padding:8px 12px; border-radius:8px; opacity:0.88; }
  .admin-nav a:hover{ opacity:1; background:rgba(244,236,198,0.14); }
  .admin-account{ display:flex; align-items:center; gap:10px; font-size:13px; opacity:0.85; }
  .admin-account form{ margin:0; }
  .admin-account button{ font:inherit; font-size:13px; background:transparent; border:1px solid rgba(244,236,198,0.5); color:#f4ecc6; padding:7px 12px; border-radius:8px; cursor:pointer; }
  .admin-account button:hover{ background:rgba(244,236,198,0.14); }
  .admin-main{ max-width:1080px; margin:0 auto; padding:32px 24px 64px; }
  h1{ font-size:26px; font-weight:700; color:#884858; margin:0 0 20px; }
  .card{ background:#f6efde; border:1px solid rgba(136,72,88,0.16); border-radius:16px; padding:24px; margin-bottom:24px; }
  table{ width:100%; border-collapse:collapse; font-size:14px; }
  th,td{ text-align:left; padding:10px 12px; border-bottom:1px solid rgba(136,72,88,0.14); vertical-align:top; }
  th{ font-size:12px; text-transform:uppercase; letter-spacing:0.06em; color:#6f3a47; }
  .badge-yes{ color:#1f7a4d; font-weight:700; }
  .badge-no{ color:#9a2b2b; font-weight:700; }
  .group-card{ background:#f6efde; border:1px solid rgba(136,72,88,0.16); border-radius:16px; margin-bottom:28px; overflow:hidden; }
  .group-header{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 20px; background:rgba(136,72,88,0.08); border-bottom:2px dashed rgba(136,72,88,0.35); font-weight:700; color:#884858; }
  .group-header .group-count{ font-size:12px; font-weight:700; background:#884858; color:#f4ecc6; padding:4px 12px; border-radius:999px; white-space:nowrap; }
  .group-card table{ margin:0; }
  .group-card tr:last-child td{ border-bottom:none; }
  tr.row-deleted{ opacity:0.5; text-decoration:line-through; }
  .row-action-btn{ font:inherit; font-size:12px; background:transparent; border:1px solid rgba(136,72,88,0.4); color:#884858; padding:6px 12px; border-radius:8px; cursor:pointer; white-space:nowrap; }
  .row-action-btn:hover{ background:rgba(136,72,88,0.1); }
  label{ display:block; font-size:13px; font-weight:700; color:#884858; margin-bottom:6px; }
  input[type=email], input[type=password], input[type=text]{ width:100%; max-width:360px; padding:10px 12px; border:1.5px solid rgba(136,72,88,0.25); border-radius:10px; font-size:15px; margin-bottom:16px; }
  button[type=submit], .btn{ background:#884858; color:#f4ecc6; font-weight:700; border:none; padding:11px 20px; border-radius:999px; cursor:pointer; font-size:14px; }
  button[type=submit]:hover, .btn:hover{ background:#6f3a47; }
  .msg-error{ background:#fbe3e3; color:#9a2b2b; border-radius:10px; padding:10px 14px; font-size:14px; margin-bottom:16px; }
  .msg-ok{ background:#e3f3ea; color:#1f7a4d; border-radius:10px; padding:10px 14px; font-size:14px; margin-bottom:16px; }
  .hint{ font-size:13px; opacity:0.7; margin-top:-10px; margin-bottom:16px; }
</style>
</head>
<body>
<header class="admin-header">
  <a class="brand" href="<?= $admin ? 'index.php' : 'login.php' ?>">NŌMA people · Administrace</a>
  <?php if ($admin): ?>
  <nav class="admin-nav">
    <a href="index.php">Registrace</a>
    <a href="settings.php">Nastavení</a>
    <a href="users.php">Uživatelé</a>
    <a href="account.php">Můj účet</a>
    <a href="../index.html" target="_blank" rel="noopener">Přejít na web</a>
  </nav>
  <div class="admin-account">
    <span><?= esc($admin['email']) ?></span>
    <form method="post" action="logout.php">
      <?php csrf_field(); ?>
      <button type="submit">Odhlásit</button>
    </form>
  </div>
  <?php endif; ?>
</header>
<main class="admin-main">
<h1><?= esc($pageTitle) ?></h1>

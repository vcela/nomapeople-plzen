<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';

auth_bootstrap();
$admin = require_admin();
$pageTitle = 'Registrace';

$registrations = db()->query(
    'SELECT id, name, email, phone, note, gdpr_consent, photo_consent, created_at FROM registrations ORDER BY created_at DESC'
)->fetchAll();

require __DIR__ . '/inc/header.php';
?>
<div class="card">
  <p style="margin-top:0;">Celkem přihlášených: <strong><?= count($registrations) ?></strong></p>
  <table>
    <thead>
      <tr>
        <th>Datum</th>
        <th>Jméno</th>
        <th>E-mail</th>
        <th>Telefon</th>
        <th>Poznámka</th>
        <th>GDPR</th>
        <th>Foto/video</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$registrations): ?>
      <tr><td colspan="7">Zatím žádné přihlášky.</td></tr>
      <?php endif; ?>
      <?php foreach ($registrations as $r): ?>
      <tr>
        <td><?= esc(date('d.m.Y H:i', strtotime($r['created_at']))) ?></td>
        <td><?= esc($r['name']) ?></td>
        <td><a href="mailto:<?= esc($r['email']) ?>"><?= esc($r['email']) ?></a></td>
        <td><?= esc($r['phone'] ?: '—') ?></td>
        <td><?= nl2br(esc($r['note'] ?: '—')) ?></td>
        <td><?= $r['gdpr_consent'] ? '<span class="badge-yes">Ano</span>' : '<span class="badge-no">Ne</span>' ?></td>
        <td><?= $r['photo_consent'] ? '<span class="badge-yes">Ano</span>' : '<span class="badge-no">Ne</span>' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>

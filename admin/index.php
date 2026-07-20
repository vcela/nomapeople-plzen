<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';

auth_bootstrap();
$admin = require_admin();
$pageTitle = 'Registrace';

// "Delete" here is a visual soft-delete only — deleted_at just gets set/
// cleared, the row is still shown (struck through, at the end of its
// lesson-week group) and still counts toward nothing being lost. Toggling
// back (deleted_at -> NULL) is intentional so a misclick is never final.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_delete') {
    if (csrf_verify($_POST['csrf'] ?? null)) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('SELECT deleted_at FROM registrations WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                $newValue = $row['deleted_at'] ? null : date('Y-m-d H:i:s');
                db()->prepare('UPDATE registrations SET deleted_at = ? WHERE id = ?')->execute([$newValue, $id]);
            }
        }
    }
    admin_redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hard_delete') {
    if (csrf_verify($_POST['csrf'] ?? null)) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db()->prepare('DELETE FROM registrations WHERE id = ?')->execute([$id]);
        }
    }
    admin_redirect('index.php');
}

// Which upcoming lesson a registration "belongs to": the next occurrence of
// the configured weekday on or after the day it was created (today counts
// as itself, so a same-day signup groups with that day's lesson).
function lesson_bucket_date(string $createdAt, int $weekday): string {
    $day = (new DateTimeImmutable($createdAt))->setTime(0, 0);
    $currentIso = (int) $day->format('N');
    $diff = ($weekday - $currentIso + 7) % 7;
    return $day->modify("+{$diff} days")->format('Y-m-d');
}

function czech_count_label(int $n, string $one, string $few, string $many): string {
    if ($n === 1) return $one;
    if ($n >= 2 && $n <= 4) return $few;
    return $many;
}

// Colored pill for the "Zdroj" column so the traffic category (přímá /
// vyhledávač / reklama / odkaz / kampaň) is readable at a glance without
// having to parse the label text itself.
function source_badge(string $type, ?string $label): string {
    $styles = [
        'ads'      => ['bg' => '#fbe3e3', 'fg' => '#9a2b2b', 'icon' => '📢'],
        'search'   => ['bg' => '#e3edf3', 'fg' => '#2a5f7a', 'icon' => '🔍'],
        'referral' => ['bg' => '#eee3f3', 'fg' => '#5b2a7a', 'icon' => '🔗'],
        'campaign' => ['bg' => '#f3ecdd', 'fg' => '#8a6a1f', 'icon' => '✉️'],
        'direct'   => ['bg' => '#e3f3ea', 'fg' => '#1f7a4d', 'icon' => '➜'],
    ];
    $s = $styles[$type] ?? $styles['direct'];
    $text = $label !== null && $label !== '' ? $label : 'Přímá návštěva';
    return '<span title="' . esc($text) . '" style="display:inline-block; max-width:200px; overflow:hidden; text-overflow:ellipsis; vertical-align:bottom; background:' . $s['bg'] . '; color:' . $s['fg']
        . '; font-weight:700; font-size:12px; padding:4px 10px; border-radius:999px; white-space:nowrap;">'
        . $s['icon'] . ' ' . esc($text) . '</span>';
}

$stmt = db()->query("SELECT setting_value FROM settings WHERE setting_key = 'lesson_weekday'");
$weekdaySetting = $stmt->fetch();
$lessonWeekday = $weekdaySetting ? (int) $weekdaySetting['setting_value'] : 1;

$registrations = db()->query(
    'SELECT id, name, email, phone, note, gdpr_consent, photo_consent, created_at, deleted_at, source_type, source_label FROM registrations ORDER BY created_at DESC'
)->fetchAll();

$groups = []; // bucket date (Y-m-d) => ['active' => [...rows], 'deleted' => [...rows]]
foreach ($registrations as $r) {
    $bucket = lesson_bucket_date($r['created_at'], $lessonWeekday);
    if (!isset($groups[$bucket])) {
        $groups[$bucket] = ['active' => [], 'deleted' => []];
    }
    if ($r['deleted_at']) {
        $groups[$bucket]['deleted'][] = $r;
    } else {
        $groups[$bucket]['active'][] = $r;
    }
}
krsort($groups); // 'Y-m-d' bucket keys sort correctly as strings -> soonest/most recent lesson first

// "Next event" = the lesson-week bucket a registration made right now would fall into.
$nextBucket = lesson_bucket_date(date('Y-m-d H:i:s'), $lessonWeekday);
$nextEventCount = count($groups[$nextBucket]['active'] ?? []);

$nextEventEmails = array_values(array_unique(array_column($groups[$nextBucket]['active'] ?? [], 'email')));
sort($nextEventEmails);

$allEmails = array_values(array_unique(array_column($registrations, 'email')));
sort($allEmails);

require __DIR__ . '/inc/header.php';
?>
<div class="card">
  <p style="margin-top:0;">Přihlášených na nejbližší lekci: <strong><?= $nextEventCount ?></strong></p>
  <details>
    <summary style="cursor:pointer; font-weight:700; color:#884858;">E-maily na nejbližší lekci (<?= count($nextEventEmails) ?>)</summary>
    <textarea readonly rows="3" onclick="this.select();" style="width:100%; margin-top:10px; padding:10px; border:1.5px solid rgba(136,72,88,0.25); border-radius:10px; font-size:13px; font-family:inherit;"><?= esc(implode(', ', $nextEventEmails)) ?></textarea>
  </details>
  <details style="margin-top:14px;">
    <summary style="cursor:pointer; font-weight:700; color:#884858;">Všechny e-maily, co se kdy přihlásily (<?= count($allEmails) ?>)</summary>
    <textarea readonly rows="4" onclick="this.select();" style="width:100%; margin-top:10px; padding:10px; border:1.5px solid rgba(136,72,88,0.25); border-radius:10px; font-size:13px; font-family:inherit;"><?= esc(implode(', ', $allEmails)) ?></textarea>
  </details>
</div>

<?php if (!$registrations): ?>
<div class="card">Zatím žádné přihlášky.</div>
<?php endif; ?>

<?php foreach ($groups as $bucketDate => $group): ?>
<?php
  $dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $bucketDate);
  $activeCount = count($group['active']);
  $rows = array_merge($group['active'], $group['deleted']);
?>
<details class="group-card"<?= $bucketDate === $nextBucket ? ' open' : '' ?>>
  <summary class="group-header">
    <span class="group-title"><span class="chevron">▸</span> <?= esc(czech_weekday_name((int) $dateObj->format('N'))) ?> <?= esc($dateObj->format('d.m.Y')) ?></span>
    <span class="group-count"><?= $activeCount ?> <?= esc(czech_count_label($activeCount, 'přihlášený', 'přihlášení', 'přihlášených')) ?></span>
  </summary>
  <div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th class="col-sticky-left">Jméno</th>
        <th>Datum</th>
        <th>E-mail</th>
        <th>Telefon</th>
        <th>Poznámka</th>
        <th>Zdroj</th>
        <th>GDPR</th>
        <th>Foto/video</th>
        <th class="col-sticky-right">Akce</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr<?= $r['deleted_at'] ? ' class="row-deleted"' : '' ?>>
        <td class="col-sticky-left"><?= esc($r['name']) ?></td>
        <td><?= esc(date('d.m.Y H:i', strtotime($r['created_at']))) ?></td>
        <td><a href="mailto:<?= esc($r['email']) ?>"><?= esc($r['email']) ?></a></td>
        <td><?= esc($r['phone'] ?: '—') ?></td>
        <td><?= nl2br(esc($r['note'] ?: '—')) ?></td>
        <td><?= source_badge($r['source_type'] ?: 'direct', $r['source_label']) ?></td>
        <td><?= $r['gdpr_consent'] ? '<span class="badge-yes">Ano</span>' : '<span class="badge-no">Ne</span>' ?></td>
        <td><?= $r['photo_consent'] ? '<span class="badge-yes">Ano</span>' : '<span class="badge-no">Ne</span>' ?></td>
        <td class="col-sticky-right" style="white-space:nowrap;">
          <form method="post" action="index.php" style="display:inline-block; margin:0;">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="toggle_delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="row-action-btn"><?= $r['deleted_at'] ? 'Ale přijde' : 'Nepřijde' ?></button>
          </form>
          <form method="post" action="index.php" style="display:inline-block; margin:0;" data-confirm="Opravdu trvale smazat záznam <?= esc($r['name']) ?>? Tuto akci nelze vrátit zpět.">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="hard_delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="row-action-btn row-action-danger" title="Trvale smazat">✕</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</details>
<?php endforeach; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>

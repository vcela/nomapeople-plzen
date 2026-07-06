<?php
// Outbound email via SMTP (PHPMailer, vendored manually — no composer on
// this host). A failed send is logged but never thrown: a bounced or
// misconfigured mailbox must not undo an already-stored registration.

require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;

function make_mailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    return $mail;
}

function get_notification_email(): ?string {
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute(['notification_email']);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : null;
}

function send_admin_notification(array $reg): void {
    try {
        $to = get_notification_email();
        if (!$to) return;
        $mail = make_mailer();
        $mail->addAddress($to);
        if (!empty($reg['email'])) {
            $mail->addReplyTo($reg['email'], $reg['name']);
        }
        $mail->isHTML(true);
        $mail->Subject = 'Nová přihláška – kontaktní improvizace Plzeň';

        $rows = [
            'Jméno' => $reg['name'],
            'E-mail' => $reg['email'],
            'Telefon' => $reg['phone'] ?: '—',
            'Poznámka' => $reg['note'] ?: '—',
            'Souhlas GDPR' => $reg['gdpr_consent'] ? 'Ano' : 'Ne',
            'Souhlas foto/video' => $reg['photo_consent'] ? 'Ano' : 'Ne',
        ];
        $html = '<h2>Nová přihláška na kontaktní improvizaci</h2><table cellpadding="6" cellspacing="0">';
        foreach ($rows as $label => $value) {
            $html .= '<tr><td><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong></td><td>'
                . nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) . '</td></tr>';
        }
        $html .= '</table>';

        $mail->Body = $html;
        $mail->AltBody = strip_tags(str_replace(['<tr>', '</tr>'], ["\n", ''], $html));
        $mail->send();
    } catch (Throwable $e) {
        error_log('[mailer] admin notification failed: ' . $e->getMessage());
    }
}

function send_registrant_confirmation(array $reg): void {
    try {
        $mail = make_mailer();
        $mail->addAddress($reg['email'], $reg['name']);
        $mail->isHTML(true);
        $mail->Subject = 'Kontaktní improvizace Plzeň – letní pondělky';

        $name = htmlspecialchars($reg['name'], ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
<p>Ahoj,</p>
<p>mám velkou radost, že ses přihlásil/a na letní sérii lekcí kontaktní improvizace v Plzni. Děkuji za důvěru a těším se na společné objevování pohybu, naslouchání, hravosti a kontaktu.</p>
<p><strong>Praktické informace</strong></p>
<p>📅 <strong>Kdy:</strong> 22. 6. – 31. 8. 2026, každé pondělí 18:00–20:00<br>
📍 <strong>Kde:</strong> Pohybový Ateliér J7, Jablonského 7, Plzeň 2–Slovany<br>
Zazvoň na Pohybový Ateliér a projdi rovně přes divo-zahradu až do ateliéru :)</p>
<p>💰 <strong>Cena:</strong> 300 Kč za lekci (2 hodiny)<br>
Platba na místě v hotovosti nebo přes QR kód.</p>
<p>Prosím, doraž alespoň <strong>15 minut před začátkem</strong>, ať máš čas se v klidu naladit, převléknout a přivítat.</p>
<p>Budeme bosky nebo v ponožkách. Vezmi si pohodlné oblečení, ve kterém se můžeš volně hýbat (ideálně s delšími rukávy a nohavicemi), a láhev vody.</p>
<p>Pokud by ses na cokoliv chtěl/a zeptat, stačí odpovědět na tento e-mail.</p>
<p>Moc se těším na společné pondělky :)</p>
<p>P.S. Pokud bys chtěl/a pozvat kamarádku nebo kamaráda, kterému by to mohlo sedět, klidně předej info dál – přihlášení i všechny detaily najdeš na <a href="https://nomapeople.com/plzen">nomapeople.com/plzen</a>.</p>
<p>Těším se na tebe!<br>NŌMA people</p>
HTML;

        $altBody = <<<TEXT
Ahoj {$name},

mám velkou radost, že ses přihlásil/a na letní sérii lekcí kontaktní improvizace v Plzni. Děkuji za důvěru a těším se na společné objevování pohybu, naslouchání, hravosti a kontaktu.

Praktické informace

Kdy: 22. 6. – 31. 8. 2026, každé pondělí 18:00–20:00
Kde: Pohybový Ateliér J7, Jablonského 7, Plzeň 2–Slovany
Zazvoň na Pohybový Ateliér a projdi rovně přes divo-zahradu až do ateliéru :)

Cena: 300 Kč za lekci (2 hodiny)
Platba na místě v hotovosti nebo přes QR kód.

Prosím, doraž alespoň 15 minut před začátkem, ať máš čas se v klidu naladit, převléknout a přivítat.

Budeme bosky nebo v ponožkách. Vezmi si pohodlné oblečení, ve kterém se můžeš volně hýbat (ideálně s delšími rukávy a nohavicemi), a láhev vody.

Pokud by ses na cokoliv chtěl/a zeptat, stačí odpovědět na tento e-mail.

Moc se těším na společné pondělky :)

P.S. Pokud bys chtěl/a pozvat kamarádku nebo kamaráda, kterému by to mohlo sedět, klidně předej info dál – přihlášení i všechny detaily najdeš na https://nomapeople.com/plzen

Těším se na tebe!
NŌMA people
TEXT;

        $mail->Body = $html;
        $mail->AltBody = $altBody;
        $mail->send();
    } catch (Throwable $e) {
        error_log('[mailer] registrant confirmation failed: ' . $e->getMessage());
    }
}

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
        $mail->Subject = 'Potvrzení přihlášky – kontaktní improvizace Plzeň';

        $name = htmlspecialchars($reg['name'], ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
<p>Ahoj {$name},</p>
<p>díky za přihlášení na letní sérii lekcí kontaktní improvizace v Plzni! Jsi v hře.</p>
<p>
  <strong>Kdy:</strong> 22. 6. – 31. 8. 2026, každé pondělí 18–20 h<br>
  <strong>Kde:</strong> Pohybový Ateliér J7, Jablonského 7, Plzeň 2-Slovany<br>
  <strong>Cena:</strong> 300 Kč za lekci (2 hodiny), platba na místě
</p>
<p>Doraž prosím 10 minut před začátkem, ať se můžeš pomalu naladit, protáhnout a přivítat. Budeme bosky nebo v ponožkách — vezmi si pohodlné oblečení (ideálně s dlouhými rukávy a nohavicemi) a láhev vody.</p>
<p>Těšíme se na tebe!<br>NŌMA people</p>
HTML;

        $mail->Body = $html;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $html));
        $mail->send();
    } catch (Throwable $e) {
        error_log('[mailer] registrant confirmation failed: ' . $e->getMessage());
    }
}

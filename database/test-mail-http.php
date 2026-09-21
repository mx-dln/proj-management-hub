<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$base = $argv[1] ?? 'http://127.0.0.1:8088';
if (!preg_match('~^http://127\.0\.0\.1:\d+$~', $base)) throw new RuntimeException('Local preview only.');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/Mailer.php';
$mailer = new Mailer(Database::getInstance()->getConnection());
if ($mailer->settings()['mail_enabled'] !== '0') throw new RuntimeException('HTTP tests require SMTP to remain disabled.');
function httpCheck($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}
function sessionClient() {
    $curl = curl_init();
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 20]);
    return $curl;
}
function mailRequest($curl, $path, $data = null) {
    global $base;
    curl_setopt($curl, CURLOPT_URL, $base . $path);
    curl_setopt($curl, CURLOPT_POST, $data !== null);
    if ($data !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    $body = curl_exec($curl);
    return [curl_getinfo($curl, CURLINFO_RESPONSE_CODE), $body];
}
$admin = sessionClient();
[$status] = mailRequest($admin, '/ajax/mail.php?action=status');
httpCheck($status === 401, 'Guest cannot access mail settings');
mailRequest($admin, '/login.php', ['username' => 'admin', 'password' => 'password123']);
[$status, $html] = mailRequest($admin, '/index.php?module=settings');
$dom = new DOMDocument();
@$dom->loadHTML($html);
$panel = $dom->getElementById('settings-mail');
$csrf = $panel ? $panel->getAttribute('data-csrf') : '';
httpCheck($status === 200 && strlen($csrf) === 64, 'Admin settings includes mail form and CSRF token');
[$status, $body] = mailRequest($admin, '/ajax/mail.php?action=status');
$data = json_decode($body, true);
httpCheck($status === 200 && $data['queue']['available'] && !str_contains($body, 'mail_password'), 'Admin can inspect queue without exposing credentials');
[$status] = mailRequest($admin, '/ajax/mail.php?action=save');
httpCheck($status === 405, 'Settings changes require POST');
foreach (['save', 'test', 'process', 'retry'] as $action) {
    [$status] = mailRequest($admin, '/ajax/mail.php?action=' . $action, ['mail_host' => 'smtp.example.invalid']);
    httpCheck($status === 403, $action . ' requires a valid CSRF token');
}
[$status] = mailRequest($admin, '/ajax/mail.php?action=save', ['csrf' => $csrf, 'mail_host' => 'smtp://bad-host', 'mail_port' => '587', 'mail_encryption' => 'tls']);
httpCheck($status === 422, 'Invalid SMTP configuration is rejected');
[$status] = mailRequest($admin, '/ajax/settings.php?action=save_general', ['csrf' => $csrf, 'mail_enabled' => '1', 'mail_password' => 'should-not-save']);
httpCheck($status === 200 && $mailer->settings()['mail_enabled'] === '0' && $mailer->settings()['mail_password'] === '', 'General settings cannot overwrite SMTP controls or credentials');
foreach (['faculty1', 'viewer'] as $username) {
    $client = sessionClient();
    mailRequest($client, '/login.php', ['username' => $username, 'password' => 'password123']);
    foreach (['status', 'save', 'test', 'process', 'retry'] as $action) {
        [$status] = mailRequest($client, '/ajax/mail.php?action=' . $action, $action === 'status' ? null : ['csrf' => $csrf]);
        httpCheck($status === 403, $username . ' cannot access mail action ' . $action);
    }
}
foreach (['/config/mail.key', '/vendor/phpmailer/phpmailer/src/PHPMailer.php', '/composer.lock'] as $path) {
    [$status] = mailRequest($admin, $path);
    httpCheck($status === 403, $path . ' is not publicly served');
}
httpCheck($mailer->settings()['mail_enabled'] === '0', 'SMTP remains disabled; no test emails sent');

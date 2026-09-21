<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/EmailQueue.php';

function mailCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}
function mailReject(callable $fn, string $message): void {
    try { $fn(); } catch (InvalidArgumentException $e) { mailCheck(true, $message); return; }
    throw new RuntimeException('Expected rejection: ' . $message);
}
class TestMailer extends Mailer {
    public bool $fail = false;
    public array $delivered = [];
    public function send(string $email, string $name, string $title, string $message, string $actionUrl): void {
        if ($this->fail) throw new RuntimeException('Simulated SMTP outage.');
        $this->delivered[] = compact('email', 'title', 'message');
    }
}

$db = db();
$testDatabase = 'mail_test_' . bin2hex(random_bytes(5));
$keyPath = sys_get_temp_dir() . '/isu-mail-test-' . bin2hex(random_bytes(6)) . '.key';
$schema = [];
foreach (['users', 'notifications', 'settings'] as $table) $schema[] = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
$db->exec("CREATE DATABASE `$testDatabase`");
$process = null;
try {
    $db->exec("USE `$testDatabase`");
    foreach ($schema as $sql) $db->exec($sql);
    $db->exec(file_get_contents(__DIR__ . '/migrations/20260920_mail.sql'));
    $db->exec(file_get_contents(__DIR__ . '/migrations/20260920_mail.sql'));
    mailCheck(true, 'Migration is repeatable');
    $db->exec("INSERT INTO users (username, email, password, role) VALUES ('smtp_test', 'test@example.invalid', 'unused', 'faculty')");
    $userId = (int)$db->lastInsertId();
    $mailer = new TestMailer($db, $keyPath);
    $queue = new EmailQueue($db, $mailer);
    createNotification($userId, 'Disabled test', 'No email', 'info', 'proposal');
    mailCheck((int)$db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn() === 0, 'SMTP disabled by default; in-app notifications still work');
    $config = array_replace(Mailer::DEFAULTS, [
        'mail_enabled' => '1', 'mail_host' => '127.0.0.1', 'mail_auth' => '0', 'mail_encryption' => 'none',
        'mail_from_email' => 'sender@example.invalid', 'mail_app_url' => 'https://projects.example.edu/hub',
    ]);
    $mailer->save($config);
    mailCheck((int)$db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn() === 0, 'Enabling SMTP does not email historical notifications');
    mailReject(fn() => $mailer->save(array_replace($config, ['mail_from_name' => "Bad\r\nBcc: x@example.invalid"])), 'Reject header injection');
    mailReject(fn() => $mailer->save(array_replace($config, ['mail_host' => 'host;second-host'])), 'Reject multiple SMTP hosts and schemes');
    mailReject(fn() => $mailer->save(array_replace($config, ['mail_app_url' => 'https://user:pass@example.edu'])), 'Reject credentials in application URL');
    mailReject(fn() => $mailer->save(array_replace($config, ['mail_auth' => '1', 'mail_username' => 'test'])), 'Require credentials and encryption for authenticated SMTP');
    $protected = array_replace($config, ['mail_auth' => '1', 'mail_encryption' => 'tls', 'mail_username' => 'smtp-user', 'mail_password' => 'synthetic-test-password']);
    $mailer->save($protected);
    $encrypted = $mailer->settings()['mail_password'];
    mailCheck($encrypted !== 'synthetic-test-password' && is_file($keyPath), 'SMTP password is encrypted with a separate server key');
    mailCheck(!isset($mailer->publicSettings()['mail_password']) && $mailer->publicSettings()['password_configured'], 'Password never appears in public settings');
    $protected['mail_password'] = '';
    $mailer->save($protected);
    mailCheck($mailer->settings()['mail_password'] === $encrypted, 'Blank password retains saved credentials');
    $mailer->save(array_replace($config, ['clear_password' => '1']));
    mailCheck(!$mailer->publicSettings()['password_configured'], 'Explicit password removal works');

    createNotification($userId, 'Proposal approved', 'Approved &amp; ready', 'success', 'proposal', null, '/index.php?module=proposals');
    $notificationId = (int)$db->query('SELECT MAX(id) FROM notifications')->fetchColumn();
    $queue->enqueue($notificationId, 'proposal');
    mailCheck((int)$db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn() === 1, 'Notification hook queues once per notification');
    $db->beginTransaction();
    createNotification($userId, 'Rolled back', 'Never send', 'info', 'report');
    $db->rollBack();
    mailCheck((int)$db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn() === 1, 'Queue insert rolls back with its notification');
    $mailer->save(array_replace($config, ['mail_reports' => '0']));
    createNotification($userId, 'Report submitted', 'Disabled category', 'info', 'report');
    createNotification($userId, 'Unmapped event', 'No email', 'info', 'moa');
    mailCheck((int)$db->query('SELECT COUNT(*) FROM email_queue')->fetchColumn() === 1, 'Disabled and unsupported categories stay in-app only');
    $mailer->save($config);
    $result = $queue->process();
    mailCheck($result['sent'] === 1 && count($mailer->delivered) === 1, 'Worker delivers to the notification recipient');
    mailCheck($queue->process()['sent'] === 0, 'Sent emails are not delivered again');
    createNotification($userId, 'New assignment', 'Assigned', 'info', 'activity');
    $db->exec("UPDATE users SET is_active = 0 WHERE id = {$userId}");
    mailCheck($queue->process()['cancelled'] === 1, 'Delivery skips deactivated recipients');
    createNotification($userId, 'Inactive user', 'No email', 'info', 'proposal');
    mailCheck((int)$db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn() === 0, 'Inactive users are not queued');
    $db->exec("UPDATE users SET is_active = 1 WHERE id = {$userId}");

    createNotification($userId, 'Report returned', 'Please revise', 'warning', 'report');
    $mailer->fail = true;
    mailCheck($queue->process()['retrying'] === 1, 'SMTP failure schedules retry without losing the notification');
    mailCheck($queue->process()['retrying'] === 0, 'Retry delay prevents immediate repeated attempts');
    for ($attempt = 2; $attempt <= 3; $attempt++) {
        $db->exec("UPDATE email_queue SET available_at = NOW() WHERE status = 'pending'");
        $result = $queue->process();
    }
    mailCheck($result['failed'] === 1 && $queue->status()['failed'] === 1, 'Three failed attempts stop automatic retries');
    mailCheck($queue->retryFailed() === 1, 'Administrator can requeue failed emails');
    $mailer->fail = false;
    mailCheck($queue->process()['sent'] === 1, 'Requeued email can be delivered');
    $mailer->save(array_replace($config, ['mail_enabled' => '0']));
    mailCheck($queue->process()['paused'], 'Disabling SMTP pauses delivery');
    $mailer->save($config);
    $other = new PDO('mysql:host=' . DB_HOST . ';dbname=' . $testDatabase, DB_USER, DB_PASS);
    $lockName = 'isu_mail_' . sha1($testDatabase);
    $other->prepare('SELECT GET_LOCK(?, 0)')->execute([$lockName]);
    mailCheck($queue->process()['busy'], 'Overlapping workers cannot send the same batch');
    $other->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);

    $content = Mailer::content('<script>alert(1)</script>', 'A &amp; B', 'https://outside.example/phish', $config['mail_app_url']);
    mailCheck(!str_contains($content['html'], '<script>') && !str_contains($content['html'], 'outside.example') && str_contains($content['text'], 'A & B'), 'Templates escape HTML, decode stored text, and keep links inside the app');
    $process = proc_open([PHP_BINARY, __DIR__ . '/test-smtp-server.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start SMTP test capture.');
    stream_set_timeout($pipes[1], 25);
    $address = trim((string)fgets($pipes[1]));
    if (!preg_match('/^127\.0\.0\.1:(\d+)$/', $address, $match)) throw new RuntimeException('SMTP capture did not start.');
    $mailer->save(array_replace($config, ['mail_port' => $match[1]]));
    (new Mailer($db, $keyPath))->send('test@example.invalid', 'Test Recipient', 'SMTP integration test', 'Synthetic test only', '/index.php?module=proposals');
    $capture = json_decode(trim(stream_get_contents($pipes[1])), true);
    foreach ($pipes as $pipe) fclose($pipe);
    $exit = proc_close($process);
    $process = null;
    mailCheck($exit === 0 && str_contains($capture['message'] ?? '', 'multipart/alternative') && str_contains($capture['message'], 'SMTP integration test') && str_contains($capture['message'], '/hub/index.php?module=proposals'), 'Real PHPMailer SMTP exchange produces HTML, plain text, and correct links');
    try {
        (new Mailer($db, $keyPath))->send('test@example.invalid', 'Test', 'Connection failure', 'Test', '');
        throw new LogicException('Closed SMTP capture should reject connections.');
    } catch (RuntimeException $e) {
        mailCheck(str_starts_with($e->getMessage(), 'SMTP connection failed.'), 'Connection failures return useful errors without server details');
    }
    $db->exec('RENAME TABLE email_queue TO unavailable_email_queue');
    createNotification($userId, 'Queue unavailable', 'Still delivered in-app', 'info', 'proposal');
    mailCheck((int)$db->query("SELECT COUNT(*) FROM notifications WHERE title = 'Queue unavailable'")->fetchColumn() === 1, 'Queue outage does not break in-app notifications');
    echo "All mail tests passed. No email left the loopback capture server.\n";
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if ($db->inTransaction()) $db->rollBack();
    $db->exec('USE `' . DB_NAME . '`');
    $db->exec("DROP DATABASE `$testDatabase`");
    if (is_file($keyPath)) unlink($keyPath);
}

<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';
require_once __DIR__ . '/../includes/EmailQueue.php';

if (!isLoggedIn()) jsonResponse(['success' => false, 'message' => 'Sign in to continue.'], 401);
$stmt = db()->prepare('SELECT role, email, username FROM users WHERE id = ? AND is_active = 1 AND deleted_at IS NULL');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'admin') jsonResponse(['success' => false, 'message' => 'Only administrators can manage email.'], 403);
$_SESSION['role'] = $user['role'];
Permissions::requirePermission(Permissions::canManageSettings());
header('Cache-Control: no-store');
$action = $_GET['action'] ?? 'status';
$mailer = new Mailer(db());
$queue = new EmailQueue(db(), $mailer);
if ($action === 'status') jsonResponse(['success' => true, 'queue' => $queue->status()]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'message' => 'POST required.'], 405);
if (empty($_SESSION['settings_csrf']) || !is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['settings_csrf'], $_POST['csrf'])) jsonResponse(['success' => false, 'message' => 'Session expired. Refresh and try again.'], 403);
try {
    switch ($action) {
        case 'save':
            if (($_POST['mail_enabled'] ?? '') === '1' && !$queue->status()['available']) throw new RuntimeException('Install the email queue migration before enabling email.');
            $mailer->save($_POST);
            auditLog('edit', 'settings', null, 'Updated SMTP settings and email preferences');
            jsonResponse(['success' => true, 'message' => 'Email settings saved.', 'settings' => $mailer->publicSettings()]);
        case 'test':
            if (time() - (int)($_SESSION['mail_last_test'] ?? 0) < 30) jsonResponse(['success' => false, 'message' => 'Wait 30 seconds before sending another test.'], 429);
            $_SESSION['mail_last_test'] = time();
            session_write_close();
            $mailer->send($user['email'], $user['username'], 'SMTP test - ISU Project Hub', 'Your saved SMTP settings successfully sent this test message.', '/index.php?module=notifications');
            auditLog('email_test', 'settings', null, 'SMTP server accepted a test email for the signed-in administrator');
            jsonResponse(['success' => true, 'message' => 'SMTP server accepted the test email. Check your inbox and spam folder.']);
        case 'process':
            session_write_close();
            $result = $queue->process(5);
            $message = $result['paused'] ? 'Email delivery is disabled.' : ($result['busy'] ? 'The mail worker is already running.' : "Sent: {$result['sent']}. Retrying: {$result['retrying']}. Failed: {$result['failed']}. Cancelled: {$result['cancelled']}.");
            jsonResponse(['success' => true, 'message' => $message, 'queue' => $queue->status()]);
        case 'retry':
            $count = $queue->retryFailed();
            auditLog('email_retry', 'settings', null, 'Requeued ' . $count . ' failed emails');
            jsonResponse(['success' => true, 'message' => $count . ' failed emails queued for retry.', 'queue' => $queue->status()]);
        default:
            jsonResponse(['success' => false, 'message' => 'Unknown mail action.'], 400);
    }
} catch (InvalidArgumentException $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
} catch (RuntimeException $e) {
    if ($e instanceof PDOException) {
        error_log('Mail settings database operation failed.');
        jsonResponse(['success' => false, 'message' => 'Email settings could not be updated. Check the database migration.'], 500);
    }
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 503);
} catch (Throwable $e) {
    error_log('Mail settings operation failed.');
    jsonResponse(['success' => false, 'message' => 'The email operation failed. Check the server configuration.'], 500);
}

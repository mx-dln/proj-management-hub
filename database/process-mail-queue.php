<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/EmailQueue.php';
$options = getopt('', ['limit:']);
try {
    $result = (new EmailQueue(Database::getInstance()->getConnection()))->process((int)($options['limit'] ?? 25));
    echo json_encode($result, JSON_PRETTY_PRINT), PHP_EOL;
    exit($result['failed'] > 0 || $result['retrying'] > 0 ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, "Mail queue could not run. Verify the migration and server configuration.\n");
    exit(1);
}

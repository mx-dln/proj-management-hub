<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'isu-proj-hub');
define('DB_USER', 'root');
define('DB_PASS', '');


define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME', 'ETS Project Management Hub');
if (!defined('SITE_URL')) {
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $scheme = $forwardedProto ? explode(',', $forwardedProto)[0] : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
    $host = $forwardedHost ? explode(',', $forwardedHost)[0] : ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = preg_replace('~/[^/]*$~', '', $scriptName);
    $basePath = rtrim($basePath ?: '', '/');
    if ($basePath === '/') $basePath = '';
    define('SITE_URL', rtrim($scheme . '://' . $host . $basePath, '/'));
}
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

define('ITEMS_PER_PAGE', 10);
define('DATE_FORMAT', 'M d, Y');
define('DATETIME_FORMAT', 'M d, Y h:i A');

date_default_timezone_set('Asia/Manila');

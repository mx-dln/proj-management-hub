<?php
// Load local/server configuration overrides if present
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'isu-proj-hub');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
if (!defined('SITE_NAME')) define('SITE_NAME', 'ETS Project Management Hub');
if (!defined('SITE_URL')) {
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $scheme = $forwardedProto ? explode(',', $forwardedProto)[0] : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
    $host = $forwardedHost ? explode(',', $forwardedHost)[0] : ($_SERVER['HTTP_HOST'] ?? 'localhost');
    
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = preg_replace('~/[^/]*$~', '', $scriptName);
    $basePath = preg_replace('~/(?:ajax|cron|api)$~i', '', $basePath);
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

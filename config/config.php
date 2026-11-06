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
    
    // Determine the base path of the application relative to web root
    $appDir = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
    $scriptFilename = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    $basePath = '';
    if ($appDir !== '' && $scriptFilename !== '' && str_starts_with($scriptFilename, $appDir)) {
        $relPath = substr($scriptFilename, strlen($appDir));
        if ($relPath !== '' && str_ends_with($scriptName, $relPath)) {
            $basePath = substr($scriptName, 0, -strlen($relPath));
        }
    } else {
        $basePath = preg_replace('~/(?:ajax|cron|modules|views|layouts|assets|includes|config)(?:/.*)?$~i', '', $scriptName);
        if ($basePath === $scriptName) {
            $basePath = preg_replace('~/[^/]*$~', '', $scriptName);
        }
    }
    $basePath = rtrim($basePath ?: '', '/');
    define('SITE_URL', rtrim($scheme . '://' . $host . $basePath, '/'));
}
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

define('ITEMS_PER_PAGE', 10);
define('DATE_FORMAT', 'M d, Y');
define('DATETIME_FORMAT', 'M d, Y h:i A');

date_default_timezone_set('Asia/Manila');

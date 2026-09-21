<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'isu-proj-hub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'ETS Project Management Hub');
if (!defined('SITE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'isu-proj-hub.test';
    define('SITE_URL', $scheme . '://' . $host);
}
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

define('ITEMS_PER_PAGE', 10);
define('DATE_FORMAT', 'M d, Y');
define('DATETIME_FORMAT', 'M d, Y h:i A');

date_default_timezone_set('Asia/Manila');

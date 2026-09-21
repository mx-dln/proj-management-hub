<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
Database::getInstance()->getConnection()->exec(file_get_contents(__DIR__ . '/migrations/20260920_mail.sql'));
echo "Email queue migration complete. SMTP remains disabled until configured in Settings.\n";

<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
$pdo->exec(file_get_contents(__DIR__ . '/migrations/20260921_designation.sql'));
echo "Designation category ready. Existing records and uploads were not moved.\n";

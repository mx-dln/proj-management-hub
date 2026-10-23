<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();
$columns = [
    ['proposals', 'proponent', 'VARCHAR(255) NULL AFTER title'],
    ['proposals', 'attachments', 'LONGTEXT NULL AFTER attachment'],
    ['accomplishment_reports', 'attachments', 'LONGTEXT NULL AFTER attachment'],
    ['moas', 'attachments', 'LONGTEXT NULL AFTER attachment'],
];

foreach ($columns as [$table, $column, $definition]) {
    $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $check->execute([$table, $column]);
    if (!(int)$check->fetchColumn()) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "Added {$table}.{$column}\n";
    } else {
        echo "Exists {$table}.{$column}\n";
    }
}

<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
$root = dirname(__DIR__);
$destination = $root . '/database/backups/explorer-' . date('Ymd-His');
if (!mkdir($destination, 0700, true)) throw new RuntimeException('Cannot create backup');
$pdo = Database::getInstance()->getConnection();
$pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
$pdo->beginTransaction();
$out = fopen($destination . '/database.sql', 'xb');
fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n");
foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    fwrite($out, $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1] . ";\n");
    foreach ($pdo->query("SELECT * FROM `$table`") as $row) {
        $values = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row));
        fwrite($out, "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n");
    }
}
fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($out);
$pdo->commit();
$zip = new ZipArchive();
if ($zip->open($destination . '/workspace.zip', ZipArchive::CREATE) !== true) throw new RuntimeException('Cannot create archive');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$count = 0;
foreach ($iterator as $file) {
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    if (str_starts_with($relative, 'database/backups/')) continue;
    if ($file->isFile()) { $zip->addFile($file->getPathname(), $relative); $count++; }
}
$zip->close();
echo $destination, PHP_EOL, $count, ' files archived; database snapshot saved.', PHP_EOL;

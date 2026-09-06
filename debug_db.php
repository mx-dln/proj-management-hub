<?php
require_once __DIR__ . '/config/helpers.php';
requireLogin();

echo "<h2>Database Debug</h2>";

// Check programs
$programs = db()->query("SELECT id, title, status FROM programs")->fetchAll();
echo "<h3>Programs (" . count($programs) . ")</h3>";
echo "<pre>"; print_r($programs); echo "</pre>";

// Check if deleted_at exists
$cols = db()->query("SHOW COLUMNS FROM programs")->fetchAll();
echo "<h3>Programs Table Columns</h3>";
echo "<pre>"; print_r(array_column($cols, 'Field')); echo "</pre>";

// Check projects
$projects = db()->query("SELECT id, program_id, title, status FROM projects LIMIT 5")->fetchAll();
echo "<h3>Projects (" . count($projects) . ")</h3>";
echo "<pre>"; print_r($projects); echo "</pre>";
?>

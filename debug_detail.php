<?php
require_once __DIR__ . '/config/helpers.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
echo "<h2>Testing Program Detail for ID: $id</h2>";

// Test query
$stmt = db()->prepare("SELECT * FROM programs WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

echo "<h3>Query Result:</h3>";
echo "<pre>";
if ($data) {
    echo "FOUND: " . json_encode($data, JSON_PRETTY_PRINT);
} else {
    echo "NOT FOUND";
}
echo "</pre>";

// Test without prepare
$programs = db()->query("SELECT id, title FROM programs")->fetchAll();
echo "<h3>All Programs:</h3>";
echo "<pre>"; print_r($programs); echo "</pre>";
?>

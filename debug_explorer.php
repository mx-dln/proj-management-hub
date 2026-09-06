<?php
require_once __DIR__ . '/config/helpers.php';
requireLogin();

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';

echo "<h2>Explorer Debug</h2>";
echo "<p>Action: $action</p>";
echo "<p>Type: $type</p>";
echo "<p>ID: $id</p>";

if ($action === 'detail' && $type === 'program' && $id) {
    echo "<h3>Testing Detail Query</h3>";
    
    try {
        $stmt = db()->prepare("SELECT * FROM programs WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        echo "<pre>";
        if ($data) {
            echo "FOUND: " . json_encode($data, JSON_PRETTY_PRINT);
        } else {
            echo "NOT FOUND";
        }
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
    }
}
?>

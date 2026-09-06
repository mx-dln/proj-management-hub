<?php
require_once __DIR__ . '/config/helpers.php';

$userId = 3; // Juan Dela Cruz's user_id
$facultyId = db()->prepare("SELECT id FROM faculty_profiles WHERE user_id = ?");
$facultyId->execute([$userId]);
$fid = $facultyId->fetch()['id'] ?? null;

echo "Juan's Faculty ID: " . ($fid ?? 'NOT FOUND') . "\n\n";

if ($fid) {
    echo "=== PROGRAM ASSIGNMENTS ===\n";
    $stmt = db()->prepare("SELECT pa.*, p.title FROM program_assignments pa JOIN programs p ON pa.program_id = p.id WHERE pa.faculty_id = ? AND pa.is_active = 1");
    $stmt->execute([$fid]);
    $results = $stmt->fetchAll();
    echo empty($results) ? "None\n" : json_encode($results, JSON_PRETTY_PRINT) . "\n";

    echo "\n=== PROJECT ASSIGNMENTS ===\n";
    $stmt = db()->prepare("SELECT pa.*, p.title FROM project_assignments pa JOIN projects p ON pa.project_id = p.id WHERE pa.faculty_id = ? AND pa.is_active = 1");
    $stmt->execute([$fid]);
    $results = $stmt->fetchAll();
    echo empty($results) ? "None\n" : json_encode($results, JSON_PRETTY_PRINT) . "\n";

    echo "\n=== COMPONENT ASSIGNMENTS ===\n";
    $stmt = db()->prepare("SELECT ca.*, c.title FROM component_assignments ca JOIN components c ON ca.component_id = c.id WHERE ca.faculty_id = ? AND ca.is_active = 1");
    $stmt->execute([$fid]);
    $results = $stmt->fetchAll();
    echo empty($results) ? "None\n" : json_encode($results, JSON_PRETTY_PRINT) . "\n";

    echo "\n=== ACTIVITY ASSIGNMENTS ===\n";
    $stmt = db()->prepare("SELECT aa.*, ea.title FROM activity_assignments aa JOIN extension_activities ea ON aa.activity_id = ea.id WHERE aa.faculty_id = ? AND aa.is_active = 1");
    $stmt->execute([$fid]);
    $results = $stmt->fetchAll();
    echo empty($results) ? "None\n" : json_encode($results, JSON_PRETTY_PRINT) . "\n";
}
?>

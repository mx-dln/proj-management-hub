<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'save_general':
        Permissions::requirePermission(Permissions::canManageSettings());
        try {
            $sql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'general') ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = db()->prepare($sql);
            foreach ($_POST as $key => $value) { $stmt->execute([$key, $value, $value]); }
            auditLog('edit', 'settings', null, 'Updated general settings');
            jsonResponse(['success' => true, 'message' => 'Settings saved']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'add_department':
        Permissions::requirePermission(Permissions::canManageSettings());
        try {
            db()->prepare("INSERT INTO departments (name, code) VALUES (?, ?)")->execute([sanitize($_POST['name'] ?? ''), sanitize($_POST['code'] ?? '')]);
            jsonResponse(['success' => true, 'message' => 'Department added']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'add_funding':
        Permissions::requirePermission(Permissions::canManageSettings());
        try {
            db()->prepare("INSERT INTO funding_sources (name, description) VALUES (?, ?)")->execute([sanitize($_POST['name'] ?? ''), sanitize($_POST['description'] ?? '')]);
            jsonResponse(['success' => true, 'message' => 'Funding source added']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'add_activity_type':
        Permissions::requirePermission(Permissions::canManageSettings());
        try {
            db()->prepare("INSERT INTO activity_types (name) VALUES (?)")->execute([sanitize($_POST['name'] ?? '')]);
            jsonResponse(['success' => true, 'message' => 'Activity type added']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

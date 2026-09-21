<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'save_general':
        Permissions::requirePermission(Permissions::canManageSettings());
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'message' => 'POST required'], 405);
        if (empty($_SESSION['settings_csrf']) || !is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['settings_csrf'], $_POST['csrf'])) jsonResponse(['success' => false, 'message' => 'Session expired. Refresh and try again.'], 403);
        try {
            $sql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'general') ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = db()->prepare($sql);
            $keys = db()->query("SELECT setting_key FROM settings WHERE setting_group = 'general'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($keys as $key) {
                if (str_starts_with($key, 'mail_') || !isset($_POST[$key]) || !is_string($_POST[$key])) continue;
                $stmt->execute([$key, $_POST[$key], $_POST[$key]]);
            }
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

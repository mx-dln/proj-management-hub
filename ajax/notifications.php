<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'get_unread':
        $stmt = db()->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$user['id']]);
        jsonResponse($stmt->fetchAll());
        break;

    case 'mark_all_read':
        db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user['id']]);
        jsonResponse(['success' => true]);
        break;

    case 'mark_read':
        $input = json_decode(file_get_contents('php://input'), true);
        db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([intval($input['id'] ?? 0), $user['id']]);
        jsonResponse(['success' => true]);
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

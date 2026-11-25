<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'check_designation':
        $stmt = db()->prepare("
            SELECT n.id as notif_id, n.title as notif_title, n.message as notif_message, n.entity_id as document_id, n.created_at as issued_at,
                   d.title as doc_title, d.file_name, d.entity_type, d.entity_id
            FROM notifications n
            LEFT JOIN documents d ON n.entity_id = d.id
            WHERE n.user_id = ? 
              AND (n.entity_type = 'designation' OR n.title LIKE '%Designation%')
              AND n.is_read = 0
            ORDER BY n.id DESC
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $designation = $stmt->fetch();
        if ($designation && !empty($designation['entity_type']) && !empty($designation['entity_id'])) {
            $tableMap = ['program'=>'programs', 'project'=>'projects', 'component'=>'components', 'activity'=>'extension_activities'];
            $tbl = $tableMap[$designation['entity_type']] ?? null;
            if ($tbl) {
                $eStmt = db()->prepare("SELECT title FROM {$tbl} WHERE id = ?");
                $eStmt->execute([$designation['entity_id']]);
                $designation['entity_name'] = $eStmt->fetchColumn() ?: '';
            }
        }
        jsonResponse(['success' => true, 'has_designation' => (bool)$designation, 'designation' => $designation ?: null]);
        break;

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
        $id = intval($input['id'] ?? ($_GET['id'] ?? ($_POST['id'] ?? 0)));
        db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$id, $user['id']]);
        jsonResponse(['success' => true]);
        break;

    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? ($_GET['id'] ?? 0));
        db()->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([$id, $user['id']]);
        jsonResponse(['success' => true, 'message' => 'Notification deleted']);
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

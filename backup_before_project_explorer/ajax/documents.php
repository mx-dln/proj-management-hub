<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        $entityType = sanitize($_POST['entity_type'] ?? '');
        $entityId = intval($_POST['entity_id'] ?? 0);
        Permissions::requirePermission(Permissions::canUploadDocument($entityType, $entityId));
        
        if (empty($_FILES['file']['tmp_name'])) jsonResponse(['success' => false, 'message' => 'No file selected'], 400);
        
        $result = uploadFile($_FILES['file'], 'documents');
        if ($result['success']) {
            try {
                $data = [
                    'title' => sanitize($_POST['title'] ?? $result['original_name']),
                    'category_id' => intval($_POST['category_id'] ?? 0) ?: null,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'file_path' => $result['file_path'],
                    'file_name' => $result['original_name'],
                    'file_type' => pathinfo($result['original_name'], PATHINFO_EXTENSION),
                    'description' => sanitize($_POST['description'] ?? ''),
                    'uploaded_by' => $user['id'],
                ];
                $sql = "INSERT INTO documents (title, category_id, entity_type, entity_id, file_path, file_name, file_type, description, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                db()->prepare($sql)->execute(array_values($data));
                $id = db()->lastInsertId();
                auditLog('create', 'document', $id, 'Uploaded document: ' . $data['title']);
                jsonResponse(['success' => true, 'message' => 'Document uploaded', 'id' => $id]);
            } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        } else {
            jsonResponse(['success' => false, 'message' => $result['error']], 400);
        }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT d.*, dc.name as category_name, CONCAT(fp.first_name, ' ', fp.last_name) as uploader_name FROM documents d LEFT JOIN document_categories dc ON d.category_id = dc.id LEFT JOIN faculty_profiles fp ON d.uploaded_by = fp.user_id WHERE d.id = ? AND d.deleted_at IS NULL");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if (!$data) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

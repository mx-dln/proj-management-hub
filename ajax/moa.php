<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canManageMoa());
        $data = [
            'moa_number' => sanitize($_POST['moa_number'] ?? ''),
            'project_id' => intval($_POST['project_id'] ?? 0),
            'partner_agency' => sanitize($_POST['partner_agency'] ?? ''),
            'date_signed' => !empty($_POST['date_signed']) ? $_POST['date_signed'] : null,
            'expiration_date' => !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null,
            'status' => 'pending',
            'remarks' => sanitize($_POST['remarks'] ?? ''),
            'created_by' => $user['id'],
        ];
        if (empty($data['moa_number'])) jsonResponse(['success' => false, 'message' => 'MOA number is required'], 400);
        try {
            $sql = "INSERT INTO moas (moa_number, project_id, partner_agency, date_signed, expiration_date, status, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            if (!empty($_FILES['attachment']['tmp_name'])) {
                $result = uploadFile($_FILES['attachment'], 'moa', ['pdf']);
                if ($result['success']) db()->prepare("UPDATE moas SET attachment = ? WHERE id = ?")->execute([$result['file_path'], $id]);
            }
            auditLog('create', 'moa', $id, 'Created MOA: ' . $data['moa_number']);
            jsonResponse(['success' => true, 'message' => 'MOA created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500); }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canManageMoa());
        try {
            $fields = []; $params = [];
            foreach (['moa_number','partner_agency','remarks','status'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f]); }
            }
            foreach (['date_signed','expiration_date'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = $input[$f] ?: null; }
            }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE moas SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'moa', $id, 'Updated MOA');
            jsonResponse(['success' => true, 'message' => 'MOA updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT m.*, p.title as project_title FROM moas m LEFT JOIN projects p ON m.project_id = p.id WHERE m.id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if (!$data) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete':
        $id = intval($_GET['id'] ?? 0);
        Permissions::requirePermission(Permissions::canManageMoa());
        try {
            db()->prepare("DELETE FROM moas WHERE id = ?")->execute([$id]);
            auditLog('delete', 'moa', $id, 'Deleted MOA');
            jsonResponse(['success' => true, 'message' => 'MOA deleted']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

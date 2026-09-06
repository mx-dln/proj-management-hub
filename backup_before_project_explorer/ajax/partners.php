<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canManagePartners());
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'agency_type' => sanitize($_POST['agency_type'] ?? ''),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'contact_number' => sanitize($_POST['contact_number'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
        ];
        if (empty($data['name'])) jsonResponse(['success' => false, 'message' => 'Name is required'], 400);
        try {
            $sql = "INSERT INTO partner_agencies (name, agency_type, contact_person, contact_number, email, address) VALUES (?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            auditLog('create', 'partner_agency', $id, 'Created partner: ' . $data['name']);
            jsonResponse(['success' => true, 'message' => 'Partner created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canManagePartners());
        try {
            $fields = []; $params = [];
            foreach (['name','agency_type','contact_person','contact_number','email','address'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f]); }
            }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE partner_agencies SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'partner_agency', $id, 'Updated partner');
            jsonResponse(['success' => true, 'message' => 'Partner updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT * FROM partner_agencies WHERE id = ?");
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

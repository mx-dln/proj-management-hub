<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canManageBeneficiaries());
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'group_type' => sanitize($_POST['group_type'] ?? ''),
            'barangay' => sanitize($_POST['barangay'] ?? ''),
            'municipality' => sanitize($_POST['municipality'] ?? ''),
            'province' => sanitize($_POST['province'] ?? ''),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'contact_number' => sanitize($_POST['contact_number'] ?? ''),
        ];
        if (empty($data['name'])) jsonResponse(['success' => false, 'message' => 'Name is required'], 400);
        try {
            $sql = "INSERT INTO beneficiary_groups (name, group_type, barangay, municipality, province, contact_person, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            auditLog('create', 'beneficiary_group', $id, 'Created beneficiary: ' . $data['name']);
            jsonResponse(['success' => true, 'message' => 'Beneficiary created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canManageBeneficiaries());
        try {
            $fields = []; $params = [];
            foreach (['name','group_type','barangay','municipality','province','contact_person','contact_number'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f]); }
            }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE beneficiary_groups SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'beneficiary_group', $id, 'Updated beneficiary');
            jsonResponse(['success' => true, 'message' => 'Beneficiary updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT * FROM beneficiary_groups WHERE id = ?");
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

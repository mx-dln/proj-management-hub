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
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        if (!$input) {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        }
        $id = intval($input['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
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
            $stmt = db()->prepare("
                SELECT bg.*, COUNT(ap.id) as beneficiary_count
                FROM beneficiary_groups bg
                LEFT JOIN activity_participants ap ON ap.beneficiary_group_id = bg.id
                WHERE bg.id = ?
                GROUP BY bg.id
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if (!$data) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete':
        $id = intval($_GET['id'] ?? ($_POST['id'] ?? 0));
        if (!$id) {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = intval($input['id'] ?? 0);
        }
        if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
        Permissions::requirePermission(Permissions::canDeleteBeneficiary($id));
        try {
            $bStmt = db()->prepare("SELECT * FROM beneficiary_groups WHERE id = ?");
            $bStmt->execute([$id]);
            $b = $bStmt->fetch();
            if (!$b) jsonResponse(['success' => false, 'message' => 'Beneficiary not found'], 404);
            
            // Check if projects or participants reference this beneficiary
            $projCount = (int)db()->query("SELECT COUNT(*) FROM projects WHERE beneficiary_group_id = {$id}")->fetchColumn();
            $partCount = (int)db()->query("SELECT COUNT(*) FROM activity_participants WHERE beneficiary_group_id = {$id}")->fetchColumn();
            if ($projCount > 0 || $partCount > 0) {
                // If the table has an is_active column or foreign keys, let's check
                $checkActive = db()->query("SHOW COLUMNS FROM beneficiary_groups LIKE 'is_active'");
                if ($checkActive && $checkActive->fetch()) {
                    db()->prepare("UPDATE beneficiary_groups SET is_active = 0 WHERE id = ?")->execute([$id]);
                    auditLog('delete', 'beneficiary_group', $id, 'Deactivated beneficiary (in use): ' . $b['name']);
                    jsonResponse(['success' => true, 'message' => 'Beneficiary deactivated because it is linked to projects/activities']);
                } else {
                    // Set references to NULL or delete
                    db()->prepare("UPDATE projects SET beneficiary_group_id = NULL WHERE beneficiary_group_id = ?")->execute([$id]);
                    db()->prepare("UPDATE activity_participants SET beneficiary_group_id = NULL WHERE beneficiary_group_id = ?")->execute([$id]);
                    db()->prepare("DELETE FROM beneficiary_groups WHERE id = ?")->execute([$id]);
                    auditLog('delete', 'beneficiary_group', $id, 'Deleted beneficiary: ' . $b['name']);
                    jsonResponse(['success' => true, 'message' => 'Beneficiary deleted']);
                }
            } else {
                db()->prepare("DELETE FROM beneficiary_groups WHERE id = ?")->execute([$id]);
                auditLog('delete', 'beneficiary_group', $id, 'Deleted beneficiary: ' . $b['name']);
                jsonResponse(['success' => true, 'message' => 'Beneficiary deleted']);
            }
        } catch (Throwable $e) {
            jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

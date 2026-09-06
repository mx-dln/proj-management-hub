<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canCreateComponent());
        $data = [
            'component_code' => generateCode('COMP', 'components', 'component_code'),
            'project_id' => intval($_POST['project_id'] ?? 0),
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'expected_outputs' => sanitize($_POST['expected_outputs'] ?? ''),
            'status' => 'planned',
            'created_by' => $user['id'],
        ];
        if (empty($data['title'])) jsonResponse(['success' => false, 'message' => 'Title is required'], 400);
        if (empty($data['project_id'])) jsonResponse(['success' => false, 'message' => 'Project is required'], 400);
        try {
            $sql = "INSERT INTO components (component_code, project_id, title, description, start_date, end_date, expected_outputs, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            auditLog('create', 'component', $id, 'Created component: ' . $data['title'], false, $data['project_id']);
            jsonResponse(['success' => true, 'message' => 'Component created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT c.*, p.title as project_title FROM components c LEFT JOIN projects p ON c.project_id = p.id WHERE c.id = ? AND c.deleted_at IS NULL");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if (!$data) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canEditComponent($id));
        $comp = db()->prepare("SELECT project_id FROM components WHERE id = ?");
        $comp->execute([$id]);
        $compData = $comp->fetch();
        if (!$compData) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
        try {
            $fields = []; $params = [];
            foreach (['title','description','status','expected_outputs'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f]); }
            }
            foreach (['start_date','end_date'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = $input[$f] ?: null; }
            }
            if (isset($input['completion_percentage'])) { $fields[] = "completion_percentage = ?"; $params[] = intval($input['completion_percentage']); }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE components SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'component', $id, 'Updated component', false, $compData['project_id']);
            jsonResponse(['success' => true, 'message' => 'Component updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'assign_member':
        $input = json_decode(file_get_contents('php://input'), true);
        Permissions::requirePermission(Permissions::canAssignMembers());
        try {
            db()->prepare("INSERT INTO component_assignments (component_id, faculty_id, assignment_type, assigned_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_active = 1")
                ->execute([intval($input['component_id']), intval($input['faculty_id']), $input['assignment_type'] ?? 'member', $user['id']]);
            jsonResponse(['success' => true, 'message' => 'Member assigned']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get_members':
        $id = intval($_GET['id'] ?? 0);
        $stmt = db()->prepare("SELECT ca.*, fp.first_name, fp.last_name, d.name as department_name FROM component_assignments ca JOIN faculty_profiles fp ON ca.faculty_id = fp.id LEFT JOIN departments d ON fp.department_id = d.id WHERE ca.component_id = ? AND ca.is_active = 1");
        $stmt->execute([$id]);
        jsonResponse($stmt->fetchAll());
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

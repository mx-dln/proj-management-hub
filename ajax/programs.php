<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';
require_once __DIR__ . '/../models/ProgramModel.php';

requireLogin();
$model = new ProgramModel();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canCreateProgram());
        $data = [
            'program_code' => generateCode('PROG', 'programs', 'program_code'),
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'college' => sanitize($_POST['college'] ?? ''),
            'campus' => sanitize($_POST['campus'] ?? ''),
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'budget_allocation' => floatval($_POST['budget_allocation'] ?? 0),
            'objectives' => sanitize($_POST['objectives'] ?? ''),
            'expected_outputs' => sanitize($_POST['expected_outputs'] ?? ''),
            'status' => 'active',
            'created_by' => $user['id'],
        ];
        if (empty($data['title'])) jsonResponse(['success' => false, 'message' => 'Title is required'], 400);
        try {
            $id = $model->create($data);
            auditLog('create', 'program', $id, 'Created program: ' . $data['title']);
            jsonResponse(['success' => true, 'message' => 'Program created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canEditProgram($id));
        try {
            $fields = []; $params = [];
            foreach (['title','description','college','campus','objectives','expected_outputs'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f]); }
            }
            foreach (['start_date','end_date'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = $input[$f] ?: null; }
            }
            if (isset($input['budget_allocation'])) { $fields[] = "budget_allocation = ?"; $params[] = floatval($input['budget_allocation']); }
            if (isset($input['status'])) { $fields[] = "status = ?"; $params[] = $input['status']; }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE programs SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'program', $id, 'Updated program');
            jsonResponse(['success' => true, 'message' => 'Program updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'delete':
        $id = intval($_GET['id'] ?? 0);
        Permissions::requirePermission(Permissions::canDeleteProgram($id));
        try { $model->softDelete($id); auditLog('delete', 'program', $id, 'Deleted program'); jsonResponse(['success' => true, 'message' => 'Program deleted']); }
        catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $program = $model->findById($id);
            if (!$program) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            jsonResponse($program);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'assign_member':
        $input = json_decode(file_get_contents('php://input'), true);
        $programId = intval($input['program_id'] ?? 0);
        $facultyId = intval($input['faculty_id'] ?? 0);
        $assignmentType = $input['assignment_type'] ?? 'member';
        Permissions::requirePermission(Permissions::canAssignMembers());
        try {
            db()->prepare("INSERT INTO program_assignments (program_id, faculty_id, assignment_type, assigned_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_active = 1, assignment_type = ?")
                ->execute([$programId, $facultyId, $assignmentType, $user['id'], $assignmentType]);
            
            // Notify the assigned faculty
            $facultyUser = db()->prepare("SELECT user_id FROM faculty_profiles WHERE id = ?");
            $facultyUser->execute([$facultyId]);
            $fu = $facultyUser->fetch();
            if ($fu) {
                $program = $model->findById($programId);
                createNotification($fu['user_id'], 'New Assignment', "You have been assigned as {$assignmentType} to program: " . $program['title'], 'info', 'program', $programId, '/index.php?module=programs');
            }
            
            auditLog('create', 'program_assignment', $programId, 'Assigned ' . $assignmentType . ' to program');
            jsonResponse(['success' => true, 'message' => 'Member assigned']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'remove_member':
        $input = json_decode(file_get_contents('php://input'), true);
        $programId = intval($input['program_id'] ?? 0);
        $facultyId = intval($input['faculty_id'] ?? 0);
        Permissions::requirePermission(Permissions::canAssignMembers());
        try {
            db()->prepare("UPDATE program_assignments SET is_active = 0 WHERE program_id = ? AND faculty_id = ?")
                ->execute([$programId, $facultyId]);
            auditLog('delete', 'program_assignment', $programId, 'Removed member from program');
            jsonResponse(['success' => true, 'message' => 'Member removed']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get_members':
        $programId = intval($_GET['id'] ?? 0);
        $stmt = db()->prepare("SELECT pa.*, fp.first_name, fp.last_name, fp.department_id, d.name as department_name FROM program_assignments pa JOIN faculty_profiles fp ON pa.faculty_id = fp.id LEFT JOIN departments d ON fp.department_id = d.id WHERE pa.program_id = ? AND pa.is_active = 1");
        $stmt->execute([$programId]);
        jsonResponse($stmt->fetchAll());
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

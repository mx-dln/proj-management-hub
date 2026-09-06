<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';
require_once __DIR__ . '/../models/ProjectModel.php';

requireLogin();
$model = new ProjectModel();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canCreateProject());
        $data = [
            'project_code' => generateCode('PROJ', 'projects', 'project_code'),
            'program_id' => intval($_POST['program_id'] ?? 0),
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'budget' => floatval($_POST['budget'] ?? 0),
            'funding_source_id' => intval($_POST['funding_source_id'] ?? 0) ?: null,
            'partner_agency_id' => intval($_POST['partner_agency_id'] ?? 0) ?: null,
            'beneficiary_group_id' => intval($_POST['beneficiary_group_id'] ?? 0) ?: null,
            'location' => sanitize($_POST['location'] ?? ''),
            'objectives' => sanitize($_POST['objectives'] ?? ''),
            'expected_outputs' => sanitize($_POST['expected_outputs'] ?? ''),
            'status' => 'draft',
            'created_by' => $user['id'],
        ];
        if (empty($data['title'])) jsonResponse(['success' => false, 'message' => 'Title is required'], 400);
        if (empty($data['program_id'])) jsonResponse(['success' => false, 'message' => 'Program is required'], 400);
        try {
            $id = $model->create($data);
            auditLog('create', 'project', $id, 'Created project: ' . $data['title'], false, $id);
            jsonResponse(['success' => true, 'message' => 'Project created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500); }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canEditProject($id));
        try {
            $fields = []; $params = [];
            foreach (['title','description','location','objectives','expected_outputs'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f]); }
            }
            foreach (['program_id','funding_source_id','partner_agency_id','beneficiary_group_id'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = intval($input[$f]) ?: null; }
            }
            foreach (['start_date','end_date'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = $input[$f] ?: null; }
            }
            if (isset($input['budget'])) { $fields[] = "budget = ?"; $params[] = floatval($input['budget']); }
            if (isset($input['status'])) { $fields[] = "status = ?"; $params[] = $input['status']; }
            if (isset($input['completion_percentage'])) { $fields[] = "completion_percentage = ?"; $params[] = intval($input['completion_percentage']); }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE projects SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'project', $id, 'Updated project', false, $id);
            jsonResponse(['success' => true, 'message' => 'Project updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'delete':
        $id = intval($_GET['id'] ?? 0);
        Permissions::requirePermission(Permissions::canDeleteProject($id));
        try { $model->softDelete($id); auditLog('delete', 'project', $id, 'Deleted project', false, $id); jsonResponse(['success' => true, 'message' => 'Project deleted']); }
        catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT p.*, pr.title as program_title FROM projects p LEFT JOIN programs pr ON p.program_id = pr.id WHERE p.id = ? AND p.deleted_at IS NULL");
            $stmt->execute([$id]);
            $project = $stmt->fetch();
            if (!$project) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            $project['canEdit'] = Permissions::canEditProject($id);
            jsonResponse($project);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'assign_member':
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = intval($input['project_id'] ?? 0);
        $facultyId = intval($input['faculty_id'] ?? 0);
        $assignmentType = $input['assignment_type'] ?? 'member';
        Permissions::requirePermission(Permissions::canAssignMembers());
        try {
            db()->prepare("INSERT INTO project_assignments (project_id, faculty_id, assignment_type, assigned_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_active = 1, assignment_type = ?")
                ->execute([$projectId, $facultyId, $assignmentType, $user['id'], $assignmentType]);
            
            $facultyUser = db()->prepare("SELECT user_id FROM faculty_profiles WHERE id = ?");
            $facultyUser->execute([$facultyId]);
            $fu = $facultyUser->fetch();
            if ($fu) {
                $project = $model->findById($projectId);
                createNotification($fu['user_id'], 'New Assignment', "You have been assigned as {$assignmentType} to project: " . $project['title'], 'info', 'project', $projectId, '/index.php?module=projects');
            }
            
            auditLog('create', 'project_assignment', $projectId, 'Assigned ' . $assignmentType . ' to project', false, $projectId);
            jsonResponse(['success' => true, 'message' => 'Member assigned']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'remove_member':
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = intval($input['project_id'] ?? 0);
        $facultyId = intval($input['faculty_id'] ?? 0);
        Permissions::requirePermission(Permissions::canAssignMembers());
        try {
            db()->prepare("UPDATE project_assignments SET is_active = 0 WHERE project_id = ? AND faculty_id = ?")
                ->execute([$projectId, $facultyId]);
            auditLog('delete', 'project_assignment', $projectId, 'Removed member from project', false, $projectId);
            jsonResponse(['success' => true, 'message' => 'Member removed']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get_members':
        $projectId = intval($_GET['id'] ?? 0);
        $stmt = db()->prepare("SELECT pa.*, fp.first_name, fp.last_name, d.name as department_name FROM project_assignments pa JOIN faculty_profiles fp ON pa.faculty_id = fp.id LEFT JOIN departments d ON fp.department_id = d.id WHERE pa.project_id = ? AND pa.is_active = 1");
        $stmt->execute([$projectId]);
        jsonResponse($stmt->fetchAll());
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

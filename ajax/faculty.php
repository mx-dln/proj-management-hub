<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT fp.*, u.email as user_email, u.username, d.name as department_name FROM faculty_profiles fp JOIN users u ON fp.user_id = u.id LEFT JOIN departments d ON fp.department_id = d.id WHERE fp.id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if (!$data) jsonResponse(['success' => false, 'message' => 'Faculty not found'], 404);
            $timeline = [];
            $assignmentSources = [
                ['program_assignments', 'program_id', 'programs', 'Program Assignment'],
                ['project_assignments', 'project_id', 'projects', 'Project Assignment'],
                ['component_assignments', 'component_id', 'components', 'Component Assignment'],
                ['activity_assignments', 'activity_id', 'extension_activities', 'Activity Assignment'],
            ];
            foreach ($assignmentSources as [$assignmentTable, $entityColumn, $entityTable, $label]) {
                $sql = "SELECT ? as timeline_type, a.assignment_type as action_label, e.title, a.assigned_at as created_at
                        FROM {$assignmentTable} a
                        JOIN {$entityTable} e ON e.id = a.{$entityColumn}
                        WHERE a.faculty_id = ? AND a.is_active = 1";
                $itemStmt = db()->prepare($sql);
                $itemStmt->execute([$label, $id]);
                foreach ($itemStmt->fetchAll() as $row) {
                    $timeline[] = [
                        'type' => $row['timeline_type'],
                        'label' => ucfirst($row['action_label']) . ': ' . $row['title'],
                        'description' => 'Assigned as ' . ucfirst($row['action_label']),
                        'created_at' => $row['created_at'],
                    ];
                }
            }
            $auditStmt = db()->prepare("SELECT action, entity_type, description, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
            $auditStmt->execute([(int)$data['user_id']]);
            foreach ($auditStmt->fetchAll() as $row) {
                $timeline[] = [
                    'type' => ucfirst(str_replace('_', ' ', $row['entity_type'] ?: 'Activity')),
                    'label' => ucfirst(str_replace('_', ' ', $row['action'])),
                    'description' => $row['description'] ?: '-',
                    'created_at' => $row['created_at'],
                ];
            }
            usort($timeline, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
            $data['timeline'] = array_slice($timeline, 0, 20);
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'list':
        $search = $_GET['search'] ?? '';
        $department = $_GET['department'] ?? '';
        $role = $_SESSION['role'] ?? '';
        
        if ($role === 'admin' || $role === 'viewer') {
            $sql = "SELECT fp.*, u.username, u.email as user_email, d.name as department_name FROM faculty_profiles fp JOIN users u ON fp.user_id = u.id LEFT JOIN departments d ON fp.department_id = d.id WHERE u.deleted_at IS NULL";
            $params = [];
            if ($search) { $sql .= " AND (fp.first_name LIKE ? OR fp.last_name LIKE ? OR fp.employee_id LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
            if ($department) { $sql .= " AND fp.department_id = ?"; $params[] = $department; }
            $sql .= " ORDER BY fp.last_name, fp.first_name";
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        } else {
            // Faculty - only see their department
            $fid = Permissions::getFacultyId();
            $myProfile = db()->prepare("SELECT department_id FROM faculty_profiles WHERE id = ?");
            $myProfile->execute([$fid]);
            $myDept = $myProfile->fetch();
            
            if (!$myDept) { jsonResponse([]); exit; }
            
            $sql = "SELECT fp.*, u.username, u.email as user_email, d.name as department_name FROM faculty_profiles fp JOIN users u ON fp.user_id = u.id LEFT JOIN departments d ON fp.department_id = d.id WHERE u.deleted_at IS NULL AND fp.department_id = ?";
            $params = [$myDept['department_id']];
            if ($search) { $sql .= " AND (fp.first_name LIKE ? OR fp.last_name LIKE ? OR fp.employee_id LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
            $sql .= " ORDER BY fp.last_name, fp.first_name";
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

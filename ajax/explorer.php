<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();

$action = $_GET['action'] ?? '';
$user = currentUser();
$role = $user['role'];

try {
    switch ($action) {
    // ============================================
    // TREE - Navigation only
    // ============================================
    case 'tree':
        $parentId = intval($_GET['parent_id'] ?? 0);
        $parentType = sanitize($_GET['parent_type'] ?? 'root');
        $facultyId = $_SESSION['faculty_id'] ?? null;

        $nodes = [];

        if ($parentType === 'root') {
            // Load programs
            if ($role === 'admin' || $role === 'viewer') {
                $stmt = db()->prepare("SELECT id, title, status FROM programs ORDER BY title");
                $stmt->execute();
            } elseif ($facultyId) {
                $stmt = db()->prepare("SELECT DISTINCT p.id, p.title, p.status FROM programs p WHERE p.id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1) OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN project_assignments pa ON proj.id = pa.project_id WHERE pa.faculty_id = ? AND pa.is_active = 1) OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN components c ON c.project_id = proj.id JOIN component_assignments ca ON c.id = ca.component_id WHERE ca.faculty_id = ? AND ca.is_active = 1) OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN components c ON c.project_id = proj.id JOIN extension_activities ea ON ea.component_id = c.id JOIN activity_assignments aa ON ea.id = aa.activity_id WHERE aa.faculty_id = ? AND aa.is_active = 1) ORDER BY p.title");
                $stmt->execute([$facultyId, $facultyId, $facultyId, $facultyId]);
            } else {
                jsonResponse(['success' => true, 'nodes' => []]);
            }
            foreach ($stmt->fetchAll() as $p) {
                $nodes[] = ['id' => $p['id'], 'type' => 'program', 'title' => $p['title'], 'status' => $p['status'], 'hasChildren' => true, 'icon' => 'fa-layer-group'];
            }
        } elseif ($parentType === 'program') {
            // Load projects under program
            if ($role === 'admin' || $role === 'viewer') {
                $stmt = db()->prepare("SELECT id, title, status, completion_percentage FROM projects WHERE program_id = ? ORDER BY title");
                $stmt->execute([$parentId]);
            } elseif ($facultyId) {
                $stmt = db()->prepare("SELECT DISTINCT p.id, p.title, p.status, p.completion_percentage FROM projects p WHERE p.program_id = ? AND (p.id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1) OR p.id IN (SELECT DISTINCT c.project_id FROM components c JOIN component_assignments ca ON c.id = ca.component_id WHERE ca.faculty_id = ? AND ca.is_active = 1) OR p.id IN (SELECT DISTINCT c.project_id FROM components c JOIN extension_activities ea ON ea.component_id = c.id JOIN activity_assignments aa ON ea.id = aa.activity_id WHERE aa.faculty_id = ? AND aa.is_active = 1)) ORDER BY p.title");
                $stmt->execute([$parentId, $facultyId, $facultyId, $facultyId]);
            } else {
                jsonResponse(['success' => true, 'nodes' => []]);
            }
            foreach ($stmt->fetchAll() as $p) {
                $nodes[] = ['id' => $p['id'], 'type' => 'project', 'title' => $p['title'], 'status' => $p['status'], 'progress' => $p['completion_percentage'], 'hasChildren' => true, 'icon' => 'fa-folder-open'];
            }
        } elseif ($parentType === 'project') {
            // Load management folders
            $stmt = db()->prepare("SELECT (SELECT COUNT(*) FROM components WHERE project_id = ?) as comp_count, (SELECT COUNT(*) FROM extension_activities WHERE component_id IN (SELECT id FROM components WHERE project_id = ?)) as act_count");
            $stmt->execute([$parentId, $parentId]);
            $counts = $stmt->fetch();
            
            $nodes[] = ['id' => $parentId, 'type' => 'components_folder', 'title' => 'Components', 'count' => (int)($counts['comp_count'] ?? 0), 'hasChildren' => false, 'icon' => 'fa-puzzle-piece'];
            $nodes[] = ['id' => $parentId, 'type' => 'activities_folder', 'title' => 'Activities', 'count' => (int)($counts['act_count'] ?? 0), 'hasChildren' => false, 'icon' => 'fa-calendar-check'];
        }

        jsonResponse(['success' => true, 'nodes' => $nodes]);
        break;

    // ============================================
    // DETAIL - Right panel content
    // ============================================
    case 'detail':
        $id = intval($_GET['id'] ?? 0);
        $type = sanitize($_GET['type'] ?? '');
        if (!$id || !$type) jsonResponse(['success' => false, 'message' => 'Invalid parameters'], 400);

        switch ($type) {
            case 'program':
                $stmt = db()->prepare("SELECT * FROM programs WHERE id = ?");
                $stmt->execute([$id]);
                $data = $stmt->fetch();
                if (!$data) jsonResponse(['success' => false, 'message' => 'Program not found'], 404);
                
                $data['projects_count'] = (int)db()->prepare("SELECT COUNT(*) FROM projects WHERE program_id = ?")->execute([$id]) ? db()->prepare("SELECT COUNT(*) as c FROM projects WHERE program_id = ?") : 0;
                $stmt2 = db()->prepare("SELECT COUNT(*) as c FROM projects WHERE program_id = ?");
                $stmt2->execute([$id]);
                $data['projects_count'] = (int)$stmt2->fetch()['c'];
                
                $stmt3 = db()->prepare("SELECT pa.*, fp.first_name, fp.last_name, fp.department_id, d.name as department_name FROM program_assignments pa JOIN faculty_profiles fp ON pa.faculty_id = fp.id LEFT JOIN departments d ON fp.department_id = d.id WHERE pa.program_id = ? AND pa.is_active = 1");
                $stmt3->execute([$id]);
                $data['assignments'] = $stmt3->fetchAll();
                
                // Get stats
                $stmt4 = db()->prepare("SELECT COUNT(DISTINCT c.id) as components, COUNT(DISTINCT ea.id) as activities FROM projects p LEFT JOIN components c ON c.project_id = p.id LEFT JOIN extension_activities ea ON ea.component_id = c.id WHERE p.program_id = ?");
                $stmt4->execute([$id]);
                $stats = $stmt4->fetch();
                $data['components_count'] = (int)($stats['components'] ?? 0);
                $data['activities_count'] = (int)($stats['activities'] ?? 0);
                
                $stmt5 = db()->prepare("SELECT COUNT(DISTINCT pa.faculty_id) as faculty FROM projects p JOIN project_assignments pa ON pa.project_id = p.id WHERE p.program_id = ? AND pa.is_active = 1");
                $stmt5->execute([$id]);
                $data['faculty_count'] = (int)$stmt5->fetch()['faculty'];
                
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'project':
                $stmt = db()->prepare("SELECT p.*, pr.title as program_title FROM projects p LEFT JOIN programs pr ON p.program_id = pr.id WHERE p.id = ?");
                $stmt->execute([$id]);
                $data = $stmt->fetch();
                if (!$data) jsonResponse(['success' => false, 'message' => 'Project not found'], 404);
                
                $stmt2 = db()->prepare("SELECT COUNT(*) as c FROM components WHERE project_id = ?");
                $stmt2->execute([$id]);
                $data['components_count'] = (int)$stmt2->fetch()['c'];
                
                $stmt3 = db()->prepare("SELECT pa.*, fp.first_name, fp.last_name FROM project_assignments pa JOIN faculty_profiles fp ON pa.faculty_id = fp.id WHERE pa.project_id = ? AND pa.is_active = 1");
                $stmt3->execute([$id]);
                $data['assignments'] = $stmt3->fetchAll();
                
                // Get documents
                $stmt4 = db()->prepare("SELECT * FROM documents WHERE entity_type = 'project' AND entity_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
                $stmt4->execute([$id]);
                $data['documents'] = $stmt4->fetchAll();
                
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'components_folder':
                $projectId = $id;
                $search = sanitize($_GET['search'] ?? '');
                $where = " WHERE c.project_id = ?";
                $params = [$projectId];
                if ($search) { $where .= " AND (c.title LIKE ? OR c.component_code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
                
                $stmt = db()->prepare("SELECT COUNT(*) as count FROM components c{$where}");
                $stmt->execute($params);
                $total = (int)$stmt->fetch()['count'];
                
                $stmt = db()->prepare("SELECT c.*, CONCAT(fp.first_name, ' ', fp.last_name) as leader_name FROM components c LEFT JOIN component_assignments ca ON c.id = ca.component_id AND ca.assignment_type = 'leader' AND ca.is_active = 1 LEFT JOIN faculty_profiles fp ON ca.faculty_id = fp.id{$where} ORDER BY c.title");
                $stmt->execute($params);
                $components = $stmt->fetchAll();
                
                $project = db()->prepare("SELECT title FROM projects WHERE id = ?")->execute([$projectId]) ? db()->prepare("SELECT title FROM projects WHERE id = ?") : null;
                $project->execute([$projectId]);
                $projectData = $project->fetch();
                
                jsonResponse(['success' => true, 'data' => ['components' => $components, 'total' => $total, 'project_id' => $projectId, 'project_title' => $projectData['title'] ?? '']]);
                break;

            case 'activities_folder':
                $projectId = $id;
                $search = sanitize($_GET['search'] ?? '');
                $where = " WHERE c.project_id = ?";
                $params = [$projectId];
                
                $stmt = db()->prepare("SELECT ea.*, c.title as component_title, at.name as type_name FROM extension_activities ea JOIN components c ON ea.component_id = c.id LEFT JOIN activity_types at ON ea.activity_type_id = at.id{$where} ORDER BY ea.start_datetime DESC");
                $stmt->execute($params);
                $activities = $stmt->fetchAll();
                
                $project = db()->prepare("SELECT title FROM projects WHERE id = ?")->execute([$projectId]) ? db()->prepare("SELECT title FROM projects WHERE id = ?") : null;
                $project->execute([$projectId]);
                $projectData = $project->fetch();
                
                jsonResponse(['success' => true, 'data' => ['activities' => $activities, 'project_id' => $projectId, 'project_title' => $projectData['title'] ?? '']]);
                break;

            default:
                jsonResponse(['success' => false, 'message' => 'Invalid type'], 400);
        }
        break;

    // ============================================
    // SEARCH
    // ============================================
    case 'search':
        $query = sanitize($_GET['q'] ?? '');
        $facultyId = $_SESSION['faculty_id'] ?? null;
        if (strlen($query) < 2) jsonResponse(['success' => true, 'results' => []]);
        
        $results = [];
        $likeQuery = "%{$query}%";
        
        // Search programs
        if ($role === 'admin' || $role === 'viewer') {
            $stmt = db()->prepare("SELECT id, title, 'program' as type FROM programs WHERE title LIKE ? LIMIT 5");
            $stmt->execute([$likeQuery]);
        } elseif ($facultyId) {
            $stmt = db()->prepare("SELECT DISTINCT p.id, p.title, 'program' as type FROM programs p WHERE p.title LIKE ? AND (p.id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1) OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN project_assignments pa ON proj.id = pa.project_id WHERE pa.faculty_id = ? AND is_active = 1)) LIMIT 5");
            $stmt->execute([$likeQuery, $facultyId, $facultyId]);
        } else {
            $stmt = null;
        }
        if ($stmt) $results = array_merge($results, $stmt->fetchAll());
        
        // Search projects
        if ($role === 'admin' || $role === 'viewer') {
            $stmt = db()->prepare("SELECT id, title, 'project' as type FROM projects WHERE title LIKE ? LIMIT 5");
            $stmt->execute([$likeQuery]);
        } elseif ($facultyId) {
            $stmt = db()->prepare("SELECT DISTINCT p.id, p.title, 'project' as type FROM projects p WHERE p.title LIKE ? AND p.id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1) LIMIT 5");
            $stmt->execute([$likeQuery, $facultyId]);
        } else {
            $stmt = null;
        }
        if ($stmt) $results = array_merge($results, $stmt->fetchAll());
        
        jsonResponse(['success' => true, 'results' => $results]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}

<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';
require_once __DIR__ . '/../includes/AssignmentVisibility.php';

requireLogin();

$action = $_GET['action'] ?? '';
$user = currentUser();
$role = $user['role'];

try {
    switch ($action) {
    // ============================================
    // DRIVE - Google Drive style workspace
    // ============================================
    case 'drive':
        $type = sanitize($_GET['type'] ?? 'root');
        $id = intval($_GET['id'] ?? 0);
        $folder = sanitize($_GET['folder'] ?? '');
        $query = trim(sanitize($_GET['q'] ?? ''));
        $facultyId = $_SESSION['faculty_id'] ?? null;

        $like = "%{$query}%";
        $items = [];
        $breadcrumbs = [['type' => 'root', 'id' => 0, 'folder' => '', 'title' => 'My Drive']];
        $title = 'My Drive';
        $subtitle = 'Programs and project folders';
        $upload = null;

        if ($type === 'program' && $id > 0 && !AssignmentVisibility::canAccess('program', $id)) {
            jsonResponse(['success' => false, 'message' => 'You do not have access to this program'], 403);
        }
        if (($type === 'project' || ($type === 'folder' && $id > 0)) && !AssignmentVisibility::canAccess('project', $id)) {
            jsonResponse(['success' => false, 'message' => 'You do not have access to this project'], 403);
        }

        $fileIcon = function($ext) {
            $ext = strtolower((string)$ext);
            if (in_array($ext, ['pdf'])) return 'fa-file-pdf';
            if (in_array($ext, ['doc', 'docx'])) return 'fa-file-word';
            if (in_array($ext, ['xls', 'xlsx', 'csv'])) return 'fa-file-excel';
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) return 'fa-file-image';
            return 'fa-file-lines';
        };

        $addFile = function($row) use (&$items, $fileIcon) {
            $items[] = [
                'kind' => 'file',
                'id' => $row['id'],
                'type' => $row['type'],
                'title' => $row['title'],
                'subtitle' => $row['subtitle'] ?? '',
                'owner' => $row['owner'] ?? '',
                'status' => $row['status'] ?? '',
                'updated_at' => $row['updated_at'] ?? $row['created_at'] ?? '',
                'url' => $row['url'] ?? '',
                'icon' => $row['icon'] ?? $fileIcon($row['extension'] ?? ''),
                'meta' => $row,
            ];
        };

        $visibleProgramSql = "p.deleted_at IS NULL";
        $visibleProgramParams = [];
        if ($role === 'faculty' && $facultyId) {
            $visibleProgramSql .= " AND (p.id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1)
                OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN project_assignments pa ON proj.id = pa.project_id WHERE pa.faculty_id = ? AND pa.is_active = 1)
                OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN components c ON c.project_id = proj.id JOIN component_assignments ca ON c.id = ca.component_id WHERE ca.faculty_id = ? AND ca.is_active = 1)
                OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN components c ON c.project_id = proj.id JOIN extension_activities ea ON ea.component_id = c.id JOIN activity_assignments aa ON ea.id = aa.activity_id WHERE aa.faculty_id = ? AND aa.is_active = 1))";
            $visibleProgramParams = [$facultyId, $facultyId, $facultyId, $facultyId];
        }

        if ($type === 'root' && !$folder) {
            $sql = "SELECT p.id, p.title, p.program_code, p.status, p.updated_at,
                    (SELECT COUNT(*) FROM projects pr WHERE pr.program_id = p.id AND pr.deleted_at IS NULL) as child_count
                    FROM programs p WHERE {$visibleProgramSql}";
            $params = $visibleProgramParams;
            if ($query !== '') { $sql .= " AND (p.title LIKE ? OR p.program_code LIKE ?)"; $params[] = $like; $params[] = $like; }
            $sql .= " ORDER BY p.title";
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $p) {
                $items[] = ['kind' => 'folder', 'type' => 'program', 'id' => $p['id'], 'title' => $p['title'], 'subtitle' => $p['program_code'], 'status' => $p['status'], 'count' => (int)$p['child_count'], 'icon' => 'fa-folder'];
            }
        } elseif ($type === 'program') {
            $stmt = db()->prepare("SELECT title, program_code FROM programs WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $program = $stmt->fetch();
            if (!$program) jsonResponse(['success' => false, 'message' => 'Program not found'], 404);
            $title = $program['title'];
            $subtitle = $program['program_code'] . ' project folders';
            $breadcrumbs[] = ['type' => 'program', 'id' => $id, 'folder' => '', 'title' => $program['title']];

            $sql = "SELECT p.id, p.title, p.project_code, p.status, p.completion_percentage, p.updated_at,
                    (SELECT COUNT(*) FROM documents d WHERE d.entity_type = 'project' AND d.entity_id = p.id AND d.deleted_at IS NULL) as document_count
                    FROM projects p WHERE p.program_id = ? AND p.deleted_at IS NULL";
            $params = [$id];
            if ($role === 'faculty' && $facultyId) {
                $sql .= " AND (p.id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)
                    OR p.id IN (SELECT DISTINCT c.project_id FROM components c JOIN component_assignments ca ON c.id = ca.component_id WHERE ca.faculty_id = ? AND ca.is_active = 1)
                    OR p.id IN (SELECT DISTINCT c.project_id FROM components c JOIN extension_activities ea ON ea.component_id = c.id JOIN activity_assignments aa ON ea.id = aa.activity_id WHERE aa.faculty_id = ? AND aa.is_active = 1))";
                array_push($params, $facultyId, $facultyId, $facultyId);
            }
            if ($query !== '') { $sql .= " AND (p.title LIKE ? OR p.project_code LIKE ?)"; $params[] = $like; $params[] = $like; }
            $sql .= " ORDER BY p.title";
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $p) {
                $items[] = ['kind' => 'folder', 'type' => 'project', 'id' => $p['id'], 'title' => $p['title'], 'subtitle' => $p['project_code'] . ' · ' . (int)$p['completion_percentage'] . '% complete', 'status' => $p['status'], 'count' => (int)$p['document_count'], 'icon' => 'fa-folder'];
            }
        } elseif ($type === 'project' && !$folder) {
            $stmt = db()->prepare("SELECT p.*, pr.title as program_title FROM projects p LEFT JOIN programs pr ON p.program_id = pr.id WHERE p.id = ? AND p.deleted_at IS NULL");
            $stmt->execute([$id]);
            $project = $stmt->fetch();
            if (!$project) jsonResponse(['success' => false, 'message' => 'Project not found'], 404);
            $title = $project['title'];
            $subtitle = $project['project_code'] . ' drive folders';
            $breadcrumbs[] = ['type' => 'program', 'id' => $project['program_id'], 'folder' => '', 'title' => $project['program_title'] ?: 'Program'];
            $breadcrumbs[] = ['type' => 'project', 'id' => $id, 'folder' => '', 'title' => $project['title']];
            $folders = [
                ['key' => 'proposal_files', 'title' => 'Proposal Files', 'icon' => 'fa-file-signature'],
                ['key' => 'designation_files', 'title' => 'Designation Files', 'icon' => 'fa-id-badge'],
                ['key' => 'accomplishment_q1', 'title' => 'Accomplishment Q1', 'icon' => 'fa-chart-line'],
                ['key' => 'accomplishment_q2', 'title' => 'Accomplishment Q2', 'icon' => 'fa-chart-line'],
                ['key' => 'accomplishment_q3', 'title' => 'Accomplishment Q3', 'icon' => 'fa-chart-line'],
                ['key' => 'accomplishment_q4', 'title' => 'Accomplishment Q4', 'icon' => 'fa-chart-line'],
                ['key' => 'certificates', 'title' => 'Certificates', 'icon' => 'fa-award'],
                ['key' => 'moa', 'title' => 'MOA', 'icon' => 'fa-handshake'],
                ['key' => 'documents', 'title' => 'Other Documents', 'icon' => 'fa-folder-open'],
            ];
            foreach ($folders as $f) {
                $items[] = ['kind' => 'folder', 'type' => 'folder', 'id' => $id, 'folder' => $f['key'], 'title' => $f['title'], 'subtitle' => 'Open folder', 'icon' => $f['icon']];
            }
        } else {
            $folderTitles = [
                'proposal_files' => 'Proposal Files',
                'designation_files' => 'Designation Files',
                'accomplishment_q1' => 'Accomplishment Q1',
                'accomplishment_q2' => 'Accomplishment Q2',
                'accomplishment_q3' => 'Accomplishment Q3',
                'accomplishment_q4' => 'Accomplishment Q4',
                'certificates' => 'Certificates',
                'moa' => 'MOA',
                'documents' => 'Other Documents',
                'proposals_all' => 'All Proposals',
                'documents_all' => 'All Documents',
                'moa_all' => 'All MOAs',
                'certificates_all' => 'All Certificates',
            ];
            $title = $folderTitles[$folder] ?? 'Folder';
            $subtitle = 'Files and records';

            if ($type === 'folder' && $id > 0) {
                $stmt = db()->prepare("SELECT p.title, p.project_code, p.program_id, pr.title as program_title FROM projects p LEFT JOIN programs pr ON p.program_id = pr.id WHERE p.id = ?");
                $stmt->execute([$id]);
                $project = $stmt->fetch();
                if ($project) {
                    $breadcrumbs[] = ['type' => 'program', 'id' => $project['program_id'], 'folder' => '', 'title' => $project['program_title'] ?: 'Program'];
                    $breadcrumbs[] = ['type' => 'project', 'id' => $id, 'folder' => '', 'title' => $project['title']];
                    $breadcrumbs[] = ['type' => 'folder', 'id' => $id, 'folder' => $folder, 'title' => $title];
                    $subtitle = $project['project_code'] . ' · ' . $project['title'];
                    $upload = ['entity_type' => 'project', 'entity_id' => $id, 'folder' => $folder];
                }
            } else {
                $breadcrumbs[] = ['type' => 'folder', 'id' => 0, 'folder' => $folder, 'title' => $title];
            }

            if (in_array($folder, ['proposal_files', 'proposals_all'])) {
                $sql = "SELECT pr.id, pr.title, pr.proposal_number as subtitle, pr.status, pr.updated_at, pr.attachment, p.title as project_title, CONCAT(fp.first_name, ' ', fp.last_name) as owner
                        FROM proposals pr LEFT JOIN projects p ON pr.project_id = p.id LEFT JOIN faculty_profiles fp ON pr.submitted_by = fp.user_id WHERE 1=1";
                $params = [];
                if ($folder === 'proposal_files') { $sql .= " AND pr.project_id = ?"; $params[] = $id; }
                if ($role === 'faculty' && $facultyId) { $sql .= " AND (pr.submitted_by = ? OR pr.project_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1))"; array_push($params, $user['id'], $facultyId); }
                if ($query !== '') { $sql .= " AND (pr.title LIKE ? OR pr.proposal_number LIKE ?)"; $params[] = $like; $params[] = $like; }
                $sql .= " ORDER BY pr.updated_at DESC";
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    $addFile(['id' => $r['id'], 'type' => 'proposal', 'title' => $r['title'], 'subtitle' => $r['subtitle'] . ($r['project_title'] ? ' · ' . $r['project_title'] : ''), 'owner' => $r['owner'], 'status' => $r['status'], 'updated_at' => $r['updated_at'], 'url' => $r['attachment'] ? UPLOAD_URL . $r['attachment'] : '', 'extension' => pathinfo($r['attachment'] ?: '', PATHINFO_EXTENSION), 'icon' => 'fa-file-signature']);
                }
            }

            if (in_array($folder, ['documents', 'designation_files', 'documents_all'])) {
                $sql = "SELECT d.id, d.title, d.file_name, d.file_type, d.file_path, d.status, d.updated_at, dc.name as category_name, CONCAT(fp.first_name, ' ', fp.last_name) as owner
                        FROM documents d LEFT JOIN document_categories dc ON d.category_id = dc.id LEFT JOIN faculty_profiles fp ON d.uploaded_by = fp.user_id WHERE d.deleted_at IS NULL";
                $params = [];
                if ($folder !== 'documents_all') { $sql .= " AND d.entity_type = 'project' AND d.entity_id = ?"; $params[] = $id; }
                if ($folder === 'documents_all' && $role === 'faculty' && $facultyId) {
                    $sql .= " AND ((d.entity_type = 'program' AND d.entity_id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1))
                        OR (d.entity_type = 'project' AND d.entity_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1))
                        OR (d.entity_type = 'component' AND d.entity_id IN (SELECT component_id FROM component_assignments WHERE faculty_id = ? AND is_active = 1))
                        OR (d.entity_type = 'activity' AND d.entity_id IN (SELECT activity_id FROM activity_assignments WHERE faculty_id = ? AND is_active = 1)))";
                    array_push($params, $facultyId, $facultyId, $facultyId, $facultyId);
                }
                if ($folder === 'designation_files') { $sql .= " AND (dc.name LIKE '%Designation%' OR d.title LIKE '%Designation%')"; }
                if ($query !== '') { $sql .= " AND (d.title LIKE ? OR d.file_name LIKE ?)"; $params[] = $like; $params[] = $like; }
                $sql .= " ORDER BY d.updated_at DESC";
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    $addFile(['id' => $r['id'], 'type' => 'document', 'title' => $r['title'], 'subtitle' => ($r['category_name'] ?: 'Document') . ' · ' . $r['file_name'], 'owner' => $r['owner'], 'status' => $r['status'], 'updated_at' => $r['updated_at'], 'url' => UPLOAD_URL . $r['file_path'], 'extension' => $r['file_type']]);
                }
            }

            if (in_array($folder, ['accomplishment_q1', 'accomplishment_q2', 'accomplishment_q3', 'accomplishment_q4'])) {
                $quarter = strtoupper(substr($folder, -2));
                $sql = "SELECT ar.id, ar.title, ar.report_number, ar.status, ar.updated_at, ar.attachment, CONCAT(fp.first_name, ' ', fp.last_name) as owner
                        FROM accomplishment_reports ar LEFT JOIN faculty_profiles fp ON ar.submitted_by = fp.user_id WHERE ar.project_id = ? AND ar.period_quarter = ?";
                $params = [$id, $quarter];
                if ($query !== '') { $sql .= " AND (ar.title LIKE ? OR ar.report_number LIKE ?)"; $params[] = $like; $params[] = $like; }
                $sql .= " ORDER BY ar.updated_at DESC";
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    $addFile(['id' => $r['id'], 'type' => 'report', 'title' => $r['title'], 'subtitle' => $r['report_number'], 'owner' => $r['owner'], 'status' => $r['status'], 'updated_at' => $r['updated_at'], 'url' => $r['attachment'] ? UPLOAD_URL . $r['attachment'] : '', 'extension' => pathinfo($r['attachment'] ?: '', PATHINFO_EXTENSION), 'icon' => 'fa-file-chart-column']);
                }
            }

            if (in_array($folder, ['moa', 'moa_all'])) {
                $sql = "SELECT m.id, m.moa_number, m.status, m.updated_at, m.attachment, p.title as project_title FROM moas m LEFT JOIN projects p ON m.project_id = p.id WHERE 1=1";
                $params = [];
                if ($folder === 'moa') { $sql .= " AND m.project_id = ?"; $params[] = $id; }
                if ($role === 'faculty' && $facultyId) { $sql .= " AND m.project_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)"; $params[] = $facultyId; }
                if ($query !== '') { $sql .= " AND (m.moa_number LIKE ? OR p.title LIKE ?)"; $params[] = $like; $params[] = $like; }
                $sql .= " ORDER BY m.updated_at DESC";
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    $addFile(['id' => $r['id'], 'type' => 'moa', 'title' => $r['moa_number'], 'subtitle' => $r['project_title'] ?: 'MOA', 'owner' => '', 'status' => $r['status'], 'updated_at' => $r['updated_at'], 'url' => $r['attachment'] ? UPLOAD_URL . $r['attachment'] : '', 'extension' => pathinfo($r['attachment'] ?: '', PATHINFO_EXTENSION), 'icon' => 'fa-file-contract']);
                }
            }

            if (in_array($folder, ['certificates', 'certificates_all'])) {
                $sql = "SELECT c.id, c.certificate_number, c.recipient_name, c.status, c.created_at, c.file_path, ea.title as activity_title
                        FROM certificates c LEFT JOIN extension_activities ea ON c.activity_id = ea.id LEFT JOIN components co ON ea.component_id = co.id WHERE 1=1";
                $params = [];
                if ($folder === 'certificates') { $sql .= " AND co.project_id = ?"; $params[] = $id; }
                if ($role === 'faculty' && $facultyId) { $sql .= " AND c.activity_id IN (SELECT activity_id FROM activity_assignments WHERE faculty_id = ? AND is_active = 1)"; $params[] = $facultyId; }
                if ($query !== '') { $sql .= " AND (c.recipient_name LIKE ? OR c.certificate_number LIKE ? OR ea.title LIKE ?)"; $params[] = $like; $params[] = $like; $params[] = $like; }
                $sql .= " ORDER BY c.created_at DESC";
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    $addFile(['id' => $r['id'], 'type' => 'certificate', 'title' => $r['recipient_name'], 'subtitle' => $r['certificate_number'] . ' · ' . ($r['activity_title'] ?: 'Certificate'), 'owner' => '', 'status' => $r['status'], 'updated_at' => $r['created_at'], 'url' => $r['file_path'] ? UPLOAD_URL . $r['file_path'] : '', 'extension' => pathinfo($r['file_path'] ?: '', PATHINFO_EXTENSION), 'icon' => 'fa-award']);
                }
            }
        }

        jsonResponse([
            'success' => true,
            'title' => $title,
            'subtitle' => $subtitle,
            'breadcrumbs' => $breadcrumbs,
            'items' => $items,
            'upload' => $upload,
        ]);
        break;

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

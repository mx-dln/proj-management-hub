<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        $projectId = intval($_POST['project_id'] ?? 0);
        Permissions::requirePermission(Permissions::canCreateReport($projectId));
        $data = [
            'report_number' => generateCode('AR', 'accomplishment_reports', 'report_number'),
            'report_type' => sanitize($_POST['report_type'] ?? 'quarterly'),
            'period_quarter' => sanitize($_POST['period_quarter'] ?? '') ?: null,
            'period_year' => intval($_POST['period_year'] ?? date('Y')),
            'project_id' => $projectId,
            'title' => sanitize($_POST['title'] ?? ''),
            'summary' => sanitize($_POST['summary'] ?? ''),
            'submitted_by' => $user['id'],
            'status' => 'draft',
        ];
        if (empty($data['title'])) jsonResponse(['success' => false, 'message' => 'Title is required'], 400);
        try {
            $sql = "INSERT INTO accomplishment_reports (report_number, report_type, period_quarter, period_year, project_id, title, summary, submitted_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            if (!empty($_FILES['attachment']['tmp_name'])) {
                $result = uploadFile($_FILES['attachment'], 'reports');
                if ($result['success']) db()->prepare("UPDATE accomplishment_reports SET attachment = ? WHERE id = ?")->execute([$result['file_path'], $id]);
            }
            auditLog('create', 'report', $id, 'Created report: ' . $data['title'], false, $projectId);
            jsonResponse(['success' => true, 'message' => 'Report created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'submit':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $report = db()->prepare("SELECT * FROM accomplishment_reports WHERE id = ?");
        $report->execute([$id]);
        $r = $report->fetch();
        if (!$r) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
        Permissions::requirePermission(Permissions::canCreateReport($r['project_id']));
        try {
            $newStatus = $r['status'] === 'returned' ? 'resubmitted' : 'submitted';
            db()->prepare("UPDATE accomplishment_reports SET status = ?, date_submitted = CURDATE() WHERE id = ?")->execute([$newStatus, $id]);
            db()->prepare("INSERT INTO report_approvals (report_id, approver_id, action, remarks) VALUES (?, ?, 'submitted', ?)")
                ->execute([$id, $user['id'], 'Submitted for review']);
            
            $admins = db()->query("SELECT id FROM users WHERE role = 'admin' AND deleted_at IS NULL")->fetchAll();
            foreach ($admins as $a) {
                createNotification($a['id'], 'New Report Submitted', "Report '{$r['title']}' has been submitted.", 'info', 'report', $id, '/index.php?module=reports');
            }
            
            auditLog('edit', 'report', $id, 'Submitted report');
            jsonResponse(['success' => true, 'message' => 'Report submitted']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'review':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $action = sanitize($input['action'] ?? '');
        $remarks = sanitize($input['remarks'] ?? '');
        Permissions::requirePermission(Permissions::canApproveReport());
        if (!in_array($action, ['approved','returned'])) jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        try {
            db()->prepare("UPDATE accomplishment_reports SET status = ?, remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                ->execute([$action, $remarks, $user['id'], $id]);
            db()->prepare("INSERT INTO report_approvals (report_id, approver_id, action, remarks) VALUES (?, ?, ?, ?)")
                ->execute([$id, $user['id'], $action, $remarks]);
            
            $report = db()->prepare("SELECT * FROM accomplishment_reports WHERE id = ?");
            $report->execute([$id]);
            $r = $report->fetch();
            
            $notifType = $action === 'approved' ? 'success' : 'warning';
            createNotification($r['submitted_by'], 'Report ' . ucfirst($action), "Your report '{$r['title']}' has been {$action}.", $notifType, 'report', $id, '/index.php?module=reports');
            
            auditLog($action, 'report', $id, ucfirst($action) . ' report');
            jsonResponse(['success' => true, 'message' => 'Report ' . $action]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT ar.*, p.title as project_title, CONCAT(fp.first_name, ' ', fp.last_name) as submitter_name FROM accomplishment_reports ar JOIN projects p ON ar.project_id = p.id LEFT JOIN faculty_profiles fp ON ar.submitted_by = fp.user_id WHERE ar.id = ?");
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

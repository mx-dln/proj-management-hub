<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        $projectId = intval($_POST['project_id'] ?? 0);
        Permissions::requirePermission(Permissions::canCreateProposal($projectId));
        $data = [
            'proposal_number' => generateCode('PROP', 'proposals', 'proposal_number'),
            'project_id' => $projectId,
            'title' => sanitize($_POST['title'] ?? ''),
            'submitted_by' => $user['id'],
            'status' => 'draft',
            'remarks' => sanitize($_POST['remarks'] ?? ''),
        ];
        if (empty($data['title'])) jsonResponse(['success' => false, 'message' => 'Title is required'], 400);
        try {
            $sql = "INSERT INTO proposals (proposal_number, project_id, title, submitted_by, status, remarks) VALUES (?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            if (!empty($_FILES['attachment']['tmp_name'])) {
                $result = uploadFile($_FILES['attachment'], 'proposals', ['pdf','doc','docx']);
                if ($result['success']) db()->prepare("UPDATE proposals SET attachment = ? WHERE id = ?")->execute([$result['file_path'], $id]);
            }
            auditLog('create', 'proposal', $id, 'Created proposal: ' . $data['title'], false, $projectId);
            jsonResponse(['success' => true, 'message' => 'Proposal created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'submit':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $proposal = db()->prepare("SELECT * FROM proposals WHERE id = ?");
        $proposal->execute([$id]);
        $p = $proposal->fetch();
        if (!$p) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
        Permissions::requirePermission(Permissions::canSubmitProposal($p['project_id']));
        try {
            $newStatus = $p['status'] === 'returned' ? 'resubmitted' : 'submitted';
            db()->prepare("UPDATE proposals SET status = ?, date_submitted = CURDATE() WHERE id = ?")->execute([$newStatus, $id]);
            db()->prepare("INSERT INTO proposal_approvals (proposal_id, approver_id, action, remarks) VALUES (?, ?, 'submitted', ?)")
                ->execute([$id, $user['id'], 'Submitted for review']);
            
            // Notify admin
            $admins = db()->query("SELECT id FROM users WHERE role = 'admin' AND deleted_at IS NULL")->fetchAll();
            foreach ($admins as $a) {
                createNotification($a['id'], 'New Proposal Submitted', "Proposal '{$p['title']}' has been submitted for review.", 'info', 'proposal', $id, '/index.php?module=proposals');
            }
            
            auditLog('edit', 'proposal', $id, 'Submitted proposal');
            jsonResponse(['success' => true, 'message' => 'Proposal submitted']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'review':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $action = sanitize($input['action'] ?? '');
        $remarks = sanitize($input['remarks'] ?? '');
        Permissions::requirePermission(Permissions::canApproveProposal());
        if (!in_array($action, ['approved','returned','rejected'])) jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        try {
            db()->prepare("UPDATE proposals SET status = ?, remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                ->execute([$action, $remarks, $user['id'], $id]);
            db()->prepare("INSERT INTO proposal_approvals (proposal_id, approver_id, action, remarks) VALUES (?, ?, ?, ?)")
                ->execute([$id, $user['id'], $action, $remarks]);
            
            $proposal = db()->prepare("SELECT * FROM proposals WHERE id = ?");
            $proposal->execute([$id]);
            $p = $proposal->fetch();
            
            $notifType = $action === 'approved' ? 'success' : ($action === 'returned' ? 'warning' : 'error');
            createNotification($p['submitted_by'], 'Proposal ' . ucfirst($action), "Your proposal '{$p['title']}' has been {$action}.", $notifType, 'proposal', $id, '/index.php?module=proposals');
            
            auditLog($action, 'proposal', $id, ucfirst($action) . ' proposal');
            jsonResponse(['success' => true, 'message' => 'Proposal ' . $action]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT pr.*, p.title as project_title, CONCAT(fp.first_name, ' ', fp.last_name) as submitter_name FROM proposals pr JOIN projects p ON pr.project_id = p.id LEFT JOIN faculty_profiles fp ON pr.submitted_by = fp.user_id WHERE pr.id = ?");
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

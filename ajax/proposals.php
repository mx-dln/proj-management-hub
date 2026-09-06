<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create_program_proposal':
        // Faculty submits a proposal for a new program
        if (!in_array($user['role'], ['faculty', 'admin'])) {
            jsonResponse(['success' => false, 'message' => 'Only faculty can submit proposals'], 403);
        }

        $title = sanitize($_POST['title'] ?? '');
        if (empty($title)) jsonResponse(['success' => false, 'message' => 'Title is required'], 400);

        // Create a proposal with no project_id (it's a program proposal)
        $data = [
            'proposal_number' => generateCode('PROP', 'proposals', 'proposal_number'),
            'project_id' => null, // No project yet
            'title' => $title,
            'submitted_by' => $user['id'],
            'date_submitted' => date('Y-m-d'),
            'status' => 'submitted',
            'remarks' => json_encode([
                'type' => 'program_proposal',
                'description' => sanitize($_POST['description'] ?? ''),
                'college' => sanitize($_POST['college'] ?? ''),
                'campus' => sanitize($_POST['campus'] ?? ''),
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'budget' => floatval($_POST['budget'] ?? 0),
                'funding_source' => sanitize($_POST['funding_source'] ?? ''),
                'objectives' => sanitize($_POST['objectives'] ?? ''),
                'beneficiaries' => sanitize($_POST['beneficiaries'] ?? ''),
                'location' => sanitize($_POST['location'] ?? ''),
            ]),
        ];

        try {
            $sql = "INSERT INTO proposals (proposal_number, project_id, title, submitted_by, date_submitted, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute([
                $data['proposal_number'], $data['project_id'], $data['title'],
                $data['submitted_by'], $data['date_submitted'], $data['status'], $data['remarks']
            ]);
            $id = db()->lastInsertId();

            // Notify admins
            $admins = db()->query("SELECT id FROM users WHERE role = 'admin' AND deleted_at IS NULL")->fetchAll();
            foreach ($admins as $a) {
                createNotification($a['id'], 'New Program Proposal', "Faculty submitted a proposal: {$title}", 'info', 'proposal', $id, '/index.php?module=proposals');
            }

            auditLog('create', 'proposal', $id, 'Submitted program proposal: ' . $title);
            jsonResponse(['success' => true, 'message' => 'Proposal submitted for review', 'id' => $id]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
        break;

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
            // Get proposal BEFORE updating (to preserve metadata)
            $proposal = db()->prepare("SELECT * FROM proposals WHERE id = ?");
            $proposal->execute([$id]);
            $p = $proposal->fetch();
            if (!$p) jsonResponse(['success' => false, 'message' => 'Proposal not found'], 404);

            // Update status - keep original remarks for program proposals
            $isProgramProposal = is_null($p['project_id']) || $p['project_id'] == 0;
            if ($isProgramProposal) {
                db()->prepare("UPDATE proposals SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                    ->execute([$action, $user['id'], $id]);
            } else {
                db()->prepare("UPDATE proposals SET status = ?, remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                    ->execute([$action, $remarks, $user['id'], $id]);
            }

            db()->prepare("INSERT INTO proposal_approvals (proposal_id, approver_id, action, remarks) VALUES (?, ?, ?, ?)")
                ->execute([$id, $user['id'], $action, $remarks]);
            
            $notifType = $action === 'approved' ? 'success' : ($action === 'returned' ? 'warning' : 'error');
            createNotification($p['submitted_by'], 'Proposal ' . ucfirst($action), "Your proposal '{$p['title']}' has been {$action}.", $notifType, 'proposal', $id, '/index.php?module=proposals');
            
            // If approved and it's a program proposal, create a program
            if ($action === 'approved' && $isProgramProposal) {
                $meta = json_decode($p['remarks'], true);
                if ($meta && ($meta['type'] ?? '') === 'program_proposal') {
                    $programData = [
                        'program_code' => generateCode('PROG', 'programs', 'program_code'),
                        'title' => $p['title'],
                        'description' => $meta['description'] ?? '',
                        'college' => $meta['college'] ?? '',
                        'campus' => $meta['campus'] ?? 'Cauayan Campus',
                        'start_date' => !empty($meta['start_date']) ? $meta['start_date'] : null,
                        'end_date' => !empty($meta['end_date']) ? $meta['end_date'] : null,
                        'budget_allocation' => floatval($meta['budget'] ?? 0),
                        'objectives' => $meta['objectives'] ?? '',
                        'expected_outputs' => '',
                        'status' => 'active',
                        'created_by' => $user['id'],
                    ];
                    
                    $sql = "INSERT INTO programs (program_code, title, description, college, campus, start_date, end_date, budget_allocation, objectives, expected_outputs, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    db()->prepare($sql)->execute(array_values($programData));
                    $programId = db()->lastInsertId();
                    
                    // Assign the proposer as program leader
                    $facultyId = db()->prepare("SELECT id FROM faculty_profiles WHERE user_id = ?");
                    $facultyId->execute([$p['submitted_by']]);
                    $fid = $facultyId->fetch();
                    if ($fid) {
                        db()->prepare("INSERT INTO program_assignments (program_id, faculty_id, assignment_type, assigned_by) VALUES (?, ?, 'leader', ?)")
                            ->execute([$programId, $fid['id'], $user['id']]);
                    }
                    
                    // Notify proposer
                    createNotification($p['submitted_by'], 'Program Created', "Your proposal has been approved and Program '{$p['title']}' has been created.", 'success', 'program', $programId, '/index.php?module=explorer');
                    
                    auditLog('create', 'program', $programId, 'Auto-created program from approved proposal: ' . $p['title']);
                }
            }
            
            auditLog($action, 'proposal', $id, ucfirst($action) . ' proposal');
            jsonResponse(['success' => true, 'message' => 'Proposal ' . $action]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT pr.*, p.title as project_title, CONCAT(fp.first_name, ' ', fp.last_name) as submitter_name FROM proposals pr LEFT JOIN projects p ON pr.project_id = p.id LEFT JOIN faculty_profiles fp ON pr.submitted_by = fp.user_id WHERE pr.id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if (!$data) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            
            // Parse remarks if it's JSON (program proposal)
            $meta = json_decode($data['remarks'], true);
            if ($meta && is_array($meta) && isset($meta['type'])) {
                $data['proposal_meta'] = $meta;
                $data['is_program_proposal'] = ($meta['type'] === 'program_proposal');
            }
            
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

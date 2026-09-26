<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'template':
        Permissions::requirePermission(Permissions::canGenerateReports());
        $projectId = intval($_GET['project_id'] ?? 0);
        $year = intval($_GET['year'] ?? date('Y'));
        $quarter = sanitize($_GET['quarter'] ?? 'Q1');
        if (!$projectId || !in_array($quarter, ['Q1','Q2','Q3','Q4'], true)) jsonResponse(['success' => false, 'message' => 'Project, year, and quarter are required'], 400);
        try {
            $projectStmt = db()->prepare("
                SELECT p.*, pr.title as program_title, pa.name as partner_name, bg.name as beneficiary_name,
                    CONCAT(fp.first_name, ' ', fp.last_name) as leader_name
                FROM projects p
                LEFT JOIN programs pr ON p.program_id = pr.id
                LEFT JOIN partner_agencies pa ON p.partner_agency_id = pa.id
                LEFT JOIN beneficiary_groups bg ON p.beneficiary_group_id = bg.id
                LEFT JOIN project_assignments pas ON pas.project_id = p.id AND pas.assignment_type = 'leader' AND pas.is_active = 1
                LEFT JOIN faculty_profiles fp ON fp.id = pas.faculty_id
                WHERE p.id = ? AND p.deleted_at IS NULL
            ");
            $projectStmt->execute([$projectId]);
            $project = $projectStmt->fetch();
            if (!$project) jsonResponse(['success' => false, 'message' => 'Project not found'], 404);

            $proposalStmt = db()->prepare("SELECT * FROM proposals WHERE project_id = ? ORDER BY created_at DESC LIMIT 1");
            $proposalStmt->execute([$projectId]);
            $proposal = $proposalStmt->fetch();

            $moaStmt = db()->prepare("SELECT m.*, pa.name as partner_name FROM moas m LEFT JOIN partner_agencies pa ON m.partner_agency_id = pa.id WHERE m.project_id = ? ORDER BY m.created_at DESC LIMIT 1");
            $moaStmt->execute([$projectId]);
            $moa = $moaStmt->fetch();

            $activitiesStmt = db()->prepare("
                SELECT ea.*, c.title as component_title, COUNT(ap.id) as participants,
                    SUM(CASE WHEN ap.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count
                FROM extension_activities ea
                LEFT JOIN components c ON ea.component_id = c.id
                LEFT JOIN activity_participants ap ON ap.activity_id = ea.id
                WHERE c.project_id = ? AND ea.deleted_at IS NULL
                    AND YEAR(COALESCE(ea.start_datetime, ea.created_at)) = ?
                    AND QUARTER(COALESCE(ea.start_datetime, ea.created_at)) = ?
                GROUP BY ea.id
                ORDER BY ea.start_datetime, ea.title
            ");
            $activitiesStmt->execute([$projectId, $year, (int)substr($quarter, 1)]);
            $activities = $activitiesStmt->fetchAll();

            $reportStmt = db()->prepare("SELECT * FROM accomplishment_reports WHERE project_id = ? AND period_year = ? AND period_quarter = ? ORDER BY created_at DESC LIMIT 1");
            $reportStmt->execute([$projectId, $year, $quarter]);
            $report = $reportStmt->fetch();

            ob_start();
            ?>
            <div class="report-template">
                <h2><?= e($quarter) ?> <?= e($year) ?> Accomplishment Report</h2>
                <dl>
                    <dt>Proposal Title</dt><dd><?= e($proposal['title'] ?? $project['title']) ?></dd>
                    <dt>Project Leader / Head</dt><dd><?= e($project['leader_name'] ?: '-') ?></dd>
                    <dt>Program</dt><dd><?= e($project['program_title'] ?? '-') ?></dd>
                    <dt>Partner Agency</dt><dd><?= e($project['partner_name'] ?? '-') ?></dd>
                    <dt>Beneficiaries</dt><dd><?= e($project['beneficiary_name'] ?? '-') ?></dd>
                    <dt>MOA</dt><dd><?= e($moa ? (($moa['moa_number'] ?? '-') . ' · ' . ($moa['status'] ?? '-')) : 'No MOA recorded') ?></dd>
                    <dt>Project Report</dt><dd><?= e($report['title'] ?? 'No uploaded/generated report yet') ?></dd>
                </dl>
                <h3>Project Summary</h3>
                <p><?= nl2br(e($report['summary'] ?? $project['description'] ?? '-')) ?></p>
                <h3>Activities and Related Information</h3>
                <table>
                    <thead><tr><th>Activity</th><th>Component</th><th>Date</th><th>Venue</th><th>Participants</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?= e($activity['title']) ?></td>
                            <td><?= e($activity['component_title'] ?? '-') ?></td>
                            <td><?= e(formatDate($activity['start_datetime'])) ?></td>
                            <td><?= e($activity['venue'] ?? '-') ?></td>
                            <td><?= (int)$activity['present_count'] ?> present / <?= (int)$activity['participants'] ?> listed</td>
                            <td><?= e($activity['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$activities): ?><tr><td colspan="6">No activities recorded for this quarter.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                <h3>Partner Agency Requirements</h3>
                <p><?= e($project['partner_name'] ? 'Include activity outputs, attendance, photos, certificates, MOA status, and signed report files required by ' . $project['partner_name'] . '.' : 'No partner agency recorded for this project.') ?></p>
            </div>
            <style>
            .report-template{background:#fff;color:#111827;padding:24px;border-radius:8px;line-height:1.5}
            .report-template h2{font-size:22px;font-weight:800;margin-bottom:16px}.report-template h3{font-size:16px;font-weight:800;margin-top:18px;margin-bottom:8px}
            .report-template dl{display:grid;grid-template-columns:180px 1fr;gap:6px 12px}.report-template dt{font-weight:700}.report-template dd{margin:0}
            .report-template table{width:100%;border-collapse:collapse;margin-top:8px}.report-template th,.report-template td{border:1px solid #d1d5db;padding:8px;text-align:left;font-size:12px}.report-template th{background:#f3f4f6}
            @media print{body *{visibility:hidden}.report-template,.report-template *{visibility:visible}.report-template{position:absolute;left:0;top:0;width:100%;box-shadow:none}}
            </style>
            <?php
            jsonResponse(['success' => true, 'html' => ob_get_clean()]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
        break;

    case 'file':
        $id = intval($_GET['id'] ?? 0);
        $stmt = db()->prepare("SELECT * FROM accomplishment_reports WHERE id = ?");
        $stmt->execute([$id]);
        $report = $stmt->fetch();
        if (!$report || empty($report['attachment'])) jsonResponse(['success' => false, 'message' => 'File not found'], 404);
        Permissions::requirePermission(Permissions::isAdmin() || Permissions::isViewer() || Permissions::canCreateReport($report['project_id']));
        $base = realpath(UPLOAD_PATH);
        $file = realpath(UPLOAD_PATH . $report['attachment']);
        if (!$base || !$file || !str_starts_with($file, $base . DIRECTORY_SEPARATOR) || !is_file($file)) jsonResponse(['success' => false, 'message' => 'File not found'], 404);
        $name = basename($report['attachment']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
        $inline = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'text/plain'], true) && !isset($_GET['download']);
        header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename=\"report-file\"; filename*=UTF-8''" . rawurlencode($name));
        header('Content-Length: ' . filesize($file));
        header('X-Content-Type-Options: nosniff');
        session_write_close();
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') readfile($file);
        break;

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
            'date_submitted' => date('Y-m-d'),
            'status' => 'submitted',
        ];
        if (empty($data['title'])) jsonResponse(['success' => false, 'message' => 'Title is required'], 400);
        if (!$data['project_id']) jsonResponse(['success' => false, 'message' => 'Project is required'], 400);
        if ($data['report_type'] === 'quarterly' && empty($data['period_quarter'])) jsonResponse(['success' => false, 'message' => 'Quarter is required for quarterly reports'], 400);
        if ($data['period_year'] < 2021 || $data['period_year'] > (int)date('Y') + 1) jsonResponse(['success' => false, 'message' => 'Enter a valid report year'], 400);
        try {
            $sql = "INSERT INTO accomplishment_reports (report_number, report_type, period_quarter, period_year, project_id, title, summary, submitted_by, date_submitted, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            if (!empty($_FILES['attachment']['tmp_name'])) {
                $result = uploadFile($_FILES['attachment'], 'reports');
                if ($result['success']) {
                    db()->prepare("UPDATE accomplishment_reports SET attachment = ? WHERE id = ?")->execute([$result['file_path'], $id]);
                } else {
                    jsonResponse(['success' => false, 'message' => $result['error'] ?? 'Attachment upload failed'], 422);
                }
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
            Permissions::requirePermission(Permissions::isAdmin() || Permissions::isViewer() || Permissions::canCreateReport($data['project_id']));
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

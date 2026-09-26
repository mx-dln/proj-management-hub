<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'import_attendance':
        Permissions::requirePermission(Permissions::canGenerateCertificate());
        $activityId = intval($_POST['activity_id'] ?? 0);
        if (!$activityId) jsonResponse(['success' => false, 'message' => 'Activity is required'], 400);
        if (empty($_FILES['attendance_file']['tmp_name'])) jsonResponse(['success' => false, 'message' => 'CSV attendance file is required'], 400);
        $handle = fopen($_FILES['attendance_file']['tmp_name'], 'r');
        if (!$handle) jsonResponse(['success' => false, 'message' => 'Could not read CSV file'], 400);
        $header = fgetcsv($handle);
        if (!$header) jsonResponse(['success' => false, 'message' => 'CSV file is empty'], 400);
        $keys = array_map(fn($h) => strtolower(trim((string)$h)), $header);
        $imported = 0;
        $created = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($keys, array_pad($row, count($keys), '')) ?: [];
            $name = sanitize($data['name'] ?? $data['participant'] ?? $data['participant_name'] ?? '');
            if ($name === '') continue;
            $status = strtolower(sanitize($data['status'] ?? $data['attendance'] ?? 'present'));
            if (!in_array($status, ['present','absent','registered'], true)) $status = 'present';
            $participant = db()->prepare("SELECT id FROM activity_participants WHERE activity_id = ? AND LOWER(name) = LOWER(?) LIMIT 1");
            $participant->execute([$activityId, $name]);
            $participantId = $participant->fetchColumn();
            if (!$participantId) {
                db()->prepare("INSERT INTO activity_participants (activity_id, name, attendance_status, qr_code) VALUES (?, ?, ?, ?)")
                    ->execute([$activityId, $name, $status, 'QR-' . strtoupper(substr(uniqid(), -8))]);
                $participantId = db()->lastInsertId();
                $created++;
            } else {
                db()->prepare("UPDATE activity_participants SET attendance_status = ? WHERE id = ?")->execute([$status, $participantId]);
            }
            db()->prepare("INSERT INTO activity_attendance (activity_id, participant_id, session_date, time_in, time_out, status) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([
                    $activityId,
                    $participantId,
                    !empty($data['date']) ? $data['date'] : date('Y-m-d'),
                    !empty($data['time_in']) ? $data['time_in'] : null,
                    !empty($data['time_out']) ? $data['time_out'] : null,
                    $status === 'registered' ? 'present' : $status,
                ]);
            $imported++;
        }
        fclose($handle);
        auditLog('import', 'attendance', $activityId, "Imported {$imported} attendance rows");
        jsonResponse(['success' => true, 'message' => "Imported {$imported} attendance rows. Created {$created} new participants."]);
        break;

    case 'create':
        Permissions::requirePermission(Permissions::canGenerateCertificate());
        $data = [
            'certificate_number' => generateCode('CERT', 'certificates', 'certificate_number'),
            'activity_id' => intval($_POST['activity_id'] ?? 0) ?: null,
            'recipient_name' => sanitize($_POST['recipient_name'] ?? ''),
            'recipient_type' => sanitize($_POST['recipient_type'] ?? 'participant'),
            'date_issued' => !empty($_POST['date_issued']) ? $_POST['date_issued'] : date('Y-m-d'),
            'qr_code' => strtoupper(substr(uniqid(), -8)),
            'status' => 'generated',
            'generated_by' => $user['id'],
        ];
        if (empty($data['recipient_name'])) jsonResponse(['success' => false, 'message' => 'Recipient name is required'], 400);
        try {
            $sql = "INSERT INTO certificates (certificate_number, activity_id, recipient_name, recipient_type, date_issued, qr_code, status, generated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            auditLog('create', 'certificate', $id, 'Generated certificate for: ' . $data['recipient_name']);
            jsonResponse(['success' => true, 'message' => 'Certificate generated', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'batch_generate':
        Permissions::requirePermission(Permissions::canGenerateCertificate());
        $activityId = intval($_POST['activity_id'] ?? 0);
        if (!$activityId) jsonResponse(['success' => false, 'message' => 'Activity is required'], 400);
        
        $participants = db()->prepare("SELECT * FROM activity_participants WHERE activity_id = ? AND attendance_status = 'present' AND certificate_status = 'not_issued'");
        $participants->execute([$activityId]);
        $participants = $participants->fetchAll();
        
        $count = 0;
        foreach ($participants as $p) {
            $certNumber = generateCode('CERT', 'certificates', 'certificate_number');
            $qrCode = strtoupper(substr(uniqid(), -8));
            db()->prepare("INSERT INTO certificates (certificate_number, activity_id, recipient_name, recipient_type, date_issued, qr_code, status, generated_by) VALUES (?, ?, ?, 'participant', CURDATE(), ?, 'generated', ?)")
                ->execute([$certNumber, $activityId, $p['name'], $qrCode, $user['id']]);
            db()->prepare("UPDATE activity_participants SET certificate_status = 'issued' WHERE id = ?")->execute([$p['id']]);
            $count++;
        }
        
        auditLog('create', 'certificates', $activityId, "Batch generated {$count} certificates");
        jsonResponse(['success' => true, 'message' => "{$count} certificates generated"]);
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT c.*, ea.title as activity_title FROM certificates c LEFT JOIN extension_activities ea ON c.activity_id = ea.id WHERE c.id = ?");
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

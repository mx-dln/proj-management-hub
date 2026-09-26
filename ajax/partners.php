<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';
require_once __DIR__ . '/../includes/Mailer.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canManagePartners());
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'agency_type' => sanitize($_POST['agency_type'] ?? ''),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'contact_number' => sanitize($_POST['contact_number'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
        ];
        if (empty($data['name'])) jsonResponse(['success' => false, 'message' => 'Name is required'], 400);
        try {
            $sql = "INSERT INTO partner_agencies (name, agency_type, contact_person, contact_number, email, address) VALUES (?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            auditLog('create', 'partner_agency', $id, 'Created partner: ' . $data['name']);
            jsonResponse(['success' => true, 'message' => 'Partner created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canManagePartners());
        try {
            $fields = []; $params = [];
            foreach (['name','agency_type','contact_person','contact_number','email','address'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f]); }
            }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE partner_agencies SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'partner_agency', $id, 'Updated partner');
            jsonResponse(['success' => true, 'message' => 'Partner updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT * FROM partner_agencies WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if (!$data) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'send_email':
        Permissions::requirePermission(Permissions::canManagePartners());
        $id = intval($_POST['id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid partner'], 400);
        if ($subject === '' || $message === '') jsonResponse(['success' => false, 'message' => 'Subject and message are required'], 400);
        if (mb_strlen($subject) > 180) jsonResponse(['success' => false, 'message' => 'Subject is too long'], 400);
        try {
            $stmt = db()->prepare("SELECT * FROM partner_agencies WHERE id = ? AND is_active = 1");
            $stmt->execute([$id]);
            $partner = $stmt->fetch();
            if (!$partner) jsonResponse(['success' => false, 'message' => 'Partner not found'], 404);
            if (!filter_var($partner['email'] ?? '', FILTER_VALIDATE_EMAIL)) jsonResponse(['success' => false, 'message' => 'Partner has no valid email address'], 422);

            $mailer = new Mailer(db());
            $settings = $mailer->settings();
            if ($settings['mail_enabled'] !== '1') jsonResponse(['success' => false, 'message' => 'Email delivery is disabled. Enable SMTP in Settings first.'], 422);
            $recipientName = trim(($partner['contact_person'] ?? '') ?: $partner['name']);
            $body = "Good day {$recipientName},\n\n{$message}\n\nThank you.";
            $mailer->send($partner['email'], $recipientName, $subject, $body, '/index.php?module=partners');
            auditLog('email', 'partner_agency', $id, 'Sent email to partner: ' . $partner['name']);
            jsonResponse(['success' => true, 'message' => 'Email sent to partner.']);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 503);
        } catch (Exception $e) {
            error_log('Partner email failed: ' . $e->getMessage());
            jsonResponse(['success' => false, 'message' => 'Could not send partner email. Check SMTP settings.'], 500);
        }
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

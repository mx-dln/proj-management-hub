<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canCreateActivity());
        $data = [
            'activity_code' => generateCode('ACT', 'extension_activities', 'activity_code'),
            'component_id' => intval($_POST['component_id'] ?? 0),
            'activity_type_id' => intval($_POST['activity_type_id'] ?? 0) ?: null,
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'objectives' => sanitize($_POST['objectives'] ?? ''),
            'venue' => sanitize($_POST['venue'] ?? ''),
            'start_datetime' => !empty($_POST['start_datetime']) ? $_POST['start_datetime'] : null,
            'end_datetime' => !empty($_POST['end_datetime']) ? $_POST['end_datetime'] : null,
            'target_participants' => intval($_POST['target_participants'] ?? 0) ?: null,
            'budget_allocation' => floatval($_POST['budget_allocation'] ?? 0),
            'status' => 'planned',
            'created_by' => $user['id'],
        ];
        if (empty($data['title'])) jsonResponse(['success' => false, 'message' => 'Title is required'], 400);
        try {
            $sql = "INSERT INTO extension_activities (activity_code, component_id, activity_type_id, title, description, objectives, venue, start_datetime, end_datetime, target_participants, budget_allocation, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            auditLog('create', 'activity', $id, 'Created activity: ' . $data['title']);
            jsonResponse(['success' => true, 'message' => 'Activity created', 'id' => $id]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        try {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = db()->prepare("SELECT ea.*, c.title as component_title, at.name as type_name FROM extension_activities ea LEFT JOIN components c ON ea.component_id = c.id LEFT JOIN activity_types at ON ea.activity_type_id = at.id WHERE ea.id = ? AND ea.deleted_at IS NULL");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if (!$data) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
            jsonResponse($data);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canEditActivity($id));
        try {
            $fields = []; $params = [];
            foreach (['title','description','objectives','venue','status'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f]); }
            }
            foreach (['start_datetime','end_datetime'] as $f) {
                if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = $input[$f] ?: null; }
            }
            if (isset($input['activity_type_id'])) { $fields[] = "activity_type_id = ?"; $params[] = intval($input['activity_type_id']) ?: null; }
            if (isset($input['target_participants'])) { $fields[] = "target_participants = ?"; $params[] = intval($input['target_participants']); }
            if (isset($input['budget_allocation'])) { $fields[] = "budget_allocation = ?"; $params[] = floatval($input['budget_allocation']); }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE extension_activities SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'activity', $id, 'Updated activity');
            jsonResponse(['success' => true, 'message' => 'Activity updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'add_participant':
        Permissions::requirePermission(Permissions::isAdmin());
        $data = [
            'activity_id' => intval($_POST['activity_id'] ?? 0),
            'name' => sanitize($_POST['name'] ?? ''),
            'age' => intval($_POST['age'] ?? 0) ?: null,
            'gender' => sanitize($_POST['gender'] ?? ''),
            'barangay' => sanitize($_POST['barangay'] ?? ''),
            'municipality' => sanitize($_POST['municipality'] ?? ''),
            'province' => sanitize($_POST['province'] ?? ''),
            'organization' => sanitize($_POST['organization'] ?? ''),
            'occupation' => sanitize($_POST['occupation'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
        ];
        if (empty($data['name'])) jsonResponse(['success' => false, 'message' => 'Name is required'], 400);
        try {
            $data['qr_code'] = 'QR-' . strtoupper(substr(uniqid(), -8));
            $sql = "INSERT INTO activity_participants (activity_id, name, age, gender, barangay, municipality, province, organization, occupation, phone, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            $id = db()->lastInsertId();
            auditLog('create', 'participant', $id, 'Added participant: ' . $data['name']);
            jsonResponse(['success' => true, 'message' => 'Participant added', 'id' => $id, 'qr_code' => $data['qr_code']]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get_participants':
        $activityId = intval($_GET['id'] ?? 0);
        $stmt = db()->prepare("SELECT * FROM activity_participants WHERE activity_id = ? ORDER BY name");
        $stmt->execute([$activityId]);
        jsonResponse($stmt->fetchAll());
        break;

    case 'add_speaker':
        Permissions::requirePermission(Permissions::isAdmin());
        $data = [
            'activity_id' => intval($_POST['activity_id'] ?? 0),
            'name' => sanitize($_POST['name'] ?? ''),
            'affiliation' => sanitize($_POST['affiliation'] ?? ''),
            'topic' => sanitize($_POST['topic'] ?? ''),
            'contact_number' => sanitize($_POST['contact_number'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
        ];
        try {
            $sql = "INSERT INTO activity_resource_speakers (activity_id, name, affiliation, topic, contact_number, email) VALUES (?, ?, ?, ?, ?, ?)";
            db()->prepare($sql)->execute(array_values($data));
            jsonResponse(['success' => true, 'message' => 'Speaker added']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'assign_member':
        $input = json_decode(file_get_contents('php://input'), true);
        Permissions::requirePermission(Permissions::canAssignMembers());
        try {
            db()->prepare("INSERT INTO activity_assignments (activity_id, faculty_id, assignment_type, assigned_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_active = 1")
                ->execute([intval($input['activity_id']), intval($input['faculty_id']), $input['assignment_type'] ?? 'member', $user['id']]);
            jsonResponse(['success' => true, 'message' => 'Member assigned']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get_members':
        $id = intval($_GET['id'] ?? 0);
        $stmt = db()->prepare("SELECT aa.*, fp.first_name, fp.last_name FROM activity_assignments aa JOIN faculty_profiles fp ON aa.faculty_id = fp.id WHERE aa.activity_id = ? AND aa.is_active = 1");
        $stmt->execute([$id]);
        jsonResponse($stmt->fetchAll());
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

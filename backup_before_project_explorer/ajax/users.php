<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'create':
        Permissions::requirePermission(Permissions::canManageUsers());
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize($_POST['role'] ?? 'faculty');
        
        if (empty($username) || empty($email) || empty($password)) jsonResponse(['success' => false, 'message' => 'Username, email, and password are required'], 400);
        if (strlen($password) < 6) jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
        if (!in_array($role, ['admin','faculty','viewer'])) jsonResponse(['success' => false, 'message' => 'Invalid role'], 400);
        
        $existing = db()->prepare("SELECT id FROM users WHERE username = ?");
        $existing->execute([$username]);
        if ($existing->fetch()) jsonResponse(['success' => false, 'message' => 'Username already exists'], 400);
        
        try {
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            db()->prepare($sql)->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            $userId = db()->lastInsertId();
            
            db()->prepare("INSERT INTO faculty_profiles (user_id, employee_id, first_name, last_name, middle_name, department_id, position, contact_number, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([
                    $userId,
                    sanitize($_POST['employee_id'] ?? 'EMP-' . str_pad($userId, 4, '0', STR_PAD_LEFT)),
                    sanitize($_POST['first_name'] ?? ''),
                    sanitize($_POST['last_name'] ?? ''),
                    sanitize($_POST['middle_name'] ?? ''),
                    intval($_POST['department_id'] ?? 0) ?: null,
                    sanitize($_POST['position'] ?? ''),
                    sanitize($_POST['contact_number'] ?? ''),
                    $email,
                ]);
            
            auditLog('create', 'user', $userId, 'Created user: ' . $username);
            jsonResponse(['success' => true, 'message' => 'User created', 'id' => $userId]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500); }
        break;

    case 'update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canManageUsers());
        try {
            $fields = []; $params = [];
            if (isset($input['email'])) { $fields[] = "email = ?"; $params[] = sanitize($input['email']); }
            if (isset($input['role'])) { $fields[] = "role = ?"; $params[] = sanitize($input['role']); }
            if (isset($input['is_active'])) { $fields[] = "is_active = ?"; $params[] = intval($input['is_active']); }
            if (!empty($input['password'])) { $fields[] = "password = ?"; $params[] = password_hash($input['password'], PASSWORD_DEFAULT); }
            if (!empty($fields)) { $params[] = $id; db()->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params); }
            auditLog('edit', 'user', $id, 'Updated user');
            jsonResponse(['success' => true, 'message' => 'User updated']);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'toggle_status':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        Permissions::requirePermission(Permissions::canManageUsers());
        try {
            $userRecord = db()->prepare("SELECT is_active FROM users WHERE id = ?");
            $userRecord->execute([$id]);
            $record = $userRecord->fetch();
            if (!$record) jsonResponse(['success' => false, 'message' => 'User not found'], 404);
            $newStatus = $record['is_active'] ? 0 : 1;
            db()->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newStatus, $id]);
            auditLog('edit', 'user', $id, 'User ' . ($newStatus ? 'activated' : 'deactivated'));
            jsonResponse(['success' => true, 'message' => 'User ' . ($newStatus ? 'activated' : 'deactivated')]);
        } catch (Exception $e) { jsonResponse(['success' => false, 'message' => 'Failed'], 500); }
        break;

    case 'get':
        Permissions::requirePermission(Permissions::canManageUsers());
        $id = intval($_GET['id'] ?? 0);
        $stmt = db()->prepare("SELECT u.*, fp.employee_id, fp.first_name, fp.last_name, fp.middle_name, fp.department_id, fp.position, fp.contact_number, d.name as department_name FROM users u LEFT JOIN faculty_profiles fp ON u.id = fp.user_id LEFT JOIN departments d ON fp.department_id = d.id WHERE u.id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        if (!$data) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
        jsonResponse($data);
        break;

    case 'list':
        Permissions::requirePermission(Permissions::canManageUsers());
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $sql = "SELECT u.*, fp.first_name, fp.last_name, fp.department_id, d.name as department_name FROM users u LEFT JOIN faculty_profiles fp ON u.id = fp.user_id LEFT JOIN departments d ON fp.department_id = d.id WHERE u.deleted_at IS NULL";
        $params = [];
        if ($search) { $sql .= " AND (u.username LIKE ? OR fp.first_name LIKE ? OR fp.last_name LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
        if ($role) { $sql .= " AND u.role = ?"; $params[] = $role; }
        $sql .= " ORDER BY u.created_at DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        jsonResponse($stmt->fetchAll());
        break;

    default: jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

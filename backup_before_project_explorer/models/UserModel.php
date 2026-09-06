<?php
require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel {
    protected $table = 'users';

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND deleted_at IS NULL");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
            return $user;
        }
        return false;
    }

    public function getAllWithProfile($search = '', $role = '', $limit = 10, $offset = 0) {
        $where = " WHERE u.deleted_at IS NULL";
        $params = [];
        if ($search) { $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR fp.first_name LIKE ? OR fp.last_name LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]); }
        if ($role) { $where .= " AND u.role = ?"; $params[] = $role; }
        $countStmt = $this->db->prepare("SELECT COUNT(*) as count FROM users u LEFT JOIN faculty_profiles fp ON u.id = fp.user_id{$where}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['count'];
        $sql = "SELECT u.*, fp.first_name, fp.last_name, fp.department_id, fp.position, fp.employee_id, d.name as department_name FROM users u LEFT JOIN faculty_profiles fp ON u.id = fp.user_id LEFT JOIN departments d ON fp.department_id = d.id{$where} ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }
}

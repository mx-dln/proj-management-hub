<?php
require_once __DIR__ . '/BaseModel.php';

class FacultyModel extends BaseModel {
    protected $table = 'faculty_profiles';

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT fp.*, d.name as department_name FROM faculty_profiles fp LEFT JOIN departments d ON fp.department_id = d.id WHERE fp.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function getAll($search = '', $department = '', $limit = 10, $offset = 0) {
        $where = " WHERE 1=1";
        $params = [];
        if ($search) { $where .= " AND (fp.first_name LIKE ? OR fp.last_name LIKE ? OR fp.employee_id LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
        if ($department) { $where .= " AND fp.department_id = ?"; $params[] = $department; }
        $countStmt = $this->db->prepare("SELECT COUNT(*) as count FROM faculty_profiles fp{$where}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['count'];
        $sql = "SELECT fp.*, u.username, u.email as user_email, u.role, u.last_login, d.name as department_name FROM faculty_profiles fp JOIN users u ON fp.user_id = u.id LEFT JOIN departments d ON fp.department_id = d.id WHERE u.deleted_at IS NULL" . ($search ? " AND (fp.first_name LIKE ? OR fp.last_name LIKE ? OR fp.employee_id LIKE ?)" : "") . ($department ? " AND fp.department_id = ?" : "") . " ORDER BY fp.last_name, fp.first_name LIMIT ? OFFSET ?";
        $p2 = [];
        if ($search) $p2 = array_merge($p2, ["%$search%","%$search%","%$search%"]);
        if ($department) $p2[] = $department;
        $p2[] = $limit; $p2[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p2);
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }
}

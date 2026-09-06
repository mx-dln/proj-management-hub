<?php
require_once __DIR__ . '/BaseModel.php';

class ProgramModel extends BaseModel {
    protected $table = 'programs';

    public function getAllFiltered($search = '', $status = '', $limit = 10, $offset = 0) {
        $where = " WHERE p.deleted_at IS NULL";
        $params = [];
        if ($search) { $where .= " AND (p.title LIKE ? OR p.program_code LIKE ? OR p.college LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
        if ($status) { $where .= " AND p.status = ?"; $params[] = $status; }
        $countStmt = $this->db->prepare("SELECT COUNT(*) as count FROM programs p{$where}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['count'];
        $sql = "SELECT p.* FROM programs p{$where} ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function getAssigned($facultyId, $limit = 10, $offset = 0) {
        $sql = "SELECT p.*, pa.assignment_type FROM programs p JOIN program_assignments pa ON p.id = pa.program_id WHERE pa.faculty_id = ? AND pa.is_active = 1 AND p.deleted_at IS NULL ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$facultyId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getMembers($programId) {
        $sql = "SELECT pa.*, fp.first_name, fp.last_name, fp.department_id, d.name as department_name FROM program_assignments pa JOIN faculty_profiles fp ON pa.faculty_id = fp.id LEFT JOIN departments d ON fp.department_id = d.id WHERE pa.program_id = ? AND pa.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$programId]);
        return $stmt->fetchAll();
    }
}

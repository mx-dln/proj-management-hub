<?php
require_once __DIR__ . '/BaseModel.php';

class ProjectModel extends BaseModel {
    protected $table = 'projects';

    public function getAllFiltered($search = '', $status = '', $programId = '', $limit = 10, $offset = 0) {
        $where = " WHERE p.deleted_at IS NULL";
        $params = [];
        if ($search) { $where .= " AND (p.title LIKE ? OR p.project_code LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%"]); }
        if ($status) { $where .= " AND p.status = ?"; $params[] = $status; }
        if ($programId) { $where .= " AND p.program_id = ?"; $params[] = $programId; }
        $countStmt = $this->db->prepare("SELECT COUNT(*) as count FROM projects p{$where}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['count'];
        $sql = "SELECT p.*, pr.title as program_title, fs.name as funding_name FROM projects p LEFT JOIN programs pr ON p.program_id = pr.id LEFT JOIN funding_sources fs ON p.funding_source_id = fs.id{$where} ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function getAssigned($facultyId, $limit = 10, $offset = 0) {
        $sql = "SELECT DISTINCT p.*, pr.title as program_title FROM projects p LEFT JOIN programs pr ON p.program_id = pr.id WHERE p.deleted_at IS NULL AND (p.id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1) OR p.program_id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1)) ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$facultyId, $facultyId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getStats() {
        $role = $_SESSION['role'] ?? '';
        $stats = [];
        if ($role === 'admin') {
            $stats['programs'] = $this->db->query("SELECT COUNT(*) as c FROM programs WHERE deleted_at IS NULL")->fetch()['c'];
            $stats['projects'] = $this->db->query("SELECT COUNT(*) as c FROM projects WHERE deleted_at IS NULL")->fetch()['c'];
            $stats['components'] = $this->db->query("SELECT COUNT(*) as c FROM components WHERE deleted_at IS NULL")->fetch()['c'];
            $stats['activities'] = $this->db->query("SELECT COUNT(*) as c FROM extension_activities WHERE deleted_at IS NULL")->fetch()['c'];
            $stats['ongoing'] = $this->db->query("SELECT COUNT(*) as c FROM projects WHERE status = 'ongoing' AND deleted_at IS NULL")->fetch()['c'];
            $stats['completed'] = $this->db->query("SELECT COUNT(*) as c FROM projects WHERE status = 'completed' AND deleted_at IS NULL")->fetch()['c'];
            $stats['pending_proposals'] = $this->db->query("SELECT COUNT(*) as c FROM proposals WHERE status = 'submitted'")->fetch()['c'];
            $stats['approved_proposals'] = $this->db->query("SELECT COUNT(*) as c FROM proposals WHERE status = 'approved'")->fetch()['c'];
            $stats['faculty'] = $this->db->query("SELECT COUNT(*) as c FROM faculty_profiles")->fetch()['c'];
        } else {
            $fid = Permissions::getFacultyId();
            $stats['programs'] = $this->db->prepare("SELECT COUNT(*) as c FROM program_assignments WHERE faculty_id = ? AND is_active = 1");
            $stats['programs']->execute([$fid]); $stats['programs'] = $stats['programs']->fetch()['c'];
            $stats['projects'] = $this->db->prepare("SELECT COUNT(DISTINCT p.id) as c FROM projects p WHERE p.deleted_at IS NULL AND (p.id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1) OR p.program_id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1))");
            $stats['projects']->execute([$fid, $fid]); $stats['projects'] = $stats['projects']->fetch()['c'];
            $stats['components'] = $this->db->prepare("SELECT COUNT(*) as c FROM component_assignments WHERE faculty_id = ? AND is_active = 1");
            $stats['components']->execute([$fid]); $stats['components'] = $stats['components']->fetch()['c'];
            $stats['activities'] = $this->db->prepare("SELECT COUNT(*) as c FROM activity_assignments WHERE faculty_id = ? AND is_active = 1");
            $stats['activities']->execute([$fid]); $stats['activities'] = $stats['activities']->fetch()['c'];
            $stats['my_proposals'] = $this->db->prepare("SELECT COUNT(*) as c FROM proposals WHERE submitted_by = ?");
            $stats['my_proposals']->execute([$_SESSION['user_id']]); $stats['my_proposals'] = $stats['my_proposals']->fetch()['c'];
            $stats['my_certs'] = $this->db->prepare("SELECT COUNT(*) as c FROM certificates WHERE generated_by = ?");
            $stats['my_certs']->execute([$_SESSION['user_id']]); $stats['my_certs'] = $stats['my_certs']->fetch()['c'];
        }
        return $stats;
    }
}

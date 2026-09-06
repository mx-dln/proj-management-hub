<?php
/**
 * Assignment-Based Data Visibility
 * Controls what records each role can see
 */

class AssignmentVisibility {
    
    private static function getFacultyId($userId = null) {
        if ($userId === null) $userId = $_SESSION['user_id'] ?? 0;
        $stmt = db()->prepare("SELECT id FROM faculty_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }

    private static function getUserId() {
        return $_SESSION['user_id'] ?? 0;
    }

    private static function isAdmin() { return ($_SESSION['role'] ?? '') === 'admin'; }
    private static function isFaculty() { return ($_SESSION['role'] ?? '') === 'faculty'; }

    // ============================================
    // PROGRAMS
    // ============================================
    public static function getVisiblePrograms($search = '', $status = '', $limit = 50, $offset = 0) {
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;
        
        $where = " WHERE p.deleted_at IS NULL";
        $params = [];
        
        if ($search) { $where .= " AND (p.title LIKE ? OR p.program_code LIKE ?)"; $params = ["%$search%", "%$search%"]; }
        if ($status) { $where .= " AND p.status = ?"; $params[] = $status; }
        
        if ($facultyId) {
            $where .= " AND (p.id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1)
                        OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN project_assignments pa ON proj.id = pa.project_id WHERE pa.faculty_id = ? AND pa.is_active = 1)
                        OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN components c ON c.project_id = proj.id JOIN component_assignments ca ON c.id = ca.component_id WHERE ca.faculty_id = ? AND ca.is_active = 1)
                        OR p.id IN (SELECT DISTINCT proj.program_id FROM projects proj JOIN components c ON c.project_id = proj.id JOIN extension_activities ea ON ea.component_id = c.id JOIN activity_assignments aa ON ea.id = aa.activity_id WHERE aa.faculty_id = ? AND aa.is_active = 1))";
            $params = array_merge([$facultyId, $facultyId, $facultyId, $facultyId], $params);
        }
        
        $countSql = "SELECT COUNT(*) as count FROM programs p{$where}";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['count'];
        
        $sql = "SELECT p.* FROM programs p{$where} ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    // ============================================
    // PROJECTS
    // ============================================
    public static function getVisibleProjects($search = '', $status = '', $programId = '', $limit = 50, $offset = 0) {
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;
        
        $where = " WHERE p.deleted_at IS NULL";
        $params = [];
        
        if ($search) { $where .= " AND (p.title LIKE ? OR p.project_code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($status) { $where .= " AND p.status = ?"; $params[] = $status; }
        if ($programId) { $where .= " AND p.program_id = ?"; $params[] = $programId; }
        
        if ($facultyId) {
            $where .= " AND (p.id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)
                        OR p.id IN (SELECT DISTINCT c.project_id FROM components c JOIN component_assignments ca ON c.id = ca.component_id WHERE ca.faculty_id = ? AND ca.is_active = 1)
                        OR p.id IN (SELECT DISTINCT c.project_id FROM components c JOIN extension_activities ea ON ea.component_id = c.id JOIN activity_assignments aa ON ea.id = aa.activity_id WHERE aa.faculty_id = ? AND aa.is_active = 1))";
            $params = array_merge([$facultyId, $facultyId, $facultyId], $params);
        }
        
        $countSql = "SELECT COUNT(*) as count FROM projects p{$where}";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['count'];
        
        $sql = "SELECT p.*, pr.title as program_title FROM projects p LEFT JOIN programs pr ON p.program_id = pr.id{$where} ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    // ============================================
    // COMPONENTS
    // ============================================
    public static function getVisibleComponents($search = '', $limit = 50, $offset = 0) {
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;
        
        $where = " WHERE c.deleted_at IS NULL";
        $params = [];
        
        if ($search) { $where .= " AND (c.title LIKE ? OR c.component_code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        
        if ($facultyId) {
            $where .= " AND (c.id IN (SELECT component_id FROM component_assignments WHERE faculty_id = ? AND is_active = 1)
                        OR c.id IN (SELECT DISTINCT ea.component_id FROM extension_activities ea JOIN activity_assignments aa ON ea.id = aa.activity_id WHERE aa.faculty_id = ? AND aa.is_active = 1))";
            $params = array_merge([$facultyId, $facultyId], $params);
        }
        
        $countSql = "SELECT COUNT(*) as count FROM components c{$where}";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['count'];
        
        $sql = "SELECT c.*, p.title as project_title FROM components c LEFT JOIN projects p ON c.project_id = p.id{$where} ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    // ============================================
    // ACTIVITIES
    // ============================================
    public static function getVisibleActivities($search = '', $limit = 50, $offset = 0) {
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;
        
        $where = " WHERE ea.deleted_at IS NULL";
        $params = [];
        
        if ($search) { $where .= " AND (ea.title LIKE ? OR ea.activity_code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        
        if ($facultyId) {
            $where .= " AND ea.id IN (SELECT activity_id FROM activity_assignments WHERE faculty_id = ? AND is_active = 1)";
            $params = array_merge([$facultyId], $params);
        }
        
        $countSql = "SELECT COUNT(*) as count FROM extension_activities ea{$where}";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['count'];
        
        $sql = "SELECT ea.*, c.title as component_title, at.name as type_name FROM extension_activities ea LEFT JOIN components c ON ea.component_id = c.id LEFT JOIN activity_types at ON ea.activity_type_id = at.id{$where} ORDER BY ea.start_datetime DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    // ============================================
    // PROPOSALS
    // ============================================
    public static function getVisibleProposals($search = '', $status = '', $limit = 50, $offset = 0) {
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;
        $userId = self::getUserId();
        
        $where = " WHERE 1=1";
        $params = [];
        
        if ($search) { $where .= " AND (pr.title LIKE ? OR pr.proposal_number LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($status) { $where .= " AND pr.status = ?"; $params[] = $status; }
        
        if ($facultyId) {
            // Faculty sees: their own proposals OR proposals for projects they're assigned to
            $where .= " AND (pr.submitted_by = ? OR pr.project_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1))";
            $params = array_merge([$userId, $facultyId], $params);
        }
        
        $countSql = "SELECT COUNT(*) as count FROM proposals pr{$where}";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['count'];
        
        $sql = "SELECT pr.*, p.title as project_title, CONCAT(fp.first_name, ' ', fp.last_name) as submitter_name FROM proposals pr LEFT JOIN projects p ON pr.project_id = p.id LEFT JOIN faculty_profiles fp ON pr.submitted_by = fp.user_id{$where} ORDER BY pr.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    // ============================================
    // REPORTS
    // ============================================
    public static function getVisibleReports($search = '', $type = '', $limit = 50, $offset = 0) {
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;
        
        $where = " WHERE 1=1";
        $params = [];
        
        if ($search) { $where .= " AND (ar.title LIKE ? OR ar.report_number LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($type) { $where .= " AND ar.report_type = ?"; $params[] = $type; }
        
        if ($facultyId) {
            $where .= " AND ar.project_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)";
            $params = array_merge([$facultyId], $params);
        }
        
        $countSql = "SELECT COUNT(*) as count FROM accomplishment_reports ar{$where}";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['count'];
        
        $sql = "SELECT ar.*, p.title as project_title, CONCAT(fp.first_name, ' ', fp.last_name) as submitter_name FROM accomplishment_reports ar JOIN projects p ON ar.project_id = p.id LEFT JOIN faculty_profiles fp ON ar.submitted_by = fp.user_id{$where} ORDER BY ar.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    // ============================================
    // DOCUMENTS
    // ============================================
    public static function getVisibleDocuments($search = '', $category = '', $limit = 50, $offset = 0) {
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;
        
        $where = " WHERE d.deleted_at IS NULL";
        $params = [];
        
        if ($search) { $where .= " AND (d.title LIKE ? OR d.file_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($category) { $where .= " AND d.category_id = ?"; $params[] = $category; }
        
        if ($facultyId) {
            $where .= " AND ((d.entity_type = 'program' AND d.entity_id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1))
                        OR (d.entity_type = 'project' AND d.entity_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1))
                        OR (d.entity_type = 'component' AND d.entity_id IN (SELECT component_id FROM component_assignments WHERE faculty_id = ? AND is_active = 1))
                        OR (d.entity_type = 'activity' AND d.entity_id IN (SELECT activity_id FROM activity_assignments WHERE faculty_id = ? AND is_active = 1)))";
            $params = array_merge([$facultyId, $facultyId, $facultyId, $facultyId], $params);
        }
        
        $countSql = "SELECT COUNT(*) as count FROM documents d{$where}";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['count'];
        
        $sql = "SELECT d.*, dc.name as category_name FROM documents d LEFT JOIN document_categories dc ON d.category_id = dc.id{$where} ORDER BY d.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    // ============================================
    // CERTIFICATES
    // ============================================
    public static function getVisibleCertificates($limit = 50, $offset = 0) {
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;
        
        if ($facultyId) {
            $where = " WHERE c.activity_id IN (SELECT activity_id FROM activity_assignments WHERE faculty_id = ? AND is_active = 1)";
            $params = [$facultyId];
        } else {
            $where = " WHERE 1=1";
            $params = [];
        }
        
        $countSql = "SELECT COUNT(*) as count FROM certificates c{$where}";
        $stmt = db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['count'];
        
        $sql = "SELECT c.*, ea.title as activity_title FROM certificates c LEFT JOIN extension_activities ea ON c.activity_id = ea.id{$where} ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    // ============================================
    // DASHBOARD STATS
    // ============================================
    public static function getDashboardStats() {
        $stats = [];
        $facultyId = self::isFaculty() ? self::getFacultyId() : null;

        if (self::isAdmin()) {
            $stats['programs'] = (int)db()->query("SELECT COUNT(*) as c FROM programs WHERE deleted_at IS NULL")->fetch()['c'];
            $stats['projects'] = (int)db()->query("SELECT COUNT(*) as c FROM projects WHERE deleted_at IS NULL")->fetch()['c'];
            $stats['components'] = (int)db()->query("SELECT COUNT(*) as c FROM components WHERE deleted_at IS NULL")->fetch()['c'];
            $stats['activities'] = (int)db()->query("SELECT COUNT(*) as c FROM extension_activities WHERE deleted_at IS NULL")->fetch()['c'];
            $stats['ongoing'] = (int)db()->query("SELECT COUNT(*) as c FROM projects WHERE status = 'ongoing' AND deleted_at IS NULL")->fetch()['c'];
            $stats['completed'] = (int)db()->query("SELECT COUNT(*) as c FROM projects WHERE status = 'completed' AND deleted_at IS NULL")->fetch()['c'];
            $stats['pending_proposals'] = (int)db()->query("SELECT COUNT(*) as c FROM proposals WHERE status = 'submitted'")->fetch()['c'];
            $stats['pending_reports'] = (int)db()->query("SELECT COUNT(*) as c FROM accomplishment_reports WHERE status = 'submitted'")->fetch()['c'];
            $stats['faculty'] = (int)db()->query("SELECT COUNT(*) as c FROM faculty_profiles")->fetch()['c'];
            $stats['partners'] = (int)db()->query("SELECT COUNT(*) as c FROM partner_agencies WHERE is_active = 1")->fetch()['c'];
            $stats['beneficiaries'] = (int)db()->query("SELECT COUNT(*) as c FROM beneficiary_groups WHERE is_active = 1")->fetch()['c'];
            $stats['certificates'] = (int)db()->query("SELECT COUNT(*) as c FROM certificates")->fetch()['c'];
        } elseif ($facultyId) {
            $stmt = db()->prepare("SELECT COUNT(DISTINCT program_id) as c FROM program_assignments WHERE faculty_id = ? AND is_active = 1");
            $stmt->execute([$facultyId]);
            $stats['my_programs'] = (int)$stmt->fetch()['c'];

            $stmt = db()->prepare("SELECT COUNT(DISTINCT project_id) as c FROM project_assignments WHERE faculty_id = ? AND is_active = 1");
            $stmt->execute([$facultyId]);
            $stats['my_projects'] = (int)$stmt->fetch()['c'];

            $stmt = db()->prepare("SELECT COUNT(DISTINCT component_id) as c FROM component_assignments WHERE faculty_id = ? AND is_active = 1");
            $stmt->execute([$facultyId]);
            $stats['my_components'] = (int)$stmt->fetch()['c'];

            $stmt = db()->prepare("SELECT COUNT(DISTINCT activity_id) as c FROM activity_assignments WHERE faculty_id = ? AND is_active = 1");
            $stmt->execute([$facultyId]);
            $stats['my_activities'] = (int)$stmt->fetch()['c'];

            $stmt = db()->prepare("SELECT COUNT(*) as c FROM proposals WHERE submitted_by = ? AND status = 'submitted'");
            $stmt->execute([$_SESSION['user_id']]);
            $stats['my_pending_proposals'] = (int)$stmt->fetch()['c'];

            $stmt = db()->prepare("SELECT COUNT(*) as c FROM accomplishment_reports WHERE submitted_by = ? AND status = 'submitted'");
            $stmt->execute([$_SESSION['user_id']]);
            $stats['my_pending_reports'] = (int)$stmt->fetch()['c'];
        }

        return $stats;
    }

    /**
     * Check if faculty can access a specific entity
     */
    public static function canAccess($entityType, $entityId) {
        if (self::isAdmin()) return true;
        
        $facultyId = self::getFacultyId();
        if (!$facultyId) return false;

        switch ($entityType) {
            case 'program':
                $stmt = db()->prepare("SELECT COUNT(*) as c FROM program_assignments WHERE program_id = ? AND faculty_id = ? AND is_active = 1");
                $stmt->execute([$entityId, $facultyId]);
                if ($stmt->fetch()['c'] > 0) return true;
                $stmt = db()->prepare("SELECT COUNT(*) as c FROM projects WHERE program_id = ? AND id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)");
                $stmt->execute([$entityId, $facultyId]);
                return $stmt->fetch()['c'] > 0;

            case 'project':
                $stmt = db()->prepare("SELECT COUNT(*) as c FROM project_assignments WHERE project_id = ? AND faculty_id = ? AND is_active = 1");
                $stmt->execute([$entityId, $facultyId]);
                if ($stmt->fetch()['c'] > 0) return true;
                $stmt = db()->prepare("SELECT COUNT(*) as c FROM components WHERE project_id = ? AND id IN (SELECT component_id FROM component_assignments WHERE faculty_id = ? AND is_active = 1)");
                $stmt->execute([$entityId, $facultyId]);
                return $stmt->fetch()['c'] > 0;

            case 'component':
                $stmt = db()->prepare("SELECT COUNT(*) as c FROM component_assignments WHERE component_id = ? AND faculty_id = ? AND is_active = 1");
                $stmt->execute([$entityId, $facultyId]);
                if ($stmt->fetch()['c'] > 0) return true;
                $stmt = db()->prepare("SELECT COUNT(*) as c FROM extension_activities WHERE component_id = ? AND id IN (SELECT activity_id FROM activity_assignments WHERE faculty_id = ? AND is_active = 1)");
                $stmt->execute([$entityId, $facultyId]);
                return $stmt->fetch()['c'] > 0;

            case 'activity':
                $stmt = db()->prepare("SELECT COUNT(*) as c FROM activity_assignments WHERE activity_id = ? AND faculty_id = ? AND is_active = 1");
                $stmt->execute([$entityId, $facultyId]);
                return $stmt->fetch()['c'] > 0;

            default:
                return false;
        }
    }
}

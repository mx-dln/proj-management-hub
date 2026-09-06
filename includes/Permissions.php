<?php
/**
 * ISU-Cauayan ETS Hub - Permission System v2
 * 
 * Version 1 Workflow:
 * - Admin: Creates Programs, Projects, Components, Activities, manages everything
 * - Faculty: Views assigned items, submits proposals/reports
 * - Viewer: Read-only
 */

class Permissions {
    public static function getRole() { return $_SESSION['role'] ?? ''; }
    public static function getUserId() { return $_SESSION['user_id'] ?? 0; }
    public static function isAdmin() { return self::getRole() === 'admin'; }
    public static function isFaculty() { return self::getRole() === 'faculty'; }
    public static function isViewer() { return self::getRole() === 'viewer'; }

    public static function getFacultyId($userId = null) {
        if ($userId === null) $userId = self::getUserId();
        $stmt = db()->prepare("SELECT id FROM faculty_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }

    // ============================================
    // ASSIGNMENT CHECKS
    // ============================================

    public static function isProgramLeader($programId, $userId = null) {
        $fid = self::getFacultyId($userId);
        if (!$fid) return false;
        $stmt = db()->prepare("SELECT id FROM program_assignments WHERE program_id = ? AND faculty_id = ? AND assignment_type = 'leader' AND is_active = 1");
        $stmt->execute([$programId, $fid]);
        return $stmt->fetch() !== false;
    }

    public static function isProjectLeader($projectId, $userId = null) {
        $fid = self::getFacultyId($userId);
        if (!$fid) return false;
        $stmt = db()->prepare("SELECT id FROM project_assignments WHERE project_id = ? AND faculty_id = ? AND assignment_type = 'leader' AND is_active = 1");
        $stmt->execute([$projectId, $fid]);
        return $stmt->fetch() !== false;
    }

    public static function isStudyLeader($componentId, $userId = null) {
        $fid = self::getFacultyId($userId);
        if (!$fid) return false;
        $stmt = db()->prepare("SELECT id FROM component_assignments WHERE component_id = ? AND faculty_id = ? AND assignment_type = 'leader' AND is_active = 1");
        $stmt->execute([$componentId, $fid]);
        return $stmt->fetch() !== false;
    }

    public static function isAssignedToProgram($programId, $userId = null) {
        $fid = self::getFacultyId($userId);
        if (!$fid) return false;
        $stmt = db()->prepare("SELECT id FROM program_assignments WHERE program_id = ? AND faculty_id = ? AND is_active = 1");
        $stmt->execute([$programId, $fid]);
        return $stmt->fetch() !== false;
    }

    public static function isAssignedToProject($projectId, $userId = null) {
        $fid = self::getFacultyId($userId);
        if (!$fid) return false;
        $stmt = db()->prepare("SELECT id FROM project_assignments WHERE project_id = ? AND faculty_id = ? AND is_active = 1");
        $stmt->execute([$projectId, $fid]);
        return $stmt->fetch() !== false;
    }

    public static function isAssignedToComponent($componentId, $userId = null) {
        $fid = self::getFacultyId($userId);
        if (!$fid) return false;
        $stmt = db()->prepare("SELECT id FROM component_assignments WHERE component_id = ? AND faculty_id = ? AND is_active = 1");
        $stmt->execute([$componentId, $fid]);
        return $stmt->fetch() !== false;
    }

    public static function isAssignedToActivity($activityId, $userId = null) {
        $fid = self::getFacultyId($userId);
        if (!$fid) return false;
        $stmt = db()->prepare("SELECT id FROM activity_assignments WHERE activity_id = ? AND faculty_id = ? AND is_active = 1");
        $stmt->execute([$activityId, $fid]);
        return $stmt->fetch() !== false;
    }

    // ============================================
    // HIERARCHICAL ACCESS
    // ============================================

    public static function canAccessProgram($programId) {
        if (self::isAdmin() || self::isViewer()) return true;
        return self::isFaculty() && self::isAssignedToProgram($programId);
    }

    public static function canAccessProject($projectId) {
        if (self::isAdmin() || self::isViewer()) return true;
        if (self::isFaculty() && self::isAssignedToProject($projectId)) return true;
        $stmt = db()->prepare("SELECT program_id FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        return $project && self::isAssignedToProgram($project['program_id']);
    }

    public static function canAccessComponent($componentId) {
        if (self::isAdmin() || self::isViewer()) return true;
        if (self::isFaculty() && self::isAssignedToComponent($componentId)) return true;
        $stmt = db()->prepare("SELECT project_id FROM components WHERE id = ?");
        $stmt->execute([$componentId]);
        $comp = $stmt->fetch();
        return $comp && self::canAccessProject($comp['project_id']);
    }

    public static function canAccessActivity($activityId) {
        if (self::isAdmin() || self::isViewer()) return true;
        if (self::isFaculty() && self::isAssignedToActivity($activityId)) return true;
        $stmt = db()->prepare("SELECT component_id FROM extension_activities WHERE id = ?");
        $stmt->execute([$activityId]);
        $act = $stmt->fetch();
        return $act && self::canAccessComponent($act['component_id']);
    }

    // ============================================
    // PROGRAM PERMISSIONS (Admin only)
    // ============================================

    public static function canCreateProgram() { return self::isAdmin(); }
    public static function canEditProgram($id) { return self::isAdmin(); }
    public static function canDeleteProgram($id) { return self::isAdmin(); }

    // ============================================
    // PROJECT PERMISSIONS (Admin only)
    // ============================================

    public static function canCreateProject() { return self::isAdmin(); }
    public static function canEditProject($projectId) {
        if (self::isAdmin()) return true;
        if (self::isFaculty()) {
            return self::isProjectLeader($projectId) || self::isProjectMember($projectId);
        }
        return false;
    }
    public static function canDeleteProject($id) { return self::isAdmin(); }

    // ============================================
    // COMPONENT PERMISSIONS (Admin only)
    // ============================================

    public static function canCreateComponent() { return self::isAdmin(); }
    public static function canEditComponent($id) { return self::isAdmin(); }
    public static function canDeleteComponent($id) { return self::isAdmin(); }

    // ============================================
    // ACTIVITY PERMISSIONS (Admin only)
    // ============================================

    public static function canCreateActivity() { return self::isAdmin(); }
    public static function canEditActivity($id) { return self::isAdmin(); }
    public static function canDeleteActivity($id) { return self::isAdmin(); }

    // ============================================
    // ASSIGNMENT PERMISSIONS (Admin only)
    // ============================================

    // Admin or Project Leader/Member can assign
    public static function canAssignMembers() { 
        return self::isAdmin() || self::isFaculty();
    }

    // ============================================
    // PROPOSAL PERMISSIONS
    // ============================================

    public static function canCreateProposal($projectId) {
        return self::isFaculty() && self::isProjectLeader($projectId);
    }

    public static function canSubmitProposal($projectId) {
        return self::isFaculty() && self::isProjectLeader($projectId);
    }

    public static function canApproveProposal() { return self::isAdmin(); }

    // ============================================
    // DOCUMENT PERMISSIONS
    // ============================================

    public static function canUploadDocument($entityType, $entityId) {
        if (self::isAdmin()) return true;
        if (!self::isFaculty()) return false;
        switch ($entityType) {
            case 'program': return self::isAssignedToProgram($entityId);
            case 'project': return self::isAssignedToProject($entityId);
            case 'component': return self::isAssignedToComponent($entityId);
            case 'activity': return self::isAssignedToActivity($entityId);
            default: return false;
        }
    }

    // ============================================
    // REPORT PERMISSIONS
    // ============================================

    public static function canCreateReport($projectId) {
        return self::isFaculty() && self::isProjectLeader($projectId);
    }

    public static function canApproveReport() { return self::isAdmin(); }

    // ============================================
    // MOA PERMISSIONS (Admin only)
    // ============================================

    public static function canManageMoa() { return self::isAdmin(); }
    public static function canViewMoa() { return true; }

    // ============================================
    // CERTIFICATE PERMISSIONS (Admin only)
    // ============================================

    public static function canGenerateCertificate() { return self::isAdmin(); }

    // ============================================
    // SYSTEM PERMISSIONS (Admin only)
    // ============================================

    public static function canManageUsers() { return self::isAdmin(); }
    public static function canManageSettings() { return self::isAdmin(); }
    public static function canViewAuditLogs() { return self::isAdmin(); }
    public static function canManagePartners() { return self::isAdmin(); }
    public static function canManageBeneficiaries() { return self::isAdmin(); }

    // ============================================
    // REPORT ACCESS
    // ============================================

    public static function canGenerateReports() {
        return self::isAdmin() || self::isViewer();
    }

    // ============================================
    // ENFORCEMENT
    // ============================================

    public static function requirePermission($allowed) {
        if (!$allowed) {
            jsonResponse(['success' => false, 'message' => 'Forbidden: You do not have permission to perform this action'], 403);
        }
    }
}

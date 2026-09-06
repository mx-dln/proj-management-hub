<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';

requireLogin();
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($action) {
    case 'get':
        $entityType = sanitize($_GET['entity_type'] ?? '');
        $entityId = intval($_GET['entity_id'] ?? 0);

        if (!$entityType || !$entityId) {
            jsonResponse(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        // Get current assignments
        $assignmentTable = $entityType . '_assignments';
        $entityColumn = $entityType === 'activity' ? 'activity_id' : ($entityType . '_id');
        
        $stmt = db()->prepare("
            SELECT a.*, fp.first_name, fp.last_name, fp.department_id, d.name as department_name, fp.email
            FROM {$assignmentTable} a
            JOIN faculty_profiles fp ON a.faculty_id = fp.id
            LEFT JOIN departments d ON fp.department_id = d.id
            WHERE a.{$entityColumn} = ? AND a.is_active = 1
            ORDER BY a.assignment_type DESC, fp.last_name
        ");
        $stmt->execute([$entityId]);
        $assignments = $stmt->fetchAll();

        // Get available faculty based on hierarchy
        $availableFaculty = getAvailableFaculty($entityType, $entityId);

        jsonResponse([
            'success' => true,
            'assignments' => $assignments,
            'availableFaculty' => $availableFaculty,
            'leaderAssignmentType' => 'leader'
        ]);
        break;

    case 'save':
        $input = json_decode(file_get_contents('php://input'), true);
        $entityType = sanitize($input['entity_type'] ?? '');
        $entityId = intval($input['entity_id'] ?? 0);
        $leaderId = $input['leader_id'] ? intval($input['leader_id']) : null;
        $memberIds = $input['member_ids'] ?? [];

        if (!$entityType || !$entityId) {
            jsonResponse(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        // Check permission
        Permissions::requirePermission(Permissions::canAssignMembers());

        $assignmentTable = $entityType . '_assignments';
        $entityColumn = $entityType === 'activity' ? 'activity_id' : ($entityType . '_id');

        try {
            db()->beginTransaction();

            // Get entity name for notifications
            $entityName = getEntityName($entityType, $entityId);

            // Update leader
            if ($leaderId) {
                // Remove existing leader
                db()->prepare("UPDATE {$assignmentTable} SET is_active = 0 WHERE {$entityColumn} = ? AND assignment_type = 'leader'")
                    ->execute([$entityId]);
                
                // Set new leader
                db()->prepare("INSERT INTO {$assignmentTable} ({$entityColumn}, faculty_id, assignment_type, assigned_by, is_active) 
                              VALUES (?, ?, 'leader', ?, 1) 
                              ON DUPLICATE KEY UPDATE is_active = 1, assignment_type = 'leader', assigned_by = ?")
                    ->execute([$entityId, $leaderId, $user['id'], $user['id']]);

                // Notify leader
                notifyFaculty($leaderId, $entityType, $entityId, $entityName, 'leader');
            }

            // Get current members
            $currentMembers = db()->prepare("SELECT faculty_id FROM {$assignmentTable} WHERE {$entityColumn} = ? AND assignment_type = 'member' AND is_active = 1");
            $currentMembers->execute([$entityId]);
            $currentMemberIds = array_column($currentMembers->fetchAll(), 'faculty_id');

            // Add new members
            foreach ($memberIds as $memberId) {
                if (!in_array($memberId, $currentMemberIds)) {
                    db()->prepare("INSERT INTO {$assignmentTable} ({$entityColumn}, faculty_id, assignment_type, assigned_by, is_active) 
                                  VALUES (?, ?, 'member', ?, 1) 
                                  ON DUPLICATE KEY UPDATE is_active = 1, assigned_by = ?")
                        ->execute([$entityId, $memberId, $user['id'], $user['id']]);

                    // Notify new member
                    notifyFaculty($memberId, $entityType, $entityId, $entityName, 'member');
                }
            }

            // Remove members not in new list
            foreach ($currentMemberIds as $currentId) {
                if (!in_array($currentId, $memberIds) && $currentId !== $leaderId) {
                    db()->prepare("UPDATE {$assignmentTable} SET is_active = 0 WHERE {$entityColumn} = ? AND faculty_id = ?")
                        ->execute([$entityId, $currentId]);

                    // Notify removed member
                    notifyFacultyRemoved($currentId, $entityType, $entityId, $entityName);
                }
            }

            db()->commit();

            // Audit log
            auditLog('edit', $entityType . '_assignment', $entityId, "Updated assignments for {$entityType}: {$entityName}");

            jsonResponse(['success' => true, 'message' => 'Assignments saved successfully']);
        } catch (Exception $e) {
            db()->rollback();
            jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Get available faculty based on entity hierarchy
 */
function getAvailableFaculty($entityType, $entityId) {
    switch ($entityType) {
        case 'program':
            // All active faculty
            return db()->query("
                SELECT fp.id, fp.first_name, fp.last_name, fp.email, d.name as department_name
                FROM faculty_profiles fp
                JOIN users u ON fp.user_id = u.id
                LEFT JOIN departments d ON fp.department_id = d.id
                WHERE u.is_active = 1 AND u.deleted_at IS NULL
                ORDER BY fp.last_name, fp.first_name
            ")->fetchAll();

        case 'project':
            // Faculty assigned to parent program
            $stmt = db()->prepare("
                SELECT fp.id, fp.first_name, fp.last_name, fp.email, d.name as department_name
                FROM faculty_profiles fp
                JOIN users u ON fp.user_id = u.id
                LEFT JOIN departments d ON fp.department_id = d.id
                WHERE u.is_active = 1 AND u.deleted_at IS NULL
                AND fp.id IN (
                    SELECT faculty_id FROM program_assignments 
                    WHERE program_id = (SELECT program_id FROM projects WHERE id = ?) AND is_active = 1
                )
                ORDER BY fp.last_name, fp.first_name
            ");
            $stmt->execute([$entityId]);
            return $stmt->fetchAll();

        case 'component':
            // Faculty assigned to parent project
            $stmt = db()->prepare("
                SELECT fp.id, fp.first_name, fp.last_name, fp.email, d.name as department_name
                FROM faculty_profiles fp
                JOIN users u ON fp.user_id = u.id
                LEFT JOIN departments d ON fp.department_id = d.id
                WHERE u.is_active = 1 AND u.deleted_at IS NULL
                AND fp.id IN (
                    SELECT faculty_id FROM project_assignments 
                    WHERE project_id = (SELECT project_id FROM components WHERE id = ?) AND is_active = 1
                )
                ORDER BY fp.last_name, fp.first_name
            ");
            $stmt->execute([$entityId]);
            return $stmt->fetchAll();

        case 'activity':
            // Faculty assigned to parent component
            $stmt = db()->prepare("
                SELECT fp.id, fp.first_name, fp.last_name, fp.email, d.name as department_name
                FROM faculty_profiles fp
                JOIN users u ON fp.user_id = u.id
                LEFT JOIN departments d ON fp.department_id = d.id
                WHERE u.is_active = 1 AND u.deleted_at IS NULL
                AND fp.id IN (
                    SELECT faculty_id FROM component_assignments 
                    WHERE component_id = (SELECT component_id FROM extension_activities WHERE id = ?) AND is_active = 1
                )
                ORDER BY fp.last_name, fp.first_name
            ");
            $stmt->execute([$entityId]);
            return $stmt->fetchAll();

        default:
            return [];
    }
}

/**
 * Get entity name for notifications
 */
function getEntityName($entityType, $entityId) {
    $tableMap = [
        'program' => 'programs',
        'project' => 'projects',
        'component' => 'components',
        'activity' => 'extension_activities'
    ];
    $table = $tableMap[$entityType] ?? 'programs';
    $stmt = db()->prepare("SELECT title FROM {$table} WHERE id = ?");
    $stmt->execute([$entityId]);
    $result = $stmt->fetch();
    return $result ? $result['title'] : 'Unknown';
}

/**
 * Notify faculty about assignment
 */
function notifyFaculty($facultyId, $entityType, $entityId, $entityName, $role) {
    $stmt = db()->prepare("SELECT user_id FROM faculty_profiles WHERE id = ?");
    $stmt->execute([$facultyId]);
    $faculty = $stmt->fetch();
    
    if ($faculty) {
        $roleLabel = ucfirst($role);
        $typeLabel = ucfirst($entityType);
        createNotification(
            $faculty['user_id'],
            "New {$roleLabel} Assignment",
            "You have been assigned as {$roleLabel} to {$typeLabel}: {$entityName}",
            'info',
            $entityType,
            $entityId,
            "/index.php?module={$entityType}s"
        );
    }
}

/**
 * Notify faculty about removal
 */
function notifyFacultyRemoved($facultyId, $entityType, $entityId, $entityName) {
    $stmt = db()->prepare("SELECT user_id FROM faculty_profiles WHERE id = ?");
    $stmt->execute([$facultyId]);
    $faculty = $stmt->fetch();
    
    if ($faculty) {
        $typeLabel = ucfirst($entityType);
        createNotification(
            $faculty['user_id'],
            "Assignment Removed",
            "You have been removed from {$typeLabel}: {$entityName}",
            'warning',
            $entityType,
            $entityId,
            "/index.php?module={$entityType}s"
        );
    }
}

<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';
require_once __DIR__ . '/../includes/AssignmentVisibility.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/FacultyModel.php';
require_once __DIR__ . '/../models/ProgramModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/ExplorerModel.php';

class AuthController {
    public function login($username, $password) {
        $model = new UserModel();
        $user = $model->authenticate($username, $password);
        if ($user) {
            if (!$user['is_active']) return ['success' => false, 'message' => 'Account is deactivated'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $profile = (new FacultyModel())->getByUserId($user['id']);
            $_SESSION['user_name'] = $profile ? $profile['first_name'] . ' ' . $profile['last_name'] : $user['username'];
            $_SESSION['faculty_id'] = $profile ? $profile['id'] : null;
            auditLog('login', 'user', $user['id'], 'User logged in');
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    public function logout() {
        auditLog('logout', 'user', $_SESSION['user_id'] ?? null, 'User logged out');
        session_destroy();
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

class DashboardController {
    public function index() {
        $stats = AssignmentVisibility::getDashboardStats();
        $userId = $_SESSION['user_id'] ?? 0;
        $role = $_SESSION['role'] ?? '';
        $facultyId = $_SESSION['faculty_id'] ?? null;

        if ($role === 'admin') {
            $recentActivities = db()->query("SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10")->fetchAll();
        } else {
            $recentActivities = db()->prepare("SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id WHERE al.user_id = ? ORDER BY al.created_at DESC LIMIT 10");
            $recentActivities->execute([$userId]);
            $recentActivities = $recentActivities->fetchAll();
        }

        $notifications = db()->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
        $notifications->execute([$userId]);
        $notifications = $notifications->fetchAll();

        if ($role === 'faculty' && $facultyId) {
            $projectWhere = "WHERE p.deleted_at IS NULL AND p.id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)";
            $projectParams = [$facultyId];
            $activityWhere = "WHERE ea.deleted_at IS NULL AND ea.id IN (SELECT activity_id FROM activity_assignments WHERE faculty_id = ? AND is_active = 1)";
            $activityParams = [$facultyId];
        } else {
            $projectWhere = "WHERE p.deleted_at IS NULL";
            $projectParams = [];
            $activityWhere = "WHERE ea.deleted_at IS NULL";
            $activityParams = [];
        }

        $projectStatusStmt = db()->prepare("SELECT p.status, COUNT(*) as total FROM projects p {$projectWhere} GROUP BY p.status ORDER BY total DESC");
        $projectStatusStmt->execute($projectParams);
        $projectStatus = $projectStatusStmt->fetchAll();

        $activityStatusStmt = db()->prepare("SELECT ea.status, COUNT(*) as total FROM extension_activities ea {$activityWhere} GROUP BY ea.status ORDER BY total DESC");
        $activityStatusStmt->execute($activityParams);
        $activityStatus = $activityStatusStmt->fetchAll();

        $monthlyStmt = db()->prepare("SELECT DATE_FORMAT(COALESCE(ea.start_datetime, ea.created_at), '%b') as month_label, MONTH(COALESCE(ea.start_datetime, ea.created_at)) as month_num, COUNT(*) as total FROM extension_activities ea {$activityWhere} AND YEAR(COALESCE(ea.start_datetime, ea.created_at)) = YEAR(CURDATE()) GROUP BY MONTH(COALESCE(ea.start_datetime, ea.created_at)), DATE_FORMAT(COALESCE(ea.start_datetime, ea.created_at), '%b') ORDER BY MONTH(COALESCE(ea.start_datetime, ea.created_at))");
        $monthlyStmt->execute($activityParams);
        $monthlyRows = $monthlyStmt->fetchAll();
        $monthlyByNumber = [];
        foreach ($monthlyRows as $row) {
            $monthlyByNumber[(int)$row['month_num']] = (int)$row['total'];
        }
        $monthlyActivities = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyActivities[] = [
                'month_label' => date('M', mktime(0, 0, 0, $month, 1)),
                'month_num' => $month,
                'total' => $monthlyByNumber[$month] ?? 0,
            ];
        }

        $departmentStmt = db()->prepare("SELECT COALESCE(d.name, 'Unassigned') as department, COUNT(DISTINCT fp.id) as total FROM faculty_profiles fp LEFT JOIN departments d ON fp.department_id = d.id GROUP BY COALESCE(d.name, 'Unassigned') ORDER BY total DESC LIMIT 6");
        $departmentStmt->execute();
        $departmentLoad = $departmentStmt->fetchAll();

        $topProjectsStmt = db()->prepare("SELECT p.title, p.status, p.completion_percentage, p.budget FROM projects p {$projectWhere} ORDER BY p.completion_percentage DESC, p.updated_at DESC LIMIT 5");
        $topProjectsStmt->execute($projectParams);
        $topProjects = $topProjectsStmt->fetchAll();

        $budgetStmt = db()->prepare("SELECT COALESCE(SUM(p.budget), 0) as total_budget, COALESCE(AVG(p.completion_percentage), 0) as average_completion FROM projects p {$projectWhere}");
        $budgetStmt->execute($projectParams);
        $budgetSummary = $budgetStmt->fetch();

        $participantStmt = db()->prepare("SELECT COUNT(*) as total FROM activity_participants ap JOIN extension_activities ea ON ap.activity_id = ea.id {$activityWhere}");
        $participantStmt->execute($activityParams);
        $participantTotal = (int)$participantStmt->fetch()['total'];

        $programPerformanceStmt = db()->prepare("SELECT pr.title, COUNT(DISTINCT p.id) as projects, COUNT(DISTINCT ea.id) as activities, COALESCE(AVG(p.completion_percentage), 0) as completion FROM programs pr LEFT JOIN projects p ON p.program_id = pr.id AND p.deleted_at IS NULL LEFT JOIN components c ON c.project_id = p.id AND c.deleted_at IS NULL LEFT JOIN extension_activities ea ON ea.component_id = c.id AND ea.deleted_at IS NULL WHERE pr.deleted_at IS NULL GROUP BY pr.id, pr.title ORDER BY activities DESC, projects DESC LIMIT 5");
        $programPerformanceStmt->execute();
        $programPerformance = $programPerformanceStmt->fetchAll();
        $driveStats = (new ExplorerModel())->storageStats();

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/dashboard/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class ProgramController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisiblePrograms($_GET['search'] ?? '', $_GET['status'] ?? '', $limit, $offset);
        $totalPages = ceil($result['total'] / $limit);

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/programs/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }

    public function view($id) {
        if (!AssignmentVisibility::canAccess('program', $id)) {
            header('Location: index.php?module=programs');
            exit;
        }
        $model = new ProgramModel();
        $program = $model->findById($id);
        if (!$program) { header('Location: index.php?module=programs'); exit; }
        $members = $model->getMembers($id);
        $projects = db()->prepare("SELECT * FROM projects WHERE program_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
        $projects->execute([$id]);
        $projects = $projects->fetchAll();
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/programs/view.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }

    public function create() {
        Permissions::requirePermission(Permissions::canCreateProgram());
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/programs/create.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class ProjectController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisibleProjects($_GET['search'] ?? '', $_GET['status'] ?? '', $_GET['program'] ?? '', $limit, $offset);
        $totalPages = ceil($result['total'] / $limit);
        $programs = AssignmentVisibility::getVisiblePrograms('', '', 100, 0)['data'];

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/projects/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }

    public function view($id) {
        if (!AssignmentVisibility::canAccess('project', $id)) {
            header('Location: index.php?module=projects');
            exit;
        }
        $model = new ProjectModel();
        $project = $model->findById($id);
        if (!$project) { header('Location: index.php?module=projects'); exit; }
        $program = db()->prepare("SELECT * FROM programs WHERE id = ?");
        $program->execute([$project['program_id']]);
        $program = $program->fetch();
        $assignments = db()->prepare("SELECT pa.*, fp.first_name, fp.last_name FROM project_assignments pa JOIN faculty_profiles fp ON pa.faculty_id = fp.id WHERE pa.project_id = ? AND pa.is_active = 1");
        $assignments->execute([$id]);
        $assignments = $assignments->fetchAll();
        $components = db()->prepare("SELECT * FROM components WHERE project_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
        $components->execute([$id]);
        $components = $components->fetchAll();
        $proposals = db()->prepare("SELECT * FROM proposals WHERE project_id = ? ORDER BY created_at DESC");
        $proposals->execute([$id]);
        $proposals = $proposals->fetchAll();
        $projectDocuments = db()->prepare("SELECT d.*, dc.name as category_name FROM documents d LEFT JOIN document_categories dc ON d.category_id = dc.id WHERE d.entity_type = 'project' AND d.entity_id = ? AND d.deleted_at IS NULL ORDER BY d.created_at DESC");
        $projectDocuments->execute([$id]);
        $projectDocuments = $projectDocuments->fetchAll();
        $projectMoas = db()->prepare("SELECT m.*, pa.name as partner_name FROM moas m LEFT JOIN partner_agencies pa ON m.partner_agency_id = pa.id WHERE m.project_id = ? ORDER BY m.created_at DESC");
        $projectMoas->execute([$id]);
        $projectMoas = $projectMoas->fetchAll();
        $projectReports = db()->prepare("SELECT * FROM accomplishment_reports WHERE project_id = ? ORDER BY period_year DESC, FIELD(period_quarter, 'Q4','Q3','Q2','Q1'), created_at DESC");
        $projectReports->execute([$id]);
        $projectReports = $projectReports->fetchAll();
        $projectCertificates = db()->prepare("SELECT c.*, ea.title as activity_title FROM certificates c LEFT JOIN extension_activities ea ON c.activity_id = ea.id LEFT JOIN components co ON ea.component_id = co.id WHERE co.project_id = ? ORDER BY c.created_at DESC");
        $projectCertificates->execute([$id]);
        $projectCertificates = $projectCertificates->fetchAll();
        $projectActivities = db()->prepare("SELECT ea.id, ea.title FROM extension_activities ea JOIN components co ON ea.component_id = co.id WHERE co.project_id = ? AND ea.deleted_at IS NULL ORDER BY ea.title");
        $projectActivities->execute([$id]);
        $projectActivities = $projectActivities->fetchAll();
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/projects/view.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }

    public function create() {
        Permissions::requirePermission(Permissions::canCreateProject());
        $programs = db()->query("SELECT id, title FROM programs WHERE deleted_at IS NULL AND status = 'active' ORDER BY title")->fetchAll();
        $fundingSources = db()->query("SELECT * FROM funding_sources WHERE is_active = 1")->fetchAll();
        $partnerAgencies = db()->query("SELECT * FROM partner_agencies WHERE is_active = 1")->fetchAll();
        $beneficiaryGroups = db()->query("SELECT * FROM beneficiary_groups WHERE is_active = 1")->fetchAll();
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/projects/create.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class ComponentController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisibleComponents($_GET['search'] ?? '', $limit, $offset);
        $components = $result['data'];
        $totalPages = ceil($result['total'] / $limit);

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/components/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class ActivityController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisibleActivities($_GET['search'] ?? '', $limit, $offset);
        $activities = $result['data'];
        $totalPages = ceil($result['total'] / $limit);

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/activities/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class ProposalController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisibleProposals($_GET['search'] ?? '', $_GET['status'] ?? '', $limit, $offset);
        $proposals = $result['data'];
        $totalPages = ceil($result['total'] / $limit);

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/proposals/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class DocumentController {
    public function index() {
        require_once __DIR__ . '/../models/DocumentModel.php';
        $isDesignation = ($_GET['module'] ?? '') === 'designations';
        $documentModule = $isDesignation ? 'designations' : 'documents';
        $categories = db()->query('SELECT * FROM document_categories WHERE is_active = 1 ORDER BY name')->fetchAll();
        $categoryId = $isDesignation ? '' : ($_GET['category'] ?? '');
        $uploadTargets = DocumentModel::uploadTargets();
        $canUpload = !empty($uploadTargets) && (!$isDesignation || Permissions::isAdmin());
        $uploadLimit = DocumentModel::uploadLimit();
        $_SESSION['documents_csrf'] ??= bin2hex(random_bytes(32));
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisibleDocuments($_GET['search'] ?? '', $categoryId, $limit, $offset);
        $documents = $result['data'];
        $totalPages = ceil($result['total'] / $limit);

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/documents/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class ReportController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisibleReports($_GET['search'] ?? '', $_GET['type'] ?? '', $_GET['year'] ?? '', $_GET['quarter'] ?? '', $limit, $offset);
        $reports = $result['data'];
        $totalPages = ceil($result['total'] / $limit);

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/reports/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class MoaController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? '');
        $searchWhere = '';
        $searchParams = [];
        if ($search !== '') {
            $searchWhere = " AND (m.moa_number LIKE ? OR p.title LIKE ? OR pa.name LIKE ? OR m.status LIKE ?)";
            $searchParams = array_fill(0, 4, "%{$search}%");
        }

        $role = $_SESSION['role'] ?? '';
        if ($role === 'admin' || $role === 'viewer') {
            $stmt = db()->prepare("SELECT m.*, p.title as project_title, pa.name as partner_name FROM moas m LEFT JOIN projects p ON m.project_id = p.id LEFT JOIN partner_agencies pa ON m.partner_agency_id = pa.id WHERE 1=1{$searchWhere} ORDER BY m.created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute(array_merge($searchParams, [$limit, $offset]));
            $moas = $stmt->fetchAll();
            $cstmt = db()->prepare("SELECT COUNT(*) as c FROM moas m LEFT JOIN projects p ON m.project_id = p.id LEFT JOIN partner_agencies pa ON m.partner_agency_id = pa.id WHERE 1=1{$searchWhere}");
            $cstmt->execute($searchParams);
            $total = $cstmt->fetch()['c'];
        } else {
            $fid = Permissions::getFacultyId();
            if (!$fid) {
                $moas = [];
                $total = 0;
            } else {
            $visibleMoaWhere = "m.project_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)
                OR p.program_id IN (SELECT program_id FROM program_assignments WHERE faculty_id = ? AND is_active = 1)";
            $stmt = db()->prepare("SELECT m.*, p.title as project_title, pa.name as partner_name FROM moas m LEFT JOIN projects p ON m.project_id = p.id LEFT JOIN partner_agencies pa ON m.partner_agency_id = pa.id WHERE ({$visibleMoaWhere}){$searchWhere} ORDER BY m.created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute(array_merge([$fid, $fid], $searchParams, [$limit, $offset]));
            $moas = $stmt->fetchAll();
            $cstmt = db()->prepare("SELECT COUNT(*) as c FROM moas m LEFT JOIN projects p ON m.project_id = p.id LEFT JOIN partner_agencies pa ON m.partner_agency_id = pa.id WHERE ({$visibleMoaWhere}){$searchWhere}");
            $cstmt->execute(array_merge([$fid, $fid], $searchParams));
            $total = $cstmt->fetch()['c'];
            }
        }
        $totalPages = ceil($total / $limit);

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/moa/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class CertificateController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisibleCertificates($limit, $offset);
        $certificates = $result['data'];
        $totalPages = ceil($result['total'] / $limit);

        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/certificates/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class FacultyController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $model = new FacultyModel();
        $result = $model->getAll($_GET['search'] ?? '', $_GET['department'] ?? '', $limit, $offset);
        $totalPages = ceil($result['total'] / $limit);
        $departments = db()->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY name")->fetchAll();
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/faculty/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class PartnerController {
    public function index() {
        $partners = db()->query("SELECT * FROM partner_agencies WHERE is_active = 1 ORDER BY name")->fetchAll();
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/partners/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class BeneficiaryController {
    public function index() {
        $beneficiaries = db()->query("SELECT * FROM beneficiary_groups WHERE is_active = 1 ORDER BY name")->fetchAll();
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/beneficiaries/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class NotificationController {
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $userId = $_SESSION['user_id'];
        $stmt = db()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$userId, $limit, $offset]);
        $notifications = $stmt->fetchAll();
        $countStmt = db()->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $total = $countStmt->fetch()['count'];
        $totalPages = ceil($total / $limit);
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/notifications/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class AuditController {
    public function index() {
        Permissions::requirePermission(Permissions::canViewAuditLogs());
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $where = " WHERE 1=1";
        $params = [];
        if ($_GET['search'] ?? '') { $where .= " AND (u.username LIKE ? OR al.description LIKE ?)"; $params = array_merge($params, ["%".$_GET['search']."%","%".$_GET['search']."%"]); }
        if ($_GET['action'] ?? '') { $where .= " AND al.action = ?"; $params[] = $_GET['action']; }
        $countStmt = db()->prepare("SELECT COUNT(*) as count FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id{$where}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['count'];
        $sql = "SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id{$where} ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $result = ['data' => $stmt->fetchAll(), 'total' => $total];
        $totalPages = ceil($total / $limit);
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/audit-logs/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

class SettingController {
    public function index() {
        Permissions::requirePermission(Permissions::canManageSettings());
        $departments = db()->query("SELECT * FROM departments ORDER BY name")->fetchAll();
        $fundingSources = db()->query("SELECT * FROM funding_sources ORDER BY name")->fetchAll();
        $activityTypes = db()->query("SELECT * FROM activity_types ORDER BY name")->fetchAll();
        $documentCategories = db()->query("SELECT * FROM document_categories ORDER BY name")->fetchAll();
        $settings = db()->query("SELECT * FROM settings ORDER BY setting_group, setting_key")->fetchAll();
        require_once __DIR__ . '/../layouts/header.php';
        require_once __DIR__ . '/../views/settings/index.php';
        require_once __DIR__ . '/../layouts/footer.php';
    }
}

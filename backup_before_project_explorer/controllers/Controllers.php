<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/Permissions.php';
require_once __DIR__ . '/../includes/AssignmentVisibility.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/FacultyModel.php';
require_once __DIR__ . '/../models/ProgramModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';

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
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $result = AssignmentVisibility::getVisibleDocuments($_GET['search'] ?? '', $_GET['category'] ?? '', $limit, $offset);
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

        $result = AssignmentVisibility::getVisibleReports($_GET['search'] ?? '', $_GET['type'] ?? '', $limit, $offset);
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

        $role = $_SESSION['role'] ?? '';
        if ($role === 'admin') {
            $moas = db()->query("SELECT m.*, p.title as project_title FROM moas m LEFT JOIN projects p ON m.project_id = p.id ORDER BY m.created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
            $total = db()->query("SELECT COUNT(*) as c FROM moas")->fetch()['c'];
        } else {
            $fid = Permissions::getFacultyId();
            $stmt = db()->prepare("SELECT m.*, p.title as project_title FROM moas m LEFT JOIN projects p ON m.project_id = p.id WHERE m.project_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1) ORDER BY m.created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$fid, $limit, $offset]);
            $moas = $stmt->fetchAll();
            $cstmt = db()->prepare("SELECT COUNT(*) as c FROM moas WHERE project_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)");
            $cstmt->execute([$fid]);
            $total = $cstmt->fetch()['c'];
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

<?php
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/Permissions.php';
require_once __DIR__ . '/includes/AssignmentVisibility.php';
require_once __DIR__ . '/models/BaseModel.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/FacultyModel.php';
require_once __DIR__ . '/models/ProgramModel.php';
require_once __DIR__ . '/models/ProjectModel.php';
require_once __DIR__ . '/controllers/Controllers.php';

requireLogin();

$module = $_GET['module'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

switch ($module) {
    case 'dashboard':
        (new DashboardController())->index();
        break;
    case 'explorer':
        require_once __DIR__ . '/layouts/header.php';
        require_once __DIR__ . '/views/explorer/index.php';
        require_once __DIR__ . '/layouts/footer.php';
        break;
    case 'programs':
        $c = new ProgramController();
        if ($action === 'create') $c->create();
        elseif ($action === 'view' && $id) $c->view($id);
        else $c->index();
        break;
    case 'projects':
        $c = new ProjectController();
        if ($action === 'create') $c->create();
        elseif ($action === 'view' && $id) $c->view($id);
        else $c->index();
        break;
    case 'project-files':
        require_once __DIR__ . '/layouts/header.php';
        require_once __DIR__ . '/views/projects/files.php';
        require_once __DIR__ . '/layouts/footer.php';
        break;
    case 'components':
        (new ComponentController())->index();
        break;
    case 'activities':
        (new ActivityController())->index();
        break;
    case 'faculty':
        (new FacultyController())->index();
        break;
    case 'proposals':
        (new ProposalController())->index();
        break;
    case 'documents':
    case 'designations':
        (new DocumentController())->index();
        break;
    case 'reports':
        (new ReportController())->index();
        break;
    case 'moa':
        (new MoaController())->index();
        break;
    case 'certificates':
        (new CertificateController())->index();
        break;
    case 'partners':
        Permissions::requirePermission(Permissions::canManagePartners());
        (new PartnerController())->index();
        break;
    case 'beneficiaries':
        Permissions::requirePermission(Permissions::canManageBeneficiaries());
        (new BeneficiaryController())->index();
        break;
    case 'notifications':
        (new NotificationController())->index();
        break;
    case 'audit-logs':
        (new AuditController())->index();
        break;
    case 'settings':
        (new SettingController())->index();
        break;
    case 'users':
        Permissions::requirePermission(Permissions::canManageUsers());
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $userResult = (new UserModel())->getAllWithProfile($_GET['search'] ?? '', $_GET['role'] ?? '', $limit, $offset);
        $users = $userResult['data'];
        $totalPages = ceil($userResult['total'] / $limit);
        $departments = db()->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY name")->fetchAll();
        require_once __DIR__ . '/layouts/header.php';
        require_once __DIR__ . '/views/users/index.php';
        require_once __DIR__ . '/layouts/footer.php';
        break;
    case 'analytics':
        (new DashboardController())->index();
        break;
    default:
        (new DashboardController())->index();
        break;
}

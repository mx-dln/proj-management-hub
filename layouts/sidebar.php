<?php
$currentPage = $_GET['module'] ?? 'dashboard';
$user = currentUser();
$notifCount = getUnreadNotificationCount($user['id']);
$projectPages = ['projects', 'programs', 'components', 'activities', 'project-files'];
$primaryItems = [
    ['icon' => 'fas fa-chart-pie', 'label' => 'Dashboard', 'module' => 'dashboard', 'roles' => ['admin','faculty','viewer']],
    ['icon' => 'fas fa-diagram-project', 'label' => 'Project Management', 'module' => 'projects', 'roles' => ['admin','faculty','viewer'], 'children' => [
        ['icon' => 'fas fa-layer-group', 'label' => 'Programs', 'module' => 'programs'],
        ['icon' => 'fas fa-folder-open', 'label' => 'Projects', 'module' => 'projects'],
        ['icon' => 'fas fa-puzzle-piece', 'label' => 'Components', 'module' => 'components'],
        ['icon' => 'fas fa-calendar-check', 'label' => 'Activities', 'module' => 'activities'],
        ['icon' => 'fas fa-folder-tree', 'label' => 'Files', 'module' => 'project-files'],
    ]],
    ['icon' => 'fas fa-file-alt', 'label' => 'Proposals', 'module' => 'proposals', 'roles' => ['admin','faculty']],
    ['icon' => 'fas fa-file-signature', 'label' => 'Designation', 'module' => 'designations', 'roles' => ['admin','faculty','viewer']],
    ['icon' => 'fas fa-handshake', 'label' => 'MOA', 'module' => 'moa', 'roles' => ['admin','faculty','viewer']],
    ['icon' => 'fas fa-clipboard-check', 'label' => 'Accomplishments', 'module' => 'reports', 'roles' => ['admin','faculty','viewer']],
    ['icon' => 'fas fa-award', 'label' => 'Certificates', 'module' => 'certificates', 'roles' => ['admin','faculty','viewer']],
];

$navGroups = [
    'records' => [
        'label' => 'Records',
        'items' => [
            ['icon' => 'fas fa-archive', 'label' => 'Documents', 'module' => 'documents', 'roles' => ['admin','faculty','viewer']],
        ]
    ],
    'people' => [
        'label' => 'People',
        'items' => [
            ['icon' => 'fas fa-user-tie', 'label' => 'Faculty', 'module' => 'faculty', 'roles' => ['admin','viewer']],
            ['icon' => 'fas fa-building', 'label' => 'Partners', 'module' => 'partners', 'roles' => ['admin']],
            ['icon' => 'fas fa-people-group', 'label' => 'Beneficiaries', 'module' => 'beneficiaries', 'roles' => ['admin']],
        ]
    ],
    'monitoring' => [
        'label' => 'Monitoring',
        'items' => [
            ['icon' => 'fas fa-chart-bar', 'label' => 'Analytics', 'module' => 'analytics', 'roles' => ['admin','viewer']],
            ['icon' => 'fas fa-history', 'label' => 'Audit Logs', 'module' => 'audit-logs', 'roles' => ['admin']],
        ]
    ],
    'admin' => [
        'label' => 'Administration',
        'items' => [
            ['icon' => 'fas fa-users-cog', 'label' => 'Users', 'module' => 'users', 'roles' => ['admin']],
            ['icon' => 'fas fa-cog', 'label' => 'Settings', 'module' => 'settings', 'roles' => ['admin']],
        ]
    ],
];

$activeGroup = '';
foreach ($navGroups as $groupKey => $group) {
    foreach ($group['items'] as $item) {
        if ($item['module'] === $currentPage) {
            $activeGroup = $groupKey;
            break;
        }
    }
}
?>
<aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-64 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out shadow-xl">
    <div class="px-4 py-5 border-b border-[#1E7A4B]">
        <div class="flex items-center gap-3">
            <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="ISU Logo" class="w-10 h-10 object-contain" onerror="this.style.display='none'">
            <div>
                <h2 class="text-white font-bold text-sm leading-tight">ISU–Cauayan</h2>
                <p class="text-[10px] text-white/50 leading-tight">Extension Training Services</p>
            </div>
        </div>
        <p class="text-[10px] text-white/30 mt-2">Project Management Hub v1.0</p>
    </div>

    <nav class="mt-3 px-3 overflow-y-auto h-[calc(100vh-7rem)]" aria-label="Main navigation">
        <div class="pb-3 mb-3 border-b border-[#1E7A4B]" data-primary-navigation>
            <?php foreach ($primaryItems as $item): ?>
                <?php if (!in_array($user['role'], $item['roles'], true)) continue;
                $isActive = $currentPage === $item['module'] || ($item['module'] === 'projects' && in_array($currentPage, $projectPages, true)); ?>
                <a href="<?= SITE_URL ?>/index.php?module=<?= $item['module'] ?>" class="sidebar-item <?= $isActive ? 'active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?> aria-label="<?= e($item['label']) ?>">
                    <i class="<?= $item['icon'] ?>" aria-hidden="true"></i><span><?= e($item['label']) ?></span>
                </a>
                <?php if (!empty($item['children']) && $isActive): ?>
                    <div class="ml-5 mt-1 mb-2 border-l border-[#1E7A4B] pl-2">
                        <?php foreach ($item['children'] as $child): ?>
                            <a href="<?= SITE_URL ?>/index.php?module=<?= e($child['module']) ?>" class="sidebar-item <?= $currentPage === $child['module'] ? 'active' : '' ?> text-sm" aria-label="<?= e($child['label']) ?>">
                                <i class="<?= e($child['icon']) ?>" aria-hidden="true"></i><span><?= e($child['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php foreach ($navGroups as $groupKey => $group): ?>
            <?php
            $hasAccess = false;
            foreach ($group['items'] as $item) {
                if (in_array($user['role'], $item['roles'])) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) continue;
            $isOpen = ($activeGroup === $groupKey);
            ?>
            <div class="sidebar-group <?= $isOpen ? 'open' : '' ?>" data-group="<?= $groupKey ?>">
                <div class="sidebar-group-header" onclick="toggleSidebarGroup('<?= $groupKey ?>')" role="button" tabindex="0" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                    <span class="group-label"><?= $group['label'] ?></span>
                    <i class="fas fa-chevron-down group-chevron"></i>
                </div>
                <div class="sidebar-group-items">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php if (in_array($user['role'], $item['roles'])): ?>
                            <a href="<?= SITE_URL ?>/index.php?module=<?= $item['module'] ?>" 
                               class="sidebar-item <?= $currentPage === $item['module'] ? 'active' : '' ?>"
                               aria-label="<?= $item['label'] ?>">
                                <i class="<?= $item['icon'] ?>"></i>
                                <span><?= $item['label'] ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>
<div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black/60 hidden lg:hidden" onclick="toggleSidebar()"></div>

<?php
$user = currentUser();
$notifCount = getUnreadNotificationCount($user['id']);
$currentPage = $_GET['module'] ?? 'dashboard';
$currentAction = $_GET['action'] ?? 'index';

// Build breadcrumbs
$pageLabels = [
    'dashboard' => 'Dashboard',
    'programs' => 'Programs',
    'projects' => 'Projects',
    'components' => 'Components',
    'activities' => 'Activities',
    'faculty' => 'Faculty',
    'proposals' => 'Proposals',
    'documents' => 'Documents',
    'reports' => 'Reports',
    'moa' => 'MOAs',
    'certificates' => 'Certificates',
    'partners' => 'Partner Agencies',
    'beneficiaries' => 'Beneficiaries',
    'notifications' => 'Notifications',
    'audit-logs' => 'Audit Logs',
    'settings' => 'Settings',
];

$actionLabels = [
    'index' => '',
    'create' => 'Create',
    'view' => 'View',
    'edit' => 'Edit',
];

$breadcrumbs = [['label' => 'Dashboard', 'url' => SITE_URL . '/index.php']];
if ($currentPage !== 'dashboard') {
    $breadcrumbs[] = ['label' => $pageLabels[$currentPage] ?? ucfirst($currentPage), 'url' => SITE_URL . '/index.php?module=' . $currentPage];
    if ($currentAction !== 'index' && $actionLabels[$currentAction] ?? false) {
        $breadcrumbs[] = ['label' => $actionLabels[$currentAction], 'url' => null];
    }
}
?>
<header class="bg-[#1F2937] border-b border-[#374151] h-14 flex items-center justify-between px-4 md:px-6 sticky top-0 z-30">
    <!-- Left: Mobile menu + Breadcrumbs -->
    <div class="flex items-center gap-4">
        <button onclick="toggleSidebar()" class="lg:hidden text-[#9CA3AF] hover:text-[#F9FAFB] p-1" aria-label="Toggle sidebar">
            <i class="fas fa-bars text-lg"></i>
        </button>
        
        <!-- Breadcrumbs -->
        <nav class="breadcrumb hidden md:flex" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <?php if ($i > 0): ?>
                    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
                <?php if ($crumb['url'] && $i < count($breadcrumbs) - 1): ?>
                    <a href="<?= $crumb['url'] ?>" class="breadcrumb-item hover:text-[#D1D5DB] transition-colors"><?= $crumb['label'] ?></a>
                <?php else: ?>
                    <span class="breadcrumb-item active"><?= $crumb['label'] ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Right: Actions -->
    <div class="flex items-center gap-1">
        <!-- Search (Desktop) -->
        <div class="hidden lg:block mr-2">
            <form action="<?= SITE_URL ?>/index.php" method="GET">
                <input type="hidden" name="module" value="search">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#6B7280] text-xs"></i>
                    <input type="text" name="q" placeholder="Search..." class="filter-input pl-9 w-56 text-sm py-1.5" value="<?= e($_GET['q'] ?? '') ?>">
                </div>
            </form>
        </div>

        <!-- Notifications -->
        <div class="relative" id="notif-dropdown-container">
            <button onclick="toggleNotifDropdown()" class="action-btn relative" aria-label="Notifications">
                <i class="fas fa-bell text-sm"></i>
                <?php if ($notifCount > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 bg-[#DC2626] text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold" id="notif-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
                <?php endif; ?>
            </button>
            <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-[#1F2937] rounded-xl shadow-xl border border-[#374151] overflow-hidden z-50">
                <div class="p-3 border-b border-[#374151] flex items-center justify-between">
                    <span class="font-semibold text-[#F9FAFB] text-sm">Notifications</span>
                    <button onclick="markAllNotifRead()" class="text-xs text-[#86EFAC] hover:text-[#0F643A] transition-colors">Mark all read</button>
                </div>
                <div id="notif-list" class="max-h-64 overflow-y-auto">
                    <div class="p-4 text-center text-[#6B7280] text-sm">Loading...</div>
                </div>
                <a href="<?= SITE_URL ?>/index.php?module=notifications" class="block p-2.5 text-center text-sm text-[#86EFAC] hover:bg-[#324152] border-t border-[#374151] transition-colors font-medium">
                    View all notifications
                </a>
            </div>
        </div>

        <!-- User Manual -->
        <button onclick="openUserManual()" class="action-btn" aria-label="User Manual" title="User Manual">
            <i class="fas fa-question-circle text-sm"></i>
        </button>

        <!-- Settings Shortcut -->
        <?php if ($user['role'] === 'admin'): ?>
            <a href="<?= SITE_URL ?>/index.php?module=settings" class="action-btn" aria-label="Settings">
                <i class="fas fa-cog text-sm"></i>
            </a>
        <?php endif; ?>

        <!-- User Menu -->
        <div class="relative ml-1" id="user-dropdown-container">
            <button onclick="toggleUserDropdown()" class="flex items-center gap-2 p-1 rounded-lg hover:bg-[#324152] transition-colors">
                <div class="w-8 h-8 bg-[#14532D] rounded-lg flex items-center justify-center">
                    <i class="fas fa-user text-[#86EFAC] text-xs"></i>
                </div>
                <span class="hidden md:block text-sm font-medium text-[#F9FAFB] max-w-[100px] truncate"><?= e($user['name'] ?: $user['username']) ?></span>
                <i class="fas fa-chevron-down text-[10px] text-[#6B7280] hidden md:block"></i>
            </button>
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-[#1F2937] rounded-xl shadow-xl border border-[#374151] overflow-hidden z-50">
                <div class="px-4 py-3 border-b border-[#374151]">
                    <p class="text-sm font-medium text-[#F9FAFB]"><?= e($user['name'] ?: $user['username']) ?></p>
                    <p class="text-xs text-[#6B7280]"><?= e($user['email']) ?></p>
                    <span class="inline-block mt-1 text-[10px] px-2 py-0.5 rounded-full bg-[#14532D] text-[#86EFAC] font-medium"><?= ucfirst($user['role']) ?></span>
                </div>
                <div class="py-1">
                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-[#D1D5DB] hover:bg-[#324152] transition-colors">
                        <i class="fas fa-user-circle w-4 text-center text-[#9CA3AF]"></i> Profile
                    </a>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-[#D1D5DB] hover:bg-[#324152] transition-colors">
                        <i class="fas fa-key w-4 text-center text-[#9CA3AF]"></i> Change Password
                    </a>
                </div>
                <div class="border-t border-[#374151] py-1">
                    <a href="<?= SITE_URL ?>/logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-[#FCA5A5] hover:bg-[#7F1D1D] transition-colors">
                        <i class="fas fa-sign-out-alt w-4 text-center"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

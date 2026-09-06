<div class="mb-6">
    <h1 class="text-2xl font-bold text-[#F9FAFB]">Dashboard</h1>
    <p class="text-[#9CA3AF] text-sm mt-1">Welcome back, <?= e(currentUser()['name'] ?: currentUser()['username']) ?></p>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
<!-- Admin Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card animate-fade-in"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Programs</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['programs'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-layer-group text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.1s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Projects</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['projects'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-folder-open text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.2s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Components</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['components'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-puzzle-piece text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.3s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Activities</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['activities'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-calendar-check text-[#86EFAC] text-2xl"></i></div></div></div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card animate-fade-in" style="animation-delay:0.4s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Ongoing</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['ongoing'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-spinner text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.5s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Completed</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['completed'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-check-circle text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.6s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Pending Proposals</p><p class="text-3xl font-bold text-[#FCD34D] mt-2"><?= $stats['pending_proposals'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#78350F]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-file-alt text-[#FCD34D] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.7s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Faculty</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['faculty'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-users text-[#86EFAC] text-2xl"></i></div></div></div>
</div>

<?php elseif ($_SESSION['role'] === 'faculty'): ?>
<!-- Faculty Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card animate-fade-in"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">My Programs</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['my_programs'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-layer-group text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.1s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">My Projects</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['my_projects'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-folder-open text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.2s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">My Components</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['my_components'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-puzzle-piece text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.3s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">My Activities</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['my_activities'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-calendar-check text-[#86EFAC] text-2xl"></i></div></div></div>
</div>

<!-- Assigned Items -->
<?php
$facultyId = $_SESSION['faculty_id'] ?? null;
if ($facultyId):
    $myPrograms = db()->prepare("SELECT p.*, pa.assignment_type FROM programs p JOIN program_assignments pa ON p.id = pa.program_id WHERE pa.faculty_id = ? AND pa.is_active = 1 AND p.deleted_at IS NULL ORDER BY p.title LIMIT 5");
    $myPrograms->execute([$facultyId]);
    $myPrograms = $myPrograms->fetchAll();

    $myProjects = db()->prepare("SELECT p.*, pa.assignment_type FROM projects p JOIN project_assignments pa ON p.id = pa.project_id WHERE pa.faculty_id = ? AND pa.is_active = 1 AND p.deleted_at IS NULL ORDER BY p.title LIMIT 5");
    $myProjects->execute([$facultyId]);
    $myProjects = $myProjects->fetchAll();

    $myActivities = db()->prepare("SELECT ea.*, aa.assignment_type FROM extension_activities ea JOIN activity_assignments aa ON ea.id = aa.activity_id WHERE aa.faculty_id = ? AND aa.is_active = 1 AND ea.deleted_at IS NULL AND ea.start_datetime >= CURDATE() ORDER BY ea.start_datetime LIMIT 5");
    $myActivities->execute([$facultyId]);
    $myActivities = $myActivities->fetchAll();
?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- My Programs -->
    <div class="card animate-fade-in">
        <div class="card-header"><h3 class="text-sm font-semibold text-[#F9FAFB]">My Programs</h3></div>
        <div class="card-body">
            <?php if (empty($myPrograms)): ?>
                <div class="text-center py-4"><i class="fas fa-layer-group text-2xl text-[#374151] mb-2"></i><p class="text-sm text-[#6B7280]">No assigned programs</p></div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($myPrograms as $p): ?>
                        <a href="<?= SITE_URL ?>/index.php?module=programs" class="flex items-center gap-3 p-3 rounded-xl bg-[#111827] border border-[#374151] hover:border-[#0F643A] transition-colors">
                            <div class="w-10 h-10 bg-[#14532D] rounded-lg flex items-center justify-center"><i class="fas fa-layer-group text-[#86EFAC]"></i></div>
                            <div class="flex-1 min-w-0"><p class="text-sm font-medium text-[#F9FAFB] truncate"><?= e($p['title']) ?></p><p class="text-xs text-[#6B7280]"><?= ucfirst($p['assignment_type']) ?></p></div>
                            <?= getStatusBadge($p['status']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Projects -->
    <div class="card animate-fade-in">
        <div class="card-header"><h3 class="text-sm font-semibold text-[#F9FAFB]">My Projects</h3></div>
        <div class="card-body">
            <?php if (empty($myProjects)): ?>
                <div class="text-center py-4"><i class="fas fa-folder-open text-2xl text-[#374151] mb-2"></i><p class="text-sm text-[#6B7280]">No assigned projects</p></div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($myProjects as $p): ?>
                        <a href="<?= SITE_URL ?>/index.php?module=projects" class="flex items-center gap-3 p-3 rounded-xl bg-[#111827] border border-[#374151] hover:border-[#0F643A] transition-colors">
                            <div class="w-10 h-10 bg-[#14532D] rounded-lg flex items-center justify-center"><i class="fas fa-folder-open text-[#86EFAC]"></i></div>
                            <div class="flex-1 min-w-0"><p class="text-sm font-medium text-[#F9FAFB] truncate"><?= e($p['title']) ?></p><p class="text-xs text-[#6B7280]"><?= ucfirst($p['assignment_type']) ?></p></div>
                            <?= getStatusBadge($p['status']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Upcoming Activities -->
<div class="card animate-fade-in mb-6">
    <div class="card-header"><h3 class="text-sm font-semibold text-[#F9FAFB]">Upcoming Activities</h3></div>
    <div class="card-body">
        <?php if (empty($myActivities)): ?>
            <div class="text-center py-4"><i class="fas fa-calendar-check text-2xl text-[#374151] mb-2"></i><p class="text-sm text-[#6B7280]">No upcoming activities</p></div>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($myActivities as $a): ?>
                    <a href="<?= SITE_URL ?>/index.php?module=activities" class="flex items-center gap-3 p-3 rounded-xl bg-[#111827] border border-[#374151] hover:border-[#0F643A] transition-colors">
                        <div class="w-10 h-10 bg-[#1E3A8A] rounded-lg flex items-center justify-center"><i class="fas fa-calendar text-[#BFDBFE]"></i></div>
                        <div class="flex-1 min-w-0"><p class="text-sm font-medium text-[#F9FAFB] truncate"><?= e($a['title']) ?></p><p class="text-xs text-[#6B7280]"><?= $a['start_datetime'] ? date('M d, Y h:i A', strtotime($a['start_datetime'])) : '-' ?></p></div>
                        <?= getStatusBadge($a['status']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php elseif ($_SESSION['role'] === 'viewer'): ?>
<!-- Viewer Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card animate-fade-in"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Programs</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['programs'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-layer-group text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.1s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Projects</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['projects'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-folder-open text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.2s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Components</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['components'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-puzzle-piece text-[#86EFAC] text-2xl"></i></div></div></div>
    <div class="stat-card animate-fade-in" style="animation-delay:0.3s"><div class="flex items-center justify-between"><div><p class="text-xs text-[#9CA3AF] uppercase tracking-wider font-medium">Activities</p><p class="text-3xl font-bold text-[#F9FAFB] mt-2"><?= $stats['activities'] ?? 0 ?></p></div><div class="w-14 h-14 bg-[#14532D]/50 rounded-2xl flex items-center justify-center"><i class="fas fa-calendar-check text-[#86EFAC] text-2xl"></i></div></div></div>
</div>
<?php endif; ?>

<!-- Recent Activities & Notifications -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card animate-fade-in">
        <div class="card-header"><h3 class="text-sm font-semibold text-[#F9FAFB]">Recent Activities</h3></div>
        <div class="card-body">
            <?php if (empty($recentActivities)): ?><div class="text-center py-6"><i class="fas fa-history text-3xl text-[#374151] mb-2"></i><p class="text-sm text-[#6B7280]">No recent activities</p></div>
            <?php else: ?><div class="space-y-2"><?php foreach (array_slice($recentActivities, 0, 5) as $act): ?><div class="flex items-center gap-3 p-3 rounded-xl bg-[#111827] border border-[#374151]"><div class="w-8 h-8 bg-[#14532D] rounded-full flex items-center justify-center"><i class="fas fa-circle text-[6px] text-[#86EFAC]"></i></div><div class="flex-1 min-w-0"><p class="text-sm text-[#D1D5DB]"><span class="font-medium text-[#F9FAFB]"><?= e($act['username'] ?? 'System') ?></span> <?= e($act['description'] ?? $act['action']) ?></p><p class="text-xs text-[#6B7280] mt-0.5"><?= timeAgo($act['created_at']) ?></p></div></div><?php endforeach; ?></div><?php endif; ?>
        </div>
    </div>
    <div class="card animate-fade-in">
        <div class="card-header"><h3 class="text-sm font-semibold text-[#F9FAFB]">Notifications</h3></div>
        <div class="card-body">
            <?php if (empty($notifications)): ?><div class="text-center py-6"><i class="fas fa-bell-slash text-3xl text-[#374151] mb-2"></i><p class="text-sm text-[#6B7280]">No new notifications</p></div>
            <?php else: ?><div class="space-y-2"><?php foreach ($notifications as $n): ?><a href="<?= $n['action_url'] ? SITE_URL . $n['action_url'] : '#' ?>" class="flex items-center gap-3 p-3 rounded-xl bg-[#111827] border border-[#374151] hover:border-[#0F643A] transition-colors"><div class="w-8 h-8 bg-[#1E3A8A] rounded-full flex items-center justify-center"><i class="fas fa-bell text-[#BFDBFE] text-xs"></i></div><div class="flex-1 min-w-0"><p class="text-sm font-medium text-[#F9FAFB]"><?= e($n['title']) ?></p><p class="text-xs text-[#6B7280] mt-0.5"><?= e($n['message']) ?></p></div></a><?php endforeach; ?></div><?php endif; ?>
        </div>
    </div>
</div>

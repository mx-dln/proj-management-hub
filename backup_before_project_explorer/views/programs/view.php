<?php $canEdit = Permissions::canEditProgram($program['id']); ?>
<div class="mb-6"><a href="<?= SITE_URL ?>/index.php?module=programs" class="text-[#86EFAC] hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Programs</a></div>
<div class="card mb-6">
    <div class="card-body">
        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2"><span class="text-sm font-mono text-[#86EFAC] bg-[#14532D] px-2 py-0.5 rounded"><?= e($program['program_code']) ?></span><?= getStatusBadge($program['status']) ?></div>
                <h1 class="text-2xl font-bold text-[#F9FAFB]"><?= e($program['title']) ?></h1>
                <p class="text-[#D1D5DB] mt-2"><?= e($program['description'] ?? '') ?></p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div><p class="text-xs text-[#9CA3AF]">College</p><p class="text-sm font-medium text-[#F9FAFB]"><?= e($program['college'] ?? '-') ?></p></div>
                    <div><p class="text-xs text-[#9CA3AF]">Campus</p><p class="text-sm font-medium text-[#F9FAFB]"><?= e($program['campus'] ?? '-') ?></p></div>
                    <div><p class="text-xs text-[#9CA3AF]">Duration</p><p class="text-sm font-medium text-[#F9FAFB]"><?= formatDate($program['start_date']) ?> - <?= formatDate($program['end_date']) ?></p></div>
                    <div><p class="text-xs text-[#9CA3AF]">Budget</p><p class="text-sm font-medium text-[#F9FAFB]"><?= formatCurrency($program['budget_allocation']) ?></p></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">Projects</h3></div><div class="card-body">
        <?php if (empty($projects)): ?><p class="text-[#6B7280] text-sm">No projects yet</p>
        <?php else: ?><div class="space-y-2"><?php foreach ($projects as $p): ?><a href="<?= SITE_URL ?>/index.php?module=projects&action=view&id=<?= $p['id'] ?>" class="flex items-center gap-3 p-3 rounded-lg bg-[#111827] border border-[#374151] hover:border-[#0F643A] transition-colors"><div class="w-10 h-10 bg-[#14532D] rounded-lg flex items-center justify-center"><i class="fas fa-folder text-[#86EFAC]"></i></div><div class="flex-1"><p class="text-sm font-medium text-[#F9FAFB]"><?= e($p['title']) ?></p><p class="text-xs text-[#6B7280]"><?= e($p['project_code']) ?></p></div><?= getStatusBadge($p['status']) ?></a><?php endforeach; ?></div><?php endif; ?>
    </div></div>
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">Team Members</h3></div><div class="card-body">
        <?php if (empty($members)): ?><p class="text-[#6B7280] text-sm">No members assigned</p>
        <?php else: ?><div class="space-y-2"><?php foreach ($members as $m): ?><div class="flex items-center gap-3 p-3 rounded-lg bg-[#111827] border border-[#374151]"><div class="avatar"><i class="fas fa-user text-sm"></i></div><div class="flex-1"><p class="text-sm font-medium text-[#F9FAFB]"><?= e($m['first_name'] . ' ' . $m['last_name']) ?></p><p class="text-xs text-[#6B7280]"><?= e($m['department_name'] ?? '') ?> &middot; <?= ucfirst($m['assignment_type']) ?></p></div></div><?php endforeach; ?></div><?php endif; ?>
    </div></div>
</div>

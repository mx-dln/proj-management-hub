<?php $canEdit = Permissions::canEditProject($project['id']); ?>
<div class="mb-6"><a href="<?= SITE_URL ?>/index.php?module=projects" class="text-[#86EFAC] hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Projects</a></div>
<div class="card mb-6"><div class="card-body">
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2"><span class="text-sm font-mono text-[#86EFAC] bg-[#14532D] px-2 py-0.5 rounded"><?= e($project['project_code']) ?></span><?= getStatusBadge($project['status']) ?></div>
            <h1 class="text-2xl font-bold text-[#F9FAFB]"><?= e($project['title']) ?></h1>
            <p class="text-[#D1D5DB] mt-2"><?= e($project['description'] ?? '') ?></p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                <div><p class="text-xs text-[#9CA3AF]">Program</p><p class="text-sm font-medium text-[#F9FAFB]"><?= e($program['title'] ?? '-') ?></p></div>
                <div><p class="text-xs text-[#9CA3AF]">Budget</p><p class="text-sm font-medium text-[#F9FAFB]"><?= formatCurrency($project['budget']) ?></p></div>
                <div><p class="text-xs text-[#9CA3AF]">Duration</p><p class="text-sm font-medium text-[#F9FAFB]"><?= formatDate($project['start_date']) ?> - <?= formatDate($project['end_date']) ?></p></div>
                <div><p class="text-xs text-[#9CA3AF]">Location</p><p class="text-sm font-medium text-[#F9FAFB]"><?= e($project['location'] ?? '-') ?></p></div>
            </div>
        </div>
        <div class="text-center"><div class="relative w-20 h-20"><svg class="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36"><circle cx="18" cy="18" r="16" fill="none" stroke="#374151" stroke-width="3"/><circle cx="18" cy="18" r="16" fill="none" stroke="#0F643A" stroke-width="3" stroke-dasharray="<?= $project['completion_percentage'] ?> <?= 100-$project['completion_percentage'] ?>" stroke-linecap="round"/></svg><span class="absolute inset-0 flex items-center justify-center text-lg font-bold text-[#F9FAFB]"><?= $project['completion_percentage'] ?>%</span></div></div>
    </div>
</div></div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">Team</h3></div><div class="card-body">
        <?php if(empty($assignments)): ?><p class="text-[#6B7280] text-sm">No team members</p>
        <?php else: ?><div class="space-y-2"><?php foreach($assignments as $a): ?><div class="flex items-center gap-3 p-3 rounded-lg bg-[#111827] border border-[#374151]"><div class="avatar"><i class="fas fa-user text-sm"></i></div><div class="flex-1"><p class="text-sm font-medium text-[#F9FAFB]"><?= e($a['first_name'].' '.$a['last_name']) ?></p><p class="text-xs text-[#6B7280]"><?= ucfirst($a['assignment_type']) ?></p></div></div><?php endforeach; ?></div><?php endif; ?>
    </div></div>
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">Components</h3></div><div class="card-body">
        <?php if(empty($components)): ?><p class="text-[#6B7280] text-sm">No components yet</p>
        <?php else: ?><div class="space-y-2"><?php foreach($components as $c): ?><div class="flex items-center gap-3 p-3 rounded-lg bg-[#111827] border border-[#374151]"><div class="w-10 h-10 bg-[#14532D] rounded-lg flex items-center justify-center"><i class="fas fa-puzzle-piece text-[#86EFAC]"></i></div><div class="flex-1"><p class="text-sm font-medium text-[#F9FAFB]"><?= e($c['title']) ?></p><p class="text-xs text-[#6B7280]"><?= e($c['component_code']) ?></p></div><?= getStatusBadge($c['status']) ?></a></div><?php endforeach; ?></div><?php endif; ?>
    </div></div>
</div>

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

<?php
$currentYear = (int)date('Y');
$years = range($currentYear, max(2021, $currentYear - 5));
$reportsByQuarter = [];
foreach ($projectReports ?? [] as $report) {
    $year = (int)($report['period_year'] ?: $currentYear);
    $quarter = $report['period_quarter'] ?: 'Q1';
    $reportsByQuarter[$year][$quarter][] = $report;
}
?>

<div class="project-hub mt-6">
    <div class="project-hub-header">
        <h2>Project Files and Records</h2>
        <p>Proposal, project files, MOA, accomplishments, certificates, and yearly Drive folders for this project.</p>
    </div>

    <section class="project-section">
        <div class="section-title"><h3>Proposals</h3><a href="<?= SITE_URL ?>/index.php?module=proposals&search=<?= urlencode($project['title']) ?>" class="btn-ghost">Open Module</a></div>
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Number</th><th>Title</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            <?php foreach (($proposals ?? []) as $p): ?><tr>
                <td><span class="table-code"><?= e($p['proposal_number'] ?? '-') ?></span></td>
                <td><span class="table-title"><?= e($p['title'] ?? $project['title']) ?></span></td>
                <td><?= formatDate($p['date_submitted'] ?? null) ?></td>
                <td><?= getStatusBadge($p['status'] ?? 'draft') ?></td>
                <td><a class="btn-ghost" href="<?= SITE_URL ?>/index.php?module=proposals&search=<?= urlencode($p['proposal_number'] ?? $project['title']) ?>">View / Review</a></td>
            </tr><?php endforeach; ?>
            <?php if(empty($proposals)): ?><tr><td colspan="5" class="text-center text-[#6B7280] py-6">No proposal history yet</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>

    <section class="project-section">
        <div class="section-title"><h3>Designation / Project Files</h3><a href="<?= SITE_URL ?>/index.php?module=designations" class="btn-primary"><i class="fas fa-upload mr-1"></i> Upload</a></div>
        <?php foreach(($projectDocuments ?? []) as $d): ?>
            <div class="project-file-row"><div><i class="fas fa-caret-right mr-2"></i><strong><?= e($d['title']) ?></strong><small><?= e($d['file_name']) ?> · <?= e($d['category_name'] ?? 'File') ?></small></div><a class="btn-ghost" href="<?= SITE_URL ?>/index.php?module=designations&search=<?= urlencode($d['title']) ?>">View</a></div>
        <?php endforeach; ?>
        <?php if(empty($projectDocuments)): ?><div class="project-file-row muted"><div><i class="fas fa-caret-right mr-2"></i>No project files yet</div><a class="btn-primary" href="<?= SITE_URL ?>/index.php?module=designations"><i class="fas fa-upload mr-1"></i> Upload</a></div><?php endif; ?>
    </section>

    <section class="project-section">
        <div class="section-title"><h3>MOA</h3><button onclick="openProjectMoaForm()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New MOA</button></div>
        <div class="project-moa-grid">
            <?php foreach(($projectMoas ?? []) as $m): ?><article class="project-moa-card">
                <dl><dt>MOA #:</dt><dd><?= e($m['moa_number']) ?></dd><dt>Partner:</dt><dd><?= e($m['partner_name'] ?? '-') ?></dd><dt>Signed Date:</dt><dd><?= formatDate($m['date_signed']) ?></dd><dt>End Date:</dt><dd><?= formatDate($m['expiration_date']) ?></dd><dt>Status:</dt><dd><?= getStatusBadge($m['status']) ?></dd></dl>
            </article><?php endforeach; ?>
            <?php if(empty($projectMoas)): ?><div class="sketch-empty">No MOA uploaded yet. MOA upload is required when available.</div><?php endif; ?>
        </div>
    </section>

    <section class="project-section">
        <div class="section-title"><h3>Accomplishment</h3><span class="text-sm text-[#9CA3AF]">Per year, multiple files per quarter</span></div>
        <?php foreach($years as $year): ?>
            <h4 class="year-title"><?= $year ?></h4>
            <div class="quarter-grid compact">
                <?php foreach(['Q1','Q2','Q3','Q4'] as $quarter): $items = $reportsByQuarter[$year][$quarter] ?? []; ?>
                    <div class="quarter-card">
                        <h2><?= $quarter ?></h2>
                        <p><?= count($items) ?> file<?= count($items) === 1 ? '' : 's' ?></p>
                        <div class="quarter-actions">
                            <a class="btn-ghost" href="<?= SITE_URL ?>/index.php?module=reports&search=<?= urlencode($project['title']) ?>">View</a>
                            <button onclick="openProjectReportUpload('<?= $quarter ?>', <?= $year ?>)" class="btn-primary">Upload</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="project-section">
        <div class="section-title"><h3>Certificates</h3><a href="<?= SITE_URL ?>/index.php?module=certificates" class="btn-primary">Generate</a></div>
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Certificate #</th><th>Recipient</th><th>Activity</th><th>Issued</th><th>Actions</th></tr></thead><tbody>
            <?php foreach(($projectCertificates ?? []) as $c): ?><tr><td><span class="table-code"><?= e($c['certificate_number']) ?></span></td><td><?= e($c['recipient_name']) ?></td><td><?= e($c['activity_title'] ?? '-') ?></td><td><?= formatDate($c['date_issued']) ?></td><td><a class="btn-ghost" href="<?= SITE_URL ?>/index.php?module=certificates&search=<?= urlencode($c['certificate_number']) ?>">View / Print</a></td></tr><?php endforeach; ?>
            <?php if(empty($projectCertificates)): ?><tr><td colspan="5" class="text-center text-[#6B7280] py-6">No certificates yet</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>

    <section class="project-section">
        <div class="section-title"><h3>Drive</h3><a href="<?= SITE_URL ?>/index.php?module=explorer" class="btn-ghost">Open Drive</a></div>
        <div class="drive-years">
            <?php foreach($years as $year): ?><a href="<?= SITE_URL ?>/index.php?module=explorer&search=<?= $year ?>" class="drive-year"><i class="fas fa-folder"></i><span><?= $year ?></span></a><?php endforeach; ?>
        </div>
    </section>
</div>

<style>
.project-hub{display:grid;gap:18px}.project-hub-header,.project-section{background:#172331;border:1px solid #374151;border-radius:10px;padding:18px}.project-hub-header h2{font-size:22px;font-weight:800;color:#F9FAFB}.project-hub-header p{color:#9CA3AF;font-size:13px;margin-top:4px}
.section-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.section-title h3{font-size:18px;font-weight:800;color:#F9FAFB}.project-file-row{display:flex;align-items:center;justify-content:space-between;gap:12px;background:#111827;border:1px solid #374151;border-radius:8px;padding:12px;margin-bottom:10px}.project-file-row small{display:block;color:#9CA3AF;margin-top:3px}.project-file-row.muted{color:#9CA3AF}
.project-moa-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.project-moa-card{background:#111827;border:1px solid #374151;border-radius:8px;padding:14px}.project-moa-card dl{display:grid;grid-template-columns:110px 1fr;gap:7px}.project-moa-card dt{color:#9CA3AF}.project-moa-card dd{color:#F9FAFB}.year-title{font-weight:800;color:#86EFAC;margin:14px 0 8px}
.quarter-grid.compact{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.quarter-card{background:#111827;border:1px solid #374151;border-radius:8px;padding:14px}.quarter-card h2{font-size:24px;font-weight:800;color:#86EFAC}.quarter-card p{color:#D1D5DB;margin:8px 0}.quarter-actions{display:flex;gap:8px;flex-wrap:wrap}.drive-years{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px}.drive-year{display:flex;align-items:center;gap:10px;background:#111827;border:1px solid #374151;border-radius:8px;padding:13px;color:#F9FAFB}.drive-year i{color:#FCD34D}
@media(max-width:900px){.project-moa-grid,.quarter-grid.compact{grid-template-columns:1fr 1fr}}@media(max-width:640px){.project-moa-grid,.quarter-grid.compact{grid-template-columns:1fr}.section-title,.project-file-row{align-items:flex-start;flex-direction:column}}
</style>

<script>
const projectId = <?= (int)$project['id'] ?>;
const projectTitle = <?= json_encode($project['title']) ?>;
const projectActivities = <?= json_encode(array_map(fn($a) => ['value' => $a['id'], 'label' => $a['title']], $projectActivities ?? [])) ?>;
const projectPartners = <?= json_encode(array_map(fn($p) => ['value' => $p['id'], 'label' => $p['name']], db()->query("SELECT id, name FROM partner_agencies WHERE is_active = 1 ORDER BY name")->fetchAll())) ?>;
function openProjectMoaForm(){
    const sl = getSlideOver({size:'md'});
    sl.openForm('New MOA', projectTitle, `<div class="form-section"><div class="form-grid">
        ${fieldHtml({name:'moa_number',label:'MOA Information',required:true,placeholder:'MOA-<?= date('Y') ?>-0001'})}
        <input type="hidden" name="project_id" value="${projectId}">
        ${fieldHtml({name:'partner_agency_id',label:'Partner Agency',type:'select',required:true,options:projectPartners})}
        ${fieldHtml({name:'date_signed',label:'Date Signed',type:'date'})}
        ${fieldHtml({name:'expiration_date',label:'End Date',type:'date'})}
        ${fieldHtml({name:'remarks',label:'Remarks',type:'textarea',rows:3,fullWidth:true})}
        <label class="form-label full-width">MOA File <input name="attachment" type="file" accept="application/pdf" class="form-input" required></label>
    </div></div>`, {showSaveAnother:false});
    document.getElementById('slideoverForm').addEventListener('submit', async e => { e.preventDefault(); await submitSlideOverForm(sl, `<?= SITE_URL ?>/ajax/moa.php?action=create`, new FormData(e.target), () => location.reload()); });
}
function openProjectReportUpload(q, year){
    const sl = getSlideOver({size:'md'});
    sl.openForm('Upload ' + q + ' Accomplishment', projectTitle, `<div class="form-section"><div class="form-grid">
        <input type="hidden" name="project_id" value="${projectId}">
        ${fieldHtml({name:'title',label:'Title',required:true,value:projectTitle + ' - ' + q + ' ' + year,fullWidth:true})}
        <input type="hidden" name="report_type" value="quarterly"><input type="hidden" name="period_quarter" value="${q}"><input type="hidden" name="period_year" value="${year}">
        ${fieldHtml({name:'summary',label:'Summary',type:'textarea',rows:3,fullWidth:true})}
        <label class="form-label full-width">File <input name="attachment" type="file" class="form-input" required></label>
    </div></div>`, {showSaveAnother:false});
    document.getElementById('slideoverForm').addEventListener('submit', async e => { e.preventDefault(); await submitSlideOverForm(sl, `<?= SITE_URL ?>/ajax/reports.php?action=create`, new FormData(e.target), () => location.reload()); });
}
</script>

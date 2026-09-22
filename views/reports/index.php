<?php
if (Permissions::isAdmin()) {
    $projectsForReports = db()->query("SELECT id, title FROM projects WHERE deleted_at IS NULL ORDER BY title")->fetchAll();
} else {
    $projectsForReports = [];
}
$canUploadReport = Permissions::isAdmin();
$reportsByQuarter = [];
foreach ($reports as $report) {
    $reportsByQuarter[$report['period_quarter'] ?: 'Q1'][] = $report;
}
?>
<div class="sketch-board">
    <div class="sketch-head">
        <div><h1>Accomplishment</h1><p>Quarterly project files</p></div>
    </div>
    <div class="quarter-grid">
        <?php foreach(['Q1','Q2','Q3','Q4'] as $quarter): $latest = $reportsByQuarter[$quarter][0] ?? null; ?>
            <section class="quarter-card">
                <h2><?= $quarter ?></h2>
                <?php if($latest): ?>
                    <p><?= e($latest['title']) ?></p>
                    <small><?= e($latest['project_title'] ?? '-') ?> · <?= getStatusBadge($latest['status']) ?></small>
                <?php else: ?>
                    <p>No file uploaded</p>
                    <small>Ready for upload</small>
                <?php endif; ?>
                <div class="quarter-actions">
                    <?php if($latest): ?><button onclick="viewReport(<?= (int)$latest['id'] ?>, this)" class="btn-ghost"><i class="fas fa-eye mr-1"></i> View</button><?php endif; ?>
                    <?php if($latest && !empty($latest['attachment'])): ?><button onclick="printReport(<?= (int)$latest['id'] ?>)" class="btn-ghost"><i class="fas fa-print mr-1"></i> Print</button><?php endif; ?>
                    <?php if($canUploadReport): ?><button onclick="openReportUpload('<?= $quarter ?>')" class="btn-primary"><i class="fas fa-upload mr-1"></i> Upload</button><?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="data-table-container mt-6">
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Quarter</th><th>Title</th><th>Project</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach($reports as $r): ?><tr>
            <td><?= e($r['period_quarter'] ?: '-') ?></td>
            <td><span class="table-title"><?= e($r['title']) ?></span></td>
            <td><?= e($r['project_title'] ?? '-') ?></td>
            <td><?= getStatusBadge($r['status']) ?></td>
            <td>
                <button onclick="viewReport(<?= (int)$r['id'] ?>, this)" class="action-btn" title="View" aria-label="View"><i class="fas fa-eye"></i></button>
                <?php if(!empty($r['attachment'])): ?><button onclick="printReport(<?= (int)$r['id'] ?>)" class="action-btn" title="Print" aria-label="Print"><i class="fas fa-print"></i></button><?php endif; ?>
            </td>
        </tr><?php endforeach; ?>
        <?php if(empty($reports)): ?><tr><td colspan="5" class="text-center text-[#6B7280] py-8">No accomplishment files yet</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>

<style>
.quarter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;padding:18px}
.quarter-card{background:#111827;border:1px solid #374151;border-radius:8px;padding:18px;min-height:150px;display:flex;flex-direction:column;justify-content:space-between}
.quarter-card h2{font-size:28px;font-weight:800;color:#86EFAC}.quarter-card p{color:#F9FAFB;font-weight:700;margin-top:12px}.quarter-card small{color:#9CA3AF}.quarter-actions{display:flex;gap:8px;margin-top:16px;flex-wrap:wrap}
@media(max-width:1000px){.quarter-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:640px){.quarter-grid{grid-template-columns:1fr}}
</style>
<script>
const siteUrl = <?= json_encode(SITE_URL) ?>;
const reportProjects = <?= json_encode(array_map(fn($p) => ['value'=>$p['id'],'label'=>$p['title']], $projectsForReports), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
function openReportUpload(q){
    const sl = getSlideOver({size:'md'});
    const formHtml = `<div class="form-section"><div class="form-grid">
        ${fieldHtml({name:'project_id',label:'Project',type:'select',required:true,options:reportProjects,fullWidth:true})}
        ${fieldHtml({name:'title',label:'Title',required:true,value:q + ' Accomplishment Report',fullWidth:true})}
        ${fieldHtml({name:'report_type',label:'Type',type:'select',value:'quarterly',options:[{value:'quarterly',label:'Quarterly'}]})}
        ${fieldHtml({name:'period_quarter',label:'Quarter',type:'select',value:q,options:['Q1','Q2','Q3','Q4'].map(x=>({value:x,label:x}))})}
        ${fieldHtml({name:'period_year',label:'Year',type:'number',value:'<?= date('Y') ?>'})}
        ${fieldHtml({name:'summary',label:'Summary',type:'textarea',rows:3,fullWidth:true})}
        <label class="form-label full-width">File<input name="attachment" type="file" class="form-input"></label>
    </div></div>`;
    sl.openForm(`Upload ${q}`, 'Accomplishment file', formHtml, {showSaveAnother:false});
    document.getElementById('slideoverForm').addEventListener('submit', async e => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/reports.php?action=create`, new FormData(e.target), () => location.reload());
    });
}
async function viewReport(id, btn){
    const data = await fetchWithLoading(`${siteUrl}/ajax/reports.php?action=get&id=${id}`, btn);
    const fileActions = data.attachment ? `<div class="flex flex-wrap gap-2 mb-4"><a href="${siteUrl}/ajax/reports.php?action=file&id=${Number(data.id)}" class="btn-ghost" target="_blank" rel="noopener"><i class="fas fa-eye" aria-hidden="true"></i> Open</a><button type="button" onclick="printReport(${Number(data.id)})" class="btn-ghost"><i class="fas fa-print" aria-hidden="true"></i> Print</button><a href="${siteUrl}/ajax/reports.php?action=file&id=${Number(data.id)}&download=1" class="btn-primary"><i class="fas fa-download" aria-hidden="true"></i> Download</a></div>` : '';
    getSlideOver({size:'md'}).openView(data.title, data.report_number, fileActions + viewSectionHtml('Accomplishment File', [
        viewFieldHtml('Quarter', escapeHtml(data.period_quarter || '-')),
        viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
        viewFieldHtml('Status', getStatusBadge(data.status)),
        viewFieldHtml('Summary', escapeHtml(data.summary || '-'))
    ]), {size:'md'});
}
function printReport(id){
    const win = window.open(`${siteUrl}/ajax/reports.php?action=file&id=${Number(id)}`, '_blank', 'noopener');
    if (!win) { showToast('Allow pop-ups to print this file.', 'error'); return; }
    win.addEventListener('load', () => win.print(), { once: true });
}
</script>

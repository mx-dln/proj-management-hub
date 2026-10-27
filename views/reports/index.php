<?php
if (Permissions::isAdmin()) {
    $projectsForReports = db()->query("SELECT id, title FROM projects WHERE deleted_at IS NULL ORDER BY title")->fetchAll();
} elseif (Permissions::isFaculty()) {
    $projectsForReports = AssignmentVisibility::getVisibleProjects('', '', '', 500, 0)['data'];
} else {
    $projectsForReports = [];
}
$canUploadReport = Permissions::isAdmin();
$canDownloadReport = Permissions::isAdmin();
$reportsByQuarter = [];
foreach ($reports as $report) {
    $reportsByQuarter[$report['period_quarter'] ?: 'Q1'][] = $report;
}
$selectedYear = $_GET['year'] ?? date('Y');
$selectedQuarter = $_GET['quarter'] ?? '';
$reportYears = range((int)date('Y'), 2021);
?>
<div class="sketch-board">
    <div class="sketch-head">
        <div><h1>Accomplishment</h1><p>Quarterly project files and previous-year reports</p></div>
        <button onclick="openReportTemplate()" class="btn-primary"><i class="fas fa-file-lines mr-1"></i> Generate Template</button>
    </div>
    <form method="GET" class="filter-bar m-4 flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="reports">
        <div class="flex-1 min-w-0"><input type="search" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Search reports..." class="filter-input"></div>
        <select name="year" class="filter-select" aria-label="Year">
            <option value="">All Years</option>
            <?php foreach($reportYears as $year): ?><option value="<?= $year ?>" <?= (string)$selectedYear === (string)$year ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?>
        </select>
        <select name="quarter" class="filter-select" aria-label="Quarter">
            <option value="">All Quarters</option>
            <?php foreach(['Q1','Q2','Q3','Q4'] as $quarter): ?><option value="<?= $quarter ?>" <?= $selectedQuarter === $quarter ? 'selected' : '' ?>><?= $quarter ?></option><?php endforeach; ?>
        </select>
        <select name="type" class="filter-select" aria-label="Report type">
            <option value="">All Types</option>
            <?php foreach(['quarterly' => 'Quarterly', 'semi_annual' => 'Semi-Annual', 'annual' => 'Annual', 'terminal' => 'Terminal'] as $value => $label): ?><option value="<?= $value ?>" <?= ($_GET['type'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
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
                    <?php if($latest && !empty($latest['attachment'])): ?><button onclick="previewReport(<?= (int)$latest['id'] ?>, <?= htmlspecialchars(json_encode($latest['title'] ?? 'Report'), ENT_QUOTES, 'UTF-8') ?>)" class="btn-ghost"><i class="fas fa-eye mr-1"></i> View</button><button onclick="printReport(<?= (int)$latest['id'] ?>)" class="btn-ghost"><i class="fas fa-print mr-1"></i> Print</button><?php endif; ?>
                    <?php if($canUploadReport): ?><button onclick="openReportUpload('<?= $quarter ?>')" class="btn-primary"><i class="fas fa-upload mr-1"></i> Upload</button><?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="data-table-container mt-6">
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Year</th><th>Quarter</th><th>Title</th><th>Project</th><th>File</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach($reports as $r): ?><tr>
            <td><?= e($r['period_year'] ?: '-') ?></td>
            <td><?= e($r['period_quarter'] ?: '-') ?></td>
            <td><span class="table-title"><?= e($r['title']) ?></span></td>
            <td><?= e($r['project_title'] ?? '-') ?></td>
            <td><?php if(!empty($r['attachment'])): ?><span class="file-extension-pill"><?= e(strtoupper(pathinfo($r['attachment'], PATHINFO_EXTENSION))) ?></span><?php if(!empty($r['attachments'])): ?><span class="text-xs text-[#9CA3AF] ml-1">+<?= count(attachmentPaths($r)) - 1 ?></span><?php endif; ?><?php else: ?><span class="text-[#6B7280]">-</span><?php endif; ?></td>
            <td><?= getStatusBadge($r['status']) ?></td>
            <td>
                <?php if(!empty($r['attachment'])): ?><button onclick="previewReport(<?= (int)$r['id'] ?>, <?= htmlspecialchars(json_encode($r['title'] ?? 'Report'), ENT_QUOTES, 'UTF-8') ?>)" class="action-btn" title="View" aria-label="View"><i class="fas fa-eye"></i></button><button onclick="printReport(<?= (int)$r['id'] ?>)" class="action-btn" title="Print" aria-label="Print"><i class="fas fa-print"></i></button><?php endif; ?>
            </td>
        </tr><?php endforeach; ?>
        <?php if(empty($reports)): ?><tr><td colspan="7" class="text-center text-[#6B7280] py-8">No accomplishment files yet</td></tr><?php endif; ?>
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
        ${fieldHtml({name:'period_year',label:'Year',type:'number',value:'<?= e($selectedYear ?: date('Y')) ?>'})}
        ${fieldHtml({name:'summary',label:'Summary / Notes',type:'textarea',rows:3,fullWidth:true,placeholder:'Optional. Admin can also review details from the uploaded accomplishment file.'})}
        <label class="form-label full-width">Scanned / Uploaded Report File *
            <input id="report-file" name="attachments[]" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx,.csv" class="form-input" multiple required>
            <p class="text-xs text-[#9CA3AF] mt-1">Select one or more scanned pages. Images will be kept in upload order.</p>
        </label>
        <div class="camera-scan-panel full-width"><div class="camera-scan-actions"><button type="button" id="report-scan-start" class="btn-ghost"><i class="fas fa-camera"></i> Scan with Camera</button><button type="button" id="report-scan-capture" class="btn-primary hidden"><i class="fas fa-camera-retro"></i> Capture Page</button><button type="button" id="report-scan-stop" class="btn-ghost hidden"><i class="fas fa-stop"></i> Stop Camera</button></div><div id="report-scan-panel" class="hidden"><video id="report-scan-video" autoplay playsinline muted></video><canvas id="report-scan-canvas" class="hidden"></canvas></div><p id="report-scan-status" class="camera-scan-status">Capture multiple pages; each page is added to the file list.</p></div>
    </div></div>`;
    sl.openForm(`Upload ${q}`, 'Accomplishment file', formHtml, {showSaveAnother:false});
    const reportForm = document.getElementById('slideoverForm');
    bindCameraScanner(reportForm, reportForm.querySelector('#report-file'), {prefix:'report', slideover:sl});
    reportForm.addEventListener('submit', async e => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/reports.php?action=create`, new FormData(e.target), () => location.reload());
    });
}

function openReportTemplate(){
    const sl = getSlideOver({size:'md'});
    const formHtml = `<div class="form-section"><div class="form-grid">
        ${fieldHtml({name:'project_id',label:'Project',type:'select',required:true,options:reportProjects,fullWidth:true})}
        ${fieldHtml({name:'period_quarter',label:'Quarter',type:'select',value:'<?= e($selectedQuarter ?: 'Q1') ?>',options:['Q1','Q2','Q3','Q4'].map(x=>({value:x,label:x}))})}
        ${fieldHtml({name:'period_year',label:'Year',type:'number',value:'<?= e($selectedYear ?: date('Y')) ?>'})}
    </div></div>`;
    sl.openForm('Generate Report Template', 'Build from project data', formHtml, {showSaveAnother:false});
    document.getElementById('slideoverForm').addEventListener('submit', async e => {
        e.preventDefault();
        const data = new FormData(e.target);
        const url = `${siteUrl}/ajax/reports.php?action=template&project_id=${encodeURIComponent(data.get('project_id'))}&quarter=${encodeURIComponent(data.get('period_quarter'))}&year=${encodeURIComponent(data.get('period_year'))}`;
        const result = await fetchWithLoading(url);
        if (!result.success) { showToast(result.message || 'Failed to generate template', 'error'); return; }
        sl.openView('Generated Report Template', `${data.get('period_quarter')} ${data.get('period_year')}`, `<div class="flex justify-end mb-3 no-print"><button onclick="window.print()" class="btn-primary"><i class="fas fa-print mr-1"></i> Print</button></div>${result.html}`, {size:'lg'});
    });
}

async function previewReport(id, title = 'Report'){
    const fileUrl = `${siteUrl}/ajax/reports.php?action=file&id=${Number(id)}`;
    const data = await fetchWithLoading(`${siteUrl}/ajax/reports.php?action=get&id=${Number(id)}`);
    const urls = Array.from({length: Number(data.attachment_count || 1)}, (_, index) => `${fileUrl}&attachment_index=${index}`);
    openDocumentGallery({
        urls,
        downloadUrl: `${fileUrl}&download=1`,
        title,
        subtitle: 'Accomplishment report file',
        fileName: title || 'report',
        allowDownload: <?= $canDownloadReport ? 'true' : 'false' ?>
    });
}
function printReport(id){
    const win = window.open(`${siteUrl}/ajax/reports.php?action=file&id=${Number(id)}`, '_blank', 'noopener');
    if (!win) { showToast('Allow pop-ups to print this file.', 'error'); return; }
    win.addEventListener('load', () => win.print(), { once: true });
}
</script>

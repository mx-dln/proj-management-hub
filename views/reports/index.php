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

$selectedYear = $_GET['year'] ?? '';
$selectedQuarter = $_GET['quarter'] ?? '';
$reportYears = range((int)date('Y'), 2021);

$reportsByQuarter = [];
$annualReports = [];
try {
    $cardWhere = " WHERE 1=1";
    $cardParams = [];
    if (!empty($selectedYear)) {
        $cardWhere .= " AND ar.period_year = ?";
        $cardParams[] = (int)$selectedYear;
    }
    if (!Permissions::isAdmin() && !Permissions::isViewer() && Permissions::isFaculty()) {
        $fid = Permissions::getFacultyId();
        if ($fid) {
            $cardWhere .= " AND ar.project_id IN (SELECT project_id FROM project_assignments WHERE faculty_id = ? AND is_active = 1)";
            $cardParams[] = $fid;
        }
    }
    $cardStmt = db()->prepare("
        SELECT ar.*, p.title as project_title
        FROM accomplishment_reports ar
        JOIN projects p ON ar.project_id = p.id
        {$cardWhere}
        ORDER BY ar.period_year DESC, ar.created_at DESC
    ");
    $cardStmt->execute($cardParams);
    $cardRows = $cardStmt->fetchAll();
    foreach ($cardRows as $report) {
        if ($report['report_type'] === 'annual' || empty($report['period_quarter'])) {
            $annualReports[] = $report;
        } else {
            $reportsByQuarter[$report['period_quarter']][] = $report;
        }
    }
} catch (Throwable $e) {
    foreach ($reports as $report) {
        if ($report['report_type'] === 'annual' || empty($report['period_quarter'])) {
            $annualReports[] = $report;
        } else {
            $reportsByQuarter[$report['period_quarter']][] = $report;
        }
    }
}
$latestAnnual = $annualReports[0] ?? null;
?>
<div class="sketch-board">
    <div class="sketch-head">
        <div>
            <h1>Accomplishment Reports</h1>
            <p>Annual & quarterly accomplishment files across 2021–<?= date('Y') ?></p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openReportTemplate()" class="btn-primary"><i class="fas fa-file-lines mr-1"></i> Generate Template</button>
            <?php if ($canUploadReport): ?>
                <button onclick="openReportUpload('auto')" class="btn-primary bg-[#10B981] hover:bg-[#059669]"><i class="fas fa-plus mr-1"></i> Upload Report</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Yearly Quick Navigation -->
    <div class="px-4 pt-3 pb-1 flex items-center gap-2 overflow-x-auto flex-wrap">
        <span class="text-xs text-[#9CA3AF] uppercase font-semibold mr-1 flex items-center gap-1"><i class="fas fa-calendar-alt text-emerald-400"></i> Report Year:</span>
        <a href="?module=reports" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= empty($selectedYear) ? 'bg-[#10B981] text-white shadow' : 'bg-[#1F2937] text-gray-300 hover:bg-[#374151]' ?>">All Years</a>
        <?php foreach ($reportYears as $yr): ?>
            <a href="?module=reports&year=<?= $yr ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= (string)$selectedYear === (string)$yr ? 'bg-[#10B981] text-white shadow' : 'bg-[#1F2937] text-gray-300 hover:bg-[#374151]' ?>"><?= $yr ?></a>
        <?php endforeach; ?>
    </div>

    <form method="GET" class="filter-bar m-4 flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="reports">
        <div class="flex-1 min-w-0"><input type="search" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Search reports by title or number..." class="filter-input"></div>
        <select name="year" class="filter-select" aria-label="Year">
            <option value="">All Years</option>
            <?php foreach($reportYears as $year): ?><option value="<?= $year ?>" <?= (string)$selectedYear === (string)$year ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?>
        </select>
        <select name="quarter" class="filter-select" aria-label="Quarter">
            <option value="">All Periods</option>
            <option value="annual" <?= $selectedQuarter === 'annual' ? 'selected' : '' ?>>Annual / Full Year</option>
            <?php foreach(['Q1','Q2','Q3','Q4'] as $quarter): ?><option value="<?= $quarter ?>" <?= $selectedQuarter === $quarter ? 'selected' : '' ?>><?= $quarter ?></option><?php endforeach; ?>
        </select>
        <select name="type" class="filter-select" aria-label="Report type">
            <option value="">All Types</option>
            <?php foreach(['quarterly' => 'Quarterly', 'annual' => 'Annual / Yearly', 'semi_annual' => 'Semi-Annual', 'terminal' => 'Terminal'] as $value => $label): ?><option value="<?= $value ?>" <?= ($_GET['type'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
        <?php if (!empty($_GET['search']) || !empty($_GET['year']) || !empty($_GET['quarter']) || !empty($_GET['type'])): ?>
            <a href="?module=reports" class="btn-ghost" title="Clear filters"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>

    <!-- Cards Grid: Annual + 4 Quarters -->
    <div class="quarter-grid">
        <!-- Annual / Full Year Card -->
        <section class="quarter-card quarter-card-annual">
            <div class="flex items-center justify-between">
                <h2>Annual</h2>
                <span class="badge bg-purple-900/60 text-purple-200 border border-purple-500/40 text-[11px] font-semibold">Full Year</span>
            </div>
            <?php if($latestAnnual): ?>
                <p><?= e($latestAnnual['title']) ?></p>
                <small><?= e($latestAnnual['project_title'] ?? '-') ?> · <?= getStatusBadge($latestAnnual['status']) ?></small>
            <?php else: ?>
                <p>No Annual file uploaded</p>
                <small>Ready for <?= $selectedYear ?: date('Y') ?> Annual report</small>
            <?php endif; ?>
            <div class="quarter-actions">
                <?php if($latestAnnual && !empty($latestAnnual['attachment'])): ?>
                    <button onclick="previewReport(<?= (int)$latestAnnual['id'] ?>, <?= htmlspecialchars(json_encode($latestAnnual['title'] ?? 'Annual Report'), ENT_QUOTES, 'UTF-8') ?>)" class="btn-ghost"><i class="fas fa-eye mr-1"></i> View</button>
                    <button onclick="printReport(<?= (int)$latestAnnual['id'] ?>)" class="btn-ghost"><i class="fas fa-print mr-1"></i> Print</button>
                <?php endif; ?>
                <?php if($canUploadReport): ?>
                    <button onclick="openReportUpload('Annual')" class="btn-primary bg-purple-700 hover:bg-purple-600"><i class="fas fa-upload mr-1"></i> Upload Annual</button>
                <?php endif; ?>
            </div>
        </section>

        <!-- Q1 to Q4 Cards -->
        <?php foreach(['Q1','Q2','Q3','Q4'] as $quarter): $latest = $reportsByQuarter[$quarter][0] ?? null; ?>
            <section class="quarter-card">
                <div class="flex items-center justify-between">
                    <h2><?= $quarter ?></h2>
                    <span class="text-xs text-[#9CA3AF]"><?= $quarter === 'Q1' ? 'Jan-Mar' : ($quarter === 'Q2' ? 'Apr-Jun' : ($quarter === 'Q3' ? 'Jul-Sep' : 'Oct-Dec')) ?></span>
                </div>
                <?php if($latest): ?>
                    <p><?= e($latest['title']) ?></p>
                    <small><?= e($latest['project_title'] ?? '-') ?> · <?= getStatusBadge($latest['status']) ?></small>
                <?php else: ?>
                    <p>No file uploaded</p>
                    <small>Ready for <?= $selectedYear ?: date('Y') ?> <?= $quarter ?></small>
                <?php endif; ?>
                <div class="quarter-actions">
                    <?php if($latest && !empty($latest['attachment'])): ?><button onclick="previewReport(<?= (int)$latest['id'] ?>, <?= htmlspecialchars(json_encode($latest['title'] ?? 'Report'), ENT_QUOTES, 'UTF-8') ?>)" class="btn-ghost"><i class="fas fa-eye mr-1"></i> View</button><button onclick="printReport(<?= (int)$latest['id'] ?>)" class="btn-ghost"><i class="fas fa-print mr-1"></i> Print</button><?php endif; ?>
                    <?php if($canUploadReport): ?><button onclick="openReportUpload('<?= $quarter ?>')" class="btn-primary"><i class="fas fa-upload mr-1"></i> Upload</button><?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="data-table-container mt-6">
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Year</th><th>Period / Type</th><th>Title</th><th>Project</th><th>File</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach($reports as $r): ?><tr>
            <td><span class="table-code font-bold"><?= e($r['period_year'] ?: '-') ?></span></td>
            <td>
                <?php if($r['report_type'] === 'annual' || empty($r['period_quarter'])): ?>
                    <span class="badge bg-purple-900/60 text-purple-200 border border-purple-500/40 text-xs font-semibold">Annual</span>
                <?php else: ?>
                    <span class="badge badge-submitted text-xs font-semibold"><?= e($r['period_quarter']) ?></span>
                <?php endif; ?>
                <?php if($r['report_type'] && $r['report_type'] !== 'quarterly' && $r['report_type'] !== 'annual'): ?>
                    <span class="text-[11px] text-[#9CA3AF] block mt-0.5"><?= ucfirst($r['report_type']) ?></span>
                <?php endif; ?>
            </td>
            <td><span class="table-title"><?= e($r['title']) ?></span></td>
            <td><?= e($r['project_title'] ?? '-') ?></td>
            <td><?php if(!empty($r['attachment'])): ?><span class="file-extension-pill"><?= e(strtoupper(pathinfo($r['attachment'], PATHINFO_EXTENSION))) ?></span><?php if(!empty($r['attachments'])): ?><span class="text-xs text-[#9CA3AF] ml-1">+<?= count(attachmentPaths($r)) - 1 ?></span><?php endif; ?><?php else: ?><span class="text-[#6B7280]">-</span><?php endif; ?></td>
            <td><?= getStatusBadge($r['status']) ?></td>
            <td>
                <?php if(!empty($r['attachment'])): ?><button onclick="previewReport(<?= (int)$r['id'] ?>, <?= htmlspecialchars(json_encode($r['title'] ?? 'Report'), ENT_QUOTES, 'UTF-8') ?>)" class="action-btn" title="View" aria-label="View"><i class="fas fa-eye"></i></button><button onclick="printReport(<?= (int)$r['id'] ?>)" class="action-btn" title="Print" aria-label="Print"><i class="fas fa-print"></i></button><?php endif; ?>
                <?php if (Permissions::canDeleteReport()): ?>
                    <button onclick="deleteReport(<?= (int)$r['id'] ?>, this)" class="action-btn action-btn-danger" title="Delete" aria-label="Delete"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
            </td>
        </tr><?php endforeach; ?>
        <?php if(empty($reports)): ?><tr><td colspan="7" class="text-center text-[#6B7280] py-8">No accomplishment files found for this filter</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>

<style>
.quarter-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;padding:18px}
.quarter-card{background:#111827;border:1px solid #374151;border-radius:8px;padding:18px;min-height:150px;display:flex;flex-direction:column;justify-content:space-between}
.quarter-card-annual{border-color:#7C3AED;background:linear-gradient(180deg, #1e1338 0%, #111827 100%)}
.quarter-card h2{font-size:24px;font-weight:800;color:#86EFAC}
.quarter-card-annual h2{color:#C084FC}
.quarter-card p{color:#F9FAFB;font-weight:700;margin-top:12px;font-size:14px}.quarter-card small{color:#9CA3AF}.quarter-actions{display:flex;gap:8px;margin-top:16px;flex-wrap:wrap}
@media(max-width:1200px){.quarter-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.quarter-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:500px){.quarter-grid{grid-template-columns:1fr}}
</style>
<script>
const siteUrl = <?= json_encode(SITE_URL) ?>;
const reportProjects = <?= json_encode(array_map(fn($p) => ['value'=>$p['id'],'label'=>$p['title']], $projectsForReports), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const selectedYearParam = <?= json_encode($selectedYear) ?>;

function getAutoQuarter() {
    const month = new Date().getMonth() + 1;
    return 'Q' + Math.ceil(month / 3);
}

function openReportUpload(q){
    const sl = getSlideOver({size:'md'});
    const isAuto = q === 'auto';
    const effectiveQ = isAuto ? getAutoQuarter() : q;
    const isAnnual = effectiveQ === 'Annual' || effectiveQ === 'annual';
    const yr = selectedYearParam || new Date().getFullYear();
    const defaultTitle = isAnnual ? `${yr} Annual Accomplishment Report` : `${effectiveQ} ${yr} Accomplishment Report`;

    const formHtml = `<div class="form-section"><div class="form-grid">
        ${fieldHtml({name:'project_id',label:'Project',type:'select',required:true,options:reportProjects,fullWidth:true})}
        ${fieldHtml({name:'title',label:'Title',required:true,value:defaultTitle,fullWidth:true})}
        ${fieldHtml({name:'report_type',label:'Report Type',type:'select',value: isAnnual ? 'annual' : 'quarterly', options:[
            {value:'quarterly',label:'Quarterly'},
            {value:'annual',label:'Annual / Yearly'},
            {value:'semi_annual',label:'Semi-Annual'},
            {value:'terminal',label:'Terminal'}
        ]})}
        ${fieldHtml({name:'period_quarter',label:'Quarter (Blank if Annual)',type:'select',value: isAnnual ? '' : effectiveQ, options:[
            {value:'',label:'None / Annual (Full Year)'},
            {value:'Q1',label:'Q1 (Jan - Mar)'},
            {value:'Q2',label:'Q2 (Apr - Jun)'},
            {value:'Q3',label:'Q3 (Jul - Sep)'},
            {value:'Q4',label:'Q4 (Oct - Dec)'}
        ]})}
        ${fieldHtml({name:'period_year',label:'Year (2021–' + (new Date().getFullYear() + 1) + ')',type:'number',value: yr, required:true})}
        ${fieldHtml({name:'summary',label:'Summary / Highlights',type:'textarea',rows:3,fullWidth:true,placeholder:'Optional summary or key accomplishments from the file.'})}
        <label class="form-label full-width">Scanned / Uploaded Accomplishment File *
            <input id="report-file" name="attachments[]" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx,.csv" class="form-input" multiple required>
            <p class="text-xs text-[#9CA3AF] mt-1">Select one or more scanned pages. PDF, Word, Excel, and images are supported.</p>
        </label>
        <div class="camera-scan-panel full-width"><div class="camera-scan-actions"><button type="button" id="report-scan-start" class="btn-ghost"><i class="fas fa-camera"></i> Scan with Camera</button><button type="button" id="report-scan-capture" class="btn-primary hidden"><i class="fas fa-camera-retro"></i> Capture Page</button><button type="button" id="report-scan-stop" class="btn-ghost hidden"><i class="fas fa-stop"></i> Stop Camera</button></div><div id="report-scan-panel" class="hidden"><video id="report-scan-video" autoplay playsinline muted></video><canvas id="report-scan-canvas" class="hidden"></canvas></div><p id="report-scan-status" class="camera-scan-status">Capture multiple pages; each page is added to the file list.</p></div>
    </div></div>`;

    sl.openForm(`Upload ${isAnnual ? 'Annual Report' : (effectiveQ + ' Report')}`, 'Accomplishment file', formHtml, {showSaveAnother:false, submitText:'Upload Report'});
    const reportForm = document.getElementById('slideoverForm');
    
    // Auto sync title when type or quarter changes
    const typeSelect = reportForm.querySelector('[name="report_type"]');
    const quarterSelect = reportForm.querySelector('[name="period_quarter"]');
    const yearInput = reportForm.querySelector('[name="period_year"]');
    const titleInput = reportForm.querySelector('[name="title"]');
    const updateTitle = () => {
        const y = yearInput.value || new Date().getFullYear();
        if (typeSelect.value === 'annual' || !quarterSelect.value) {
            titleInput.value = `${y} Annual Accomplishment Report`;
        } else {
            titleInput.value = `${quarterSelect.value} ${y} Accomplishment Report`;
        }
    };
    typeSelect.addEventListener('change', () => {
        if (typeSelect.value === 'annual') quarterSelect.value = '';
        else if (!quarterSelect.value) quarterSelect.value = getAutoQuarter();
        updateTitle();
    });
    quarterSelect.addEventListener('change', () => {
        if (!quarterSelect.value) typeSelect.value = 'annual';
        else typeSelect.value = 'quarterly';
        updateTitle();
    });
    yearInput.addEventListener('input', updateTitle);

    bindCameraScanner(reportForm, reportForm.querySelector('#report-file'), {prefix:'report', slideover:sl});
    reportForm.addEventListener('submit', async e => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/reports.php?action=create`, new FormData(e.target), () => location.reload());
    });
}

function openReportTemplate(){
    const sl = getSlideOver({size:'md'});
    const yr = selectedYearParam || new Date().getFullYear();
    const autoQ = getAutoQuarter();

    const formHtml = `<div class="form-section">
        <p class="text-xs text-[#9CA3AF] mb-3">System automatically compiles recorded activities, participants, partner agency, and MOA status into a clean printable report template.</p>
        <div class="form-grid">
            ${fieldHtml({name:'project_id',label:'Project',type:'select',required:true,options:reportProjects,fullWidth:true})}
            ${fieldHtml({name:'period_quarter',label:'Period (Auto-detected)',type:'select',value: autoQ, options:[
                {value: autoQ, label: `Current Quarter (${autoQ}) - Auto`},
                {value:'annual', label:'Full Year / Annual (All 4 Quarters)'},
                {value:'Q1',label:'Q1 (Jan - Mar)'},
                {value:'Q2',label:'Q2 (Apr - Jun)'},
                {value:'Q3',label:'Q3 (Jul - Sep)'},
                {value:'Q4',label:'Q4 (Oct - Dec)'}
            ]})}
            ${fieldHtml({name:'period_year',label:'Year',type:'number',value: yr, required:true})}
        </div>
    </div>`;

    sl.openForm('Generate Report Template', 'Build from system activity data', formHtml, {showSaveAnother:false, submitText:'Generate Template'});
    document.getElementById('slideoverForm').addEventListener('submit', async e => {
        e.preventDefault();
        sl.setLoading(true);
        try {
            const data = new FormData(e.target);
            const quarterVal = data.get('period_quarter');
            const yearVal = data.get('period_year');
            const url = `${siteUrl}/ajax/reports.php?action=template&project_id=${encodeURIComponent(data.get('project_id'))}&quarter=${encodeURIComponent(quarterVal)}&year=${encodeURIComponent(yearVal)}`;
            const result = await fetchWithLoading(url);
            if (!result.success) {
                sl.setLoading(false);
                showToast(result.message || 'Failed to generate template', 'error');
                return;
            }
            const label = quarterVal === 'annual' ? `Annual ${yearVal}` : `${quarterVal} ${yearVal}`;
            sl.openView('Generated Report Template', label, `<div class="flex justify-end mb-3 no-print"><button onclick="window.print()" class="btn-primary"><i class="fas fa-print mr-1"></i> Print</button></div>${result.html}`, {size:'lg'});
        } catch (err) {
            console.error('Template generation error:', err);
            sl.setLoading(false);
            showToast(err.message || 'An error occurred while generating template', 'error');
        }
    });
}

async function previewReport(id, title = 'Report'){
    try {
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
    } catch (err) {
        console.error('Preview report error:', err);
        showToast(err.message || 'Failed to preview report', 'error');
    }
}
function printReport(id){
    const win = window.open(`${siteUrl}/ajax/reports.php?action=file&id=${Number(id)}`, '_blank', 'noopener');
    if (!win) { showToast('Allow pop-ups to print this file.', 'error'); return; }
    win.addEventListener('load', () => win.print(), { once: true });
}

window.deleteReport = async function(id, btn) {
    if (!confirm('Are you sure you want to delete this accomplishment report?')) return;
    setButtonLoading(btn, true);
    try {
        const response = await fetch(`${siteUrl}/ajax/reports.php?action=delete`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id})
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Delete failed.');
        showToast(data.message || 'Report deleted.');
        setTimeout(() => location.reload(), 700);
    } catch (error) {
        showToast(error.message || 'Could not delete report.', 'error');
    } finally {
        setButtonLoading(btn, false);
    }
};
</script>

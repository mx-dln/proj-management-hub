<?php
$projectOptions = Permissions::canManageMoa() ? db()->query("SELECT id, title FROM projects WHERE deleted_at IS NULL ORDER BY title")->fetchAll() : [];
$partnerOptions = Permissions::canManageMoa() ? db()->query("SELECT id, name FROM partner_agencies WHERE is_active = 1 ORDER BY name")->fetchAll() : [];
$canManageMoa = Permissions::canManageMoa();
?>
<div class="sketch-board">
    <div class="sketch-head">
        <div><h1>MOA</h1><p>Memorandum of Agreement records</p></div>
        <?php if ($canManageMoa): ?><button onclick="focusMoaForm()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New MOA</button><?php endif; ?>
    </div>
    <div class="moa-sketch">
        <section class="moa-list">
            <form method="GET" class="moa-search">
                <input type="hidden" name="module" value="moa">
                <input type="search" name="search" value="<?= e($search ?? '') ?>" placeholder="Search MOA, project, partner, status..." aria-label="Search MOA records">
                <button type="submit" class="btn-primary" aria-label="Search"><i class="fas fa-search"></i></button>
                <?php if (!empty($search)): ?><a href="<?= SITE_URL ?>/index.php?module=moa" class="btn-ghost" aria-label="Clear search"><i class="fas fa-times"></i></a><?php endif; ?>
            </form>
            <div class="moa-table-wrap">
                <table class="moa-table">
                    <thead><tr><th>MOA</th><th>Project</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach($moas as $m): ?>
                        <tr>
                            <td>
                                <span class="moa-code"><?= e($m['moa_number']) ?></span>
                                <small><?= e($m['partner_name'] ?? '-') ?></small>
                            </td>
                            <td><?= e($m['project_title'] ?? '-') ?></td>
                            <td><?= getStatusBadge($m['status']) ?></td>
                            <td>
                                <div class="moa-row-actions">
                                    <button onclick="viewMoa(<?= (int)$m['id'] ?>, this)" class="action-btn" title="View" aria-label="View"><i class="fas fa-eye"></i></button>
                                    <button onclick="printMoa(<?= (int)$m['id'] ?>, <?= !empty($m['attachment']) ? 'true' : 'false' ?>)" class="action-btn" title="Print" aria-label="Print"><i class="fas fa-print"></i></button>
                                    <?php if ($canManageMoa): ?><button onclick="editMoa(<?= (int)$m['id'] ?>, this)" class="action-btn" title="Edit" aria-label="Edit"><i class="fas fa-pen"></i></button><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($moas)): ?><tr><td colspan="4" class="moa-empty">No MOA records found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="moa-pagination" aria-label="MOA pages">
                    <span>Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
                    <div>
                        <?php if ($page > 1): ?><a class="btn-ghost" href="?<?= e(http_build_query(['module' => 'moa', 'search' => $search ?? '', 'page' => $page - 1])) ?>" aria-label="Previous page"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                        <?php if ($page < $totalPages): ?><a class="btn-ghost" href="?<?= e(http_build_query(['module' => 'moa', 'search' => $search ?? '', 'page' => $page + 1])) ?>" aria-label="Next page"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        </section>
        <section class="moa-form-panel">
            <?php if ($canManageMoa): ?>
            <button onclick="focusMoaForm()" class="btn-primary mb-4"><i class="fas fa-plus mr-1"></i> New MOA</button>
            <form id="moaInlineForm" class="sketch-form">
                <label>MOA Information:<input name="moa_number" required placeholder="MOA-2026-0001"></label>
                <label>Project Title:
                    <select name="project_id" required><option value="">Select project</option><?php foreach($projectOptions as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select>
                </label>
                <label>Partner Agency:
                    <select name="partner_agency_id"><option value="">Select partner</option><?php foreach($partnerOptions as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select>
                </label>
                <label>Date Signed:<input type="date" name="date_signed"></label>
                <label>End Date:<input type="date" name="expiration_date"></label>
                <label class="full">Remarks:<textarea name="remarks" rows="3"></textarea></label>
                <label class="full">MOA File:<input type="file" name="attachment" accept="application/pdf"></label>
                <div class="sketch-actions"><button type="submit" class="btn-primary">Save</button></div>
            </form>
            <?php else: ?><div class="sketch-empty">View access only</div><?php endif; ?>
        </section>
    </div>
</div>
<style>
.moa-sketch{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:0}.moa-list{padding:18px;border-right:1px solid #374151}.moa-form-panel{padding:18px}
.moa-search{display:flex;gap:8px;margin-bottom:14px}.moa-search input{min-width:0;flex:1;background:#111827;border:1px solid #374151;border-radius:8px;color:#F9FAFB;padding:10px 12px;font-size:14px}
.moa-table-wrap{overflow-x:auto;border:1px solid #374151;border-radius:8px;background:#111827}.moa-table{width:100%;border-collapse:collapse}.moa-table th{background:#0F643A;color:#fff;font-size:11px;text-transform:uppercase;letter-spacing:.06em;text-align:left;padding:12px}.moa-table td{border-top:1px solid #2D3748;color:#F9FAFB;padding:12px;vertical-align:middle;font-size:13px}.moa-table tbody tr:hover{background:#172f25}.moa-code{display:block;font-weight:800;color:#86EFAC}.moa-table small{display:block;color:#9CA3AF;margin-top:3px}.moa-row-actions{display:flex;gap:4px;justify-content:flex-end}.moa-empty{text-align:center!important;color:#6B7280!important;padding:28px!important}.moa-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;color:#9CA3AF;font-size:13px}.moa-pagination div{display:flex;gap:8px}
@media(max-width:900px){.moa-sketch{grid-template-columns:1fr}.moa-list{border-right:0;border-bottom:1px solid #374151}}
</style>
<script>
const siteUrl = <?= json_encode(SITE_URL) ?>;
const canManageMoa = <?= $canManageMoa ? 'true' : 'false' ?>;
function focusMoaForm(){ document.querySelector('#moaInlineForm input[name="moa_number"]')?.focus(); }
document.getElementById('moaInlineForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const submitButton = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitButton, true);
    try {
        const response = await fetch(`${siteUrl}/ajax/moa.php?action=create`, { method: 'POST', body: new FormData(e.target) });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Save failed.');
        showToast(data.message || 'MOA saved.');
        setTimeout(() => location.reload(), 700);
    } catch (error) {
        showToast(error.message || 'Could not save MOA.', 'error');
    } finally {
        setButtonLoading(submitButton, false);
    }
});
async function viewMoa(id, btn){
    const data = await fetchWithLoading(`${siteUrl}/ajax/moa.php?action=get&id=${id}`, btn);
    const downloadAction = canManageMoa ? `<a href="${siteUrl}/ajax/moa.php?action=file&id=${Number(data.id)}&download=1" class="btn-primary"><i class="fas fa-download" aria-hidden="true"></i> Download</a>` : '';
    const fileActions = `<div class="flex flex-wrap gap-2 mb-4">${data.attachment ? `<a href="${siteUrl}/ajax/moa.php?action=file&id=${Number(data.id)}" class="btn-ghost" target="_blank" rel="noopener"><i class="fas fa-eye" aria-hidden="true"></i> Open</a>` : ''}<button type="button" onclick="printMoa(${Number(data.id)}, ${data.attachment ? 'true' : 'false'})" class="btn-ghost"><i class="fas fa-print" aria-hidden="true"></i> Print</button>${downloadAction}</div>`;
    getSlideOver({size:'md'}).openView(data.moa_number, data.project_title || '', fileActions + viewSectionHtml('MOA Information', [
        viewFieldHtml('MOA #', escapeHtml(data.moa_number || '-')),
        viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
        viewFieldHtml('Partner Agency', escapeHtml(data.partner_name || '-')),
        viewFieldHtml('Date Signed', formatDate(data.date_signed)),
        viewFieldHtml('End Date', formatDate(data.expiration_date)),
        viewFieldHtml('Remarks', escapeHtml(data.remarks || '-')),
    ]), {size:'md'});
}
function editMoa(id, btn){ viewMoa(id, btn); }
async function printMoa(id, hasAttachment = false){
    if (!hasAttachment) {
        await printMoaDetails(id);
        return;
    }
    const win = window.open(`${siteUrl}/ajax/moa.php?action=file&id=${Number(id)}`, '_blank', 'noopener');
    if (!win) { showToast('Allow pop-ups to print this file.', 'error'); return; }
    win.addEventListener('load', () => win.print(), { once: true });
}
async function printMoaDetails(id){
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/moa.php?action=get&id=${Number(id)}`);
        const win = window.open('', '_blank', 'noopener');
        if (!win) { showToast('Allow pop-ups to print this MOA.', 'error'); return; }
        win.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>MOA ${escapeHtml(data.moa_number || '')}</title><style>
            body{font-family:Arial,sans-serif;color:#111827;margin:36px;line-height:1.5}
            h1{font-size:24px;margin:0 0 4px}p{margin:0 0 20px;color:#4B5563}
            dl{display:grid;grid-template-columns:150px 1fr;gap:10px 18px;max-width:760px}
            dt{font-weight:700;color:#374151}dd{margin:0}
            @media print{body{margin:24mm}}
        </style></head><body>
            <h1>Memorandum of Agreement</h1>
            <p>${escapeHtml(data.moa_number || '')}</p>
            <dl>
                <dt>Project</dt><dd>${escapeHtml(data.project_title || '-')}</dd>
                <dt>Partner Agency</dt><dd>${escapeHtml(data.partner_name || '-')}</dd>
                <dt>Date Signed</dt><dd>${formatDate(data.date_signed)}</dd>
                <dt>End Date</dt><dd>${formatDate(data.expiration_date)}</dd>
                <dt>Status</dt><dd>${escapeHtml(data.status || '-')}</dd>
                <dt>Remarks</dt><dd>${escapeHtml(data.remarks || '-')}</dd>
            </dl>
        </body></html>`);
        win.document.close();
        win.focus();
        win.print();
    } catch (error) {
        showToast(error.message || 'Failed to print MOA.', 'error');
    }
}
</script>

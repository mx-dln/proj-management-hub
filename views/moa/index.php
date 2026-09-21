<?php
$projectOptions = db()->query("SELECT id, title FROM projects WHERE deleted_at IS NULL ORDER BY title")->fetchAll();
$partnerOptions = db()->query("SELECT id, name FROM partner_agencies WHERE is_active = 1 ORDER BY name")->fetchAll();
?>
<div class="sketch-board">
    <div class="sketch-head">
        <div><h1>MOA</h1><p>Memorandum of Agreement records</p></div>
        <?php if (Permissions::canManageMoa()): ?><button onclick="focusMoaForm()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New MOA</button><?php endif; ?>
    </div>
    <div class="moa-sketch">
        <section class="moa-list">
            <?php if(empty($moas)): ?><div class="sketch-empty">No MOA records yet</div><?php endif; ?>
            <?php foreach($moas as $m): ?>
                <article class="moa-record">
                    <dl>
                        <dt>MOA #:</dt><dd><?= e($m['moa_number']) ?></dd>
                        <dt>Project:</dt><dd><?= e($m['project_title'] ?? '-') ?></dd>
                        <dt>Partner:</dt><dd><?= e($m['partner_name'] ?? '-') ?></dd>
                        <dt>Signed Date:</dt><dd><?= formatDate($m['date_signed']) ?></dd>
                        <dt>End Date:</dt><dd><?= formatDate($m['expiration_date']) ?></dd>
                        <dt>Status:</dt><dd><?= getStatusBadge($m['status']) ?></dd>
                    </dl>
                    <div class="quarter-actions">
                        <button onclick="viewMoa(<?= (int)$m['id'] ?>, this)" class="btn-ghost">View</button>
                        <?php if (Permissions::canManageMoa()): ?><button onclick="editMoa(<?= (int)$m['id'] ?>, this)" class="btn-ghost">Edit</button><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <section class="moa-form-panel">
            <?php if (Permissions::canManageMoa()): ?>
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
.moa-sketch{display:grid;grid-template-columns:1fr 1fr;gap:0}.moa-list{padding:18px;border-right:1px solid #374151}.moa-form-panel{padding:18px}
.moa-record{border:1px solid #374151;background:#111827;border-radius:8px;padding:14px;margin-bottom:12px}.moa-record dl{display:grid;grid-template-columns:120px 1fr;gap:8px}.moa-record dt{color:#9CA3AF}.moa-record dd{color:#F9FAFB}
@media(max-width:900px){.moa-sketch{grid-template-columns:1fr}.moa-list{border-right:0;border-bottom:1px solid #374151}}
</style>
<script>
const siteUrl = window.location.origin;
function focusMoaForm(){ document.querySelector('#moaInlineForm input[name="moa_number"]')?.focus(); }
document.getElementById('moaInlineForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    await submitSlideOverForm({setLoading:()=>{},close:()=>{}}, `${siteUrl}/ajax/moa.php?action=create`, new FormData(e.target), () => location.reload());
});
async function viewMoa(id, btn){
    const data = await fetchWithLoading(`${siteUrl}/ajax/moa.php?action=get&id=${id}`, btn);
    getSlideOver({size:'md'}).openView(data.moa_number, data.project_title || '', viewSectionHtml('MOA Information', [
        viewFieldHtml('MOA #', escapeHtml(data.moa_number || '-')),
        viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
        viewFieldHtml('Partner Agency', escapeHtml(data.partner_name || '-')),
        viewFieldHtml('Date Signed', formatDate(data.date_signed)),
        viewFieldHtml('End Date', formatDate(data.expiration_date)),
        viewFieldHtml('Remarks', escapeHtml(data.remarks || '-')),
    ]), {size:'md'});
}
function editMoa(id, btn){ viewMoa(id, btn); }
</script>

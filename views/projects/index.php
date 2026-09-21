<div class="sketch-board">
    <div class="sketch-head">
        <div>
            <h1>Project Management</h1>
            <p>(MGA PROJECTS)</p>
        </div>
        <?php if (Permissions::canCreateProject()): ?>
            <button onclick="openCreateProject()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New Program</button>
        <?php endif; ?>
    </div>

    <div class="sketch-project-layout">
        <aside class="sketch-list">
            <form method="GET" class="sketch-search">
                <input type="hidden" name="module" value="projects">
                <input type="search" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Search projects">
            </form>
            <?php if(empty($result['data'])): ?>
                <div class="sketch-empty">No projects yet</div>
            <?php else: ?>
                <?php foreach($result['data'] as $p): ?>
                    <button type="button" class="sketch-project-row" onclick="viewProject(<?= (int)$p['id'] ?>, this)">
                        <i class="fas fa-caret-right"></i>
                        <span><?= e($p['title']) ?></span>
                        <small><?= e($p['project_code']) ?></small>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </aside>

        <section class="sketch-form-panel">
            <div class="sketch-form-title">
                <button onclick="openCreateProject()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New Program</button>
            </div>
            <form id="inlineProjectForm" class="sketch-form">
                <label>Project Title:<input name="title" required></label>
                <label>Project Leader:<input name="project_leader" placeholder="Assign after saving"></label>
                <label class="full">Description:<textarea name="description" rows="2"></textarea></label>
                <div class="two">
                    <label>College:<input name="college"></label>
                    <label>Campus:<input name="location"></label>
                </div>
                <div class="two">
                    <label>Start Date:<input name="start_date" type="date"></label>
                    <label>End Date:<input name="end_date" type="date"></label>
                </div>
                <label class="full">Objectives:<textarea name="objectives" rows="2"></textarea></label>
                <label class="full">Expected Output:<textarea name="expected_outputs" rows="2"></textarea></label>
                <label class="full">Parent Program:
                    <select name="program_id" required>
                        <option value="">Select program</option>
                        <?php foreach($programs as $program): ?><option value="<?= (int)$program['id'] ?>"><?= e($program['title']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <div class="sketch-actions">
                    <button type="reset" class="btn-ghost">Cancel</button>
                    <button type="submit" name="again" value="1" class="btn-ghost">Save & Create Another</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </section>
    </div>
</div>

<style>
.sketch-board{background:#172331;border:1px solid #374151;border-radius:10px;overflow:hidden;color:#F9FAFB}
.sketch-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 18px;border-bottom:1px solid #374151;background:#111827}
.sketch-head h1{font-size:22px;font-weight:800}.sketch-head p{font-size:12px;color:#9CA3AF;margin-top:3px}
.sketch-project-layout{display:grid;grid-template-columns:300px 1fr;min-height:560px}
.sketch-list{border-right:1px solid #374151;padding:14px;background:#16202c}
.sketch-search input,.sketch-form input,.sketch-form textarea,.sketch-form select{width:100%;background:#0F172A;border:1px solid #374151;border-radius:7px;color:#F9FAFB;padding:9px 10px}
.sketch-project-row{width:100%;display:grid;grid-template-columns:16px 1fr;gap:8px;text-align:left;align-items:center;padding:10px 8px;border-radius:7px;color:#D1D5DB;margin-top:8px}
.sketch-project-row:hover{background:#203348;color:#fff}.sketch-project-row small{grid-column:2;color:#6B7280;font-size:11px}
.sketch-empty{color:#6B7280;text-align:center;padding:32px 10px}.sketch-form-panel{padding:20px 24px}
.sketch-form-title{margin-bottom:18px}.sketch-form{display:grid;gap:13px;max-width:850px}
.sketch-form label{display:grid;gap:6px;color:#D1D5DB;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.02em}
.sketch-form .two{display:grid;grid-template-columns:1fr 1fr;gap:14px}.sketch-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:10px}
@media(max-width:900px){.sketch-project-layout{grid-template-columns:1fr}.sketch-list{border-right:0;border-bottom:1px solid #374151}.sketch-form .two{grid-template-columns:1fr}}
</style>

<script>
const siteUrl = '<?= SITE_URL ?>';
const programsList = <?= json_encode($programs) ?>;
document.getElementById('inlineProjectForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const keepOpen = e.submitter?.name === 'again';
    try {
        const data = await fetch(`${siteUrl}/ajax/projects.php?action=create`, { method:'POST', body:new FormData(e.target) }).then(r => r.json());
        if (!data.success) throw new Error(data.message || 'Failed to save');
        showToast(data.message || 'Saved');
        if (keepOpen) e.target.reset(); else setTimeout(() => location.reload(), 700);
    } catch (err) { showToast(err.message, 'error'); }
});
function openCreateProject(){ document.querySelector('#inlineProjectForm input[name="title"]')?.focus(); }
async function viewProject(id, btn){
    const data = await fetchWithLoading(`${siteUrl}/ajax/projects.php?action=get&id=${id}`, btn);
    getSlideOver({size:'md'}).openView(data.title, data.project_code, viewSectionHtml('Project Information', [
        viewFieldHtml('Project Title', escapeHtml(data.title || '-')),
        viewFieldHtml('Project Leader', 'Assign members'),
        viewFieldHtml('Description', escapeHtml(data.description || '-')),
        viewFieldHtml('College', '-'),
        viewFieldHtml('Campus', escapeHtml(data.location || '-')),
        viewFieldHtml('Start Date', formatDate(data.start_date)),
        viewFieldHtml('End Date', formatDate(data.end_date)),
        viewFieldHtml('Objectives', escapeHtml(data.objectives || '-')),
        viewFieldHtml('Expected Output', escapeHtml(data.expected_outputs || '-')),
    ]), {size:'md'});
}
</script>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Projects</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage extension projects</p></div>
    <?php if (Permissions::canCreateProject()): ?>
        <button onclick="openCreateProject()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New Project</button>
    <?php endif; ?>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="projects">
        <div class="flex-1"><input type="text" name="search" placeholder="Search projects..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <select name="status" class="filter-select"><option value="">All Status</option><?php foreach(['draft','planned','ongoing','completed','cancelled'] as $s): ?><option value="<?= $s ?>" <?= ($_GET['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div id="projectsTable">
    <div class="data-table-container">
        <?php if(empty($result['data'])): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-folder-open"></i></div><h3 class="empty-state-title">No projects found</h3></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr>
                <th>Code</th><th>Project</th><th>Program</th><th>Budget</th><th>Status</th><th>Progress</th><th>Actions</th>
            </tr></thead><tbody>
                <?php foreach($result['data'] as $i=>$p): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i*0.05 ?>s">
                        <td data-label="Code"><span class="table-code"><?= e($p['project_code']) ?></span></td>
                        <td data-label="Project"><span class="table-title"><?= e($p['title']) ?></span></td>
                        <td data-label="Program"><span class="text-[#D1D5DB]"><?= e($p['program_title'] ?? '-') ?></span></td>
                        <td data-label="Budget"><span class="table-amount"><?= formatCurrency($p['budget']) ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($p['status']) ?></td>
                        <td data-label="Progress"><div class="flex items-center gap-2"><div class="progress-bar w-16"><div class="progress-fill" style="width:<?= $p['completion_percentage'] ?>%"></div></div><span class="text-xs text-[#9CA3AF]"><?= $p['completion_percentage'] ?>%</span></div></td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewProject(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                                <?php if(Permissions::canEditProject($p['id'])): ?>
                                    <button onclick="editProject(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="Edit"><i class="fas fa-edit text-sm"></i></button>
                                <?php endif; ?>
                                <?php if(Permissions::canAssignMembers()): ?>
                                    <button onclick="openAssignments('project', <?= $p['id'] ?>, '<?= e(addslashes($p['title'])) ?>')" class="action-btn" title="Assign"><i class="fas fa-users text-sm"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';
const programsList = <?= json_encode($programs) ?>;

function refreshProjectsTable() {
    const params = new URLSearchParams(window.location.search);
    fetch(`${siteUrl}/index.php?module=projects&${params.toString()}`)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.getElementById('projectsTable');
            if (newTable) document.getElementById('projectsTable').innerHTML = newTable.innerHTML;
        });
}

function openCreateProject() {
    const sl = getSlideOver({ size: 'lg', title: 'New Project', subtitle: 'Create extension project' });
    const programOptions = programsList.map(p => ({ value: p.id, label: p.title }));
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Project Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'title', label: 'Project Title', required: true, placeholder: 'Enter project title', fullWidth: true })}
                ${fieldHtml({ name: 'program_id', label: 'Parent Program', type: 'select', options: programOptions, required: true, fullWidth: true })}
                ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe the project', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'location', label: 'Location', placeholder: 'Project location' })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Timeline & Budget</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date' })}
                ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date' })}
                ${fieldHtml({ name: 'budget', label: 'Budget', type: 'number', placeholder: '0.00' })}
                ${fieldHtml({ name: 'funding_source_id', label: 'Funding Source', type: 'select', options: <?= json_encode(array_map(fn($f) => ['value'=>$f['id'],'label'=>$f['name']], $fundingSources ?? [])) ?> })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Details</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'partner_agency_id', label: 'Partner Agency', type: 'select', options: <?= json_encode(array_map(fn($p) => ['value'=>$p['id'],'label'=>$p['name']], $partnerAgencies ?? [])) ?> })}
                ${fieldHtml({ name: 'beneficiary_group_id', label: 'Beneficiary', type: 'select', options: <?= json_encode(array_map(fn($b) => ['value'=>$b['id'],'label'=>$b['name']], $beneficiaryGroups ?? [])) ?> })}
                ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', fullWidth: true, rows: 3 })}
            </div>
        </div>`;
    sl.openForm('New Project', 'Create extension project', formHtml, { showSaveAnother: true, size: 'lg' });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/projects.php?action=create`, new FormData(e.target), () => refreshProjectsTable());
    });
}

async function viewProject(id, btn) {
    const sl = getSlideOver({ size: 'lg', title: 'Project Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/projects.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = data.project_code;
        const content = `
            ${viewSectionHtml('General Information', [
                viewFieldHtml('Code', `<span class="table-code">${escapeHtml(data.project_code)}</span>`),
                viewFieldHtml('Status', getStatusBadge(data.status)),
                viewFieldHtml('Program', escapeHtml(data.program_title || '-')),
                viewFieldHtml('Location', escapeHtml(data.location)),
                viewFieldHtml('Budget', formatCurrency(data.budget)),
                viewFieldHtml('Completion', data.completion_percentage + '%'),
            ])}
            ${data.description ? viewSectionHtml('Description', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>`]) : ''}
            ${data.objectives ? viewSectionHtml('Objectives', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.objectives)}</p></div>`]) : ''}
        `;
        sl.openView(data.title, data.project_code, content, {
            size: 'lg',
            onEdit: data.canEdit ? () => editProject(id, null) : null
        });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

async function editProject(id, btn) {
    const sl = getSlideOver({ size: 'lg', title: 'Edit Project', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/projects.php?action=get&id=${id}`, btn);
        sl.setTitle('Edit Project');
        sl.subtitle = data.project_code;
        const programOptions = programsList.map(p => ({ value: p.id, label: p.title }));
        const formHtml = `
            <input type="hidden" name="id" value="${data.id}">
            <div class="form-section">
                <h4 class="form-section-title">Project Information</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'title', label: 'Project Title', required: true, value: data.title, fullWidth: true })}
                    ${fieldHtml({ name: 'program_id', label: 'Parent Program', type: 'select', options: programOptions, value: data.program_id, required: true, fullWidth: true })}
                    ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', value: data.description, fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'location', label: 'Location', value: data.location })}
                </div>
            </div>
            <div class="form-section">
                <h4 class="form-section-title">Timeline & Budget</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date', value: data.start_date })}
                    ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date', value: data.end_date })}
                    ${fieldHtml({ name: 'budget', label: 'Budget', type: 'number', value: data.budget })}
                    ${fieldHtml({ name: 'status', label: 'Status', type: 'select', value: data.status, options: [{value:'draft',label:'Draft'},{value:'planned',label:'Planned'},{value:'ongoing',label:'Ongoing'},{value:'completed',label:'Completed'},{value:'cancelled',label:'Cancelled'}] })}
                    ${fieldHtml({ name: 'completion_percentage', label: 'Completion %', type: 'number', value: data.completion_percentage })}
                </div>
            </div>
            <div class="form-section">
                <h4 class="form-section-title">Details</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', value: data.objectives, fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', value: data.expected_outputs, fullWidth: true, rows: 3 })}
                </div>
            </div>`;
        sl.openForm('Edit Project', data.project_code, formHtml, { showSaveAnother: false, size: 'lg' });
        document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitSlideOverForm(sl, `${siteUrl}/ajax/projects.php?action=update`, new FormData(e.target), () => refreshProjectsTable());
        });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Components</h1><p class="text-[#9CA3AF] text-sm mt-1">Project components and study areas</p></div>
    <?php if (Permissions::canCreateComponent()): ?>
        <button onclick="openCreateComponent()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New Component</button>
    <?php endif; ?>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="components">
        <div class="flex-1"><input type="text" name="search" placeholder="Search components..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div id="componentsTable">
    <div class="data-table-container">
        <?php if (empty($components)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-puzzle-piece"></i></div><h3 class="empty-state-title">No components found</h3><p class="empty-state-text">Components will appear when projects are created.</p></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Code</th><th>Component</th><th>Project</th><th>Status</th><th>Progress</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($components as $i => $c): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Code"><span class="table-code"><?= e($c['component_code']) ?></span></td>
                        <td data-label="Component"><span class="table-title"><?= e($c['title']) ?></span></td>
                        <td data-label="Project"><span class="text-[#D1D5DB]"><?= e($c['project_title'] ?? '-') ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($c['status']) ?></td>
                        <td data-label="Progress"><div class="flex items-center gap-2"><div class="progress-bar w-16"><div class="progress-fill" style="width:<?= $c['completion_percentage'] ?>%"></div></div><span class="text-xs text-[#9CA3AF]"><?= $c['completion_percentage'] ?>%</span></div></td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewComponent(<?= $c['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                                <?php if (Permissions::canEditComponent($c['id'])): ?>
                                    <button onclick="editComponent(<?= $c['id'] ?>, this)" class="action-btn" data-loading title="Edit"><i class="fas fa-edit text-sm"></i></button>
                                <?php endif; ?>
                                <?php if (Permissions::canAssignMembers()): ?>
                                    <button onclick="openAssignments('component', <?= $c['id'] ?>, '<?= e(addslashes($c['title'])) ?>')" class="action-btn" title="Assign"><i class="fas fa-users text-sm"></i></button>
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

function refreshComponentsTable() {
    fetch(`${siteUrl}/index.php?module=components`)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.getElementById('componentsTable');
            if (newTable) document.getElementById('componentsTable').innerHTML = newTable.innerHTML;
        });
}

function openCreateComponent() {
    const sl = getSlideOver({ size: 'md', title: 'New Component', subtitle: 'Create project component' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Component Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'project_id', label: 'Parent Project', type: 'select', required: true, fullWidth: true, options: <?= json_encode(array_map(fn($p) => ['value'=>$p['id'],'label'=>$p['project_code'].' - '.$p['title']], db()->query("SELECT id, project_code, title FROM projects WHERE deleted_at IS NULL ORDER BY title")->fetchAll())) ?> })}
                ${fieldHtml({ name: 'title', label: 'Component Title', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date' })}
                ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date' })}
                ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', fullWidth: true, rows: 3 })}
            </div>
        </div>`;
    sl.openForm('New Component', 'Create project component', formHtml, { showSaveAnother: true });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/components.php?action=create`, new FormData(e.target), () => refreshComponentsTable());
    });
}

async function viewComponent(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Component Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/components.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = data.component_code;
        const content = viewSectionHtml('Component Information', [
            viewFieldHtml('Code', `<span class="table-code">${escapeHtml(data.component_code)}</span>`),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
            viewFieldHtml('Start Date', formatDate(data.start_date)),
            viewFieldHtml('End Date', formatDate(data.end_date)),
            viewFieldHtml('Completion', data.completion_percentage + '%'),
        ]) + (data.description ? viewSectionHtml('Description', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>`]) : '');
        sl.openView(data.title, data.component_code, content, { size: 'md', onEdit: () => editComponent(id, null) });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

async function editComponent(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Edit Component', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/components.php?action=get&id=${id}`, btn);
        sl.setTitle('Edit Component');
        sl.subtitle = data.component_code;
        const formHtml = `
            <input type="hidden" name="id" value="${data.id}">
            <div class="form-section">
                <div class="form-grid">
                    ${fieldHtml({ name: 'title', label: 'Title', required: true, value: data.title, fullWidth: true })}
                    ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', value: data.description, fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'status', label: 'Status', type: 'select', value: data.status, options: [{value:'planned',label:'Planned'},{value:'in_progress',label:'In Progress'},{value:'completed',label:'Completed'}] })}
                    ${fieldHtml({ name: 'completion_percentage', label: 'Completion %', type: 'number', value: data.completion_percentage })}
                </div>
            </div>`;
        sl.openForm('Edit Component', data.component_code, formHtml, { showSaveAnother: false });
        document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitSlideOverForm(sl, `${siteUrl}/ajax/components.php?action=update`, new FormData(e.target), () => refreshComponentsTable());
        });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

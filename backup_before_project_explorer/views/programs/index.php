<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Programs</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage extension programs</p></div>
    <?php if (Permissions::canCreateProgram()): ?>
        <button onclick="openCreateProgram()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New Program</button>
    <?php endif; ?>
</div>

<div class="filter-bar mb-6">
    <form method="GET" id="programFilterForm" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="programs">
        <div class="flex-1"><input type="text" name="search" placeholder="Search programs..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <select name="status" class="filter-select"><option value="">All Status</option><?php foreach(['draft','active','completed','archived'] as $s): ?><option value="<?= $s ?>" <?= ($_GET['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div id="programsTable">
    <div class="data-table-container">
        <?php if(empty($result['data'])): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-layer-group"></i></div><h3 class="empty-state-title">No programs found</h3></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr>
                <th>Code</th><th>Program Title</th><th>College</th><th>Status</th><th>Actions</th>
            </tr></thead><tbody>
                <?php foreach($result['data'] as $i=>$p): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i*0.05 ?>s">
                        <td data-label="Code"><span class="table-code"><?= e($p['program_code']) ?></span></td>
                        <td data-label="Title"><span class="table-title"><?= e($p['title']) ?></span></td>
                        <td data-label="College"><span class="text-[#D1D5DB]"><?= e($p['college'] ?? '-') ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($p['status']) ?></td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewProgram(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                                <?php if(Permissions::canEditProgram($p['id'])): ?>
                                    <button onclick="editProgram(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="Edit"><i class="fas fa-edit text-sm"></i></button>
                                <?php endif; ?>
                                <?php if(Permissions::canAssignMembers()): ?>
                                    <button onclick="openAssignments('program', <?= $p['id'] ?>, '<?= e(addslashes($p['title'])) ?>')" class="action-btn" title="Assign"><i class="fas fa-users text-sm"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
    <?php if($totalPages>1): ?>
        <div class="flex items-center justify-between mt-6">
            <p class="text-sm text-[#9CA3AF]">Showing <?= ($page-1)*$limit+1 ?> to <?= min($page*$limit,$result['total']) ?> of <?= $result['total'] ?></p>
            <div class="pagination">
                <?php if($page>1): ?><a href="?module=programs&page=<?= $page-1 ?>" class="pagination-btn"><i class="fas fa-chevron-left text-xs"></i></a><?php endif; ?>
                <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?><a href="?module=programs&page=<?= $i ?>" class="pagination-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
                <?php if($page<$totalPages): ?><a href="?module=programs&page=<?= $page+1 ?>" class="pagination-btn"><i class="fas fa-chevron-right text-xs"></i></a><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';

function refreshProgramsTable() {
    const params = new URLSearchParams(window.location.search);
    fetch(`${siteUrl}/index.php?module=programs&${params.toString()}`)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.getElementById('programsTable');
            if (newTable) document.getElementById('programsTable').innerHTML = newTable.innerHTML;
        });
}

function openCreateProgram() {
    const sl = getSlideOver({ size: 'md', title: 'New Program', subtitle: 'Create extension program' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Program Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'title', label: 'Program Title', required: true, placeholder: 'Enter program title', fullWidth: true })}
                ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe the program', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'college', label: 'College', placeholder: 'e.g., College of Agriculture' })}
                ${fieldHtml({ name: 'campus', label: 'Campus', placeholder: 'e.g., Cauayan Campus', value: 'Cauayan Campus' })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Timeline & Budget</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date' })}
                ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date' })}
                ${fieldHtml({ name: 'budget_allocation', label: 'Budget', type: 'number', placeholder: '0.00', fullWidth: true })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Objectives</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', placeholder: 'Program objectives', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', placeholder: 'Expected deliverables', fullWidth: true, rows: 3 })}
            </div>
        </div>`;
    sl.openForm('New Program', 'Create extension program', formHtml, { showSaveAnother: true, size: 'md' });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/programs.php?action=create`, new FormData(e.target), () => refreshProgramsTable());
    });
}

async function viewProgram(id, btn) {
    const sl = getSlideOver({ size: 'lg', title: 'Program Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/programs.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = data.program_code;
        
        const content = `
            ${viewSectionHtml('General Information', [
                viewFieldHtml('Code', `<span class="table-code">${escapeHtml(data.program_code)}</span>`),
                viewFieldHtml('Status', getStatusBadge(data.status)),
                viewFieldHtml('College', escapeHtml(data.college)),
                viewFieldHtml('Campus', escapeHtml(data.campus)),
            ])}
            ${viewSectionHtml('Timeline & Budget', [
                viewFieldHtml('Start Date', formatDate(data.start_date)),
                viewFieldHtml('End Date', formatDate(data.end_date)),
                viewFieldHtml('Budget', formatCurrency(data.budget_allocation)),
            ])}
            ${data.description ? viewSectionHtml('Description', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>`]) : ''}
            ${data.objectives ? viewSectionHtml('Objectives', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.objectives)}</p></div>`]) : ''}
        `;
        
        sl.openView(data.title, data.program_code, content, {
            size: 'lg',
            onEdit: <?= Permissions::canEditProgram(0) ? '() => editProgram(id, null)' : 'null' ?>
        });
    } catch (err) { console.error('View program error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

async function editProgram(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Edit Program', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/programs.php?action=get&id=${id}`, btn);
        sl.setTitle('Edit Program');
        sl.subtitle = data.program_code;
        const formHtml = `
            <input type="hidden" name="id" value="${data.id}">
            <div class="form-section">
                <h4 class="form-section-title">Program Information</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'title', label: 'Program Title', required: true, value: data.title, fullWidth: true })}
                    ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', value: data.description, fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'college', label: 'College', value: data.college })}
                    ${fieldHtml({ name: 'campus', label: 'Campus', value: data.campus })}
                </div>
            </div>
            <div class="form-section">
                <h4 class="form-section-title">Timeline & Budget</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date', value: data.start_date })}
                    ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date', value: data.end_date })}
                    ${fieldHtml({ name: 'budget_allocation', label: 'Budget', type: 'number', value: data.budget_allocation, fullWidth: true })}
                </div>
            </div>`;
        sl.openForm('Edit Program', data.program_code, formHtml, { showSaveAnother: false });
        document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitSlideOverForm(sl, `${siteUrl}/ajax/programs.php?action=update`, new FormData(e.target), () => refreshProgramsTable());
        });
    } catch (err) { console.error('Edit program error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

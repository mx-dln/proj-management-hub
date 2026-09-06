<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Extension Activities</h1><p class="text-[#9CA3AF] text-sm mt-1">Seminars, trainings, workshops</p></div>
    <?php if (Permissions::canCreateActivity()): ?>
        <button onclick="openCreateActivity()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New Activity</button>
    <?php endif; ?>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="activities">
        <div class="flex-1"><input type="text" name="search" placeholder="Search activities..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div id="activitiesTable">
    <div class="data-table-container">
        <?php if (empty($activities)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-calendar-check"></i></div><h3 class="empty-state-title">No activities found</h3></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Code</th><th>Activity</th><th>Type</th><th>Component</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($activities as $i => $a): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Code"><span class="table-code"><?= e($a['activity_code']) ?></span></td>
                        <td data-label="Activity"><span class="table-title"><?= e($a['title']) ?></span></td>
                        <td data-label="Type"><span class="text-[#D1D5DB]"><?= e($a['type_name'] ?? '-') ?></span></td>
                        <td data-label="Component"><span class="text-[#D1D5DB]"><?= e($a['component_title'] ?? '-') ?></span></td>
                        <td data-label="Date"><span class="table-date"><?= $a['start_datetime'] ? date('M d, Y', strtotime($a['start_datetime'])) : '-' ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($a['status']) ?></td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewActivity(<?= $a['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                                <?php if (Permissions::canEditActivity($a['id'])): ?>
                                    <button onclick="editActivity(<?= $a['id'] ?>, this)" class="action-btn" data-loading title="Edit"><i class="fas fa-edit text-sm"></i></button>
                                <?php endif; ?>
                                <?php if (Permissions::canAssignMembers()): ?>
                                    <button onclick="openAssignments('activity', <?= $a['id'] ?>, '<?= e(addslashes($a['title'])) ?>')" class="action-btn" title="Assign"><i class="fas fa-users text-sm"></i></button>
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
const activityTypes = <?= json_encode(db()->query("SELECT id, name FROM activity_types WHERE is_active = 1")->fetchAll()) ?>;

function refreshActivitiesTable() {
    fetch(`${siteUrl}/index.php?module=activities`)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.getElementById('activitiesTable');
            if (newTable) document.getElementById('activitiesTable').innerHTML = newTable.innerHTML;
        });
}

function openCreateActivity() {
    const sl = getSlideOver({ size: 'lg', title: 'New Activity', subtitle: 'Create extension activity' });
    const typeOptions = activityTypes.map(t => ({ value: t.id, label: t.name }));
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Activity Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'component_id', label: 'Parent Component', type: 'select', required: true, fullWidth: true, options: <?= json_encode(array_map(fn($c) => ['value'=>$c['id'],'label'=>$c['component_code'].' - '.$c['title']], db()->query("SELECT id, component_code, title FROM components WHERE deleted_at IS NULL ORDER BY title")->fetchAll())) ?> })}
                ${fieldHtml({ name: 'title', label: 'Activity Title', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'activity_type_id', label: 'Type', type: 'select', options: typeOptions })}
                ${fieldHtml({ name: 'venue', label: 'Venue', placeholder: 'Activity venue' })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Schedule & Budget</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'start_datetime', label: 'Start Date/Time', type: 'datetime-local' })}
                ${fieldHtml({ name: 'end_datetime', label: 'End Date/Time', type: 'datetime-local' })}
                ${fieldHtml({ name: 'target_participants', label: 'Target Participants', type: 'number' })}
                ${fieldHtml({ name: 'budget_allocation', label: 'Budget', type: 'number' })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Details</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', fullWidth: true, rows: 3 })}
            </div>
        </div>`;
    sl.openForm('New Activity', 'Create extension activity', formHtml, { showSaveAnother: true, size: 'lg' });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/activities.php?action=create`, new FormData(e.target), () => refreshActivitiesTable());
    });
}

async function viewActivity(id, btn) {
    const sl = getSlideOver({ size: 'lg', title: 'Activity Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/activities.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = data.activity_code;
        const content = viewSectionHtml('Activity Information', [
            viewFieldHtml('Code', `<span class="table-code">${escapeHtml(data.activity_code)}</span>`),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('Type', escapeHtml(data.type_name || '-')),
            viewFieldHtml('Component', escapeHtml(data.component_title || '-')),
            viewFieldHtml('Venue', escapeHtml(data.venue)),
            viewFieldHtml('Start', data.start_datetime ? new Date(data.start_datetime).toLocaleString() : '-'),
            viewFieldHtml('End', data.end_datetime ? new Date(data.end_datetime).toLocaleString() : '-'),
            viewFieldHtml('Target Participants', data.target_participants || '-'),
            viewFieldHtml('Budget', formatCurrency(data.budget_allocation)),
        ]) + (data.description ? viewSectionHtml('Description', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>`]) : '');
        sl.openView(data.title, data.activity_code, content, { size: 'lg', onEdit: () => editActivity(id, null) });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

async function editActivity(id, btn) {
    const sl = getSlideOver({ size: 'lg', title: 'Edit Activity', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/activities.php?action=get&id=${id}`, btn);
        sl.setTitle('Edit Activity');
        sl.subtitle = data.activity_code;
        const typeOptions = activityTypes.map(t => ({ value: t.id, label: t.name }));
        const formHtml = `
            <input type="hidden" name="id" value="${data.id}">
            <div class="form-section">
                <div class="form-grid">
                    ${fieldHtml({ name: 'title', label: 'Title', required: true, value: data.title, fullWidth: true })}
                    ${fieldHtml({ name: 'activity_type_id', label: 'Type', type: 'select', options: typeOptions, value: data.activity_type_id })}
                    ${fieldHtml({ name: 'venue', label: 'Venue', value: data.venue })}
                    ${fieldHtml({ name: 'status', label: 'Status', type: 'select', value: data.status, options: [{value:'planned',label:'Planned'},{value:'ongoing',label:'Ongoing'},{value:'completed',label:'Completed'},{value:'cancelled',label:'Cancelled'}] })}
                    ${fieldHtml({ name: 'start_datetime', label: 'Start', type: 'datetime-local', value: data.start_datetime ? data.start_datetime.replace(' ','T').substring(0,16) : '' })}
                    ${fieldHtml({ name: 'end_datetime', label: 'End', type: 'datetime-local', value: data.end_datetime ? data.end_datetime.replace(' ','T').substring(0,16) : '' })}
                    ${fieldHtml({ name: 'target_participants', label: 'Target Participants', type: 'number', value: data.target_participants })}
                    ${fieldHtml({ name: 'budget_allocation', label: 'Budget', type: 'number', value: data.budget_allocation })}
                    ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', value: data.description, fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', value: data.objectives, fullWidth: true, rows: 3 })}
                </div>
            </div>`;
        sl.openForm('Edit Activity', data.activity_code, formHtml, { showSaveAnother: false, size: 'lg' });
        document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitSlideOverForm(sl, `${siteUrl}/ajax/activities.php?action=update`, new FormData(e.target), () => refreshActivitiesTable());
        });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

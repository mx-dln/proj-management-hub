<?php
if (!class_exists('AssignmentVisibility')) {
    require_once __DIR__ . '/../../includes/AssignmentVisibility.php';
}
$activityComponentOptions = Permissions::isAdmin()
    ? db()->query("SELECT id, component_code, title FROM components WHERE deleted_at IS NULL ORDER BY title")->fetchAll()
    : AssignmentVisibility::getVisibleComponents('', 500, 0)['data'];
?>
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Extension Activities</h1><p class="text-[#9CA3AF] text-sm mt-1">Seminars, trainings, workshops</p></div>
    <?php if (Permissions::canCreateActivity()): ?>
        <button onclick="openCreateActivity()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New Activity</button>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../projects/navigation.php'; ?>
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
                                <button onclick='viewParticipants(<?= (int)$a['id'] ?>, <?= json_encode($a['title']) ?>, this)' class="action-btn" data-loading title="Participants"><i class="fas fa-user-check text-sm"></i></button>
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
const siteUrl = <?= json_encode(SITE_URL) ?>;
const activityTypes = <?= json_encode(db()->query("SELECT id, name FROM activity_types WHERE is_active = 1")->fetchAll()) ?>;
const beneficiaryGroups = <?= json_encode(array_map(fn($b) => ['value' => $b['id'], 'label' => $b['name']], db()->query("SELECT id, name FROM beneficiary_groups WHERE is_active = 1 ORDER BY name")->fetchAll()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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
                ${fieldHtml({ name: 'component_id', label: 'Parent Component', type: 'select', required: true, fullWidth: true, options: <?= json_encode(array_map(fn($c) => ['value'=>$c['id'],'label'=>$c['component_code'].' - '.$c['title']], $activityComponentOptions)) ?> })}
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
        const participants = await fetchWithLoading(`${siteUrl}/ajax/activities.php?action=get_participants&id=${id}`);
        const participantRows = participants.length ? participants.map(p => `
            <tr>
                <td>${escapeHtml(p.name || '-')}</td>
                <td>${escapeHtml(p.beneficiary_group_name || p.organization || '-')}</td>
                <td>${escapeHtml(p.municipality || '-')}</td>
                <td>${escapeHtml(p.attendance_status || '-')}</td>
                <td>${escapeHtml(p.certificate_status || '-')}</td>
            </tr>
        `).join('') : `<tr><td colspan="5" class="text-center text-[#6B7280] py-4">No participants listed yet</td></tr>`;
        const participantSection = `<div class="form-section">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h4 class="form-section-title mb-0">Participants (${participants.length})</h4>
                ${data.can_add_participants ? `<button type="button" onclick='openAddParticipant(${Number(id)}, ${JSON.stringify(data.title || '')})' class="btn-primary"><i class="fas fa-plus mr-1"></i> Add</button>` : ''}
            </div>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Name</th><th>Beneficiary Group</th><th>Municipality</th><th>Attendance</th><th>Certificate</th></tr></thead><tbody>${participantRows}</tbody></table></div>
        </div>`;
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
        ]) + participantSection + (data.description ? viewSectionHtml('Description', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>`]) : '');
        sl.openView(data.title, data.activity_code, content, { size: 'lg', onEdit: () => editActivity(id, null) });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

async function viewParticipants(id, title, btn) {
    const activity = await fetch(`${siteUrl}/ajax/activities.php?action=get&id=${id}`).then(r => r.json());
    const participants = await fetchWithLoading(`${siteUrl}/ajax/activities.php?action=get_participants&id=${id}`, btn);
    const rows = participants.length ? participants.map(p => `
        <tr>
            <td><span class="table-title">${escapeHtml(p.name || '-')}</span><p class="text-xs text-[#6B7280]">${escapeHtml(p.phone || '')}</p></td>
            <td>${escapeHtml(p.beneficiary_group_name || p.organization || '-')}</td>
            <td>${escapeHtml([p.barangay, p.municipality, p.province].filter(Boolean).join(', ') || '-')}</td>
            <td>${escapeHtml(p.attendance_status || '-')}</td>
            <td>${escapeHtml(p.certificate_status || '-')}</td>
        </tr>
    `).join('') : `<tr><td colspan="5" class="text-center text-[#6B7280] py-6">No participants listed yet</td></tr>`;
    const addButton = activity.can_add_participants ? `<button type="button" onclick='openAddParticipant(${Number(id)}, ${JSON.stringify(title || '')})' class="btn-primary"><i class="fas fa-plus mr-1"></i> Add Participant</button>` : '';
    getSlideOver({size:'lg'}).openView('Participants', title || '', `<div class="form-section">
        <div class="flex justify-end mb-3">${addButton}</div>
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Name</th><th>Beneficiary Group</th><th>Address</th><th>Attendance</th><th>Certificate</th></tr></thead><tbody>${rows}</tbody></table></div>
    </div>`, {size:'lg'});
}

function openAddParticipant(activityId, title) {
    const sl = getSlideOver({ size: 'md' });
    const formHtml = `<div class="form-section"><div class="form-grid">
        <input type="hidden" name="activity_id" value="${Number(activityId)}">
        ${fieldHtml({ name: 'name', label: 'Participant Name', required: true, fullWidth: true })}
        ${fieldHtml({ name: 'beneficiary_group_id', label: 'Beneficiary Group', type: 'select', options: beneficiaryGroups, fullWidth: true })}
        ${fieldHtml({ name: 'age', label: 'Age', type: 'number' })}
        ${fieldHtml({ name: 'gender', label: 'Gender', type: 'select', options: [{value:'',label:'-'},{value:'male',label:'Male'},{value:'female',label:'Female'},{value:'other',label:'Other'}] })}
        ${fieldHtml({ name: 'organization', label: 'Organization' })}
        ${fieldHtml({ name: 'occupation', label: 'Occupation' })}
        ${fieldHtml({ name: 'phone', label: 'Phone' })}
        ${fieldHtml({ name: 'barangay', label: 'Barangay' })}
        ${fieldHtml({ name: 'municipality', label: 'Municipality' })}
        ${fieldHtml({ name: 'province', label: 'Province' })}
    </div></div>`;
    sl.openForm('Add Participant', title || '', formHtml, { showSaveAnother: true, size: 'md' });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/activities.php?action=add_participant`, new FormData(e.target), () => viewParticipants(activityId, title, null));
    });
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

window.refreshActivitiesTable = refreshActivitiesTable;
window.openCreateActivity = openCreateActivity;
window.viewActivity = viewActivity;
window.viewParticipants = viewParticipants;
window.openAddParticipant = openAddParticipant;
window.editActivity = editActivity;
</script>

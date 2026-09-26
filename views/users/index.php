<?php
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
?>
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Users</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage user accounts and access roles</p></div>
    <button onclick="openCreateUser()" class="btn-primary"><i class="fas fa-user-plus mr-1"></i> New User</button>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="users">
        <div class="flex-1 min-w-0"><input type="search" name="search" value="<?= e($search) ?>" placeholder="Search users..." class="filter-input" aria-label="Search users"></div>
        <select name="role" class="filter-select" aria-label="Role">
            <option value="">All Roles</option>
            <?php foreach (['admin' => 'Admin', 'faculty' => 'Faculty', 'viewer' => 'Viewer'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $roleFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary" aria-label="Search"><i class="fas fa-search"></i></button>
        <?php if ($search || $roleFilter): ?><a href="<?= SITE_URL ?>/index.php?module=users" class="btn-ghost" aria-label="Clear filters"><i class="fas fa-times"></i></a><?php endif; ?>
    </form>
</div>

<div class="data-table-container">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>User</th><th>Name</th><th>Department</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td data-label="User"><span class="table-title"><?= e($u['username']) ?></span><p class="text-xs text-[#6B7280]"><?= e($u['email']) ?></p></td>
                    <td data-label="Name"><?= e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: '-') ?></td>
                    <td data-label="Department"><?= e($u['department_name'] ?? '-') ?></td>
                    <td data-label="Role"><span class="badge badge-submitted"><?= e(ucfirst($u['role'])) ?></span></td>
                    <td data-label="Status"><span class="badge <?= $u['is_active'] ? 'badge-approved' : 'badge-archived' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td data-label="Last Login"><span class="table-date"><?= $u['last_login'] ? formatDateTime($u['last_login']) : '-' ?></span></td>
                    <td data-label="Actions">
                        <button onclick="viewUser(<?= (int)$u['id'] ?>, this)" class="action-btn" title="View" aria-label="View"><i class="fas fa-eye"></i></button>
                        <button onclick="editUser(<?= (int)$u['id'] ?>, this)" class="action-btn" title="Edit" aria-label="Edit"><i class="fas fa-pen"></i></button>
                        <button onclick="toggleUserStatus(<?= (int)$u['id'] ?>, this)" class="action-btn" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>" aria-label="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"><i class="fas <?= $u['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i></button>
                        <button onclick="deleteUser(<?= (int)$u['id'] ?>, this)" class="action-btn action-btn-danger" title="Delete" aria-label="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?><tr><td colspan="7" class="text-center text-[#6B7280] py-8">No users found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="flex items-center justify-between mt-4" aria-label="User pages">
    <span class="text-sm text-[#9CA3AF]">Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
    <div class="flex gap-2">
        <?php if ($page > 1): ?><a class="btn-ghost" href="?<?= e(http_build_query(['module' => 'users', 'search' => $search, 'role' => $roleFilter, 'page' => $page - 1])) ?>" aria-label="Previous page"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
        <?php if ($page < $totalPages): ?><a class="btn-ghost" href="?<?= e(http_build_query(['module' => 'users', 'search' => $search, 'role' => $roleFilter, 'page' => $page + 1])) ?>" aria-label="Next page"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
    </div>
</nav>
<?php endif; ?>

<script>
window.usersSiteUrl = <?= json_encode(SITE_URL) ?>;
const siteUrl = window.usersSiteUrl;
const departments = <?= json_encode(array_map(fn($d) => ['value' => $d['id'], 'label' => $d['name']], $departments), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function userFormHtml(data = {}, isEdit = false) {
    return `<div class="form-section"><div class="form-grid">
        ${isEdit ? `<input type="hidden" name="id" value="${Number(data.id || 0)}">` : ''}
        ${fieldHtml({name:'username',label:'Username',required:true,value:data.username || ''})}
        ${fieldHtml({name:'email',label:'Email',type:'email',required:true,value:data.email || ''})}
        ${fieldHtml({name:'password',label:isEdit ? 'New Password' : 'Password',type:'password',required:!isEdit,help:isEdit ? 'Leave blank to keep current password.' : ''})}
        ${fieldHtml({name:'role',label:'Role',type:'select',required:true,value:data.role || 'faculty',options:[{value:'admin',label:'Admin'},{value:'faculty',label:'Faculty'},{value:'viewer',label:'Viewer'}]})}
        ${fieldHtml({name:'employee_id',label:'Employee ID',value:data.employee_id || ''})}
        ${fieldHtml({name:'department_id',label:'Department',type:'select',value:data.department_id || '',options:departments})}
        ${fieldHtml({name:'first_name',label:'First Name',value:data.first_name || ''})}
        ${fieldHtml({name:'last_name',label:'Last Name',value:data.last_name || ''})}
        ${fieldHtml({name:'middle_name',label:'Middle Name',value:data.middle_name || ''})}
        ${fieldHtml({name:'position',label:'Position',value:data.position || ''})}
        ${fieldHtml({name:'contact_number',label:'Contact Number',value:data.contact_number || '',fullWidth:true})}
    </div></div>`;
}

window.openCreateUser = function openCreateUser() {
    const sl = getSlideOver({size:'md'});
    sl.openForm('New User', 'Create user account', userFormHtml(), {showSaveAnother:false});
    document.getElementById('slideoverForm').addEventListener('submit', async e => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/users.php?action=create`, new FormData(e.target), () => location.reload());
    });
}

window.viewUser = async function viewUser(id, btn) {
    const data = await fetchWithLoading(`${siteUrl}/ajax/users.php?action=get&id=${id}`, btn);
    const details = viewSectionHtml('User Details', [
        viewFieldHtml('Name', escapeHtml(`${data.first_name || ''} ${data.last_name || ''}`.trim() || '-')),
        viewFieldHtml('Employee ID', escapeHtml(data.employee_id || '-')),
        viewFieldHtml('Department', escapeHtml(data.department_name || '-')),
        viewFieldHtml('Position', escapeHtml(data.position || '-')),
        viewFieldHtml('Role', escapeHtml(data.role || '-')),
        viewFieldHtml('Status', data.is_active == 1 ? 'Active' : 'Inactive')
    ]);
    getSlideOver({size:'lg'}).openView(data.username, data.email, details + userSubmissionHistoryHtml(data.submission_history || []), {size:'lg'});
}

function userSubmissionHistoryHtml(history) {
    const rows = history.length ? history.map(item => {
        const typeLabel = item.submission_type === 'report' ? 'Report' : 'Proposal';
        const fileLink = item.attachment
            ? `<a href="${siteUrl}/ajax/${item.submission_type === 'report' ? 'reports' : 'proposals'}.php?action=file&id=${Number(item.submission_id)}" target="_blank" rel="noopener" class="btn-ghost text-xs"><i class="fas fa-file-alt mr-1"></i> File</a>`
            : '';
        const versionText = item.submission_type === 'proposal'
            ? `<span>${Number(item.version_count || 0)} version${Number(item.version_count || 0) === 1 ? '' : 's'}</span>`
            : '';
        return `<div class="border border-[#374151] rounded-lg p-3 bg-[#111827]">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge badge-submitted">${typeLabel}</span>
                        <span class="table-code">${escapeHtml(item.reference_number || '-')}</span>
                        ${getStatusBadge(item.status || '')}
                    </div>
                    <p class="text-sm font-semibold text-[#F9FAFB] mt-2">${escapeHtml(item.title || '-')}</p>
                    <p class="text-xs text-[#9CA3AF] mt-1">${escapeHtml(item.parent_title || '-')}</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">${fileLink}</div>
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-[#9CA3AF] mt-3">
                <span>Submitted: ${formatDate(item.submitted_at)}</span>
                <span>${Number(item.review_count || 0)} review action${Number(item.review_count || 0) === 1 ? '' : 's'}</span>
                ${versionText}
            </div>
            ${item.remarks ? `<p class="text-xs text-[#D1D5DB] mt-2 whitespace-pre-wrap">${escapeHtml(item.remarks)}</p>` : ''}
        </div>`;
    }).join('') : `<div class="text-sm text-[#6B7280] border border-dashed border-[#374151] rounded-lg p-4 text-center">No submission history yet</div>`;

    return `<div class="form-section">
        <h4 class="form-section-title">Submission History</h4>
        <div class="space-y-2">${rows}</div>
    </div>`;
}

window.editUser = async function editUser(id, btn) {
    const data = await fetchWithLoading(`${siteUrl}/ajax/users.php?action=get&id=${id}`, btn);
    const sl = getSlideOver({size:'md'});
    sl.openForm('Edit User', data.username || '', userFormHtml(data, true), {showSaveAnother:false});
    document.getElementById('slideoverForm').addEventListener('submit', async e => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/users.php?action=update`, new FormData(e.target), () => location.reload());
    });
}

window.toggleUserStatus = async function toggleUserStatus(id, btn) {
    if (!confirm('Update this user status?')) return;
    setButtonLoading(btn, true);
    try {
        const response = await fetch(`${siteUrl}/ajax/users.php?action=toggle_status`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id})
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Status update failed.');
        showToast(data.message || 'User updated.');
        setTimeout(() => location.reload(), 700);
    } catch (error) {
        showToast(error.message || 'Could not update user.', 'error');
    } finally {
        setButtonLoading(btn, false);
    }
}

window.deleteUser = async function deleteUser(id, btn) {
    if (!confirm('Delete this user account?')) return;
    setButtonLoading(btn, true);
    try {
        const response = await fetch(`${siteUrl}/ajax/users.php?action=delete`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id})
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Delete failed.');
        showToast(data.message || 'User deleted.');
        setTimeout(() => location.reload(), 700);
    } catch (error) {
        showToast(error.message || 'Could not delete user.', 'error');
    } finally {
        setButtonLoading(btn, false);
    }
}
</script>

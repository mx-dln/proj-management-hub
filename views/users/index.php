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
                        <button onclick="toggleUserStatus(<?= (int)$u['id'] ?>, this)" class="action-btn" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>" aria-label="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"><i class="fas <?= $u['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i></button>
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
const siteUrl = <?= json_encode(SITE_URL) ?>;
const departments = <?= json_encode(array_map(fn($d) => ['value' => $d['id'], 'label' => $d['name']], $departments), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function openCreateUser() {
    const sl = getSlideOver({size:'md'});
    const formHtml = `<div class="form-section"><div class="form-grid">
        ${fieldHtml({name:'username',label:'Username',required:true})}
        ${fieldHtml({name:'email',label:'Email',type:'email',required:true})}
        ${fieldHtml({name:'password',label:'Password',type:'password',required:true})}
        ${fieldHtml({name:'role',label:'Role',type:'select',required:true,options:[{value:'admin',label:'Admin'},{value:'faculty',label:'Faculty'},{value:'viewer',label:'Viewer'}]})}
        ${fieldHtml({name:'employee_id',label:'Employee ID'})}
        ${fieldHtml({name:'department_id',label:'Department',type:'select',options:departments})}
        ${fieldHtml({name:'first_name',label:'First Name'})}
        ${fieldHtml({name:'last_name',label:'Last Name'})}
        ${fieldHtml({name:'middle_name',label:'Middle Name'})}
        ${fieldHtml({name:'position',label:'Position'})}
        ${fieldHtml({name:'contact_number',label:'Contact Number',fullWidth:true})}
    </div></div>`;
    sl.openForm('New User', 'Create user account', formHtml, {showSaveAnother:false});
    document.getElementById('slideoverForm').addEventListener('submit', async e => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/users.php?action=create`, new FormData(e.target), () => location.reload());
    });
}

async function viewUser(id, btn) {
    const data = await fetchWithLoading(`${siteUrl}/ajax/users.php?action=get&id=${id}`, btn);
    getSlideOver({size:'md'}).openView(data.username, data.email, viewSectionHtml('User Details', [
        viewFieldHtml('Name', escapeHtml(`${data.first_name || ''} ${data.last_name || ''}`.trim() || '-')),
        viewFieldHtml('Employee ID', escapeHtml(data.employee_id || '-')),
        viewFieldHtml('Department', escapeHtml(data.department_name || '-')),
        viewFieldHtml('Position', escapeHtml(data.position || '-')),
        viewFieldHtml('Role', escapeHtml(data.role || '-')),
        viewFieldHtml('Status', data.is_active == 1 ? 'Active' : 'Inactive')
    ]), {size:'md'});
}

async function toggleUserStatus(id, btn) {
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
</script>

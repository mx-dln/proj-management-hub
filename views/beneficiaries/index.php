<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Beneficiary Groups</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage target beneficiary groups</p></div>
    <button onclick="openCreateBeneficiary()" class="btn-primary"><i class="fas fa-plus mr-1"></i> Add Group</button>
</div>

<div id="beneficiariesTable">
    <div class="data-table-container">
        <?php if (empty($beneficiaries)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-people-group"></i></div><h3 class="empty-state-title">No beneficiary groups found</h3><p class="empty-state-text">Add your first beneficiary group.</p></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Name</th><th>Type</th><th>Barangay</th><th>Municipality</th><th>Contact</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($beneficiaries as $i => $b): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Name"><span class="table-title"><?= e($b['name']) ?></span></td>
                        <td data-label="Type"><span class="badge badge-submitted"><?= ucfirst($b['group_type'] ?? '-') ?></span></td>
                        <td data-label="Barangay"><span class="text-[#D1D5DB]"><?= e($b['barangay'] ?? '-') ?></span></td>
                        <td data-label="Municipality"><span class="text-[#D1D5DB]"><?= e($b['municipality'] ?? '-') ?></span></td>
                        <td data-label="Contact"><span class="text-[#D1D5DB]"><?= e($b['contact_person'] ?? '-') ?></span></td>
                        <td data-label="Actions">
                            <button onclick="viewBeneficiary(<?= $b['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';

function refreshBeneficiariesTable() {
    fetch(`${siteUrl}/index.php?module=beneficiaries`)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.getElementById('beneficiariesTable');
            if (newTable) document.getElementById('beneficiariesTable').innerHTML = newTable.innerHTML;
        });
}

function openCreateBeneficiary() {
    const sl = getSlideOver({ size: 'md', title: 'Add Beneficiary Group', subtitle: 'Add a new beneficiary group' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Group Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'name', label: 'Group Name', required: true, placeholder: 'Enter group name', fullWidth: true })}
                ${fieldHtml({ name: 'group_type', label: 'Type', type: 'select', options: [{value:'individual',label:'Individual'},{value:'organization',label:'Organization'},{value:'community',label:'Community'},{value:'lgu',label:'LGU'}] })}
                ${fieldHtml({ name: 'barangay', label: 'Barangay', placeholder: 'Barangay name' })}
                ${fieldHtml({ name: 'municipality', label: 'Municipality', placeholder: 'Municipality name' })}
                ${fieldHtml({ name: 'province', label: 'Province', placeholder: 'Province name' })}
                ${fieldHtml({ name: 'contact_person', label: 'Contact Person', placeholder: 'Full name' })}
                ${fieldHtml({ name: 'contact_number', label: 'Contact Number', placeholder: 'Phone number' })}
            </div>
        </div>`;
    sl.openForm('Add Beneficiary Group', 'New beneficiary group', formHtml, { showSaveAnother: true });
    const form = document.getElementById('slideoverForm');
    form.addEventListener('submit', async (e) => { e.preventDefault(); await submitSlideOverForm(sl, `${siteUrl}/ajax/beneficiaries.php?action=create`, new FormData(form), () => refreshBeneficiariesTable()); });
}

async function viewBeneficiary(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Beneficiary Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/beneficiaries.php?action=get&id=${id}`, btn);
        sl.setTitle(data.name);
        sl.subtitle = data.group_type ? data.group_type.charAt(0).toUpperCase() + data.group_type.slice(1) : '';
        const content = viewSectionHtml('Group Information', [
            viewFieldHtml('Name', escapeHtml(data.name)),
            viewFieldHtml('Type', escapeHtml(data.group_type)),
            viewFieldHtml('Barangay', escapeHtml(data.barangay)),
            viewFieldHtml('Municipality', escapeHtml(data.municipality)),
            viewFieldHtml('Province', escapeHtml(data.province)),
            viewFieldHtml('Contact Person', escapeHtml(data.contact_person)),
            viewFieldHtml('Contact Number', escapeHtml(data.contact_number)),
        ]);
        sl.openView(data.name, '', content, { size: 'md' });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

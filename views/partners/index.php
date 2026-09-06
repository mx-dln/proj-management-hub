<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Partner Agencies</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage partner organizations</p></div>
    <button onclick="openCreatePartner()" class="btn-primary"><i class="fas fa-plus mr-1"></i> Add Partner</button>
</div>

<div id="partnersTable">
    <div class="data-table-container">
        <?php if (empty($partners)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-building"></i></div><h3 class="empty-state-title">No partners found</h3><p class="empty-state-text">Add your first partner agency.</p></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Name</th><th>Type</th><th>Contact</th><th>Email</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($partners as $i => $p): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Name"><span class="table-title"><?= e($p['name']) ?></span></td>
                        <td data-label="Type"><span class="badge badge-submitted"><?= ucfirst($p['agency_type'] ?? '-') ?></span></td>
                        <td data-label="Contact"><span class="text-[#D1D5DB]"><?= e($p['contact_person'] ?? '-') ?></span></td>
                        <td data-label="Email"><span class="text-[#D1D5DB]"><?= e($p['email'] ?? '-') ?></span></td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewPartner(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                                <button onclick="editPartner(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="Edit"><i class="fas fa-edit text-sm"></i></button>
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

function refreshPartnersTable() {
    fetch(`${siteUrl}/index.php?module=partners`)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.getElementById('partnersTable');
            if (newTable) document.getElementById('partnersTable').innerHTML = newTable.innerHTML;
        });
}

function openCreatePartner() {
    const sl = getSlideOver({ size: 'md', title: 'Add Partner Agency', subtitle: 'Add a new partner organization' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Agency Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'name', label: 'Agency Name', required: true, placeholder: 'Enter agency name', fullWidth: true })}
                ${fieldHtml({ name: 'agency_type', label: 'Type', type: 'select', options: [{value:'government',label:'Government'},{value:'ngo',label:'NGO'},{value:'private',label:'Private'},{value:'academic',label:'Academic'}] })}
                ${fieldHtml({ name: 'contact_person', label: 'Contact Person', placeholder: 'Full name' })}
                ${fieldHtml({ name: 'contact_number', label: 'Contact Number', placeholder: 'Phone number' })}
                ${fieldHtml({ name: 'email', label: 'Email', type: 'email', placeholder: 'email@example.com', fullWidth: true })}
                ${fieldHtml({ name: 'address', label: 'Address', type: 'textarea', placeholder: 'Full address', fullWidth: true, rows: 2 })}
            </div>
        </div>`;
    sl.openForm('Add Partner Agency', 'New partner organization', formHtml, { showSaveAnother: true, size: 'md' });
    const form = document.getElementById('slideoverForm');
    form.addEventListener('submit', async (e) => { e.preventDefault(); await submitSlideOverForm(sl, `${siteUrl}/ajax/partners.php?action=create`, new FormData(form), () => refreshPartnersTable()); });
}

async function viewPartner(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Partner Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/partners.php?action=get&id=${id}`, btn);
        sl.setTitle(data.name);
        sl.subtitle = data.agency_type ? data.agency_type.charAt(0).toUpperCase() + data.agency_type.slice(1) : '';
        const content = viewSectionHtml('Agency Information', [
            viewFieldHtml('Name', escapeHtml(data.name)),
            viewFieldHtml('Type', escapeHtml(data.agency_type)),
            viewFieldHtml('Contact Person', escapeHtml(data.contact_person)),
            viewFieldHtml('Contact Number', escapeHtml(data.contact_number)),
            viewFieldHtml('Email', escapeHtml(data.email)),
            viewFieldHtml('Address', escapeHtml(data.address)),
        ]);
        sl.openView(data.name, '', content, { size: 'md', onEdit: () => editPartner(id) });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

async function editPartner(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Edit Partner', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/partners.php?action=get&id=${id}`, btn);
        sl.setTitle('Edit Partner');
        sl.subtitle = data.name;
        const formHtml = `
            <input type="hidden" name="id" value="${data.id}">
            <div class="form-section">
                <h4 class="form-section-title">Agency Information</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'name', label: 'Agency Name', required: true, value: data.name, fullWidth: true })}
                    ${fieldHtml({ name: 'agency_type', label: 'Type', type: 'select', value: data.agency_type, options: [{value:'government',label:'Government'},{value:'ngo',label:'NGO'},{value:'private',label:'Private'},{value:'academic',label:'Academic'}] })}
                    ${fieldHtml({ name: 'contact_person', label: 'Contact Person', value: data.contact_person })}
                    ${fieldHtml({ name: 'contact_number', label: 'Contact Number', value: data.contact_number })}
                    ${fieldHtml({ name: 'email', label: 'Email', type: 'email', value: data.email, fullWidth: true })}
                    ${fieldHtml({ name: 'address', label: 'Address', type: 'textarea', value: data.address, fullWidth: true, rows: 2 })}
                </div>
            </div>`;
        sl.openForm('Edit Partner', data.name, formHtml, { showSaveAnother: false });
        const form = document.getElementById('slideoverForm');
        form.addEventListener('submit', async (e) => { e.preventDefault(); await submitSlideOverForm(sl, `${siteUrl}/ajax/partners.php?action=update`, new FormData(form), () => refreshPartnersTable()); });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

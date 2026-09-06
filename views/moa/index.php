<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">MOA Management</h1><p class="text-[#9CA3AF] text-sm mt-1">Memorandum of Agreement records</p></div>
    <?php if (Permissions::canManageMoa()): ?>
        <button onclick="openCreateMoa()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New MOA</button>
    <?php endif; ?>
</div>

<div id="moaTable">
    <div class="data-table-container">
        <?php if (empty($moas)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-handshake"></i></div><h3 class="empty-state-title">No MOA found</h3></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>MOA #</th><th>Project</th><th>Partner</th><th>Signed</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($moas as $i => $m): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="MOA #"><span class="table-code"><?= e($m['moa_number']) ?></span></td>
                        <td data-label="Project"><span class="table-title"><?= e($m['project_title'] ?? '-') ?></span></td>
                        <td data-label="Partner"><span class="text-[#D1D5DB]"><?= e($m['partner_agency'] ?? '-') ?></span></td>
                        <td data-label="Signed"><span class="table-date"><?= formatDate($m['date_signed']) ?></span></td>
                        <td data-label="Expires"><span class="table-date"><?= formatDate($m['expiration_date']) ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($m['status']) ?></td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewMoa(<?= $m['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                                <?php if (Permissions::canManageMoa()): ?>
                                    <button onclick="editMoa(<?= $m['id'] ?>, this)" class="action-btn" data-loading title="Edit"><i class="fas fa-edit text-sm"></i></button>
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

function refreshMoaTable() {
    fetch(`${siteUrl}/index.php?module=moa`)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.getElementById('moaTable');
            if (newTable) document.getElementById('moaTable').innerHTML = newTable.innerHTML;
        });
}

function openCreateMoa() {
    const sl = getSlideOver({ size: 'md', title: 'New MOA', subtitle: 'Create MOA record' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">MOA Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'moa_number', label: 'MOA Number', required: true, placeholder: 'e.g., MOA-2026-0001' })}
                ${fieldHtml({ name: 'project_id', label: 'Project', type: 'select', required: true, options: <?= json_encode(array_map(fn($p) => ['value'=>$p['id'],'label'=>$p['title']], db()->query("SELECT id, title FROM projects WHERE deleted_at IS NULL ORDER BY title")->fetchAll())) ?> })}
                ${fieldHtml({ name: 'partner_agency', label: 'Partner Agency', required: true })}
                ${fieldHtml({ name: 'date_signed', label: 'Date Signed', type: 'date' })}
                ${fieldHtml({ name: 'expiration_date', label: 'Expiration Date', type: 'date' })}
                ${fieldHtml({ name: 'remarks', label: 'Remarks', type: 'textarea', fullWidth: true, rows: 3 })}
            </div>
        </div>`;
    sl.openForm('New MOA', 'Create MOA record', formHtml, { showSaveAnother: false });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/moa.php?action=create`, new FormData(e.target), () => refreshMoaTable());
    });
}

async function viewMoa(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'MOA Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/moa.php?action=get&id=${id}`, btn);
        sl.setTitle(data.moa_number);
        sl.subtitle = data.partner_agency || '';
        const content = viewSectionHtml('MOA Information', [
            viewFieldHtml('MOA Number', `<span class="table-code">${escapeHtml(data.moa_number)}</span>`),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
            viewFieldHtml('Partner', escapeHtml(data.partner_agency)),
            viewFieldHtml('Signed', formatDate(data.date_signed)),
            viewFieldHtml('Expires', formatDate(data.expiration_date)),
        ]) + (data.remarks ? viewSectionHtml('Remarks', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.remarks)}</p></div>`]) : '');
        sl.openView(data.moa_number, '', content, { size: 'md', onEdit: () => editMoa(id, null) });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

async function editMoa(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Edit MOA', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/moa.php?action=get&id=${id}`, btn);
        sl.setTitle('Edit MOA');
        sl.subtitle = data.moa_number;
        const formHtml = `
            <input type="hidden" name="id" value="${data.id}">
            <div class="form-section">
                <div class="form-grid">
                    ${fieldHtml({ name: 'moa_number', label: 'MOA Number', required: true, value: data.moa_number })}
                    ${fieldHtml({ name: 'partner_agency', label: 'Partner Agency', required: true, value: data.partner_agency })}
                    ${fieldHtml({ name: 'date_signed', label: 'Date Signed', type: 'date', value: data.date_signed })}
                    ${fieldHtml({ name: 'expiration_date', label: 'Expiration', type: 'date', value: data.expiration_date })}
                    ${fieldHtml({ name: 'status', label: 'Status', type: 'select', value: data.status, options: [{value:'pending',label:'Pending'},{value:'active',label:'Active'},{value:'expired',label:'Expired'},{value:'terminated',label:'Terminated'}] })}
                    ${fieldHtml({ name: 'remarks', label: 'Remarks', type: 'textarea', value: data.remarks, fullWidth: true, rows: 3 })}
                </div>
            </div>`;
        sl.openForm('Edit MOA', data.moa_number, formHtml, { showSaveAnother: false });
        document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitSlideOverForm(sl, `${siteUrl}/ajax/moa.php?action=update`, new FormData(e.target), () => refreshMoaTable());
        });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

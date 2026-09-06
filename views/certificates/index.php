<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Certificates</h1><p class="text-[#9CA3AF] text-sm mt-1">Generate and manage certificates</p></div>
    <?php if (Permissions::canGenerateCertificate()): ?>
        <button onclick="openGenerateCertificate()" class="btn-primary"><i class="fas fa-plus mr-1"></i> Generate Certificate</button>
    <?php endif; ?>
</div>

<div id="certificatesTable">
    <div class="data-table-container">
        <?php if (empty($certificates)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-award"></i></div><h3 class="empty-state-title">No certificates found</h3></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Certificate #</th><th>Recipient</th><th>Activity</th><th>Type</th><th>Issued</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($certificates as $i => $c): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Number"><span class="table-code"><?= e($c['certificate_number']) ?></span></td>
                        <td data-label="Recipient"><span class="table-title"><?= e($c['recipient_name']) ?></span></td>
                        <td data-label="Activity"><span class="text-[#D1D5DB]"><?= e($c['activity_title'] ?? '-') ?></span></td>
                        <td data-label="Type"><span class="badge badge-submitted"><?= ucfirst($c['recipient_type']) ?></span></td>
                        <td data-label="Issued"><span class="table-date"><?= formatDate($c['date_issued']) ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($c['status']) ?></td>
                        <td data-label="Actions">
                            <button onclick="viewCertificate(<?= $c['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';

function openGenerateCertificate() {
    const sl = getSlideOver({ size: 'md', title: 'Generate Certificate', subtitle: 'Create certificate' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Certificate Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'recipient_name', label: 'Recipient Name', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'recipient_type', label: 'Type', type: 'select', required: true, options: [{value:'participant',label:'Participant'},{value:'resource_speaker',label:'Resource Speaker'}] })}
                ${fieldHtml({ name: 'activity_id', label: 'Activity', type: 'select', options: <?= json_encode(array_map(fn($a) => ['value'=>$a['id'],'label'=>$a['title']], db()->query("SELECT id, title FROM extension_activities WHERE deleted_at IS NULL ORDER BY title")->fetchAll())) ?> })}
                ${fieldHtml({ name: 'date_issued', label: 'Date Issued', type: 'date', value: '<?= date('Y-m-d') ?>' })}
            </div>
        </div>`;
    sl.openForm('Generate Certificate', 'Create certificate', formHtml, { showSaveAnother: true });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/certificates.php?action=create`, new FormData(e.target), () => location.reload());
    });
}

async function viewCertificate(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Certificate Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/certificates.php?action=get&id=${id}`, btn);
        sl.setTitle(data.certificate_number);
        sl.subtitle = data.recipient_name;
        const content = viewSectionHtml('Certificate Information', [
            viewFieldHtml('Number', `<span class="table-code">${escapeHtml(data.certificate_number)}</span>`),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('Recipient', escapeHtml(data.recipient_name)),
            viewFieldHtml('Type', ucfirst(data.recipient_type)),
            viewFieldHtml('Activity', escapeHtml(data.activity_title || '-')),
            viewFieldHtml('Issued', formatDate(data.date_issued)),
            viewFieldHtml('QR Code', escapeHtml(data.qr_code || '-')),
        ]);
        sl.openView(data.certificate_number, data.recipient_name, content, { size: 'md' });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

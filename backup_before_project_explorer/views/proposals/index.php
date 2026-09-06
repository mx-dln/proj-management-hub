<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Proposals</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage project proposals</p></div>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="proposals">
        <div class="flex-1"><input type="text" name="search" placeholder="Search proposals..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <select name="status" class="filter-select"><option value="">All Status</option><?php foreach(['draft','submitted','under_review','approved','returned','rejected'] as $s): ?><option value="<?= $s ?>"><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div id="proposalsTable">
    <div class="data-table-container">
        <?php if (empty($proposals)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-file-alt"></i></div><h3 class="empty-state-title">No proposals found</h3></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Number</th><th>Title</th><th>Project</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($proposals as $i => $p): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Number"><span class="table-code"><?= e($p['proposal_number']) ?></span></td>
                        <td data-label="Title"><span class="table-title"><?= e($p['title']) ?></span></td>
                        <td data-label="Project"><span class="text-[#D1D5DB]"><?= e($p['project_title'] ?? '-') ?></span></td>
                        <td data-label="Submitted"><span class="table-date"><?= formatDate($p['date_submitted']) ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($p['status']) ?></td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewProposal(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                                <?php if ($_SESSION['role'] === 'faculty' && $p['submitted_by'] == $_SESSION['user_id'] && in_array($p['status'], ['draft','returned'])): ?>
                                    <button onclick="submitProposal(<?= $p['id'] ?>)" class="action-btn action-btn-success" title="Submit"><i class="fas fa-paper-plane text-sm"></i></button>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] === 'admin' && in_array($p['status'], ['submitted','under_review'])): ?>
                                    <button onclick="reviewProposal(<?= $p['id'] ?>, 'approved')" class="action-btn action-btn-success" title="Approve"><i class="fas fa-check text-sm"></i></button>
                                    <button onclick="showReturnModal(<?= $p['id'] ?>)" class="action-btn" title="Return"><i class="fas fa-undo text-sm"></i></button>
                                    <button onclick="reviewProposal(<?= $p['id'] ?>, 'rejected')" class="action-btn action-btn-danger" title="Reject"><i class="fas fa-times text-sm"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<!-- Return Modal -->
<div id="returnModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/60" onclick="closeReturnModal()"></div>
    <div class="relative bg-[#1F2937] rounded-2xl shadow-2xl max-w-md w-full p-6 border border-[#374151]">
        <h3 class="text-lg font-semibold text-[#F9FAFB] mb-4">Return for Revision</h3>
        <form id="returnForm" class="space-y-4">
            <input type="hidden" name="id" id="returnId">
            <div><label class="form-label">Remarks *</label><textarea name="remarks" rows="3" class="form-input" required placeholder="Explain what needs revision..."></textarea></div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeReturnModal()" class="btn-ghost">Cancel</button>
                <button type="submit" class="bg-[#D97706] hover:bg-[#B45309] text-white px-4 py-2 rounded-lg text-sm font-medium">Return</button>
            </div>
        </form>
    </div>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';

document.addEventListener('DOMContentLoaded', function() {
    const returnForm = document.getElementById('returnForm');
    if (returnForm) {
        returnForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = parseInt(document.getElementById('returnId').value);
            const remarks = e.target.querySelector('[name="remarks"]').value;
            const data = await ajax(`${siteUrl}/ajax/proposals.php?action=review`, { id, action: 'returned', remarks });
            if (data.success) { showToast(data.message); closeReturnModal(); setTimeout(() => location.reload(), 1000); }
            else showToast(data.message || 'Failed', 'error');
        });
    }
});

function submitProposal(id) {
    showConfirm('Submit Proposal', 'Submit for review?', () => {
        ajax(`${siteUrl}/ajax/proposals.php?action=submit`, { id })
            .then(data => { if (data.success) { showToast(data.message); setTimeout(() => location.reload(), 1000); } else showToast(data.message, 'error'); });
    });
}

function reviewProposal(id, action) {
    const label = action === 'approved' ? 'Approve' : 'Reject';
    showConfirm(label, label + ' this proposal?', () => {
        ajax(`${siteUrl}/ajax/proposals.php?action=review`, { id, action, remarks: '' })
            .then(data => { if (data.success) { showToast(data.message); setTimeout(() => location.reload(), 1000); } else showToast(data.message, 'error'); });
    }, label);
}

function showReturnModal(id) { document.getElementById('returnId').value = id; document.getElementById('returnModal').classList.remove('hidden'); }
function closeReturnModal() { document.getElementById('returnModal').classList.add('hidden'); }

async function viewProposal(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Proposal Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/proposals.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = data.proposal_number;
        const content = viewSectionHtml('Proposal Information', [
            viewFieldHtml('Number', `<span class="table-code">${escapeHtml(data.proposal_number)}</span>`),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
            viewFieldHtml('Submitted', formatDate(data.date_submitted)),
            viewFieldHtml('Submitted By', escapeHtml(data.submitter_name || '-')),
        ]) + (data.remarks ? viewSectionHtml('Remarks', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.remarks)}</p></div>`]) : '');
        sl.openView(data.title, data.proposal_number, content, { size: 'md' });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

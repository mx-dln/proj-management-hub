<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Proposals</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage project proposals</p></div>
    <?php if ($_SESSION['role'] === 'faculty'): ?>
        <button onclick="openProposalSubmit()" class="btn-primary"><i class="fas fa-plus mr-1"></i> Add Proposal</button>
    <?php endif; ?>
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
            <div class="overflow-x-auto"><table class="data-table proposals-table"><thead><tr><th>Number</th><th>Title</th><th>Project</th><th>File</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($proposals as $i => $p): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Number"><span class="table-code"><?= e($p['proposal_number']) ?></span></td>
                        <td data-label="Title"><span class="table-title"><?= e($p['title']) ?></span></td>
                        <td data-label="Project"><span class="text-[#D1D5DB]"><?= e($p['project_title'] ?? $p['title'] ?? '-') ?></span></td>
                        <td data-label="File"><?php if (!empty($p['attachment'])): ?><a href="<?= SITE_URL ?>/ajax/proposals.php?action=file&amp;id=<?= (int)$p['id'] ?>" target="_blank" rel="noopener" class="action-btn proposal-file-action" title="Open uploaded proposal" aria-label="Open uploaded proposal"><i class="fas fa-file-alt text-sm"></i></a><?php else: ?><span class="text-[#6B7280]">-</span><?php endif; ?></td>
                        <td data-label="Submitted"><span class="table-date"><?= formatDate($p['date_submitted']) ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($p['status']) ?></td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewProposal(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                                <?php if ($_SESSION['role'] === 'faculty' && $p['submitted_by'] == $_SESSION['user_id'] && in_array($p['status'], ['draft','returned'])): ?>
                                    <?php if ($p['status'] === 'returned'): ?>
                                        <button onclick="openProposalRevision(<?= $p['id'] ?>, this)" class="action-btn action-btn-success" title="Revise and Submit"><i class="fas fa-file-upload text-sm"></i></button>
                                    <?php else: ?>
                                        <button onclick="submitProposal(<?= $p['id'] ?>)" class="action-btn action-btn-success" title="Submit"><i class="fas fa-paper-plane text-sm"></i></button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] === 'admin' && in_array($p['status'], ['submitted','under_review'])): ?>
                                    <button onclick="reviewProposal(<?= $p['id'] ?>, 'approved', this)" class="action-btn action-btn-success" title="Approve"><i class="fas fa-check text-sm"></i></button>
                                    <button onclick="showReturnModal(<?= $p['id'] ?>)" class="action-btn" title="Return"><i class="fas fa-undo text-sm"></i></button>
                                    <button onclick="reviewProposal(<?= $p['id'] ?>, 'rejected', this)" class="action-btn action-btn-danger" title="Reject"><i class="fas fa-times text-sm"></i></button>
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
const siteUrl = <?= json_encode(SITE_URL) ?>;

function openProposalSubmit() {
    const sl = getSlideOver({ size: 'lg' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Proposal Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'title', label: 'Project / Proposal Title', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', rows: 3, fullWidth: true })}
                ${fieldHtml({ name: 'college', label: 'College' })}
                ${fieldHtml({ name: 'campus', label: 'Campus', value: 'Cauayan Campus' })}
                ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date' })}
                ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date' })}
                ${fieldHtml({ name: 'budget', label: 'Budget', type: 'number', placeholder: '0.00' })}
                ${fieldHtml({ name: 'funding_source', label: 'Funding Source' })}
                ${fieldHtml({ name: 'location', label: 'Location' })}
                ${fieldHtml({ name: 'beneficiaries', label: 'Beneficiaries' })}
                ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', rows: 3, fullWidth: true })}
                <label class="form-label full-width">Proposal File *
                    <input name="attachment" type="file" accept=".pdf,.doc,.docx" class="form-input" required>
                </label>
            </div>
        </div>`;
    sl.openForm('Add Proposal', 'Send proposal to admin for review', formHtml, { showSaveAnother: false, size: 'lg' });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/proposals.php?action=create_program_proposal`, new FormData(e.target), () => location.reload());
    });
}

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

function reviewProposal(id, action, btn = null) {
    const label = action === 'approved' ? 'Approve' : 'Reject';
    showConfirm(label, label + ' this proposal?', async () => {
        const actionButtons = btn ? btn.closest('td')?.querySelectorAll('button') : [];
        actionButtons?.forEach(button => { button.disabled = true; button.classList.add('opacity-70', 'cursor-not-allowed'); });
        setButtonLoading(btn, true);
        showLoading();
        try {
            const data = await ajax(`${siteUrl}/ajax/proposals.php?action=review`, { id, action, remarks: '' });
            if (!data.success) throw new Error(data.message || `${label} failed`);
            showToast(data.message || `Proposal ${action}`);
            setTimeout(() => location.reload(), 700);
        } catch (error) {
            showToast(error.message || `${label} failed`, 'error');
            setButtonLoading(btn, false);
            actionButtons?.forEach(button => { button.disabled = false; button.classList.remove('opacity-70', 'cursor-not-allowed'); });
            hideLoading();
        }
    }, label);
}

function showReturnModal(id) { document.getElementById('returnId').value = id; document.getElementById('returnModal').classList.remove('hidden'); }
function closeReturnModal() { document.getElementById('returnModal').classList.add('hidden'); }

async function openProposalRevision(id, btn) {
    const data = await fetchWithLoading(`${siteUrl}/ajax/proposals.php?action=get&id=${id}`, btn);
    const sl = getSlideOver({ size: 'md' });
    const formHtml = `<div class="form-section"><div class="form-grid">
        <input type="hidden" name="id" value="${Number(id)}">
        ${fieldHtml({ name: 'remarks', label: 'Revision Remarks / Comments', type: 'textarea', rows: 4, fullWidth: true, required: true, placeholder: 'Describe what you changed before resubmitting.' })}
        <label class="form-label full-width">Revised Proposal File *
            <input name="attachment" type="file" accept=".pdf,.doc,.docx" class="form-input" required>
        </label>
    </div></div>`;
    sl.openForm('Revise Proposal', data.proposal_number || '', formHtml, { showSaveAnother: false, size: 'md' });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/proposals.php?action=revise`, new FormData(e.target), () => location.reload());
    });
}

async function viewProposal(id, btn) {
    const sl = getSlideOver({ size: 'lg', title: 'Proposal Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/proposals.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = data.proposal_number;

        let content = viewSectionHtml('Proposal Information', [
            viewFieldHtml('Number', `<span class="table-code">${escapeHtml(data.proposal_number)}</span>`),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
            viewFieldHtml('Submitted', formatDate(data.date_submitted)),
            viewFieldHtml('Submitted By', escapeHtml(data.submitter_name || '-')),
        ]);
        if (data.attachment) {
            const fileUrl = `${siteUrl}/ajax/proposals.php?action=file&id=${Number(data.id)}`;
            content += `<div class="form-section"><h4 class="form-section-title">Uploaded Proposal File</h4><div class="flex flex-wrap gap-2">
                <a href="${fileUrl}" target="_blank" rel="noopener" class="btn-ghost"><i class="fas fa-external-link-alt mr-1"></i> Open</a>
                <a href="${fileUrl}&download=1" class="btn-primary"><i class="fas fa-download mr-1"></i> Download</a>
            </div></div>`;
        }
        if (Array.isArray(data.history) && data.history.length) {
            const historyRows = data.history.map(item => {
                const label = item.item_type === 'version' ? `Version ${escapeHtml(item.version || '')}` : escapeHtml((item.action || '').replace('_', ' '));
                const note = item.remarks ? `<p class="text-xs text-[#9CA3AF] mt-1">${escapeHtml(item.remarks)}</p>` : '';
                return `<div class="border border-[#374151] rounded-lg p-3 bg-[#111827]">
                    <div class="flex items-center justify-between gap-3">
                        <strong class="text-sm text-[#F9FAFB]">${label}</strong>
                        <span class="text-xs text-[#6B7280]">${formatDate(item.created_at)}</span>
                    </div>
                    <p class="text-xs text-[#D1D5DB] mt-1">${escapeHtml(item.actor_name || '-')}</p>
                    ${note}
                </div>`;
            }).join('');
            content += `<div class="form-section"><h4 class="form-section-title">Submission History</h4><div class="space-y-2">${historyRows}</div></div>`;
        }

        // Parse remarks - might be JSON for program proposals
        let remarksHtml = '';
        try {
            const meta = JSON.parse(data.remarks);
            if (meta && meta.type === 'program_proposal') {
                // Program proposal - show structured data
                content += viewSectionHtml('Program Details', [
                    viewFieldHtml('College', escapeHtml(meta.college || '-')),
                    viewFieldHtml('Campus', escapeHtml(meta.campus || '-')),
                    viewFieldHtml('Budget', formatCurrency(meta.budget)),
                    viewFieldHtml('Funding Source', escapeHtml(meta.funding_source || '-')),
                    viewFieldHtml('Start Date', formatDate(meta.start_date)),
                    viewFieldHtml('End Date', formatDate(meta.end_date)),
                    viewFieldHtml('Location', escapeHtml(meta.location || '-')),
                    viewFieldHtml('Beneficiaries', escapeHtml(meta.beneficiaries || '-')),
                ]);
                content += proposalBudgetCommentsHtml(data);
                if (meta.description) {
                    content += `<div class="form-section"><h4 class="form-section-title">Description</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(meta.description)}</p></div>`;
                }
                if (meta.objectives) {
                    content += `<div class="form-section"><h4 class="form-section-title">Objectives</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(meta.objectives)}</p></div>`;
                }
            } else {
                // Regular remarks
                if (data.remarks) {
                    content += `<div class="form-section"><h4 class="form-section-title">Remarks</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.remarks)}</p></div>`;
                }
            }
        } catch (e) {
            // Not JSON, display as plain text
            if (data.remarks) {
                content += `<div class="form-section"><h4 class="form-section-title">Remarks</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.remarks)}</p></div>`;
            }
        }

        content += proposalCommentsHtml(data);
        sl.openView(data.title, data.proposal_number, content, { size: 'lg' });
        bindProposalCommentForm(id, sl);
        bindProposalBudgetCommentForm(id, sl);
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

function proposalBudgetCommentsHtml(data) {
    const comments = Array.isArray(data.budget_comments) ? data.budget_comments : [];
    const rows = comments.length ? comments.map(comment => {
        const displayName = (comment.faculty_name || '').trim() || comment.username || '-';
        const role = comment.role ? comment.role.charAt(0).toUpperCase() + comment.role.slice(1) : '';
        return `<div class="border border-[#374151] rounded-lg p-3 bg-[#111827]">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <strong class="text-sm text-[#F9FAFB]">${escapeHtml(displayName)}</strong>
                    <span class="text-xs text-[#6B7280] ml-2">${escapeHtml(role)}</span>
                </div>
                <span class="text-xs text-[#6B7280]">${formatDate(comment.created_at)}</span>
            </div>
            <p class="text-sm text-[#D1D5DB] mt-2 whitespace-pre-wrap">${escapeHtml(comment.comment || '')}</p>
        </div>`;
    }).join('') : `<div class="text-sm text-[#6B7280] border border-dashed border-[#374151] rounded-lg p-4 text-center">No budget comments yet</div>`;

    return `<div class="form-section">
        <h4 class="form-section-title">Budget Comments / Remarks</h4>
        <div class="space-y-2 mb-3">${rows}</div>
        <form id="proposalBudgetCommentForm" class="space-y-3">
            <textarea name="comment" rows="3" class="form-input" required placeholder="Add budget remarks or comments..."></textarea>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary"><i class="fas fa-comment-dollar mr-1"></i> Add Budget Comment</button>
            </div>
        </form>
    </div>`;
}

function proposalCommentsHtml(data) {
    const comments = Array.isArray(data.comments) ? data.comments : [];
    const rows = comments.length ? comments.map(comment => {
        const displayName = (comment.faculty_name || '').trim() || comment.username || '-';
        const role = comment.role ? comment.role.charAt(0).toUpperCase() + comment.role.slice(1) : '';
        return `<div class="border border-[#374151] rounded-lg p-3 bg-[#111827]">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <strong class="text-sm text-[#F9FAFB]">${escapeHtml(displayName)}</strong>
                    <span class="text-xs text-[#6B7280] ml-2">${escapeHtml(role)}</span>
                </div>
                <span class="text-xs text-[#6B7280]">${formatDate(comment.created_at)}</span>
            </div>
            <p class="text-sm text-[#D1D5DB] mt-2 whitespace-pre-wrap">${escapeHtml(comment.comment || '')}</p>
        </div>`;
    }).join('') : `<div class="text-sm text-[#6B7280] border border-dashed border-[#374151] rounded-lg p-4 text-center">No comments yet</div>`;

    return `<div class="form-section">
        <h4 class="form-section-title">Remarks & Comments</h4>
        <div class="space-y-2 mb-3">${rows}</div>
        <form id="proposalCommentForm" class="space-y-3">
            <textarea name="comment" rows="3" class="form-input" required placeholder="Add remarks or comments..."></textarea>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary"><i class="fas fa-comment mr-1"></i> Add Comment</button>
            </div>
        </form>
    </div>`;
}

function bindProposalCommentForm(id, sl) {
    const form = document.getElementById('proposalCommentForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const comment = e.target.comment.value.trim();
        if (!comment) return;
        const result = await ajax(`${siteUrl}/ajax/proposals.php?action=add_comment`, { id, comment });
        if (!result.success) {
            showToast(result.message || 'Failed to add comment', 'error');
            return;
        }
        showToast(result.message || 'Comment added');
        sl.close(true);
        viewProposal(id, null);
    });
}

function bindProposalBudgetCommentForm(id, sl) {
    const form = document.getElementById('proposalBudgetCommentForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const comment = e.target.comment.value.trim();
        if (!comment) return;
        const result = await ajax(`${siteUrl}/ajax/proposals.php?action=add_budget_comment`, { id, comment });
        if (!result.success) {
            showToast(result.message || 'Failed to add budget comment', 'error');
            return;
        }
        showToast(result.message || 'Budget comment added');
        sl.close(true);
        viewProposal(id, null);
    });
}
</script>

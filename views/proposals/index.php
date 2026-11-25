<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Proposals</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage project proposals</p></div>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <button onclick="openProposalSubmit(true)" class="btn-primary"><i class="fas fa-camera mr-1"></i> Scan Proposal</button>
    <?php elseif ($_SESSION['role'] === 'faculty'): ?>
        <button onclick="openProposalSubmit(false)" class="btn-primary"><i class="fas fa-plus mr-1"></i> Add Proposal</button>
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
            <div class="overflow-x-auto"><table class="data-table proposals-table"><thead><tr><th>Number</th><th>Title</th><th>Proponent</th><th>Project</th><th>File</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($proposals as $i => $p): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Number">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="table-code"><?= e($p['proposal_number']) ?></span>
                                <span class="badge <?= ($p['version'] ?? 1) > 1 ? 'bg-purple-900/60 text-purple-200 border border-purple-500/40 text-[11px]' : 'badge-submitted text-[11px]' ?>">v<?= (int)($p['version'] ?? 1) ?><?= ($p['version'] ?? 1) > 1 ? ' (Rev)' : '' ?></span>
                            </div>
                        </td>
                        <td data-label="Title"><span class="table-title"><?= e($p['title']) ?></span></td>
                        <td data-label="Proponent"><span class="text-[#D1D5DB]"><?= e($p['proponent'] ?? '-') ?></span></td>
                        <td data-label="Project"><span class="text-[#D1D5DB]"><?= e($p['project_title'] ?? $p['title'] ?? '-') ?></span></td>
                        <td data-label="File"><?php if (!empty($p['attachment'])): ?><div class="flex items-center justify-center gap-2"><button type="button" onclick="previewProposalFile(<?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($p['title'] ?? 'Proposal'), ENT_QUOTES, 'UTF-8') ?>)" class="action-btn proposal-file-action" title="Preview uploaded proposal" aria-label="Preview uploaded proposal"><i class="fas fa-file-alt text-sm"></i></button><span class="file-extension-pill"><?= e(strtoupper(pathinfo($p['attachment'], PATHINFO_EXTENSION))) ?></span></div><?php else: ?><span class="text-[#6B7280]">-</span><?php endif; ?></td>
                        <td data-label="Submitted"><span class="table-date"><?= formatDate($p['date_submitted']) ?></span></td>
                        <td data-label="Status">
                            <?= getStatusBadge($p['status']) ?>
                            <?php if ($p['status'] === 'returned'): ?>
                                <span class="text-[11px] text-amber-400 block mt-1 font-medium"><i class="fas fa-undo mr-1"></i>Revision required</span>
                            <?php elseif ($p['status'] === 'rejected'): ?>
                                <span class="text-[11px] text-red-400 block mt-1 font-medium"><i class="fas fa-exclamation-circle mr-1"></i>Reupload open</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Actions">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewProposal(<?= $p['id'] ?>, this)" class="action-btn" data-loading title="View Details & Timeline"><i class="fas fa-eye text-sm"></i></button>
                                <?php if ($_SESSION['role'] === 'faculty' && $p['submitted_by'] == $_SESSION['user_id'] && in_array($p['status'], ['draft','returned','rejected'])): ?>
                                    <?php if (in_array($p['status'], ['returned', 'rejected'])): ?>
                                        <button onclick="openProposalRevision(<?= $p['id'] ?>, this)" class="action-btn action-btn-warning" title="Revise and Resubmit Proposal"><i class="fas fa-file-upload text-sm"></i></button>
                                    <?php else: ?>
                                        <button onclick="submitProposal(<?= $p['id'] ?>)" class="action-btn action-btn-success" title="Submit"><i class="fas fa-paper-plane text-sm"></i></button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] === 'admin' && in_array($p['status'], ['submitted','under_review'])): ?>
                                    <button onclick="reviewProposal(<?= $p['id'] ?>, 'approved', this)" class="action-btn action-btn-success" title="Approve"><i class="fas fa-check text-sm"></i></button>
                                    <button onclick="showReturnModal(<?= $p['id'] ?>)" class="action-btn" title="Return for Revision"><i class="fas fa-undo text-sm"></i></button>
                                    <button onclick="showRejectModal(<?= $p['id'] ?>)" class="action-btn action-btn-danger" title="Reject Proposal"><i class="fas fa-times text-sm"></i></button>
                                <?php endif; ?>
                                <?php if (Permissions::canDeleteProposal()): ?>
                                    <button onclick="deleteProposal(<?= $p['id'] ?>, this)" class="action-btn action-btn-danger" title="Delete" aria-label="Delete"><i class="fas fa-trash text-sm"></i></button>
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
        <h3 class="text-lg font-semibold text-amber-400 mb-4"><i class="fas fa-undo mr-2"></i>Return for Revision</h3>
        <form id="returnForm" class="space-y-4">
            <input type="hidden" name="id" id="returnId">
            <div>
                <label class="form-label">Revision Remarks / Instructions *</label>
                <textarea name="remarks" rows="3" class="form-input" required placeholder="Explain clearly what needs to be revised or corrected by the faculty..."></textarea>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeReturnModal()" class="btn-ghost">Cancel</button>
                <button type="submit" class="bg-[#D97706] hover:bg-[#B45309] text-white px-4 py-2 rounded-lg text-sm font-medium">Return to Faculty</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/60" onclick="closeRejectModal()"></div>
    <div class="relative bg-[#1F2937] rounded-2xl shadow-2xl max-w-md w-full p-6 border border-[#374151]">
        <h3 class="text-lg font-semibold text-red-400 mb-4"><i class="fas fa-times-circle mr-2"></i>Reject Proposal</h3>
        <form id="rejectForm" class="space-y-4">
            <input type="hidden" name="id" id="rejectId">
            <div>
                <label class="form-label">Reason for Rejection / Required Action *</label>
                <textarea name="remarks" rows="3" class="form-input" required placeholder="Explain why this proposal is rejected so the faculty can address it and re-upload..."></textarea>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeRejectModal()" class="btn-ghost">Cancel</button>
                <button type="submit" class="bg-[#DC2626] hover:bg-[#B91C1C] text-white px-4 py-2 rounded-lg text-sm font-medium">Reject Proposal</button>
            </div>
        </form>
    </div>
</div>

<script>
const siteUrl = <?= json_encode(SITE_URL) ?>;

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' +
           d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true });
}

async function previewProposalFile(id, title = 'Proposal') {
    const fileUrl = `${siteUrl}/ajax/proposals.php?action=file&id=${Number(id)}`;
    const data = await fetchWithLoading(`${siteUrl}/ajax/proposals.php?action=get&id=${Number(id)}`);
    const urls = Array.from({length: Number(data.attachment_count || 1)}, (_, index) => `${fileUrl}&attachment_index=${index}`);
    openDocumentGallery({
        urls,
        downloadUrl: `${fileUrl}&download=1`,
        title,
        subtitle: 'Uploaded proposal file',
        fileName: `${title || 'proposal'}`
    });
}

function openProposalSubmit(adminScan = false) {
    const sl = getSlideOver({ size: 'md' });
    const scanMarkup = `
                <div class="proposal-scan-panel full-width">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div><p class="form-label mb-0">Scan with Camera</p><p class="text-xs text-[#9CA3AF] mt-1">Capture one or more pages when no scanned file is available.</p></div>
                        <button type="button" id="proposal-scan-start" class="btn-ghost"><i class="fas fa-camera mr-1" aria-hidden="true"></i> Open Camera</button>
                    </div>
                    <div id="proposal-scan-panel" class="hidden mt-3 space-y-3">
                        <video id="proposal-scan-video" class="w-full rounded bg-black" playsinline autoplay muted style="max-height:320px;object-fit:contain"></video>
                        <canvas id="proposal-scan-canvas" class="hidden"></canvas>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="proposal-scan-capture" class="btn-primary"><i class="fas fa-circle-dot mr-1" aria-hidden="true"></i> Capture Page</button>
                            <button type="button" id="proposal-scan-stop" class="btn-ghost">Close Camera</button>
                        </div>
                        <p id="proposal-scan-status" class="text-xs text-[#9CA3AF]" role="status"></p>
                    </div>
                </div>`;
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Scanned Proposal Submission</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'title', label: 'Proposal Title', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'project_program', label: 'Project / Program', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'project_leader', label: 'Project Leader / Head', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'proponent', label: 'Proponent', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'date_submitted', label: 'Date Submitted', type: 'date', value: new Date().toISOString().slice(0, 10), required: true, fullWidth: true })}
                <label class="form-label full-width">Scanned Proposal File *
                    <input name="attachments[]" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.csv,.xls,.xlsx,.xlsm" class="form-input" multiple required>
                </label>
                ${scanMarkup}
                <p class="full-width text-xs text-[#9CA3AF]">Upload the scanned/signed proposal document. Other details will be reviewed from the file by Admin.</p>
            </div>
        </div>`;
    sl.openForm(adminScan ? 'Scan Proposal' : 'Add Proposal', adminScan ? 'Capture proposal pages for review' : 'Upload scanned proposal for admin review', formHtml, { showSaveAnother: false, size: 'md' });
    const form = document.getElementById('slideoverForm');
    const fileInput = form.querySelector('input[name="attachments[]"]');
    bindProposalScanner(form, fileInput, sl);
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/proposals.php?action=create_program_proposal`, new FormData(e.target), () => location.reload());
    });
}

function bindProposalScanner(form, fileInput, slideover) {
    const startButton = form.querySelector('#proposal-scan-start');
    const stopButton = form.querySelector('#proposal-scan-stop');
    const captureButton = form.querySelector('#proposal-scan-capture');
    const panel = form.querySelector('#proposal-scan-panel');
    const video = form.querySelector('#proposal-scan-video');
    const canvas = form.querySelector('#proposal-scan-canvas');
    const status = form.querySelector('#proposal-scan-status');
    let stream = null;

    const stopScan = () => {
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        panel.classList.add('hidden');
    };
    slideover.onClose = stopScan;

    startButton.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            showToast('Camera scanning is not supported in this browser.', 'error');
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            video.srcObject = stream;
            panel.classList.remove('hidden');
            status.textContent = 'Position the proposal page clearly, then capture.';
        } catch (error) {
            showToast('Camera access was blocked or unavailable.', 'error');
        }
    });

    stopButton.addEventListener('click', stopScan);
    captureButton.addEventListener('click', async () => {
        if (!stream || !video.videoWidth) {
            showToast('Camera is not ready yet.', 'error');
            return;
        }
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.92));
        if (!blob) {
            showToast('Could not capture the proposal page.', 'error');
            return;
        }
        const fileName = `proposal-scan-${new Date().toISOString().replace(/[:.]/g, '-')}.jpg`;
        const transfer = new DataTransfer();
        Array.from(fileInput.files || []).forEach(existing => transfer.items.add(existing));
        transfer.items.add(new File([blob], fileName, { type: 'image/jpeg' }));
        fileInput.files = transfer.files;
        status.textContent = `Captured ${fileName}. Capture another page or save the proposal.`;
        showToast('Proposal page captured.');
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
    const rejectForm = document.getElementById('rejectForm');
    if (rejectForm) {
        rejectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = parseInt(document.getElementById('rejectId').value);
            const remarks = e.target.querySelector('[name="remarks"]').value;
            const data = await ajax(`${siteUrl}/ajax/proposals.php?action=review`, { id, action: 'rejected', remarks });
            if (data.success) { showToast(data.message); closeRejectModal(); setTimeout(() => location.reload(), 1000); }
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
    if (action === 'rejected') {
        showRejectModal(id);
        return;
    }
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
function showRejectModal(id) { document.getElementById('rejectId').value = id; document.getElementById('rejectModal').classList.remove('hidden'); }
function closeRejectModal() { document.getElementById('rejectModal').classList.add('hidden'); }

async function previewProposalVersionFile(id, version, title = 'Proposal Version') {
    const fileUrl = `${siteUrl}/ajax/proposals.php?action=file&id=${Number(id)}&version=${Number(version)}`;
    openDocumentGallery({
        urls: [fileUrl],
        downloadUrl: `${fileUrl}&download=1`,
        title: `${title}`,
        subtitle: `Proposal file (Version ${version})`,
        fileName: `${title || 'proposal'}-v${version}`
    });
}

async function openProposalRevision(id, btn) {
    const data = await fetchWithLoading(`${siteUrl}/ajax/proposals.php?action=get&id=${id}`, btn);
    const sl = getSlideOver({ size: 'md' });
    const currentVer = Number(data.version) || 1;
    const nextVer = currentVer + 1;

    let returnRemarksBanner = '';
    const reviewRemarks = data.latest_review?.remarks || (data.status !== 'submitted' && data.remarks && !data.remarks.startsWith('{') ? data.remarks : '');
    if (reviewRemarks) {
        const isRej = data.status === 'rejected';
        returnRemarksBanner = `
            <div class="full-width p-4 ${isRej ? 'bg-red-950/40 border-red-800/60' : 'bg-amber-950/40 border-amber-800/60'} border rounded-xl mb-3">
                <div class="flex items-center gap-2 ${isRej ? 'text-red-400' : 'text-amber-400'} font-semibold text-xs uppercase tracking-wider mb-1">
                    <i class="fas ${isRej ? 'fa-times-circle' : 'fa-undo'}"></i>
                    <span>${isRej ? 'Rejection Reason / Required Actions' : 'Admin Return Remarks'}</span>
                </div>
                <p class="text-sm text-gray-100 whitespace-pre-wrap mt-1">${escapeHtml(reviewRemarks)}</p>
                ${data.latest_review?.reviewer_name ? `<span class="text-xs text-[#9CA3AF] mt-2 block">Feedback by ${escapeHtml(data.latest_review.reviewer_name)} on ${formatDateTime(data.latest_review.performed_at)}</span>` : ''}
            </div>
        `;
    }

    const scanMarkup = `
        <div class="proposal-scan-panel full-width">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div><p class="form-label mb-0">Or Scan Pages with Camera</p><p class="text-xs text-[#9CA3AF] mt-0.5">Capture revised pages if scanning from paper.</p></div>
                <button type="button" id="proposal-scan-start" class="btn-ghost"><i class="fas fa-camera mr-1" aria-hidden="true"></i> Open Camera</button>
            </div>
            <div id="proposal-scan-panel" class="hidden mt-3 space-y-3">
                <video id="proposal-scan-video" class="w-full rounded bg-black" playsinline autoplay muted style="max-height:300px;object-fit:contain"></video>
                <canvas id="proposal-scan-canvas" class="hidden"></canvas>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="proposal-scan-capture" class="btn-primary"><i class="fas fa-circle-dot mr-1" aria-hidden="true"></i> Capture Page</button>
                    <button type="button" id="proposal-scan-stop" class="btn-ghost">Close Camera</button>
                </div>
                <p id="proposal-scan-status" class="text-xs text-[#9CA3AF]" role="status"></p>
            </div>
        </div>`;

    const formHtml = `
        <div class="form-section">
            <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-[#374151]">
                <div>
                    <h4 class="form-section-title mb-0">Re-upload Revised Proposal</h4>
                    <p class="text-xs text-[#9CA3AF] mt-0.5">Resubmitting as <span class="text-purple-300 font-semibold">Version ${nextVer}</span> (Previous: v${currentVer})</p>
                </div>
                <span class="badge bg-purple-900/60 text-purple-200 border border-purple-500/40 text-xs">v${nextVer}</span>
            </div>
            <div class="form-grid">
                <input type="hidden" name="id" value="${Number(id)}">
                ${returnRemarksBanner}
                ${fieldHtml({ name: 'remarks', label: 'Revision Notes / What was changed *', type: 'textarea', rows: 3, fullWidth: true, required: true, placeholder: 'Explain what corrections or updates were made in this revised file...' })}
                <label class="form-label full-width">Revised Proposal File *
                    <input name="attachments[]" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.csv,.xls,.xlsx,.xlsm" class="form-input" multiple required>
                </label>
                ${scanMarkup}
                <p class="full-width text-xs text-[#9CA3AF]">Upload the revised proposal document (PDF, Word, or images). Upon submitting, it will immediately go back to Admin for review as Version ${nextVer}.</p>
            </div>
        </div>`;

    sl.openForm(`Revise Proposal (v${nextVer})`, data.proposal_number || '', formHtml, { showSaveAnother: false, size: 'md', submitText: `Submit Revision v${nextVer}` });
    const form = document.getElementById('slideoverForm');
    const fileInput = form.querySelector('input[name="attachments[]"]');
    bindProposalScanner(form, fileInput, sl);
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/proposals.php?action=revise`, new FormData(e.target), () => location.reload());
    });
}

async function viewProposal(id, btn) {
    const sl = getSlideOver({ size: 'lg', title: 'Proposal Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/proposals.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = `${data.proposal_number} · Version ${data.version || 1}`;

        let content = '';

        // Top Return / Rejection Banner
        const reviewRemarks = data.latest_review?.remarks || (data.status !== 'submitted' && data.remarks && !data.remarks.startsWith('{') ? data.remarks : '');
        if (['returned', 'rejected'].includes(data.status)) {
            const isRej = data.status === 'rejected';
            const borderColor = isRej ? 'border-red-500/50 bg-red-950/25' : 'border-amber-500/50 bg-amber-950/25';
            const textColor = isRej ? 'text-red-400' : 'text-amber-400';
            const icon = isRej ? 'fa-times-circle' : 'fa-undo';
            const heading = isRej ? 'Proposal Rejected' : 'Proposal Returned for Revision';

            content += `
                <div class="border-2 ${borderColor} rounded-xl p-4 mb-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2 ${textColor} font-bold text-sm">
                            <i class="fas ${icon} text-base"></i>
                            <span>${heading}</span>
                        </div>
                        ${data.canRevise ? `<button type="button" onclick="openProposalRevision(${id}, this)" class="btn-primary text-xs py-1.5 px-3"><i class="fas fa-file-upload mr-1"></i> Re-upload Revised File</button>` : ''}
                    </div>
                    <div class="bg-[#111827] border border-[#374151] rounded-lg p-3 text-sm">
                        <span class="text-xs ${textColor} font-semibold uppercase tracking-wider block mb-1">
                            <i class="fas fa-comment-dots mr-1"></i> Admin Feedback / Required Changes:
                        </span>
                        <p class="text-gray-100 whitespace-pre-wrap">${escapeHtml(reviewRemarks || 'Please review required changes and submit a revised version.')}</p>
                        ${data.latest_review?.reviewer_name ? `<span class="text-xs text-[#9CA3AF] mt-2 block">Feedback given by ${escapeHtml(data.latest_review.reviewer_name)} on ${formatDateTime(data.latest_review.performed_at)}</span>` : ''}
                    </div>
                </div>
            `;
        }

        // Proposal Information
        content += viewSectionHtml('Proposal Information', [
            viewFieldHtml('Number', `<span class="table-code">${escapeHtml(data.proposal_number)}</span> <span class="badge ${data.version > 1 ? 'bg-purple-900/60 text-purple-200 border border-purple-500/40 text-[11px]' : 'badge-submitted text-[11px]'} ml-1">v${data.version || 1}</span>`),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('Project / Program', escapeHtml(data.project_title || '-')),
            viewFieldHtml('Proponent', escapeHtml(data.proponent || '-')),
            viewFieldHtml('Submitted', formatDate(data.date_submitted)),
            viewFieldHtml('Submitted By', escapeHtml(data.submitter_name || '-')),
        ]);

        // Latest file download/preview
        if (data.attachment) {
            const fileUrl = `${siteUrl}/ajax/proposals.php?action=file&id=${Number(data.id)}`;
            content += `<div class="form-section"><h4 class="form-section-title">Latest Uploaded Proposal File (v${data.version || 1})</h4><div class="flex flex-wrap gap-2">
                <button type="button" onclick="previewProposalFile(${Number(data.id)}, this.dataset.title)" data-title="${escapeHtml(data.title || 'Proposal')}" class="btn-ghost"><i class="fas fa-eye mr-1"></i> Preview Latest</button>
                <a href="${fileUrl}&download=1" class="btn-primary"><i class="fas fa-download mr-1"></i> Download Latest</a>
            </div></div>`;
        }

        // Proposal Timeline & Historical Versions
        if (Array.isArray(data.history) && data.history.length) {
            const timelineItems = data.history.map((item, idx) => {
                const isVer = item.item_type === 'version';
                let badgeClass = 'bg-[#1E3A8A] text-[#BFDBFE]';
                let icon = '<i class="fas fa-paper-plane text-blue-400"></i>';
                let title = `Version ${item.version || 1} Submission`;
                if (isVer) {
                    icon = '<i class="fas fa-file-arrow-up text-purple-400"></i>';
                    badgeClass = 'bg-purple-900/50 text-purple-200 border border-purple-500/30';
                    title = (Number(item.version) > 1) ? `Version ${item.version} Submission (Revised Proposal)` : 'Version 1 Initial Submission';
                } else if (item.action === 'approved') {
                    icon = '<i class="fas fa-check-circle text-green-400"></i>';
                    badgeClass = 'bg-green-900/50 text-green-200 border border-green-500/30';
                    title = 'Approved by Admin';
                } else if (item.action === 'returned') {
                    icon = '<i class="fas fa-undo text-amber-400"></i>';
                    badgeClass = 'bg-amber-900/50 text-amber-200 border border-amber-500/30';
                    title = 'Returned for Revision';
                } else if (item.action === 'rejected') {
                    icon = '<i class="fas fa-times-circle text-red-400"></i>';
                    badgeClass = 'bg-red-900/50 text-red-200 border border-red-500/30';
                    title = 'Rejected by Admin';
                }

                let fileBtn = '';
                if (isVer && (item.attachment || item.version)) {
                    const verNum = Number(item.version) || 1;
                    fileBtn = `
                        <div class="flex flex-wrap items-center gap-2 mt-2 pt-2 border-t border-[#374151]/50">
                            <button type="button" onclick="previewProposalVersionFile(${Number(data.id)}, ${verNum}, '${escapeHtml(data.title || 'Proposal')} v${verNum}')" class="btn-ghost text-xs py-1 px-2.5">
                                <i class="fas fa-eye mr-1"></i> Preview v${verNum} File
                            </button>
                            <a href="${siteUrl}/ajax/proposals.php?action=file&id=${Number(data.id)}&version=${verNum}&download=1" class="btn-primary text-xs py-1 px-2.5">
                                <i class="fas fa-download mr-1"></i> Download v${verNum}
                            </a>
                        </div>
                    `;
                }

                return `
                    <div class="relative pl-8 pb-4 last:pb-0">
                        <div class="absolute left-0 top-1 w-6 h-6 rounded-full bg-[#1F2937] border border-[#374151] flex items-center justify-center text-xs">
                            ${icon}
                        </div>
                        ${idx < data.history.length - 1 ? '<div class="absolute left-3 top-7 bottom-0 w-0.5 bg-[#374151]"></div>' : ''}
                        <div class="bg-[#111827] border border-[#374151] rounded-xl p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="badge ${badgeClass} text-xs font-semibold">${title}</span>
                                    <span class="text-xs text-[#9CA3AF]">${escapeHtml(item.actor_name || '-')}</span>
                                </div>
                                <span class="text-xs text-[#6B7280]"><i class="fas fa-clock mr-1"></i>${formatDateTime(item.created_at)}</span>
                            </div>
                            ${item.remarks ? `<p class="text-xs text-[#D1D5DB] mt-2 whitespace-pre-wrap bg-[#1F2937]/50 p-2 rounded border border-[#374151]/40">${escapeHtml(item.remarks)}</p>` : ''}
                            ${fileBtn}
                        </div>
                    </div>
                `;
            }).join('');
            content += `
                <div class="form-section">
                    <h4 class="form-section-title"><i class="fas fa-timeline mr-1.5 text-purple-400"></i> Proposal Timeline & Submission History</h4>
                    <div class="relative pt-1">${timelineItems}</div>
                </div>
            `;
        }

        // Parse remarks - might be JSON for program proposals
        try {
            const meta = JSON.parse(data.remarks);
            if (meta && meta.type === 'program_proposal') {
                const detailFields = [
                    viewFieldHtml('Project / Program', escapeHtml(meta.project_program || data.project_title || '-')),
                    viewFieldHtml('Project Leader / Head', escapeHtml(meta.project_leader || '-')),
                    viewFieldHtml('Submission Type', escapeHtml(meta.submission_mode || 'Scanned proposal upload')),
                    viewFieldHtml('Date Submitted', formatDate(meta.date_submitted || data.date_submitted)),
                ];
                if (meta.college) detailFields.push(viewFieldHtml('College', escapeHtml(meta.college)));
                if (meta.campus) detailFields.push(viewFieldHtml('Campus', escapeHtml(meta.campus)));
                if (meta.budget) detailFields.push(viewFieldHtml('Budget', formatCurrency(meta.budget)));
                if (meta.funding_source) detailFields.push(viewFieldHtml('Funding Source', escapeHtml(meta.funding_source)));
                if (meta.start_date) detailFields.push(viewFieldHtml('Start Date', formatDate(meta.start_date)));
                if (meta.end_date) detailFields.push(viewFieldHtml('End Date', formatDate(meta.end_date)));
                if (meta.location) detailFields.push(viewFieldHtml('Location', escapeHtml(meta.location)));
                if (meta.beneficiaries) detailFields.push(viewFieldHtml('Beneficiaries', escapeHtml(meta.beneficiaries)));
                content += viewSectionHtml('Proposal Upload Details', detailFields);
                if (meta.description) {
                    content += `<div class="form-section"><h4 class="form-section-title">Description</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(meta.description)}</p></div>`;
                }
                if (meta.objectives) {
                    content += `<div class="form-section"><h4 class="form-section-title">Objectives</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(meta.objectives)}</p></div>`;
                }
            } else if (data.remarks && !['returned','rejected'].includes(data.status)) {
                content += `<div class="form-section"><h4 class="form-section-title">Remarks</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.remarks)}</p></div>`;
            }
        } catch (e) {
            if (data.remarks && !['returned','rejected'].includes(data.status)) {
                content += `<div class="form-section"><h4 class="form-section-title">Remarks</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.remarks)}</p></div>`;
            }
        }

        // Budget Comments - ALWAYS visible so user can see comment sa budget
        content += proposalBudgetCommentsHtml(data);

        // General Remarks & Comments
        content += proposalCommentsHtml(data);

        sl.openView(data.title, `${data.proposal_number} (v${data.version || 1})`, content, { size: 'lg' });
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
                    <span class="text-xs text-[#6B7280] ml-2 font-medium">${escapeHtml(role)}</span>
                </div>
                <span class="text-xs text-[#6B7280]">${formatDateTime(comment.created_at)}</span>
            </div>
            <p class="text-sm text-[#D1D5DB] mt-2 whitespace-pre-wrap">${escapeHtml(comment.comment || '')}</p>
        </div>`;
    }).join('') : `<div class="text-sm text-[#6B7280] border border-dashed border-[#374151] rounded-lg p-4 text-center">No budget comments yet. Faculty and Admin can discuss funding and budget breakdown here.</div>`;

    return `<div class="form-section">
        <div class="flex items-center justify-between mb-1">
            <h4 class="form-section-title mb-0"><i class="fas fa-comment-dollar mr-1.5 text-amber-400"></i> Budget Remarks & Comments</h4>
            ${comments.length ? `<span class="badge bg-amber-950/60 text-amber-300 border border-amber-600/40 text-xs">${comments.length} Comment${comments.length > 1 ? 's' : ''}</span>` : ''}
        </div>
        <p class="text-xs text-[#9CA3AF] mb-3">Faculty proponents and administrators can review and discuss budget allocations and requirements here.</p>
        <div class="space-y-2 mb-3">${rows}</div>
        <form id="proposalBudgetCommentForm" class="space-y-3">
            <textarea name="comment" rows="2" class="form-input" required placeholder="Add a budget comment or question on allocations..."></textarea>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary text-xs py-1.5 px-3"><i class="fas fa-comment-dollar mr-1"></i> Post Budget Comment</button>
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

window.deleteProposal = async function(id, btn) {
    if (!confirm('Are you sure you want to delete this proposal?')) return;
    setButtonLoading(btn, true);
    try {
        const response = await fetch(`${siteUrl}/ajax/proposals.php?action=delete`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id})
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Delete failed.');
        showToast(data.message || 'Proposal deleted.');
        setTimeout(() => location.reload(), 700);
    } catch (error) {
        showToast(error.message || 'Could not delete proposal.', 'error');
    } finally {
        setButtonLoading(btn, false);
    }
};
</script>

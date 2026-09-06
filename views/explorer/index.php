<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-[#F9FAFB]">Project Explorer</h1>
        <p class="text-[#9CA3AF] text-sm mt-1">Browse and manage the extension project hierarchy</p>
    </div>
    <div class="flex gap-2">
        <?php if ($_SESSION['role'] === 'faculty'): ?>
            <button onclick="openSubmitProposal()" class="btn-ghost"><i class="fas fa-file-alt mr-1"></i> Submit Proposal</button>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <button onclick="openCreateProgram()" class="btn-primary"><i class="fas fa-plus mr-1"></i> New Program</button>
        <?php endif; ?>
    </div>
</div>

<!-- Search -->
<div class="mb-4">
    <div class="relative">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#6B7280] text-sm"></i>
        <input type="text" id="explorer-search" placeholder="Search programs, projects..." class="filter-input pl-10">
    </div>
</div>

<!-- Explorer Layout -->
<div class="explorer-layout">
    <div class="explorer-tree" id="explorer-tree">
        <div class="p-4 text-center text-[#6B7280] text-sm">Loading...</div>
    </div>
    <div class="explorer-detail" id="explorer-detail">
        <div class="explorer-empty">
            <div class="explorer-empty-icon"><i class="fas fa-layer-group"></i></div>
            <h3 class="explorer-empty-title">Select an item</h3>
            <p class="explorer-empty-text">Choose a program or project from the tree to view details, or select Components/Activities to manage them.</p>
        </div>
    </div>
</div>

<script>
const userRole = '<?= $_SESSION['role'] ?? '' ?>';
const siteUrl = '<?= SITE_URL ?>';
window.activityTypes = <?= json_encode(array_map(fn($t) => ['value'=>$t['id'],'label'=>$t['name']], db()->query("SELECT id, name FROM activity_types WHERE is_active = 1")->fetchAll())) ?>;

function openCreateProgram() {
    const sl = getSlideOver({ size: 'md', title: 'New Program', subtitle: 'Create extension program' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Program Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'title', label: 'Program Title', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'college', label: 'College' })}
                ${fieldHtml({ name: 'campus', label: 'Campus', value: 'Cauayan Campus' })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Timeline & Budget</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date' })}
                ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date' })}
                ${fieldHtml({ name: 'budget_allocation', label: 'Budget', type: 'number' })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Objectives</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', fullWidth: true, rows: 3 })}
            </div>
        </div>`;
    sl.openForm('New Program', 'Create program', formHtml, { showSaveAnother: true });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/programs.php?action=create`, new FormData(e.target), () => explorer.loadTree());
    });
}

function openSubmitProposal() {
    const sl = getSlideOver({ size: 'lg', title: 'Submit Proposal', subtitle: 'Propose a new extension program' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Proposal Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'title', label: 'Program/Project Title', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', fullWidth: true, rows: 3, placeholder: 'Describe the proposed extension program or project' })}
                ${fieldHtml({ name: 'college', label: 'College', placeholder: 'e.g., College of Agriculture' })}
                ${fieldHtml({ name: 'campus', label: 'Campus', value: 'Cauayan Campus' })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Timeline & Budget</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'start_date', label: 'Proposed Start Date', type: 'date' })}
                ${fieldHtml({ name: 'end_date', label: 'Proposed End Date', type: 'date' })}
                ${fieldHtml({ name: 'budget', label: 'Estimated Budget', type: 'number' })}
                ${fieldHtml({ name: 'funding_source', label: 'Funding Source', placeholder: 'e.g., University Fund, CHED, DOST' })}
            </div>
        </div>
        <div class="form-section">
            <h4 class="form-section-title">Details</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', fullWidth: true, rows: 3 })}
                ${fieldHtml({ name: 'beneficiaries', label: 'Target Beneficiaries', fullWidth: true, placeholder: 'e.g., Farmers, Senior Citizens, OSY' })}
                ${fieldHtml({ name: 'location', label: 'Target Location', fullWidth: true })}
                ${fieldHtml({ name: 'remarks', label: 'Additional Remarks', type: 'textarea', fullWidth: true, rows: 2 })}
            </div>
        </div>
        <div class="p-3 rounded-lg bg-[#1E3A8A] border border-[#2563EB] text-[#BFDBFE] text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            Your proposal will be reviewed by ETS and the Administrator. Once approved, a Program will be created automatically.
        </div>`;
    sl.openForm('Submit Proposal', 'Propose a new extension program', formHtml, { showSaveAnother: false, size: 'lg' });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        await submitSlideOverForm(sl, `${siteUrl}/ajax/proposals.php?action=create_program_proposal`, formData, () => {
            showToast('Proposal submitted! ETS will review it.', 'success');
        });
    });
}
</script>

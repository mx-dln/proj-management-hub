<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Accomplishment Reports</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage project reports</p></div>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="reports">
        <div class="flex-1"><input type="text" name="search" placeholder="Search reports..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <select name="type" class="filter-select"><option value="">All Types</option><?php foreach(['quarterly','semi_annual','annual','terminal'] as $t): ?><option value="<?= $t ?>"><?= ucfirst(str_replace('_',' ',$t)) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div id="reportsTable">
    <div class="data-table-container">
        <?php if (empty($reports)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-clipboard-check"></i></div><h3 class="empty-state-title">No reports found</h3><p class="empty-state-text">Reports will appear when submitted for projects.</p></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Number</th><th>Title</th><th>Type</th><th>Project</th><th>Period</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($reports as $i => $r): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Number"><span class="table-code"><?= e($r['report_number']) ?></span></td>
                        <td data-label="Title"><span class="table-title"><?= e($r['title']) ?></span></td>
                        <td data-label="Type"><span class="badge badge-submitted"><?= ucfirst(str_replace('_',' ',$r['report_type'])) ?></span></td>
                        <td data-label="Project"><span class="text-[#D1D5DB]"><?= e($r['project_title'] ?? '-') ?></span></td>
                        <td data-label="Period"><span class="text-[#D1D5DB]"><?= e($r['period_quarter'] ?? '') ?> <?= $r['period_year'] ?? '' ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($r['status']) ?></td>
                        <td data-label="Actions">
                            <button onclick="viewReport(<?= $r['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';

async function viewReport(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Report Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/reports.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = data.report_number;
        const content = viewSectionHtml('Report Information', [
            viewFieldHtml('Report Number', `<span class="table-code">${escapeHtml(data.report_number)}</span>`),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('Type', ucfirst((data.report_type || '').replace(/_/g, ' '))),
            viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
            viewFieldHtml('Period', `${data.period_quarter || ''} ${data.period_year || ''}`),
            viewFieldHtml('Date Submitted', formatDate(data.date_submitted)),
        ]) + (data.summary ? viewSectionHtml('Summary', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.summary)}</p></div>`]) : '');
        sl.openView(data.title, data.report_number, content, { size: 'md' });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Document Repository</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage project documents</p></div>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="documents">
        <div class="flex-1"><input type="text" name="search" placeholder="Search documents..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <select name="category" class="filter-select"><option value="">All Categories</option><?php $cats = db()->query("SELECT * FROM document_categories WHERE is_active = 1")->fetchAll(); foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div id="documentsTable">
    <div class="data-table-container">
        <?php if (empty($documents)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-archive"></i></div><h3 class="empty-state-title">No documents found</h3><p class="empty-state-text">Documents will be uploaded to projects and activities.</p></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Title</th><th>Category</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($documents as $i => $d): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Title"><span class="table-title"><?= e($d['title']) ?></span><p class="text-xs text-[#6B7280]"><?= e($d['file_name']) ?></p></td>
                        <td data-label="Category"><span class="text-[#D1D5DB]"><?= e($d['category_name'] ?? '-') ?></span></td>
                        <td data-label="Type"><span class="text-[#D1D5DB]"><?= e($d['entity_type']) ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($d['status']) ?></td>
                        <td data-label="Actions">
                            <button onclick="viewDocument(<?= $d['id'] ?>, this)" class="action-btn" data-loading title="View"><i class="fas fa-eye text-sm"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';

async function viewDocument(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Document Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/documents.php?action=get&id=${id}`, btn);
        sl.setTitle(data.title);
        sl.subtitle = data.file_name;
        const content = viewSectionHtml('Document Information', [
            viewFieldHtml('Title', escapeHtml(data.title)),
            viewFieldHtml('Category', escapeHtml(data.category_name || '-')),
            viewFieldHtml('Entity Type', escapeHtml(data.entity_type)),
            viewFieldHtml('Status', getStatusBadge(data.status)),
            viewFieldHtml('File Name', escapeHtml(data.file_name)),
            viewFieldHtml('Version', 'v' + data.version),
        ]);
        sl.openView(data.title, data.file_name, content, { size: 'md' });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

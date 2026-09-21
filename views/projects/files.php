<?php
$fileModel = new ExplorerModel();
$fileSearch = trim($_GET['search'] ?? '');
$projectFiles = [];
foreach ($fileModel->all() as $fileNode) {
    if ($fileNode['kind'] !== 'file' || !$fileModel->readable($fileNode['id'])) continue;
    $chain = $fileModel->chain($fileNode['id']);
    array_pop($chain);
    $fileNode['location'] = implode(' / ', array_column($chain, 'name'));
    if ($fileSearch !== '' && stripos($fileNode['name'] . ' ' . $fileNode['location'], $fileSearch) === false) continue;
    $projectFiles[] = $fileNode;
}
usort($projectFiles, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));
$filePage = max(1, (int)($_GET['page'] ?? 1));
$filePages = max(1, (int)ceil(count($projectFiles) / ITEMS_PER_PAGE));
$filePage = min($filePage, $filePages);
$projectFiles = array_slice($projectFiles, ($filePage - 1) * ITEMS_PER_PAGE, ITEMS_PER_PAGE);
?>
<h1 class="text-2xl font-bold text-[#F9FAFB] mb-6">Project Files</h1>
<?php require __DIR__ . '/navigation.php'; ?>
<form method="get" class="flex gap-3 mb-6">
    <input type="hidden" name="module" value="project-files">
    <input type="search" name="search" value="<?= e($fileSearch) ?>" placeholder="Search files and locations..." aria-label="Search project files" class="filter-input min-w-0 flex-1">
    <button class="btn-primary" aria-label="Search"><i class="fas fa-search" aria-hidden="true"></i></button>
</form>
<div class="data-table-container overflow-x-auto">
    <table class="data-table"><thead><tr><th>Name</th><th>Location</th><th>Owner</th><th>Modified</th><th>Size</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($projectFiles as $file): ?>
        <tr>
            <td data-label="Name" class="break-words"><i class="fas fa-file mr-2" aria-hidden="true"></i><?= e($file['name']) ?></td>
            <td data-label="Location" class="text-xs break-words"><?= e($file['location']) ?></td>
            <td data-label="Owner"><?= e($file['owner']) ?></td>
            <td data-label="Modified"><?= e(formatDate($file['updated_at'])) ?></td>
            <td data-label="Size"><?= number_format($file['size'] / 1024, 1) ?> KB</td>
            <td data-label="Actions"><a class="action-btn" href="<?= SITE_URL ?>/ajax/drive.php?action=file&amp;id=<?= (int)$file['id'] ?>" target="_blank" rel="noopener" aria-label="Open <?= e($file['name']) ?>" title="Open"><i class="fas fa-eye" aria-hidden="true"></i></a><a class="action-btn" href="<?= SITE_URL ?>/ajax/drive.php?action=file&amp;id=<?= (int)$file['id'] ?>&amp;download=1" aria-label="Download <?= e($file['name']) ?>" title="Download"><i class="fas fa-download" aria-hidden="true"></i></a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$projectFiles): ?><tr><td colspan="6" class="text-center py-8 text-[#9CA3AF]">No files found.</td></tr><?php endif; ?>
    </tbody></table>
</div>
<?php if ($filePages > 1): ?><nav class="flex items-center justify-between mt-4" aria-label="File pages"><span class="text-sm text-[#9CA3AF]">Page <?= $filePage ?> of <?= $filePages ?></span><div class="flex gap-2"><?php if ($filePage > 1): ?><a class="btn-ghost" href="?<?= e(http_build_query(['module' => 'project-files', 'search' => $fileSearch, 'page' => $filePage - 1])) ?>" aria-label="Previous page"><i class="fas fa-chevron-left"></i></a><?php endif; ?><?php if ($filePage < $filePages): ?><a class="btn-ghost" href="?<?= e(http_build_query(['module' => 'project-files', 'search' => $fileSearch, 'page' => $filePage + 1])) ?>" aria-label="Next page"><i class="fas fa-chevron-right"></i></a><?php endif; ?></div></nav><?php endif; ?>

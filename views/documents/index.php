<?php if ($isDesignation): ?>
<div class="sketch-board">
    <div class="sketch-head"><div><h1>Designation</h1><p>(MGA FILES)</p></div></div>
    <div class="designation-list">
        <?php if (empty($uploadTargets) && empty($documents)): ?><div class="sketch-empty">No designation targets yet</div><?php endif; ?>
        <?php foreach ($uploadTargets as $target): ?>
            <?php
                $targetType = $target['type'] ?? '';
                $targetId = $target['id'] ?? null;
                if ((!$targetType || !$targetId) && !empty($target['value']) && str_contains($target['value'], ':')) {
                    [$targetType, $targetId] = explode(':', $target['value'], 2);
                }
                $targetId = (int)$targetId;
                $targetLabel = $target['label'] ?? ucfirst($targetType) . ' #' . $targetId;
                $attached = array_values(array_filter($documents, fn($d) => $d['entity_type'] === $targetType && (int)$d['entity_id'] === $targetId));
                $latest = $attached[0] ?? null;
            ?>
            <div class="designation-row <?= $latest ? 'has-file' : '' ?>" <?= $latest ? 'onclick="viewDocument(' . (int)$latest['id'] . ', this)" tabindex="0" role="button"' : '' ?>>
                <div class="designation-name"><button type="button" class="designation-caret" <?= $latest ? 'onclick="event.stopPropagation(); viewDocument(' . (int)$latest['id'] . ', this)" aria-label="View file"' : 'disabled aria-label="No file uploaded yet"' ?>><i class="fas fa-caret-right"></i></button><span><?= e($targetLabel) ?></span><small><?= $latest ? e($latest['title'] . ' · ' . $latest['file_name']) : e(ucfirst($targetType) . ' · no uploaded file yet') ?></small></div>
                <div class="designation-actions">
                    <?php if ($latest): ?>
                        <button onclick="event.stopPropagation(); viewDocument(<?= (int)$latest['id'] ?>, this)" class="btn-ghost">View</button>
                        <button onclick="event.stopPropagation(); printDocument(<?= (int)$latest['id'] ?>)" class="btn-ghost"><i class="fas fa-print mr-1"></i> Print</button>
                        <a href="<?= SITE_URL ?>/ajax/documents.php?action=file&amp;id=<?= (int)$latest['id'] ?>&amp;download=1" onclick="event.stopPropagation()" class="btn-primary"><i class="fas fa-download mr-1"></i> Download</a>
                    <?php else: ?>
                        <button class="btn-ghost" disabled>View</button>
                        <?php if ($canUpload): ?><button onclick="event.stopPropagation(); openDocumentUpload('<?= e($targetType) ?>', <?= $targetId ?>)" class="btn-primary"><i class="fas fa-upload mr-1"></i> Upload</button><?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php foreach ($documents as $d): ?>
            <?php if (!array_filter($uploadTargets, function($t) use ($d) { $type = $t['type'] ?? ''; $id = $t['id'] ?? null; if ((!$type || !$id) && !empty($t['value']) && str_contains($t['value'], ':')) { [$type, $id] = explode(':', $t['value'], 2); } return $type === $d['entity_type'] && (int)$id === (int)$d['entity_id']; })): ?>
            <div class="designation-row has-file" onclick="viewDocument(<?= (int)$d['id'] ?>, this)" tabindex="0" role="button">
                <div class="designation-name"><button type="button" class="designation-caret" onclick="event.stopPropagation(); viewDocument(<?= (int)$d['id'] ?>, this)" aria-label="View file"><i class="fas fa-caret-right"></i></button><span><?= e($d['title']) ?></span><small><?= e($d['file_name']) ?></small></div>
                <div class="designation-actions"><button onclick="event.stopPropagation(); viewDocument(<?= (int)$d['id'] ?>, this)" class="btn-ghost">View</button></div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<style>
.designation-list{padding:18px}.designation-row{display:flex;align-items:center;justify-content:space-between;gap:14px;border:1px solid #374151;background:#111827;border-radius:8px;padding:12px 14px;margin-bottom:10px}
.designation-row.has-file{cursor:pointer}.designation-row.has-file:hover{border-color:#0F643A;background:#172f25}
.designation-name{display:grid;grid-template-columns:24px 1fr;gap:8px;color:#F9FAFB}.designation-name small{grid-column:2;color:#9CA3AF}.designation-actions{display:flex;gap:8px;flex-wrap:wrap}
.designation-caret{width:24px;height:24px;border-radius:6px;color:#86EFAC;display:inline-flex;align-items:center;justify-content:center}.designation-caret:not(:disabled):hover{background:#14532D}.designation-caret:disabled{color:#6B7280;cursor:not-allowed}
@media(max-width:700px){.designation-row{align-items:flex-start;flex-direction:column}}
</style>
<?php else: ?>
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]"><?= $isDesignation ? 'Designation' : 'Document Repository' ?></h1><p class="text-[#9CA3AF] text-sm mt-1"><?= $isDesignation ? 'Designation letters and office orders' : 'Project documents' ?></p></div>
    <?php if ($canUpload): ?><button type="button" onclick="openDocumentUpload()" class="btn-primary"><i class="fas fa-upload" aria-hidden="true"></i> Upload</button><?php endif; ?>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="<?= $documentModule ?>">
        <div class="flex-1 min-w-0"><input type="search" name="search" placeholder="Search files..." aria-label="Search files" value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <?php if (!$isDesignation): ?><select name="category" class="filter-select" aria-label="Category"><option value="">All Categories</option><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string)$categoryId === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?php endif; ?>
        <button type="submit" class="btn-primary" aria-label="Search"><i class="fas fa-search" aria-hidden="true"></i></button>
    </form>
</div>

<div id="documentsTable">
    <div class="data-table-container">
        <?php if (empty($documents)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-file-alt"></i></div><h3 class="empty-state-title">No <?= $isDesignation ? 'designation files' : 'documents' ?> found</h3></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Title</th><th>Category</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($documents as $i => $d): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Title"><span class="table-title"><?= e($d['title']) ?></span><p class="text-xs text-[#6B7280]"><?= e($d['file_name']) ?></p></td>
                        <td data-label="Category"><span class="text-[#D1D5DB]"><?= e($d['category_name'] ?? '-') ?></span></td>
                        <td data-label="Type"><span class="text-[#D1D5DB]"><?= e($d['entity_type']) ?></span></td>
                        <td data-label="Status"><?= getStatusBadge($d['status']) ?></td>
                        <td data-label="Actions">
                            <button onclick="viewDocument(<?= (int)$d['id'] ?>, this)" class="action-btn" aria-label="View <?= e($d['title']) ?>" title="View"><i class="fas fa-eye text-sm" aria-hidden="true"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($totalPages > 1): ?><nav class="flex items-center justify-between mt-4" aria-label="Document pages"><span class="text-sm text-[#9CA3AF]">Page <?= $page ?> of <?= $totalPages ?></span><div class="flex gap-2"><?php foreach ([-1 => 'Previous', 1 => 'Next'] as $step => $label): $targetPage = $page + $step; if ($targetPage < 1 || $targetPage > $totalPages) continue; ?><a class="btn-ghost" aria-label="<?= $label ?> page" href="?<?= e(http_build_query(['module' => $documentModule, 'search' => $_GET['search'] ?? '', 'category' => $categoryId, 'page' => $targetPage])) ?>"><i class="fas fa-chevron-<?= $step < 0 ? 'left' : 'right' ?>" aria-hidden="true"></i></a><?php endforeach; ?></div></nav><?php endif; ?>
<script id="document-config" type="application/json"><?= json_encode(['scope' => $documentModule, 'csrf' => $_SESSION['documents_csrf'], 'targets' => $uploadTargets, 'categories' => array_map(fn($c) => ['value' => $c['id'], 'label' => $c['name']], $categories), 'limit' => $uploadLimit, 'extensions' => DocumentModel::EXTENSIONS], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= SITE_URL ?>/assets/js/documents.js?v=20260921" defer></script>
<?php endif; ?>
<?php if ($isDesignation): ?>
<script id="document-config" type="application/json"><?= json_encode(['scope' => $documentModule, 'csrf' => $_SESSION['documents_csrf'], 'targets' => $uploadTargets, 'categories' => array_map(fn($c) => ['value' => $c['id'], 'label' => $c['name']], $categories), 'limit' => $uploadLimit, 'extensions' => DocumentModel::EXTENSIONS], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= SITE_URL ?>/assets/js/documents.js?v=20260921" defer></script>
<?php endif; ?>

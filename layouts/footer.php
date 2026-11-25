                </div>
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeConfirmModal()"></div>
        <div class="relative bg-[#1F2937] rounded-2xl shadow-2xl max-w-md w-full p-6 modal-enter border border-[#374151]">
            <div class="text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-[#7F1D1D] flex items-center justify-center mb-4">
                    <i class="fas fa-exclamation-triangle text-[#FCA5A5] text-xl"></i>
                </div>
                <h3 id="confirm-title" class="text-lg font-semibold text-[#F9FAFB] mb-2">Confirm Action</h3>
                <p id="confirm-message" class="text-[#9CA3AF] text-sm mb-6">Are you sure?</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="closeConfirmModal()" class="btn-ghost">Cancel</button>
                    <button id="confirm-btn" class="bg-[#DC2626] hover:bg-[#B91C1C] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-[#1F2937] rounded-xl p-6 shadow-2xl flex items-center gap-3 border border-[#374151]">
            <div class="spinner"></div>
            <span class="text-[#F9FAFB] text-sm">Processing...</span>
        </div>
    </div>

    <?php
    $pendingDesignation = null;
    $pUserId = $_SESSION['user_id'] ?? 0;
    if ($pUserId) {
        try {
            $pStmt = db()->prepare("
                SELECT n.id as notif_id, n.title as notif_title, n.message as notif_message, n.entity_id as document_id, n.created_at as issued_at,
                       d.title as doc_title, d.file_name, d.entity_type, d.entity_id
                FROM notifications n
                LEFT JOIN documents d ON n.entity_id = d.id
                WHERE n.user_id = ? 
                  AND (n.entity_type = 'designation' OR n.title LIKE '%Designation%')
                  AND n.is_read = 0
                ORDER BY n.id DESC
                LIMIT 1
            ");
            $pStmt->execute([$pUserId]);
            $pendingDesignation = $pStmt->fetch();
            if ($pendingDesignation && !empty($pendingDesignation['entity_type']) && !empty($pendingDesignation['entity_id'])) {
                $pTableMap = ['program'=>'programs', 'project'=>'projects', 'component'=>'components', 'activity'=>'extension_activities'];
                $pTbl = $pTableMap[$pendingDesignation['entity_type']] ?? null;
                if ($pTbl) {
                    $peStmt = db()->prepare("SELECT title FROM {$pTbl} WHERE id = ?");
                    $peStmt->execute([$pendingDesignation['entity_id']]);
                    $pendingDesignation['entity_name'] = $peStmt->fetchColumn() ?: '';
                }
            }
        } catch (Throwable $e) {}
    }
    ?>
    <?php if ($pendingDesignation): ?>
    <!-- Designation Arrival Notification Modal -->
    <div id="designationArrivalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="relative bg-[#1F2937] border-2 border-[#10B981] rounded-2xl shadow-2xl max-w-lg w-full p-6 text-center modal-enter">
            <div class="mx-auto w-16 h-16 rounded-full bg-[#064E3B] text-[#34D399] flex items-center justify-center text-3xl mb-4 border border-[#10B981]/50 shadow-lg">
                <i class="fas fa-award"></i>
            </div>
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-[#14532D] text-[#86EFAC] border border-[#1E7A4B] mb-2 uppercase tracking-wider">
                Official Designation Notice
            </span>
            <h3 class="text-xl font-extrabold text-[#F9FAFB] mb-2">New Designation Arrived!</h3>
            <p class="text-[#D1D5DB] text-sm mb-4">You have been issued an official faculty designation. Please review your assignment details below.</p>
            
            <div class="bg-[#111827] border border-[#374151] rounded-xl p-4 text-left mb-6 space-y-2">
                <div class="flex items-start gap-2">
                    <i class="fas fa-file-contract text-[#86EFAC] mt-1"></i>
                    <div>
                        <span class="text-xs text-[#9CA3AF] block">Designation Order:</span>
                        <strong class="text-sm text-[#F9FAFB]"><?= e($pendingDesignation['doc_title'] ?: ($pendingDesignation['notif_title'] ?: 'Designation Order')) ?></strong>
                        <?php if (!empty($pendingDesignation['file_name'])): ?>
                            <small class="text-[#9CA3AF] block text-xs"><?= e($pendingDesignation['file_name']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($pendingDesignation['entity_name'])): ?>
                <div class="flex items-start gap-2 pt-1 border-t border-[#374151]/60">
                    <i class="fas fa-project-diagram text-[#F5D20C] mt-1"></i>
                    <div>
                        <span class="text-xs text-[#9CA3AF] block">Related <?= e(ucfirst($pendingDesignation['entity_type'])) ?>:</span>
                        <span class="text-sm text-[#D1D5DB]"><?= e($pendingDesignation['entity_name']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="flex items-center gap-2 pt-1 border-t border-[#374151]/60">
                    <i class="fas fa-clock text-[#60A5FA]"></i>
                    <span class="text-xs text-[#9CA3AF]">Issued:</span>
                    <span class="text-xs text-[#D1D5DB]"><?= e(formatDateTime($pendingDesignation['issued_at'])) ?></span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?= SITE_URL ?>/index.php?module=designations&view_doc=<?= (int)$pendingDesignation['document_id'] ?>" onclick="dismissDesignationModal(<?= (int)$pendingDesignation['notif_id'] ?>)" class="btn-primary flex-1 justify-center py-2.5 text-center bg-[#0F643A] hover:bg-[#1E7A4B] text-white font-semibold rounded-lg shadow-md transition-all">
                    <i class="fas fa-eye mr-2"></i> View Designation
                </a>
                <button type="button" onclick="dismissDesignationModal(<?= (int)$pendingDesignation['notif_id'] ?>)" class="btn-ghost flex-1 justify-center py-2.5 text-[#D1D5DB] hover:text-white border border-[#374151] rounded-lg transition-all">
                    <i class="fas fa-check mr-2"></i> Acknowledge
                </button>
            </div>
        </div>
    </div>
    <script>
    function dismissDesignationModal(notifId) {
        const modal = document.getElementById('designationArrivalModal');
        if (modal) modal.remove();
        fetch('<?= SITE_URL ?>/ajax/notifications.php?action=mark_read', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: notifId})
        }).catch(() => {});
    }
    </script>
    <?php endif; ?>

    <script src="<?= SITE_URL ?>/assets/js/app.js?v=<?= e(filemtime(__DIR__ . '/../assets/js/app.js')) ?>"></script>
    <script src="<?= SITE_URL ?>/assets/js/slideover.js?v=<?= e(filemtime(__DIR__ . '/../assets/js/slideover.js')) ?>"></script>
    <script src="<?= SITE_URL ?>/assets/js/camera-scanner.js?v=<?= e(filemtime(__DIR__ . '/../assets/js/camera-scanner.js')) ?>"></script>
    <script src="<?= SITE_URL ?>/assets/js/assignments.js?v=<?= e(filemtime(__DIR__ . '/../assets/js/assignments.js')) ?>"></script>
    <script src="<?= SITE_URL ?>/assets/js/explorer.js?v=<?= e(filemtime(__DIR__ . '/../assets/js/explorer.js')) ?>"></script>
    <script type="module" src="<?= SITE_URL ?>/assets/js/document-viewer.js?v=<?= e(filemtime(__DIR__ . '/../assets/js/document-viewer.js')) ?>"></script>
</body>
</html>

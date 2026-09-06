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

    <script src="<?= SITE_URL ?>/assets/js/app.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/slideover.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/assignments.js"></script>
</body>
</html>

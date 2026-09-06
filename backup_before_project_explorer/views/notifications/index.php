<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Notifications</h1><p class="text-[#9CA3AF] text-sm mt-1">Your notifications</p></div>
    <button onclick="markAllRead()" class="btn-ghost"><i class="fas fa-check-double mr-1"></i> Mark all as read</button>
</div>
<div class="card">
    <?php if(empty($notifications)): ?><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-bell-slash"></i></div><h3 class="empty-state-title">No notifications</h3><p class="empty-state-text">You're all caught up</p></div>
    <?php else: ?><div class="divide-y divide-[#374151]"><?php foreach($notifications as $n): ?>
        <div class="flex items-start gap-4 p-4 <?= !$n['is_read']?'bg-[#0F643A]/5':'' ?> hover:bg-[#324152] transition-colors">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 <?= !$n['is_read']?'bg-[#1E3A8A]':'bg-[#374151]' ?>"><i class="fas fa-bell <?= !$n['is_read']?'text-[#BFDBFE]':'text-[#6B7280]' ?> text-sm"></i></div>
            <div class="flex-1 min-w-0"><div class="flex items-center gap-2"><p class="text-sm font-medium text-[#F9FAFB]"><?= e($n['title']) ?></p><?php if(!$n['is_read']): ?><span class="w-2 h-2 bg-[#3B82F6] rounded-full"></span><?php endif; ?></div><p class="text-sm text-[#9CA3AF] mt-1"><?= e($n['message']) ?></p><p class="text-xs text-[#6B7280] mt-2"><?= timeAgo($n['created_at']) ?></p></div>
            <?php if($n['action_url']): ?><a href="<?= SITE_URL . $n['action_url'] ?>" class="action-btn flex-shrink-0"><i class="fas fa-external-link-alt text-sm"></i></a><?php endif; ?>
        </div>
    <?php endforeach; ?></div><?php endif; ?>
</div>
<script>function markAllRead(){fetch('<?= SITE_URL ?>/ajax/notifications.php?action=mark_all_read').then(r=>r.json()).then(()=>{showToast('All marked as read');setTimeout(()=>location.reload(),1000)})}</script>

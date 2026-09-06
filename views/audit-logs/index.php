<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Audit Logs</h1><p class="text-[#9CA3AF] text-sm mt-1">System activity logs</p></div>
</div>
<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="audit-logs">
        <div class="flex-1"><input type="text" name="search" placeholder="Search logs..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <select name="action" class="filter-select"><option value="">All Actions</option><?php foreach(['login','logout','create','edit','delete','approve','reject'] as $a): ?><option value="<?= $a ?>" <?= ($_GET['action']??'')===$a?'selected':'' ?>><?= ucfirst($a) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>
<div class="data-table-container">
    <?php if(empty($result['data'])): ?><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-history"></i></div><h3 class="empty-state-title">No logs found</h3></div>
    <?php else: ?><div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Entity</th><th>Description</th><th>IP</th></tr></thead><tbody>
        <?php foreach($result['data'] as $i=>$log): ?><tr class="animate-row" style="animation-delay:<?= $i*0.03 ?>s">
            <td data-label="Time"><span class="table-date"><?= date('M d, Y h:i A',strtotime($log['created_at'])) ?></span></td>
            <td data-label="User"><span class="table-title"><?= e($log['username'] ?? 'System') ?></span></td>
            <td data-label="Action"><span class="badge <?php $ac=['login'=>'badge-submitted','create'=>'badge-approved','edit'=>'badge-pending','delete'=>'badge-rejected','approve'=>'badge-approved','reject'=>'badge-rejected']; echo $ac[$log['action']]??'badge-draft'; ?>"><?= ucfirst($log['action']) ?></span></td>
            <td data-label="Entity"><span class="text-[#D1D5DB]"><?= e($log['entity_type'] ?? '-') ?></span></td>
            <td data-label="Description"><span class="text-[#D1D5DB] max-w-[300px] truncate block"><?= e($log['description'] ?? '-') ?></span></td>
            <td data-label="IP"><span class="text-[#6B7280] font-mono text-xs"><?= e($log['ip_address'] ?? '-') ?></span></td>
        </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</div>
<?php if($totalPages>1): ?><div class="flex items-center justify-between mt-6"><p class="text-sm text-[#9CA3AF]">Showing <?= ($page-1)*$limit+1 ?> to <?= min($page*$limit,$result['total']) ?> of <?= $result['total'] ?></p><div class="pagination"><?php if($page>1): ?><a href="?module=audit-logs&page=<?= $page-1 ?>" class="pagination-btn"><i class="fas fa-chevron-left text-xs"></i></a><?php endif; ?><?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?><a href="?module=audit-logs&page=<?= $i ?>" class="pagination-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?><?php if($page<$totalPages): ?><a href="?module=audit-logs&page=<?= $page+1 ?>" class="pagination-btn"><i class="fas fa-chevron-right text-xs"></i></a><?php endif; ?></div></div><?php endif; ?>

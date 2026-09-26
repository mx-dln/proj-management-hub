<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Faculty</h1><p class="text-[#9CA3AF] text-sm mt-1">Manage faculty members</p></div>
</div>

<div class="filter-bar mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="module" value="faculty">
        <div class="flex-1"><input type="text" name="search" placeholder="Search faculty..." value="<?= e($_GET['search'] ?? '') ?>" class="filter-input"></div>
        <select name="department" class="filter-select"><option value="">All Departments</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>" <?= ($_GET['department']??'')==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div id="facultyTable">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if(empty($result['data'])): ?>
            <div class="col-span-full"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-user-tie"></i></div><h3 class="empty-state-title">No faculty found</h3></div></div>
        <?php else: ?>
            <?php foreach($result['data'] as $i=>$f): ?>
                <div class="card p-5 animate-fade-in cursor-pointer hover:border-[#0F643A] transition-colors" style="animation-delay:<?= $i*0.05 ?>s" onclick="viewFaculty(<?= $f['id'] ?>, this)" data-loading>
                    <div class="flex items-center gap-4 mb-3">
                        <div class="avatar"><i class="fas fa-user"></i></div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-[#F9FAFB] truncate"><?= e($f['last_name'].', '.$f['first_name']) ?></h3>
                            <p class="text-xs text-[#9CA3AF]"><?= e($f['position'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-[#9CA3AF]"><i class="fas fa-building mr-2 w-4"></i><?= e($f['department_name'] ?? '-') ?></p>
                        <p class="text-xs text-[#9CA3AF]"><i class="fas fa-id-badge mr-2 w-4"></i><?= e($f['employee_id']) ?></p>
                        <p class="text-xs text-[#9CA3AF]"><i class="fas fa-envelope mr-2 w-4"></i><?= e($f['user_email'] ?? '-') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const siteUrl = <?= json_encode(SITE_URL) ?>;

async function viewFaculty(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Faculty Profile', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/faculty.php?action=get&id=${id}`, btn);
        sl.setTitle(`${data.first_name} ${data.last_name}`);
        sl.subtitle = data.employee_id;
        const timelineRows = Array.isArray(data.timeline) && data.timeline.length ? data.timeline.map(item => `
            <div class="faculty-timeline-item">
                <div class="faculty-timeline-dot"></div>
                <div class="faculty-timeline-card">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-[#F9FAFB]">${escapeHtml(item.label || '-')}</p>
                        <span class="text-xs text-[#6B7280]">${formatDate(item.created_at)}</span>
                    </div>
                    <p class="text-xs text-[#9CA3AF] mt-1">${escapeHtml(item.type || 'Activity')}</p>
                    <p class="text-sm text-[#D1D5DB] mt-2">${escapeHtml(item.description || '-')}</p>
                </div>
            </div>
        `).join('') : `<p class="text-sm text-[#6B7280] text-center py-6">No timeline records yet</p>`;
        const content = `
            <div class="flex items-center gap-4 mb-6 p-4 rounded-xl bg-[#111827] border border-[#374151]">
                <div class="avatar" style="width:48px;height:48px;font-size:18px"><i class="fas fa-user"></i></div>
                <div>
                    <p class="text-lg font-semibold text-[#F9FAFB]">${escapeHtml(data.first_name)} ${escapeHtml(data.last_name)}</p>
                    <p class="text-sm text-[#9CA3AF]">${escapeHtml(data.position || '')}</p>
                </div>
            </div>
            ${viewSectionHtml('Information', [
                viewFieldHtml('Employee ID', escapeHtml(data.employee_id)),
                viewFieldHtml('Department', escapeHtml(data.department_name || '-')),
                viewFieldHtml('Position', escapeHtml(data.position)),
                viewFieldHtml('Email', escapeHtml(data.email || data.user_email)),
                viewFieldHtml('Contact', escapeHtml(data.contact_number)),
                viewFieldHtml('Specialization', escapeHtml(data.specialization)),
            ])}
            <div class="form-section">
                <h4 class="form-section-title">Faculty Activity Timeline</h4>
                <div class="faculty-timeline">${timelineRows}</div>
            </div>`;
        sl.openView(`${data.first_name} ${data.last_name}`, data.employee_id, content, { size: 'lg' });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}
</script>

<style>
.faculty-timeline{display:flex;flex-direction:column;gap:12px}
.faculty-timeline-item{display:grid;grid-template-columns:18px 1fr;gap:10px;position:relative}
.faculty-timeline-item::before{content:"";position:absolute;left:8px;top:18px;bottom:-12px;width:1px;background:#374151}
.faculty-timeline-item:last-child::before{display:none}
.faculty-timeline-dot{width:10px;height:10px;border-radius:999px;background:#86EFAC;margin:7px 0 0 4px;box-shadow:0 0 0 3px rgba(15,100,58,.35)}
.faculty-timeline-card{background:#111827;border:1px solid #374151;border-radius:8px;padding:12px}
</style>

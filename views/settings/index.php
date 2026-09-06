<div class="mb-6"><h1 class="text-2xl font-bold text-[#F9FAFB]">Settings</h1><p class="text-[#9CA3AF] text-sm mt-1">System configuration</p></div>
<div class="mb-6 border-b border-[#374151]"><nav class="flex gap-1 overflow-x-auto">
    <button onclick="showSettingsTab('general')" class="settings-tab active px-4 py-2.5 text-sm font-medium border-b-2 border-[#0F643A] text-[#86EFAC]">General</button>
    <button onclick="showSettingsTab('departments')" class="settings-tab px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-[#9CA3AF] hover:text-[#F9FAFB]">Departments</button>
    <button onclick="showSettingsTab('funding')" class="settings-tab px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-[#9CA3AF] hover:text-[#F9FAFB]">Funding</button>
    <button onclick="showSettingsTab('activities')" class="settings-tab px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-[#9CA3AF] hover:text-[#F9FAFB]">Activity Types</button>
</nav></div>

<div id="settings-general" class="settings-content">
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">General Settings</h3></div><div class="card-body">
        <form id="generalForm" class="space-y-4">
            <?php foreach($settings as $s): if($s['setting_group']==='general'): ?><div><label class="form-label"><?= e(ucwords(str_replace('_',' ',$s['setting_key']))) ?></label><input type="text" name="<?= e($s['setting_key']) ?>" value="<?= e($s['setting_value']) ?>" class="form-input"></div><?php endif; endforeach; ?>
            <button type="submit" class="btn-primary"><i class="fas fa-save mr-1"></i> Save</button>
        </form>
    </div></div>
</div>

<div id="settings-departments" class="settings-content hidden">
    <div class="card"><div class="card-header flex items-center justify-between"><h3 class="text-lg font-semibold text-[#F9FAFB]">Departments</h3></div><div class="card-body">
        <div class="space-y-2"><?php foreach($departments as $d): ?><div class="flex items-center justify-between p-3 rounded-lg bg-[#111827] border border-[#374151]"><div><p class="text-sm font-medium text-[#F9FAFB]"><?= e($d['name']) ?></p><p class="text-xs text-[#6B7280]"><?= e($d['code'] ?? '') ?></p></div><span class="text-xs px-2 py-0.5 rounded <?= $d['is_active']?'bg-[#14532D] text-[#86EFAC]':'bg-[#374151] text-[#9CA3AF]' ?>"><?= $d['is_active']?'Active':'Inactive' ?></span></div><?php endforeach; ?></div>
    </div></div>
</div>

<div id="settings-funding" class="settings-content hidden">
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">Funding Sources</h3></div><div class="card-body">
        <div class="space-y-2"><?php foreach($fundingSources as $f): ?><div class="flex items-center justify-between p-3 rounded-lg bg-[#111827] border border-[#374151]"><p class="text-sm font-medium text-[#F9FAFB]"><?= e($f['name']) ?></p><span class="text-xs px-2 py-0.5 rounded <?= $f['is_active']?'bg-[#14532D] text-[#86EFAC]':'bg-[#374151] text-[#9CA3AF]' ?>"><?= $f['is_active']?'Active':'Inactive' ?></span></div><?php endforeach; ?></div>
    </div></div>
</div>

<div id="settings-activities" class="settings-content hidden">
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">Activity Types</h3></div><div class="card-body">
        <div class="space-y-2"><?php foreach($activityTypes as $a): ?><div class="flex items-center justify-between p-3 rounded-lg bg-[#111827] border border-[#374151]"><p class="text-sm font-medium text-[#F9FAFB]"><?= e($a['name']) ?></p><span class="text-xs px-2 py-0.5 rounded <?= $a['is_active']?'bg-[#14532D] text-[#86EFAC]':'bg-[#374151] text-[#9CA3AF]' ?>"><?= $a['is_active']?'Active':'Inactive' ?></span></div><?php endforeach; ?></div>
    </div></div>
</div>

<script>
const siteUrl='<?= SITE_URL ?>';
function showSettingsTab(t){document.querySelectorAll('.settings-content').forEach(e=>e.classList.add('hidden'));document.querySelectorAll('.settings-tab').forEach(e=>{e.classList.remove('border-[#0F643A]','text-[#86EFAC]');e.classList.add('border-transparent','text-[#9CA3AF]')});document.getElementById('settings-'+t).classList.remove('hidden');event.target.classList.add('border-[#0F643A]','text-[#86EFAC]');event.target.classList.remove('border-transparent','text-[#9CA3AF]')}
document.addEventListener('DOMContentLoaded',function(){const f=document.getElementById('generalForm');if(f)submitForm(f,siteUrl+'/ajax/settings.php?action=save_general',function(){setTimeout(()=>location.reload(),1000)})});
</script>

<div class="mb-6"><a href="<?= SITE_URL ?>/index.php?module=projects" class="text-[#86EFAC] hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Projects</a><h1 class="text-2xl font-bold text-[#F9FAFB] mt-2">Create New Project</h1></div>
<form id="projectForm" class="space-y-6">
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">Project Information</h3></div><div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="form-label">Project Title *</label><input type="text" name="title" required class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-input"></textarea></div>
            <div><label class="form-label">Program *</label><select name="program_id" required class="form-select"><option value="">Select Program</option><?php foreach($programs as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></div>
            <div><label class="form-label">Funding Source</label><select name="funding_source_id" class="form-select"><option value="">Select</option><?php foreach($fundingSources as $f): ?><option value="<?= $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="form-label">Partner Agency</label><select name="partner_agency_id" class="form-select"><option value="">Select</option><?php foreach($partnerAgencies as $pa): ?><option value="<?= $pa['id'] ?>"><?= e($pa['name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="form-label">Beneficiary Group</label><select name="beneficiary_group_id" class="form-select"><option value="">Select</option><?php foreach($beneficiaryGroups as $bg): ?><option value="<?= $bg['id'] ?>"><?= e($bg['name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="form-label">Budget</label><input type="number" name="budget" step="0.01" min="0" class="form-input"></div>
            <div><label class="form-label">Location</label><input type="text" name="location" class="form-input"></div>
            <div><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-input"></div>
            <div><label class="form-label">End Date</label><input type="date" name="end_date" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Objectives</label><textarea name="objectives" rows="3" class="form-input"></textarea></div>
            <div class="md:col-span-2"><label class="form-label">Expected Outputs</label><textarea name="expected_outputs" rows="3" class="form-input"></textarea></div>
        </div>
    </div></div>
    <div class="flex justify-end gap-3"><a href="<?= SITE_URL ?>/index.php?module=projects" class="btn-ghost">Cancel</a><button type="submit" class="btn-primary"><i class="fas fa-save mr-1"></i> Create Project</button></div>
</form>
<script>submitForm(document.getElementById('projectForm'),'<?= SITE_URL ?>/ajax/projects.php?action=create',function(d){setTimeout(()=>{window.location.href='<?= SITE_URL ?>/index.php?module=projects&action=view&id='+d.id},1000)});</script>

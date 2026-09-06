<div class="mb-6"><a href="<?= SITE_URL ?>/index.php?module=programs" class="text-[#86EFAC] hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Programs</a><h1 class="text-2xl font-bold text-[#F9FAFB] mt-2">Create New Program</h1></div>
<form id="programForm" class="space-y-6">
    <div class="card"><div class="card-header"><h3 class="text-lg font-semibold text-[#F9FAFB]">Program Information</h3></div><div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="form-label">Program Title *</label><input type="text" name="title" required class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-input"></textarea></div>
            <div><label class="form-label">College</label><input type="text" name="college" class="form-input"></div>
            <div><label class="form-label">Campus</label><input type="text" name="campus" class="form-input" value="Cauayan Campus"></div>
            <div><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-input"></div>
            <div><label class="form-label">End Date</label><input type="date" name="end_date" class="form-input"></div>
            <div><label class="form-label">Budget Allocation</label><input type="number" name="budget_allocation" step="0.01" min="0" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Objectives</label><textarea name="objectives" rows="3" class="form-input"></textarea></div>
            <div class="md:col-span-2"><label class="form-label">Expected Outputs</label><textarea name="expected_outputs" rows="3" class="form-input"></textarea></div>
        </div>
    </div></div>
    <div class="flex justify-end gap-3"><a href="<?= SITE_URL ?>/index.php?module=programs" class="btn-ghost">Cancel</a><button type="submit" class="btn-primary"><i class="fas fa-save mr-1"></i> Create Program</button></div>
</form>
<script>submitForm(document.getElementById('programForm'),'<?= SITE_URL ?>/ajax/programs.php?action=create',function(d){setTimeout(()=>{window.location.href='<?= SITE_URL ?>/index.php?module=programs&action=view&id='+d.id},1000)});</script>

<nav class="flex gap-1 overflow-x-auto border-b border-[#374151] mb-6" aria-label="Project management sections">
    <?php foreach (['projects' => 'Projects', 'programs' => 'Programs', 'components' => 'Components', 'activities' => 'Activities', 'project-files' => 'Files'] as $projectModule => $projectLabel):
        $selected = ($_GET['module'] ?? '') === $projectModule; ?>
        <a href="<?= SITE_URL ?>/index.php?module=<?= $projectModule ?>" class="px-4 py-3 whitespace-nowrap text-sm border-b-2 <?= $selected ? 'border-[#0F643A] text-[#86EFAC]' : 'border-transparent text-[#9CA3AF] hover:text-white' ?>" <?= $selected ? 'aria-current="page"' : '' ?>><?= $projectLabel ?></a>
    <?php endforeach; ?>
</nav>

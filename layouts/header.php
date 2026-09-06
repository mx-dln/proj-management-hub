<?php require_once __DIR__ . '/../config/helpers.php'; ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(getSetting('system_name', SITE_NAME)) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                'isu': { DEFAULT:'#0F643A', hover:'#1E7A4B', light:'#D1FAE5', dark:'#0A4A2B' },
                'gold': { DEFAULT:'#F5D20C', dark:'#C4972C' }
            }}}
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/app.css">
</head>
<body class="h-full bg-[#111827] text-[#F9FAFB]">
    <div id="app" class="flex h-full">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <?php include __DIR__ . '/topnav.php'; ?>
            <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-[#111827]">
                <div class="max-w-7xl mx-auto">
                    <?php $success = flash('success'); $error = flash('error'); ?>
                    <?php if ($success): ?>
                        <div class="mb-4 p-4 rounded-xl bg-[#14532D] border border-[#1E7A4B] text-[#86EFAC] flex items-center gap-2 toast-enter">
                            <i class="fas fa-check-circle"></i> <?= e($success) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="mb-4 p-4 rounded-xl bg-[#7F1D1D] border border-[#991B1B] text-[#FCA5A5] flex items-center gap-2 toast-enter">
                            <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>

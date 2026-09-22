<?php
require_once __DIR__ . '/../../includes/EmailQueue.php';
$mailConfig = (new Mailer(db()))->publicSettings();
?>
<div id="settings-mail" class="settings-content hidden" data-endpoint="<?= e(SITE_URL) ?>/ajax/mail.php" data-csrf="<?= e($_SESSION['settings_csrf']) ?>">
    <form id="mail-settings-form" class="space-y-6 max-w-4xl">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['settings_csrf']) ?>">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-[#F9FAFB]">Email Delivery</h2>
            <label class="flex items-center gap-2 text-sm text-[#D1D5DB]"><input id="mail-enabled" type="checkbox" name="mail_enabled" value="1" <?= $mailConfig['mail_enabled'] === '1' ? 'checked' : '' ?>> Enable email notifications</label>
        </div>
        <fieldset class="border-t border-[#374151] pt-4">
            <legend class="text-sm font-semibold text-[#D1D5DB] pr-3">SMTP Server</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="min-w-0"><label for="mail-host" class="form-label">Host</label><input id="mail-host" name="mail_host" class="form-input" maxlength="255" placeholder="smtp.example.com" value="<?= e($mailConfig['mail_host']) ?>"></div>
                <div><label for="mail-port" class="form-label">Port</label><input id="mail-port" name="mail_port" type="number" min="1" max="65535" required class="form-input" value="<?= e($mailConfig['mail_port']) ?>"></div>
                <div><label for="mail-encryption" class="form-label">Encryption</label><select id="mail-encryption" name="mail_encryption" class="form-input"><?php foreach (['tls' => 'STARTTLS', 'ssl' => 'SSL / TLS', 'none' => 'None (local mail server only)'] as $value => $label): ?><option value="<?= $value ?>" <?= $mailConfig['mail_encryption'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="flex items-end min-h-[64px]"><label class="flex items-center gap-2 text-sm text-[#D1D5DB] pb-3"><input type="checkbox" id="mail-auth" name="mail_auth" value="1" <?= $mailConfig['mail_auth'] === '1' ? 'checked' : '' ?>> SMTP authentication</label></div>
                <div class="min-w-0"><label for="mail-username" class="form-label">Username</label><input id="mail-username" name="mail_username" autocomplete="off" maxlength="255" class="form-input" value="<?= e($mailConfig['mail_username']) ?>"></div>
                <div class="min-w-0"><label for="mail-password" class="form-label">Password / App Password</label><input id="mail-password" type="password" name="mail_password" autocomplete="new-password" class="form-input" maxlength="4096"><p id="mail-password-note" class="text-xs text-[#9CA3AF] mt-1"><?= $mailConfig['password_configured'] ? 'Password saved. Leave blank to keep it.' : 'No password saved.' ?></p><label class="flex items-center gap-2 text-xs text-[#9CA3AF] mt-2"><input type="checkbox" name="clear_password" value="1"> Remove saved password</label></div>
            </div>
        </fieldset>
        <fieldset class="border-t border-[#374151] pt-4">
            <legend class="text-sm font-semibold text-[#D1D5DB] pr-3">Sender</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="min-w-0"><label for="mail-from-email" class="form-label">From Email</label><input id="mail-from-email" type="email" name="mail_from_email" maxlength="255" class="form-input" value="<?= e($mailConfig['mail_from_email']) ?>"></div>
                <div class="min-w-0"><label for="mail-from-name" class="form-label">From Name</label><input id="mail-from-name" name="mail_from_name" maxlength="255" class="form-input" value="<?= e($mailConfig['mail_from_name']) ?>"></div>
                <div class="min-w-0"><label for="mail-reply-to" class="form-label">Reply-to Email (optional)</label><input id="mail-reply-to" type="email" name="mail_reply_to" maxlength="255" class="form-input" value="<?= e($mailConfig['mail_reply_to']) ?>"></div>
                <div class="min-w-0"><label for="mail-app-url" class="form-label">Application URL</label><input id="mail-app-url" type="url" name="mail_app_url" maxlength="255" class="form-input" placeholder="https://projects.example.edu" value="<?= e($mailConfig['mail_app_url']) ?>"><p class="text-xs text-[#9CA3AF] mt-1">Email links will open this address.</p></div>
            </div>
        </fieldset>
        <fieldset class="border-t border-[#374151] pt-4 space-y-3">
            <legend class="text-sm font-semibold text-[#D1D5DB] pr-3">Email Notifications</legend>
            <?php foreach (['proposals' => 'Proposals: submissions and review decisions', 'reports' => 'Accomplishment reports: submissions and review decisions', 'assignments' => 'Assignments: additions, removals, and program creation'] as $category => $label): ?>
            <label class="flex items-start gap-3 text-sm text-[#D1D5DB]"><input type="checkbox" class="mt-1" name="mail_<?= $category ?>" value="1" <?= $mailConfig['mail_' . $category] === '1' ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
            <?php endforeach; ?>
        </fieldset>
        <div class="flex flex-wrap gap-3 items-center">
            <button type="submit" class="btn-primary"><i class="fas fa-save mr-2" aria-hidden="true"></i>Save Email Settings</button>
            <button type="button" id="mail-test" class="btn-ghost"><i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>Send Test to My Email</button>
            <span id="mail-unsaved" class="text-xs text-[#FCD34D] hidden">Save changes before sending a test.</span>
        </div>
        <p id="mail-feedback" role="status" aria-live="polite" class="text-sm text-[#D1D5DB] break-words"></p>
    </form>
    <section class="mt-8 border-t border-[#374151] pt-5" aria-labelledby="mail-queue-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="mail-queue-heading" class="text-lg font-semibold text-[#F9FAFB]">Email Queue</h2>
            <div class="flex flex-wrap gap-2">
                <button id="mail-retry" type="button" class="btn-ghost text-sm"><i class="fas fa-rotate-right mr-2" aria-hidden="true"></i>Retry Failed</button>
                <button id="mail-process" type="button" class="btn-ghost text-sm"><i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>Send Next 5</button>
                <button id="mail-refresh" type="button" class="btn-ghost" aria-label="Refresh email queue" title="Refresh email queue"><i class="fas fa-arrows-rotate" aria-hidden="true"></i></button>
            </div>
        </div>
        <p id="mail-worker-status" class="text-sm text-[#9CA3AF] mt-2" role="status"></p>
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 my-5">
            <?php foreach (['pending' => 'Pending', 'sending' => 'Sending', 'sent' => 'Accepted by SMTP', 'failed' => 'Failed'] as $status => $label): ?>
            <div><dt class="text-xs text-[#9CA3AF]"><?= $label ?></dt><dd class="text-xl font-semibold text-[#F9FAFB]" data-mail-count="<?= $status ?>">0</dd></div>
            <?php endforeach; ?>
        </dl>
        <div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead class="text-xs text-[#9CA3AF] border-b border-[#374151]"><tr><th scope="col" class="py-3 pr-4">Notification</th><th scope="col" class="py-3 pr-4">Recipient</th><th scope="col" class="py-3 pr-4">Status</th><th scope="col" class="py-3">Delivery Notes</th></tr></thead><tbody id="mail-queue-rows" class="text-[#D1D5DB]"></tbody></table></div>
    </section>
</div>
<script src="<?= e(SITE_URL) ?>/assets/js/mail-settings.js?v=<?= filemtime(__DIR__ . '/../../assets/js/mail-settings.js') ?>" defer></script>

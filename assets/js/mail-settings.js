(() => {
    const panel = document.getElementById('settings-mail');
    if (!panel) return;
    const form = document.getElementById('mail-settings-form');
    const feedback = document.getElementById('mail-feedback');
    const test = document.getElementById('mail-test');
    let busy = false;
    let dirty = false;

    function renderDiagnostic(lines) {
        const wrap = document.getElementById('mail-diagnostic-wrap');
        const output = document.getElementById('mail-diagnostic');
        if (!Array.isArray(lines) || !lines.length) { wrap.classList.add('hidden'); output.textContent = ''; return; }
        output.textContent = lines.join('\n');
        wrap.classList.remove('hidden');
    }

    function updateControls() {
        panel.querySelectorAll('button').forEach(button => { button.disabled = busy; });
        form.querySelectorAll('input:not([type="hidden"]), select').forEach(input => { input.disabled = busy; });
        test.disabled = busy;
        document.getElementById('mail-unsaved').classList.toggle('hidden', !dirty);
        const enabled = document.getElementById('mail-enabled').checked;
        ['mail-host', 'mail-from-email', 'mail-app-url'].forEach(id => { document.getElementById(id).required = enabled; });
        document.getElementById('mail-username').required = enabled && document.getElementById('mail-auth').checked;
    }

    async function request(action, data, label) {
        if (busy) return null;
        busy = true;
        updateControls();
        panel.setAttribute('aria-busy', 'true');
        if (label) feedback.textContent = label;
        let responseResult = null;
        try {
            const options = {headers: {'X-Requested-With': 'XMLHttpRequest'}};
            if (data) {
                data.set('csrf', panel.dataset.csrf);
                options.method = 'POST';
                options.body = data;
            }
            const response = await fetch(`${panel.dataset.endpoint}?action=${action}`, options);
            responseResult = await response.json();
            if (!response.ok || !responseResult.success) throw new Error(responseResult.message || 'The email request failed.');
            if (responseResult.message) feedback.textContent = responseResult.message;
            if (responseResult.diagnostic) renderDiagnostic(responseResult.diagnostic);
            if (responseResult.queue) renderQueue(responseResult.queue);
            return responseResult;
        } catch (error) {
            if (responseResult?.diagnostic) renderDiagnostic(responseResult.diagnostic);
            feedback.textContent = error instanceof SyntaxError ? 'The server could not complete the email request. Please try again.' : error.message;
            return null;
        } finally {
            busy = false;
            panel.setAttribute('aria-busy', 'false');
            updateControls();
        }
    }

    function renderQueue(queue) {
        panel.querySelectorAll('[data-mail-count]').forEach(item => { item.textContent = queue[item.dataset.mailCount] || 0; });
        document.getElementById('mail-worker-status').textContent = !queue.available
            ? 'Email queue is not installed. Contact your system administrator.'
            : queue.last_run ? `Last delivery run: ${queue.last_run}. Automatic delivery requires a scheduled mail worker.`
                : 'No delivery runs yet. Automatic delivery requires a scheduled mail worker.';
        const rows = document.getElementById('mail-queue-rows');
        rows.replaceChildren();
        for (const item of queue.recent) {
            const row = document.createElement('tr');
            row.className = 'border-b border-[#374151]';
            for (const value of [item.title, item.username, item.status === 'sent' ? 'Accepted by SMTP' : item.status, item.last_error || '']) {
                const cell = document.createElement('td');
                cell.className = 'py-3 pr-4 align-top break-words';
                cell.style.maxWidth = '280px';
                cell.style.overflowWrap = 'anywhere';
                cell.textContent = value;
                row.append(cell);
            }
            rows.append(row);
        }
        if (!queue.recent.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'py-5 text-[#9CA3AF]';
            cell.textContent = 'No emails queued yet.';
            row.append(cell);
            rows.append(row);
        }
    }

    form.addEventListener('input', () => { dirty = true; updateControls(); });
    form.addEventListener('change', () => { dirty = true; updateControls(); });
    async function saveSettings() {
        const result = await request('save', new FormData(form), 'Saving email settings...');
        if (!result) return null;
        dirty = false;
        document.getElementById('mail-password').value = '';
        form.elements.clear_password.checked = false;
        document.getElementById('mail-password-note').textContent = result.settings.password_configured ? 'Password saved. Leave blank to keep it.' : 'No password saved.';
        updateControls();
        return result;
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        await saveSettings();
    });
    test.addEventListener('click', async () => {
        if (dirty && !await saveSettings()) return;
        const testData = new FormData();
        testData.set('test_recipient', document.getElementById('mail-test-recipient').value.trim());
        testData.set('send_test', document.getElementById('mail-send-test').checked ? '1' : '0');
        await request('test', testData, 'Testing SMTP settings...');
    });
    function applyPreset(port, encryption) {
        document.getElementById('mail-host').value = 'mail.isu-project-hub.online';
        document.getElementById('mail-port').value = port;
        document.getElementById('mail-encryption').value = encryption;
        document.getElementById('mail-auth').checked = true;
        document.getElementById('mail-username').value = 'support@isu-project-hub.online';
        document.getElementById('mail-from-email').value = 'support@isu-project-hub.online';
        document.getElementById('mail-reply-to').value = 'support@isu-project-hub.online';
        dirty = true;
        updateControls();
        feedback.textContent = 'cPanel preset applied. Enter or keep the saved password, then click Save & Test SMTP.';
    }
    document.getElementById('mail-preset-cpanel-ssl').addEventListener('click', () => applyPreset('465', 'ssl'));
    document.getElementById('mail-preset-cpanel-tls').addEventListener('click', () => applyPreset('587', 'tls'));
    panel.querySelectorAll('.mail-port-preset').forEach(button => button.addEventListener('click', () => {
        document.getElementById('mail-port').value = button.dataset.port;
        dirty = true;
        updateControls();
    }));
    document.getElementById('mail-process').addEventListener('click', () => request('process', new FormData(), 'Sending queued emails...'));
    document.getElementById('mail-retry').addEventListener('click', () => request('retry', new FormData(), 'Queuing failed emails for retry...'));
    document.getElementById('mail-refresh').addEventListener('click', () => request('status', null, 'Refreshing email queue...').then(result => { if (result) feedback.textContent = 'Email queue refreshed.'; }));
    document.addEventListener('mail-settings-open', () => request('status'));
    updateControls();
})();

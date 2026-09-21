(() => {
    const panel = document.getElementById('settings-mail');
    if (!panel) return;
    const form = document.getElementById('mail-settings-form');
    const feedback = document.getElementById('mail-feedback');
    const test = document.getElementById('mail-test');
    let busy = false;
    let dirty = false;

    function updateControls() {
        panel.querySelectorAll('button').forEach(button => { button.disabled = busy; });
        form.querySelectorAll('input:not([type="hidden"]), select').forEach(input => { input.disabled = busy; });
        test.disabled = busy || dirty;
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
        try {
            const options = {headers: {'X-Requested-With': 'XMLHttpRequest'}};
            if (data) {
                data.set('csrf', panel.dataset.csrf);
                options.method = 'POST';
                options.body = data;
            }
            const response = await fetch(`${panel.dataset.endpoint}?action=${action}`, options);
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'The email request failed.');
            if (result.message) feedback.textContent = result.message;
            if (result.queue) renderQueue(result.queue);
            return result;
        } catch (error) {
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
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const result = await request('save', new FormData(form), 'Saving email settings...');
        if (!result) return;
        dirty = false;
        document.getElementById('mail-password').value = '';
        form.elements.clear_password.checked = false;
        document.getElementById('mail-password-note').textContent = result.settings.password_configured ? 'Password saved. Leave blank to keep it.' : 'No password saved.';
        updateControls();
    });
    test.addEventListener('click', () => request('test', new FormData(), 'Sending test email...'));
    document.getElementById('mail-process').addEventListener('click', () => request('process', new FormData(), 'Sending queued emails...'));
    document.getElementById('mail-retry').addEventListener('click', () => request('retry', new FormData(), 'Queuing failed emails for retry...'));
    document.getElementById('mail-refresh').addEventListener('click', () => request('status', null, 'Refreshing email queue...').then(result => { if (result) feedback.textContent = 'Email queue refreshed.'; }));
    document.addEventListener('mail-settings-open', () => request('status'));
    updateControls();
})();

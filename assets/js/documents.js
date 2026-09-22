const documentConfig = JSON.parse(document.getElementById('document-config').textContent);

function documentSize(bytes) {
    return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : (bytes / 1024).toFixed(1) + ' KB';
}

function openDocumentUpload(preselectedType = '', preselectedId = '') {
    const sl = getSlideOver({ size: 'md' });
    const optionMarkup = rows => rows.map(row => `<option value="${escapeHtml(String(row.value))}">${escapeHtml(row.label)}</option>`).join('');
    sl.openForm(documentConfig.scope === 'designations' ? 'Upload Designation' : 'Upload Document', '', `
        <div class="space-y-4">
            <div><label class="form-label" for="document-title">Title</label><input id="document-title" name="title" class="form-input" maxlength="255" required></div>
            <div><label class="form-label" for="document-target">Related Record</label><select id="document-target" name="target" class="form-select" required><option value="">Select...</option>${optionMarkup(documentConfig.targets)}</select></div>
            ${documentConfig.scope === 'designations' ? '' : `<div><label class="form-label" for="document-category">Category</label><select id="document-category" name="category_id" class="form-select" required><option value="">Select...</option>${optionMarkup(documentConfig.categories)}</select></div>`}
            <div><label class="form-label" for="document-file">File</label><input id="document-file" name="file" type="file" accept="${documentConfig.extensions.map(ext => '.' + ext).join(',')}" class="form-input" required><p class="text-xs text-[#9CA3AF] mt-2">Maximum file size: ${documentSize(documentConfig.limit)}</p></div>
            <div><label class="form-label" for="document-description">Remarks</label><textarea id="document-description" name="description" class="form-input" rows="3"></textarea></div>
            <p id="document-upload-status" class="text-sm text-[#9CA3AF]" role="status" aria-live="polite"></p>
            <progress id="document-upload-progress" class="w-full hidden" max="100" value="0" aria-label="Upload progress"></progress>
        </div>`, { showSaveAnother: false });
    const form = sl.element.querySelector('form');
    const fileInput = form.elements.file;
    if (preselectedType && preselectedId) {
        form.elements.target.value = `${preselectedType}:${preselectedId}`;
    }
    fileInput.addEventListener('change', () => {
        if (!form.elements.title.value && fileInput.files[0]) form.elements.title.value = fileInput.files[0].name;
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const file = fileInput.files[0];
        if (!file || file.size > documentConfig.limit) {
            showToast('Choose a file within the current upload limit.', 'error');
            return;
        }
        const payload = new FormData(form);
        const [type, id] = form.elements.target.value.split(':');
        payload.set('entity_type', type);
        payload.set('entity_id', id);
        payload.set('scope', documentConfig.scope);
        payload.set('csrf', documentConfig.csrf);
        sl.setLoading(true);
        const progress = form.querySelector('progress');
        const status = form.querySelector('[role="status"]');
        progress.classList.remove('hidden');
        status.textContent = 'Uploading...';
        // Keep an accidental navigation from interrupting this multipart upload.
        const preventExit = event => { event.preventDefault(); event.returnValue = ''; };
        window.addEventListener('beforeunload', preventExit);
        try {
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', `${SITE_URL}/ajax/documents.php?action=create`);
                xhr.upload.onprogress = event => {
                    if (!event.lengthComputable) return;
                    progress.value = Math.round(event.loaded / event.total * 100);
                    status.textContent = progress.value === 100 ? 'Saving file...' : `Uploading ${progress.value}%`;
                };
                xhr.onload = () => {
                    let data;
                    try { data = JSON.parse(xhr.responseText); }
                    catch { reject(new Error(xhr.status === 413 ? 'File exceeds the hosting upload limit.' : 'The server could not complete the upload.')); return; }
                    if (xhr.status >= 200 && xhr.status < 300 && data.success) resolve(data);
                    else reject(new Error(data.message || 'Upload failed.'));
                };
                xhr.onerror = () => reject(new Error('Connection lost. Please try again.'));
                xhr.send(payload);
            });
            sl.hasUnsavedChanges = false;
            window.removeEventListener('beforeunload', preventExit);
            window.location.reload();
        } catch (error) {
            status.textContent = error.message;
            showToast(error.message, 'error');
        } finally {
            window.removeEventListener('beforeunload', preventExit);
            sl.setLoading(false);
        }
    });
}

async function viewDocument(id, button) {
    const sl = getSlideOver({ size: 'lg' });
    try {
        const data = await fetchWithLoading(`${SITE_URL}/ajax/documents.php?action=get&id=${id}`, button);
        if (data.success === false) throw new Error(data.message);
        const fileUrl = `${SITE_URL}/ajax/documents.php?action=file&id=${Number(data.id)}`;
        const fields = [viewFieldHtml('Category', escapeHtml(data.category_name || 'Uncategorized')), viewFieldHtml('Related Record', escapeHtml(data.entity_type)), viewFieldHtml('Created', escapeHtml(data.created_at))];
        if (data.uploader_name) fields.push(viewFieldHtml('Uploaded by', escapeHtml(data.uploader_name)));
        if (data.file_size !== null) fields.push(viewFieldHtml('Size', documentSize(Number(data.file_size))));
        const attachment = data.has_file ? `<div class="flex flex-wrap gap-2 mb-4"><a href="${fileUrl}" class="btn-ghost" target="_blank" rel="noopener"><i class="fas fa-external-link-alt" aria-hidden="true"></i> Open</a><button type="button" onclick="printDocument(${Number(data.id)})" class="btn-ghost"><i class="fas fa-print" aria-hidden="true"></i> Print</button><a href="${fileUrl}&download=1" class="btn-primary"><i class="fas fa-download" aria-hidden="true"></i> Download</a></div>${data.previewable ? `<iframe src="${fileUrl}" title="File preview" class="w-full border-0 rounded mb-4 bg-white" style="height:50vh"></iframe>` : ''}` : '<p class="text-sm text-[#9CA3AF] mb-4">Attachment unavailable.</p>';
        sl.openView(escapeHtml(data.title), escapeHtml(data.file_name), attachment + viewSectionHtml('File Details', fields), { size: 'lg' });
    } catch (error) {
        showToast(error.message || 'Failed to load file.', 'error');
        sl.close(true);
    }
}

function printDocument(id) {
    const win = window.open(`${SITE_URL}/ajax/documents.php?action=file&id=${Number(id)}`, '_blank', 'noopener');
    if (!win) {
        showToast('Allow pop-ups to print this file.', 'error');
        return;
    }
    win.addEventListener('load', () => win.print(), { once: true });
}

let zrimoClient = null;
let activeViewer = null;
let activeObjectUrl = null;

function viewerBaseUrl() {
    return (window.APP_BASE_URL || '').replace(/\/+$/, '');
}

function safeHtml(value) {
    return window.escapeHtml ? window.escapeHtml(value) : String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function getZrimoClient() {
    if (zrimoClient) return zrimoClient;
    const base = viewerBaseUrl();
    const module = await import(`${base}/assets/vendor/zrimo/index.js`);
    zrimoClient = module.ViewerClient.create({
        assetBaseUrl: new URL(`${base}/assets/vendor/zrimo/`, window.location.href),
    });
    return zrimoClient;
}

function filenameFromDisposition(header) {
    if (!header) return '';
    const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch) {
        try { return decodeURIComponent(utfMatch[1].trim().replace(/^"|"$/g, '')); }
        catch { return utfMatch[1].trim().replace(/^"|"$/g, ''); }
    }
    const asciiMatch = header.match(/filename="?([^";]+)"?/i);
    return asciiMatch ? asciiMatch[1].trim() : '';
}

function filenameFromUrl(url) {
    try {
        const parsed = new URL(url, window.location.href);
        const name = parsed.pathname.split('/').pop() || '';
        return decodeURIComponent(name);
    } catch {
        return '';
    }
}

function extensionFromName(fileName) {
    const clean = String(fileName || '').split('?')[0].toLowerCase();
    const dot = clean.lastIndexOf('.');
    return dot >= 0 ? clean.slice(dot + 1) : '';
}

function revokeActiveObjectUrl() {
    if (activeObjectUrl) {
        URL.revokeObjectURL(activeObjectUrl);
        activeObjectUrl = null;
    }
}

async function renderNativePreview(container, objectUrl, fileName, mimeType, blob) {
    const ext = extensionFromName(fileName);
    if (mimeType === 'application/pdf' || ext === 'pdf') {
        container.innerHTML = `<iframe src="${safeHtml(objectUrl)}" title="${safeHtml(fileName || 'PDF preview')}" class="doc-viewer-native"></iframe>`;
        return true;
    }
    if (mimeType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) {
        container.innerHTML = `<div class="doc-viewer-image-wrap"><img src="${safeHtml(objectUrl)}" alt="${safeHtml(fileName || 'Image preview')}"></div>`;
        return true;
    }
    if (['csv', 'tsv'].includes(ext)) {
        const text = await blob.text();
        container.innerHTML = csvTableHtml(text, ext === 'tsv' ? '\t' : ',');
        return true;
    }
    if (ext === 'txt' || mimeType.startsWith('text/')) {
        const text = await blob.text();
        container.innerHTML = `<pre class="doc-viewer-text">${safeHtml(text)}</pre>`;
        return true;
    }
    return false;
}

function parseDelimited(text, delimiter = ',') {
    const rows = [];
    let row = [];
    let value = '';
    let quoted = false;
    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        const next = text[i + 1];
        if (quoted) {
            if (char === '"' && next === '"') {
                value += '"';
                i++;
            } else if (char === '"') {
                quoted = false;
            } else {
                value += char;
            }
            continue;
        }
        if (char === '"') {
            quoted = true;
        } else if (char === delimiter) {
            row.push(value);
            value = '';
        } else if (char === '\n') {
            row.push(value);
            rows.push(row);
            row = [];
            value = '';
        } else if (char !== '\r') {
            value += char;
        }
    }
    if (value !== '' || row.length) {
        row.push(value);
        rows.push(row);
    }
    return rows;
}

function spreadsheetColumn(index) {
    let label = '';
    let n = index + 1;
    while (n > 0) {
        const rem = (n - 1) % 26;
        label = String.fromCharCode(65 + rem) + label;
        n = Math.floor((n - 1) / 26);
    }
    return label;
}

function csvTableHtml(text, delimiter) {
    const rows = parseDelimited(text, delimiter).filter(row => row.some(cell => cell !== ''));
    if (!rows.length) return '<div class="doc-viewer-empty">No rows found in this file.</div>';
    const maxColumns = Math.max(...rows.map(row => row.length));
    const header = Array.from({ length: maxColumns }, (_, index) => `<th>${spreadsheetColumn(index)}</th>`).join('');
    const body = rows.map((row, rowIndex) => {
        const cells = Array.from({ length: maxColumns }, (_, colIndex) => `<td>${safeHtml(row[colIndex] ?? '')}</td>`).join('');
        return `<tr><th scope="row">${rowIndex + 1}</th>${cells}</tr>`;
    }).join('');
    return `
        <div class="doc-viewer-sheet">
            <table>
                <thead><tr><th class="corner"></th>${header}</tr></thead>
                <tbody>${body}</tbody>
            </table>
        </div>`;
}

function closeDocumentViewer() {
    document.getElementById('document-viewer-modal')?.remove();
    if (activeViewer) {
        activeViewer.destroy().catch(() => {});
        activeViewer = null;
    }
    revokeActiveObjectUrl();
    document.body.style.overflow = '';
}

function documentViewerFallback(fileUrl, fileName) {
    const safeUrl = safeHtml(fileUrl);
    const safeName = safeHtml(fileName || 'Document');
    return `
        <div class="doc-viewer-fallback">
            <i class="fas fa-file-alt" aria-hidden="true"></i>
            <p>Preview is not available for this file in the browser.</p>
            <a href="${safeUrl}" target="_blank" rel="noopener" class="btn-primary">
                <i class="fas fa-external-link-alt mr-1" aria-hidden="true"></i> Open ${safeName}
            </a>
        </div>`;
}

async function loadDocumentIntoViewer(container, options) {
    container.innerHTML = '<div class="doc-viewer-loading"><div class="spinner"></div><span>Loading preview...</span></div>';
    try {
        const response = await fetch(options.url, { credentials: 'same-origin' });
        if (!response.ok) throw new Error('File could not be loaded.');
        let blob = await response.blob();
        const headerName = filenameFromDisposition(response.headers.get('Content-Disposition'));
        const urlName = filenameFromUrl(options.url);
        const fileName = headerName || options.fileName || urlName || options.title || 'document';
        const ext = extensionFromName(fileName);
        let mimeType = blob.type || response.headers.get('Content-Type') || 'application/octet-stream';
        if (['csv', 'tsv'].includes(ext) && !mimeType.startsWith('text/')) {
            blob = new Blob([await blob.arrayBuffer()], { type: ext === 'tsv' ? 'text/tab-separated-values' : 'text/csv' });
            mimeType = blob.type;
        }
        revokeActiveObjectUrl();
        activeObjectUrl = URL.createObjectURL(blob);

        if (await renderNativePreview(container, activeObjectUrl, fileName, mimeType, blob)) return;

        const file = new File([blob], fileName, { type: mimeType });
        const client = await getZrimoClient();

        if (activeViewer) {
            await activeViewer.destroy().catch(() => {});
            activeViewer = null;
        }

        container.innerHTML = '';
        activeViewer = client.createViewer({
            container,
            ui: true,
            fit: 'width',
        });
        await activeViewer.load(file, { fileName });
    } catch (error) {
        console.warn('Document viewer fallback:', error);
        revokeActiveObjectUrl();
        container.innerHTML = documentViewerFallback(options.url, options.fileName || options.title);
    }
}

function openDocumentViewer(options) {
    const title = options.title || 'Document Preview';
    const subtitle = options.subtitle || options.fileName || '';
    const fileUrl = options.url;
    const downloadUrl = options.downloadUrl || `${fileUrl}${fileUrl.includes('?') ? '&' : '?'}download=1`;
    const viewerId = `doc-viewer-${Date.now()}`;
    closeDocumentViewer();
    document.body.style.overflow = 'hidden';
    const modal = document.createElement('div');
    modal.id = 'document-viewer-modal';
    modal.className = 'doc-viewer-modal';
    modal.innerHTML = `
        <div class="doc-viewer-backdrop" data-doc-viewer-close></div>
        <section class="doc-viewer-dialog" role="dialog" aria-modal="true" aria-labelledby="document-viewer-title">
            <header class="doc-viewer-head">
                <div class="min-w-0">
                    <h2 id="document-viewer-title">${safeHtml(title)}</h2>
                    <p>${safeHtml(subtitle)}</p>
                </div>
                <button type="button" class="action-btn" data-doc-viewer-close aria-label="Close preview"><i class="fas fa-times" aria-hidden="true"></i></button>
            </header>
            <div class="doc-viewer-actions">
                <a href="${safeHtml(fileUrl)}" target="_blank" rel="noopener" class="btn-ghost">
                    <i class="fas fa-external-link-alt mr-1" aria-hidden="true"></i> Open
                </a>
                <button type="button" class="btn-ghost" data-doc-viewer-print>
                    <i class="fas fa-print mr-1" aria-hidden="true"></i> Print
                </button>
                ${options.allowDownload === false ? '' : `<a href="${safeHtml(downloadUrl)}" class="btn-primary"><i class="fas fa-download mr-1" aria-hidden="true"></i> Download</a>`}
            </div>
            <div id="${viewerId}" class="doc-viewer-shell" role="region" aria-label="Document preview"></div>
        </section>`;
    document.body.appendChild(modal);
    modal.querySelectorAll('[data-doc-viewer-close]').forEach(button => button.addEventListener('click', closeDocumentViewer));
    modal.querySelector('[data-doc-viewer-print]')?.addEventListener('click', () => printDocumentUrl(fileUrl));
    const escHandler = event => {
        if (event.key === 'Escape') {
            document.removeEventListener('keydown', escHandler);
            closeDocumentViewer();
        }
    };
    document.addEventListener('keydown', escHandler);

    const container = document.getElementById(viewerId);
    if (container) loadDocumentIntoViewer(container, options);
}

function printDocumentUrl(fileUrl) {
    const win = window.open(fileUrl, '_blank', 'noopener');
    if (!win) {
        window.showToast('Allow pop-ups to print this file.', 'error');
        return;
    }
    win.addEventListener('load', () => win.print(), { once: true });
}

function openDocumentGallery(options) {
    const urls = Array.isArray(options.urls) && options.urls.length ? options.urls : [options.url];
    if (urls.length <= 1) return openDocumentViewer({ ...options, url: urls[0] });
    let index = 0;
    const render = () => {
        const currentUrl = urls[index];
        const currentDownloadUrl = `${currentUrl}${currentUrl.includes('?') ? '&' : '?'}download=1`;
        openDocumentViewer({ ...options, url: currentUrl, downloadUrl: currentDownloadUrl });
        const modal = document.getElementById('document-viewer-modal');
        const actions = modal?.querySelector('.doc-viewer-actions');
        if (!actions) return;
        const nav = document.createElement('div');
        nav.className = 'doc-gallery-nav';
        nav.innerHTML = `<button type="button" class="btn-ghost" data-gallery-prev aria-label="Previous file"><i class="fas fa-chevron-left"></i></button><span>File ${index + 1} of ${urls.length}</span><button type="button" class="btn-ghost" data-gallery-next aria-label="Next file"><i class="fas fa-chevron-right"></i></button>`;
        actions.prepend(nav);
        nav.querySelector('[data-gallery-prev]').addEventListener('click', () => { index = (index - 1 + urls.length) % urls.length; render(); });
        nav.querySelector('[data-gallery-next]').addEventListener('click', () => { index = (index + 1) % urls.length; render(); });
    };
    render();
}

window.openDocumentViewer = openDocumentViewer;
window.openDocumentGallery = openDocumentGallery;
window.printDocumentUrl = printDocumentUrl;
window.closeDocumentViewer = closeDocumentViewer;

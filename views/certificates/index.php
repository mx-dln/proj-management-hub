<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-bold text-[#F9FAFB]">Certificates</h1><p class="text-[#9CA3AF] text-sm mt-1">View and print certificates</p></div>
    <?php if (Permissions::canGenerateCertificate()): ?>
        <button onclick="openGenerateCertificate()" class="btn-primary"><i class="fas fa-plus mr-1"></i> Generate Certificate</button>
    <?php endif; ?>
</div>

<div id="certificatesTable">
    <div class="data-table-container">
        <?php if (empty($certificates)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-award"></i></div><h3 class="empty-state-title">No certificates found</h3></div>
        <?php else: ?>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Certificate #</th><th>Recipient</th><th>Activity</th><th>Type</th><th>Issued</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($certificates as $i => $c): ?>
                    <tr class="animate-row" style="animation-delay:<?= $i * 0.05 ?>s">
                        <td data-label="Number"><span class="table-code"><?= e($c['certificate_number']) ?></span></td>
                        <td data-label="Recipient"><span class="table-title"><?= e($c['recipient_name']) ?></span></td>
                        <td data-label="Activity"><span class="text-[#D1D5DB]"><?= e($c['activity_title'] ?? '-') ?></span></td>
                        <td data-label="Type"><span class="badge badge-submitted"><?= ucfirst($c['recipient_type']) ?></span></td>
                        <td data-label="Issued"><span class="table-date"><?= formatDate($c['date_issued']) ?></span></td>
                        <td data-label="Actions">
                            <div class="flex gap-2 justify-end">
                                <button onclick="viewCertificate(<?= $c['id'] ?>, this)" class="btn-ghost">View</button>
                                <button onclick="viewCertificate(<?= $c['id'] ?>, this)" class="btn-primary"><i class="fas fa-print mr-1"></i> Print</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<style>
.certificate-sheet{background:#fff;color:#111827;width:100%;max-width:820px;aspect-ratio:1.414/1;margin:0 auto;padding:18px;box-shadow:0 20px 60px rgba(0,0,0,.35)}
.certificate-border{height:100%;border:6px double #0F643A;padding:9px}
.certificate-inner{height:100%;border:1px solid #C4972C;padding:20px 42px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:space-between;box-sizing:border-box}
.certificate-header{display:flex;flex-direction:column;align-items:center;gap:4px}
.certificate-logo{width:58px;height:58px;object-fit:contain}
.certificate-topline{width:150px;height:3px;background:#C4972C;margin-bottom:6px}
.certificate-kicker{font-size:11px;text-transform:uppercase;letter-spacing:2px;color:#4B5563}
.certificate-school{font-size:27px;font-family:Georgia,serif;font-weight:700;color:#0F643A;line-height:1.05}
.certificate-campus{font-size:11px;text-transform:uppercase;color:#374151}
.certificate-title{font-size:36px;font-family:Georgia,serif;color:#111827;line-height:1.05}
.certificate-copy{font-size:14px;color:#374151;line-height:1.35}
.certificate-copy.wide{max-width:680px}
.certificate-recipient{font-size:34px;font-family:Georgia,serif;font-weight:700;color:#0F643A;line-height:1.12;padding-bottom:6px}
.certificate-recipient-line{width:min(420px,70%);height:2px;background:#C4972C;margin-top:4px;margin-bottom:2px}
.certificate-activity{font-size:18px;font-weight:700;max-width:720px;color:#111827;line-height:1.2}
.certificate-body{display:flex;flex-direction:column;align-items:center;gap:7px}
.certificate-footer{width:100%;display:grid;grid-template-columns:1fr 86px 1fr;align-items:end;gap:28px}
.signature-line{border-top:1px solid #111827;margin-bottom:7px}
.signature-name{font-size:12px;font-weight:700}
.signature-role{font-size:9px;text-transform:uppercase;color:#6B7280}
.seal-logo{width:56px;height:56px;object-fit:contain;margin:0 auto}
@media (max-width:760px){.certificate-sheet{max-width:100%;padding:12px}.certificate-inner{padding:16px}.certificate-title{font-size:28px}.certificate-recipient{font-size:26px}.certificate-recipient-line{width:82%;margin-top:4px}.certificate-activity{font-size:16px}.certificate-footer{grid-template-columns:1fr;gap:14px}.certificate-seal{order:-1}}
@page{size:A4 landscape;margin:0}
@media print{html,body{margin:0!important;background:#fff!important}body *{visibility:hidden!important}.certificate-sheet,.certificate-sheet *{visibility:visible!important}.certificate-sheet{position:fixed!important;left:0!important;top:0!important;width:297mm!important;height:210mm!important;max-width:none!important;aspect-ratio:auto!important;padding:12mm!important;box-shadow:none!important;box-sizing:border-box!important}.certificate-footer{grid-template-columns:1fr 86px 1fr!important}.no-print,.slideover-header,.slideover-footer{display:none!important}}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const siteUrl = <?= json_encode(SITE_URL) ?>;
const certificateLogoUrl = `${siteUrl}/assets/images/logo.png`;

function openGenerateCertificate() {
    const sl = getSlideOver({ size: 'md', title: 'Generate Certificate', subtitle: 'Create certificate' });
    const formHtml = `
        <div class="form-section">
            <h4 class="form-section-title">Certificate Information</h4>
            <div class="form-grid">
                ${fieldHtml({ name: 'recipient_name', label: 'Recipient Name', required: true, fullWidth: true })}
                ${fieldHtml({ name: 'recipient_type', label: 'Type', type: 'select', required: true, options: [{value:'participant',label:'Participant'},{value:'resource_speaker',label:'Resource Speaker'}] })}
                ${fieldHtml({ name: 'activity_id', label: 'Activity', type: 'select', options: <?= json_encode(array_map(fn($a) => ['value'=>$a['id'],'label'=>$a['title']], db()->query("SELECT id, title FROM extension_activities WHERE deleted_at IS NULL ORDER BY title")->fetchAll())) ?> })}
                ${fieldHtml({ name: 'date_issued', label: 'Date Issued', type: 'date', value: '<?= date('Y-m-d') ?>' })}
            </div>
        </div>`;
    sl.openForm('Generate Certificate', 'Create certificate', formHtml, { showSaveAnother: true });
    document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitSlideOverForm(sl, `${siteUrl}/ajax/certificates.php?action=create`, new FormData(e.target), () => location.reload());
    });
}

async function viewCertificate(id, btn) {
    const sl = getSlideOver({ size: 'md', title: 'Certificate Details', subtitle: 'Loading...' });
    try {
        const data = await fetchWithLoading(`${siteUrl}/ajax/certificates.php?action=get&id=${id}`, btn);
        const content = certificatePreviewHtml(data);
        sl.openView(data.certificate_number, data.recipient_name, content, { size: 'lg' });
    } catch (err) { console.error('Error:', err); showToast(err.message || 'Failed to load', 'error'); sl.close(true); }
}

function certificatePreviewHtml(data) {
    const title = data.recipient_type === 'resource_speaker' ? 'Certificate of Appreciation' : 'Certificate of Participation';
    const roleText = data.recipient_type === 'resource_speaker'
        ? 'for generously sharing expertise as a resource speaker during'
        : 'for active participation in';
    const activity = escapeHtml(data.activity_title || 'an Extension Training Services activity');
    const issued = formatDate(data.date_issued);
    const certNo = escapeHtml(data.certificate_number || '');
    const qr = escapeHtml(data.qr_code || certNo);

    return `
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 no-print">
            <div>${getStatusBadge(data.status)} <span class="text-xs text-[#6B7280] ml-2">${certNo}</span></div>
            <div class="flex gap-2">
                <button onclick="printCertificate()" class="btn-ghost"><i class="fas fa-print mr-1"></i> Print</button>
                <button onclick="downloadCertificatePdf('${certNo}', this)" class="btn-primary"><i class="fas fa-download mr-1"></i> Download PDF</button>
            </div>
        </div>
        <div id="certificate-print-area" class="certificate-sheet">
            <div class="certificate-border">
                <div class="certificate-inner">
                    <div class="certificate-header">
                        <div class="certificate-topline"></div>
                        <img src="${certificateLogoUrl}" alt="ISU Logo" class="certificate-logo">
                        <div class="certificate-kicker">Republic of the Philippines</div>
                        <div class="certificate-school">Isabela State University</div>
                        <div class="certificate-campus">Cauayan Campus - Extension Training Services</div>
                    </div>
                    <div class="certificate-body">
                        <div class="certificate-title">${title}</div>
                        <div class="certificate-copy">This certificate is proudly presented to</div>
                        <div class="certificate-recipient">${escapeHtml(data.recipient_name)}</div>
                        <div class="certificate-recipient-line"></div>
                        <div class="certificate-copy wide">${roleText}</div>
                        <div class="certificate-activity">${activity}</div>
                        <div class="certificate-copy">Issued on ${issued}.</div>
                    </div>
                    <div class="certificate-footer">
                        <div class="certificate-signature">
                            <div class="signature-line"></div>
                            <div class="signature-name">ETS Coordinator</div>
                            <div class="signature-role">Program Authority</div>
                        </div>
                        <div class="certificate-seal">
                            <img src="${certificateLogoUrl}" alt="ISU Logo" class="seal-logo">
                            <div class="text-[10px] text-[#6B7280] mt-2">Verify: ${qr}</div>
                        </div>
                        <div class="certificate-signature">
                            <div class="signature-line"></div>
                            <div class="signature-name">Campus Executive Officer</div>
                            <div class="signature-role">Approving Official</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

function printCertificate() {
    const certificate = document.getElementById('certificate-print-area');
    if (!certificate) return;
    const frame = document.createElement('iframe');
    frame.style.position = 'fixed';
    frame.style.right = '0';
    frame.style.bottom = '0';
    frame.style.width = '0';
    frame.style.height = '0';
    frame.style.border = '0';
    document.body.appendChild(frame);

    const doc = frame.contentWindow.document;
    doc.open();
    doc.write(buildCertificateDocument(certificate.outerHTML));
    doc.close();

    waitForFrameImages(frame).then(() => {
        frame.contentWindow.focus();
        frame.contentWindow.print();
        setTimeout(() => frame.remove(), 1200);
    });
}

async function downloadCertificatePdf(filename, button) {
    const certificate = document.getElementById('certificate-print-area');
    if (!certificate) return;
    if (!window.html2canvas || !window.jspdf) {
        showToast('PDF tools are still loading. Please try again.', 'warning');
        return;
    }
    const oldText = button ? button.innerHTML : '';
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Preparing';
    }
    let holder = null;
    try {
        const exportNode = certificate.cloneNode(true);
        exportNode.style.width = '1123px';
        exportNode.style.height = '794px';
        exportNode.style.maxWidth = 'none';
        exportNode.style.aspectRatio = 'auto';
        exportNode.style.boxShadow = 'none';
        exportNode.querySelector('.certificate-recipient')?.style.setProperty('padding-bottom', '14px');
        exportNode.querySelector('.certificate-recipient-line')?.style.setProperty('margin-top', '8px');
        holder = document.createElement('div');
        holder.style.position = 'fixed';
        holder.style.left = '-9999px';
        holder.style.top = '0';
        holder.style.background = '#fff';
        holder.appendChild(exportNode);
        document.body.appendChild(holder);
        const canvas = await html2canvas(exportNode, { scale: 2, backgroundColor: '#ffffff', useCORS: true });
        const imgData = canvas.toDataURL('image/png');
        const pdf = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        pdf.addImage(imgData, 'PNG', 0, 0, pageWidth, pageHeight);
        pdf.save(`${filename || 'certificate'}.pdf`);
    } catch (err) {
        console.error('PDF download failed:', err);
        showToast('Failed to generate PDF', 'error');
    } finally {
        if (holder) holder.remove();
        if (button) {
            button.disabled = false;
            button.innerHTML = oldText;
        }
    }
}

function buildCertificateDocument(certificateHtml) {
    return `<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <base href="${siteUrl}/">
    <title>Certificate</title>
    <style>
        @page{size:A4 landscape;margin:0}
        html,body{margin:0;background:#fff;width:297mm;height:210mm;overflow:hidden}
        .certificate-sheet{background:#fff;color:#111827;width:297mm;height:210mm;max-width:none;aspect-ratio:auto;margin:0;padding:12mm;box-shadow:none;box-sizing:border-box;font-family:Arial,sans-serif}
        .certificate-border{height:100%;border:6px double #0F643A;padding:9px;box-sizing:border-box}
        .certificate-inner{height:100%;border:1px solid #C4972C;padding:20px 42px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:space-between;box-sizing:border-box}
        .certificate-header{display:flex;flex-direction:column;align-items:center;gap:4px}
        .certificate-logo{width:58px;height:58px;object-fit:contain}
        .certificate-topline{width:150px;height:3px;background:#C4972C;margin-bottom:6px}
        .certificate-kicker{font-size:11px;text-transform:uppercase;letter-spacing:2px;color:#4B5563}
        .certificate-school{font-size:27px;font-family:Georgia,serif;font-weight:700;color:#0F643A;line-height:1.05}
        .certificate-campus{font-size:11px;text-transform:uppercase;color:#374151}
        .certificate-body{display:flex;flex-direction:column;align-items:center;gap:7px}
        .certificate-title{font-size:36px;font-family:Georgia,serif;color:#111827;line-height:1.05}
        .certificate-copy{font-size:14px;color:#374151;line-height:1.35}
        .certificate-copy.wide{max-width:680px}
        .certificate-recipient{font-size:34px;font-family:Georgia,serif;font-weight:700;color:#0F643A;line-height:1.12;padding-bottom:6px}
        .certificate-recipient-line{width:420px;height:2px;background:#C4972C;margin-top:4px;margin-bottom:2px}
        .certificate-activity{font-size:18px;font-weight:700;max-width:720px;color:#111827;line-height:1.2}
        .certificate-footer{width:100%;display:grid;grid-template-columns:1fr 86px 1fr;align-items:end;gap:28px}
        .signature-line{border-top:1px solid #111827;margin-bottom:7px}
        .signature-name{font-size:12px;font-weight:700}
        .signature-role{font-size:9px;text-transform:uppercase;color:#6B7280}
        .seal-logo{width:56px;height:56px;object-fit:contain;margin:0 auto}
        .text-\\[10px\\]{font-size:10px}.text-\\[\\#6B7280\\]{color:#6B7280}.mt-2{margin-top:.5rem}
    </style>
</head>
<body>${certificateHtml}</body>
</html>`;
}

function waitForFrameImages(frame) {
    const images = Array.from(frame.contentDocument.images || []);
    return Promise.all(images.map(img => img.complete ? Promise.resolve() : new Promise(resolve => {
        img.onload = resolve;
        img.onerror = resolve;
    })));
}

</script>

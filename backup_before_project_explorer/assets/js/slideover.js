// SlideOver Component - ISU-Cauayan ETS Hub

class SlideOver {
    constructor(options = {}) {
        this.size = options.size || 'md'; // sm, md, lg
        this.title = options.title || '';
        this.subtitle = options.subtitle || '';
        this.onClose = options.onClose || null;
        this.hasUnsavedChanges = false;
        this.element = null;
        this.backdrop = null;
        this.isOpen = false;
    }

    open(content, size) {
        if (this.isOpen) this.close(true);
        
        const panelSize = size || this.size;
        this.isOpen = true;
        this.hasUnsavedChanges = false;

        // Create backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'slideover-backdrop';
        this.backdrop.addEventListener('click', () => this.close());
        document.body.appendChild(this.backdrop);

        // Create panel
        this.element = document.createElement('div');
        this.element.className = `slideover slideover-${panelSize}`;
        this.element.setAttribute('role', 'dialog');
        this.element.setAttribute('aria-modal', 'true');
        this.element.setAttribute('aria-label', this.title);
        this.element.innerHTML = `
            <div class="slideover-header">
                <div>
                    <h2 class="text-lg font-semibold text-[#F9FAFB] slideover-title">${this.title}</h2>
                    <p class="text-xs text-[#6B7280] mt-1 slideover-subtitle">${this.subtitle}</p>
                </div>
                <button class="action-btn slideover-close" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="slideover-body">
                ${content}
            </div>
        `;

        // Close button
        this.element.querySelector('.slideover-close').addEventListener('click', () => this.close());

        document.body.appendChild(this.element);

        // Focus trap
        this.setupFocusTrap();

        // ESC key
        this.escHandler = (e) => {
            if (e.key === 'Escape') this.close();
        };
        document.addEventListener('keydown', this.escHandler);

        // Track unsaved changes
        this.trackChanges();
    }

    openForm(title, subtitle, formHtml, options = {}) {
        const size = options.size || 'md';
        const showSaveAnother = options.showSaveAnother !== false;
        
        const footerHtml = `
            <div class="slideover-footer">
                <button type="button" class="btn-ghost slideover-cancel">Cancel</button>
                ${showSaveAnother ? '<button type="button" class="btn-save-another" id="saveAndCreate">Save & Create Another</button>' : ''}
                <button type="submit" form="slideoverForm" class="btn-primary slideover-save" id="saveBtn">
                    <span class="save-text">Save</span>
                    <span class="save-spinner hidden"><span class="btn-spinner"></span>Saving...</span>
                </button>
            </div>
        `;

        this.title = title;
        this.subtitle = subtitle;
        this.open(`
            <form id="slideoverForm" autocomplete="off">
                ${formHtml}
            </form>
            ${footerHtml}
        `, size);

        // Cancel button
        this.element.querySelector('.slideover-cancel')?.addEventListener('click', () => this.close());

        return this.element;
    }

    openView(title, subtitle, content, options = {}) {
        const size = options.size || 'md';
        const editCallback = options.onEdit || null;

        let footerHtml = `
            <div class="slideover-footer">
                <button type="button" class="btn-ghost slideover-close-view">Close</button>
                ${editCallback ? '<button type="button" class="btn-primary slideover-edit"><i class="fas fa-edit mr-2"></i>Edit</button>' : ''}
            </div>
        `;

        this.title = title;
        this.subtitle = subtitle;
        this.open(`
            <div class="slideover-view-content">
                ${content}
            </div>
            ${footerHtml}
        `, size);

        this.element.querySelector('.slideover-close-view')?.addEventListener('click', () => this.close());
        
        if (editCallback) {
            this.element.querySelector('.slideover-edit')?.addEventListener('click', () => {
                this.close();
                setTimeout(editCallback, 300);
            });
        }

        return this.element;
    }

    close(force = false) {
        if (!this.isOpen) return;

        if (this.hasUnsavedChanges && !force) {
            this.showUnsavedDialog();
            return;
        }

        this.isOpen = false;

        // Animate out
        if (this.element) {
            this.element.classList.add('closing');
        }
        if (this.backdrop) {
            this.backdrop.classList.add('closing');
        }

        setTimeout(() => {
            this.element?.remove();
            this.backdrop?.remove();
            this.element = null;
            this.backdrop = null;
            document.body.style.overflow = '';
        }, 200);

        // Remove ESC handler
        if (this.escHandler) {
            document.removeEventListener('keydown', this.escHandler);
        }

        if (this.onClose) this.onClose();
    }

    showUnsavedDialog() {
        const dialog = document.createElement('div');
        dialog.className = 'fixed inset-0 z-[60] flex items-center justify-center p-4';
        dialog.innerHTML = `
            <div class="fixed inset-0 bg-black/60" onclick="this.parentElement.remove()"></div>
            <div class="relative bg-[#1F2937] rounded-2xl shadow-2xl max-w-sm w-full p-6 border border-[#374151] modal-enter">
                <div class="text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-[#78350F] flex items-center justify-center mb-4">
                        <i class="fas fa-exclamation-triangle text-[#FCD34D] text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-[#F9FAFB] mb-2">Unsaved Changes</h3>
                    <p class="text-[#9CA3AF] text-sm mb-6">You have unsaved changes. Do you want to discard them?</p>
                    <div class="flex gap-3 justify-center">
                        <button class="btn-ghost unsaved-continue">Continue Editing</button>
                        <button class="bg-[#DC2626] hover:bg-[#B91C1C] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors unsaved-discard">Discard</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(dialog);

        dialog.querySelector('.unsaved-continue').addEventListener('click', () => dialog.remove());
        dialog.querySelector('.unsaved-discard').addEventListener('click', () => {
            dialog.remove();
            this.hasUnsavedChanges = false;
            this.close(true);
        });
    }

    trackChanges() {
        const form = this.element?.querySelector('form');
        if (!form) return;

        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const initialValue = input.value;
            input.addEventListener('input', () => {
                this.hasUnsavedChanges = input.value !== initialValue;
            });
            input.addEventListener('change', () => {
                this.hasUnsavedChanges = input.value !== initialValue;
            });
        });
    }

    setupFocusTrap() {
        if (!this.element) return;
        const focusable = this.element.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable.length === 0) return;
        
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        this.element.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;
            if (e.shiftKey) {
                if (document.activeElement === first) { last.focus(); e.preventDefault(); }
            } else {
                if (document.activeElement === last) { first.focus(); e.preventDefault(); }
            }
        });

        setTimeout(() => first.focus(), 100);
    }

    setLoading(loading) {
        const saveBtn = this.element?.querySelector('.slideover-save');
        if (!saveBtn) return;

        const saveText = saveBtn.querySelector('.save-text');
        const saveSpinner = saveBtn.querySelector('.save-spinner');

        if (loading) {
            saveBtn.disabled = true;
            saveText?.classList.add('hidden');
            saveSpinner?.classList.remove('hidden');
        } else {
            saveBtn.disabled = false;
            saveText?.classList.remove('hidden');
            saveSpinner?.classList.add('hidden');
        }
    }

    setFieldValue(name, value) {
        const input = this.element?.querySelector(`[name="${name}"]`);
        if (input) input.value = value ?? '';
    }

    getFieldValues() {
        const form = this.element?.querySelector('form');
        if (!form) return {};
        return Object.fromEntries(new FormData(form));
    }

    showErrors(errors) {
        // Clear previous errors
        this.element?.querySelectorAll('.field-error').forEach(el => el.remove());
        this.element?.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));

        if (!errors || typeof errors !== 'object') return;

        Object.entries(errors).forEach(([field, message]) => {
            const input = this.element?.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('border-[#DC2626]');
                const error = document.createElement('p');
                error.className = 'field-error';
                error.textContent = message;
                input.parentElement.appendChild(error);
            }
        });
    }

    setTitle(title) {
        const el = this.element?.querySelector('.slideover-title');
        if (el) el.textContent = title;
    }

    setSubtitle(subtitle) {
        const el = this.element?.querySelector('.slideover-subtitle');
        if (el) el.textContent = subtitle;
    }
}

// Global SlideOver instance
let currentSlideOver = null;

function getSlideOver(options) {
    if (currentSlideOver && currentSlideOver.isOpen) {
        currentSlideOver.close(true);
    }
    currentSlideOver = new SlideOver(options);
    return currentSlideOver;
}

// Helper to submit form via AJAX and handle response
async function submitSlideOverForm(slideover, url, formData, refreshCallback) {
    slideover.setLoading(true);
    slideover.showErrors(null);

    try {
        const response = await fetch(url, { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            showToast(data.message || 'Saved successfully');
            slideover.hasUnsavedChanges = false;
            slideover.close(true);
            if (refreshCallback) refreshCallback(data);
        } else if (data.errors) {
            slideover.showErrors(data.errors);
            showToast('Please fix the errors below', 'error');
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    } catch (err) {
        showToast('An error occurred', 'error');
    } finally {
        slideover.setLoading(false);
    }
}

// Helper to load data and populate form
async function loadFormData(url) {
    const response = await fetch(url);
    return await response.json();
}

// Helper to build form field HTML
function fieldHtml(options) {
    const { name, label, type = 'text', value = '', required = false, placeholder = '', options: selectOptions, fullWidth = false, rows = 3, help = '' } = options;
    
    const requiredAttr = required ? 'required' : '';
    const requiredMark = required ? '<span class="text-[#DC2626] ml-1">*</span>' : '';
    const widthClass = fullWidth ? 'full-width' : '';
    
    let inputHtml = '';
    
    switch (type) {
        case 'select':
            inputHtml = `<select name="${name}" class="form-select" ${requiredAttr}>
                <option value="">${placeholder || 'Select...'}</option>
                ${(selectOptions || []).map(o => `<option value="${o.value}" ${o.value == value ? 'selected' : ''}>${o.label}</option>`).join('')}
            </select>`;
            break;
        case 'textarea':
            inputHtml = `<textarea name="${name}" rows="${rows}" class="form-input" placeholder="${placeholder}" ${requiredAttr}>${value || ''}</textarea>`;
            break;
        case 'date':
            inputHtml = `<input type="date" name="${name}" value="${value || ''}" class="form-input" ${requiredAttr}>`;
            break;
        case 'number':
            inputHtml = `<input type="number" name="${name}" value="${value || ''}" class="form-input" placeholder="${placeholder}" step="0.01" min="0" ${requiredAttr}>`;
            break;
        case 'file':
            inputHtml = `<input type="file" name="${name}" class="form-input text-sm" ${requiredAttr}>`;
            break;
        default:
            inputHtml = `<input type="${type}" name="${name}" value="${escapeHtml(value || '')}" class="form-input" placeholder="${placeholder}" ${requiredAttr}>`;
    }
    
    return `
        <div class="${widthClass}">
            <label class="form-label">${label}${requiredMark}</label>
            ${inputHtml}
            ${help ? `<p class="text-xs text-[#6B7280] mt-1">${help}</p>` : ''}
        </div>
    `;
}

// Helper to build view field HTML
function viewFieldHtml(label, value) {
    return `
        <div class="view-field">
            <p class="view-field-label">${label}</p>
            <p class="view-field-value">${value || '-'}</p>
        </div>
    `;
}

// Helper to build view section
function viewSectionHtml(title, fields) {
    return `
        <div class="form-section">
            <h4 class="form-section-title">${title}</h4>
            <div class="form-grid">
                ${fields.join('')}
            </div>
        </div>
    `;
}

// ============================================
// BUTTON LOADING HELPERS
// ============================================

function setButtonLoading(btn, loading) {
    if (!btn) return;
    if (loading) {
        btn.dataset.originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-spinner fa-spin text-sm';
        } else {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i>';
        }
    } else {
        btn.disabled = false;
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
        if (btn.dataset.originalHtml) {
            btn.innerHTML = btn.dataset.originalHtml;
        }
    }
}

// Wrap fetch with button loading
async function fetchWithLoading(url, btn) {
    setButtonLoading(btn, true);
    try {
        const response = await fetch(url);
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text);
            throw new Error('Server returned invalid response');
        }
    } catch (err) {
        setButtonLoading(btn, false);
        throw err;
    } finally {
        setButtonLoading(btn, false);
    }
}

// Global loading for action buttons
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.action-btn[data-loading]');
    if (btn && !btn.disabled) {
        setButtonLoading(btn, true);
        // Auto-reset after 5 seconds as safety
        setTimeout(() => setButtonLoading(btn, false), 5000);
    }
});

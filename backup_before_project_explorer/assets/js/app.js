// ISU-Cauayan ETS Hub - Core JavaScript v2

const SITE_URL = window.location.origin;

// ============================================
// SIDEBAR
// ============================================

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

function toggleSidebarGroup(groupKey) {
    const group = document.querySelector(`[data-group="${groupKey}"]`);
    if (!group) return;
    
    const isOpen = group.classList.contains('open');
    group.classList.toggle('open');
    
    // Save state to localStorage
    const expanded = getExpandedGroups();
    if (isOpen) {
        delete expanded[groupKey];
    } else {
        expanded[groupKey] = true;
    }
    localStorage.setItem('sidebar_expanded', JSON.stringify(expanded));
    
    // Update aria
    const header = group.querySelector('.sidebar-group-header');
    if (header) header.setAttribute('aria-expanded', !isOpen);
}

function getExpandedGroups() {
    try {
        return JSON.parse(localStorage.getItem('sidebar_expanded') || '{}');
    } catch { return {}; }
}

function restoreSidebarState() {
    const expanded = getExpandedGroups();
    Object.keys(expanded).forEach(key => {
        const group = document.querySelector(`[data-group="${key}"]`);
        if (group) {
            group.classList.add('open');
            const header = group.querySelector('.sidebar-group-header');
            if (header) header.setAttribute('aria-expanded', 'true');
        }
    });
}

// Keyboard support for sidebar groups
document.addEventListener('keydown', function(e) {
    if (e.target.classList.contains('sidebar-group-header') && (e.key === 'Enter' || e.key === ' ')) {
        e.preventDefault();
        e.target.click();
    }
});

// ============================================
// DROPDOWNS
// ============================================

function toggleNotifDropdown() {
    const dropdown = document.getElementById('notif-dropdown');
    dropdown.classList.toggle('hidden');
    if (!dropdown.classList.contains('hidden')) {
        loadNotifications();
    }
    document.getElementById('user-dropdown').classList.add('hidden');
}

function toggleUserDropdown() {
    document.getElementById('user-dropdown').classList.toggle('hidden');
    document.getElementById('notif-dropdown').classList.add('hidden');
}

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#notif-dropdown-container')) {
        document.getElementById('notif-dropdown').classList.add('hidden');
    }
    if (!e.target.closest('#user-dropdown-container')) {
        document.getElementById('user-dropdown').classList.add('hidden');
    }
});

// ============================================
// NOTIFICATIONS
// ============================================

function loadNotifications() {
    fetch(SITE_URL + '/ajax/notifications.php?action=get_unread')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('notif-list');
            if (!data || data.length === 0) {
                list.innerHTML = '<div class="p-6 text-center"><i class="fas fa-bell-slash text-2xl text-[#374151] mb-2"></i><p class="text-[#6B7280] text-sm">No new notifications</p></div>';
                return;
            }
            list.innerHTML = data.map(n => `
                <a href="${n.action_url || SITE_URL + '/index.php?module=notifications'}" class="block px-4 py-3 hover:bg-[#324152] border-b border-[#374151] transition-colors">
                    <p class="text-sm font-medium text-[#F9FAFB]">${escapeHtml(n.title)}</p>
                    <p class="text-xs text-[#6B7280] mt-1 line-clamp-2">${escapeHtml(n.message)}</p>
                    <p class="text-[10px] text-[#4B5563] mt-1">${timeAgo(n.created_at)}</p>
                </a>
            `).join('');
        })
        .catch(() => {
            document.getElementById('notif-list').innerHTML = '<div class="p-4 text-center text-[#6B7280] text-sm">Failed to load</div>';
        });
}

function markAllNotifRead() {
    fetch(SITE_URL + '/ajax/notifications.php?action=mark_all_read')
        .then(r => r.json())
        .then(() => {
            const badge = document.getElementById('notif-badge');
            if (badge) badge.remove();
            loadNotifications();
            showToast('All notifications marked as read');
        });
}

// ============================================
// TOAST NOTIFICATIONS
// ============================================

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const config = {
        success: { bg: 'bg-[#14532D] border-[#1E7A4B]', text: 'text-[#86EFAC]', icon: 'fa-check-circle' },
        error: { bg: 'bg-[#7F1D1D] border-[#991B1B]', text: 'text-[#FCA5A5]', icon: 'fa-exclamation-circle' },
        warning: { bg: 'bg-[#78350F] border-[#92400E]', text: 'text-[#FCD34D]', icon: 'fa-exclamation-triangle' },
        info: { bg: 'bg-[#1E3A8A] border-[#2563EB]', text: 'text-[#BFDBFE]', icon: 'fa-info-circle' },
    };
    const c = config[type] || config.success;
    
    const toast = document.createElement('div');
    toast.className = `toast-notification flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl border ${c.bg} ${c.text} min-w-[300px] max-w-[400px]`;
    toast.innerHTML = `
        <i class="fas ${c.icon}"></i>
        <span class="text-sm flex-1">${escapeHtml(message)}</span>
        <button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 transition-opacity"><i class="fas fa-times text-xs"></i></button>
    `;
    container.appendChild(toast);
    setTimeout(() => { toast.classList.add('hiding'); setTimeout(() => toast.remove(), 300); }, 4000);
}

// ============================================
// CONFIRMATION MODAL
// ============================================

let confirmCallback = null;

function showConfirm(title, message, callback, btnText = 'Confirm') {
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-message').textContent = message;
    document.getElementById('confirm-btn').textContent = btnText;
    document.getElementById('confirm-modal').classList.remove('hidden');
    confirmCallback = callback;
}

function closeConfirmModal() {
    document.getElementById('confirm-modal').classList.add('hidden');
    confirmCallback = null;
}

document.getElementById('confirm-btn')?.addEventListener('click', function() {
    if (confirmCallback) confirmCallback();
    closeConfirmModal();
});

// ============================================
// LOADING
// ============================================

function showLoading() { document.getElementById('loading-overlay').classList.remove('hidden'); }
function hideLoading() { document.getElementById('loading-overlay').classList.add('hidden'); }

// ============================================
// AJAX HELPERS
// ============================================

function ajax(url, data = null, method = 'POST') {
    const options = { method, headers: { 'Content-Type': 'application/json' } };
    if (data && method !== 'GET') options.body = JSON.stringify(data);
    return fetch(url, options).then(r => r.json());
}

function ajaxForm(url, formData) {
    return fetch(url, { method: 'POST', body: formData }).then(r => r.json());
}

function submitForm(form, url, callback) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showLoading();
        const formData = new FormData(form);
        ajaxForm(url, formData)
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast(data.message || 'Saved successfully');
                    if (callback) callback(data);
                } else {
                    showToast(data.message || 'Save failed', 'error');
                }
            })
            .catch(() => { hideLoading(); showToast('An error occurred', 'error'); });
    });
}

// ============================================
// UTILITIES
// ============================================

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return new Date(dateStr).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
}

function formatCurrency(amount) {
    return '₱' + parseFloat(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Status Badge (JavaScript version)
function getStatusBadge(status) {
    const classes = {
        'draft': 'badge-draft',
        'pending': 'badge-pending',
        'submitted': 'badge-submitted',
        'under_review': 'badge-submitted',
        'resubmitted': 'badge-submitted',
        'active': 'badge-active',
        'ongoing': 'badge-active',
        'approved': 'badge-approved',
        'completed': 'badge-completed',
        'archived': 'badge-draft',
        'returned': 'badge-returned',
        'rejected': 'badge-rejected',
        'cancelled': 'badge-cancelled',
        'expired': 'badge-expired',
        'terminated': 'badge-terminated',
        'planned': 'badge-planned',
        'in_progress': 'badge-in_progress',
    };
    const labels = {
        'under_review': 'Under Review',
        'in_progress': 'In Progress',
        'resubmitted': 'Resubmitted',
    };
    const cls = classes[status] || 'badge-draft';
    const label = labels[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ') : '-');
    return `<span class="badge ${cls}">${label}</span>`;
}

// UCFirst helper
function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function confirmDelete(url, itemName = 'item') {
    showConfirm(
        'Delete ' + itemName,
        'Are you sure you want to delete this ' + itemName + '? This action cannot be undone.',
        () => {
            showLoading();
            ajax(url)
                .then(data => {
                    hideLoading();
                    if (data.success) { showToast(data.message || 'Deleted'); setTimeout(() => location.reload(), 1000); }
                    else { showToast(data.message || 'Delete failed', 'error'); }
                })
                .catch(() => { hideLoading(); showToast('An error occurred', 'error'); });
        },
        'Delete'
    );
}

// ============================================
// USER MANUAL
// ============================================

function openUserManual() {
    const sl = getSlideOver({ size: 'lg', title: 'User Manual', subtitle: 'How to use the ETS Project Hub' });
    
    const content = `
        <div class="space-y-6">
            <!-- Getting Started -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-rocket mr-2 text-[#86EFAC]"></i>Getting Started</h4>
                <div class="space-y-3 text-sm text-[#D1D5DB]">
                    <p>Welcome to the <strong class="text-[#F9FAFB]">ISU-Cauayan Extension Training Services Project Management Hub</strong>. This system manages the complete lifecycle of university extension programs.</p>
                    <div class="p-3 rounded-lg bg-[#111827] border border-[#374151]">
                        <p class="font-medium text-[#F9FAFB] mb-1">System Hierarchy</p>
                        <p>Program → Project → Component → Extension Activity</p>
                        <p class="text-xs text-[#6B7280] mt-1">Every project belongs to a Program. Every Component belongs to a Project. Every Activity belongs to a Component.</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-chart-pie mr-2 text-[#86EFAC]"></i>Dashboard</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p>The dashboard shows your key metrics at a glance:</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li><strong>Statistics Cards</strong> - Total programs, projects, components, activities</li>
                        <li><strong>Charts</strong> - Visual breakdowns of your data</li>
                        <li><strong>Recent Activities</strong> - Latest system actions</li>
                        <li><strong>Notifications</strong> - Unread alerts and updates</li>
                    </ul>
                </div>
            </div>

            <!-- Programs -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-layer-group mr-2 text-[#86EFAC]"></i>Program Management</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p><strong class="text-[#F9FAFB]">Programs</strong> are college-level extension initiatives.</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Click <strong>+ New Program</strong> to create</li>
                        <li>Click <strong>View</strong> to see details</li>
                        <li>Click <strong>Edit</strong> to modify</li>
                        <li>Add team members to programs</li>
                    </ul>
                </div>
            </div>

            <!-- Projects -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-folder-open mr-2 text-[#86EFAC]"></i>Project Management</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p><strong class="text-[#F9FAFB]">Projects</strong> are specific extension projects within programs.</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Projects must belong to a Program</li>
                        <li>Assign a Project Leader</li>
                        <li>Track budget, timeline, and progress</li>
                        <li>Create Components within projects</li>
                    </ul>
                </div>
            </div>

            <!-- Components & Activities -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-puzzle-piece mr-2 text-[#86EFAC]"></i>Components & Activities</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p><strong class="text-[#F9FAFB]">Components</strong> group related activities together.</p>
                    <p><strong class="text-[#F9FAFB]">Extension Activities</strong> are the actual implementation events (trainings, seminars, workshops).</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Each activity has participants, attendance, photos</li>
                        <li>Resource speakers can be recorded</li>
                        <li>Evaluations and outputs are tracked</li>
                    </ul>
                </div>
            </div>

            <!-- Proposals -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-file-alt mr-2 text-[#86EFAC]"></i>Proposals</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p>Project Leaders submit proposals for ETS approval.</p>
                    <p><strong>Workflow:</strong> Draft → Submitted → Under Review → Approved/Returned/Rejected</p>
                </div>
            </div>

            <!-- MOA -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-handshake mr-2 text-[#86EFAC]"></i>MOA (Memorandum of Agreement)</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p>Track agreements with partner agencies.</p>
                    <p>Monitor expiration dates and renewal requirements.</p>
                </div>
            </div>

            <!-- Reports -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-clipboard-check mr-2 text-[#86EFAC]"></i>Accomplishment Reports</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p>Submit quarterly, semi-annual, annual, and terminal reports.</p>
                    <p><strong>Workflow:</strong> Draft → Submitted → Under Review → Approved/Returned</p>
                </div>
            </div>

            <!-- Certificates -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-award mr-2 text-[#86EFAC]"></i>Certificates</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p>Generate certificates for activity participants and resource speakers.</p>
                    <p>Types: Participation, Completion, Recognition, Appreciation</p>
                </div>
            </div>

            <!-- Roles -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-users mr-2 text-[#86EFAC]"></i>User Roles</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <div class="p-2 rounded bg-[#111827] border border-[#374151]">
                        <p><strong class="text-[#FCA5A5]">Administrator</strong> - Full system access, manages users and settings</p>
                    </div>
                    <div class="p-2 rounded bg-[#111827] border border-[#374151]">
                        <p><strong class="text-[#86EFAC]">Faculty</strong> - Manages assigned projects, submits proposals and reports</p>
                    </div>
                    <div class="p-2 rounded bg-[#111827] border border-[#374151]">
                        <p><strong class="text-[#BFDBFE]">Viewer</strong> - Read-only access to dashboards and reports</p>
                    </div>
                </div>
            </div>

            <!-- Assignments -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-tasks mr-2 text-[#86EFAC]"></i>Assignments</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p>Leadership is assigned, not a login role:</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li><strong>Program Leader</strong> - Manages a program</li>
                        <li><strong>Project Leader</strong> - Manages a project</li>
                        <li><strong>Study Leader</strong> - Manages a component</li>
                        <li><strong>Activity Leader</strong> - Manages an activity</li>
                    </ul>
                    <p class="text-xs text-[#6B7280]">A faculty member can hold multiple assignments across different entities.</p>
                </div>
            </div>

            <!-- SlideOver Panels -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-columns mr-2 text-[#86EFAC]"></i>Using SlideOver Panels</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p>This system uses <strong class="text-[#F9FAFB]">SlideOver panels</strong> for all forms:</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Click <strong>+ New</strong> to open create form</li>
                        <li>Click <strong>View</strong> to see details</li>
                        <li>Click <strong>Edit</strong> to modify</li>
                        <li>Press <strong>ESC</strong> to close</li>
                        <li>Changes are saved via AJAX (no page reload)</li>
                    </ul>
                </div>
            </div>

            <!-- Notifications -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-bell mr-2 text-[#86EFAC]"></i>Notifications</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <p>Click the <strong>bell icon</strong> in the top navigation to view notifications.</p>
                    <p>You'll be notified when:</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Proposals are approved or returned</li>
                        <li>Reports are reviewed</li>
                        <li>You're assigned to a project</li>
                        <li>MOA is created or expiring</li>
                    </ul>
                </div>
            </div>

            <!-- Tips -->
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-lightbulb mr-2 text-[#FCD34D]"></i>Tips</h4>
                <div class="space-y-2 text-sm text-[#D1D5DB]">
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Use the <strong>sidebar groups</strong> to navigate between modules</li>
                        <li>Your sidebar state is saved automatically</li>
                        <li>All forms validate before saving</li>
                        <li>Unsaved changes trigger a warning</li>
                        <li>Use <strong>breadcrumbs</strong> to track your location</li>
                    </ul>
                </div>
            </div>
        </div>
    `;

    sl.openView('User Manual', 'ISU-Cauayan ETS Project Hub v1.0', content, { size: 'lg' });
}

// ============================================
// INIT
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    restoreSidebarState();
});

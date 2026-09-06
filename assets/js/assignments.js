// Assignment Management Module - ISU-Cauayan ETS Hub

class AssignmentManager {
    constructor(siteUrl) {
        this.siteUrl = siteUrl;
        this.currentSlideover = null;
    }

    // Open assignment SlideOver for any entity
    openAssignments(entityType, entityId, entityName) {
        const titles = {
            'program': 'Program Assignments',
            'project': 'Project Assignments',
            'component': 'Component Assignments',
            'activity': 'Activity Assignments'
        };

        const sl = getSlideOver({ size: 'lg', title: titles[entityType] || 'Assignments', subtitle: entityName });
        this.currentSlideover = sl;

        // Build content
        const content = `
            <div id="assignmentContent">
                <div class="flex items-center justify-center py-8">
                    <div class="spinner"></div>
                </div>
            </div>
        `;

        const footerHtml = `
            <div class="slideover-footer">
                <button type="button" class="btn-ghost" onclick="assignmentManager.close()">Close</button>
                <button type="button" class="btn-primary" id="saveAssignmentsBtn" onclick="assignmentManager.saveAssignments('${entityType}', ${entityId})">
                    <span class="save-text">Save Assignments</span>
                    <span class="save-spinner hidden"><span class="btn-spinner"></span>Saving...</span>
                </button>
            </div>
        `;

        sl.open(content + footerHtml, 'lg');
        this.loadAssignments(entityType, entityId);
    }

    // Load current assignments
    async loadAssignments(entityType, entityId) {
        try {
            const response = await fetch(`${this.siteUrl}/ajax/assignments.php?action=get&entity_type=${entityType}&entity_id=${entityId}`);
            const data = await response.json();

            if (!data.success) {
                showToast(data.message || 'Failed to load', 'error');
                return;
            }

            this.renderAssignments(entityType, entityId, data);
        } catch (err) {
            showToast('Failed to load assignments', 'error');
        }
    }

    // Render assignment form
    renderAssignments(entityType, entityId, data) {
        const { assignments, availableFaculty, leaderAssignmentType } = data;
        const leader = assignments.find(a => a.assignment_type === 'leader');
        const members = assignments.filter(a => a.assignment_type === 'member');

        const leaderLabel = {
            'program': 'Program Leader',
            'project': 'Project Leader',
            'component': 'Study Leader',
            'activity': 'Activity Leader'
        }[entityType] || 'Leader';

        const container = document.getElementById('assignmentContent');
        container.innerHTML = `
            <!-- Leader Section -->
            <div class="form-section">
                <h4 class="form-section-title">
                    <i class="fas fa-crown mr-2 text-[#F5D20C]"></i>${leaderLabel}
                </h4>
                <p class="text-xs text-[#6B7280] mb-3">Only one leader can be assigned. Select the faculty member responsible for this ${entityType}.</p>
                <select id="leaderSelect" class="form-select" onchange="assignmentManager.hasChanges = true">
                    <option value="">Select ${leaderLabel}</option>
                    ${availableFaculty.map(f => `
                        <option value="${f.id}" ${leader && leader.faculty_id == f.id ? 'selected' : ''}>
                            ${escapeHtml(f.last_name)}, ${escapeHtml(f.first_name)} - ${escapeHtml(f.department_name || 'No Department')}
                        </option>
                    `).join('')}
                </select>
            </div>

            <!-- Members Section -->
            <div class="form-section">
                <h4 class="form-section-title">
                    <i class="fas fa-users mr-2 text-[#86EFAC]"></i>Members
                </h4>
                <p class="text-xs text-[#6B7280] mb-3">Select multiple faculty members to assign to this ${entityType}.</p>
                
                <!-- Search and Add -->
                <div class="relative mb-3">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#6B7280] text-sm"></i>
                    <input type="text" id="memberSearch" placeholder="Search by name, department, or email..." 
                           class="filter-input pl-10" oninput="assignmentManager.filterMembers()">
                </div>
                
                <div id="memberList" class="max-h-48 overflow-y-auto border border-[#374151] rounded-lg">
                    ${availableFaculty.map(f => {
                        const isMember = members.some(m => m.faculty_id == f.id);
                        const isLeader = leader && leader.faculty_id == f.id;
                        return `
                            <label class="flex items-center gap-3 px-3 py-2 hover:bg-[#324152] cursor-pointer border-b border-[#2D3748] last:border-0 member-item"
                                   data-name="${(f.first_name + ' ' + f.last_name).toLowerCase()}"
                                   data-department="${(f.department_name || '').toLowerCase()}"
                                   data-email="${(f.email || '').toLowerCase()}">
                                <input type="checkbox" name="members[]" value="${f.id}" 
                                       ${isMember ? 'checked' : ''} ${isLeader ? 'disabled checked' : ''}
                                       class="rounded border-[#374151] text-[#0F643A] focus:ring-[#0F643A]"
                                       onchange="assignmentManager.hasChanges = true">
                                <div class="avatar" style="width:32px;height:32px;font-size:12px">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-[#F9FAFB]">${escapeHtml(f.first_name)} ${escapeHtml(f.last_name)}</p>
                                    <p class="text-xs text-[#6B7280]">${escapeHtml(f.department_name || 'No Department')} ${f.email ? '· ' + escapeHtml(f.email) : ''}</p>
                                </div>
                                ${isLeader ? '<span class="text-xs px-2 py-0.5 bg-[#78350F] text-[#FCD34D] rounded-full">Leader</span>' : ''}
                            </label>
                        `;
                    }).join('')}
                </div>
                
                <p class="text-xs text-[#6B7280] mt-2">
                    <span id="selectedCount"><?= count($members) ?></span> member(s) selected
                </p>
            </div>

            <!-- Current Assignments Summary -->
            <div class="form-section">
                <h4 class="form-section-title">
                    <i class="fas fa-list mr-2 text-[#BFDBFE]"></i>Current Assignments
                </h4>
                <div id="assignmentsSummary" class="space-y-2">
                    ${this.renderAssignmentsSummary(assignments)}
                </div>
            </div>
        `;

        // Add checkbox change listener
        container.querySelectorAll('input[name="members[]"]').forEach(cb => {
            cb.addEventListener('change', () => this.updateSelectedCount());
        });
    }

    renderAssignmentsSummary(assignments) {
        if (assignments.length === 0) {
            return '<p class="text-sm text-[#6B7280]">No assignments yet.</p>';
        }

        return assignments.map(a => `
            <div class="flex items-center gap-3 p-3 rounded-lg bg-[#111827] border border-[#374151]">
                <div class="avatar" style="width:32px;height:32px;font-size:12px">
                    <i class="fas fa-user"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-[#F9FAFB]">${escapeHtml(a.first_name)} ${escapeHtml(a.last_name)}</p>
                    <p class="text-xs text-[#6B7280]">${escapeHtml(a.department_name || 'No Department')} · Assigned ${formatDate(a.assigned_at)}</p>
                </div>
                <span class="badge ${a.assignment_type === 'leader' ? 'badge-approved' : 'badge-submitted'}">
                    ${a.assignment_type === 'leader' ? 'Leader' : 'Member'}
                </span>
            </div>
        `).join('');
    }

    // Filter members by search
    filterMembers() {
        const query = document.getElementById('memberSearch').value.toLowerCase();
        const items = document.querySelectorAll('.member-item');
        items.forEach(item => {
            const name = item.dataset.name || '';
            const dept = item.dataset.department || '';
            const email = item.dataset.email || '';
            const match = name.includes(query) || dept.includes(query) || email.includes(query);
            item.style.display = match ? '' : 'none';
        });
    }

    // Update selected count
    updateSelectedCount() {
        const checked = document.querySelectorAll('input[name="members[]"]:checked:not(:disabled)');
        const countEl = document.getElementById('selectedCount');
        if (countEl) countEl.textContent = checked.length;
    }

    // Save assignments
    async saveAssignments(entityType, entityId) {
        const leaderId = document.getElementById('leaderSelect').value;
        const memberCheckboxes = document.querySelectorAll('input[name="members[]"]:checked:not(:disabled)');
        const memberIds = Array.from(memberCheckboxes).map(cb => parseInt(cb.value));

        const saveBtn = document.getElementById('saveAssignmentsBtn');
        const saveText = saveBtn.querySelector('.save-text');
        const saveSpinner = saveBtn.querySelector('.save-spinner');
        
        saveBtn.disabled = true;
        saveText.classList.add('hidden');
        saveSpinner.classList.remove('hidden');

        try {
            const response = await fetch(`${this.siteUrl}/ajax/assignments.php?action=save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    entity_type: entityType,
                    entity_id: entityId,
                    leader_id: leaderId ? parseInt(leaderId) : null,
                    member_ids: memberIds
                })
            });
            const data = await response.json();

            if (data.success) {
                showToast(data.message || 'Assignments saved');
                this.hasChanges = false;
                this.close();
                // Refresh current page
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || 'Failed', 'error');
            }
        } catch (err) {
            showToast('An error occurred', 'error');
        } finally {
            saveBtn.disabled = false;
            saveText.classList.remove('hidden');
            saveSpinner.classList.add('hidden');
        }
    }

    // Close SlideOver
    close() {
        if (this.currentSlideover) {
            this.currentSlideover.close(true);
            this.currentSlideover = null;
        }
    }
}

// Global instance
let assignmentManager = null;

document.addEventListener('DOMContentLoaded', function() {
    assignmentManager = new AssignmentManager(SITE_URL);
});

// Helper to open assignments from HTML onclick
function openAssignments(entityType, entityId, entityName) {
    if (!assignmentManager) {
        assignmentManager = new AssignmentManager(SITE_URL);
    }
    assignmentManager.openAssignments(entityType, entityId, entityName);
}

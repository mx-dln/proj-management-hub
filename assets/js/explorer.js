// Project Explorer - Management-Based Explorer v3

class ProjectExplorer {
    constructor() {
        this.selectedNode = null;
        this.expandedNodes = new Set();
        this.loadedNodes = new Set();
        this.loadExpandedState();
    }

    init() {
        this.loadTree();
        this.setupSearch();
    }

    // ============================================
    // TREE - Navigation only
    // ============================================
    async loadTree() {
        const container = document.getElementById('explorer-tree');
        if (!container) return;
        container.innerHTML = '<div class="p-4"><div class="skeleton skeleton-line long"></div><div class="skeleton skeleton-line medium"></div></div>';

        try {
            const response = await fetch(`${SITE_URL}/ajax/explorer.php?action=tree&parent_type=root`);
            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch (e) { container.innerHTML = '<div class="p-4 text-center text-[#FCA5A5] text-sm">Server error</div>'; return; }

            if (data.success) {
                container.innerHTML = '';
                if (!data.nodes || data.nodes.length === 0) {
                    container.innerHTML = '<div class="p-6 text-center"><i class="fas fa-folder-open text-3xl text-[#374151] mb-3"></i><p class="text-sm text-[#6B7280]">No programs found</p></div>';
                    return;
                }
                data.nodes.forEach(node => this.renderNode(container, node, 0));
                this.restoreExpandedState();
            } else {
                container.innerHTML = `<div class="p-4 text-center text-[#FCA5A5] text-sm">${escapeHtml(data.message || 'Failed')}</div>`;
            }
        } catch (err) {
            container.innerHTML = '<div class="p-4 text-center text-[#FCA5A5] text-sm">Failed to load</div>';
        }
    }

    renderNode(parent, node, depth) {
        const nodeEl = document.createElement('div');
        nodeEl.className = 'tree-node';
        nodeEl.dataset.id = node.id;
        nodeEl.dataset.type = node.type;

        const isActive = this.selectedNode && this.selectedNode.id == node.id && this.selectedNode.type === node.type;
        const isExpanded = this.expandedNodes.has(`${node.type}-${node.id}`);
        const isAdmin = typeof userRole !== 'undefined' && userRole === 'admin';
        const isFolder = node.type === 'components_folder' || node.type === 'activities_folder';

        const showActions = isAdmin && !isFolder;

        nodeEl.innerHTML = `
            <div class="tree-item ${isActive ? 'active' : ''}" style="padding-left: ${16 + depth * 20}px" 
                 data-node-key="${node.type}-${node.id}">
                ${node.hasChildren ? `
                    <span class="node-chevron ${isExpanded ? 'expanded' : ''}" onclick="event.stopPropagation(); explorer.toggleNode('${node.type}', ${node.id})">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                ` : '<span class="node-chevron" style="width:16px"></span>'}
                <span class="node-icon" onclick="explorer.selectNode('${node.type}', ${node.id})" style="cursor:pointer">
                    <i class="fas ${node.icon}"></i>
                </span>
                <span class="node-title" onclick="explorer.selectNode('${node.type}', ${node.id})" style="cursor:pointer">
                    ${escapeHtml(node.title)}
                    ${node.count !== undefined ? `<span class="text-[#6B7280] text-xs ml-1">(${node.count})</span>` : ''}
                </span>
                ${node.progress !== undefined ? `<span class="node-badge">${node.progress}%</span>` : ''}
                ${showActions ? `
                    <div class="node-actions">
                        <button class="node-action-btn" onclick="event.stopPropagation(); explorer.editEntity('${node.type}', ${node.id})" title="Edit">
                            <i class="fas fa-pencil"></i>
                        </button>
                        <button class="node-action-btn danger" onclick="event.stopPropagation(); explorer.deleteEntity('${node.type}', ${node.id}, '${escapeHtml(node.title)}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ` : ''}
            </div>
            <div class="tree-children ${isExpanded ? 'expanded' : ''}" id="children-${node.type}-${node.id}"></div>
        `;

        parent.appendChild(nodeEl);

        if (isExpanded && node.hasChildren) {
            this.loadChildren(node.type, node.id);
        }
    }

    async toggleNode(type, id) {
        const key = `${type}-${id}`;
        const isExpanded = this.expandedNodes.has(key);
        if (isExpanded) { this.expandedNodes.delete(key); } else { this.expandedNodes.add(key); }

        const childrenEl = document.getElementById(`children-${key}`);
        const chevron = document.querySelector(`[data-node-key="${key}"] .node-chevron`);

        if (isExpanded) {
            if (childrenEl) childrenEl.classList.remove('expanded');
            if (chevron) chevron.classList.remove('expanded');
        } else {
            if (childrenEl) childrenEl.classList.add('expanded');
            if (chevron) chevron.classList.add('expanded');
            if (!this.loadedNodes.has(key)) {
                await this.loadChildren(type, id);
            }
        }
        this.saveExpandedState();
    }

    async loadChildren(type, id) {
        const key = `${type}-${id}`;
        const childrenEl = document.getElementById(`children-${key}`);
        if (!childrenEl) return;
        childrenEl.innerHTML = '<div class="p-3"><div class="skeleton skeleton-line medium"></div></div>';

        try {
            const response = await fetch(`${SITE_URL}/ajax/explorer.php?action=tree&parent_type=${type}&parent_id=${id}`);
            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch (e) { childrenEl.innerHTML = '<div class="p-3 text-center text-[#FCA5A5] text-sm">Error</div>'; return; }

            if (data.success) {
                childrenEl.innerHTML = '';
                if (!data.nodes || data.nodes.length === 0) {
                    childrenEl.innerHTML = '<div class="p-3 text-center text-[#6B7280] text-sm">No items</div>';
                } else {
                    const depth = this.getDepth(type);
                    data.nodes.forEach(node => this.renderNode(childrenEl, node, depth));
                }
                this.loadedNodes.add(key);
            } else {
                childrenEl.innerHTML = `<div class="p-3 text-center text-[#FCA5A5] text-sm">${escapeHtml(data.message || 'Failed')}</div>`;
            }
        } catch (err) {
            childrenEl.innerHTML = '<div class="p-3 text-center text-[#FCA5A5] text-sm">Failed</div>';
        }
    }

    getDepth(type) {
        const depths = { 'program': 0, 'project': 1, 'components_folder': 2, 'activities_folder': 2 };
        return (depths[type] || 0) + 1;
    }

    // ============================================
    // SELECT & DETAIL
    // ============================================
    async selectNode(type, id) {
        document.querySelectorAll('.tree-item').forEach(el => el.classList.remove('active'));
        const nodeEl = document.querySelector(`[data-node-key="${type}-${id}"]`);
        if (nodeEl) nodeEl.classList.add('active');
        this.selectedNode = { type, id };
        await this.loadDetail(type, id);
    }

    async loadDetail(type, id) {
        const detailEl = document.getElementById('explorer-detail');
        if (!detailEl) return;
        detailEl.innerHTML = '<div class="p-6"><div class="skeleton skeleton-line long" style="height:32px"></div><div class="skeleton skeleton-line medium mt-4"></div></div>';

        try {
            const response = await fetch(`${SITE_URL}/ajax/explorer.php?action=detail&type=${type}&id=${id}`);
            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch (e) { detailEl.innerHTML = '<div class="explorer-empty"><div class="explorer-empty-icon"><i class="fas fa-exclamation-triangle"></i></div><p class="explorer-empty-title">Error</p></div>'; return; }

            if (data.success && data.data) {
                this.renderDetail(type, data.data);
            } else {
                detailEl.innerHTML = `<div class="explorer-empty"><div class="explorer-empty-icon"><i class="fas fa-exclamation-triangle"></i></div><p class="explorer-empty-title">Not Found</p><p class="explorer-empty-text">${escapeHtml(data.message || 'Item not found')}</p></div>`;
            }
        } catch (err) {
            detailEl.innerHTML = '<div class="explorer-empty"><div class="explorer-empty-icon"><i class="fas fa-exclamation-triangle"></i></div><p class="explorer-empty-title">Error</p></div>';
        }
    }

    renderDetail(type, data) {
        const renderers = {
            'program': () => this.renderProgramDetail(data),
            'project': () => this.renderProjectDetail(data),
            'components_folder': () => this.renderComponentsManagement(data),
            'activities_folder': () => this.renderActivitiesManagement(data),
        };
        if (renderers[type]) renderers[type]();
    }

    // Program Detail
    renderProgramDetail(data) {
        const detailEl = document.getElementById('explorer-detail');
        const isAdmin = userRole === 'admin';
        detailEl.innerHTML = `
            <div class="detail-header">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="detail-title">${escapeHtml(data.title)}</h2>
                        <p class="detail-subtitle">${escapeHtml(data.program_code || '')} &middot; ${getStatusBadge(data.status)}</p>
                    </div>
                    ${isAdmin || userRole === 'faculty' ? `<div class="flex gap-2"><button onclick="explorer.openCreateProject(${data.id})" class="btn-primary text-sm"><i class="fas fa-plus mr-1"></i>New Project</button>${isAdmin ? '<button onclick="explorer.editEntity(\'program\', ' + data.id + ')" class="btn-ghost text-sm"><i class="fas fa-pencil mr-1"></i>Edit</button>' : ''}</div>` : ''}
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="stat-card"><p class="text-xs text-[#9CA3AF] uppercase">Projects</p><p class="text-2xl font-bold text-[#F9FAFB] mt-1">${data.projects_count || 0}</p></div>
                <div class="stat-card"><p class="text-xs text-[#9CA3AF] uppercase">Components</p><p class="text-2xl font-bold text-[#F9FAFB] mt-1">${data.components_count || 0}</p></div>
                <div class="stat-card"><p class="text-xs text-[#9CA3AF] uppercase">Activities</p><p class="text-2xl font-bold text-[#F9FAFB] mt-1">${data.activities_count || 0}</p></div>
                <div class="stat-card"><p class="text-xs text-[#9CA3AF] uppercase">Faculty</p><p class="text-2xl font-bold text-[#F9FAFB] mt-1">${data.faculty_count || 0}</p></div>
            </div>
            <div class="detail-grid mb-6">
                <div class="detail-field"><p class="detail-field-label">College</p><p class="detail-field-value">${escapeHtml(data.college || '-')}</p></div>
                <div class="detail-field"><p class="detail-field-label">Campus</p><p class="detail-field-value">${escapeHtml(data.campus || '-')}</p></div>
                <div class="detail-field"><p class="detail-field-label">Budget</p><p class="detail-field-value">${formatCurrency(data.budget_allocation)}</p></div>
                <div class="detail-field"><p class="detail-field-label">Duration</p><p class="detail-field-value">${formatDate(data.start_date)} - ${formatDate(data.end_date)}</p></div>
            </div>
            ${data.description ? `<div class="detail-section"><h4 class="detail-section-title">Description</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>` : ''}
            ${data.objectives ? `<div class="detail-section"><h4 class="detail-section-title">Objectives</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.objectives)}</p></div>` : ''}
            ${data.assignments && data.assignments.length > 0 ? `<div class="detail-section"><h4 class="detail-section-title">Assigned Faculty</h4><div class="space-y-2">${data.assignments.map(a => `<div class="flex items-center gap-3 p-3 rounded-lg bg-[#111827] border border-[#374151]"><div class="avatar" style="width:32px;height:32px;font-size:12px"><i class="fas fa-user"></i></div><div class="flex-1"><p class="text-sm font-medium text-[#F9FAFB]">${escapeHtml(a.first_name)} ${escapeHtml(a.last_name)}</p><p class="text-xs text-[#6B7280]">${escapeHtml(a.department_name || '')}</p></div><span class="badge ${a.assignment_type === 'leader' ? 'badge-approved' : 'badge-submitted'}">${a.assignment_type}</span></div>`).join('')}</div></div>` : ''}
        `;
    }

    // Project Detail
    renderProjectDetail(data) {
        const detailEl = document.getElementById('explorer-detail');
        const isAdmin = userRole === 'admin';
        detailEl.innerHTML = `
            <div class="detail-header">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="detail-title">${escapeHtml(data.title)}</h2>
                        <p class="detail-subtitle">${escapeHtml(data.project_code || '')} &middot; ${getStatusBadge(data.status)}</p>
                    </div>
                    ${userRole === 'admin' || userRole === 'faculty' ? `<div class="flex gap-2"><button onclick="explorer.openAssignments('project', ${data.id}, '${escapeHtml(data.title)}')" class="btn-ghost text-sm"><i class="fas fa-users mr-1"></i>Assign</button><button onclick="explorer.editEntity('project', ${data.id})" class="btn-ghost text-sm"><i class="fas fa-pencil mr-1"></i>Edit</button></div>` : ''}
                </div>
            </div>
            <div class="detail-grid mb-6">
                <div class="detail-field"><p class="detail-field-label">Program</p><p class="detail-field-value">${escapeHtml(data.program_title || '-')}</p></div>
                <div class="detail-field"><p class="detail-field-label">Budget</p><p class="detail-field-value">${formatCurrency(data.budget)}</p></div>
                <div class="detail-field"><p class="detail-field-label">Duration</p><p class="detail-field-value">${formatDate(data.start_date)} - ${formatDate(data.end_date)}</p></div>
                <div class="detail-field"><p class="detail-field-label">Components</p><p class="detail-field-value">${data.components_count || 0}</p></div>
                <div class="detail-field"><p class="detail-field-label">Location</p><p class="detail-field-value">${escapeHtml(data.location || '-')}</p></div>
                <div class="detail-field"><p class="detail-field-label">Progress</p><div class="flex items-center gap-2 mt-1"><div class="progress-bar flex-1"><div class="progress-fill" style="width:${data.completion_percentage || 0}%"></div></div><span class="text-sm text-[#9CA3AF]">${data.completion_percentage || 0}%</span></div></div>
            </div>
            ${data.description ? `<div class="detail-section"><h4 class="detail-section-title">Description</h4><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>` : ''}
            ${data.assignments && data.assignments.length > 0 ? `<div class="detail-section"><h4 class="detail-section-title">Team Members</h4><div class="space-y-2">${data.assignments.map(a => `<div class="flex items-center gap-3 p-3 rounded-lg bg-[#111827] border border-[#374151]"><div class="avatar" style="width:32px;height:32px;font-size:12px"><i class="fas fa-user"></i></div><div class="flex-1"><p class="text-sm font-medium text-[#F9FAFB]">${escapeHtml(a.first_name)} ${escapeHtml(a.last_name)}</p></div><span class="badge ${a.assignment_type === 'leader' ? 'badge-approved' : 'badge-submitted'}">${a.assignment_type}</span></div>`).join('')}</div></div>` : ''}
        `;
    }

    // Components Management
    renderComponentsManagement(data) {
        const detailEl = document.getElementById('explorer-detail');
        const isAdmin = userRole === 'admin';
        const components = data.components || [];
        
        detailEl.innerHTML = `
            <div class="detail-header">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="detail-title">Components</h2>
                        <p class="detail-subtitle">Manage components for ${escapeHtml(data.project_title || 'this project')}</p>
                    </div>
                    ${isAdmin ? `<button onclick="explorer.openCreateComponent(${data.project_id})" class="btn-primary text-sm"><i class="fas fa-plus mr-1"></i>Add Component</button>` : ''}
                </div>
            </div>
            <div class="mb-4">
                <input type="text" placeholder="Search components..." class="filter-input" oninput="explorer.filterComponents(this.value)">
            </div>
            <div id="components-list" class="data-table-container">
                ${components.length === 0 ? `
                    <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-puzzle-piece"></i></div><h3 class="empty-state-title">No components</h3><p class="empty-state-text">Add components to organize activities.</p></div>
                ` : `
                    <div class="overflow-x-auto"><table class="data-table"><thead><tr>
                        <th>Name</th><th>Status</th><th>Progress</th><th>Leader</th><th>Actions</th>
                    </tr></thead><tbody>
                        ${components.map((c, i) => `
                            <tr class="animate-row" style="animation-delay:${i * 0.05}s" data-search="${(c.title || '').toLowerCase()}">
                                <td data-label="Name"><span class="table-title">${escapeHtml(c.title)}</span><p class="text-xs text-[#6B7280]">${escapeHtml(c.component_code || '')}</p></td>
                                <td data-label="Status">${getStatusBadge(c.status)}</td>
                                <td data-label="Progress"><div class="flex items-center gap-2"><div class="progress-bar w-16"><div class="progress-fill" style="width:${c.completion_percentage || 0}%"></div></div><span class="text-xs text-[#9CA3AF]">${c.completion_percentage || 0}%</span></div></td>
                                <td data-label="Leader"><span class="text-[#D1D5DB]">${escapeHtml(c.leader_name || '-')}</span></td>
                                <td data-label="Actions">
                                    <div class="flex items-center justify-end gap-1">
                                        <button onclick="explorer.viewComponent(${c.id})" class="action-btn" title="View"><i class="fas fa-eye text-sm"></i></button>
                                        <button onclick="explorer.editEntity('component', ${c.id})" class="action-btn" title="Edit"><i class="fas fa-pencil text-sm"></i></button>
                                        <button onclick="explorer.deleteEntity('component', ${c.id}, '${escapeHtml(c.title)}')" class="action-btn action-btn-danger" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody></table></div>
                `}
            </div>
        `;
    }

    // Activities Management
    renderActivitiesManagement(data) {
        const detailEl = document.getElementById('explorer-detail');
        const isAdmin = userRole === 'admin';
        const activities = data.activities || [];
        
        detailEl.innerHTML = `
            <div class="detail-header">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="detail-title">Activities</h2>
                        <p class="detail-subtitle">Manage activities for ${escapeHtml(data.project_title || 'this project')}</p>
                    </div>
                    ${isAdmin ? `<button onclick="explorer.openCreateActivity(${data.project_id})" class="btn-primary text-sm"><i class="fas fa-plus mr-1"></i>Add Activity</button>` : ''}
                </div>
            </div>
            <div id="activities-list" class="data-table-container">
                ${activities.length === 0 ? `
                    <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-calendar-check"></i></div><h3 class="empty-state-title">No activities</h3><p class="empty-state-text">Add activities to this project.</p></div>
                ` : `
                    <div class="overflow-x-auto"><table class="data-table"><thead><tr>
                        <th>Activity</th><th>Component</th><th>Venue</th><th>Date</th><th>Status</th><th>Actions</th>
                    </tr></thead><tbody>
                        ${activities.map((a, i) => `
                            <tr class="animate-row" style="animation-delay:${i * 0.05}s">
                                <td data-label="Activity"><span class="table-title">${escapeHtml(a.title)}</span></td>
                                <td data-label="Component"><span class="text-[#D1D5DB]">${escapeHtml(a.component_title || '-')}</span></td>
                                <td data-label="Venue"><span class="text-[#D1D5DB]">${escapeHtml(a.venue || '-')}</span></td>
                                <td data-label="Date"><span class="table-date">${a.start_datetime ? new Date(a.start_datetime).toLocaleDateString('en-PH', {month:'short',day:'numeric',year:'numeric'}) : '-'}</span></td>
                                <td data-label="Status">${getStatusBadge(a.status)}</td>
                                <td data-label="Actions">
                                    <div class="flex items-center justify-end gap-1">
                                        <button onclick="explorer.viewActivity(${a.id})" class="action-btn" title="View"><i class="fas fa-eye text-sm"></i></button>
                                        <button onclick="explorer.editEntity('activity', ${a.id})" class="action-btn" title="Edit"><i class="fas fa-pencil text-sm"></i></button>
                                        <button onclick="explorer.deleteEntity('activity', ${a.id}, '${escapeHtml(a.title)}')" class="action-btn action-btn-danger" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody></table></div>
                `}
            </div>
        `;
    }

    filterComponents(query) {
        const rows = document.querySelectorAll('#components-list tr[data-search]');
        query = query.toLowerCase();
        rows.forEach(row => {
            row.style.display = row.dataset.search.includes(query) ? '' : 'none';
        });
    }

    // View Component detail
    async viewComponent(id) {
        const sl = getSlideOver({ size: 'md', title: 'Component Details', subtitle: 'Loading...' });
        try {
            const response = await fetch(`${SITE_URL}/ajax/components.php?action=get&id=${id}`);
            const data = await response.json();
            sl.setTitle(data.title || 'Component');
            sl.subtitle = data.component_code || '';
            const content = viewSectionHtml('Component Information', [
                viewFieldHtml('Code', `<span class="table-code">${escapeHtml(data.component_code || '')}</span>`),
                viewFieldHtml('Status', getStatusBadge(data.status)),
                viewFieldHtml('Project', escapeHtml(data.project_title || '-')),
                viewFieldHtml('Start Date', formatDate(data.start_date)),
                viewFieldHtml('End Date', formatDate(data.end_date)),
                viewFieldHtml('Completion', (data.completion_percentage || 0) + '%'),
            ]) + (data.description ? viewSectionHtml('Description', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>`]) : '')
            + (data.expected_outputs ? viewSectionHtml('Expected Outputs', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.expected_outputs)}</p></div>`]) : '');
            sl.openView(data.title, data.component_code, content, { size: 'md' });
        } catch (err) { showToast('Failed to load', 'error'); sl.close(true); }
    }

    // View Activity detail
    async viewActivity(id) {
        const sl = getSlideOver({ size: 'lg', title: 'Activity Details', subtitle: 'Loading...' });
        try {
            const response = await fetch(`${SITE_URL}/ajax/activities.php?action=get&id=${id}`);
            const data = await response.json();
            sl.setTitle(data.title || 'Activity');
            sl.subtitle = data.activity_code || '';
            const content = viewSectionHtml('Activity Information', [
                viewFieldHtml('Code', `<span class="table-code">${escapeHtml(data.activity_code || '')}</span>`),
                viewFieldHtml('Status', getStatusBadge(data.status)),
                viewFieldHtml('Type', escapeHtml(data.type_name || '-')),
                viewFieldHtml('Component', escapeHtml(data.component_title || '-')),
                viewFieldHtml('Venue', escapeHtml(data.venue || '-')),
                viewFieldHtml('Start', data.start_datetime ? new Date(data.start_datetime).toLocaleString() : '-'),
                viewFieldHtml('End', data.end_datetime ? new Date(data.end_datetime).toLocaleString() : '-'),
                viewFieldHtml('Target Participants', data.target_participants || '-'),
                viewFieldHtml('Budget', formatCurrency(data.budget_allocation)),
            ]) + (data.description ? viewSectionHtml('Description', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.description)}</p></div>`]) : '')
            + (data.objectives ? viewSectionHtml('Objectives', [`<div class="full-width"><p class="text-sm text-[#D1D5DB]">${escapeHtml(data.objectives)}</p></div>`]) : '');
            sl.openView(data.title, data.activity_code, content, { size: 'lg' });
        } catch (err) { showToast('Failed to load', 'error'); sl.close(true); }
    }

    // ============================================
    // CRUD OPERATIONS
    // ============================================
    editEntity(type, id) {
        // Show loading on the clicked button
        const nodeEl = document.querySelector(`[data-node-key="${type}-${id}"]`);
        if (nodeEl) {
            const btn = nodeEl.querySelector('.node-action-btn');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-pencil"></i>';
                    btn.disabled = false;
                }, 3000);
            }
        }

        const editFunctions = {
            'program': () => this.editProgram(id),
            'project': () => this.editProject(id),
            'component': () => this.editComponent(id),
            'activity': () => this.editActivity(id),
        };
        if (editFunctions[type]) editFunctions[type]();
    }

    async editProgram(id) {
        // Reset any loading buttons
        document.querySelectorAll('.node-action-btn').forEach(btn => {
            if (btn.disabled) { btn.innerHTML = '<i class="fas fa-pencil"></i>'; btn.disabled = false; }
        });
        
        const sl = getSlideOver({ size: 'md', title: 'Edit Program', subtitle: 'Loading...' });
        try {
            const response = await fetch(`${SITE_URL}/ajax/programs.php?action=get&id=${id}`);
            const data = await response.json();
            sl.setTitle('Edit Program');
            sl.subtitle = data.program_code || '';
            const formHtml = `
                <input type="hidden" name="id" value="${data.id}">
                <div class="form-section">
                    <h4 class="form-section-title">Program Information</h4>
                    <div class="form-grid">
                        ${fieldHtml({ name: 'title', label: 'Program Title', required: true, value: data.title, fullWidth: true })}
                        ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', value: data.description, fullWidth: true, rows: 3 })}
                        ${fieldHtml({ name: 'college', label: 'College', value: data.college })}
                        ${fieldHtml({ name: 'campus', label: 'Campus', value: data.campus })}
                    </div>
                </div>
                <div class="form-section">
                    <h4 class="form-section-title">Timeline & Budget</h4>
                    <div class="form-grid">
                        ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date', value: data.start_date })}
                        ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date', value: data.end_date })}
                        ${fieldHtml({ name: 'budget_allocation', label: 'Budget', type: 'number', value: data.budget_allocation })}
                        ${fieldHtml({ name: 'status', label: 'Status', type: 'select', value: data.status, options: [{value:'draft',label:'Draft'},{value:'active',label:'Active'},{value:'completed',label:'Completed'},{value:'archived',label:'Archived'}] })}
                    </div>
                </div>
                <div class="form-section">
                    <h4 class="form-section-title">Objectives</h4>
                    <div class="form-grid">
                        ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', value: data.objectives, fullWidth: true, rows: 3 })}
                        ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', value: data.expected_outputs, fullWidth: true, rows: 3 })}
                    </div>
                </div>`;
            sl.openForm('Edit Program', data.program_code, formHtml, { showSaveAnother: false });
            document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                await submitSlideOverForm(sl, `${SITE_URL}/ajax/programs.php?action=update`, new FormData(e.target), () => {
                    this.loadedNodes.clear();
                    this.loadTree();
                });
            });
        } catch (err) { showToast('Failed to load', 'error'); sl.close(true); }
    }

    async editProject(id) {
        document.querySelectorAll('.node-action-btn').forEach(btn => {
            if (btn.disabled) { btn.innerHTML = '<i class="fas fa-pencil"></i>'; btn.disabled = false; }
        });
        
        const sl = getSlideOver({ size: 'lg', title: 'Edit Project', subtitle: 'Loading...' });
        try {
            const response = await fetch(`${SITE_URL}/ajax/projects.php?action=get&id=${id}`);
            const data = await response.json();
            sl.setTitle('Edit Project');
            sl.subtitle = data.project_code || '';
            const formHtml = `
                <input type="hidden" name="id" value="${data.id}">
                <div class="form-section">
                    <h4 class="form-section-title">Project Information</h4>
                    <div class="form-grid">
                        ${fieldHtml({ name: 'title', label: 'Project Title', required: true, value: data.title, fullWidth: true })}
                        ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', value: data.description, fullWidth: true, rows: 3 })}
                        ${fieldHtml({ name: 'location', label: 'Location', value: data.location })}
                    </div>
                </div>
                <div class="form-section">
                    <h4 class="form-section-title">Timeline & Budget</h4>
                    <div class="form-grid">
                        ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date', value: data.start_date })}
                        ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date', value: data.end_date })}
                        ${fieldHtml({ name: 'budget', label: 'Budget', type: 'number', value: data.budget })}
                        ${fieldHtml({ name: 'status', label: 'Status', type: 'select', value: data.status, options: [{value:'draft',label:'Draft'},{value:'planned',label:'Planned'},{value:'ongoing',label:'Ongoing'},{value:'completed',label:'Completed'},{value:'cancelled',label:'Cancelled'}] })}
                        ${fieldHtml({ name: 'completion_percentage', label: 'Completion %', type: 'number', value: data.completion_percentage })}
                    </div>
                </div>
                <div class="form-section">
                    <h4 class="form-section-title">Details</h4>
                    <div class="form-grid">
                        ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', value: data.objectives, fullWidth: true, rows: 3 })}
                        ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', value: data.expected_outputs, fullWidth: true, rows: 3 })}
                    </div>
                </div>`;
            sl.openForm('Edit Project', data.project_code, formHtml, { showSaveAnother: false, size: 'lg' });
            document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                await submitSlideOverForm(sl, `${SITE_URL}/ajax/projects.php?action=update`, new FormData(e.target), () => {
                    this.loadedNodes.clear();
                    this.loadTree();
                });
            });
        } catch (err) { showToast('Failed to load', 'error'); sl.close(true); }
    }

    async editComponent(id) {
        const sl = getSlideOver({ size: 'md', title: 'Edit Component', subtitle: 'Loading...' });
        try {
            const response = await fetch(`${SITE_URL}/ajax/components.php?action=get&id=${id}`);
            const data = await response.json();
            sl.setTitle('Edit Component');
            sl.subtitle = data.component_code || '';
            const formHtml = `
                <input type="hidden" name="id" value="${data.id}">
                <div class="form-section">
                    <div class="form-grid">
                        ${fieldHtml({ name: 'title', label: 'Title', required: true, value: data.title, fullWidth: true })}
                        ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', value: data.description, fullWidth: true, rows: 3 })}
                        ${fieldHtml({ name: 'status', label: 'Status', type: 'select', value: data.status, options: [{value:'planned',label:'Planned'},{value:'in_progress',label:'In Progress'},{value:'completed',label:'Completed'}] })}
                        ${fieldHtml({ name: 'completion_percentage', label: 'Completion %', type: 'number', value: data.completion_percentage })}
                        ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date', value: data.start_date })}
                        ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date', value: data.end_date })}
                    </div>
                </div>`;
            sl.openForm('Edit Component', data.component_code, formHtml, { showSaveAnother: false });
            document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                await submitSlideOverForm(sl, `${SITE_URL}/ajax/components.php?action=update`, new FormData(e.target), () => {
                    this.loadedNodes.clear();
                    this.loadTree();
                    if (this.selectedNode) this.loadDetail(this.selectedNode.type, this.selectedNode.id);
                });
            });
        } catch (err) { showToast('Failed to load', 'error'); sl.close(true); }
    }

    async editActivity(id) {
        const sl = getSlideOver({ size: 'lg', title: 'Edit Activity', subtitle: 'Loading...' });
        try {
            const response = await fetch(`${SITE_URL}/ajax/activities.php?action=get&id=${id}`);
            const data = await response.json();
            sl.setTitle('Edit Activity');
            sl.subtitle = data.activity_code || '';
            const typeOptions = window.activityTypes || [];
            const formHtml = `
                <input type="hidden" name="id" value="${data.id}">
                <div class="form-section">
                    <div class="form-grid">
                        ${fieldHtml({ name: 'title', label: 'Title', required: true, value: data.title, fullWidth: true })}
                        ${fieldHtml({ name: 'activity_type_id', label: 'Type', type: 'select', options: typeOptions, value: data.activity_type_id })}
                        ${fieldHtml({ name: 'venue', label: 'Venue', value: data.venue })}
                        ${fieldHtml({ name: 'status', label: 'Status', type: 'select', value: data.status, options: [{value:'planned',label:'Planned'},{value:'ongoing',label:'Ongoing'},{value:'completed',label:'Completed'},{value:'cancelled',label:'Cancelled'}] })}
                        ${fieldHtml({ name: 'start_datetime', label: 'Start', type: 'datetime-local', value: data.start_datetime ? data.start_datetime.replace(' ','T').substring(0,16) : '' })}
                        ${fieldHtml({ name: 'end_datetime', label: 'End', type: 'datetime-local', value: data.end_datetime ? data.end_datetime.replace(' ','T').substring(0,16) : '' })}
                        ${fieldHtml({ name: 'target_participants', label: 'Target Participants', type: 'number', value: data.target_participants })}
                        ${fieldHtml({ name: 'budget_allocation', label: 'Budget', type: 'number', value: data.budget_allocation })}
                        ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', value: data.description, fullWidth: true, rows: 3 })}
                        ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', value: data.objectives, fullWidth: true, rows: 2 })}
                    </div>
                </div>`;
            sl.openForm('Edit Activity', data.activity_code, formHtml, { showSaveAnother: false, size: 'lg' });
            document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                await submitSlideOverForm(sl, `${SITE_URL}/ajax/activities.php?action=update`, new FormData(e.target), () => {
                    this.loadedNodes.clear();
                    this.loadTree();
                    if (this.selectedNode) this.loadDetail(this.selectedNode.type, this.selectedNode.id);
                });
            });
        } catch (err) { showToast('Failed to load', 'error'); sl.close(true); }
    }

    deleteEntity(type, id, name) {
        const deleteUrls = {
            'program': `${SITE_URL}/ajax/programs.php?action=delete&id=${id}`,
            'project': `${SITE_URL}/ajax/projects.php?action=delete&id=${id}`,
            'component': `${SITE_URL}/ajax/components.php?action=delete&id=${id}`,
            'activity': `${SITE_URL}/ajax/activities.php?action=delete&id=${id}`,
        };
        if (deleteUrls[type]) {
            showConfirm('Delete ' + type.charAt(0).toUpperCase() + type.slice(1), `Delete "${name}"?`, () => {
                fetch(deleteUrls[type]).then(r => r.json()).then(data => {
                    if (data.success) {
                        showToast(data.message || 'Deleted');
                        this.loadedNodes.clear();
                        this.loadTree();
                        document.getElementById('explorer-detail').innerHTML = '<div class="explorer-empty"><div class="explorer-empty-icon"><i class="fas fa-check-circle text-[#86EFAC]"></i></div><p class="explorer-empty-title">Deleted</p><p class="explorer-empty-text">Select another item from the tree.</p></div>';
                    } else {
                        showToast(data.message || 'Failed', 'error');
                    }
                }).catch(() => showToast('Error', 'error'));
            }, 'Delete');
        }
    }

    openAssignments(type, id, name) {
        if (typeof window.openAssignments === 'function') {
            window.openAssignments(type, id, name);
        }
    }

    openCreateProject(programId) {
        const sl = getSlideOver({ size: 'lg', title: 'New Project', subtitle: 'Create extension project' });
        const formHtml = `
            <input type="hidden" name="program_id" value="${programId}">
            <div class="form-section">
                <h4 class="form-section-title">Project Information</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'title', label: 'Project Title', required: true, fullWidth: true })}
                    ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'location', label: 'Location' })}
                </div>
            </div>
            <div class="form-section">
                <h4 class="form-section-title">Timeline & Budget</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date' })}
                    ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date' })}
                    ${fieldHtml({ name: 'budget', label: 'Budget', type: 'number' })}
                </div>
            </div>
            <div class="form-section">
                <h4 class="form-section-title">Details</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', fullWidth: true, rows: 2 })}
                </div>
            </div>`;
        sl.openForm('New Project', 'Create project under program', formHtml, { showSaveAnother: true, size: 'lg' });
        document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitSlideOverForm(sl, `${SITE_URL}/ajax/projects.php?action=create`, new FormData(e.target), () => {
                this.loadedNodes.clear();
                this.loadTree();
                if (this.selectedNode) this.loadDetail(this.selectedNode.type, this.selectedNode.id);
            });
        });
    }

    openCreateComponent(projectId) {
        const sl = getSlideOver({ size: 'md', title: 'New Component', subtitle: 'Create project component' });
        const formHtml = `
            <input type="hidden" name="project_id" value="${projectId}">
            <div class="form-section">
                <h4 class="form-section-title">Component Information</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'title', label: 'Component Title', required: true, fullWidth: true })}
                    ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'start_date', label: 'Start Date', type: 'date' })}
                    ${fieldHtml({ name: 'end_date', label: 'End Date', type: 'date' })}
                    ${fieldHtml({ name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea', fullWidth: true, rows: 2 })}
                </div>
            </div>`;
        sl.openForm('New Component', 'Create component', formHtml, { showSaveAnother: true });
        document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitSlideOverForm(sl, `${SITE_URL}/ajax/components.php?action=create`, new FormData(e.target), () => {
                this.loadedNodes.clear();
                this.loadTree();
                if (this.selectedNode) this.loadDetail(this.selectedNode.type, this.selectedNode.id);
            });
        });
    }

    openCreateActivity(projectId) {
        const sl = getSlideOver({ size: 'lg', title: 'New Activity', subtitle: 'Create extension activity' });
        const formHtml = `
            <input type="hidden" name="project_id" value="${projectId}">
            <div class="form-section">
                <h4 class="form-section-title">Activity Information</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'title', label: 'Activity Title', required: true, fullWidth: true })}
                    ${fieldHtml({ name: 'activity_type_id', label: 'Type', type: 'select', options: window.activityTypes || [] })}
                    ${fieldHtml({ name: 'venue', label: 'Venue' })}
                </div>
            </div>
            <div class="form-section">
                <h4 class="form-section-title">Schedule & Budget</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'start_datetime', label: 'Start Date/Time', type: 'datetime-local' })}
                    ${fieldHtml({ name: 'end_datetime', label: 'End Date/Time', type: 'datetime-local' })}
                    ${fieldHtml({ name: 'target_participants', label: 'Target Participants', type: 'number' })}
                    ${fieldHtml({ name: 'budget_allocation', label: 'Budget', type: 'number' })}
                </div>
            </div>
            <div class="form-section">
                <h4 class="form-section-title">Details</h4>
                <div class="form-grid">
                    ${fieldHtml({ name: 'description', label: 'Description', type: 'textarea', fullWidth: true, rows: 3 })}
                    ${fieldHtml({ name: 'objectives', label: 'Objectives', type: 'textarea', fullWidth: true, rows: 2 })}
                </div>
            </div>`;
        sl.openForm('New Activity', 'Create activity', formHtml, { showSaveAnother: true, size: 'lg' });
        document.getElementById('slideoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitSlideOverForm(sl, `${SITE_URL}/ajax/activities.php?action=create`, new FormData(e.target), () => {
                this.loadedNodes.clear();
                this.loadTree();
                if (this.selectedNode) this.loadDetail(this.selectedNode.type, this.selectedNode.id);
            });
        });
    }

    // Search
    setupSearch() {
        const searchInput = document.getElementById('explorer-search');
        if (!searchInput) return;
        let timeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => this.search(e.target.value), 300);
        });
    }

    async search(query) {
        if (!query || query.length < 2) { this.loadTree(); return; }
        const container = document.getElementById('explorer-tree');
        container.innerHTML = '<div class="p-4 text-center text-[#6B7280] text-sm">Searching...</div>';
        try {
            const response = await fetch(`${SITE_URL}/ajax/explorer.php?action=search&q=${encodeURIComponent(query)}`);
            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch (e) { container.innerHTML = '<div class="p-4 text-center text-[#FCA5A5] text-sm">Error</div>'; return; }
            if (data.success && data.results && data.results.length > 0) {
                container.innerHTML = '';
                data.results.forEach(node => {
                    const icon = { 'program': 'fa-layer-group', 'project': 'fa-folder-open' }[node.type] || 'fa-file';
                    this.renderNode(container, { ...node, icon, hasChildren: node.type === 'program' }, 0);
                });
            } else {
                container.innerHTML = '<div class="p-6 text-center"><i class="fas fa-search text-3xl text-[#374151] mb-3"></i><p class="text-sm text-[#6B7280]">No results</p></div>';
            }
        } catch (err) {
            container.innerHTML = '<div class="p-4 text-center text-[#FCA5A5] text-sm">Search failed</div>';
        }
    }

    saveExpandedState() { localStorage.setItem('explorer_expanded', JSON.stringify([...this.expandedNodes])); }
    loadExpandedState() { try { const s = localStorage.getItem('explorer_expanded'); if (s) this.expandedNodes = new Set(JSON.parse(s)); } catch (e) {} }
    restoreExpandedState() {
        this.expandedNodes.forEach(key => {
            const c = document.getElementById(`children-${key}`);
            const ch = document.querySelector(`[data-node-key="${key}"] .node-chevron`);
            if (c) c.classList.add('expanded');
            if (ch) ch.classList.add('expanded');
        });
    }
}

const explorer = new ProjectExplorer();
document.addEventListener('DOMContentLoaded', () => { if (document.getElementById('explorer-tree')) explorer.init(); });

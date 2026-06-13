const escapeHTML = (value) => {
    if (value == null) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const app = {
    currentProjectId: null,
    currentFilePreview: null,
    searchTimeout: null,

    init: function () {
        document.getElementById('auth-form').addEventListener('submit', (e) => { e.preventDefault(); this.handleAuth(); });
        this.checkSession();

        const searchInputTable = document.getElementById('search-projects-table');
        if (searchInputTable) searchInputTable.addEventListener('input', () => this.filterProjectsTable());

        const filterSelect = document.getElementById('filter-projects');
        if (filterSelect) filterSelect.addEventListener('change', () => this.filterProjectsTable());

        this.populateForemanDropdown();

        const searchAwd = document.getElementById('search-award-costs');
        if (searchAwd) {
            searchAwd.addEventListener('input', () => {
                clearTimeout(this._awardSearchTimer);
                this._awardSearchTimer = setTimeout(() => {
                    this.loadAwardCosts(searchAwd.value);
                }, 400);
            });
        }
        const awdProject = document.getElementById('awd-project');
        if (awdProject) {
            awdProject.addEventListener('change', function () {
                app.fillAwardProjectFields(this.value);
            });
        }

        const searchBom = document.getElementById('search-bom');
        if (searchBom) {
            searchBom.addEventListener('input', () => {
                clearTimeout(this._bomSearchTimer);
                this._bomSearchTimer = setTimeout(() => {
                    this.loadBOMItems(searchBom.value);
                }, 400);
            });
        }
        const bomProject = document.getElementById('bom-project');
        if (bomProject) {
            bomProject.addEventListener('change', function () {
                document.getElementById('bom-award-cost-text').value = '';
                app.loadBOMAwardCosts(this.value);
                if (!document.getElementById('bom-id').value) {
                    app.loadBOMItems('', this.value);
                }
            });
        }

        const searchManpower = document.getElementById('search-manpower');
        if (searchManpower) {
            searchManpower.addEventListener('input', () => {
                clearTimeout(this._manpowerSearchTimer);
                this._manpowerSearchTimer = setTimeout(() => {
                    this.searchManpower(searchManpower.value);
                }, 400);
            });
        }

        const searchBilling = document.getElementById('search-billing');
        if (searchBilling) {
            searchBilling.addEventListener('input', () => {
                clearTimeout(this._billingSearchTimer);
                this._billingSearchTimer = setTimeout(() => {
                    this.loadBillingRecords(searchBilling.value);
                }, 400);
            });
        }
        const bpProject = document.getElementById('bp-project');
        if (bpProject) {
            bpProject.addEventListener('change', function () {
                app.loadBillingAwardCosts(this.value);
                app.loadBillingSummary(this.value);
                if (!document.getElementById('bp-id').value) {
                    app.loadBillingRecords('', this.value);
                }
            });
        }

        const searchCashRelease = document.getElementById('search-cash-releases');
        if (searchCashRelease) {
            searchCashRelease.addEventListener('input', () => {
                clearTimeout(this._cashReleaseSearchTimer);
                this._cashReleaseSearchTimer = setTimeout(() => {
                    this.loadCashReleaseRecords(searchCashRelease.value);
                }, 400);
            });
        }

        const searchPayrollEntries = document.getElementById('search-payroll-entries');
        if (searchPayrollEntries) {
            searchPayrollEntries.addEventListener('input', () => {
                clearTimeout(this._payrollEntrySearchTimer);
                this._payrollEntrySearchTimer = setTimeout(() => {
                    this.loadPayrollEntryRecords(searchPayrollEntries.value);
                }, 400);
            });
        }
        const searchSuppliers = document.getElementById('search-suppliers');
        if (searchSuppliers) {
            searchSuppliers.addEventListener('input', () => {
                clearTimeout(this._supplierSearchTimer);
                this._supplierSearchTimer = setTimeout(() => {
                    this.loadSupplierRecords(searchSuppliers.value);
                }, 400);
            });
        }

        const peProject = document.getElementById('pe-project');
        if (peProject) {
            peProject.addEventListener('change', function () {
                if (!document.getElementById('pe-id').value) {
                    app.loadPayrollEntryRecords();
                }
            });
        }
        const peType = document.getElementById('pe-type');
        if (peType) {
            peType.addEventListener('change', function () {
                app.togglePayrollType();
            });
        }

        const manPhotoInput = document.getElementById('man-photo');
        if (manPhotoInput) {
            manPhotoInput.addEventListener('change', function () {
                app.previewManpowerPhoto(this);
            });
        }
    },

    request: async function (action, data = {}, isFormData = false) {
        try {
            let bodyData;
            if (isFormData) {
                bodyData = data; bodyData.append('action', action);
            } else {
                bodyData = new URLSearchParams(); bodyData.append('action', action);
                for (let key in data) bodyData.append(key, data[key]);
            }
            const res = await fetch('backend/api.php', { method: 'POST', body: bodyData });
            if (res.status === 401) {
                document.getElementById('auth-screen').style.display = 'flex';
                document.getElementById('app-layout').style.display = 'none';
                return { status: 'error', message: 'Session expired. Please login again.' };
            }
            const text = await res.text();
            try { return JSON.parse(text); }
            catch (e) { console.error("Backend Error:", text); return { status: 'error', message: 'DB Error: Check Backend.' }; }
        } catch (e) { console.error("Network Error:", e); return { status: 'error', message: 'Failed to connect.' }; }
    },

    checkSession: async function () { const res = await this.request('check_session'); if (res.logged_in) { document.getElementById('auth-screen').style.display = 'none'; document.getElementById('app-layout').style.display = 'flex'; this.showModule('dashboard'); } },

    handleAuth: async function () {
        const emailInput = document.getElementById('auth-email');
        const passInput = document.getElementById('auth-pass');
        emailInput.classList.remove('input-error'); passInput.classList.remove('input-error');

        const res = await this.request('login', { email: emailInput.value, password: passInput.value });
        if (res.status === 'success') {
            document.getElementById('auth-screen').style.display = 'none'; document.getElementById('app-layout').style.display = 'flex'; this.showModule('dashboard');
        } else {
            this.showToast(res.message, 'error');
            if (res.field === 'email') { emailInput.classList.add('input-error'); emailInput.focus(); }
            else if (res.field === 'password') { passInput.classList.add('input-error'); passInput.focus(); }
            else { emailInput.classList.add('input-error'); passInput.classList.add('input-error'); }
        }
    },

    logout: async function () { await this.request('logout'); location.reload(); },

    togglePassword: function () {
        const passInput = document.getElementById('auth-pass'); const eyeIcon = document.getElementById('toggle-password');
        if (passInput.type === 'password') { passInput.type = 'text'; eyeIcon.classList.remove('fa-eye'); eyeIcon.classList.add('fa-eye-slash'); }
        else { passInput.type = 'password'; eyeIcon.classList.remove('fa-eye-slash'); eyeIcon.classList.add('fa-eye'); }
    },

    // --- FIX: BINALIK ANG MISSING TOGGLE SIDEBAR FUNCTION PARA SA HAMBURGER MENU ---
    toggleSidebar: function () {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        } else {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        }
    },

    closeSidebarMobile: function () {
        if (window.innerWidth <= 900) {
            document.querySelector('.sidebar').classList.remove('active');
            document.getElementById('sidebar-overlay').classList.remove('active');
        }
    },

    esc: function (v) { return escapeHTML(v); },

    showToast: function (message, type = 'success') {
        const existingToast = document.querySelector('.toast-notification');
        if (existingToast) existingToast.remove();
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type === 'error' ? 'toast-error' : ''}`;
        const icon = type === 'error' ? '<i class="fa-solid fa-circle-exclamation" style="color:var(--danger); font-size:1.2rem;"></i>' : '<i class="fa-solid fa-circle-check" style="color:var(--success); font-size:1.2rem;"></i>';
        toast.innerHTML = `${icon} <span>${escapeHTML(message)}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.animation = 'slideInRight 0.3s reverse forwards'; setTimeout(() => toast.remove(), 4000); }, 4000);
    },

    openModal: function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            if (modalId === 'modal-add-stock') { this.populateInventorySupplierDropdown(); this.populateCategoryDropdown(); const newCatInput = document.getElementById('stock-category-new'); if (newCatInput) { newCatInput.style.display = 'none'; newCatInput.value = ''; } }
        }
    },
    closeModal: function (modalId) { const modal = document.getElementById(modalId); if (modal) modal.style.display = 'none'; },
    closeModalOnBackdrop: function (event, modalId) { if (event.target.id === modalId) this.closeModal(modalId); },

    // ==========================================
    // MODULE ROUTING
    // ==========================================
    showModule: function (id) {
        if (id === 'award_costs') {
            id = 'global_ntp';
            this._pendingNTPTab = 'award_cost';
        }
        document.querySelectorAll('.module').forEach(m => m.classList.remove('active'));
        document.getElementById('mod-' + id).classList.add('active');

        document.querySelectorAll('.nav-links li').forEach(li => li.classList.remove('active'));
        let activeLi = document.querySelector(`.nav-links li[data-module='${id}']`);
        let cleanTitle = activeLi ? activeLi.textContent.trim() : 'Dashboard';

        if (this._pendingNTPTab === 'award_cost') cleanTitle = 'Award Cost';
        document.getElementById('dynamic-breadcrumbs').innerHTML = `<span class="breadcrumb-link" onclick="app.showModule('dashboard')"><i class="fa-solid fa-house"></i> Home</span><i class="fa-solid fa-chevron-right separator"></i><b id="breadcrumb-current" class="active-crumb">${escapeHTML(cleanTitle)}</b>`;
        if (activeLi) activeLi.classList.add('active');

        if (id === 'dashboard') this.loadDashboard();
        if (id === 'projects') {
            this.closeProjectDetails(); document.getElementById('breadcrumb-current').innerText = "Projects (Sites)";
            if (document.getElementById('search-projects-table')) document.getElementById('search-projects-table').value = '';
            if (document.getElementById('filter-projects')) document.getElementById('filter-projects').value = 'all';
            this.loadProjects();
        }
        if (id === 'materials') { this.loadSuppliersDashboard(); this.loadMaterialSuppliers(); }
        if (id === 'users') { this.loadProjectOptionsForManpower(); this.populateManpowerDropdowns(); this.loadForemenDropdown(); this.switchManpowerView('foreman'); }
        if (id === 'bill_of_materials') this.loadBOMModule();
        if (id === 'billing_progress') this.loadBillingModule();
        if (id === 'payroll') { this.backToActivePayroll(); this.renderPayrollTab(); this.populatePayrollDatalists(); this.loadPayrollEntries(); }
        if (id === 'cash_release') this.loadCashRelease();
        if (id === 'global_ntp') this.loadGlobalNTP();

        this.clearGlobalSearch();
        this.closeSidebarMobile();
    },

    // FIX: Dashboard numbers now sync exactly with the backend count.
    // FIX: Dashboard ongoing projects now counts directly from get_projects.
    loadDashboard: async function () {
        const stats = await this.request('get_stats');
        const projects = await this.request('get_projects');

        window.allProjectsData = Array.isArray(projects) ? projects : [];

        // Count active projects directly from project records.
        // Any project that is not completed will show under Ongoing Projects.
        const activeProjects = window.allProjectsData.filter(p => {
            const status = (p.status || '').toLowerCase().trim();
            return status !== 'completed';
        });

        document.getElementById('stat-projects').innerText = activeProjects.length || 0;
        document.getElementById('stat-users').innerText = stats.users || 0;
        document.getElementById('stat-cash-release').innerText = '₱' + parseFloat(stats.total_cash_release || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2
        });
        document.getElementById('stat-payroll-advance').innerText = '₱' + parseFloat(stats.total_payroll_advance || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2
        });

        this.loadUpcomingDeadlines();
    },

    loadUpcomingDeadlines: async function () {
        const tbody = document.getElementById('deadlines-content'); if (!tbody) return;
        const projects = window.allProjectsData || await this.request('get_projects');
        const today = new Date(); today.setHours(0, 0, 0, 0);
        let deadlines = [];

        if (Array.isArray(projects)) {
            projects.forEach(p => {
                if ((p.status || '').toLowerCase().trim() === 'completed') return;
                let sDate = new Date(p.start_date);
                let dOffset = Math.floor((sDate - today) / (1000 * 60 * 60 * 24));
                deadlines.push({ type: 'project', icon: 'fa-city', site: p.location, action: p.name, daysOffset: dOffset, actualDate: sDate });
            });
        }

        deadlines = deadlines.filter(t => t.daysOffset <= 30).sort((a, b) => a.daysOffset - b.daysOffset);
        tbody.innerHTML = '';
        if (deadlines.length === 0) { tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 20px; color: var(--text-muted);">No upcoming deadlines.</td></tr>`; return; }

        deadlines.forEach(task => {
            let statusBadge = '';
            let dateText = task.actualDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            if (task.daysOffset < 0) {
                statusBadge = `<span class="badge" style="background: #FEE2E2; color: #EF4444; border: 1px solid #FCA5A5;">OVERDUE (${Math.abs(task.daysOffset)} DAYS LATE)</span>`;
            } else if (task.daysOffset === 0) {
                statusBadge = `<span class="badge" style="background: #FEE2E2; color: #EF4444; border: 1px solid #FCA5A5;">DUE TODAY</span>`;
            } else if (task.daysOffset <= 7) {
                statusBadge = `<span class="badge" style="background: #FEF3C7; color: #B45309; border: 1px solid #FCD34D;">URGENT (${task.daysOffset} DAYS LEFT)</span>`;
            } else {
                statusBadge = `<span class="badge" style="background: #E5E7EB; color: #374151; border: 1px solid #D1D5DB;">UPCOMING (${task.daysOffset} DAYS LEFT)</span>`;
            }

            tbody.innerHTML += `<tr>
                <td style="text-align: center;"><div class="type-icon-box" style="margin: 0 auto; color: var(--text-muted);"><i class="fa-solid ${task.icon}"></i></div></td>
                <td><b style="color:var(--text-dark);">${escapeHTML(task.site)}</b></td>
                <td style="font-weight: 600; color: var(--text-main);">${escapeHTML(task.action)}</td>
                <td><span style="font-weight: 600; color: var(--text-dark);">${dateText}</span></td>
                <td>${statusBadge}</td>
            </tr>`;
        });
    },

    handleGlobalSearch: function (query) {
        const searchContainer = document.getElementById('global-search-results');
        const content = document.getElementById('search-results-content');
        const clearBtn = document.getElementById('clear-search-btn');
        const deadlinesContainer = document.getElementById('upcoming-deadlines-container');

        if (!searchContainer || !content) return;

        if (!query || query.trim() === '') {
            searchContainer.style.display = 'none';
            if (clearBtn) clearBtn.style.display = 'none';
            if (deadlinesContainer) deadlinesContainer.style.display = 'block';
            clearTimeout(this.searchTimeout);
            return;
        }

        if (clearBtn) clearBtn.style.display = 'block';
        searchContainer.style.display = 'block';
        if (deadlinesContainer) deadlinesContainer.style.display = 'none';

        const queryDisplay = document.getElementById('search-query-display');
        if (queryDisplay) queryDisplay.innerText = query;

        content.innerHTML = `<p style="padding: 10px; color: var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Searching across all modules...</p>`;

        clearTimeout(this.searchTimeout);

        this.searchTimeout = setTimeout(async () => {
            try {
                if (
                    !window.globalSearchData ||
                    !window.globalSearchData.timestamp ||
                    Date.now() - window.globalSearchData.timestamp > 15000
                ) {
                    const [
                        projects,
                        users,
                        suppliers,
                        inventory,
                        awardCosts,
                        cashReleases
                    ] = await Promise.all([
                        this.request('get_projects'),
                        this.request('get_active_manpower'),
                        this.request('get_suppliers'),
                        this.request('get_inventory'),
                        this.request('get_award_costs'),
                        this.request('get_cash_releases')
                    ]);

                    window.globalSearchData = {
                        projects: Array.isArray(projects) ? projects : [],
                        users: Array.isArray(users) ? users : [],
                        suppliers: Array.isArray(suppliers) ? suppliers : [],
                        inventory: Array.isArray(inventory) ? inventory : [],
                        award_costs: Array.isArray(awardCosts) ? awardCosts : [],
                        cash_releases: Array.isArray(cashReleases) ? cashReleases : [],
                        timestamp: Date.now()
                    };
                }

                const db = window.globalSearchData || {
                    projects: [],
                    users: [],
                    suppliers: [],
                    inventory: [],
                    award_costs: [],
                    cash_releases: []
                };

                const q = query.toLowerCase().trim();
                let resultsHTML = '';

                const safe = (value) => String(value || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

                const matchedProjs = (db.projects || []).filter(p =>
                    (p.name || '').toLowerCase().includes(q) ||
                    (p.block_no || '').toLowerCase().includes(q) ||
                    (p.lot_no || '').toLowerCase().includes(q) ||
                    (p.location || '').toLowerCase().includes(q) ||
                    (p.foreman || '').toLowerCase().includes(q) ||
                    (p.foreman_2 || '').toLowerCase().includes(q) ||
                    (p.status || '').toLowerCase().includes(q)
                );

                const matchedUsers = (db.users || []).filter(u =>
                    (u.name || '').toLowerCase().includes(q) ||
                    (u.position || '').toLowerCase().includes(q) ||
                    (u.skills || '').toLowerCase().includes(q) ||
                    (u.project_name || '').toLowerCase().includes(q)
                );

                const matchedSuppliers = (db.suppliers || []).filter(s =>
                    (s.name || '').toLowerCase().includes(q) ||
                    (s.materials || '').toLowerCase().includes(q) ||
                    (s.contact || '').toLowerCase().includes(q) ||
                    (s.email || '').toLowerCase().includes(q)
                );

                const matchedInventory = (db.inventory || []).filter(i =>
                    (i.name || '').toLowerCase().includes(q) ||
                    (i.category || '').toLowerCase().includes(q) ||
                    (i.supplier || '').toLowerCase().includes(q)
                );

                const matchedAwards = (db.award_costs || []).filter(a =>
                    (a.service_agreement_code || '').toLowerCase().includes(q) ||
                    (a.scope_of_work || '').toLowerCase().includes(q) ||
                    (a.work_description || '').toLowerCase().includes(q) ||
                    (a.project_description || '').toLowerCase().includes(q) ||
                    (a.project_name || '').toLowerCase().includes(q) ||
                    (a.block_no || '').toLowerCase().includes(q) ||
                    (a.lot_no || '').toLowerCase().includes(q) ||
                    (a.location || '').toLowerCase().includes(q) ||
                    (a.item || '').toLowerCase().includes(q)
                );

                const matchedCash = (db.cash_releases || []).filter(c =>
                    (c.release_date || '').toLowerCase().includes(q) ||
                    (c.category || '').toLowerCase().includes(q) ||
                    (c.released_to || '').toLowerCase().includes(q) ||
                    (c.release_description || '').toLowerCase().includes(q) ||
                    String(c.release_amount || '').includes(q)
                );

                matchedProjs.forEach(item => {
                    resultsHTML += `
                    <div class="search-result-item" onclick="app.showModule('projects'); setTimeout(() => app.openProjectDetails(${item.id}, '${safe(item.name)}', '${safe(item.location)}'), 250)">
                        <div class="search-icon-box"><i class="fa-solid fa-city"></i></div>
                        <div class="search-content">
                            <h4>${escapeHTML(item.name) || 'Unnamed Project'}</h4>
                            <p>${escapeHTML(item.location) || 'No location'} | Foreman: ${escapeHTML(item.foreman) || 'N/A'}</p>
                            <span class="search-category-badge">Projects</span>
                        </div>
                    </div>
                `;
                });

                matchedUsers.forEach(item => {
                    let safeName = safe(item.name);
                    let safeSkill = safe(item.skills || 'Uncategorized');
                    let safeId = String(item.name || '').replace(/[^a-zA-Z0-9]/g, '-');

                    resultsHTML += `
                    <div class="search-result-item" onclick="app.showModule('users'); setTimeout(() => app.openSkillFolder('${safeSkill}'), 250)">
                        <div class="search-icon-box" style="color:var(--success);"><i class="fa-solid fa-user-helmet"></i></div>
                        <div class="search-content">
                            <h4>${escapeHTML(item.name) || 'Unnamed Worker'}</h4>
                            <p>${escapeHTML(item.position) || 'Worker'} | ${escapeHTML(item.skills) || 'N/A'}</p>
                            <span class="search-category-badge" style="color:var(--success);">Record List</span>
                        </div>
                    </div>
                `;

                    resultsHTML += `
                    <div class="search-result-item" onclick="app.showModule('payroll'); setTimeout(() => { const payName = document.getElementById('pay-name'); if(payName) payName.value = '${safeName}'; const r = document.getElementById('nested-${safeId}'); if(r){ r.classList.add('active'); r.scrollIntoView({behavior: 'smooth', block: 'center'}); } }, 350);">
                        <div class="search-icon-box" style="color:var(--warning);"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <div class="search-content">
                            <h4>${escapeHTML(item.name) || 'Unnamed Worker'}</h4>
                            <p>Log Cash Advance / Compute Balance</p>
                            <span class="search-category-badge" style="color:var(--warning);">Payroll</span>
                        </div>
                    </div>
                `;
                });

                matchedSuppliers.forEach(item => {
                    resultsHTML += `
                    <div class="search-result-item" onclick="app.showModule('materials'); setTimeout(() => app.switchMatTab('suppliers'), 250);">
                        <div class="search-icon-box" style="color:#10B981;"><i class="fa-solid fa-truck-field"></i></div>
                        <div class="search-content">
                            <h4>${escapeHTML(item.name) || 'Unnamed Supplier'}</h4>
                            <p>Provides: ${escapeHTML(item.materials) || 'N/A'}</p>
                            <span class="search-category-badge" style="color:#10B981;">Supplier</span>
                        </div>
                    </div>
                `;
                });

                matchedInventory.forEach(item => {
                    resultsHTML += `
                    <div class="search-result-item" onclick="app.showModule('materials'); setTimeout(() => app.switchMatTab('inventory'), 250);">
                        <div class="search-icon-box" style="color:#3B82F6;"><i class="fa-solid fa-boxes-stacked"></i></div>
                        <div class="search-content">
                            <h4>${escapeHTML(item.name) || 'Unnamed Item'}</h4>
                            <p>Stock: ${escapeHTML(item.stock) || 0} ${escapeHTML(item.unit) || ''} | Category: ${escapeHTML(item.category) || 'N/A'}</p>
                            <span class="search-category-badge" style="color:#3B82F6;">Inventory</span>
                        </div>
                    </div>
                `;
                });

                matchedAwards.forEach(item => {
                    const displayName = item.service_agreement_code || item.scope_of_work || item.work_description || 'Award Cost';
                    const displayAmount = item.total_amount || item.amount || 0;
                    resultsHTML += `
                    <div class="search-result-item" onclick="app.showModule('award_costs');">
                        <div class="search-icon-box" style="color:#8B5CF6;"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="search-content">
                            <h4>${escapeHTML(displayName)}</h4>
                            <p>${escapeHTML(item.project_name || '')} | ₱${parseFloat(displayAmount).toLocaleString('en-US')}</p>
                            <span class="search-category-badge" style="color:#8B5CF6;">Award Cost</span>
                        </div>
                    </div>
                `;
                });

                matchedCash.forEach(item => {
                    resultsHTML += `
                    <div class="search-result-item" onclick="app.showModule('cash_release');">
                        <div class="search-icon-box" style="color:#EF4444;"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                        <div class="search-content">
                            <h4>${escapeHTML(item.released_to || 'Cash Release')} - ${escapeHTML(item.category || 'N/A')}</h4>
                            <p>${escapeHTML(item.release_description || 'No Description')} | Amount: ₱${parseFloat(item.release_amount || 0).toLocaleString('en-US')}</p>
                            <span class="search-category-badge" style="color:#EF4444;">Cash Release</span>
                        </div>
                    </div>
                `;
                });

                if (resultsHTML === '') {
                    resultsHTML = `<p style="padding: 10px; color: var(--text-muted);">No results found.</p>`;
                }

                content.innerHTML = resultsHTML;
            } catch (error) {
                console.error('Global Search Error:', error);
                content.innerHTML = `<p style="padding: 10px; color: var(--danger);">Search error. Please check backend connection.</p>`;
            }
        }, 400);
    },

    clearGlobalSearch: function () {
        const input = document.getElementById('global-search-input');
        const deadlinesContainer = document.getElementById('upcoming-deadlines-container');

        if (input) input.value = '';
        this.handleGlobalSearch('');

        if (deadlinesContainer) deadlinesContainer.style.display = 'block';
    },

    // ==========================================
    // PROJECTS & WORKSPACE
    // ==========================================
    populateForemanDropdown: async function () {
        const users = await this.request('get_active_manpower');
        const select1 = document.getElementById('proj-foreman');
        const select2 = document.getElementById('proj-foreman2');
        if (select1) select1.innerHTML = '<option value="">Select Foreman 1 (Required)</option>';
        if (select2) select2.innerHTML = '<option value="">Select Foreman 2 (Optional)</option>';
        if (users && Array.isArray(users)) {
            const foremen = users.filter(m => m.position && (m.position.toLowerCase().includes('foreman') || m.position.toLowerCase().includes('lead') || m.position.toLowerCase().includes('engineer') || m.position.toLowerCase().includes('in-charge')));
            foremen.forEach(f => {
                const opt = `<option value="${escapeHTML(f.name)}">${escapeHTML(f.name)} (${escapeHTML(f.position)})</option>`;
                if (select1) select1.innerHTML += opt;
                if (select2) select2.innerHTML += opt;
            });
        }
    },

    handleFileSelect: function (input) {
        const display = document.getElementById('file-name-display'); const label = document.getElementById('file-dropzone-label');
        if (input.files && input.files.length > 0) { this.currentFilePreview = URL.createObjectURL(input.files[0]); display.innerHTML = `<i class="fa-regular fa-file-lines"></i> ${escapeHTML(input.files[0].name)} <span style="margin-left:10px; color:var(--primary-hover); text-decoration:underline;" onclick="event.preventDefault(); app.viewAttachedFile('${this.currentFilePreview}')">View</span>`; label.style.borderColor = "var(--success)"; label.style.color = "var(--success)"; }
        else { this.currentFilePreview = null; display.innerText = "Attach Initial NTP Document (Optional)"; label.style.borderColor = "#D1D5DB"; label.style.color = "var(--text-muted)"; }
    },

    viewAttachedFile: function (url) { if (url) { document.getElementById('resume-img').src = url; document.getElementById('resume-modal').style.display = 'block'; } },

    submitProjectForm: async function () {
        const name = document.getElementById('proj-name').value;
        const block_no = document.getElementById('proj-block').value;
        const lot_no = document.getElementById('proj-lot').value;
        const client = document.getElementById('proj-client').value || '-';
        const location = document.getElementById('proj-loc').value;
        const desc = document.getElementById('proj-desc').value;
        const foremanRaw = document.getElementById('proj-foreman').value;
        const foreman2Raw = document.getElementById('proj-foreman2').value;
        const start_date = document.getElementById('proj-start').value;
        const fileInput = document.getElementById('proj-ntp-init');
        if (!name || !block_no || !lot_no || !location || !start_date || !foremanRaw) { this.showToast('Project Name, Block, Lot, Location, Foreman 1, and Date are required!', 'error'); return; }
        const foreman = foremanRaw.split(' (')[0];
        const foreman_2 = foreman2Raw ? foreman2Raw.split(' (')[0] : '';
        const res = await this.request('add_project', { name, client, location, desc, foreman, start_date, block_no, lot_no, foreman_2 });
        if (res.status === 'error') return this.showToast(res.message, 'error');
        document.getElementById('proj-name').value = ''; document.getElementById('proj-block').value = ''; document.getElementById('proj-lot').value = ''; document.getElementById('proj-loc').value = ''; document.getElementById('proj-desc').value = ''; document.getElementById('proj-start').value = ''; document.getElementById('proj-client').value = ''; document.getElementById('proj-foreman').value = ''; document.getElementById('proj-foreman2').value = ''; fileInput.value = ''; this.handleFileSelect(fileInput);

        await this.loadProjects();
        this.loadProjectOptionsForManpower();
        this.loadDashboard();
        this.showToast("Project successfully created.");
    },

    loadProjects: async function () { this.closeProjectDetails(); window.allProjectsData = await this.request('get_projects'); this.filterProjectsTable(); this.populateForemanDropdown(); },

    filterProjectsTable: function () {
        const tbody = document.querySelector('#table-projects tbody'); if (!tbody || !window.allProjectsData) return;
        const search = (document.getElementById('search-projects-table')?.value || '').toLowerCase(); const filter = document.getElementById('filter-projects')?.value || 'all';
        let filtered = window.allProjectsData;
        if (search) filtered = filtered.filter(p =>
            (p.name || '').toLowerCase().includes(search) ||
            (p.block_no || '').toLowerCase().includes(search) ||
            (p.lot_no || '').toLowerCase().includes(search) ||
            (p.location || '').toLowerCase().includes(search) ||
            (p.foreman || '').toLowerCase().includes(search) ||
            (p.foreman_2 || '').toLowerCase().includes(search) ||
            (p.work_description || '').toLowerCase().includes(search) ||
            (p.project_description || '').toLowerCase().includes(search)
        );
        if (filter !== 'all') filtered = filtered.filter(p => p.status === filter);
        filtered.sort((a, b) => new Date(b.start_date) - new Date(a.start_date));
        tbody.innerHTML = '';
        if (filtered.length === 0) { tbody.innerHTML = `<tr><td colspan="9" class="empty-state-wrapper"><i class="fa-solid fa-folder-open"></i><p>No projects found.</p></td></tr>`; return; }

        filtered.forEach(p => {
            let stat = (p.status || '').toLowerCase().trim();
            let statusUI = ''; let actionBtn = ''; let viewNtpBtn = stat === 'pending' ? `<button class="btn-outline" style="height: 26px; padding: 0 8px; font-size: 0.75rem;" onclick="app.showModule('global_ntp')" title="View NTP"><i class="fa-solid fa-file-pdf"></i> Verify NTP</button>` : '';
            let safeName = escapeHTML(p.name || '').replace(/'/g, "\\'");
            let safeLoc = escapeHTML(p.location || '').replace(/'/g, "\\'");

            if (stat === 'pending') {
                statusUI = `<span class="badge pending">Pending (NTP)</span>`; actionBtn = ``;
            } else {
                statusUI = `<select onchange="app.updateProjectStatus(${p.id}, this.value)" class="table-status-select" style="height:24px; padding: 0 4px; width:auto; font-size:0.75rem; background: ${stat === 'completed' ? '#D1FAE5' : '#FEFCE8'}; color: ${stat === 'completed' ? 'var(--success)' : '#854D0E'};"><option value="ongoing" ${stat === 'ongoing' ? 'selected' : ''}>Ongoing</option><option value="completed" ${stat === 'completed' ? 'selected' : ''}>Completed</option></select>`;
                actionBtn = `<button class="btn" style="height: 26px; padding: 0 8px; font-size: 0.75rem;" onclick="app.openProjectDetails(${p.id}, '${safeName}', '${safeLoc}')"><i class="fa-solid fa-folder-open"></i> Workspace</button>`;
            }

            let projNameClickable = `<span style="cursor:pointer; color:var(--primary-hover); text-decoration:underline;" onclick="app.openProjectDetails(${p.id}, '${safeName}', '${safeLoc}')">${escapeHTML(p.name)}</span>`;
            tbody.innerHTML += `<tr><td><b style="color:var(--text-dark);">${projNameClickable}</b><br><small style="color:var(--text-muted); font-size:0.75rem;">${escapeHTML(p.description) || ''}</small></td><td style="font-weight:600;">${escapeHTML(p.block_no) || '-'}</td><td style="font-weight:600;">${escapeHTML(p.lot_no) || '-'}</td><td>${escapeHTML(p.location)}</td><td><b style="color:var(--text-main); font-size:0.8rem;"><i class="fa-solid fa-user-helmet"></i> ${escapeHTML(p.foreman) || '-'}</b></td><td>${escapeHTML(p.foreman_2) || '-'}</td><td style="font-weight: 600;">${escapeHTML(p.start_date)}</td><td>${statusUI}</td><td><div style="display: flex; gap: 4px;">${viewNtpBtn}${actionBtn}<button class="btn-danger" style="height: 26px; padding: 0 8px; border-radius: 4px;" onclick="app.deleteProject(${p.id})"><i class="fa-solid fa-trash" style="font-size: 0.8rem;"></i></button></div></td></tr>`;
        });
    },

    updateProjectStatus: async function (id, status) { await this.request('update_project_status', { id, status }); await this.loadProjects(); this.loadDashboard(); },
    deleteProject: async function (id) { if (confirm("DANGER ZONE! Deleting this project will wipe all tracking data. Are you sure?")) { await this.request('delete_project', { id }); await this.loadProjects(); this.loadDashboard(); } },

    openProjectDetails: async function (id, name, location) {
        this.currentProjectId = id;
        document.getElementById('projects-list-view').style.display = 'none'; document.getElementById('project-details-view').style.display = 'block';
        document.getElementById('pd-name').innerText = name; document.getElementById('pd-loc-display').innerText = location ? location : "Location not specified";
        const data = await this.request('get_project_data', { project_id: id });
        if (data && data.project) {
            const p = data.project;
            document.getElementById('pd-block-display').innerHTML = `<i class="fa-solid fa-cube"></i> Block: ${escapeHTML(p.block_no || '-')}`;
            document.getElementById('pd-lot-display').innerHTML = `<i class="fa-solid fa-cube"></i> Lot: ${escapeHTML(p.lot_no || '-')}`;
            document.getElementById('pd-foreman-display').innerHTML = `<i class="fa-solid fa-user-helmet"></i> Foreman 1: ${escapeHTML(p.foreman || '-')}`;
            document.getElementById('pd-foreman2-display').innerHTML = `<i class="fa-solid fa-user-helmet"></i> Foreman 2: ${escapeHTML(p.foreman_2 || '-')}`;
            const wdEl = document.getElementById('pd-work-desc-display');
            if (wdEl) wdEl.innerHTML = `<i class="fa-solid fa-briefcase"></i> Work: ${escapeHTML(p.work_description || '-')}`;
            const pdEl = document.getElementById('pd-project-desc-display');
            if (pdEl) pdEl.innerHTML = `<i class="fa-solid fa-file-lines"></i> Project: ${escapeHTML(p.project_description || '-')}`;
            const taEl = document.getElementById('pd-total-amount-display');
            if (taEl) taEl.innerHTML = `<i class="fa-solid fa-coins"></i> Total Amount: ${p.total_amount ? '₱' + parseFloat(p.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) : '-'}`;
            const cdEl = document.getElementById('pd-completion-display');
            if (cdEl) cdEl.innerHTML = `<i class="fa-solid fa-calendar-check"></i> Completion: ${escapeHTML(p.completion_date || '-')}`;
        }
        document.getElementById('dynamic-breadcrumbs').innerHTML = `<span class="breadcrumb-link" onclick="app.showModule('dashboard')"><i class="fa-solid fa-house"></i> Home</span><i class="fa-solid fa-chevron-right separator"></i><span class="breadcrumb-link" onclick="app.showModule('projects')">Projects (Sites)</span><i class="fa-solid fa-chevron-right separator"></i><b id="breadcrumb-current" class="active-crumb">Workspace</b>`;
        this.switchProjectTab('progress');
    },

    closeProjectDetails: function () {
        this.currentProjectId = null;
        document.getElementById('projects-list-view').style.display = 'block'; document.getElementById('project-details-view').style.display = 'none';
        document.getElementById('dynamic-breadcrumbs').innerHTML = `<span class="breadcrumb-link" onclick="app.showModule('dashboard')"><i class="fa-solid fa-house"></i> Home</span><i class="fa-solid fa-chevron-right separator"></i><b id="breadcrumb-current" class="active-crumb">Projects (Sites)</b>`;
        ['pd-block-display','pd-lot-display','pd-foreman-display','pd-foreman2-display','pd-work-desc-display','pd-project-desc-display','pd-total-amount-display','pd-completion-display'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '';
        });
    },

    switchProjectTab: function (tabId) {
        document.getElementById('tab-progress').classList.remove('active'); document.getElementById('tab-materials').classList.remove('active'); document.getElementById('tab-manpower').classList.remove('active');
        document.getElementById('ptab-progress').classList.remove('active'); document.getElementById('ptab-materials').classList.remove('active'); document.getElementById('ptab-manpower').classList.remove('active');
        document.getElementById('tab-' + tabId).classList.add('active'); document.getElementById('ptab-' + tabId).classList.add('active');
        if (tabId === 'materials') this.renderProjectWorkspaceMaterials();
        if (tabId === 'progress') this.renderProjectChecklist();
        if (tabId === 'manpower') this.renderManpowerAssignments();
    },

    // --- CHECKLIST CRUD FROM DB ---
    renderProjectChecklist: async function () {
        const data = await this.request('get_project_data', { project_id: this.currentProjectId }); const grid = document.getElementById('checklist-grid'); grid.innerHTML = '';
        if (!data.checklist || data.checklist.length === 0) { grid.innerHTML = `<p style="grid-column: 1/-1; text-align:center; padding: 20px; color:var(--text-muted);">No checklist generated.</p>`; return; }
        let grouped = {};
        data.checklist.forEach(item => { const cat = item.category || 'Uncategorized'; if (!grouped[cat]) grouped[cat] = { items: [], assigned: item.assigned_worker }; if (item.task_name !== '') grouped[cat].items.push(item); if (item.assigned_worker) grouped[cat].assigned = item.assigned_worker; });

        let totalItems = 0; let completedItems = 0;
        Object.keys(grouped).forEach((cat, cIdx) => {
            let itemsHtml = '';
            grouped[cat].items.forEach((item, iIdx) => {
                totalItems++; let isComp = item.status === 'Completed'; if (isComp) completedItems++;
                const completedClass = isComp ? 'completed' : ''; const checkedAttr = isComp ? 'checked' : '';
                itemsHtml += `<div class="checklist-item ${completedClass}" id="item-row-${item.id}"><div class="checklist-item-left" onclick="app.toggleChecklistItemDB(${item.id}, '${isComp ? 'Not Started' : 'Completed'}', '${item.assigned_worker}', ${item.award_cost})"><input type="checkbox" ${checkedAttr} onclick="event.stopPropagation(); app.toggleChecklistItemDB(${item.id}, '${isComp ? 'Not Started' : 'Completed'}', '${item.assigned_worker}', ${item.award_cost})"><span class="checklist-task-label">${item.task_name} <small style="color:var(--success); font-weight:700;" onclick="event.stopPropagation(); app.editChecklistCostDB(${item.id}, ${item.award_cost})">(₱${parseFloat(item.award_cost).toLocaleString('en-US')})</small></span></div><div class="checklist-item-actions"><button class="checklist-action-btn edit" onclick="app.editChecklistItemDB(${item.id}, '${item.task_name.replace(/'/g, "\\'")}')"><i class="fa-solid fa-pencil"></i></button><button class="checklist-action-btn delete" onclick="app.deleteChecklistItemDB(${item.id})"><i class="fa-solid fa-trash"></i></button></div></div>`;
            });
            let badgeHtml = grouped[cat].assigned ? `<span class="badge-assigned"><i class="fa-solid fa-user-check"></i> ${grouped[cat].assigned}</span>` : '';
            grid.innerHTML += `<div class="checklist-category"><h4>${cat} ${badgeHtml} <button class="checklist-action-btn delete" style="float:right;" onclick="app.deleteCategoryDB('${cat}')"><i class="fa-solid fa-xmark"></i></button></h4><div id="cat-items-${cIdx}">${itemsHtml}</div><div id="add-task-container-${cIdx}" style="display:none; margin-top:8px;"><input type="text" class="inline-input" id="add-task-input-${cIdx}" placeholder="Type task and press Enter..." onkeydown="if(event.key==='Enter') app.saveNewTaskDB('${cat}', this.value, ${cIdx})" onblur="this.parentElement.style.display='none'; document.getElementById('btn-show-add-${cIdx}').style.display='block';"></div><button id="btn-show-add-${cIdx}" class="add-task-btn" onclick="this.style.display='none'; document.getElementById('add-task-container-${cIdx}').style.display='block'; document.getElementById('add-task-input-${cIdx}').focus();"><i class="fa-solid fa-plus"></i> Add Task</button></div>`;
        });
        const pct = totalItems === 0 ? 0 : Math.round((completedItems / totalItems) * 100);
        document.getElementById('proj-progress-bar').style.width = pct + '%'; document.getElementById('proj-progress-text').innerText = pct + '%';
    },

    toggleChecklistItemDB: async function (taskId, newStatus, assignedWorker, cost) {
        await this.request('update_checklist_status', { task_id: taskId, status: newStatus });
        if (newStatus === 'Completed') { if (assignedWorker && assignedWorker !== 'null') { this.showToast(`Task completed! ₱${parseFloat(cost).toLocaleString('en-US')} auto-synced to ${assignedWorker}'s Payroll.`); } else { this.showToast(`Task completed! (Warning: No worker assigned, won't sync to Payroll).`, 'warning'); } }
        this.renderProjectChecklist();
    },

    saveNewTaskDB: async function (category, taskName, cIdx) { if (taskName.trim() !== '') { await this.request('add_checklist_task', { project_id: this.currentProjectId, category: category, task_name: taskName.trim() }); this.showToast('Task added.'); } document.getElementById('add-task-container-' + cIdx).style.display = 'none'; this.renderProjectChecklist(); },
    editChecklistItemDB: function (taskId, currentName) { const row = document.getElementById(`item-row-${taskId}`); row.innerHTML = `<div style="width:100%;"><input type="text" class="inline-input" value="${currentName}" onblur="app.saveEditedTaskDB(${taskId}, this.value)" onkeydown="if(event.key==='Enter') this.blur()" autofocus></div>`; row.querySelector('input').focus(); },
    saveEditedTaskDB: async function (taskId, newName) { if (newName.trim() !== '') { await this.request('edit_checklist_task', { task_id: taskId, task_name: newName.trim() }); } this.renderProjectChecklist(); },
    editChecklistCostDB: function (taskId, currentCost) { const newCost = prompt("Update Award Cost for this task (₱):", currentCost); if (newCost !== null && !isNaN(newCost)) { this.request('update_task_cost', { task_id: taskId, cost: newCost }).then(() => this.renderProjectChecklist()); } },
    deleteChecklistItemDB: async function (taskId) { await this.request('delete_checklist_task', { task_id: taskId }); this.renderProjectChecklist(); },
    showAddCategoryInput: function () { document.getElementById('btn-add-cat').style.display = 'none'; const input = document.getElementById('input-add-cat'); input.style.display = 'block'; input.focus(); },
    saveNewCategoryDB: async function (val) { const input = document.getElementById('input-add-cat'); input.style.display = 'none'; input.value = ''; document.getElementById('btn-add-cat').style.display = 'inline-flex'; if (val.trim() !== '') { await this.request('add_checklist_task', { project_id: this.currentProjectId, category: val.trim(), task_name: '' }); this.showToast('New Phase/Category added.'); this.renderProjectChecklist(); } },
    deleteCategoryDB: async function (category) { if (confirm(`Delete category "${category}" and all its tasks?`)) { await this.request('delete_checklist_category', { project_id: this.currentProjectId, category: category }); this.renderProjectChecklist(); } },

    // --- MANPOWER ASSIGNMENT (TAB 3) ---
    renderManpowerAssignments: async function () {
        const users = await this.request('get_active_manpower'); const data = await this.request('get_project_data', { project_id: this.currentProjectId });
        const workerSelect = document.getElementById('assign-worker'); workerSelect.innerHTML = '<option value="">Select Worker</option>';
        if (users && Array.isArray(users)) users.forEach(m => { workerSelect.innerHTML += `<option value="${escapeHTML(m.name)}">${escapeHTML(m.name)} (${escapeHTML(m.position) || 'Worker'})</option>`; });
        const catSelect = document.getElementById('assign-category'); catSelect.innerHTML = '<option value="">Select Category/Phase</option>';
        const tbody = document.getElementById('assignments-content'); tbody.innerHTML = '';
        if (!data.checklist || data.checklist.length === 0) { tbody.innerHTML = `<tr><td colspan="3" class="empty-state-wrapper"><p>No categories found in checklist.</p></td></tr>`; return; }
        let grouped = {}; data.checklist.forEach(item => { const cat = item.category || 'Uncategorized'; if (!grouped[cat]) grouped[cat] = { assigned: item.assigned_worker }; if (item.assigned_worker) grouped[cat].assigned = item.assigned_worker; });
        let hasAssignments = false;
        Object.keys(grouped).forEach(cat => { catSelect.innerHTML += `<option value="${cat}">${cat}</option>`; if (grouped[cat].assigned) { hasAssignments = true; tbody.innerHTML += `<tr><td><b style="color:var(--text-dark);">${cat}</b></td><td><span class="badge-assigned" style="margin:0;"><i class="fa-solid fa-user-check"></i> ${grouped[cat].assigned}</span></td><td><button class="btn-danger" style="height: 26px; padding: 0 8px; border-radius: 4px;" onclick="app.removeWorkerAssignment('${cat}')"><i class="fa-solid fa-trash"></i></button></td></tr>`; } });
        if (!hasAssignments) tbody.innerHTML = `<tr><td colspan="3" class="empty-state-wrapper"><p>No workers assigned to specific tasks yet.</p></td></tr>`;
    },
    assignWorkerToCategory: async function () { const cat = document.getElementById('assign-category').value; const worker = document.getElementById('assign-worker').value; if (!cat || !worker) { this.showToast('Select both Category and Worker.', 'error'); return; } await this.request('assign_worker', { project_id: this.currentProjectId, category: cat, worker: worker }); this.renderManpowerAssignments(); this.showToast(`${worker} assigned to ${cat}. Sync to Payroll Enabled.`); },
    removeWorkerAssignment: async function (cat) { await this.request('remove_worker', { project_id: this.currentProjectId, category: cat }); this.renderManpowerAssignments(); },

    // --- MATERIAL ISSUANCES FROM DB ---
    renderProjectWorkspaceMaterials: async function () {
        const inventory = await this.request('get_inventory'); const projData = await this.request('get_project_data', { project_id: this.currentProjectId });
        const select = document.getElementById('issue-item'); select.innerHTML = '<option value="">Select Inventory Item</option>';
        inventory.forEach(inv => { select.innerHTML += `<option value="${inv.id}">${escapeHTML(inv.name)} (Stock: ${escapeHTML(inv.stock)} ${escapeHTML(inv.unit)})</option>`; });
        const allProjs = window.allProjectsData || await this.request('get_projects'); const currentP = allProjs.find(p => p.id == this.currentProjectId);
        if (currentP) document.getElementById('issue-receiver').value = currentP.foreman || '';
        const issuances = projData.issuances || []; const tbody = document.getElementById('issuance-history-content'); tbody.innerHTML = ''; let totalItems = 0; let totalCost = 0;
        if (issuances.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="empty-state-wrapper"><i class="fa-solid fa-clipboard"></i><p>No materials issued to this site yet.</p></td></tr>`;
        } else { issuances.forEach(i => { const rowCost = i.qty * i.unit_cost; totalItems += parseInt(i.qty); totalCost += parseFloat(rowCost); let fmtCost = parseFloat(rowCost).toLocaleString('en-US', { minimumFractionDigits: 2 }); tbody.innerHTML += `<tr><td style="color:var(--text-muted); font-weight:600;">${escapeHTML(i.issue_date.split(' ')[0])}</td><td><b style="color:var(--text-dark);">${escapeHTML(i.item_name)}</b></td><td style="font-weight:700;">${escapeHTML(i.qty)} <span style="color:var(--text-muted); font-weight:500;">${escapeHTML(i.unit)}</span></td><td style="color:var(--success); font-weight:700;">₱${fmtCost}</td><td>${escapeHTML(i.receiver)}</td></tr>`; }); }
        document.getElementById('proj-summary-qty').innerText = `${totalItems} Total Qty`; document.getElementById('proj-summary-cost').innerText = `₱${parseFloat(totalCost).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
    },
    issueMaterial: async function () {
        const itemId = document.getElementById('issue-item').value; const qty = parseInt(document.getElementById('issue-qty').value); const receiver = document.getElementById('issue-receiver').value;
        if (!itemId || !qty || qty <= 0 || !receiver) { this.showToast('Item, Valid Quantity, and Receiver are required!', 'error'); return; }
        const res = await this.request('issue_material', { project_id: this.currentProjectId, item_id: itemId, qty: qty, receiver: receiver });
        if (res.status === 'error') { this.showToast(res.message, 'error'); return; }
        document.getElementById('issue-item').value = ''; document.getElementById('issue-qty').value = '';
        this.renderProjectWorkspaceMaterials(); this.showToast(`Material successfully issued to site.`);
    },

    // ==========================================
    // MODULE: MATERIAL SUPPLIERS & INVENTORY (DB)
    // ==========================================
    switchMatTab: function (tabId) { document.getElementById('tab-mat-suppliers').classList.remove('active'); document.getElementById('tab-mat-inventory').classList.remove('active'); document.getElementById('tab-mat-' + tabId).classList.add('active'); document.getElementById('mtab-suppliers').classList.remove('active'); document.getElementById('mtab-inventory').classList.remove('active'); document.getElementById('mtab-' + tabId).classList.add('active'); },
    loadSuppliersDashboard: async function () { this.renderInventoryTable(); },
    renderInventoryTable: async function () {
        const inventory = await this.request('get_inventory'); const suppliers = await this.request('get_suppliers'); const tbody = document.getElementById('inventory-content'); if (!tbody) return; tbody.innerHTML = ''; let lowStockCount = 0;
        if (inventory.length === 0) { tbody.innerHTML = `<tr><td colspan="5" class="empty-state-wrapper"><i class="fa-solid fa-box-open"></i><p>No inventory items available.</p></td></tr>`; }
        inventory.forEach(inv => { let stockStyle = "color: var(--text-dark);"; if (inv.stock <= 5) { stockStyle = "color: var(--danger); font-weight: 800;"; lowStockCount++; } let supMatch = suppliers.find(s => s.name === inv.supplier); let supplierUI = supMatch ? `<b>${escapeHTML(supMatch.name)}</b><br><small style="color:var(--text-muted);">${escapeHTML(supMatch.contact)}</small>` : `<b style="color:var(--text-muted);">Unassigned</b>`; let fmtCost = parseFloat(inv.unit_cost).toLocaleString('en-US', { minimumFractionDigits: 2 }); tbody.innerHTML += `<tr><td><b style="color:var(--text-dark); font-size:0.9rem;">${escapeHTML(inv.name)}</b><br><small style="color:var(--text-muted);">${escapeHTML(inv.unit)}</small></td><td style="color:var(--text-muted); font-weight:600;">${escapeHTML(inv.category)}</td><td><span style="${stockStyle} font-size:1rem;">${escapeHTML(inv.stock)}</span></td><td style="color:var(--success); font-weight:700;">₱${fmtCost}</td><td>${supplierUI}</td></tr>`; });
        document.getElementById('stat-low-stock').innerText = `${lowStockCount} Items`;
    },
    populateInventorySupplierDropdown: async function () {         const suppliers = await this.request('get_suppliers'); const select = document.getElementById('stock-supplier'); if (!select) return; select.innerHTML = '<option value="">Select Supplier</option>'; suppliers.filter(s => s.status === 'Active').forEach(sup => { select.innerHTML += `<option value="${escapeHTML(sup.name)}">${escapeHTML(sup.name)}</option>`; }); },
    populateCategoryDropdown: async function () { const categories = await this.request('get_inventory_categories'); const select = document.getElementById('stock-category'); if (!select) return; select.innerHTML = '<option value="">Select Category</option>'; categories.forEach(cat => { select.innerHTML += `<option value="${escapeHTML(cat)}">${escapeHTML(cat)}</option>`; }); select.innerHTML += `<option value="ADD_NEW" style="font-weight: 800; color: var(--primary-hover);">+ Add New Category</option>`; },
    handleCategoryChange: function (val) { const newCatInput = document.getElementById('stock-category-new'); if (!newCatInput) return; if (val === 'ADD_NEW') { newCatInput.style.display = 'block'; newCatInput.focus(); } else { newCatInput.style.display = 'none'; newCatInput.value = ''; } },
    submitNewSupplier: async function () {
        const name = document.getElementById('new-sup-name').value; const mats = document.getElementById('new-sup-materials').value; const contact = document.getElementById('new-sup-contact').value; const email = document.getElementById('new-sup-email').value;
        if (!name || !mats || !contact) { this.showToast('Name, Materials, and Contact are required!', 'error'); return; }
        await this.request('add_supplier', { name, materials: mats, contact, email });
        document.getElementById('new-sup-name').value = ''; document.getElementById('new-sup-materials').value = ''; document.getElementById('new-sup-contact').value = ''; document.getElementById('new-sup-email').value = ''; this.closeModal('modal-add-supplier'); this.loadSupplierRecords(); this.loadSupplierSummary(); this.showToast('New supplier successfully added.');
    },
    submitNewStock: async function () {
        const name = document.getElementById('stock-name').value; let cat = document.getElementById('stock-category').value; const qty = document.getElementById('stock-qty').value; const unit = document.getElementById('stock-unit').value; const cost = document.getElementById('stock-cost').value; const supplier = document.getElementById('stock-supplier').value;
        if (cat === 'ADD_NEW') { cat = document.getElementById('stock-category-new').value.trim(); if (cat) await this.request('add_inventory_category', { name: cat }); }
        if (!name || !qty || !unit || !cost || !cat) { this.showToast('Name, Category, Stock, Unit, and Cost are required!', 'error'); return; }
        await this.request('add_inventory', { name, category: cat, qty, unit, cost, supplier });
        document.getElementById('stock-name').value = ''; document.getElementById('stock-category').value = ''; document.getElementById('stock-category-new').value = ''; document.getElementById('stock-category-new').style.display = 'none'; document.getElementById('stock-qty').value = ''; document.getElementById('stock-unit').value = ''; document.getElementById('stock-cost').value = ''; document.getElementById('stock-supplier').value = ''; this.closeModal('modal-add-stock'); this.renderInventoryTable(); this.showToast(`Inventory item added successfully.`);
    },

    // ==========================================
    // MODULE: MATERIAL SUPPLIER (INLINE FORM)
    // ==========================================
    loadMaterialSuppliers: async function () {
        this.loadSupplierRecords();
        this.loadSupplierSummary();
    },
    loadSupplierRecords: async function (query) {
        const data = query ? await this.request('search_suppliers', { query }) : await this.request('get_suppliers');
        const tbody = document.getElementById('suppliers-content');
        if (!tbody) return;
        const suppliers = (data && data.status === 'success' && data.data) ? data.data : (Array.isArray(data) ? data : []);
        tbody.innerHTML = '';
        if (suppliers.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" class="empty-state-wrapper"><i class="fa-solid fa-truck-field"></i><p>No suppliers found.</p></td></tr>`;
            return;
        }
        suppliers.forEach(s => {
            let badgeClass = 'ms-badge-active';
            let badgeLabel = 'Active';
            if (s.status === 'Inactive') { badgeClass = 'ms-badge-inactive'; badgeLabel = 'Inactive'; }
            else if (s.status === 'Preferred') { badgeClass = 'ms-badge-preferred'; badgeLabel = 'Preferred'; }
            else if (s.status === 'Blacklisted') { badgeClass = 'ms-badge-blacklisted'; badgeLabel = 'Blacklisted'; }
            tbody.innerHTML += `<tr>
                <td class="ms-col-name"><b>${app.esc(s.name)}</b></td>
                <td class="ms-col-person">${app.esc(s.contact_person) || '-'}</td>
                <td class="ms-col-contact">${app.esc(s.contact) || '-'}</td>
                <td class="ms-col-email">${app.esc(s.email) || '-'}</td>
                <td class="ms-col-category">${app.esc(s.material_category) || '-'}</td>
                <td class="ms-col-materials">${app.esc(s.materials) || '-'}</td>
                <td class="ms-col-quote ms-amount-cell">${parseFloat(s.price_quote || 0).toLocaleString('en-US', {style:'currency', currency:'PHP'})}</td>
                <td class="ms-col-terms">${app.esc(s.payment_terms) || '-'}</td>
                <td class="ms-col-status"><span class="ms-badge ${badgeClass}">${badgeLabel}</span></td>
                <td class="ms-col-remarks">${app.esc(s.remarks) || '-'}</td>
                <td class="ms-col-action">
                    <button class="action-btn" onclick="app.editSupplierRecord('${s.id}')" title="Edit"><i class="fa-solid fa-pencil"></i></button>
                    ${s.status !== 'Inactive' ? `<button class="action-btn" onclick="app.deleteSupplierRecord('${s.id}')" title="Deactivate"><i class="fa-solid fa-ban"></i></button>` : ''}
                </td>
            </tr>`;
        });
    },
    loadSupplierSummary: async function () {
        const data = await this.request('get_suppliers');
        const suppliers = (data && data.status === 'success' && data.data) ? data.data : (Array.isArray(data) ? data : []);
        const total = suppliers.length;
        const active = suppliers.filter(s => s.status === 'Active').length;
        const inactive = suppliers.filter(s => s.status === 'Inactive').length;
        const preferred = suppliers.filter(s => s.status === 'Preferred').length;
        const blacklisted = suppliers.filter(s => s.status === 'Blacklisted').length;
        const el = (id) => document.getElementById(id);
        if (el('ms-total-suppliers')) el('ms-total-suppliers').innerText = total;
        if (el('ms-active-suppliers')) el('ms-active-suppliers').innerText = active;
        if (el('ms-inactive-suppliers')) el('ms-inactive-suppliers').innerText = inactive;
        if (el('ms-preferred-suppliers')) el('ms-preferred-suppliers').innerText = preferred;
        if (el('ms-blacklisted-suppliers')) el('ms-blacklisted-suppliers').innerText = blacklisted;
        if (el('stat-active-suppliers')) el('stat-active-suppliers').innerText = active;
    },
    formatPriceQuote: function (input) {
        let val = input.value.replace(/[^0-9.]/g, '');
        if (val) {
            const num = parseFloat(val) || 0;
            input.value = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    },
    saveSupplierRecord: async function () {
        const id = document.getElementById('ms-id').value;
        const name = document.getElementById('ms-name').value.trim();
        if (!name) { this.showToast('Supplier Name is required.', 'error'); return; }
        const contact_person = document.getElementById('ms-contact-person').value.trim();
        const contact = document.getElementById('ms-contact').value.trim();
        const email = document.getElementById('ms-email').value.trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { this.showToast('Invalid email format.', 'error'); return; }
        const address = document.getElementById('ms-address').value.trim();
        const material_category = document.getElementById('ms-material-category').value.trim();
        const materials = document.getElementById('ms-materials').value.trim();
        let price_quote = document.getElementById('ms-price-quote').value.replace(/[^0-9.]/g, '');
        price_quote = parseFloat(price_quote) || 0;
        if (price_quote < 0) { this.showToast('Price Quote must be 0 or greater.', 'error'); return; }
        const payment_terms = document.getElementById('ms-payment-terms').value.trim();
        const status = document.getElementById('ms-status').value;
        const remarks = document.getElementById('ms-remarks').value.trim();

        const payload = {
            name, contact_person, contact, email, address,
            material_category, materials,
            price_quote, payment_terms,
            status, remarks
        };
        if (id) payload.id = id;

        const action = id ? 'update_supplier' : 'add_supplier';
        const res = await this.request(action, payload);
        if (res && res.status === 'success') {
            this.showToast(id ? 'Supplier updated.' : 'Supplier added.');
            this.cancelEditSupplierRecord();
            await this.loadSupplierRecords();
            this.loadSupplierSummary();
        } else {
            this.showToast(res?.message || 'Operation failed.', 'error');
        }
    },
    editSupplierRecord: async function (id) {
        const data = await this.request('get_suppliers');
        const suppliers = (data && data.status === 'success' && data.data) ? data.data : (Array.isArray(data) ? data : []);
        const r = suppliers.find(s => String(s.id) === String(id));
        if (!r) { this.showToast('Record not found.', 'error'); return; }
        document.getElementById('ms-id').value = r.id;
        document.getElementById('ms-name').value = r.name || '';
        document.getElementById('ms-contact-person').value = r.contact_person || '';
        document.getElementById('ms-contact').value = r.contact || '';
        document.getElementById('ms-email').value = r.email || '';
        document.getElementById('ms-address').value = r.address || '';
        document.getElementById('ms-material-category').value = r.material_category || '';
        document.getElementById('ms-materials').value = r.materials || '';
        const pq = parseFloat(r.price_quote || 0);
        document.getElementById('ms-price-quote').value = pq.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('ms-payment-terms').value = r.payment_terms || '';
        document.getElementById('ms-status').value = r.status || 'Active';
        document.getElementById('ms-remarks').value = r.remarks || '';
        document.getElementById('ms-submit-text').textContent = 'Update Supplier';
        document.getElementById('ms-cancel-btn').style.display = '';
        document.querySelector('#mod-materials .card:first-of-type')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    cancelEditSupplierRecord: function () {
        document.getElementById('ms-id').value = '';
        document.getElementById('ms-name').value = '';
        document.getElementById('ms-contact-person').value = '';
        document.getElementById('ms-contact').value = '';
        document.getElementById('ms-email').value = '';
        document.getElementById('ms-address').value = '';
        document.getElementById('ms-material-category').value = '';
        document.getElementById('ms-materials').value = '';
        document.getElementById('ms-price-quote').value = '';
        document.getElementById('ms-payment-terms').value = '';
        document.getElementById('ms-status').value = 'Active';
        document.getElementById('ms-remarks').value = '';
        document.getElementById('ms-submit-text').textContent = 'Add Supplier';
        document.getElementById('ms-cancel-btn').style.display = 'none';
    },
    deleteSupplierRecord: async function (id) {
        if (!confirm('Deactivate this supplier?')) return;
        const res = await this.request('delete_supplier', { id });
        if (res && res.status === 'success') {
            this.showToast('Supplier deactivated.');
            await this.loadSupplierRecords();
            this.loadSupplierSummary();
        } else {
            this.showToast(res?.message || 'Operation failed.', 'error');
        }
    },

    // ==========================================
    // MODULE: MANPOWER (RECORD LIST) 
    // ==========================================

    // FIX: Populates all projects properly now
    loadProjectOptionsForManpower: async function () {
        try {
            const proj = await this.request('get_projects');
            const select = document.getElementById('man-project');
            if (!select) return;
            select.innerHTML = '<option value="">Select Project (Optional)</option>';
            if (proj && Array.isArray(proj)) {
                proj.forEach(p => select.innerHTML += `<option value="${p.id}">${escapeHTML(p.name)} - ${escapeHTML(p.location)}</option>`);
            }
        } catch (e) { console.error(e); }
    },

    populateManpowerDropdowns: async function () {
        const skills = await this.request('get_manpower_skills'); const select = document.getElementById('man-skills'); if (!select) return;
        select.innerHTML = '<option value="">Select Skill / Folder</option>';
        if (skills && Array.isArray(skills)) { skills.forEach(s => { const sName = s.skill_name || 'Uncategorized'; if (sName !== 'Uncategorized') { select.innerHTML += `<option value="${escapeHTML(sName)}">${escapeHTML(sName)}</option>`; } }); }
        select.innerHTML += `<option value="ADD_NEW" style="font-weight: 800; color: var(--primary-hover);">+ Add New Folder (Via Dropdown)</option>`;
    },
    handleSkillChange: function (val) { const newCatInput = document.getElementById('man-skills-new'); if (!newCatInput) return; if (val === 'ADD_NEW') { newCatInput.style.display = 'block'; newCatInput.focus(); } else { newCatInput.style.display = 'none'; newCatInput.value = ''; } },
    handlePosChange: function (val) { const newPosInput = document.getElementById('man-pos-new'); if (!newPosInput) return; if (val === 'ADD_NEW') { newPosInput.style.display = 'block'; newPosInput.focus(); } else { newPosInput.style.display = 'none'; newPosInput.value = ''; } },

    addFolderOnly: async function () { const name = prompt("Enter new Empty Folder / Skill Category name:"); if (name && name.trim() !== '') { await this.request('add_skill_category', { name: name.trim() }); this.loadManpowerFolders(); this.populateManpowerDropdowns(); this.showToast('Empty Folder created.'); } },
    editFolder: async function (oldName) { const newName = prompt("Rename Folder '" + oldName + "' to:", oldName); if (newName && newName.trim() !== '' && newName !== oldName) { await this.request('edit_skill_category', { old_name: oldName, new_name: newName.trim() }); this.loadManpowerFolders(); this.populateManpowerDropdowns(); this.showToast('Folder renamed successfully.'); } },
    deleteFolder: async function (name) { if (confirm(`Are you sure you want to delete the folder "${name}"? Workers in this folder will be moved to "Uncategorized".`)) { await this.request('delete_skill_category', { name: name }); this.loadManpowerFolders(); this.populateManpowerDropdowns(); this.showToast('Folder deleted. Workers moved to Uncategorized.'); } },

    archiveManpower: async function (id) { if (confirm('Archive this worker? They will be removed from active sites and sent to the Archived folder.')) { await this.request('archive_manpower', { id: id }); this.showToast('Worker archived.'); const currentTitleRaw = document.getElementById('current-skill-title').innerText.trim(); const cleanTitle = currentTitleRaw.replace('Rename Folder', '').replace('Delete Folder', '').trim(); if (cleanTitle) { this.openSkillFolder(cleanTitle); } else { this.loadManpowerFolders(); } } },
    restoreManpower: async function (id) { if (confirm('Restore this worker back to active status?')) { await this.request('restore_manpower', { id: id }); this.showToast('Worker restored.'); this.openArchivedFolder(); } },
    // ==========================================
    // MANPOWER / RECORD LIST
    // ==========================================
    addManpower: async function () {
        const get = (id) => document.getElementById(id);

        const id = (get('man-id')?.value || '').trim();
        const nameInput = get('man-name');
        const skillsInput = get('man-skills');
        const skillsNewInput = get('man-skills-new');
        const positionInput = get('man-pos');
        const positionNewInput = get('man-pos-new');
        const salaryInput = get('man-salary');
        const projectInput = get('man-project');
        const foremanInput = get('man-foreman');
        const contactInput = get('man-contact');
        const addressInput = get('man-address');
        const statusInput = get('man-status');
        const photoInput = get('man-photo');

        const name = (nameInput?.value || '').trim();
        let skills = (skillsInput?.value || '').trim();
        if (skills === 'ADD_NEW') skills = (skillsNewInput?.value || '').trim();
        let position = (positionInput?.value || '').trim();
        if (position === 'ADD_NEW') position = (positionNewInput?.value || '').trim();
        const salary = (salaryInput?.value || '').trim();
        const project_id = projectInput?.value || '';
        const project_site_text = document.getElementById('man-project-text')?.value?.trim() || '';
        const foreman = foremanInput?.value || '';
        const contact_number = contactInput?.value || '';
        const address = addressInput?.value || '';
        const status = statusInput?.value || 'Active';

        if (!name || !position || !salary || !skills) {
            this.showToast('Fill in Name, Skill, Position, and Salary Rate!', 'error');
            return;
        }

        const action = id ? 'update_manpower' : 'add_manpower';
        const fd = new FormData();
        fd.append('name', name);
        fd.append('skills', skills);
        fd.append('position', position);
        fd.append('salary', salary);
        fd.append('project_id', project_id || '');
        fd.append('project_site_text', project_site_text);
        fd.append('foreman', foreman);
        fd.append('contact_number', contact_number);
        fd.append('address', address);
        fd.append('status', status);
        if (id) fd.append('id', id);
        if (photoInput && photoInput.files && photoInput.files.length > 0) {
            fd.append('photo', photoInput.files[0]);
        }

        const res = await this.request(action, fd, true);

        if (res.status === 'success') {
            this.clearManpowerForm();
            this.populateManpowerDropdowns();
            if (this._currentManpowerView === 'foreman') {
                this.loadManpowerForemanGroups();
            } else {
                this.loadManpowerFolders();
            }
            if (typeof this.loadDashboard === 'function') this.loadDashboard();
            if (typeof this.populateForemanDropdown === 'function') this.populateForemanDropdown();
            this.showToast(id ? "Record updated." : "Record added.");
        } else {
            this.showToast("Warning: " + (res.message || 'Unable to save record.'), 'error');
        }
    },

    clearManpowerForm: function () {
        ['man-id', 'man-name', 'man-skills-new', 'man-pos-new', 'man-salary', 'man-contact', 'man-address', 'man-foreman', 'man-project-text'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const skillsSelect = document.getElementById('man-skills');
        if (skillsSelect) skillsSelect.value = '';
        const posSelect = document.getElementById('man-pos');
        if (posSelect) posSelect.value = '';
        const projectSelect = document.getElementById('man-project');
        if (projectSelect) projectSelect.value = '';
        const statusSelect = document.getElementById('man-status');
        if (statusSelect) statusSelect.value = 'Active';
        const photoInput = document.getElementById('man-photo');
        if (photoInput) photoInput.value = '';
        const cancelBtn = document.getElementById('man-cancel-btn');
        if (cancelBtn) cancelBtn.style.display = 'none';
        const submitText = document.getElementById('man-submit-text');
        if (submitText) submitText.innerText = 'Add Record';
        this.clearManpowerPhotoPreview();
    },

    previewManpowerPhoto: function (input) {
        const previewImg = document.getElementById('man-photo-img');
        const placeholder = document.getElementById('man-photo-placeholder');
        if (!previewImg || !placeholder) return;

        const file = input.files && input.files[0];
        if (!file) {
            this.clearManpowerPhotoPreview();
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.showToast('Invalid file type. Only JPG, PNG, and WEBP images are allowed.', 'error');
            input.value = '';
            this.clearManpowerPhotoPreview();
            return;
        }

        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            this.showToast('File too large. Maximum size is 5MB.', 'error');
            input.value = '';
            this.clearManpowerPhotoPreview();
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.onerror = function () {
            app.showToast('Failed to read file.', 'error');
        };
        reader.readAsDataURL(file);
    },

    clearManpowerPhotoPreview: function () {
        const previewImg = document.getElementById('man-photo-img');
        const placeholder = document.getElementById('man-photo-placeholder');
        if (previewImg) {
            previewImg.src = '';
            previewImg.style.display = 'none';
        }
        if (placeholder) placeholder.style.display = 'flex';
    },

    setManpowerPhotoPreview: function (path) {
        const previewImg = document.getElementById('man-photo-img');
        const placeholder = document.getElementById('man-photo-placeholder');
        if (!previewImg || !placeholder) return;
        if (path && path.trim() !== '') {
            previewImg.src = path;
            previewImg.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            this.clearManpowerPhotoPreview();
        }
    },

    cancelEditManpower: function () {
        this.clearManpowerForm();
    },

    loadForemenDropdown: async function () {
        const foremen = await this.request('get_foremen_list');
        const select = document.getElementById('man-foreman');
        if (!select) return;
        select.innerHTML = '<option value="">No Foreman Assigned</option>';
        if (foremen && Array.isArray(foremen)) {
            foremen.forEach(f => {
                select.innerHTML += `<option value="${escapeHTML(f.name)}">${escapeHTML(f.name)} ${f.skills ? '(' + escapeHTML(f.skills) + ')' : ''}</option>`;
            });
        }
        // Also add foremen from workers' foreman field
        const allForemanNames = await this.request('get_all_foreman_names');
        if (allForemanNames && Array.isArray(allForemanNames)) {
            allForemanNames.forEach(name => {
                if (name && !Array.from(select.options).some(o => o.value === name)) {
                    select.innerHTML += `<option value="${escapeHTML(name)}">${escapeHTML(name)}</option>`;
                }
            });
        }
    },

    switchManpowerView: function (view) {
        this._currentManpowerView = view;
        document.querySelectorAll('.man-tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`.man-tab[data-view="${view}"]`).classList.add('active');

        const foremanView = document.getElementById('manpower-foreman-view');
        const skillsView = document.getElementById('manpower-skills-view');
        const tableView = document.getElementById('manpower-table-view');

        if (tableView) tableView.style.display = 'none';

        if (view === 'foreman') {
            if (foremanView) foremanView.style.display = 'block';
            if (skillsView) skillsView.style.display = 'none';
            this.loadManpowerForemanGroups();
        } else {
            if (foremanView) foremanView.style.display = 'none';
            if (skillsView) skillsView.style.display = 'block';
            this.loadManpowerFolders();
        }
    },

    loadManpowerForemanGroups: async function () {
        const grid = document.getElementById('foreman-groups-grid');
        if (!grid) return;
        grid.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:20px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</p>';

        const foremen = await this.request('get_foremen_list');
        grid.innerHTML = '';

        const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
        });

        const escapeJs = (value) => String(value || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\n/g, '\\n').replace(/\r/g, '\\r');

        if (foremen && Array.isArray(foremen) && foremen.length > 0) {
            foremen.forEach(f => {
                const count = parseInt(f.worker_count || 0);
                grid.innerHTML += `
                    <div class="man-group-card" onclick="app.openForemanGroup('${escapeJs(f.name)}')">
                        <div class="man-group-icon"><i class="fa-solid fa-people-group"></i></div>
                        <div class="man-group-info">
                            <div class="man-group-name">${escapeHtml(f.name)}</div>
                            <div class="man-group-meta">${count} worker(s)${f.skills ? ' · ' + escapeHtml(f.skills) : ''}${f.contact_number ? ' · ' + escapeHtml(f.contact_number) : ''}</div>
                        </div>
                        <div class="man-group-count">${count}</div>
                    </div>
                `;
            });
        }

        // Always show Unassigned group
        grid.innerHTML += `
            <div class="man-group-card man-group-unassigned" onclick="app.openForemanGroup('')">
                <div class="man-group-icon"><i class="fa-solid fa-user-slash"></i></div>
                <div class="man-group-info">
                    <div class="man-group-name">No Foreman Assigned</div>
                    <div class="man-group-meta">Workers without a foreman assignment</div>
                </div>
                <div class="man-group-count"><i class="fa-solid fa-chevron-right"></i></div>
            </div>
        `;

        // Archived card
        grid.innerHTML += `
            <div class="man-group-card" style="background:#FEF2F2; border-color:#FCA5A5;" onclick="app.openArchivedFolder()">
                <div class="man-group-icon" style="background:#FEE2E2; color:var(--danger);"><i class="fa-solid fa-box-archive"></i></div>
                <div class="man-group-info">
                    <div class="man-group-name" style="color:var(--danger);">Archived Records</div>
                    <div class="man-group-meta">Inactive Worker Pool</div>
                </div>
                <div class="man-group-count"><i class="fa-solid fa-chevron-right"></i></div>
            </div>
        `;

        if ((!foremen || !Array.isArray(foremen) || foremen.length === 0)) {
            grid.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:20px;">No foreman groups found. Add a worker with a Foreman position to get started.</p>' + grid.innerHTML;
        }
    },

    openForemanGroup: async function (foremanName) {
        const tableView = document.getElementById('manpower-table-view');
        const foremanView = document.getElementById('manpower-foreman-view');
        if (foremanView) foremanView.style.display = 'none';
        if (tableView) tableView.style.display = 'block';

        const title = document.getElementById('current-manpower-title');
        const safeName = foremanName || 'No Foreman Assigned';
        title.innerHTML = `<i class="fa-solid fa-people-group" style="margin-right:8px;"></i> ${escapeHTML(safeName)}`;

        const table = document.getElementById('table-users');
        table.innerHTML = `<thead><tr><th>Name</th><th>Project Assigned</th><th>Skills</th><th>Position</th><th>Rate</th><th>Contact</th><th>Foreman</th><th>Bio Data</th><th>Action</th></tr></thead><tbody></tbody>`;

        const tbody = document.querySelector('#table-users tbody');
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;">Loading...</td></tr>`;

        let workerList;
        if (foremanName) {
            workerList = await this.request('get_manpower_by_foreman', { foreman: foremanName });
        } else {
            workerList = await this.request('get_unassigned_workers');
        }

        tbody.innerHTML = '';
        if (!workerList || workerList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;">No workers found in this group.</td></tr>`;
            return;
        }

        workerList.forEach(w => {
            let photoUrl = w.photo || w.photo_path;
            let resumeButton = '';
            let avatarHtml = `<div style="width:36px;height:36px;border-radius:50%;background:#E5E7EB;display:flex;align-items:center;justify-content:center;color:#9CA3AF;"><i class="fa-solid fa-user"></i></div>`;

            if (photoUrl) {
                resumeButton = `<button class="btn-outline btn-sm" onclick="app.viewAttachedFile('${photoUrl}')"><i class="fa-solid fa-image"></i> View</button>`;
                avatarHtml = `<img src="${photoUrl}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--border);cursor:pointer;" onclick="app.viewAttachedFile('${photoUrl}')" onerror="this.src='https://ui-avatars.com/api/?name=${w.name}&background=FACC15&color=000'">`;
            } else {
                resumeButton = `<label class="btn-outline btn-sm" style="cursor:pointer;margin:0;padding:4px 8px;font-size:0.75rem;border-color:var(--primary);color:var(--text-dark);background:#FEFCE8;"><i class="fa-solid fa-upload"></i> Add Bio<input type="file" style="display:none;" accept="image/*" onchange="app.uploadBioData(${w.id}, this)"></label>`;
            }

            let nameDisplay = `<div style="display:flex;align-items:center;gap:10px;">${avatarHtml}<div><b style="color:var(--text-dark);">${escapeHTML(w.name)}</b><br><small style="color:var(--text-muted);">ID: ${w.id}</small></div></div>`;

            tbody.innerHTML += `<tr>
                <td>${nameDisplay}</td>
                <td>${escapeHTML(w.project_site_text || w.project_name) || '<small style="color:var(--text-muted);">Unassigned</small>'}</td>
                <td>${escapeHTML(w.skills) || 'Uncategorized'}</td>
                <td>${escapeHTML(w.position) || 'N/A'}</td>
                <td style="font-weight:600;">₱${parseFloat(w.rate || w.salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                <td>${escapeHTML(w.contact_number) || '<span style="color:#D1D5DB;">—</span>'}</td>
                <td>${escapeHTML(w.foreman) || '<span style="color:#D1D5DB;">—</span>'}</td>
                <td>${resumeButton}</td>
                <td>
                    <button class="btn-outline btn-sm" onclick="app.editManpower(${w.id})" title="Edit"><i class="fa-solid fa-pencil"></i></button>
                    <button class="btn-outline btn-sm" style="color:var(--danger);border-color:#FCA5A5;" onclick="app.archiveManpower(${w.id})"><i class="fa-solid fa-box-archive"></i></button>
                </td>
            </tr>`;
        });
    },

    searchManpower: async function (query) {
        const foremanView = document.getElementById('manpower-foreman-view');
        const skillsView = document.getElementById('manpower-skills-view');
        const tableView = document.getElementById('manpower-table-view');

        if (!query || query.trim() === '') {
            // Reset to current view
            if (this._currentManpowerView === 'foreman') {
                if (foremanView) foremanView.style.display = 'block';
                if (skillsView) skillsView.style.display = 'none';
                if (tableView) tableView.style.display = 'none';
                this.loadManpowerForemanGroups();
            } else {
                if (foremanView) foremanView.style.display = 'none';
                if (skillsView) skillsView.style.display = 'block';
                if (tableView) tableView.style.display = 'none';
                this.loadManpowerFolders();
            }
            return;
        }

        // Show results in table view
        if (foremanView) foremanView.style.display = 'none';
        if (skillsView) skillsView.style.display = 'none';
        if (tableView) tableView.style.display = 'block';

        const title = document.getElementById('current-manpower-title');
        title.innerHTML = `<i class="fa-solid fa-magnifying-glass" style="margin-right:8px;"></i> Search: "${escapeHTML(query)}"`;

        const table = document.getElementById('table-users');
        table.innerHTML = `<thead><tr><th>Name</th><th>Project Assigned</th><th>Skills</th><th>Position</th><th>Rate</th><th>Contact</th><th>Foreman</th><th>Bio Data</th><th>Action</th></tr></thead><tbody></tbody>`;

        const tbody = document.querySelector('#table-users tbody');
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</td></tr>`;

        const workerList = await this.request('search_manpower', { query: query.trim() });

        tbody.innerHTML = '';
        if (!workerList || workerList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;">No records found matching "${escapeHTML(query)}".</td></tr>`;
            return;
        }

        workerList.forEach(w => {
            let photoUrl = w.photo || w.photo_path;
            let resumeButton = '';
            let avatarHtml = `<div style="width:36px;height:36px;border-radius:50%;background:#E5E7EB;display:flex;align-items:center;justify-content:center;color:#9CA3AF;"><i class="fa-solid fa-user"></i></div>`;

            if (photoUrl) {
                resumeButton = `<button class="btn-outline btn-sm" onclick="app.viewAttachedFile('${photoUrl}')"><i class="fa-solid fa-image"></i> View</button>`;
                avatarHtml = `<img src="${photoUrl}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--border);cursor:pointer;" onclick="app.viewAttachedFile('${photoUrl}')" onerror="this.src='https://ui-avatars.com/api/?name=${w.name}&background=FACC15&color=000'">`;
            } else {
                resumeButton = `<label class="btn-outline btn-sm" style="cursor:pointer;margin:0;padding:4px 8px;font-size:0.75rem;border-color:var(--primary);color:var(--text-dark);background:#FEFCE8;"><i class="fa-solid fa-upload"></i> Add Bio<input type="file" style="display:none;" accept="image/*" onchange="app.uploadBioData(${w.id}, this)"></label>`;
            }

            let nameDisplay = `<div style="display:flex;align-items:center;gap:10px;">${avatarHtml}<div><b style="color:var(--text-dark);">${escapeHTML(w.name)}</b><br><small style="color:var(--text-muted);">ID: ${w.id}</small></div></div>`;

            tbody.innerHTML += `<tr>
                <td>${nameDisplay}</td>
                <td>${escapeHTML(w.project_site_text || w.project_name) || '<small style="color:var(--text-muted);">Unassigned</small>'}</td>
                <td>${escapeHTML(w.skills) || 'Uncategorized'}</td>
                <td>${escapeHTML(w.position) || 'N/A'}</td>
                <td style="font-weight:600;">₱${parseFloat(w.rate || w.salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                <td>${escapeHTML(w.contact_number) || '<span style="color:#D1D5DB;">—</span>'}</td>
                <td>${escapeHTML(w.foreman) || '<span style="color:#D1D5DB;">—</span>'}</td>
                <td>${resumeButton}</td>
                <td>
                    <button class="btn-outline btn-sm" onclick="app.editManpower(${w.id})" title="Edit"><i class="fa-solid fa-pencil"></i></button>
                    <button class="btn-outline btn-sm" style="color:var(--danger);border-color:#FCA5A5;" onclick="app.archiveManpower(${w.id})"><i class="fa-solid fa-box-archive"></i></button>
                </td>
            </tr>`;
        });
    },

    editManpower: async function (id) {
        const allWorkers = await this.request('search_manpower', { query: String(id) });
        let worker = null;
        if (allWorkers && Array.isArray(allWorkers)) {
            worker = allWorkers.find(w => w.id == id);
        }
        if (!worker) {
            this.showToast('Could not load worker data.', 'error');
            return;
        }

        document.getElementById('man-id').value = worker.id;
        document.getElementById('man-name').value = worker.name || '';
        document.getElementById('man-skills').value = worker.skills || '';
        document.getElementById('man-skills-new').value = '';
        document.getElementById('man-skills-new').style.display = 'none';
        document.getElementById('man-pos').value = worker.position || '';
        document.getElementById('man-pos-new').value = '';
        document.getElementById('man-pos-new').style.display = 'none';
        document.getElementById('man-salary').value = parseFloat(worker.rate || worker.salary || 0);
        document.getElementById('man-project').value = worker.project_id || '';
        document.getElementById('man-project-text').value = worker.project_site_text || '';
        document.getElementById('man-foreman').value = worker.foreman || '';
        document.getElementById('man-contact').value = worker.contact_number || '';
        document.getElementById('man-address').value = worker.address || '';
        document.getElementById('man-status').value = worker.status || 'Active';

        const photoUrl = worker.photo || worker.photo_path || '';
        if (photoUrl) {
            this.setManpowerPhotoPreview(photoUrl);
        } else {
            this.clearManpowerPhotoPreview();
        }

        const cancelBtn = document.getElementById('man-cancel-btn');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
        const submitText = document.getElementById('man-submit-text');
        if (submitText) submitText.innerText = 'Update Record';

        document.getElementById('mod-users').scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    loadManpowerFolders: async function (page = 1) {
        const grid = document.getElementById('skill-folders-grid');
        if (!grid) return;

        this.manpowerFolderPage = page;
        grid.innerHTML = '';

        const oldPager = document.getElementById('manpower-folder-pagination');
        if (oldPager) oldPager.remove();

        const perPage = 10;
        const skills = await this.request('get_manpower_skills');

        const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
        });

        const escapeJs = (value) => String(value || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\n/g, '\\n').replace(/\r/g, '\\r');

        if (!Array.isArray(skills) || skills.length === 0) {
            grid.innerHTML = '<p style="color: var(--text-muted);">No records found.</p>';
        } else {
            const totalRecords = skills.length;
            const totalPages = Math.ceil(totalRecords / perPage);
            if (this.manpowerFolderPage < 1) this.manpowerFolderPage = 1;
            if (this.manpowerFolderPage > totalPages) this.manpowerFolderPage = totalPages;

            const startIndex = (this.manpowerFolderPage - 1) * perPage;
            const endIndex = Math.min(startIndex + perPage, totalRecords);
            const paginatedSkills = skills.slice(startIndex, endIndex);

            paginatedSkills.forEach(s => {
                const skillName = s.skill_name || 'Uncategorized';
                const workerCount = s.worker_count || 0;
                grid.innerHTML += `
                    <div class="stat-card" style="cursor:pointer;min-height:100px;" onclick="app.openSkillFolder('${escapeJs(skillName)}')">
                        <div class="stat-details">
                            <h3 style="font-size:1rem;color:var(--text-dark);text-transform:capitalize;font-weight:600;">${escapeHtml(skillName)}</h3>
                            <span style="color:var(--text-muted);font-size:0.85rem;">${workerCount} Record(s)</span>
                        </div>
                        <div class="stat-icon" style="background:var(--bg-main);color:var(--text-muted);">
                            <i class="fa-solid fa-folder"></i>
                        </div>
                    </div>
                `;
            });

            if (totalRecords > perPage) {
                const pager = document.createElement('div');
                pager.id = 'manpower-folder-pagination';
                pager.className = 'table-pagination';
                const showingFrom = startIndex + 1;
                const showingTo = endIndex;
                pager.innerHTML = `
                    <div class="pagination-info">Showing <b>${showingFrom}</b>-${showingTo} of <b>${totalRecords}</b> folders</div>
                    <div class="pagination-actions">
                        <button type="button" class="pagination-btn" onclick="app.changeManpowerFolderPage(${this.manpowerFolderPage - 1})" ${this.manpowerFolderPage === 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i> Previous</button>
                        <span class="pagination-page">Page ${this.manpowerFolderPage} of ${totalPages}</span>
                        <button type="button" class="pagination-btn" onclick="app.changeManpowerFolderPage(${this.manpowerFolderPage + 1})" ${this.manpowerFolderPage === totalPages ? 'disabled' : ''}>Next <i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                `;
                grid.parentNode.appendChild(pager);
            }
        }
    },

    changeManpowerFolderPage: function (page) {
        this.loadManpowerFolders(page);
        const grid = document.getElementById('skill-folders-grid');
        if (grid) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    uploadBioData: async function (workerId, inputElement) {
        if (!inputElement.files || inputElement.files.length === 0) return;
        const fd = new FormData();
        fd.append('worker_id', workerId);
        fd.append('photo', inputElement.files[0]);
        const res = await this.request('update_bio_data', fd, true);
        if (res.status === 'success') {
            this.showToast("Bio Data updated.");
            const title = document.getElementById('current-manpower-title');
            if (title) {
                const raw = title.innerText.trim();
                const currentView = document.getElementById('manpower-foreman-view').style.display !== 'none' ? 'foreman' : 'skill';
                if (currentView === 'foreman') this.loadManpowerForemanGroups();
                else this.backToManpowerView();
            } else {
                this.backToManpowerView();
            }
        } else {
            this.showToast(res.message || 'Unable to update.', 'error');
        }
    },

    openSkillFolder: async function (skillName) {
        const tableView = document.getElementById('manpower-table-view');
        const foremanView = document.getElementById('manpower-foreman-view');
        const skillsView = document.getElementById('manpower-skills-view');
        if (foremanView) foremanView.style.display = 'none';
        if (skillsView) skillsView.style.display = 'none';
        if (tableView) tableView.style.display = 'block';

        const title = document.getElementById('current-manpower-title');
        let titleHtml = `<span><i class="fa-solid fa-folder-open" style="margin-right:8px;"></i> ${escapeHTML(skillName)}</span>`;

        if (skillName !== 'Uncategorized') {
            let safeSkill = escapeHTML(skillName).replace(/'/g, "\\'");
            titleHtml += `<div style="display:flex;gap:8px;margin-left:auto;">
                <button class="btn-outline" style="height:30px;padding:0 12px;font-size:0.8rem;" onclick="app.editFolder('${safeSkill}')"><i class="fa-solid fa-pencil"></i> Rename</button>
                <button class="btn-danger" style="height:30px;padding:0 12px;font-size:0.8rem;" onclick="app.deleteFolder('${safeSkill}')"><i class="fa-solid fa-trash"></i> Delete</button>
            </div>`;
        }
        title.innerHTML = `<div style="display:flex;align-items:center;justify-content:space-between;width:100%;">${titleHtml}</div>`;

        const table = document.getElementById('table-users');
        table.innerHTML = `<thead><tr><th>Name</th><th>Project Assigned</th><th>Skills</th><th>Position</th><th>Rate</th><th>Contact</th><th>Foreman</th><th>Bio Data</th><th>Action</th></tr></thead><tbody></tbody>`;

        const workerList = await this.request('get_manpower_by_skill', { skill: skillName });
        const tbody = document.querySelector('#table-users tbody');
        tbody.innerHTML = '';
        if (!workerList || workerList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;">No records found.</td></tr>`;
            return;
        }

        workerList.forEach(w => {
            let photoUrl = w.photo || w.photo_path;
            let resumeButton = '';
            let avatarHtml = `<div style="width:36px;height:36px;border-radius:50%;background:#E5E7EB;display:flex;align-items:center;justify-content:center;color:#9CA3AF;"><i class="fa-solid fa-user"></i></div>`;

            if (photoUrl) {
                resumeButton = `<button class="btn-outline btn-sm" onclick="app.viewAttachedFile('${photoUrl}')"><i class="fa-solid fa-image"></i> View</button>`;
                avatarHtml = `<img src="${photoUrl}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--border);cursor:pointer;" onclick="app.viewAttachedFile('${photoUrl}')" onerror="this.src='https://ui-avatars.com/api/?name=${w.name}&background=FACC15&color=000'">`;
            } else {
                resumeButton = `<label class="btn-outline btn-sm" style="cursor:pointer;margin:0;padding:4px 8px;font-size:0.75rem;border-color:var(--primary);color:var(--text-dark);background:#FEFCE8;"><i class="fa-solid fa-upload"></i> Add Bio<input type="file" style="display:none;" accept="image/*" onchange="app.uploadBioData(${w.id}, this)"></label>`;
            }

            let nameDisplay = `<div style="display:flex;align-items:center;gap:10px;">${avatarHtml}<div><b style="color:var(--text-dark);">${escapeHTML(w.name)}</b><br><small style="color:var(--text-muted);">ID: ${w.id}</small></div></div>`;

            tbody.innerHTML += `<tr>
                <td>${nameDisplay}</td>
                <td>${escapeHTML(w.project_site_text || w.project_name) || '<small style="color:var(--text-muted);">Unassigned</small>'}</td>
                <td>${escapeHTML(w.skills) || 'Uncategorized'}</td>
                <td>${escapeHTML(w.position) || 'N/A'}</td>
                <td style="font-weight:600;">₱${parseFloat(w.rate || w.salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                <td>${escapeHTML(w.contact_number) || '<span style="color:#D1D5DB;">—</span>'}</td>
                <td>${escapeHTML(w.foreman) || '<span style="color:#D1D5DB;">—</span>'}</td>
                <td>${resumeButton}</td>
                <td>
                    <button class="btn-outline btn-sm" onclick="app.editManpower(${w.id})" title="Edit"><i class="fa-solid fa-pencil"></i></button>
                    <button class="btn-outline btn-sm" style="color:var(--danger);border-color:#FCA5A5;" onclick="app.archiveManpower(${w.id})"><i class="fa-solid fa-box-archive"></i></button>
                </td>
            </tr>`;
        });
    },

    openArchivedFolder: async function () {
        const tableView = document.getElementById('manpower-table-view');
        const foremanView = document.getElementById('manpower-foreman-view');
        const skillsView = document.getElementById('manpower-skills-view');
        if (foremanView) foremanView.style.display = 'none';
        if (skillsView) skillsView.style.display = 'none';
        if (tableView) tableView.style.display = 'block';

        const title = document.getElementById('current-manpower-title');
        title.innerHTML = `<i class="fa-solid fa-box-archive" style="color:var(--danger);margin-right:8px;"></i> Archived Manpower`;

        const table = document.getElementById('table-users');
        table.innerHTML = `<thead><tr><th>Name</th><th>Position</th><th>Folder (Skill)</th><th>Contact</th><th>Foreman</th><th>Date Archived</th><th>Action</th></tr></thead><tbody></tbody>`;

        const tbody = document.querySelector('#table-users tbody');
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Loading archived records...</td></tr>`;

        const response = await this.request('get_archived_manpower');
        const workerList = Array.isArray(response) ? response : [];

        tbody.innerHTML = '';
        if (workerList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No archived records found.</td></tr>`;
            return;
        }

        workerList.forEach(w => {
            tbody.innerHTML += `<tr>
                <td><b>${escapeHTML(w.name) || 'Unnamed'}</b></td>
                <td>${escapeHTML(w.position) || 'N/A'}</td>
                <td><span class="badge" style="background:#E5E7EB;color:#4B5563;">${escapeHTML(w.skills) || 'Uncategorized'}</span></td>
                <td>${escapeHTML(w.contact_number) || '<span style="color:#D1D5DB;">—</span>'}</td>
                <td>${escapeHTML(w.foreman) || '<span style="color:#D1D5DB;">—</span>'}</td>
                <td style="color:var(--danger);font-weight:700;"><i class="fa-regular fa-clock"></i> ${escapeHTML(w.archived_date) || 'N/A'}</td>
                <td><button class="btn-success-solid btn" style="height:26px;padding:0 10px;font-size:0.75rem;" onclick="app.restoreManpower(${w.id})"><i class="fa-solid fa-rotate-left"></i> Restore</button></td>
            </tr>`;
        });
    },

    backToManpowerView: function () {
        const tableView = document.getElementById('manpower-table-view');
        if (tableView) tableView.style.display = 'none';
        if (this._currentManpowerView === 'foreman') {
            document.getElementById('manpower-foreman-view').style.display = 'block';
            this.loadManpowerForemanGroups();
        } else {
            document.getElementById('manpower-skills-view').style.display = 'block';
            this.loadManpowerFolders();
        }
    },

    archiveManpower: async function (id) {
        if (confirm('Archive this worker? They will be removed from active sites.')) {
            await this.request('archive_manpower', { id: id });
            this.showToast('Worker archived.');
            this.backToManpowerView();
        }
    },
    restoreManpower: async function (id) {
        if (confirm('Restore this worker back to active status?')) {
            await this.request('restore_manpower', { id: id });
            this.showToast('Worker restored.');
            this.openArchivedFolder();
        }
    },

    // ==========================================
    // MODULE: AWARD COST
    // ==========================================

    handleAwardFileSelect: function (input) {
        const display = document.getElementById('awd-file-name');
        if (input.files && input.files.length > 0) {
            const url = URL.createObjectURL(input.files[0]);
            display.innerHTML = `<i class="fa-regular fa-file-lines"></i> ${escapeHTML(input.files[0].name)} <span style="margin-left:10px; color:var(--primary-hover); text-decoration:underline;" onclick="event.preventDefault(); app.viewAttachedFile('${url}')">Preview</span>`;
        } else {
            display.innerText = 'Click to upload or drag file here';
        }
    },

    loadAwardCostsModule: async function () {
        const select = document.getElementById('awd-project');
        if (select) {
            select.innerHTML = '<option value="">Select Project Site / NTP Reference *</option>';
            const projects = await this.request('get_projects');
            if (projects && Array.isArray(projects)) {
                projects.forEach(p => {
                    select.innerHTML += `<option value="${p.id}">${escapeHTML(p.name)} - ${escapeHTML(p.location)}</option>`;
                });
            }
        }
        this.loadAwardCosts();
    },

    fillAwardProjectFields: async function (projectId) {
        if (!projectId) {
            document.getElementById('awd-block').value = '';
            document.getElementById('awd-lot').value = '';
            document.getElementById('awd-location').value = '';
            document.getElementById('awd-start').value = '';
            document.getElementById('awd-completion').value = '';
            document.getElementById('awd-work-desc').value = '';
            document.getElementById('awd-project-desc').value = '';
            document.getElementById('awd-total-amount').value = '';
            return;
        }
        const projects = window.allProjectsData || await this.request('get_projects');
        const proj = Array.isArray(projects) ? projects.find(p => p.id == projectId) : null;
        if (proj) {
            document.getElementById('awd-block').value = proj.block_no || '';
            document.getElementById('awd-lot').value = proj.lot_no || '';
            document.getElementById('awd-location').value = proj.location || '';
            document.getElementById('awd-start').value = proj.start_date || '';
            document.getElementById('awd-completion').value = proj.completion_date || '';
            document.getElementById('awd-work-desc').value = proj.work_description || '';
            document.getElementById('awd-project-desc').value = proj.project_description || '';
            document.getElementById('awd-total-amount').value = proj.total_amount ? parseFloat(proj.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) : '';
        }
    },

    addAwardCost: async function () {
        const id = document.getElementById('awd-id').value;
        const project_id = document.getElementById('awd-project').value;
        const service_agreement_code = document.getElementById('awd-service-code').value;
        const block_no = document.getElementById('awd-block').value;
        const lot_no = document.getElementById('awd-lot').value;
        const location = document.getElementById('awd-location').value;
        const item = document.getElementById('awd-item').value;
        const unit = document.getElementById('awd-unit').value;
        const start_date = document.getElementById('awd-start').value;
        const completion_date = document.getElementById('awd-completion').value;
        const work_description = document.getElementById('awd-work-desc').value;
        const project_description = document.getElementById('awd-project-desc').value;
        const total_amount = document.getElementById('awd-total-amount').value.replace(/,/g, '');
        const fileInput = document.getElementById('awd-attachment');

        if (!project_id || !service_agreement_code || !item || !unit || !start_date || !completion_date || !work_description || !project_description || !total_amount) {
            this.showToast('Please fill in all required fields!', 'error');
            return;
        }
        if (parseFloat(total_amount) < 0) {
            this.showToast('Total Amount cannot be negative.', 'error');
            return;
        }
        if (completion_date < start_date) {
            this.showToast('Completion Date cannot be earlier than Start Date.', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('project_id', project_id);
        fd.append('service_agreement_code', service_agreement_code);
        fd.append('block_no', block_no);
        fd.append('lot_no', lot_no);
        fd.append('location', location);
        fd.append('item', item);
        fd.append('unit', unit);
        fd.append('start_date', start_date);
        fd.append('completion_date', completion_date);
        fd.append('work_description', work_description);
        fd.append('project_description', project_description);
        fd.append('total_amount', total_amount);

        if (fileInput.files && fileInput.files.length > 0) {
            fd.append('attachment', fileInput.files[0]);
        }

        let res;
        if (id) {
            fd.append('id', id);
            res = await this.request('edit_award_cost', fd, true);
        } else {
            res = await this.request('add_award_cost', fd, true);
        }

        if (res.status === 'success') {
            this.clearAwardCostForm();
            this.loadAwardCosts();
            window.globalSearchData = null;
            this.showToast(id ? 'Award Cost updated.' : 'Award Cost added.');
        } else {
            this.showToast(res.message, 'error');
        }
    },

    clearAwardCostForm: function () {
        document.getElementById('awd-id').value = '';
        document.getElementById('awd-project').value = '';
        document.getElementById('awd-block').value = '';
        document.getElementById('awd-lot').value = '';
        document.getElementById('awd-location').value = '';
        document.getElementById('awd-service-code').value = '';
        document.getElementById('awd-item').value = '';
        document.getElementById('awd-unit').value = '';
        document.getElementById('awd-start').value = '';
        document.getElementById('awd-completion').value = '';
        document.getElementById('awd-work-desc').value = '';
        document.getElementById('awd-project-desc').value = '';
        document.getElementById('awd-total-amount').value = '';
        document.getElementById('awd-attachment').value = '';
        document.getElementById('awd-file-name').innerText = 'Click to upload or drag file here';
        const cancelBtn = document.getElementById('awd-cancel-btn');
        if (cancelBtn) cancelBtn.style.display = 'none';
        const submitText = document.getElementById('awd-submit-text');
        if (submitText) submitText.innerText = 'Add Record';
    },

    cancelEditAwardCost: function () {
        this.clearAwardCostForm();
    },

    loadAwardCosts: async function (searchQuery) {
        const tbody = document.querySelector('#table-award-costs tbody');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="12" class="awd-empty-state"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>`;

        let data;
        if (searchQuery && searchQuery.trim() !== '') {
            data = await this.request('search_award_costs', { query: searchQuery });
        } else {
            data = await this.request('get_award_costs');
        }

        tbody.innerHTML = '';
        if (!data || !Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="12" class="awd-empty-state"><i class="fa-solid fa-clipboard-list"></i><p>No award cost records found.</p></td></tr>`;
            return;
        }

        data.forEach(d => {
            const fmtAmt = parseFloat(d.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
            const path = d.attachment_path || '';
            const attachmentHtml = path
                ? `<span class="awd-attachment-link" onclick="app.viewAttachedFile('${escapeHTML(path.replace(/'/g, "\\'"))}')"><i class="fa-solid fa-paperclip"></i> View File</span>`
                : `<span class="awd-no-file">No file</span>`;
            const projectName = d.project_name || 'N/A';

            tbody.innerHTML += `<tr>
                <td><b>${escapeHTML(d.service_agreement_code)}</b></td>
                <td><span class="awd-project-badge">${escapeHTML(projectName)}</span></td>
                <td class="awd-col-block">${escapeHTML(d.block_no) || '-'}</td>
                <td class="awd-col-lot">${escapeHTML(d.lot_no) || '-'}</td>
                <td><span class="awd-truncate" title="${escapeHTML(d.location || '')}">${escapeHTML(d.location) || '-'}</span></td>
                <td>${escapeHTML(d.item)}</td>
                <td class="awd-col-unit">${escapeHTML(d.unit)}</td>
                <td class="awd-col-date">${escapeHTML(d.start_date)}</td>
                <td class="awd-col-date">${escapeHTML(d.completion_date)}</td>
                <td class="awd-col-amount"><span class="awd-amount-display">₱${fmtAmt}</span></td>
                <td class="awd-col-attachment">${attachmentHtml}</td>
                <td class="awd-col-action">
                    <button class="btn-outline" style="height: 28px; padding: 0 10px; font-size: 0.75rem;" onclick="app.editAwardCost(${d.id})" title="Edit"><i class="fa-solid fa-pencil"></i></button>
                    <button class="btn-danger" style="height: 28px; padding: 0 10px; border-radius: 4px;" onclick="app.deleteAwardCost(${d.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        });
    },

    editAwardCost: async function (id) {
        const res = await this.request('get_award_cost', { id });
        if (!res || res.status === 'error' || !res.data) {
            this.showToast('Could not load award cost data.', 'error');
            return;
        }

        const d = res.data;
        document.getElementById('awd-id').value = d.id;
        document.getElementById('awd-project').value = d.project_id || '';
        document.getElementById('awd-block').value = d.block_no || '';
        document.getElementById('awd-lot').value = d.lot_no || '';
        document.getElementById('awd-location').value = d.location || '';
        document.getElementById('awd-service-code').value = d.service_agreement_code || '';
        document.getElementById('awd-item').value = d.item || '';
        document.getElementById('awd-unit').value = d.unit || '';
        document.getElementById('awd-start').value = d.start_date || '';
        document.getElementById('awd-completion').value = d.completion_date || '';
        document.getElementById('awd-work-desc').value = d.work_description || '';
        document.getElementById('awd-project-desc').value = d.project_description || '';
        document.getElementById('awd-total-amount').value = parseFloat(d.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

        if (d.attachment_path) {
            const path = d.attachment_path.replace(/'/g, "\\'");
            document.getElementById('awd-file-name').innerHTML = `<i class="fa-regular fa-file-lines"></i> ${escapeHTML(d.attachment_path)} <span style="margin-left:10px; color:var(--primary-hover); text-decoration:underline;" onclick="event.preventDefault(); app.viewAttachedFile('${escapeHTML(path)}')">View</span>`;
        }

        const cancelBtn = document.getElementById('awd-cancel-btn');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
        const submitText = document.getElementById('awd-submit-text');
        if (submitText) submitText.innerText = 'Update Record';

        document.getElementById('mod-global_ntp').scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    deleteAwardCost: async function (id) { if (confirm("Delete this Award Cost record?")) { await this.request('delete_award_cost', { id }); this.loadAwardCosts(); window.globalSearchData = null; } },

    // ==========================================
    // MODULE: BILL OF MATERIALS (BOM)
    // ==========================================
    loadBOMModule: async function () {
        const select = document.getElementById('bom-project');
        if (select) {
            select.innerHTML = '<option value="">Select Project Site / NTP</option>';
            const projects = await this.request('get_projects');
            if (projects && Array.isArray(projects)) {
                projects.forEach(p => {
                    select.innerHTML += `<option value="${p.id}">${escapeHTML(p.name)} - ${escapeHTML(p.location || '')}</option>`;
                });
            }
        }
        const awardSelect = document.getElementById('bom-award-cost');
        if (awardSelect) awardSelect.innerHTML = '<option value="">Select Award Cost (Optional)</option>';
        this.clearBOMForm();
        this.loadBOMItems();
    },

    loadBOMAwardCosts: async function (projectId) {
        const awardSelect = document.getElementById('bom-award-cost');
        if (!awardSelect) return;
        awardSelect.innerHTML = '<option value="">Select Award Cost (Optional)</option>';
        if (!projectId) return;
        const data = await this.request('get_award_costs_for_bom', { project_id: projectId });
        if (data && Array.isArray(data)) {
            data.forEach(a => {
                awardSelect.innerHTML += `<option value="${a.id}">${escapeHTML(a.service_agreement_code)}${a.item ? ' - ' + escapeHTML(a.item) : ''}</option>`;
            });
        }
    },

    calcBOMTotal: function () {
        const qty = parseFloat(document.getElementById('bom-quantity').value.replace(/,/g, '')) || 0;
        const cost = parseFloat(document.getElementById('bom-unit-cost').value.replace(/,/g, '')) || 0;
        const total = qty * cost;
        document.getElementById('bom-total-cost').value = total > 0 ? total.toLocaleString('en-US', { minimumFractionDigits: 2 }) : '';
    },

    clearBOMForm: function () {
        document.getElementById('bom-id').value = '';
        document.getElementById('bom-project').value = '';
        document.getElementById('bom-award-cost').innerHTML = '<option value="">Select Award Cost (Optional)</option>';
        document.getElementById('bom-award-cost-text').value = '';
        document.getElementById('bom-material-name').value = '';
        document.getElementById('bom-description').value = '';
        document.getElementById('bom-quantity').value = '';
        document.getElementById('bom-unit').value = '';
        document.getElementById('bom-unit-cost').value = '';
        document.getElementById('bom-total-cost').value = '';
        document.getElementById('bom-supplier').value = '';
        document.getElementById('bom-remarks').value = '';
        const cancelBtn = document.getElementById('bom-cancel-btn');
        if (cancelBtn) cancelBtn.style.display = 'none';
        const submitText = document.getElementById('bom-submit-text');
        if (submitText) submitText.innerText = 'Add Record';
    },

    addBOMItem: async function () {
        const id = document.getElementById('bom-id').value;
        const project_id = document.getElementById('bom-project').value;
        const award_cost_id = document.getElementById('bom-award-cost').value;
        const award_cost_text = document.getElementById('bom-award-cost-text').value.trim();
        const material_name = document.getElementById('bom-material-name').value.trim();
        const description = document.getElementById('bom-description').value.trim();
        const quantity = document.getElementById('bom-quantity').value;
        const unit = document.getElementById('bom-unit').value.trim();
        const unit_cost = document.getElementById('bom-unit-cost').value.replace(/,/g, '');
        const supplier_name = document.getElementById('bom-supplier').value.trim();
        const remarks = document.getElementById('bom-remarks').value.trim();

        if (!project_id) { this.showToast('Please select a Project Site / NTP.', 'error'); return; }
        if (!material_name) { this.showToast('Material Name is required.', 'error'); return; }
        if (!quantity || parseFloat(quantity) <= 0) { this.showToast('Quantity must be greater than 0.', 'error'); return; }
        if (!unit) { this.showToast('Unit is required.', 'error'); return; }
        if (parseFloat(unit_cost) < 0) { this.showToast('Unit Cost cannot be negative.', 'error'); return; }

        const action = id ? 'edit_bom_item' : 'add_bom_item';
        const payload = {
            id, project_id, award_cost_id, award_cost_text, material_name, description,
            quantity, unit, unit_cost, supplier_name, remarks
        };

        const res = await this.request(action, payload);
        if (res && res.status === 'success') {
            this.showToast(id ? 'BOM item updated.' : 'BOM item added.');
            this.clearBOMForm();
            this.loadBOMItems();
        } else {
            this.showToast(res ? res.message : 'Operation failed.', 'error');
        }
    },

    loadBOMItems: async function (searchQuery, projectId) {
        const tbody = document.querySelector('#table-bom tbody');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="10" class="bom-empty-state"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>`;

        let data;
        if (searchQuery && searchQuery.trim() !== '') {
            data = await this.request('search_bom_items', { query: searchQuery });
        } else {
            const payload = {};
            if (projectId) payload.project_id = projectId;
            data = await this.request('get_bom_items', payload);
        }

        tbody.innerHTML = '';
        if (!data || !Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="10" class="bom-empty-state"><i class="fa-solid fa-receipt"></i><p>No BOM records found.</p></td></tr>`;
            return;
        }

        data.forEach(d => {
            const qty = parseFloat(d.quantity || 0);
            const unitCost = parseFloat(d.unit_cost || 0);
            const totalCost = parseFloat(d.total_cost || 0);
            const projectName = d.project_name || 'N/A';
            const awardCostRef = d.award_cost_text || d.service_agreement_code || '';
            const supplier = d.supplier_name || '';
            const remarks = d.remarks || '';

            tbody.innerHTML += `<tr>
                <td><span class="bom-project-badge">${escapeHTML(projectName)}</span></td>
                <td>${awardCostRef ? escapeHTML(awardCostRef) : '<span class="bom-no-data">—</span>'}</td>
                <td><b>${escapeHTML(d.material_name)}</b>${d.description ? `<br><span class="bom-desc">${escapeHTML(d.description)}</span>` : ''}</td>
                <td class="bom-col-qty">${qty.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                <td class="bom-col-unit">${escapeHTML(d.unit)}</td>
                <td class="bom-col-cost"><span class="bom-amount">₱${unitCost.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span></td>
                <td class="bom-col-cost"><span class="bom-amount">₱${totalCost.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span></td>
                <td>${supplier ? escapeHTML(supplier) : '<span class="bom-no-data">—</span>'}</td>
                <td>${remarks ? escapeHTML(remarks) : '<span class="bom-no-data">—</span>'}</td>
                <td class="bom-col-action">
                    <button class="btn-outline" style="height: 28px; padding: 0 10px; font-size: 0.75rem;" onclick="app.editBOMItem(${d.id})" title="Edit"><i class="fa-solid fa-pencil"></i></button>
                    <button class="btn-danger" style="height: 28px; padding: 0 10px; border-radius: 4px;" onclick="app.deleteBOMItem(${d.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        });
    },

    editBOMItem: async function (id) {
        const res = await this.request('get_bom_item', { id });
        if (!res || res.status === 'error' || !res.data) {
            this.showToast('Could not load BOM item data.', 'error');
            return;
        }

        const d = res.data;
        document.getElementById('bom-id').value = d.id;
        document.getElementById('bom-project').value = d.project_id || '';

        // Load award costs for this project
        await this.loadBOMAwardCosts(d.project_id);
        document.getElementById('bom-award-cost').value = d.award_cost_id || '';
        document.getElementById('bom-award-cost-text').value = d.award_cost_text || '';

        document.getElementById('bom-material-name').value = d.material_name || '';
        document.getElementById('bom-description').value = d.description || '';
        document.getElementById('bom-quantity').value = parseFloat(d.quantity || 0);
        document.getElementById('bom-unit').value = d.unit || '';
        document.getElementById('bom-unit-cost').value = parseFloat(d.unit_cost || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
        this.calcBOMTotal();
        document.getElementById('bom-supplier').value = d.supplier_name || '';
        document.getElementById('bom-remarks').value = d.remarks || '';

        const cancelBtn = document.getElementById('bom-cancel-btn');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
        const submitText = document.getElementById('bom-submit-text');
        if (submitText) submitText.innerText = 'Update Record';

        document.getElementById('mod-bill_of_materials').scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    cancelEditBOM: function () {
        this.clearBOMForm();
    },

    deleteBOMItem: async function (id) {
        if (confirm("Delete this BOM item?")) {
            const res = await this.request('delete_bom_item', { id });
            if (res && res.status === 'success') {
                this.showToast('BOM item deleted.');
                this.loadBOMItems();
            } else {
                this.showToast(res ? res.message : 'Delete failed.', 'error');
            }
        }
    },

    // ==========================================
    // MODULE: PAYROLL (SMART LEDGER SYNC)
    // ==========================================
    formatCurrencyInput: function (input) { let val = input.value.replace(/[^0-9.]/g, ''); let parts = val.split('.'); parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ","); input.value = parts.join('.'); },
    populatePayrollDatalists: async function () {
        const users = await this.request('get_active_manpower'); const datalist = document.getElementById('worker-names-list'); if (!datalist) return; datalist.innerHTML = ''; if (users && Array.isArray(users)) { users.forEach(u => { datalist.innerHTML += `<option value="${escapeHTML(u.name)}">`; }); }
        const jobs = await this.request('get_award_costs'); const jobList = document.getElementById('pay-job-list'); if (jobList) { jobList.innerHTML = ''; if (jobs && Array.isArray(jobs)) { jobs.forEach(j => { const jobName = j.work_description || j.scope_of_work || ''; if (jobName) jobList.innerHTML += `<option value="${escapeHTML(jobName)}">`; }); } }
    },
    clearPayrollForm: function () { ['pay-date', 'pay-name', 'pay-job', 'pay-award', 'pay-advance'].forEach(id => { if (document.getElementById(id)) document.getElementById(id).value = ''; }); },

    addManualPayroll: async function () {
        const date = document.getElementById('pay-date').value; const name = document.getElementById('pay-name').value; const job = document.getElementById('pay-job').value;
        let rawAward = document.getElementById('pay-award').value.replace(/,/g, ''); let rawAdvance = document.getElementById('pay-advance').value.replace(/,/g, ''); const award = parseFloat(rawAward || 0); const advance = parseFloat(rawAdvance || 0);

        if (!name || !job) { this.showToast('Name and Job Description required.', 'error'); return; }

        const res = await this.request('add_payroll', { date: date || new Date().toISOString().split('T')[0], name: name, job_desc: job, award: award, advance: advance });
        if (res && res.status === 'success') { this.clearPayrollForm(); this.renderPayrollTab(); this.loadDashboard(); this.showToast('Transaction logged!'); } else { this.showToast(res ? res.message : 'Unknown database error.', 'error'); }
    },

    openEditPayrollModal: function (id, award, advance) { document.getElementById('edit-pay-id').value = id; document.getElementById('edit-pay-award').value = award ? parseFloat(award).toLocaleString('en-US', { minimumFractionDigits: 2 }) : ''; document.getElementById('edit-pay-advance').value = advance ? parseFloat(advance).toLocaleString('en-US', { minimumFractionDigits: 2 }) : ''; this.openModal('modal-edit-payroll'); },
    saveEditedPayroll: async function () {
        const id = document.getElementById('edit-pay-id').value; const rawAward = document.getElementById('edit-pay-award').value.replace(/,/g, ''); const rawAdvance = document.getElementById('edit-pay-advance').value.replace(/,/g, ''); const award = parseFloat(rawAward || 0); const advance = parseFloat(rawAdvance || 0);

        if (!isNaN(award) && !isNaN(advance)) { await this.request('edit_payroll_entry', { id: id, award_cost: award, cash_advance: advance }); this.closeModal('modal-edit-payroll'); this.renderPayrollTab(); this.loadDashboard(); this.showToast('Record updated. Balances recalculated.'); } else { this.showToast('Invalid numbers entered.', 'error'); }
    },

    deletePayrollEntry: async function (id) {
        if (confirm("Are you sure you want to delete this payroll record? It will automatically recalculate the balances.")) { await this.request('delete_payroll_entry', { id: id }); this.renderPayrollTab(); this.loadDashboard(); this.showToast('Record deleted. Balances updated.'); }
    },

    togglePayrollRow: function (workerNameSafe, btn) {
        const row = document.getElementById(`nested-${workerNameSafe}`); const isExpanding = !row.classList.contains('active');
        document.querySelectorAll('.nested-row').forEach(r => r.classList.remove('active')); document.querySelectorAll('.btn-toggle-details').forEach(b => b.innerHTML = '<i class="fa-solid fa-eye"></i> View Details');
        if (isExpanding) { row.classList.add('active'); if (btn) btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide Details'; }
    },

    renderPayrollTab: async function () {
        const tbody = document.getElementById('payroll-content'); if (!tbody) return; tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Retrieving live payroll data...</td></tr>`;
        const completedTasks = await this.request('get_all_completed_tasks'); const manualPayrolls = await this.request('get_payroll'); let aggregatedData = {};

        if (completedTasks && Array.isArray(completedTasks)) { completedTasks.forEach(task => { const worker = task.assigned_worker; if (!aggregatedData[worker]) aggregatedData[worker] = { job: task.category, txns: [] }; aggregatedData[worker].txns.push({ id: task.id, source: 'auto', project: task.project_name, blkLot: task.project_location || '-', award: parseFloat(task.award_cost) || 0, date: task.completion_date || 'N/A', sale: 0 }); }); }
        if (manualPayrolls && Array.isArray(manualPayrolls)) { manualPayrolls.forEach(entry => { if (!aggregatedData[entry.name]) aggregatedData[entry.name] = { job: entry.job_description, txns: [] }; aggregatedData[entry.name].job = entry.job_description; aggregatedData[entry.name].txns.push({ id: entry.id, source: 'manual', project: "Cash Advance Log", blkLot: entry.job_description, award: parseFloat(entry.award_cost) || 0, date: entry.pay_date, sale: parseFloat(entry.cash_advance) || 0 }); }); }

        Object.keys(aggregatedData).forEach(workerName => {
            let data = aggregatedData[workerName]; data.txns.sort((a, b) => new Date(a.date) - new Date(b.date));
            let runningAward = 0; let runningAdvance = 0;
            data.txns.forEach(txn => { runningAward += txn.award; runningAdvance += txn.sale; txn.overall = runningAdvance; txn.balance = runningAward - runningAdvance; });
            data.totalAward = runningAward; data.totalSale = runningAdvance; data.latestBalance = runningAward - runningAdvance;
        });

        tbody.innerHTML = ''; let grandTotal = 0; let workerCount = 0;

        if (Object.keys(aggregatedData).length === 0) { tbody.innerHTML = `<tr><td colspan="4" class="empty-state-wrapper"><i class="fa-solid fa-file-invoice-dollar"></i><p>No payroll data found. Complete tasks in workspace to sync.</p></td></tr>`; document.getElementById('payroll-total').innerText = '₱0.00'; document.getElementById('payroll-count').innerText = '0 Worker(s)'; return; }

        Object.keys(aggregatedData).forEach(workerName => {
            workerCount++; const data = aggregatedData[workerName]; grandTotal += data.latestBalance; let safeId = workerName.replace(/[^a-zA-Z0-9]/g, '-');
            tbody.innerHTML += `<tr><td><b style="color:var(--text-dark);">${escapeHTML(workerName)}</b></td><td><span class="badge ongoing">${escapeHTML(data.job)}</span></td><td style="font-weight: 800; color:var(--text-dark);">₱${data.totalAward.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td style="text-align: center;"><button class="btn-outline btn-toggle-details" id="btn-toggle-${safeId}" style="height: 26px; padding: 0 8px; font-size: 0.75rem;" onclick="app.togglePayrollRow('${safeId}', this)"><i class="fa-solid fa-eye"></i> View Details</button></td></tr>`;

            let nestedRows = '';
            data.txns.forEach(b => {
                let awardText = b.award > 0 ? `₱${b.award.toLocaleString('en-US', { minimumFractionDigits: 2 })}` : `<span style="color:#D1D5DB;">-</span>`; let saleText = b.sale > 0 ? `-₱${b.sale.toLocaleString('en-US', { minimumFractionDigits: 2 })}` : `<span style="color:var(--danger);">-₱0.00</span>`;
                let actionHtml = b.source === 'manual' ? `<button class="btn-outline" style="padding: 2px 6px; font-size: 0.7rem; margin-right: 2px;" onclick="app.openEditPayrollModal(${b.id}, ${b.award}, ${b.sale})"><i class="fa-solid fa-pencil"></i></button><button class="btn-danger" style="padding: 2px 6px; font-size: 0.7rem;" onclick="app.deletePayrollEntry(${b.id})"><i class="fa-solid fa-trash"></i></button>` : `<small style="color:var(--text-muted); font-size: 0.7rem;">Auto-Sync</small>`;
                nestedRows += `<tr><td><b>${b.project}</b></td><td>${b.blkLot}</td><td style="font-weight:600; color:var(--text-dark);">${awardText}</td><td style="color:var(--text-muted);">${b.date}</td><td style="color:var(--danger);">${saleText}</td><td>₱${b.overall.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td style="font-weight:800; color:var(--success);">₱${b.balance.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td>${actionHtml}</td></tr>`;
            });

            nestedRows += `<tr style="background-color: #FEFCE8; border-top: 2px solid var(--primary);"><td colspan="2" style="text-align: right; font-weight: 800; color: var(--text-dark);">SUMMARY TOTAL:</td><td style="font-weight: 800; color: var(--text-dark);">₱${data.totalAward.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td></td><td style="font-weight: 800; color: var(--danger);">-₱${data.totalSale.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td></td><td style="font-weight: 900; color: var(--success); font-size: 0.9rem;">₱${data.latestBalance.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td></td></tr>`;
            tbody.innerHTML += `<tr class="nested-row" id="nested-${safeId}"><td colspan="4" style="padding: 0;"><div class="nested-table-container"><h4 style="margin-bottom: 8px; font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">Award Cost Breakdown for ${workerName}</h4><table class="nested-table nested-header-red"><thead><tr><th>PROJECT</th><th>BLK & LOT</th><th>AWARD COST (₱)</th><th>DATE</th><th>SALE / CASH ADV. (₱)</th><th>OVERALL (₱)</th><th>BALANCE (₱)</th><th style="width: 70px;">ACTION</th></tr></thead><tbody>${nestedRows}</tbody></table></div></td></tr>`;
        });

        document.getElementById('payroll-total').innerText = `₱${grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}`; document.getElementById('payroll-count').innerText = `${workerCount} Worker(s)`;
    },

    resetDatabasePayroll: async function () {
        if (confirm("This will close the cycle and archive ONLY the workers who have fully consumed their Award Cost (Balance = 0). Continue?")) {
            const res = await this.request('archive_and_reset_payroll');
            this.renderPayrollTab();
            if (res && res.archived > 0) { this.showToast(`Cycle Closed. ${res.archived} completed records moved to History.`); }
            else { this.showToast("No fully completed records (Balance = 0) to archive.", "warning"); }
        }
    },

    toggleHistRow: function (idSafe, btn) {
        const row = document.getElementById(`nested-${idSafe}`); const isExpanding = !row.classList.contains('active');
        document.querySelectorAll('.nested-hist-row').forEach(r => r.classList.remove('active')); document.querySelectorAll('.btn-toggle-hist').forEach(b => b.innerHTML = '<i class="fa-solid fa-eye"></i> View Details');
        if (isExpanding) { row.classList.add('active'); if (btn) btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide Details'; }
    },

    viewPayrollHistory: async function () {
        document.getElementById('payroll-active-view').style.display = 'none'; document.getElementById('payroll-history-view').style.display = 'block';
        const tbody = document.getElementById('payroll-history-content'); if (tbody) tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Retrieving archive...</td></tr>`;

        const history = await this.request('get_payroll_history');
        if (history.length === 0) { tbody.innerHTML = `<tr><td colspan="4" class="empty-state-wrapper"><i class="fa-solid fa-folder-open"></i><p>No history found.</p></td></tr>`; return; }

        let groupedHistory = {};
        history.forEach(h => {
            let workerName = h.name;
            if (!groupedHistory[workerName]) { groupedHistory[workerName] = { records: [], totalPayout: 0, cycles: new Set() }; }
            groupedHistory[workerName].records.push(h); groupedHistory[workerName].totalPayout += parseFloat(h.balance || h.net_pay || 0); groupedHistory[workerName].cycles.add(h.cycle_id);
        });

        tbody.innerHTML = '';
        Object.keys(groupedHistory).forEach((workerName, idx) => {
            let data = groupedHistory[workerName]; let safeId = 'hist-' + idx;
            tbody.innerHTML += `<tr><td><b style="color:var(--text-dark);"><i class="fa-solid fa-folder" style="color:var(--primary); margin-right:8px;"></i> ${escapeHTML(workerName)}</b></td><td><span class="badge ongoing">${data.cycles.size} Cycle(s)</span></td><td style="font-weight: 800; color:var(--text-dark);">₱${data.totalPayout.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td style="text-align: center;"><button class="btn-outline btn-toggle-hist" id="btn-toggle-${safeId}" style="height: 26px; padding: 0 8px; font-size: 0.75rem;" onclick="app.toggleHistRow('${safeId}', this)"><i class="fa-solid fa-eye"></i> View Details</button></td></tr>`;
            let nestedRows = '';
            data.records.forEach(r => { nestedRows += `<tr><td><small style="color:var(--text-muted); font-weight:700;">${escapeHTML(r.cycle_id)}</small></td><td>${escapeHTML(r.pay_date)}</td><td>${escapeHTML(r.job_description) || '-'}</td><td style="font-weight:600; color:var(--text-dark);">₱${parseFloat(r.award_cost || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td style="color:var(--danger);">-₱${parseFloat(r.cash_advance || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td><td style="font-weight:800; color:var(--success);">₱${parseFloat(r.balance || r.net_pay || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>`; });
            tbody.innerHTML += `<tr class="nested-row nested-hist-row" id="nested-${safeId}"><td colspan="4" style="padding: 0;"><div class="nested-table-container"><h4 style="margin-bottom: 8px; font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">Archive Breakdown for ${workerName}</h4><table class="nested-table nested-header-red"><thead><tr><th>CYCLE ID</th><th>DATE PAID</th><th>JOB DESCRIPTION</th><th>AWARD COST (₱)</th><th>ADVANCE (₱)</th><th>BALANCE / PAYOUT (₱)</th></tr></thead><tbody>${nestedRows}</tbody></table></div></td></tr>`;
        });
    },

    backToActivePayroll: function () { if (document.getElementById('payroll-history-view')) document.getElementById('payroll-history-view').style.display = 'none'; if (document.getElementById('payroll-active-view')) document.getElementById('payroll-active-view').style.display = 'block'; },

    // --- PAYROLL ENTRIES (Manpower / Subcon) ---
    _payrollEntryData: [],

    loadPayrollEntries: async function () {
        document.getElementById('pe-id').value = '';
        document.getElementById('pe-submit-text').textContent = 'Add Payroll Entry';
        document.getElementById('pe-cancel-btn').style.display = 'none';
        document.getElementById('pe-project').value = '';
        document.getElementById('pe-type').value = 'Manpower';
        document.getElementById('pe-worker').value = '';
        document.getElementById('pe-foreman').value = '';
        document.getElementById('pe-payee-name').value = '';
        document.getElementById('pe-position').value = '';
        document.getElementById('pe-skill').value = '';
        document.getElementById('pe-period-start').value = '';
        document.getElementById('pe-period-end').value = '';
        document.getElementById('pe-amount').value = '';
        document.getElementById('pe-payment-method').value = '';
        document.getElementById('pe-status').value = 'Pending';
        document.getElementById('pe-remarks').value = '';
        document.getElementById('pe-subcon-company').value = '';
        document.getElementById('pe-subcon-scope').value = '';
        document.getElementById('pe-subcon-ref').value = '';
        this.togglePayrollType();

        await this.populatePayrollEntryProjects();
        await this.populatePayrollEntryWorkers();
        await this.loadPayrollEntryRecords();
        await this.loadPayrollEntrySummary();
    },

    togglePayrollType: function () {
        const type = document.getElementById('pe-type').value;
        const isSubcon = type === 'Subcon';
        document.querySelectorAll('.pe-subcon-field').forEach(el => {
            el.style.display = isSubcon ? '' : 'none';
        });
    },

    populatePayrollEntryProjects: async function () {
        const data = await this.request('get_projects');
        const sel = document.getElementById('pe-project');
        sel.innerHTML = '<option value="">Select Project</option>';
        const list = (data && data.status === 'success' && data.data) ? data.data : (Array.isArray(data) ? data : []);
        list.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.name + (p.block_no ? ` (Blk ${p.block_no}${p.lot_no ? ` Lot ${p.lot_no}` : ''})` : '');
            sel.appendChild(opt);
        });
    },

    _payrollEntryWorkers: [],

    populatePayrollEntryWorkers: async function () {
        const data = await this.request('get_active_manpower');
        const sel = document.getElementById('pe-worker');
        sel.innerHTML = '<option value="">Select Worker</option>';
        this._payrollEntryWorkers = (data && data.status === 'success' && data.data) ? data.data : (Array.isArray(data) ? data : []);
        this._payrollEntryWorkers.forEach(w => {
            const opt = document.createElement('option');
            opt.value = w.id;
            opt.textContent = w.name + (w.skills ? ` (${w.skills})` : '');
            sel.appendChild(opt);
        });
    },

    onWorkerSelect: function () {
        const sel = document.getElementById('pe-worker');
        const selected = sel.options[sel.selectedIndex];
        if (selected && selected.value) {
            const worker = this._payrollEntryWorkers.find(w => w.id == selected.value);
            if (worker) {
                document.getElementById('pe-payee-name').value = worker.name || '';
                document.getElementById('pe-position').value = worker.position || '';
                document.getElementById('pe-skill').value = worker.skills || '';
                document.getElementById('pe-foreman').value = worker.foreman || '';
            }
        }
    },

    loadPayrollEntryRecords: async function (query) {
        const q = query || document.getElementById('search-payroll-entries')?.value || '';
        const tbody = document.querySelector('#table-payroll-entries tbody');
        if (!tbody) return;
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;padding:20px;"><i class="fa-solid fa-spinner fa-spin"></i> Retrieving records...</td></tr>`;

        let data;
        if (q.trim()) {
            data = await this.request('search_payroll_entry_records', { query: q });
        } else {
            data = await this.request('get_payroll_entry_records');
        }
        this._payrollEntryData = (data && data.status === 'success' && data.data) ? data.data : (Array.isArray(data) ? data : []);
        this.renderPayrollEntryTable();
        this.loadPayrollEntrySummary();
    },

    renderPayrollEntryTable: function () {
        const tbody = document.querySelector('#table-payroll-entries tbody');
        if (!tbody) return;
        if (!this._payrollEntryData || !this._payrollEntryData.length) {
            tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;padding:32px;color:var(--text-muted);">No payroll entries found.</td></tr>`;
            return;
        }
        tbody.innerHTML = this._payrollEntryData.map(r => {
            const statusClass = `pe-status-${(r.payroll_status || 'Pending').toLowerCase()}`;
            const pStart = r.period_start || '';
            const pEnd = r.period_end || '';
            const periodStr = pStart ? (pStart + (pEnd ? ` to ${pEnd}` : '')) : '';
            return `<tr>
                <td><span class="pe-badge ${r.payroll_type === 'Subcon' ? 'pe-badge-subcon' : 'pe-badge-manpower'}">${app.esc(r.payroll_type)}</span></td>
                <td>${app.esc(r.project_name || '')}</td>
                <td><b>${app.esc(r.payee_name || '')}</b></td>
                <td>${app.esc(r.worker_name || '—')}</td>
                <td>${app.esc(r.foreman || '—')}</td>
                <td>${app.esc(periodStr)}</td>
                <td class="pe-amount-cell" style="font-weight:800;">₱${parseFloat(r.net_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td>${app.esc(r.payment_method || '—')}</td>
                <td><span class="pe-badge ${statusClass}">${app.esc(r.payroll_status)}</span></td>
                <td>${app.esc(r.remarks || '')}</td>
                <td>
                    <button class="action-btn" onclick="app.editPayrollEntry('${r.id}')" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button class="action-btn" onclick="app.deletePayrollEntryRecord('${r.id}')" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>`;
        }).join('');
    },

    loadPayrollEntrySummary: async function () {
        const projectId = document.getElementById('pe-project').value;
        const data = await this.request('get_payroll_entry_record_summary', { project_id: projectId || '' });
        if (data && data.status === 'success' && data.data) {
            const s = data.data;
            document.getElementById('pe-sum-gross').textContent = `₱${parseFloat(s.total_gross || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;

            document.getElementById('pe-sum-paid').textContent = `₱${parseFloat(s.total_paid || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
            document.getElementById('pe-sum-pending').textContent = `₱${parseFloat(s.total_pending || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
            document.getElementById('pe-sum-manpower-count').textContent = s.manpower_count || 0;
            document.getElementById('pe-sum-subcon-count').textContent = s.subcon_count || 0;
        }
    },

    addPayrollEntry: async function () {
        const id = document.getElementById('pe-id').value;
        const project_id = document.getElementById('pe-project').value;
        if (!project_id) { this.showToast('Please select a project.', 'error'); return; }
        const payroll_type = document.getElementById('pe-type').value;
        const payee_name = document.getElementById('pe-payee-name').value.trim();
        if (!payee_name) { this.showToast('Payee Name is required.', 'error'); return; }
        const period_start = document.getElementById('pe-period-start').value;
        if (!period_start) { this.showToast('Period Start is required.', 'error'); return; }
        const period_end = document.getElementById('pe-period-end').value;
        if (!period_end) { this.showToast('Period End is required.', 'error'); return; }
        if (period_end < period_start) { this.showToast('Period End cannot be earlier than Period Start.', 'error'); return; }

        const amount = parseFloat(document.getElementById('pe-amount').value) || 0;
        if (amount < 0) { this.showToast('Amount cannot be negative.', 'error'); return; }

        const payload = {
            project_id,
            worker_id: document.getElementById('pe-worker').value,
            foreman: document.getElementById('pe-foreman').value.trim(),
            payroll_type,
            payee_name,
            position_or_role: document.getElementById('pe-position').value.trim(),
            skill: document.getElementById('pe-skill').value.trim(),
            period_start,
            period_end,
            amount,
            payment_method: document.getElementById('pe-payment-method').value,
            payroll_status: document.getElementById('pe-status').value,
            subcon_company: document.getElementById('pe-subcon-company').value.trim(),
            subcon_scope: document.getElementById('pe-subcon-scope').value.trim(),
            subcon_reference_no: document.getElementById('pe-subcon-ref').value.trim(),
            remarks: document.getElementById('pe-remarks').value.trim()
        };

        const action = id ? 'update_payroll_entry_record' : 'add_payroll_entry_record';
        if (id) payload.id = id;
        const res = await this.request(action, payload);
        if (res && res.status === 'success') {
            this.showToast(id ? 'Payroll entry updated.' : 'Payroll entry added.');
            document.getElementById('pe-id').value = '';
            document.getElementById('pe-submit-text').textContent = 'Add Payroll Entry';
            document.getElementById('pe-cancel-btn').style.display = 'none';
            document.getElementById('pe-period-start').value = '';
            document.getElementById('pe-period-end').value = '';
            document.getElementById('pe-amount').value = '';
            document.getElementById('pe-payment-method').value = '';
            document.getElementById('pe-status').value = 'Pending';
            document.getElementById('pe-remarks').value = '';
            document.getElementById('pe-subcon-company').value = '';
            document.getElementById('pe-subcon-scope').value = '';
            document.getElementById('pe-subcon-ref').value = '';
            await this.loadPayrollEntryRecords();
            this.loadPayrollEntrySummary();
        } else {
            this.showToast(res?.message || 'Operation failed.', 'error');
        }
    },

    editPayrollEntry: async function (id) {
        const data = await this.request('get_payroll_entry_record', { id });
        if (!data || data.status !== 'success' || !data.data) {
            this.showToast('Record not found.', 'error');
            return;
        }
        const r = data.data;
        document.getElementById('pe-id').value = r.id;
        document.getElementById('pe-project').value = r.project_id;
        document.getElementById('pe-type').value = r.payroll_type || 'Manpower';
        this.togglePayrollType();
        document.getElementById('pe-worker').value = r.worker_id || '';
        document.getElementById('pe-foreman').value = r.foreman || '';
        document.getElementById('pe-payee-name').value = r.payee_name || '';
        document.getElementById('pe-position').value = r.position_or_role || '';
        document.getElementById('pe-skill').value = r.skill || '';
        document.getElementById('pe-period-start').value = r.period_start || '';
        document.getElementById('pe-period-end').value = r.period_end || '';
        document.getElementById('pe-amount').value = r.net_amount || r.gross_amount || '';
        document.getElementById('pe-payment-method').value = r.payment_method || '';
        document.getElementById('pe-status').value = r.payroll_status || 'Pending';
        document.getElementById('pe-remarks').value = r.remarks || '';
        document.getElementById('pe-subcon-company').value = r.subcon_company || '';
        document.getElementById('pe-subcon-scope').value = r.subcon_scope || '';
        document.getElementById('pe-subcon-ref').value = r.subcon_reference_no || '';
        document.getElementById('pe-submit-text').textContent = 'Update Payroll Entry';
        document.getElementById('pe-cancel-btn').style.display = '';
        document.querySelector('#mod-payroll .card:last-child')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    cancelEditPayrollEntry: function () {
        document.getElementById('pe-id').value = '';
        document.getElementById('pe-submit-text').textContent = 'Add Payroll Entry';
        document.getElementById('pe-cancel-btn').style.display = 'none';
        document.getElementById('pe-period-start').value = '';
        document.getElementById('pe-period-end').value = '';
        document.getElementById('pe-amount').value = '';
        document.getElementById('pe-payment-method').value = '';
        document.getElementById('pe-status').value = 'Pending';
        document.getElementById('pe-remarks').value = '';
        document.getElementById('pe-subcon-company').value = '';
        document.getElementById('pe-subcon-scope').value = '';
        document.getElementById('pe-subcon-ref').value = '';
        document.querySelector('#mod-payroll .card:last-child')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    deletePayrollEntryRecord: async function (id) {
        if (!confirm('Delete this payroll entry?')) return;
        const res = await this.request('delete_payroll_entry_record', { id });
        if (res && res.status === 'success') {
            this.showToast('Payroll entry deleted.');
            await this.loadPayrollEntryRecords();
            this.loadPayrollEntrySummary();
        } else {
            this.showToast(res?.message || 'Delete failed.', 'error');
        }
    },

    // --- CASH RELEASE / CAPITAL MONITORING ---

    loadCashRelease: async function () {
        document.getElementById('cr-id').value = '';
        document.getElementById('cr-submit-text').textContent = 'Add Record';
        document.getElementById('cr-cancel-btn').style.display = 'none';
        document.getElementById('cr-date').value = '';
        document.getElementById('cr-category').value = '';
        document.getElementById('cr-receiver').value = '';
        document.getElementById('cr-particulars').value = '';
        document.getElementById('cr-amount').value = '';

        await this.loadCashReleaseCategoryTotals();
        await this.loadCashReleaseRecords();
    },

    loadCashReleaseCategoryTotals: async function () {
        const data = await this.request('get_cash_release_category_totals');
        if (data && data.status === 'success' && data.data) {
            const t = data.data;
            document.getElementById('cr-total-materials').textContent = '₱' + parseFloat(t.total_materials || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            document.getElementById('cr-total-labor').textContent = '₱' + parseFloat(t.total_labor || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            document.getElementById('cr-total-other').textContent = '₱' + parseFloat(t.total_other || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            document.getElementById('cr-grand-total').textContent = '₱' + parseFloat(t.grand_total || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
        }
    },

    loadCashReleaseRecords: async function (query) {
        const q = query || document.getElementById('search-cash-releases')?.value || '';
        const tbody = document.getElementById('cash-release-content');
        if (!tbody) return;
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:20px;"><i class="fa-solid fa-spinner fa-spin"></i> Retrieving records...</td></tr>`;

        let data;
        if (q.trim()) {
            data = await this.request('search_cash_releases', { query: q });
        } else {
            data = await this.request('get_cash_releases');
        }
        this._cashReleaseData = (data && data.status === 'success' && data.data) ? data.data : (Array.isArray(data) ? data : []);
        this.renderCashReleaseTable();
    },

    renderCashReleaseTable: function () {
        const tbody = document.getElementById('cash-release-content');
        if (!tbody) return;
        if (!this._cashReleaseData || !this._cashReleaseData.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);">No cash release records found.</td></tr>`;
            return;
        }
        tbody.innerHTML = this._cashReleaseData.map(r => {
            const amount = parseFloat(r.release_amount || 0);
            return `<tr>
                <td>${app.esc(r.release_date || '')}</td>
                <td>${app.esc(r.category || '—')}</td>
                <td>${app.esc(r.released_to || '—')}</td>
                <td>${app.esc(r.release_description || '')}</td>
                <td class="cr-amount-out">-₱${amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td>
                    <button class="action-btn" onclick="app.editCashRelease('${r.id}')" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button class="action-btn" onclick="app.deleteCashRelease('${r.id}')" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>`;
        }).join('');
    },

    addCashRelease: async function () {
        const id = document.getElementById('cr-id').value;
        const release_date = document.getElementById('cr-date').value;
        if (!release_date) { this.showToast('Please select a date.', 'error'); return; }
        const category = document.getElementById('cr-category').value;
        if (!category) { this.showToast('Please select a category.', 'error'); return; }
        const receiver = document.getElementById('cr-receiver').value.trim();
        if (!receiver) { this.showToast('Please enter receiver name.', 'error'); return; }
        const particulars = document.getElementById('cr-particulars').value.trim();
        const amount = parseFloat(document.getElementById('cr-amount').value) || 0;
        if (amount <= 0) { this.showToast('Amount must be greater than 0.', 'error'); return; }

        const payload = {
            release_date,
            category,
            released_to: receiver,
            release_description: particulars,
            release_amount: amount
        };

        const action = id ? 'update_cash_release' : 'add_cash_release';
        if (id) payload.id = id;
        const data = await this.request(action, payload);
        if (data && data.status === 'success') {
            this.showToast(id ? 'Cash release updated.' : 'Cash release added.');
            document.getElementById('cr-id').value = '';
            document.getElementById('cr-submit-text').textContent = 'Add Record';
            document.getElementById('cr-cancel-btn').style.display = 'none';
            document.getElementById('cr-date').value = '';
            document.getElementById('cr-category').value = '';
            document.getElementById('cr-receiver').value = '';
            document.getElementById('cr-particulars').value = '';
            document.getElementById('cr-amount').value = '';
            await this.loadCashReleaseRecords();
            await this.loadCashReleaseCategoryTotals();
            this.loadDashboard();
        } else {
            this.showToast(data?.message || 'Operation failed.', 'error');
        }
    },

    editCashRelease: async function (id) {
        const data = await this.request('get_cash_release', { id });
        if (!data || data.status !== 'success' || !data.data) {
            this.showToast('Record not found.', 'error');
            return;
        }
        const r = data.data;
        document.getElementById('cr-id').value = r.id;
        document.getElementById('cr-date').value = r.release_date || '';
        document.getElementById('cr-category').value = r.category || '';
        document.getElementById('cr-receiver').value = r.released_to || '';
        document.getElementById('cr-particulars').value = r.release_description || '';
        document.getElementById('cr-amount').value = r.release_amount || '';
        document.getElementById('cr-submit-text').textContent = 'Update Record';
        document.getElementById('cr-cancel-btn').style.display = '';

        document.getElementById('mod-cash_release')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    cancelEditCashRelease: function () {
        document.getElementById('cr-id').value = '';
        document.getElementById('cr-submit-text').textContent = 'Add Record';
        document.getElementById('cr-cancel-btn').style.display = 'none';
        document.getElementById('cr-date').value = '';
        document.getElementById('cr-category').value = '';
        document.getElementById('cr-receiver').value = '';
        document.getElementById('cr-particulars').value = '';
        document.getElementById('cr-amount').value = '';
        document.getElementById('mod-cash_release')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    deleteCashRelease: async function (id) {
        if (!confirm('Delete this cash release record?')) return;
        const data = await this.request('delete_cash_release', { id });
        if (data && data.status === 'success') {
            this.showToast('Cash release deleted.');
            await this.loadCashReleaseRecords();
            await this.loadCashReleaseCategoryTotals();
            this.loadDashboard();
        } else {
            this.showToast(data?.message || 'Delete failed.', 'error');
        }
    },

    // --- NTP GLOBAL ---
    loadGlobalNTP: async function () {
        const proj = await this.request('get_projects');
        const select = document.getElementById('g-ntp-project');

        if (select) {
            select.innerHTML = '<option value="">Select Project</option>';

            if (Array.isArray(proj) && proj.length > 0) {
                const availableProjects = proj.filter(p => {
                    const status = (p.status || '').toLowerCase().trim();

                    // Lalabas lahat ng project basta hindi completed
                    return status !== 'completed';
                });

                if (availableProjects.length > 0) {
                    availableProjects.forEach(p => {
                        select.innerHTML += `
                        <option value="${p.id}">
                            ${escapeHTML(p.name) || 'Unnamed Project'} - ${escapeHTML(p.location) || 'No location'}
                        </option>
                    `;
                    });
                } else {
                    select.innerHTML += '<option value="" disabled>No available projects</option>';
                }
            } else {
                select.innerHTML += '<option value="" disabled>No projects found</option>';
            }
        }

        const ntps = await this.request('get_all_ntps');
        const tbody = document.querySelector('#table-global-ntp tbody');

        if (!tbody) return;

        tbody.innerHTML = '';

        if (Array.isArray(ntps) && ntps.length > 0) {
            ntps.forEach(n => {
                let fmtCost = n.award_cost
                    ? '₱' + parseFloat(n.award_cost).toLocaleString('en-US')
                    : 'N/A';

                tbody.innerHTML += `
                <tr>
                    <td><b style="color:var(--text-dark);">${escapeHTML(n.project_name) || 'N/A'}</b></td>
                    <td>${escapeHTML(n.ntp_ticket) || 'N/A'}</td>
                    <td>${escapeHTML(n.date_received) || 'N/A'}</td>
                    <td style="font-weight:700;">${fmtCost}</td>
                    <td><b style="color:var(--danger);">${escapeHTML(n.due_date) || 'N/A'}</b></td>
                    <td>${escapeHTML(n.acceptance_date) || 'N/A'}</td>
                    <td>${escapeHTML(n.completion_date_project || 'N/A')}</td>
                    <td style="font-weight:700;">${n.total_amount_project ? '₱' + parseFloat(n.total_amount_project).toLocaleString('en-US') : 'N/A'}</td>
                    <td>
                        ${n.file_path
                        ? `<span style="cursor:pointer; color:var(--primary-hover); text-decoration:underline;" onclick="app.viewAttachedFile('${safe(n.file_path)}')">View PDF</span>`
                        : 'No file'
                    }
                    </td>
                </tr>
            `;
            });
        } else {
            tbody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state-wrapper">
                    <i class="fa-solid fa-file-pdf"></i>
                    <p>No NTP records found.</p>
                </td>
            </tr>
        `;
        }

        if (this._pendingNTPTab) {
            const tab = this._pendingNTPTab;
            this._pendingNTPTab = null;
            this.switchNTPTab(tab);
        } else {
            this.switchNTPTab('ntp');
        }
    },
    switchNTPTab: function (tabId) {
        document.querySelectorAll('.ntp-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.ntp-tab-content').forEach(t => t.style.display = 'none');
        const tabBtn = document.querySelector(`.ntp-tab[data-tab="${tabId}"]`);
        if (tabBtn) tabBtn.classList.add('active');
        const tabContent = document.getElementById(`ntp-tab-${tabId}`);
        if (tabContent) tabContent.style.display = 'block';
        if (tabId === 'award_cost') {
            this.loadAwardCostsModule();
        }
    },
    uploadGlobalNTP: async function () {
        const project_id = document.getElementById('g-ntp-project').value; const ticket = document.getElementById('g-ntp-ticket').value; const date = document.getElementById('g-ntp-date').value; const award_cost = document.getElementById('g-ntp-cost').value; const due_date = document.getElementById('g-ntp-due').value; const accept_date = document.getElementById('g-ntp-accept').value; const completion_date = document.getElementById('g-ntp-completion').value; const work_description = document.getElementById('g-ntp-work-desc').value; const project_description = document.getElementById('g-ntp-project-desc').value; const total_amount = document.getElementById('g-ntp-total-amount').value || 0; const fileInput = document.getElementById('g-ntp-file');
        if (!project_id || !date || !due_date || fileInput.files.length === 0) { this.showToast('Project, NTP Date, Due Date, and File are required!', 'error'); return; }
        const fd = new FormData(); fd.append('project_id', project_id); fd.append('ticket', ticket); fd.append('date', date); fd.append('award_cost', award_cost); fd.append('due_date', due_date); fd.append('accept_date', accept_date); fd.append('completion_date', completion_date); fd.append('work_description', work_description); fd.append('project_description', project_description); fd.append('total_amount', total_amount); fd.append('file', fileInput.files[0]);
        const res = await this.request('upload_ntp_file', fd, true);
        if (res.status === 'success') { document.getElementById('g-ntp-ticket').value = ''; document.getElementById('g-ntp-cost').value = ''; document.getElementById('g-ntp-accept').value = ''; document.getElementById('g-ntp-completion').value = ''; document.getElementById('g-ntp-work-desc').value = ''; document.getElementById('g-ntp-project-desc').value = ''; document.getElementById('g-ntp-total-amount').value = ''; document.getElementById('g-ntp-file').value = ''; await this.loadGlobalNTP(); this.loadDashboard(); this.showToast("NTP Successfully uploaded!"); } else { this.showToast(res.message, 'error'); }
    },

    openBulkAdd: function (module = 'projects') {
        const modal = document.getElementById('modal-bulk-all');
        const select = document.getElementById('bulk-all-module');
        const textarea = document.getElementById('bulk-all-textarea');

        if (!modal || !select || !textarea) return;

        select.value = module || 'projects';
        this.updateBulkTemplate();
        modal.style.display = 'flex';
    },

    updateBulkTemplate: function () {
        const select = document.getElementById('bulk-all-module');
        const format = document.getElementById('bulk-all-format');
        const example = document.getElementById('bulk-all-example');
        const textarea = document.getElementById('bulk-all-textarea');

        if (!select || !format || !example || !textarea) return;

        const templates = {
            projects: {
                format: 'Project Name, Block, Lot, Client, Location, Description, Foreman, Foreman 2 optional, Start Date YYYY-MM-DD, Completion Date optional, Work Description optional, Project Description optional, Total Amount optional',
                example: 'Project A, Block 1, Lot 5, Client One, Laguna, Two storey house, Juan Foreman, Pedro Foreman, 2026-06-01, 2026-12-01, CHB Laying & Finish, "Two Storey Residential Unit", 1500000'
            },
            suppliers: {
                format: 'Supplier Name, Materials, Contact, Email optional',
                example: 'ABC Hardware, "Cement, Sand, Gravel", 09123456789, abc@email.com'
            },
            inventory: {
                format: 'Item Name, Category, Quantity, Unit, Unit Cost, Supplier optional',
                example: 'Cement, Construction Materials, 100, bags, 280, ABC Hardware'
            },
            manpower: {
                format: 'Full Name, Skills, Position, Daily Rate, Project Name or Project ID optional',
                example: 'Juan Dela Cruz, Mason, Worker, 700, Project A'
            },
            award_costs: {
                format: 'Project Name or ID, Service Agreement Code, Item, Unit, Start Date YYYY-MM-DD, Completion Date YYYY-MM-DD, Work Description, Project Description, Total Amount',
                example: 'Project A, SAC-001, CHB, sqm, 2026-06-01, 2026-06-15, CHB Laying, Two storey house, 15000'
            },
            payroll: {
                format: 'Date YYYY-MM-DD, Worker Name, Job Description, Award Cost, Cash Advance',
                example: '2026-06-01, Juan Dela Cruz, CHB Laying, 1500, 500'
            },
            cash_release: {
                format: 'Date YYYY-MM-DD, Category (Materials/Labor/Other Expenses), Receiver Name, Description, Amount',
                example: '2026-06-01, Materials, Juan Dela Cruz, Payment for materials, 5600'
            },
            ntp: {
                format: 'Project Name or ID, NTP Ticket, Date Received YYYY-MM-DD, Award Cost, Due Date YYYY-MM-DD, Acceptance Date optional, Completion Date optional, Work Description optional, Project Description optional, Total Amount optional',
                example: 'Project A, NTP-001, 2026-06-01, 50000, 2026-06-10, 2026-06-02, 2026-12-01, "CHB Laying & Finishing", "Two Storey Unit", 1500000'
            }
        };

        const picked = templates[select.value] || templates.projects;

        format.textContent = picked.format;
        example.textContent = picked.example;
        textarea.placeholder = picked.example + '\n' + picked.example;
    },

    parseCSVLine: function (line) {
        const result = [];
        let current = '';
        let inQuotes = false;

        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            const nextChar = line[i + 1];

            if (char === '"' && inQuotes && nextChar === '"') {
                current += '"';
                i++;
                continue;
            }

            if (char === '"') {
                inQuotes = !inQuotes;
                continue;
            }

            if (char === ',' && !inQuotes) {
                result.push(current.trim());
                current = '';
                continue;
            }

            current += char;
        }

        result.push(current.trim());
        return result;
    },

    parseBulkRows: function (module, rawText) {
        const mappings = {
            projects: ['name', 'block_no', 'lot_no', 'client', 'location', 'description', 'foreman', 'foreman_2', 'start_date'],
            suppliers: ['name', 'materials', 'contact', 'email'],
            inventory: ['name', 'category', 'qty', 'unit', 'cost', 'supplier'],
            manpower: ['name', 'skills', 'position', 'salary', 'project'],
            award_costs: ['project', 'service_agreement_code', 'item', 'unit', 'start_date', 'completion_date', 'work_description', 'project_description', 'total_amount'],
            payroll: ['date', 'name', 'job_desc', 'award', 'advance'],
            cash_release: ['date', 'category', 'name', 'description', 'amount'],
            ntp: ['project', 'ticket', 'date', 'award_cost', 'due_date', 'accept_date']
        };

        const keys = mappings[module];
        if (!keys) return [];

        return rawText
            .split(/\r?\n/)
            .map(line => line.trim())
            .filter(line => line !== '')
            .map(line => {
                const parts = this.parseCSVLine(line);
                const item = {};

                keys.forEach((key, index) => {
                    item[key] = parts[index] || '';
                });

                return item;
            });
    },

    bulkAddAll: async function () {
        const moduleSelect = document.getElementById('bulk-all-module');
        const textarea = document.getElementById('bulk-all-textarea');

        if (!moduleSelect || !textarea) return;

        const module = moduleSelect.value;
        const rawText = (textarea.value || '').trim();

        if (!module) {
            this.showToast('Please select a module.', 'error');
            return;
        }

        if (!rawText) {
            this.showToast('Please paste at least one record.', 'error');
            return;
        }

        const items = this.parseBulkRows(module, rawText);

        if (!items.length) {
            this.showToast('No valid rows found.', 'error');
            return;
        }

        const res = await this.request('bulk_add_all', {
            module,
            items: JSON.stringify(items)
        });

        if (res.status === 'success') {
            textarea.value = '';
            this.closeModal('modal-bulk-all');
            window.globalSearchData = null;

            if (module === 'projects') {
                this.loadProjects();
                this.loadDashboard();
                this.loadProjectOptionsForManpower();
            }

            if (module === 'suppliers' || module === 'inventory') {
                this.loadSuppliersDashboard();
            }

            if (module === 'manpower') {
                this.populateManpowerDropdowns();
                this.loadManpowerFolders();
                this.loadDashboard();
                this.populateForemanDropdown();
            }

            if (module === 'award_costs') {
                this.loadAwardCosts();
            }

            if (module === 'payroll') {
                this.renderPayrollTab();
                this.populatePayrollDatalists();
                this.loadDashboard();
            }

            if (module === 'cash_release') {
                this.loadCashRelease();
                this.loadDashboard();
            }

            if (module === 'ntp') {
                this.loadGlobalNTP();
            }

            const skippedCount = Array.isArray(res.skipped) ? res.skipped.length : 0;
            const skippedText = skippedCount ? ` Skipped: ${skippedCount}.` : '';

            this.showToast(`${res.inserted || items.length} record(s) added successfully.${skippedText}`);
        } else {
            this.showToast(res.message || 'Bulk add failed.', 'error');

            if (Array.isArray(res.skipped) && res.skipped.length) {
                console.table(res.skipped);
            }
        }
    }
};

app._cashReleaseData = [];

app.recordsPerPage = 10;
app.pagination = {};
app.paginationTimers = {};
app.paginationTableCounter = 0;
app.paginationObserver = null;

app.setupAutoPagination = function () {
    if (this.paginationObserver) return;

    const scanTables = () => {
        document.querySelectorAll('.sheet-table').forEach(table => {
            this.watchPaginatedTable(table);
        });
    };

    scanTables();

    this.paginationObserver = new MutationObserver((mutations) => {
        let shouldScan = false;

        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (
                    node.nodeType === 1 &&
                    (
                        node.matches?.('.sheet-table') ||
                        node.querySelector?.('.sheet-table')
                    )
                ) {
                    shouldScan = true;
                }
            });
        });

        if (shouldScan) scanTables();
    });

    this.paginationObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
};

app.watchPaginatedTable = function (table) {
    if (!table || table.dataset.paginationWatch === '1') return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    if (!table.id) {
        this.paginationTableCounter += 1;
        table.id = 'auto-paginated-table-' + this.paginationTableCounter;
    }

    table.dataset.paginationWatch = '1';

    const runPagination = () => {
        clearTimeout(this.paginationTimers[table.id]);

        this.paginationTimers[table.id] = setTimeout(() => {
            this.applyTablePagination(table.id);
        }, 80);
    };

    const rowObserver = new MutationObserver(runPagination);
    rowObserver.observe(tbody, {
        childList: true
    });

    runPagination();
};

app.getTableRowGroups = function (tbody) {
    const rows = Array.from(tbody.children).filter(row => row.tagName === 'TR');
    const groups = [];

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];

        if (row.classList.contains('nested-row')) continue;

        const group = [row];
        const nextRow = rows[i + 1];

        if (nextRow && nextRow.classList.contains('nested-row')) {
            group.push(nextRow);
            i++;
        }

        groups.push(group);
    }

    return groups;
};

app.applyTablePagination = function (tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const groups = this.getTableRowGroups(tbody);
    const totalRecords = groups.length;
    const perPage = this.recordsPerPage || 10;

    const wrapper = table.closest('.table-responsive') || table;
    const pagerId = tableId + '-pagination';

    let pager = document.getElementById(pagerId);

    if (totalRecords <= perPage) {
        groups.forEach(group => {
            group.forEach(row => {
                row.style.display = '';
            });
        });

        if (pager) pager.remove();
        this.pagination[tableId] = 1;
        return;
    }

    const totalPages = Math.ceil(totalRecords / perPage);
    let currentPage = parseInt(this.pagination[tableId] || 1);

    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;

    this.pagination[tableId] = currentPage;

    const startIndex = (currentPage - 1) * perPage;
    const endIndex = startIndex + perPage;

    groups.forEach((group, index) => {
        const shouldShow = index >= startIndex && index < endIndex;

        group.forEach(row => {
            row.style.display = shouldShow ? '' : 'none';
        });
    });

    if (!pager) {
        pager = document.createElement('div');
        pager.id = pagerId;
        pager.className = 'table-pagination';

        if (wrapper.nextSibling) {
            wrapper.parentNode.insertBefore(pager, wrapper.nextSibling);
        } else {
            wrapper.parentNode.appendChild(pager);
        }
    }

    const showingFrom = startIndex + 1;
    const showingTo = Math.min(endIndex, totalRecords);

    pager.innerHTML = `
        <div class="pagination-info">
            Showing <b>${showingFrom}</b>-${showingTo} of <b>${totalRecords}</b> records
        </div>

        <div class="pagination-actions">
            <button 
                type="button" 
                class="pagination-btn" 
                onclick="app.changeTablePage('${tableId}', ${currentPage - 1})"
                ${currentPage === 1 ? 'disabled' : ''}
            >
                <i class="fa-solid fa-chevron-left"></i> Previous
            </button>

            <span class="pagination-page">Page ${currentPage} of ${totalPages}</span>

            <button 
                type="button" 
                class="pagination-btn" 
                onclick="app.changeTablePage('${tableId}', ${currentPage + 1})"
                ${currentPage === totalPages ? 'disabled' : ''}
            >
                Next <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    `;
};

app.changeTablePage = function (tableId, page) {
    this.pagination[tableId] = page;
    this.applyTablePagination(tableId);

    const table = document.getElementById(tableId);
    const wrapper = table?.closest('.table-responsive');

    if (wrapper) {
        wrapper.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
};

// =========================
// BILLING PROGRESS
// =========================
app._billingData = [];

app.calcBillingTotal = function () {
    const billed = parseFloat(document.getElementById('bp-amount-billed').value.replace(/,/g, '')) || 0;
    const collected = parseFloat(document.getElementById('bp-amount-collected').value.replace(/,/g, '')) || 0;
    if (collected > billed) {
        app.showAlertMessage?.('Amount Collected cannot exceed Amount Billed.', 'warning');
        document.getElementById('bp-amount-collected').value = billed.toFixed(2);
    }
};

app.loadBillingModule = async function () {
    document.getElementById('bp-id').value = '';
    document.getElementById('bp-submit-text').textContent = 'Add Record';
    document.getElementById('bp-cancel-btn').style.display = 'none';
    document.getElementById('bp-project').value = '';
    document.getElementById('bp-award-cost').innerHTML = '<option value="">No Award Cost / Service Agreement</option>';
    document.getElementById('bp-date').value = '';
    document.getElementById('bp-ref-no').value = '';
    document.getElementById('bp-description').value = '';
    document.getElementById('bp-amount-billed').value = '';
    document.getElementById('bp-amount-collected').value = '';
    document.getElementById('bp-payment-method').value = '';
    document.getElementById('bp-status').value = 'Pending';
    document.getElementById('bp-remarks').value = '';

    await this.populateBillingProjects();
    await this.loadBillingRecords();
};

app.populateBillingProjects = async function () {
    const data = await this.request('get_projects');
    const sel = document.getElementById('bp-project');
    const val = sel.value;
    sel.innerHTML = '<option value="">Select Project Site / NTP</option>';
    const list = (data && data.status === 'success' && data.data) ? data.data : (Array.isArray(data) ? data : []);
    list.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        const loc = p.location ? ` - ${p.location}` : '';
        const blk = p.block_no ? ` (Blk ${p.block_no}${p.lot_no ? ` Lot ${p.lot_no}` : ''})` : '';
        opt.textContent = `${p.name}${blk}${loc}`;
        sel.appendChild(opt);
    });
    sel.value = val;
};

app.loadBillingAwardCosts = async function (projectId) {
    const sel = document.getElementById('bp-award-cost');
    sel.innerHTML = '<option value="">Service Agreement / Award Cost (Optional)</option>';
    if (!projectId) return;
    const data = await this.request('get_award_costs_for_billing', { project_id: projectId });
    if (data && data.status === 'success' && data.data) {
        data.data.forEach(a => {
            const opt = document.createElement('option');
            opt.value = a.id;
            opt.textContent = `${a.service_agreement_code} - ₱${parseFloat(a.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
            sel.appendChild(opt);
        });
    }
};

app.loadBillingSummary = async function (projectId) {
    const data = await this.request('get_billing_summary', { project_id: projectId });
    if (data && data.status === 'success' && data.data) {
        const s = data.data;
        const hasAward = parseFloat(s.award_total_amount || 0) > 0;
        document.getElementById('bp-sum-award').textContent = hasAward
            ? `₱${parseFloat(s.award_total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
            : 'N/A';
        document.getElementById('bp-sum-billed').textContent = `₱${parseFloat(s.total_billed || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        document.getElementById('bp-sum-collected').textContent = `₱${parseFloat(s.total_collected || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        document.getElementById('bp-sum-balance').textContent = hasAward
            ? `₱${Math.max(0, s.remaining_balance || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
            : 'N/A';
        const pct = hasAward ? Math.min(100, ((s.total_collected || 0) / s.award_total_amount) * 100) : 0;
        document.getElementById('bp-progress-bar').style.width = `${pct}%`;
        document.getElementById('bp-sum-percent').textContent = hasAward ? `${pct.toFixed(1)}%` : 'N/A';
    } else {
        document.getElementById('bp-sum-award').textContent = 'N/A';
        document.getElementById('bp-sum-billed').textContent = '₱0.00';
        document.getElementById('bp-sum-collected').textContent = '₱0.00';
        document.getElementById('bp-sum-balance').textContent = 'N/A';
        document.getElementById('bp-progress-bar').style.width = '0%';
        document.getElementById('bp-sum-percent').textContent = 'N/A';
    }
};

app.loadBillingRecords = async function (query, projectId) {
    const q = query || document.getElementById('search-billing')?.value || '';
    let data;
    if (q.trim()) {
        data = await this.request('search_billing_records', { query: q });
    } else {
        data = await this.request('get_billing_records', { project_id: projectId || '' });
    }
    this._billingData = (data && data.status === 'success' && data.data) ? data.data : [];
    this.renderBillingTable();
    if (!q && !projectId) {
        // auto-load summary for first project if none selected
        const sel = document.getElementById('bp-project');
        if (sel && sel.value) {
            this.loadBillingSummary(sel.value);
        }
    }
};

app.renderBillingTable = function () {
    const tbody = document.querySelector('#table-billing tbody');
    if (!tbody) return;
    if (!this._billingData || !this._billingData.length) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:32px;color:var(--text-muted);">No billing records found.</td></tr>`;
        return;
    }
    tbody.innerHTML = this._billingData.map(r => {
        const statusClass = `bp-status-${(r.status || 'Pending').toLowerCase().replace(/\s+/g, '-')}`;
        return `<tr>
            <td>${r.billing_date || ''}</td>
            <td>${app.esc(r.project_name || '')}</td>
            <td>${app.esc(r.service_agreement_code || '—')}</td>
            <td>${app.esc(r.billing_reference_no || '—')}</td>
            <td>${app.esc(r.billing_description || '')}</td>
            <td class="bp-amount-cell">₱${parseFloat(r.amount_billed || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td class="bp-amount-cell">₱${parseFloat(r.amount_collected || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td><span class="bp-badge ${statusClass}">${app.esc(r.status)}</span></td>
            <td>${app.esc(r.remarks || '')}</td>
            <td>
                <button class="action-btn" onclick="app.editBillingRecord('${r.id}')" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                <button class="action-btn" onclick="app.deleteBillingRecord('${r.id}')" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
            </td>
        </tr>`;
    }).join('');
};

app.addBillingRecord = async function () {
    const id = document.getElementById('bp-id').value;
    const project_id = document.getElementById('bp-project').value;
    if (!project_id) { app.showAlertMessage?.('Please select a project.', 'warning'); return; }
    const billing_date = document.getElementById('bp-date').value;
    if (!billing_date) { app.showAlertMessage?.('Please select a billing date.', 'warning'); return; }
    const billing_description = document.getElementById('bp-description').value.trim();
    if (!billing_description) { app.showAlertMessage?.('Please enter a billing description.', 'warning'); return; }
    const amount_billed = parseFloat(document.getElementById('bp-amount-billed').value.replace(/,/g, '')) || 0;
    if (amount_billed <= 0) { app.showAlertMessage?.('Amount Billed must be greater than 0.', 'warning'); return; }
    const amount_collected = parseFloat(document.getElementById('bp-amount-collected').value.replace(/,/g, '')) || 0;
    if (amount_collected > amount_billed) { app.showAlertMessage?.('Amount Collected cannot exceed Amount Billed.', 'warning'); return; }
    const status = document.getElementById('bp-status').value;
    if (status === 'Collected' && amount_collected < amount_billed) {
        app.showAlertMessage?.('Please set Amount Collected equal to Amount Billed when status is Collected.', 'warning'); return;
    }

    const payload = {
        project_id,
        award_cost_id: document.getElementById('bp-award-cost').value,
        billing_date,
        billing_reference_no: document.getElementById('bp-ref-no').value.trim(),
        billing_description,
        amount_billed,
        amount_collected,
        payment_method: document.getElementById('bp-payment-method').value,
        remarks: document.getElementById('bp-remarks').value.trim(),
        status
    };

    const action = id ? 'edit_billing_record' : 'add_billing_record';
    if (id) payload.id = id;
    const data = await this.request(action, payload);
    if (data && data.status === 'success') {
        app.showAlertMessage?.(id ? 'Billing record updated.' : 'Billing record added.', 'success');
        document.getElementById('bp-id').value = '';
        document.getElementById('bp-submit-text').textContent = 'Add Record';
        document.getElementById('bp-cancel-btn').style.display = 'none';
        document.getElementById('bp-amount-billed').value = '';
        document.getElementById('bp-amount-collected').value = '';
        document.getElementById('bp-date').value = '';
        document.getElementById('bp-ref-no').value = '';
        document.getElementById('bp-description').value = '';
        document.getElementById('bp-payment-method').value = '';
        document.getElementById('bp-remarks').value = '';
        document.getElementById('bp-status').value = 'Pending';
        await this.loadBillingRecords('', project_id);
        await this.loadBillingSummary(project_id);
    } else {
        app.showAlertMessage?.(data?.message || 'Operation failed.', 'error');
    }
};

app.editBillingRecord = async function (id) {
    const data = await this.request('get_billing_record', { id });
    if (!data || data.status !== 'success' || !data.data) {
        app.showAlertMessage?.('Record not found.', 'error');
        return;
    }
    const r = data.data;
    document.getElementById('bp-id').value = r.id;
    document.getElementById('bp-project').value = r.project_id;
    await this.loadBillingAwardCosts(r.project_id);
    document.getElementById('bp-award-cost').value = r.award_cost_id || '';
    document.getElementById('bp-date').value = r.billing_date || '';
    document.getElementById('bp-ref-no').value = r.billing_reference_no || '';
    document.getElementById('bp-description').value = r.billing_description || '';
    document.getElementById('bp-amount-billed').value = r.amount_billed || '';
    document.getElementById('bp-amount-collected').value = r.amount_collected || '';
    document.getElementById('bp-payment-method').value = r.payment_method || '';
    document.getElementById('bp-status').value = r.status || 'Pending';
    document.getElementById('bp-remarks').value = r.remarks || '';
    document.getElementById('bp-submit-text').textContent = 'Update Record';
    document.getElementById('bp-cancel-btn').style.display = '';

    document.getElementById('mod-billing_progress')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

app.cancelEditBillingRecord = function () {
    document.getElementById('bp-id').value = '';
    document.getElementById('bp-submit-text').textContent = 'Add Record';
    document.getElementById('bp-cancel-btn').style.display = 'none';
    document.getElementById('bp-amount-billed').value = '';
    document.getElementById('bp-amount-collected').value = '';
    document.getElementById('bp-date').value = '';
    document.getElementById('bp-ref-no').value = '';
    document.getElementById('bp-description').value = '';
    document.getElementById('bp-payment-method').value = '';
    document.getElementById('bp-remarks').value = '';
    document.getElementById('bp-status').value = 'Pending';
    document.getElementById('mod-billing_progress')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

app.deleteBillingRecord = async function (id) {
    if (!confirm('Are you sure you want to delete this billing record?')) return;
    const data = await this.request('delete_billing_record', { id });
    if (data && data.status === 'success') {
        app.showAlertMessage?.('Billing record deleted.', 'success');
        const projectId = document.getElementById('bp-project').value;
        await this.loadBillingRecords('', projectId);
        await this.loadBillingSummary(projectId);
    } else {
        app.showAlertMessage?.(data?.message || 'Delete failed.', 'error');
    }
};

window.onload = () => {
    app.init();

    setTimeout(() => {
        app.setupAutoPagination();
    }, 300);
};
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>J.I.OJO Construction Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>

<body>

    <div id="auth-screen">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fa-solid fa-helmet-safety" style="color: var(--primary);"></i> J.I.OJO</h2>
                <p>Enterprise Management</p>
            </div>

            <form id="auth-form">
                <div class="login-input-group">
                    <label for="auth-email">Corporate Email</label>
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" id="auth-email" class="with-icon" placeholder="admin@jiojo.com" required>
                </div>

                <div class="login-input-group">
                    <label for="auth-pass">Password</label>
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" id="auth-pass" class="with-icon" placeholder="••••••••" required>
                    <i class="fa-solid fa-eye" id="toggle-password" onclick="app.togglePassword()"></i>
                </div>

                <button type="submit" class="btn login-btn" id="auth-btn">
                    SECURE LOGIN <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
                </button>
            </form>
        </div>
    </div>

    <div id="app-layout">

        <div id="sidebar-overlay" onclick="app.toggleSidebar()"></div>

        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fa-solid fa-helmet-safety"></i> J.I.OJO</h2>
                <span>Management System</span>
                <button class="mobile-close-btn" onclick="app.toggleSidebar()"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <ul class="nav-links">
                <li onclick="app.showModule('dashboard')" data-module="dashboard" class="active"><i
                        class="fa-solid fa-chart-pie"></i> Dashboard</li>
                <li onclick="app.showModule('users')" data-module="users"><i class="fa-solid fa-address-card"></i>
                    Manpower List</li>
                <li onclick="app.showModule('bill_of_materials')" data-module="bill_of_materials"><i
                        class="fa-solid fa-receipt"></i> Bill of Materials</li>
                <li onclick="app.showModule('billing_progress')" data-module="billing_progress"><i
                        class="fa-solid fa-chart-line"></i> Billing Progress</li>
                <li onclick="app.showModule('payroll')" data-module="payroll"><i
                        class="fa-solid fa-money-check-dollar"></i> Payroll</li>
                <li onclick="app.showModule('cash_release')" data-module="cash_release"><i
                        class="fa-solid fa-hand-holding-dollar"></i> Cash Release</li>
                <li onclick="app.showModule('global_ntp')" data-module="global_ntp"><i
                        class="fa-solid fa-file-signature"></i> Notice to Proceed</li>
                <li onclick="app.showModule('projects')" data-module="projects"><i class="fa-solid fa-city"></i>
                    Projects (Sites)</li>
                <li onclick="app.showModule('materials')" data-module="materials"><i
                        class="fa-solid fa-truck-ramp-box"></i> Material Supplier</li>
            </ul>
            <div class="logout-btn" onclick="app.logout()"><i class="fa-solid fa-power-off"
                    style="margin-right:10px;"></i> Logout</div>
        </aside>

        <main class="main-content">
            <header class="header">
                <button class="mobile-menu-btn" onclick="app.toggleSidebar()"><i class="fa-solid fa-bars"></i></button>

                <div class="breadcrumbs" id="dynamic-breadcrumbs">
                    <span class="breadcrumb-link" onclick="app.showModule('dashboard')"><i
                            class="fa-solid fa-house"></i> Home</span>
                    <i class="fa-solid fa-chevron-right separator"></i>
                    <b id="breadcrumb-current" class="active-crumb">Dashboard</b>
                </div>
                <div class="global-search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="global-search-input" placeholder="Search Projects & Manpower..."
                        oninput="app.handleGlobalSearch(this.value)">
                    <button class="clear-search-btn" id="clear-search-btn" onclick="app.clearGlobalSearch()"><i
                            class="fa-solid fa-circle-xmark"></i></button>
                </div>

                <div class="header-right">
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=FACC15&color=000&bold=true"
                            alt="Avatar">
                        <div class="user-info"><span class="user-name">System Admin</span><span
                                class="user-role">Project Manager</span></div>
                    </div>
                </div>
            </header>

            <section id="mod-dashboard" class="module active">
                <div class="quick-stats-grid">
                    <div class="stat-card" onclick="app.showModule('projects')">
                        <div class="stat-details">
                            <h3>Ongoing Projects</h3>
                            <h2 id="stat-projects">0</h2><span class="badge ongoing">Active Sites</span>
                        </div>
                        <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
                    </div>
                    <div class="stat-card" onclick="app.showModule('users')">
                        <div class="stat-details">
                            <h3>Active Manpower</h3>
                            <h2 id="stat-users">0</h2><span class="badge success">Deployed</span>
                        </div>
                        <div class="stat-icon" style="background:var(--success-bg); color:var(--success);"><i
                                class="fa-solid fa-users"></i></div>
                    </div>
                    <div class="stat-card" onclick="app.showModule('cash_release')">
                        <div class="stat-details">
                            <h3>Total Cash Released</h3>
                            <h2 id="stat-cash-release">₱0.00</h2><span class="badge pending">Ledger</span>
                        </div>
                        <div class="stat-icon" style="background:var(--danger-bg); color:var(--danger);"><i
                                class="fa-solid fa-hand-holding-dollar"></i></div>
                    </div>
                    <div class="stat-card" onclick="app.showModule('payroll')">
                        <div class="stat-details">
                            <h3>Total Cash Advance</h3>
                            <h2 id="stat-payroll-advance">₱0.00</h2><span class="badge pending">Payroll</span>
                        </div>
                        <div class="stat-icon" style="background:var(--warning-bg); color:var(--warning);"><i
                                class="fa-solid fa-money-check-dollar"></i></div>
                    </div>
                </div>
                <div id="upcoming-deadlines-container" class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-calendar-days" style="color: var(--text-dark);"></i> Upcoming
                            Deadlines</h3>
                        <p>Automatically sorts tasks prioritizing urgent deadlines within the next 30 days.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="sheet-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px; text-align: center;">Type</th>
                                    <th>Site / Name</th>
                                    <th>Action Needed</th>
                                    <th>Target Date</th>
                                    <th>Urgency</th>
                                </tr>
                            </thead>
                            <tbody id="deadlines-content"></tbody>
                        </table>
                    </div>
                </div>
                <div id="global-search-results" class="card" style="display:none;">
                    <div class="card-header"
                        style="border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 16px;">
                        <h3><i class="fa-solid fa-bolt" style="color: var(--primary-hover);"></i> Search Results
                        </h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">Found results
                            matching
                            "<b id="search-query-display" style="color:var(--text-dark);"></b>"</p>
                    </div>
                    <div id="search-results-content" style="display: flex; flex-direction: column; gap: 8px;">
                    </div>
                </div>
            </section>

            <section id="mod-projects" class="module">
                <div id="projects-list-view">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-layer-group"></i> Create New Project / Site</h3>
                        </div>
                        <div class="form-grid">
                            <input type="text" id="proj-name" placeholder="Project / Unit Name">
                            <input type="text" id="proj-block" placeholder="Block No.">
                            <input type="text" id="proj-lot" placeholder="Lot No.">
                            <input type="text" id="proj-client" placeholder="Client Name">
                            <input type="text" id="proj-loc" placeholder="Location">
                            <input type="text" id="proj-desc" placeholder="Model / Description">

                            <select id="proj-foreman">
                                <option value="">Select Foreman 1 (Required)</option>
                            </select>

                            <select id="proj-foreman2">
                                <option value="">Select Foreman 2 (Optional)</option>
                            </select>

                            <input type="date" id="proj-start" title="Start Date">

                            <div style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px;">
                                <label id="file-dropzone-label" for="proj-ntp-init" class="btn-outline"
                                    style="cursor: pointer; width: 100%; justify-content: flex-start; border-style: dashed; border-width: 2px; color: var(--text-muted); border-color: #D1D5DB; font-weight: 600;">
                                    <i class="fa-solid fa-file-arrow-up"></i>
                                    <span id="file-name-display">Attach Initial NTP Document (Optional)</span>
                                </label>

                                <input type="file" id="proj-ntp-init" style="display: none;" accept=".pdf, image/*"
                                    onchange="app.handleFileSelect(this)">
                            </div>

                            <div
                                style="grid-column: 1 / -1; display: flex; justify-content: flex-end; gap:8px; margin-top: 4px;">
                                <button type="button" class="btn-outline" onclick="app.openBulkAdd('projects')">
                                    <i class="fa-solid fa-layer-group"></i> Bulk Add
                                </button>

                                <button type="button" class="btn" onclick="app.submitProjectForm()">
                                    <i class="fa-solid fa-plus"></i> Create Project
                                </button>
                            </div>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div style="position: relative; width: 250px;"><i class="fa-solid fa-magnifying-glass"
                                    style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i><input
                                    type="text" id="search-projects-table" placeholder="Search Projects..."
                                    style="width: 100%; padding-left: 32px; border-radius: 6px; border: 1px solid var(--border); height: 32px; font-size: 12px;">
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;"><span
                                    style="font-weight: 600; color: var(--text-muted); font-size: 0.8rem;">Filter:</span><select
                                    id="filter-projects"
                                    style="width: 130px; height: 32px; border-radius: 6px; border: 1px solid var(--border); font-size: 12px;">
                                    <option value="all">All Projects</option>
                                    <option value="pending">Pending</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                </select></div>
                        </div>
                        <div class="table-responsive">
                            <table class="sheet-table" id="table-projects">
                                <thead>
                                    <tr>
                                        <th>Project/Site Name</th>
                                        <th>Block</th>
                                        <th>Lot</th>
                                        <th>Location</th>
                                        <th>Foreman 1</th>
                                        <th>Foreman 2</th>
                                        <th>Start Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="project-details-view" style="display:none;">
                    <div style="margin-bottom: 24px; display: flex; gap: 16px; align-items: flex-start;">
                        <button class="btn-outline" onclick="app.closeProjectDetails()"
                            style="height: 32px; padding: 0 12px; font-size: 0.75rem; color: var(--text-muted);"><i
                                class="fa-solid fa-arrow-left"></i> Back to List</button>
                        <div>
                            <h2 id="pd-name"
                                style="color: var(--text-dark); font-weight: 800; font-size: 1.8rem; margin-bottom:4px; line-height: 1;">
                                Project Name</h2>
                            <p style="color: var(--text-muted); font-size: 0.9rem;"><i class="fa-solid fa-location-dot"
                                    style="color: var(--primary-hover);"></i>
                                <span id="pd-loc-display">Location</span>
                            </p>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">
                                <span id="pd-block-display" style="margin-right: 16px;"></span>
                                <span id="pd-lot-display" style="margin-right: 16px;"></span>
                                <span id="pd-foreman-display" style="margin-right: 16px;"></span>
                                <span id="pd-foreman2-display" style="margin-right: 16px;"></span>
                                <span id="pd-work-desc-display" style="margin-right: 16px;"></span>
                                <span id="pd-project-desc-display" style="margin-right: 16px;"></span>
                                <span id="pd-total-amount-display" style="margin-right: 16px;"></span>
                                <span id="pd-completion-display"></span>
                            </p>
                        </div>
                    </div>

                    <div class="proj-tabs">
                        <div class="proj-tab active" id="tab-progress" onclick="app.switchProjectTab('progress')"><i
                                class="fa-solid fa-list-check"></i>
                            Checklist</div>
                        <div class="proj-tab" id="tab-materials" onclick="app.switchProjectTab('materials')"><i
                                class="fa-solid fa-truck-ramp-box"></i> Material Issuance</div>
                        <div class="proj-tab" id="tab-manpower" onclick="app.switchProjectTab('manpower')"><i
                                class="fa-solid fa-users-gear"></i> Manpower Assignment</div>
                    </div>

                    <div id="ptab-progress" class="proj-section active">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div class="card"
                                style="padding: 16px 20px; flex: 1; margin-bottom: 0; margin-right: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 800; color: var(--text-dark); font-size: 0.95rem;">Overall
                                        Project Progress</span><span id="proj-progress-text"
                                        style="font-weight: 900; color: var(--text-dark); font-size: 1.2rem;">0%</span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar" id="proj-progress-bar" style="width: 0%;"></div>
                                </div>
                            </div>
                            <div id="add-category-container">
                                <button id="btn-add-cat" class="btn" onclick="app.showAddCategoryInput()"><i
                                        class="fa-solid fa-folder-plus"></i> Add Category</button>
                                <input type="text" id="input-add-cat" class="inline-input"
                                    style="display:none; height: 32px;" placeholder="New Phase/Category Name..."
                                    onblur="app.saveNewCategoryDB(this.value)"
                                    onkeydown="if(event.key==='Enter') this.blur()">
                            </div>
                        </div>
                        <div id="checklist-grid" class="checklist-grid"></div>
                    </div>

                    <div id="ptab-materials" class="proj-section">
                        <div class="quick-stats-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="stat-card" style="padding: 16px 20px;">
                                <div class="stat-details">
                                    <h3>Materials Issued (Total)</h3>
                                    <h2 id="proj-summary-qty">0 Items</h2>
                                </div>
                                <div class="stat-icon" style="background:#EFF6FF; color:#3B82F6;"><i
                                        class="fa-solid fa-boxes-stacked"></i></div>
                            </div>
                            <div class="stat-card" style="padding: 16px 20px;">
                                <div class="stat-details">
                                    <h3>Total Cost Allocation</h3>
                                    <h2 id="proj-summary-cost" style="color:var(--success);">₱0.00</h2>
                                </div>
                                <div class="stat-icon" style="background:var(--success-bg); color:var(--success);"><i
                                        class="fa-solid fa-peso-sign"></i></div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fa-solid fa-clipboard-list" style="color:var(--text-dark);"></i>
                                    Log
                                    Material Issuance</h3>
                            </div>
                            <div class="form-grid"
                                style="grid-template-columns: 2fr 1fr 1.5fr auto; align-items:center;">
                                <select id="issue-item">
                                    <option value="">Select Inventory Item</option>
                                </select>
                                <input type="number" id="issue-qty" placeholder="Qty">
                                <input type="text" id="issue-receiver" placeholder="Receiver Name">
                                <button class="btn" onclick="app.issueMaterial()"><i class="fa-solid fa-check"></i>
                                    Issue</button>
                            </div>
                            <div style="margin-top: 24px;">
                                <h4
                                    style="font-weight: 800; font-size: 1rem; margin-bottom:12px; color:var(--text-dark);">
                                    Issuance History</h4>
                                <div class="table-responsive">
                                    <table class="sheet-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Item Issued</th>
                                                <th>Quantity</th>
                                                <th>Total Cost</th>
                                                <th>Received By</th>
                                            </tr>
                                        </thead>
                                        <tbody id="issuance-history-content"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="ptab-manpower" class="proj-section">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fa-solid fa-users-gear" style="color:var(--text-dark);"></i>
                                    Category
                                    Assignments</h3>
                                <p>Assign workers to checklist categories. Completed tasks auto-sync to their
                                    Payroll
                                    Breakdown.</p>
                            </div>
                            <div class="form-grid" style="grid-template-columns: 1fr 1fr auto; align-items:center;">
                                <select id="assign-category">
                                    <option value="">Select Category/Phase</option>
                                </select>
                                <select id="assign-worker">
                                    <option value="">Select Worker</option>
                                </select>
                                <button class="btn" onclick="app.assignWorkerToCategory()"><i
                                        class="fa-solid fa-link"></i> Assign Person</button>
                            </div>
                            <div style="margin-top: 24px;">
                                <div class="table-responsive">
                                    <table class="sheet-table">
                                        <thead>
                                            <tr>
                                                <th>Checklist Category</th>
                                                <th>Assigned Personnel</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="assignments-content"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="mod-materials" class="module">
                <div class="quick-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-details">
                            <h3>Active Suppliers</h3>
                            <h2 id="stat-active-suppliers">0</h2>
                        </div>
                        <div class="stat-icon" style="background:#D1FAE5; color:#10B981;"><i
                                class="fa-solid fa-truck-fast"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-details">
                            <h3>Pending Deliveries</h3>
                            <h2 id="stat-pending-deliveries">0</h2>
                        </div>
                        <div class="stat-icon" style="background:#FEF3C7; color:#F59E0B;"><i
                                class="fa-solid fa-clock-rotate-left"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-details">
                            <h3>Low Stock Alerts</h3>
                            <h2 id="stat-low-stock">0 Items</h2>
                        </div>
                        <div class="stat-icon" style="background:#FEE2E2; color:#EF4444;"><i
                                class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                </div>
                <div class="card" style="padding-top: 10px;">
                    <div class="proj-tabs">
                        <div class="proj-tab active" id="tab-mat-suppliers" onclick="app.switchMatTab('suppliers')"><i
                                class="fa-solid fa-address-book"></i>
                            Suppliers</div>
                        <div class="proj-tab" id="tab-mat-inventory" onclick="app.switchMatTab('inventory')"><i
                                class="fa-solid fa-boxes-stacked"></i> Inventory</div>
                    </div>
                    <div id="mtab-suppliers" class="proj-section active">
                        <!-- Summary Cards -->
                        <div class="ms-summary-grid">
                            <div class="ms-summary-card">
                                <div class="ms-summary-value" id="ms-total-suppliers">0</div>
                                <div class="ms-summary-label">Total Suppliers</div>
                            </div>
                            <div class="ms-summary-card ms-card-active">
                                <div class="ms-summary-value" id="ms-active-suppliers">0</div>
                                <div class="ms-summary-label">Active</div>
                            </div>
                            <div class="ms-summary-card ms-card-inactive">
                                <div class="ms-summary-value" id="ms-inactive-suppliers">0</div>
                                <div class="ms-summary-label">Inactive</div>
                            </div>
                            <div class="ms-summary-card ms-card-preferred">
                                <div class="ms-summary-value" id="ms-preferred-suppliers">0</div>
                                <div class="ms-summary-label">Preferred</div>
                            </div>
                            <div class="ms-summary-card ms-card-blacklisted">
                                <div class="ms-summary-value" id="ms-blacklisted-suppliers">0</div>
                                <div class="ms-summary-label">Blacklisted</div>
                            </div>
                        </div>

                        <!-- Form Card -->
                        <div class="card" style="margin-top:15px;">
                            <div class="card-header">
                                <h3><i class="fa-solid fa-truck-field"></i> Material Supplier Record</h3>
                                <p>Add or update supplier information.</p>
                            </div>
                            <input type="hidden" id="ms-id">

                            <!-- Section 1: Supplier Information -->
                            <div class="ms-section">
                                <div class="ms-section-title"><i class="fa-solid fa-building"></i> Supplier Information</div>
                                <div class="ms-field-grid">
                                    <div class="ms-field-group ms-field-half">
                                        <label class="ms-field-label">Supplier Name <span class="required">*</span></label>
                                        <input type="text" id="ms-name" placeholder="e.g. BuildRight Hardware">
                                    </div>
                                    <div class="ms-field-group ms-field-half">
                                        <label class="ms-field-label">Contact Person</label>
                                        <input type="text" id="ms-contact-person" placeholder="e.g. Juan Dela Cruz">
                                    </div>
                                    <div class="ms-field-group ms-field-half">
                                        <label class="ms-field-label">Contact Number</label>
                                        <input type="text" id="ms-contact" placeholder="0917-XXX-XXXX">
                                    </div>
                                    <div class="ms-field-group ms-field-half">
                                        <label class="ms-field-label">Email</label>
                                        <input type="email" id="ms-email" placeholder="contact@supplier.com">
                                    </div>
                                    <div class="ms-field-group ms-field-full">
                                        <label class="ms-field-label">Address</label>
                                        <textarea id="ms-address" placeholder="e.g. 123 Main St, City" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Material Details -->
                            <div class="ms-section">
                                <div class="ms-section-title"><i class="fa-solid fa-boxes"></i> Material Details</div>
                                <div class="ms-field-grid">
                                    <div class="ms-field-group ms-field-half">
                                        <label class="ms-field-label">Material Category</label>
                                        <input type="text" id="ms-material-category" placeholder="e.g. Construction Materials">
                                    </div>
                                    <div class="ms-field-group ms-field-half">
                                        <label class="ms-field-label">Supplied Materials</label>
                                        <input type="text" id="ms-materials" placeholder="e.g. Cement, Rebars, Gravel">
                                    </div>
                                    <div class="ms-field-group ms-field-half">
                                        <label class="ms-field-label">Price Quote (₱)</label>
                                        <input type="text" id="ms-price-quote" placeholder="0.00" oninput="app.formatPriceQuote(this)">
                                    </div>
                                    <div class="ms-field-group ms-field-half">
                                        <label class="ms-field-label">Payment Terms</label>
                                        <input type="text" id="ms-payment-terms" placeholder="e.g. 30 Days, COD">
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Status / Notes -->
                            <div class="ms-section">
                                <div class="ms-section-title"><i class="fa-solid fa-gear"></i> Status / Notes</div>
                                <div class="ms-field-grid">
                                    <div class="ms-field-group ms-field-quarter">
                                        <label class="ms-field-label">Status</label>
                                        <select id="ms-status">
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                            <option value="Preferred">Preferred</option>
                                            <option value="Blacklisted">Blacklisted</option>
                                        </select>
                                    </div>
                                    <div class="ms-field-group ms-field-threequarter">
                                        <label class="ms-field-label">Remarks</label>
                                        <textarea id="ms-remarks" placeholder="Optional notes" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="ms-buttons">
                                <button type="button" class="btn-outline" id="ms-cancel-btn" onclick="app.cancelEditSupplierRecord()" style="display:none;">Cancel</button>
                                <button type="button" class="btn" onclick="app.saveSupplierRecord()"><i class="fa-solid fa-floppy-disk"></i> <span id="ms-submit-text">Add Supplier</span></button>
                            </div>
                        </div>

                        <!-- Search + Bulk -->
                        <div class="card" style="margin-top:15px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                                <div style="display:flex; align-items:center; gap:8px; flex:1;">
                                    <i class="fa-solid fa-search" style="color:var(--text-muted);"></i>
                                    <input type="text" id="search-suppliers" placeholder="Search suppliers..." style="flex:1; min-width:200px; padding:8px 12px; border:1px solid var(--border-color); border-radius:6px;">
                                </div>
                                <button type="button" class="btn-outline" onclick="app.openBulkAdd('suppliers')" style="white-space:nowrap;">
                                    <i class="fa-solid fa-layer-group"></i> Bulk Add
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="card" style="margin-top:15px;">
                            <div class="table-responsive">
                                <table class="sheet-table" id="table-suppliers">
                                    <thead>
                                        <tr>
                                            <th class="ms-col-name">Supplier Name</th>
                                            <th class="ms-col-person">Contact Person</th>
                                            <th class="ms-col-contact">Contact No.</th>
                                            <th class="ms-col-email">Email</th>
                                            <th class="ms-col-category">Material Category</th>
                                            <th class="ms-col-materials">Supplied Materials</th>
                                            <th class="ms-col-quote">Price Quote</th>
                                            <th class="ms-col-terms">Payment Terms</th>
                                            <th class="ms-col-status">Status</th>
                                            <th class="ms-col-remarks">Remarks</th>
                                            <th class="ms-col-action">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="suppliers-content"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div id="mtab-inventory" class="proj-section">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <div>
                                <h3 style="color: var(--text-dark); font-weight: 800; font-size: 1.1rem;">Site
                                    Inventory
                                </h3>
                            </div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <button type="button" class="btn-outline" onclick="app.openBulkAdd('inventory')">
                                    <i class="fa-solid fa-layer-group"></i> Bulk Add
                                </button>

                                <button type="button" class="btn" onclick="app.openModal('modal-add-stock')">
                                    <i class="fa-solid fa-box-open"></i> Add Stock
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="sheet-table" id="table-inventory">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Unit Cost</th>
                                        <th>Preferred Supplier</th>
                                    </tr>
                                </thead>
                                <tbody id="inventory-content"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <section id="mod-users" class="module">
                <!-- FORM CARD -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-address-card"></i> Manpower Record</h3>
                        <p>Add or update worker information.</p>
                    </div>

                    <input type="hidden" id="man-id" value="">

                    <div class="man-form">
                        <div class="man-section">
                            <div class="man-section-title">
                                <i class="fa-solid fa-user"></i> Worker Details
                            </div>
                            <div class="man-field-grid">
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-name">
                                        Full Name <span class="required">*</span>
                                    </label>
                                    <input type="text" id="man-name" placeholder="e.g. Juan Dela Cruz" required>
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-skills">
                                        Skill / Category <span class="required">*</span>
                                    </label>
                                    <div style="display:flex; gap:6px; align-items:start; width:100%;">
                                        <select id="man-skills" style="flex:1;" onchange="app.handleSkillChange(this.value)">
                                            <option value="">Select Skill / Folder</option>
                                        </select>
                                        <input type="text" id="man-skills-new" placeholder="New skill name"
                                            style="display:none; width:160px; flex:none;">
                                    </div>
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-pos">
                                        Position <span class="required">*</span>
                                    </label>
                                    <div style="display:flex; gap:6px; align-items:start; width:100%;">
                                        <select id="man-pos" style="flex:1;" onchange="app.handlePosChange(this.value)">
                                            <option value="">Select Position</option>
                                            <option value="Worker">Worker</option>
                                            <option value="Foreman">Foreman</option>
                                            <option value="Lead">Lead</option>
                                            <option value="Engineer">Engineer</option>
                                            <option value="In-Charge">In-Charge</option>
                                            <option value="ADD_NEW" style="font-weight:800;color:var(--primary-hover);">+ Add New Position</option>
                                        </select>
                                        <input type="text" id="man-pos-new" placeholder="New position"
                                            style="display:none; width:160px; flex:none;">
                                    </div>
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-salary">
                                        Daily Rate (₱) <span class="required">*</span>
                                    </label>
                                    <input type="number" id="man-salary" placeholder="0.00" step="0.01" required>
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-contact">
                                        Contact Number
                                    </label>
                                    <input type="text" id="man-contact" placeholder="e.g. 09171234567">
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-address">
                                        Address
                                    </label>
                                    <input type="text" id="man-address" placeholder="e.g. Brgy. San Jose, City">
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-project">
                                        Project Site / NTP
                                    </label>
                                    <select id="man-project">
                                        <option value="">Select Existing Project Site / NTP (Optional)</option>
                                    </select>
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-project-text">
                                        Or Type Project Site Manually
                                    </label>
                                    <input type="text" id="man-project-text" placeholder="Type project/site name if not yet created">
                                    <small style="display:block; color:var(--text-muted); font-size:0.7rem; margin-top:2px;">Use manual input only if the project is not yet created.</small>
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-foreman">
                                        Assigned Foreman
                                    </label>
                                    <select id="man-foreman">
                                        <option value="">No Foreman Assigned</option>
                                    </select>
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-status">
                                        Status
                                    </label>
                                    <select id="man-status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="man-field-group">
                                    <label class="man-field-label" for="man-photo">
                                        Photo / Bio Data
                                    </label>
                                    <input type="file" id="man-photo" accept="image/jpeg,image/png,image/webp">
                                    <div class="man-photo-preview" id="man-photo-preview">
                                        <img id="man-photo-img" src="" alt="Photo preview" style="display:none;">
                                        <div class="man-photo-placeholder" id="man-photo-placeholder">
                                            <i class="fa-solid fa-camera"></i>
                                            <span>No photo selected</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="man-buttons">
                            <button type="button" class="btn-outline" id="man-cancel-btn" style="display: none;"
                                onclick="app.cancelEditManpower()">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                            <button type="button" class="btn-outline" onclick="app.openBulkAdd('manpower')">
                                <i class="fa-solid fa-users"></i> Bulk Add
                            </button>
                            <button type="button" class="btn" onclick="app.addManpower()">
                                <i class="fa-solid fa-check"></i> <span id="man-submit-text">Add Record</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- BROWSE / SEARCH CARD -->
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                        <h3 style="font-weight:800; font-size:1.1rem;"><i class="fa-solid fa-magnifying-glass"></i> Browse Records</h3>
                        <div style="position:relative; width:min(300px,100%);">
                            <i class="fa-solid fa-magnifying-glass"
                                style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); pointer-events:none;"></i>
                            <input type="text" id="search-manpower" placeholder="Search by name, skill, or foreman..."
                                style="width:100%; padding-left:34px; border-radius:6px; border:1px solid var(--border); height:34px; font-size:13px;">
                        </div>
                    </div>

                    <!-- Tab toggle -->
                    <div class="man-tabs">
                        <button class="man-tab active" data-view="foreman" onclick="app.switchManpowerView('foreman')">
                            <i class="fa-solid fa-people-group"></i> By Foreman
                        </button>
                        <button class="man-tab" data-view="skill" onclick="app.switchManpowerView('skill')">
                            <i class="fa-solid fa-tags"></i> By Skill
                        </button>
                    </div>

                    <!-- Foreman Groups View -->
                    <div id="manpower-foreman-view">
                        <div id="foreman-groups-grid" class="man-grid"></div>
                    </div>

                    <!-- Skill Categories View -->
                    <div id="manpower-skills-view" style="display: none;">
                        <div id="skill-folders-grid" class="quick-stats-grid"></div>
                    </div>
                </div>

                <!-- TABLE VIEW (workers under a foreman or skill) -->
                <div id="manpower-table-view" style="display: none;">
                    <div class="card">
                        <button class="btn-outline" onclick="app.backToManpowerView()" style="margin-bottom: 16px;">
                            <i class="fa-solid fa-arrow-left"></i> Back to Groups
                        </button>
                        <h3 id="current-manpower-title"
                            style="margin-bottom: 16px; color: var(--text-dark); font-weight:800; font-size:1.3rem;">
                            Workers</h3>
                        <div class="table-responsive">
                            <table class="sheet-table" id="table-users">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Project Assigned</th>
                                        <th>Skills</th>
                                        <th>Position</th>
                                        <th>Rate</th>
                                        <th>Contact</th>
                                        <th>Foreman</th>
                                        <th>Bio Data</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>



            <section id="mod-bill_of_materials" class="module">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-receipt"></i> Bill of Materials (BOM)</h3>
                        <p>Record planned/required materials for each project site.</p>
                    </div>

                    <input type="hidden" id="bom-id" value="">

                    <div class="bom-form">

                        <!-- SECTION: Project Reference -->
                        <div class="bom-section">
                            <div class="bom-section-title">
                                <i class="fa-solid fa-city"></i> Project Reference
                            </div>
                            <div class="bom-field-grid">
                                <div class="bom-field-group bom-field-full">
                                    <label class="bom-field-label" for="bom-project">
                                        Project Site / NTP <span class="required">*</span>
                                    </label>
                                    <select id="bom-project" required>
                                        <option value="">Select Project Site / NTP</option>
                                    </select>
                                </div>
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-award-cost">
                                        Award Cost Reference (Optional)
                                    </label>
                                    <select id="bom-award-cost">
                                        <option value="">Select Award Cost (Optional)</option>
                                    </select>
                                </div>
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-award-cost-text">
                                        Or enter manually
                                    </label>
                                    <input type="text" id="bom-award-cost-text" placeholder="Type Award Cost if not listed">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Material Details -->
                        <div class="bom-section">
                            <div class="bom-section-title">
                                <i class="fa-solid fa-cubes"></i> Material Details
                            </div>
                            <div class="bom-field-grid">
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-material-name">
                                        Material Name <span class="required">*</span>
                                    </label>
                                    <input type="text" id="bom-material-name" placeholder="e.g. Portland Cement" required>
                                </div>
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-quantity">
                                        Quantity <span class="required">*</span>
                                    </label>
                                    <input type="number" id="bom-quantity" class="bom-number-input" min="0.01" step="0.01" placeholder="0.00" required
                                        oninput="app.calcBOMTotal()">
                                </div>
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-unit">
                                        Unit <span class="required">*</span>
                                    </label>
                                    <input type="text" id="bom-unit" placeholder="e.g. bags, pcs, cu.m" required>
                                </div>
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-unit-cost">
                                        Unit Cost (₱) <span class="required">*</span>
                                    </label>
                                    <input type="text" id="bom-unit-cost" class="bom-number-input" placeholder="0.00" required
                                        oninput="app.calcBOMTotal()">
                                </div>
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-total-cost">
                                        Total Cost (₱) <span class="bom-auto-badge">Auto</span>
                                    </label>
                                    <input type="text" id="bom-total-cost" class="bom-readonly" placeholder="Auto-computed" readonly>
                                </div>
                                <div class="bom-field-group bom-field-full">
                                    <label class="bom-field-label" for="bom-description">
                                        Description
                                    </label>
                                    <textarea id="bom-description" placeholder="Optional description of the material" style="min-height: 60px;"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Supplier / Notes -->
                        <div class="bom-section">
                            <div class="bom-section-title">
                                <i class="fa-solid fa-truck"></i> Supplier / Notes
                            </div>
                            <div class="bom-field-grid">
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-supplier">
                                        Supplier Name (Optional)
                                    </label>
                                    <input type="text" id="bom-supplier" placeholder="e.g. ACME Construction Supply">
                                </div>
                                <div class="bom-field-group">
                                    <label class="bom-field-label" for="bom-remarks">
                                        Remarks
                                    </label>
                                    <input type="text" id="bom-remarks" placeholder="Optional notes">
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="bom-buttons">
                            <button type="button" class="btn-outline" id="bom-cancel-btn" style="display: none;"
                                onclick="app.cancelEditBOM()">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                            <button type="button" class="btn" onclick="app.addBOMItem()">
                                <i class="fa-solid fa-plus"></i> <span id="bom-submit-text">Add Record</span>
                            </button>
                        </div>

                    </div>
                </div>

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="font-weight:800; font-size:1.1rem;"><i class="fa-solid fa-table-list"></i> Bill of Materials Records</h3>
                        <div style="position: relative; width: min(300px, 100%);">
                            <i class="fa-solid fa-magnifying-glass"
                                style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                            <input type="text" id="search-bom" placeholder="Search by material, project, award cost..."
                                style="width: 100%; padding-left: 34px; border-radius: 6px; border: 1px solid var(--border); height: 34px; font-size: 13px;">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="sheet-table" id="table-bom">
                            <thead>
                                <tr>
                                    <th class="bom-col-project">Project / Site</th>
                                    <th class="bom-col-code">Award Cost Ref</th>
                                    <th class="bom-col-material">Material Name</th>
                                    <th class="bom-col-qty">Qty</th>
                                    <th class="bom-col-unit">Unit</th>
                                    <th class="bom-col-cost">Unit Cost (₱)</th>
                                    <th class="bom-col-cost">Total Cost (₱)</th>
                                    <th class="bom-col-supplier">Supplier</th>
                                    <th class="bom-col-remarks">Remarks</th>
                                    <th class="bom-col-action">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="mod-billing_progress" class="module">

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-chart-line"></i> Billing Progress</h3>
                        <p>Monitor payments collected per project.</p>
                    </div>

                    <input type="hidden" id="bp-id" value="">

                    <div class="bp-form">

                        <!-- SECTION: Project Billing Reference -->
                        <div class="bp-section">
                            <div class="bp-section-title">
                                <i class="fa-solid fa-city"></i> Project Billing Reference
                            </div>
                            <div class="bp-field-grid">
                                <div class="bp-field-group bp-field-full">
                                    <label class="bp-field-label" for="bp-project">
                                        Project Site / NTP <span class="required">*</span>
                                    </label>
                                    <select id="bp-project" required>
                                        <option value="">Select Project Site / NTP</option>
                                    </select>
                                </div>
                                <div class="bp-field-group">
                                    <label class="bp-field-label" for="bp-award-cost">
                                        Award Cost / Service Agreement (Optional)
                                    </label>
                                    <select id="bp-award-cost">
                                        <option value="">No Award Cost / Service Agreement</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Billing Entry -->
                        <div class="bp-section">
                            <div class="bp-section-title">
                                <i class="fa-solid fa-file-invoice"></i> Billing Entry
                            </div>
                            <div class="bp-field-grid">
                                <div class="bp-field-group">
                                    <label class="bp-field-label" for="bp-date">
                                        Billing Date <span class="required">*</span>
                                    </label>
                                    <input type="date" id="bp-date" required>
                                </div>
                                <div class="bp-field-group">
                                    <label class="bp-field-label" for="bp-ref-no">
                                        Billing Reference No.
                                    </label>
                                    <input type="text" id="bp-ref-no" placeholder="e.g. INV-2026-001">
                                </div>
                                <div class="bp-field-group bp-field-full">
                                    <label class="bp-field-label" for="bp-description">
                                        Billing Description <span class="required">*</span>
                                    </label>
                                    <textarea id="bp-description" placeholder="Describe this billing entry" style="min-height: 60px;" required></textarea>
                                </div>
                                <div class="bp-field-group">
                                    <label class="bp-field-label" for="bp-amount-billed">
                                        Amount Billed (₱) <span class="required">*</span>
                                    </label>
                                    <input type="text" id="bp-amount-billed" placeholder="0.00" required
                                        oninput="app.calcBillingTotal()">
                                </div>
                                <div class="bp-field-group">
                                    <label class="bp-field-label" for="bp-amount-collected">
                                        Amount Collected (₱)
                                    </label>
                                    <input type="text" id="bp-amount-collected" placeholder="0.00"
                                        oninput="app.calcBillingTotal()">
                                </div>
                                <div class="bp-field-group">
                                    <label class="bp-field-label" for="bp-payment-method">
                                        Payment Method
                                    </label>
                                    <select id="bp-payment-method">
                                        <option value="">Select method</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Check">Check</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="bp-field-group">
                                    <label class="bp-field-label" for="bp-status">
                                        Status <span class="required">*</span>
                                    </label>
                                    <select id="bp-status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Partially Collected">Partially Collected</option>
                                        <option value="Collected">Collected</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Summary -->
                        <div class="bp-section">
                            <div class="bp-section-title">
                                <i class="fa-solid fa-calculator"></i> Billing Summary
                            </div>
                            <div class="bp-summary-grid" id="bp-summary-grid">
                                <div class="bp-summary-card">
                                    <span class="bp-summary-label">Total Award Amount</span>
                                    <span class="bp-summary-value" id="bp-sum-award">₱0.00</span>
                                </div>
                                <div class="bp-summary-card">
                                    <span class="bp-summary-label">Total Billed</span>
                                    <span class="bp-summary-value" id="bp-sum-billed">₱0.00</span>
                                </div>
                                <div class="bp-summary-card">
                                    <span class="bp-summary-label">Total Collected</span>
                                    <span class="bp-summary-value" id="bp-sum-collected">₱0.00</span>
                                </div>
                                <div class="bp-summary-card">
                                    <span class="bp-summary-label">Remaining Balance</span>
                                    <span class="bp-summary-value" id="bp-sum-balance">₱0.00</span>
                                </div>
                                <div class="bp-summary-card bp-summary-wide">
                                    <span class="bp-summary-label">Collection Progress</span>
                                    <div class="bp-progress-bar-container">
                                        <div class="bp-progress-bar" id="bp-progress-bar" style="width:0%;"></div>
                                    </div>
                                    <span class="bp-summary-value" id="bp-sum-percent">0%</span>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Remarks -->
                        <div class="bp-section">
                            <div class="bp-section-title">
                                <i class="fa-solid fa-note-sticky"></i> Remarks
                            </div>
                            <div class="bp-field-grid">
                                <div class="bp-field-group bp-field-full">
                                    <label class="bp-field-label" for="bp-remarks">
                                        Remarks
                                    </label>
                                    <textarea id="bp-remarks" placeholder="Optional notes" style="min-height: 50px;"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="bp-buttons">
                            <button type="button" class="btn-outline" id="bp-cancel-btn" style="display: none;"
                                onclick="app.cancelEditBillingRecord()">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                            <button type="button" class="btn" onclick="app.addBillingRecord()">
                                <i class="fa-solid fa-plus"></i> <span id="bp-submit-text">Add Record</span>
                            </button>
                        </div>

                    </div>
                </div>

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="font-weight:800; font-size:1.1rem;"><i class="fa-solid fa-table-list"></i> Billing Records</h3>
                        <div style="position: relative; width: min(300px, 100%);">
                            <i class="fa-solid fa-magnifying-glass"
                                style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                            <input type="text" id="search-billing" placeholder="Search by project, reference, description..."
                                style="width: 100%; padding-left: 34px; border-radius: 6px; border: 1px solid var(--border); height: 34px; font-size: 13px;">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="sheet-table" id="table-billing">
                            <thead>
                                <tr>
                                    <th class="bp-col-date">Date</th>
                                    <th class="bp-col-project">Project / Site</th>
                                    <th class="bp-col-code">Service Agreement / Award Cost</th>
                                    <th class="bp-col-ref">Reference No.</th>
                                    <th class="bp-col-desc">Description</th>
                                    <th class="bp-col-amount">Amount Billed</th>
                                    <th class="bp-col-amount">Amount Collected</th>
                                    <th class="bp-col-status">Status</th>
                                    <th class="bp-col-remarks">Remarks</th>
                                    <th class="bp-col-action">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </section>

            <section id="mod-payroll" class="module">
                <div id="payroll-active-view">
                    <div class="card">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
                            <div>
                                <h3 style="color: var(--text-dark); font-weight: 800; font-size: 1.2rem;"><i
                                        class="fa-solid fa-file-invoice-dollar"
                                        style="color:var(--text-muted); margin-right:8px;"></i> Current Payroll
                                    Cycle
                                </h3>
                            </div>
                            <div style="display:flex; gap: 10px;">
                                <button class="btn-outline" onclick="app.viewPayrollHistory()"><i
                                        class="fa-solid fa-clock-rotate-left"></i> History</button>
                                <button class="btn-success-solid btn" onclick="app.resetDatabasePayroll()"><i
                                        class="fa-solid fa-check-double"></i> Close Cycle</button>
                            </div>
                        </div>

                        <div class="form-grid"
                            style="grid-template-columns: 1fr 1.5fr 2fr 1fr 1fr; align-items:center; background: #F8FAFC;">
                            <input type="date" id="pay-date" title="Pay Period End Date">
                            <input type="text" id="pay-name" list="worker-names-list" placeholder="Search Worker Name">
                            <datalist id="worker-names-list"></datalist>
                            <input type="text" id="pay-job" list="pay-job-list" placeholder="Job/Unit Description">
                            <datalist id="pay-job-list"></datalist>
                            <input type="text" id="pay-award" placeholder="Award Cost (₱)"
                                oninput="app.formatCurrencyInput(this)">
                            <input type="text" id="pay-advance" placeholder="Cash Advance (₱)"
                                oninput="app.formatCurrencyInput(this)">
                            <div
                                style="grid-column: 1/-1; display:flex; justify-content:flex-end; gap:8px; margin-top:4px; flex-wrap:wrap;">
                                <button type="button" class="btn-outline" onclick="app.openBulkAdd('payroll')">
                                    <i class="fa-solid fa-layer-group"></i> Bulk Add
                                </button>

                                <button type="button" class="btn-outline" onclick="app.clearPayrollForm()">
                                    <i class="fa-solid fa-eraser"></i> Clear
                                </button>

                                <button type="button" class="btn-yellow-solid btn" onclick="app.addManualPayroll()">
                                    <i class="fa-solid fa-plus"></i> Add to Payslip
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive" style="overflow: visible;">
                            <table class="sheet-table" id="table-payroll">
                                <thead>
                                    <tr>
                                        <th>NAME</th>
                                        <th>JOB DESCRIPTION</th>
                                        <th>AWARD COST (₱)</th>
                                        <th style="width: 120px; text-align: center;">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="payroll-content"></tbody>
                                <tfoot style="background: #F3F4F6;">
                                    <tr>
                                        <td colspan="2"
                                            style="text-align: right; font-weight: 800; color: var(--text-muted);">
                                            TOTAL
                                            (₱):</td>
                                        <td style="font-weight: 800; color: var(--text-dark); font-size: 1.1rem;"
                                            id="payroll-total">₱0.00</td>
                                        <td style="text-align: center; color: var(--text-muted); font-weight: 600;"
                                            id="payroll-count">0 Worker(s)</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="payroll-history-view" style="display: none;">
                    <div class="card">
                        <button class="btn-outline" onclick="app.backToActivePayroll()" style="margin-bottom: 24px;"><i
                                class="fa-solid fa-arrow-left"></i> Back to
                            Current</button>
                        <h3 style="margin-bottom: 24px; color: var(--text-dark); font-weight:800; font-size:1.2rem;">
                            <i class="fa-solid fa-box-archive"></i> Payroll History (Last 12 Months)
                        </h3>
                        <div class="table-responsive" style="overflow: visible;">
                            <table class="sheet-table" id="table-payroll-history">
                                <thead>
                                    <tr>
                                        <th>WORKER NAME</th>
                                        <th>TOTAL CYCLES</th>
                                        <th>TOTAL HISTORICAL PAYOUT (₱)</th>
                                        <th style="width: 120px; text-align: center;">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="payroll-history-content"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-users-gear"></i> Payroll Entries (Manpower / Subcon)</h3>
                        <p>Record manual payroll for both regular workers and subcontractors.</p>
                    </div>

                    <input type="hidden" id="pe-id" value="">

                    <div class="pe-form">

                        <!-- SECTION: Payroll Reference -->
                        <div class="pe-section">
                            <div class="pe-section-title">
                                <i class="fa-solid fa-briefcase"></i> Payroll Reference
                            </div>
                            <div class="pe-field-grid">
                                <div class="pe-field-group pe-field-half">
                                    <label class="pe-field-label" for="pe-project">
                                        Project Site / NTP <span class="required">*</span>
                                    </label>
                                    <select id="pe-project" required>
                                        <option value="">Select Project</option>
                                    </select>
                                </div>
                                <div class="pe-field-group pe-field-half">
                                    <label class="pe-field-label" for="pe-type">
                                        Payroll Type <span class="required">*</span>
                                    </label>
                                    <select id="pe-type" required onchange="app.togglePayrollType()">
                                        <option value="Manpower">Manpower</option>
                                        <option value="Subcon">Subcon</option>
                                    </select>
                                </div>
                                <div class="pe-field-group" id="pe-worker-group">
                                    <label class="pe-field-label" for="pe-worker">
                                        Worker / Manpower
                                    </label>
                                    <select id="pe-worker" onchange="app.onWorkerSelect()">
                                        <option value="">Select Worker</option>
                                    </select>
                                </div>
                                <div class="pe-field-group" id="pe-foreman-group">
                                    <label class="pe-field-label" for="pe-foreman">
                                        Foreman
                                    </label>
                                    <input type="text" id="pe-foreman" placeholder="Foreman name">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Payee Details -->
                        <div class="pe-section">
                            <div class="pe-section-title">
                                <i class="fa-solid fa-user"></i> Payee Details
                            </div>
                            <div class="pe-field-grid">
                                <div class="pe-field-group pe-field-half">
                                    <label class="pe-field-label" for="pe-payee-name">
                                        Payee Name <span class="required">*</span>
                                    </label>
                                    <input type="text" id="pe-payee-name" placeholder="Full name" required>
                                </div>
                                <div class="pe-field-group pe-field-half">
                                    <label class="pe-field-label" for="pe-position">
                                        Position / Role
                                    </label>
                                    <input type="text" id="pe-position" placeholder="e.g. Mason">
                                </div>
                                <div class="pe-field-group" id="pe-skill-group">
                                    <label class="pe-field-label" for="pe-skill">
                                        Skill
                                    </label>
                                    <input type="text" id="pe-skill" placeholder="e.g. CHB Laying">
                                </div>
                                <!-- Subcon fields (hidden by default) -->
                                <div class="pe-field-group pe-subcon-field" style="display:none;">
                                    <label class="pe-field-label" for="pe-subcon-company">
                                        Subcon Company
                                    </label>
                                    <input type="text" id="pe-subcon-company" placeholder="Company name">
                                </div>
                                <div class="pe-field-group pe-field-full pe-subcon-field" style="display:none;">
                                    <label class="pe-field-label" for="pe-subcon-scope">
                                        Subcon Scope / Work Description
                                    </label>
                                    <textarea id="pe-subcon-scope" placeholder="Scope of work" style="min-height:50px;"></textarea>
                                </div>
                                <div class="pe-field-group pe-subcon-field" style="display:none;">
                                    <label class="pe-field-label" for="pe-subcon-ref">
                                        Subcon Reference No.
                                    </label>
                                    <input type="text" id="pe-subcon-ref" placeholder="e.g. SUB-001">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Pay Period & Amount -->
                        <div class="pe-section">
                            <div class="pe-section-title">
                                <i class="fa-solid fa-calendar"></i> Pay Period & Amount
                            </div>
                            <div class="pe-field-grid">
                                <div class="pe-field-group">
                                    <label class="pe-field-label" for="pe-period-start">
                                        Period Start <span class="required">*</span>
                                    </label>
                                    <input type="date" id="pe-period-start" required>
                                </div>
                                <div class="pe-field-group">
                                    <label class="pe-field-label" for="pe-period-end">
                                        Period End <span class="required">*</span>
                                    </label>
                                    <input type="date" id="pe-period-end" required>
                                </div>
                                <div class="pe-field-group pe-field-full">
                                    <label class="pe-field-label" for="pe-amount">
                                        Amount / Net Pay (₱) <span class="required">*</span>
                                    </label>
                                    <input type="number" id="pe-amount" placeholder="0.00" min="0" step="0.01" style="font-weight:700;">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Payment Details -->
                        <div class="pe-section">
                            <div class="pe-section-title">
                                <i class="fa-solid fa-credit-card"></i> Payment Details
                            </div>
                            <div class="pe-field-grid">
                                <div class="pe-field-group">
                                    <label class="pe-field-label" for="pe-payment-method">
                                        Payment Method
                                    </label>
                                    <select id="pe-payment-method">
                                        <option value="">Select method</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Check">Check</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="pe-field-group">
                                    <label class="pe-field-label" for="pe-status">
                                        Payroll Status <span class="required">*</span>
                                    </label>
                                    <select id="pe-status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Paid">Paid</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="pe-field-group pe-field-full">
                                    <label class="pe-field-label" for="pe-remarks">
                                        Remarks
                                    </label>
                                    <textarea id="pe-remarks" placeholder="Optional notes" style="min-height:50px;"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="pe-buttons">
                            <button type="button" class="btn-outline" id="pe-cancel-btn" style="display: none;"
                                onclick="app.cancelEditPayrollEntry()">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                            <button type="button" class="btn" onclick="app.addPayrollEntry()">
                                <i class="fa-solid fa-plus"></i> <span id="pe-submit-text">Add Payroll Entry</span>
                            </button>
                        </div>

                    </div>
                </div>

                <div class="card" style="margin-top: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="font-weight:800; font-size:1.1rem;"><i class="fa-solid fa-table-list"></i> Payroll Entries</h3>
                        <div style="position: relative; width: min(300px, 100%);">
                            <i class="fa-solid fa-magnifying-glass"
                                style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                            <input type="text" id="search-payroll-entries" placeholder="Search by project, payee, type..."
                                style="width: 100%; padding-left: 34px; border-radius: 6px; border: 1px solid var(--border); height: 34px; font-size: 13px;">
                        </div>
                    </div>
                    <!-- Summary cards -->
                    <div class="pe-summary-grid" id="pe-summary-grid" style="margin-bottom:16px;">
                        <div class="pe-summary-card">
                            <span class="pe-summary-label">Total Payroll</span>
                            <span class="pe-summary-value" id="pe-sum-gross">₱0.00</span>
                        </div>
                        <div class="pe-summary-card">
                            <span class="pe-summary-label">Paid</span>
                            <span class="pe-summary-value" id="pe-sum-paid">₱0.00</span>
                        </div>
                        <div class="pe-summary-card">
                            <span class="pe-summary-label">Pending</span>
                            <span class="pe-summary-value" id="pe-sum-pending">₱0.00</span>
                        </div>
                        <div class="pe-summary-card">
                            <span class="pe-summary-label">Manpower</span>
                            <span class="pe-summary-value" id="pe-sum-manpower-count">0</span>
                        </div>
                        <div class="pe-summary-card">
                            <span class="pe-summary-label">Subcon</span>
                            <span class="pe-summary-value" id="pe-sum-subcon-count">0</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="sheet-table" id="table-payroll-entries">
                            <thead>
                                <tr>
                                    <th class="pe-col-type">Type</th>
                                    <th class="pe-col-project">Project / Site</th>
                                    <th class="pe-col-payee">Payee Name</th>
                                    <th class="pe-col-worker">Worker</th>
                                    <th class="pe-col-foreman">Foreman</th>
                                    <th class="pe-col-period">Period</th>
                                    <th class="pe-col-amount">Amount / Net Pay</th>
                                    <th class="pe-col-method">Payment</th>
                                    <th class="pe-col-status">Status</th>
                                    <th class="pe-col-remarks">Remarks</th>
                                    <th class="pe-col-action">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </section>

            <section id="mod-cash_release" class="module">

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-hand-holding-dollar"></i> Cash Release</h3>
                        <p>Record outgoing cash transactions.</p>
                    </div>

                    <!-- Summary Cards -->
                    <div class="cr-summary-row">
                        <div class="cr-summary-card-sm">
                            <span class="cr-summary-label">Total Materials</span>
                            <span class="cr-summary-value" id="cr-total-materials">₱0.00</span>
                        </div>
                        <div class="cr-summary-card-sm">
                            <span class="cr-summary-label">Total Labor</span>
                            <span class="cr-summary-value" id="cr-total-labor">₱0.00</span>
                        </div>
                        <div class="cr-summary-card-sm">
                            <span class="cr-summary-label">Other Expenses</span>
                            <span class="cr-summary-value" id="cr-total-other">₱0.00</span>
                        </div>
                        <div class="cr-summary-card-sm cr-summary-grand">
                            <span class="cr-summary-label">Grand Total</span>
                            <span class="cr-summary-value" id="cr-grand-total">₱0.00</span>
                        </div>
                    </div>

                    <input type="hidden" id="cr-id" value="">

                    <!-- Simple Log Form -->
                    <div class="cr-simple-form">
                        <div class="cr-simple-row">
                            <div class="cr-simple-field">
                                <label for="cr-date">Date</label>
                                <input type="date" id="cr-date">
                            </div>
                            <div class="cr-simple-field">
                                <label for="cr-category">Category</label>
                                <select id="cr-category">
                                    <option value="">Select</option>
                                    <option value="Materials">Material</option>
                                    <option value="Labor">Labor</option>
                                    <option value="Other Expenses">Other Expenses</option>
                                </select>
                            </div>
                            <div class="cr-simple-field">
                                <label for="cr-receiver">Receiver Name</label>
                                <input type="text" id="cr-receiver" placeholder="Receiver name">
                            </div>
                            <div class="cr-simple-field cr-simple-wide">
                                <label for="cr-particulars">Particulars / Description</label>
                                <input type="text" id="cr-particulars" placeholder="Describe the transaction">
                            </div>
                            <div class="cr-simple-field">
                                <label for="cr-amount">Amount (₱)</label>
                                <input type="number" id="cr-amount" placeholder="0.00" min="0.01" step="0.01">
                            </div>
                            <div class="cr-simple-field cr-simple-btn">
                                <label>&nbsp;</label>
                                <button type="button" class="btn-outline" id="cr-cancel-btn" style="display: none;" onclick="app.cancelEditCashRelease()">
                                    <i class="fa-solid fa-xmark"></i> Cancel
                                </button>
                                <button type="button" class="btn" onclick="app.addCashRelease()">
                                    <i class="fa-solid fa-plus"></i> <span id="cr-submit-text">Add Record</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="font-weight:800; font-size:1.1rem;"><i class="fa-solid fa-table-list"></i> Cash Release Log</h3>
                        <div style="position: relative; width: min(300px, 100%);">
                            <i class="fa-solid fa-magnifying-glass"
                                style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                            <input type="text" id="search-cash-releases" placeholder="Search by date, category, name..."
                                style="width: 100%; padding-left: 34px; border-radius: 6px; border: 1px solid var(--border); height: 34px; font-size: 13px;">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="sheet-table" id="table-cash-release">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Receiver Name</th>
                                    <th>Particulars</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="cash-release-content"></tbody>
                        </table>
                    </div>
                </div>

            </section>

            <section id="mod-global_ntp" class="module">
                <!-- Tab Buttons -->
                <div class="ntp-tabs">
                    <button class="ntp-tab active" data-tab="ntp" onclick="app.switchNTPTab('ntp')">
                        <i class="fa-solid fa-file-signature"></i> NTP Records
                    </button>
                    <button class="ntp-tab" data-tab="award_cost" onclick="app.switchNTPTab('award_cost')">
                        <i class="fa-solid fa-clipboard-list"></i> Award Cost
                    </button>
                </div>

                <!-- NTP Tab Content -->
                <div id="ntp-tab-ntp" class="ntp-tab-content" style="display:block">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-file-signature"></i> Notice to Proceed (NTP)</h3>
                            <p>Upload verified NTPs here to unlock a Pending project's execution phases.</p>
                        </div>
                        <div class="form-grid">
                            <select id="g-ntp-project">
                                <option value="">Select Pending Project</option>
                            </select>
                            <input type="text" id="g-ntp-ticket" placeholder="NTP Ticket">
                            <input type="date" id="g-ntp-date" title="NTP Date" required>
                            <input type="number" id="g-ntp-cost" placeholder="Award Cost (₱)">
                            <input type="date" id="g-ntp-due" title="Due Date" required>
                            <input type="date" id="g-ntp-accept" title="Acceptance Date">
                            <input type="date" id="g-ntp-completion" title="Completion Date">
                            <input type="text" id="g-ntp-work-desc" placeholder="Work Description (scope of work)">
                            <input type="text" id="g-ntp-project-desc" placeholder="Project Description">
                            <input type="number" id="g-ntp-total-amount" placeholder="Total Amount (₱)" step="0.01">
                            <label for="g-ntp-file" class="sr-only">NTP File</label>
                            <input type="file" id="g-ntp-file" accept=".pdf, image/*">
                            <div
                                style="grid-column: 1/-1; display:flex; justify-content:flex-end; gap:8px; flex-wrap:wrap;">
                                <button type="button" class="btn-outline" onclick="app.openBulkAdd('ntp')">
                                    <i class="fa-solid fa-layer-group"></i> Bulk Add
                                </button>

                                <button type="button" class="btn" onclick="app.uploadGlobalNTP()">
                                    <i class="fa-solid fa-upload"></i> Upload NTP
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="sheet-table" id="table-global-ntp">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Ticket</th>
                                        <th>NTP Date</th>
                                        <th>Award Cost</th>
                                        <th>Due Date</th>
                                        <th>Accept Date</th>
                                        <th>Completion Date</th>
                                        <th>Total Amount</th>
                                        <th>Document</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Award Cost Tab Content -->
                <div id="ntp-tab-award_cost" class="ntp-tab-content" style="display:none">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-clipboard-list"></i> Award Cost Entry</h3>
                            <p>Record project award costs with full details and supporting documents.</p>
                        </div>

                        <input type="hidden" id="awd-id" value="">

                        <div class="awd-form">

                            <!-- SECTION: Project Reference -->
                            <div class="awd-section">
                                <div class="awd-section-title">
                                    <i class="fa-solid fa-city"></i> Project Reference
                                </div>
                                <div class="awd-field-grid">
                                    <div class="awd-field-group awd-field-full">
                                        <label class="awd-field-label" for="awd-project">
                                            Project Site / NTP <span class="required">*</span>
                                        </label>
                                        <select id="awd-project" required>
                                            <option value="">Select Project Site / NTP Reference</option>
                                        </select>
                                    </div>
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-block">
                                            Block <span class="awd-readonly-badge">Auto</span>
                                        </label>
                                        <input type="text" id="awd-block" class="awd-readonly" placeholder="Auto-filled from project" readonly>
                                    </div>
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-lot">
                                            Lot <span class="awd-readonly-badge">Auto</span>
                                        </label>
                                        <input type="text" id="awd-lot" class="awd-readonly" placeholder="Auto-filled from project" readonly>
                                    </div>
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-location">
                                            Location <span class="awd-readonly-badge">Auto</span>
                                        </label>
                                        <input type="text" id="awd-location" class="awd-readonly" placeholder="Auto-filled from project" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Agreement Details -->
                            <div class="awd-section">
                                <div class="awd-section-title">
                                    <i class="fa-solid fa-file-contract"></i> Agreement Details
                                </div>
                                <div class="awd-field-grid">
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-service-code">
                                            Service Agreement Code No. <span class="required">*</span>
                                        </label>
                                        <input type="text" id="awd-service-code" placeholder="e.g. SAC-2026-001" required>
                                    </div>
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-item">
                                            Item <span class="required">*</span>
                                        </label>
                                        <input type="text" id="awd-item" placeholder="e.g. CHB, Rebar, Labor" required>
                                    </div>
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-unit">
                                            Unit <span class="required">*</span>
                                        </label>
                                        <input type="text" id="awd-unit" placeholder="e.g. sqm, pcs, lot" required>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Schedule and Amount -->
                            <div class="awd-section">
                                <div class="awd-section-title">
                                    <i class="fa-solid fa-calendar-days"></i> Schedule and Amount
                                </div>
                                <div class="awd-field-grid">
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-start">
                                            Start Date <span class="required">*</span>
                                        </label>
                                        <input type="date" id="awd-start" required>
                                    </div>
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-completion">
                                            Completion Date <span class="required">*</span>
                                        </label>
                                        <input type="date" id="awd-completion" required>
                                    </div>
                                    <div class="awd-field-group">
                                        <label class="awd-field-label" for="awd-total-amount">
                                            Total Amount (₱) <span class="required">*</span>
                                        </label>
                                        <input type="text" id="awd-total-amount" placeholder="0.00"
                                            oninput="app.formatCurrencyInput(this)" required>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Descriptions -->
                            <div class="awd-section">
                                <div class="awd-section-title">
                                    <i class="fa-solid fa-align-left"></i> Descriptions
                                </div>
                                <div class="awd-field-grid">
                                    <div class="awd-field-group awd-field-full">
                                        <label class="awd-field-label" for="awd-work-desc">
                                            Work Description <span class="required">*</span>
                                        </label>
                                        <textarea id="awd-work-desc" placeholder="Describe the scope of work covered by this award cost" style="min-height: 70px;" required></textarea>
                                    </div>
                                    <div class="awd-field-group awd-field-full">
                                        <label class="awd-field-label" for="awd-project-desc">
                                            Project Description <span class="required">*</span>
                                        </label>
                                        <textarea id="awd-project-desc" placeholder="Describe the project or unit this award cost applies to" style="min-height: 70px;" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Attachment -->
                            <div class="awd-section">
                                <div class="awd-section-title">
                                    <i class="fa-solid fa-paperclip"></i> Attachment
                                </div>
                                <div class="awd-field-grid">
                                    <div class="awd-field-group awd-field-full">
                                        <label class="awd-field-label" for="awd-attachment">
                                            Attach NTP / Supporting Document
                                        </label>
                                        <div class="awd-attachment-area">
                                            <label for="awd-attachment" class="awd-attachment-label">
                                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                                <span id="awd-file-name">Click to upload or drag file here</span>
                                            </label>
                                            <input type="file" id="awd-attachment" style="display: none;"
                                                accept=".pdf,.jpg,.jpeg,.png,.webp,.xls,.xlsx,.csv"
                                                onchange="app.handleAwardFileSelect(this)">
                                            <span class="awd-attachment-hint">Allowed: PDF, JPG, PNG, WebP, XLS, XLSX, CSV (Max 5MB)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="awd-buttons">
                                <button type="button" class="btn-outline" id="awd-cancel-btn" style="display: none;"
                                    onclick="app.cancelEditAwardCost()">
                                    <i class="fa-solid fa-xmark"></i> Cancel
                                </button>
                                <button type="button" class="btn-outline" onclick="app.openBulkAdd('award_costs')">
                                    <i class="fa-solid fa-layer-group"></i> Bulk Add
                                </button>
                                <button type="button" class="btn" onclick="app.addAwardCost()">
                                    <i class="fa-solid fa-plus"></i> <span id="awd-submit-text">Add Record</span>
                                </button>
                            </div>

                        </div>
                    </div>

                    <div class="card" style="margin-top:15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                            <h3 style="font-weight:800; font-size:1.1rem;"><i class="fa-solid fa-table-list"></i> Award Cost Records</h3>
                            <div style="position: relative; width: min(300px, 100%);">
                                <i class="fa-solid fa-magnifying-glass"
                                    style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                                <input type="text" id="search-award-costs" placeholder="Search by code, project, item..."
                                    style="width: 100%; padding-left: 34px; border-radius: 6px; border: 1px solid var(--border); height: 34px; font-size: 13px;">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="sheet-table" id="table-award-costs">
                                <thead>
                                    <tr>
                                        <th class="awd-col-code">Service Agreement Code</th>
                                        <th class="awd-col-project">Project / Site</th>
                                        <th class="awd-col-block">Block</th>
                                        <th class="awd-col-lot">Lot</th>
                                        <th class="awd-col-location">Location</th>
                                        <th class="awd-col-item">Item</th>
                                        <th class="awd-col-unit">Unit</th>
                                        <th class="awd-col-date">Start Date</th>
                                        <th class="awd-col-date">Completion Date</th>
                                        <th class="awd-col-amount">Total Amount (₱)</th>
                                        <th class="awd-col-attachment">Attachment</th>
                                        <th class="awd-col-action">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <div id="modal-add-supplier" class="modal-overlay" onclick="app.closeModalOnBackdrop(event, 'modal-add-supplier')"
        style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-truck-field" style="color: var(--primary-hover);"></i> Add New
                    Supplier</h3>
                <button class="modal-close" onclick="app.closeModal('modal-add-supplier')"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <div>
                    <label class="modal-label" for="new-sup-name">Supplier Name</label>
                    <input type="text" id="new-sup-name" placeholder="e.g. BuildRight Hardware">
                </div>

                <div>
                    <label class="modal-label" for="new-sup-materials">Items Provided</label>
                    <input type="text" id="new-sup-materials" placeholder="e.g. Cement, Rebars">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="modal-label" for="new-sup-contact">Contact Number</label>
                        <input type="text" id="new-sup-contact" placeholder="0917-XXX-XXXX">
                    </div>

                    <div>
                        <label class="modal-label" for="new-sup-email">Email</label>
                        <input type="email" id="new-sup-email" placeholder="contact@supplier.com">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-outline" onclick="app.closeModal('modal-add-supplier')">Cancel</button>
                <button class="btn" onclick="app.submitNewSupplier()">Save Supplier</button>
            </div>
        </div>
    </div>

    <div id="modal-add-stock" class="modal-overlay" onclick="app.closeModalOnBackdrop(event, 'modal-add-stock')"
        style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-box-open" style="color: var(--primary-hover);"></i> Add Stock Item
                </h3>
                <button class="modal-close" onclick="app.closeModal('modal-add-stock')"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <div>
                    <label class="modal-label" for="stock-name">Item Name</label>
                    <input type="text" id="stock-name" placeholder="e.g. Portland Cement">
                </div>

                <div>
                    <label class="modal-label" for="stock-category">Category</label>
                    <select id="stock-category" onchange="app.handleCategoryChange(this.value)"></select>
                    <input type="text" id="stock-category-new" placeholder="Type new category..."
                        style="display: none; margin-top: 8px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="modal-label" for="stock-qty">Stock</label>
                        <input type="number" id="stock-qty" placeholder="0">
                    </div>

                    <div>
                        <label class="modal-label" for="stock-unit">Unit</label>
                        <input type="text" id="stock-unit" placeholder="Bags">
                    </div>

                    <div>
                        <label class="modal-label" for="stock-cost">Unit Cost (₱)</label>
                        <input type="number" id="stock-cost" placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="modal-label" for="stock-supplier">Preferred Supplier</label>
                    <select id="stock-supplier"></select>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-outline" onclick="app.closeModal('modal-add-stock')">Cancel</button>
                <button class="btn" onclick="app.submitNewStock()">Save Item</button>
            </div>
        </div>
    </div>

    <div id="modal-edit-payroll" class="modal-overlay" onclick="app.closeModalOnBackdrop(event, 'modal-edit-payroll')"
        style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pencil" style="color: var(--primary-hover);"></i> Edit Payroll Record
                </h3>
                <button class="modal-close" onclick="app.closeModal('modal-edit-payroll')"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="edit-pay-id">

                <div>
                    <label class="modal-label" for="edit-pay-award">Award Cost (₱)</label>
                    <input type="text" id="edit-pay-award" oninput="app.formatCurrencyInput(this)">
                </div>

                <div>
                    <label class="modal-label" for="edit-pay-advance">Cash Advance (₱)</label>
                    <input type="text" id="edit-pay-advance" oninput="app.formatCurrencyInput(this)">
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-outline" onclick="app.closeModal('modal-edit-payroll')">Cancel</button>
                <button class="btn" onclick="app.saveEditedPayroll()">Save Changes</button>
            </div>
        </div>
    </div>
    <div id="resume-modal"
        style="display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%; background-color:rgba(9, 9, 11, 0.95); overflow:auto; backdrop-filter: blur(6px);">
        <span onclick="document.getElementById('resume-modal').style.display='none'"
            style="position:absolute; top:20px; right:40px; color:var(--primary); font-size:40px; font-weight:bold; cursor:pointer;">&times;</span>
        <img id="resume-img"
            style="margin:auto; display:block; max-width:80%; max-height:90vh; margin-top:5vh; border-radius: var(--radius-md); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
    </div>

    <div id="modal-bulk-all" class="modal-overlay" onclick="app.closeModalOnBackdrop(event, 'modal-bulk-all')"
        style="display: none;">
        <div class="modal-container bulk-all-modal">
            <div class="modal-header">
                <h3>
                    <i class="fa-solid fa-layer-group" style="color: var(--primary-hover);"></i>
                    Bulk Add Records
                </h3>
                <button class="modal-close" onclick="app.closeModal('modal-bulk-all')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <label class="modal-label" for="bulk-all-module">Choose module</label>
                <select id="bulk-all-module" onchange="app.updateBulkTemplate()">
                    <option value="projects">Projects / Sites</option>
                    <option value="suppliers">Suppliers</option>
                    <option value="inventory">Inventory / Stock</option>
                    <option value="manpower">Manpower / Record List</option>
                    <option value="award_costs">Award Costs</option>
                    <option value="payroll">Payroll</option>
                    <option value="cash_release">Cash Release</option>
                    <option value="ntp">Notice to Proceed</option>
                </select>

                <div class="bulk-example-box">
                    <b>Format per line:</b>
                    <span id="bulk-all-format">Project Name, Client, Location, Description, Foreman, Start Date
                        YYYY-MM-DD</span>
                    <small>Example:</small>
                    <code
                        id="bulk-all-example">Project A, Client One, Laguna, Two storey house, Juan Foreman, 2026-06-01</code>
                </div>

                <label class="modal-label" for="bulk-all-textarea">Paste records here</label>
                <textarea id="bulk-all-textarea"
                    placeholder="Project A, Client One, Laguna, Two storey house, Juan Foreman, 2026-06-01"></textarea>

                <p class="bulk-note">
                    Use one record per line. Use <b>comma (,)</b> as separator.
                    Press <b>Enter</b> kapag panibagong record/tao/item na.
                    Kapag may comma sa loob ng isang field, lagyan ng quotes, example:
                    <b>"Cement, Sand, Gravel"</b>.
                </p>
            </div>

            <div class="modal-footer">
                <button class="btn-outline" onclick="app.closeModal('modal-bulk-all')">Cancel</button>
                <button class="btn" onclick="app.bulkAddAll()">
                    <i class="fa-solid fa-upload"></i> Save Bulk Records
                </button>
            </div>
        </div>
    </div>
    <script src="js/app.js?v=<?php echo time(); ?>"></script>
</body>

</html>
# VD License Manager - Frontend Specifications

## 1. Admin Dashboard Overview

### 1.1 Main Dashboard Layout
```
┌─────────────────────────────────────────────────────┐
│ VD License Manager Dashboard                        │
├─────────────────────────────────────────────────────┤
│ [Statistics Cards Row]                              │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐   │
│ │ Active  │ │ Expired │ │Provider │ │ Today's │   │
│ │Licenses │ │Licenses │ │Accounts │ │ Usage   │   │
│ │  1,234  │ │   45    │ │   127   │ │  2,856  │   │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘   │
├─────────────────────────────────────────────────────┤
│ [Charts Section]                                    │
│ ┌─────────────────┐ ┌─────────────────────────────┐ │
│ │ Usage Trends    │ │ License Status Distribution │ │
│ │ (Line Chart)    │ │ (Pie Chart)                 │ │
│ └─────────────────┘ └─────────────────────────────┘ │
├─────────────────────────────────────────────────────┤
│ [Recent Activity Table]                             │
│ Recent License Resolutions (Last 24h)               │
│ ┌───────────┬────────────┬────────────┬──────────┐ │
│ │ Time      │ License    │ Device     │ Status   │ │
│ │ 14:32     │ VD-AB12... │ Chrome/Win │ Success  │ │
│ │ 14:28     │ VD-CD34... │ Safari/Mac │ Failed   │ │
│ └───────────┴────────────┴────────────┴──────────┘ │
└─────────────────────────────────────────────────────┘
```

### 1.2 Navigation Menu Structure
```
VD License Manager
├── Dashboard
├── Licenses
│   ├── All Licenses
│   ├── Add New License
│   ├── License Settings Override
│   └── Bulk Actions
├── Provider Accounts
│   ├── All Providers
│   ├── Add New Provider
│   ├── Update Credentials
│   └── Account Status
├── Products
│   ├── Product Settings
│   ├── Product Field Sharing
│   ├── Product Analytics
│   └── Provider Mapping
├── Devices
│   ├── Registered Devices
│   └── Suspicious Activity
├── Analytics & Reports
│   ├── Product Analytics
│   ├── Provider Lookup
│   ├── Usage Reports
│   └── Security Logs
└── Settings
    ├── Global Settings
    ├── Security Settings
    └── API Settings
```

## 2. License Management Interface

### 2.1 License List View
```html
<!-- Admin License List Template -->
<div class="vd-license-manager-wrap">
    <h1>License Management
        <a href="#" class="page-title-action">Add New License</a>
    </h1>

    <!-- Filter Bar -->
    <div class="vd-filter-bar">
        <select name="status">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="suspended">Suspended</option>
        </select>

        <select name="product">
            <option value="">All Products</option>
            <!-- Dynamic product options -->
        </select>

        <select name="provider_account">
            <option value="">All Provider Accounts</option>
            <!-- Dynamic provider options -->
        </select>

        <select name="has_overrides">
            <option value="">All Settings</option>
            <option value="yes">Has Custom Settings</option>
            <option value="no">Uses Default Settings</option>
        </select>

        <input type="text" name="search" placeholder="Search licenses...">
        <button class="button">Filter</button>
        <a href="#" class="button" id="bulk-assign-provider">Bulk Assign Provider</a>
    </div>

    <!-- Bulk Actions -->
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <select name="action">
                <option value="-1">Bulk Actions</option>
                <option value="activate">Activate</option>
                <option value="suspend">Suspend</option>
                <option value="delete">Delete</option>
            </select>
            <input type="submit" class="button action" value="Apply">
        </div>
    </div>

    <!-- License Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="check-column"><input type="checkbox"></td>
                <th>License Key</th>
                <th>Product</th>
                <th>Assigned Account</th>
                <th>Status</th>
                <th>Devices</th>
                <th>Settings</th>
                <th>Created</th>
                <th>Expires</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th class="check-column"><input type="checkbox"></th>
                <td><code>VD-AB12-CD34-EF56</code></td>
                <td>Premium Plugin v2.0</td>
                <td>
                    <span class="provider-account">helium10-main-01</span>
                    <small class="assignment-method">(manual)</small>
                </td>
                <td><span class="status-active">Active</span></td>
                <td>3/5</td>
                <td>
                    <span class="settings-source product" title="Dùng cài đặt sản phẩm">P</span>
                    <a href="#" class="view-settings" data-license-id="123">View</a>
                </td>
                <td>2024-01-15</td>
                <td>2024-12-15</td>
                <td>
                    <a href="#">View</a> |
                    <a href="#">Edit</a> |
                    <a href="#" class="change-provider" data-license-id="123">Change Account</a> |
                    <a href="#" class="customize-settings" data-license-id="123">Settings</a>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### 2.2 Add/Edit License Form
```html
<div class="vd-license-form-wrap">
    <h2>Add New License</h2>

    <form method="post" class="vd-license-form">
        <table class="form-table">
            <tr>
                <th><label for="license_key">License Key</label></th>
                <td>
                    <input type="text" id="license_key" name="license_key"
                           class="regular-text" placeholder="Auto-generated if empty">
                    <button type="button" class="button" id="generate-key">Generate</button>
                </td>
            </tr>

            <tr>
                <th><label for="product_id">Product *</label></th>
                <td>
                    <select id="product_id" name="product_id" required>
                        <option value="">Select Product</option>
                        <!-- Dynamic product options -->
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="customer_email">Customer Email *</label></th>
                <td>
                    <input type="email" id="customer_email" name="customer_email"
                           class="regular-text" required>
                </td>
            </tr>

            <tr>
                <th><label for="max_devices">Max Devices</label></th>
                <td>
                    <input type="number" id="max_devices" name="max_devices"
                           min="1" max="100" value="" class="small-text"
                           placeholder="Leave empty to use product default">
                    <p class="description">Override product default (leave empty to inherit)</p>
                </td>
            </tr>

            <tr>
                <th><label for="rate_limit_requests">Rate Limit (Requests)</label></th>
                <td>
                    <input type="number" id="rate_limit_requests" name="rate_limit_requests"
                           min="1" value="" class="small-text"
                           placeholder="Leave empty to use product default">
                    <p class="description">Override product default (leave empty to inherit)</p>
                </td>
            </tr>

            <tr>
                <th><label for="rate_limit_window_hours">Rate Limit Window (Hours)</label></th>
                <td>
                    <input type="number" id="rate_limit_window_hours" name="rate_limit_window_hours"
                           min="1" max="24" value="" class="small-text"
                           placeholder="Leave empty to use product default">
                    <p class="description">Time window for rate limiting (leave empty to inherit)</p>
                </td>
            </tr>

            <tr>
                <th><label for="provider_account_id">Assigned Provider Account</label></th>
                <td>
                    <select id="provider_account_id" name="provider_account_id">
                        <option value="">Auto-assign based on product settings</option>
                        <!-- Dynamic provider options -->
                    </select>
                    <p class="description">Manually assign specific provider account</p>
                </td>
            </tr>

            <tr>
                <th><label for="expires_at">Expiration Date</label></th>
                <td>
                    <input type="datetime-local" id="expires_at" name="expires_at">
                    <p class="description">Leave empty for lifetime license</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button-primary" value="Create License">
            <a href="#" class="button">Cancel</a>
        </p>
    </form>
</div>

### 2.3 License Settings Override Modal
```html
<div id="vd-license-settings-modal" class="vd-modal" style="display:none;">
    <div class="vd-modal-content">
        <div class="vd-modal-header">
            <h2>License Settings Override</h2>
            <span class="vd-modal-close">&times;</span>
        </div>

        <div class="vd-settings-comparison">
            <div class="settings-column">
                <h3>Current Effective Settings</h3>
                <div class="settings-display">
                    <div class="setting-item">
                        <label>Max Devices:</label>
                        <span id="current-max-devices">5</span>
                        <small class="source">(from product settings)</small>
                    </div>
                    <div class="setting-item">
                        <label>Rate Limit:</label>
                        <span id="current-rate-limit">100 requests/hour</span>
                        <small class="source">(from product settings)</small>
                    </div>
                </div>
            </div>

            <div class="settings-column">
                <h3>License Override Settings</h3>
                <form class="vd-license-settings-form">
                    <input type="hidden" name="license_id" id="settings-license-id">

                    <div class="form-group">
                        <label for="override_max_devices">Max Devices Override</label>
                        <input type="number" id="override_max_devices" name="max_devices"
                               min="1" max="100" placeholder="Leave empty to use default">
                        <small>Override the product default for this license only</small>
                    </div>

                    <div class="form-group">
                        <label for="override_rate_limit_requests">Rate Limit Requests</label>
                        <input type="number" id="override_rate_limit_requests" name="rate_limit_requests"
                               min="1" placeholder="Leave empty to use default">
                    </div>

                    <div class="form-group">
                        <label for="override_rate_limit_window_hours">Rate Limit Window (Hours)</label>
                        <input type="number" id="override_rate_limit_window_hours" name="rate_limit_window_hours"
                               min="1" max="24" placeholder="Leave empty to use default">
                    </div>

                    <div class="form-group">
                        <label for="override_notes">Notes (Optional)</label>
                        <textarea id="override_notes" name="notes" rows="3"
                                  placeholder="Reason for custom settings..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary">Save Override Settings</button>
                        <button type="button" class="button remove-override">Remove All Overrides</button>
                        <button type="button" class="button vd-modal-close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

### 2.4 Change Provider Account Modal
```html
<div id="vd-change-provider-modal" class="vd-modal" style="display:none;">
    <div class="vd-modal-content">
        <div class="vd-modal-header">
            <h2>Change Provider Account Assignment</h2>
            <span class="vd-modal-close">&times;</span>
        </div>

        <div class="current-assignment-info">
            <h3>Current Assignment</h3>
            <div class="assignment-details">
                <p><strong>Account:</strong> <span id="current-account-name">helium10-main-01</span></p>
                <p><strong>Provider:</strong> <span id="current-provider">Helium10</span></p>
                <p><strong>Assigned:</strong> <span id="assignment-date">2024-01-15 10:30</span></p>
                <p><strong>Method:</strong> <span id="assignment-method">Auto</span></p>
            </div>
        </div>

        <form class="vd-change-provider-form">
            <input type="hidden" name="license_id" id="change-provider-license-id">

            <div class="form-group">
                <label for="new_provider_account_id">New Provider Account *</label>
                <select id="new_provider_account_id" name="provider_account_id" required>
                    <option value="">Select Provider Account</option>
                    <!-- Dynamic provider options -->
                </select>
            </div>

            <div class="form-group">
                <label for="change_reason">Reason for Change *</label>
                <textarea id="change_reason" name="change_reason" rows="3" required
                          placeholder="Explain why you're changing the provider account..."></textarea>
            </div>

            <div class="provider-info-preview" id="provider-preview" style="display:none;">
                <h4>New Provider Account Info</h4>
                <div class="provider-stats">
                    <span class="stat">Current Load: <strong id="preview-load">15/20</strong></span>
                    <span class="stat">Status: <strong id="preview-status">Active</strong></span>
                    <span class="stat">Success Rate: <strong id="preview-success-rate">98.2%</strong></span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Change Provider Account</button>
                <button type="button" class="button vd-modal-close">Cancel</button>
            </div>
        </form>

        <div class="assignment-history">
            <h3>Assignment History</h3>
            <div class="history-list">
                <!-- Will be populated via AJAX -->
            </div>
        </div>
    </div>
</div>
```

## 3. Provider Account Management

### 3.1 Provider List Interface
```html
<div class="vd-provider-wrap">
    <h1>Provider Accounts
        <a href="#" class="page-title-action">Add New Provider</a>
    </h1>

    <!-- Provider Cards Grid -->
    <div class="vd-provider-grid">
        <div class="vd-provider-card active">
            <div class="provider-header">
                <h3>Netflix Account #1</h3>
                <span class="status-badge status-active">Active</span>
            </div>

            <div class="provider-details">
                <p><strong>Product:</strong> Streaming Service</p>
                <p><strong>Assignments:</strong> 45/50</p>
                <p><strong>Success Rate:</strong> 98.2%</p>
                <p><strong>Last Used:</strong> 2 minutes ago</p>
            </div>

            <div class="provider-actions">
                <button class="button button-small edit-provider" data-provider-id="1">Edit</button>
                <button class="button button-small update-credentials" data-provider-id="1">Update Credentials</button>
                <button class="button button-small test-provider" data-provider-id="1">Test</button>
                <button class="button button-small button-link-delete">Suspend</button>
            </div>
        </div>

        <!-- More provider cards... -->
    </div>
</div>
```

### 3.2 Provider Form (Modal)
```html
<div id="vd-provider-modal" class="vd-modal" style="display:none;">
    <div class="vd-modal-content">
        <div class="vd-modal-header">
            <h2>Add Provider Account</h2>
            <span class="vd-modal-close">&times;</span>
        </div>

        <form class="vd-provider-form">
            <div class="form-group">
                <label for="provider_type">Provider Type *</label>
                <select id="provider_type" name="provider_type" required>
                    <option value="">Select Type</option>
                    <option value="netflix">Netflix</option>
                    <option value="spotify">Spotify</option>
                    <option value="adobe">Adobe Creative Cloud</option>
                    <option value="office365">Office 365</option>
                    <option value="custom">Custom</option>
                </select>
            </div>

            <div class="form-group">
                <label for="product_id">Associated Product *</label>
                <select id="product_id" name="product_id" required>
                    <!-- Dynamic product options -->
                </select>
            </div>

            <div class="form-group">
                <label for="account_email">Email</label>
                <input type="email" id="account_email" name="email" class="regular-text"
                       placeholder="account@example.com">
                <small>Email tài khoản (plain text)</small>
            </div>

            <div class="form-group">
                <label for="account_password">Password</label>
                <input type="text" id="account_password" name="password" class="regular-text"
                       placeholder="password123">
                <small>Mật khẩu (plain text, không mã hóa)</small>
            </div>

            <div class="form-group">
                <label for="twofa_code">2FA Code (if applicable)</label>
                <input type="text" id="twofa_code" name="twofa_code" class="small-text"
                       placeholder="123456" maxlength="10">
                <small>Mã 2FA hiện tại (plain text)</small>
            </div>

            <div class="form-group">
                <label for="cookies">Cookies (if applicable)</label>
                <textarea id="cookies" name="cookies" rows="6"
                          placeholder="Paste cookie string here (plain text)"></textarea>
                <small>Cookie string từ browser (plain text, không mã hóa)</small>
            </div>

            <div class="form-group">
                <label for="additional_data">Additional Data (JSON)</label>
                <textarea id="additional_data" name="content_data" rows="4"
                          placeholder='{"custom_field": "value"}'></textarea>
                <small>Dữ liệu bổ sung ở định dạng JSON</small>
            </div>

            <div class="form-group">
                <label for="max_assignments">Max Assignments</label>
                <input type="number" id="max_assignments" name="max_assignments"
                       min="1" value="50" class="small-text">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" checked>
                    Active (available for assignments)
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Save Provider</button>
                <button type="button" class="button vd-modal-close">Cancel</button>
            </div>
        </form>
    </div>
</div>
```

## 4. Reports and Analytics

### 4.1 Usage Reports Dashboard
```html
<div class="vd-reports-wrap">
    <h1>Usage Reports</h1>

    <!-- Date Range Selector -->
    <div class="vd-date-range">
        <label>Date Range:</label>
        <input type="date" id="start_date" name="start_date">
        <span>to</span>
        <input type="date" id="end_date" name="end_date">
        <button class="button">Apply</button>

        <div class="preset-ranges">
            <a href="#" data-range="today">Today</a> |
            <a href="#" data-range="week">This Week</a> |
            <a href="#" data-range="month">This Month</a> |
            <a href="#" data-range="quarter">This Quarter</a>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="vd-metrics-grid">
        <div class="metric-card">
            <div class="metric-number">12,456</div>
            <div class="metric-label">Total Resolutions</div>
            <div class="metric-change positive">+15.2%</div>
        </div>

        <div class="metric-card">
            <div class="metric-number">98.7%</div>
            <div class="metric-label">Success Rate</div>
            <div class="metric-change positive">+2.1%</div>
        </div>

        <div class="metric-card">
            <div class="metric-number">145ms</div>
            <div class="metric-label">Avg Response Time</div>
            <div class="metric-change negative">-8ms</div>
        </div>
    </div>

    <!-- Charts Container -->
    <div class="vd-charts-container">
        <div class="chart-section">
            <h3>Resolution Trends</h3>
            <canvas id="resolution-trends-chart"></canvas>
        </div>

        <div class="chart-section">
            <h3>Top Products by Usage</h3>
            <canvas id="product-usage-chart"></canvas>
        </div>
    </div>
</div>
```

### 4.2 Security Monitoring Interface
```html
<div class="vd-security-dashboard">
    <h2>Security Monitoring</h2>

    <!-- Alert Summary -->
    <div class="security-alerts">
        <div class="alert-card high-risk">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <h4>High Risk Activity Detected</h4>
                <p>3 licenses showing unusual device patterns</p>
                <a href="#" class="alert-action">Investigate</a>
            </div>
        </div>
    </div>

    <!-- Risk Score Distribution -->
    <div class="risk-distribution">
        <h3>Device Risk Score Distribution</h3>
        <div class="risk-bars">
            <div class="risk-bar">
                <span class="risk-label">Low (0-30)</span>
                <div class="risk-bar-fill" style="width: 75%">
                    <span class="risk-count">1,234 devices</span>
                </div>
            </div>
            <div class="risk-bar">
                <span class="risk-label">Medium (31-70)</span>
                <div class="risk-bar-fill" style="width: 20%">
                    <span class="risk-count">324 devices</span>
                </div>
            </div>
            <div class="risk-bar">
                <span class="risk-label">High (71-100)</span>
                <div class="risk-bar-fill" style="width: 5%">
                    <span class="risk-count">45 devices</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Security Events -->
    <div class="security-events">
        <h3>Recent Security Events</h3>
        <table class="security-events-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Event Type</th>
                    <th>License</th>
                    <th>Risk Score</th>
                    <th>Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="event-high-risk">
                    <td>14:32</td>
                    <td>Suspicious Device</td>
                    <td>VD-AB12...</td>
                    <td><span class="risk-score high">85</span></td>
                    <td>Multiple countries in 1 hour</td>
                    <td><button class="button button-small">Block</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

## 5. Product Settings Management

### 5.1 Product Settings List
```html
<div class="vd-product-settings-wrap">
    <h1>Product Settings Management
        <a href="#" class="page-title-action">Add Product Settings</a>
    </h1>

    <!-- Product Settings Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Max Devices</th>
                <th>Rate Limit</th>
                <th>Window (Hours)</th>
                <th>Auto Approval</th>
                <th>Licenses Count</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>8210</td>
                <td>Helium10 Premium Access</td>
                <td>5 devices</td>
                <td>100 requests</td>
                <td>1 hour</td>
                <td>✓ Enabled</td>
                <td>234 licenses</td>
                <td>
                    <a href="#" class="edit-product-settings" data-product-id="8210">Edit</a> |
                    <a href="#" class="edit-field-sharing" data-product-id="8210">Field Sharing</a> |
                    <a href="#" class="view-product-licenses" data-product-id="8210">View Licenses</a>
                </td>
            </tr>
        </tbody>
    </table>
</div>

### 5.2 Product Settings Form Modal
```html
<div id="vd-product-settings-modal" class="vd-modal" style="display:none;">
    <div class="vd-modal-content">
        <div class="vd-modal-header">
            <h2>Product Settings Configuration</h2>
            <span class="vd-modal-close">&times;</span>
        </div>

        <form class="vd-product-settings-form">
            <input type="hidden" name="product_id" id="product-settings-id">

            <div class="form-group">
                <label for="product-max-devices">Max Devices Default *</label>
                <input type="number" id="product-max-devices" name="max_devices"
                       min="1" max="100" value="3" required>
                <small>Số thiết bị mặc định cho license của sản phẩm này</small>
            </div>

            <div class="form-group">
                <label for="product-rate-limit-requests">Rate Limit Requests *</label>
                <input type="number" id="product-rate-limit-requests" name="rate_limit_requests"
                       min="1" value="100" required>
                <small>Số request tối đa trong khung thời gian</small>
            </div>

            <div class="form-group">
                <label for="product-rate-limit-window">Rate Limit Window (Hours) *</label>
                <input type="number" id="product-rate-limit-window" name="rate_limit_window_hours"
                       min="1" max="24" value="1" required>
                <small>Khung thời gian tính rate limit</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="auto_approval_enabled" checked>
                    Auto Approval Enabled
                </label>
                <small>Tự động phê duyệt thiết bị mới có risk score thấp</small>
            </div>

            <div class="form-group">
                <label for="product-grace-period">Grace Period (Hours)</label>
                <input type="number" id="product-grace-period" name="grace_period_hours"
                       min="0" max="168" value="72">
                <small>Thời gian gia hạn khi vượt giới hạn thiết bị</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Save Product Settings</button>
                <button type="button" class="button vd-modal-close">Cancel</button>
            </div>
        </form>
    </div>
</div>
```

### 5.3 Product Field Sharing Configuration Interface

```html
<div class="vd-field-sharing-wrap">
    <h1>Product Field Sharing Configuration</h1>

    <!-- Product Selection -->
    <div class="vd-product-selector">
        <label for="select-product">Select Product:</label>
        <select id="select-product" name="product_id">
            <option value="">-- Choose a Product --</option>
            <option value="8210">Helium10 Premium Access</option>
            <option value="8211">Midjourney Pro Subscription</option>
            <option value="8212">Freepik Business License</option>
        </select>
        <button type="button" class="button button-secondary" id="load-field-config">Load Configuration</button>
    </div>

    <!-- Field Configuration Interface -->
    <div id="field-sharing-config" style="display:none;">
        <div class="config-header">
            <h2>Field Sharing Configuration for: <span id="selected-product-name"></span></h2>
            <div class="config-summary">
                <span class="summary-item">Total Fields: <strong id="total-fields">0</strong></span>
                <span class="summary-item">Shared Fields: <strong id="shared-fields">0</strong></span>
                <span class="summary-item">Sensitive Shared: <strong id="sensitive-shared">0</strong></span>
            </div>
        </div>

        <!-- Field Categories -->
        <div class="field-categories">
            <!-- Login Info Category -->
            <div class="category-section" data-category="Login Info">
                <h3>🔐 Login Information</h3>
                <div class="fields-grid">
                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-email" name="fields[email]" data-field="email">
                            <label for="field-email">
                                <span class="field-name">Email đăng nhập</span>
                                <span class="field-type"></span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[email]" placeholder="Tên hiển thị" value="Email đăng nhập">
                            <input type="number" name="sort_orders[email]" placeholder="Thứ tự" value="1" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-password" name="fields[password]" data-field="password">
                            <label for="field-password">
                                <span class="field-name">Mật khẩu</span>
                                <span class="field-type sensitive">🔒 Sensitive</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[password]" placeholder="Tên hiển thị" value="Mật khẩu">
                            <input type="number" name="sort_orders[password]" placeholder="Thứ tự" value="2" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-twofa" name="fields[twofa_code]" data-field="twofa_code">
                            <label for="field-twofa">
                                <span class="field-name">Mã 2FA</span>
                                <span class="field-type sensitive">🔒 Sensitive</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[twofa_code]" placeholder="Tên hiển thị" value="Mã 2FA">
                            <input type="number" name="sort_orders[twofa_code]" placeholder="Thứ tự" value="3" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-cookies" name="fields[cookies]" data-field="cookies">
                            <label for="field-cookies">
                                <span class="field-name">Cookie đăng nhập</span>
                                <span class="field-type sensitive">🔒 Sensitive</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[cookies]" placeholder="Tên hiển thị" value="Cookie đăng nhập">
                            <input type="number" name="sort_orders[cookies]" placeholder="Thứ tự" value="4" min="1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recovery Info Category -->
            <div class="category-section" data-category="Recovery Info">
                <h3>🔄 Recovery Information</h3>
                <div class="fields-grid">
                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-recovery-email" name="fields[recovery_email]" data-field="recovery_email">
                            <label for="field-recovery-email">
                                <span class="field-name">Email khôi phục</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[recovery_email]" placeholder="Tên hiển thị" value="Email khôi phục">
                            <input type="number" name="sort_orders[recovery_email]" placeholder="Thứ tự" value="5" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-recovery-password" name="fields[recovery_password]" data-field="recovery_password">
                            <label for="field-recovery-password">
                                <span class="field-name">Mật khẩu khôi phục</span>
                                <span class="field-type sensitive">🔒 Sensitive</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[recovery_password]" placeholder="Tên hiển thị" value="Mật khẩu khôi phục">
                            <input type="number" name="sort_orders[recovery_password]" placeholder="Thứ tự" value="6" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-recovery-twofa" name="fields[recovery_twofa_code]" data-field="recovery_twofa_code">
                            <label for="field-recovery-twofa">
                                <span class="field-name">Mã 2FA khôi phục</span>
                                <span class="field-type sensitive">🔒 Sensitive</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[recovery_twofa_code]" placeholder="Tên hiển thị" value="Mã 2FA khôi phục">
                            <input type="number" name="sort_orders[recovery_twofa_code]" placeholder="Thứ tự" value="7" min="1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Info Category -->
            <div class="category-section" data-category="Account Info">
                <h3>📊 Account Information</h3>
                <div class="fields-grid">
                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-reg-date" name="fields[account_registration_date]" data-field="account_registration_date">
                            <label for="field-reg-date">
                                <span class="field-name">Ngày đăng ký</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[account_registration_date]" placeholder="Tên hiển thị" value="Ngày đăng ký">
                            <input type="number" name="sort_orders[account_registration_date]" placeholder="Thứ tự" value="8" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-exp-date" name="fields[account_expiry_date]" data-field="account_expiry_date">
                            <label for="field-exp-date">
                                <span class="field-name">Ngày hết hạn</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[account_expiry_date]" placeholder="Tên hiển thị" value="Ngày hết hạn">
                            <input type="number" name="sort_orders[account_expiry_date]" placeholder="Thứ tự" value="9" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-amount" name="fields[registration_amount]" data-field="registration_amount">
                            <label for="field-amount">
                                <span class="field-name">Số tiền đăng ký</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[registration_amount]" placeholder="Tên hiển thị" value="Số tiền đăng ký">
                            <input type="number" name="sort_orders[registration_amount]" placeholder="Thứ tự" value="10" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-status" name="fields[status]" data-field="status">
                            <label for="field-status">
                                <span class="field-name">Trạng thái tài khoản</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[status]" placeholder="Tên hiển thị" value="Trạng thái">
                            <input type="number" name="sort_orders[status]" placeholder="Thứ tự" value="11" min="1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Info Category -->
            <div class="category-section" data-category="Operational">
                <h3>⚙️ Operational Information</h3>
                <div class="fields-grid">
                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-license-count" name="fields[assigned_licenses_count]" data-field="assigned_licenses_count">
                            <label for="field-license-count">
                                <span class="field-name">Số license gán</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[assigned_licenses_count]" placeholder="Tên hiển thị" value="Số license gán">
                            <input type="number" name="sort_orders[assigned_licenses_count]" placeholder="Thứ tự" value="12" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-last-checked" name="fields[last_checked_at]" data-field="last_checked_at">
                            <label for="field-last-checked">
                                <span class="field-name">Lần check cuối</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[last_checked_at]" placeholder="Tên hiển thị" value="Lần check cuối">
                            <input type="number" name="sort_orders[last_checked_at]" placeholder="Thứ tự" value="13" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-last-success" name="fields[last_success_at]" data-field="last_success_at">
                            <label for="field-last-success">
                                <span class="field-name">Lần thành công cuối</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[last_success_at]" placeholder="Tên hiển thị" value="Lần thành công cuối">
                            <input type="number" name="sort_orders[last_success_at]" placeholder="Thứ tự" value="14" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-error-count" name="fields[error_count]" data-field="error_count">
                            <label for="field-error-count">
                                <span class="field-name">Số lỗi</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[error_count]" placeholder="Tên hiển thị" value="Số lỗi">
                            <input type="number" name="sort_orders[error_count]" placeholder="Thứ tự" value="15" min="1">
                        </div>
                    </div>

                    <div class="field-config-row">
                        <div class="field-checkbox">
                            <input type="checkbox" id="field-notes" name="fields[notes]" data-field="notes">
                            <label for="field-notes">
                                <span class="field-name">Ghi chú</span>
                            </label>
                        </div>
                        <div class="field-settings">
                            <input type="text" name="display_names[notes]" placeholder="Tên hiển thị" value="Ghi chú">
                            <input type="number" name="sort_orders[notes]" placeholder="Thứ tự" value="16" min="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <button type="button" class="button button-secondary" id="select-all-fields">✓ Select All</button>
            <button type="button" class="button button-secondary" id="deselect-all-fields">✗ Deselect All</button>
            <button type="button" class="button button-secondary" id="select-login-only">🔐 Login Info Only</button>
            <button type="button" class="button button-secondary" id="exclude-sensitive">🔒 Exclude Sensitive</button>
        </div>

        <!-- Preview Section -->
        <div class="preview-section">
            <h3>🔍 Customer View Preview</h3>
            <div class="preview-content" id="customer-preview">
                <p class="preview-placeholder">Select fields to see customer preview</p>
            </div>
            <button type="button" class="button button-secondary" id="refresh-preview">Refresh Preview</button>
        </div>

        <!-- Save Actions -->
        <div class="save-actions">
            <button type="submit" class="button button-primary" id="save-field-config">💾 Save Configuration</button>
            <button type="button" class="button button-secondary" id="reset-config">🔄 Reset to Default</button>
            <button type="button" class="button" id="cancel-config">❌ Cancel</button>
        </div>
    </div>
</div>

<!-- Field Sharing CSS -->
<style>
.vd-field-sharing-wrap {
    max-width: 1200px;
    margin: 20px 0;
}

.vd-product-selector {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.config-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
}

.config-summary {
    display: flex;
    gap: 20px;
}

.summary-item {
    background: #e1f5fe;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
}

.category-section {
    margin-bottom: 30px;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    overflow: hidden;
}

.category-section h3 {
    background: #f8f9fa;
    margin: 0;
    padding: 12px 15px;
    border-bottom: 1px solid #e5e5e5;
    font-size: 14px;
    font-weight: 600;
}

.fields-grid {
    padding: 15px;
}

.field-config-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    border: 1px solid #f0f0f0;
    margin-bottom: 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.field-config-row:hover {
    background: #f9f9f9;
    border-color: #ddd;
}

.field-config-row.selected {
    background: #e3f2fd;
    border-color: #2196f3;
}

.field-checkbox {
    flex: 1;
}

.field-checkbox label {
    display: flex;
    align-items: center;
    font-weight: 500;
    cursor: pointer;
}

.field-name {
    margin-left: 8px;
}

.field-type.sensitive {
    background: #ffecb3;
    color: #f57c00;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 8px;
}

.field-settings {
    display: flex;
    gap: 8px;
    align-items: center;
}

.field-settings input[type="text"] {
    width: 150px;
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
}

.field-settings input[type="number"] {
    width: 60px;
    padding: 4px 6px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
    text-align: center;
}

.quick-actions {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.preview-section {
    margin: 20px 0;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.preview-section h3 {
    background: #f5f5f5;
    margin: 0;
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
}

.preview-content {
    padding: 15px;
    background: #fafafa;
    min-height: 100px;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
}

.preview-placeholder {
    color: #888;
    font-style: italic;
}

.save-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<!-- Field Sharing JavaScript -->
<script>
jQuery(document).ready(function($) {
    // Load product configuration
    $('#load-field-config').on('click', function() {
        const productId = $('#select-product').val();
        if (!productId) {
            alert('Please select a product first');
            return;
        }

        loadFieldConfiguration(productId);
    });

    // Quick action buttons
    $('#select-all-fields').on('click', function() {
        $('.field-config-row input[type="checkbox"]').prop('checked', true).trigger('change');
    });

    $('#deselect-all-fields').on('click', function() {
        $('.field-config-row input[type="checkbox"]').prop('checked', false).trigger('change');
    });

    $('#select-login-only').on('click', function() {
        $('.field-config-row input[type="checkbox"]').prop('checked', false);
        $('.category-section[data-category="Login Info"] input[type="checkbox"]').prop('checked', true);
        updateSummaryAndPreview();
    });

    $('#exclude-sensitive').on('click', function() {
        $('.field-config-row .field-type.sensitive').closest('.field-config-row').find('input[type="checkbox"]').prop('checked', false);
        updateSummaryAndPreview();
    });

    // Field selection changes
    $(document).on('change', '.field-config-row input[type="checkbox"]', function() {
        const row = $(this).closest('.field-config-row');
        if (this.checked) {
            row.addClass('selected');
        } else {
            row.removeClass('selected');
        }
        updateSummaryAndPreview();
    });

    // Refresh preview
    $('#refresh-preview').on('click', function() {
        generatePreview();
    });

    // Save configuration
    $('#save-field-config').on('click', function() {
        saveFieldConfiguration();
    });

    function loadFieldConfiguration(productId) {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'vd_get_product_field_config',
                product_id: productId,
                nonce: vd_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    populateFieldConfiguration(response.data);
                    $('#field-sharing-config').show();
                } else {
                    alert('Error loading configuration: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to load configuration');
            }
        });
    }

    function populateFieldConfiguration(data) {
        $('#selected-product-name').text(data.product_name);

        // Populate field checkboxes and settings
        Object.keys(data.field_configs).forEach(function(fieldName) {
            const config = data.field_configs[fieldName];
            const checkbox = $('input[data-field="' + fieldName + '"]');
            const row = checkbox.closest('.field-config-row');

            checkbox.prop('checked', config.is_shared);
            row.find('input[name="display_names[' + fieldName + ']"]').val(config.display_name);
            row.find('input[name="sort_orders[' + fieldName + ']"]').val(config.sort_order);

            if (config.is_shared) {
                row.addClass('selected');
            }
        });

        updateSummaryAndPreview();
    }

    function updateSummaryAndPreview() {
        const totalFields = $('.field-config-row').length;
        const sharedFields = $('.field-config-row input[type="checkbox"]:checked').length;
        const sensitiveShared = $('.field-config-row input[type="checkbox"]:checked').closest('.field-config-row').find('.field-type.sensitive').length;

        $('#total-fields').text(totalFields);
        $('#shared-fields').text(sharedFields);
        $('#sensitive-shared').text(sensitiveShared);

        generatePreview();
    }

    function generatePreview() {
        const selectedFields = [];
        $('.field-config-row input[type="checkbox"]:checked').each(function() {
            const row = $(this).closest('.field-config-row');
            const displayName = row.find('input[name^="display_names"]').val();
            const fieldName = $(this).data('field');
            const isSensitive = row.find('.field-type.sensitive').length > 0;

            selectedFields.push({
                field: fieldName,
                display: displayName,
                sensitive: isSensitive
            });
        });

        if (selectedFields.length === 0) {
            $('#customer-preview').html('<p class="preview-placeholder">No fields selected for sharing</p>');
            return;
        }

        // Sort by order
        selectedFields.sort((a, b) => {
            const orderA = parseInt($('input[data-field="' + a.field + '"]').closest('.field-config-row').find('input[name^="sort_orders"]').val()) || 0;
            const orderB = parseInt($('input[data-field="' + b.field + '"]').closest('.field-config-row').find('input[name^="sort_orders"]').val()) || 0;
            return orderA - orderB;
        });

        // Generate preview JSON
        let previewData = {};
        selectedFields.forEach(function(field) {
            previewData[field.display] = getSampleData(field.field);
            if (field.sensitive) {
                previewData[field.display + '_type'] = 'sensitive';
            }
        });

        $('#customer-preview').html('<pre>' + JSON.stringify(previewData, null, 2) + '</pre>');
    }

    function getSampleData(fieldName) {
        const samples = {
            'email': 'premium@helium10.com',
            'password': 'SuperSecretPassword123',
            'twofa_code': '123456',
            'cookies': 'session_id=abc123; auth_token=xyz789',
            'recovery_email': 'backup@email.com',
            'recovery_password': 'RecoveryPass456',
            'recovery_twofa_code': '987654',
            'account_registration_date': '2024-01-15',
            'account_expiry_date': '2024-12-31',
            'registration_amount': '299.00',
            'status': 'active',
            'assigned_licenses_count': 12,
            'last_checked_at': '2024-01-20T08:30:00Z',
            'last_success_at': '2024-01-20T08:25:00Z',
            'error_count': 0,
            'notes': 'Premium account - high priority'
        };
        return samples[fieldName] || 'Sample data';
    }

    function saveFieldConfiguration() {
        const productId = $('#select-product').val();
        if (!productId) {
            alert('No product selected');
            return;
        }

        const fieldConfigs = {};
        $('.field-config-row').each(function() {
            const row = $(this);
            const checkbox = row.find('input[type="checkbox"]');
            const fieldName = checkbox.data('field');
            const displayName = row.find('input[name^="display_names"]').val();
            const sortOrder = parseInt(row.find('input[name^="sort_orders"]').val()) || 1;
            const isSensitive = row.find('.field-type.sensitive').length > 0;

            fieldConfigs[fieldName] = {
                is_shared: checkbox.is(':checked'),
                display_name: displayName,
                sort_order: sortOrder,
                is_sensitive: isSensitive
            };
        });

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'vd_save_product_field_config',
                product_id: productId,
                field_configs: fieldConfigs,
                nonce: vd_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Configuration saved successfully!');
                } else {
                    alert('Error saving configuration: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to save configuration');
            }
        });
    }
});
</script>
```

## 6. Query and Statistics Interface

### 6.1 Product Analytics Dashboard
```html
<div class="vd-product-analytics-wrap">
    <h1>Product Analytics</h1>

    <div class="vd-analytics-filters">
        <select name="product_id" id="analytics-product-filter">
            <option value="">Select Product</option>
            <!-- Dynamic product options -->
        </select>
        <button class="button">Load Analytics</button>
    </div>

    <div id="product-analytics-display" class="analytics-dashboard" style="display:none;">
        <!-- Product Overview Stats -->
        <div class="analytics-section">
            <h2>Product Overview: <span id="product-name"></span></h2>

            <div class="vd-stats-grid">
                <div class="vd-stat-card">
                    <div class="stat-number" id="total-licenses">0</div>
                    <div class="stat-label">Total Licenses</div>
                </div>

                <div class="vd-stat-card">
                    <div class="stat-number" id="active-licenses">0</div>
                    <div class="stat-label">Active Licenses</div>
                </div>

                <div class="vd-stat-card">
                    <div class="stat-number" id="assigned-providers">0</div>
                    <div class="stat-label">Assigned Providers</div>
                </div>

                <div class="vd-stat-card">
                    <div class="stat-number" id="total-devices">0</div>
                    <div class="stat-label">Registered Devices</div>
                </div>
            </div>
        </div>

        <!-- Provider Distribution -->
        <div class="analytics-section">
            <h3>Provider Account Distribution</h3>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Account Name</th>
                        <th>Provider Type</th>
                        <th>License Count</th>
                        <th>Status</th>
                        <th>Load</th>
                    </tr>
                </thead>
                <tbody id="provider-distribution-list">
                    <!-- Will be populated via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Current Settings -->
        <div class="analytics-section">
            <h3>Product Settings</h3>
            <div class="settings-display">
                <div class="setting-row">
                    <label>Max Devices:</label>
                    <span id="product-max-devices-display">-</span>
                </div>
                <div class="setting-row">
                    <label>Rate Limit:</label>
                    <span id="product-rate-limit-display">-</span>
                </div>
                <div class="setting-row">
                    <label>Auto Approval:</label>
                    <span id="product-auto-approval-display">-</span>
                </div>
            </div>
        </div>
    </div>
</div>

### 6.2 Provider Account Search Interface
```html
<div class="vd-provider-search-wrap">
    <h1>Provider Account Lookup</h1>

    <div class="vd-search-form">
        <label for="provider-account-search">Search by Account Name:</label>
        <input type="text" id="provider-account-search" class="regular-text"
               placeholder="Enter provider account name...">
        <button class="button button-primary" id="search-provider-btn">Search</button>
    </div>

    <div id="provider-search-results" class="search-results" style="display:none;">
        <!-- Provider Account Details -->
        <div class="provider-details-section">
            <h2>Account Information</h2>
            <div class="provider-info-grid">
                <div class="info-item">
                    <label>Account Name:</label>
                    <span id="provider-account-name">-</span>
                </div>
                <div class="info-item">
                    <label>Provider Type:</label>
                    <span id="provider-type">-</span>
                </div>
                <div class="info-item">
                    <label>Status:</label>
                    <span id="provider-status">-</span>
                </div>
                <div class="info-item">
                    <label>Capacity:</label>
                    <span id="provider-capacity">-</span>
                </div>
            </div>
        </div>

        <!-- Assigned Licenses -->
        <div class="assigned-licenses-section">
            <h3>Assigned Licenses</h3>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>License Key</th>
                        <th>Product ID</th>
                        <th>Status</th>
                        <th>Device Count</th>
                        <th>Assignment Method</th>
                        <th>Last Accessed</th>
                    </tr>
                </thead>
                <tbody id="assigned-licenses-list">
                    <!-- Will be populated via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Served Products -->
        <div class="served-products-section">
            <h3>Served Products</h3>
            <div class="products-grid">
                <!-- Will be populated via AJAX -->
            </div>
        </div>
    </div>
</div>
```

## 7. Settings Interface

### 7.1 General Settings Tab
```html
<div class="vd-settings-wrap">
    <h1>VD License Manager Settings</h1>

    <nav class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active">General</a>
        <a href="#security" class="nav-tab">Security</a>
        <a href="#api" class="nav-tab">API Settings</a>
        <a href="#notifications" class="nav-tab">Notifications</a>
    </nav>

    <div id="general" class="tab-content">
        <form method="post" action="options.php">
            <?php settings_fields('vd_license_general'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Default License Duration</th>
                    <td>
                        <input type="number" name="vd_default_duration"
                               value="<?php echo get_option('vd_default_duration', 365); ?>"
                               min="1" class="small-text"> days
                    </td>
                </tr>

                <tr>
                    <th scope="row">Default Max Devices</th>
                    <td>
                        <input type="number" name="vd_default_max_devices"
                               value="<?php echo get_option('vd_default_max_devices', 5); ?>"
                               min="1" class="small-text"> devices
                    </td>
                </tr>

                <tr>
                    <th scope="row">Assignment Strategy</th>
                    <td>
                        <select name="vd_assignment_strategy">
                            <option value="least_loaded">Least Loaded</option>
                            <option value="round_robin">Round Robin</option>
                            <option value="sequential">Sequential</option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
</div>
```

### 5.2 Security Settings Tab
```html
<div id="security" class="tab-content" style="display:none;">
    <form method="post" action="options.php">
        <?php settings_fields('vd_license_security'); ?>

        <h3>Rate Limiting</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Requests per Hour</th>
                <td>
                    <input type="number" name="vd_rate_limit_hour"
                           value="<?php echo get_option('vd_rate_limit_hour', 100); ?>"
                           min="1" class="small-text">
                    <p class="description">Maximum license resolution requests per hour per device</p>
                </td>
            </tr>

            <tr>
                <th scope="row">Requests per Day</th>
                <td>
                    <input type="number" name="vd_rate_limit_day"
                           value="<?php echo get_option('vd_rate_limit_day', 1000); ?>"
                           min="1" class="regular-text">
                </td>
            </tr>
        </table>

        <h3>Risk Assessment</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Auto-Approval Threshold</th>
                <td>
                    <input type="number" name="vd_risk_threshold"
                           value="<?php echo get_option('vd_risk_threshold', 70); ?>"
                           min="0" max="100" class="small-text">
                    <p class="description">Requests with risk scores below this will be auto-approved</p>
                </td>
            </tr>

            <tr>
                <th scope="row">Block High Risk</th>
                <td>
                    <label>
                        <input type="checkbox" name="vd_block_high_risk"
                               <?php checked(get_option('vd_block_high_risk')); ?>>
                        Automatically block requests with risk scores above 90
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
```

## 6. Customer Portal Interface

### 6.1 License Checker Widget
```html
<div class="vd-customer-portal">
    <h3>Check Your License</h3>

    <form id="license-check-form" class="vd-license-check">
        <div class="form-group">
            <label for="license_key">License Key</label>
            <input type="text" id="license_key" name="license_key"
                   placeholder="VD-XXXX-XXXX-XXXX" pattern="VD-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}">
        </div>

        <button type="submit" class="button button-primary">Check License</button>
    </form>

    <div id="license-result" class="license-result" style="display:none;">
        <!-- Results will be populated via JavaScript -->
    </div>
</div>
```

### 6.2 License Status Display
```html
<div class="license-status-card valid">
    <div class="status-header">
        <span class="status-icon">✓</span>
        <h4>License Active</h4>
    </div>

    <div class="license-details">
        <div class="detail-row">
            <span class="label">Product:</span>
            <span class="value">Premium Plugin v2.0</span>
        </div>

        <div class="detail-row">
            <span class="label">Expires:</span>
            <span class="value">December 15, 2024</span>
        </div>

        <div class="detail-row">
            <span class="label">Devices:</span>
            <span class="value">3 of 5 used</span>
        </div>
    </div>

    <div class="device-list">
        <h5>Registered Devices:</h5>
        <ul>
            <li>
                <span class="device-info">Chrome on Windows</span>
                <span class="device-date">Added: Jan 15, 2024</span>
            </li>
            <li>
                <span class="device-info">Safari on MacOS</span>
                <span class="device-date">Added: Jan 20, 2024</span>
            </li>
        </ul>
    </div>
</div>
```

## 7. CSS Styling Framework

### 7.1 Main Stylesheet (admin/css/admin.css)
```css
/* VD License Manager Admin Styles */

.vd-license-manager-wrap {
    margin: 20px 0;
}

/* Dashboard Statistics Cards */
.vd-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.vd-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #0073aa;
    line-height: 1;
}

.stat-label {
    color: #666;
    margin-top: 8px;
    font-size: 14px;
}

.stat-change {
    font-size: 12px;
    margin-top: 5px;
}

.stat-change.positive {
    color: #46b450;
}

.stat-change.negative {
    color: #dc3232;
}

/* Provider Cards */
.vd-provider-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.vd-provider-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 20px;
    transition: box-shadow 0.2s;
}

.vd-provider-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.provider-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

/* Risk Score Indicators */
.risk-score {
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: bold;
    font-size: 11px;
}

.risk-score.low {
    background: #d4edda;
    color: #155724;
}

.risk-score.medium {
    background: #fff3cd;
    color: #856404;
}

.risk-score.high {
    background: #f8d7da;
    color: #721c24;
}

/* Modal Styles */
.vd-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.vd-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 6px;
    width: 600px;
    max-width: 90%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.vd-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vd-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.vd-modal-close:hover {
    color: #333;
}

/* Form Styles */
.vd-license-form,
.vd-provider-form {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-group small {
    color: #666;
    font-style: italic;
}

/* Charts Container */
.vd-charts-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 30px;
}

.chart-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .vd-stats-grid {
        grid-template-columns: 1fr;
    }

    .vd-provider-grid {
        grid-template-columns: 1fr;
    }

    .vd-charts-container {
        grid-template-columns: 1fr;
    }

    .vd-modal-content {
        width: 95%;
        margin: 2% auto;
    }
}
```

## 8. JavaScript Functionality

### 8.1 Admin JavaScript (admin/js/admin.js)
```javascript
jQuery(document).ready(function($) {

    // License key generation
    $('#generate-key').on('click', function() {
        const key = generateLicenseKey();
        $('#license_key').val(key);
    });

    function generateLicenseKey() {
        const segments = [];
        for (let i = 0; i < 3; i++) {
            segments.push(Math.random().toString(36).substr(2, 4).toUpperCase());
        }
        return 'VD-' + segments.join('-');
    }

    // Modal handling
    $('.vd-modal-trigger').on('click', function(e) {
        e.preventDefault();
        const modalId = $(this).data('modal');
        $('#' + modalId).show();
    });

    $('.vd-modal-close').on('click', function() {
        $('.vd-modal').hide();
    });

    // Provider testing
    $('.test-provider').on('click', function() {
        const providerId = $(this).data('provider-id');
        testProviderAccount(providerId);
    });

    function testProviderAccount(providerId) {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'vd_test_provider',
                provider_id: providerId,
                nonce: vd_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Provider test successful', 'success');
                } else {
                    showNotice('Provider test failed: ' + response.data, 'error');
                }
            }
        });
    }

    // Real-time dashboard updates
    if ($('#vd-dashboard').length) {
        setInterval(updateDashboardStats, 30000); // Update every 30 seconds
    }

    function updateDashboardStats() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'vd_get_dashboard_stats',
                nonce: vd_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatCards(response.data);
                }
            }
        });
    }

    // Chart initialization with Chart.js
    if ($('#resolution-trends-chart').length) {
        initResolutionTrendsChart();
    }

    function initResolutionTrendsChart() {
        const ctx = document.getElementById('resolution-trends-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [], // Will be populated via AJAX
                datasets: [{
                    label: 'Successful Resolutions',
                    data: [],
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0,115,170,0.1)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

});
```

## 9. User Experience Guidelines

### 9.1 Loading States
- Show spinner indicators during AJAX requests
- Disable form submit buttons during processing
- Display progress bars for bulk operations

### 9.2 Error Handling
- Display user-friendly error messages
- Provide clear instructions for resolving issues
- Log detailed errors for admin review

### 9.3 Accessibility
- Use semantic HTML elements
- Provide ARIA labels for screen readers
- Ensure keyboard navigation works properly
- Maintain sufficient color contrast ratios

### 9.4 Mobile Responsiveness
- Stack dashboard cards on mobile devices
- Make tables horizontally scrollable
- Ensure modal dialogs fit mobile screens
- Use touch-friendly button sizes

This frontend specification provides a complete user interface framework for the VD License Manager plugin, covering both administrative interfaces and customer-facing components with modern, responsive design patterns.
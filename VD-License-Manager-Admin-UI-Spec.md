# VD License Manager - Admin UI Specifications

## Overview
Comprehensive admin interface specifications for the VD License Manager plugin, providing role-based access to license management, account configuration, audit trails, and system monitoring.

## Menu Structure & Navigation

### Main Menu Items
```
VD License Manager
├── Dashboard                    (manage_licenses)
├── Licenses                     (manage_licenses)
│   ├── All Licenses
│   ├── Add New License
│   └── Import/Export
├── Provider Accounts            (manage_provider_accounts)
│   ├── All Accounts
│   ├── Add New Account
│   └── Assignment Rules
├── Products                     (manage_products)
│   ├── All Products
│   ├── Add New Product
│   └── Field Sharing
├── Users & Devices             (view_license_users)
│   ├── Licensed Users
│   ├── Device Management
│   └── Usage Reports
├── Rate Limiting               (manage_rate_limits)
│   ├── Global Settings
│   ├── License Rules
│   └── IP Blacklist
├── Audit & Logs               (view_audit_logs)
│   ├── System Audit
│   ├── Access Logs
│   └── Error Reports
└── Settings                   (manage_vd_settings)
    ├── General
    ├── Security
    └── Performance
```

## Role & Capability Management

### Custom Capabilities
```php
// Core capabilities
'manage_licenses'           => 'Manage all license operations'
'view_licenses'            => 'View license information'
'edit_licenses'            => 'Edit existing licenses'
'delete_licenses'          => 'Delete licenses'

// Provider account capabilities
'manage_provider_accounts' => 'Manage provider accounts'
'view_provider_accounts'   => 'View provider account info'
'assign_accounts'          => 'Manual account assignment'
'reveal_credentials'       => 'View plain text credentials'

// Product capabilities
'manage_products'          => 'Manage products and settings'
'configure_field_sharing'  => 'Configure field sharing rules'

// User & device capabilities
'view_license_users'       => 'View licensed users'
'manage_devices'           => 'Approve/reject devices'
'view_usage_reports'       => 'Access usage statistics'

// Rate limiting capabilities
'manage_rate_limits'       => 'Configure rate limiting'
'view_rate_limits'         => 'View rate limit status'

// Audit capabilities
'view_audit_logs'          => 'Access audit trails'
'export_audit_data'        => 'Export audit information'

// System capabilities
'manage_vd_settings'       => 'System configuration'
```

### Role Assignments
```php
// Administrator - Full access
$admin_caps = [
    'manage_licenses', 'manage_provider_accounts', 'manage_products',
    'view_license_users', 'manage_devices', 'manage_rate_limits',
    'view_audit_logs', 'export_audit_data', 'manage_vd_settings',
    'reveal_credentials', 'assign_accounts'
];

// License Manager - Core operations
$manager_caps = [
    'manage_licenses', 'view_provider_accounts', 'manage_products',
    'view_license_users', 'manage_devices', 'view_audit_logs'
];

// Support Staff - Limited access
$support_caps = [
    'view_licenses', 'view_license_users', 'view_audit_logs'
];
```

## Screen Designs & Components

### 1. Dashboard Screen

**Layout**: 4-column responsive grid
**URL**: `/wp-admin/admin.php?page=vd-license-dashboard`

#### Widgets
```html
<div class="vd-dashboard-widgets">
    <!-- Statistics Cards -->
    <div class="vd-widget vd-stats-card">
        <h3>License Statistics</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number">{total_licenses}</span>
                <span class="stat-label">Total Licenses</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{active_licenses}</span>
                <span class="stat-label">Active</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{expired_licenses}</span>
                <span class="stat-label">Expired</span>
            </div>
        </div>
    </div>

    <!-- Provider Account Health -->
    <div class="vd-widget vd-account-health">
        <h3>Provider Account Status</h3>
        <div class="account-list">
            {foreach provider_accounts}
            <div class="account-item status-{status}">
                <span class="account-name">{provider_name}</span>
                <span class="account-status">{status}</span>
                <span class="usage-count">{assignment_count} assigned</span>
            </div>
            {endforeach}
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="vd-widget vd-recent-activity">
        <h3>Recent Activity</h3>
        <div class="activity-feed">
            {foreach recent_audit_logs limit=10}
            <div class="activity-item">
                <span class="activity-time">{time_ago}</span>
                <span class="activity-action">{action}</span>
                <span class="activity-user">{user_login}</span>
            </div>
            {endforeach}
        </div>
    </div>

    <!-- System Health -->
    <div class="vd-widget vd-system-health">
        <h3>System Health</h3>
        <div class="health-indicators">
            <div class="health-item">
                <span class="indicator status-{db_status}"></span>
                <span>Database</span>
            </div>
            <div class="health-item">
                <span class="indicator status-{cache_status}"></span>
                <span>Cache</span>
            </div>
            <div class="health-item">
                <span class="indicator status-{rate_limit_status}"></span>
                <span>Rate Limiting</span>
            </div>
        </div>
    </div>
</div>
```

### 2. License Management Screen

**Layout**: List table with advanced filtering
**URL**: `/wp-admin/admin.php?page=vd-licenses`

#### List Table Columns
```php
$columns = [
    'license_key'    => 'License Key',
    'product'        => 'Product',
    'user'          => 'Licensed User',
    'provider'      => 'Provider Account',
    'status'        => 'Status',
    'devices'       => 'Devices',
    'expires'       => 'Expires',
    'created'       => 'Created',
    'actions'       => 'Actions'
];
```

#### Advanced Filters
```html
<div class="vd-license-filters">
    <select name="filter_product">
        <option value="">All Products</option>
        {foreach products}
        <option value="{id}">{name}</option>
        {endforeach}
    </select>

    <select name="filter_status">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="expired">Expired</option>
        <option value="suspended">Suspended</option>
    </select>

    <select name="filter_provider">
        <option value="">All Providers</option>
        {foreach providers}
        <option value="{id}">{name}</option>
        {endforeach}
    </select>

    <input type="text" name="filter_user" placeholder="User email/username">
    <input type="date" name="filter_created_from" placeholder="Created from">
    <input type="date" name="filter_created_to" placeholder="Created to">

    <button type="submit" class="button">Filter</button>
    <button type="button" class="button" id="clear-filters">Clear</button>
</div>
```

#### Bulk Actions
```html
<select name="bulk_action">
    <option value="">Bulk Actions</option>
    <option value="activate">Activate</option>
    <option value="suspend">Suspend</option>
    <option value="extend">Extend Expiration</option>
    <option value="reassign">Reassign Provider</option>
    <option value="delete">Delete</option>
    <option value="export">Export Selected</option>
</select>
```

### 3. Provider Account Management Screen

**Layout**: Card-based layout with search and filtering
**URL**: `/wp-admin/admin.php?page=vd-provider-accounts`

#### Account Card Design
```html
<div class="vd-provider-card status-{status}">
    <div class="card-header">
        <h4 class="provider-name">{provider_name}</h4>
        <span class="status-badge status-{status}">{status_label}</span>
    </div>

    <div class="card-body">
        <div class="account-info">
            <div class="info-row">
                <span class="label">Account Type:</span>
                <span class="value">{account_type}</span>
            </div>
            <div class="info-row">
                <span class="label">Assigned Licenses:</span>
                <span class="value">{assignment_count}</span>
            </div>
            <div class="info-row">
                <span class="label">Last Used:</span>
                <span class="value">{last_used}</span>
            </div>
        </div>

        <div class="credential-section">
            <h5>Account Credentials
                <button class="reveal-btn" data-account-id="{id}">
                    <span class="reveal-text">Reveal</span>
                    <span class="hide-text" style="display:none">Hide</span>
                </button>
            </h5>

            <div class="credential-fields" style="display:none">
                {foreach account_fields}
                <div class="credential-row">
                    <label>{field_label}:</label>
                    <input type="text" value="{field_value}" readonly>
                    <button class="copy-btn" data-value="{field_value}">Copy</button>
                </div>
                {endforeach}
            </div>
        </div>
    </div>

    <div class="card-actions">
        <button class="button button-primary edit-account" data-id="{id}">Edit</button>
        <button class="button test-account" data-id="{id}">Test Connection</button>
        <button class="button assign-manual" data-id="{id}">Manual Assign</button>
        {if can_delete}
        <button class="button button-link-delete delete-account" data-id="{id}">Delete</button>
        {endif}
    </div>
</div>
```

### 4. Product Configuration Screen

**Layout**: Tabbed interface
**URL**: `/wp-admin/admin.php?page=vd-products`

#### Product Form Tabs
```html
<div class="vd-product-tabs">
    <ul class="tab-nav">
        <li><a href="#basic-info" class="active">Basic Info</a></li>
        <li><a href="#field-sharing">Field Sharing</a></li>
        <li><a href="#device-limits">Device Limits</a></li>
        <li><a href="#rate-limits">Rate Limits</a></li>
    </ul>

    <div class="tab-content">
        <!-- Basic Info Tab -->
        <div id="basic-info" class="tab-pane active">
            <table class="form-table">
                <tr>
                    <th>Product Name</th>
                    <td><input type="text" name="product_name" value="{product_name}" required></td>
                </tr>
                <tr>
                    <th>Provider</th>
                    <td>
                        <select name="provider_id" required>
                            <option value="">Select Provider</option>
                            {foreach providers}
                            <option value="{id}" {selected}>{name}</option>
                            {endforeach}
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <select name="status">
                            <option value="active" {selected}>Active</option>
                            <option value="inactive" {selected}>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Field Sharing Tab -->
        <div id="field-sharing" class="tab-pane">
            <h3>Configure Shared Fields</h3>
            <p>Select which account fields customers can see when using this product:</p>

            <div class="field-sharing-config">
                {foreach available_fields}
                <div class="field-option">
                    <label>
                        <input type="checkbox" name="shared_fields[]" value="{field_key}" {checked}>
                        <span class="field-name">{field_label}</span>
                        <span class="field-description">{field_description}</span>
                    </label>
                </div>
                {endforeach}
            </div>

            <div class="sharing-preview">
                <h4>Preview - Customer Will See:</h4>
                <div class="preview-box">
                    {foreach selected_fields}
                    <div class="preview-field">
                        <span class="label">{field_label}:</span>
                        <span class="value">[Field Value]</span>
                    </div>
                    {endforeach}
                </div>
            </div>
        </div>

        <!-- Device Limits Tab -->
        <div id="device-limits" class="tab-pane">
            <table class="form-table">
                <tr>
                    <th>Max Devices</th>
                    <td>
                        <input type="number" name="max_devices" value="{max_devices}" min="1" max="100">
                        <p class="description">Maximum devices allowed per license</p>
                    </td>
                </tr>
                <tr>
                    <th>Auto-Approval Threshold</th>
                    <td>
                        <input type="number" name="auto_approval_threshold" value="{auto_approval_threshold}" min="0" max="100">
                        <p class="description">Risk score threshold for automatic device approval (0-100)</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Rate Limits Tab -->
        <div id="rate-limits" class="tab-pane">
            <table class="form-table">
                <tr>
                    <th>Requests per Hour</th>
                    <td><input type="number" name="requests_per_hour" value="{requests_per_hour}"></td>
                </tr>
                <tr>
                    <th>Requests per Day</th>
                    <td><input type="number" name="requests_per_day" value="{requests_per_day}"></td>
                </tr>
                <tr>
                    <th>Burst Limit</th>
                    <td><input type="number" name="burst_limit" value="{burst_limit}"></td>
                </tr>
            </table>
        </div>
    </div>
</div>
```

## Credential Reveal/Mask Functionality

### Security Implementation
```javascript
// Credential reveal with audit logging
class VDCredentialManager {
    constructor() {
        this.revealedAccounts = new Set();
        this.initEventListeners();
    }

    initEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.reveal-btn')) {
                this.toggleReveal(e.target);
            }
            if (e.target.matches('.copy-btn')) {
                this.copyCredential(e.target);
            }
        });
    }

    async toggleReveal(button) {
        const accountId = button.dataset.accountId;
        const credentialSection = button.closest('.card-body').querySelector('.credential-fields');
        const revealText = button.querySelector('.reveal-text');
        const hideText = button.querySelector('.hide-text');

        if (this.revealedAccounts.has(accountId)) {
            // Hide credentials
            credentialSection.style.display = 'none';
            revealText.style.display = 'inline';
            hideText.style.display = 'none';
            this.revealedAccounts.delete(accountId);

            // Log hide action
            await this.logCredentialAccess(accountId, 'hide');
        } else {
            // Show confirmation dialog
            if (!await this.confirmReveal()) {
                return;
            }

            // Load and reveal credentials
            const credentials = await this.loadCredentials(accountId);
            this.populateCredentials(credentialSection, credentials);

            credentialSection.style.display = 'block';
            revealText.style.display = 'none';
            hideText.style.display = 'inline';
            this.revealedAccounts.add(accountId);

            // Log reveal action
            await this.logCredentialAccess(accountId, 'reveal');

            // Auto-hide after 5 minutes
            setTimeout(() => {
                if (this.revealedAccounts.has(accountId)) {
                    button.click();
                }
            }, 300000);
        }
    }

    async confirmReveal() {
        return new Promise((resolve) => {
            const modal = this.createConfirmationModal();
            modal.show();

            modal.onConfirm = () => {
                modal.hide();
                resolve(true);
            };

            modal.onCancel = () => {
                modal.hide();
                resolve(false);
            };
        });
    }

    createConfirmationModal() {
        const modal = document.createElement('div');
        modal.className = 'vd-modal-overlay';
        modal.innerHTML = `
            <div class="vd-modal">
                <div class="modal-header">
                    <h3>Reveal Credentials</h3>
                </div>
                <div class="modal-body">
                    <p><strong>Warning:</strong> You are about to reveal sensitive account credentials.</p>
                    <p>This action will be logged for security audit purposes.</p>
                    <ul>
                        <li>Only reveal credentials when necessary</li>
                        <li>Do not share or screenshot credentials</li>
                        <li>Credentials will auto-hide after 5 minutes</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button class="button button-primary confirm-btn">Reveal Credentials</button>
                    <button class="button cancel-btn">Cancel</button>
                </div>
            </div>
        `;

        const confirmBtn = modal.querySelector('.confirm-btn');
        const cancelBtn = modal.querySelector('.cancel-btn');

        confirmBtn.addEventListener('click', () => modal.onConfirm?.());
        cancelBtn.addEventListener('click', () => modal.onCancel?.());

        modal.show = () => {
            document.body.appendChild(modal);
            modal.style.display = 'flex';
        };

        modal.hide = () => {
            modal.remove();
        };

        return modal;
    }

    async loadCredentials(accountId) {
        const response = await fetch(`${ajaxurl}?action=vd_get_account_credentials`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `account_id=${accountId}&_wpnonce=${vd_nonce}`
        });

        return await response.json();
    }

    populateCredentials(container, credentials) {
        const fields = container.querySelectorAll('.credential-row');
        fields.forEach(row => {
            const input = row.querySelector('input');
            const fieldKey = input.dataset.fieldKey;
            if (credentials[fieldKey]) {
                input.value = credentials[fieldKey];
            }
        });
    }

    async copyCredential(button) {
        const value = button.dataset.value;
        await navigator.clipboard.writeText(value);

        // Show feedback
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('copied');

        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('copied');
        }, 2000);

        // Log copy action
        const accountId = button.closest('.vd-provider-card').dataset.accountId;
        await this.logCredentialAccess(accountId, 'copy');
    }

    async logCredentialAccess(accountId, action) {
        await fetch(`${ajaxurl}?action=vd_log_credential_access`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `account_id=${accountId}&action=${action}&_wpnonce=${vd_nonce}`
        });
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new VDCredentialManager();
});
```

### PHP Backend Support
```php
// AJAX handler for credential reveal
add_action('wp_ajax_vd_get_account_credentials', 'vd_handle_get_credentials');

function vd_handle_get_credentials() {
    // Verify nonce and capabilities
    if (!wp_verify_nonce($_POST['_wpnonce'], 'vd_admin_nonce') ||
        !current_user_can('reveal_credentials')) {
        wp_die('Access denied');
    }

    $account_id = intval($_POST['account_id']);

    // Get decrypted credentials from bz_vd_provider_accounts table
    $account = vd_get_provider_account($account_id);
    if (!$account) {
        wp_send_json_error('Account not found');
    }

    // Decrypt and return credentials
    $credentials = vd_decrypt_account_credentials($account);

    // Log access to bz_vd_audit_logs table
    vd_log_audit_event([
        'action' => 'credential_reveal',
        'object_type' => 'provider_account',
        'object_id' => $account_id,
        'user_id' => get_current_user_id(),
        'ip_address' => vd_get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'details' => [
            'account_name' => $account->account_name,
            'provider_name' => $account->provider_name
        ]
    ]);

    wp_send_json_success($credentials);
}

// AJAX handler for credential access logging
add_action('wp_ajax_vd_log_credential_access', 'vd_handle_log_credential_access');

function vd_handle_log_credential_access() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'vd_admin_nonce')) {
        wp_die('Access denied');
    }

    $account_id = intval($_POST['account_id']);
    $action = sanitize_text_field($_POST['action']);

    $allowed_actions = ['reveal', 'hide', 'copy'];
    if (!in_array($action, $allowed_actions)) {
        wp_send_json_error('Invalid action');
    }

    // Log to bz_vd_audit_logs table
    vd_log_audit_event([
        'action' => "credential_{$action}",
        'object_type' => 'provider_account',
        'object_id' => $account_id,
        'user_id' => get_current_user_id(),
        'ip_address' => vd_get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    wp_send_json_success();
}
```

## Audit Trail Integration

### Audit Log Viewer
**URL**: `/wp-admin/admin.php?page=vd-audit-logs`

```html
<div class="vd-audit-viewer">
    <!-- Filters -->
    <div class="audit-filters">
        <select name="filter_action">
            <option value="">All Actions</option>
            <option value="credential_reveal">Credential Reveal</option>
            <option value="credential_hide">Credential Hide</option>
            <option value="credential_copy">Credential Copy</option>
            <option value="license_create">License Create</option>
            <option value="account_assign">Account Assign</option>
        </select>

        <select name="filter_user">
            <option value="">All Users</option>
            {foreach admin_users}
            <option value="{id}">{display_name}</option>
            {endforeach}
        </select>

        <input type="datetime-local" name="filter_date_from">
        <input type="datetime-local" name="filter_date_to">

        <button class="button">Filter</button>
        <button class="button export-audit">Export CSV</button>
    </div>

    <!-- Audit Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Object</th>
                <th>IP Address</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            {foreach audit_logs}
            <tr class="audit-row severity-{severity}">
                <td>{created_at}</td>
                <td>
                    <strong>{user_display_name}</strong>
                    <br><small>{user_login}</small>
                </td>
                <td>
                    <span class="action-badge action-{action}">{action_label}</span>
                </td>
                <td>
                    {object_type} #{object_id}
                    {if object_name}<br><small>{object_name}</small>{endif}
                </td>
                <td>{ip_address}</td>
                <td>
                    <button class="button-link view-details" data-id="{id}">
                        View Details
                    </button>
                </td>
            </tr>
            {endforeach}
        </tbody>
    </table>
</div>

<!-- Details Modal -->
<div id="audit-details-modal" class="vd-modal-overlay" style="display:none">
    <div class="vd-modal audit-modal">
        <div class="modal-header">
            <h3>Audit Log Details</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="audit-details-content">
                <!-- Populated via AJAX -->
            </div>
        </div>
    </div>
</div>
```

## Settings & Configuration

### General Settings Tab
```html
<div class="vd-settings-section">
    <h3>License Configuration</h3>
    <table class="form-table">
        <tr>
            <th>Default License Duration</th>
            <td>
                <input type="number" name="default_license_days" value="{default_days}">
                <span class="unit">days</span>
            </td>
        </tr>
        <tr>
            <th>License Key Format</th>
            <td>
                <select name="license_key_format">
                    <option value="uuid">UUID (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)</option>
                    <option value="base32">Base32 (XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX)</option>
                    <option value="custom">Custom Pattern</option>
                </select>
                <div id="custom-pattern" style="display:none">
                    <input type="text" name="custom_pattern" placeholder="e.g., VD-{8}-{4}-{4}">
                    <p class="description">Use {n} for random alphanumeric segments</p>
                </div>
            </td>
        </tr>
        <tr>
            <th>Assignment Algorithm</th>
            <td>
                <select name="assignment_algorithm">
                    <option value="least_loaded">Least Loaded</option>
                    <option value="round_robin">Round Robin</option>
                    <option value="sequential">Sequential</option>
                    <option value="random">Random</option>
                </select>
            </td>
        </tr>
    </table>
</div>

<div class="vd-settings-section">
    <h3>Device Management</h3>
    <table class="form-table">
        <tr>
            <th>Default Device Limit</th>
            <td><input type="number" name="default_device_limit" value="{limit}" min="1"></td>
        </tr>
        <tr>
            <th>Auto-Approval Threshold</th>
            <td>
                <input type="range" name="auto_approval_threshold" min="0" max="100" value="{threshold}">
                <span class="range-value">{threshold}</span>
                <p class="description">Risk score threshold for automatic device approval</p>
            </td>
        </tr>
        <tr>
            <th>Device Fingerprinting</th>
            <td>
                <fieldset>
                    <label><input type="checkbox" name="fingerprint_browser" {checked}> Browser Info</label>
                    <label><input type="checkbox" name="fingerprint_screen" {checked}> Screen Resolution</label>
                    <label><input type="checkbox" name="fingerprint_timezone" {checked}> Timezone</label>
                    <label><input type="checkbox" name="fingerprint_language" {checked}> Language</label>
                </fieldset>
            </td>
        </tr>
    </table>
</div>
```

### Security Settings Tab
```html
<div class="vd-settings-section">
    <h3>Encryption Settings</h3>
    <table class="form-table">
        <tr>
            <th>Encryption Method</th>
            <td>
                <select name="encryption_method">
                    <option value="aes-256-gcm" selected>AES-256-GCM (Recommended)</option>
                    <option value="aes-256-cbc">AES-256-CBC</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>Encryption Key Status</th>
            <td>
                <span class="key-status status-{key_status}">{key_status_label}</span>
                <button class="button regenerate-key">Regenerate Key</button>
                <p class="description">
                    <strong>Warning:</strong> Regenerating the encryption key will make existing encrypted data unreadable.
                </p>
            </td>
        </tr>
    </table>
</div>

<div class="vd-settings-section">
    <h3>API Security</h3>
    <table class="form-table">
        <tr>
            <th>Rate Limiting</th>
            <td>
                <label><input type="checkbox" name="enable_rate_limiting" {checked}> Enable Rate Limiting</label>
            </td>
        </tr>
        <tr>
            <th>IP Whitelist</th>
            <td>
                <textarea name="ip_whitelist" rows="5" placeholder="One IP per line&#10;192.168.1.0/24&#10;10.0.0.1">{ip_whitelist}</textarea>
                <p class="description">Leave empty to allow all IPs. Use CIDR notation for ranges.</p>
            </td>
        </tr>
        <tr>
            <th>Failed Login Protection</th>
            <td>
                <input type="number" name="max_failed_attempts" value="{max_attempts}" min="1">
                <span>attempts before</span>
                <input type="number" name="lockout_duration" value="{lockout_minutes}" min="1">
                <span>minute lockout</span>
            </td>
        </tr>
    </table>
</div>
```

## Responsive Design & Accessibility

### CSS Framework Integration
```css
/* Responsive breakpoints */
@media (max-width: 1200px) {
    .vd-dashboard-widgets {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .vd-dashboard-widgets {
        grid-template-columns: 1fr;
    }

    .vd-provider-card {
        margin-bottom: 1rem;
    }

    .card-actions {
        flex-direction: column;
        gap: 0.5rem;
    }

    .vd-audit-viewer .audit-filters {
        flex-direction: column;
        gap: 0.5rem;
    }
}

/* Accessibility improvements */
.screen-reader-text {
    clip: rect(1px, 1px, 1px, 1px);
    position: absolute !important;
    height: 1px;
    width: 1px;
    overflow: hidden;
}

.vd-modal {
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.vd-modal:focus {
    outline: 2px solid #0073aa;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .status-badge,
    .action-badge {
        border: 2px solid currentColor;
    }

    .vd-provider-card {
        border: 2px solid #666;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .vd-modal,
    .credential-fields {
        transition: none;
    }
}
```

## Acceptance Criteria

### Functional Requirements

1. **Menu Navigation**
   - [ ] All menu items display correctly based on user capabilities
   - [ ] Menu structure follows WordPress admin conventions
   - [ ] Breadcrumb navigation shows current location
   - [ ] Menu highlights active section

2. **Role-Based Access Control**
   - [ ] Custom capabilities integrate with WordPress roles
   - [ ] Users see only permitted menu items and functions
   - [ ] Capability checks prevent unauthorized access to AJAX endpoints
   - [ ] Role assignment follows principle of least privilege

3. **Dashboard Functionality**
   - [ ] Statistics widgets display accurate real-time data
   - [ ] Provider account health updates every 5 minutes
   - [ ] Recent activity shows last 10 audit log entries
   - [ ] System health indicators reflect actual system status

4. **License Management**
   - [ ] List table supports pagination (25/50/100 per page)
   - [ ] Advanced filters work correctly and persist across pages
   - [ ] Bulk actions process selected items with confirmation
   - [ ] Export functionality generates CSV with all selected data

5. **Provider Account Management**
   - [ ] Account cards display all relevant information
   - [ ] Status badges reflect real-time account status
   - [ ] Search and filtering work across all fields
   - [ ] Test connection validates account credentials

6. **Credential Security**
   - [ ] Credentials hidden by default on page load
   - [ ] Reveal requires confirmation dialog with warning
   - [ ] All credential access logged to audit trail
   - [ ] Auto-hide after 5 minutes for security
   - [ ] Copy functionality works in all modern browsers

7. **Product Configuration**
   - [ ] Tabbed interface saves state between switches
   - [ ] Field sharing preview updates in real-time
   - [ ] Form validation prevents invalid configurations
   - [ ] Changes require confirmation before saving

### Security Requirements

1. **Authentication & Authorization**
   - [ ] All admin pages require appropriate capabilities
   - [ ] AJAX endpoints verify nonces and permissions
   - [ ] Session timeout enforced for sensitive operations
   - [ ] Failed login attempts tracked and limited

2. **Audit Logging**
   - [ ] All sensitive actions logged with full context
   - [ ] Log entries include timestamp, user, IP, and details
   - [ ] Audit logs tamper-evident and append-only
   - [ ] Export functionality restricted to authorized users

3. **Data Protection**
   - [ ] Credentials encrypted at rest using AES-256-GCM
   - [ ] Decryption only occurs when explicitly requested
   - [ ] No credentials stored in browser localStorage/sessionStorage
   - [ ] Copy operations don't expose data in developer tools

### Performance Requirements

1. **Page Load Performance**
   - [ ] Dashboard loads within 2 seconds on standard hardware
   - [ ] List tables paginate efficiently with large datasets
   - [ ] AJAX operations complete within 1 second
   - [ ] Database queries use appropriate indexes

2. **Scalability**
   - [ ] Admin interface handles 10,000+ licenses efficiently
   - [ ] Provider account cards virtualize for large numbers
   - [ ] Audit log viewer supports millions of entries
   - [ ] Search operations remain responsive with growth

### Usability Requirements

1. **User Experience**
   - [ ] Interface follows WordPress admin design patterns
   - [ ] Forms provide clear validation feedback
   - [ ] Loading states indicate progress for long operations
   - [ ] Error messages provide actionable guidance

2. **Accessibility**
   - [ ] All interactive elements keyboard accessible
   - [ ] Screen readers can navigate full interface
   - [ ] Color contrast meets WCAG 2.1 AA standards
   - [ ] Focus indicators visible and distinctive

3. **Responsive Design**
   - [ ] Interface usable on tablets (768px+)
   - [ ] Mobile optimization for critical functions
   - [ ] Touch targets minimum 44px for mobile
   - [ ] Text readable without horizontal scrolling

### Integration Requirements

1. **WordPress Integration**
   - [ ] Uses WordPress admin styles and components
   - [ ] Integrates with WordPress user management
   - [ ] Follows WordPress coding standards
   - [ ] Compatible with multisite installations

2. **Plugin Compatibility**
   - [ ] No conflicts with common admin plugins
   - [ ] Proper enqueueing of scripts and styles
   - [ ] Hooks available for extensibility
   - [ ] Translation-ready with proper text domains

## Implementation Notes

### File Structure
```
wp-content/plugins/vd-license-manager/
├── admin/
│   ├── css/
│   │   ├── admin-styles.css
│   │   └── admin-responsive.css
│   ├── js/
│   │   ├── admin-scripts.js
│   │   ├── credential-manager.js
│   │   └── audit-viewer.js
│   ├── pages/
│   │   ├── dashboard.php
│   │   ├── licenses.php
│   │   ├── provider-accounts.php
│   │   ├── products.php
│   │   ├── users-devices.php
│   │   ├── rate-limiting.php
│   │   ├── audit-logs.php
│   │   └── settings.php
│   └── includes/
│       ├── class-admin-menu.php
│       ├── class-list-tables.php
│       └── class-ajax-handlers.php
```

### Development Phases

**Phase 1: Core Structure**
- Set up menu system and basic pages
- Implement capability system
- Create base CSS framework

**Phase 2: License Management**
- Build license list table
- Add filtering and bulk actions
- Implement CRUD operations

**Phase 3: Provider Management**
- Create account card layout
- Implement credential reveal/mask
- Add audit logging integration

**Phase 4: Advanced Features**
- Product configuration interface
- Rate limiting management
- Audit log viewer

**Phase 5: Polish & Testing**
- Responsive design optimization
- Accessibility improvements
- Performance optimization
- Security testing
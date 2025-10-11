# LỘ TRÌNH TRIỂN KHAI PLUGIN VD LICENSE MANAGER

## 📋 TỔNG QUAN DỰ ÁN

**Mục tiêu:** Triển khai plugin WordPress quản lý license keys tích hợp WooCommerce + LMfWC  
**Thời gian:** 16 ngày làm việc (~3 tuần)  
**Phương pháp:** Incremental Development - Test sau mỗi milestone  
**Stack:** WordPress, PHP 8.1+, MySQL 8.0+, WooCommerce, LMfWC

---

## 🎯 CÁC MILESTONE CHÍNH

| Milestone | Thời gian | Deliverable | Test được gì |
|-----------|-----------|-------------|--------------|
| M1 | Ngày 1-2 | Database + Admin UI | Menu, Tables, CRUD Pools/Accounts |
| M2 | Ngày 3-5 | LMfWC Sync | Order completed → License sync |
| M3 | Ngày 6-9 | Portal API | Customer access license |
| M4 | Ngày 10-11 | Device Tracking | Multi-device management |
| M5 | Ngày 12-13 | Portal UI | Frontend portal page |
| M6 | Ngày 14 | Email System | Auto send license email |
| M7 | Ngày 15-16 | Admin Dashboard | Stats, Analytics, Management |

---

# 📅 NGÀY 1-2: DATABASE + ADMIN UI

## NGÀY 1: Database Schema & Menu Structure

### 🎯 Mục tiêu
- Tạo đầy đủ database tables với proper indexes
- Register WordPress Admin Menu
- Setup plugin activation/deactivation hooks

### 📝 Tasks

#### Task 1.1: Plugin Structure Setup (2h)
```
File structure cần tạo:
vd-license-manager/
├── vd-license-manager.php          (Main plugin file)
├── includes/
│   ├── class-vd-activator.php      (Activation hooks)
│   ├── class-vd-deactivator.php    (Deactivation hooks)
│   ├── class-vd-database.php       (Database schema)
│   └── class-vd-admin-menu.php     (Admin menu registration)
├── admin/
│   ├── css/
│   │   └── vd-admin.css
│   ├── js/
│   │   └── vd-admin.js
│   └── partials/
│       ├── dashboard.php
│       ├── pools.php
│       ├── accounts.php
│       └── licenses.php
└── assets/
    └── icon.png
```

**Checklist:**
- [ ] Tạo plugin header với metadata
- [ ] Setup autoloader hoặc require files
- [ ] Register activation hook
- [ ] Register deactivation hook
- [ ] Plugin có thể activate trong WP Admin

**Test:**
```bash
# WordPress Admin
1. Vào Plugins → Installed Plugins
2. Tìm "VD License Manager"
3. Click "Activate"
   ✅ Plugin activate thành công
   ✅ Không có error messages
```

---

#### Task 1.2: Database Schema Creation (3h)

**Tables cần tạo:**

##### 1. bz_vd_product_pools
```sql
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bz_vd_product_pools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'WooCommerce Product ID',
  `pool_name` VARCHAR(255) NOT NULL,
  `capacity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Max accounts in pool',
  `assigned_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Current assigned licenses',
  `status` ENUM('active', 'inactive', 'full', 'expired') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `idx_capacity` (`capacity`, `assigned_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

##### 2. bz_vd_product_share_configs
```sql
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bz_vd_product_share_configs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `max_devices` INT UNSIGNED NOT NULL DEFAULT 2,
  `validity_days` INT UNSIGNED NOT NULL DEFAULT 30,
  `max_requests_per_day` INT UNSIGNED NOT NULL DEFAULT 10,
  `allow_vps` TINYINT(1) NOT NULL DEFAULT 0,
  `selected_credential_fields` JSON DEFAULT NULL COMMENT 'Fields to show to customer',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

##### 3. bz_vd_provider_accounts
```sql
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bz_vd_provider_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pool_id` BIGINT UNSIGNED NOT NULL,
  `provider` VARCHAR(100) NOT NULL COMMENT 'Netflix, Spotify, etc',
  `credentials` JSON NOT NULL COMMENT 'All account credentials',
  `account_fields` JSON DEFAULT NULL COMMENT 'Custom fields: webhook_url, etc',
  `status` ENUM('active', 'inactive', 'expired', 'error') NOT NULL DEFAULT 'active',
  `expires_at` DATETIME DEFAULT NULL,
  `last_credential_update` DATETIME DEFAULT NULL,
  `next_credential_update` DATETIME DEFAULT NULL,
  `internal_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pool_id` (`pool_id`),
  KEY `idx_provider` (`provider`),
  KEY `idx_status` (`status`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_account_pool` FOREIGN KEY (`pool_id`) 
    REFERENCES `{$wpdb->prefix}bz_vd_product_pools` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

##### 4. bz_vd_license_keys
```sql
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bz_vd_license_keys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `license_key` VARCHAR(19) NOT NULL COMMENT 'A3F9-K2L4-M8N1-P5Q7',
  `lmfwc_license_id` BIGINT UNSIGNED NOT NULL COMMENT 'Link to LMfWC table',
  `product_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('active', 'expired', 'suspended', 'revoked') NOT NULL DEFAULT 'active',
  `valid_from` DATETIME NOT NULL,
  `valid_until` DATETIME NOT NULL,
  `assigned_pool_id` BIGINT UNSIGNED DEFAULT NULL,
  `assigned_account_id` BIGINT UNSIGNED DEFAULT NULL,
  `assigned_at` DATETIME DEFAULT NULL,
  `last_accessed_at` DATETIME DEFAULT NULL,
  `access_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_license_key` (`license_key`),
  UNIQUE KEY `idx_lmfwc_license_id` (`lmfwc_license_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_valid_until` (`valid_until`),
  KEY `idx_assigned_pool` (`assigned_pool_id`),
  CONSTRAINT `fk_license_pool` FOREIGN KEY (`assigned_pool_id`) 
    REFERENCES `{$wpdb->prefix}bz_vd_product_pools` (`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_license_account` FOREIGN KEY (`assigned_account_id`) 
    REFERENCES `{$wpdb->prefix}bz_vd_provider_accounts` (`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

##### 5. bz_vd_license_devices
```sql
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bz_vd_license_devices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `license_id` BIGINT UNSIGNED NOT NULL,
  `device_combined_id` VARCHAR(64) NOT NULL COMMENT 'SHA256 hash',
  `device_fingerprint` VARCHAR(64) NOT NULL,
  `device_token` VARCHAR(20) NOT NULL,
  `device_name` VARCHAR(255) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `is_vps` TINYINT(1) NOT NULL DEFAULT 0,
  `vps_provider` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'blocked', 'removed') NOT NULL DEFAULT 'active',
  `first_access_at` DATETIME NOT NULL,
  `last_access_at` DATETIME NOT NULL,
  `access_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_license_device` (`license_id`, `device_combined_id`),
  KEY `idx_device_combined_id` (`device_combined_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_vps` (`is_vps`),
  CONSTRAINT `fk_device_license` FOREIGN KEY (`license_id`) 
    REFERENCES `{$wpdb->prefix}bz_vd_license_keys` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

##### 6. bz_vd_device_access_log
```sql
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bz_vd_device_access_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `license_id` BIGINT UNSIGNED NOT NULL,
  `device_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `request_type` VARCHAR(50) NOT NULL COMMENT 'access, register_device, etc',
  `result` ENUM('success', 'blocked', 'error') NOT NULL,
  `error_code` VARCHAR(50) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `is_vps` TINYINT(1) NOT NULL DEFAULT 0,
  `vps_provider` VARCHAR(100) DEFAULT NULL,
  `response_data` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_license_id` (`license_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_result` (`result`),
  KEY `idx_is_vps` (`is_vps`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Code Implementation:**
```php
// includes/class-vd-database.php

class VD_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // SQL statements for all 6 tables
        // (Use the SQL above)
        
        dbDelta($sql_pools);
        dbDelta($sql_configs);
        dbDelta($sql_accounts);
        dbDelta($sql_licenses);
        dbDelta($sql_devices);
        dbDelta($sql_logs);
        
        // Store database version
        add_option('vd_db_version', '1.0.0');
        
        // Log creation
        error_log('VD License Manager: Database tables created successfully');
    }
    
    public static function drop_tables() {
        global $wpdb;
        
        $tables = [
            'bz_vd_device_access_log',
            'bz_vd_license_devices',
            'bz_vd_license_keys',
            'bz_vd_provider_accounts',
            'bz_vd_product_share_configs',
            'bz_vd_product_pools'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
        
        delete_option('vd_db_version');
    }
}
```

**Checklist:**
- [ ] SQL syntax check - no errors
- [ ] All 6 tables defined correctly
- [ ] Foreign keys properly set
- [ ] Indexes on frequently queried columns
- [ ] Activation hook calls create_tables()
- [ ] Deactivation hook optional (không drop tables)

**Test Database Creation:**
```bash
# Step 1: Activate plugin
WordPress Admin → Plugins → Activate "VD License Manager"

# Step 2: Check phpMyAdmin/Adminer
Database: wp_database
Tables should exist:
✅ wp_bz_vd_product_pools
✅ wp_bz_vd_product_share_configs
✅ wp_bz_vd_provider_accounts
✅ wp_bz_vd_license_keys
✅ wp_bz_vd_license_devices
✅ wp_bz_vd_device_access_log

# Step 3: Verify structure
For each table:
✅ Click table → Structure
✅ Check columns match schema
✅ Check indexes exist
✅ Check foreign keys (Relations tab)

# Step 4: Test constraints
Try manual insert:
INSERT INTO wp_bz_vd_provider_accounts (pool_id, provider, credentials)
VALUES (999, 'Test', '{}');

Expected: ERROR - Foreign key constraint fails (pool_id 999 doesn't exist)
✅ Constraints working

# Step 5: Check wp_options
SELECT * FROM wp_options WHERE option_name = 'vd_db_version';
Expected: option_value = '1.0.0'
✅ Version stored
```

---

#### Task 1.3: Admin Menu Registration (2h)

**Code:**
```php
// includes/class-vd-admin-menu.php

class VD_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function register_menu() {
        // Main menu
        add_menu_page(
            'VD License Manager',                    // Page title
            'VD License',                            // Menu title
            'manage_options',                        // Capability
            'vd-license-manager',                    // Menu slug
            array($this, 'render_dashboard_page'),   // Callback
            'dashicons-tickets-alt',                 // Icon
            30                                       // Position (after Comments)
        );
        
        // Submenu: Dashboard (duplicate of main)
        add_submenu_page(
            'vd-license-manager',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'vd-license-manager',
            array($this, 'render_dashboard_page')
        );
        
        // Submenu: Pools
        add_submenu_page(
            'vd-license-manager',
            'Pools Management',
            'Pools',
            'manage_options',
            'vd-pools',
            array($this, 'render_pools_page')
        );
        
        // Submenu: Accounts
        add_submenu_page(
            'vd-license-manager',
            'Provider Accounts',
            'Accounts',
            'manage_options',
            'vd-accounts',
            array($this, 'render_accounts_page')
        );
        
        // Submenu: Licenses
        add_submenu_page(
            'vd-license-manager',
            'License Keys',
            'Licenses',
            'manage_options',
            'vd-licenses',
            array($this, 'render_licenses_page')
        );
        
        // Submenu: Devices
        add_submenu_page(
            'vd-license-manager',
            'Device Management',
            'Devices',
            'manage_options',
            'vd-devices',
            array($this, 'render_devices_page')
        );
        
        // Submenu: Logs
        add_submenu_page(
            'vd-license-manager',
            'Access Logs',
            'Logs',
            'manage_options',
            'vd-logs',
            array($this, 'render_logs_page')
        );
        
        // Submenu: Settings
        add_submenu_page(
            'vd-license-manager',
            'Settings',
            'Settings',
            'manage_options',
            'vd-settings',
            array($this, 'render_settings_page')
        );
    }
    
    // Placeholder page renders (Day 1)
    public function render_dashboard_page() {
        echo '<div class="wrap">';
        echo '<h1>VD License Manager - Dashboard</h1>';
        echo '<p>Dashboard coming on Day 15-16...</p>';
        echo '</div>';
    }
    
    public function render_pools_page() {
        require_once VD_PLUGIN_DIR . 'admin/partials/pools.php';
    }
    
    public function render_accounts_page() {
        require_once VD_PLUGIN_DIR . 'admin/partials/accounts.php';
    }
    
    public function render_licenses_page() {
        echo '<div class="wrap">';
        echo '<h1>License Keys</h1>';
        echo '<p>License management coming on Day 3-5...</p>';
        echo '</div>';
    }
    
    public function render_devices_page() {
        echo '<div class="wrap">';
        echo '<h1>Device Management</h1>';
        echo '<p>Device management coming on Day 10-11...</p>';
        echo '</div>';
    }
    
    public function render_logs_page() {
        echo '<div class="wrap">';
        echo '<h1>Access Logs</h1>';
        echo '<p>Logs viewer coming on Day 15-16...</p>';
        echo '</div>';
    }
    
    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>Settings</h1>';
        echo '<p>Settings coming soon...</p>';
        echo '</div>';
    }
    
    public function enqueue_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'vd-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'vd-admin-css',
            VD_PLUGIN_URL . 'admin/css/vd-admin.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'vd-admin-js',
            VD_PLUGIN_URL . 'admin/js/vd-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('vd-admin-js', 'vdAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vd_admin_nonce')
        ));
    }
}

// Initialize
new VD_Admin_Menu();
```

**Basic CSS (admin/css/vd-admin.css):**
```css
/* VD License Manager Admin Styles */

.vd-wrap {
    margin: 20px 20px 0 0;
}

.vd-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.vd-page-header h1 {
    margin: 0;
}

.vd-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.vd-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.vd-stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #646970;
    font-weight: normal;
}

.vd-stat-card .stat-value {
    font-size: 32px;
    font-weight: 600;
    color: #1d2327;
    margin: 0;
}

.vd-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.vd-table thead {
    background: #f6f7f7;
}

.vd-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 1px solid #ccd0d4;
}

.vd-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f1;
}

.vd-table tr:hover {
    background: #f6f7f7;
}

.vd-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.vd-badge.active {
    background: #d4edda;
    color: #155724;
}

.vd-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.vd-btn-primary {
    background: #2271b1;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 3px;
    cursor: pointer;
}

.vd-btn-primary:hover {
    background: #135e96;
}

.vd-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.vd-empty-state .dashicons {
    font-size: 48px;
    color: #c3c4c7;
    margin-bottom: 10px;
}
```

**Checklist:**
- [ ] Menu registered in admin_menu hook
- [ ] All submenu items added
- [ ] Icon shows correctly (dashicons-tickets-alt)
- [ ] CSS file created with basic styles
- [ ] JS file created (empty for now)
- [ ] Assets enqueued only on plugin pages

**Test Menu:**
```bash
# WordPress Admin
1. Check left sidebar
   ✅ "VD License" menu visible
   ✅ Icon shows (ticket icon)
   ✅ Position after Comments menu

2. Hover on "VD License"
   ✅ Submenu expands showing:
       - Dashboard
       - Pools
       - Accounts
       - Licenses
       - Devices
       - Logs
       - Settings

3. Click "Dashboard"
   ✅ Page loads
   ✅ Shows "Dashboard coming soon..."
   ✅ No PHP errors

4. Click each submenu item
   ✅ All pages load
   ✅ Placeholder text shows
   ✅ No errors

5. Check browser console (F12)
   ✅ vd-admin.css loaded
   ✅ vd-admin.js loaded
   ✅ No 404 errors

6. Check page source
   ✅ vdAdmin object available
   ✅ ajaxUrl defined
   ✅ nonce available
```

---

### 📊 End of Day 1 Deliverables

**Completed:**
- ✅ Plugin structure created
- ✅ 6 database tables created with proper schema
- ✅ Admin menu registered with 7 pages
- ✅ Basic CSS framework
- ✅ All pages accessible (placeholders)

**Admin can test:**
1. Plugin activation works
2. Database tables exist
3. Menu appears in sidebar
4. All submenu pages load

**Screenshots to capture:**
1. Plugin activated successfully
2. phpMyAdmin showing all 6 tables
3. WordPress Admin with VD License menu
4. Each submenu page (placeholders)

---

## NGÀY 2: Pools & Accounts Management UI

### 🎯 Mục tiêu
- Tạo giao diện quản lý Pools (List, Add, Edit, Delete)
- Tạo giao diện quản lý Accounts (List, Add, Edit, Delete)
- AJAX handlers cho CRUD operations
- Admin có thể thêm/sửa/xóa Pools và Accounts

### 📝 Tasks

#### Task 2.1: Pools List Page (3h)

**File: admin/partials/pools.php**

```php
<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get all pools
global $wpdb;
$pools_table = $wpdb->prefix . 'bz_vd_product_pools';
$accounts_table = $wpdb->prefix . 'bz_vd_provider_accounts';

// Get pools with account counts
$pools = $wpdb->get_results("
    SELECT 
        p.*,
        COUNT(a.id) as account_count
    FROM {$pools_table} p
    LEFT JOIN {$accounts_table} a ON p.id = a.pool_id AND a.status = 'active'
    GROUP BY p.id
    ORDER BY p.id DESC
");

// Calculate stats
$total_pools = count($pools);
$total_capacity = array_sum(array_column($pools, 'capacity'));
$total_assigned = array_sum(array_column($pools, 'assigned_count'));
?>

<div class="wrap vd-wrap">
    <div class="vd-page-header">
        <h1>Pools Management</h1>
        <button class="button button-primary" id="vd-add-pool-btn">
            <span class="dashicons dashicons-plus-alt"></span> Add New Pool
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="vd-stats-cards">
        <div class="vd-stat-card">
            <h3>Total Pools</h3>
            <p class="stat-value"><?php echo $total_pools; ?></p>
        </div>
        <div class="vd-stat-card">
            <h3>Total Capacity</h3>
            <p class="stat-value"><?php echo $total_capacity; ?></p>
        </div>
        <div class="vd-stat-card">
            <h3>Slots Used</h3>
            <p class="stat-value"><?php echo $total_assigned; ?> / <?php echo $total_capacity; ?></p>
        </div>
        <div class="vd-stat-card">
            <h3>Available Slots</h3>
            <p class="stat-value"><?php echo $total_capacity - $total_assigned; ?></p>
        </div>
    </div>

    <!-- Pools Table -->
    <?php if (empty($pools)): ?>
        <div class="vd-empty-state">
            <span class="dashicons dashicons-portfolio"></span>
            <h2>No pools found</h2>
            <p>Click "Add New Pool" to create your first pool.</p>
        </div>
    <?php else: ?>
        <table class="vd-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pool Name</th>
                    <th>Product ID</th>
                    <th>Capacity</th>
                    <th>Used / Available</th>
                    <th>Accounts</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pools as $pool): 
                    $available = $pool->capacity - $pool->assigned_count;
                    $usage_percent = $pool->capacity > 0 ? ($pool->assigned_count / $pool->capacity) * 100 : 0;
                    $status_class = $pool->status;
                    if ($available == 0) $status_class = 'full';
                ?>
                <tr>
                    <td><?php echo $pool->id; ?></td>
                    <td><strong><?php echo esc_html($pool->pool_name); ?></strong></td>
                    <td><?php echo $pool->product_id; ?></td>
                    <td><?php echo $pool->capacity; ?></td>
                    <td>
                        <?php echo $pool->assigned_count; ?> / <?php echo $available; ?>
                        <div style="width:100px; height:4px; background:#ddd; margin-top:4px;">
                            <div style="width:<?php echo $usage_percent; ?>%; height:100%; background:<?php echo $usage_percent >= 80 ? '#dc3545' : '#28a745'; ?>;"></div>
                        </div>
                    </td>
                    <td><?php echo $pool->account_count; ?></td>
                    <td>
                        <span class="vd-badge <?php echo $status_class; ?>">
                            <?php echo ucfirst($pool->status); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($pool->created_at)); ?></td>
                    <td>
                        <button class="button button-small vd-edit-pool" data-pool-id="<?php echo $pool->id; ?>">Edit</button>
                        <button class="button button-small button-link-delete vd-delete-pool" data-pool-id="<?php echo $pool->id; ?>">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Add/Edit Pool Modal -->
<div id="vd-pool-modal" style="display:none;">
    <div class="vd-modal-backdrop"></div>
    <div class="vd-modal-content">
        <div class="vd-modal-header">
            <h2 id="vd-pool-modal-title">Add New Pool</h2>
            <button class="vd-modal-close">&times;</button>
        </div>
        <div class="vd-modal-body">
            <form id="vd-pool-form">
                <input type="hidden" id="pool-id" name="pool_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="pool-name">Pool Name <span class="required">*</span></label></th>
                        <td>
                            <input type="text" id="pool-name" name="pool_name" class="regular-text" required>
                            <p class="description">Example: Netflix Pool 1, Spotify Premium Pool</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="product-id">Product ID <span class="required">*</span></label></th>
                        <td>
                            <input type="number" id="product-id" name="product_id" class="small-text" required>
                            <p class="description">WooCommerce Product ID. Check Products page.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="capacity">Capacity <span class="required">*</span></label></th>
                        <td>
                            <input type="number" id="capacity" name="capacity" class="small-text" min="1" required>
                            <p class="description">Maximum number of accounts in this pool.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status">Status</label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="vd-modal-footer">
            <button class="button" id="vd-pool-cancel">Cancel</button>
            <button class="button button-primary" id="vd-pool-save">Save Pool</button>
        </div>
    </div>
</div>

<style>
.vd-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
}
.vd-modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    width: 600px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 100001;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.vd-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}
.vd-modal-header h2 {
    margin: 0;
}
.vd-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
}
.vd-modal-body {
    padding: 20px;
}
.vd-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}
.vd-modal-footer .button {
    margin-left: 10px;
}
.required {
    color: #dc3545;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add Pool button
    $('#vd-add-pool-btn').on('click', function() {
        $('#vd-pool-modal-title').text('Add New Pool');
        $('#vd-pool-form')[0].reset();
        $('#pool-id').val('');
        $('#vd-pool-modal').show();
    });
    
    // Edit Pool button
    $('.vd-edit-pool').on('click', function() {
        var poolId = $(this).data('pool-id');
        
        // AJAX get pool data
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_get_pool',
                nonce: vdAdmin.nonce,
                pool_id: poolId
            },
            success: function(response) {
                if (response.success) {
                    var pool = response.data;
                    $('#vd-pool-modal-title').text('Edit Pool');
                    $('#pool-id').val(pool.id);
                    $('#pool-name').val(pool.pool_name);
                    $('#product-id').val(pool.product_id);
                    $('#capacity').val(pool.capacity);
                    $('#status').val(pool.status);
                    $('#vd-pool-modal').show();
                }
            }
        });
    });
    
    // Close modal
    $('.vd-modal-close, #vd-pool-cancel').on('click', function() {
        $('#vd-pool-modal').hide();
    });
    
    // Save Pool
    $('#vd-pool-save').on('click', function() {
        var formData = {
            action: 'vd_save_pool',
            nonce: vdAdmin.nonce,
            pool_id: $('#pool-id').val(),
            pool_name: $('#pool-name').val(),
            product_id: $('#product-id').val(),
            capacity: $('#capacity').val(),
            status: $('#status').val()
        };
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Pool saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    // Delete Pool
    $('.vd-delete-pool').on('click', function() {
        if (!confirm('Are you sure you want to delete this pool?')) {
            return;
        }
        
        var poolId = $(this).data('pool-id');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_delete_pool',
                nonce: vdAdmin.nonce,
                pool_id: poolId
            },
            success: function(response) {
                if (response.success) {
                    alert('Pool deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
});
</script>
```

**Checklist:**
- [ ] Pools list displays correctly
- [ ] Stats cards show accurate counts
- [ ] Empty state shows when no pools
- [ ] Add button opens modal
- [ ] Edit button loads pool data
- [ ] Delete button shows confirmation

---

#### Task 2.2: Pools AJAX Handlers (2h)

**File: includes/class-vd-pools-ajax.php**

```php
<?php

class VD_Pools_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_vd_get_pool', array($this, 'get_pool'));
        add_action('wp_ajax_vd_save_pool', array($this, 'save_pool'));
        add_action('wp_ajax_vd_delete_pool', array($this, 'delete_pool'));
    }
    
    public function get_pool() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $pool_id = intval($_POST['pool_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_pools';
        
        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $pool_id
        ), ARRAY_A);
        
        if (!$pool) {
            wp_send_json_error(['message' => 'Pool not found']);
        }
        
        wp_send_json_success($pool);
    }
    
    public function save_pool() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        // Validate input
        $pool_id = intval($_POST['pool_id']);
        $pool_name = sanitize_text_field($_POST['pool_name']);
        $product_id = intval($_POST['product_id']);
        $capacity = intval($_POST['capacity']);
        $status = sanitize_text_field($_POST['status']);
        
        if (empty($pool_name) || $product_id <= 0 || $capacity <= 0) {
            wp_send_json_error(['message' => 'Invalid input data']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_pools';
        
        $data = [
            'pool_name' => $pool_name,
            'product_id' => $product_id,
            'capacity' => $capacity,
            'status' => $status
        ];
        
        if ($pool_id > 0) {
            // Update existing pool
            $result = $wpdb->update(
                $table,
                $data,
                ['id' => $pool_id],
                ['%s', '%d', '%d', '%s'],
                ['%d']
            );
            
            if ($result === false) {
                wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            }
            
            wp_send_json_success(['message' => 'Pool updated successfully', 'pool_id' => $pool_id]);
            
        } else {
            // Insert new pool
            $data['assigned_count'] = 0;
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                $table,
                $data,
                ['%s', '%d', '%d', '%s', '%d', '%s']
            );
            
            if ($result === false) {
                wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            }
            
            wp_send_json_success(['message' => 'Pool created successfully', 'pool_id' => $wpdb->insert_id]);
        }
    }
    
    public function delete_pool() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $pool_id = intval($_POST['pool_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_pools';
        
        // Check if pool has accounts
        $accounts_table = $wpdb->prefix . 'bz_vd_provider_accounts';
        $account_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$accounts_table} WHERE pool_id = %d",
            $pool_id
        ));
        
        if ($account_count > 0) {
            wp_send_json_error(['message' => "Cannot delete pool with {$account_count} accounts. Remove accounts first."]);
        }
        
        $result = $wpdb->delete($table, ['id' => $pool_id], ['%d']);
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
        }
        
        wp_send_json_success(['message' => 'Pool deleted successfully']);
    }
}

// Initialize
new VD_Pools_Ajax();
```

**Checklist:**
- [ ] AJAX handlers registered
- [ ] Security nonce checks implemented
- [ ] User capability checks
- [ ] Input validation
- [ ] Database operations working
- [ ] Error handling

---

#### Task 2.3: Accounts Management UI (3h)

**File: admin/partials/accounts.php**

```php
<?php
// Similar structure to pools.php but for accounts
// Table shows: ID, Provider, Pool, Credentials (masked), Status, Expires At, Actions
// Modal form includes: Provider, Pool dropdown, Credentials (JSON textarea or fields), Expiration date

// Get all accounts with pool info
global $wpdb;
$accounts_table = $wpdb->prefix . 'bz_vd_provider_accounts';
$pools_table = $wpdb->prefix . 'bz_vd_product_pools';

$accounts = $wpdb->get_results("
    SELECT 
        a.*,
        p.pool_name,
        p.product_id
    FROM {$accounts_table} a
    LEFT JOIN {$pools_table} p ON a.pool_id = p.id
    ORDER BY a.id DESC
");

// Get pools for dropdown
$pools = $wpdb->get_results("
    SELECT id, pool_name, product_id 
    FROM {$pools_table} 
    WHERE status = 'active'
    ORDER BY pool_name ASC
");
?>

<div class="wrap vd-wrap">
    <div class="vd-page-header">
        <h1>Provider Accounts</h1>
        <button class="button button-primary" id="vd-add-account-btn">
            <span class="dashicons dashicons-plus-alt"></span> Add New Account
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="vd-stats-cards">
        <div class="vd-stat-card">
            <h3>Total Accounts</h3>
            <p class="stat-value"><?php echo count($accounts); ?></p>
        </div>
        <div class="vd-stat-card">
            <h3>Active</h3>
            <p class="stat-value"><?php echo count(array_filter($accounts, fn($a) => $a->status === 'active')); ?></p>
        </div>
        <div class="vd-stat-card">
            <h3>Expired</h3>
            <p class="stat-value"><?php echo count(array_filter($accounts, fn($a) => $a->status === 'expired')); ?></p>
        </div>
        <div class="vd-stat-card">
            <h3>Providers</h3>
            <p class="stat-value"><?php echo count(array_unique(array_column($accounts, 'provider'))); ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="vd-filters" style="margin-bottom:20px;">
        <select id="filter-pool">
            <option value="">All Pools</option>
            <?php foreach ($pools as $pool): ?>
                <option value="<?php echo $pool->id; ?>"><?php echo esc_html($pool->pool_name); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filter-provider">
            <option value="">All Providers</option>
            <?php 
            $providers = array_unique(array_column($accounts, 'provider'));
            foreach ($providers as $provider): 
            ?>
                <option value="<?php echo esc_attr($provider); ?>"><?php echo esc_html($provider); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filter-status">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="expired">Expired</option>
            <option value="error">Error</option>
        </select>
    </div>

    <!-- Accounts Table -->
    <?php if (empty($accounts)): ?>
        <div class="vd-empty-state">
            <span class="dashicons dashicons-groups"></span>
            <h2>No accounts found</h2>
            <p>Click "Add New Account" to add your first provider account.</p>
        </div>
    <?php else: ?>
        <table class="vd-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Provider</th>
                    <th>Pool</th>
                    <th>Credentials</th>
                    <th>Status</th>
                    <th>Expires At</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): 
                    $credentials = json_decode($account->credentials, true);
                    $cred_preview = '';
                    if (isset($credentials['email'])) {
                        $cred_preview = substr($credentials['email'], 0, 15) . '...';
                    } elseif (isset($credentials['account_login'])) {
                        $cred_preview = substr($credentials['account_login'], 0, 15) . '...';
                    } else {
                        $cred_preview = count($credentials) . ' fields';
                    }
                ?>
                <tr data-pool-id="<?php echo $account->pool_id; ?>" 
                    data-provider="<?php echo esc_attr($account->provider); ?>" 
                    data-status="<?php echo esc_attr($account->status); ?>">
                    <td><?php echo $account->id; ?></td>
                    <td><strong><?php echo esc_html($account->provider); ?></strong></td>
                    <td><?php echo esc_html($account->pool_name); ?></td>
                    <td>
                        <?php echo esc_html($cred_preview); ?>
                        <button class="button button-small vd-view-credentials" data-account-id="<?php echo $account->id; ?>">
                            <span class="dashicons dashicons-visibility"></span> View
                        </button>
                    </td>
                    <td>
                        <span class="vd-badge <?php echo $account->status; ?>">
                            <?php echo ucfirst($account->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        if ($account->expires_at) {
                            echo date('M d, Y', strtotime($account->expires_at));
                            $days_until = floor((strtotime($account->expires_at) - time()) / 86400);
                            if ($days_until < 7 && $days_until >= 0) {
                                echo '<br><span style="color:#dc3545;">Expires in ' . $days_until . ' days</span>';
                            } elseif ($days_until < 0) {
                                echo '<br><span style="color:#dc3545;">Expired</span>';
                            }
                        } else {
                            echo 'No expiration';
                        }
                        ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($account->created_at)); ?></td>
                    <td>
                        <button class="button button-small vd-edit-account" data-account-id="<?php echo $account->id; ?>">Edit</button>
                        <button class="button button-small button-link-delete vd-delete-account" data-account-id="<?php echo $account->id; ?>">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Add/Edit Account Modal -->
<div id="vd-account-modal" style="display:none;">
    <div class="vd-modal-backdrop"></div>
    <div class="vd-modal-content" style="width:700px;">
        <div class="vd-modal-header">
            <h2 id="vd-account-modal-title">Add New Account</h2>
            <button class="vd-modal-close">&times;</button>
        </div>
        <div class="vd-modal-body">
            <form id="vd-account-form">
                <input type="hidden" id="account-id" name="account_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="provider">Provider <span class="required">*</span></label></th>
                        <td>
                            <select id="provider" name="provider" class="regular-text" required>
                                <option value="">-- Select Provider --</option>
                                <option value="Netflix">Netflix</option>
                                <option value="Spotify">Spotify</option>
                                <option value="YouTube Premium">YouTube Premium</option>
                                <option value="Canva Pro">Canva Pro</option>
                                <option value="ChatGPT Plus">ChatGPT Plus</option>
                                <option value="Custom">Custom (Other)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pool-id">Pool <span class="required">*</span></label></th>
                        <td>
                            <select id="pool-id" name="pool_id" class="regular-text" required>
                                <option value="">-- Select Pool --</option>
                                <?php foreach ($pools as $pool): ?>
                                    <option value="<?php echo $pool->id; ?>">
                                        <?php echo esc_html($pool->pool_name); ?> (Product #<?php echo $pool->product_id; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($pools)): ?>
                                <p class="description" style="color:#dc3545;">No pools available. Please create a pool first.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2"><h3 style="margin:0;">Credentials</h3></th>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <p class="description">Enter credentials as JSON format. Common fields: email, password, profile_name, pin_code, cookie</p>
                            <textarea id="credentials" name="credentials" rows="10" class="large-text code" required placeholder='{
  "email": "account@example.com",
  "password": "yourpassword",
  "profile_name": "Profile 1"
}'></textarea>
                            <p class="description">Or use simple fields below (will auto-convert to JSON):</p>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:10px;">
                                <div>
                                    <label>Email/Login:</label>
                                    <input type="text" id="cred-email" class="regular-text" placeholder="account@example.com">
                                </div>
                                <div>
                                    <label>Password:</label>
                                    <input type="text" id="cred-password" class="regular-text" placeholder="password123">
                                </div>
                                <div>
                                    <label>Profile Name:</label>
                                    <input type="text" id="cred-profile" class="regular-text" placeholder="Profile 1">
                                </div>
                                <div>
                                    <label>PIN Code:</label>
                                    <input type="text" id="cred-pin" class="regular-text" placeholder="1234">
                                </div>
                            </div>
                            <button type="button" class="button" id="build-json-btn" style="margin-top:10px;">Build JSON from Fields</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="expires-at">Expires At</label></th>
                        <td>
                            <input type="date" id="expires-at" name="expires_at">
                            <p class="description">Optional: Set expiration date for this account</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="account-status">Status</label></th>
                        <td>
                            <select id="account-status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="expired">Expired</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="internal-notes">Internal Notes</label></th>
                        <td>
                            <textarea id="internal-notes" name="internal_notes" rows="3" class="large-text" placeholder="Private admin notes (not visible to customers)"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="vd-modal-footer">
            <button class="button" id="vd-account-cancel">Cancel</button>
            <button class="button button-primary" id="vd-account-save">Save Account</button>
        </div>
    </div>
</div>

<!-- View Credentials Modal -->
<div id="vd-credentials-modal" style="display:none;">
    <div class="vd-modal-backdrop"></div>
    <div class="vd-modal-content" style="width:500px;">
        <div class="vd-modal-header">
            <h2>Account Credentials</h2>
            <button class="vd-modal-close">&times;</button>
        </div>
        <div class="vd-modal-body">
            <div id="credentials-display"></div>
        </div>
        <div class="vd-modal-footer">
            <button class="button" id="vd-credentials-close">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Build JSON from simple fields
    $('#build-json-btn').on('click', function() {
        var json = {};
        if ($('#cred-email').val()) json.email = $('#cred-email').val();
        if ($('#cred-password').val()) json.password = $('#cred-password').val();
        if ($('#cred-profile').val()) json.profile_name = $('#cred-profile').val();
        if ($('#cred-pin').val()) json.pin_code = $('#cred-pin').val();
        
        $('#credentials').val(JSON.stringify(json, null, 2));
    });
    
    // Filters
    function filterTable() {
        var poolId = $('#filter-pool').val();
        var provider = $('#filter-provider').val();
        var status = $('#filter-status').val();
        
        $('.vd-table tbody tr').each(function() {
            var show = true;
            
            if (poolId && $(this).data('pool-id') != poolId) show = false;
            if (provider && $(this).data('provider') != provider) show = false;
            if (status && $(this).data('status') != status) show = false;
            
            $(this).toggle(show);
        });
    }
    
    $('#filter-pool, #filter-provider, #filter-status').on('change', filterTable);
    
    // Add Account
    $('#vd-add-account-btn').on('click', function() {
        $('#vd-account-modal-title').text('Add New Account');
        $('#vd-account-form')[0].reset();
        $('#account-id').val('');
        $('#credentials').val('');
        $('#vd-account-modal').show();
    });
    
    // Edit Account
    $('.vd-edit-account').on('click', function() {
        var accountId = $(this).data('account-id');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_get_account',
                nonce: vdAdmin.nonce,
                account_id: accountId
            },
            success: function(response) {
                if (response.success) {
                    var acc = response.data;
                    $('#vd-account-modal-title').text('Edit Account');
                    $('#account-id').val(acc.id);
                    $('#provider').val(acc.provider);
                    $('#pool-id').val(acc.pool_id);
                    $('#credentials').val(acc.credentials);
                    $('#expires-at').val(acc.expires_at ? acc.expires_at.split(' ')[0] : '');
                    $('#account-status').val(acc.status);
                    $('#internal-notes').val(acc.internal_notes);
                    $('#vd-account-modal').show();
                }
            }
        });
    });
    
    // View Credentials
    $('.vd-view-credentials').on('click', function() {
        var accountId = $(this).data('account-id');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_get_account',
                nonce: vdAdmin.nonce,
                account_id: accountId
            },
            success: function(response) {
                if (response.success) {
                    var creds = JSON.parse(response.data.credentials);
                    var html = '<table class="widefat"><tbody>';
                    
                    for (var key in creds) {
                        var value = creds[key];
                        var isSensitive = ['password', 'pin', 'cookie', 'token'].some(s => key.toLowerCase().includes(s));
                        var displayValue = value;
                        
                        if (isSensitive) {
                            displayValue = '<span class="masked">' + '•'.repeat(value.length) + '</span>';
                            displayValue += ' <button class="button button-small toggle-visibility" data-value="' + value + '">Show</button>';
                        }
                        
                        html += '<tr>';
                        html += '<th style="width:150px;">' + key + '</th>';
                        html += '<td>' + displayValue;
                        html += ' <button class="button button-small copy-btn" data-value="' + value + '">Copy</button>';
                        html += '</td>';
                        html += '</tr>';
                    }
                    
                    html += '</tbody></table>';
                    $('#credentials-display').html(html);
                    $('#vd-credentials-modal').show();
                }
            }
        });
    });
    
    // Toggle password visibility
    $(document).on('click', '.toggle-visibility', function() {
        var btn = $(this);
        var span = btn.prev('.masked');
        var value = btn.data('value');
        
        if (btn.text() === 'Show') {
            span.text(value);
            btn.text('Hide');
        } else {
            span.text('•'.repeat(value.length));
            btn.text('Show');
        }
    });
    
    // Copy to clipboard
    $(document).on('click', '.copy-btn', function() {
        var value = $(this).data('value');
        navigator.clipboard.writeText(value).then(function() {
            alert('Copied to clipboard!');
        });
    });
    
    // Close modals
    $('.vd-modal-close, #vd-account-cancel, #vd-credentials-close').on('click', function() {
        $('#vd-account-modal').hide();
        $('#vd-credentials-modal').hide();
    });
    
    // Save Account
    $('#vd-account-save').on('click', function() {
        // Validate JSON
        try {
            JSON.parse($('#credentials').val());
        } catch (e) {
            alert('Invalid JSON format in credentials field');
            return;
        }
        
        var formData = {
            action: 'vd_save_account',
            nonce: vdAdmin.nonce,
            account_id: $('#account-id').val(),
            provider: $('#provider').val(),
            pool_id: $('#pool-id').val(),
            credentials: $('#credentials').val(),
            expires_at: $('#expires-at').val(),
            status: $('#account-status').val(),
            internal_notes: $('#internal-notes').val()
        };
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Account saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    // Delete Account
    $('.vd-delete-account').on('click', function() {
        if (!confirm('Are you sure you want to delete this account?')) {
            return;
        }
        
        var accountId = $(this).data('account-id');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_delete_account',
                nonce: vdAdmin.nonce,
                account_id: accountId
            },
            success: function(response) {
                if (response.success) {
                    alert('Account deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
});
</script>
```

**Checklist:**
- [ ] Accounts list with pool info
- [ ] Stats cards accurate
- [ ] Filters working (pool, provider, status)
- [ ] Add/Edit modal with all fields
- [ ] JSON credentials input
- [ ] Simple fields → JSON builder
- [ ] View credentials modal
- [ ] Password masking/toggle
- [ ] Copy to clipboard

---

#### Task 2.4: Accounts AJAX Handlers (2h)

**File: includes/class-vd-accounts-ajax.php**

```php
<?php

class VD_Accounts_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_vd_get_account', array($this, 'get_account'));
        add_action('wp_ajax_vd_save_account', array($this, 'save_account'));
        add_action('wp_ajax_vd_delete_account', array($this, 'delete_account'));
    }
    
    public function get_account() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $account_id = intval($_POST['account_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_provider_accounts';
        
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $account_id
        ), ARRAY_A);
        
        if (!$account) {
            wp_send_json_error(['message' => 'Account not found']);
        }
        
        wp_send_json_success($account);
    }
    
    public function save_account() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        // Validate input
        $account_id = intval($_POST['account_id']);
        $provider = sanitize_text_field($_POST['provider']);
        $pool_id = intval($_POST['pool_id']);
        $credentials = $_POST['credentials']; // Already JSON string
        $expires_at = !empty($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null;
        $status = sanitize_text_field($_POST['status']);
        $internal_notes = sanitize_textarea_field($_POST['internal_notes']);
        
        // Validate JSON
        $cred_array = json_decode($credentials, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON format in credentials']);
        }
        
        if (empty($provider) || $pool_id <= 0 || empty($credentials)) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_provider_accounts';
        
        $data = [
            'provider' => $provider,
            'pool_id' => $pool_id,
            'credentials' => $credentials,
            'status' => $status,
            'internal_notes' => $internal_notes,
            'last_credential_update' => current_time('mysql')
        ];
        
        if ($expires_at) {
            $data['expires_at'] = $expires_at;
        }
        
        if ($account_id > 0) {
            // Update
            $result = $wpdb->update(
                $table,
                $data,
                ['id' => $account_id]
            );
            
            if ($result === false) {
                wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            }
            
            wp_send_json_success(['message' => 'Account updated', 'account_id' => $account_id]);
            
        } else {
            // Insert
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert($table, $data);
            
            if ($result === false) {
                wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            }
            
            wp_send_json_success(['message' => 'Account created', 'account_id' => $wpdb->insert_id]);
        }
    }
    
    public function delete_account() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $account_id = intval($_POST['account_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_provider_accounts';
        
        // Check if account is assigned to any licenses
        $licenses_table = $wpdb->prefix . 'bz_vd_license_keys';
        $license_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$licenses_table} WHERE assigned_account_id = %d",
            $account_id
        ));
        
        if ($license_count > 0) {
            wp_send_json_error(['message' => "Cannot delete account assigned to {$license_count} licenses. Unassign first."]);
        }
        
        $result = $wpdb->delete($table, ['id' => $account_id], ['%d']);
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
        }
        
        wp_send_json_success(['message' => 'Account deleted successfully']);
    }
}

// Initialize
new VD_Accounts_Ajax();
```

---

### 📊 End of Day 2 Deliverables

**Completed:**
- ✅ Pools Management UI (List, Add, Edit, Delete)
- ✅ Pools AJAX handlers with validation
- ✅ Accounts Management UI (List, Add, Edit, Delete, View)
- ✅ Accounts AJAX handlers
- ✅ Credentials viewer with masking
- ✅ Filters for accounts table
- ✅ JSON credential builder helper

**Admin can test:**
1. ✅ Create pools with capacity
2. ✅ Edit/delete pools
3. ✅ Add accounts to pools
4. ✅ View credentials securely
5. ✅ Edit/delete accounts
6. ✅ Filter accounts by pool/provider/status
7. ✅ Copy credentials to clipboard
8. ✅ Validate JSON format

**Test Checklist Day 2:**
```
POOLS MANAGEMENT:
1. Add Pool:
   - Name: Netflix Pool 1
   - Product ID: 100
   - Capacity: 5
   ✅ Saves successfully
   ✅ Shows in table
   ✅ Stats update

2. Edit Pool:
   ✅ Click Edit → Modal loads data
   ✅ Change capacity to 10
   ✅ Updates successfully

3. Delete Pool (without accounts):
   ✅ Shows confirmation
   ✅ Deletes successfully

4. Try delete pool with accounts:
   ✅ Shows error message
   ✅ Prevents deletion

ACCOUNTS MANAGEMENT:
1. Add Account:
   - Provider: Netflix
   - Pool: Netflix Pool 1
   - Credentials: {"email":"test@netflix.com","password":"pass123"}
   ✅ Saves successfully
   ✅ Shows in table

2. View Credentials:
   ✅ Click View → Modal shows
   ✅ Password masked
   ✅ Toggle Show/Hide works
   ✅ Copy button works

3. Edit Account:
   ✅ Click Edit → Loads data
   ✅ Modify credentials
   ✅ Updates successfully

4. JSON Builder:
   ✅ Fill simple fields
   ✅ Click "Build JSON"
   ✅ JSON textarea populated

5. Filters:
   ✅ Filter by Pool → Shows correct accounts
   ✅ Filter by Provider → Works
   ✅ Filter by Status → Works

6. Delete Account:
   ✅ Shows confirmation
   ✅ Deletes successfully

DATABASE CHECK:
✅ wp_bz_vd_product_pools has 1+ rows
✅ wp_bz_vd_provider_accounts has 1+ rows
✅ Credentials stored as valid JSON
✅ Foreign keys working (pool_id references pools.id)
```

---

### 🎉 MILESTONE 1 COMPLETE!

**Day 1-2 Summary:**
- Database schema created (6 tables)
- Admin menu with 7 pages
- Pools CRUD fully functional
- Accounts CRUD fully functional
- Beautiful UI with stats, modals, filters
- Secure credential viewing
- All AJAX with nonce security

**Ready for next milestone:** LMfWC Sync (Day 3-5)

---

# 📅 NGÀY 3-5: LMFWC SYNC

## NGÀY 3: Share Config Setup

### 🎯 Mục tiêu
- Tạo UI để admin setup Share Config cho mỗi Product
- Define max_devices, validity_days, request limits
- Tích hợp với WooCommerce Products
- Test save/retrieve configs

### 📝 Tasks

#### Task 3.1: Product Share Config UI (4h)

**Add submenu: Share Configs**

**File: admin/partials/share-configs.php**

```php
<?php
// List all WooCommerce products with their share configs
// Allow admin to set: max_devices, validity_days, max_requests_per_day, allow_vps
// UI similar to WooCommerce products list but with VD config columns

$products = wc_get_products(['limit' => -1, 'status' => 'publish']);

global $wpdb;
$configs_table = $wpdb->prefix . 'bz_vd_product_share_configs';

// Get existing configs
$existing_configs = $wpdb->get_results("SELECT * FROM {$configs_table}", OBJECT_K);
?>

<div class="wrap vd-wrap">
    <h1>Product Share Configurations</h1>
    <p class="description">Configure license sharing settings for each WooCommerce product.</p>
    
    <table class="vd-table wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Max Devices</th>
                <th>Validity (Days)</th>
                <th>Max Requests/Day</th>
                <th>Allow VPS</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): 
                $product_id = $product->get_id();
                $config = isset($existing_configs[$product_id]) ? $existing_configs[$product_id] : null;
            ?>
            <tr>
                <td><?php echo $product_id; ?></td>
                <td>
                    <strong><?php echo esc_html($product->get_name()); ?></strong>
                    <br><small><?php echo $product->get_sku() ? 'SKU: ' . $product->get_sku() : ''; ?></small>
                </td>
                <td>
                    <?php echo $config ? $config->max_devices : '<em>Not set</em>'; ?>
                </td>
                <td>
                    <?php echo $config ? $config->validity_days : '<em>Not set</em>'; ?>
                </td>
                <td>
                    <?php echo $config ? $config->max_requests_per_day : '<em>Not set</em>'; ?>
                </td>
                <td>
                    <?php 
                    if ($config) {
                        echo $config->allow_vps ? '<span class="vd-badge active">Yes</span>' : '<span class="vd-badge inactive">No</span>';
                    } else {
                        echo '<em>Not set</em>';
                    }
                    ?>
                </td>
                <td>
                    <button class="button button-small vd-config-product" data-product-id="<?php echo $product_id; ?>">
                        <?php echo $config ? 'Edit Config' : 'Set Config'; ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Config Modal -->
<div id="vd-config-modal" style="display:none;">
    <div class="vd-modal-backdrop"></div>
    <div class="vd-modal-content">
        <div class="vd-modal-header">
            <h2>Share Configuration</h2>
            <button class="vd-modal-close">&times;</button>
        </div>
        <div class="vd-modal-body">
            <form id="vd-config-form">
                <input type="hidden" id="config-product-id" name="product_id">
                
                <table class="form-table">
                    <tr>
                        <th colspan="2">
                            <h3 id="config-product-name" style="margin:0;"></h3>
                        </th>
                    </tr>
                    <tr>
                        <th><label for="max-devices">Max Devices <span class="required">*</span></label></th>
                        <td>
                            <input type="number" id="max-devices" name="max_devices" min="1" max="10" value="2" required>
                            <p class="description">Maximum number of devices per license (1-10)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="validity-days">Validity Period (Days) <span class="required">*</span></label></th>
                        <td>
                            <input type="number" id="validity-days" name="validity_days" min="1" value="30" required>
                            <p class="description">How many days the license is valid</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="max-requests">Max Requests per Day</label></th>
                        <td>
                            <input type="number" id="max-requests" name="max_requests_per_day" min="1" value="10">
                            <p class="description">Maximum API requests per device per day (0 = unlimited)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="allow-vps">Allow VPS/Datacenter IPs</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="allow-vps" name="allow_vps" value="1">
                                Allow access from VPS/datacenter IP addresses
                            </label>
                            <p class="description">If unchecked, VPS IPs will be blocked</p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="vd-modal-footer">
            <button class="button" id="vd-config-cancel">Cancel</button>
            <button class="button button-primary" id="vd-config-save">Save Configuration</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.vd-config-product').on('click', function() {
        var productId = $(this).data('product-id');
        
        // Get product config
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_get_share_config',
                nonce: vdAdmin.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    var config = response.data.config;
                    var product = response.data.product;
                    
                    $('#config-product-id').val(productId);
                    $('#config-product-name').text(product.name + ' (ID: ' + productId + ')');
                    
                    if (config) {
                        $('#max-devices').val(config.max_devices);
                        $('#validity-days').val(config.validity_days);
                        $('#max-requests').val(config.max_requests_per_day);
                        $('#allow-vps').prop('checked', config.allow_vps == 1);
                    } else {
                        // Defaults
                        $('#max-devices').val(2);
                        $('#validity-days').val(30);
                        $('#max-requests').val(10);
                        $('#allow-vps').prop('checked', false);
                    }
                    
                    $('#vd-config-modal').show();
                }
            }
        });
    });
    
    $('.vd-modal-close, #vd-config-cancel').on('click', function() {
        $('#vd-config-modal').hide();
    });
    
    $('#vd-config-save').on('click', function() {
        var formData = {
            action: 'vd_save_share_config',
            nonce: vdAdmin.nonce,
            product_id: $('#config-product-id').val(),
            max_devices: $('#max-devices').val(),
            validity_days: $('#validity-days').val(),
            max_requests_per_day: $('#max-requests').val(),
            allow_vps: $('#allow-vps').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Configuration saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
});
</script>
```

**AJAX Handler:**

```php
// includes/class-vd-share-config-ajax.php

class VD_Share_Config_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_vd_get_share_config', array($this, 'get_config'));
        add_action('wp_ajax_vd_save_share_config', array($this, 'save_config'));
    }
    
    public function get_config() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(['message' => 'Product not found']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_share_configs';
        
        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d",
            $product_id
        ), ARRAY_A);
        
        wp_send_json_success([
            'product' => [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'sku' => $product->get_sku()
            ],
            'config' => $config
        ]);
    }
    
    public function save_config() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $max_devices = intval($_POST['max_devices']);
        $validity_days = intval($_POST['validity_days']);
        $max_requests = intval($_POST['max_requests_per_day']);
        $allow_vps = intval($_POST['allow_vps']);
        
        if ($max_devices < 1 || $validity_days < 1) {
            wp_send_json_error(['message' => 'Invalid input values']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_share_configs';
        
        $data = [
            'product_id' => $product_id,
            'max_devices' => $max_devices,
            'validity_days' => $validity_days,
            'max_requests_per_day' => $max_requests,
            'allow_vps' => $allow_vps
        ];
        
        // Check if exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE product_id = %d",
            $product_id
        ));
        
        if ($exists) {
            // Update
            $result = $wpdb->update($table, $data, ['product_id' => $product_id]);
        } else {
            // Insert
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table, $data);
        }
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
        }
        
        wp_send_json_success(['message' => 'Configuration saved']);
    }
}

new VD_Share_Config_Ajax();
```

**Test Day 3:**
```
1. Go to VD License → Share Configs
   ✅ Shows all WooCommerce products
   ✅ Config status shows "Not set" for new products

2. Click "Set Config" for Product #100
   ✅ Modal opens
   ✅ Product name shows
   ✅ Default values: 2 devices, 30 days

3. Set config:
   - Max Devices: 3
   - Validity: 30 days
   - Max Requests: 10
   - Allow VPS: No
   → Save

   ✅ Success message
   ✅ Table updates showing config

4. Click "Edit Config"
   ✅ Modal loads saved values
   ✅ Can modify and save

5. Check database:
   ✅ wp_bz_vd_product_share_configs has row
   ✅ product_id = 100
   ✅ Values correct
```

---

## NGÀY 4-5: LMfWC Integration & License Sync

### 🎯 Mục tiêu
- Hook vào WooCommerce order completed
- Detect LMfWC license creation
- Sync license vào VD table
- Assign pool tự động
- Handle edge cases (pool full, no pools, etc)

### 📝 Tasks

#### Task 4.1: LMfWC Sync Hook (4h)

**File: includes/class-vd-lmfwc-sync.php**

```php
<?php

class VD_LMfWC_Sync {
    
    public function __construct() {
        // Hook when order completed
        add_action('woocommerce_order_status_completed', array($this, 'sync_license_on_order_complete'), 20);
        
        // Better: Hook directly to LMfWC license created (if available)
        add_action('lmfwc_license_created', array($this, 'sync_from_lmfwc_license'), 10, 2);
    }
    
    /**
     * Sync license when order completed
     * Priority 20 to run AFTER LMfWC (priority 10)
     */
    public function sync_license_on_order_complete($order_id) {
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("VD Sync: Order #{$order_id} not found");
            return;
        }
        
        // Check if already synced
        if ($order->get_meta('_vd_license_synced')) {
            error_log("VD Sync: Order #{$order_id} already synced");
            return;
        }
        
        // Get order items
        $items = $order->get_items();
        
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            
            // Check if product has share config (means it's a license product)
            if (!$this->product_has_share_config($product_id)) {
                continue;
            }
            
            // Get LMfWC license for this product
            $lmfwc_license = $this->get_lmfwc_license($order_id, $product_id);
            
            if (!$lmfwc_license) {
                error_log("VD Sync: No LMfWC license found for Order #{$order_id}, Product #{$product_id}");
                $order->add_order_note("VD License sync failed: LMfWC license not found for product #{$product_id}");
                continue;
            }
            
            // Sync to VD system
            $result = $this->sync_license_to_vd($lmfwc_license, $order);
            
            if (is_wp_error($result)) {
                error_log("VD Sync Error: " . $result->get_error_message());
                $order->add_order_note("VD License sync failed: " . $result->get_error_message());
            } else {
                $order->update_meta_data('_vd_license_synced', 'yes');
                $order->update_meta_data('_vd_license_id', $result['vd_license_id']);
                $order->save();
            }
        }
    }
    
    /**
     * Check if product has share config
     */
    private function product_has_share_config($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_share_configs';
        
        $config = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE product_id = %d",
            $product_id
        ));
        
        return !empty($config);
    }
    
    /**
     * Get LMfWC license from database
     */
    private function get_lmfwc_license($order_id, $product_id) {
        global $wpdb;
        
        // LMfWC stores licenses in wp_lmfwc_licenses table
        $license = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}lmfwc_licenses 
            WHERE order_id = %d AND product_id = %d
            ORDER BY id DESC
            LIMIT 1
        ", $order_id, $product_id), ARRAY_A);
        
        return $license;
    }
    
    /**
     * Sync license to VD system
     */
    private function sync_license_to_vd($lmfwc_license, $order) {
        global $wpdb;
        
        // Extract data
        $license_key = $lmfwc_license['license_key'];
        $lmfwc_license_id = $lmfwc_license['id'];
        $product_id = $lmfwc_license['product_id'];
        $order_id = $order->get_id();
        $customer_id = $order->get_customer_id();
        
        // Get share config
        $config = $this->get_share_config($product_id);
        if (!$config) {
            return new WP_Error('no_config', "No share config found for product #{$product_id}");
        }
        
        // Calculate validity dates
        $valid_from = current_time('mysql');
        $valid_until = date('Y-m-d H:i:s', strtotime("+{$config->validity_days} days"));
        
        // Insert into VD licenses table
        $licenses_table = $wpdb->prefix . 'bz_vd_license_keys';
        
        $data = [
            'license_key' => $license_key,
            'lmfwc_license_id' => $lmfwc_license_id,
            'product_id' => $product_id,
            'order_id' => $order_id,
            'customer_id' => $customer_id,
            'status' => 'active',
            'valid_from' => $valid_from,
            'valid_until' => $valid_until,
            'assigned_pool_id' => null,  // Will assign when customer first accesses
            'assigned_account_id' => null,
            'assigned_at' => null,
            'last_accessed_at' => null,
            'access_count' => 0,
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($licenses_table, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to insert VD license: ' . $wpdb->last_error);
        }
        
        $vd_license_id = $wpdb->insert_id;
        
        // Log sync
        error_log("VD Sync Success: License #{$vd_license_id} created for Order #{$order_id}");
        $order->add_order_note("VD License synced successfully: {$license_key} (VD ID: {$vd_license_id})");
        
        return [
            'vd_license_id' => $vd_license_id,
            'license_key' => $license_key
        ];
    }
    
    /**
     * Get share config for product
     */
    private function get_share_config($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_share_configs';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d",
            $product_id
        ));
    }
}

// Initialize
new VD_LMfWC_Sync();
```

**Test Day 4:**
```
SETUP:
1. Install & activate LMfWC plugin
2. Create license generator in LMfWC for Product #100
3. Create Share Config for Product #100

TEST:
1. Create test order:
   - Product: #100 (Netflix Premium)
   - Customer: test@example.com
   - Payment: Mark as paid
   
2. Mark order as Completed
   
3. Check WP Admin → Orders → View Order
   ✅ Order note: "VD License synced successfully..."
   ✅ Order meta: _vd_license_synced = yes

4. Check database:
   Table: wp_lmfwc_licenses
   ✅ Has license for order_id
   
   Table: wp_bz_vd_license_keys
   ✅ Has license with same license_key
   ✅ lmfwc_license_id matches
   ✅ product_id = 100
   ✅ status = 'active'
   ✅ valid_until = 30 days from now
   ✅ assigned_pool_id = NULL (not assigned yet)

5. Try create another order
   ✅ Each order creates separate license
   ✅ All sync correctly

6. Test edge case - No LMfWC license:
   - Product without LMfWC generator
   ✅ Order note: "LMfWC license not found"
   ✅ No VD license created

7. Check error log:
   ✅ Logs show sync activities
   ✅ Errors logged if any
```

---

#### Task 4.2: Pool Assignment Logic (4h)

**Add method to assign pool when customer first accesses portal**

**File: includes/class-vd-pool-assignment.php**

```php
<?php

class VD_Pool_Assignment {
    
    /**
     * Assign pool to license
     * Called when customer first accesses portal
     */
    public static function assign_pool_to_license($license_id) {
        global $wpdb;
        
        $licenses_table = $wpdb->prefix . 'bz_vd_license_keys';
        
        // Get license
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$licenses_table} WHERE id = %d",
            $license_id
        ), ARRAY_A);
        
        if (!$license) {
            return new WP_Error('not_found', 'License not found');
        }
        
        // Check if already assigned
        if ($license['assigned_pool_id']) {
            return [
                'already_assigned' => true,
                'pool_id' => $license['assigned_pool_id'],
                'account_id' => $license['assigned_account_id']
            ];
        }
        
        // Find available pool
        $pool = self::find_available_pool($license['product_id']);
        
        if (!$pool) {
            // No pools available
            return new WP_Error('no_pools', 'No pools available for this product');
        }
        
        // Check pool capacity
        if ($pool->assigned_count >= $pool->capacity) {
            // Pool is full, try next
            return new WP_Error('pool_full', 'All pools are at full capacity');
        }
        
        // Get random account from pool
        $account = self::get_random_account_from_pool($pool->id);
        
        if (!$account) {
            return new WP_Error('no_accounts', 'No accounts available in pool');
        }
        
        // Assign pool and account
        $result = $wpdb->update(
            $licenses_table,
            [
                'assigned_pool_id' => $pool->id,
                'assigned_account_id' => $account->id,
                'assigned_at' => current_time('mysql')
            ],
            ['id' => $license_id]
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to assign pool');
        }
        
        // Increment pool assigned count
        $pools_table = $wpdb->prefix . 'bz_vd_product_pools';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$pools_table} SET assigned_count = assigned_count + 1 WHERE id = %d",
            $pool->id
        ));
        
        // Log assignment
        error_log("VD Pool Assignment: License #{$license_id} → Pool #{$pool->id}, Account #{$account->id}");
        
        return [
            'assigned' => true,
            'pool_id' => $pool->id,
            'pool_name' => $pool->pool_name,
            'account_id' => $account->id,
            'account' => $account
        ];
    }
    
    /**
     * Find pool with available capacity
     */
    private static function find_available_pool($product_id) {
        global $wpdb;
        $pools_table = $wpdb->prefix . 'bz_vd_product_pools';
        
        // Get pools for product, ordered by least used first
        $pool = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$pools_table}
            WHERE product_id = %d 
            AND status = 'active'
            AND assigned_count < capacity
            ORDER BY assigned_count ASC
            LIMIT 1
        ", $product_id));
        
        return $pool;
    }
    
    /**
     * Get random account from pool
     */
    private static function get_random_account_from_pool($pool_id) {
        global $wpdb;
        $accounts_table = $wpdb->prefix . 'bz_vd_provider_accounts';
        
        $account = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$accounts_table}
            WHERE pool_id = %d 
            AND status = 'active'
            ORDER BY RAND()
            LIMIT 1
        ", $pool_id));
        
        return $account;
    }
    
    /**
     * Check pool availability for product
     */
    public static function check_pool_availability($product_id) {
        global $wpdb;
        $pools_table = $wpdb->prefix . 'bz_vd_product_pools';
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_pools,
                SUM(capacity) as total_capacity,
                SUM(assigned_count) as total_assigned,
                SUM(capacity - assigned_count) as available_slots
            FROM {$pools_table}
            WHERE product_id = %d AND status = 'active'
        ", $product_id), ARRAY_A);
        
        return $stats;
    }
}
```

**Test Pool Assignment:**
```
SETUP:
1. Pool #1: Capacity 3, Assigned 0
2. Account #1, #2, #3 in Pool #1
3. License #50 synced but not assigned (assigned_pool_id = NULL)

TEST:
1. Call assign_pool_to_license(50)
   
2. Check result:
   ✅ Returns assigned = true
   ✅ pool_id = 1
   ✅ account_id in [1,2,3]

3. Check database:
   wp_bz_vd_license_keys:
   ✅ License #50: assigned_pool_id = 1
   ✅ assigned_account_id = (one of 1,2,3)
   ✅ assigned_at = current timestamp
   
   wp_bz_vd_product_pools:
   ✅ Pool #1: assigned_count = 1

4. Call assign again for same license:
   ✅ Returns already_assigned = true
   ✅ Same pool_id and account_id
   ✅ assigned_count doesn't increment again

5. Create 2 more licenses and assign:
   ✅ License #51 assigned → Pool assigned_count = 2
   ✅ License #52 assigned → Pool assigned_count = 3

6. Try assign 4th license (pool full):
   ✅ Returns WP_Error: 'pool_full'
   ✅ License not assigned

7. Add Pool #2 with capacity 5
   ✅ 4th license now assigns to Pool #2
   ✅ Uses least-filled pool first
```

---

### 📊 End of Day 5 Deliverables

**Completed:**
- ✅ Share Config UI for products
- ✅ LMfWC sync on order completed
- ✅ VD license table populated
- ✅ Pool assignment logic
- ✅ Handle pool full scenario
- ✅ Error logging and order notes

**Database state after Day 5:**
```
wp_bz_vd_product_share_configs:
- Product #100: max_devices=3, validity=30

wp_bz_vd_product_pools:
- Pool #1: capacity=5, assigned_count=3

wp_bz_vd_provider_accounts:
- 5 accounts in Pool #1

wp_lmfwc_licenses:
- 3 licenses from LMfWC

wp_bz_vd_license_keys:
- 3 licenses synced
- assigned_pool_id = NULL (will assign on first portal access)
```

**Ready for:** Portal API (Day 6-9)

---

# 📅 NGÀY 6-9: PORTAL API

## NGÀY 6-7: REST API Endpoints

### 🎯 Mục tiêu
- Tạo REST API endpoint: `/wp-json/vd/v1/license/access`
- Validate license key
- Assign pool on first access
- Return account credentials
- Device fingerprinting

### 📝 Tasks

#### Task 6.1: Register REST API Route (2h)

**File: includes/class-vd-rest-api.php**

```php
<?php

class VD_REST_API {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('vd/v1', '/license/access', [
            'methods' => 'POST',
            'callback' => array($this, 'handle_license_access'),
            'permission_callback' => '__return_true',  // Public endpoint
            'args' => [
                'license_key' => [
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => function($param) {
                        return preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $param);
                    }
                ],
                'device_fingerprint' => [
                    'required' => false,
                    'type' => 'string'
                ],
                'device_token' => [
                    'required' => false,
                    'type' => 'string'
                ],
                'device_combined_id' => [
                    'required' => false,
                    'type' => 'string'
                ],
                'device_name' => [
                    'required' => false,
                    'type' => 'string'
                ]
            ]
        ]);
    }
    
    public function handle_license_access($request) {
        // Extract parameters
        $license_key = sanitize_text_field($request['license_key']);
        $device_fingerprint = isset($request['device_fingerprint']) ? sanitize_text_field($request['device_fingerprint']) : null;
        $device_token = isset($request['device_token']) ? sanitize_text_field($request['device_token']) : null;
        $device_combined_id = isset($request['device_combined_id']) ? sanitize_text_field($request['device_combined_id']) : null;
        $device_name = isset($request['device_name']) ? sanitize_text_field($request['device_name']) : 'Unknown Device';
        
        // Get IP address
        $ip_address = $this->get_client_ip();
        
        // Get user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Step 1: Validate License Key
        $license = $this->get_license_by_key($license_key);
        
        if (!$license) {
            return $this->error_response('invalid_license', 'License key not found', 404);
        }
        
        // Step 2: Check license status
        if ($license['status'] !== 'active') {
            return $this->error_response('license_inactive', 'License is ' . $license['status'], 403);
        }
        
        // Step 3: Check expiration
        if (strtotime($license['valid_until']) < time()) {
            // Update status to expired
            $this->update_license_status($license['id'], 'expired');
            return $this->error_response('license_expired', 'License has expired', 403);
        }
        
        // Step 4: Assign pool if not assigned
        if (!$license['assigned_pool_id']) {
            $assignment = VD_Pool_Assignment::assign_pool_to_license($license['id']);
            
            if (is_wp_error($assignment)) {
                return $this->error_response(
                    $assignment->get_error_code(), 
                    $assignment->get_error_message(),
                    503
                );
            }
            
            // Reload license with assigned pool/account
            $license = $this->get_license_by_key($license_key);
        }
        
        // Step 5: Get account credentials
        $account = $this->get_account($license['assigned_account_id']);
        
        if (!$account) {
            return $this->error_response('account_not_found', 'Account credentials not available', 500);
        }
        
        // Step 6: Update access stats
        $this->update_license_access($license['id']);
        
        // Step 7: Return success response
        return new WP_REST_Response([
            'status' => 'success',
            'license' => [
                'key' => $license['license_key'],
                'status' => $license['status'],
                'valid_from' => $license['valid_from'],
                'valid_until' => $license['valid_until'],
                'product_id' => $license['product_id']
            ],
            'account' => [
                'provider' => $account['provider'],
                'credentials' => json_decode($account['credentials'], true)
            ],
            'pool' => [
                'id' => $license['assigned_pool_id'],
                'name' => $this->get_pool_name($license['assigned_pool_id'])
            ]
        ], 200);
    }
    
    /**
     * Helper: Get license by key
     */
    private function get_license_by_key($license_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_keys';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE license_key = %s",
            $license_key
        ), ARRAY_A);
    }
    
    /**
     * Helper: Get account
     */
    private function get_account($account_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_provider_accounts';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $account_id
        ), ARRAY_A);
    }
    
    /**
     * Helper: Update license access stats
     */
    private function update_license_access($license_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_keys';
        
        $wpdb->query($wpdb->prepare("
            UPDATE {$table} 
            SET last_accessed_at = %s, access_count = access_count + 1
            WHERE id = %d
        ", current_time('mysql'), $license_id));
    }
    
    /**
     * Helper: Update license status
     */
    private function update_license_status($license_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_keys';
        
        $wpdb->update($table, ['status' => $status], ['id' => $license_id]);
    }
    
    /**
     * Helper: Get pool name
     */
    private function get_pool_name($pool_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_pools';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT pool_name FROM {$table} WHERE id = %d",
            $pool_id
        ));
    }
    
    /**
     * Helper: Get client IP
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    /**
     * Helper: Error response
     */
    private function error_response($code, $message, $status_code = 400) {
        return new WP_REST_Response([
            'status' => 'error',
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ], $status_code);
    }
}

// Initialize
new VD_REST_API();
```

**Test API Day 6:**
```bash
# Test 1: Valid license, first access
curl -X POST https://vidieu.vn/wp-json/vd/v1/license/access \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "A3F9-K2L4-M8N1-P5Q7"
  }'

Expected Response:
{
  "status": "success",
  "license": {
    "key": "A3F9-K2L4-M8N1-P5Q7",
    "status": "active",
    "valid_from": "2025-10-08 00:00:00",
    "valid_until": "2025-11-07 23:59:59",
    "product_id": 100
  },
  "account": {
    "provider": "Netflix",
    "credentials": {
      "email": "test@netflix.com",
      "password": "pass123",
      "profile_name": "Profile 1"
    }
  },
  "pool": {
    "id": 1,
    "name": "Netflix Pool 1"
  }
}

✅ Status 200
✅ Pool assigned
✅ Credentials returned

# Test 2: Invalid license key
curl -X POST https://vidieu.vn/wp-json/vd/v1/license/access \
  -d '{"license_key": "INVALID-KEY"}'

Expected:
{
  "status": "error",
  "error": {
    "code": "invalid_license",
    "message": "License key not found"
  }
}
✅ Status 404

# Test 3: Expired license
curl -X POST https://vidieu.vn/wp-json/vd/v1/license/access \
  -d '{"license_key": "EXPIRED-LICENSE-KEY"}'

Expected:
{
  "status": "error",
  "error": {
    "code": "license_expired",
    "message": "License has expired"
  }
}
✅ Status 403

# Test 4: Same license, second access
curl -X POST https://vidieu.vn/wp-json/vd/v1/license/access \
  -d '{"license_key": "A3F9-K2L4-M8N1-P5Q7"}'

Expected:
✅ Returns SAME pool and account
✅ access_count incremented
✅ last_accessed_at updated

# Test 5: All pools full
curl -X POST https://vidieu.vn/wp-json/vd/v1/license/access \
  -d '{"license_key": "NEW-LICENSE-KEY"}'

Expected:
{
  "status": "error",
  "error": {
    "code": "pool_full",
    "message": "All pools are at full capacity"
  }
}
✅ Status 503

# Check database after tests:
wp_bz_vd_license_keys:
✅ assigned_pool_id populated
✅ assigned_account_id populated
✅ assigned_at timestamp
✅ last_accessed_at updated
✅ access_count = 2 (for test 1+4)

wp_bz_vd_product_pools:
✅ assigned_count incremented
```

---

## NGÀY 8-9: Device Tracking & Management

### 🎯 Mục tiêu
- Implement device fingerprinting
- Track multiple devices per license
- Enforce max_devices limit
- Device blocking/unblocking
- Device management UI for admin

### 📝 Tasks

#### Task 8.1: Device Registration Logic (4h)

**Update REST API to handle device registration**

**File: includes/class-vd-device-manager.php**

```php
<?php

class VD_Device_Manager {
    
    /**
     * Register or validate device for license
     */
    public static function register_device($license, $device_data) {
        global $wpdb;
        
        $license_id = $license['id'];
        $device_combined_id = $device_data['device_combined_id'];
        $device_fingerprint = $device_data['device_fingerprint'];
        $device_token = $device_data['device_token'];
        $device_name = $device_data['device_name'];
        $user_agent = $device_data['user_agent'];
        $ip_address = $device_data['ip_address'];
        
        // Get share config to check max_devices
        $config = self::get_share_config($license['product_id']);
        
        if (!$config) {
            return new WP_Error('no_config', 'Product configuration not found');
        }
        
        $max_devices = $config->max_devices;
        
        // Check if device already registered
        $devices_table = $wpdb->prefix . 'bz_vd_license_devices';
        
        $existing_device = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$devices_table}
            WHERE license_id = %d AND device_combined_id = %s
        ", $license_id, $device_combined_id), ARRAY_A);
        
        if ($existing_device) {
            // Device exists - update access stats
            if ($existing_device['status'] === 'blocked') {
                return new WP_Error('device_blocked', 'This device has been blocked');
            }
            
            $wpdb->update(
                $devices_table,
                [
                    'last_access_at' => current_time('mysql'),
                    'access_count' => $existing_device['access_count'] + 1,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent
                ],
                ['id' => $existing_device['id']]
            );
            
            return [
                'device_id' => $existing_device['id'],
                'status' => 'existing',
                'device_name' => $existing_device['device_name']
            ];
        }
        
        // New device - check if limit reached
        $current_device_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$devices_table}
            WHERE license_id = %d AND status = 'active'
        ", $license_id));
        
        if ($current_device_count >= $max_devices) {
            return new WP_Error(
                'max_devices_reached',
                "Maximum {$max_devices} devices allowed. Please remove a device first."
            );
        }
        
        // Register new device
        $insert_data = [
            'license_id' => $license_id,
            'device_combined_id' => $device_combined_id,
            'device_fingerprint' => $device_fingerprint,
            'device_token' => $device_token,
            'device_name' => $device_name,
            'user_agent' => $user_agent,
            'ip_address' => $ip_address,
            'status' => 'active',
            'first_access_at' => current_time('mysql'),
            'last_access_at' => current_time('mysql'),
            'access_count' => 1,
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($devices_table, $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to register device');
        }
        
        $device_id = $wpdb->insert_id;
        
        // Log device registration
        error_log("VD Device: New device #{$device_id} registered for License #{$license_id}");
        
        return [
            'device_id' => $device_id,
            'status' => 'new',
            'device_name' => $device_name,
            'devices_used' => $current_device_count + 1,
            'max_devices' => $max_devices
        ];
    }
    
    /**
     * Get all devices for license
     */
    public static function get_license_devices($license_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_devices';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table}
            WHERE license_id = %d
            ORDER BY created_at DESC
        ", $license_id), ARRAY_A);
    }
    
    /**
     * Get share config
     */
    private static function get_share_config($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_product_share_configs';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d",
            $product_id
        ));
    }
    
    /**
     * Block device
     */
    public static function block_device($device_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_devices';
        
        return $wpdb->update(
            $table,
            ['status' => 'blocked'],
            ['id' => $device_id]
        );
    }
    
    /**
     * Unblock device
     */
    public static function unblock_device($device_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_devices';
        
        return $wpdb->update(
            $table,
            ['status' => 'active'],
            ['id' => $device_id]
        );
    }
    
    /**
     * Remove device
     */
    public static function remove_device($device_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_devices';
        
        return $wpdb->delete($table, ['id' => $device_id]);
    }
}
```

**Update REST API to include device tracking:**

```php
// In class-vd-rest-api.php, add to handle_license_access()

// After Step 5 (Get account credentials), add:

// Step 5.5: Handle device registration (if device data provided)
$device_result = null;
if ($device_combined_id) {
    $device_data = [
        'device_combined_id' => $device_combined_id,
        'device_fingerprint' => $device_fingerprint,
        'device_token' => $device_token,
        'device_name' => $device_name,
        'user_agent' => $user_agent,
        'ip_address' => $ip_address
    ];
    
    $device_result = VD_Device_Manager::register_device($license, $device_data);
    
    if (is_wp_error($device_result)) {
        // Log but continue (device error shouldn't block license access)
        $this->log_access($license['id'], null, $ip_address, 'device_error', $device_result->get_error_message());
        
        // If max devices reached, return error
        if ($device_result->get_error_code() === 'max_devices_reached') {
            return $this->error_response(
                'max_devices_reached',
                $device_result->get_error_message(),
                403
            );
        }
    }
}

// Get all devices for this license
$devices = VD_Device_Manager::get_license_devices($license['id']);

// Update response to include device info
return new WP_REST_Response([
    'status' => 'success',
    'license' => [
        'key' => $license['license_key'],
        'status' => $license['status'],
        'valid_from' => $license['valid_from'],
        'valid_until' => $license['valid_until'],
        'product_id' => $license['product_id']
    ],
    'account' => [
        'provider' => $account['provider'],
        'credentials' => json_decode($account['credentials'], true)
    ],
    'pool' => [
        'id' => $license['assigned_pool_id'],
        'name' => $this->get_pool_name($license['assigned_pool_id'])
    ],
    'device' => $device_result,
    'devices' => array_map(function($d) {
        return [
            'id' => $d['id'],
            'name' => $d['device_name'],
            'status' => $d['status'],
            'first_access' => $d['first_access_at'],
            'last_access' => $d['last_access_at'],
            'access_count' => $d['access_count']
        ];
    }, $devices)
], 200);
```

**Checklist:**
- [ ] Device registration logic implemented
- [ ] Max devices limit enforced
- [ ] Device exists check working
- [ ] Device blocking/unblocking functions
- [ ] API response includes device info

---

#### Task 8.2: Access Logging (3h)

**Create comprehensive access logging**

```php
// Add to class-vd-rest-api.php

/**
 * Log every API access attempt
 */
private function log_access($license_id, $device_id, $ip_address, $result, $error_message = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'bz_vd_device_access_log';
    
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    $data = [
        'license_id' => $license_id,
        'device_id' => $device_id,
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'request_type' => 'access',
        'result' => $result,  // 'success', 'blocked', 'error'
        'error_code' => null,
        'error_message' => $error_message,
        'created_at' => current_time('mysql')
    ];
    
    $wpdb->insert($table, $data);
}

// Call log_access() at different points:

// On success:
$this->log_access($license['id'], $device_result['device_id'], $ip_address, 'success');

// On error:
$this->log_access($license['id'], null, $ip_address, 'error', 'License not found');

// On blocked:
$this->log_access($license['id'], $device_id, $ip_address, 'blocked', 'Device blocked');
```

---

#### Task 8.3: Admin Device Management UI (5h)

**File: admin/partials/devices.php**

```php
<?php
// Device management page for admin

global $wpdb;
$devices_table = $wpdb->prefix . 'bz_vd_license_devices';
$licenses_table = $wpdb->prefix . 'bz_vd_license_keys';

// Get all devices with license info
$devices = $wpdb->get_results("
    SELECT 
        d.*,
        l.license_key,
        l.customer_id,
        l.product_id
    FROM {$devices_table} d
    LEFT JOIN {$licenses_table} l ON d.license_id = l.id
    ORDER BY d.created_at DESC
    LIMIT 100
");

// Get user info
$customer_names = [];
if (!empty($devices)) {
    $customer_ids = array_unique(array_column($devices, 'customer_id'));
    foreach ($customer_ids as $customer_id) {
        $user = get_user_by('id', $customer_id);
        $customer_names[$customer_id] = $user ? $user->display_name : "User #{$customer_id}";
    }
}
?>

<div class="wrap vd-wrap">
    <h1>Device Management</h1>
    
    <!-- Stats Cards -->
    <div class="vd-stats-cards">
        <div class="vd-stat-card">
            <h3>Total Devices</h3>
            <p class="stat-value"><?php echo count($devices); ?></p>
        </div>
        <div class="vd-stat-card">
            <h3>Active Devices</h3>
            <p class="stat-value">
                <?php echo count(array_filter($devices, fn($d) => $d->status === 'active')); ?>
            </p>
        </div>
        <div class="vd-stat-card">
            <h3>Blocked Devices</h3>
            <p class="stat-value">
                <?php echo count(array_filter($devices, fn($d) => $d->status === 'blocked')); ?>
            </p>
        </div>
        <div class="vd-stat-card">
            <h3>Unique Licenses</h3>
            <p class="stat-value">
                <?php echo count(array_unique(array_column($devices, 'license_id'))); ?>
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="vd-filters" style="margin-bottom:20px;">
        <input type="text" id="filter-license-key" placeholder="Search by License Key" class="regular-text">
        <select id="filter-status">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="blocked">Blocked</option>
            <option value="removed">Removed</option>
        </select>
        <input type="text" id="filter-device-name" placeholder="Search by Device Name" class="regular-text">
    </div>

    <!-- Devices Table -->
    <table class="vd-table wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Device Name</th>
                <th>License Key</th>
                <th>Customer</th>
                <th>IP Address</th>
                <th>Status</th>
                <th>First Access</th>
                <th>Last Access</th>
                <th>Access Count</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($devices as $device): ?>
            <tr data-device-id="<?php echo $device->id; ?>" 
                data-license-key="<?php echo esc_attr($device->license_key); ?>"
                data-status="<?php echo esc_attr($device->status); ?>"
                data-device-name="<?php echo esc_attr($device->device_name); ?>">
                <td><?php echo $device->id; ?></td>
                <td>
                    <strong><?php echo esc_html($device->device_name); ?></strong>
                    <br><small><?php echo substr($device->device_combined_id, 0, 16); ?>...</small>
                </td>
                <td>
                    <a href="?page=vd-licenses&license_id=<?php echo $device->license_id; ?>">
                        <?php echo esc_html($device->license_key); ?>
                    </a>
                </td>
                <td><?php echo esc_html($customer_names[$device->customer_id] ?? 'Unknown'); ?></td>
                <td><?php echo esc_html($device->ip_address); ?></td>
                <td>
                    <span class="vd-badge <?php echo $device->status; ?>">
                        <?php echo ucfirst($device->status); ?>
                    </span>
                </td>
                <td><?php echo date('M d, Y H:i', strtotime($device->first_access_at)); ?></td>
                <td><?php echo date('M d, Y H:i', strtotime($device->last_access_at)); ?></td>
                <td><?php echo $device->access_count; ?></td>
                <td>
                    <button class="button button-small vd-view-device" data-device-id="<?php echo $device->id; ?>">
                        View
                    </button>
                    <?php if ($device->status === 'active'): ?>
                        <button class="button button-small vd-block-device" data-device-id="<?php echo $device->id; ?>">
                            Block
                        </button>
                    <?php else: ?>
                        <button class="button button-small vd-unblock-device" data-device-id="<?php echo $device->id; ?>">
                            Unblock
                        </button>
                    <?php endif; ?>
                    <button class="button button-small button-link-delete vd-remove-device" data-device-id="<?php echo $device->id; ?>">
                        Remove
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Device Details Modal -->
<div id="vd-device-modal" style="display:none;">
    <div class="vd-modal-backdrop"></div>
    <div class="vd-modal-content">
        <div class="vd-modal-header">
            <h2>Device Details</h2>
            <button class="vd-modal-close">&times;</button>
        </div>
        <div class="vd-modal-body">
            <div id="device-details-content"></div>
        </div>
        <div class="vd-modal-footer">
            <button class="button" id="vd-device-close">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Filters
    function filterTable() {
        var licenseKey = $('#filter-license-key').val().toLowerCase();
        var status = $('#filter-status').val();
        var deviceName = $('#filter-device-name').val().toLowerCase();
        
        $('.vd-table tbody tr').each(function() {
            var show = true;
            
            if (licenseKey && !$(this).data('license-key').toLowerCase().includes(licenseKey)) {
                show = false;
            }
            if (status && $(this).data('status') !== status) {
                show = false;
            }
            if (deviceName && !$(this).data('device-name').toLowerCase().includes(deviceName)) {
                show = false;
            }
            
            $(this).toggle(show);
        });
    }
    
    $('#filter-license-key, #filter-status, #filter-device-name').on('input change', filterTable);
    
    // View Device Details
    $('.vd-view-device').on('click', function() {
        var deviceId = $(this).data('device-id');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_get_device_details',
                nonce: vdAdmin.nonce,
                device_id: deviceId
            },
            success: function(response) {
                if (response.success) {
                    var device = response.data;
                    var html = '<table class="widefat"><tbody>';
                    
                    html += '<tr><th>Device ID</th><td>' + device.id + '</td></tr>';
                    html += '<tr><th>Device Name</th><td>' + device.device_name + '</td></tr>';
                    html += '<tr><th>Combined ID</th><td><code>' + device.device_combined_id + '</code></td></tr>';
                    html += '<tr><th>Fingerprint</th><td><code>' + device.device_fingerprint + '</code></td></tr>';
                    html += '<tr><th>Token</th><td><code>' + device.device_token + '</code></td></tr>';
                    html += '<tr><th>IP Address</th><td>' + device.ip_address + '</td></tr>';
                    html += '<tr><th>User Agent</th><td>' + device.user_agent + '</td></tr>';
                    html += '<tr><th>Status</th><td><span class="vd-badge ' + device.status + '">' + device.status + '</span></td></tr>';
                    html += '<tr><th>First Access</th><td>' + device.first_access_at + '</td></tr>';
                    html += '<tr><th>Last Access</th><td>' + device.last_access_at + '</td></tr>';
                    html += '<tr><th>Access Count</th><td>' + device.access_count + '</td></tr>';
                    
                    html += '</tbody></table>';
                    
                    $('#device-details-content').html(html);
                    $('#vd-device-modal').show();
                }
            }
        });
    });
    
    // Block Device
    $('.vd-block-device').on('click', function() {
        if (!confirm('Block this device? The user will not be able to access from this device.')) {
            return;
        }
        
        var deviceId = $(this).data('device-id');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_block_device',
                nonce: vdAdmin.nonce,
                device_id: deviceId
            },
            success: function(response) {
                if (response.success) {
                    alert('Device blocked successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    // Unblock Device
    $('.vd-unblock-device').on('click', function() {
        var deviceId = $(this).data('device-id');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_unblock_device',
                nonce: vdAdmin.nonce,
                device_id: deviceId
            },
            success: function(response) {
                if (response.success) {
                    alert('Device unblocked successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    // Remove Device
    $('.vd-remove-device').on('click', function() {
        if (!confirm('Permanently remove this device? This cannot be undone.')) {
            return;
        }
        
        var deviceId = $(this).data('device-id');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_remove_device',
                nonce: vdAdmin.nonce,
                device_id: deviceId
            },
            success: function(response) {
                if (response.success) {
                    alert('Device removed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    // Close modal
    $('.vd-modal-close, #vd-device-close').on('click', function() {
        $('#vd-device-modal').hide();
    });
});
</script>
```

**AJAX Handlers for Device Management:**

```php
// includes/class-vd-device-ajax.php

class VD_Device_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_vd_get_device_details', array($this, 'get_device_details'));
        add_action('wp_ajax_vd_block_device', array($this, 'block_device'));
        add_action('wp_ajax_vd_unblock_device', array($this, 'unblock_device'));
        add_action('wp_ajax_vd_remove_device', array($this, 'remove_device'));
    }
    
    public function get_device_details() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $device_id = intval($_POST['device_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_devices';
        
        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $device_id
        ), ARRAY_A);
        
        if (!$device) {
            wp_send_json_error(['message' => 'Device not found']);
        }
        
        wp_send_json_success($device);
    }
    
    public function block_device() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $device_id = intval($_POST['device_id']);
        $result = VD_Device_Manager::block_device($device_id);
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to block device']);
        }
        
        wp_send_json_success(['message' => 'Device blocked']);
    }
    
    public function unblock_device() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $device_id = intval($_POST['device_id']);
        $result = VD_Device_Manager::unblock_device($device_id);
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to unblock device']);
        }
        
        wp_send_json_success(['message' => 'Device unblocked']);
    }
    
    public function remove_device() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $device_id = intval($_POST['device_id']);
        $result = VD_Device_Manager::remove_device($device_id);
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to remove device']);
        }
        
        wp_send_json_success(['message' => 'Device removed']);
    }
}

new VD_Device_Ajax();
```

---

### 📊 End of Day 8-9 Deliverables

**Test Day 8-9:**
```
DEVICE REGISTRATION:
1. Call API with device data:
   curl -X POST /wp-json/vd/v1/license/access \
     -d '{
       "license_key": "A3F9-K2L4-M8N1-P5Q7",
       "device_combined_id": "abc123...",
       "device_fingerprint": "fp123",
       "device_token": "dt_xyz",
       "device_name": "iPhone 14"
     }'
   
   ✅ Returns device_id
   ✅ status = 'new'
   ✅ devices_used = 1

2. Call again with same device:
   ✅ status = 'existing'
   ✅ access_count incremented
   ✅ No duplicate device

3. Register 2nd device (max_devices = 3):
   ✅ Success, devices_used = 2

4. Register 3rd device:
   ✅ Success, devices_used = 3

5. Try register 4th device:
   ✅ Error: max_devices_reached
   ✅ HTTP 403

ADMIN DEVICE MANAGEMENT:
1. Go to VD License → Devices
   ✅ Shows all 3 devices
   ✅ Stats cards accurate

2. Click "View" on device:
   ✅ Modal shows full details
   ✅ Combined ID, fingerprint visible

3. Click "Block" on device #1:
   ✅ Confirmation dialog
   ✅ Device status = blocked
   ✅ Badge turns red

4. Try access with blocked device:
   ✅ API returns error: device_blocked

5. Click "Unblock":
   ✅ Device active again

6. Click "Remove":
   ✅ Confirmation dialog
   ✅ Device deleted from DB
   ✅ Customer can register new device now

7. Filters:
   ✅ Search by license key
   ✅ Filter by status
   ✅ Search by device name

DATABASE CHECK:
wp_bz_vd_license_devices:
✅ 3 devices registered
✅ device_combined_id unique per license
✅ access_count incrementing
✅ last_access_at updating

wp_bz_vd_device_access_log:
✅ Every API call logged
✅ Success/error/blocked logged
```

---

# 📅 NGÀY 10-11: VPS DETECTION & BLOCKING

### 🎯 Mục tiêu
- Implement VPS/Datacenter IP detection
- Block VPS IPs if product config disallows
- IP database/API integration
- Whitelist management
- VPS bypass for testing

### 📝 Tasks

#### Task 10.1: VPS Detection Service (4h)

**Option 1: Use free IP check API**
**Option 2: Local database of datacenter IP ranges**

**File: includes/class-vd-vps-detector.php**

```php
<?php

class VD_VPS_Detector {
    
    private static $datacenter_asns = [
        // Common VPS providers
        'AS14061' => 'DigitalOcean',
        'AS16509' => 'Amazon AWS',
        'AS15169' => 'Google Cloud',
        'AS8075' => 'Microsoft Azure',
        'AS20473' => 'Vultr',
        'AS63949' => 'Linode',
        'AS24940' => 'Hetzner',
        'AS13335' => 'Cloudflare',
        // Add more...
    ];
    
    /**
     * Check if IP is from VPS/Datacenter
     * Returns: ['is_vps' => bool, 'provider' => string, 'method' => string]
     */
    public static function check_ip($ip_address) {
        // Method 1: Check against local datacenter IP ranges
        $local_check = self::check_local_database($ip_address);
        if ($local_check['is_vps']) {
            return $local_check;
        }
        
        // Method 2: Use external API (fallback)
        $api_check = self::check_via_api($ip_address);
        if ($api_check['is_vps']) {
            return $api_check;
        }
        
        // Not detected as VPS
        return [
            'is_vps' => false,
            'provider' => null,
            'method' => 'none'
        ];
    }
    
    /**
     * Check against local IP ranges database
     */
    private static function check_local_database($ip_address) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_datacenter_ip_ranges';
        
        // Convert IP to long for range comparison
        $ip_long = ip2long($ip_address);
        
        if ($ip_long === false) {
            return ['is_vps' => false, 'provider' => null, 'method' => 'invalid_ip'];
        }
        
        // Check if IP is in any datacenter range
        $range = $wpdb->get_row($wpdb->prepare("
            SELECT provider, description 
            FROM {$table}
            WHERE %d BETWEEN ip_start_long AND ip_end_long
            LIMIT 1
        ", $ip_long), ARRAY_A);
        
        if ($range) {
            return [
                'is_vps' => true,
                'provider' => $range['provider'],
                'method' => 'local_db',
                'description' => $range['description']
            ];
        }
        
        return ['is_vps' => false, 'provider' => null, 'method' => 'local_db_miss'];
    }
    
    /**
     * Check via external API (ipinfo.io, ipapi.co, etc.)
     */
    private static function check_via_api($ip_address) {
        // Use cached result if available (24 hours)
        $cache_key = 'vd_vps_check_' . md5($ip_address);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Call API (example using ipinfo.io)
        $api_url = "https://ipinfo.io/{$ip_address}/json";
        $response = wp_remote_get($api_url, [
            'timeout' => 5,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return ['is_vps' => false, 'provider' => null, 'method' => 'api_error'];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return ['is_vps' => false, 'provider' => null, 'method' => 'api_invalid'];
        }
        
        // Check if it's a hosting/datacenter
        $is_hosting = false;
        $provider = null;
        
        // Check org field for common hosting providers
        if (isset($data['org'])) {
            $org = strtolower($data['org']);
            
            $hosting_keywords = [
                'amazon', 'aws', 'google cloud', 'microsoft', 'azure',
                'digitalocean', 'vultr', 'linode', 'ovh', 'hetzner',
                'hosting', 'datacenter', 'data center', 'cloud'
            ];
            
            foreach ($hosting_keywords as $keyword) {
                if (strpos($org, $keyword) !== false) {
                    $is_hosting = true;
                    $provider = $data['org'];
                    break;
                }
            }
        }
        
        $result = [
            'is_vps' => $is_hosting,
            'provider' => $provider,
            'method' => 'api',
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null
        ];
        
        // Cache for 24 hours
        set_transient($cache_key, $result, DAY_IN_SECONDS);
        
        return $result;
    }
    
    /**
     * Check if IP is whitelisted
     */
    public static function is_whitelisted($ip_address) {
        // Get whitelist from options
        $whitelist = get_option('vd_vps_whitelist_ips', []);
        
        return in_array($ip_address, $whitelist);
    }
    
    /**
     * Add IP to whitelist
     */
    public static function add_to_whitelist($ip_address) {
        $whitelist = get_option('vd_vps_whitelist_ips', []);
        
        if (!in_array($ip_address, $whitelist)) {
            $whitelist[] = $ip_address;
            update_option('vd_vps_whitelist_ips', $whitelist);
        }
        
        return true;
    }
    
    /**
     * Remove IP from whitelist
     */
    public static function remove_from_whitelist($ip_address) {
        $whitelist = get_option('vd_vps_whitelist_ips', []);
        $whitelist = array_diff($whitelist, [$ip_address]);
        update_option('vd_vps_whitelist_ips', array_values($whitelist));
        
        return true;
    }
}
```

**Create table for datacenter IP ranges:**

```sql
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bz_vd_datacenter_ip_ranges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(100) NOT NULL COMMENT 'AWS, Google Cloud, etc',
  `ip_range` VARCHAR(50) NOT NULL COMMENT 'CIDR notation',
  `ip_start` VARCHAR(45) NOT NULL,
  `ip_end` VARCHAR(45) NOT NULL,
  `ip_start_long` BIGINT UNSIGNED NOT NULL,
  `ip_end_long` BIGINT UNSIGNED NOT NULL,
  `country` VARCHAR(10) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_range` (`ip_start_long`, `ip_end_long`),
  KEY `idx_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Seed common datacenter IP ranges:**

```php
// includes/vd-seed-datacenter-ips.php

function vd_seed_datacenter_ip_ranges() {
    global $wpdb;
    $table = $wpdb->prefix . 'bz_vd_datacenter_ip_ranges';
    
    // Sample AWS IP ranges
    $ranges = [
        [
            'provider' => 'Amazon AWS',
            'ip_range' => '3.0.0.0/15',
            'ip_start' => '3.0.0.0',
            'ip_end' => '3.1.255.255',
            'country' => 'US'
        ],
        [
            'provider' => 'DigitalOcean',
            'ip_range' => '104.131.0.0/16',
            'ip_start' => '104.131.0.0',
            'ip_end' => '104.131.255.255',
            'country' => 'US'
        ],
        // Add more ranges from:
        // - AWS: https://ip-ranges.amazonaws.com/ip-ranges.json
        // - Google Cloud: https://www.gstatic.com/ipranges/cloud.json
        // - Azure: https://www.microsoft.com/en-us/download/details.aspx?id=56519
    ];
    
    foreach ($ranges as $range) {
        $range['ip_start_long'] = ip2long($range['ip_start']);
        $range['ip_end_long'] = ip2long($range['ip_end']);
        
        $wpdb->insert($table, $range);
    }
}

// Run once on activation
register_activation_hook(VD_PLUGIN_FILE, 'vd_seed_datacenter_ip_ranges');
```

---

#### Task 10.2: Integrate VPS Detection into API (3h)

**Update REST API to check VPS:**

```php
// In class-vd-rest-api.php, add BEFORE device registration:

// Step 2.5: VPS Detection & Blocking
$config = $this->get_share_config($license['product_id']);

if ($config && !$config->allow_vps) {
    // Check if IP is whitelisted first
    if (!VD_VPS_Detector::is_whitelisted($ip_address)) {
        
        // Check if VPS
        $vps_check = VD_VPS_Detector::check_ip($ip_address);
        
        if ($vps_check['is_vps']) {
            // Log blocked attempt
            $this->log_access($license['id'], null, $ip_address, 'blocked', 'VPS detected: ' . $vps_check['provider']);
            
            // Update device record if exists
            if ($device_combined_id) {
                $this->update_device_vps_status($license['id'], $device_combined_id, $vps_check);
            }
            
            return $this->error_response(
                'vps_blocked',
                'Access from VPS/Datacenter IPs is not allowed for this product. Provider: ' . $vps_check['provider'],
                403
            );
        }
    }
}

// Helper method to update device VPS status
private function update_device_vps_status($license_id, $device_combined_id, $vps_check) {
    global $wpdb;
    $table = $wpdb->prefix . 'bz_vd_license_devices';
    
    $wpdb->update(
        $table,
        [
            'is_vps' => 1,
            'vps_provider' => $vps_check['provider']
        ],
        [
            'license_id' => $license_id,
            'device_combined_id' => $device_combined_id
        ]
    );
}

private function get_share_config($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'bz_vd_product_share_configs';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE product_id = %d",
        $product_id
    ));
}
```

---

#### Task 10.3: VPS Settings UI (3h)

**Add VPS settings page**

**File: admin/partials/settings-vps.php**

```php
<?php
// VPS Detection Settings

$whitelist_ips = get_option('vd_vps_whitelist_ips', []);
$detection_method = get_option('vd_vps_detection_method', 'api'); // 'local_db', 'api', 'both'
$api_provider = get_option('vd_vps_api_provider', 'ipinfo'); // 'ipinfo', 'ipapi', 'ipqualityscore'
?>

<div class="wrap vd-wrap">
    <h1>VPS Detection Settings</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('vd_vps_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th colspan="2">
                    <h2>Detection Method</h2>
                </th>
            </tr>
            <tr>
                <th><label>Detection Method</label></th>
                <td>
                    <select name="vd_vps_detection_method">
                        <option value="local_db" <?php selected($detection_method, 'local_db'); ?>>
                            Local Database Only (Fast, No API calls)
                        </option>
                        <option value="api" <?php selected($detection_method, 'api'); ?>>
                            External API Only (Most accurate, slower)
                        </option>
                        <option value="both" <?php selected($detection_method, 'both'); ?>>
                            Both (Try local DB first, fallback to API)
                        </option>
                    </select>
                    <p class="description">
                        Recommended: "Both" for best accuracy and speed.
                    </p>
                </td>
            </tr>
            
            <tr>
                <th><label>API Provider</label></th>
                <td>
                    <select name="vd_vps_api_provider">
                        <option value="ipinfo" <?php selected($api_provider, 'ipinfo'); ?>>
                            IPInfo.io (Free tier: 50k/month)
                        </option>
                        <option value="ipapi" <?php selected($api_provider, 'ipapi'); ?>>
                            IP-API.com (Free, no key required)
                        </option>
                        <option value="ipqualityscore" <?php selected($api_provider, 'ipqualityscore'); ?>>
                            IPQualityScore (Paid, most accurate)
                        </option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th colspan="2">
                    <h2>IP Whitelist</h2>
                    <p class="description">
                        IPs in this list will bypass VPS detection. Useful for testing or trusted VPS users.
                    </p>
                </th>
            </tr>
            <tr>
                <th><label>Whitelisted IPs</label></th>
                <td>
                    <textarea name="vd_vps_whitelist_ips" rows="10" class="large-text code"><?php 
                        echo implode("\n", $whitelist_ips); 
                    ?></textarea>
                    <p class="description">
                        One IP per line. Example:<br>
                        123.45.67.89<br>
                        234.56.78.90
                    </p>
                </td>
            </tr>
            
            <tr>
                <th colspan="2">
                    <h2>Datacenter IP Ranges</h2>
                </th>
            </tr>
            <tr>
                <th><label>Local Database Status</label></th>
                <td>
                    <?php
                    global $wpdb;
                    $table = $wpdb->prefix . 'bz_vd_datacenter_ip_ranges';
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    ?>
                    <p>
                        <strong><?php echo number_format($count); ?></strong> IP ranges in database
                    </p>
                    <button type="button" class="button" id="vd-update-ip-ranges">
                        Update IP Ranges from Cloud Providers
                    </button>
                    <p class="description">
                        This will fetch latest IP ranges from AWS, Google Cloud, Azure, etc.
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Save VPS Settings'); ?>
    </form>
    
    <hr>
    
    <h2>VPS Detection Test</h2>
    <p>Test an IP address to see if it's detected as VPS/Datacenter:</p>
    
    <div style="margin:20px 0;">
        <input type="text" id="test-ip" placeholder="123.45.67.89" class="regular-text">
        <button class="button" id="test-vps-btn">Test IP</button>
    </div>
    
    <div id="test-result" style="display:none; padding:15px; background:#f0f0f1; border-left:4px solid #2271b1; margin-top:15px;">
        <h3>Test Result:</h3>
        <div id="test-result-content"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Test VPS Detection
    $('#test-vps-btn').on('click', function() {
        var ip = $('#test-ip').val();
        
        if (!ip) {
            alert('Please enter an IP address');
            return;
        }
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_test_vps_detection',
                nonce: vdAdmin.nonce,
                ip: ip
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    var html = '<table class="widefat"><tbody>';
                    html += '<tr><th>IP Address</th><td>' + ip + '</td></tr>';
                    html += '<tr><th>Is VPS?</th><td>';
                    if (result.is_vps) {
                        html += '<span class="vd-badge inactive">YES - VPS Detected</span>';
                    } else {
                        html += '<span class="vd-badge active">NO - Regular IP</span>';
                    }
                    html += '</td></tr>';
                    html += '<tr><th>Provider</th><td>' + (result.provider || 'N/A') + '</td></tr>';
                    html += '<tr><th>Detection Method</th><td>' + result.method + '</td></tr>';
                    html += '<tr><th>Country</th><td>' + (result.country || 'N/A') + '</td></tr>';
                    html += '<tr><th>City</th><td>' + (result.city || 'N/A') + '</td></tr>';
                    html += '</tbody></table>';
                    
                    $('#test-result-content').html(html);
                    $('#test-result').show();
                }
            }
        });
    });
    
    // Update IP Ranges
    $('#vd-update-ip-ranges').on('click', function() {
        if (!confirm('This will update datacenter IP ranges from cloud providers. Continue?')) {
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_update_datacenter_ip_ranges',
                nonce: vdAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('IP ranges updated: ' + response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    btn.prop('disabled', false).text('Update IP Ranges from Cloud Providers');
                }
            }
        });
    });
});
</script>

<style>
#test-result {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
```

**AJAX Handlers:**

```php
// includes/class-vd-vps-ajax.php

class VD_VPS_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_vd_test_vps_detection', array($this, 'test_vps_detection'));
        add_action('wp_ajax_vd_update_datacenter_ip_ranges', array($this, 'update_ip_ranges'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_settings() {
        register_setting('vd_vps_settings', 'vd_vps_detection_method');
        register_setting('vd_vps_settings', 'vd_vps_api_provider');
        register_setting('vd_vps_settings', 'vd_vps_whitelist_ips', [
            'sanitize_callback' => array($this, 'sanitize_whitelist')
        ]);
    }
    
    public function sanitize_whitelist($input) {
        if (is_string($input)) {
            $lines = explode("\n", $input);
            $ips = [];
            
            foreach ($lines as $line) {
                $ip = trim($line);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ips[] = $ip;
                }
            }
            
            return $ips;
        }
        
        return [];
    }
    
    public function test_vps_detection() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $ip = sanitize_text_field($_POST['ip']);
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            wp_send_json_error(['message' => 'Invalid IP address']);
        }
        
        $result = VD_VPS_Detector::check_ip($ip);
        
        wp_send_json_success($result);
    }
    
    public function update_ip_ranges() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        // Fetch AWS IP ranges
        $aws_url = 'https://ip-ranges.amazonaws.com/ip-ranges.json';
        $response = wp_remote_get($aws_url, ['timeout' => 30]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Failed to fetch AWS IP ranges']);
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data || !isset($data['prefixes'])) {
            wp_send_json_error(['message' => 'Invalid data from AWS']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_datacenter_ip_ranges';
        
        // Clear old AWS ranges
        $wpdb->delete($table, ['provider' => 'Amazon AWS']);
        
        $inserted = 0;
        foreach ($data['prefixes'] as $prefix) {
            if (!isset($prefix['ip_prefix'])) continue;
            
            $cidr = $prefix['ip_prefix'];
            list($ip, $mask) = explode('/', $cidr);
            
            // Calculate IP range
            $ip_long = ip2long($ip);
            $mask_long = -1 << (32 - (int)$mask);
            $ip_start_long = $ip_long & $mask_long;
            $ip_end_long = $ip_long | (~$mask_long & 0xFFFFFFFF);
            
            $wpdb->insert($table, [
                'provider' => 'Amazon AWS',
                'ip_range' => $cidr,
                'ip_start' => long2ip($ip_start_long),
                'ip_end' => long2ip($ip_end_long),
                'ip_start_long' => $ip_start_long,
                'ip_end_long' => $ip_end_long,
                'country' => $prefix['region'] ?? null,
                'description' => $prefix['service'] ?? 'AWS',
                'created_at' => current_time('mysql')
            ]);
            
            $inserted++;
        }
        
        wp_send_json_success(['message' => "{$inserted} AWS IP ranges imported"]);
    }
}

new VD_VPS_Ajax();
```

---

### 📊 End of Day 10-11 Deliverables

**Test VPS Detection:**
```
SETUP:
1. Product #100: allow_vps = 0 (block VPS)
2. License with valid key

TEST VPS BLOCKING:
1. Access from regular home IP:
   ✅ Success, no VPS detected

2. Access from DigitalOcean VPS (104.131.x.x):
   ✅ Error: vps_blocked
   ✅ HTTP 403
   ✅ Message includes provider name

3. Access from AWS EC2:
   ✅ Blocked (if IP in database)
   ✅ Or detected via API

4. Add VPS IP to whitelist:
   Settings → VPS → Add IP to whitelist
   
5. Retry access from same VPS:
   ✅ Success! (whitelist bypass)

ADMIN VPS SETTINGS:
1. Go to Settings → VPS Detection
   ✅ Shows detection method options
   ✅ Shows whitelist textarea

2. Test IP: Enter 104.131.50.100
   ✅ Click "Test IP"
   ✅ Result shows: VPS Detected
   ✅ Provider: DigitalOcean

3. Test regular IP: 1.1.1.1
   ✅ Result: Not VPS (or Cloudflare)

4. Update IP Ranges:
   ✅ Click button
   ✅ Fetches from AWS
   ✅ Success message shows count

DATABASE:
wp_bz_vd_datacenter_ip_ranges:
✅ Thousands of IP ranges
✅ Fast lookup via ip_start_long/ip_end_long indexes

wp_bz_vd_license_devices:
✅ is_vps field populated
✅ vps_provider saved

wp_bz_vd_device_access_log:
✅ VPS blocks logged
✅ is_vps = 1 for VPS attempts
```

---

# 📅 NGÀY 12-13: PORTAL FRONTEND UI

### 🎯 Mục tiêu
- Tạo trang Portal HTML/CSS/JS đẹp
- Device fingerprinting client-side
- Form nhập license key
- Hiển thị credentials
- Device list
- Responsive design

### 📝 Tasks

#### Task 12.1: Portal Shortcode & Page Setup (2h)

**Create shortcode for portal**

```php
// includes/class-vd-portal-shortcode.php

class VD_Portal_Shortcode {
    
    public function __construct() {
        add_shortcode('vd_license_portal', array($this, 'render_portal'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_portal_assets'));
    }
    
    public function enqueue_portal_assets() {
        // Only load on portal page
        if (!is_page() || !has_shortcode(get_post()->post_content, 'vd_license_portal')) {
            return;
        }
        
        // FingerprintJS (for device fingerprinting)
        wp_enqueue_script(
            'fingerprintjs',
            'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js',
            [],
            '3.0.0',
            true
        );
        
        // Portal CSS
        wp_enqueue_style(
            'vd-portal-css',
            VD_PLUGIN_URL . 'public/css/portal.css',
            [],
            '1.0.0'
        );
        
        // Portal JS
        wp_enqueue_script(
            'vd-portal-js',
            VD_PLUGIN_URL . 'public/js/portal.js',
            ['jquery', 'fingerprintjs'],
            '1.0.0',
            true
        );
        
        // Localize script
        wp_localize_script('vd-portal-js', 'vdPortal', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('vd/v1/'),
            'nonce' => wp_create_nonce('vd_portal_nonce')
        ]);
    }
    
    public function render_portal($atts) {
        ob_start();
        include VD_PLUGIN_DIR . 'public/templates/portal.php';
        return ob_get_clean();
    }
}

new VD_Portal_Shortcode();
```

---

#### Task 12.2: Portal HTML Template (3h)

**File: public/templates/portal.php**

```php
<div class="vd-portal-container">
    <div class="vd-portal-logo">
        <?php 
        $logo_url = get_option('vd_portal_logo_url', VD_PLUGIN_URL . 'assets/logo.png');
        ?>
        <img src="<?php echo esc_url($logo_url); ?>" alt="VidieuVN">
    </div>
    
    <!-- Step 1: Enter License Key -->
    <div id="vd-step-license" class="vd-step active">
        <div class="vd-card">
            <h2>Truy cập License</h2>
            <p class="vd-description">
                Nhập license key của bạn để lấy thông tin tài khoản
            </p>
            
            <form id="vd-license-form">
                <div class="vd-form-group">
                    <label for="license-key">License Key</label>
                    <input 
                        type="text" 
                        id="license-key" 
                        name="license_key"
                        placeholder="A3F9-K2L4-M8N1-P5Q7"
                        pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                        required
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <small class="vd-help-text">
                        License key đã được gửi vào email của bạn
                    </small>
                </div>
                
                <button type="submit" class="vd-btn vd-btn-primary" id="vd-access-btn">
                    <span class="vd-btn-text">Truy cập</span>
                    <span class="vd-btn-loader" style="display:none;">
                        <span class="vd-spinner"></span> Đang xử lý...
                    </span>
                </button>
            </form>
            
            <div id="vd-error-message" class="vd-alert vd-alert-error" style="display:none;">
                <strong>Lỗi:</strong> <span id="vd-error-text"></span>
            </div>
        </div>
    </div>
    
    <!-- Step 2: Display Credentials -->
    <div id="vd-step-credentials" class="vd-step" style="display:none;">
        <div class="vd-card">
            <!-- License Info Header -->
            <div class="vd-license-header">
                <div class="vd-license-status">
                    <span class="vd-badge vd-badge-success">
                        <svg width="16" height="16" fill="currentColor"><circle cx="8" cy="8" r="8"/></svg>
                        Active
                    </span>
                </div>
                <div class="vd-license-key">
                    <strong>License:</strong> <code id="display-license-key"></code>
                </div>
                <div class="vd-license-expiry">
                    <strong>Hết hạn:</strong> <span id="display-expiry"></span>
                </div>
            </div>
            
            <!-- Account Credentials -->
            <div class="vd-credentials-section">
                <h3>
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Thông tin đăng nhập
                </h3>
                
                <div id="credentials-list" class="vd-credentials-list">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Devices Section -->
            <div class="vd-devices-section">
                <h3>
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    Thiết bị của bạn
                    <span class="vd-device-count" id="device-count-badge">0/0</span>
                </h3>
                
                <div id="devices-list" class="vd-devices-list">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Actions -->
            <div class="vd-actions">
                <button class="vd-btn vd-btn-secondary" id="vd-back-btn">
                    ← Nhập license khác
                </button>
                <button class="vd-btn vd-btn-outline" id="vd-refresh-btn">
                    🔄 Làm mới
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="vd-loading-overlay" style="display:none;">
        <div class="vd-spinner-large"></div>
        <p>Đang tải...</p>
    </div>
</div>
```

---

#### Task 12.3: Portal CSS (4h)

**File: public/css/portal.css**

```css
/* VD License Portal Styles */

:root {
    --vd-primary: #2271b1;
    --vd-primary-hover: #135e96;
    --vd-success: #28a745;
    --vd-danger: #dc3545;
    --vd-warning: #ffc107;
    --vd-gray-50: #f9fafb;
    --vd-gray-100: #f3f4f6;
    --vd-gray-200: #e5e7eb;
    --vd-gray-300: #d1d5db;
    --vd-gray-600: #4b5563;
    --vd-gray-800: #1f2937;
    --vd-gray-900: #111827;
    --vd-border-radius: 8px;
    --vd-box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --vd-box-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Container */
.vd-portal-container {
    max-width: 600px;
    margin: 60px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

/* Logo */
.vd-portal-logo {
    text-align: center;
    margin-bottom: 40px;
}

.vd-portal-logo img {
    max-width: 200px;
    height: auto;
}

/* Card */
.vd-card {
    background: white;
    border-radius: var(--vd-border-radius);
    box-shadow: var(--vd-box-shadow-lg);
    padding: 40px;
    margin-bottom: 20px;
}

.vd-card h2 {
    margin: 0 0 10px 0;
    color: var(--vd-gray-900);
    font-size: 24px;
    font-weight: 600;
}

.vd-description {
    color: var(--vd-gray-600);
    margin: 0 0 30px 0;
    font-size: 15px;
}

/* Form */
.vd-form-group {
    margin-bottom: 24px;
}

.vd-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--vd-gray-800);
    font-size: 14px;
}

.vd-form-group input[type="text"] {
    width: 100%;
    padding: 12px 16px;
    font-size: 16px;
    border: 2px solid var(--vd-gray-300);
    border-radius: var(--vd-border-radius);
    transition: all 0.2s;
    font-family: 'Courier New', monospace;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.vd-form-group input[type="text"]:focus {
    outline: none;
    border-color: var(--vd-primary);
    box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1);
}

.vd-help-text {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    color: var(--vd-gray-600);
}

/* Buttons */
.vd-btn {
    display: inline-block;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 500;
    border: none;
    border-radius: var(--vd-border-radius);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.vd-btn-primary {
    background: var(--vd-primary);
    color: white;
    width: 100%;
}

.vd-btn-primary:hover {
    background: var(--vd-primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--vd-box-shadow);
}

.vd-btn-primary:disabled {
    background: var(--vd-gray-300);
    cursor: not-allowed;
    transform: none;
}

.vd-btn-secondary {
    background: var(--vd-gray-100);
    color: var(--vd-gray-800);
}

.vd-btn-secondary:hover {
    background: var(--vd-gray-200);
}

.vd-btn-outline {
    background: white;
    color: var(--vd-primary);
    border: 2px solid var(--vd-primary);
}

.vd-btn-outline:hover {
    background: var(--vd-primary);
    color: white;
}

/* Button Loading State */
.vd-btn-loader {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.vd-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Alerts */
.vd-alert {
    padding: 12px 16px;
    border-radius: var(--vd-border-radius);
    margin: 20px 0;
    font-size: 14px;
}

.vd-alert-error {
    background: #fee;
    color: #c00;
    border-left: 4px solid var(--vd-danger);
}

.vd-alert-success {
    background: #efe;
    color: #060;
    border-left: 4px solid var(--vd-success);
}

/* Badge */
.vd-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
}

.vd-badge-success {
    background: #d4edda;
    color: #155724;
}

.vd-badge-warning {
    background: #fff3cd;
    color: #856404;
}

.vd-badge-danger {
    background: #f8d7da;
    color: #721c24;
}

/* License Header */
.vd-license-header {
    background: var(--vd-gray-50);
    padding: 20px;
    border-radius: var(--vd-border-radius);
    margin-bottom: 30px;
    display: grid;
    gap: 12px;
}

.vd-license-header code {
    background: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    letter-spacing: 1px;
}

/* Credentials Section */
.vd-credentials-section {
    margin-bottom: 30px;
}

.vd-credentials-section h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px 0;
    font-size: 18px;
    color: var(--vd-gray-800);
}

.vd-credential-item {
    background: var(--vd-gray-50);
    padding: 16px;
    border-radius: var(--vd-border-radius);
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vd-credential-label {
    font-weight: 500;
    color: var(--vd-gray-600);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vd-credential-value {
    display: flex;
    align-items: center;
    gap: 8px;
}

.vd-credential-value code {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    background: white;
    padding: 6px 10px;
    border-radius: 4px;
}

.vd-credential-value.masked code {
    letter-spacing: 3px;
}

.vd-btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: var(--vd-primary);
    transition: color 0.2s;
}

.vd-btn-icon:hover {
    color: var(--vd-primary-hover);
}

/* Devices Section */
.vd-devices-section h3 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 0 16px 0;
    font-size: 18px;
}

.vd-device-count {
    font-size: 14px;
    font-weight: normal;
    color: var(--vd-gray-600);
}

.vd-device-item {
    background: var(--vd-gray-50);
    padding: 16px;
    border-radius: var(--vd-border-radius);
    margin-bottom: 12px;
    display: grid;
    gap: 8px;
}

.vd-device-name {
    font-weight: 500;
    color: var(--vd-gray-900);
    display: flex;
    align-items: center;
    gap: 8px;
}

.vd-device-current {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--vd-success);
}

.vd-device-meta {
    font-size: 13px;
    color: var(--vd-gray-600);
}

/* Actions */
.vd-actions {
    margin-top: 30px;
    display: flex;
    gap: 12px;
}

/* Loading Overlay */
#vd-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.vd-spinner-large {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

#vd-loading-overlay p {
    color: white;
    margin-top: 16px;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 640px) {
    .vd-portal-container {
        margin: 30px auto;
    }
    
    .vd-card {
        padding: 24px;
    }
    
    .vd-actions {
        flex-direction: column;
    }
    
    .vd-credential-item,
    .vd-device-item {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* Success Animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.vd-step.active {
    animation: slideIn 0.3s ease-out;
}
```

---

#### Task 12.4: Portal JavaScript (5h)

**File: public/js/portal.js**

```javascript
(function($) {
    'use strict';
    
    let deviceFingerprint = null;
    let deviceToken = null;
    let deviceCombinedId = null;
    
    // Initialize FingerprintJS
    async function initFingerprint() {
        try {
            const fp = await FingerprintJS.load();
            const result = await fp.get();
            
            deviceFingerprint = result.visitorId;
            deviceToken = 'dt_' + generateRandomToken();
            deviceCombinedId = await generateCombinedId(deviceFingerprint, deviceToken);
            
            console.log('Device fingerprint initialized');
        } catch (error) {
            console.error('Fingerprint error:', error);
            // Fallback to random ID
            deviceFingerprint = 'fp_' + generateRandomToken();
            deviceToken = 'dt_' + generateRandomToken();
            deviceCombinedId = await generateCombinedId(deviceFingerprint, deviceToken);
        }
    }
    
    // Generate random token
    function generateRandomToken() {
        return Math.random().toString(36).substring(2, 15) + 
               Math.random().toString(36).substring(2, 15);
    }
    
    // Generate SHA256 hash for combined ID
    async function generateCombinedId(fingerprint, token) {
        const combined = fingerprint + '|' + token;
        const msgBuffer = new TextEncoder().encode(combined);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }
    
    // Get device name
    function getDeviceName() {
        const ua = navigator.userAgent;
        let deviceType = 'Desktop';
        let os = 'Unknown OS';
        let browser = 'Unknown Browser';
        
        // Detect device type
        if (/Mobile|Android|iPhone|iPad|iPod/.test(ua)) {
            deviceType = /iPad/.test(ua) ? 'Tablet' : 'Mobile';
        }
        
        // Detect OS
        if (/Windows/.test(ua)) os = 'Windows ' + (ua.match(/Windows NT (\d+\.\d+)/) || ['',''])[1];
        else if (/Mac OS X/.test(ua)) os = 'macOS';
        else if (/Android/.test(ua)) os = 'Android';
        else if (/iPhone|iPad/.test(ua)) os = 'iOS';
        else if (/Linux/.test(ua)) os = 'Linux';
        
        // Detect browser
        if (/Chrome/.test(ua) && !/Edg/.test(ua)) browser = 'Chrome';
        else if (/Safari/.test(ua) && !/Chrome/.test(ua)) browser = 'Safari';
        else if (/Firefox/.test(ua)) browser = 'Firefox';
        else if (/Edg/.test(ua)) browser = 'Edge';
        
        return `${deviceType} - ${os} - ${browser}`;
    }
    
    // Access license
    $('#vd-license-form').on('submit', async function(e) {
        e.preventDefault();
        
        const licenseKey = $('#license-key').val().trim().toUpperCase();
        
        if (!licenseKey) {
            showError('Vui lòng nhập license key');
            return;
        }
        
        // Show loading
        setLoading(true);
        hideError();
        
        try {
            // Ensure fingerprint is initialized
            if (!deviceFingerprint) {
                await initFingerprint();
            }
            
            // Call API
            const response = await fetch(vdPortal.restUrl + 'license/access', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    license_key: licenseKey,
                    device_fingerprint: deviceFingerprint,
                    device_token: deviceToken,
                    device_combined_id: deviceCombinedId,
                    device_name: getDeviceName()
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                displayCredentials(data);
            } else {
                showError(data.error.message || 'Có lỗi xảy ra');
            }
            
        } catch (error) {
            console.error('API Error:', error);
            showError('Không thể kết nối đến server. Vui lòng thử lại.');
        } finally {
            setLoading(false);
        }
    });
    
    // Display credentials
    function displayCredentials(data) {
        // Hide license form, show credentials
        $('#vd-step-license').hide();
        $('#vd-step-credentials').show();
        
        // Display license info
        $('#display-license-key').text(data.license.key);
        $('#display-expiry').text(formatDate(data.license.valid_until));
        
        // Display account credentials
        const credentials = data.account.credentials;
        let credHtml = '';
        
        for (const [key, value] of Object.entries(credentials)) {
            const isSensitive = ['password', 'pin', 'cookie', 'token'].some(
                s => key.toLowerCase().includes(s)
            );
            
            const displayKey = key.replace(/_/g, ' ')
                                 .replace(/\b\w/g, l => l.toUpperCase());
            
            credHtml += `
                <div class="vd-credential-item">
                    <div>
                        <div class="vd-credential-label">${displayKey}</div>
                        <div class="vd-credential-value ${isSensitive ? 'masked' : ''}" data-value="${escapeHtml(value)}">
                            <code>${isSensitive ? '•'.repeat(value.length) : escapeHtml(value)}</code>
                            ${isSensitive ? `
                                <button class="vd-btn-icon toggle-visibility" title="Show/Hide">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            ` : ''}
                            <button class="vd-btn-icon copy-credential" title="Copy">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        $('#credentials-list').html(credHtml);
        
        // Display devices
        displayDevices(data.devices, data.device);
        
        // Scroll to top
        window.scrollTo(0, 0);
    }
    
    // Display devices
    function displayDevices(devices, currentDevice) {
        const maxDevices = 3; // This should come from share config
        const deviceCount = devices.filter(d => d.status === 'active').length;
        
        $('#device-count-badge').text(`${deviceCount}/${maxDevices}`);
        
        let devicesHtml = '';
        
        devices.forEach(device => {
            const isCurrent = currentDevice && device.id === currentDevice.device_id;
            
            devicesHtml += `
                <div class="vd-device-item">
                    <div class="vd-device-name">
                        ${escapeHtml(device.name)}
                        ${isCurrent ? '<span class="vd-device-current">● Thiết bị này</span>' : ''}
                        <span class="vd-badge vd-badge-${device.status === 'active' ? 'success' : 'danger'}">
                            ${device.status}
                        </span>
                    </div>
                    <div class="vd-device-meta">
                        Truy cập lần đầu: ${formatDate(device.first_access)} • 
                        Lần cuối: ${formatDate(device.last_access)} • 
                        ${device.access_count} lần
                    </div>
                </div>
            `;
        });
        
        if (devices.length === 0) {
            devicesHtml = '<p style="color:#999;">Chưa có thiết bị nào được đăng ký.</p>';
        }
        
        $('#devices-list').html(devicesHtml);
    }
    
    // Toggle credential visibility
    $(document).on('click', '.toggle-visibility', function() {
        const $valueDiv = $(this).closest('.vd-credential-value');
        const $code = $valueDiv.find('code');
        const realValue = $valueDiv.data('value');
        
        if ($valueDiv.hasClass('masked')) {
            $code.text(realValue);
            $valueDiv.removeClass('masked');
        } else {
            $code.text('•'.repeat(realValue.length));
            $valueDiv.addClass('masked');
        }
    });
    
    // Copy credential
    $(document).on('click', '.copy-credential', function() {
        const value = $(this).closest('.vd-credential-value').data('value');
        
        navigator.clipboard.writeText(value).then(() => {
            // Show success feedback
            const $btn = $(this);
            const originalHTML = $btn.html();
            
            $btn.html(`
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            `);
            
            setTimeout(() => {
                $btn.html(originalHTML);
            }, 2000);
        }).catch(err => {
            alert('Không thể copy. Vui lòng copy thủ công.');
        });
    });
    
    // Back button
    $('#vd-back-btn').on('click', function() {
        $('#vd-step-credentials').hide();
        $('#vd-step-license').show();
        $('#license-key').val('').focus();
    });
    
    // Refresh button
    $('#vd-refresh-btn').on('click', function() {
        const licenseKey = $('#display-license-key').text();
        $('#license-key').val(licenseKey);
        $('#vd-license-form').submit();
    });
    
    // Helper functions
    function setLoading(loading) {
        const $btn = $('#vd-access-btn');
        
        if (loading) {
            $btn.prop('disabled', true);
            $btn.find('.vd-btn-text').hide();
            $btn.find('.vd-btn-loader').show();
        } else {
            $btn.prop('disabled', false);
            $btn.find('.vd-btn-text').show();
            $btn.find('.vd-btn-loader').hide();
        }
    }
    
    function showError(message) {
        $('#vd-error-text').text(message);
        $('#vd-error-message').slideDown();
    }
    
    function hideError() {
        $('#vd-error-message').slideUp();
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('vi-VN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    // Initialize on page load
    $(document).ready(function() {
        initFingerprint();
        
        // Focus on license input
        $('#license-key').focus();
        
        // Auto-format license key input
        $('#license-key').on('input', function() {
            let value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            // Add dashes
            if (value.length > 4) value = value.slice(0,4) + '-' + value.slice(4);
            if (value.length > 9) value = value.slice(0,9) + '-' + value.slice(9);
            if (value.length > 14) value = value.slice(0,14) + '-' + value.slice(14);
            if (value.length > 19) value = value.slice(0,19);
            
            $(this).val(value);
        });
    });
    
})(jQuery);
```

---

### 📊 End of Day 12-13 Deliverables

**Test Portal:**
```
SETUP:
1. Create WP page: "License Portal"
2. Add shortcode: [vd_license_portal]
3. Publish page

ACCESS PORTAL:
1. Go to: vidieu.vn/license-portal/
   ✅ Portal loads
   ✅ Logo displays
   ✅ License input field visible

2. Enter license key: A3F9-K2L4-M8N1-P5Q7
   ✅ Auto-formats with dashes
   ✅ Uppercase conversion

3. Click "Truy cập":
   ✅ Loading state shows
   ✅ API called
   ✅ Credentials screen appears

4. Credentials screen shows:
   ✅ License key
   ✅ Expiry date
   ✅ Account credentials (email, password, etc.)
   ✅ Password masked with bullets

5. Click eye icon on password:
   ✅ Password visible
   ✅ Click again → Masked

6. Click copy button:
   ✅ Copied to clipboard
   ✅ Checkmark shows briefly

7. Devices section:
   ✅ Shows current device with green dot
   ✅ Device count: 1/3
   ✅ First access, last access times

8. Click "Làm mới":
   ✅ Reloads data
   ✅ Updates access count

9. Mobile test:
   ✅ Responsive design
   ✅ Touch-friendly buttons
   ✅ Readable on small screens

BROWSER CONSOLE:
✅ No JavaScript errors
✅ Device fingerprint logged
✅ API calls successful

NETWORK TAB:
✅ POST to /wp-json/vd/v1/license/access
✅ Includes device data
✅ Response 200 OK
```

---

# 📅 NGÀY 14: EMAIL NOTIFICATIONS

### 🎯 Mục tiêu
- Tạo email template đẹp
- Gửi email khi order completed
- Include license key & portal link
- HTML email với styling
- Support multi-language

### 📝 Tasks

#### Task 14.1: Email Template System (3h)

**File: includes/class-vd-email-manager.php**

```php
<?php

class VD_Email_Manager {
    
    public function __construct() {
        // Hook to send email after license sync
        add_action('vd_license_synced', array($this, 'send_license_email'), 10, 2);
    }
    
    /**
     * Send license delivery email
     */
    public function send_license_email($vd_license_id, $lmfwc_license_id) {
        global $wpdb;
        
        // Get license data
        $license = $this->get_license_data($vd_license_id);
        
        if (!$license) {
            error_log("VD Email: License #{$vd_license_id} not found");
            return;
        }
        
        // Get order
        $order = wc_get_order($license['order_id']);
        
        if (!$order) {
            error_log("VD Email: Order #{$license['order_id']} not found");
            return;
        }
        
        // Get customer email
        $customer_email = $order->get_billing_email();
        
        // Get product
        $product = wc_get_product($license['product_id']);
        
        // Prepare email data
        $email_data = [
            'customer_name' => $order->get_billing_first_name() ?: 'Quý khách',
            'product_name' => $product->get_name(),
            'license_key' => $license['license_key'],
            'valid_from' => $license['valid_from'],
            'valid_until' => $license['valid_until'],
            'portal_url' => get_permalink(get_option('vd_portal_page_id')),
            'order_id' => $license['order_id'],
            'support_email' => get_option('admin_email')
        ];
        
        // Render email template
        $subject = $this->get_email_subject($email_data);
        $message = $this->render_email_template($email_data);
        
        // Send email
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        $sent = wp_mail($customer_email, $subject, $message, $headers);
        
        if ($sent) {
            error_log("VD Email: License email sent to {$customer_email}");
            
            // Update license meta
            $wpdb->update(
                $wpdb->prefix . 'bz_vd_license_keys',
                ['updated_at' => current_time('mysql')],
                ['id' => $vd_license_id]
            );
        } else {
            error_log("VD Email: Failed to send to {$customer_email}");
        }
        
        return $sent;
    }
    
    /**
     * Get license data
     */
    private function get_license_data($license_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bz_vd_license_keys';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $license_id
        ), ARRAY_A);
    }
    
    /**
     * Get email subject
     */
    private function get_email_subject($data) {
        $template = get_option('vd_email_subject', 
            '[{site_name}] 🎬 License {product_name} - Hướng dẫn lấy tài khoản'
        );
        
        return str_replace(
            ['{site_name}', '{product_name}', '{license_key}'],
            [get_bloginfo('name'), $data['product_name'], $data['license_key']],
            $template
        );
    }
    
    /**
     * Render email template
     */
    private function render_email_template($data) {
        ob_start();
        include VD_PLUGIN_DIR . 'includes/email-templates/license-delivery.php';
        return ob_get_clean();
    }
}

// Initialize
new VD_Email_Manager();

// Trigger email from sync class
// Add to VD_LMfWC_Sync::sync_license_to_vd() after successful insert:
// do_action('vd_license_synced', $vd_license_id, $lmfwc_license_id);
```

---

#### Task 14.2: Email HTML Template (4h)

**File: includes/email-templates/license-delivery.php**

```php
<?php
// Email template for license delivery
// Variables available: $data array with customer_name, product_name, license_key, etc.

$primary_color = get_option('vd_email_primary_color', '#2271b1');
$logo_url = get_option('vd_email_logo_url', get_site_icon_url());
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($data['product_name']); ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f4f4f5;">
    
    <!-- Email Container -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; padding: 40px 0;">
        <tr>
            <td align="center">
                
                <!-- Email Content -->
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $primary_color; ?>dd 100%); padding: 40px 30px; text-align: center;">
                            <?php if ($logo_url): ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo get_bloginfo('name'); ?>" style="max-width: 150px; height: auto; margin-bottom: 20px;">
                            <?php endif; ?>
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">
                                🎉 License của bạn đã sẵn sàng!
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Greeting -->
                    <tr>
                        <td style="padding: 30px 30px 20px 30px;">
                            <p style="margin: 0; font-size: 16px; color: #18181b; line-height: 1.6;">
                                Xin chào <strong><?php echo esc_html($data['customer_name']); ?></strong>,
                            </p>
                            <p style="margin: 16px 0 0 0; font-size: 16px; color: #52525b; line-height: 1.6;">
                                Cảm ơn bạn đã mua <strong><?php echo esc_html($data['product_name']); ?></strong> tại <?php echo get_bloginfo('name'); ?>! 
                                License key của bạn đã được tạo thành công.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- License Key Box -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #fafafa 0%, #f4f4f5 100%); border: 2px dashed <?php echo $primary_color; ?>; border-radius: 8px; padding: 24px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #71717a; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                                            License Key của bạn
                                        </p>
                                        <p style="margin: 0; font-family: 'Courier New', monospace; font-size: 24px; color: <?php echo $primary_color; ?>; letter-spacing: 2px; font-weight: bold;">
                                            <?php echo esc_html($data['license_key']); ?>
                                        </p>
                                        <p style="margin: 12px 0 0 0; font-size: 14px; color: #a1a1aa;">
                                            <strong>Hạn sử dụng:</strong> <?php echo date('d/m/Y', strtotime($data['valid_until'])); ?>
                                            (<?php echo ceil((strtotime($data['valid_until']) - time()) / 86400); ?> ngày)
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- CTA Button -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; text-align: center;">
                            <a href="<?php echo esc_url($data['portal_url'] . '?license=' . $data['license_key']); ?>" 
                               style="display: inline-block; background-color: <?php echo $primary_color; ?>; color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 6px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                🔑 Lấy thông tin tài khoản ngay
                            </a>
                            <p style="margin: 16px 0 0 0; font-size: 13px; color: #a1a1aa;">
                                Hoặc copy link: <?php echo esc_url($data['portal_url']); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Instructions -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px 20px; border-radius: 4px;">
                                <p style="margin: 0 0 12px 0; font-size: 15px; color: #92400e; font-weight: 600;">
                                    📋 Hướng dẫn sử dụng:
                                </p>
                                <ol style="margin: 0; padding-left: 20px; color: #78350f; font-size: 14px; line-height: 1.8;">
                                    <li>Click vào nút "<strong>Lấy thông tin tài khoản</strong>" phía trên</li>
                                    <li>Nhập license key: <code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-family: monospace;"><?php echo $data['license_key']; ?></code></li>
                                    <li>Hệ thống sẽ hiển thị thông tin đăng nhập cho bạn</li>
                                    <li>Copy thông tin và sử dụng ngay!</li>
                                </ol>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Important Notes -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 16px 20px; border-radius: 4px;">
                                <p style="margin: 0 0 8px 0; font-size: 15px; color: #7f1d1d; font-weight: 600;">
                                    ⚠️ Lưu ý quan trọng:
                                </p>
                                <ul style="margin: 0; padding-left: 20px; color: #7f1d1d; font-size: 14px; line-height: 1.8;">
                                    <li>Không chia sẻ license key cho người khác</li>
                                    <li>Giới hạn <strong>tối đa 2-3 thiết bị</strong> (tùy gói)</li>
                                    <li>Không đổi mật khẩu tài khoản được cung cấp</li>
                                    <li>Liên hệ support nếu gặp vấn đề</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer Info -->
                    <tr>
                        <td style="padding: 30px; background-color: #fafafa; border-top: 1px solid #e4e4e7;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="50%" style="padding-right: 10px;">
                                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #71717a; font-weight: 600;">
                                            📞 Hỗ trợ
                                        </p>
                                        <p style="margin: 0; font-size: 14px; color: #52525b; line-height: 1.6;">
                                            Email: <?php echo esc_html($data['support_email']); ?><br>
                                            Response trong vòng 24h
                                        </p>
                                    </td>
                                    <td width="50%" style="padding-left: 10px; text-align: right;">
                                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #71717a; font-weight: 600;">
                                            📦 Đơn hàng
                                        </p>
                                        <p style="margin: 0; font-size: 14px; color: #52525b; line-height: 1.6;">
                                            Order #<?php echo $data['order_id']; ?><br>
                                            <?php echo date('d/m/Y H:i', strtotime($data['valid_from'])); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Social Footer -->
                    <tr>
                        <td style="padding: 20px 30px; background-color: #18181b; text-align: center;">
                            <p style="margin: 0 0 12px 0; font-size: 14px; color: #a1a1aa;">
                                Theo dõi chúng tôi
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <!-- Social icons - customize as needed -->
                                        <a href="#" style="display: inline-block; margin: 0 8px;">
                                            <img src="https://via.placeholder.com/24/ffffff/000000?text=F" alt="Facebook" style="width: 24px; height: 24px;">
                                        </a>
                                        <a href="#" style="display: inline-block; margin: 0 8px;">
                                            <img src="https://via.placeholder.com/24/ffffff/000000?text=T" alt="Telegram" style="width: 24px; height: 24px;">
                                        </a>
                                        <a href="#" style="display: inline-block; margin: 0 8px;">
                                            <img src="https://via.placeholder.com/24/ffffff/000000?text=Z" alt="Zalo" style="width: 24px; height: 24px;">
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 16px 0 0 0; font-size: 12px; color: #71717a; line-height: 1.6;">
                                © <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. All rights reserved.<br>
                                Email này được gửi tự động, vui lòng không reply.
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>
```

---

#### Task 14.3: Email Settings UI (2h)

**Add email settings tab**

**File: admin/partials/settings-email.php**

```php
<?php
// Email settings

$subject = get_option('vd_email_subject', '[{site_name}] 🎬 License {product_name} - Hướng dẫn lấy tài khoản');
$primary_color = get_option('vd_email_primary_color', '#2271b1');
$logo_url = get_option('vd_email_logo_url', get_site_icon_url());
$from_name = get_option('vd_email_from_name', get_bloginfo('name'));
$from_email = get_option('vd_email_from_email', get_option('admin_email'));
?>

<div class="wrap vd-wrap">
    <h1>Email Settings</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('vd_email_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th colspan="2"><h2>Email Sender</h2></th>
            </tr>
            <tr>
                <th><label for="from-name">From Name</label></th>
                <td>
                    <input type="text" id="from-name" name="vd_email_from_name" 
                           value="<?php echo esc_attr($from_name); ?>" class="regular-text">
                    <p class="description">Name that appears in "From" field</p>
                </td>
            </tr>
            <tr>
                <th><label for="from-email">From Email</label></th>
                <td>
                    <input type="email" id="from-email" name="vd_email_from_email" 
                           value="<?php echo esc_attr($from_email); ?>" class="regular-text">
                    <p class="description">Email address for sending notifications</p>
                </td>
            </tr>
            
            <tr>
                <th colspan="2"><h2>Email Template</h2></th>
            </tr>
            <tr>
                <th><label for="email-subject">Subject Line</label></th>
                <td>
                    <input type="text" id="email-subject" name="vd_email_subject" 
                           value="<?php echo esc_attr($subject); ?>" class="large-text">
                    <p class="description">
                        Available variables: {site_name}, {product_name}, {license_key}<br>
                        Example: [VidieuVN] 🎬 License Netflix Premium - Hướng dẫn lấy tài khoản
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="primary-color">Primary Color</label></th>
                <td>
                    <input type="color" id="primary-color" name="vd_email_primary_color" 
                           value="<?php echo esc_attr($primary_color); ?>">
                    <p class="description">Main color for email header and buttons</p>
                </td>
            </tr>
            <tr>
                <th><label for="logo-url">Logo URL</label></th>
                <td>
                    <input type="url" id="logo-url" name="vd_email_logo_url" 
                           value="<?php echo esc_attr($logo_url); ?>" class="large-text">
                    <button type="button" class="button" id="upload-logo-btn">Upload Logo</button>
                    <p class="description">Logo displayed in email header (recommended: 150x50px)</p>
                    <?php if ($logo_url): ?>
                        <p><img src="<?php echo esc_url($logo_url); ?>" style="max-width: 150px; margin-top: 10px;"></p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th colspan="2"><h2>Test Email</h2></th>
            </tr>
            <tr>
                <th><label for="test-email">Send Test Email</label></th>
                <td>
                    <input type="email" id="test-email" placeholder="your@email.com" class="regular-text">
                    <button type="button" class="button" id="send-test-email-btn">Send Test</button>
                    <p class="description">Send a sample license email to test appearance</p>
                    <div id="test-email-result" style="margin-top: 10px;"></div>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Save Email Settings'); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Upload logo
    $('#upload-logo-btn').on('click', function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Select Logo',
            button: {
                text: 'Use this logo'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#logo-url').val(attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // Send test email
    $('#send-test-email-btn').on('click', function() {
        var email = $('#test-email').val();
        
        if (!email) {
            alert('Please enter an email address');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Sending...');
        $('#test-email-result').html('');
        
        $.ajax({
            url: vdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vd_send_test_email',
                nonce: vdAdmin.nonce,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    $('#test-email-result').html('<span style="color:green;">✓ Test email sent successfully!</span>');
                } else {
                    $('#test-email-result').html('<span style="color:red;">✗ Error: ' + response.data.message + '</span>');
                }
                $btn.prop('disabled', false).text('Send Test');
            }
        });
    });
});
</script>
```

**AJAX Handler:**

```php
// includes/class-vd-email-ajax.php

class VD_Email_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_vd_send_test_email', array($this, 'send_test_email'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_settings() {
        register_setting('vd_email_settings', 'vd_email_from_name');
        register_setting('vd_email_settings', 'vd_email_from_email', [
            'sanitize_callback' => 'sanitize_email'
        ]);
        register_setting('vd_email_settings', 'vd_email_subject');
        register_setting('vd_email_settings', 'vd_email_primary_color');
        register_setting('vd_email_settings', 'vd_email_logo_url', [
            'sanitize_callback' => 'esc_url_raw'
        ]);
    }
    
    public function send_test_email() {
        check_ajax_referer('vd_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Invalid email address']);
        }
        
        // Create fake data for test
        $email_data = [
            'customer_name' => 'Test Customer',
            'product_name' => 'Netflix Premium 4K - 30 ngày',
            'license_key' => 'TEST-1234-ABCD-5678',
            'valid_from' => current_time('mysql'),
            'valid_until' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'portal_url' => home_url('/license-portal'),
            'order_id' => '9999',
            'support_email' => get_option('admin_email')
        ];
        
        $email_manager = new VD_Email_Manager();
        
        // Render template
        ob_start();
        $data = $email_data; // Make available to template
        include VD_PLUGIN_DIR . 'includes/email-templates/license-delivery.php';
        $message = ob_get_clean();
        
        // Send
        $subject = '[TEST] ' . str_replace(
            ['{site_name}', '{product_name}'],
            [get_bloginfo('name'), $email_data['product_name']],
            get_option('vd_email_subject')
        );
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('vd_email_from_name') . ' <' . get_option('vd_email_from_email') . '>'
        ];
        
        $sent = wp_mail($email, $subject, $message, $headers);
        
        if ($sent) {
            wp_send_json_success(['message' => 'Test email sent']);
        } else {
            wp_send_json_error(['message' => 'Failed to send email']);
        }
    }
}

new VD_Email_Ajax();
```

---

### 📊 End of Day 14 Deliverables

**Test Email System:**
```
SETUP SMTP (Recommended):
1. Install WP Mail SMTP plugin
2. Configure with Gmail/SendGrid/Mailgun
3. Test SMTP connection

EMAIL SETTINGS:
1. Go to VD License → Settings → Email
   ✅ Shows all email options

2. Customize settings:
   - From Name: VidieuVN
   - From Email: noreply@vidieu.vn
   - Subject: [VidieuVN] 🎬...
   - Primary Color: #2271b1
   - Logo: Upload custom logo

3. Send test email:
   ✅ Enter your email
   ✅ Click "Send Test"
   ✅ Receive email within 1 minute

EMAIL APPEARANCE:
✅ Beautiful HTML design
✅ Responsive on mobile
✅ Logo displays
✅ License key prominent
✅ CTA button clear
✅ Instructions easy to follow
✅ Social footer

ORDER FLOW TEST:
1. Create test order
2. Mark as completed
3. Check customer email inbox:
   ✅ Email received
   ✅ Subject correct
   ✅ License key displayed
   ✅ Portal link works
   ✅ Click portal link → Pre-fills license

GMAIL/OUTLOOK:
✅ Email not in spam
✅ Images load
✅ Buttons clickable
✅ Readable on mobile app
```

---

## 🎯 SUMMARY PROGRESS

**Day 8-14 completed:**
- ✅ Device tracking & management
- ✅ VPS detection & blocking
- ✅ Portal frontend (HTML/CSS/JS)
- ✅ Device fingerprinting
- ✅ Beautiful email templates
- ✅ Email customization settings

**Mục tiêu Ngày 15-16:** Xây dựng Dashboard & Analytics với KPI cards, charts, logs viewer, và export reports.

---

## 📅 NGÀY 15: ADMIN DASHBOARD & ANALYTICS

### 🎯 Mục tiêu
- Tạo Dashboard Overview với KPI cards
- Xây dựng Analytics page với charts (Chart.js)
- Hiển thị Access Logs với filters
- Export logs (CSV, Excel)

---

### 📝 PROMPT 1: Dashboard Overview Page (3h)

```
VD License Manager - Dashboard Overview

TÃI LIỆU THAM KHẢO:
- VD_License_Manager_Roadmap.md (section Dashboard)
- FLOW_4_Admin_Management.xml (section 5.2 Analytics Dashboard)

YÊU CẦU:
Tạo trang Dashboard tổng quan với KPI cards và quick stats.

FILE: admin/partials/dashboard.php

NHIỆM VỤ:

1. PAGE LAYOUT:
   - Header: "VD License Manager - Dashboard"
   - Date Range Selector (top right):
     * Presets: Last 7 days, Last 30 days, This month, Custom
     * Default: Last 30 days
     * Store in $_GET['date_range']

2. KPI CARDS (6 cards in 3x2 grid):

Card 1: Total Requests
- Query: COUNT(*) FROM bz_vd_device_access_log WHERE created_at IN [date_range]
- Value: 8,945 requests
- Change: Calculate +12% vs previous period
- Icon: 📊 (green arrow if increase)
- Sparkline: Mini chart (last 7 days trend)

Card 2: Success Rate  
- Query: COUNT(CASE result='success') / COUNT(*) * 100
- Value: 94.2% (8,427/8,945)
- Change: +1.2% vs previous
- Color: Green if >95%, Yellow if 90-95%, Red if <90%
- Gauge chart (circular progress)

Card 3: Active Licenses
- Query: COUNT(*) FROM bz_vd_license_keys WHERE status='active'
- Value: 1,234 licenses
- Sub-text: "890 (72%) accessed at least once"

Card 4: VPS Blocked
- Query: COUNT(*) WHERE result='blocked_vps'
- Value: 234 requests (2.6%)
- Change: -5% vs previous (improvement - green arrow down)
- Color: Red (high alert)

Card 5: Device Registrations
- Query: COUNT(*) FROM bz_vd_license_devices WHERE created_at IN [date_range]
- Value: 456 new devices
- Change: +8% vs previous

Card 6: Avg Requests/License
- Query: AVG(request_count) from grouped query
- Value: 7.3 requests/license
- Change: +0.5 vs previous

3. QUICK ACCESS SECTIONS:

Recent Licenses (Table):
- Last 10 licenses created
- Columns: Key, Product, Customer, Status, Created
- Link: "View All Licenses"

Top Active Licenses (Table):
- Top 10 by request count (today)
- Columns: Key, Customer, Requests, Success Rate
- Link: "View Analytics"

Recent Device Activity (List):
- Last 10 device registrations/removals
- Format: "[Time] Device [name] registered/removed for license [key]"
- Link: "View All Devices"

System Alerts (if any):
- VPS block rate > 10% in last hour
- Account expired (not updated)
- Pool capacity > 95%
- Display as colored alerts with icons

4. STYLING:
- Use CSS Grid for KPI cards (3 columns, gap 20px)
- Card style: white background, border-radius 8px, box-shadow
- Responsive: 2 columns on tablet, 1 column on mobile
- Icons: SVG or Font Awesome
- Colors: Match WordPress admin color scheme

5. HELPER FUNCTIONS:
```php
// Get date range timestamps
function get_date_range($preset) {
    // Return [start_timestamp, end_timestamp]
}

// Calculate percentage change
function calculate_change($current, $previous) {
    // Return [percentage, direction 'up'/'down']
}

// Format large numbers
function format_number($number) {
    // 1234 → 1.2K, 1234567 → 1.2M
}
```

TESTING:
1. Navigate to Dashboard → Shows all 6 KPI cards
2. Change date range → All stats update via AJAX
3. Click "View All Licenses" → Goes to Licenses page
4. Check responsive design on mobile
5. Verify SQL queries are optimized (use EXPLAIN)

DELIVERABLES:
- admin/partials/dashboard.php
- admin/css/dashboard.css
- admin/js/dashboard.js
- Helper functions in includes/class-vd-analytics.php
```

---

### 📝 PROMPT 2: Analytics Page với Charts (4h)

```
VD License Manager - Analytics Page with Charts

TÀI LIỆU THAM KHẢO:
- FLOW_4_Admin_Management.xml (section 5.2.4 - 5.2.11)
- Chart.js documentation

YÊU CẦU:
Tạo trang Analytics với interactive charts và detailed metrics.

FILE: admin/partials/analytics.php

NHIỆM VỤ:

1. ENQUEUE CHART.JS:
```php
// In admin menu class
wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');
wp_enqueue_script('vd-analytics', VD_PLUGIN_URL . 'admin/js/analytics.js', ['jquery', 'chartjs']);
```

2. CHARTS TO BUILD:

CHART 1: Access Frequency (Line Chart)
- Title: "Request Activity Over Time"
- X-axis: Dates (last 30 days)
- Y-axis: Number of requests
- Lines:
  * Total Requests (blue)
  * Success (green)
  * Blocked (red)
  * VPS Blocked (orange)
- Features: Zoom, Pan, Tooltip with breakdown
- SQL:
```sql
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total,
    COUNT(CASE WHEN result='success' THEN 1 END) as success,
    COUNT(CASE WHEN result LIKE 'blocked%' THEN 1 END) as blocked,
    COUNT(CASE WHEN result='blocked_vps' THEN 1 END) as vps_blocked
FROM bz_vd_device_access_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date
```

CHART 2: Result Distribution (Pie/Donut Chart)
- Title: "Request Results Breakdown"
- Segments:
  * Success: 94.2% (green)
  * VPS Blocked: 2.6% (red)
  * Device Limit: 1.4% (orange)
  * Request Limit: 1.0% (yellow)
  * Other: 1.8% (gray)
- Interactive: Click segment → Filter logs by result
- SQL:
```sql
SELECT result, COUNT(*) as count
FROM bz_vd_device_access_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY result
```

CHART 3: VPS Detection (Stacked Bar Chart)
- Title: "VPS vs Residential Breakdown"
- X-axis: Days (last 30 days)
- Y-axis: Request count
- Bars:
  * Residential (green stack)
  * VPS (red stack)
- Show VPS percentage label on each bar
- SQL:
```sql
SELECT 
    DATE(created_at) as date,
    COUNT(CASE WHEN is_vps=0 THEN 1 END) as residential,
    COUNT(CASE WHEN is_vps=1 THEN 1 END) as vps
FROM bz_vd_device_access_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
```

CHART 4: Top VPS Providers (Horizontal Bar Chart)
- Title: "Top VPS Providers Detected"
- Y-axis: Provider names (AWS, DigitalOcean, Google Cloud...)
- X-axis: Number of detections
- Limit: Top 10 providers
- SQL:
```sql
SELECT vps_provider, COUNT(*) as count
FROM bz_vd_device_access_log
WHERE is_vps=1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY vps_provider
ORDER BY count DESC
LIMIT 10
```

CHART 5: Device Status Distribution (Doughnut Chart)
- Title: "Device Status Overview"
- Segments:
  * Active: 70% (green)
  * Removed: 20% (gray)
  * Blocked: 10% (red)
- SQL:
```sql
SELECT status, COUNT(*) as count
FROM bz_vd_license_devices
GROUP BY status
```

CHART 6: Pool Capacity Usage (Horizontal Bar Chart)
- Title: "Pool Capacity Overview"
- Y-axis: Pool names
- X-axis: Percentage used
- Bars: Green (<80%), Yellow (80-95%), Red (>95%)
- Show used/total numbers as labels
- SQL:
```sql
SELECT 
    p.pool_name,
    p.capacity,
    COUNT(lk.id) as used,
    (COUNT(lk.id) / p.capacity * 100) as percentage
FROM bz_vd_product_pools p
LEFT JOIN bz_vd_license_keys lk ON p.id = lk.pool_id
GROUP BY p.id
```

3. CHART.JS CONFIGURATION:
```javascript
// Chart color scheme
const colors = {
    primary: '#2271b1',
    success: '#00a32a',
    danger: '#d63638',
    warning: '#dba617',
    gray: '#8c8f94'
};

// Common chart options
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        },
        tooltip: {
            mode: 'index',
            intersect: false
        }
    }
};
```

4. LAYOUT:
- Page header with Date Range Selector
- Charts in 2-column grid (responsive to 1 column on mobile)
- Each chart in card with title and description
- Loading state while fetching data
- Error handling if no data

5. AJAX DATA LOADING:
```javascript
// Load chart data via AJAX
function loadChartData(chartType, dateRange) {
    $.ajax({
        url: vdAnalytics.ajaxUrl,
        data: {
            action: 'vd_get_chart_data',
            chart_type: chartType,
            date_range: dateRange,
            nonce: vdAnalytics.nonce
        },
        success: function(response) {
            renderChart(chartType, response.data);
        }
    });
}
```

TESTING:
1. Navigate to Analytics → All 6 charts load
2. Change date range → Charts update
3. Hover on chart elements → Tooltips show details
4. Click pie chart segment → Filters applied
5. Resize window → Charts responsive
6. Check browser console → No errors
7. Test with empty data → "No data" message shows

DELIVERABLES:
- admin/partials/analytics.php
- admin/js/analytics.js (Chart.js config)
- admin/css/analytics.css
- includes/class-vd-analytics.php (AJAX handlers)
```

---

### 📝 PROMPT 3: Access Logs Viewer (3h)

```
VD License Manager - Access Logs Viewer

TÀI LIỆU THAM KHẢO:
- FLOW_4_Admin_Management.xml (section 5.1 Access Logs)

YÊU CẦU:
Tạo trang xem logs với filters, search, và export.

FILE: admin/partials/logs.php

NHIỆM VỤ:

1. FILTERS SECTION (Top of page):

Date Range Filter:
- Preset: Today, Yesterday, Last 7 days, Last 30 days, Custom
- Date pickers: From Date, To Date

Result Filter:
- Dropdown: All, Success, Blocked (all), Blocked - VPS, Blocked - Device Limit, Blocked - Request Limit, Error

License Filter:
- Input: License key (autocomplete suggestions)

Device Filter:
- Input: Device name/fingerprint

IP Filter:
- Input: IP address or CIDR

VPS Status Filter:
- Dropdown: All, Residential, VPS Detected

Sort Options:
- Dropdown: Time (newest/oldest), Result, IP, License

Items Per Page:
- Dropdown: 50, 100, 200, 500

2. LOGS TABLE:

Columns:
- Time (with relative time: "2 hours ago")
- License Key (link to license details)
- Customer (email/name)
- Device (icon + name, e.g., 🖥️ Laptop - Windows 11)
- IP Address (with country flag if available)
- VPS Status (badge: "Residential" green, "VPS" red + provider)
- Result (badge with color)
- Error Code (if any)
- Pool → Account (e.g., "Pool 1 → Netflix001")

Row Actions:
- View Full Log Details (modal popup)
- View License Details
- View Device Details

3. PAGINATION:
- Total count: "Showing 1-50 of 12,345 logs"
- Page numbers with First, Previous, Next, Last buttons
- Jump to page input

4. EXPORT SECTION:

Export Button (top right):
- Format options: CSV, Excel (XLSX), JSON
- Export options:
  * Current page (50 records)
  * All filtered results
  * Custom date range
  * All logs (warning if > 10,000 records)

CSV Columns:
Time, License Key, Customer, Device, IP, VPS Status, VPS Provider, Result, Error Code, Pool, Account

5. QUICK STATS CARDS (above table):

Card 1: Total Logs
- Value: 12,345 (all time)

Card 2: Today's Activity
- Value: 234 requests
- Sub: Success 220 (94%), Blocked 14 (6%)

Card 3: VPS Blocked (Last 7 days)
- Value: 45 blocked
- Link: Filter by VPS

Card 4: Most Active License
- Value: A3F9-K2L4-M8N1-P5Q7
- Sub: 150 requests today
- Link: View license

6. SQL QUERY:
```sql
SELECT 
    dal.*,
    lk.license_key,
    lk.customer_email,
    d.device_name,
    d.device_fingerprint,
    p.pool_name,
    pa.account_name,
    pa.username as account_username
FROM bz_vd_device_access_log dal
LEFT JOIN bz_vd_license_keys lk ON dal.license_id = lk.id
LEFT JOIN bz_vd_license_devices d ON dal.device_id = d.id
LEFT JOIN bz_vd_product_pools p ON lk.pool_id = p.id
LEFT JOIN bz_vd_provider_accounts pa ON dal.account_id = pa.id
WHERE 1=1
    [AND dal.created_at BETWEEN ? AND ?]
    [AND dal.result = ?]
    [AND lk.license_key LIKE ?]
    [AND dal.ip_address = ?]
    [AND dal.is_vps = ?]
ORDER BY dal.created_at DESC
LIMIT ? OFFSET ?
```

7. EXPORT PHP:
```php
// Handle CSV export
function export_logs_csv($filters) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="vd-access-logs-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['Time', 'License Key', 'Customer', 'Device', 'IP', 'VPS Status', 'VPS Provider', 'Result', 'Error Code', 'Pool', 'Account']);
    
    // Data
    $logs = get_logs($filters);
    foreach ($logs as $log) {
        fputcsv($output, [
            $log->created_at,
            $log->license_key,
            $log->customer_email,
            $log->device_name,
            $log->ip_address,
            $log->is_vps ? 'VPS' : 'Residential',
            $log->vps_provider,
            $log->result,
            $log->error_code,
            $log->pool_name,
            $log->account_name
        ]);
    }
    
    fclose($output);
    exit;
}
```

8. LOG DETAILS MODAL:
- Popup when "View Full Log Details" clicked
- Show all fields including:
  * Full device fingerprint
  * User agent
  * Request/Response details (if stored)
  * Geographic location
  * Timeline of related events

TESTING:
1. Navigate to Logs → Table loads with default filter (last 7 days)
2. Apply filters → Table updates via AJAX
3. Click pagination → Loads next page
4. Export CSV → File downloads with correct data
5. Click "View Full Log Details" → Modal shows details
6. Search license key → Results filtered
7. Sort by different columns → Order changes
8. Test with 10,000+ logs → Pagination works, export warns

DELIVERABLES:
- admin/partials/logs.php
- admin/js/logs.js (AJAX filtering, export)
- admin/css/logs.css
- includes/class-vd-logs.php (Query builders, export handlers)
```

---

## 📅 NGÀY 16: REPORTS & FINAL POLISH

### 📝 PROMPT 4: Reports Generator (4h)

```
VD License Manager - Reports Generator

TÀI LIỆU THAM KHẢO:
- FLOW_4_Admin_Management.xml (section 5.3 Reports)

YÊU CẦU:
Tạo hệ thống generate và export reports.

FILE: admin/partials/reports.php

NHIỆM VỤ:

1. REPORT TYPES:

Report 1: License Usage Report
- Data: All licenses, request count, success rate, devices, pool assignment
- Group by: Product, Status, Date range
- Charts: Usage trends, top licenses, success rate distribution

Report 2: Device Activity Report
- Data: All devices, registrations, last accessed, status changes
- Group by: License, Device type (browser/OS), Status
- Charts: Device types pie chart, registrations over time

Report 3: VPS Detection Report
- Data: All VPS attempts, blocked rate, providers, IPs, countries
- Group by: Provider, Country, Date
- Charts: VPS providers pie chart, detections over time, geographic map

Report 4: Pool Performance Report
- Data: Pools, capacity, assigned licenses, accounts, success rate
- Highlight: Underutilized pools (<50%), Overloaded pools (>95%)
- Charts: Capacity usage bars, success rate comparison

Report 5: Account Credentials Report
- Data: Accounts, expires_at, last update, next update due
- Highlight: Expired accounts, expiring soon (<7 days)
- Charts: Expiration timeline, account usage

Report 6: Error & Blocked Report
- Data: All blocked/error attempts, reasons, frequency
- Group by: Error type, License, Device
- Charts: Error distribution pie chart, blocked attempts over time

2. REPORT BUILDER INTERFACE:

Form Fields:
```html
<select name="report_type">
    <option value="license_usage">License Usage Report</option>
    <option value="device_activity">Device Activity Report</option>
    <option value="vps_detection">VPS Detection Report</option>
    <option value="pool_performance">Pool Performance Report</option>
    <option value="account_credentials">Account Credentials Report</option>
    <option value="error_blocked">Error & Blocked Report</option>
</select>

<input type="date" name="date_from">
<input type="date" name="date_to">

<div id="report-specific-filters">
    <!-- Dynamically loaded based on report type -->
</div>

<select name="group_by">
    <!-- Options based on report type -->
</select>

<select name="sort_by">
    <!-- Options based on report type -->
</select>

<input type="checkbox" name="include_charts"> Include Charts

<button>Generate Report</button>
```

3. REPORT OUTPUT:

Preview Section:
- Show report on screen first
- Summary stats at top
- Tables with data
- Charts (if include_charts checked)
- Export buttons: PDF, Excel, CSV, HTML

4. PDF EXPORT:
```php
// Using TCPDF or mPDF library
require_once('vendor/autoload.php');

function generate_pdf_report($report_data, $report_type) {
    $pdf = new \TCPDF();
    $pdf->SetTitle('VD License Manager - ' . $report_type);
    
    // Add logo
    $pdf->Image(VD_PLUGIN_DIR . 'assets/logo.png', 15, 10, 30);
    
    // Add title
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 15, ucwords(str_replace('_', ' ', $report_type)), 0, 1, 'C');
    
    // Add date range
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Date Range: ' . $report_data['date_from'] . ' to ' . $report_data['date_to'], 0, 1, 'C');
    
    // Add summary table
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Summary', 0, 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $html = '<table border="1" cellpadding="5">';
    foreach ($report_data['summary'] as $key => $value) {
        $html .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html);
    
    // Add charts (as images)
    if ($report_data['include_charts']) {
        foreach ($report_data['charts'] as $chart) {
            $pdf->AddPage();
            $pdf->Image($chart['image_path'], 15, 40, 180);
        }
    }
    
    // Add detailed data table
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Detailed Data', 0, 1);
    
    $html = build_data_table_html($report_data['data']);
    $pdf->writeHTML($html);
    
    // Output
    $pdf->Output('VD_Report_' . $report_type . '_' . date('Y-m-d') . '.pdf', 'D');
}
```

5. EXCEL EXPORT:
```php
// Using PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generate_excel_report($report_data, $report_type) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Title
    $sheet->setCellValue('A1', ucwords(str_replace('_', ' ', $report_type)));
    $sheet->getStyle('A1')->getFont()->setSize(20)->setBold(true);
    
    // Summary
    $row = 3;
    $sheet->setCellValue('A' . $row, 'Summary');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;
    
    foreach ($report_data['summary'] as $key => $value) {
        $sheet->setCellValue('A' . $row, $key);
        $sheet->setCellValue('B' . $row, $value);
        $row++;
    }
    
    // Data table
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Detailed Data');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;
    
    // Headers
    $col = 'A';
    foreach ($report_data['columns'] as $column) {
        $sheet->setCellValue($col . $row, $column);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $col++;
    }
    $row++;
    
    // Data rows
    foreach ($report_data['data'] as $data_row) {
        $col = 'A';
        foreach ($data_row as $cell_value) {
            $sheet->setCellValue($col . $row, $cell_value);
            $col++;
        }
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="VD_Report_' . $report_type . '_' . date('Y-m-d') . '.xlsx"');
    $writer->save('php://output');
    exit;
}
```

6. SCHEDULED REPORTS (Optional):

Settings Section:
- Enable scheduled reports
- Schedule: Daily, Weekly, Monthly
- Report type to auto-generate
- Email to send reports to
- Save settings

Cron Job:
```php
// Register cron event
add_action('vd_scheduled_report', 'vd_send_scheduled_report');

function vd_send_scheduled_report() {
    $settings = get_option('vd_scheduled_reports');
    
    if (!$settings['enabled']) {
        return;
    }
    
    foreach ($settings['reports'] as $report_config) {
        // Generate report
        $report_data = generate_report_data($report_config);
        
        // Create PDF
        $pdf_path = generate_pdf_report($report_data, $report_config['type']);
        
        // Email
        $to = $report_config['email'];
        $subject = 'VD License Manager - Scheduled Report: ' . ucwords(str_replace('_', ' ', $report_config['type']));
        $message = 'Please find attached your scheduled report.';
        $attachments = [$pdf_path];
        
        wp_mail($to, $subject, $message, [], $attachments);
        
        // Cleanup
        unlink($pdf_path);
    }
}
```

TESTING:
1. Navigate to Reports → Form loads
2. Select "License Usage Report" → Specific filters appear
3. Generate report → Preview shows
4. Export PDF → File downloads, opens correctly
5. Export Excel → File downloads, data formatted
6. Export CSV → File downloads
7. Test with empty data → "No data" message
8. Test all 6 report types → All generate correctly
9. Enable scheduled reports → Cron job executes, email sent

DELIVERABLES:
- admin/partials/reports.php
- admin/js/reports.js
- admin/css/reports.css
- includes/class-vd-reports.php (Report generators, exporters)
- composer.json (dependencies: tcpdf, phpspreadsheet)
```

---

### 📝 PROMPT 5: Final Testing & Optimization (4h)

```
VD License Manager - Final Testing & Performance Optimization

YÊU CẦU:
Kiểm tra toàn bộ plugin, optimize performance, và documentation.

NHIỆM VỤ:

1. COMPREHENSIVE TESTING:

Test Suite 1: Dashboard
- [ ] All 6 KPI cards display correctly
- [ ] Date range selector works
- [ ] Stats calculate accurately
- [ ] Recent tables show correct data
- [ ] Links to other pages work
- [ ] Page loads < 2 seconds

Test Suite 2: Analytics
- [ ] All 6 charts render correctly
- [ ] Chart data matches SQL queries
- [ ] Interactive features work (click, hover, zoom)
- [ ] Date range updates charts
- [ ] No JavaScript errors
- [ ] Responsive on mobile

Test Suite 3: Logs
- [ ] Logs table displays all fields
- [ ] Filters work (date, result, license, IP)
- [ ] Pagination works
- [ ] Search works
- [ ] Export CSV/Excel works
- [ ] 10,000+ logs paginate smoothly

Test Suite 4: Reports
- [ ] All 6 report types generate
- [ ] PDF export works, formatting correct
- [ ] Excel export works, data formatted
- [ ] Charts embed in reports correctly
- [ ] Scheduled reports email correctly

2. PERFORMANCE OPTIMIZATION:

Database Indexing:
```sql
-- Add indexes if not exist
CREATE INDEX idx_dal_created_at ON bz_vd_device_access_log(created_at);
CREATE INDEX idx_dal_result ON bz_vd_device_access_log(result);
CREATE INDEX idx_dal_license_id ON bz_vd_device_access_log(license_id);
CREATE INDEX idx_dal_ip ON bz_vd_device_access_log(ip_address);
CREATE INDEX idx_lk_status ON bz_vd_license_keys(status);
CREATE INDEX idx_ld_status ON bz_vd_license_devices(status);
```

Query Optimization:
- Use prepared statements for all queries
- Limit results with OFFSET/LIMIT
- Use COUNT(*) with subqueries for pagination
- Cache expensive queries (Transient API):
```php
$cache_key = 'vd_dashboard_stats_' . $date_range;
$stats = get_transient($cache_key);

if (false === $stats) {
    $stats = calculate_dashboard_stats($date_range);
    set_transient($cache_key, $stats, HOUR_IN_SECONDS);
}
```

AJAX Loading:
- Load charts asynchronously
- Show loading spinners
- Handle errors gracefully

Asset Optimization:
- Minify CSS/JS files
- Use CDN for Chart.js
- Lazy load charts (IntersectionObserver)
- Only enqueue scripts on relevant admin pages

3. CODE CLEANUP:

Remove Debug Code:
- Remove all var_dump(), print_r(), console.log()
- Remove commented out code blocks
- Remove unused functions

Add Error Handling:
```php
try {
    // Database query
} catch (Exception $e) {
    error_log('VD Plugin Error: ' . $e->getMessage());
    wp_send_json_error(['message' => 'Database error occurred']);
}
```

Security Audit:
- Verify nonce checks on all AJAX handlers
- Sanitize all inputs: sanitize_text_field(), absint()
- Escape all outputs: esc_html(), esc_attr(), esc_url()
- Check capability: current_user_can('manage_options')
- Prepared statements for all SQL queries

4. DOCUMENTATION:

Create README.md:
```markdown
# VD License Manager

WordPress plugin for managing license keys with WooCommerce + LMfWC integration.

## Features
- Dashboard with KPI metrics
- Analytics with interactive charts
- Access logs with VPS detection
- Device management
- Email notifications
- Comprehensive reports

## Installation
1. Upload to /wp-content/plugins/
2. Activate plugin
3. Configure settings
4. Set up pools and accounts

## Requirements
- WordPress 6.0+
- PHP 8.1+
- MySQL 8.0+
- WooCommerce 8.0+
- License Manager for WooCommerce 3.0+

## Usage
See docs/ folder for detailed documentation.

## Support
support@vidieu.vn
```

Create CHANGELOG.md:
```markdown
# Changelog

## [1.0.0] - 2025-10-16
### Added
- Initial release
- Dashboard with 6 KPI cards
- Analytics with 6 interactive charts
- Access logs viewer with filters
- 6 report types with PDF/Excel export
- VPS detection and blocking
- Device fingerprinting
- Email notifications
- Portal frontend for customers

## [1.0.1] - TBD
### Planned
- Scheduled reports
- Real-time monitoring
- Advanced analytics
- API endpoints
```

Create docs/ADMIN_GUIDE.md:
- How to create pools
- How to add accounts
- How to manage licenses
- How to use analytics
- How to generate reports

Create docs/API_REFERENCE.md:
- REST API endpoints
- Request/Response examples
- Error codes
- Authentication

5. FINAL CHECKLIST:

Code Quality:
- [ ] All functions have docblocks
- [ ] Code follows WordPress Coding Standards
- [ ] No PHP notices/warnings
- [ ] No JavaScript console errors
- [ ] All strings translatable (i18n)

Functionality:
- [ ] All features from roadmap implemented
- [ ] All workflows (FLOW 1-4) work end-to-end
- [ ] Email sending works
- [ ] VPS detection works
- [ ] Device tracking works
- [ ] Reports export correctly

Performance:
- [ ] Dashboard loads < 2s
- [ ] Analytics page loads < 3s
- [ ] Logs page with 10,000 records loads < 3s
- [ ] No N+1 query problems
- [ ] Database queries optimized

Security:
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Nonce verification on AJAX
- [ ] Capability checks on admin pages
- [ ] SQL injection prevention
- [ ] XSS prevention

Documentation:
- [ ] README.md complete
- [ ] CHANGELOG.md up to date
- [ ] Admin guide written
- [ ] Code comments added
- [ ] API documented

DELIVERABLES:
- README.md
- CHANGELOG.md
- docs/ADMIN_GUIDE.md
- docs/API_REFERENCE.md
- Performance optimization report
- Final test results
```

---

## 📊 TỔNG KẾT NGÀY 15-16

**Đã hoàn thành:**
- ✅ Dashboard với 6 KPI cards
- ✅ Analytics với 6 interactive charts (Chart.js)
- ✅ Access Logs viewer với filters & export
- ✅ Reports generator với 6 report types
- ✅ PDF/Excel export
- ✅ Performance optimization
- ✅ Documentation

**Plugin hoàn chỉnh 100%:**
- ✅ Database schema (6 tables)
- ✅ Admin UI (7 pages)
- ✅ LMfWC integration
- ✅ REST API
- ✅ Portal frontend
- ✅ Device tracking
- ✅ VPS detection
- ✅ Email system
- ✅ Dashboard & Analytics
- ✅ Reports
- ✅ Documentation

**Sẵn sàng:**
- 🚀 Deploy to production
- 📦 Submit to WordPress.org (optional)
- 📧 Send to client for testing
- 🎉 Launch!

---

Bạn muốn tôi điều chỉnh gì trong các prompts này không? Hoặc có thêm tính năng nào cần bổ sung cho Dashboard/Analytics không?

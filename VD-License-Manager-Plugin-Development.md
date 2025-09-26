# VD License Manager - Plugin Development Guide

## 1. Plugin Architecture

### 1.1 Core Plugin Structure
```
vd-license-manager/
├── vd-license-manager.php              # Main plugin file
├── includes/
│   ├── class-vd-license-manager.php    # Main plugin class
│   ├── class-vd-database.php           # Database operations
│   ├── class-vd-api.php                # REST API endpoints
│   ├── class-vd-license-resolver.php   # License resolution logic
│   ├── class-vd-admin.php              # Admin interface
│   ├── class-vd-security.php           # Security & encryption
│   └── class-vd-woocommerce.php        # WooCommerce integration
├── admin/
│   ├── css/
│   ├── js/
│   └── partials/                       # Admin page templates
├── public/
│   ├── css/
│   └── js/
└── languages/                          # Internationalization
```

### 1.2 Main Plugin File (vd-license-manager.php)
```php
<?php
/**
 * Plugin Name: VD License Manager
 * Description: Advanced license management system for digital products
 * Version: 1.0.0
 * Author: Vidieu.vn
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VD_LICENSE_MANAGER_VERSION', '1.0.0');
define('VD_LICENSE_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VD_LICENSE_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, ['VD_License_Manager', 'activate']);
register_deactivation_hook(__FILE__, ['VD_License_Manager', 'deactivate']);

require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-license-manager.php';

function run_vd_license_manager() {
    $plugin = new VD_License_Manager();
    $plugin->run();
}

run_vd_license_manager();
```

## 2. Core PHP Classes

### 2.1 Main Plugin Class (class-vd-license-manager.php)
```php
<?php
class VD_License_Manager {

    private $loader;
    private $plugin_name;
    private $version;

    public function __construct() {
        $this->version = VD_LICENSE_MANAGER_VERSION;
        $this->plugin_name = 'vd-license-manager';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
    }

    private function load_dependencies() {
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-database.php';
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-api.php';
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-license-resolver.php';
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-admin.php';
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-security.php';
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-woocommerce.php';

        // New classes for enhanced functionality
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-settings-manager.php';
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-provider-manager.php';
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-analytics.php';
        require_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-vd-assignment-manager.php';
    }

    public static function activate() {
        $database = new VD_Database();
        $database->create_tables();
    }

    public function run() {
        // Initialize all components
    }
}
```

### 2.2 Database Class (class-vd-database.php)
```php
<?php
class VD_Database {

    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function create_tables() {
        $this->create_global_settings_table();              // 1. Global settings
        $this->create_provider_accounts_table();             // 2. Provider accounts
        $this->create_content_versions_table();              // 3. Content versions
        $this->create_licenses_table();                      // 4. Licenses
        $this->create_product_settings_table();              // 5. Product settings
        $this->create_license_settings_override_table();     // 6. License overrides
        $this->create_license_assignments_table();           // 7. License assignments
        $this->create_license_assignment_history_table();    // 8. Assignment history
        $this->create_product_provider_mapping_table();      // 9. Product mapping
        $this->create_device_records_table();                // 10. Device records
        $this->create_access_logs_table();                   // 11. Access logs
        $this->create_rate_limits_table();                   // 12. Rate limits
        $this->create_audit_logs_table();                    // 13. Audit logs
    }

    private function create_licenses_table() {
        $table_name = $this->wpdb->prefix . 'vd_licenses';

        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_key VARCHAR(64) NOT NULL UNIQUE,
            product_id BIGINT UNSIGNED NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            status ENUM('active','expired','suspended') NOT NULL DEFAULT 'active',
            max_devices INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NULL,
            last_used_at DATETIME NULL,
            INDEX idx_license_key (license_key),
            INDEX idx_product_id (product_id),
            INDEX idx_customer_email (customer_email),
            INDEX idx_status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Additional table creation methods...
}
```

### 2.3 License Resolver Class (class-vd-license-resolver.php)
```php
<?php
class VD_License_Resolver {

    private $wpdb;
    private $security;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->security = new VD_Security();
    }

    public function resolve_license_info($license_key, $device_fp, $device_info = []) {
        try {
            // BƯỚC 1: Kiểm tra license hợp lệ
            $license = $this->validate_license($license_key);
            if (!$license) {
                return $this->error_response('Invalid or expired license key');
            }

            // BƯỚC 2: Kiểm tra rate limiting
            if (!$this->check_rate_limit($license_key, $device_fp)) {
                return $this->error_response('Rate limit exceeded');
            }

            // BƯỚC 3: Validate device
            $device_validation = $this->validate_device($license, $device_fp, $device_info);
            if (!$device_validation['valid']) {
                return $this->error_response($device_validation['message']);
            }

            // BƯỚC 4: Phân phối provider account theo sản phẩm
            $assignment = $this->assign_provider_account($license['product_id'], $license_key, $device_fp);
            if (!$assignment) {
                return $this->error_response('No available provider accounts');
            }

            // Log successful resolution
            $this->log_usage($license_key, $device_fp, 'success');

            return [
                'success' => true,
                'data' => [
                    'account_info' => $assignment['account_info'],
                    'content_version' => $assignment['content_version'],
                    'expires_at' => $license['expires_at']
                ]
            ];

        } catch (Exception $e) {
            $this->log_usage($license_key, $device_fp, 'error', $e->getMessage());
            return $this->error_response('Internal error occurred');
        }
    }

    private function validate_license($license_key) {
        $sql = "SELECT * FROM {$this->wpdb->prefix}vd_licenses
                WHERE license_key = %s AND status = 'active'";
        $license = $this->wpdb->get_row($this->wpdb->prepare($sql, $license_key), ARRAY_A);

        if (!$license) return false;

        // Check expiration
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            // Update status to expired
            $this->wpdb->update(
                $this->wpdb->prefix . 'vd_licenses',
                ['status' => 'expired'],
                ['id' => $license['id']]
            );
            return false;
        }

        return $license;
    }

    private function calculate_risk_score($license, $device_fp, $device_info) {
        $score = 0;

        // Check device history
        $device_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT device_fp) FROM {$this->wpdb->prefix}vd_device_records
             WHERE license_key = %s",
            $license['license_key']
        ));

        if ($device_count > ($license['max_devices'] ?? 5)) {
            $score += 40;
        }

        // Check IP reputation (simplified)
        if (isset($device_info['country']) && $device_info['country'] !== 'VN') {
            $score += 20;
        }

        // Check user agent patterns
        if (isset($device_info['user_agent']) &&
            (strpos($device_info['user_agent'], 'bot') !== false ||
             strpos($device_info['user_agent'], 'crawler') !== false)) {
            $score += 30;
        }

        return min($score, 100);
    }

    // Additional methods for rate limiting, device validation, assignment...
}
```

### 2.4 Admin Interface Class (class-vd-admin.php)
```php
<?php
class VD_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            VD_LICENSE_MANAGER_PLUGIN_URL . 'admin/css/admin.css',
            [],
            $this->version,
            'all'
        );
    }

    public function add_admin_menu() {
        add_menu_page(
            'VD License Manager',
            'License Manager',
            'manage_options',
            'vd-license-manager',
            [$this, 'display_dashboard'],
            'dashicons-key',
            30
        );

        add_submenu_page(
            'vd-license-manager',
            'Licenses',
            'Licenses',
            'manage_options',
            'vd-license-manager-licenses',
            [$this, 'display_licenses']
        );

        add_submenu_page(
            'vd-license-manager',
            'Provider Accounts',
            'Provider Accounts',
            'manage_options',
            'vd-license-manager-providers',
            [$this, 'display_providers']
        );
    }

    public function display_dashboard() {
        include_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    public function display_licenses() {
        include_once VD_LICENSE_MANAGER_PLUGIN_DIR . 'admin/partials/licenses.php';
    }
}
```

## 3. WordPress Integration

### 3.1 Hooks and Filters
```php
// Custom hooks for extensibility
do_action('vd_license_before_resolution', $license_key, $device_fp);
do_action('vd_license_after_resolution', $result, $license_key);

// Filters for customization
$assignment_strategy = apply_filters('vd_license_assignment_strategy', 'least_loaded', $product_id);
$risk_threshold = apply_filters('vd_license_risk_threshold', 70);
```

### 3.2 Custom Post Types (if needed)
```php
public function register_post_types() {
    register_post_type('vd_license_log', [
        'labels' => [
            'name' => 'License Logs',
            'singular_name' => 'License Log'
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_admin_bar' => false,
        'capability_type' => 'post',
        'supports' => ['title', 'editor']
    ]);
}
```

### 3.3 Capabilities and Permissions
```php
public function add_capabilities() {
    $role = get_role('administrator');
    $role->add_cap('manage_vd_licenses');
    $role->add_cap('view_vd_reports');

    // Create custom role
    add_role('vd_license_manager', 'License Manager', [
        'read' => true,
        'manage_vd_licenses' => true,
        'view_vd_reports' => true
    ]);
}
```

## 4. Security Implementation

### 4.1 Encryption Class (class-vd-security.php)
```php
<?php
class VD_Security {

    private $encryption_key;

    public function __construct() {
        $this->encryption_key = $this->get_encryption_key();
    }

    public function encrypt_account_info($account_data) {
        $json_data = json_encode($account_data);
        $iv = random_bytes(16);

        $encrypted = openssl_encrypt(
            $json_data,
            'AES-256-GCM',
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt_account_info($encrypted_data) {
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);

        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-GCM',
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return json_decode($decrypted, true);
    }

    public function generate_device_fingerprint($device_info) {
        $fingerprint_data = [
            $device_info['ip'] ?? '',
            $device_info['user_agent'] ?? '',
            $device_info['screen_resolution'] ?? '',
            $device_info['timezone'] ?? '',
            $device_info['language'] ?? ''
        ];

        return hash('sha256', implode('|', $fingerprint_data));
    }

    private function get_encryption_key() {
        $key = get_option('vd_license_encryption_key');
        if (!$key) {
            $key = base64_encode(random_bytes(32));
            update_option('vd_license_encryption_key', $key);
        }
        return base64_decode($key);
    }
}
```

## 5. WooCommerce Integration

### 5.1 Integration Class (class-vd-woocommerce.php)
```php
<?php
class VD_WooCommerce {

    public function __construct() {
        add_action('woocommerce_order_status_completed', [$this, 'generate_license_on_completion']);
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_license_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_license_fields']);
    }

    public function generate_license_on_completion($order_id) {
        $order = wc_get_order($order_id);

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $requires_license = get_post_meta($product->get_id(), '_vd_requires_license', true);

            if ($requires_license === 'yes') {
                $this->create_license_for_product($order, $product, $item);
            }
        }
    }

    private function create_license_for_product($order, $product, $item) {
        global $wpdb;

        $license_key = $this->generate_license_key();
        $max_devices = get_post_meta($product->get_id(), '_vd_max_devices', true) ?: 5;
        $expires_days = get_post_meta($product->get_id(), '_vd_license_duration', true) ?: 365;

        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_days} days"));

        $wpdb->insert(
            $wpdb->prefix . 'vd_licenses',
            [
                'license_key' => $license_key,
                'product_id' => $product->get_id(),
                'customer_email' => $order->get_billing_email(),
                'max_devices' => $max_devices,
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql')
            ]
        );

        // Send license email to customer
        $this->send_license_email($order, $license_key, $product);
    }

    private function generate_license_key() {
        return 'VD-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 4)) . '-' .
               strtoupper(substr(md5(uniqid(rand(), true)), 0, 4)) . '-' .
               strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    }
}
```

## 6. Error Handling and Logging

### 6.1 Error Handler
```php
class VD_Error_Handler {

    public static function log_error($message, $context = []) {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log(sprintf(
                '[VD License Manager] %s - Context: %s',
                $message,
                json_encode($context)
            ));
        }

        // Store in database for admin review
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vd_audit_logs',
            [
                'action' => 'error',
                'details' => json_encode(['message' => $message, 'context' => $context]),
                'created_at' => current_time('mysql')
            ]
        );
    }

    public static function handle_exception($exception) {
        self::log_error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        return [
            'success' => false,
            'message' => 'An internal error occurred. Please try again later.'
        ];
    }
}
```

## 7. Performance Optimizations

### 7.1 Caching Strategy
```php
class VD_Cache {

    private static $cache_group = 'vd_license_manager';

    public static function get($key) {
        return wp_cache_get($key, self::$cache_group);
    }

    public static function set($key, $value, $expiration = 3600) {
        return wp_cache_set($key, $value, self::$cache_group, $expiration);
    }

    public static function delete($key) {
        return wp_cache_delete($key, self::$cache_group);
    }

    public static function get_license_data($license_key) {
        $cache_key = 'license_' . md5($license_key);
        $cached = self::get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_licenses WHERE license_key = %s",
            $license_key
        ), ARRAY_A);

        if ($license) {
            self::set($cache_key, $license, 1800); // 30 minutes
        }

        return $license;
    }
}
```

## 8. Development Standards

### 8.1 Coding Standards
- Follow WordPress Coding Standards
- Use proper sanitization and validation
- Implement proper nonce verification for forms
- Use prepared statements for database queries
- Follow object-oriented programming principles

### 8.2 Code Documentation
```php
/**
 * Resolves license information for a given license key and device
 *
 * @since 1.0.0
 * @param string $license_key The license key to resolve
 * @param string $device_fp Device fingerprint
 * @param array $device_info Additional device information
 * @return array Resolution result with success status and data
 */
public function resolve_license_info($license_key, $device_fp, $device_info = []) {
    // Implementation
}
```

### 8.3 Unit Testing Structure
```php
class Test_VD_License_Resolver extends WP_UnitTestCase {

    private $resolver;

    public function setUp() {
        parent::setUp();
        $this->resolver = new VD_License_Resolver();
    }

    public function test_valid_license_resolution() {
        // Test implementation
    }

    public function test_expired_license_rejection() {
        // Test implementation
    }
}
```

## 9. Plugin Activation and Deactivation

### 9.1 Activation Process
```php
public static function activate() {
    // Create database tables
    $database = new VD_Database();
    $database->create_tables();

    // Set default options
    add_option('vd_license_rate_limit', 100);
    add_option('vd_license_risk_threshold', 70);

    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $vd_dir = $upload_dir['basedir'] . '/vd-license-manager';
    if (!file_exists($vd_dir)) {
        wp_mkdir_p($vd_dir);
    }

    // Schedule cleanup cron job
    wp_schedule_event(time(), 'daily', 'vd_license_cleanup');
}
```

### 9.2 Deactivation Process
```php
public static function deactivate() {
    // Remove scheduled events
    wp_clear_scheduled_hook('vd_license_cleanup');

    // Clear cache
    wp_cache_flush();
}
```

## 10. New Classes for Enhanced Functionality

### 10.1 Settings Manager Class (class-vd-settings-manager.php)
```php
<?php
class VD_Settings_Manager {

    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get effective settings for a license (inheritance chain)
     */
    public function get_license_settings($license_id, $product_id) {
        // License override settings
        $license_override = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}vd_license_settings_override WHERE license_id = %d",
            $license_id
        ), ARRAY_A);

        // Product settings
        $product_settings = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}vd_product_settings WHERE product_id = %d",
            $product_id
        ), ARRAY_A);

        // Global settings
        $global_settings = $this->get_global_settings();

        // Merge with priority: License > Product > Global
        return [
            'max_devices' => $license_override['max_devices']
                ?? $product_settings['max_devices']
                ?? $global_settings['default_max_devices']
                ?? 3,

            'rate_limit_requests' => $license_override['rate_limit_requests']
                ?? $product_settings['rate_limit_requests']
                ?? $global_settings['default_rate_limit_requests']
                ?? 100,

            'rate_limit_window_hours' => $license_override['rate_limit_window_hours']
                ?? $product_settings['rate_limit_window_hours']
                ?? $global_settings['default_rate_limit_window_hours']
                ?? 1,

            'auto_approval_enabled' => $license_override['auto_approval_enabled']
                ?? $product_settings['auto_approval_enabled']
                ?? ($global_settings['auto_approval_enabled'] === 'true')
                ?? true,

            'grace_period_hours' => $license_override['grace_period_hours']
                ?? $product_settings['grace_period_hours']
                ?? $global_settings['grace_period_hours']
                ?? 72
        ];
    }

    /**
     * Create or update license settings override
     */
    public function create_license_override($license_id, $settings, $notes = '') {
        $data = array_merge($settings, [
            'license_id' => $license_id,
            'notes' => $notes,
            'updated_at' => current_time('mysql')
        ]);

        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}vd_license_settings_override WHERE license_id = %d",
            $license_id
        ));

        if ($existing) {
            $result = $this->wpdb->update(
                "{$this->wpdb->prefix}vd_license_settings_override",
                $data,
                ['license_id' => $license_id]
            );
            return $result !== false ? $existing->id : false;
        } else {
            $data['created_at'] = current_time('mysql');
            return $this->wpdb->insert(
                "{$this->wpdb->prefix}vd_license_settings_override",
                $data
            ) ? $this->wpdb->insert_id : false;
        }
    }

    /**
     * Remove license settings override
     */
    public function remove_license_override($license_id) {
        return $this->wpdb->delete(
            "{$this->wpdb->prefix}vd_license_settings_override",
            ['license_id' => $license_id]
        );
    }

    /**
     * Get global settings as key-value array
     */
    private function get_global_settings() {
        $settings = $this->wpdb->get_results(
            "SELECT setting_key, setting_value FROM {$this->wpdb->prefix}vd_global_settings",
            ARRAY_A
        );

        $global_config = [];
        foreach ($settings as $setting) {
            $global_config[$setting['setting_key']] = $setting['setting_value'];
        }

        return $global_config;
    }

    /**
     * Update global settings
     */
    public function update_global_settings($new_settings) {
        foreach ($new_settings as $key => $value) {
            $this->wpdb->replace(
                "{$this->wpdb->prefix}vd_global_settings",
                [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'updated_at' => current_time('mysql')
                ]
            );
        }

        return true;
    }
}
```

### 10.2 Assignment Manager Class (class-vd-assignment-manager.php)
```php
<?php
class VD_Assignment_Manager {

    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Manually assign provider account to license
     */
    public function manual_assign($license_id, $provider_account_id, $admin_user_id, $reason = '') {
        try {
            $this->wpdb->query('START TRANSACTION');

            // Get current assignment
            $current_assignment = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}vd_license_assignments WHERE license_id = %d",
                $license_id
            ), ARRAY_A);

            // Get provider account info
            $provider_account = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}vd_provider_accounts WHERE id = %d",
                $provider_account_id
            ), ARRAY_A);

            if (!$provider_account || $provider_account['status'] !== 'active') {
                throw new Exception("Provider account không khả dụng");
            }

            // Log assignment history
            if ($current_assignment) {
                $this->log_assignment_change(
                    $license_id,
                    $current_assignment['provider_account_id'],
                    $provider_account_id,
                    $admin_user_id,
                    'manual',
                    $reason
                );

                // Update current assignment
                $this->wpdb->update(
                    "{$this->wpdb->prefix}vd_license_assignments",
                    [
                        'provider_account_id' => $provider_account_id,
                        'assigned_by' => $admin_user_id,
                        'assignment_method' => 'manual',
                        'change_reason' => $reason,
                        'assigned_at' => current_time('mysql')
                    ],
                    ['license_id' => $license_id]
                );
            } else {
                // Create new assignment
                $this->wpdb->insert(
                    "{$this->wpdb->prefix}vd_license_assignments",
                    [
                        'license_id' => $license_id,
                        'provider_account_id' => $provider_account_id,
                        'assigned_by' => $admin_user_id,
                        'assignment_method' => 'manual',
                        'change_reason' => $reason,
                        'status' => 'active'
                    ]
                );

                $this->log_assignment_change(
                    $license_id,
                    null,
                    $provider_account_id,
                    $admin_user_id,
                    'manual',
                    $reason ?: 'Initial manual assignment'
                );
            }

            $this->wpdb->query('COMMIT');

            return [
                'success' => true,
                'message' => "License assigned to {$provider_account['account_name']}",
                'assignment_id' => $current_assignment['id'] ?? $this->wpdb->insert_id
            ];

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return [
                'success' => false,
                'message' => 'Assignment failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get assignment history for a license
     */
    public function get_assignment_history($license_id) {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT
                ah.*,
                u.user_login as admin_username,
                old_pa.account_name as old_account_name,
                new_pa.account_name as new_account_name
            FROM {$this->wpdb->prefix}vd_license_assignment_history ah
            LEFT JOIN {$this->wpdb->users} u ON ah.changed_by = u.ID
            LEFT JOIN {$this->wpdb->prefix}vd_provider_accounts old_pa ON ah.old_provider_account_id = old_pa.id
            LEFT JOIN {$this->wpdb->prefix}vd_provider_accounts new_pa ON ah.new_provider_account_id = new_pa.id
            WHERE ah.license_id = %d
            ORDER BY ah.created_at DESC
        ", $license_id), ARRAY_A);
    }

    /**
     * Log assignment change in history table
     */
    private function log_assignment_change($license_id, $old_provider_id, $new_provider_id, $admin_user_id, $method, $reason) {
        // Get account names for backup
        $old_account_name = null;
        $new_account_name = null;

        if ($old_provider_id) {
            $old_account = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT account_name FROM {$this->wpdb->prefix}vd_provider_accounts WHERE id = %d",
                $old_provider_id
            ));
            $old_account_name = $old_account->account_name ?? 'Unknown';
        }

        $new_account = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT account_name FROM {$this->wpdb->prefix}vd_provider_accounts WHERE id = %d",
            $new_provider_id
        ));
        $new_account_name = $new_account->account_name ?? 'Unknown';

        $this->wpdb->insert(
            "{$this->wpdb->prefix}vd_license_assignment_history",
            [
                'license_id' => $license_id,
                'old_provider_account_id' => $old_provider_id,
                'new_provider_account_id' => $new_provider_id,
                'changed_by' => $admin_user_id,
                'change_method' => $method,
                'change_reason' => $reason,
                'old_account_name' => $old_account_name,
                'new_account_name' => $new_account_name,
                'created_at' => current_time('mysql')
            ]
        );
    }
}
```

### 10.3 Provider Manager Class (class-vd-provider-manager.php)
```php
<?php
class VD_Provider_Manager {

    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Update provider credentials (plain text storage)
     */
    public function update_credentials($provider_account_id, $credentials_data) {
        try {
            $this->wpdb->query('START TRANSACTION');

            // Get current version number
            $current_version = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT MAX(version_number) FROM {$this->wpdb->prefix}vd_content_versions
                 WHERE provider_account_id = %d",
                $provider_account_id
            ));

            $new_version = ($current_version ?? 0) + 1;

            // Prepare content data
            $content_data = wp_json_encode($credentials_data);
            $content_hash = hash('sha256', $content_data);

            // Insert new version
            $result = $this->wpdb->insert(
                "{$this->wpdb->prefix}vd_content_versions",
                [
                    'provider_account_id' => $provider_account_id,
                    'version_number' => $new_version,
                    'content_type' => $credentials_data['content_type'] ?? 'credentials_2fa',
                    'content_data' => $content_data,
                    'email' => $credentials_data['email'] ?? null,
                    'password' => $credentials_data['password'] ?? null,
                    'twofa_code' => $credentials_data['twofa_code'] ?? null,
                    'cookies' => $credentials_data['cookies'] ?? null,
                    'content_hash' => $content_hash,
                    'format' => $credentials_data['format'] ?? 'json',
                    'is_active' => 1,
                    'created_at' => current_time('mysql')
                ]
            );

            if (!$result) {
                throw new Exception("Failed to insert new version");
            }

            // Deactivate old versions
            $this->wpdb->update(
                "{$this->wpdb->prefix}vd_content_versions",
                ['is_active' => 0],
                [
                    'provider_account_id' => $provider_account_id,
                    'version_number' => $new_version
                ],
                ['%d'],
                ['%d', '%s']
            );

            $this->wpdb->query('COMMIT');

            return [
                'success' => true,
                'version_number' => $new_version,
                'content_hash' => $content_hash,
                'message' => 'Credentials updated successfully'
            ];

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return [
                'success' => false,
                'message' => 'Failed to update credentials: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get provider account statistics by name
     */
    public function get_account_stats_by_name($account_name) {
        $provider_account = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}vd_provider_accounts WHERE account_name = %s",
            $account_name
        ), ARRAY_A);

        if (!$provider_account) {
            return ['error' => 'Account not found'];
        }

        // Get assigned licenses
        $assigned_licenses = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT
                l.id, l.license_key, l.product_id, l.status, l.expires_at,
                la.assigned_at, la.last_accessed, la.assignment_method,
                COUNT(dr.id) as device_count,
                SUM(CASE WHEN dr.status = 'approved' THEN 1 ELSE 0 END) as approved_devices
            FROM {$this->wpdb->prefix}vd_licenses l
            INNER JOIN {$this->wpdb->prefix}vd_license_assignments la ON l.id = la.license_id
            LEFT JOIN {$this->wpdb->prefix}vd_device_requests dr ON l.id = dr.license_id
            WHERE la.provider_account_id = %d AND la.status = 'active'
            GROUP BY l.id
            ORDER BY la.last_accessed DESC
        ", $provider_account['id']), ARRAY_A);

        return [
            'account_info' => $provider_account,
            'assigned_licenses' => $assigned_licenses,
            'stats' => [
                'total_assigned_licenses' => count($assigned_licenses),
                'capacity_usage' => $provider_account['current_load'] . '/' . $provider_account['capacity']
            ]
        ];
    }
}
```

### 10.4 Analytics Class (class-vd-analytics.php)
```php
<?php
class VD_Analytics {

    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get comprehensive product analytics
     */
    public function get_product_analytics($product_id) {
        // Basic stats
        $stats = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT
                COUNT(l.id) as total_licenses,
                SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active_licenses,
                SUM(CASE WHEN l.status = 'expired' THEN 1 ELSE 0 END) as expired_licenses,
                SUM(CASE WHEN l.status = 'suspended' THEN 1 ELSE 0 END) as suspended_licenses,
                COUNT(DISTINCT la.provider_account_id) as assigned_providers,
                COUNT(dr.id) as total_devices,
                SUM(CASE WHEN dr.status = 'approved' THEN 1 ELSE 0 END) as approved_devices
            FROM {$this->wpdb->prefix}vd_licenses l
            LEFT JOIN {$this->wpdb->prefix}vd_license_assignments la ON l.id = la.license_id
            LEFT JOIN {$this->wpdb->prefix}vd_device_requests dr ON l.id = dr.license_id
            WHERE l.product_id = %d
        ", $product_id), ARRAY_A);

        // Product settings
        $product_settings = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}vd_product_settings WHERE product_id = %d",
            $product_id
        ), ARRAY_A);

        // Assigned providers with performance metrics
        $assigned_providers = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT
                pa.id, pa.account_name, pa.provider, pa.status,
                COUNT(la.id) as license_count,
                AVG(al.response_time_ms) as avg_response_time,
                COUNT(al.id) as total_requests,
                SUM(CASE WHEN al.status = 'success' THEN 1 ELSE 0 END) as successful_requests,
                (SUM(CASE WHEN al.status = 'success' THEN 1 ELSE 0 END) * 100.0 / COUNT(al.id)) as success_rate
            FROM {$this->wpdb->prefix}vd_provider_accounts pa
            INNER JOIN {$this->wpdb->prefix}vd_license_assignments la ON pa.id = la.provider_account_id
            INNER JOIN {$this->wpdb->prefix}vd_licenses l ON la.license_id = l.id
            LEFT JOIN {$this->wpdb->prefix}vd_access_logs al ON l.id = al.license_id AND al.provider_account_id = pa.id
            WHERE l.product_id = %d AND la.status = 'active'
            GROUP BY pa.id
            ORDER BY license_count DESC
        ", $product_id), ARRAY_A);

        return [
            'product_id' => $product_id,
            'stats' => $stats ?: [
                'total_licenses' => 0,
                'active_licenses' => 0,
                'expired_licenses' => 0,
                'suspended_licenses' => 0,
                'assigned_providers' => 0,
                'total_devices' => 0,
                'approved_devices' => 0
            ],
            'settings' => $product_settings,
            'assigned_providers' => $assigned_providers
        ];
    }

    /**
     * Get license list with detailed information
     */
    public function get_detailed_license_list($filters = []) {
        $where_clauses = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['product_id'])) {
            $where_clauses[] = "l.product_id = %d";
            $params[] = $filters['product_id'];
        }

        if (!empty($filters['status'])) {
            $where_clauses[] = "l.status = %s";
            $params[] = $filters['status'];
        }

        if (!empty($filters['provider_account_id'])) {
            $where_clauses[] = "la.provider_account_id = %d";
            $params[] = $filters['provider_account_id'];
        }

        if (!empty($filters['has_overrides'])) {
            $where_clauses[] = "lso.id IS " . ($filters['has_overrides'] === 'yes' ? 'NOT NULL' : 'NULL');
        }

        $where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

        $sql = "
            SELECT
                l.id, l.license_key, l.product_id, l.status, l.expires_at, l.created_at,
                pa.account_name as assigned_account,
                pa.provider as provider_type,
                la.assignment_method, la.assigned_at,
                COUNT(DISTINCT dr.id) as total_devices,
                SUM(CASE WHEN dr.status = 'approved' THEN 1 ELSE 0 END) as approved_devices,
                (CASE WHEN lso.id IS NOT NULL THEN 'yes' ELSE 'no' END) as has_override,
                ps.max_devices as product_max_devices,
                lso.max_devices as license_max_devices
            FROM {$this->wpdb->prefix}vd_licenses l
            LEFT JOIN {$this->wpdb->prefix}vd_license_assignments la ON l.id = la.license_id
            LEFT JOIN {$this->wpdb->prefix}vd_provider_accounts pa ON la.provider_account_id = pa.id
            LEFT JOIN {$this->wpdb->prefix}vd_device_requests dr ON l.id = dr.license_id
            LEFT JOIN {$this->wpdb->prefix}vd_license_settings_override lso ON l.id = lso.license_id
            LEFT JOIN {$this->wpdb->prefix}vd_product_settings ps ON l.product_id = ps.product_id
            {$where_sql}
            GROUP BY l.id, pa.account_name, pa.provider, la.assignment_method, la.assigned_at, lso.id, ps.max_devices, lso.max_devices
            ORDER BY l.created_at DESC
        ";

        if (empty($params)) {
            return $this->wpdb->get_results($sql, ARRAY_A);
        } else {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
        }
    }
}
```

This plugin development guide provides the complete WordPress plugin architecture for the VD License Manager system, including all new classes for settings management, provider management, assignment tracking, and analytics functionality. The system now supports all the requested features including settings inheritance, manual account assignment, plain text credential storage, and comprehensive admin interfaces.
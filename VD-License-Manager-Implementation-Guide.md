# VD License Manager - Implementation & Deployment Guide

## 📋 Implementation Roadmap

### Phase 1: Foundation Setup (Week 1-2)
- [x] Database schema design
- [x] Plugin architecture planning
- [x] Security implementation (OpenSSL encryption)
- [ ] **NEXT**: Core plugin structure
- [ ] Basic license validation
- [ ] Device fingerprinting
- [ ] Database table creation & migration

### Phase 2: Core Features (Week 3-4)
- [ ] License-to-provider assignment (sticky)
- [ ] Content management (cookie/credentials)
- [ ] Device management & approval system
- [ ] Risk scoring algorithm
- [ ] Rate limiting with smart bypass
- [ ] Customer API endpoint

### Phase 3: Admin Interface (Week 5-6)
- [ ] Admin dashboard
- [ ] Provider account management
- [ ] Device approval interface
- [ ] Audit log viewer
- [ ] Rate limiting configuration
- [ ] WooCommerce integration

### Phase 4: Polish & Launch (Week 7-8)
- [ ] UI/UX refinements
- [ ] Copy+download functionality
- [ ] Comprehensive testing
- [ ] Performance optimization
- [ ] Documentation finalization
- [ ] Production deployment

---

## 🚀 Server Requirements

### Minimum System Requirements
```bash
PHP: >= 8.0
MySQL: >= 5.7 (hoặc MariaDB >= 10.3)
WordPress: >= 5.0
WooCommerce: >= 5.0
License Manager for WooCommerce: >= 3.0
Memory Limit: >= 256M
Max Execution Time: >= 120s
```

### Required PHP Extensions
```bash
php -m | grep -E "(openssl|json|pdo|mysqli|curl|mbstring|gd|zip)"
```

Cần có:
- `openssl` - cho encryption
- `json` - API responses
- `pdo_mysql`/`mysqli` - database
- `curl` - external API calls
- `mbstring` - string handling
- `gd` - image processing (nếu có captcha)
- `zip` - plugin updates

---

## ⚙️ Installation & Setup

### Step 1: Pre-installation Setup

#### Tạo Encryption Key
```bash
# Tạo 32-byte encryption key
php -r 'echo "VD_ENCRYPTION_KEY: base64:" . base64_encode(random_bytes(32)) . PHP_EOL;'
```

#### Cấu hình wp-config.php
```php
// Thêm vào wp-config.php (KHÔNG commit file này)
define('VD_ENCRYPTION_KEY', 'base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=');

// Optional: Debug mode (chỉ dùng trên dev)
define('VD_DEBUG_MODE', false);

// Database connection tuning
define('WP_MEMORY_LIMIT', '512M');
ini_set('max_execution_time', 300);
```

### Step 2: Plugin Installation

#### Method 1: Upload Plugin
```bash
# Upload plugin zip file qua WordPress Admin
# hoặc extract vào wp-content/plugins/
unzip vd-license-manager.zip -d /path/to/wp-content/plugins/
```

#### Method 2: Git Clone (Developer)
```bash
cd wp-content/plugins/
git clone [repository-url] vd-license-manager
cd vd-license-manager
composer install --no-dev  # nếu có dependencies
```

### Step 3: Database Setup

#### Activate Plugin
- Vào WordPress Admin → Plugins
- Activate "VD License Manager"
- Plugin sẽ tự tạo database tables

#### Verify Tables Created
```sql
SHOW TABLES LIKE 'wp_vd_%';
-- Expected: 15+ tables
```

### Step 4: Dependencies Setup

#### LMfWC Configuration
```php
// Trong WordPress Admin → LMfWC Settings
REST API: Enable
API Key: Generate and save securely

// Test connection
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/lmfwc/v2/licenses
```

---

## 🛠️ Development Implementation Steps

### Step 1: Create Main Plugin File

#### `vd-license-manager.php`
```php
<?php
/**
 * Plugin Name: VD License Manager
 * Description: Advanced license management system for Helium10, Midjourney, Freepik
 * Version: 1.0.0
 * Author: VD Team
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VD_LM_VERSION', '1.0.0');
define('VD_LM_PATH', plugin_dir_path(__FILE__));
define('VD_LM_URL', plugin_dir_url(__FILE__));
define('VD_LM_FILE', __FILE__);

// Plugin activation/deactivation hooks
register_activation_hook(__FILE__, 'vd_license_manager_activate');
register_deactivation_hook(__FILE__, 'vd_license_manager_deactivate');

/**
 * Plugin activation
 */
function vd_license_manager_activate() {
    // Check requirements
    if (!vd_check_requirements()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('VD License Manager requires PHP 8.0+ and WordPress 5.0+');
    }

    // Create database tables
    require_once VD_LM_PATH . 'includes/class-database-manager.php';
    VD_Database_Manager::create_tables();

    // Add custom capabilities
    vd_add_custom_capabilities();

    // Schedule cron jobs
    vd_schedule_cron_jobs();
}

/**
 * Plugin deactivation
 */
function vd_license_manager_deactivate() {
    // Clear scheduled crons
    wp_clear_scheduled_hook('vd_cleanup_logs');
    wp_clear_scheduled_hook('vd_check_provider_health');
}

/**
 * Check system requirements
 */
function vd_check_requirements() {
    global $wp_version;

    if (version_compare(PHP_VERSION, '8.0', '<')) {
        return false;
    }

    if (version_compare($wp_version, '5.0', '<')) {
        return false;
    }

    if (!extension_loaded('openssl')) {
        return false;
    }

    return true;
}

// Initialize plugin
add_action('plugins_loaded', 'vd_license_manager_init');

function vd_license_manager_init() {
    // Load main plugin class
    require_once VD_LM_PATH . 'includes/class-vd-license-manager.php';

    // Initialize
    VD_License_Manager::get_instance()->init();
}
```

### Step 2: Create Database Manager

#### `includes/class-database-manager.php`
```php
<?php

class VD_Database_Manager {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $tables = self::get_table_schemas($charset_collate);

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        // Update version
        update_option('vd_license_manager_db_version', VD_LM_VERSION);
    }

    private static function get_table_schemas($charset_collate) {
        global $wpdb;

        return [
            // Provider accounts table
            "CREATE TABLE {$wpdb->prefix}vd_provider_accounts (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                provider enum('helium10','midjourney','freepik') NOT NULL,
                share_type enum('cookie','credentials','credentials_2fa') NOT NULL,
                account_name varchar(255) NOT NULL,
                capacity int unsigned NOT NULL DEFAULT 10,
                current_load int unsigned NOT NULL DEFAULT 0,
                status enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_provider_status (provider, status)
            ) $charset_collate;",

            // Content versions table
            "CREATE TABLE {$wpdb->prefix}vd_content_versions (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                provider_account_id bigint(20) unsigned NOT NULL,
                version_number int unsigned NOT NULL,
                content_type enum('cookie','credentials') NOT NULL,
                encrypted_content mediumtext NOT NULL,
                content_hash varchar(64) NOT NULL,
                format enum('json','netscape','headers','plain') NOT NULL DEFAULT 'json',
                scope varchar(255) NULL,
                expires_at datetime NULL,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_account_version (provider_account_id, version_number),
                KEY idx_active_content (provider_account_id, is_active),
                FOREIGN KEY (provider_account_id) REFERENCES {$wpdb->prefix}vd_provider_accounts(id) ON DELETE CASCADE
            ) $charset_collate;",

            // Add more tables here from the complete schema...
        ];
    }

    public static function check_and_update_tables() {
        $current_version = get_option('vd_license_manager_db_version', '0.0.0');

        if (version_compare($current_version, VD_LM_VERSION, '<')) {
            self::create_tables();
        }
    }
}
```

### Step 3: Create Core License Manager Class

#### `includes/class-vd-license-manager.php`
```php
<?php

class VD_License_Manager {
    private static $instance = null;
    private $license_core;
    private $device_manager;
    private $content_manager;
    private $assignment_engine;
    private $security_manager;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        $this->load_dependencies();
        $this->setup_hooks();
        $this->init_components();
        $this->setup_api_routes();
    }

    private function load_dependencies() {
        require_once VD_LM_PATH . 'includes/class-license-core.php';
        require_once VD_LM_PATH . 'includes/class-device-manager.php';
        require_once VD_LM_PATH . 'includes/class-content-manager.php';
        require_once VD_LM_PATH . 'includes/class-assignment-engine.php';
        require_once VD_LM_PATH . 'security/class-security-manager.php';
        require_once VD_LM_PATH . 'api/class-customer-api.php';
        require_once VD_LM_PATH . 'api/class-admin-api.php';

        // Load integrations
        require_once VD_LM_PATH . 'integrations/class-woocommerce.php';
        require_once VD_LM_PATH . 'integrations/class-lmfwc.php';

        // Load admin interface
        if (is_admin()) {
            require_once VD_LM_PATH . 'admin/class-admin-dashboard.php';
        }
    }

    private function setup_hooks() {
        // Initialize database check
        add_action('wp_loaded', [VD_Database_Manager::class, 'check_and_update_tables']);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Setup cron jobs
        add_action('vd_cleanup_logs', [$this, 'cleanup_old_logs']);
        add_action('vd_check_provider_health', [$this, 'check_provider_health']);
    }

    private function init_components() {
        $this->security_manager = new VD_Security_Manager();
        $this->license_core = new VD_License_Core();
        $this->device_manager = new VD_Device_Manager();
        $this->content_manager = new VD_Content_Manager();
        $this->assignment_engine = new VD_Assignment_Engine();
    }

    private function setup_api_routes() {
        add_action('rest_api_init', function() {
            $customer_api = new VD_Customer_API();
            $customer_api->register_routes();

            $admin_api = new VD_Admin_API();
            $admin_api->register_routes();
        });
    }

    public function enqueue_public_assets() {
        wp_enqueue_script(
            'vd-license-manager-public',
            VD_LM_URL . 'public/assets/js/public-script.js',
            ['jquery'],
            VD_LM_VERSION,
            true
        );

        wp_enqueue_script(
            'vd-device-fingerprint',
            VD_LM_URL . 'public/assets/js/device-fingerprint.js',
            [],
            VD_LM_VERSION,
            true
        );

        wp_enqueue_style(
            'vd-license-manager-public',
            VD_LM_URL . 'public/assets/css/public-style.css',
            [],
            VD_LM_VERSION
        );

        // Localize script
        wp_localize_script('vd-license-manager-public', 'vd_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('vd/v1/'),
            'nonce' => wp_create_nonce('vd_license_manager_nonce')
        ]);
    }

    public function enqueue_admin_assets() {
        wp_enqueue_script(
            'vd-license-manager-admin',
            VD_LM_URL . 'admin/assets/js/admin-script.js',
            ['jquery', 'wp-api'],
            VD_LM_VERSION,
            true
        );

        wp_enqueue_style(
            'vd-license-manager-admin',
            VD_LM_URL . 'admin/assets/css/admin-style.css',
            [],
            VD_LM_VERSION
        );
    }

    public function cleanup_old_logs() {
        global $wpdb;

        // Clean logs older than 90 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}vd_access_logs WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-90 days'))
        ));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}vd_audit_logs WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-90 days'))
        ));
    }

    public function check_provider_health() {
        // Implementation for provider health checks
        require_once VD_LM_PATH . 'includes/class-provider-health-checker.php';
        $health_checker = new VD_Provider_Health_Checker();
        $health_checker->check_all_providers();
    }
}
```

### Step 4: Implementation Testing Strategy

#### Unit Tests Setup
```bash
# Install PHPUnit for WordPress
composer require --dev phpunit/phpunit
composer require --dev yoast/phpunit-polyfills

# Create tests directory
mkdir tests
mkdir tests/unit
mkdir tests/integration
```

#### Basic Test Example
```php
<?php
// tests/unit/test-license-core.php

class Test_License_Core extends WP_UnitTestCase {

    private $license_core;

    public function setUp(): void {
        parent::setUp();
        $this->license_core = new VD_License_Core();
    }

    public function test_validate_license_key_format() {
        // Test valid format
        $this->assertTrue(vd_validate_license_key('VD-1234-ABCD-5678'));

        // Test invalid formats
        $this->assertFalse(vd_validate_license_key('invalid-key'));
        $this->assertFalse(vd_validate_license_key(''));
        $this->assertFalse(vd_validate_license_key('vd-1234-abcd-5678'));
    }

    public function test_device_fingerprint_validation() {
        // Test valid fingerprint (64 char hex)
        $valid_fp = hash('sha256', 'test-device-info');
        $this->assertTrue(vd_validate_device_fp($valid_fp));

        // Test invalid fingerprints
        $this->assertFalse(vd_validate_device_fp('short'));
        $this->assertFalse(vd_validate_device_fp('invalid-hex-chars-ZZZZ'));
    }

    public function test_risk_score_calculation() {
        // Mock data
        $license_id = 123;
        $device_fp = hash('sha256', 'test-device');
        $device_info = [
            'ip' => '1.2.3.4',
            'user_agent' => 'Mozilla/5.0...',
            'country' => 'VN'
        ];

        // Test calculation
        $score = calculate_risk_score($license_id, $device_fp, $device_info);
        $this->assertIsNumeric($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }
}
```

---

## 🔧 Configuration

### 1. Plugin Settings

#### VD License Manager Settings
```php
// WordPress Admin → VD License → Settings
General Settings:
- Default Device Limit: 3
- Auto-Approval: Enable
- Rate Limiting: 10 requests/5 minutes
- Debug Logging: Disable (production)

Provider Settings:
- Default Provider: helium10
- Failover Mode: Enable
- Health Check Interval: 1 hour

Security Settings:
- Encryption Key: [Configured in wp-config.php]
- Session Timeout: 24 hours
- Max Login Attempts: 5
```

### 2. WooCommerce Integration

#### Product Configuration
```php
// Cho mỗi product cần license:
Product Data → General:
☑ Enable VD License Manager
Provider Type: [helium10/midjourney/freepik]
License Duration: 30 days
Device Limit: 3 (override global)
```

### 3. Provider Accounts Setup

#### Admin Dashboard Configuration
```
VD License → Provider Accounts → Add New:
- Provider: Helium10
- Share Type: credentials_2fa
- Account Name: main-account-01
- Capacity: 10 licenses
- Credentials: [encrypted storage]
```

---

## 📊 Environment-Specific Settings

### Development Environment
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('VD_DEBUG_MODE', true);

// Less restrictive rate limits
define('VD_RATE_LIMIT_DEV', true);
```

### Staging Environment
```php
// wp-config.php
define('WP_DEBUG', false);
define('VD_DEBUG_MODE', false);

// Use separate database
$table_prefix = 'staging_wp_';

// Disable email notifications
define('VD_DISABLE_EMAILS', true);
```

### Production Environment
```php
// wp-config.php
define('WP_DEBUG', false);
define('VD_DEBUG_MODE', false);
define('VD_LOG_LEVEL', 'error'); // Only log errors

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// Performance
define('WP_CACHE', true);
```

---

## 🔒 Security Checklist

### File Permissions
```bash
# WordPress standard
find /path/to/wordpress -type d -exec chmod 755 {} \;
find /path/to/wordpress -type f -exec chmod 644 {} \;
chmod 600 wp-config.php

# Plugin specific
chmod 600 wp-content/plugins/vd-license-manager/config/*
```

### Database Security
```sql
-- Create dedicated database user
CREATE USER 'vd_license'@'localhost' IDENTIFIED BY 'strong-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON wp_vd_*.* TO 'vd_license'@'localhost';
FLUSH PRIVILEGES;
```

### Backup Strategy
```bash
# Daily database backup
0 2 * * * mysqldump -u backup_user -p'password' wp_database > /backups/wp_$(date +\%Y\%m\%d).sql

# Weekly encryption key backup
0 1 * * 0 echo "VD_ENCRYPTION_KEY backup: $(grep VD_ENCRYPTION_KEY wp-config.php)" > /secure-backup/keys-$(date +\%Y\%m\%d).txt
```

---

## 📝 Testing Procedures

### 1. Basic Functionality Test
```bash
# Test license creation
curl -X POST https://yoursite.com/wp-json/vd/v1/license/resolve-info \
  -H "Content-Type: application/json" \
  -d '{"license_key":"TEST-1234","device_fp":"abc123..."}'
```

### 2. Encryption Test
```php
// WP-CLI command
wp eval 'echo vd_encrypt_aes_gcm("test data");'
wp eval 'echo vd_decrypt_aes_gcm("encrypted_blob_here");'
```

### 3. Database Test
```sql
-- Verify data integrity
SELECT COUNT(*) FROM wp_vd_provider_accounts;
SELECT COUNT(*) FROM wp_vd_licenses;
```

### 4. Load Testing
```bash
# Use Apache Bench for API load testing
ab -n 1000 -c 10 -H "Content-Type: application/json" \
   -p license-request.json \
   https://yoursite.com/wp-json/vd/v1/license/resolve-info
```

---

## 🐛 Troubleshooting

### Common Issues

#### "Encryption key not defined"
```php
// Fix: Add to wp-config.php
define('VD_ENCRYPTION_KEY', 'base64:your-key-here');
```

#### "Database table doesn't exist"
```php
// Fix: Reactivate plugin or manual table creation
wp plugin deactivate vd-license-manager
wp plugin activate vd-license-manager
```

#### "LMfWC API connection failed"
```php
// Check: API key và endpoint
wp option get lmfwc_rest_api_key
wp option get lmfwc_rest_api_url
```

#### High Memory Usage
```php
// Check memory usage
wp eval 'echo "Memory: " . memory_get_peak_usage(true) / 1024 / 1024 . "MB";'

// Increase limits in wp-config.php
ini_set('memory_limit', '512M');
```

### Debug Logging
```php
// Enable detailed logging
add_action('init', function() {
    if (defined('VD_DEBUG_MODE') && VD_DEBUG_MODE) {
        error_log('VD License Manager Debug Mode Enabled');
    }
});
```

### Performance Monitoring
```bash
# Monitor slow queries
sudo tail -f /var/log/mysql/mysql-slow.log | grep vd_

# Check PHP error logs
tail -f /var/log/php/error.log | grep "VD License"
```

---

## 🔄 Update & Maintenance Procedures

### Plugin Updates
```bash
# Backup before update
cp -r wp-content/plugins/vd-license-manager /backup/plugins/

# Update plugin
# Auto-update through WordPress Admin hoặc manual replace

# Run migration if needed
wp plugin activate vd-license-manager
```

### Database Migrations
```sql
-- Check version
SELECT option_value FROM wp_options WHERE option_name = 'vd_license_manager_version';

-- Manual migration if needed
-- (Plugin handles automatically)
```

### Regular Maintenance Tasks

#### Daily Tasks
- Monitor error logs
- Check provider account health
- Verify backup completion
- Review rate limiting stats

#### Weekly Tasks
- Review audit logs
- Check device approval rates
- Update provider credentials if needed
- Analyze performance metrics

#### Monthly Tasks
- Database optimization
- Performance review
- Security audit
- Clean old logs (>90 days)
- Provider account capacity review

---

## 📞 Support & Monitoring

### Log Files Location
```
/wp-content/debug.log (WordPress)
/wp-content/plugins/vd-license-manager/logs/ (Plugin)
/var/log/nginx/error.log (Server)
/var/log/mysql/mysql-slow.log (Database)
```

### Monitoring Metrics
- API response times
- Rate limiting hit rates
- Device approval success rates
- Provider account health
- Database query performance

### Required Info for Support
1. WordPress version
2. Plugin version
3. PHP version
4. Error logs
5. Steps to reproduce issue
6. Server specifications

---

## 🎯 Next Steps for Implementation

### Immediate Actions (Week 1)
1. **Create plugin structure** - Set up folder hierarchy
2. **Implement database manager** - Create all required tables
3. **Build security manager** - OpenSSL encryption functions
4. **Create main plugin class** - Core initialization

### Priority Development (Week 2-3)
1. **License core functionality** - Validation & assignment
2. **Device management** - Fingerprinting & approval
3. **Content manager** - Cookie/credentials handling
4. **Customer API** - Main resolve-info endpoint

### Admin Interface (Week 4-5)
1. **Admin dashboard** - Overview & statistics
2. **Provider management** - Account configuration
3. **Device approval** - Manual review interface
4. **WooCommerce integration** - Product meta fields

### Final Polish (Week 6-8)
1. **UI/UX improvements** - Customer portal design
2. **Copy+download feature** - JavaScript implementation
3. **Comprehensive testing** - Unit & integration tests
4. **Performance optimization** - Caching & queries
5. **Production deployment** - Final configuration

---

**⚠️ Security Reminder**: Luôn backup encryption key an toàn. Mất key = mất tất cả dữ liệu encrypted.
<?php
/**
 * Core database tables for VD License Manager
 *
 * Creates the main 4 tables for provider accounts, pools, and assignments
 *
 * @package VD_License_Manager
 * @subpackage Database
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Database Core class
 *
 * Handles creation of core database tables for the plugin
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_DB_Core {

    /**
     * Table prefix
     *
     * @since 1.0.0
     * @var string
     */
    private $table_prefix;

    /**
     * Database charset
     *
     * @since 1.0.0
     * @var string
     */
    private $charset_collate;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix;
        $this->charset_collate = $wpdb->get_charset_collate();

        // Override to use utf8mb4 explicitly per environment config
        if (empty($this->charset_collate)) {
            $this->charset_collate = 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        }
    }

    /**
     * Create all core database tables
     *
     * Creates the 4 core tables:
     * - bz_vd_provider_accounts: Provider account credentials
     * - bz_vd_product_pools: Product pools for license management
     * - bz_vd_pool_accounts: Links accounts to pools
     * - bz_vd_cookie_assignments: Maps licenses to accounts
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public static function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Table 1: Provider Accounts
        $sql1 = "CREATE TABLE bz_vd_provider_accounts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider varchar(50) NOT NULL,
            account_login varchar(255) NOT NULL,
            display_name varchar(255) NOT NULL,
            capacity int(11) NOT NULL DEFAULT 5,
            status varchar(20) NOT NULL DEFAULT 'active',
            cookie longtext,
            cookie_format varchar(20) DEFAULT 'json',
            cookie_updated_at datetime,
            login_email varchar(255),
            login_password varchar(255),
            totp_secret varchar(255),
            recovery_email varchar(255),
            recovery_phone varchar(50),
            notes text,

            -- Subscription Management
            subscription_start_date DATE NULL COMMENT 'Ngày bắt đầu đăng ký',
            subscription_end_date DATE NULL COMMENT 'Ngày hết hạn đăng ký',
            subscription_cost DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'Số tiền đăng ký',
            currency VARCHAR(3) NULL DEFAULT 'USD' COMMENT 'Đơn vị tiền tệ',
            auto_renewal TINYINT(1) NULL DEFAULT 0 COMMENT 'Tự động gia hạn',

            -- Account Details
            plan_type VARCHAR(50) NULL COMMENT 'Loại gói dịch vụ',
            profile_limit INT NULL DEFAULT 1 COMMENT 'Số profile tối đa',
            video_quality ENUM('SD','HD','4K','8K') NULL COMMENT 'Chất lượng video',
            account_region VARCHAR(5) NULL COMMENT 'Vùng tài khoản',

            -- Security
            last_password_changed DATETIME NULL COMMENT 'Lần đổi password cuối',
            has_2fa TINYINT(1) NULL DEFAULT 0 COMMENT 'Có 2FA không',
            security_level ENUM('low','medium','high') NULL DEFAULT 'medium' COMMENT 'Mức bảo mật',

            -- Business Intelligence
            total_revenue DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'Tổng doanh thu',
            total_licenses_served INT NULL DEFAULT 0 COMMENT 'Tổng licenses phục vụ',
            success_rate DECIMAL(5,2) NULL DEFAULT 0.00 COMMENT 'Tỷ lệ thành công',

            expires_at datetime,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_provider (provider),
            KEY idx_status (status),
            KEY idx_account_login (account_login),
            KEY idx_subscription_dates (subscription_start_date, subscription_end_date),
            KEY idx_plan_type (plan_type),
            KEY idx_account_region (account_region)
        ) $charset_collate;";

        // Table 2: Product Pools
        $sql2 = "CREATE TABLE bz_vd_product_pools (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL,
            strategy varchar(20) NOT NULL DEFAULT 'sticky',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_product (product_id)
        ) $charset_collate;";

        // Table 3: Pool Accounts
        $sql3 = "CREATE TABLE bz_vd_pool_accounts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pool_id bigint(20) unsigned NOT NULL,
            provider_account_id bigint(20) unsigned NOT NULL,
            weight int(11) DEFAULT 1,
            priority int(11) DEFAULT 0,
            added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_pool (pool_id),
            KEY idx_account (provider_account_id)
        ) $charset_collate;";

        // Table 4: Cookie Assignments
        $sql4 = "CREATE TABLE bz_vd_cookie_assignments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            provider_account_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            revoked_at datetime,
            notes text,
            PRIMARY KEY  (id),
            KEY idx_license (license_id),
            KEY idx_account (provider_account_id),
            KEY idx_status (status)
        ) $charset_collate;";

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);

        error_log('VD License Manager: Core database tables created successfully');
    }

    /**
     * Get all table definitions
     *
     * @since 1.0.0
     * @return array Array of table SQL definitions
     */
    private function get_table_definitions() {
        return array(
            'provider_accounts' => $this->get_provider_accounts_sql(),
            'product_pools' => $this->get_product_pools_sql(),
            'pool_accounts' => $this->get_pool_accounts_sql(),
            'cookie_assignments' => $this->get_cookie_assignments_sql()
        );
    }

    /**
     * Get provider accounts table SQL
     *
     * @since 1.0.0
     * @return string
     */
    private function get_provider_accounts_sql() {
        $table_name = $this->table_prefix . 'vd_provider_accounts';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(64) NOT NULL,
            account_login VARCHAR(191) NOT NULL,
            display_name VARCHAR(191) NOT NULL,
            status ENUM('active','suspended','expired') DEFAULT 'active',
            capacity INT UNSIGNED NOT NULL DEFAULT 1,
            cookie TEXT NULL,
            cookie_format ENUM('json','netscape','headers') DEFAULT 'json',
            cookie_updated_at DATETIME NULL,
            login_email VARCHAR(255) NULL,
            login_password VARCHAR(255) NULL,
            totp_secret VARCHAR(100) NULL,
            recovery_email VARCHAR(255) NULL,
            recovery_phone VARCHAR(50) NULL,
            notes TEXT NULL,
            meta JSON NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_provider_login (provider, account_login)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get product pools table SQL
     *
     * @since 1.0.0
     * @return string
     */
    private function get_product_pools_sql() {
        $table_name = $this->table_prefix . 'vd_product_pools';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(191) NOT NULL,
            strategy ENUM('sticky','weighted','priority') DEFAULT 'sticky',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_product_pool (product_id)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get pool accounts table SQL
     *
     * @since 1.0.0
     * @return string
     */
    private function get_pool_accounts_sql() {
        $table_name = $this->table_prefix . 'vd_pool_accounts';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            pool_id BIGINT UNSIGNED NOT NULL,
            provider_account_id BIGINT UNSIGNED NOT NULL,
            capacity_override INT NULL,
            weight INT NULL,
            priority TINYINT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pool_id (pool_id),
            INDEX idx_provider_account_id (provider_account_id)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get cookie assignments table SQL
     *
     * @since 1.0.0
     * @return string
     */
    private function get_cookie_assignments_sql() {
        $table_name = $this->table_prefix . 'vd_cookie_assignments';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT UNSIGNED NOT NULL,
            provider_account_id BIGINT UNSIGNED NOT NULL,
            strategy ENUM('sticky','round_robin','priority') DEFAULT 'sticky',
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active','paused','migrating','revoked') DEFAULT 'active',
            notes TEXT NULL,
            UNIQUE KEY uq_license_assignment (license_id, status),
            INDEX idx_provider_account_status (provider_account_id, status)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get table names for core tables
     *
     * @since 1.0.0
     * @return array Array of core table names
     */
    public function get_core_table_names() {
        return array(
            'provider_accounts' => $this->table_prefix . 'vd_provider_accounts',
            'product_pools' => $this->table_prefix . 'vd_product_pools',
            'pool_accounts' => $this->table_prefix . 'vd_pool_accounts',
            'cookie_assignments' => $this->table_prefix . 'vd_cookie_assignments'
        );
    }

    /**
     * Check if core tables exist
     *
     * @since 1.0.0
     * @return bool True if all core tables exist
     */
    public function core_tables_exist() {
        global $wpdb;

        $tables = $this->get_core_table_names();

        foreach ($tables as $table_name) {
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ));

            if ($table_exists !== $table_name) {
                return false;
            }
        }

        return true;
    }
}
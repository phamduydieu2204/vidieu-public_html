<?php
/**
 * Logs and configuration database tables for VD License Manager
 *
 * Creates the 4 final tables for configuration and audit trail
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
 * VD Database Logs class
 *
 * Handles creation of logs and configuration database tables for the plugin
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_DB_Logs {

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
     * Create all logs and configuration tables
     *
     * Creates the 3 final tables:
     * - bz_vd_account_fetch_log: Account info fetch audit trail
     * - bz_vd_license_access_log: License access audit trail
     * - bz_vd_license_rate_limits: API rate limiting configuration
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public static function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Table 1: Product Share Configs
        $sql1 = "CREATE TABLE bz_vd_product_share_configs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            share_fields longtext NOT NULL,
            instructions text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_product (product_id)
        ) $charset_collate;";

        // Table 2: Account Fetch Log
        $sql2 = "CREATE TABLE bz_vd_account_fetch_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            device_fp char(64) NOT NULL,
            provider_account_id bigint(20) unsigned NOT NULL,
            fetched_fields longtext,
            ip_address varchar(45),
            user_agent text,
            country varchar(100),
            city varchar(100),
            status varchar(20) NOT NULL DEFAULT 'success',
            fetched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_license (license_id),
            KEY idx_device (device_fp),
            KEY idx_date (fetched_at)
        ) $charset_collate;";

        // Table 3: License Access Log
        $sql3 = "CREATE TABLE bz_vd_license_access_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            device_fp char(64) NOT NULL,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            reason text,
            ip_address varchar(45),
            user_agent text,
            country varchar(100),
            city varchar(100),
            accessed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_license (license_id),
            KEY idx_device (device_fp),
            KEY idx_date (accessed_at)
        ) $charset_collate;";

        // Table 4: License Rate Limits
        $sql4 = "CREATE TABLE bz_vd_license_rate_limits (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            window_seconds int(11) NOT NULL DEFAULT 300,
            max_hits int(11) NOT NULL DEFAULT 10,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_license (license_id)
        ) $charset_collate;";

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);

        error_log('VD License Manager: Logs and config tables created successfully');
    }

    /**
     * Get all logs and config table definitions
     *
     * @since 1.0.0
     * @return array Array of table SQL definitions
     */
    private function get_table_definitions() {
        return array(
            'product_share_configs' => $this->get_product_share_configs_sql(),
            'account_fetch_log' => $this->get_account_fetch_log_sql(),
            'license_access_log' => $this->get_license_access_log_sql(),
            'license_rate_limits' => $this->get_license_rate_limits_sql()
        );
    }

    /**
     * Get product share configs table SQL
     *
     * @since 1.0.0
     * @return string
     */
    private function get_product_share_configs_sql() {
        $table_name = $this->table_prefix . 'vd_product_share_configs';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            share_fields JSON NOT NULL,
            display_format ENUM('portal','file','api') DEFAULT 'portal',
            instructions TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_product_config (product_id)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get account fetch log table SQL
     *
     * @since 1.0.0
     * @return string
     */
    private function get_account_fetch_log_sql() {
        $table_name = $this->table_prefix . 'vd_account_fetch_log';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT UNSIGNED NOT NULL,
            device_fp CHAR(64) NOT NULL,
            provider_account_id BIGINT UNSIGNED NOT NULL,
            fetched_fields JSON NOT NULL,
            fetched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            status ENUM('success','fail','blocked','rate_limited') DEFAULT 'success',
            reason TEXT NULL,
            INDEX idx_license_fetched (license_id, fetched_at),
            INDEX idx_device_fetched (device_fp, fetched_at),
            INDEX idx_provider_account (provider_account_id)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get license access log table SQL
     *
     * @since 1.0.0
     * @return string
     */
    private function get_license_access_log_sql() {
        $table_name = $this->table_prefix . 'vd_license_access_log';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT UNSIGNED NOT NULL,
            device_fp CHAR(64) NOT NULL,
            action ENUM('verify','approve','block','rotate') NOT NULL,
            status ENUM('success','fail','pending','blocked') NOT NULL,
            reason TEXT NULL,
            ip_address VARCHAR(45) NULL,
            ip_country CHAR(2) NULL,
            ip_city VARCHAR(100) NULL,
            accessed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_license_accessed (license_id, accessed_at),
            INDEX idx_device_accessed (device_fp, accessed_at)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get license rate limits table SQL
     *
     * @since 1.0.0
     * @return string
     */
    private function get_license_rate_limits_sql() {
        $table_name = $this->table_prefix . 'vd_license_rate_limits';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT UNSIGNED NOT NULL,
            window_seconds INT UNSIGNED NOT NULL DEFAULT 300,
            max_hits INT UNSIGNED NOT NULL DEFAULT 10,
            last_reset DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_license_window (license_id, window_seconds)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get table names for logs and config tables
     *
     * @since 1.0.0
     * @return array Array of logs and config table names
     */
    public function get_logs_table_names() {
        return array(
            'product_share_configs' => $this->table_prefix . 'vd_product_share_configs',
            'account_fetch_log' => $this->table_prefix . 'vd_account_fetch_log',
            'license_access_log' => $this->table_prefix . 'vd_license_access_log',
            'license_rate_limits' => $this->table_prefix . 'vd_license_rate_limits'
        );
    }

    /**
     * Check if logs and config tables exist
     *
     * @since 1.0.0
     * @return bool True if all logs and config tables exist
     */
    public function logs_tables_exist() {
        global $wpdb;

        $tables = $this->get_logs_table_names();

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
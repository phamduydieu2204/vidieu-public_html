<?php
/**
 * Device database tables for VD License Manager
 *
 * Creates the 3 device control tables for fingerprinting and access management
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
 * VD Database Devices class
 *
 * Handles creation of device control database tables for the plugin
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_DB_Devices {

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
     * Create all device database tables
     *
     * Creates the 3 device tables:
     * - bz_vd_device_fingerprints: Device registry with SHA-256 fingerprints
     * - bz_vd_license_devices: Device allowlist for licenses
     * - bz_vd_license_device_limits: Device limits per license
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public static function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Table 1: Device Fingerprints
        $sql1 = "CREATE TABLE bz_vd_device_fingerprints (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            device_fp char(64) NOT NULL,
            user_agent text,
            os varchar(100),
            browser varchar(100),
            screen_resolution varchar(20),
            timezone_offset int(11),
            language varchar(10),
            platform varchar(50),
            first_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_device_fp (device_fp)
        ) $charset_collate;";

        // Table 2: License Devices
        $sql2 = "CREATE TABLE bz_vd_license_devices (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            device_fp char(64) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            first_used_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_used_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            approved_by bigint(20) unsigned,
            approved_at datetime,
            notes text,
            PRIMARY KEY  (id),
            KEY idx_license (license_id),
            KEY idx_device (device_fp),
            KEY idx_status (status)
        ) $charset_collate;";

        // Table 3: License Device Limits
        $sql3 = "CREATE TABLE bz_vd_license_device_limits (
            license_id bigint(20) unsigned NOT NULL,
            max_devices int(11) NOT NULL DEFAULT 1,
            rotation_cooldown_seconds int(11) NOT NULL DEFAULT 86400,
            last_rotation_at datetime,
            PRIMARY KEY  (license_id)
        ) $charset_collate;";

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);

        error_log('VD License Manager: Device database tables created successfully');
    }

    /**
     * Get all device table definitions
     *
     * @since 1.0.0
     * @return array Array of table SQL definitions
     */
    private function get_table_definitions() {
        return array(
            'device_fingerprints' => $this->get_device_fingerprints_sql(),
            'license_devices' => $this->get_license_devices_sql(),
            'license_device_limits' => $this->get_license_device_limits_sql()
        );
    }

    /**
     * Get device fingerprints table SQL
     *
     * Stores device fingerprints with SHA-256 hash and metadata
     *
     * @since 1.0.0
     * @return string
     */
    private function get_device_fingerprints_sql() {
        $table_name = $this->table_prefix . 'vd_device_fingerprints';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            fp_hash CHAR(64) NOT NULL UNIQUE,

            first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_seen DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            ua VARCHAR(512) NULL,
            os VARCHAR(64) NULL,
            browser VARCHAR(64) NULL,
            device_model VARCHAR(64) NULL,

            meta JSON NULL,

            INDEX idx_fp_hash (fp_hash),
            INDEX idx_last_seen (last_seen)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get license devices table SQL
     *
     * Links devices to licenses with approval status
     *
     * @since 1.0.0
     * @return string
     */
    private function get_license_devices_sql() {
        $table_name = $this->table_prefix . 'vd_license_devices';

        return "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT UNSIGNED NOT NULL,
            device_fp CHAR(64) NOT NULL,

            status ENUM('pending','approved','blocked') DEFAULT 'pending',
            approved_by BIGINT UNSIGNED NULL,
            approved_at DATETIME NULL,
            last_used_at DATETIME NULL,

            notes TEXT NULL,

            UNIQUE KEY uq_license_device (license_id, device_fp),
            INDEX idx_license_status (license_id, status),
            INDEX idx_device_fp (device_fp),
            INDEX idx_status (status),
            INDEX idx_last_used (last_used_at)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get license device limits table SQL
     *
     * Stores device limits and rotation cooldown per license
     *
     * @since 1.0.0
     * @return string
     */
    private function get_license_device_limits_sql() {
        $table_name = $this->table_prefix . 'vd_license_device_limits';

        return "CREATE TABLE $table_name (
            license_id BIGINT UNSIGNED PRIMARY KEY,

            max_devices INT UNSIGNED NOT NULL DEFAULT 1,
            rotation_cooldown_seconds INT UNSIGNED DEFAULT 86400,

            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_updated_at (updated_at)
        ) ENGINE=InnoDB $this->charset_collate;";
    }

    /**
     * Get table names for device tables
     *
     * @since 1.0.0
     * @return array Array of device table names
     */
    public function get_device_table_names() {
        return array(
            'device_fingerprints' => $this->table_prefix . 'vd_device_fingerprints',
            'license_devices' => $this->table_prefix . 'vd_license_devices',
            'license_device_limits' => $this->table_prefix . 'vd_license_device_limits'
        );
    }

    /**
     * Check if device tables exist
     *
     * @since 1.0.0
     * @return bool True if all device tables exist
     */
    public function device_tables_exist() {
        global $wpdb;

        $tables = $this->get_device_table_names();

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
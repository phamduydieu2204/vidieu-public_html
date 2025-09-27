<?php
/**
 * VD License Manager - Database Manager
 *
 * Handles database schema and table management
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * VD_Database_Manager class
 *
 * Step 2.2: Schema definitions only - NO table creation yet
 */
class VD_Database_Manager {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Private constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get table schemas - Step 2.2: DEFINITIONS ONLY
     *
     * NOTE: This method only returns schema definitions.
     * NO tables are created in this step.
     */
    public static function get_table_schemas($charset_collate = '') {
        global $wpdb;

        if (empty($charset_collate)) {
            $charset_collate = $wpdb->get_charset_collate();
        }

        $schemas = array();

        // Table 1: Licenses (Core license data)
        $schemas['bz_licenses'] = "CREATE TABLE {$wpdb->prefix}bz_licenses (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            owner_name varchar(255) DEFAULT '',
            owner_email varchar(255) DEFAULT '',
            status enum('active','inactive','expired','suspended') DEFAULT 'active',
            device_limit int(11) DEFAULT 3,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY product_id (product_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // Table 2: Provider Accounts (Encrypted credentials)
        $schemas['bz_provider_accounts'] = "CREATE TABLE {$wpdb->prefix}bz_provider_accounts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            account_name varchar(255) NOT NULL,
            email text NOT NULL,
            password text NOT NULL,
            cookies longtext DEFAULT NULL,
            two_factor_secret text DEFAULT NULL,
            account_status enum('active','inactive','suspended','banned') DEFAULT 'active',
            health_score decimal(5,2) DEFAULT 100.00,
            last_health_check datetime DEFAULT NULL,
            total_assignments int(11) DEFAULT 0,
            active_assignments int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY account_status (account_status),
            KEY health_score (health_score)
        ) $charset_collate;";

        // Table 3: Content Versions (Cookie/content versioning)
        $schemas['bz_content_versions'] = "CREATE TABLE {$wpdb->prefix}bz_content_versions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider_account_id bigint(20) unsigned NOT NULL,
            content_type enum('cookies','profile_data','session_data') NOT NULL,
            content_data longtext NOT NULL,
            version_number int(11) DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY provider_account_id (provider_account_id),
            KEY content_type (content_type),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Table 4: License Assignments (License to provider mappings)
        $schemas['bz_license_assignments'] = "CREATE TABLE {$wpdb->prefix}bz_license_assignments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            provider_account_id bigint(20) unsigned NOT NULL,
            assignment_method enum('automatic','manual','sticky') DEFAULT 'automatic',
            status enum('active','inactive','terminated') DEFAULT 'active',
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_used_at datetime DEFAULT NULL,
            usage_count int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY unique_active_assignment (license_id, status),
            KEY provider_account_id (provider_account_id),
            KEY assignment_method (assignment_method),
            KEY status (status)
        ) $charset_collate;";

        // Additional tables will be added in later steps...
        // This is Step 2.2 - keeping it minimal for now

        return $schemas;
    }

    /**
     * Get table list - Step 2.2: Information only
     */
    public static function get_table_list() {
        $tables = array(
            'bz_licenses' => 'Core license data',
            'bz_provider_accounts' => 'Provider account credentials (encrypted)',
            'bz_content_versions' => 'Cookie and content versioning',
            'bz_license_assignments' => 'License to provider mappings',
            // More tables will be added in Step 2.5
            'bz_product_settings' => 'Product configurations (pending)',
            'bz_product_provider_mapping' => 'Product-provider relationships (pending)',
            'bz_product_field_sharing_config' => 'Field sharing config (pending)',
            'bz_device_requests' => 'Device registration (pending)',
            'bz_access_logs' => 'Access logging (pending)',
            'bz_credential_audit' => 'Credential audit trail (pending)',
            'bz_rate_limits' => 'Rate limiting (pending)'
        );

        return $tables;
    }

    /**
     * Check if table exists - Utility method
     */
    public static function table_exists($table_name) {
        global $wpdb;

        $full_table_name = $wpdb->prefix . $table_name;
        $result = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");

        return $result === $full_table_name;
    }

    /**
     * Get database statistics - Information only
     */
    public static function get_database_info() {
        global $wpdb;

        $info = array(
            'database_name' => DB_NAME,
            'table_prefix' => $wpdb->prefix,
            'charset_collate' => $wpdb->get_charset_collate(),
            'mysql_version' => $wpdb->get_var('SELECT VERSION()'),
            'defined_tables' => count(self::get_table_list()),
            'existing_tables' => 0
        );

        // Count existing tables
        $table_list = self::get_table_list();
        foreach ($table_list as $table => $description) {
            if (self::table_exists($table)) {
                $info['existing_tables']++;
            }
        }

        return $info;
    }

    /**
     * Validate database requirements
     */
    public static function validate_database_requirements() {
        global $wpdb;

        $requirements = array(
            'mysql_version' => array(
                'required' => '5.7.0',
                'current' => $wpdb->get_var('SELECT VERSION()'),
                'met' => false
            ),
            'charset_support' => array(
                'required' => 'utf8mb4',
                'current' => $wpdb->charset,
                'met' => false
            ),
            'innodb_support' => array(
                'required' => true,
                'current' => false,
                'met' => false
            )
        );

        // Check MySQL version
        $current_version = $requirements['mysql_version']['current'];
        $requirements['mysql_version']['met'] = version_compare($current_version, '5.7.0', '>=');

        // Check charset
        $requirements['charset_support']['met'] = ($wpdb->charset === 'utf8mb4');

        // Check InnoDB support
        $innodb_check = $wpdb->get_var("SHOW ENGINES");
        $requirements['innodb_support']['current'] = (strpos($innodb_check, 'InnoDB') !== false);
        $requirements['innodb_support']['met'] = $requirements['innodb_support']['current'];

        return $requirements;
    }

    /**
     * Step 2.2: NO TABLE CREATION - Just preparation
     *
     * This method will be implemented in Step 2.3
     */
    public static function create_tables() {
        // STEP 2.2: This method is NOT implemented yet
        // Will be added in Step 2.3 - Single Table Creation

        return array(
            'success' => false,
            'message' => 'Table creation not implemented in Step 2.2',
            'step' => '2.2 - Schema definitions only',
            'next_step' => '2.3 - Single table creation'
        );
    }
}
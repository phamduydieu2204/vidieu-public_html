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
        $schemas['vd_licenses'] = "CREATE TABLE {$wpdb->prefix}vd_licenses (
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
        $schemas['vd_provider_accounts'] = "CREATE TABLE {$wpdb->prefix}vd_provider_accounts (
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
        $schemas['vd_content_versions'] = "CREATE TABLE {$wpdb->prefix}vd_content_versions (
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
        $schemas['vd_license_assignments'] = "CREATE TABLE {$wpdb->prefix}vd_license_assignments (
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

        // Step 2.5: Add remaining tables

        // Table 5: Product Settings (Product configurations)
        $schemas['vd_product_settings'] = "CREATE TABLE {$wpdb->prefix}vd_product_settings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            setting_key varchar(255) NOT NULL,
            setting_value longtext NOT NULL,
            setting_type enum('string','number','boolean','json','encrypted') DEFAULT 'string',
            is_encrypted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_product_setting (product_id, setting_key),
            KEY product_id (product_id),
            KEY setting_key (setting_key)
        ) $charset_collate;";

        // Table 6: Product Provider Mapping (Which providers serve which products)
        $schemas['vd_product_provider_mapping'] = "CREATE TABLE {$wpdb->prefix}vd_product_provider_mapping (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            provider_account_id bigint(20) unsigned NOT NULL,
            priority int(11) DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            max_assignments int(11) DEFAULT 100,
            current_assignments int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_product_provider (product_id, provider_account_id),
            KEY product_id (product_id),
            KEY provider_account_id (provider_account_id),
            KEY is_active (is_active),
            KEY priority (priority)
        ) $charset_collate;";

        // Table 7: Product Field Sharing Config (Which fields to share for each product)
        $schemas['vd_product_field_sharing_config'] = "CREATE TABLE {$wpdb->prefix}vd_product_field_sharing_config (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            field_name varchar(255) NOT NULL,
            share_type enum('copy_only','editable','hidden') DEFAULT 'copy_only',
            field_label varchar(255) DEFAULT '',
            field_description text DEFAULT '',
            display_order int(11) DEFAULT 0,
            is_required tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_product_field (product_id, field_name),
            KEY product_id (product_id),
            KEY share_type (share_type),
            KEY display_order (display_order)
        ) $charset_collate;";

        // Table 8: Device Requests (Device registration and approval)
        $schemas['vd_device_requests'] = "CREATE TABLE {$wpdb->prefix}vd_device_requests (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            device_name varchar(255) NOT NULL,
            device_fingerprint text NOT NULL,
            device_info longtext DEFAULT NULL,
            request_ip varchar(45) NOT NULL,
            user_agent text DEFAULT '',
            status enum('pending','approved','rejected','auto_approved') DEFAULT 'pending',
            approval_threshold decimal(5,2) DEFAULT NULL,
            rejection_reason text DEFAULT '',
            approved_by varchar(255) DEFAULT '',
            approved_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_license_device (license_id, device_fingerprint),
            KEY license_id (license_id),
            KEY status (status),
            KEY request_ip (request_ip),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Table 9: Access Logs (API access and usage tracking)
        $schemas['vd_access_logs'] = "CREATE TABLE {$wpdb->prefix}vd_access_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned DEFAULT NULL,
            device_request_id bigint(20) unsigned DEFAULT NULL,
            provider_account_id bigint(20) unsigned DEFAULT NULL,
            action_type enum('validation','data_access','device_register','login_attempt','api_call') NOT NULL,
            action_details longtext DEFAULT NULL,
            request_ip varchar(45) NOT NULL,
            user_agent text DEFAULT '',
            response_status enum('success','error','denied','rate_limited') NOT NULL,
            response_data longtext DEFAULT NULL,
            processing_time_ms int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY device_request_id (device_request_id),
            KEY provider_account_id (provider_account_id),
            KEY action_type (action_type),
            KEY response_status (response_status),
            KEY request_ip (request_ip),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Table 10: Credential Audit (Security audit trail for sensitive operations)
        $schemas['vd_credential_audit'] = "CREATE TABLE {$wpdb->prefix}vd_credential_audit (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider_account_id bigint(20) unsigned NOT NULL,
            audit_type enum('access','update','encryption','decryption','health_check','assignment') NOT NULL,
            performed_by varchar(255) NOT NULL,
            action_description text NOT NULL,
            old_data_hash varchar(255) DEFAULT NULL,
            new_data_hash varchar(255) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT '',
            severity enum('low','medium','high','critical') DEFAULT 'medium',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider_account_id (provider_account_id),
            KEY audit_type (audit_type),
            KEY performed_by (performed_by),
            KEY severity (severity),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Table 11: Rate Limits (API rate limiting and throttling)
        $schemas['vd_rate_limits'] = "CREATE TABLE {$wpdb->prefix}vd_rate_limits (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            identifier varchar(255) NOT NULL,
            identifier_type enum('license_key','ip_address','device_fingerprint','user_agent') NOT NULL,
            action_type varchar(100) NOT NULL,
            request_count int(11) DEFAULT 1,
            window_start datetime NOT NULL,
            window_end datetime NOT NULL,
            limit_exceeded tinyint(1) DEFAULT 0,
            last_request_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_rate_limit (identifier, action_type, window_start),
            KEY identifier (identifier),
            KEY identifier_type (identifier_type),
            KEY action_type (action_type),
            KEY window_start (window_start),
            KEY window_end (window_end),
            KEY limit_exceeded (limit_exceeded)
        ) $charset_collate;";

        // Additional system tables used by migration manager

        // Table 12: Providers (Provider configurations)
        $schemas['vd_providers'] = "CREATE TABLE {$wpdb->prefix}vd_providers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type enum('lmfwc','api','manual') DEFAULT 'api',
            description text DEFAULT '',
            status enum('active','inactive','suspended') DEFAULT 'active',
            priority int(11) DEFAULT 1,
            settings longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name),
            KEY type (type),
            KEY status (status),
            KEY priority (priority)
        ) $charset_collate;";

        // Table 13: System Configuration (System settings storage)
        $schemas['vd_system_config'] = "CREATE TABLE {$wpdb->prefix}vd_system_config (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            config_key varchar(255) NOT NULL,
            config_value longtext NOT NULL,
            description text DEFAULT '',
            is_encrypted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY config_key (config_key),
            KEY is_encrypted (is_encrypted)
        ) $charset_collate;";

        // Table 14: Cache Data (Caching system)
        $schemas['vd_cache_data'] = "CREATE TABLE {$wpdb->prefix}vd_cache_data (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            cache_value longtext NOT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // Table 15: Audit Logs (General audit trail - different from credential_audit)
        $schemas['vd_audit_logs'] = "CREATE TABLE {$wpdb->prefix}vd_audit_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            entity_type varchar(100) NOT NULL,
            entity_id bigint(20) unsigned NOT NULL,
            action varchar(100) NOT NULL,
            user_id bigint(20) unsigned DEFAULT 0,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT '',
            details text DEFAULT '',
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY action (action),
            KEY user_id (user_id),
            KEY ip_address (ip_address),
            KEY created_at (created_at)
        ) $charset_collate;";

        return $schemas;
    }

    /**
     * Get table list - Step 2.2: Information only
     */
    public static function get_table_list() {
        $tables = array(
            // Core tables (Step 2.3)
            'vd_licenses' => 'Core license data',
            'vd_provider_accounts' => 'Provider account credentials (encrypted)',
            'vd_content_versions' => 'Cookie and content versioning',
            'vd_license_assignments' => 'License to provider mappings',

            // Additional tables (Step 2.5)
            'vd_product_settings' => 'Product configurations and settings',
            'vd_product_provider_mapping' => 'Product-provider relationships',
            'vd_product_field_sharing_config' => 'Field sharing configuration',
            'vd_device_requests' => 'Device registration and approval',
            'vd_access_logs' => 'API access and usage tracking',
            'vd_credential_audit' => 'Security audit trail',
            'vd_rate_limits' => 'Rate limiting and throttling',

            // System tables
            'vd_providers' => 'Provider configurations',
            'vd_system_config' => 'System configuration settings',
            'vd_cache_data' => 'System cache storage',
            'vd_audit_logs' => 'General audit trail'
        );

        return $tables;
    }

    /**
     * Check if table exists - Utility method
     */
    public static function table_exists($table_name) {
        global $wpdb;

        // Table name should now be in format vd_* (without bz_ prefix)
        // $wpdb->prefix already contains bz_ so final table name will be bz_vd_*
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
     * Step 2.5: Create all tables - Full schema creation
     *
     * IMPORTANT: Creates ALL 11 tables for the complete system
     */
    public static function create_tables() {
        global $wpdb;

        // Step 2.5: Create all tables
        $result = array(
            'success' => false,
            'step' => '2.5 - Full schema creation',
            'tables' => array(),
            'errors' => array(),
            'dbdelta_output' => array()
        );

        try {
            // Get charset for this installation
            $charset_collate = $wpdb->get_charset_collate();

            // Get all schemas
            $schemas = self::get_table_schemas($charset_collate);

            // Use WordPress dbDelta for table creation
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            error_log("[VD License Manager] Step 2.5 - Starting full schema creation with " . count($schemas) . " tables");

            $created_count = 0;
            $failed_count = 0;

            // Create all tables
            foreach ($schemas as $table_name => $sql) {
                error_log("[VD License Manager] Step 2.5 - Creating table: $table_name");

                try {
                    // Execute dbDelta for this table
                    $dbdelta_result = dbDelta($sql);
                    $result['dbdelta_output'][$table_name] = $dbdelta_result;

                    // Check if table was created successfully
                    if (self::table_exists($table_name)) {
                        $result['tables'][$table_name] = true;
                        $created_count++;
                        error_log("[VD License Manager] Step 2.5 - Table $table_name created successfully");
                    } else {
                        $result['tables'][$table_name] = false;
                        $failed_count++;
                        $error_msg = "Failed to create table $table_name";
                        $result['errors'][] = $error_msg;
                        error_log("[VD License Manager] Step 2.5 - $error_msg");
                    }

                } catch (Exception $table_exception) {
                    $result['tables'][$table_name] = false;
                    $failed_count++;
                    $error_msg = "Exception creating table $table_name: " . $table_exception->getMessage();
                    $result['errors'][] = $error_msg;
                    error_log("[VD License Manager] Step 2.5 - $error_msg");
                }
            }

            // Determine overall success
            if ($created_count > 0 && $failed_count === 0) {
                $result['success'] = true;
                error_log("[VD License Manager] Step 2.5 - All $created_count tables created successfully");
            } elseif ($created_count > 0) {
                $result['success'] = false; // Partial success is still failure for step completion
                error_log("[VD License Manager] Step 2.5 - Partial success: $created_count created, $failed_count failed");
            } else {
                $result['success'] = false;
                error_log("[VD License Manager] Step 2.5 - Complete failure: no tables created");
            }

            $result['summary'] = array(
                'total_tables' => count($schemas),
                'created' => $created_count,
                'failed' => $failed_count
            );

        } catch (Exception $e) {
            $result['errors'][] = 'Exception: ' . $e->getMessage();
            error_log("[VD License Manager] Step 2.5 - Exception: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Verify foreign key relationships and table integrity
     *
     * @return array Verification results
     */
    public static function verify_table_relationships() {
        global $wpdb;

        $verification = array(
            'success' => true,
            'checks' => array(),
            'errors' => array(),
            'warnings' => array()
        );

        // Check if all expected tables exist
        $expected_tables = array_keys(self::get_table_list());
        $missing_tables = array();

        foreach ($expected_tables as $table) {
            if (!self::table_exists($table)) {
                $missing_tables[] = $table;
                $verification['success'] = false;
            }
        }

        if (!empty($missing_tables)) {
            $verification['errors'][] = 'Missing tables: ' . implode(', ', $missing_tables);
        } else {
            $verification['checks'][] = 'All expected tables exist';
        }

        // Check foreign key relationships (logical relationships since MySQL FK constraints aren't used)
        $relationships = array(
            'vd_license_assignments' => array(
                'license_id' => 'vd_licenses.id',
                'provider_account_id' => 'vd_provider_accounts.id'
            ),
            'vd_content_versions' => array(
                'provider_account_id' => 'vd_provider_accounts.id'
            ),
            'vd_product_settings' => array(),
            'vd_product_provider_mapping' => array(
                'provider_account_id' => 'vd_provider_accounts.id'
            ),
            'vd_product_field_sharing_config' => array(),
            'vd_device_requests' => array(
                'license_id' => 'vd_licenses.id'
            ),
            'vd_access_logs' => array(
                'license_id' => 'vd_licenses.id',
                'device_request_id' => 'vd_device_requests.id',
                'provider_account_id' => 'vd_provider_accounts.id'
            ),
            'vd_credential_audit' => array(
                'provider_account_id' => 'vd_provider_accounts.id'
            ),
            'vd_rate_limits' => array()
        );

        // For now, just verify table structures exist (actual FK constraint checking would be complex)
        foreach ($relationships as $table => $fk_fields) {
            if (!self::table_exists($table)) {
                continue; // Already reported as missing
            }

            // Check if table has expected columns
            // Table name is in format vd_* and $wpdb->prefix is bz_
            $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}{$table}");
            $column_names = array();
            foreach ($columns as $column) {
                $column_names[] = $column->Field;
            }

            foreach ($fk_fields as $field => $references) {
                if (!in_array($field, $column_names)) {
                    $verification['errors'][] = "Table $table missing foreign key field: $field";
                    $verification['success'] = false;
                } else {
                    $verification['checks'][] = "Table $table has foreign key field: $field";
                }
            }
        }

        // Check basic table structure integrity
        foreach ($expected_tables as $table) {
            if (!self::table_exists($table)) {
                continue;
            }

            // Table name is in format vd_* and $wpdb->prefix is bz_
            $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}{$table}");
            if (empty($columns)) {
                $verification['errors'][] = "Table $table exists but has no columns";
                $verification['success'] = false;
            } else {
                // Check for required fields that should exist in all tables
                $column_names = array_column($columns, 'Field');

                if (!in_array('id', $column_names)) {
                    $verification['errors'][] = "Table $table missing primary key 'id'";
                    $verification['success'] = false;
                }

                if (!in_array('created_at', $column_names)) {
                    $verification['warnings'][] = "Table $table missing 'created_at' timestamp";
                }

                $verification['checks'][] = "Table $table structure verified (" . count($columns) . " columns)";
            }
        }

        // Log verification results
        if ($verification['success']) {
            error_log('[VD License Manager] Step 2.5 - Table relationship verification PASSED');
        } else {
            error_log('[VD License Manager] Step 2.5 - Table relationship verification FAILED: ' .
                     implode(', ', $verification['errors']));
        }

        return $verification;
    }
}
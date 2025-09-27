<?php
/**
 * VD License Manager Database Manager
 *
 * Handles database table creation, migrations, and basic database operations
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Database_Manager class
 *
 * Manages all database operations for VD License Manager
 */
class VD_Database_Manager {

    /**
     * Database version
     *
     * @var string
     */
    private static $db_version = '1.0.0';

    /**
     * WordPress database object
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Table prefix
     *
     * @var string
     */
    private $table_prefix;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix;
    }

    /**
     * Create all database tables
     *
     * @since 1.0.0
     * @return array Results of table creation
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_schemas = self::get_table_schemas($charset_collate);

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $results = [];

        // Log start of table creation
        vd_debug_log('Starting database table creation for VD License Manager');

        foreach ($table_schemas as $table_name => $sql) {
            vd_debug_log("Creating table: {$table_name}");

            try {
                $result = dbDelta($sql);
                $results[$table_name] = [
                    'success' => true,
                    'result' => $result,
                    'sql' => $sql
                ];

                vd_debug_log("Successfully created table: {$table_name}");

                // Verify table was created
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vd_{$table_name}'");
                if (!$table_exists) {
                    vd_debug_log("WARNING: Table {$table_name} creation reported success but table not found");
                    $results[$table_name]['success'] = false;
                    $results[$table_name]['error'] = 'Table not found after creation';
                }

            } catch (Exception $e) {
                $results[$table_name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'sql' => $sql
                ];

                vd_debug_log("ERROR creating table {$table_name}: " . $e->getMessage());
            }
        }

        // Create indexes after all tables are created
        self::create_indexes();

        // Update database version
        update_option('vd_license_manager_db_version', self::$db_version);
        update_option('vd_license_manager_db_created', current_time('mysql'));

        // Remove the flag that indicates tables need to be created
        delete_option('vd_license_manager_needs_db_creation');

        vd_debug_log('Completed database table creation for VD License Manager');

        return $results;
    }

    /**
     * Get all table schemas with bz_ prefix
     *
     * @since 1.0.0
     * @param string $charset_collate WordPress charset collation
     * @return array Array of table schemas
     */
    private static function get_table_schemas($charset_collate) {
        global $wpdb;

        $tables = [];

        // 1. Core Licenses Table
        $tables['licenses'] = "CREATE TABLE {$wpdb->prefix}vd_licenses (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_key varchar(64) NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned NULL,
            user_id bigint(20) unsigned NULL,
            status enum('active','expired','suspended','revoked') NOT NULL DEFAULT 'active',
            max_devices int unsigned NULL,
            expires_at datetime NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_license_key (license_key),
            KEY idx_product_status (product_id, status),
            KEY idx_user_licenses (user_id, status),
            KEY idx_expires (expires_at, status),
            KEY idx_created (created_at)
        ) $charset_collate;";

        // 2. Provider Accounts Table
        $tables['provider_accounts'] = "CREATE TABLE {$wpdb->prefix}vd_provider_accounts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider enum('helium10','midjourney','freepik') NOT NULL,
            account_name varchar(255) NOT NULL,
            share_type enum('cookie','credentials','credentials_2fa') NOT NULL,
            capacity int unsigned NOT NULL DEFAULT 10,
            current_load int unsigned NOT NULL DEFAULT 0,
            status enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_provider_status (provider, status),
            KEY idx_capacity_load (capacity, current_load),
            KEY idx_share_type (share_type)
        ) $charset_collate;";

        // 3. Content Versions Table (for cookies/credentials)
        $tables['content_versions'] = "CREATE TABLE {$wpdb->prefix}vd_content_versions (
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
            KEY idx_content_type (content_type),
            KEY idx_expires (expires_at)
        ) $charset_collate;";

        // 4. License Assignments Table
        $tables['license_assignments'] = "CREATE TABLE {$wpdb->prefix}vd_license_assignments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            provider_account_id bigint(20) unsigned NOT NULL,
            assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_accessed datetime NULL,
            status enum('active','migrating','inactive') NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_license_assignment (license_id),
            KEY idx_account_load (provider_account_id, status),
            KEY idx_last_accessed (last_accessed)
        ) $charset_collate;";

        // 5. Product Settings Table
        $tables['product_settings'] = "CREATE TABLE {$wpdb->prefix}vd_product_settings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            provider enum('helium10','midjourney','freepik') NOT NULL,
            share_type enum('cookie','credentials','credentials_2fa') NOT NULL,
            max_devices int unsigned NOT NULL DEFAULT 3,
            auto_approval_threshold decimal(5,2) NOT NULL DEFAULT 25.00,
            assignment_algorithm enum('least_loaded','round_robin','sequential','random') NOT NULL DEFAULT 'least_loaded',
            rate_limit_per_hour int unsigned NOT NULL DEFAULT 60,
            rate_limit_per_day int unsigned NOT NULL DEFAULT 1000,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_product_provider (product_id, provider),
            KEY idx_provider_active (provider, is_active),
            KEY idx_product_active (product_id, is_active)
        ) $charset_collate;";

        // 6. Product Provider Mapping Table
        $tables['product_provider_mapping'] = "CREATE TABLE {$wpdb->prefix}vd_product_provider_mapping (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            provider_account_id bigint(20) unsigned NOT NULL,
            allocation_strategy enum('round_robin','least_loaded','sequential') NOT NULL DEFAULT 'least_loaded',
            priority int NOT NULL DEFAULT 1,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_product_provider_priority (product_id, provider_account_id, priority),
            KEY idx_product_active (product_id, is_active),
            KEY idx_priority (priority),
            KEY idx_strategy (allocation_strategy)
        ) $charset_collate;";

        // 7. Product Field Sharing Config Table
        $tables['product_field_sharing_config'] = "CREATE TABLE {$wpdb->prefix}vd_product_field_sharing_config (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            field_key varchar(100) NOT NULL,
            field_label varchar(255) NOT NULL,
            field_type enum('text','password','email','url','textarea') NOT NULL DEFAULT 'text',
            is_shared tinyint(1) NOT NULL DEFAULT 0,
            display_order int NOT NULL DEFAULT 0,
            is_required tinyint(1) NOT NULL DEFAULT 0,
            field_description text NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_product_field (product_id, field_key),
            KEY idx_product_shared (product_id, is_shared),
            KEY idx_display_order (display_order)
        ) $charset_collate;";

        // 8. Device Requests Table
        $tables['device_requests'] = "CREATE TABLE {$wpdb->prefix}vd_device_requests (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            device_fp varchar(64) NOT NULL,
            device_info json NOT NULL,
            risk_score decimal(5,2) NOT NULL DEFAULT 0.00,
            auto_approved tinyint(1) NOT NULL DEFAULT 0,
            status enum('pending','approved','blocked','over_limit') NOT NULL DEFAULT 'pending',
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            country_code varchar(2) NULL,
            first_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            approved_at datetime NULL,
            approved_by bigint(20) unsigned NULL,
            notes text NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_license_device (license_id, device_fp),
            KEY idx_status (status),
            KEY idx_auto_approved (auto_approved),
            KEY idx_risk_score (risk_score),
            KEY idx_ip_address (ip_address),
            KEY idx_first_seen (first_seen)
        ) $charset_collate;";

        // 9. Access Logs Table
        $tables['access_logs'] = "CREATE TABLE {$wpdb->prefix}vd_access_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            device_fp varchar(64) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            endpoint varchar(255) NOT NULL,
            response_status int unsigned NOT NULL,
            response_time decimal(8,3) NOT NULL DEFAULT 0.000,
            request_data json NULL,
            response_data json NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_license_device (license_id, device_fp),
            KEY idx_ip_address (ip_address),
            KEY idx_endpoint (endpoint),
            KEY idx_response_status (response_status),
            KEY idx_created (created_at)
        ) $charset_collate;";

        // 10. Credential Audit Table
        $tables['credential_audit'] = "CREATE TABLE {$wpdb->prefix}vd_credential_audit (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            action enum('reveal','hide','copy','update','delete') NOT NULL,
            object_type enum('provider_account','license','content_version') NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            details json NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_action_object (action, object_type, object_id),
            KEY idx_user_action (user_id, action),
            KEY idx_ip_address (ip_address),
            KEY idx_created (created_at)
        ) $charset_collate;";

        // 11. Rate Limits Table
        $tables['rate_limits'] = "CREATE TABLE {$wpdb->prefix}vd_rate_limits (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            identifier varchar(255) NOT NULL,
            identifier_type enum('ip','license','user','device') NOT NULL,
            requests int unsigned NOT NULL DEFAULT 1,
            window_start datetime NOT NULL,
            window_end datetime NOT NULL,
            limit_type enum('hourly','daily','burst') NOT NULL DEFAULT 'hourly',
            is_blocked tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_identifier_type (identifier, identifier_type),
            KEY idx_window (window_start, window_end),
            KEY idx_limit_type (limit_type),
            KEY idx_blocked (is_blocked),
            KEY idx_created (created_at)
        ) $charset_collate;";

        return $tables;
    }

    /**
     * Create database indexes for performance
     *
     * @since 1.0.0
     */
    private static function create_indexes() {
        global $wpdb;

        vd_debug_log('Creating additional database indexes');

        $indexes = [
            // Composite indexes for common queries
            "CREATE INDEX idx_vd_licenses_product_user_status ON {$wpdb->prefix}vd_licenses (product_id, user_id, status)",
            "CREATE INDEX idx_vd_assignments_account_status_accessed ON {$wpdb->prefix}vd_license_assignments (provider_account_id, status, last_accessed)",
            "CREATE INDEX idx_vd_devices_license_status_risk ON {$wpdb->prefix}vd_device_requests (license_id, status, risk_score)",
            "CREATE INDEX idx_vd_access_logs_license_created ON {$wpdb->prefix}vd_access_logs (license_id, created_at)",
            "CREATE INDEX idx_vd_rate_limits_identifier_window ON {$wpdb->prefix}vd_rate_limits (identifier, window_start, window_end)"
        ];

        foreach ($indexes as $index_sql) {
            try {
                $wpdb->query($index_sql);
                vd_debug_log("Created index: " . substr($index_sql, 0, 100) . "...");
            } catch (Exception $e) {
                vd_debug_log("Failed to create index: " . $e->getMessage());
            }
        }
    }

    /**
     * Check if all tables exist
     *
     * @since 1.0.0
     * @return array Status of each table
     */
    public static function check_tables_exist() {
        global $wpdb;

        $table_names = [
            'licenses', 'provider_accounts', 'content_versions', 'license_assignments',
            'product_settings', 'product_provider_mapping', 'product_field_sharing_config',
            'device_requests', 'access_logs', 'credential_audit', 'rate_limits'
        ];

        $results = [];

        foreach ($table_names as $table_name) {
            $full_table_name = $wpdb->prefix . 'vd_' . $table_name;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            $results[$table_name] = !empty($exists);
        }

        return $results;
    }

    /**
     * Get database statistics
     *
     * @since 1.0.0
     * @return array Database statistics
     */
    public static function get_database_stats() {
        global $wpdb;

        $stats = [
            'db_version' => get_option('vd_license_manager_db_version', 'Not set'),
            'created_at' => get_option('vd_license_manager_db_created', 'Not set'),
            'tables' => []
        ];

        $table_names = [
            'licenses', 'provider_accounts', 'content_versions', 'license_assignments',
            'product_settings', 'product_provider_mapping', 'product_field_sharing_config',
            'device_requests', 'access_logs', 'credential_audit', 'rate_limits'
        ];

        foreach ($table_names as $table_name) {
            $full_table_name = $wpdb->prefix . 'vd_' . $table_name;

            // Check if table exists
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");

            if ($exists) {
                // Get row count
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
                $stats['tables'][$table_name] = [
                    'exists' => true,
                    'row_count' => intval($count)
                ];
            } else {
                $stats['tables'][$table_name] = [
                    'exists' => false,
                    'row_count' => 0
                ];
            }
        }

        return $stats;
    }

    /**
     * Drop all VD License Manager tables (for development/testing)
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public static function drop_all_tables() {
        global $wpdb;

        if (!defined('VD_DEBUG_MODE') || !VD_DEBUG_MODE) {
            vd_debug_log('Attempted to drop tables but not in debug mode');
            return false;
        }

        vd_debug_log('Dropping all VD License Manager tables (DEBUG MODE)');

        $table_names = [
            'rate_limits', 'credential_audit', 'access_logs', 'device_requests',
            'product_field_sharing_config', 'product_provider_mapping', 'product_settings',
            'license_assignments', 'content_versions', 'provider_accounts', 'licenses'
        ];

        $success = true;

        foreach ($table_names as $table_name) {
            $full_table_name = $wpdb->prefix . 'vd_' . $table_name;
            $result = $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");

            if ($result === false) {
                vd_debug_log("Failed to drop table: {$full_table_name}");
                $success = false;
            } else {
                vd_debug_log("Dropped table: {$full_table_name}");
            }
        }

        if ($success) {
            delete_option('vd_license_manager_db_version');
            delete_option('vd_license_manager_db_created');
            vd_debug_log('All VD License Manager tables dropped successfully');
        }

        return $success;
    }

    /**
     * Get current database version
     *
     * @since 1.0.0
     * @return string Database version
     */
    public static function get_db_version() {
        return get_option('vd_license_manager_db_version', '0.0.0');
    }

    /**
     * Check if database needs update
     *
     * @since 1.0.0
     * @return bool True if update needed
     */
    public static function needs_update() {
        $current_version = self::get_db_version();
        return version_compare($current_version, self::$db_version, '<');
    }

    /**
     * Update database if needed
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public static function maybe_update_database() {
        if (self::needs_update()) {
            vd_debug_log('Database update needed, running table creation');
            $results = self::create_tables();

            $success_count = 0;
            $total_count = count($results);

            foreach ($results as $table_name => $result) {
                if ($result['success']) {
                    $success_count++;
                }
            }

            vd_debug_log("Database update completed: {$success_count}/{$total_count} tables successful");

            return $success_count === $total_count;
        }

        return true; // No update needed
    }
}
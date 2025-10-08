<?php
/**
 * Database Management
 *
 * Handles database table creation and updates
 *
 * @package VD_License_Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VD_Database {

    /**
     * Create all plugin tables
     *
     * @since 2.0.0
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table 1: Provider Accounts (already exists - skip if exists)
        $table_accounts = $wpdb->prefix . 'vd_provider_accounts';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_accounts'") != $table_accounts) {
            $sql_accounts = "CREATE TABLE $table_accounts (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                provider varchar(50) NOT NULL,
                account_login varchar(255) NOT NULL,
                display_name varchar(255) NOT NULL,
                capacity int(11) NOT NULL DEFAULT 0,
                status varchar(20) NOT NULL DEFAULT 'active',
                cookie text,
                login_email varchar(255) DEFAULT NULL,
                login_password varchar(255) DEFAULT NULL,
                expires_at datetime DEFAULT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY idx_provider (provider),
                KEY idx_status (status),
                KEY idx_expires_at (expires_at)
            ) $charset_collate;";

            dbDelta($sql_accounts);
        }

        // Table 2: Product Pools (already exists - skip if exists)
        $table_pools = $wpdb->prefix . 'vd_product_pools';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_pools'") != $table_pools) {
            $sql_pools = "CREATE TABLE $table_pools (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                product_id bigint(20) unsigned NOT NULL,
                account_id bigint(20) unsigned NOT NULL,
                priority int(11) NOT NULL DEFAULT 0,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY product_account (product_id, account_id),
                KEY idx_product_id (product_id),
                KEY idx_account_id (account_id),
                KEY idx_priority (priority),
                KEY idx_is_active (is_active)
            ) $charset_collate;";

            dbDelta($sql_pools);
        }

        // Table 3: Product Share Configs (NEW - must create)
        $table_configs = $wpdb->prefix . 'vd_product_share_configs';

        $sql_configs = "CREATE TABLE $table_configs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            max_profiles int(11) NOT NULL DEFAULT 1,
            max_devices_per_profile int(11) NOT NULL DEFAULT 1,
            sharing_duration_days int(11) NOT NULL DEFAULT 30,
            auto_rotate tinyint(1) NOT NULL DEFAULT 0,
            rotation_interval_days int(11) DEFAULT NULL,
            allow_concurrent_streams int(11) DEFAULT NULL,
            custom_rules text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id),
            KEY idx_product_id (product_id),
            KEY idx_auto_rotate (auto_rotate)
        ) $charset_collate;";

        dbDelta($sql_configs);

        // Log table creation
        error_log('VD License Manager: Database tables created/updated');
    }

    /**
     * Run on plugin activation
     *
     * @since 2.0.0
     */
    public static function activate() {
        self::create_tables();

        // Update database version
        update_option('vd_license_manager_db_version', VD_VERSION);

        error_log('VD License Manager: Plugin activated, database version set to ' . VD_VERSION);
    }

    /**
     * Check if tables need update
     *
     * @since 2.0.0
     */
    public static function maybe_update() {
        $current_version = get_option('vd_license_manager_db_version', '0');

        if (version_compare($current_version, VD_VERSION, '<')) {
            self::create_tables();
            update_option('vd_license_manager_db_version', VD_VERSION);

            error_log('VD License Manager: Database updated from version ' . $current_version . ' to ' . VD_VERSION);
        }
    }

    /**
     * Check if share configs table exists
     *
     * @since 2.0.0
     * @return bool True if table exists
     */
    public static function share_configs_table_exists() {
        global $wpdb;

        $table_configs = $wpdb->prefix . 'vd_product_share_configs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_configs'") == $table_configs;

        if (!$table_exists) {
            error_log('VD License Manager: Share configs table does not exist');
        }

        return $table_exists;
    }

    /**
     * Force create share configs table
     *
     * @since 2.0.0
     */
    public static function force_create_share_configs_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_configs = $wpdb->prefix . 'vd_product_share_configs';

        $sql_configs = "CREATE TABLE $table_configs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            max_profiles int(11) NOT NULL DEFAULT 1,
            max_devices_per_profile int(11) NOT NULL DEFAULT 1,
            sharing_duration_days int(11) NOT NULL DEFAULT 30,
            auto_rotate tinyint(1) NOT NULL DEFAULT 0,
            rotation_interval_days int(11) DEFAULT NULL,
            allow_concurrent_streams int(11) DEFAULT NULL,
            custom_rules text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id),
            KEY idx_product_id (product_id),
            KEY idx_auto_rotate (auto_rotate)
        ) $charset_collate;";

        dbDelta($sql_configs);

        // Verify creation
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_configs'") == $table_configs;

        if ($table_exists) {
            error_log('VD License Manager: Share configs table created successfully');
            update_option('vd_license_manager_db_version', VD_VERSION);
            return true;
        } else {
            error_log('VD License Manager: Failed to create share configs table');
            return false;
        }
    }

    /**
     * Get database status for debugging
     *
     * @since 2.0.0
     * @return array Database status
     */
    public static function get_database_status() {
        global $wpdb;

        $tables = array(
            'provider_accounts' => $wpdb->prefix . 'vd_provider_accounts',
            'product_pools' => $wpdb->prefix . 'vd_product_pools',
            'product_share_configs' => $wpdb->prefix . 'vd_product_share_configs'
        );

        $status = array();

        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
            $status[$name] = array(
                'table' => $table,
                'exists' => $exists,
                'count' => $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0
            );
        }

        $status['db_version'] = get_option('vd_license_manager_db_version', '0');
        $status['plugin_version'] = VD_VERSION;

        return $status;
    }
}
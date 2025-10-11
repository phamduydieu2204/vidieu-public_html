<?php
/**
 * Database Migration Handler
 *
 * Manages database table creation, updates, and version tracking.
 * Creates all 11 tables required for VD License Manager functionality.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes
 * @since      1.0.0
 * @author     Vidieu Team <admin@vidieu.vn>
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database Migration Handler Class
 *
 * Handles creation, updates, and deletion of all plugin database tables.
 * Implements version tracking to support future schema updates.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Database {

    /**
     * Database schema version
     *
     * Increment this when making schema changes to trigger updates.
     *
     * @since 1.0.0
     */
    const DB_VERSION = '1.0.0';

    /**
     * Option name for storing database version
     *
     * @since 1.0.0
     */
    const VERSION_OPTION = 'vd_lm_db_version';

    /**
     * Create all database tables
     *
     * Creates all 11 tables required for the plugin in the correct dependency order.
     * Uses dbDelta for safe table creation and updates.
     *
     * @since 1.0.0
     * @return void
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Include upgrade functions
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        VD_LM_Logger_Service::info( 'Starting database table creation', array(
            'db_version' => self::DB_VERSION,
            'charset_collate' => $charset_collate,
        ) );

        // Create tables in dependency order to respect foreign key relationships
        self::create_provider_accounts_table( $charset_collate );
        self::create_product_pools_table( $charset_collate );
        self::create_pool_accounts_table( $charset_collate );
        self::create_license_keys_table( $charset_collate );
        self::create_product_share_configs_table( $charset_collate );
        self::create_device_fingerprints_table( $charset_collate );
        self::create_license_devices_table( $charset_collate );
        self::create_license_device_limits_table( $charset_collate );
        self::create_account_fetch_log_table( $charset_collate );
        self::create_license_access_log_table( $charset_collate );
        self::create_license_rate_limits_table( $charset_collate );

        // Update database version
        update_option( self::VERSION_OPTION, self::DB_VERSION );

        VD_LM_Logger_Service::info( 'Database tables created successfully', array(
            'tables_created' => 11,
            'db_version' => self::DB_VERSION,
        ) );
    }

    /**
     * Check if tables need creation or update
     *
     * Compares the installed database version with the current version
     * and triggers table creation/update if needed.
     *
     * @since 1.0.0
     * @return bool True if tables were updated, false if no update needed
     */
    public static function maybe_update_tables() {
        $installed_version = get_option( self::VERSION_OPTION, '0.0.0' );

        if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
            VD_LM_Logger_Service::info( 'Database update required', array(
                'installed_version' => $installed_version,
                'required_version' => self::DB_VERSION,
            ) );

            self::create_tables();
            return true;
        }

        return false;
    }

    /**
     * Migrate provider accounts table to new schema
     *
     * Adds missing columns to existing installations.
     *
     * @since 1.0.0
     * @return bool True if migration was successful
     */
    public static function migrate_provider_accounts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_provider_accounts';

        // Check if table exists
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ) );

        if ( ! $table_exists ) {
            VD_LM_Logger_Service::info( 'Provider accounts table does not exist, skipping migration' );
            return false;
        }

        // Define columns to add/modify
        $columns_to_add = array(
            'account_password' => "ADD COLUMN account_password TEXT NULL COMMENT 'Login password (encrypted)' AFTER display_name",
            'cookies' => "ADD COLUMN cookies LONGTEXT NULL COMMENT 'Session cookies (encrypted)' AFTER expires_at",
            'phone_recovery' => "ADD COLUMN phone_recovery VARCHAR(50) NULL COMMENT 'Recovery phone number (encrypted)' AFTER cookies",
            'email_recovery' => "ADD COLUMN email_recovery VARCHAR(255) NULL COMMENT 'Recovery email address (encrypted)' AFTER phone_recovery",
            'secret_key' => "ADD COLUMN secret_key TEXT NULL COMMENT 'API secret key (encrypted)' AFTER api_key",
            'current_usage' => "ADD COLUMN current_usage INT(11) NOT NULL DEFAULT 0 COMMENT 'Current number of active licenses' AFTER custom_fields",
            'last_credentials_update' => "ADD COLUMN last_credentials_update DATETIME NULL COMMENT 'Last time any credential was changed' AFTER current_usage",
            'next_update_due' => "ADD COLUMN next_update_due DATETIME NULL COMMENT 'When credentials need to be updated next' AFTER last_credentials_update",
            'notes' => "ADD COLUMN notes TEXT NULL COMMENT 'Internal admin notes (not encrypted)' AFTER next_update_due",
        );

        // Columns to modify
        $columns_to_modify = array(
            'display_name' => "MODIFY COLUMN display_name VARCHAR(255) NULL COMMENT 'Admin display name for easy identification'",
            'account_login' => "MODIFY COLUMN account_login VARCHAR(255) NOT NULL COMMENT 'Login username or email'",
            'expires_at' => "MODIFY COLUMN expires_at DATETIME NULL COMMENT 'Provider account expiration date'",
            'status' => "MODIFY COLUMN status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active' COMMENT 'Account status'",
            'api_key' => "MODIFY COLUMN api_key TEXT NULL COMMENT 'API key for provider services (encrypted)'",
            'api_token' => "MODIFY COLUMN api_token LONGTEXT NULL COMMENT 'API access token (encrypted)'",
            'two_factor_secret' => "MODIFY COLUMN two_factor_secret TEXT NULL COMMENT '2FA TOTP secret (encrypted)'",
        );

        // Rename columns if needed
        $columns_to_rename = array(
            'login_password' => "CHANGE COLUMN login_password account_password TEXT NULL COMMENT 'Login password (encrypted)'",
            'cookie' => "CHANGE COLUMN cookie cookies LONGTEXT NULL COMMENT 'Session cookies (encrypted)'",
            'account_fields' => "CHANGE COLUMN account_fields custom_fields LONGTEXT NULL COMMENT 'Additional custom fields as JSON (encrypted)'",
        );

        $migration_errors = array();
        $migration_success = array();

        // Add missing columns
        foreach ( $columns_to_add as $column => $sql ) {
            $column_exists = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                $column
            ) );

            if ( empty( $column_exists ) ) {
                $result = $wpdb->query( "ALTER TABLE {$table_name} {$sql}" );
                if ( $result === false ) {
                    $migration_errors[] = "Failed to add column '{$column}': " . $wpdb->last_error;
                } else {
                    $migration_success[] = "Added column '{$column}'";
                    VD_LM_Logger_Service::info( "Migration: Added column '{$column}' to {$table_name}" );
                }
            }
        }

        // Rename columns
        foreach ( $columns_to_rename as $old_column => $sql ) {
            $column_exists = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                $old_column
            ) );

            if ( ! empty( $column_exists ) ) {
                $result = $wpdb->query( "ALTER TABLE {$table_name} {$sql}" );
                if ( $result === false ) {
                    $migration_errors[] = "Failed to rename column '{$old_column}': " . $wpdb->last_error;
                } else {
                    $migration_success[] = "Renamed column '{$old_column}'";
                    VD_LM_Logger_Service::info( "Migration: Renamed column '{$old_column}' in {$table_name}" );
                }
            }
        }

        // Modify existing columns
        foreach ( $columns_to_modify as $column => $sql ) {
            $result = $wpdb->query( "ALTER TABLE {$table_name} {$sql}" );
            if ( $result === false ) {
                $migration_errors[] = "Failed to modify column '{$column}': " . $wpdb->last_error;
            } else {
                $migration_success[] = "Modified column '{$column}'";
                VD_LM_Logger_Service::info( "Migration: Modified column '{$column}' in {$table_name}" );
            }
        }

        // Add unique constraint if it doesn't exist
        $constraint_exists = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = %s
            AND TABLE_NAME = %s
            AND CONSTRAINT_NAME = 'uk_provider_login'",
            DB_NAME,
            $table_name
        ) );

        if ( empty( $constraint_exists ) ) {
            $result = $wpdb->query( "ALTER TABLE {$table_name} ADD UNIQUE KEY uk_provider_login (provider, account_login)" );
            if ( $result === false ) {
                $migration_errors[] = "Failed to add unique constraint: " . $wpdb->last_error;
            } else {
                $migration_success[] = "Added unique constraint for provider+login";
                VD_LM_Logger_Service::info( "Migration: Added unique constraint to {$table_name}" );
            }
        }

        // Update migration flag
        update_option( 'vd_accounts_migrated_v2', time() );

        // Log migration results
        VD_LM_Logger_Service::info( 'Provider accounts table migration completed', array(
            'success_count' => count( $migration_success ),
            'error_count' => count( $migration_errors ),
            'success_operations' => $migration_success,
            'errors' => $migration_errors,
        ) );

        return empty( $migration_errors );
    }

    /**
     * Drop all plugin tables
     *
     * Removes all plugin tables and related options.
     * Used during plugin uninstallation.
     *
     * @since 1.0.0
     * @return void
     */
    public static function drop_tables() {
        global $wpdb;

        VD_LM_Logger_Service::info( 'Starting database table deletion' );

        // Drop tables in reverse order to respect foreign key dependencies
        $tables = array(
            'vd_license_rate_limits',
            'vd_license_access_log',
            'vd_account_fetch_log',
            'vd_license_device_limits',
            'vd_license_devices',
            'vd_device_fingerprints',
            'vd_product_share_configs',
            'vd_license_keys',
            'vd_pool_accounts',
            'vd_product_pools',
            'vd_provider_accounts',
        );

        foreach ( $tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
            VD_LM_Logger_Service::info( "Table dropped: {$table_name}" );
        }

        // Delete version option
        delete_option( self::VERSION_OPTION );

        VD_LM_Logger_Service::info( 'Database tables dropped successfully', array(
            'tables_dropped' => count( $tables ),
        ) );
    }

    /**
     * Create provider accounts table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_provider_accounts_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_provider_accounts';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider varchar(100) NOT NULL COMMENT 'Provider name: Netflix, Spotify, Helium10, etc.',
            account_login varchar(255) NOT NULL COMMENT 'Login username or email',
            display_name varchar(255) NULL COMMENT 'Admin display name for easy identification',
            account_password text NULL COMMENT 'Login password (encrypted with VD_LM_Encryption_Service)',
            capacity int(11) NOT NULL DEFAULT 1 COMMENT 'Maximum licenses this account can serve',
            status enum('active','inactive','suspended') NOT NULL DEFAULT 'active' COMMENT 'Account status',
            expires_at datetime NULL COMMENT 'Provider account expiration date',
            cookies longtext NULL COMMENT 'Session cookies (encrypted)',
            phone_recovery varchar(50) NULL COMMENT 'Recovery phone number (encrypted)',
            email_recovery varchar(255) NULL COMMENT 'Recovery email address (encrypted)',
            security_question varchar(255) NULL COMMENT 'Security question text',
            security_answer text NULL COMMENT 'Security answer (encrypted)',
            backup_codes text NULL COMMENT 'Backup recovery codes (encrypted)',
            two_factor_secret text NULL COMMENT '2FA TOTP secret (encrypted)',
            api_key text NULL COMMENT 'API key for provider services (encrypted)',
            secret_key text NULL COMMENT 'API secret key (encrypted)',
            api_token longtext NULL COMMENT 'API access token (encrypted)',
            custom_fields longtext NULL COMMENT 'Additional custom fields as JSON (encrypted)',
            current_usage int(11) NOT NULL DEFAULT 0 COMMENT 'Current number of active licenses',
            last_credentials_update datetime NULL COMMENT 'Last time any credential was changed',
            next_update_due datetime NULL COMMENT 'When credentials need to be updated next',
            notes text NULL COMMENT 'Internal admin notes (not encrypted)',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_provider_login (provider, account_login),
            KEY idx_provider (provider),
            KEY idx_status (status),
            KEY idx_expires_at (expires_at),
            KEY idx_current_usage (current_usage),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create product pools table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_product_pools_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_product_pools';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pool_name varchar(255) NOT NULL COMMENT 'Human-readable pool name',
            product_id bigint(20) unsigned NOT NULL COMMENT 'WooCommerce product ID',
            priority int(11) NOT NULL DEFAULT 0 COMMENT 'Pool priority (0 = highest)',
            capacity int(11) NOT NULL DEFAULT 10 COMMENT 'Maximum licenses this pool can handle',
            status enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Pool status',
            assignment_strategy enum('random','sticky','weighted','priority') NOT NULL DEFAULT 'random' COMMENT 'How to assign accounts from this pool',
            rotation_enabled tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether to rotate accounts periodically',
            rotation_interval int(11) NULL COMMENT 'Rotation interval in hours (if enabled)',
            description text NULL COMMENT 'Pool description for admin reference',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_product_id (product_id),
            KEY idx_priority (priority),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create pool accounts mapping table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_pool_accounts_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_pool_accounts';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pool_id bigint(20) unsigned NOT NULL COMMENT 'FK to vd_product_pools.id',
            account_id bigint(20) unsigned NOT NULL COMMENT 'FK to vd_provider_accounts.id',
            weight int(11) NOT NULL DEFAULT 1 COMMENT 'Weight for weighted assignment strategy',
            is_primary tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Primary account for sticky strategy',
            status enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Assignment status',
            assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_pool_account (pool_id, account_id),
            KEY idx_pool_id (pool_id),
            KEY idx_account_id (account_id),
            KEY idx_status (status),
            KEY idx_weight (weight)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create license keys table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_license_keys_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_license_keys';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL COMMENT 'License key from LMfWC',
            lmfwc_license_id bigint(20) unsigned NOT NULL COMMENT 'FK to LMfWC licenses table',
            product_id bigint(20) unsigned NOT NULL COMMENT 'WooCommerce product ID',
            order_id bigint(20) unsigned NOT NULL COMMENT 'WooCommerce order ID',
            customer_id bigint(20) unsigned NOT NULL COMMENT 'WordPress user ID',
            customer_email varchar(255) NOT NULL COMMENT 'Customer email address',
            pool_id bigint(20) unsigned NULL COMMENT 'FK to vd_product_pools.id',
            account_id bigint(20) unsigned NULL COMMENT 'FK to vd_provider_accounts.id',
            status enum('active','inactive','expired','suspended') NOT NULL DEFAULT 'active',
            expires_at datetime NULL COMMENT 'License expiration date (synced from LMfWC)',
            max_devices int(11) NOT NULL DEFAULT 2 COMMENT 'Maximum devices allowed for this license',
            current_devices int(11) NOT NULL DEFAULT 0 COMMENT 'Current number of registered devices',
            max_requests_per_day int(11) NOT NULL DEFAULT 10 COMMENT 'Maximum API requests per day',
            max_requests_per_hour int(11) NOT NULL DEFAULT 5 COMMENT 'Maximum API requests per hour',
            assigned_at datetime NULL COMMENT 'When account was assigned to this license',
            last_rotation_at datetime NULL COMMENT 'Last time account was rotated',
            synced_at datetime NULL COMMENT 'Last sync from LMfWC',
            sync_hash varchar(64) NULL COMMENT 'Hash of synced data for change detection',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_license_key (license_key),
            UNIQUE KEY uk_lmfwc_license_id (lmfwc_license_id),
            KEY idx_product_id (product_id),
            KEY idx_customer_id (customer_id),
            KEY idx_customer_email (customer_email),
            KEY idx_pool_id (pool_id),
            KEY idx_account_id (account_id),
            KEY idx_status (status),
            KEY idx_expires_at (expires_at),
            KEY idx_assigned_at (assigned_at)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create product share configs table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_product_share_configs_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_product_share_configs';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL COMMENT 'WooCommerce product ID',
            share_type enum('cookie','userpass','userpass_2fa','api_key','custom') NOT NULL DEFAULT 'userpass' COMMENT 'Type of credentials to share',
            response_fields longtext NOT NULL COMMENT 'JSON config of fields to return to customer',
            field_mapping longtext NULL COMMENT 'JSON mapping of internal fields to response fields',
            max_devices int(11) NOT NULL DEFAULT 2 COMMENT 'Maximum devices per license for this product',
            max_requests_per_day int(11) NOT NULL DEFAULT 10 COMMENT 'API rate limit per day',
            max_requests_per_hour int(11) NOT NULL DEFAULT 5 COMMENT 'API rate limit per hour',
            vps_detection_enabled tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Enable VPS detection for this product',
            vps_block_enabled tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Block VPS usage for this product',
            auto_rotation_enabled tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Enable automatic account rotation',
            rotation_interval_hours int(11) NULL COMMENT 'Hours between rotations (if enabled)',
            session_timeout_minutes int(11) NOT NULL DEFAULT 60 COMMENT 'Session timeout in minutes',
            concurrent_sessions_allowed int(11) NOT NULL DEFAULT 1 COMMENT 'Max concurrent sessions per license',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_product_id (product_id),
            KEY idx_share_type (share_type),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create device fingerprints table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_device_fingerprints_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_device_fingerprints';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            device_id varchar(64) NOT NULL COMMENT 'Unique device identifier (hash)',
            fingerprint_hash varchar(64) NOT NULL COMMENT 'Fingerprint hash for fast lookup',
            user_agent text NOT NULL COMMENT 'Browser user agent string',
            ip_address varchar(45) NOT NULL COMMENT 'Device IP address (IPv4 or IPv6)',
            screen_resolution varchar(20) NULL COMMENT 'Screen resolution (e.g., 1920x1080)',
            timezone varchar(50) NULL COMMENT 'Device timezone',
            language varchar(10) NULL COMMENT 'Browser language',
            platform varchar(50) NULL COMMENT 'Operating system platform',
            canvas_fingerprint varchar(64) NULL COMMENT 'Canvas fingerprint hash',
            webgl_fingerprint varchar(64) NULL COMMENT 'WebGL fingerprint hash',
            audio_fingerprint varchar(64) NULL COMMENT 'Audio context fingerprint hash',
            connection_type varchar(20) NULL COMMENT 'Connection type (wifi, cellular, etc.)',
            isp varchar(255) NULL COMMENT 'Internet service provider',
            country_code varchar(2) NULL COMMENT 'Country code from IP geolocation',
            city varchar(100) NULL COMMENT 'City from IP geolocation',
            is_vps tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether device is detected as VPS',
            vps_confidence_score decimal(3,2) NULL COMMENT 'VPS confidence score (0.00-1.00)',
            vps_indicators longtext NULL COMMENT 'JSON array of VPS detection indicators',
            status enum('active','inactive','blocked') NOT NULL DEFAULT 'active' COMMENT 'Device status',
            first_seen_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            access_count int(11) NOT NULL DEFAULT 1 COMMENT 'Number of times this device accessed the system',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_device_id (device_id),
            KEY idx_fingerprint_hash (fingerprint_hash),
            KEY idx_ip_address (ip_address),
            KEY idx_is_vps (is_vps),
            KEY idx_status (status),
            KEY idx_last_seen_at (last_seen_at),
            KEY idx_country_code (country_code)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create license devices table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_license_devices_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_license_devices';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL COMMENT 'FK to vd_license_keys.id',
            device_id varchar(64) NOT NULL COMMENT 'FK to vd_device_fingerprints.device_id',
            device_name varchar(255) NULL COMMENT 'User-provided device name',
            registration_ip varchar(45) NOT NULL COMMENT 'IP address when device was registered',
            status enum('active','inactive','suspended') NOT NULL DEFAULT 'active' COMMENT 'Device registration status',
            first_access_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_access_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            access_count int(11) NOT NULL DEFAULT 1 COMMENT 'Total number of accesses',
            last_request_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            requests_today int(11) NOT NULL DEFAULT 0 COMMENT 'Requests made today',
            requests_this_hour int(11) NOT NULL DEFAULT 0 COMMENT 'Requests made this hour',
            rate_limit_reset_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            registered_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_license_device (license_id, device_id),
            KEY idx_license_id (license_id),
            KEY idx_device_id (device_id),
            KEY idx_status (status),
            KEY idx_last_access_at (last_access_at),
            KEY idx_registration_ip (registration_ip)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create license device limits table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_license_device_limits_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_license_device_limits';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL COMMENT 'FK to vd_license_keys.id',
            max_devices int(11) NOT NULL DEFAULT 2 COMMENT 'Maximum devices allowed',
            current_devices int(11) NOT NULL DEFAULT 0 COMMENT 'Currently registered devices',
            device_cooldown_hours int(11) NOT NULL DEFAULT 24 COMMENT 'Hours before device can be replaced',
            last_device_change_at datetime NULL COMMENT 'Last time a device was added/removed',
            strict_mode tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Strict device limit enforcement',
            allow_device_replacement tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Allow replacing old devices',
            violation_count int(11) NOT NULL DEFAULT 0 COMMENT 'Number of limit violations',
            last_violation_at datetime NULL COMMENT 'Last device limit violation',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_license_id (license_id),
            KEY idx_max_devices (max_devices),
            KEY idx_current_devices (current_devices),
            KEY idx_last_device_change_at (last_device_change_at),
            KEY idx_violation_count (violation_count)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create account fetch log table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_account_fetch_log_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_account_fetch_log';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL COMMENT 'FK to vd_license_keys.id',
            account_id bigint(20) unsigned NOT NULL COMMENT 'FK to vd_provider_accounts.id',
            pool_id bigint(20) unsigned NOT NULL COMMENT 'FK to vd_product_pools.id',
            fetch_reason enum('new_assignment','rotation','replacement','retry') NOT NULL COMMENT 'Why account was fetched',
            assignment_strategy varchar(50) NOT NULL COMMENT 'Strategy used for assignment',
            request_ip varchar(45) NOT NULL COMMENT 'IP address of request',
            user_agent text NULL COMMENT 'User agent of request',
            device_id varchar(64) NULL COMMENT 'Device ID if available',
            success tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether fetch was successful',
            error_message text NULL COMMENT 'Error message if fetch failed',
            execution_time_ms int(11) NULL COMMENT 'Execution time in milliseconds',
            pool_capacity_at_fetch int(11) NULL COMMENT 'Pool capacity when fetched',
            fetched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_license_id (license_id),
            KEY idx_account_id (account_id),
            KEY idx_pool_id (pool_id),
            KEY idx_fetch_reason (fetch_reason),
            KEY idx_success (success),
            KEY idx_fetched_at (fetched_at),
            KEY idx_request_ip (request_ip)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create license access log table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_license_access_log_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_license_access_log';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NULL COMMENT 'FK to vd_license_keys.id (null for invalid license)',
            license_key varchar(255) NOT NULL COMMENT 'License key used in request',
            endpoint varchar(100) NOT NULL COMMENT 'API endpoint accessed',
            method varchar(10) NOT NULL DEFAULT 'GET' COMMENT 'HTTP method',
            ip_address varchar(45) NOT NULL COMMENT 'Client IP address',
            user_agent text NULL COMMENT 'Client user agent',
            device_id varchar(64) NULL COMMENT 'Device ID if provided',
            request_data longtext NULL COMMENT 'Request parameters (JSON)',
            response_status int(11) NOT NULL COMMENT 'HTTP response status code',
            response_data longtext NULL COMMENT 'Response data (JSON, may be truncated)',
            authentication_result enum('success','invalid_license','expired_license','suspended_license','device_limit','rate_limit','vps_blocked','other') NOT NULL COMMENT 'Authentication result',
            security_flags longtext NULL COMMENT 'Security flags and warnings (JSON)',
            execution_time_ms int(11) NULL COMMENT 'Request execution time in milliseconds',
            memory_usage_mb decimal(8,2) NULL COMMENT 'Memory usage in MB',
            rate_limit_remaining int(11) NULL COMMENT 'Rate limit remaining after request',
            rate_limit_reset_at datetime NULL COMMENT 'When rate limit resets',
            country_code varchar(2) NULL COMMENT 'Country code from IP',
            city varchar(100) NULL COMMENT 'City from IP geolocation',
            accessed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_license_id (license_id),
            KEY idx_license_key (license_key),
            KEY idx_endpoint (endpoint),
            KEY idx_ip_address (ip_address),
            KEY idx_device_id (device_id),
            KEY idx_response_status (response_status),
            KEY idx_authentication_result (authentication_result),
            KEY idx_accessed_at (accessed_at),
            KEY idx_country_code (country_code)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }

    /**
     * Create license rate limits table
     *
     * @since 1.0.0
     * @access private
     * @param string $charset_collate Database charset collation
     * @return void
     */
    private static function create_license_rate_limits_table( $charset_collate ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_license_rate_limits';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL COMMENT 'FK to vd_license_keys.id',
            device_id varchar(64) NULL COMMENT 'Device ID (null for license-wide limits)',
            limit_type enum('hourly','daily','monthly') NOT NULL COMMENT 'Type of rate limit',
            max_requests int(11) NOT NULL COMMENT 'Maximum requests allowed',
            window_start datetime NOT NULL COMMENT 'Start of current rate limit window',
            window_end datetime NOT NULL COMMENT 'End of current rate limit window',
            current_requests int(11) NOT NULL DEFAULT 0 COMMENT 'Requests made in current window',
            remaining_requests int(11) NOT NULL COMMENT 'Requests remaining in window',
            total_violations int(11) NOT NULL DEFAULT 0 COMMENT 'Total rate limit violations',
            last_violation_at datetime NULL COMMENT 'Last rate limit violation',
            consecutive_violations int(11) NOT NULL DEFAULT 0 COMMENT 'Consecutive violations (resets on success)',
            is_blocked tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this license/device is rate limited',
            block_expires_at datetime NULL COMMENT 'When rate limit block expires',
            block_reason varchar(255) NULL COMMENT 'Reason for rate limit block',
            avg_request_time_ms decimal(8,2) NULL COMMENT 'Average request execution time',
            peak_requests_per_minute int(11) NULL COMMENT 'Peak requests per minute',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_license_device_type (license_id, device_id, limit_type),
            KEY idx_license_id (license_id),
            KEY idx_device_id (device_id),
            KEY idx_limit_type (limit_type),
            KEY idx_window_end (window_end),
            KEY idx_is_blocked (is_blocked),
            KEY idx_last_violation_at (last_violation_at)
        ) $charset_collate;";

        dbDelta( $sql );
        VD_LM_Logger_Service::info( "Table created: {$table_name}" );
    }
}
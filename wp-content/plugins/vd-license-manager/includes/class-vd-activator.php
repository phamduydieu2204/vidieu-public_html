<?php
/**
 * Plugin activator for VD License Manager
 *
 * Handles plugin activation requirements and database setup
 *
 * @package VD_License_Manager
 * @subpackage Core
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Activator class
 *
 * Handles plugin activation tasks and requirements checking
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Activator {

    /**
     * Activate the plugin
     *
     * @since 1.0.0
     * @return void
     */
    public static function activate() {
        try {
            // Check system requirements
            $requirements_check = self::check_requirements();
            if (is_wp_error($requirements_check)) {
                wp_die(
                    esc_html($requirements_check->get_error_message()),
                    esc_html__('VD License Manager Activation Error', 'vd-license-manager'),
                    array('back_link' => true)
                );
            }

            // Create database tables
            $database_result = self::create_database_tables();
            if (is_wp_error($database_result)) {
                error_log('VD License Manager: Database creation failed - ' . $database_result->get_error_message());
            }

            // Set default options and finish setup
            self::set_default_options();
            flush_rewrite_rules();
            update_option('vd_license_manager_activated', current_time('timestamp'));
            error_log('VD License Manager: Plugin activated successfully');

        } catch (Exception $e) {
            error_log('VD License Manager: Activation failed - ' . $e->getMessage());
            wp_die(
                esc_html__('Plugin activation failed. Please check error logs.', 'vd-license-manager'),
                esc_html__('VD License Manager Activation Error', 'vd-license-manager'),
                array('back_link' => true)
            );
        }
    }

    /**
     * Check system requirements
     *
     * @since 1.0.0
     * @return bool|WP_Error True if requirements met, WP_Error if not
     */
    private static function check_requirements() {
        global $wp_version;

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            return new WP_Error('php_version', 'VD License Manager requires PHP 7.4+. Current: ' . PHP_VERSION);
        }

        // Check WordPress version
        if (version_compare($wp_version, '5.8', '<')) {
            return new WP_Error('wp_version', 'VD License Manager requires WordPress 5.8+. Current: ' . $wp_version);
        }

        // Check required PHP extensions
        $required_extensions = array('mysqli', 'curl', 'mbstring', 'json');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                return new WP_Error('php_extension', 'Required PHP extension missing: ' . $extension);
            }
        }

        // Check WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_missing', 'VD License Manager requires WooCommerce.');
        }

        // Check License Manager for WooCommerce is active
        if (!function_exists('lmfwc_get_license')) {
            return new WP_Error('lmfwc_missing', 'VD License Manager requires License Manager for WooCommerce (LMFWC).');
        }

        return true;
    }

    /**
     * Create database tables
     *
     * @since 1.0.0
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private static function create_database_tables() {
        global $wpdb;

        error_log('VD License Manager: Starting database table creation...');

        // DROP all existing tables first (clean slate)
        $tables_to_drop = array(
            'bz_vd_license_rate_limits',
            'bz_vd_license_access_log',
            'bz_vd_account_fetch_log',
            'bz_vd_product_share_configs',
            'bz_vd_license_device_limits',
            'bz_vd_license_devices',
            'bz_vd_device_fingerprints',
            'bz_vd_cookie_assignments',
            'bz_vd_pool_accounts',
            'bz_vd_product_pools',
            'bz_vd_provider_accounts'
        );

        foreach ($tables_to_drop as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        error_log('VD License Manager: Dropped existing tables');

        // Load database classes
        require_once VD_PLUGIN_PATH . 'includes/database/class-vd-db-core.php';
        require_once VD_PLUGIN_PATH . 'includes/database/class-vd-db-devices.php';
        require_once VD_PLUGIN_PATH . 'includes/database/class-vd-db-logs.php';

        // Create tables fresh
        VD_DB_Core::create_tables();
        VD_DB_Devices::create_tables();
        VD_DB_Logs::create_tables();

        // Verify table count
        $tables = $wpdb->get_results("SHOW TABLES LIKE 'bz_vd_%'");
        $count = count($tables);

        if ($count === 11) {
            error_log("VD License Manager: ✅ All 11 tables created successfully");
        } else {
            error_log("VD License Manager: ⚠️ Expected 11 tables, created $count");
            foreach ($tables as $table) {
                $name = array_values((array)$table)[0];
                error_log("  Table: $name");
            }
        }

        return $count === 11 ? true : new WP_Error('database_creation', "Expected 11 tables, created $count");
    }

    /**
     * Set default plugin options
     *
     * @since 1.0.0
     * @return void
     */
    private static function set_default_options() {
        $defaults = array(
            'vd_license_manager_version' => VD_VERSION,
            'vd_license_manager_db_version' => '1.0.0',
            'vd_default_device_limit' => 1,
            'vd_default_cooldown_seconds' => 86400,
            'vd_rate_limit_window' => 300,
            'vd_rate_limit_max_hits' => 10,
            'vd_enable_geolocation' => true,
            'vd_log_retention_days' => 365
        );

        foreach ($defaults as $name => $value) {
            if (get_option($name) === false) {
                add_option($name, $value);
            }
        }
    }
}
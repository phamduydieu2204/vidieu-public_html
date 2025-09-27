<?php
/**
 * VD License Manager Activator
 *
 * Handles plugin activation and deactivation tasks
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Activator class
 *
 * Handles plugin activation and deactivation
 */
class VD_Activator {

    /**
     * Plugin activation callback
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Check WordPress version
        if (!self::check_wordpress_version()) {
            deactivate_plugins(plugin_basename(VD_LM_FILE));
            wp_die(__('VD License Manager requires WordPress 5.0 or higher.', VD_LM_TEXT_DOMAIN));
        }

        // Check PHP version
        if (!self::check_php_version()) {
            deactivate_plugins(plugin_basename(VD_LM_FILE));
            wp_die(__('VD License Manager requires PHP 7.4 or higher.', VD_LM_TEXT_DOMAIN));
        }

        // Check required extensions
        if (!self::check_required_extensions()) {
            deactivate_plugins(plugin_basename(VD_LM_FILE));
            wp_die(__('VD License Manager requires OpenSSL, JSON, MySQLi, cURL, and mbstring PHP extensions.', VD_LM_TEXT_DOMAIN));
        }

        // Check encryption key
        if (!self::check_encryption_key()) {
            deactivate_plugins(plugin_basename(VD_LM_FILE));
            wp_die(__('VD License Manager requires VD_ENCRYPTION_KEY to be defined in wp-config.php.', VD_LM_TEXT_DOMAIN));
        }

        // Set activation flag
        update_option('vd_license_manager_activation_time', time());
        update_option('vd_license_manager_version', VD_LM_VERSION);

        // Create database tables (will be implemented in Sprint 2)
        self::create_database_tables();

        // Add custom capabilities
        self::add_custom_capabilities();

        // Schedule cron jobs
        self::schedule_cron_jobs();

        // Create default options
        self::create_default_options();

        // Log activation
        if (function_exists('error_log')) {
            error_log('[VD License Manager] Plugin activated successfully. Version: ' . VD_LM_VERSION);
        }
    }

    /**
     * Plugin deactivation callback
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        self::clear_cron_jobs();

        // Log deactivation
        if (function_exists('error_log')) {
            error_log('[VD License Manager] Plugin deactivated.');
        }

        // Note: We don't remove database tables or capabilities
        // This allows users to reactivate without losing data
    }

    /**
     * Check WordPress version requirement
     *
     * @since 1.0.0
     * @return bool True if WordPress version is sufficient
     */
    private static function check_wordpress_version() {
        global $wp_version;
        return version_compare($wp_version, '5.0', '>=');
    }

    /**
     * Check PHP version requirement
     *
     * @since 1.0.0
     * @return bool True if PHP version is sufficient
     */
    private static function check_php_version() {
        return version_compare(PHP_VERSION, '7.4', '>=');
    }

    /**
     * Check required PHP extensions
     *
     * @since 1.0.0
     * @return bool True if all required extensions are loaded
     */
    private static function check_required_extensions() {
        $required_extensions = ['openssl', 'json', 'mysqli', 'curl', 'mbstring'];

        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check encryption key configuration
     *
     * @since 1.0.0
     * @return bool True if encryption key is properly configured
     */
    private static function check_encryption_key() {
        if (!defined('VD_ENCRYPTION_KEY') || empty(VD_ENCRYPTION_KEY)) {
            return false;
        }

        $key = VD_ENCRYPTION_KEY;

        // Handle base64 encoded keys
        if (strpos($key, 'base64:') === 0) {
            $decoded = base64_decode(substr($key, 7));
            return $decoded !== false && strlen($decoded) === 32;
        }

        // Direct key should be 32 bytes
        return strlen($key) === 32;
    }

    /**
     * Create database tables - Step 2.5: Full schema creation
     *
     * @since 1.0.0
     */
    private static function create_database_tables() {
        // Step 2.5: Load database manager and create all tables
        if (!class_exists('VD_Database_Manager')) {
            require_once VD_LM_PATH . 'includes/class-vd-database-manager.php';
        }

        try {
            // Create tables (Step 2.5: all 11 tables)
            $result = VD_Database_Manager::create_tables();

            if ($result['success']) {
                update_option('vd_license_manager_db_version', '1.0.0');
                update_option('vd_license_manager_tables_created', time());
                update_option('vd_license_manager_full_schema_version', '2.5');

                // Log successful creation
                $created_tables = array_keys(array_filter($result['tables']));
                error_log('[VD License Manager] Step 2.5 - Full schema created successfully: ' .
                         implode(', ', $created_tables) . ' (' . count($created_tables) . ' tables)');
            } else {
                // Log errors but don't prevent activation
                $errors = !empty($result['errors']) ? implode(', ', $result['errors']) : 'Unknown errors';
                error_log('[VD License Manager] Step 2.5 - Database table creation issues: ' . $errors);

                // Log summary if available
                if (isset($result['summary'])) {
                    $summary = $result['summary'];
                    error_log('[VD License Manager] Step 2.5 - Summary: ' .
                             $summary['created'] . '/' . $summary['total_tables'] . ' tables created');
                }
            }
        } catch (Exception $e) {
            // Log error but don't prevent activation
            error_log('[VD License Manager] Step 2.5 - Database creation exception: ' . $e->getMessage());
        }
    }

    /**
     * Add custom capabilities to WordPress roles
     * Step 3.3.5b: Use VD_Capability_Manager for standardized capability management
     *
     * @since 1.0.0
     */
    private static function add_custom_capabilities() {
        // Step 3.3.5b: Use VD_Capability_Manager for consistent capability management
        if (!class_exists('VD_Capability_Manager')) {
            require_once VD_LM_PATH . 'includes/class-vd-capability-manager.php';
        }

        try {
            // Get capability manager instance and add capabilities
            $capability_manager = VD_Capability_Manager::get_instance();
            $capability_manager->add_capabilities();

            // Step 3.3.5c: Create single custom role (VD License Viewer)
            $capability_manager->create_single_role();

            // Fire action for capability addition
            do_action('vd_license_manager_activated');

            error_log('[VD License Manager] Step 3.3.5c - Capabilities and single role added via VD_Capability_Manager');

        } catch (Exception $e) {
            // Log error but don't prevent activation
            error_log('[VD License Manager] Step 3.3.5c - Capability/role addition error: ' . $e->getMessage());

            // Fallback to basic capabilities if VD_Capability_Manager fails
            $admin_role = get_role('administrator');
            if ($admin_role) {
                $admin_role->add_cap('manage_vd_licenses');
                $admin_role->add_cap('view_vd_licenses');
                error_log('[VD License Manager] Step 3.3.5c - Fallback: Basic capabilities added');
            }
        }

        // Note: Step 3.3.5c - Single custom role creation completed
        // For Step 3.3.5c: Administrator role gets 11 capabilities + VD License Viewer role created
    }

    /**
     * Schedule cron jobs
     *
     * @since 1.0.0
     */
    private static function schedule_cron_jobs() {
        // Schedule cleanup job if not already scheduled
        if (!wp_next_scheduled('vd_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'vd_cleanup_logs');
        }

        // Schedule provider health check
        if (!wp_next_scheduled('vd_check_provider_health')) {
            wp_schedule_event(time(), 'hourly', 'vd_check_provider_health');
        }

        // Schedule license expiration check
        if (!wp_next_scheduled('vd_check_license_expiration')) {
            wp_schedule_event(time(), 'twicedaily', 'vd_check_license_expiration');
        }
    }

    /**
     * Clear scheduled cron jobs
     *
     * @since 1.0.0
     */
    private static function clear_cron_jobs() {
        wp_clear_scheduled_hook('vd_cleanup_logs');
        wp_clear_scheduled_hook('vd_check_provider_health');
        wp_clear_scheduled_hook('vd_check_license_expiration');
    }

    /**
     * Create default plugin options
     *
     * @since 1.0.0
     */
    private static function create_default_options() {
        $default_options = [
            'default_device_limit' => 3,
            'auto_approval_threshold' => 25.0,
            'rate_limit_requests_per_hour' => 60,
            'rate_limit_requests_per_day' => 1000,
            'log_retention_days' => 90,
            'enable_debug_logging' => false,
            'assignment_algorithm' => 'least_loaded',
            'session_timeout_minutes' => 30
        ];

        foreach ($default_options as $option_name => $default_value) {
            $option_key = 'vd_license_manager_' . $option_name;
            if (!get_option($option_key)) {
                add_option($option_key, $default_value);
            }
        }
    }

    /**
     * Get activation requirements status
     *
     * @since 1.0.0
     * @return array Requirements status
     */
    public static function get_requirements_status() {
        return [
            'wordpress_version' => [
                'required' => '5.0',
                'current' => $GLOBALS['wp_version'],
                'met' => self::check_wordpress_version()
            ],
            'php_version' => [
                'required' => '7.4',
                'current' => PHP_VERSION,
                'met' => self::check_php_version()
            ],
            'extensions' => [
                'required' => ['openssl', 'json', 'mysqli', 'curl', 'mbstring'],
                'met' => self::check_required_extensions()
            ],
            'encryption_key' => [
                'configured' => defined('VD_ENCRYPTION_KEY'),
                'valid' => self::check_encryption_key()
            ]
        ];
    }
}
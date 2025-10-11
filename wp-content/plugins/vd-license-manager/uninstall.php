<?php
/**
 * Fired when the plugin is uninstalled
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @package    VD_License_Manager
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * VD License Manager Uninstaller
 *
 * Handles complete removal of plugin data when uninstalled.
 */
class VD_License_Manager_Uninstaller {

    /**
     * Run the uninstall process
     *
     * @since 1.0.0
     */
    public static function uninstall() {
        // Check if we should preserve data
        if ( self::should_preserve_data() ) {
            return;
        }

        // Remove database tables
        self::drop_database_tables();

        // Remove plugin options
        self::remove_plugin_options();

        // Remove transients
        self::remove_transients();

        // Remove capabilities
        self::remove_capabilities();

        // Clear cron jobs
        self::clear_cron_jobs();

        // Remove log files
        self::remove_log_files();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Check if data should be preserved
     *
     * @since  1.0.0
     * @access private
     * @return bool True if data should be preserved
     */
    private static function should_preserve_data() {
        // Check if there's a setting to preserve data
        $preserve_data = get_option( 'vd_license_manager_preserve_data_on_uninstall', false );

        return (bool) $preserve_data;
    }

    /**
     * Drop all database tables
     *
     * @since  1.0.0
     * @access private
     */
    private static function drop_database_tables() {
        global $wpdb;

        $tables = array(
            'vd_provider_accounts',
            'vd_product_pools',
            'vd_pool_accounts',
            'vd_cookie_assignments',
            'vd_product_share_configs',
            'vd_device_fingerprints',
            'vd_license_devices',
            'vd_license_device_limits',
            'vd_account_fetch_log',
            'vd_license_access_log',
            'vd_license_rate_limits',
        );

        foreach ( $tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            $wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
        }
    }

    /**
     * Remove all plugin options
     *
     * @since  1.0.0
     * @access private
     */
    private static function remove_plugin_options() {
        global $wpdb;

        // Remove all options that start with 'vd_license_manager_'
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE 'vd_license_manager_%'"
        );

        // Remove specific options
        $options = array(
            'vd_license_manager_version',
            'vd_license_manager_activated_at',
            'vd_license_manager_deactivated_at',
            'vd_license_manager_db_version',
        );

        foreach ( $options as $option ) {
            delete_option( $option );
        }
    }

    /**
     * Remove all plugin transients
     *
     * @since  1.0.0
     * @access private
     */
    private static function remove_transients() {
        global $wpdb;

        // Remove transients that start with 'vd_'
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_vd_%'
            OR option_name LIKE '_transient_timeout_vd_%'"
        );

        // Remove site transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_site_transient_vd_%'
            OR option_name LIKE '_site_transient_timeout_vd_%'"
        );

        // Clear object cache
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'vd-license-manager' );
        }
    }

    /**
     * Remove custom capabilities
     *
     * @since  1.0.0
     * @access private
     */
    private static function remove_capabilities() {
        $capabilities = array(
            'vd_manage_accounts',
            'vd_manage_pools',
            'vd_manage_licenses',
            'vd_view_logs',
            'vd_manage_settings',
        );

        $roles = array( 'administrator', 'shop_manager' );

        foreach ( $roles as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                foreach ( $capabilities as $capability ) {
                    $role->remove_cap( $capability );
                }
            }
        }
    }

    /**
     * Clear scheduled cron jobs
     *
     * @since  1.0.0
     * @access private
     */
    private static function clear_cron_jobs() {
        $cron_hooks = array(
            'vd_license_manager_cleanup',
            'vd_license_manager_stats_update',
            'vd_license_manager_check_expired_licenses',
            'vd_license_manager_pool_capacity_check',
            'vd_license_manager_device_cleanup',
        );

        foreach ( $cron_hooks as $hook ) {
            wp_clear_scheduled_hook( $hook );
        }
    }

    /**
     * Remove log files
     *
     * @since  1.0.0
     * @access private
     */
    private static function remove_log_files() {
        // Remove main log file
        $log_file = WP_CONTENT_DIR . '/vd-logs/vd-license-manager.log';
        if ( file_exists( $log_file ) ) {
            unlink( $log_file );
        }

        // Remove backup log files
        $log_dir = WP_CONTENT_DIR . '/vd-logs/';
        if ( is_dir( $log_dir ) ) {
            $backup_files = glob( $log_dir . '*.bak' );
            foreach ( $backup_files as $file ) {
                if ( file_exists( $file ) ) {
                    unlink( $file );
                }
            }

            // Remove directory if empty
            if ( self::is_dir_empty( $log_dir ) ) {
                rmdir( $log_dir );
            }
        }

        // Remove debug log entries from wp-content/debug.log
        $debug_log = WP_CONTENT_DIR . '/debug.log';
        if ( file_exists( $debug_log ) ) {
            $content = file_get_contents( $debug_log );
            $cleaned_content = preg_replace( '/.*VD License Manager.*\n/', '', $content );
            if ( $cleaned_content !== $content ) {
                file_put_contents( $debug_log, $cleaned_content );
            }
        }
    }

    /**
     * Check if directory is empty
     *
     * @since  1.0.0
     * @access private
     * @param  string $dir Directory path
     * @return bool True if directory is empty
     */
    private static function is_dir_empty( $dir ) {
        if ( ! is_readable( $dir ) ) {
            return false;
        }

        $handle = opendir( $dir );
        while ( false !== ( $entry = readdir( $handle ) ) ) {
            if ( $entry !== '.' && $entry !== '..' ) {
                closedir( $handle );
                return false;
            }
        }
        closedir( $handle );
        return true;
    }
}

// Run the uninstaller
VD_License_Manager_Uninstaller::uninstall();
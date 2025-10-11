<?php
/**
 * Fired during plugin deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fired during plugin deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Deactivator {

    /**
     * Plugin deactivation handler
     *
     * Performs cleanup tasks when the plugin is deactivated:
     * - Clear scheduled cron jobs
     * - Flush rewrite rules
     * - Clear transients and cache
     * - Log deactivation
     *
     * Note: This does NOT delete database tables or permanent data.
     * That is handled by uninstall.php when the plugin is deleted.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled cron jobs
        self::clear_scheduled_events();

        // Clear transients and cache
        self::clear_transients();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log deactivation
        if ( function_exists( 'error_log' ) ) {
            error_log( 'VD License Manager: Plugin deactivated' );
        }

        // Update deactivation timestamp
        update_option( 'vd_license_manager_deactivated_at', current_time( 'mysql' ) );
    }

    /**
     * Clear scheduled cron events
     *
     * Removes any cron jobs that were scheduled by the plugin
     *
     * @since 1.0.0
     */
    private static function clear_scheduled_events() {
        // Clear any scheduled hooks
        $scheduled_hooks = array(
            'vd_license_manager_cleanup',
            'vd_license_manager_stats_update',
            'vd_license_manager_check_expired_licenses',
            'vd_license_manager_pool_capacity_check',
            'vd_license_manager_device_cleanup',
        );

        foreach ( $scheduled_hooks as $hook ) {
            if ( wp_next_scheduled( $hook ) ) {
                wp_clear_scheduled_hook( $hook );
            }
        }
    }

    /**
     * Clear plugin transients and cache
     *
     * Removes temporary data stored by the plugin
     *
     * @since 1.0.0
     */
    private static function clear_transients() {
        // Clear plugin-specific transients
        $transients = array(
            'vd_license_stats',
            'vd_pool_capacity_cache',
            'vd_device_fingerprint_cache',
            'vd_rate_limit_cache',
            'vd_account_assignment_cache',
        );

        foreach ( $transients as $transient ) {
            delete_transient( $transient );
        }

        // Clear any site transients
        foreach ( $transients as $transient ) {
            delete_site_transient( $transient );
        }

        // Clear object cache if available
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'vd-license-manager' );
        }
    }
}
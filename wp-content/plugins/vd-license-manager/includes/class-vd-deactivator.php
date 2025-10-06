<?php
/**
 * Plugin deactivator for VD License Manager
 *
 * Handles plugin deactivation cleanup tasks
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
 * VD Deactivator class
 *
 * Handles plugin deactivation cleanup tasks
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Deactivator {

    /**
     * Deactivate the plugin
     *
     * Performs cleanup tasks but preserves data for potential reactivation
     *
     * @since 1.0.0
     * @return void
     */
    public static function deactivate() {
        try {
            // Flush rewrite rules
            flush_rewrite_rules();

            // Clear scheduled cron jobs
            self::clear_scheduled_crons();

            // Clear transients
            self::clear_transients();

            // Log deactivation
            error_log('VD License Manager: Plugin deactivated successfully');

            // Update deactivation timestamp
            update_option('vd_license_manager_deactivated', current_time('timestamp'));

        } catch (Exception $e) {
            error_log('VD License Manager: Deactivation cleanup failed - ' . $e->getMessage());
        }
    }

    /**
     * Clear scheduled cron jobs
     *
     * @since 1.0.0
     * @return void
     */
    private static function clear_scheduled_crons() {
        $cron_hooks = array(
            'vd_daily_cleanup',
            'vd_hourly_check',
            'vd_account_check'
        );

        foreach ($cron_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }

    /**
     * Clear plugin transients
     *
     * @since 1.0.0
     * @return void
     */
    private static function clear_transients() {
        global $wpdb;

        // Clear transients with vd_ prefix
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_vd_%'
             OR option_name LIKE '_transient_timeout_vd_%'"
        );

        // Clear site transients with vd_ prefix (for multisite)
        if (is_multisite()) {
            $wpdb->query(
                "DELETE FROM {$wpdb->sitemeta}
                 WHERE meta_key LIKE '_site_transient_vd_%'
                 OR meta_key LIKE '_site_transient_timeout_vd_%'"
            );
        }
    }
}
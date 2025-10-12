<?php
/**
 * Cron Jobs Handler
 *
 * Handles scheduled tasks like sending renewal reminders
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class VD_LM_Cron {

    /**
     * Constructor
     */
    public function __construct() {
        // Register cron schedules
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));

        // Register cron hooks
        add_action('vd_check_expiring_licenses', array($this, 'check_expiring_licenses'));

        // Activate cron on plugin activation
        add_action('vd_lm_activated', array($this, 'schedule_cron'));

        // Deactivate cron on plugin deactivation
        add_action('vd_lm_deactivated', array($this, 'unschedule_cron'));
    }

    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules($schedules) {
        // Add daily schedule (if not exists)
        if (!isset($schedules['daily'])) {
            $schedules['daily'] = array(
                'interval' => DAY_IN_SECONDS,
                'display'  => __('Once Daily', 'vd-license-manager')
            );
        }

        return $schedules;
    }

    /**
     * Schedule cron job
     */
    public function schedule_cron() {
        if (!wp_next_scheduled('vd_check_expiring_licenses')) {
            wp_schedule_event(time(), 'daily', 'vd_check_expiring_licenses');
            error_log('VD Cron: Scheduled daily license expiry check');
        }
    }

    /**
     * Unschedule cron job
     */
    public function unschedule_cron() {
        $timestamp = wp_next_scheduled('vd_check_expiring_licenses');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vd_check_expiring_licenses');
            error_log('VD Cron: Unscheduled license expiry check');
        }
    }

    /**
     * Check for expiring licenses and send reminders
     */
    public function check_expiring_licenses() {
        error_log('VD Cron: === CHECKING EXPIRING LICENSES ===');

        global $wpdb;

        // Get licenses that expired TODAY (between start of today and now)
        $today_start = date('Y-m-d 00:00:00');
        $now = date('Y-m-d H:i:s');

        $licenses = $wpdb->get_results($wpdb->prepare(
            "SELECT id, license_key, customer_email, product_id, expires_at
            FROM {$wpdb->prefix}vd_license_keys
            WHERE status = 'active'
            AND expires_at BETWEEN %s AND %s
            AND (renewal_reminder_sent_at IS NULL OR renewal_reminder_sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
            ORDER BY expires_at ASC",
            $today_start,
            $now
        ), ARRAY_A);

        error_log('VD Cron: Found ' . count($licenses) . ' licenses that expired TODAY');

        if (empty($licenses)) {
            error_log('VD Cron: No expiring licenses to process');
            return;
        }

        // Send renewal reminders
        require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-email-handler.php';

        $sent_count = 0;
        $failed_count = 0;

        foreach ($licenses as $license) {
            error_log('VD Cron: Processing license ID: ' . $license['id']);

            $sent = VD_LM_Email_Handler::send_renewal_reminder($license['id']);

            if ($sent) {
                $sent_count++;
            } else {
                $failed_count++;
            }

            // Small delay to avoid overwhelming mail server
            sleep(1);
        }

        error_log('VD Cron: Renewal reminders sent: ' . $sent_count . ', Failed: ' . $failed_count);
        error_log('VD Cron: === EXPIRING LICENSES CHECK COMPLETE ===');
    }
}

// Initialize
new VD_LM_Cron();
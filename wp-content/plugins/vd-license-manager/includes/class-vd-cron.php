<?php
/**
 * Scheduled tasks for VD License Manager
 *
 * Handles automated maintenance and monitoring tasks
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
 * VD Cron class
 *
 * Manages scheduled maintenance and monitoring tasks
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Cron {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Register scheduled events on init
        add_action('init', array($this, 'register_schedules'));

        // Hook scheduled actions
        add_action('vd_daily_cleanup', array($this, 'daily_cleanup'));
        add_action('vd_hourly_check', array($this, 'hourly_check_accounts'));

        // Manual trigger hooks for testing
        add_action('wp_ajax_vd_trigger_cleanup', array($this, 'manual_trigger_cleanup'));
        add_action('wp_ajax_vd_trigger_check', array($this, 'manual_trigger_check'));
    }

    /**
     * Register scheduled events
     *
     * @since 1.0.0
     */
    public function register_schedules() {
        // Schedule daily cleanup if not already scheduled
        if (!wp_next_scheduled('vd_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vd_daily_cleanup');
            VD_Logger::log('Cron: Daily cleanup scheduled', 'info');
        }

        // Schedule hourly check if not already scheduled
        if (!wp_next_scheduled('vd_hourly_check')) {
            wp_schedule_event(time(), 'hourly', 'vd_hourly_check');
            VD_Logger::log('Cron: Hourly check scheduled', 'info');
        }

        // Add custom interval for capacity checks (every 4 hours)
        add_filter('cron_schedules', array($this, 'add_custom_schedules'));

        // Schedule capacity check if not already scheduled
        if (!wp_next_scheduled('vd_capacity_check')) {
            wp_schedule_event(time(), 'four_hourly', 'vd_capacity_check');
            add_action('vd_capacity_check', array($this, 'check_capacity'));
        }
    }

    /**
     * Add custom cron schedules
     *
     * @since 1.0.0
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_custom_schedules($schedules) {
        $schedules['four_hourly'] = array(
            'interval' => 4 * HOUR_IN_SECONDS,
            'display' => __('Every 4 Hours', 'vd-license-manager')
        );

        return $schedules;
    }

    /**
     * Daily cleanup tasks
     *
     * @since 1.0.0
     */
    public function daily_cleanup() {
        $start_time = microtime(true);
        VD_Logger::log('Cron: Starting daily cleanup', 'info');

        global $wpdb;
        $cleanup_results = array();

        try {
            // 1. Anonymize old IP addresses (>90 days)
            $anonymize_days = get_option('vd_ip_anonymize_days', 90);
            $anonymize_date = date('Y-m-d H:i:s', strtotime("-{$anonymize_days} days"));

            // Anonymize access log IPs
            $access_anonymized = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}vd_license_access_log
                 SET ip_address = MD5(CONCAT(ip_address, 'vd_privacy_salt'))
                 WHERE access_time < %s AND ip_address NOT LIKE '%[hash]%'",
                $anonymize_date
            ));

            // Anonymize fetch log IPs
            $fetch_anonymized = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}vd_account_fetch_log
                 SET ip_address = MD5(CONCAT(ip_address, 'vd_privacy_salt'))
                 WHERE fetch_time < %s AND ip_address NOT LIKE '%[hash]%'",
                $anonymize_date
            ));

            $cleanup_results['ip_anonymized'] = $access_anonymized + $fetch_anonymized;

            // 2. Delete old logs (>365 days)
            $retention_days = get_option('vd_log_retention_days', 365);
            $deletion_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

            // Delete old access logs
            $access_deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vd_license_access_log WHERE access_time < %s",
                $deletion_date
            ));

            // Delete old fetch logs
            $fetch_deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vd_account_fetch_log WHERE fetch_time < %s",
                $deletion_date
            ));

            $cleanup_results['logs_deleted'] = $access_deleted + $fetch_deleted;

            // 3. Revoke assignments for expired licenses
            $expired_revoked = $wpdb->query(
                "UPDATE {$wpdb->prefix}vd_cookie_assignments ca
                 INNER JOIN {$wpdb->prefix}lmfwc_licenses l ON ca.license_id = l.id
                 SET ca.status = 'revoked', ca.revoked_at = NOW(), ca.updated_at = NOW()
                 WHERE l.expires_at < NOW() AND ca.status = 'active'"
            );

            $cleanup_results['assignments_revoked'] = $expired_revoked;

            // 4. Clean expired device registrations
            $device_cleanup = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}vd_license_devices ld
                 INNER JOIN {$wpdb->prefix}lmfwc_licenses l ON ld.license_id = l.id
                 SET ld.status = 'expired', ld.updated_at = NOW()
                 WHERE l.expires_at < NOW() AND ld.status = 'approved'"
            ));

            $cleanup_results['devices_expired'] = $device_cleanup;

            // 5. Delete expired transients
            delete_expired_transients();
            $cleanup_results['transients_cleaned'] = true;

            // 6. Optimize database tables
            $tables = array(
                $wpdb->prefix . 'vd_license_access_log',
                $wpdb->prefix . 'vd_account_fetch_log',
                $wpdb->prefix . 'vd_cookie_assignments',
                $wpdb->prefix . 'vd_license_devices'
            );

            foreach ($tables as $table) {
                $wpdb->query("OPTIMIZE TABLE `{$table}`");
            }

            $cleanup_results['tables_optimized'] = count($tables);

            $execution_time = round(microtime(true) - $start_time, 2);

            // Log cleanup summary
            VD_Logger::log(sprintf(
                'Cron: Daily cleanup completed in %s seconds. IPs anonymized: %d, Logs deleted: %d, Assignments revoked: %d, Devices expired: %d',
                $execution_time,
                $cleanup_results['ip_anonymized'],
                $cleanup_results['logs_deleted'],
                $cleanup_results['assignments_revoked'],
                $cleanup_results['devices_expired']
            ), 'info');

        } catch (Exception $e) {
            VD_Logger::log('Cron: Daily cleanup failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Hourly account monitoring
     *
     * @since 1.0.0
     */
    public function hourly_check_accounts() {
        VD_Logger::log('Cron: Starting hourly account check', 'info');

        global $wpdb;

        try {
            // Check for accounts expiring soon
            $warning_days = get_option('vd_expiry_warning_days', 7);
            $expiry_check_date = date('Y-m-d H:i:s', strtotime("+{$warning_days} days"));

            $expiring_accounts = $wpdb->get_results($wpdb->prepare(
                "SELECT account_id, name, provider, expires_at
                 FROM {$wpdb->prefix}vd_provider_accounts
                 WHERE is_active = 1
                   AND expires_at IS NOT NULL
                   AND expires_at < %s
                   AND expires_at > NOW()
                 ORDER BY expires_at ASC",
                $expiry_check_date
            ), ARRAY_A);

            if (!empty($expiring_accounts)) {
                $this->notify_expiring_accounts($expiring_accounts);
                VD_Logger::log(sprintf('Cron: Found %d expiring accounts', count($expiring_accounts)), 'warning');
            }

            // Check for failed assignments in last hour
            $failed_assignments = $wpdb->get_var(
                "SELECT COUNT(*)
                 FROM {$wpdb->prefix}vd_cookie_assignments
                 WHERE status = 'failed'
                   AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );

            if ($failed_assignments > 5) {
                $this->notify_high_failure_rate($failed_assignments);
                VD_Logger::log(sprintf('Cron: High assignment failure rate: %d failures in last hour', $failed_assignments), 'warning');
            }

            // Check for accounts with low usage
            $this->check_unused_accounts();

        } catch (Exception $e) {
            VD_Logger::log('Cron: Hourly check failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Check pool capacity usage
     *
     * @since 1.0.0
     */
    public function check_capacity() {
        VD_Logger::log('Cron: Starting capacity check', 'info');

        global $wpdb;

        try {
            $warning_threshold = get_option('vd_capacity_warning_percent', 90);

            // Get pools with high usage
            $high_usage_pools = $wpdb->get_results($wpdb->prepare(
                "SELECT
                    pp.pool_id,
                    pp.name,
                    COUNT(ca.assignment_id) as active_assignments,
                    SUM(pa.capacity) as total_capacity,
                    ROUND((COUNT(ca.assignment_id) / SUM(pa.capacity)) * 100, 2) as usage_percent
                 FROM {$wpdb->prefix}vd_product_pools pp
                 LEFT JOIN {$wpdb->prefix}vd_pool_accounts poa ON pp.pool_id = poa.pool_id AND poa.is_active = 1
                 LEFT JOIN {$wpdb->prefix}vd_provider_accounts pa ON poa.provider_account_id = pa.account_id AND pa.is_active = 1
                 LEFT JOIN {$wpdb->prefix}vd_cookie_assignments ca ON pa.account_id = ca.provider_account_id AND ca.status = 'active'
                 WHERE pp.is_active = 1
                 GROUP BY pp.pool_id
                 HAVING usage_percent > %d AND total_capacity > 0
                 ORDER BY usage_percent DESC",
                $warning_threshold
            ), ARRAY_A);

            if (!empty($high_usage_pools)) {
                $this->notify_high_capacity($high_usage_pools);
                VD_Logger::log(sprintf('Cron: Found %d pools with high capacity usage', count($high_usage_pools)), 'warning');
            }

        } catch (Exception $e) {
            VD_Logger::log('Cron: Capacity check failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Check for unused accounts
     *
     * @since 1.0.0
     */
    private function check_unused_accounts() {
        global $wpdb;

        // Find accounts with no assignments in 30 days
        $unused_accounts = $wpdb->get_results(
            "SELECT pa.account_id, pa.name, pa.provider
             FROM {$wpdb->prefix}vd_provider_accounts pa
             LEFT JOIN {$wpdb->prefix}vd_cookie_assignments ca ON pa.account_id = ca.provider_account_id
               AND ca.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
             WHERE pa.is_active = 1 AND ca.assignment_id IS NULL
             GROUP BY pa.account_id",
            ARRAY_A
        );

        if (count($unused_accounts) > 0) {
            VD_Logger::log(sprintf('Cron: Found %d unused accounts in last 30 days', count($unused_accounts)), 'info');
        }
    }

    /**
     * Send expiring accounts notification
     *
     * @since 1.0.0
     * @param array $accounts Expiring accounts
     */
    private function notify_expiring_accounts($accounts) {
        $admin_email = get_option('vd_admin_email', get_option('admin_email'));
        $subject = sprintf(__('[%s] Accounts Expiring Soon', 'vd-license-manager'), get_bloginfo('name'));

        $message = __("The following accounts are expiring soon:\n\n", 'vd-license-manager');

        foreach ($accounts as $account) {
            $days_left = ceil((strtotime($account['expires_at']) - time()) / DAY_IN_SECONDS);
            $message .= sprintf("- %s (%s): %d days remaining\n",
                              $account['name'],
                              ucfirst($account['provider']),
                              $days_left);
        }

        $message .= sprintf("\n%s", admin_url('admin.php?page=vd-provider-accounts'));

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Send high failure rate notification
     *
     * @since 1.0.0
     * @param int $failure_count Number of failures
     */
    private function notify_high_failure_rate($failure_count) {
        $admin_email = get_option('vd_admin_email', get_option('admin_email'));
        $subject = sprintf(__('[%s] High Assignment Failure Rate', 'vd-license-manager'), get_bloginfo('name'));

        $message = sprintf(__("Warning: %d assignment failures in the last hour.\n\n", 'vd-license-manager'), $failure_count);
        $message .= __("This may indicate:\n", 'vd-license-manager');
        $message .= __("- Insufficient account capacity\n", 'vd-license-manager');
        $message .= __("- Account authentication issues\n", 'vd-license-manager');
        $message .= __("- System configuration problems\n\n", 'vd-license-manager');
        $message .= sprintf("%s", admin_url('admin.php?page=vd-logs&tab=assignment'));

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Send high capacity notification
     *
     * @since 1.0.0
     * @param array $pools High usage pools
     */
    private function notify_high_capacity($pools) {
        $admin_email = get_option('vd_admin_email', get_option('admin_email'));
        $subject = sprintf(__('[%s] High Pool Capacity Usage', 'vd-license-manager'), get_bloginfo('name'));

        $message = __("The following pools have high capacity usage:\n\n", 'vd-license-manager');

        foreach ($pools as $pool) {
            $message .= sprintf("- %s: %d/%d (%s%% usage)\n",
                              $pool['name'],
                              $pool['active_assignments'],
                              $pool['total_capacity'],
                              $pool['usage_percent']);
        }

        $message .= sprintf("\n%s", admin_url('admin.php?page=vd-product-pools'));

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Manual trigger for cleanup (testing)
     *
     * @since 1.0.0
     */
    public function manual_trigger_cleanup() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions'));
        }

        $this->daily_cleanup();
        wp_redirect(admin_url('admin.php?page=vd-settings&tab=advanced&cleanup=triggered'));
        exit;
    }

    /**
     * Manual trigger for hourly check (testing)
     *
     * @since 1.0.0
     */
    public function manual_trigger_check() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions'));
        }

        $this->hourly_check_accounts();
        wp_redirect(admin_url('admin.php?page=vd-settings&tab=advanced&check=triggered'));
        exit;
    }

    /**
     * Unschedule all events (called on deactivation)
     *
     * @since 1.0.0
     */
    public function unschedule_events() {
        wp_clear_scheduled_hook('vd_daily_cleanup');
        wp_clear_scheduled_hook('vd_hourly_check');
        wp_clear_scheduled_hook('vd_capacity_check');

        VD_Logger::log('Cron: All scheduled events unscheduled', 'info');
    }
}
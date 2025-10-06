<?php
/**
 * Admin dashboard page for VD License Manager
 *
 * Displays overview statistics, warnings, and recent activity
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Admin Dashboard class
 *
 * Renders the main dashboard page with stats and activity
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Dashboard {

    /**
     * Render dashboard page
     *
     * Main method to display the dashboard interface
     *
     * @since 1.0.0
     */
    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $stats = self::get_stats();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('VD License Manager Dashboard', 'vd-license-manager'); ?></h1>

            <!-- Overview Cards -->
            <div class="vd-dashboard-cards">
                <?php self::render_stat_card(
                    __('Total Accounts', 'vd-license-manager'),
                    $stats['accounts']['active'] . '/' . $stats['accounts']['total'],
                    'dashicons-groups',
                    'blue'
                ); ?>

                <?php self::render_stat_card(
                    __('Active Licenses', 'vd-license-manager'),
                    $stats['licenses']['active'],
                    'dashicons-admin-network',
                    'green'
                ); ?>

                <?php self::render_stat_card(
                    __('Capacity Usage', 'vd-license-manager'),
                    $stats['capacity']['percentage'] . '%',
                    'dashicons-chart-pie',
                    $stats['capacity']['percentage'] > 90 ? 'red' : ($stats['capacity']['percentage'] > 70 ? 'orange' : 'green')
                ); ?>

                <?php self::render_stat_card(
                    __('Pending Devices', 'vd-license-manager'),
                    $stats['devices']['pending'],
                    'dashicons-smartphone',
                    $stats['devices']['pending'] > 0 ? 'orange' : 'green'
                ); ?>
            </div>

            <div class="vd-dashboard-content">
                <!-- Warnings Section -->
                <div class="vd-warnings">
                    <h2><?php echo esc_html__('Warnings & Alerts', 'vd-license-manager'); ?></h2>
                    <?php self::render_warnings($stats); ?>
                </div>

                <!-- Recent Activity Section -->
                <div class="vd-activity">
                    <h2><?php echo esc_html__('Recent Activity', 'vd-license-manager'); ?></h2>
                    <?php self::render_recent_activity(); ?>
                </div>
            </div>
        </div>

        <style>
        .vd-dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .vd-stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        .vd-stat-card.blue { border-left: 4px solid #0073aa; }
        .vd-stat-card.green { border-left: 4px solid #46b450; }
        .vd-stat-card.orange { border-left: 4px solid #ffb900; }
        .vd-stat-card.red { border-left: 4px solid #dc3232; }
        .vd-dashboard-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .vd-warnings, .vd-activity {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        @media (max-width: 960px) {
            .vd-dashboard-content { grid-template-columns: 1fr; }
        }
        </style>
        <?php
    }

    /**
     * Get dashboard statistics
     *
     * Queries database for overview stats
     *
     * @since 1.0.0
     * @return array Statistics data
     */
    private static function get_stats() {
        global $wpdb;

        // Get account stats
        $account_stats = $wpdb->get_results(
            "SELECT is_active, COUNT(*) as count
             FROM {$wpdb->prefix}vd_provider_accounts
             GROUP BY is_active",
            ARRAY_A
        );

        $accounts = array('active' => 0, 'total' => 0);
        foreach ($account_stats as $stat) {
            $accounts['total'] += (int) $stat['count'];
            if ($stat['is_active']) {
                $accounts['active'] = (int) $stat['count'];
            }
        }

        // Get license stats
        $active_licenses = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lmfwc_licenses WHERE status = 'active'"
        );

        // Get capacity stats
        $total_capacity = (int) $wpdb->get_var(
            "SELECT SUM(capacity) FROM {$wpdb->prefix}vd_provider_accounts WHERE is_active = 1"
        );

        $used_capacity = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_cookie_assignments WHERE status = 'active'"
        );

        $capacity_percentage = $total_capacity > 0 ? round(($used_capacity / $total_capacity) * 100) : 0;

        // Get pending devices
        $pending_devices = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_license_devices WHERE status = 'pending'"
        );

        return array(
            'accounts' => $accounts,
            'licenses' => array('active' => $active_licenses),
            'capacity' => array(
                'used' => $used_capacity,
                'total' => $total_capacity,
                'percentage' => $capacity_percentage
            ),
            'devices' => array('pending' => $pending_devices)
        );
    }

    /**
     * Render stat card
     *
     * @since 1.0.0
     * @param string $title Card title
     * @param string $value Card value
     * @param string $icon Dashicon class
     * @param string $color Card color theme
     */
    private static function render_stat_card($title, $value, $icon, $color) {
        ?>
        <div class="vd-stat-card <?php echo esc_attr($color); ?>">
            <div class="dashicons <?php echo esc_attr($icon); ?>" style="font-size: 32px; margin-bottom: 10px;"></div>
            <h3 style="margin: 10px 0 5px 0; font-size: 24px;"><?php echo esc_html($value); ?></h3>
            <p style="margin: 0; color: #666;"><?php echo esc_html($title); ?></p>
        </div>
        <?php
    }

    /**
     * Render warnings section
     *
     * @since 1.0.0
     * @param array $stats Dashboard statistics
     */
    private static function render_warnings($stats) {
        global $wpdb;

        $warnings = array();

        // Check capacity warnings
        if ($stats['capacity']['percentage'] > 90) {
            $warnings[] = array(
                'message' => sprintf(__('System capacity at %d%% - consider adding more accounts', 'vd-license-manager'), $stats['capacity']['percentage']),
                'link' => admin_url('admin.php?page=vd-provider-accounts')
            );
        }

        // Check pending devices
        if ($stats['devices']['pending'] > 0) {
            $warnings[] = array(
                'message' => sprintf(__('%d devices pending approval', 'vd-license-manager'), $stats['devices']['pending']),
                'link' => admin_url('admin.php?page=vd-devices')
            );
        }

        // Check accounts expiring soon
        $expiring_accounts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_provider_accounts
             WHERE is_active = 1 AND expires_at IS NOT NULL
             AND expires_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)"
        );

        if ($expiring_accounts > 0) {
            $warnings[] = array(
                'message' => sprintf(__('%d accounts expiring within 7 days', 'vd-license-manager'), $expiring_accounts),
                'link' => admin_url('admin.php?page=vd-provider-accounts')
            );
        }

        if (empty($warnings)) {
            echo '<p style="color: #46b450;">' . esc_html__('No warnings at this time.', 'vd-license-manager') . '</p>';
        } else {
            echo '<ul>';
            foreach ($warnings as $warning) {
                self::render_warning($warning['message'], $warning['link']);
            }
            echo '</ul>';
        }
    }

    /**
     * Render warning item
     *
     * @since 1.0.0
     * @param string $message Warning message
     * @param string $link Action link
     */
    private static function render_warning($message, $link) {
        ?>
        <li style="margin-bottom: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <span class="dashicons dashicons-warning" style="color: #856404; margin-right: 5px;"></span>
            <?php echo esc_html($message); ?>
            <a href="<?php echo esc_url($link); ?>" style="margin-left: 10px;"><?php echo esc_html__('View', 'vd-license-manager'); ?></a>
        </li>
        <?php
    }

    /**
     * Render recent activity
     *
     * @since 1.0.0
     */
    private static function render_recent_activity() {
        global $wpdb;

        $activities = $wpdb->get_results(
            "SELECT 'access' as type, action, status, reason, accessed_at as timestamp
             FROM {$wpdb->prefix}vd_license_access_log
             WHERE accessed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)

             UNION ALL

             SELECT 'fetch' as type, 'account_fetch' as action, status, '' as reason, accessed_at as timestamp
             FROM {$wpdb->prefix}vd_account_fetch_log
             WHERE accessed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)

             ORDER BY timestamp DESC
             LIMIT 10"
        );

        if (empty($activities)) {
            echo '<p>' . esc_html__('No recent activity.', 'vd-license-manager') . '</p>';
            return;
        }

        echo '<ul>';
        foreach ($activities as $activity) {
            self::render_activity_item($activity);
        }
        echo '</ul>';
    }

    /**
     * Render activity item
     *
     * @since 1.0.0
     * @param object $item Activity item data
     */
    private static function render_activity_item($item) {
        $icon = $item->type === 'fetch' ? 'dashicons-download' : 'dashicons-admin-users';
        $color = $item->status === 'success' ? '#46b450' : '#dc3232';
        ?>
        <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
            <span class="dashicons <?php echo esc_attr($icon); ?>" style="color: <?php echo esc_attr($color); ?>; margin-right: 8px;"></span>
            <strong><?php echo esc_html(ucfirst($item->action)); ?>:</strong>
            <span style="color: <?php echo esc_attr($color); ?>;"><?php echo esc_html($item->status); ?></span>
            <small style="float: right; color: #666;">
                <?php echo esc_html(VD_DateTime::relative_time($item->timestamp)); ?>
            </small>
        </li>
        <?php
    }
}
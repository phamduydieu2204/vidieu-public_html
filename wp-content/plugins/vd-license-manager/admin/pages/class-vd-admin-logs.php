<?php
/**
 * Logs management page for VD License Manager
 *
 * Displays access logs and fetch logs with filtering and export
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
 * VD Admin Logs class
 *
 * Manages log viewing, filtering, and CSV export
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Logs {

    /**
     * Render logs page with tabs
     *
     * @since 1.0.0
     */
    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Handle CSV export
        if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            self::handle_export_csv();
            return;
        }

        $active_tab = $_GET['tab'] ?? 'access';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('System Logs', 'vd-license-manager'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=vd-logs&tab=access" class="nav-tab <?php echo $active_tab === 'access' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Access Logs', 'vd-license-manager'); ?>
                </a>
                <a href="?page=vd-logs&tab=fetch" class="nav-tab <?php echo $active_tab === 'fetch' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Fetch Logs', 'vd-license-manager'); ?>
                </a>
            </h2>

            <?php if ($active_tab === 'access'): ?>
                <?php self::render_access_logs(); ?>
            <?php else: ?>
                <?php self::render_fetch_logs(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render access logs tab
     *
     * @since 1.0.0
     */
    private static function render_access_logs() {
        global $wpdb;

        $per_page = 50;
        $current_page = max(1, (int) ($_GET['paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;

        // Build WHERE clause
        $where_clauses = array();
        $where_values = array();

        if (!empty($_GET['date_from'])) {
            $where_clauses[] = "al.access_time >= %s";
            $where_values[] = sanitize_text_field($_GET['date_from']) . ' 00:00:00';
        }

        if (!empty($_GET['date_to'])) {
            $where_clauses[] = "al.access_time <= %s";
            $where_values[] = sanitize_text_field($_GET['date_to']) . ' 23:59:59';
        }

        if (!empty($_GET['license_filter'])) {
            $license_search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['license_filter'])) . '%';
            $where_clauses[] = "l.license_key LIKE %s";
            $where_values[] = $license_search;
        }

        if (!empty($_GET['device_filter'])) {
            $device_search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['device_filter'])) . '%';
            $where_clauses[] = "al.device_fp LIKE %s";
            $where_values[] = $device_search;
        }

        if (!empty($_GET['status_filter'])) {
            $where_clauses[] = "al.status = %s";
            $where_values[] = sanitize_text_field($_GET['status_filter']);
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        // Get total count
        $count_query = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}vd_license_access_log al
            LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON al.license_id = l.id
            $where_sql
        ";

        $total_items = !empty($where_values)
            ? $wpdb->get_var($wpdb->prepare($count_query, $where_values))
            : $wpdb->get_var($count_query);

        // Get logs
        $logs_query = "
            SELECT
                al.log_id,
                al.access_time,
                al.device_fp,
                al.ip_address,
                al.action,
                al.status,
                al.user_agent,
                l.license_key,
                df.fp_data
            FROM {$wpdb->prefix}vd_license_access_log al
            LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON al.license_id = l.id
            LEFT JOIN {$wpdb->prefix}vd_device_fingerprints df ON al.device_fp = df.fp_hash
            $where_sql
            ORDER BY al.access_time DESC
            LIMIT %d OFFSET %d
        ";

        $query_values = array_merge($where_values, array($per_page, $offset));
        $logs = $wpdb->get_results($wpdb->prepare($logs_query, $query_values), ARRAY_A);

        self::render_filters_section('access');
        self::render_access_logs_table($logs, $current_page, $total_items, $per_page);
    }

    /**
     * Render fetch logs tab
     *
     * @since 1.0.0
     */
    private static function render_fetch_logs() {
        global $wpdb;

        $per_page = 50;
        $current_page = max(1, (int) ($_GET['paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;

        // Build WHERE clause
        $where_clauses = array();
        $where_values = array();

        if (!empty($_GET['date_from'])) {
            $where_clauses[] = "fl.fetch_time >= %s";
            $where_values[] = sanitize_text_field($_GET['date_from']) . ' 00:00:00';
        }

        if (!empty($_GET['date_to'])) {
            $where_clauses[] = "fl.fetch_time <= %s";
            $where_values[] = sanitize_text_field($_GET['date_to']) . ' 23:59:59';
        }

        if (!empty($_GET['license_filter'])) {
            $license_search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['license_filter'])) . '%';
            $where_clauses[] = "l.license_key LIKE %s";
            $where_values[] = $license_search;
        }

        if (!empty($_GET['device_filter'])) {
            $device_search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['device_filter'])) . '%';
            $where_clauses[] = "fl.device_fp LIKE %s";
            $where_values[] = $device_search;
        }

        if (!empty($_GET['status_filter'])) {
            $where_clauses[] = "fl.status = %s";
            $where_values[] = sanitize_text_field($_GET['status_filter']);
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        // Get total count
        $count_query = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}vd_account_fetch_log fl
            LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON fl.license_id = l.id
            $where_sql
        ";

        $total_items = !empty($where_values)
            ? $wpdb->get_var($wpdb->prepare($count_query, $where_values))
            : $wpdb->get_var($count_query);

        // Get logs
        $logs_query = "
            SELECT
                fl.log_id,
                fl.fetch_time,
                fl.device_fp,
                fl.status,
                fl.fields_fetched,
                l.license_key,
                pa.name as account_name,
                pa.account_login
            FROM {$wpdb->prefix}vd_account_fetch_log fl
            LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON fl.license_id = l.id
            LEFT JOIN {$wpdb->prefix}vd_provider_accounts pa ON fl.provider_account_id = pa.account_id
            $where_sql
            ORDER BY fl.fetch_time DESC
            LIMIT %d OFFSET %d
        ";

        $query_values = array_merge($where_values, array($per_page, $offset));
        $logs = $wpdb->get_results($wpdb->prepare($logs_query, $query_values), ARRAY_A);

        self::render_filters_section('fetch');
        self::render_fetch_logs_table($logs, $current_page, $total_items, $per_page);
    }

    /**
     * Handle CSV export
     *
     * @since 1.0.0
     */
    private static function handle_export_csv() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'export_logs_csv')) {
            wp_die(__('Security check failed.'));
        }

        $type = sanitize_text_field($_GET['type'] ?? 'access');
        $date = current_time('Y-m-d');
        $filename = "vd-logs-{$type}-{$date}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if ($type === 'access') {
            fputcsv($output, array('Time', 'License', 'Device', 'IP/Location', 'Action', 'Status'));

            global $wpdb;
            $logs = $wpdb->get_results("
                SELECT al.access_time, l.license_key, al.device_fp, al.ip_address, al.action, al.status
                FROM {$wpdb->prefix}vd_license_access_log al
                LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON al.license_id = l.id
                ORDER BY al.access_time DESC
                LIMIT 1000
            ", ARRAY_A);

            foreach ($logs as $log) {
                $ip_display = self::get_privacy_safe_ip($log['ip_address'], $log['access_time']);
                fputcsv($output, array(
                    $log['access_time'],
                    substr($log['license_key'], 0, 20) . '...',
                    substr($log['device_fp'], 0, 12) . '...',
                    $ip_display,
                    $log['action'],
                    $log['status']
                ));
            }
        } else {
            fputcsv($output, array('Time', 'License', 'Device', 'Account', 'Fields', 'Status'));

            global $wpdb;
            $logs = $wpdb->get_results("
                SELECT fl.fetch_time, l.license_key, fl.device_fp, pa.name, fl.fields_fetched, fl.status
                FROM {$wpdb->prefix}vd_account_fetch_log fl
                LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON fl.license_id = l.id
                LEFT JOIN {$wpdb->prefix}vd_provider_accounts pa ON fl.provider_account_id = pa.account_id
                ORDER BY fl.fetch_time DESC
                LIMIT 1000
            ", ARRAY_A);

            foreach ($logs as $log) {
                $fields = json_decode($log['fields_fetched'], true);
                $fields_display = is_array($fields) ? implode(', ', $fields) : '';

                fputcsv($output, array(
                    $log['fetch_time'],
                    substr($log['license_key'], 0, 20) . '...',
                    substr($log['device_fp'], 0, 12) . '...',
                    $log['name'],
                    $fields_display,
                    $log['status']
                ));
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Get privacy-safe IP display
     *
     * @since 1.0.0
     * @param string $ip_address Original IP
     * @param string $access_time Access time
     * @return string Safe IP display
     */
    private static function get_privacy_safe_ip($ip_address, $access_time) {
        $ninety_days_ago = strtotime('-90 days');
        $log_time = strtotime($access_time);

        if ($log_time < $ninety_days_ago) {
            return VD_Security::hash_ip_for_privacy($ip_address);
        }

        return $ip_address;
    }

    /**
     * Render filters section
     *
     * @since 1.0.0
     * @param string $type Tab type (access/fetch)
     */
    private static function render_filters_section($type) {
        $export_url = wp_nonce_url(
            admin_url('admin.php?page=vd-logs&action=export_csv&type=' . $type),
            'export_logs_csv'
        );
        ?>
        <div class="tablenav top">
            <form method="get" action="">
                <input type="hidden" name="page" value="vd-logs" />
                <input type="hidden" name="tab" value="<?php echo esc_attr($type); ?>" />

                <label><?php echo esc_html__('From:', 'vd-license-manager'); ?>
                    <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" />
                </label>

                <label><?php echo esc_html__('To:', 'vd-license-manager'); ?>
                    <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" />
                </label>

                <label><?php echo esc_html__('License:', 'vd-license-manager'); ?>
                    <input type="text" name="license_filter" value="<?php echo esc_attr($_GET['license_filter'] ?? ''); ?>" placeholder="<?php echo esc_attr__('License key...', 'vd-license-manager'); ?>" />
                </label>

                <label><?php echo esc_html__('Device:', 'vd-license-manager'); ?>
                    <input type="text" name="device_filter" value="<?php echo esc_attr($_GET['device_filter'] ?? ''); ?>" placeholder="<?php echo esc_attr__('Device fingerprint...', 'vd-license-manager'); ?>" />
                </label>

                <label><?php echo esc_html__('Status:', 'vd-license-manager'); ?>
                    <select name="status_filter">
                        <option value=""><?php echo esc_html__('All Statuses', 'vd-license-manager'); ?></option>
                        <option value="success" <?php selected($_GET['status_filter'] ?? '', 'success'); ?>><?php echo esc_html__('Success', 'vd-license-manager'); ?></option>
                        <option value="failed" <?php selected($_GET['status_filter'] ?? '', 'failed'); ?>><?php echo esc_html__('Failed', 'vd-license-manager'); ?></option>
                        <option value="blocked" <?php selected($_GET['status_filter'] ?? '', 'blocked'); ?>><?php echo esc_html__('Blocked', 'vd-license-manager'); ?></option>
                    </select>
                </label>

                <?php submit_button(__('Filter', 'vd-license-manager'), '', 'filter', false, array('id' => 'filter-submit')); ?>
                <a href="<?php echo esc_url($export_url); ?>" class="button"><?php echo esc_html__('Export CSV', 'vd-license-manager'); ?></a>
            </form>
        </div>
        <?php
    }

    /**
     * Render access logs table
     *
     * @since 1.0.0
     * @param array $logs Log entries
     * @param int $current_page Current page
     * @param int $total_items Total items
     * @param int $per_page Items per page
     */
    private static function render_access_logs_table($logs, $current_page, $total_items, $per_page) {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 15%;"><?php echo esc_html__('Time', 'vd-license-manager'); ?></th>
                    <th style="width: 20%;"><?php echo esc_html__('License', 'vd-license-manager'); ?></th>
                    <th style="width: 15%;"><?php echo esc_html__('Device', 'vd-license-manager'); ?></th>
                    <th style="width: 20%;"><?php echo esc_html__('IP/Location', 'vd-license-manager'); ?></th>
                    <th style="width: 15%;"><?php echo esc_html__('Action', 'vd-license-manager'); ?></th>
                    <th style="width: 15%;"><?php echo esc_html__('Status', 'vd-license-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6"><?php echo esc_html__('No access logs found.', 'vd-license-manager'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(VD_DateTime::format_datetime($log['access_time'])); ?></td>
                            <td>
                                <?php if ($log['license_key']): ?>
                                    <span title="<?php echo esc_attr($log['license_key']); ?>">
                                        <?php echo esc_html(substr($log['license_key'], 0, 20) . '...'); ?>
                                    </span>
                                <?php else: ?>
                                    <em><?php echo esc_html__('No license', 'vd-license-manager'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span title="<?php echo esc_attr($log['device_fp']); ?>" style="font-family: monospace;">
                                    <?php echo esc_html(substr($log['device_fp'], 0, 12) . '...'); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(self::get_privacy_safe_ip($log['ip_address'], $log['access_time'])); ?></td>
                            <td><?php echo esc_html(ucfirst($log['action'])); ?></td>
                            <td>
                                <span style="color: <?php echo $log['status'] === 'success' ? '#46b450' : '#dc3232'; ?>;">
                                    <?php echo esc_html(ucfirst($log['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        $total_pages = ceil($total_items / $per_page);
        if ($total_pages > 1) {
            $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page
            ));

            if ($page_links) {
                echo '<div class="tablenav bottom"><div class="tablenav-pages">' . $page_links . '</div></div>';
            }
        }
    }

    /**
     * Render fetch logs table
     *
     * @since 1.0.0
     * @param array $logs Log entries
     * @param int $current_page Current page
     * @param int $total_items Total items
     * @param int $per_page Items per page
     */
    private static function render_fetch_logs_table($logs, $current_page, $total_items, $per_page) {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 15%;"><?php echo esc_html__('Time', 'vd-license-manager'); ?></th>
                    <th style="width: 20%;"><?php echo esc_html__('License', 'vd-license-manager'); ?></th>
                    <th style="width: 15%;"><?php echo esc_html__('Device', 'vd-license-manager'); ?></th>
                    <th style="width: 20%;"><?php echo esc_html__('Account Used', 'vd-license-manager'); ?></th>
                    <th style="width: 20%;"><?php echo esc_html__('Fields Fetched', 'vd-license-manager'); ?></th>
                    <th style="width: 10%;"><?php echo esc_html__('Status', 'vd-license-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6"><?php echo esc_html__('No fetch logs found.', 'vd-license-manager'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $fields = json_decode($log['fields_fetched'], true);
                        $fields_display = is_array($fields) ? implode(', ', $fields) : '';
                        ?>
                        <tr>
                            <td><?php echo esc_html(VD_DateTime::format_datetime($log['fetch_time'])); ?></td>
                            <td>
                                <?php if ($log['license_key']): ?>
                                    <span title="<?php echo esc_attr($log['license_key']); ?>">
                                        <?php echo esc_html(substr($log['license_key'], 0, 20) . '...'); ?>
                                    </span>
                                <?php else: ?>
                                    <em><?php echo esc_html__('No license', 'vd-license-manager'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span title="<?php echo esc_attr($log['device_fp']); ?>" style="font-family: monospace;">
                                    <?php echo esc_html(substr($log['device_fp'], 0, 12) . '...'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['account_name']): ?>
                                    <strong><?php echo esc_html($log['account_name']); ?></strong><br>
                                    <small><?php echo esc_html($log['account_login']); ?></small>
                                <?php else: ?>
                                    <em><?php echo esc_html__('No account', 'vd-license-manager'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo esc_html($fields_display); ?></small>
                            </td>
                            <td>
                                <span style="color: <?php echo $log['status'] === 'success' ? '#46b450' : '#dc3232'; ?>;">
                                    <?php echo esc_html(ucfirst($log['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        $total_pages = ceil($total_items / $per_page);
        if ($total_pages > 1) {
            $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page
            ));

            if ($page_links) {
                echo '<div class="tablenav bottom"><div class="tablenav-pages">' . $page_links . '</div></div>';
            }
        }
    }
}
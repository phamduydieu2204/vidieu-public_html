<?php
/**
 * Devices management page for VD License Manager
 *
 * Displays and manages device approvals in a WP_List_Table format
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure WP_List_Table is available
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * VD Admin Devices class
 *
 * Extends WP_List_Table for device management
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Devices extends WP_List_Table {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'device',
            'plural' => 'devices',
            'ajax' => false
        ));

        // Handle actions
        $action = $_GET['action'] ?? '';
        if ($action && isset($_GET['device_id'])) {
            $this->handle_action($action, (int) $_GET['device_id']);
        }
    }

    /**
     * Get table columns
     *
     * @since 1.0.0
     * @return array Column headers
     */
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'device_fp' => __('Device', 'vd-license-manager'),
            'license_key' => __('License', 'vd-license-manager'),
            'os' => __('OS/Browser', 'vd-license-manager'),
            'last_used' => __('Last Used', 'vd-license-manager'),
            'status' => __('Status', 'vd-license-manager'),
            'actions' => __('Actions', 'vd-license-manager')
        );
    }

    /**
     * Get sortable columns
     *
     * @since 1.0.0
     * @return array Sortable columns
     */
    public function get_sortable_columns() {
        return array(
            'last_used' => array('last_used_at', true),
            'status' => array('status', false)
        );
    }

    /**
     * Prepare table items
     *
     * @since 1.0.0
     */
    public function prepare_items() {
        global $wpdb;

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Build WHERE clause
        $where_clauses = array();
        $where_values = array();

        // Search functionality
        if (!empty($_GET['s'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['s'])) . '%';
            $where_clauses[] = "(ld.device_fp LIKE %s OR l.license_key LIKE %s)";
            $where_values[] = $search;
            $where_values[] = $search;
        }

        // Status filter
        if (!empty($_GET['device_status'])) {
            $where_clauses[] = "ld.status = %s";
            $where_values[] = sanitize_text_field($_GET['device_status']);
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        // Get sorting
        $orderby = !empty($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'ld.created_at';
        $order = !empty($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

        // Count total items
        $total_query = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}vd_license_devices ld
            LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON ld.license_id = l.id
            LEFT JOIN {$wpdb->prefix}vd_device_fingerprints df ON ld.device_fp = df.fp_hash
            $where_sql
        ";

        if (!empty($where_values)) {
            $total_items = $wpdb->get_var($wpdb->prepare($total_query, $where_values));
        } else {
            $total_items = $wpdb->get_var($total_query);
        }

        // Get items
        $items_query = "
            SELECT
                ld.device_id,
                ld.license_id,
                ld.device_fp,
                ld.status,
                ld.last_used_at,
                ld.created_at,
                ld.notes,
                l.license_key,
                df.fp_data
            FROM {$wpdb->prefix}vd_license_devices ld
            LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON ld.license_id = l.id
            LEFT JOIN {$wpdb->prefix}vd_device_fingerprints df ON ld.device_fp = df.fp_hash
            $where_sql
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d
        ";

        $query_values = array_merge($where_values, array($per_page, $offset));
        $this->items = $wpdb->get_results($wpdb->prepare($items_query, $query_values), ARRAY_A);

        // Set pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    /**
     * Default column renderer
     *
     * @since 1.0.0
     * @param array $item Row data
     * @param string $column_name Column name
     * @return string Column content
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'last_used':
                return $item['last_used_at'] ? VD_DateTime::relative_time($item['last_used_at']) : '-';
            default:
                return isset($item[$column_name]) ? $item[$column_name] : '';
        }
    }

    /**
     * Checkbox column
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Checkbox HTML
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="device_ids[]" value="%s" />', $item['device_id']);
    }

    /**
     * Device fingerprint column with icon
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Device display with icon
     */
    public function column_device_fp($item) {
        $icon = $this->get_device_icon($item);
        $short_fp = substr($item['device_fp'], 0, 12) . '...';
        $full_fp = esc_attr($item['device_fp']);

        return sprintf(
            '%s <span title="%s" style="font-family: monospace;">%s</span>',
            $icon,
            $full_fp,
            $short_fp
        );
    }

    /**
     * License key column
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string License key display
     */
    public function column_license_key($item) {
        if (empty($item['license_key'])) {
            return '<em>' . __('No license', 'vd-license-manager') . '</em>';
        }

        $short_key = substr($item['license_key'], 0, 15) . '...';
        return sprintf('<span title="%s">%s</span>', esc_attr($item['license_key']), $short_key);
    }

    /**
     * OS/Browser column
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string OS and browser info
     */
    public function column_os($item) {
        if (empty($item['fp_data'])) {
            return '-';
        }

        $fp_data = json_decode($item['fp_data'], true);
        if (!is_array($fp_data)) {
            return '-';
        }

        $ua_info = VD_Data::parse_user_agent($fp_data['ua'] ?? '');
        $os = $fp_data['os'] ?? $ua_info['os'] ?? '-';
        $browser = $ua_info['browser'] ?? '-';

        return sprintf('<strong>%s</strong><br><small>%s</small>', esc_html($os), esc_html($browser));
    }

    /**
     * Status column with badges
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Status badge
     */
    public function column_status($item) {
        $status = $item['status'];
        $badges = array(
            'pending' => array('color' => '#ffb900', 'label' => __('Pending', 'vd-license-manager')),
            'approved' => array('color' => '#46b450', 'label' => __('Approved', 'vd-license-manager')),
            'blocked' => array('color' => '#dc3232', 'label' => __('Blocked', 'vd-license-manager'))
        );

        $badge = $badges[$status] ?? array('color' => '#666', 'label' => ucfirst($status));

        return sprintf(
            '<span style="background: %s; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">%s</span>',
            $badge['color'],
            $badge['label']
        );
    }

    /**
     * Actions column
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Action buttons
     */
    public function column_actions($item) {
        $actions = array();

        if ($item['status'] === 'pending') {
            $approve_url = wp_nonce_url(
                admin_url('admin.php?page=vd-devices&action=approve&device_id=' . $item['device_id']),
                'approve_device_' . $item['device_id']
            );
            $actions[] = '<a href="' . esc_url($approve_url) . '" class="button button-small button-primary">' . __('Approve', 'vd-license-manager') . '</a>';
        }

        if ($item['status'] === 'approved') {
            $block_url = wp_nonce_url(
                admin_url('admin.php?page=vd-devices&action=block&device_id=' . $item['device_id']),
                'block_device_' . $item['device_id']
            );
            $actions[] = '<a href="' . esc_url($block_url) . '" class="button button-small" onclick="return confirm(\'' .
                esc_js(__('Block this device?', 'vd-license-manager')) . '\')">' . __('Block', 'vd-license-manager') . '</a>';
        }

        return implode(' ', $actions);
    }

    /**
     * Get bulk actions
     *
     * @since 1.0.0
     * @return array Bulk actions
     */
    public function get_bulk_actions() {
        return array(
            'approve' => __('Approve', 'vd-license-manager'),
            'block' => __('Block', 'vd-license-manager'),
            'delete' => __('Delete', 'vd-license-manager')
        );
    }

    /**
     * Extra table navigation
     *
     * @since 1.0.0
     * @param string $which Top or bottom
     */
    public function extra_tablenav($which) {
        if ($which === 'top') {
            $selected_status = isset($_GET['device_status']) ? sanitize_text_field($_GET['device_status']) : '';
            ?>
            <div class="alignleft actions">
                <select name="device_status">
                    <option value=""><?php _e('All Statuses', 'vd-license-manager'); ?></option>
                    <option value="pending" <?php selected($selected_status, 'pending'); ?>><?php _e('Pending', 'vd-license-manager'); ?></option>
                    <option value="approved" <?php selected($selected_status, 'approved'); ?>><?php _e('Approved', 'vd-license-manager'); ?></option>
                    <option value="blocked" <?php selected($selected_status, 'blocked'); ?>><?php _e('Blocked', 'vd-license-manager'); ?></option>
                </select>

                <?php submit_button(__('Filter', 'vd-license-manager'), '', 'filter_action', false, array('id' => 'post-query-submit')); ?>
            </div>
            <?php
        }
    }

    /**
     * Handle device actions
     *
     * @since 1.0.0
     * @param string $action Action to perform
     * @param int $device_id Device ID
     */
    private function handle_action($action, $device_id) {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.'));
        }

        switch ($action) {
            case 'approve':
                check_admin_referer('approve_device_' . $device_id);
                $this->handle_approve($device_id);
                break;
            case 'block':
                check_admin_referer('block_device_' . $device_id);
                $this->handle_block($device_id, 'Blocked by admin');
                break;
        }
    }

    /**
     * Handle device approval
     *
     * @since 1.0.0
     * @param int $device_id Device ID to approve
     */
    private function handle_approve($device_id) {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'vd_license_devices',
            array(
                'status' => 'approved',
                'approved_by' => get_current_user_id(),
                'approved_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('device_id' => $device_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=vd-devices&message=' . urlencode(__('Device approved successfully!', 'vd-license-manager'))));
        } else {
            wp_redirect(admin_url('admin.php?page=vd-devices&message=' . urlencode(__('Failed to approve device.', 'vd-license-manager')) . '&type=error'));
        }
        exit;
    }

    /**
     * Handle device blocking
     *
     * @since 1.0.0
     * @param int $device_id Device ID to block
     * @param string $reason Block reason
     */
    private function handle_block($device_id, $reason) {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'vd_license_devices',
            array(
                'status' => 'blocked',
                'notes' => sanitize_text_field($reason),
                'updated_at' => current_time('mysql')
            ),
            array('device_id' => $device_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=vd-devices&message=' . urlencode(__('Device blocked successfully!', 'vd-license-manager'))));
        } else {
            wp_redirect(admin_url('admin.php?page=vd-devices&message=' . urlencode(__('Failed to block device.', 'vd-license-manager')) . '&type=error'));
        }
        exit;
    }

    /**
     * Get device icon based on device type
     *
     * @since 1.0.0
     * @param array $item Device item data
     * @return string Device icon emoji
     */
    private function get_device_icon($item) {
        if (empty($item['fp_data'])) {
            return '🖥️';
        }

        $fp_data = json_decode($item['fp_data'], true);
        if (!is_array($fp_data)) {
            return '🖥️';
        }

        $ua = $fp_data['ua'] ?? '';
        $os = $fp_data['os'] ?? '';

        // Mobile devices
        if (preg_match('/iPhone|iPad|Android/i', $ua)) {
            return '📱';
        }

        // Desktop/Laptop
        if (preg_match('/Windows|Mac/i', $os)) {
            return '💻';
        }

        // Default desktop
        return '🖥️';
    }
}
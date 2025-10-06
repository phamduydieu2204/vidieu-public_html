<?php
/**
 * Provider accounts list table for VD License Manager
 *
 * Displays provider accounts in a WP_List_Table format
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
 * VD Admin Accounts List class
 *
 * Extends WP_List_Table for provider accounts management
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Accounts_List extends WP_List_Table {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'provider_account',
            'plural' => 'provider_accounts',
            'ajax' => false
        ));
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
            'id' => __('ID', 'vd-license-manager'),
            'display_name' => __('Account', 'vd-license-manager'),
            'provider' => __('Provider', 'vd-license-manager'),
            'capacity' => __('Capacity', 'vd-license-manager'),
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
            'id' => array('account_id', false),
            'provider' => array('provider', false),
            'capacity' => array('capacity', false),
            'status' => array('is_active', false)
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
            $where_clauses[] = "(name LIKE %s OR account_login LIKE %s OR provider LIKE %s)";
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
        }

        // Provider filter
        if (!empty($_GET['provider'])) {
            $where_clauses[] = "provider = %s";
            $where_values[] = sanitize_text_field($_GET['provider']);
        }

        // Status filter
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $where_clauses[] = "is_active = %d";
            $where_values[] = $_GET['status'] === 'active' ? 1 : 0;
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        // Get sorting
        $orderby = !empty($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'account_id';
        $order = !empty($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

        // Count total items
        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}vd_provider_accounts $where_sql";
        if (!empty($where_values)) {
            $total_items = $wpdb->get_var($wpdb->prepare($total_query, $where_values));
        } else {
            $total_items = $wpdb->get_var($total_query);
        }

        // Get items
        $items_query = "
            SELECT account_id, name, account_login, provider, capacity, is_active, created_at
            FROM {$wpdb->prefix}vd_provider_accounts
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
            case 'id':
                return $item['account_id'];
            case 'provider':
                return ucfirst($item['provider']);
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
        return sprintf('<input type="checkbox" name="account_ids[]" value="%s" />', $item['account_id']);
    }

    /**
     * Display name column with row actions
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Column content with actions
     */
    public function column_display_name($item) {
        $edit_url = admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $item['account_id']);
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=vd-provider-accounts&action=delete&id=' . $item['account_id']),
            'delete_account_' . $item['account_id']
        );

        $title = '<strong><a href="' . esc_url($edit_url) . '">' . esc_html($item['name']) . '</a></strong>';
        if (!empty($item['account_login'])) {
            $title .= '<br><small>' . esc_html($item['account_login']) . '</small>';
        }

        $actions = array(
            'edit' => '<a href="' . esc_url($edit_url) . '">' . __('Edit', 'vd-license-manager') . '</a>',
            'delete' => '<a href="' . esc_url($delete_url) . '" onclick="return confirm(\'' .
                esc_js(__('Are you sure you want to delete this account?', 'vd-license-manager')) . '\')">' .
                __('Delete', 'vd-license-manager') . '</a>'
        );

        return $title . $this->row_actions($actions);
    }

    /**
     * Capacity column with usage bar
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Capacity display with progress bar
     */
    public function column_capacity($item) {
        global $wpdb;

        $used = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_cookie_assignments WHERE provider_account_id = %d AND status = 'active'",
            $item['account_id']
        ));

        $total = (int) $item['capacity'];
        $percentage = $total > 0 ? round(($used / $total) * 100) : 0;
        $color = $percentage > 90 ? '#dc3232' : ($percentage > 70 ? '#ffb900' : '#46b450');

        return sprintf(
            '%d/%d <div style="width:50px;height:8px;background:#ddd;border-radius:4px;display:inline-block;margin-left:5px;"><div style="width:%d%%;height:100%%;background:%s;border-radius:4px;"></div></div>',
            $used,
            $total,
            $percentage,
            $color
        );
    }

    /**
     * Status column
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Status display
     */
    public function column_status($item) {
        if ($item['is_active']) {
            return '<span style="color: #46b450;">●</span> ' . __('Active', 'vd-license-manager');
        } else {
            return '<span style="color: #dc3232;">●</span> ' . __('Suspended', 'vd-license-manager');
        }
    }

    /**
     * Actions column
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Action buttons
     */
    public function column_actions($item) {
        $edit_url = admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $item['account_id']);
        return '<a href="' . esc_url($edit_url) . '" class="button button-small">' . __('Edit', 'vd-license-manager') . '</a>';
    }

    /**
     * Get bulk actions
     *
     * @since 1.0.0
     * @return array Bulk actions
     */
    public function get_bulk_actions() {
        return array(
            'delete' => __('Delete', 'vd-license-manager'),
            'activate' => __('Activate', 'vd-license-manager'),
            'suspend' => __('Suspend', 'vd-license-manager')
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
            global $wpdb;

            $providers = $wpdb->get_col("SELECT DISTINCT provider FROM {$wpdb->prefix}vd_provider_accounts ORDER BY provider");
            $selected_provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
            $selected_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
            ?>
            <div class="alignleft actions">
                <select name="provider">
                    <option value=""><?php _e('All Providers', 'vd-license-manager'); ?></option>
                    <?php foreach ($providers as $provider): ?>
                        <option value="<?php echo esc_attr($provider); ?>" <?php selected($selected_provider, $provider); ?>>
                            <?php echo esc_html(ucfirst($provider)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status">
                    <option value=""><?php _e('All Statuses', 'vd-license-manager'); ?></option>
                    <option value="active" <?php selected($selected_status, 'active'); ?>><?php _e('Active', 'vd-license-manager'); ?></option>
                    <option value="suspended" <?php selected($selected_status, 'suspended'); ?>><?php _e('Suspended', 'vd-license-manager'); ?></option>
                </select>

                <?php submit_button(__('Filter', 'vd-license-manager'), '', 'filter_action', false, array('id' => 'post-query-submit')); ?>
            </div>
            <?php
        }
    }
}
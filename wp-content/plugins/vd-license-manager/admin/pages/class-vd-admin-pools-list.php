<?php
/**
 * Product pools list table for VD License Manager
 *
 * Displays product pools in a WP_List_Table format
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
 * VD Admin Pools List class
 *
 * Extends WP_List_Table for product pools management
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Pools_List extends WP_List_Table {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'product_pool',
            'plural' => 'product_pools',
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
            'name' => __('Pool Name', 'vd-license-manager'),
            'product' => __('Product', 'vd-license-manager'),
            'strategy' => __('Strategy', 'vd-license-manager'),
            'accounts_count' => __('Accounts', 'vd-license-manager'),
            'usage' => __('Usage', 'vd-license-manager'),
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
            'id' => array('pool_id', false),
            'name' => array('name', false),
            'strategy' => array('strategy', false)
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

        // Build query with usage calculation
        $query = "
            SELECT
                pp.pool_id,
                pp.name,
                pp.product_id,
                pp.strategy,
                pp.is_active,
                COUNT(poa.provider_account_id) as accounts_count,
                COALESCE(SUM(pa.capacity), 0) as total_capacity,
                COALESCE(usage_data.used_capacity, 0) as used_capacity
            FROM {$wpdb->prefix}vd_product_pools pp
            LEFT JOIN {$wpdb->prefix}vd_pool_accounts poa ON pp.pool_id = poa.pool_id AND poa.is_active = 1
            LEFT JOIN {$wpdb->prefix}vd_provider_accounts pa ON poa.provider_account_id = pa.account_id AND pa.is_active = 1
            LEFT JOIN (
                SELECT
                    pp2.pool_id,
                    COUNT(ca.assignment_id) as used_capacity
                FROM {$wpdb->prefix}vd_product_pools pp2
                LEFT JOIN {$wpdb->prefix}vd_pool_accounts poa2 ON pp2.pool_id = poa2.pool_id
                LEFT JOIN {$wpdb->prefix}vd_cookie_assignments ca ON poa2.provider_account_id = ca.provider_account_id AND ca.status = 'active'
                GROUP BY pp2.pool_id
            ) usage_data ON pp.pool_id = usage_data.pool_id
            WHERE pp.is_active = 1
            GROUP BY pp.pool_id
        ";

        // Add search functionality
        if (!empty($_GET['s'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['s'])) . '%';
            $query .= $wpdb->prepare(" AND pp.name LIKE %s", $search);
        }

        // Add sorting
        $orderby = !empty($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'pool_id';
        $order = !empty($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
        $query .= " ORDER BY $orderby $order";

        // Count total items for pagination
        $count_query = "SELECT COUNT(*) FROM ({$query}) as count_table";
        $total_items = $wpdb->get_var($count_query);

        // Add pagination
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);

        $this->items = $wpdb->get_results($query, ARRAY_A);

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
                return $item['pool_id'];
            case 'accounts_count':
                return $item['accounts_count'];
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
        return sprintf('<input type="checkbox" name="pool_ids[]" value="%s" />', $item['pool_id']);
    }

    /**
     * Pool name column with row actions
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Column content with actions
     */
    public function column_name($item) {
        $edit_url = admin_url('admin.php?page=vd-product-pools&action=edit&id=' . $item['pool_id']);
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=vd-product-pools&action=delete&id=' . $item['pool_id']),
            'delete_pool_' . $item['pool_id']
        );

        $title = '<strong><a href="' . esc_url($edit_url) . '">' . esc_html($item['name']) . '</a></strong>';

        $actions = array(
            'edit' => '<a href="' . esc_url($edit_url) . '">' . __('Edit', 'vd-license-manager') . '</a>',
            'delete' => '<a href="' . esc_url($delete_url) . '" onclick="return confirm(\'' .
                esc_js(__('Are you sure you want to delete this pool?', 'vd-license-manager')) . '\')">' .
                __('Delete', 'vd-license-manager') . '</a>'
        );

        return $title . $this->row_actions($actions);
    }

    /**
     * Product column
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Product name from WooCommerce
     */
    public function column_product($item) {
        if (empty($item['product_id'])) {
            return '<em>' . __('No product assigned', 'vd-license-manager') . '</em>';
        }

        $product_title = get_the_title($item['product_id']);
        if (empty($product_title)) {
            return '<em>' . __('Product not found', 'vd-license-manager') . '</em>';
        }

        $product_url = admin_url('post.php?post=' . $item['product_id'] . '&action=edit');
        return '<a href="' . esc_url($product_url) . '" target="_blank">' . esc_html($product_title) . '</a>';
    }

    /**
     * Strategy column with badge styling
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Strategy badge
     */
    public function column_strategy($item) {
        $strategies = array(
            'sticky' => array('label' => __('Sticky', 'vd-license-manager'), 'color' => '#0073aa'),
            'weighted' => array('label' => __('Weighted', 'vd-license-manager'), 'color' => '#46b450'),
            'priority' => array('label' => __('Priority', 'vd-license-manager'), 'color' => '#ffb900')
        );

        $strategy = $strategies[$item['strategy']] ?? array('label' => ucfirst($item['strategy']), 'color' => '#666');

        return sprintf(
            '<span style="background: %s; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">%s</span>',
            $strategy['color'],
            $strategy['label']
        );
    }

    /**
     * Usage column with progress bar
     *
     * @since 1.0.0
     * @param array $item Row data
     * @return string Usage display with progress bar
     */
    public function column_usage($item) {
        $total = (int) $item['total_capacity'];
        $used = (int) $item['used_capacity'];

        if ($total === 0) {
            return '<em>' . __('No capacity', 'vd-license-manager') . '</em>';
        }

        $percentage = round(($used / $total) * 100);

        // Color coding
        if ($percentage < 50) {
            $color = '#46b450'; // Green
        } elseif ($percentage < 80) {
            $color = '#ffb900'; // Yellow
        } else {
            $color = '#dc3232'; // Red
        }

        return sprintf(
            '%d/%d (%d%%) <div style="width:60px;height:8px;background:#ddd;border-radius:4px;display:inline-block;margin-left:5px;vertical-align:middle;"><div style="width:%d%%;height:100%%;background:%s;border-radius:4px;"></div></div>',
            $used,
            $total,
            $percentage,
            $percentage,
            $color
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
        $edit_url = admin_url('admin.php?page=vd-product-pools&action=edit&id=' . $item['pool_id']);
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
            'delete' => __('Delete', 'vd-license-manager')
        );
    }
}
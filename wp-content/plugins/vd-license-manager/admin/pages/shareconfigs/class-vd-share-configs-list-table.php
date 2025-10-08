<?php
/**
 * Share Configs List Table
 *
 * Displays share configs in a WordPress admin table
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Share Configs List Table Class
 *
 * Extends WP_List_Table for share configs display
 *
 * @package VD_License_Manager
 * @since 1.0.1
 */
class VD_Share_Configs_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 1.0.1
	 */
	public function __construct() {
		parent::__construct(array(
			'singular' => 'config',
			'plural'   => 'configs',
			'ajax'     => false
		));
	}

	/**
	 * Get table columns
	 *
	 * @since 1.0.1
	 * @return array Column names and labels
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'id'           => __('ID', 'vd-license-manager'),
			'product_name' => __('Sản phẩm', 'vd-license-manager'),
			'max_profiles' => __('Max Profiles', 'vd-license-manager'),
			'max_devices'  => __('Max Devices', 'vd-license-manager'),
			'duration'     => __('Thời hạn', 'vd-license-manager'),
			'auto_rotate'  => __('Tự động xoay', 'vd-license-manager'),
			'created_at'   => __('Ngày tạo', 'vd-license-manager')
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @since 1.0.1
	 * @return array Sortable columns
	 */
	public function get_sortable_columns() {
		return array(
			'id'         => array('id', true),  // true = already sorted DESC
			'product_id' => array('product_id', false),
			'created_at' => array('created_at', false)
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @since 1.0.1
	 * @return array Bulk actions
	 */
	public function get_bulk_actions() {
		return array(
			'bulk-delete' => __('Xóa', 'vd-license-manager')
		);
	}

	/**
	 * Render checkbox column
	 *
	 * @since 1.0.1
	 * @param object $item Config item
	 * @return string Checkbox HTML
	 */
	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="config[]" value="%d" />',
			$item->id
		);
	}

	/**
	 * Render ID column with row actions
	 *
	 * @since 1.0.1
	 * @param object $item Config item
	 * @return string Column HTML
	 */
	public function column_id($item) {
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=%s&action=%s&config=%d">%s</a>',
				esc_attr($_REQUEST['page']),
				'edit',
				$item->id,
				__('Sửa', 'vd-license-manager')
			),
			'delete' => sprintf(
				'<a href="?page=%s&action=%s&config=%d&_wpnonce=%s" onclick="return confirm(\'%s\')" style="color: #dc3232;">%s</a>',
				esc_attr($_REQUEST['page']),
				'delete',
				$item->id,
				wp_create_nonce('delete-config_' . $item->id),
				esc_js(__('Bạn có chắc muốn xóa cấu hình này?', 'vd-license-manager')),
				__('Xóa', 'vd-license-manager')
			)
		);

		return sprintf('%1$s %2$s', $item->id, $this->row_actions($actions));
	}

	/**
	 * Render product name column
	 *
	 * @since 1.0.1
	 * @param object $item Config item
	 * @return string Column HTML
	 */
	public function column_product_name($item) {
		if (empty($item->product_name)) {
			return sprintf(
				'<span style="color: #dc3232;">%s <small>(ID: %d)</small></span>',
				__('⚠ Sản phẩm không tồn tại', 'vd-license-manager'),
				$item->product_id
			);
		}

		// Link to edit product in WooCommerce
		return sprintf(
			'<a href="%s" target="_blank" title="%s">%s</a> <small style="color: #666;">(ID: %d)</small>',
			admin_url('post.php?post=' . $item->product_id . '&action=edit'),
			__('Chỉnh sửa sản phẩm trong WooCommerce', 'vd-license-manager'),
			esc_html($item->product_name),
			$item->product_id
		);
	}

	/**
	 * Render max profiles column
	 *
	 * @since 1.0.1
	 * @param object $item Config item
	 * @return string Column HTML
	 */
	public function column_max_profiles($item) {
		return sprintf(
			'<span class="badge" style="background: #007cba; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;">%d</span>',
			$item->max_profiles
		);
	}

	/**
	 * Render max devices column
	 *
	 * @since 1.0.1
	 * @param object $item Config item
	 * @return string Column HTML
	 */
	public function column_max_devices($item) {
		return sprintf(
			'<span class="badge" style="background: #00a32a; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;">%d</span>',
			$item->max_devices_per_profile
		);
	}

	/**
	 * Render duration column
	 *
	 * @since 1.0.1
	 * @param object $item Config item
	 * @return string Column HTML
	 */
	public function column_duration($item) {
		return sprintf(
			'<span style="color: #646970;">%d %s</span>',
			$item->sharing_duration_days,
			__('ngày', 'vd-license-manager')
		);
	}

	/**
	 * Render auto rotate column
	 *
	 * @since 1.0.1
	 * @param object $item Config item
	 * @return string Column HTML
	 */
	public function column_auto_rotate($item) {
		if ($item->auto_rotate) {
			$interval = $item->rotation_interval_days ? $item->rotation_interval_days . ' ' . __('ngày', 'vd-license-manager') : __('không xác định', 'vd-license-manager');
			return sprintf(
				'<span style="color: #00a32a;" title="%s">✓ %s</span>',
				sprintf(__('Chu kỳ: %s', 'vd-license-manager'), $interval),
				__('Có', 'vd-license-manager')
			);
		} else {
			return sprintf(
				'<span style="color: #dc3232;">✗ %s</span>',
				__('Không', 'vd-license-manager')
			);
		}
	}

	/**
	 * Render created at column
	 *
	 * @since 1.0.1
	 * @param object $item Config item
	 * @return string Column HTML
	 */
	public function column_created_at($item) {
		$date = new DateTime($item->created_at);
		return sprintf(
			'<span title="%s">%s</span>',
			$date->format('Y-m-d H:i:s'),
			$date->format('d/m/Y')
		);
	}

	/**
	 * Handle default column display
	 *
	 * @since 1.0.1
	 * @param object $item        Config item
	 * @param string $column_name Column name
	 * @return string Column HTML
	 */
	public function column_default($item, $column_name) {
		switch ($column_name) {
			default:
				return isset($item->$column_name) ? esc_html($item->$column_name) : '';
		}
	}

	/**
	 * Process bulk actions
	 *
	 * @since 1.0.1
	 */
	public function process_bulk_action() {
		// Bulk delete is handled in the controller
		// This method is required by WP_List_Table but we don't use it
	}

	/**
	 * Prepare items for display
	 *
	 * @since 1.0.1
	 */
	public function prepare_items() {
		global $wpdb;

		// Set columns
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// Handle bulk actions
		$this->process_bulk_action();

		// Pagination
		$per_page = 20;
		$current_page = $this->get_pagenum();
		$offset = ($current_page - 1) * $per_page;

		// Sorting
		$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'id';
		$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

		// Validate orderby
		$allowed_orderby = array('id', 'product_id', 'created_at');
		if (!in_array($orderby, $allowed_orderby)) {
			$orderby = 'id';
		}

		// Validate order
		$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

		// Search
		$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

		// Build query
		$configs_table = 'bz_vd_product_share_configs';
		$posts_table = $wpdb->posts;

		$where = '1=1';
		$params = array();

		if (!empty($search)) {
			$where .= ' AND (posts.post_title LIKE %s)';
			$search_term = '%' . $wpdb->esc_like($search) . '%';
			$params[] = $search_term;
		}

		// Get total items
		$total_query = "SELECT COUNT(*) FROM {$configs_table} configs
						LEFT JOIN {$posts_table} posts ON configs.product_id = posts.ID
						WHERE {$where}";

		if (!empty($params)) {
			$total_items = $wpdb->get_var($wpdb->prepare($total_query, $params));
		} else {
			$total_items = $wpdb->get_var($total_query);
		}

		// Get items
		$items_query = "SELECT
							configs.*,
							posts.post_title as product_name
						FROM {$configs_table} configs
						LEFT JOIN {$posts_table} posts ON configs.product_id = posts.ID
						WHERE {$where}
						ORDER BY configs.{$orderby} {$order}
						LIMIT %d OFFSET %d";

		$query_params = $params;
		$query_params[] = $per_page;
		$query_params[] = $offset;

		$this->items = $wpdb->get_results($wpdb->prepare($items_query, $query_params));

		// Set pagination
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));
	}

	/**
	 * Display when no items found
	 *
	 * @since 1.0.1
	 */
	public function no_items() {
		_e('Không tìm thấy cấu hình chia sẻ nào.', 'vd-license-manager');
	}
}
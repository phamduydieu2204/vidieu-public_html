<?php
/**
 * Product Pools List Table
 *
 * Displays product pools in a WordPress admin table
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
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
 * Pools List Table Class
 *
 * Extends WP_List_Table for product pools display
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Pools_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(array(
			'singular' => 'pool',
			'plural'   => 'pools',
			'ajax'     => false
		));
	}

	/**
	 * Get table columns
	 *
	 * @since 1.0.0
	 * @return array Column names and labels
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'id'           => __('ID', 'vd-license-manager'),
			'product_name' => __('Sản phẩm', 'vd-license-manager'),
			'account_name' => __('Tài khoản', 'vd-license-manager'),
			'priority'     => __('Độ ưu tiên', 'vd-license-manager'),
			'is_active'    => __('Trạng thái', 'vd-license-manager'),
			'created_at'   => __('Ngày tạo', 'vd-license-manager')
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
			'id'         => array('id', true),  // true = already sorted DESC
			'priority'   => array('priority', false),
			'is_active'  => array('is_active', false),
			'created_at' => array('created_at', false)
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 * @param object $item Pool item
	 * @return string Checkbox HTML
	 */
	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="pool[]" value="%d" />',
			$item->id
		);
	}

	/**
	 * Render ID column with row actions
	 *
	 * @since 1.0.0
	 * @param object $item Pool item
	 * @return string Column HTML
	 */
	public function column_id($item) {
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=%s&action=%s&pool=%d">%s</a>',
				esc_attr($_REQUEST['page']),
				'edit',
				$item->id,
				__('Sửa', 'vd-license-manager')
			),
			'delete' => sprintf(
				'<a href="?page=%s&action=%s&pool=%d&_wpnonce=%s" onclick="return confirm(\'%s\')">%s</a>',
				esc_attr($_REQUEST['page']),
				'delete',
				$item->id,
				wp_create_nonce('delete-pool_' . $item->id),
				esc_js(__('Bạn có chắc muốn xóa pool này?', 'vd-license-manager')),
				__('Xóa', 'vd-license-manager')
			)
		);

		return sprintf('%1$s %2$s', $item->id, $this->row_actions($actions));
	}

	/**
	 * Render product name column
	 *
	 * @since 1.0.0
	 * @param object $item Pool item
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
	 * Render account name column
	 *
	 * @since 1.0.0
	 * @param object $item Pool item
	 * @return string Column HTML
	 */
	public function column_account_name($item) {
		if (empty($item->account_name)) {
			return sprintf(
				'<span style="color: #dc3232;">%s <small>(ID: %d)</small></span>',
				__('⚠ Tài khoản không tồn tại', 'vd-license-manager'),
				$item->account_id
			);
		}

		// Provider icon
		$provider_icon = $this->get_provider_icon($item->account_provider);

		// Link to edit account
		return sprintf(
			'%s <a href="?page=vd-provider-accounts&action=edit&id=%d" title="%s">%s</a> <small style="color: #666;">(ID: %d)</small>',
			$provider_icon,
			$item->account_id,
			__('Chỉnh sửa tài khoản', 'vd-license-manager'),
			esc_html($item->account_name),
			$item->account_id
		);
	}

	/**
	 * Get provider icon
	 *
	 * @since 1.0.0
	 * @param string $provider Provider name
	 * @return string Icon HTML
	 */
	private function get_provider_icon($provider) {
		$icons = array(
			'netflix'        => '🎬',
			'spotify'        => '🎵',
			'youtube'        => '📺',
			'disney'         => '🏰',
			'disney+'        => '🏰',
			'hbo'            => '📽️',
			'hbo max'        => '📽️',
			'amazon'         => '📦',
			'amazon prime'   => '📦',
			'hulu'           => '🟢',
			'apple'          => '🍎',
			'apple tv'       => '🍎',
			'paramount'      => '⭐',
			'peacock'        => '🦚',
			'tiktok'         => '🎤',
			'twitch'         => '🎮',
			'canva'          => '🎨',
			'other'          => '📌'
		);

		$provider_name = strtolower(trim($provider ?? ''));
		return isset($icons[$provider_name]) ? $icons[$provider_name] : '📌';
	}

	/**
	 * Render priority column
	 *
	 * @since 1.0.0
	 * @param object $item Pool item
	 * @return string Column HTML
	 */
	public function column_priority($item) {
		$color = $item->priority == 0 ? '#007cba' : '#50575e';
		return sprintf(
			'<span class="priority-badge" style="background-color: %s;">%d</span>',
			$color,
			$item->priority
		);
	}

	/**
	 * Render status column
	 *
	 * @since 1.0.0
	 * @param object $item Pool item
	 * @return string Column HTML
	 */
	public function column_is_active($item) {
		if ($item->is_active) {
			return '<span class="status-badge status-active">✓ ' . __('Hoạt động', 'vd-license-manager') . '</span>';
		} else {
			return '<span class="status-badge status-inactive">✗ ' . __('Ngừng', 'vd-license-manager') . '</span>';
		}
	}

	/**
	 * Render created date column
	 *
	 * @since 1.0.0
	 * @param object $item Pool item
	 * @return string Column HTML
	 */
	public function column_created_at($item) {
		$date = date_i18n(
			get_option('date_format'),
			strtotime($item->created_at)
		);
		$time = date_i18n(
			get_option('time_format'),
			strtotime($item->created_at)
		);

		return sprintf(
			'<span title="%s %s">%s</span>',
			$date,
			$time,
			$date
		);
	}

	/**
	 * Default column renderer
	 *
	 * @since 1.0.0
	 * @param object $item        Pool item
	 * @param string $column_name Column name
	 * @return string Column HTML
	 */
	public function column_default($item, $column_name) {
		return isset($item->$column_name) ? esc_html($item->$column_name) : '';
	}

	/**
	 * Prepare table items
	 *
	 * @since 1.0.0
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
		$allowed_orderby = array('id', 'priority', 'is_active', 'created_at');
		if (!in_array($orderby, $allowed_orderby)) {
			$orderby = 'id';
		}

		// Validate order
		$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

		// Search
		$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

		// Build query
		$pools_table = 'bz_vd_product_pools';
		$posts_table = $wpdb->posts;
		$accounts_table = 'bz_vd_provider_accounts';

		$where = '1=1';
		$params = array();

		if (!empty($search)) {
			$where .= ' AND (posts.post_title LIKE %s OR accounts.display_name LIKE %s OR accounts.provider LIKE %s)';
			$search_term = '%' . $wpdb->esc_like($search) . '%';
			$params[] = $search_term;
			$params[] = $search_term;
			$params[] = $search_term;
		}

		// Get total items
		$total_query = "SELECT COUNT(*) FROM {$pools_table} pools
                       LEFT JOIN {$posts_table} posts ON pools.product_id = posts.ID
                       LEFT JOIN {$accounts_table} accounts ON pools.account_id = accounts.id
                       WHERE {$where}";

		if (!empty($params)) {
			$total_query = $wpdb->prepare($total_query, $params);
		}

		$total_items = intval($wpdb->get_var($total_query));

		// Get items
		$items_query = "SELECT
                           pools.*,
                           posts.post_title as product_name,
                           accounts.display_name as account_name,
                           accounts.provider as account_provider
                       FROM {$pools_table} pools
                       LEFT JOIN {$posts_table} posts ON pools.product_id = posts.ID AND posts.post_type = 'product'
                       LEFT JOIN {$accounts_table} accounts ON pools.account_id = accounts.id
                       WHERE {$where}
                       ORDER BY pools.{$orderby} {$order}
                       LIMIT %d OFFSET %d";

		$params[] = $per_page;
		$params[] = $offset;

		$items_query = $wpdb->prepare($items_query, $params);
		$this->items = $wpdb->get_results($items_query);

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in pools list table - ' . $wpdb->last_error);
			$this->items = array();
		}

		// Set pagination
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));
	}

	/**
	 * Process bulk actions
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_action() {
		// Bulk actions are handled in the controller
	}

	/**
	 * Message when no items found
	 *
	 * @since 1.0.0
	 */
	public function no_items() {
		_e('Chưa có pool nào được tạo. <a href="?page=vd-product-pools&action=add">Tạo pool đầu tiên</a>', 'vd-license-manager');
	}
}
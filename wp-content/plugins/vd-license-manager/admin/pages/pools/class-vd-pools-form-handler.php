<?php
/**
 * Product Pools Form Handler
 *
 * Handles form submission, validation, and saving
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Pools Form Handler Class
 *
 * Static methods for processing form submissions
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Pools_Form_Handler {

	/**
	 * Process form submission
	 *
	 * @since 1.0.0
	 * @return array|null Array with 'success' (bool), 'message' (string), 'errors' (WP_Error|null)
	 */
	public static function process() {
		// Check if form was submitted
		if (!isset($_POST['action']) || $_POST['action'] !== 'save_pool') {
			return null;
		}

		// Verify nonce
		if (!isset($_POST['vd_pool_nonce']) || !wp_verify_nonce($_POST['vd_pool_nonce'], 'vd_save_pool')) {
			return array(
				'success' => false,
				'message' => __('Kiểm tra bảo mật thất bại.', 'vd-license-manager'),
				'errors' => null
			);
		}

		// Check permissions
		if (!current_user_can('manage_options')) {
			return array(
				'success' => false,
				'message' => __('Bạn không có quyền thực hiện hành động này.', 'vd-license-manager'),
				'errors' => null
			);
		}

		// Determine if add or edit
		$is_edit = isset($_POST['pool_id']) && !empty($_POST['pool_id']);
		$pool_id = $is_edit ? intval($_POST['pool_id']) : 0;

		// Collect form data
		$data = self::collect_form_data();

		// Load dependencies
		require_once VD_PLUGIN_PATH . 'includes/validators/class-vd-pool-validator.php';
		require_once VD_PLUGIN_PATH . 'includes/repositories/class-vd-pools-repository.php';

		// Sanitize data
		$data = VD_Pool_Validator::sanitize_pool_data($data);

		// Validate
		if ($is_edit) {
			$validation = VD_Pool_Validator::validate_for_update($pool_id, $data);
		} else {
			$validation = VD_Pool_Validator::validate_for_insert($data);
		}

		if (is_wp_error($validation)) {
			return array(
				'success' => false,
				'message' => __('Vui lòng kiểm tra lại thông tin.', 'vd-license-manager'),
				'errors' => $validation
			);
		}

		// Save to database
		if ($is_edit) {
			$result = VD_Pools_Repository::update_pool($pool_id, $data);
			$success_message = __('Pool đã được cập nhật thành công.', 'vd-license-manager');
		} else {
			$result = VD_Pools_Repository::insert_pool($data);
			$success_message = __('Pool đã được thêm thành công.', 'vd-license-manager');
		}

		if (is_wp_error($result)) {
			return array(
				'success' => false,
				'message' => __('Lỗi khi lưu pool: ', 'vd-license-manager') . $result->get_error_message(),
				'errors' => $result
			);
		}

		// Success - redirect
		$redirect_url = add_query_arg(
			array(
				'page' => 'vd-product-pools',
				'message' => urlencode($success_message),
				'type' => 'success'
			),
			admin_url('admin.php')
		);

		wp_redirect($redirect_url);
		exit;
	}

	/**
	 * Collect form data
	 *
	 * @since 1.0.0
	 * @return array Form data
	 */
	private static function collect_form_data() {
		$data = array();

		// Product ID (required for add, read-only for edit)
		if (isset($_POST['product_id'])) {
			$data['product_id'] = sanitize_text_field($_POST['product_id']);
		}

		// Account ID (required for add, read-only for edit)
		if (isset($_POST['account_id'])) {
			$data['account_id'] = sanitize_text_field($_POST['account_id']);
		}

		// Priority (optional, default 0)
		if (isset($_POST['priority'])) {
			$data['priority'] = sanitize_text_field($_POST['priority']);
		}

		// Active status (checkbox, default unchecked = 0)
		$data['is_active'] = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

		return $data;
	}

	/**
	 * Get pool for editing
	 *
	 * @since 1.0.0
	 * @param int $pool_id Pool ID
	 * @return object|null Pool object or null
	 */
	public static function get_pool_for_edit($pool_id) {
		require_once VD_PLUGIN_PATH . 'includes/repositories/class-vd-pools-repository.php';

		return VD_Pools_Repository::get_pool($pool_id);
	}

	/**
	 * Validate pool ID and permissions for edit
	 *
	 * @since 1.0.0
	 * @param int $pool_id Pool ID
	 * @return array|null Error array or null if valid
	 */
	public static function validate_edit_access($pool_id) {
		// Check permissions
		if (!current_user_can('manage_options')) {
			return array(
				'success' => false,
				'message' => __('Bạn không có quyền thực hiện hành động này.', 'vd-license-manager'),
				'errors' => null
			);
		}

		// Validate pool ID
		$pool_id = intval($pool_id);
		if ($pool_id <= 0) {
			return array(
				'success' => false,
				'message' => __('ID pool không hợp lệ.', 'vd-license-manager'),
				'errors' => null
			);
		}

		// Check if pool exists
		$pool = self::get_pool_for_edit($pool_id);
		if (!$pool) {
			return array(
				'success' => false,
				'message' => __('Pool không tồn tại.', 'vd-license-manager'),
				'errors' => null
			);
		}

		return null; // Valid
	}

	/**
	 * Get products for dropdown
	 *
	 * @since 1.0.0
	 * @return array Products
	 */
	public static function get_products_for_dropdown() {
		global $wpdb;

		$products = $wpdb->get_results(
			"SELECT ID, post_title
			 FROM {$wpdb->posts}
			 WHERE post_type = 'product'
			   AND post_status = 'publish'
			 ORDER BY post_title ASC"
		);

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_products_for_dropdown - ' . $wpdb->last_error);
			return array();
		}

		return $products;
	}

	/**
	 * Get accounts for dropdown
	 *
	 * @since 1.0.0
	 * @return array Accounts
	 */
	public static function get_accounts_for_dropdown() {
		global $wpdb;

		$accounts = $wpdb->get_results(
			"SELECT id, display_name, provider
			 FROM bz_vd_provider_accounts
			 WHERE status = 'active'
			 ORDER BY display_name ASC"
		);

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_accounts_for_dropdown - ' . $wpdb->last_error);
			return array();
		}

		return $accounts;
	}

	/**
	 * Check if product-account combination already exists
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID
	 * @param int $account_id Account ID
	 * @param int $exclude_pool_id Pool ID to exclude (for edit mode)
	 * @return bool True if combination exists, false otherwise
	 */
	public static function combination_exists($product_id, $account_id, $exclude_pool_id = 0) {
		global $wpdb;

		$product_id = intval($product_id);
		$account_id = intval($account_id);
		$exclude_pool_id = intval($exclude_pool_id);

		if ($product_id <= 0 || $account_id <= 0) {
			return false;
		}

		$sql = "SELECT COUNT(*) FROM bz_vd_product_pools
				WHERE product_id = %d AND account_id = %d";

		$params = array($product_id, $account_id);

		if ($exclude_pool_id > 0) {
			$sql .= " AND id != %d";
			$params[] = $exclude_pool_id;
		}

		$count = $wpdb->get_var($wpdb->prepare($sql, $params));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in combination_exists - ' . $wpdb->last_error);
			return false;
		}

		return intval($count) > 0;
	}

	/**
	 * Log form processing activity
	 *
	 * @since 1.0.0
	 * @param string $action Action performed (add/edit)
	 * @param int    $pool_id Pool ID
	 * @param array  $data Form data
	 */
	private static function log_activity($action, $pool_id, $data) {
		$user = wp_get_current_user();

		$log_message = sprintf(
			'VD License Manager: User %s (%s) %s pool %d - Product: %s, Account: %s, Priority: %s, Active: %s',
			$user->user_login,
			$user->ID,
			$action,
			$pool_id,
			isset($data['product_id']) ? $data['product_id'] : 'N/A',
			isset($data['account_id']) ? $data['account_id'] : 'N/A',
			isset($data['priority']) ? $data['priority'] : 'N/A',
			isset($data['is_active']) ? ($data['is_active'] ? 'Yes' : 'No') : 'N/A'
		);

		error_log($log_message);
	}
}
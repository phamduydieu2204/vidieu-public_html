<?php
/**
 * Share Configs Form Handler
 *
 * Handles form submission, validation, and saving
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Share Configs Form Handler Class
 *
 * Static methods for processing form submissions
 *
 * @package VD_License_Manager
 * @since 1.0.1
 */
class VD_Share_Configs_Form_Handler {

	/**
	 * Process form submission
	 *
	 * @since 1.0.1
	 * @return array|null Array with 'success' (bool), 'message' (string), 'errors' (WP_Error|null)
	 */
	public static function process() {
		// Check if form was submitted
		if (!isset($_POST['action']) || $_POST['action'] !== 'save_config') {
			return null;
		}

		// Verify nonce
		if (!isset($_POST['vd_config_nonce']) || !wp_verify_nonce($_POST['vd_config_nonce'], 'vd_save_config')) {
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
		$is_edit = isset($_POST['config_id']) && !empty($_POST['config_id']);
		$config_id = $is_edit ? intval($_POST['config_id']) : 0;

		// Collect form data
		$data = self::collect_form_data();

		// Sanitize data
		$data = VD_Share_Config_Validator::sanitize_data($data);

		// Validate
		if ($is_edit) {
			$validation = VD_Share_Config_Validator::validate_edit($data, $config_id);
		} else {
			$validation = VD_Share_Config_Validator::validate_add($data);
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
			$result = VD_Share_Config_Repository::update_config($config_id, $data);
			$success_message = __('Cấu hình đã được cập nhật thành công.', 'vd-license-manager');
		} else {
			$result = VD_Share_Config_Repository::insert_config($data);
			$success_message = __('Cấu hình đã được thêm thành công.', 'vd-license-manager');
		}

		if (is_wp_error($result)) {
			return array(
				'success' => false,
				'message' => __('Lỗi khi lưu cấu hình: ', 'vd-license-manager') . $result->get_error_message(),
				'errors' => $result
			);
		}

		// Success - redirect
		$redirect_url = add_query_arg(
			array(
				'page' => 'vd-share-configs',
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
	 * @since 1.0.1
	 * @return array Form data
	 */
	private static function collect_form_data() {
		$data = array();

		// Product ID (required for add, read-only for edit)
		if (isset($_POST['product_id'])) {
			$data['product_id'] = sanitize_text_field($_POST['product_id']);
		}

		// Max profiles (required)
		if (isset($_POST['max_profiles'])) {
			$data['max_profiles'] = sanitize_text_field($_POST['max_profiles']);
		}

		// Max devices per profile (required)
		if (isset($_POST['max_devices_per_profile'])) {
			$data['max_devices_per_profile'] = sanitize_text_field($_POST['max_devices_per_profile']);
		}

		// Sharing duration days (required)
		if (isset($_POST['sharing_duration_days'])) {
			$data['sharing_duration_days'] = sanitize_text_field($_POST['sharing_duration_days']);
		}

		// Auto rotate (checkbox, default unchecked = 0)
		$data['auto_rotate'] = isset($_POST['auto_rotate']) && $_POST['auto_rotate'] == '1' ? 1 : 0;

		// Rotation interval days (optional, only if auto_rotate is enabled)
		if (isset($_POST['rotation_interval_days']) && !empty($_POST['rotation_interval_days'])) {
			$data['rotation_interval_days'] = sanitize_text_field($_POST['rotation_interval_days']);
		} else {
			$data['rotation_interval_days'] = null;
		}

		// Allow concurrent streams (optional)
		if (isset($_POST['allow_concurrent_streams']) && !empty($_POST['allow_concurrent_streams'])) {
			$data['allow_concurrent_streams'] = sanitize_text_field($_POST['allow_concurrent_streams']);
		} else {
			$data['allow_concurrent_streams'] = null;
		}

		// Custom rules (optional)
		if (isset($_POST['custom_rules']) && !empty(trim($_POST['custom_rules']))) {
			$data['custom_rules'] = sanitize_textarea_field($_POST['custom_rules']);
		} else {
			$data['custom_rules'] = null;
		}

		return $data;
	}

	/**
	 * Get config for editing
	 *
	 * @since 1.0.1
	 * @param int $config_id Config ID
	 * @return object|null Config object or null
	 */
	public static function get_config_for_edit($config_id) {
		return VD_Share_Config_Repository::get_config($config_id);
	}

	/**
	 * Validate config ID and permissions for edit
	 *
	 * @since 1.0.1
	 * @param int $config_id Config ID
	 * @return array|null Error array or null if valid
	 */
	public static function validate_edit_access($config_id) {
		// Check permissions
		if (!current_user_can('manage_options')) {
			return array(
				'success' => false,
				'message' => __('Bạn không có quyền thực hiện hành động này.', 'vd-license-manager'),
				'errors' => null
			);
		}

		// Validate config ID
		$config_id = intval($config_id);
		if ($config_id <= 0) {
			return array(
				'success' => false,
				'message' => __('ID cấu hình không hợp lệ.', 'vd-license-manager'),
				'errors' => null
			);
		}

		// Check if config exists
		$config = self::get_config_for_edit($config_id);
		if (!$config) {
			return array(
				'success' => false,
				'message' => __('Không tìm thấy cấu hình.', 'vd-license-manager'),
				'errors' => null
			);
		}

		return null; // Valid
	}

	/**
	 * Handle add form submission
	 *
	 * @since 1.0.1
	 * @param array $post_data POST data
	 * @return array Result array
	 */
	public static function handle_add($post_data) {
		// Sanitize data
		$data = VD_Share_Config_Validator::sanitize_data($post_data);

		// Validate
		$validation = VD_Share_Config_Validator::validate_add($data);
		if (is_wp_error($validation)) {
			return array(
				'success' => false,
				'errors' => $validation
			);
		}

		// Insert
		$result = VD_Share_Config_Repository::insert_config($data);
		if (is_wp_error($result)) {
			return array(
				'success' => false,
				'errors' => $result
			);
		}

		return array(
			'success' => true,
			'message' => __('Cấu hình đã được thêm thành công.', 'vd-license-manager'),
			'config_id' => $result
		);
	}

	/**
	 * Handle edit form submission
	 *
	 * @since 1.0.1
	 * @param int   $config_id Config ID
	 * @param array $post_data POST data
	 * @return array Result array
	 */
	public static function handle_edit($config_id, $post_data) {
		// Validate access
		$access_check = self::validate_edit_access($config_id);
		if ($access_check) {
			return $access_check;
		}

		// Sanitize data
		$data = VD_Share_Config_Validator::sanitize_data($post_data);

		// Validate
		$validation = VD_Share_Config_Validator::validate_edit($data, $config_id);
		if (is_wp_error($validation)) {
			return array(
				'success' => false,
				'errors' => $validation
			);
		}

		// Update
		$result = VD_Share_Config_Repository::update_config($config_id, $data);
		if (is_wp_error($result)) {
			return array(
				'success' => false,
				'errors' => $result
			);
		}

		return array(
			'success' => true,
			'message' => __('Cấu hình đã được cập nhật thành công.', 'vd-license-manager')
		);
	}

	/**
	 * Get form defaults for new config
	 *
	 * @since 1.0.1
	 * @return array Default values
	 */
	public static function get_form_defaults() {
		return VD_Share_Config_Validator::get_defaults();
	}

	/**
	 * Prepare config data for form display
	 *
	 * @since 1.0.1
	 * @param object $config Config object from database
	 * @return object Prepared config object
	 */
	public static function prepare_config_for_form($config) {
		return VD_Share_Config_Validator::validate_config_object($config);
	}

	/**
	 * Check if product is already configured
	 *
	 * @since 1.0.1
	 * @param int $product_id Product ID
	 * @param int $exclude_id Config ID to exclude (for updates)
	 * @return bool True if product has config
	 */
	public static function product_has_config($product_id, $exclude_id = 0) {
		return VD_Share_Config_Repository::product_has_config($product_id, $exclude_id);
	}

	/**
	 * Get available products for dropdown
	 *
	 * @since 1.0.1
	 * @return array Array of product objects
	 */
	public static function get_available_products() {
		return VD_Share_Config_Repository::get_products_without_configs();
	}

	/**
	 * Get config statistics for dashboard
	 *
	 * @since 1.0.1
	 * @return array Statistics array
	 */
	public static function get_config_stats() {
		global $wpdb;

		$configs_table = 'bz_vd_product_share_configs';

		$stats = array(
			'total_configs' => $wpdb->get_var("SELECT COUNT(*) FROM {$configs_table}"),
			'auto_rotate_enabled' => $wpdb->get_var("SELECT COUNT(*) FROM {$configs_table} WHERE auto_rotate = 1"),
			'products_configured' => $wpdb->get_var("SELECT COUNT(DISTINCT product_id) FROM {$configs_table}"),
			'products_without_configs' => $wpdb->get_var("
				SELECT COUNT(*) FROM {$wpdb->posts} p
				LEFT JOIN {$configs_table} c ON p.ID = c.product_id
				WHERE p.post_type = 'product'
				  AND p.post_status = 'publish'
				  AND c.id IS NULL
			")
		);

		// Ensure all values are integers
		foreach ($stats as $key => $value) {
			$stats[$key] = intval($value);
		}

		return $stats;
	}
}
<?php
/**
 * Product Share Config Repository class for VD License Manager
 *
 * Handles all database operations for product sharing configurations
 *
 * @package VD_License_Manager
 * @subpackage Repositories
 * @since 1.0.1
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * VD Share Config Repository class
 *
 * Static methods for product share configurations database operations
 *
 * @package VD_License_Manager
 * @since 1.0.1
 */
class VD_Share_Config_Repository {

	/**
	 * Get share configs list with filters and pagination
	 *
	 * @since 1.0.1
	 * @param array $args Query arguments
	 * @return array Array of share config objects
	 */
	public static function get_configs($args = array()) {
		global $wpdb;

		$defaults = array(
			'offset'     => 0,
			'limit'      => 20,
			'product_id' => '',
			'orderby'    => 'id',
			'order'      => 'DESC'
		);

		$args = wp_parse_args($args, $defaults);

		$table = 'bz_vd_product_share_configs';
		$where_clauses = array();
		$where_values = array();

		// Build WHERE clauses
		if (!empty($args['product_id'])) {
			$where_clauses[] = 'product_id = %d';
			$where_values[] = intval($args['product_id']);
		}

		$where_sql = '';
		if (!empty($where_clauses)) {
			$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
		}

		// Validate orderby and order
		$allowed_orderby = array('id', 'product_id', 'max_profiles', 'sharing_duration_days', 'created_at');
		$orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'id';
		$order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

		$sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$where_values[] = intval($args['limit']);
		$where_values[] = intval($args['offset']);

		if (!empty($where_values)) {
			$prepared_sql = $wpdb->prepare($sql, $where_values);
		} else {
			$prepared_sql = $wpdb->prepare($sql, intval($args['limit']), intval($args['offset']));
		}

		$results = $wpdb->get_results($prepared_sql);

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_configs - ' . $wpdb->last_error);
			return array();
		}

		return $results ? $results : array();
	}

	/**
	 * Get single share config by ID
	 *
	 * @since 1.0.1
	 * @param int $config_id Config ID
	 * @return object|null Config object or null if not found
	 */
	public static function get_config($config_id) {
		global $wpdb;

		$config_id = intval($config_id);
		if ($config_id <= 0) {
			return null;
		}

		$table = 'bz_vd_product_share_configs';
		$sql = "SELECT * FROM {$table} WHERE id = %d";

		$result = $wpdb->get_row($wpdb->prepare($sql, $config_id));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_config - ' . $wpdb->last_error);
			return null;
		}

		return $result;
	}

	/**
	 * Get share config by product ID
	 *
	 * @since 1.0.1
	 * @param int $product_id Product ID
	 * @return object|null Config object or null if not found
	 */
	public static function get_config_by_product($product_id) {
		global $wpdb;

		$product_id = intval($product_id);
		if ($product_id <= 0) {
			return null;
		}

		$table = 'bz_vd_product_share_configs';
		$sql = "SELECT * FROM {$table} WHERE product_id = %d";

		$result = $wpdb->get_row($wpdb->prepare($sql, $product_id));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_config_by_product - ' . $wpdb->last_error);
			return null;
		}

		return $result;
	}

	/**
	 * Count total configs for pagination
	 *
	 * @since 1.0.1
	 * @param array $args Query arguments (same filters as get_configs)
	 * @return int Total count
	 */
	public static function get_total_count($args = array()) {
		global $wpdb;

		$table = 'bz_vd_product_share_configs';
		$where_clauses = array();
		$where_values = array();

		// Build WHERE clauses
		if (!empty($args['product_id'])) {
			$where_clauses[] = 'product_id = %d';
			$where_values[] = intval($args['product_id']);
		}

		$where_sql = '';
		if (!empty($where_clauses)) {
			$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
		}

		$sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";

		if (!empty($where_values)) {
			$prepared_sql = $wpdb->prepare($sql, $where_values);
		} else {
			$prepared_sql = $sql;
		}

		$count = $wpdb->get_var($prepared_sql);

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_total_count - ' . $wpdb->last_error);
			return 0;
		}

		return intval($count);
	}

	/**
	 * Insert new share config
	 *
	 * @since 1.0.1
	 * @param array $data Config data
	 * @return int|WP_Error Config ID on success, WP_Error on failure
	 */
	public static function insert_config($data) {
		global $wpdb;

		// Validate data
		$validation = VD_Share_Config_Validator::validate_add($data);
		if (is_wp_error($validation)) {
			return $validation;
		}

		$table = 'bz_vd_product_share_configs';

		// Prepare data for insertion
		$insert_data = array(
			'product_id'               => intval($data['product_id']),
			'max_profiles'            => intval($data['max_profiles']),
			'max_devices_per_profile' => intval($data['max_devices_per_profile']),
			'sharing_duration_days'   => intval($data['sharing_duration_days']),
			'auto_rotate'            => !empty($data['auto_rotate']) ? 1 : 0,
			'rotation_interval_days' => !empty($data['rotation_interval_days']) ? intval($data['rotation_interval_days']) : null,
			'allow_concurrent_streams' => !empty($data['allow_concurrent_streams']) ? intval($data['allow_concurrent_streams']) : null,
			'custom_rules'           => !empty($data['custom_rules']) ? sanitize_textarea_field($data['custom_rules']) : null,
			'created_at'             => current_time('mysql'),
			'updated_at'             => current_time('mysql')
		);

		$format = array(
			'%d',    // product_id
			'%d',    // max_profiles
			'%d',    // max_devices_per_profile
			'%d',    // sharing_duration_days
			'%d',    // auto_rotate
			'%d',    // rotation_interval_days
			'%d',    // allow_concurrent_streams
			'%s',    // custom_rules
			'%s',    // created_at
			'%s'     // updated_at
		);

		$result = $wpdb->insert($table, $insert_data, $format);

		if ($result === false) {
			$error_msg = $wpdb->last_error ?: __('Không thể thêm cấu hình chia sẻ', 'vd-license-manager');
			error_log('VD License Manager: Insert config failed - ' . $error_msg);
			return new WP_Error('insert_failed', $error_msg);
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update existing share config
	 *
	 * @since 1.0.1
	 * @param int   $config_id Config ID
	 * @param array $data      Config data
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function update_config($config_id, $data) {
		global $wpdb;

		$config_id = intval($config_id);
		if ($config_id <= 0) {
			return new WP_Error('invalid_id', __('ID cấu hình không hợp lệ', 'vd-license-manager'));
		}

		// Check if config exists
		$existing_config = self::get_config($config_id);
		if (!$existing_config) {
			return new WP_Error('config_not_found', __('Không tìm thấy cấu hình chia sẻ', 'vd-license-manager'));
		}

		// Validate data
		$validation = VD_Share_Config_Validator::validate_edit($data, $config_id);
		if (is_wp_error($validation)) {
			return $validation;
		}

		$table = 'bz_vd_product_share_configs';

		// Prepare data for update
		$update_data = array(
			'max_profiles'            => intval($data['max_profiles']),
			'max_devices_per_profile' => intval($data['max_devices_per_profile']),
			'sharing_duration_days'   => intval($data['sharing_duration_days']),
			'auto_rotate'            => !empty($data['auto_rotate']) ? 1 : 0,
			'rotation_interval_days' => !empty($data['rotation_interval_days']) ? intval($data['rotation_interval_days']) : null,
			'allow_concurrent_streams' => !empty($data['allow_concurrent_streams']) ? intval($data['allow_concurrent_streams']) : null,
			'custom_rules'           => !empty($data['custom_rules']) ? sanitize_textarea_field($data['custom_rules']) : null,
			'updated_at'             => current_time('mysql')
		);

		$format = array(
			'%d',    // max_profiles
			'%d',    // max_devices_per_profile
			'%d',    // sharing_duration_days
			'%d',    // auto_rotate
			'%d',    // rotation_interval_days
			'%d',    // allow_concurrent_streams
			'%s',    // custom_rules
			'%s'     // updated_at
		);

		$where = array('id' => $config_id);
		$where_format = array('%d');

		$result = $wpdb->update($table, $update_data, $where, $format, $where_format);

		if ($result === false) {
			$error_msg = $wpdb->last_error ?: __('Không thể cập nhật cấu hình chia sẻ', 'vd-license-manager');
			error_log('VD License Manager: Update config failed - ' . $error_msg);
			return new WP_Error('update_failed', $error_msg);
		}

		return true;
	}

	/**
	 * Delete share config
	 *
	 * @since 1.0.1
	 * @param int $config_id Config ID
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function delete_config($config_id) {
		global $wpdb;

		$config_id = intval($config_id);
		if ($config_id <= 0) {
			return new WP_Error('invalid_id', __('ID cấu hình không hợp lệ', 'vd-license-manager'));
		}

		// Check if config exists
		$existing_config = self::get_config($config_id);
		if (!$existing_config) {
			return new WP_Error('config_not_found', __('Không tìm thấy cấu hình chia sẻ', 'vd-license-manager'));
		}

		$table = 'bz_vd_product_share_configs';
		$result = $wpdb->delete($table, array('id' => $config_id), array('%d'));

		if ($result === false) {
			$error_msg = $wpdb->last_error ?: __('Không thể xóa cấu hình chia sẻ', 'vd-license-manager');
			error_log('VD License Manager: Delete config failed - ' . $error_msg);
			return new WP_Error('delete_failed', $error_msg);
		}

		return true;
	}

	/**
	 * Check if product already has a share config
	 *
	 * @since 1.0.1
	 * @param int $product_id Product ID
	 * @param int $exclude_id Config ID to exclude from check (for updates)
	 * @return bool True if exists, false otherwise
	 */
	public static function product_has_config($product_id, $exclude_id = 0) {
		global $wpdb;

		$product_id = intval($product_id);
		$exclude_id = intval($exclude_id);

		if ($product_id <= 0) {
			return false;
		}

		$table = 'bz_vd_product_share_configs';
		$sql = "SELECT COUNT(*) FROM {$table} WHERE product_id = %d";
		$params = array($product_id);

		if ($exclude_id > 0) {
			$sql .= " AND id != %d";
			$params[] = $exclude_id;
		}

		$count = $wpdb->get_var($wpdb->prepare($sql, $params));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in product_has_config - ' . $wpdb->last_error);
			return false;
		}

		return intval($count) > 0;
	}

	/**
	 * Get all products with share configs
	 *
	 * @since 1.0.1
	 * @return array Array of product IDs
	 */
	public static function get_configured_products() {
		global $wpdb;

		$table = 'bz_vd_product_share_configs';
		$sql = "SELECT DISTINCT product_id FROM {$table} ORDER BY product_id ASC";

		$results = $wpdb->get_col($sql);

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_configured_products - ' . $wpdb->last_error);
			return array();
		}

		return $results ? array_map('intval', $results) : array();
	}
}
<?php
/**
 * Pool Validator
 *
 * Validation rules for product pools
 *
 * @package VD_License_Manager
 * @subpackage Validators
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Pool Validator Class
 *
 * Static methods for validating product pool data
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Pool_Validator {

	/**
	 * Validate pool data for insert
	 *
	 * @since 1.0.0
	 * @param array $data Pool data to validate
	 * @return true|WP_Error True if valid, WP_Error if validation fails
	 */
	public static function validate_for_insert($data) {
		$errors = new WP_Error();

		// Validate product_id
		if (empty($data['product_id'])) {
			$errors->add('product_id_required', __('Sản phẩm là bắt buộc.', 'vd-license-manager'));
		} else {
			$product_id = intval($data['product_id']);
			if ($product_id <= 0) {
				$errors->add('invalid_product_id', __('ID sản phẩm không hợp lệ.', 'vd-license-manager'));
			} elseif (!self::product_exists($product_id)) {
				$errors->add('product_not_found', __('Sản phẩm không tồn tại.', 'vd-license-manager'));
			}
		}

		// Validate account_id
		if (empty($data['account_id'])) {
			$errors->add('account_id_required', __('Tài khoản là bắt buộc.', 'vd-license-manager'));
		} else {
			$account_id = intval($data['account_id']);
			if ($account_id <= 0) {
				$errors->add('invalid_account_id', __('ID tài khoản không hợp lệ.', 'vd-license-manager'));
			} elseif (!self::account_exists($account_id)) {
				$errors->add('account_not_found', __('Tài khoản không tồn tại.', 'vd-license-manager'));
			}
		}

		// Validate uniqueness (product + account combination)
		if (!empty($data['product_id']) && !empty($data['account_id'])) {
			if (self::pool_exists($data['product_id'], $data['account_id'])) {
				$errors->add('duplicate_pool', __('Tài khoản này đã được gán cho sản phẩm này rồi.', 'vd-license-manager'));
			}
		}

		// Validate priority
		if (isset($data['priority'])) {
			$priority = intval($data['priority']);
			if ($priority < 0) {
				$errors->add('invalid_priority', __('Độ ưu tiên phải là số không âm.', 'vd-license-manager'));
			}
		}

		// Validate is_active
		if (isset($data['is_active'])) {
			$is_active = intval($data['is_active']);
			if (!in_array($is_active, array(0, 1), true)) {
				$errors->add('invalid_status', __('Trạng thái không hợp lệ.', 'vd-license-manager'));
			}
		}

		if ($errors->has_errors()) {
			return $errors;
		}

		return true;
	}

	/**
	 * Validate pool data for update
	 *
	 * @since 1.0.0
	 * @param int   $pool_id Pool ID being updated
	 * @param array $data    Pool data to validate
	 * @return true|WP_Error True if valid, WP_Error if validation fails
	 */
	public static function validate_for_update($pool_id, $data) {
		$errors = new WP_Error();

		// Validate pool_id
		$pool_id = intval($pool_id);
		if ($pool_id <= 0) {
			$errors->add('invalid_pool_id', __('ID pool không hợp lệ.', 'vd-license-manager'));
			return $errors;
		}

		// Validate pool exists
		require_once plugin_dir_path(dirname(__FILE__)) . 'repositories/class-vd-pools-repository.php';

		$existing_pool = VD_Pools_Repository::get_pool($pool_id);
		if (!$existing_pool) {
			$errors->add('pool_not_found', __('Pool không tồn tại.', 'vd-license-manager'));
			return $errors;
		}

		// Validate product_id if provided
		if (isset($data['product_id']) && !empty($data['product_id'])) {
			$product_id = intval($data['product_id']);
			if ($product_id <= 0) {
				$errors->add('invalid_product_id', __('ID sản phẩm không hợp lệ.', 'vd-license-manager'));
			} elseif (!self::product_exists($product_id)) {
				$errors->add('product_not_found', __('Sản phẩm không tồn tại.', 'vd-license-manager'));
			}
		}

		// Validate account_id if provided
		if (isset($data['account_id']) && !empty($data['account_id'])) {
			$account_id = intval($data['account_id']);
			if ($account_id <= 0) {
				$errors->add('invalid_account_id', __('ID tài khoản không hợp lệ.', 'vd-license-manager'));
			} elseif (!self::account_exists($account_id)) {
				$errors->add('account_not_found', __('Tài khoản không tồn tại.', 'vd-license-manager'));
			}
		}

		// Check uniqueness if product_id or account_id changed
		$new_product_id = isset($data['product_id']) ? intval($data['product_id']) : $existing_pool->product_id;
		$new_account_id = isset($data['account_id']) ? intval($data['account_id']) : $existing_pool->account_id;

		// Only check if combination changed
		if ($new_product_id != $existing_pool->product_id || $new_account_id != $existing_pool->account_id) {
			if (self::pool_exists($new_product_id, $new_account_id)) {
				$errors->add('duplicate_pool', __('Tài khoản này đã được gán cho sản phẩm này rồi.', 'vd-license-manager'));
			}
		}

		// Validate priority if provided
		if (isset($data['priority'])) {
			$priority = intval($data['priority']);
			if ($priority < 0) {
				$errors->add('invalid_priority', __('Độ ưu tiên phải là số không âm.', 'vd-license-manager'));
			}
		}

		// Validate is_active if provided
		if (isset($data['is_active'])) {
			$is_active = intval($data['is_active']);
			if (!in_array($is_active, array(0, 1), true)) {
				$errors->add('invalid_status', __('Trạng thái không hợp lệ.', 'vd-license-manager'));
			}
		}

		if ($errors->has_errors()) {
			return $errors;
		}

		return true;
	}

	/**
	 * Sanitize pool data
	 *
	 * @since 1.0.0
	 * @param array $data Raw pool data
	 * @return array Sanitized pool data
	 */
	public static function sanitize_pool_data($data) {
		$sanitized = array();

		if (isset($data['product_id'])) {
			$sanitized['product_id'] = intval($data['product_id']);
		}

		if (isset($data['account_id'])) {
			$sanitized['account_id'] = intval($data['account_id']);
		}

		if (isset($data['priority'])) {
			$sanitized['priority'] = intval($data['priority']);
		} else {
			$sanitized['priority'] = 0; // Default priority
		}

		if (isset($data['is_active'])) {
			$sanitized['is_active'] = intval($data['is_active']);
		} else {
			$sanitized['is_active'] = 1; // Default active
		}

		return $sanitized;
	}

	/**
	 * Check if a WooCommerce product exists
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID
	 * @return bool True if product exists, false otherwise
	 */
	private static function product_exists($product_id) {
		global $wpdb;

		$product_id = intval($product_id);
		if ($product_id <= 0) {
			return false;
		}

		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE ID = %d AND post_type = 'product' AND post_status IN ('publish', 'private')",
			$product_id
		));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in product_exists - ' . $wpdb->last_error);
			return false;
		}

		return intval($count) > 0;
	}

	/**
	 * Check if a provider account exists
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return bool True if account exists, false otherwise
	 */
	private static function account_exists($account_id) {
		$account_id = intval($account_id);
		if ($account_id <= 0) {
			return false;
		}

		require_once plugin_dir_path(dirname(__FILE__)) . 'repositories/class-vd-accounts-repository.php';

		$account = VD_Accounts_Repository::get_account($account_id);
		return !is_null($account);
	}

	/**
	 * Check if a pool with the same product + account exists
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID
	 * @param int $account_id Account ID
	 * @return bool True if pool exists, false otherwise
	 */
	private static function pool_exists($product_id, $account_id) {
		$product_id = intval($product_id);
		$account_id = intval($account_id);

		if ($product_id <= 0 || $account_id <= 0) {
			return false;
		}

		require_once plugin_dir_path(dirname(__FILE__)) . 'repositories/class-vd-pools-repository.php';

		return VD_Pools_Repository::pool_exists($product_id, $account_id);
	}
}
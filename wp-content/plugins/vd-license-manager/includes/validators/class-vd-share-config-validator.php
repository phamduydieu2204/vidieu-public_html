<?php
/**
 * Share Config Validator class for VD License Manager
 *
 * Validates product share configuration data before database operations
 *
 * @package VD_License_Manager
 * @subpackage Validators
 * @since 1.0.1
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * VD Share Config Validator class
 *
 * Static methods for validating share configuration data
 *
 * @package VD_License_Manager
 * @since 1.0.1
 */
class VD_Share_Config_Validator {

	/**
	 * Validate data for adding new share config
	 *
	 * @since 1.0.1
	 * @param array $data Share config data
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_add($data) {
		$errors = new WP_Error();

		// Required fields validation
		if (empty($data['product_id'])) {
			$errors->add('missing_product_id', __('ID sản phẩm là bắt buộc', 'vd-license-manager'));
		} else {
			$product_id = intval($data['product_id']);
			if ($product_id <= 0) {
				$errors->add('invalid_product_id', __('ID sản phẩm không hợp lệ', 'vd-license-manager'));
			} else {
				// Check if product exists (WooCommerce)
				$product = wc_get_product($product_id);
				if (!$product) {
					$errors->add('product_not_found', __('Sản phẩm không tồn tại', 'vd-license-manager'));
				}

				// Check for duplicate config
				if (VD_Share_Config_Repository::product_has_config($product_id)) {
					$errors->add('duplicate_config', __('Sản phẩm này đã có cấu hình chia sẻ', 'vd-license-manager'));
				}
			}
		}

		// Validate required integer fields
		self::validate_required_fields($data, $errors);

		// Validate optional fields
		self::validate_optional_fields($data, $errors);

		// Validate business logic
		self::validate_business_logic($data, $errors);

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Validate data for updating share config
	 *
	 * @since 1.0.1
	 * @param array $data      Share config data
	 * @param int   $config_id Config ID (for duplicate checking)
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_edit($data, $config_id = 0) {
		$errors = new WP_Error();

		$config_id = intval($config_id);

		// Check if config exists (if config_id provided)
		if ($config_id > 0) {
			$config = VD_Share_Config_Repository::get_config($config_id);
			if (!$config) {
				return new WP_Error('config_not_found', __('Không tìm thấy cấu hình chia sẻ', 'vd-license-manager'));
			}
		}

		// Validate product_id if provided
		if (isset($data['product_id'])) {
			$product_id = intval($data['product_id']);
			if ($product_id <= 0) {
				$errors->add('invalid_product_id', __('ID sản phẩm không hợp lệ', 'vd-license-manager'));
			} else {
				// Check if product exists
				$product = wc_get_product($product_id);
				if (!$product) {
					$errors->add('product_not_found', __('Sản phẩm không tồn tại', 'vd-license-manager'));
				}

				// Check for duplicate config (exclude current config)
				if (VD_Share_Config_Repository::product_has_config($product_id, $config_id)) {
					$errors->add('duplicate_config', __('Sản phẩm này đã có cấu hình chia sẻ', 'vd-license-manager'));
				}
			}
		}

		// Validate fields if provided
		if (isset($data['max_profiles']) || isset($data['max_devices_per_profile']) || isset($data['sharing_duration_days'])) {
			self::validate_required_fields($data, $errors, true);
		}

		// Validate optional fields
		self::validate_optional_fields($data, $errors);

		// Validate business logic
		self::validate_business_logic($data, $errors);

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Validate required integer fields
	 *
	 * @since 1.0.1
	 * @param array    $data       Share config data
	 * @param WP_Error $errors     Error object to add errors to
	 * @param bool     $is_update  Whether this is an update validation
	 */
	private static function validate_required_fields($data, $errors, $is_update = false) {
		// Max profiles validation
		if (!$is_update || isset($data['max_profiles'])) {
			if (empty($data['max_profiles'])) {
				$errors->add('missing_max_profiles', __('Số lượng hồ sơ tối đa là bắt buộc', 'vd-license-manager'));
			} else {
				$max_profiles = intval($data['max_profiles']);
				if ($max_profiles < 1 || $max_profiles > 50) {
					$errors->add('invalid_max_profiles', __('Số lượng hồ sơ tối đa phải từ 1 đến 50', 'vd-license-manager'));
				}
			}
		}

		// Max devices per profile validation
		if (!$is_update || isset($data['max_devices_per_profile'])) {
			if (empty($data['max_devices_per_profile'])) {
				$errors->add('missing_max_devices', __('Số thiết bị tối đa mỗi hồ sơ là bắt buộc', 'vd-license-manager'));
			} else {
				$max_devices = intval($data['max_devices_per_profile']);
				if ($max_devices < 1 || $max_devices > 20) {
					$errors->add('invalid_max_devices', __('Số thiết bị tối đa mỗi hồ sơ phải từ 1 đến 20', 'vd-license-manager'));
				}
			}
		}

		// Sharing duration validation
		if (!$is_update || isset($data['sharing_duration_days'])) {
			if (empty($data['sharing_duration_days'])) {
				$errors->add('missing_duration', __('Thời gian chia sẻ là bắt buộc', 'vd-license-manager'));
			} else {
				$duration = intval($data['sharing_duration_days']);
				if ($duration < 1 || $duration > 365) {
					$errors->add('invalid_duration', __('Thời gian chia sẻ phải từ 1 đến 365 ngày', 'vd-license-manager'));
				}
			}
		}
	}

	/**
	 * Validate optional fields
	 *
	 * @since 1.0.1
	 * @param array    $data   Share config data
	 * @param WP_Error $errors Error object to add errors to
	 */
	private static function validate_optional_fields($data, $errors) {
		// Auto rotate validation
		if (isset($data['auto_rotate'])) {
			$auto_rotate = $data['auto_rotate'];
			if ($auto_rotate !== '0' && $auto_rotate !== '1' && $auto_rotate !== 0 && $auto_rotate !== 1 && !is_bool($auto_rotate)) {
				$errors->add('invalid_auto_rotate', __('Tự động xoay phải là true hoặc false', 'vd-license-manager'));
			}
		}

		// Rotation interval validation
		if (isset($data['rotation_interval_days']) && !empty($data['rotation_interval_days'])) {
			$interval = intval($data['rotation_interval_days']);
			if ($interval < 1 || $interval > 365) {
				$errors->add('invalid_rotation_interval', __('Khoảng thời gian xoay phải từ 1 đến 365 ngày', 'vd-license-manager'));
			}
		}

		// Allow concurrent streams validation
		if (isset($data['allow_concurrent_streams']) && !empty($data['allow_concurrent_streams'])) {
			$streams = intval($data['allow_concurrent_streams']);
			if ($streams < 1 || $streams > 10) {
				$errors->add('invalid_concurrent_streams', __('Số luồng đồng thời phải từ 1 đến 10', 'vd-license-manager'));
			}
		}

		// Custom rules validation
		if (isset($data['custom_rules']) && !empty($data['custom_rules'])) {
			$rules = trim($data['custom_rules']);
			if (strlen($rules) > 5000) {
				$errors->add('custom_rules_too_long', __('Quy tắc tùy chỉnh không được quá 5000 ký tự', 'vd-license-manager'));
			}

			// Check for potentially dangerous content
			$dangerous_patterns = array(
				'<script',
				'javascript:',
				'data:text/html',
				'eval(',
				'exec(',
				'system(',
				'shell_exec('
			);

			foreach ($dangerous_patterns as $pattern) {
				if (stripos($rules, $pattern) !== false) {
					$errors->add('dangerous_custom_rules', __('Quy tắc tùy chỉnh chứa nội dung không an toàn', 'vd-license-manager'));
					break;
				}
			}
		}
	}

	/**
	 * Validate business logic rules
	 *
	 * @since 1.0.1
	 * @param array    $data   Share config data
	 * @param WP_Error $errors Error object to add errors to
	 */
	private static function validate_business_logic($data, $errors) {
		// If auto_rotate is enabled, rotation_interval_days should be provided
		if (isset($data['auto_rotate']) && !empty($data['auto_rotate'])) {
			if (empty($data['rotation_interval_days'])) {
				$errors->add('missing_rotation_interval', __('Khi bật tự động xoay, bạn phải nhập khoảng thời gian xoay', 'vd-license-manager'));
			}
		}

		// If rotation_interval_days is provided, auto_rotate should be enabled
		if (isset($data['rotation_interval_days']) && !empty($data['rotation_interval_days'])) {
			if (empty($data['auto_rotate'])) {
				$errors->add('auto_rotate_required', __('Khi nhập khoảng thời gian xoay, bạn phải bật tự động xoay', 'vd-license-manager'));
			}
		}

		// Validate logical relationships
		if (isset($data['max_profiles']) && isset($data['max_devices_per_profile'])) {
			$max_profiles = intval($data['max_profiles']);
			$max_devices = intval($data['max_devices_per_profile']);
			$total_devices = $max_profiles * $max_devices;

			// Warn if total devices is very high
			if ($total_devices > 100) {
				$errors->add('high_device_count', sprintf(
					__('Tổng số thiết bị (%d) có thể quá cao. Điều này có thể ảnh hưởng đến hiệu suất.', 'vd-license-manager'),
					$total_devices
				));
			}
		}

		// Validate rotation interval vs sharing duration
		if (isset($data['rotation_interval_days']) && isset($data['sharing_duration_days'])) {
			$rotation_interval = intval($data['rotation_interval_days']);
			$sharing_duration = intval($data['sharing_duration_days']);

			if ($rotation_interval > $sharing_duration) {
				$errors->add('rotation_interval_too_long', __('Khoảng thời gian xoay không được lớn hơn thời gian chia sẻ', 'vd-license-manager'));
			}
		}
	}

	/**
	 * Sanitize share config data
	 *
	 * @since 1.0.1
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	public static function sanitize_data($data) {
		$sanitized = array();

		// Product ID
		if (isset($data['product_id'])) {
			$sanitized['product_id'] = intval($data['product_id']);
		}

		// Max profiles
		if (isset($data['max_profiles'])) {
			$sanitized['max_profiles'] = intval($data['max_profiles']);
		}

		// Max devices per profile
		if (isset($data['max_devices_per_profile'])) {
			$sanitized['max_devices_per_profile'] = intval($data['max_devices_per_profile']);
		}

		// Sharing duration days
		if (isset($data['sharing_duration_days'])) {
			$sanitized['sharing_duration_days'] = intval($data['sharing_duration_days']);
		}

		// Auto rotate
		if (isset($data['auto_rotate'])) {
			$sanitized['auto_rotate'] = !empty($data['auto_rotate']) ? 1 : 0;
		}

		// Rotation interval days
		if (isset($data['rotation_interval_days'])) {
			$sanitized['rotation_interval_days'] = !empty($data['rotation_interval_days']) ? intval($data['rotation_interval_days']) : null;
		}

		// Allow concurrent streams
		if (isset($data['allow_concurrent_streams'])) {
			$sanitized['allow_concurrent_streams'] = !empty($data['allow_concurrent_streams']) ? intval($data['allow_concurrent_streams']) : null;
		}

		// Custom rules
		if (isset($data['custom_rules'])) {
			$sanitized['custom_rules'] = !empty($data['custom_rules']) ? sanitize_textarea_field($data['custom_rules']) : null;
		}

		return $sanitized;
	}

	/**
	 * Get default values for new share config
	 *
	 * @since 1.0.1
	 * @return array Default values
	 */
	public static function get_defaults() {
		return array(
			'max_profiles'             => 1,
			'max_devices_per_profile'  => 1,
			'sharing_duration_days'    => 30,
			'auto_rotate'             => 0,
			'rotation_interval_days'  => null,
			'allow_concurrent_streams' => null,
			'custom_rules'            => null
		);
	}

	/**
	 * Validate config data before display
	 *
	 * @since 1.0.1
	 * @param object $config Config object from database
	 * @return object Validated config object
	 */
	public static function validate_config_object($config) {
		if (!$config) {
			return null;
		}

		// Ensure required fields have minimum values
		$config->max_profiles = max(1, intval($config->max_profiles));
		$config->max_devices_per_profile = max(1, intval($config->max_devices_per_profile));
		$config->sharing_duration_days = max(1, intval($config->sharing_duration_days));

		// Ensure boolean fields are properly formatted
		$config->auto_rotate = intval($config->auto_rotate);

		// Ensure nullable fields are properly handled
		$config->rotation_interval_days = !empty($config->rotation_interval_days) ? intval($config->rotation_interval_days) : null;
		$config->allow_concurrent_streams = !empty($config->allow_concurrent_streams) ? intval($config->allow_concurrent_streams) : null;
		$config->custom_rules = !empty($config->custom_rules) ? $config->custom_rules : null;

		return $config;
	}
}
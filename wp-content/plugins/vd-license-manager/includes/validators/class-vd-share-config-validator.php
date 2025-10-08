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
		if (isset($data['max_profiles']) || isset($data['max_devices_per_profile']) || isset($data['max_devices']) || isset($data['sharing_duration_days'])) {
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

		// Max devices total validation
		if (!$is_update || isset($data['max_devices'])) {
			if (empty($data['max_devices'])) {
				$errors->add('missing_max_devices_total', __('Tổng số thiết bị tối đa là bắt buộc', 'vd-license-manager'));
			} else {
				$max_devices_total = intval($data['max_devices']);
				if ($max_devices_total < 1 || $max_devices_total > 100) {
					$errors->add('invalid_max_devices_total', __('Tổng số thiết bị tối đa phải từ 1 đến 100', 'vd-license-manager'));
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
		// Last update date validation
		if (isset($data['last_update_date']) && !empty($data['last_update_date'])) {
			$last_update = sanitize_text_field($data['last_update_date']);
			if (!self::is_valid_datetime($last_update)) {
				$errors->add('invalid_last_update_date', __('Ngày cập nhật cuối không hợp lệ', 'vd-license-manager'));
			}
		}

		// Next update date validation
		if (isset($data['next_update_date']) && !empty($data['next_update_date'])) {
			$next_update = sanitize_text_field($data['next_update_date']);
			if (!self::is_valid_datetime($next_update)) {
				$errors->add('invalid_next_update_date', __('Ngày cập nhật tiếp theo không hợp lệ', 'vd-license-manager'));
			} else {
				// Check if next update date is in the future
				$next_update_timestamp = strtotime($next_update);
				if ($next_update_timestamp < time()) {
					$errors->add('next_update_date_past', __('Ngày cập nhật tiếp theo phải trong tương lai', 'vd-license-manager'));
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
		// Validate logical relationships between device limits
		if (isset($data['max_profiles']) && isset($data['max_devices_per_profile']) && isset($data['max_devices'])) {
			$max_profiles = intval($data['max_profiles']);
			$max_devices_per_profile = intval($data['max_devices_per_profile']);
			$max_devices_total = intval($data['max_devices']);

			// Check if total devices is consistent
			$calculated_total = $max_profiles * $max_devices_per_profile;
			if ($max_devices_total < $calculated_total) {
				$errors->add('device_limit_mismatch', sprintf(
					__('Tổng số thiết bị (%d) phải ít nhất bằng số hồ sơ × thiết bị mỗi hồ sơ (%d)', 'vd-license-manager'),
					$max_devices_total,
					$calculated_total
				));
			}

			// Warn if total devices is very high
			if ($max_devices_total > 200) {
				$errors->add('high_device_count', sprintf(
					__('Tổng số thiết bị (%d) rất cao. Điều này có thể ảnh hưởng đến hiệu suất.', 'vd-license-manager'),
					$max_devices_total
				));
			}
		}

		// Validate date relationships
		if (isset($data['last_update_date']) && isset($data['next_update_date'])) {
			$last_update = sanitize_text_field($data['last_update_date']);
			$next_update = sanitize_text_field($data['next_update_date']);

			if (!empty($last_update) && !empty($next_update)) {
				$last_timestamp = strtotime($last_update);
				$next_timestamp = strtotime($next_update);

				if ($next_timestamp <= $last_timestamp) {
					$errors->add('invalid_date_sequence', __('Ngày cập nhật tiếp theo phải sau ngày cập nhật cuối', 'vd-license-manager'));
				}
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

		// Max devices total
		if (isset($data['max_devices'])) {
			$sanitized['max_devices'] = intval($data['max_devices']);
		}

		// Sharing duration days
		if (isset($data['sharing_duration_days'])) {
			$sanitized['sharing_duration_days'] = intval($data['sharing_duration_days']);
		}

		// Last update date
		if (isset($data['last_update_date'])) {
			$sanitized['last_update_date'] = !empty($data['last_update_date']) ? sanitize_text_field($data['last_update_date']) : null;
		}

		// Next update date
		if (isset($data['next_update_date'])) {
			$sanitized['next_update_date'] = !empty($data['next_update_date']) ? sanitize_text_field($data['next_update_date']) : null;
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
			'max_devices'              => 1,
			'sharing_duration_days'    => 30,
			'last_update_date'         => null,
			'next_update_date'         => null
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
		$config->max_devices = max(1, intval($config->max_devices));
		$config->sharing_duration_days = max(1, intval($config->sharing_duration_days));

		// Ensure date fields are properly handled
		$config->last_update_date = !empty($config->last_update_date) ? $config->last_update_date : null;
		$config->next_update_date = !empty($config->next_update_date) ? $config->next_update_date : null;

		return $config;
	}

	/**
	 * Validate datetime format
	 *
	 * @since 1.0.1
	 * @param string $datetime DateTime string
	 * @return bool True if valid, false otherwise
	 */
	private static function is_valid_datetime($datetime) {
		$formats = array(
			'Y-m-d H:i:s',
			'Y-m-d H:i',
			'Y-m-d',
			'd/m/Y H:i:s',
			'd/m/Y H:i',
			'd/m/Y'
		);

		foreach ($formats as $format) {
			$date = DateTime::createFromFormat($format, $datetime);
			if ($date && $date->format($format) === $datetime) {
				return true;
			}
		}

		return false;
	}
}
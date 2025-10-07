<?php
/**
 * Account Validator class for VD License Manager
 *
 * Validates provider account data before database operations
 *
 * @package VD_License_Manager
 * @subpackage Validators
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * VD Account Validator class
 *
 * Static methods for validating account data
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Account_Validator {

	/**
	 * Validate data for adding new account
	 *
	 * @since 1.0.0
	 * @param array $data Account data
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_add($data) {
		$errors = new WP_Error();

		// Required fields validation
		if (empty($data['provider'])) {
			$errors->add('missing_provider', 'Provider is required');
		} else {
			$provider_validation = self::validate_provider($data['provider']);
			if (is_wp_error($provider_validation)) {
				$errors->add($provider_validation->get_error_code(), $provider_validation->get_error_message());
			}
		}

		if (empty($data['account_login'])) {
			$errors->add('missing_account_login', 'Account login is required');
		} elseif (strlen($data['account_login']) > 191) {
			$errors->add('account_login_too_long', 'Account login must be 191 characters or less');
		}

		if (empty($data['display_name'])) {
			$errors->add('missing_display_name', 'Display name is required');
		} elseif (strlen($data['display_name']) > 191) {
			$errors->add('display_name_too_long', 'Display name must be 191 characters or less');
		}

		// Check for duplicate account
		if (!empty($data['provider']) && !empty($data['account_login'])) {
			if (VD_Accounts_Repository::account_exists($data['provider'], $data['account_login'])) {
				$errors->add('duplicate_account', 'Account already exists for this provider');
			}
		}

		// Optional field validations
		self::validate_optional_fields($data, $errors);

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Validate data for updating account
	 *
	 * @since 1.0.0
	 * @param int   $account_id Account ID
	 * @param array $data       Account data
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_update($account_id, $data) {
		$errors = new WP_Error();

		// Check if account exists
		$account = VD_Accounts_Repository::get_account($account_id);
		if (!$account) {
			return new WP_Error('account_not_found', 'Account not found');
		}

		// Validate provider if provided
		if (isset($data['provider'])) {
			$provider_validation = self::validate_provider($data['provider']);
			if (is_wp_error($provider_validation)) {
				$errors->add($provider_validation->get_error_code(), $provider_validation->get_error_message());
			}
		}

		// Validate account_login length
		if (isset($data['account_login']) && strlen($data['account_login']) > 191) {
			$errors->add('account_login_too_long', 'Account login must be 191 characters or less');
		}

		// Validate display_name length
		if (isset($data['display_name']) && strlen($data['display_name']) > 191) {
			$errors->add('display_name_too_long', 'Display name must be 191 characters or less');
		}

		// Check for duplicate if provider/login changed
		if (isset($data['provider']) && isset($data['account_login'])) {
			$provider = $data['provider'];
			$account_login = $data['account_login'];
		} else {
			$provider = isset($data['provider']) ? $data['provider'] : $account->provider;
			$account_login = isset($data['account_login']) ? $data['account_login'] : $account->account_login;
		}

		if ($provider !== $account->provider || $account_login !== $account->account_login) {
			if (VD_Accounts_Repository::account_exists($provider, $account_login)) {
				$errors->add('duplicate_account', 'Account already exists for this provider');
			}
		}

		// Optional field validations
		self::validate_optional_fields($data, $errors);

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Validate optional fields
	 *
	 * @since 1.0.0
	 * @param array    $data   Account data
	 * @param WP_Error $errors Error object to add errors to
	 */
	private static function validate_optional_fields($data, $errors) {
		// Validate capacity
		if (isset($data['capacity'])) {
			$capacity = intval($data['capacity']);
			if ($capacity < 1) {
				$errors->add('invalid_capacity', 'Capacity must be a positive integer');
			}
		}

		// Validate status
		if (isset($data['status'])) {
			$valid_statuses = array('active', 'suspended', 'expired');
			if (!in_array($data['status'], $valid_statuses)) {
				$errors->add('invalid_status', 'Status must be active, suspended, or expired');
			}
		}

		// Validate emails
		if (!empty($data['login_email'])) {
			$email_validation = self::validate_email($data['login_email']);
			if (is_wp_error($email_validation)) {
				$errors->add('invalid_login_email', 'Invalid login email format');
			}
		}

		if (!empty($data['recovery_email'])) {
			$email_validation = self::validate_email($data['recovery_email']);
			if (is_wp_error($email_validation)) {
				$errors->add('invalid_recovery_email', 'Invalid recovery email format');
			}
		}

		// Validate phone
		if (!empty($data['recovery_phone'])) {
			$phone_validation = self::validate_phone($data['recovery_phone']);
			if (is_wp_error($phone_validation)) {
				$errors->add('invalid_recovery_phone', $phone_validation->get_error_message());
			}
		}

		// Validate cookie
		if (!empty($data['cookie'])) {
			$format = isset($data['cookie_format']) ? $data['cookie_format'] : 'json';
			$cookie_validation = self::validate_cookie($data['cookie'], $format);
			if (is_wp_error($cookie_validation)) {
				$errors->add('invalid_cookie', $cookie_validation->get_error_message());
			}
		}
	}

	/**
	 * Check if provider is in allowed list
	 *
	 * @since 1.0.0
	 * @param string $provider Provider name
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_provider($provider) {
		$allowed_providers = self::get_allowed_providers();

		if (!in_array($provider, $allowed_providers)) {
			return new WP_Error('invalid_provider', 'Invalid provider selected');
		}

		return true;
	}

	/**
	 * Validate email format
	 *
	 * @since 1.0.0
	 * @param string $email Email address
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_email($email) {
		if (!is_email($email)) {
			return new WP_Error('invalid_email', 'Invalid email format');
		}

		return true;
	}

	/**
	 * Validate phone number format
	 *
	 * @since 1.0.0
	 * @param string $phone Phone number
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_phone($phone) {
		// Allow +, digits, spaces, dashes, parentheses
		if (!preg_match('/^[\+\d\s\-\(\)]+$/', $phone)) {
			return new WP_Error('invalid_phone', 'Phone number contains invalid characters');
		}

		// Check minimum length (at least 7 digits)
		$digits_only = preg_replace('/[^\d]/', '', $phone);
		if (strlen($digits_only) < 7) {
			return new WP_Error('phone_too_short', 'Phone number must contain at least 7 digits');
		}

		return true;
	}

	/**
	 * Validate cookie based on format
	 *
	 * @since 1.0.0
	 * @param string $cookie Cookie data
	 * @param string $format Cookie format (json, netscape, headers)
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_cookie($cookie, $format) {
		switch ($format) {
			case 'json':
				$decoded = json_decode($cookie, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					return new WP_Error('invalid_json', 'Invalid JSON format in cookie');
				}
				break;

			case 'netscape':
				// Basic netscape format check (tab-separated values)
				$lines = explode("\n", trim($cookie));
				foreach ($lines as $line) {
					$line = trim($line);
					if (empty($line) || strpos($line, '#') === 0) {
						continue; // Skip empty lines and comments
					}
					$parts = explode("\t", $line);
					if (count($parts) < 6) {
						return new WP_Error('invalid_netscape', 'Invalid Netscape cookie format');
					}
				}
				break;

			case 'headers':
				// Headers format - just check it's not empty
				if (empty(trim($cookie))) {
					return new WP_Error('empty_headers', 'Headers format cookie cannot be empty');
				}
				break;

			default:
				return new WP_Error('unknown_format', 'Unknown cookie format');
		}

		return true;
	}

	/**
	 * Sanitize all account data fields
	 *
	 * @since 1.0.0
	 * @param array $data Account data
	 * @return array Sanitized data
	 */
	public static function sanitize_account_data($data) {
		$sanitized = array();

		$field_map = array(
			'provider'        => 'sanitize_text_field',
			'account_login'   => 'sanitize_text_field',
			'display_name'    => 'sanitize_text_field',
			'capacity'        => 'intval',
			'status'          => 'sanitize_text_field',
			'login_email'     => 'sanitize_email',
			'totp_secret'     => 'sanitize_text_field',
			'recovery_email'  => 'sanitize_email',
			'recovery_phone'  => 'sanitize_text_field',
			'notes'           => 'sanitize_textarea_field',
			'cookie_format'   => 'sanitize_text_field'
		);

		foreach ($field_map as $field => $sanitize_func) {
			if (isset($data[$field])) {
				$sanitized[$field] = $sanitize_func($data[$field]);
			}
		}

		// Special handling for password and cookie (no sanitization)
		if (isset($data['login_password'])) {
			$sanitized['login_password'] = $data['login_password'];
		}

		if (isset($data['cookie'])) {
			$sanitized['cookie'] = wp_kses_post($data['cookie']);
		}

		return $sanitized;
	}

	/**
	 * Get list of allowed providers
	 *
	 * @since 1.0.0
	 * @return array Array of provider names
	 */
	public static function get_allowed_providers() {
		$providers = array('netflix', 'spotify', 'youtube', 'disney', 'hbo', 'amazon', 'hulu', 'other');

		return apply_filters('vd_allowed_providers', $providers);
	}
}
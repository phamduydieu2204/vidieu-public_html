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
		// Original field validations
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

		// Validate cookie (simple validation - just check not empty if provided)
		if (!empty($data['cookie'])) {
			// Simple validation - just ensure it's not empty
			if (strlen(trim($data['cookie'])) === 0) {
				$errors->add('empty_cookie', 'Cookie data cannot be empty if provided');
			}
		}

		// NEW FIELD VALIDATIONS (11 fields)

		// Subscription Management validations (5 fields)
		if (!empty($data['subscription_start_date'])) {
			$date_validation = self::validate_date($data['subscription_start_date'], 'Subscription Start Date');
			if (is_wp_error($date_validation)) {
				$errors->add('invalid_subscription_start_date', $date_validation->get_error_message());
			}
		}

		if (!empty($data['subscription_end_date'])) {
			$date_validation = self::validate_date($data['subscription_end_date'], 'Subscription End Date');
			if (is_wp_error($date_validation)) {
				$errors->add('invalid_subscription_end_date', $date_validation->get_error_message());
			}
		}

		if (isset($data['subscription_cost'])) {
			$cost = floatval($data['subscription_cost']);
			if ($cost < 0) {
				$errors->add('invalid_subscription_cost', 'Subscription cost cannot be negative');
			}
		}

		if (!empty($data['currency'])) {
			$currency_validation = self::validate_currency($data['currency']);
			if (is_wp_error($currency_validation)) {
				$errors->add('invalid_currency', $currency_validation->get_error_message());
			}
		}

		if (!empty($data['auto_renewal'])) {
			$renewal = intval($data['auto_renewal']);
			if ($renewal !== 0 && $renewal !== 1) {
				$errors->add('invalid_auto_renewal', 'Auto renewal must be 0 or 1');
			}
		}

		// Account Details validations (3 fields)
		if (!empty($data['plan_type'])) {
			$plan = sanitize_text_field($data['plan_type']);
			if (strlen($plan) > 100) {
				$errors->add('plan_type_too_long', 'Plan type must be 100 characters or less');
			}
		}

		if (isset($data['profile_limit'])) {
			$limit = intval($data['profile_limit']);
			if ($limit < 1 || $limit > 10) {
				$errors->add('invalid_profile_limit', 'Profile limit must be between 1 and 10');
			}
		}

		if (!empty($data['account_region'])) {
			$region = sanitize_text_field($data['account_region']);
			if (strlen($region) > 5) {
				$errors->add('region_too_long', 'Account region must be 5 characters or less');
			}
		}

		// Security validations (2 fields)
		if (!empty($data['last_password_changed'])) {
			$datetime_validation = self::validate_datetime($data['last_password_changed'], 'Last Password Changed');
			if (is_wp_error($datetime_validation)) {
				$errors->add('invalid_last_password_changed', $datetime_validation->get_error_message());
			}
		}

		if (!empty($data['has_2fa'])) {
			$twofa = intval($data['has_2fa']);
			if ($twofa !== 0 && $twofa !== 1) {
				$errors->add('invalid_has_2fa', 'Two-factor auth must be 0 or 1');
			}
		}

		// Business Intelligence validations (2 fields)
		if (isset($data['total_revenue'])) {
			$revenue = floatval($data['total_revenue']);
			if ($revenue < 0) {
				$errors->add('invalid_total_revenue', 'Total revenue cannot be negative');
			}
		}

		if (isset($data['total_licenses_served'])) {
			$licenses = intval($data['total_licenses_served']);
			if ($licenses < 0) {
				$errors->add('invalid_total_licenses_served', 'Total licenses served cannot be negative');
			}
		}
	}

	/**
	 * Validate provider (free text input)
	 *
	 * @since 1.0.0
	 * @param string $provider Provider name
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public static function validate_provider($provider) {
		// Allow free text input for provider
		// Just validate length (max 100 characters as per database schema)
		if (strlen($provider) > 100) {
			return new WP_Error('provider_too_long', 'Provider name must be 100 characters or less');
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
	 * Sanitize all account data fields
	 *
	 * @since 1.0.0
	 * @param array $data Account data
	 * @return array Sanitized data
	 */
	public static function sanitize_account_data($data) {
		$sanitized = array();

		// Core fields (12 original fields)
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
		);

		// NEW FIELDS (11 additional fields)
		// Subscription Management (5 fields)
		$field_map['subscription_start_date'] = 'sanitize_text_field';
		$field_map['subscription_end_date'] = 'sanitize_text_field';
		$field_map['subscription_cost'] = 'floatval';
		$field_map['currency'] = 'sanitize_text_field';
		$field_map['auto_renewal'] = 'intval';

		// Account Details (3 fields)
		$field_map['plan_type'] = 'sanitize_text_field';
		$field_map['profile_limit'] = 'intval';
		$field_map['account_region'] = 'sanitize_text_field';

		// Security (2 fields)
		$field_map['last_password_changed'] = 'sanitize_text_field';
		$field_map['has_2fa'] = 'intval';

		// Business Intelligence (2 fields) - Usually read-only
		$field_map['total_revenue'] = 'floatval';
		$field_map['total_licenses_served'] = 'intval';

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
	 * Validate date field
	 *
	 * @since 1.0.0
	 * @param string $date       Date string
	 * @param string $field_name Field name for error messages
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	private static function validate_date($date, $field_name) {
		if (empty($date)) return true;

		$parsed = strtotime($date);
		if ($parsed === false) {
			return new WP_Error('invalid_date', sprintf(__('%s must be a valid date.', 'vd-license-manager'), $field_name));
		}
		return true;
	}

	/**
	 * Validate datetime field
	 *
	 * @since 1.0.0
	 * @param string $datetime   Datetime string
	 * @param string $field_name Field name for error messages
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	private static function validate_datetime($datetime, $field_name) {
		if (empty($datetime)) return true;

		$parsed = strtotime($datetime);
		if ($parsed === false) {
			return new WP_Error('invalid_datetime', sprintf(__('%s must be a valid datetime.', 'vd-license-manager'), $field_name));
		}
		return true;
	}

	/**
	 * Validate currency code
	 *
	 * @since 1.0.0
	 * @param string $currency Currency code
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	private static function validate_currency($currency) {
		if (empty($currency)) return true;

		$allowed = array('USD', 'VND', 'EUR', 'GBP', 'JPY');
		if (!in_array(strtoupper($currency), $allowed)) {
			return new WP_Error('invalid_currency', __('Invalid currency code.', 'vd-license-manager'));
		}
		return true;
	}

}
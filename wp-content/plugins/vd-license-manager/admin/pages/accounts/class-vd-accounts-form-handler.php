<?php
/**
 * Provider Accounts form handler
 *
 * Processes form submissions for add and edit operations
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check
if (!defined('ABSPATH')) {
	exit;
}

/**
 * VD Accounts Form Handler class
 *
 * Handles form processing, validation, and database operations
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Accounts_Form_Handler {

	/**
	 * Main entry point for form submission handling
	 *
	 * @since 1.0.0
	 */
	public static function handle_save() {
		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'vd-license-manager'));
		}

		// Verify nonce
		if (!self::verify_nonce()) {
			wp_die(__('Security check failed.', 'vd-license-manager'));
		}

		// Determine if add or edit operation
		$account_id = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;

		if ($account_id > 0) {
			// Edit existing account
			self::process_update($account_id);
		} else {
			// Add new account
			self::process_add();
		}
	}

	/**
	 * Process new account creation
	 *
	 * @since 1.0.0
	 */
	private static function process_add() {
		// Get form data
		$data = self::get_form_data();

		// Validate data
		$validation = VD_Account_Validator::validate_add($data);
		if (is_wp_error($validation)) {
			$message = $validation->get_error_message();
			self::redirect_with_message(
				admin_url('admin.php?page=vd-provider-accounts&action=add'),
				$message,
				'error'
			);
			return;
		}

		// Insert account
		$result = VD_Accounts_Repository::insert_account($data);
		if (is_wp_error($result)) {
			$message = $result->get_error_message();
			self::redirect_with_message(
				admin_url('admin.php?page=vd-provider-accounts&action=add'),
				$message,
				'error'
			);
			return;
		}

		// Success - redirect to list with success message
		self::redirect_with_message(
			admin_url('admin.php?page=vd-provider-accounts'),
			__('Account added successfully.', 'vd-license-manager'),
			'success'
		);
	}

	/**
	 * Process account update
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID to update
	 */
	private static function process_update($account_id) {
		// Verify account exists
		$account = VD_Accounts_Repository::get_account($account_id);
		if (!$account) {
			wp_die(__('Account not found.', 'vd-license-manager'));
		}

		// Get form data
		$data = self::get_form_data();
		$data['id'] = $account_id;

		// Validate data
		$validation = VD_Account_Validator::validate_update($data);
		if (is_wp_error($validation)) {
			$message = $validation->get_error_message();
			self::redirect_with_message(
				admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $account_id),
				$message,
				'error'
			);
			return;
		}

		// Update account
		$result = VD_Accounts_Repository::update_account($account_id, $data);
		if (is_wp_error($result)) {
			$message = $result->get_error_message();
			self::redirect_with_message(
				admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $account_id),
				$message,
				'error'
			);
			return;
		}

		// Success - redirect to list with success message
		self::redirect_with_message(
			admin_url('admin.php?page=vd-provider-accounts'),
			__('Account updated successfully.', 'vd-license-manager'),
			'success'
		);
	}

	/**
	 * Verify security nonce
	 *
	 * @since 1.0.0
	 * @return bool True if nonce is valid
	 */
	private static function verify_nonce() {
		return wp_verify_nonce($_POST['vd_account_nonce'], 'vd_save_account');
	}

	/**
	 * Extract and sanitize form data from $_POST
	 *
	 * @since 1.0.0
	 * @return array Sanitized form data
	 */
	private static function get_form_data() {
		return array(
			// Original fields
			'provider'        => sanitize_text_field($_POST['provider'] ?? ''),
			'account_login'   => sanitize_text_field($_POST['account_login'] ?? ''),
			'display_name'    => sanitize_text_field($_POST['display_name'] ?? ''),
			'capacity'        => intval($_POST['capacity'] ?? 0),
			'status'          => sanitize_text_field($_POST['status'] ?? 'active'),
			'cookie'          => sanitize_textarea_field($_POST['cookie'] ?? ''),
			'cookie_format'   => sanitize_text_field($_POST['cookie_format'] ?? 'json'),
			'login_email'     => sanitize_email($_POST['login_email'] ?? ''),
			'login_password'  => sanitize_text_field($_POST['login_password'] ?? ''),
			'totp_secret'     => sanitize_text_field($_POST['totp_secret'] ?? ''),
			'recovery_email'  => sanitize_email($_POST['recovery_email'] ?? ''),
			'recovery_phone'  => sanitize_text_field($_POST['recovery_phone'] ?? ''),
			'notes'           => sanitize_textarea_field($_POST['notes'] ?? ''),

			// NEW FIELDS (15 additional)
			// Subscription Management (5 fields)
			'subscription_start_date' => sanitize_text_field($_POST['subscription_start_date'] ?? ''),
			'subscription_end_date'   => sanitize_text_field($_POST['subscription_end_date'] ?? ''),
			'subscription_cost'       => floatval($_POST['subscription_cost'] ?? 0.00),
			'currency'                => sanitize_text_field($_POST['currency'] ?? 'USD'),
			'auto_renewal'            => intval($_POST['auto_renewal'] ?? 0),

			// Account Details (4 fields)
			'plan_type'               => sanitize_text_field($_POST['plan_type'] ?? ''),
			'profile_limit'           => intval($_POST['profile_limit'] ?? 1),
			'video_quality'           => sanitize_text_field($_POST['video_quality'] ?? ''),
			'account_region'          => sanitize_text_field($_POST['account_region'] ?? ''),

			// Security (3 fields)
			'last_password_changed'   => sanitize_text_field($_POST['last_password_changed'] ?? ''),
			'has_2fa'                 => intval($_POST['has_2fa'] ?? 0),
			'security_level'          => sanitize_text_field($_POST['security_level'] ?? 'medium'),

			// Business Intelligence (3 fields) - Usually readonly, but include for completeness
			'total_revenue'           => floatval($_POST['total_revenue'] ?? 0.00),
			'total_licenses_served'   => intval($_POST['total_licenses_served'] ?? 0),
			'success_rate'            => floatval($_POST['success_rate'] ?? 0.00)
		);
	}

	/**
	 * Redirect with message parameters
	 *
	 * @since 1.0.0
	 * @param string $url     Redirect URL
	 * @param string $message Message to display
	 * @param string $type    Message type (success|error)
	 */
	private static function redirect_with_message($url, $message, $type = 'success') {
		$url = add_query_arg(array(
			'message' => urlencode($message),
			'type'    => $type
		), $url);

		wp_safe_redirect($url);
		exit;
	}
}
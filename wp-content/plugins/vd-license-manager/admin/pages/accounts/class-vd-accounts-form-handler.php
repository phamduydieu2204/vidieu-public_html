<?php
/**
 * Provider Accounts Form Handler
 *
 * Processes form submissions for add/edit provider accounts
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
 * Form handler for provider accounts
 *
 * Handles form processing, validation, and database operations
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Accounts_Form_Handler {

	/**
	 * Main entry point for form submission
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function handle_save() {
		// Check user permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to perform this action.', 'vd-license-manager'));
		}

		// Verify nonce
		self::verify_nonce();

		// Determine if this is add or edit
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
	 * @return void
	 */
	private static function process_add() {
		// Get form data
		$data = self::get_form_data();

		// Sanitize data
		$data = VD_Account_Validator::sanitize_account_data($data);

		// Validate data
		$validation = VD_Account_Validator::validate_add($data);

		if (is_wp_error($validation)) {
			// Validation failed - redirect back with errors
			$error_message = implode('<br>', $validation->get_error_messages());
			self::redirect_with_message(
				admin_url('admin.php?page=vd-provider-accounts&action=add'),
				$error_message,
				'error'
			);
			return;
		}

		// Insert account into database
		$account_id = VD_Accounts_Repository::insert_account($data);

		if (is_wp_error($account_id)) {
			// Insert failed
			self::redirect_with_message(
				admin_url('admin.php?page=vd-provider-accounts&action=add'),
				$account_id->get_error_message(),
				'error'
			);
			return;
		}

		// Success - redirect to list page
		self::redirect_with_message(
			admin_url('admin.php?page=vd-provider-accounts'),
			__('Tài khoản đã được tạo thành công!', 'vd-license-manager'),
			'success'
		);
	}

	/**
	 * Process account update
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID to update
	 * @return void
	 */
	private static function process_update($account_id) {
		// Verify account exists
		$account = VD_Accounts_Repository::get_account($account_id);

		if (!$account) {
			wp_die(__('Không tìm thấy tài khoản.', 'vd-license-manager'));
		}

		// Get form data
		$data = self::get_form_data();

		// Sanitize data
		$data = VD_Account_Validator::sanitize_account_data($data);

		// Validate data
		$validation = VD_Account_Validator::validate_update($account_id, $data);

		if (is_wp_error($validation)) {
			// Validation failed
			$error_message = implode('<br>', $validation->get_error_messages());
			self::redirect_with_message(
				admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $account_id),
				$error_message,
				'error'
			);
			return;
		}

		// Update account in database
		$result = VD_Accounts_Repository::update_account($account_id, $data);

		if (is_wp_error($result)) {
			// Update failed
			self::redirect_with_message(
				admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $account_id),
				$result->get_error_message(),
				'error'
			);
			return;
		}

		// Success - redirect to list page
		self::redirect_with_message(
			admin_url('admin.php?page=vd-provider-accounts'),
			__('Tài khoản đã được cập nhật thành công!', 'vd-license-manager'),
			'success'
		);
	}

	/**
	 * Verify nonce for security
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function verify_nonce() {
		if (!isset($_POST['vd_account_nonce']) ||
			!wp_verify_nonce($_POST['vd_account_nonce'], 'vd_save_account')) {
			wp_die(__('Kiểm tra bảo mật thất bại. Vui lòng thử lại.', 'vd-license-manager'));
		}
	}

	/**
	 * Extract form data from $_POST
	 *
	 * @since 1.0.0
	 * @return array Form data (24 fields total)
	 */
	private static function get_form_data() {
		return array(
			// Core fields (13 original fields)
			'provider'        => isset($_POST['provider']) ? $_POST['provider'] : '',
			'account_login'   => isset($_POST['account_login']) ? $_POST['account_login'] : '',
			'display_name'    => isset($_POST['display_name']) ? $_POST['display_name'] : '',
			'capacity'        => isset($_POST['capacity']) ? $_POST['capacity'] : 5,
			'status'          => isset($_POST['status']) ? $_POST['status'] : 'active',

			// Authentication fields
			'cookie'          => isset($_POST['cookie']) ? $_POST['cookie'] : '',
			'login_email'     => isset($_POST['login_email']) ? $_POST['login_email'] : '',
			'login_password'  => isset($_POST['login_password']) ? $_POST['login_password'] : '',
			'totp_secret'     => isset($_POST['totp_secret']) ? $_POST['totp_secret'] : '',
			'recovery_email'  => isset($_POST['recovery_email']) ? $_POST['recovery_email'] : '',
			'recovery_phone'  => isset($_POST['recovery_phone']) ? $_POST['recovery_phone'] : '',
			'notes'           => isset($_POST['notes']) ? $_POST['notes'] : '',

			// NEW FIELDS (11 additional fields)
			// Subscription Management (5 fields)
			'subscription_start_date' => isset($_POST['subscription_start_date']) ? $_POST['subscription_start_date'] : '',
			'subscription_end_date'   => isset($_POST['subscription_end_date']) ? $_POST['subscription_end_date'] : '',
			'subscription_cost'       => isset($_POST['subscription_cost']) ? $_POST['subscription_cost'] : 0,
			'currency'                => isset($_POST['currency']) ? $_POST['currency'] : 'USD',
			'auto_renewal'            => isset($_POST['auto_renewal']) ? 1 : 0,

			// Account Details (3 fields)
			'plan_type'       => isset($_POST['plan_type']) ? $_POST['plan_type'] : '',
			'profile_limit'   => isset($_POST['profile_limit']) ? $_POST['profile_limit'] : 1,
			'account_region'  => isset($_POST['account_region']) ? $_POST['account_region'] : '',

			// Security (2 fields)
			'last_password_changed' => isset($_POST['last_password_changed']) ? $_POST['last_password_changed'] : '',
			'has_2fa'               => isset($_POST['has_2fa']) ? 1 : 0,

			// Business Intelligence (2 fields) - Usually read-only, but allow manual override
			'total_revenue'         => isset($_POST['total_revenue']) ? $_POST['total_revenue'] : 0,
			'total_licenses_served' => isset($_POST['total_licenses_served']) ? $_POST['total_licenses_served'] : 0
		);
	}

	/**
	 * Redirect with success or error message
	 *
	 * @since 1.0.0
	 * @param string $url     Redirect URL
	 * @param string $message Message to display
	 * @param string $type    Message type (success|error)
	 * @return void
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
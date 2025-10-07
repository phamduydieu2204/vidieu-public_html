<?php
/**
 * Provider Accounts Repository class for VD License Manager
 *
 * Handles all database operations for provider accounts
 *
 * @package VD_License_Manager
 * @subpackage Repositories
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * VD Accounts Repository class
 *
 * Static methods for provider accounts database operations
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Accounts_Repository {

	/**
	 * Get accounts list with filters and pagination
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments
	 * @return array Array of account objects
	 */
	public static function get_accounts($args = array()) {
		global $wpdb;

		$defaults = array(
			'offset'  => 0,
			'limit'   => 20,
			'search'  => '',
			'provider' => '',
			'status'  => '',
			'orderby' => 'id',
			'order'   => 'DESC'
		);

		$args = wp_parse_args($args, $defaults);

		$table = 'bz_vd_provider_accounts';
		$where_clauses = array();
		$where_values = array();

		// Build WHERE clauses
		if (!empty($args['search'])) {
			$search = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
			$where_clauses[] = '(display_name LIKE %s OR account_login LIKE %s)';
			$where_values[] = $search;
			$where_values[] = $search;
		}

		if (!empty($args['provider'])) {
			$where_clauses[] = 'provider = %s';
			$where_values[] = sanitize_text_field($args['provider']);
		}

		if (!empty($args['status'])) {
			$where_clauses[] = 'status = %s';
			$where_values[] = sanitize_text_field($args['status']);
		}

		$where_sql = '';
		if (!empty($where_clauses)) {
			$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
		}

		// Validate orderby and order
		$allowed_orderby = array('id', 'provider', 'display_name', 'status', 'created_at');
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
			error_log('VD License Manager: Database error in get_accounts - ' . $wpdb->last_error);
			return array();
		}

		return $results ? $results : array();
	}

	/**
	 * Get single account by ID
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return object|null Account object or null if not found
	 */
	public static function get_account($account_id) {
		global $wpdb;

		$account_id = intval($account_id);
		if ($account_id <= 0) {
			return null;
		}

		$table = 'bz_vd_provider_accounts';
		$sql = "SELECT * FROM {$table} WHERE id = %d";

		$result = $wpdb->get_row($wpdb->prepare($sql, $account_id));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_account - ' . $wpdb->last_error);
			return null;
		}

		return $result;
	}

	/**
	 * Count total accounts for pagination
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments (same filters as get_accounts)
	 * @return int Total count
	 */
	public static function get_total_count($args = array()) {
		global $wpdb;

		$table = 'bz_vd_provider_accounts';
		$where_clauses = array();
		$where_values = array();

		// Build WHERE clauses (same as get_accounts)
		if (!empty($args['search'])) {
			$search = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
			$where_clauses[] = '(display_name LIKE %s OR account_login LIKE %s)';
			$where_values[] = $search;
			$where_values[] = $search;
		}

		if (!empty($args['provider'])) {
			$where_clauses[] = 'provider = %s';
			$where_values[] = sanitize_text_field($args['provider']);
		}

		if (!empty($args['status'])) {
			$where_clauses[] = 'status = %s';
			$where_values[] = sanitize_text_field($args['status']);
		}

		$where_sql = '';
		if (!empty($where_clauses)) {
			$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
		}

		$sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";

		if (!empty($where_values)) {
			$result = $wpdb->get_var($wpdb->prepare($sql, $where_values));
		} else {
			$result = $wpdb->get_var($sql);
		}

		return intval($result);
	}

	/**
	 * Create new provider account
	 *
	 * @since 1.0.0
	 * @param array $data Account data
	 * @return int|WP_Error Account ID on success, WP_Error on failure
	 */
	public static function insert_account($data) {
		global $wpdb;

		// Validate data
		$validation = VD_Account_Validator::validate_add($data);
		if (is_wp_error($validation)) {
			return $validation;
		}

		// Prepare insert data
		$insert_data = array(
			'provider'        => sanitize_text_field($data['provider']),
			'account_login'   => sanitize_text_field($data['account_login']),
			'display_name'    => sanitize_text_field($data['display_name']),
			'capacity'        => isset($data['capacity']) ? intval($data['capacity']) : 5,
			'status'          => isset($data['status']) ? sanitize_text_field($data['status']) : 'active',
			'cookie'          => isset($data['cookie']) ? wp_kses_post($data['cookie']) : null,
			'cookie_format'   => isset($data['cookie_format']) ? sanitize_text_field($data['cookie_format']) : 'json',
			'login_email'     => isset($data['login_email']) ? sanitize_email($data['login_email']) : null,
			'login_password'  => isset($data['login_password']) ? sanitize_text_field($data['login_password']) : null,
			'totp_secret'     => isset($data['totp_secret']) ? sanitize_text_field($data['totp_secret']) : null,
			'recovery_email'  => isset($data['recovery_email']) ? sanitize_email($data['recovery_email']) : null,
			'recovery_phone'  => isset($data['recovery_phone']) ? sanitize_text_field($data['recovery_phone']) : null,
			'notes'           => isset($data['notes']) ? wp_kses_post($data['notes']) : null,
			'created_at'      => current_time('mysql'),
			'updated_at'      => current_time('mysql')
		);

		$table = 'bz_vd_provider_accounts';

		// Debug logging
		error_log('VD License Manager: Attempting to insert into table: ' . $table);
		error_log('VD License Manager: Insert data: ' . print_r($insert_data, true));

		$result = $wpdb->insert($table, $insert_data);

		if ($result === false) {
			error_log('VD License Manager: Database error in insert_account - ' . $wpdb->last_error);
			error_log('VD License Manager: Last query: ' . $wpdb->last_query);
			return new WP_Error('db_insert_error', 'Failed to create account: ' . $wpdb->last_error);
		}

		if ($wpdb->insert_id) {
			error_log('VD License Manager: Account created successfully with ID: ' . $wpdb->insert_id);
			return $wpdb->insert_id;
		} else {
			error_log('VD License Manager: Insert succeeded but no insert_id returned');
			return new WP_Error('no_insert_id', 'Account may have been created but ID not returned');
		}
	}

	/**
	 * Update existing account
	 *
	 * @since 1.0.0
	 * @param int   $account_id Account ID
	 * @param array $data       Account data
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function update_account($account_id, $data) {
		global $wpdb;

		$account_id = intval($account_id);
		if ($account_id <= 0) {
			return new WP_Error('invalid_id', 'Invalid account ID');
		}

		// Validate data
		$validation = VD_Account_Validator::validate_update($account_id, $data);
		if (is_wp_error($validation)) {
			return $validation;
		}

		// Prepare update data
		$update_data = array();
		$fields = array('provider', 'account_login', 'display_name', 'capacity', 'status',
		               'cookie', 'cookie_format', 'login_email', 'login_password',
		               'totp_secret', 'recovery_email', 'recovery_phone', 'notes');

		foreach ($fields as $field) {
			if (isset($data[$field])) {
				switch ($field) {
					case 'capacity':
						$update_data[$field] = intval($data[$field]);
						break;
					case 'login_email':
					case 'recovery_email':
						$update_data[$field] = sanitize_email($data[$field]);
						break;
					case 'cookie':
					case 'notes':
						$update_data[$field] = wp_kses_post($data[$field]);
						break;
					default:
						$update_data[$field] = sanitize_text_field($data[$field]);
				}
			}
		}

		$update_data['updated_at'] = current_time('mysql');

		$table = 'bz_vd_provider_accounts';
		$result = $wpdb->update($table, $update_data, array('id' => $account_id));

		if ($result === false) {
			error_log('VD License Manager: Database error in update_account - ' . $wpdb->last_error);
			return new WP_Error('db_update_error', 'Failed to update account');
		}

		return true;
	}

	/**
	 * Delete account if safe
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function delete_account($account_id) {
		global $wpdb;

		$account_id = intval($account_id);
		if ($account_id <= 0) {
			return new WP_Error('invalid_id', 'Invalid account ID');
		}

		// Check if account has active assignments
		if (self::has_active_assignments($account_id)) {
			return new WP_Error('has_assignments', 'Cannot delete account with active license assignments');
		}

		$table = 'bz_vd_provider_accounts';
		$result = $wpdb->delete($table, array('id' => $account_id), array('%d'));

		if ($result === false) {
			error_log('VD License Manager: Database error in delete_account - ' . $wpdb->last_error);
			return new WP_Error('db_delete_error', 'Failed to delete account');
		}

		if ($result === 0) {
			return new WP_Error('not_found', 'Account not found');
		}

		return true;
	}

	/**
	 * Check if account already exists (prevent duplicates)
	 *
	 * @since 1.0.0
	 * @param string $provider      Provider name
	 * @param string $account_login Account login
	 * @return bool True if exists, false otherwise
	 */
	public static function account_exists($provider, $account_login) {
		global $wpdb;

		$table = 'bz_vd_provider_accounts';
		$sql = "SELECT id FROM {$table} WHERE provider = %s AND account_login = %s";

		$result = $wpdb->get_var($wpdb->prepare($sql,
			sanitize_text_field($provider),
			sanitize_text_field($account_login)
		));

		return !empty($result);
	}

	/**
	 * Check if account has active cookie assignments
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return bool True if has assignments, false otherwise
	 */
	public static function has_active_assignments($account_id) {
		global $wpdb;

		$account_id = intval($account_id);
		$table = 'bz_vd_cookie_assignments';
		$sql = "SELECT COUNT(*) FROM {$table} WHERE provider_account_id = %d AND status = 'active'";

		$count = $wpdb->get_var($wpdb->prepare($sql, $account_id));

		return intval($count) > 0;
	}

	/**
	 * Get list of distinct providers for filter dropdown
	 *
	 * @since 1.0.0
	 * @return array Array of provider names
	 */
	public static function get_providers() {
		global $wpdb;

		$table = 'bz_vd_provider_accounts';
		$sql = "SELECT DISTINCT provider FROM {$table} ORDER BY provider";

		$results = $wpdb->get_col($sql);

		return $results ? $results : array();
	}

	/**
	 * Calculate how many licenses using this account
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return array Array with 'used' and 'capacity' keys
	 */
	public static function get_account_usage($account_id) {
		global $wpdb;

		$account_id = intval($account_id);
		$table = 'bz_vd_cookie_assignments';
		$sql = "SELECT COUNT(*) FROM {$table} WHERE provider_account_id = %d AND status = 'active'";

		$used = intval($wpdb->get_var($wpdb->prepare($sql, $account_id)));

		// Get account capacity
		$account = self::get_account($account_id);
		$capacity = $account ? intval($account->capacity) : 0;

		return array(
			'used'     => $used,
			'capacity' => $capacity
		);
	}
}
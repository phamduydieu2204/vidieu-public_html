<?php
/**
 * Product Pools Repository
 *
 * Database operations for product pools (connections between products and accounts)
 *
 * @package VD_License_Manager
 * @subpackage Repositories
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Product Pools Repository Class
 *
 * Handles all database operations for product pools
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Pools_Repository {

	/**
	 * Table name without prefix
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TABLE_NAME = 'bz_vd_product_pools';

	/**
	 * Get full table name with WordPress prefix
	 *
	 * @since 1.0.0
	 * @return string Full table name
	 */
	private static function get_table_name() {
		return self::TABLE_NAME;
	}

	/**
	 * Insert a new pool entry
	 *
	 * @since 1.0.0
	 * @param array $data Pool data
	 * @return int|WP_Error Pool ID on success, WP_Error on failure
	 */
	public static function insert_pool($data) {
		global $wpdb;

		$table = self::get_table_name();

		// Prepare data for insertion
		$insert_data = array(
			'product_id' => isset($data['product_id']) ? intval($data['product_id']) : 0,
			'account_id' => isset($data['account_id']) ? intval($data['account_id']) : 0,
			'priority'   => isset($data['priority']) ? intval($data['priority']) : 0,
			'is_active'  => isset($data['is_active']) ? intval($data['is_active']) : 1,
		);

		// Validate required fields
		if ($insert_data['product_id'] <= 0) {
			return new WP_Error('invalid_product_id', __('Product ID is required and must be valid.', 'vd-license-manager'));
		}

		if ($insert_data['account_id'] <= 0) {
			return new WP_Error('invalid_account_id', __('Account ID is required and must be valid.', 'vd-license-manager'));
		}

		// Check if entry already exists
		if (self::pool_exists($insert_data['product_id'], $insert_data['account_id'])) {
			return new WP_Error('pool_exists', __('Pool entry already exists for this product and account.', 'vd-license-manager'));
		}

		// Insert into database
		$result = $wpdb->insert(
			$table,
			$insert_data,
			array('%d', '%d', '%d', '%d')
		);

		if ($result === false) {
			error_log('VD License Manager: Database error in insert_pool - ' . $wpdb->last_error);
			return new WP_Error('db_insert_error', __('Failed to insert pool entry.', 'vd-license-manager'));
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get a pool entry by ID
	 *
	 * @since 1.0.0
	 * @param int $pool_id Pool ID
	 * @return object|null Pool object or null if not found
	 */
	public static function get_pool($pool_id) {
		global $wpdb;

		$pool_id = intval($pool_id);
		if ($pool_id <= 0) {
			return null;
		}

		$table = self::get_table_name();

		$pool = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$pool_id
		));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_pool - ' . $wpdb->last_error);
			return null;
		}

		return $pool;
	}

	/**
	 * Get all pool entries for a product
	 *
	 * @since 1.0.0
	 * @param int  $product_id   Product ID
	 * @param bool $active_only  Only return active pools
	 * @return array Array of pool objects
	 */
	public static function get_pools_by_product($product_id, $active_only = true) {
		global $wpdb;

		$product_id = intval($product_id);
		if ($product_id <= 0) {
			return array();
		}

		$table = self::get_table_name();

		$where = "WHERE product_id = %d";
		$params = array($product_id);

		if ($active_only) {
			$where .= " AND is_active = 1";
		}

		$pools = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$table} {$where} ORDER BY priority ASC, id ASC",
			$params
		));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_pools_by_product - ' . $wpdb->last_error);
			return array();
		}

		return $pools ? $pools : array();
	}

	/**
	 * Get all pool entries for an account
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return array Array of pool objects
	 */
	public static function get_pools_by_account($account_id) {
		global $wpdb;

		$account_id = intval($account_id);
		if ($account_id <= 0) {
			return array();
		}

		$table = self::get_table_name();

		$pools = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$table} WHERE account_id = %d ORDER BY priority ASC, id ASC",
			$account_id
		));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_pools_by_account - ' . $wpdb->last_error);
			return array();
		}

		return $pools ? $pools : array();
	}

	/**
	 * Get all pools with filters
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments
	 * @return array Array of pool objects
	 */
	public static function get_all_pools($args = array()) {
		global $wpdb;

		$table = self::get_table_name();

		// Default arguments
		$defaults = array(
			'product_id' => null,
			'account_id' => null,
			'is_active'  => null,
			'orderby'    => 'id',
			'order'      => 'DESC',
			'limit'      => 20,
			'offset'     => 0
		);

		$args = wp_parse_args($args, $defaults);

		// Build WHERE clause
		$where = array('1=1');
		$params = array();

		if (!is_null($args['product_id']) && intval($args['product_id']) > 0) {
			$where[] = 'product_id = %d';
			$params[] = intval($args['product_id']);
		}

		if (!is_null($args['account_id']) && intval($args['account_id']) > 0) {
			$where[] = 'account_id = %d';
			$params[] = intval($args['account_id']);
		}

		if (!is_null($args['is_active'])) {
			$where[] = 'is_active = %d';
			$params[] = intval($args['is_active']);
		}

		$where_clause = implode(' AND ', $where);

		// Build ORDER BY clause
		$allowed_orderby = array('id', 'product_id', 'account_id', 'priority', 'is_active', 'created_at', 'updated_at');
		$orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'id';
		$order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

		// Build LIMIT clause
		$limit_clause = '';
		if ($args['limit'] > 0) {
			$limit_clause = $wpdb->prepare('LIMIT %d OFFSET %d', intval($args['limit']), intval($args['offset']));
		}

		// Build full query
		$query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} {$limit_clause}";

		if (!empty($params)) {
			$query = $wpdb->prepare($query, $params);
		}

		$pools = $wpdb->get_results($query);

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in get_all_pools - ' . $wpdb->last_error);
			return array();
		}

		return $pools ? $pools : array();
	}

	/**
	 * Update a pool entry
	 *
	 * @since 1.0.0
	 * @param int   $pool_id Pool ID
	 * @param array $data    Updated data
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function update_pool($pool_id, $data) {
		global $wpdb;

		$pool_id = intval($pool_id);
		if ($pool_id <= 0) {
			return new WP_Error('invalid_pool_id', __('Pool ID is required and must be valid.', 'vd-license-manager'));
		}

		// Check if pool exists
		$existing_pool = self::get_pool($pool_id);
		if (!$existing_pool) {
			return new WP_Error('pool_not_found', __('Pool entry not found.', 'vd-license-manager'));
		}

		$table = self::get_table_name();

		// Prepare data for update
		$update_data = array();
		$format = array();

		if (isset($data['product_id']) && intval($data['product_id']) > 0) {
			$update_data['product_id'] = intval($data['product_id']);
			$format[] = '%d';
		}
		if (isset($data['account_id']) && intval($data['account_id']) > 0) {
			$update_data['account_id'] = intval($data['account_id']);
			$format[] = '%d';
		}
		if (isset($data['priority'])) {
			$update_data['priority'] = intval($data['priority']);
			$format[] = '%d';
		}
		if (isset($data['is_active'])) {
			$update_data['is_active'] = intval($data['is_active']);
			$format[] = '%d';
		}

		if (empty($update_data)) {
			return new WP_Error('no_data', __('No data to update.', 'vd-license-manager'));
		}

		// Check for duplicate if product_id or account_id changed
		if (isset($update_data['product_id']) || isset($update_data['account_id'])) {
			$new_product_id = isset($update_data['product_id']) ? $update_data['product_id'] : $existing_pool->product_id;
			$new_account_id = isset($update_data['account_id']) ? $update_data['account_id'] : $existing_pool->account_id;

			if (($new_product_id != $existing_pool->product_id || $new_account_id != $existing_pool->account_id) &&
				self::pool_exists($new_product_id, $new_account_id)) {
				return new WP_Error('pool_exists', __('Pool entry already exists for this product and account.', 'vd-license-manager'));
			}
		}

		// Update database
		$result = $wpdb->update(
			$table,
			$update_data,
			array('id' => $pool_id),
			$format,
			array('%d')
		);

		if ($result === false) {
			error_log('VD License Manager: Database error in update_pool - ' . $wpdb->last_error);
			return new WP_Error('db_update_error', __('Failed to update pool entry.', 'vd-license-manager'));
		}

		return true;
	}

	/**
	 * Delete a pool entry
	 *
	 * @since 1.0.0
	 * @param int $pool_id Pool ID
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function delete_pool($pool_id) {
		global $wpdb;

		$pool_id = intval($pool_id);
		if ($pool_id <= 0) {
			return new WP_Error('invalid_pool_id', __('Pool ID is required and must be valid.', 'vd-license-manager'));
		}

		$table = self::get_table_name();

		$result = $wpdb->delete(
			$table,
			array('id' => $pool_id),
			array('%d')
		);

		if ($result === false) {
			error_log('VD License Manager: Database error in delete_pool - ' . $wpdb->last_error);
			return new WP_Error('db_delete_error', __('Failed to delete pool entry.', 'vd-license-manager'));
		}

		if ($result === 0) {
			return new WP_Error('pool_not_found', __('Pool entry not found.', 'vd-license-manager'));
		}

		return true;
	}

	/**
	 * Delete all pools for a product
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID
	 * @return int|WP_Error Number of deleted rows or WP_Error
	 */
	public static function delete_pools_by_product($product_id) {
		global $wpdb;

		$product_id = intval($product_id);
		if ($product_id <= 0) {
			return new WP_Error('invalid_product_id', __('Product ID is required and must be valid.', 'vd-license-manager'));
		}

		$table = self::get_table_name();

		$result = $wpdb->delete(
			$table,
			array('product_id' => $product_id),
			array('%d')
		);

		if ($result === false) {
			error_log('VD License Manager: Database error in delete_pools_by_product - ' . $wpdb->last_error);
			return new WP_Error('db_delete_error', __('Failed to delete pool entries.', 'vd-license-manager'));
		}

		return intval($result);
	}

	/**
	 * Delete all pools for an account
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return int|WP_Error Number of deleted rows or WP_Error
	 */
	public static function delete_pools_by_account($account_id) {
		global $wpdb;

		$account_id = intval($account_id);
		if ($account_id <= 0) {
			return new WP_Error('invalid_account_id', __('Account ID is required and must be valid.', 'vd-license-manager'));
		}

		$table = self::get_table_name();

		$result = $wpdb->delete(
			$table,
			array('account_id' => $account_id),
			array('%d')
		);

		if ($result === false) {
			error_log('VD License Manager: Database error in delete_pools_by_account - ' . $wpdb->last_error);
			return new WP_Error('db_delete_error', __('Failed to delete pool entries.', 'vd-license-manager'));
		}

		return intval($result);
	}

	/**
	 * Check if pool entry exists
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID
	 * @param int $account_id Account ID
	 * @return bool True if exists, false otherwise
	 */
	public static function pool_exists($product_id, $account_id) {
		global $wpdb;

		$product_id = intval($product_id);
		$account_id = intval($account_id);

		if ($product_id <= 0 || $account_id <= 0) {
			return false;
		}

		$table = self::get_table_name();

		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND account_id = %d",
			$product_id,
			$account_id
		));

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in pool_exists - ' . $wpdb->last_error);
			return false;
		}

		return intval($count) > 0;
	}

	/**
	 * Count pools with filters
	 *
	 * @since 1.0.0
	 * @param array $args Filter arguments
	 * @return int Total count
	 */
	public static function count_pools($args = array()) {
		global $wpdb;

		$table = self::get_table_name();

		// Build WHERE clause
		$where = array('1=1');
		$params = array();

		if (isset($args['product_id']) && !is_null($args['product_id']) && intval($args['product_id']) > 0) {
			$where[] = 'product_id = %d';
			$params[] = intval($args['product_id']);
		}

		if (isset($args['account_id']) && !is_null($args['account_id']) && intval($args['account_id']) > 0) {
			$where[] = 'account_id = %d';
			$params[] = intval($args['account_id']);
		}

		if (isset($args['is_active']) && !is_null($args['is_active'])) {
			$where[] = 'is_active = %d';
			$params[] = intval($args['is_active']);
		}

		$where_clause = implode(' AND ', $where);

		$query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";

		if (!empty($params)) {
			$query = $wpdb->prepare($query, $params);
		}

		$count = $wpdb->get_var($query);

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in count_pools - ' . $wpdb->last_error);
			return 0;
		}

		return intval($count);
	}
}
<?php
/**
 * WooCommerce Order Handler
 *
 * Handles order completion events and triggers license assignment
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class VD_LM_Order_Handler {

    /**
     * Email handler instance
     *
     * @var VD_LM_Email_Handler
     */
    private $email_handler;

    /**
     * Column mapping for bz_vd_provider_accounts table
     *
     * EXACT DATABASE SCHEMA (CONFIRMED from user specification):
     * - id (bigint) - Primary key ✅
     * - provider (varchar 100) - Provider name: Netflix, Spotify, Helium10 ✅
     * - account_login (varchar 255) - Login username or email ✅
     * - display_name (varchar 255) - Admin display name ✅
     * - login_password (text) - Login password (encrypted) - NOT 'account_password'
     * - capacity (int) - Maximum licenses this account can serve ✅
     * - status (enum) - active, inactive, suspended ✅
     * - current_usage (int) - Current number of active licenses ✅
     * - expires_at (datetime) - Provider account expiration date ✅
     * - cookie (longtext) - Session cookies
     * - security_question (varchar 255) - Security question text
     * - security_answer (longtext) - Security answer
     * - account_password (longtext) - Alternative password field
     * - notes (text) - Internal admin notes
     * - created_at, updated_at (datetime) ✅
     *
     * Maps logical names to actual database column names for safety
     */
    const ACCOUNT_COLUMNS = array(
        'id' => 'id',
        'login' => 'account_login',
        'password' => 'login_password',        // CORRECTED: was 'account_password'
        'alt_password' => 'account_password',  // Alternative password field
        'provider' => 'provider',
        'display_name' => 'display_name',
        'status' => 'status',
        'capacity' => 'capacity',
        'current_usage' => 'current_usage',
        'expires_at' => 'expires_at',
        'cookie' => 'cookie',
        'security_question' => 'security_question',
        'security_answer' => 'security_answer',
        'notes' => 'notes',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at'
    );

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize email handler
        $this->email_handler = new VD_LM_Email_Handler();

        // Hook into order status change
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'), 10, 2);

        error_log('VD Order Handler: Registered WooCommerce hooks');
        error_log('VD Order Handler: Expected account table schema: ' . implode(', ', self::ACCOUNT_COLUMNS));
        error_log('VD Order Handler: CRITICAL MAPPINGS - login→account_login, password→login_password, service→provider');
    }

    /**
     * Handle order completed event
     *
     * @param int $order_id Order ID
     * @param WC_Order $order Order object
     */
    public function handle_order_completed($order_id, $order = null) {
        error_log('=== VD ORDER COMPLETED: ID ' . $order_id . ' ===');

        // Get order object if not provided
        if (!$order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            error_log('VD Order Handler: Order not found: ' . $order_id);
            return;
        }

        // Check if already processed
        $processed = $order->get_meta('_vd_licenses_processed', true);
        if ($processed) {
            error_log('VD Order Handler: Order already processed, skipping: ' . $order_id);
            return;
        }

        error_log('VD Order Handler: Processing order ' . $order_id);
        error_log('VD Order Handler: Customer email: ' . $order->get_billing_email());
        error_log('VD Order Handler: Customer name: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name());

        // Get order items
        $items = $order->get_items();

        if (empty($items)) {
            error_log('VD Order Handler: No items in order ' . $order_id);
            return;
        }

        error_log('VD Order Handler: Found ' . count($items) . ' items in order');

        $licenses_assigned = 0;
        $licenses_failed = 0;

        // Process each item
        foreach ($items as $item_id => $item) {
            $product_id = $item->get_product_id();
            $product_name = $item->get_name();
            $quantity = $item->get_quantity();

            error_log('VD Order Handler: Processing item - Product ID: ' . $product_id . ', Name: ' . $product_name . ', Qty: ' . $quantity);

            // Get license keys for this item from LMfWC
            $license_keys = $this->get_license_keys_for_item($order_id, $item_id, $product_id);

            if (empty($license_keys)) {
                error_log('VD Order Handler: No license keys found for product ' . $product_id);
                continue;
            }

            error_log('VD Order Handler: Found ' . count($license_keys) . ' license keys for product ' . $product_id);

            // Process each license key
            foreach ($license_keys as $license_data) {
                $license_key = $license_data['license_key'];
                $lmfwc_license_id = $license_data['id'];

                error_log('VD Order Handler: Processing license key: ' . $license_key . ' (LMfWC ID: ' . $lmfwc_license_id . ')');

                $result = $this->process_license_assignment(
                    $order_id,
                    $order,
                    $product_id,
                    $license_key,
                    $lmfwc_license_id
                );

                if ($result['success']) {
                    $licenses_assigned++;
                    error_log('VD Order Handler: License assigned successfully: ' . $license_key);

                    // Send credentials email to customer
                    $this->send_credentials_email($order, $product_id, $license_key, $result['account_id']);
                } else {
                    $licenses_failed++;
                    error_log('VD Order Handler: License assignment failed: ' . $license_key . ' - Reason: ' . $result['message']);
                }
            }
        }

        // Mark order as processed
        $order->update_meta_data('_vd_licenses_processed', true);
        $order->update_meta_data('_vd_licenses_assigned', $licenses_assigned);
        $order->update_meta_data('_vd_licenses_failed', $licenses_failed);
        $order->save();

        error_log('VD Order Handler: Order ' . $order_id . ' processing complete - Assigned: ' . $licenses_assigned . ', Failed: ' . $licenses_failed);

        // Add order note
        $order->add_order_note(
            sprintf(
                'VD License Manager: %d license(s) assigned successfully, %d failed.',
                $licenses_assigned,
                $licenses_failed
            )
        );
    }

    /**
     * Get license keys for order item from LMfWC
     *
     * @param int $order_id Order ID
     * @param int $item_id Order item ID
     * @param int $product_id Product ID
     * @return array License keys with IDs
     */
    private function get_license_keys_for_item($order_id, $item_id, $product_id) {
        global $wpdb;

        error_log('VD Order Handler: Fetching license keys from LMfWC for order ' . $order_id . ', product ' . $product_id);

        // LMfWC stores licenses in lmfwc_licenses table
        // They're linked to orders via order_id
        $table = $wpdb->prefix . 'lmfwc_licenses';

        // Get licenses for this order and product
        $licenses = $wpdb->get_results($wpdb->prepare(
            "SELECT id, license_key, status, valid_for, expires_at
            FROM {$table}
            WHERE order_id = %d
            AND product_id = %d
            AND status IN (2, 3)",  // 2=SOLD, 3=DELIVERED
            $order_id,
            $product_id
        ), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('VD Order Handler: Database error fetching licenses: ' . $wpdb->last_error);
            return array();
        }

        if (empty($licenses)) {
            error_log('VD Order Handler: No licenses found in LMfWC table for order ' . $order_id . ', product ' . $product_id);
            return array();
        }

        error_log('VD Order Handler: Retrieved ' . count($licenses) . ' licenses from LMfWC');

        // Log license details
        foreach ($licenses as $license) {
            error_log('VD Order Handler: License - Key: ' . $license['license_key'] . ', Status: ' . $license['status'] . ', Valid for: ' . $license['valid_for'] . ' days');
        }

        return $licenses;
    }

    /**
     * Process license assignment for a single license key
     *
     * @param int $order_id Order ID
     * @param WC_Order $order Order object
     * @param int $product_id Product ID
     * @param string $license_key License key
     * @param int $lmfwc_license_id LMfWC license ID
     * @return array Result array with 'success' and 'message'
     */
    private function process_license_assignment($order_id, $order, $product_id, $license_key, $lmfwc_license_id) {
        error_log('VD License Assignment: Starting for license ' . $license_key . ', product ' . $product_id);

        // Step 1: Get share config for product
        $share_config = $this->get_share_config($product_id);

        if (!$share_config) {
            return array(
                'success' => false,
                'message' => 'No share config found for product ' . $product_id
            );
        }

        error_log('VD License Assignment: Share config found - max_devices: ' . $share_config['max_devices'] . ', validity_days: ' . $share_config['validity_days']);

        // Step 2: Find pool assigned to this product
        $pool = $this->get_pool_for_product($product_id);

        if (!$pool) {
            return array(
                'success' => false,
                'message' => 'No pool assigned to product ' . $product_id
            );
        }

        error_log('VD License Assignment: Pool found - ID: ' . $pool['id'] . ', Name: ' . $pool['pool_name']);

        // Step 3: Find available account in pool
        $account = $this->get_available_account($pool['id']);

        if (!$account) {
            return array(
                'success' => false,
                'message' => 'No available account in pool ' . $pool['pool_name']
            );
        }

        error_log('VD License Assignment: Account found - ID: ' . $account['id'] . ', Login: ' . $account['account_login']);

        // Step 4: Create license record
        $license_id = $this->create_license_record(
            $license_key,
            $lmfwc_license_id,
            $order_id,
            $order,
            $product_id,
            $pool['id'],
            $account['id'],
            $share_config
        );

        if (!$license_id) {
            return array(
                'success' => false,
                'message' => 'Failed to create license record in database'
            );
        }

        error_log('VD License Assignment: License record created - ID: ' . $license_id);

        // Step 5: Update account usage
        $this->update_account_usage($account['id']);

        return array(
            'success' => true,
            'message' => 'License assigned successfully',
            'license_id' => $license_id,
            'account_id' => $account['id']
        );
    }

    /**
     * Get share config for product
     *
     * @param int $product_id Product ID
     * @return array|null Share config or null
     */
    private function get_share_config($product_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'vd_product_share_configs';

        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d",
            $product_id
        ), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('VD License Assignment: Error fetching share config: ' . $wpdb->last_error);
            return null;
        }

        return $config;
    }

    /**
     * Get pool assigned to product
     *
     * @param int $product_id Product ID
     * @return array|null Pool data or null
     */
    private function get_pool_for_product($product_id) {
        global $wpdb;

        error_log('VD License Assignment: === SEARCHING FOR POOL ===');
        error_log('VD License Assignment: Product ID: ' . $product_id);

        // Get pool directly from product_pools table
        $table = $wpdb->prefix . 'vd_product_pools';
        error_log('VD License Assignment: Product pools table: ' . $table);

        // First check if any pools exist for this product
        $all_pools = $wpdb->get_results($wpdb->prepare(
            "SELECT pp.*, p.name as pool_name, p.status as pool_status
            FROM {$table} pp
            LEFT JOIN {$wpdb->prefix}vd_pools p ON pp.pool_id = p.id
            WHERE pp.product_id = %d
            ORDER BY pp.priority ASC",
            $product_id
        ), ARRAY_A);

        error_log('VD License Assignment: Found ' . count($all_pools) . ' pool assignments for product');
        foreach ($all_pools as $pool_assignment) {
            error_log('  - Pool ID: ' . $pool_assignment['pool_id'] .
                     ', Name: ' . ($pool_assignment['pool_name'] ?: 'NULL') .
                     ', Priority: ' . $pool_assignment['priority'] .
                     ', Assignment Status: ' . $pool_assignment['status'] .
                     ', Pool Status: ' . ($pool_assignment['pool_status'] ?: 'NULL'));
        }

        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT pp.*, p.name as pool_name
            FROM {$table} pp
            JOIN {$wpdb->prefix}vd_pools p ON pp.pool_id = p.id
            WHERE pp.product_id = %d
            AND pp.status = 'active'
            AND p.status = 'active'
            ORDER BY pp.priority ASC
            LIMIT 1",
            $product_id
        ), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('VD License Assignment: Database error fetching pool: ' . $wpdb->last_error);
            error_log('VD License Assignment: Last query: ' . $wpdb->last_query);
            return null;
        }

        if (!$pool) {
            error_log('VD License Assignment: ✗ No active pool found for product ' . $product_id);
            error_log('VD License Assignment: Check if product has any pool assignments and if pools are active');
            return null;
        }

        error_log('VD License Assignment: ✓ Found active pool');
        error_log('VD License Assignment: Pool ID: ' . $pool['pool_id']);
        error_log('VD License Assignment: Pool Name: ' . $pool['pool_name']);
        error_log('VD License Assignment: Priority: ' . $pool['priority']);

        return $pool;
    }

    /**
     * Get available account from pool
     *
     * @param int $pool_id Pool ID
     * @return array|null Account data or null
     */
    private function get_available_account($pool_id) {
        global $wpdb;

        error_log('VD License Assignment: === SEARCHING FOR AVAILABLE ACCOUNT ===');
        error_log('VD License Assignment: Pool ID: ' . $pool_id);
        error_log('VD License Assignment: Provider accounts table: ' . $wpdb->prefix . 'vd_provider_accounts');
        error_log('VD License Assignment: Pool accounts table: ' . $wpdb->prefix . 'vd_pool_accounts');

        // First, check if pool has any accounts assigned
        $pool_account_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_pool_accounts WHERE pool_id = %d",
            $pool_id
        ));
        error_log('VD License Assignment: Pool has ' . $pool_account_count . ' account assignments');

        if ($pool_account_count == 0) {
            error_log('VD License Assignment: No accounts assigned to pool ' . $pool_id);
            return null;
        }

        // Check all accounts in this pool (for debugging)
        $all_pool_accounts = $wpdb->get_results($wpdb->prepare(
            "SELECT pa.*, a.account_login, a.current_usage, a.capacity, a.status as account_status
            FROM {$wpdb->prefix}vd_pool_accounts pa
            LEFT JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
            WHERE pa.pool_id = %d",
            $pool_id
        ), ARRAY_A);

        error_log('VD License Assignment: All accounts in pool:');
        foreach ($all_pool_accounts as $account) {
            error_log('  - Account ID: ' . $account['account_id'] .
                     ', Login: ' . ($account['account_login'] ?: 'NULL') .
                     ', Usage: ' . ($account['current_usage'] ?: '0') . '/' . ($account['capacity'] ?: '0') .
                     ', Account Status: ' . ($account['account_status'] ?: 'NULL') .
                     ', Pool Status: ' . $account['status'] .
                     ', Weight: ' . $account['weight']);
        }

        // Find account that is:
        // 1. In this pool (via pool_accounts mapping)
        // 2. Status = 'active'
        // 3. Has available capacity (current_usage < capacity)
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, pa.weight, pa.status as pool_assignment_status
            FROM {$wpdb->prefix}vd_provider_accounts a
            INNER JOIN {$wpdb->prefix}vd_pool_accounts pa ON a.id = pa.account_id
            WHERE pa.pool_id = %d
            AND pa.status = 'active'
            AND a.status = 'active'
            AND a.current_usage < a.capacity
            ORDER BY a.current_usage ASC, pa.weight DESC
            LIMIT 1",
            $pool_id
        ), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('VD License Assignment: Database error: ' . $wpdb->last_error);
            error_log('VD License Assignment: Last query: ' . $wpdb->last_query);
            return null;
        }

        if ($account) {
            error_log('VD License Assignment: ✓ Found available account');

            // COMPREHENSIVE SCHEMA DEBUG - Shows exact database columns
            error_log('VD SCHEMA DEBUG: Account Full Data: ' . print_r($account, true));
            error_log('VD SCHEMA DEBUG: Available Keys: ' . implode(', ', array_keys($account)));

            // Standard account info using actual column names
            error_log('VD License Assignment: Account ID: ' . $account['id']);
            error_log('VD License Assignment: Account Login: ' . $this->get_account_field($account, 'login'));
            error_log('VD License Assignment: Provider: ' . $this->get_account_field($account, 'provider'));
            error_log('VD License Assignment: Current Usage: ' . $this->get_account_field($account, 'current_usage') .
                     '/' . $this->get_account_field($account, 'capacity'));
            error_log('VD License Assignment: Status: ' . $this->get_account_field($account, 'status'));
            error_log('VD License Assignment: Weight: ' . (isset($account['weight']) ? $account['weight'] : 'N/A'));

            // Enhanced debug: Show exact column access results
            error_log('VD ACCOUNT VERIFICATION: Direct field access test:');
            error_log('  - $account[\'account_login\']: ' . (isset($account['account_login']) ? $account['account_login'] : 'MISSING'));
            error_log('  - $account[\'provider\']: ' . (isset($account['provider']) ? $account['provider'] : 'MISSING'));
            error_log('  - $account[\'login_password\']: ' . (isset($account['login_password']) ? 'SET' : 'MISSING'));
            error_log('  - $account[\'account_password\']: ' . (isset($account['account_password']) ? 'SET' : 'MISSING'));
            error_log('  - $account[\'current_usage\']: ' . (isset($account['current_usage']) ? $account['current_usage'] : 'MISSING'));
            error_log('  - $account[\'capacity\']: ' . (isset($account['capacity']) ? $account['capacity'] : 'MISSING'));

            // Verify expected vs actual schema
            $this->verify_account_schema($account);
        } else {
            error_log('VD License Assignment: ✗ No available account found in pool ' . $pool_id);
            error_log('VD License Assignment: All accounts may be at capacity or inactive');
        }

        return $account;
    }

    /**
     * Get account field safely using column mapping
     *
     * @param array $account Account data array
     * @param string $field Logical field name
     * @return mixed Field value or default
     */
    private function get_account_field($account, $field) {
        if (!is_array($account)) {
            return 'NULL_ACCOUNT';
        }

        // Use column mapping to get actual database column name
        $actual_column = isset(self::ACCOUNT_COLUMNS[$field]) ? self::ACCOUNT_COLUMNS[$field] : $field;

        if (isset($account[$actual_column])) {
            return $account[$actual_column];
        }

        // Fallback - try the logical name directly
        if (isset($account[$field])) {
            return $account[$field];
        }

        return 'MISSING_' . strtoupper($field);
    }

    /**
     * Verify account schema matches expected columns
     *
     * @param array $account Account data
     */
    private function verify_account_schema($account) {
        error_log('VD SCHEMA VERIFICATION: === Checking Account Schema ===');

        $expected_columns = array_values(self::ACCOUNT_COLUMNS);
        $actual_columns = array_keys($account);

        error_log('VD SCHEMA VERIFICATION: Expected columns: ' . implode(', ', $expected_columns));
        error_log('VD SCHEMA VERIFICATION: Actual columns: ' . implode(', ', $actual_columns));

        // Check for missing expected columns
        $missing_columns = array_diff($expected_columns, $actual_columns);
        if (!empty($missing_columns)) {
            error_log('VD SCHEMA VERIFICATION: ⚠️ Missing expected columns: ' . implode(', ', $missing_columns));
        }

        // Check for unexpected columns
        $extra_columns = array_diff($actual_columns, $expected_columns);
        if (!empty($extra_columns)) {
            error_log('VD SCHEMA VERIFICATION: ℹ️ Extra columns found: ' . implode(', ', $extra_columns));
        }

        // Verify critical columns exist with their correct database names
        $critical_columns = [
            'id' => 'Primary key',
            'account_login' => 'Login username/email (NOT login)',
            'provider' => 'Provider name (NOT service_name)',
            'status' => 'Account status',
            'capacity' => 'Maximum license capacity',
            'current_usage' => 'Current usage count'
        ];

        foreach ($critical_columns as $column => $description) {
            if (!isset($account[$column])) {
                error_log('VD SCHEMA VERIFICATION: ❌ CRITICAL MISSING: ' . $column . ' (' . $description . ')');
            } else {
                $value = is_string($account[$column]) ? $account[$column] : (string)$account[$column];
                error_log('VD SCHEMA VERIFICATION: ✅ Found: ' . $column . ' = \'' . $value . '\' (' . $description . ')');
            }
        }

        // Check for password fields (should have one or both)
        $has_login_password = isset($account['login_password']);
        $has_account_password = isset($account['account_password']);
        error_log('VD SCHEMA VERIFICATION: Password fields - login_password: ' .
                 ($has_login_password ? '✅ SET' : '❌ MISSING') .
                 ', account_password: ' . ($has_account_password ? '✅ SET' : '❌ MISSING'));

        error_log('VD SCHEMA VERIFICATION: === Schema Check Complete ===');
    }

    /**
     * Create license record in database
     *
     * @param string $license_key License key
     * @param int $lmfwc_license_id LMfWC license ID
     * @param int $order_id Order ID
     * @param WC_Order $order Order object
     * @param int $product_id Product ID
     * @param int $pool_id Pool ID
     * @param int $account_id Account ID
     * @param array $share_config Share config
     * @return int|false License ID or false
     */
    private function create_license_record($license_key, $lmfwc_license_id, $order_id, $order, $product_id, $pool_id, $account_id, $share_config) {
        global $wpdb;

        $table = $wpdb->prefix . 'vd_license_keys';

        // Calculate expiry date
        $expires_at = null;
        if ($share_config['validity_days'] > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . $share_config['validity_days'] . ' days'));
        }

        // Get customer ID (WordPress user ID)
        $customer_id = $order->get_customer_id();
        if (!$customer_id) {
            $customer_id = 0; // Guest customer
        }

        // Decrypt license key for plain text storage
        $license_key_plain = $this->decrypt_license_key($license_key);

        $data = array(
            'license_key' => $license_key, // Keep encrypted for compatibility
            'license_key_plain' => $license_key_plain, // Store plain text for fast API lookups
            'lmfwc_license_id' => $lmfwc_license_id,
            'product_id' => $product_id,
            'order_id' => $order_id,
            'customer_id' => $customer_id,
            'customer_email' => $order->get_billing_email(),
            'pool_id' => $pool_id,
            'account_id' => $account_id,
            'status' => 'active',
            'expires_at' => $expires_at,
            'max_devices' => $share_config['max_devices'],
            'current_devices' => 0,
            'max_requests_per_day' => $share_config['max_requests_per_day'],
            'max_requests_per_hour' => intval($share_config['max_requests_per_day'] / 24), // Rough calculation
            'assigned_at' => current_time('mysql'),
            'synced_at' => current_time('mysql'),
            'sync_hash' => md5($license_key . $lmfwc_license_id . $order_id),
            'created_at' => current_time('mysql')
        );

        error_log('VD License Assignment: Creating license record with data: ' . print_r($data, true));

        $result = $wpdb->insert(
            $table,
            $data,
            array('%s', '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            error_log('VD License Assignment: Failed to insert license: ' . $wpdb->last_error);
            return false;
        }

        $license_id = $wpdb->insert_id;
        error_log('VD License Assignment: License record created with ID: ' . $license_id);

        return $license_id;
    }

    /**
     * Update account usage count
     *
     * @param int $account_id Account ID
     */
    private function update_account_usage($account_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'vd_provider_accounts';

        error_log('VD License Assignment: === UPDATING ACCOUNT USAGE ===');
        error_log('VD License Assignment: Account ID: ' . $account_id);

        // Get current usage before update
        $current_data = $wpdb->get_row($wpdb->prepare(
            "SELECT account_login, current_usage, capacity FROM {$table} WHERE id = %d",
            $account_id
        ), ARRAY_A);

        if ($current_data) {
            error_log('VD License Assignment: Before update - Login: ' . $current_data['account_login'] .
                     ', Usage: ' . $current_data['current_usage'] . '/' . $current_data['capacity']);
        } else {
            error_log('VD License Assignment: ERROR - Account not found: ' . $account_id);
            return;
        }

        // Increment current usage
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET current_usage = current_usage + 1, updated_at = %s WHERE id = %d",
            current_time('mysql'),
            $account_id
        ));

        if ($wpdb->last_error) {
            error_log('VD License Assignment: Database error updating usage: ' . $wpdb->last_error);
            error_log('VD License Assignment: Query: ' . $wpdb->last_query);
        } else {
            error_log('VD License Assignment: ✓ Update query executed, rows affected: ' . $result);

            // Verify update worked
            $updated_data = $wpdb->get_row($wpdb->prepare(
                "SELECT current_usage FROM {$table} WHERE id = %d",
                $account_id
            ), ARRAY_A);

            if ($updated_data) {
                error_log('VD License Assignment: After update - Usage: ' . $updated_data['current_usage'] .
                         '/' . $current_data['capacity']);

                if ($updated_data['current_usage'] > $current_data['current_usage']) {
                    error_log('VD License Assignment: ✓ Usage successfully incremented');
                } else {
                    error_log('VD License Assignment: ⚠ Usage did not increment as expected');
                }
            }
        }
    }

    /**
     * Send license key email to customer
     *
     * Sends simple email with license key and portal link only.
     * NO account credentials are included in email.
     *
     * @since 1.0.0
     * @param WC_Order $order Order object
     * @param int $product_id Product ID
     * @param string $license_key License key
     * @param int $account_id Assigned account ID (not used in email)
     */
    private function send_credentials_email($order, $product_id, $license_key, $account_id) {
        try {
            // Get customer information
            $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            $customer_email = $order->get_billing_email();

            // Get product information
            $product = wc_get_product($product_id);
            $product_name = $product ? $product->get_name() : 'Product #' . $product_id;

            // Get share config for this product
            $share_config = $this->get_share_config($product_id);
            $max_devices = $share_config ? $share_config['max_devices'] : 1;
            $validity_days = $share_config ? $share_config['validity_days'] : 0;

            // Calculate expiry date
            $expiry_date = $validity_days > 0
                ? date('d/m/Y', strtotime('+' . $validity_days . ' days'))
                : 'Trọn đời';

            // Prepare email data (NO PASSWORD FIELDS!)
            $email_data = [
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'product_name' => $product_name,
                'license_key' => $license_key,
                'max_devices' => $max_devices,
                'validity_days' => $validity_days,
                'expiry_date' => $expiry_date,
                'portal_url' => home_url('/license-portal/'),
                'order_id' => $order->get_order_number(),
                'site_name' => get_bloginfo('name'),
                'site_url' => home_url()
            ];

            error_log('VD Email: Sending license key email (NO PASSWORDS) for license ' . $license_key);
            error_log('VD Email: License key passed to email handler: ' . substr($license_key, 0, 20) . '...');

            // Send email
            $result = $this->email_handler->send_credentials_email($email_data);

            if (is_wp_error($result)) {
                error_log('VD Email: Failed to send license key email for license ' . $license_key . ': ' . $result->get_error_message());
            } else {
                error_log('VD Email: License key email sent successfully for license ' . $license_key . ' to ' . $customer_email);
            }

        } catch (Exception $e) {
            error_log('VD Email: Exception sending license key email for license ' . $license_key . ': ' . $e->getMessage());
        }
    }

    /**
     * Decrypt license key using available LMFWC methods
     *
     * @param string $encrypted_key Encrypted license key from LMFWC
     * @return string Decrypted license key or original if decryption fails
     */
    private function decrypt_license_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }

        error_log('VD Order Handler: Attempting to decrypt license key: ' . substr($encrypted_key, 0, 20) . '...');

        // Method 1: Try LMFWC lmfwc_decrypt function
        if (function_exists('lmfwc_decrypt')) {
            try {
                $decrypted = lmfwc_decrypt($encrypted_key);
                if (!empty($decrypted) && $decrypted !== $encrypted_key) {
                    error_log('VD Order Handler: Successfully decrypted using lmfwc_decrypt(): ' . $decrypted);
                    return $decrypted;
                }
            } catch (Exception $e) {
                error_log('VD Order Handler: lmfwc_decrypt failed: ' . $e->getMessage());
            }
        }

        // Method 2: Try LMFWC Crypto class
        if (class_exists('LicenseManagerForWooCommerce\Crypto')) {
            try {
                $crypto = new \LicenseManagerForWooCommerce\Crypto();
                $decrypted = $crypto->decrypt($encrypted_key);
                if (!empty($decrypted) && $decrypted !== $encrypted_key) {
                    error_log('VD Order Handler: Successfully decrypted using Crypto class: ' . $decrypted);
                    return $decrypted;
                }
            } catch (Exception $e) {
                error_log('VD Order Handler: Crypto class failed: ' . $e->getMessage());
            }
        }

        // Method 3: Try VD_Encryption class (fallback)
        if (class_exists('VD_Encryption') && method_exists('VD_Encryption', 'decrypt')) {
            try {
                $decrypted = VD_Encryption::decrypt($encrypted_key);
                if (!empty($decrypted) && $decrypted !== $encrypted_key) {
                    error_log('VD Order Handler: Successfully decrypted using VD_Encryption: ' . $decrypted);
                    return $decrypted;
                }
            } catch (Exception $e) {
                error_log('VD Order Handler: VD_Encryption failed: ' . $e->getMessage());
            }
        }

        error_log('VD Order Handler: All decryption methods failed, storing encrypted key as fallback');
        return $encrypted_key; // Return as-is if decryption fails
    }
}

// Initialize
if (!function_exists('vd_init_order_handler')) {
    function vd_init_order_handler() {
        new VD_LM_Order_Handler();
        error_log('VD: Order Handler initialized');
    }
    add_action('init', 'vd_init_order_handler');
}
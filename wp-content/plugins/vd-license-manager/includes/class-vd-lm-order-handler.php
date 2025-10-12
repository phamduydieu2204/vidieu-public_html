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
     * Constructor
     */
    public function __construct() {
        // Hook into order status change
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'), 10, 2);

        error_log('VD Order Handler: Registered WooCommerce hooks');
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

        // Get pool directly from product_pools table
        $table = $wpdb->prefix . 'vd_product_pools';

        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d AND status = 'active' ORDER BY priority ASC LIMIT 1",
            $product_id
        ), ARRAY_A);

        if ($wpdb->last_error) {
            error_log('VD License Assignment: Error fetching pool: ' . $wpdb->last_error);
            return null;
        }

        if (!$pool) {
            error_log('VD License Assignment: No active pool found for product ' . $product_id);
            return null;
        }

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

        // Find account that is:
        // 1. In this pool (via pool_accounts mapping)
        // 2. Status = 'active'
        // 3. Has available capacity (current_usage < capacity)
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*
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
            error_log('VD License Assignment: Error fetching available account: ' . $wpdb->last_error);
            return null;
        }

        return $account;
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

        $data = array(
            'license_key' => $license_key,
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

        // Increment current usage
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET current_usage = current_usage + 1, updated_at = %s WHERE id = %d",
            current_time('mysql'),
            $account_id
        ));

        if ($wpdb->last_error) {
            error_log('VD License Assignment: Error updating account usage: ' . $wpdb->last_error);
        } else {
            error_log('VD License Assignment: Account ' . $account_id . ' usage incremented');
        }
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
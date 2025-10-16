<?php
/**
 * Pools Admin Page
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class VD_LM_Pools_Page {

    public function __construct() {
        $this->handle_actions();
    }

    private function handle_actions() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'vd-license-manager'));
        }

        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) :
                 (isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '');

        switch ($action) {
            case 'create_pool':
                $this->handle_create_pool();
                break;
            case 'update_pool':
                $this->handle_update_pool();
                break;
            case 'delete_pool':
                $this->handle_delete_pool();
                break;
            case 'assign_products':
                $this->handle_assign_products();
                break;
        }
    }

    private function handle_create_pool() {
        if (!isset($_POST['vd_pools_nonce']) ||
            !wp_verify_nonce($_POST['vd_pools_nonce'], 'vd_pools_action')) {
            wp_die(__('Security check failed.', 'vd-license-manager'));
        }

        $name = isset($_POST['pool_name']) ? sanitize_text_field($_POST['pool_name']) : '';
        $description = isset($_POST['pool_description']) ? sanitize_textarea_field($_POST['pool_description']) : '';
        $status = isset($_POST['pool_status']) ? sanitize_text_field($_POST['pool_status']) : 'active';

        if (empty($name)) {
            $this->add_notice('error', 'Pool name is required.');
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vd_pools';

        $result = $wpdb->insert(
            $table,
            array(
                'name' => $name,
                'description' => $description,
                'status' => $status
            ),
            array('%s', '%s', '%s')
        );

        if ($result) {
            $pool_id = $wpdb->insert_id;

            // Handle product assignments
            $this->handle_product_assignments($pool_id);

            error_log('VD Pools: Created pool ID ' . $pool_id . ': ' . $name);
            $this->add_notice('success', 'Pool created successfully with product assignments.');
        } else {
            error_log('VD Pools: Failed to create pool: ' . $wpdb->last_error);
            $this->add_notice('error', 'Failed to create pool: ' . $wpdb->last_error);
        }
    }

    private function handle_update_pool() {
        if (!isset($_POST['vd_pools_nonce']) ||
            !wp_verify_nonce($_POST['vd_pools_nonce'], 'vd_pools_action')) {
            wp_die(__('Security check failed.', 'vd-license-manager'));
        }

        $pool_id = isset($_POST['pool_id']) ? absint($_POST['pool_id']) : 0;
        $name = isset($_POST['pool_name']) ? sanitize_text_field($_POST['pool_name']) : '';
        $description = isset($_POST['pool_description']) ? sanitize_textarea_field($_POST['pool_description']) : '';
        $status = isset($_POST['pool_status']) ? sanitize_text_field($_POST['pool_status']) : 'active';

        if (!$pool_id || empty($name)) {
            $this->add_notice('error', 'Invalid pool data.');
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vd_pools';

        $result = $wpdb->update(
            $table,
            array(
                'name' => $name,
                'description' => $description,
                'status' => $status
            ),
            array('id' => $pool_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($result !== false) {
            // Handle product assignments update
            $this->handle_product_assignments($pool_id);

            error_log('VD Pools: Updated pool ID ' . $pool_id);
            $this->add_notice('success', 'Pool updated successfully with product assignments.');
        } else {
            error_log('VD Pools: Failed to update pool: ' . $wpdb->last_error);
            $this->add_notice('error', 'Failed to update pool.');
        }
    }

    private function handle_delete_pool() {
        if (!isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'delete_pool_' . $_GET['id'])) {
            wp_die(__('Security check failed.', 'vd-license-manager'));
        }

        $pool_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        if (!$pool_id) {
            $this->add_notice('error', 'Invalid pool ID.');
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vd_pools';

        // Check if pool has accounts assigned
        $account_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_pool_accounts WHERE pool_id = %d",
            $pool_id
        ));

        if ($account_count > 0) {
            $this->add_notice('error', 'Cannot delete pool with assigned accounts. Remove accounts first.');
            return;
        }

        // Check if pool has products assigned
        $product_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_product_pools WHERE pool_id = %d",
            $pool_id
        ));

        if ($product_count > 0) {
            $this->add_notice('error', 'Cannot delete pool with assigned products. Remove products first.');
            return;
        }

        $result = $wpdb->delete($table, array('id' => $pool_id), array('%d'));

        if ($result) {
            error_log('VD Pools: Deleted pool ID ' . $pool_id);
            $this->add_notice('success', 'Pool deleted successfully.');
        } else {
            $this->add_notice('error', 'Failed to delete pool.');
        }
    }

    private function add_notice($type, $message) {
        add_settings_error('vd_pools', 'vd_pools_message', $message, $type);
    }

    /**
     * Handle product assignments for a pool
     *
     * @since 1.0.1
     * @param int $pool_id Pool ID
     */
    private function handle_product_assignments($pool_id) {
        global $wpdb;

        // DEBUG: Add comprehensive logging
        error_log("=== VD POOLS: PRODUCT ASSIGNMENT DEBUG START ===");
        error_log("Pool ID: {$pool_id}");
        error_log("POST data: " . print_r($_POST, true));

        if (!isset($_POST['assigned_products']) || !is_array($_POST['assigned_products'])) {
            error_log("WARNING: No assigned_products in POST data");
            error_log("=== VD POOLS: PRODUCT ASSIGNMENT DEBUG END (NO DATA) ===");
            return;
        }

        $product_pools_table = $wpdb->prefix . 'vd_product_pools';
        error_log("Table name: {$product_pools_table}");

        // DEBUG: Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $product_pools_table
        ));

        if (!$table_exists) {
            error_log("CRITICAL ERROR: Table {$product_pools_table} does not exist!");
            error_log("=== VD POOLS: PRODUCT ASSIGNMENT DEBUG END (TABLE MISSING) ===");
            return;
        } else {
            error_log("SUCCESS: Table {$product_pools_table} exists");
        }

        // Start transaction
        $wpdb->query('START TRANSACTION');
        error_log("Transaction started");

        try {
            // Remove existing product assignments for this pool
            error_log("Deleting existing assignments for pool {$pool_id}");
            $delete_result = $wpdb->delete(
                $product_pools_table,
                array('pool_id' => $pool_id),
                array('%d')
            );

            if ($delete_result === false) {
                error_log("DELETE ERROR: " . $wpdb->last_error);
            } else {
                error_log("DELETE SUCCESS: {$delete_result} rows affected");
            }

            // Add new product assignments
            $assigned_products = $_POST['assigned_products'];
            $success = true;
            error_log("Processing " . count($assigned_products) . " product assignments");

            foreach ($assigned_products as $index => $product_id) {
                $product_id = absint($product_id);
                error_log("Processing product: {$product_id}");

                if ($product_id > 0) {
                    $priority = isset($_POST['product_priorities'][$index]) ?
                               absint($_POST['product_priorities'][$index]) : ($index + 1);

                    error_log("Inserting: product_id={$product_id}, pool_id={$pool_id}, priority={$priority}");

                    // Get pool name for reference
                    $pool_name = $wpdb->get_var($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}vd_pools WHERE id = %d",
                        $pool_id
                    ));

                    $result = $wpdb->insert(
                        $product_pools_table,
                        array(
                            'pool_name' => $pool_name ?: 'Unknown Pool',
                            'product_id' => $product_id,
                            'pool_id' => $pool_id,
                            'priority' => $priority,
                            'capacity' => 10, // Default capacity
                            'status' => 'active',
                            'assignment_strategy' => 'random',
                            'rotation_enabled' => 0,
                            'rotation_interval' => null,
                            'description' => null
                        ),
                        array('%s', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s')
                    );

                    if ($result === false) {
                        error_log("INSERT ERROR for product {$product_id}: " . $wpdb->last_error);
                        $success = false;
                        break;
                    } else {
                        error_log("INSERT SUCCESS for product {$product_id}, insert_id: " . $wpdb->insert_id);
                    }
                } else {
                    error_log("Skipping invalid product ID: {$product_id}");
                }
            }

            if ($success) {
                $wpdb->query('COMMIT');
                error_log("TRANSACTION SUCCESS: Committed changes for pool {$pool_id}");
                error_log("VD Pools: Product assignments updated for pool ID {$pool_id}");
            } else {
                $wpdb->query('ROLLBACK');
                error_log("TRANSACTION FAILED: Rolled back changes for pool {$pool_id}");
                error_log("VD Pools: Failed to update product assignments for pool ID {$pool_id}");
            }

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log("EXCEPTION CAUGHT: " . $e->getMessage());
            error_log("VD Pools: Exception in product assignments: " . $e->getMessage());
        }

        error_log("=== VD POOLS: PRODUCT ASSIGNMENT DEBUG END ===");
    }

    /**
     * Get WooCommerce products for dropdown
     *
     * @since 1.0.1
     * @return array Array of products
     */
    private function get_woocommerce_products() {
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $product_options = array();
        foreach ($products as $product) {
            $product_options[] = array(
                'id' => $product->ID,
                'title' => $product->post_title
            );
        }

        return $product_options;
    }

    /**
     * Get assigned products for a pool
     *
     * @since 1.0.1
     * @param int $pool_id Pool ID
     * @return array Array of assigned products with priorities
     */
    private function get_assigned_products($pool_id) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT pp.product_id, pp.priority, p.post_title
             FROM {$wpdb->prefix}vd_product_pools pp
             LEFT JOIN {$wpdb->posts} p ON pp.product_id = p.ID
             WHERE pp.pool_id = %d
             ORDER BY pp.priority ASC",
            $pool_id
        ), ARRAY_A);

        return $results;
    }

    public function render() {
        global $wpdb;

        // Get all pools
        $table = $wpdb->prefix . 'vd_pools';
        $pools = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A);

        // Get editing pool if any
        $editing_pool = null;
        $assigned_products = array();
        if (isset($_GET['edit']) && absint($_GET['edit'])) {
            $editing_pool = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                absint($_GET['edit'])
            ), ARRAY_A);

            if ($editing_pool) {
                $assigned_products = $this->get_assigned_products($editing_pool['id']);
            }
        }

        // Get all WooCommerce products for the dropdown
        $woocommerce_products = $this->get_woocommerce_products();

        require_once VD_PLUGIN_DIR . 'admin/partials/pools-list.php';
    }
}
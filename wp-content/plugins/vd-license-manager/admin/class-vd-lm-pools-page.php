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
            error_log('VD Pools: Created pool ID ' . $pool_id . ': ' . $name);
            $this->add_notice('success', 'Pool created successfully.');
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
            error_log('VD Pools: Updated pool ID ' . $pool_id);
            $this->add_notice('success', 'Pool updated successfully.');
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

    public function render() {
        global $wpdb;

        // Get all pools
        $table = $wpdb->prefix . 'vd_pools';
        $pools = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A);

        // Get editing pool if any
        $editing_pool = null;
        if (isset($_GET['edit']) && absint($_GET['edit'])) {
            $editing_pool = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                absint($_GET['edit'])
            ), ARRAY_A);
        }

        require_once VD_PLUGIN_DIR . 'admin/partials/pools-list.php';
    }
}
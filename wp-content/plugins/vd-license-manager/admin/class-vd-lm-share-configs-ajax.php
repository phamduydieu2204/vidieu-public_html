<?php
/**
 * Share Configs AJAX Handler
 *
 * Handles AJAX requests for Share Configurations page.
 * Provides quick save functionality without page reload.
 *
 * @package    VD_License_Manager
 * @subpackage Admin
 * @since      1.0.0
 */

defined('ABSPATH') || exit;

class VD_LM_Share_Configs_Ajax {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('wp_ajax_vd_save_share_config', array($this, 'save_config'));
        error_log('VD AJAX Handler: Share Configs AJAX handler registered');
    }

    /**
     * Save share config via AJAX
     *
     * @since 1.0.0
     */
    public function save_config() {
        error_log('=== VD AJAX: save_config CALLED ===');
        error_log('VD AJAX: POST data: ' . print_r($_POST, true));

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vd_lm_nonce')) {
            error_log('VD AJAX: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        error_log('VD AJAX: Nonce verified');

        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('VD AJAX: Permission denied');
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
        }

        error_log('VD AJAX: Permissions OK');

        // Get product_id
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

        if (!$product_id) {
            error_log('VD AJAX: Invalid product ID');
            wp_send_json_error(array('message' => 'Invalid product ID.'));
        }

        error_log('VD AJAX: Product ID: ' . $product_id);

        // Verify WooCommerce is active
        if (!function_exists('wc_get_product')) {
            wp_send_json_error(array('message' => 'WooCommerce is not active.'));
        }

        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('VD AJAX: Product not found');
            wp_send_json_error(array('message' => 'Product not found.'));
        }

        error_log('VD AJAX: Product found: ' . $product->get_name());

        // Get config data
        $config_data = array(
            'product_id'           => $product_id,
            'max_devices'          => isset($_POST['max_devices']) ? absint($_POST['max_devices']) : 2,
            'validity_days'        => isset($_POST['validity_days']) ? absint($_POST['validity_days']) : 30,
            'max_requests_per_day' => isset($_POST['max_requests_per_day']) ? absint($_POST['max_requests_per_day']) : 100,
            'allow_vps'            => isset($_POST['allow_vps']) ? absint($_POST['allow_vps']) : 0,
        );

        error_log('VD AJAX: Config data: ' . print_r($config_data, true));

        // Validate
        if ($config_data['max_devices'] < 1 || $config_data['max_devices'] > 10) {
            wp_send_json_error(array('message' => 'Max devices must be between 1 and 10.'));
        }

        if ($config_data['validity_days'] < 1 || $config_data['validity_days'] > 3650) {
            wp_send_json_error(array('message' => 'Validity days must be between 1 and 3650.'));
        }

        if ($config_data['max_requests_per_day'] < 10 || $config_data['max_requests_per_day'] > 10000) {
            wp_send_json_error(array('message' => 'Max requests per day must be between 10 and 10000.'));
        }

        error_log('VD AJAX: Validation passed');

        // Save to database
        global $wpdb;
        $table = $wpdb->prefix . 'vd_product_share_configs';

        error_log('VD AJAX: Table name: ' . $table);

        // Check if exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE product_id = %d",
            $product_id
        ));

        if ($existing) {
            error_log('VD AJAX: Config exists, updating ID: ' . $existing->id);

            // Update
            $result = $wpdb->update(
                $table,
                $config_data,
                array('product_id' => $product_id),
                array('%d', '%d', '%d', '%d', '%d'),
                array('%d')
            );

            if ($result !== false) {
                error_log("VD AJAX: Updated share config for product {$product_id}");
                wp_send_json_success(array(
                    'message' => 'Configuration updated successfully.',
                    'config' => $config_data,
                    'action' => 'updated'
                ));
            } else {
                error_log("VD AJAX: Failed to update config - " . $wpdb->last_error);
                wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
            }
        } else {
            error_log('VD AJAX: Config does not exist, inserting new');

            // Insert
            $result = $wpdb->insert(
                $table,
                $config_data,
                array('%d', '%d', '%d', '%d', '%d')
            );

            if ($result) {
                error_log("VD AJAX: Created share config for product {$product_id}, ID: " . $wpdb->insert_id);
                wp_send_json_success(array(
                    'message' => 'Configuration created successfully.',
                    'config' => $config_data,
                    'config_id' => $wpdb->insert_id,
                    'action' => 'created'
                ));
            } else {
                error_log("VD AJAX: Failed to insert config - " . $wpdb->last_error);
                wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
            }
        }
    }
}

// CRITICAL: Initialize the handler properly
if (!function_exists('vd_init_share_configs_ajax')) {
    function vd_init_share_configs_ajax() {
        new VD_LM_Share_Configs_Ajax();
        error_log('VD AJAX Handler: Initialized Share Configs AJAX');
    }
    add_action('init', 'vd_init_share_configs_ajax');
}
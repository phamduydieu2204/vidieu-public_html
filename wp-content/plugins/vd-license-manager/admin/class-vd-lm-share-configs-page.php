<?php
/**
 * Share Configs Admin Page
 *
 * Handles the Share Configurations page where admin can configure
 * license sharing settings for each WooCommerce product.
 *
 * @package    VD_License_Manager
 * @subpackage Admin
 * @since      1.0.0
 */

defined('ABSPATH') || exit;

class VD_LM_Share_Configs_Page {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Handle actions on construct
        $this->handle_actions();
    }

    /**
     * Handle page actions
     *
     * @since 1.0.0
     */
    private function handle_actions() {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'vd-license-manager'));
        }

        // Get action from POST or GET
        $action = '';
        if (isset($_POST['action'])) {
            $action = sanitize_text_field($_POST['action']);
        } elseif (isset($_GET['action'])) {
            $action = sanitize_text_field($_GET['action']);
        }

        // Log action
        error_log('VD Share Configs: Action detected: ' . $action);

        // Route actions
        switch ($action) {
            case 'save_config':
                $this->handle_save_config();
                break;
            case 'delete_config':
                $this->handle_delete_config();
                break;
        }
    }

    /**
     * Handle save config
     *
     * @since 1.0.0
     */
    private function handle_save_config() {
        // Verify nonce
        if (!isset($_POST['vd_lm_nonce']) ||
            !wp_verify_nonce($_POST['vd_lm_nonce'], 'vd_lm_share_config_action')) {
            wp_die(__('Security check failed.', 'vd-license-manager'));
        }

        // Get product_id
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

        if (!$product_id) {
            $this->add_notice('error', 'Invalid product ID.');
            return;
        }

        // Verify product exists in WooCommerce
        if (!function_exists('wc_get_product')) {
            $this->add_notice('error', 'WooCommerce is not active.');
            return;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            $this->add_notice('error', 'Product not found.');
            return;
        }

        // Get config data
        $config_data = array(
            'product_id'           => $product_id,
            'max_devices'          => isset($_POST['max_devices']) ? absint($_POST['max_devices']) : 2,
            'validity_days'        => isset($_POST['validity_days']) ? absint($_POST['validity_days']) : 30,
            'max_requests_per_day' => isset($_POST['max_requests_per_day']) ? absint($_POST['max_requests_per_day']) : 100,
            'allow_vps'            => isset($_POST['allow_vps']) ? 1 : 0,
        );

        // Validate
        if ($config_data['max_devices'] < 1) {
            $this->add_notice('error', 'Max devices must be at least 1.');
            return;
        }

        if ($config_data['validity_days'] < 1) {
            $this->add_notice('error', 'Validity days must be at least 1.');
            return;
        }

        if ($config_data['max_requests_per_day'] < 10) {
            $this->add_notice('error', 'Max requests per day must be at least 10.');
            return;
        }

        // Save to database
        global $wpdb;
        $table = $wpdb->prefix . 'vd_product_share_configs';

        // Check if config exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE product_id = %d",
            $product_id
        ));

        if ($existing) {
            // Update
            $result = $wpdb->update(
                $table,
                $config_data,
                array('product_id' => $product_id),
                array('%d', '%d', '%d', '%d', '%d'),
                array('%d')
            );

            if ($result !== false) {
                $this->add_notice('success', 'Share config updated successfully.');
                error_log("VD Share Config: Updated config for product {$product_id}");
            } else {
                $this->add_notice('error', 'Database error: ' . $wpdb->last_error);
                error_log("VD Share Config: Update failed - " . $wpdb->last_error);
            }
        } else {
            // Insert
            $result = $wpdb->insert(
                $table,
                $config_data,
                array('%d', '%d', '%d', '%d', '%d')
            );

            if ($result) {
                $this->add_notice('success', 'Share config created successfully.');
                error_log("VD Share Config: Created config for product {$product_id}");
            } else {
                $this->add_notice('error', 'Database error: ' . $wpdb->last_error);
                error_log("VD Share Config: Insert failed - " . $wpdb->last_error);
            }
        }
    }

    /**
     * Handle delete config
     *
     * @since 1.0.0
     */
    private function handle_delete_config() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'delete_config_' . $_GET['id'])) {
            wp_die(__('Security check failed.', 'vd-license-manager'));
        }

        $config_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        if (!$config_id) {
            $this->add_notice('error', 'Invalid config ID.');
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vd_product_share_configs';

        $result = $wpdb->delete($table, array('id' => $config_id), array('%d'));

        if ($result) {
            $this->add_notice('success', 'Share config deleted successfully.');
            error_log("VD Share Config: Deleted config ID {$config_id}");
        } else {
            $this->add_notice('error', 'Failed to delete config.');
            error_log("VD Share Config: Delete failed for ID {$config_id}");
        }
    }

    /**
     * Add admin notice
     *
     * @since 1.0.0
     * @param string $type    Notice type (success, error, warning, info)
     * @param string $message Notice message
     */
    private function add_notice($type, $message) {
        add_settings_error(
            'vd_share_configs',
            'vd_share_configs_message',
            $message,
            $type
        );
    }

    /**
     * Render page
     *
     * @since 1.0.0
     */
    public function render() {
        // Check if WooCommerce is active
        if (!function_exists('wc_get_products')) {
            echo '<div class="wrap vd-wrap">';
            echo '<h1>' . esc_html__('Product Share Configurations', 'vd-license-manager') . '</h1>';
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('WooCommerce is required but not active. Please install and activate WooCommerce.', 'vd-license-manager');
            echo '</p></div>';
            echo '</div>';
            return;
        }

        // Get all WooCommerce products
        $products = wc_get_products(array(
            'limit'  => -1,
            'status' => 'publish',
            'orderby' => 'name',
            'order' => 'ASC',
        ));

        // Get existing configs
        global $wpdb;
        $table = $wpdb->prefix . 'vd_product_share_configs';
        $configs_raw = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);

        // Index configs by product_id
        $configs = array();
        foreach ($configs_raw as $config) {
            $configs[$config['product_id']] = $config;
        }

        // Include template
        require_once VD_PLUGIN_DIR . 'admin/partials/share-configs-list.php';
    }
}
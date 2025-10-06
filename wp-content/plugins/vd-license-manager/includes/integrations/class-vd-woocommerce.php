<?php
/**
 * WooCommerce integration for VD License Manager
 *
 * Handles automatic account assignment when orders are completed
 *
 * @package VD_License_Manager
 * @subpackage Integrations
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD WooCommerce integration class
 *
 * Manages WooCommerce hooks and license assignment
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_WooCommerce {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Order completion hook
        add_action('woocommerce_order_status_completed', array($this, 'on_order_completed'), 10, 1);

        // Product meta box hooks
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_meta_box'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta'));

        // Admin notices
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }

    /**
     * Handle order completion
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     */
    public function on_order_completed($order_id) {
        if (!$order_id) {
            return;
        }

        try {
            // Get order object
            $order = wc_get_order($order_id);
            if (!$order) {
                VD_Logger::log("WooCommerce: Order {$order_id} not found", 'error');
                return;
            }

            // Get licenses from order
            $licenses = $this->get_licenses_from_order($order_id);
            if (empty($licenses)) {
                VD_Logger::log("WooCommerce: No licenses found for order {$order_id}", 'info');
                return;
            }

            $assignment_results = array();
            $total_assigned = 0;
            $total_failed = 0;

            // Process each license
            foreach ($licenses as $license) {
                $license_id = $license['id'];
                $license_key = $license['license_key'];
                $product_id = $this->get_product_id_from_order_item($order, $license);

                if (!$product_id) {
                    VD_Logger::log("WooCommerce: Could not determine product ID for license {$license_key}", 'warning');
                    $total_failed++;
                    continue;
                }

                // Check if product has auto-assign enabled
                $auto_assign = get_post_meta($product_id, '_vd_auto_assign', true);
                if (!$auto_assign) {
                    VD_Logger::log("WooCommerce: Auto-assign disabled for product {$product_id}, license {$license_key}", 'info');
                    continue;
                }

                // Check if product has a pool configured
                $pool = $this->get_product_pool($product_id);
                if (!$pool) {
                    VD_Logger::log("WooCommerce: No pool configured for product {$product_id}, license {$license_key}", 'warning');
                    $total_failed++;
                    continue;
                }

                // Assign account to license
                VD_Logger::log("WooCommerce: Attempting to assign account to license {$license_key} (Product: {$product_id})", 'info');

                $assignment_result = VD_Assignment::assign_account_to_license($license_id, $product_id);

                if ($assignment_result['success']) {
                    $total_assigned++;
                    VD_Logger::log("WooCommerce: Successfully assigned account to license {$license_key}", 'info');

                    $assignment_results[] = array(
                        'license_key' => $license_key,
                        'product_id' => $product_id,
                        'success' => true,
                        'account_id' => $assignment_result['account_id'] ?? null
                    );
                } else {
                    $total_failed++;
                    VD_Logger::log("WooCommerce: Failed to assign account to license {$license_key}: " . $assignment_result['message'], 'error');

                    $assignment_results[] = array(
                        'license_key' => $license_key,
                        'product_id' => $product_id,
                        'success' => false,
                        'error' => $assignment_result['message']
                    );
                }
            }

            // Log summary
            VD_Logger::log("WooCommerce: Order {$order_id} processing complete. Assigned: {$total_assigned}, Failed: {$total_failed}", 'info');

            // Store results for admin notice
            if ($total_assigned > 0 || $total_failed > 0) {
                update_option('vd_wc_assignment_notice', array(
                    'order_id' => $order_id,
                    'total_assigned' => $total_assigned,
                    'total_failed' => $total_failed,
                    'results' => $assignment_results,
                    'timestamp' => current_time('mysql')
                ));
            }

            // Send email notification if enabled and assignments were made
            if ($total_assigned > 0 && get_option('vd_notify_assignments', false)) {
                $this->send_assignment_notification($order, $assignment_results);
            }

        } catch (Exception $e) {
            VD_Logger::log("WooCommerce: Exception in order completion for order {$order_id}: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Get licenses from order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array License records
     */
    private function get_licenses_from_order($order_id) {
        global $wpdb;

        // Query LMFWC licenses table
        $licenses = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lmfwc_licenses WHERE order_id = %d",
            $order_id
        ), ARRAY_A);

        return $licenses ?: array();
    }

    /**
     * Get product ID from order item for a license
     *
     * @since 1.0.0
     * @param WC_Order $order Order object
     * @param array $license License data
     * @return int|null Product ID
     */
    private function get_product_id_from_order_item($order, $license) {
        // Try to get product ID from license record first
        if (!empty($license['product_id'])) {
            return (int) $license['product_id'];
        }

        // Fallback: get from order items
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id) {
                return $product_id;
            }
        }

        return null;
    }

    /**
     * Get product pool configuration
     *
     * @since 1.0.0
     * @param int $product_id Product ID
     * @return array|null Pool data
     */
    private function get_product_pool($product_id) {
        global $wpdb;

        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_product_pools WHERE product_id = %d AND is_active = 1",
            $product_id
        ), ARRAY_A);

        return $pool;
    }

    /**
     * Add product meta box for auto-assign setting
     *
     * @since 1.0.0
     */
    public function add_product_meta_box() {
        global $post;

        if (!$post || $post->post_type !== 'product') {
            return;
        }

        $auto_assign = get_post_meta($post->ID, '_vd_auto_assign', true);
        $pool_exists = $this->get_product_pool($post->ID);

        ?>
        <div class="options_group">
            <h3><?php echo esc_html__('VD License Manager', 'vd-license-manager'); ?></h3>

            <p class="form-field">
                <label for="_vd_auto_assign">
                    <input type="checkbox" name="_vd_auto_assign" id="_vd_auto_assign" value="1" <?php checked($auto_assign, 1); ?> />
                    <?php echo esc_html__('Auto-assign account on purchase', 'vd-license-manager'); ?>
                </label>
            </p>

            <p class="form-field">
                <?php if ($pool_exists): ?>
                    <span style="color: #46b450;">✅ <?php echo esc_html__('Pool configured', 'vd-license-manager'); ?></span>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vd-product-pools&action=edit&id=' . $pool_exists['pool_id'])); ?>" class="button button-small">
                        <?php echo esc_html__('Edit Pool', 'vd-license-manager'); ?>
                    </a>
                <?php else: ?>
                    <span style="color: #dc3232;">⚠️ <?php echo esc_html__('No pool configured', 'vd-license-manager'); ?></span>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vd-product-pools&action=add&product_id=' . $post->ID)); ?>" class="button button-small">
                        <?php echo esc_html__('Create Pool', 'vd-license-manager'); ?>
                    </a>
                <?php endif; ?>
            </p>

            <p class="description">
                <?php echo esc_html__('Enable automatic account assignment when orders are completed. A product pool must be configured for this to work.', 'vd-license-manager'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Save product meta
     *
     * @since 1.0.0
     * @param int $product_id Product ID
     */
    public function save_product_meta($product_id) {
        if (!current_user_can('edit_post', $product_id)) {
            return;
        }

        $auto_assign = isset($_POST['_vd_auto_assign']) ? 1 : 0;
        update_post_meta($product_id, '_vd_auto_assign', $auto_assign);

        VD_Logger::log("WooCommerce: Auto-assign setting for product {$product_id} updated to: " . ($auto_assign ? 'enabled' : 'disabled'), 'info');
    }

    /**
     * Send assignment notification email
     *
     * @since 1.0.0
     * @param WC_Order $order Order object
     * @param array $results Assignment results
     */
    private function send_assignment_notification($order, $results) {
        $admin_email = get_option('vd_admin_email', get_option('admin_email'));

        $subject = sprintf(__('[%s] Account Assignment Complete - Order #%s', 'vd-license-manager'),
                          get_bloginfo('name'),
                          $order->get_order_number());

        $successful = array_filter($results, function($r) { return $r['success']; });
        $failed = array_filter($results, function($r) { return !$r['success']; });

        $message = sprintf(__("Order #%s has been processed for automatic account assignment.\n\n", 'vd-license-manager'), $order->get_order_number());
        $message .= sprintf(__("Successful assignments: %d\n", 'vd-license-manager'), count($successful));
        $message .= sprintf(__("Failed assignments: %d\n\n", 'vd-license-manager'), count($failed));

        if (!empty($failed)) {
            $message .= __("Failed licenses:\n", 'vd-license-manager');
            foreach ($failed as $failure) {
                $message .= sprintf("- %s: %s\n", $failure['license_key'], $failure['error']);
            }
        }

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Show admin notices
     *
     * @since 1.0.0
     */
    public function show_admin_notices() {
        $notice = get_option('vd_wc_assignment_notice');
        if (!$notice) {
            return;
        }

        // Show notice for 24 hours
        if (strtotime($notice['timestamp']) < strtotime('-1 day')) {
            delete_option('vd_wc_assignment_notice');
            return;
        }

        $class = ($notice['total_failed'] > 0) ? 'notice-warning' : 'notice-success';
        ?>
        <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
            <p>
                <strong><?php echo esc_html__('VD License Manager:', 'vd-license-manager'); ?></strong>
                <?php echo sprintf(
                    esc_html__('Order #%s processed. %d accounts assigned, %d failed.', 'vd-license-manager'),
                    $notice['order_id'],
                    $notice['total_assigned'],
                    $notice['total_failed']
                ); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=vd-logs&tab=assignment')); ?>">
                    <?php echo esc_html__('View logs', 'vd-license-manager'); ?>
                </a>
            </p>
        </div>
        <?php

        // Clear notice after showing
        delete_option('vd_wc_assignment_notice');
    }
}
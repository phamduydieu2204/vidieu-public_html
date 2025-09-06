<?php
/**
 * Plugin Name: Force VCB-MH Display
 * Description: Đảm bảo VCB-MH hiển thị đúng và disable VietQR cũ
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove all VietQR old hooks early
 */
add_action('init', function() {
    // Remove VietQR integration hook completely
    remove_action('wp_footer', 'vidieu_reorganize_bacs_with_qr', 10);
    remove_action('wp_footer', 'vidieu_reorganize_bacs_with_qr', 999);
    
    // Remove any other potential hooks
    remove_all_actions('wp_footer', 10);
    
    // Also remove from other possible hooks
    remove_action('woocommerce_thankyou_bacs', 'vidieu_reorganize_bacs_with_qr');
    remove_action('woocommerce_before_thankyou', 'vidieu_reorganize_bacs_with_qr');
    remove_action('woocommerce_after_thankyou', 'vidieu_reorganize_bacs_with_qr');
}, 1);

/**
 * Remove only VietQR old hooks on order-received page
 */
add_action('wp', function() {
    if (is_order_received_page()) {
        // Only remove specific VietQR hooks, not ALL wp_footer
        remove_action('wp_footer', 'vidieu_reorganize_bacs_with_qr', 10);
    }
}, 999);

/**
 * Force hide old VietQR elements via CSS
 */
add_action('wp_head', function() {
    if (is_order_received_page()) {
        ?>
        <style>
        /* Force hide ALL old VietQR elements */
        .vietqr-bank-transfer-container,
        .vietqr-bank-table,
        .vietqr-qr-cell,
        .vietqr-email-notice,
        .vietqr-contact-buttons,
        .vd-vietqr,
        [class*="vietqr"] {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            overflow: hidden !important;
        }
        
        /* Ensure VCB-MH displays */
        #vcb-gateway,
        #payment-info,
        .vcb-gateway-result {
            display: block !important;
            visibility: visible !important;
        }
        
        /* Hide any img with vietqr.io that's NOT from VCB-MH */
        img[src*="vietqr.io"][src*="0821000013390"] {
            display: none !important;
        }
        
        /* Only show VCB-MH QR images */
        #vcb-gateway img[src*="vietqr.io"],
        #payment-info img[src*="vietqr.io"] {
            display: block !important;
        }
        </style>
        <?php
    }
});

/**
 * Ensure VCB-MH thankyou_page runs
 */
add_action('woocommerce_thankyou', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Debug info
    if (current_user_can('manage_options')) {
        echo '<!-- Debug: Order payment method: ' . $order->get_payment_method() . ' -->';
    }
    
    // If it's VCB-MH, ensure the gateway's thankyou_page method runs
    if ($order->get_payment_method() === 'vcb-gateway-mh') {
        // Get the gateway instance
        $payment_gateways = WC()->payment_gateways()->payment_gateways();
        if (isset($payment_gateways['vcb-gateway-mh'])) {
            $vcb_gateway = $payment_gateways['vcb-gateway-mh'];
            
            // Check if thankyou_page method already ran
            if (!did_action('vcb_mh_thankyou_displayed')) {
                // Force run the thankyou_page method
                if (method_exists($vcb_gateway, 'thankyou_page')) {
                    $vcb_gateway->thankyou_page($order_id);
                    do_action('vcb_mh_thankyou_displayed');
                }
            }
        }
    }
}, 3); // Run before VCB-MH's priority 4

/**
 * JavaScript to remove old VietQR elements
 */
add_action('wp_footer', function() {
    if (is_order_received_page()) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Remove all elements with vietqr classes
            const vietqrElements = document.querySelectorAll('[class*="vietqr"]');
            vietqrElements.forEach(el => el.remove());
            
            // Remove any images with old account number
            const oldQRImages = document.querySelectorAll('img[src*="0821000013390"]');
            oldQRImages.forEach(img => img.remove());
            
            // Debug
            console.log('VCB-MH Force Display: Removed', vietqrElements.length, 'old VietQR elements');
        });
        </script>
        <?php
    }
}, 999);

/**
 * Log which payment gateway is being used
 */
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    error_log('Order #' . $order_id . ' created with payment method: ' . $order->get_payment_method());
}, 10, 3);
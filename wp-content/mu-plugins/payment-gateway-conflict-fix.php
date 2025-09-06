<?php
/**
 * Plugin Name: Payment Gateway Conflict Fix
 * Description: Giải quyết conflict giữa VietQR cũ và VCB-MH
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable VietQR cũ khi order dùng VCB-MH payment method
 */
add_action('init', function() {
    // Hook vào trước khi VietQR integration chạy
    add_action('wp_footer', function() {
        // Chỉ chạy trên trang order-received
        if (!is_order_received_page()) {
            return;
        }
        
        // Get order ID
        $order_id = get_query_var('order-received');
        if (!$order_id) {
            return;
        }
        
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Nếu dùng vcb-gateway-mh, disable VietQR cũ
        if ($order->get_payment_method() === 'vcb-gateway-mh') {
            // Remove hook của VietQR cũ
            remove_action('wp_footer', 'vidieu_reorganize_bacs_with_qr', 10);
            
            // Add CSS để ẩn các element của VietQR cũ (phòng trường hợp)
            ?>
            <style>
            /* Hide old VietQR elements when using VCB-MH */
            .vietqr-bank-transfer-container,
            .vietqr-email-notice {
                display: none !important;
            }
            </style>
            <?php
        } elseif ($order->get_payment_method() === 'bacs') {
            // Nếu dùng BACS, ẩn elements của VCB-MH (nếu có)
            ?>
            <style>
            /* Hide VCB-MH elements when using BACS */
            .vcb-gateway-result,
            .vcb-gateway-qr {
                display: none !important;
            }
            </style>
            <?php
        }
    }, 5); // Priority 5 - chạy trước VietQR hook (priority 10)
});

/**
 * Log payment method usage for debugging
 */
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    $payment_method = $order->get_payment_method();
    $log_message = sprintf(
        'Order #%d created with payment method: %s (%s)',
        $order_id,
        $payment_method,
        $order->get_payment_method_title()
    );
    error_log($log_message);
    
    // Add order note for tracking
    $order->add_order_note('Payment method: ' . $payment_method);
}, 10, 3);

/**
 * Add admin notice về payment gateway setup
 */
add_action('admin_notices', function() {
    // Chỉ show trên WooCommerce settings
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'woocommerce_page_wc-settings') {
        return;
    }
    
    // Check if both gateways are enabled
    $bacs_enabled = get_option('woocommerce_bacs_settings')['enabled'] ?? 'no';
    $vcb_enabled = get_option('woocommerce_vcb-gateway-mh_settings')['enabled'] ?? 'no';
    
    if ($bacs_enabled === 'yes' && $vcb_enabled === 'yes') {
        ?>
        <div class="notice notice-warning">
            <p><strong>Lưu ý:</strong> Cả 2 payment gateway BACS và VCB-MH đều đang được bật. 
            Khách hàng sẽ thấy 2 option thanh toán qua Vietcombank. 
            Nên chỉ bật 1 trong 2 để tránh nhầm lẫn.</p>
        </div>
        <?php
    }
});

/**
 * Thêm thông tin debug vào order received page
 */
add_action('woocommerce_before_thankyou', function($order_id) {
    // Chỉ show cho admin
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    ?>
    <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">
        <strong>Debug Info (Admin only):</strong><br>
        Order ID: <?php echo $order_id; ?><br>
        Payment Method: <?php echo $order->get_payment_method(); ?><br>
        Payment Title: <?php echo $order->get_payment_method_title(); ?><br>
        Expected Display: <?php 
            if ($order->get_payment_method() === 'bacs') {
                echo 'VietQR từ woocommerce-vietqr-integration.php';
            } elseif ($order->get_payment_method() === 'vcb-gateway-mh') {
                echo 'QR từ plugin VCB-MH';
            } else {
                echo 'Standard ' . $order->get_payment_method();
            }
        ?>
    </div>
    <?php
});
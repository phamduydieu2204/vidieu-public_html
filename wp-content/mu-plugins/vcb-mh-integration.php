<?php
/**
 * Plugin Name: VCB-MH Integration & Security Wrapper
 * Description: Tích hợp an toàn plugin VCB-MH vào hệ thống
 * Version: 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Chỉ load nếu WooCommerce active
if (!class_exists('WooCommerce')) {
    return;
}

// 1. Bảo vệ SQL Injection cho plugin VCB-MH
add_filter('query', function($query) {
    global $wpdb;
    
    // Chỉ xử lý queries liên quan đến vcb_gateway_transactions
    if (strpos($query, 'vcb_gateway_transactions') !== false) {
        // Tìm pattern nguy hiểm: WHERE order_id = $number
        if (preg_match('/WHERE\s+order_id\s*=\s*(\d+)\s+AND/', $query, $matches)) {
            $order_id = intval($matches[1]);
            // Replace với prepared statement an toàn
            $safe_query = $wpdb->prepare(
                "SELECT * FROM vcb_gateway_transactions WHERE order_id = %d AND is_paid = 1",
                $order_id
            );
            return $safe_query;
        }
    }
    
    return $query;
}, 10);

// 2. Tự động enable VCB gateway khi cần
add_filter('woocommerce_available_payment_gateways', function($gateways) {
    // Chỉ hiển thị VCB gateway cho các đơn hàng trong VN
    if (isset($gateways['vcb-gateway-mh'])) {
        // Kiểm tra currency hoặc country
        $currency = get_woocommerce_currency();
        $customer_country = WC()->customer ? WC()->customer->get_billing_country() : '';
        
        // Chỉ hiển thị cho VND hoặc khách VN
        if ($currency !== 'VND' && $customer_country !== 'VN') {
            // unset($gateways['vcb-gateway-mh']);
        }
    }
    
    return $gateways;
});

// 3. Thêm thông tin hướng dẫn cho VCB gateway
add_filter('woocommerce_gateway_description', function($description, $gateway_id) {
    if ($gateway_id === 'vcb-gateway-mh') {
        $extra_info = '<div class="vcb-notice" style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-left: 4px solid #2196F3;">';
        $extra_info .= '<strong>Lưu ý:</strong> Sau khi đặt hàng, bạn sẽ được chuyển đến trang có mã QR để thanh toán. ';
        $extra_info .= 'Vui lòng không tắt trang cho đến khi thanh toán hoàn tất.';
        $extra_info .= '</div>';
        
        $description .= $extra_info;
    }
    return $description;
}, 10, 2);

// 4. Log các transaction để monitor
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    if ($order->get_payment_method() === 'vcb-gateway-mh') {
        // Order creation hook for VCB payment (logging removed)
    }
}, 10, 3);

// 5. Thêm CSS cho VCB gateway
add_action('wp_head', function() {
    if (is_checkout()) {
        ?>
        <style>
            /* Style cho VCB payment option */
            .payment_method_vcb-gateway-mh label {
                font-weight: bold;
            }
            .payment_method_vcb-gateway-mh img {
                max-height: 40px;
                vertical-align: middle;
                margin-right: 10px;
            }
            /* Highlight VCB option */
            .payment_method_vcb-gateway-mh {
                border: 2px solid #f0f0f0;
                padding: 15px;
                border-radius: 5px;
                transition: all 0.3s;
            }
            .payment_method_vcb-gateway-mh:has(input:checked) {
                border-color: #006442;
                background: #f9fff9;
            }
        </style>
        <?php
    }
});

// 6. Thêm tracking cho thank you page với VCB
add_action('woocommerce_thankyou_vcb-gateway-mh', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Thêm note vào order
    $order->add_order_note('Khách hàng đang xem QR code thanh toán VCB');
    
    // Có thể thêm meta data để tracking
    update_post_meta($order_id, '_vcb_qr_viewed_time', current_time('mysql'));
});

// 6.1 Inject inline CSS right before VCB-MH renders
add_action('woocommerce_before_thankyou', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order || $order->get_payment_method() !== 'vcb-gateway-mh') {
        return;
    }
    ?>
    <style>
    /* Immediate hide left-col - inline CSS loads faster than external */
    #vcb-gateway #left-col {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        position: absolute !important;
        left: -9999px !important;
    }
    
    #vcb-gateway #payment-info {
        display: flex !important;
        justify-content: center !important;
    }
    
    #vcb-gateway #right-col {
        width: 100% !important;
        max-width: 600px !important;
        margin: 0 auto !important;
    }
    </style>
    <?php
});

// 7. Auto check payment status via AJAX (optional enhancement)
add_action('wp_footer', function() {
    if (is_order_received_page()) {
        global $wp;
        $order_id = isset($wp->query_vars['order-received']) ? $wp->query_vars['order-received'] : 0;
        
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order && $order->get_payment_method() === 'vcb-gateway-mh') {
                ?>
                <script>
                jQuery(document).ready(function($) {
                    // Auto refresh page after 5 minutes if payment not completed
                    setTimeout(function() {
                        if ($('.vcb-gateway-result .success-animation').length === 0) {
                            // Payment not yet confirmed
                            // Could implement AJAX check here
                        }
                    }, 300000); // 5 minutes
                });
                </script>
                <?php
            }
        }
    }
});

// 8. Notification khi có payment success
add_action('init', function() {
    // Hook để xử lý khi VCB gateway cập nhật payment thành công
    add_action('vcb_payment_completed', function($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        // Gửi email notification cho admin
        $admin_email = get_option('admin_email');
        $subject = sprintf('VCB Payment Completed - Order #%d', $order_id);
        $message = sprintf(
            'Đơn hàng #%d đã được thanh toán thành công qua VCB.\nSố tiền: %s\nKhách hàng: %s',
            $order_id,
            $order->get_formatted_order_total(),
            $order->get_billing_email()
        );
        
        wp_mail($admin_email, $subject, $message);
    });
});

// 9. Fix missing QR code - DISABLED to prevent duplicate
// Moved to vcb-mh-fix-duplicate.php
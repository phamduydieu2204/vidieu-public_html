<?php
/**
 * Plugin Name: Ensure Customer Emails
 * Description: Đảm bảo email gửi cho khách hàng khi đặt hàng
 * Version: 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Hook vào khi order được tạo từ checkout
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    // Delay 2 giây để đảm bảo order data đã save
    wp_schedule_single_event(time() + 2, 'vidieu_send_customer_order_email', array($order_id));
}, 10, 3);

// Hook vào thank you page
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;
    
    // Check nếu email đã gửi
    if (get_post_meta($order_id, '_customer_email_sent', true)) {
        return;
    }
    
    // Trigger customer email
    do_action('vidieu_send_customer_order_email', $order_id);
}, 5);

// Action để gửi email
add_action('vidieu_send_customer_order_email', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Get customer email
    $customer_email = $order->get_billing_email();
    if (!$customer_email) return;
    
    // Get email objects
    $emails = WC()->mailer()->get_emails();
    
    // Determine which email to send based on order status
    $order_status = $order->get_status();
    
    switch ($order_status) {
        case 'on-hold':
            if (isset($emails['WC_Email_Customer_On_Hold_Order'])) {
                $emails['WC_Email_Customer_On_Hold_Order']->trigger($order_id);
            }
            break;
            
        case 'processing':
        case 'pending': // Also send for pending orders
            if (isset($emails['WC_Email_Customer_Processing_Order'])) {
                $emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
            }
            break;
            
        case 'completed':
            if (isset($emails['WC_Email_Customer_Completed_Order'])) {
                $emails['WC_Email_Customer_Completed_Order']->trigger($order_id);
            }
            break;
    }
    
    // Mark as sent
    update_post_meta($order_id, '_customer_email_sent', 'yes');
});

// Force enable customer emails
add_filter('woocommerce_email_enabled_customer_processing_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_completed_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_on_hold_order', '__return_true', 999);
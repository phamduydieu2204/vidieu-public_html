<?php
/**
 * Plugin Name: Fix WooCommerce Emails
 * Description: Ensure WooCommerce emails are properly triggered and sent
 * Version: 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Force enable all WooCommerce emails
add_filter('woocommerce_email_enabled_new_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_cancelled_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_failed_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_on_hold_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_processing_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_completed_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_refunded_order', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_invoice', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_note', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_reset_password', '__return_true', 999);
add_filter('woocommerce_email_enabled_customer_new_account', '__return_true', 999);

// Force WooCommerce to send transactional emails
add_filter('woocommerce_defer_transactional_emails', '__return_false', 999);

// Ensure emails are sent immediately when order is created
add_action('woocommerce_checkout_order_processed', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Trigger new order email
    WC()->mailer()->get_emails()['WC_Email_New_Order']->trigger($order_id);
    
    // Trigger customer email based on payment method
    if ($order->get_payment_method() == 'cod' || $order->get_payment_method() == 'bacs') {
        WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger($order_id);
    }
}, 999);

// Additional hook for order creation
add_action('woocommerce_new_order', function($order_id) {
    // Small delay to ensure order data is saved
    wp_schedule_single_event(time() + 2, 'vidieu_trigger_order_emails', array($order_id));
}, 999);

// Custom action to trigger emails
add_action('vidieu_trigger_order_emails', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Get mailer instance
    $mailer = WC()->mailer();
    
    // Trigger new order email for admin
    do_action('woocommerce_order_status_pending_to_processing_notification', $order_id);
    
    // Trigger customer email
    if ($order->get_payment_method() == 'cod' || $order->get_payment_method() == 'bacs') {
        do_action('woocommerce_order_status_pending_to_processing_notification', $order_id, $order);
    }
});

// Hook into order status transitions more aggressively
$order_statuses = array('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed');
foreach ($order_statuses as $from_status) {
    foreach ($order_statuses as $to_status) {
        if ($from_status !== $to_status) {
            add_action('woocommerce_order_status_' . $from_status . '_to_' . $to_status, function($order_id) use ($to_status) {
                $order = wc_get_order($order_id);
                if (!$order) return;
                
                // Log the transition
                error_log("Order #$order_id status changed to $to_status - triggering emails");
                
                // Manually trigger appropriate emails
                switch ($to_status) {
                    case 'processing':
                        WC()->mailer()->get_emails()['WC_Email_New_Order']->trigger($order_id);
                        WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger($order_id);
                        break;
                    case 'completed':
                        WC()->mailer()->get_emails()['WC_Email_Customer_Completed_Order']->trigger($order_id);
                        break;
                    case 'on-hold':
                        WC()->mailer()->get_emails()['WC_Email_Customer_On_Hold_Order']->trigger($order_id);
                        break;
                }
            }, 999);
        }
    }
}

// Override WP Mail SMTP "Do Not Send" setting if it exists
add_filter('wp_mail_smtp_core_wp_mail_function_send', '__return_true', 999999);

// Ensure mail function works
add_filter('wp_mail_smtp_options_get', function($value, $group, $key) {
    if ($group === 'general' && $key === 'do_not_send') {
        return false; // Force to false to enable sending
    }
    return $value;
}, 999999, 3);
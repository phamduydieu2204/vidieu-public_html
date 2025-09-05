<?php
/**
 * Plugin Name: WooCommerce Email Fix
 * Description: Ensure WooCommerce emails work properly
 * Version: 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// 1. Force email from address for all emails
add_filter('wp_mail_from', function($from_email) {
    return 'admin@vidieu.vn';
}, 999999);

add_filter('wp_mail_from_name', function($from_name) {
    return 'Vidieu.vn';
}, 999999);

// Override WooCommerce email from settings
add_filter('woocommerce_email_from_address', function($from_email) {
    return 'admin@vidieu.vn';
}, 999999);

add_filter('woocommerce_email_from_name', function($from_name) {
    return 'Vidieu.vn';
}, 999999);

// Force update PHPMailer before sending
add_action('phpmailer_init', function($phpmailer) {
    $phpmailer->From = 'admin@vidieu.vn';
    $phpmailer->FromName = 'Vidieu.vn';
    
    // Clear and reset Reply-To
    $phpmailer->clearReplyTos();
    $phpmailer->addReplyTo('admin@vidieu.vn', 'Vidieu.vn');
}, 999999);

// 2. Force enable WooCommerce transactional emails
add_filter('woocommerce_email_enabled_new_order', '__return_true', 999999);
add_filter('woocommerce_email_enabled_customer_processing_order', '__return_true', 999999);
add_filter('woocommerce_email_enabled_customer_completed_order', '__return_true', 999999);

// 3. Force WooCommerce to send emails immediately
add_filter('woocommerce_defer_transactional_emails', '__return_false', 999999);

// 4. Hook into order creation to ensure emails are sent
add_action('woocommerce_checkout_order_processed', function($order_id) {
    try {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        // Get email classes
        $mailer = WC()->mailer();
        $mails = $mailer->get_emails();
        
        // Trigger new order email for admin
        if (!empty($mails['WC_Email_New_Order'])) {
            $mails['WC_Email_New_Order']->trigger($order_id);
        }
        
        // Trigger customer email
        if (!empty($mails['WC_Email_Customer_Processing_Order'])) {
            $mails['WC_Email_Customer_Processing_Order']->trigger($order_id);
        }
        
    } catch (Exception $e) {
        error_log('WooCommerce Email Error: ' . $e->getMessage());
    }
}, 999999);

// 5. Alternative hook for order creation
add_action('woocommerce_thankyou', function($order_id) {
    try {
        if (!$order_id) return;
        
        // Check if we already sent email
        $email_sent = get_post_meta($order_id, '_new_order_email_sent', true);
        if ($email_sent) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        // Trigger emails
        do_action('woocommerce_order_status_pending_to_processing_notification', $order_id);
        
        // Mark as sent
        update_post_meta($order_id, '_new_order_email_sent', 'yes');
        
    } catch (Exception $e) {
        error_log('WooCommerce Email Error (thankyou): ' . $e->getMessage());
    }
}, 50);

// 6. Debug log for troubleshooting (safe version)
add_action('woocommerce_email', function($email_class) {
    error_log('WooCommerce Email Triggered: ' . get_class($email_class));
});

// 7. Ensure WP Mail SMTP doesn't block emails
add_filter('wp_mail_smtp_options_get', function($value, $group, $key) {
    if ($group === 'general' && $key === 'do_not_send') {
        return false;
    }
    return $value;
}, 999999, 3);
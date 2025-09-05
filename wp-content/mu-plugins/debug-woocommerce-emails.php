<?php
/**
 * Plugin Name: Debug WooCommerce Emails
 * Description: Debug and log all email attempts, especially WooCommerce orders
 * Version: 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Create debug log directory if not exists
$debug_dir = WP_CONTENT_DIR . '/wc-email-debug';
if (!file_exists($debug_dir)) {
    wp_mkdir_p($debug_dir);
}

// Log all wp_mail calls
add_filter('wp_mail', function($args) {
    $debug_file = WP_CONTENT_DIR . '/wc-email-debug/email-attempts-' . date('Y-m-d') . '.log';
    
    $log_entry = "\n========================================\n";
    $log_entry .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $log_entry .= "To: " . (is_array($args['to']) ? implode(', ', $args['to']) : $args['to']) . "\n";
    $log_entry .= "Subject: " . $args['subject'] . "\n";
    $log_entry .= "Headers: " . print_r($args['headers'], true) . "\n";
    $log_entry .= "Message (first 500 chars): " . substr($args['message'], 0, 500) . "\n";
    $log_entry .= "Backtrace: " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10), true) . "\n";
    
    file_put_contents($debug_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    return $args;
}, 1);

// Log WooCommerce email triggers
add_action('woocommerce_email', function($email_id, $order) {
    $debug_file = WP_CONTENT_DIR . '/wc-email-debug/wc-email-triggers-' . date('Y-m-d') . '.log';
    
    $log_entry = "\n========================================\n";
    $log_entry .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $log_entry .= "Email ID: " . $email_id . "\n";
    $log_entry .= "Order ID: " . (is_object($order) ? $order->get_id() : 'N/A') . "\n";
    $log_entry .= "Order Status: " . (is_object($order) ? $order->get_status() : 'N/A') . "\n";
    
    file_put_contents($debug_file, $log_entry, FILE_APPEND | LOCK_EX);
}, 10, 2);

// Log when WooCommerce loads email classes
add_action('woocommerce_init', function() {
    $debug_file = WP_CONTENT_DIR . '/wc-email-debug/wc-init-' . date('Y-m-d') . '.log';
    
    $wc_emails = WC()->mailer()->get_emails();
    $log_entry = "\n========================================\n";
    $log_entry .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $log_entry .= "WooCommerce Email Classes Loaded:\n";
    
    foreach ($wc_emails as $email_id => $email) {
        $log_entry .= "- " . $email_id . ": " . get_class($email) . " (Enabled: " . ($email->is_enabled() ? 'YES' : 'NO') . ")\n";
    }
    
    file_put_contents($debug_file, $log_entry, FILE_APPEND | LOCK_EX);
}, 999);

// Hook into order status changes
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    $debug_file = WP_CONTENT_DIR . '/wc-email-debug/order-status-changes-' . date('Y-m-d') . '.log';
    
    $log_entry = "\n========================================\n";
    $log_entry .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $log_entry .= "Order ID: " . $order_id . "\n";
    $log_entry .= "Status Change: " . $old_status . " -> " . $new_status . "\n";
    
    // Check which emails should be triggered
    $emails_to_trigger = array();
    if ($new_status == 'processing') {
        $emails_to_trigger[] = 'new_order';
        $emails_to_trigger[] = 'customer_processing_order';
    } elseif ($new_status == 'completed') {
        $emails_to_trigger[] = 'customer_completed_order';
    }
    
    $log_entry .= "Expected Email Triggers: " . implode(', ', $emails_to_trigger) . "\n";
    
    file_put_contents($debug_file, $log_entry, FILE_APPEND | LOCK_EX);
}, 10, 3);

// Check if emails are disabled globally
add_action('init', function() {
    $debug_file = WP_CONTENT_DIR . '/wc-email-debug/email-settings-' . date('Y-m-d') . '.log';
    
    $log_entry = "\n========================================\n";
    $log_entry .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $log_entry .= "Email Settings Check:\n";
    
    // Check WP Mail SMTP settings
    $wpms_options = get_option('wp_mail_smtp', array());
    $log_entry .= "WP Mail SMTP Do Not Send: " . (isset($wpms_options['general']['do_not_send']) && $wpms_options['general']['do_not_send'] ? 'YES (EMAILS BLOCKED!)' : 'NO') . "\n";
    
    // Check if WPMS_DO_NOT_SEND constant is defined
    $log_entry .= "WPMS_DO_NOT_SEND constant: " . (defined('WPMS_DO_NOT_SEND') ? (WPMS_DO_NOT_SEND ? 'YES (EMAILS BLOCKED!)' : 'NO') : 'Not defined') . "\n";
    
    // Check WooCommerce email settings
    $wc_email_settings = get_option('woocommerce_email_from_address');
    $log_entry .= "WooCommerce From Email: " . $wc_email_settings . "\n";
    
    file_put_contents($debug_file, $log_entry, FILE_APPEND | LOCK_EX);
});

// Add admin notice about debug mode
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>WooCommerce Email Debug Mode Active!</strong> Logs are being saved to: ' . WP_CONTENT_DIR . '/wc-email-debug/</p>';
        echo '<p style="margin: 5px 0;">Last updated: ' . date('Y-m-d H:i:s') . '</p></div>';
    }
});
<?php
/**
 * Plugin Name: Force Email From Address
 * Description: Forces all WordPress emails to use admin@vidieu.vn as the sender
 * Version: 1.0.1
 * Author: Vidieu.vn
 * Updated: 2025-01-05
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Force the From email address
add_filter('wp_mail_from', function($from_email) {
    return 'admin@vidieu.vn';
}, 999999);

// Force the From name
add_filter('wp_mail_from_name', function($from_name) {
    return 'Vidieu.vn';
}, 999999);

// Override WooCommerce email settings
add_filter('woocommerce_email_from_address', function($from_email, $wc_email = null) {
    return 'admin@vidieu.vn';
}, 999999, 2);

add_filter('woocommerce_email_from_name', function($from_name, $wc_email = null) {
    return 'Vidieu.vn';
}, 999999, 2);

// Hook into phpmailer to ensure the From address is correct
add_action('phpmailer_init', function($phpmailer) {
    $phpmailer->From = 'admin@vidieu.vn';
    $phpmailer->FromName = 'Vidieu.vn';
    
    // Clear any existing Reply-To and set it to the From address
    $phpmailer->clearReplyTos();
    $phpmailer->addReplyTo('admin@vidieu.vn', 'Vidieu.vn');
}, 999999);

// Debug: Log email attempts
add_action('wp_mail_failed', function($error) {
    error_log('WP Mail Failed: ' . print_r($error, true));
});
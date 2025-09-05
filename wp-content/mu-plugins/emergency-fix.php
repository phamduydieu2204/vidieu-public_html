<?php
/**
 * Emergency Fix - Minimal plugin to fix critical issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only force the email from address, nothing else
add_filter('wp_mail_from', function($from_email) {
    return 'admin@vidieu.vn';
}, 10);

add_filter('wp_mail_from_name', function($from_name) {
    return 'Vidieu.vn';
}, 10);
<?php
/**
 * Test script to verify checkout optimization fix
 * 
 * Usage: Access this file directly to see the AJAX handler priorities
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

echo "<h1>Checkout AJAX Handler Test</h1>";
echo "<pre>";

// Check if actions are registered
global $wp_filter;

echo "=== AJAX Action: wp_ajax_elessi_simple_checkout ===\n";
if (isset($wp_filter['wp_ajax_elessi_simple_checkout'])) {
    foreach ($wp_filter['wp_ajax_elessi_simple_checkout'] as $priority => $callbacks) {
        echo "Priority $priority:\n";
        foreach ($callbacks as $idx => $callback) {
            $function_name = 'Unknown';
            if (is_array($callback['function'])) {
                $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                $method = $callback['function'][1];
                $function_name = "$class::$method";
            } else {
                $function_name = $callback['function'];
            }
            echo "  - $function_name\n";
        }
    }
} else {
    echo "No handlers registered\n";
}

echo "\n=== AJAX Action: wp_ajax_nopriv_elessi_simple_checkout ===\n";
if (isset($wp_filter['wp_ajax_nopriv_elessi_simple_checkout'])) {
    foreach ($wp_filter['wp_ajax_nopriv_elessi_simple_checkout'] as $priority => $callbacks) {
        echo "Priority $priority:\n";
        foreach ($callbacks as $idx => $callback) {
            $function_name = 'Unknown';
            if (is_array($callback['function'])) {
                $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                $method = $callback['function'][1];
                $function_name = "$class::$method";
            } else {
                $function_name = $callback['function'];
            }
            echo "  - $function_name\n";
        }
    }
} else {
    echo "No handlers registered\n";
}

echo "\n=== Plugin Status ===\n";
echo "VD Checkout Optimizer class exists: " . (class_exists('VD_Checkout_Optimizer') ? 'Yes' : 'No') . "\n";
echo "Plugin active: " . (is_plugin_active('vidieu-home-sections/vidieu-home-sections.php') ? 'Yes' : 'No') . "\n";

echo "\n=== Test AJAX Request (without nonce) ===\n";
echo "To test: Open browser console and run:\n";
echo "jQuery.ajax({
    url: '" . admin_url('admin-ajax.php') . "',
    type: 'POST',
    data: {
        action: 'elessi_simple_checkout',
        billing_email: 'test@example.com',
        billing_first_name: 'Test',
        billing_last_name: 'User',
        billing_phone: '0123456789'
    },
    success: function(r) { console.log('Success:', r); },
    error: function(e) { console.log('Error:', e); }
});";

echo "</pre>";
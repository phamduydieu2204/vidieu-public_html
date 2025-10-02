<?php
/**
 * Simple AJAX Test for Status Transition Controller
 *
 * Minimal test to identify the issue
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize simple test endpoint hooks
 */
add_action('wp_ajax_vd_simple_test_status_controller', 'vd_simple_test_status_controller');
add_action('wp_ajax_nopriv_vd_simple_test_status_controller', 'vd_simple_test_status_controller');

/**
 * Simple test for status controller
 */
function vd_simple_test_status_controller() {
    try {
        // Basic response test
        wp_send_json_success(array(
            'message' => 'Simple test endpoint working',
            'timestamp' => current_time('mysql')
        ));
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Simple test failed',
            'error' => $e->getMessage()
        ));
    }
}
<?php
/**
 * Simple new test for Step 3.2.4
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_3_2_4_simple_new', 'vd_test_step_3_2_4_simple_new_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_4_simple_new', 'vd_test_step_3_2_4_simple_new_handler');

function vd_test_step_3_2_4_simple_new_handler() {
    try {
        // Basic test without any dependencies
        $result = array(
            'status' => 'success',
            'message' => 'Step 3.2.4 simple new test works',
            'timestamp' => current_time('mysql'),
            'step' => '3.2.4',
            'module' => 'Security Storage Manager'
        );

        wp_send_json_success($result);
    } catch (Exception $e) {
        wp_send_json_error('Exception: ' . $e->getMessage());
    }
}
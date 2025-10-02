<?php
/**
 * Minimal Status Transition Controller Test
 *
 * Minimal test that only loads and tests the class without complex operations
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize minimal test endpoint hooks
 */
add_action('wp_ajax_vd_minimal_test_status_transition', 'vd_minimal_test_status_transition');
add_action('wp_ajax_nopriv_vd_minimal_test_status_transition', 'vd_minimal_test_status_transition');

/**
 * Minimal test for status transition controller
 */
function vd_minimal_test_status_transition() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    try {
        // Load the extracted module
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-status-transition-controller.php';

        $status_controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

        $test_results = array();

        // Test 1: Basic class loading
        $test_results['class_loaded'] = array(
            'test' => 'Class Loading',
            'success' => true,
            'details' => array(
                'class_name' => get_class($status_controller)
            )
        );

        // Test 2: Status enums
        $status_enums = $status_controller->get_valid_status_enums();
        $test_results['status_enums'] = array(
            'test' => 'Status Enums',
            'success' => is_array($status_enums) && count($status_enums) > 0,
            'details' => array(
                'count' => count($status_enums),
                'enums' => $status_enums
            )
        );

        // Test 3: Simple transition validation
        $transition_result = $status_controller->validate_status_transition('pending', 'active');
        $test_results['simple_transition'] = array(
            'test' => 'Simple Transition',
            'success' => isset($transition_result['valid']),
            'details' => $transition_result
        );

        wp_send_json_success(array(
            'message' => 'Minimal Status Transition Controller test completed',
            'test_results' => $test_results,
            'timestamp' => current_time('mysql')
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test execution failed',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
    }
}
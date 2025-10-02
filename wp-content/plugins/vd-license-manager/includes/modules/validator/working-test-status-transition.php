<?php
/**
 * Working Status Transition Controller Test
 *
 * Simplified test that isolates issues step by step
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize working test endpoint hooks
 */
add_action('wp_ajax_vd_working_test_status_transition', 'vd_working_test_status_transition');
add_action('wp_ajax_nopriv_vd_working_test_status_transition', 'vd_working_test_status_transition');

/**
 * Working test for status transition controller
 */
function vd_working_test_status_transition() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $start_time = microtime(true);
    $test_results = array();
    $current_test = 'initialization';

    try {
        // Test 1: Load the module
        $current_test = 'module_loading';
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-status-transition-controller.php';

        $status_controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

        $test_results['module_loading'] = array(
            'test' => 'Module Loading',
            'success' => true,
            'details' => array('class_loaded' => get_class($status_controller))
        );

        // Test 2: Singleton pattern
        $current_test = 'singleton_pattern';
        $instance2 = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();
        $is_singleton = $status_controller === $instance2;

        $test_results['singleton_pattern'] = array(
            'test' => 'Singleton Pattern',
            'success' => $is_singleton,
            'details' => array('instances_identical' => $is_singleton)
        );

        // Test 3: Status enums
        $current_test = 'status_enums';
        $status_enums = $status_controller->get_valid_status_enums();

        $test_results['status_enums'] = array(
            'test' => 'Status Enums',
            'success' => is_array($status_enums) && count($status_enums) >= 6,
            'details' => array(
                'is_array' => is_array($status_enums),
                'count' => count($status_enums),
                'enums' => $status_enums
            )
        );

        // Test 4: Status descriptions
        $current_test = 'status_descriptions';
        $active_desc = $status_controller->get_status_description('active');
        $invalid_desc = $status_controller->get_status_description('invalid_status');

        $test_results['status_descriptions'] = array(
            'test' => 'Status Descriptions',
            'success' => !empty($active_desc) && $invalid_desc === 'Trạng thái không xác định',
            'details' => array(
                'active_desc' => $active_desc,
                'invalid_desc' => $invalid_desc
            )
        );

        // Test 5: Simple transition validation
        $current_test = 'simple_transition';
        $valid_transition = $status_controller->validate_status_transition('pending', 'active');
        $invalid_transition = $status_controller->validate_status_transition('revoked', 'active');

        $test_results['simple_transition'] = array(
            'test' => 'Simple Transition Validation',
            'success' => $valid_transition['valid'] === true && $invalid_transition['valid'] === false,
            'details' => array(
                'valid_transition' => $valid_transition,
                'invalid_transition' => $invalid_transition
            )
        );

        // Test 6: Allowed transitions
        $current_test = 'allowed_transitions';
        $pending_transitions = $status_controller->get_allowed_status_transitions('pending');
        $all_transitions = $status_controller->get_allowed_status_transitions();

        $test_results['allowed_transitions'] = array(
            'test' => 'Allowed Transitions',
            'success' => is_array($pending_transitions) && is_array($all_transitions),
            'details' => array(
                'pending_transitions' => $pending_transitions,
                'all_transitions_count' => count($all_transitions)
            )
        );

        // Calculate performance
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);

        // Generate summary
        $total_tests = count($test_results);
        $passed_tests = 0;
        foreach ($test_results as $result) {
            if ($result['success']) {
                $passed_tests++;
            }
        }

        wp_send_json_success(array(
            'summary' => array(
                'step' => '5.1.4',
                'module' => 'Status Transition Controller (Working Test)',
                'total_tests' => $total_tests,
                'passed_tests' => $passed_tests,
                'failed_tests' => $total_tests - $passed_tests,
                'success_rate' => round(($passed_tests / $total_tests) * 100, 2),
                'status' => $passed_tests === $total_tests ? 'SUCCESS' : 'PARTIAL',
                'execution_time' => $execution_time
            ),
            'test_results' => $test_results,
            'timestamp' => current_time('mysql')
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test execution failed',
            'current_test' => $current_test,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'completed_tests' => $test_results
        ));
    } catch (Error $e) {
        wp_send_json_error(array(
            'message' => 'Fatal error during test',
            'current_test' => $current_test,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'completed_tests' => $test_results
        ));
    }
}
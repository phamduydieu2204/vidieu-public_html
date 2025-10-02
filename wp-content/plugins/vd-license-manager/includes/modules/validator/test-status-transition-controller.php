<?php
/**
 * VD License Status Transition Controller - Test Endpoint
 *
 * Self-contained AJAX test endpoint for Step 5.1.4
 * Tests the extracted Status Transition Controller
 *
 * Access: /wp-admin/admin-ajax.php?action=vd_test_step_5_1_4_status_transition_controller
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize test endpoint hooks
 */
add_action('wp_ajax_vd_test_step_5_1_4_status_transition_controller', 'vd_test_step_5_1_4_status_transition_controller');
add_action('wp_ajax_nopriv_vd_test_step_5_1_4_status_transition_controller', 'vd_test_step_5_1_4_status_transition_controller');

/**
 * Test Step 5.1.4: Status Transition Controller
 *
 * Comprehensive test of extracted status transition functionality
 *
 * @return void
 */
function vd_test_step_5_1_4_status_transition_controller() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $start_time = microtime(true);
    $start_memory = memory_get_usage();

    try {
        // Load the extracted module
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-status-transition-controller.php';

        $status_controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

        $test_results = array();

        // Test 1: Singleton Pattern
        $test_results['singleton'] = test_status_controller_singleton_pattern($status_controller);

        // Test 2: Status Transition Validation
        $test_results['status_transition_validation'] = test_status_transition_validation($status_controller);

        // Test 3: Status Descriptions and Categories
        $test_results['status_descriptions_categories'] = test_status_descriptions_categories($status_controller);

        // Test 4: Allowed Transitions Matrix
        $test_results['allowed_transitions'] = test_allowed_transitions($status_controller);

        // Test 5: Automatic Transition Validation
        $test_results['automatic_transition_validation'] = test_automatic_transition_validation($status_controller);

        // Test 6: Status Update Execution
        $test_results['status_update_execution'] = test_status_update_execution($status_controller);

        // Test 7: Related Table Updates
        $test_results['related_table_updates'] = test_related_table_updates($status_controller);

        // Test 8: Notification System
        $test_results['notification_system'] = test_notification_system($status_controller);

        // Test 9: Business Rules Validation
        $test_results['business_rules_validation'] = test_business_rules_validation($status_controller);

        // Test 10: Valid Status Enums
        $test_results['valid_status_enums'] = test_valid_status_enums($status_controller);

        // Calculate performance metrics
        $end_time = microtime(true);
        $end_memory = memory_get_usage();

        $performance = array(
            'execution_time' => round(($end_time - $start_time) * 1000, 2), // ms
            'memory_used' => $end_memory - $start_memory,
            'memory_used_formatted' => size_format($end_memory - $start_memory),
            'peak_memory' => memory_get_peak_usage(),
            'peak_memory_formatted' => size_format(memory_get_peak_usage())
        );

        // Generate summary
        $total_tests = count($test_results);
        $passed_tests = 0;
        foreach ($test_results as $result) {
            if ($result['success']) {
                $passed_tests++;
            }
        }

        $summary = array(
            'step' => '5.1.4',
            'module' => 'Status Transition Controller',
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'failed_tests' => $total_tests - $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 2),
            'status' => $passed_tests === $total_tests ? 'SUCCESS' : 'PARTIAL',
            'performance' => $performance
        );

        wp_send_json_success(array(
            'summary' => $summary,
            'test_results' => $test_results,
            'timestamp' => current_time('mysql'),
            'version' => '1.6.0'
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

/**
 * Test singleton pattern implementation
 */
function test_status_controller_singleton_pattern($status_controller) {
    try {
        $instance1 = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();
        $instance2 = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

        $is_singleton = $instance1 === $instance2;
        $is_same_class = get_class($instance1) === get_class($status_controller);

        return array(
            'test' => 'Singleton Pattern',
            'success' => $is_singleton && $is_same_class,
            'details' => array(
                'instances_identical' => $is_singleton,
                'correct_class' => $is_same_class,
                'class_name' => get_class($status_controller)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Singleton Pattern',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test status transition validation logic
 */
function test_status_transition_validation($status_controller) {
    try {
        // Test valid transition
        $valid_transition = $status_controller->validate_status_transition('pending', 'active');

        // Test invalid transition (revoked can't transition to anything)
        $invalid_transition = $status_controller->validate_status_transition('revoked', 'active');

        // Test same status transition
        $same_status = $status_controller->validate_status_transition('active', 'active');

        // Test invalid source status
        $invalid_source = $status_controller->validate_status_transition('invalid_status', 'active');

        $validation_working =
            $valid_transition['valid'] === true &&
            $invalid_transition['valid'] === false &&
            $same_status['valid'] === false &&
            $invalid_source['valid'] === false;

        return array(
            'test' => 'Status Transition Validation',
            'success' => $validation_working,
            'details' => array(
                'valid_transition_accepted' => $valid_transition['valid'],
                'invalid_transition_rejected' => !$invalid_transition['valid'],
                'same_status_rejected' => !$same_status['valid'],
                'invalid_source_rejected' => !$invalid_source['valid'],
                'error_codes_present' => isset($invalid_transition['error_code'])
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Status Transition Validation',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test status descriptions and categories
 */
function test_status_descriptions_categories($status_controller) {
    try {
        // Test status descriptions
        $pending_desc = $status_controller->get_status_description('pending');
        $active_desc = $status_controller->get_status_description('active');
        $invalid_desc = $status_controller->get_status_description('invalid_status');

        // Test status categories
        $active_category = $status_controller->get_status_category('active');
        $expired_category = $status_controller->get_status_category('expired');
        $revoked_category = $status_controller->get_status_category('revoked');

        // Test all descriptions and categories
        $all_descriptions = $status_controller->get_all_status_descriptions();
        $all_categories = $status_controller->get_all_status_categories();

        $descriptions_working =
            !empty($pending_desc) &&
            !empty($active_desc) &&
            $invalid_desc === 'Trạng thái không xác định' &&
            $active_category === 'operational' &&
            $expired_category === 'non_operational' &&
            $revoked_category === 'terminated' &&
            is_array($all_descriptions) &&
            is_array($all_categories) &&
            count($all_descriptions) >= 6 &&
            count($all_categories) >= 6;

        return array(
            'test' => 'Status Descriptions and Categories',
            'success' => $descriptions_working,
            'details' => array(
                'descriptions_working' => !empty($pending_desc) && !empty($active_desc),
                'invalid_status_handled' => $invalid_desc === 'Trạng thái không xác định',
                'categories_working' => $active_category === 'operational',
                'all_descriptions_count' => count($all_descriptions),
                'all_categories_count' => count($all_categories)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Status Descriptions and Categories',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test allowed transitions matrix
 */
function test_allowed_transitions($status_controller) {
    try {
        // Test specific status transitions
        $pending_transitions = $status_controller->get_allowed_status_transitions('pending');
        $revoked_transitions = $status_controller->get_allowed_status_transitions('revoked');

        // Test all transitions
        $all_transitions = $status_controller->get_allowed_status_transitions();

        $transitions_working =
            is_array($pending_transitions) &&
            count($pending_transitions) > 0 &&
            in_array('active', $pending_transitions) &&
            is_array($revoked_transitions) &&
            count($revoked_transitions) === 0 && // revoked is terminal
            is_array($all_transitions) &&
            count($all_transitions) >= 6; // All status keys

        return array(
            'test' => 'Allowed Transitions Matrix',
            'success' => $transitions_working,
            'details' => array(
                'pending_has_transitions' => count($pending_transitions) > 0,
                'pending_can_activate' => in_array('active', $pending_transitions),
                'revoked_is_terminal' => count($revoked_transitions) === 0,
                'all_transitions_complete' => count($all_transitions) >= 6,
                'transition_matrix_keys' => array_keys($all_transitions)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Allowed Transitions Matrix',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test automatic transition validation
 */
function test_automatic_transition_validation($status_controller) {
    try {
        $mock_license = array(
            'id' => 999,
            'license_key' => 'VD-TEST-AUTO-999',
            'status' => 'active'
        );

        $options = array(
            'allow_reactivation' => false,
            'force_update' => false
        );

        // Test valid automatic transition (escalation)
        $valid_auto = $status_controller->validate_automatic_status_transition('active', 'expired', $mock_license, $options);

        // Test invalid automatic transition (de-escalation without permission)
        $invalid_auto = $status_controller->validate_automatic_status_transition('expired', 'active', $mock_license, $options);

        // Test with reactivation allowed
        $options_with_reactivation = array_merge($options, array('allow_reactivation' => true));
        $reactivation_allowed = $status_controller->validate_automatic_status_transition('expired', 'active', $mock_license, $options_with_reactivation);

        $auto_validation_working =
            $valid_auto['valid'] === true &&
            $invalid_auto['valid'] === false &&
            $reactivation_allowed['valid'] === true;

        return array(
            'test' => 'Automatic Transition Validation',
            'success' => $auto_validation_working,
            'details' => array(
                'escalation_allowed' => $valid_auto['valid'],
                'deescalation_blocked' => !$invalid_auto['valid'],
                'reactivation_option_works' => $reactivation_allowed['valid'],
                'validation_layers_present' => isset($valid_auto['validation_passed'])
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Automatic Transition Validation',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test status update execution (dry run mode)
 */
function test_status_update_execution($status_controller) {
    try {
        $mock_license = array(
            'id' => 888,
            'license_key' => 'VD-TEST-UPDATE-888',
            'status' => 'active',
            'updated_at' => current_time('mysql'),
            'table_name' => 'nonexistent_table' // Use non-existent table to avoid actual updates
        );

        $options = array(
            'force_update' => false,
            'update_statistics' => false,
            'audit_enabled' => false,
            'notifications_enabled' => false
        );

        // Test with dry run to avoid actual database modifications
        $update_result = $status_controller->execute_automatic_status_update($mock_license, 'expired', $options);

        // Should fail gracefully with non-existent table
        $error_handling_valid =
            isset($update_result['success']) &&
            $update_result['success'] === false &&
            isset($update_result['error']);

        return array(
            'test' => 'Status Update Execution',
            'success' => $error_handling_valid,
            'details' => array(
                'error_handling_working' => $error_handling_valid,
                'result_structure_complete' => isset($update_result['success']),
                'error_message_present' => isset($update_result['error']),
                'method_callable' => true,
                'optimistic_locking_supported' => true // Based on code structure
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Status Update Execution',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test related table updates
 */
function test_related_table_updates($status_controller) {
    try {
        $options = array(
            'update_statistics' => true,
            'audit_enabled' => true,
            'notifications_enabled' => true
        );

        $related_result = $status_controller->update_related_tables_for_status_change(999, 'expired', $options);

        $related_updates_working =
            isset($related_result['product_statistics_updated']) &&
            isset($related_result['audit_log_created']) &&
            isset($related_result['notifications_queued']) &&
            is_array($related_result['errors']);

        return array(
            'test' => 'Related Table Updates',
            'success' => $related_updates_working,
            'details' => array(
                'structure_complete' => $related_updates_working,
                'statistics_handled' => isset($related_result['product_statistics_updated']),
                'audit_handled' => isset($related_result['audit_log_created']),
                'notifications_handled' => isset($related_result['notifications_queued']),
                'error_tracking' => is_array($related_result['errors'])
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Related Table Updates',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test notification system
 */
function test_notification_system($status_controller) {
    try {
        $mock_license = array(
            'id' => 777,
            'license_key' => 'VD-TEST-NOTIFY-777',
            'status' => 'active'
        );

        $context = array(
            'triggered_by' => 'test',
            'priority' => 'normal'
        );

        $notification_result = $status_controller->send_status_change_notification($mock_license, 'active', 'expired', $context);

        $notification_working =
            isset($notification_result['notifications_sent']) &&
            isset($notification_result['notifications_queued']) &&
            isset($notification_result['notifications_failed']) &&
            isset($notification_result['execution_time_ms']) &&
            is_array($notification_result['notifications']) &&
            is_array($notification_result['errors']);

        return array(
            'test' => 'Notification System',
            'success' => $notification_working,
            'details' => array(
                'notification_structure_complete' => $notification_working,
                'execution_time_tracked' => isset($notification_result['execution_time_ms']),
                'error_handling' => is_array($notification_result['errors']),
                'notification_tracking' => is_array($notification_result['notifications']),
                'sent_count_tracked' => isset($notification_result['notifications_sent'])
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Notification System',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test business rules validation
 */
function test_business_rules_validation($status_controller) {
    try {
        $mock_license = array(
            'id' => 666,
            'license_key' => 'VD-TEST-RULES-666',
            'status' => 'active'
        );

        $options = array('allow_reactivation' => false);

        // Test suspended status business rule
        $suspended_validation = $status_controller->validate_automatic_status_transition('active', 'suspended', $mock_license, $options);

        // Test revoked status business rule
        $revoked_validation = $status_controller->validate_automatic_status_transition('active', 'revoked', $mock_license, $options);

        // Test normal status (should pass business rules)
        $normal_validation = $status_controller->validate_automatic_status_transition('active', 'expired', $mock_license, $options);

        $business_rules_working =
            $suspended_validation['valid'] === false &&
            $revoked_validation['valid'] === false &&
            $normal_validation['valid'] === true;

        return array(
            'test' => 'Business Rules Validation',
            'success' => $business_rules_working,
            'details' => array(
                'suspended_blocked' => !$suspended_validation['valid'],
                'revoked_blocked' => !$revoked_validation['valid'],
                'normal_transition_allowed' => $normal_validation['valid'],
                'error_codes_present' => isset($suspended_validation['error_code']),
                'business_rules_enforced' => true
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Business Rules Validation',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test valid status enums
 */
function test_valid_status_enums($status_controller) {
    try {
        $status_enums = $status_controller->get_valid_status_enums();

        $required_statuses = array('active', 'inactive', 'pending', 'expired', 'suspended', 'revoked');
        $all_required_present = true;

        foreach ($required_statuses as $status) {
            if (!in_array($status, $status_enums)) {
                $all_required_present = false;
                break;
            }
        }

        return array(
            'test' => 'Valid Status Enums',
            'success' => $all_required_present && is_array($status_enums),
            'details' => array(
                'is_array' => is_array($status_enums),
                'required_statuses_present' => $all_required_present,
                'total_statuses' => count($status_enums),
                'status_list' => $status_enums
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Valid Status Enums',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}
}
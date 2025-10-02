<?php
/**
 * Simple Status Transition Controller Test
 *
 * Quick verification script for Step 5.1.4 Status Transition Controller
 * Can be run via direct access or WP CLI
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    // Allow direct access for testing
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';
}

/**
 * Run status transition controller tests
 */
function test_status_transition_controller_simple() {
    try {
        // Load the status transition controller class
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-status-transition-controller.php';

        $status_controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

        $results = array();

        // Test 1: Singleton pattern
        $instance2 = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();
        $results['singleton'] = ($status_controller === $instance2) ? 'PASS' : 'FAIL';

        // Test 2: Status enums
        $status_enums = $status_controller->get_valid_status_enums();
        $results['status_enums'] = (is_array($status_enums) && count($status_enums) >= 6) ? 'PASS' : 'FAIL';

        // Test 3: Valid transition
        $valid_transition = $status_controller->validate_status_transition('pending', 'active');
        $results['valid_transition'] = (isset($valid_transition['valid']) && $valid_transition['valid']) ? 'PASS' : 'FAIL';

        // Test 4: Invalid transition (revoked is terminal)
        $invalid_transition = $status_controller->validate_status_transition('revoked', 'active');
        $results['invalid_transition'] = (isset($invalid_transition['valid']) && !$invalid_transition['valid']) ? 'PASS' : 'FAIL';

        // Test 5: Status descriptions
        $active_desc = $status_controller->get_status_description('active');
        $results['status_descriptions'] = (!empty($active_desc) && $active_desc !== 'Trạng thái không xác định') ? 'PASS' : 'FAIL';

        // Test 6: Status categories
        $active_category = $status_controller->get_status_category('active');
        $results['status_categories'] = ($active_category === 'operational') ? 'PASS' : 'FAIL';

        // Test 7: Allowed transitions
        $pending_transitions = $status_controller->get_allowed_status_transitions('pending');
        $results['allowed_transitions'] = (is_array($pending_transitions) && in_array('active', $pending_transitions)) ? 'PASS' : 'FAIL';

        return array(
            'success' => true,
            'tests' => $results,
            'passed' => count(array_filter($results, function($r) { return $r === 'PASS'; })),
            'total' => count($results),
            'timestamp' => current_time('mysql')
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => current_time('mysql')
        );
    }
}

// Run test if accessed directly
if (!defined('DOING_AJAX') && !defined('WP_CLI')) {
    $result = test_status_transition_controller_simple();
    echo "Step 5.1.4 Status Transition Controller Test Results:\n";
    echo "===================================================\n";
    if ($result['success']) {
        echo "Status: SUCCESS\n";
        echo "Tests Passed: {$result['passed']}/{$result['total']}\n";
        echo "Details:\n";
        foreach ($result['tests'] as $test => $status) {
            echo "  - {$test}: {$status}\n";
        }
    } else {
        echo "Status: ERROR\n";
        echo "Error: {$result['error']}\n";
    }
    echo "Timestamp: {$result['timestamp']}\n";
}
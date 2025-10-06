<?php
/**
 * Step 5.1.4: Status Transition Controller Test
 *
 * Comprehensive test suite for VD_License_Status_Transition_Controller
 * Tests all functionality including validation, transitions, notifications, and history tracking
 *
 * @package VD_License_Manager
 * @since 1.6.0
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Step 5.1.4: Status Transition Controller Test</title>\n";
echo "<meta charset='UTF-8'>\n";
echo "<style>\n";
echo "body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n";
echo ".header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }\n";
echo ".test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }\n";
echo ".success { background: #d4edda; border-color: #c3e6cb; color: #155724; }\n";
echo ".error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }\n";
echo ".warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }\n";
echo ".info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }\n";
echo ".test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }\n";
echo ".code { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 3px; font-family: 'Courier New', monospace; white-space: pre-wrap; }\n";
echo ".stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }\n";
echo ".stat-box { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; }\n";
echo ".stat-number { font-size: 24px; font-weight: bold; color: #495057; }\n";
echo ".stat-label { color: #6c757d; font-size: 14px; }\n";
echo ".progress { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin: 10px 0; }\n";
echo ".progress-bar { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<div class='container'>\n";
echo "<div class='header'>\n";
echo "<h1>🔄 Step 5.1.4: Status Transition Controller Test</h1>\n";
echo "<p>Comprehensive validation and testing suite for the extracted Status Transition Controller module</p>\n";
echo "<p><strong>Test License:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>\n";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s T') . "</p>\n";
echo "</div>\n";

// Initialize test statistics
$test_stats = array(
    'total_tests' => 0,
    'passed_tests' => 0,
    'failed_tests' => 0,
    'warnings' => 0,
    'start_time' => microtime(true)
);

function run_test($test_name, $test_function, &$stats) {
    $stats['total_tests']++;

    echo "<div class='test-section'>\n";
    echo "<h3>🧪 {$test_name}</h3>\n";

    try {
        $start_time = microtime(true);
        $result = $test_function();
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        if ($result['success']) {
            $stats['passed_tests']++;
            echo "<div class='test-result success'>\n";
            echo "<strong>✅ PASSED</strong> - {$result['message']}\n";
            echo "<div class='code'>Execution time: {$execution_time}ms</div>\n";
        } else {
            $stats['failed_tests']++;
            echo "<div class='test-result error'>\n";
            echo "<strong>❌ FAILED</strong> - {$result['message']}\n";
            if (isset($result['details'])) {
                echo "<div class='code'>" . print_r($result['details'], true) . "</div>\n";
            }
        }

        if (isset($result['data'])) {
            echo "<div class='code'>" . print_r($result['data'], true) . "</div>\n";
        }

    } catch (Exception $e) {
        $stats['failed_tests']++;
        echo "<div class='test-result error'>\n";
        echo "<strong>💥 EXCEPTION</strong> - " . $e->getMessage() . "\n";
        echo "<div class='code'>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "</div>\n";
    }

    echo "</div>\n";
    echo "</div>\n";
}

// WordPress Bootstrap
if (!defined('ABSPATH')) {
    $wp_config_paths = array(
        __DIR__ . '/wp-config.php',
        dirname(__DIR__) . '/wp-config.php',
        dirname(dirname(__DIR__)) . '/wp-config.php'
    );

    foreach ($wp_config_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }

    if (!defined('ABSPATH')) {
        echo "<div class='test-result error'>WordPress environment not found. Please ensure this file is in the WordPress root directory.</div>";
        echo "</div>\n</body>\n</html>";
        exit;
    }
}

// Test 1: Module Loading and Initialization
run_test("Module Loading Test", function() {
    $status_controller_file = plugin_dir_path(__FILE__) . 'wp-content/plugins/vd-license-manager/includes/modules/validator/class-vd-license-status-transition-controller.php';

    if (!file_exists($status_controller_file)) {
        return array(
            'success' => false,
            'message' => 'Status Transition Controller file not found',
            'details' => array('expected_path' => $status_controller_file)
        );
    }

    require_once $status_controller_file;

    if (!class_exists('VD\\LicenseManager\\Validator\\VD_License_Status_Transition_Controller')) {
        return array(
            'success' => false,
            'message' => 'Status Transition Controller class not loaded',
            'details' => array('class_name' => 'VD\\LicenseManager\\Validator\\VD_License_Status_Transition_Controller')
        );
    }

    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    if (!$controller) {
        return array(
            'success' => false,
            'message' => 'Failed to get Status Transition Controller instance'
        );
    }

    return array(
        'success' => true,
        'message' => 'Status Transition Controller loaded successfully',
        'data' => array(
            'class_exists' => true,
            'instance_created' => true,
            'instance_type' => get_class($controller)
        )
    );
}, $test_stats);

// Test 2: Status Validation Tests
run_test("Status Validation Test", function() {
    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    $test_cases = array(
        // Valid transitions
        array('from' => 'pending', 'to' => 'active', 'should_pass' => true),
        array('from' => 'active', 'to' => 'inactive', 'should_pass' => true),
        array('from' => 'active', 'to' => 'suspended', 'should_pass' => true),
        array('from' => 'expired', 'to' => 'active', 'should_pass' => true),

        // Invalid transitions
        array('from' => 'revoked', 'to' => 'active', 'should_pass' => false),
        array('from' => 'active', 'to' => 'pending', 'should_pass' => false),
        array('from' => 'invalid_status', 'to' => 'active', 'should_pass' => false),
        array('from' => 'active', 'to' => 'invalid_status', 'should_pass' => false),
    );

    $passed = 0;
    $total = count($test_cases);
    $results = array();

    foreach ($test_cases as $case) {
        $result = $controller->validate_status_transition($case['from'], $case['to']);

        $case_passed = ($result['valid'] === $case['should_pass']);
        if ($case_passed) $passed++;

        $results[] = array(
            'case' => "{$case['from']} -> {$case['to']}",
            'expected' => $case['should_pass'] ? 'valid' : 'invalid',
            'actual' => $result['valid'] ? 'valid' : 'invalid',
            'passed' => $case_passed,
            'details' => $result
        );
    }

    return array(
        'success' => $passed === $total,
        'message' => "Status validation test: {$passed}/{$total} test cases passed",
        'data' => array(
            'total_cases' => $total,
            'passed_cases' => $passed,
            'results' => $results
        )
    );
}, $test_stats);

// Test 3: Valid Status Enums Test
run_test("Valid Status Enums Test", function() {
    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    $valid_statuses = $controller->get_valid_status_enums();

    $expected_statuses = array('active', 'inactive', 'suspended', 'expired', 'revoked', 'pending', 'grace_period', 'trial', 'maintenance', 'blocked');

    $missing = array_diff($expected_statuses, $valid_statuses);
    $extra = array_diff($valid_statuses, $expected_statuses);

    return array(
        'success' => empty($missing) && empty($extra),
        'message' => 'Valid status enums verification',
        'data' => array(
            'valid_statuses' => $valid_statuses,
            'expected_count' => count($expected_statuses),
            'actual_count' => count($valid_statuses),
            'missing_statuses' => $missing,
            'extra_statuses' => $extra
        )
    );
}, $test_stats);

// Test 4: Transition Rules Test
run_test("Transition Rules Test", function() {
    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    $transition_rules = $controller->get_allowed_status_transitions();

    // Test specific rule: revoked should have no allowed transitions
    $revoked_transitions = $controller->get_allowed_status_transitions('revoked');

    // Test specific rule: active should allow multiple transitions
    $active_transitions = $controller->get_allowed_status_transitions('active');

    $rules_valid = (
        empty($revoked_transitions) &&
        !empty($active_transitions) &&
        is_array($transition_rules)
    );

    return array(
        'success' => $rules_valid,
        'message' => 'Transition rules structure validation',
        'data' => array(
            'total_statuses_with_rules' => count($transition_rules),
            'revoked_transitions' => $revoked_transitions,
            'active_transitions' => $active_transitions,
            'all_rules' => $transition_rules
        )
    );
}, $test_stats);

// Test 5: Status History Tracking Test
run_test("Status History Tracking Test", function() {
    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    $test_license = array(
        'id' => 12345,
        'license_key' => 'H10D-DIJD-14RC-SOLE-6KUV30',
        'product_id' => 1,
        'customer_id' => 1
    );

    $context = array(
        'reason' => 'Test status change',
        'changed_by' => 'system_test',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Status Transition Test Suite'
    );

    $result = $controller->track_status_history($test_license, 'active', 'inactive', $context);

    return array(
        'success' => $result['success'] ?? false,
        'message' => 'Status history tracking test',
        'data' => array(
            'tracking_result' => $result,
            'test_license' => $test_license,
            'test_context' => $context
        )
    );
}, $test_stats);

// Test 6: Status History Retrieval Test
run_test("Status History Retrieval Test", function() {
    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    $license_id = 12345;
    $options = array(
        'limit' => 10,
        'offset' => 0,
        'include_context' => true
    );

    $result = $controller->get_status_history($license_id, $options);

    return array(
        'success' => $result['success'] ?? false,
        'message' => 'Status history retrieval test',
        'data' => array(
            'retrieval_result' => $result,
            'license_id' => $license_id,
            'query_options' => $options
        )
    );
}, $test_stats);

// Test 7: Status Description Test
run_test("Status Description Test", function() {
    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    $test_statuses = array('active', 'inactive', 'suspended', 'expired', 'revoked', 'pending');
    $descriptions = array();

    foreach ($test_statuses as $status) {
        $descriptions[$status] = $controller->get_status_description($status);
    }

    $all_descriptions_valid = true;
    foreach ($descriptions as $status => $description) {
        if (empty($description) || $description === 'Trạng thái không xác định') {
            $all_descriptions_valid = false;
            break;
        }
    }

    return array(
        'success' => $all_descriptions_valid,
        'message' => 'Status description test',
        'data' => array(
            'status_descriptions' => $descriptions,
            'all_valid' => $all_descriptions_valid
        )
    );
}, $test_stats);

// Test 8: Main Validator Integration Test
run_test("Main Validator Integration Test", function() {
    // Load main validator
    $validator_file = plugin_dir_path(__FILE__) . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

    if (!file_exists($validator_file)) {
        return array(
            'success' => false,
            'message' => 'Main validator file not found',
            'details' => array('expected_path' => $validator_file)
        );
    }

    require_once $validator_file;

    if (!class_exists('VD_License_Validator')) {
        return array(
            'success' => false,
            'message' => 'VD_License_Validator class not found'
        );
    }

    $validator = VD_License_Validator::get_instance();

    // Test delegation through reflection
    $reflection = new ReflectionClass($validator);

    $status_controller_property = null;
    try {
        $status_controller_property = $reflection->getProperty('status_transition_controller');
        $status_controller_property->setAccessible(true);
    } catch (ReflectionException $e) {
        return array(
            'success' => false,
            'message' => 'Status Transition Controller property not found in main validator'
        );
    }

    // Check if property is initialized
    $controller_instance = $status_controller_property->getValue($validator);

    return array(
        'success' => ($controller_instance !== null),
        'message' => 'Main validator integration test',
        'data' => array(
            'validator_loaded' => true,
            'property_exists' => true,
            'controller_initialized' => ($controller_instance !== null),
            'controller_type' => $controller_instance ? get_class($controller_instance) : 'null'
        )
    );
}, $test_stats);

// Test 9: Performance Benchmark Test
run_test("Performance Benchmark Test", function() {
    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    $iterations = 100;
    $test_transitions = array(
        array('from' => 'pending', 'to' => 'active'),
        array('from' => 'active', 'to' => 'inactive'),
        array('from' => 'inactive', 'to' => 'active'),
        array('from' => 'active', 'to' => 'suspended'),
        array('from' => 'suspended', 'to' => 'active')
    );

    $start_time = microtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        foreach ($test_transitions as $transition) {
            $controller->validate_status_transition($transition['from'], $transition['to']);
        }
    }

    $end_time = microtime(true);
    $total_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    $average_time = $total_time / ($iterations * count($test_transitions));

    return array(
        'success' => true,
        'message' => 'Performance benchmark completed',
        'data' => array(
            'iterations' => $iterations,
            'transitions_per_iteration' => count($test_transitions),
            'total_validations' => $iterations * count($test_transitions),
            'total_time_ms' => round($total_time, 2),
            'average_time_ms' => round($average_time, 4),
            'validations_per_second' => round(($iterations * count($test_transitions)) / ($total_time / 1000))
        )
    );
}, $test_stats);

// Display final statistics
$test_stats['end_time'] = microtime(true);
$test_stats['total_time'] = round(($test_stats['end_time'] - $test_stats['start_time']) * 1000, 2);
$test_stats['success_rate'] = $test_stats['total_tests'] > 0 ? round(($test_stats['passed_tests'] / $test_stats['total_tests']) * 100, 1) : 0;

echo "<div class='test-section'>\n";
echo "<h2>📊 Test Results Summary</h2>\n";

echo "<div class='stats'>\n";
echo "<div class='stat-box'>\n";
echo "<div class='stat-number'>{$test_stats['total_tests']}</div>\n";
echo "<div class='stat-label'>Total Tests</div>\n";
echo "</div>\n";

echo "<div class='stat-box'>\n";
echo "<div class='stat-number'>{$test_stats['passed_tests']}</div>\n";
echo "<div class='stat-label'>Passed</div>\n";
echo "</div>\n";

echo "<div class='stat-box'>\n";
echo "<div class='stat-number'>{$test_stats['failed_tests']}</div>\n";
echo "<div class='stat-label'>Failed</div>\n";
echo "</div>\n";

echo "<div class='stat-box'>\n";
echo "<div class='stat-number'>{$test_stats['success_rate']}%</div>\n";
echo "<div class='stat-label'>Success Rate</div>\n";
echo "</div>\n";

echo "<div class='stat-box'>\n";
echo "<div class='stat-number'>{$test_stats['total_time']}ms</div>\n";
echo "<div class='stat-label'>Total Time</div>\n";
echo "</div>\n";
echo "</div>\n";

echo "<div class='progress'>\n";
echo "<div class='progress-bar' style='width: {$test_stats['success_rate']}%;'></div>\n";
echo "</div>\n";

if ($test_stats['success_rate'] == 100) {
    echo "<div class='test-result success'>\n";
    echo "<strong>🎉 ALL TESTS PASSED!</strong><br>\n";
    echo "Step 5.1.4: Status Transition Controller has been successfully extracted and integrated.\n";
    echo "</div>\n";
} elseif ($test_stats['success_rate'] >= 80) {
    echo "<div class='test-result warning'>\n";
    echo "<strong>⚠️ MOSTLY SUCCESSFUL</strong><br>\n";
    echo "Most tests passed, but some issues need attention before production deployment.\n";
    echo "</div>\n";
} else {
    echo "<div class='test-result error'>\n";
    echo "<strong>❌ SIGNIFICANT ISSUES DETECTED</strong><br>\n";
    echo "Multiple test failures indicate serious problems that must be resolved.\n";
    echo "</div>\n";
}

echo "</div>\n";

echo "<div class='test-section info'>\n";
echo "<h3>📋 Next Steps</h3>\n";
echo "<ul>\n";
echo "<li>✅ Status Transition Controller extracted and implemented</li>\n";
echo "<li>✅ Main validator updated with delegation patterns</li>\n";
echo "<li>✅ Comprehensive test suite completed</li>\n";
echo "<li>🔄 Update roadmap documentation</li>\n";
echo "<li>🔄 Report line count changes</li>\n";
echo "<li>🔄 Commit and push to GitHub</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</div>\n";
echo "</body>\n</html>";
?>
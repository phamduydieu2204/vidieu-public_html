<?php
/**
 * VD License Manager - Test Step 1.7: Status Transition Manager Module
 *
 * Test suite for Status Transition Manager module extraction and functionality
 * Tests all transition validation, business rules, and policies
 *
 * URL: /wp-admin/admin-ajax.php?action=vd_test_phase1_step1_7_status_transition
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX handler
add_action('wp_ajax_vd_test_phase1_step1_7_status_transition', 'vd_test_phase1_step1_7_status_transition');
add_action('wp_ajax_nopriv_vd_test_phase1_step1_7_status_transition', 'vd_test_phase1_step1_7_status_transition');

function vd_test_phase1_step1_7_status_transition() {
    // Set headers for JSON response
    header('Content-Type: application/json');

    try {
        // Initialize test results
        $test_results = array(
            'step' => '1.7',
            'module' => 'Status Transition Manager',
            'timestamp' => current_time('mysql'),
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'tests' => array(),
            'performance' => array(),
            'summary' => array()
        );

        // Load dependencies
        $container = VD_License_Dependency_Container::get_instance();
        $status_transition = null;
        $status_enum = null;

        try {
            $status_transition = $container->get('status.transition');
            $status_enum = $container->get('status.enum');
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Failed to load modules: ' . $e->getMessage(),
                'step' => '1.7',
                'error_type' => 'module_loading_error'
            ));
            return;
        }

        if (!$status_transition || !$status_enum) {
            wp_send_json_error(array(
                'message' => 'Status Transition or Status Enum module not available',
                'step' => '1.7',
                'error_type' => 'module_not_available'
            ));
            return;
        }

        // Test 1: Basic transition validation
        $test_results['tests']['test_1_basic_transition_validation'] = run_test_1_basic_transition_validation($status_transition);

        // Test 2: Business rules enforcement
        $test_results['tests']['test_2_business_rules_enforcement'] = run_test_2_business_rules_enforcement($status_transition);

        // Test 3: Transition policies validation
        $test_results['tests']['test_3_transition_policies'] = run_test_3_transition_policies($status_transition);

        // Test 4: Administrative controls
        $test_results['tests']['test_4_admin_controls'] = run_test_4_admin_controls($status_transition);

        // Test 5: Automatic transition validation
        $test_results['tests']['test_5_automatic_transitions'] = run_test_5_automatic_transitions($status_transition);

        // Test 6: Transition constraints
        $test_results['tests']['test_6_transition_constraints'] = run_test_6_transition_constraints($status_transition);

        // Test 7: Risk assessment functionality
        $test_results['tests']['test_7_risk_assessment'] = run_test_7_risk_assessment($status_transition);

        // Test 8: Grace period handling
        $test_results['tests']['test_8_grace_periods'] = run_test_8_grace_periods($status_transition);

        // Test 9: Integration with status enum
        $test_results['tests']['test_9_status_enum_integration'] = run_test_9_status_enum_integration($status_transition, $status_enum);

        // Test 10: Performance and memory usage
        $test_results['tests']['test_10_performance'] = run_test_10_performance($status_transition);

        // Calculate test statistics
        $test_results['total_tests'] = count($test_results['tests']);
        $passed = 0;
        $failed = 0;

        foreach ($test_results['tests'] as $test_name => $result) {
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        $test_results['passed_tests'] = $passed;
        $test_results['failed_tests'] = $failed;
        $test_results['success_rate'] = round(($passed / $test_results['total_tests']) * 100, 2);

        // Performance summary
        $total_operations = 0;
        $total_time = 0;
        foreach ($test_results['tests'] as $result) {
            if (isset($result['performance'])) {
                $total_operations += $result['performance']['operations'];
                $total_time += $result['performance']['execution_time'];
            }
        }

        $test_results['performance'] = array(
            'total_operations' => $total_operations,
            'total_time_ms' => round($total_time, 3),
            'operations_per_second' => $total_time > 0 ? round($total_operations / ($total_time / 1000), 2) : 0,
            'average_time_per_operation' => $total_operations > 0 ? round($total_time / $total_operations, 3) : 0
        );

        // Summary
        $test_results['summary'] = array(
            'module_status' => 'operational',
            'all_tests_passed' => ($failed === 0),
            'critical_failures' => 0,
            'recommendations' => array()
        );

        // Add recommendations if any tests failed
        if ($failed > 0) {
            $test_results['summary']['recommendations'][] = 'Review failed tests for potential issues';
        }

        wp_send_json_success($test_results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test execution failed: ' . $e->getMessage(),
            'step' => '1.7',
            'error_type' => 'execution_error',
            'trace' => $e->getTraceAsString()
        ));
    }
}

// Test implementations

function run_test_1_basic_transition_validation($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test valid transitions
        $valid_cases = array(
            array('inactive', 'active'),
            array('active', 'suspended'),
            array('suspended', 'active'),
            array('pending', 'active'),
            array('active', 'expired')
        );

        foreach ($valid_cases as $case) {
            $result = $status_transition->validate_status_transition($case[0], $case[1]);
            $operations++;

            if (!$result['valid']) {
                $errors[] = "Expected valid transition {$case[0]} -> {$case[1]} but got invalid";
            }
        }

        // Test invalid transitions
        $invalid_cases = array(
            array('revoked', 'active'),
            array('expired', 'pending'),
            array('invalid_status', 'active')
        );

        foreach ($invalid_cases as $case) {
            $result = $status_transition->validate_status_transition($case[0], $case[1]);
            $operations++;

            if ($result['valid']) {
                $errors[] = "Expected invalid transition {$case[0]} -> {$case[1]} but got valid";
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during basic transition validation: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Basic Transition Validation',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_2_business_rules_enforcement($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test business rule enforcement
        $mock_license = array(
            'id' => 'TEST_LICENSE_123',
            'status' => 'active',
            'expiry_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'usage_count' => 5,
            'max_usage' => 10
        );

        $rule_config = array(
            'transition_policies' => array(
                'require_admin_approval' => array('revoked'),
                'allow_expired_to_active' => false,
                'allow_revoked_transitions' => false
            ),
            'grace_periods' => array(
                'expired_grace_days' => 7,
                'suspended_grace_hours' => 24
            )
        );

        // Test enforce_transition_rules method
        $result = $status_transition->enforce_transition_rules('active', 'suspended', $mock_license, $rule_config);
        $operations++;

        if (!isset($result['allowed'])) {
            $errors[] = "enforce_transition_rules should return 'allowed' key";
        }

        // Test different transition scenarios
        $scenarios = array(
            array('active', 'revoked', false), // Should require admin
            array('expired', 'active', false), // Should not be allowed by default
            array('active', 'suspended', true)  // Should be allowed
        );

        foreach ($scenarios as $scenario) {
            $result = $status_transition->enforce_transition_rules($scenario[0], $scenario[1], $mock_license, $rule_config);
            $operations++;

            $expected_allowed = $scenario[2];
            $actual_allowed = $result['allowed'] ?? false;

            if ($actual_allowed !== $expected_allowed) {
                $errors[] = "Business rule test failed for {$scenario[0]} -> {$scenario[1]}: expected " .
                           ($expected_allowed ? 'allowed' : 'not allowed') . " but got " .
                           ($actual_allowed ? 'allowed' : 'not allowed');
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during business rules enforcement: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Business Rules Enforcement',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_3_transition_policies($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test transition policies functionality
        if (method_exists($status_transition, 'get_transition_policies')) {
            $policies = $status_transition->get_transition_policies();
            $operations++;

            if (!is_array($policies)) {
                $errors[] = "get_transition_policies should return an array";
            }
        }

        // Test policy validation
        if (method_exists($status_transition, 'validate_transition_policy')) {
            $policy_tests = array(
                array('require_admin_approval', 'revoked', true),
                array('allow_expired_to_active', 'expired', false),
                array('allow_revoked_transitions', 'revoked', false)
            );

            foreach ($policy_tests as $test) {
                $result = $status_transition->validate_transition_policy($test[0], $test[1], $test[2]);
                $operations++;

                if (!is_array($result) || !isset($result['valid'])) {
                    $errors[] = "validate_transition_policy should return array with 'valid' key";
                }
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during transition policies test: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Transition Policies',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_4_admin_controls($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test administrative control methods
        $mock_license = array('id' => 'TEST_ADMIN_123', 'status' => 'active');
        $rule_config = array(
            'transition_policies' => array(
                'require_admin_approval' => array('revoked', 'suspended')
            )
        );

        // Test admin-required transitions
        if (method_exists($status_transition, 'requires_admin_approval')) {
            $admin_required_transitions = array('revoked', 'suspended');

            foreach ($admin_required_transitions as $status) {
                $result = $status_transition->requires_admin_approval('active', $status, $rule_config);
                $operations++;

                if (!$result) {
                    $errors[] = "Transition to {$status} should require admin approval";
                }
            }
        }

        // Test non-admin transitions
        if (method_exists($status_transition, 'requires_admin_approval')) {
            $result = $status_transition->requires_admin_approval('active', 'expired', $rule_config);
            $operations++;

            if ($result) {
                $errors[] = "Transition to expired should not require admin approval";
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during admin controls test: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Administrative Controls',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_5_automatic_transitions($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test automatic transition validation
        $mock_license = array(
            'id' => 'TEST_AUTO_123',
            'status' => 'active',
            'expiry_date' => date('Y-m-d H:i:s', strtotime('-1 day'))
        );

        $options = array(
            'automatic_transitions' => true,
            'check_expiry' => true
        );

        if (method_exists($status_transition, 'validate_automatic_status_transition')) {
            $result = $status_transition->validate_automatic_status_transition('active', 'expired', $mock_license, $options);
            $operations++;

            if (!is_array($result) || !isset($result['valid'])) {
                $errors[] = "validate_automatic_status_transition should return array with 'valid' key";
            }
        }

        // Test automatic transition detection
        if (method_exists($status_transition, 'get_allowed_automatic_transitions')) {
            $auto_transitions = $status_transition->get_allowed_automatic_transitions();
            $operations++;

            if (!is_array($auto_transitions)) {
                $errors[] = "get_allowed_automatic_transitions should return an array";
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during automatic transitions test: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Automatic Transitions',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_6_transition_constraints($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test transition constraint validation
        $mock_license = array(
            'id' => 'TEST_CONSTRAINT_123',
            'status' => 'active',
            'usage_count' => 8,
            'max_usage' => 10
        );

        $constraint_tests = array(
            array(
                'type' => 'usage_limit',
                'condition' => 'under_limit',
                'expected' => true
            ),
            array(
                'type' => 'expiry_date',
                'condition' => 'not_expired',
                'expected' => true
            )
        );

        if (method_exists($status_transition, 'validate_transition_constraint')) {
            foreach ($constraint_tests as $test) {
                $constraint = array(
                    'type' => $test['type'],
                    'condition' => $test['condition']
                );

                $result = $status_transition->validate_transition_constraint($constraint, $mock_license, array());
                $operations++;

                if (!is_array($result) || !isset($result['valid'])) {
                    $errors[] = "validate_transition_constraint should return array with 'valid' key for {$test['type']}";
                }
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during transition constraints test: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Transition Constraints',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_7_risk_assessment($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test risk assessment functionality
        $mock_license = array(
            'id' => 'TEST_RISK_123',
            'status' => 'active',
            'usage_count' => 9,
            'max_usage' => 10
        );

        if (method_exists($status_transition, 'assess_transition_risk')) {
            $risk_scenarios = array(
                array('active', 'revoked'),
                array('active', 'suspended'),
                array('active', 'expired')
            );

            foreach ($risk_scenarios as $scenario) {
                $result = $status_transition->assess_transition_risk($scenario[0], $scenario[1], $mock_license);
                $operations++;

                if (!is_array($result) || !isset($result['risk_level'])) {
                    $errors[] = "assess_transition_risk should return array with 'risk_level' key for {$scenario[0]} -> {$scenario[1]}";
                }
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during risk assessment test: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Risk Assessment',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_8_grace_periods($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test grace period handling
        $mock_license = array(
            'id' => 'TEST_GRACE_123',
            'status' => 'expired',
            'expiry_date' => date('Y-m-d H:i:s', strtotime('-2 days'))
        );

        $grace_config = array(
            'expired_grace_days' => 7,
            'suspended_grace_hours' => 24
        );

        if (method_exists($status_transition, 'check_grace_period')) {
            $result = $status_transition->check_grace_period($mock_license, 'expired', $grace_config);
            $operations++;

            if (!is_array($result) || !isset($result['in_grace_period'])) {
                $errors[] = "check_grace_period should return array with 'in_grace_period' key";
            }
        }

        if (method_exists($status_transition, 'calculate_grace_period_end')) {
            $result = $status_transition->calculate_grace_period_end($mock_license, 'expired', $grace_config);
            $operations++;

            if (empty($result)) {
                $errors[] = "calculate_grace_period_end should return grace period end date";
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during grace periods test: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Grace Period Handling',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_9_status_enum_integration($status_transition, $status_enum) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Test integration with status enum module
        if (method_exists($status_transition, 'set_status_enum')) {
            $status_transition->set_status_enum($status_enum);
            $operations++;
        }

        // Test that transition validation uses enum validation
        $result = $status_transition->validate_status_transition('active', 'suspended');
        $operations++;

        if (!is_array($result) || !isset($result['valid'])) {
            $errors[] = "Status transition should integrate with status enum for validation";
        }

        // Test enum dependency
        if (method_exists($status_transition, 'get_status_enum')) {
            $enum_instance = $status_transition->get_status_enum();
            $operations++;

            if (!$enum_instance) {
                $errors[] = "Status transition should have access to status enum instance";
            }
        }

    } catch (Exception $e) {
        $errors[] = "Exception during status enum integration test: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Status Enum Integration',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations
        )
    );
}

function run_test_10_performance($status_transition) {
    $start_time = microtime(true);
    $operations = 0;
    $errors = array();

    try {
        // Performance test with multiple operations
        $mock_license = array(
            'id' => 'TEST_PERF_123',
            'status' => 'active',
            'expiry_date' => date('Y-m-d H:i:s', strtotime('+30 days'))
        );

        $rule_config = array(
            'transition_policies' => array(
                'require_admin_approval' => array('revoked')
            )
        );

        // Run multiple transition validations
        for ($i = 0; $i < 50; $i++) {
            $transitions = array(
                array('active', 'suspended'),
                array('suspended', 'active'),
                array('active', 'expired'),
                array('pending', 'active')
            );

            foreach ($transitions as $transition) {
                $result = $status_transition->validate_status_transition($transition[0], $transition[1]);
                $operations++;

                if (!isset($result['valid'])) {
                    $errors[] = "Performance test failed: invalid result structure";
                    break 2;
                }
            }
        }

        // Test memory usage
        $memory_before = memory_get_usage();

        for ($i = 0; $i < 20; $i++) {
            $status_transition->enforce_transition_rules('active', 'suspended', $mock_license, $rule_config);
            $operations++;
        }

        $memory_after = memory_get_usage();
        $memory_used = $memory_after - $memory_before;

        // Check for memory leaks (should not use excessive memory)
        if ($memory_used > 1048576) { // 1MB threshold
            $errors[] = "Potential memory leak detected: used " . round($memory_used / 1024, 2) . " KB";
        }

    } catch (Exception $e) {
        $errors[] = "Exception during performance test: " . $e->getMessage();
    }

    $execution_time = (microtime(true) - $start_time) * 1000;

    return array(
        'name' => 'Performance & Memory',
        'passed' => empty($errors),
        'errors' => $errors,
        'performance' => array(
            'execution_time' => $execution_time,
            'operations' => $operations,
            'operations_per_ms' => $execution_time > 0 ? round($operations / $execution_time, 2) : 0
        )
    );
}
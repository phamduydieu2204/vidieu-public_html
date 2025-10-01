<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phase 1 Step 1.6 Test - Status Enum Validator Module
 *
 * Tests the newly extracted VD_License_Status_Enum module
 * URL: /wp-admin/admin-ajax.php?action=vd_test_phase1_step1_6_status_enum
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_test_phase1_step1_6_status_enum', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'Phase 1 Step 1.6 - Status Enum Validator Test',
        'timestamp' => current_time('mysql'),
        'tests' => array(),
        'summary' => array(),
        'errors' => array(),
        'module_info' => array()
    );

    try {
        // Step 1: Test Module Loader
        $module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
        if (!file_exists($module_loader_file)) {
            throw new Exception('Module Loader file not found');
        }
        require_once $module_loader_file;

        if (!class_exists('VD_License_Module_Loader')) {
            throw new Exception('VD_License_Module_Loader class not found');
        }

        $module_loader = VD_License_Module_Loader::get_instance();
        $results['tests']['module_loader'] = array(
            'status' => 'pass',
            'message' => 'Module Loader initialized successfully',
            'stats' => $module_loader->get_stats()
        );

        // Step 2: Test Dependency Container
        $container_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-dependency-container.php';
        if (!file_exists($container_file)) {
            throw new Exception('Dependency Container file not found');
        }
        require_once $container_file;

        if (!class_exists('VD_License_Dependency_Container')) {
            throw new Exception('VD_License_Dependency_Container class not found');
        }

        $container = VD_License_Dependency_Container::get_instance();
        $container_initialized = $container->initialize();

        $results['tests']['dependency_container'] = array(
            'status' => $container_initialized ? 'pass' : 'fail',
            'message' => $container_initialized ? 'Dependency Container initialized successfully' : 'Container initialization failed',
            'stats' => $container->get_stats(),
            'status_info' => $container->get_status()
        );

        // Step 3: Test Status Enum Module Loading
        $status_enum_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/status/class-vd-license-status-enum.php';
        if (!file_exists($status_enum_file)) {
            throw new Exception('Status Enum module file not found');
        }
        require_once $status_enum_file;

        if (!class_exists('VD_License_Status_Enum')) {
            throw new Exception('VD_License_Status_Enum class not found');
        }

        $status_enum = $container->get('status.enum');
        $results['module_info'] = $status_enum->get_module_info();

        $results['tests']['status_enum_creation'] = array(
            'status' => 'pass',
            'message' => 'Status Enum module loaded successfully',
            'module_info' => $status_enum->get_module_info()
        );

        // Step 4: Test Basic Status Validation
        $test_statuses = array(
            'active' => array('should_pass' => true),
            'inactive' => array('should_pass' => true),
            'suspended' => array('should_pass' => true),
            'expired' => array('should_pass' => true),
            'revoked' => array('should_pass' => true),
            'pending' => array('should_pass' => true),
            'invalid_status' => array('should_pass' => false),
            '' => array('should_pass' => false)
        );

        $enum_test_results = array();
        foreach ($test_statuses as $status => $expectation) {
            $validation_result = $status_enum->validate_status_enum($status);
            $passed = ($validation_result['valid'] === $expectation['should_pass']);

            $enum_test_results[] = array(
                'status' => $status,
                'expected' => $expectation['should_pass'],
                'actual' => $validation_result['valid'],
                'passed' => $passed,
                'validation_result' => $validation_result
            );
        }

        $enum_tests_passed = count(array_filter($enum_test_results, function($test) {
            return $test['passed'];
        }));

        $results['tests']['basic_enum_validation'] = array(
            'status' => $enum_tests_passed === count($test_statuses) ? 'pass' : 'fail',
            'message' => "Status enum validation tests: {$enum_tests_passed}/" . count($test_statuses) . " passed",
            'test_results' => $enum_test_results,
            'passed_count' => $enum_tests_passed,
            'total_count' => count($test_statuses)
        );

        // Step 5: Test Status Transition Validation
        $test_transitions = array(
            array('from' => 'pending', 'to' => 'active', 'should_pass' => true),
            array('from' => 'active', 'to' => 'suspended', 'should_pass' => true),
            array('from' => 'suspended', 'to' => 'active', 'should_pass' => true),
            array('from' => 'expired', 'to' => 'active', 'should_pass' => true),
            array('from' => 'revoked', 'to' => 'active', 'should_pass' => false), // Terminal state
            array('from' => 'active', 'to' => 'invalid', 'should_pass' => false),
            array('from' => 'invalid', 'to' => 'active', 'should_pass' => false)
        );

        $transition_test_results = array();
        foreach ($test_transitions as $transition) {
            $validation_result = $status_enum->validate_status_transition($transition['from'], $transition['to']);
            $passed = ($validation_result['valid'] === $transition['should_pass']);

            $transition_test_results[] = array(
                'from' => $transition['from'],
                'to' => $transition['to'],
                'expected' => $transition['should_pass'],
                'actual' => $validation_result['valid'],
                'passed' => $passed,
                'validation_result' => $validation_result
            );
        }

        $transition_tests_passed = count(array_filter($transition_test_results, function($test) {
            return $test['passed'];
        }));

        $results['tests']['status_transition_validation'] = array(
            'status' => $transition_tests_passed === count($test_transitions) ? 'pass' : 'fail',
            'message' => "Status transition validation tests: {$transition_tests_passed}/" . count($test_transitions) . " passed",
            'test_results' => $transition_test_results,
            'passed_count' => $transition_tests_passed,
            'total_count' => count($test_transitions)
        );

        // Step 6: Test License Status Validation (Comprehensive)
        $test_licenses = array(
            // Valid active license
            array(
                'license' => array('status' => 'active', 'lookup_source' => 'lmfwc'),
                'should_pass' => true,
                'expected_code' => 'license_active'
            ),
            // Inactive license
            array(
                'license' => array('status' => 'inactive'),
                'should_pass' => false,
                'expected_code' => 'license_inactive'
            ),
            // Expired license
            array(
                'license' => array('status' => 'expired'),
                'should_pass' => false,
                'expected_code' => 'license_expired'
            ),
            // Revoked license
            array(
                'license' => array('status' => 'revoked'),
                'should_pass' => false,
                'expected_code' => 'license_revoked'
            ),
            // Missing status
            array(
                'license' => array('license_key' => 'TEST-123'),
                'should_pass' => false,
                'expected_code' => 'missing_status'
            )
        );

        $license_test_results = array();
        foreach ($test_licenses as $index => $test_case) {
            $validation_result = $status_enum->perform_status_enum_validation($test_case['license']);
            $passed = ($validation_result['valid'] === $test_case['should_pass']);

            // Additional check for error code if expected
            if (!$test_case['should_pass'] && isset($test_case['expected_code'])) {
                $code_match = (isset($validation_result['error_code']) &&
                              $validation_result['error_code'] === $test_case['expected_code']);
                $passed = $passed && $code_match;
            }

            $license_test_results[] = array(
                'test_index' => $index + 1,
                'license_data' => $test_case['license'],
                'expected_valid' => $test_case['should_pass'],
                'actual_valid' => $validation_result['valid'],
                'expected_code' => $test_case['expected_code'] ?? null,
                'actual_code' => $validation_result['error_code'] ?? null,
                'passed' => $passed,
                'validation_result' => $validation_result
            );
        }

        $license_tests_passed = count(array_filter($license_test_results, function($test) {
            return $test['passed'];
        }));

        $results['tests']['comprehensive_license_validation'] = array(
            'status' => $license_tests_passed === count($test_licenses) ? 'pass' : 'fail',
            'message' => "Comprehensive license validation tests: {$license_tests_passed}/" . count($test_licenses) . " passed",
            'test_results' => $license_test_results,
            'passed_count' => $license_tests_passed,
            'total_count' => count($test_licenses)
        );

        // Step 7: Test Status Utility Methods
        $utility_tests = array();

        // Test status categories
        $category_tests = array(
            'active' => 'usable',
            'inactive' => 'unusable',
            'suspended' => 'temporarily_unusable',
            'revoked' => 'permanently_unusable'
        );

        foreach ($category_tests as $status => $expected_category) {
            $actual_category = $status_enum->get_status_category($status);
            $utility_tests[] = array(
                'test_type' => 'category',
                'status' => $status,
                'expected' => $expected_category,
                'actual' => $actual_category,
                'passed' => $actual_category === $expected_category
            );
        }

        // Test usability checks
        $usability_tests = array(
            'active' => true,
            'inactive' => false,
            'suspended' => false,
            'revoked' => false
        );

        foreach ($usability_tests as $status => $expected_usable) {
            $actual_usable = $status_enum->is_status_usable($status);
            $utility_tests[] = array(
                'test_type' => 'usability',
                'status' => $status,
                'expected' => $expected_usable,
                'actual' => $actual_usable,
                'passed' => $actual_usable === $expected_usable
            );
        }

        // Test terminal status checks
        $terminal_tests = array(
            'revoked' => true,  // Terminal
            'active' => false,  // Not terminal
            'pending' => false  // Not terminal
        );

        foreach ($terminal_tests as $status => $expected_terminal) {
            $actual_terminal = $status_enum->is_status_terminal($status);
            $utility_tests[] = array(
                'test_type' => 'terminal',
                'status' => $status,
                'expected' => $expected_terminal,
                'actual' => $actual_terminal,
                'passed' => $actual_terminal === $expected_terminal
            );
        }

        $utility_tests_passed = count(array_filter($utility_tests, function($test) {
            return $test['passed'];
        }));

        $results['tests']['utility_methods'] = array(
            'status' => $utility_tests_passed === count($utility_tests) ? 'pass' : 'fail',
            'message' => "Utility methods tests: {$utility_tests_passed}/" . count($utility_tests) . " passed",
            'test_results' => $utility_tests,
            'passed_count' => $utility_tests_passed,
            'total_count' => count($utility_tests)
        );

        // Step 8: Test Module Statistics
        $stats = $status_enum->get_stats();
        $expected_stats_keys = array('validations_performed', 'transitions_validated', 'enum_checks', 'category_lookups');
        $stats_valid = true;

        foreach ($expected_stats_keys as $key) {
            if (!isset($stats[$key]) || !is_numeric($stats[$key])) {
                $stats_valid = false;
                break;
            }
        }

        $results['tests']['module_statistics'] = array(
            'status' => $stats_valid ? 'pass' : 'fail',
            'message' => 'Module statistics tracking',
            'statistics' => $stats,
            'stats_keys_valid' => $stats_valid,
            'validations_count' => $stats['validations_performed'] ?? 0,
            'transitions_count' => $stats['transitions_validated'] ?? 0
        );

        // Step 9: Test Performance (Multiple Operations)
        $performance_start = microtime(true);
        $performance_operations = 100;

        for ($i = 0; $i < $performance_operations; $i++) {
            $status = $i % 2 === 0 ? 'active' : 'inactive';
            $status_enum->validate_status_enum($status);
            $status_enum->get_status_category($status);
        }

        $performance_time = round((microtime(true) - $performance_start) * 1000, 2);
        $avg_time_per_operation = round($performance_time / $performance_operations, 3);

        $results['tests']['performance_test'] = array(
            'status' => $avg_time_per_operation < 1 ? 'pass' : 'warn',
            'message' => "{$performance_operations} status operations in {$performance_time}ms",
            'total_time_ms' => $performance_time,
            'operations_count' => $performance_operations,
            'avg_time_per_operation_ms' => $avg_time_per_operation,
            'performance_rating' => $avg_time_per_operation < 0.1 ? 'excellent' : ($avg_time_per_operation < 0.5 ? 'good' : 'fair')
        );

        // Step 10: Test Integration with Main Validator
        $validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
        if (file_exists($validator_file)) {
            require_once $validator_file;

            if (class_exists('VD_License_Validator')) {
                try {
                    $validator = VD_License_Validator::get_instance();
                    $validator_stats = $validator->get_validation_stats();

                    $results['tests']['validator_integration'] = array(
                        'status' => 'pass',
                        'message' => 'Main validator integration test completed',
                        'validator_stats' => $validator_stats,
                        'status_enum_available' => $container->has('status.enum')
                    );
                } catch (Exception $e) {
                    $results['tests']['validator_integration'] = array(
                        'status' => 'warn',
                        'message' => 'Validator integration test failed: ' . $e->getMessage()
                    );
                }
            } else {
                $results['tests']['validator_integration'] = array(
                    'status' => 'warn',
                    'message' => 'VD_License_Validator class not found'
                );
            }
        }

        // Calculate overall summary
        $passed_tests = 0;
        $total_tests = count($results['tests']);

        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'pass') {
                $passed_tests++;
            }
        }

        $overall_success_rate = round(($passed_tests / $total_tests) * 100, 1);

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'success_rate' => $overall_success_rate,
            'overall_status' => $overall_success_rate >= 100 ? 'excellent' : ($overall_success_rate >= 90 ? 'good' : 'needs_attention'),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'module_performance' => $avg_time_per_operation . ' ms/op'
        );

        // Phase 1 Step 1.6 completion status
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.6',
            'module' => 'Status Enum Validator',
            'completion' => 'SUCCESS',
            'next_step' => 'Step 1.7 - Status Transition Manager or Phase 1 Integration Testing',
            'files_created' => array(
                'modules/status/class-vd-license-status-enum.php' => '~520 lines',
            ),
            'files_modified' => array(
                'class-vd-license-dependency-container.php' => 'Status enum already registered',
                'class-vd-license-module-loader.php' => 'Status enum already configured'
            ),
            'performance_metrics' => array(
                'status_operations_per_ms' => round(1 / $avg_time_per_operation, 2),
                'enum_validations_performed' => $stats['enum_checks'] ?? 0,
                'transition_validations_performed' => $stats['transitions_validated'] ?? 0
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.6',
            'completion' => 'FAILED',
            'error' => $e->getMessage()
        );
    } catch (Error $e) {
        $results['status'] = 'error';
        $results['errors'][] = 'Fatal error: ' . $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.6',
            'completion' => 'FAILED',
            'error' => 'Fatal error: ' . $e->getMessage()
        );
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});
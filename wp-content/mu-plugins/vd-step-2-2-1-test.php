<?php
/**
 * Step 2.2.1 Expiry Core Module Test - AJAX Endpoint
 *
 * Test endpoint: /wp-admin/admin-ajax.php?action=vd_test_step_2_2_1
 *
 * Tests the VD_License_Rule_Expiry_Core module functionality
 * Uses AJAX approach for systematic testing with JSON output
 *
 * @package VD_License_Manager
 * @subpackage Testing
 * @since Step 2.2.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_2_2_1', 'vd_test_step_2_2_1_ajax_handler');
add_action('wp_ajax_nopriv_vd_test_step_2_2_1', 'vd_test_step_2_2_1_ajax_handler');

function vd_test_step_2_2_1_ajax_handler() {
    header('Content-Type: application/json; charset=utf-8');

    $start_time = microtime(true);
    $test_results = array();
    $overall_status = 'success';

    try {
        // Test 1: Module Loading and Basic Information
        $test_results['test_1_module_loading'] = test_expiry_core_module_loading();

        // Test 2: Core Expiry Validation Functions
        $test_results['test_2_expiry_validation'] = test_expiry_core_validation_functions();

        // Test 3: Warning Threshold Configuration
        $test_results['test_3_warning_thresholds'] = test_expiry_core_warning_thresholds();

        // Test 4: License Status Update
        $test_results['test_4_status_updates'] = test_expiry_core_status_updates();

        // Test 5: Statistics and Performance
        $test_results['test_5_statistics'] = test_expiry_core_statistics();

        // Test 6: Error Handling and Edge Cases
        $test_results['test_6_error_handling'] = test_expiry_core_error_handling();

        // Test 7: Integration with Status Business Module
        $test_results['test_7_integration'] = test_expiry_core_status_business_integration();

        // Test 8: Comprehensive Analysis Function
        $test_results['test_8_analysis'] = test_expiry_core_comprehensive_analysis();

        // Calculate overall success rate
        $success_count = 0;
        $total_tests = count($test_results);

        foreach ($test_results as $test_result) {
            if ($test_result['success']) {
                $success_count++;
            } else {
                $overall_status = 'partial_failure';
            }
        }

        $success_rate = ($success_count / $total_tests) * 100;

        if ($success_rate < 70) {
            $overall_status = 'failure';
        }

        // Final response
        $response = array(
            'status' => $overall_status,
            'message' => sprintf('✅ Step 2.2.1 Expiry Core Module Test - %d/%d tests passed (%.1f%%)',
                                $success_count, $total_tests, $success_rate),
            'module' => 'VD_License_Rule_Expiry_Core',
            'step' => '2.2.1',
            'timestamp' => current_time('mysql'),
            'execution_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms',
            'test_results' => $test_results,
            'summary' => array(
                'tests_passed' => $success_count,
                'tests_total' => $total_tests,
                'success_rate' => $success_rate . '%',
                'overall_status' => $overall_status
            )
        );

    } catch (Exception $e) {
        $response = array(
            'status' => 'error',
            'message' => '❌ Test execution failed: ' . $e->getMessage(),
            'error_details' => array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ),
            'timestamp' => current_time('mysql')
        );
    }

    echo wp_json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
}

/**
 * Test 1: Module Loading and Basic Information
 */
function test_expiry_core_module_loading() {
    try {
        // Load the module through dependency container
        require_once ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-dependency-container.php';
        require_once ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';

        $container = VD_License_Dependency_Container::get_instance();
        $container->initialize();

        $expiry_core = $container->get('rules.expiry_core');

        if (!$expiry_core) {
            return array(
                'success' => false,
                'message' => 'Failed to load Expiry Core module',
                'details' => 'Container returned null'
            );
        }

        // Test module info
        $module_info = $expiry_core->get_module_info();

        $expected_functions = array(
            'validate_license_expiry_date',
            'update_expired_license_status',
            'get_expiry_warning_threshold',
            'calculate_days_until_expiry',
            'is_lifetime_license',
            'get_expiry_analysis'
        );

        $missing_functions = array_diff($expected_functions, $module_info['functions']);

        if (!empty($missing_functions)) {
            return array(
                'success' => false,
                'message' => 'Missing expected functions',
                'details' => array(
                    'missing' => $missing_functions,
                    'available' => $module_info['functions']
                )
            );
        }

        return array(
            'success' => true,
            'message' => 'Module loaded successfully',
            'details' => array(
                'module_name' => $module_info['name'],
                'version' => $module_info['version'],
                'functions_count' => count($module_info['functions']),
                'dependencies' => $module_info['dependencies']
            )
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Module loading error: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        );
    }
}

/**
 * Test 2: Core Expiry Validation Functions
 */
function test_expiry_core_validation_functions() {
    try {
        $container = VD_License_Dependency_Container::get_instance();
        $expiry_core = $container->get('rules.expiry_core');

        if (!$expiry_core) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Test active license (valid)
        $active_license = array(
            'id' => 1,
            'license_key' => 'TEST-ACTIVE-LICENSE',
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'table_name' => 'wp_lmfwc_licenses'
        );

        $active_result = $expiry_core->validate_license_expiry_date($active_license);

        // Test expired license
        $expired_license = array(
            'id' => 2,
            'license_key' => 'TEST-EXPIRED-LICENSE',
            'status' => 'expired',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'table_name' => 'wp_lmfwc_licenses'
        );

        $expired_result = $expiry_core->validate_license_expiry_date($expired_license);

        // Test lifetime license
        $lifetime_license = array(
            'id' => 3,
            'license_key' => 'TEST-LIFETIME-LICENSE',
            'status' => 'active',
            'expires_at' => null,
            'table_name' => 'wp_lmfwc_licenses'
        );

        $lifetime_result = $expiry_core->validate_license_expiry_date($lifetime_license);

        // Test warning threshold (expires in 3 days, warning threshold is 7)
        $warning_license = array(
            'id' => 4,
            'license_key' => 'TEST-WARNING-LICENSE',
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'table_name' => 'wp_lmfwc_licenses'
        );

        $warning_result = $expiry_core->validate_license_expiry_date($warning_license);

        $validations = array(
            'active_valid' => $active_result['valid'] === true,
            'expired_invalid' => $expired_result['valid'] === false,
            'lifetime_valid' => $lifetime_result['valid'] === true && $lifetime_result['is_lifetime'] === true,
            'warning_triggered' => $warning_result['valid'] === true && $warning_result['expiry_warning'] === true
        );

        $success_count = array_sum($validations);
        $total_validations = count($validations);

        return array(
            'success' => $success_count === $total_validations,
            'message' => sprintf('Expiry validation tests: %d/%d passed', $success_count, $total_validations),
            'details' => array(
                'validations' => $validations,
                'active_days_until_expiry' => $active_result['days_until_expiry'],
                'expired_days_since' => $expired_result['expired_since_days'] ?? 'not_set',
                'warning_triggered' => $warning_result['expiry_warning']
            )
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Expiry validation test error: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        );
    }
}

/**
 * Test 3: Warning Threshold Configuration
 */
function test_expiry_core_warning_thresholds() {
    try {
        $container = VD_License_Dependency_Container::get_instance();
        $expiry_core = $container->get('rules.expiry_core');

        if (!$expiry_core) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Test default threshold
        $basic_license = array('id' => 1);
        $default_threshold = $expiry_core->get_expiry_warning_threshold($basic_license);

        // Test license-specific threshold
        $custom_license = array(
            'id' => 2,
            'expiry_warning_days' => 14
        );
        $custom_threshold = $expiry_core->get_expiry_warning_threshold($custom_license);

        // Test options override
        $options_license = array('id' => 3);
        $options_threshold = $expiry_core->get_expiry_warning_threshold($options_license, array('warning_threshold' => 21));

        $tests = array(
            'default_threshold_numeric' => is_numeric($default_threshold),
            'custom_threshold_correct' => $custom_threshold === 14,
            'options_override_correct' => $options_threshold === 21
        );

        $success_count = array_sum($tests);
        $total_tests = count($tests);

        return array(
            'success' => $success_count === $total_tests,
            'message' => sprintf('Warning threshold tests: %d/%d passed', $success_count, $total_tests),
            'details' => array(
                'tests' => $tests,
                'default_threshold' => $default_threshold,
                'custom_threshold' => $custom_threshold,
                'options_threshold' => $options_threshold
            )
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Warning threshold test error: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        );
    }
}

/**
 * Test 4: License Status Update (Mock Test)
 */
function test_expiry_core_status_updates() {
    try {
        $container = VD_License_Dependency_Container::get_instance();
        $expiry_core = $container->get('rules.expiry_core');

        if (!$expiry_core) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Test invalid license data
        $invalid_license = array('license_key' => 'INVALID');
        $invalid_result = $expiry_core->update_expired_license_status($invalid_license);

        // Test non-existent table
        $fake_license = array(
            'id' => 999,
            'table_name' => 'wp_nonexistent_table',
            'status' => 'active'
        );
        $fake_result = $expiry_core->update_expired_license_status($fake_license);

        $tests = array(
            'invalid_data_handled' => $invalid_result['success'] === false && $invalid_result['code'] === 'invalid_license_data',
            'fake_table_handled' => $fake_result['success'] === false && $fake_result['code'] === 'table_not_found'
        );

        $success_count = array_sum($tests);
        $total_tests = count($tests);

        return array(
            'success' => $success_count === $total_tests,
            'message' => sprintf('Status update tests: %d/%d passed', $success_count, $total_tests),
            'details' => array(
                'tests' => $tests,
                'invalid_error' => $invalid_result['error'],
                'fake_error' => $fake_result['error']
            )
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Status update test error: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        );
    }
}

/**
 * Test 5: Statistics and Performance
 */
function test_expiry_core_statistics() {
    try {
        $container = VD_License_Dependency_Container::get_instance();
        $expiry_core = $container->get('rules.expiry_core');

        if (!$expiry_core) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Get initial statistics
        $initial_stats = $expiry_core->get_statistics();

        // Perform some operations to update statistics
        $test_license = array(
            'id' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+10 days'))
        );

        $expiry_core->validate_license_expiry_date($test_license);
        $expiry_core->validate_license_expiry_date($test_license);

        // Get updated statistics
        $updated_stats = $expiry_core->get_statistics();

        $tests = array(
            'stats_structure_valid' => isset($initial_stats['validations_performed']),
            'validations_incremented' => $updated_stats['validations_performed'] > $initial_stats['validations_performed'],
            'stats_numeric' => is_numeric($updated_stats['validations_performed'])
        );

        $success_count = array_sum($tests);
        $total_tests = count($tests);

        return array(
            'success' => $success_count === $total_tests,
            'message' => sprintf('Statistics tests: %d/%d passed', $success_count, $total_tests),
            'details' => array(
                'tests' => $tests,
                'initial_validations' => $initial_stats['validations_performed'],
                'updated_validations' => $updated_stats['validations_performed'],
                'validation_increase' => $updated_stats['validations_performed'] - $initial_stats['validations_performed']
            )
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Statistics test error: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        );
    }
}

/**
 * Test 6: Error Handling and Edge Cases
 */
function test_expiry_core_error_handling() {
    try {
        $container = VD_License_Dependency_Container::get_instance();
        $expiry_core = $container->get('rules.expiry_core');

        if (!$expiry_core) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Test empty license data
        $empty_result = $expiry_core->validate_license_expiry_date(array());

        // Test malformed date
        $malformed_license = array(
            'id' => 1,
            'expires_at' => 'invalid-date-format'
        );
        $malformed_result = $expiry_core->validate_license_expiry_date($malformed_license);

        // Test null values
        $null_license = array(
            'id' => null,
            'expires_at' => null
        );
        $null_result = $expiry_core->validate_license_expiry_date($null_license);

        $tests = array(
            'empty_data_handled' => isset($empty_result['valid']),
            'malformed_date_handled' => isset($malformed_result['valid']),
            'null_values_handled' => isset($null_result['valid']) && $null_result['is_lifetime'] === true
        );

        $success_count = array_sum($tests);
        $total_tests = count($tests);

        return array(
            'success' => $success_count === $total_tests,
            'message' => sprintf('Error handling tests: %d/%d passed', $success_count, $total_tests),
            'details' => array(
                'tests' => $tests,
                'empty_valid' => $empty_result['valid'] ?? 'not_set',
                'malformed_valid' => $malformed_result['valid'] ?? 'not_set',
                'null_is_lifetime' => $null_result['is_lifetime'] ?? 'not_set'
            )
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error handling test error: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        );
    }
}

/**
 * Test 7: Integration with Status Business Module
 */
function test_expiry_core_status_business_integration() {
    try {
        $container = VD_License_Dependency_Container::get_instance();
        $expiry_core = $container->get('rules.expiry_core');
        $status_business = $container->get('status.business');

        if (!$expiry_core || !$status_business) {
            return array(
                'success' => false,
                'message' => 'Required modules not available',
                'details' => array(
                    'expiry_core' => $expiry_core ? 'loaded' : 'missing',
                    'status_business' => $status_business ? 'loaded' : 'missing'
                )
            );
        }

        // Test dependency injection
        $expiry_core->set_status_business($status_business);

        // Test module info shows dependency
        $module_info = $expiry_core->get_module_info();
        $has_dependency = in_array('VD_License_Status_Business', $module_info['dependencies']);

        $tests = array(
            'dependency_injection_works' => true, // No exception thrown
            'module_info_shows_dependency' => $has_dependency
        );

        $success_count = array_sum($tests);
        $total_tests = count($tests);

        return array(
            'success' => $success_count === $total_tests,
            'message' => sprintf('Integration tests: %d/%d passed', $success_count, $total_tests),
            'details' => array(
                'tests' => $tests,
                'dependencies' => $module_info['dependencies']
            )
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Integration test error: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        );
    }
}

/**
 * Test 8: Comprehensive Analysis Function
 */
function test_expiry_core_comprehensive_analysis() {
    try {
        $container = VD_License_Dependency_Container::get_instance();
        $expiry_core = $container->get('rules.expiry_core');

        if (!$expiry_core) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Test analysis for different license types
        $test_licenses = array(
            'active' => array(
                'id' => 1,
                'license_key' => 'TEST-ACTIVE',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ),
            'warning' => array(
                'id' => 2,
                'license_key' => 'TEST-WARNING',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+3 days'))
            ),
            'expired' => array(
                'id' => 3,
                'license_key' => 'TEST-EXPIRED',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ),
            'lifetime' => array(
                'id' => 4,
                'license_key' => 'TEST-LIFETIME',
                'expires_at' => null
            )
        );

        $analyses = array();
        foreach ($test_licenses as $type => $license) {
            $analyses[$type] = $expiry_core->get_expiry_analysis($license);
        }

        $tests = array(
            'active_no_action' => $analyses['active']['requires_action'] === false,
            'warning_action_required' => $analyses['warning']['requires_action'] === true,
            'expired_action_required' => $analyses['expired']['requires_action'] === true,
            'lifetime_no_action' => $analyses['lifetime']['requires_action'] === false,
            'analysis_structure_complete' => isset($analyses['active']['analysis_timestamp'])
        );

        $success_count = array_sum($tests);
        $total_tests = count($tests);

        return array(
            'success' => $success_count === $total_tests,
            'message' => sprintf('Analysis tests: %d/%d passed', $success_count, $total_tests),
            'details' => array(
                'tests' => $tests,
                'recommended_actions' => array(
                    'active' => $analyses['active']['recommended_action'],
                    'warning' => $analyses['warning']['recommended_action'],
                    'expired' => $analyses['expired']['recommended_action'],
                    'lifetime' => $analyses['lifetime']['recommended_action']
                )
            )
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Analysis test error: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        );
    }
}
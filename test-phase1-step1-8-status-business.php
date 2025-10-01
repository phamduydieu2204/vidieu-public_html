<?php
/**
 * VD License Manager Step 1.8 Status Business Logic Test
 *
 * AJAX endpoint for testing the extracted Status Business Logic module
 * Tests comprehensive business rules enforcement and status-specific rules
 *
 * Test URL: /wp-admin/admin-ajax.php?action=vd_test_phase1_step1_8_status_business
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX handler
add_action('wp_ajax_vd_test_phase1_step1_8_status_business', 'vd_test_phase1_step1_8_status_business');
add_action('wp_ajax_nopriv_vd_test_phase1_step1_8_status_business', 'vd_test_phase1_step1_8_status_business');

function vd_test_phase1_step1_8_status_business() {
    $start_time = microtime(true);
    $test_results = array();
    $passed_tests = 0;
    $total_tests = 0;

    try {
        // Initialize dependency container
        $container = VD_License_Dependency_Container::get_instance();

        // Get Status Business Logic module
        $status_business = $container->get('status.business');

        if (!$status_business) {
            wp_send_json_error(array(
                'message' => 'Status Business Logic module not loaded',
                'debug' => 'Module registration or loading failed'
            ));
            return;
        }

        // Test 1: Module Information and Dependencies
        $total_tests++;
        try {
            $module_info = $status_business->get_module_info();
            $status_enum = $status_business->get_status_enum();
            $status_transition = $status_business->get_status_transition();

            $test_results[] = array(
                'test' => 'Module Information & Dependencies',
                'status' => 'PASS',
                'details' => array(
                    'module_info' => $module_info,
                    'has_status_enum' => $status_enum !== null,
                    'has_status_transition' => $status_transition !== null,
                    'dependencies_satisfied' => ($status_enum !== null && $status_transition !== null)
                )
            );
            $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Module Information & Dependencies',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 2: Business Rule Configuration
        $total_tests++;
        try {
            $test_license = array(
                'id' => 'test-license-001',
                'status' => 'active',
                'expires_at' => date('Y-m-d H:i:s', current_time('timestamp') + (5 * 24 * 3600)) // 5 days from now
            );

            $rule_config = $status_business->get_business_rule_configuration($test_license);

            $expected_config_keys = array('grace_periods', 'escalation_rules', 'transition_policies', 'status_specific_rules');
            $has_all_keys = array_diff($expected_config_keys, array_keys($rule_config)) === array();

            $test_results[] = array(
                'test' => 'Business Rule Configuration',
                'status' => $has_all_keys ? 'PASS' : 'FAIL',
                'details' => array(
                    'rule_config' => $rule_config,
                    'has_all_required_keys' => $has_all_keys,
                    'missing_keys' => array_diff($expected_config_keys, array_keys($rule_config))
                )
            );
            if ($has_all_keys) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Business Rule Configuration',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 3: Active License Business Rules (Expiry Warning)
        $total_tests++;
        try {
            $active_license = array(
                'id' => 'active-license-001',
                'status' => 'active',
                'mapped_status' => 'active',
                'expires_at' => date('Y-m-d H:i:s', current_time('timestamp') + (3 * 24 * 3600)) // 3 days from now
            );

            $business_result = $status_business->enforce_business_rules($active_license);

            $test_results[] = array(
                'test' => 'Active License Business Rules (Expiry Warning)',
                'status' => $business_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $business_result
            );
            if ($business_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Active License Business Rules (Expiry Warning)',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 4: Expired License with Grace Period
        $total_tests++;
        try {
            $expired_license = array(
                'id' => 'expired-license-001',
                'status' => 'expired',
                'mapped_status' => 'expired',
                'expires_at' => date('Y-m-d H:i:s', current_time('timestamp') - (3 * 24 * 3600)) // 3 days ago
            );

            $business_result = $status_business->enforce_business_rules($expired_license);

            $test_results[] = array(
                'test' => 'Expired License with Grace Period',
                'status' => $business_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $business_result
            );
            if ($business_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Expired License with Grace Period',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 5: Suspended License Escalation Rules
        $total_tests++;
        try {
            $suspended_license = array(
                'id' => 'suspended-license-001',
                'status' => 'suspended',
                'mapped_status' => 'suspended',
                'last_status_change' => date('Y-m-d H:i:s', current_time('timestamp') - (10 * 24 * 3600)) // 10 days ago
            );

            $business_result = $status_business->enforce_business_rules($suspended_license);

            $test_results[] = array(
                'test' => 'Suspended License Escalation Rules',
                'status' => $business_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $business_result
            );
            if ($business_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Suspended License Escalation Rules',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 6: Pending License Timeout
        $total_tests++;
        try {
            $pending_license = array(
                'id' => 'pending-license-001',
                'status' => 'pending',
                'mapped_status' => 'pending',
                'created_at' => date('Y-m-d H:i:s', current_time('timestamp') - (35 * 24 * 3600)) // 35 days ago
            );

            $business_result = $status_business->enforce_business_rules($pending_license);

            $test_results[] = array(
                'test' => 'Pending License Timeout',
                'status' => $business_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $business_result
            );
            if ($business_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Pending License Timeout',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 7: Revoked License (Terminal State)
        $total_tests++;
        try {
            $revoked_license = array(
                'id' => 'revoked-license-001',
                'status' => 'revoked',
                'mapped_status' => 'revoked'
            );

            $business_result = $status_business->enforce_business_rules($revoked_license);

            $test_results[] = array(
                'test' => 'Revoked License (Terminal State)',
                'status' => $business_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $business_result
            );
            if ($business_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Revoked License (Terminal State)',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 8: Transition Rules Integration
        $total_tests++;
        try {
            $license_for_transition = array(
                'id' => 'transition-test-001',
                'status' => 'active',
                'mapped_status' => 'active'
            );

            $context = array(
                'from_status' => 'active',
                'to_status' => 'revoked'
            );

            $business_result = $status_business->enforce_business_rules($license_for_transition, $context);

            // Should fail because revoked transitions require admin approval
            $test_results[] = array(
                'test' => 'Transition Rules Integration (active -> revoked)',
                'status' => !$business_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $business_result,
                'expected' => 'Should fail - revoked transitions require admin approval'
            );
            if (!$business_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Transition Rules Integration (active -> revoked)',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 9: Grace Period Rules
        $total_tests++;
        try {
            $expired_license_grace = array(
                'id' => 'grace-test-001',
                'status' => 'expired',
                'mapped_status' => 'expired',
                'expires_at' => date('Y-m-d H:i:s', current_time('timestamp') - (2 * 24 * 3600)) // 2 days ago
            );

            $rule_config = $status_business->get_business_rule_configuration($expired_license_grace);
            $grace_result = $status_business->enforce_grace_period_rules($expired_license_grace, $rule_config, array());

            $test_results[] = array(
                'test' => 'Grace Period Rules',
                'status' => $grace_result['applicable'] ? 'PASS' : 'FAIL',
                'details' => $grace_result
            );
            if ($grace_result['applicable']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Grace Period Rules',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 10: Module Statistics
        $total_tests++;
        try {
            $stats = $status_business->get_stats();
            $required_stat_keys = array('rules_enforced', 'rules_violated', 'grace_periods_applied', 'escalations_triggered', 'transitions_blocked');
            $has_all_stat_keys = array_diff($required_stat_keys, array_keys($stats)) === array();

            $test_results[] = array(
                'test' => 'Module Statistics',
                'status' => $has_all_stat_keys ? 'PASS' : 'FAIL',
                'details' => array(
                    'stats' => $stats,
                    'has_all_required_keys' => $has_all_stat_keys
                )
            );
            if ($has_all_stat_keys) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Module Statistics',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Calculate success rate
        $success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0;
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        // Send response
        wp_send_json_success(array(
            'message' => sprintf('Step 1.8 Status Business Logic Tests Completed: %d/%d tests passed (%.2f%%)',
                               $passed_tests, $total_tests, $success_rate),
            'summary' => array(
                'total_tests' => $total_tests,
                'passed_tests' => $passed_tests,
                'failed_tests' => $total_tests - $passed_tests,
                'success_rate' => $success_rate,
                'execution_time_ms' => $execution_time,
                'module_version' => '1.5.0-rc.2',
                'test_timestamp' => current_time('mysql')
            ),
            'test_results' => $test_results,
            'container_status' => $container->get_status(),
            'module_stats' => $status_business->get_stats()
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test execution failed',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
        ));
    }
}
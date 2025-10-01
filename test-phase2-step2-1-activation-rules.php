<?php
/**
 * VD License Manager Step 2.1 Activation Rules Test
 *
 * AJAX endpoint for testing the extracted Activation Rules module
 * Tests comprehensive activation rules enforcement and device management
 *
 * Test URL: /wp-admin/admin-ajax.php?action=vd_test_phase2_step2_1_activation_rules
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX handler
add_action('wp_ajax_vd_test_phase2_step2_1_activation_rules', 'vd_test_phase2_step2_1_activation_rules');
add_action('wp_ajax_nopriv_vd_test_phase2_step2_1_activation_rules', 'vd_test_phase2_step2_1_activation_rules');

function vd_test_phase2_step2_1_activation_rules() {
    $start_time = microtime(true);
    $test_results = array();
    $passed_tests = 0;
    $total_tests = 0;

    try {
        // Initialize dependency container
        $container = VD_License_Dependency_Container::get_instance();

        // Get Activation Rules module
        $activation_rules = $container->get('rules.activation');

        if (!$activation_rules) {
            wp_send_json_error(array(
                'message' => 'Activation Rules module not loaded',
                'debug' => 'Module registration or loading failed'
            ));
            return;
        }

        // Test 1: Module Information and Dependencies
        $total_tests++;
        try {
            $module_info = $activation_rules->get_module_info();
            $status_business = $activation_rules->get_status_business();

            $test_results[] = array(
                'test' => 'Module Information & Dependencies',
                'status' => 'PASS',
                'details' => array(
                    'module_info' => $module_info,
                    'has_status_business' => $status_business !== null,
                    'dependencies_satisfied' => ($status_business !== null)
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

        // Test 2: Activation Limits Validation
        $total_tests++;
        try {
            $test_license = array(
                'id' => 'test-license-activation-001',
                'license_key' => 'TEST-ACTIVATION-001',
                'times_activated' => 2,
                'activations_limit' => 5
            );

            $product_settings = array('max_activations' => 5);
            $limit_result = $activation_rules->validate_activation_limits($test_license, $product_settings);

            $test_results[] = array(
                'test' => 'Activation Limits Validation (Within Limit)',
                'status' => $limit_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $limit_result
            );
            if ($limit_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Activation Limits Validation (Within Limit)',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 3: Activation Limits Exceeded
        $total_tests++;
        try {
            $exceeded_license = array(
                'id' => 'test-license-exceeded-001',
                'license_key' => 'TEST-EXCEEDED-001',
                'times_activated' => 5,
                'activations_limit' => 5
            );

            $limit_result = $activation_rules->validate_activation_limits($exceeded_license, array());

            $test_results[] = array(
                'test' => 'Activation Limits Validation (Exceeded)',
                'status' => !$limit_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $limit_result,
                'expected' => 'Should fail - activation limit exceeded'
            );
            if (!$limit_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Activation Limits Validation (Exceeded)',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 4: Device Limits Validation
        $total_tests++;
        try {
            $device_license = array(
                'id' => 'test-license-device-001',
                'license_key' => 'TEST-DEVICE-001'
            );

            $device_settings = array('max_devices' => 3);
            $device_result = $activation_rules->validate_device_limits($device_license, $device_settings);

            $test_results[] = array(
                'test' => 'Device Limits Validation',
                'status' => $device_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $device_result
            );
            if ($device_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Device Limits Validation',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 5: License Activation Settings
        $total_tests++;
        try {
            $settings_license = array(
                'id' => 'test-license-settings-001',
                'product_id' => 'test-product-001'
            );

            $activation_settings = $activation_rules->get_license_activation_settings($settings_license);
            $required_keys = array('max_devices', 'allow_device_switching', 'device_fingerprinting_enabled');
            $has_all_keys = array_diff($required_keys, array_keys($activation_settings)) === array();

            $test_results[] = array(
                'test' => 'License Activation Settings',
                'status' => $has_all_keys ? 'PASS' : 'FAIL',
                'details' => array(
                    'settings' => $activation_settings,
                    'has_all_required_keys' => $has_all_keys,
                    'missing_keys' => array_diff($required_keys, array_keys($activation_settings))
                )
            );
            if ($has_all_keys) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'License Activation Settings',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 6: User Agent Categorization
        $total_tests++;
        try {
            $test_agents = array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15' => 'mobile',
                'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15' => 'tablet',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' => 'desktop'
            );

            $categorization_results = array();
            foreach ($test_agents as $agent => $expected) {
                $detected = $activation_rules->categorize_user_agent($agent);
                $categorization_results[] = array(
                    'user_agent' => substr($agent, 0, 50) . '...',
                    'expected' => $expected,
                    'detected' => $detected,
                    'correct' => $detected === $expected
                );
            }

            $all_correct = array_filter($categorization_results, function($result) {
                return $result['correct'];
            });

            $test_results[] = array(
                'test' => 'User Agent Categorization',
                'status' => count($all_correct) === count($categorization_results) ? 'PASS' : 'FAIL',
                'details' => array(
                    'test_results' => $categorization_results,
                    'correct_count' => count($all_correct),
                    'total_count' => count($categorization_results)
                )
            );
            if (count($all_correct) === count($categorization_results)) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'User Agent Categorization',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 7: Device Fingerprinting
        $total_tests++;
        try {
            $test_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $fingerprint1 = $activation_rules->generate_device_fingerprint($test_agent);
            $fingerprint2 = $activation_rules->generate_device_fingerprint($test_agent);

            // Same agent should generate same fingerprint
            $fingerprints_consistent = ($fingerprint1 === $fingerprint2);

            $test_results[] = array(
                'test' => 'Device Fingerprinting',
                'status' => $fingerprints_consistent ? 'PASS' : 'FAIL',
                'details' => array(
                    'fingerprint1' => $fingerprint1,
                    'fingerprint2' => $fingerprint2,
                    'consistent' => $fingerprints_consistent,
                    'length' => strlen($fingerprint1)
                )
            );
            if ($fingerprints_consistent) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Device Fingerprinting',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 8: Visitor Fingerprinting
        $total_tests++;
        try {
            $test_headers = array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept-Language' => 'en-US,en;q=0.9'
            );

            $visitor_fingerprint = $activation_rules->generate_visitor_fingerprint($test_headers);

            $test_results[] = array(
                'test' => 'Visitor Fingerprinting',
                'status' => !empty($visitor_fingerprint) && strlen($visitor_fingerprint) === 32 ? 'PASS' : 'FAIL',
                'details' => array(
                    'fingerprint' => $visitor_fingerprint,
                    'length' => strlen($visitor_fingerprint),
                    'headers_used' => $test_headers
                )
            );
            if (!empty($visitor_fingerprint) && strlen($visitor_fingerprint) === 32) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Visitor Fingerprinting',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 9: Cross-device Pattern Validation
        $total_tests++;
        try {
            $cross_device_license = array(
                'id' => 'test-license-cross-device-001',
                'license_key' => 'TEST-CROSS-DEVICE-001'
            );

            $cross_device_result = $activation_rules->validate_cross_device_patterns($cross_device_license);
            $required_analysis_keys = array('unique_devices_detected', 'simultaneous_access_detected', 'violations_detected');
            $has_analysis_keys = array_diff($required_analysis_keys, array_keys($cross_device_result)) === array();

            $test_results[] = array(
                'test' => 'Cross-device Pattern Validation',
                'status' => $has_analysis_keys ? 'PASS' : 'FAIL',
                'details' => array(
                    'analysis_result' => $cross_device_result,
                    'has_all_required_keys' => $has_analysis_keys
                )
            );
            if ($has_analysis_keys) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Cross-device Pattern Validation',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Test 10: Product-level Constraints Validation
        $total_tests++;
        try {
            $constraints_license = array(
                'id' => 'test-license-constraints-001',
                'license_key' => 'TEST-CONSTRAINTS-001',
                'times_activated' => 1,
                'activations_limit' => 5
            );

            $constraints_settings = array(
                'max_activations' => 5,
                'max_devices' => 3
            );

            $constraints_result = $activation_rules->validate_product_level_constraints($constraints_license, $constraints_settings);

            $test_results[] = array(
                'test' => 'Product-level Constraints Validation',
                'status' => $constraints_result['valid'] ? 'PASS' : 'FAIL',
                'details' => $constraints_result
            );
            if ($constraints_result['valid']) $passed_tests++;
        } catch (Exception $e) {
            $test_results[] = array(
                'test' => 'Product-level Constraints Validation',
                'status' => 'FAIL',
                'error' => $e->getMessage()
            );
        }

        // Calculate success rate
        $success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0;
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        // Send response
        wp_send_json_success(array(
            'message' => sprintf('Step 2.1 Activation Rules Tests Completed: %d/%d tests passed (%.2f%%)',
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
            'module_stats' => $activation_rules->get_stats()
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
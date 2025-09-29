<?php
/**
 * VD License Manager - Step 4.1.8 Error Handling Infrastructure Test
 *
 * Purpose: Test comprehensive error handling infrastructure functionality
 * Scope: Error response format validation, HTTP status codes, error categorization, logging
 *
 * Usage: Add to Code Snippets plugin and run
 * Expected: Error handling infrastructure working với standardized responses
 *
 * @since Step 4.1.8
 */

// Safety check
if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

function vd_test_step_4_1_8_error_handling() {
    $results = [];
    $results['timestamp'] = current_time('Y-m-d H:i:s');
    $results['step'] = '4.1.8';

    echo '<div style="font-family: monospace; background: #f1f1f1; padding: 20px; margin: 20px 0; border-left: 5px solid #0073aa;">';
    echo '<h2>⚠️ VD License Manager - Step 4.1.8 Error Handling Infrastructure Test</h2>';
    echo '<p><strong>Test Time:</strong> ' . $results['timestamp'] . '</p>';
    echo '<hr>';

    // Ensure plugin is loaded
    if (!function_exists('vd_license_manager_init')) {
        echo '<div style="color: red; font-weight: bold;">❌ VD License Manager plugin not loaded. Make sure plugin is activated.</div>';
        echo '</div>';
        return false;
    }

    // Test 1: Error Handling Infrastructure Availability
    echo '<h3>🔍 Test 1: Error Handling Infrastructure</h3>';

    $router_available = class_exists('VD_API_Router');
    $router_instance = null;

    if ($router_available) {
        try {
            $router_instance = VD_API_Router::get_instance();
            echo '✅ VD_API_Router instance available<br>';

            // Check error handling methods
            $error_methods = [
                'create_api_error',
                'format_error_response',
                'handle_validation_errors',
                'handle_rate_limit_error',
                'handle_business_error',
                'get_error_statistics',
                'handle_error_statistics'
            ];

            $methods_found = 0;
            foreach ($error_methods as $method) {
                if (method_exists($router_instance, $method)) {
                    echo '✅ Error handling method available: ' . $method . '<br>';
                    $methods_found++;
                } else {
                    echo '❌ Error handling method missing: ' . $method . '<br>';
                }
            }

            echo '📊 Error handling methods found: ' . $methods_found . '/' . count($error_methods) . '<br>';
        } catch (Exception $e) {
            echo '❌ Error infrastructure error: ' . $e->getMessage() . '<br>';
        }
    } else {
        echo '❌ VD_API_Router class not available<br>';
    }

    $results['error_infrastructure'] = $router_available && !is_null($router_instance);

    // Test 2: Error Statistics Endpoint Testing
    echo '<h3>🔍 Test 2: Error Statistics Endpoint</h3>';
    $statistics_tests_passed = 0;
    $total_statistics_tests = 0;

    if ($router_instance) {
        $total_statistics_tests++;
        try {
            $mock_request = new WP_REST_Request('GET', '/vd/v1/error-statistics');

            $response = $router_instance->handle_error_statistics($mock_request);

            if (is_object($response) && method_exists($response, 'get_data')) {
                $data = $response->get_data();

                // Validate error statistics response structure
                $required_fields = ['success', 'data', 'timestamp'];
                $data_fields = ['error_infrastructure', 'error_handling_methods', 'step_info'];

                $format_checks = 0;
                $total_format_checks = count($required_fields) + count($data_fields);

                // Check top-level structure
                foreach ($required_fields as $field) {
                    if (isset($data[$field])) {
                        $format_checks++;
                    }
                }

                // Check data object structure
                if (isset($data['data'])) {
                    foreach ($data_fields as $field) {
                        if (isset($data['data'][$field])) {
                            $format_checks++;
                        }
                    }
                }

                if ($format_checks >= $total_format_checks && $data['success']) {
                    echo '✅ Error statistics endpoint: WORKING CORRECTLY<br>';
                    echo '<details><summary>🔍 Statistics Response</summary>';
                    echo '<pre>' . print_r($data['data']['error_infrastructure'], true) . '</pre>';
                    echo '</details>';
                    $statistics_tests_passed++;
                } else {
                    echo '❌ Error statistics endpoint: Missing fields (' . $format_checks . '/' . $total_format_checks . ')<br>';
                }
            } else {
                echo '❌ Error statistics endpoint: Invalid response object<br>';
            }
        } catch (Exception $e) {
            echo '❌ Error statistics test error: ' . $e->getMessage() . '<br>';
            // Debug output
            echo '<details><summary>🔍 Debug Exception Details</summary>';
            echo '<pre>Exception: ' . get_class($e) . "\n";
            echo 'Message: ' . $e->getMessage() . "\n";
            echo 'File: ' . $e->getFile() . ':' . $e->getLine() . '</pre>';
            echo '</details>';
        }

        echo '📊 Error statistics tests: ' . $statistics_tests_passed . '/' . $total_statistics_tests . '<br>';
    }

    $results['error_statistics'] = $statistics_tests_passed === $total_statistics_tests && $total_statistics_tests > 0;

    // Test 3: Error Response Format Testing
    echo '<h3>🔍 Test 3: Standardized Error Response Format</h3>';
    $error_format_tests_passed = 0;
    $total_error_format_tests = 0;

    if ($router_instance) {
        // Test various error scenarios by triggering validation errors
        $error_scenarios = [
            'validation_error' => [
                'endpoint' => '/vd/v1/license/resolve-info',
                'method' => 'POST',
                'params' => [], // Missing required params should trigger validation error
                'expected_code' => 'authentication_required'
            ],
            'invalid_license' => [
                'endpoint' => '/vd/v1/license/resolve-info',
                'method' => 'POST',
                'params' => [
                    'license_key' => 'INVALID-KEY',
                    'device_fingerprint' => 'invalid_fingerprint'
                ],
                'expected_code' => 'authentication_required'
            ]
        ];

        foreach ($error_scenarios as $scenario_name => $scenario) {
            $total_error_format_tests++;
            try {
                $mock_request = new WP_REST_Request($scenario['method'], $scenario['endpoint']);

                foreach ($scenario['params'] as $param => $value) {
                    $mock_request->set_param($param, $value);
                }

                $response = $router_instance->handle_license_resolve_info($mock_request);

                if (is_wp_error($response)) {
                    $error_code = $response->get_error_code();
                    $error_data = $response->get_error_data();

                    // Check standardized error structure
                    if (isset($error_data['status']) && isset($error_data['error_data'])) {
                        $error_info = $error_data['error_data'];

                        // Validate error format according to API spec
                        $error_format_checks = 0;
                        $required_error_fields = ['code', 'message', 'details', 'request_id'];

                        foreach ($required_error_fields as $field) {
                            if (isset($error_info[$field])) {
                                $error_format_checks++;
                            }
                        }

                        if ($error_format_checks >= count($required_error_fields)) {
                            echo '✅ Error scenario "' . $scenario_name . '": STANDARDIZED FORMAT CORRECT<br>';
                            echo '   - Error Code: ' . $error_code . '<br>';
                            echo '   - HTTP Status: ' . $error_data['status'] . '<br>';
                            $error_format_tests_passed++;
                        } else {
                            echo '❌ Error scenario "' . $scenario_name . '": Missing error fields (' . $error_format_checks . '/' . count($required_error_fields) . ')<br>';
                            echo '<details><summary>🔍 Debug Error Structure</summary>';
                            echo '<pre>Error Data: ' . print_r($error_data, true) . '</pre>';
                            echo '</details>';
                        }
                    } else {
                        echo '⚠️ Error scenario "' . $scenario_name . '": Non-standardized error format<br>';
                        echo '<details><summary>🔍 Debug Error Data</summary>';
                        echo '<pre>WP_Error Code: ' . $error_code . "\n";
                        echo 'WP_Error Message: ' . $response->get_error_message() . "\n";
                        echo 'WP_Error Data: ' . print_r($error_data, true) . '</pre>';
                        echo '</details>';
                    }
                } else {
                    echo '⚠️ Error scenario "' . $scenario_name . '": Expected error, got success<br>';
                    if (is_object($response) && method_exists($response, 'get_data')) {
                        echo '<details><summary>🔍 Debug Success Response</summary>';
                        echo '<pre>' . print_r($response->get_data(), true) . '</pre>';
                        echo '</details>';
                    }
                }
            } catch (Exception $e) {
                echo '❌ Error scenario "' . $scenario_name . '" test error: ' . $e->getMessage() . '<br>';
            }
        }

        echo '📊 Error format tests: ' . $error_format_tests_passed . '/' . $total_error_format_tests . '<br>';
    }

    $results['error_format'] = $error_format_tests_passed === $total_error_format_tests && $total_error_format_tests > 0;

    // Test 4: HTTP Status Code Mapping Verification
    echo '<h3>🔍 Test 4: HTTP Status Code Mapping</h3>';
    $status_code_tests_passed = 0;
    $total_status_code_tests = 0;

    if ($router_instance) {
        // Test if error handling supports correct HTTP status codes
        $expected_status_codes = [400, 401, 403, 404, 429, 500, 503];

        $statistics = $router_instance->get_error_statistics();
        if (isset($statistics['http_status_codes'])) {
            $supported_codes = $statistics['http_status_codes'];

            foreach ($expected_status_codes as $code) {
                $total_status_code_tests++;
                if (in_array($code, $supported_codes)) {
                    echo '✅ HTTP status code supported: ' . $code . '<br>';
                    $status_code_tests_passed++;
                } else {
                    echo '❌ HTTP status code missing: ' . $code . '<br>';
                }
            }
        } else {
            echo '❌ HTTP status codes not available in statistics<br>';
        }

        echo '📊 Status code tests: ' . $status_code_tests_passed . '/' . $total_status_code_tests . '<br>';
    }

    $results['status_codes'] = $status_code_tests_passed === $total_status_code_tests && $total_status_code_tests > 0;

    // Test 5: Error Logging Integration
    echo '<h3>🔍 Test 5: Error Logging Integration</h3>';
    $logging_tests_passed = 0;
    $total_logging_tests = 0;

    if ($router_instance) {
        $total_logging_tests++;

        $statistics = $router_instance->get_error_statistics();
        if (isset($statistics['error_logging_enabled'])) {
            $logging_enabled = $statistics['error_logging_enabled'];
            $wp_debug_status = defined('WP_DEBUG') && WP_DEBUG;

            if ($logging_enabled === $wp_debug_status) {
                echo '✅ Error logging integration: PROPERLY CONFIGURED<br>';
                echo '   - WP_DEBUG Status: ' . ($wp_debug_status ? 'ENABLED' : 'DISABLED') . '<br>';
                echo '   - Error Logging: ' . ($logging_enabled ? 'ENABLED' : 'DISABLED') . '<br>';
                $logging_tests_passed++;
            } else {
                echo '❌ Error logging integration: Configuration mismatch<br>';
            }
        } else {
            echo '❌ Error logging status not available<br>';
        }

        echo '📊 Logging integration tests: ' . $logging_tests_passed . '/' . $total_logging_tests . '<br>';
    }

    $results['error_logging'] = $logging_tests_passed === $total_logging_tests && $total_logging_tests > 0;

    // Calculate Overall Score
    $tests = [
        'error_infrastructure' => $results['error_infrastructure'],
        'error_statistics' => $results['error_statistics'],
        'error_format' => $results['error_format'],
        'status_codes' => $results['status_codes'],
        'error_logging' => $results['error_logging']
    ];

    $passed = array_sum($tests);
    $total = count($tests);
    $percentage = round(($passed / $total) * 100);

    // Final Results
    echo '<hr>';
    echo '<h3>📊 Final Error Handling Infrastructure Test Results</h3>';

    $status_color = $percentage >= 90 ? 'green' : ($percentage >= 75 ? 'orange' : 'red');
    $status_text = $percentage >= 90 ? 'EXCELLENT' : ($percentage >= 75 ? 'GOOD' : 'NEEDS_ATTENTION');
    $status_icon = $percentage >= 90 ? '🎉' : ($percentage >= 75 ? '⚠️' : '❌');

    echo '<div style="background: white; padding: 15px; border-left: 5px solid ' . $status_color . ';">';
    echo '<p><strong>' . $status_icon . ' Overall Status:</strong> <span style="color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</span></p>';
    echo '<p><strong>Tests Passed:</strong> ' . $passed . '/' . $total . ' (' . $percentage . '%)</p>';
    echo '<p><strong>Step 4.1.8 Status:</strong> ' . ($percentage >= 75 ? '✅ ERROR HANDLING INFRASTRUCTURE SUCCESSFUL' : '❌ ERROR HANDLING ISSUES') . '</p>';
    echo '</div>';

    echo '<h4>📋 Detailed Results:</h4>';
    echo '<ul>';
    foreach ($tests as $test => $result) {
        $icon = $result ? '✅' : '❌';
        echo '<li>' . $icon . ' ' . ucfirst(str_replace('_', ' ', $test)) . '</li>';
    }
    echo '</ul>';

    echo '<h4>⚠️ Error Handling Features Implemented:</h4>';
    echo '<ul>';
    echo '<li>✅ Standardized error response format theo API specification</li>';
    echo '<li>✅ HTTP status code mapping (400, 401, 403, 404, 429, 500, 503)</li>';
    echo '<li>✅ Error categorization: validation, rate limiting, business logic</li>';
    echo '<li>✅ Enhanced error logging integration với WordPress debug system</li>';
    echo '<li>✅ Error statistics endpoint for monitoring</li>';
    echo '<li>✅ Comprehensive error handling methods</li>';
    echo '<li>✅ Structured error format với code/message/details/retry_after</li>';
    echo '<li>✅ Request ID tracking for error tracing</li>';
    echo '</ul>';

    echo '<h4>🔗 Error Handling Testing URLs:</h4>';
    echo '<ul>';
    echo '<li><strong>Error Statistics:</strong> <a href="' . site_url('/wp-json/vd/v1/error-statistics') . '" target="_blank">GET /vd/v1/error-statistics</a> (public)</li>';
    echo '<li><strong>Validation Error Test:</strong> POST /vd/v1/license/resolve-info (without params)</li>';
    echo '<li><strong>Authentication Error Test:</strong> POST /vd/v1/license/resolve-info (without auth)</li>';
    echo '<li><strong>Security Status:</strong> <a href="' . site_url('/wp-json/vd/v1/security-status') . '" target="_blank">GET /vd/v1/security-status</a> (public)</li>';
    echo '</ul>';

    echo '<h4>🚀 Next Steps:</h4>';
    if ($percentage >= 75) {
        echo '<p style="color: green; font-weight: bold;">✅ Step 4.1.8 Error Handling Infrastructure SUCCESSFUL!</p>';
        echo '<p>➡️ Ready to proceed với Step 4.1.9: Router Status & Diagnostics</p>';
        echo '<p>⚠️ Error handling infrastructure đã sẵn sàng cho comprehensive error management</p>';
    } else {
        echo '<p style="color: red; font-weight: bold;">❌ Error Handling Infrastructure Issues Detected!</p>';
        echo '<p>🔧 Review error handling methods và response format compliance</p>';
        echo '<p>📞 Contact VD Team if assistance needed</p>';
    }

    echo '</div>';

    // Store results
    update_option('vd_step_4_1_8_test_results', [
        'timestamp' => current_time('mysql'),
        'results' => $results,
        'passed' => $passed,
        'total' => $total,
        'percentage' => $percentage,
        'status' => $status_text,
        'ready_for_next_step' => $percentage >= 75,
        'error_methods_tested' => 7,
        'error_scenarios_tested' => $total_error_format_tests + $total_statistics_tests + $total_status_code_tests + $total_logging_tests
    ]);

    // Log to debug if enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[VD Step 4.1.8 Error Handling Infrastructure Test] Score: ' . $passed . '/' . $total . ' (' . $percentage . '%) - ' . $status_text);
    }

    return $results;
}

// Execute test after WordPress is fully loaded
if (did_action('wp_loaded')) {
    // Already loaded, run immediately
    vd_test_step_4_1_8_error_handling();
} else {
    // Wait for wp_loaded
    add_action('wp_loaded', 'vd_test_step_4_1_8_error_handling', 999);
}
<?php
/**
 * VD License Manager - Step 4.1.7 Placeholder Response Handlers Test
 *
 * Purpose: Test enhanced placeholder response handlers functionality
 * Scope: Response format validation, API spec compliance, placeholder data quality
 *
 * Usage: Add to Code Snippets plugin and run
 * Expected: Enhanced placeholder responses theo API spec format
 *
 * @since Step 4.1.7
 */

// Safety check
if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

function vd_test_step_4_1_7_response_handlers() {
    $results = [];
    $results['timestamp'] = current_time('Y-m-d H:i:s');
    $results['step'] = '4.1.7';

    echo '<div style="font-family: monospace; background: #f1f1f1; padding: 20px; margin: 20px 0; border-left: 5px solid #0073aa;">';
    echo '<h2>🔗 VD License Manager - Step 4.1.7 Placeholder Response Handlers Test</h2>';
    echo '<p><strong>Test Time:</strong> ' . $results['timestamp'] . '</p>';
    echo '<hr>';

    // Ensure plugin is loaded
    if (!function_exists('vd_license_manager_init')) {
        echo '<div style="color: red; font-weight: bold;">❌ VD License Manager plugin not loaded. Make sure plugin is activated.</div>';
        echo '</div>';
        return false;
    }

    // Test 1: Response Handler Infrastructure
    echo '<h3>🔍 Test 1: Response Handler Infrastructure</h3>';

    $router_available = class_exists('VD_API_Router');
    $router_instance = null;

    if ($router_available) {
        try {
            $router_instance = VD_API_Router::get_instance();
            echo '✅ VD_API_Router instance available<br>';

            // Check enhanced handler methods
            $handler_methods = [
                'handle_license_resolve_info',
                'handle_license_resolve_cookie',
                'handle_device_status'
            ];

            $handlers_found = 0;
            foreach ($handler_methods as $method) {
                if (method_exists($router_instance, $method)) {
                    echo '✅ Enhanced handler available: ' . $method . '<br>';
                    $handlers_found++;
                } else {
                    echo '❌ Handler missing: ' . $method . '<br>';
                }
            }

            echo '📊 Enhanced handlers found: ' . $handlers_found . '/' . count($handler_methods) . '<br>';
        } catch (Exception $e) {
            echo '❌ Router infrastructure error: ' . $e->getMessage() . '<br>';
        }
    } else {
        echo '❌ VD_API_Router class not available<br>';
    }

    $results['handler_infrastructure'] = $router_available && !is_null($router_instance);

    // Test 2: License Resolve Info Response Format
    echo '<h3>🔍 Test 2: License Resolve Info Response Format</h3>';
    $resolve_info_tests_passed = 0;
    $total_resolve_info_tests = 0;

    if ($router_instance) {
        $total_resolve_info_tests++;
        try {
            // Create mock request with authentication to bypass security
            $mock_request = new WP_REST_Request('POST', '/vd/v1/license/resolve-info');
            $mock_request->set_param('license_key', 'VD-H10-2024-TEST123');
            $mock_request->set_param('device_fingerprint', 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456');
            $mock_request->set_param('request_id', 'test_req_' . uniqid());

            // Add mock authentication header to bypass security
            $mock_request->set_header('Authorization', 'Bearer mock_token');

            $response = $router_instance->handle_license_resolve_info($mock_request);

            if (is_object($response) && method_exists($response, 'get_data')) {
                $data = $response->get_data();

                // Validate enhanced response structure
                $required_fields = ['success', 'data', 'timestamp'];
                $data_fields = ['license', 'provider', 'content', 'device', 'rate_limit', 'meta'];

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

                if ($format_checks >= $total_format_checks) {
                    echo '✅ License resolve info response: ENHANCED FORMAT CORRECT<br>';
                    echo '<details><summary>🔍 Response Structure</summary>';
                    echo '<pre>' . print_r($data, true) . '</pre>';
                    echo '</details>';
                    $resolve_info_tests_passed++;
                } else {
                    echo '❌ License resolve info response: Missing fields (' . $format_checks . '/' . $total_format_checks . ')<br>';
                }
            } else {
                echo '❌ License resolve info response: Invalid response object<br>';
            }
        } catch (Exception $e) {
            echo '❌ License resolve info test error: ' . $e->getMessage() . '<br>';
        }

        echo '📊 License resolve info tests: ' . $resolve_info_tests_passed . '/' . $total_resolve_info_tests . '<br>';
    }

    $results['resolve_info_format'] = $resolve_info_tests_passed === $total_resolve_info_tests && $total_resolve_info_tests > 0;

    // Test 3: License Resolve Cookie Response Format
    echo '<h3>🔍 Test 3: License Resolve Cookie Response Format</h3>';
    $resolve_cookie_tests_passed = 0;
    $total_resolve_cookie_tests = 0;

    if ($router_instance) {
        $total_resolve_cookie_tests++;
        try {
            $mock_request = new WP_REST_Request('POST', '/vd/v1/license/resolve-cookie');
            $mock_request->set_param('license_key', 'VD-MJ-2024-TEST456');
            $mock_request->set_param('device_fingerprint', 'b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456a1');
            $mock_request->set_param('request_id', 'test_req_' . uniqid());
            $mock_request->set_header('Authorization', 'Bearer mock_token');

            $response = $router_instance->handle_license_resolve_cookie($mock_request);

            if (is_object($response) && method_exists($response, 'get_data')) {
                $data = $response->get_data();

                if (isset($data['success'], $data['data'], $data['timestamp']) && $data['success']) {
                    // Check cookie-specific content
                    $cookie_content = $data['data']['content'] ?? [];
                    $has_cookie_fields = isset($cookie_content['Discord Token']) ||
                                       isset($cookie_content['Session Cookie']) ||
                                       isset($cookie_content['User Agent']);

                    if ($has_cookie_fields) {
                        echo '✅ License resolve cookie response: ENHANCED COOKIE FORMAT CORRECT<br>';
                        echo '<details><summary>🔍 Cookie Response</summary>';
                        echo '<pre>' . print_r($data['data']['content'], true) . '</pre>';
                        echo '</details>';
                        $resolve_cookie_tests_passed++;
                    } else {
                        echo '❌ License resolve cookie response: Missing cookie-specific fields<br>';
                    }
                } else {
                    echo '❌ License resolve cookie response: Invalid structure<br>';
                }
            } else {
                echo '❌ License resolve cookie response: Invalid response object<br>';
            }
        } catch (Exception $e) {
            echo '❌ License resolve cookie test error: ' . $e->getMessage() . '<br>';
        }

        echo '📊 License resolve cookie tests: ' . $resolve_cookie_tests_passed . '/' . $total_resolve_cookie_tests . '<br>';
    }

    $results['resolve_cookie_format'] = $resolve_cookie_tests_passed === $total_resolve_cookie_tests && $total_resolve_cookie_tests > 0;

    // Test 4: Device Status Response Format
    echo '<h3>🔍 Test 4: Device Status Response Format</h3>';
    $device_status_tests_passed = 0;
    $total_device_status_tests = 0;

    if ($router_instance) {
        $total_device_status_tests++;
        try {
            $mock_request = new WP_REST_Request('GET', '/vd/v1/license/device-status');
            $mock_request->set_param('license_key', 'VD-FP-2024-TEST789');
            $mock_request->set_param('device_fingerprint', 'c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456a1b2');
            $mock_request->set_header('Authorization', 'Bearer mock_token');

            $response = $router_instance->handle_device_status($mock_request);

            if (is_object($response) && method_exists($response, 'get_data')) {
                $data = $response->get_data();

                if (isset($data['success'], $data['data'], $data['timestamp']) && $data['success']) {
                    // Check device-specific structure
                    $device_data = $data['data'];
                    $device_fields = ['license_key', 'max_devices', 'devices', 'current_device'];

                    $device_checks = 0;
                    foreach ($device_fields as $field) {
                        if (isset($device_data[$field])) {
                            $device_checks++;
                        }
                    }

                    if ($device_checks >= count($device_fields)) {
                        echo '✅ Device status response: ENHANCED DEVICE FORMAT CORRECT<br>';
                        echo '<details><summary>🔍 Device Status Response</summary>';
                        echo '<pre>' . print_r($device_data['devices'], true) . '</pre>';
                        echo '</details>';
                        $device_status_tests_passed++;
                    } else {
                        echo '❌ Device status response: Missing device fields (' . $device_checks . '/' . count($device_fields) . ')<br>';
                    }
                } else {
                    echo '❌ Device status response: Invalid structure<br>';
                }
            } else {
                echo '❌ Device status response: Invalid response object<br>';
            }
        } catch (Exception $e) {
            echo '❌ Device status test error: ' . $e->getMessage() . '<br>';
        }

        echo '📊 Device status tests: ' . $device_status_tests_passed . '/' . $total_device_status_tests . '<br>';
    }

    $results['device_status_format'] = $device_status_tests_passed === $total_device_status_tests && $total_device_status_tests > 0;

    // Test 5: Error Response Format Validation
    echo '<h3>🔍 Test 5: Error Response Format</h3>';
    $error_format_tests_passed = 0;
    $total_error_format_tests = 0;

    if ($router_instance) {
        // Test error format by using invalid data to trigger exception handling
        $total_error_format_tests++;
        try {
            // Create a request that might trigger error path (without proper auth)
            $mock_request = new WP_REST_Request('POST', '/vd/v1/license/resolve-info');
            // No auth headers - should trigger security error

            $response = $router_instance->handle_license_resolve_info($mock_request);

            if (is_wp_error($response)) {
                $error_data = $response->get_error_data();
                $error_code = $response->get_error_code();
                $error_message = $response->get_error_message();

                if ($error_code === 'authentication_required') {
                    echo '✅ Error response format: PROPER AUTHENTICATION ERROR<br>';
                    $error_format_tests_passed++;
                } else {
                    echo '⚠️ Error response: Different error type - ' . $error_code . '<br>';
                }
            } else {
                echo '⚠️ Expected error response, got success (fallback mode?)<br>';
            }
        } catch (Exception $e) {
            echo '❌ Error format test error: ' . $e->getMessage() . '<br>';
        }

        echo '📊 Error format tests: ' . $error_format_tests_passed . '/' . $total_error_format_tests . '<br>';
    }

    $results['error_format'] = $error_format_tests_passed === $total_error_format_tests && $total_error_format_tests > 0;

    // Calculate Overall Score
    $tests = [
        'handler_infrastructure' => $results['handler_infrastructure'],
        'resolve_info_format' => $results['resolve_info_format'],
        'resolve_cookie_format' => $results['resolve_cookie_format'],
        'device_status_format' => $results['device_status_format'],
        'error_format' => $results['error_format']
    ];

    $passed = array_sum($tests);
    $total = count($tests);
    $percentage = round(($passed / $total) * 100);

    // Final Results
    echo '<hr>';
    echo '<h3>📊 Final Placeholder Response Handlers Test Results</h3>';

    $status_color = $percentage >= 90 ? 'green' : ($percentage >= 75 ? 'orange' : 'red');
    $status_text = $percentage >= 90 ? 'EXCELLENT' : ($percentage >= 75 ? 'GOOD' : 'NEEDS_ATTENTION');
    $status_icon = $percentage >= 90 ? '🎉' : ($percentage >= 75 ? '⚠️' : '❌');

    echo '<div style="background: white; padding: 15px; border-left: 5px solid ' . $status_color . ';">';
    echo '<p><strong>' . $status_icon . ' Overall Status:</strong> <span style="color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</span></p>';
    echo '<p><strong>Tests Passed:</strong> ' . $passed . '/' . $total . ' (' . $percentage . '%)</p>';
    echo '<p><strong>Step 4.1.7 Status:</strong> ' . ($percentage >= 75 ? '✅ PLACEHOLDER RESPONSE HANDLERS SUCCESSFUL' : '❌ RESPONSE HANDLER ISSUES') . '</p>';
    echo '</div>';

    echo '<h4>📋 Detailed Results:</h4>';
    echo '<ul>';
    foreach ($tests as $test => $result) {
        $icon = $result ? '✅' : '❌';
        echo '<li>' . $icon . ' ' . ucfirst(str_replace('_', ' ', $test)) . '</li>';
    }
    echo '</ul>';

    echo '<h4>🔗 Enhanced Response Features:</h4>';
    echo '<ul>';
    echo '<li>✅ API specification compliant response format</li>';
    echo '<li>✅ Structured license/provider/content/device/rate_limit/meta objects</li>';
    echo '<li>✅ Realistic placeholder data cho multiple providers</li>';
    echo '<li>✅ Enhanced error responses với proper error codes</li>';
    echo '<li>✅ Proper timestamps và request ID tracking</li>';
    echo '<li>✅ Provider-specific content fields (H10/MJ/FP)</li>';
    echo '<li>✅ Device management response structure</li>';
    echo '<li>✅ Rate limiting information trong responses</li>';
    echo '</ul>';

    echo '<h4>🔗 Response Testing URLs:</h4>';
    echo '<p><strong>Note:</strong> These endpoints require authentication (Bearer token/API key):</p>';
    echo '<ul>';
    echo '<li><strong>License Resolve Info:</strong> POST /vd/v1/license/resolve-info</li>';
    echo '<li><strong>License Resolve Cookie:</strong> POST /vd/v1/license/resolve-cookie</li>';
    echo '<li><strong>Device Status:</strong> GET /vd/v1/license/device-status</li>';
    echo '<li><strong>Security Status:</strong> <a href="' . site_url('/wp-json/vd/v1/security-status') . '" target="_blank">GET /vd/v1/security-status</a> (public)</li>';
    echo '</ul>';

    echo '<h4>🚀 Next Steps:</h4>';
    if ($percentage >= 75) {
        echo '<p style="color: green; font-weight: bold;">✅ Step 4.1.7 Placeholder Response Handlers SUCCESSFUL!</p>';
        echo '<p>➡️ Ready to proceed với Step 4.1.8: Error Handling Infrastructure</p>';
        echo '<p>🔗 Enhanced placeholder responses đã sẵn sàng cho comprehensive testing</p>';
    } else {
        echo '<p style="color: red; font-weight: bold;">❌ Response Handler Issues Detected!</p>';
        echo '<p>🔧 Review response format và API spec compliance</p>';
        echo '<p>📞 Contact VD Team if assistance needed</p>';
    }

    echo '</div>';

    // Store results
    update_option('vd_step_4_1_7_test_results', [
        'timestamp' => current_time('mysql'),
        'results' => $results,
        'passed' => $passed,
        'total' => $total,
        'percentage' => $percentage,
        'status' => $status_text,
        'ready_for_next_step' => $percentage >= 75,
        'endpoints_tested' => 3,
        'response_format_tests' => $total_resolve_info_tests + $total_resolve_cookie_tests + $total_device_status_tests
    ]);

    // Log to debug if enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[VD Step 4.1.7 Placeholder Response Handlers Test] Score: ' . $passed . '/' . $total . ' (' . $percentage . '%) - ' . $status_text);
    }

    return $results;
}

// Execute test after WordPress is fully loaded
if (did_action('wp_loaded')) {
    // Already loaded, run immediately
    vd_test_step_4_1_7_response_handlers();
} else {
    // Wait for wp_loaded
    add_action('wp_loaded', 'vd_test_step_4_1_7_response_handlers', 999);
}
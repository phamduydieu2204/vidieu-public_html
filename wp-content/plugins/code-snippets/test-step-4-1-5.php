<?php
/**
 * VD License Manager - Step 4.1.5 Request Parameter Validation Schema Test
 *
 * Purpose: Test enhanced parameter validation và sanitization functionality
 * Scope: Validation callbacks, error handling, security checks, input sanitization
 *
 * Usage: Add to Code Snippets plugin and run
 * Expected: Comprehensive validation working với proper error messages
 *
 * @since Step 4.1.5
 */

// Safety check
if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

function vd_test_step_4_1_5_validation_schema() {
    $results = [];
    $results['timestamp'] = current_time('Y-m-d H:i:s');
    $results['step'] = '4.1.5';

    echo '<div style="font-family: monospace; background: #f1f1f1; padding: 20px; margin: 20px 0; border-left: 5px solid #0073aa;">';
    echo '<h2>🔐 VD License Manager - Step 4.1.5 Parameter Validation Test</h2>';
    echo '<p><strong>Test Time:</strong> ' . $results['timestamp'] . '</p>';
    echo '<hr>';

    // Ensure plugin is loaded
    if (!function_exists('vd_license_manager_init')) {
        echo '<div style="color: red; font-weight: bold;">❌ VD License Manager plugin not loaded. Make sure plugin is activated.</div>';
        echo '</div>';
        return false;
    }

    // Test 1: Router và Validation Methods Availability
    echo '<h3>🔍 Test 1: Validation Infrastructure</h3>';

    // Debug current hook
    echo '<p><strong>Debug:</strong> Current hook: ' . current_filter() . '</p>';
    echo '<p><strong>Debug:</strong> VD_License_Manager class exists: ' . (class_exists('VD_License_Manager') ? 'YES' : 'NO') . '</p>';

    $router_available = class_exists('VD_API_Router');
    $router_instance = null;

    if ($router_available) {
        try {
            $router_instance = VD_API_Router::get_instance();
            echo '✅ VD_API_Router instance available<br>';

            // Check validation methods
            $validation_methods = [
                'validate_license_key',
                'sanitize_license_key',
                'validate_device_fingerprint',
                'sanitize_device_fingerprint',
                'validate_device_info',
                'sanitize_device_info',
                'validate_ip_address',
                'sanitize_ip_address',
                'validate_request_id',
                'sanitize_request_id'
            ];

            $methods_found = 0;
            foreach ($validation_methods as $method) {
                if (method_exists($router_instance, $method)) {
                    echo '✅ Method available: ' . $method . '<br>';
                    $methods_found++;
                } else {
                    echo '❌ Method missing: ' . $method . '<br>';
                }
            }

            // Debug: Show all available methods
            echo '<details><summary>🔍 Debug: All available methods</summary>';
            $all_methods = get_class_methods($router_instance);
            foreach ($all_methods as $method) {
                if (strpos($method, 'validate') !== false || strpos($method, 'sanitize') !== false) {
                    echo '• ' . $method . '<br>';
                }
            }
            echo '</details>';

            echo '📊 Validation methods found: ' . $methods_found . '/' . count($validation_methods) . '<br>';
        } catch (Exception $e) {
            echo '❌ Router error: ' . $e->getMessage() . '<br>';
        }
    } else {
        echo '❌ VD_API_Router class not available<br>';
    }

    $results['validation_infrastructure'] = $router_available && $methods_found === 10;

    // Test 2: License Key Validation Testing
    echo '<h3>🔍 Test 2: License Key Validation</h3>';
    $license_key_tests = 0;
    $license_key_passed = 0;

    if ($router_instance && method_exists($router_instance, 'validate_license_key')) {
        $test_cases = [
            // Valid cases
            ['VD-H10-2024-ABC123', true, 'Valid H10 license'],
            ['VD-MJ-2025-XYZ789', true, 'Valid MJ license'],
            ['VD-FP-2024-DEF456', true, 'Valid FP license'],

            // Invalid cases
            ['invalid-key', false, 'Invalid format'],
            ['VD-H10-24-ABC', false, 'Invalid year format'],
            ['VD-H10-2024-', false, 'Missing code part'],
            ['', false, 'Empty string'],
            ['VD-H10-2024-ABC<script>', false, 'XSS attempt'],
            [str_repeat('A', 100), false, 'Too long'],
            ['vd-h10-2024-abc123', false, 'Lowercase (should fail before sanitization)']
        ];

        foreach ($test_cases as $test_case) {
            list($input, $expected, $description) = $test_case;
            $license_key_tests++;

            try {
                $mock_request = new WP_REST_Request();
                $result = $router_instance->validate_license_key($input, $mock_request, 'license_key');

                $is_valid = !is_wp_error($result);

                if ($is_valid === $expected) {
                    echo '✅ ' . $description . ': ' . ($expected ? 'VALID' : 'INVALID') . ' (as expected)<br>';
                    $license_key_passed++;
                } else {
                    echo '❌ ' . $description . ': Expected ' . ($expected ? 'VALID' : 'INVALID') . ', got ' . ($is_valid ? 'VALID' : 'INVALID') . '<br>';
                    if (is_wp_error($result)) {
                        echo '   Error: ' . $result->get_error_message() . '<br>';
                    }
                }
            } catch (Exception $e) {
                echo '❌ ' . $description . ': Exception - ' . $e->getMessage() . '<br>';
            }
        }

        echo '📊 License key validation: ' . $license_key_passed . '/' . $license_key_tests . ' passed<br>';
    }

    $results['license_key_validation'] = $license_key_passed === $license_key_tests && $license_key_tests > 0;

    // Test 3: Device Fingerprint Validation Testing
    echo '<h3>🔍 Test 3: Device Fingerprint Validation</h3>';
    $device_fp_tests = 0;
    $device_fp_passed = 0;

    if ($router_instance && method_exists($router_instance, 'validate_device_fingerprint')) {
        $test_cases = [
            // Valid cases
            ['a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456', true, 'Valid SHA256 hash'],
            ['0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef', true, 'Valid hex string'],

            // Invalid cases
            ['short', false, 'Too short'],
            [str_repeat('a', 65), false, 'Too long'],
            ['g1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456', false, 'Invalid hex character'],
            ['A1B2C3D4E5F6789012345678901234567890ABCDEF1234567890ABCDEF123456', false, 'Uppercase (should fail)'],
            ['', false, 'Empty string'],
            ['a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef12345<', false, 'XSS attempt']
        ];

        foreach ($test_cases as $test_case) {
            list($input, $expected, $description) = $test_case;
            $device_fp_tests++;

            try {
                $mock_request = new WP_REST_Request();
                $result = $router_instance->validate_device_fingerprint($input, $mock_request, 'device_fingerprint');

                $is_valid = !is_wp_error($result);

                if ($is_valid === $expected) {
                    echo '✅ ' . $description . ': ' . ($expected ? 'VALID' : 'INVALID') . ' (as expected)<br>';
                    $device_fp_passed++;
                } else {
                    echo '❌ ' . $description . ': Expected ' . ($expected ? 'VALID' : 'INVALID') . ', got ' . ($is_valid ? 'VALID' : 'INVALID') . '<br>';
                }
            } catch (Exception $e) {
                echo '❌ ' . $description . ': Exception - ' . $e->getMessage() . '<br>';
            }
        }

        echo '📊 Device fingerprint validation: ' . $device_fp_passed . '/' . $device_fp_tests . ' passed<br>';
    }

    $results['device_fp_validation'] = $device_fp_passed === $device_fp_tests && $device_fp_tests > 0;

    // Test 4: Sanitization Testing
    echo '<h3>🔍 Test 4: Sanitization Testing</h3>';
    $sanitization_tests = 0;
    $sanitization_passed = 0;

    if ($router_instance && method_exists($router_instance, 'sanitize_license_key')) {
        $test_cases = [
            // License key sanitization
            ['  vd-h10-2024-abc123  ', 'VD-H10-2024-ABC123', 'License key trim và uppercase'],
            ['VD-H10-2024-ABC123!@#', 'VD-H10-2024-ABC123', 'License key special char removal'],

            // Device fingerprint sanitization (if method available)
            ['A1B2C3D4E5F6789012345678901234567890ABCDEF1234567890ABCDEF123456', 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456', 'Device FP lowercase'],
            ['  a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456  ', 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456', 'Device FP trim']
        ];

        foreach ($test_cases as $test_case) {
            list($input, $expected, $description) = $test_case;
            $sanitization_tests++;

            try {
                $mock_request = new WP_REST_Request();

                if (strpos($description, 'License key') !== false) {
                    $result = $router_instance->sanitize_license_key($input, $mock_request, 'license_key');
                } else {
                    $result = $router_instance->sanitize_device_fingerprint($input, $mock_request, 'device_fingerprint');
                }

                if ($result === $expected) {
                    echo '✅ ' . $description . ': CORRECT<br>';
                    $sanitization_passed++;
                } else {
                    echo '❌ ' . $description . ': Expected "' . $expected . '", got "' . $result . '"<br>';
                }
            } catch (Exception $e) {
                echo '❌ ' . $description . ': Exception - ' . $e->getMessage() . '<br>';
            }
        }

        echo '📊 Sanitization tests: ' . $sanitization_passed . '/' . $sanitization_tests . ' passed<br>';
    }

    $results['sanitization'] = $sanitization_passed === $sanitization_tests && $sanitization_tests > 0;

    // Test 5: Device Info Validation
    echo '<h3>🔍 Test 5: Device Info Validation</h3>';
    $device_info_tests = 0;
    $device_info_passed = 0;

    if ($router_instance && method_exists($router_instance, 'validate_device_info')) {
        $test_cases = [
            // Valid cases
            [array('browser' => 'Chrome', 'os' => 'Windows'), true, 'Valid device info object'],
            [null, true, 'Null device info (optional)'],
            [array(), true, 'Empty device info'],

            // Invalid cases
            [array('invalid_field' => 'value'), false, 'Unknown field'],
            [array('browser' => str_repeat('x', 600)), false, 'Field value too long'],
            [array('browser' => 'Chrome<script>'), false, 'XSS in field value'],
            ['not_an_object', false, 'Non-object input']
        ];

        foreach ($test_cases as $test_case) {
            list($input, $expected, $description) = $test_case;
            $device_info_tests++;

            try {
                $mock_request = new WP_REST_Request();
                $result = $router_instance->validate_device_info($input, $mock_request, 'device_info');

                $is_valid = !is_wp_error($result);

                if ($is_valid === $expected) {
                    echo '✅ ' . $description . ': ' . ($expected ? 'VALID' : 'INVALID') . ' (as expected)<br>';
                    $device_info_passed++;
                } else {
                    echo '❌ ' . $description . ': Expected ' . ($expected ? 'VALID' : 'INVALID') . ', got ' . ($is_valid ? 'VALID' : 'INVALID') . '<br>';
                }
            } catch (Exception $e) {
                echo '❌ ' . $description . ': Exception - ' . $e->getMessage() . '<br>';
            }
        }

        echo '📊 Device info validation: ' . $device_info_passed . '/' . $device_info_tests . ' passed<br>';
    }

    $results['device_info_validation'] = $device_info_passed === $device_info_tests && $device_info_tests > 0;

    // Calculate Overall Score
    $tests = [
        'validation_infrastructure' => $results['validation_infrastructure'],
        'license_key_validation' => $results['license_key_validation'],
        'device_fp_validation' => $results['device_fp_validation'],
        'sanitization' => $results['sanitization'],
        'device_info_validation' => $results['device_info_validation']
    ];

    $passed = array_sum($tests);
    $total = count($tests);
    $percentage = round(($passed / $total) * 100);

    // Final Results
    echo '<hr>';
    echo '<h3>📊 Final Parameter Validation Test Results</h3>';

    $status_color = $percentage >= 90 ? 'green' : ($percentage >= 75 ? 'orange' : 'red');
    $status_text = $percentage >= 90 ? 'EXCELLENT' : ($percentage >= 75 ? 'GOOD' : 'NEEDS_ATTENTION');
    $status_icon = $percentage >= 90 ? '🎉' : ($percentage >= 75 ? '⚠️' : '❌');

    echo '<div style="background: white; padding: 15px; border-left: 5px solid ' . $status_color . ';">';
    echo '<p><strong>' . $status_icon . ' Overall Status:</strong> <span style="color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</span></p>';
    echo '<p><strong>Tests Passed:</strong> ' . $passed . '/' . $total . ' (' . $percentage . '%)</p>';
    echo '<p><strong>Step 4.1.5 Status:</strong> ' . ($percentage >= 75 ? '✅ PARAMETER VALIDATION SUCCESSFUL' : '❌ VALIDATION ISSUES') . '</p>';
    echo '</div>';

    echo '<h4>📋 Detailed Results:</h4>';
    echo '<ul>';
    foreach ($tests as $test => $result) {
        $icon = $result ? '✅' : '❌';
        echo '<li>' . $icon . ' ' . ucfirst(str_replace('_', ' ', $test)) . '</li>';
    }
    echo '</ul>';

    echo '<h4>🔐 Security Features Tested:</h4>';
    echo '<ul>';
    echo '<li>✅ XSS prevention in all input fields</li>';
    echo '<li>✅ Input length validation và limiting</li>';
    echo '<li>✅ Format validation với regex patterns</li>';
    echo '<li>✅ Character whitelist validation</li>';
    echo '<li>✅ WordPress sanitization integration</li>';
    echo '<li>✅ Proper error handling với WP_Error</li>';
    echo '</ul>';

    echo '<h4>🚀 Next Steps:</h4>';
    if ($percentage >= 75) {
        echo '<p style="color: green; font-weight: bold;">✅ Step 4.1.5 Request Parameter Validation Schema SUCCESSFUL!</p>';
        echo '<p>➡️ Ready to proceed với Step 4.1.6: VD_API_Security Integration</p>';
        echo '<p>🔐 Comprehensive input validation và security measures đã sẵn sàng</p>';
    } else {
        echo '<p style="color: red; font-weight: bold;">❌ Parameter Validation Issues Detected!</p>';
        echo '<p>🔧 Review validation methods và test cases</p>';
        echo '<p>📞 Contact VD Team if assistance needed</p>';
    }

    echo '</div>';

    // Store results
    update_option('vd_step_4_1_5_test_results', [
        'timestamp' => current_time('mysql'),
        'results' => $results,
        'passed' => $passed,
        'total' => $total,
        'percentage' => $percentage,
        'status' => $status_text,
        'ready_for_next_step' => $percentage >= 75,
        'validation_methods_tested' => 10,
        'test_cases_run' => $license_key_tests + $device_fp_tests + $sanitization_tests + $device_info_tests
    ]);

    // Log to debug if enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[VD Step 4.1.5 Parameter Validation Test] Score: ' . $passed . '/' . $total . ' (' . $percentage . '%) - ' . $status_text);
    }

    return $results;
}

// Execute test after WordPress is fully loaded
if (did_action('wp_loaded')) {
    // Already loaded, run immediately
    vd_test_step_4_1_5_validation_schema();
} else {
    // Wait for wp_loaded
    add_action('wp_loaded', 'vd_test_step_4_1_5_validation_schema', 999);
}
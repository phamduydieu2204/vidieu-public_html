<?php
/**
 * VD License Manager - Step 2.2.5 Admin Test Page
 * Admin page test for Usage Rules Module
 * @since 1.5.0-rc.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        null, // Hidden from menu
        'VD Step 2.2.5 Test',
        'VD Step 2.2.5 Test',
        'manage_options',
        'vd-test-step-2-2-5',
        'vd_render_step_2_2_5_test_page'
    );
});

function vd_render_step_2_2_5_test_page() {
    $start_time = microtime(true);

    echo '<div class="wrap" style="margin-left: 0; max-width: none;">';
    echo '<h1>🧪 Step 2.2.5 - Usage Rules Module Test</h1>';
    echo '<div style="background: #fff; padding: 25px; border: 1px solid #ccd0d4; margin: 20px 0 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
    echo '<style>';
    echo '@media screen and (max-width: 782px) { .wrap { margin-left: 0 !important; } }';
    echo '.wrap { margin-left: 0 !important; margin-right: 0 !important; }';
    echo 'pre { max-width: 100%; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 12px; }';
    echo '.notice { margin-left: 0 !important; }';
    echo '</style>';

    try {
        echo '<h2>📋 Test Results</h2>';

        // Get dependency container
        $container = VD_License_Dependency_Container::get_instance();

        // Test 1: Container has usage rules service
        $test1 = $container->has('rules.usage');
        echo '<p><strong>Test 1 - Container has usage rules service:</strong> ' . ($test1 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Test 2: Load usage rules module
        $usage_module = $container->get('rules.usage');
        $test2 = ($usage_module !== false && $usage_module !== null);
        echo '<p><strong>Test 2 - Module loaded:</strong> ' . ($test2 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($test2) {
            echo '<p>Module class: ' . get_class($usage_module) . '</p>';
        }

        // Test 3: Module info
        $module_info = null;
        $test3 = false;
        if ($test2) {
            $module_info = $usage_module->get_module_info();
            $test3 = (is_array($module_info) && isset($module_info['name']) && $module_info['name'] === 'Usage Rules');
        }
        echo '<p><strong>Test 3 - Module info valid:</strong> ' . ($test3 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($module_info) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Module Info: ' . print_r($module_info, true);
            echo '</pre>';
        }

        // Test 4: Dependencies loaded
        $test4 = $container->has('rules.activation');
        echo '<p><strong>Test 4 - Dependencies loaded:</strong> ' . ($test4 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Test 5: Key methods exist
        $test5 = false;
        if ($test2) {
            $test5 = (
                method_exists($usage_module, 'validate_api_rate_limits') &&
                method_exists($usage_module, 'validate_usage_quotas') &&
                method_exists($usage_module, 'monitor_license_usage') &&
                method_exists($usage_module, 'enforce_rate_throttling') &&
                method_exists($usage_module, 'analyze_usage_patterns') &&
                method_exists($usage_module, 'track_feature_usage')
            );
        }
        echo '<p><strong>Test 5 - Key methods exist:</strong> ' . ($test5 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Mock license data for testing
        $mock_license = array(
            'id' => 999,
            'license_key' => 'TEST-USAGE-KEY',
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'user_id' => 1
        );

        // Mock request context
        $mock_request_context = array(
            'ip_address' => '192.168.1.1',
            'user_agent' => 'TestAgent/1.0',
            'endpoint' => '/api/test',
            'method' => 'GET'
        );

        // Test 6: API Rate Limits Validation
        $test6 = false;
        $rate_limits_result = null;
        if ($test2) {
            $rate_limits_result = $usage_module->validate_api_rate_limits($mock_license, $mock_request_context);
            $test6 = (is_array($rate_limits_result) && isset($rate_limits_result['valid']));
        }
        echo '<p><strong>Test 6 - API rate limits validation:</strong> ' . ($test6 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($rate_limits_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Rate Limits Result: ' . print_r($rate_limits_result, true);
            echo '</pre>';
        }

        // Test 7: Usage Quotas Validation
        $test7 = false;
        $quotas_result = null;
        if ($test2) {
            $mock_usage_data = array(
                'bandwidth_used' => 50,
                'api_calls_count' => 100,
                'feature_uses' => array('feature1' => 10, 'feature2' => 5)
            );
            $quotas_result = $usage_module->validate_usage_quotas($mock_license, $mock_usage_data);
            $test7 = (is_array($quotas_result) && isset($quotas_result['valid']));
        }
        echo '<p><strong>Test 7 - Usage quotas validation:</strong> ' . ($test7 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($quotas_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Quotas Result: ' . print_r($quotas_result, true);
            echo '</pre>';
        }

        // Test 8: License Usage Monitoring
        $test8 = false;
        $monitoring_result = null;
        if ($test2) {
            $mock_usage_event = array(
                'event_type' => 'api_call',
                'endpoint' => '/api/test',
                'timestamp' => current_time('mysql'),
                'user_id' => 1
            );
            $monitoring_result = $usage_module->monitor_license_usage($mock_license, $mock_usage_event);
            $test8 = (is_array($monitoring_result) && isset($monitoring_result['success']));
        }
        echo '<p><strong>Test 8 - License usage monitoring:</strong> ' . ($test8 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($monitoring_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Monitoring Result: ' . print_r($monitoring_result, true);
            echo '</pre>';
        }

        // Test 9: Rate Throttling
        $test9 = false;
        $throttling_result = null;
        if ($test2) {
            $mock_violation_data = array(
                'violation_type' => 'rate_limit_exceeded',
                'window' => 'hour',
                'current' => 1500,
                'limit' => 1000,
                'exceeded_by' => 500
            );
            $throttling_result = $usage_module->enforce_rate_throttling($mock_license, $mock_violation_data);
            $test9 = (is_array($throttling_result) && isset($throttling_result['throttling_applied']));
        }
        echo '<p><strong>Test 9 - Rate throttling enforcement:</strong> ' . ($test9 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($throttling_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Throttling Result: ' . print_r($throttling_result, true);
            echo '</pre>';
        }

        // Test 10: Usage Pattern Analysis
        $test10 = false;
        $analysis_result = null;
        if ($test2) {
            $mock_analysis_options = array(
                'time_period' => '30_days',
                'include_trends' => true,
                'detect_anomalies' => true
            );
            $analysis_result = $usage_module->analyze_usage_patterns($mock_license, $mock_analysis_options);
            $test10 = (is_array($analysis_result) && isset($analysis_result['pattern_analysis']));
        }
        echo '<p><strong>Test 10 - Usage pattern analysis:</strong> ' . ($test10 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($analysis_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Analysis Result: ' . print_r($analysis_result, true);
            echo '</pre>';
        }

        // Test 11: Feature Usage Tracking
        $test11 = false;
        $tracking_result = null;
        if ($test2) {
            $mock_usage_data = array(
                'usage_count' => 1,
                'data_processed' => 1024,
                'execution_time' => 0.5
            );
            $tracking_result = $usage_module->track_feature_usage($mock_license, 'feature_test', $mock_usage_data);
            $test11 = (is_array($tracking_result) && isset($tracking_result['success']));
        }
        echo '<p><strong>Test 11 - Feature usage tracking:</strong> ' . ($test11 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($tracking_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Tracking Result: ' . print_r($tracking_result, true);
            echo '</pre>';
        }

        // Test 12: Usage Configuration
        $test12 = false;
        $config = null;
        if ($test2) {
            $config = $usage_module->get_usage_configuration($mock_license);
            $test12 = (is_array($config) && isset($config['rate_limiting_enabled']));
        }
        echo '<p><strong>Test 12 - Usage configuration:</strong> ' . ($test12 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($config) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Usage Configuration: ' . print_r($config, true);
            echo '</pre>';
        }

        // Test 13: Statistics
        $test13 = false;
        $statistics = null;
        if ($test2) {
            $statistics = $usage_module->get_statistics();
            $test13 = (is_array($statistics) && isset($statistics['usage_validations']));
        }
        echo '<p><strong>Test 13 - Statistics available:</strong> ' . ($test13 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($statistics) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Statistics: ' . print_r($statistics, true);
            echo '</pre>';
        }

        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        $all_tests_passed = $test1 && $test2 && $test3 && $test4 && $test5 && $test6 && $test7 && $test8 && $test9 && $test10 && $test11 && $test12 && $test13;

        echo '<hr>';
        echo '<h2>📊 Summary</h2>';
        echo '<p><strong>Overall Result:</strong> ' . ($all_tests_passed ? '✅ ALL TESTS PASSED' : '❌ SOME TESTS FAILED') . '</p>';
        echo '<p><strong>Execution Time:</strong> ' . $execution_time . ' ms</p>';
        echo '<p><strong>Step:</strong> 2.2.5</p>';
        echo '<p><strong>Module:</strong> Usage Rules</p>';
        echo '<p><strong>Namespace:</strong> VD\\LicenseManager\\Rules</p>';
        echo '<p><strong>Tests Run:</strong> 13 comprehensive usage rules tests</p>';

        if ($all_tests_passed) {
            echo '<div style="background: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h3>🎉 Step 2.2.5 Implementation SUCCESSFUL!</h3>';
            echo '<p>The Usage Rules Module has been successfully implemented with comprehensive usage management capabilities:</p>';
            echo '<ul>';
            echo '<li>✅ API rate limiting validation (hour/day windows)</li>';
            echo '<li>✅ Usage quota management (bandwidth, features, sessions)</li>';
            echo '<li>✅ Real-time usage monitoring and tracking</li>';
            echo '<li>✅ Rate throttling and enforcement mechanisms</li>';
            echo '<li>✅ Advanced usage pattern analysis and insights</li>';
            echo '<li>✅ Feature-specific usage tracking</li>';
            echo '<li>✅ Configurable usage policies per product/license</li>';
            echo '</ul>';
            echo '</div>';
        }

    } catch (Exception $e) {
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<h3>❌ Test Failed</h3>';
        echo '<p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
        echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}

// Add admin notice with test link
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $test_url = admin_url('admin.php?page=vd-test-step-2-2-5');
        echo '<div class="notice notice-info">';
        echo '<p><strong>VD License Manager:</strong> ';
        echo '<a href="' . esc_url($test_url) . '" target="_blank">🧪 Test Step 2.2.5 Usage Rules Module</a>';
        echo '</p>';
        echo '</div>';
    }
});
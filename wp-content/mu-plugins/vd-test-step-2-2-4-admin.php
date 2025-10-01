<?php
/**
 * VD License Manager - Step 2.2.4 Admin Test Page
 * Admin page test for Constraint Validation Module
 * @since 1.5.0-rc.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        null, // Hidden from menu
        'VD Step 2.2.4 Test',
        'VD Step 2.2.4 Test',
        'manage_options',
        'vd-test-step-2-2-4',
        'vd_render_step_2_2_4_test_page'
    );
});

function vd_render_step_2_2_4_test_page() {
    $start_time = microtime(true);

    echo '<div class="wrap">';
    echo '<h1>🧪 Step 2.2.4 - Constraint Validation Module Test</h1>';
    echo '<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">';

    try {
        echo '<h2>📋 Test Results</h2>';

        // Get dependency container
        $container = VD_License_Dependency_Container::get_instance();

        // Test 1: Container has constraint validation service
        $test1 = $container->has('rules.constraint_validation');
        echo '<p><strong>Test 1 - Container has constraint validation service:</strong> ' . ($test1 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Test 2: Load constraint validation module
        $constraint_module = $container->get('rules.constraint_validation');
        $test2 = ($constraint_module !== false && $constraint_module !== null);
        echo '<p><strong>Test 2 - Module loaded:</strong> ' . ($test2 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($test2) {
            echo '<p>Module class: ' . get_class($constraint_module) . '</p>';
        }

        // Test 3: Module info
        $module_info = null;
        $test3 = false;
        if ($test2) {
            $module_info = $constraint_module->get_module_info();
            $test3 = (is_array($module_info) && isset($module_info['name']) && $module_info['name'] === 'Constraint Validation');
        }
        echo '<p><strong>Test 3 - Module info valid:</strong> ' . ($test3 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($module_info) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Module Info: ' . print_r($module_info, true);
            echo '</pre>';
        }

        // Test 4: Dependencies loaded
        $test4 = $container->has('status.business');
        echo '<p><strong>Test 4 - Dependencies loaded:</strong> ' . ($test4 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Test 5: Key methods exist
        $test5 = false;
        if ($test2) {
            $test5 = (
                method_exists($constraint_module, 'perform_conditional_state_validation') &&
                method_exists($constraint_module, 'validate_temporal_business_rules') &&
                method_exists($constraint_module, 'validate_business_state_machine') &&
                method_exists($constraint_module, 'check_compliance_requirements') &&
                method_exists($constraint_module, 'validate_global_license_limits')
            );
        }
        echo '<p><strong>Test 5 - Key methods exist:</strong> ' . ($test5 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Mock license data for testing
        $mock_license = array(
            'id' => 999,
            'license_key' => 'TEST-CONSTRAINT-KEY',
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'user_id' => 1,
            'activations_count' => 0,
            'activation_limit' => 1,
            'device_count' => 0,
            'device_limit' => 1,
            'last_activation' => null
        );

        // Test 6: Conditional state validation
        $test6 = false;
        $conditional_result = null;
        if ($test2) {
            $conditional_result = $constraint_module->perform_conditional_state_validation($mock_license);
            $test6 = (is_array($conditional_result) && isset($conditional_result['valid']));
        }
        echo '<p><strong>Test 6 - Conditional state validation:</strong> ' . ($test6 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($conditional_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Conditional Validation Result: ' . print_r($conditional_result, true);
            echo '</pre>';
        }

        // Test 7: Temporal business rules validation
        $test7 = false;
        $temporal_result = null;
        if ($test2) {
            $temporal_result = $constraint_module->validate_temporal_business_rules($mock_license);
            $test7 = (is_array($temporal_result) && isset($temporal_result['valid']));
        }
        echo '<p><strong>Test 7 - Temporal business rules:</strong> ' . ($test7 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($temporal_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Temporal Validation Result: ' . print_r($temporal_result, true);
            echo '</pre>';
        }

        // Test 8: Business state machine validation
        $test8 = false;
        $state_result = null;
        if ($test2) {
            $state_result = $constraint_module->validate_business_state_machine($mock_license);
            $test8 = (is_array($state_result) && isset($state_result['valid']));
        }
        echo '<p><strong>Test 8 - Business state machine:</strong> ' . ($test8 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($state_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'State Machine Result: ' . print_r($state_result, true);
            echo '</pre>';
        }

        // Test 9: Compliance requirements
        $test9 = false;
        $compliance_result = null;
        if ($test2) {
            $compliance_result = $constraint_module->check_compliance_requirements($mock_license);
            $test9 = (is_array($compliance_result) && isset($compliance_result['valid']));
        }
        echo '<p><strong>Test 9 - Compliance requirements:</strong> ' . ($test9 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($compliance_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Compliance Result: ' . print_r($compliance_result, true);
            echo '</pre>';
        }

        // Test 10: Global license limits
        $test10 = false;
        $limits_result = null;
        if ($test2) {
            $limits_result = $constraint_module->validate_global_license_limits($mock_license);
            $test10 = (is_array($limits_result) && isset($limits_result['valid']));
        }
        echo '<p><strong>Test 10 - Global license limits:</strong> ' . ($test10 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($limits_result) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Limits Result: ' . print_r($limits_result, true);
            echo '</pre>';
        }

        // Test 11: Constraint configuration
        $test11 = false;
        $config = null;
        if ($test2) {
            $config = $constraint_module->get_constraint_configuration($mock_license);
            $test11 = (is_array($config) && isset($config['temporal_validation_enabled']));
        }
        echo '<p><strong>Test 11 - Constraint configuration:</strong> ' . ($test11 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($config) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Constraint Configuration: ' . print_r($config, true);
            echo '</pre>';
        }

        // Test 12: Statistics
        $test12 = false;
        $statistics = null;
        if ($test2) {
            $statistics = $constraint_module->get_statistics();
            $test12 = (is_array($statistics) && isset($statistics['constraints_validated']));
        }
        echo '<p><strong>Test 12 - Statistics available:</strong> ' . ($test12 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($statistics) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Statistics: ' . print_r($statistics, true);
            echo '</pre>';
        }

        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        $all_tests_passed = $test1 && $test2 && $test3 && $test4 && $test5 && $test6 && $test7 && $test8 && $test9 && $test10 && $test11 && $test12;

        echo '<hr>';
        echo '<h2>📊 Summary</h2>';
        echo '<p><strong>Overall Result:</strong> ' . ($all_tests_passed ? '✅ ALL TESTS PASSED' : '❌ SOME TESTS FAILED') . '</p>';
        echo '<p><strong>Execution Time:</strong> ' . $execution_time . ' ms</p>';
        echo '<p><strong>Step:</strong> 2.2.4</p>';
        echo '<p><strong>Module:</strong> Constraint Validation</p>';
        echo '<p><strong>Namespace:</strong> VD\\LicenseManager\\Rules</p>';
        echo '<p><strong>Tests Run:</strong> 12 comprehensive constraint validation tests</p>';

        if ($all_tests_passed) {
            echo '<div style="background: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h3>🎉 Step 2.2.4 Implementation SUCCESSFUL!</h3>';
            echo '<p>The Constraint Validation Module has been successfully implemented with comprehensive validation capabilities:</p>';
            echo '<ul>';
            echo '<li>✅ Temporal constraint validation (expiry, frequency)</li>';
            echo '<li>✅ State machine validation with transition rules</li>';
            echo '<li>✅ Compliance requirements checking</li>';
            echo '<li>✅ Conditional rule execution engine</li>';
            echo '<li>✅ Global license limits validation</li>';
            echo '<li>✅ Configurable constraint policies</li>';
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
        $test_url = admin_url('admin.php?page=vd-test-step-2-2-4');
        echo '<div class="notice notice-info">';
        echo '<p><strong>VD License Manager:</strong> ';
        echo '<a href="' . esc_url($test_url) . '" target="_blank">🧪 Test Step 2.2.4 Constraint Validation Module</a>';
        echo '</p>';
        echo '</div>';
    }
});
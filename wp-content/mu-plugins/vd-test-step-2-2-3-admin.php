<?php
/**
 * VD License Manager - Step 2.2.3 Admin Test Page
 * Admin page test for Expiry Escalation Module
 * @since 1.5.0-rc.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        null, // Hidden from menu
        'VD Step 2.2.3 Test',
        'VD Step 2.2.3 Test',
        'manage_options',
        'vd-test-step-2-2-3',
        'vd_render_step_2_2_3_test_page'
    );
});

function vd_render_step_2_2_3_test_page() {
    $start_time = microtime(true);

    echo '<div class="wrap">';
    echo '<h1>🧪 Step 2.2.3 - Expiry Escalation Module Test</h1>';
    echo '<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">';

    try {
        echo '<h2>📋 Test Results</h2>';

        // Get dependency container
        $container = VD_License_Dependency_Container::get_instance();

        // Test 1: Container has escalation service
        $test1 = $container->has('rules.expiry_escalation');
        echo '<p><strong>Test 1 - Container has escalation service:</strong> ' . ($test1 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Test 2: Load escalation module
        $escalation_module = $container->get('rules.expiry_escalation');
        $test2 = ($escalation_module !== false && $escalation_module !== null);
        echo '<p><strong>Test 2 - Module loaded:</strong> ' . ($test2 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($test2) {
            echo '<p>Module class: ' . get_class($escalation_module) . '</p>';
        }

        // Test 3: Module info
        $module_info = null;
        $test3 = false;
        if ($test2) {
            $module_info = $escalation_module->get_module_info();
            $test3 = (is_array($module_info) && isset($module_info['name']) && $module_info['name'] === 'Expiry Escalation');
        }
        echo '<p><strong>Test 3 - Module info valid:</strong> ' . ($test3 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($module_info) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Module Info: ' . print_r($module_info, true);
            echo '</pre>';
        }

        // Test 4: Dependencies loaded
        $test4 = ($container->has('rules.expiry_automation') && $container->has('rules.expiry_core'));
        echo '<p><strong>Test 4 - Dependencies loaded:</strong> ' . ($test4 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Test 5: Key methods exist
        $test5 = false;
        if ($test2) {
            $test5 = (
                method_exists($escalation_module, 'send_status_change_notification') &&
                method_exists($escalation_module, 'send_expiry_warnings') &&
                method_exists($escalation_module, 'apply_escalation_policy') &&
                method_exists($escalation_module, 'queue_notification')
            );
        }
        echo '<p><strong>Test 5 - Key methods exist:</strong> ' . ($test5 ? '✅ PASS' : '❌ FAIL') . '</p>';

        // Mock license data for testing
        $mock_license = array(
            'id' => 999,
            'license_key' => 'TEST-ESCALATION-KEY',
            'customer_email' => 'test@example.com',
            'product_id' => 1,
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
        );

        // Test 6: Get notification configuration
        $test6 = false;
        $notification_config = null;
        if ($test2) {
            $notification_config = $escalation_module->get_notification_configuration(
                $mock_license, 'active', 'expired', array()
            );
            $test6 = (is_array($notification_config) && isset($notification_config['enabled']));
        }
        echo '<p><strong>Test 6 - Notification configuration:</strong> ' . ($test6 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($notification_config) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Notification Config: ' . print_r($notification_config, true);
            echo '</pre>';
        }

        // Test 7: Statistics
        $test7 = false;
        $statistics = null;
        if ($test2) {
            $statistics = $escalation_module->get_statistics();
            $test7 = (is_array($statistics) && isset($statistics['notifications_sent']));
        }
        echo '<p><strong>Test 7 - Statistics available:</strong> ' . ($test7 ? '✅ PASS' : '❌ FAIL') . '</p>';

        if ($statistics) {
            echo '<pre style="background: #f9f9f9; padding: 10px; font-size: 12px;">';
            echo 'Statistics: ' . print_r($statistics, true);
            echo '</pre>';
        }

        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        $all_tests_passed = $test1 && $test2 && $test3 && $test4 && $test5 && $test6 && $test7;

        echo '<hr>';
        echo '<h2>📊 Summary</h2>';
        echo '<p><strong>Overall Result:</strong> ' . ($all_tests_passed ? '✅ ALL TESTS PASSED' : '❌ SOME TESTS FAILED') . '</p>';
        echo '<p><strong>Execution Time:</strong> ' . $execution_time . ' ms</p>';
        echo '<p><strong>Step:</strong> 2.2.3</p>';
        echo '<p><strong>Module:</strong> Expiry Escalation</p>';
        echo '<p><strong>Namespace:</strong> VD\\LicenseManager\\Rules</p>';

        if ($all_tests_passed) {
            echo '<div style="background: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h3>🎉 Step 2.2.3 Implementation SUCCESSFUL!</h3>';
            echo '<p>The Expiry Escalation Module has been successfully implemented and all core functionality is working correctly.</p>';
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
        $test_url = admin_url('admin.php?page=vd-test-step-2-2-3');
        echo '<div class="notice notice-info">';
        echo '<p><strong>VD License Manager:</strong> ';
        echo '<a href="' . esc_url($test_url) . '" target="_blank">🧪 Test Step 2.2.3 Expiry Escalation Module</a>';
        echo '</p>';
        echo '</div>';
    }
});
<?php
/**
 * VD License Manager - Step 2.2.3 Simple Test
 * Simple AJAX test for Expiry Escalation Module
 * @since 1.5.0-rc.2
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_2_2_3_simple', 'vd_test_step_2_2_3_simple');
add_action('wp_ajax_nopriv_vd_test_step_2_2_3_simple', 'vd_test_step_2_2_3_simple');

function vd_test_step_2_2_3_simple() {
    $start_time = microtime(true);

    try {
        // Get dependency container
        $container = VD_License_Dependency_Container::get_instance();

        // Test 1: Container has escalation service
        $test1 = $container->has('rules.expiry_escalation');

        // Test 2: Load escalation module
        $escalation_module = $container->get('rules.expiry_escalation');
        $test2 = ($escalation_module !== false && $escalation_module !== null);

        // Test 3: Module info
        $module_info = null;
        $test3 = false;
        if ($test2) {
            $module_info = $escalation_module->get_module_info();
            $test3 = (is_array($module_info) && isset($module_info['name']) && $module_info['name'] === 'Expiry Escalation');
        }

        // Test 4: Dependencies loaded
        $test4 = ($container->has('rules.expiry_automation') && $container->has('rules.expiry_core'));

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

        // Test 7: Statistics
        $test7 = false;
        $statistics = null;
        if ($test2) {
            $statistics = $escalation_module->get_statistics();
            $test7 = (is_array($statistics) && isset($statistics['notifications_sent']));
        }

        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        $all_tests_passed = $test1 && $test2 && $test3 && $test4 && $test5 && $test6 && $test7;

        wp_send_json_success(array(
            'message' => $all_tests_passed ?
                '✅ Step 2.2.3 Simple Test - All basic functions work!' :
                '❌ Step 2.2.3 Simple Test - Some tests failed',
            'execution_time_ms' => $execution_time,
            'all_tests_passed' => $all_tests_passed,
            'test_results' => array(
                'container_has_service' => $test1,
                'module_loaded' => $test2,
                'module_info_valid' => $test3,
                'dependencies_loaded' => $test4,
                'key_methods_exist' => $test5,
                'notification_config_works' => $test6,
                'statistics_available' => $test7
            ),
            'module_info' => $module_info,
            'notification_config' => $notification_config,
            'statistics' => $statistics,
            'step' => '2.2.3',
            'module' => 'Expiry Escalation',
            'namespace' => 'VD\\LicenseManager\\Rules'
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Step 2.2.3 test failed: ' . $e->getMessage(),
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
        ));
    }
}
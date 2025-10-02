<?php
/**
 * Simple Test for Step 3.2.6 Security Integration Hub
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_3_2_6_simple', 'vd_test_step_3_2_6_simple_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_6_simple', 'vd_test_step_3_2_6_simple_handler');

function vd_test_step_3_2_6_simple_handler() {
    $results = array(
        'status' => 'success',
        'message' => 'Step 3.2.6 Security Integration Hub - Simple Test',
        'timestamp' => current_time('mysql'),
        'tests' => array()
    );

    try {
        // Test 1: Check if module file exists
        $module_file = plugin_dir_path(__FILE__) . 'modules/security/class-vd-license-security-integration-hub.php';
        $results['tests']['file_exists'] = file_exists($module_file);

        // Test 2: Check if dependency container exists
        $results['tests']['container_exists'] = class_exists('VD_License_Dependency_Container');

        // Test 3: Check if module loader exists
        $results['tests']['loader_exists'] = class_exists('VD_License_Module_Loader');

        if (class_exists('VD_License_Dependency_Container')) {
            $container = VD_License_Dependency_Container::get_instance();

            // Test 4: Check if integration hub is registered
            $results['tests']['integration_hub_registered'] = $container->has('security.integration_hub');

            if ($container->has('security.integration_hub')) {
                // Test 5: Try to load the module
                try {
                    $integration_hub = $container->get('security.integration_hub');
                    $results['tests']['module_loaded'] = ($integration_hub !== false);

                    if ($integration_hub && method_exists($integration_hub, 'get_module_info')) {
                        $module_info = $integration_hub->get_module_info();
                        $results['tests']['module_info_available'] = !empty($module_info);
                        $results['module_info'] = $module_info;
                    }
                } catch (Exception $e) {
                    $results['tests']['module_loaded'] = false;
                    $results['load_error'] = $e->getMessage();
                }
            }
        }

        // Summary
        $passed = count(array_filter($results['tests']));
        $total = count($results['tests']);
        $results['summary'] = "Passed: $passed/$total tests";

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['error'] = $e->getMessage();
    }

    wp_send_json($results);
}
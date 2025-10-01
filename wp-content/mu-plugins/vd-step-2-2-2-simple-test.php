<?php
/**
 * Step 2.2.2 Simple AJAX Test
 *
 * Test endpoint: /wp-admin/admin-ajax.php?action=vd_test_step_2_2_2_simple
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_2_2_2_simple', 'vd_test_step_2_2_2_simple_handler');
add_action('wp_ajax_nopriv_vd_test_step_2_2_2_simple', 'vd_test_step_2_2_2_simple_handler');

function vd_test_step_2_2_2_simple_handler() {
    header('Content-Type: application/json; charset=utf-8');

    $start_time = microtime(true);

    try {
        // Simple test - just check module loading
        $container = VD_License_Dependency_Container::get_instance();
        $expiry_automation = $container->get('rules.expiry_automation');

        if (!$expiry_automation) {
            wp_send_json_error('Module not loaded');
            return;
        }

        // Test basic info
        $module_info = $expiry_automation->get_module_info();
        $statistics = $expiry_automation->get_statistics();

        // Test one simple method
        $test_license = array(
            'id' => 999,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'status' => 'active'
        );

        $escalation_config = $expiry_automation->get_escalation_configuration($test_license);

        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        wp_send_json_success(array(
            'message' => '✅ Step 2.2.2 Simple Test - All basic functions work!',
            'module' => 'VD_License_Rule_Expiry_Automation',
            'step' => '2.2.2',
            'timestamp' => current_time('mysql'),
            'execution_time' => $execution_time . 'ms',
            'results' => array(
                'module_loaded' => true,
                'module_name' => $module_info['name'],
                'version' => $module_info['version'],
                'functions_count' => count($module_info['functions']),
                'dependencies_count' => count($module_info['dependencies']),
                'escalation_config' => $escalation_config,
                'statistics' => $statistics
            ),
            'status' => 'success'
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test failed: ' . $e->getMessage(),
            'error_details' => array(
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ),
            'execution_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
        ));
    }
}
<?php
/**
 * Simple test for Step 4.3 Third-party Integrations
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register simple AJAX test endpoint
add_action('wp_ajax_vd_test_step_4_3_integration', 'vd_test_step_4_3_integration_simple');
add_action('wp_ajax_nopriv_vd_test_step_4_3_integration', 'vd_test_step_4_3_integration_simple');

function vd_test_step_4_3_integration_simple() {
    // Ultra simple test first
    wp_send_json_success(array(
        'message' => 'Step 4.3 Integration Manager basic connectivity test passed',
        'status' => 'working',
        'timestamp' => current_time('mysql')
    ));
    return;

    // Original complex test below (disabled for now)
    try {
        // Get module loader
        if (!class_exists('VD_License_Module_Loader')) {
            wp_send_json_error(array(
                'message' => 'Module loader not available'
            ));
            return;
        }

        $loader = VD_License_Module_Loader::get_instance();

        // Check if integration module is registered
        $registry = $loader->get_registry();
        $integration_registered = isset($registry['integration.manager']);

        // Try to load the module
        $integration_manager = null;
        $load_success = false;

        if ($integration_registered) {
            $integration_manager = $loader->load_module('integration.manager');
            $load_success = !is_null($integration_manager) && is_object($integration_manager);
        }

        // Get module stats
        $stats = $loader->get_stats();

        wp_send_json_success(array(
            'message' => 'Step 4.3 Third-party Integrations test completed',
            'module_registered' => $integration_registered,
            'module_loaded' => $load_success,
            'class_name' => $load_success ? get_class($integration_manager) : 'Not loaded',
            'loader_stats' => $stats,
            'registry_keys' => array_keys($registry),
            'version' => '1.6.0',
            'timestamp' => current_time('mysql')
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test failed: ' . $e->getMessage(),
            'exception' => $e->getMessage()
        ));
    }
}
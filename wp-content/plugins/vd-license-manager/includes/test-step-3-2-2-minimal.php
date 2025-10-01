<?php
/**
 * VD License Manager - Minimal Test for Step 3.2.2 Security Threat Detector
 *
 * Extremely basic test to isolate fatal error
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_minimal_test_step_3_2_2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_minimal_test_step_3_2_2', 'vd_minimal_test_step_3_2_2_handler');
add_action('wp_ajax_nopriv_vd_minimal_test_step_3_2_2', 'vd_minimal_test_step_3_2_2_handler');

/**
 * Minimal test handler for Step 3.2.2 Security Threat Detector
 */
function vd_minimal_test_step_3_2_2_handler() {
    try {
        $results = array(
            'status' => 'minimal_test_start',
            'timestamp' => current_time('mysql'),
            'steps' => array()
        );

        // Step 1: Get container
        $results['steps']['1_container'] = 'attempting';
        $container = VD_License_Dependency_Container::get_instance();
        if (!$container) {
            throw new Exception('Container failed');
        }
        $results['steps']['1_container'] = 'success';

        // Step 2: Get threat detector
        $results['steps']['2_get_module'] = 'attempting';
        $threat_detector = $container->get('security.threat_detector');
        if (!$threat_detector) {
            throw new Exception('Module failed');
        }
        $results['steps']['2_get_module'] = 'success';

        // Step 3: Test basic method (get_module_info)
        $results['steps']['3_module_info'] = 'attempting';
        $info = $threat_detector->get_module_info();
        $results['steps']['3_module_info'] = 'success';
        $results['module_name'] = $info['name'] ?? 'unknown';

        // Step 4: Test simple configuration method
        $results['steps']['4_get_config'] = 'attempting';
        $config = $threat_detector->get_configuration();
        $results['steps']['4_get_config'] = 'success';
        $results['has_config'] = !empty($config);

        $results['status'] = 'minimal_test_complete';
        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Minimal test failed: ' . $e->getMessage(),
            'results' => $results ?? array(),
            'trace' => $e->getTraceAsString()
        ));
    } catch (Error $e) {
        wp_send_json_error(array(
            'message' => 'Fatal minimal test error: ' . $e->getMessage(),
            'results' => $results ?? array(),
            'trace' => $e->getTraceAsString()
        ));
    }
}
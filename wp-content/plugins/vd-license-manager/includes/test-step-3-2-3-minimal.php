<?php
/**
 * VD License Manager - Minimal Test for Step 3.2.3 Security Privacy Manager
 *
 * Extremely basic test to isolate loading issues
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_minimal_test_step_3_2_3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_minimal_test_step_3_2_3', 'vd_minimal_test_step_3_2_3_handler');
add_action('wp_ajax_nopriv_vd_minimal_test_step_3_2_3', 'vd_minimal_test_step_3_2_3_handler');

/**
 * Minimal test handler for Step 3.2.3 Security Privacy Manager
 */
function vd_minimal_test_step_3_2_3_handler() {
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

        // Step 2: Check if privacy manager is registered
        $results['steps']['2_check_registration'] = 'attempting';
        $is_registered = $container->has('security.privacy_manager');
        $results['steps']['2_check_registration'] = $is_registered ? 'registered' : 'not_registered';

        // Step 3: Try to get privacy manager (this might fail)
        $results['steps']['3_get_module'] = 'attempting';
        try {
            $privacy_manager = $container->get('security.privacy_manager');
            if (!$privacy_manager) {
                $results['steps']['3_get_module'] = 'null_returned';
            } else {
                $results['steps']['3_get_module'] = 'success';
                $results['module_class'] = get_class($privacy_manager);
            }
        } catch (Exception $e) {
            $results['steps']['3_get_module'] = 'exception: ' . $e->getMessage();
        } catch (Error $e) {
            $results['steps']['3_get_module'] = 'fatal: ' . $e->getMessage();
        }

        // Step 4: Try basic method if we got the module
        if (isset($privacy_manager) && $privacy_manager) {
            $results['steps']['4_test_method'] = 'attempting';
            try {
                $config = $privacy_manager->get_configuration();
                $results['steps']['4_test_method'] = 'success';
                $results['has_config'] = !empty($config);
            } catch (Exception $e) {
                $results['steps']['4_test_method'] = 'method_exception: ' . $e->getMessage();
            }
        } else {
            $results['steps']['4_test_method'] = 'skipped_no_module';
        }

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
<?php
/**
 * VD License Manager - Isolate Specific Privacy Manager Test
 *
 * Test specific methods in isolation to identify issues
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_isolate_test_step_3_2_3&method=anonymize
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_isolate_test_step_3_2_3', 'vd_isolate_test_step_3_2_3_handler');
add_action('wp_ajax_nopriv_vd_isolate_test_step_3_2_3', 'vd_isolate_test_step_3_2_3_handler');

/**
 * Isolate test handler for Step 3.2.3 Security Privacy Manager
 */
function vd_isolate_test_step_3_2_3_handler() {
    $method_to_test = isset($_GET['method']) ? sanitize_text_field($_GET['method']) : 'anonymize';

    try {
        $results = array(
            'method_tested' => $method_to_test,
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

        // Step 2: Get privacy manager
        $results['steps']['2_get_module'] = 'attempting';
        $privacy_manager = $container->get('security.privacy_manager');
        if (!$privacy_manager) {
            throw new Exception('Privacy manager failed');
        }
        $results['steps']['2_get_module'] = 'success';

        // Step 3: Test specific method
        $results['steps']['3_test_method'] = 'attempting';

        switch ($method_to_test) {
            case 'anonymize':
                $test_data = array(
                    'email' => 'john.doe@example.com',
                    'firstname' => 'John'
                );
                $result = $privacy_manager->anonymize_user_data($test_data);
                $results['method_result'] = $result;
                $results['steps']['3_test_method'] = 'success';
                break;

            case 'pii_detect':
                $test_data = array(
                    'user_email' => 'sensitive@example.com',
                    'safe_field' => 'public_information'
                );
                $result = $privacy_manager->detect_and_mask_pii($test_data);
                $results['method_result'] = $result;
                $results['steps']['3_test_method'] = 'success';
                break;

            case 'basic_config':
                $result = $privacy_manager->get_configuration();
                $results['method_result'] = $result;
                $results['steps']['3_test_method'] = 'success';
                break;

            default:
                throw new Exception('Unknown method: ' . $method_to_test);
        }

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Isolate test failed: ' . $e->getMessage(),
            'method_tested' => $method_to_test,
            'results' => $results ?? array(),
            'trace' => $e->getTraceAsString()
        ));
    } catch (Error $e) {
        wp_send_json_error(array(
            'message' => 'Fatal isolate test error: ' . $e->getMessage(),
            'method_tested' => $method_to_test,
            'results' => $results ?? array(),
            'trace' => $e->getTraceAsString()
        ));
    }
}
<?php
/**
 * VD License Manager - Debug Step 3.2.3 Security Privacy Manager
 *
 * Debug endpoint to identify loading issues with the Security Privacy Manager
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_debug_step_3_2_3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_debug_step_3_2_3', 'vd_debug_step_3_2_3_handler');
add_action('wp_ajax_nopriv_vd_debug_step_3_2_3', 'vd_debug_step_3_2_3_handler');

/**
 * Debug handler for Step 3.2.3 Security Privacy Manager
 */
function vd_debug_step_3_2_3_handler() {
    try {
        $debug_info = array(
            'step' => 'initialization',
            'timestamp' => current_time('mysql'),
            'debug_steps' => array()
        );

        // Step 1: Check if dependency container exists
        $debug_info['debug_steps']['1_container_class'] = class_exists('VD_License_Dependency_Container') ? 'exists' : 'missing';

        if (!class_exists('VD_License_Dependency_Container')) {
            wp_send_json_error(array(
                'message' => 'VD_License_Dependency_Container class not found',
                'debug_info' => $debug_info
            ));
            return;
        }

        // Step 2: Get container instance
        $debug_info['step'] = 'container_instance';
        $container = VD_License_Dependency_Container::get_instance();
        $debug_info['debug_steps']['2_container_instance'] = $container ? 'success' : 'failed';

        if (!$container) {
            wp_send_json_error(array(
                'message' => 'Failed to get dependency container instance',
                'debug_info' => $debug_info
            ));
            return;
        }

        // Step 3: Check if module loader exists
        $debug_info['step'] = 'module_loader_check';
        $debug_info['debug_steps']['3_module_loader_class'] = class_exists('VD_License_Module_Loader') ? 'exists' : 'missing';

        // Step 4: Check if privacy manager is registered
        $debug_info['step'] = 'privacy_manager_registration';
        $debug_info['debug_steps']['4_privacy_manager_registered'] = $container->has('security.privacy_manager') ? 'registered' : 'not_registered';

        // Step 5: Check if privacy manager class file exists
        $debug_info['step'] = 'privacy_manager_file_check';
        $privacy_manager_file = VD_LM_PATH . 'includes/modules/security/class-vd-license-security-privacy-manager.php';
        $debug_info['debug_steps']['5_privacy_manager_file'] = file_exists($privacy_manager_file) ? 'exists' : 'missing';
        $debug_info['privacy_manager_file_path'] = $privacy_manager_file;

        // Step 6: Check if privacy manager class exists (try to load)
        $debug_info['step'] = 'privacy_manager_class_check';
        if (file_exists($privacy_manager_file)) {
            try {
                require_once $privacy_manager_file;
                $debug_info['debug_steps']['6_privacy_manager_class'] = class_exists('VD\\LicenseManager\\Security\\Privacy\\VD_License_Security_Privacy_Manager') ? 'loaded' : 'class_not_found';
            } catch (Exception $e) {
                $debug_info['debug_steps']['6_privacy_manager_class'] = 'load_error: ' . $e->getMessage();
            } catch (Error $e) {
                $debug_info['debug_steps']['6_privacy_manager_class'] = 'fatal_error: ' . $e->getMessage();
            }
        } else {
            $debug_info['debug_steps']['6_privacy_manager_class'] = 'file_missing';
        }

        // Step 7: Try to get privacy manager instance
        $debug_info['step'] = 'privacy_manager_instance';
        try {
            $privacy_manager = $container->get('security.privacy_manager');
            $debug_info['debug_steps']['7_privacy_manager_instance'] = $privacy_manager ? 'success' : 'failed';

            if ($privacy_manager) {
                $debug_info['privacy_manager_class'] = get_class($privacy_manager);
                $debug_info['privacy_manager_methods'] = get_class_methods($privacy_manager);
            }
        } catch (Exception $e) {
            $debug_info['debug_steps']['7_privacy_manager_instance'] = 'exception: ' . $e->getMessage();
        } catch (Error $e) {
            $debug_info['debug_steps']['7_privacy_manager_instance'] = 'fatal: ' . $e->getMessage();
        }

        // Step 8: Check module loader registry
        $debug_info['step'] = 'module_registry_check';
        try {
            $module_loader = $container->get('module_loader');
            if ($module_loader) {
                $registry = $module_loader->get_registry();
                $debug_info['debug_steps']['8_module_registry'] = 'retrieved';
                $debug_info['registered_modules'] = array_keys($registry);
                $debug_info['privacy_manager_in_registry'] = isset($registry['security.privacy_manager']) ? 'yes' : 'no';

                if (isset($registry['security.privacy_manager'])) {
                    $debug_info['privacy_manager_config'] = $registry['security.privacy_manager'];
                }
            } else {
                $debug_info['debug_steps']['8_module_registry'] = 'module_loader_failed';
            }
        } catch (Exception $e) {
            $debug_info['debug_steps']['8_module_registry'] = 'exception: ' . $e->getMessage();
        }

        $debug_info['step'] = 'debug_complete';
        wp_send_json_success($debug_info);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Debug failed: ' . $e->getMessage(),
            'debug_info' => $debug_info ?? array(),
            'trace' => $e->getTraceAsString()
        ));
    } catch (Error $e) {
        wp_send_json_error(array(
            'message' => 'Fatal debug error: ' . $e->getMessage(),
            'debug_info' => $debug_info ?? array(),
            'trace' => $e->getTraceAsString()
        ));
    }
}
<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Step 4.1: API Framework Debug Test
 * Simple debug test to identify issues
 */

// AJAX handler for debugging
add_action('wp_ajax_vd_test_step_4_1_debug', 'vd_test_step_4_1_debug_handler');
add_action('wp_ajax_nopriv_vd_test_step_4_1_debug', 'vd_test_step_4_1_debug_handler');

function vd_test_step_4_1_debug_handler() {
    $debug_info = array(
        'step' => 'Step 4.1 Debug',
        'timestamp' => current_time('Y-m-d H:i:s'),
        'status' => 'running',
        'checks' => array()
    );

    try {
        // Check 1: Basic WordPress functions
        $debug_info['checks']['wordpress'] = array(
            'wp_send_json_exists' => function_exists('wp_send_json'),
            'current_time_exists' => function_exists('current_time'),
            'is_admin_exists' => function_exists('is_admin')
        );

        // Check 2: Module loader availability
        $debug_info['checks']['module_loader'] = array(
            'class_exists' => class_exists('VD_License_Module_Loader'),
            'can_get_instance' => false,
            'instance_type' => 'N/A'
        );

        if (class_exists('VD_License_Module_Loader')) {
            try {
                $loader = VD_License_Module_Loader::get_instance();
                $debug_info['checks']['module_loader']['can_get_instance'] = true;
                $debug_info['checks']['module_loader']['instance_type'] = get_class($loader);
            } catch (Exception $e) {
                $debug_info['checks']['module_loader']['error'] = $e->getMessage();
            }
        }

        // Check 3: API framework file existence
        $api_file = plugin_dir_path(__FILE__) . 'modules/api/class-vd-license-api-framework.php';
        $debug_info['checks']['api_file'] = array(
            'path' => $api_file,
            'exists' => file_exists($api_file),
            'readable' => is_readable($api_file)
        );

        // Check 4: Try to include the API framework file
        if (file_exists($api_file)) {
            try {
                require_once $api_file;
                $debug_info['checks']['api_include'] = array(
                    'included' => true,
                    'class_exists' => class_exists('VD\\LicenseManager\\API\\VD_License_API_Framework')
                );
            } catch (Exception $e) {
                $debug_info['checks']['api_include'] = array(
                    'included' => false,
                    'error' => $e->getMessage()
                );
            } catch (Error $e) {
                $debug_info['checks']['api_include'] = array(
                    'included' => false,
                    'fatal_error' => $e->getMessage()
                );
            }
        }

        // Check 5: Module registry
        if (class_exists('VD_License_Module_Loader')) {
            try {
                $loader = VD_License_Module_Loader::get_instance();
                $registry = $loader->get_registry();
                $debug_info['checks']['module_registry'] = array(
                    'has_api_framework' => isset($registry['api.framework']),
                    'total_modules' => count($registry)
                );
            } catch (Exception $e) {
                $debug_info['checks']['module_registry'] = array(
                    'error' => $e->getMessage()
                );
            }
        }

        // Check 6: Try loading the module
        if (class_exists('VD_License_Module_Loader')) {
            try {
                $loader = VD_License_Module_Loader::get_instance();
                $api_framework = $loader->load_module('api.framework');
                $debug_info['checks']['module_loading'] = array(
                    'loaded' => $api_framework !== false,
                    'class_name' => $api_framework ? get_class($api_framework) : 'N/A'
                );
            } catch (Exception $e) {
                $debug_info['checks']['module_loading'] = array(
                    'loaded' => false,
                    'error' => $e->getMessage()
                );
            } catch (Error $e) {
                $debug_info['checks']['module_loading'] = array(
                    'loaded' => false,
                    'fatal_error' => $e->getMessage()
                );
            }
        }

        $debug_info['status'] = 'completed';

    } catch (Exception $e) {
        $debug_info['status'] = 'error';
        $debug_info['error'] = $e->getMessage();
    } catch (Error $e) {
        $debug_info['status'] = 'fatal_error';
        $debug_info['fatal_error'] = $e->getMessage();
    }

    wp_send_json($debug_info);
}
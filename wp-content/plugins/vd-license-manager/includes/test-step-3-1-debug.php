<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug test for Step 3.1 Security Validator
 * Simple debug to identify the issue causing fatal errors
 */

// AJAX handler for debugging security validator
add_action('wp_ajax_vd_test_step_3_1_debug', 'vd_test_step_3_1_debug_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_1_debug', 'vd_test_step_3_1_debug_handler');

function vd_test_step_3_1_debug_handler() {
    $debug_info = array(
        'step' => 'Step 3.1 Security Validator Debug',
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

        // Check 2: Container availability
        $debug_info['checks']['container'] = array(
            'class_exists' => class_exists('VD_License_Dependency_Container'),
            'can_get_instance' => false,
            'instance_type' => 'N/A'
        );

        if (class_exists('VD_License_Dependency_Container')) {
            try {
                $container = VD_License_Dependency_Container::get_instance();
                $debug_info['checks']['container']['can_get_instance'] = true;
                $debug_info['checks']['container']['instance_type'] = get_class($container);
            } catch (Exception $e) {
                $debug_info['checks']['container']['error'] = $e->getMessage();
            }
        }

        // Check 3: Module loader availability
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

        // Check 4: Security validator module
        if (class_exists('VD_License_Dependency_Container')) {
            try {
                $container = VD_License_Dependency_Container::get_instance();
                $security_validator = $container->get('security.validator');
                $debug_info['checks']['security_validator'] = array(
                    'loaded' => $security_validator !== false,
                    'class_name' => $security_validator ? get_class($security_validator) : 'N/A'
                );
            } catch (Exception $e) {
                $debug_info['checks']['security_validator'] = array(
                    'loaded' => false,
                    'error' => $e->getMessage()
                );
            }
        }

        // Check 5: Original test file functions
        $test_functions = array(
            'vd_test_step_3_1_security_validator',
            'test_security_validator_module_loading',
            'test_user_security_context_analysis',
            'test_security_score_calculation'
        );

        $debug_info['checks']['test_functions'] = array();
        foreach ($test_functions as $func) {
            $debug_info['checks']['test_functions'][$func] = function_exists($func);
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
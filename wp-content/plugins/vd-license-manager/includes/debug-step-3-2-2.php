<?php
/**
 * Simple debug endpoint for Step 3.2.2 Security Threat Detector
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register simple debug AJAX handler
add_action('wp_ajax_vd_debug_step_3_2_2', 'vd_debug_step_3_2_2_handler');
add_action('wp_ajax_nopriv_vd_debug_step_3_2_2', 'vd_debug_step_3_2_2_handler');

function vd_debug_step_3_2_2_handler() {
    try {
        $debug_info = array(
            'status' => 'debug_endpoint_working',
            'timestamp' => current_time('mysql'),
            'step' => '3.2.2_threat_detector_debug',
            'files_exist' => array(),
            'classes_loaded' => array(),
            'container_status' => 'not_checked',
            'module_loading' => array()
        );

        // Check if threat detector file exists
        $threat_detector_file = VD_LM_PATH . 'includes/modules/security/class-vd-license-security-threat-detector.php';
        $debug_info['files_exist']['threat_detector'] = file_exists($threat_detector_file);
        $debug_info['threat_detector_path'] = $threat_detector_file;

        // Check if VD classes are loaded
        $declared_classes = get_declared_classes();
        $vd_classes = array_filter($declared_classes, function($class) {
            return strpos($class, 'VD_') === 0;
        });
        $debug_info['classes_loaded'] = $vd_classes;

        // Try to get container instance
        if (class_exists('VD_License_Dependency_Container')) {
            try {
                $container = VD_License_Dependency_Container::get_instance();
                if ($container) {
                    $debug_info['container_status'] = 'initialized';
                    $debug_info['registered_services'] = $container->get_service_ids();

                    // Try to load event logger first (dependency)
                    try {
                        $event_logger = $container->get('security.event_logger');
                        if ($event_logger) {
                            $debug_info['module_loading']['event_logger'] = 'loaded_successfully';
                        } else {
                            $debug_info['module_loading']['event_logger'] = 'failed_to_load';
                        }
                    } catch (Exception $e) {
                        $debug_info['module_loading']['event_logger'] = 'error: ' . $e->getMessage();
                    }

                    // Try to load threat detector
                    try {
                        $threat_detector = $container->get('security.threat_detector');
                        if ($threat_detector) {
                            $debug_info['module_loading']['threat_detector'] = 'loaded_successfully';
                            $debug_info['threat_detector_info'] = $threat_detector->get_module_info();
                        } else {
                            $debug_info['module_loading']['threat_detector'] = 'failed_to_load';
                        }
                    } catch (Exception $e) {
                        $debug_info['module_loading']['threat_detector'] = 'error: ' . $e->getMessage();
                    } catch (Error $e) {
                        $debug_info['module_loading']['threat_detector'] = 'fatal_error: ' . $e->getMessage();
                    }
                } else {
                    $debug_info['container_status'] = 'failed_to_get_instance';
                }
            } catch (Exception $e) {
                $debug_info['container_status'] = 'error: ' . $e->getMessage();
            } catch (Error $e) {
                $debug_info['container_status'] = 'fatal_error: ' . $e->getMessage();
            }
        } else {
            $debug_info['container_status'] = 'class_not_found';
        }

        // Try manual file inclusion test
        try {
            if (file_exists($threat_detector_file)) {
                // Test if file can be included without errors
                ob_start();
                $include_result = include_once $threat_detector_file;
                $include_output = ob_get_clean();

                $debug_info['manual_include'] = array(
                    'result' => $include_result,
                    'output' => $include_output,
                    'status' => 'success'
                );

                // Check if class exists after include
                if (class_exists('VD\LicenseManager\Security\Detection\VD_License_Security_Threat_Detector')) {
                    $debug_info['manual_include']['class_exists'] = true;
                } else {
                    $debug_info['manual_include']['class_exists'] = false;
                }
            }
        } catch (Exception $e) {
            $debug_info['manual_include'] = array(
                'status' => 'exception',
                'message' => $e->getMessage()
            );
        } catch (Error $e) {
            $debug_info['manual_include'] = array(
                'status' => 'fatal_error',
                'message' => $e->getMessage()
            );
        }

        wp_send_json_success($debug_info);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Debug failed: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
    } catch (Error $e) {
        wp_send_json_error(array(
            'message' => 'Fatal debug error: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
    }
}
<?php
/**
 * Simple debug endpoint for Step 3.2.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register simple debug AJAX handler
add_action('wp_ajax_vd_debug_step_3_2_1', 'vd_debug_step_3_2_1_handler');
add_action('wp_ajax_nopriv_vd_debug_step_3_2_1', 'vd_debug_step_3_2_1_handler');

function vd_debug_step_3_2_1_handler() {
    try {
        $debug_info = array(
            'status' => 'debug_endpoint_working',
            'timestamp' => current_time('mysql'),
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_path' => VD_LM_PATH ?? 'VD_LM_PATH not defined',
            'classes_loaded' => array(),
            'files_exist' => array(),
            'container_status' => 'not_checked'
        );

        // Check if VD classes are loaded
        $declared_classes = get_declared_classes();
        $vd_classes = array_filter($declared_classes, function($class) {
            return strpos($class, 'VD_') === 0;
        });
        $debug_info['classes_loaded'] = $vd_classes;

        // Check if key files exist
        $key_files = array(
            'module_loader' => VD_LM_PATH . 'includes/class-vd-license-module-loader.php',
            'dependency_container' => VD_LM_PATH . 'includes/class-vd-license-dependency-container.php',
            'event_logger' => VD_LM_PATH . 'includes/modules/security/class-vd-license-security-event-logger.php'
        );

        foreach ($key_files as $name => $path) {
            $debug_info['files_exist'][$name] = file_exists($path);
        }

        // Try to get container instance
        if (class_exists('VD_License_Dependency_Container')) {
            try {
                $container = VD_License_Dependency_Container::get_instance();
                if ($container) {
                    $debug_info['container_status'] = 'initialized';
                    $debug_info['registered_services'] = $container->get_service_ids();

                    // Try to load event logger
                    try {
                        $event_logger = $container->get('security.event_logger');
                        if ($event_logger) {
                            $debug_info['event_logger_status'] = 'loaded_successfully';
                            $debug_info['event_logger_info'] = $event_logger->get_module_info();
                        } else {
                            $debug_info['event_logger_status'] = 'failed_to_load';
                        }
                    } catch (Exception $e) {
                        $debug_info['event_logger_status'] = 'error: ' . $e->getMessage();
                    }
                } else {
                    $debug_info['container_status'] = 'failed_to_get_instance';
                }
            } catch (Exception $e) {
                $debug_info['container_status'] = 'error: ' . $e->getMessage();
            }
        } else {
            $debug_info['container_status'] = 'class_not_found';
        }

        wp_send_json_success($debug_info);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Debug failed: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
    }
}
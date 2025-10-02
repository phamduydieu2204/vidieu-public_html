<?php
/**
 * Minimal debug endpoint to identify fatal errors
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_minimal_debug', 'vd_minimal_debug_handler');
add_action('wp_ajax_nopriv_vd_minimal_debug', 'vd_minimal_debug_handler');

function vd_minimal_debug_handler() {
    try {
        $result = array(
            'status' => 'success',
            'timestamp' => current_time('mysql'),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_path' => VD_LM_PATH,
            'plugin_url' => VD_LM_URL,
            'memory_usage' => memory_get_usage(true),
            'error_log_enabled' => ini_get('log_errors') ? 'yes' : 'no',
            'classes_loaded' => array()
        );

        // Check if core classes exist
        $classes_to_check = array(
            'VD_License_Manager',
            'VD_License_Module_Loader',
            'VD_License_Dependency_Container'
        );

        foreach ($classes_to_check as $class) {
            $result['classes_loaded'][$class] = class_exists($class) ? 'yes' : 'no';
        }

        wp_send_json_success($result);
    } catch (Exception $e) {
        wp_send_json_error('Exception: ' . $e->getMessage());
    } catch (Error $e) {
        wp_send_json_error('Fatal Error: ' . $e->getMessage());
    }
}
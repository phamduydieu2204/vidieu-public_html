<?php
/**
 * Simple debug for Step 3.2.4
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_debug_storage_manager', 'vd_debug_storage_manager_handler');
add_action('wp_ajax_nopriv_vd_debug_storage_manager', 'vd_debug_storage_manager_handler');

function vd_debug_storage_manager_handler() {
    try {
        $container = VD_License_Dependency_Container::get_instance();

        $result = array(
            'container_exists' => $container ? 'yes' : 'no',
            'storage_manager_registered' => $container ? ($container->has('security.storage_manager') ? 'yes' : 'no') : 'unknown',
            'file_exists' => file_exists(VD_LM_PATH . 'includes/modules/security/class-vd-license-security-storage-manager.php') ? 'yes' : 'no',
            'test_file_exists' => file_exists(VD_LM_PATH . 'includes/test-step-3-2-4-security-storage-manager.php') ? 'yes' : 'no'
        );

        wp_send_json_success($result);
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
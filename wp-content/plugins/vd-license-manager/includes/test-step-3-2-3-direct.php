<?php
/**
 * VD License Manager - Direct Test for Step 3.2.3 Security Privacy Manager
 *
 * Very simple direct test to bypass all complex frameworks
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_direct_test_step_3_2_3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_direct_test_step_3_2_3', 'vd_direct_test_step_3_2_3_handler');
add_action('wp_ajax_nopriv_vd_direct_test_step_3_2_3', 'vd_direct_test_step_3_2_3_handler');

/**
 * Direct test handler for Step 3.2.3 Security Privacy Manager
 */
function vd_direct_test_step_3_2_3_handler() {
    // Simple response without try-catch to see the actual error
    $container = VD_License_Dependency_Container::get_instance();
    $privacy_manager = $container->get('security.privacy_manager');

    // Simple test data
    $test_data = array('email' => 'test@example.com');

    // Call the method that's causing issues
    $result = $privacy_manager->anonymize_user_data($test_data);

    wp_send_json_success(array(
        'message' => 'Direct test passed',
        'result' => $result
    ));
}
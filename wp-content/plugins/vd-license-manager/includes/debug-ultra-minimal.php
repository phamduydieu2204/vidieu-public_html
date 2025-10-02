<?php
/**
 * Ultra minimal debug - không dependencies
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_ultra_minimal', 'vd_ultra_minimal_handler');
add_action('wp_ajax_nopriv_vd_ultra_minimal', 'vd_ultra_minimal_handler');

function vd_ultra_minimal_handler() {
    wp_send_json_success(array(
        'message' => 'WordPress AJAX works',
        'time' => date('Y-m-d H:i:s'),
        'php' => PHP_VERSION
    ));
}
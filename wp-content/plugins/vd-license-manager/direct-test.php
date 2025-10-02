<?php
/**
 * Direct test file - không qua WordPress plugin system
 */

// Load WordPress
require_once('../../../wp-load.php');

// Direct response
header('Content-Type: application/json');
echo json_encode(array(
    'status' => 'direct_test_works',
    'time' => date('Y-m-d H:i:s'),
    'php' => PHP_VERSION,
    'wp_version' => get_bloginfo('version'),
    'plugin_dir' => __DIR__,
    'vd_plugin_exists' => file_exists(__DIR__ . '/vd-license-manager.php') ? 'yes' : 'no',
    'constants_defined' => array(
        'VD_LM_VERSION' => defined('VD_LM_VERSION') ? VD_LM_VERSION : 'not_defined',
        'VD_LM_PATH' => defined('VD_LM_PATH') ? VD_LM_PATH : 'not_defined'
    )
));
exit;
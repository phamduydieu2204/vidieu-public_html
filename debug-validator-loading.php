<?php
/**
 * Debug VD_License_Validator Loading
 * Simple test to isolate the loading issue
 */

// Include WordPress
require_once dirname(__FILE__) . '/wp-load.php';

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

echo '<h1>Debug VD_License_Validator Loading</h1>';

// Test 1: Check file existence
$validator_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-license-validator.php';
echo '<h2>1. File Existence Check</h2>';
echo '<p>File path: ' . $validator_file . '</p>';
echo '<p>File exists: ' . (file_exists($validator_file) ? 'YES' : 'NO') . '</p>';

if (file_exists($validator_file)) {
    echo '<p>File size: ' . filesize($validator_file) . ' bytes</p>';
    echo '<p>File is readable: ' . (is_readable($validator_file) ? 'YES' : 'NO') . '</p>';
}

// Test 2: Check VD_LM_PATH constant
echo '<h2>2. Constants Check</h2>';
echo '<p>VD_LM_PATH defined: ' . (defined('VD_LM_PATH') ? 'YES' : 'NO') . '</p>';
if (defined('VD_LM_PATH')) {
    echo '<p>VD_LM_PATH value: ' . VD_LM_PATH . '</p>';
    $expected_file = VD_LM_PATH . 'includes/class-vd-license-validator.php';
    echo '<p>Expected file path: ' . $expected_file . '</p>';
    echo '<p>Expected file exists: ' . (file_exists($expected_file) ? 'YES' : 'NO') . '</p>';
}

// Test 3: Try to load plugin main file
echo '<h2>3. Plugin Main File Loading</h2>';
$plugin_main = WP_PLUGIN_DIR . '/vd-license-manager/vd-license-manager.php';
echo '<p>Plugin main file: ' . $plugin_main . '</p>';
echo '<p>Plugin main exists: ' . (file_exists($plugin_main) ? 'YES' : 'NO') . '</p>';

if (!defined('VD_LM_PATH') && file_exists($plugin_main)) {
    echo '<p>Loading plugin main file...</p>';
    require_once $plugin_main;
    echo '<p>VD_LM_PATH now defined: ' . (defined('VD_LM_PATH') ? 'YES' : 'NO') . '</p>';
    if (defined('VD_LM_PATH')) {
        echo '<p>VD_LM_PATH value: ' . VD_LM_PATH . '</p>';
    }
}

// Test 4: Try to load validator file directly
echo '<h2>4. Direct Validator File Loading</h2>';
echo '<p>VD_License_Validator class exists before loading: ' . (class_exists('VD_License_Validator') ? 'YES' : 'NO') . '</p>';

if (defined('VD_LM_PATH')) {
    $validator_file_correct = VD_LM_PATH . 'includes/class-vd-license-validator.php';
    echo '<p>Correct validator file path: ' . $validator_file_correct . '</p>';

    if (file_exists($validator_file_correct)) {
        echo '<p>Attempting to load validator file...</p>';

        // Capture any errors
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            require_once $validator_file_correct;
            echo '<p>✅ File loaded successfully</p>';
        } catch (Exception $e) {
            echo '<p>❌ Exception during loading: ' . $e->getMessage() . '</p>';
        } catch (Error $e) {
            echo '<p>❌ Fatal error during loading: ' . $e->getMessage() . '</p>';
        }

        echo '<p>VD_License_Validator class exists after loading: ' . (class_exists('VD_License_Validator') ? 'YES' : 'NO') . '</p>';
    } else {
        echo '<p>❌ Validator file not found at correct path</p>';
    }
} else {
    echo '<p>❌ VD_LM_PATH not defined</p>';
}

// Test 5: Check for any PHP errors
echo '<h2>5. Error Information</h2>';
$last_error = error_get_last();
if ($last_error) {
    echo '<p>Last PHP error:</p>';
    echo '<pre>' . print_r($last_error, true) . '</pre>';
} else {
    echo '<p>No recent PHP errors</p>';
}

// Test 6: Check memory and system info
echo '<h2>6. System Information</h2>';
echo '<p>PHP Version: ' . phpversion() . '</p>';
echo '<p>Memory Limit: ' . ini_get('memory_limit') . '</p>';
echo '<p>Memory Usage: ' . number_format(memory_get_usage(true) / 1024 / 1024, 2) . ' MB</p>';
echo '<p>Max Execution Time: ' . ini_get('max_execution_time') . ' seconds</p>';

?>
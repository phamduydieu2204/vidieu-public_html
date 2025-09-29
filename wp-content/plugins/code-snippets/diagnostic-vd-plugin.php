<?php
/**
 * VD License Manager - Plugin Loading Diagnostic
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🔧 VD License Manager Plugin Diagnostic - " . current_time('Y-m-d H:i:s') . "</h2>";

// 1. Check WordPress basics
echo "<h3>1. WordPress Environment</h3>";
echo "WordPress Version: " . get_bloginfo('version') . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Admin User: " . (current_user_can('manage_options') ? 'YES' : 'NO') . "<br>";
echo "Is Admin: " . (is_admin() ? 'YES' : 'NO') . "<br>";

// 2. Check active plugins
echo "<h3>2. Plugin Status</h3>";
$active_plugins = get_option('active_plugins', array());
$vd_plugin_found = false;
$vd_plugin_path = '';

foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'vd-license-manager') !== false) {
        $vd_plugin_found = true;
        $vd_plugin_path = $plugin;
        break;
    }
}

echo "VD License Manager in active plugins: " . ($vd_plugin_found ? "✅ YES" : "❌ NO") . "<br>";
if ($vd_plugin_found) {
    echo "Plugin path: {$vd_plugin_path}<br>";
}

// 3. Check plugin files existence
echo "<h3>3. File System Check</h3>";
$plugin_dir = WP_PLUGIN_DIR . '/vd-license-manager';
$main_file = $plugin_dir . '/vd-license-manager.php';
$validator_file = $plugin_dir . '/includes/class-vd-license-validator.php';
$manager_file = $plugin_dir . '/includes/class-vd-license-manager.php';

echo "Plugin directory: " . (is_dir($plugin_dir) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
echo "Main plugin file: " . (file_exists($main_file) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
echo "License Manager class: " . (file_exists($manager_file) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
echo "License Validator class: " . (file_exists($validator_file) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";

if (file_exists($validator_file)) {
    echo "Validator file size: " . round(filesize($validator_file) / 1024, 1) . " KB<br>";
}

// 4. Check class loading
echo "<h3>4. Class Loading Status</h3>";
echo "VD_License_Manager class: " . (class_exists('VD_License_Manager') ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";
echo "VD_License_Validator class: " . (class_exists('VD_License_Validator') ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";

// Try manual loading if not loaded
if (!class_exists('VD_License_Validator') && file_exists($validator_file)) {
    echo "<h3>5. Manual Loading Test</h3>";
    echo "Attempting manual require_once...<br>";

    try {
        require_once $validator_file;
        echo "Manual require completed<br>";
        echo "Class after manual load: " . (class_exists('VD_License_Validator') ? "✅ NOW LOADED" : "❌ STILL NOT LOADED") . "<br>";

        if (class_exists('VD_License_Validator')) {
            $validator = VD_License_Validator::get_instance();
            echo "Instance creation: " . ($validator ? "✅ SUCCESS" : "❌ FAILED") . "<br>";

            if ($validator) {
                $test_result = $validator->validate_license_key_format('H10D-DIJD-14RC-SOLE-6KUV30');
                echo "Quick validation test: " . ($test_result ? "✅ WORKING" : "❌ FAILED") . "<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Manual loading error: " . $e->getMessage() . "<br>";
    }
}

// 5. Check WordPress hooks
echo "<h3>6. WordPress Hooks Check</h3>";
global $wp_filter;

$plugin_hooks = array();
if (isset($wp_filter['plugins_loaded'])) {
    foreach ($wp_filter['plugins_loaded']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_string($callback['function']) && strpos($callback['function'], 'vd_') !== false) {
                $plugin_hooks[] = $callback['function'];
            }
        }
    }
}

echo "VD plugin hooks found: " . count($plugin_hooks) . "<br>";
if (!empty($plugin_hooks)) {
    foreach ($plugin_hooks as $hook) {
        echo "- {$hook}<br>";
    }
}

// 6. Check constants
echo "<h3>7. Plugin Constants</h3>";
$vd_constants = array('VD_LM_VERSION', 'VD_LM_PATH', 'VD_LM_URL', 'VD_LM_FILE');
foreach ($vd_constants as $constant) {
    if (defined($constant)) {
        echo "✅ {$constant}: " . constant($constant) . "<br>";
    } else {
        echo "❌ {$constant}: NOT DEFINED<br>";
    }
}

// 7. Function availability
echo "<h3>8. Function Check</h3>";
$vd_functions = array('vd_license_manager_init', 'vd_check_requirements', 'vd_is_admin');
foreach ($vd_functions as $function) {
    echo "{$function}: " . (function_exists($function) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
}

// 8. Error checking
echo "<h3>9. Error Log Check</h3>";
$error_log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($error_log_file)) {
    echo "Debug log exists: ✅ YES<br>";
    echo "Debug log size: " . round(filesize($error_log_file) / 1024, 1) . " KB<br>";

    // Check for recent VD-related errors
    $log_content = file_get_contents($error_log_file);
    $vd_errors = substr_count($log_content, 'VD_License');
    echo "VD-related log entries: {$vd_errors}<br>";
} else {
    echo "Debug log: ❌ NOT FOUND<br>";
}

// Log this diagnostic
error_log("[VD Plugin Diagnostic] VD_License_Manager: " . (class_exists('VD_License_Manager') ? 'LOADED' : 'NOT_LOADED') .
          ", VD_License_Validator: " . (class_exists('VD_License_Validator') ? 'LOADED' : 'NOT_LOADED'));

echo "<p><strong>Diagnostic completed. Check results above.</strong></p>";
<?php
/**
 * VD License Manager - Plugin Activation Helper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🔧 VD License Manager Plugin Activation - " . current_time('Y-m-d H:i:s') . "</h2>";

// Check if user can activate plugins
if (!current_user_can('activate_plugins')) {
    echo "❌ You don't have permission to activate plugins.<br>";
    return;
}

// Plugin path
$plugin_file = 'vd-license-manager/vd-license-manager.php';

// Check if plugin exists
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
if (!file_exists($plugin_path)) {
    echo "❌ Plugin file not found: {$plugin_path}<br>";
    return;
}

echo "✅ Plugin file found: {$plugin_path}<br>";

// Check if already active
if (is_plugin_active($plugin_file)) {
    echo "✅ VD License Manager is already ACTIVE<br>";
} else {
    echo "⚠️ VD License Manager is NOT ACTIVE<br>";
    echo "Attempting to activate...<br>";

    // Attempt to activate
    $result = activate_plugin($plugin_file);

    if (is_wp_error($result)) {
        echo "❌ Activation failed: " . $result->get_error_message() . "<br>";
    } else {
        echo "✅ Plugin activated successfully!<br>";
    }
}

// Check status after activation attempt
echo "<h3>Current Status:</h3>";
if (is_plugin_active($plugin_file)) {
    echo "✅ Plugin Status: ACTIVE<br>";

    // Check if classes are now loaded
    echo "VD_License_Manager class: " . (class_exists('VD_License_Manager') ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";
    echo "VD_License_Validator class: " . (class_exists('VD_License_Validator') ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";

    // If classes not loaded, try to trigger loading
    if (!class_exists('VD_License_Validator')) {
        echo "<br>Classes not loaded yet. Triggering plugin initialization...<br>";

        // Try to call the init function
        if (function_exists('vd_license_manager_init')) {
            vd_license_manager_init();
            echo "Called vd_license_manager_init()<br>";
        }

        // Check again
        echo "After init - VD_License_Validator: " . (class_exists('VD_License_Validator') ? "✅ NOW LOADED" : "❌ STILL NOT LOADED") . "<br>";
    }

    if (class_exists('VD_License_Validator')) {
        echo "<br>✅ Running quick validation test...<br>";
        try {
            $validator = VD_License_Validator::get_instance();
            if ($validator) {
                $test_result = $validator->validate_license_key_format('H10D-DIJD-14RC-SOLE-6KUV30');
                echo "Validation test result: " . ($test_result ? "✅ WORKING" : "❌ FAILED") . "<br>";

                // Test enhanced methods
                if (method_exists($validator, 'get_detailed_validation')) {
                    echo "Enhanced validation method: ✅ AVAILABLE<br>";
                } else {
                    echo "Enhanced validation method: ❌ NOT AVAILABLE<br>";
                }
            }
        } catch (Exception $e) {
            echo "❌ Test error: " . $e->getMessage() . "<br>";
        }
    }

} else {
    echo "❌ Plugin Status: INACTIVE<br>";

    // Show all plugins for reference
    echo "<h3>All Plugins Status:</h3>";
    $all_plugins = get_plugins();
    foreach ($all_plugins as $plugin_path => $plugin_data) {
        if (strpos($plugin_path, 'vd-') !== false || strpos($plugin_data['Name'], 'VD') !== false) {
            $active = is_plugin_active($plugin_path) ? '✅ ACTIVE' : '❌ INACTIVE';
            echo "{$plugin_data['Name']}: {$active} ({$plugin_path})<br>";
        }
    }
}

// Log the result
error_log("[VD Plugin Activation] Status: " . (is_plugin_active($plugin_file) ? 'ACTIVE' : 'INACTIVE') .
          ", Classes loaded: " . (class_exists('VD_License_Validator') ? 'YES' : 'NO'));

echo "<p><strong>Activation check completed.</strong></p>";
<?php
/**
 * Test AJAX Handler Loading
 *
 * This script tests if the VD License Manager AJAX handler is properly loaded.
 * Access this via: /test-ajax-handler.php
 */

// Load WordPress
require_once dirname(__FILE__) . '/wp-config.php';

echo "<h2>VD License Manager - AJAX Handler Test</h2>\n";

// Check if plugin is active
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$plugin_file = 'vd-license-manager/vd-license-manager.php';
$is_active = is_plugin_active($plugin_file);

echo "<h3>Plugin Status:</h3>\n";
if ($is_active) {
    echo "<p style='color: green;'>✓ VD License Manager is ACTIVE</p>\n";
} else {
    echo "<p style='color: red;'>✗ VD License Manager is NOT ACTIVE</p>\n";
    echo "<p>Please activate the plugin first.</p>\n";
    exit;
}

// Check if constants are defined
echo "<h3>Constants Check:</h3>\n";
$constants = ['VD_PLUGIN_DIR', 'VD_PLUGIN_URL', 'VD_PLUGIN_BASENAME', 'VD_TEXT_DOMAIN'];

foreach ($constants as $const) {
    if (defined($const)) {
        echo "<p style='color: green;'>✓ $const = " . constant($const) . "</p>\n";
    } else {
        echo "<p style='color: red;'>✗ $const is NOT DEFINED</p>\n";
    }
}

// Check if AJAX handler file exists
echo "<h3>AJAX Handler File Check:</h3>\n";
$ajax_file = WP_PLUGIN_DIR . '/vd-license-manager/admin/class-vd-lm-share-configs-ajax.php';

if (file_exists($ajax_file)) {
    echo "<p style='color: green;'>✓ AJAX handler file exists: $ajax_file</p>\n";
} else {
    echo "<p style='color: red;'>✗ AJAX handler file NOT FOUND: $ajax_file</p>\n";
}

// Check if class exists
echo "<h3>Class Check:</h3>\n";
if (class_exists('VD_LM_Share_Configs_Ajax')) {
    echo "<p style='color: green;'>✓ VD_LM_Share_Configs_Ajax class is loaded</p>\n";
} else {
    echo "<p style='color: red;'>✗ VD_LM_Share_Configs_Ajax class is NOT loaded</p>\n";
}

// Check if AJAX action is registered
echo "<h3>AJAX Action Check:</h3>\n";
global $wp_filter;

if (isset($wp_filter['wp_ajax_vd_save_share_config'])) {
    echo "<p style='color: green;'>✓ wp_ajax_vd_save_share_config action is registered</p>\n";

    // Show callbacks
    $callbacks = $wp_filter['wp_ajax_vd_save_share_config']->callbacks;
    foreach ($callbacks as $priority => $callback_group) {
        foreach ($callback_group as $callback) {
            $function_name = 'Unknown';
            if (is_array($callback['function'])) {
                if (is_object($callback['function'][0])) {
                    $function_name = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                } else {
                    $function_name = $callback['function'][0] . '::' . $callback['function'][1];
                }
            } else {
                $function_name = $callback['function'];
            }
            echo "<p style='margin-left: 20px;'>→ Priority $priority: $function_name</p>\n";
        }
    }
} else {
    echo "<p style='color: red;'>✗ wp_ajax_vd_save_share_config action is NOT registered</p>\n";
}

// Test AJAX URL generation
echo "<h3>AJAX URL Test:</h3>\n";
$ajax_url = admin_url('admin-ajax.php');
echo "<p>AJAX URL: $ajax_url</p>\n";

// Show debug log tail (last 20 lines)
echo "<h3>Debug Log (last 20 lines):</h3>\n";
$debug_log = WP_CONTENT_DIR . '/debug.log';

if (file_exists($debug_log)) {
    $lines = file($debug_log);
    $last_lines = array_slice($lines, -20);

    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>\n";
    foreach ($last_lines as $line) {
        // Highlight VD entries
        if (strpos($line, 'VD ') !== false) {
            echo "<span style='background: yellow;'>" . esc_html($line) . "</span>";
        } else {
            echo esc_html($line);
        }
    }
    echo "</pre>\n";
} else {
    echo "<p style='color: orange;'>Debug log not found at: $debug_log</p>\n";
}

echo "<hr>\n";
echo "<h3>Instructions:</h3>\n";
echo "<ol>\n";
echo "<li>If all checks pass, the AJAX handler should work</li>\n";
echo "<li>Go to VD License Manager → Share Configs</li>\n";
echo "<li>Try saving a configuration</li>\n";
echo "<li>Check debug.log for AJAX messages</li>\n";
echo "<li>Delete this test file when done</li>\n";
echo "</ol>\n";

echo "<p><small>Test performed at: " . date('Y-m-d H:i:s') . "</small></p>\n";
?>
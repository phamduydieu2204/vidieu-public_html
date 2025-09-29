<?php
/**
 * VD License Manager - Simple Debug Test for Step 4.2.4.5.1a
 * URL: https://vidieu.vn/wp-admin/admin.php?vd_debug_step_4_2_4_5_1a=run
 */

// Check if we're in admin and test is requested
if (is_admin() && isset($_GET['vd_debug_step_4_2_4_5_1a']) && $_GET['vd_debug_step_4_2_4_5_1a'] === 'run') {

    // Basic PHP check
    echo "<h2>🔍 VD License Manager - Debug Step 4.2.4.5.1a</h2>";
    echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

    // Check if WordPress is loaded
    if (function_exists('wp_get_current_user')) {
        echo "<p>✅ WordPress is loaded</p>";
    } else {
        echo "<p>❌ WordPress not loaded</p>";
        return;
    }

    // Check plugin file exists
    $plugin_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
    if (file_exists($plugin_file)) {
        echo "<p>✅ Validator file exists: " . $plugin_file . "</p>";
    } else {
        echo "<p>❌ Validator file not found: " . $plugin_file . "</p>";
        return;
    }

    // Try to include the file manually
    try {
        if (!class_exists('VD_License_Validator')) {
            include_once $plugin_file;
            echo "<p>✅ Validator file included</p>";
        } else {
            echo "<p>✅ VD_License_Validator already loaded</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error including validator: " . $e->getMessage() . "</p>";
        return;
    } catch (ParseError $e) {
        echo "<p>❌ Parse error in validator: " . $e->getMessage() . " on line " . $e->getLine() . "</p>";
        return;
    }

    // Check if class exists after include
    if (class_exists('VD_License_Validator')) {
        echo "<p>✅ VD_License_Validator class available</p>";

        // Try to create instance
        try {
            $validator = new VD_License_Validator();
            echo "<p>✅ Validator instance created</p>";

            // Check if new methods exist
            $methods = ['track_status_history', 'get_status_history', 'get_status_statistics'];
            foreach ($methods as $method) {
                if (method_exists($validator, $method)) {
                    echo "<p>✅ Method {$method} exists</p>";

                    // Try to call the method
                    try {
                        if ($method === 'track_status_history') {
                            $result = $validator->track_status_history('test', 'active', 'inactive');
                        } elseif ($method === 'get_status_history') {
                            $result = $validator->get_status_history(1);
                        } else {
                            $result = $validator->get_status_statistics();
                        }
                        echo "<p>✅ Method {$method} callable, returned: " . json_encode($result) . "</p>";
                    } catch (Exception $e) {
                        echo "<p>⚠️ Method {$method} error: " . $e->getMessage() . "</p>";
                    }
                } else {
                    echo "<p>❌ Method {$method} not found</p>";
                }
            }

        } catch (Exception $e) {
            echo "<p>❌ Error creating validator: " . $e->getMessage() . "</p>";
        }

    } else {
        echo "<p>❌ VD_License_Validator class not found after include</p>";
    }

    echo "<p><strong>Debug completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><a href='" . admin_url('admin.php') . "'>Back to Admin</a></p>";

    // Stop execution to prevent other output
    exit;
}
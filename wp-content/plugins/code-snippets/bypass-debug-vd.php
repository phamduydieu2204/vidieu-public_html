<?php
/**
 * Bypass Debug Test - Skip Security Audit
 * URL: https://vidieu.vn/wp-admin/admin.php?vd_bypass_debug=run
 */

if (is_admin() && isset($_GET['vd_bypass_debug']) && $_GET['vd_bypass_debug'] === 'run') {

    echo "<h2>🔍 Bypass Debug - Skip Security Audit</h2>";

    // Step 1: Load only essential dependencies
    $dependencies = [
        'VD_Database_Manager' => ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-database-manager.php',
        'VD_Encryption_Manager' => ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-encryption-manager.php'
    ];

    foreach ($dependencies as $class => $file) {
        if (!class_exists($class) && file_exists($file)) {
            try {
                include_once $file;
                echo "<p>✅ {$class} loaded</p>";
            } catch (Exception $e) {
                echo "<p>❌ Error loading {$class}: " . $e->getMessage() . "</p>";
                exit;
            }
        }
    }

    // Step 2: Load validator without Security Audit
    $validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

    try {
        if (!class_exists('VD_License_Validator')) {
            include_once $validator_file;
        }
        echo "<p>✅ Validator class loaded (without Security Audit)</p>";

        // Step 3: Test instance creation
        try {
            $validator = VD_License_Validator::get_instance();
            echo "<p>🎉 SUCCESS! Validator instance created</p>";

            // Test our new methods
            $methods = ['track_status_history', 'get_status_history', 'get_status_statistics'];
            foreach ($methods as $method) {
                if (method_exists($validator, $method)) {
                    echo "<p>✅ Method {$method} exists</p>";

                    try {
                        if ($method === 'track_status_history') {
                            $result = $validator->track_status_history('test123', 'active', 'inactive');
                        } elseif ($method === 'get_status_history') {
                            $result = $validator->get_status_history(123);
                        } else {
                            $result = $validator->get_status_statistics();
                        }
                        echo "<p>✅ {$method} callable: " . json_encode($result) . "</p>";
                    } catch (Exception $e) {
                        echo "<p>⚠️ {$method} error: " . $e->getMessage() . "</p>";
                    }
                } else {
                    echo "<p>❌ Method {$method} not found</p>";
                }
            }

        } catch (Error $e) {
            echo "<p>❌ Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "</p>";
        } catch (Exception $e) {
            echo "<p>❌ Exception: " . $e->getMessage() . "</p>";
        }

    } catch (ParseError $e) {
        echo "<p>❌ Parse Error: " . $e->getMessage() . " on line " . $e->getLine() . "</p>";
    }

    echo "<p><strong>Bypass debug completed</strong></p>";
    echo "<p><a href='" . admin_url('admin.php') . "'>Back to Admin</a></p>";
    exit;
}
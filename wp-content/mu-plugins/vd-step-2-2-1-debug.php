<?php
/**
 * Step 2.2.1 Debug Helper
 *
 * Truy cập: https://vidieu.vn/vd-debug-step-2-2-1.php
 */

// Prevent direct access without WordPress
if (!defined('ABSPATH')) {
    // Load WordPress if accessed directly
    require_once('../../../wp-load.php');
}

// Set content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Step 2.2.1 Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { background: #f0f0f1; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { border-left: 4px solid #46b450; }
        .error { border-left: 4px solid #dc3232; }
        pre { background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Step 2.2.1 - Expiry Core Module Debug</h1>

    <?php
    try {
        echo '<div class="result success">';
        echo '<h2>✅ WordPress Loaded Successfully</h2>';
        echo '<p>WordPress Version: ' . get_bloginfo('version') . '</p>';
        echo '<p>Current Time: ' . current_time('mysql') . '</p>';
        echo '</div>';

        // Test 1: Check if classes exist
        echo '<div class="result">';
        echo '<h2>Class Availability Check</h2>';
        $classes = [
            'VD_License_Module_Loader',
            'VD_License_Dependency_Container',
            'VD_License_Rule_Expiry_Core'
        ];

        foreach ($classes as $class) {
            if (class_exists($class)) {
                echo "<p style='color: green;'>✅ {$class} - Available</p>";
            } else {
                echo "<p style='color: red;'>❌ {$class} - Not found</p>";
            }
        }
        echo '</div>';

        // Test 2: Module Loader
        if (class_exists('VD_License_Module_Loader')) {
            echo '<div class="result success">';
            echo '<h2>Module Loader Test</h2>';

            $loader = VD_License_Module_Loader::get_instance();
            echo '<p>✅ Module Loader instance created</p>';

            $registry = $loader->get_registry();
            if (isset($registry['rules.expiry_core'])) {
                echo '<p>✅ rules.expiry_core found in registry</p>';
                echo '<pre>' . print_r($registry['rules.expiry_core'], true) . '</pre>';
            } else {
                echo '<p style="color: red;">❌ rules.expiry_core not in registry</p>';
            }

            echo '</div>';
        }

        // Test 3: Try loading the module
        if (class_exists('VD_License_Module_Loader')) {
            echo '<div class="result">';
            echo '<h2>Module Loading Test</h2>';

            $loader = VD_License_Module_Loader::get_instance();
            $expiry_core = $loader->load_module('rules.expiry_core');

            if ($expiry_core) {
                echo '<p style="color: green;">✅ Expiry Core module loaded successfully!</p>';
                echo '<p>Class: ' . get_class($expiry_core) . '</p>';

                $module_info = $expiry_core->get_module_info();
                echo '<h3>Module Information:</h3>';
                echo '<pre>' . print_r($module_info, true) . '</pre>';

            } else {
                echo '<p style="color: red;">❌ Failed to load Expiry Core module</p>';
            }

            echo '</div>';
        }

        // Test 4: Container test
        if (class_exists('VD_License_Dependency_Container')) {
            echo '<div class="result">';
            echo '<h2>Dependency Container Test</h2>';

            $container = VD_License_Dependency_Container::get_instance();
            echo '<p>✅ Container instance created</p>';

            if ($container->has('rules.expiry_core')) {
                echo '<p style="color: green;">✅ rules.expiry_core service registered</p>';

                try {
                    $service = $container->get('rules.expiry_core');
                    echo '<p style="color: green;">✅ Service resolved: ' . get_class($service) . '</p>';
                } catch (Exception $e) {
                    echo '<p style="color: red;">❌ Service resolution failed: ' . $e->getMessage() . '</p>';
                }
            } else {
                echo '<p style="color: red;">❌ rules.expiry_core service not registered</p>';
            }

            echo '</div>';
        }

    } catch (Exception $e) {
        echo '<div class="result error">';
        echo '<h2>❌ Error Occurred</h2>';
        echo '<p>' . esc_html($e->getMessage()) . '</p>';
        echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>

    <div class="result">
        <h2>Test Links</h2>
        <p>
            <a href="<?php echo admin_url('tools.php?page=vd-step-2-2-1-test'); ?>">Admin Test Page</a> |
            <a href="<?php echo admin_url('admin-ajax.php?action=vd_test_step_2_2_1'); ?>">AJAX Test</a> |
            <a href="javascript:location.reload();">Refresh Debug</a>
        </p>
    </div>

</body>
</html>
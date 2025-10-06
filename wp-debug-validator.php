<?php
/**
 * WordPress Debug Validator - Test VD_License_Validator loading within WordPress
 */

// Include WordPress
require_once dirname(__FILE__) . '/wp-load.php';

// Security check - allow any user for debugging
if (!current_user_can('read')) {
    // wp_die('Unauthorized access');
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html>
<head>
    <title>🔍 WordPress VD_License_Validator Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 1000px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .section { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 WordPress VD_License_Validator Debug</h1>
        <p class="info">Testing VD_License_Validator loading within WordPress environment</p>

        <div class="section">
            <h3>1. WordPress Environment Check</h3>
            <?php
            echo '<p class="success">✅ WordPress loaded successfully</p>';
            echo '<p class="info">WordPress Version: ' . get_bloginfo('version') . '</p>';
            echo '<p class="info">PHP Version: ' . phpversion() . '</p>';
            echo '<p class="info">Current User ID: ' . get_current_user_id() . '</p>';
            ?>
        </div>

        <div class="section">
            <h3>2. Plugin Status Check</h3>
            <?php
            $plugin_file = 'vd-license-manager/vd-license-manager.php';
            $all_plugins = get_plugins();
            $is_installed = isset($all_plugins[$plugin_file]);
            $is_active = is_plugin_active($plugin_file);

            echo '<p class="' . ($is_installed ? 'success' : 'error') . '">';
            echo $is_installed ? '✅' : '❌';
            echo ' Plugin installed: ' . ($is_installed ? 'YES' : 'NO') . '</p>';

            echo '<p class="' . ($is_active ? 'success' : 'error') . '">';
            echo $is_active ? '✅' : '❌';
            echo ' Plugin active: ' . ($is_active ? 'YES' : 'NO') . '</p>';

            if ($is_installed) {
                $plugin_data = $all_plugins[$plugin_file];
                echo '<p class="info">Plugin Name: ' . htmlspecialchars($plugin_data['Name']) . '</p>';
                echo '<p class="info">Plugin Version: ' . htmlspecialchars($plugin_data['Version']) . '</p>';
            }
            ?>
        </div>

        <div class="section">
            <h3>3. File System Check</h3>
            <?php
            $plugin_dir = WP_PLUGIN_DIR . '/vd-license-manager';
            $main_file = $plugin_dir . '/vd-license-manager.php';
            $validator_file = $plugin_dir . '/includes/class-vd-license-validator.php';

            echo '<p class="info">Plugin Directory: ' . htmlspecialchars($plugin_dir) . '</p>';

            echo '<p class="' . (is_dir($plugin_dir) ? 'success' : 'error') . '">';
            echo is_dir($plugin_dir) ? '✅' : '❌';
            echo ' Plugin directory exists</p>';

            echo '<p class="' . (file_exists($main_file) ? 'success' : 'error') . '">';
            echo file_exists($main_file) ? '✅' : '❌';
            echo ' Main plugin file exists</p>';

            echo '<p class="' . (file_exists($validator_file) ? 'success' : 'error') . '">';
            echo file_exists($validator_file) ? '✅' : '❌';
            echo ' Validator file exists</p>';

            if (file_exists($validator_file)) {
                echo '<p class="info">Validator file size: ' . number_format(filesize($validator_file)) . ' bytes</p>';
                echo '<p class="info">Last modified: ' . date('Y-m-d H:i:s', filemtime($validator_file)) . '</p>';
            }
            ?>
        </div>

        <div class="section">
            <h3>4. Class Loading Test</h3>
            <?php
            echo '<p class="' . (class_exists('VD_License_Validator', false) ? 'success' : 'warning') . '">';
            echo class_exists('VD_License_Validator', false) ? '✅' : '⚠️';
            echo ' VD_License_Validator class loaded: ' . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . '</p>';

            if (!class_exists('VD_License_Validator', false)) {
                echo '<p class="info">Attempting manual loading...</p>';

                if (file_exists($validator_file)) {
                    ob_start();
                    $error_before = error_get_last();

                    try {
                        require_once $validator_file;
                        $load_success = true;
                        $load_error = null;
                    } catch (ParseError $e) {
                        $load_success = false;
                        $load_error = 'Parse Error: ' . $e->getMessage();
                    } catch (Error $e) {
                        $load_success = false;
                        $load_error = 'Fatal Error: ' . $e->getMessage();
                    } catch (Exception $e) {
                        $load_success = false;
                        $load_error = 'Exception: ' . $e->getMessage();
                    }

                    $output = ob_get_clean();
                    $error_after = error_get_last();

                    if ($load_success) {
                        echo '<p class="success">✅ Manual loading completed</p>';

                        if (class_exists('VD_License_Validator', false)) {
                            echo '<p class="success">✅ VD_License_Validator class now available!</p>';

                            // Test class methods
                            $reflection = new ReflectionClass('VD_License_Validator');
                            echo '<p class="info">Class methods: ' . count($reflection->getMethods()) . '</p>';

                            if ($reflection->hasMethod('validate_security_compliance')) {
                                echo '<p class="success">✅ validate_security_compliance method exists</p>';
                            }
                        } else {
                            echo '<p class="error">❌ Class still not available after loading</p>';
                        }
                    } else {
                        echo '<p class="error">❌ Manual loading failed</p>';
                        if ($load_error) {
                            echo '<p class="error">Error: ' . htmlspecialchars($load_error) . '</p>';
                        }
                    }

                    if ($output) {
                        echo '<p class="warning">Output during loading:</p>';
                        echo '<pre>' . htmlspecialchars($output) . '</pre>';
                    }

                    if ($error_after && $error_after !== $error_before) {
                        echo '<p class="error">PHP Error detected:</p>';
                        echo '<pre>' . htmlspecialchars(print_r($error_after, true)) . '</pre>';
                    }
                }
            }
            ?>
        </div>

        <div class="section">
            <h3>5. Plugin Dependencies Check</h3>
            <?php
            $required_constants = ['VD_LM_PATH', 'VD_LM_URL', 'VD_LM_VERSION'];
            foreach ($required_constants as $constant) {
                echo '<p class="' . (defined($constant) ? 'success' : 'warning') . '">';
                echo defined($constant) ? '✅' : '⚠️';
                echo ' ' . $constant . ': ' . (defined($constant) ? constant($constant) : 'NOT DEFINED') . '</p>';
            }

            // Check if main plugin class exists
            echo '<p class="' . (class_exists('VD_License_Manager') ? 'success' : 'warning') . '">';
            echo class_exists('VD_License_Manager') ? '✅' : '⚠️';
            echo ' VD_License_Manager class: ' . (class_exists('VD_License_Manager') ? 'EXISTS' : 'NOT FOUND') . '</p>';
            ?>
        </div>

        <div class="section">
            <h3>6. Error Log Analysis</h3>
            <?php
            $error = error_get_last();
            if ($error) {
                echo '<p class="error">Last PHP Error:</p>';
                echo '<pre>' . htmlspecialchars(print_r($error, true)) . '</pre>';
            } else {
                echo '<p class="success">✅ No recent PHP errors</p>';
            }
            ?>
        </div>

        <p style="margin-top: 20px; color: #666; font-size: 14px;">
            🕒 Debug completed at: <?php echo current_time('mysql'); ?>
        </p>
    </div>
</body>
</html>
<?php
/**
 * Advanced Class Loading Debug
 */

require_once dirname(__FILE__) . '/wp-load.php';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html>
<head>
    <title>🔬 Advanced Class Loading Debug</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #ffffff; }
        .success { color: #4caf50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .info { color: #2196f3; }
        pre { background: #2d2d2d; padding: 10px; border-radius: 4px; overflow-x: auto; border-left: 4px solid #4caf50; }
        .error-pre { border-left-color: #f44336; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #444; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔬 Advanced VD_License_Validator Class Loading Debug</h1>

    <div class="section">
        <h3>Step 1: Pre-loading State</h3>
        <?php
        echo '<p>Class exists before loading: <span class="' . (class_exists('VD_License_Validator', false) ? 'success' : 'warning') . '">' . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . '</span></p>';

        $validator_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-license-validator.php';
        echo '<p class="info">Target file: ' . htmlspecialchars($validator_file) . '</p>';
        echo '<p class="info">File exists: <span class="' . (file_exists($validator_file) ? 'success' : 'error') . '">' . (file_exists($validator_file) ? 'YES' : 'NO') . '</span></p>';
        ?>
    </div>

    <div class="section">
        <h3>Step 2: Enable All Error Reporting</h3>
        <?php
        // Maximum error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);

        echo '<p class="success">✅ Error reporting enabled at maximum level</p>';
        echo '<p class="info">Error reporting level: ' . error_reporting() . '</p>';
        echo '<p class="info">Display errors: ' . ini_get('display_errors') . '</p>';
        ?>
    </div>

    <div class="section">
        <h3>Step 3: Capture All Output During Loading</h3>
        <?php
        if (file_exists($validator_file)) {
            echo '<p class="info">Starting output capture...</p>';

            // Start output buffering
            ob_start();

            // Clear previous errors
            error_clear_last();

            // Set custom error handler
            set_error_handler(function($severity, $message, $file, $line) {
                echo "<div class='error'>⚠️ PHP Error: $message in $file on line $line (Severity: $severity)</div>";
                return false; // Continue with normal error handling
            });

            echo '<p class="info">Attempting to include file...</p>';

            try {
                $include_result = include_once $validator_file;
                echo '<p class="success">✅ include_once completed</p>';
                echo '<p class="info">Include result: ' . var_export($include_result, true) . '</p>';
            } catch (ParseError $e) {
                echo '<p class="error">❌ Parse Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p class="error">File: ' . htmlspecialchars($e->getFile()) . '</p>';
                echo '<p class="error">Line: ' . $e->getLine() . '</p>';
            } catch (Error $e) {
                echo '<p class="error">❌ Fatal Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p class="error">File: ' . htmlspecialchars($e->getFile()) . '</p>';
                echo '<p class="error">Line: ' . $e->getLine() . '</p>';
            } catch (Exception $e) {
                echo '<p class="error">❌ Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p class="error">File: ' . htmlspecialchars($e->getFile()) . '</p>';
                echo '<p class="error">Line: ' . $e->getLine() . '</p>';
            }

            // Restore error handler
            restore_error_handler();

            // Get any output
            $output = ob_get_clean();

            if ($output) {
                echo '<p class="warning">⚠️ Output captured during loading:</p>';
                echo '<pre class="error-pre">' . htmlspecialchars($output) . '</pre>';
            } else {
                echo '<p class="success">✅ No output during loading</p>';
            }

            // Check for PHP errors
            $last_error = error_get_last();
            if ($last_error) {
                echo '<p class="error">❌ PHP Error detected:</p>';
                echo '<pre class="error-pre">' . htmlspecialchars(print_r($last_error, true)) . '</pre>';
            } else {
                echo '<p class="success">✅ No PHP errors detected</p>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h3>Step 4: Post-loading Analysis</h3>
        <?php
        echo '<p>Class exists after loading: <span class="' . (class_exists('VD_License_Validator', false) ? 'success' : 'error') . '">' . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . '</span></p>';

        // Check all declared classes
        $all_classes = get_declared_classes();
        $vd_classes = array_filter($all_classes, function($class) {
            return strpos($class, 'VD_') === 0;
        });

        echo '<p class="info">All VD_* classes found: ' . count($vd_classes) . '</p>';
        if ($vd_classes) {
            echo '<pre>' . htmlspecialchars(implode("\n", $vd_classes)) . '</pre>';
        }

        // Check if class exists with autoloading
        echo '<p>Class exists with autoloading: <span class="' . (class_exists('VD_License_Validator', true) ? 'success' : 'error') . '">' . (class_exists('VD_License_Validator', true) ? 'YES' : 'NO') . '</span></p>';
        ?>
    </div>

    <div class="section">
        <h3>Step 5: File Content Analysis</h3>
        <?php
        if (file_exists($validator_file)) {
            $content = file_get_contents($validator_file);
            $lines = explode("\n", $content);

            echo '<p class="info">Total lines: ' . count($lines) . '</p>';
            echo '<p class="info">File size: ' . number_format(strlen($content)) . ' bytes</p>';

            // Look for class declaration
            $class_line = null;
            foreach ($lines as $num => $line) {
                if (preg_match('/^class\s+VD_License_Validator/', trim($line))) {
                    $class_line = $num + 1;
                    break;
                }
            }

            if ($class_line) {
                echo '<p class="success">✅ Class declaration found on line: ' . $class_line . '</p>';
                echo '<p class="info">Declaration: ' . htmlspecialchars(trim($lines[$class_line - 1])) . '</p>';
            } else {
                echo '<p class="error">❌ Class declaration not found!</p>';
            }

            // Check for namespace declaration
            $namespace_line = null;
            foreach ($lines as $num => $line) {
                if (preg_match('/^namespace\s+/', trim($line))) {
                    $namespace_line = $num + 1;
                    break;
                }
            }

            if ($namespace_line) {
                echo '<p class="warning">⚠️ Namespace declaration found on line: ' . $namespace_line . '</p>';
                echo '<p class="info">Namespace: ' . htmlspecialchars(trim($lines[$namespace_line - 1])) . '</p>';
                echo '<p class="error">❌ This explains why class is not found - it\'s in a namespace!</p>';
            } else {
                echo '<p class="success">✅ No namespace declaration found</p>';
            }
        }
        ?>
    </div>

    <p style="margin-top: 30px; color: #888;">🕒 Debug completed at: <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html>
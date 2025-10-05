<?php
/**
 * Step 4.3.1 Infrastructure Test
 * Simple test for validation infrastructure module
 */

echo "<h1>🧪 Step 4.3.1 Infrastructure Test</h1>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p>";

// Load WordPress
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/wp-config.php');
    require_once(ABSPATH . 'wp-load.php');
}

echo "<h2>1. WordPress Environment</h2>";
echo "✅ WordPress loaded<br>";

echo "<h2>2. Load Plugin</h2>";
$plugin_dir = dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/';

// Load plugin files
$files_to_load = array(
    'vd-license-manager.php',
    'includes/class-vd-license-manager.php',
    'includes/class-vd-license-module-loader.php',
    'includes/modules/infrastructure/class-vd-license-validation-infrastructure.php'
);

foreach ($files_to_load as $file) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        require_once $full_path;
        echo "✅ Loaded: " . basename($file) . "<br>";
    } else {
        echo "❌ Missing: " . basename($file) . "<br>";
    }
}

echo "<h2>3. Test Infrastructure Module</h2>";

try {
    if (class_exists('VD\\LicenseManager\\Infrastructure\\VD_License_Validation_Infrastructure')) {
        $infrastructure = VD\LicenseManager\Infrastructure\VD_License_Validation_Infrastructure::get_instance();
        echo "✅ Infrastructure module loaded<br>";

        // Test main methods
        $methods_to_test = array(
            'extract_license_key' => 'License key extraction',
            'transform_context_to_options' => 'Context transformation',
            'map_orchestrator_result_to_legacy_format' => 'Result mapping',
            'count_orchestrator_checks' => 'Check counting',
            'get_validation_statistics' => 'Statistics',
            'get_status' => 'Status check',
            'health_check' => 'Health check'
        );

        echo "<h3>Testing Methods:</h3>";
        foreach ($methods_to_test as $method => $description) {
            if (method_exists($infrastructure, $method)) {
                echo "✅ $description ($method)<br>";
            } else {
                echo "❌ $description ($method) - Not found<br>";
            }
        }

        // Test extract_license_key
        if (method_exists($infrastructure, 'extract_license_key')) {
            echo "<h4>Testing extract_license_key:</h4>";
            try {
                $test_license = array('license_key' => 'TEST-KEY-123');
                $result = $infrastructure->extract_license_key($test_license);
                echo "✅ Result: $result<br>";
            } catch (Exception $e) {
                echo "⚠️ Error: " . $e->getMessage() . "<br>";
            }
        }

        // Test count_orchestrator_checks
        if (method_exists($infrastructure, 'count_orchestrator_checks')) {
            echo "<h4>Testing count_orchestrator_checks:</h4>";
            try {
                $test_result = array(
                    'checks_passed' => 5,
                    'checks_failed' => 2,
                    'checks_skipped' => 1
                );
                $counts = $infrastructure->count_orchestrator_checks($test_result);
                echo "✅ Counts: " . json_encode($counts) . "<br>";
            } catch (Exception $e) {
                echo "⚠️ Error: " . $e->getMessage() . "<br>";
            }
        }

        // Test get_status
        if (method_exists($infrastructure, 'get_status')) {
            echo "<h4>Testing get_status:</h4>";
            try {
                $status = $infrastructure->get_status();
                echo "✅ Status: $status<br>";
            } catch (Exception $e) {
                echo "⚠️ Error: " . $e->getMessage() . "<br>";
            }
        }

    } else {
        echo "❌ Infrastructure module class not found<br>";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Summary</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Step 4.3.1 Test Results</h3>";
echo "<p><strong>Infrastructure Module:</strong> Validation Infrastructure</p>";
echo "<p><strong>Purpose:</strong> Extracted infrastructure utilities from monolithic validator</p>";
echo "<p><strong>Key Functions:</strong></p>";
echo "<ul>";
echo "<li>License key extraction</li>";
echo "<li>Context transformation</li>";
echo "<li>Result mapping</li>";
echo "<li>Check counting</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Test completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
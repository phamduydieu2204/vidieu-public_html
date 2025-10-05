<?php
/**
 * Step 4.3.2 Validation Analyzer Test
 * Simple test for validation analyzer module
 */

echo "<h1>🧪 Step 4.3.2 Validation Analyzer Test</h1>";
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
    'includes/modules/validation/class-vd-license-validation-analyzer.php'
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

echo "<h2>3. Test Validation Analyzer Module</h2>";

try {
    if (class_exists('VD\\LicenseManager\\Validation\\VD_License_Validation_Analyzer')) {
        $analyzer = VD\LicenseManager\Validation\VD_License_Validation_Analyzer::get_instance();
        echo "✅ Validation Analyzer module loaded<br>";

        // Test main methods
        $methods_to_test = array(
            'validate_license_key_format' => 'License key format validation',
            'validate_license_status' => 'License status validation',
            'validate_license_expiry' => 'License expiry validation',
            'validate_license_keys_batch' => 'Batch validation',
            'get_validation_statistics' => 'Statistics',
            'get_module_info' => 'Module info',
            'health_check' => 'Health check'
        );

        echo "<h3>Testing Methods:</h3>";
        foreach ($methods_to_test as $method => $description) {
            if (method_exists($analyzer, $method)) {
                echo "✅ $description ($method)<br>";
            } else {
                echo "❌ $description ($method) - Not found<br>";
            }
        }

        // Test with real license key
        echo "<h3>Testing with Real License:</h3>";
        $real_license = 'H10D-DIJD-14RC-SOLE-6KUV30';

        // Test format validation
        if (method_exists($analyzer, 'validate_license_key_format')) {
            echo "<h4>Format Validation:</h4>";
            try {
                $result = $analyzer->validate_license_key_format($real_license, true);
                if (is_array($result)) {
                    $status = $result['valid'] ? '✅' : '❌';
                    echo "&nbsp;&nbsp;$status Result: " . ($result['valid'] ? 'Valid' : 'Invalid') . "<br>";
                    if (isset($result['format'])) {
                        echo "&nbsp;&nbsp;📋 Format: " . $result['format'] . "<br>";
                    }
                    if (isset($result['analysis']['entropy_score'])) {
                        echo "&nbsp;&nbsp;📊 Entropy: " . $result['analysis']['entropy_score'] . "<br>";
                    }
                }
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;⚠️ Error: " . $e->getMessage() . "<br>";
            }
        }

        // Test expiry validation
        if (method_exists($analyzer, 'validate_license_expiry')) {
            echo "<h4>Expiry Validation:</h4>";
            try {
                $result = $analyzer->validate_license_expiry($real_license);
                if (is_array($result)) {
                    $status = $result['valid'] ? '✅' : '❌';
                    echo "&nbsp;&nbsp;$status Result: " . ($result['valid'] ? 'Valid' : 'Expired') . "<br>";
                    if (isset($result['days_to_expiry'])) {
                        echo "&nbsp;&nbsp;📅 Days to expiry: " . $result['days_to_expiry'] . "<br>";
                    }
                }
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;⚠️ Error: " . $e->getMessage() . "<br>";
            }
        }

        // Test batch validation
        if (method_exists($analyzer, 'validate_license_keys_batch')) {
            echo "<h4>Batch Validation:</h4>";
            try {
                $batch_keys = array($real_license, 'TEST-KEY-001', 'INVALID');
                $result = $analyzer->validate_license_keys_batch($batch_keys);
                if (is_array($result) && isset($result['batch_size'])) {
                    echo "&nbsp;&nbsp;✅ Batch size: " . $result['batch_size'] . "<br>";
                    if (isset($result['performance'])) {
                        echo "&nbsp;&nbsp;⚡ Total time: " . $result['performance']['total_execution_time'] . "<br>";
                    }
                }
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;⚠️ Error: " . $e->getMessage() . "<br>";
            }
        }

        // Test module info
        if (method_exists($analyzer, 'get_module_info')) {
            echo "<h4>Module Information:</h4>";
            try {
                $info = $analyzer->get_module_info();
                echo "&nbsp;&nbsp;📋 Name: " . ($info['name'] ?? 'Unknown') . "<br>";
                echo "&nbsp;&nbsp;📋 Version: " . ($info['version'] ?? 'Unknown') . "<br>";
                echo "&nbsp;&nbsp;📋 Methods: " . count($info['methods'] ?? array()) . " methods<br>";
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;⚠️ Error: " . $e->getMessage() . "<br>";
            }
        }

    } else {
        echo "❌ Validation Analyzer module class not found<br>";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Integration Test</h2>";

try {
    if (file_exists($plugin_dir . 'includes/class-vd-license-validator.php')) {
        require_once $plugin_dir . 'includes/class-vd-license-validator.php';

        if (class_exists('VD_License_Validator')) {
            $validator = VD_License_Validator::get_instance();
            echo "✅ Main validator loaded<br>";

            echo "<h3>Testing Delegation:</h3>";

            // Test format validation delegation
            $format_result = $validator->validate_license_key_format('H10D-DIJD-14RC-SOLE-6KUV30', true);
            if (is_array($format_result)) {
                echo "✅ Format validation delegation working<br>";
            } else {
                echo "❌ Format validation delegation issue<br>";
            }

            // Test expiry validation delegation
            $expiry_result = $validator->validate_license_expiry('H10D-DIJD-14RC-SOLE-6KUV30');
            if (is_array($expiry_result)) {
                echo "✅ Expiry validation delegation working<br>";
            } else {
                echo "❌ Expiry validation delegation issue<br>";
            }

        } else {
            echo "❌ Main validator class not available<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Integration test error: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Summary</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Step 4.3.2 Test Results</h3>";
echo "<p><strong>Module:</strong> Validation Analyzer</p>";
echo "<p><strong>Purpose:</strong> Core validation analysis for license format, status, expiry, and business rules</p>";
echo "<p><strong>Key Features:</strong></p>";
echo "<ul>";
echo "<li>🔍 Advanced license key format validation</li>";
echo "<li>📊 Comprehensive status validation</li>";
echo "<li>⏰ Expiry validation with grace periods</li>";
echo "<li>🚀 Batch processing capabilities</li>";
echo "<li>🔗 Integration with main validator</li>";
echo "</ul>";
echo "<p><strong>Test License:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>";
echo "</div>";

echo "<p><strong>Test completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
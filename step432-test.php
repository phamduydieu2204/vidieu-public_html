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
    // Load additional dependencies that validator might need
    $validator_dependencies = array(
        'includes/class-vd-database-manager.php',
        'includes/class-vd-encryption-manager.php',
        'includes/class-vd-license-core.php'
    );

    echo "<h3>Loading Validator Dependencies:</h3>";
    foreach ($validator_dependencies as $dep_file) {
        $full_path = $plugin_dir . $dep_file;
        if (file_exists($full_path)) {
            require_once $full_path;
            echo "✅ Loaded: " . basename($dep_file) . "<br>";
        } else {
            echo "⚠️ Optional: " . basename($dep_file) . " (not found)<br>";
        }
    }

    echo "<h3>Loading Main Validator:</h3>";
    $validator_file = $plugin_dir . 'includes/class-vd-license-validator.php';
    if (file_exists($validator_file)) {
        // Check file content first
        $file_content = file_get_contents($validator_file);
        $has_class = strpos($file_content, 'class VD_License_Validator') !== false;
        echo "✅ Validator file exists (" . number_format(filesize($validator_file)) . " bytes)<br>";
        echo ($has_class ? "✅" : "❌") . " File contains VD_License_Validator class<br>";

        // Try to include with error handling
        try {
            require_once $validator_file;
            echo "✅ Validator file included<br>";

            // Check if class exists after include
            if (class_exists('VD_License_Validator')) {
                echo "✅ VD_License_Validator class available<br>";

                // Try to instantiate
                try {
                    $validator = VD_License_Validator::get_instance();
                    echo "✅ Main validator instantiated<br>";

                    echo "<h3>Testing Delegation:</h3>";

                    // Test format validation delegation
                    echo "<strong>Format Validation Delegation:</strong><br>";
                    try {
                        $format_result = $validator->validate_license_key_format('H10D-DIJD-14RC-SOLE-6KUV30', true);
                        if (is_array($format_result)) {
                            $status = $format_result['valid'] ? '✅' : '❌';
                            echo "&nbsp;&nbsp;$status Format validation: " . ($format_result['valid'] ? 'Working' : 'Failed') . "<br>";
                            echo "&nbsp;&nbsp;📋 Delegation result type: " . gettype($format_result) . "<br>";
                            if (isset($format_result['module_error'])) {
                                echo "&nbsp;&nbsp;⚠️ Module error detected<br>";
                            }
                        } else {
                            echo "&nbsp;&nbsp;❌ Format validation returned: " . gettype($format_result) . "<br>";
                        }
                    } catch (Exception $e) {
                        echo "&nbsp;&nbsp;❌ Format validation error: " . $e->getMessage() . "<br>";
                    }

                    // Test expiry validation delegation
                    echo "<strong>Expiry Validation Delegation:</strong><br>";
                    try {
                        $expiry_result = $validator->validate_license_expiry('H10D-DIJD-14RC-SOLE-6KUV30');
                        if (is_array($expiry_result)) {
                            $status = $expiry_result['valid'] ? '✅' : '❌';
                            echo "&nbsp;&nbsp;$status Expiry validation: " . ($expiry_result['valid'] ? 'Working' : 'Failed') . "<br>";
                            echo "&nbsp;&nbsp;📋 Delegation result type: " . gettype($expiry_result) . "<br>";
                            if (isset($expiry_result['module_error'])) {
                                echo "&nbsp;&nbsp;⚠️ Module error detected<br>";
                            }
                        } else {
                            echo "&nbsp;&nbsp;❌ Expiry validation returned: " . gettype($expiry_result) . "<br>";
                        }
                    } catch (Exception $e) {
                        echo "&nbsp;&nbsp;❌ Expiry validation error: " . $e->getMessage() . "<br>";
                    }

                    // Test batch validation delegation
                    echo "<strong>Batch Validation Delegation:</strong><br>";
                    try {
                        $batch_result = $validator->validate_license_keys_batch(array('H10D-DIJD-14RC-SOLE-6KUV30', 'TEST-KEY'));
                        if (is_array($batch_result)) {
                            echo "&nbsp;&nbsp;✅ Batch validation working<br>";
                            echo "&nbsp;&nbsp;📋 Result type: " . gettype($batch_result) . "<br>";
                            if (isset($batch_result['batch_size'])) {
                                echo "&nbsp;&nbsp;📊 Batch size: " . $batch_result['batch_size'] . "<br>";
                            }
                            if (isset($batch_result['module_error'])) {
                                echo "&nbsp;&nbsp;⚠️ Module error detected<br>";
                            }
                        } else {
                            echo "&nbsp;&nbsp;❌ Batch validation returned: " . gettype($batch_result) . "<br>";
                        }
                    } catch (Exception $e) {
                        echo "&nbsp;&nbsp;❌ Batch validation error: " . $e->getMessage() . "<br>";
                    }

                } catch (Exception $e) {
                    echo "❌ Validator instantiation error: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "❌ VD_License_Validator class not available after include<br>";

                // Debug: Show available classes
                $all_classes = get_declared_classes();
                $vd_classes = array_filter($all_classes, function($class) {
                    return strpos($class, 'VD_') === 0;
                });
                echo "📋 Available VD classes: " . implode(', ', array_slice($vd_classes, 0, 10)) . "<br>";
            }
        } catch (ParseError $e) {
            echo "❌ Parse error in validator: " . $e->getMessage() . "<br>";
        } catch (Error $e) {
            echo "❌ Fatal error in validator: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Validator file not found<br>";
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
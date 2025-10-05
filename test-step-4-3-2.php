<?php
/**
 * Test Step 4.3.2: Validation Analyzer
 *
 * Comprehensive test for Step 4.3.2 Validation Analyzer module
 * Tests core validation analysis for license format, status, expiry, and business rules
 */

echo "<h1>🧪 Test Step 4.3.2: Validation Analyzer</h1>";
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

        echo "<h3>Testing Core Methods:</h3>";

        // Test validate_license_key_format
        echo "<h4>1. License Key Format Validation:</h4>";
        $test_keys = array(
            'H10D-DIJD-14RC-SOLE-6KUV30' => 'Real license key',
            'TEST-KEY1-2345-ABCD-EFGH01' => 'Valid format test',
            'INVALID-KEY' => 'Invalid format test',
            '' => 'Empty key test'
        );

        foreach ($test_keys as $key => $description) {
            echo "<strong>Testing: $description</strong><br>";
            if (!empty($key)) {
                try {
                    $result = $analyzer->validate_license_key_format($key, true);
                    if (is_array($result)) {
                        $status = $result['valid'] ? '✅' : '❌';
                        echo "&nbsp;&nbsp;$status Format: " . ($result['format'] ?? 'unknown') . "<br>";
                        if (isset($result['analysis'])) {
                            echo "&nbsp;&nbsp;📊 Entropy: " . $result['analysis']['entropy_score'] . "<br>";
                            echo "&nbsp;&nbsp;🔧 Checksum: " . ($result['analysis']['checksum_valid'] ? 'Valid' : 'Invalid') . "<br>";
                        }
                        if (isset($result['performance'])) {
                            echo "&nbsp;&nbsp;⚡ Time: " . $result['performance']['execution_time'] . "<br>";
                        }
                    } else {
                        echo "&nbsp;&nbsp;" . ($result ? '✅' : '❌') . " Basic validation<br>";
                    }
                } catch (Exception $e) {
                    echo "&nbsp;&nbsp;⚠️ Error: " . $e->getMessage() . "<br>";
                }
            } else {
                $result = $analyzer->validate_license_key_format($key, true);
                echo "&nbsp;&nbsp;❌ Empty key: " . ($result['error'] ?? 'No error message') . "<br>";
            }
            echo "<br>";
        }

        // Test validate_license_status
        echo "<h4>2. License Status Validation:</h4>";
        $test_licenses = array(
            array(
                'id' => 1,
                'license_key' => 'H10D-DIJD-14RC-SOLE-6KUV30',
                'status' => 'active',
                'expires_at' => date('Y-m-d H:i:s', time() + (30 * 24 * 3600)),
                'user_id' => 1,
                'usage_count' => 5,
                'usage_limit' => 100
            ),
            array(
                'id' => 2,
                'license_key' => 'TEST-EXPIRED-KEY',
                'status' => 'expired',
                'expires_at' => date('Y-m-d H:i:s', time() - (10 * 24 * 3600)),
                'user_id' => 2
            ),
            array(
                'status' => 'invalid_status'
            )
        );

        foreach ($test_licenses as $index => $license) {
            echo "<strong>Test License " . ($index + 1) . " (Status: " . ($license['status'] ?? 'missing') . "):</strong><br>";
            try {
                $result = $analyzer->validate_license_status($license);
                $status = $result['valid'] ? '✅' : '❌';
                echo "&nbsp;&nbsp;$status Validation: " . ($result['valid'] ? 'Passed' : 'Failed') . "<br>";

                if (!empty($result['errors'])) {
                    echo "&nbsp;&nbsp;❌ Errors: " . implode(', ', $result['errors']) . "<br>";
                }
                if (!empty($result['warnings'])) {
                    echo "&nbsp;&nbsp;⚠️ Warnings: " . implode(', ', $result['warnings']) . "<br>";
                }
                if (isset($result['performance'])) {
                    echo "&nbsp;&nbsp;⚡ Time: " . $result['performance']['execution_time'] . "<br>";
                }
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;⚠️ Error: " . $e->getMessage() . "<br>";
            }
            echo "<br>";
        }

        // Test validate_license_expiry
        echo "<h4>3. License Expiry Validation:</h4>";
        $test_expiry_keys = array(
            'H10D-DIJD-14RC-SOLE-6KUV30' => 'Real license key',
            'TEST-FUTURE-EXPIRY' => 'Test key',
            'NONEXISTENT-KEY' => 'Non-existent key'
        );

        foreach ($test_expiry_keys as $key => $description) {
            echo "<strong>Testing: $description</strong><br>";
            try {
                $result = $analyzer->validate_license_expiry($key);
                $status = $result['valid'] ? '✅' : '❌';
                echo "&nbsp;&nbsp;$status Expiry: " . ($result['valid'] ? 'Valid' : 'Invalid/Expired') . "<br>";

                if (isset($result['days_to_expiry'])) {
                    echo "&nbsp;&nbsp;📅 Days to expiry: " . $result['days_to_expiry'] . "<br>";
                }
                if (isset($result['grace_period'])) {
                    $grace = $result['grace_period'];
                    echo "&nbsp;&nbsp;🛡️ Grace period: " . ($grace['in_grace_period'] ? 'Active' : 'Not active') . "<br>";
                    if ($grace['in_grace_period']) {
                        echo "&nbsp;&nbsp;⏰ Grace days remaining: " . $grace['grace_days_remaining'] . "<br>";
                    }
                }
                if (isset($result['performance'])) {
                    echo "&nbsp;&nbsp;⚡ Time: " . $result['performance']['execution_time'] . "<br>";
                }
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;⚠️ Error: " . $e->getMessage() . "<br>";
            }
            echo "<br>";
        }

        // Test batch validation
        echo "<h4>4. Batch Validation:</h4>";
        $batch_keys = array(
            'H10D-DIJD-14RC-SOLE-6KUV30',
            'TEST-KEY1-2345-ABCD-EFGH01',
            'INVALID-KEY'
        );

        try {
            $batch_result = $analyzer->validate_license_keys_batch($batch_keys);
            echo "✅ Batch validation completed<br>";
            echo "&nbsp;&nbsp;📊 Batch size: " . $batch_result['batch_size'] . "<br>";
            echo "&nbsp;&nbsp;⚡ Total time: " . $batch_result['performance']['total_execution_time'] . "<br>";
            echo "&nbsp;&nbsp;⚡ Average per license: " . $batch_result['performance']['average_per_license'] . "<br>";

            echo "&nbsp;&nbsp;<strong>Individual Results:</strong><br>";
            foreach ($batch_result['results'] as $index => $result) {
                $format_status = $result['format_validation']['valid'] ? '✅' : '❌';
                $expiry_status = $result['expiry_validation']['valid'] ? '✅' : '❌';
                echo "&nbsp;&nbsp;&nbsp;&nbsp;$index. Format: $format_status, Expiry: $expiry_status<br>";
            }
        } catch (Exception $e) {
            echo "⚠️ Batch validation error: " . $e->getMessage() . "<br>";
        }

        // Test statistics and module info
        echo "<h4>5. Module Statistics:</h4>";
        try {
            $stats = $analyzer->get_validation_statistics();
            echo "📊 <strong>Validation Statistics:</strong><br>";
            foreach ($stats as $key => $value) {
                echo "&nbsp;&nbsp;• $key: $value<br>";
            }

            $info = $analyzer->get_module_info();
            echo "<br>ℹ️ <strong>Module Information:</strong><br>";
            echo "&nbsp;&nbsp;• Name: " . $info['name'] . "<br>";
            echo "&nbsp;&nbsp;• Version: " . $info['version'] . "<br>";
            echo "&nbsp;&nbsp;• Methods: " . implode(', ', $info['methods']) . "<br>";
            echo "&nbsp;&nbsp;• Cache size: " . $info['cache_size'] . "<br>";

            // Health check
            $health = $analyzer->health_check();
            echo "&nbsp;&nbsp;• Health: " . ($health ? '✅ Healthy' : '❌ Issues detected') . "<br>";

        } catch (Exception $e) {
            echo "⚠️ Statistics error: " . $e->getMessage() . "<br>";
        }

    } else {
        echo "❌ Validation Analyzer module class not found<br>";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Integration Test with Main Validator</h2>";

try {
    if (file_exists($plugin_dir . 'includes/class-vd-license-validator.php')) {
        require_once $plugin_dir . 'includes/class-vd-license-validator.php';

        if (class_exists('VD_License_Validator')) {
            $validator = VD_License_Validator::get_instance();
            echo "✅ Main validator loaded<br>";

            echo "<h3>Testing Delegation:</h3>";

            // Test delegation for format validation
            echo "<strong>Format validation delegation:</strong><br>";
            $format_result = $validator->validate_license_key_format('H10D-DIJD-14RC-SOLE-6KUV30', true);
            if (is_array($format_result) && isset($format_result['valid'])) {
                echo "&nbsp;&nbsp;" . ($format_result['valid'] ? '✅' : '❌') . " Delegation working<br>";
                echo "&nbsp;&nbsp;📋 Result type: " . gettype($format_result) . "<br>";
            } else {
                echo "&nbsp;&nbsp;❌ Delegation issue<br>";
            }

            // Test delegation for expiry validation
            echo "<strong>Expiry validation delegation:</strong><br>";
            $expiry_result = $validator->validate_license_expiry('H10D-DIJD-14RC-SOLE-6KUV30');
            if (is_array($expiry_result)) {
                echo "&nbsp;&nbsp;" . ($expiry_result['valid'] ? '✅' : '❌') . " Delegation working<br>";
                echo "&nbsp;&nbsp;📋 Result type: " . gettype($expiry_result) . "<br>";
            } else {
                echo "&nbsp;&nbsp;❌ Delegation issue<br>";
            }

            // Test delegation for batch validation
            echo "<strong>Batch validation delegation:</strong><br>";
            $batch_result = $validator->validate_license_keys_batch(array('H10D-DIJD-14RC-SOLE-6KUV30', 'TEST-KEY'));
            if (is_array($batch_result) && isset($batch_result['batch_size'])) {
                echo "&nbsp;&nbsp;✅ Delegation working<br>";
                echo "&nbsp;&nbsp;📊 Batch size: " . $batch_result['batch_size'] . "<br>";
            } else {
                echo "&nbsp;&nbsp;❌ Delegation issue<br>";
            }

        } else {
            echo "❌ Main validator class not available<br>";
        }
    } else {
        echo "❌ Main validator file not found<br>";
    }

} catch (Exception $e) {
    echo "❌ Integration test error: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Summary</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Step 4.3.2 Test Results</h3>";
echo "<p><strong>Module:</strong> Validation Analyzer</p>";
echo "<p><strong>Purpose:</strong> Core validation analysis for license format, status, expiry, and business rules</p>";
echo "<p><strong>Key Features Tested:</strong></p>";
echo "<ul>";
echo "<li>🔍 License key format validation with entropy analysis</li>";
echo "<li>📊 License status validation with business rules</li>";
echo "<li>⏰ License expiry validation with grace period calculation</li>";
echo "<li>🚀 Batch validation for multiple licenses</li>";
echo "<li>📈 Validation statistics and performance monitoring</li>";
echo "<li>🔗 Integration with main validator through delegation</li>";
echo "</ul>";
echo "<p><strong>Real License Used:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>";
echo "</div>";

echo "<p><strong>Test completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
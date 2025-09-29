<?php
/**
 * VD License Manager - Plugin Loading Fix Verification
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the comprehensive test function
$test_file = dirname(__FILE__) . '/test-vd-step-4-2-2.php';
if (file_exists($test_file)) {
    include_once $test_file;
}

echo "<h2>🔧 VD Plugin Loading Fix Verification - " . current_time('Y-m-d H:i:s') . "</h2>";

// 1. Current status
echo "<h3>1. Current Class Status</h3>";
echo "VD_License_Manager: " . (class_exists('VD_License_Manager') ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";
echo "VD_License_Validator: " . (class_exists('VD_License_Validator') ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";

// 2. Plugin status
echo "<h3>2. Plugin Status</h3>";
$plugin_file = 'vd-license-manager/vd-license-manager.php';
echo "Plugin active: " . (is_plugin_active($plugin_file) ? "✅ YES" : "❌ NO") . "<br>";

// 3. Manual init if needed
if (!class_exists('VD_License_Manager') && function_exists('vd_license_manager_init')) {
    echo "<h3>3. Manual Initialization Attempt</h3>";
    echo "Calling vd_license_manager_init()...<br>";

    try {
        vd_license_manager_init();
        echo "Init function completed<br>";
        echo "VD_License_Manager after init: " . (class_exists('VD_License_Manager') ? "✅ NOW LOADED" : "❌ STILL NOT LOADED") . "<br>";
    } catch (Exception $e) {
        echo "❌ Init error: " . $e->getMessage() . "<br>";
    }
}

// 4. Test Step 4.2.2 functionality if available
if (class_exists('VD_License_Validator')) {
    echo "<h3>4. Step 4.2.2 Comprehensive Functionality Test</h3>";

    try {
        $validator = VD_License_Validator::get_instance();
        if ($validator) {
            echo "✅ Got validator instance<br>";

            // Call the comprehensive test function if available
            if (function_exists('vd_test_step_4_2_2_enhanced_validation')) {
                echo "<h4>🧪 Running Comprehensive Step 4.2.2 Test Suite</h4>";
                $test_results = vd_test_step_4_2_2_enhanced_validation();

                if (is_array($test_results)) {
                    echo "<h4>📊 Test Summary</h4>";
                    echo "Tests Passed: {$test_results['passed_tests']}/{$test_results['total_tests']} ({$test_results['success_rate']}%)<br>";
                    echo "Status: {$test_results['status']}<br>";
                }
            } else {
                // Fallback to basic tests
                echo "<h4>⚠️ Running Basic Validation Tests (comprehensive test function not found)</h4>";

                // Test basic validation
                $test_key = 'H10D-DIJD-14RC-SOLE-6KUV30';
                $result = $validator->validate_license_key_format($test_key);
                echo "Basic validation ('{$test_key}'): " . ($result ? "✅ VALID" : "❌ INVALID") . "<br>";

                // Test enhanced methods
                if (method_exists($validator, 'get_detailed_validation')) {
                    $detailed = $validator->get_detailed_validation($test_key);
                    echo "Enhanced validation: " . (is_array($detailed) && $detailed['valid'] ? "✅ WORKING" : "❌ FAILED") . "<br>";

                    if (is_array($detailed) && isset($detailed['format_checks'])) {
                        echo "Format checks performed: " . count($detailed['format_checks']) . "<br>";
                    }
                } else {
                    echo "❌ get_detailed_validation method: NOT FOUND<br>";
                }

                if (method_exists($validator, 'vd_validate_license_key')) {
                    $business_result = $validator->vd_validate_license_key($test_key);
                    echo "Business logic wrapper: " . ($business_result ? "✅ WORKING" : "❌ FAILED") . "<br>";
                } else {
                    echo "❌ vd_validate_license_key method: NOT FOUND<br>";
                }

                if (method_exists($validator, 'validate_license_keys_batch')) {
                    echo "Batch validation method: ✅ AVAILABLE<br>";
                } else {
                    echo "❌ validate_license_keys_batch method: NOT FOUND<br>";
                }

                // Test different license formats
                echo "<h4>🔍 Format Compatibility Tests</h4>";
                $test_cases = array(
                    'H10D-DIJD-14RC-SOLE-6KUV30' => 'VD Standard',
                    'ABCD-EFGH-IJKL-MNOP' => 'LMfWC Standard',
                    'ABCDEFGH-IJKLMNOP-QRSTUVWX' => 'LMfWC Extended',
                    'ABCD-EFGH-IJKL' => 'Legacy Format',
                    'INVALID-KEY' => 'Invalid Format'
                );

                foreach ($test_cases as $key => $format) {
                    $valid = $validator->validate_license_key_format($key);
                    $status = $valid ? "✅ VALID" : "❌ INVALID";
                    echo "{$format}: {$status}<br>";
                }
            }

        } else {
            echo "❌ Failed to get validator instance<br>";
        }
    } catch (Exception $e) {
        echo "❌ Validator test error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "<h3>4. Step 4.2.2 Test</h3>";
    echo "❌ VD_License_Validator not available for testing<br>";
}

// 5. Check error logs for recent entries
echo "<h3>5. Recent Error Log Check</h3>";
$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $recent_lines = explode("\n", $log_content);
    $recent_lines = array_slice($recent_lines, -20); // Last 20 lines

    $vd_logs = array_filter($recent_lines, function($line) {
        return strpos($line, '[VD License Manager]') !== false;
    });

    if (!empty($vd_logs)) {
        echo "Recent VD logs (" . count($vd_logs) . " entries):<br>";
        foreach (array_slice($vd_logs, -5) as $log) { // Show last 5
            echo "<small>" . htmlspecialchars($log) . "</small><br>";
        }
    } else {
        echo "No recent VD logs found<br>";
    }
} else {
    echo "Debug log not found<br>";
}

// 6. Force reload test
echo "<h3>6. Force Reload Test</h3>";
if (current_user_can('manage_options')) {
    echo '<a href="' . admin_url('plugins.php?deactivate=' . urlencode($plugin_file)) . '" class="button">Deactivate Plugin</a> ';
    echo '<a href="' . admin_url('plugins.php?activate=' . urlencode($plugin_file)) . '" class="button button-primary">Activate Plugin</a><br>';
    echo '<small>Use these buttons to force reload the plugin if needed</small><br>';
}

// Log this test
error_log("[VD Plugin Loading Fix Test] VD_License_Manager: " . (class_exists('VD_License_Manager') ? 'LOADED' : 'NOT_LOADED') .
          ", VD_License_Validator: " . (class_exists('VD_License_Validator') ? 'LOADED' : 'NOT_LOADED'));

echo "<p><strong>Loading fix verification completed.</strong></p>";
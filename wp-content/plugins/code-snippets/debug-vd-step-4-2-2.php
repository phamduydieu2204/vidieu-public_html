<?php
/**
 * VD License Manager - Step 4.2.2 Simple Debug Test
 * Quick validation check
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🧪 VD Step 4.2.2 Debug Test - " . current_time('Y-m-d H:i:s') . "</h2>";

// Test 1: Check if class exists
echo "<h3>1. Class Check</h3>";
if (class_exists('VD_License_Validator')) {
    echo "✅ VD_License_Validator class EXISTS<br>";

    // Test 2: Get instance
    $validator = VD_License_Validator::get_instance();
    if ($validator) {
        echo "✅ Got instance successfully<br>";

        // Test 3: Simple validation
        echo "<h3>2. Simple Validation Test</h3>";
        $test_key = 'H10D-DIJD-14RC-SOLE-6KUV30';
        $result = $validator->validate_license_key_format($test_key);
        echo "Testing key: {$test_key}<br>";
        echo "Result: " . ($result ? "✅ VALID" : "❌ INVALID") . "<br>";

        // Test 4: Check new methods
        echo "<h3>3. New Methods Check</h3>";
        if (method_exists($validator, 'get_detailed_validation')) {
            echo "✅ get_detailed_validation() method EXISTS<br>";

            // Test detailed validation
            $detailed = $validator->get_detailed_validation($test_key);
            if (is_array($detailed)) {
                echo "✅ Detailed validation working<br>";
                echo "Valid: " . ($detailed['valid'] ? 'YES' : 'NO') . "<br>";
                if (isset($detailed['format_checks'])) {
                    echo "Format checks: " . count($detailed['format_checks']) . "<br>";
                }
            } else {
                echo "❌ Detailed validation failed<br>";
            }
        } else {
            echo "❌ get_detailed_validation() method NOT FOUND<br>";
        }

        if (method_exists($validator, 'vd_validate_license_key')) {
            echo "✅ vd_validate_license_key() method EXISTS<br>";

            $business_result = $validator->vd_validate_license_key($test_key);
            echo "Business logic result: " . ($business_result ? "✅ VALID" : "❌ INVALID") . "<br>";
        } else {
            echo "❌ vd_validate_license_key() method NOT FOUND<br>";
        }

        if (method_exists($validator, 'validate_license_keys_batch')) {
            echo "✅ validate_license_keys_batch() method EXISTS<br>";
        } else {
            echo "❌ validate_license_keys_batch() method NOT FOUND<br>";
        }

        // Test 5: Error case
        echo "<h3>4. Error Testing</h3>";
        $invalid_key = 'INVALID-KEY';
        $error_result = $validator->get_detailed_validation($invalid_key);
        if (isset($error_result['error_code'])) {
            echo "✅ Error detection working: " . $error_result['error_code'] . "<br>";
            echo "Error message: " . $error_result['error_message'] . "<br>";
        } else {
            echo "⚠️ Error detection not working as expected<br>";
        }

    } else {
        echo "❌ Failed to get instance<br>";
    }
} else {
    echo "❌ VD_License_Validator class NOT FOUND<br>";

    // Check if main plugin is loaded
    if (class_exists('VD_License_Manager')) {
        echo "✅ VD_License_Manager class exists<br>";
    } else {
        echo "❌ VD_License_Manager class NOT FOUND<br>";
    }
}

echo "<h3>5. File Check</h3>";
$validator_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-license-validator.php';
if (file_exists($validator_file)) {
    echo "✅ Validator file EXISTS: {$validator_file}<br>";
    echo "File size: " . round(filesize($validator_file) / 1024, 1) . " KB<br>";
} else {
    echo "❌ Validator file NOT FOUND: {$validator_file}<br>";
}

// Log to debug.log
error_log("[VD Debug Test 4.2.2] Class exists: " . (class_exists('VD_License_Validator') ? 'YES' : 'NO'));

echo "<p><strong>Debug test completed. Check results above.</strong></p>";
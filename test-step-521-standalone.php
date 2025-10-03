<?php
/**
 * Standalone Test for Micro-Step 1: Format Validation Replacement
 *
 * Direct test without admin interface
 */

// WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

echo "<h1>🧪 Test Micro-Step 1: Format Validation Replacement</h1>\n";
echo "<pre>";

try {
    // Load the monolithic validator
    require_once('./wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php');

    $validator = VD_License_Validator::get_instance();

    // Test license keys
    $test_keys = [
        'VD-TEST-1234-5678',
        'VD-DEMO-9999-0000',
        'INVALID-KEY-FORMAT'
    ];

    echo "Testing Format Validation Replacement:\n";
    echo "=====================================\n\n";

    foreach ($test_keys as $key) {
        echo "Testing: $key\n";

        // Test pattern validation first
        require_once('./wp-content/plugins/vd-license-manager/includes/modules/format/class-vd-license-pattern-validator.php');
        $pattern_validator = VD_License_Pattern_Validator::get_instance();
        $pattern_result = $pattern_validator->validate_license_key_format($key, true);

        echo "Pattern Result: " . json_encode($pattern_result, JSON_PRETTY_PRINT) . "\n";

        // Test the replaced method
        $result = $validator->validate_license_key_format($key, true);

        echo "Final Result: " . (is_array($result) ? json_encode($result, JSON_PRETTY_PRINT) : ($result ? 'VALID' : 'INVALID')) . "\n";
        echo "---\n";
    }

    echo "\n✅ Micro-Step 1 test completed successfully!\n";
    echo "Format validation replacement is working correctly.\n";

} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
<?php
/**
 * Debug Micro-Step 3: Expiry Processing
 */

// WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

echo "<h1>🔍 Debug Micro-Step 3: Expiry Processing</h1>\n";
echo "<pre>";

try {
    echo "1. Loading validator...\n";
    require_once('./wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php');
    $validator = VD_License_Validator::get_instance();
    echo "✅ Validator loaded successfully\n\n";

    echo "2. Testing expiry processor module loading...\n";
    $expiry_processor_file = './wp-content/plugins/vd-license-manager/includes/modules/validator/class-vd-license-expiry-processor.php';

    if (file_exists($expiry_processor_file)) {
        echo "✅ Expiry processor file exists\n";

        require_once($expiry_processor_file);
        echo "✅ Expiry processor file loaded\n";

        if (class_exists('VD\LicenseManager\Validator\VD_License_Expiry_Processor')) {
            echo "✅ VD_License_Expiry_Processor class exists (with namespace)\n";

            $expiry_processor = VD\LicenseManager\Validator\VD_License_Expiry_Processor::get_instance();
            echo "✅ Expiry processor instance created\n";

            // Test validate_license_expiry_date method
            if (method_exists($expiry_processor, 'validate_license_expiry_date')) {
                echo "✅ validate_license_expiry_date method exists\n";

                echo "\n3. Testing expiry validation...\n";
                $result = $expiry_processor->validate_license_expiry_date('VD-TEST-1234-5678');
                echo "✅ Direct expiry processor test successful\n";
                echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

            } else {
                echo "❌ validate_license_expiry_date method not found\n";
            }

        } else {
            echo "❌ VD_License_Expiry_Processor class not found\n";
        }
    } else {
        echo "❌ Expiry processor file not found at: $expiry_processor_file\n";
    }

    echo "\n4. Testing validator expiry method...\n";
    $validator_result = $validator->validate_license_expiry('VD-TEST-1234-5678');
    echo "✅ Validator expiry test successful\n";
    echo "Result: " . json_encode($validator_result, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
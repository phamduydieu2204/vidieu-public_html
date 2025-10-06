<?php
// Direct Fix for VD_License_Validator namespace issues
header('Content-Type: text/plain; charset=utf-8');

echo "⚡ DIRECT FIX for VD_License_Validator\n";
echo "=====================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

if (!file_exists($validator_file)) {
    echo "❌ File not found\n";
    exit;
}

echo "Target: " . basename($validator_file) . "\n";
echo "Size: " . number_format(filesize($validator_file)) . " bytes\n\n";

// Read content
$content = file_get_contents($validator_file);

// Direct string replacements based on the exact patterns we found
$fixes = [
    // class_exists fixes
    "class_exists('VD\\LicenseManager\\Validator\\VD_License_Expiry_Processor')" => "class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Expiry_Processor')",
    "class_exists('VD\\LicenseManager\\Validator\\VD_License_Status_Transition_Controller')" => "class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller')",
    "class_exists('VD\\LicenseManager\\Validator\\VD_License_Validation_Orchestrator')" => "class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Validation_Orchestrator')",
    "class_exists('VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator')" => "class_exists('VD\\\\LicenseManager\\\\Compliance\\\\VD_License_Security_Integration_Validator')",

    // Instantiation fixes
    "VD\\LicenseManager\\Validator\\VD_License_Expiry_Processor::get_instance()" => "VD\\\\LicenseManager\\\\Validator\\\\VD_License_Expiry_Processor::get_instance()",
    "VD\\LicenseManager\\Validator\\VD_License_Status_Transition_Controller::get_instance()" => "VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller::get_instance()",
    "VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator::get_instance()" => "VD\\\\LicenseManager\\\\Compliance\\\\VD_License_Security_Integration_Validator::get_instance()",
];

echo "=== Applying Direct Fixes ===\n";

$changes_made = 0;
foreach ($fixes as $search => $replace) {
    $before_count = substr_count($content, $search);
    if ($before_count > 0) {
        $content = str_replace($search, $replace, $content);
        $after_count = substr_count($content, $search);
        $fixed_count = $before_count - $after_count;
        echo "✅ Fixed '$search': $fixed_count instances\n";
        $changes_made += $fixed_count;
    }
}

echo "\nTotal changes made: $changes_made\n";

if ($changes_made > 0) {
    // Create backup
    $backup_file = $validator_file . '.backup.' . time();
    copy($validator_file, $backup_file);
    echo "✅ Backup: " . basename($backup_file) . "\n";

    // Write fixed content
    if (file_put_contents($validator_file, $content)) {
        echo "✅ File updated successfully\n";
        echo "New size: " . number_format(filesize($validator_file)) . " bytes\n";
    } else {
        echo "❌ Failed to write file\n";
        exit;
    }
} else {
    echo "ℹ️ No changes needed - file appears correct\n";
}

// Verify fixes
echo "\n=== Verification ===\n";
$new_content = file_get_contents($validator_file);

// Count remaining issues
$remaining_class_exists = 0;
$remaining_instantiation = 0;

$lines = explode("\n", $new_content);
foreach ($lines as $num => $line) {
    if (preg_match('/class_exists\([\'"][^\'"]*(\\\\)[^\\\\]/', $line)) {
        $remaining_class_exists++;
    }
    if (preg_match('/= VD\\\\[^\\\\].*::get_instance/', $line)) {
        $remaining_instantiation++;
    }
}

echo "Remaining class_exists issues: $remaining_class_exists\n";
echo "Remaining instantiation issues: $remaining_instantiation\n";

$total_remaining = $remaining_class_exists + $remaining_instantiation;
echo "Total remaining issues: $total_remaining\n";

if ($total_remaining == 0) {
    echo "🎉 ALL NAMESPACE ISSUES FIXED!\n";
} else {
    echo "⚠️ Still has $total_remaining issues\n";
}

// Test syntax
echo "\n=== Syntax Test ===\n";
$tokens = @token_get_all($new_content);
if ($tokens === false) {
    echo "❌ Syntax error still exists\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
} else {
    echo "✅ Syntax is valid\n";
}

// Test loading
echo "\n=== Loading Test ===\n";

// Clear cache
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($validator_file, true);
    echo "✅ Cache cleared\n";
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
error_clear_last();

try {
    include $validator_file;
    echo "✅ File loaded successfully\n";
} catch (ParseError $e) {
    echo "❌ Parse Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
if ($output) {
    echo "Output: $output\n";
}

echo "VD_License_Validator available: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

echo "\n" . str_repeat("=", 50) . "\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 FINAL SUCCESS!\n";
    echo "VD_License_Validator is ready for Step 4.4.3.5!\n";
} else {
    echo "❌ Still not working - may need manual investigation\n";
}

echo "Completed: " . date('Y-m-d H:i:s T') . "\n";
?>
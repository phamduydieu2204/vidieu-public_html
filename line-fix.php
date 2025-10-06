<?php
// Manual line-by-line fix for namespace issues
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

header('Content-Type: text/plain; charset=utf-8');

echo "🔧 MANUAL LINE-BY-LINE FIX\n";
echo "==========================\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

if (!file_exists($validator_file)) {
    echo "❌ File not found\n";
    exit;
}

// Read file
$content = file_get_contents($validator_file);
$lines = explode("\n", $content);

echo "Current file: " . number_format(strlen($content)) . " chars, " . count($lines) . " lines\n\n";

// Manual fixes for the exact lines we know have issues
$manual_fixes = [
    1520 => "        if (!class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Expiry_Processor')) {",
    1524 => "        \$expiry_processor = VD\\\\LicenseManager\\\\Validator\\\\VD_License_Expiry_Processor::get_instance();",
    2203 => "        if (!class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Expiry_Processor')) {",
    2207 => "        \$expiry_processor = VD\\\\LicenseManager\\\\Validator\\\\VD_License_Expiry_Processor::get_instance();",
    2233 => "        if (!class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller')) {",
    2237 => "        \$status_controller = VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller::get_instance();",
    2348 => "        if (!class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller')) {",
    2352 => "        \$status_controller = VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller::get_instance();",
    2428 => "        if (!class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller')) {",
    2432 => "        \$status_controller = VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller::get_instance();",
    6422 => "        if (!class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Validation_Orchestrator')) {",
    6427 => "            \$orchestrator = \\\\VD\\\\LicenseManager\\\\Validator\\\\VD_License_Validation_Orchestrator::get_instance();",
    6845 => "            \$security_validator = VD\\\\LicenseManager\\\\Compliance\\\\VD_License_Security_Integration_Validator::get_instance();",
    6921 => "                'generate_advanced_validation_report' => class_exists('VD\\\\LicenseManager\\\\Validator\\\\VD_License_Validation_Orchestrator')",
    7368 => "            if (!class_exists('VD\\\\LicenseManager\\\\Compliance\\\\VD_License_Security_Integration_Validator')) {",
    7376 => "            if (class_exists('VD\\\\LicenseManager\\\\Compliance\\\\VD_License_Security_Integration_Validator')) {",
    7377 => "                \$security_validator = VD\\\\LicenseManager\\\\Compliance\\\\VD_License_Security_Integration_Validator::get_instance();",
];

echo "=== APPLYING MANUAL FIXES ===\n";

$changes_made = 0;
foreach ($manual_fixes as $line_num => $new_content) {
    if (isset($lines[$line_num - 1])) {
        $old_content = $lines[$line_num - 1];
        if (trim($old_content) !== trim($new_content)) {
            echo "Line $line_num:\n";
            echo "  OLD: " . trim($old_content) . "\n";
            echo "  NEW: " . trim($new_content) . "\n";
            $lines[$line_num - 1] = $new_content;
            $changes_made++;
        } else {
            echo "Line $line_num: Already correct\n";
        }
    }
}

echo "\nTotal changes: $changes_made\n";

if ($changes_made > 0) {
    // Create backup
    $backup_file = $validator_file . '.manual.backup.' . time();
    copy($validator_file, $backup_file);
    echo "✅ Backup: " . basename($backup_file) . "\n";

    // Write new content
    $new_content = implode("\n", $lines);
    if (file_put_contents($validator_file, $new_content)) {
        echo "✅ File updated successfully\n";
        echo "New size: " . number_format(strlen($new_content)) . " chars\n";
    } else {
        echo "❌ Failed to write file\n";
        exit;
    }
} else {
    echo "ℹ️ No changes needed\n";
}

// Test syntax
echo "\n=== SYNTAX TEST ===\n";
$test_content = file_get_contents($validator_file);
$tokens = @token_get_all($test_content);

if ($tokens === false) {
    echo "❌ Syntax error exists\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
} else {
    echo "✅ Syntax is valid (" . number_format(count($tokens)) . " tokens)\n";
}

// Test loading
echo "\n=== LOADING TEST ===\n";

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
    echo "✅ File included successfully\n";
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

$error = error_get_last();
if ($error) {
    echo "❌ PHP Error: " . $error['message'] . " on line " . $error['line'] . "\n";
}

echo "VD_License_Validator available: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

echo "\n" . str_repeat("=", 40) . "\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 MANUAL FIX SUCCESS!\n";
    echo "VD_License_Validator is now available!\n";
    echo "Ready for Step 4.4.3.5!\n";
} else {
    echo "❌ Manual fix failed\n";
    echo "May need additional investigation\n";
}

echo "Completed: " . date('Y-m-d H:i:s T') . "\n";
?>
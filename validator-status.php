<?php
// Direct validation without WordPress
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output as plain text
header('Content-Type: text/plain; charset=utf-8');

echo "VD_License_Validator Syntax Status\n";
echo "==================================\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

if (!file_exists($validator_file)) {
    echo "❌ File not found: $validator_file\n";
    exit(1);
}

echo "✅ File found: " . basename($validator_file) . "\n";
echo "Size: " . number_format(filesize($validator_file)) . " bytes\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($validator_file)) . "\n\n";

// Read and test content
$content = file_get_contents($validator_file);

// Test tokenization
$tokens = @token_get_all($content);

if ($tokens === false) {
    echo "❌ SYNTAX ERROR!\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
} else {
    echo "✅ SYNTAX VALID!\n";
    echo "Tokens: " . number_format(count($tokens)) . "\n\n";

    // Count fixes
    $fixes = [
        'Expiry_Processor' => substr_count($content, 'VD\\\\LicenseManager\\\\Validator\\\\VD_License_Expiry_Processor::get_instance()'),
        'Status_Controller' => substr_count($content, 'VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller::get_instance()'),
        'Validation_Orchestrator' => substr_count($content, 'VD\\\\LicenseManager\\\\Validator\\\\VD_License_Validation_Orchestrator::get_instance()'),
        'Security_Integration' => substr_count($content, 'VD\\\\LicenseManager\\\\Compliance\\\\VD_License_Security_Integration_Validator::get_instance()')
    ];

    echo "Fixed namespace instances:\n";
    foreach ($fixes as $name => $count) {
        echo "  $name: $count\n";
    }

    $total_fixes = array_sum($fixes);
    echo "\nTotal fixed: $total_fixes instances\n";

    if ($total_fixes > 0) {
        echo "🎉 All namespace syntax errors have been FIXED!\n";
    } else {
        echo "⚠️  No fixed instances found\n";
    }
}

echo "\nTest time: " . date('Y-m-d H:i:s T') . "\n";
?>
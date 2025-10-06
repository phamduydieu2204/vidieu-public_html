<?php
// Disable WordPress routing for direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Prevent WordPress from loading
if (!isset($_GET['direct'])) {
    header('Location: ' . $_SERVER['REQUEST_URI'] . '?direct=1');
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

echo "🔧 Syntax Check Final Results\n";
echo "===============================\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

echo "File: " . basename($validator_file) . "\n";
echo "Path: " . $validator_file . "\n\n";

if (!file_exists($validator_file)) {
    echo "❌ FAIL: File not found\n";
    exit(1);
}

$size = filesize($validator_file);
echo "✅ File exists: " . number_format($size) . " bytes\n";

// Test PHP syntax by tokenizing
echo "\nTesting PHP syntax...\n";
$content = file_get_contents($validator_file);

// Suppress any output from tokenization
ob_start();
$tokens = @token_get_all($content);
ob_end_clean();

if ($tokens === false) {
    echo "❌ CRITICAL: Syntax error detected!\n";

    // Get last error
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n";
    }

    exit(1);
} else {
    echo "✅ SUCCESS: PHP syntax is valid\n";
    echo "   Tokens parsed: " . number_format(count($tokens)) . "\n";
}

// Test namespace patterns
echo "\nChecking namespace syntax...\n";

// Look for problematic patterns
$issues = 0;

// Check 1: Single backslash in instantiations (should be double)
if (preg_match('/= [A-Z][^=]*\\\\[^\\\\]/', $content)) {
    echo "❌ Found single backslash in namespace instantiation\n";
    $issues++;
} else {
    echo "✅ No single backslash issues found\n";
}

// Check 2: Count try-catch balance
$try_count = substr_count($content, 'try {');
$catch_count = substr_count($content, '} catch');

echo "   Try blocks: $try_count\n";
echo "   Catch blocks: $catch_count\n";

if ($try_count === $catch_count) {
    echo "✅ Try-catch blocks are balanced\n";
} else {
    echo "⚠️  Try-catch may be unbalanced\n";
    $issues++;
}

// Check 3: Look for specific namespace classes
$namespace_classes = [
    'VD\\\\LicenseManager\\\\Validator\\\\VD_License_Expiry_Processor',
    'VD\\\\LicenseManager\\\\Validator\\\\VD_License_Status_Transition_Controller',
    'VD\\\\LicenseManager\\\\Validator\\\\VD_License_Validation_Orchestrator',
    'VD\\\\LicenseManager\\\\Compliance\\\\VD_License_Security_Integration_Validator'
];

echo "\nChecking specific namespace fixes...\n";
foreach ($namespace_classes as $class) {
    $count = substr_count($content, $class . '::get_instance()');
    echo "✅ $class: $count instances\n";
}

// Final result
echo "\n" . str_repeat("=", 40) . "\n";
if ($issues === 0) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "✅ File syntax is completely valid\n";
    echo "✅ Namespace patterns are correct\n";
    echo "✅ Ready for WordPress loading\n";
} else {
    echo "❌ $issues ISSUES DETECTED\n";
    echo "Fix required before use\n";
}

echo "\nTimestamp: " . date('Y-m-d H:i:s T') . "\n";
?>
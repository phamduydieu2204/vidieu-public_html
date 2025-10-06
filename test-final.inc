<?php
// Final test for VD_License_Validator after complete namespace fix
header('Content-Type: text/plain; charset=utf-8');

echo "🎯 FINAL VD_License_Validator Test\n";
echo "=================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n\n";

// Bootstrap WordPress manually
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
    require_once(ABSPATH . 'wp-config.php');
    require_once(ABSPATH . 'wp-includes/wp-db.php');
    require_once(ABSPATH . 'wp-includes/pluggable.php');
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

$validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

echo "File: " . basename($validator_file) . "\n";
echo "Size: " . number_format(filesize($validator_file)) . " bytes\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($validator_file)) . "\n\n";

// Test 1: Basic syntax
echo "=== Test 1: Basic Syntax ===\n";
$content = file_get_contents($validator_file);
$tokens = @token_get_all($content);

if ($tokens === false) {
    echo "❌ FAILED: Syntax error in tokenization\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
    exit;
} else {
    echo "✅ SUCCESS: Syntax is valid\n";
    echo "Tokens: " . number_format(count($tokens)) . "\n";
}

// Test 2: Check for remaining namespace issues
echo "\n=== Test 2: Namespace Issues Check ===\n";
$single_backslash_count = preg_match_all('/class_exists\([\'"]VD\\\\[^\\\\]/', $content);
echo "Single backslash in class_exists(): " . $single_backslash_count . "\n";

$single_backslash_instantiation = preg_match_all('/= VD\\\\[^\\\\]/', $content);
echo "Single backslash in instantiation: " . $single_backslash_instantiation . "\n";

if ($single_backslash_count == 0 && $single_backslash_instantiation == 0) {
    echo "✅ SUCCESS: No namespace issues found\n";
} else {
    echo "❌ FAILED: Namespace issues still exist\n";
}

// Test 3: Manual loading
echo "\n=== Test 3: Manual Loading ===\n";
echo "Class exists before: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
error_clear_last();

$load_success = false;
try {
    require_once $validator_file;
    $load_success = true;
    echo "✅ File included successfully\n";
} catch (ParseError $e) {
    echo "❌ Parse Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
if ($output) {
    echo "Output during loading:\n" . $output . "\n";
}

$error = error_get_last();
if ($error) {
    echo "❌ PHP Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n";
}

echo "Class exists after: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

// Test 4: Final verification
echo "\n=== Test 4: Final Verification ===\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 SUCCESS: VD_License_Validator is available!\n";

    $reflection = new ReflectionClass('VD_License_Validator');
    echo "Methods: " . count($reflection->getMethods()) . "\n";

    if ($reflection->hasMethod('validate_security_compliance')) {
        echo "✅ validate_security_compliance method exists\n";
    } else {
        echo "❌ validate_security_compliance method missing\n";
    }
} else {
    echo "❌ FAILED: VD_License_Validator still not available\n";
}

// Test 5: List all VD classes
echo "\n=== Test 5: All VD Classes ===\n";
$all_classes = get_declared_classes();
$vd_classes = array_filter($all_classes, function($class) {
    return strpos($class, 'VD_') === 0;
});

echo "Total VD_* classes: " . count($vd_classes) . "\n";
foreach ($vd_classes as $class) {
    if ($class === 'VD_License_Validator') {
        echo "🎯 " . $class . " (TARGET CLASS)\n";
    } else {
        echo "   " . $class . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 FINAL RESULT: SUCCESS!\n";
    echo "VD_License_Validator is ready for use!\n";
} else {
    echo "❌ FINAL RESULT: FAILED!\n";
    echo "Additional investigation needed.\n";
}

echo "Test completed: " . date('Y-m-d H:i:s T') . "\n";
?>
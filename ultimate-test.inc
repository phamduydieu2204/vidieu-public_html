<?php
// Ultimate VD_License_Validator Test - Force Cache Refresh
header('Content-Type: text/plain; charset=utf-8');

echo "🎯 ULTIMATE VD_License_Validator Test\n";
echo "====================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n";
echo "Cache Buster: " . time() . "\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

if (!file_exists($validator_file)) {
    echo "❌ File not found: $validator_file\n";
    exit;
}

echo "File: " . basename($validator_file) . "\n";
echo "Size: " . number_format(filesize($validator_file)) . " bytes\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($validator_file)) . "\n\n";

// Force refresh by clearing any opcode cache
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($validator_file, true);
    echo "✅ OPcache invalidated\n";
}

if (function_exists('apc_delete_file')) {
    apc_delete_file($validator_file);
    echo "✅ APC cache cleared\n";
}

// Read fresh content
$content = file_get_contents($validator_file);
echo "Content read: " . number_format(strlen($content)) . " characters\n\n";

// Test 1: Count namespace issues
echo "=== Namespace Issue Check ===\n";
$single_backslash_class_exists = preg_match_all('/class_exists\([\'"][^\'"]+(\\\\)[^\\\\]/', $content);
$single_backslash_instantiation = preg_match_all('/= [A-Z][^=]*(\\\\)[^\\\\].*::get_instance/', $content);

echo "Single backslash in class_exists(): $single_backslash_class_exists\n";
echo "Single backslash in instantiation: $single_backslash_instantiation\n";

if ($single_backslash_class_exists == 0 && $single_backslash_instantiation == 0) {
    echo "✅ No namespace syntax issues found\n";
} else {
    echo "❌ Namespace issues still exist\n";
}

// Test 2: Basic syntax check
echo "\n=== Basic Syntax Check ===\n";
$tokens = @token_get_all($content);
if ($tokens === false) {
    echo "❌ Tokenization failed\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
} else {
    echo "✅ Tokenization successful: " . number_format(count($tokens)) . " tokens\n";
}

// Test 3: Manual loading with maximum error reporting
echo "\n=== Manual Loading Test ===\n";
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Class exists before: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

ob_start();
error_clear_last();

set_error_handler(function($severity, $message, $file, $line) {
    echo "PHP Error (Level $severity): $message in " . basename($file) . " on line $line\n";
    return false;
});

$load_success = false;
try {
    // Use include instead of require_once to force reload
    include $validator_file;
    $load_success = true;
    echo "✅ File loaded successfully\n";
} catch (ParseError $e) {
    echo "❌ Parse Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

restore_error_handler();
$output = ob_get_clean();

if ($output) {
    echo "Output during loading:\n$output\n";
}

$error = error_get_last();
if ($error) {
    echo "❌ Last PHP error:\n";
    echo "  Message: " . $error['message'] . "\n";
    echo "  File: " . basename($error['file']) . "\n";
    echo "  Line: " . $error['line'] . "\n";
}

echo "Class exists after: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

// Test 4: Final verification
echo "\n=== Final Verification ===\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 SUCCESS: VD_License_Validator is available!\n";

    $reflection = new ReflectionClass('VD_License_Validator');
    echo "Class file: " . $reflection->getFileName() . "\n";
    echo "Methods: " . count($reflection->getMethods()) . "\n";

    if ($reflection->hasMethod('validate_security_compliance')) {
        echo "✅ validate_security_compliance method exists\n";
    }

} else {
    echo "❌ VD_License_Validator is NOT available\n";

    // List similar classes
    $all_classes = get_declared_classes();
    $vd_validator_classes = array_filter($all_classes, function($class) {
        return strpos($class, 'VD_') === 0 && strpos($class, 'Validator') !== false;
    });

    if ($vd_validator_classes) {
        echo "Related VD_*Validator classes found:\n";
        foreach ($vd_validator_classes as $class) {
            echo "  - $class\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 ULTIMATE RESULT: SUCCESS!\n";
    echo "All namespace syntax errors have been resolved!\n";
    echo "VD_License_Validator is ready for Step 4.4.3.5!\n";
} else {
    echo "❌ ULTIMATE RESULT: STILL FAILED\n";
    echo "Additional investigation or cache clearing needed.\n";
}

echo "\nTest completed: " . date('Y-m-d H:i:s T') . "\n";
?>
<?php
// Advanced Class Debug - Bypass WordPress routing
if (!defined('ABSPATH')) {
    // Manually bootstrap WordPress without routing
    define('ABSPATH', dirname(__FILE__) . '/');
    require_once(ABSPATH . 'wp-config.php');
    require_once(ABSPATH . 'wp-includes/wp-db.php');
    require_once(ABSPATH . 'wp-includes/pluggable.php');
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

header('Content-Type: text/plain; charset=utf-8');

echo "🔬 VD_License_Validator Advanced Debug\n";
echo "====================================\n\n";

$validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

echo "Target file: " . basename($validator_file) . "\n";
echo "Full path: " . $validator_file . "\n";
echo "File exists: " . (file_exists($validator_file) ? 'YES' : 'NO') . "\n\n";

if (!file_exists($validator_file)) {
    echo "❌ Cannot proceed - file not found\n";
    exit;
}

echo "File size: " . number_format(filesize($validator_file)) . " bytes\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($validator_file)) . "\n\n";

// Step 1: Check if class already exists
echo "=== STEP 1: Pre-loading Check ===\n";
echo "Class exists (no autoload): " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";
echo "Class exists (with autoload): " . (class_exists('VD_License_Validator', true) ? 'YES' : 'NO') . "\n\n";

// Step 2: Maximum error reporting
echo "=== STEP 2: Error Configuration ===\n";
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
echo "Error reporting enabled at maximum level\n\n";

// Step 3: Analyze file content first
echo "=== STEP 3: File Content Analysis ===\n";
$content = file_get_contents($validator_file);
$lines = explode("\n", $content);

echo "Total lines: " . count($lines) . "\n";

// Look for namespace
$namespace_found = false;
foreach ($lines as $num => $line) {
    if (preg_match('/^\s*namespace\s+/', $line)) {
        echo "❌ FOUND NAMESPACE on line " . ($num + 1) . ": " . trim($line) . "\n";
        $namespace_found = true;
        break;
    }
}

if (!$namespace_found) {
    echo "✅ No namespace declaration found\n";
}

// Look for class declaration
$class_found = false;
foreach ($lines as $num => $line) {
    if (preg_match('/^\s*class\s+VD_License_Validator/', $line)) {
        echo "✅ Class declaration found on line " . ($num + 1) . ": " . trim($line) . "\n";
        $class_found = true;
        break;
    }
}

if (!$class_found) {
    echo "❌ Class declaration not found!\n";
}

echo "\n";

// Step 4: Attempt manual loading with full error capture
echo "=== STEP 4: Manual Loading Attempt ===\n";

// Clear any previous errors
error_clear_last();

// Custom error handler
$captured_errors = [];
set_error_handler(function($severity, $message, $file, $line) use (&$captured_errors) {
    $captured_errors[] = "Error (Level $severity): $message in $file on line $line";
    return false;
});

// Capture all output
ob_start();

$load_success = false;
$load_error = null;

try {
    echo "Attempting include_once...\n";
    $result = include_once $validator_file;
    echo "Include result: " . var_export($result, true) . "\n";
    $load_success = true;
} catch (ParseError $e) {
    $load_error = "Parse Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
} catch (Error $e) {
    $load_error = "Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
} catch (Exception $e) {
    $load_error = "Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}

$output = ob_get_clean();
restore_error_handler();

echo "Loading completed: " . ($load_success ? 'SUCCESS' : 'FAILED') . "\n";

if ($load_error) {
    echo "❌ Error: " . $load_error . "\n";
}

if ($captured_errors) {
    echo "❌ Captured errors:\n";
    foreach ($captured_errors as $error) {
        echo "  - " . $error . "\n";
    }
}

if ($output) {
    echo "Output during loading:\n";
    echo $output . "\n";
}

$last_error = error_get_last();
if ($last_error) {
    echo "❌ Last PHP error:\n";
    echo "  Message: " . $last_error['message'] . "\n";
    echo "  File: " . $last_error['file'] . "\n";
    echo "  Line: " . $last_error['line'] . "\n";
}

echo "\n";

// Step 5: Post-loading analysis
echo "=== STEP 5: Post-loading Analysis ===\n";
echo "Class exists after loading (no autoload): " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";
echo "Class exists after loading (with autoload): " . (class_exists('VD_License_Validator', true) ? 'YES' : 'NO') . "\n";

// List all VD_* classes
$all_classes = get_declared_classes();
$vd_classes = array_filter($all_classes, function($class) {
    return strpos($class, 'VD_') === 0;
});

echo "VD_* classes found: " . count($vd_classes) . "\n";
if ($vd_classes) {
    foreach ($vd_classes as $class) {
        echo "  - " . $class . "\n";
    }
}

echo "\n=== CONCLUSION ===\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 SUCCESS: Class is now available!\n";
} else {
    echo "❌ FAILED: Class still not available\n";
    if ($namespace_found) {
        echo "Likely cause: Class is in a namespace\n";
    } elseif ($load_error) {
        echo "Likely cause: " . $load_error . "\n";
    } else {
        echo "Likely cause: Unknown structural issue\n";
    }
}

echo "\nCompleted at: " . date('Y-m-d H:i:s T') . "\n";
?>
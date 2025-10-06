<?php
// Check file integrity and identify discrepancies
header('Content-Type: text/plain; charset=utf-8');

echo "🔍 File Integrity Check\n";
echo "======================\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

if (!file_exists($validator_file)) {
    echo "❌ File not found\n";
    exit;
}

echo "File: " . basename($validator_file) . "\n";
echo "Size: " . number_format(filesize($validator_file)) . " bytes\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($validator_file)) . "\n\n";

// Read content
$content = file_get_contents($validator_file);
$lines = explode("\n", $content);

echo "=== File Analysis ===\n";
echo "Total lines: " . count($lines) . "\n";
echo "Total characters: " . number_format(strlen($content)) . "\n";

// Find class declaration
$class_line = null;
foreach ($lines as $num => $line) {
    if (preg_match('/^class\s+VD_License_Validator/', trim($line))) {
        $class_line = $num + 1;
        break;
    }
}

if ($class_line) {
    echo "✅ Class declaration found on line: " . $class_line . "\n";
    echo "Declaration: " . trim($lines[$class_line - 1]) . "\n";
} else {
    echo "❌ Class declaration NOT found!\n";
}

// Check for class closure
$last_lines = array_slice($lines, -10);
$has_closing_brace = false;
foreach ($last_lines as $line) {
    if (trim($line) === '}') {
        $has_closing_brace = true;
        break;
    }
}

echo "Class properly closed: " . ($has_closing_brace ? 'YES' : 'NO') . "\n";

// Check for namespace issues
$namespace_issues = 0;
foreach ($lines as $num => $line) {
    // Check for single backslash in class_exists
    if (preg_match('/class_exists\([\'"]VD\\\\[^\\\\]/', $line)) {
        echo "⚠️ Single backslash in class_exists on line " . ($num + 1) . "\n";
        $namespace_issues++;
    }

    // Check for single backslash in instantiation
    if (preg_match('/= VD\\\\[^\\\\]/', $line)) {
        echo "⚠️ Single backslash in instantiation on line " . ($num + 1) . "\n";
        $namespace_issues++;
    }
}

echo "Namespace issues found: " . $namespace_issues . "\n\n";

// Test actual content parsing
echo "=== Content Parsing Test ===\n";

// Create a minimal test file
$test_content = "<?php\nclass TestClass { public function test() { return 'works'; } }";
$test_file = __DIR__ . '/test_class_check.php';
file_put_contents($test_file, $test_content);

echo "Testing with simple class...\n";
ob_start();
include $test_file;
$output = ob_get_clean();

if (class_exists('TestClass', false)) {
    echo "✅ Simple class test: SUCCESS\n";
    $test_obj = new TestClass();
    echo "Method call result: " . $test_obj->test() . "\n";
} else {
    echo "❌ Simple class test: FAILED\n";
}

unlink($test_file);

// Now test our actual validator file
echo "\nTesting VD_License_Validator file...\n";

ob_start();
error_clear_last();

try {
    include $validator_file;
    echo "✅ Include completed without exceptions\n";
} catch (ParseError $e) {
    echo "❌ Parse Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

$include_output = ob_get_clean();
if ($include_output) {
    echo "Output during include:\n" . $include_output . "\n";
}

$error = error_get_last();
if ($error) {
    echo "❌ PHP Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n";
}

// Check class existence
echo "\nClass availability after include:\n";
echo "VD_License_Validator exists: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

// List all defined classes for comparison
$all_classes = get_declared_classes();
$vd_related = array_filter($all_classes, function($class) {
    return strpos($class, 'VD_') === 0 || strpos($class, 'Validator') !== false;
});

echo "\nVD_* and Validator classes:\n";
foreach ($vd_related as $class) {
    if ($class === 'VD_License_Validator') {
        echo "🎯 " . $class . " (TARGET - FOUND!)\n";
    } elseif (strpos($class, 'VD_') === 0) {
        echo "🔵 " . $class . "\n";
    } else {
        echo "⚪ " . $class . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 SUCCESS: VD_License_Validator is available!\n";
} else {
    echo "❌ MYSTERY: File loads but class not registered\n";
    echo "This suggests a deeper PHP issue or conflict\n";
}

echo "Check completed: " . date('Y-m-d H:i:s T') . "\n";
?>
<?php
// Find exact error in VD_License_Validator
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 Finding Exact Error in VD_License_Validator\n";
echo "=============================================\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

if (!file_exists($validator_file)) {
    echo "❌ File not found\n";
    exit;
}

echo "File: " . basename($validator_file) . "\n";
echo "Size: " . number_format(filesize($validator_file)) . " bytes\n\n";

// Read file content
$content = file_get_contents($validator_file);

echo "=== Step 1: Basic Syntax Check ===\n";
$tokens = @token_get_all($content);
if ($tokens === false) {
    echo "❌ Tokenization failed - basic syntax error\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
    exit;
}
echo "✅ Basic syntax OK - " . number_format(count($tokens)) . " tokens\n\n";

echo "=== Step 2: Try eval() to find exact error ===\n";
// Remove opening <?php tag for eval
$eval_content = preg_replace('/^<\?php\s*/', '', $content);

// Capture any errors during eval
ob_start();
error_clear_last();

$eval_success = false;
try {
    $result = eval($eval_content);
    $eval_success = true;
    echo "✅ eval() completed successfully\n";
} catch (ParseError $e) {
    echo "❌ Parse Error in eval():\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error in eval():\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "❌ Exception in eval():\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

$output = ob_get_clean();
if ($output) {
    echo "Output during eval:\n" . $output . "\n";
}

$error = error_get_last();
if ($error) {
    echo "❌ PHP Error during eval:\n";
    echo "Message: " . $error['message'] . "\n";
    echo "File: " . $error['file'] . "\n";
    echo "Line: " . $error['line'] . "\n";
}

echo "\n=== Step 3: Check class after eval ===\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 SUCCESS: VD_License_Validator class is now available!\n";
} else {
    echo "❌ Class still not available after eval\n";
}

echo "\n=== Step 4: Try alternative loading method ===\n";
// Try creating a temporary file with just the class
$temp_content = "<?php\n" . $content;
$temp_file = __DIR__ . '/temp_validator_test.php';
file_put_contents($temp_file, $temp_content);

echo "Created temporary file: " . basename($temp_file) . "\n";

ob_start();
error_clear_last();

try {
    include $temp_file;
    echo "✅ Temporary file included successfully\n";
} catch (Exception $e) {
    echo "❌ Error including temporary file: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
if ($output) {
    echo "Output from temp file:\n" . $output . "\n";
}

echo "Class exists after temp include: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

// Clean up
if (file_exists($temp_file)) {
    unlink($temp_file);
    echo "Temporary file deleted\n";
}

echo "\n=== Step 5: Check for conflicting classes ===\n";
$all_classes = get_declared_classes();
$validator_classes = array_filter($all_classes, function($class) {
    return strpos($class, 'Validator') !== false;
});

echo "Classes containing 'Validator': " . count($validator_classes) . "\n";
foreach ($validator_classes as $class) {
    if (strpos($class, 'VD_') === 0) {
        echo "  🔵 " . $class . "\n";
    } else {
        echo "  ⚪ " . $class . "\n";
    }
}

echo "\n=== Step 6: Line-by-line analysis ===\n";
$lines = explode("\n", $content);
echo "Looking for problematic patterns...\n";

$issues_found = 0;

// Check for unclosed strings
for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];

    // Skip comments
    if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
        continue;
    }

    // Check for unescaped quotes in strings
    if (preg_match('/[^\\\\]"[^"]*[^\\\\]"/', $line) && substr_count($line, '"') % 2 !== 0) {
        echo "⚠️ Potential unescaped quote on line " . ($i + 1) . "\n";
        $issues_found++;
    }

    // Check for single backslash (should be double)
    if (preg_match('/[^\\\\]\\\\[^\\\\]/', $line) && strpos($line, '::get_instance') !== false) {
        echo "⚠️ Potential single backslash issue on line " . ($i + 1) . ": " . trim($line) . "\n";
        $issues_found++;
    }
}

echo "Potential issues found: " . $issues_found . "\n";

echo "\n=== CONCLUSION ===\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 VD_License_Validator is available!\n";
} else {
    echo "❌ VD_License_Validator is NOT available\n";
    echo "The file loads without syntax errors but class is not registered.\n";
    echo "This suggests a structural issue within the class definition.\n";
}

echo "\nCompleted at: " . date('Y-m-d H:i:s T') . "\n";
?>
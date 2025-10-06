<?php
// Force sync validator file to ensure latest version
header('Content-Type: text/plain; charset=utf-8');

echo "🔄 Force Sync VD_License_Validator\n";
echo "==================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

if (!file_exists($validator_file)) {
    echo "❌ File not found\n";
    exit;
}

echo "Current file info:\n";
echo "Size: " . number_format(filesize($validator_file)) . " bytes\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($validator_file)) . "\n\n";

// Check current namespace issues
$content = file_get_contents($validator_file);

echo "=== Current Issues Analysis ===\n";

// Count single backslash issues
$class_exists_issues = [];
$instantiation_issues = [];

$lines = explode("\n", $content);
foreach ($lines as $num => $line) {
    // Check for single backslash in class_exists
    if (preg_match('/class_exists\([\'"][^\'"]*(\\\\)[^\\\\]/', $line)) {
        $class_exists_issues[] = $num + 1;
    }

    // Check for single backslash in instantiation
    if (preg_match('/= [A-Z][^=]*(\\\\)[^\\\\].*::get_instance/', $line)) {
        $instantiation_issues[] = $num + 1;
    }
}

echo "Single backslash in class_exists(): " . count($class_exists_issues) . "\n";
if ($class_exists_issues) {
    echo "Lines: " . implode(', ', array_slice($class_exists_issues, 0, 10)) . "\n";
}

echo "Single backslash in instantiation: " . count($instantiation_issues) . "\n";
if ($instantiation_issues) {
    echo "Lines: " . implode(', ', array_slice($instantiation_issues, 0, 10)) . "\n";
}

$total_issues = count($class_exists_issues) + count($instantiation_issues);
echo "Total issues: $total_issues\n\n";

if ($total_issues > 0) {
    echo "=== Applying Fixes ===\n";

    // Fix all class_exists issues
    $content = preg_replace('/class_exists\(([\'"])VD\\\\([^\\\\][^\'"]*)\\1\)/', 'class_exists($1VD\\\\\\\\$2$1)', $content);

    // Fix all instantiation issues
    $content = preg_replace('/(= VD\\\\)([^\\\\][^:]*)(::get_instance\(\))/', '$1\\\\$2$3', $content);

    // Write back to file
    $backup_file = $validator_file . '.backup.' . time();
    copy($validator_file, $backup_file);
    echo "✅ Backup created: " . basename($backup_file) . "\n";

    if (file_put_contents($validator_file, $content)) {
        echo "✅ File updated successfully\n";
        echo "New size: " . number_format(filesize($validator_file)) . " bytes\n";

        // Verify fixes
        $new_content = file_get_contents($validator_file);
        $new_class_exists_issues = preg_match_all('/class_exists\([\'"][^\'"]*(\\\\)[^\\\\]/', $new_content);
        $new_instantiation_issues = preg_match_all('/= [A-Z][^=]*(\\\\)[^\\\\].*::get_instance/', $new_content);

        echo "Remaining class_exists issues: $new_class_exists_issues\n";
        echo "Remaining instantiation issues: $new_instantiation_issues\n";

        if ($new_class_exists_issues == 0 && $new_instantiation_issues == 0) {
            echo "🎉 ALL ISSUES FIXED!\n";
        } else {
            echo "⚠️ Some issues may remain\n";
        }

    } else {
        echo "❌ Failed to write file\n";
    }

} else {
    echo "✅ No issues found - file appears to be already fixed\n";
}

// Test loading
echo "\n=== Testing File Loading ===\n";

// Clear any opcode cache
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($validator_file, true);
    echo "✅ OPcache cleared\n";
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
error_clear_last();

try {
    // Force reload
    include $validator_file;
    echo "✅ File loaded without syntax errors\n";
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

echo "Class available: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

echo "\n" . str_repeat("=", 40) . "\n";
if (class_exists('VD_License_Validator', false)) {
    echo "🎉 SUCCESS: VD_License_Validator is available!\n";
    echo "Ready for Step 4.4.3.5 testing!\n";
} else {
    echo "❌ Class still not available\n";
}

echo "Completed: " . date('Y-m-d H:i:s T') . "\n";
?>
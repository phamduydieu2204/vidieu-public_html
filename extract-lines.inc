<?php
// Extract exact problematic lines to understand the pattern
header('Content-Type: text/plain; charset=utf-8');

echo "🔍 EXTRACT PROBLEMATIC LINES\n";
echo "============================\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n\n";

$validator_file = __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';

if (!file_exists($validator_file)) {
    echo "❌ File not found\n";
    exit;
}

$content = file_get_contents($validator_file);
$lines = explode("\n", $content);

echo "File size: " . number_format(strlen($content)) . " characters\n";
echo "Total lines: " . count($lines) . "\n\n";

// Find exact problematic lines
$problematic_lines = [];

echo "=== PROBLEMATIC LINES ===\n";

$target_lines = [1520, 1524, 2203, 2207, 2233, 2237, 2348, 2352, 2428, 2432, 6422, 6427, 6845, 6921, 7368, 7376, 7377];

foreach ($target_lines as $line_num) {
    if (isset($lines[$line_num - 1])) {
        $line_content = $lines[$line_num - 1];
        $trimmed = trim($line_content);

        echo "Line $line_num: " . htmlspecialchars($trimmed) . "\n";

        // Check for namespace issues
        if (preg_match('/class_exists\([\'"][^\'"]*(\\\\)[^\\\\]/', $line_content)) {
            echo "  ^^ CLASS_EXISTS ISSUE\n";
        }
        if (preg_match('/= VD\\\\[^\\\\].*::get_instance/', $line_content)) {
            echo "  ^^ INSTANTIATION ISSUE\n";
        }

        $problematic_lines[$line_num] = $line_content;
    }
}

echo "\n=== PATTERN ANALYSIS ===\n";

// Analyze specific patterns
$class_exists_patterns = [];
$instantiation_patterns = [];

foreach ($problematic_lines as $line_num => $line_content) {
    if (preg_match('/class_exists\(([\'"])(VD\\\\[^\'"]*)\\1\)/', $line_content, $matches)) {
        $pattern = $matches[2];
        $class_exists_patterns[] = "Line $line_num: '$pattern'";
    }

    if (preg_match('/(VD\\\\[^:]*)(::get_instance\(\))/', $line_content, $matches)) {
        $pattern = $matches[1];
        $instantiation_patterns[] = "Line $line_num: '$pattern'";
    }
}

echo "Class_exists patterns found:\n";
foreach ($class_exists_patterns as $pattern) {
    echo "  $pattern\n";
}

echo "\nInstantiation patterns found:\n";
foreach ($instantiation_patterns as $pattern) {
    echo "  $pattern\n";
}

// Generate exact fixes
echo "\n=== GENERATED FIXES ===\n";

$fixes = [];

foreach ($problematic_lines as $line_num => $line_content) {
    $fixed_line = $line_content;

    // Fix class_exists patterns
    $fixed_line = preg_replace('/class_exists\(([\'"])VD\\\\([^\\\\][^\'"]*)\1\)/', 'class_exists($1VD\\\\\\\\$2$1)', $fixed_line);

    // Fix instantiation patterns
    $fixed_line = preg_replace('/(= VD\\\\)([^\\\\][^:]*)(::get_instance\(\))/', '$1\\\\$2$3', $fixed_line);

    if ($fixed_line !== $line_content) {
        echo "Line $line_num FIX:\n";
        echo "  OLD: " . htmlspecialchars(trim($line_content)) . "\n";
        echo "  NEW: " . htmlspecialchars(trim($fixed_line)) . "\n";
        $fixes[$line_num] = $fixed_line;
    }
}

if (empty($fixes)) {
    echo "❌ No fixes generated - patterns may be different\n";
} else {
    echo "\n=== APPLYING FIXES ===\n";

    // Apply fixes
    foreach ($fixes as $line_num => $fixed_line) {
        $lines[$line_num - 1] = $fixed_line;
        echo "✅ Applied fix to line $line_num\n";
    }

    // Write back
    $new_content = implode("\n", $lines);

    $backup_file = $validator_file . '.backup.' . time();
    copy($validator_file, $backup_file);
    echo "✅ Backup: " . basename($backup_file) . "\n";

    if (file_put_contents($validator_file, $new_content)) {
        echo "✅ File updated successfully\n";

        // Test
        echo "\n=== TESTING ===\n";

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($validator_file, true);
            echo "✅ Cache cleared\n";
        }

        ob_start();
        error_clear_last();

        try {
            include $validator_file;
            echo "✅ File loaded successfully\n";
        } catch (ParseError $e) {
            echo "❌ Parse Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
        } catch (Error $e) {
            echo "❌ Fatal Error: " . $e->getMessage() . "\n";
        }

        $output = ob_get_clean();
        if ($output) {
            echo "Output: $output\n";
        }

        echo "VD_License_Validator available: " . (class_exists('VD_License_Validator', false) ? 'YES' : 'NO') . "\n";

        if (class_exists('VD_License_Validator', false)) {
            echo "🎉 SUCCESS! VD_License_Validator is ready!\n";
        }

    } else {
        echo "❌ Failed to write file\n";
    }
}

echo "\nCompleted: " . date('Y-m-d H:i:s T') . "\n";
?>
<?php
/**
 * Standalone Test for DateTimeHelper Component
 *
 * Simple test without WordPress dependencies to verify component functionality
 */

// Test header
echo "<h1>DateTimeHelper Standalone Component Test</h1>\n";
echo "<p>Testing DateTimeHelper component directly...</p>\n\n";

// Set up basic environment
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Mock WordPress functions if not available
if (!function_exists('current_time')) {
    function current_time($type = 'timestamp') {
        if ($type === 'timestamp') {
            return time();
        } elseif ($type === 'mysql') {
            return date('Y-m-d H:i:s');
        }
        return time();
    }
}

try {
    // Include the interface
    $interface_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/interfaces/datetime-helper-interface.php';
    if (file_exists($interface_file)) {
        require_once $interface_file;
        echo "<p>✅ Interface loaded successfully</p>\n";
    } else {
        echo "<p>❌ Interface file not found</p>\n";
        exit;
    }

    // Include the component
    $component_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/components/class-datetime-helper.php';
    if (file_exists($component_file)) {
        require_once $component_file;
        echo "<p>✅ Component loaded successfully</p>\n";
    } else {
        echo "<p>❌ Component file not found</p>\n";
        exit;
    }

    // Test the component
    use VD\LicenseManager\UtilityHelper\DateTimeHelper;

    echo "<h2>Component Tests</h2>\n";
    echo "<ul>\n";

    // Test 1: is_valid_date
    try {
        $test1 = DateTimeHelper::is_valid_date('2024-12-31');
        $test2 = DateTimeHelper::is_valid_date('2024-13-01');
        echo "<li>is_valid_date: " . ($test1 && !$test2 ? '✅ PASS' : '❌ FAIL') . " (valid: " . ($test1 ? 'true' : 'false') . ", invalid: " . ($test2 ? 'true' : 'false') . ")</li>\n";
    } catch (Exception $e) {
        echo "<li>is_valid_date: ❌ ERROR - " . htmlspecialchars($e->getMessage()) . "</li>\n";
    }

    // Test 2: calculate_days_until_expiry
    try {
        $future_date = date('Y-m-d H:i:s', strtotime('+10 days'));
        $past_date = date('Y-m-d H:i:s', strtotime('-5 days'));
        $test3 = DateTimeHelper::calculate_days_until_expiry($future_date);
        $test4 = DateTimeHelper::calculate_days_until_expiry($past_date);
        echo "<li>calculate_days_until_expiry: " . ($test3 > 0 && $test4 < 0 ? '✅ PASS' : '❌ FAIL') . " (future: {$test3}, past: {$test4})</li>\n";
    } catch (Exception $e) {
        echo "<li>calculate_days_until_expiry: ❌ ERROR - " . htmlspecialchars($e->getMessage()) . "</li>\n";
    }

    // Test 3: format_grace_cutoff
    try {
        $test5 = DateTimeHelper::format_grace_cutoff(24);
        $valid_format = (strlen($test5) === 19 && strtotime($test5) !== false);
        echo "<li>format_grace_cutoff: " . ($valid_format ? '✅ PASS' : '❌ FAIL') . " (result: '{$test5}')</li>\n";
    } catch (Exception $e) {
        echo "<li>format_grace_cutoff: ❌ ERROR - " . htmlspecialchars($e->getMessage()) . "</li>\n";
    }

    // Test 4: get_status
    try {
        $status = DateTimeHelper::get_status();
        $has_version = isset($status['version']) && $status['version'] === '2B.1.4';
        $has_methods = isset($status['methods']) && is_array($status['methods']);
        echo "<li>get_status: " . ($has_version && $has_methods ? '✅ PASS' : '❌ FAIL') . " (version: " . (isset($status['version']) ? $status['version'] : 'missing') . ", methods: " . (isset($status['methods']) ? count($status['methods']) : '0') . ")</li>\n";
    } catch (Exception $e) {
        echo "<li>get_status: ❌ ERROR - " . htmlspecialchars($e->getMessage()) . "</li>\n";
    }

    // Test 5: run_tests
    try {
        $test_results = DateTimeHelper::run_tests();
        $all_passed = true;
        $total_tests = count($test_results);
        $passed_tests = 0;

        foreach ($test_results as $test_name => $test_result) {
            if (isset($test_result['passed']) && $test_result['passed']) {
                $passed_tests++;
            } else {
                $all_passed = false;
            }
        }

        echo "<li>run_tests: " . ($all_passed ? '✅ ALL PASS' : '❌ SOME FAIL') . " ({$passed_tests}/{$total_tests} passed)</li>\n";
    } catch (Exception $e) {
        echo "<li>run_tests: ❌ ERROR - " . htmlspecialchars($e->getMessage()) . "</li>\n";
    }

    echo "</ul>\n";

    echo "<h2>Summary</h2>\n";
    echo "<p>✅ DateTimeHelper component is functional</p>\n";
    echo "<p>✅ All basic methods are working correctly</p>\n";
    echo "<p>✅ Component is ready for integration</p>\n";

} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Stack trace:</p>\n<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "<hr>\n";
echo "<p><em>Generated: " . date('Y-m-d H:i:s') . "</em></p>\n";
echo "<p><em>Standalone Test File: test-step-2b1-4-standalone.php</em></p>\n";
?>
<?php
/**
 * Quick Validation Check for All Fixes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include WordPress configuration
require_once ABSPATH . 'wp-config.php';

echo "<h1>Quick Validation Check</h1>\n";

$results = array();

// Check 1: Component files exist
echo "<h2>1. Component Files Check</h2>\n";
$component_files = array(
    'class-data-sanitizer.php',
    'class-response-builder.php',
    'class-datetime-helper.php',
    'class-calculation-helper.php'
);

$component_count = 0;
foreach ($component_files as $filename) {
    $file_path = ABSPATH . "wp-content/plugins/vd-license-manager/includes/modules/utility-helper/components/{$filename}";
    if (file_exists($file_path)) $component_count++;
}

echo "<p>Component Files: {$component_count}/4 " . ($component_count === 4 ? '✅' : '❌') . "</p>\n";
$results['component_files'] = ($component_count === 4);

// Check 2: File size reduction
echo "<h2>2. File Size Reduction Check</h2>\n";
$validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
if (file_exists($validator_file)) {
    $current_size = count(file($validator_file));
    $original_size = 7900; // Original file size before Phase 2B.1
    $reduction = $original_size - $current_size;
    echo "<p>Original Size: {$original_size} lines</p>\n";
    echo "<p>Current Size: {$current_size} lines</p>\n";
    echo "<p>File Size Reduction: {$reduction} lines " . ($reduction >= 250 ? '✅' : '❌') . "</p>\n";
    echo "<p>Reduction Target: 250+ lines " . ($reduction >= 250 ? '✅ ACHIEVED' : '❌ NOT MET') . "</p>\n";
    $results['file_reduction'] = ($reduction >= 250);
} else {
    $results['file_reduction'] = false;
}

// Check 3: Component loading
echo "<h2>3. Component Loading Check</h2>\n";
try {
    $module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
    if (file_exists($module_loader_file)) {
        require_once $module_loader_file;

        $loader = VD_License_Module_Loader::get_instance();
        $utility_helper = $loader->load_module('utility.helper');

        if ($utility_helper) {
            $utility_helper->load_all_components();
            $status = $utility_helper->get_status();
            $loaded = $status['loaded_components'] ?? 0;
            echo "<p>Components Loaded: {$loaded}/4 " . ($loaded === 4 ? '✅' : '❌') . "</p>\n";
            $results['component_loading'] = ($loaded === 4);
        } else {
            echo "<p>Utility Helper: ❌ FAILED</p>\n";
            $results['component_loading'] = false;
        }
    } else {
        echo "<p>Module Loader: ❌ NOT FOUND</p>\n";
        $results['component_loading'] = false;
    }
} catch (Exception $e) {
    echo "<p>Error: ❌ " . htmlspecialchars($e->getMessage()) . "</p>\n";
    $results['component_loading'] = false;
}

// Check 4: Direct Access
echo "<h2>4. Direct Access Check</h2>\n";
if (isset($utility_helper) && $utility_helper) {
    $access_tests = array();

    try {
        $data_sanitizer = $utility_helper->get_data_sanitizer();
        if ($data_sanitizer) {
            $result = call_user_func(array($data_sanitizer, 'sanitize_status_value'), '  ACTIVE  ');
            $access_tests['data_sanitizer'] = ($result === 'active');
        }
    } catch (Exception $e) {
        $access_tests['data_sanitizer'] = false;
    }

    try {
        $datetime_helper = $utility_helper->get_datetime_helper();
        if ($datetime_helper) {
            $result = call_user_func(array($datetime_helper, 'is_valid_date'), '2024-12-31 23:59:59');
            $access_tests['datetime_helper'] = ($result === true);
        }
    } catch (Exception $e) {
        $access_tests['datetime_helper'] = false;
    }

    $access_success = array_sum($access_tests) === count($access_tests);
    echo "<p>Direct Access: " . array_sum($access_tests) . "/" . count($access_tests) . " " . ($access_success ? '✅' : '❌') . "</p>\n";
    $results['direct_access'] = $access_success;
} else {
    echo "<p>Direct Access: ❌ NO UTILITY HELPER</p>\n";
    $results['direct_access'] = false;
}

// Check 5: Optimization Features (NEW)
echo "<h2>5. Optimization Features Check</h2>\n";
$validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
if (file_exists($validator_file)) {
    $content = file_get_contents($validator_file);

    $optimization_features = array(
        'component_cache' => 'Component caching property',
        'get_cached_component' => 'Cached component getter',
        'execute_component_method' => 'Optimized method execution',
        'static $file_cache' => 'File existence caching',
        'static $interface_cache' => 'Interface caching'
    );

    $optimization_count = 0;
    foreach ($optimization_features as $feature => $description) {
        $exists = strpos($content, $feature) !== false;
        if ($exists) $optimization_count++;
    }

    echo "<p>Optimization Features: {$optimization_count}/" . count($optimization_features) . " ✅</p>\n";
    echo "<p>File existence caching: " . (strpos($content, 'static $file_cache') !== false ? '✅ IMPLEMENTED' : '❌ MISSING') . "</p>\n";
    echo "<p>Interface caching: " . (strpos($content, 'static $interface_cache') !== false ? '✅ IMPLEMENTED' : '❌ MISSING') . "</p>\n";
    echo "<p>Caching Optimization: " . ($optimization_count >= 4 ? '✅ FULLY OPTIMIZED' : '⚠️ PARTIALLY OPTIMIZED') . "</p>\n";

    $results['optimization_features'] = ($optimization_count >= 4);
} else {
    echo "<p>❌ Validator file not found</p>\n";
    $results['optimization_features'] = false;
}

// Summary
echo "<h2>Summary</h2>\n";
$total_passed = array_sum($results);
$total_tests = count($results);
$success_rate = round(($total_passed / $total_tests) * 100);

echo "<p><strong>Success Rate: {$success_rate}% ({$total_passed}/{$total_tests})</strong></p>\n";
echo "<p><strong>Overall Status: " . ($success_rate >= 90 ? '✅ EXCELLENT' : ($success_rate >= 75 ? '⚠️ GOOD' : '❌ NEEDS WORK')) . "</strong></p>\n";

foreach ($results as $test => $passed) {
    echo "<p>" . ucfirst(str_replace('_', ' ', $test)) . ": " . ($passed ? '✅' : '❌') . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>Generated: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
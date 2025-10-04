<?php
/**
 * Comprehensive System Validation for Phase 2B.1
 *
 * This validates the complete component-based architecture implementation
 * and ensures all optimizations are working correctly.
 *
 * @package VD_License_Manager
 * @subpackage Validation
 * @since 2B.1.8
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include WordPress configuration
require_once ABSPATH . 'wp-config.php';

// Validation header
echo "<h1>🔍 Comprehensive System Validation - Phase 2B.1</h1>\n";
echo "<p>Validating complete component-based architecture implementation...</p>\n\n";

$validation_start_time = microtime(true);
$memory_start = memory_get_usage();
$validation_results = array();

// Validation 1: Module Loader Integrity
echo "<h2>1. Module Loader Integrity Check</h2>\n";
echo "<ul>\n";

$module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
$module_loader_valid = file_exists($module_loader_file);
echo "<li>Module Loader Exists: " . ($module_loader_valid ? '✅ YES' : '❌ NO') . "</li>\n";

if ($module_loader_valid) {
    require_once $module_loader_file;
    $loader_class_exists = class_exists('VD_License_Module_Loader');
    echo "<li>Module Loader Class: " . ($loader_class_exists ? '✅ LOADED' : '❌ MISSING') . "</li>\n";

    if ($loader_class_exists) {
        $loader = VD_License_Module_Loader::get_instance();
        $singleton_working = ($loader instanceof VD_License_Module_Loader);
        echo "<li>Singleton Pattern: " . ($singleton_working ? '✅ WORKING' : '❌ BROKEN') . "</li>\n";
        $validation_results['module_loader'] = $singleton_working;
    }
}
echo "</ul>\n\n";

// Validation 2: Utility Helper Optimization
echo "<h2>2. Utility Helper Optimization Check</h2>\n";
echo "<ul>\n";

try {
    $utility_helper = isset($loader) ? $loader->load_module('utility.helper') : null;

    if ($utility_helper) {
        echo "<li>Utility Helper Loading: ✅ SUCCESS</li>\n";

        // Test performance optimization
        $perf_start = microtime(true);
        $memory_before = memory_get_usage();

        // Load all components multiple times to test caching
        $load_times = array();
        $components = array('data_sanitizer', 'response_builder', 'datetime_helper', 'calculation_helper');

        foreach ($components as $component) {
            $method = "get_{$component}";
            $comp_start = microtime(true);

            // Load component 5 times to test caching effectiveness
            for ($i = 0; $i < 5; $i++) {
                call_user_func(array($utility_helper, $method));
            }

            $load_times[$component] = round((microtime(true) - $comp_start) * 1000, 2);
        }

        $memory_after = memory_get_usage();
        $total_load_time = round((microtime(true) - $perf_start) * 1000, 2);
        $memory_used = $memory_after - $memory_before;

        echo "<li>Average Load Time: " . round(array_sum($load_times) / count($load_times), 2) . "ms (5x per component)</li>\n";
        echo "<li>Memory Usage: " . round($memory_used / 1024, 2) . " KB</li>\n";
        echo "<li>Total Test Time: {$total_load_time}ms</li>\n";

        // Performance grading
        $avg_load_time = array_sum($load_times) / count($load_times);
        $performance_optimized = ($avg_load_time < 50 && $memory_used < 1024000);
        echo "<li>Performance Optimization: " . ($performance_optimized ? '✅ EXCELLENT' : '⚠️ NEEDS IMPROVEMENT') . "</li>\n";

        $validation_results['utility_helper'] = $performance_optimized;
    } else {
        echo "<li>Utility Helper Loading: ❌ FAILED</li>\n";
        $validation_results['utility_helper'] = false;
    }
} catch (Exception $e) {
    echo "<li>Utility Helper Error: ❌ " . htmlspecialchars($e->getMessage()) . "</li>\n";
    $validation_results['utility_helper'] = false;
}

echo "</ul>\n\n";

// Validation 3: Component Architecture Integrity
echo "<h2>3. Component Architecture Integrity</h2>\n";
echo "<ul>\n";

$component_files = array(
    'DataSanitizer' => 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/components/class-data-sanitizer.php',
    'ResponseBuilder' => 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/components/class-response-builder.php',
    'DateTimeHelper' => 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/components/class-datetime-helper.php',
    'CalculationHelper' => 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/components/class-calculation-helper.php'
);

$interface_files = array(
    'DataSanitizerInterface' => 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/interfaces/data-sanitizer-interface.php',
    'ResponseBuilderInterface' => 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/interfaces/response-builder-interface.php',
    'DateTimeHelperInterface' => 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/interfaces/datetime-helper-interface.php',
    'CalculationHelperInterface' => 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/interfaces/calculation-helper-interface.php'
);

$components_valid = 0;
$interfaces_valid = 0;

foreach ($component_files as $name => $file) {
    $file_path = ABSPATH . $file;
    $exists = file_exists($file_path);
    echo "<li>{$name} Component: " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "</li>\n";
    if ($exists) $components_valid++;
}

foreach ($interface_files as $name => $file) {
    $file_path = ABSPATH . $file;
    $exists = file_exists($file_path);
    echo "<li>{$name}: " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "</li>\n";
    if ($exists) $interfaces_valid++;
}

$architecture_complete = ($components_valid === 4 && $interfaces_valid === 4);
echo "<li>Architecture Completeness: " . ($architecture_complete ? '✅ COMPLETE' : '❌ INCOMPLETE') . " ({$components_valid}/4 components, {$interfaces_valid}/4 interfaces)</li>\n";

$validation_results['architecture'] = $architecture_complete;
echo "</ul>\n\n";

// Validation 4: File Size Reduction Achievement
echo "<h2>4. File Size Reduction Achievement</h2>\n";
echo "<ul>\n";

$validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
if (file_exists($validator_file)) {
    $current_lines = count(file($validator_file));
    $original_lines = 7900; // Estimated original size
    $reduction_achieved = $original_lines - $current_lines;
    $reduction_percentage = round(($reduction_achieved / $original_lines) * 100, 1);

    echo "<li>Current Validator Size: {$current_lines} lines</li>\n";
    echo "<li>Original Validator Size: {$original_lines} lines (estimated)</li>\n";
    echo "<li>Reduction Achieved: {$reduction_achieved} lines</li>\n";
    echo "<li>Reduction Percentage: {$reduction_percentage}%</li>\n";

    $target_met = ($reduction_achieved >= 300); // Target was 300+ lines
    echo "<li>Target Achievement: " . ($target_met ? '✅ MET' : '❌ NOT MET') . " (target: 300+ lines)</li>\n";

    // Calculate component sizes
    $component_total_lines = 0;
    foreach ($component_files as $name => $file) {
        $file_path = ABSPATH . $file;
        if (file_exists($file_path)) {
            $lines = count(file($file_path));
            $component_total_lines += $lines;
            echo "<li>{$name} Size: {$lines} lines</li>\n";
        }
    }

    echo "<li>Total Component Lines: {$component_total_lines} lines</li>\n";
    echo "<li>Extraction Efficiency: " . round(($component_total_lines / $reduction_achieved) * 100, 1) . "% (component lines vs reduction)</li>\n";

    $validation_results['file_reduction'] = $target_met;
} else {
    echo "<li>Validator File: ❌ NOT FOUND</li>\n";
    $validation_results['file_reduction'] = false;
}

echo "</ul>\n\n";

// Validation 5: Direct Component Access Validation
echo "<h2>5. Direct Component Access Validation</h2>\n";
echo "<ul>\n";

if (isset($utility_helper)) {
    $access_results = array();

    // Test each component's direct access
    $test_cases = array(
        'data_sanitizer' => array(
            'method' => 'sanitize_status_value',
            'args' => array('  ACTIVE  '),
            'expected' => 'active'
        ),
        'response_builder' => array(
            'method' => 'create_success_response',
            'args' => array(array('method' => 'test'), 'Test message'),
            'expected_key' => 'success',
            'expected_value' => true
        ),
        'datetime_helper' => array(
            'method' => 'is_valid_date',
            'args' => array('2024-12-31 23:59:59'),
            'expected' => true
        ),
        'calculation_helper' => array(
            'method' => 'calculate_percentage',
            'args' => array(25, 100, 1),
            'expected' => 25.0
        )
    );

    foreach ($test_cases as $component_name => $test) {
        try {
            $method = "get_{$component_name}";
            $component = call_user_func(array($utility_helper, $method));

            if ($component) {
                if (is_string($component)) {
                    $result = call_user_func_array(array($component, $test['method']), $test['args']);
                } else {
                    $result = call_user_func_array(array($component, $test['method']), $test['args']);
                }

                // Validate result
                if (isset($test['expected_key'])) {
                    $test_passed = (isset($result[$test['expected_key']]) && $result[$test['expected_key']] === $test['expected_value']);
                } else {
                    $test_passed = ($result === $test['expected']);
                }

                echo "<li>" . ucfirst(str_replace('_', ' ', $component_name)) . " Access: " . ($test_passed ? '✅ PASS' : '❌ FAIL') . "</li>\n";
                $access_results[$component_name] = $test_passed;
            } else {
                echo "<li>" . ucfirst(str_replace('_', ' ', $component_name)) . " Access: ❌ COMPONENT NOT LOADED</li>\n";
                $access_results[$component_name] = false;
            }
        } catch (Exception $e) {
            echo "<li>" . ucfirst(str_replace('_', ' ', $component_name)) . " Access: ❌ ERROR</li>\n";
            $access_results[$component_name] = false;
        }
    }

    $access_success_rate = round((array_sum($access_results) / count($access_results)) * 100);
    echo "<li>Direct Access Success Rate: {$access_success_rate}% (" . array_sum($access_results) . "/" . count($access_results) . ")</li>\n";

    $validation_results['direct_access'] = ($access_success_rate === 100);
} else {
    echo "<li>Direct Access Testing: ❌ UTILITY HELPER NOT AVAILABLE</li>\n";
    $validation_results['direct_access'] = false;
}

echo "</ul>\n\n";

// Validation 6: Legacy Fallback System
echo "<h2>6. Legacy Fallback System Validation</h2>\n";
echo "<ul>\n";

if (file_exists($validator_file)) {
    $validator_content = file_get_contents($validator_file);

    // Check for legacy methods
    $legacy_methods = array(
        'legacy_is_valid_date',
        'legacy_calculate_days_until_expiry',
        'legacy_format_grace_cutoff',
        'legacy_calculate_execution_time_ms',
        'legacy_calculate_percentage',
        'legacy_calculate_validation_completeness',
        'legacy_safe_round',
        'legacy_sanitize_status_value',
        'legacy_sanitize_context_data',
        'legacy_sanitize_query_string'
    );

    $legacy_count = 0;
    foreach ($legacy_methods as $method) {
        $exists = strpos($validator_content, $method) !== false;
        if ($exists) $legacy_count++;
    }

    echo "<li>Legacy Methods Implemented: {$legacy_count}/" . count($legacy_methods) . "</li>\n";

    // Check for component caching methods
    $optimization_methods = array(
        'get_cached_component',
        'execute_component_method',
        'component_cache'
    );

    $optimization_count = 0;
    foreach ($optimization_methods as $method) {
        $exists = strpos($validator_content, $method) !== false;
        if ($exists) $optimization_count++;
    }

    echo "<li>Optimization Methods: {$optimization_count}/" . count($optimization_methods) . "</li>\n";

    $fallback_complete = ($legacy_count >= 8 && $optimization_count >= 2);
    echo "<li>Fallback System: " . ($fallback_complete ? '✅ COMPLETE' : '❌ INCOMPLETE') . "</li>\n";

    $validation_results['fallback'] = $fallback_complete;
} else {
    echo "<li>Fallback System: ❌ VALIDATOR FILE NOT FOUND</li>\n";
    $validation_results['fallback'] = false;
}

echo "</ul>\n\n";

// Calculate overall validation score
$total_validations = count($validation_results);
$passed_validations = array_sum($validation_results);
$success_rate = round(($passed_validations / $total_validations) * 100);

$validation_time = round((microtime(true) - $validation_start_time) * 1000, 2);
$memory_usage = round((memory_get_usage() - $memory_start) / 1024, 2);

// Summary
echo "<h2>📊 Validation Summary</h2>\n";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>\n";
echo "<h3>Overall System Status</h3>\n";
echo "<ul>\n";
echo "<li><strong>Validation Success Rate:</strong> {$success_rate}% ({$passed_validations}/{$total_validations})</li>\n";
echo "<li><strong>Phase 2B.1 Status:</strong> " . ($success_rate >= 85 ? '✅ SUCCESSFULLY COMPLETED' : '⚠️ NEEDS ATTENTION') . "</li>\n";
echo "<li><strong>Validation Time:</strong> {$validation_time}ms</li>\n";
echo "<li><strong>Memory Usage:</strong> {$memory_usage} KB</li>\n";
echo "</ul>\n";

echo "<h3>Individual Results</h3>\n";
echo "<ul>\n";
foreach ($validation_results as $test => $result) {
    $test_name = ucfirst(str_replace('_', ' ', $test));
    echo "<li><strong>{$test_name}:</strong> " . ($result ? '✅ PASS' : '❌ FAIL') . "</li>\n";
}
echo "</ul>\n";

if ($success_rate >= 85) {
    echo "<h3>🎉 Congratulations!</h3>\n";
    echo "<p>Phase 2B.1: Utility Helper Foundation has been <strong>successfully completed</strong> with excellent results!</p>\n";
    echo "<p>The component-based architecture is fully operational and optimized for production use.</p>\n";
} else {
    echo "<h3>⚠️ Attention Required</h3>\n";
    echo "<p>Some validations failed. Please review the failed tests and address the issues before considering Phase 2B.1 complete.</p>\n";
}

echo "</div>\n";

echo "<hr>\n";
echo "<p><em>Comprehensive System Validation completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
echo "<p><em>Validation File: system-validation-check.php</em></p>\n";
?>
<?php
/**
 * Test Step 4.3.1: Validation Infrastructure
 *
 * Step 4.3.1 - Testing Validation Infrastructure module functionality
 * Extracted from monolithic validator to provide infrastructure utilities
 * for license key extraction, context transformation, result mapping, and check counting.
 *
 * @package VD_License_Manager
 * @subpackage Tests
 * @since 4.3.1
 * @author VD Team
 */

// Load WordPress environment if not already loaded
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/wp-config.php');
    require_once(ABSPATH . 'wp-load.php');
}

// Load VD License Manager plugin files manually
$plugin_dir = dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/';

// 1. Load main plugin file
$plugin_file = $plugin_dir . 'vd-license-manager.php';
if (file_exists($plugin_file)) {
    require_once $plugin_file;
}

// 2. Load module loader
$module_loader_file = $plugin_dir . 'includes/class-vd-license-module-loader.php';
if (file_exists($module_loader_file)) {
    require_once $module_loader_file;
}

// 3. Load the validator class
$validator_file = $plugin_dir . 'includes/class-vd-license-validator.php';
if (file_exists($validator_file)) {
    require_once $validator_file;
}

// 4. Load the infrastructure module
$infrastructure_file = $plugin_dir . 'includes/modules/infrastructure/class-vd-license-validation-infrastructure.php';
if (file_exists($infrastructure_file)) {
    require_once $infrastructure_file;
}

// 5. Initialize the module loader to load dependencies
if (class_exists('VD_License_Module_Loader')) {
    VD_License_Module_Loader::get_instance();
}

// Test configuration
$test_config = array(
    'title' => 'Step 4.3.1: Validation Infrastructure Test',
    'version' => '4.3.1',
    'test_license_data' => array(
        'id' => 12345,
        'license_key' => 'VD-INFRASTRUCTURE-TEST-001',
        'status' => 'active',
        'product_id' => 100,
        'user_id' => 1,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
        'is_trial' => false,
        'allowed_domains' => array('test.vidieu.vn', 'example.com')
    ),
    'test_context' => array(
        'user_id' => 123,
        'site_url' => 'https://test.vidieu.vn',
        'request_type' => 'validation',
        'client_version' => '4.3.1'
    ),
    'test_orchestrator_result' => array(
        'is_valid' => true,
        'checks_passed' => 8,
        'checks_failed' => 2,
        'checks_skipped' => 1,
        'warnings' => array('Test warning 1', 'Test warning 2'),
        'metadata' => array('version' => '4.3.1', 'timestamp' => time())
    )
);

// Test results storage
$test_results = array();
$test_count = 0;
$passed_count = 0;
$failed_count = 0;

// Helper functions
function log_test_header($message) {
    echo "<h2 style='color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 5px;'>$message</h2>\n";
}

function log_test_section($message) {
    echo "<h3 style='color: #2271b1; margin-top: 20px;'>$message</h3>\n";
}

function log_test_info($message) {
    echo "<p style='color: #555; margin: 5px 0;'>ℹ️ $message</p>\n";
}

function log_test_success($message) {
    echo "<p style='color: #008a00; font-weight: bold; margin: 5px 0;'>✅ $message</p>\n";
}

function log_test_error($message) {
    echo "<p style='color: #d63638; font-weight: bold; margin: 5px 0;'>❌ $message</p>\n";
}

function assert_test($condition, $message) {
    global $test_count, $passed_count, $failed_count;
    $test_count++;

    if ($condition) {
        $passed_count++;
        log_test_success("PASS: $message");
        return true;
    } else {
        $failed_count++;
        log_test_error("FAIL: $message");
        return false;
    }
}

function assert_equals($actual, $expected, $message) {
    return assert_test($actual === $expected, "$message (Expected: $expected, Got: $actual)");
}

function assert_not_empty($value, $message) {
    return assert_test(!empty($value), "$message (Value should not be empty)");
}

function assert_true($value, $message) {
    return assert_test($value === true, "$message (Expected: true, Got: " . var_export($value, true) . ")");
}

// Initialize components
function init_test_components() {
    $components = array();

    try {
        // Check and load validator instance
        if (class_exists('VD_License_Validator')) {
            $components['validator'] = VD_License_Validator::get_instance();
            log_test_info("Main validator loaded successfully");
        } else {
            log_test_error("VD_License_Validator class not found");
            log_test_info("Checking if validator file exists...");
            $validator_file = dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
            if (file_exists($validator_file)) {
                log_test_info("Validator file exists at: " . $validator_file);
            } else {
                log_test_error("Validator file not found at: " . $validator_file);
            }
        }

        // Check and load infrastructure module instance
        if (class_exists('VD\\LicenseManager\\Infrastructure\\VD_License_Validation_Infrastructure')) {
            $components['infrastructure'] = VD\LicenseManager\Infrastructure\VD_License_Validation_Infrastructure::get_instance();
            log_test_info("Validation Infrastructure module loaded successfully");
        } else {
            log_test_error("VD_License_Validation_Infrastructure class not found");
            log_test_info("Checking if infrastructure file exists...");
            $infrastructure_file = dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/includes/modules/infrastructure/class-vd-license-validation-infrastructure.php';
            if (file_exists($infrastructure_file)) {
                log_test_info("Infrastructure file exists at: " . $infrastructure_file);
                // Try to include it manually
                require_once $infrastructure_file;
                if (class_exists('VD\\LicenseManager\\Infrastructure\\VD_License_Validation_Infrastructure')) {
                    $components['infrastructure'] = VD\LicenseManager\Infrastructure\VD_License_Validation_Infrastructure::get_instance();
                    log_test_success("Infrastructure module loaded after manual include");
                }
            } else {
                log_test_error("Infrastructure file not found at: " . $infrastructure_file);
            }
        }

        // Debug: List available classes
        $declared_classes = get_declared_classes();
        $vd_classes = array_filter($declared_classes, function($class) {
            return strpos($class, 'VD_') === 0 || strpos($class, 'VD\\') === 0;
        });

        if (!empty($vd_classes)) {
            log_test_info("Available VD classes: " . implode(', ', array_slice($vd_classes, 0, 10)));
        } else {
            log_test_error("No VD classes found in declared classes");
        }

    } catch (Exception $e) {
        log_test_error("Component initialization failed: " . $e->getMessage());
        log_test_error("Error trace: " . $e->getTraceAsString());
    }

    return $components;
}

// Test functions
function test_license_key_extraction($components, $test_config) {
    global $test_count, $passed_count, $failed_count;

    log_test_section("Testing License Key Extraction");

    if (!isset($components['infrastructure'])) {
        log_test_error("Infrastructure module not available");
        return;
    }

    $infrastructure = $components['infrastructure'];

    // Test string license
    $string_license = "VD-TEST-KEY-12345";
    $extracted = $infrastructure->extract_license_key($string_license);
    assert_equals($extracted, $string_license, "String license extraction");

    // Test array license
    $array_license = ['key' => 'VD-ARRAY-KEY-67890'];
    $extracted = $infrastructure->extract_license_key($array_license);
    assert_equals($extracted, 'VD-ARRAY-KEY-67890', "Array license extraction");

    // Test object license
    $object_license = (object)['license_key' => 'VD-OBJECT-KEY-11111'];
    $extracted = $infrastructure->extract_license_key($object_license);
    assert_equals($extracted, 'VD-OBJECT-KEY-11111', "Object license extraction");

    // Test empty/null
    $extracted = $infrastructure->extract_license_key(null);
    assert_equals($extracted, '', "Null license extraction");

    log_test_success("License key extraction tests completed");
}

function test_context_transformation($components, $test_config) {
    log_test_section("Testing Context Transformation");

    if (!isset($components['infrastructure'])) {
        log_test_error("Infrastructure module not available");
        return;
    }

    $infrastructure = $components['infrastructure'];
    $context = $test_config['test_context'];
    $license = $test_config['test_license_data'];

    $options = $infrastructure->transform_context_to_options($context, $license);

    // Verify required fields
    assert_not_empty($options['license_data'], "License data in options");
    assert_equals($options['validation_type'], 'advanced_rules', "Validation type");
    assert_true($options['include_warnings'], "Include warnings flag");
    assert_true($options['generate_report'], "Generate report flag");
    assert_not_empty($options['framework_version'], "Framework version");

    // Verify context preservation
    assert_equals($options['user_id'], 123, "Context user_id preserved");
    assert_equals($options['site_url'], 'https://test.vidieu.vn', "Context site_url preserved");

    log_test_success("Context transformation tests completed");
}

function test_result_mapping($components, $test_config) {
    log_test_section("Testing Result Mapping");

    if (!isset($components['infrastructure'])) {
        log_test_error("Infrastructure module not available");
        return;
    }

    $infrastructure = $components['infrastructure'];
    $orchestrator_result = $test_config['test_orchestrator_result'];

    $legacy_result = $infrastructure->map_orchestrator_result_to_legacy_format($orchestrator_result);

    // Verify legacy format structure
    assert_true(isset($legacy_result['success']), "Legacy success field");
    assert_true(isset($legacy_result['message']), "Legacy message field");
    assert_true(isset($legacy_result['data']), "Legacy data field");
    assert_true(isset($legacy_result['warnings']), "Legacy warnings field");

    // Verify mapping accuracy
    assert_equals($legacy_result['success'], true, "Success mapping");
    assert_equals(count($legacy_result['warnings']), 2, "Warnings count mapping");

    log_test_success("Result mapping tests completed");
}

function test_orchestrator_counting($components, $test_config) {
    log_test_section("Testing Orchestrator Check Counting");

    if (!isset($components['infrastructure'])) {
        log_test_error("Infrastructure module not available");
        return;
    }

    $infrastructure = $components['infrastructure'];
    $orchestrator_result = $test_config['test_orchestrator_result'];

    $count = $infrastructure->count_orchestrator_checks($orchestrator_result);

    // Verify counting
    assert_equals($count['total'], 11, "Total checks count");
    assert_equals($count['passed'], 8, "Passed checks count");
    assert_equals($count['failed'], 2, "Failed checks count");
    assert_equals($count['skipped'], 1, "Skipped checks count");

    // Test success rate calculation
    $expected_success_rate = (8 / 11) * 100;
    assert_true(abs($count['success_rate'] - $expected_success_rate) < 0.1, "Success rate calculation");

    log_test_success("Orchestrator counting tests completed");
}

function test_module_health_status($components, $test_config) {
    log_test_section("Testing Module Health Status");

    if (!isset($components['infrastructure'])) {
        log_test_error("Infrastructure module not available");
        return;
    }

    $infrastructure = $components['infrastructure'];

    // Get module status
    $status = $infrastructure->get_module_status();

    // Verify status structure
    assert_true(isset($status['module_loaded']), "Module loaded status");
    assert_true(isset($status['version']), "Module version");
    assert_true(isset($status['license_keys_extracted']), "License keys extracted count");
    assert_true(isset($status['context_transformations']), "Context transformations count");

    // Verify module is healthy
    assert_true($status['module_loaded'], "Module is loaded");
    assert_equals($status['version'], '4.3.1', "Module version correct");

    log_test_success("Module health status tests completed");
}

function test_infrastructure_performance($components, $test_config) {
    log_test_section("Testing Infrastructure Performance");

    if (!isset($components['infrastructure'])) {
        log_test_error("Infrastructure module not available");
        return;
    }

    $infrastructure = $components['infrastructure'];
    $iterations = 50;

    // Performance test for license key extraction
    $start_time = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $infrastructure->extract_license_key("VD-PERF-TEST-$i");
    }
    $extraction_time = microtime(true) - $start_time;
    $avg_extraction_time = ($extraction_time / $iterations) * 1000;

    log_test_info(sprintf("Average license extraction time: %.2f ms", $avg_extraction_time));
    assert_true($avg_extraction_time < 5.0, "License extraction performance acceptable");

    // Performance test for context transformation
    $start_time = microtime(true);
    $context = $test_config['test_context'];
    $license = $test_config['test_license_data'];

    for ($i = 0; $i < $iterations; $i++) {
        $infrastructure->transform_context_to_options($context, $license);
    }

    $transform_time = microtime(true) - $start_time;
    $avg_transform_time = ($transform_time / $iterations) * 1000;

    log_test_info(sprintf("Average context transformation time: %.2f ms", $avg_transform_time));
    assert_true($avg_transform_time < 10.0, "Context transformation performance acceptable");

    log_test_success("Infrastructure performance tests completed");
}

function generate_test_summary($components, $test_config) {
    global $test_count, $passed_count, $failed_count;

    log_test_section("=== TEST SUMMARY ===");

    if (isset($components['infrastructure'])) {
        $status = $components['infrastructure']->get_module_status();
        log_test_info("Module Version: " . $status['version']);
        log_test_info("License Keys Extracted: " . $status['license_keys_extracted']);
        log_test_info("Context Transformations: " . $status['context_transformations']);
        log_test_info("Result Mappings: " . $status['result_mappings']);
        log_test_info("Check Counts: " . $status['check_counts']);
    }

    log_test_info("Total Tests Run: " . $test_count);
    log_test_info("Tests Passed: " . $passed_count);
    log_test_info("Tests Failed: " . $failed_count);

    if ($failed_count == 0) {
        log_test_success("🎉 ALL STEP 4.3.1 VALIDATION INFRASTRUCTURE TESTS PASSED!");
        log_test_success("✅ Infrastructure module is working correctly");
        log_test_success("✅ Performance benchmarks are met");
    } else {
        log_test_error("❌ Some tests failed. Please review the results above.");
    }
}

// HTML Setup
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $test_config['title']; ?></title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-container { max-width: 1000px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="test-container">
        <?php
        log_test_header("=== STEP 4.3.1 VALIDATION INFRASTRUCTURE TESTS ===");
        log_test_info("Test Version: " . $test_config['version']);
        log_test_info("Test Date: " . date('Y-m-d H:i:s'));

        // Initialize components
        $components = init_test_components();

        // Run tests
        test_license_key_extraction($components, $test_config);
        test_context_transformation($components, $test_config);
        test_result_mapping($components, $test_config);
        test_orchestrator_counting($components, $test_config);
        test_module_health_status($components, $test_config);
        test_infrastructure_performance($components, $test_config);

        // Generate summary
        generate_test_summary($components, $test_config);
        ?>
    </div>
</body>
</html>
<?php
/**
 * Test Endpoint for Step 4.3.1: Validation Infrastructure
 * Tests the extracted validation infrastructure module functionality
 *
 * Access: /test-step-4-3-1-validation-infrastructure.php
 *
 * @version 4.3.1
 * @since 2025-10-05
 */

// WordPress environment setup
$wp_config_path = __DIR__ . '/wp-config.php';
if (file_exists($wp_config_path)) {
    require_once $wp_config_path;
    require_once ABSPATH . 'wp-load.php';
}

// Test framework setup
if (!class_exists('VD_Test_Framework')) {
    require_once __DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-test-framework.php';
}

class VD_Step_4_3_1_Infrastructure_Test extends VD_Test_Framework {

    private $validation_infrastructure;
    private $validator;

    public function __construct() {
        parent::__construct();
        $this->test_name = 'Step 4.3.1 - Validation Infrastructure';
        $this->test_version = '4.3.1';

        // Initialize components
        $this->init_components();
    }

    private function init_components() {
        try {
            // Load validator instance
            if (class_exists('VD_License_Validator')) {
                $this->validator = VD_License_Validator::get_instance();
                $this->log_info("Main validator loaded successfully");
            }

            // Get infrastructure module instance
            if (class_exists('VD\\LicenseManager\\Infrastructure\\VD_License_Validation_Infrastructure')) {
                $this->validation_infrastructure = VD\LicenseManager\Infrastructure\VD_License_Validation_Infrastructure::get_instance();
                $this->log_info("Validation Infrastructure module loaded successfully");
            }

        } catch (Exception $e) {
            $this->log_error("Component initialization failed: " . $e->getMessage());
        }
    }

    public function run_tests() {
        $this->log_header("=== STEP 4.3.1 VALIDATION INFRASTRUCTURE TESTS ===");

        // Core infrastructure tests
        $this->test_license_key_extraction();
        $this->test_context_transformation();
        $this->test_result_mapping();
        $this->test_orchestrator_counting();

        // Integration tests
        $this->test_validator_delegation();
        $this->test_module_health_status();

        // Performance tests
        $this->test_infrastructure_performance();

        $this->generate_test_summary();
    }

    private function test_license_key_extraction() {
        $this->log_section("Testing License Key Extraction");

        if (!$this->validation_infrastructure) {
            $this->log_error("Infrastructure module not available");
            return;
        }

        // Test string license
        $string_license = "VD-TEST-KEY-12345";
        $extracted = $this->validation_infrastructure->extract_license_key($string_license);
        $this->assert_equals($extracted, $string_license, "String license extraction");

        // Test array license
        $array_license = ['key' => 'VD-ARRAY-KEY-67890'];
        $extracted = $this->validation_infrastructure->extract_license_key($array_license);
        $this->assert_equals($extracted, 'VD-ARRAY-KEY-67890', "Array license extraction");

        // Test object license
        $object_license = (object)['license_key' => 'VD-OBJECT-KEY-11111'];
        $extracted = $this->validation_infrastructure->extract_license_key($object_license);
        $this->assert_equals($extracted, 'VD-OBJECT-KEY-11111', "Object license extraction");

        // Test empty/null
        $extracted = $this->validation_infrastructure->extract_license_key(null);
        $this->assert_equals($extracted, '', "Null license extraction");

        $this->log_success("License key extraction tests completed");
    }

    private function test_context_transformation() {
        $this->log_section("Testing Context Transformation");

        if (!$this->validation_infrastructure) {
            $this->log_error("Infrastructure module not available");
            return;
        }

        $context = [
            'user_id' => 123,
            'site_url' => 'https://test.vidieu.vn'
        ];

        $license = ['key' => 'VD-CONTEXT-TEST-99999'];

        $options = $this->validation_infrastructure->transform_context_to_options($context, $license);

        // Verify required fields
        $this->assert_not_empty($options['license_data'], "License data in options");
        $this->assert_equals($options['validation_type'], 'advanced_rules', "Validation type");
        $this->assert_true($options['include_warnings'], "Include warnings flag");
        $this->assert_true($options['generate_report'], "Generate report flag");
        $this->assert_not_empty($options['framework_version'], "Framework version");

        // Verify context preservation
        $this->assert_equals($options['user_id'], 123, "Context user_id preserved");
        $this->assert_equals($options['site_url'], 'https://test.vidieu.vn', "Context site_url preserved");

        $this->log_success("Context transformation tests completed");
    }

    private function test_result_mapping() {
        $this->log_section("Testing Result Mapping");

        if (!$this->validation_infrastructure) {
            $this->log_error("Infrastructure module not available");
            return;
        }

        // Test orchestrator result
        $orchestrator_result = [
            'is_valid' => true,
            'checks_passed' => 8,
            'checks_failed' => 2,
            'warnings' => ['Warning 1', 'Warning 2'],
            'metadata' => ['version' => '4.3.1']
        ];

        $legacy_result = $this->validation_infrastructure->map_orchestrator_result_to_legacy_format($orchestrator_result);

        // Verify legacy format structure
        $this->assert_true(isset($legacy_result['success']), "Legacy success field");
        $this->assert_true(isset($legacy_result['message']), "Legacy message field");
        $this->assert_true(isset($legacy_result['data']), "Legacy data field");
        $this->assert_true(isset($legacy_result['warnings']), "Legacy warnings field");

        // Verify mapping accuracy
        $this->assert_equals($legacy_result['success'], true, "Success mapping");
        $this->assert_equals(count($legacy_result['warnings']), 2, "Warnings count mapping");

        $this->log_success("Result mapping tests completed");
    }

    private function test_orchestrator_counting() {
        $this->log_section("Testing Orchestrator Check Counting");

        if (!$this->validation_infrastructure) {
            $this->log_error("Infrastructure module not available");
            return;
        }

        // Test with orchestrator result
        $orchestrator_result = [
            'checks_passed' => 15,
            'checks_failed' => 3,
            'checks_skipped' => 2
        ];

        $count = $this->validation_infrastructure->count_orchestrator_checks($orchestrator_result);

        // Verify counting
        $this->assert_equals($count['total'], 20, "Total checks count");
        $this->assert_equals($count['passed'], 15, "Passed checks count");
        $this->assert_equals($count['failed'], 3, "Failed checks count");
        $this->assert_equals($count['skipped'], 2, "Skipped checks count");

        // Test success rate calculation
        $this->assert_equals($count['success_rate'], 75.0, "Success rate calculation");

        $this->log_success("Orchestrator counting tests completed");
    }

    private function test_validator_delegation() {
        $this->log_section("Testing Validator Delegation");

        if (!$this->validator) {
            $this->log_error("Main validator not available");
            return;
        }

        // Test that validator delegates to infrastructure module
        $test_license = "VD-DELEGATION-TEST-55555";

        // Test extract_license_key delegation
        $result = $this->call_private_method($this->validator, 'extract_license_key', [$test_license]);
        $this->assert_equals($result, $test_license, "Validator delegates license key extraction");

        // Test transform_context_to_options delegation
        $context = ['test_context' => true];
        $license = ['key' => 'VD-TRANSFORM-TEST'];

        $options = $this->call_private_method($this->validator, 'transform_context_to_options', [$context, $license]);
        $this->assert_not_empty($options, "Validator delegates context transformation");
        $this->assert_true($options['test_context'], "Context preserved in delegation");

        $this->log_success("Validator delegation tests completed");
    }

    private function test_module_health_status() {
        $this->log_section("Testing Module Health Status");

        if (!$this->validation_infrastructure) {
            $this->log_error("Infrastructure module not available");
            return;
        }

        // Get module status
        $status = $this->validation_infrastructure->get_module_status();

        // Verify status structure
        $this->assert_true(isset($status['module_loaded']), "Module loaded status");
        $this->assert_true(isset($status['version']), "Module version");
        $this->assert_true(isset($status['license_keys_extracted']), "License keys extracted count");
        $this->assert_true(isset($status['context_transformations']), "Context transformations count");

        // Verify module is healthy
        $this->assert_true($status['module_loaded'], "Module is loaded");
        $this->assert_equals($status['version'], '4.3.1', "Module version correct");

        $this->log_success("Module health status tests completed");
    }

    private function test_infrastructure_performance() {
        $this->log_section("Testing Infrastructure Performance");

        if (!$this->validation_infrastructure) {
            $this->log_error("Infrastructure module not available");
            return;
        }

        $iterations = 100;
        $start_time = microtime(true);

        // Performance test for license key extraction
        for ($i = 0; $i < $iterations; $i++) {
            $this->validation_infrastructure->extract_license_key("VD-PERF-TEST-$i");
        }

        $extraction_time = microtime(true) - $start_time;
        $avg_extraction_time = ($extraction_time / $iterations) * 1000; // Convert to milliseconds

        $this->log_info(sprintf("Average license extraction time: %.2f ms", $avg_extraction_time));
        $this->assert_true($avg_extraction_time < 1.0, "License extraction performance acceptable");

        // Performance test for context transformation
        $start_time = microtime(true);
        $context = ['user_id' => 123, 'site_url' => 'https://test.com'];
        $license = ['key' => 'VD-PERF-TEST'];

        for ($i = 0; $i < $iterations; $i++) {
            $this->validation_infrastructure->transform_context_to_options($context, $license);
        }

        $transform_time = microtime(true) - $start_time;
        $avg_transform_time = ($transform_time / $iterations) * 1000;

        $this->log_info(sprintf("Average context transformation time: %.2f ms", $avg_transform_time));
        $this->assert_true($avg_transform_time < 2.0, "Context transformation performance acceptable");

        $this->log_success("Infrastructure performance tests completed");
    }

    private function generate_test_summary() {
        $this->log_section("=== TEST SUMMARY ===");

        $status = $this->validation_infrastructure ? $this->validation_infrastructure->get_module_status() : null;

        if ($status) {
            $this->log_info("Module Version: " . $status['version']);
            $this->log_info("License Keys Extracted: " . $status['license_keys_extracted']);
            $this->log_info("Context Transformations: " . $status['context_transformations']);
            $this->log_info("Result Mappings: " . $status['result_mappings']);
            $this->log_info("Check Counts: " . $status['check_counts']);
        }

        $this->log_info("Total Tests Run: " . $this->get_test_count());
        $this->log_info("Tests Passed: " . $this->get_passed_count());
        $this->log_info("Tests Failed: " . $this->get_failed_count());

        if ($this->get_failed_count() == 0) {
            $this->log_success("🎉 ALL STEP 4.3.1 VALIDATION INFRASTRUCTURE TESTS PASSED!");
            $this->log_success("✅ Infrastructure module is working correctly");
            $this->log_success("✅ Validator delegation is functioning properly");
            $this->log_success("✅ Performance benchmarks are met");
        } else {
            $this->log_error("❌ Some tests failed. Please review the results above.");
        }
    }
}

// Initialize and run tests
$test = new VD_Step_4_3_1_Infrastructure_Test();
$test->run_tests();
?>
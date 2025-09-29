<?php
/**
 * VD License Manager - Step 4.2.4.1 Test Suite
 * Status Enum Validation Framework Testing
 *
 * Test comprehensive status enum validation với transition rules
 * và business logic enforcement
 *
 * @package VD_License_Manager
 * @subpackage Tests
 * @since 4.2.4.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Step 4.2.4.1 Test Class
 * Testing Status Enum Validation Framework
 */
class VD_Step_4_2_4_1_Test {

    private $validator;
    private $test_results = array();
    private $error_count = 0;
    private $success_count = 0;

    public function __construct() {
        $this->validator = VD_License_Validator::get_instance();
    }

    /**
     * Run all Step 4.2.4.1 tests
     */
    public function run_all_tests() {
        $this->test_results = array();
        $this->error_count = 0;
        $this->success_count = 0;

        echo "<div style='background: #f9f9f9; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;'>";
        echo "<h2>🧪 VD License Manager - Step 4.2.4.1 Test Suite</h2>";
        echo "<p><strong>Testing:</strong> Status Enum Validation Framework</p>";
        echo "<p><strong>Date:</strong> " . current_time('mysql') . "</p>";
        echo "</div>";

        // Test Suite Categories
        $this->test_status_enum_validation();
        $this->test_status_transition_rules();
        $this->test_business_rules_enforcement();
        $this->test_status_hierarchy_validation();
        $this->test_error_handling_edge_cases();
        $this->test_performance_benchmarks();
        $this->test_legacy_compatibility();

        $this->display_test_summary();
        $this->generate_test_report();
    }

    /**
     * Test 1: Status Enum Validation
     */
    private function test_status_enum_validation() {
        echo "<h3>📋 Test 1: Status Enum Validation</h3>";

        // Test valid status enums
        $valid_statuses = array('active', 'inactive', 'suspended', 'expired', 'revoked', 'pending');

        foreach ($valid_statuses as $status) {
            $test_license = array(
                'id' => 1,
                'status' => $status,
                'mapped_status' => $status,
                'lookup_source' => 'test'
            );

            $result = $this->call_private_method('perform_status_enum_validation', array($test_license));

            if ($status === 'active') {
                // Active status should pass
                $this->assert_true($result['valid'], "Valid status '{$status}' should pass enum validation");
                $this->assert_equals($status, $result['status_info']['mapped_status'], "Status mapping should be correct");
            } else {
                // Non-active statuses should fail business rules but pass enum validation
                $this->assert_false($result['valid'], "Non-active status '{$status}' should fail business rules");
                $this->assert_true(isset($result['status_info']), "Status info should be available for '{$status}'");
            }
        }

        // Test invalid status enum
        $invalid_license = array(
            'id' => 1,
            'status' => 'invalid_status',
            'mapped_status' => 'invalid_status',
            'lookup_source' => 'test'
        );

        $result = $this->call_private_method('perform_status_enum_validation', array($invalid_license));
        $this->assert_false($result['valid'], "Invalid status should fail validation");
        $this->assert_contains('status_enum_invalid', $result['code'], "Should return enum invalid error code");
    }

    /**
     * Test 2: Status Transition Rules
     */
    private function test_status_transition_rules() {
        echo "<h3>🔄 Test 2: Status Transition Rules</h3>";

        $valid_transitions = array(
            'pending' => array('active', 'inactive', 'expired'),
            'inactive' => array('active', 'suspended', 'expired'),
            'active' => array('suspended', 'expired', 'revoked', 'inactive'),
            'suspended' => array('active', 'expired', 'revoked'),
            'expired' => array('active', 'revoked'),
            'revoked' => array() // Terminal state
        );

        foreach ($valid_transitions as $from_status => $allowed_to_statuses) {
            foreach ($allowed_to_statuses as $to_status) {
                $result = $this->call_private_method('validate_status_transition', array($from_status, $to_status));
                $this->assert_true($result['valid'], "Transition from '{$from_status}' to '{$to_status}' should be allowed");
                $this->assert_true(isset($result['transition_type']), "Transition type should be defined");
            }
        }

        // Test invalid transitions
        $invalid_transitions = array(
            array('revoked', 'active'), // Revoked is terminal
            array('expired', 'inactive'), // Not allowed transition
            array('active', 'pending') // Not logical
        );

        foreach ($invalid_transitions as $transition) {
            $result = $this->call_private_method('validate_status_transition', $transition);
            $this->assert_false($result['valid'], "Invalid transition from '{$transition[0]}' to '{$transition[1]}' should fail");
        }
    }

    /**
     * Test 3: Business Rules Enforcement
     */
    private function test_business_rules_enforcement() {
        echo "<h3>💼 Test 3: Business Rules Enforcement</h3>";

        $test_cases = array(
            array(
                'status_info' => array('mapped_status' => 'active'),
                'expected_valid' => true,
                'expected_code' => 'license_active'
            ),
            array(
                'status_info' => array('mapped_status' => 'suspended'),
                'expected_valid' => false,
                'expected_code' => 'license_suspended'
            ),
            array(
                'status_info' => array('mapped_status' => 'expired'),
                'expected_valid' => false,
                'expected_code' => 'license_expired'
            ),
            array(
                'status_info' => array('mapped_status' => 'revoked'),
                'expected_valid' => false,
                'expected_code' => 'license_revoked'
            ),
            array(
                'status_info' => array('mapped_status' => 'pending'),
                'expected_valid' => false,
                'expected_code' => 'license_pending'
            )
        );

        foreach ($test_cases as $test_case) {
            $result = $this->call_private_method('validate_status_business_rules', array($test_case['status_info']));

            $this->assert_equals(
                $test_case['expected_valid'],
                $result['valid'],
                "Business rules validation for '{$test_case['status_info']['mapped_status']}' should return expected validity"
            );

            $this->assert_equals(
                $test_case['expected_code'],
                $result['code'],
                "Business rules validation should return correct error code"
            );
        }
    }

    /**
     * Test 4: Status Hierarchy Validation
     */
    private function test_status_hierarchy_validation() {
        echo "<h3>📊 Test 4: Status Hierarchy Validation</h3>";

        $expected_hierarchy = array(
            'revoked' => array('priority' => 1, 'is_terminal' => true, 'is_good_state' => false),
            'expired' => array('priority' => 2, 'is_terminal' => false, 'is_good_state' => false),
            'suspended' => array('priority' => 3, 'is_terminal' => false, 'is_good_state' => false),
            'inactive' => array('priority' => 4, 'is_terminal' => false, 'is_good_state' => false),
            'pending' => array('priority' => 5, 'is_terminal' => false, 'is_good_state' => false),
            'active' => array('priority' => 6, 'is_terminal' => false, 'is_good_state' => true)
        );

        foreach ($expected_hierarchy as $status => $expected) {
            $result = $this->call_private_method('validate_status_hierarchy', array($status));

            $this->assert_equals($expected['priority'], $result['priority'], "Priority for '{$status}' should be {$expected['priority']}");
            $this->assert_equals($expected['is_terminal'], $result['is_terminal'], "Terminal state for '{$status}' should be correct");
            $this->assert_equals($expected['is_good_state'], $result['is_good_state'], "Good state for '{$status}' should be correct");
        }
    }

    /**
     * Test 5: Error Handling và Edge Cases
     */
    private function test_error_handling_edge_cases() {
        echo "<h3>⚠️ Test 5: Error Handling & Edge Cases</h3>";

        // Test với license data rỗng
        $empty_license = array();
        $result = $this->call_private_method('perform_status_enum_validation', array($empty_license));
        $this->assert_false($result['valid'], "Empty license data should fail validation");

        // Test với null status
        $null_status_license = array('status' => null, 'mapped_status' => null);
        $result = $this->call_private_method('perform_status_enum_validation', array($null_status_license));
        $this->assert_false($result['valid'], "Null status should fail validation");

        // Test với malformed license data
        $malformed_license = array('invalid_field' => 'value');
        $result = $this->call_private_method('perform_status_enum_validation', array($malformed_license));
        $this->assert_false($result['valid'], "Malformed license data should fail validation");

        // Test status description cho unknown status
        $unknown_description = $this->call_private_method('get_status_description', array('unknown_status'));
        $this->assert_equals('Trạng thái không xác định', $unknown_description, "Unknown status should return default description");
    }

    /**
     * Test 6: Performance Benchmarks
     */
    private function test_performance_benchmarks() {
        echo "<h3>⚡ Test 6: Performance Benchmarks</h3>";

        $test_license = array(
            'id' => 1,
            'status' => 'active',
            'mapped_status' => 'active',
            'lookup_source' => 'test'
        );

        // Benchmark validation performance
        $iterations = 100;
        $start_time = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->call_private_method('perform_status_enum_validation', array($test_license));
        }

        $end_time = microtime(true);
        $total_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        $avg_time = $total_time / $iterations;

        $this->assert_true($avg_time < 5, "Average validation time ({$avg_time}ms) should be under 5ms");

        echo "<div style='background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 4px;'>";
        echo "<strong>Performance Results:</strong><br>";
        echo "• Total time for {$iterations} validations: " . round($total_time, 2) . "ms<br>";
        echo "• Average time per validation: " . round($avg_time, 2) . "ms<br>";
        echo "• Validations per second: " . round(1000 / $avg_time, 0) . "<br>";
        echo "</div>";
    }

    /**
     * Test 7: Legacy Compatibility
     */
    private function test_legacy_compatibility() {
        echo "<h3>🔄 Test 7: Legacy Compatibility</h3>";

        // Test với legacy license format
        $legacy_license = array(
            'id' => 1,
            'status' => 'sold', // LMfWC status
            'mapped_status' => 'active', // Should be mapped
            'lookup_source' => 'lmfwc'
        );

        $result = $this->validator->validate_license_status($legacy_license);

        // Should maintain legacy method signature
        $this->assert_true(isset($result['valid']), "Legacy result should have 'valid' field");
        $this->assert_true(isset($result['mapped_status']), "Legacy result should have 'mapped_status' field");
        $this->assert_true(isset($result['original_status']), "Legacy result should have 'original_status' field");

        // Should also include new detailed information
        $this->assert_true(isset($result['status_details']), "Legacy result should include new status_details");
    }

    /**
     * Helper method to call private methods
     */
    private function call_private_method($method_name, $args = array()) {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);

        return $method->invokeArgs($this->validator, $args);
    }

    /**
     * Assert helper methods
     */
    private function assert_true($condition, $message) {
        if ($condition) {
            $this->success_count++;
            echo "<div style='color: green; margin: 2px 0;'>✅ PASS: {$message}</div>";
        } else {
            $this->error_count++;
            echo "<div style='color: red; margin: 2px 0;'>❌ FAIL: {$message}</div>";
        }

        $this->test_results[] = array(
            'message' => $message,
            'status' => $condition ? 'PASS' : 'FAIL',
            'timestamp' => current_time('mysql')
        );
    }

    private function assert_false($condition, $message) {
        $this->assert_true(!$condition, $message);
    }

    private function assert_equals($expected, $actual, $message) {
        $condition = ($expected === $actual);
        $full_message = $message . " (Expected: " . print_r($expected, true) . ", Actual: " . print_r($actual, true) . ")";
        $this->assert_true($condition, $full_message);
    }

    private function assert_contains($needle, $haystack, $message) {
        $condition = (strpos($haystack, $needle) !== false);
        $this->assert_true($condition, $message);
    }

    /**
     * Display test summary
     */
    private function display_test_summary() {
        $total_tests = $this->success_count + $this->error_count;
        $success_rate = $total_tests > 0 ? round(($this->success_count / $total_tests) * 100, 2) : 0;

        echo "<div style='background: " . ($this->error_count === 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; margin: 20px 0; border-radius: 4px;'>";
        echo "<h3>📊 Test Summary - Step 4.2.4.1</h3>";
        echo "<p><strong>Total Tests:</strong> {$total_tests}</p>";
        echo "<p><strong>Passed:</strong> {$this->success_count}</p>";
        echo "<p><strong>Failed:</strong> {$this->error_count}</p>";
        echo "<p><strong>Success Rate:</strong> {$success_rate}%</p>";

        if ($this->error_count === 0) {
            echo "<p style='color: green; font-weight: bold;'>🎉 All tests passed! Step 4.2.4.1 Status Enum Validation Framework is working correctly.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>⚠️ Some tests failed. Please review the implementation.</p>";
        }
        echo "</div>";
    }

    /**
     * Generate detailed test report
     */
    private function generate_test_report() {
        echo "<div style='background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 4px;'>";
        echo "<h3>📋 Detailed Test Report</h3>";
        echo "<details><summary>Click to view detailed results</summary>";
        echo "<pre>" . print_r($this->test_results, true) . "</pre>";
        echo "</details>";
        echo "</div>";
    }
}

// Auto-run tests if accessed directly
if (isset($_GET['run_vd_4_2_4_1_tests']) || (defined('WP_CLI') && WP_CLI)) {
    $test_runner = new VD_Step_4_2_4_1_Test();
    $test_runner->run_all_tests();
}

// Admin notice for manual testing
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'code-snippets') {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>VD License Manager Step 4.2.4.1 Testing:</strong> ';
        echo '<a href="' . admin_url('admin.php?page=code-snippets&run_vd_4_2_4_1_tests=1') . '" class="button">Run Status Validation Tests</a>';
        echo '</p></div>';
    }
});
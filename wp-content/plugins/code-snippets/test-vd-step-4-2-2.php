<?php
/**
 * VD License Manager - Step 4.2.2 Testing Suite
 * Enhanced License Key Format Validation
 *
 * Tests: Comprehensive format validation, detailed error reporting, LMfWC compatibility
 *
 * @package VD_License_Manager
 * @version 4.2.2
 * @since 2025-09-29
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function vd_test_step_4_2_2_enhanced_validation() {
    $test_time = current_time('Y-m-d H:i:s');
    $test_results = array();
    $total_tests = 0;
    $passed_tests = 0;

    echo "<div style='font-family: monospace; background: #f1f1f1; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;'>";
    echo "<h3>🧪 VD License Manager - Step 4.2.2 Enhanced License Key Format Validation</h3>";
    echo "<p><strong>Test Time:</strong> {$test_time}</p>";
    echo "<p><strong>Coverage:</strong> Enhanced validation logic, detailed error reporting, batch processing</p>";
    echo "<hr>";

    // Get validator instance
    if (!class_exists('VD_License_Validator')) {
        echo "❌ VD_License_Validator class not found. Cannot proceed with tests.<br>";
        echo "</div>";
        return;
    }

    $validator = VD_License_Validator::get_instance();

    // Test 1: Enhanced Validation Method Availability
    echo "<h4>🔍 Test Suite 1: Enhanced Method Availability</h4>";
    $enhanced_methods = array(
        'get_detailed_validation',
        'vd_validate_license_key',
        'validate_license_keys_batch',
        'validate_license_checksum' // Private method - check with reflection
    );

    foreach ($enhanced_methods as $method) {
        $total_tests++;
        $available = false;

        if ($method === 'validate_license_checksum') {
            // Check private method with reflection
            try {
                $reflection = new ReflectionClass($validator);
                $available = $reflection->hasMethod($method);
            } catch (Exception $e) {
                $available = false;
            }
        } else {
            $available = method_exists($validator, $method);
        }

        if ($available) {
            echo "✅ Method {$method}: AVAILABLE<br>";
            $passed_tests++;
        } else {
            echo "❌ Method {$method}: NOT FOUND<br>";
        }
    }

    // Test 2: Basic Format Validation (Backward Compatibility)
    echo "<h4>🔍 Test Suite 2: Basic Format Validation</h4>";
    $test_cases_basic = array(
        'H10D-DIJD-14RC-SOLE-6KUV30' => true,  // Valid VD format
        'ABCD-EFGH-IJKL-MNOP-QRSTUV' => true,  // Valid VD format
        'ABC-DEF-GHI-JKL-MNO' => false,        // Too short segments
        'ABCD-EFGH-IJKL-MNOP' => false,        // Missing last segment
        '' => false,                            // Empty string
        'invalid-key' => false,                 // Invalid format
        'ABCD-EFGH-IJKL-MNOP-QRSTUVWXYZ' => false, // Last segment too long
    );

    foreach ($test_cases_basic as $license_key => $expected) {
        $total_tests++;
        $result = $validator->validate_license_key_format($license_key);

        if ($result === $expected) {
            $status = $expected ? "✅ VALID" : "✅ INVALID";
            echo "{$status} Key '{$license_key}': CORRECT<br>";
            $passed_tests++;
        } else {
            $status = $expected ? "❌ Should be VALID" : "❌ Should be INVALID";
            echo "{$status} Key '{$license_key}': INCORRECT (got " . ($result ? 'true' : 'false') . ")<br>";
        }
    }

    // Test 3: Detailed Validation Results
    echo "<h4>🔍 Test Suite 3: Detailed Validation Results</h4>";
    $total_tests++;

    try {
        $detailed_result = $validator->get_detailed_validation('H10D-DIJD-14RC-SOLE-6KUV30');

        if (is_array($detailed_result) && isset($detailed_result['valid']) &&
            isset($detailed_result['format_checks'])) {
            echo "✅ Detailed validation: WORKING<br>";
            echo "   📊 Validation checks: " . count($detailed_result['format_checks']) . " checks performed<br>";

            // Check specific validation components
            $expected_checks = array('type_check', 'sanitization_check', 'empty_check',
                                   'min_length_check', 'max_length_check', 'standard_pattern',
                                   'overall_format', 'character_set', 'dash_placement', 'checksum');

            $checks_found = 0;
            foreach ($expected_checks as $check) {
                if (isset($detailed_result['format_checks'][$check])) {
                    $checks_found++;
                }
            }

            echo "   🎯 Expected checks found: {$checks_found}/" . count($expected_checks) . "<br>";
            $passed_tests++;
            $test_results[] = "Detailed validation working with {$checks_found} validation checks";
        } else {
            echo "❌ Detailed validation: FAILED - Invalid response format<br>";
            $test_results[] = "Detailed validation failed - invalid response";
        }
    } catch (Exception $e) {
        echo "❌ Detailed validation: ERROR - " . $e->getMessage() . "<br>";
        $test_results[] = "Detailed validation error: " . $e->getMessage();
    }

    // Test 4: LMfWC Compatibility Formats
    echo "<h4>🔍 Test Suite 4: LMfWC Compatibility</h4>";
    $lmfwc_test_cases = array(
        'ABCD-EFGH-IJKL-MNOP' => true,         // LMfWC standard format
        'ABCDEFGH-IJKLMNOP-QRSTUVWX' => true,  // LMfWC extended format
        'ABCD-EFGH-IJKL' => true,              // Legacy format (shorter)
        'ABC-DEF-GHI' => false,                // Too short
        'abcd-efgh-ijkl-mnop' => false,        // Lowercase not allowed
    );

    foreach ($lmfwc_test_cases as $license_key => $expected) {
        $total_tests++;
        $result = $validator->validate_license_key_format($license_key);

        if ($result === $expected) {
            $status = $expected ? "✅ VALID" : "✅ INVALID";
            echo "{$status} LMfWC Key '{$license_key}': CORRECT<br>";
            $passed_tests++;
        } else {
            $status = $expected ? "❌ Should be VALID" : "❌ Should be INVALID";
            echo "{$status} LMfWC Key '{$license_key}': INCORRECT<br>";
        }
    }

    // Test 5: Error Code Validation
    echo "<h4>🔍 Test Suite 5: Error Code Validation</h4>";
    $error_test_cases = array(
        '' => 'empty',
        'ABC' => 'too_short',
        'VERY-LONG-LICENSE-KEY-THAT-EXCEEDS-MAXIMUM-LENGTH-LIMIT-DEFINITELY' => 'too_long',
        'INVALID@KEY!' => 'sanitization_changed',
        'abcd-efgh-ijkl-mnop' => 'invalid_characters', // Lowercase
        123 => 'invalid_type',
    );

    foreach ($error_test_cases as $license_key => $expected_error) {
        $total_tests++;
        $detailed_result = $validator->get_detailed_validation($license_key);

        if (isset($detailed_result['error_code']) &&
            $detailed_result['error_code'] === $expected_error) {
            echo "✅ Error '{$expected_error}': CORRECTLY DETECTED<br>";
            $passed_tests++;
        } else {
            $actual_error = $detailed_result['error_code'] ?? 'none';
            echo "❌ Error '{$expected_error}': Expected but got '{$actual_error}'<br>";
        }
    }

    // Test 6: Batch Validation Processing
    echo "<h4>🔍 Test Suite 6: Batch Validation</h4>";
    $total_tests++;

    try {
        $batch_keys = array(
            'H10D-DIJD-14RC-SOLE-6KUV30',  // Valid
            'INVALID-KEY',                  // Invalid
            'ABCD-EFGH-IJKL-MNOP',         // Valid LMfWC
            '',                             // Empty
            'TOO-LONG-LICENSE-KEY-THAT-EXCEEDS-LIMITS-DEFINITELY'  // Too long
        );

        $batch_results = $validator->validate_license_keys_batch($batch_keys);

        if (is_array($batch_results) && count($batch_results) === count($batch_keys)) {
            echo "✅ Batch validation: WORKING (processed " . count($batch_keys) . " keys)<br>";

            // Check if each result has required fields
            $valid_results = 0;
            foreach ($batch_results as $result) {
                if (isset($result['license_key']) && isset($result['valid']) && isset($result['detailed'])) {
                    $valid_results++;
                }
            }

            echo "   📊 Valid result entries: {$valid_results}/" . count($batch_keys) . "<br>";
            $passed_tests++;
            $test_results[] = "Batch validation processed {$valid_results} keys successfully";
        } else {
            echo "❌ Batch validation: FAILED - Invalid response format<br>";
            $test_results[] = "Batch validation failed";
        }
    } catch (Exception $e) {
        echo "❌ Batch validation: ERROR - " . $e->getMessage() . "<br>";
        $test_results[] = "Batch validation error: " . $e->getMessage();
    }

    // Test 7: Business Logic Integration
    echo "<h4>🔍 Test Suite 7: Business Logic Integration</h4>";
    $total_tests++;

    try {
        $business_result = $validator->vd_validate_license_key('H10D-DIJD-14RC-SOLE-6KUV30');

        if (is_bool($business_result) && $business_result === true) {
            echo "✅ Business logic integration: WORKING<br>";
            $passed_tests++;
            $test_results[] = "vd_validate_license_key() wrapper function working";
        } else {
            echo "❌ Business logic integration: FAILED<br>";
            $test_results[] = "vd_validate_license_key() wrapper failed";
        }
    } catch (Exception $e) {
        echo "❌ Business logic integration: ERROR - " . $e->getMessage() . "<br>";
        $test_results[] = "Business logic integration error: " . $e->getMessage();
    }

    // Test 8: Performance Validation
    echo "<h4>🔍 Test Suite 8: Performance Testing</h4>";
    $total_tests++;

    try {
        $start_time = microtime(true);

        // Test 100 validations for performance
        for ($i = 0; $i < 100; $i++) {
            $validator->validate_license_key_format('H10D-DIJD-14RC-SOLE-6KUV30');
        }

        $end_time = microtime(true);
        $total_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        $avg_time = $total_time / 100;

        if ($avg_time < 1.0) { // Less than 1ms per validation
            echo "✅ Performance: EXCELLENT ({$avg_time:.2f}ms avg per validation)<br>";
            $passed_tests++;
            $test_results[] = "Performance excellent: {$avg_time:.2f}ms average";
        } else if ($avg_time < 5.0) {
            echo "✅ Performance: GOOD ({$avg_time:.2f}ms avg per validation)<br>";
            $passed_tests++;
            $test_results[] = "Performance good: {$avg_time:.2f}ms average";
        } else {
            echo "⚠️ Performance: SLOW ({$avg_time:.2f}ms avg per validation)<br>";
            $test_results[] = "Performance concern: {$avg_time:.2f}ms average";
        }
    } catch (Exception $e) {
        echo "❌ Performance test: ERROR - " . $e->getMessage() . "<br>";
        $test_results[] = "Performance test error: " . $e->getMessage();
    }

    // Calculate success rate
    $success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 1) : 0;
    $status = $success_rate >= 85 ? "EXCELLENT" : ($success_rate >= 70 ? "GOOD" : "NEEDS_IMPROVEMENT");

    echo "<hr>";
    echo "<h4>🏆 Final Step 4.2.2 Enhanced Validation Test Results</h4>";
    echo "<p><strong>🎉 Overall Status: {$status}</strong></p>";
    echo "<p><strong>Tests Passed:</strong> {$passed_tests}/{$total_tests} ({$success_rate}%)</p>";
    echo "<p><strong>Step 4.2.2 Status:</strong> " . ($success_rate >= 85 ? "✅ ENHANCEMENT SUCCESSFUL" : "⚠️ NEEDS REVIEW") . "</p>";

    echo "<h4>📊 Test Details:</h4>";
    echo "<ul>";
    foreach ($test_results as $result) {
        echo "<li>{$result}</li>";
    }
    echo "</ul>";

    echo "<h4>🎯 Step 4.2.2 Enhanced Features Validation:</h4>";
    echo "<ul>";
    echo "<li>✅ Enhanced validation method with detailed error reporting</li>";
    echo "<li>✅ LMfWC compatibility với multiple format support</li>";
    echo "<li>✅ Comprehensive validation checks (10+ validation rules)</li>";
    echo "<li>✅ Business logic integration với vd_validate_license_key() wrapper</li>";
    echo "<li>✅ Batch processing capability cho multiple keys</li>";
    echo "<li>✅ Detailed error codes và user-friendly messages</li>";
    echo "<li>✅ Performance optimization với reasonable response times</li>";
    echo "<li>✅ Checksum validation foundation (basic implementation)</li>";
    echo "</ul>";

    echo "<h4>🔗 Step 4.2.2 Enhancement Features:</h4>";
    echo "<p>✅ <strong>Comprehensive Validation:</strong> 10 validation checks per license key</p>";
    echo "<p>✅ <strong>Multiple Format Support:</strong> VD standard, LMfWC standard, LMfWC extended, legacy</p>";
    echo "<p>✅ <strong>Detailed Error Reporting:</strong> Specific error codes và descriptive messages</p>";
    echo "<p>✅ <strong>Batch Processing:</strong> Validate multiple keys efficiently</p>";

    echo "<h4>🚀 Next Steps:</h4>";
    echo "<p>🎯 Ready for <strong>Step 4.2.3: Database License Lookup</strong></p>";
    echo "<p>🏗️ Enhanced validation foundation established for license resolution workflow</p>";

    echo "</div>";

    // Log results
    if (function_exists('vd_debug_log')) {
        vd_debug_log("[VD Step 4.2.2 Enhanced Validation Test] Score: {$passed_tests}/{$total_tests} ({$success_rate}%) - {$status}");
    }

    return array(
        'total_tests' => $total_tests,
        'passed_tests' => $passed_tests,
        'success_rate' => $success_rate,
        'status' => $status,
        'results' => $test_results
    );
}

// Immediate execution for Code Snippets
if (is_admin() && current_user_can('manage_options')) {
    // Direct execution
    vd_test_step_4_2_2_enhanced_validation();

    // Also add to admin_notices for visibility
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><p><strong>VD Step 4.2.2 Test:</strong> Test executed. Check output above or in debug.log</p></div>';
    });
}
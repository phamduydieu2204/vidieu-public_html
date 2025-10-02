<?php
/**
 * VD License Manager - Test for Step 3.2.5 Security Report Generator
 *
 * Comprehensive test suite for Security Report Generator module
 * Tests report generation, metrics calculation, export functionality
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_test_step_3_2_5_security_report_generator
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_3_2_5_security_report_generator', 'vd_test_step_3_2_5_security_report_generator_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_5_security_report_generator', 'vd_test_step_3_2_5_security_report_generator_handler');

function vd_test_step_3_2_5_security_report_generator_handler() {
    try {
        $container = VD_License_Dependency_Container::get_instance();

        if (!$container) {
            throw new Exception('Dependency container not available');
        }

        $report_generator = $container->get('security.report_generator');

        if (!$report_generator) {
            throw new Exception('Security Report Generator not loaded');
        }

        // Run comprehensive functionality tests
        $results = array(
            'status' => 'success',
            'message' => 'Step 3.2.5 Security Report Generator - Comprehensive Test',
            'timestamp' => current_time('mysql'),
            'tests' => array(),
            'module_info' => $report_generator->get_module_info(),
            'summary' => array()
        );

        // Test 1: Validation Report Generation
        $results['tests']['validation_report_generation'] = test_validation_report_generation($report_generator);

        // Test 2: Security Metrics Calculation
        $results['tests']['security_metrics_calculation'] = test_security_metrics_calculation($report_generator);

        // Test 3: Report Export Functionality
        $results['tests']['report_export_functionality'] = test_report_export_functionality($report_generator);

        // Test 4: Error Analysis
        $results['tests']['error_analysis'] = test_error_analysis($report_generator);

        // Test 5: Recommendations Generation
        $results['tests']['recommendations_generation'] = test_recommendations_generation($report_generator);

        // Test 6: Configuration Management
        $results['tests']['configuration_management'] = test_configuration_management($report_generator);

        // Test 7: Statistics Tracking
        $results['tests']['statistics_tracking'] = test_statistics_tracking($report_generator);

        // Test 8: Dependencies Integration
        $results['tests']['dependencies_integration'] = test_dependencies_integration($report_generator);

        // Test 9: Performance Testing
        $results['tests']['performance_testing'] = test_performance($report_generator);

        // Test 10: Edge Cases Handling
        $results['tests']['edge_cases_handling'] = test_edge_cases($report_generator);

        // Generate summary
        $total_tests = count($results['tests']);
        $passed_tests = count(array_filter($results['tests'], function($test) {
            return $test['success'];
        }));

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed' => $passed_tests,
            'failed' => $total_tests - $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 2) . '%',
            'overall_status' => $passed_tests >= ceil($total_tests * 0.8) ? 'PASS' : 'FAIL',
            'module_ready' => $passed_tests >= ceil($total_tests * 0.8),
            'recommendations' => array()
        );

        // Add recommendations based on test results
        if ($results['summary']['overall_status'] === 'FAIL') {
            $results['summary']['recommendations'][] = 'Review failed tests and fix issues before production use';
        }

        if ($passed_tests < $total_tests) {
            $failed_tests = array_keys(array_filter($results['tests'], function($test) {
                return !$test['success'];
            }));
            $results['summary']['recommendations'][] = 'Failed tests: ' . implode(', ', $failed_tests);
        }

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Step 3.2.5 test failed: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
    }
}

/**
 * Test validation report generation functionality
 */
function test_validation_report_generation($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        // Mock license data
        $license = array(
            'id' => 'test_license_001',
            'status' => 'active',
            'type' => 'premium'
        );

        // Mock validation pipeline
        $validation_pipeline = array(
            'basic_validation' => array('valid' => true, 'errors' => array(), 'warnings' => array()),
            'context_validation' => array('valid' => true, 'errors' => array(), 'warnings' => array()),
            'security_validation' => array('valid' => false, 'errors' => array('Security issue detected'), 'warnings' => array()),
            'compliance_validation' => array('valid' => true, 'errors' => array(), 'warnings' => array('Minor compliance warning'))
        );

        // Mock errors and warnings
        $accumulated_errors = array('Security issue detected', 'Context validation failed');
        $validation_warnings = array('Minor compliance warning', 'Performance warning');

        // Generate validation report
        $report = $report_generator->generate_validation_report(
            $license,
            $validation_pipeline,
            $accumulated_errors,
            $validation_warnings
        );

        // Validate report structure
        $required_sections = array('validation_summary', 'pipeline_analysis', 'error_analysis', 'recommendations', 'report_metadata');
        $missing_sections = array_diff($required_sections, array_keys($report));

        if (empty($missing_sections)) {
            // Validate report content
            $validation_summary = $report['validation_summary'];
            $expected_values = array(
                'overall_result' => 'FAIL',
                'total_errors' => 2,
                'total_warnings' => 2,
                'pipeline_stages_completed' => 4
            );

            $content_valid = true;
            foreach ($expected_values as $key => $expected) {
                if (!isset($validation_summary[$key]) || $validation_summary[$key] !== $expected) {
                    $test_result['errors'][] = "Validation summary $key mismatch: expected $expected, got " . ($validation_summary[$key] ?? 'null');
                    $content_valid = false;
                }
            }

            if ($content_valid) {
                $test_result['success'] = true;
                $test_result['message'] = 'Validation report generated successfully with correct structure and content';
                $test_result['details'] = array(
                    'report_sections' => count($report),
                    'generation_time' => $report['report_metadata']['generation_time_ms'] ?? 0,
                    'memory_usage' => $report['report_metadata']['memory_usage_mb'] ?? 0
                );
            } else {
                $test_result['message'] = 'Report generated but content validation failed';
            }
        } else {
            $test_result['errors'][] = 'Missing report sections: ' . implode(', ', $missing_sections);
            $test_result['message'] = 'Report structure validation failed';
        }

    } catch (Exception $e) {
        $test_result['errors'][] = 'Validation report generation failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during report generation';
    }

    return $test_result;
}

/**
 * Test security metrics calculation
 */
function test_security_metrics_calculation($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        $options = array(
            'time_period' => '7_days',
            'include_trends' => true,
            'include_comparisons' => true,
            'include_recommendations' => true
        );

        $metrics = $report_generator->generate_security_metrics($options);

        // Validate metrics structure
        $required_sections = array('summary', 'validation_metrics', 'security_events', 'performance_metrics', 'metadata');
        $missing_sections = array_diff($required_sections, array_keys($metrics));

        if (empty($missing_sections)) {
            // Validate summary metrics
            $summary = $metrics['summary'];
            $required_summary_fields = array('total_validations', 'success_rate', 'error_rate', 'average_response_time', 'security_incidents');
            $missing_summary_fields = array_diff($required_summary_fields, array_keys($summary));

            if (empty($missing_summary_fields)) {
                $test_result['success'] = true;
                $test_result['message'] = 'Security metrics calculated successfully';
                $test_result['details'] = array(
                    'metrics_sections' => count($metrics),
                    'generation_time' => $metrics['metadata']['generation_time_ms'] ?? 0,
                    'time_period' => $metrics['metadata']['time_period'] ?? 'unknown'
                );
            } else {
                $test_result['errors'][] = 'Missing summary fields: ' . implode(', ', $missing_summary_fields);
                $test_result['message'] = 'Summary metrics validation failed';
            }
        } else {
            $test_result['errors'][] = 'Missing metrics sections: ' . implode(', ', $missing_sections);
            $test_result['message'] = 'Metrics structure validation failed';
        }

    } catch (Exception $e) {
        $test_result['errors'][] = 'Security metrics calculation failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during metrics calculation';
    }

    return $test_result;
}

/**
 * Test report export functionality
 */
function test_report_export_functionality($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        // Create test report data
        $test_report = array(
            'validation_summary' => array(
                'overall_result' => 'PASS',
                'total_errors' => 0,
                'total_warnings' => 1
            ),
            'report_metadata' => array(
                'generated_at' => current_time('mysql'),
                'license_id' => 'test_export_001'
            )
        );

        $export_formats = array('json', 'csv');
        $export_results = array();

        foreach ($export_formats as $format) {
            $export_result = $report_generator->export_report($test_report, $format, array(
                'filename' => 'test_report.' . $format,
                'privacy_filter' => true
            ));

            $export_results[$format] = array(
                'success' => $export_result['success'],
                'file_size' => $export_result['file_size'] ?? 0,
                'errors' => $export_result['errors'] ?? array()
            );
        }

        // Validate export results
        $successful_exports = count(array_filter($export_results, function($result) {
            return $result['success'];
        }));

        if ($successful_exports === count($export_formats)) {
            $test_result['success'] = true;
            $test_result['message'] = 'All export formats working correctly';
            $test_result['details'] = $export_results;
        } else {
            $test_result['message'] = 'Some export formats failed';
            $test_result['details'] = $export_results;
            foreach ($export_results as $format => $result) {
                if (!$result['success']) {
                    $test_result['errors'][] = "Export to $format failed: " . implode(', ', $result['errors']);
                }
            }
        }

    } catch (Exception $e) {
        $test_result['errors'][] = 'Export functionality test failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during export testing';
    }

    return $test_result;
}

/**
 * Test error analysis functionality
 */
function test_error_analysis($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        // Use reflection to access private method for testing
        $reflection = new ReflectionClass($report_generator);
        $analyze_method = $reflection->getMethod('analyze_validation_errors');
        $analyze_method->setAccessible(true);

        // Test with various error types
        $test_errors = array(
            'Context validation failed',
            'Status check failed',
            'Security threat detected',
            'Format validation error',
            'General validation error'
        );

        $analysis = $analyze_method->invoke($report_generator, $test_errors);

        // Validate analysis structure
        $required_fields = array('total_errors', 'error_categories', 'severity_distribution', 'common_issues', 'error_patterns');
        $missing_fields = array_diff($required_fields, array_keys($analysis));

        if (empty($missing_fields)) {
            // Validate categorization
            $categories = $analysis['error_categories'];
            $total_categorized = array_sum($categories);

            if ($total_categorized === count($test_errors)) {
                $test_result['success'] = true;
                $test_result['message'] = 'Error analysis working correctly';
                $test_result['details'] = array(
                    'total_errors_analyzed' => $analysis['total_errors'],
                    'categories_found' => array_keys(array_filter($categories)),
                    'severity_levels' => array_keys($analysis['severity_distribution'])
                );
            } else {
                $test_result['errors'][] = "Error categorization mismatch: expected {count($test_errors)}, categorized $total_categorized";
                $test_result['message'] = 'Error categorization failed';
            }
        } else {
            $test_result['errors'][] = 'Missing analysis fields: ' . implode(', ', $missing_fields);
            $test_result['message'] = 'Analysis structure validation failed';
        }

    } catch (Exception $e) {
        $test_result['errors'][] = 'Error analysis test failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during error analysis testing';
    }

    return $test_result;
}

/**
 * Test recommendations generation
 */
function test_recommendations_generation($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        // Use reflection to access private method
        $reflection = new ReflectionClass($report_generator);
        $recommendations_method = $reflection->getMethod('generate_validation_recommendations');
        $recommendations_method->setAccessible(true);

        // Test data
        $license = array('id' => 'test_001');
        $pipeline = array('stage1' => array(), 'stage2' => array()); // Incomplete pipeline
        $errors = array('Critical error', 'Security issue');

        $recommendations = $recommendations_method->invoke($report_generator, $license, $pipeline, $errors);

        // Validate recommendations structure
        $required_categories = array('immediate_actions', 'preventive_measures', 'monitoring_suggestions', 'optimization_tips');
        $missing_categories = array_diff($required_categories, array_keys($recommendations));

        if (empty($missing_categories)) {
            // Check if recommendations are generated based on test conditions
            $immediate_actions = $recommendations['immediate_actions'];
            $has_error_recommendation = false;
            $has_pipeline_recommendation = false;

            foreach ($immediate_actions as $action) {
                if (strpos($action, 'validation errors') !== false) {
                    $has_error_recommendation = true;
                }
                if (strpos($action, 'pipeline stages') !== false) {
                    $has_pipeline_recommendation = true;
                }
            }

            if ($has_error_recommendation && $has_pipeline_recommendation) {
                $test_result['success'] = true;
                $test_result['message'] = 'Recommendations generated correctly based on validation state';
                $test_result['details'] = array(
                    'categories_count' => count($recommendations),
                    'immediate_actions' => count($immediate_actions),
                    'total_recommendations' => array_sum(array_map('count', $recommendations))
                );
            } else {
                $test_result['errors'][] = 'Expected recommendations not generated based on test conditions';
                $test_result['message'] = 'Recommendations logic validation failed';
            }
        } else {
            $test_result['errors'][] = 'Missing recommendation categories: ' . implode(', ', $missing_categories);
            $test_result['message'] = 'Recommendations structure validation failed';
        }

    } catch (Exception $e) {
        $test_result['errors'][] = 'Recommendations generation test failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during recommendations testing';
    }

    return $test_result;
}

/**
 * Test configuration management
 */
function test_configuration_management($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        // Get current configuration
        $original_config = $report_generator->get_configuration();

        // Test configuration update
        $new_config = array(
            'enable_pdf_export' => false,
            'retention_days' => 60
        );

        $update_result = $report_generator->update_configuration($new_config);

        if ($update_result) {
            // Verify configuration was updated
            $updated_config = $report_generator->get_configuration();

            $config_updated = ($updated_config['enable_pdf_export'] === false) &&
                             ($updated_config['retention_days'] === 60);

            if ($config_updated) {
                // Restore original configuration
                $report_generator->update_configuration($original_config);

                $test_result['success'] = true;
                $test_result['message'] = 'Configuration management working correctly';
                $test_result['details'] = array(
                    'update_successful' => true,
                    'config_fields' => count($updated_config),
                    'restored' => true
                );
            } else {
                $test_result['errors'][] = 'Configuration values not updated correctly';
                $test_result['message'] = 'Configuration update validation failed';
            }
        } else {
            $test_result['errors'][] = 'Configuration update operation failed';
            $test_result['message'] = 'Configuration update failed';
        }

    } catch (Exception $e) {
        $test_result['errors'][] = 'Configuration management test failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during configuration testing';
    }

    return $test_result;
}

/**
 * Test statistics tracking
 */
function test_statistics_tracking($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        // Get initial statistics
        $initial_stats = $report_generator->get_statistics();

        // Generate a report to increment statistics
        $test_license = array('id' => 'stats_test');
        $test_pipeline = array();
        $test_errors = array();

        $report_generator->generate_validation_report($test_license, $test_pipeline, $test_errors);

        // Get updated statistics
        $updated_stats = $report_generator->get_statistics();

        // Verify statistics were updated
        $reports_incremented = $updated_stats['reports_generated'] > $initial_stats['reports_generated'];

        if ($reports_incremented) {
            $test_result['success'] = true;
            $test_result['message'] = 'Statistics tracking working correctly';
            $test_result['details'] = array(
                'initial_reports' => $initial_stats['reports_generated'],
                'updated_reports' => $updated_stats['reports_generated'],
                'stats_fields' => count($updated_stats)
            );
        } else {
            $test_result['errors'][] = 'Statistics not incremented after report generation';
            $test_result['message'] = 'Statistics tracking failed';
        }

    } catch (Exception $e) {
        $test_result['errors'][] = 'Statistics tracking test failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during statistics testing';
    }

    return $test_result;
}

/**
 * Test dependencies integration
 */
function test_dependencies_integration($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        $module_info = $report_generator->get_module_info();
        $dependencies = $module_info['dependencies'] ?? array();

        // Check if module has the expected dependencies
        $expected_dependencies = array('security.event_logger', 'security.storage_manager', 'security.privacy_manager');
        $missing_dependencies = array_diff($expected_dependencies, $dependencies);

        if (empty($missing_dependencies)) {
            // Test dependency injection methods
            $reflection = new ReflectionClass($report_generator);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $dependency_methods = array_filter($methods, function($method) {
                return strpos($method->getName(), 'set_') === 0;
            });

            if (count($dependency_methods) >= 3) {
                $test_result['success'] = true;
                $test_result['message'] = 'Dependencies integration working correctly';
                $test_result['details'] = array(
                    'expected_dependencies' => $expected_dependencies,
                    'declared_dependencies' => $dependencies,
                    'dependency_methods' => array_map(function($method) { return $method->getName(); }, $dependency_methods)
                );
            } else {
                $test_result['errors'][] = 'Insufficient dependency injection methods found';
                $test_result['message'] = 'Dependency injection methods validation failed';
            }
        } else {
            $test_result['errors'][] = 'Missing dependencies: ' . implode(', ', $missing_dependencies);
            $test_result['message'] = 'Dependencies declaration validation failed';
        }

    } catch (Exception $e) {
        $test_result['errors'][] = 'Dependencies integration test failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during dependencies testing';
    }

    return $test_result;
}

/**
 * Test performance characteristics
 */
function test_performance($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        // Generate multiple reports to test performance
        $report_count = 5;
        $generation_times = array();

        for ($i = 0; $i < $report_count; $i++) {
            $test_start = microtime(true);

            $report_generator->generate_validation_report(
                array('id' => "perf_test_$i"),
                array('stage1' => array(), 'stage2' => array()),
                array('test error')
            );

            $generation_times[] = (microtime(true) - $test_start) * 1000; // Convert to milliseconds
        }

        $total_time = microtime(true) - $start_time;
        $memory_used = memory_get_usage() - $start_memory;
        $average_time = array_sum($generation_times) / count($generation_times);

        // Performance criteria
        $max_average_time = 100; // 100ms
        $max_memory_per_report = 1048576; // 1MB

        $performance_acceptable = ($average_time <= $max_average_time) &&
                                 ($memory_used <= ($max_memory_per_report * $report_count));

        if ($performance_acceptable) {
            $test_result['success'] = true;
            $test_result['message'] = 'Performance characteristics within acceptable limits';
        } else {
            $test_result['message'] = 'Performance characteristics exceed acceptable limits';
        }

        $test_result['details'] = array(
            'reports_generated' => $report_count,
            'total_time_ms' => round($total_time * 1000, 2),
            'average_time_ms' => round($average_time, 2),
            'memory_used_mb' => round($memory_used / 1024 / 1024, 2),
            'memory_per_report_kb' => round(($memory_used / $report_count) / 1024, 2),
            'acceptable_performance' => $performance_acceptable
        );

    } catch (Exception $e) {
        $test_result['errors'][] = 'Performance test failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during performance testing';
    }

    return $test_result;
}

/**
 * Test edge cases handling
 */
function test_edge_cases($report_generator) {
    $test_result = array(
        'success' => false,
        'message' => '',
        'details' => array(),
        'errors' => array()
    );

    try {
        $edge_cases_passed = 0;
        $total_edge_cases = 4;
        $edge_case_results = array();

        // Edge Case 1: Empty data
        try {
            $report = $report_generator->generate_validation_report(array(), array(), array());
            $edge_case_results['empty_data'] = 'PASS - Handled gracefully';
            $edge_cases_passed++;
        } catch (Exception $e) {
            $edge_case_results['empty_data'] = 'FAIL - ' . $e->getMessage();
        }

        // Edge Case 2: Large error array
        try {
            $large_errors = array_fill(0, 1000, 'Test error');
            $report = $report_generator->generate_validation_report(
                array('id' => 'large_test'),
                array(),
                $large_errors
            );
            $edge_case_results['large_errors'] = 'PASS - Handled large error array';
            $edge_cases_passed++;
        } catch (Exception $e) {
            $edge_case_results['large_errors'] = 'FAIL - ' . $e->getMessage();
        }

        // Edge Case 3: Invalid export format
        try {
            $export_result = $report_generator->export_report(array(), 'invalid_format');
            if (!$export_result['success'] && !empty($export_result['errors'])) {
                $edge_case_results['invalid_export'] = 'PASS - Properly rejected invalid format';
                $edge_cases_passed++;
            } else {
                $edge_case_results['invalid_export'] = 'FAIL - Should have rejected invalid format';
            }
        } catch (Exception $e) {
            $edge_case_results['invalid_export'] = 'FAIL - ' . $e->getMessage();
        }

        // Edge Case 4: Configuration with invalid values
        try {
            $original_config = $report_generator->get_configuration();
            $invalid_config = array('retention_days' => -1, 'max_report_size' => 'invalid');
            $report_generator->update_configuration($invalid_config);
            $updated_config = $report_generator->get_configuration();

            // Should either reject invalid values or sanitize them
            if ($updated_config['retention_days'] !== -1) {
                $edge_case_results['invalid_config'] = 'PASS - Invalid values handled';
                $edge_cases_passed++;
            } else {
                $edge_case_results['invalid_config'] = 'FAIL - Invalid values accepted';
            }

            // Restore original config
            $report_generator->update_configuration($original_config);
        } catch (Exception $e) {
            $edge_case_results['invalid_config'] = 'FAIL - ' . $e->getMessage();
        }

        $success_rate = ($edge_cases_passed / $total_edge_cases) * 100;

        if ($edge_cases_passed >= ceil($total_edge_cases * 0.75)) {
            $test_result['success'] = true;
            $test_result['message'] = 'Edge cases handled adequately';
        } else {
            $test_result['message'] = 'Some edge cases not handled properly';
        }

        $test_result['details'] = array(
            'edge_cases_passed' => $edge_cases_passed,
            'total_edge_cases' => $total_edge_cases,
            'success_rate' => round($success_rate, 1) . '%',
            'results' => $edge_case_results
        );

    } catch (Exception $e) {
        $test_result['errors'][] = 'Edge cases test failed: ' . $e->getMessage();
        $test_result['message'] = 'Exception during edge cases testing';
    }

    return $test_result;
}
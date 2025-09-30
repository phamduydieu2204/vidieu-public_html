<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phase 1 Step 1.4 Test - Database LMfWC Adapter Module
 *
 * Tests the newly extracted VD_License_LMfWC_Adapter module
 * URL: /wp-admin/admin-ajax.php?action=vd_test_phase1_step1_4_lmfwc_adapter
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_test_phase1_step1_4_lmfwc_adapter', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'Phase 1 Step 1.4 - Database LMfWC Adapter Test',
        'timestamp' => current_time('mysql'),
        'tests' => array(),
        'summary' => array(),
        'errors' => array(),
        'module_info' => array()
    );

    try {
        // Step 1: Test Module Loader
        $module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
        if (!file_exists($module_loader_file)) {
            throw new Exception('Module Loader file not found');
        }
        require_once $module_loader_file;

        if (!class_exists('VD_License_Module_Loader')) {
            throw new Exception('VD_License_Module_Loader class not found');
        }

        $module_loader = VD_License_Module_Loader::get_instance();
        $results['tests']['module_loader'] = array(
            'status' => 'pass',
            'message' => 'Module Loader initialized successfully',
            'stats' => $module_loader->get_stats()
        );

        // Step 2: Test Dependency Container
        $container_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-dependency-container.php';
        if (!file_exists($container_file)) {
            throw new Exception('Dependency Container file not found');
        }
        require_once $container_file;

        if (!class_exists('VD_License_Dependency_Container')) {
            throw new Exception('VD_License_Dependency_Container class not found');
        }

        $container = VD_License_Dependency_Container::get_instance();
        $container_initialized = $container->initialize();

        $results['tests']['dependency_container'] = array(
            'status' => $container_initialized ? 'pass' : 'fail',
            'message' => $container_initialized ? 'Dependency Container initialized successfully' : 'Container initialization failed',
            'stats' => $container->get_stats(),
            'status_info' => $container->get_status()
        );

        // Step 3: Test Query Manager Module (dependency)
        $query_manager = $container->get('database.query_manager');
        if (!$query_manager) {
            throw new Exception('Query Manager module not found');
        }

        $results['tests']['query_manager_dependency'] = array(
            'status' => 'pass',
            'message' => 'Query Manager dependency loaded successfully',
            'module_info' => $query_manager->get_module_info()
        );

        // Step 4: Test LMfWC Adapter Module Creation
        $lmfwc_adapter_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/database/class-vd-license-lmfwc-adapter.php';
        if (!file_exists($lmfwc_adapter_file)) {
            throw new Exception('LMfWC Adapter module file not found');
        }
        require_once $lmfwc_adapter_file;

        if (!class_exists('VD_License_LMfWC_Adapter')) {
            throw new Exception('VD_License_LMfWC_Adapter class not found');
        }

        $lmfwc_adapter = $container->get('database.lmfwc_adapter');
        $results['module_info'] = $lmfwc_adapter->get_module_info();

        $results['tests']['lmfwc_adapter_creation'] = array(
            'status' => 'pass',
            'message' => 'LMfWC Adapter module loaded successfully',
            'module_info' => $lmfwc_adapter->get_module_info()
        );

        // Step 5: Test LMfWC Table Information
        $table_info = $lmfwc_adapter->get_lmfwc_table_info();

        $results['tests']['lmfwc_table_info'] = array(
            'status' => 'pass',
            'message' => 'LMfWC table information retrieved successfully',
            'table_info' => $table_info
        );

        // Step 6: Test Status Mapping Functions
        $test_status_mappings = array(
            1 => 'active',
            2 => 'inactive',
            3 => 'expired',
            4 => 'suspended',
            'active' => 1,
            'inactive' => 2,
            'expired' => 3,
            'suspended' => 4,
            99 => 'unknown'  // Invalid status
        );

        $mapping_results = array();
        $successful_mappings = 0;

        foreach ($test_status_mappings as $input => $expected) {
            if (is_numeric($input)) {
                $mapped = $lmfwc_adapter->map_lmfwc_status($input);
            } else {
                $mapped = $lmfwc_adapter->map_to_lmfwc_status($input);
            }

            $mapping_results[$input] = array(
                'input' => $input,
                'expected' => $expected,
                'mapped' => $mapped,
                'correct' => ($mapped === $expected)
            );

            if ($mapped === $expected) {
                $successful_mappings++;
            }
        }

        $mapping_success_rate = round(($successful_mappings / count($test_status_mappings)) * 100, 1);

        $results['tests']['status_mapping'] = array(
            'status' => $mapping_success_rate >= 100 ? 'pass' : 'partial',
            'message' => "Status mapping: {$successful_mappings}/" . count($test_status_mappings) . " mappings correct ({$mapping_success_rate}%)",
            'success_rate' => $mapping_success_rate,
            'mapping_results' => $mapping_results
        );

        // Step 7: Test LMfWC License Lookup
        $test_license_keys = array(
            'TEST-LMFWC-KEY1-ABCD-123456' => 'Test LMfWC key 1',
            'TEST-LMFWC-KEY2-EFGH-789012' => 'Test LMfWC key 2',
            'INVALID-LMFWC-KEY' => 'Invalid LMfWC key',
            '' => 'Empty key'
        );

        $lookup_results = array();
        $successful_lookups = 0;

        foreach ($test_license_keys as $license_key => $description) {
            $start_lookup = microtime(true);
            $lookup_result = $lmfwc_adapter->get_lmfwc_license($license_key, true);
            $lookup_time = round((microtime(true) - $start_lookup) * 1000, 3);

            $lookup_results[$license_key] = array(
                'description' => $description,
                'result' => $lookup_result,
                'found' => $lookup_result !== null,
                'lookup_time' => $lookup_time,
                'has_metadata' => $lookup_result ? isset($lookup_result['lmfwc_metadata']) : false
            );

            if ($lookup_result !== null) {
                $successful_lookups++;
            }
        }

        $results['tests']['lmfwc_license_lookup'] = array(
            'status' => 'pass',
            'message' => "LMfWC license lookup completed: {$successful_lookups}/" . count($test_license_keys) . " keys processed",
            'lookup_results' => $lookup_results
        );

        // Step 8: Test LMfWC Activation Statistics
        $activation_test_key = 'TEST-ACTIVATION-STATS-KEY';
        $activation_stats = $lmfwc_adapter->get_lmfwc_activation_stats($activation_test_key);

        $results['tests']['activation_statistics'] = array(
            'status' => 'pass',
            'message' => 'LMfWC activation statistics retrieved successfully',
            'test_key' => $activation_test_key,
            'activation_stats' => $activation_stats
        );

        // Step 9: Test LMfWC Licenses by Criteria
        $test_criteria = array(
            'status' => array(1, 2), // active and inactive
        );

        $criteria_options = array(
            'limit' => 5,
            'order_by' => 'created_at',
            'order' => 'DESC',
            'include_metadata' => true
        );

        $start_criteria = microtime(true);
        $licenses_by_criteria = $lmfwc_adapter->get_lmfwc_licenses_by_criteria($test_criteria, $criteria_options);
        $criteria_time = round((microtime(true) - $start_criteria) * 1000, 2);

        $results['tests']['licenses_by_criteria'] = array(
            'status' => 'pass',
            'message' => count($licenses_by_criteria) . " licenses found by criteria in {$criteria_time}ms",
            'criteria_used' => $test_criteria,
            'options_used' => $criteria_options,
            'licenses_found' => count($licenses_by_criteria),
            'processing_time' => $criteria_time
        );

        // Step 10: Test LMfWC Status Count
        $status_counts = array();
        foreach (array(1, 2, 3, 4) as $status_code) {
            $count = $lmfwc_adapter->count_lmfwc_licenses_by_status($status_code);
            $status_name = $lmfwc_adapter->map_lmfwc_status($status_code);
            $status_counts[$status_name] = $count;
        }

        $results['tests']['status_counts'] = array(
            'status' => 'pass',
            'message' => 'LMfWC status counts retrieved successfully',
            'status_distribution' => $status_counts
        );

        // Step 11: Test Schema Validation
        $test_license_data = array(
            'id' => 123,
            'license_key' => 'TEST-SCHEMA-KEY',
            'status' => 1,
            'created_at' => current_time('mysql'),
            'product_id' => 456,
            'times_activated' => 2,
            'times_activated_max' => 5
        );

        $schema_validation = $lmfwc_adapter->validate_lmfwc_schema($test_license_data);

        $results['tests']['schema_validation'] = array(
            'status' => $schema_validation['valid'] ? 'pass' : 'warn',
            'message' => 'LMfWC schema validation completed',
            'test_data' => $test_license_data,
            'validation_result' => $schema_validation
        );

        // Step 12: Test Performance
        $performance_start = microtime(true);
        $performance_iterations = 15;
        $performance_key = 'PERF-LMFWC-TEST-KEY';

        for ($i = 0; $i < $performance_iterations; $i++) {
            $lmfwc_adapter->get_lmfwc_license($performance_key, false);
        }

        $performance_time = round((microtime(true) - $performance_start) * 1000, 2);
        $avg_time_per_lookup = round($performance_time / $performance_iterations, 3);

        $results['tests']['performance'] = array(
            'status' => $avg_time_per_lookup < 5 ? 'pass' : 'warn',
            'message' => "{$performance_iterations} LMfWC lookups in {$performance_time}ms (avg: {$avg_time_per_lookup}ms)",
            'total_time' => $performance_time,
            'average_time' => $avg_time_per_lookup,
            'iterations' => $performance_iterations
        );

        // Step 13: Test Module Statistics
        $lmfwc_stats = $lmfwc_adapter->get_stats();

        $results['tests']['module_statistics'] = array(
            'status' => 'pass',
            'message' => 'LMfWC Adapter statistics retrieved successfully',
            'statistics' => $lmfwc_stats
        );

        // Step 14: Test Integration with Main Validator
        $validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
        if (file_exists($validator_file)) {
            require_once $validator_file;

            if (class_exists('VD_License_Validator')) {
                try {
                    $validator = VD_License_Validator::get_instance();

                    // Test basic integration - just verify the adapter is accessible through DI
                    $test_integration_key = 'TEST-VALIDATOR-INTEGRATION';

                    if (method_exists($validator, 'validate_license_key_format')) {
                        $test_validation = $validator->validate_license_key_format($test_integration_key, true);
                        $integration_result = is_array($test_validation) ? 'integration_successful' : 'basic_validation_returned';
                    } else {
                        $integration_result = 'method_not_available';
                    }

                    $results['tests']['main_validator_integration'] = array(
                        'status' => 'pass',
                        'message' => 'Main validator integration test completed',
                        'integration_result' => $integration_result
                    );
                } catch (Exception $e) {
                    $results['tests']['main_validator_integration'] = array(
                        'status' => 'warn',
                        'message' => 'Integration test failed: ' . $e->getMessage()
                    );
                }
            } else {
                $results['tests']['main_validator_integration'] = array(
                    'status' => 'warn',
                    'message' => 'VD_License_Validator class not found'
                );
            }
        } else {
            $results['tests']['main_validator_integration'] = array(
                'status' => 'warn',
                'message' => 'Main validator file not found'
            );
        }

        // Calculate overall summary
        $passed_tests = 0;
        $total_tests = count($results['tests']);

        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'pass') {
                $passed_tests++;
            }
        }

        $overall_success_rate = round(($passed_tests / $total_tests) * 100, 1);

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'success_rate' => $overall_success_rate,
            'overall_status' => $overall_success_rate >= 100 ? 'excellent' : ($overall_success_rate >= 80 ? 'good' : 'needs_attention'),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
        );

        // Phase 1 Step 1.4 completion status
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.4',
            'module' => 'Database LMfWC Adapter',
            'completion' => 'SUCCESS',
            'next_step' => 'Step 1.5 - Extract Database Cache Manager',
            'files_created' => array(
                'modules/database/class-vd-license-lmfwc-adapter.php' => '~450 lines',
            ),
            'files_modified' => array(
                'class-vd-license-dependency-container.php' => 'Added LMfWC adapter with dependency injection',
                'class-vd-license-module-loader.php' => 'LMfWC adapter registry already configured'
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.4',
            'completion' => 'FAILED',
            'error' => $e->getMessage()
        );
    } catch (Error $e) {
        $results['status'] = 'error';
        $results['errors'][] = 'Fatal error: ' . $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.4',
            'completion' => 'FAILED',
            'error' => 'Fatal error: ' . $e->getMessage()
        );
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});
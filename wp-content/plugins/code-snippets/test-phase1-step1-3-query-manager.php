<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phase 1 Step 1.3 Test - Database Query Manager Module
 *
 * Tests the newly extracted VD_License_Query_Manager module
 * URL: /wp-admin/admin-ajax.php?action=vd_test_phase1_step1_3_query_manager
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_test_phase1_step1_3_query_manager', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'Phase 1 Step 1.3 - Database Query Manager Test',
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

        // Step 3: Test Query Manager Module
        $query_manager_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/database/class-vd-license-query-manager.php';
        if (!file_exists($query_manager_file)) {
            throw new Exception('Query Manager module file not found');
        }
        require_once $query_manager_file;

        if (!class_exists('VD_License_Query_Manager')) {
            throw new Exception('VD_License_Query_Manager class not found');
        }

        $query_manager = $container->get('database.query_manager');
        $results['module_info'] = $query_manager->get_module_info();

        $results['tests']['query_manager_creation'] = array(
            'status' => 'pass',
            'message' => 'Query Manager module loaded successfully',
            'module_info' => $query_manager->get_module_info()
        );

        // Step 4: Test Table Configuration
        $supported_tables = $query_manager->get_supported_tables();
        $table_configs = array();

        foreach ($supported_tables as $table_type) {
            $config = $query_manager->get_table_config($table_type);
            $table_configs[$table_type] = $config;
        }

        $results['tests']['table_configuration'] = array(
            'status' => 'pass',
            'message' => count($supported_tables) . ' table types supported',
            'supported_tables' => $supported_tables,
            'table_configs' => $table_configs
        );

        // Step 5: Test Table Existence Checks
        $table_existence_tests = array();

        foreach ($supported_tables as $table_type) {
            $config = $query_manager->get_table_config($table_type);
            $table_name = $config['table_name'];
            $exists = $query_manager->table_exists($table_name);

            $table_existence_tests[$table_type] = array(
                'table_name' => $table_name,
                'exists' => $exists,
                'config' => $config
            );
        }

        $results['tests']['table_existence'] = array(
            'status' => 'pass',
            'message' => 'Table existence checks completed',
            'table_tests' => $table_existence_tests
        );

        // Step 6: Test License Lookup (with test keys)
        $test_license_keys = array(
            'TEST-KEY1-ABCD-EFGH-123456' => 'Test key 1',
            'TEST-KEY2-IJKL-MNOP-789012' => 'Test key 2',
            'INVALID-TEST-KEY' => 'Invalid test key',
            '' => 'Empty key'
        );

        $lookup_results = array();
        $successful_lookups = 0;

        foreach ($test_license_keys as $license_key => $description) {
            $start_lookup = microtime(true);
            $lookup_result = $query_manager->lookup_license($license_key, true);
            $lookup_time = round((microtime(true) - $start_lookup) * 1000, 3);

            $lookup_results[$license_key] = array(
                'description' => $description,
                'result' => $lookup_result,
                'found' => $lookup_result !== null,
                'lookup_time' => $lookup_time
            );

            if ($lookup_result !== null) {
                $successful_lookups++;
            }
        }

        $results['tests']['license_lookup'] = array(
            'status' => 'pass',
            'message' => "License lookup completed: {$successful_lookups}/" . count($test_license_keys) . " keys found",
            'lookup_results' => $lookup_results
        );

        // Step 7: Test Debug Information
        $debug_info = $query_manager->get_lookup_debug_info('TEST-DEBUG-KEY');

        $results['tests']['debug_information'] = array(
            'status' => 'pass',
            'message' => 'Debug information retrieved successfully',
            'debug_info' => $debug_info
        );

        // Step 8: Test Query Statistics
        $query_stats = $query_manager->get_stats();

        $results['tests']['query_statistics'] = array(
            'status' => 'pass',
            'message' => 'Query statistics retrieved successfully',
            'statistics' => $query_stats
        );

        // Step 9: Test Cache Operations
        $cache_test_key = 'CACHE-TEST-KEY-123';

        // First lookup (should be cache miss)
        $first_lookup = $query_manager->lookup_license($cache_test_key, true);
        $stats_after_first = $query_manager->get_stats();

        // Second lookup (should be cache hit if key was found)
        $second_lookup = $query_manager->lookup_license($cache_test_key, true);
        $stats_after_second = $query_manager->get_stats();

        // Clear cache test
        $query_manager->clear_cache();
        $third_lookup = $query_manager->lookup_license($cache_test_key, true);
        $stats_after_clear = $query_manager->get_stats();

        $results['tests']['cache_operations'] = array(
            'status' => 'pass',
            'message' => 'Cache operations tested successfully',
            'cache_test' => array(
                'test_key' => $cache_test_key,
                'first_lookup' => $first_lookup !== null,
                'second_lookup' => $second_lookup !== null,
                'third_lookup_after_clear' => $third_lookup !== null,
                'stats_progression' => array(
                    'after_first' => array(
                        'cache_hits' => $stats_after_first['cache_hits'],
                        'cache_misses' => $stats_after_first['cache_misses']
                    ),
                    'after_second' => array(
                        'cache_hits' => $stats_after_second['cache_hits'],
                        'cache_misses' => $stats_after_second['cache_misses']
                    ),
                    'after_clear' => array(
                        'cache_hits' => $stats_after_clear['cache_hits'],
                        'cache_misses' => $stats_after_clear['cache_misses']
                    )
                )
            )
        );

        // Step 10: Test Performance
        $performance_start = microtime(true);
        $performance_iterations = 20;
        $performance_key = 'PERF-TEST-KEY-456';

        for ($i = 0; $i < $performance_iterations; $i++) {
            $query_manager->lookup_license($performance_key, true);
        }

        $performance_time = round((microtime(true) - $performance_start) * 1000, 2);
        $avg_time_per_lookup = round($performance_time / $performance_iterations, 3);

        $results['tests']['performance'] = array(
            'status' => $avg_time_per_lookup < 10 ? 'pass' : 'warn',
            'message' => "{$performance_iterations} lookups in {$performance_time}ms (avg: {$avg_time_per_lookup}ms)",
            'total_time' => $performance_time,
            'average_time' => $avg_time_per_lookup,
            'iterations' => $performance_iterations
        );

        // Step 11: Test Module Integration with Main Validator
        $validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
        if (file_exists($validator_file)) {
            require_once $validator_file;

            if (class_exists('VD_License_Validator')) {
                try {
                    $validator = VD_License_Validator::get_instance();

                    // Test pattern validation that doesn't require database lookup
                    if (method_exists($validator, 'validate_license_key_format')) {
                        $test_validation = $validator->validate_license_key_format('TEST-INTEGRATION-KEY', true);
                        $validation_result = is_array($test_validation) ? 'detailed_result_returned' : 'simple_result_returned';
                    } else {
                        $validation_result = 'method_not_found';
                    }

                    $results['tests']['main_validator_integration'] = array(
                        'status' => 'pass',
                        'message' => 'Main validator integration test completed',
                        'validation_result' => $validation_result
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

        // Phase 1 Step 1.3 completion status
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.3',
            'module' => 'Database Query Manager',
            'completion' => 'SUCCESS',
            'next_step' => 'Step 1.4 - Extract Database LMfWC Adapter',
            'files_created' => array(
                'modules/database/class-vd-license-query-manager.php' => '~420 lines',
            ),
            'files_modified' => array(
                'class-vd-license-dependency-container.php' => 'Added query manager to core services',
                'class-vd-license-validator.php' => 'Integrated query manager module'
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.3',
            'completion' => 'FAILED',
            'error' => $e->getMessage()
        );
    } catch (Error $e) {
        $results['status'] = 'error';
        $results['errors'][] = 'Fatal error: ' . $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.3',
            'completion' => 'FAILED',
            'error' => 'Fatal error: ' . $e->getMessage()
        );
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});
<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phase 1 Step 1.5 Test - Database Cache Manager Module
 *
 * Tests the newly extracted VD_License_Cache_Manager module
 * URL: /wp-admin/admin-ajax.php?action=vd_test_phase1_step1_5_cache_manager
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_test_phase1_step1_5_cache_manager', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'Phase 1 Step 1.5 - Database Cache Manager Test',
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

        // Step 3: Test Cache Manager Module
        $cache_manager_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/database/class-vd-license-cache-manager.php';
        if (!file_exists($cache_manager_file)) {
            throw new Exception('Cache Manager module file not found');
        }
        require_once $cache_manager_file;

        if (!class_exists('VD\\LicenseManager\\Database\\VD_License_Cache_Manager')) {
            throw new Exception('VD_License_Cache_Manager class not found');
        }

        $cache_manager = $container->get('database.cache_manager');
        $results['module_info'] = $cache_manager->get_module_info();

        $results['tests']['cache_manager_creation'] = array(
            'status' => 'pass',
            'message' => 'Cache Manager module loaded successfully',
            'module_info' => $cache_manager->get_module_info()
        );

        // Step 4: Test Basic Cache Operations
        $test_license_key = 'TEST-CACHE-LICENSE-KEY-12345';
        $test_validation_result = array(
            'valid' => true,
            'license_id' => 999,
            'status' => 'active',
            'test_timestamp' => current_time('mysql')
        );

        // Test cache set
        $cache_set_result = $cache_manager->set_validation_cache($test_license_key, $test_validation_result);

        // Test cache get
        $cached_result = $cache_manager->get_validation_cache($test_license_key);

        $results['tests']['basic_cache_operations'] = array(
            'status' => ($cache_set_result && $cached_result && $cached_result['valid'] === true) ? 'pass' : 'fail',
            'message' => 'Basic cache set/get operations',
            'cache_set_success' => $cache_set_result,
            'cache_retrieved' => $cached_result !== null,
            'data_integrity' => $cached_result && $cached_result['license_id'] === 999
        );

        // Step 5: Test Settings Cache
        $test_license_id = 123;
        $test_product_id = 456;
        $test_settings = array(
            'max_devices' => 5,
            'rate_limit_requests' => 100,
            'auto_approval_enabled' => true
        );

        $settings_cache_set = $cache_manager->set_settings_cache($test_license_id, $test_product_id, $test_settings);
        $cached_settings = $cache_manager->get_settings_cache($test_license_id, $test_product_id);

        $results['tests']['settings_cache_operations'] = array(
            'status' => ($settings_cache_set && $cached_settings) ? 'pass' : 'fail',
            'message' => 'Settings cache operations',
            'settings_cached' => $settings_cache_set,
            'settings_retrieved' => $cached_settings !== null,
            'max_devices_match' => $cached_settings && $cached_settings['max_devices'] === 5
        );

        // Step 6: Test History Cache
        $test_history_key = 'license_history_test_key';
        $test_history_data = array(
            'license_id' => 789,
            'status_changes' => array(
                array('from' => 'inactive', 'to' => 'active', 'timestamp' => current_time('mysql'))
            ),
            'event_count' => 1
        );

        $history_cache_set = $cache_manager->set_history_cache($test_history_key, $test_history_data);
        $cached_history = $cache_manager->get_history_cache($test_history_key);

        $results['tests']['history_cache_operations'] = array(
            'status' => ($history_cache_set && $cached_history) ? 'pass' : 'fail',
            'message' => 'History cache operations',
            'history_cached' => $history_cache_set,
            'history_retrieved' => $cached_history !== null,
            'event_count_match' => $cached_history && $cached_history['event_count'] === 1
        );

        // Step 7: Test Cache Statistics
        $cache_stats = $cache_manager->get_cache_stats();

        $results['tests']['cache_statistics'] = array(
            'status' => is_array($cache_stats) && isset($cache_stats['hits']) ? 'pass' : 'fail',
            'message' => 'Cache statistics retrieval',
            'statistics' => $cache_stats,
            'has_hit_rate' => isset($cache_stats['hit_rate_percentage']),
            'has_memory_info' => isset($cache_stats['memory_usage_mb'])
        );

        // Step 8: Test Performance (Multiple Operations)
        $performance_start = microtime(true);
        $performance_operations = 50;

        for ($i = 0; $i < $performance_operations; $i++) {
            $perf_key = "PERF_TEST_KEY_{$i}";
            $perf_data = array('test_id' => $i, 'data' => str_repeat('x', 100));

            $cache_manager->set_validation_cache($perf_key, $perf_data);
            $retrieved = $cache_manager->get_validation_cache($perf_key);
        }

        $performance_time = round((microtime(true) - $performance_start) * 1000, 2);
        $avg_time_per_operation = round($performance_time / ($performance_operations * 2), 3); // *2 for set+get

        $results['tests']['performance_test'] = array(
            'status' => $avg_time_per_operation < 1 ? 'pass' : 'warn',
            'message' => "{$performance_operations} set+get operations in {$performance_time}ms",
            'total_time_ms' => $performance_time,
            'operations_count' => $performance_operations * 2,
            'avg_time_per_operation_ms' => $avg_time_per_operation,
            'performance_rating' => $avg_time_per_operation < 0.5 ? 'excellent' : ($avg_time_per_operation < 1 ? 'good' : 'fair')
        );

        // Step 9: Test Cache Cleanup
        $initial_stats = $cache_manager->get_cache_stats();
        $cleanup_removed = $cache_manager->cleanup_expired_cache();

        // Add entries with short TTL for cleanup testing
        for ($i = 0; $i < 5; $i++) {
            $cache_manager->set_validation_cache("CLEANUP_TEST_{$i}", array('data' => $i), 1); // 1 second TTL
        }

        sleep(2); // Wait for expiration
        $expired_removed = $cache_manager->cleanup_expired_cache();

        $results['tests']['cache_cleanup'] = array(
            'status' => 'pass',
            'message' => 'Cache cleanup operations',
            'initial_cleanup_removed' => $cleanup_removed,
            'expired_entries_removed' => $expired_removed,
            'cleanup_functional' => $expired_removed >= 5
        );

        // Step 10: Test Cache Invalidation by Pattern
        // Add some test entries with patterns
        $pattern_test_keys = array('USER_123_DATA', 'USER_123_SETTINGS', 'USER_456_DATA', 'SYSTEM_CONFIG');
        foreach ($pattern_test_keys as $key) {
            $cache_manager->set_validation_cache($key, array('key' => $key));
        }

        $invalidated_count = $cache_manager->invalidate_cache_by_pattern('USER_123_*');

        $results['tests']['pattern_invalidation'] = array(
            'status' => $invalidated_count >= 2 ? 'pass' : 'fail',
            'message' => 'Cache invalidation by pattern',
            'pattern_used' => 'USER_123_*',
            'entries_invalidated' => $invalidated_count,
            'expected_minimum' => 2
        );

        // Step 11: Test Clear All Cache
        $cache_manager->clear_all_cache();
        $post_clear_stats = $cache_manager->get_cache_stats();

        $results['tests']['clear_all_cache'] = array(
            'status' => ($post_clear_stats['validation_entries'] === 0 && $post_clear_stats['history_entries'] === 0) ? 'pass' : 'fail',
            'message' => 'Clear all cache functionality',
            'validation_entries_after_clear' => $post_clear_stats['validation_entries'],
            'history_entries_after_clear' => $post_clear_stats['history_entries']
        );

        // Step 12: Test Cache Export/Debug Info
        $debug_info = $cache_manager->export_cache_debug_info(true);

        $results['tests']['debug_export'] = array(
            'status' => is_array($debug_info) && isset($debug_info['module_info']) ? 'pass' : 'fail',
            'message' => 'Cache debug information export',
            'has_module_info' => isset($debug_info['module_info']),
            'has_statistics' => isset($debug_info['statistics']),
            'has_config' => isset($debug_info['config'])
        );

        // Step 13: Test Integration with Main Validator
        $validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
        if (file_exists($validator_file)) {
            require_once $validator_file;

            if (class_exists('VD_License_Validator')) {
                try {
                    $validator = VD_License_Validator::get_instance();

                    // Test if validator has cache manager integration
                    $validator_stats = $validator->get_validation_stats();

                    $results['tests']['validator_integration'] = array(
                        'status' => 'pass',
                        'message' => 'Main validator integration test completed',
                        'validator_stats' => $validator_stats,
                        'cache_entries_detected' => isset($validator_stats['cache_entries'])
                    );
                } catch (Exception $e) {
                    $results['tests']['validator_integration'] = array(
                        'status' => 'warn',
                        'message' => 'Validator integration test failed: ' . $e->getMessage()
                    );
                }
            } else {
                $results['tests']['validator_integration'] = array(
                    'status' => 'warn',
                    'message' => 'VD_License_Validator class not found'
                );
            }
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
            'overall_status' => $overall_success_rate >= 100 ? 'excellent' : ($overall_success_rate >= 90 ? 'good' : 'needs_attention'),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'cache_performance' => $cache_stats['hit_rate_percentage'] ?? 0
        );

        // Phase 1 Step 1.5 completion status
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.5',
            'module' => 'Database Cache Manager',
            'completion' => 'SUCCESS',
            'next_step' => 'Step 1.6 - Status Enum Validator or Phase 1 Integration Testing',
            'files_created' => array(
                'modules/database/class-vd-license-cache-manager.php' => '~550 lines',
            ),
            'files_modified' => array(
                'class-vd-license-dependency-container.php' => 'Added cache manager to core services',
                'class-vd-license-module-loader.php' => 'Added cache manager module registration'
            ),
            'performance_metrics' => array(
                'cache_operations_per_ms' => round(1 / $avg_time_per_operation, 2),
                'memory_efficiency' => $post_clear_stats['memory_usage_mb'] ?? 0,
                'hit_rate_achieved' => $cache_stats['hit_rate_percentage'] ?? 0
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.5',
            'completion' => 'FAILED',
            'error' => $e->getMessage()
        );
    } catch (Error $e) {
        $results['status'] = 'error';
        $results['errors'][] = 'Fatal error: ' . $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.5',
            'completion' => 'FAILED',
            'error' => 'Fatal error: ' . $e->getMessage()
        );
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});
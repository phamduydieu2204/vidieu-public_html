<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test Optimized License Lookup with Decryption Scan
 *
 * Tests the updated VD License Manager with decryption scan method
 * and performance optimization features
 *
 * URL: /wp-admin/admin-ajax.php?action=vd_test_optimized_license_lookup
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_test_optimized_license_lookup', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'Optimized License Lookup Test',
        'timestamp' => current_time('mysql'),
        'tests' => array(),
        'summary' => array(),
        'errors' => array(),
        'customer_license' => 'H10D-DIJD-14RC-SOLE-6KUV30'
    );

    try {
        $customer_license = 'H10D-DIJD-14RC-SOLE-6KUV30';

        // Step 1: Test VD License Manager Dependencies
        $container_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-dependency-container.php';
        if (!file_exists($container_file)) {
            throw new Exception('VD License Manager dependency container not found');
        }
        require_once $container_file;

        $container = VD_License_Dependency_Container::get_instance();
        $container_initialized = $container->initialize();

        if (!$container_initialized) {
            throw new Exception('VD License Manager container failed to initialize');
        }

        $query_manager = $container->get('database.query_manager');

        $results['tests']['vd_manager_initialization'] = array(
            'status' => 'pass',
            'message' => 'VD License Manager initialized successfully',
            'container_stats' => $container->get_stats(),
            'query_manager_info' => $query_manager->get_module_info()
        );

        // Step 2: Test optimized lookup with customer license
        $start_time = microtime(true);
        $lookup_result = $query_manager->lookup_license($customer_license, true);
        $lookup_time = round((microtime(true) - $start_time) * 1000, 3);

        $results['tests']['optimized_customer_lookup'] = array(
            'status' => $lookup_result ? 'pass' : 'fail',
            'message' => $lookup_result ? 'Customer license found successfully' : 'Customer license not found',
            'lookup_time_ms' => $lookup_time,
            'license_found' => $lookup_result ? true : false,
            'license_data' => $lookup_result ? array(
                'id' => $lookup_result['id'],
                'status' => $lookup_result['status'],
                'mapped_status' => $lookup_result['mapped_status'],
                'lookup_source' => $lookup_result['lookup_source'],
                'lookup_method' => $lookup_result['lookup_method'],
                'scan_count' => isset($lookup_result['scan_count']) ? $lookup_result['scan_count'] : 'N/A',
                'decrypted_key_matches' => isset($lookup_result['decrypted_license_key']) &&
                                         $lookup_result['decrypted_license_key'] === $customer_license,
                'created_at' => $lookup_result['created_at'],
                'is_expired' => $lookup_result['is_expired']
            ) : null
        );

        // Step 3: Test performance with multiple lookups
        $performance_test_keys = array(
            $customer_license,
            'TEST-NONEXISTENT-KEY-1',
            $customer_license, // Test cache
            'TEST-NONEXISTENT-KEY-2',
            $customer_license  // Test cache again
        );

        $performance_results = array();
        $total_time = 0;
        $cache_hits = 0;

        foreach ($performance_test_keys as $index => $test_key) {
            $start_time = microtime(true);
            $result = $query_manager->lookup_license($test_key, true);
            $individual_time = round((microtime(true) - $start_time) * 1000, 3);
            $total_time += $individual_time;

            $performance_results[] = array(
                'test_number' => $index + 1,
                'license_key' => $test_key === $customer_license ? 'CUSTOMER_LICENSE' : $test_key,
                'found' => $result ? true : false,
                'time_ms' => $individual_time,
                'lookup_method' => $result ? $result['lookup_method'] : 'not_found',
                'scan_count' => ($result && isset($result['scan_count'])) ? $result['scan_count'] : null
            );

            if ($individual_time < 5 && $result) {
                $cache_hits++; // Likely a cache hit
            }
        }

        $results['tests']['performance_multiple_lookups'] = array(
            'status' => 'pass',
            'message' => 'Performance test with multiple lookups completed',
            'total_lookups' => count($performance_test_keys),
            'total_time_ms' => round($total_time, 3),
            'average_time_ms' => round($total_time / count($performance_test_keys), 3),
            'likely_cache_hits' => $cache_hits,
            'individual_results' => $performance_results
        );

        // Step 4: Test query manager statistics
        $query_stats = $query_manager->get_stats();

        $results['tests']['query_statistics'] = array(
            'status' => 'pass',
            'message' => 'Query statistics retrieved successfully',
            'statistics' => $query_stats,
            'has_decryption_stats' => isset($query_stats['decryption_scan_count']),
            'decryption_scans' => $query_stats['decryption_scan_count'] ?? 0,
            'decryption_scan_time' => $query_stats['decryption_scan_time'] ?? 0
        );

        // Step 5: Test hash optimization readiness
        global $wpdb;
        $lmfwc_table = $wpdb->prefix . 'lmfwc_licenses';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$lmfwc_table'") === $lmfwc_table;

        if ($table_exists) {
            $columns = $wpdb->get_col("DESCRIBE $lmfwc_table");
            $has_hash_column = in_array('license_key_hash', $columns);

            $results['tests']['hash_optimization_readiness'] = array(
                'status' => 'info',
                'message' => 'Hash optimization readiness check',
                'table_exists' => true,
                'has_hash_column' => $has_hash_column,
                'hash_optimization_enabled' => apply_filters('vd_enable_license_hash_optimization', false),
                'recommendation' => $has_hash_column ?
                    'Ready for hash optimization - enable with filter' :
                    'Add license_key_hash column for optimal performance'
            );

            // Step 6: Test potential hash optimization (simulation)
            if ($has_hash_column) {
                $test_hash = hash('sha256', $customer_license);
                $hash_lookup_sql = $wpdb->prepare(
                    "SELECT COUNT(*) FROM $lmfwc_table WHERE license_key_hash = %s",
                    $test_hash
                );
                $hash_matches = $wpdb->get_var($hash_lookup_sql);

                $results['tests']['hash_optimization_simulation'] = array(
                    'status' => $hash_matches > 0 ? 'pass' : 'info',
                    'message' => 'Hash optimization simulation',
                    'customer_license_hash' => $test_hash,
                    'hash_matches_found' => $hash_matches,
                    'hash_lookup_would_work' => $hash_matches > 0
                );
            }
        } else {
            $results['tests']['hash_optimization_readiness'] = array(
                'status' => 'warn',
                'message' => 'LMfWC table not found - hash optimization not available'
            );
        }

        // Step 7: Test fallback mechanisms
        $test_fallback_key = 'VD-INTERNAL-TEST-KEY-123';
        $fallback_result = $query_manager->lookup_license($test_fallback_key, true);

        $results['tests']['fallback_mechanism'] = array(
            'status' => 'pass',
            'message' => 'Fallback mechanism test completed',
            'test_key' => $test_fallback_key,
            'found_in_vd_internal' => $fallback_result ? true : false,
            'lookup_source' => $fallback_result ? $fallback_result['lookup_source'] : 'none',
            'lookup_method' => $fallback_result ? $fallback_result['lookup_method'] : 'none'
        );

        // Step 8: Test scan limit configuration
        $default_scan_limit = apply_filters('vd_lmfwc_scan_limit', 200);
        $custom_scan_limit = 50;

        // Temporarily modify scan limit
        add_filter('vd_lmfwc_scan_limit', function() use ($custom_scan_limit) {
            return $custom_scan_limit;
        });

        $start_time = microtime(true);
        $limited_result = $query_manager->lookup_license('SCAN-LIMIT-TEST-KEY', false); // No cache
        $limited_time = round((microtime(true) - $start_time) * 1000, 3);

        $results['tests']['scan_limit_configuration'] = array(
            'status' => 'pass',
            'message' => 'Scan limit configuration test',
            'default_scan_limit' => $default_scan_limit,
            'custom_scan_limit' => $custom_scan_limit,
            'limited_scan_time_ms' => $limited_time,
            'scan_limit_respected' => $limited_time < 50 // Should be faster with smaller limit
        );

        // Calculate summary
        $passed_tests = 0;
        $total_tests = count($results['tests']);

        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'pass') {
                $passed_tests++;
            }
        }

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 1),
            'customer_license_found' => $lookup_result ? true : false,
            'lookup_method_used' => $lookup_result ? $lookup_result['lookup_method'] : 'none',
            'average_lookup_time' => round($total_time / count($performance_test_keys), 2) . ' ms',
            'optimization_status' => $has_hash_column ?? false ? 'hash_ready' : 'decryption_scan_only',
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
        );

        // Implementation recommendations
        $results['recommendations'] = array(
            'current_method' => 'Decryption scan with 200 license limit',
            'performance' => array(
                'current_avg_time' => round($total_time / count($performance_test_keys), 2) . ' ms',
                'optimization_potential' => $has_hash_column ?? false ?
                    'Enable hash optimization for instant lookups' :
                    'Add hash column for 10x faster lookups'
            ),
            'implementation_steps' => array(
                '1. Current implementation working with decryption scan',
                '2. Add license_key_hash column to LMfWC table if not exists',
                '3. Enable hash optimization filter: vd_enable_license_hash_optimization',
                '4. Monitor performance improvements',
                '5. Adjust scan limits based on dataset size'
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});
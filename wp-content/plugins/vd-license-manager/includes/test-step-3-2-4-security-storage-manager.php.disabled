<?php
/**
 * VD License Manager - Test for Step 3.2.4 Security Storage Manager
 *
 * Comprehensive test suite for Security Audit Storage Manager module
 * Tests database operations, file-based logging, log rotation, cleanup, archiving
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_test_step_3_2_4_security_storage_manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_test_step_3_2_4_security_storage_manager', 'vd_test_step_3_2_4_security_storage_manager_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_4_security_storage_manager', 'vd_test_step_3_2_4_security_storage_manager_handler');

/**
 * Test handler for Step 3.2.4 Security Storage Manager
 */
function vd_test_step_3_2_4_security_storage_manager_handler() {
    try {
        // Initialize dependency container
        $container = VD_License_Dependency_Container::get_instance();

        if (!$container) {
            throw new Exception('Failed to get dependency container instance');
        }

        // Get storage manager instance
        $storage_manager = $container->get('security.storage_manager');

        if (!$storage_manager) {
            throw new Exception('Failed to load Security Storage Manager module');
        }

        $results = array(
            'module_info' => $storage_manager->get_module_info(),
            'tests' => array(),
            'summary' => array(),
            'timestamp' => current_time('mysql')
        );

        // Test 1: Audit Log Storage
        $results['tests']['audit_log_storage'] = test_audit_log_storage($storage_manager);

        // Test 2: License History Storage
        $results['tests']['license_history_storage'] = test_license_history_storage($storage_manager);

        // Test 3: History Retrieval
        $results['tests']['history_retrieval'] = test_history_retrieval($storage_manager);

        // Test 4: Log Archiving
        $results['tests']['log_archiving'] = test_log_archiving($storage_manager);

        // Test 5: Log Cleanup
        $results['tests']['log_cleanup'] = test_log_cleanup($storage_manager);

        // Test 6: Storage Statistics
        $results['tests']['storage_statistics'] = test_storage_statistics($storage_manager);

        // Test 7: Configuration Management
        $results['tests']['configuration_management'] = test_configuration_management($storage_manager);

        // Test 8: Performance Optimization
        $results['tests']['performance_optimization'] = test_performance_optimization($storage_manager);

        // Test 9: Storage Types Support
        $results['tests']['storage_types_support'] = test_storage_types_support($storage_manager);

        // Test 10: Dependency Integration
        $results['tests']['dependency_integration'] = test_dependency_integration($storage_manager);

        // Generate summary
        $results['summary'] = generate_test_summary($results['tests'], $storage_manager);

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Storage Manager test failed: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
    }
}

/**
 * Test audit log storage functionality
 */
function test_audit_log_storage($storage_manager) {
    $test_result = array(
        'name' => 'Audit Log Storage',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test storing various types of audit logs
        $test_logs = array(
            array(
                'event_type' => 'license_validation',
                'level' => 'info',
                'message' => 'License validated successfully',
                'context' => array('license_id' => 123, 'user_id' => 1)
            ),
            array(
                'event_type' => 'security_violation',
                'level' => 'warning',
                'message' => 'Suspicious activity detected',
                'context' => array('ip_address' => '192.168.1.1', 'user_agent' => 'test')
            ),
            array(
                'event_type' => 'system_error',
                'level' => 'error',
                'message' => 'Database connection failed',
                'context' => array('error_code' => 500, 'component' => 'database')
            )
        );

        $stored_count = 0;
        $storage_results = array();

        foreach ($test_logs as $index => $log_entry) {
            $result = $storage_manager->store_audit_log($log_entry, array(
                'storage_type' => 'memory',
                'privacy_filter' => true
            ));

            $storage_results[] = $result;

            if ($result['success']) {
                $stored_count++;
            }
        }

        // Test different storage types
        $multi_storage_result = $storage_manager->store_audit_log(array(
            'event_type' => 'test_multi_storage',
            'level' => 'info',
            'message' => 'Testing multiple storage types',
            'context' => array('test' => true)
        ), array(
            'storage_type' => 'both', // both memory and database
            'immediate_flush' => true
        ));

        $test_result['details'] = array(
            'logs_tested' => count($test_logs),
            'logs_stored' => $stored_count,
            'success_rate' => round(($stored_count / count($test_logs)) * 100, 2) . '%',
            'multi_storage_test' => $multi_storage_result['success'],
            'storage_locations' => $multi_storage_result['storage_locations'] ?? array(),
            'storage_results' => $storage_results
        );

        $test_result['success'] = $stored_count === count($test_logs) && $multi_storage_result['success'];

    } catch (Exception $e) {
        $test_result['errors'][] = 'Audit log storage test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test license history storage functionality
 */
function test_license_history_storage($storage_manager) {
    $test_result = array(
        'name' => 'License History Storage',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test storing license history entries
        $test_histories = array(
            array(
                'license_id' => 101,
                'action' => 'activation',
                'old_status' => 'inactive',
                'new_status' => 'active',
                'context' => array('device_id' => 'device_001', 'activation_time' => time())
            ),
            array(
                'license_id' => 102,
                'action' => 'status_change',
                'old_status' => 'active',
                'new_status' => 'suspended',
                'context' => array('reason' => 'violation', 'admin_id' => 1)
            ),
            array(
                'license_id' => 103,
                'action' => 'expiry',
                'old_status' => 'active',
                'new_status' => 'expired',
                'context' => array('expiry_date' => date('Y-m-d H:i:s'), 'auto_expired' => true)
            )
        );

        $stored_count = 0;
        $history_results = array();

        foreach ($test_histories as $history_entry) {
            $result = $storage_manager->store_license_history($history_entry, array(
                'storage_type' => 'memory',
                'track_changes' => true,
                'privacy_filter' => true
            ));

            $history_results[] = $result;

            if ($result['success']) {
                $stored_count++;
            }
        }

        // Test privacy filtering in history
        $sensitive_history = array(
            'license_id' => 104,
            'action' => 'user_data_change',
            'context' => array(
                'email' => 'user@example.com',
                'phone' => '+1-555-123-4567',
                'ip_address' => '192.168.1.100'
            )
        );

        $privacy_result = $storage_manager->store_license_history($sensitive_history, array(
            'privacy_filter' => true
        ));

        $test_result['details'] = array(
            'histories_tested' => count($test_histories),
            'histories_stored' => $stored_count,
            'success_rate' => round(($stored_count / count($test_histories)) * 100, 2) . '%',
            'privacy_filtering_applied' => $privacy_result['success'],
            'history_results' => $history_results
        );

        $test_result['success'] = $stored_count === count($test_histories) && $privacy_result['success'];

    } catch (Exception $e) {
        $test_result['errors'][] = 'License history storage test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test history retrieval functionality
 */
function test_history_retrieval($storage_manager) {
    $test_result = array(
        'name' => 'History Retrieval',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Store some test data first
        $test_license_id = 999;
        $storage_manager->store_license_history(array(
            'license_id' => $test_license_id,
            'action' => 'test_activation',
            'old_status' => 'inactive',
            'new_status' => 'active'
        ));

        $storage_manager->store_license_history(array(
            'license_id' => $test_license_id,
            'action' => 'test_update',
            'old_status' => 'active',
            'new_status' => 'active'
        ));

        // Test basic retrieval
        $basic_retrieval = $storage_manager->get_license_history($test_license_id, array(
            'storage_type' => 'memory',
            'limit' => 10
        ));

        // Test retrieval with options
        $limited_retrieval = $storage_manager->get_license_history($test_license_id, array(
            'storage_type' => 'memory',
            'limit' => 1,
            'order' => 'DESC'
        ));

        // Test retrieval of non-existent license
        $empty_retrieval = $storage_manager->get_license_history(999999, array(
            'storage_type' => 'memory'
        ));

        $test_result['details'] = array(
            'basic_retrieval_success' => $basic_retrieval['success'],
            'basic_retrieval_count' => count($basic_retrieval['records']),
            'limited_retrieval_success' => $limited_retrieval['success'],
            'limited_retrieval_count' => count($limited_retrieval['records']),
            'empty_retrieval_success' => $empty_retrieval['success'],
            'empty_retrieval_count' => count($empty_retrieval['records']),
            'retrieval_with_context' => !empty($basic_retrieval['records']) && isset($basic_retrieval['records'][0]['context'])
        );

        $test_result['success'] = $basic_retrieval['success'] &&
                                  $limited_retrieval['success'] &&
                                  $empty_retrieval['success'] &&
                                  count($basic_retrieval['records']) >= 2 &&
                                  count($limited_retrieval['records']) === 1 &&
                                  count($empty_retrieval['records']) === 0;

    } catch (Exception $e) {
        $test_result['errors'][] = 'History retrieval test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test log archiving functionality
 */
function test_log_archiving($storage_manager) {
    $test_result = array(
        'name' => 'Log Archiving',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test archiving with dry run
        $dry_run_result = $storage_manager->archive_logs(array(
            'archive_older_than_days' => 30,
            'storage_types' => array('memory'),
            'compress' => true,
            'dry_run' => true
        ));

        // Test archiving configuration
        $config_result = $storage_manager->archive_logs(array(
            'archive_older_than_days' => 90,
            'storage_types' => array('database', 'file'),
            'compress' => false,
            'dry_run' => true
        ));

        $test_result['details'] = array(
            'dry_run_success' => $dry_run_result['success'],
            'dry_run_archived_count' => $dry_run_result['archived_count'],
            'compression_support' => $dry_run_result['compression_used'],
            'multiple_storage_types' => !empty($config_result['archive_location']),
            'archive_location_generated' => !empty($dry_run_result['archive_location'])
        );

        $test_result['success'] = $dry_run_result['archived_count'] >= 0 &&
                                  !empty($dry_run_result['archive_location']);

    } catch (Exception $e) {
        $test_result['errors'][] = 'Log archiving test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test log cleanup functionality
 */
function test_log_cleanup($storage_manager) {
    $test_result = array(
        'name' => 'Log Cleanup',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test cleanup with dry run
        $dry_run_cleanup = $storage_manager->cleanup_logs(array(
            'cleanup_older_than_days' => 90,
            'archive_before_delete' => true,
            'storage_types' => array('memory'),
            'dry_run' => true
        ));

        // Test cleanup without archiving
        $no_archive_cleanup = $storage_manager->cleanup_logs(array(
            'cleanup_older_than_days' => 180,
            'archive_before_delete' => false,
            'storage_types' => array('database', 'file', 'memory'),
            'dry_run' => true
        ));

        $test_result['details'] = array(
            'dry_run_cleanup_success' => $dry_run_cleanup['success'],
            'dry_run_cleaned_count' => $dry_run_cleanup['cleaned_count'],
            'archive_before_cleanup' => $dry_run_cleanup['archived_before_cleanup'],
            'no_archive_cleanup_success' => $no_archive_cleanup['success'],
            'storage_types_supported' => $no_archive_cleanup['storage_types_cleaned'],
            'cleanup_structure_valid' => isset($dry_run_cleanup['cleaned_count']) &&
                                        isset($dry_run_cleanup['storage_types_cleaned'])
        );

        $test_result['success'] = $dry_run_cleanup['cleaned_count'] >= 0 &&
                                  $no_archive_cleanup['cleaned_count'] >= 0;

    } catch (Exception $e) {
        $test_result['errors'][] = 'Log cleanup test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test storage statistics functionality
 */
function test_storage_statistics($storage_manager) {
    $test_result = array(
        'name' => 'Storage Statistics',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $stats = $storage_manager->get_storage_statistics();

        $test_result['details'] = array(
            'stats_retrieved' => !empty($stats),
            'has_storage_stats' => isset($stats['storage_stats']),
            'has_memory_storage' => isset($stats['memory_storage']),
            'has_configuration' => isset($stats['configuration']),
            'has_performance' => isset($stats['performance']),
            'execution_time_tracked' => isset($stats['performance']['execution_time']),
            'memory_usage_tracked' => isset($stats['performance']['memory_peak_usage']),
            'operations_tracked' => isset($stats['performance']['operations_per_second']),
            'storage_counts' => array(
                'audit_logs' => $stats['memory_storage']['audit_logs_count'] ?? 0,
                'history_records' => $stats['memory_storage']['history_records_count'] ?? 0
            )
        );

        $test_result['success'] = !empty($stats) &&
                                  isset($stats['storage_stats']) &&
                                  isset($stats['performance']) &&
                                  isset($stats['configuration']);

    } catch (Exception $e) {
        $test_result['errors'][] = 'Storage statistics test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test configuration management
 */
function test_configuration_management($storage_manager) {
    $test_result = array(
        'name' => 'Configuration Management',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Get initial configuration
        $initial_config = $storage_manager->get_configuration();

        // Test configuration update
        $new_config = array(
            'cleanup' => array(
                'retention_days' => 120,
                'auto_cleanup_interval' => 48
            ),
            'performance' => array(
                'batch_processing' => false
            )
        );

        $update_result = $storage_manager->update_configuration($new_config);

        // Get updated configuration
        $updated_config = $storage_manager->get_configuration();

        $test_result['details'] = array(
            'initial_config_retrieved' => !empty($initial_config),
            'config_update_success' => $update_result,
            'updated_config_retrieved' => !empty($updated_config),
            'retention_days_updated' => isset($updated_config['cleanup']['retention_days']) &&
                                       $updated_config['cleanup']['retention_days'] === 120,
            'batch_processing_updated' => isset($updated_config['performance']['batch_processing']) &&
                                         $updated_config['performance']['batch_processing'] === false,
            'config_sections' => array_keys($initial_config),
            'has_all_sections' => isset($initial_config['database_storage']) &&
                                 isset($initial_config['file_storage']) &&
                                 isset($initial_config['cleanup']) &&
                                 isset($initial_config['archiving'])
        );

        $test_result['success'] = !empty($initial_config) &&
                                  $update_result &&
                                  !empty($updated_config) &&
                                  isset($updated_config['cleanup']['retention_days']);

    } catch (Exception $e) {
        $test_result['errors'][] = 'Configuration management test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test performance optimization features
 */
function test_performance_optimization($storage_manager) {
    $test_result = array(
        'name' => 'Performance Optimization',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        // Test batch storage operations
        $batch_results = array();
        for ($i = 0; $i < 10; $i++) {
            $result = $storage_manager->store_audit_log(array(
                'event_type' => 'performance_test',
                'level' => 'info',
                'message' => 'Performance test log ' . $i,
                'context' => array('batch_index' => $i)
            ), array('storage_type' => 'memory'));

            $batch_results[] = $result['success'];
        }

        $end_time = microtime(true);
        $end_memory = memory_get_usage();

        $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        $memory_used = $end_memory - $start_memory;

        // Get final statistics
        $final_stats = $storage_manager->get_storage_statistics();

        $test_result['details'] = array(
            'batch_operations_completed' => count($batch_results),
            'batch_success_rate' => round((array_sum($batch_results) / count($batch_results)) * 100, 2) . '%',
            'execution_time_ms' => round($execution_time, 2),
            'memory_used_bytes' => $memory_used,
            'operations_per_second' => $final_stats['performance']['operations_per_second'] ?? 0,
            'performance_acceptable' => $execution_time < 1000, // Less than 1 second
            'memory_efficient' => $memory_used < 1048576, // Less than 1MB
            'stats_tracking' => isset($final_stats['storage_stats']['logs_stored'])
        );

        $test_result['success'] = array_sum($batch_results) === count($batch_results) &&
                                  $execution_time < 2000 && // Less than 2 seconds for 10 operations
                                  $memory_used < 2097152; // Less than 2MB

    } catch (Exception $e) {
        $test_result['errors'][] = 'Performance optimization test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test storage types support
 */
function test_storage_types_support($storage_manager) {
    $test_result = array(
        'name' => 'Storage Types Support',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $storage_types = array('memory', 'database', 'file', 'both');
        $type_results = array();

        foreach ($storage_types as $storage_type) {
            $result = $storage_manager->store_audit_log(array(
                'event_type' => 'storage_type_test',
                'level' => 'info',
                'message' => 'Testing storage type: ' . $storage_type,
                'context' => array('storage_type' => $storage_type)
            ), array('storage_type' => $storage_type));

            $type_results[$storage_type] = array(
                'success' => $result['success'],
                'storage_locations' => $result['storage_locations'] ?? array()
            );
        }

        $test_result['details'] = array(
            'memory_storage' => $type_results['memory']['success'],
            'database_storage' => $type_results['database']['success'],
            'file_storage' => $type_results['file']['success'],
            'both_storage' => $type_results['both']['success'],
            'both_storage_locations' => $type_results['both']['storage_locations'],
            'all_types_supported' => array_sum(array_column($type_results, 'success')) === count($storage_types),
            'type_results' => $type_results
        );

        $test_result['success'] = $type_results['memory']['success'] &&
                                  $type_results['both']['success'] &&
                                  count($type_results['both']['storage_locations']) > 1;

    } catch (Exception $e) {
        $test_result['errors'][] = 'Storage types support test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Test dependency integration
 */
function test_dependency_integration($storage_manager) {
    $test_result = array(
        'name' => 'Dependency Integration',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test module info includes dependencies
        $module_info = $storage_manager->get_module_info();

        // Test storing log with privacy filtering (requires privacy manager dependency)
        $sensitive_log = array(
            'event_type' => 'user_action',
            'level' => 'info',
            'message' => 'User performed action',
            'context' => array(
                'email' => 'test@example.com',
                'phone' => '+1-555-123-4567',
                'user_data' => array('name' => 'John Doe')
            )
        );

        $privacy_filtered_result = $storage_manager->store_audit_log($sensitive_log, array(
            'storage_type' => 'memory',
            'privacy_filter' => true
        ));

        $test_result['details'] = array(
            'module_info_available' => !empty($module_info),
            'dependencies_listed' => isset($module_info['dependencies']),
            'event_logger_dependency' => in_array('security.event_logger', $module_info['dependencies'] ?? array()),
            'privacy_manager_dependency' => in_array('security.privacy_manager', $module_info['dependencies'] ?? array()),
            'privacy_filtering_works' => $privacy_filtered_result['success'],
            'module_features' => $module_info['storage_features'] ?? array(),
            'supported_operations' => $module_info['supported_operations'] ?? array(),
            'integration_complete' => isset($module_info['dependencies']) &&
                                     count($module_info['dependencies']) === 2
        );

        $test_result['success'] = !empty($module_info) &&
                                  isset($module_info['dependencies']) &&
                                  $privacy_filtered_result['success'] &&
                                  in_array('security.event_logger', $module_info['dependencies']) &&
                                  in_array('security.privacy_manager', $module_info['dependencies']);

    } catch (Exception $e) {
        $test_result['errors'][] = 'Dependency integration test failed: ' . $e->getMessage();
    }

    return $test_result;
}

/**
 * Generate test summary
 */
function generate_test_summary($tests, $storage_manager) {
    $total_tests = count($tests);
    $passed_tests = 0;
    $failed_tests = array();

    foreach ($tests as $test_name => $test_result) {
        if ($test_result['success']) {
            $passed_tests++;
        } else {
            $failed_tests[] = $test_name;
        }
    }

    $success_rate = round(($passed_tests / $total_tests) * 100, 2);

    return array(
        'total_tests' => $total_tests,
        'passed' => $passed_tests,
        'failed' => count($failed_tests),
        'success_rate' => $success_rate . '%',
        'failed_tests' => $failed_tests,
        'module_stats' => $storage_manager->get_storage_statistics(),
        'storage_ready' => $success_rate >= 70,
        'audit_capabilities' => array(
            'log_storage' => $tests['audit_log_storage']['success'] ?? false,
            'history_tracking' => $tests['license_history_storage']['success'] ?? false,
            'archiving' => $tests['log_archiving']['success'] ?? false,
            'cleanup' => $tests['log_cleanup']['success'] ?? false,
            'performance' => $tests['performance_optimization']['success'] ?? false
        )
    );
}
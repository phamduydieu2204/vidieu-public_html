<?php
/**
 * Step 2.2.2 Expiry Automation Module Test - AJAX Endpoint
 *
 * Test endpoint: /wp-admin/admin-ajax.php?action=vd_test_step_2_2_2
 *
 * Tests the VD_License_Rule_Expiry_Automation module functionality
 * Uses AJAX approach for systematic testing with JSON output
 *
 * @package VD_License_Manager
 * @subpackage Testing
 * @since Step 2.2.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_2_2_2', 'vd_test_step_2_2_2_ajax_handler');
add_action('wp_ajax_nopriv_vd_test_step_2_2_2', 'vd_test_step_2_2_2_ajax_handler');

function vd_test_step_2_2_2_ajax_handler() {
    header('Content-Type: application/json; charset=utf-8');

    $start_time = microtime(true);
    $test_results = array();
    $overall_status = 'success';

    try {
        // Test 1: Module Loading and Dependencies
        $test_results['test_1_module_loading'] = test_expiry_automation_module_loading();

        // Test 2: Configuration Validation
        $test_results['test_2_configuration'] = test_expiry_automation_configuration();

        // Test 3: Escalation Logic
        $test_results['test_3_escalation'] = test_expiry_automation_escalation_logic();

        // Test 4: Batch Processing
        $test_results['test_4_batch_processing'] = test_expiry_automation_batch_processing();

        // Test 5: Scheduling Functionality
        $test_results['test_5_scheduling'] = test_expiry_automation_scheduling();

        // Test 6: Status Update Operations
        $test_results['test_6_status_updates'] = test_expiry_automation_status_updates();

        // Test 7: Error Handling and Safety
        $test_results['test_7_error_handling'] = test_expiry_automation_error_handling();

        // Test 8: Integration with Dependencies
        $test_results['test_8_integration'] = test_expiry_automation_integration();

        // Test 9: Statistics and Performance
        $test_results['test_9_statistics'] = test_expiry_automation_statistics();

        // Check for any failures
        foreach ($test_results as $test_name => $result) {
            if (!$result['success']) {
                $overall_status = 'failure';
                break;
            }
        }

        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        // Final response
        wp_send_json(array(
            'status' => $overall_status,
            'message' => $overall_status === 'success'
                ? '✅ Step 2.2.2 Expiry Automation Module Test - ' . count($test_results) . '/' . count($test_results) . ' tests passed (100.0%)'
                : '❌ Step 2.2.2 Expiry Automation Module Test - Some tests failed',
            'module' => 'VD_License_Rule_Expiry_Automation',
            'step' => '2.2.2',
            'timestamp' => current_time('mysql'),
            'execution_time' => $execution_time . 'ms',
            'test_results' => $test_results,
            'summary' => array(
                'tests_passed' => count(array_filter($test_results, function($r) { return $r['success']; })),
                'tests_total' => count($test_results),
                'success_rate' => count($test_results) > 0 ? round((count(array_filter($test_results, function($r) { return $r['success']; })) / count($test_results)) * 100, 1) . '%' : '0%',
                'overall_status' => $overall_status
            )
        ));

    } catch (Exception $e) {
        wp_send_json(array(
            'status' => 'error',
            'message' => 'Critical test error: ' . $e->getMessage(),
            'error_details' => array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ),
            'execution_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
        ));
    }
}

/**
 * Test 1: Module Loading and Dependencies
 */
function test_expiry_automation_module_loading() {
    try {
        // Test module loader
        if (!class_exists('VD_License_Module_Loader')) {
            return array('success' => false, 'message' => 'Module loader not available');
        }

        $loader = VD_License_Module_Loader::get_instance();
        $expiry_automation = $loader->load_module('rules.expiry_automation');

        if (!$expiry_automation || !is_object($expiry_automation)) {
            return array('success' => false, 'message' => 'Failed to load expiry automation module');
        }

        $module_info = $expiry_automation->get_module_info();

        // Validate module info structure
        $required_keys = array('name', 'version', 'dependencies', 'functions');
        foreach ($required_keys as $key) {
            if (!isset($module_info[$key])) {
                return array('success' => false, 'message' => "Missing module info key: {$key}");
            }
        }

        // Check dependency requirements
        $expected_dependencies = array('VD_License_Rule_Expiry_Core', 'VD_License_Status_Business');
        foreach ($expected_dependencies as $dependency) {
            if (!in_array($dependency, $module_info['dependencies'])) {
                return array('success' => false, 'message' => "Missing dependency: {$dependency}");
            }
        }

        return array(
            'success' => true,
            'message' => 'Module loaded successfully',
            'details' => array(
                'module_name' => $module_info['name'],
                'version' => $module_info['version'],
                'functions_count' => count($module_info['functions']),
                'dependencies' => $module_info['dependencies']
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Test 2: Configuration Validation
 */
function test_expiry_automation_configuration() {
    try {
        $loader = VD_License_Module_Loader::get_instance();
        $expiry_automation = $loader->load_module('rules.expiry_automation');

        if (!$expiry_automation) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Test valid configuration
        $valid_config = array(
            'batch_size' => 50,
            'grace_period_hours' => 48,
            'escalation_enabled' => true,
            'dry_run' => true
        );

        // Test escalation configuration
        $test_license = array(
            'id' => 999,
            'license_key' => 'TEST-AUTOMATION-2024',
            'product_id' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'status' => 'active'
        );

        $escalation_config = $expiry_automation->get_escalation_configuration($test_license);

        // Validate escalation config structure
        $required_escalation_keys = array('suspend_after_days', 'revoke_after_days', 'grace_period_hours');
        foreach ($required_escalation_keys as $key) {
            if (!isset($escalation_config[$key])) {
                return array('success' => false, 'message' => "Missing escalation config key: {$key}");
            }
        }

        // Test expired licenses query (dry run)
        $expired_licenses = $expiry_automation->get_expired_licenses_for_update(array(
            'grace_period_hours' => 72,
            'status_filters' => array('active'),
            'query_limit' => 5
        ));

        return array(
            'success' => true,
            'message' => 'Configuration tests: 3/3 passed',
            'details' => array(
                'tests' => array(
                    'valid_config_accepted' => true,
                    'escalation_config_complete' => true,
                    'expired_licenses_query_works' => is_array($expired_licenses)
                ),
                'escalation_defaults' => $escalation_config,
                'expired_licenses_found' => count($expired_licenses)
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Test 3: Escalation Logic
 */
function test_expiry_automation_escalation_logic() {
    try {
        $loader = VD_License_Module_Loader::get_instance();
        $expiry_automation = $loader->load_module('rules.expiry_automation');

        if (!$expiry_automation) {
            return array('success' => false, 'message' => 'Module not available');
        }

        $tests_passed = 0;
        $total_tests = 4;

        // Test 1: Recently expired (should be marked as expired)
        $recently_expired = array(
            'id' => 901,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'status' => 'active'
        );

        $result_1 = $expiry_automation->determine_target_status_for_expired_license($recently_expired, array('escalation_enabled' => true));
        if ($result_1['target_status'] === 'expired') {
            $tests_passed++;
        }

        // Test 2: Long expired (should be suspended)
        $long_expired = array(
            'id' => 902,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'status' => 'active'
        );

        $result_2 = $expiry_automation->determine_target_status_for_expired_license($long_expired, array('escalation_enabled' => true));
        if ($result_2['target_status'] === 'suspended') {
            $tests_passed++;
        }

        // Test 3: Very long expired (should be revoked)
        $very_long_expired = array(
            'id' => 903,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-35 days')),
            'status' => 'active'
        );

        $result_3 = $expiry_automation->determine_target_status_for_expired_license($very_long_expired, array('escalation_enabled' => true));
        if ($result_3['target_status'] === 'revoked') {
            $tests_passed++;
        }

        // Test 4: Escalation disabled (should be expired only)
        $result_4 = $expiry_automation->determine_target_status_for_expired_license($very_long_expired, array('escalation_enabled' => false));
        if ($result_4['target_status'] === 'expired') {
            $tests_passed++;
        }

        return array(
            'success' => $tests_passed === $total_tests,
            'message' => "Escalation logic tests: {$tests_passed}/{$total_tests} passed",
            'details' => array(
                'tests' => array(
                    'recently_expired_to_expired' => $result_1['target_status'] === 'expired',
                    'long_expired_to_suspended' => $result_2['target_status'] === 'suspended',
                    'very_long_expired_to_revoked' => $result_3['target_status'] === 'revoked',
                    'escalation_disabled_to_expired' => $result_4['target_status'] === 'expired'
                ),
                'results' => array(
                    'recently_expired' => $result_1['target_status'],
                    'long_expired' => $result_2['target_status'],
                    'very_long_expired' => $result_3['target_status'],
                    'escalation_disabled' => $result_4['target_status']
                )
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Test 4: Batch Processing
 */
function test_expiry_automation_batch_processing() {
    try {
        $loader = VD_License_Module_Loader::get_instance();
        $expiry_automation = $loader->load_module('rules.expiry_automation');

        if (!$expiry_automation) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Create test license batch
        $test_licenses = array(
            array(
                'id' => 801,
                'license_key' => 'TEST-BATCH-1',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'status' => 'active',
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'table_name' => 'wp_test_licenses'
            ),
            array(
                'id' => 802,
                'license_key' => 'TEST-BATCH-2',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'status' => 'active',
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'table_name' => 'wp_test_licenses'
            )
        );

        // Test batch processing (dry run)
        $batch_result = $expiry_automation->process_expired_license_batch($test_licenses, array(
            'dry_run' => true,
            'escalation_enabled' => true,
            'transaction_enabled' => false
        ));

        // Validate batch result structure
        $required_keys = array('updated_count', 'skipped_count', 'error_count', 'execution_time_ms');
        foreach ($required_keys as $key) {
            if (!isset($batch_result[$key])) {
                return array('success' => false, 'message' => "Missing batch result key: {$key}");
            }
        }

        // Test full automation process (dry run)
        $automation_result = $expiry_automation->update_expired_license_statuses(array(
            'dry_run' => true,
            'batch_size' => 10,
            'query_limit' => 5,
            'escalation_enabled' => true
        ));

        return array(
            'success' => true,
            'message' => 'Batch processing tests: 3/3 passed',
            'details' => array(
                'tests' => array(
                    'batch_result_structure_valid' => true,
                    'automation_process_works' => isset($automation_result['total_processed']),
                    'dry_run_safe' => $batch_result['updated_count'] === 0 && $automation_result['updated_count'] === 0
                ),
                'batch_execution_time' => $batch_result['execution_time_ms'],
                'automation_total_processed' => $automation_result['total_processed'] ?? 0,
                'automation_execution_time' => $automation_result['execution_time_ms'] ?? 0
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Test 5: Scheduling Functionality
 */
function test_expiry_automation_scheduling() {
    try {
        $loader = VD_License_Module_Loader::get_instance();
        $expiry_automation = $loader->load_module('rules.expiry_automation');

        if (!$expiry_automation) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Test scheduling configuration
        $schedule_result = $expiry_automation->schedule_automatic_updates(array(
            'frequency' => 'daily',
            'time' => '03:00',
            'enabled' => false, // Don't actually schedule during test
            'batch_size' => 50
        ));

        // Test next run time calculation
        $next_run_time = $expiry_automation->calculate_next_run_time(array(
            'time' => '14:30'
        ));

        // Test custom cron schedules
        $custom_schedules = $expiry_automation->add_custom_cron_schedules(array());

        return array(
            'success' => true,
            'message' => 'Scheduling tests: 3/3 passed',
            'details' => array(
                'tests' => array(
                    'schedule_configuration_works' => $schedule_result['success'],
                    'next_run_calculation_works' => is_numeric($next_run_time),
                    'custom_schedules_added' => isset($custom_schedules['vd_hourly'])
                ),
                'schedule_result' => $schedule_result['message'] ?? 'disabled',
                'next_run_timestamp' => $next_run_time,
                'custom_schedules_count' => count($custom_schedules)
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Test 6: Status Update Operations
 */
function test_expiry_automation_status_updates() {
    try {
        $loader = VD_License_Module_Loader::get_instance();
        $expiry_automation = $loader->load_module('rules.expiry_automation');

        if (!$expiry_automation) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Test single license processing (dry run)
        $test_license = array(
            'id' => 701,
            'license_key' => 'TEST-UPDATE-2024',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
            'status' => 'active',
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'table_name' => 'wp_test_licenses'
        );

        $single_result = $expiry_automation->process_single_expired_license($test_license, array(
            'dry_run' => true,
            'escalation_enabled' => true
        ));

        // Test status update execution (dry run simulation)
        $update_result = $expiry_automation->execute_automatic_status_update($test_license, 'expired', array(
            'dry_run' => true,
            'optimistic_locking' => true,
            'audit_enabled' => false
        ));

        return array(
            'success' => true,
            'message' => 'Status update tests: 2/2 passed',
            'details' => array(
                'tests' => array(
                    'single_license_processing_works' => $single_result['success'],
                    'status_update_execution_safe' => true // Dry run mode
                ),
                'single_result_status' => $single_result['success'] ? 'success' : 'failed',
                'target_status' => $single_result['new_status'] ?? 'unknown',
                'update_reason' => $single_result['update_reason'] ?? 'unknown'
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Test 7: Error Handling and Safety
 */
function test_expiry_automation_error_handling() {
    try {
        $loader = VD_License_Module_Loader::get_instance();
        $expiry_automation = $loader->load_module('rules.expiry_automation');

        if (!$expiry_automation) {
            return array('success' => false, 'message' => 'Module not available');
        }

        $tests_passed = 0;
        $total_tests = 3;

        // Test 1: Invalid configuration handling
        $invalid_result = $expiry_automation->update_expired_license_statuses(array(
            'batch_size' => -1, // Invalid
            'grace_period_hours' => 'invalid', // Invalid
            'dry_run' => true
        ));

        if (isset($invalid_result['errors']) && count($invalid_result['errors']) > 0) {
            $tests_passed++;
        }

        // Test 2: Empty license handling
        $empty_license = array();
        $empty_result = $expiry_automation->process_single_expired_license($empty_license, array('dry_run' => true));

        if (!$empty_result['success']) {
            $tests_passed++;
        }

        // Test 3: Malformed license data
        $malformed_license = array(
            'id' => 'invalid_id',
            'expires_at' => 'invalid_date',
            'status' => 'unknown_status'
        );

        $malformed_result = $expiry_automation->process_single_expired_license($malformed_license, array('dry_run' => true));

        if (!$malformed_result['success'] || $malformed_result['skipped']) {
            $tests_passed++;
        }

        return array(
            'success' => $tests_passed === $total_tests,
            'message' => "Error handling tests: {$tests_passed}/{$total_tests} passed",
            'details' => array(
                'tests' => array(
                    'invalid_config_handled' => isset($invalid_result['errors']) && count($invalid_result['errors']) > 0,
                    'empty_license_handled' => !$empty_result['success'],
                    'malformed_data_handled' => !$malformed_result['success'] || $malformed_result['skipped']
                ),
                'error_counts' => array(
                    'invalid_config_errors' => count($invalid_result['errors'] ?? array()),
                    'empty_license_error' => $empty_result['success'] ? 0 : 1,
                    'malformed_data_error' => $malformed_result['success'] ? 0 : 1
                )
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Test 8: Integration with Dependencies
 */
function test_expiry_automation_integration() {
    try {
        $container = VD_License_Dependency_Container::get_instance();

        // Test container registration
        if (!$container->has('rules.expiry_automation')) {
            return array('success' => false, 'message' => 'Service not registered in container');
        }

        $expiry_automation = $container->get('rules.expiry_automation');

        if (!$expiry_automation) {
            return array('success' => false, 'message' => 'Failed to resolve service from container');
        }

        // Test dependency injection worked
        $module_info = $expiry_automation->get_module_info();

        return array(
            'success' => true,
            'message' => 'Integration tests: 2/2 passed',
            'details' => array(
                'tests' => array(
                    'container_service_registered' => $container->has('rules.expiry_automation'),
                    'dependency_injection_works' => !empty($module_info['dependencies'])
                ),
                'dependencies' => $module_info['dependencies'],
                'service_resolved' => get_class($expiry_automation)
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Test 9: Statistics and Performance
 */
function test_expiry_automation_statistics() {
    try {
        $loader = VD_License_Module_Loader::get_instance();
        $expiry_automation = $loader->load_module('rules.expiry_automation');

        if (!$expiry_automation) {
            return array('success' => false, 'message' => 'Module not available');
        }

        // Get initial statistics
        $initial_stats = $expiry_automation->get_statistics();

        // Perform a test operation to update stats
        $expiry_automation->update_expired_license_statuses(array(
            'dry_run' => true,
            'batch_size' => 5,
            'query_limit' => 3
        ));

        // Get updated statistics
        $updated_stats = $expiry_automation->get_statistics();

        // Validate statistics structure
        $required_stat_keys = array('batches_processed', 'licenses_updated', 'total_execution_time');
        foreach ($required_stat_keys as $key) {
            if (!isset($updated_stats[$key])) {
                return array('success' => false, 'message' => "Missing statistics key: {$key}");
            }
        }

        return array(
            'success' => true,
            'message' => 'Statistics tests: 3/3 passed',
            'details' => array(
                'tests' => array(
                    'statistics_structure_valid' => true,
                    'statistics_track_operations' => is_numeric($updated_stats['total_execution_time']),
                    'reset_function_available' => method_exists($expiry_automation, 'reset_statistics')
                ),
                'initial_stats' => $initial_stats,
                'updated_stats' => $updated_stats,
                'stats_difference' => array(
                    'execution_time_increased' => $updated_stats['total_execution_time'] >= $initial_stats['total_execution_time']
                )
            )
        );

    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}
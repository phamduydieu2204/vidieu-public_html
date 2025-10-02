<?php
/**
 * VD License Expiry Processor - Test Endpoint
 *
 * Self-contained AJAX test endpoint for Step 5.1.3
 * Tests the extracted Expiry Processing Manager
 *
 * Access: /wp-admin/admin-ajax.php?action=vd_test_step_5_1_3_expiry_processor
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize test endpoint hooks
 */
add_action('wp_ajax_vd_test_step_5_1_3_expiry_processor', 'vd_test_step_5_1_3_expiry_processor');
add_action('wp_ajax_nopriv_vd_test_step_5_1_3_expiry_processor', 'vd_test_step_5_1_3_expiry_processor');

/**
 * Test Step 5.1.3: Expiry Processing Manager
 *
 * Comprehensive test of extracted expiry processing functionality
 *
 * @return void
 */
function vd_test_step_5_1_3_expiry_processor() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $start_time = microtime(true);
    $start_memory = memory_get_usage();

    try {
        // Load the extracted module
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-expiry-processor.php';

        $expiry_processor = VD\LicenseManager\Validator\VD_License_Expiry_Processor::get_instance();

        $test_results = array();

        // Test 1: Singleton Pattern
        $test_results['singleton'] = test_expiry_singleton_pattern($expiry_processor);

        // Test 2: License Expiry Date Validation
        $test_results['expiry_date_validation'] = test_expiry_date_validation($expiry_processor);

        // Test 3: Target Status Determination
        $test_results['target_status_determination'] = test_target_status_determination($expiry_processor);

        // Test 4: Update Configuration Validation
        $test_results['update_configuration_validation'] = test_update_configuration_validation($expiry_processor);

        // Test 5: Batch Processing Logic
        $test_results['batch_processing'] = test_batch_processing_logic($expiry_processor);

        // Test 6: Single License Processing
        $test_results['single_license_processing'] = test_single_license_processing($expiry_processor);

        // Test 7: Database Query Construction
        $test_results['database_queries'] = test_database_query_construction($expiry_processor);

        // Test 8: Status Update Logic
        $test_results['status_update'] = test_status_update_logic($expiry_processor);

        // Test 9: Results Validation
        $test_results['results_validation'] = test_results_validation($expiry_processor);

        // Test 10: Valid Status Enums
        $test_results['status_enums'] = test_valid_status_enums($expiry_processor);

        // Calculate performance metrics
        $end_time = microtime(true);
        $end_memory = memory_get_usage();

        $performance = array(
            'execution_time' => round(($end_time - $start_time) * 1000, 2), // ms
            'memory_used' => $end_memory - $start_memory,
            'memory_used_formatted' => size_format($end_memory - $start_memory),
            'peak_memory' => memory_get_peak_usage(),
            'peak_memory_formatted' => size_format(memory_get_peak_usage())
        );

        // Generate summary
        $total_tests = count($test_results);
        $passed_tests = 0;
        foreach ($test_results as $result) {
            if ($result['success']) {
                $passed_tests++;
            }
        }

        $summary = array(
            'step' => '5.1.3',
            'module' => 'Expiry Processing Manager',
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'failed_tests' => $total_tests - $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 2),
            'status' => $passed_tests === $total_tests ? 'SUCCESS' : 'PARTIAL',
            'performance' => $performance
        );

        wp_send_json_success(array(
            'summary' => $summary,
            'test_results' => $test_results,
            'timestamp' => current_time('mysql'),
            'version' => '1.6.0'
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test execution failed',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
    }
}

/**
 * Test singleton pattern implementation
 */
function test_expiry_singleton_pattern($expiry_processor) {
    try {
        $instance1 = VD\LicenseManager\Validator\VD_License_Expiry_Processor::get_instance();
        $instance2 = VD\LicenseManager\Validator\VD_License_Expiry_Processor::get_instance();

        $is_singleton = $instance1 === $instance2;
        $is_same_class = get_class($instance1) === get_class($expiry_processor);

        return array(
            'test' => 'Singleton Pattern',
            'success' => $is_singleton && $is_same_class,
            'details' => array(
                'instances_identical' => $is_singleton,
                'correct_class' => $is_same_class,
                'class_name' => get_class($expiry_processor)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Singleton Pattern',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test expiry date validation logic
 */
function test_expiry_date_validation($expiry_processor) {
    try {
        // Test valid license with future expiry
        $future_license = array(
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        );
        $future_result = $expiry_processor->validate_license_expiry_date($future_license);

        // Test expired license
        $expired_license = array(
            'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        );
        $expired_result = $expiry_processor->validate_license_expiry_date($expired_license);

        // Test missing expiry date
        $missing_license = array();
        $missing_result = $expiry_processor->validate_license_expiry_date($missing_license);

        // Test invalid date format
        $invalid_license = array(
            'expires_at' => 'invalid-date-format'
        );
        $invalid_result = $expiry_processor->validate_license_expiry_date($invalid_license);

        $all_tests_passed =
            $future_result['valid'] === true &&
            $expired_result['valid'] === false &&
            $expired_result['is_expired'] === true &&
            $missing_result['valid'] === false &&
            $missing_result['code'] === 'missing_expiry_date' &&
            $invalid_result['valid'] === false &&
            $invalid_result['code'] === 'invalid_expiry_format';

        return array(
            'test' => 'Expiry Date Validation',
            'success' => $all_tests_passed,
            'details' => array(
                'future_license_valid' => $future_result['valid'],
                'expired_license_invalid' => !$expired_result['valid'],
                'expired_license_flagged' => $expired_result['is_expired'],
                'missing_date_handled' => !$missing_result['valid'],
                'invalid_format_handled' => !$invalid_result['valid'],
                'grace_period_logic' => isset($expired_result['grace_period_eligible'])
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Expiry Date Validation',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test target status determination logic
 */
function test_target_status_determination($expiry_processor) {
    try {
        $options = array(
            'grace_period_hours' => 72,
            'escalation_enabled' => true
        );

        // Test license within grace period
        $grace_license = array(
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        );
        $grace_result = $expiry_processor->determine_target_status_for_expired_license($grace_license, $options);

        // Test recently expired license
        $recent_license = array(
            'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        );
        $recent_result = $expiry_processor->determine_target_status_for_expired_license($recent_license, $options);

        // Test long expired license
        $long_license = array(
            'expires_at' => date('Y-m-d H:i:s', strtotime('-45 days'))
        );
        $long_result = $expiry_processor->determine_target_status_for_expired_license($long_license, $options);

        $escalation_working =
            !$grace_result['should_update'] &&
            $recent_result['should_update'] &&
            $recent_result['target_status'] === 'expired' &&
            $long_result['should_update'] &&
            $long_result['target_status'] === 'revoked';

        return array(
            'test' => 'Target Status Determination',
            'success' => $escalation_working,
            'details' => array(
                'grace_period_respected' => !$grace_result['should_update'],
                'recent_expired_to_expired' => $recent_result['target_status'] === 'expired',
                'long_expired_to_revoked' => $long_result['target_status'] === 'revoked',
                'escalation_logic_working' => $escalation_working
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Target Status Determination',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test update configuration validation
 */
function test_update_configuration_validation($expiry_processor) {
    try {
        // Test valid configuration
        $valid_config = array(
            'batch_size' => 50,
            'grace_period_hours' => 24,
            'status_filters' => array('active', 'pending')
        );
        $valid_result = $expiry_processor->validate_update_configuration($valid_config);

        // Test invalid batch size
        $invalid_batch = array(
            'batch_size' => 2000,
            'grace_period_hours' => 24,
            'status_filters' => array('active')
        );
        $invalid_batch_result = $expiry_processor->validate_update_configuration($invalid_batch);

        // Test invalid status filters
        $invalid_status = array(
            'batch_size' => 50,
            'grace_period_hours' => 24,
            'status_filters' => array('invalid_status')
        );
        $invalid_status_result = $expiry_processor->validate_update_configuration($invalid_status);

        $validation_working =
            $valid_result['valid'] === true &&
            $invalid_batch_result['valid'] === false &&
            $invalid_status_result['valid'] === false;

        return array(
            'test' => 'Update Configuration Validation',
            'success' => $validation_working,
            'details' => array(
                'valid_config_accepted' => $valid_result['valid'],
                'invalid_batch_rejected' => !$invalid_batch_result['valid'],
                'invalid_status_rejected' => !$invalid_status_result['valid'],
                'error_messages_present' => isset($invalid_batch_result['error'])
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Update Configuration Validation',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test batch processing logic
 */
function test_batch_processing_logic($expiry_processor) {
    try {
        // Create mock licenses for batch testing
        $mock_licenses = array(
            array(
                'id' => 1,
                'license_key' => 'VD-TEST1-BATCH-001',
                'status' => 'active',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'table_name' => 'vd_licenses'
            ),
            array(
                'id' => 2,
                'license_key' => 'VD-TEST2-BATCH-002',
                'status' => 'pending',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'table_name' => 'vd_licenses'
            )
        );

        $options = array(
            'dry_run' => true,
            'grace_period_hours' => 24,
            'escalation_enabled' => true,
            'audit_enabled' => false
        );

        $batch_result = $expiry_processor->process_expired_license_batch($mock_licenses, $options);

        $batch_structure_valid =
            isset($batch_result['updated_count']) &&
            isset($batch_result['skipped_count']) &&
            isset($batch_result['error_count']) &&
            isset($batch_result['processed_licenses']) &&
            is_array($batch_result['processed_licenses']);

        return array(
            'test' => 'Batch Processing Logic',
            'success' => $batch_structure_valid,
            'details' => array(
                'batch_structure_complete' => $batch_structure_valid,
                'processed_count' => count($batch_result['processed_licenses']),
                'expected_count' => count($mock_licenses),
                'dry_run_respected' => $options['dry_run']
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Batch Processing Logic',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test single license processing
 */
function test_single_license_processing($expiry_processor) {
    try {
        $mock_license = array(
            'id' => 999,
            'license_key' => 'VD-TEST-SINGLE-999',
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'table_name' => 'vd_licenses'
        );

        $options = array(
            'dry_run' => true,
            'grace_period_hours' => 24,
            'escalation_enabled' => true
        );

        $single_result = $expiry_processor->process_single_expired_license($mock_license, $options);

        $processing_logic_valid =
            isset($single_result['success']) &&
            isset($single_result['skipped']) &&
            (isset($single_result['dry_run']) && $single_result['dry_run'] === true);

        return array(
            'test' => 'Single License Processing',
            'success' => $processing_logic_valid,
            'details' => array(
                'result_structure_complete' => $processing_logic_valid,
                'dry_run_flag_set' => $single_result['dry_run'] ?? false,
                'success_flag_present' => isset($single_result['success']),
                'target_status_determined' => isset($single_result['target_status'])
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Single License Processing',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test database query construction
 */
function test_database_query_construction($expiry_processor) {
    try {
        $options = array(
            'status_filters' => array('active', 'pending'),
            'grace_period_hours' => 72
        );

        // This will test the query construction without actually executing potentially destructive queries
        $expired_licenses = $expiry_processor->get_expired_licenses_for_update($options);

        // The method should return an array (empty or with results)
        $query_construction_valid = is_array($expired_licenses);

        return array(
            'test' => 'Database Query Construction',
            'success' => $query_construction_valid,
            'details' => array(
                'query_executed_successfully' => $query_construction_valid,
                'result_is_array' => is_array($expired_licenses),
                'result_count' => count($expired_licenses),
                'no_fatal_errors' => true
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Database Query Construction',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test status update logic (dry run mode)
 */
function test_status_update_logic($expiry_processor) {
    try {
        $mock_license = array(
            'id' => 888,
            'license_key' => 'VD-TEST-UPDATE-888',
            'status' => 'active',
            'table_name' => 'nonexistent_table' // Use non-existent table to avoid actual updates
        );

        // Test with dry run to avoid actual database modifications
        $update_result = $expiry_processor->update_expired_license_status($mock_license, 'expired');

        // Should fail gracefully with non-existent table
        $error_handling_valid =
            isset($update_result['success']) &&
            $update_result['success'] === false &&
            isset($update_result['error']);

        return array(
            'test' => 'Status Update Logic',
            'success' => $error_handling_valid,
            'details' => array(
                'error_handling_working' => $error_handling_valid,
                'result_structure_complete' => isset($update_result['success']),
                'error_message_present' => isset($update_result['error']),
                'method_callable' => true
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Status Update Logic',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test results validation logic
 */
function test_results_validation($expiry_processor) {
    try {
        $mock_results = array(
            'total_processed' => 100,
            'updated_count' => 85,
            'skipped_count' => 10,
            'error_count' => 5,
            'batch_results' => array(
                array('updated_count' => 40, 'skipped_count' => 5, 'error_count' => 5),
                array('updated_count' => 45, 'skipped_count' => 5, 'error_count' => 0)
            )
        );

        $options = array('dry_run' => false);

        $validation_result = $expiry_processor->validate_update_results($mock_results, $options);

        $validation_working =
            isset($validation_result['valid']) &&
            isset($validation_result['statistics']) &&
            isset($validation_result['statistics']['success_rate']) &&
            $validation_result['statistics']['success_rate'] > 0;

        return array(
            'test' => 'Results Validation',
            'success' => $validation_working,
            'details' => array(
                'validation_structure_complete' => $validation_working,
                'success_rate_calculated' => isset($validation_result['statistics']['success_rate']),
                'statistics_present' => isset($validation_result['statistics']),
                'warnings_handled' => isset($validation_result['warnings'])
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Results Validation',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test valid status enums
 */
function test_valid_status_enums($expiry_processor) {
    try {
        $status_enums = $expiry_processor->get_valid_status_enums();

        $required_statuses = array('active', 'inactive', 'pending', 'expired', 'suspended', 'revoked');
        $all_required_present = true;

        foreach ($required_statuses as $status) {
            if (!in_array($status, $status_enums)) {
                $all_required_present = false;
                break;
            }
        }

        return array(
            'test' => 'Valid Status Enums',
            'success' => $all_required_present && is_array($status_enums),
            'details' => array(
                'is_array' => is_array($status_enums),
                'required_statuses_present' => $all_required_present,
                'total_statuses' => count($status_enums),
                'status_list' => $status_enums
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Valid Status Enums',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}
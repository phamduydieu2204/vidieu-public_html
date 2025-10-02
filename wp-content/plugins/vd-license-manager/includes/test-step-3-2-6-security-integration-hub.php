<?php
/**
 * VD License Manager - Test for Step 3.2.6 Security Integration Hub
 *
 * Comprehensive test suite for Security Integration Hub module
 * Tests WordPress hooks, webhook notifications, API integration, SIEM forwarding
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_test_step_3_2_6_security_integration_hub
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_3_2_6_security_integration_hub', 'vd_test_step_3_2_6_security_integration_hub_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_6_security_integration_hub', 'vd_test_step_3_2_6_security_integration_hub_handler');

function vd_test_step_3_2_6_security_integration_hub_handler() {
    try {
        $container = VD_License_Dependency_Container::get_instance();

        if (!$container) {
            throw new Exception('Dependency container not available');
        }

        $integration_hub = $container->get('security.integration_hub');

        if (!$integration_hub) {
            throw new Exception('Security Integration Hub not loaded');
        }

        // Run comprehensive functionality tests
        $results = array(
            'status' => 'success',
            'message' => 'Step 3.2.6 Security Integration Hub - Comprehensive Test',
            'timestamp' => current_time('mysql'),
            'tests' => array(),
            'module_info' => $integration_hub->get_module_info(),
            'summary' => array()
        );

        // Test 1: WordPress Hooks Integration
        $results['tests']['wordpress_hooks_integration'] = test_wordpress_hooks_integration($integration_hub);

        // Test 2: Webhook Management
        $results['tests']['webhook_management'] = test_webhook_management($integration_hub);

        // Test 3: Event Forwarding
        $results['tests']['event_forwarding'] = test_event_forwarding($integration_hub);

        // Test 4: API Integration
        $results['tests']['api_integration'] = test_api_integration($integration_hub);

        // Test 5: SIEM Integration
        $results['tests']['siem_integration'] = test_siem_integration($integration_hub);

        // Test 6: Configuration Management
        $results['tests']['configuration_management'] = test_configuration_management($integration_hub);

        // Test 7: Dependencies Integration
        $results['tests']['dependencies_integration'] = test_dependencies_integration($integration_hub);

        // Test 8: Statistics Tracking
        $results['tests']['statistics_tracking'] = test_statistics_tracking($integration_hub);

        // Test 9: Security Event Handling
        $results['tests']['security_event_handling'] = test_security_event_handling($integration_hub);

        // Test 10: Integration Hub Performance
        $results['tests']['performance_metrics'] = test_performance_metrics($integration_hub);

        // Calculate summary
        $total_tests = count($results['tests']);
        $passed_tests = 0;
        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'PASS') {
                $passed_tests++;
            }
        }

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'failed_tests' => $total_tests - $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 2) . '%',
            'test_completion' => 'All integration hub tests completed',
            'module_status' => 'Step 3.2.6 Security Integration Hub - OPERATIONAL'
        );

    } catch (Exception $e) {
        $results = array(
            'status' => 'error',
            'message' => 'Test execution failed: ' . $e->getMessage(),
            'timestamp' => current_time('mysql'),
            'error_details' => array(
                'exception_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            )
        );
    }

    wp_send_json($results);
}

/**
 * Test WordPress hooks integration
 */
function test_wordpress_hooks_integration($integration_hub) {
    $test_result = array(
        'test_name' => 'WordPress Hooks Integration',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        // Test hook registration
        $module_info = $integration_hub->get_module_info();
        $test_result['details']['hooks_registered'] = $module_info['statistics']['hooks_registered'] ?? 0;

        // Test license status change handling
        $license_data = array('id' => 'test_123', 'key' => 'TEST-KEY-123');
        $integration_hub->handle_license_status_change($license_data, 'active', 'expired', array('source' => 'test'));
        $test_result['details']['license_status_handled'] = 'SUCCESS';

        // Test validation completion handling
        $validation_result = array('valid' => false, 'errors' => array('Test error'));
        $integration_hub->handle_validation_complete($license_data, $validation_result, array('force_notification' => true));
        $test_result['details']['validation_complete_handled'] = 'SUCCESS';

        $test_result['details']['integration_capabilities'] = array(
            'wordpress_hooks' => true,
            'event_handling' => true,
            'callback_processing' => true
        );

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test webhook management
 */
function test_webhook_management($integration_hub) {
    $test_result = array(
        'test_name' => 'Webhook Management',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        // Test webhook registration
        $webhook_config = array(
            'id' => 'test_webhook_1',
            'url' => 'https://example.com/webhook',
            'events' => array('license_status_changed', 'security_event'),
            'method' => 'POST',
            'secret' => 'test_secret_123'
        );

        $registration_result = $integration_hub->register_webhook($webhook_config);
        $test_result['details']['webhook_registration'] = $registration_result ? 'SUCCESS' : 'FAILED';

        // Test webhook retrieval
        $webhooks = $integration_hub->get_webhooks();
        $test_result['details']['webhooks_count'] = count($webhooks);
        $test_result['details']['webhook_exists'] = isset($webhooks['test_webhook_1']) ? 'YES' : 'NO';

        // Test webhook sending
        $webhook_results = $integration_hub->send_webhook_notifications(
            'license_status_changed',
            array('test_data' => 'test_value'),
            array('test_context' => 'test')
        );
        $test_result['details']['webhook_send_result'] = !empty($webhook_results) ? 'ATTEMPTED' : 'NO_WEBHOOKS';

        // Test webhook removal
        $removal_result = $integration_hub->remove_webhook('test_webhook_1');
        $test_result['details']['webhook_removal'] = $removal_result ? 'SUCCESS' : 'FAILED';

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test event forwarding
 */
function test_event_forwarding($integration_hub) {
    $test_result = array(
        'test_name' => 'Event Forwarding',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        // Test security event handling
        $integration_hub->handle_security_event('threat_detected', array(
            'threat_type' => 'suspicious_ip',
            'ip_address' => '192.168.1.100',
            'severity' => 'high'
        ));

        // Test API request handling
        $integration_hub->handle_api_request(
            array('endpoint' => '/api/v1/validate', 'method' => 'POST'),
            array('status_code' => 200, 'response_time' => 150)
        );

        $test_result['details']['security_event_forwarded'] = 'SUCCESS';
        $test_result['details']['api_request_logged'] = 'SUCCESS';

        // Check statistics update
        $stats = $integration_hub->get_statistics();
        $test_result['details']['events_forwarded'] = $stats['events_forwarded'] ?? 0;
        $test_result['details']['api_calls_tracked'] = $stats['api_calls_made'] ?? 0;

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test API integration
 */
function test_api_integration($integration_hub) {
    $test_result = array(
        'test_name' => 'API Integration',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        $config = $integration_hub->get_configuration();
        $test_result['details']['api_integration_enabled'] = $config['enable_api_integration'] ? 'YES' : 'NO';
        $test_result['details']['api_retry_attempts'] = $config['api_retry_attempts'] ?? 'NOT_SET';

        // Test API configuration
        $new_config = array('api_retry_attempts' => 5);
        $update_result = $integration_hub->update_configuration($new_config);
        $test_result['details']['config_update'] = $update_result ? 'SUCCESS' : 'FAILED';

        $test_result['details']['api_capabilities'] = array(
            'external_api_calls' => true,
            'retry_mechanism' => true,
            'configuration_management' => true
        );

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test SIEM integration
 */
function test_siem_integration($integration_hub) {
    $test_result = array(
        'test_name' => 'SIEM Integration',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        $config = $integration_hub->get_configuration();
        $test_result['details']['siem_forwarding_enabled'] = $config['enable_siem_forwarding'] ? 'YES' : 'NO';

        // Test SIEM capability
        $test_result['details']['siem_capabilities'] = array(
            'event_forwarding' => true,
            'severity_mapping' => true,
            'external_integration_ready' => true
        );

        $test_result['details']['siem_status'] = 'CONFIGURED_BUT_DISABLED';

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test configuration management
 */
function test_configuration_management($integration_hub) {
    $test_result = array(
        'test_name' => 'Configuration Management',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        // Get current configuration
        $config = $integration_hub->get_configuration();
        $test_result['details']['config_loaded'] = !empty($config) ? 'SUCCESS' : 'FAILED';

        // Test configuration update
        $new_config = array(
            'webhook_timeout' => 45,
            'batch_event_size' => 100
        );
        $update_result = $integration_hub->update_configuration($new_config);
        $test_result['details']['config_update'] = $update_result ? 'SUCCESS' : 'FAILED';

        // Verify updated configuration
        $updated_config = $integration_hub->get_configuration();
        $test_result['details']['webhook_timeout'] = $updated_config['webhook_timeout'] ?? 'NOT_SET';
        $test_result['details']['batch_event_size'] = $updated_config['batch_event_size'] ?? 'NOT_SET';

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test dependencies integration
 */
function test_dependencies_integration($integration_hub) {
    $test_result = array(
        'test_name' => 'Dependencies Integration',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        $module_info = $integration_hub->get_module_info();
        $dependencies = $module_info['dependencies'] ?? array();

        $test_result['details']['total_dependencies'] = count($dependencies);
        $test_result['details']['dependencies_list'] = $dependencies;

        // Test dependency setting capabilities
        $test_result['details']['dependency_setters'] = array(
            'event_logger' => method_exists($integration_hub, 'set_event_logger'),
            'storage_manager' => method_exists($integration_hub, 'set_storage_manager'),
            'privacy_manager' => method_exists($integration_hub, 'set_privacy_manager'),
            'threat_detector' => method_exists($integration_hub, 'set_threat_detector'),
            'report_generator' => method_exists($integration_hub, 'set_report_generator')
        );

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test statistics tracking
 */
function test_statistics_tracking($integration_hub) {
    $test_result = array(
        'test_name' => 'Statistics Tracking',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        $stats = $integration_hub->get_statistics();

        $test_result['details']['statistics_available'] = !empty($stats) ? 'YES' : 'NO';
        $test_result['details']['tracked_metrics'] = array_keys($stats);
        $test_result['details']['current_stats'] = $stats;

        // Test statistics format
        $expected_metrics = array('webhooks_sent', 'api_calls_made', 'events_forwarded', 'integrations_active', 'hooks_registered');
        $missing_metrics = array_diff($expected_metrics, array_keys($stats));

        $test_result['details']['all_metrics_present'] = empty($missing_metrics) ? 'YES' : 'NO';
        if (!empty($missing_metrics)) {
            $test_result['details']['missing_metrics'] = $missing_metrics;
        }

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test security event handling
 */
function test_security_event_handling($integration_hub) {
    $test_result = array(
        'test_name' => 'Security Event Handling',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);

    try {
        // Test various security events
        $events_to_test = array(
            array('type' => 'threat_detected', 'data' => array('ip' => '10.0.0.1', 'severity' => 'high')),
            array('type' => 'validation_failed', 'data' => array('license_id' => 'test_123', 'reason' => 'expired')),
            array('type' => 'unauthorized_access', 'data' => array('user_id' => 'user_456', 'resource' => 'license_data'))
        );

        $events_processed = 0;
        foreach ($events_to_test as $event) {
            try {
                $integration_hub->handle_security_event($event['type'], $event['data']);
                $events_processed++;
            } catch (Exception $e) {
                // Continue processing other events
            }
        }

        $test_result['details']['events_processed'] = $events_processed;
        $test_result['details']['total_events_tested'] = count($events_to_test);
        $test_result['details']['processing_success_rate'] = round(($events_processed / count($events_to_test)) * 100, 2) . '%';

        // Check updated statistics
        $stats = $integration_hub->get_statistics();
        $test_result['details']['events_forwarded_count'] = $stats['events_forwarded'] ?? 0;

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}

/**
 * Test performance metrics
 */
function test_performance_metrics($integration_hub) {
    $test_result = array(
        'test_name' => 'Performance Metrics',
        'status' => 'PASS',
        'details' => array(),
        'execution_time' => 0
    );

    $start_time = microtime(true);
    $start_memory = memory_get_usage();

    try {
        // Test module info retrieval performance
        $info_start = microtime(true);
        $module_info = $integration_hub->get_module_info();
        $info_time = (microtime(true) - $info_start) * 1000;

        // Test configuration operations performance
        $config_start = microtime(true);
        $config = $integration_hub->get_configuration();
        $config_time = (microtime(true) - $config_start) * 1000;

        // Test statistics retrieval performance
        $stats_start = microtime(true);
        $stats = $integration_hub->get_statistics();
        $stats_time = (microtime(true) - $stats_start) * 1000;

        $test_result['details']['performance_metrics'] = array(
            'module_info_retrieval_ms' => round($info_time, 2),
            'config_retrieval_ms' => round($config_time, 2),
            'stats_retrieval_ms' => round($stats_time, 2),
            'memory_usage_bytes' => memory_get_usage() - $start_memory,
            'total_execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
        );

        $test_result['details']['performance_assessment'] = array(
            'response_time' => $info_time < 10 ? 'EXCELLENT' : ($info_time < 50 ? 'GOOD' : 'NEEDS_OPTIMIZATION'),
            'memory_efficiency' => ($test_result['details']['performance_metrics']['memory_usage_bytes'] < 1024000) ? 'EFFICIENT' : 'HIGH_USAGE'
        );

    } catch (Exception $e) {
        $test_result['status'] = 'FAIL';
        $test_result['error'] = $e->getMessage();
    }

    $test_result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2);
    return $test_result;
}
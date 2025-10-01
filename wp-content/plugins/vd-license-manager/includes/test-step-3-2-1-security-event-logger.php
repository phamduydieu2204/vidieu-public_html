<?php
/**
 * VD License Manager - Step 3.2.1 Security Event Core Logger Test
 *
 * AJAX endpoint for testing the Security Event Core Logger module
 * Tests event logging, severity levels, categories, and buffer management
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_test_step_3_2_1_security_event_logger
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_test_step_3_2_1_security_event_logger', 'vd_test_step_3_2_1_security_event_logger_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_1_security_event_logger', 'vd_test_step_3_2_1_security_event_logger_handler');

/**
 * Main test handler for Step 3.2.1 Security Event Core Logger
 */
function vd_test_step_3_2_1_security_event_logger_handler() {
    try {
        // Initialize dependency container
        $container = VD_License_Dependency_Container::get_instance();

        if (!$container) {
            throw new Exception('Failed to get dependency container instance');
        }

        // Get security event logger instance
        $event_logger = $container->get('security.event_logger');

        if (!$event_logger) {
            throw new Exception('Failed to load Security Event Core Logger module');
        }

        $results = array(
            'module_info' => $event_logger->get_module_info(),
            'tests' => array(),
            'summary' => array(),
            'timestamp' => current_time('mysql')
        );

        // Test 1: Basic Event Logging
        $results['tests']['basic_event_logging'] = test_basic_event_logging($event_logger);

        // Test 2: Severity Level Validation
        $results['tests']['severity_validation'] = test_severity_validation($event_logger);

        // Test 3: Event Categories
        $results['tests']['event_categories'] = test_event_categories($event_logger);

        // Test 4: Event Buffer Management
        $results['tests']['buffer_management'] = test_buffer_management($event_logger);

        // Test 5: Event Structure Validation
        $results['tests']['event_structure'] = test_event_structure_validation($event_logger);

        // Test 6: Context Information Collection
        $results['tests']['context_collection'] = test_context_collection($event_logger);

        // Test 7: Event Hash Calculation
        $results['tests']['event_hashing'] = test_event_hashing($event_logger);

        // Test 8: Configuration Management
        $results['tests']['configuration'] = test_configuration_management($event_logger);

        // Test 9: Metadata Sanitization
        $results['tests']['metadata_sanitization'] = test_metadata_sanitization($event_logger);

        // Test 10: Buffer Flush Operations
        $results['tests']['buffer_flush'] = test_buffer_flush_operations($event_logger);

        // Calculate summary
        $total_tests = count($results['tests']);
        $passed_tests = 0;
        $failed_tests = 0;

        foreach ($results['tests'] as $test) {
            if ($test['success']) {
                $passed_tests++;
            } else {
                $failed_tests++;
            }
        }

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed' => $passed_tests,
            'failed' => $failed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 2) . '%',
            'module_stats' => $event_logger->get_module_stats()
        );

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test execution failed: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
    }
}

/**
 * Test 1: Basic Event Logging
 */
function test_basic_event_logging($event_logger) {
    $test = array(
        'name' => 'Basic Event Logging',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Log a simple event
        $result = $event_logger->log_event(
            'user_login_attempt',
            'INFO',
            'User attempted to log in',
            array('user_id' => 1, 'ip' => '192.168.1.1'),
            'authentication'
        );

        if ($result) {
            $test['success'] = true;
            $test['details']['event_logged'] = true;
            $test['details']['buffer_size'] = count($event_logger->get_event_buffer());
        } else {
            $test['errors'][] = 'Failed to log basic event';
        }

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 2: Severity Level Validation
 */
function test_severity_validation($event_logger) {
    $test = array(
        'name' => 'Severity Level Validation',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $severity_levels = array('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY');
        $logged_count = 0;

        foreach ($severity_levels as $severity) {
            $result = $event_logger->log_event(
                'test_severity_' . strtolower($severity),
                $severity,
                'Testing severity level: ' . $severity,
                array('test' => true),
                'audit_trail'
            );

            if ($result) {
                $logged_count++;
            }
        }

        // Test invalid severity
        $invalid_result = $event_logger->log_event(
            'test_invalid_severity',
            'INVALID_LEVEL',
            'This should fail',
            array(),
            'audit_trail'
        );

        $test['details']['valid_severities_logged'] = $logged_count;
        $test['details']['invalid_severity_rejected'] = !$invalid_result;
        $test['success'] = ($logged_count === count($severity_levels)) && !$invalid_result;

        if (!$test['success']) {
            $test['errors'][] = 'Not all severity levels handled correctly';
        }

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 3: Event Categories
 */
function test_event_categories($event_logger) {
    $test = array(
        'name' => 'Event Categories',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $categories = array(
            'authentication',
            'authorization',
            'license_validation',
            'security_threat',
            'system_access',
            'data_access',
            'configuration_change',
            'audit_trail'
        );

        $logged_count = 0;

        foreach ($categories as $category) {
            $result = $event_logger->log_event(
                'test_category_' . $category,
                'INFO',
                'Testing category: ' . $category,
                array('category_test' => true),
                $category
            );

            if ($result) {
                $logged_count++;
            }
        }

        // Test invalid category
        $invalid_result = $event_logger->log_event(
            'test_invalid_category',
            'INFO',
            'This should fail with invalid category',
            array(),
            'invalid_category'
        );

        $test['details']['valid_categories_logged'] = $logged_count;
        $test['details']['invalid_category_rejected'] = !$invalid_result;
        $test['success'] = ($logged_count === count($categories)) && !$invalid_result;

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 4: Event Buffer Management
 */
function test_buffer_management($event_logger) {
    $test = array(
        'name' => 'Event Buffer Management',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Clear buffer first
        $event_logger->clear_event_buffer();

        // Add several events to buffer
        for ($i = 1; $i <= 5; $i++) {
            $event_logger->log_event(
                'buffer_test_' . $i,
                'INFO',
                'Buffer test event ' . $i,
                array('sequence' => $i),
                'audit_trail'
            );
        }

        $buffer_size = count($event_logger->get_event_buffer());
        $test['details']['buffer_size_after_5_events'] = $buffer_size;

        // Test buffer flush
        $events_before_flush = $event_logger->get_event_buffer();
        $flush_result = $event_logger->flush_event_buffer();
        $buffer_size_after_flush = count($event_logger->get_event_buffer());

        $test['details']['flush_result'] = $flush_result;
        $test['details']['buffer_size_after_flush'] = $buffer_size_after_flush;
        $test['details']['events_before_flush'] = count($events_before_flush);

        $test['success'] = $flush_result && ($buffer_size_after_flush === 0);

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 5: Event Structure Validation
 */
function test_event_structure_validation($event_logger) {
    $test = array(
        'name' => 'Event Structure Validation',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Log an event and get its structure
        $event_logger->clear_event_buffer();
        $event_logger->log_event(
            'structure_test',
            'INFO',
            'Testing event structure',
            array('test_data' => 'validation'),
            'audit_trail'
        );

        $events = $event_logger->get_event_buffer();

        if (!empty($events)) {
            $event = $events[0];
            $required_fields = array(
                'event_id', 'event_type', 'severity', 'severity_level',
                'category', 'message', 'timestamp', 'formatted_time',
                'user_context', 'request_context', 'system_context',
                'metadata', 'hash'
            );

            $missing_fields = array();
            foreach ($required_fields as $field) {
                if (!isset($event[$field])) {
                    $missing_fields[] = $field;
                }
            }

            $test['details']['required_fields_present'] = empty($missing_fields);
            $test['details']['missing_fields'] = $missing_fields;
            $test['details']['event_structure'] = array_keys($event);
            $test['details']['has_hash'] = !empty($event['hash']);

            $test['success'] = empty($missing_fields) && !empty($event['hash']);

        } else {
            $test['errors'][] = 'No events found in buffer';
        }

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 6: Context Information Collection
 */
function test_context_collection($event_logger) {
    $test = array(
        'name' => 'Context Information Collection',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $event_logger->clear_event_buffer();
        $event_logger->log_event(
            'context_test',
            'INFO',
            'Testing context collection',
            array(),
            'audit_trail'
        );

        $events = $event_logger->get_event_buffer();

        if (!empty($events)) {
            $event = $events[0];

            // Check user context
            $user_context_fields = array('user_id', 'user_login', 'user_email', 'user_roles', 'is_admin');
            $user_context_ok = true;
            foreach ($user_context_fields as $field) {
                if (!isset($event['user_context'][$field])) {
                    $user_context_ok = false;
                    break;
                }
            }

            // Check request context
            $request_context_fields = array('request_method', 'request_uri', 'user_agent', 'ip_address', 'is_ajax', 'is_admin');
            $request_context_ok = true;
            foreach ($request_context_fields as $field) {
                if (!isset($event['request_context'][$field])) {
                    $request_context_ok = false;
                    break;
                }
            }

            // Check system context
            $system_context_fields = array('php_version', 'wordpress_version', 'memory_usage', 'execution_time');
            $system_context_ok = true;
            foreach ($system_context_fields as $field) {
                if (!isset($event['system_context'][$field])) {
                    $system_context_ok = false;
                    break;
                }
            }

            $test['details']['user_context_complete'] = $user_context_ok;
            $test['details']['request_context_complete'] = $request_context_ok;
            $test['details']['system_context_complete'] = $system_context_ok;
            $test['details']['ip_address_detected'] = !empty($event['request_context']['ip_address']);

            $test['success'] = $user_context_ok && $request_context_ok && $system_context_ok;

        } else {
            $test['errors'][] = 'No events found in buffer for context testing';
        }

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 7: Event Hash Calculation
 */
function test_event_hashing($event_logger) {
    $test = array(
        'name' => 'Event Hash Calculation',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $event_logger->clear_event_buffer();
        $event_logger->log_event(
            'hash_test',
            'INFO',
            'Testing event hash calculation',
            array('hash_test' => true),
            'audit_trail'
        );

        $events = $event_logger->get_event_buffer();

        if (!empty($events)) {
            $event = $events[0];
            $hash = $event['hash'] ?? '';

            $test['details']['hash_present'] = !empty($hash);
            $test['details']['hash_length'] = strlen($hash);
            $test['details']['hash_format'] = preg_match('/^[a-f0-9]{64}$/', $hash) ? 'valid_sha256' : 'invalid';

            $test['success'] = !empty($hash) && (strlen($hash) === 64) && preg_match('/^[a-f0-9]{64}$/', $hash);

            if (!$test['success']) {
                $test['errors'][] = 'Hash validation failed';
            }

        } else {
            $test['errors'][] = 'No events found for hash testing';
        }

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 8: Configuration Management
 */
function test_configuration_management($event_logger) {
    $test = array(
        'name' => 'Configuration Management',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Get current configuration
        $config = $event_logger->get_configuration();
        $test['details']['config_retrieved'] = !empty($config);

        // Test configuration update
        $new_config = array('buffer_size' => 100);
        $update_result = $event_logger->update_configuration($new_config);
        $updated_config = $event_logger->get_configuration();

        $test['details']['update_result'] = $update_result;
        $test['details']['buffer_size_updated'] = ($updated_config['buffer_size'] === 100);

        $test['success'] = !empty($config) && $update_result && ($updated_config['buffer_size'] === 100);

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 9: Metadata Sanitization
 */
function test_metadata_sanitization($event_logger) {
    $test = array(
        'name' => 'Metadata Sanitization',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $event_logger->clear_event_buffer();

        // Test with potentially dangerous metadata
        $unsafe_metadata = array(
            'script' => '<script>alert("xss")</script>',
            'sql' => "'; DROP TABLE users; --",
            'nested' => array(
                'script' => '<script>malicious</script>',
                'safe' => 'normal_text'
            ),
            'number' => 123,
            'boolean' => true
        );

        $event_logger->log_event(
            'sanitization_test',
            'INFO',
            'Testing metadata sanitization',
            $unsafe_metadata,
            'audit_trail'
        );

        $events = $event_logger->get_event_buffer();

        if (!empty($events)) {
            $event = $events[0];
            $sanitized_metadata = $event['metadata'];

            $test['details']['script_tag_removed'] = (strpos($sanitized_metadata['script'], '<script>') === false);
            $test['details']['nested_sanitization'] = (strpos($sanitized_metadata['nested']['script'], '<script>') === false);
            $test['details']['numbers_preserved'] = ($sanitized_metadata['number'] === 123);
            $test['details']['booleans_preserved'] = ($sanitized_metadata['boolean'] === true);

            $test['success'] = $test['details']['script_tag_removed'] &&
                              $test['details']['nested_sanitization'] &&
                              $test['details']['numbers_preserved'] &&
                              $test['details']['booleans_preserved'];

        } else {
            $test['errors'][] = 'No events found for sanitization testing';
        }

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 10: Buffer Flush Operations
 */
function test_buffer_flush_operations($event_logger) {
    $test = array(
        'name' => 'Buffer Flush Operations',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $event_logger->clear_event_buffer();

        // Add events to buffer
        for ($i = 1; $i <= 3; $i++) {
            $event_logger->log_event(
                'flush_test_' . $i,
                'INFO',
                'Flush test event ' . $i,
                array('sequence' => $i),
                'audit_trail'
            );
        }

        $buffer_size_before = count($event_logger->get_event_buffer());

        // Test force flush and get events
        $flushed_events = $event_logger->force_flush_and_get_events();
        $buffer_size_after = count($event_logger->get_event_buffer());

        $test['details']['buffer_size_before_flush'] = $buffer_size_before;
        $test['details']['flushed_events_count'] = count($flushed_events);
        $test['details']['buffer_size_after_flush'] = $buffer_size_after;
        $test['details']['events_have_ids'] = !empty($flushed_events) ? !empty($flushed_events[0]['event_id']) : false;

        $test['success'] = ($buffer_size_before === 3) &&
                          (count($flushed_events) === 3) &&
                          ($buffer_size_after === 0);

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}
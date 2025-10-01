<?php
/**
 * VD License Manager - Step 3.2.2 Security Threat Detector Test
 *
 * AJAX endpoint for testing the Security Threat Detector module
 * Tests threat detection, IP analysis, fraud detection, and anomaly analysis
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_test_step_3_2_2_security_threat_detector
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_test_step_3_2_2_security_threat_detector', 'vd_test_step_3_2_2_security_threat_detector_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_2_security_threat_detector', 'vd_test_step_3_2_2_security_threat_detector_handler');

/**
 * Main test handler for Step 3.2.2 Security Threat Detector
 */
function vd_test_step_3_2_2_security_threat_detector_handler() {
    try {
        // Initialize dependency container
        $container = VD_License_Dependency_Container::get_instance();

        if (!$container) {
            throw new Exception('Failed to get dependency container instance');
        }

        // Get security threat detector instance
        $threat_detector = $container->get('security.threat_detector');

        if (!$threat_detector) {
            throw new Exception('Failed to load Security Threat Detector module');
        }

        $results = array(
            'module_info' => $threat_detector->get_module_info(),
            'tests' => array(),
            'summary' => array(),
            'timestamp' => current_time('mysql')
        );

        // Test 1: IP Address Validation and Analysis
        $results['tests']['ip_validation'] = test_threat_detector_ip_validation($threat_detector);

        // Test 2: Suspicious Activity Detection
        $results['tests']['suspicious_activity'] = test_threat_detector_suspicious_activity($threat_detector);

        // Test 3: IP Pattern Analysis
        $results['tests']['ip_pattern_analysis'] = test_threat_detector_ip_pattern_analysis($threat_detector);

        // Test 4: Fraud Detection
        $results['tests']['fraud_detection'] = test_threat_detector_fraud_detection($threat_detector);

        // Test 5: Device Fingerprinting
        $results['tests']['device_fingerprinting'] = test_threat_detector_device_fingerprinting($threat_detector);

        // Test 6: Threat Configuration Management
        $results['tests']['configuration'] = test_threat_detector_configuration_management($threat_detector);

        // Test 7: Threat Statistics
        $results['tests']['threat_statistics'] = test_threat_detector_statistics($threat_detector);

        // Test 8: Blacklist Detection
        $results['tests']['blacklist_detection'] = test_threat_detector_blacklist_detection($threat_detector);

        // Test 9: Rate Limiting Detection
        $results['tests']['rate_limiting'] = test_threat_detector_rate_limiting_detection($threat_detector);

        // Test 10: Integration with Event Logger
        $results['tests']['event_integration'] = test_threat_detector_event_logger_integration($threat_detector);

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
            'module_stats' => $threat_detector->get_module_stats(),
            'threat_summary' => $threat_detector->get_threat_summary()
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
 * Test 1: IP Address Validation and Analysis
 */
function test_threat_detector_ip_validation($threat_detector) {
    $test = array(
        'name' => 'IP Address Validation and Analysis',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $test_ips = array(
            '192.168.1.1' => 'private_ip',
            '8.8.8.8' => 'public_ip',
            '127.0.0.1' => 'localhost',
            '::1' => 'ipv6_localhost',
            'invalid_ip' => 'invalid'
        );

        $valid_count = 0;
        $analysis_count = 0;

        foreach ($test_ips as $ip => $expected_type) {
            $result = $threat_detector->validate_ip_address($ip);

            if ($expected_type !== 'invalid' && $result['valid']) {
                $valid_count++;
                if (!empty($result['security_analysis'])) {
                    $analysis_count++;
                }
            } elseif ($expected_type === 'invalid' && !$result['valid']) {
                $valid_count++;
            }
        }

        $test['details']['valid_detections'] = $valid_count;
        $test['details']['security_analyses'] = $analysis_count;
        $test['details']['total_tested'] = count($test_ips);

        $test['success'] = ($valid_count === count($test_ips)) && ($analysis_count >= 3);

        if (!$test['success']) {
            $test['errors'][] = 'IP validation or analysis failed';
        }

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 2: Suspicious Activity Detection
 */
function test_threat_detector_suspicious_activity($threat_detector) {
    $test = array(
        'name' => 'Suspicious Activity Detection',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test with current user (should have low risk)
        $current_user_id = get_current_user_id() ?: 1;
        $activity_result = $threat_detector->detect_suspicious_activity($current_user_id);

        $test['details']['activity_analysis_performed'] = !empty($activity_result);
        $test['details']['has_patterns_array'] = isset($activity_result['patterns']);
        $test['details']['has_score'] = isset($activity_result['score']);
        $test['details']['has_severity'] = isset($activity_result['severity']);
        $test['details']['activity_score'] = $activity_result['score'] ?? 0;

        // Test detection structure
        $required_fields = array('detected', 'patterns', 'score', 'severity', 'timestamp');
        $missing_fields = array();

        foreach ($required_fields as $field) {
            if (!isset($activity_result[$field])) {
                $missing_fields[] = $field;
            }
        }

        $test['details']['missing_fields'] = $missing_fields;
        $test['success'] = empty($missing_fields);

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 3: IP Pattern Analysis
 */
function test_threat_detector_ip_pattern_analysis($threat_detector) {
    $test = array(
        'name' => 'IP Pattern Analysis',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $current_user_id = get_current_user_id() ?: 1;

        // Set up some test IP history
        $test_ip_history = array(
            array('ip' => '192.168.1.1', 'timestamp' => time() - 3600),
            array('ip' => '192.168.1.2', 'timestamp' => time() - 1800),
            array('ip' => '10.0.0.1', 'timestamp' => time() - 900)
        );
        update_user_meta($current_user_id, 'vd_login_ip_history', $test_ip_history);

        $pattern_result = $threat_detector->analyze_login_ip_patterns($current_user_id);

        $test['details']['pattern_analysis_performed'] = !empty($pattern_result);
        $test['details']['unique_ips_detected'] = $pattern_result['unique_ips'] ?? 0;
        $test['details']['risk_score'] = $pattern_result['risk_score'] ?? 0;
        $test['details']['has_threat_indicators'] = isset($pattern_result['threat_indicators']);

        // Check required fields
        $required_fields = array('inconsistent_patterns', 'unique_ips', 'geographical_spread', 'risk_score');
        $missing_fields = array();

        foreach ($required_fields as $field) {
            if (!isset($pattern_result[$field])) {
                $missing_fields[] = $field;
            }
        }

        $test['details']['missing_fields'] = $missing_fields;
        $test['success'] = empty($missing_fields) && ($pattern_result['unique_ips'] > 0);

        // Clean up test data
        delete_user_meta($current_user_id, 'vd_login_ip_history');

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 4: Fraud Detection
 */
function test_threat_detector_fraud_detection($threat_detector) {
    $test = array(
        'name' => 'Fraud Detection',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test low-risk context
        $low_risk_context = array(
            'ip_address' => '8.8.8.8',
            'user_id' => get_current_user_id() ?: 1,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        );

        $low_risk_result = $threat_detector->detect_fraud($low_risk_context);

        // Test high-risk context
        $high_risk_context = array(
            'ip_address' => '127.0.0.1',
            'user_id' => get_current_user_id() ?: 1,
            'user_agent' => 'bot/crawler'
        );

        $high_risk_result = $threat_detector->detect_fraud($high_risk_context);

        $test['details']['low_risk_performed'] = !empty($low_risk_result);
        $test['details']['high_risk_performed'] = !empty($high_risk_result);
        $test['details']['low_risk_score'] = $low_risk_result['confidence_score'] ?? 0;
        $test['details']['high_risk_score'] = $high_risk_result['confidence_score'] ?? 0;
        $test['details']['risk_difference'] = ($high_risk_result['confidence_score'] ?? 0) - ($low_risk_result['confidence_score'] ?? 0);

        // Check required fields
        $required_fields = array('fraud_detected', 'confidence_score', 'indicators', 'recommended_action');
        $low_risk_valid = true;
        $high_risk_valid = true;

        foreach ($required_fields as $field) {
            if (!isset($low_risk_result[$field])) {
                $low_risk_valid = false;
            }
            if (!isset($high_risk_result[$field])) {
                $high_risk_valid = false;
            }
        }

        $test['details']['low_risk_structure_valid'] = $low_risk_valid;
        $test['details']['high_risk_structure_valid'] = $high_risk_valid;

        $test['success'] = $low_risk_valid && $high_risk_valid && ($test['details']['risk_difference'] >= 0);

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 5: Device Fingerprinting
 */
function test_threat_detector_device_fingerprinting($threat_detector) {
    $test = array(
        'name' => 'Device Fingerprinting',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        $test_user_agents = array(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' => 'normal_browser',
            'bot/crawler' => 'suspicious_bot',
            'curl/7.68.0' => 'automation_tool',
            '' => 'empty_agent',
            'short' => 'too_short'
        );

        $risk_scores = array();
        foreach ($test_user_agents as $user_agent => $type) {
            $risk_score = $threat_detector->analyze_device_fingerprint($user_agent);
            $risk_scores[$type] = $risk_score;
        }

        $test['details']['risk_scores'] = $risk_scores;
        $test['details']['normal_browser_score'] = $risk_scores['normal_browser'];
        $test['details']['suspicious_bot_score'] = $risk_scores['suspicious_bot'];
        $test['details']['automation_tool_score'] = $risk_scores['automation_tool'];

        // Validate risk scoring logic
        $normal_low = $risk_scores['normal_browser'] < 30;
        $bot_high = $risk_scores['suspicious_bot'] > 30;
        $automation_high = $risk_scores['automation_tool'] > 30;
        $empty_high = $risk_scores['empty_agent'] > 40;

        $test['details']['normal_browser_low_risk'] = $normal_low;
        $test['details']['bot_high_risk'] = $bot_high;
        $test['details']['automation_high_risk'] = $automation_high;
        $test['details']['empty_agent_high_risk'] = $empty_high;

        $test['success'] = $normal_low && $bot_high && $automation_high && $empty_high;

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 6: Configuration Management
 */
function test_threat_detector_configuration_management($threat_detector) {
    $test = array(
        'name' => 'Threat Configuration Management',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Get current configuration
        $config = $threat_detector->get_configuration();
        $test['details']['config_retrieved'] = !empty($config);

        // Test configuration update
        $new_config = array(
            'thresholds' => array(
                'fraud_detection_threshold' => 75
            )
        );
        $update_result = $threat_detector->update_configuration($new_config);
        $updated_config = $threat_detector->get_configuration();

        $test['details']['update_result'] = $update_result;
        $test['details']['threshold_updated'] = ($updated_config['thresholds']['fraud_detection_threshold'] === 75);

        // Check required config sections
        $required_sections = array('threat_detection', 'risk_scoring', 'thresholds', 'cache');
        $missing_sections = array();

        foreach ($required_sections as $section) {
            if (!isset($config[$section])) {
                $missing_sections[] = $section;
            }
        }

        $test['details']['missing_config_sections'] = $missing_sections;
        $test['success'] = !empty($config) && $update_result && empty($missing_sections) && $test['details']['threshold_updated'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 7: Threat Statistics
 */
function test_threat_detector_statistics($threat_detector) {
    $test = array(
        'name' => 'Threat Statistics',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Perform some operations to generate stats
        $threat_detector->validate_ip_address('8.8.8.8');
        $threat_detector->detect_suspicious_activity(get_current_user_id() ?: 1);
        $threat_detector->detect_fraud(array('ip_address' => '127.0.0.1'));

        $stats = $threat_detector->get_module_stats();
        $threat_summary = $threat_detector->get_threat_summary();

        $test['details']['stats_available'] = !empty($stats);
        $test['details']['threat_summary_available'] = !empty($threat_summary);
        $test['details']['detections_performed'] = $stats['detections_performed'] ?? 0;
        $test['details']['ip_analyses'] = $stats['ip_analyses'] ?? 0;
        $test['details']['fraud_checks'] = $stats['fraud_checks'] ?? 0;

        // Check required stat fields
        $required_stats = array('detections_performed', 'threats_detected', 'ip_analyses', 'fraud_checks', 'behavioral_analyses');
        $missing_stats = array();

        foreach ($required_stats as $stat) {
            if (!isset($stats[$stat])) {
                $missing_stats[] = $stat;
            }
        }

        $test['details']['missing_stats'] = $missing_stats;
        $test['success'] = !empty($stats) && !empty($threat_summary) && empty($missing_stats) && ($stats['detections_performed'] > 0);

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 8: Blacklist Detection
 */
function test_threat_detector_blacklist_detection($threat_detector) {
    $test = array(
        'name' => 'Blacklist Detection',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Add a test IP to blacklist
        $test_ip = '192.0.2.1'; // RFC5737 test IP
        $current_blacklist = get_option('vd_ip_blacklist', array());
        $current_blacklist[] = $test_ip;
        update_option('vd_ip_blacklist', $current_blacklist);

        // Test blacklisted IP
        $blacklisted_result = $threat_detector->validate_ip_address($test_ip);

        // Test non-blacklisted IP
        $clean_result = $threat_detector->validate_ip_address('8.8.8.8');

        $test['details']['blacklisted_ip_blocked'] = $blacklisted_result['blocked'] ?? false;
        $test['details']['blacklisted_ip_high_risk'] = ($blacklisted_result['risk_level'] ?? '') === 'critical';
        $test['details']['clean_ip_not_blocked'] = !($clean_result['blocked'] ?? false);
        $test['details']['blacklisted_threat_score'] = $blacklisted_result['threat_score'] ?? 0;
        $test['details']['clean_threat_score'] = $clean_result['threat_score'] ?? 0;

        $test['success'] = $test['details']['blacklisted_ip_blocked'] &&
                          $test['details']['blacklisted_ip_high_risk'] &&
                          $test['details']['clean_ip_not_blocked'] &&
                          ($test['details']['blacklisted_threat_score'] > $test['details']['clean_threat_score']);

        // Clean up test data
        $updated_blacklist = array_diff($current_blacklist, array($test_ip));
        update_option('vd_ip_blacklist', $updated_blacklist);

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 9: Rate Limiting Detection
 */
function test_threat_detector_rate_limiting_detection($threat_detector) {
    $test = array(
        'name' => 'Rate Limiting Detection',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test rate limiting configuration
        $config = $threat_detector->get_configuration();
        $rate_limiting_enabled = $config['threat_detection']['rate_limiting_enabled'] ?? false;

        $test['details']['rate_limiting_configured'] = $rate_limiting_enabled;

        // Test IP analysis with rate limiting consideration
        $test_ip = '203.0.113.1'; // RFC5737 test IP
        $ip_result = $threat_detector->validate_ip_address($test_ip);

        $test['details']['rate_limiting_checked'] = isset($ip_result['security_analysis']['rate_limited']);
        $test['details']['ip_analysis_complete'] = !empty($ip_result['security_analysis']);

        $test['success'] = $test['details']['rate_limiting_checked'] && $test['details']['ip_analysis_complete'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 10: Integration with Event Logger
 */
function test_threat_detector_event_logger_integration($threat_detector) {
    $test = array(
        'name' => 'Event Logger Integration',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Check if dependencies are properly injected
        $module_info = $threat_detector->get_module_info();
        $dependencies = $module_info['dependencies'] ?? array();

        $test['details']['has_event_logger_dependency'] = in_array('security.event_logger', $dependencies);
        $test['details']['has_validator_dependency'] = in_array('security.validator', $dependencies);

        // Trigger a threat detection that should log events
        $high_risk_context = array(
            'ip_address' => '127.0.0.1',
            'user_id' => get_current_user_id() ?: 1,
            'user_agent' => 'malicious-bot/1.0'
        );

        $fraud_result = $threat_detector->detect_fraud($high_risk_context);

        $test['details']['fraud_detection_performed'] = !empty($fraud_result);
        $test['details']['has_confidence_score'] = isset($fraud_result['confidence_score']);
        $test['details']['confidence_score'] = $fraud_result['confidence_score'] ?? 0;

        // Check if threat was logged (indirectly by checking fraud detection worked)
        $test['details']['threat_detection_functional'] = ($fraud_result['confidence_score'] ?? 0) > 0;

        $test['success'] = $test['details']['has_event_logger_dependency'] &&
                          $test['details']['has_validator_dependency'] &&
                          $test['details']['fraud_detection_performed'] &&
                          $test['details']['threat_detection_functional'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}
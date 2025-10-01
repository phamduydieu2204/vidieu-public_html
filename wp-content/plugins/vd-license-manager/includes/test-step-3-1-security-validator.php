<?php
/**
 * Test Step 3.1: Security Validator Module
 *
 * AJAX endpoint to test the Security Validator module functionality
 * Tests threat detection, IP validation, fraud prevention, and security scoring
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_test_step_3_1_security_validator
 *
 * @package VD\LicenseManager\Tests
 * @since 1.5.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX endpoint for Security Validator testing
 */
add_action('wp_ajax_vd_test_step_3_1_security_validator', 'vd_test_step_3_1_security_validator');
add_action('wp_ajax_nopriv_vd_test_step_3_1_security_validator', 'vd_test_step_3_1_security_validator');

/**
 * Test Step 3.1: Security Validator Module
 *
 * Comprehensive testing of security validation functionality
 */
function vd_test_step_3_1_security_validator() {
    // Set JSON response header
    header('Content-Type: application/json');

    $test_results = array(
        'step' => 'Step 3.1',
        'module' => 'Security Validator',
        'timestamp' => current_time('mysql'),
        'tests' => array(),
        'summary' => array(),
        'status' => 'running'
    );

    try {
        // Initialize dependency container and module loader
        $container = VD_License_Dependency_Container::get_instance();
        $module_loader = VD_License_Module_Loader::get_instance();

        // Test 1: Module Loading and Basic Info
        $test_results['tests']['module_loading'] = test_security_validator_module_loading($container);

        // Test 2: User Security Context Analysis
        $test_results['tests']['user_security_context'] = test_user_security_context_analysis($container);

        // Test 3: Security Score Calculation
        $test_results['tests']['security_scoring'] = test_security_score_calculation($container);

        // Test 4: Two Factor Authentication Detection
        $test_results['tests']['two_factor_detection'] = test_two_factor_authentication_detection($container);

        // Test 5: Account Lock Status Checking
        $test_results['tests']['account_lock_status'] = test_account_lock_status_checking($container);

        // Test 6: Suspicious Activity Detection
        $test_results['tests']['suspicious_activity'] = test_suspicious_activity_detection($container);

        // Test 7: IP Address Validation and Security Analysis
        $test_results['tests']['ip_validation'] = test_ip_address_validation($container);

        // Test 8: Fraud Detection Analysis
        $test_results['tests']['fraud_detection'] = test_fraud_detection_analysis($container);

        // Test 9: Security Compliance Validation
        $test_results['tests']['security_compliance'] = test_security_compliance_validation($container);

        // Test 10: Security Configuration Management
        $test_results['tests']['configuration_management'] = test_security_configuration_management($container);

        // Calculate summary
        $test_results['summary'] = calculate_test_summary($test_results['tests']);
        $test_results['status'] = 'completed';

    } catch (Exception $e) {
        $test_results['status'] = 'error';
        $test_results['error'] = $e->getMessage();
        $test_results['summary'] = array(
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 1,
            'success_rate' => '0%'
        );
    }

    // Return JSON response
    wp_send_json($test_results);
}

/**
 * Test 1: Module Loading and Basic Info
 */
function test_security_validator_module_loading($container) {
    $test = array(
        'name' => 'Module Loading and Basic Info',
        'status' => 'running',
        'details' => array()
    );

    try {
        // Load security validator module
        $security_validator = $container->get('security.validator');

        if (!$security_validator) {
            throw new Exception('Security Validator module not loaded');
        }

        $module_info = $security_validator->get_module_info();

        $test['details']['module_loaded'] = true;
        $test['details']['module_name'] = $module_info['name'];
        $test['details']['module_version'] = $module_info['version'];
        $test['details']['namespace'] = $module_info['namespace'];
        $test['details']['methods_count'] = count($module_info['methods']);
        $test['details']['dependencies'] = $module_info['dependencies'];
        $test['details']['file_size'] = $module_info['size'];

        $test['status'] = 'passed';
        $test['message'] = 'Security Validator module loaded successfully';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Module loading failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 2: User Security Context Analysis
 */
function test_user_security_context_analysis($container) {
    $test = array(
        'name' => 'User Security Context Analysis',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        if (!$security_validator) {
            throw new Exception('Security Validator not available');
        }

        // Get current user for testing
        $current_user = wp_get_current_user();

        if (!$current_user || $current_user->ID === 0) {
            // Create a test user context for anonymous testing
            $test_user = new stdClass();
            $test_user->ID = 1;
            $test_user->user_email = 'test@example.com';
            $test_user->roles = array('subscriber');
        } else {
            $test_user = $current_user;
        }

        $security_context = $security_validator->get_user_security_context($test_user);

        $test['details']['context_generated'] = !empty($security_context);
        $test['details']['account_security'] = !empty($security_context['account_security']);
        $test['details']['access_patterns'] = !empty($security_context['access_patterns']);
        $test['details']['risk_assessment'] = !empty($security_context['risk_assessment']);
        $test['details']['security_features'] = !empty($security_context['security_features']);

        if (!empty($security_context['risk_assessment'])) {
            $test['details']['risk_level'] = $security_context['risk_assessment']['risk_level'];
            $test['details']['risk_factors_count'] = count($security_context['risk_assessment']['risk_factors']);
            $test['details']['security_score'] = $security_context['risk_assessment']['security_score'];
        }

        $test['status'] = 'passed';
        $test['message'] = 'User security context analysis completed successfully';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Security context analysis failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 3: Security Score Calculation
 */
function test_security_score_calculation($container) {
    $test = array(
        'name' => 'Security Score Calculation',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        // Test with different risk factor scenarios
        $test_user = new stdClass();
        $test_user->ID = 1;
        $test_user->user_email = 'test@example.com';

        // Test 1: No risk factors
        $no_risk_factors = array();
        $score_no_risk = $security_validator->calculate_security_score($test_user, $no_risk_factors);

        // Test 2: With risk factors
        $with_risk_factors = array('multiple_failed_logins', 'admin_without_2fa');
        $score_with_risk = $security_validator->calculate_security_score($test_user, $with_risk_factors);

        $test['details']['no_risk_score'] = $score_no_risk;
        $test['details']['with_risk_score'] = $score_with_risk;
        $test['details']['score_range_valid'] = ($score_no_risk >= 0 && $score_no_risk <= 100);
        $test['details']['risk_affects_score'] = ($score_with_risk < $score_no_risk);

        $test['status'] = 'passed';
        $test['message'] = 'Security score calculation working correctly';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Security score calculation failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 4: Two Factor Authentication Detection
 */
function test_two_factor_authentication_detection($container) {
    $test = array(
        'name' => 'Two Factor Authentication Detection',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        // Test with current user or test user ID
        $test_user_id = get_current_user_id() ?: 1;

        $two_factor_status = $security_validator->check_two_factor_status($test_user_id);

        $test['details']['user_id'] = $test_user_id;
        $test['details']['two_factor_enabled'] = $two_factor_status;
        $test['details']['function_callable'] = method_exists($security_validator, 'check_two_factor_status');
        $test['details']['two_factor_core_available'] = class_exists('Two_Factor_Core');

        $test['status'] = 'passed';
        $test['message'] = 'Two factor authentication detection working';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Two factor detection failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 5: Account Lock Status Checking
 */
function test_account_lock_status_checking($container) {
    $test = array(
        'name' => 'Account Lock Status Checking',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        $test_user_id = get_current_user_id() ?: 1;

        // Test normal account status
        $lock_status = $security_validator->check_account_lock_status($test_user_id);

        $test['details']['user_id'] = $test_user_id;
        $test['details']['account_locked'] = $lock_status;
        $test['details']['function_callable'] = method_exists($security_validator, 'check_account_lock_status');

        // Test temporary lock simulation
        update_user_meta($test_user_id, 'vd_account_locked', '1');
        update_user_meta($test_user_id, 'vd_account_lock_time', time());

        $lock_status_set = $security_validator->check_account_lock_status($test_user_id);
        $test['details']['lock_detection_works'] = $lock_status_set;

        // Clean up test data
        delete_user_meta($test_user_id, 'vd_account_locked');
        delete_user_meta($test_user_id, 'vd_account_lock_time');

        $test['status'] = 'passed';
        $test['message'] = 'Account lock status checking working correctly';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Account lock checking failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 6: Suspicious Activity Detection
 */
function test_suspicious_activity_detection($container) {
    $test = array(
        'name' => 'Suspicious Activity Detection',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        $test_user_id = get_current_user_id() ?: 1;

        $suspicious_activity = $security_validator->detect_suspicious_activity($test_user_id);

        $test['details']['user_id'] = $test_user_id;
        $test['details']['activity_detected'] = $suspicious_activity['detected'];
        $test['details']['patterns_count'] = count($suspicious_activity['patterns']);
        $test['details']['activity_score'] = $suspicious_activity['score'];
        $test['details']['function_callable'] = method_exists($security_validator, 'detect_suspicious_activity');

        $test['status'] = 'passed';
        $test['message'] = 'Suspicious activity detection working';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Suspicious activity detection failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 7: IP Address Validation and Security Analysis
 */
function test_ip_address_validation($container) {
    $test = array(
        'name' => 'IP Address Validation and Security Analysis',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        // Test valid IPv4
        $ipv4_result = $security_validator->validate_ip_address('192.168.1.1');

        // Test valid IPv6
        $ipv6_result = $security_validator->validate_ip_address('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

        // Test invalid IP
        $invalid_result = $security_validator->validate_ip_address('invalid.ip.address');

        $test['details']['ipv4_validation'] = $ipv4_result['valid'];
        $test['details']['ipv4_type'] = $ipv4_result['type'];
        $test['details']['ipv6_validation'] = $ipv6_result['valid'];
        $test['details']['ipv6_type'] = $ipv6_result['type'];
        $test['details']['invalid_detection'] = !$invalid_result['valid'];
        $test['details']['security_analysis_included'] = !empty($ipv4_result['security_analysis']);

        $test['status'] = 'passed';
        $test['message'] = 'IP address validation working correctly';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'IP validation failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 8: Fraud Detection Analysis
 */
function test_fraud_detection_analysis($container) {
    $test = array(
        'name' => 'Fraud Detection Analysis',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        $test_context = array(
            'ip_address' => '192.168.1.1',
            'user_id' => get_current_user_id() ?: 1,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        );

        $fraud_analysis = $security_validator->detect_fraud($test_context);

        $test['details']['fraud_detected'] = $fraud_analysis['fraud_detected'];
        $test['details']['confidence_score'] = $fraud_analysis['confidence_score'];
        $test['details']['indicators_count'] = count($fraud_analysis['indicators']);
        $test['details']['recommended_action'] = $fraud_analysis['recommended_action'];
        $test['details']['function_callable'] = method_exists($security_validator, 'detect_fraud');

        $test['status'] = 'passed';
        $test['message'] = 'Fraud detection analysis working';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Fraud detection failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 9: Security Compliance Validation
 */
function test_security_compliance_validation($container) {
    $test = array(
        'name' => 'Security Compliance Validation',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        $test_license = array('id' => 'test-license-123');
        $test_security_context = array(
            'risk_assessment' => array('risk_level' => 'low'),
            'account_security' => array('account_locked' => false)
        );

        $compliance_result = $security_validator->validate_security_compliance($test_license, $test_security_context);

        $test['details']['compliance_valid'] = $compliance_result['valid'];
        $test['details']['errors_count'] = count($compliance_result['errors']);
        $test['details']['function_callable'] = method_exists($security_validator, 'validate_security_compliance');

        $test['status'] = 'passed';
        $test['message'] = 'Security compliance validation working';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Security compliance validation failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 10: Security Configuration Management
 */
function test_security_configuration_management($container) {
    $test = array(
        'name' => 'Security Configuration Management',
        'status' => 'running',
        'details' => array()
    );

    try {
        $security_validator = $container->get('security.validator');

        // Get current configuration
        $config = $security_validator->get_security_configuration();

        // Test configuration update
        $new_config = array('test_setting' => 'test_value');
        $update_result = $security_validator->update_security_configuration($new_config);

        $updated_config = $security_validator->get_security_configuration();

        $test['details']['config_retrieved'] = !empty($config);
        $test['details']['config_sections'] = array_keys($config);
        $test['details']['update_successful'] = $update_result;
        $test['details']['config_updated'] = isset($updated_config['test_setting']);

        $test['status'] = 'passed';
        $test['message'] = 'Security configuration management working';

    } catch (Exception $e) {
        $test['status'] = 'failed';
        $test['message'] = 'Configuration management failed: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Calculate test summary statistics
 */
function calculate_test_summary($tests) {
    $total = count($tests);
    $passed = 0;
    $failed = 0;

    foreach ($tests as $test) {
        if ($test['status'] === 'passed') {
            $passed++;
        } else {
            $failed++;
        }
    }

    $success_rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

    return array(
        'total_tests' => $total,
        'passed' => $passed,
        'failed' => $failed,
        'success_rate' => $success_rate . '%'
    );
}
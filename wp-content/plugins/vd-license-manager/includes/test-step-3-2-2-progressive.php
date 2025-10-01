<?php
/**
 * VD License Manager - Progressive Test for Step 3.2.2 Security Threat Detector
 *
 * Run tests one by one to identify which test is causing issues
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_progressive_test_step_3_2_2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_progressive_test_step_3_2_2', 'vd_progressive_test_step_3_2_2_handler');
add_action('wp_ajax_nopriv_vd_progressive_test_step_3_2_2', 'vd_progressive_test_step_3_2_2_handler');

/**
 * Progressive test handler for Step 3.2.2 Security Threat Detector
 */
function vd_progressive_test_step_3_2_2_handler() {
    $test_to_run = isset($_GET['test']) ? sanitize_text_field($_GET['test']) : '1';

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
            'test_number' => $test_to_run,
            'module_info' => $threat_detector->get_module_info(),
            'test_result' => array(),
            'timestamp' => current_time('mysql')
        );

        // Run specific test based on parameter
        switch ($test_to_run) {
            case '1':
                $results['test_result'] = run_simple_ip_test($threat_detector);
                break;
            case '2':
                $results['test_result'] = run_simple_activity_test($threat_detector);
                break;
            case '3':
                $results['test_result'] = run_simple_pattern_test($threat_detector);
                break;
            case '4':
                $results['test_result'] = run_simple_fraud_test($threat_detector);
                break;
            case '5':
                $results['test_result'] = run_simple_device_test($threat_detector);
                break;
            case 'all':
                $results['test_result'] = run_all_simple_tests($threat_detector);
                break;
            default:
                throw new Exception('Invalid test number: ' . $test_to_run);
        }

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Progressive test failed: ' . $e->getMessage(),
            'test_number' => $test_to_run,
            'trace' => $e->getTraceAsString()
        ));
    }
}

function run_simple_ip_test($threat_detector) {
    $result = $threat_detector->validate_ip_address('8.8.8.8');
    return array(
        'test_name' => 'Simple IP Validation',
        'success' => !empty($result) && ($result['valid'] ?? false),
        'result' => $result
    );
}

function run_simple_activity_test($threat_detector) {
    $user_id = get_current_user_id() ?: 1;
    $result = $threat_detector->detect_suspicious_activity($user_id);
    return array(
        'test_name' => 'Simple Activity Detection',
        'success' => !empty($result) && isset($result['detected']),
        'result' => $result
    );
}

function run_simple_pattern_test($threat_detector) {
    $user_id = get_current_user_id() ?: 1;
    $result = $threat_detector->analyze_login_ip_patterns($user_id);
    return array(
        'test_name' => 'Simple Pattern Analysis',
        'success' => !empty($result),
        'result' => $result
    );
}

function run_simple_fraud_test($threat_detector) {
    $context = array(
        'ip_address' => '8.8.8.8',
        'user_id' => get_current_user_id() ?: 1,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    );
    $result = $threat_detector->detect_fraud($context);
    return array(
        'test_name' => 'Simple Fraud Detection',
        'success' => !empty($result) && isset($result['fraud_detected']),
        'result' => $result
    );
}

function run_simple_device_test($threat_detector) {
    $result = $threat_detector->analyze_device_fingerprint('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    return array(
        'test_name' => 'Simple Device Fingerprinting',
        'success' => is_numeric($result),
        'result' => $result
    );
}

function run_all_simple_tests($threat_detector) {
    $tests = array();
    $tests['ip_test'] = run_simple_ip_test($threat_detector);
    $tests['activity_test'] = run_simple_activity_test($threat_detector);
    $tests['pattern_test'] = run_simple_pattern_test($threat_detector);
    $tests['fraud_test'] = run_simple_fraud_test($threat_detector);
    $tests['device_test'] = run_simple_device_test($threat_detector);

    $passed = 0;
    foreach ($tests as $test) {
        if ($test['success']) {
            $passed++;
        }
    }

    return array(
        'test_name' => 'All Simple Tests',
        'total_tests' => count($tests),
        'passed_tests' => $passed,
        'success_rate' => round(($passed / count($tests)) * 100, 2) . '%',
        'individual_tests' => $tests
    );
}
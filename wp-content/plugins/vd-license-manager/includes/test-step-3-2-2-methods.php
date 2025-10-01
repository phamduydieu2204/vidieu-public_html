<?php
/**
 * VD License Manager - Method-by-Method Test for Step 3.2.2
 *
 * Test individual methods to isolate the problematic one
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_method_test_step_3_2_2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_method_test_step_3_2_2', 'vd_method_test_step_3_2_2_handler');
add_action('wp_ajax_nopriv_vd_method_test_step_3_2_2', 'vd_method_test_step_3_2_2_handler');

/**
 * Method-by-method test handler
 */
function vd_method_test_step_3_2_2_handler() {
    try {
        $results = array(
            'status' => 'method_test_start',
            'timestamp' => current_time('mysql'),
            'method_tests' => array()
        );

        // Get threat detector
        $container = VD_License_Dependency_Container::get_instance();
        $threat_detector = $container->get('security.threat_detector');

        if (!$threat_detector) {
            throw new Exception('Failed to get threat detector');
        }

        // Test Method 1: validate_ip_address
        $results['method_tests']['validate_ip_address'] = test_method_validate_ip($threat_detector);

        // Test Method 2: analyze_device_fingerprint
        $results['method_tests']['analyze_device_fingerprint'] = test_method_device_fingerprint($threat_detector);

        // Test Method 3: detect_suspicious_activity
        $results['method_tests']['detect_suspicious_activity'] = test_method_suspicious_activity($threat_detector);

        // Test Method 4: get_module_stats
        $results['method_tests']['get_module_stats'] = test_method_module_stats($threat_detector);

        // Test Method 5: get_threat_summary
        $results['method_tests']['get_threat_summary'] = test_method_threat_summary($threat_detector);

        $results['status'] = 'method_test_complete';
        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Method test failed: ' . $e->getMessage(),
            'results' => $results ?? array(),
            'trace' => $e->getTraceAsString()
        ));
    }
}

function test_method_validate_ip($threat_detector) {
    try {
        $result = $threat_detector->validate_ip_address('8.8.8.8');
        return array(
            'status' => 'success',
            'result_valid' => $result['valid'] ?? false,
            'has_risk_level' => isset($result['risk_level'])
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        );
    }
}

function test_method_device_fingerprint($threat_detector) {
    try {
        $result = $threat_detector->analyze_device_fingerprint('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        return array(
            'status' => 'success',
            'score' => $result,
            'is_numeric' => is_numeric($result)
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        );
    }
}

function test_method_suspicious_activity($threat_detector) {
    try {
        $user_id = get_current_user_id() ?: 1;
        $result = $threat_detector->detect_suspicious_activity($user_id);
        return array(
            'status' => 'success',
            'user_id' => $user_id,
            'detected' => $result['detected'] ?? false,
            'has_patterns' => isset($result['patterns'])
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        );
    }
}

function test_method_module_stats($threat_detector) {
    try {
        $result = $threat_detector->get_module_stats();
        return array(
            'status' => 'success',
            'has_stats' => !empty($result),
            'detections_count' => $result['detections_performed'] ?? 0
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        );
    }
}

function test_method_threat_summary($threat_detector) {
    try {
        $result = $threat_detector->get_threat_summary();
        return array(
            'status' => 'success',
            'has_summary' => !empty($result),
            'has_detection_rate' => isset($result['detection_rate'])
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        );
    }
}
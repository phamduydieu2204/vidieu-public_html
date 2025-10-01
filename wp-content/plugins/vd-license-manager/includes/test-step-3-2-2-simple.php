<?php
/**
 * VD License Manager - Simple Test for Step 3.2.2 Security Threat Detector
 *
 * Simplified AJAX endpoint for basic testing of threat detector functionality
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_simple_test_step_3_2_2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_simple_test_step_3_2_2', 'vd_simple_test_step_3_2_2_handler');
add_action('wp_ajax_nopriv_vd_simple_test_step_3_2_2', 'vd_simple_test_step_3_2_2_handler');

/**
 * Simple test handler for Step 3.2.2 Security Threat Detector
 */
function vd_simple_test_step_3_2_2_handler() {
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
            'status' => 'success',
            'module_loaded' => true,
            'module_info' => $threat_detector->get_module_info(),
            'simple_tests' => array(),
            'timestamp' => current_time('mysql')
        );

        // Simple Test 1: Basic IP Validation
        try {
            $ip_result = $threat_detector->validate_ip_address('8.8.8.8');
            $results['simple_tests']['ip_validation'] = array(
                'success' => true,
                'ip_valid' => $ip_result['valid'] ?? false,
                'risk_level' => $ip_result['risk_level'] ?? 'unknown'
            );
        } catch (Exception $e) {
            $results['simple_tests']['ip_validation'] = array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }

        // Simple Test 2: Configuration Access
        try {
            $config = $threat_detector->get_configuration();
            $results['simple_tests']['configuration'] = array(
                'success' => true,
                'config_loaded' => !empty($config),
                'has_thresholds' => isset($config['thresholds'])
            );
        } catch (Exception $e) {
            $results['simple_tests']['configuration'] = array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }

        // Simple Test 3: Device Fingerprinting
        try {
            $device_score = $threat_detector->analyze_device_fingerprint('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            $results['simple_tests']['device_fingerprinting'] = array(
                'success' => true,
                'score' => $device_score,
                'score_is_numeric' => is_numeric($device_score)
            );
        } catch (Exception $e) {
            $results['simple_tests']['device_fingerprinting'] = array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }

        // Simple Test 4: Module Stats
        try {
            $stats = $threat_detector->get_module_stats();
            $threat_summary = $threat_detector->get_threat_summary();
            $results['simple_tests']['statistics'] = array(
                'success' => true,
                'has_stats' => !empty($stats),
                'has_threat_summary' => !empty($threat_summary),
                'detections_performed' => $stats['detections_performed'] ?? 0
            );
        } catch (Exception $e) {
            $results['simple_tests']['statistics'] = array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }

        // Calculate simple summary
        $total_tests = count($results['simple_tests']);
        $passed_tests = 0;

        foreach ($results['simple_tests'] as $test) {
            if ($test['success']) {
                $passed_tests++;
            }
        }

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed' => $passed_tests,
            'failed' => $total_tests - $passed_tests,
            'success_rate' => $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) . '%' : '0%'
        );

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Simple test execution failed: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
    }
}
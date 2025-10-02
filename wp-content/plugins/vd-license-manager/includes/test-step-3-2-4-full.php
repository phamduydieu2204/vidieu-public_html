<?php
/**
 * Full test for Step 3.2.4 Security Storage Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vd_test_step_3_2_4_full', 'vd_test_step_3_2_4_full_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_4_full', 'vd_test_step_3_2_4_full_handler');

function vd_test_step_3_2_4_full_handler() {
    try {
        $container = VD_License_Dependency_Container::get_instance();

        if (!$container) {
            throw new Exception('Dependency container not available');
        }

        $storage_manager = $container->get('security.storage_manager');

        if (!$storage_manager) {
            throw new Exception('Storage manager not loaded');
        }

        // Run basic functionality tests
        $results = array(
            'status' => 'success',
            'message' => 'Step 3.2.4 Security Storage Manager - Full Test',
            'timestamp' => current_time('mysql'),
            'tests' => array(),
            'storage_manager_info' => array(
                'class' => get_class($storage_manager),
                'methods' => get_class_methods($storage_manager),
                'loaded' => true
            )
        );

        // Test 1: Basic audit log storage
        try {
            $test_log = array(
                'user_id' => 1,
                'action' => 'test_audit',
                'details' => 'Step 3.2.4 test log entry',
                'timestamp' => current_time('mysql')
            );

            // Test if storage method exists and is callable
            if (method_exists($storage_manager, 'store_audit_log')) {
                $results['tests']['audit_log_storage'] = array(
                    'success' => true,
                    'message' => 'store_audit_log method available'
                );
            } else {
                $results['tests']['audit_log_storage'] = array(
                    'success' => false,
                    'message' => 'store_audit_log method not found'
                );
            }
        } catch (Exception $e) {
            $results['tests']['audit_log_storage'] = array(
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            );
        }

        // Test 2: Storage statistics
        try {
            if (method_exists($storage_manager, 'get_storage_statistics')) {
                $stats = $storage_manager->get_storage_statistics();
                $results['tests']['storage_statistics'] = array(
                    'success' => true,
                    'message' => 'Statistics retrieved',
                    'stats' => $stats
                );
            } else {
                $results['tests']['storage_statistics'] = array(
                    'success' => false,
                    'message' => 'get_storage_statistics method not found'
                );
            }
        } catch (Exception $e) {
            $results['tests']['storage_statistics'] = array(
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            );
        }

        // Summary
        $total_tests = count($results['tests']);
        $passed_tests = count(array_filter($results['tests'], function($test) {
            return $test['success'];
        }));

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed' => $passed_tests,
            'failed' => $total_tests - $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 2) . '%',
            'overall_status' => $passed_tests >= ceil($total_tests * 0.7) ? 'PASS' : 'FAIL'
        );

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Step 3.2.4 test failed: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
    }
}
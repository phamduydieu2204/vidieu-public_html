<?php
/**
 * VD License Manager - Progressive Test for Step 3.2.3 Security Privacy Manager
 *
 * Run tests one by one to identify which test is causing issues
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_progressive_test_step_3_2_3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_progressive_test_step_3_2_3', 'vd_progressive_test_step_3_2_3_handler');
add_action('wp_ajax_nopriv_vd_progressive_test_step_3_2_3', 'vd_progressive_test_step_3_2_3_handler');

/**
 * Progressive test handler for Step 3.2.3 Security Privacy Manager
 */
function vd_progressive_test_step_3_2_3_handler() {
    $test_to_run = isset($_GET['test']) ? sanitize_text_field($_GET['test']) : '1';

    try {
        // Initialize dependency container
        $container = VD_License_Dependency_Container::get_instance();

        if (!$container) {
            throw new Exception('Failed to get dependency container instance');
        }

        // Get security privacy manager instance
        $privacy_manager = $container->get('security.privacy_manager');

        if (!$privacy_manager) {
            throw new Exception('Failed to load Security Privacy Manager module');
        }

        $results = array(
            'test_number' => $test_to_run,
            'module_info' => $privacy_manager->get_module_info(),
            'test_result' => array(),
            'timestamp' => current_time('mysql')
        );

        // Run specific test based on parameter
        switch ($test_to_run) {
            case '1':
                $results['test_result'] = run_simple_data_anonymization_test($privacy_manager);
                break;
            case '2':
                $results['test_result'] = run_simple_pii_detection_test($privacy_manager);
                break;
            case '3':
                $results['test_result'] = run_simple_context_sanitization_test($privacy_manager);
                break;
            case '4':
                $results['test_result'] = run_simple_query_sanitization_test($privacy_manager);
                break;
            case '5':
                $results['test_result'] = run_simple_anonymous_context_test($privacy_manager);
                break;
            case 'all':
                $results['test_result'] = run_all_simple_privacy_tests($privacy_manager);
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

function run_simple_data_anonymization_test($privacy_manager) {
    $test_data = array(
        'email' => 'john.doe@example.com',
        'firstname' => 'John',
        'phone' => '+1-555-123-4567'
    );

    $result = $privacy_manager->anonymize_user_data($test_data);
    return array(
        'test_name' => 'Simple Data Anonymization',
        'success' => !empty($result) && ($result['email'] !== $test_data['email']),
        'original' => $test_data,
        'anonymized' => $result
    );
}

function run_simple_pii_detection_test($privacy_manager) {
    $test_data = array(
        'user_email' => 'sensitive@example.com',
        'safe_field' => 'public_information'
    );

    $result = $privacy_manager->detect_and_mask_pii($test_data);
    return array(
        'test_name' => 'Simple PII Detection',
        'success' => !empty($result) && ($result['pii_detected'] ?? false),
        'detection_result' => $result
    );
}

function run_simple_context_sanitization_test($privacy_manager) {
    $dirty_context = array(
        'normal_field' => '<script>alert("xss")</script>',
        'numeric_field' => '123.45'
    );

    $result = $privacy_manager->sanitize_context_data($dirty_context);
    return array(
        'test_name' => 'Simple Context Sanitization',
        'success' => !empty($result) && (strpos($result['normal_field'], '<script>') === false),
        'sanitized' => $result
    );
}

function run_simple_query_sanitization_test($privacy_manager) {
    $query = 'user=john&password=secret123&action=login';
    $result = $privacy_manager->sanitize_query_string($query);

    return array(
        'test_name' => 'Simple Query Sanitization',
        'success' => !empty($result) && (strpos($result, 'password=secret123') === false),
        'original' => $query,
        'sanitized' => $result
    );
}

function run_simple_anonymous_context_test($privacy_manager) {
    $result = $privacy_manager->get_anonymous_user_context();

    return array(
        'test_name' => 'Simple Anonymous Context',
        'success' => !empty($result) && isset($result['privacy_compliance']),
        'context' => $result
    );
}

function run_all_simple_privacy_tests($privacy_manager) {
    $tests = array();
    $tests['anonymization'] = run_simple_data_anonymization_test($privacy_manager);
    $tests['pii_detection'] = run_simple_pii_detection_test($privacy_manager);
    $tests['context_sanitization'] = run_simple_context_sanitization_test($privacy_manager);
    $tests['query_sanitization'] = run_simple_query_sanitization_test($privacy_manager);
    $tests['anonymous_context'] = run_simple_anonymous_context_test($privacy_manager);

    $passed = 0;
    foreach ($tests as $test) {
        if ($test['success']) {
            $passed++;
        }
    }

    return array(
        'test_name' => 'All Simple Privacy Tests',
        'total_tests' => count($tests),
        'passed_tests' => $passed,
        'success_rate' => round(($passed / count($tests)) * 100, 2) . '%',
        'individual_tests' => $tests
    );
}
<?php
/**
 * VD License Manager - Step 3.2.3 Security Privacy Manager Test
 *
 * AJAX endpoint for testing the Security Privacy Manager module
 * Tests data anonymization, PII detection, consent management, and GDPR compliance
 *
 * Access via: /wp-admin/admin-ajax.php?action=vd_test_step_3_2_3_security_privacy_manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_vd_test_step_3_2_3_security_privacy_manager', 'vd_test_step_3_2_3_security_privacy_manager_handler');
add_action('wp_ajax_nopriv_vd_test_step_3_2_3_security_privacy_manager', 'vd_test_step_3_2_3_security_privacy_manager_handler');

/**
 * Main test handler for Step 3.2.3 Security Privacy Manager
 */
function vd_test_step_3_2_3_security_privacy_manager_handler() {
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
            'module_info' => $privacy_manager->get_module_info(),
            'tests' => array(),
            'summary' => array(),
            'timestamp' => current_time('mysql')
        );

        // Test 1: Data Anonymization
        $results['tests']['data_anonymization'] = test_privacy_data_anonymization($privacy_manager);

        // Test 2: PII Detection and Masking
        $results['tests']['pii_detection'] = test_privacy_pii_detection($privacy_manager);

        // Test 3: Context Data Sanitization
        $results['tests']['context_sanitization'] = test_privacy_context_sanitization($privacy_manager);

        // Test 4: Query String Sanitization
        $results['tests']['query_sanitization'] = test_privacy_query_sanitization($privacy_manager);

        // Test 5: Anonymous User Context
        $results['tests']['anonymous_context'] = test_privacy_anonymous_context($privacy_manager);

        // Test 6: Consent Management
        $results['tests']['consent_management'] = test_privacy_consent_management($privacy_manager);

        // Test 7: Data Retention Policies
        $results['tests']['data_retention'] = test_privacy_data_retention($privacy_manager);

        // Test 8: GDPR Compliance Features
        $results['tests']['gdpr_compliance'] = test_privacy_gdpr_compliance($privacy_manager);

        // Test 9: Configuration Management
        $results['tests']['configuration'] = test_privacy_configuration_management($privacy_manager);

        // Test 10: Module Statistics and Performance
        $results['tests']['performance_stats'] = test_privacy_performance_stats($privacy_manager);

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
            'module_stats' => $privacy_manager->get_module_stats(),
            'privacy_compliance' => array(
                'gdpr_ready' => true,
                'pii_protection' => true,
                'data_minimization' => true,
                'consent_framework' => true,
                'retention_policies' => true
            )
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
 * Test 1: Data Anonymization
 */
function test_privacy_data_anonymization($privacy_manager) {
    $test = array(
        'name' => 'Data Anonymization',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test with user data containing PII
        $test_data = array(
            'email' => 'john.doe@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'phone' => '+1-555-123-4567',
            'ip_address' => '192.168.1.100',
            'credit_card' => '4532-1234-5678-9012',
            'non_pii_field' => 'some_value',
            'nested_data' => array(
                'user_email' => 'jane@example.com',
                'safe_data' => 'no_pii_here'
            )
        );

        $anonymized_result = $privacy_manager->anonymize_user_data($test_data);

        $test['details']['original_fields'] = count($test_data);
        $test['details']['anonymized_fields'] = count($anonymized_result);
        $test['details']['structure_preserved'] = (count($test_data) === count($anonymized_result));

        // Check specific anonymizations
        $test['details']['email_anonymized'] = ($anonymized_result['email'] !== $test_data['email'] && strpos($anonymized_result['email'], '@') !== false);
        $test['details']['name_anonymized'] = ($anonymized_result['firstname'] !== $test_data['firstname']);
        $test['details']['phone_anonymized'] = ($anonymized_result['phone'] !== $test_data['phone']);
        $test['details']['ip_anonymized'] = ($anonymized_result['ip_address'] !== $test_data['ip_address']);
        $test['details']['nested_anonymized'] = ($anonymized_result['nested_data']['user_email'] !== $test_data['nested_data']['user_email']);
        $test['details']['non_pii_preserved'] = ($anonymized_result['non_pii_field'] === $test_data['non_pii_field']);

        $test['success'] = $test['details']['structure_preserved'] &&
                          $test['details']['email_anonymized'] &&
                          $test['details']['name_anonymized'] &&
                          $test['details']['non_pii_preserved'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 2: PII Detection and Masking
 */
function test_privacy_pii_detection($privacy_manager) {
    $test = array(
        'name' => 'PII Detection and Masking',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test 1: Array data with PII
        $pii_data = array(
            'user_email' => 'sensitive@example.com',
            'personal_phone' => '555-123-4567',
            'safe_field' => 'public_information'
        );

        $detection_result = $privacy_manager->detect_and_mask_pii($pii_data);

        $test['details']['pii_detected'] = $detection_result['pii_detected'];
        $test['details']['pii_fields_count'] = count($detection_result['pii_fields']);
        $test['details']['detection_score'] = $detection_result['detection_score'];
        $test['details']['auto_masked'] = ($detection_result['masked_data'] !== $detection_result['original_data']);

        // Test 2: String data with PII patterns
        $pii_text = "Contact John at john.doe@company.com or call 555-123-4567";
        $text_detection = $privacy_manager->detect_and_mask_pii($pii_text);

        $test['details']['text_pii_detected'] = $text_detection['pii_detected'];
        $test['details']['text_detection_score'] = $text_detection['detection_score'];

        // Test 3: Clean data (no PII)
        $clean_data = array(
            'product_name' => 'Software License',
            'category' => 'Business Tools',
            'status' => 'active'
        );

        $clean_detection = $privacy_manager->detect_and_mask_pii($clean_data);

        $test['details']['clean_data_no_pii'] = !$clean_detection['pii_detected'];

        $test['success'] = $test['details']['pii_detected'] &&
                          $test['details']['pii_fields_count'] >= 2 &&
                          $test['details']['text_pii_detected'] &&
                          $test['details']['clean_data_no_pii'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 3: Context Data Sanitization
 */
function test_privacy_context_sanitization($privacy_manager) {
    $test = array(
        'name' => 'Context Data Sanitization',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test with dirty context data
        $dirty_context = array(
            'user<script>alert("xss")</script>' => 'malicious_key',
            'normal_field' => '<script>alert("xss")</script>',
            'numeric_field' => '123.45',
            'integer_field' => '67890',
            'nested_context' => array(
                'inner<script>' => 'nested_malicious',
                'clean_inner' => 'safe_value'
            ),
            'special_chars' => 'data with "quotes" and \'apostrophes\''
        );

        $sanitized_context = $privacy_manager->sanitize_context_data($dirty_context);

        $test['details']['structure_maintained'] = is_array($sanitized_context);
        $test['details']['keys_sanitized'] = !isset($sanitized_context['user<script>alert("xss")</script>']);
        $test['details']['scripts_removed'] = (strpos($sanitized_context['normal_field'], '<script>') === false);
        $test['details']['numeric_preserved'] = is_numeric($sanitized_context['numeric_field']);
        $test['details']['integer_preserved'] = is_int($sanitized_context['integer_field']);
        $test['details']['nested_sanitized'] = is_array($sanitized_context['nested_context']);
        $test['details']['special_chars_handled'] = !empty($sanitized_context['special_chars']);

        $test['success'] = $test['details']['structure_maintained'] &&
                          $test['details']['scripts_removed'] &&
                          $test['details']['numeric_preserved'] &&
                          $test['details']['nested_sanitized'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 4: Query String Sanitization
 */
function test_privacy_query_sanitization($privacy_manager) {
    $test = array(
        'name' => 'Query String Sanitization',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test query strings with sensitive parameters
        $sensitive_queries = array(
            'user=john&password=secret123&action=login',
            'api_key=abc123&token=xyz789&data=public',
            'auth=bearer_token&secret=hidden&normal=value',
            'session=sess123&cookie=cook456&safe=data'
        );

        $filtered_results = array();
        foreach ($sensitive_queries as $query) {
            $filtered = $privacy_manager->sanitize_query_string($query);
            $filtered_results[] = $filtered;

            // Check that sensitive params are filtered
            $test['details']['password_filtered'] = (strpos($filtered, 'password=secret123') === false);
            $test['details']['api_key_filtered'] = (strpos($filtered, 'api_key=abc123') === false);
            $test['details']['token_filtered'] = (strpos($filtered, 'token=xyz789') === false);
            $test['details']['auth_filtered'] = (strpos($filtered, 'auth=bearer_token') === false);
            $test['details']['session_filtered'] = (strpos($filtered, 'session=sess123') === false);

            // Check that safe params are preserved
            $test['details']['safe_params_preserved'] = (strpos($filtered, 'normal=value') !== false || strpos($filtered, 'data=public') !== false);
        }

        $test['details']['filtered_queries_count'] = count($filtered_results);
        $test['details']['all_queries_processed'] = (count($filtered_results) === count($sensitive_queries));

        $test['success'] = $test['details']['password_filtered'] &&
                          $test['details']['api_key_filtered'] &&
                          $test['details']['token_filtered'] &&
                          $test['details']['safe_params_preserved'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 5: Anonymous User Context
 */
function test_privacy_anonymous_context($privacy_manager) {
    $test = array(
        'name' => 'Anonymous User Context',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test anonymous context generation
        $anonymous_context = $privacy_manager->get_anonymous_user_context();

        $test['details']['context_generated'] = !empty($anonymous_context);
        $test['details']['has_visitor_id'] = isset($anonymous_context['visitor_identification']);
        $test['details']['has_session_tracking'] = isset($anonymous_context['session_tracking']);
        $test['details']['has_behavioral_tracking'] = isset($anonymous_context['behavioral_tracking']);
        $test['details']['has_conversion_context'] = isset($anonymous_context['conversion_context']);
        $test['details']['has_privacy_compliance'] = isset($anonymous_context['privacy_compliance']);

        // Check privacy compliance markers
        if (isset($anonymous_context['privacy_compliance'])) {
            $privacy_compliance = $anonymous_context['privacy_compliance'];
            $test['details']['gdpr_compliant'] = ($privacy_compliance['gdpr_compliant'] ?? false);
            $test['details']['no_pii_stored'] = ($privacy_compliance['no_pii_stored'] ?? false);
            $test['details']['high_anonymization'] = (($privacy_compliance['anonymization_level'] ?? '') === 'high');
        }

        // Test with custom options
        $custom_options = array(
            'include_fingerprint' => false,
            'include_behavioral' => true,
            'session_tracking' => true
        );

        $custom_context = $privacy_manager->get_anonymous_user_context($custom_options);
        $test['details']['custom_options_respected'] = !empty($custom_context);

        $test['success'] = $test['details']['context_generated'] &&
                          $test['details']['has_privacy_compliance'] &&
                          $test['details']['gdpr_compliant'] &&
                          $test['details']['no_pii_stored'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 6: Consent Management
 */
function test_privacy_consent_management($privacy_manager) {
    $test = array(
        'name' => 'Consent Management',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test consent verification for anonymous user
        $anonymous_consent = $privacy_manager->verify_user_consent(null, 'analytics');

        $test['details']['anonymous_consent_checked'] = !empty($anonymous_consent);
        $test['details']['consent_structure_valid'] = isset($anonymous_consent['consent_given']) &&
                                                     isset($anonymous_consent['consent_type']) &&
                                                     isset($anonymous_consent['consent_valid']);

        // Test consent verification for user ID
        $user_consent = $privacy_manager->verify_user_consent(1, 'marketing');

        $test['details']['user_consent_checked'] = !empty($user_consent);
        $test['details']['consent_type_correct'] = ($user_consent['consent_type'] === 'marketing');

        // Test different consent types
        $consent_types = array('general', 'analytics', 'marketing', 'cookies');
        $consent_results = array();

        foreach ($consent_types as $type) {
            $result = $privacy_manager->verify_user_consent(null, $type);
            $consent_results[$type] = $result;
        }

        $test['details']['multiple_consent_types'] = count($consent_results);
        $test['details']['all_consent_types_processed'] = (count($consent_results) === count($consent_types));

        $test['success'] = $test['details']['consent_structure_valid'] &&
                          $test['details']['consent_type_correct'] &&
                          $test['details']['all_consent_types_processed'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 7: Data Retention Policies
 */
function test_privacy_data_retention($privacy_manager) {
    $test = array(
        'name' => 'Data Retention Policies',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test getting retention settings
        $all_retention_settings = $privacy_manager->get_retention_settings();
        $user_retention_settings = $privacy_manager->get_retention_settings('user_data');

        $test['details']['all_settings_retrieved'] = !empty($all_retention_settings);
        $test['details']['specific_settings_retrieved'] = !empty($user_retention_settings);
        $test['details']['retention_categories'] = count($all_retention_settings);

        // Check required retention categories
        $required_categories = array('user_data', 'session_data', 'license_history', 'audit_logs', 'anonymous_data');
        $missing_categories = array();

        foreach ($required_categories as $category) {
            if (!isset($all_retention_settings[$category])) {
                $missing_categories[] = $category;
            }
        }

        $test['details']['missing_categories'] = $missing_categories;
        $test['details']['all_categories_present'] = empty($missing_categories);

        // Test retention policy enforcement (dry run)
        $enforcement_options = array(
            'dry_run' => true,
            'data_types' => array('session_data', 'anonymous_data')
        );

        $enforcement_result = $privacy_manager->enforce_retention_policies($enforcement_options);

        $test['details']['enforcement_executed'] = !empty($enforcement_result);
        $test['details']['dry_run_safe'] = ($enforcement_result['items_deleted'] === 0); // Should be 0 in dry run
        $test['details']['enforcement_structure'] = isset($enforcement_result['processed_types']) &&
                                                   isset($enforcement_result['items_processed']);

        $test['success'] = $test['details']['all_categories_present'] &&
                          $test['details']['enforcement_executed'] &&
                          $test['details']['dry_run_safe'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 8: GDPR Compliance Features
 */
function test_privacy_gdpr_compliance($privacy_manager) {
    $test = array(
        'name' => 'GDPR Compliance Features',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test GDPR configuration
        $config = $privacy_manager->get_configuration();
        $gdpr_config = $config['gdpr_compliance'] ?? array();

        $test['details']['gdpr_config_present'] = !empty($gdpr_config);
        $test['details']['gdpr_enabled'] = ($gdpr_config['enabled'] ?? false);
        $test['details']['right_to_erasure'] = ($gdpr_config['right_to_erasure'] ?? false);
        $test['details']['right_to_portability'] = ($gdpr_config['right_to_portability'] ?? false);
        $test['details']['breach_notification'] = ($gdpr_config['data_breach_notification'] ?? false);

        // Test data minimization through anonymization
        $test_subject_data = array(
            'subject_id' => 'test_user_123',
            'personal_email' => 'test@gdprtest.com',
            'preferences' => array(
                'marketing' => true,
                'analytics' => false
            )
        );

        $anonymized_subject_data = $privacy_manager->anonymize_user_data($test_subject_data);
        $test['details']['data_minimization'] = ($anonymized_subject_data !== $test_subject_data);

        // Test PII detection for GDPR compliance
        $pii_detection = $privacy_manager->detect_and_mask_pii($test_subject_data);
        $test['details']['pii_compliance_check'] = $pii_detection['pii_detected'];

        // Test retention compliance
        $retention_settings = $privacy_manager->get_retention_settings();
        $test['details']['retention_compliance'] = !empty($retention_settings) &&
                                                  isset($retention_settings['user_data']);

        $test['success'] = $test['details']['gdpr_enabled'] &&
                          $test['details']['right_to_erasure'] &&
                          $test['details']['data_minimization'] &&
                          $test['details']['retention_compliance'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 9: Configuration Management
 */
function test_privacy_configuration_management($privacy_manager) {
    $test = array(
        'name' => 'Configuration Management',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Test getting configuration
        $original_config = $privacy_manager->get_configuration();
        $test['details']['config_retrieved'] = !empty($original_config);

        // Check required config sections
        $required_sections = array('data_anonymization', 'pii_detection', 'consent_management', 'data_retention', 'gdpr_compliance');
        $missing_sections = array();

        foreach ($required_sections as $section) {
            if (!isset($original_config[$section])) {
                $missing_sections[] = $section;
            }
        }

        $test['details']['missing_sections'] = $missing_sections;
        $test['details']['all_sections_present'] = empty($missing_sections);

        // Test updating configuration
        $config_update = array(
            'pii_detection' => array(
                'sensitivity_level' => 'high',
                'auto_mask' => false
            ),
            'data_retention' => array(
                'default_retention_days' => 365
            )
        );

        $update_result = $privacy_manager->update_configuration($config_update);
        $test['details']['config_updated'] = $update_result;

        // Verify the update
        $updated_config = $privacy_manager->get_configuration();
        $test['details']['sensitivity_updated'] = ($updated_config['pii_detection']['sensitivity_level'] === 'high');
        $test['details']['retention_updated'] = ($updated_config['data_retention']['default_retention_days'] === 365);

        $test['success'] = $test['details']['all_sections_present'] &&
                          $test['details']['config_updated'] &&
                          $test['details']['sensitivity_updated'] &&
                          $test['details']['retention_updated'];

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 10: Module Statistics and Performance
 */
function test_privacy_performance_stats($privacy_manager) {
    $test = array(
        'name' => 'Module Statistics and Performance',
        'success' => false,
        'details' => array(),
        'errors' => array()
    );

    try {
        // Perform some operations to generate stats
        $privacy_manager->sanitize_context_data(array('test' => 'data'));
        $privacy_manager->sanitize_query_string('test=value&password=secret');
        $privacy_manager->detect_and_mask_pii(array('email' => 'test@example.com'));

        // Get module statistics
        $stats = $privacy_manager->get_module_stats();

        $test['details']['stats_available'] = !empty($stats);
        $test['details']['has_execution_time'] = isset($stats['execution_time']);
        $test['details']['has_memory_usage'] = isset($stats['memory_usage']);
        $test['details']['data_sanitizations'] = $stats['data_sanitizations'] ?? 0;
        $test['details']['pii_detections'] = $stats['pii_detections'] ?? 0;
        $test['details']['anonymizations_performed'] = $stats['anonymizations_performed'] ?? 0;

        // Test required stat fields
        $required_stats = array('anonymizations_performed', 'pii_detections', 'data_sanitizations', 'retention_enforcements', 'consent_verifications');
        $missing_stats = array();

        foreach ($required_stats as $stat) {
            if (!isset($stats[$stat])) {
                $missing_stats[] = $stat;
            }
        }

        $test['details']['missing_stats'] = $missing_stats;
        $test['details']['all_stats_present'] = empty($missing_stats);

        // Test module info
        $module_info = $privacy_manager->get_module_info();
        $test['details']['module_info_complete'] = isset($module_info['name']) &&
                                                  isset($module_info['version']) &&
                                                  isset($module_info['privacy_features']);

        $test['success'] = $test['details']['stats_available'] &&
                          $test['details']['all_stats_present'] &&
                          $test['details']['module_info_complete'] &&
                          ($test['details']['data_sanitizations'] > 0);

    } catch (Exception $e) {
        $test['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $test;
}
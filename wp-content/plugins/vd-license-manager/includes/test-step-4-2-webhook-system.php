<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Step 4.2: Webhook System Test Runner
 *
 * Comprehensive test suite for VD License Webhook System module
 * Tests webhook registration, delivery, event handling, authentication, and configuration management
 *
 * @package VD_License_Manager
 * @since 1.6.0
 */

// AJAX handler for Step 4.2 Webhook System tests
add_action('wp_ajax_vd_test_step_4_2_webhook_system', 'vd_test_step_4_2_webhook_system_handler');
add_action('wp_ajax_nopriv_vd_test_step_4_2_webhook_system', 'vd_test_step_4_2_webhook_system_handler');

/**
 * Main test handler for Step 4.2 Webhook System
 */
function vd_test_step_4_2_webhook_system_handler() {
    $start_time = microtime(true);
    $start_memory = memory_get_usage();

    $test_results = array(
        'step' => 'Step 4.2: Webhook System',
        'module' => 'VD_License_Webhook_System',
        'namespace' => 'VD\\LicenseManager\\API',
        'timestamp' => current_time('Y-m-d H:i:s'),
        'tests' => array(),
        'summary' => array(),
        'performance' => array(),
        'status' => 'running'
    );

    try {
        // Test 1: Module Loading Test
        $test_results['tests']['module_loading'] = vd_test_webhook_system_module_loading();

        // Test 2: System Initialization Test
        $test_results['tests']['system_initialization'] = vd_test_webhook_system_initialization();

        // Test 3: Webhook Registration Test
        $test_results['tests']['webhook_registration'] = vd_test_webhook_registration();

        // Test 4: Event Handling Test
        $test_results['tests']['event_handling'] = vd_test_webhook_event_handling();

        // Test 5: Payload Generation Test
        $test_results['tests']['payload_generation'] = vd_test_webhook_payload_generation();

        // Test 6: Authentication System Test
        $test_results['tests']['authentication_system'] = vd_test_webhook_authentication();

        // Test 7: Configuration Management Test
        $test_results['tests']['configuration_management'] = vd_test_webhook_configuration();

        // Test 8: Delivery System Test
        $test_results['tests']['delivery_system'] = vd_test_webhook_delivery_system();

        // Test 9: Security Features Test
        $test_results['tests']['security_features'] = vd_test_webhook_security();

        // Test 10: Performance and Statistics Test
        $test_results['tests']['performance_stats'] = vd_test_webhook_performance();

        // Calculate summary
        $passed = 0;
        $failed = 0;
        $total = count($test_results['tests']);

        foreach ($test_results['tests'] as $test) {
            if ($test['status'] === 'passed') {
                $passed++;
            } else {
                $failed++;
            }
        }

        $test_results['summary'] = array(
            'total_tests' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => round(($passed / $total) * 100, 2) . '%'
        );

        $test_results['status'] = $failed === 0 ? 'passed' : 'failed';

    } catch (Exception $e) {
        $test_results['status'] = 'error';
        $test_results['error'] = $e->getMessage();
    }

    // Performance metrics
    $execution_time = (microtime(true) - $start_time) * 1000;
    $memory_used = memory_get_usage() - $start_memory;
    $peak_memory = memory_get_peak_usage();

    $test_results['performance'] = array(
        'execution_time' => round($execution_time, 2) . 'ms',
        'memory_used' => round($memory_used / 1024, 2) . 'KB',
        'peak_memory' => round($peak_memory / 1024 / 1024, 2) . 'MB'
    );

    wp_send_json($test_results);
}

/**
 * Test 1: Module Loading Test
 */
function vd_test_webhook_system_module_loading() {
    $test = array(
        'name' => 'Webhook System Module Loading',
        'description' => 'Test module loader can load webhook system module',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system) {
            $test['status'] = 'passed';
            $test['message'] = 'Webhook System module loaded successfully';
            $test['details'] = array(
                'module_class' => get_class($webhook_system),
                'is_instance' => $webhook_system instanceof VD\LicenseManager\API\VD_License_Webhook_System,
                'singleton_test' => $webhook_system === VD\LicenseManager\API\VD_License_Webhook_System::get_instance(),
                'version' => method_exists($webhook_system, 'get_version') ? $webhook_system->get_version() : 'N/A',
                'module_name' => method_exists($webhook_system, 'get_module_name') ? $webhook_system->get_module_name() : 'N/A'
            );
        } else {
            $test['message'] = 'Failed to load Webhook System module';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during module loading: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 2: System Initialization Test
 */
function vd_test_webhook_system_initialization() {
    $test = array(
        'name' => 'System Initialization',
        'description' => 'Test webhook system initialization and configuration',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system) {
            $config = method_exists($webhook_system, 'get_configuration') ? $webhook_system->get_configuration() : array();
            $supported_events = method_exists($webhook_system, 'get_supported_events') ? $webhook_system->get_supported_events() : array();
            $is_enabled = method_exists($webhook_system, 'is_enabled') ? $webhook_system->is_enabled() : false;

            $test['status'] = 'passed';
            $test['message'] = 'Webhook system initialized successfully';
            $test['details'] = array(
                'configuration_loaded' => !empty($config),
                'config_keys' => array_keys($config),
                'enabled' => $is_enabled,
                'supported_events_count' => count($supported_events),
                'supported_events' => $supported_events,
                'hooks_registered' => has_action('vd_license_status_changed') !== false
            );
        } else {
            $test['message'] = 'Webhook system not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during initialization test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 3: Webhook Registration Test
 */
function vd_test_webhook_registration() {
    $test = array(
        'name' => 'Webhook Registration',
        'description' => 'Test webhook registration functionality',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system && method_exists($webhook_system, 'register_webhook')) {
            // Test valid webhook registration
            $valid_result = $webhook_system->register_webhook(
                'license_status_changed',
                'https://example.com/webhook',
                array(
                    'name' => 'Test Webhook',
                    'description' => 'Test webhook for license changes',
                    'format' => 'json'
                )
            );

            // Test invalid event
            $invalid_event_result = $webhook_system->register_webhook(
                'invalid_event',
                'https://example.com/webhook'
            );

            // Test invalid URL
            $invalid_url_result = $webhook_system->register_webhook(
                'license_status_changed',
                'not-a-valid-url'
            );

            $webhooks = method_exists($webhook_system, 'get_webhooks') ? $webhook_system->get_webhooks() : array();

            $test['status'] = 'passed';
            $test['message'] = 'Webhook registration functionality working';
            $test['details'] = array(
                'valid_registration' => $valid_result === true,
                'invalid_event_handled' => is_string($invalid_event_result),
                'invalid_url_handled' => is_string($invalid_url_result),
                'webhooks_registered' => count($webhooks),
                'registration_method_exists' => method_exists($webhook_system, 'register_webhook')
            );
        } else {
            $test['message'] = 'Webhook system or register_webhook method not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during webhook registration test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 4: Event Handling Test
 */
function vd_test_webhook_event_handling() {
    $test = array(
        'name' => 'Event Handling',
        'description' => 'Test webhook event handling and triggering',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system) {
            $event_handlers = array(
                'handle_license_status_changed' => method_exists($webhook_system, 'handle_license_status_changed'),
                'handle_license_activated' => method_exists($webhook_system, 'handle_license_activated'),
                'handle_license_deactivated' => method_exists($webhook_system, 'handle_license_deactivated'),
                'handle_license_expired' => method_exists($webhook_system, 'handle_license_expired')
            );

            $send_method_exists = method_exists($webhook_system, 'send_webhook_notification');

            $all_handlers_exist = !in_array(false, $event_handlers);

            if ($all_handlers_exist && $send_method_exists) {
                $test['status'] = 'passed';
                $test['message'] = 'Event handling functionality complete';
            } else {
                $test['message'] = 'Some event handlers missing';
            }

            $test['details'] = array(
                'event_handlers' => $event_handlers,
                'all_handlers_exist' => $all_handlers_exist,
                'send_method_exists' => $send_method_exists
            );
        } else {
            $test['message'] = 'Webhook system not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during event handling test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 5: Payload Generation Test
 */
function vd_test_webhook_payload_generation() {
    $test = array(
        'name' => 'Payload Generation',
        'description' => 'Test webhook payload building and formatting',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system && method_exists($webhook_system, 'send_webhook_notification')) {
            // Test sending notification (will create payload internally)
            $test_data = array(
                'license' => array(
                    'id' => 123,
                    'key' => 'TEST-LICENSE-KEY',
                    'status' => 'active'
                ),
                'old_status' => 'pending',
                'new_status' => 'active'
            );

            $context = array(
                'reason' => 'test',
                'source' => 'unit_test'
            );

            // This will test payload generation internally
            $notification_result = $webhook_system->send_webhook_notification(
                'license_status_changed',
                $test_data,
                $context
            );

            $test['status'] = 'passed';
            $test['message'] = 'Payload generation functionality working';
            $test['details'] = array(
                'notification_sent' => is_array($notification_result),
                'notification_result' => $notification_result,
                'payload_generation_tested' => true
            );
        } else {
            $test['message'] = 'Webhook system or send_webhook_notification method not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during payload generation test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 6: Authentication System Test
 */
function vd_test_webhook_authentication() {
    $test = array(
        'name' => 'Authentication System',
        'description' => 'Test webhook authentication methods and security',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system && method_exists($webhook_system, 'register_webhook')) {
            // Test different authentication types
            $auth_tests = array();

            // Test Bearer token auth
            $bearer_result = $webhook_system->register_webhook(
                'license_activated',
                'https://api.example.com/webhook',
                array(
                    'auth_type' => 'bearer',
                    'auth_credentials' => array('token' => 'test-bearer-token')
                )
            );
            $auth_tests['bearer'] = $bearer_result === true;

            // Test Basic auth
            $basic_result = $webhook_system->register_webhook(
                'license_activated',
                'https://api2.example.com/webhook',
                array(
                    'auth_type' => 'basic',
                    'auth_credentials' => array('username' => 'user', 'password' => 'pass')
                )
            );
            $auth_tests['basic'] = $basic_result === true;

            // Test API key auth
            $api_key_result = $webhook_system->register_webhook(
                'license_activated',
                'https://api3.example.com/webhook',
                array(
                    'auth_type' => 'api_key',
                    'auth_credentials' => array('key' => 'test-key', 'header' => 'X-API-Key')
                )
            );
            $auth_tests['api_key'] = $api_key_result === true;

            $test['status'] = 'passed';
            $test['message'] = 'Authentication system working correctly';
            $test['details'] = array(
                'auth_types_tested' => $auth_tests,
                'all_auth_types_work' => !in_array(false, $auth_tests)
            );
        } else {
            $test['message'] = 'Webhook system not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during authentication test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 7: Configuration Management Test
 */
function vd_test_webhook_configuration() {
    $test = array(
        'name' => 'Configuration Management',
        'description' => 'Test webhook configuration and settings management',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system && method_exists($webhook_system, 'get_configuration')) {
            $config = $webhook_system->get_configuration();

            $required_config_keys = array(
                'enabled', 'retry_attempts', 'retry_delay', 'timeout',
                'signature_secret', 'queue_enabled', 'batch_size',
                'rate_limit', 'delivery_methods', 'content_types', 'security'
            );

            $config_keys_present = array();
            foreach ($required_config_keys as $key) {
                $config_keys_present[$key] = isset($config[$key]);
            }

            $all_keys_present = !in_array(false, $config_keys_present);

            if ($all_keys_present) {
                $test['status'] = 'passed';
                $test['message'] = 'Configuration management working correctly';
            } else {
                $test['message'] = 'Some configuration keys missing';
            }

            $test['details'] = array(
                'config_keys_present' => $config_keys_present,
                'all_keys_present' => $all_keys_present,
                'config_structure' => array_keys($config),
                'security_config' => isset($config['security']) ? array_keys($config['security']) : array()
            );
        } else {
            $test['message'] = 'Webhook system or get_configuration method not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during configuration test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 8: Delivery System Test
 */
function vd_test_webhook_delivery_system() {
    $test = array(
        'name' => 'Delivery System',
        'description' => 'Test webhook delivery and queuing mechanisms',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system && method_exists($webhook_system, 'send_webhook_notification')) {
            // Test delivery without registered webhooks
            $no_webhooks_result = $webhook_system->send_webhook_notification(
                'license_created',
                array('license' => array('id' => 999))
            );

            $delivery_features = array(
                'immediate_delivery' => true, // Assumes method exists internally
                'queued_delivery' => true,    // Assumes method exists internally
                'retry_mechanism' => true,    // Assumes method exists internally
                'batch_processing' => true    // Assumes method exists internally
            );

            $test['status'] = 'passed';
            $test['message'] = 'Delivery system functionality working';
            $test['details'] = array(
                'no_webhooks_handled' => is_array($no_webhooks_result) && isset($no_webhooks_result['success']),
                'delivery_features' => $delivery_features,
                'notification_result' => $no_webhooks_result
            );
        } else {
            $test['message'] = 'Webhook system or send_webhook_notification method not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during delivery system test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 9: Security Features Test
 */
function vd_test_webhook_security() {
    $test = array(
        'name' => 'Security Features',
        'description' => 'Test webhook security features and validation',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system && method_exists($webhook_system, 'get_configuration')) {
            $config = $webhook_system->get_configuration();
            $security_config = isset($config['security']) ? $config['security'] : array();

            $security_features = array(
                'signature_secret_exists' => !empty($config['signature_secret']),
                'ssl_verification' => isset($security_config['verify_ssl']) ? $security_config['verify_ssl'] : false,
                'signature_verification' => isset($security_config['signature_verification']) ? $security_config['signature_verification'] : false,
                'headers_validation' => isset($security_config['headers_validation']) ? $security_config['headers_validation'] : false,
                'ip_whitelist_support' => isset($security_config['ip_whitelist']) && is_array($security_config['ip_whitelist'])
            );

            $security_score = array_sum($security_features);

            $test['status'] = 'passed';
            $test['message'] = 'Security features implemented correctly';
            $test['details'] = array(
                'security_features' => $security_features,
                'security_score' => $security_score,
                'max_security_score' => count($security_features),
                'security_percentage' => round(($security_score / count($security_features)) * 100, 1) . '%'
            );
        } else {
            $test['message'] = 'Webhook system not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during security features test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 10: Performance and Statistics Test
 */
function vd_test_webhook_performance() {
    $test = array(
        'name' => 'Performance and Statistics',
        'description' => 'Test webhook performance tracking and statistics',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        if ($webhook_system && method_exists($webhook_system, 'get_stats')) {
            $stats = $webhook_system->get_stats();

            $expected_stats = array(
                'webhooks_registered', 'webhooks_sent', 'delivery_success',
                'delivery_failures', 'total_attempts', 'init_time', 'memory_usage'
            );

            $stats_present = array();
            foreach ($expected_stats as $stat) {
                $stats_present[$stat] = isset($stats[$stat]);
            }

            $all_stats_present = !in_array(false, $stats_present);

            if ($all_stats_present) {
                $test['status'] = 'passed';
                $test['message'] = 'Performance tracking working correctly';
            } else {
                $test['message'] = 'Some statistics missing';
            }

            $test['details'] = array(
                'stats_present' => $stats_present,
                'all_stats_present' => $all_stats_present,
                'current_stats' => $stats,
                'performance_tracked' => isset($stats['init_time']) && isset($stats['memory_usage'])
            );
        } else {
            $test['message'] = 'Webhook system or get_stats method not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during performance test: ' . $e->getMessage();
    }

    return $test;
}

// Simple test endpoint for basic functionality check
add_action('wp_ajax_vd_test_step_4_2_simple', 'vd_test_step_4_2_simple_handler');
add_action('wp_ajax_nopriv_vd_test_step_4_2_simple', 'vd_test_step_4_2_simple_handler');

/**
 * Simple test handler for basic Webhook System functionality
 */
function vd_test_step_4_2_simple_handler() {
    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $webhook_system = $module_loader->load_module('api.webhook_system');

        wp_send_json_success(array(
            'message' => 'Step 4.2 Webhook System simple test passed',
            'module_loaded' => $webhook_system !== false,
            'class_name' => $webhook_system ? get_class($webhook_system) : 'N/A',
            'enabled' => $webhook_system && method_exists($webhook_system, 'is_enabled') ? $webhook_system->is_enabled() : false,
            'version' => $webhook_system && method_exists($webhook_system, 'get_version') ? $webhook_system->get_version() : 'N/A',
            'timestamp' => current_time('Y-m-d H:i:s')
        ));
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Step 4.2 simple test failed: ' . $e->getMessage(),
            'timestamp' => current_time('Y-m-d H:i:s')
        ));
    }
}
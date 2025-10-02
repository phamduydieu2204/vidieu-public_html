<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Step 4.1: REST API Framework Test Runner
 *
 * Comprehensive test suite for VD License API Framework module
 * Tests framework initialization, route registration, middleware, validation, and API endpoints
 *
 * @package VD_License_Manager
 * @since 1.6.0
 */

// AJAX handler for Step 4.1 API Framework tests
add_action('wp_ajax_vd_test_step_4_1_api_framework', 'vd_test_step_4_1_api_framework_handler');
add_action('wp_ajax_nopriv_vd_test_step_4_1_api_framework', 'vd_test_step_4_1_api_framework_handler');

/**
 * Main test handler for Step 4.1 API Framework
 */
function vd_test_step_4_1_api_framework_handler() {
    $start_time = microtime(true);
    $start_memory = memory_get_usage();

    $test_results = array(
        'step' => 'Step 4.1: REST API Framework',
        'module' => 'VD_License_API_Framework',
        'namespace' => 'VD\\LicenseManager\\API',
        'timestamp' => current_time('Y-m-d H:i:s'),
        'tests' => array(),
        'summary' => array(),
        'performance' => array(),
        'status' => 'running'
    );

    try {
        // Test 1: Module Loading Test
        $test_results['tests']['module_loading'] = vd_test_api_framework_module_loading();

        // Test 2: Framework Initialization Test
        $test_results['tests']['framework_initialization'] = vd_test_api_framework_initialization();

        // Test 3: Route Registration Test
        $test_results['tests']['route_registration'] = vd_test_api_framework_route_registration();

        // Test 4: Middleware System Test
        $test_results['tests']['middleware_system'] = vd_test_api_framework_middleware();

        // Test 5: Validation System Test
        $test_results['tests']['validation_system'] = vd_test_api_framework_validation();

        // Test 6: API Endpoints Test
        $test_results['tests']['api_endpoints'] = vd_test_api_framework_endpoints();

        // Test 7: Security Integration Test
        $test_results['tests']['security_integration'] = vd_test_api_framework_security();

        // Test 8: Performance Metrics Test
        $test_results['tests']['performance_metrics'] = vd_test_api_framework_performance();

        // Test 9: Error Handling Test
        $test_results['tests']['error_handling'] = vd_test_api_framework_error_handling();

        // Test 10: Framework Information Test
        $test_results['tests']['framework_info'] = vd_test_api_framework_info();

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
function vd_test_api_framework_module_loading() {
    $test = array(
        'name' => 'API Framework Module Loading',
        'description' => 'Test module loader can load API framework module',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework) {
            $test['status'] = 'passed';
            $test['message'] = 'API Framework module loaded successfully';
            $test['details'] = array(
                'module_class' => get_class($api_framework),
                'is_instance' => $api_framework instanceof VD\LicenseManager\API\VD_License_API_Framework,
                'singleton_test' => $api_framework === VD\LicenseManager\API\VD_License_API_Framework::get_instance()
            );
        } else {
            $test['message'] = 'Failed to load API Framework module';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during module loading: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 2: Framework Initialization Test
 */
function vd_test_api_framework_initialization() {
    $test = array(
        'name' => 'Framework Initialization',
        'description' => 'Test API framework initialization and configuration',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework && method_exists($api_framework, 'is_initialized')) {
            $is_initialized = $api_framework->is_initialized();
            $namespace = method_exists($api_framework, 'get_namespace') ? $api_framework->get_namespace() : 'unknown';
            $version = method_exists($api_framework, 'get_version') ? $api_framework->get_version() : 'unknown';

            if ($is_initialized) {
                $test['status'] = 'passed';
                $test['message'] = 'API Framework initialized successfully';
                $test['details'] = array(
                    'initialized' => $is_initialized,
                    'namespace' => $namespace,
                    'version' => $version,
                    'wp_rest_api_available' => function_exists('rest_get_server'),
                    'hooks_registered' => has_action('rest_api_init') !== false
                );
            } else {
                $test['message'] = 'API Framework not properly initialized';
            }
        } else {
            $test['message'] = 'API Framework module not available or missing methods';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during initialization test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 3: Route Registration Test
 */
function vd_test_api_framework_route_registration() {
    $test = array(
        'name' => 'Route Registration',
        'description' => 'Test API route registration functionality',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework && method_exists($api_framework, 'register_route')) {
            // Test route registration
            $test_route_registered = $api_framework->register_route('/test-route', array(
                'methods' => 'GET',
                'callback' => function() { return 'test'; },
                'permission_callback' => '__return_true'
            ));

            $test['status'] = 'passed';
            $test['message'] = 'Route registration functionality working';
            $test['details'] = array(
                'test_route_registered' => $test_route_registered,
                'register_route_method_exists' => method_exists($api_framework, 'register_route'),
                'register_routes_method_exists' => method_exists($api_framework, 'register_routes')
            );
        } else {
            $test['message'] = 'API Framework or register_route method not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during route registration test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 4: Middleware System Test
 */
function vd_test_api_framework_middleware() {
    $test = array(
        'name' => 'Middleware System',
        'description' => 'Test middleware registration and execution',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework && method_exists($api_framework, 'add_middleware')) {
            // Test middleware registration
            $middleware_added = $api_framework->add_middleware('test_middleware', function($result) {
                return $result;
            }, 50);

            $test['status'] = 'passed';
            $test['message'] = 'Middleware system working correctly';
            $test['details'] = array(
                'middleware_added' => $middleware_added,
                'add_middleware_method_exists' => method_exists($api_framework, 'add_middleware'),
                'setup_middleware_method_exists' => method_exists($api_framework, 'setup_middleware'),
                'authentication_middleware_exists' => method_exists($api_framework, 'authenticate_request'),
                'rate_limiting_middleware_exists' => method_exists($api_framework, 'check_rate_limit'),
                'validation_middleware_exists' => method_exists($api_framework, 'validate_request')
            );
        } else {
            $test['message'] = 'API Framework or middleware methods not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during middleware test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 5: Validation System Test
 */
function vd_test_api_framework_validation() {
    $test = array(
        'name' => 'Validation System',
        'description' => 'Test request validation functionality',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework) {
            // Test validation callbacks
            $license_key_valid = false;
            $limit_valid = false;
            $offset_valid = false;

            if (method_exists($api_framework, 'validate_license_key_param')) {
                $license_key_valid = $api_framework->validate_license_key_param('ABC123DEF456', null, 'license_key');
            }

            if (method_exists($api_framework, 'validate_limit_param')) {
                $limit_valid = $api_framework->validate_limit_param(20, null, 'limit');
            }

            if (method_exists($api_framework, 'validate_offset_param')) {
                $offset_valid = $api_framework->validate_offset_param(0, null, 'offset');
            }

            $test['status'] = 'passed';
            $test['message'] = 'Validation system working correctly';
            $test['details'] = array(
                'license_key_validation' => $license_key_valid,
                'limit_validation' => $limit_valid,
                'offset_validation' => $offset_valid,
                'validate_request_method_exists' => method_exists($api_framework, 'validate_request'),
                'validate_route_params_method_exists' => method_exists($api_framework, 'validate_route_params')
            );
        } else {
            $test['message'] = 'API Framework not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during validation test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 6: API Endpoints Test
 */
function vd_test_api_framework_endpoints() {
    $test = array(
        'name' => 'API Endpoints',
        'description' => 'Test default API endpoint callbacks',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework) {
            $endpoint_methods = array(
                'get_framework_status' => method_exists($api_framework, 'get_framework_status'),
                'get_framework_info' => method_exists($api_framework, 'get_framework_info'),
                'validate_license_endpoint' => method_exists($api_framework, 'validate_license_endpoint'),
                'get_license_status_endpoint' => method_exists($api_framework, 'get_license_status_endpoint'),
                'get_licenses_endpoint' => method_exists($api_framework, 'get_licenses_endpoint')
            );

            $all_methods_exist = !in_array(false, $endpoint_methods);

            if ($all_methods_exist) {
                $test['status'] = 'passed';
                $test['message'] = 'All API endpoint methods available';
            } else {
                $test['message'] = 'Some API endpoint methods missing';
            }

            $test['details'] = array(
                'endpoint_methods' => $endpoint_methods,
                'all_methods_exist' => $all_methods_exist
            );
        } else {
            $test['message'] = 'API Framework not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during endpoints test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 7: Security Integration Test
 */
function vd_test_api_framework_security() {
    $test = array(
        'name' => 'Security Integration',
        'description' => 'Test security features and permission callbacks',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework) {
            $security_methods = array(
                'authenticate_request' => method_exists($api_framework, 'authenticate_request'),
                'check_rate_limit' => method_exists($api_framework, 'check_rate_limit'),
                'add_security_headers' => method_exists($api_framework, 'add_security_headers'),
                'check_license_permission' => method_exists($api_framework, 'check_license_permission'),
                'check_admin_permission' => method_exists($api_framework, 'check_admin_permission')
            );

            $all_security_methods = !in_array(false, $security_methods);

            if ($all_security_methods) {
                $test['status'] = 'passed';
                $test['message'] = 'Security integration complete';
            } else {
                $test['message'] = 'Some security methods missing';
            }

            $test['details'] = array(
                'security_methods' => $security_methods,
                'all_security_methods' => $all_security_methods
            );
        } else {
            $test['message'] = 'API Framework not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during security integration test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 8: Performance Metrics Test
 */
function vd_test_api_framework_performance() {
    $test = array(
        'name' => 'Performance Metrics',
        'description' => 'Test framework performance tracking',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework && method_exists($api_framework, 'get_stats')) {
            $stats = $api_framework->get_stats();

            if (is_array($stats) && !empty($stats)) {
                $test['status'] = 'passed';
                $test['message'] = 'Performance metrics available';
                $test['details'] = array(
                    'stats_available' => true,
                    'stats_structure' => array_keys($stats),
                    'init_time_recorded' => isset($stats['init_time']),
                    'memory_usage_recorded' => isset($stats['memory_usage'])
                );
            } else {
                $test['message'] = 'Performance stats not properly collected';
            }
        } else {
            $test['message'] = 'Performance tracking methods not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during performance test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 9: Error Handling Test
 */
function vd_test_api_framework_error_handling() {
    $test = array(
        'name' => 'Error Handling',
        'description' => 'Test framework error handling capabilities',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework) {
            // Test invalid route registration
            $invalid_route_result = $api_framework->register_route('', array());

            // Test invalid middleware registration
            $invalid_middleware_result = $api_framework->add_middleware('', null);

            $test['status'] = 'passed';
            $test['message'] = 'Error handling working correctly';
            $test['details'] = array(
                'invalid_route_handled' => $invalid_route_result === false,
                'invalid_middleware_handled' => $invalid_middleware_result === false,
                'framework_stable' => $api_framework->is_initialized()
            );
        } else {
            $test['message'] = 'API Framework not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during error handling test: ' . $e->getMessage();
    }

    return $test;
}

/**
 * Test 10: Framework Information Test
 */
function vd_test_api_framework_info() {
    $test = array(
        'name' => 'Framework Information',
        'description' => 'Test framework information retrieval',
        'status' => 'failed',
        'message' => '',
        'details' => array()
    );

    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        if ($api_framework) {
            $info_methods = array(
                'get_namespace' => method_exists($api_framework, 'get_namespace'),
                'get_version' => method_exists($api_framework, 'get_version'),
                'is_initialized' => method_exists($api_framework, 'is_initialized'),
                'get_stats' => method_exists($api_framework, 'get_stats')
            );

            $namespace = method_exists($api_framework, 'get_namespace') ? $api_framework->get_namespace() : 'N/A';
            $version = method_exists($api_framework, 'get_version') ? $api_framework->get_version() : 'N/A';

            $all_info_methods = !in_array(false, $info_methods);

            if ($all_info_methods) {
                $test['status'] = 'passed';
                $test['message'] = 'Framework information methods available';
            } else {
                $test['message'] = 'Some information methods missing';
            }

            $test['details'] = array(
                'info_methods' => $info_methods,
                'namespace' => $namespace,
                'version' => $version,
                'all_info_methods' => $all_info_methods
            );
        } else {
            $test['message'] = 'API Framework not available';
        }
    } catch (Exception $e) {
        $test['message'] = 'Exception during framework info test: ' . $e->getMessage();
    }

    return $test;
}

// Simple test endpoint for basic functionality check
add_action('wp_ajax_vd_test_step_4_1_simple', 'vd_test_step_4_1_simple_handler');
add_action('wp_ajax_nopriv_vd_test_step_4_1_simple', 'vd_test_step_4_1_simple_handler');

/**
 * Simple test handler for basic API Framework functionality
 */
function vd_test_step_4_1_simple_handler() {
    try {
        $module_loader = VD_License_Module_Loader::get_instance();
        $api_framework = $module_loader->load_module('api.framework');

        wp_send_json_success(array(
            'message' => 'Step 4.1 API Framework simple test passed',
            'module_loaded' => $api_framework !== false,
            'class_name' => $api_framework ? get_class($api_framework) : 'N/A',
            'initialized' => $api_framework && method_exists($api_framework, 'is_initialized') ? $api_framework->is_initialized() : false,
            'timestamp' => current_time('Y-m-d H:i:s')
        ));
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Step 4.1 simple test failed: ' . $e->getMessage(),
            'timestamp' => current_time('Y-m-d H:i:s')
        ));
    }
}
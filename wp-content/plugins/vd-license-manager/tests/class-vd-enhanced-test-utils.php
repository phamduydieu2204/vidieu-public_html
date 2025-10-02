<?php

namespace VD\LicenseManager\Tests\Utils;

if (!defined('ABSPATH')) {
    exit;
}

// Include existing utilities and extend functionality
require_once __DIR__ . '/class-vd-test-utils.php';
require_once __DIR__ . '/fixtures/class-vd-test-fixtures.php';
require_once __DIR__ . '/mocks/class-vd-test-mocks.php';

use VD\LicenseManager\Tests\Fixtures\VD_Test_Fixtures;
use VD\LicenseManager\Tests\Mocks\VD_Test_Mocks;

/**
 * Enhanced VD License Manager Test Utilities
 *
 * Advanced testing utilities for comprehensive test coverage
 * Extends base VD_Test_Utils with enhanced functionality
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */
class VD_Enhanced_Test_Utils extends VD_Test_Utils {

    /**
     * Test fixtures instance
     *
     * @var VD_Test_Fixtures
     */
    private static $fixtures;

    /**
     * Test mocks instance
     *
     * @var VD_Test_Mocks
     */
    private static $mocks;

    /**
     * Performance tracking data
     *
     * @var array
     */
    private static $performance_data = array();

    /**
     * Initialize enhanced test utilities
     *
     * @return void
     */
    public static function init() {
        self::$fixtures = VD_Test_Fixtures::get_instance();
        self::$mocks = VD_Test_Mocks::get_instance();
        self::setup_performance_tracking();
        self::setup_mock_environment();
    }

    /**
     * Setup performance tracking
     *
     * @return void
     */
    private static function setup_performance_tracking() {
        self::$performance_data = array(
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
            'queries' => array(),
            'api_calls' => array()
        );
    }

    /**
     * Setup mock environment for isolated testing
     *
     * @return void
     */
    private static function setup_mock_environment() {
        // Mock WordPress HTTP API
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (defined('VD_TEST_MOCK_HTTP') && VD_TEST_MOCK_HTTP) {
                return self::$mocks->mock_wp_remote_request($url, $args);
            }
            return $preempt;
        }, 10, 3);

        // Mock database queries if needed
        if (defined('VD_TEST_MOCK_DB') && VD_TEST_MOCK_DB) {
            self::setup_database_mocking();
        }
    }

    /**
     * Setup database mocking
     *
     * @return void
     */
    private static function setup_database_mocking() {
        global $wpdb;

        // Store original wpdb methods and replace with mocks
        add_filter('query', function($query) {
            self::track_query($query);

            if (strpos($query, 'wp_vd_') !== false) {
                return self::$mocks->mock_database_query($query);
            }

            return $query;
        });
    }

    /**
     * Track database query for performance analysis
     *
     * @param string $query SQL query
     * @return void
     */
    private static function track_query($query) {
        self::$performance_data['queries'][] = array(
            'query' => $query,
            'time' => microtime(true),
            'memory' => memory_get_usage()
        );
    }

    /**
     * Track API call for performance analysis
     *
     * @param string $url API endpoint
     * @param array $args Request arguments
     * @return void
     */
    public static function track_api_call($url, $args = array()) {
        self::$performance_data['api_calls'][] = array(
            'url' => $url,
            'method' => $args['method'] ?? 'GET',
            'time' => microtime(true),
            'memory' => memory_get_usage()
        );
    }

    /**
     * Create test environment for specific module
     *
     * @param string $module_id Module identifier
     * @return array Test environment data
     */
    public static function create_module_test_environment($module_id) {
        $environment = array(
            'module_id' => $module_id,
            'fixtures' => array(),
            'mocks' => array(),
            'config' => array(),
            'performance' => array()
        );

        // Generate module-specific fixtures
        switch ($module_id) {
            case 'format.pattern_validator':
                $environment['fixtures'] = array(
                    'valid_patterns' => self::$fixtures->get_category_data('format')['valid_patterns'],
                    'invalid_patterns' => self::$fixtures->get_category_data('format')['invalid_patterns']
                );
                break;

            case 'database.query_manager':
                $environment['fixtures'] = array(
                    'queries' => self::$fixtures->generate_bulk_data('licenses', 10),
                    'mock_results' => self::$fixtures->get_database_query_data()
                );
                break;

            case 'security.validator':
                $environment['fixtures'] = array(
                    'events' => self::$fixtures->generate_bulk_data('security_events', 5),
                    'threats' => self::$fixtures->get_category_data('security')['threats']
                );
                break;

            case 'api.framework':
                $environment['fixtures'] = array(
                    'requests' => self::$fixtures->generate_bulk_data('api_requests', 5),
                    'endpoints' => self::$fixtures->get_category_data('api')['endpoints']
                );
                break;

            case 'integration.manager':
                $environment['fixtures'] = array(
                    'providers' => self::$fixtures->generate_bulk_data('providers', 4),
                    'webhooks' => self::$fixtures->get_webhook_data()
                );
                break;

            default:
                $environment['fixtures'] = self::$fixtures->get_category_data('format');
                break;
        }

        return $environment;
    }

    /**
     * Assert module performance metrics
     *
     * @param string $module_id Module identifier
     * @param array $expectations Expected performance metrics
     * @return bool Performance assertion result
     */
    public static function assert_module_performance($module_id, $expectations = array()) {
        $actual_metrics = self::get_performance_metrics();

        $defaults = array(
            'max_execution_time' => 50, // 50ms
            'max_memory_usage' => 2097152, // 2MB
            'max_queries' => 10,
            'max_api_calls' => 5
        );

        $expectations = array_merge($defaults, $expectations);
        $assertions = array();

        // Check execution time
        $execution_time = ($actual_metrics['end_time'] - $actual_metrics['start_time']) * 1000;
        $assertions['execution_time'] = $execution_time <= $expectations['max_execution_time'];

        // Check memory usage
        $memory_used = $actual_metrics['memory_used'];
        $assertions['memory_usage'] = $memory_used <= $expectations['max_memory_usage'];

        // Check query count
        $query_count = count($actual_metrics['queries']);
        $assertions['query_count'] = $query_count <= $expectations['max_queries'];

        // Check API call count
        $api_call_count = count($actual_metrics['api_calls']);
        $assertions['api_call_count'] = $api_call_count <= $expectations['max_api_calls'];

        return !in_array(false, $assertions, true);
    }

    /**
     * Get performance metrics
     *
     * @return array Performance metrics
     */
    public static function get_performance_metrics() {
        return array(
            'start_time' => self::$performance_data['start_time'],
            'end_time' => microtime(true),
            'start_memory' => self::$performance_data['start_memory'],
            'end_memory' => memory_get_usage(),
            'memory_used' => memory_get_usage() - self::$performance_data['start_memory'],
            'peak_memory' => memory_get_peak_usage(),
            'queries' => self::$performance_data['queries'],
            'api_calls' => self::$performance_data['api_calls']
        );
    }

    /**
     * Create test license with specific scenario
     *
     * @param string $scenario Test scenario (valid, expired, suspended, etc.)
     * @return array License data
     */
    public static function create_test_license($scenario = 'valid') {
        return self::$fixtures->get_license_data($scenario);
    }

    /**
     * Create test provider configuration
     *
     * @param string $provider Provider name
     * @return array Provider configuration
     */
    public static function create_test_provider($provider = 'helium10') {
        return self::$fixtures->get_provider_data($provider);
    }

    /**
     * Simulate module loading and initialization
     *
     * @param string $module_id Module identifier
     * @return array Module simulation result
     */
    public static function simulate_module_loading($module_id) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        // Simulate module loading process
        $loader = VD_License_Module_Loader::get_instance();

        try {
            $module = $loader->load_module($module_id);
            $success = !is_null($module) && is_object($module);

            return array(
                'success' => $success,
                'module' => $module,
                'class_name' => $success ? get_class($module) : null,
                'loading_time' => (microtime(true) - $start_time) * 1000,
                'memory_used' => memory_get_usage() - $start_memory,
                'error' => $success ? null : 'Failed to load module'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'module' => null,
                'class_name' => null,
                'loading_time' => (microtime(true) - $start_time) * 1000,
                'memory_used' => memory_get_usage() - $start_memory,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Test module method with performance tracking
     *
     * @param object $module Module instance
     * @param string $method Method name
     * @param array $args Method arguments
     * @return array Test result with performance data
     */
    public static function test_module_method($module, $method, $args = array()) {
        if (!method_exists($module, $method)) {
            return array(
                'success' => false,
                'result' => null,
                'error' => "Method {$method} does not exist",
                'performance' => array()
            );
        }

        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        try {
            $result = call_user_func_array(array($module, $method), $args);

            return array(
                'success' => true,
                'result' => $result,
                'error' => null,
                'performance' => array(
                    'execution_time' => (microtime(true) - $start_time) * 1000,
                    'memory_used' => memory_get_usage() - $start_memory,
                    'method' => $method,
                    'args_count' => count($args)
                )
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'result' => null,
                'error' => $e->getMessage(),
                'performance' => array(
                    'execution_time' => (microtime(true) - $start_time) * 1000,
                    'memory_used' => memory_get_usage() - $start_memory,
                    'method' => $method,
                    'args_count' => count($args)
                )
            );
        }
    }

    /**
     * Generate comprehensive test report
     *
     * @param array $test_results Test results from multiple tests
     * @return array Comprehensive test report
     */
    public static function generate_test_report($test_results) {
        $total_tests = count($test_results);
        $passed_tests = 0;
        $failed_tests = 0;
        $total_execution_time = 0;
        $total_memory_used = 0;

        foreach ($test_results as $result) {
            if ($result['success']) {
                $passed_tests++;
            } else {
                $failed_tests++;
            }

            if (isset($result['performance']['execution_time'])) {
                $total_execution_time += $result['performance']['execution_time'];
            }

            if (isset($result['performance']['memory_used'])) {
                $total_memory_used += $result['performance']['memory_used'];
            }
        }

        return array(
            'summary' => array(
                'total_tests' => $total_tests,
                'passed_tests' => $passed_tests,
                'failed_tests' => $failed_tests,
                'success_rate' => $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0
            ),
            'performance' => array(
                'total_execution_time' => round($total_execution_time, 2),
                'average_execution_time' => $total_tests > 0 ? round($total_execution_time / $total_tests, 2) : 0,
                'total_memory_used' => $total_memory_used,
                'average_memory_used' => $total_tests > 0 ? round($total_memory_used / $total_tests, 2) : 0,
                'peak_memory' => memory_get_peak_usage()
            ),
            'details' => $test_results,
            'generated_at' => current_time('mysql')
        );
    }

    /**
     * Setup test database tables (if needed)
     *
     * @return bool Setup success
     */
    public static function setup_test_database() {
        global $wpdb;

        // This would typically create test tables or reset existing ones
        // For now, we'll just return true as this is infrastructure setup

        return true;
    }

    /**
     * Cleanup test environment
     *
     * @return void
     */
    public static function cleanup_test_environment() {
        // Clear caches
        if (self::$fixtures) {
            self::$fixtures->clear_cache();
        }

        if (self::$mocks) {
            self::$mocks->clear_mock_responses();
        }

        // Reset performance data
        self::$performance_data = array();

        // Remove test-specific filters
        remove_all_filters('pre_http_request');
        remove_all_filters('query');
    }

    /**
     * Enable debug mode for tests
     *
     * @param bool $enable Enable debug mode
     * @return void
     */
    public static function set_debug_mode($enable = true) {
        if ($enable) {
            define('VD_TEST_DEBUG', true);
            define('VD_TEST_MOCK_HTTP', true);
            define('VD_TEST_MOCK_DB', false); // Usually keep real DB for integration tests
        }
    }

    /**
     * Get test environment status
     *
     * @return array Environment status
     */
    public static function get_environment_status() {
        return array(
            'fixtures_loaded' => !is_null(self::$fixtures),
            'mocks_loaded' => !is_null(self::$mocks),
            'debug_mode' => defined('VD_TEST_DEBUG') && VD_TEST_DEBUG,
            'mock_http' => defined('VD_TEST_MOCK_HTTP') && VD_TEST_MOCK_HTTP,
            'mock_db' => defined('VD_TEST_MOCK_DB') && VD_TEST_MOCK_DB,
            'performance_tracking' => !empty(self::$performance_data),
            'memory_usage' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage()
        );
    }
}
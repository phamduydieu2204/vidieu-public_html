<?php
/**
 * VD License Manager Test Utilities
 *
 * Utility functions and helpers for VD License Manager PHPUnit tests
 * Provides common testing functionality and data manipulation tools
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

/**
 * Test utilities for VD License Manager
 */
class VD_Test_Utils {

    /**
     * Generate test license data structure
     *
     * @param array $overrides Data overrides
     * @return array License data
     */
    public static function generate_license_data($overrides = []) {
        $defaults = [
            'id' => rand(1, 9999),
            'license_key' => self::generate_license_key(),
            'status' => 'active',
            'product_id' => rand(1, 100),
            'user_id' => rand(1, 50),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'activations_limit' => 5,
            'times_activated' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Generate random license key
     *
     * @param string $prefix Key prefix
     * @return string License key
     */
    public static function generate_license_key($prefix = 'VD') {
        return sprintf(
            '%s-%04X-%04X-%04X',
            $prefix,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }

    /**
     * Generate test context data
     *
     * @param array $overrides Context overrides
     * @return array Context data
     */
    public static function generate_context($overrides = []) {
        $defaults = [
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('timestamp'),
            'source' => 'test',
            'action' => 'validation',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test Suite'
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create mock WordPress user
     *
     * @param array $user_data User data
     * @return WP_User Mock user object
     */
    public static function create_mock_user($user_data = []) {
        $defaults = [
            'ID' => rand(1, 9999),
            'user_login' => 'testuser' . rand(1, 999),
            'user_email' => 'test' . rand(1, 999) . '@example.com',
            'display_name' => 'Test User',
            'user_registered' => current_time('mysql')
        ];

        $user_data = array_merge($defaults, $user_data);

        return new WP_User((object) $user_data);
    }

    /**
     * Create mock product data
     *
     * @param array $product_data Product data
     * @return array Product data
     */
    public static function create_mock_product($product_data = []) {
        $defaults = [
            'ID' => rand(1, 9999),
            'post_title' => 'Test Product ' . rand(1, 999),
            'post_type' => 'product',
            'post_status' => 'publish',
            'meta' => [
                '_regular_price' => '29.99',
                '_vd_license_enabled' => 'yes',
                '_vd_activations_limit' => 5
            ]
        ];

        return array_merge($defaults, $product_data);
    }

    /**
     * Mock WordPress database queries
     *
     * @param array $query_results Predefined query results
     * @return object Mock wpdb object
     */
    public static function mock_wpdb($query_results = []) {
        return new class($query_results) {
            private $results;
            public $insert_id = 123;
            public $last_error = '';

            public function __construct($results) {
                $this->results = $results;
            }

            public function prepare($query, ...$args) {
                return vsprintf(str_replace('%s', "'%s'", $query), $args);
            }

            public function get_row($query, $output = OBJECT) {
                return $this->results['get_row'] ?? null;
            }

            public function get_results($query, $output = OBJECT) {
                return $this->results['get_results'] ?? [];
            }

            public function insert($table, $data) {
                return $this->results['insert'] ?? true;
            }

            public function update($table, $data, $where) {
                return $this->results['update'] ?? 1;
            }

            public function query($query) {
                return $this->results['query'] ?? true;
            }
        };
    }

    /**
     * Assert array structure matches expected format
     *
     * @param array $array Array to check
     * @param array $expected_structure Expected structure
     * @return bool Structure matches
     */
    public static function assert_array_structure($array, $expected_structure) {
        foreach ($expected_structure as $key => $type) {
            if (!array_key_exists($key, $array)) {
                return false;
            }

            if ($type === 'array' && !is_array($array[$key])) {
                return false;
            }

            if ($type === 'string' && !is_string($array[$key])) {
                return false;
            }

            if ($type === 'int' && !is_int($array[$key])) {
                return false;
            }

            if ($type === 'bool' && !is_bool($array[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate test batch data
     *
     * @param int $count Number of items
     * @param string $type Data type ('license', 'user', 'product')
     * @param array $common_args Common arguments for all items
     * @return array Batch data
     */
    public static function generate_batch_data($count, $type = 'license', $common_args = []) {
        $batch = [];

        for ($i = 0; $i < $count; $i++) {
            switch ($type) {
                case 'license':
                    $batch[] = self::generate_license_data(array_merge($common_args, [
                        'license_key' => self::generate_license_key('BATCH' . $i)
                    ]));
                    break;

                case 'user':
                    $batch[] = self::create_mock_user(array_merge($common_args, [
                        'user_login' => 'batchuser' . $i
                    ]));
                    break;

                case 'product':
                    $batch[] = self::create_mock_product(array_merge($common_args, [
                        'post_title' => 'Batch Product ' . $i
                    ]));
                    break;
            }
        }

        return $batch;
    }

    /**
     * Simulate time passage for testing time-based functionality
     *
     * @param int $seconds Seconds to advance
     */
    public static function advance_time($seconds) {
        if (function_exists('uopz_set_return')) {
            uopz_set_return('time', time() + $seconds);
        }
    }

    /**
     * Reset simulated time
     */
    public static function reset_time() {
        if (function_exists('uopz_unset_return')) {
            uopz_unset_return('time');
        }
    }

    /**
     * Create temporary test file
     *
     * @param string $content File content
     * @param string $suffix File suffix
     * @return string File path
     */
    public static function create_temp_file($content = '', $suffix = '.tmp') {
        $temp_file = tempnam(sys_get_temp_dir(), 'vd_test_') . $suffix;
        file_put_contents($temp_file, $content);
        return $temp_file;
    }

    /**
     * Clean up temporary files
     *
     * @param array $files File paths to clean up
     */
    public static function cleanup_temp_files($files) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Generate test configuration
     *
     * @param string $module Module name
     * @return array Configuration
     */
    public static function get_test_config($module = 'general') {
        $configs = [
            'general' => [
                'timeout' => 30,
                'max_retries' => 3,
                'batch_size' => 10,
                'debug_mode' => true
            ],
            'activation' => [
                'max_activations' => 5,
                'allow_reactivation' => true,
                'check_device_limits' => true
            ],
            'expiry' => [
                'grace_period_hours' => 72,
                'auto_cleanup' => true,
                'notification_enabled' => true
            ],
            'usage' => [
                'rate_limit_enabled' => true,
                'max_requests_per_hour' => 1000,
                'quota_enforcement' => true
            ]
        ];

        return $configs[$module] ?? $configs['general'];
    }

    /**
     * Assert performance metrics
     *
     * @param array $metrics Performance metrics
     * @param array $thresholds Performance thresholds
     * @return bool Performance is acceptable
     */
    public static function assert_performance($metrics, $thresholds = []) {
        $default_thresholds = [
            'max_execution_time_ms' => 1000,
            'max_memory_usage_mb' => 50,
            'max_database_queries' => 20
        ];

        $thresholds = array_merge($default_thresholds, $thresholds);

        foreach ($thresholds as $metric => $threshold) {
            if (isset($metrics[$metric]) && $metrics[$metric] > $threshold) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log test execution details
     *
     * @param string $test_name Test name
     * @param array $details Test details
     */
    public static function log_test_execution($test_name, $details) {
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log(sprintf(
                '[VD Test] %s: %s',
                $test_name,
                json_encode($details, JSON_PRETTY_PRINT)
            ));
        }
    }

    /**
     * Format test result for display
     *
     * @param array $result Test result
     * @return string Formatted result
     */
    public static function format_test_result($result) {
        $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
        $time = isset($result['execution_time_ms']) ? $result['execution_time_ms'] . 'ms' : 'N/A';
        $memory = isset($result['memory_used_formatted']) ? $result['memory_used_formatted'] : 'N/A';

        return sprintf(
            '%s | Time: %s | Memory: %s | %s',
            $status,
            $time,
            $memory,
            $result['message'] ?? ''
        );
    }
}
<?php
/**
 * VD License Manager Base Test Case
 *
 * Base test case class for all VD License Manager PHPUnit tests
 * Provides common functionality and setup for testing modules
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

/**
 * Base test case for VD License Manager
 */
class VD_Test_Case extends WP_UnitTestCase {

    /**
     * VD License Manager dependency container
     *
     * @var VD_License_Dependency_Container
     */
    protected $container;

    /**
     * VD License Manager module loader
     *
     * @var VD_License_Module_Loader
     */
    protected $module_loader;

    /**
     * Test license data factory
     *
     * @var VD_Test_Factory
     */
    protected $factory;

    /**
     * Test utilities
     *
     * @var VD_Test_Utils
     */
    protected $utils;

    /**
     * Set up test environment before each test
     */
    public function setUp(): void {
        parent::setUp();

        // Initialize VD License Manager components
        $this->container = VD_License_Dependency_Container::get_instance();
        $this->module_loader = VD_License_Module_Loader::get_instance();
        $this->factory = new VD_Test_Factory();
        $this->utils = new VD_Test_Utils();

        // Clear any existing cache
        if (method_exists($this->container, 'clear_instances')) {
            $this->container->clear_instances();
        }

        // Set up test database state
        $this->setup_test_data();

        // Hook into WordPress for testing
        $this->setup_hooks();
    }

    /**
     * Clean up after each test
     */
    public function tearDown(): void {
        // Clean up test data
        $this->cleanup_test_data();

        // Reset container state
        if (method_exists($this->container, 'clear_instances')) {
            $this->container->clear_instances();
        }

        // Remove hooks
        $this->cleanup_hooks();

        parent::tearDown();
    }

    /**
     * Set up test data before each test
     */
    protected function setup_test_data() {
        global $wpdb;

        // Create test users
        $this->test_user_id = $this->factory->user->create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'role' => 'subscriber'
        ]);

        $this->admin_user_id = $this->factory->user->create([
            'user_login' => 'testadmin',
            'user_email' => 'admin@example.com',
            'role' => 'administrator'
        ]);

        // Create test products
        $this->test_product_id = $this->factory->post->create([
            'post_type' => 'product',
            'post_title' => 'Test Product',
            'post_status' => 'publish'
        ]);

        // Set current user for tests
        wp_set_current_user($this->admin_user_id);
    }

    /**
     * Clean up test data after each test
     */
    protected function cleanup_test_data() {
        global $wpdb;

        // Clean up test licenses
        $wpdb->query("DELETE FROM {$wpdb->prefix}vd_test_licenses WHERE license_key LIKE 'TEST-%'");

        // Clean up test metadata
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'vd_test_%'");
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'vd_test_%'");

        // Reset current user
        wp_set_current_user(0);
    }

    /**
     * Set up WordPress hooks for testing
     */
    protected function setup_hooks() {
        // Add test-specific hooks
        add_action('vd_test_hook', [$this, 'test_hook_callback']);
    }

    /**
     * Clean up WordPress hooks after testing
     */
    protected function cleanup_hooks() {
        // Remove test-specific hooks
        remove_action('vd_test_hook', [$this, 'test_hook_callback']);
    }

    /**
     * Test hook callback
     */
    public function test_hook_callback() {
        // Test hook implementation
    }

    /**
     * Create test license data
     *
     * @param array $args License arguments
     * @return array License data
     */
    protected function create_test_license($args = []) {
        $defaults = [
            'license_key' => 'TEST-' . uniqid(),
            'status' => 'active',
            'product_id' => $this->test_product_id,
            'user_id' => $this->test_user_id,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'activations_limit' => 5,
            'times_activated' => 0
        ];

        $license_data = array_merge($defaults, $args);

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'vd_test_licenses', $license_data);
        $license_data['id'] = $wpdb->insert_id;

        return $license_data;
    }

    /**
     * Create expired test license
     *
     * @param array $args Additional arguments
     * @return array License data
     */
    protected function create_expired_license($args = []) {
        $defaults = [
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ];

        return $this->create_test_license(array_merge($defaults, $args));
    }

    /**
     * Create test license batch
     *
     * @param int $count Number of licenses to create
     * @param array $args Common arguments for all licenses
     * @return array Array of license data
     */
    protected function create_license_batch($count = 5, $args = []) {
        $licenses = [];

        for ($i = 0; $i < $count; $i++) {
            $license_args = array_merge($args, [
                'license_key' => 'TEST-BATCH-' . $i . '-' . uniqid()
            ]);
            $licenses[] = $this->create_test_license($license_args);
        }

        return $licenses;
    }

    /**
     * Assert that a module is loaded correctly
     *
     * @param string $module_id Module identifier
     * @param string $expected_class Expected class name
     */
    protected function assertModuleLoaded($module_id, $expected_class) {
        $this->assertTrue($this->container->has($module_id), "Module {$module_id} should be registered");

        $module = $this->container->get($module_id);
        $this->assertInstanceOf($expected_class, $module, "Module {$module_id} should be instance of {$expected_class}");
    }

    /**
     * Assert that a method returns expected structure
     *
     * @param array $result Method result
     * @param array $expected_keys Expected keys in result
     */
    protected function assertValidResult($result, $expected_keys = ['valid', 'data', 'message']) {
        $this->assertIsArray($result, 'Result should be an array');

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $result, "Result should have '{$key}' key");
        }
    }

    /**
     * Assert that validation result is successful
     *
     * @param array $result Validation result
     */
    protected function assertValidationSuccess($result) {
        $this->assertValidResult($result);
        $this->assertTrue($result['valid'], 'Validation should be successful');
    }

    /**
     * Assert that validation result is failure
     *
     * @param array $result Validation result
     * @param string $expected_error Expected error message (optional)
     */
    protected function assertValidationFailure($result, $expected_error = null) {
        $this->assertValidResult($result);
        $this->assertFalse($result['valid'], 'Validation should fail');

        if ($expected_error) {
            $this->assertStringContainsString($expected_error, $result['message'], 'Error message should contain expected text');
        }
    }

    /**
     * Mock WordPress function for testing
     *
     * @param string $function_name Function name to mock
     * @param mixed $return_value Return value
     */
    protected function mockWordPressFunction($function_name, $return_value) {
        if (!function_exists($function_name)) {
            eval("function {$function_name}() { return " . var_export($return_value, true) . "; }");
        }
    }

    /**
     * Get test configuration
     *
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value
     */
    protected function getTestConfig($key, $default = null) {
        $config = [
            'batch_size' => 10,
            'timeout' => 30,
            'retry_attempts' => 3,
            'debug_mode' => true
        ];

        return isset($config[$key]) ? $config[$key] : $default;
    }

    /**
     * Skip test if module not available
     *
     * @param string $module_id Module identifier
     */
    protected function skipIfModuleNotAvailable($module_id) {
        if (!$this->container->has($module_id)) {
            $this->markTestSkipped("Module {$module_id} is not available");
        }
    }

    /**
     * Get memory usage for performance testing
     *
     * @return array Memory usage information
     */
    protected function getMemoryUsage() {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'current_formatted' => size_format(memory_get_usage(true)),
            'peak_formatted' => size_format(memory_get_peak_usage(true))
        ];
    }

    /**
     * Measure execution time for performance testing
     *
     * @param callable $callback Function to measure
     * @return array Execution results with timing
     */
    protected function measureExecutionTime($callback) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage(true);

        $result = call_user_func($callback);

        $execution_time = microtime(true) - $start_time;
        $memory_used = memory_get_usage(true) - $start_memory;

        return [
            'result' => $result,
            'execution_time_ms' => round($execution_time * 1000, 2),
            'memory_used' => $memory_used,
            'memory_used_formatted' => size_format($memory_used)
        ];
    }
}
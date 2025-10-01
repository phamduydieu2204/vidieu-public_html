<?php
/**
 * Step 2.1 - Activation Rules Module Tests
 *
 * PHPUnit tests for VD_License_Rule_Activation module
 * Tests product constraints, device limits, and activation management
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

/**
 * Test Step 2.1 - Activation Rules Module
 */
class Test_VD_License_Rule_Activation extends VD_Test_Case {

    /**
     * Activation rules module instance
     *
     * @var VD_License_Rule_Activation
     */
    private $activation_rules;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();

        $this->skipIfModuleNotAvailable('rules.activation');
        $this->activation_rules = $this->container->get('rules.activation');
    }

    /**
     * Test 1: Module Loading and Initialization
     */
    public function test_module_loading() {
        $this->assertModuleLoaded('rules.activation', 'VD_License_Rule_Activation');
        $this->assertInstanceOf('VD_License_Rule_Activation', $this->activation_rules);
    }

    /**
     * Test 2: Product Level Constraints Validation - Valid License
     */
    public function test_validate_product_level_constraints_valid() {
        $license = $this->create_test_license([
            'status' => 'active',
            'product_id' => $this->test_product_id,
            'activations_limit' => 5,
            'times_activated' => 2
        ]);

        $context = VD_Test_Utils::generate_context([
            'action' => 'activation',
            'device_id' => 'test-device-123'
        ]);

        $result = $this->activation_rules->validate_product_level_constraints($license, $context);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('constraints_checked', $result);
        $this->assertTrue($result['constraints_checked']['activation_limit']);
    }

    /**
     * Test 3: Product Level Constraints - Activation Limit Exceeded
     */
    public function test_validate_product_level_constraints_limit_exceeded() {
        $license = $this->create_test_license([
            'status' => 'active',
            'activations_limit' => 3,
            'times_activated' => 3
        ]);

        $context = VD_Test_Utils::generate_context([
            'action' => 'activation'
        ]);

        $result = $this->activation_rules->validate_product_level_constraints($license, $context);

        $this->assertValidationFailure($result, 'activation limit exceeded');
        $this->assertEquals('activation_limit_exceeded', $result['error_code']);
    }

    /**
     * Test 4: Device Limits Enforcement - New Device
     */
    public function test_enforce_device_limits_new_device() {
        $license = $this->create_test_license([
            'status' => 'active',
            'activations_limit' => 5,
            'times_activated' => 2
        ]);

        $context = VD_Test_Utils::generate_context([
            'device_id' => 'new-device-456',
            'device_info' => [
                'name' => 'Test Device',
                'type' => 'desktop',
                'os' => 'Windows 11'
            ]
        ]);

        $result = $this->activation_rules->enforce_device_limits($license, $context);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('device_registered', $result);
        $this->assertTrue($result['device_registered']);
    }

    /**
     * Test 5: Device Limits Enforcement - Existing Device
     */
    public function test_enforce_device_limits_existing_device() {
        $license = $this->create_test_license();

        // Pre-register device
        $device_id = 'existing-device-789';
        $this->register_test_device($license['id'], $device_id);

        $context = VD_Test_Utils::generate_context([
            'device_id' => $device_id
        ]);

        $result = $this->activation_rules->enforce_device_limits($license, $context);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('device_found', $result);
        $this->assertTrue($result['device_found']);
    }

    /**
     * Test 6: Activation Management - Successful Activation
     */
    public function test_manage_license_activation_success() {
        $license = $this->create_test_license([
            'status' => 'active',
            'activations_limit' => 5,
            'times_activated' => 1
        ]);

        $activation_data = [
            'license_id' => $license['id'],
            'device_id' => 'activation-device-001',
            'device_info' => [
                'name' => 'Activation Test Device',
                'type' => 'mobile'
            ],
            'activation_type' => 'standard'
        ];

        $result = $this->activation_rules->manage_license_activation($activation_data);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('activation_id', $result);
        $this->assertArrayHasKey('updated_activations_count', $result);
        $this->assertEquals(2, $result['updated_activations_count']);
    }

    /**
     * Test 7: Activation Management - Deactivation
     */
    public function test_manage_license_deactivation() {
        $license = $this->create_test_license([
            'times_activated' => 3
        ]);

        // Create existing activation
        $activation_id = $this->create_test_activation($license['id'], 'deactivation-device-001');

        $deactivation_data = [
            'license_id' => $license['id'],
            'device_id' => 'deactivation-device-001',
            'action' => 'deactivate',
            'reason' => 'user_request'
        ];

        $result = $this->activation_rules->manage_license_activation($deactivation_data);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('deactivated', $result);
        $this->assertTrue($result['deactivated']);
        $this->assertEquals(2, $result['updated_activations_count']);
    }

    /**
     * Test 8: Multiple Device Activation Scenario
     */
    public function test_multiple_device_activation() {
        $license = $this->create_test_license([
            'activations_limit' => 3,
            'times_activated' => 0
        ]);

        $devices = ['device-001', 'device-002', 'device-003'];
        $activation_results = [];

        foreach ($devices as $device_id) {
            $activation_data = [
                'license_id' => $license['id'],
                'device_id' => $device_id,
                'device_info' => ['name' => "Test Device {$device_id}"]
            ];

            $result = $this->activation_rules->manage_license_activation($activation_data);
            $activation_results[] = $result;
        }

        // All activations should succeed
        foreach ($activation_results as $result) {
            $this->assertValidationSuccess($result);
        }

        // Fourth activation should fail
        $fourth_activation = [
            'license_id' => $license['id'],
            'device_id' => 'device-004'
        ];

        $result = $this->activation_rules->manage_license_activation($fourth_activation);
        $this->assertValidationFailure($result, 'activation limit');
    }

    /**
     * Test 9: Product Constraints with Custom Rules
     */
    public function test_product_constraints_custom_rules() {
        $license = $this->create_test_license();

        // Add custom constraints to product
        update_post_meta($this->test_product_id, '_vd_custom_constraints', [
            'domain_restriction' => 'example.com',
            'ip_whitelist' => ['192.168.1.0/24'],
            'time_restriction' => [
                'start' => '09:00',
                'end' => '17:00'
            ]
        ]);

        $context = VD_Test_Utils::generate_context([
            'domain' => 'example.com',
            'ip_address' => '192.168.1.100',
            'timestamp' => strtotime('2024-01-01 10:00:00')
        ]);

        $result = $this->activation_rules->validate_product_level_constraints($license, $context);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('custom_constraints_validated', $result);
    }

    /**
     * Test 10: Bulk Activation Processing
     */
    public function test_bulk_activation_processing() {
        $licenses = $this->create_license_batch(5, [
            'activations_limit' => 3,
            'times_activated' => 1
        ]);

        $bulk_activations = [];
        foreach ($licenses as $license) {
            $bulk_activations[] = [
                'license_id' => $license['id'],
                'device_id' => 'bulk-device-' . $license['id'],
                'device_info' => ['type' => 'bulk_test']
            ];
        }

        $result = $this->activation_rules->process_bulk_activations($bulk_activations);

        $this->assertValidResult($result, ['processed', 'successful', 'failed', 'results']);
        $this->assertEquals(5, $result['processed']);
        $this->assertEquals(5, $result['successful']);
        $this->assertEquals(0, $result['failed']);
    }

    /**
     * Test 11: Performance Test - Large Batch Processing
     */
    public function test_performance_large_batch() {
        $large_batch = [];
        for ($i = 0; $i < 100; $i++) {
            $license = $this->create_test_license([
                'license_key' => 'PERF-TEST-' . $i,
                'activations_limit' => 10
            ]);

            $large_batch[] = [
                'license_id' => $license['id'],
                'device_id' => 'perf-device-' . $i
            ];
        }

        $performance_result = $this->measureExecutionTime(function() use ($large_batch) {
            return $this->activation_rules->process_bulk_activations($large_batch);
        });

        $this->assertValidationSuccess($performance_result['result']);
        $this->assertLessThan(5000, $performance_result['execution_time_ms'], 'Bulk processing should complete within 5 seconds');

        VD_Test_Utils::log_test_execution('Performance Large Batch', [
            'batch_size' => 100,
            'execution_time_ms' => $performance_result['execution_time_ms'],
            'memory_used' => $performance_result['memory_used_formatted']
        ]);
    }

    /**
     * Test 12: Error Handling and Edge Cases
     */
    public function test_error_handling_edge_cases() {
        // Test with invalid license data
        $invalid_license = ['id' => 99999, 'license_key' => 'INVALID'];
        $context = VD_Test_Utils::generate_context();

        $result = $this->activation_rules->validate_product_level_constraints($invalid_license, $context);
        $this->assertValidationFailure($result);

        // Test with malformed data
        $result = $this->activation_rules->manage_license_activation([]);
        $this->assertValidationFailure($result, 'missing required');

        // Test with null context
        $license = $this->create_test_license();
        $result = $this->activation_rules->validate_product_level_constraints($license, null);
        $this->assertValidationFailure($result);
    }

    /**
     * Test 13: Integration with Status Business Logic
     */
    public function test_status_business_integration() {
        $license = $this->create_test_license(['status' => 'inactive']);

        $activation_data = [
            'license_id' => $license['id'],
            'device_id' => 'integration-device',
            'trigger_status_change' => true
        ];

        $result = $this->activation_rules->manage_license_activation($activation_data);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('status_updated', $result);
        $this->assertTrue($result['status_updated']);
    }

    /**
     * Test 14: Concurrent Activation Handling
     */
    public function test_concurrent_activation_handling() {
        $license = $this->create_test_license([
            'activations_limit' => 1,
            'times_activated' => 0
        ]);

        // Simulate concurrent activations
        $concurrent_activations = [
            ['device_id' => 'concurrent-1', 'timestamp' => time()],
            ['device_id' => 'concurrent-2', 'timestamp' => time()]
        ];

        $results = [];
        foreach ($concurrent_activations as $activation) {
            $activation_data = array_merge([
                'license_id' => $license['id']
            ], $activation);

            $results[] = $this->activation_rules->manage_license_activation($activation_data);
        }

        // Only one should succeed
        $successful = array_filter($results, function($result) {
            return $result['valid'] === true;
        });

        $this->assertCount(1, $successful, 'Only one concurrent activation should succeed');
    }

    /**
     * Test 15: Cleanup and Resource Management
     */
    public function test_cleanup_and_resource_management() {
        $license = $this->create_test_license();

        // Create multiple activations
        for ($i = 0; $i < 5; $i++) {
            $this->create_test_activation($license['id'], "cleanup-device-{$i}");
        }

        $cleanup_result = $this->activation_rules->cleanup_expired_activations([
            'license_id' => $license['id'],
            'older_than_days' => 0 // Clean all for testing
        ]);

        $this->assertValidationSuccess($cleanup_result);
        $this->assertArrayHasKey('cleaned_count', $cleanup_result);
        $this->assertGreaterThanOrEqual(5, $cleanup_result['cleaned_count']);
    }

    /**
     * Helper: Register test device for license
     */
    private function register_test_device($license_id, $device_id) {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . 'vd_license_activations', [
            'license_id' => $license_id,
            'device_id' => $device_id,
            'device_name' => 'Test Device',
            'activated_at' => current_time('mysql'),
            'status' => 'active'
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Helper: Create test activation
     */
    private function create_test_activation($license_id, $device_id) {
        return $this->register_test_device($license_id, $device_id);
    }
}
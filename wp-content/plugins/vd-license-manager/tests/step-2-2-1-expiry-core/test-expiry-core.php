<?php
/**
 * Step 2.2.1 - Expiry Core Module Tests
 *
 * PHPUnit tests for VD_License_Rule_Expiry_Core module
 * Tests basic expiry validation, status management, and date calculations
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

/**
 * Test Step 2.2.1 - Expiry Core Module
 */
class Test_VD_License_Rule_Expiry_Core extends VD_Test_Case {

    /**
     * Expiry core module instance
     *
     * @var VD_License_Rule_Expiry_Core
     */
    private $expiry_core;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();

        $this->skipIfModuleNotAvailable('rules.expiry_core');
        $this->expiry_core = $this->container->get('rules.expiry_core');
    }

    /**
     * Test 1: Module Loading and Initialization
     */
    public function test_module_loading() {
        $this->assertModuleLoaded('rules.expiry_core', 'VD_License_Rule_Expiry_Core');
        $this->assertInstanceOf('VD_License_Rule_Expiry_Core', $this->expiry_core);
    }

    /**
     * Test 2: License Expiry Date Validation - Valid Future Date
     */
    public function test_validate_license_expiry_date_valid_future() {
        $license = $this->create_test_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('+6 months')),
            'status' => 'active'
        ]);

        $result = $this->expiry_core->validate_license_expiry_date($license);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('days_until_expiry', $result);
        $this->assertArrayHasKey('expiry_warning', $result);
        $this->assertGreaterThan(180, $result['days_until_expiry']);
        $this->assertFalse($result['expiry_warning']);
    }

    /**
     * Test 3: License Expiry Date Validation - Expired License
     */
    public function test_validate_license_expiry_date_expired() {
        $license = $this->create_expired_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'status' => 'active'
        ]);

        $result = $this->expiry_core->validate_license_expiry_date($license);

        $this->assertValidationFailure($result, 'đã hết hạn');
        $this->assertEquals('license_expired', $result['code']);
        $this->assertArrayHasKey('expired_days_ago', $result);
        $this->assertGreaterThan(0, $result['expired_days_ago']);
    }

    /**
     * Test 4: License Expiry Date Validation - Lifetime License
     */
    public function test_validate_license_expiry_date_lifetime() {
        $license = $this->create_test_license([
            'expires_at' => null,
            'status' => 'active'
        ]);

        $result = $this->expiry_core->validate_license_expiry_date($license);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('is_lifetime', $result);
        $this->assertTrue($result['is_lifetime']);
        $this->assertNull($result['days_until_expiry']);
    }

    /**
     * Test 5: License Expiry Date Validation - Warning Period
     */
    public function test_validate_license_expiry_date_warning() {
        $license = $this->create_test_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('+5 days')),
            'status' => 'active'
        ]);

        $result = $this->expiry_core->validate_license_expiry_date($license);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('expiry_warning', $result);
        $this->assertTrue($result['expiry_warning']);
        $this->assertArrayHasKey('warning_level', $result);
        $this->assertEquals('critical', $result['warning_level']);
    }

    /**
     * Test 6: Expiry Status Management - Determine Expiry Status
     */
    public function test_determine_expiry_status() {
        // Test active license
        $active_license = $this->create_test_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'status' => 'active'
        ]);

        $result = $this->expiry_core->determine_expiry_status($active_license);
        $this->assertEquals('active', $result['recommended_status']);

        // Test expired license
        $expired_license = $this->create_expired_license();
        $result = $this->expiry_core->determine_expiry_status($expired_license);
        $this->assertEquals('expired', $result['recommended_status']);

        // Test warning period license
        $warning_license = $this->create_test_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('+2 days'))
        ]);

        $result = $this->expiry_core->determine_expiry_status($warning_license);
        $this->assertEquals('warning', $result['recommended_status']);
    }

    /**
     * Test 7: Basic Status Change Processing
     */
    public function test_process_basic_status_change() {
        $license = $this->create_test_license([
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);

        $change_data = [
            'license_id' => $license['id'],
            'old_status' => 'active',
            'new_status' => 'expired',
            'reason' => 'automatic_expiry_check',
            'context' => [
                'expiry_date' => $license['expires_at'],
                'check_time' => current_time('mysql')
            ]
        ];

        $result = $this->expiry_core->process_basic_status_change($change_data);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('status_updated', $result);
        $this->assertTrue($result['status_updated']);
        $this->assertArrayHasKey('change_logged', $result);
    }

    /**
     * Test 8: Expiry Date Calculations - Days Until Expiry
     */
    public function test_calculate_days_until_expiry() {
        // Test exact calculations
        $test_cases = [
            ['days' => 30, 'expected_range' => [29, 31]],
            ['days' => 7, 'expected_range' => [6, 8]],
            ['days' => 1, 'expected_range' => [0, 2]],
            ['days' => -5, 'expected_range' => [-6, -4]]
        ];

        foreach ($test_cases as $case) {
            $license = $this->create_test_license([
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$case['days']} days"))
            ]);

            $result = $this->expiry_core->calculate_days_until_expiry($license);

            $this->assertArrayHasKey('days_until_expiry', $result);
            $this->assertGreaterThanOrEqual($case['expected_range'][0], $result['days_until_expiry']);
            $this->assertLessThanOrEqual($case['expected_range'][1], $result['days_until_expiry']);
        }
    }

    /**
     * Test 9: Expiry Warning Levels
     */
    public function test_determine_warning_levels() {
        $warning_test_cases = [
            ['days' => 30, 'expected_level' => 'none'],
            ['days' => 14, 'expected_level' => 'early'],
            ['days' => 7, 'expected_level' => 'standard'],
            ['days' => 3, 'expected_level' => 'urgent'],
            ['days' => 1, 'expected_level' => 'critical'],
            ['days' => -1, 'expected_level' => 'expired']
        ];

        foreach ($warning_test_cases as $case) {
            $license = $this->create_test_license([
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$case['days']} days"))
            ]);

            $result = $this->expiry_core->determine_warning_level($license);

            $this->assertArrayHasKey('warning_level', $result);
            $this->assertEquals($case['expected_level'], $result['warning_level']);
        }
    }

    /**
     * Test 10: Batch Expiry Validation
     */
    public function test_batch_expiry_validation() {
        $licenses = [
            $this->create_test_license(['expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))]),
            $this->create_expired_license(),
            $this->create_test_license(['expires_at' => date('Y-m-d H:i:s', strtotime('+5 days'))]),
            $this->create_test_license(['expires_at' => null]) // lifetime
        ];

        $result = $this->expiry_core->validate_batch_expiry($licenses);

        $this->assertValidResult($result, ['processed', 'valid', 'expired', 'warning', 'lifetime']);
        $this->assertEquals(4, $result['processed']);
        $this->assertEquals(1, $result['valid']);
        $this->assertEquals(1, $result['expired']);
        $this->assertEquals(1, $result['warning']);
        $this->assertEquals(1, $result['lifetime']);
    }

    /**
     * Test 11: Grace Period Handling
     */
    public function test_grace_period_handling() {
        $license = $this->create_test_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('-12 hours')),
            'status' => 'active'
        ]);

        $grace_config = [
            'grace_period_hours' => 24,
            'grace_status' => 'grace_period',
            'allow_usage_during_grace' => true
        ];

        $result = $this->expiry_core->check_grace_period($license, $grace_config);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('in_grace_period', $result);
        $this->assertTrue($result['in_grace_period']);
        $this->assertArrayHasKey('grace_hours_remaining', $result);
        $this->assertGreaterThan(0, $result['grace_hours_remaining']);
    }

    /**
     * Test 12: Timezone Handling
     */
    public function test_timezone_handling() {
        // Test with different timezones
        $timezones = ['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo'];

        foreach ($timezones as $timezone) {
            $original_timezone = date_default_timezone_get();
            date_default_timezone_set($timezone);

            $license = $this->create_test_license([
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))
            ]);

            $result = $this->expiry_core->validate_license_expiry_date($license, [
                'timezone' => $timezone
            ]);

            $this->assertValidationSuccess($result);
            $this->assertArrayHasKey('timezone_used', $result);
            $this->assertEquals($timezone, $result['timezone_used']);

            date_default_timezone_set($original_timezone);
        }
    }

    /**
     * Test 13: Performance Test - Large Batch Processing
     */
    public function test_performance_large_batch_validation() {
        $large_batch = [];

        for ($i = 0; $i < 1000; $i++) {
            $days_offset = rand(-30, 365);
            $large_batch[] = $this->create_test_license([
                'license_key' => 'PERF-EXPIRY-' . $i,
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$days_offset} days"))
            ]);
        }

        $performance_result = $this->measureExecutionTime(function() use ($large_batch) {
            return $this->expiry_core->validate_batch_expiry($large_batch);
        });

        $this->assertValidationSuccess($performance_result['result']);
        $this->assertLessThan(3000, $performance_result['execution_time_ms'], 'Batch expiry validation should complete within 3 seconds for 1000 licenses');

        VD_Test_Utils::log_test_execution('Performance Large Batch Expiry', [
            'batch_size' => 1000,
            'execution_time_ms' => $performance_result['execution_time_ms'],
            'memory_used' => $performance_result['memory_used_formatted']
        ]);
    }

    /**
     * Test 14: Error Handling and Edge Cases
     */
    public function test_error_handling_edge_cases() {
        // Test with invalid date formats
        $invalid_license = $this->create_test_license([
            'expires_at' => 'invalid-date-format'
        ]);

        $result = $this->expiry_core->validate_license_expiry_date($invalid_license);
        $this->assertValidationFailure($result);

        // Test with null license
        $result = $this->expiry_core->validate_license_expiry_date(null);
        $this->assertValidationFailure($result, 'license data required');

        // Test with empty array
        $result = $this->expiry_core->validate_license_expiry_date([]);
        $this->assertValidationFailure($result);

        // Test with malformed data
        $malformed_license = ['id' => 'not-numeric', 'expires_at' => '2024-13-45 25:70:80'];
        $result = $this->expiry_core->validate_license_expiry_date($malformed_license);
        $this->assertValidationFailure($result);
    }

    /**
     * Test 15: Integration with Status Business Logic
     */
    public function test_status_business_integration() {
        $license = $this->create_test_license([
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]);

        $integration_result = $this->expiry_core->integrate_with_status_business($license, [
            'trigger_status_change' => true,
            'update_database' => false, // Test mode
            'notify_changes' => false
        ]);

        $this->assertValidationSuccess($integration_result);
        $this->assertArrayHasKey('status_recommendation', $integration_result);
        $this->assertEquals('expired', $integration_result['status_recommendation']);
        $this->assertArrayHasKey('business_rules_applied', $integration_result);
    }

    /**
     * Helper: Create license with custom expiry scenarios
     */
    private function create_license_with_expiry($expiry_offset_days) {
        if ($expiry_offset_days === null) {
            $expires_at = null; // lifetime
        } else {
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_offset_days} days"));
        }

        return $this->create_test_license([
            'expires_at' => $expires_at,
            'license_key' => 'EXPIRY-TEST-' . uniqid()
        ]);
    }

    /**
     * Helper: Assert expiry validation structure
     */
    private function assertExpiryValidationStructure($result) {
        $expected_keys = [
            'valid',
            'days_until_expiry',
            'expiry_warning',
            'warning_level',
            'is_lifetime',
            'grace_period_applicable'
        ];

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $result, "Expiry validation result should contain '{$key}' key");
        }
    }

    /**
     * Helper: Create test data for specific scenarios
     */
    private function getExpiryTestScenarios() {
        return [
            'far_future' => ['days' => 365, 'expected_warning' => false],
            'near_future' => ['days' => 30, 'expected_warning' => false],
            'warning_period' => ['days' => 7, 'expected_warning' => true],
            'critical_period' => ['days' => 1, 'expected_warning' => true],
            'recently_expired' => ['days' => -1, 'expected_expired' => true],
            'long_expired' => ['days' => -30, 'expected_expired' => true],
            'lifetime' => ['days' => null, 'expected_lifetime' => true]
        ];
    }
}
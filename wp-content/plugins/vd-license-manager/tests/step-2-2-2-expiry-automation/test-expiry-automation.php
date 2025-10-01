<?php
/**
 * Step 2.2.2 - Expiry Automation Module Tests
 *
 * PHPUnit tests for VD_License_Rule_Expiry_Automation module
 * Tests automated expiry checking, batch processing, and notification systems
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

/**
 * Test Step 2.2.2 - Expiry Automation Module
 */
class Test_VD_License_Rule_Expiry_Automation extends VD_Test_Case {

    /**
     * Expiry automation module instance
     *
     * @var VD_License_Rule_Expiry_Automation
     */
    private $expiry_automation;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();

        $this->skipIfModuleNotAvailable('rules.expiry_automation');
        $this->expiry_automation = $this->container->get('rules.expiry_automation');
    }

    /**
     * Test 1: Module Loading and Initialization
     */
    public function test_module_loading() {
        $this->assertModuleLoaded('rules.expiry_automation', 'VD_License_Rule_Expiry_Automation');
        $this->assertInstanceOf('VD_License_Rule_Expiry_Automation', $this->expiry_automation);
    }

    /**
     * Test 2: Automated Expiry Check - Batch Processing
     */
    public function test_automated_expiry_check_batch() {
        // Create test licenses with various expiry dates
        $licenses = [
            $this->create_test_license(['expires_at' => date('Y-m-d H:i:s', strtotime('+5 days'))]),
            $this->create_test_license(['expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]),
            $this->create_test_license(['expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))]),
            $this->create_test_license(['expires_at' => date('Y-m-d H:i:s', strtotime('-1 week'))])
        ];

        $batch_config = [
            'batch_size' => 10,
            'check_upcoming_days' => 30,
            'process_expired' => true,
            'dry_run' => true // Test mode
        ];

        $result = $this->expiry_automation->process_automated_expiry_check($batch_config);

        $this->assertValidResult($result, ['processed', 'expiring_soon', 'expired', 'warnings_sent']);
        $this->assertGreaterThanOrEqual(4, $result['processed']);
        $this->assertGreaterThanOrEqual(1, $result['expiring_soon']);
        $this->assertGreaterThanOrEqual(2, $result['expired']);
    }

    /**
     * Test 3: Identify Expiring Licenses
     */
    public function test_identify_expiring_licenses() {
        // Create licenses expiring at different times
        $test_data = [
            ['days' => 3, 'expected_in_result' => true],
            ['days' => 15, 'expected_in_result' => true],
            ['days' => 45, 'expected_in_result' => false],
            ['days' => -5, 'expected_in_result' => false] // Already expired
        ];

        $created_licenses = [];
        foreach ($test_data as $data) {
            $created_licenses[] = $this->create_test_license([
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$data['days']} days")),
                'license_key' => 'EXPIRY-ID-' . abs($data['days'])
            ]);
        }

        $criteria = [
            'days_ahead' => 30,
            'include_grace_period' => false,
            'status_filter' => ['active']
        ];

        $result = $this->expiry_automation->identify_expiring_licenses($criteria);

        $this->assertValidResult($result, ['count', 'licenses', 'criteria_used']);
        $this->assertEquals(2, $result['count']); // Only 3-day and 15-day should match
        $this->assertCount(2, $result['licenses']);
    }

    /**
     * Test 4: Process Expired Licenses
     */
    public function test_process_expired_licenses() {
        // Create expired licenses
        $expired_licenses = [
            $this->create_test_license([
                'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'active'
            ]),
            $this->create_test_license([
                'expires_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'status' => 'active'
            ])
        ];

        $processing_config = [
            'auto_deactivate' => true,
            'send_notifications' => false, // Skip notifications in tests
            'update_status' => true,
            'grace_period_hours' => 24
        ];

        $result = $this->expiry_automation->process_expired_licenses($processing_config);

        $this->assertValidResult($result, ['processed', 'deactivated', 'grace_period_applied', 'notifications_sent']);
        $this->assertGreaterThanOrEqual(2, $result['processed']);
        $this->assertGreaterThanOrEqual(1, $result['deactivated']); // Week-old should be deactivated
        $this->assertGreaterThanOrEqual(1, $result['grace_period_applied']); // 1-day old might get grace
    }

    /**
     * Test 5: Schedule Automation Tasks
     */
    public function test_schedule_automation_tasks() {
        $schedule_config = [
            'enable_cron' => false, // Test mode - don't actually schedule
            'intervals' => ['hourly', 'daily', 'weekly'],
            'tasks' => [
                'expiry_check' => ['interval' => 'hourly'],
                'notification_batch' => ['interval' => 'daily'],
                'cleanup_expired' => ['interval' => 'weekly']
            ]
        ];

        $result = $this->expiry_automation->schedule_automation_tasks($schedule_config);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('scheduled_tasks', $result);
        $this->assertArrayHasKey('next_run_times', $result);
        $this->assertCount(3, $result['scheduled_tasks']);
    }

    /**
     * Test 6: Notification Triggering
     */
    public function test_notification_triggering() {
        $license_expiring_soon = $this->create_test_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'user_id' => 123
        ]);

        $notification_config = [
            'warning_days' => [30, 7, 1],
            'send_email' => false, // Test mode
            'send_webhook' => false,
            'dry_run' => true
        ];

        $result = $this->expiry_automation->trigger_expiry_notifications($notification_config);

        $this->assertValidResult($result, ['notifications_prepared', 'email_queue', 'webhook_queue']);
        $this->assertGreaterThanOrEqual(1, $result['notifications_prepared']);
        $this->assertArrayHasKey('7_day_warnings', $result['email_queue']);
    }

    /**
     * Test 7: Grace Period Automation
     */
    public function test_grace_period_automation() {
        $recently_expired_license = $this->create_test_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('-12 hours')),
            'status' => 'active'
        ]);

        $grace_config = [
            'grace_period_hours' => 48,
            'auto_apply' => true,
            'notify_grace_start' => false // Test mode
        ];

        $result = $this->expiry_automation->apply_automated_grace_period($grace_config);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('grace_periods_applied', $result);
        $this->assertArrayHasKey('licenses_processed', $result);
        $this->assertGreaterThanOrEqual(1, $result['grace_periods_applied']);
    }

    /**
     * Test 8: Bulk Status Updates
     */
    public function test_bulk_status_updates() {
        // Create licenses in various states
        $licenses_for_update = [
            $this->create_test_license(['status' => 'active', 'expires_at' => date('Y-m-d H:i:s', strtotime('-2 days'))]),
            $this->create_test_license(['status' => 'active', 'expires_at' => date('Y-m-d H:i:s', strtotime('-1 week'))]),
            $this->create_test_license(['status' => 'warning', 'expires_at' => date('Y-m-d H:i:s', strtotime('-1 month'))])
        ];

        $update_rules = [
            'expired_to_inactive' => true,
            'long_expired_to_suspended' => true,
            'warning_expired_to_expired' => true,
            'batch_size' => 100
        ];

        $result = $this->expiry_automation->process_bulk_status_updates($update_rules);

        $this->assertValidResult($result, ['processed', 'updated', 'status_changes']);
        $this->assertGreaterThanOrEqual(3, $result['processed']);
        $this->assertGreaterThanOrEqual(3, $result['updated']);
        $this->assertArrayHasKey('expired', $result['status_changes']);
    }

    /**
     * Test 9: Cleanup Automation
     */
    public function test_cleanup_automation() {
        // Create old expired data
        $old_license = $this->create_test_license([
            'expires_at' => date('Y-m-d H:i:s', strtotime('-6 months')),
            'status' => 'expired'
        ]);

        $cleanup_config = [
            'remove_expired_older_than_days' => 180,
            'cleanup_activations' => true,
            'cleanup_logs' => true,
            'dry_run' => true // Test mode
        ];

        $result = $this->expiry_automation->run_automated_cleanup($cleanup_config);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('cleanup_summary', $result);
        $this->assertArrayHasKey('items_identified', $result['cleanup_summary']);
        $this->assertArrayHasKey('would_remove', $result['cleanup_summary']);
    }

    /**
     * Test 10: Cron Job Integration
     */
    public function test_cron_job_integration() {
        $cron_config = [
            'register_hooks' => false, // Test mode
            'validate_schedules' => true,
            'check_existing' => true
        ];

        $result = $this->expiry_automation->integrate_with_wp_cron($cron_config);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('cron_hooks', $result);
        $this->assertArrayHasKey('schedules_validated', $result);
        $this->assertTrue($result['schedules_validated']);
    }

    /**
     * Test 11: Automation Logging
     */
    public function test_automation_logging() {
        $log_config = [
            'log_level' => 'info',
            'include_performance' => true,
            'include_details' => true
        ];

        $automation_event = [
            'type' => 'expiry_check',
            'processed' => 50,
            'found_expired' => 5,
            'execution_time_ms' => 1200
        ];

        $result = $this->expiry_automation->log_automation_activity($automation_event, $log_config);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('log_entry_created', $result);
        $this->assertArrayHasKey('log_id', $result);
        $this->assertTrue($result['log_entry_created']);
    }

    /**
     * Test 12: Performance Test - Large Batch Automation
     */
    public function test_performance_large_batch_automation() {
        $large_batch = [];

        for ($i = 0; $i < 1000; $i++) {
            $days_offset = rand(-60, 60);
            $large_batch[] = $this->create_test_license([
                'license_key' => 'PERF-AUTO-' . $i,
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$days_offset} days"))
            ]);
        }

        $performance_config = [
            'batch_size' => 100,
            'max_execution_time' => 30,
            'memory_limit_mb' => 128,
            'dry_run' => true
        ];

        $performance_result = $this->measureExecutionTime(function() use ($performance_config) {
            return $this->expiry_automation->process_automated_expiry_check($performance_config);
        });

        $this->assertValidationSuccess($performance_result['result']);
        $this->assertLessThan(30000, $performance_result['execution_time_ms'], 'Large batch automation should complete within 30 seconds');

        VD_Test_Utils::log_test_execution('Performance Large Batch Automation', [
            'batch_size' => 1000,
            'execution_time_ms' => $performance_result['execution_time_ms'],
            'memory_used' => $performance_result['memory_used_formatted']
        ]);
    }

    /**
     * Test 13: Error Recovery & Retry Logic
     */
    public function test_error_recovery_retry_logic() {
        // Test with problematic data that might cause errors
        $problematic_license = $this->create_test_license([
            'expires_at' => 'invalid-date-format',
            'status' => 'unknown_status'
        ]);

        $retry_config = [
            'max_retries' => 3,
            'retry_delay_seconds' => 1,
            'skip_errors' => true,
            'log_errors' => false // Test mode
        ];

        $result = $this->expiry_automation->process_with_retry_logic($retry_config);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('retry_attempts', $result);
        $this->assertArrayHasKey('errors_handled', $result);
        $this->assertArrayHasKey('successful_recoveries', $result);
    }

    /**
     * Test 14: Webhook Integration
     */
    public function test_webhook_integration() {
        $webhook_config = [
            'webhook_url' => 'https://example.com/webhook/test',
            'events' => ['license_expired', 'expiry_warning', 'grace_period_started'],
            'send_webhooks' => false, // Test mode
            'validate_urls' => true
        ];

        $test_event = [
            'event_type' => 'license_expired',
            'license_id' => 123,
            'license_key' => 'TEST-WEBHOOK-123',
            'expired_at' => current_time('mysql')
        ];

        $result = $this->expiry_automation->process_webhook_notifications($webhook_config, [$test_event]);

        $this->assertValidationSuccess($result);
        $this->assertArrayHasKey('webhooks_prepared', $result);
        $this->assertArrayHasKey('validation_results', $result);
        $this->assertEquals(1, $result['webhooks_prepared']);
    }

    /**
     * Test 15: Automation Configuration Management
     */
    public function test_automation_configuration_management() {
        $dynamic_config = [
            'expiry_check_frequency' => 'hourly',
            'warning_days' => [30, 14, 7, 1],
            'grace_period_hours' => 72,
            'auto_deactivate_after_days' => 30,
            'batch_processing_size' => 50,
            'enable_notifications' => true,
            'webhook_endpoints' => ['https://api.example.com/notify']
        ];

        // Test configuration validation
        $validation_result = $this->expiry_automation->validate_automation_config($dynamic_config);
        $this->assertValidationSuccess($validation_result);

        // Test configuration application
        $application_result = $this->expiry_automation->apply_automation_config($dynamic_config);
        $this->assertValidationSuccess($application_result);
        $this->assertArrayHasKey('config_applied', $application_result);
        $this->assertTrue($application_result['config_applied']);

        // Test configuration retrieval
        $current_config = $this->expiry_automation->get_current_automation_config();
        $this->assertArrayHasKey('expiry_check_frequency', $current_config);
        $this->assertEquals('hourly', $current_config['expiry_check_frequency']);
    }

    /**
     * Helper: Create license batch with various expiry scenarios
     */
    private function create_expiry_test_batch($count = 10) {
        $batch = [];
        $scenarios = [
            ['days' => 30],   // Far future
            ['days' => 7],    // Warning period
            ['days' => 1],    // Critical period
            ['days' => -1],   // Recently expired
            ['days' => -30],  // Long expired
            ['days' => null]  // Lifetime
        ];

        for ($i = 0; $i < $count; $i++) {
            $scenario = $scenarios[$i % count($scenarios)];

            $expires_at = $scenario['days'] === null
                ? null
                : date('Y-m-d H:i:s', strtotime("+{$scenario['days']} days"));

            $batch[] = $this->create_test_license([
                'license_key' => 'BATCH-EXPIRY-' . $i,
                'expires_at' => $expires_at
            ]);
        }

        return $batch;
    }

    /**
     * Helper: Assert automation result structure
     */
    private function assertAutomationResult($result, $expected_keys = []) {
        $default_keys = ['processed', 'execution_time_ms', 'memory_used'];
        $all_keys = array_merge($default_keys, $expected_keys);

        foreach ($all_keys as $key) {
            $this->assertArrayHasKey($key, $result, "Automation result should contain '{$key}' key");
        }
    }
}
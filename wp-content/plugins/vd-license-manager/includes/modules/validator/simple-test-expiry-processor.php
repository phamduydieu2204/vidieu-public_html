<?php
/**
 * Simple Expiry Processor Test
 *
 * Quick verification script for Step 5.1.3 Expiry Processing Manager
 * Can be run via direct access or WP CLI
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    // Allow direct access for testing
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';
}

/**
 * Run expiry processor tests
 */
function test_expiry_processor_simple() {
    try {
        // Load the expiry processor class
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-expiry-processor.php';

        $expiry_processor = VD\LicenseManager\Validator\VD_License_Expiry_Processor::get_instance();

        $results = array();

        // Test 1: Singleton pattern
        $instance2 = VD\LicenseManager\Validator\VD_License_Expiry_Processor::get_instance();
        $results['singleton'] = ($expiry_processor === $instance2) ? 'PASS' : 'FAIL';

        // Test 2: Status enums
        $status_enums = $expiry_processor->get_valid_status_enums();
        $results['status_enums'] = (is_array($status_enums) && count($status_enums) >= 6) ? 'PASS' : 'FAIL';

        // Test 3: Configuration validation
        $valid_config = array(
            'batch_size' => 50,
            'grace_period_hours' => 24,
            'status_filters' => array('active', 'pending')
        );
        $config_result = $expiry_processor->validate_update_configuration($valid_config);
        $results['config_validation'] = (isset($config_result['valid']) && $config_result['valid']) ? 'PASS' : 'FAIL';

        // Test 4: Expiry date validation
        $future_license = array('expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')));
        $expiry_result = $expiry_processor->validate_license_expiry_date($future_license);
        $results['expiry_validation'] = (isset($expiry_result['valid']) && $expiry_result['valid']) ? 'PASS' : 'FAIL';

        // Test 5: Target status determination
        $expired_license = array('expires_at' => date('Y-m-d H:i:s', strtotime('-10 days')));
        $options = array('grace_period_hours' => 24, 'escalation_enabled' => true);
        $status_result = $expiry_processor->determine_target_status_for_expired_license($expired_license, $options);
        $results['status_determination'] = (isset($status_result['should_update']) && $status_result['should_update']) ? 'PASS' : 'FAIL';

        return array(
            'success' => true,
            'tests' => $results,
            'passed' => count(array_filter($results, function($r) { return $r === 'PASS'; })),
            'total' => count($results),
            'timestamp' => current_time('mysql')
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => current_time('mysql')
        );
    }
}

// Run test if accessed directly
if (!defined('DOING_AJAX') && !defined('WP_CLI')) {
    $result = test_expiry_processor_simple();
    echo "Step 5.1.3 Expiry Processor Test Results:\n";
    echo "=========================================\n";
    if ($result['success']) {
        echo "Status: SUCCESS\n";
        echo "Tests Passed: {$result['passed']}/{$result['total']}\n";
        echo "Details:\n";
        foreach ($result['tests'] as $test => $status) {
            echo "  - {$test}: {$status}\n";
        }
    } else {
        echo "Status: ERROR\n";
        echo "Error: {$result['error']}\n";
    }
    echo "Timestamp: {$result['timestamp']}\n";
}
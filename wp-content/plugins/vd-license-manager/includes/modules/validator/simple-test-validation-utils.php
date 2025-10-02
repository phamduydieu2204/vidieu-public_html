<?php
/**
 * Simple Validation Utils Test
 *
 * Quick verification script for Step 5.1.2 Validation Utils Manager
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
 * Run validation utils tests
 */
function test_validation_utils_simple() {
    try {
        // Load the validation utils class
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-validation-utils.php';

        $validation_utils = VD\LicenseManager\Validator\VD_License_Validation_Utils::get_instance();

        $results = array();

        // Test 1: Singleton pattern
        $instance2 = VD\LicenseManager\Validator\VD_License_Validation_Utils::get_instance();
        $results['singleton'] = ($validation_utils === $instance2) ? 'PASS' : 'FAIL';

        // Test 2: Database utilities - check wp_options table
        global $wpdb;
        $wp_options_exists = $validation_utils->table_exists($wpdb->prefix . 'options');
        $results['database_utils'] = $wp_options_exists ? 'PASS' : 'FAIL';

        // Test 3: Memory utilities
        $memory_info = $validation_utils->get_memory_usage_info();
        $results['memory_utils'] = isset($memory_info['current_usage']) ? 'PASS' : 'FAIL';

        // Test 4: Error creation
        $error = $validation_utils->create_validation_error('test', 'Test error');
        $results['error_creation'] = (isset($error['valid']) && $error['valid'] === false) ? 'PASS' : 'FAIL';

        // Test 5: Format validation
        $valid_key = $validation_utils->validate_license_key_format('VD-ABCD1234-EFGH5678-IJKL9012');
        $results['format_validation'] = (isset($valid_key['valid']) && $valid_key['valid'] === true) ? 'PASS' : 'FAIL';

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
    $result = test_validation_utils_simple();
    echo "Step 5.1.2 Validation Utils Test Results:\n";
    echo "========================================\n";
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
<?php
/**
 * Test Device Logic with Real License
 */

define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

header('Content-Type: text/plain');

global $wpdb;

$test_license = 'H10D-DIJD-14RC-SOLE-6KUV30';
$license_table = $wpdb->prefix . 'vd_license_keys';

echo "=== Test Device Logic with Real Data ===\n\n";

// Step 1: Get license
$license = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$license_table} WHERE license_key_plain = %s",
    $test_license
));

if (!$license) {
    echo "❌ License not found: {$test_license}\n";
    exit;
}

echo "✅ License found:\n";
echo "  ID: {$license->id}\n";
echo "  Product ID: {$license->product_id}\n";
echo "  Status: {$license->status}\n";
echo "  Max Devices: {$license->max_devices}\n";
echo "  Created: {$license->created_at}\n\n";

// Step 2: Test device manager config
if (class_exists('VD_LM_Device_Manager')) {
    echo "✅ VD_LM_Device_Manager class found\n";

    $device_manager = new VD_LM_Device_Manager();

    // Call the private method using reflection
    $reflection = new ReflectionClass($device_manager);
    $method = $reflection->getMethod('get_license_device_config');
    $method->setAccessible(true);

    $device_config = $method->invoke($device_manager, $license->id);

    echo "Device Config Result:\n";
    echo "  Max Devices: {$device_config['max_devices']}\n";
    echo "  Strict Mode: {$device_config['strict_mode']}\n";
    echo "  Allow Replacement: {$device_config['allow_device_replacement']}\n";
    echo "  Cooldown Hours: {$device_config['device_cooldown_hours']}\n\n";

} else {
    echo "❌ VD_LM_Device_Manager class NOT found\n";
}

// Step 3: Check product config if needed
if ($license->max_devices == 0) {
    echo "License max_devices is 0, checking product config...\n";
    $product_config_table = $wpdb->prefix . 'vd_product_share_configs';
    $product_config = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$product_config_table} WHERE product_id = %d",
        $license->product_id
    ));

    if ($product_config) {
        echo "✅ Product config found:\n";
        echo "  Product ID: {$product_config->product_id}\n";
        echo "  Max Devices Per License: {$product_config->max_devices_per_license}\n";
        echo "  Share Type: {$product_config->share_type}\n\n";
    } else {
        echo "❌ No product config found for product ID: {$license->product_id}\n\n";
    }
}

// Step 4: Direct API test simulation
echo "=== Simulating API Call ===\n";
$device_combined_id = 'test_fp_new';
$device_name = 'Test Device';

try {
    $validation_result = $device_manager->validate_and_register_device(
        $license,
        $device_combined_id,
        $device_name,
        '127.0.0.1',
        'Test User Agent'
    );

    if (is_wp_error($validation_result)) {
        echo "❌ Device validation failed: " . $validation_result->get_error_message() . "\n";
        echo "   Error code: " . $validation_result->get_error_code() . "\n";
    } else {
        echo "✅ Device validation successful!\n";
        echo "   Device ID: " . $validation_result['device_id'] . "\n";
        echo "   Is New: " . ($validation_result['is_new_device'] ? 'Yes' : 'No') . "\n";
        echo "   Slot: " . $validation_result['slot'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "Plugin Path: " . __DIR__ . "\n";
?>
<?php
/**
 * Debug Device Config Logic
 */

define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

header('Content-Type: text/plain');

global $wpdb;

$test_license = 'H10D-MF9H-KMTB-JQMO';
$license_table = $wpdb->prefix . 'vd_license_keys';

echo "=== Debug Device Config Logic ===\n\n";

// Step 1: Find license
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
echo "  Max Devices: {$license->max_devices}\n\n";

// Step 2: Check device limits table
$limits_table = $wpdb->prefix . 'vd_license_device_limits';
$device_limits = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$limits_table} WHERE license_id = %d",
    $license->id
), ARRAY_A);

echo "Device Limits Table Check:\n";
if ($device_limits) {
    echo "  ✅ Found device limits record\n";
    echo "  Max Devices: {$device_limits['max_devices']}\n";
} else {
    echo "  ❌ No device limits record found\n";
}
echo "\n";

// Step 3: Check product config
$product_config_table = $wpdb->prefix . 'vd_product_share_configs';
$product_config = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$product_config_table} WHERE product_id = %d",
    $license->product_id
), ARRAY_A);

echo "Product Config Check:\n";
if ($product_config) {
    echo "  ✅ Found product config\n";
    echo "  Max Devices Per License: {$product_config['max_devices_per_license']}\n";
} else {
    echo "  ❌ No product config found\n";
}
echo "\n";

// Step 4: Simulate logic
echo "Device Config Logic Simulation:\n";

if ($device_limits) {
    echo "  → Using device limits table: {$device_limits['max_devices']}\n";
    $final_max = $device_limits['max_devices'];
} else {
    $license_max = $license->max_devices ?? 2;
    echo "  → License max_devices: {$license_max}\n";

    if ($license_max == 0 && $product_config) {
        echo "  → License max = 0, using product config: {$product_config['max_devices_per_license']}\n";
        $final_max = $product_config['max_devices_per_license'];
    } else {
        echo "  → Using license max_devices: {$license_max}\n";
        $final_max = $license_max;
    }
}

echo "\n✅ Final max_devices: {$final_max}\n\n";

// Step 5: Check existing devices
$devices_table = $wpdb->prefix . 'vd_license_devices';
$existing_devices = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$devices_table} WHERE license_id = %d AND status = 'active'",
    $license->id
));

echo "Existing Devices Check:\n";
echo "  Active devices: " . count($existing_devices) . "\n";
echo "  Max allowed: {$final_max}\n";
echo "  Can register new: " . (count($existing_devices) < $final_max ? 'YES' : 'NO') . "\n\n";

if (count($existing_devices) > 0) {
    echo "Active devices list:\n";
    foreach ($existing_devices as $device) {
        echo "  - ID: {$device->id}, Combined ID: " . substr($device->device_combined_id, 0, 12) . "...\n";
    }
}

echo "\n=== Conclusion ===\n";
if (count($existing_devices) < $final_max) {
    echo "✅ Device registration should work!\n";
} else {
    echo "❌ Device limit reached - registration will fail\n";
}
?>
<?php
/**
 * Direct SQL Update for License
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

echo "=== License Fix SQL Update ===\n\n";

// Check current license
$license = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$license_table} WHERE license_key_plain = %s",
    $test_license
));

if ($license) {
    echo "Current License:\n";
    echo "  ID: {$license->id}\n";
    echo "  Product ID: {$license->product_id}\n";
    echo "  Status: {$license->status}\n";
    echo "  Max Devices: {$license->max_devices}\n\n";

    if ($license->max_devices == 0) {
        echo "Updating max_devices to 2...\n";

        $sql = $wpdb->prepare(
            "UPDATE {$license_table} SET max_devices = 2 WHERE id = %d",
            $license->id
        );

        $result = $wpdb->query($sql);

        if ($result !== false) {
            echo "✅ SUCCESS: Updated max_devices to 2\n";

            // Verify
            $updated = $wpdb->get_var($wpdb->prepare(
                "SELECT max_devices FROM {$license_table} WHERE id = %d",
                $license->id
            ));
            echo "✅ VERIFIED: max_devices = {$updated}\n\n";
        } else {
            echo "❌ FAILED: " . $wpdb->last_error . "\n\n";
        }
    } else {
        echo "✅ License already allows {$license->max_devices} devices\n\n";
    }
} else {
    echo "❌ License not found\n\n";
}

echo "=== Test Command ===\n";
echo "curl -X POST \"https://vidieu.vn/wp-json/vd/v1/license/access\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"license_key\": \"{$test_license}\", \"device_name\": \"Test Device\", \"device_fingerprint\": \"test_fp_456\"}'\n\n";

echo "=== Portal Test ===\n";
echo "Visit: https://vidieu.vn/license-portal/\n";
echo "Enter license: {$test_license}\n";
echo "Expected: Device registration should work\n";
?>
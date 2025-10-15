<?php
/**
 * Fix License Device Limits
 * Updates test license to allow device registration
 */

define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "<h1>🔧 Fix License Device Limits</h1>\n";

global $wpdb;
$license_table = $wpdb->prefix . 'vd_license_keys';
$test_license = 'H10D-DIJD-14RC-SOLE-6KUV30';

echo "<h2>1. Current License Status</h2>\n";

$license = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$license_table} WHERE license_key_plain = %s",
    $test_license
));

if ($license) {
    echo "<p>✅ License found:</p>\n";
    echo "<ul>\n";
    echo "<li>ID: {$license->id}</li>\n";
    echo "<li>Product ID: {$license->product_id}</li>\n";
    echo "<li>Status: {$license->status}</li>\n";
    echo "<li>Max Devices: <strong>{$license->max_devices}</strong></li>\n";
    echo "</ul>\n";

    if ($license->max_devices == 0) {
        echo "<h2>2. Fixing Device Limit</h2>\n";

        $result = $wpdb->update(
            $license_table,
            ['max_devices' => 2],
            ['id' => $license->id],
            ['%d'],
            ['%d']
        );

        if ($result !== false) {
            echo "<p style='color: green;'>✅ Updated max_devices from 0 to 2</p>\n";

            // Verify update
            $updated_license = $wpdb->get_row($wpdb->prepare(
                "SELECT max_devices FROM {$license_table} WHERE id = %d",
                $license->id
            ));

            echo "<p>✅ Verified: max_devices = {$updated_license->max_devices}</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Failed to update: " . $wpdb->last_error . "</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✅ License already allows {$license->max_devices} devices</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ License not found</p>\n";
}

echo "<h2>3. Test API Again</h2>\n";
echo "<p>Now try the API endpoint again:</p>\n";
echo "<pre>\n";
echo "curl -X POST \"https://vidieu.vn/wp-json/vd/v1/license/access\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"license_key\": \"{$test_license}\", \"device_name\": \"Test Device\", \"device_fingerprint\": \"test_fp_123\"}'\n";
echo "</pre>\n";

echo "<h2>4. Next Steps</h2>\n";
echo "<ul>\n";
echo "<li><a href='https://vidieu.vn/license-portal/' target='_blank'>🌐 Test Portal UI</a></li>\n";
echo "<li><a href='" . admin_url('admin.php?page=vd-devices') . "' target='_blank'>⚙️ Device Management</a></li>\n";
echo "<li><a href='check-devices.php' target='_blank'>🔍 Device Verification</a></li>\n";
echo "</ul>\n";
?>
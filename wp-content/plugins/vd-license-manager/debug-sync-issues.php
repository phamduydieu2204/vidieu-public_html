<?php
/**
 * Debug License Sync and Database Issues
 */

define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

header('Content-Type: text/plain');

global $wpdb;

$test_license = 'H10D-M117-ERXR-6R8Y';
$product_id = 8210; // Helium10

echo "=== VD License Manager Debug ===\n\n";

// 1. Check bz_vd_license_access_log table structure
echo "1. ACCESS LOG TABLE STRUCTURE:\n";
$access_log_table = $wpdb->prefix . 'vd_license_access_log';
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $access_log_table));

if ($table_exists) {
    echo "✅ Table exists: {$access_log_table}\n";
    $columns = $wpdb->get_results("DESCRIBE {$access_log_table}");
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
} else {
    echo "❌ Table missing: {$access_log_table}\n";
}
echo "\n";

// 2. Check LMfWC license table for our test license
echo "2. LMfWC LICENSE CHECK:\n";
$lmfwc_table = $wpdb->prefix . 'lmfwc_licenses';
$lmfwc_license = $wpdb->get_row($wpdb->prepare(
    "SELECT id, license_key, order_id, product_id, status, valid_for, expires_at, created_at
     FROM {$lmfwc_table}
     WHERE license_key = %s",
    $test_license
));

if ($lmfwc_license) {
    echo "✅ Found in LMfWC:\n";
    echo "  ID: {$lmfwc_license->id}\n";
    echo "  License Key: {$lmfwc_license->license_key}\n";
    echo "  Order ID: {$lmfwc_license->order_id}\n";
    echo "  Product ID: {$lmfwc_license->product_id}\n";
    echo "  Status: {$lmfwc_license->status}\n";
    echo "  Valid For: {$lmfwc_license->valid_for}\n";
    echo "  Expires At: {$lmfwc_license->expires_at}\n";
    echo "  Created At: {$lmfwc_license->created_at}\n";

    // Status meanings
    $status_map = [
        1 => 'INACTIVE',
        2 => 'SOLD',
        3 => 'DELIVERED',
        4 => 'ACTIVE'
    ];
    echo "  Status Meaning: " . ($status_map[$lmfwc_license->status] ?? 'UNKNOWN') . "\n";
} else {
    echo "❌ Not found in LMfWC table\n";
}
echo "\n";

// 3. Check VD license table
echo "3. VD LICENSE TABLE CHECK:\n";
$vd_license_table = $wpdb->prefix . 'vd_license_keys';
$vd_license = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$vd_license_table} WHERE license_key_plain = %s",
    $test_license
));

if ($vd_license) {
    echo "✅ Found in VD table:\n";
    echo "  ID: {$vd_license->id}\n";
    echo "  License Key Plain: {$vd_license->license_key_plain}\n";
    echo "  LMfWC License ID: {$vd_license->lmfwc_license_id}\n";
    echo "  Product ID: {$vd_license->product_id}\n";
    echo "  Status: {$vd_license->status}\n";
    echo "  Max Devices: {$vd_license->max_devices}\n";
    echo "  Pool ID: {$vd_license->assigned_pool_id}\n";
    echo "  Account ID: {$vd_license->assigned_account_id}\n";
} else {
    echo "❌ Not found in VD table\n";
}
echo "\n";

// 4. Check if there's a WooCommerce order for this license
if (isset($lmfwc_license) && $lmfwc_license->order_id) {
    echo "4. WOOCOMMERCE ORDER CHECK:\n";
    $order = wc_get_order($lmfwc_license->order_id);
    if ($order) {
        echo "✅ Order found: #{$lmfwc_license->order_id}\n";
        echo "  Status: {$order->get_status()}\n";
        echo "  Date Created: {$order->get_date_created()}\n";
        echo "  Customer: {$order->get_billing_email()}\n";

        // Check if VD processed this order
        $vd_processed = $order->get_meta('_vd_licenses_processed', true);
        echo "  VD Processed: " . ($vd_processed ? 'YES' : 'NO') . "\n";

        echo "  Items:\n";
        foreach ($order->get_items() as $item) {
            echo "    - Product #{$item->get_product_id()}: {$item->get_name()} (Qty: {$item->get_quantity()})\n";
        }
    } else {
        echo "❌ Order not found: #{$lmfwc_license->order_id}\n";
    }
    echo "\n";
}

// 5. Check product configuration
echo "5. PRODUCT CONFIGURATION CHECK:\n";
$config_table = $wpdb->prefix . 'vd_product_share_configs';
$product_config = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$config_table} WHERE product_id = %d",
    $product_id
));

if ($product_config) {
    echo "✅ Product config found:\n";
    echo "  Product ID: {$product_config->product_id}\n";
    echo "  Max Devices: {$product_config->max_devices_per_license}\n";
    echo "  Response Fields: {$product_config->response_fields}\n";
    echo "  Is Active: {$product_config->is_active}\n";
} else {
    echo "❌ No product configuration for product {$product_id}\n";
}
echo "\n";

// 6. Manual sync test
if (isset($lmfwc_license) && !$vd_license) {
    echo "6. MANUAL SYNC TEST:\n";
    echo "License exists in LMfWC but not in VD table.\n";
    echo "Status: {$lmfwc_license->status} (" . ($status_map[$lmfwc_license->status] ?? 'UNKNOWN') . ")\n";

    if (in_array($lmfwc_license->status, [2, 3])) {
        echo "✅ Status is SOLD/DELIVERED - should be synced\n";
    } else {
        echo "❌ Status is not SOLD/DELIVERED - won't be synced automatically\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "License in LMfWC: " . ($lmfwc_license ? "YES" : "NO") . "\n";
echo "License in VD: " . ($vd_license ? "YES" : "NO") . "\n";
echo "Access log table: " . ($table_exists ? "EXISTS" : "MISSING") . "\n";
echo "Product config: " . ($product_config ? "EXISTS" : "MISSING") . "\n";

if ($lmfwc_license && !$vd_license) {
    echo "\n❗ ISSUE: License not synced from LMfWC to VD\n";
    echo "Possible reasons:\n";
    echo "- Order not marked as completed\n";
    echo "- License status not SOLD(2) or DELIVERED(3)\n";
    echo "- VD Order Handler not triggered\n";
    echo "- Database error during sync\n";
}
?>
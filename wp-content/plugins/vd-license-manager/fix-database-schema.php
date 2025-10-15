<?php
/**
 * Fix Database Schema Issues
 */

define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

header('Content-Type: text/plain');
echo "=== VD License Manager Database Fix ===\n\n";

global $wpdb;

// Fix 1: Check and fix access log table
echo "1. FIXING ACCESS LOG TABLE:\n";
$access_log_table = $wpdb->prefix . 'vd_license_access_log';

// Check if table exists
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $access_log_table));

if ($table_exists) {
    echo "✅ Table exists: {$access_log_table}\n";

    // Check columns
    $columns = $wpdb->get_results("DESCRIBE {$access_log_table}");
    $column_names = array_column($columns, 'Field');

    echo "Current columns: " . implode(', ', $column_names) . "\n";

    if (!in_array('created_at', $column_names)) {
        echo "❌ Missing 'created_at' column, adding...\n";
        $wpdb->query("ALTER TABLE {$access_log_table} ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        if ($wpdb->last_error) {
            echo "❌ Error: " . $wpdb->last_error . "\n";
        } else {
            echo "✅ Added 'created_at' column\n";
        }
    } else {
        echo "✅ 'created_at' column exists\n";
    }
} else {
    echo "❌ Table missing, creating...\n";

    // Re-create the table using the database class
    require_once 'includes/class-vd-lm-database.php';
    VD_LM_Database::create_tables();
    echo "✅ Database tables created\n";
}

echo "\n2. MANUAL LICENSE SYNC:\n";

$test_license = 'H10D-M117-ERXR-6R8Y';
$product_id = 8210;

// Check if license exists in LMfWC
$lmfwc_table = $wpdb->prefix . 'lmfwc_licenses';
$lmfwc_license = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$lmfwc_table} WHERE license_key = %s",
    $test_license
));

if ($lmfwc_license) {
    echo "✅ Found in LMfWC: {$test_license}\n";
    echo "  Order ID: {$lmfwc_license->order_id}\n";
    echo "  Status: {$lmfwc_license->status}\n";

    // Check if already in VD table
    $vd_table = $wpdb->prefix . 'vd_license_keys';
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$vd_table} WHERE license_key_plain = %s",
        $test_license
    ));

    if ($existing) {
        echo "✅ Already exists in VD table\n";
    } else {
        echo "📝 Adding to VD table...\n";

        // Manual insert
        $result = $wpdb->insert(
            $vd_table,
            [
                'license_key' => hash('sha256', $test_license),
                'license_key_plain' => $test_license,
                'lmfwc_license_id' => $lmfwc_license->id,
                'product_id' => $lmfwc_license->product_id,
                'customer_id' => $lmfwc_license->user_id,
                'order_id' => $lmfwc_license->order_id,
                'status' => 'active',
                'max_devices' => 2, // Default from product config
                'expires_at' => $lmfwc_license->expires_at,
                'synced_at' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            [
                '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s'
            ]
        );

        if ($result) {
            $new_id = $wpdb->insert_id;
            echo "✅ License synced successfully (ID: {$new_id})\n";
        } else {
            echo "❌ Sync failed: " . $wpdb->last_error . "\n";
        }
    }
} else {
    echo "❌ License not found in LMfWC: {$test_license}\n";
}

echo "\n3. CREATING PRODUCT CONFIG:\n";

$config_table = $wpdb->prefix . 'vd_product_share_configs';
$existing_config = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$config_table} WHERE product_id = %d",
    $product_id
));

if ($existing_config) {
    echo "✅ Product config already exists for product {$product_id}\n";
} else {
    echo "📝 Creating product config for product {$product_id}...\n";

    $response_fields = json_encode([
        'fields' => [
            [
                'key' => 'cookie',
                'label' => 'Session Cookie',
                'type' => 'textarea',
                'order' => 1,
                'required' => true
            ]
        ]
    ]);

    $result = $wpdb->insert(
        $config_table,
        [
            'product_id' => $product_id,
            'max_devices_per_license' => 2,
            'device_reset_days' => 7,
            'max_requests_per_day' => 10,
            'response_fields' => $response_fields,
            'pool_assignment_rule' => 'priority',
            'is_active' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ],
        [
            '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s'
        ]
    );

    if ($result) {
        echo "✅ Product config created\n";
    } else {
        echo "❌ Failed to create product config: " . $wpdb->last_error . "\n";
    }
}

echo "\n=== FINAL TEST ===\n";

// Test the portal now
echo "Test the license portal at: https://vidieu.vn/license-portal/\n";
echo "Use license: {$test_license}\n";
echo "Expected: License should be found and show device registration form\n";

echo "\n✅ Fix completed!\n";
?>
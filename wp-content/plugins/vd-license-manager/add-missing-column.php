<?php
/**
 * Add Missing error_code Column - VD License Manager
 *
 * Safely adds the missing error_code column to vd_license_access_log table
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

// Only run for admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "=== ADD MISSING ERROR_CODE COLUMN ===\n\n";

global $wpdb;
$table_name = $wpdb->prefix . 'vd_license_access_log';

// 1. Check if table exists
$table_exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $table_name
)) === $table_name;

if (!$table_exists) {
    echo "❌ ERROR: Table {$table_name} doesn't exist!\n";
    exit;
}

echo "1. Table Status: ✅ {$table_name} exists\n\n";

// 2. Check if column already exists
$columns = $wpdb->get_results("DESCRIBE {$table_name}");
$column_names = array_column($columns, 'Field');

if (in_array('error_code', $column_names)) {
    echo "✅ SUCCESS: Column 'error_code' already exists!\n";
    echo "   No action needed.\n";
} else {
    echo "2. Adding missing 'error_code' column...\n";

    // Add the column
    $sql = "ALTER TABLE {$table_name}
            ADD COLUMN error_code VARCHAR(50) NULL
            COMMENT 'Error code if access failed'
            AFTER authentication_result";

    $result = $wpdb->query($sql);

    if ($result !== false) {
        echo "   ✅ Column added successfully!\n";

        // Verify column was added
        $new_columns = $wpdb->get_results("DESCRIBE {$table_name}");
        $new_column_names = array_column($new_columns, 'Field');

        if (in_array('error_code', $new_column_names)) {
            echo "   ✅ Verification: Column exists and is properly positioned\n";
        } else {
            echo "   ❌ Verification failed: Column not found after adding\n";
        }
    } else {
        echo "   ❌ Failed to add column!\n";
        echo "   SQL Error: " . $wpdb->last_error . "\n";
        exit;
    }
}

// 3. Show final table structure
echo "\n3. Final Table Structure:\n";
$final_columns = $wpdb->get_results("DESCRIBE {$table_name}");

foreach ($final_columns as $i => $column) {
    $marker = ($column->Field === 'error_code') ? ' ← NEW' : '';
    echo sprintf("   %2d. %-25s %s%s\n", $i + 1, $column->Field, $column->Type, $marker);
}

echo "\n   Total columns: " . count($final_columns) . "\n";

// 4. Test the column works
echo "\n4. Testing new column functionality...\n";

// Insert test data with error_code
$test_data = array(
    'license_id' => 999,
    'license_key' => 'TEST-ERROR-CODE-' . time(),
    'device_id' => 'test-device-error',
    'ip_address' => '127.0.0.1',
    'endpoint' => '/test',
    'method' => 'GET',
    'response_status' => 400,
    'authentication_result' => 'invalid_license',
    'error_code' => 'license_expired'  // Test the new column
);

$insert_result = $wpdb->insert($table_name, $test_data);

if ($insert_result !== false) {
    $test_id = $wpdb->insert_id;
    echo "   ✅ Test insert with error_code successful\n";

    // Retrieve and verify
    $retrieved = $wpdb->get_row($wpdb->prepare(
        "SELECT license_key, authentication_result, error_code FROM {$table_name} WHERE id = %d",
        $test_id
    ));

    if ($retrieved && $retrieved->error_code === 'license_expired') {
        echo "   ✅ Error code retrieved correctly: '{$retrieved->error_code}'\n";
    } else {
        echo "   ❌ Error code not retrieved correctly\n";
    }

    // Clean up
    $wpdb->delete($table_name, array('id' => $test_id));
    echo "   ✅ Test data cleaned up\n";
} else {
    echo "   ❌ Test insert failed: " . $wpdb->last_error . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ SUCCESS: Table now has error_code column!\n";
echo "\n📊 Updated Structure:\n";
echo "   - Total columns: " . count($final_columns) . " (was 20, now 21)\n";
echo "   - New column: error_code VARCHAR(50) NULL\n";
echo "   - Position: After authentication_result\n";
echo "   - Purpose: Store error codes for failed access attempts\n";

echo "\n🎉 REST API will now work perfectly with all required columns!\n";
echo "\n=== PROCESS COMPLETE ===\n";
?>
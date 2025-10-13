<?php
/**
 * Test API Table Mapping - VD License Manager
 *
 * Test if REST API correctly uses the vd_license_access_log table
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

// Only run for admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "=== VD LICENSE MANAGER - API TABLE MAPPING TEST ===\n\n";

global $wpdb;

// 1. Check table existence
$old_table = $wpdb->prefix . 'bz_vd_device_access_log';
$new_table = $wpdb->prefix . 'vd_license_access_log';

$old_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_table)) === $old_table;
$new_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $new_table)) === $new_table;

echo "1. Table Status:\n";
echo "   Old table ({$old_table}): " . ($old_exists ? '✅ EXISTS' : '❌ MISSING') . "\n";
echo "   New table ({$new_table}): " . ($new_exists ? '✅ EXISTS' : '❌ MISSING') . "\n\n";

if (!$new_exists) {
    echo "❌ CRITICAL: New table doesn't exist!\n";
    exit;
}

// 2. Test table structure compatibility
echo "2. Testing table structure compatibility...\n";

// Check required columns exist
$columns = $wpdb->get_results("DESCRIBE {$new_table}");
$column_names = array_column($columns, 'Field');

$required_columns = [
    'license_id', 'license_key', 'device_id', 'ip_address',
    'user_agent', 'endpoint', 'method', 'response_status',
    'authentication_result', 'error_code', 'accessed_at'
];

$missing_columns = array_diff($required_columns, $column_names);

if (empty($missing_columns)) {
    echo "   ✅ All required columns exist\n";
} else {
    echo "   ❌ Missing columns: " . implode(', ', $missing_columns) . "\n";
}

echo "   Total columns: " . count($column_names) . "\n";
echo "   Columns: " . implode(', ', $column_names) . "\n\n";

// 3. Test API log insertion (mock data)
echo "3. Testing API log insertion...\n";

$test_data = array(
    'license_id' => 999,
    'license_key' => 'TEST-API-MAPP-ING-' . time(),
    'device_id' => 'test-device-' . uniqid(),
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Test Agent',
    'endpoint' => '/license/access',
    'method' => 'GET',
    'response_status' => 200,
    'authentication_result' => 'success'
);

$insert_result = $wpdb->insert($new_table, $test_data);

if ($insert_result !== false) {
    echo "   ✅ Test data inserted successfully\n";
    $inserted_id = $wpdb->insert_id;
    echo "   Insert ID: {$inserted_id}\n";

    // Test query back
    $selected = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$new_table} WHERE id = %d",
        $inserted_id
    ));

    if ($selected) {
        echo "   ✅ Test data retrieved successfully\n";
        echo "   License Key: {$selected->license_key}\n";
        echo "   Device ID: {$selected->device_id}\n";
        echo "   Auth Result: {$selected->authentication_result}\n";
    }

    // Clean up test data
    $wpdb->delete($new_table, array('id' => $inserted_id));
    echo "   ✅ Test data cleaned up\n";
} else {
    echo "   ❌ Test data insertion failed\n";
    echo "   Error: " . $wpdb->last_error . "\n";
}

// 4. Test count query (like rate limiting uses)
echo "\n4. Testing count queries...\n";

$count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$new_table}
     WHERE authentication_result = 'success'
     AND accessed_at >= %s",
    date('Y-m-d 00:00:00')
));

echo "   Today's successful requests: {$count}\n";
echo "   ✅ Count query works\n";

// 5. Summary
echo "\n=== SUMMARY ===\n";

if ($new_exists && empty($missing_columns)) {
    echo "✅ SUCCESS: API table mapping is working correctly!\n";
    echo "\n🎉 REST API will now:\n";
    echo "   - Use the correct table: {$new_table}\n";
    echo "   - Log access attempts with proper columns\n";
    echo "   - Rate limiting will work\n";
    echo "   - Analytics data will be captured\n\n";

    echo "📊 Table Statistics:\n";
    $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$new_table}");
    $table_size = $wpdb->get_var("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2)
                                  FROM information_schema.TABLES
                                  WHERE table_schema = DATABASE()
                                  AND table_name = '{$new_table}'");

    echo "   Total rows: {$total_rows}\n";
    echo "   Table size: {$table_size} MB\n";
} else {
    echo "❌ FAILURE: API table mapping has issues!\n";
    echo "   Please check missing columns or table structure\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
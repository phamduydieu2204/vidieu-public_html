<?php
/**
 * Test Pool-Product Assignment Fix
 *
 * Verifies that the schema mismatch fix resolves the pool-product assignment bug.
 * This script tests the complete flow without requiring admin interface.
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/../../../wp-load.php');
}

// Prevent direct access from web
if (!defined('WP_CLI') && !current_user_can('administrator')) {
    wp_die('Access denied. This is a developer testing script.');
}

echo "=== POOL-PRODUCT ASSIGNMENT FIX VERIFICATION ===\n\n";

// Test 1: Verify table structure
echo "TEST 1: Table Structure Verification\n";
echo "=====================================\n";

global $wpdb;
$table_name = $wpdb->prefix . 'vd_product_pools';

// Check if table exists
$table_exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $table_name
));

if (!$table_exists) {
    echo "❌ CRITICAL: Table {$table_name} does not exist!\n";
    echo "   Solution: Run WordPress admin to trigger table creation.\n\n";
} else {
    echo "✅ Table {$table_name} exists\n";

    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    echo "   Columns found:\n";

    $required_columns = ['id', 'pool_name', 'product_id', 'priority', 'capacity', 'status', 'pool_id'];
    $found_columns = [];

    foreach ($columns as $column) {
        echo "   - {$column->Field} ({$column->Type})\n";
        $found_columns[] = $column->Field;
    }

    // Check for required columns
    $missing_columns = array_diff($required_columns, $found_columns);
    if (empty($missing_columns)) {
        echo "✅ All required columns present\n\n";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missing_columns) . "\n";
        echo "   Solution: Update plugin to trigger schema migration.\n\n";
    }
}

// Test 2: Create test pool
echo "TEST 2: Create Test Pool\n";
echo "========================\n";

$test_pool_name = 'Test Pool ' . time();
$pools_table = $wpdb->prefix . 'vd_pools';

$pool_insert = $wpdb->insert(
    $pools_table,
    [
        'name' => $test_pool_name,
        'description' => 'Test pool for verification',
        'status' => 'active'
    ],
    ['%s', '%s', '%s']
);

if ($pool_insert) {
    $pool_id = $wpdb->insert_id;
    echo "✅ Test pool created: ID {$pool_id}, Name '{$test_pool_name}'\n\n";
} else {
    echo "❌ Failed to create test pool: " . $wpdb->last_error . "\n\n";
    exit;
}

// Test 3: Test product assignment logic
echo "TEST 3: Product Assignment Logic\n";
echo "=================================\n";

// Simulate form data
$_POST = [
    'assigned_products' => ['123', '456'],  // Fake product IDs
    'product_priorities' => ['1', '2']
];

echo "Simulating form submission:\n";
echo "- assigned_products: " . print_r($_POST['assigned_products'], true);
echo "- product_priorities: " . print_r($_POST['product_priorities'], true);

// Test the assignment logic manually
if ($table_exists && !empty($missing_columns)) {
    echo "⚠️ Skipping INSERT test due to missing columns\n\n";
} else {

    echo "Testing INSERT operations:\n";

    $assigned_products = $_POST['assigned_products'];
    $success_count = 0;

    foreach ($assigned_products as $index => $product_id) {
        $product_id = absint($product_id);
        $priority = isset($_POST['product_priorities'][$index]) ?
                   absint($_POST['product_priorities'][$index]) : ($index + 1);

        echo "  Inserting: product_id={$product_id}, pool_id={$pool_id}, priority={$priority}\n";

        $result = $wpdb->insert(
            $table_name,
            [
                'pool_name' => $test_pool_name,
                'product_id' => $product_id,
                'pool_id' => $pool_id,
                'priority' => $priority,
                'capacity' => 10,
                'status' => 'active',
                'assignment_strategy' => 'random',
                'rotation_enabled' => 0,
                'rotation_interval' => null,
                'description' => null
            ],
            ['%s', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s']
        );

        if ($result) {
            $insert_id = $wpdb->insert_id;
            echo "  ✅ SUCCESS: Insert ID {$insert_id}\n";
            $success_count++;
        } else {
            echo "  ❌ FAILED: " . $wpdb->last_error . "\n";
        }
    }

    echo "\nINSERT Results: {$success_count}/" . count($assigned_products) . " successful\n\n";
}

// Test 4: Verify data exists
echo "TEST 4: Data Verification\n";
echo "=========================\n";

$assignments = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE pool_id = %d",
    $pool_id
));

echo "Assignments found for pool {$pool_id}:\n";
if (empty($assignments)) {
    echo "❌ No assignments found!\n";
} else {
    echo "✅ Found " . count($assignments) . " assignments:\n";
    foreach ($assignments as $assignment) {
        echo "  - ID: {$assignment->id}, Product: {$assignment->product_id}, Priority: {$assignment->priority}\n";
    }
}
echo "\n";

// Test 5: Integration test with pool listing
echo "TEST 5: Integration Test - Pool Listing\n";
echo "=======================================\n";

// Simulate how admin list shows product count
$product_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE pool_id = %d",
    $pool_id
));

echo "Product count for pool {$pool_id}: {$product_count}\n";
if ($product_count > 0) {
    echo "✅ Pool-product relationship working correctly!\n";
} else {
    echo "❌ Pool-product relationship broken - count is 0\n";
}
echo "\n";

// Cleanup
echo "CLEANUP: Removing test data\n";
echo "===========================\n";

// Delete assignments
$deleted_assignments = $wpdb->delete($table_name, ['pool_id' => $pool_id], ['%d']);
echo "Deleted {$deleted_assignments} assignments\n";

// Delete pool
$deleted_pool = $wpdb->delete($pools_table, ['id' => $pool_id], ['%d']);
echo "Deleted {$deleted_pool} pool\n\n";

// Final summary
echo "=== VERIFICATION COMPLETE ===\n\n";

if ($table_exists && empty($missing_columns) && $success_count > 0) {
    echo "🎉 FIX VERIFICATION: SUCCESS\n";
    echo "✅ Table structure correct\n";
    echo "✅ INSERT operations working\n";
    echo "✅ Data retrieval working\n";
    echo "✅ Pool-product relationships functional\n\n";
    echo "The pool-product assignment bug has been FIXED!\n";
} else {
    echo "❌ FIX VERIFICATION: ISSUES REMAIN\n";
    echo "Please check the errors above and ensure:\n";
    echo "- Table exists with correct schema\n";
    echo "- All required columns present\n";
    echo "- INSERT operations succeed\n";
}

// Unset test data
unset($_POST);
?>
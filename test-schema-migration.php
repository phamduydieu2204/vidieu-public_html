<?php
/**
 * Test Schema Migration
 *
 * Test script để verify schema migration hoạt động đúng
 */

// Load WordPress
require_once(__DIR__ . '/wp-config.php');
require_once(__DIR__ . '/wp-load.php');

echo "=== VD SCHEMA MIGRATION TEST ===\n\n";

// Load migration class
require_once(__DIR__ . '/wp-content/plugins/vd-license-manager/includes/migrations/migrate-account-columns-v2.php');

// Test 1: Check migration status before
echo "1. Migration Status Before:\n";
$status_before = VD_LM_Account_Columns_Migration_V2::get_status();
echo "   Status: " . $status_before['status'] . "\n";
echo "   Message: " . $status_before['message'] . "\n\n";

// Test 2: Check current table structure
echo "2. Current Table Structure:\n";
global $wpdb;
$table_name = $wpdb->prefix . 'vd_provider_accounts';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
echo "   Table exists: " . ($table_exists ? "YES" : "NO") . "\n";

if ($table_exists) {
    $columns = $wpdb->get_results("DESCRIBE $table_name", ARRAY_A);
    echo "   Columns found:\n";
    foreach ($columns as $col) {
        echo "     - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }

    // Check for critical columns
    $column_names = array_column($columns, 'Field');
    $critical_checks = [
        'account_login' => in_array('account_login', $column_names),
        'account_password' => in_array('account_password', $column_names),
        'current_usage' => in_array('current_usage', $column_names),
        'capacity' => in_array('capacity', $column_names),
        'login_password' => in_array('login_password', $column_names), // Legacy
    ];

    echo "\n   Critical Column Check:\n";
    foreach ($critical_checks as $col => $exists) {
        $status = $exists ? "✅ EXISTS" : "❌ MISSING";
        echo "     - $col: $status\n";
    }
}

echo "\n3. Sample Account Data (if any):\n";
if ($table_exists) {
    $sample_accounts = $wpdb->get_results("SELECT id, provider, account_login, capacity, current_usage, status FROM $table_name LIMIT 3", ARRAY_A);

    if (empty($sample_accounts)) {
        echo "   No accounts found in table\n";
    } else {
        foreach ($sample_accounts as $account) {
            echo "   Account {$account['id']}: {$account['account_login']} ({$account['provider']}) - {$account['current_usage']}/{$account['capacity']} [{$account['status']}]\n";
        }
    }
}

echo "\n4. Testing Order Handler Column Mapping:\n";
// Load order handler to test mapping
require_once(__DIR__ . '/wp-content/plugins/vd-license-manager/includes/class-vd-lm-order-handler.php');

$order_handler = new VD_LM_Order_Handler();
$columns_mapping = $order_handler::ACCOUNT_COLUMNS;

echo "   Order Handler Mapping:\n";
foreach ($columns_mapping as $logical => $actual) {
    $exists_in_db = $table_exists ? in_array($actual, $column_names) : false;
    $status = $exists_in_db ? "✅" : "❌";
    echo "     $logical → $actual $status\n";
}

// Test 5: Run migration (if safe)
echo "\n5. Running Migration (DRY RUN MODE):\n";

// For safety, let's just analyze what would happen
if (!VD_LM_Account_Columns_Migration_V2::is_migration_complete()) {
    echo "   Migration not yet run. Would execute:\n";
    echo "   - Create backup of current data\n";
    echo "   - Analyze schema inconsistencies\n";
    echo "   - Fix column naming conflicts\n";
    echo "   - Recalculate current_usage values\n";
    echo "   - Add missing indexes\n";

    // Actually run migration if user wants
    if (isset($_GET['run_migration']) && $_GET['run_migration'] === 'yes') {
        echo "\n   EXECUTING MIGRATION...\n";
        $migration_result = VD_LM_Account_Columns_Migration_V2::run();

        echo "   Migration Result:\n";
        echo "     Success: " . ($migration_result['success'] ? "YES" : "NO") . "\n";
        echo "     Message: " . $migration_result['message'] . "\n";
        echo "     Execution Time: " . round($migration_result['execution_time'], 3) . "s\n";

        if (isset($migration_result['backup_file'])) {
            echo "     Backup File: " . $migration_result['backup_file'] . "\n";
        }

        if (isset($migration_result['schema_changes'])) {
            echo "     Schema Changes:\n";
            foreach ($migration_result['schema_changes'] as $change) {
                echo "       - $change\n";
            }
        }
    } else {
        echo "\n   To actually run migration, add ?run_migration=yes to URL\n";
    }
} else {
    echo "   Migration already completed.\n";
    $status_after = VD_LM_Account_Columns_Migration_V2::get_status();
    echo "   Completed at: " . $status_after['completed_at'] . "\n";
    echo "   Execution time: " . $status_after['execution_time'] . "s\n";
}

echo "\n=== TEST COMPLETE ===\n";

// Clean up
unlink(__FILE__);
?>
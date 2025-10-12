<?php
/**
 * VD License Manager - Account Assignment Diagnostic Script
 *
 * This script helps diagnose account assignment issues by checking:
 * 1. Database table existence and structure
 * 2. Data relationships between tables
 * 3. Account availability and capacity
 * 4. Pool assignments and priorities
 *
 * Run this script to verify the complete license assignment flow.
 */

// WordPress bootstrap
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-includes/wp-db.php';

// Connect to database
$wpdb = new wpdb('vidieu', 'Vidieu0204@#&6', 'vidieu_db', 'localhost');

echo "=== VD LICENSE MANAGER - ACCOUNT ASSIGNMENT DIAGNOSTIC ===\n\n";

// 1. Check table existence
echo "1. CHECKING TABLE EXISTENCE:\n";
$required_tables = [
    'bz_vd_provider_accounts',
    'bz_vd_pools',
    'bz_vd_pool_accounts',
    'bz_vd_product_pools',
    'bz_vd_license_keys'
];

foreach ($required_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table;
    echo "   " . ($exists ? "✓" : "✗") . " {$table}\n";

    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        echo "     Records: {$count}\n";
    }
}
echo "\n";

// 2. Check provider accounts
echo "2. PROVIDER ACCOUNTS ANALYSIS:\n";
$accounts = $wpdb->get_results(
    "SELECT id, provider, account_login, status, current_usage, capacity, created_at
     FROM bz_vd_provider_accounts
     ORDER BY created_at DESC",
    ARRAY_A
);

echo "   Total accounts: " . count($accounts) . "\n";
foreach ($accounts as $account) {
    $status_icon = $account['status'] == 'active' ? '✓' : '✗';
    $usage_status = $account['current_usage'] < $account['capacity'] ? 'Available' : 'Full';
    echo "   {$status_icon} ID:{$account['id']} | {$account['provider']} | {$account['account_login']} | " .
         "Status:{$account['status']} | Usage:{$account['current_usage']}/{$account['capacity']} | {$usage_status}\n";
}
echo "\n";

// 3. Check pools
echo "3. POOLS ANALYSIS:\n";
$pools = $wpdb->get_results(
    "SELECT id, name, status, created_at FROM bz_vd_pools ORDER BY created_at DESC",
    ARRAY_A
);

echo "   Total pools: " . count($pools) . "\n";
foreach ($pools as $pool) {
    $status_icon = $pool['status'] == 'active' ? '✓' : '✗';
    echo "   {$status_icon} ID:{$pool['id']} | {$pool['name']} | Status:{$pool['status']}\n";

    // Check accounts in this pool
    $pool_accounts = $wpdb->get_results($wpdb->prepare(
        "SELECT pa.*, a.account_login, a.status as account_status, a.current_usage, a.capacity
         FROM bz_vd_pool_accounts pa
         LEFT JOIN bz_vd_provider_accounts a ON pa.account_id = a.id
         WHERE pa.pool_id = %d",
        $pool['id']
    ), ARRAY_A);

    echo "     Assigned accounts: " . count($pool_accounts) . "\n";
    foreach ($pool_accounts as $pool_account) {
        $account_icon = ($pool_account['status'] == 'active' && $pool_account['account_status'] == 'active') ? '✓' : '✗';
        $available = ($pool_account['current_usage'] < $pool_account['capacity']) ? 'Available' : 'Full';
        echo "       {$account_icon} Account ID:{$pool_account['account_id']} | " .
             "Login:{$pool_account['account_login']} | Weight:{$pool_account['weight']} | " .
             "Usage:{$pool_account['current_usage']}/{$pool_account['capacity']} | {$available}\n";
    }
}
echo "\n";

// 4. Check product pool assignments
echo "4. PRODUCT POOL ASSIGNMENTS:\n";
$product_pools = $wpdb->get_results(
    "SELECT pp.*, p.name as pool_name, p.status as pool_status
     FROM bz_vd_product_pools pp
     LEFT JOIN bz_vd_pools p ON pp.pool_id = p.id
     ORDER BY pp.product_id, pp.priority",
    ARRAY_A
);

echo "   Total product-pool assignments: " . count($product_pools) . "\n";
$current_product = null;
foreach ($product_pools as $assignment) {
    if ($current_product != $assignment['product_id']) {
        echo "   Product ID: {$assignment['product_id']}\n";
        $current_product = $assignment['product_id'];
    }

    $status_icon = ($assignment['status'] == 'active' && $assignment['pool_status'] == 'active') ? '✓' : '✗';
    echo "     {$status_icon} Pool ID:{$assignment['pool_id']} | {$assignment['pool_name']} | " .
         "Priority:{$assignment['priority']} | Status:{$assignment['status']}\n";
}
echo "\n";

// 5. Test account assignment logic for a specific product
echo "5. TESTING ACCOUNT ASSIGNMENT LOGIC:\n";
$test_product_id = $wpdb->get_var("SELECT product_id FROM bz_vd_product_pools LIMIT 1");

if ($test_product_id) {
    echo "   Testing with Product ID: {$test_product_id}\n\n";

    // Step 1: Find pool for product
    echo "   Step 1 - Finding pool for product:\n";
    $pool = $wpdb->get_row($wpdb->prepare(
        "SELECT pp.*, p.name as pool_name
         FROM bz_vd_product_pools pp
         JOIN bz_vd_pools p ON pp.pool_id = p.id
         WHERE pp.product_id = %d
         AND pp.status = 'active'
         AND p.status = 'active'
         ORDER BY pp.priority ASC
         LIMIT 1",
        $test_product_id
    ), ARRAY_A);

    if ($pool) {
        echo "   ✓ Found pool: ID {$pool['pool_id']} - {$pool['pool_name']}\n\n";

        // Step 2: Find available account in pool
        echo "   Step 2 - Finding available account in pool:\n";
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, pa.weight
             FROM bz_vd_provider_accounts a
             INNER JOIN bz_vd_pool_accounts pa ON a.id = pa.account_id
             WHERE pa.pool_id = %d
             AND pa.status = 'active'
             AND a.status = 'active'
             AND a.current_usage < a.capacity
             ORDER BY a.current_usage ASC, pa.weight DESC
             LIMIT 1",
            $pool['pool_id']
        ), ARRAY_A);

        if ($account) {
            echo "   ✓ Found available account:\n";
            echo "     Account ID: {$account['id']}\n";
            echo "     Login: {$account['account_login']}\n";
            echo "     Provider: {$account['provider']}\n";
            echo "     Usage: {$account['current_usage']}/{$account['capacity']}\n";
            echo "     Weight: {$account['weight']}\n";
            echo "   \n";
            echo "   ✅ ASSIGNMENT WOULD SUCCEED\n";
        } else {
            echo "   ✗ No available account found\n";
            echo "   \n";
            echo "   Reasons could be:\n";
            echo "   - All accounts in pool are at capacity\n";
            echo "   - All accounts in pool are inactive\n";
            echo "   - Pool has no accounts assigned\n";
            echo "   \n";
            echo "   ❌ ASSIGNMENT WOULD FAIL\n";
        }
    } else {
        echo "   ✗ No active pool found for product {$test_product_id}\n";
        echo "   \n";
        echo "   ❌ ASSIGNMENT WOULD FAIL - NO POOL\n";
    }
} else {
    echo "   No product pool assignments found to test\n";
}
echo "\n";

// 6. Check recent license assignments
echo "6. RECENT LICENSE ASSIGNMENTS:\n";
$recent_licenses = $wpdb->get_results(
    "SELECT lk.id, lk.license_key, lk.product_id, lk.account_id, lk.pool_id, lk.status, lk.created_at,
            a.account_login, p.name as pool_name
     FROM bz_vd_license_keys lk
     LEFT JOIN bz_vd_provider_accounts a ON lk.account_id = a.id
     LEFT JOIN bz_vd_pools p ON lk.pool_id = p.id
     ORDER BY lk.created_at DESC
     LIMIT 5",
    ARRAY_A
);

if (count($recent_licenses) > 0) {
    echo "   Last 5 license assignments:\n";
    foreach ($recent_licenses as $license) {
        $account_info = $license['account_login'] ? $license['account_login'] : 'No Account';
        $pool_info = $license['pool_name'] ? $license['pool_name'] : 'No Pool';
        echo "   - License: {$license['license_key']} | Product: {$license['product_id']} | " .
             "Account: {$account_info} | Pool: {$pool_info} | Status: {$license['status']}\n";
    }
} else {
    echo "   No license assignments found\n";
}
echo "\n";

// 7. Summary and recommendations
echo "7. SUMMARY AND RECOMMENDATIONS:\n";

// Check if system is ready for license assignment
$has_accounts = count($accounts) > 0;
$has_pools = count($pools) > 0;
$has_assignments = count($product_pools) > 0;
$has_active_accounts = false;

foreach ($accounts as $account) {
    if ($account['status'] == 'active' && $account['current_usage'] < $account['capacity']) {
        $has_active_accounts = true;
        break;
    }
}

if ($has_accounts && $has_pools && $has_assignments && $has_active_accounts) {
    echo "   ✅ System is ready for license assignment\n";
    echo "   \n";
    echo "   To test the complete flow:\n";
    echo "   1. Complete a WooCommerce order for a product with pool assignment\n";
    echo "   2. Check debug.log for detailed assignment logging\n";
    echo "   3. Verify license record in bz_vd_license_keys table\n";
} else {
    echo "   ⚠️  System needs setup before license assignment will work:\n";

    if (!$has_accounts) {
        echo "   - Create provider accounts via admin interface\n";
    }
    if (!$has_pools) {
        echo "   - Create pools via admin interface\n";
    }
    if (!$has_assignments) {
        echo "   - Assign products to pools\n";
    }
    if (!$has_active_accounts) {
        echo "   - Ensure accounts have available capacity (current_usage < capacity)\n";
    }
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
?>
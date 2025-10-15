<?php
/**
 * VD Pool Account Logic Analysis
 * Temporary file to analyze the current pool assignment system
 */

// Load WordPress
require_once(__DIR__ . '/wp-config.php');
require_once(__DIR__ . '/wp-load.php');

echo "=== VD POOL ACCOUNT LOGIC ANALYSIS ===\n\n";

global $wpdb;

// Check database connection
echo "Database: " . DB_NAME . "\n";
echo "Prefix: " . $wpdb->prefix . "\n\n";

// 1. Check if tables exist
echo "=== TABLE EXISTENCE CHECK ===\n";
$tables_to_check = [
    'vd_pools',
    'vd_provider_accounts',
    'vd_pool_accounts',
    'vd_product_pools',
    'vd_license_keys'
];

foreach ($tables_to_check as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
    echo "- $full_table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

echo "\n=== POOL STRUCTURE ANALYSIS ===\n";

// 2. Check pools table structure and data
$pools_table = $wpdb->prefix . 'vd_pools';
if ($wpdb->get_var("SHOW TABLES LIKE '$pools_table'") === $pools_table) {
    echo "Pools Table Structure:\n";
    $columns = $wpdb->get_results("DESCRIBE $pools_table");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type}) {$col->Null} {$col->Key} {$col->Default}\n";
    }

    $pool_count = $wpdb->get_var("SELECT COUNT(*) FROM $pools_table");
    echo "\nTotal Pools: $pool_count\n";

    if ($pool_count > 0) {
        $pools = $wpdb->get_results("SELECT * FROM $pools_table LIMIT 5", ARRAY_A);
        echo "Sample Pools:\n";
        foreach ($pools as $pool) {
            echo "  ID: {$pool['id']}, Name: {$pool['name']}, Status: {$pool['status']}\n";
        }
    }
}

echo "\n=== PROVIDER ACCOUNTS ANALYSIS ===\n";

// 3. Check provider accounts structure and capacity logic
$accounts_table = $wpdb->prefix . 'vd_provider_accounts';
if ($wpdb->get_var("SHOW TABLES LIKE '$accounts_table'") === $accounts_table) {
    echo "Provider Accounts Table Structure:\n";
    $columns = $wpdb->get_results("DESCRIBE $accounts_table");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type}) {$col->Null} {$col->Key} {$col->Default}\n";
    }

    // Check capacity vs current_usage logic
    $accounts = $wpdb->get_results("
        SELECT id, provider, account_login, capacity, current_usage, status, expires_at
        FROM $accounts_table
        ORDER BY id
        LIMIT 10
    ", ARRAY_A);

    echo "\nAccount Capacity Analysis:\n";
    foreach ($accounts as $account) {
        $usage_percent = $account['capacity'] > 0 ? round(($account['current_usage'] / $account['capacity']) * 100, 1) : 0;
        $status_flag = '';

        if ($account['status'] !== 'active') {
            $status_flag .= " [INACTIVE]";
        }
        if (!empty($account['expires_at']) && strtotime($account['expires_at']) < time()) {
            $status_flag .= " [EXPIRED]";
        }
        if ($account['current_usage'] >= $account['capacity']) {
            $status_flag .= " [AT_CAPACITY]";
        }

        echo "  Account {$account['id']}: {$account['account_login']} ({$account['provider']}) - ";
        echo "Usage: {$account['current_usage']}/{$account['capacity']} ({$usage_percent}%)$status_flag\n";
    }
}

echo "\n=== POOL-ACCOUNT RELATIONSHIPS ===\n";

// 4. Check pool-account mapping logic
$pool_accounts_table = $wpdb->prefix . 'vd_pool_accounts';
if ($wpdb->get_var("SHOW TABLES LIKE '$pool_accounts_table'") === $pool_accounts_table) {
    echo "Pool-Accounts Table Structure:\n";
    $columns = $wpdb->get_results("DESCRIBE $pool_accounts_table");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type}) {$col->Null} {$col->Key} {$col->Default}\n";
    }

    // Show relationships with pool capacity calculation
    $relationships = $wpdb->get_results("
        SELECT
            p.id as pool_id,
            p.name as pool_name,
            pa.pool_id,
            pa.account_id,
            pa.weight,
            pa.is_primary,
            pa.status as assignment_status,
            a.provider,
            a.account_login,
            a.capacity as account_capacity,
            a.current_usage as account_usage,
            a.status as account_status
        FROM $pools_table p
        LEFT JOIN $pool_accounts_table pa ON p.id = pa.pool_id
        LEFT JOIN $accounts_table a ON pa.account_id = a.id
        ORDER BY p.id, pa.weight DESC, pa.is_primary DESC
        LIMIT 20
    ", ARRAY_A);

    echo "\nPool-Account Relationships:\n";
    $current_pool = null;
    foreach ($relationships as $rel) {
        if ($current_pool !== $rel['pool_id']) {
            $current_pool = $rel['pool_id'];
            echo "\n  Pool {$rel['pool_id']}: {$rel['pool_name']}\n";
        }

        if ($rel['account_id']) {
            $usage = $rel['account_usage'] ?: 0;
            $capacity = $rel['account_capacity'] ?: 0;
            $usage_info = $capacity > 0 ? " ($usage/$capacity)" : " ($usage/?)";
            $weight_info = $rel['weight'] ? " weight:{$rel['weight']}" : "";
            $primary_info = $rel['is_primary'] ? " [PRIMARY]" : "";
            $status_info = $rel['assignment_status'] === 'active' ? "" : " [{$rel['assignment_status']}]";

            echo "    -> Account {$rel['account_id']}: {$rel['account_login']} ({$rel['provider']})$usage_info$weight_info$primary_info$status_info\n";
        } else {
            echo "    -> No accounts assigned\n";
        }
    }
}

echo "\n=== PRODUCT-POOL MAPPINGS ===\n";

// 5. Check product-pool assignments with priority logic
$product_pools_table = $wpdb->prefix . 'vd_product_pools';
if ($wpdb->get_var("SHOW TABLES LIKE '$product_pools_table'") === $product_pools_table) {
    echo "Product-Pools Table Structure:\n";
    $columns = $wpdb->get_results("DESCRIBE $product_pools_table");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type}) {$col->Null} {$col->Key} {$col->Default}\n";
    }

    $product_pools = $wpdb->get_results("
        SELECT
            pp.*,
            p.name as pool_name,
            p.status as pool_status
        FROM $product_pools_table pp
        LEFT JOIN $pools_table p ON pp.pool_id = p.id
        ORDER BY pp.product_id, pp.priority ASC
        LIMIT 15
    ", ARRAY_A);

    echo "\nProduct-Pool Assignments (with priority):\n";
    $current_product = null;
    foreach ($product_pools as $pp) {
        if ($current_product !== $pp['product_id']) {
            $current_product = $pp['product_id'];
            echo "\n  Product {$pp['product_id']}:\n";
        }

        $priority_info = isset($pp['priority']) ? " priority:{$pp['priority']}" : "";
        $status_info = $pp['status'] !== 'active' ? " [{$pp['status']}]" : "";
        echo "    -> Pool {$pp['pool_id']}: {$pp['pool_name']}$priority_info$status_info\n";
    }
}

echo "\n=== LICENSE ASSIGNMENT ANALYSIS ===\n";

// 6. Check how licenses are assigned to pools and accounts
$licenses_table = $wpdb->prefix . 'vd_license_keys';
if ($wpdb->get_var("SHOW TABLES LIKE '$licenses_table'") === $licenses_table) {
    echo "License Keys Table Structure:\n";
    $columns = $wpdb->get_results("DESCRIBE $licenses_table");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type}) {$col->Null} {$col->Key} {$col->Default}\n";
    }

    // Check assignment patterns
    $licenses = $wpdb->get_results("
        SELECT
            l.id,
            l.license_key,
            l.product_id,
            l.pool_id,
            l.account_id,
            l.status,
            l.assigned_at,
            p.name as pool_name,
            a.account_login
        FROM $licenses_table l
        LEFT JOIN $pools_table p ON l.pool_id = p.id
        LEFT JOIN $accounts_table a ON l.account_id = a.id
        ORDER BY l.assigned_at DESC
        LIMIT 10
    ", ARRAY_A);

    echo "\nRecent License Assignments:\n";
    foreach ($licenses as $license) {
        $key_display = substr($license['license_key'], 0, 20) . '...';
        $pool_info = $license['pool_name'] ? " -> Pool: {$license['pool_name']}" : " -> No Pool";
        $account_info = $license['account_login'] ? " -> Account: {$license['account_login']}" : " -> No Account";
        echo "  License {$license['id']}: $key_display (Product {$license['product_id']})$pool_info$account_info\n";
    }
}

echo "\n=== ASSIGNMENT LOGIC ANALYSIS ===\n";

// 7. Test assignment logic for a sample product
$sample_product_id = $wpdb->get_var("SELECT product_id FROM $product_pools_table LIMIT 1");

if ($sample_product_id) {
    echo "Testing assignment logic for Product $sample_product_id:\n\n";

    // Get pools for this product (ordered by priority)
    $product_pools = $wpdb->get_results($wpdb->prepare("
        SELECT
            pp.*,
            p.name as pool_name,
            p.status as pool_status
        FROM $product_pools_table pp
        JOIN $pools_table p ON pp.pool_id = p.id
        WHERE pp.product_id = %d
        AND pp.status = 'active'
        AND p.status = 'active'
        ORDER BY pp.priority ASC
    ", $sample_product_id), ARRAY_A);

    echo "Available pools (priority order):\n";
    foreach ($product_pools as $pool) {
        // Calculate pool capacity
        $pool_capacity = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(a.capacity)
            FROM $pool_accounts_table pa
            JOIN $accounts_table a ON pa.account_id = a.id
            WHERE pa.pool_id = %d
            AND pa.status = 'active'
            AND a.status = 'active'
        ", $pool['pool_id']));

        $pool_assigned = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM $licenses_table
            WHERE pool_id = %d AND status = 'active'
        ", $pool['pool_id']));

        $capacity_info = "Capacity: $pool_assigned/$pool_capacity";
        $available = ($pool_assigned < $pool_capacity) ? "AVAILABLE" : "FULL";

        echo "  Priority {$pool['priority']}: Pool {$pool['pool_id']} ({$pool['pool_name']}) - $capacity_info [$available]\n";

        // Show accounts in this pool
        $pool_accounts = $wpdb->get_results($wpdb->prepare("
            SELECT
                a.id,
                a.account_login,
                a.capacity,
                a.current_usage,
                pa.weight,
                pa.is_primary
            FROM $pool_accounts_table pa
            JOIN $accounts_table a ON pa.account_id = a.id
            WHERE pa.pool_id = %d
            AND pa.status = 'active'
            AND a.status = 'active'
            ORDER BY a.current_usage ASC, pa.weight DESC
        ", $pool['pool_id']), ARRAY_A);

        foreach ($pool_accounts as $account) {
            $usage_status = ($account['current_usage'] < $account['capacity']) ? "AVAILABLE" : "FULL";
            $weight_info = $account['weight'] ? " weight:{$account['weight']}" : "";
            $primary_info = $account['is_primary'] ? " [PRIMARY]" : "";
            echo "    -> Account {$account['id']}: {$account['account_login']} ({$account['current_usage']}/{$account['capacity']})$weight_info$primary_info [$usage_status]\n";
        }
    }
}

echo "\n=== EDGE CASES & ISSUES DETECTED ===\n";

// 8. Check for potential issues
$issues = [];

// Check for accounts at capacity
$at_capacity = $wpdb->get_var("
    SELECT COUNT(*)
    FROM $accounts_table
    WHERE current_usage >= capacity AND status = 'active'
");
if ($at_capacity > 0) {
    $issues[] = "$at_capacity active accounts are at or over capacity";
}

// Check for expired accounts still active
$expired_active = $wpdb->get_var("
    SELECT COUNT(*)
    FROM $accounts_table
    WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at < NOW()
");
if ($expired_active > 0) {
    $issues[] = "$expired_active accounts are active but expired";
}

// Check for pools with no accounts
$empty_pools = $wpdb->get_var("
    SELECT COUNT(p.id)
    FROM $pools_table p
    LEFT JOIN $pool_accounts_table pa ON p.id = pa.pool_id AND pa.status = 'active'
    WHERE p.status = 'active' AND pa.pool_id IS NULL
");
if ($empty_pools > 0) {
    $issues[] = "$empty_pools active pools have no accounts assigned";
}

// Check for products with no pools
$products_no_pools = $wpdb->get_var("
    SELECT COUNT(DISTINCT l.product_id)
    FROM $licenses_table l
    LEFT JOIN $product_pools_table pp ON l.product_id = pp.product_id AND pp.status = 'active'
    WHERE pp.product_id IS NULL
");
if ($products_no_pools > 0) {
    $issues[] = "$products_no_pools products have licenses but no pool assignments";
}

if (empty($issues)) {
    echo "No critical issues detected.\n";
} else {
    foreach ($issues as $issue) {
        echo "⚠️  $issue\n";
    }
}

echo "\n=== ANALYSIS COMPLETE ===\n";

// Clean up
unlink(__FILE__);
?>
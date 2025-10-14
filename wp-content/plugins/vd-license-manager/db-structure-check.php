<?php
/**
 * Database Structure Checker
 * Uses WordPress $wpdb to check database structure
 */

// Include WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;

// Set headers for plain text output
header('Content-Type: text/plain; charset=utf-8');

echo "=== VD LICENSE MANAGER DATABASE STRUCTURE CHECK ===\n\n";

// Tables to check
$tables = [
    'bz_vd_pools',
    'bz_vd_provider_accounts',
    'bz_vd_pool_accounts',
    'bz_vd_product_pools',
    'bz_vd_license_keys',
    'bz_vd_product_share_configs'
];

foreach ($tables as $table) {
    echo "TABLE: {$table}\n";
    echo str_repeat("=", 50) . "\n";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

    if (!$table_exists) {
        echo "❌ Table does not exist\n\n";
        continue;
    }

    echo "✅ Table exists\n\n";

    // Get table structure
    $structure = $wpdb->get_results("DESCRIBE {$table}", ARRAY_A);

    if (!empty($structure)) {
        printf("%-20s %-30s %-8s %-8s %-15s %-10s\n",
               "Field", "Type", "Null", "Key", "Default", "Extra");
        echo str_repeat("-", 100) . "\n";

        foreach ($structure as $field) {
            printf("%-20s %-30s %-8s %-8s %-15s %-10s\n",
                   $field['Field'],
                   $field['Type'],
                   $field['Null'],
                   $field['Key'],
                   $field['Default'] ?? 'NULL',
                   $field['Extra']
            );
        }
        echo "\n";
    }

    // Get row count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    echo "Row Count: {$count}\n";

    // Get indexes
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}", ARRAY_A);
    if (!empty($indexes)) {
        echo "\nIndexes:\n";
        printf("%-20s %-20s %-8s %-10s\n", "Key Name", "Column", "Unique", "Type");
        echo str_repeat("-", 60) . "\n";

        foreach ($indexes as $index) {
            printf("%-20s %-20s %-8s %-10s\n",
                   $index['Key_name'],
                   $index['Column_name'],
                   $index['Non_unique'] ? 'No' : 'Yes',
                   $index['Index_type']
            );
        }
    }

    echo "\n" . str_repeat("=", 80) . "\n\n";
}

// Special check for relationship data
echo "RELATIONSHIP DATA CHECK\n";
echo str_repeat("=", 50) . "\n";

// Pool-Account relationships
$pool_account_data = $wpdb->get_results("
    SELECT pa.*, p.name as pool_name, a.provider, a.account_login
    FROM bz_vd_pool_accounts pa
    LEFT JOIN bz_vd_pools p ON pa.pool_id = p.id
    LEFT JOIN bz_vd_provider_accounts a ON pa.account_id = a.id
    LIMIT 5
", ARRAY_A);

echo "Pool-Account Relationships (first 5):\n";
if (!empty($pool_account_data)) {
    foreach ($pool_account_data as $row) {
        echo "- Pool: {$row['pool_name']} | Account: {$row['provider']} - {$row['account_login']} | Weight: {$row['weight']} | Status: {$row['status']}\n";
    }
} else {
    echo "No pool-account relationships found\n";
}

echo "\n";

// Product-Pool relationships
$product_pool_data = $wpdb->get_results("
    SELECT pp.*, p.name as pool_name, pr.post_title as product_name
    FROM bz_vd_product_pools pp
    LEFT JOIN bz_vd_pools p ON pp.pool_id = p.id
    LEFT JOIN wp_posts pr ON pp.product_id = pr.ID
    LIMIT 5
", ARRAY_A);

echo "Product-Pool Relationships (first 5):\n";
if (!empty($product_pool_data)) {
    foreach ($product_pool_data as $row) {
        echo "- Product: {$row['product_name']} (ID: {$row['product_id']}) | Pool: {$row['pool_name']}\n";
    }
} else {
    echo "No product-pool relationships found\n";
}

echo "\n=== END OF REPORT ===\n";
?>
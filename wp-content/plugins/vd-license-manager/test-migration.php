<?php
/**
 * Test Migration Script
 * Temporary script to test database migration
 */

// Include WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Set headers for plain text output
header('Content-Type: text/plain; charset=utf-8');

echo "=== VD LICENSE MANAGER MIGRATION TEST ===\n\n";

// Load migration manager
require_once VD_PLUGIN_DIR . 'includes/database/class-vd-migration-manager.php';

echo "Current migration version: " . VD_Migration_Manager::get_current_version() . "\n";
echo "Target version: " . VD_Migration_Manager::CURRENT_VERSION . "\n";
echo "Needs migration: " . (VD_Migration_Manager::needs_migration() ? 'YES' : 'NO') . "\n\n";

if (isset($_GET['run']) && $_GET['run'] === 'true') {
    echo "Running migrations...\n\n";

    $success = VD_Migration_Manager::run_migrations();

    if ($success) {
        echo "✅ Migrations completed successfully!\n";
    } else {
        echo "❌ Migrations failed!\n";
    }

    echo "\nNew version: " . VD_Migration_Manager::get_current_version() . "\n";
} else {
    echo "Add ?run=true to URL to execute migrations\n";
}

// Check product_pools table structure
echo "\n=== PRODUCT POOLS TABLE STRUCTURE ===\n";
global $wpdb;
$table_name = $wpdb->prefix . 'vd_product_pools';

$structure = $wpdb->get_results("DESCRIBE {$table_name}", ARRAY_A);

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
} else {
    echo "❌ Table does not exist or query failed\n";
}

echo "\n=== END OF TEST ===\n";
?>
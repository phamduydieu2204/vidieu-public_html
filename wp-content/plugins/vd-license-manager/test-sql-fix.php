<?php
/**
 * Test SQL Fix - VD License Manager
 *
 * Simple test to verify the SQL syntax fix works
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

// Only run for admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "=== VD LICENSE MANAGER - SQL FIX TEST ===\n\n";

// Load database class
require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-database.php';

echo "1. Testing table creation with improved SQL syntax...\n";

$result = VD_LM_Database::test_access_log_table_creation();

echo "   Result:\n";
foreach ($result as $key => $value) {
    if (is_array($value)) {
        echo "   - {$key}: " . json_encode($value) . "\n";
    } else {
        echo "   - {$key}: " . ($value ?: 'NULL') . "\n";
    }
}

if (isset($result['created']) && $result['created']) {
    echo "\n✅ SUCCESS: Table created successfully!\n";
    echo "   Columns: " . $result['columns_count'] . "\n";

    if ($result['columns_count'] >= 15) {
        echo "   All expected columns present ✅\n";
    }
} elseif (isset($result['exists_before']) && $result['exists_before']) {
    echo "\n✅ SUCCESS: Table already exists!\n";
    echo "   Columns: " . $result['columns_count'] . "\n";
} else {
    echo "\n❌ FAILURE: Table could not be created\n";
    if (!empty($result['sql_error'])) {
        echo "   SQL Error: " . $result['sql_error'] . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
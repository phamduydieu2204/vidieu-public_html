<?php
/**
 * Database Structure Checker
 * Temporary script to check table structures
 */

// Include WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;

echo '<h1>🔍 Database Structure Check</h1>';

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
    echo "<h2>📋 Table: {$table}</h2>";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

    if (!$table_exists) {
        echo "<p style='color: red;'>❌ Table does not exist</p>";
        continue;
    }

    echo "<p style='color: green;'>✅ Table exists</p>";

    // Get table structure
    $structure = $wpdb->get_results("DESCRIBE {$table}", ARRAY_A);

    if (!empty($structure)) {
        echo '<table border="1" cellpadding="5" cellspacing="0" style="margin: 10px 0;">';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';

        foreach ($structure as $field) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($field['Field']) . '</strong></td>';
            echo '<td>' . esc_html($field['Type']) . '</td>';
            echo '<td>' . esc_html($field['Null']) . '</td>';
            echo '<td>' . esc_html($field['Key']) . '</td>';
            echo '<td>' . esc_html($field['Default']) . '</td>';
            echo '<td>' . esc_html($field['Extra']) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    // Get row count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    echo "<p><strong>Row Count:</strong> {$count}</p>";

    echo '<hr>';
}

// Check indexes for bz_vd_pool_accounts specifically
echo '<h2>🔍 Indexes for bz_vd_pool_accounts</h2>';
$indexes = $wpdb->get_results("SHOW INDEX FROM bz_vd_pool_accounts", ARRAY_A);

if (!empty($indexes)) {
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Key Name</th><th>Column</th><th>Unique</th><th>Type</th></tr>';

    foreach ($indexes as $index) {
        echo '<tr>';
        echo '<td>' . esc_html($index['Key_name']) . '</td>';
        echo '<td>' . esc_html($index['Column_name']) . '</td>';
        echo '<td>' . ($index['Non_unique'] ? 'No' : 'Yes') . '</td>';
        echo '<td>' . esc_html($index['Index_type']) . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

echo '<p><a href="/wp-admin/admin.php?page=vd-license-manager">← Back to VD License Manager</a></p>';
?>
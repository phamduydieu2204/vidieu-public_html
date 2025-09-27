<?php
/**
 * Test migration script
 *
 * Temporary file to test table prefix migration
 */

// This file should be accessed through WordPress admin
if (!defined('ABSPATH')) {
    require_once '../../../wp-config.php';
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Include the plugin files
require_once 'includes/functions.php';
require_once 'includes/class-vd-database-manager.php';
require_once 'includes/class-vd-table-migrator.php';

echo "<h1>VD License Manager - Table Migration Test</h1>";

// Get migrator instance
$migrator = VD_Table_Migrator::get_instance();

// Check if we should run migration
if (isset($_GET['run_migration']) && $_GET['run_migration'] === '1') {
    echo "<h2>Running Migration...</h2>";

    $results = $migrator->perform_migration();

    echo "<h3>Migration Results:</h3>";
    echo "<pre>" . print_r($results, true) . "</pre>";

    echo "<p><a href='?'>Check Status Again</a></p>";
} else {
    // Show current status
    echo "<h2>Current Status</h2>";

    $analysis = $migrator->analyze_tables();

    echo "<h3>Table Analysis:</h3>";
    echo "<pre>" . print_r($analysis, true) . "</pre>";

    if ($analysis['status'] === 'needs_migration') {
        echo "<p style='color: red;'><strong>Migration needed!</strong></p>";
        echo "<p><a href='?run_migration=1' onclick=\"return confirm('Are you sure you want to run the migration? This will drop incorrect tables and recreate correct ones.')\">Run Migration</a></p>";
    } else {
        echo "<p style='color: green;'><strong>No migration needed.</strong></p>";
    }
}

echo "<hr>";
echo "<p><em>This is a temporary test file and should be removed after migration is complete.</em></p>";
?>
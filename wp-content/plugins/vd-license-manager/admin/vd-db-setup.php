<?php
/**
 * Database Setup and Test Data Insertion
 * Run this via WordPress admin or direct execution
 */

// Only run if WordPress is loaded
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = array(
        __DIR__ . '/../../../wp-load.php',
        __DIR__ . '/../../../../wp-load.php',
        dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php'
    );

    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }

    if (!$wp_loaded) {
        die('Could not load WordPress. Please run this from WordPress admin or ensure WordPress is available.');
    }
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin access required.');
}

echo '<h2>VD License Manager - Database Setup</h2>';

global $wpdb;

// Check table
$table_name = 'bz_vd_provider_accounts';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if (!$table_exists) {
    echo '<p style="color: red;">❌ Table does not exist: ' . $table_name . '</p>';
    echo '<p>Please activate the plugin to create database tables.</p>';
    exit;
}

echo '<p style="color: green;">✅ Table exists: ' . $table_name . '</p>';

// Check current data
$count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo '<p>Current records: ' . intval($count) . '</p>';

// Insert test data if needed
if ($count < 3) {
    echo '<h3>Inserting test data...</h3>';

    $test_accounts = array(
        array(
            'provider' => 'netflix',
            'account_login' => 'test1@example.com',
            'display_name' => 'Test Netflix Account',
            'capacity' => 5,
            'status' => 'active'
        ),
        array(
            'provider' => 'spotify',
            'account_login' => 'test2@example.com',
            'display_name' => 'Test Spotify Account',
            'capacity' => 3,
            'status' => 'active'
        ),
        array(
            'provider' => 'youtube',
            'account_login' => 'test3@example.com',
            'display_name' => 'Test YouTube Account',
            'capacity' => 10,
            'status' => 'suspended'
        )
    );

    foreach ($test_accounts as $account) {
        $result = $wpdb->insert(
            $table_name,
            array(
                'provider' => $account['provider'],
                'account_login' => $account['account_login'],
                'display_name' => $account['display_name'],
                'capacity' => $account['capacity'],
                'status' => $account['status'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        if ($result !== false) {
            echo '<p style="color: green;">✅ Inserted: ' . esc_html($account['display_name']) . '</p>';
        } else {
            echo '<p style="color: red;">❌ Failed: ' . esc_html($account['display_name']) . '</p>';
            echo '<p>Error: ' . esc_html($wpdb->last_error) . '</p>';
        }
    }

    $new_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo '<p><strong>Total records now: ' . intval($new_count) . '</strong></p>';
}

echo '<h3>✅ Setup Complete!</h3>';
echo '<p><strong>Next step:</strong> Go to <a href="' . admin_url('admin.php?page=vd-provider-accounts') . '">Provider Accounts page</a></p>';
?>
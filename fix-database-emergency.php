<?php
/**
 * Emergency Database Fix Script
 *
 * This script manually fixes the database column sizes for encrypted fields
 * Run this once to resolve the "phone_recovery value too long" error
 */

// WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('wp-config.php');

// Check if we're in WordPress admin or have appropriate permissions
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    // For emergency use, allow direct execution with a safety check
    if (!isset($_GET['emergency_fix']) || $_GET['emergency_fix'] !== 'vd_database_fix_2024') {
        die('Unauthorized access. Add ?emergency_fix=vd_database_fix_2024 to run this emergency script.');
    }
}

echo "<h1>VD License Manager - Emergency Database Fix</h1>\n";
echo "<p>Starting database column size fix...</p>\n";

// Include the database class
if (!class_exists('VD_LM_Database')) {
    require_once('wp-content/plugins/vd-license-manager/includes/class-vd-lm-database.php');
}

global $wpdb;
$table_name = $wpdb->prefix . 'bz_vd_provider_accounts';

echo "<h2>Current Table Structure:</h2>\n";
$columns = $wpdb->get_results("DESCRIBE {$table_name}");
if ($columns) {
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $column) {
        $highlight = '';
        if (in_array($column->Field, ['account_password', 'cookies', 'phone_recovery', 'email_recovery', 'security_answer', 'backup_codes', 'two_factor_secret', 'api_key', 'secret_key', 'api_token'])) {
            $highlight = 'style="background-color: yellow;"';
        }
        echo "<tr {$highlight}><td>{$column->Field}</td><td>{$column->Type}</td><td>{$column->Null}</td><td>{$column->Key}</td><td>{$column->Default}</td><td>{$column->Extra}</td></tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p style='color: red;'>ERROR: Could not read table structure!</p>\n";
    die();
}

echo "<h2>Applying Database Fixes:</h2>\n";

// Reset the option to force re-run
delete_option('vd_encrypted_columns_fixed');

// Define the alterations manually for better control
$alterations = array(
    "ALTER TABLE {$table_name} MODIFY COLUMN account_password LONGTEXT NULL COMMENT 'Account password (encrypted)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN cookies LONGTEXT NULL COMMENT 'Session cookies (encrypted, can be very long)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN phone_recovery TEXT NULL COMMENT 'Recovery phone number (encrypted)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN email_recovery TEXT NULL COMMENT 'Recovery email address (encrypted)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN security_answer LONGTEXT NULL COMMENT 'Security question answer (encrypted)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN backup_codes LONGTEXT NULL COMMENT 'Account backup codes (encrypted)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN two_factor_secret TEXT NULL COMMENT '2FA secret key (encrypted)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN api_key TEXT NULL COMMENT 'Provider API key (encrypted)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN secret_key TEXT NULL COMMENT 'Provider secret key (encrypted)'",
    "ALTER TABLE {$table_name} MODIFY COLUMN api_token LONGTEXT NULL COMMENT 'API authentication token (encrypted)'"
);

$success_count = 0;
$error_count = 0;

echo "<ul>\n";
foreach ($alterations as $sql) {
    echo "<li>Executing: <code>" . esc_html($sql) . "</code><br>\n";
    $result = $wpdb->query($sql);
    if ($result === false) {
        echo "<span style='color: red;'>❌ FAILED: " . esc_html($wpdb->last_error) . "</span></li>\n";
        $error_count++;
    } else {
        echo "<span style='color: green;'>✅ SUCCESS</span></li>\n";
        $success_count++;
    }
}
echo "</ul>\n";

echo "<h2>Updated Table Structure:</h2>\n";
$columns_after = $wpdb->get_results("DESCRIBE {$table_name}");
if ($columns_after) {
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns_after as $column) {
        $highlight = '';
        if (in_array($column->Field, ['account_password', 'cookies', 'phone_recovery', 'email_recovery', 'security_answer', 'backup_codes', 'two_factor_secret', 'api_key', 'secret_key', 'api_token'])) {
            if (strpos($column->Type, 'varchar') !== false) {
                $highlight = 'style="background-color: red; color: white;"';  // Still varchar = problem
            } else {
                $highlight = 'style="background-color: lightgreen;"';  // TEXT/LONGTEXT = good
            }
        }
        echo "<tr {$highlight}><td>{$column->Field}</td><td>{$column->Type}</td><td>{$column->Null}</td><td>{$column->Key}</td><td>{$column->Default}</td><td>{$column->Extra}</td></tr>\n";
    }
    echo "</table>\n";
}

if ($error_count === 0) {
    // Mark as fixed to prevent re-running
    update_option('vd_encrypted_columns_fixed', true);
    echo "<h2 style='color: green;'>✅ ALL FIXES APPLIED SUCCESSFULLY!</h2>\n";
    echo "<p>The database has been updated. All encrypted fields now use TEXT or LONGTEXT columns.</p>\n";
    echo "<p>You can now create accounts with long encrypted values without errors.</p>\n";
} else {
    echo "<h2 style='color: red;'>❌ SOME FIXES FAILED</h2>\n";
    echo "<p>Successful: {$success_count}, Failed: {$error_count}</p>\n";
    echo "<p>Please check the MySQL user permissions or try running the ALTER statements manually in phpMyAdmin.</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>Try creating a new account to test if the error is resolved</li>\n";
echo "<li>If successful, you can delete this file: <code>fix-database-emergency.php</code></li>\n";
echo "<li>The account form should now work without 'value too long' errors</li>\n";
echo "</ul>\n";

echo "<p><em>Emergency fix completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
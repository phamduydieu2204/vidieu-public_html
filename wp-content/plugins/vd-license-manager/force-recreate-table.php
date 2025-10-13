<?php
/**
 * Force Recreate Access Log Table - VD License Manager
 *
 * Safely drops and recreates the bz_vd_license_access_log table
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

// Only run for admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "=== FORCE RECREATE ACCESS LOG TABLE ===\n\n";

global $wpdb;
$table_name = $wpdb->prefix . 'vd_license_access_log';

// 1. Check current status
$table_exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $table_name
)) === $table_name;

echo "1. Current Status:\n";
echo "   Table exists: " . ($table_exists ? 'YES' : 'NO') . "\n\n";

if ($table_exists) {
    // Show current row count
    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    echo "   Current rows: {$row_count}\n\n";

    // Drop existing table
    echo "2. Dropping existing table...\n";
    $drop_result = $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

    if ($drop_result !== false) {
        echo "   ✅ Table dropped successfully\n\n";
    } else {
        echo "   ❌ Failed to drop table: " . $wpdb->last_error . "\n\n";
        exit;
    }
} else {
    echo "2. No existing table to drop\n\n";
}

// 3. Create new table with improved SQL
echo "3. Creating new table...\n";

$charset_collate = $wpdb->get_charset_collate();

// Improved SQL - removed potential MariaDB incompatibility issues
$sql = "CREATE TABLE {$table_name} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    license_id BIGINT UNSIGNED NULL,
    license_key VARCHAR(255) NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    method VARCHAR(10) NOT NULL DEFAULT 'GET',
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    device_id VARCHAR(64) NULL,
    request_data LONGTEXT NULL,
    response_status INT NOT NULL,
    response_data LONGTEXT NULL,
    authentication_result ENUM('success','invalid_license','expired_license','suspended_license','device_limit','rate_limit','vps_blocked','other') NOT NULL,
    security_flags LONGTEXT NULL,
    execution_time_ms INT NULL,
    memory_usage_mb DECIMAL(8,2) NULL,
    rate_limit_remaining INT NULL,
    rate_limit_reset_at DATETIME NULL,
    country_code VARCHAR(2) NULL,
    city VARCHAR(100) NULL,
    accessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_license_id (license_id),
    INDEX idx_license_key (license_key),
    INDEX idx_endpoint (endpoint),
    INDEX idx_ip_address (ip_address),
    INDEX idx_device_id (device_id),
    INDEX idx_response_status (response_status),
    INDEX idx_authentication_result (authentication_result),
    INDEX idx_accessed_at (accessed_at),
    INDEX idx_country_code (country_code)
) {$charset_collate};";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

echo "   Using charset: {$charset_collate}\n";
echo "   SQL length: " . strlen($sql) . " chars\n";

$result = dbDelta($sql);

// Check for errors
if ($wpdb->last_error) {
    echo "   ❌ MySQL Error: " . $wpdb->last_error . "\n";
} else {
    echo "   ✅ No SQL errors\n";
}

// Verify table creation
$table_created = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $table_name
)) === $table_name;

echo "   Table created: " . ($table_created ? '✅ YES' : '❌ NO') . "\n";

if ($table_created) {
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    echo "   Columns: " . count($columns) . "\n";
    echo "   Indexes: ";

    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
    $index_names = array_unique(array_column($indexes, 'Key_name'));
    echo count($index_names) . " (" . implode(', ', $index_names) . ")\n";

    // Test insert
    echo "\n4. Testing insert...\n";
    $test_data = array(
        'license_key' => 'TEST-' . time(),
        'endpoint' => '/test',
        'ip_address' => '127.0.0.1',
        'response_status' => 200,
        'authentication_result' => 'success'
    );

    $insert_result = $wpdb->insert($table_name, $test_data);

    if ($insert_result !== false) {
        echo "   ✅ Test insert successful\n";

        // Clean up test data
        $wpdb->delete($table_name, array('license_key' => $test_data['license_key']));
        echo "   ✅ Test data cleaned up\n";
    } else {
        echo "   ❌ Test insert failed: " . $wpdb->last_error . "\n";
    }
}

echo "\n✅ PROCESS COMPLETE!\n";
echo "\nTable: {$table_name}\n";
echo "Status: " . ($table_created ? 'CREATED SUCCESSFULLY' : 'CREATION FAILED') . "\n";

if ($table_created) {
    echo "\n🎉 You can now deactivate and reactivate the plugin safely!\n";
}
?>
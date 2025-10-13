<?php
/**
 * Test Table Creation - VD License Manager
 *
 * Simple test to check if bz_vd_license_access_log table can be created
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

// Only run for admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "=== VD LICENSE MANAGER - TABLE CREATION TEST ===\n\n";

global $wpdb;
$table_name = $wpdb->prefix . 'vd_license_access_log';

// Check if table exists
$table_exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $table_name
)) === $table_name;

echo "1. Current Status:\n";
echo "   Table name: {$table_name}\n";
echo "   Table exists: " . ($table_exists ? 'YES' : 'NO') . "\n\n";

if ($table_exists) {
    // Show table structure
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    echo "2. Table Structure (" . count($columns) . " columns):\n";
    foreach ($columns as $column) {
        echo "   - {$column->Field} ({$column->Type})\n";
    }
} else {
    echo "2. Attempting to create table...\n";

    // Load database class
    require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-database.php';

    // Get charset
    $charset_collate = $wpdb->get_charset_collate();

    // Create SQL (extracted from class-vd-lm-database.php)
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        license_id bigint(20) unsigned NULL COMMENT 'FK to vd_license_keys.id (null for invalid license)',
        license_key varchar(255) NOT NULL COMMENT 'License key used in request',
        endpoint varchar(100) NOT NULL COMMENT 'API endpoint accessed',
        method varchar(10) NOT NULL DEFAULT 'GET' COMMENT 'HTTP method',
        ip_address varchar(45) NOT NULL COMMENT 'Client IP address',
        user_agent text NULL COMMENT 'Client user agent',
        device_id varchar(64) NULL COMMENT 'Device ID if provided',
        request_data longtext NULL COMMENT 'Request parameters (JSON)',
        response_status int(11) NOT NULL COMMENT 'HTTP response status code',
        response_data longtext NULL COMMENT 'Response data (JSON, may be truncated)',
        authentication_result enum('success','invalid_license','expired_license','suspended_license','device_limit','rate_limit','vps_blocked','other') NOT NULL COMMENT 'Authentication result',
        security_flags longtext NULL COMMENT 'Security flags and warnings (JSON)',
        execution_time_ms int(11) NULL COMMENT 'Request execution time in milliseconds',
        memory_usage_mb decimal(8,2) NULL COMMENT 'Memory usage in MB',
        rate_limit_remaining int(11) NULL COMMENT 'Rate limit remaining after request',
        rate_limit_reset_at datetime NULL COMMENT 'When rate limit resets',
        country_code varchar(2) NULL COMMENT 'Country code from IP',
        city varchar(100) NULL COMMENT 'City from IP geolocation',
        accessed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_license_id (license_id),
        KEY idx_license_key (license_key),
        KEY idx_endpoint (endpoint),
        KEY idx_ip_address (ip_address),
        KEY idx_device_id (device_id),
        KEY idx_response_status (response_status),
        KEY idx_authentication_result (authentication_result),
        KEY idx_accessed_at (accessed_at),
        KEY idx_country_code (country_code)
    ) $charset_collate;";

    // Try to create table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    echo "   SQL Length: " . strlen($sql) . " characters\n";
    echo "   Charset Collate: {$charset_collate}\n";

    $result = dbDelta($sql);

    echo "   dbDelta Result: " . print_r($result, true) . "\n";

    if ($wpdb->last_error) {
        echo "   ❌ MySQL Error: " . $wpdb->last_error . "\n";
    }

    // Check if table was created
    $table_created = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table_name
    )) === $table_name;

    echo "   Table created: " . ($table_created ? '✅ YES' : '❌ NO') . "\n";

    if ($table_created) {
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        echo "   Columns created: " . count($columns) . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
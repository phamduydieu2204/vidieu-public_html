<?php
/**
 * VD License Manager - Database Table Debugging
 * Check LMfWC table existence và prefix
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

echo "<h2>🔍 Database Table Analysis - " . current_time('Y-m-d H:i:s') . "</h2>";

echo "<h3>1. Database Connection Info</h3>";
echo "Database Name: " . DB_NAME . "<br>";
echo "Table Prefix: " . $wpdb->prefix . "<br>";
echo "Last Error: " . ($wpdb->last_error ?: 'None') . "<br>";

echo "<h3>2. All Tables với LMfWC pattern</h3>";
$tables = $wpdb->get_results("SHOW TABLES LIKE '%lmfwc%'", ARRAY_N);

if ($tables) {
    foreach ($tables as $table) {
        echo "✅ Found: " . $table[0] . "<br>";
    }
} else {
    echo "❌ No LMfWC tables found<br>";
}

echo "<h3>3. All Tables với BZ pattern</h3>";
$bz_tables = $wpdb->get_results("SHOW TABLES LIKE '%bz_%'", ARRAY_N);

if ($bz_tables) {
    foreach ($bz_tables as $table) {
        echo "✅ Found: " . $table[0] . "<br>";
    }
} else {
    echo "❌ No BZ_ tables found<br>";
}

echo "<h3>4. All Tables trong database</h3>";
$all_tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
$relevant_tables = array();

foreach ($all_tables as $table) {
    $table_name = $table[0];
    if (strpos($table_name, 'licens') !== false ||
        strpos($table_name, 'lmfwc') !== false ||
        strpos($table_name, 'bz_') !== false ||
        strpos($table_name, 'vd_') !== false) {
        $relevant_tables[] = $table_name;
    }
}

if ($relevant_tables) {
    echo "📊 Relevant license tables found:<br>";
    foreach ($relevant_tables as $table) {
        echo "- " . $table . "<br>";
    }
} else {
    echo "⚠️ No license-related tables found<br>";
}

echo "<h3>5. Table Prefix Testing</h3>";
$possible_prefixes = array(
    $wpdb->prefix . 'lmfwc_licenses',
    'bz_lmfwc_licenses',
    $wpdb->prefix . 'bz_lmfwc_licenses',
    'wp_bz_lmfwc_licenses',
    $wpdb->prefix . 'vd_licenses'
);

foreach ($possible_prefixes as $test_table) {
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $test_table
    ));

    $status = $exists > 0 ? "✅ EXISTS" : "❌ NOT FOUND";
    echo "{$status}: {$test_table}<br>";
}

echo "<h3>6. LMfWC Plugin Check</h3>";
if (is_plugin_active('license-manager-for-woocommerce/license-manager-for-woocommerce.php')) {
    echo "✅ LMfWC Plugin: ACTIVE<br>";
} else {
    echo "❌ LMfWC Plugin: NOT ACTIVE<br>";
}

// Check for LMfWC constants
if (defined('LMFWC_VERSION')) {
    echo "✅ LMfWC Version: " . LMFWC_VERSION . "<br>";
} else {
    echo "❌ LMfWC Constants: NOT DEFINED<br>";
}

echo "<h3>7. WordPress Tables Check</h3>";
$wp_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}%'", ARRAY_N);
echo "📊 WordPress tables found: " . count($wp_tables) . "<br>";

echo "<h3>8. Environment Detection</h3>";
$env_file = ABSPATH . '.env';
if (file_exists($env_file)) {
    echo "✅ .env file exists<br>";
    $env_content = file_get_contents($env_file);
    if (strpos($env_content, 'LMFWC_TABLE') !== false) {
        echo "✅ LMFWC_TABLE config found in .env<br>";
    }
} else {
    echo "❌ .env file not found<br>";
}

echo "<p><strong>Analysis completed.</strong></p>";
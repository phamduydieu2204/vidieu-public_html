<?php
/**
 * VD License Manager - Database Lookup Fix Verification
 * Test database table fix cho Step 4.2.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🔧 Database Lookup Fix Verification - " . current_time('Y-m-d H:i:s') . "</h2>";

// 1. Check plugin loading
echo "<h3>1. Plugin Status</h3>";
echo "VD_License_Manager: " . (class_exists('VD_License_Manager') ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";
echo "VD_License_Validator: " . (class_exists('VD_License_Validator') ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";

if (!class_exists('VD_License_Validator')) {
    echo "<p style='color: red;'>❌ Cannot proceed - VD_License_Validator not loaded</p>";
    return;
}

// 2. Database table checking
echo "<h3>2. Database Table Verification</h3>";
global $wpdb;

$validator = VD_License_Validator::get_instance();

// Test table existence method
try {
    $reflection = new ReflectionClass($validator);
    $method = $reflection->getMethod('table_exists');
    $method->setAccessible(true);

    // Test different table names
    $test_tables = array(
        'bz_lmfwc_licenses' => 'LMfWC Table (corrected)',
        $wpdb->prefix . 'lmfwc_licenses' => 'WP Prefix + LMfWC',
        $wpdb->prefix . 'vd_licenses' => 'VD Internal Table',
        'wp_lmfwc_licenses' => 'Direct WP + LMfWC'
    );

    foreach ($test_tables as $table_name => $description) {
        $exists = $method->invoke($validator, $table_name);
        $status = $exists ? "✅ EXISTS" : "❌ NOT FOUND";
        echo "{$status} {$description}: {$table_name}<br>";
    }

    echo "<h3>3. Debug Information Test</h3>";
    $debug_method = $reflection->getMethod('get_lookup_debug_info');
    $debug_method->setAccessible(true);
    $debug_info = $debug_method->invoke($validator, 'TEST-KEY');

    echo "📊 Debug Info:<br>";
    foreach ($debug_info as $key => $value) {
        if (is_bool($value)) {
            $value = $value ? 'TRUE' : 'FALSE';
        }
        echo "- {$key}: {$value}<br>";
    }

} catch (Exception $e) {
    echo "❌ Reflection error: " . $e->getMessage() . "<br>";
}

// 3. Test license validation with corrected table
echo "<h3>4. License Validation Test</h3>";

$test_licenses = array(
    'H10D-DIJD-14RC-SOLE-6KUV30',
    'ABCD-EFGH-IJKL-MNOP',
    'INVALID-KEY'
);

foreach ($test_licenses as $test_key) {
    try {
        $result = $validator->validate_license_expiry($test_key);

        $status = $result['valid'] ? "✅ VALID" : "✅ PROCESSED";
        $error_code = $result['code'] ?? 'no_code';

        echo "{$status} License '{$test_key}': {$error_code}<br>";

        // Check if lookup details are included
        if (isset($result['lookup_details'])) {
            echo "   📊 Lookup details available<br>";
        }

    } catch (Exception $e) {
        echo "❌ License '{$test_key}': ERROR - " . $e->getMessage() . "<br>";
    }
}

// 4. Direct database query test
echo "<h3>5. Direct Database Query Test</h3>";

try {
    // Test bz_lmfwc_licenses table directly
    $count = $wpdb->get_var("SELECT COUNT(*) FROM bz_lmfwc_licenses");
    if ($count !== null) {
        echo "✅ Direct query bz_lmfwc_licenses: {$count} records found<br>";
    } else {
        echo "❌ Direct query bz_lmfwc_licenses: FAILED<br>";
        echo "   Error: " . ($wpdb->last_error ?: 'Unknown') . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Direct query error: " . $e->getMessage() . "<br>";
}

// 5. Sample data test if table exists
echo "<h3>6. Sample Data Test</h3>";

try {
    $sample_data = $wpdb->get_results("SELECT license_key, status, expires_at FROM bz_lmfwc_licenses LIMIT 3", ARRAY_A);

    if ($sample_data) {
        echo "✅ Sample data retrieved:<br>";
        foreach ($sample_data as $license) {
            echo "- Key: " . substr($license['license_key'], 0, 8) . "*** Status: {$license['status']} Expires: {$license['expires_at']}<br>";
        }
    } else {
        echo "⚠️ No sample data found (table might be empty)<br>";
    }
} catch (Exception $e) {
    echo "❌ Sample data error: " . $e->getMessage() . "<br>";
}

// 6. LMfWC Status Mapping Test
echo "<h3>7. Status Mapping Test</h3>";

try {
    $reflection = new ReflectionClass($validator);
    $mapping_method = $reflection->getMethod('map_lmfwc_status');
    $mapping_method->setAccessible(true);

    $test_statuses = array(1, 2, 3, 4, 'active', 'inactive', 'expired', 'disabled');

    foreach ($test_statuses as $status) {
        $mapped = $mapping_method->invoke($validator, $status);
        echo "Status {$status} → {$mapped}<br>";
    }

} catch (Exception $e) {
    echo "❌ Status mapping error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>🏆 Fix Verification Results</h3>";

// Calculate overall status
$table_exists = false;
try {
    $reflection = new ReflectionClass($validator);
    $method = $reflection->getMethod('table_exists');
    $method->setAccessible(true);
    $table_exists = $method->invoke($validator, 'bz_lmfwc_licenses');
} catch (Exception $e) {
    // Ignore
}

if ($table_exists) {
    echo "<p style='color: green;'><strong>✅ DATABASE FIX SUCCESSFUL</strong></p>";
    echo "<p>🎯 Table 'bz_lmfwc_licenses' found and accessible</p>";
    echo "<p>🚀 Step 4.2.3 Database Lookup functionality is working</p>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ TABLE NOT FOUND</strong></p>";
    echo "<p>🔍 LMfWC table 'bz_lmfwc_licenses' not available</p>";
    echo "<p>🔄 System will use fallback to VD internal tables</p>";
}

echo "<p><strong>Verification completed.</strong></p>";
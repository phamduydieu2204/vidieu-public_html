<?php
/**
 * VD License Manager Encryption Test
 *
 * Simple test to check if our encryption methods work
 */

// Load WordPress
require_once 'wp-load.php';

// Security check - basic protection
if (!isset($_GET['test']) || $_GET['test'] !== 'vd123') {
    die('Access denied');
}

echo "<!DOCTYPE html><html><head><title>VD Test</title></head><body>";
echo "<h1>VD License Manager Test</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

try {
    global $wpdb;

    // Check database connection
    echo "<h2>Database Test</h2>";
    $result = $wpdb->get_var("SELECT 1 as test");
    if ($result == 1) {
        echo "<p style='color:green'>✅ Database connected</p>";
    } else {
        echo "<p style='color:red'>❌ Database connection failed</p>";
    }

    // Check for license table
    echo "<h2>License Table Check</h2>";
    $tables_to_check = array(
        $wpdb->prefix . 'vd_license_keys',
        $wpdb->prefix . 'lmfwc_licenses'
    );

    $license_table = null;
    foreach ($tables_to_check as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        if ($exists) {
            echo "<p style='color:green'>✅ Found table: $table</p>";
            $license_table = $table;
            break;
        } else {
            echo "<p style='color:orange'>⚠️ Table not found: $table</p>";
        }
    }

    if (!$license_table) {
        echo "<p style='color:red'>❌ No license table found</p>";
        // Show available tables
        echo "<h3>Available tables:</h3><ul>";
        $all_tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        foreach ($all_tables as $table) {
            if (strpos($table[0], 'license') !== false || strpos($table[0], 'lmfwc') !== false) {
                echo "<li>{$table[0]}</li>";
            }
        }
        echo "</ul>";
    } else {
        // Get sample licenses
        echo "<h2>Sample License Keys</h2>";
        $licenses = $wpdb->get_results("SELECT id, license_key, LEFT(license_key, 50) as sample FROM $license_table LIMIT 3", ARRAY_A);

        if (empty($licenses)) {
            echo "<p style='color:red'>❌ No licenses found</p>";
        } else {
            echo "<table border='1' style='border-collapse:collapse'>";
            echo "<tr><th>ID</th><th>Length</th><th>Sample</th></tr>";
            foreach ($licenses as $license) {
                echo "<tr>";
                echo "<td>{$license['id']}</td>";
                echo "<td>" . strlen($license['license_key']) . "</td>";
                echo "<td>{$license['sample']}...</td>";
                echo "</tr>";
            }
            echo "</table>";

            // Test if our encryption class exists
            echo "<h2>Encryption Class Test</h2>";

            $plugin_file = 'wp-content/plugins/vd-license-manager/includes/class-vd-lm-encryption.php';
            if (file_exists($plugin_file)) {
                require_once $plugin_file;
                echo "<p style='color:green'>✅ Encryption class file loaded</p>";

                if (class_exists('VD_Encryption')) {
                    echo "<p style='color:green'>✅ VD_Encryption class exists</p>";

                    // Test decryption on first license
                    $test_key = $licenses[0]['license_key'];
                    echo "<h3>Testing License ID: {$licenses[0]['id']}</h3>";
                    echo "<p>Encrypted: " . substr($test_key, 0, 50) . "...</p>";

                    $decrypted = VD_Encryption::decrypt($test_key);

                    if (!empty($decrypted)) {
                        echo "<p style='color:green'>✅ <strong>SUCCESS!</strong> Decrypted to: <strong>$decrypted</strong></p>";
                    } else {
                        echo "<p style='color:red'>❌ Decryption failed (empty result)</p>";
                    }

                } else {
                    echo "<p style='color:red'>❌ VD_Encryption class not found</p>";
                }
            } else {
                echo "<p style='color:red'>❌ Encryption class file not found: $plugin_file</p>";
            }
        }
    }

    // Show encryption keys in options table
    echo "<h2>WordPress Options - Encryption Keys</h2>";
    $options = $wpdb->get_results("
        SELECT option_name, LEFT(option_value, 40) as sample, LENGTH(option_value) as len
        FROM {$wpdb->prefix}options
        WHERE option_name LIKE '%encrypt%' OR option_name LIKE '%key%'
    ", ARRAY_A);

    if (empty($options)) {
        echo "<p>No encryption-related options found</p>";
    } else {
        echo "<table border='1' style='border-collapse:collapse'>";
        echo "<tr><th>Option Name</th><th>Sample Value</th><th>Length</th></tr>";
        foreach ($options as $option) {
            echo "<tr>";
            echo "<td>{$option['option_name']}</td>";
            echo "<td>{$option['sample']}...</td>";
            echo "<td>{$option['len']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><em>Test completed: " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>
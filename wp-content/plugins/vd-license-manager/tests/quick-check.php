<?php
// Prevent timeout
set_time_limit(120);
ini_set('memory_limit', '512M');

// Enable error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load WordPress
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/../../../../wp-load.php');
}

// Track execution time
$start_time = microtime(true);
$peak_memory = 0;

function track_memory() {
    global $peak_memory;
    $current = memory_get_usage(true);
    if ($current > $peak_memory) {
        $peak_memory = $current;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>VD License Manager - Quick Check</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
            line-height: 1.6;
        }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .info { color: blue; }
        .warning { color: orange; font-weight: bold; }
        .section {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #007cba;
        }
        .header {
            background: #0073aa;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        pre {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>VD License Manager - Quick Check</h1>
        <p class="info">Fast basic verification of DAY 7-8 implementation</p>
        <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <!-- SECTION 1: ENVIRONMENT CHECK -->
    <div class="section">
        <h2>Section 1: Environment Check</h2>
        <?php
        track_memory();

        try {
            // PHP Version
            $php_version = PHP_VERSION;
            if (version_compare($php_version, '7.4', '>=')) {
                echo '<p class="pass">✅ PHP Version: ' . $php_version . '</p>';
            } else {
                echo '<p class="fail">❌ PHP Version: ' . $php_version . ' (requires 7.4+)</p>';
            }

            // WordPress
            if (defined('ABSPATH') && function_exists('get_bloginfo')) {
                $wp_version = get_bloginfo('version');
                echo '<p class="pass">✅ WordPress: ' . $wp_version . ' loaded</p>';
            } else {
                echo '<p class="fail">❌ WordPress not loaded properly</p>';
            }

            // Database connection
            global $wpdb;
            if ($wpdb && $wpdb->db_version()) {
                $db_version = $wpdb->db_version();
                echo '<p class="pass">✅ Database: Connected (' . $db_version . ')</p>';
            } else {
                echo '<p class="fail">❌ Database: Connection failed</p>';
            }

            // Required PHP extensions
            $extensions = ['json', 'curl', 'mysqli'];
            foreach ($extensions as $ext) {
                if (extension_loaded($ext)) {
                    echo '<p class="pass">✅ PHP Extension: ' . $ext . '</p>';
                } else {
                    echo '<p class="fail">❌ PHP Extension missing: ' . $ext . '</p>';
                }
            }

        } catch (Exception $e) {
            echo '<p class="fail">❌ Environment check failed: ' . esc_html($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <!-- SECTION 2: DATABASE TABLES -->
    <div class="section">
        <h2>Section 2: Database Tables</h2>
        <?php
        track_memory();

        try {
            global $wpdb;

            $required_tables = [
                // Core tables (must exist)
                'vd_license_keys',           // Main license table (NOT vd_licenses)
                'vd_license_devices',        // Device tracking (NOT vd_devices)
                'vd_license_access_log',     // Access logging
                'vd_pools',                  // Pool definitions
                'vd_provider_accounts',      // Account credentials

                // Configuration tables
                'vd_product_share_configs',  // Per-product rules
                'vd_product_pools',          // Product-pool links
                'vd_pool_accounts',          // Pool-account links

                // Tracking tables
                'vd_license_rate_limits',    // Rate limiting
                'vd_license_device_limits',  // Device limits
                'vd_device_fingerprints',    // Fingerprint history
                'vd_account_fetch_log'       // Fetch tracking
            ];

            $missing_tables = [];

            foreach ($required_tables as $table) {
                $table_name = $wpdb->prefix . $table;
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SHOW TABLES LIKE %s",
                    $table_name
                ));

                if ($exists) {
                    echo '<p class="pass">✅ Table exists: ' . $table . '</p>';
                } else {
                    echo '<p class="fail">❌ Table missing: ' . $table . '</p>';
                    $missing_tables[] = $table;
                }
            }

            if (empty($missing_tables)) {
                echo '<p class="info">🎉 All core tables found!</p>';
            } else {
                echo '<p class="warning">⚠️ Missing tables: ' . implode(', ', $missing_tables) . '</p>';
            }

        } catch (Exception $e) {
            echo '<p class="fail">❌ Database table check failed: ' . esc_html($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <!-- SECTION 3: PLUGIN CLASSES -->
    <div class="section">
        <h2>Section 3: Plugin Classes</h2>
        <?php
        track_memory();

        try {
            $required_classes = [
                'VD_License_Manager' => 'Main plugin class',
                'VD_REST_API' => 'REST API handler',
                'VD_LM_Device_Manager' => 'Device manager service',
                'VD_LM_Database' => 'Database installer'
            ];

            $missing_classes = [];

            foreach ($required_classes as $class => $description) {
                if (class_exists($class)) {
                    echo '<p class="pass">✅ Class loaded: ' . $class . ' (' . $description . ')</p>';
                } else {
                    echo '<p class="fail">❌ Class missing: ' . $class . ' (' . $description . ')</p>';
                    $missing_classes[] = $class;
                }
            }

            if (empty($missing_classes)) {
                echo '<p class="info">🎉 All plugin classes loaded!</p>';
            } else {
                echo '<p class="warning">⚠️ Missing classes: ' . implode(', ', $missing_classes) . '</p>';
            }

        } catch (Exception $e) {
            echo '<p class="fail">❌ Plugin class check failed: ' . esc_html($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <!-- SECTION 4: REST API ENDPOINT -->
    <div class="section">
        <h2>Section 4: REST API Endpoint</h2>
        <?php
        track_memory();

        try {
            // Check if REST API is available
            if (!function_exists('rest_get_server')) {
                echo '<p class="fail">❌ WordPress REST API not available</p>';
            } else {
                echo '<p class="pass">✅ WordPress REST API available</p>';

                // Get REST server
                $server = rest_get_server();
                $routes = $server->get_routes();

                // Check our endpoint
                $our_endpoint = '/vd/v1/license/access';
                if (isset($routes[$our_endpoint])) {
                    echo '<p class="pass">✅ REST Endpoint registered: ' . $our_endpoint . '</p>';

                    // Show methods
                    $methods = [];
                    foreach ($routes[$our_endpoint] as $route) {
                        if (isset($route['methods'])) {
                            $methods = array_merge($methods, array_keys($route['methods']));
                        }
                    }
                    if (!empty($methods)) {
                        echo '<p class="info">   Methods: ' . implode(', ', array_unique($methods)) . '</p>';
                    }
                } else {
                    echo '<p class="fail">❌ REST Endpoint missing: ' . $our_endpoint . '</p>';
                }

                // Check namespace
                $vd_routes = array_filter(array_keys($routes), function($route) {
                    return strpos($route, '/vd/v1/') === 0;
                });

                if (!empty($vd_routes)) {
                    echo '<p class="info">   VD endpoints found: ' . count($vd_routes) . '</p>';
                } else {
                    echo '<p class="warning">⚠️ No VD endpoints found in namespace /vd/v1/</p>';
                }
            }

        } catch (Exception $e) {
            echo '<p class="fail">❌ REST API check failed: ' . esc_html($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <!-- SECTION 5: SAMPLE DATA TEST -->
    <div class="section">
        <h2>Section 5: Sample Data Test</h2>
        <?php
        track_memory();

        try {
            global $wpdb;

            $test_license_key = 'TEST-QUICK-CHECK-' . time();
            $table_name = $wpdb->prefix . 'vd_licenses';

            // Check if table exists first
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ));

            if (!$table_exists) {
                echo '<p class="warning">⚠️ Cannot test: Table ' . $table_name . ' does not exist</p>';
            } else {
                // Try to insert test record
                $insert_result = $wpdb->insert(
                    $table_name,
                    [
                        'license_key' => $test_license_key,
                        'status' => 'active',
                        'product_id' => 9999,
                        'max_devices' => 2
                    ],
                    ['%s', '%s', '%d', '%d']
                );

                if ($insert_result) {
                    echo '<p class="pass">✅ Sample insert: SUCCESS</p>';

                    // Try to query it back
                    $query_result = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$table_name} WHERE license_key = %s",
                        $test_license_key
                    ));

                    if ($query_result && $query_result->license_key === $test_license_key) {
                        echo '<p class="pass">✅ Sample query: SUCCESS</p>';

                        // Delete test record
                        $delete_result = $wpdb->delete(
                            $table_name,
                            ['license_key' => $test_license_key],
                            ['%s']
                        );

                        if ($delete_result) {
                            echo '<p class="pass">✅ Sample delete: SUCCESS</p>';
                            echo '<p class="info">🎉 Database operations working properly!</p>';
                        } else {
                            echo '<p class="fail">❌ Sample delete: FAILED</p>';
                        }
                    } else {
                        echo '<p class="fail">❌ Sample query: FAILED</p>';
                    }
                } else {
                    echo '<p class="fail">❌ Sample insert: FAILED</p>';
                    if ($wpdb->last_error) {
                        echo '<p class="fail">   Error: ' . esc_html($wpdb->last_error) . '</p>';
                    }
                }
            }

        } catch (Exception $e) {
            echo '<p class="fail">❌ Sample data test failed: ' . esc_html($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <!-- PERFORMANCE SECTION -->
    <div class="section">
        <h2>Performance</h2>
        <?php
        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 2);
        $peak_memory_mb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $current_memory_mb = round(memory_get_usage(true) / 1024 / 1024, 2);
        ?>
        <p>⏱️ Execution time: <strong><?php echo $execution_time; ?>s</strong></p>
        <p>💾 Peak memory: <strong><?php echo $peak_memory_mb; ?>MB</strong></p>
        <p>💾 Current memory: <strong><?php echo $current_memory_mb; ?>MB</strong></p>

        <?php if ($execution_time < 5): ?>
            <p class="pass">✅ Performance: GOOD (< 5 seconds)</p>
        <?php elseif ($execution_time < 15): ?>
            <p class="warning">⚠️ Performance: ACCEPTABLE (< 15 seconds)</p>
        <?php else: ?>
            <p class="fail">❌ Performance: SLOW (> 15 seconds)</p>
        <?php endif; ?>

        <?php if ($peak_memory_mb < 64): ?>
            <p class="pass">✅ Memory usage: GOOD (< 64MB)</p>
        <?php elseif ($peak_memory_mb < 128): ?>
            <p class="warning">⚠️ Memory usage: ACCEPTABLE (< 128MB)</p>
        <?php else: ?>
            <p class="fail">❌ Memory usage: HIGH (> 128MB)</p>
        <?php endif; ?>
    </div>

    <!-- SUMMARY SECTION -->
    <div class="section">
        <h2>Summary & Next Steps</h2>

        <?php
        // Count pass/fail from output (simple approach)
        ob_start();
        ?>

        <p class="info"><strong>Quick Check Completed!</strong></p>

        <p><strong>If all checks show ✅ (green):</strong></p>
        <ul>
            <li>Your DAY 7-8 implementation is properly installed</li>
            <li>Core components are loaded and functional</li>
            <li>You can proceed to full testing</li>
        </ul>

        <p><strong>If you see ❌ (red) errors:</strong></p>
        <ul>
            <li>Fix the reported issues first</li>
            <li>Re-run this quick check</li>
            <li>Do not proceed to full testing until all issues are resolved</li>
        </ul>

        <p><strong>Next Steps:</strong></p>
        <ul>
            <li><strong>Full Test:</strong> <a href="manual-test-day-7-8.php" target="_blank">manual-test-day-7-8.php</a></li>
            <li><strong>API Testing:</strong> Use Postman or curl to test REST endpoints</li>
            <li><strong>Documentation:</strong> See <a href="README.md" target="_blank">README.md</a> for testing guide</li>
        </ul>

        <p><strong>Test Endpoints:</strong></p>
        <pre>POST <?php echo home_url('/wp-json/vd/v1/license/access'); ?>
Content-Type: application/json

{
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30",
    "device_combined_id": "test_device_001",
    "device_name": "Test Device"
}</pre>
    </div>

    <div class="section">
        <h2>Debug Information</h2>
        <p><strong>WordPress Info:</strong></p>
        <ul>
            <li>WordPress Version: <?php echo get_bloginfo('version'); ?></li>
            <li>PHP Version: <?php echo PHP_VERSION; ?></li>
            <li>Database Version: <?php echo $wpdb->db_version(); ?></li>
            <li>Site URL: <?php echo home_url(); ?></li>
            <li>Plugin Directory: <?php echo WP_PLUGIN_DIR; ?></li>
        </ul>

        <p><strong>VD Plugin Info:</strong></p>
        <ul>
            <li>Plugin Active: <?php echo is_plugin_active('vd-license-manager/vd-license-manager.php') ? 'YES' : 'NO'; ?></li>
            <li>Table Prefix: <?php echo $wpdb->prefix; ?></li>
            <li>REST URL Base: <?php echo rest_url('vd/v1/'); ?></li>
        </ul>
    </div>

</body>
</html>
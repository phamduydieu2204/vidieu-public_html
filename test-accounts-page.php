<?php
/**
 * TEMPORARY Test File for Provider Accounts Page
 * Access: /test-accounts-page.php?run_test=yes
 * DELETE AFTER TESTING
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Security checks
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin access required.');
}

if (!isset($_GET['run_test']) || $_GET['run_test'] !== 'yes') {
    wp_die('Add ?run_test=yes to URL to run tests');
}

global $wpdb;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Provider Accounts Page Tests</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #0073aa; margin-top: 30px; padding: 10px; background: #fff; border-left: 4px solid #0073aa; }
        p { padding: 10px; background: #fff; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .summary { background: #0073aa; color: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .test-result { margin: 10px 0; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧪 Provider Accounts Page Tests</h1>
    <p><strong>Warning:</strong> This is a temporary test file. Delete after verification.</p>

    <?php
    // TEST 1: Check Database Table
    echo '<h2>TEST 1: Database Table Check</h2>';

    $table_name = 'bz_vd_provider_accounts';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

    if ($table_exists) {
        echo '<div class="test-result success">✅ Table exists: ' . $table_name . '</div>';

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo '<div class="test-result">📊 Current record count: ' . intval($count) . '</div>';
    } else {
        echo '<div class="test-result error">❌ Table does not exist: ' . $table_name . '</div>';
        echo '<p>Please run the plugin activator to create database tables.</p>';
    }

    // TEST 2: Insert Test Data (if needed)
    echo '<h2>TEST 2: Insert Test Data</h2>';

    if ($table_exists && $count < 3) {
        echo '<div class="test-result">🔄 Inserting test data...</div>';

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
                echo '<div class="test-result success">✅ Inserted: ' . esc_html($account['display_name']) . '</div>';
            } else {
                echo '<div class="test-result error">❌ Failed to insert: ' . esc_html($account['display_name']) . '</div>';
                echo '<div class="test-result error">Error: ' . esc_html($wpdb->last_error) . '</div>';
            }
        }

        // Re-count after insertion
        $new_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo '<div class="test-result">📊 New record count: ' . intval($new_count) . '</div>';
    } else {
        echo '<div class="test-result">ℹ️ Test data already exists or table not available</div>';
    }

    // TEST 3: Check Repository Classes
    echo '<h2>TEST 3: Repository Classes Check</h2>';

    if (class_exists('VD_Accounts_Repository')) {
        echo '<div class="test-result success">✅ VD_Accounts_Repository class loaded</div>';

        try {
            $total = VD_Accounts_Repository::get_total_count();
            echo '<div class="test-result success">✅ Repository working - Total accounts: ' . $total . '</div>';
        } catch (Exception $e) {
            echo '<div class="test-result error">❌ Repository error: ' . $e->getMessage() . '</div>';
        }
    } else {
        echo '<div class="test-result error">❌ VD_Accounts_Repository class not found</div>';
    }

    // TEST 4: Check Admin Classes
    echo '<h2>TEST 4: Admin Classes Check</h2>';

    $admin_classes = array(
        'VD_Admin_Provider_Accounts',
        'VD_Accounts_List_Table',
        'VD_Accounts_List_View'
    );

    foreach ($admin_classes as $class) {
        if (class_exists($class)) {
            echo '<div class="test-result success">✅ ' . $class . ' class loaded</div>';
        } else {
            echo '<div class="test-result error">❌ ' . $class . ' class not found</div>';
        }
    }

    // TEST 5: Test Provider List
    echo '<h2>TEST 5: Provider List Test</h2>';

    if (class_exists('VD_Accounts_Repository')) {
        try {
            $providers = VD_Accounts_Repository::get_providers();
            if (is_array($providers) && count($providers) > 0) {
                echo '<div class="test-result success">✅ Provider list retrieved - Count: ' . count($providers) . '</div>';
                echo '<div class="test-result">📋 Providers: ' . implode(', ', $providers) . '</div>';
            } else {
                echo '<div class="test-result error">❌ No providers found or invalid response</div>';
            }
        } catch (Exception $e) {
            echo '<div class="test-result error">❌ Provider list error: ' . $e->getMessage() . '</div>';
        }
    }
    ?>

    <div class="summary">
        <h2 style="color: white; background: transparent; border: none;">🎯 Test Summary</h2>
        <p style="background: transparent;"><strong>Next Step:</strong> Go to the Provider Accounts page in WordPress admin</p>
        <p style="background: transparent;"><strong>URL:</strong> <a href="<?php echo admin_url('admin.php?page=vd-provider-accounts'); ?>" style="color: #fff; text-decoration: underline;"><?php echo admin_url('admin.php?page=vd-provider-accounts'); ?></a></p>
        <p style="background: transparent;"><strong>Manual Test Checklist:</strong></p>
        <ol style="background: transparent;">
            <li>Page loads without errors</li>
            <li>Table displays with test data</li>
            <li>Search box works</li>
            <li>Provider and status filters work</li>
            <li>Sorting works on columns</li>
            <li>Edit links work</li>
            <li>Bulk actions dropdown exists</li>
        </ol>
        <p style="background: transparent;"><strong>Remember to delete this file after testing:</strong></p>
        <pre style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); padding: 10px;">/test-accounts-page.php</pre>
    </div>

</body>
</html>
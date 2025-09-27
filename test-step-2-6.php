<?php
/**
 * Step 2.6 Testing Script
 * Test Provider Account & Device Managers functionality
 */

// WordPress bootstrap
require_once('wp-config.php');
require_once('wp-includes/wp-db.php');

global $wpdb;

echo "=== STEP 2.6 TESTING SCRIPT ===\n\n";

// 1. Check database tables
echo "1. CHECKING DATABASE TABLES:\n";
echo "=============================\n";

$tables_to_check = [
    'bz_vd_licenses',
    'bz_vd_license_meta',
    'bz_vd_providers',
    'bz_vd_provider_accounts',
    'bz_vd_devices',
    'bz_vd_device_approvals',
    'bz_vd_audit_logs',
    'bz_vd_system_config',
    'bz_vd_encryption_keys',
    'bz_vd_api_requests',
    'bz_vd_cache_data'
];

$existing_tables = [];
$missing_tables = [];

foreach ($tables_to_check as $table) {
    $result = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if ($result) {
        $existing_tables[] = $table;
        echo "✅ {$table} - EXISTS\n";
    } else {
        $missing_tables[] = $table;
        echo "❌ {$table} - MISSING\n";
    }
}

echo "\nSummary: " . count($existing_tables) . "/" . count($tables_to_check) . " tables exist\n\n";

// 2. Test Provider Account Manager
echo "2. TESTING PROVIDER ACCOUNT MANAGER:\n";
echo "====================================\n";

// Load the plugin classes
require_once('wp-content/plugins/vd-license-manager/includes/class-vd-encryption-manager.php');
require_once('wp-content/plugins/vd-license-manager/includes/class-vd-provider-account.php');

try {
    // Test basic provider account creation
    $provider_data = [
        'provider_id' => 1,
        'account_name' => 'Test Provider Account',
        'api_key' => 'test_api_key_123456789',
        'api_secret' => 'test_secret_abcdefgh',
        'endpoint_url' => 'https://api.testprovider.com',
        'auth_method' => 'api_key',
        'status' => 'active',
        'health_score' => 95,
        'last_health_check' => current_time('mysql'),
        'settings' => json_encode(['timeout' => 30, 'retries' => 3])
    ];

    echo "Creating test provider account...\n";
    $provider_manager = new VD_Provider_Account();
    $result = $provider_manager->create_account($provider_data);

    if ($result) {
        echo "✅ Provider account created successfully (ID: {$result})\n";

        // Test encryption by retrieving the account
        echo "Testing data retrieval and decryption...\n";
        $retrieved = $provider_manager->get_account($result);

        if ($retrieved && $retrieved['api_key'] === 'test_api_key_123456789') {
            echo "✅ Data encryption/decryption working correctly\n";
        } else {
            echo "❌ Data encryption/decryption failed\n";
        }

        // Cleanup
        $provider_manager->delete_account($result);
        echo "✅ Test data cleaned up\n";
    } else {
        echo "❌ Provider account creation failed\n";
    }

} catch (Exception $e) {
    echo "❌ Provider Account Manager Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test Device Manager
echo "3. TESTING DEVICE MANAGER:\n";
echo "=========================\n";

require_once('wp-content/plugins/vd-license-manager/includes/class-vd-device-manager.php');

try {
    $device_data = [
        'license_id' => 1,
        'device_name' => 'Test Device',
        'device_fingerprint' => 'test_fingerprint_' . time(),
        'platform' => 'Windows',
        'platform_version' => '10.0',
        'hardware_id' => 'TEST-HW-' . time(),
        'mac_address' => '00:11:22:33:44:55',
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Test User Agent',
        'registration_source' => 'api'
    ];

    echo "Registering test device...\n";
    $device_manager = new VD_Device_Manager();
    $result = $device_manager->register_device($device_data);

    if ($result) {
        echo "✅ Device registered successfully (ID: {$result})\n";

        // Test auto-approval
        echo "Testing auto-approval algorithm...\n";
        $device_info = $device_manager->get_device($result);

        if ($device_info) {
            $approval_status = $device_info['approval_status'];
            echo "Device approval status: {$approval_status}\n";

            if (in_array($approval_status, ['approved', 'pending'])) {
                echo "✅ Auto-approval algorithm working\n";
            } else {
                echo "❌ Auto-approval algorithm failed\n";
            }
        }

        // Cleanup
        $device_manager->delete_device($result);
        echo "✅ Test device cleaned up\n";
    } else {
        echo "❌ Device registration failed\n";
    }

} catch (Exception $e) {
    echo "❌ Device Manager Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Check audit trails
echo "4. CHECKING AUDIT TRAILS:\n";
echo "=========================\n";

$audit_count = $wpdb->get_var("SELECT COUNT(*) FROM bz_vd_audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
echo "Recent audit log entries (last 5 minutes): {$audit_count}\n";

if ($audit_count > 0) {
    echo "✅ Audit trail logging is working\n";

    // Show latest audit entry
    $latest_audit = $wpdb->get_row("SELECT * FROM bz_vd_audit_logs ORDER BY created_at DESC LIMIT 1", ARRAY_A);
    if ($latest_audit) {
        echo "Latest audit entry:\n";
        echo "  - Action: {$latest_audit['action']}\n";
        echo "  - Entity: {$latest_audit['entity_type']} (ID: {$latest_audit['entity_id']})\n";
        echo "  - Time: {$latest_audit['created_at']}\n";
    }
} else {
    echo "⚠️ No recent audit entries found\n";
}

echo "\n";

// 5. Test encryption manager directly
echo "5. TESTING ENCRYPTION MANAGER:\n";
echo "===============================\n";

try {
    $encryption_manager = new VD_Encryption_Manager();
    $test_data = "This is sensitive test data: API_KEY_12345";

    echo "Testing encryption...\n";
    $encrypted = $encryption_manager->encrypt($test_data);

    if ($encrypted) {
        echo "✅ Data encrypted successfully\n";

        echo "Testing decryption...\n";
        $decrypted = $encryption_manager->decrypt($encrypted);

        if ($decrypted === $test_data) {
            echo "✅ Data decrypted successfully - encryption is working perfectly\n";
        } else {
            echo "❌ Decryption failed - data mismatch\n";
        }
    } else {
        echo "❌ Encryption failed\n";
    }

} catch (Exception $e) {
    echo "❌ Encryption Manager Error: " . $e->getMessage() . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";
echo "Please review the results above.\n";
echo "All ✅ indicates successful functionality.\n";
echo "Any ❌ indicates issues that need attention.\n";
?>
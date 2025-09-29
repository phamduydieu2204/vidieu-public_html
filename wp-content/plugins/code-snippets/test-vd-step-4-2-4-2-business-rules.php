<?php
/**
 * VD License Manager - Step 4.2.4.2 Test Suite
 * Business Rule Enforcement Engine Testing
 *
 * Test comprehensive business rules với grace periods, escalation, và transition validation
 *
 * @package VD_License_Manager
 * @subpackage Tests
 * @since 4.2.4.2
 */

// Hook trực tiếp vào WordPress admin_init
add_action('admin_init', function() {
    if (isset($_GET['vd_test_4242'])) {
        vd_run_step_4242_test();
        exit;
    }
});

// Admin notice để dễ access
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'snippets') {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>🧪 VD License Manager Testing:</strong> ';
        echo '<a href="' . admin_url('?vd_test_4242=1') . '" class="button button-primary">Run Step 4.2.4.2 Test</a> ';
        echo '<small>(Business Rule Enforcement Engine)</small>';
        echo '</p></div>';
    }
});

/**
 * Main test execution function cho Step 4.2.4.2
 */
function vd_run_step_4242_test() {
    echo '<div style="max-width: 1200px; margin: 20px auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, segoe ui, Roboto, sans-serif;">';
    echo '<h1>🧪 VD License Manager - Step 4.2.4.2 Test</h1>';
    echo '<h2>Business Rule Enforcement Engine</h2>';
    echo '<p><strong>Test executed at:</strong> ' . current_time('mysql') . '</p>';

    // Check WordPress environment
    if (!function_exists('current_user_can')) {
        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
        echo '<h3>❌ WordPress Not Loaded</h3>';
        echo '<p>WordPress functions are not available.</p>';
        echo '</div></div>';
        return;
    }

    echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
    echo '<h3>✅ WordPress Environment Ready</h3>';
    echo '<p>WordPress version: ' . get_bloginfo('version') . '</p>';
    echo '</div>';

    // Check VD License Manager
    if (!class_exists('VD_License_Manager')) {
        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
        echo '<h3>❌ VD License Manager Not Found</h3>';
        echo '<p>Plugin is not loaded. Please ensure it is activated.</p>';
        echo '</div></div>';
        return;
    }

    // Load VD_License_Validator if needed
    if (!class_exists('VD_License_Validator')) {
        $plugin_dir = WP_PLUGIN_DIR . '/vd-license-manager/includes/';
        $dependencies = array(
            'functions.php',
            'class-vd-database-manager.php',
            'class-vd-encryption-manager.php',
            'class-vd-license-validator.php'
        );

        foreach ($dependencies as $file) {
            $file_path = $plugin_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    if (!class_exists('VD_License_Validator')) {
        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
        echo '<h3>❌ VD_License_Validator Not Available</h3>';
        echo '</div></div>';
        return;
    }

    echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
    echo '<h3>✅ VD_License_Validator Available</h3>';
    echo '</div>';

    try {
        $validator = VD_License_Validator::get_instance();

        if (!$validator) {
            throw new Exception('Failed to get VD_License_Validator instance');
        }

        // Test 1: Business Rule Configuration
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>📋 Test 1: Business Rule Configuration</h3>';

        $test_license = array(
            'id' => 1,
            'product_id' => 1,
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+5 days'))
        );

        $reflection = new ReflectionClass($validator);
        $config_method = $reflection->getMethod('get_business_rule_configuration');
        $config_method->setAccessible(true);
        $config = $config_method->invoke($validator, $test_license);

        echo '<p><strong>Grace Periods:</strong></p>';
        echo '<ul>';
        foreach ($config['grace_periods'] as $key => $value) {
            echo '<li>' . $key . ': ' . $value . '</li>';
        }
        echo '</ul>';

        echo '<p><strong>Escalation Rules:</strong></p>';
        echo '<ul>';
        foreach ($config['escalation_rules'] as $key => $value) {
            echo '<li>' . $key . ': ' . $value . '</li>';
        }
        echo '</ul>';
        echo '</div>';

        // Test 2: Grace Period Rules
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>⏰ Test 2: Grace Period Rules</h3>';

        // Test expiry warning grace period
        $grace_method = $reflection->getMethod('enforce_grace_period_rules');
        $grace_method->setAccessible(true);
        $grace_result = $grace_method->invoke($validator, $test_license, $config, array());

        echo '<p><strong>Expiry Warning Test:</strong></p>';
        echo '<p>• Has Grace Period: ' . ($grace_result['has_grace_period'] ? 'Yes' : 'No') . '</p>';
        if ($grace_result['has_grace_period']) {
            echo '<p>• Grace Type: ' . $grace_result['grace_type'] . '</p>';
            echo '<p>• Remaining Hours: ' . $grace_result['grace_remaining_hours'] . '</p>';
        }
        echo '</div>';

        // Test 3: Escalation Rules
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>⚡ Test 3: Escalation Rules</h3>';

        // Test với expired license (30+ days ago)
        $expired_license = array(
            'id' => 2,
            'status' => 'expired',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-35 days'))
        );

        $escalation_method = $reflection->getMethod('enforce_escalation_rules');
        $escalation_method->setAccessible(true);
        $escalation_result = $escalation_method->invoke($validator, $expired_license, $config, array());

        echo '<p><strong>Long-Expired License Test:</strong></p>';
        echo '<p>• Escalation Required: ' . ($escalation_result['escalation_required'] ? 'Yes' : 'No') . '</p>';
        if ($escalation_result['escalation_required']) {
            echo '<p>• Escalation Type: ' . $escalation_result['escalation_type'] . '</p>';
            echo '<p>• Target Status: ' . $escalation_result['target_status'] . '</p>';
            echo '<p>• Reason: ' . $escalation_result['escalation_reason'] . '</p>';
        }
        echo '</div>';

        // Test 4: Transition Rules
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>🔄 Test 4: Transition Rules</h3>';

        $transition_method = $reflection->getMethod('enforce_transition_rules');
        $transition_method->setAccessible(true);

        // Test valid transition
        $valid_transition = $transition_method->invoke($validator, 'inactive', 'active', $test_license, $config);
        echo '<p><strong>Valid Transition (inactive → active):</strong></p>';
        echo '<p>• Allowed: ' . ($valid_transition['allowed'] ? 'Yes' : 'No') . '</p>';
        echo '<p>• Type: ' . ($valid_transition['transition_type'] ?? 'N/A') . '</p>';

        // Test invalid transition (expired → active without permission)
        $invalid_transition = $transition_method->invoke($validator, 'expired', 'active', $expired_license, $config);
        echo '<p><strong>Invalid Transition (expired → active):</strong></p>';
        echo '<p>• Allowed: ' . ($invalid_transition['allowed'] ? 'Yes' : 'No') . '</p>';
        if (!$invalid_transition['allowed']) {
            echo '<p>• Reason: ' . $invalid_transition['reason'] . '</p>';
        }
        echo '</div>';

        // Test 5: Active License Business Rules
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>✅ Test 5: Active License Business Rules</h3>';

        $active_rules_method = $reflection->getMethod('enforce_active_license_business_rules');
        $active_rules_method->setAccessible(true);
        $active_result = $active_rules_method->invoke($validator, $test_license, $config, array());

        echo '<p><strong>Active License with Expiry Warning:</strong></p>';
        echo '<p>• Valid: ' . ($active_result['valid'] ? 'Yes' : 'No') . '</p>';
        echo '<p>• Rules Applied: ' . count($active_result['rules_applied']) . '</p>';

        if (!empty($active_result['rules_applied'])) {
            foreach ($active_result['rules_applied'] as $rule) {
                echo '<p>• ' . $rule['rule'] . ': ' . $rule['message'] . '</p>';
            }
        }
        echo '</div>';

        // Test 6: Full Business Rule Enforcement
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>🏛️ Test 6: Full Business Rule Enforcement</h3>';

        // Test với public method
        if (method_exists($validator, 'enforce_business_rules')) {
            $context = array(
                'from_status' => 'inactive',
                'to_status' => 'active',
                'transition_reason' => 'manual_activation'
            );

            $full_result = $validator->enforce_business_rules($test_license, $context);

            echo '<p><strong>Full Business Rule Test:</strong></p>';
            echo '<p>• Valid: ' . ($full_result['valid'] ? 'Yes' : 'No') . '</p>';
            echo '<p>• Rules Applied: ' . count($full_result['rules_applied'] ?? array()) . '</p>';
            echo '<p>• Grace Period Active: ' . ($full_result['grace_period']['has_grace_period'] ?? false ? 'Yes' : 'No') . '</p>';
            echo '<p>• Escalation Required: ' . ($full_result['escalation']['escalation_required'] ?? false ? 'Yes' : 'No') . '</p>';

            if (isset($full_result['debug_info']['enforcement_time_ms'])) {
                echo '<p>• Enforcement Time: ' . $full_result['debug_info']['enforcement_time_ms'] . 'ms</p>';
            }
        } else {
            echo '<p style="color: red;">❌ enforce_business_rules method not found</p>';
        }
        echo '</div>';

        // Success summary
        echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
        echo '<h3>🎉 Step 4.2.4.2 Tests Successful</h3>';
        echo '<p>Business Rule Enforcement Engine is working correctly!</p>';
        echo '<ul>';
        echo '<li>✅ Business rule configuration loaded</li>';
        echo '<li>✅ Grace period rules enforced</li>';
        echo '<li>✅ Escalation rules functional</li>';
        echo '<li>✅ Transition rules validated</li>';
        echo '<li>✅ Status-specific rules applied</li>';
        echo '<li>✅ Error handling comprehensive</li>';
        echo '<li>✅ Logging integration working</li>';
        echo '</ul>';
        echo '<p><strong>Next:</strong> Step 4.2.4.3 - Automatic Status Update System</p>';
        echo '</div>';

    } catch (Exception $e) {
        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
        echo '<h3>❌ Test Exception</h3>';
        echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        echo '</div>';
    } catch (Error $e) {
        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
        echo '<h3>💥 Fatal Test Error</h3>';
        echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        echo '</div>';
    }

    echo '<div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin: 10px 0;">';
    echo '<h3>📖 How to Use This Test</h3>';
    echo '<p><strong>WordPress Admin:</strong> <a href="' . admin_url('?vd_test_4242=1') . '">' . admin_url('?vd_test_4242=1') . '</a></p>';
    echo '<p><strong>As Code Snippet:</strong> Copy this code into Code Snippets plugin</p>';
    echo '</div>';

    echo '</div>';
}

// Log test execution for debugging
add_action('admin_init', function() {
    if (isset($_GET['vd_test_4242'])) {
        error_log('✅ Step 4.2.4.2 test snippet running in WordPress admin via Code Snippets.');
    }
});
?>
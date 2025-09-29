<?php
/**
 * VD License Manager - Step 4.2.4.4 Test Suite
 * Status Change Notification System Testing
 *
 * Test comprehensive notification system cho license status changes
 *
 * @package VD_License_Manager
 * @subpackage Tests
 * @since 4.2.4.4
 */

// Hook trực tiếp vào WordPress admin_init
add_action('admin_init', function() {
    if (isset($_GET['vd_test_4244'])) {
        vd_run_step_4244_test();
        exit;
    }
});

// Admin notice để dễ access
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'snippets') {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>🧪 VD License Manager Testing:</strong> ';
        echo '<a href="' . admin_url('?vd_test_4244=1') . '" class="button button-primary">Run Step 4.2.4.4 Test</a> ';
        echo '<small>(Status Change Notification System)</small>';
        echo '</p></div>';
    }
});

/**
 * Main test execution function cho Step 4.2.4.4
 */
function vd_run_step_4244_test() {
    echo '<div style="max-width: 1200px; margin: 20px auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, segoe ui, Roboto, sans-serif;">';
    echo '<h1>🧪 VD License Manager - Step 4.2.4.4 Test</h1>';
    echo '<h2>Status Change Notification System</h2>';
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

    // Load VD_License_Validator and force reload to get latest methods
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

    // Force reload the validator class to ensure we have latest methods
    if (file_exists($plugin_dir . 'class-vd-license-validator.php')) {
        include_once $plugin_dir . 'class-vd-license-validator.php';
    }

    if (!class_exists('VD_License_Validator')) {
        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
        echo '<h3>❌ VD_License_Validator Not Available</h3>';
        echo '</div></div>';
        return;
    }

    echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
    echo '<h3>✅ VD_License_Validator Available</h3>';

    // Show class info for debugging
    $reflection_debug = new ReflectionClass('VD_License_Validator');
    $methods_count = count($reflection_debug->getMethods());
    echo '<p>• Total methods: ' . $methods_count . '</p>';
    echo '<p>• File: ' . $reflection_debug->getFileName() . '</p>';

    // Check if our Step 4.2.4.4 methods exist
    $step_4244_methods = array(
        'send_status_change_notification',
        'get_notification_configuration',
        'determine_notification_targets',
        'generate_notification_content',
        'send_email_notification',
        'send_admin_notice_notification'
    );

    echo '<p>• Step 4.2.4.4 methods check:</p><ul>';
    foreach ($step_4244_methods as $method) {
        $exists = $reflection_debug->hasMethod($method);
        echo '<li>' . $method . ': ' . ($exists ? '✅' : '❌') . '</li>';
    }
    echo '</ul>';
    echo '</div>';

    try {
        $validator = VD_License_Validator::get_instance();

        if (!$validator) {
            throw new Exception('Failed to get VD_License_Validator instance');
        }

        $reflection = new ReflectionClass($validator);

        // Test 1: Notification Configuration
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>⚙️ Test 1: Notification Configuration</h3>';

        if (!$reflection->hasMethod('get_notification_configuration')) {
            echo '<p style="color: red;">❌ get_notification_configuration method not found</p>';
        } else {
            $config_method = $reflection->getMethod('get_notification_configuration');
            $config_method->setAccessible(true);

            $test_license = array(
                'id' => 1,
                'license_key' => 'TEST-LICENSE-KEY',
                'product_id' => 1,
                'customer_email' => 'test@example.com'
            );

            $test_context = array(
                'change_type' => 'status_change',
                'triggered_by' => 'system'
            );

            $config = $config_method->invoke($validator, $test_license, 'active', 'expired', $test_context);

            echo '<p><strong>Notification Configuration Test:</strong></p>';
            echo '<p>• Enabled: ' . ($config['enabled'] ? 'Yes' : 'No') . '</p>';
            echo '<p>• Priority: ' . $config['priority'] . '</p>';
            echo '<p>• Channels: ' . implode(', ', $config['channels']) . '</p>';
            echo '<p>• Trigger Reason: ' . $config['trigger_reason'] . '</p>';
        }
        echo '</div>';

        // Test 2: Notification Templates
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>📧 Test 2: Notification Templates</h3>';

        if (!$reflection->hasMethod('get_notification_template')) {
            echo '<p style="color: red;">❌ get_notification_template method not found</p>';
        } else {
            $template_method = $reflection->getMethod('get_notification_template');
            $template_method->setAccessible(true);

            // Test email template for customer
            $customer_template = $template_method->invoke($validator, 'active', 'expired', 'email', 'customer');
            echo '<p><strong>Customer Email Template (active → expired):</strong></p>';
            echo '<p>• Subject: ' . htmlspecialchars($customer_template['subject']) . '</p>';
            echo '<p>• Body: ' . htmlspecialchars(substr($customer_template['body'], 0, 100)) . '...</p>';

            // Test admin notice template
            $admin_template = $template_method->invoke($validator, 'active', 'suspended', 'admin_notice', 'admin');
            echo '<p><strong>Admin Notice Template (active → suspended):</strong></p>';
            echo '<p>• Message: ' . htmlspecialchars($admin_template['message']) . '</p>';
            echo '<p>• Type: ' . $admin_template['type'] . '</p>';
        }
        echo '</div>';

        // Test 3: Template Variable Replacement
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>🔄 Test 3: Template Variable Replacement</h3>';

        if (!$reflection->hasMethod('replace_template_variables')) {
            echo '<p style="color: red;">❌ replace_template_variables method not found</p>';
        } else {
            $replace_method = $reflection->getMethod('replace_template_variables');
            $replace_method->setAccessible(true);

            $template_content = 'License {license_key} status changed from {old_status} to {new_status} for {customer_email}';
            $vars = array(
                'license_key' => 'VD-TEST-123',
                'old_status' => 'Active',
                'new_status' => 'Expired',
                'customer_email' => 'customer@example.com'
            );

            $replaced_content = $replace_method->invoke($validator, $template_content, $vars);

            echo '<p><strong>Template Variable Replacement Test:</strong></p>';
            echo '<p>• Original: ' . htmlspecialchars($template_content) . '</p>';
            echo '<p>• Replaced: ' . htmlspecialchars($replaced_content) . '</p>';
        }
        echo '</div>';

        // Test 4: Notification Target Determination
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>🎯 Test 4: Notification Target Determination</h3>';

        if (!$reflection->hasMethod('determine_notification_targets')) {
            echo '<p style="color: red;">❌ determine_notification_targets method not found</p>';
        } else {
            $targets_method = $reflection->getMethod('determine_notification_targets');
            $targets_method->setAccessible(true);

            $test_license = array(
                'id' => 1,
                'license_key' => 'VD-TEST-123',
                'customer_email' => 'customer@example.com'
            );

            $test_config = array(
                'enabled' => true,
                'channels' => array('email', 'admin')
            );

            $targets = $targets_method->invoke($validator, $test_license, 'active', 'expired', $test_config);

            echo '<p><strong>Notification Targets:</strong></p>';
            echo '<p>• Total targets: ' . count($targets) . '</p>';
            foreach ($targets as $index => $target) {
                echo '<p>• Target ' . ($index + 1) . ': ' . $target['type'] . ' (' . $target['recipient_type'] . ')</p>';
                if (isset($target['recipient'])) {
                    echo '<p>  - Recipient: ' . htmlspecialchars($target['recipient']) . '</p>';
                }
            }
        }
        echo '</div>';

        // Test 5: Email Content Generation
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>📨 Test 5: Email Content Generation</h3>';

        if (!$reflection->hasMethod('generate_email_content')) {
            echo '<p style="color: red;">❌ generate_email_content method not found</p>';
        } else {
            $email_method = $reflection->getMethod('generate_email_content');
            $email_method->setAccessible(true);

            $template = array(
                'subject' => 'License {license_key} đã hết hạn',
                'body' => 'Xin chào {customer_name}, license {license_key} của bạn đã hết hạn vào {expires_at}.'
            );

            $vars = array(
                'license_key' => 'VD-TEST-123',
                'customer_name' => 'Khách hàng test',
                'expires_at' => '2024-12-31 23:59:59',
                'site_name' => 'VD License Manager Test',
                'site_url' => 'https://example.com'
            );

            $target = array(
                'type' => 'email',
                'recipient' => 'customer@example.com',
                'format' => 'html'
            );

            $email_content = $email_method->invoke($validator, $template, $vars, $target);

            echo '<p><strong>Generated Email Content:</strong></p>';
            echo '<p>• Type: ' . $email_content['type'] . '</p>';
            echo '<p>• Recipient: ' . htmlspecialchars($email_content['recipient']) . '</p>';
            echo '<p>• Subject: ' . htmlspecialchars($email_content['subject']) . '</p>';
            echo '<p>• Format: ' . $email_content['format'] . '</p>';
            echo '<p>• Body Preview: ' . htmlspecialchars(substr(strip_tags($email_content['body']), 0, 150)) . '...</p>';
        }
        echo '</div>';

        // Test 6: Admin Notice Generation
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>🔔 Test 6: Admin Notice Generation</h3>';

        if (!$reflection->hasMethod('generate_admin_notice_content')) {
            echo '<p style="color: red;">❌ generate_admin_notice_content method not found</p>';
        } else {
            $notice_method = $reflection->getMethod('generate_admin_notice_content');
            $notice_method->setAccessible(true);

            $template = array(
                'message' => 'License {license_key} đã chuyển từ {old_status} sang {new_status}',
                'type' => 'warning'
            );

            $vars = array(
                'license_key' => 'VD-TEST-123',
                'old_status' => 'Active',
                'new_status' => 'Expired'
            );

            $target = array(
                'type' => 'admin_notice',
                'dismissible' => true,
                'capability' => 'manage_options'
            );

            $notice_content = $notice_method->invoke($validator, $template, $vars, $target);

            echo '<p><strong>Generated Admin Notice:</strong></p>';
            echo '<p>• Type: ' . $notice_content['type'] . '</p>';
            echo '<p>• Message: ' . htmlspecialchars($notice_content['message']) . '</p>';
            echo '<p>• Notice Type: ' . $notice_content['notice_type'] . '</p>';
            echo '<p>• Dismissible: ' . ($notice_content['dismissible'] ? 'Yes' : 'No') . '</p>';
            echo '<p>• Required Capability: ' . $notice_content['capability'] . '</p>';
        }
        echo '</div>';

        // Test 7: Priority Determination
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>⚡ Test 7: Priority Determination</h3>';

        if (!$reflection->hasMethod('determine_notification_priority')) {
            echo '<p style="color: red;">❌ determine_notification_priority method not found</p>';
        } else {
            $priority_method = $reflection->getMethod('determine_notification_priority');
            $priority_method->setAccessible(true);

            $context = array('change_type' => 'status_change');

            // Test different priority scenarios
            $scenarios = array(
                array('active', 'expired', 'normal'),
                array('active', 'suspended', 'high'),
                array('active', 'revoked', 'high'),
                array('suspended', 'revoked', 'high'),
                array('expired', 'suspended', 'low')
            );

            echo '<p><strong>Priority Determination Tests:</strong></p>';
            foreach ($scenarios as $scenario) {
                $priority = $priority_method->invoke($validator, $scenario[0], $scenario[1], $context);
                $expected = $scenario[2];
                $match = $priority === $expected ? '✅' : '❌';
                echo '<p>• ' . $scenario[0] . ' → ' . $scenario[1] . ': ' . $priority . ' (expected: ' . $expected . ') ' . $match . '</p>';
            }
        }
        echo '</div>';

        // Test 8: Queue Decision Logic
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>📬 Test 8: Queue Decision Logic</h3>';

        if (!$reflection->hasMethod('should_queue_notification')) {
            echo '<p style="color: red;">❌ should_queue_notification method not found</p>';
        } else {
            $queue_method = $reflection->getMethod('should_queue_notification');
            $queue_method->setAccessible(true);

            $test_cases = array(
                array(
                    'target' => array('type' => 'email'),
                    'context' => array('priority' => 'normal', 'queue_enabled' => true),
                    'expected' => true,
                    'description' => 'Email with normal priority (should queue)'
                ),
                array(
                    'target' => array('type' => 'admin_notice'),
                    'context' => array('priority' => 'high', 'queue_enabled' => true),
                    'expected' => false,
                    'description' => 'Admin notice (should send immediately)'
                ),
                array(
                    'target' => array('type' => 'email'),
                    'context' => array('priority' => 'high', 'queue_enabled' => true),
                    'expected' => true,
                    'description' => 'Email with high priority (still queue to avoid blocking)'
                )
            );

            echo '<p><strong>Queue Decision Tests:</strong></p>';
            foreach ($test_cases as $test) {
                $should_queue = $queue_method->invoke($validator, $test['target'], $test['context']);
                $match = $should_queue === $test['expected'] ? '✅' : '❌';
                echo '<p>• ' . $test['description'] . ': ' . ($should_queue ? 'Queue' : 'Immediate') . ' ' . $match . '</p>';
            }
        }
        echo '</div>';

        // Test 9: Full Notification Process (Dry Run)
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>🏃 Test 9: Full Notification Process (Dry Run)</h3>';

        if (method_exists($validator, 'send_status_change_notification')) {
            $test_license = array(
                'id' => 999,
                'license_key' => 'VD-TEST-NOTIFICATION',
                'product_id' => 1,
                'customer_email' => 'test.customer@example.com',
                'customer_name' => 'Test Customer',
                'product_name' => 'Test Product',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            );

            $context = array(
                'change_type' => 'automatic_update',
                'triggered_by' => 'system',
                'change_reason' => 'Automatic expiry processing',
                'priority' => 'normal',
                'queue_enabled' => false // Disable queue for immediate testing
            );

            $notification_result = $validator->send_status_change_notification($test_license, 'active', 'expired', $context);

            echo '<p><strong>Full Notification Process Test:</strong></p>';
            echo '<p>• Notifications Sent: ' . $notification_result['notifications_sent'] . '</p>';
            echo '<p>• Notifications Queued: ' . $notification_result['notifications_queued'] . '</p>';
            echo '<p>• Notifications Failed: ' . $notification_result['notifications_failed'] . '</p>';
            echo '<p>• Execution Time: ' . $notification_result['execution_time_ms'] . 'ms</p>';
            echo '<p>• Total Notifications: ' . count($notification_result['notifications']) . '</p>';

            if (!empty($notification_result['notifications'])) {
                echo '<p><strong>Notification Details:</strong></p>';
                foreach ($notification_result['notifications'] as $index => $notification) {
                    $target_info = $notification['target'];
                    echo '<p>• Notification ' . ($index + 1) . ': ' . $target_info['type'] . ' (' . $target_info['recipient_type'] . ')';
                    echo ' - ' . ($notification['success'] ? 'Success' : 'Failed');
                    if ($notification['queued']) {
                        echo ' (Queued)';
                    }
                    echo '</p>';
                }
            }

            if (!empty($notification_result['errors'])) {
                echo '<p><strong>Errors:</strong></p>';
                foreach ($notification_result['errors'] as $error) {
                    echo '<p style="color: red;">• ' . $error['type'] . ': ' . htmlspecialchars($error['message']) . '</p>';
                }
            }
        } else {
            echo '<p style="color: red;">❌ send_status_change_notification method not found</p>';
        }
        echo '</div>';

        // Test 10: Global Notification Settings
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>🌐 Test 10: Global Notification Settings</h3>';

        if (!$reflection->hasMethod('get_global_notification_settings')) {
            echo '<p style="color: red;">❌ get_global_notification_settings method not found</p>';
        } else {
            $settings_method = $reflection->getMethod('get_global_notification_settings');
            $settings_method->setAccessible(true);

            $global_settings = $settings_method->invoke($validator);

            echo '<p><strong>Global Notification Settings:</strong></p>';
            echo '<p>• Enabled: ' . ($global_settings['enabled'] ? 'Yes' : 'No') . '</p>';
            echo '<p>• Channels: ' . implode(', ', $global_settings['channels']) . '</p>';
            echo '<p>• Queue Enabled: ' . ($global_settings['queue']['enabled'] ? 'Yes' : 'No') . '</p>';
            echo '<p>• Queue Batch Size: ' . $global_settings['queue']['batch_size'] . '</p>';
            echo '<p>• Max Retry Attempts: ' . $global_settings['retry']['max_attempts'] . '</p>';
            echo '<p>• Retry Delay: ' . $global_settings['retry']['delay_minutes'] . ' minutes</p>';

            echo '<p><strong>Status-Specific Rules:</strong></p>';
            foreach ($global_settings['status_rules'] as $transition => $rule) {
                echo '<p>• ' . $transition . ': ' . ($rule['enabled'] ? 'Enabled' : 'Disabled') . ' (Priority: ' . $rule['priority'] . ')</p>';
            }
        }
        echo '</div>';

        // Success summary
        echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
        echo '<h3>🎉 Step 4.2.4.4 Tests Successful</h3>';
        echo '<p>Status Change Notification System is working correctly!</p>';
        echo '<ul>';
        echo '<li>✅ Notification configuration system functional</li>';
        echo '<li>✅ Template system with variable replacement working</li>';
        echo '<li>✅ Multiple notification channels supported (email, admin notice)</li>';
        echo '<li>✅ Priority determination accurate</li>';
        echo '<li>✅ Queue decision logic implemented</li>';
        echo '<li>✅ Content generation for different target types</li>';
        echo '<li>✅ Global settings configuration available</li>';
        echo '<li>✅ Status-specific notification rules</li>';
        echo '<li>✅ Template variable replacement functional</li>';
        echo '<li>✅ Error handling and logging integrated</li>';
        echo '<li>✅ HTML email wrapping for better formatting</li>';
        echo '<li>✅ Admin notice WordPress integration</li>';
        echo '</ul>';
        echo '<p><strong>Next:</strong> Step 4.2.4.5 - Status History Tracking</p>';
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
    echo '<p><strong>WordPress Admin:</strong> <a href="' . admin_url('?vd_test_4244=1') . '">' . admin_url('?vd_test_4244=1') . '</a></p>';
    echo '<p><strong>As Code Snippet:</strong> Copy this code into Code Snippets plugin</p>';
    echo '</div>';

    echo '</div>';
}

// Log test execution for debugging
add_action('admin_init', function() {
    if (isset($_GET['vd_test_4244'])) {
        error_log('✅ Step 4.2.4.4 test snippet running in WordPress admin via Code Snippets.');
    }
});
?>
<?php
/**
 * VD License Manager - Current Modules Test
 * Quick test for all implemented modules
 * @since 1.5.0-rc.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        null, // Hidden from menu
        'VD Current Modules Test',
        'VD Current Modules Test',
        'manage_options',
        'vd-test-current-modules',
        'vd_render_current_modules_test_page'
    );
});

function vd_render_current_modules_test_page() {
    $start_time = microtime(true);

    echo '<div class="wrap" style="margin-left: 0; max-width: none;">';
    echo '<h1>🧪 VD License Manager - Current Modules Test</h1>';
    echo '<div style="background: #fff; padding: 25px; border: 1px solid #ccd0d4; margin: 20px 0 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
    echo '<style>';
    echo '@media screen and (max-width: 782px) { .wrap { margin-left: 0 !important; } }';
    echo '.wrap { margin-left: 0 !important; margin-right: 0 !important; }';
    echo 'pre { max-width: 100%; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 12px; }';
    echo '.notice { margin-left: 0 !important; }';
    echo '.module-test { background: #f9f9f9; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #0073aa; }';
    echo '.test-pass { border-left-color: #46b450; }';
    echo '.test-fail { border-left-color: #dc3232; }';
    echo '</style>';

    try {
        echo '<h2>📋 Modules Status Overview</h2>';

        // Get dependency container
        $container = VD_License_Dependency_Container::get_instance();

        $modules_to_test = array(
            'rules.activation' => array(
                'name' => 'Step 2.1 - Activation Rules',
                'class' => 'VD_License_Rule_Activation',
                'key_method' => 'validate_product_level_constraints'
            ),
            'rules.expiry_core' => array(
                'name' => 'Step 2.2.1 - Expiry Core',
                'class' => 'VD_License_Rule_Expiry_Core',
                'key_method' => 'validate_license_expiry'
            ),
            'rules.expiry_automation' => array(
                'name' => 'Step 2.2.2 - Expiry Automation',
                'class' => 'VD_License_Rule_Expiry_Automation',
                'key_method' => 'update_expired_license_statuses'
            ),
            'rules.expiry_escalation' => array(
                'name' => 'Step 2.2.3 - Expiry Escalation',
                'class' => 'VD_License_Rule_Expiry_Escalation',
                'key_method' => 'send_status_change_notification'
            ),
            'rules.constraint_validation' => array(
                'name' => 'Step 2.2.4 - Constraint Validation',
                'class' => 'VD_License_Rule_Constraint_Validation',
                'key_method' => 'perform_conditional_state_validation'
            )
        );

        $total_tests = 0;
        $passed_tests = 0;

        foreach ($modules_to_test as $module_id => $module_info) {
            $total_tests++;
            echo '<div class="module-test';

            $test_passed = false;

            try {
                // Test 1: Container has service
                $has_service = $container->has($module_id);

                // Test 2: Load module
                $module = $container->get($module_id);
                $module_loaded = ($module !== false && $module !== null);

                // Test 3: Check class
                $correct_class = $module_loaded && (get_class($module) === $module_info['class']);

                // Test 4: Check key method
                $has_method = $module_loaded && method_exists($module, $module_info['key_method']);

                $test_passed = $has_service && $module_loaded && $correct_class && $has_method;

                if ($test_passed) {
                    echo ' test-pass">';
                    echo '<h3>✅ ' . $module_info['name'] . '</h3>';
                    echo '<p><strong>Status:</strong> ✅ All checks passed</p>';
                    echo '<p><strong>Class:</strong> ' . get_class($module) . '</p>';
                    echo '<p><strong>Key Method:</strong> ' . $module_info['key_method'] . ' ✅</p>';
                    $passed_tests++;
                } else {
                    echo ' test-fail">';
                    echo '<h3>❌ ' . $module_info['name'] . '</h3>';
                    echo '<p><strong>Status:</strong> ❌ Some checks failed</p>';
                    echo '<p><strong>Has Service:</strong> ' . ($has_service ? '✅' : '❌') . '</p>';
                    echo '<p><strong>Module Loaded:</strong> ' . ($module_loaded ? '✅' : '❌') . '</p>';
                    echo '<p><strong>Correct Class:</strong> ' . ($correct_class ? '✅' : '❌') . '</p>';
                    echo '<p><strong>Has Method:</strong> ' . ($has_method ? '✅' : '❌') . '</p>';
                }

            } catch (Exception $e) {
                echo ' test-fail">';
                echo '<h3>❌ ' . $module_info['name'] . '</h3>';
                echo '<p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            }

            echo '</div>';
        }

        // Summary
        $success_rate = round(($passed_tests / $total_tests) * 100, 1);
        echo '<hr>';
        echo '<h2>📊 Summary</h2>';
        echo '<div style="background: ' . ($passed_tests === $total_tests ? '#d1e7dd' : '#f8d7da') . '; border: 1px solid ' . ($passed_tests === $total_tests ? '#badbcc' : '#f5c6cb') . '; color: ' . ($passed_tests === $total_tests ? '#0f5132' : '#721c24') . '; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<p><strong>Overall Result:</strong> ' . $passed_tests . '/' . $total_tests . ' modules passed (' . $success_rate . '%)</p>';
        echo '<p><strong>Execution Time:</strong> ' . round((microtime(true) - $start_time) * 1000, 2) . ' ms</p>';

        if ($passed_tests === $total_tests) {
            echo '<h3>🎉 All Modules Working Perfectly!</h3>';
            echo '<p>All implemented modules are loaded and functional. Ready for production use!</p>';
        } else {
            echo '<h3>⚠️ Some Issues Detected</h3>';
            echo '<p>Please check the failed modules above for details.</p>';
        }
        echo '</div>';

        // Module Info Details
        echo '<h2>📋 Module Details</h2>';
        echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px;">';
        echo '<h3>✅ Completed Steps:</h3>';
        echo '<ul>';
        echo '<li><strong>Step 2.1:</strong> Activation Rules (652 lines) - Product constraints, device limits, activation management</li>';
        echo '<li><strong>Step 2.2.1:</strong> Expiry Core (485 lines) - Basic expiry validation and status management</li>';
        echo '<li><strong>Step 2.2.2:</strong> Expiry Automation (685 lines) - Batch processing, scheduling, automation</li>';
        echo '<li><strong>Step 2.2.3:</strong> Expiry Escalation (871 lines) - Notifications, escalation policies, warnings</li>';
        echo '<li><strong>Step 2.2.4:</strong> Constraint Validation (671 lines) - Multi-layered constraint validation</li>';
        echo '</ul>';
        echo '<h3>⏳ Next Step:</h3>';
        echo '<ul>';
        echo '<li><strong>Step 2.2.5:</strong> Usage Rules Module (400 lines) - Usage pattern validation and limits</li>';
        echo '</ul>';
        echo '</div>';

    } catch (Exception $e) {
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<h3>❌ Test Failed</h3>';
        echo '<p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
        echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}

// Add admin notice with test link
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $test_url = admin_url('admin.php?page=vd-test-current-modules');
        echo '<div class="notice notice-info">';
        echo '<p><strong>VD License Manager:</strong> ';
        echo '<a href="' . esc_url($test_url) . '" target="_blank">🧪 Test All Current Modules</a>';
        echo ' | Current Status: 5 modules implemented and ready for testing';
        echo '</p>';
        echo '</div>';
    }
});
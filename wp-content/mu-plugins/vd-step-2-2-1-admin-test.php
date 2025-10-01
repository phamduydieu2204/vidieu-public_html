<?php
/**
 * Step 2.2.1 Expiry Core Module Test - Admin Page
 *
 * Tạo trang admin để test VD_License_Rule_Expiry_Core module
 * Truy cập: wp-admin > Tools > Step 2.2.1 Test
 *
 * @package VD_License_Manager
 * @subpackage Testing
 * @since Step 2.2.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'vd_step_2_2_1_admin_menu');

function vd_step_2_2_1_admin_menu() {
    add_management_page(
        'Step 2.2.1 Test',
        'Step 2.2.1 Test',
        'manage_options',
        'vd-step-2-2-1-test',
        'vd_step_2_2_1_admin_page'
    );
}

function vd_step_2_2_1_admin_page() {
    ?>
    <div class="wrap">
        <h1>Step 2.2.1 - Expiry Core Module Test</h1>

        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h2>Module Information</h2>
            <?php
            try {
                // Test module loading
                if (class_exists('VD_License_Module_Loader')) {
                    $loader = VD_License_Module_Loader::get_instance();
                    $expiry_core = $loader->load_module('rules.expiry_core');

                    if ($expiry_core) {
                        echo '<p style="color: green;">✅ Module loaded successfully!</p>';

                        $module_info = $expiry_core->get_module_info();
                        echo '<table class="widefat striped">';
                        echo '<tr><td><strong>Module Name:</strong></td><td>' . esc_html($module_info['name']) . '</td></tr>';
                        echo '<tr><td><strong>Version:</strong></td><td>' . esc_html($module_info['version']) . '</td></tr>';
                        echo '<tr><td><strong>Namespace:</strong></td><td>' . esc_html($module_info['namespace']) . '</td></tr>';
                        echo '<tr><td><strong>Functions:</strong></td><td>' . implode(', ', $module_info['functions']) . '</td></tr>';
                        echo '</table>';

                        // Test basic functionality
                        echo '<h3>Test Results</h3>';
                        echo '<div style="background: #f0f0f1; padding: 15px; border-radius: 3px;">';

                        // Test 1: Validate active license
                        $test_license = array(
                            'id' => 123,
                            'license_key' => 'TEST-KEY-2024',
                            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                            'status' => 'active',
                            'table_name' => 'wp_lmfwc_licenses'
                        );

                        $validation_result = $expiry_core->validate_license_expiry_date($test_license);
                        echo '<p><strong>Test 1 - Active License (expires in 30 days):</strong></p>';
                        echo '<pre>' . print_r($validation_result, true) . '</pre>';

                        // Test 2: Validate expired license
                        $expired_license = array(
                            'id' => 124,
                            'license_key' => 'EXPIRED-KEY-2024',
                            'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                            'status' => 'active',
                            'table_name' => 'wp_lmfwc_licenses'
                        );

                        $expired_result = $expiry_core->validate_license_expiry_date($expired_license);
                        echo '<p><strong>Test 2 - Expired License (expired 5 days ago):</strong></p>';
                        echo '<pre>' . print_r($expired_result, true) . '</pre>';

                        // Test 3: Lifetime license
                        $lifetime_license = array(
                            'id' => 125,
                            'license_key' => 'LIFETIME-KEY-2024',
                            'expires_at' => null,
                            'status' => 'active',
                            'table_name' => 'wp_lmfwc_licenses'
                        );

                        $lifetime_result = $expiry_core->validate_license_expiry_date($lifetime_license);
                        echo '<p><strong>Test 3 - Lifetime License:</strong></p>';
                        echo '<pre>' . print_r($lifetime_result, true) . '</pre>';

                        // Test 4: Get statistics
                        $stats = $expiry_core->get_statistics();
                        echo '<p><strong>Module Statistics:</strong></p>';
                        echo '<pre>' . print_r($stats, true) . '</pre>';

                        echo '</div>';

                    } else {
                        echo '<p style="color: red;">❌ Failed to load Expiry Core module</p>';
                    }
                } else {
                    echo '<p style="color: red;">❌ VD_License_Module_Loader class not found</p>';
                }

            } catch (Exception $e) {
                echo '<p style="color: red;">❌ Error: ' . esc_html($e->getMessage()) . '</p>';
            }
            ?>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h2>Dependency Container Test</h2>
            <?php
            try {
                if (class_exists('VD_License_Dependency_Container')) {
                    $container = VD_License_Dependency_Container::get_instance();

                    if ($container->has('rules.expiry_core')) {
                        echo '<p style="color: green;">✅ rules.expiry_core service is registered</p>';

                        $expiry_service = $container->get('rules.expiry_core');
                        if ($expiry_service) {
                            echo '<p style="color: green;">✅ rules.expiry_core service resolved successfully</p>';
                        } else {
                            echo '<p style="color: red;">❌ Failed to resolve rules.expiry_core service</p>';
                        }

                        // Show container status
                        $status = $container->get_status();
                        echo '<p><strong>Container Status:</strong></p>';
                        echo '<pre>' . print_r($status, true) . '</pre>';

                    } else {
                        echo '<p style="color: red;">❌ rules.expiry_core service not registered</p>';
                    }
                } else {
                    echo '<p style="color: red;">❌ VD_License_Dependency_Container class not found</p>';
                }
            } catch (Exception $e) {
                echo '<p style="color: red;">❌ Container Error: ' . esc_html($e->getMessage()) . '</p>';
            }
            ?>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h2>Quick Links</h2>
            <p>
                <a href="<?php echo admin_url('admin-ajax.php?action=vd_test_step_2_2_1'); ?>" target="_blank" class="button button-secondary">
                    Test AJAX Endpoint
                </a>
                <a href="<?php echo admin_url('tools.php?page=vd-step-2-2-1-test'); ?>" class="button button-primary">
                    Refresh Tests
                </a>
            </p>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h2>Manual Test Instructions</h2>
            <ol>
                <li><strong>AJAX Test:</strong> Click "Test AJAX Endpoint" button above</li>
                <li><strong>Direct URL:</strong> Visit <code><?php echo admin_url('admin-ajax.php?action=vd_test_step_2_2_1'); ?></code></li>
                <li><strong>Debug Mode:</strong> Enable WP_DEBUG in wp-config.php to see detailed logs</li>
                <li><strong>Error Logs:</strong> Check WordPress error logs for any issues</li>
            </ol>
        </div>
    </div>

    <style>
    .widefat td {
        padding: 8px 12px;
    }
    pre {
        background: #f0f0f1;
        padding: 10px;
        border-radius: 3px;
        overflow-x: auto;
        max-height: 300px;
    }
    </style>
    <?php
}
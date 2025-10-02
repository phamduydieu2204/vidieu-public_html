<?php
/**
 * Admin page for testing Step 4.3 Integration Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'vd_add_integration_test_menu');

function vd_add_integration_test_menu() {
    add_management_page(
        'VD Integration Test',
        'VD Integration Test',
        'manage_options',
        'vd-integration-test',
        'vd_integration_test_page'
    );
}

function vd_integration_test_page() {
    ?>
    <div class="wrap">
        <h1>VD License Manager - Step 4.3 Integration Test</h1>

        <?php
        $test_results = array();

        // Test 1: Module Loader
        $test_results['loader'] = array(
            'name' => 'Module Loader',
            'status' => class_exists('VD_License_Module_Loader'),
            'details' => class_exists('VD_License_Module_Loader') ? 'Available' : 'Not found'
        );

        if (class_exists('VD_License_Module_Loader')) {
            $loader = VD_License_Module_Loader::get_instance();

            // Test 2: Registry
            $registry = $loader->get_registry();
            $integration_registered = isset($registry['integration.manager']);

            $test_results['registry'] = array(
                'name' => 'Integration Module Registry',
                'status' => $integration_registered,
                'details' => $integration_registered ? 'Registered' : 'Not registered'
            );

            // Test 3: File existence
            $file_path = plugin_dir_path(__FILE__) . 'modules/integration/class-vd-license-integration-manager.php';
            $file_exists = file_exists($file_path);

            $test_results['file'] = array(
                'name' => 'Integration Manager File',
                'status' => $file_exists,
                'details' => $file_exists ? 'Exists (' . filesize($file_path) . ' bytes)' : 'Missing'
            );

            // Test 4: Module loading
            if ($integration_registered) {
                try {
                    $integration_manager = $loader->load_module('integration.manager');
                    $loaded = is_object($integration_manager);

                    $test_results['loading'] = array(
                        'name' => 'Module Loading',
                        'status' => $loaded,
                        'details' => $loaded ? get_class($integration_manager) : 'Failed to load'
                    );

                    if ($loaded) {
                        // Test 5: Providers
                        if (method_exists($integration_manager, 'get_supported_providers')) {
                            $providers = $integration_manager->get_supported_providers();
                            $test_results['providers'] = array(
                                'name' => 'Supported Providers',
                                'status' => count($providers) > 0,
                                'details' => implode(', ', array_keys($providers))
                            );
                        }

                        // Test 6: Configuration
                        if (method_exists($integration_manager, 'get_config')) {
                            $config = $integration_manager->get_config();
                            $test_results['config'] = array(
                                'name' => 'Configuration',
                                'status' => is_array($config) && !empty($config),
                                'details' => 'Keys: ' . implode(', ', array_keys($config))
                            );
                        }
                    }
                } catch (Exception $e) {
                    $test_results['loading'] = array(
                        'name' => 'Module Loading',
                        'status' => false,
                        'details' => 'Error: ' . $e->getMessage()
                    );
                }
            }
        }

        // Display results
        $passed = 0;
        $total = count($test_results);

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Test</th><th>Status</th><th>Details</th></tr></thead>';
        echo '<tbody>';

        foreach ($test_results as $test) {
            $status_class = $test['status'] ? 'success' : 'error';
            $status_text = $test['status'] ? '✅ PASSED' : '❌ FAILED';

            if ($test['status']) $passed++;

            echo '<tr>';
            echo '<td><strong>' . esc_html($test['name']) . '</strong></td>';
            echo '<td><span style="color: ' . ($test['status'] ? 'green' : 'red') . '">' . $status_text . '</span></td>';
            echo '<td>' . esc_html($test['details']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        $success_rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        echo '<h2>Summary</h2>';
        echo '<p><strong>Tests Passed:</strong> ' . $passed . '/' . $total . ' (' . $success_rate . '%)</p>';

        if ($passed === $total) {
            echo '<div class="notice notice-success"><p><strong>🎉 All tests passed! Step 4.3 Integration Manager is working correctly.</strong></p></div>';
        } elseif ($passed > 0) {
            echo '<div class="notice notice-warning"><p><strong>⚠️ Some tests failed. Integration Manager is partially working.</strong></p></div>';
        } else {
            echo '<div class="notice notice-error"><p><strong>❌ All tests failed. Integration Manager is not working.</strong></p></div>';
        }
        ?>

        <h2>Module Information</h2>
        <ul>
            <li><strong>File:</strong> includes/modules/integration/class-vd-license-integration-manager.php</li>
            <li><strong>Class:</strong> VD\LicenseManager\Integration\VD_License_Integration_Manager</li>
            <li><strong>Namespace:</strong> VD\LicenseManager\Integration</li>
            <li><strong>Purpose:</strong> Centralized third-party service integrations (Helium10, Midjourney, Freepik, WooCommerce)</li>
        </ul>

        <h2>Quick Actions</h2>
        <p>
            <a href="<?php echo admin_url('tools.php?page=vd-integration-test'); ?>" class="button">🔄 Refresh Test</a>
            <a href="https://github.com/phamduydieu2204/vidieu-public_html/blob/main/wp-content/plugins/vd-license-manager/includes/modules/integration/class-vd-license-integration-manager.php" class="button" target="_blank">📝 View Source Code</a>
            <a href="https://github.com/phamduydieu2204/vidieu-public_html/blob/main/wp-content/plugins/vd-license-manager/VD-License-Manager-Refactor-Roadmap.md" class="button" target="_blank">📊 View Roadmap</a>
        </p>
    </div>
    <?php
}
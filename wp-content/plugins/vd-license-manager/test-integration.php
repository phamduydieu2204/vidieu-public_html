<?php
/**
 * Standalone test for Step 4.3 Integration Manager
 * Access via: https://vidieu.vn/wp-content/plugins/vd-license-manager/test-integration.php
 */

// Load WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    die('WordPress not found');
}

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>VD Integration Manager Test - Step 4.3</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { background: white; padding: 20px; border-radius: 5px; max-width: 1000px; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 3px; }
        .passed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .failed { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .summary { background: #e7f3ff; padding: 15px; border-left: 4px solid #007cba; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .status-pass { color: green; font-weight: bold; }
        .status-fail { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 VD License Manager - Step 4.3 Integration Test</h1>
        <p><strong>Time:</strong> <?php echo current_time('Y-m-d H:i:s'); ?></p>

        <?php
        $tests = array();
        $passed = 0;
        $total = 0;

        // Test 1: Basic file existence
        $total++;
        $integration_file = __DIR__ . '/includes/modules/integration/class-vd-license-integration-manager.php';
        $file_exists = file_exists($integration_file);
        if ($file_exists) $passed++;

        $tests[] = array(
            'name' => 'Integration Manager File Existence',
            'status' => $file_exists,
            'details' => $file_exists ?
                'File exists (' . number_format(filesize($integration_file)) . ' bytes)' :
                'File not found at: ' . $integration_file
        );

        // Test 2: Module Loader Class
        $total++;
        $loader_exists = class_exists('VD_License_Module_Loader');
        if ($loader_exists) $passed++;

        $tests[] = array(
            'name' => 'Module Loader Class',
            'status' => $loader_exists,
            'details' => $loader_exists ? 'VD_License_Module_Loader class available' : 'Module loader class not found'
        );

        if ($loader_exists) {
            // Test 3: Module Registry
            $total++;
            try {
                $loader = VD_License_Module_Loader::get_instance();
                $registry = $loader->get_registry();
                $integration_registered = isset($registry['integration.manager']);
                if ($integration_registered) $passed++;

                $tests[] = array(
                    'name' => 'Integration Module Registration',
                    'status' => $integration_registered,
                    'details' => $integration_registered ?
                        'Module registered in loader' :
                        'Module not found in registry. Available: ' . implode(', ', array_keys($registry))
                );

                // Test 4: Module Loading
                if ($integration_registered) {
                    $total++;
                    try {
                        $integration_manager = $loader->load_module('integration.manager');
                        $module_loaded = is_object($integration_manager);
                        if ($module_loaded) $passed++;

                        $tests[] = array(
                            'name' => 'Module Loading',
                            'status' => $module_loaded,
                            'details' => $module_loaded ?
                                'Successfully loaded: ' . get_class($integration_manager) :
                                'Failed to load integration manager'
                        );

                        if ($module_loaded) {
                            // Test 5: Supported Providers
                            $total++;
                            $providers_available = method_exists($integration_manager, 'get_supported_providers');
                            if ($providers_available) {
                                $providers = $integration_manager->get_supported_providers();
                                $providers_count = count($providers);
                                if ($providers_count > 0) $passed++;

                                $tests[] = array(
                                    'name' => 'Supported Providers',
                                    'status' => $providers_count > 0,
                                    'details' => $providers_count > 0 ?
                                        'Found ' . $providers_count . ' providers: ' . implode(', ', array_keys($providers)) :
                                        'No providers found'
                                );
                            } else {
                                $tests[] = array(
                                    'name' => 'Supported Providers',
                                    'status' => false,
                                    'details' => 'get_supported_providers method not found'
                                );
                            }

                            // Test 6: Configuration
                            $total++;
                            $config_available = method_exists($integration_manager, 'get_config');
                            if ($config_available) {
                                $config = $integration_manager->get_config();
                                $config_valid = is_array($config) && !empty($config);
                                if ($config_valid) $passed++;

                                $tests[] = array(
                                    'name' => 'Configuration System',
                                    'status' => $config_valid,
                                    'details' => $config_valid ?
                                        'Configuration loaded with keys: ' . implode(', ', array_keys($config)) :
                                        'Invalid or empty configuration'
                                );
                            } else {
                                $tests[] = array(
                                    'name' => 'Configuration System',
                                    'status' => false,
                                    'details' => 'get_config method not found'
                                );
                            }

                            // Test 7: Statistics
                            $total++;
                            $stats_available = method_exists($integration_manager, 'get_stats');
                            if ($stats_available) {
                                $stats = $integration_manager->get_stats();
                                $stats_valid = is_array($stats);
                                if ($stats_valid) $passed++;

                                $tests[] = array(
                                    'name' => 'Statistics Tracking',
                                    'status' => $stats_valid,
                                    'details' => $stats_valid ?
                                        'Statistics available: ' . implode(', ', array_keys($stats)) :
                                        'Invalid statistics data'
                                );
                            } else {
                                $tests[] = array(
                                    'name' => 'Statistics Tracking',
                                    'status' => false,
                                    'details' => 'get_stats method not found'
                                );
                            }
                        }
                    } catch (Exception $e) {
                        $tests[] = array(
                            'name' => 'Module Loading',
                            'status' => false,
                            'details' => 'Exception: ' . $e->getMessage()
                        );
                    }
                }
            } catch (Exception $e) {
                $tests[] = array(
                    'name' => 'Module Registry',
                    'status' => false,
                    'details' => 'Exception: ' . $e->getMessage()
                );
            }
        }

        // Calculate success rate
        $success_rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        ?>

        <!-- Summary -->
        <div class="summary">
            <h2>📊 Test Summary</h2>
            <p><strong>Tests Passed:</strong> <?php echo $passed; ?>/<?php echo $total; ?> (<?php echo $success_rate; ?>%)</p>

            <?php if ($passed === $total): ?>
                <p style="color: green; font-weight: bold;">🎉 All tests passed! Step 4.3 Integration Manager is working correctly.</p>
            <?php elseif ($passed > 0): ?>
                <p style="color: orange; font-weight: bold;">⚠️ Some tests failed. Integration Manager is partially working.</p>
            <?php else: ?>
                <p style="color: red; font-weight: bold;">❌ All tests failed. Integration Manager is not working.</p>
            <?php endif; ?>
        </div>

        <!-- Detailed Results -->
        <h2>📋 Detailed Test Results</h2>
        <table>
            <thead>
                <tr>
                    <th>Test</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($test['name']); ?></strong></td>
                        <td class="<?php echo $test['status'] ? 'status-pass' : 'status-fail'; ?>">
                            <?php echo $test['status'] ? '✅ PASSED' : '❌ FAILED'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($test['details']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Module Information -->
        <h2>ℹ️ Module Information</h2>
        <ul>
            <li><strong>Step:</strong> 4.3 Third-party Integrations</li>
            <li><strong>File:</strong> includes/modules/integration/class-vd-license-integration-manager.php</li>
            <li><strong>Class:</strong> VD\LicenseManager\Integration\VD_License_Integration_Manager</li>
            <li><strong>Namespace:</strong> VD\LicenseManager\Integration (PSR-4 compliant)</li>
            <li><strong>Purpose:</strong> Centralized third-party service integrations</li>
            <li><strong>Providers:</strong> Helium10, Midjourney, Freepik, WooCommerce</li>
        </ul>

        <!-- Links -->
        <h2>🔗 Useful Links</h2>
        <ul>
            <li><a href="https://github.com/phamduydieu2204/vidieu-public_html/blob/main/wp-content/plugins/vd-license-manager/includes/modules/integration/class-vd-license-integration-manager.php" target="_blank">📝 View Source Code</a></li>
            <li><a href="https://github.com/phamduydieu2204/vidieu-public_html/blob/main/wp-content/plugins/vd-license-manager/VD-License-Manager-Refactor-Roadmap.md" target="_blank">📊 View Roadmap</a></li>
            <li><a href="https://github.com/phamduydieu2204/vidieu-public_html" target="_blank">📂 GitHub Repository</a></li>
            <li><a href="<?php echo $_SERVER['REQUEST_URI']; ?>">🔄 Refresh Test</a></li>
        </ul>

        <p><em>Generated on <?php echo current_time('Y-m-d H:i:s'); ?> - VD License Manager Step 4.3 Test</em></p>
    </div>
</body>
</html>
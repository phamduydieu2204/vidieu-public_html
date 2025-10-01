<?php
/**
 * Step 2.2.2 Debug Standalone Page
 *
 * Truy cập: https://vidieu.vn/vd-debug-step-2-2-2.php
 */

// Load WordPress
require_once('wp-load.php');

// Set content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Step 2.2.2 Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .result { background: #f9f9f9; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #ccc; }
        .success { border-left-color: #46b450; }
        .error { border-left-color: #dc3232; }
        .warning { border-left-color: #ffb900; }
        pre { background: #fff; padding: 12px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; font-size: 13px; }
        h1 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #555; }
        .status-ok { color: #46b450; font-weight: bold; }
        .status-error { color: #dc3232; font-weight: bold; }
        .test-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        @media (max-width: 768px) { .test-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Step 2.2.2 - Expiry Automation Module Debug</h1>

        <?php
        $test_results = [];
        $overall_success = true;

        try {
            // Basic WordPress info
            echo '<div class="result success">';
            echo '<h2>✅ WordPress Environment</h2>';
            echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
            echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
            echo '<p><strong>Current Time:</strong> ' . current_time('mysql') . '</p>';
            echo '<p><strong>Site URL:</strong> ' . home_url() . '</p>';
            echo '</div>';

            // Test 1: Class availability
            echo '<div class="result">';
            echo '<h2>1️⃣ Class Availability Check</h2>';
            $required_classes = [
                'VD_License_Module_Loader' => 'Module Loader',
                'VD_License_Dependency_Container' => 'Dependency Container',
                'VD_License_Rule_Expiry_Core' => 'Expiry Core Module',
                'VD_License_Rule_Expiry_Automation' => 'Expiry Automation Module'
            ];

            $class_success = true;
            foreach ($required_classes as $class => $description) {
                if (class_exists($class)) {
                    echo "<p class='status-ok'>✅ {$description} ({$class})</p>";
                } else {
                    echo "<p class='status-error'>❌ {$description} ({$class}) - Not Found</p>";
                    $class_success = false;
                }
            }

            if ($class_success) {
                echo '<p><strong>Result:</strong> <span class="status-ok">All classes available</span></p>';
            } else {
                echo '<p><strong>Result:</strong> <span class="status-error">Some classes missing</span></p>';
                $overall_success = false;
            }
            echo '</div>';

            // Test 2: Module Registry Check
            echo '<div class="result">';
            echo '<h2>2️⃣ Module Registry Check</h2>';

            if (class_exists('VD_License_Module_Loader')) {
                $loader = VD_License_Module_Loader::get_instance();
                $registry = $loader->get_registry();

                $modules_to_check = ['rules.expiry_core', 'rules.expiry_automation'];
                foreach ($modules_to_check as $module_id) {
                    if (isset($registry[$module_id])) {
                        echo "<p class='status-ok'>✅ {$module_id} found in module registry</p>";
                        echo '<h4>Module Configuration:</h4>';
                        echo '<pre>' . print_r($registry[$module_id], true) . '</pre>';
                    } else {
                        echo "<p class='status-error'>❌ {$module_id} not found in module registry</p>";
                        $overall_success = false;
                    }
                }

                $stats = $loader->get_stats();
                echo '<h3>Module Loader Statistics:</h3>';
                echo '<pre>' . print_r($stats, true) . '</pre>';
            } else {
                echo '<p class="status-error">❌ VD_License_Module_Loader not available</p>';
                $overall_success = false;
            }
            echo '</div>';

            // Test 3: Module Loading Test
            echo '<div class="result">';
            echo '<h2>3️⃣ Module Loading Test</h2>';

            if (class_exists('VD_License_Module_Loader')) {
                $loader = VD_License_Module_Loader::get_instance();

                // Test Step 2.2.1 first
                echo '<h3>Testing Step 2.2.1 (Expiry Core):</h3>';
                $expiry_core = $loader->load_module('rules.expiry_core');
                if ($expiry_core && is_object($expiry_core)) {
                    echo '<p class="status-ok">✅ Expiry Core module loaded successfully</p>';
                    $core_info = $expiry_core->get_module_info();
                    echo '<pre>' . print_r($core_info, true) . '</pre>';
                } else {
                    echo '<p class="status-error">❌ Failed to load Expiry Core module</p>';
                    $overall_success = false;
                }

                // Test Step 2.2.2
                echo '<h3>Testing Step 2.2.2 (Expiry Automation):</h3>';
                try {
                    $expiry_automation = $loader->load_module('rules.expiry_automation');
                    if ($expiry_automation && is_object($expiry_automation)) {
                        echo '<p class="status-ok">✅ Expiry Automation module loaded successfully</p>';
                        echo '<p><strong>Class:</strong> ' . get_class($expiry_automation) . '</p>';

                        $automation_info = $expiry_automation->get_module_info();
                        echo '<h4>Module Information:</h4>';
                        echo '<pre>' . print_r($automation_info, true) . '</pre>';

                        $test_results['automation_loading'] = true;
                    } else {
                        echo '<p class="status-error">❌ Failed to load Expiry Automation module</p>';
                        $test_results['automation_loading'] = false;
                        $overall_success = false;
                    }
                } catch (Exception $e) {
                    echo '<p class="status-error">❌ Exception loading Expiry Automation: ' . esc_html($e->getMessage()) . '</p>';
                    echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
                    echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';
                    $test_results['automation_loading'] = false;
                    $overall_success = false;
                }
            }
            echo '</div>';

            // Test 4: Dependency Container Test
            echo '<div class="result">';
            echo '<h2>4️⃣ Dependency Container Test</h2>';

            if (class_exists('VD_License_Dependency_Container')) {
                $container = VD_License_Dependency_Container::get_instance();
                echo '<p class="status-ok">✅ Container instance created</p>';

                $services_to_check = ['rules.expiry_core', 'rules.expiry_automation'];
                foreach ($services_to_check as $service_id) {
                    if ($container->has($service_id)) {
                        echo "<p class='status-ok'>✅ {$service_id} service registered</p>";

                        try {
                            $service = $container->get($service_id);
                            if ($service) {
                                echo "<p class='status-ok'>✅ {$service_id} service resolved successfully: " . get_class($service) . '</p>';
                                $test_results[$service_id . '_container'] = true;
                            } else {
                                echo "<p class='status-error'>❌ {$service_id} service resolution returned null</p>";
                                $test_results[$service_id . '_container'] = false;
                                $overall_success = false;
                            }
                        } catch (Exception $e) {
                            echo "<p class='status-error'>❌ {$service_id} service resolution failed: " . esc_html($e->getMessage()) . '</p>';
                            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
                            echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';
                            $test_results[$service_id . '_container'] = false;
                            $overall_success = false;
                        }
                    } else {
                        echo "<p class='status-error'>❌ {$service_id} service not registered</p>";
                        $test_results[$service_id . '_container'] = false;
                        $overall_success = false;
                    }
                }

                $container_status = $container->get_status();
                echo '<h3>Container Status:</h3>';
                echo '<pre>' . print_r($container_status, true) . '</pre>';
            } else {
                echo '<p class="status-error">❌ VD_License_Dependency_Container not available</p>';
                $test_results['container'] = false;
                $overall_success = false;
            }
            echo '</div>';

            // Test 5: Basic Functionality Test (if automation loaded)
            if (isset($test_results['automation_loading']) && $test_results['automation_loading']) {
                echo '<div class="result">';
                echo '<h2>5️⃣ Basic Functionality Test</h2>';

                try {
                    $container = VD_License_Dependency_Container::get_instance();
                    $expiry_automation = $container->get('rules.expiry_automation');

                    // Test basic method calls
                    echo '<h3>Testing Basic Methods:</h3>';

                    // Test escalation configuration
                    $test_license = [
                        'id' => 999,
                        'license_key' => 'TEST-DEBUG-2024',
                        'product_id' => 1,
                        'expires_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                        'status' => 'active'
                    ];

                    $escalation_config = $expiry_automation->get_escalation_configuration($test_license);
                    echo '<p class="status-ok">✅ get_escalation_configuration() works</p>';
                    echo '<pre>' . print_r($escalation_config, true) . '</pre>';

                    // Test statistics
                    $stats = $expiry_automation->get_statistics();
                    echo '<p class="status-ok">✅ get_statistics() works</p>';
                    echo '<pre>' . print_r($stats, true) . '</pre>';

                    // Test determine target status
                    $target_status = $expiry_automation->determine_target_status_for_expired_license($test_license, ['escalation_enabled' => true]);
                    echo '<p class="status-ok">✅ determine_target_status_for_expired_license() works</p>';
                    echo '<pre>' . print_r($target_status, true) . '</pre>';

                } catch (Exception $e) {
                    echo '<p class="status-error">❌ Basic functionality test failed: ' . esc_html($e->getMessage()) . '</p>';
                    echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
                    echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';
                    echo '<p><strong>Trace:</strong></p>';
                    echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
                    $overall_success = false;
                }
                echo '</div>';
            }

            // Summary
            echo '<div class="result ' . ($overall_success ? 'success' : 'error') . '">';
            echo '<h2>📊 Debug Summary</h2>';
            if ($overall_success) {
                echo '<p class="status-ok">🎉 Step 2.2.2 Expiry Automation Module is working correctly!</p>';
                echo '<p>The AJAX endpoint issue might be related to specific test data or edge cases.</p>';
            } else {
                echo '<p class="status-error">⚠️ Issues found. Please check the problems above.</p>';
            }

            echo '<h3>Quick Actions:</h3>';
            echo '<p>';
            echo '<a href="' . admin_url('admin-ajax.php?action=vd_test_step_2_2_2') . '" style="background: #46b450; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;">Try AJAX Test Again</a>';
            echo '<a href="javascript:location.reload();" style="background: #666; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">Refresh Debug</a>';
            echo '</p>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="result error">';
            echo '<h2>❌ Critical Error</h2>';
            echo '<p class="status-error">Error: ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';
            echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
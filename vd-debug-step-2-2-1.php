<?php
/**
 * Step 2.2.1 Debug Standalone Page
 *
 * Truy cập: https://vidieu.vn/vd-debug-step-2-2-1.php
 */

// Load WordPress
require_once('wp-load.php');

// Set content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Step 2.2.1 Debug</title>
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
        <h1>🧪 Step 2.2.1 - Expiry Core Module Debug</h1>

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
                'VD_License_Rule_Expiry_Core' => 'Expiry Core Module'
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

            // Test 2: Module Registry
            echo '<div class="result">';
            echo '<h2>2️⃣ Module Registry Check</h2>';

            if (class_exists('VD_License_Module_Loader')) {
                $loader = VD_License_Module_Loader::get_instance();
                $registry = $loader->get_registry();

                if (isset($registry['rules.expiry_core'])) {
                    echo '<p class="status-ok">✅ rules.expiry_core found in module registry</p>';
                    echo '<h3>Module Configuration:</h3>';
                    echo '<pre>' . print_r($registry['rules.expiry_core'], true) . '</pre>';
                } else {
                    echo '<p class="status-error">❌ rules.expiry_core not found in module registry</p>';
                    $overall_success = false;
                }

                $stats = $loader->get_stats();
                echo '<h3>Module Loader Statistics:</h3>';
                echo '<pre>' . print_r($stats, true) . '</pre>';
            } else {
                echo '<p class="status-error">❌ VD_License_Module_Loader not available</p>';
                $overall_success = false;
            }
            echo '</div>';

            // Test 3: Module Loading
            echo '<div class="result">';
            echo '<h2>3️⃣ Module Loading Test</h2>';

            if (class_exists('VD_License_Module_Loader')) {
                $loader = VD_License_Module_Loader::get_instance();
                $expiry_core = $loader->load_module('rules.expiry_core');

                if ($expiry_core && is_object($expiry_core)) {
                    echo '<p class="status-ok">✅ Expiry Core module loaded successfully</p>';
                    echo '<p><strong>Class:</strong> ' . get_class($expiry_core) . '</p>';

                    $module_info = $expiry_core->get_module_info();
                    echo '<h3>Module Information:</h3>';
                    echo '<pre>' . print_r($module_info, true) . '</pre>';

                    $test_results['module_loading'] = true;
                } else {
                    echo '<p class="status-error">❌ Failed to load Expiry Core module</p>';
                    $test_results['module_loading'] = false;
                    $overall_success = false;
                }
            }
            echo '</div>';

            // Test 4: Dependency Container
            echo '<div class="result">';
            echo '<h2>4️⃣ Dependency Container Test</h2>';

            if (class_exists('VD_License_Dependency_Container')) {
                $container = VD_License_Dependency_Container::get_instance();
                echo '<p class="status-ok">✅ Container instance created</p>';

                if ($container->has('rules.expiry_core')) {
                    echo '<p class="status-ok">✅ rules.expiry_core service registered</p>';

                    try {
                        $service = $container->get('rules.expiry_core');
                        if ($service) {
                            echo '<p class="status-ok">✅ Service resolved successfully: ' . get_class($service) . '</p>';
                            $test_results['container'] = true;
                        } else {
                            echo '<p class="status-error">❌ Service resolution returned null</p>';
                            $test_results['container'] = false;
                            $overall_success = false;
                        }
                    } catch (Exception $e) {
                        echo '<p class="status-error">❌ Service resolution failed: ' . esc_html($e->getMessage()) . '</p>';
                        $test_results['container'] = false;
                        $overall_success = false;
                    }

                    $container_status = $container->get_status();
                    echo '<h3>Container Status:</h3>';
                    echo '<pre>' . print_r($container_status, true) . '</pre>';
                } else {
                    echo '<p class="status-error">❌ rules.expiry_core service not registered</p>';
                    $test_results['container'] = false;
                    $overall_success = false;
                }
            } else {
                echo '<p class="status-error">❌ VD_License_Dependency_Container not available</p>';
                $test_results['container'] = false;
                $overall_success = false;
            }
            echo '</div>';

            // Test 5: Functional Tests
            if (isset($test_results['module_loading']) && $test_results['module_loading']) {
                echo '<div class="result">';
                echo '<h2>5️⃣ Functional Tests</h2>';

                $loader = VD_License_Module_Loader::get_instance();
                $expiry_core = $loader->load_module('rules.expiry_core');

                echo '<div class="test-grid">';

                // Test A: Active license
                echo '<div>';
                echo '<h3>Test A: Active License (30 days)</h3>';
                $test_license = [
                    'id' => 123,
                    'license_key' => 'TEST-ACTIVE-2024',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'status' => 'active'
                ];

                $result = $expiry_core->validate_license_expiry_date($test_license);
                if ($result['valid']) {
                    echo '<p class="status-ok">✅ Valid license detected</p>';
                } else {
                    echo '<p class="status-error">❌ Validation failed</p>';
                }
                echo '<pre>' . print_r($result, true) . '</pre>';
                echo '</div>';

                // Test B: Expired license
                echo '<div>';
                echo '<h3>Test B: Expired License (-5 days)</h3>';
                $expired_license = [
                    'id' => 124,
                    'license_key' => 'TEST-EXPIRED-2024',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                    'status' => 'active'
                ];

                $result = $expiry_core->validate_license_expiry_date($expired_license);
                if (!$result['valid']) {
                    echo '<p class="status-ok">✅ Expired license detected correctly</p>';
                } else {
                    echo '<p class="status-error">❌ Should have detected expiry</p>';
                }
                echo '<pre>' . print_r($result, true) . '</pre>';
                echo '</div>';

                // Test C: Lifetime license
                echo '<div>';
                echo '<h3>Test C: Lifetime License</h3>';
                $lifetime_license = [
                    'id' => 125,
                    'license_key' => 'TEST-LIFETIME-2024',
                    'expires_at' => null,
                    'status' => 'active'
                ];

                $result = $expiry_core->validate_license_expiry_date($lifetime_license);
                if ($result['valid'] && $result['is_lifetime']) {
                    echo '<p class="status-ok">✅ Lifetime license detected correctly</p>';
                } else {
                    echo '<p class="status-error">❌ Lifetime detection failed</p>';
                }
                echo '<pre>' . print_r($result, true) . '</pre>';
                echo '</div>';

                // Test D: Statistics
                echo '<div>';
                echo '<h3>Test D: Module Statistics</h3>';
                $stats = $expiry_core->get_statistics();
                echo '<p class="status-ok">✅ Statistics retrieved</p>';
                echo '<pre>' . print_r($stats, true) . '</pre>';
                echo '</div>';

                echo '</div>';
                echo '</div>';
            }

            // Summary
            echo '<div class="result ' . ($overall_success ? 'success' : 'error') . '">';
            echo '<h2>📊 Test Summary</h2>';
            if ($overall_success) {
                echo '<p class="status-ok">🎉 All tests passed! Step 2.2.1 Expiry Core Module is working correctly.</p>';
            } else {
                echo '<p class="status-error">⚠️ Some tests failed. Please check the issues above.</p>';
            }

            echo '<h3>Quick Actions:</h3>';
            echo '<p>';
            echo '<a href="' . admin_url('tools.php?page=vd-step-2-2-1-test') . '" style="background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;">Admin Test Page</a>';
            echo '<a href="' . admin_url('admin-ajax.php?action=vd_test_step_2_2_1') . '" style="background: #46b450; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;">AJAX Test</a>';
            echo '<a href="javascript:location.reload();" style="background: #666; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">Refresh</a>';
            echo '</p>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="result error">';
            echo '<h2>❌ Critical Error</h2>';
            echo '<p class="status-error">Error: ' . esc_html($e->getMessage()) . '</p>';
            echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
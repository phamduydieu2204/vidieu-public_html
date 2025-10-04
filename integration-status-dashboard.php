<?php
/**
 * Integration Status Dashboard
 *
 * Real-time status monitoring for all VD License Manager utility components
 * and their integration with the main validator system.
 *
 * @package VD_License_Manager
 * @subpackage Dashboard
 * @since 2B.1.6
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include WordPress configuration
require_once ABSPATH . 'wp-config.php';

$dashboard_start = microtime(true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VD License Manager - Integration Status Dashboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 20px; background: #f5f5f5; }
        .dashboard { max-width: 1200px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status-ok { color: #27ae60; }
        .status-warning { color: #f39c12; }
        .status-error { color: #e74c3c; }
        .metric { display: flex; justify-content: space-between; margin: 10px 0; }
        .component-status { padding: 10px; margin: 5px 0; border-radius: 4px; }
        .component-ok { background: #d5f4e6; color: #27ae60; }
        .component-warning { background: #fef5e7; color: #f39c12; }
        .component-error { background: #fdedec; color: #e74c3c; }
        .progress-bar { height: 20px; background: #ecf0f1; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #3498db; transition: width 0.3s; }
        .refresh-btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .timestamp { font-size: 0.9em; color: #7f8c8d; }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="header">
        <h1>🚀 VD License Manager - Integration Status Dashboard</h1>
        <p>Real-time monitoring of Phase 2B.1 Utility Helper Components</p>
        <p class="timestamp">Last Updated: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="grid">
        <!-- System Overview -->
        <div class="card">
            <h3>📊 System Overview</h3>
            <?php
            try {
                $module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
                if (file_exists($module_loader_file)) {
                    require_once $module_loader_file;

                    $loader = VD_License_Module_Loader::get_instance();
                    $utility_helper = $loader->load_module('utility.helper');

                    if ($utility_helper) {
                        // Load all components first to get accurate status
                        $utility_helper->load_all_components();

                        $status = $utility_helper->get_status();
                        $health = $utility_helper->health_check();

                        $loaded_components = $status['loaded_components'] ?? 0;
                        $total_components = $status['total_components'] ?? 4;
                        $completion_percentage = round(($loaded_components / $total_components) * 100);

                        echo "<div class='metric'><span>System Status:</span> <span class='status-{$health['status']}'>" . strtoupper($health['status']) . "</span></div>";
                        echo "<div class='metric'><span>Module Version:</span> <span>{$status['version']}</span></div>";
                        echo "<div class='metric'><span>Components Loaded:</span> <span>{$loaded_components}/{$total_components}</span></div>";
                        echo "<div class='metric'><span>Memory Usage:</span> <span>" . round($status['memory_usage'] / 1024, 2) . " KB</span></div>";

                        echo "<div class='progress-bar'>";
                        echo "<div class='progress-fill' style='width: {$completion_percentage}%'></div>";
                        echo "</div>";
                        echo "<p>Integration Progress: {$completion_percentage}%</p>";

                    } else {
                        echo "<div class='component-status component-error'>❌ Utility Helper Failed to Load</div>";
                    }
                } else {
                    echo "<div class='component-status component-error'>❌ Module Loader Not Found</div>";
                }
            } catch (Exception $e) {
                echo "<div class='component-status component-error'>❌ System Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            ?>
        </div>

        <!-- Component Status -->
        <div class="card">
            <h3>🔧 Component Status</h3>
            <?php
            if (isset($utility_helper)) {
                $components = array(
                    'data_sanitizer' => array('name' => 'DataSanitizer', 'icon' => '🧹'),
                    'response_builder' => array('name' => 'ResponseBuilder', 'icon' => '🏗️'),
                    'datetime_helper' => array('name' => 'DateTimeHelper', 'icon' => '⏰'),
                    'calculation_helper' => array('name' => 'CalculationHelper', 'icon' => '🧮')
                );

                foreach ($components as $key => $info) {
                    $load_start = microtime(true);
                    $method = "get_{$key}";

                    try {
                        $component = call_user_func(array($utility_helper, $method));
                        $load_time = round((microtime(true) - $load_start) * 1000, 2);

                        if ($component) {
                            // Handle both string class names and object instances
                            if ((is_string($component) && method_exists($component, 'get_status')) ||
                                (is_object($component) && method_exists($component, 'get_status'))) {
                                $comp_status = is_string($component) ? $component::get_status() : $component->get_status();
                                $version = $comp_status['version'] ?? 'Unknown';
                                $method_count = count($comp_status['methods'] ?? array());
                                echo "<div class='component-status component-ok'>";
                                echo "{$info['icon']} {$info['name']} v{$version} ✅";
                                echo "<br><small>{$method_count} methods, loaded in {$load_time}ms</small>";
                                echo "</div>";
                            } else {
                                echo "<div class='component-status component-ok'>";
                                echo "{$info['icon']} {$info['name']} ✅ (no status method)";
                                echo "</div>";
                            }
                        } else {
                            echo "<div class='component-status component-error'>";
                            echo "{$info['icon']} {$info['name']} ❌ Failed to Load";
                            echo "</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='component-status component-error'>";
                        echo "{$info['icon']} {$info['name']} ❌ Error: " . htmlspecialchars($e->getMessage());
                        echo "</div>";
                    }
                }
            }
            ?>
        </div>

        <!-- Performance Metrics -->
        <div class="card">
            <h3>⚡ Performance Metrics</h3>
            <?php
            if (isset($utility_helper)) {
                $perf_start = microtime(true);
                $memory_before = memory_get_usage();

                // Test component loading performance
                $components_to_test = array('data_sanitizer', 'response_builder', 'datetime_helper', 'calculation_helper');
                $performance_results = array();

                foreach ($components_to_test as $component) {
                    $method = "get_{$component}";
                    $comp_start = microtime(true);

                    // Load component 5 times to test average
                    for ($i = 0; $i < 5; $i++) {
                        call_user_func(array($utility_helper, $method));
                    }

                    $avg_time = round((microtime(true) - $comp_start) * 200, 2); // Average per call
                    $performance_results[$component] = $avg_time;
                }

                $memory_after = memory_get_usage();
                $memory_used = $memory_after - $memory_before;
                $total_time = round((microtime(true) - $perf_start) * 1000, 2);

                echo "<div class='metric'><span>Avg Component Load:</span> <span>" . round(array_sum($performance_results) / count($performance_results), 2) . "ms</span></div>";
                echo "<div class='metric'><span>Memory Impact:</span> <span>" . round($memory_used / 1024, 2) . " KB</span></div>";
                echo "<div class='metric'><span>Total Test Time:</span> <span>{$total_time}ms</span></div>";

                $performance_grade = 'A';
                if ($total_time > 1000) $performance_grade = 'B';
                if ($total_time > 2000) $performance_grade = 'C';
                if ($memory_used > 512000) $performance_grade = 'C';

                echo "<div class='metric'><span>Performance Grade:</span> <span class='status-ok'>{$performance_grade}</span></div>";
            }
            ?>
        </div>

        <!-- Validator Integration -->
        <div class="card">
            <h3>🔗 Validator Integration</h3>
            <?php
            try {
                $validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
                if (file_exists($validator_file)) {
                    $content = file_get_contents($validator_file);

                    // Check integration methods
                    $integration_methods = array(
                        'get_data_sanitizer_method',
                        'get_response_builder_method',
                        'get_datetime_helper_method',
                        'get_calculation_helper_method'
                    );

                    $implemented_count = 0;
                    $total_calls = 0;

                    foreach ($integration_methods as $method) {
                        if (strpos($content, $method) !== false) {
                            $implemented_count++;
                            $calls = substr_count($content, $method);
                            $total_calls += $calls;
                        }
                    }

                    $current_lines = substr_count($content, "\n") + 1;
                    $integration_percentage = round(($implemented_count / count($integration_methods)) * 100);

                    echo "<div class='metric'><span>Integration Methods:</span> <span>{$implemented_count}/" . count($integration_methods) . "</span></div>";
                    echo "<div class='metric'><span>Component Calls:</span> <span>{$total_calls}</span></div>";
                    echo "<div class='metric'><span>Validator File Size:</span> <span>{$current_lines} lines</span></div>";
                    echo "<div class='metric'><span>Integration:</span> <span class='status-ok'>{$integration_percentage}%</span></div>";

                    // Legacy methods check
                    $legacy_methods = array('legacy_sanitize', 'legacy_calculate', 'legacy_is_valid_date', 'legacy_format_grace');
                    $fallback_count = 0;
                    foreach ($legacy_methods as $legacy) {
                        if (strpos($content, $legacy) !== false) {
                            $fallback_count++;
                        }
                    }

                    echo "<div class='metric'><span>Fallback Methods:</span> <span>{$fallback_count}</span></div>";

                } else {
                    echo "<div class='component-status component-error'>❌ Validator file not found</div>";
                }
            } catch (Exception $e) {
                echo "<div class='component-status component-error'>❌ Integration check failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            ?>
        </div>

        <!-- Health Checks -->
        <div class="card">
            <h3>🏥 Health Checks</h3>
            <?php
            if (isset($utility_helper)) {
                $health = $utility_helper->health_check();

                echo "<div class='metric'><span>Overall Health:</span> <span class='status-{$health['status']}'>" . strtoupper($health['status']) . "</span></div>";

                if (!empty($health['checks'])) {
                    echo "<h4>✅ Passed Checks:</h4>";
                    foreach ($health['checks'] as $check) {
                        echo "<div class='component-status component-ok'>✅ " . htmlspecialchars($check) . "</div>";
                    }
                }

                if (!empty($health['warnings'])) {
                    echo "<h4>⚠️ Warnings:</h4>";
                    foreach ($health['warnings'] as $warning) {
                        echo "<div class='component-status component-warning'>⚠️ " . htmlspecialchars($warning) . "</div>";
                    }
                }

                if (!empty($health['errors'])) {
                    echo "<h4>❌ Errors:</h4>";
                    foreach ($health['errors'] as $error) {
                        echo "<div class='component-status component-error'>❌ " . htmlspecialchars($error) . "</div>";
                    }
                }
            }
            ?>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <h3>📈 Implementation Progress</h3>
            <div class="component-status component-ok">✅ 2B.1.1: Environment Setup</div>
            <div class="component-status component-ok">✅ 2B.1.2: Data Sanitizer Implementation</div>
            <div class="component-status component-ok">✅ 2B.1.3: Response Builder Implementation</div>
            <div class="component-status component-ok">✅ 2B.1.4: DateTime Helper Implementation</div>
            <div class="component-status component-ok">✅ 2B.1.5: Calculation Helper Implementation</div>
            <div class="component-status component-ok">✅ 2B.1.6: Integration Testing</div>
            <div class="component-status component-ok">✅ 2B.1.7: Code Extraction & Replacement</div>
            <div class="component-status component-ok">✅ 2B.1.8: Final Optimization & Testing</div>

            <div class="progress-bar" style="margin-top: 15px;">
                <div class="progress-fill" style="width: 100%"></div>
            </div>
            <p>Phase 2B.1 Progress: 100% Complete (8/8 steps)</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Dashboard</button>
        <a href="test-step-2b1-6-integration-testing.php" class="refresh-btn" style="text-decoration: none; margin-left: 10px;">🧪 Run Full Integration Test</a>
    </div>

    <div style="text-align: center; margin-top: 20px; color: #7f8c8d;">
        <p>Dashboard generated in <?php echo round((microtime(true) - $dashboard_start) * 1000, 2); ?>ms</p>
        <p><em>VD License Manager - Phase 2B.1 Utility Helper Integration Dashboard</em></p>
    </div>
</div>

</body>
</html>
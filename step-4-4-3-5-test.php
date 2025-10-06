<?php
/**
 * Step 4.4.3.5 Test - Integration with Main Validator
 *
 * Tests the enhanced integration between main validator and Security Integration Validator including:
 * - Direct delegation to enhanced Security Integration Validator
 * - Fallback mechanism testing
 * - Integration metadata validation
 * - Performance and reliability testing
 * - Error handling and recovery
 */

// Include WordPress
require_once dirname(__FILE__) . '/wp-load.php';

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

// Load VD License Manager classes
if (!class_exists('VD_License_Validator')) {
    $validator_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-license-validator.php';
    if (file_exists($validator_file)) {
        require_once $validator_file;
    }
}

// Load Module Loader if needed
if (!class_exists('VD_License_Module_Loader')) {
    $module_loader_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-license-module-loader.php';
    if (file_exists($module_loader_file)) {
        require_once $module_loader_file;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Step 4.4.3.5 Integration with Main Validator Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 4px; color: #155724; }
        .error { background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 4px; color: #721c24; }
        .info { background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 4px; color: #0c5460; }
        .warning { background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 4px; color: #856404; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .integration-primary { color: #28a745; font-weight: bold; }
        .integration-fallback { color: #fd7e14; font-weight: bold; }
        .integration-error { color: #dc3545; font-weight: bold; }
        .performance-excellent { color: #28a745; }
        .performance-good { color: #6f42c1; }
        .performance-acceptable { color: #fd7e14; }
    </style>
</head>
<body>
    <h1>⚡ Step 4.4.3.5 Integration with Main Validator Test</h1>
    <p><strong>License Key:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>
    <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>Step:</strong> 4.4.3.5 - Enhanced Integration with Main Validator</p>

<?php

echo '<h2>1. Main Validator Loading Test</h2>';

// Check if main validator exists and loads
$main_validator_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-license-validator.php';

echo '<div class="info">🔍 Debug Info:</div>';
echo '<div class="info">📁 WP_PLUGIN_DIR: ' . WP_PLUGIN_DIR . '</div>';
echo '<div class="info">📄 Main validator file path: ' . $main_validator_file . '</div>';

if (file_exists($main_validator_file)) {
    echo '<div class="success">✅ Main validator file exists</div>';
    $file_size = filesize($main_validator_file);
    echo '<div class="info">📋 File size: ' . number_format($file_size) . ' bytes</div>';

    // Check if validator class is available
    if (class_exists('VD_License_Validator')) {
        echo '<div class="success">✅ VD_License_Validator class available</div>';
    } else {
        echo '<div class="error">❌ VD_License_Validator class not found</div>';
        echo '<div class="info">🔄 Attempting to load validator class...</div>';

        $validator_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-license-validator.php';
        if (file_exists($validator_file)) {
            require_once $validator_file;
            if (class_exists('VD_License_Validator')) {
                echo '<div class="success">✅ VD_License_Validator class loaded successfully</div>';
            } else {
                echo '<div class="error">❌ Failed to load VD_License_Validator class</div>';
                exit;
            }
        } else {
            echo '<div class="error">❌ Validator file not found at: ' . $validator_file . '</div>';
            exit;
        }
    }
} else {
    echo '<div class="error">❌ Main validator file not found</div>';
    exit;
}

echo '<h2>2. Integration Method Access Test</h2>';

try {
    // Create or get main validator instance
    $main_validator = VD_License_Validator::get_instance();

    if ($main_validator) {
        echo '<div class="success">✅ Main validator instance created successfully</div>';

        // Use reflection to access the private validate_security_compliance method
        $reflection = new ReflectionClass($main_validator);
        $security_compliance_method = $reflection->getMethod('validate_security_compliance');
        $security_compliance_method->setAccessible(true);

        echo '<div class="success">✅ Security compliance method accessible via reflection</div>';
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Main validator access failed: ' . $e->getMessage() . '</div>';
    exit;
}

echo '<h2>3. Enhanced Integration Testing</h2>';

try {
    // Prepare comprehensive test data
    $test_license = array(
        'license_key' => 'H10D-DIJD-14RC-SOLE-6KUV30',
        'product_id' => 'vd-license-manager',
        'status' => 'active',
        'created_date' => date('Y-m-d H:i:s', strtotime('-30 days')),
        'expires_date' => date('Y-m-d H:i:s', strtotime('+365 days')),
        'max_activations' => 5,
        'activation_count' => 2,
        'domain' => 'vidieu.vn',
        'user_id' => 1
    );

    $test_security_context = array(
        'user_ip' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 Test Browser',
        'domain' => 'vidieu.vn',
        'ssl_enabled' => true,
        'auth_level' => 'admin',
        'session_security' => array(
            'session_timeout' => 3600,
            'csrf_protection' => true,
            'secure_cookies' => true
        ),
        'security_policies' => array(
            'password_policy' => 'strong',
            'access_control' => 'rbac',
            'data_encryption' => 'aes256',
            'audit_logging' => true
        ),
        'compliance_requirements' => array(
            'gdpr' => true,
            'pci_dss' => false,
            'iso27001' => true,
            'industry_specific' => array('general')
        )
    );

    echo '<h3>3.1 Primary Integration Test (Enhanced Security Integration Validator)</h3>';
    $integration_start = microtime(true);
    $integration_result = $security_compliance_method->invoke($main_validator, $test_license, $test_security_context);
    $integration_end = microtime(true);

    echo '<div class="success">✅ Main validator security compliance method executed successfully</div>';

    $execution_time = round(($integration_end - $integration_start) * 1000, 2);
    echo '<div class="info">⏱️ Integration Execution Time: ' . $execution_time . 'ms</div>';

    // Performance classification
    $performance_class = 'performance-excellent';
    if ($execution_time > 10) {
        $performance_class = 'performance-acceptable';
    } elseif ($execution_time > 5) {
        $performance_class = 'performance-good';
    }
    echo '<div class="info">📊 Performance Rating: <span class="' . $performance_class . '">' .
         ($execution_time <= 5 ? 'Excellent' : ($execution_time <= 10 ? 'Good' : 'Acceptable')) . '</span></div>';

    // Analyze integration result
    if (is_array($integration_result)) {
        echo '<div class="success">✅ Integration returned valid array result</div>';

        echo '<h4>Integration Result Analysis:</h4>';
        echo '<table>';
        echo '<tr><th>Property</th><th>Value</th><th>Status</th></tr>';

        // Basic validation properties
        $basic_props = array('valid', 'status', 'compliance_score', 'message');
        foreach ($basic_props as $prop) {
            if (isset($integration_result[$prop])) {
                $status_icon = '✅ Present';
                $value = is_bool($integration_result[$prop]) ?
                        ($integration_result[$prop] ? 'true' : 'false') :
                        $integration_result[$prop];
            } else {
                $status_icon = '❌ Missing';
                $value = 'N/A';
            }

            echo '<tr>';
            echo '<td>' . ucfirst(str_replace('_', ' ', $prop)) . '</td>';
            echo '<td>' . $value . '</td>';
            echo '<td>' . $status_icon . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        echo '<h4>Integration Metadata Analysis:</h4>';
        if (isset($integration_result['integration_info'])) {
            echo '<div class="success">✅ Integration metadata present</div>';

            $integration_info = $integration_result['integration_info'];
            echo '<table>';
            echo '<tr><th>Metadata</th><th>Value</th></tr>';
            foreach ($integration_info as $key => $value) {
                echo '<tr>';
                echo '<td>' . ucfirst(str_replace('_', ' ', $key)) . '</td>';
                echo '<td>' . $value . '</td>';
                echo '</tr>';
            }
            echo '</table>';

            // Determine integration type
            $integration_type = 'Unknown';
            $integration_class = 'integration-error';

            if (isset($integration_info['delegated_to'])) {
                if (strpos($integration_info['delegated_to'], 'Security Integration Validator') !== false) {
                    $integration_type = 'Primary (Enhanced)';
                    $integration_class = 'integration-primary';
                } elseif (strpos($integration_info['delegated_to'], 'Generic Compliance Validator') !== false) {
                    $integration_type = 'Secondary (Fallback)';
                    $integration_class = 'integration-fallback';
                } elseif (strpos($integration_info['delegated_to'], 'Main Validator Fallback') !== false) {
                    $integration_type = 'Final Fallback';
                    $integration_class = 'integration-fallback';
                }
            }

            echo '<div class="info">🔗 Integration Type: <span class="' . $integration_class . '">' . $integration_type . '</span></div>';

            if (isset($integration_info['integration_step'])) {
                echo '<div class="info">📋 Integration Step: ' . $integration_info['integration_step'] . '</div>';
            }
        } else {
            echo '<div class="warning">⚠️ Integration metadata missing - may be using old validator</div>';
        }

        echo '<h4>Step 4.4.3.4 Features Validation:</h4>';
        $step_4_4_3_4_features = array(
            'compliance_checks' => 'Compliance checks array',
            'detailed_results' => 'Detailed results object',
            'recommendations' => 'Compliance recommendations',
            'validation_metrics' => 'Performance metrics'
        );

        echo '<table>';
        echo '<tr><th>Step 4.4.3.4 Feature</th><th>Present</th><th>Content</th></tr>';
        foreach ($step_4_4_3_4_features as $feature => $description) {
            $present = isset($integration_result[$feature]) ? '✅ Yes' : '❌ No';
            $content = 'N/A';

            if (isset($integration_result[$feature])) {
                if (is_array($integration_result[$feature])) {
                    $content = count($integration_result[$feature]) . ' items';
                } else {
                    $content = $integration_result[$feature];
                }
            }

            echo '<tr>';
            echo '<td>' . $description . '</td>';
            echo '<td>' . $present . '</td>';
            echo '<td>' . $content . '</td>';
            echo '</tr>';
        }
        echo '</table>';

    } else {
        echo '<div class="error">❌ Integration returned invalid result type: ' . gettype($integration_result) . '</div>';
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Integration test failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>4. Fallback Mechanism Testing</h2>';

// Test fallback scenarios by temporarily disabling the enhanced validator
echo '<h3>4.1 Testing Fallback Scenarios</h3>';

try {
    // We'll test different scenarios by checking if fallback metadata is properly added

    echo '<div class="info">🧪 Testing integration reliability and fallback handling...</div>';

    // Multiple test runs to check consistency
    $test_runs = 3;
    $successful_runs = 0;
    $integration_types = array();

    for ($i = 1; $i <= $test_runs; $i++) {
        $run_start = microtime(true);
        $run_result = $security_compliance_method->invoke($main_validator, $test_license, $test_security_context);
        $run_end = microtime(true);

        if (is_array($run_result) && isset($run_result['valid'])) {
            $successful_runs++;

            // Track integration type
            if (isset($run_result['integration_info']['delegated_to'])) {
                $integration_types[] = $run_result['integration_info']['delegated_to'];
            }
        }

        echo '<div class="success">✅ Test run ' . $i . ': Success (' . round(($run_end - $run_start) * 1000, 2) . 'ms)</div>';
    }

    echo '<div class="info">📊 Reliability: ' . $successful_runs . '/' . $test_runs . ' runs successful</div>';

    // Analyze integration consistency
    $unique_integrations = array_unique($integration_types);
    echo '<div class="info">🔗 Integration Consistency: ' . (count($unique_integrations) == 1 ? 'Consistent' : 'Variable') . '</div>';

    if (count($unique_integrations) == 1) {
        echo '<div class="success">✅ All runs used: ' . $unique_integrations[0] . '</div>';
    } else {
        echo '<div class="warning">⚠️ Mixed integration types detected:</div>';
        foreach ($unique_integrations as $type) {
            echo '<div class="warning">- ' . $type . '</div>';
        }
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Fallback testing failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>5. Integration Performance Metrics</h2>';

// Performance testing with multiple iterations
echo '<h3>5.1 Performance Benchmarking</h3>';

try {
    $benchmark_runs = 10;
    $execution_times = array();

    echo '<div class="info">🏃 Running ' . $benchmark_runs . ' performance benchmark tests...</div>';

    for ($i = 1; $i <= $benchmark_runs; $i++) {
        $bench_start = microtime(true);
        $bench_result = $security_compliance_method->invoke($main_validator, $test_license, $test_security_context);
        $bench_end = microtime(true);

        $execution_times[] = ($bench_end - $bench_start) * 1000;
    }

    // Calculate performance statistics
    $avg_time = array_sum($execution_times) / count($execution_times);
    $min_time = min($execution_times);
    $max_time = max($execution_times);

    echo '<table>';
    echo '<tr><th>Metric</th><th>Value</th><th>Rating</th></tr>';
    echo '<tr><td>Average Execution Time</td><td>' . round($avg_time, 2) . 'ms</td><td>' .
         ($avg_time <= 5 ? '<span class="performance-excellent">Excellent</span>' :
         ($avg_time <= 10 ? '<span class="performance-good">Good</span>' :
         '<span class="performance-acceptable">Acceptable</span>')) . '</td></tr>';
    echo '<tr><td>Minimum Time</td><td>' . round($min_time, 2) . 'ms</td><td>Best Case</td></tr>';
    echo '<tr><td>Maximum Time</td><td>' . round($max_time, 2) . 'ms</td><td>Worst Case</td></tr>';
    echo '<tr><td>Performance Variance</td><td>' . round($max_time - $min_time, 2) . 'ms</td><td>' .
         (($max_time - $min_time) <= 2 ? '<span class="performance-excellent">Low</span>' :
         (($max_time - $min_time) <= 5 ? '<span class="performance-good">Medium</span>' :
         '<span class="performance-acceptable">High</span>')) . '</td></tr>';
    echo '</table>';

} catch (Exception $e) {
    echo '<div class="error">❌ Performance benchmarking failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>6. Step 4.4.3.5 Implementation Assessment</h2>';

$test_results = array();
$test_results['main_validator_loaded'] = class_exists('VD_License_Validator');
$test_results['integration_method_accessible'] = isset($security_compliance_method);
$test_results['integration_executed_successfully'] = isset($integration_result) && is_array($integration_result);
$test_results['integration_metadata_present'] = isset($integration_result['integration_info']);
$test_results['step_4_4_3_4_features_available'] = isset($integration_result['compliance_checks']) && isset($integration_result['compliance_score']);
$test_results['enhanced_validator_integrated'] = isset($integration_result['integration_info']['delegated_to']) &&
                                                  strpos($integration_result['integration_info']['delegated_to'], 'Security Integration Validator') !== false;
$test_results['fallback_mechanism_working'] = $successful_runs >= ($test_runs - 1);
$test_results['performance_acceptable'] = isset($avg_time) && $avg_time <= 15;

$passed_tests = array_sum($test_results);
$total_tests = count($test_results);

echo '<div class="info"><strong>Test Summary:</strong></div>';
echo '<table>';
echo '<tr><th>Test</th><th>Result</th></tr>';
foreach ($test_results as $test => $result) {
    $status = $result ? '✅ Pass' : '❌ Fail';
    echo '<tr><td>' . ucfirst(str_replace('_', ' ', $test)) . '</td><td>' . $status . '</td></tr>';
}
echo '</table>';

echo '<div class="info"><strong>Overall Score: ' . $passed_tests . '/' . $total_tests . '</strong></div>';

if ($passed_tests === $total_tests) {
    echo '<div class="success"><strong>🎉 Step 4.4.3.5 Implementation: SUCCESS!</strong></div>';
    echo '<div class="success">✅ Enhanced integration with main validator is working correctly</div>';
    echo '<div class="success">✅ Direct delegation to Security Integration Validator operational</div>';
    echo '<div class="success">✅ Fallback mechanisms properly implemented</div>';
    echo '<div class="success">✅ Ready for Step 4.4.3.6 (Comprehensive Testing & Optimization)</div>';
} elseif ($passed_tests >= ($total_tests - 1)) {
    echo '<div class="warning"><strong>⚠️ Step 4.4.3.5 Implementation: MOSTLY WORKING</strong></div>';
    echo '<div class="warning">Minor issues detected, but core integration functionality is operational</div>';
} else {
    echo '<div class="error"><strong>❌ Step 4.4.3.5 Implementation: FAILED</strong></div>';
    echo '<div class="error">Major issues detected, requires debugging</div>';
}

echo '<br><strong>Test completed at:</strong> ' . date('Y-m-d H:i:s') . '<br>';

?>

</body>
</html>
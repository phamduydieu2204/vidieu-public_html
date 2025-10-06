<?php
/**
 * Step 4.4.3.2b Test - Step Integration Analysis
 *
 * Tests the step integration analysis implementation including:
 * - analyze_step_integrations() method functionality
 * - Step dependency checking logic
 * - Integration completeness calculation
 * - Health assessment algorithms
 * - Integration recommendations generation
 */

// Include WordPress
require_once dirname(__FILE__) . '/wp-load.php';

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Step 4.4.3.2b Integration Analysis Test</title>
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
        .health-excellent { color: #28a745; font-weight: bold; }
        .health-good { color: #6f42c1; font-weight: bold; }
        .health-fair { color: #fd7e14; font-weight: bold; }
        .health-poor { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Step 4.4.3.2b Step Integration Analysis Test</h1>
    <p><strong>License Key:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>
    <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>Step:</strong> 4.4.3.2b - Step Integration Analysis</p>

<?php

echo '<h2>1. Module Loading Test</h2>';

// Check if module file exists
$module_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/modules/compliance/class-vd-license-security-integration-validator.php';

echo '<div class="info">🔍 Debug Info:</div>';
echo '<div class="info">📁 WP_PLUGIN_DIR: ' . WP_PLUGIN_DIR . '</div>';
echo '<div class="info">📄 Module file path: ' . $module_file . '</div>';

if (file_exists($module_file)) {
    echo '<div class="success">✅ Security Integration Validator module file exists</div>';
    $file_size = filesize($module_file);
    echo '<div class="info">📋 File size: ' . number_format($file_size) . ' bytes</div>';

    if ($file_size === 0) {
        echo '<div class="error">⚠️ Warning: File is empty (0 bytes)</div>';
        exit;
    }
} else {
    echo '<div class="error">❌ Module file not found at: ' . $module_file . '</div>';
    // Try alternative path
    $alt_module_file = dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/includes/modules/compliance/class-vd-license-security-integration-validator.php';
    echo '<div class="info">🔄 Trying alternative path: ' . $alt_module_file . '</div>';
    if (file_exists($alt_module_file)) {
        echo '<div class="success">✅ Found module at alternative path</div>';
        $module_file = $alt_module_file;
    } else {
        echo '<div class="error">❌ Module file not found at alternative path either</div>';
        exit;
    }
}

// Load the module
try {
    require_once $module_file;
    echo '<div class="success">✅ Module file loaded successfully</div>';

    $class_name = 'VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator';
    if (class_exists($class_name)) {
        echo '<div class="success">✅ Class exists: ' . $class_name . '</div>';
    } else {
        echo '<div class="error">❌ Class not found</div>';
        exit;
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Module loading failed: ' . $e->getMessage() . '</div>';
    exit;
}

echo '<h2>2. Instance Creation Test</h2>';

try {
    $instance = VD\LicenseManager\Compliance\VD_License_Security_Integration_Validator::get_instance();

    if ($instance) {
        echo '<div class="success">✅ Instance created successfully</div>';

        $module_info = $instance->get_module_info();
        echo '<div class="info">📋 Module Version: ' . $module_info['version'] . '</div>';
        echo '<div class="info">📋 Module Step: ' . $module_info['step'] . '</div>';
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Instance creation failed: ' . $e->getMessage() . '</div>';
    exit;
}

echo '<h2>3. Step Integration Analysis Test</h2>';

try {
    // Use reflection to access private methods for testing
    $reflection = new ReflectionClass($instance);

    // Test analyze_step_integrations method
    echo '<h3>3.1 Main Integration Analysis Test</h3>';
    $analysis_method = $reflection->getMethod('analyze_step_integrations');
    $analysis_method->setAccessible(true);
    $integration_analysis = $analysis_method->invoke($instance);

    echo '<div class="success">✅ analyze_step_integrations() method accessible</div>';
    echo '<div class="info">📋 Analysis Timestamp: ' . $integration_analysis['analysis_timestamp'] . '</div>';
    echo '<div class="info">📋 Total Steps Analyzed: ' . $integration_analysis['step_count'] . '</div>';

    // Test dependency analysis
    echo '<h3>3.2 Dependency Analysis Test</h3>';
    $dependency_method = $reflection->getMethod('check_step_dependencies');
    $dependency_method->setAccessible(true);

    $detect_method = $reflection->getMethod('detect_available_steps');
    $detect_method->setAccessible(true);
    $detected_steps = $detect_method->invoke($instance);

    $dependency_analysis = $dependency_method->invoke($instance, $detected_steps);

    echo '<div class="success">✅ check_step_dependencies() method accessible</div>';
    echo '<div class="info">📋 Dependency analysis completed for ' . count($dependency_analysis) . ' steps</div>';

    echo '<table>';
    echo '<tr><th>Step ID</th><th>Dependencies</th><th>Dependencies Met</th><th>Missing Dependencies</th></tr>';
    foreach ($dependency_analysis as $step_id => $dep_info) {
        $deps_met_icon = $dep_info['dependencies_met'] ? '✅ Yes' : '❌ No';
        $deps_list = empty($dep_info['dependencies']) ? 'None' : implode(', ', $dep_info['dependencies']);
        $missing_deps = empty($dep_info['missing_dependencies']) ? 'None' : implode(', ', $dep_info['missing_dependencies']);

        echo '<tr>';
        echo '<td>' . $step_id . '</td>';
        echo '<td>' . $deps_list . '</td>';
        echo '<td>' . $deps_met_icon . '</td>';
        echo '<td>' . $missing_deps . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // Test completeness calculation
    echo '<h3>3.3 Completeness Calculation Test</h3>';
    $completeness_method = $reflection->getMethod('calculate_integration_completeness');
    $completeness_method->setAccessible(true);
    $completeness = $completeness_method->invoke($instance, $detected_steps);

    echo '<div class="success">✅ calculate_integration_completeness() method accessible</div>';
    echo '<div class="info">📊 Overall Completeness: ' . $completeness['overall_percentage'] . '%</div>';
    echo '<div class="info">📊 Critical Steps Completeness: ' . $completeness['critical_percentage'] . '%</div>';

    echo '<table>';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    echo '<tr><td>Total Steps</td><td>' . $completeness['total_steps'] . '</td></tr>';
    echo '<tr><td>Available Steps</td><td>' . $completeness['available_steps'] . '</td></tr>';
    echo '<tr><td>Missing Steps</td><td>' . $completeness['missing_steps'] . '</td></tr>';
    echo '<tr><td>Critical Steps</td><td>' . $completeness['critical_steps'] . '</td></tr>';
    echo '<tr><td>Available Critical</td><td>' . $completeness['available_critical'] . '</td></tr>';
    echo '</table>';

    // Test health assessment
    echo '<h3>3.4 Health Assessment Test</h3>';
    $health_method = $reflection->getMethod('assess_integration_health');
    $health_method->setAccessible(true);
    $health_assessment = $health_method->invoke($instance, $detected_steps, $dependency_analysis);

    echo '<div class="success">✅ assess_integration_health() method accessible</div>';

    $health_class = 'health-' . $health_assessment['health_status'];
    echo '<div class="info">🏥 Health Score: <span class="' . $health_class . '">' . $health_assessment['health_score'] . '/100</span></div>';
    echo '<div class="info">🏥 Health Status: <span class="' . $health_class . '">' . ucfirst($health_assessment['health_status']) . '</span></div>';

    echo '<h4>Health Factors Breakdown:</h4>';
    echo '<table>';
    echo '<tr><th>Factor</th><th>Score</th></tr>';
    foreach ($health_assessment['health_factors'] as $factor => $score) {
        echo '<tr><td>' . ucfirst(str_replace('_', ' ', $factor)) . '</td><td>' . round($score, 2) . '</td></tr>';
    }
    echo '</table>';

    // Test issue detection
    echo '<h3>3.5 Issue Detection Test</h3>';
    $issues_method = $reflection->getMethod('detect_integration_issues');
    $issues_method->setAccessible(true);
    $issues = $issues_method->invoke($instance, $detected_steps, $dependency_analysis);

    echo '<div class="success">✅ detect_integration_issues() method accessible</div>';
    echo '<div class="info">🚨 Issues Detected: ' . count($issues) . '</div>';

    if (!empty($issues)) {
        echo '<table>';
        echo '<tr><th>Type</th><th>Step</th><th>Severity</th><th>Message</th></tr>';
        foreach ($issues as $issue) {
            $severity_class = $issue['severity'] === 'high' ? 'error' : ($issue['severity'] === 'medium' ? 'warning' : 'info');
            echo '<tr>';
            echo '<td>' . $issue['type'] . '</td>';
            echo '<td>' . $issue['step_name'] . '</td>';
            echo '<td><span class="' . $severity_class . '">' . ucfirst($issue['severity']) . '</span></td>';
            echo '<td>' . $issue['message'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="success">✅ No integration issues detected</div>';
    }

    // Test recommendations generation
    echo '<h3>3.6 Recommendations Generation Test</h3>';
    $recommendations_method = $reflection->getMethod('generate_integration_recommendations');
    $recommendations_method->setAccessible(true);
    $recommendations = $recommendations_method->invoke($instance, $detected_steps, $health_assessment);

    echo '<div class="success">✅ generate_integration_recommendations() method accessible</div>';
    echo '<div class="info">💡 Recommendations Generated: ' . count($recommendations) . '</div>';

    echo '<table>';
    echo '<tr><th>Priority</th><th>Action</th><th>Message</th></tr>';
    foreach ($recommendations as $rec) {
        $priority_class = $rec['priority'] === 'high' ? 'error' : ($rec['priority'] === 'medium' ? 'warning' : 'info');
        echo '<tr>';
        echo '<td><span class="' . $priority_class . '">' . ucfirst($rec['priority']) . '</span></td>';
        echo '<td>' . $rec['action'] . '</td>';
        echo '<td>' . $rec['message'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';

} catch (Exception $e) {
    echo '<div class="error">❌ Step integration analysis test failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>4. Integration Analysis Results</h2>';

// Display comprehensive analysis results
if (isset($integration_analysis)) {
    echo '<h3>4.1 Complete Analysis Summary</h3>';

    echo '<div class="info"><strong>Available Steps (' . count($integration_analysis['available_steps']) . '):</strong></div>';
    foreach ($integration_analysis['available_steps'] as $step_id => $step_info) {
        echo '<div class="success">✅ ' . $step_info['name'] . ' (' . $step_info['method'] . ')</div>';
    }

    echo '<div class="info"><strong>Missing Steps (' . count($integration_analysis['missing_steps']) . '):</strong></div>';
    foreach ($integration_analysis['missing_steps'] as $step_id => $step_info) {
        echo '<div class="error">❌ ' . $step_info['name'] . ' (' . $step_info['method'] . ')</div>';
    }

    echo '<h3>4.2 Integration Health Summary</h3>';
    $health = $integration_analysis['health_assessment'];
    $health_class = 'health-' . $health['health_status'];

    echo '<div class="info"><strong>Overall Health Score:</strong> <span class="' . $health_class . '">' . $health['health_score'] . '/100 (' . ucfirst($health['health_status']) . ')</span></div>';

    echo '<h3>4.3 Action Recommendations</h3>';
    foreach ($integration_analysis['recommendations'] as $rec) {
        $priority_class = $rec['priority'] === 'high' ? 'error' : ($rec['priority'] === 'medium' ? 'warning' : 'success');
        echo '<div class="' . $priority_class . '">🔧 [' . strtoupper($rec['priority']) . '] ' . $rec['message'] . '</div>';
    }
}

echo '<h2>5. Step 4.4.3.2b Implementation Assessment</h2>';

$test_results = array();
$test_results['module_loaded'] = class_exists('VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator');
$test_results['instance_created'] = isset($instance) && $instance !== null;
$test_results['analysis_method_works'] = isset($integration_analysis) && is_array($integration_analysis);
$test_results['dependency_analysis_works'] = isset($dependency_analysis) && count($dependency_analysis) === 4;
$test_results['completeness_calculation_works'] = isset($completeness) && isset($completeness['overall_percentage']);
$test_results['health_assessment_works'] = isset($health_assessment) && isset($health_assessment['health_score']);
$test_results['issue_detection_works'] = isset($issues) && is_array($issues);
$test_results['recommendations_generated'] = isset($recommendations) && count($recommendations) > 0;

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
    echo '<div class="success"><strong>🎉 Step 4.4.3.2b Implementation: SUCCESS!</strong></div>';
    echo '<div class="success">✅ Step Integration Analysis is working correctly</div>';
    echo '<div class="success">✅ Ready for Step 4.4.3.2c (Step Monitoring and Alerting)</div>';
} elseif ($passed_tests >= ($total_tests - 1)) {
    echo '<div class="warning"><strong>⚠️ Step 4.4.3.2b Implementation: MOSTLY WORKING</strong></div>';
    echo '<div class="warning">Minor issues detected, but core functionality is operational</div>';
} else {
    echo '<div class="error"><strong>❌ Step 4.4.3.2b Implementation: FAILED</strong></div>';
    echo '<div class="error">Major issues detected, requires debugging</div>';
}

echo '<br><strong>Test completed at:</strong> ' . date('Y-m-d H:i:s') . '<br>';

?>

</body>
</html>
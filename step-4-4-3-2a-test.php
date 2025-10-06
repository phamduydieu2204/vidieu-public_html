<?php
/**
 * Step 4.4.3.2a Test - Step Detection Infrastructure
 *
 * Tests the step detection infrastructure implementation including:
 * - detect_available_steps() method functionality
 * - get_step_configuration() array structure
 * - Step existence checking logic
 * - Step metadata generation
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
    <title>Step 4.4.3.2a Detection Infrastructure Test</title>
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
    </style>
</head>
<body>
    <h1>🔍 Step 4.4.3.2a Step Detection Infrastructure Test</h1>
    <p><strong>License Key:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>
    <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>Step:</strong> 4.4.3.2a - Step Detection Infrastructure</p>

<?php

echo '<h2>1. Module Loading Test</h2>';

// Check if module file exists
$module_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/modules/compliance/class-vd-license-security-integration-validator.php';

if (file_exists($module_file)) {
    echo '<div class="success">✅ Security Integration Validator module file exists</div>';
    echo '<div class="info">📋 File size: ' . number_format(filesize($module_file)) . ' bytes</div>';
} else {
    echo '<div class="error">❌ Module file not found</div>';
    exit;
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

echo '<h2>3. Step Detection Infrastructure Test</h2>';

try {
    // Use reflection to access private methods for testing
    $reflection = new ReflectionClass($instance);

    // Test get_step_configuration method
    echo '<h3>3.1 Step Configuration Test</h3>';
    $config_method = $reflection->getMethod('get_step_configuration');
    $config_method->setAccessible(true);
    $step_config = $config_method->invoke($instance);

    echo '<div class="success">✅ get_step_configuration() method accessible</div>';
    echo '<div class="info">📋 Configuration contains ' . count($step_config) . ' step definitions</div>';

    echo '<table>';
    echo '<tr><th>Step ID</th><th>Name</th><th>Method</th><th>Priority</th><th>Critical</th></tr>';
    foreach ($step_config as $step_id => $step_info) {
        echo '<tr>';
        echo '<td>' . $step_id . '</td>';
        echo '<td>' . $step_info['name'] . '</td>';
        echo '<td>' . $step_info['method'] . '</td>';
        echo '<td>' . $step_info['priority'] . '</td>';
        echo '<td>' . ($step_info['critical'] ? '✅ Yes' : '❌ No') . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // Test detect_available_steps method
    echo '<h3>3.2 Step Detection Test</h3>';
    $detect_method = $reflection->getMethod('detect_available_steps');
    $detect_method->setAccessible(true);
    $detected_steps = $detect_method->invoke($instance);

    echo '<div class="success">✅ detect_available_steps() method accessible</div>';
    echo '<div class="info">📋 Detected ' . count($detected_steps) . ' steps</div>';

    $available_count = 0;
    $missing_count = 0;

    echo '<table>';
    echo '<tr><th>Step ID</th><th>Name</th><th>Method</th><th>Available</th><th>Status</th><th>Priority</th></tr>';
    foreach ($detected_steps as $step_id => $step_info) {
        $status_class = $step_info['available'] ? 'success' : 'error';
        $status_icon = $step_info['available'] ? '✅' : '❌';

        if ($step_info['available']) {
            $available_count++;
        } else {
            $missing_count++;
        }

        echo '<tr>';
        echo '<td>' . $step_info['id'] . '</td>';
        echo '<td>' . $step_info['name'] . '</td>';
        echo '<td>' . $step_info['method'] . '</td>';
        echo '<td>' . $status_icon . ' ' . ($step_info['available'] ? 'Available' : 'Missing') . '</td>';
        echo '<td>' . ucfirst($step_info['status']) . '</td>';
        echo '<td>' . $step_info['priority'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<div class="info">📊 Detection Summary:</div>';
    echo '<div class="info">✅ Available Steps: ' . $available_count . '</div>';
    echo '<div class="info">❌ Missing Steps: ' . $missing_count . '</div>';
    echo '<div class="info">📋 Total Steps: ' . count($detected_steps) . '</div>';

    if ($available_count === count($detected_steps)) {
        echo '<div class="success">🎉 All steps are available and integrated!</div>';
    } elseif ($available_count > 0) {
        echo '<div class="warning">⚠️ Partial integration detected</div>';
    } else {
        echo '<div class="error">❌ No steps are currently integrated</div>';
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Step detection test failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>4. Integration with Foundation Test</h2>';

// Test if the new detection infrastructure integrates with foundation
try {
    $foundation_result = $instance->validate_step_integration(
        array('license_key' => 'H10D-DIJD-14RC-SOLE-6KUV30'),
        array('test_context' => true)
    );

    echo '<div class="success">✅ Foundation validate_step_integration() still works</div>';
    echo '<div class="info">📋 Foundation Mode: ' . $foundation_result['foundation_info']['mode'] . '</div>';
    echo '<div class="info">📋 Foundation Status: ' . $foundation_result['status'] . '</div>';

} catch (Exception $e) {
    echo '<div class="error">❌ Foundation integration test failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>5. Step 4.4.3.2a Implementation Assessment</h2>';

$test_results = array();
$test_results['module_loaded'] = class_exists('VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator');
$test_results['instance_created'] = isset($instance) && $instance !== null;
$test_results['step_configuration_works'] = isset($step_config) && count($step_config) === 4;
$test_results['step_detection_works'] = isset($detected_steps) && count($detected_steps) === 4;
$test_results['foundation_compatibility'] = isset($foundation_result) && $foundation_result['status'] === 'foundation_mode';

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
    echo '<div class="success"><strong>🎉 Step 4.4.3.2a Implementation: SUCCESS!</strong></div>';
    echo '<div class="success">✅ Step Detection Infrastructure is working correctly</div>';
    echo '<div class="success">✅ Ready for Step 4.4.3.2b (Integration Analysis)</div>';
} elseif ($passed_tests >= ($total_tests - 1)) {
    echo '<div class="warning"><strong>⚠️ Step 4.4.3.2a Implementation: MOSTLY WORKING</strong></div>';
    echo '<div class="warning">Minor issues detected, but core functionality is operational</div>';
} else {
    echo '<div class="error"><strong>❌ Step 4.4.3.2a Implementation: FAILED</strong></div>';
    echo '<div class="error">Major issues detected, requires debugging</div>';
}

echo '<br><strong>Test completed at:</strong> ' . date('Y-m-d H:i:s') . '<br>';

?>

</body>
</html>
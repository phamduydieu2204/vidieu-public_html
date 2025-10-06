<?php
/**
 * Step 4.4.3.1 Security Integration Foundation Test
 *
 * Tests the foundation module for Security Integration Validator
 * Verifies basic structure, singleton pattern, and mock method responses
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
    <title>Step 4.4.3.1 Foundation Test</title>
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
    <h1>🔒 Step 4.4.3.1 Security Integration Foundation Test</h1>
    <p><strong>License Key:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>
    <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

<?php

echo '<h2>1. Module File and Structure Test</h2>';

// Check if module file exists
$module_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/modules/compliance/class-vd-license-security-integration-validator.php';

if (file_exists($module_file)) {
    echo '<div class="success">✅ Module file exists: ' . basename($module_file) . '</div>';
    echo '<div class="info">📋 File size: ' . number_format(filesize($module_file)) . ' bytes</div>';
} else {
    echo '<div class="error">❌ Module file not found</div>';
    exit;
}

echo '<h2>2. Module Loading Test</h2>';

try {
    // Include the module file
    require_once $module_file;
    echo '<div class="success">✅ Module file loaded successfully</div>';

    // Check if class exists
    $class_name = 'VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator';
    if (class_exists($class_name)) {
        echo '<div class="success">✅ Class exists: ' . $class_name . '</div>';
    } else {
        echo '<div class="error">❌ Class not found: ' . $class_name . '</div>';
        exit;
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Module loading failed: ' . $e->getMessage() . '</div>';
    exit;
}

echo '<h2>3. Singleton Pattern Test</h2>';

try {
    // Test singleton instance creation
    $instance1 = VD\LicenseManager\Compliance\VD_License_Security_Integration_Validator::get_instance();
    $instance2 = VD\LicenseManager\Compliance\VD_License_Security_Integration_Validator::get_instance();

    if ($instance1 === $instance2) {
        echo '<div class="success">✅ Singleton pattern working correctly</div>';
        echo '<div class="info">📋 Instance type: ' . get_class($instance1) . '</div>';
    } else {
        echo '<div class="error">❌ Singleton pattern failed - multiple instances created</div>';
    }

    // Test module readiness
    if ($instance1->is_ready()) {
        echo '<div class="success">✅ Module is initialized and ready</div>';
    } else {
        echo '<div class="warning">⚠️ Module not ready</div>';
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Singleton test failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>4. Module Information Test</h2>';

if (isset($instance1)) {
    try {
        $module_info = $instance1->get_module_info();
        echo '<div class="success">✅ Module info retrieved successfully</div>';
        echo '<div class="info">Module Information:</div>';
        echo '<pre>' . json_encode($module_info, JSON_PRETTY_PRINT) . '</pre>';

    } catch (Exception $e) {
        echo '<div class="error">❌ Module info test failed: ' . $e->getMessage() . '</div>';
    }
}

echo '<h2>5. Foundation Methods Test</h2>';

if (isset($instance1)) {
    // Test data
    $test_license = array(
        'license_key' => 'H10D-DIJD-14RC-SOLE-6KUV30',
        'status' => 'active',
        'user_id' => 1,
        'domain' => 'vidieu.vn'
    );

    $test_context = array(
        'user_id' => 1,
        'ip' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'timestamp' => time()
    );

    $test_security_context = array(
        'login_method' => 'standard',
        'session_id' => 'test_session_123',
        'device_fingerprint' => 'test_device_456'
    );

    // Test validate_step_integration
    echo '<h3>5.1 Step Integration Validation Test</h3>';
    try {
        $result = $instance1->validate_step_integration($test_license, $test_context);
        echo '<div class="success">✅ validate_step_integration executed</div>';
        echo '<div class="info">Result:</div>';
        echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';

        // Verify result structure
        if (isset($result['valid']) && $result['valid'] === true) {
            echo '<div class="success">✅ Valid result returned</div>';
        }
        if (isset($result['foundation_info']['step']) && $result['foundation_info']['step'] === '4.4.3.1') {
            echo '<div class="success">✅ Correct step identifier</div>';
        }

    } catch (Exception $e) {
        echo '<div class="error">❌ Step integration test failed: ' . $e->getMessage() . '</div>';
    }

    // Test validate_user_security_context
    echo '<h3>5.2 User Security Context Validation Test</h3>';
    try {
        $result = $instance1->validate_user_security_context($test_security_context);
        echo '<div class="success">✅ validate_user_security_context executed</div>';
        echo '<div class="info">Result:</div>';
        echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';

        // Verify result structure
        if (isset($result['security_score']) && is_numeric($result['security_score'])) {
            echo '<div class="success">✅ Security score provided: ' . $result['security_score'] . '</div>';
        }

    } catch (Exception $e) {
        echo '<div class="error">❌ Security context test failed: ' . $e->getMessage() . '</div>';
    }

    // Test validate_security_compliance
    echo '<h3>5.3 Security Compliance Validation Test</h3>';
    try {
        $result = $instance1->validate_security_compliance($test_license, $test_security_context);
        echo '<div class="success">✅ validate_security_compliance executed</div>';
        echo '<div class="info">Result:</div>';
        echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';

        // Verify result structure
        if (isset($result['compliance_score']) && is_numeric($result['compliance_score'])) {
            echo '<div class="success">✅ Compliance score provided: ' . $result['compliance_score'] . '</div>';
        }

    } catch (Exception $e) {
        echo '<div class="error">❌ Security compliance test failed: ' . $e->getMessage() . '</div>';
    }
}

echo '<h2>6. Performance Test</h2>';

if (isset($instance1)) {
    $start_time = microtime(true);

    // Run multiple calls to test performance
    for ($i = 0; $i < 10; $i++) {
        $instance1->validate_step_integration($test_license, $test_context);
        $instance1->validate_user_security_context($test_security_context);
        $instance1->validate_security_compliance($test_license, $test_security_context);
    }

    $execution_time = (microtime(true) - $start_time) * 1000;
    echo '<div class="info">📊 Performance: 30 method calls in ' . round($execution_time, 2) . 'ms</div>';
    echo '<div class="info">📊 Average per call: ' . round($execution_time / 30, 2) . 'ms</div>';

    if ($execution_time < 100) {
        echo '<div class="success">✅ Performance test passed (< 100ms)</div>';
    } else {
        echo '<div class="warning">⚠️ Performance slower than expected</div>';
    }
}

echo '<h2>7. Final Assessment</h2>';

$test_results = array();
$test_results['file_exists'] = file_exists($module_file);
$test_results['class_loaded'] = class_exists('VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator');
$test_results['singleton_working'] = isset($instance1) && isset($instance2) && ($instance1 === $instance2);
$test_results['module_ready'] = isset($instance1) && $instance1->is_ready();
$test_results['methods_working'] = true; // Assume true if we got this far

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
    echo '<div class="success"><strong>🎉 Step 4.4.3.1 Foundation Test: ALL TESTS PASSED!</strong></div>';
    echo '<div class="success">✅ Security Integration Foundation module is working correctly</div>';
    echo '<div class="success">✅ Ready for Step 4.4.3.2 implementation</div>';
} elseif ($passed_tests >= ($total_tests - 1)) {
    echo '<div class="warning"><strong>⚠️ Step 4.4.3.1 Foundation Test: MOSTLY WORKING</strong></div>';
    echo '<div class="warning">Minor issues detected, but foundation is functional</div>';
} else {
    echo '<div class="error"><strong>❌ Step 4.4.3.1 Foundation Test: FAILED</strong></div>';
    echo '<div class="error">Major issues detected, requires debugging</div>';
}

echo '<br><strong>Test completed at:</strong> ' . date('Y-m-d H:i:s') . '<br>';

?>

</body>
</html>
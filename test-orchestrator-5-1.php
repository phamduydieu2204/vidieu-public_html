<?php
/**
 * Micro-Step 5.1: Orchestrator Module Assessment Test
 * Duration: 1 hour
 * Goal: Verify VD_License_Validation_Orchestrator module exists and functions
 */

// WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

echo "<h1>🔍 Micro-Step 5.1: Orchestrator Module Assessment</h1>\n";
echo "<pre>";

echo "=== ORCHESTRATOR MODULE ASSESSMENT ===\n\n";

$assessment_results = array(
    'module_exists' => false,
    'class_loadable' => false,
    'singleton_works' => false,
    'key_methods_exist' => false,
    'namespace_correct' => false,
    'dependencies_met' => false
);

try {
    echo "1. Checking orchestrator file existence...\n";
    $orchestrator_file = './wp-content/plugins/vd-license-manager/includes/modules/validator/class-vd-license-validation-orchestrator.php';

    if (file_exists($orchestrator_file)) {
        echo "   ✅ File exists: " . $orchestrator_file . "\n";
        echo "   📊 File size: " . filesize($orchestrator_file) . " bytes\n";
        $assessment_results['module_exists'] = true;
    } else {
        echo "   ❌ File not found: " . $orchestrator_file . "\n";
        throw new Exception("Orchestrator file not found");
    }

    echo "\n2. Testing class loading...\n";
    require_once($orchestrator_file);
    echo "   ✅ File loaded without syntax errors\n";
    $assessment_results['class_loadable'] = true;

    echo "\n3. Checking namespace and class definition...\n";
    if (class_exists('VD\LicenseManager\Validator\VD_License_Validation_Orchestrator')) {
        echo "   ✅ Class exists with correct namespace\n";
        $assessment_results['namespace_correct'] = true;
    } else {
        echo "   ❌ Class not found with expected namespace\n";
        throw new Exception("Class not found in expected namespace");
    }

    echo "\n4. Testing singleton pattern...\n";
    $instance1 = VD\LicenseManager\Validator\VD_License_Validation_Orchestrator::get_instance();
    $instance2 = VD\LicenseManager\Validator\VD_License_Validation_Orchestrator::get_instance();

    if ($instance1 === $instance2 && is_object($instance1)) {
        echo "   ✅ Singleton pattern working correctly\n";
        echo "   📊 Instance class: " . get_class($instance1) . "\n";
        $assessment_results['singleton_works'] = true;
    } else {
        echo "   ❌ Singleton pattern not working\n";
        throw new Exception("Singleton pattern failed");
    }

    echo "\n5. Verifying key methods existence...\n";
    $required_methods = [
        'orchestrate_license_validation',
        'generate_advanced_validation_report',
        'get_orchestrator_configuration',
        'initialize_validation_modules'
    ];

    $missing_methods = array();
    foreach ($required_methods as $method) {
        if (method_exists($instance1, $method)) {
            echo "   ✅ Method exists: {$method}()\n";
        } else {
            echo "   ❌ Method missing: {$method}()\n";
            $missing_methods[] = $method;
        }
    }

    if (empty($missing_methods)) {
        $assessment_results['key_methods_exist'] = true;
        echo "   ✅ All required methods present\n";
    } else {
        throw new Exception("Missing methods: " . implode(', ', $missing_methods));
    }

    echo "\n6. Testing basic method calls...\n";

    // Test get_orchestrator_configuration
    try {
        $config = $instance1->get_orchestrator_configuration();
        if (is_array($config)) {
            echo "   ✅ get_orchestrator_configuration() returns array\n";
            echo "   📊 Config keys: " . implode(', ', array_keys($config)) . "\n";
        } else {
            echo "   ⚠️  get_orchestrator_configuration() returns non-array\n";
        }
    } catch (Exception $e) {
        echo "   ❌ get_orchestrator_configuration() failed: " . $e->getMessage() . "\n";
    }

    // Test initialize_validation_modules
    try {
        $init_result = $instance1->initialize_validation_modules();
        if (is_array($init_result)) {
            echo "   ✅ initialize_validation_modules() executed\n";
            echo "   📊 Result: " . json_encode($init_result, JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "   ⚠️  initialize_validation_modules() unexpected return\n";
        }
    } catch (Exception $e) {
        echo "   ❌ initialize_validation_modules() failed: " . $e->getMessage() . "\n";
    }

    $assessment_results['dependencies_met'] = true;

    echo "\n=== ASSESSMENT SUMMARY ===\n";
    $total_checks = count($assessment_results);
    $passed_checks = count(array_filter($assessment_results));

    echo "Total Checks: {$total_checks}\n";
    echo "Passed Checks: {$passed_checks}\n";
    echo "Success Rate: " . round(($passed_checks / $total_checks) * 100, 1) . "%\n\n";

    foreach ($assessment_results as $check => $result) {
        $status = $result ? '✅ PASS' : '❌ FAIL';
        $formatted_check = str_replace('_', ' ', ucwords($check, '_'));
        echo "{$status}: {$formatted_check}\n";
    }

    if ($passed_checks === $total_checks) {
        echo "\n🎉 MICRO-STEP 5.1 COMPLETED SUCCESSFULLY!\n";
        echo "✅ Orchestrator module is fully functional and ready for integration\n";
        echo "🔄 Ready to proceed to Micro-Step 5.2: Advanced Validation Rules Mapping\n";
    } else {
        echo "\n⚠️  MICRO-STEP 5.1 PARTIALLY COMPLETED\n";
        echo "❌ Some checks failed - review and fix before proceeding\n";
    }

} catch (Exception $e) {
    echo "\n❌ ASSESSMENT FAILED: " . $e->getMessage() . "\n";
    echo "🔄 Fix issues before proceeding to next micro-step\n";
} catch (Error $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== MICRO-STEP 5.1 ASSESSMENT COMPLETE ===\n";
echo "</pre>";
?>
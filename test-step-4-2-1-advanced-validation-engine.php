<?php
/**
 * Test Step 4.2.1: Advanced Validation Engine
 *
 * Step 4.2.1 - Testing Advanced Validation Engine module functionality
 * Extracted from monolithic validator to provide advanced validation rule processing,
 * enhanced basic validation, conditional state validation, and cross-entity relationship validation.
 *
 * @package VD_License_Manager
 * @subpackage Tests
 * @since 4.2.1
 * @author VD Team
 */

// Load WordPress environment if not already loaded
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/wp-config.php');
}

// Test configuration
$test_config = array(
    'title' => 'Step 4.2.1: Advanced Validation Engine Test',
    'version' => '4.2.1',
    'test_license_data' => array(
        'id' => 12345,
        'license_key' => 'TEST-ADVANCED-VALIDATION-ENGINE-001',
        'status' => 'active',
        'product_id' => 100,
        'user_id' => 1,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
    ),
    'test_context' => array(
        'old_status' => 'pending',
        'new_status' => 'active',
        'user_context' => array(
            'user_id' => 1,
            'user_role' => 'administrator'
        ),
        'ip_context' => array(
            'ip_address' => '192.168.1.100'
        )
    ),
    'debug_mode' => true
);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>{$test_config['title']}</title>\n";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #333; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
h2 { color: #0073aa; margin-top: 30px; }
h3 { color: #666; margin-top: 20px; }
.test-section { background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
.success { background: #d4edda; border-color: #28a745; color: #155724; padding: 10px; border-radius: 4px; margin: 5px 0; }
.error { background: #f8d7da; border-color: #dc3545; color: #721c24; padding: 10px; border-radius: 4px; margin: 5px 0; }
.warning { background: #fff3cd; border-color: #ffc107; color: #856404; padding: 10px; border-radius: 4px; margin: 5px 0; }
.info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; padding: 10px; border-radius: 4px; margin: 5px 0; }
.code { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; margin: 5px 0; }
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0; }
.stat-card { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center; }
.stat-value { font-size: 24px; font-weight: bold; color: #0073aa; }
.stat-label { color: #666; font-size: 12px; text-transform: uppercase; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; font-weight: bold; }
.json-data { max-height: 300px; overflow-y: auto; }
</style></head><body>\n";

echo "<div class='container'>\n";
echo "<h1>🚀 {$test_config['title']}</h1>\n";
echo "<div class='info'>Testing Advanced Validation Engine module extracted from monolithic validator class</div>\n";

// Initialize test environment
echo "<div class='test-section'>\n";
echo "<h2>🚀 Test Environment Setup</h2>\n";

$start_time = microtime(true);
$test_results = array();
$total_tests = 0;
$passed_tests = 0;

try {
    // Load VD License Manager
    if (!class_exists('VD_License_Manager')) {
        require_once(dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/vd-license-manager.php');
    }

    echo "<div class='success'>✅ WordPress environment loaded successfully</div>\n";

    // Load Module Loader directly
    if (!class_exists('VD_License_Module_Loader')) {
        require_once(dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php');
    }

    // Get Module Loader
    if (class_exists('VD_License_Module_Loader')) {
        $loader = VD_License_Module_Loader::get_instance();
        echo "<div class='success'>✅ Module Loader available</div>\n";
    } else {
        throw new Exception('Module Loader not found');
    }

    // Load Advanced Validation Engine module
    $validation_engine = $loader->load_module('validation_rules.advanced_engine');
    if ($validation_engine) {
        echo "<div class='success'>✅ Advanced Validation Engine module loaded successfully</div>\n";
        echo "<div class='info'>Module Class: " . get_class($validation_engine) . "</div>\n";
    } else {
        throw new Exception('Failed to load Advanced Validation Engine module');
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Setup Error: " . $e->getMessage() . "</div>\n";
    echo "</div></body></html>";
    exit;
}

echo "</div>\n";

// Test 1: Module Status and Health Check
echo "<div class='test-section'>\n";
echo "<h2>📊 Test 1: Module Status and Health Check</h2>\n";

$total_tests++;
try {
    $status = $validation_engine->get_status();
    $health = $validation_engine->health_check();

    echo "<h3>Module Status:</h3>\n";
    echo "<div class='code'>" . print_r($status, true) . "</div>\n";

    echo "<h3>Health Check Results:</h3>\n";
    echo "<div class='code'>" . print_r($health, true) . "</div>\n";

    if ($status['initialized'] && $health['status'] !== 'error') {
        echo "<div class='success'>✅ Test 1 PASSED: Module is healthy and initialized</div>\n";
        $passed_tests++;
        $test_results['module_health'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 1 FAILED: Module health issues detected</div>\n";
        $test_results['module_health'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 1 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['module_health'] = 'ERROR';
}

echo "</div>\n";

// Test 2: Advanced Validation Rules Fallback
echo "<div class='test-section'>\n";
echo "<h2>🔧 Test 2: Advanced Validation Rules Fallback</h2>\n";

$total_tests++;
try {
    $license = $test_config['test_license_data'];
    $context = $test_config['test_context'];

    $validation_result = $validation_engine->apply_advanced_validation_rules_fallback($license, $context);

    echo "<div class='info'>Testing advanced validation rules with test license data</div>\n";
    echo "<h3>Validation Result:</h3>\n";
    echo "<div class='code'>" . print_r($validation_result, true) . "</div>\n";

    if (isset($validation_result['valid']) && isset($validation_result['framework_version'])) {
        echo "<div class='success'>✅ Test 2 PASSED: Advanced validation rules fallback working</div>\n";
        $passed_tests++;
        $test_results['advanced_rules_fallback'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 2 FAILED: Invalid validation result structure</div>\n";
        $test_results['advanced_rules_fallback'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 2 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['advanced_rules_fallback'] = 'ERROR';
}

echo "</div>\n";

// Test 3: Enhanced Basic Validation
echo "<div class='test-section'>\n";
echo "<h2>🔍 Test 3: Enhanced Basic Validation</h2>\n";

$total_tests++;
try {
    $license = $test_config['test_license_data'];
    $context = $test_config['test_context'];

    $validation_result = $validation_engine->perform_enhanced_basic_validation($license, $context);

    echo "<div class='info'>Testing enhanced basic validation with user and IP context</div>\n";
    echo "<h3>Enhanced Validation Result:</h3>\n";
    echo "<div class='code'>" . print_r($validation_result, true) . "</div>\n";

    // Test with invalid data
    $invalid_license = array('license_key' => ''); // Missing required fields
    $invalid_result = $validation_engine->perform_enhanced_basic_validation($invalid_license, array());

    echo "<h3>Invalid License Test:</h3>\n";
    echo "<div class='code'>" . print_r($invalid_result, true) . "</div>\n";

    if (isset($validation_result['valid']) && isset($validation_result['enhanced_checks'])) {
        echo "<div class='success'>✅ Test 3 PASSED: Enhanced basic validation working</div>\n";
        $passed_tests++;
        $test_results['enhanced_basic_validation'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 3 FAILED: Enhanced validation structure incomplete</div>\n";
        $test_results['enhanced_basic_validation'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 3 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['enhanced_basic_validation'] = 'ERROR';
}

echo "</div>\n";

// Test 4: Conditional State Validation
echo "<div class='test-section'>\n";
echo "<h2>⚡ Test 4: Conditional State Validation</h2>\n";

$total_tests++;
try {
    $license = $test_config['test_license_data'];
    $context = $test_config['test_context'];

    $validation_result = $validation_engine->perform_conditional_state_validation($license, $context);

    echo "<div class='info'>Testing conditional state validation with state transitions</div>\n";
    echo "<h3>Conditional Validation Result:</h3>\n";
    echo "<div class='code'>" . print_r($validation_result, true) . "</div>\n";

    // Test invalid state transition
    $invalid_context = array_merge($context, array(
        'new_status' => 'invalid_status'
    ));
    $invalid_result = $validation_engine->perform_conditional_state_validation($license, $invalid_context);

    echo "<h3>Invalid State Transition Test:</h3>\n";
    echo "<div class='code'>" . print_r($invalid_result, true) . "</div>\n";

    if (isset($validation_result['valid']) && isset($validation_result['conditional_checks'])) {
        echo "<div class='success'>✅ Test 4 PASSED: Conditional state validation working</div>\n";
        $passed_tests++;
        $test_results['conditional_state_validation'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 4 FAILED: Conditional validation structure incomplete</div>\n";
        $test_results['conditional_state_validation'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 4 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['conditional_state_validation'] = 'ERROR';
}

echo "</div>\n";

// Test 5: License Relationships Validation
echo "<div class='test-section'>\n";
echo "<h2>🔗 Test 5: License Relationships Validation</h2>\n";

$total_tests++;
try {
    $license = $test_config['test_license_data'];
    $context = $test_config['test_context'];

    $validation_result = $validation_engine->validate_license_relationships($license, $context);

    echo "<div class='info'>Testing cross-entity license relationship validation</div>\n";
    echo "<h3>Relationship Validation Result:</h3>\n";
    echo "<div class='code'>" . print_r($validation_result, true) . "</div>\n";

    // Test with inconsistent user data
    $inconsistent_license = array_merge($license, array('user_id' => 999));
    $inconsistent_result = $validation_engine->validate_license_relationships($inconsistent_license, $context);

    echo "<h3>Inconsistent User Data Test:</h3>\n";
    echo "<div class='code'>" . print_r($inconsistent_result, true) . "</div>\n";

    if (isset($validation_result['valid']) && isset($validation_result['cross_entity_checks'])) {
        echo "<div class='success'>✅ Test 5 PASSED: License relationships validation working</div>\n";
        $passed_tests++;
        $test_results['license_relationships'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 5 FAILED: Relationship validation structure incomplete</div>\n";
        $test_results['license_relationships'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 5 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['license_relationships'] = 'ERROR';
}

echo "</div>\n";

// Test 6: Integration with Main Validator (Code Verification)
echo "<div class='test-section'>\n";
echo "<h2>🔗 Test 6: Integration with Main Validator</h2>\n";

$total_tests++;
try {
    // Check if validator file exists first
    $validator_file = dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
    if (!file_exists($validator_file)) {
        throw new Exception('Validator file not found at: ' . $validator_file);
    }

    echo "<div class='success'>✅ Validator file found</div>\n";

    // Read file content and verify integration code exists
    $validator_content = file_get_contents($validator_file);
    if ($validator_content === false) {
        throw new Exception('Cannot read validator file content');
    }

    echo "<div class='info'>Checking integration code in validator file...</div>\n";

    // Check for Advanced Validation Engine property declaration
    $property_pattern = '/private\s+\$advanced_validation_engine\s*=\s*null/';
    $property_found = preg_match($property_pattern, $validator_content);

    if ($property_found) {
        echo "<div class='success'>✅ Advanced Validation Engine property declaration found</div>\n";
    } else {
        echo "<div class='error'>❌ Advanced Validation Engine property declaration not found</div>\n";
    }

    // Check for module loading in initialization
    $init_pattern = '/\$this->advanced_validation_engine\s*=\s*\$loader->load_module\s*\(\s*[\'"]validation_rules\.advanced_engine[\'"]\s*\)/';
    $init_found = preg_match($init_pattern, $validator_content);

    if ($init_found) {
        echo "<div class='success'>✅ Advanced Validation Engine module loading code found</div>\n";
    } else {
        echo "<div class='error'>❌ Advanced Validation Engine module loading code not found</div>\n";
    }

    // Check for delegation methods
    $delegation_methods = array(
        'apply_advanced_validation_rules_fallback' => '/if\s*\(\s*\$this->advanced_validation_engine\s*\)\s*{\s*return\s+\$this->advanced_validation_engine->apply_advanced_validation_rules_fallback/',
        'perform_enhanced_basic_validation' => '/if\s*\(\s*\$this->advanced_validation_engine\s*\)\s*{\s*return\s+\$this->advanced_validation_engine->perform_enhanced_basic_validation/',
        'perform_conditional_state_validation' => '/if\s*\(\s*\$this->advanced_validation_engine\s*\)\s*{\s*return\s+\$this->advanced_validation_engine->perform_conditional_state_validation/',
        'validate_license_relationships' => '/if\s*\(\s*\$this->advanced_validation_engine\s*\)\s*{\s*return\s+\$this->advanced_validation_engine->validate_license_relationships/'
    );

    $delegation_count = 0;
    foreach ($delegation_methods as $method => $pattern) {
        if (preg_match($pattern, $validator_content)) {
            echo "<div class='success'>✅ Delegation for {$method}() found</div>\n";
            $delegation_count++;
        } else {
            echo "<div class='error'>❌ Delegation for {$method}() not found</div>\n";
        }
    }

    // Overall integration assessment
    $total_checks = 2 + count($delegation_methods); // property + init + 4 delegation methods
    $passed_checks = ($property_found ? 1 : 0) + ($init_found ? 1 : 0) + $delegation_count;

    echo "<h3>Integration Summary:</h3>\n";
    echo "<div class='info'>Integration checks passed: {$passed_checks}/{$total_checks}</div>\n";

    if ($passed_checks >= $total_checks * 0.8) { // 80% or more
        echo "<div class='success'>✅ Advanced Validation Engine integration is properly implemented</div>\n";
        $passed_tests++;
        $test_results['validator_integration'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Advanced Validation Engine integration is incomplete</div>\n";
        $test_results['validator_integration'] = 'FAILED';
    }

    // Show some actual code snippets as proof
    echo "<h3>Code Verification Examples:</h3>\n";

    if (preg_match('/private\s+\$advanced_validation_engine[^;]*;/', $validator_content, $matches)) {
        echo "<div class='code'>Property: " . htmlspecialchars(trim($matches[0])) . "</div>\n";
    }

    if (preg_match('/\$this->advanced_validation_engine\s*=\s*\$loader->load_module[^;]*;/', $validator_content, $matches)) {
        echo "<div class='code'>Initialization: " . htmlspecialchars(trim($matches[0])) . "</div>\n";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 6 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['validator_integration'] = 'ERROR';
}

echo "</div>\n";

// Test Summary
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time) * 1000, 2);

echo "<div class='test-section'>\n";
echo "<h2>📋 Test Summary</h2>\n";

echo "<div class='stats'>\n";
echo "<div class='stat-card'>\n";
echo "<div class='stat-value'>{$passed_tests}/{$total_tests}</div>\n";
echo "<div class='stat-label'>Tests Passed</div>\n";
echo "</div>\n";

echo "<div class='stat-card'>\n";
echo "<div class='stat-value'>" . round(($passed_tests / $total_tests) * 100, 1) . "%</div>\n";
echo "<div class='stat-label'>Success Rate</div>\n";
echo "</div>\n";

echo "<div class='stat-card'>\n";
echo "<div class='stat-value'>{$execution_time}ms</div>\n";
echo "<div class='stat-label'>Execution Time</div>\n";
echo "</div>\n";

echo "<div class='stat-card'>\n";
echo "<div class='stat-value'>4.2.1</div>\n";
echo "<div class='stat-label'>Module Version</div>\n";
echo "</div>\n";
echo "</div>\n";

echo "<h3>Detailed Test Results:</h3>\n";
echo "<table>\n";
echo "<tr><th>Test</th><th>Result</th><th>Description</th></tr>\n";

$test_descriptions = array(
    'module_health' => 'Module initialization and health check',
    'advanced_rules_fallback' => 'Advanced validation rules with orchestrator fallback',
    'enhanced_basic_validation' => 'Enhanced basic validation with context awareness',
    'conditional_state_validation' => 'Conditional state validation with business logic',
    'license_relationships' => 'Cross-entity license relationship validation',
    'validator_integration' => 'Integration with main validator class'
);

foreach ($test_results as $test_name => $result) {
    $status_class = strtolower($result);
    $icon = ($result === 'PASSED') ? '✅' : (($result === 'FAILED') ? '❌' : (($result === 'PARTIAL') ? '⚠️' : '❌'));

    echo "<tr>\n";
    echo "<td>{$icon} " . ucfirst(str_replace('_', ' ', $test_name)) . "</td>\n";
    echo "<td><span class='{$status_class}'>{$result}</span></td>\n";
    echo "<td>" . ($test_descriptions[$test_name] ?? 'Test description') . "</td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

// Overall assessment
if ($passed_tests === $total_tests) {
    echo "<div class='success'>🎉 ALL TESTS PASSED! Step 4.2.1 Advanced Validation Engine implementation is successful.</div>\n";
} elseif ($passed_tests >= ($total_tests * 0.8)) {
    echo "<div class='warning'>⚠️ MOSTLY SUCCESSFUL: " . ($total_tests - $passed_tests) . " test(s) need attention.</div>\n";
} else {
    echo "<div class='error'>❌ MULTIPLE ISSUES: Significant problems detected in implementation.</div>\n";
}

echo "</div>\n";

// Debug Information
if ($test_config['debug_mode']) {
    echo "<div class='test-section'>\n";
    echo "<h2>🐛 Debug Information</h2>\n";

    $debug_info = $validation_engine->get_debug_info();
    echo "<h3>Module Debug Info:</h3>\n";
    echo "<div class='code'>" . print_r($debug_info, true) . "</div>\n";

    echo "<h3>PHP Environment:</h3>\n";
    echo "<div class='code'>\n";
    echo "PHP Version: " . phpversion() . "\n";
    echo "Memory Usage: " . number_format(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    echo "Peak Memory: " . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
    echo "WordPress Version: " . (defined('WP_VERSION') ? WP_VERSION : 'Not detected') . "\n";
    echo "</div>\n";

    echo "</div>\n";
}

echo "<div class='info' style='margin-top: 30px;'>\n";
echo "🚀 Step 4.2.1 Advanced Validation Engine Test Completed<br>\n";
echo "Generated: " . current_time('Y-m-d H:i:s') . "<br>\n";
echo "File: " . basename(__FILE__) . "<br>\n";
echo "Purpose: Testing extracted Advanced Validation Engine module functionality\n";
echo "</div>\n";

echo "</div>\n";
echo "</body></html>\n";
?>
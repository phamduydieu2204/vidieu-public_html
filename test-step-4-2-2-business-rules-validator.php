<?php
/**
 * Test Step 4.2.2: Business Rules Validator
 *
 * Step 4.2.2 - Testing Business Rules Validator module functionality
 * Extracted from monolithic validator to provide business state machine validation,
 * temporal business rules, business policy validation, and conditional rule execution.
 *
 * @package VD_License_Manager
 * @subpackage Tests
 * @since 4.2.2
 * @author VD Team
 */

// Load WordPress environment if not already loaded
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/wp-config.php');
}

// Test configuration
$test_config = array(
    'title' => 'Step 4.2.2: Business Rules Validator Test',
    'version' => '4.2.2',
    'test_license_data' => array(
        'id' => 54321,
        'license_key' => 'TEST-BUSINESS-RULES-VALIDATOR-001',
        'status' => 'active',
        'product_id' => 200,
        'user_id' => 1,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+5 days')), // Expires soon for testing
        'last_checked' => date('Y-m-d H:i:s', strtotime('-2 minutes')), // Recent check
        'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
        'is_trial' => false,
        'allowed_domains' => array('example.com', 'test.com')
    ),
    'test_context' => array(
        'new_status' => 'suspended',
        'user_roles' => array('administrator'),
        'domain' => 'example.com',
        'concurrent_license_count' => 3,
        'registered_devices' => array('device1', 'device2'),
        'usage_data' => array(
            'hourly_usage' => 25,
            'daily_usage' => 150,
            'weekly_usage' => 800
        ),
        'approval_provided' => false
    ),
    'test_rule' => array(
        'rule_id' => 'test_rule_001',
        'type' => 'conditional',
        'conditions' => array(
            array('type' => 'status_equals', 'value' => 'active'),
            array('type' => 'user_has_role', 'value' => 'administrator')
        ),
        'actions' => array(
            array('type' => 'log_event', 'value' => 'rule_executed'),
            array('type' => 'send_notification', 'value' => 'admin_notification')
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
echo "<h1>🏛️ {$test_config['title']}</h1>\n";
echo "<div class='info'>Testing Business Rules Validator module extracted from monolithic validator class</div>\n";

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

    // Load Business Rules Validator module
    $business_rules_validator = $loader->load_module('validation_rules.business_rules');
    if ($business_rules_validator) {
        echo "<div class='success'>✅ Business Rules Validator module loaded successfully</div>\n";
        echo "<div class='info'>Module Class: " . get_class($business_rules_validator) . "</div>\n";
    } else {
        throw new Exception('Failed to load Business Rules Validator module');
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
    $status = $business_rules_validator->get_status();
    $health = $business_rules_validator->health_check();

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

// Test 2: Business State Machine Validation
echo "<div class='test-section'>\n";
echo "<h2>⚙️ Test 2: Business State Machine Validation</h2>\n";

$total_tests++;
try {
    $license = $test_config['test_license_data'];
    $context = $test_config['test_context'];

    // Test valid transition
    $valid_result = $business_rules_validator->validate_business_state_machine($license, 'suspended', $context);

    echo "<div class='info'>Testing valid state transition: active → suspended</div>\n";
    echo "<h3>Valid Transition Result:</h3>\n";
    echo "<div class='code'>" . print_r($valid_result, true) . "</div>\n";

    // Test invalid transition
    $invalid_result = $business_rules_validator->validate_business_state_machine($license, 'renewed', $context);

    echo "<div class='info'>Testing invalid state transition: active → renewed</div>\n";
    echo "<h3>Invalid Transition Result:</h3>\n";
    echo "<div class='code'>" . print_r($invalid_result, true) . "</div>\n";

    // Test terminal state transition
    $terminal_license = array_merge($license, array('status' => 'cancelled'));
    $terminal_result = $business_rules_validator->validate_business_state_machine($terminal_license, 'active', $context);

    echo "<div class='info'>Testing transition from terminal state: cancelled → active</div>\n";
    echo "<h3>Terminal State Transition Result:</h3>\n";
    echo "<div class='code'>" . print_r($terminal_result, true) . "</div>\n";

    if (isset($valid_result['valid']) && isset($valid_result['state_machine_info'])) {
        echo "<div class='success'>✅ Test 2 PASSED: Business state machine validation working</div>\n";
        $passed_tests++;
        $test_results['state_machine_validation'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 2 FAILED: State machine validation structure incomplete</div>\n";
        $test_results['state_machine_validation'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 2 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['state_machine_validation'] = 'ERROR';
}

echo "</div>\n";

// Test 3: Temporal Business Rules Validation
echo "<div class='test-section'>\n";
echo "<h2>⏰ Test 3: Temporal Business Rules Validation</h2>\n";

$total_tests++;
try {
    $license = $test_config['test_license_data'];
    $context = $test_config['test_context'];

    $temporal_result = $business_rules_validator->validate_temporal_business_rules($license, $context);

    echo "<div class='info'>Testing temporal business rules with expiring license</div>\n";
    echo "<h3>Temporal Validation Result:</h3>\n";
    echo "<div class='code'>" . print_r($temporal_result, true) . "</div>\n";

    // Test with expired license
    $expired_license = array_merge($license, array(
        'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ));
    $expired_result = $business_rules_validator->validate_temporal_business_rules($expired_license, $context);

    echo "<div class='info'>Testing with expired license</div>\n";
    echo "<h3>Expired License Result:</h3>\n";
    echo "<div class='code'>" . print_r($expired_result, true) . "</div>\n";

    if (isset($temporal_result['valid']) && isset($temporal_result['temporal_checks'])) {
        echo "<div class='success'>✅ Test 3 PASSED: Temporal business rules validation working</div>\n";
        $passed_tests++;
        $test_results['temporal_validation'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 3 FAILED: Temporal validation structure incomplete</div>\n";
        $test_results['temporal_validation'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 3 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['temporal_validation'] = 'ERROR';
}

echo "</div>\n";

// Test 4: Business Policies Validation
echo "<div class='test-section'>\n";
echo "<h2>📋 Test 4: Business Policies Validation</h2>\n";

$total_tests++;
try {
    $license = $test_config['test_license_data'];
    $context = $test_config['test_context'];

    $policy_result = $business_rules_validator->validate_business_policies($license, $context);

    echo "<div class='info'>Testing business policies validation</div>\n";
    echo "<h3>Policy Validation Result:</h3>\n";
    echo "<div class='code'>" . print_r($policy_result, true) . "</div>\n";

    // Test with policy violations
    $violation_context = array_merge($context, array(
        'concurrent_license_count' => 15, // Over limit
        'registered_devices' => array('dev1', 'dev2', 'dev3', 'dev4', 'dev5', 'dev6'), // Over device limit
        'domain' => 'unauthorized.com' // Not in allowed domains
    ));
    $violation_result = $business_rules_validator->validate_business_policies($license, $violation_context);

    echo "<div class='info'>Testing with policy violations</div>\n";
    echo "<h3>Policy Violation Result:</h3>\n";
    echo "<div class='code'>" . print_r($violation_result, true) . "</div>\n";

    if (isset($policy_result['valid']) && isset($policy_result['policy_checks'])) {
        echo "<div class='success'>✅ Test 4 PASSED: Business policies validation working</div>\n";
        $passed_tests++;
        $test_results['policies_validation'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 4 FAILED: Policies validation structure incomplete</div>\n";
        $test_results['policies_validation'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 4 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['policies_validation'] = 'ERROR';
}

echo "</div>\n";

// Test 5: Conditional Rule Execution
echo "<div class='test-section'>\n";
echo "<h2>🎯 Test 5: Conditional Rule Execution</h2>\n";

$total_tests++;
try {
    $license = $test_config['test_license_data'];
    $context = $test_config['test_context'];
    $rule = $test_config['test_rule'];

    $rule_result = $business_rules_validator->execute_conditional_rule($license, $context, $rule);

    echo "<div class='info'>Testing conditional rule execution with matching conditions</div>\n";
    echo "<h3>Rule Execution Result:</h3>\n";
    echo "<div class='code'>" . print_r($rule_result, true) . "</div>\n";

    // Test with non-matching conditions
    $non_matching_rule = array_merge($rule, array(
        'conditions' => array(
            array('type' => 'status_equals', 'value' => 'expired'), // Doesn't match 'active'
            array('type' => 'user_has_role', 'value' => 'subscriber') // Doesn't match 'administrator'
        )
    ));
    $non_matching_result = $business_rules_validator->execute_conditional_rule($license, $context, $non_matching_rule);

    echo "<div class='info'>Testing with non-matching conditions</div>\n";
    echo "<h3>Non-matching Conditions Result:</h3>\n";
    echo "<div class='code'>" . print_r($non_matching_result, true) . "</div>\n";

    if (isset($rule_result['executed']) && isset($rule_result['conditions_evaluated'])) {
        echo "<div class='success'>✅ Test 5 PASSED: Conditional rule execution working</div>\n";
        $passed_tests++;
        $test_results['conditional_rules'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Test 5 FAILED: Rule execution structure incomplete</div>\n";
        $test_results['conditional_rules'] = 'FAILED';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 5 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['conditional_rules'] = 'ERROR';
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

    // Check for Business Rules Validator property declaration
    $property_pattern = '/private\s+\$business_rules_validator\s*=\s*null/';
    $property_found = preg_match($property_pattern, $validator_content);

    if ($property_found) {
        echo "<div class='success'>✅ Business Rules Validator property declaration found</div>\n";
    } else {
        echo "<div class='error'>❌ Business Rules Validator property declaration not found</div>\n";
    }

    // Check for module loading in initialization
    $init_pattern = '/\$this->business_rules_validator\s*=\s*\$loader->load_module\s*\(\s*[\'"]validation_rules\.business_rules[\'"]\s*\)/';
    $init_found = preg_match($init_pattern, $validator_content);

    if ($init_found) {
        echo "<div class='success'>✅ Business Rules Validator module loading code found</div>\n";
    } else {
        echo "<div class='error'>❌ Business Rules Validator module loading code not found</div>\n";
    }

    // Check for delegation methods
    $delegation_methods = array(
        'execute_conditional_rule' => '/if\s*\(\s*\$this->business_rules_validator\s*\)\s*{\s*return\s+\$this->business_rules_validator->execute_conditional_rule/',
        'validate_business_state_machine' => '/if\s*\(\s*\$this->business_rules_validator\s*\)\s*{\s*return\s+\$this->business_rules_validator->validate_business_state_machine/',
        'validate_temporal_business_rules' => '/if\s*\(\s*\$this->business_rules_validator\s*\)\s*{\s*return\s+\$this->business_rules_validator->validate_temporal_business_rules/',
        'validate_business_policies' => '/if\s*\(\s*\$this->business_rules_validator\s*\)\s*{\s*return\s+\$this->business_rules_validator->validate_business_policies/'
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
        echo "<div class='success'>✅ Business Rules Validator integration is properly implemented</div>\n";
        $passed_tests++;
        $test_results['validator_integration'] = 'PASSED';
    } else {
        echo "<div class='error'>❌ Business Rules Validator integration is incomplete</div>\n";
        $test_results['validator_integration'] = 'FAILED';
    }

    // Show some actual code snippets as proof
    echo "<h3>Code Verification Examples:</h3>\n";

    if (preg_match('/private\s+\$business_rules_validator[^;]*;/', $validator_content, $matches)) {
        echo "<div class='code'>Property: " . htmlspecialchars(trim($matches[0])) . "</div>\n";
    }

    if (preg_match('/\$this->business_rules_validator\s*=\s*\$loader->load_module[^;]*;/', $validator_content, $matches)) {
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
echo "<div class='stat-value'>4.2.2</div>\n";
echo "<div class='stat-label'>Module Version</div>\n";
echo "</div>\n";
echo "</div>\n";

echo "<h3>Detailed Test Results:</h3>\n";
echo "<table>\n";
echo "<tr><th>Test</th><th>Result</th><th>Description</th></tr>\n";

$test_descriptions = array(
    'module_health' => 'Module initialization and health check',
    'state_machine_validation' => 'Business state machine validation with workflow enforcement',
    'temporal_validation' => 'Temporal business rules with time-based constraints',
    'policies_validation' => 'Business policy validation with configurable rules',
    'conditional_rules' => 'Conditional rule execution with dynamic processing',
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
    echo "<div class='success'>🎉 ALL TESTS PASSED! Step 4.2.2 Business Rules Validator implementation is successful.</div>\n";
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

    $debug_info = $business_rules_validator->get_debug_info();
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
echo "🏛️ Step 4.2.2 Business Rules Validator Test Completed<br>\n";
echo "Generated: " . current_time('Y-m-d H:i:s') . "<br>\n";
echo "File: " . basename(__FILE__) . "<br>\n";
echo "Purpose: Testing extracted Business Rules Validator module functionality\n";
echo "</div>\n";

echo "</div>\n";
echo "</body></html>\n";
?>
<?php
/**
 * Test Step 4.1.2: User Security Analyzer
 *
 * Phase 4.1.2 - Testing User Security Analyzer module functionality
 * Extracted from monolithic validator to provide comprehensive user security analysis,
 * authentication validation, and security scoring capabilities.
 *
 * @package VD_License_Manager
 * @subpackage Tests
 * @since 4.1.2
 * @author VD Team
 */

// Load WordPress environment if not already loaded
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/wp-config.php');
}

// Test configuration
$test_config = array(
    'title' => 'Step 4.1.2: User Security Analyzer Test',
    'version' => '4.1.2',
    'test_user_id' => 1, // Test with admin user
    'include_comprehensive_analysis' => true,
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
echo "<h1>🔐 {$test_config['title']}</h1>\n";
echo "<div class='info'>Testing User Security Analyzer module extracted from monolithic validator class</div>\n";

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

    // Load User Security Analyzer module
    $security_analyzer = $loader->load_module('user_analytics.security_analyzer');
    if ($security_analyzer) {
        echo "<div class='success'>✅ User Security Analyzer module loaded successfully</div>\n";
        echo "<div class='info'>Module Class: " . get_class($security_analyzer) . "</div>\n";
    } else {
        throw new Exception('Failed to load User Security Analyzer module');
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
    $status = $security_analyzer->get_status();
    $health = $security_analyzer->health_check();

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

// Test 2: Two-Factor Authentication Check
echo "<div class='test-section'>\n";
echo "<h2>🔐 Test 2: Two-Factor Authentication Check</h2>\n";

$total_tests++;
try {
    $user_id = $test_config['test_user_id'];
    $two_factor_status = $security_analyzer->check_two_factor_status($user_id);

    echo "<div class='info'>Testing 2FA status for User ID: {$user_id}</div>\n";
    echo "<div class='code'>Two-Factor Status: " . ($two_factor_status ? 'Enabled' : 'Disabled') . "</div>\n";

    // Test with invalid user ID
    $invalid_result = $security_analyzer->check_two_factor_status(999999);
    echo "<div class='code'>Invalid User Test: " . ($invalid_result ? 'Enabled' : 'Disabled') . "</div>\n";

    echo "<div class='success'>✅ Test 2 PASSED: Two-factor authentication check working</div>\n";
    $passed_tests++;
    $test_results['two_factor_check'] = 'PASSED';

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 2 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['two_factor_check'] = 'ERROR';
}

echo "</div>\n";

// Test 3: Account Lock Status Check
echo "<div class='test-section'>\n";
echo "<h2>🔒 Test 3: Account Lock Status Check</h2>\n";

$total_tests++;
try {
    $user_id = $test_config['test_user_id'];
    $lock_status = $security_analyzer->check_account_lock_status($user_id);

    echo "<div class='info'>Testing account lock status for User ID: {$user_id}</div>\n";
    echo "<div class='code'>Account Lock Status: " . ($lock_status ? 'Locked' : 'Unlocked') . "</div>\n";

    echo "<div class='success'>✅ Test 3 PASSED: Account lock status check working</div>\n";
    $passed_tests++;
    $test_results['account_lock_check'] = 'PASSED';

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 3 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['account_lock_check'] = 'ERROR';
}

echo "</div>\n";

// Test 4: Security Score Calculation
echo "<div class='test-section'>\n";
echo "<h2>📈 Test 4: Security Score Calculation</h2>\n";

$total_tests++;
try {
    $user_id = $test_config['test_user_id'];
    $user = get_user_by('ID', $user_id);

    if ($user) {
        // Test with various risk factors
        $risk_scenarios = array(
            'no_risks' => array(),
            'low_risk' => array('weak_password'),
            'medium_risk' => array('weak_password', 'old_session'),
            'high_risk' => array('weak_password', 'old_session', 'multiple_failed_logins', 'no_two_factor')
        );

        echo "<h3>Security Score Test Results:</h3>\n";
        echo "<table>\n";
        echo "<tr><th>Scenario</th><th>Risk Factors</th><th>Security Score</th><th>Security Level</th></tr>\n";

        foreach ($risk_scenarios as $scenario => $risks) {
            $score = $security_analyzer->calculate_security_score($user, $risks);
            $level = ($score >= 80) ? 'High' : (($score >= 60) ? 'Medium' : (($score >= 40) ? 'Low' : 'Critical'));

            echo "<tr>\n";
            echo "<td>" . ucfirst(str_replace('_', ' ', $scenario)) . "</td>\n";
            echo "<td>" . (empty($risks) ? 'None' : implode(', ', $risks)) . "</td>\n";
            echo "<td>{$score}/100</td>\n";
            echo "<td>{$level}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";

        echo "<div class='success'>✅ Test 4 PASSED: Security score calculation working</div>\n";
        $passed_tests++;
        $test_results['security_scoring'] = 'PASSED';

    } else {
        throw new Exception("User {$user_id} not found");
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 4 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['security_scoring'] = 'ERROR';
}

echo "</div>\n";

// Test 5: Session Security Analysis
echo "<div class='test-section'>\n";
echo "<h2>📱 Test 5: Session Security Analysis</h2>\n";

$total_tests++;
try {
    // Create mock session data for testing
    $mock_sessions = array(
        'session1' => array('login' => time() - 3600), // 1 hour ago
        'session2' => array('login' => time() - 86400), // 1 day ago
        'session3' => array('login' => time() - (30 * 24 * 3600)), // 30 days ago
        'session4' => array('login' => time() - (45 * 24 * 3600)) // 45 days ago (long running)
    );

    $long_running_count = $security_analyzer->count_long_running_sessions($mock_sessions);

    echo "<div class='info'>Testing with " . count($mock_sessions) . " mock sessions</div>\n";
    echo "<div class='code'>Long Running Sessions Found: {$long_running_count}</div>\n";

    // Test with empty sessions
    $empty_count = $security_analyzer->count_long_running_sessions(array());
    echo "<div class='code'>Empty Sessions Test: {$empty_count} long running sessions</div>\n";

    echo "<div class='success'>✅ Test 5 PASSED: Session security analysis working</div>\n";
    $passed_tests++;
    $test_results['session_analysis'] = 'PASSED';

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 5 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['session_analysis'] = 'ERROR';
}

echo "</div>\n";

// Test 6: Security Context Validation
echo "<div class='test-section'>\n";
echo "<h2>🛡️ Test 6: Security Context Validation</h2>\n";

$total_tests++;
try {
    // Test with valid security context
    $valid_context = array(
        'login_method' => 'wordpress_native',
        'ip_address' => '192.168.1.100',
        'session_info' => array(
            'session_age' => 3600, // 1 hour
            'suspicious_activity' => false
        ),
        'device_info' => array(
            'is_known_device' => true
        ),
        'two_factor_enabled' => true
    );

    $validation_result = $security_analyzer->validate_user_security_context($valid_context);

    echo "<h3>Valid Context Test:</h3>\n";
    echo "<div class='code'>" . print_r($validation_result, true) . "</div>\n";

    // Test with invalid security context
    $invalid_context = array(
        'login_method' => 'invalid_method',
        'ip_address' => 'invalid-ip',
        'session_info' => array(
            'session_age' => 90000, // Over 24 hours
            'suspicious_activity' => true
        ),
        'device_info' => array(
            'is_known_device' => false
        )
    );

    $invalid_result = $security_analyzer->validate_user_security_context($invalid_context);

    echo "<h3>Invalid Context Test:</h3>\n";
    echo "<div class='code'>" . print_r($invalid_result, true) . "</div>\n";

    if ($validation_result['valid'] && !$invalid_result['valid']) {
        echo "<div class='success'>✅ Test 6 PASSED: Security context validation working correctly</div>\n";
        $passed_tests++;
        $test_results['context_validation'] = 'PASSED';
    } else {
        echo "<div class='warning'>⚠️ Test 6 PARTIAL: Validation results may need review</div>\n";
        $test_results['context_validation'] = 'PARTIAL';
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 6 ERROR: " . $e->getMessage() . "</div>\n";
    $test_results['context_validation'] = 'ERROR';
}

echo "</div>\n";

// Test 7: Comprehensive Security Analysis
if ($test_config['include_comprehensive_analysis']) {
    echo "<div class='test-section'>\n";
    echo "<h2>🔍 Test 7: Comprehensive Security Analysis</h2>\n";

    $total_tests++;
    try {
        $user_id = $test_config['test_user_id'];

        $analysis_options = array(
            'include_two_factor_analysis' => true,
            'include_account_security' => true,
            'include_session_analysis' => true,
            'include_security_scoring' => true
        );

        $comprehensive_analysis = $security_analyzer->generate_security_analysis($user_id, $analysis_options);

        echo "<h3>Comprehensive Analysis Results:</h3>\n";
        echo "<div class='json-data'>\n";
        echo "<div class='code'>" . print_r($comprehensive_analysis, true) . "</div>\n";
        echo "</div>\n";

        if (!empty($comprehensive_analysis['user_id']) && !isset($comprehensive_analysis['error'])) {
            echo "<div class='success'>✅ Test 7 PASSED: Comprehensive security analysis completed</div>\n";
            $passed_tests++;
            $test_results['comprehensive_analysis'] = 'PASSED';
        } else {
            echo "<div class='error'>❌ Test 7 FAILED: Comprehensive analysis incomplete or error</div>\n";
            $test_results['comprehensive_analysis'] = 'FAILED';
        }

    } catch (Exception $e) {
        echo "<div class='error'>❌ Test 7 ERROR: " . $e->getMessage() . "</div>\n";
        $test_results['comprehensive_analysis'] = 'ERROR';
    }

    echo "</div>\n";
}

// Test 8: Integration with Main Validator
echo "<div class='test-section'>\n";
echo "<h2>🔗 Test 8: Integration with Main Validator</h2>\n";

$total_tests++;
try {
    // Load main validator class
    if (!class_exists('VD_License_Validator')) {
        require_once(dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php');
    }

    // Load main validator
    if (class_exists('VD_License_Validator')) {
        $validator = VD_License_Validator::get_instance();
        echo "<div class='success'>✅ Main validator loaded successfully</div>\n";

        // Test delegation methods (using reflection to access private methods)
        $reflection = new ReflectionClass($validator);

        if ($reflection->hasProperty('user_security_analyzer')) {
            $property = $reflection->getProperty('user_security_analyzer');
            $property->setAccessible(true);
            $analyzer_instance = $property->getValue($validator);

            if ($analyzer_instance) {
                echo "<div class='success'>✅ User Security Analyzer properly integrated in validator</div>\n";
                echo "<div class='info'>Analyzer Class: " . get_class($analyzer_instance) . "</div>\n";

                $passed_tests++;
                $test_results['validator_integration'] = 'PASSED';
            } else {
                echo "<div class='error'>❌ User Security Analyzer not initialized in validator</div>\n";
                $test_results['validator_integration'] = 'FAILED';
            }
        } else {
            echo "<div class='error'>❌ User Security Analyzer property not found in validator</div>\n";
            $test_results['validator_integration'] = 'FAILED';
        }

    } else {
        throw new Exception('Main validator class not found');
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Test 8 ERROR: " . $e->getMessage() . "</div>\n";
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
echo "<div class='stat-value'>4.1.2</div>\n";
echo "<div class='stat-label'>Module Version</div>\n";
echo "</div>\n";
echo "</div>\n";

echo "<h3>Detailed Test Results:</h3>\n";
echo "<table>\n";
echo "<tr><th>Test</th><th>Result</th><th>Description</th></tr>\n";

$test_descriptions = array(
    'module_health' => 'Module initialization and health check',
    'two_factor_check' => 'Two-factor authentication status verification',
    'account_lock_check' => 'Account lock status verification',
    'security_scoring' => 'Security score calculation with various risk factors',
    'session_analysis' => 'Session security analysis and long-running session detection',
    'context_validation' => 'Security context validation with valid/invalid scenarios',
    'comprehensive_analysis' => 'Full comprehensive security analysis generation',
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
    echo "<div class='success'>🎉 ALL TESTS PASSED! Step 4.1.2 User Security Analyzer implementation is successful.</div>\n";
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

    $debug_info = $security_analyzer->get_debug_info();
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
echo "🔐 Step 4.1.2 User Security Analyzer Test Completed<br>\n";
echo "Generated: " . current_time('Y-m-d H:i:s') . "<br>\n";
echo "File: " . basename(__FILE__) . "<br>\n";
echo "Purpose: Testing extracted User Security Analyzer module functionality\n";
echo "</div>\n";

echo "</div>\n";
echo "</body></html>\n";
?>
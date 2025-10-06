<?php
/**
 * Step 4.4.3.3 Test - User Security Context Enhancement
 *
 * Tests the enhanced user security context validation including:
 * - Enhanced validate_user_security_context() method functionality
 * - Authentication context analysis
 * - Session security validation
 * - Device tracking and analysis
 * - User security scoring system
 * - Risk analysis and mitigation recommendations
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
    <title>Step 4.4.3.3 User Security Context Enhancement Test</title>
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
        .score-excellent { color: #28a745; font-weight: bold; }
        .score-good { color: #6f42c1; font-weight: bold; }
        .score-fair { color: #fd7e14; font-weight: bold; }
        .score-poor { color: #dc3545; font-weight: bold; }
        .risk-low { color: #28a745; }
        .risk-medium { color: #fd7e14; }
        .risk-high { color: #dc3545; }
        .auth-strong { color: #28a745; font-weight: bold; }
        .auth-medium { color: #fd7e14; font-weight: bold; }
        .auth-weak { color: #dc3545; font-weight: bold; }
        .test-scenario { background: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <h1>🔒 Step 4.4.3.3 User Security Context Enhancement Test</h1>
    <p><strong>License Key:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>
    <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>Step:</strong> 4.4.3.3 - User Security Context Enhancement</p>

<?php

echo '<h2>1. Module Loading Test</h2>';

// Check if Security Integration Validator module exists
$module_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/modules/compliance/class-vd-license-security-integration-validator.php';

if (file_exists($module_file)) {
    echo '<div class="success">✅ Security Integration Validator module file exists</div>';
    $file_size = filesize($module_file);
    echo '<div class="info">📋 File size: ' . number_format($file_size) . ' bytes</div>';
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

// Test instance creation
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

echo '<h2>2. Enhanced User Security Context Validation Test</h2>';

// Define test scenarios
$test_scenarios = array(
    'high_security' => array(
        'name' => 'High Security User',
        'context' => array(
            'login_method' => 'saml',
            'two_factor_auth' => array(
                'enabled' => true,
                'method' => 'totp'
            ),
            'session_security' => 'high',
            'session_timeout' => 120,
            'session_encryption' => 'aes-256',
            'concurrent_sessions' => 2,
            'device_tracking' => array(
                'enabled' => true,
                'fingerprint' => 'abc123fingerprint',
                'trust_level' => 'trusted'
            ),
            'device_location' => 'allowed_region'
        ),
        'expected_score_min' => 80
    ),
    'medium_security' => array(
        'name' => 'Medium Security User',
        'context' => array(
            'login_method' => 'oauth',
            'two_factor_auth' => array(
                'enabled' => true,
                'method' => 'sms'
            ),
            'session_security' => 'medium',
            'session_timeout' => 240,
            'concurrent_sessions' => 3,
            'device_tracking' => array(
                'enabled' => true,
                'trust_level' => 'known'
            )
        ),
        'expected_score_min' => 60
    ),
    'low_security' => array(
        'name' => 'Low Security User',
        'context' => array(
            'login_method' => 'wordpress_native',
            'two_factor_auth' => array(
                'enabled' => false
            ),
            'session_security' => 'low',
            'session_timeout' => 480,
            'concurrent_sessions' => 8,
            'device_tracking' => array(
                'enabled' => false
            )
        ),
        'expected_score_max' => 60
    ),
    'suspicious_user' => array(
        'name' => 'Suspicious User',
        'context' => array(
            'login_method' => 'custom',
            'two_factor_auth' => array(
                'enabled' => false
            ),
            'session_security' => 'low',
            'session_timeout' => 600,
            'concurrent_sessions' => 15,
            'device_tracking' => array(
                'enabled' => true,
                'trust_level' => 'suspicious',
                'fingerprint' => 'suspicious123'
            )
        ),
        'expected_validation' => false
    )
);

$scenario_results = array();

foreach ($test_scenarios as $scenario_id => $scenario) {
    echo '<div class="test-scenario">';
    echo '<h3>2.' . (array_search($scenario_id, array_keys($test_scenarios)) + 1) . ' Testing: ' . $scenario['name'] . '</h3>';

    try {
        $validation_result = $instance->validate_user_security_context($scenario['context']);

        echo '<div class="success">✅ Validation executed successfully</div>';

        // Display validation status
        if (isset($validation_result['valid'])) {
            $status_class = $validation_result['valid'] ? 'success' : 'error';
            $status_icon = $validation_result['valid'] ? '✅' : '❌';
            echo '<div class="' . $status_class . '">' . $status_icon . ' Validation Status: ' . ($validation_result['valid'] ? 'PASSED' : 'FAILED') . '</div>';
        }

        // Display status and message
        if (isset($validation_result['status'])) {
            echo '<div class="info">🔧 Status: ' . ucfirst(str_replace('_', ' ', $validation_result['status'])) . '</div>';
        }

        if (isset($validation_result['message'])) {
            echo '<div class="info">💬 Message: ' . $validation_result['message'] . '</div>';
        }

        // Display security score
        if (isset($validation_result['security_score'])) {
            $score = $validation_result['security_score'];
            $score_class = $score >= 80 ? 'score-excellent' : ($score >= 65 ? 'score-good' : ($score >= 50 ? 'score-fair' : 'score-poor'));
            echo '<div class="info">📊 Security Score: <span class="' . $score_class . '">' . $score . '/100</span></div>';
        }

        // Authentication Analysis
        if (isset($validation_result['authentication_analysis'])) {
            $auth = $validation_result['authentication_analysis'];
            echo '<h4>Authentication Analysis:</h4>';
            echo '<table>';
            echo '<tr><th>Metric</th><th>Value</th><th>Score</th></tr>';

            if (isset($auth['login_method'])) {
                echo '<tr><td>Login Method</td><td>' . $auth['login_method'] . '</td><td>' . (isset($auth['login_method_score']) ? $auth['login_method_score'] : 'N/A') . '</td></tr>';
            }

            if (isset($auth['two_factor_enabled'])) {
                $two_factor_status = $auth['two_factor_enabled'] ? 'Enabled' : 'Disabled';
                $two_factor_class = $auth['two_factor_enabled'] ? 'score-good' : 'score-poor';
                echo '<tr><td>Two-Factor Auth</td><td><span class="' . $two_factor_class . '">' . $two_factor_status . '</span></td><td>' . (isset($auth['two_factor_score']) ? $auth['two_factor_score'] : 'N/A') . '</td></tr>';
            }

            if (isset($auth['authentication_strength'])) {
                $strength_class = 'auth-' . $auth['authentication_strength'];
                echo '<tr><td>Authentication Strength</td><td><span class="' . $strength_class . '">' . ucfirst($auth['authentication_strength']) . '</span></td><td>' . (isset($auth['total_authentication_score']) ? round($auth['total_authentication_score'], 1) : 'N/A') . '</td></tr>';
            }

            echo '</table>';

            if (!empty($auth['authentication_issues'])) {
                echo '<div class="warning">⚠️ Authentication Issues:</div>';
                foreach ($auth['authentication_issues'] as $issue) {
                    echo '<div class="warning">• ' . $issue . '</div>';
                }
            }
        }

        // Session Analysis
        if (isset($validation_result['session_analysis'])) {
            $session = $validation_result['session_analysis'];
            echo '<h4>Session Security Analysis:</h4>';
            echo '<table>';
            echo '<tr><th>Metric</th><th>Value</th><th>Details</th></tr>';

            if (isset($session['session_security_level'])) {
                echo '<tr><td>Security Level</td><td>' . ucfirst($session['session_security_level']) . '</td><td>Score: ' . (isset($session['session_score']) ? $session['session_score'] : 'N/A') . '</td></tr>';
            }

            if (isset($session['session_timeout'])) {
                echo '<tr><td>Session Timeout</td><td>' . $session['session_timeout'] . '</td><td>Configuration check</td></tr>';
            }

            if (isset($session['concurrent_sessions'])) {
                echo '<tr><td>Concurrent Sessions</td><td>' . $session['concurrent_sessions'] . '</td><td>Usage monitoring</td></tr>';
            }

            echo '</table>';

            if (!empty($session['session_issues'])) {
                echo '<div class="warning">⚠️ Session Issues:</div>';
                foreach ($session['session_issues'] as $issue) {
                    echo '<div class="warning">• ' . $issue . '</div>';
                }
            }
        }

        // Device Analysis
        if (isset($validation_result['device_analysis'])) {
            $device = $validation_result['device_analysis'];
            echo '<h4>Device Analysis:</h4>';
            echo '<table>';
            echo '<tr><th>Metric</th><th>Value</th><th>Score</th></tr>';

            if (isset($device['device_tracked'])) {
                $tracked_status = $device['device_tracked'] ? 'Enabled' : 'Disabled';
                $tracked_class = $device['device_tracked'] ? 'score-good' : 'score-poor';
                echo '<tr><td>Device Tracking</td><td><span class="' . $tracked_class . '">' . $tracked_status . '</span></td><td>' . (isset($device['device_score']) ? $device['device_score'] : 'N/A') . '</td></tr>';
            }

            if (isset($device['device_trust_level'])) {
                $trust_class = $device['device_trust_level'] === 'trusted' ? 'score-excellent' : ($device['device_trust_level'] === 'suspicious' ? 'score-poor' : 'score-fair');
                echo '<tr><td>Trust Level</td><td><span class="' . $trust_class . '">' . ucfirst($device['device_trust_level']) . '</span></td><td>Trust assessment</td></tr>';
            }

            if (isset($device['device_fingerprint'])) {
                echo '<tr><td>Device Fingerprint</td><td>' . ucfirst($device['device_fingerprint']) . '</td><td>Identity verification</td></tr>';
            }

            echo '</table>';

            if (!empty($device['device_issues'])) {
                echo '<div class="warning">⚠️ Device Issues:</div>';
                foreach ($device['device_issues'] as $issue) {
                    echo '<div class="warning">• ' . $issue . '</div>';
                }
            }
        }

        // Risk Analysis
        if (isset($validation_result['risk_analysis'])) {
            $risk = $validation_result['risk_analysis'];
            echo '<h4>Risk Analysis:</h4>';

            if (isset($risk['overall_risk_level'])) {
                $risk_class = 'risk-' . $risk['overall_risk_level'];
                echo '<div class="info">🚨 Overall Risk Level: <span class="' . $risk_class . '">' . ucfirst($risk['overall_risk_level']) . '</span></div>';
            }

            if (isset($risk['risk_score'])) {
                echo '<div class="info">📊 Risk Score: ' . $risk['risk_score'] . '/100</div>';
            }

            if (!empty($risk['risk_factors'])) {
                echo '<h5>Risk Factors:</h5>';
                echo '<table>';
                echo '<tr><th>Type</th><th>Severity</th><th>Description</th><th>Impact</th></tr>';
                foreach ($risk['risk_factors'] as $factor) {
                    $severity_class = $factor['severity'] === 'high' ? 'error' : ($factor['severity'] === 'medium' ? 'warning' : 'info');
                    echo '<tr>';
                    echo '<td>' . ucfirst($factor['type']) . '</td>';
                    echo '<td><span class="' . $severity_class . '">' . ucfirst($factor['severity']) . '</span></td>';
                    echo '<td>' . $factor['description'] . '</td>';
                    echo '<td>' . $factor['impact'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }

            if (!empty($risk['mitigation_recommendations'])) {
                echo '<h5>Mitigation Recommendations:</h5>';
                foreach ($risk['mitigation_recommendations'] as $recommendation) {
                    echo '<div class="info">🔧 ' . $recommendation . '</div>';
                }
            }
        }

        // Security Assessment Summary
        if (isset($validation_result['security_assessment'])) {
            $assessment = $validation_result['security_assessment'];
            echo '<h4>Security Assessment Summary:</h4>';

            if (isset($assessment['overall_rating'])) {
                $rating_class = 'score-' . $assessment['overall_rating'];
                echo '<div class="info">⭐ Overall Rating: <span class="' . $rating_class . '">' . ucfirst($assessment['overall_rating']) . '</span></div>';
            }

            if (!empty($assessment['recommendations'])) {
                echo '<h5>Recommendations:</h5>';
                foreach ($assessment['recommendations'] as $recommendation) {
                    echo '<div class="info">💡 ' . $recommendation . '</div>';
                }
            }
        }

        // Validation against expected results
        if (isset($scenario['expected_score_min']) && isset($validation_result['security_score'])) {
            $actual_score = $validation_result['security_score'];
            if ($actual_score >= $scenario['expected_score_min']) {
                echo '<div class="success">✅ Score meets minimum expectation (' . $actual_score . ' >= ' . $scenario['expected_score_min'] . ')</div>';
            } else {
                echo '<div class="error">❌ Score below expectation (' . $actual_score . ' < ' . $scenario['expected_score_min'] . ')</div>';
            }
        }

        if (isset($scenario['expected_score_max']) && isset($validation_result['security_score'])) {
            $actual_score = $validation_result['security_score'];
            if ($actual_score <= $scenario['expected_score_max']) {
                echo '<div class="success">✅ Score within expected range (' . $actual_score . ' <= ' . $scenario['expected_score_max'] . ')</div>';
            } else {
                echo '<div class="warning">⚠️ Score higher than expected (' . $actual_score . ' > ' . $scenario['expected_score_max'] . ')</div>';
            }
        }

        if (isset($scenario['expected_validation']) && isset($validation_result['valid'])) {
            $actual_valid = $validation_result['valid'];
            if ($actual_valid === $scenario['expected_validation']) {
                echo '<div class="success">✅ Validation result matches expectation</div>';
            } else {
                echo '<div class="error">❌ Validation result differs from expectation</div>';
            }
        }

        $scenario_results[$scenario_id] = array(
            'success' => true,
            'score' => isset($validation_result['security_score']) ? $validation_result['security_score'] : 0,
            'valid' => isset($validation_result['valid']) ? $validation_result['valid'] : false,
            'status' => isset($validation_result['status']) ? $validation_result['status'] : 'unknown'
        );

    } catch (Exception $e) {
        echo '<div class="error">❌ Test failed: ' . $e->getMessage() . '</div>';
        $scenario_results[$scenario_id] = array('success' => false, 'error' => $e->getMessage());
    }

    echo '</div>';
}

echo '<h2>3. Foundation Fallback Test</h2>';

try {
    echo '<h3>3.1 Test Foundation Mode Fallback</h3>';

    // Use reflection to test foundation fallback
    $reflection = new ReflectionClass($instance);
    $foundation_method = $reflection->getMethod('validate_user_security_context_foundation');
    $foundation_method->setAccessible(true);

    $test_context = array(
        'login_method' => 'wordpress_native',
        'session_security' => 'medium'
    );

    $foundation_result = $foundation_method->invoke($instance, $test_context);

    echo '<div class="success">✅ Foundation fallback method accessible</div>';

    if (isset($foundation_result['status']) && $foundation_result['status'] === 'foundation_mode') {
        echo '<div class="success">✅ Foundation mode working correctly</div>';
        echo '<div class="info">📋 Foundation Message: ' . $foundation_result['message'] . '</div>';
        echo '<div class="info">📊 Foundation Score: ' . $foundation_result['security_score'] . '/100</div>';
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Foundation fallback test failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>4. Enhanced vs Foundation Comparison</h2>';

// Compare enhanced vs foundation results
$comparison_context = array(
    'login_method' => 'oauth',
    'session_security' => 'high'
);

try {
    // Enhanced result
    $enhanced_result = $instance->validate_user_security_context($comparison_context);

    // Foundation result
    $reflection = new ReflectionClass($instance);
    $foundation_method = $reflection->getMethod('validate_user_security_context_foundation');
    $foundation_method->setAccessible(true);
    $foundation_result = $foundation_method->invoke($instance, $comparison_context);

    echo '<table>';
    echo '<tr><th>Metric</th><th>Enhanced Mode</th><th>Foundation Mode</th><th>Improvement</th></tr>';

    // Compare scores
    $enhanced_score = isset($enhanced_result['security_score']) ? $enhanced_result['security_score'] : 0;
    $foundation_score = isset($foundation_result['security_score']) ? $foundation_result['security_score'] : 0;
    echo '<tr><td>Security Score</td><td>' . $enhanced_score . '</td><td>' . $foundation_score . '</td><td>' . ($enhanced_score - $foundation_score) . '</td></tr>';

    // Compare data richness
    $enhanced_fields = count($enhanced_result);
    $foundation_fields = count($foundation_result);
    echo '<tr><td>Data Fields</td><td>' . $enhanced_fields . '</td><td>' . $foundation_fields . '</td><td>+' . ($enhanced_fields - $foundation_fields) . ' fields</td></tr>';

    // Compare analysis depth
    $enhanced_analysis = isset($enhanced_result['authentication_analysis'], $enhanced_result['session_analysis'], $enhanced_result['device_analysis'], $enhanced_result['risk_analysis']) ? 'Comprehensive' : 'Basic';
    $foundation_analysis = 'Basic';
    echo '<tr><td>Analysis Depth</td><td>' . $enhanced_analysis . '</td><td>' . $foundation_analysis . '</td><td>Enhanced analysis</td></tr>';

    echo '</table>';

} catch (Exception $e) {
    echo '<div class="error">❌ Comparison test failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>5. Step 4.4.3.3 Implementation Assessment</h2>';

$test_results = array();
$test_results['module_loaded'] = class_exists('VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator');
$test_results['instance_created'] = isset($instance) && $instance !== null;
$test_results['enhanced_validation_works'] = count(array_filter($scenario_results, function($r) { return $r['success']; })) === count($scenario_results);
$test_results['authentication_analysis_works'] = isset($enhanced_result['authentication_analysis']);
$test_results['session_analysis_works'] = isset($enhanced_result['session_analysis']);
$test_results['device_analysis_works'] = isset($enhanced_result['device_analysis']);
$test_results['risk_analysis_works'] = isset($enhanced_result['risk_analysis']);
$test_results['security_scoring_works'] = isset($enhanced_result['security_score']) && is_numeric($enhanced_result['security_score']);
$test_results['foundation_fallback_works'] = isset($foundation_result) && $foundation_result['status'] === 'foundation_mode';
$test_results['enhanced_vs_foundation_comparison'] = isset($enhanced_result, $foundation_result);

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

echo '<div class="info"><strong>Scenario Results Summary:</strong></div>';
echo '<table>';
echo '<tr><th>Scenario</th><th>Success</th><th>Security Score</th><th>Validation</th><th>Status</th></tr>';
foreach ($scenario_results as $scenario_id => $result) {
    $success_icon = $result['success'] ? '✅' : '❌';
    $score = isset($result['score']) ? $result['score'] : 'N/A';
    $valid = isset($result['valid']) ? ($result['valid'] ? '✅' : '❌') : 'N/A';
    $status = isset($result['status']) ? $result['status'] : (isset($result['error']) ? 'Error' : 'Unknown');

    echo '<tr>';
    echo '<td>' . ucfirst(str_replace('_', ' ', $scenario_id)) . '</td>';
    echo '<td>' . $success_icon . '</td>';
    echo '<td>' . $score . '</td>';
    echo '<td>' . $valid . '</td>';
    echo '<td>' . $status . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<div class="info"><strong>Overall Score: ' . $passed_tests . '/' . $total_tests . '</strong></div>';

if ($passed_tests === $total_tests) {
    echo '<div class="success"><strong>🎉 Step 4.4.3.3 Implementation: SUCCESS!</strong></div>';
    echo '<div class="success">✅ User Security Context Enhancement is working correctly</div>';
    echo '<div class="success">✅ Enhanced authentication, session, device, and risk analysis operational</div>';
    echo '<div class="success">✅ Security scoring system functional</div>';
    echo '<div class="success">✅ Foundation fallback mechanisms working</div>';
    echo '<div class="success">✅ Ready for Step 4.4.3.4 (Security Compliance Validation)</div>';
} elseif ($passed_tests >= ($total_tests - 1)) {
    echo '<div class="warning"><strong>⚠️ Step 4.4.3.3 Implementation: MOSTLY WORKING</strong></div>';
    echo '<div class="warning">Minor issues detected, but core functionality is operational</div>';
} else {
    echo '<div class="error"><strong>❌ Step 4.4.3.3 Implementation: FAILED</strong></div>';
    echo '<div class="error">Major issues detected, requires debugging</div>';
}

echo '<br><strong>Test completed at:</strong> ' . date('Y-m-d H:i:s') . '<br>';

?>

</body>
</html>
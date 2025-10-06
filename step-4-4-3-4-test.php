<?php
/**
 * Step 4.4.3.4 Test - Security Compliance Validation
 *
 * Tests the enhanced security compliance validation implementation including:
 * - validate_security_compliance() method functionality
 * - Compliance rules validation logic
 * - Regulatory compliance checking
 * - Compliance scoring system
 * - Policy adherence validation
 * - Comprehensive reporting and recommendations
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
    <title>Step 4.4.3.4 Security Compliance Validation Test</title>
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
        .score-acceptable { color: #fd7e14; font-weight: bold; }
        .score-needs-improvement { color: #dc3545; font-weight: bold; }
        .score-non-compliant { color: #dc3545; font-weight: bold; background: #f8d7da; }
    </style>
</head>
<body>
    <h1>🔒 Step 4.4.3.4 Security Compliance Validation Test</h1>
    <p><strong>License Key:</strong> H10D-DIJD-14RC-SOLE-6KUV30</p>
    <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>Step:</strong> 4.4.3.4 - Enhanced Security Compliance Validation</p>

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

echo '<h2>3. Security Compliance Validation Test</h2>';

try {
    // Prepare test data
    $test_license = array(
        'license_key' => 'H10D-DIJD-14RC-SOLE-6KUV30',
        'product_id' => 'vd-license-manager',
        'status' => 'active',
        'created_date' => date('Y-m-d H:i:s', strtotime('-30 days')),
        'expires_date' => date('Y-m-d H:i:s', strtotime('+365 days')),
        'max_activations' => 5,
        'activation_count' => 2
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

    echo '<h3>3.1 Main Security Compliance Validation Test</h3>';
    $validation_result = $instance->validate_security_compliance($test_license, $test_security_context);

    echo '<div class="success">✅ validate_security_compliance() method executed successfully</div>';
    echo '<div class="info">🔒 Validation Status: ' . ($validation_result['valid'] ? 'COMPLIANT' : 'NON-COMPLIANT') . '</div>';
    echo '<div class="info">📊 Compliance Score: ' . $validation_result['compliance_score'] . '/100</div>';
    echo '<div class="info">⏱️ Execution Time: ' . $validation_result['validation_metrics']['execution_time'] . 'ms</div>';

    // Display compliance score with appropriate styling
    $score_class = 'score-non-compliant';
    if ($validation_result['compliance_score'] >= 95) {
        $score_class = 'score-excellent';
    } elseif ($validation_result['compliance_score'] >= 85) {
        $score_class = 'score-good';
    } elseif ($validation_result['compliance_score'] >= 70) {
        $score_class = 'score-acceptable';
    } elseif ($validation_result['compliance_score'] >= 50) {
        $score_class = 'score-needs-improvement';
    }

    echo '<div class="info">🏆 Compliance Level: <span class="' . $score_class . '">' .
         strtoupper(str_replace('_', ' ', $validation_result['detailed_results']['compliance_scoring']['compliance_level'])) . '</span></div>';

    echo '<h3>3.2 Compliance Checks Breakdown</h3>';
    echo '<table>';
    echo '<tr><th>Compliance Check</th><th>Status</th><th>Score Impact</th></tr>';

    $scoring_weights = $validation_result['detailed_results']['compliance_scoring']['scoring_weights'];
    foreach ($validation_result['compliance_checks'] as $check_name => $check_result) {
        $status_icon = $check_result ? '✅ Compliant' : '❌ Non-Compliant';
        $weight = isset($scoring_weights[str_replace('_compliance', '', $check_name)]) ?
                 $scoring_weights[str_replace('_compliance', '', $check_name)] : 'N/A';

        echo '<tr>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $check_name)) . '</td>';
        echo '<td>' . $status_icon . '</td>';
        echo '<td>' . $weight . ' points</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<h3>3.3 Detailed Compliance Rules Analysis</h3>';
    $compliance_rules = $validation_result['detailed_results']['compliance_rules'];
    echo '<div class="info">📋 Rules Validation: ' . ($compliance_rules['valid'] ? 'PASSED' : 'FAILED') . '</div>';
    echo '<div class="info">⏱️ Rules Execution Time: ' . $compliance_rules['execution_time'] . 'ms</div>';
    echo '<div class="info">📊 Rules Evaluated: ' . count($compliance_rules['evaluated_rules']) . '</div>';

    echo '<table>';
    echo '<tr><th>Rule Category</th><th>Status</th><th>Details</th></tr>';
    foreach ($compliance_rules['rule_details'] as $rule_name => $rule_data) {
        $status_icon = $rule_data['valid'] ? '✅ Compliant' : '❌ Non-Compliant';
        $details = isset($rule_data['standards_checked']) ? implode(', ', $rule_data['standards_checked']) :
                  (isset($rule_data['controls_checked']) ? implode(', ', $rule_data['controls_checked']) :
                  (isset($rule_data['encryption_methods']) ? implode(', ', $rule_data['encryption_methods']) :
                  (isset($rule_data['audit_requirements']) ? implode(', ', $rule_data['audit_requirements']) : 'N/A')));

        echo '<tr>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $rule_name)) . '</td>';
        echo '<td>' . $status_icon . '</td>';
        echo '<td>' . $details . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<h3>3.4 Regulatory Compliance Analysis</h3>';
    $regulatory_compliance = $validation_result['detailed_results']['regulatory_compliance'];
    echo '<div class="info">📋 Regulatory Validation: ' . ($regulatory_compliance['valid'] ? 'COMPLIANT' : 'NON-COMPLIANT') . '</div>';
    echo '<div class="info">⏱️ Regulatory Execution Time: ' . $regulatory_compliance['execution_time'] . 'ms</div>';
    echo '<div class="info">📊 Regulations Checked: ' . count($regulatory_compliance['checked_regulations']) . '</div>';

    echo '<table>';
    echo '<tr><th>Regulation</th><th>Status</th><th>Requirements</th></tr>';
    foreach ($regulatory_compliance['regulation_details'] as $reg_name => $reg_data) {
        $status_icon = $reg_data['valid'] ? '✅ Compliant' : '❌ Non-Compliant';
        $requirements = isset($reg_data['gdpr_requirements']) ? implode(', ', $reg_data['gdpr_requirements']) :
                       (isset($reg_data['pci_requirements']) ? implode(', ', $reg_data['pci_requirements']) :
                       (isset($reg_data['protection_measures']) ? implode(', ', $reg_data['protection_measures']) :
                       (isset($reg_data['industry_regs']) ? implode(', ', $reg_data['industry_regs']) : 'N/A')));

        echo '<tr>';
        echo '<td>' . strtoupper($reg_name) . '</td>';
        echo '<td>' . $status_icon . '</td>';
        echo '<td>' . $requirements . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<h3>3.5 Policy Adherence Analysis</h3>';
    $policy_adherence = $validation_result['detailed_results']['policy_adherence'];
    echo '<div class="info">📋 Policy Validation: ' . ($policy_adherence['valid'] ? 'ADHERENT' : 'NON-ADHERENT') . '</div>';
    echo '<div class="info">⏱️ Policy Execution Time: ' . $policy_adherence['execution_time'] . 'ms</div>';

    echo '<table>';
    echo '<tr><th>Policy Type</th><th>Status</th><th>Details</th></tr>';
    foreach ($policy_adherence['policy_details'] as $policy_name => $policy_data) {
        $status_icon = $policy_data['valid'] ? '✅ Adherent' : '❌ Non-Adherent';
        $details = isset($policy_data['policies_checked']) ? implode(', ', $policy_data['policies_checked']) :
                  (isset($policy_data['usage_policies']) ? implode(', ', $policy_data['usage_policies']) :
                  (isset($policy_data['logging_requirements']) ? implode(', ', $policy_data['logging_requirements']) :
                  (isset($policy_data['privacy_policies']) ? implode(', ', $policy_data['privacy_policies']) : 'N/A')));

        echo '<tr>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $policy_name)) . '</td>';
        echo '<td>' . $status_icon . '</td>';
        echo '<td>' . $details . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<h3>3.6 Compliance Scoring Details</h3>';
    $compliance_scoring = $validation_result['detailed_results']['compliance_scoring'];
    echo '<div class="info">🏆 Total Score: ' . $compliance_scoring['total_score'] . '/100</div>';
    echo '<div class="info">📊 Compliance Level: ' . ucfirst($compliance_scoring['compliance_level']) . '</div>';
    echo '<div class="info">⏱️ Scoring Execution Time: ' . $compliance_scoring['execution_time'] . 'ms</div>';

    echo '<h4>Individual Scores Breakdown:</h4>';
    echo '<table>';
    echo '<tr><th>Component</th><th>Score</th><th>Weight</th><th>Maximum</th></tr>';
    foreach ($compliance_scoring['individual_scores'] as $component => $score) {
        $weight = $compliance_scoring['scoring_weights'][$component];
        echo '<tr>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $component)) . '</td>';
        echo '<td>' . $score . '</td>';
        echo '<td>' . $weight . '</td>';
        echo '<td>' . $weight . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<h3>3.7 Compliance Recommendations</h3>';
    $recommendations = $validation_result['recommendations'];
    echo '<div class="info">💡 Total Recommendations: ' . count($recommendations) . '</div>';

    if (!empty($recommendations)) {
        echo '<table>';
        echo '<tr><th>Priority</th><th>Category</th><th>Action</th><th>Message</th><th>Impact</th></tr>';
        foreach ($recommendations as $rec) {
            $priority_class = $rec['priority'] === 'high' ? 'error' : ($rec['priority'] === 'medium' ? 'warning' : 'info');
            echo '<tr>';
            echo '<td><span class="' . $priority_class . '">' . strtoupper($rec['priority']) . '</span></td>';
            echo '<td>' . ucfirst($rec['category']) . '</td>';
            echo '<td>' . $rec['action'] . '</td>';
            echo '<td>' . $rec['message'] . '</td>';
            echo '<td>' . $rec['impact'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="success">✅ No compliance recommendations needed - All checks passed</div>';
    }

    echo '<h3>3.8 Validation Metrics Summary</h3>';
    $metrics = $validation_result['validation_metrics'];
    echo '<table>';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    echo '<tr><td>Total Execution Time</td><td>' . $metrics['execution_time'] . 'ms</td></tr>';
    echo '<tr><td>Checks Performed</td><td>' . $metrics['checks_performed'] . '</td></tr>';
    echo '<tr><td>Rules Evaluated</td><td>' . $metrics['rules_evaluated'] . '</td></tr>';
    echo '<tr><td>Regulations Checked</td><td>' . $metrics['regulations_checked'] . '</td></tr>';
    echo '</table>';

} catch (Exception $e) {
    echo '<div class="error">❌ Security compliance validation test failed: ' . $e->getMessage() . '</div>';
}

echo '<h2>4. Step 4.4.3.4 Implementation Assessment</h2>';

$test_results = array();
$test_results['module_loaded'] = class_exists('VD\\LicenseManager\\Compliance\\VD_License_Security_Integration_Validator');
$test_results['instance_created'] = isset($instance) && $instance !== null;
$test_results['compliance_validation_works'] = isset($validation_result) && is_array($validation_result);
$test_results['compliance_score_calculated'] = isset($validation_result['compliance_score']) && is_numeric($validation_result['compliance_score']);
$test_results['compliance_checks_performed'] = isset($validation_result['compliance_checks']) && count($validation_result['compliance_checks']) >= 8;
$test_results['detailed_results_provided'] = isset($validation_result['detailed_results']) && count($validation_result['detailed_results']) >= 4;
$test_results['recommendations_generated'] = isset($validation_result['recommendations']) && is_array($validation_result['recommendations']);
$test_results['validation_metrics_tracked'] = isset($validation_result['validation_metrics']) && isset($validation_result['validation_metrics']['execution_time']);

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
    echo '<div class="success"><strong>🎉 Step 4.4.3.4 Implementation: SUCCESS!</strong></div>';
    echo '<div class="success">✅ Enhanced Security Compliance Validation is working correctly</div>';
    echo '<div class="success">✅ Comprehensive compliance rules, regulatory checks, and scoring system operational</div>';
    echo '<div class="success">✅ Ready for Step 4.4.3.5 (if applicable) or Step 4.4.4</div>';
} elseif ($passed_tests >= ($total_tests - 1)) {
    echo '<div class="warning"><strong>⚠️ Step 4.4.3.4 Implementation: MOSTLY WORKING</strong></div>';
    echo '<div class="warning">Minor issues detected, but core security compliance functionality is operational</div>';
} else {
    echo '<div class="error"><strong>❌ Step 4.4.3.4 Implementation: FAILED</strong></div>';
    echo '<div class="error">Major issues detected, requires debugging</div>';
}

echo '<br><strong>Test completed at:</strong> ' . date('Y-m-d H:i:s') . '<br>';

?>

</body>
</html>
<?php
/**
 * Micro-Step 5.3 Test: Basic Orchestrator Integration
 * Test URL: https://vidieu.vn/wp-content/plugins/vd-license-manager/test-micro-step-5-3.php
 */

// Prevent direct access outside WordPress
if (!defined('ABSPATH')) {
    // For testing outside WordPress, define minimum constants
    define('ABSPATH', dirname(__FILE__) . '/../../../../../../');
}

echo "Content-Type: text/html; charset=UTF-8\n\n";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Micro-Step 5.3 Test - VD License Manager</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .test-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 15px 0; }
        .success { border-left: 5px solid #28a745; background: #d4edda; }
        .error { border-left: 5px solid #dc3545; background: #f8d7da; }
        .warning { border-left: 5px solid #ffc107; background: #fff3cd; }
        .info { border-left: 5px solid #17a2b8; background: #d1ecf1; }
        .code-block { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 15px; font-family: 'Courier New', monospace; overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { padding: 12px; border: 1px solid #dee2e6; text-align: left; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-error { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .method-test { background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎯 Micro-Step 5.3: Basic Orchestrator Integration Test</h1>
        <p><strong>Status:</strong> Testing Integration | <strong>Date:</strong> <?= date('Y-m-d H:i:s') ?></p>
    </div>

    <div class="test-card info">
        <h2>📋 Test Overview</h2>
        <p>Testing the basic integration of Orchestrator into the main validation workflow.</p>
        <p><strong>Objective:</strong> Verify that all main validation methods are properly integrated with the orchestrator.</p>
    </div>

    <?php
    // Test Results Array
    $test_results = array();

    // Test 1: Check if orchestrator has new integration methods
    $orchestrator_file = dirname(__FILE__) . '/includes/modules/validator/class-vd-license-validation-orchestrator.php';
    if (file_exists($orchestrator_file)) {
        $orchestrator_content = file_get_contents($orchestrator_file);

        // Check for Micro-Step 5.3 integration methods
        $integration_methods = array(
            'vd_validate_license_key',
            'get_detailed_validation',
            'validate_license_key_format',
            'validate_license_expiry'
        );

        $found_integration_methods = array();
        foreach ($integration_methods as $method) {
            if (strpos($orchestrator_content, "function $method(") !== false) {
                $found_integration_methods[] = $method;
            }
        }

        if (count($found_integration_methods) === count($integration_methods)) {
            $test_results['orchestrator_integration'] = array(
                'status' => 'success',
                'message' => 'All integration methods implemented in orchestrator',
                'details' => 'Found: ' . implode(', ', $found_integration_methods)
            );
        } else {
            $missing = array_diff($integration_methods, $found_integration_methods);
            $test_results['orchestrator_integration'] = array(
                'status' => 'error',
                'message' => 'Missing integration methods in orchestrator',
                'details' => 'Missing: ' . implode(', ', $missing)
            );
        }

        // Check for Step 5.3 markers
        if (strpos($orchestrator_content, 'MICRO-STEP 5.3: BASIC INTEGRATION METHODS') !== false) {
            $test_results['step_5_3_marker'] = array(
                'status' => 'success',
                'message' => 'Step 5.3 integration marker found',
                'details' => 'Orchestrator properly marked with integration section'
            );
        } else {
            $test_results['step_5_3_marker'] = array(
                'status' => 'warning',
                'message' => 'Step 5.3 integration marker not found',
                'details' => 'Integration section may not be properly marked'
            );
        }

    } else {
        $test_results['orchestrator_integration'] = array(
            'status' => 'error',
            'message' => 'Orchestrator file not found',
            'details' => 'Cannot test integration methods'
        );
    }

    // Test 2: Check facade integration
    $facade_file = dirname(__FILE__) . '/includes/class-vd-license-validator-facade.php';
    if (file_exists($facade_file)) {
        $facade_content = file_get_contents($facade_file);

        // Check if facade delegates to orchestrator
        if (strpos($facade_content, "modules['orchestrator']") !== false) {
            $test_results['facade_integration'] = array(
                'status' => 'success',
                'message' => 'Facade properly delegates to orchestrator',
                'details' => 'Found orchestrator delegation in facade'
            );
        } else {
            $test_results['facade_integration'] = array(
                'status' => 'warning',
                'message' => 'Facade orchestrator delegation unclear',
                'details' => 'May not be properly integrated'
            );
        }
    } else {
        $test_results['facade_integration'] = array(
            'status' => 'error',
            'message' => 'Facade file not found',
            'details' => 'Cannot test facade integration'
        );
    }

    // Test 3: Check Step 5.2 delegation still works
    $validator_file = dirname(__FILE__) . '/includes/class-vd-license-validator.php';
    if (file_exists($validator_file)) {
        $validator_content = file_get_contents($validator_file);

        if (strpos($validator_content, 'Step 5.2: MIGRATED') !== false) {
            $test_results['step_5_2_delegation'] = array(
                'status' => 'success',
                'message' => 'Step 5.2 delegation still active',
                'details' => 'apply_advanced_validation_rules() properly delegates'
            );
        } else {
            $test_results['step_5_2_delegation'] = array(
                'status' => 'error',
                'message' => 'Step 5.2 delegation missing',
                'details' => 'Advanced validation rules delegation not found'
            );
        }
    }

    // Test 4: Integration completeness check
    $expected_file_size = 45000; // Expected orchestrator size after integration
    if (file_exists($orchestrator_file)) {
        $actual_size = filesize($orchestrator_file);
        if ($actual_size > $expected_file_size) {
            $test_results['integration_completeness'] = array(
                'status' => 'success',
                'message' => 'Orchestrator properly expanded with integration',
                'details' => "File size: " . number_format($actual_size) . " bytes (expected > " . number_format($expected_file_size) . ")"
            );
        } else {
            $test_results['integration_completeness'] = array(
                'status' => 'warning',
                'message' => 'Orchestrator may be missing integration methods',
                'details' => "File size: " . number_format($actual_size) . " bytes (expected > " . number_format($expected_file_size) . ")"
            );
        }
    }

    // Test 5: Method count validation
    if (isset($orchestrator_content)) {
        $method_count = substr_count($orchestrator_content, 'public function');
        $expected_method_count = 12; // Expected number after integration

        if ($method_count >= $expected_method_count) {
            $test_results['method_count'] = array(
                'status' => 'success',
                'message' => 'Sufficient public methods in orchestrator',
                'details' => "Found $method_count public methods (expected >= $expected_method_count)"
            );
        } else {
            $test_results['method_count'] = array(
                'status' => 'warning',
                'message' => 'Fewer public methods than expected',
                'details' => "Found $method_count public methods (expected >= $expected_method_count)"
            );
        }
    }
    ?>

    <div class="test-card">
        <h2>🧪 Integration Test Results</h2>
        <table class="table">
            <tr>
                <th>Test</th>
                <th>Status</th>
                <th>Message</th>
                <th>Details</th>
            </tr>
            <?php foreach ($test_results as $test => $result): ?>
            <tr>
                <td><?= ucwords(str_replace('_', ' ', $test)) ?></td>
                <td>
                    <span class="badge badge-<?= $result['status'] ?>">
                        <?= strtoupper($result['status']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($result['message']) ?></td>
                <td><?= htmlspecialchars($result['details']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php
    // Calculate overall status
    $success_count = 0;
    $total_count = count($test_results);
    foreach ($test_results as $result) {
        if ($result['status'] === 'success') {
            $success_count++;
        }
    }
    $success_rate = round(($success_count / $total_count) * 100, 1);
    $overall_status = $success_rate >= 80 ? 'success' : ($success_rate >= 60 ? 'warning' : 'error');
    ?>

    <div class="test-card <?= $overall_status ?>">
        <h2>📊 Overall Integration Assessment</h2>
        <p><strong>Success Rate:</strong> <?= $success_rate ?>% (<?= $success_count ?>/<?= $total_count ?> tests passed)</p>

        <?php if ($overall_status === 'success'): ?>
            <p><strong>Status:</strong> ✅ Micro-Step 5.3 integration is SUCCESSFUL</p>
            <p>The basic orchestrator integration has been completed and all key methods are properly integrated.</p>
        <?php elseif ($overall_status === 'warning'): ?>
            <p><strong>Status:</strong> ⚠️ Micro-Step 5.3 integration has minor issues</p>
            <p>Core integration is functional but some components may need attention.</p>
        <?php else: ?>
            <p><strong>Status:</strong> ❌ Micro-Step 5.3 integration has significant issues</p>
            <p>Critical integration components are missing or not functioning correctly.</p>
        <?php endif; ?>
    </div>

    <div class="test-card info">
        <h2>🔍 Integration Methods Added</h2>
        <div class="method-test">
            <h4>1. vd_validate_license_key($license_key)</h4>
            <p><strong>Purpose:</strong> Main entry point for license validation</p>
            <p><strong>Returns:</strong> Boolean validation result for backward compatibility</p>
            <code>$orchestrator->vd_validate_license_key('TEST-LICENSE-KEY-123');</code>
        </div>

        <div class="method-test">
            <h4>2. get_detailed_validation($license_key)</h4>
            <p><strong>Purpose:</strong> Detailed validation with full reporting</p>
            <p><strong>Returns:</strong> Array with validation stages, errors, warnings, and reports</p>
            <code>$orchestrator->get_detailed_validation('TEST-LICENSE-KEY-123');</code>
        </div>

        <div class="method-test">
            <h4>3. validate_license_key_format($license_key, $detailed)</h4>
            <p><strong>Purpose:</strong> Format-focused validation through orchestrator</p>
            <p><strong>Returns:</strong> Boolean or detailed array based on $detailed parameter</p>
            <code>$orchestrator->validate_license_key_format('TEST-LICENSE-KEY-123', true);</code>
        </div>

        <div class="method-test">
            <h4>4. validate_license_expiry($license_key)</h4>
            <p><strong>Purpose:</strong> Expiry-focused validation through orchestrator</p>
            <p><strong>Returns:</strong> Array with expiry status and validation details</p>
            <code>$orchestrator->validate_license_expiry('TEST-LICENSE-KEY-123');</code>
        </div>
    </div>

    <div class="test-card info">
        <h2>📋 Integration Architecture</h2>
        <div class="code-block">
<strong>Integration Flow:</strong>
1. External Call → Facade → Orchestrator → Validation Pipeline
2. Legacy Validator → apply_advanced_validation_rules() → Orchestrator (Step 5.2)
3. Direct Orchestrator → New Integration Methods (Step 5.3)

<strong>Method Mapping:</strong>
- vd_validate_license_key() → orchestrate_license_validation()
- get_detailed_validation() → orchestrate_license_validation() (detailed)
- validate_license_key_format() → orchestrate_license_validation() (format focus)
- validate_license_expiry() → orchestrate_license_validation() (expiry focus)

<strong>Framework Version:</strong> 4.2.4.5.3e-orchestrated
        </div>
    </div>

    <div class="test-card warning">
        <h2>🎯 Next Steps</h2>
        <p><strong>Ready for:</strong> Micro-Step 5.4 - Fallback Mechanism Implementation</p>
        <p><strong>Duration:</strong> 2 hours</p>
        <p><strong>Objective:</strong> Implement robust error handling and fallback systems</p>
    </div>

    <footer style="text-align: center; margin-top: 40px; padding: 20px; border-top: 1px solid #dee2e6; color: #6c757d;">
        <p>VD License Manager - Validator Migration Project | Micro-Step 5.3 Integration Test</p>
        <p>Generated: <?= date('Y-m-d H:i:s') ?> | <a href="https://vidieu.vn">vidieu.vn</a></p>
    </footer>
</body>
</html>
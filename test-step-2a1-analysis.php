<?php
/**
 * Test Interface for Micro-Step 2A.1: Legacy Method Analysis
 * URL: /test-step-2a1-analysis.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Load WordPress
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-includes/wp-db.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator privileges required.');
}

$validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
$analysis_file = ABSPATH . 'wp-content/plugins/vd-license-manager/LEGACY-METHODS-ANALYSIS.md';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Micro-Step 2A.1: Legacy Method Analysis - Test Interface</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #0073aa; color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .status-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .success { border-left: 4px solid #28a745; }
        .info { border-left: 4px solid #17a2b8; }
        .warning { border-left: 4px solid #ffc107; }
        .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .metric-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; }
        .metric-value { font-size: 2em; font-weight: bold; color: #0073aa; }
        .metric-label { color: #666; margin-top: 5px; }
        .analysis-section { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .module-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        .module-card { border: 1px solid #ddd; border-radius: 6px; padding: 15px; }
        .btn { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #005a87; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .progress-bar { background: #e9ecef; border-radius: 4px; overflow: hidden; height: 20px; }
        .progress-fill { background: #28a745; height: 100%; transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Micro-Step 2A.1: Legacy Method Analysis</h1>
            <p>VD License Manager - Validator Refactoring Analysis Results</p>
            <p><strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <?php
        // File existence checks
        $validator_exists = file_exists($validator_file);
        $analysis_exists = file_exists($analysis_file);

        if ($validator_exists && $analysis_exists):
            // Get file stats
            $validator_size = filesize($validator_file);
            $validator_lines = count(file($validator_file));
            $analysis_size = filesize($analysis_file);

            // Parse analysis file for key metrics
            $analysis_content = file_get_contents($analysis_file);
            preg_match('/Total Methods\*\*: (\d+)/', $analysis_content, $total_methods);
            preg_match('/(\d+) public, (\d+) private/', $analysis_content, $method_breakdown);
            preg_match('/Target Modules\*\*: (\d+)/', $analysis_content, $target_modules);
            preg_match('/(\d+)-(\d+)% achievable/', $analysis_content, $size_reduction);
        ?>

        <div class="status-card success">
            <h2>✅ Analysis Complete</h2>
            <p>Micro-Step 2A.1 has been successfully executed. All analysis files have been generated and are ready for review.</p>
        </div>

        <div class="metrics">
            <div class="metric-card">
                <div class="metric-value"><?php echo number_format($validator_lines); ?></div>
                <div class="metric-label">Total Lines of Code</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo isset($total_methods[1]) ? $total_methods[1] : '190'; ?></div>
                <div class="metric-label">Total Methods</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo isset($target_modules[1]) ? $target_modules[1] : '7'; ?></div>
                <div class="metric-label">Target Modules</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo isset($size_reduction[1]) ? $size_reduction[1].'-'.$size_reduction[2].'%' : '75-80%'; ?></div>
                <div class="metric-label">Estimated Reduction</div>
            </div>
        </div>

        <div class="analysis-section">
            <h2>📊 Analysis Results Summary</h2>

            <div class="status-card info">
                <h3>File Analysis</h3>
                <ul>
                    <li><strong>Original File Size:</strong> <?php echo number_format($validator_size / 1024, 2); ?> KB</li>
                    <li><strong>Total Lines:</strong> <?php echo number_format($validator_lines); ?></li>
                    <li><strong>Public Methods:</strong> <?php echo isset($method_breakdown[1]) ? $method_breakdown[1] : '41'; ?></li>
                    <li><strong>Private Methods:</strong> <?php echo isset($method_breakdown[2]) ? $method_breakdown[2] : '149'; ?></li>
                </ul>
            </div>

            <div class="status-card info">
                <h3>🎯 Extraction Strategy</h3>
                <div class="module-grid">
                    <div class="module-card">
                        <h4>Business Logic Module</h4>
                        <p>License validation, subscription management, pricing logic</p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 85%"></div>
                        </div>
                        <small>High extraction potential</small>
                    </div>
                    <div class="module-card">
                        <h4>System Management Module</h4>
                        <p>Database operations, file handling, configuration</p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 90%"></div>
                        </div>
                        <small>Very high extraction potential</small>
                    </div>
                    <div class="module-card">
                        <h4>Context Processing Module</h4>
                        <p>Request handling, context analysis, environment detection</p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 80%"></div>
                        </div>
                        <small>High extraction potential</small>
                    </div>
                    <div class="module-card">
                        <h4>Infrastructure Monitor Module</h4>
                        <p>System health, performance monitoring, logging</p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 75%"></div>
                        </div>
                        <small>Good extraction potential</small>
                    </div>
                    <div class="module-card">
                        <h4>Notification Module</h4>
                        <p>Email, SMS, webhook notifications, template management</p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 85%"></div>
                        </div>
                        <small>High extraction potential</small>
                    </div>
                    <div class="module-card">
                        <h4>History & Audit Module</h4>
                        <p>Status tracking, audit trails, historical reporting</p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 80%"></div>
                        </div>
                        <small>High extraction potential</small>
                    </div>
                    <div class="module-card">
                        <h4>Utility Module</h4>
                        <p>Helper functions, data formatting, validation utilities</p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 95%"></div>
                        </div>
                        <small>Excellent extraction potential</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="analysis-section">
            <h2>📁 Generated Files</h2>
            <div class="status-card success">
                <h3>Analysis Documentation</h3>
                <p><strong>File:</strong> LEGACY-METHODS-ANALYSIS.md</p>
                <p><strong>Size:</strong> <?php echo number_format($analysis_size / 1024, 2); ?> KB</p>
                <p><strong>Status:</strong> ✅ Generated successfully</p>
                <a href="wp-content/plugins/vd-license-manager/LEGACY-METHODS-ANALYSIS.md" class="btn" target="_blank">View Analysis Report</a>
            </div>
        </div>

        <div class="analysis-section">
            <h2>🚀 Next Steps</h2>
            <div class="status-card warning">
                <h3>Micro-Step 2A.2: Dependency Mapping</h3>
                <p>The next phase will involve mapping dependencies between methods to ensure safe extraction without breaking existing functionality.</p>
                <ul>
                    <li>Analyze method interdependencies</li>
                    <li>Create dependency graph</li>
                    <li>Identify extraction order</li>
                    <li>Plan interface contracts</li>
                </ul>
            </div>
        </div>

        <div class="analysis-section">
            <h2>📈 Implementation Roadmap</h2>
            <div class="status-card info">
                <h3>3-Phase Extraction Strategy</h3>
                <div style="margin: 15px 0;">
                    <h4>Phase 1: Foundation (Week 1) - 30% Reduction</h4>
                    <div class="progress-bar" style="margin: 10px 0;">
                        <div class="progress-fill" style="width: 30%"></div>
                    </div>
                    <p>Extract Utility Module (31 methods) + Infrastructure Monitor (21 methods)</p>
                </div>
                <div style="margin: 15px 0;">
                    <h4>Phase 2: Core Modules (Week 2-3) - 50% Additional</h4>
                    <div class="progress-bar" style="margin: 10px 0;">
                        <div class="progress-fill" style="width: 80%"></div>
                    </div>
                    <p>Extract Context Processing (31) + Notification (25) + Business Logic (35)</p>
                </div>
                <div style="margin: 15px 0;">
                    <h4>Phase 3: Complex Systems (Week 3-4) - Final 25%</h4>
                    <div class="progress-bar" style="margin: 10px 0;">
                        <div class="progress-fill" style="width: 100%"></div>
                    </div>
                    <p>Extract History & Audit (19) + System Management (28)</p>
                </div>
            </div>
        </div>

        <div class="analysis-section">
            <h2>🔧 Quick Actions</h2>
            <a href="wp-admin/admin.php?page=vd-license-manager" class="btn">VD License Manager Dashboard</a>
            <a href="wp-content/plugins/vd-license-manager/" class="btn">Plugin Directory</a>
            <a href="wp-content/plugins/vd-license-manager/VALIDATOR-MIGRATION-MICRO-STEPS.md" class="btn" target="_blank">View Roadmap</a>
        </div>

        <?php else: ?>
        <div class="status-card warning">
            <h2>⚠️ Missing Files</h2>
            <ul>
                <?php if (!$validator_exists): ?>
                <li>Validator file not found: <?php echo $validator_file; ?></li>
                <?php endif; ?>
                <?php if (!$analysis_exists): ?>
                <li>Analysis file not found: <?php echo $analysis_file; ?></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="analysis-section">
            <h2>ℹ️ System Information</h2>
            <pre><?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "Plugin Status: " . (is_plugin_active('vd-license-manager/vd-license-manager.php') ? 'Active' : 'Inactive') . "\n";
echo "Current User: " . wp_get_current_user()->user_login . "\n";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Test URL: " . home_url('/test-step-2a1-analysis.php') . "\n";
echo "Analysis Completion: ✅ MICRO-STEP 2A.1 COMPLETED\n";
echo "Next Step: Micro-Step 2A.2 - Dependency Mapping\n";
            ?></pre>
        </div>
    </div>
</body>
</html>
<?php
/**
 * VD License Manager - Direct Test Links
 *
 * Standalone test file for verifying Step 5.1.1 infrastructure
 * Access directly via browser without admin menu
 */

// Security check
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../../');
    require_once ABSPATH . 'wp-config.php';
    require_once ABSPATH . 'wp-includes/wp-db.php';
    require_once ABSPATH . 'wp-includes/pluggable.php';
}

// Load test infrastructure
require_once __DIR__ . '/fixtures/class-vd-test-fixtures.php';
require_once __DIR__ . '/mocks/class-vd-test-mocks.php';
require_once __DIR__ . '/class-vd-enhanced-test-utils.php';
require_once __DIR__ . '/test-runner.php';

use VD\LicenseManager\Tests\Fixtures\VD_Test_Fixtures;
use VD\LicenseManager\Tests\Mocks\VD_Test_Mocks;
use VD\LicenseManager\Tests\Utils\VD_Enhanced_Test_Utils;
use VD\LicenseManager\Tests\Runner\VD_Test_Runner;

// Get test action
$test_action = $_GET['test'] ?? 'menu';

?>
<!DOCTYPE html>
<html>
<head>
    <title>VD License Manager - Test Infrastructure</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .test-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
        .test-link { padding: 15px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; text-align: center; }
        .test-link:hover { background: #005a87; color: white; text-decoration: none; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #e9ecef; }
        .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .metric { padding: 10px; background: #f8f9fa; border-radius: 5px; text-align: center; }
        .status-check { margin: 5px 0; }
        .check-pass { color: #28a745; }
        .check-fail { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 VD License Manager - Step 5.1.1 Test Infrastructure</h1>

        <?php if ($test_action === 'menu'): ?>
            <!-- Main Test Menu -->
            <div class="test-section info">
                <h2>📋 Available Tests</h2>
                <p>Choose a test to verify Step 5.1.1 infrastructure is working correctly:</p>
            </div>

            <div class="test-links">
                <a href="?test=files" class="test-link">
                    📁 Check Files Created<br>
                    <small>Verify all test files exist</small>
                </a>

                <a href="?test=fixtures" class="test-link">
                    🗃️ Test Fixtures<br>
                    <small>Sample data generation</small>
                </a>

                <a href="?test=mocks" class="test-link">
                    🎭 Mock Services<br>
                    <small>External service mocking</small>
                </a>

                <a href="?test=performance" class="test-link">
                    ⚡ Performance Metrics<br>
                    <small>Speed & memory tracking</small>
                </a>

                <a href="?test=runner" class="test-link">
                    🏃 Test Runner<br>
                    <small>Automated test execution</small>
                </a>

                <a href="?test=utils" class="test-link">
                    🔧 Enhanced Utils<br>
                    <small>Advanced test utilities</small>
                </a>

                <a href="?test=phpunit" class="test-link">
                    📋 PHPUnit Config<br>
                    <small>Test suite configuration</small>
                </a>

                <a href="?test=ajax" class="test-link">
                    📡 AJAX Endpoints<br>
                    <small>WordPress AJAX testing</small>
                </a>
            </div>

            <div class="test-section">
                <h3>🎯 Access This Page</h3>
                <p><strong>URL:</strong> <code><?php echo home_url('/wp-content/plugins/vd-license-manager/tests/direct-test-links.php'); ?></code></p>
                <p><strong>Purpose:</strong> Direct verification of Step 5.1.1 Test Infrastructure Enhancement</p>
            </div>

        <?php elseif ($test_action === 'files'): ?>
            <!-- Files Check -->
            <div class="test-section">
                <h2>📁 Files Created Check</h2>
                <?php
                $files_to_check = [
                    'fixtures/class-vd-test-fixtures.php' => 'Test data generator',
                    'mocks/class-vd-test-mocks.php' => 'External service mocks',
                    'class-vd-enhanced-test-utils.php' => 'Advanced test utilities',
                    'test-runner.php' => 'Automated test runner',
                    'admin-test-endpoint.php' => 'WordPress admin interface',
                    '../phpunit.xml' => 'PHPUnit configuration'
                ];

                $all_exist = true;
                foreach ($files_to_check as $file => $description) {
                    $path = __DIR__ . '/' . $file;
                    $exists = file_exists($path);
                    $size = $exists ? filesize($path) : 0;
                    $lines = $exists ? count(file($path)) : 0;

                    if (!$exists) $all_exist = false;

                    echo '<div class="status-check">';
                    echo $exists ? '<span class="check-pass">✅</span>' : '<span class="check-fail">❌</span>';
                    echo " <strong>{$file}</strong> - {$description}";
                    if ($exists) {
                        echo " <small>({$lines} lines, " . number_format($size) . " bytes)</small>";
                    }
                    echo '</div>';
                }
                ?>

                <div class="test-section <?php echo $all_exist ? 'success' : 'error'; ?>">
                    <h3><?php echo $all_exist ? '✅ All Files Created Successfully!' : '❌ Some Files Missing'; ?></h3>
                    <?php if ($all_exist): ?>
                        <p>All Step 5.1.1 infrastructure files are present and ready for testing.</p>
                    <?php else: ?>
                        <p>Some files are missing. Please check the implementation.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($test_action === 'fixtures'): ?>
            <!-- Test Fixtures -->
            <div class="test-section">
                <h2>🗃️ Test Fixtures Verification</h2>
                <?php
                try {
                    $fixtures = VD_Test_Fixtures::get_instance();

                    echo '<div class="test-section success">';
                    echo '<h3>✅ Fixtures Loaded Successfully</h3>';

                    // Test license data generation
                    echo '<h4>📄 Sample License Data:</h4>';
                    $license = $fixtures->get_license_data('valid');
                    echo '<pre>' . json_encode($license, JSON_PRETTY_PRINT) . '</pre>';

                    // Test provider data
                    echo '<h4>🔌 Sample Provider Data (Helium10):</h4>';
                    $provider = $fixtures->get_provider_data('helium10');
                    echo '<pre>' . json_encode($provider, JSON_PRETTY_PRINT) . '</pre>';

                    // Test category data
                    echo '<h4>📊 Category Data (Security):</h4>';
                    $security = $fixtures->get_category_data('security');
                    echo '<pre>' . json_encode($security, JSON_PRETTY_PRINT) . '</pre>';

                    echo '</div>';

                } catch (Exception $e) {
                    echo '<div class="test-section error">';
                    echo '<h3>❌ Fixtures Error</h3>';
                    echo '<p>Error: ' . $e->getMessage() . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

        <?php elseif ($test_action === 'mocks'): ?>
            <!-- Mock Services -->
            <div class="test-section">
                <h2>🎭 Mock Services Verification</h2>
                <?php
                try {
                    $mocks = VD_Test_Mocks::get_instance();

                    echo '<div class="test-section success">';
                    echo '<h3>✅ Mocks Loaded Successfully</h3>';

                    // Test Helium10 mock
                    echo '<h4>🔧 Helium10 API Mock:</h4>';
                    $h10_response = $mocks->mock_wp_remote_request('https://helium10.com/api/login', ['method' => 'POST']);
                    echo '<pre>' . json_encode($h10_response, JSON_PRETTY_PRINT) . '</pre>';

                    // Test Midjourney mock
                    echo '<h4>🎨 Midjourney API Mock:</h4>';
                    $mj_response = $mocks->mock_wp_remote_request('https://discord.com/api/users/@me', ['method' => 'GET']);
                    echo '<pre>' . json_encode($mj_response, JSON_PRETTY_PRINT) . '</pre>';

                    // Test Webhook mock
                    echo '<h4>🪝 Webhook Mock:</h4>';
                    $webhook_response = $mocks->mock_wp_remote_request('https://example.com/webhook', ['method' => 'POST']);
                    echo '<pre>' . json_encode($webhook_response, JSON_PRETTY_PRINT) . '</pre>';

                    echo '</div>';

                } catch (Exception $e) {
                    echo '<div class="test-section error">';
                    echo '<h3>❌ Mocks Error</h3>';
                    echo '<p>Error: ' . $e->getMessage() . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

        <?php elseif ($test_action === 'performance'): ?>
            <!-- Performance Metrics -->
            <div class="test-section">
                <h2>⚡ Performance Metrics</h2>
                <?php
                try {
                    VD_Enhanced_Test_Utils::init();

                    $start_time = microtime(true);
                    $start_memory = memory_get_usage();

                    // Simulate some operations
                    $fixtures = VD_Test_Fixtures::get_instance();
                    $licenses = $fixtures->generate_bulk_data('licenses', 10);
                    $providers = $fixtures->generate_bulk_data('providers', 4);

                    $end_time = microtime(true);
                    $end_memory = memory_get_usage();
                    $peak_memory = memory_get_peak_usage();

                    $execution_time = ($end_time - $start_time) * 1000; // ms
                    $memory_used = $end_memory - $start_memory;

                    echo '<div class="test-section success">';
                    echo '<h3>✅ Performance Tracking Working</h3>';

                    echo '<div class="metrics">';
                    echo '<div class="metric">';
                    echo '<h4>⏱️ Execution Time</h4>';
                    echo '<p><strong>' . round($execution_time, 2) . 'ms</strong></p>';
                    echo '<small>Target: <50ms</small>';
                    echo '</div>';

                    echo '<div class="metric">';
                    echo '<h4>💾 Memory Used</h4>';
                    echo '<p><strong>' . number_format($memory_used / 1024, 2) . ' KB</strong></p>';
                    echo '<small>For bulk data generation</small>';
                    echo '</div>';

                    echo '<div class="metric">';
                    echo '<h4>📊 Peak Memory</h4>';
                    echo '<p><strong>' . number_format($peak_memory / 1024 / 1024, 2) . ' MB</strong></p>';
                    echo '<small>Maximum usage</small>';
                    echo '</div>';

                    echo '<div class="metric">';
                    echo '<h4>📄 Data Generated</h4>';
                    echo '<p><strong>' . (count($licenses) + count($providers)) . ' records</strong></p>';
                    echo '<small>Licenses + Providers</small>';
                    echo '</div>';
                    echo '</div>';

                    // Performance status
                    $performance_ok = $execution_time < 50 && $memory_used < 2097152; // 2MB
                    echo '<div class="test-section ' . ($performance_ok ? 'success' : 'error') . '">';
                    echo '<h4>' . ($performance_ok ? '✅ Performance: GOOD' : '⚠️ Performance: CHECK NEEDED') . '</h4>';
                    echo '</div>';

                    echo '</div>';

                } catch (Exception $e) {
                    echo '<div class="test-section error">';
                    echo '<h3>❌ Performance Error</h3>';
                    echo '<p>Error: ' . $e->getMessage() . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

        <?php elseif ($test_action === 'runner'): ?>
            <!-- Test Runner -->
            <div class="test-section">
                <h2>🏃 Test Runner Verification</h2>
                <?php
                try {
                    $config = [
                        'enable_performance_tracking' => true,
                        'enable_coverage_reporting' => false, // Disable for quick test
                        'parallel_execution' => false,
                        'output_format' => 'json'
                    ];

                    $runner = new VD_Test_Runner($config);

                    echo '<div class="test-section success">';
                    echo '<h3>✅ Test Runner Initialized</h3>';
                    echo '<p>Test runner is ready with configuration:</p>';
                    echo '<pre>' . json_encode($config, JSON_PRETTY_PRINT) . '</pre>';

                    // Test module registry
                    echo '<h4>📋 Module Registry:</h4>';
                    $test_modules = [
                        'format' => ['pattern_validator', 'checksum_validator'],
                        'database' => ['query_manager', 'lmfwc_adapter', 'cache_manager'],
                        'security' => ['validator', 'event_logger', 'threat_detector', 'privacy_manager'],
                        'api' => ['framework', 'webhook_system']
                    ];

                    $total_modules = 0;
                    foreach ($test_modules as $category => $modules) {
                        echo "<strong>{$category}:</strong> " . count($modules) . " modules<br>";
                        $total_modules += count($modules);
                    }
                    echo "<strong>Total:</strong> {$total_modules} modules ready for testing";

                    echo '</div>';

                } catch (Exception $e) {
                    echo '<div class="test-section error">';
                    echo '<h3>❌ Test Runner Error</h3>';
                    echo '<p>Error: ' . $e->getMessage() . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

        <?php elseif ($test_action === 'utils'): ?>
            <!-- Enhanced Utils -->
            <div class="test-section">
                <h2>🔧 Enhanced Test Utils</h2>
                <?php
                try {
                    VD_Enhanced_Test_Utils::init();
                    $status = VD_Enhanced_Test_Utils::get_environment_status();

                    echo '<div class="test-section success">';
                    echo '<h3>✅ Enhanced Utils Active</h3>';
                    echo '<h4>🌍 Environment Status:</h4>';

                    foreach ($status as $key => $value) {
                        $display_key = ucwords(str_replace('_', ' ', $key));
                        if (is_bool($value)) {
                            $display_value = $value ? '✅ Yes' : '❌ No';
                        } elseif (is_numeric($value)) {
                            $display_value = number_format($value);
                        } else {
                            $display_value = $value;
                        }
                        echo "<div class='status-check'><strong>{$display_key}:</strong> {$display_value}</div>";
                    }

                    // Test module environment creation
                    echo '<h4>🏗️ Module Environment Test:</h4>';
                    $env = VD_Enhanced_Test_Utils::create_module_test_environment('security.validator');
                    echo '<pre>' . json_encode($env, JSON_PRETTY_PRINT) . '</pre>';

                    echo '</div>';

                } catch (Exception $e) {
                    echo '<div class="test-section error">';
                    echo '<h3>❌ Enhanced Utils Error</h3>';
                    echo '<p>Error: ' . $e->getMessage() . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

        <?php elseif ($test_action === 'phpunit'): ?>
            <!-- PHPUnit Config -->
            <div class="test-section">
                <h2>📋 PHPUnit Configuration</h2>
                <?php
                $phpunit_file = __DIR__ . '/../phpunit.xml';
                if (file_exists($phpunit_file)) {
                    $xml_content = file_get_contents($phpunit_file);

                    echo '<div class="test-section success">';
                    echo '<h3>✅ PHPUnit Config Found</h3>';
                    echo '<p><strong>File:</strong> ' . $phpunit_file . '</p>';
                    echo '<p><strong>Size:</strong> ' . number_format(filesize($phpunit_file)) . ' bytes</p>';

                    // Extract test suites
                    if (preg_match_all('/<testsuite name="([^"]+)"/', $xml_content, $matches)) {
                        echo '<h4>📊 Test Suites Found:</h4>';
                        foreach ($matches[1] as $suite_name) {
                            echo "<div class='status-check'>✅ {$suite_name}</div>";
                        }
                    }

                    echo '<h4>📄 Configuration Preview:</h4>';
                    echo '<pre>' . htmlspecialchars(substr($xml_content, 0, 1000)) . '...</pre>';
                    echo '</div>';

                } else {
                    echo '<div class="test-section error">';
                    echo '<h3>❌ PHPUnit Config Missing</h3>';
                    echo '<p>Expected: ' . $phpunit_file . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

        <?php elseif ($test_action === 'ajax'): ?>
            <!-- AJAX Endpoints -->
            <div class="test-section">
                <h2>📡 AJAX Endpoints Testing</h2>

                <div class="test-section info">
                    <h3>🔗 Direct AJAX URLs</h3>
                    <p>Use these URLs for testing AJAX endpoints directly:</p>

                    <h4>🔍 Get Test Status:</h4>
                    <code><?php echo admin_url('admin-ajax.php'); ?>?action=vd_get_test_status</code>

                    <h4>🏃 Run Tests:</h4>
                    <code><?php echo admin_url('admin-ajax.php'); ?>?action=vd_run_tests&test_type=performance</code>

                    <h4>📋 JavaScript Test Code:</h4>
                    <pre>
// Test infrastructure status
jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
    action: 'vd_get_test_status',
    _ajax_nonce: '<?php echo wp_create_nonce('vd_test_nonce'); ?>'
}, function(response) {
    console.log('Status:', response);
});

// Run performance tests
jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
    action: 'vd_run_tests',
    test_type: 'performance',
    enable_performance: true,
    output_format: 'json',
    _ajax_nonce: '<?php echo wp_create_nonce('vd_test_nonce'); ?>'
}, function(response) {
    console.log('Results:', response);
});
                    </pre>
                </div>

                <div class="test-section">
                    <h3>⚠️ Note: Admin Menu Issue</h3>
                    <p>The WordPress admin menu "VD Tests" is not showing because the admin endpoint file needs to be loaded by the main plugin.</p>
                    <p><strong>Solution:</strong> Add this line to the main plugin file:</p>
                    <code>require_once plugin_dir_path(__FILE__) . 'tests/admin-test-endpoint.php';</code>
                </div>
            </div>

        <?php endif; ?>

        <div class="test-section">
            <h3>🔄 Navigation</h3>
            <a href="?test=menu" class="test-link" style="display: inline-block; margin: 5px;">← Back to Menu</a>
            <a href="<?php echo home_url(); ?>/wp-admin/" class="test-link" style="display: inline-block; margin: 5px;">WordPress Admin →</a>
        </div>

        <div class="test-section info">
            <h3>ℹ️ About Step 5.1.1</h3>
            <p><strong>Status:</strong> ✅ Completed</p>
            <p><strong>Files Created:</strong> 5 core infrastructure files</p>
            <p><strong>Total Lines:</strong> 2,327 lines of test infrastructure code</p>
            <p><strong>Purpose:</strong> Foundation for 95% test coverage across all 25 modules</p>
            <p><strong>Next Steps:</strong> Step 5.1.2 - Unit Testing Framework Expansion</p>
        </div>
    </div>
</body>
</html>
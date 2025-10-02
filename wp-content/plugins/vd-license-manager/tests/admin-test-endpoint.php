<?php

namespace VD\LicenseManager\Tests\Admin;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/test-runner.php';
require_once __DIR__ . '/class-vd-enhanced-test-utils.php';

use VD\LicenseManager\Tests\Runner\VD_Test_Runner;
use VD\LicenseManager\Tests\Utils\VD_Enhanced_Test_Utils;

/**
 * VD License Manager Test Endpoint
 *
 * WordPress admin integration for testing infrastructure
 * Self-contained test endpoint with admin context hooks
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */
class VD_Test_Admin_Endpoint {

    /**
     * Initialize admin hooks
     *
     * @return void
     */
    public static function init() {
        if (is_admin()) {
            add_action('wp_ajax_vd_run_tests', array(__CLASS__, 'ajax_run_tests'));
            add_action('wp_ajax_vd_get_test_status', array(__CLASS__, 'ajax_get_test_status'));
            add_action('admin_menu', array(__CLASS__, 'add_test_menu'));
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        }
    }

    /**
     * Add test menu to WordPress admin
     *
     * @return void
     */
    public static function add_test_menu() {
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'tools.php',
                'VD License Manager Tests',
                'VD Tests',
                'manage_options',
                'vd-license-tests',
                array(__CLASS__, 'render_test_page')
            );
        }
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Hook suffix
     * @return void
     */
    public static function enqueue_scripts($hook) {
        if (strpos($hook, 'vd-license-tests') !== false) {
            wp_enqueue_script('jquery');
        }
    }

    /**
     * Render test page
     *
     * @return void
     */
    public static function render_test_page() {
        ?>
        <div class="wrap">
            <h1>VD License Manager - Test Infrastructure</h1>
            <div class="notice notice-info">
                <p><strong>Step 5.1.1: Test Infrastructure Enhancement</strong> - Complete testing suite for all 25 modules</p>
            </div>

            <div class="card">
                <h2>Quick Test Runner</h2>
                <p>Run comprehensive tests for all modules with performance monitoring.</p>

                <div id="test-controls">
                    <button id="run-all-tests" class="button button-primary">Run All Tests</button>
                    <button id="run-performance-tests" class="button">Performance Tests Only</button>
                    <button id="run-module-tests" class="button">Module Tests Only</button>
                </div>

                <div id="test-options" style="margin: 20px 0;">
                    <h3>Test Options</h3>
                    <label><input type="checkbox" id="skip-integration" /> Skip Integration Tests</label><br>
                    <label><input type="checkbox" id="enable-coverage" checked /> Enable Coverage Reporting</label><br>
                    <label><input type="checkbox" id="enable-performance" checked /> Enable Performance Tracking</label><br>
                    <label>Output Format:
                        <select id="output-format">
                            <option value="json">JSON</option>
                            <option value="text">Text</option>
                        </select>
                    </label>
                </div>

                <div id="test-results" style="display: none;">
                    <h3>Test Results</h3>
                    <div id="test-summary"></div>
                    <div id="test-details"></div>
                </div>

                <div id="test-progress" style="display: none;">
                    <h3>Test Progress</h3>
                    <div class="progress-bar" style="width: 100%; background: #f0f0f0; border-radius: 3px;">
                        <div id="progress-fill" style="width: 0%; background: #0073aa; height: 20px; border-radius: 3px; transition: width 0.3s;"></div>
                    </div>
                    <div id="progress-text" style="margin-top: 10px;"></div>
                </div>
            </div>

            <div class="card">
                <h2>Module Categories</h2>
                <div class="module-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">

                    <div class="module-category">
                        <h4>Format Validation (2 modules)</h4>
                        <ul>
                            <li>Pattern Validator</li>
                            <li>Checksum Validator</li>
                        </ul>
                        <button class="button test-category" data-category="format">Test Format Modules</button>
                    </div>

                    <div class="module-category">
                        <h4>Database Layer (3 modules)</h4>
                        <ul>
                            <li>Query Manager</li>
                            <li>LMFWC Adapter</li>
                            <li>Cache Manager</li>
                        </ul>
                        <button class="button test-category" data-category="database">Test Database Modules</button>
                    </div>

                    <div class="module-category">
                        <h4>Status Management (3 modules)</h4>
                        <ul>
                            <li>Status Enum</li>
                            <li>Status Transition</li>
                            <li>Business Rules</li>
                        </ul>
                        <button class="button test-category" data-category="status">Test Status Modules</button>
                    </div>

                    <div class="module-category">
                        <h4>Business Rules (6 modules)</h4>
                        <ul>
                            <li>Activation Rules</li>
                            <li>Expiry Core</li>
                            <li>Expiry Automation</li>
                            <li>Expiry Escalation</li>
                            <li>Constraint Validation</li>
                            <li>Usage Rules</li>
                        </ul>
                        <button class="button test-category" data-category="rules">Test Rules Modules</button>
                    </div>

                    <div class="module-category">
                        <h4>Security Layer (6 modules)</h4>
                        <ul>
                            <li>Security Validator</li>
                            <li>Event Logger</li>
                            <li>Threat Detector</li>
                            <li>Privacy Manager</li>
                            <li>Storage Manager</li>
                            <li>Report Generator</li>
                        </ul>
                        <button class="button test-category" data-category="security">Test Security Modules</button>
                    </div>

                    <div class="module-category">
                        <h4>API Framework (2 modules)</h4>
                        <ul>
                            <li>API Framework</li>
                            <li>Webhook System</li>
                        </ul>
                        <button class="button test-category" data-category="api">Test API Modules</button>
                    </div>

                    <div class="module-category">
                        <h4>Integration Layer (1 module)</h4>
                        <ul>
                            <li>Integration Manager</li>
                        </ul>
                        <button class="button test-category" data-category="integration">Test Integration Module</button>
                    </div>

                </div>
            </div>

            <div class="card">
                <h2>Test Infrastructure Status</h2>
                <div id="infrastructure-status">
                    <p>Loading infrastructure status...</p>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Load infrastructure status
            loadInfrastructureStatus();

            // Run all tests
            $('#run-all-tests').click(function() {
                runTests('all');
            });

            // Run performance tests
            $('#run-performance-tests').click(function() {
                runTests('performance');
            });

            // Run module tests
            $('#run-module-tests').click(function() {
                runTests('modules');
            });

            // Run category tests
            $('.test-category').click(function() {
                var category = $(this).data('category');
                runCategoryTests(category);
            });

            function runTests(type) {
                var options = {
                    action: 'vd_run_tests',
                    test_type: type,
                    skip_integration: $('#skip-integration').is(':checked'),
                    enable_coverage: $('#enable-coverage').is(':checked'),
                    enable_performance: $('#enable-performance').is(':checked'),
                    output_format: $('#output-format').val(),
                    _ajax_nonce: '<?php echo wp_create_nonce('vd_test_nonce'); ?>'
                };

                showProgress();

                $.post(ajaxurl, options, function(response) {
                    if (response.success) {
                        displayResults(response.data);
                    } else {
                        displayError(response.data);
                    }
                }).fail(function() {
                    displayError('AJAX request failed');
                });
            }

            function runCategoryTests(category) {
                var options = {
                    action: 'vd_run_tests',
                    test_type: 'category',
                    category: category,
                    enable_performance: true,
                    output_format: 'json',
                    _ajax_nonce: '<?php echo wp_create_nonce('vd_test_nonce'); ?>'
                };

                showProgress();

                $.post(ajaxurl, options, function(response) {
                    if (response.success) {
                        displayResults(response.data);
                    } else {
                        displayError(response.data);
                    }
                }).fail(function() {
                    displayError('AJAX request failed');
                });
            }

            function showProgress() {
                $('#test-progress').show();
                $('#test-results').hide();
                $('#progress-text').text('Initializing tests...');

                // Simulate progress
                var progress = 0;
                var interval = setInterval(function() {
                    progress += Math.random() * 10;
                    if (progress >= 100) {
                        progress = 100;
                        clearInterval(interval);
                        $('#progress-text').text('Tests completed!');
                    } else {
                        $('#progress-text').text('Running tests... ' + Math.round(progress) + '%');
                    }
                    $('#progress-fill').css('width', progress + '%');
                }, 500);
            }

            function displayResults(data) {
                $('#test-progress').hide();
                $('#test-results').show();

                var summary = data.summary;
                var summaryHtml = '<div class="notice notice-' + (summary.modules_failed > 0 ? 'warning' : 'success') + '">';
                summaryHtml += '<h4>Test Summary</h4>';
                summaryHtml += '<p><strong>Total Modules:</strong> ' + summary.total_modules_tested + '</p>';
                summaryHtml += '<p><strong>Passed:</strong> ' + summary.modules_passed + '</p>';
                summaryHtml += '<p><strong>Failed:</strong> ' + summary.modules_failed + '</p>';
                summaryHtml += '<p><strong>Success Rate:</strong> ' + summary.overall_success_rate + '%</p>';
                summaryHtml += '<p><strong>Execution Time:</strong> ' + Math.round(data.execution_time) + 'ms</p>';
                summaryHtml += '</div>';

                $('#test-summary').html(summaryHtml);

                var detailsHtml = '<pre style="background: #f9f9f9; padding: 15px; border-radius: 3px; overflow-x: auto;">';
                detailsHtml += JSON.stringify(data, null, 2);
                detailsHtml += '</pre>';

                $('#test-details').html(detailsHtml);
            }

            function displayError(error) {
                $('#test-progress').hide();
                $('#test-results').show();
                $('#test-summary').html('<div class="notice notice-error"><p><strong>Error:</strong> ' + error + '</p></div>');
                $('#test-details').html('');
            }

            function loadInfrastructureStatus() {
                $.post(ajaxurl, {
                    action: 'vd_get_test_status',
                    _ajax_nonce: '<?php echo wp_create_nonce('vd_test_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        var status = response.data;
                        var statusHtml = '<ul>';
                        statusHtml += '<li><strong>Fixtures Loaded:</strong> ' + (status.fixtures_loaded ? '✓' : '✗') + '</li>';
                        statusHtml += '<li><strong>Mocks Loaded:</strong> ' + (status.mocks_loaded ? '✓' : '✗') + '</li>';
                        statusHtml += '<li><strong>Debug Mode:</strong> ' + (status.debug_mode ? '✓' : '✗') + '</li>';
                        statusHtml += '<li><strong>Performance Tracking:</strong> ' + (status.performance_tracking ? '✓' : '✗') + '</li>';
                        statusHtml += '<li><strong>Memory Usage:</strong> ' + Math.round(status.memory_usage / 1024 / 1024, 2) + ' MB</li>';
                        statusHtml += '<li><strong>Peak Memory:</strong> ' + Math.round(status.peak_memory / 1024 / 1024, 2) + ' MB</li>';
                        statusHtml += '</ul>';
                        $('#infrastructure-status').html(statusHtml);
                    }
                });
            }
        });
        </script>

        <style>
        .module-category {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .module-category h4 {
            margin-top: 0;
            color: #0073aa;
        }
        .module-category ul {
            margin: 10px 0;
        }
        .module-category li {
            font-size: 13px;
            color: #666;
        }
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin: 20px 0;
            padding: 20px;
        }
        .card h2 {
            margin-top: 0;
        }
        </style>
        <?php
    }

    /**
     * AJAX handler for running tests
     *
     * @return void
     */
    public static function ajax_run_tests() {
        check_ajax_referer('vd_test_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $test_type = sanitize_text_field($_POST['test_type'] ?? 'all');
        $category = sanitize_text_field($_POST['category'] ?? '');

        $options = array(
            'skip_integration' => isset($_POST['skip_integration']) && $_POST['skip_integration'] === 'true',
            'enable_coverage_reporting' => isset($_POST['enable_coverage']) && $_POST['enable_coverage'] === 'true',
            'enable_performance_tracking' => isset($_POST['enable_performance']) && $_POST['enable_performance'] === 'true',
            'output_format' => sanitize_text_field($_POST['output_format'] ?? 'json')
        );

        try {
            $runner = new VD_Test_Runner($options);

            switch ($test_type) {
                case 'performance':
                    $results = $runner->run_performance_tests($options);
                    break;

                case 'category':
                    if (empty($category)) {
                        wp_send_json_error('Category not specified');
                        return;
                    }
                    $results = self::run_category_tests($runner, $category, $options);
                    break;

                case 'modules':
                    $results = self::run_module_tests_only($runner, $options);
                    break;

                case 'all':
                default:
                    $results = $runner->run_all_tests($options);
                    break;
            }

            wp_send_json_success($results);

        } catch (Exception $e) {
            wp_send_json_error('Test execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Run tests for specific category
     *
     * @param VD_Test_Runner $runner Test runner instance
     * @param string $category Module category
     * @param array $options Test options
     * @return array Test results
     */
    private static function run_category_tests($runner, $category, $options) {
        $module_map = array(
            'format' => array('pattern_validator', 'checksum_validator'),
            'database' => array('query_manager', 'lmfwc_adapter', 'cache_manager'),
            'status' => array('enum', 'transition', 'business'),
            'rules' => array('activation', 'expiry_core', 'expiry_automation', 'expiry_escalation', 'constraint_validation', 'usage'),
            'security' => array('validator', 'event_logger', 'threat_detector', 'privacy_manager', 'storage_manager', 'report_generator'),
            'api' => array('framework', 'webhook_system'),
            'integration' => array('manager')
        );

        if (!isset($module_map[$category])) {
            throw new Exception("Unknown category: {$category}");
        }

        return $runner->run_category_tests($category, $module_map[$category], $options);
    }

    /**
     * Run module tests only (skip integration and performance)
     *
     * @param VD_Test_Runner $runner Test runner instance
     * @param array $options Test options
     * @return array Test results
     */
    private static function run_module_tests_only($runner, $options) {
        $options['skip_integration'] = true;
        $options['enable_performance_tracking'] = false;
        return $runner->run_all_tests($options);
    }

    /**
     * AJAX handler for getting test status
     *
     * @return void
     */
    public static function ajax_get_test_status() {
        check_ajax_referer('vd_test_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        try {
            VD_Enhanced_Test_Utils::init();
            $status = VD_Enhanced_Test_Utils::get_environment_status();
            wp_send_json_success($status);
        } catch (Exception $e) {
            wp_send_json_error('Failed to get status: ' . $e->getMessage());
        }
    }
}

// Initialize if in admin context
if (is_admin()) {
    VD_Test_Admin_Endpoint::init();
}
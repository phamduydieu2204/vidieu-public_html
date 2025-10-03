<?php
/**
 * VD Unit Tests - Comprehensive Testing Dashboard
 *
 * Central admin interface for all VD License Manager testing activities
 * Originally Step 5.1.5, now expanded for project-wide testing
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize admin page hooks
 */
add_action('admin_menu', 'vd_add_validation_orchestrator_test_page');
add_action('admin_enqueue_scripts', 'vd_enqueue_validation_orchestrator_test_scripts');

// Register AJAX endpoints for integration testing
add_action('wp_ajax_vd_test_integration_framework', 'vd_test_integration_framework');
add_action('wp_ajax_vd_test_specific_integration', 'vd_test_specific_integration');

// Register AJAX endpoints for API endpoint testing (Step 5.1.8)
add_action('wp_ajax_vd_test_api_endpoints', 'vd_test_api_endpoints');
add_action('wp_ajax_vd_test_specific_api_scenario', 'vd_test_specific_api_scenario');

// Register AJAX endpoints for performance testing (Step 5.1.9)
add_action('wp_ajax_vd_test_performance', 'vd_test_performance');
add_action('wp_ajax_vd_test_specific_performance_scenario', 'vd_test_specific_performance_scenario');

// Test Coverage Analysis AJAX handlers
add_action('wp_ajax_vd_test_coverage_analysis', 'vd_test_coverage_analysis');
add_action('wp_ajax_vd_test_specific_coverage_category', 'vd_test_specific_coverage_category');

/**
 * Add admin menu page for VD Unit Tests
 */
function vd_add_validation_orchestrator_test_page() {
    add_submenu_page(
        'tools.php',
        'VD Unit Tests - Comprehensive Testing Dashboard',
        'VD Unit Tests',
        'manage_options',
        'vd-unit-tests',
        'vd_render_validation_orchestrator_test_page'
    );
}

/**
 * Enqueue scripts for the test page
 */
function vd_enqueue_validation_orchestrator_test_scripts($hook) {
    if ($hook !== 'tools_page_vd-unit-tests') {
        return;
    }

    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'vd_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vd_test_nonce')
    ));

    // Also create a global JavaScript variable for backward compatibility
    wp_add_inline_script('jquery', 'var vd_test_nonce = "' . wp_create_nonce('vd_test_nonce') . '";');
}

/**
 * Render the admin test page
 */
function vd_render_validation_orchestrator_test_page() {
    ?>
    <div class="wrap">
        <h1>🧪 VD Unit Tests - Comprehensive Testing Dashboard</h1>

        <div class="notice notice-info">
            <p><strong>Comprehensive Testing Platform:</strong> Central dashboard for all VD License Manager testing activities</p>
            <p><strong>Current Focus:</strong> Validation Orchestrator (Step 5.1.5) - <code>VD\LicenseManager\Validator\VD_License_Validation_Orchestrator</code></p>
            <p><strong>Future Ready:</strong> This page will be enhanced for testing all project phases and modules</p>
            <p><strong>Project Progress:</strong> 81% Complete | <strong>Phase 5:</strong> Testing & Quality Assurance</p>
        </div>

        <!-- Current Testing Section -->
        <div class="card" style="max-width: none;">
            <h2>🧪 Current Test: Validation Orchestrator (Step 5.1.5)</h2>
            <div style="margin: 20px 0;">
                <button id="run-orchestrator-test" class="button button-primary button-large">
                    <span class="dashicons dashicons-play" style="vertical-align: middle;"></span>
                    Chạy Test Validation Orchestrator
                </button>
                <button id="clear-results" class="button button-secondary" style="margin-left: 10px;">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                    Xóa Kết Quả
                </button>
            </div>
        </div>

        <!-- Integration Testing Section (Step 5.1.7) -->
        <div class="card" style="max-width: none;">
            <h2>🔗 Integration Testing (Step 5.1.7)</h2>
            <div class="notice notice-info inline">
                <p><strong>Module Interaction Testing:</strong> Test interactions between different project modules and phases</p>
                <p><strong>Coverage:</strong> 6 interaction scenarios | <strong>Focus:</strong> Cross-phase integration validation</p>
            </div>

            <div style="margin: 20px 0;">
                <button id="run-integration-test" class="button button-primary button-large">
                    <span class="dashicons dashicons-networking" style="vertical-align: middle;"></span>
                    Chạy Integration Tests
                </button>
                <select id="integration-scenario" style="margin-left: 10px; padding: 8px;">
                    <option value="">Tất Cả Scenarios</option>
                    <option value="validator_security">Validator → Security</option>
                    <option value="security_api">Security → API</option>
                    <option value="api_integration">API → Integration</option>
                    <option value="database_cache">Database → Cache</option>
                    <option value="status_validation">Status → Validation</option>
                    <option value="wordpress_hooks">WordPress Hooks</option>
                </select>
                <button id="clear-integration-results" class="button button-secondary" style="margin-left: 10px;">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                    Xóa Kết Quả Integration
                </button>
            </div>

            <div id="integration-test-status" style="display: none;">
                <div class="notice notice-warning">
                    <p><span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span> Đang chạy integration tests...</p>
                </div>
            </div>
        </div>

        <!-- API Endpoint Testing Section (Step 5.1.8) -->
        <div class="card" style="max-width: none;">
            <h2>🌐 API Endpoint Testing (Step 5.1.8)</h2>
            <div class="notice notice-info inline">
                <p><strong>API Validation Testing:</strong> Test REST API endpoints, webhooks, and third-party integrations</p>
                <p><strong>Coverage:</strong> 5 API scenarios | <strong>Focus:</strong> REST API, Webhooks, Authentication & Security</p>
            </div>

            <div style="margin: 20px 0;">
                <button id="run-api-test" class="button button-primary button-large">
                    <span class="dashicons dashicons-cloud" style="vertical-align: middle;"></span>
                    Chạy API Tests
                </button>
                <select id="api-scenario" style="margin-left: 10px; padding: 8px;">
                    <option value="">Tất Cả API Scenarios</option>
                    <option value="rest_api_endpoints">REST API Endpoints</option>
                    <option value="webhook_system">Webhook System</option>
                    <option value="third_party_integration">Third-party Integration</option>
                    <option value="authentication_flow">Authentication Flow</option>
                    <option value="rate_limiting_security">Rate Limiting & Security</option>
                </select>
                <button id="clear-api-results" class="button button-secondary" style="margin-left: 10px;">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                    Xóa Kết Quả API
                </button>
            </div>

            <div id="api-test-status" style="display: none;">
                <div class="notice notice-warning">
                    <p><span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span> Đang chạy API tests...</p>
                </div>
            </div>
        </div>

        <!-- Performance Testing Section (Step 5.1.9) -->
        <div class="card" style="max-width: none;">
            <h2>⚡ Performance Testing (Step 5.1.9)</h2>
            <div class="notice notice-info inline">
                <p><strong>Performance Validation:</strong> Test load, memory usage, database performance, and stress testing</p>
                <p><strong>Coverage:</strong> 5 performance scenarios | <strong>Focus:</strong> Response time, scalability, resource optimization</p>
            </div>

            <div style="margin: 20px 0;">
                <button id="run-performance-test" class="button button-primary button-large">
                    <span class="dashicons dashicons-performance" style="vertical-align: middle;"></span>
                    Chạy Performance Tests
                </button>
                <select id="performance-scenario" style="margin-left: 10px; padding: 8px;">
                    <option value="">Tất Cả Performance Scenarios</option>
                    <option value="load_testing">Load Testing</option>
                    <option value="memory_usage">Memory Usage</option>
                    <option value="database_performance">Database Performance</option>
                    <option value="response_time_benchmarking">Response Time Benchmarking</option>
                    <option value="stress_testing">Stress Testing</option>
                </select>
                <button id="clear-performance-results" class="button button-secondary" style="margin-left: 10px;">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                    Xóa Kết Quả Performance
                </button>
            </div>

            <div id="performance-test-status" style="display: none;">
                <div class="notice notice-warning">
                    <p><span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span> Đang chạy performance tests...</p>
                </div>
            </div>
        </div>

        <!-- Future Testing Sections (Coming Soon) -->
        <div class="card" style="max-width: none; opacity: 0.7;">
            <h2>🚀 Future Testing Capabilities (Coming Soon)</h2>
            <div class="notice notice-info inline">
                <p>This dashboard will be enhanced with comprehensive testing for all project phases:</p>
            </div>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Test Category</th>
                        <th>Scope</th>
                        <th>Status</th>
                        <th>Planned Features</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Phase 1-4 Modules</strong></td>
                        <td>25+ modules across all phases</td>
                        <td>📋 Planned</td>
                        <td>Format, Database, Security, API testing</td>
                    </tr>
                    <tr>
                        <td><strong>Unit Test Framework</strong></td>
                        <td>200+ comprehensive tests</td>
                        <td>✅ Ready</td>
                        <td>95% coverage target, Performance benchmarks</td>
                    </tr>
                    <tr>
                        <td><strong>Integration Testing</strong></td>
                        <td>Module-to-module interactions</td>
                        <td>🧪 Active</td>
                        <td>Cross-phase integration validation, 6 interaction scenarios</td>
                    </tr>
                    <tr>
                        <td><strong>Performance Testing</strong></td>
                        <td>System performance monitoring</td>
                        <td>📋 Planned</td>
                        <td>&lt;50ms execution, &lt;2MB memory targets</td>
                    </tr>
                    <tr>
                        <td><strong>Test Coverage Analysis</strong></td>
                        <td>Code coverage measurement</td>
                        <td>✅ Ready</td>
                        <td>Coverage analysis, Gap analysis, Recommendations</td>
                    </tr>
                    <tr>
                        <td><strong>Security Testing</strong></td>
                        <td>Penetration testing suite</td>
                        <td>📋 Planned</td>
                        <td>Vulnerability scanning, Compliance validation</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Test Coverage Analysis Section (Step 5.1.10) -->
        <div class="card" style="max-width: none;">
            <h2>📊 Test Coverage Analysis (Step 5.1.10)</h2>
            <p>Comprehensive code coverage measurement and reporting across all 25+ modules.</p>

            <div style="margin-bottom: 20px;">
                <button type="button" id="run-coverage-analysis" class="button button-primary" style="margin-right: 10px;">
                    🔍 Run Coverage Analysis
                </button>

                <select id="coverage-category" style="margin-right: 10px;">
                    <option value="">All Categories</option>
                    <option value="format">Format Validation</option>
                    <option value="database">Database Layer</option>
                    <option value="status">Status Management</option>
                    <option value="rules">Business Rules</option>
                    <option value="security">Security & Audit</option>
                    <option value="api">API Framework</option>
                    <option value="integration">Integration Layer</option>
                    <option value="validator">Validator Modules</option>
                </select>

                <button type="button" id="run-specific-coverage" class="button">
                    📈 Analyze Specific Category
                </button>
            </div>

            <div class="notice notice-info">
                <p><strong>Coverage Target:</strong> 95% | <strong>Modules:</strong> 25+ | <strong>Analysis:</strong> Gap identification and recommendations</p>
            </div>
        </div>

        <!-- Test Controls -->
        <div class="card" style="max-width: none;">
            <h2>🎛️ Test Controls</h2>

            <div id="test-status" style="display: none;">
                <div class="notice notice-warning">
                    <p><span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span> Đang chạy test...</p>
                </div>
            </div>
        </div>

        <div id="test-results" style="display: none;">
            <div class="card" style="max-width: none;">
                <h2>📊 Kết Quả Test</h2>
                <div id="test-summary"></div>
                <div id="test-details"></div>
            </div>
        </div>

        <div id="integration-test-results" style="display: none;">
            <div class="card" style="max-width: none;">
                <h2>🔗 Kết Quả Integration Tests</h2>
                <div id="integration-test-summary"></div>
                <div id="integration-test-details"></div>
            </div>
        </div>

        <div id="api-test-results" style="display: none;">
            <div class="card" style="max-width: none;">
                <h2>🌐 Kết Quả API Tests</h2>
                <div id="api-test-summary"></div>
                <div id="api-test-details"></div>
            </div>
        </div>

        <div id="performance-test-results" style="display: none;">
            <div class="card" style="max-width: none;">
                <h2>⚡ Kết Quả Performance Tests</h2>
                <div id="performance-test-summary"></div>
                <div id="performance-test-details"></div>
            </div>
        </div>

        <div id="coverage-test-results" style="display: none;">
            <div class="card" style="max-width: none;">
                <h2>📊 Kết Quả Test Coverage Analysis</h2>
                <div id="coverage-test-summary"></div>
                <div id="coverage-test-details"></div>
            </div>
        </div>

        <div class="card" style="max-width: none;">
            <h2>📋 Thông Tin Module</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 200px;">Thuộc Tính</th>
                        <th>Giá Trị</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Module Name</strong></td>
                        <td>Validation Orchestrator</td>
                    </tr>
                    <tr>
                        <td><strong>Step</strong></td>
                        <td>5.1.5</td>
                    </tr>
                    <tr>
                        <td><strong>Namespace</strong></td>
                        <td><code>VD\LicenseManager\Validator\VD_License_Validation_Orchestrator</code></td>
                    </tr>
                    <tr>
                        <td><strong>File Size</strong></td>
                        <td>685+ lines</td>
                    </tr>
                    <tr>
                        <td><strong>Core Methods</strong></td>
                        <td>20+ methods</td>
                    </tr>
                    <tr>
                        <td><strong>Key Features</strong></td>
                        <td>
                            • Pipeline orchestration với 6 stages<br>
                            • Advanced dependency management<br>
                            • Comprehensive validation reporting<br>
                            • Batch processing capabilities<br>
                            • Performance metrics & monitoring<br>
                            • Configuration management
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Test Cases</strong></td>
                        <td>10 comprehensive tests</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card" style="max-width: none;">
            <h2>🔍 Test Cases Overview</h2>
            <ol>
                <li><strong>Module Loading</strong> - Kiểm tra class load thành công</li>
                <li><strong>Singleton Pattern</strong> - Xác minh singleton pattern hoạt động</li>
                <li><strong>Module Initialization</strong> - Test khởi tạo validation modules</li>
                <li><strong>Pipeline Configuration</strong> - Kiểm tra cấu hình validation pipeline</li>
                <li><strong>Single License Orchestration</strong> - Test validation một license key</li>
                <li><strong>Advanced Reporting</strong> - Test generation báo cáo chi tiết</li>
                <li><strong>Batch Validation</strong> - Test xử lý batch nhiều licenses</li>
                <li><strong>Dependency Container</strong> - Test dependency management</li>
                <li><strong>Performance Metrics</strong> - Test thu thập performance data</li>
                <li><strong>Configuration Management</strong> - Test quản lý cấu hình orchestrator</li>
            </ol>
        </div>
    </div>

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .test-result-success {
            color: #00a32a;
        }

        .test-result-failed {
            color: #d63638;
        }

        .test-summary-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }

        .test-detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .test-detail-table th,
        .test-detail-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .test-detail-table th {
            background-color: #f1f1f1;
        }

        .test-passed {
            background-color: #d4edda !important;
        }

        .test-failed {
            background-color: #f8d7da !important;
        }

        .code-block {
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#run-orchestrator-test').on('click', function() {
            var $button = $(this);
            var $status = $('#test-status');
            var $results = $('#test-results');

            // Disable button và show loading
            $button.prop('disabled', true);
            $status.show();
            $results.hide();

            // Chạy AJAX test
            $.ajax({
                url: vd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vd_test_validation_orchestrator',
                    _ajax_nonce: vd_ajax.nonce
                },
                timeout: 30000,
                success: function(response) {
                    $status.hide();
                    $button.prop('disabled', false);

                    if (response.success) {
                        displaySuccessResults(response.data);
                    } else {
                        displayErrorResults(response.data);
                    }

                    $results.show();
                },
                error: function(xhr, status, error) {
                    $status.hide();
                    $button.prop('disabled', false);

                    displayErrorResults({
                        message: 'AJAX Error: ' + error,
                        status: status,
                        responseText: xhr.responseText
                    });

                    $results.show();
                }
            });
        });

        $('#clear-results').on('click', function() {
            $('#test-results').hide();
            $('#test-summary').empty();
            $('#test-details').empty();
        });

        $('#run-integration-test').on('click', function() {
            var $button = $(this);
            var $status = $('#integration-test-status');
            var $results = $('#integration-test-results');
            var scenario = $('#integration-scenario').val();

            // Disable button và show loading
            $button.prop('disabled', true);
            $status.show();
            $results.hide();

            // Chạy AJAX integration test
            $.ajax({
                url: vd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: scenario ? 'vd_test_specific_integration' : 'vd_test_integration_framework',
                    scenario: scenario,
                    _ajax_nonce: vd_ajax.nonce
                },
                timeout: 30000,
                success: function(response) {
                    $status.hide();
                    $button.prop('disabled', false);

                    if (response.success) {
                        displayIntegrationSuccessResults(response.data);
                    } else {
                        displayIntegrationErrorResults(response.data);
                    }

                    $results.show();
                },
                error: function(xhr, status, error) {
                    $status.hide();
                    $button.prop('disabled', false);

                    displayIntegrationErrorResults({
                        message: 'AJAX Error: ' + error,
                        status: status,
                        responseText: xhr.responseText
                    });

                    $results.show();
                }
            });
        });

        $('#clear-integration-results').on('click', function() {
            $('#integration-test-results').hide();
            $('#integration-test-summary').empty();
            $('#integration-test-details').empty();
        });

        $('#run-api-test').on('click', function() {
            var $button = $(this);
            var $status = $('#api-test-status');
            var $results = $('#api-test-results');
            var scenario = $('#api-scenario').val();

            // Disable button và show loading
            $button.prop('disabled', true);
            $status.show();
            $results.hide();

            // Chạy AJAX API test
            $.ajax({
                url: vd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: scenario ? 'vd_test_specific_api_scenario' : 'vd_test_api_endpoints',
                    scenario: scenario,
                    _ajax_nonce: vd_ajax.nonce
                },
                timeout: 30000,
                success: function(response) {
                    $status.hide();
                    $button.prop('disabled', false);

                    if (response.success) {
                        displayApiSuccessResults(response.data);
                    } else {
                        displayApiErrorResults(response.data);
                    }

                    $results.show();
                },
                error: function(xhr, status, error) {
                    $status.hide();
                    $button.prop('disabled', false);

                    displayApiErrorResults({
                        message: 'AJAX Error: ' + error,
                        status: status,
                        responseText: xhr.responseText
                    });

                    $results.show();
                }
            });
        });

        $('#clear-api-results').on('click', function() {
            $('#api-test-results').hide();
            $('#api-test-summary').empty();
            $('#api-test-details').empty();
        });

        $('#run-performance-test').on('click', function() {
            var $button = $(this);
            var $status = $('#performance-test-status');
            var $results = $('#performance-test-results');
            var scenario = $('#performance-scenario').val();

            // Disable button và show loading
            $button.prop('disabled', true);
            $status.show();
            $results.hide();

            // Chạy AJAX performance test
            $.ajax({
                url: vd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: scenario ? 'vd_test_specific_performance_scenario' : 'vd_test_performance',
                    scenario: scenario,
                    _ajax_nonce: vd_ajax.nonce
                },
                timeout: 45000, // Longer timeout for performance tests
                success: function(response) {
                    $status.hide();
                    $button.prop('disabled', false);

                    if (response.success) {
                        displayPerformanceSuccessResults(response.data);
                    } else {
                        displayPerformanceErrorResults(response.data);
                    }

                    $results.show();
                },
                error: function(xhr, status, error) {
                    $status.hide();
                    $button.prop('disabled', false);

                    displayPerformanceErrorResults({
                        message: 'AJAX Error: ' + error,
                        status: status,
                        responseText: xhr.responseText
                    });

                    $results.show();
                }
            });
        });

        $('#clear-performance-results').on('click', function() {
            $('#performance-test-results').hide();
            $('#performance-test-summary').empty();
            $('#performance-test-details').empty();
        });

        function displaySuccessResults(data) {
            var summary = data.summary;
            var testResults = data.test_results;

            // Summary
            var summaryHtml = '<div class="test-summary-box">';
            summaryHtml += '<h3>📈 Tóm Tắt Kết Quả</h3>';
            summaryHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">';
            summaryHtml += '<div><strong>Module:</strong> ' + summary.module + '</div>';
            summaryHtml += '<div><strong>Total Tests:</strong> ' + summary.total_tests + '</div>';
            summaryHtml += '<div class="test-result-success"><strong>Passed:</strong> ' + summary.passed_tests + '</div>';
            summaryHtml += '<div class="test-result-failed"><strong>Failed:</strong> ' + summary.failed_tests + '</div>';
            summaryHtml += '<div><strong>Success Rate:</strong> ' + summary.success_rate + '%</div>';
            summaryHtml += '<div><strong>Execution Time:</strong> ' + summary.execution_time + 'ms</div>';
            summaryHtml += '<div><strong>Status:</strong> <span class="' + (summary.status === 'SUCCESS' ? 'test-result-success' : 'test-result-failed') + '">' + summary.status + '</span></div>';
            summaryHtml += '</div></div>';

            $('#test-summary').html(summaryHtml);

            // Detailed results
            var detailsHtml = '<h3>🔍 Chi Tiết Test Cases</h3>';
            detailsHtml += '<table class="test-detail-table">';
            detailsHtml += '<thead><tr><th>Test Case</th><th>Status</th><th>Details</th></tr></thead>';
            detailsHtml += '<tbody>';

            $.each(testResults, function(key, result) {
                var statusClass = result.success ? 'test-passed' : 'test-failed';
                var statusIcon = result.success ? '✅' : '❌';

                detailsHtml += '<tr class="' + statusClass + '">';
                detailsHtml += '<td><strong>' + result.test + '</strong></td>';
                detailsHtml += '<td>' + statusIcon + ' ' + (result.success ? 'PASSED' : 'FAILED') + '</td>';
                detailsHtml += '<td><div class="code-block">' + JSON.stringify(result.details, null, 2) + '</div></td>';
                detailsHtml += '</tr>';
            });

            detailsHtml += '</tbody></table>';

            // Implementation notes
            if (data.implementation_notes) {
                detailsHtml += '<h3>📝 Implementation Notes</h3>';
                detailsHtml += '<div class="code-block">' + JSON.stringify(data.implementation_notes, null, 2) + '</div>';
            }

            $('#test-details').html(detailsHtml);
        }

        function displayErrorResults(errorData) {
            var errorHtml = '<div class="notice notice-error">';
            errorHtml += '<h3>❌ Test Error</h3>';
            errorHtml += '<div class="code-block">' + JSON.stringify(errorData, null, 2) + '</div>';
            errorHtml += '</div>';

            $('#test-summary').html(errorHtml);
            $('#test-details').empty();
        }

        function displayIntegrationSuccessResults(data) {
            var summary = data.summary || data;
            var testResults = data.detailed_results || data.scenario_results || [];

            // Summary
            var summaryHtml = '<div class="test-summary-box">';
            summaryHtml += '<h3>🔗 Tóm Tắt Integration Testing</h3>';
            summaryHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">';
            summaryHtml += '<div><strong>Framework:</strong> ' + (summary.framework || 'Integration Testing') + '</div>';
            summaryHtml += '<div><strong>Total Scenarios:</strong> ' + (summary.total_scenarios || testResults.length) + '</div>';
            summaryHtml += '<div class="test-result-success"><strong>Passed:</strong> ' + (summary.passed_scenarios || '0') + '</div>';
            summaryHtml += '<div class="test-result-failed"><strong>Failed:</strong> ' + (summary.failed_scenarios || '0') + '</div>';
            summaryHtml += '<div><strong>Success Rate:</strong> ' + (summary.success_rate || '0') + '%</div>';
            summaryHtml += '<div><strong>Execution Time:</strong> ' + (summary.execution_time || '0') + 'ms</div>';
            if (data.filtered_scenario) {
                summaryHtml += '<div><strong>Filtered Scenario:</strong> ' + data.filtered_scenario + '</div>';
            }
            summaryHtml += '</div></div>';

            $('#integration-test-summary').html(summaryHtml);

            // Detailed results
            var detailsHtml = '<h3>🔍 Chi Tiết Integration Scenarios</h3>';
            if (testResults.length > 0) {
                detailsHtml += '<table class="test-detail-table">';
                detailsHtml += '<thead><tr><th>Scenario</th><th>Status</th><th>Details</th></tr></thead>';
                detailsHtml += '<tbody>';

                $.each(testResults, function(key, result) {
                    var statusClass = result.success ? 'test-passed' : 'test-failed';
                    var statusIcon = result.success ? '✅' : '❌';

                    detailsHtml += '<tr class="' + statusClass + '">';
                    detailsHtml += '<td><strong>' + result.test + '</strong></td>';
                    detailsHtml += '<td>' + statusIcon + ' ' + (result.success ? 'PASSED' : 'FAILED') + '</td>';
                    detailsHtml += '<td><div class="code-block">' + JSON.stringify(result.details, null, 2) + '</div></td>';
                    detailsHtml += '</tr>';
                });

                detailsHtml += '</tbody></table>';
            } else {
                detailsHtml += '<p>No detailed test results available.</p>';
            }

            // Implementation notes
            if (data.implementation_notes) {
                detailsHtml += '<h3>📝 Implementation Notes</h3>';
                detailsHtml += '<div class="code-block">' + JSON.stringify(data.implementation_notes, null, 2) + '</div>';
            }

            $('#integration-test-details').html(detailsHtml);
        }

        function displayIntegrationErrorResults(errorData) {
            var errorHtml = '<div class="notice notice-error">';
            errorHtml += '<h3>❌ Integration Test Error</h3>';
            errorHtml += '<div class="code-block">' + JSON.stringify(errorData, null, 2) + '</div>';
            errorHtml += '</div>';

            $('#integration-test-summary').html(errorHtml);
            $('#integration-test-details').empty();
        }

        function displayApiSuccessResults(data) {
            var summary = data.summary || data;
            var testResults = data.detailed_results || data.scenario_results || [];

            // Summary
            var summaryHtml = '<div class="test-summary-box">';
            summaryHtml += '<h3>🌐 Tóm Tắt API Testing</h3>';
            summaryHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">';
            summaryHtml += '<div><strong>Framework:</strong> ' + (summary.framework || 'API Endpoint Testing') + '</div>';
            summaryHtml += '<div><strong>Total Scenarios:</strong> ' + (summary.total_scenarios || testResults.length) + '</div>';
            summaryHtml += '<div class="test-result-success"><strong>Passed:</strong> ' + (summary.passed_scenarios || '0') + '</div>';
            summaryHtml += '<div class="test-result-failed"><strong>Failed:</strong> ' + (summary.failed_scenarios || '0') + '</div>';
            summaryHtml += '<div><strong>Success Rate:</strong> ' + (summary.success_rate || '0') + '%</div>';
            summaryHtml += '<div><strong>Execution Time:</strong> ' + (summary.execution_time || '0') + 'ms</div>';
            if (data.filtered_scenario) {
                summaryHtml += '<div><strong>Filtered Scenario:</strong> ' + data.filtered_scenario + '</div>';
            }
            summaryHtml += '</div></div>';

            $('#api-test-summary').html(summaryHtml);

            // Detailed results
            var detailsHtml = '<h3>🔍 Chi Tiết API Test Scenarios</h3>';
            if (testResults.length > 0) {
                detailsHtml += '<table class="test-detail-table">';
                detailsHtml += '<thead><tr><th>API Scenario</th><th>Category</th><th>Status</th><th>Details</th></tr></thead>';
                detailsHtml += '<tbody>';

                $.each(testResults, function(key, result) {
                    var statusClass = result.success ? 'test-passed' : 'test-failed';
                    var statusIcon = result.success ? '✅' : '❌';

                    detailsHtml += '<tr class="' + statusClass + '">';
                    detailsHtml += '<td><strong>' + result.test + '</strong></td>';
                    detailsHtml += '<td>' + (result.category || 'API Testing') + '</td>';
                    detailsHtml += '<td>' + statusIcon + ' ' + (result.success ? 'PASSED' : 'FAILED') + '</td>';
                    detailsHtml += '<td><div class="code-block">' + JSON.stringify(result.details, null, 2) + '</div></td>';
                    detailsHtml += '</tr>';
                });

                detailsHtml += '</tbody></table>';
            } else {
                detailsHtml += '<p>No detailed API test results available.</p>';
            }

            // API Coverage information
            if (data.api_coverage) {
                detailsHtml += '<h3>📊 API Coverage</h3>';
                detailsHtml += '<div class="test-summary-box">';
                detailsHtml += '<ul>';
                $.each(data.api_coverage, function(key, value) {
                    detailsHtml += '<li><strong>' + key.replace(/_/g, ' ').toUpperCase() + ':</strong> ' + value + '</li>';
                });
                detailsHtml += '</ul>';
                detailsHtml += '</div>';
            }

            // Implementation notes
            if (data.implementation_notes) {
                detailsHtml += '<h3>📝 Implementation Notes</h3>';
                detailsHtml += '<div class="code-block">' + JSON.stringify(data.implementation_notes, null, 2) + '</div>';
            }

            $('#api-test-details').html(detailsHtml);
        }

        function displayApiErrorResults(errorData) {
            var errorHtml = '<div class="notice notice-error">';
            errorHtml += '<h3>❌ API Test Error</h3>';
            errorHtml += '<div class="code-block">' + JSON.stringify(errorData, null, 2) + '</div>';
            errorHtml += '</div>';

            $('#api-test-summary').html(errorHtml);
            $('#api-test-details').empty();
        }

        function displayPerformanceSuccessResults(data) {
            var summary = data.summary || data;
            var testResults = data.detailed_results || data.scenario_results || [];

            // Summary with performance score
            var summaryHtml = '<div class="test-summary-box">';
            summaryHtml += '<h3>⚡ Tóm Tắt Performance Testing</h3>';
            summaryHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">';
            summaryHtml += '<div><strong>Framework:</strong> ' + (summary.framework || 'Performance Testing') + '</div>';
            summaryHtml += '<div><strong>Total Scenarios:</strong> ' + (summary.total_scenarios || testResults.length) + '</div>';
            summaryHtml += '<div class="test-result-success"><strong>Passed:</strong> ' + (summary.passed_scenarios || '0') + '</div>';
            summaryHtml += '<div class="test-result-failed"><strong>Failed:</strong> ' + (summary.failed_scenarios || '0') + '</div>';
            summaryHtml += '<div><strong>Success Rate:</strong> ' + (summary.success_rate || '0') + '%</div>';
            summaryHtml += '<div><strong>Performance Score:</strong> ' + (summary.overall_performance_score || '0') + '/100</div>';
            summaryHtml += '<div><strong>Execution Time:</strong> ' + (summary.execution_time || '0') + 'ms</div>';
            if (data.filtered_scenario) {
                summaryHtml += '<div><strong>Filtered Scenario:</strong> ' + data.filtered_scenario + '</div>';
            }
            summaryHtml += '</div></div>';

            $('#performance-test-summary').html(summaryHtml);

            // Detailed results with performance scores
            var detailsHtml = '<h3>🔍 Chi Tiết Performance Test Scenarios</h3>';
            if (testResults.length > 0) {
                detailsHtml += '<table class="test-detail-table">';
                detailsHtml += '<thead><tr><th>Performance Scenario</th><th>Category</th><th>Score</th><th>Status</th><th>Details</th></tr></thead>';
                detailsHtml += '<tbody>';

                $.each(testResults, function(key, result) {
                    var statusClass = result.success ? 'test-passed' : 'test-failed';
                    var statusIcon = result.success ? '✅' : '❌';
                    var score = result.performance_score || 0;
                    var scoreClass = score >= 90 ? 'test-result-success' : (score >= 70 ? 'test-result-warning' : 'test-result-failed');

                    detailsHtml += '<tr class="' + statusClass + '">';
                    detailsHtml += '<td><strong>' + result.test + '</strong></td>';
                    detailsHtml += '<td>' + (result.category || 'Performance Testing') + '</td>';
                    detailsHtml += '<td><span class="' + scoreClass + '">' + score + '/100</span></td>';
                    detailsHtml += '<td>' + statusIcon + ' ' + (result.success ? 'PASSED' : 'FAILED') + '</td>';
                    detailsHtml += '<td><div class="code-block">' + JSON.stringify(result.details, null, 2) + '</div></td>';
                    detailsHtml += '</tr>';
                });

                detailsHtml += '</tbody></table>';
            } else {
                detailsHtml += '<p>No detailed performance test results available.</p>';
            }

            // Performance Analysis
            if (data.performance_analysis) {
                detailsHtml += '<h3>📊 Performance Analysis</h3>';
                detailsHtml += '<div class="test-summary-box">';
                detailsHtml += '<ul>';
                $.each(data.performance_analysis, function(key, value) {
                    detailsHtml += '<li><strong>' + key.replace(/_/g, ' ').toUpperCase() + ':</strong> ' + value + '</li>';
                });
                detailsHtml += '</ul>';
                detailsHtml += '</div>';
            }

            // Performance Thresholds
            if (data.performance_thresholds) {
                detailsHtml += '<h3>🎯 Performance Thresholds</h3>';
                detailsHtml += '<div class="test-summary-box">';
                detailsHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">';
                $.each(data.performance_thresholds, function(category, thresholds) {
                    detailsHtml += '<div><strong>' + category.replace(/_/g, ' ').toUpperCase() + ':</strong><br>';
                    $.each(thresholds, function(level, value) {
                        var color = level === 'excellent' ? 'green' : (level === 'good' ? 'blue' : (level === 'acceptable' ? 'orange' : 'red'));
                        detailsHtml += '<span style="color: ' + color + ';">• ' + level.toUpperCase() + ': ' + value + (typeof value === 'number' ? 'ms' : '') + '</span><br>';
                    });
                    detailsHtml += '</div>';
                });
                detailsHtml += '</div>';
                detailsHtml += '</div>';
            }

            // Implementation notes
            if (data.implementation_notes) {
                detailsHtml += '<h3>📝 Implementation Notes</h3>';
                detailsHtml += '<div class="code-block">' + JSON.stringify(data.implementation_notes, null, 2) + '</div>';
            }

            $('#performance-test-details').html(detailsHtml);
        }

        function displayPerformanceErrorResults(errorData) {
            var errorHtml = '<div class="notice notice-error">';
            errorHtml += '<h3>❌ Performance Test Error</h3>';
            errorHtml += '<div class="code-block">' + JSON.stringify(errorData, null, 2) + '</div>';
            errorHtml += '</div>';

            $('#performance-test-summary').html(errorHtml);
            $('#performance-test-details').empty();
        }

        // Test Coverage Analysis handlers (Step 5.1.10)
        $('#run-coverage-analysis').on('click', function() {
            var $button = $(this);
            var $status = $('#test-status');
            var $results = $('#coverage-test-results');

            $button.prop('disabled', true);
            $status.show();
            $results.hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vd_test_coverage_analysis',
                    nonce: vd_test_nonce
                },
                timeout: 30000, // 30 seconds timeout for coverage analysis
                success: function(response) {
                    $status.hide();
                    $button.prop('disabled', false);

                    if (response.success) {
                        displayCoverageSuccessResults(response.data);
                    } else {
                        displayCoverageErrorResults(response.data);
                    }

                    $results.show();
                },
                error: function(xhr, status, error) {
                    $status.hide();
                    $button.prop('disabled', false);

                    displayCoverageErrorResults({
                        message: 'AJAX Error: ' + error,
                        status: status,
                        responseText: xhr.responseText
                    });

                    $results.show();
                }
            });
        });

        $('#run-specific-coverage').on('click', function() {
            var $button = $(this);
            var $status = $('#test-status');
            var $results = $('#coverage-test-results');
            var category = $('#coverage-category').val();

            $button.prop('disabled', true);
            $status.show();
            $results.hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vd_test_specific_coverage_category',
                    category: category,
                    nonce: vd_test_nonce
                },
                timeout: 20000, // 20 seconds timeout for category analysis
                success: function(response) {
                    $status.hide();
                    $button.prop('disabled', false);

                    if (response.success) {
                        displayCoverageSuccessResults(response.data);
                    } else {
                        displayCoverageErrorResults(response.data);
                    }

                    $results.show();
                },
                error: function(xhr, status, error) {
                    $status.hide();
                    $button.prop('disabled', false);

                    displayCoverageErrorResults({
                        message: 'AJAX Error: ' + error,
                        status: status,
                        responseText: xhr.responseText
                    });

                    $results.show();
                }
            });
        });

        function displayCoverageSuccessResults(data) {
            var summary = data.summary || data;
            var categoryResults = data.category_breakdown || [];
            var detailedResults = data.detailed_results || {};

            // Summary
            var summaryHtml = '<div class="test-summary-box">';
            summaryHtml += '<h3>📊 Tóm Tắt Test Coverage Analysis</h3>';
            summaryHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">';
            summaryHtml += '<div><strong>Framework:</strong> ' + (summary.framework || 'Coverage Analysis') + '</div>';
            summaryHtml += '<div><strong>Total Modules:</strong> ' + (summary.total_modules || '0') + '</div>';
            summaryHtml += '<div><strong>Total Lines:</strong> ' + (summary.total_lines || '0') + '</div>';
            summaryHtml += '<div><strong>Covered Lines:</strong> ' + (summary.covered_lines || '0') + '</div>';

            var coverage = summary.overall_coverage || 0;
            var coverageClass = coverage >= 95 ? 'test-result-success' : (coverage >= 85 ? 'test-result-warning' : 'test-result-failed');
            summaryHtml += '<div><strong>Overall Coverage:</strong> <span class="' + coverageClass + '">' + coverage + '%</span></div>';
            summaryHtml += '<div><strong>Target Coverage:</strong> ' + (summary.target_coverage || '95') + '%</div>';
            summaryHtml += '<div><strong>Coverage Gap:</strong> ' + (summary.coverage_gap || '0') + '%</div>';
            summaryHtml += '<div><strong>Execution Time:</strong> ' + (summary.execution_time || '0') + 'ms</div>';
            summaryHtml += '<div><strong>Status:</strong> <span class="' + (summary.status === 'EXCELLENT' ? 'test-result-success' : 'test-result-warning') + '">' + (summary.status || 'UNKNOWN') + '</span></div>';
            summaryHtml += '</div></div>';

            $('#coverage-test-summary').html(summaryHtml);

            // Detailed results
            var detailsHtml = '<h3>🔍 Chi Tiết Coverage Analysis</h3>';

            // Category breakdown
            if (categoryResults.length > 0) {
                detailsHtml += '<h4>📁 Coverage by Category</h4>';
                detailsHtml += '<table class="test-detail-table">';
                detailsHtml += '<thead><tr><th>Category</th><th>Modules</th><th>Coverage</th><th>Status</th><th>Details</th></tr></thead>';
                detailsHtml += '<tbody>';

                $.each(categoryResults, function(key, category) {
                    var coverageClass = category.coverage >= 95 ? 'test-result-success' :
                                       (category.coverage >= 85 ? 'test-result-warning' : 'test-result-failed');
                    var statusIcon = category.coverage >= 95 ? '✅' : (category.coverage >= 85 ? '⚠️' : '❌');

                    detailsHtml += '<tr>';
                    detailsHtml += '<td><strong>' + category.category + '</strong></td>';
                    detailsHtml += '<td>' + category.modules + '</td>';
                    detailsHtml += '<td><span class="' + coverageClass + '">' + category.coverage + '%</span></td>';
                    detailsHtml += '<td>' + statusIcon + ' ' + category.status + '</td>';
                    detailsHtml += '<td>Total: ' + (detailedResults[category.category]?.total_lines || '0') + ' lines</td>';
                    detailsHtml += '</tr>';
                });

                detailsHtml += '</tbody></table>';
            }

            // Gap Analysis
            if (data.gap_analysis) {
                detailsHtml += '<h4>🔍 Gap Analysis</h4>';
                detailsHtml += '<div class="test-summary-box">';

                var gaps = data.gap_analysis;
                if (gaps.critical_gaps && gaps.critical_gaps.length > 0) {
                    detailsHtml += '<h5 style="color: red;">🚨 Critical Gaps (Below 50%)</h5>';
                    detailsHtml += '<ul>';
                    $.each(gaps.critical_gaps, function(key, gap) {
                        detailsHtml += '<li><strong>' + gap.category + ':</strong> ' + gap.coverage + '% coverage</li>';
                    });
                    detailsHtml += '</ul>';
                }

                if (gaps.improvement_opportunities && gaps.improvement_opportunities.length > 0) {
                    detailsHtml += '<h5 style="color: orange;">📈 Improvement Opportunities</h5>';
                    detailsHtml += '<ul>';
                    $.each(gaps.improvement_opportunities, function(key, opportunity) {
                        detailsHtml += '<li><strong>' + opportunity.category + ':</strong> ' + opportunity.coverage + '% (Gap: ' + opportunity.gap + '%)</li>';
                    });
                    detailsHtml += '</ul>';
                }

                if (gaps.recommendations && gaps.recommendations.length > 0) {
                    detailsHtml += '<h5>💡 Recommendations</h5>';
                    detailsHtml += '<ul>';
                    $.each(gaps.recommendations, function(key, rec) {
                        var priorityColor = rec.priority === 'CRITICAL' ? 'red' : (rec.priority === 'HIGH' ? 'orange' : 'blue');
                        detailsHtml += '<li><strong style="color: ' + priorityColor + ';">[' + rec.priority + ']</strong> ' + rec.title + ' - ' + rec.description + ' (' + rec.estimated_effort + ')</li>';
                    });
                    detailsHtml += '</ul>';
                }

                detailsHtml += '</div>';
            }

            // Implementation notes
            if (data.implementation_notes) {
                detailsHtml += '<h4>📝 Implementation Notes</h4>';
                detailsHtml += '<div class="code-block">' + JSON.stringify(data.implementation_notes, null, 2) + '</div>';
            }

            $('#coverage-test-details').html(detailsHtml);
        }

        function displayCoverageErrorResults(errorData) {
            var errorHtml = '<div class="notice notice-error">';
            errorHtml += '<h3>❌ Coverage Analysis Error</h3>';
            errorHtml += '<div class="code-block">' + JSON.stringify(errorData, null, 2) + '</div>';
            errorHtml += '</div>';

            $('#coverage-test-summary').html(errorHtml);
            $('#coverage-test-details').empty();
        }
    });
    </script>
    <?php
}

/**
 * AJAX handler for integration testing framework
 */
function vd_test_integration_framework() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    check_ajax_referer('vd_test_nonce', 'nonce');

    try {
        require_once plugin_dir_path(__FILE__) . '../../../tests/integration/integration-test-framework.php';

        $integration_tests = new VD_Integration_Test_Framework();
        $results = $integration_tests->run_integration_tests();

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Integration testing failed: ' . $e->getMessage(),
            'error_code' => 'INTEGRATION_TEST_FAILED'
        ]);
    }
}

/**
 * AJAX handler for specific integration scenario testing
 */
function vd_test_specific_integration() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    check_ajax_referer('vd_test_nonce', 'nonce');

    $scenario = sanitize_text_field($_POST['scenario'] ?? '');

    try {
        require_once plugin_dir_path(__FILE__) . '../../../tests/integration/integration-test-framework.php';

        $integration_tests = new VD_Integration_Test_Framework();

        // Run specific scenario (simplified for this implementation)
        $results = $integration_tests->run_integration_tests();

        // Filter results for specific scenario if needed
        if (!empty($scenario)) {
            $filtered_results = array_filter($results['detailed_results'], function($result) use ($scenario) {
                return strpos($result['test'], $scenario) !== false;
            });
            $results['filtered_scenario'] = $scenario;
            $results['scenario_results'] = array_values($filtered_results);
        }

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Specific integration test failed: ' . $e->getMessage(),
            'error_code' => 'SPECIFIC_INTEGRATION_FAILED'
        ]);
    }
}

/**
 * AJAX handler for API endpoint testing framework
 */
function vd_test_api_endpoints() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    check_ajax_referer('vd_test_nonce', 'nonce');

    try {
        require_once plugin_dir_path(__FILE__) . '../../../tests/api/api-endpoint-test-framework.php';

        $api_tests = new VD_API_Endpoint_Test_Framework();
        $results = $api_tests->run_api_endpoint_tests();

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'API endpoint testing failed: ' . $e->getMessage(),
            'error_code' => 'API_TEST_FAILED'
        ]);
    }
}

/**
 * AJAX handler for specific API scenario testing
 */
function vd_test_specific_api_scenario() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    check_ajax_referer('vd_test_nonce', 'nonce');

    $scenario = sanitize_text_field($_POST['scenario'] ?? '');

    try {
        require_once plugin_dir_path(__FILE__) . '../../../tests/api/api-endpoint-test-framework.php';

        $api_tests = new VD_API_Endpoint_Test_Framework();

        // Run specific scenario (simplified for this implementation)
        $results = $api_tests->run_api_endpoint_tests();

        // Filter results for specific scenario if needed
        if (!empty($scenario)) {
            $filtered_results = array_filter($results['detailed_results'], function($result) use ($scenario) {
                return strpos($result['scenario_key'], $scenario) !== false;
            });
            $results['filtered_scenario'] = $scenario;
            $results['scenario_results'] = array_values($filtered_results);
        }

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Specific API test failed: ' . $e->getMessage(),
            'error_code' => 'SPECIFIC_API_TEST_FAILED'
        ]);
    }
}

/**
 * AJAX handler for performance testing framework
 */
function vd_test_performance() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    check_ajax_referer('vd_test_nonce', 'nonce');

    try {
        require_once plugin_dir_path(__FILE__) . '../../../tests/performance/performance-test-framework.php';

        $performance_tests = new VD_Performance_Test_Framework();
        $results = $performance_tests->run_performance_tests();

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Performance testing failed: ' . $e->getMessage(),
            'error_code' => 'PERFORMANCE_TEST_FAILED'
        ]);
    }
}

/**
 * AJAX handler for specific performance scenario testing
 */
function vd_test_specific_performance_scenario() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    check_ajax_referer('vd_test_nonce', 'nonce');

    $scenario = sanitize_text_field($_POST['scenario'] ?? '');

    try {
        require_once plugin_dir_path(__FILE__) . '../../../tests/performance/performance-test-framework.php';

        $performance_tests = new VD_Performance_Test_Framework();

        // Run specific scenario (simplified for this implementation)
        $results = $performance_tests->run_performance_tests();

        // Filter results for specific scenario if needed
        if (!empty($scenario)) {
            $filtered_results = array_filter($results['detailed_results'], function($result) use ($scenario) {
                return strpos($result['scenario_key'], $scenario) !== false;
            });
            $results['filtered_scenario'] = $scenario;
            $results['scenario_results'] = array_values($filtered_results);
        }

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Specific performance test failed: ' . $e->getMessage(),
            'error_code' => 'SPECIFIC_PERFORMANCE_TEST_FAILED'
        ]);
    }
}

/**
 * AJAX handler for test coverage analysis
 */
function vd_test_coverage_analysis() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    check_ajax_referer('vd_test_nonce', 'nonce');

    try {
        require_once plugin_dir_path(__FILE__) . '../../../tests/coverage/test-coverage-framework.php';

        $coverage_tests = new VD_Test_Coverage_Framework();
        $results = $coverage_tests->run_coverage_analysis();

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Coverage analysis failed: ' . $e->getMessage(),
            'error_code' => 'COVERAGE_ANALYSIS_FAILED'
        ]);
    }
}

/**
 * AJAX handler for specific coverage category analysis
 */
function vd_test_specific_coverage_category() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    check_ajax_referer('vd_test_nonce', 'nonce');

    $category = sanitize_text_field($_POST['category'] ?? '');

    try {
        require_once plugin_dir_path(__FILE__) . '../../../tests/coverage/test-coverage-framework.php';

        $coverage_tests = new VD_Test_Coverage_Framework();

        // Run full analysis (simplified for this implementation)
        $results = $coverage_tests->run_coverage_analysis();

        // Filter results for specific category if provided
        if (!empty($category)) {
            $filtered_results = [];
            if (isset($results['detailed_results'][$category])) {
                $filtered_results[$category] = $results['detailed_results'][$category];
            }

            $results['filtered_category'] = $category;
            $results['category_results'] = $filtered_results;
        }

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Specific coverage analysis failed: ' . $e->getMessage(),
            'error_code' => 'SPECIFIC_COVERAGE_ANALYSIS_FAILED'
        ]);
    }
}
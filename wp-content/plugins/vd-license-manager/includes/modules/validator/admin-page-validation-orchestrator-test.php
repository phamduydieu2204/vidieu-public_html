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
                        <td><strong>Security Testing</strong></td>
                        <td>Penetration testing suite</td>
                        <td>📋 Planned</td>
                        <td>Vulnerability scanning, Compliance validation</td>
                    </tr>
                </tbody>
            </table>
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
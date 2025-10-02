<?php
/**
 * Admin Page for Validation Orchestrator Testing
 *
 * Dedicated admin interface for testing Step 5.1.5 - Validation Orchestrator
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

/**
 * Add admin menu page for validation orchestrator testing
 */
function vd_add_validation_orchestrator_test_page() {
    add_submenu_page(
        'tools.php',
        'VD License - Step 5.1.5 Test',
        'Step 5.1.5 Test',
        'manage_options',
        'vd-step-515-test',
        'vd_render_validation_orchestrator_test_page'
    );
}

/**
 * Enqueue scripts for the test page
 */
function vd_enqueue_validation_orchestrator_test_scripts($hook) {
    if ($hook !== 'tools_page_vd-step-515-test') {
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
        <h1>🧪 Step 5.1.5 - Validation Orchestrator Test</h1>

        <div class="notice notice-info">
            <p><strong>Thông tin:</strong> Trang này test module Validation Orchestrator đã được trích xuất từ validator chính.</p>
            <p><strong>Namespace:</strong> <code>VD\LicenseManager\Validator\VD_License_Validation_Orchestrator</code></p>
            <p><strong>File:</strong> <code>class-vd-license-validation-orchestrator.php</code> (685+ dòng)</p>
        </div>

        <div class="card" style="max-width: none;">
            <h2>🚀 Test Controls</h2>
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
    });
    </script>
    <?php
}
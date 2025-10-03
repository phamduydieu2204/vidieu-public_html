<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validator Migration Monitor Admin Page
 * Step 5.1.11 - Monitor validator migration status and control migration process
 *
 * @package VD_License_Manager
 * @version 1.0.0
 * @since 2025-01-03
 */

// Add admin menu
add_action('admin_menu', 'vd_add_validator_migration_monitor_page');

/**
 * Add admin menu page for migration monitoring
 */
function vd_add_validator_migration_monitor_page() {
    add_submenu_page(
        'tools.php',
        'VD Validator Migration Monitor',
        'VD Validator Migration',
        'manage_options',
        'vd-validator-migration',
        'vd_render_validator_migration_monitor_page'
    );
}

/**
 * Render the migration monitor page
 */
function vd_render_validator_migration_monitor_page() {
    // Load required classes
    $migration_file = plugin_dir_path(__FILE__) . 'class-vd-validator-migration.php';
    if (file_exists($migration_file)) {
        require_once $migration_file;
    }

    $facade_file = plugin_dir_path(__FILE__) . 'class-vd-license-validator-facade.php';
    if (file_exists($facade_file)) {
        require_once $facade_file;
    }

    // Get migration status
    $migration_status = class_exists('VD_Validator_Migration')
        ? VD_Validator_Migration::get_migration_status()
        : ['error' => 'Migration class not available'];

    ?>
    <div class="wrap">
        <h1>🔄 VD Validator Migration Monitor</h1>
        <p>Monitor and control the migration from monolithic VD_License_Validator to modular Facade pattern</p>

        <!-- Migration Status Overview -->
        <div class="card" style="max-width: none;">
            <h2>📊 Migration Status Overview</h2>

            <?php if (isset($migration_status['error'])): ?>
                <div class="notice notice-error">
                    <p><strong>Error:</strong> <?php echo esc_html($migration_status['error']); ?></p>
                </div>
            <?php else: ?>
                <div class="migration-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0;">

                    <div class="status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                        <h3>🎯 Migration Configuration</h3>
                        <ul>
                            <li><strong>Facade Enabled:</strong> <?php echo $migration_status['config']['enable_facade'] ? '✅ Yes' : '❌ No'; ?></li>
                            <li><strong>Legacy Fallback:</strong> <?php echo $migration_status['config']['enable_legacy_fallback'] ? '✅ Yes' : '❌ No'; ?></li>
                            <li><strong>Migration Mode:</strong> <?php echo esc_html($migration_status['config']['migration_mode']); ?></li>
                            <li><strong>Debug Mode:</strong> <?php echo $migration_status['config']['debug_mode'] ? '✅ Enabled' : '❌ Disabled'; ?></li>
                        </ul>
                    </div>

                    <div class="status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                        <h3>🔧 Component Availability</h3>
                        <ul>
                            <li><strong>Facade Available:</strong> <?php echo $migration_status['facade_available'] ? '✅ Available' : '❌ Not Found'; ?></li>
                            <li><strong>Legacy Available:</strong> <?php echo $migration_status['legacy_available'] ? '✅ Available' : '❌ Not Found'; ?></li>
                            <li><strong>Migration Applied:</strong> <?php echo $migration_status['migration_applied'] ? '✅ Applied' : '❌ Not Applied'; ?></li>
                        </ul>
                    </div>

                    <?php if (isset($migration_status['facade_test_result'])): ?>
                    <div class="status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                        <h3>🧪 Facade Test Results</h3>
                        <?php
                        $facade_status = $migration_status['facade_test_result']['migration_status'];
                        $total_methods = $facade_status['total_methods'] ?? 0;
                        $migrated_methods = $facade_status['migrated_methods'] ?? 0;
                        $migration_percentage = $facade_status['migration_percentage'] ?? 0;
                        ?>
                        <ul>
                            <li><strong>Total Methods:</strong> <?php echo $total_methods; ?></li>
                            <li><strong>Migrated Methods:</strong> <?php echo $migrated_methods; ?></li>
                            <li><strong>Migration Progress:</strong>
                                <span style="color: <?php echo $migration_percentage >= 50 ? 'green' : 'orange'; ?>;">
                                    <?php echo $migration_percentage; ?>%
                                </span>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        </div>

        <!-- Module Status -->
        <?php if (isset($migration_status['facade_test_result']['migration_status']['modules_loaded'])): ?>
        <div class="card" style="max-width: none;">
            <h2>🔧 Extracted Modules Status</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Implementation Step</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $modules = $migration_status['facade_test_result']['migration_status']['modules_loaded'];
                    $module_info = [
                        'validation_utils' => ['VD License Validation Utils', 'Database utilities, format validation, error handling', 'Step 5.1.2'],
                        'expiry_processor' => ['VD License Expiry Processor', 'License expiry validation and batch processing', 'Step 5.1.3'],
                        'status_controller' => ['VD License Status Transition Controller', 'Status transitions, notifications, business rules', 'Step 5.1.4'],
                        'orchestrator' => ['VD License Validation Orchestrator', 'Central validation coordination', 'Step 5.1.5']
                    ];

                    foreach ($module_info as $key => $info):
                        $loaded = $modules[$key] ?? false;
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($info[0]); ?></strong></td>
                        <td>
                            <?php if ($loaded): ?>
                                <span style="color: green;">✅ Loaded</span>
                            <?php else: ?>
                                <span style="color: red;">❌ Not Loaded</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($info[1]); ?></td>
                        <td><?php echo esc_html($info[2]); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Migration Controls -->
        <div class="card" style="max-width: none;">
            <h2>🎛️ Migration Controls</h2>

            <div style="margin-bottom: 20px;">
                <button type="button" id="test-facade" class="button button-primary" style="margin-right: 10px;">
                    🧪 Test Facade Functionality
                </button>

                <button type="button" id="refresh-status" class="button" style="margin-right: 10px;">
                    🔄 Refresh Status
                </button>

                <button type="button" id="create-backup" class="button button-secondary" style="margin-right: 10px;">
                    💾 Create Backup
                </button>

                <?php if ($migration_status['migration_applied'] ?? false): ?>
                <button type="button" id="rollback-migration" class="button" style="background: orange; color: white;">
                    ⏪ Rollback to Legacy
                </button>
                <?php endif; ?>
            </div>

            <div class="notice notice-info">
                <p><strong>Safe Migration Process:</strong> The facade pattern maintains full backward compatibility. All existing code will continue to work without changes.</p>
            </div>
        </div>

        <!-- Test Results -->
        <div id="test-results" style="display: none;">
            <div class="card" style="max-width: none;">
                <h2>🧪 Test Results</h2>
                <div id="test-content"></div>
            </div>
        </div>

        <!-- Detailed Migration Status -->
        <?php if (isset($migration_status['facade_test_result']['migration_status']['detailed_status'])): ?>
        <div class="card" style="max-width: none;">
            <h2>📋 Detailed Method Migration Status</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Method Name</th>
                        <th>Migration Status</th>
                        <th>Target Module</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $detailed_status = $migration_status['facade_test_result']['migration_status']['detailed_status'];
                    $method_modules = [
                        'validate_license_key_format' => 'Validation Utils',
                        'validate_license_expiry' => 'Expiry Processor',
                        'vd_validate_license_key' => 'Orchestrator',
                        'send_status_change_notification' => 'Status Controller',
                        'update_expired_license_statuses' => 'Expiry Processor',
                        'get_detailed_validation' => 'Orchestrator',
                        'track_status_history' => 'Status Controller'
                    ];

                    foreach ($detailed_status as $method => $status):
                        $module = $method_modules[$method] ?? 'Unknown';
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($method); ?></code></td>
                        <td>
                            <?php if ($status === 'MIGRATED'): ?>
                                <span style="color: green;">✅ Migrated</span>
                            <?php else: ?>
                                <span style="color: orange;">⚠️ Legacy</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($module); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

    <script>
    jQuery(document).ready(function($) {

        $('#test-facade').on('click', function() {
            var $button = $(this);
            var $results = $('#test-results');
            var $content = $('#test-content');

            $button.prop('disabled', true).text('🧪 Testing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vd_test_facade_functionality'
                },
                success: function(response) {
                    $button.prop('disabled', false).text('🧪 Test Facade Functionality');

                    if (response.success) {
                        $content.html('<div class="notice notice-success"><h3>✅ Facade Test Successful</h3><pre>' + JSON.stringify(response.data, null, 2) + '</pre></div>');
                    } else {
                        $content.html('<div class="notice notice-error"><h3>❌ Facade Test Failed</h3><pre>' + JSON.stringify(response.data, null, 2) + '</pre></div>');
                    }

                    $results.show();
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('🧪 Test Facade Functionality');
                    $content.html('<div class="notice notice-error"><h3>❌ AJAX Error</h3><p>' + error + '</p></div>');
                    $results.show();
                }
            });
        });

        $('#refresh-status').on('click', function() {
            location.reload();
        });

        $('#create-backup').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true).text('💾 Creating Backup...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vd_create_migration_backup'
                },
                success: function(response) {
                    $button.prop('disabled', false).text('💾 Create Backup');
                    alert(response.success ? 'Backup created successfully!' : 'Backup failed: ' + response.data);
                },
                error: function() {
                    $button.prop('disabled', false).text('💾 Create Backup');
                    alert('Backup request failed');
                }
            });
        });

        $('#rollback-migration').on('click', function() {
            if (confirm('Are you sure you want to rollback to legacy validator? This will revert all migration changes.')) {
                var $button = $(this);
                $button.prop('disabled', true).text('⏪ Rolling back...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vd_rollback_migration'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('⏪ Rollback to Legacy');
                        alert(response.success ? 'Rollback successful! Please refresh the page.' : 'Rollback failed: ' + response.data);
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('⏪ Rollback to Legacy');
                        alert('Rollback request failed');
                    }
                });
            }
        });
    });
    </script>

    <style>
    .migration-status-grid .status-card h3 {
        margin-top: 0;
        color: #23282d;
        border-bottom: 1px solid #eee;
        padding-bottom: 8px;
    }

    .migration-status-grid .status-card ul {
        margin: 10px 0;
        padding-left: 20px;
    }

    .migration-status-grid .status-card li {
        margin: 8px 0;
    }

    #test-content pre {
        background: #f1f1f1;
        padding: 15px;
        border-radius: 3px;
        overflow-x: auto;
        max-height: 400px;
    }
    </style>
    <?php
}

// AJAX Handlers
add_action('wp_ajax_vd_test_facade_functionality', 'vd_ajax_test_facade_functionality');
add_action('wp_ajax_vd_create_migration_backup', 'vd_ajax_create_migration_backup');
add_action('wp_ajax_vd_rollback_migration', 'vd_ajax_rollback_migration');

/**
 * AJAX handler for testing facade functionality
 */
function vd_ajax_test_facade_functionality() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    try {
        $facade_file = plugin_dir_path(__FILE__) . 'class-vd-license-validator-facade.php';
        if (file_exists($facade_file)) {
            require_once $facade_file;
        }

        if (class_exists('VD_License_Validator_Facade')) {
            $facade = VD_License_Validator_Facade::get_instance();
            $test_result = $facade->test_facade_functionality();
            wp_send_json_success($test_result);
        } else {
            wp_send_json_error(['message' => 'Facade class not available']);
        }

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * AJAX handler for creating migration backup
 */
function vd_ajax_create_migration_backup() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    try {
        $migration_file = plugin_dir_path(__FILE__) . 'class-vd-validator-migration.php';
        if (file_exists($migration_file)) {
            require_once $migration_file;
        }

        if (class_exists('VD_Validator_Migration')) {
            $result = VD_Validator_Migration::create_migration_backup();
            wp_send_json_success(['backup_created' => $result]);
        } else {
            wp_send_json_error('Migration class not available');
        }

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

/**
 * AJAX handler for rollback migration
 */
function vd_ajax_rollback_migration() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    try {
        $migration_file = plugin_dir_path(__FILE__) . 'class-vd-validator-migration.php';
        if (file_exists($migration_file)) {
            require_once $migration_file;
        }

        if (class_exists('VD_Validator_Migration') && class_exists('VD_License_Manager')) {
            $manager = VD_License_Manager::get_instance();
            $result = VD_Validator_Migration::rollback_migration($manager);
            wp_send_json_success(['rollback_successful' => $result]);
        } else {
            wp_send_json_error('Required classes not available');
        }

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
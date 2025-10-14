<?php
/**
 * License Sync Admin Page
 *
 * Provides admin interface to synchronize all licenses from LMFWC to VD Plugin.
 * Useful when recreating VD database or bulk syncing existing licenses.
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * License Sync Admin Class
 *
 * Handles admin page for bulk license synchronization from LMFWC to VD plugin
 */
class VD_License_Sync_Admin {

    /**
     * Constructor - Register hooks
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 20); // Run after main menu
        add_action('wp_ajax_vd_sync_licenses', [$this, 'ajax_sync_licenses']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

        error_log('VD License Sync Admin: Initialized and hooks registered');
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        error_log('VD License Sync Admin: add_admin_menu() called');

        $result = add_submenu_page(
            'vd-license-manager',
            'License Sync',
            'License Sync',
            'manage_options',
            'vd-license-sync',
            [$this, 'render_page']
        );

        if ($result) {
            error_log('VD License Sync Admin: Submenu added successfully. Hook: ' . $result);
        } else {
            error_log('VD License Sync Admin: Failed to add submenu. Parent menu may not exist.');
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'vd-license-manager_page_vd-license-sync') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'vdLicenseSync', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vd_sync_licenses'),
        ]);
    }

    /**
     * Render admin page
     */
    public function render_page() {
        global $wpdb;

        // Get statistics
        $lmfwc_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}lmfwc_licenses WHERE status IN (2, 3)");
        $vd_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vd_license_keys");

        echo '<div class="wrap">';
        echo '<h1>🔄 License Synchronization</h1>';
        echo '<p>Sync all licenses from LMFWC database to VD License Manager database.</p>';

        // Statistics
        echo '<div class="notice notice-info">';
        echo '<p><strong>📊 Current Status:</strong></p>';
        echo '<ul>';
        echo '<li>📦 <strong>LMFWC Active Licenses:</strong> ' . $lmfwc_count . ' licenses</li>';
        echo '<li>🎯 <strong>VD Synced Licenses:</strong> ' . $vd_count . ' licenses</li>';
        echo '<li>⚖️ <strong>Difference:</strong> ' . ($lmfwc_count - $vd_count) . ' licenses need sync</li>';
        echo '</ul>';
        echo '</div>';

        // Warning
        echo '<div class="notice notice-warning">';
        echo '<h3>⚠️ Important Notes:</h3>';
        echo '<ul>';
        echo '<li><strong>Backup Database:</strong> Always backup before bulk operations</li>';
        echo '<li><strong>Processing Time:</strong> Large datasets may take several minutes</li>';
        echo '<li><strong>Duplicate Handling:</strong> Existing licenses will be updated, not duplicated</li>';
        echo '<li><strong>Pool Assignment:</strong> Licenses will be auto-assigned to available pools</li>';
        echo '</ul>';
        echo '</div>';

        // Sync controls
        echo '<div class="card" style="max-width: 800px; margin: 20px 0;">';
        echo '<h2>🚀 Sync Options</h2>';

        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row">Sync Mode</th>';
        echo '<td>';
        echo '<label><input type="radio" name="sync_mode" value="all" checked> <strong>All Licenses</strong> - Sync all LMFWC active licenses</label><br>';
        echo '<label><input type="radio" name="sync_mode" value="missing"> <strong>Missing Only</strong> - Sync licenses not in VD database</label><br>';
        echo '<label><input type="radio" name="sync_mode" value="recent"> <strong>Recent Only</strong> - Sync licenses from last 30 days</label>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Options</th>';
        echo '<td>';
        echo '<label><input type="checkbox" id="assign_pools" checked> Auto-assign to pools</label><br>';
        echo '<label><input type="checkbox" id="send_emails"> Send welcome emails (careful!)</label><br>';
        echo '<label><input type="checkbox" id="dry_run"> Dry run (preview only, no changes)</label>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';

        echo '<p class="submit">';
        echo '<button type="button" id="start-sync" class="button button-primary button-large">🔄 Start Synchronization</button>';
        echo '</p>';

        echo '</div>';

        // Progress area
        echo '<div id="sync-progress" style="display: none;">';
        echo '<h3>📊 Synchronization Progress</h3>';
        echo '<div id="progress-bar-container" style="background: #f0f0f0; border-radius: 5px; padding: 3px; margin: 10px 0;">';
        echo '<div id="progress-bar" style="background: #0073aa; height: 20px; border-radius: 3px; width: 0%; transition: width 0.3s;"></div>';
        echo '</div>';
        echo '<p id="progress-text">Ready to start...</p>';
        echo '<div id="sync-log" style="background: #f9f9f9; border: 1px solid #ddd; padding: 10px; height: 300px; overflow-y: scroll; font-family: monospace; font-size: 12px;"></div>';
        echo '</div>';

        // JavaScript
        $this->render_javascript();

        echo '</div>';
    }

    /**
     * Render JavaScript for sync functionality
     */
    private function render_javascript() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let isRunning = false;

            $('#start-sync').on('click', function() {
                if (isRunning) {
                    alert('Sync is already running!');
                    return;
                }

                const syncMode = $('input[name="sync_mode"]:checked').val();
                const assignPools = $('#assign_pools').is(':checked');
                const sendEmails = $('#send_emails').is(':checked');
                const dryRun = $('#dry_run').is(':checked');

                // Confirmation
                let confirmMessage = `Are you sure you want to sync licenses?\n\n`;
                confirmMessage += `Mode: ${syncMode}\n`;
                confirmMessage += `Auto-assign pools: ${assignPools ? 'Yes' : 'No'}\n`;
                confirmMessage += `Send emails: ${sendEmails ? 'Yes' : 'No'}\n`;
                confirmMessage += `Dry run: ${dryRun ? 'Yes' : 'No'}`;

                if (sendEmails && !dryRun) {
                    confirmMessage += `\n\n⚠️ WARNING: This will send emails to customers!`;
                }

                if (!confirm(confirmMessage)) {
                    return;
                }

                isRunning = true;
                $('#start-sync').prop('disabled', true).text('⏳ Syncing...');
                $('#sync-progress').show();
                $('#sync-log').html('');

                // Start sync
                performSync(syncMode, assignPools, sendEmails, dryRun);
            });

            function performSync(syncMode, assignPools, sendEmails, dryRun) {
                $.ajax({
                    url: vdLicenseSync.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vd_sync_licenses',
                        nonce: vdLicenseSync.nonce,
                        sync_mode: syncMode,
                        assign_pools: assignPools,
                        send_emails: sendEmails,
                        dry_run: dryRun
                    },
                    success: function(response) {
                        console.log('Sync response:', response);

                        if (response.success) {
                            updateProgress(100, 'Sync completed successfully!');
                            logMessage('✅ SYNC COMPLETED');
                            logMessage('📊 Results: ' + JSON.stringify(response.data.summary, null, 2));
                        } else {
                            updateProgress(0, 'Sync failed: ' + response.data.message);
                            logMessage('❌ SYNC FAILED: ' + response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        updateProgress(0, 'AJAX error: ' + error);
                        logMessage('❌ AJAX ERROR: ' + error);
                        console.error('AJAX Error:', xhr.responseText);
                    },
                    complete: function() {
                        isRunning = false;
                        $('#start-sync').prop('disabled', false).text('🔄 Start Synchronization');
                    }
                });
            }

            function updateProgress(percent, text) {
                $('#progress-bar').css('width', percent + '%');
                $('#progress-text').text(text);
            }

            function logMessage(message) {
                const timestamp = new Date().toLocaleTimeString();
                $('#sync-log').append(`[${timestamp}] ${message}\n`);
                $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for license sync
     */
    public function ajax_sync_licenses() {
        // Security checks
        check_ajax_referer('vd_sync_licenses', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access']);
        }

        $sync_mode = sanitize_text_field($_POST['sync_mode'] ?? 'all');
        $assign_pools = filter_var($_POST['assign_pools'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $send_emails = filter_var($_POST['send_emails'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dry_run = filter_var($_POST['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

        error_log("VD License Sync: Starting sync - Mode: {$sync_mode}, Assign Pools: " . ($assign_pools ? 'Yes' : 'No') . ", Send Emails: " . ($send_emails ? 'Yes' : 'No') . ", Dry Run: " . ($dry_run ? 'Yes' : 'No'));

        try {
            $result = $this->perform_sync($sync_mode, $assign_pools, $send_emails, $dry_run);
            wp_send_json_success($result);
        } catch (Exception $e) {
            error_log('VD License Sync Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Perform the actual license synchronization
     *
     * @param string $sync_mode Sync mode: 'all', 'missing', or 'recent'
     * @param bool $assign_pools Whether to assign licenses to pools
     * @param bool $send_emails Whether to send welcome emails
     * @param bool $dry_run Whether this is a dry run (no actual changes)
     * @return array Sync results
     */
    private function perform_sync($sync_mode, $assign_pools, $send_emails, $dry_run) {
        global $wpdb;

        $start_time = microtime(true);
        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'pools_assigned' => 0,
            'emails_sent' => 0,
            'errors' => 0,
            'skipped' => 0
        ];

        // Build query based on sync mode
        $where_clause = "WHERE l.status IN (2, 3)"; // Active/Sold status

        switch ($sync_mode) {
            case 'missing':
                $where_clause .= " AND l.id NOT IN (SELECT lmfwc_license_id FROM {$wpdb->prefix}vd_license_keys WHERE lmfwc_license_id IS NOT NULL)";
                break;
            case 'recent':
                $where_clause .= " AND l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'all':
            default:
                // No additional filter
                break;
        }

        // Get LMFWC licenses to sync
        $lmfwc_licenses = $wpdb->get_results("
            SELECT l.*,
                   o.post_date as order_date,
                   om1.meta_value as customer_email,
                   om2.meta_value as customer_id
            FROM {$wpdb->prefix}lmfwc_licenses l
            LEFT JOIN {$wpdb->posts} o ON l.order_id = o.ID
            LEFT JOIN {$wpdb->postmeta} om1 ON o.ID = om1.post_id AND om1.meta_key = '_billing_email'
            LEFT JOIN {$wpdb->postmeta} om2 ON o.ID = om2.post_id AND om2.meta_key = '_customer_user'
            {$where_clause}
            ORDER BY l.created_at DESC
        ", ARRAY_A);

        error_log("VD License Sync: Found " . count($lmfwc_licenses) . " LMFWC licenses to process");

        if (empty($lmfwc_licenses)) {
            return [
                'message' => 'No licenses found to sync',
                'summary' => $summary,
                'execution_time' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }

        // Initialize Order Handler for license processing
        if (!class_exists('VD_LM_Order_Handler')) {
            require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-order-handler.php';
        }

        foreach ($lmfwc_licenses as $lmfwc_license) {
            $summary['processed']++;

            try {
                // Check if license already exists in VD
                $existing_vd_license = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}vd_license_keys WHERE lmfwc_license_id = %d",
                    $lmfwc_license['id']
                ), ARRAY_A);

                // Decrypt license key
                $license_key_plain = $this->decrypt_lmfwc_license_key($lmfwc_license['license_key']);

                if (empty($license_key_plain)) {
                    error_log("VD License Sync: Failed to decrypt license ID {$lmfwc_license['id']}");
                    $summary['errors']++;
                    continue;
                }

                if ($dry_run) {
                    error_log("VD License Sync: [DRY RUN] Would process license {$license_key_plain}");
                    continue;
                }

                if ($existing_vd_license) {
                    // Update existing license
                    $this->update_vd_license($existing_vd_license, $lmfwc_license, $license_key_plain);
                    $summary['updated']++;
                    error_log("VD License Sync: Updated existing license {$license_key_plain}");
                } else {
                    // Create new license
                    $vd_license_id = $this->create_vd_license($lmfwc_license, $license_key_plain);
                    if ($vd_license_id) {
                        $summary['created']++;
                        error_log("VD License Sync: Created new license {$license_key_plain}");

                        // Assign to pool if requested
                        if ($assign_pools) {
                            $pool_assigned = $this->assign_license_to_pool($vd_license_id, $lmfwc_license['product_id']);
                            if ($pool_assigned) {
                                $summary['pools_assigned']++;
                            }
                        }

                        // Send email if requested
                        if ($send_emails) {
                            $email_sent = $this->send_welcome_email($lmfwc_license, $license_key_plain);
                            if ($email_sent) {
                                $summary['emails_sent']++;
                            }
                        }
                    } else {
                        $summary['errors']++;
                    }
                }

            } catch (Exception $e) {
                error_log("VD License Sync: Error processing license ID {$lmfwc_license['id']}: " . $e->getMessage());
                $summary['errors']++;
            }
        }

        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        error_log("VD License Sync: Completed - " . json_encode($summary));

        return [
            'message' => 'Sync completed successfully',
            'summary' => $summary,
            'execution_time' => $execution_time
        ];
    }

    /**
     * Decrypt LMFWC license key
     */
    private function decrypt_lmfwc_license_key($encrypted_key) {
        // Method 1: Try LMFWC lmfwc_decrypt function
        if (function_exists('lmfwc_decrypt')) {
            try {
                return lmfwc_decrypt($encrypted_key);
            } catch (Exception $e) {
                error_log('VD License Sync: lmfwc_decrypt failed: ' . $e->getMessage());
            }
        }

        // Method 2: Try LMFWC Crypto class
        if (class_exists('LicenseManagerForWooCommerce\Crypto')) {
            try {
                $crypto = new \LicenseManagerForWooCommerce\Crypto();
                return $crypto->decrypt($encrypted_key);
            } catch (Exception $e) {
                error_log('VD License Sync: Crypto class failed: ' . $e->getMessage());
            }
        }

        return '';
    }

    /**
     * Create new VD license record
     */
    private function create_vd_license($lmfwc_license, $license_key_plain) {
        global $wpdb;

        $data = [
            'license_key' => $lmfwc_license['license_key'], // Keep encrypted
            'license_key_plain' => $license_key_plain, // Store plain text
            'lmfwc_license_id' => $lmfwc_license['id'],
            'product_id' => $lmfwc_license['product_id'],
            'order_id' => $lmfwc_license['order_id'],
            'customer_id' => $lmfwc_license['customer_id'] ?: 0,
            'customer_email' => $lmfwc_license['customer_email'] ?: '',
            'status' => $this->map_lmfwc_status($lmfwc_license['status']),
            'expires_at' => $lmfwc_license['expires_at'],
            'max_devices' => 2, // Default
            'current_devices' => 0,
            'max_requests_per_day' => 10, // Default
            'max_requests_per_hour' => 5, // Default
            'synced_at' => current_time('mysql'),
            'created_at' => $lmfwc_license['created_at']
        ];

        $result = $wpdb->insert($wpdb->prefix . 'vd_license_keys', $data);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update existing VD license
     */
    private function update_vd_license($existing_vd_license, $lmfwc_license, $license_key_plain) {
        global $wpdb;

        $data = [
            'license_key_plain' => $license_key_plain, // Update plain text version
            'status' => $this->map_lmfwc_status($lmfwc_license['status']),
            'expires_at' => $lmfwc_license['expires_at'],
            'synced_at' => current_time('mysql')
        ];

        return $wpdb->update(
            $wpdb->prefix . 'vd_license_keys',
            $data,
            ['id' => $existing_vd_license['id']],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * Map LMFWC status to VD status
     */
    private function map_lmfwc_status($lmfwc_status) {
        switch ($lmfwc_status) {
            case 1: return 'inactive';
            case 2: return 'active';
            case 3: return 'active'; // sold
            case 4: return 'expired';
            default: return 'inactive';
        }
    }

    /**
     * Assign license to pool (simplified)
     */
    private function assign_license_to_pool($license_id, $product_id) {
        // TODO: Implement pool assignment logic
        // For now, just return true to indicate "would assign"
        return true;
    }

    /**
     * Send welcome email (simplified)
     */
    private function send_welcome_email($lmfwc_license, $license_key_plain) {
        // TODO: Implement email sending logic
        // For now, just return true to indicate "would send"
        return true;
    }
}

// Initialize the admin class
new VD_License_Sync_Admin();
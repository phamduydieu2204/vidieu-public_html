<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Rule Expiry Automation Module
 *
 * Step 2.2.2 - Automated expiry processing and batch operations
 * PSR-4 Namespace: VD\LicenseManager\Rules
 *
 * Handles automated license expiry processing, batch updates, escalation logic,
 * and scheduled automation for expired license management
 * Part of the modular refactor initiative - Step 2.2.2
 *
 * @package VD_License_Manager
 * @subpackage Rules
 * @since 1.5.0-rc.2
 * @version Step 2.2.2
 */
class VD_License_Rule_Expiry_Automation {

    /**
     * Module version
     *
     * @since 1.5.0-rc.2
     * @var string
     */
    const VERSION = '1.5.0-rc.2';

    /**
     * Module name
     *
     * @since 1.5.0-rc.2
     * @var string
     */
    const MODULE_NAME = 'Expiry Automation';

    /**
     * Expiry core module instance
     *
     * @since 1.5.0-rc.2
     * @var VD_License_Rule_Expiry_Core|null
     */
    private $expiry_core = null;

    /**
     * Status business logic module instance
     *
     * @since 1.5.0-rc.2
     * @var VD_License_Status_Business|null
     */
    private $status_business = null;

    /**
     * Default automation configuration
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $default_config = array(
        'batch_size' => 100,
        'max_execution_time' => 300,
        'grace_period_hours' => 72,
        'escalation_enabled' => true,
        'audit_enabled' => true,
        'notification_enabled' => true,
        'transaction_enabled' => true,
        'optimistic_locking' => true
    );

    /**
     * Module statistics
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $statistics = array(
        'batches_processed' => 0,
        'licenses_updated' => 0,
        'licenses_skipped' => 0,
        'errors_encountered' => 0,
        'total_execution_time' => 0,
        'average_batch_time' => 0,
        'schedule_runs' => 0
    );

    /**
     * Constructor
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Rule_Expiry_Core $expiry_core Expiry core module
     * @param VD_License_Status_Business $status_business Status business logic module
     */
    public function __construct($expiry_core = null, $status_business = null) {
        $this->expiry_core = $expiry_core;
        $this->status_business = $status_business;
        $this->init_wordpress_hooks();
    }

    /**
     * Initialize WordPress hooks for automation
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    private function init_wordpress_hooks() {
        // Register cron hook for scheduled updates
        add_action('vd_automatic_license_updates', array($this, 'execute_scheduled_update'));

        // Register custom cron schedules if needed
        add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));
    }

    /**
     * Set expiry core dependency
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Rule_Expiry_Core $expiry_core Expiry core module
     * @return void
     */
    public function set_expiry_core($expiry_core) {
        $this->expiry_core = $expiry_core;
    }

    /**
     * Set status business dependency
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Status_Business $status_business Status business logic module
     * @return void
     */
    public function set_status_business($status_business) {
        $this->status_business = $status_business;
    }

    /**
     * Get module information
     *
     * @since 1.5.0-rc.2
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => self::MODULE_NAME,
            'version' => self::VERSION,
            'description' => 'Automated expiry processing and batch operations module',
            'namespace' => 'VD\\LicenseManager\\Rules',
            'dependencies' => array('VD_License_Rule_Expiry_Core', 'VD_License_Status_Business'),
            'functions' => array(
                'update_expired_license_statuses',
                'process_expired_license_batch',
                'schedule_automatic_updates',
                'execute_automatic_status_update',
                'determine_target_status_for_expired_license',
                'get_escalation_configuration'
            ),
            'statistics' => $this->statistics
        );
    }

    /**
     * Update expired license statuses automatically
     * Main entry point for automatic status updates
     *
     * @since 1.5.0-rc.2 (Extracted from validator 4.2.4.3)
     * @param array $options Update options and filters
     * @return array Update results with detailed statistics
     */
    public function update_expired_license_statuses($options = array()) {
        $start_time = microtime(true);

        // Initialize options with defaults
        $options = array_merge($this->default_config, $options);

        $results = array(
            'total_processed' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'batch_results' => array(),
            'execution_time_ms' => 0,
            'dry_run' => $options['dry_run'] ?? false,
            'errors' => array()
        );

        try {
            // Validate configuration
            $validation_result = $this->validate_update_configuration($options);
            if (!$validation_result['valid']) {
                throw new Exception('Invalid update configuration: ' . $validation_result['error']);
            }

            // Get expired licenses for processing
            $expired_licenses = $this->get_expired_licenses_for_update($options);

            if (empty($expired_licenses)) {
                $results['message'] = 'No expired licenses found for update';
                return $results;
            }

            $results['total_processed'] = count($expired_licenses);

            // Process in batches for performance and safety
            $batches = array_chunk($expired_licenses, $options['batch_size']);

            foreach ($batches as $batch_index => $batch) {
                $batch_result = $this->process_expired_license_batch($batch, $options);

                $results['batch_results'][] = $batch_result;
                $results['updated_count'] += $batch_result['updated_count'];
                $results['skipped_count'] += $batch_result['skipped_count'];
                $results['error_count'] += $batch_result['error_count'];

                if (!empty($batch_result['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $batch_result['errors']);
                }

                // Log batch completion
                if ($options['audit_enabled']) {
                    $this->log_batch_update_completion($batch_index + 1, $batch_result, $options);
                }

                $this->statistics['batches_processed']++;
            }

            // Final validation
            $results['validation'] = $this->validate_update_results($results, $options);

        } catch (Exception $e) {
            $results['error_count']++;
            $results['errors'][] = array(
                'type' => 'system_error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            );

            if ($options['audit_enabled']) {
                $this->log_update_error('update_expired_license_statuses', $e, $options);
            }

            $this->statistics['errors_encountered']++;
        }

        $execution_time = (microtime(true) - $start_time) * 1000;
        $results['execution_time_ms'] = round($execution_time, 2);
        $this->statistics['total_execution_time'] += $execution_time;

        // Update statistics
        $this->statistics['licenses_updated'] += $results['updated_count'];
        $this->statistics['licenses_skipped'] += $results['skipped_count'];
        $this->update_average_batch_time();

        // Audit final results
        if ($options['audit_enabled']) {
            $this->audit_automatic_update_completion($results, $options);
        }

        return $results;
    }

    /**
     * Get expired licenses that need status updates
     *
     * @since 1.5.0-rc.2 (Extracted from validator 4.2.4.3)
     * @param array $options Update options
     * @return array Expired licenses ready for update
     */
    public function get_expired_licenses_for_update($options) {
        global $wpdb;

        $status_filters = $options['status_filters'] ?? array('active', 'pending');
        $grace_period_hours = $options['grace_period_hours'] ?? 72;
        $limit = $options['query_limit'] ?? 1000;

        // Build query for expired licenses
        $placeholders = implode(',', array_fill(0, count($status_filters), '%s'));
        $grace_cutoff = date('Y-m-d H:i:s', current_time('timestamp') - ($grace_period_hours * 3600));

        // Determine table source (VD internal or LMfWC)
        $table_name = $wpdb->prefix . 'vd_licenses';
        $fallback_table = $wpdb->prefix . 'lmfwc_licenses';

        // Check primary table first
        if ($this->table_exists($table_name)) {
            $query = $wpdb->prepare("
                SELECT
                    id,
                    license_key,
                    product_id,
                    status,
                    expires_at,
                    updated_at,
                    created_at,
                    last_status_change,
                    '%s' as table_name
                FROM {$table_name}
                WHERE status IN ($placeholders)
                    AND expires_at IS NOT NULL
                    AND expires_at < %s
                    AND (last_status_change IS NULL OR last_status_change < %s)
                ORDER BY expires_at ASC
                LIMIT %d
            ", array_merge(array($table_name), $status_filters, array($grace_cutoff, $grace_cutoff, $limit)));

            $licenses = $wpdb->get_results($query, ARRAY_A);

            if (!empty($licenses)) {
                return $licenses;
            }
        }

        // Fallback to LMfWC table if available
        if ($this->table_exists($fallback_table)) {
            $query = $wpdb->prepare("
                SELECT
                    id,
                    license_key,
                    product_id,
                    status,
                    expires_at,
                    updated_at,
                    created_at,
                    NULL as last_status_change,
                    '%s' as table_name
                FROM {$fallback_table}
                WHERE status IN (1, 2)
                    AND expires_at IS NOT NULL
                    AND expires_at < %s
                ORDER BY expires_at ASC
                LIMIT %d
            ", array($fallback_table, $grace_cutoff, $limit));

            return $wpdb->get_results($query, ARRAY_A);
        }

        return array();
    }

    /**
     * Process a batch of expired licenses
     *
     * @since 1.5.0-rc.2 (Extracted from validator 4.2.4.3)
     * @param array $licenses Batch of licenses to process
     * @param array $options Update options
     * @return array Batch processing results
     */
    public function process_expired_license_batch($licenses, $options) {
        global $wpdb;

        $batch_start_time = microtime(true);
        $batch_results = array(
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'errors' => array(),
            'updates' => array(),
            'execution_time_ms' => 0
        );

        // Start transaction for batch safety if enabled
        if (!$options['dry_run'] && $options['transaction_enabled']) {
            $wpdb->query('START TRANSACTION');
        }

        try {
            foreach ($licenses as $license) {
                $update_result = $this->process_single_expired_license($license, $options);

                if ($update_result['success']) {
                    $batch_results['updated_count']++;
                    $batch_results['updates'][] = $update_result;
                } elseif ($update_result['skipped'] ?? false) {
                    $batch_results['skipped_count']++;
                } else {
                    $batch_results['error_count']++;
                    $batch_results['errors'][] = $update_result['error'] ?? 'Unknown error';
                }
            }

            // Commit transaction if all successful
            if (!$options['dry_run'] && $options['transaction_enabled']) {
                if ($batch_results['error_count'] === 0) {
                    $wpdb->query('COMMIT');
                } else {
                    $wpdb->query('ROLLBACK');
                    throw new Exception('Batch failed with ' . $batch_results['error_count'] . ' errors');
                }
            }

        } catch (Exception $e) {
            if (!$options['dry_run'] && $options['transaction_enabled']) {
                $wpdb->query('ROLLBACK');
            }

            $batch_results['error_count']++;
            $batch_results['errors'][] = array(
                'type' => 'batch_error',
                'message' => $e->getMessage()
            );
        }

        $batch_results['execution_time_ms'] = round((microtime(true) - $batch_start_time) * 1000, 2);
        return $batch_results;
    }

    /**
     * Process a single expired license update
     *
     * @since 1.5.0-rc.2 (Extracted from validator 4.2.4.3)
     * @param array $license License data
     * @param array $options Update options
     * @return array Single license update result
     */
    public function process_single_expired_license($license, $options) {
        try {
            // Determine target status based on escalation rules
            $target_status_result = $this->determine_target_status_for_expired_license($license, $options);

            if (!$target_status_result['should_update']) {
                return array(
                    'success' => false,
                    'skipped' => true,
                    'license_id' => $license['id'],
                    'reason' => $target_status_result['skip_reason'] ?? 'No update needed'
                );
            }

            $new_status = $target_status_result['target_status'];

            // Validate status transition using Status Business Logic
            if ($this->status_business) {
                $transition_validation = $this->status_business->validate_status_transition(
                    $license['status'],
                    $new_status,
                    $license
                );

                if (!$transition_validation['valid']) {
                    return array(
                        'success' => false,
                        'skipped' => false,
                        'license_id' => $license['id'],
                        'error' => array(
                            'type' => 'transition_invalid',
                            'message' => $transition_validation['error']
                        )
                    );
                }
            }

            // Execute the status update
            if (!$options['dry_run']) {
                $update_result = $this->execute_automatic_status_update($license, $new_status, $options);

                if (!$update_result['success']) {
                    return array(
                        'success' => false,
                        'skipped' => false,
                        'license_id' => $license['id'],
                        'error' => $update_result['error']
                    );
                }
            }

            return array(
                'success' => true,
                'skipped' => false,
                'license_id' => $license['id'],
                'old_status' => $license['status'],
                'new_status' => $new_status,
                'update_reason' => $target_status_result['update_reason'],
                'dry_run' => $options['dry_run']
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'skipped' => false,
                'license_id' => $license['id'],
                'error' => array(
                    'type' => 'processing_error',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                )
            );
        }
    }

    /**
     * Determine target status for expired license based on escalation rules
     *
     * @since 1.5.0-rc.2 (Extracted from validator 4.2.4.3)
     * @param array $license License data
     * @param array $options Update options
     * @return array Target status determination result
     */
    public function determine_target_status_for_expired_license($license, $options) {
        $expires_at = strtotime($license['expires_at']);
        $current_time = current_time('timestamp');
        $days_expired = max(0, ceil(($current_time - $expires_at) / (24 * 3600)));

        // Check escalation rules if enabled
        if ($options['escalation_enabled']) {
            $escalation_config = $this->get_escalation_configuration($license);

            // Revocation check (30+ days by default)
            $revoke_threshold = $escalation_config['revoke_after_days'] ?? 30;
            if ($days_expired >= $revoke_threshold) {
                return array(
                    'should_update' => true,
                    'target_status' => 'revoked',
                    'update_reason' => sprintf('Auto-revoked after %d days expired (threshold: %d)', $days_expired, $revoke_threshold)
                );
            }

            // Suspension check (7+ days by default)
            $suspend_threshold = $escalation_config['suspend_after_days'] ?? 7;
            if ($days_expired >= $suspend_threshold) {
                return array(
                    'should_update' => true,
                    'target_status' => 'suspended',
                    'update_reason' => sprintf('Auto-suspended after %d days expired (threshold: %d)', $days_expired, $suspend_threshold)
                );
            }
        }

        // Default: mark as expired
        return array(
            'should_update' => true,
            'target_status' => 'expired',
            'update_reason' => sprintf('Auto-expired after %d days past expiration', $days_expired)
        );
    }

    /**
     * Get escalation configuration for license
     *
     * @since 1.5.0-rc.2 (Extracted from validator 4.2.4.3)
     * @param array $license License data
     * @return array Escalation configuration
     */
    public function get_escalation_configuration($license) {
        // Default escalation rules
        $default_config = array(
            'suspend_after_days' => 7,
            'revoke_after_days' => 30,
            'grace_period_hours' => 72,
            'notification_enabled' => true,
            'warning_before_action' => true
        );

        // Check for product-specific overrides
        if (!empty($license['product_id'])) {
            $product_config = $this->get_product_escalation_config($license['product_id']);
            if ($product_config) {
                return array_merge($default_config, $product_config);
            }
        }

        // Check for global overrides from WordPress options
        $global_overrides = get_option('vd_license_escalation_config', array());
        if (is_array($global_overrides) && !empty($global_overrides)) {
            return array_merge($default_config, $global_overrides);
        }

        return $default_config;
    }

    /**
     * Get product-specific escalation configuration
     *
     * @since 1.5.0-rc.2 (Extracted from validator 4.2.4.3)
     * @param int $product_id Product ID
     * @return array|null Product escalation config
     */
    public function get_product_escalation_config($product_id) {
        global $wpdb;

        if (!is_numeric($product_id) || $product_id <= 0) {
            return null;
        }

        // Try VD products table first
        $config = $wpdb->get_var($wpdb->prepare(
            "SELECT escalation_config FROM {$wpdb->prefix}vd_products WHERE id = %d",
            $product_id
        ));

        // Fallback to post meta if using WooCommerce products
        if (empty($config)) {
            $config = get_post_meta($product_id, '_vd_escalation_config', true);
        }

        if (!empty($config)) {
            $decoded = is_string($config) ? json_decode($config, true) : $config;
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    /**
     * Execute automatic status update with database safety
     *
     * @since 1.5.0-rc.2 (Extracted from validator 4.2.4.3)
     * @param array $license License data
     * @param string $new_status New status to set
     * @param array $options Update options
     * @return array Update execution result
     */
    public function execute_automatic_status_update($license, $new_status, $options) {
        global $wpdb;

        try {
            $update_data = array(
                'status' => $new_status,
                'last_status_change' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );

            $where = array('id' => $license['id']);
            $where_format = array('%d');

            // Optimistic locking - ensure license hasn't changed
            if ($options['optimistic_locking'] && !$options['force_update']) {
                $where['status'] = $license['status'];
                if (isset($license['updated_at'])) {
                    $where['updated_at'] = $license['updated_at'];
                    $where_format[] = '%s';
                    $where_format[] = '%s';
                } else {
                    $where_format[] = '%s';
                }
            }

            // Determine table name
            $table_name = $license['table_name'] ?? ($wpdb->prefix . 'vd_licenses');

            $result = $wpdb->update(
                $table_name,
                $update_data,
                $where,
                array('%s', '%s', '%s'),
                $where_format
            );

            if ($result === false) {
                throw new Exception('Database update failed: ' . $wpdb->last_error);
            }

            if ($result === 0) {
                return array(
                    'success' => false,
                    'error' => array(
                        'type' => 'no_rows_affected',
                        'message' => 'License may have been modified by another process (optimistic locking)'
                    )
                );
            }

            // Log status change for audit
            if ($options['audit_enabled']) {
                $this->log_automatic_status_change($license, $new_status, $options);
            }

            // Update related tables (history, statistics)
            $this->update_related_tables_for_status_change($license['id'], $new_status, $options);

            return array(
                'success' => true,
                'rows_affected' => $result,
                'update_timestamp' => current_time('mysql'),
                'table_name' => $table_name
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => array(
                    'type' => 'update_error',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                )
            );
        }
    }

    /**
     * Schedule automatic license updates
     *
     * @since 1.5.0-rc.2 (Extracted from validator)
     * @param array $schedule_options Scheduling options
     * @return array Schedule result
     */
    public function schedule_automatic_updates($schedule_options = array()) {
        $default_schedule = array(
            'frequency' => 'daily',
            'time' => '02:00',
            'enabled' => true,
            'batch_size' => 100,
            'grace_period_hours' => 72,
            'escalation_enabled' => true
        );

        $schedule_options = array_merge($default_schedule, $schedule_options);

        try {
            // Remove existing scheduled event
            wp_clear_scheduled_hook('vd_automatic_license_updates');

            if ($schedule_options['enabled']) {
                // Calculate next run time
                $next_run = $this->calculate_next_run_time($schedule_options);

                // Schedule new event
                wp_schedule_event($next_run, $schedule_options['frequency'], 'vd_automatic_license_updates', array($schedule_options));

                $this->statistics['schedule_runs']++;

                return array(
                    'success' => true,
                    'next_run' => date('Y-m-d H:i:s', $next_run),
                    'frequency' => $schedule_options['frequency'],
                    'options' => $schedule_options
                );
            } else {
                return array(
                    'success' => true,
                    'message' => 'Automatic updates disabled'
                );
            }

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Calculate next run time for scheduled updates
     *
     * @since 1.5.0-rc.2 (Extracted from validator)
     * @param array $schedule_options Scheduling options
     * @return int Unix timestamp of next run
     */
    public function calculate_next_run_time($schedule_options) {
        $time_parts = explode(':', $schedule_options['time']);
        $hour = (int)$time_parts[0];
        $minute = isset($time_parts[1]) ? (int)$time_parts[1] : 0;

        $next_run = mktime($hour, $minute, 0);

        // If time has passed today, schedule for tomorrow
        if ($next_run <= current_time('timestamp')) {
            $next_run += 24 * 3600; // Add 24 hours
        }

        return $next_run;
    }

    /**
     * Execute scheduled update (WordPress cron callback)
     *
     * @since 1.5.0-rc.2
     * @param array $schedule_options Scheduling options
     * @return void
     */
    public function execute_scheduled_update($schedule_options = array()) {
        // Add default options for scheduled runs
        $options = array_merge($schedule_options, array(
            'triggered_by' => 'scheduled_cron',
            'audit_enabled' => true,
            'notification_enabled' => true,
            'force_update' => false,
            'dry_run' => false
        ));

        try {
            $result = $this->update_expired_license_statuses($options);

            // Log scheduled execution result
            $this->log_scheduled_execution_result($result, $options);

        } catch (Exception $e) {
            error_log('VD License Manager: Scheduled update failed: ' . $e->getMessage());
        }
    }

    /**
     * Add custom cron schedules
     *
     * @since 1.5.0-rc.2
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['vd_hourly'] = array(
            'interval' => 3600,
            'display' => __('Every Hour (VD)', 'vd-license-manager')
        );

        $schedules['vd_twice_daily'] = array(
            'interval' => 43200,
            'display' => __('Twice Daily (VD)', 'vd-license-manager')
        );

        $schedules['vd_weekly'] = array(
            'interval' => 604800,
            'display' => __('Weekly (VD)', 'vd-license-manager')
        );

        return $schedules;
    }

    /**
     * Validate update configuration
     *
     * @since 1.5.0-rc.2
     * @param array $options Update options
     * @return array Validation result
     */
    private function validate_update_configuration($options) {
        $errors = array();

        // Validate batch size
        if (!is_numeric($options['batch_size']) || $options['batch_size'] < 1) {
            $errors[] = 'Invalid batch_size: must be a positive integer';
        }

        // Validate grace period
        if (!is_numeric($options['grace_period_hours']) || $options['grace_period_hours'] < 0) {
            $errors[] = 'Invalid grace_period_hours: must be a non-negative number';
        }

        // Validate status filters
        if (isset($options['status_filters']) && !is_array($options['status_filters'])) {
            $errors[] = 'Invalid status_filters: must be an array';
        }

        return array(
            'valid' => empty($errors),
            'error' => implode('; ', $errors),
            'errors' => $errors
        );
    }

    /**
     * Validate update results
     *
     * @since 1.5.0-rc.2
     * @param array $results Update results
     * @param array $options Update options
     * @return array Validation results
     */
    private function validate_update_results($results, $options) {
        $validation = array(
            'valid' => true,
            'warnings' => array(),
            'recommendations' => array()
        );

        // Check error rate
        $error_rate = $results['total_processed'] > 0 ?
            ($results['error_count'] / $results['total_processed']) * 100 : 0;

        if ($error_rate > 10) {
            $validation['warnings'][] = sprintf('High error rate: %.1f%%', $error_rate);
        }

        // Check execution time
        if ($results['execution_time_ms'] > ($options['max_execution_time'] * 1000)) {
            $validation['warnings'][] = 'Execution time exceeded recommended limit';
            $validation['recommendations'][] = 'Consider reducing batch size or increasing execution time limit';
        }

        return $validation;
    }

    /**
     * Update related tables when status changes
     *
     * @since 1.5.0-rc.2
     * @param int $license_id License ID
     * @param string $new_status New status
     * @param array $options Update options
     * @return void
     */
    private function update_related_tables_for_status_change($license_id, $new_status, $options) {
        global $wpdb;

        try {
            // Update license history
            if ($this->table_exists($wpdb->prefix . 'vd_license_history')) {
                $wpdb->insert(
                    $wpdb->prefix . 'vd_license_history',
                    array(
                        'license_id' => $license_id,
                        'status' => $new_status,
                        'change_type' => 'automatic_update',
                        'change_reason' => 'system_automatic_status_update',
                        'changed_at' => current_time('mysql'),
                        'changed_by' => 'system'
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%s')
                );
            }

            // Update product statistics for certain statuses
            if (in_array($new_status, array('expired', 'revoked', 'suspended'))) {
                $this->update_product_statistics_for_status_change($license_id, $new_status);
            }

        } catch (Exception $e) {
            // Log error but don't fail the main update
            error_log('VD License Manager: Failed to update related tables: ' . $e->getMessage());
        }
    }

    /**
     * Update product statistics for status change
     *
     * @since 1.5.0-rc.2
     * @param int $license_id License ID
     * @param string $new_status New status
     * @return void
     */
    private function update_product_statistics_for_status_change($license_id, $new_status) {
        global $wpdb;

        try {
            // Get product ID for this license
            $product_id = $wpdb->get_var($wpdb->prepare(
                "SELECT product_id FROM {$wpdb->prefix}vd_licenses WHERE id = %d",
                $license_id
            ));

            if (!$product_id) {
                return;
            }

            // Update product stats table if exists
            if ($this->table_exists($wpdb->prefix . 'vd_product_stats')) {
                $stat_key = $new_status . '_licenses_count';

                $wpdb->query($wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}vd_product_stats (product_id, stat_key, stat_value, updated_at)
                     VALUES (%d, %s, 1, %s)
                     ON DUPLICATE KEY UPDATE
                     stat_value = stat_value + 1,
                     updated_at = %s",
                    $product_id,
                    $stat_key,
                    current_time('mysql'),
                    current_time('mysql')
                ));
            }

        } catch (Exception $e) {
            // Log error but don't fail
            error_log('VD License Manager: Failed to update product statistics: ' . $e->getMessage());
        }
    }

    /**
     * Check if database table exists
     *
     * @since 1.5.0-rc.2
     * @param string $table_name Table name to check
     * @return bool Table exists
     */
    private function table_exists($table_name) {
        global $wpdb;

        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_name
        ));

        return $table_exists > 0;
    }

    /**
     * Log batch update completion
     *
     * @since 1.5.0-rc.2
     * @param int $batch_number Batch number
     * @param array $batch_result Batch result
     * @param array $options Update options
     * @return void
     */
    private function log_batch_update_completion($batch_number, $batch_result, $options) {
        $log_data = array(
            'module' => self::MODULE_NAME,
            'action' => 'batch_completion',
            'batch_number' => $batch_number,
            'updated_count' => $batch_result['updated_count'],
            'skipped_count' => $batch_result['skipped_count'],
            'error_count' => $batch_result['error_count'],
            'execution_time_ms' => $batch_result['execution_time_ms'],
            'timestamp' => current_time('mysql')
        );

        if (function_exists('vd_debug_log')) {
            vd_debug_log('Expiry Automation Batch: ' . wp_json_encode($log_data));
        }
    }

    /**
     * Log automatic status change
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param string $new_status New status
     * @param array $options Update options
     * @return void
     */
    private function log_automatic_status_change($license, $new_status, $options) {
        $log_data = array(
            'module' => self::MODULE_NAME,
            'action' => 'status_change',
            'license_id' => $license['id'],
            'license_key' => substr($license['license_key'] ?? 'unknown', 0, 8) . '...',
            'old_status' => $license['status'] ?? 'unknown',
            'new_status' => $new_status,
            'reason' => 'automatic_expiry_processing',
            'timestamp' => current_time('mysql')
        );

        if (function_exists('vd_debug_log')) {
            vd_debug_log('Expiry Automation Status Change: ' . wp_json_encode($log_data));
        }
    }

    /**
     * Log update error
     *
     * @since 1.5.0-rc.2
     * @param string $function Function name
     * @param Exception $e Exception
     * @param array $options Update options
     * @return void
     */
    private function log_update_error($function, $e, $options) {
        $log_data = array(
            'module' => self::MODULE_NAME,
            'action' => 'error',
            'function' => $function,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'timestamp' => current_time('mysql')
        );

        error_log('VD License Manager Error: ' . wp_json_encode($log_data));
    }

    /**
     * Audit automatic update completion
     *
     * @since 1.5.0-rc.2
     * @param array $results Update results
     * @param array $options Update options
     * @return void
     */
    private function audit_automatic_update_completion($results, $options) {
        global $wpdb;

        try {
            if ($this->table_exists($wpdb->prefix . 'vd_license_audit_log')) {
                $wpdb->insert(
                    $wpdb->prefix . 'vd_license_audit_log',
                    array(
                        'module' => self::MODULE_NAME,
                        'action' => 'automatic_update_completion',
                        'license_id' => null,
                        'old_status' => null,
                        'new_status' => null,
                        'details' => wp_json_encode(array(
                            'total_processed' => $results['total_processed'],
                            'updated_count' => $results['updated_count'],
                            'skipped_count' => $results['skipped_count'],
                            'error_count' => $results['error_count'],
                            'execution_time_ms' => $results['execution_time_ms'],
                            'options' => $options
                        )),
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
                );
            }

        } catch (Exception $e) {
            error_log('VD License Manager: Failed to log audit completion: ' . $e->getMessage());
        }
    }

    /**
     * Log scheduled execution result
     *
     * @since 1.5.0-rc.2
     * @param array $result Execution result
     * @param array $options Execution options
     * @return void
     */
    private function log_scheduled_execution_result($result, $options) {
        $log_data = array(
            'module' => self::MODULE_NAME,
            'action' => 'scheduled_execution',
            'result' => $result,
            'options' => $options,
            'timestamp' => current_time('mysql')
        );

        if (function_exists('vd_debug_log')) {
            vd_debug_log('Expiry Automation Scheduled: ' . wp_json_encode($log_data));
        }
    }

    /**
     * Update average batch time statistic
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    private function update_average_batch_time() {
        if ($this->statistics['batches_processed'] > 0) {
            $this->statistics['average_batch_time'] = round(
                $this->statistics['total_execution_time'] / $this->statistics['batches_processed'],
                2
            );
        }
    }

    /**
     * Get module statistics
     *
     * @since 1.5.0-rc.2
     * @return array Module statistics
     */
    public function get_statistics() {
        return array_merge($this->statistics, array(
            'last_reset' => get_option('vd_expiry_automation_stats_reset', 'never'),
            'default_config' => $this->default_config
        ));
    }

    /**
     * Reset module statistics
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    public function reset_statistics() {
        $this->statistics = array(
            'batches_processed' => 0,
            'licenses_updated' => 0,
            'licenses_skipped' => 0,
            'errors_encountered' => 0,
            'total_execution_time' => 0,
            'average_batch_time' => 0,
            'schedule_runs' => 0
        );
        update_option('vd_expiry_automation_stats_reset', current_time('mysql'));
    }
}
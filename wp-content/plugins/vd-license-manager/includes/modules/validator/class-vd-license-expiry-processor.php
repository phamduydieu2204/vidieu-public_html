<?php

namespace VD\LicenseManager\Validator;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Expiry Processor
 *
 * Extracted expiry processing functionality from monolithic VD_License_Validator
 * Handles license expiry validation, batch processing, and status updates
 *
 * Step 5.1.3: Validator Refactoring - Expiry Processing Manager Extraction
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */
class VD_License_Expiry_Processor {

    /**
     * Singleton instance
     *
     * @var VD_License_Expiry_Processor|null
     */
    private static $instance = null;

    /**
     * Valid license status enums for processing
     *
     * @var array
     */
    private $valid_status_enums = array(
        'active', 'inactive', 'pending', 'expired', 'suspended', 'revoked'
    );

    /**
     * Default batch processing options
     *
     * @var array
     */
    private $default_batch_options = array(
        'batch_size' => 100,
        'force_update' => false,
        'dry_run' => false,
        'status_filters' => array('active', 'pending'),
        'grace_period_hours' => 72,
        'escalation_enabled' => true,
        'audit_enabled' => true
    );

    /**
     * Get singleton instance
     *
     * @return VD_License_Expiry_Processor
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Prevent cloning
     */
    private function __clone() {
        // Prevent cloning
    }

    /**
     * Prevent unserialization
     */
    private function __wakeup() {
        // Prevent unserialization
    }

    /**
     * Validate license expiry date
     *
     * Extracted from VD_License_Validator::validate_license_expiry_date()
     * Step 5.1.3 - Expiry validation logic extraction
     *
     * @since 1.6.0
     * @param array $license License data array
     * @return array Validation result with expiry status
     */
    public function validate_license_expiry_date($license) {
        if (!isset($license['expires_at'])) {
            return array(
                'valid' => false,
                'error' => 'License expires_at field is missing',
                'code' => 'missing_expiry_date'
            );
        }

        $expires_at = strtotime($license['expires_at']);
        $current_time = current_time('timestamp');

        if ($expires_at === false) {
            return array(
                'valid' => false,
                'error' => 'Invalid expiry date format',
                'code' => 'invalid_expiry_format',
                'expires_at' => $license['expires_at']
            );
        }

        $is_expired = $current_time > $expires_at;
        $days_until_expiry = ceil(($expires_at - $current_time) / (24 * 3600));

        return array(
            'valid' => !$is_expired,
            'is_expired' => $is_expired,
            'expires_at' => $license['expires_at'],
            'expires_timestamp' => $expires_at,
            'current_timestamp' => $current_time,
            'days_until_expiry' => $days_until_expiry,
            'grace_period_eligible' => $is_expired && abs($days_until_expiry) <= 3,
            'expiry_status' => $is_expired ? 'expired' : 'active'
        );
    }

    /**
     * Update expired license statuses automatically
     *
     * Extracted from VD_License_Validator::update_expired_license_statuses()
     * Step 5.1.3 - Batch processing logic extraction
     *
     * @since 1.6.0
     * @param array $options Update options and filters
     * @return array Update results with detailed statistics
     */
    public function update_expired_license_statuses($options = array()) {
        $start_time = microtime(true);

        // Merge with default options
        $options = array_merge($this->default_batch_options, $options);

        $results = array(
            'total_processed' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'batch_results' => array(),
            'execution_time_ms' => 0,
            'dry_run' => $options['dry_run'],
            'errors' => array()
        );

        try {
            // Validate update configuration
            $validation_result = $this->validate_update_configuration($options);
            if (!$validation_result['valid']) {
                throw new Exception('Invalid update configuration: ' . $validation_result['error']);
            }

            // Get expired licenses in batches
            $expired_licenses = $this->get_expired_licenses_for_update($options);

            if (empty($expired_licenses)) {
                $results['message'] = 'No expired licenses found for update';
                return $results;
            }

            $results['total_processed'] = count($expired_licenses);

            // Process in batches for performance
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
            }

            // Final validation of update results
            $results['validation'] = $this->validate_update_results($results, $options);

        } catch (Exception $e) {
            $results['error_count']++;
            $results['errors'][] = array(
                'function' => 'update_expired_license_statuses',
                'message' => $e->getMessage(),
                'timestamp' => current_time('mysql')
            );

            $this->log_update_error('update_expired_license_statuses', $e, $options);
        }

        // Calculate execution time
        $results['execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);

        return $results;
    }

    /**
     * Get expired licenses for update processing
     *
     * Extracted from VD_License_Validator::get_expired_licenses_for_update()
     * Step 5.1.3 - Database query extraction
     *
     * @since 1.6.0
     * @param array $options Query options and filters
     * @return array Array of expired license records
     */
    public function get_expired_licenses_for_update($options) {
        global $wpdb;

        $status_filters = $options['status_filters'];
        $grace_period_hours = $options['grace_period_hours'];

        // Calculate cutoff time considering grace period
        $grace_cutoff = current_time('mysql', true);
        if ($grace_period_hours > 0) {
            $grace_cutoff = date('Y-m-d H:i:s', strtotime("-{$grace_period_hours} hours"));
        }

        // Prepare status filter placeholders
        $status_placeholders = implode(',', array_fill(0, count($status_filters), '%s'));

        // Query both VD licenses and LMFWC tables
        $vd_table = $wpdb->prefix . 'vd_licenses';
        $lmfwc_table = 'bz_lmfwc_licenses';

        $expired_licenses = array();

        // Check VD licenses table
        if ($this->table_exists($vd_table)) {
            $vd_query = $wpdb->prepare(
                "SELECT id, license_key, status, expires_at, 'vd_licenses' as table_name
                 FROM {$vd_table}
                 WHERE status IN ($status_placeholders)
                 AND expires_at IS NOT NULL
                 AND expires_at < %s
                 ORDER BY expires_at ASC",
                array_merge($status_filters, array($grace_cutoff))
            );

            $vd_results = $wpdb->get_results($vd_query, ARRAY_A);
            if ($vd_results) {
                $expired_licenses = array_merge($expired_licenses, $vd_results);
            }
        }

        // Check LMFWC table if exists
        if ($this->table_exists($lmfwc_table)) {
            $lmfwc_query = $wpdb->prepare(
                "SELECT id, license_key, status, expires_at, 'lmfwc_licenses' as table_name
                 FROM {$lmfwc_table}
                 WHERE status IN ($status_placeholders)
                 AND expires_at IS NOT NULL
                 AND expires_at < %s
                 ORDER BY expires_at ASC",
                array_merge($status_filters, array($grace_cutoff))
            );

            $lmfwc_results = $wpdb->get_results($lmfwc_query, ARRAY_A);
            if ($lmfwc_results) {
                $expired_licenses = array_merge($expired_licenses, $lmfwc_results);
            }
        }

        return $expired_licenses;
    }

    /**
     * Process batch of expired licenses
     *
     * Extracted from VD_License_Validator::process_expired_license_batch()
     * Step 5.1.3 - Batch processing logic extraction
     *
     * @since 1.6.0
     * @param array $licenses Batch of license records
     * @param array $options Processing options
     * @return array Batch processing results
     */
    public function process_expired_license_batch($licenses, $options) {
        $batch_results = array(
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'processed_licenses' => array(),
            'errors' => array()
        );

        foreach ($licenses as $license) {
            $single_result = $this->process_single_expired_license($license, $options);

            if ($single_result['success']) {
                $batch_results['updated_count']++;
            } elseif ($single_result['skipped']) {
                $batch_results['skipped_count']++;
            } else {
                $batch_results['error_count']++;
                $batch_results['errors'][] = $single_result['error'];
            }

            $batch_results['processed_licenses'][] = array(
                'license_id' => $license['id'],
                'license_key' => substr($license['license_key'], 0, 8) . '...',
                'table_name' => $license['table_name'],
                'result' => $single_result
            );
        }

        return $batch_results;
    }

    /**
     * Process single expired license
     *
     * Extracted from VD_License_Validator::process_single_expired_license()
     * Step 5.1.3 - Single license processing extraction
     *
     * @since 1.6.0
     * @param array $license License record
     * @param array $options Processing options
     * @return array Processing result for single license
     */
    public function process_single_expired_license($license, $options) {
        try {
            // Determine target status based on business rules
            $target_status_result = $this->determine_target_status_for_expired_license($license, $options);

            if (!$target_status_result['should_update']) {
                return array(
                    'success' => false,
                    'skipped' => true,
                    'reason' => $target_status_result['reason'],
                    'current_status' => $license['status']
                );
            }

            $target_status = $target_status_result['target_status'];

            // Skip if already at target status
            if ($license['status'] === $target_status) {
                return array(
                    'success' => false,
                    'skipped' => true,
                    'reason' => 'Already at target status',
                    'current_status' => $license['status'],
                    'target_status' => $target_status
                );
            }

            // Perform dry run check
            if ($options['dry_run']) {
                return array(
                    'success' => true,
                    'skipped' => false,
                    'dry_run' => true,
                    'current_status' => $license['status'],
                    'target_status' => $target_status,
                    'would_update' => true
                );
            }

            // Update license status
            $update_result = $this->update_expired_license_status($license, $target_status);

            if ($update_result['success']) {
                // Log automatic status update
                $this->log_automatic_status_update($license, $target_status);

                return array(
                    'success' => true,
                    'skipped' => false,
                    'previous_status' => $license['status'],
                    'new_status' => $target_status,
                    'update_timestamp' => current_time('mysql')
                );
            } else {
                return array(
                    'success' => false,
                    'skipped' => false,
                    'error' => $update_result['error'],
                    'current_status' => $license['status']
                );
            }

        } catch (Exception $e) {
            return array(
                'success' => false,
                'skipped' => false,
                'error' => array(
                    'message' => $e->getMessage(),
                    'license_id' => $license['id'],
                    'timestamp' => current_time('mysql')
                )
            );
        }
    }

    /**
     * Determine target status for expired license
     *
     * Extracted from VD_License_Validator::determine_target_status_for_expired_license()
     * Step 5.1.3 - Business logic extraction
     *
     * @since 1.6.0
     * @param array $license License record
     * @param array $options Processing options
     * @return array Target status determination result
     */
    public function determine_target_status_for_expired_license($license, $options) {
        $expires_at = strtotime($license['expires_at']);
        $current_time = current_time('timestamp');
        $days_expired = ceil(($current_time - $expires_at) / (24 * 3600));

        // Grace period check
        if ($days_expired <= ($options['grace_period_hours'] / 24)) {
            return array(
                'should_update' => false,
                'reason' => 'Within grace period',
                'days_expired' => $days_expired,
                'grace_period_days' => $options['grace_period_hours'] / 24
            );
        }

        // Escalation rules based on how long expired
        if ($days_expired <= 7) {
            $target_status = 'expired';
        } elseif ($days_expired <= 30) {
            $target_status = 'suspended';
        } else {
            $target_status = 'revoked';
        }

        // Check if escalation is enabled
        if (!$options['escalation_enabled'] && $target_status !== 'expired') {
            $target_status = 'expired';
        }

        return array(
            'should_update' => true,
            'target_status' => $target_status,
            'days_expired' => $days_expired,
            'escalation_applied' => $options['escalation_enabled']
        );
    }

    /**
     * Update expired license status in database
     *
     * Extracted from VD_License_Validator::update_expired_license_status()
     * Step 5.1.3 - Database update extraction
     *
     * @since 1.6.0
     * @param array $license License record
     * @param string $new_status New status to set
     * @return array Update result
     */
    public function update_expired_license_status($license, $new_status) {
        global $wpdb;

        if (!isset($license['id']) || !isset($license['table_name'])) {
            return array(
                'success' => false,
                'error' => 'Missing license ID or table name'
            );
        }

        $table_name = $license['table_name'] === 'vd_licenses'
            ? $wpdb->prefix . 'vd_licenses'
            : 'bz_lmfwc_licenses';

        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $new_status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $license['id']),
            array('%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return array(
                'success' => false,
                'error' => 'Database update failed: ' . $wpdb->last_error
            );
        }

        return array(
            'success' => true,
            'rows_affected' => $result,
            'new_status' => $new_status
        );
    }

    /**
     * Validate update configuration
     *
     * Extracted from VD_License_Validator::validate_update_configuration()
     * Step 5.1.3 - Configuration validation extraction
     *
     * @since 1.6.0
     * @param array $options Update options
     * @return array Validation result
     */
    public function validate_update_configuration($options) {
        $errors = array();

        // Validate batch size
        if (!is_int($options['batch_size']) || $options['batch_size'] < 1 || $options['batch_size'] > 1000) {
            $errors[] = 'batch_size must be integer between 1 and 1000';
        }

        // Validate grace period
        if (!is_int($options['grace_period_hours']) || $options['grace_period_hours'] < 0) {
            $errors[] = 'grace_period_hours must be non-negative integer';
        }

        // Validate status filters
        if (!is_array($options['status_filters']) || empty($options['status_filters'])) {
            $errors[] = 'status_filters must be non-empty array';
        } else {
            foreach ($options['status_filters'] as $status) {
                if (!in_array($status, $this->valid_status_enums)) {
                    $errors[] = "Invalid status filter: {$status}";
                }
            }
        }

        if (!empty($errors)) {
            return array(
                'valid' => false,
                'error' => implode('; ', $errors)
            );
        }

        return array('valid' => true);
    }

    /**
     * Validate update results
     *
     * Extracted from VD_License_Validator::validate_update_results()
     * Step 5.1.3 - Results validation extraction
     *
     * @since 1.6.0
     * @param array $results Update results
     * @param array $options Update options
     * @return array Validation result
     */
    public function validate_update_results($results, $options) {
        $validation = array(
            'valid' => true,
            'warnings' => array(),
            'statistics' => array()
        );

        // Calculate success rate
        $total_processed = $results['total_processed'];
        if ($total_processed > 0) {
            $success_rate = ($results['updated_count'] / $total_processed) * 100;
            $validation['statistics']['success_rate'] = round($success_rate, 2);

            // Warning for low success rate
            if ($success_rate < 80) {
                $validation['warnings'][] = "Low success rate: {$success_rate}%";
            }
        }

        // Warning for high error rate
        if ($results['error_count'] > ($total_processed * 0.1)) {
            $validation['warnings'][] = "High error rate: {$results['error_count']} errors";
        }

        $validation['statistics']['total_batches'] = count($results['batch_results']);
        $validation['statistics']['avg_batch_size'] = $total_processed > 0
            ? round($total_processed / count($results['batch_results']), 2)
            : 0;

        return $validation;
    }

    /**
     * Log automatic status update
     *
     * Extracted from VD_License_Validator::log_automatic_status_update()
     * Step 5.1.3 - Logging utility extraction
     *
     * @since 1.6.0
     * @param array $license License record
     * @param string $new_status New status
     * @return void
     */
    public function log_automatic_status_update($license, $new_status) {
        if (!function_exists('vd_debug_log')) {
            return;
        }

        vd_debug_log(sprintf(
            '[VD Expiry Processor] Automatic status update: License %s (ID: %s) changed from %s to %s',
            substr($license['license_key'], 0, 8) . '...',
            $license['id'],
            $license['status'],
            $new_status
        ));
    }

    /**
     * Log batch update completion
     *
     * Extracted from VD_License_Validator::log_batch_update_completion()
     * Step 5.1.3 - Batch logging extraction
     *
     * @since 1.6.0
     * @param int $batch_number Batch sequence number
     * @param array $batch_result Batch processing result
     * @param array $options Processing options
     * @return void
     */
    public function log_batch_update_completion($batch_number, $batch_result, $options) {
        if (!function_exists('vd_debug_log')) {
            return;
        }

        vd_debug_log(sprintf(
            '[VD Expiry Processor] Batch %d completed: %d updated, %d skipped, %d errors',
            $batch_number,
            $batch_result['updated_count'],
            $batch_result['skipped_count'],
            $batch_result['error_count']
        ));
    }

    /**
     * Log update error
     *
     * Extracted from VD_License_Validator::log_update_error()
     * Step 5.1.3 - Error logging extraction
     *
     * @since 1.6.0
     * @param string $function Function name where error occurred
     * @param Exception $exception Exception object
     * @param array $options Processing options
     * @return void
     */
    public function log_update_error($function, $exception, $options) {
        if (!function_exists('vd_debug_log')) {
            return;
        }

        vd_debug_log(sprintf(
            '[VD Expiry Processor] Error in %s: %s',
            $function,
            $exception->getMessage()
        ));
    }

    /**
     * Check if database table exists
     *
     * Utility method for database table existence check
     * Step 5.1.3 - Database utility
     *
     * @since 1.6.0
     * @param string $table_name Table name to check
     * @return bool True if table exists, false otherwise
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
     * Get valid status enums
     *
     * Step 5.1.3 - Status validation utility
     *
     * @since 1.6.0
     * @return array Valid license status values
     */
    public function get_valid_status_enums() {
        return $this->valid_status_enums;
    }
}
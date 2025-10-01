<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Rule Expiry Core Module
 *
 * Step 2.2.1 - Basic expiry validation and detection
 * PSR-4 Namespace: VD\LicenseManager\Rules
 *
 * Handles core expiry date validation, warning thresholds, and basic status updates
 * Part of the modular refactor initiative to break down complex business logic
 *
 * @package VD_License_Manager
 * @subpackage Rules
 * @since 1.5.0-rc.2
 * @version Step 2.2.1
 */
class VD_License_Rule_Expiry_Core {

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
    const MODULE_NAME = 'Expiry Core';

    /**
     * Status business logic module instance
     *
     * @since 1.5.0-rc.2
     * @var VD_License_Status_Business|null
     */
    private $status_business = null;

    /**
     * Default expiry warning threshold (days)
     *
     * @since 1.5.0-rc.2
     * @var int
     */
    private $default_warning_threshold = 7;

    /**
     * Module statistics
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $statistics = array(
        'validations_performed' => 0,
        'expired_detected' => 0,
        'warnings_triggered' => 0,
        'lifetime_licenses' => 0,
        'status_updates' => 0
    );

    /**
     * Constructor
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Status_Business $status_business Status business logic module
     */
    public function __construct($status_business = null) {
        $this->status_business = $status_business;
        $this->init_default_settings();
    }

    /**
     * Initialize default settings
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    private function init_default_settings() {
        // Allow configuration override via WordPress options
        $this->default_warning_threshold = get_option('vd_license_expiry_warning_days', 7);
    }

    /**
     * Set status business logic dependency
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
            'description' => 'Core expiry validation and detection module',
            'namespace' => 'VD\\LicenseManager\\Rules',
            'dependencies' => array('VD_License_Status_Business'),
            'functions' => array(
                'validate_license_expiry_date',
                'update_expired_license_status',
                'get_expiry_warning_threshold',
                'calculate_days_until_expiry',
                'is_lifetime_license',
                'get_expiry_analysis'
            ),
            'statistics' => $this->statistics
        );
    }

    /**
     * Validate license expiry date
     * Extracted from main validator Step 4.2.3 - Core expiry validation logic
     *
     * @since 1.5.0-rc.2 (Extracted from 4.2.3)
     * @param array $license License data
     * @param array $options Validation options
     * @return array Validation result
     */
    public function validate_license_expiry_date($license, $options = array()) {
        $this->statistics['validations_performed']++;

        $expires_at = $license['expires_at'] ?? null;

        // Handle null expiry (lifetime license)
        if (!$expires_at || $expires_at === '0000-00-00 00:00:00') {
            $this->statistics['lifetime_licenses']++;
            return array(
                'valid' => true,
                'days_until_expiry' => null,
                'expiry_warning' => false,
                'is_lifetime' => true,
                'warning_threshold' => $this->get_expiry_warning_threshold($license, $options),
                'validation_timestamp' => current_time('mysql')
            );
        }

        $expiry_timestamp = strtotime($expires_at);
        $current_timestamp = current_time('timestamp');

        // Check if expired
        if ($expiry_timestamp < $current_timestamp) {
            $this->statistics['expired_detected']++;
            $expired_since_days = ceil(($current_timestamp - $expiry_timestamp) / (24 * 3600));

            return array(
                'valid' => false,
                'error' => 'License đã hết hạn vào ' . date('d/m/Y H:i', $expiry_timestamp),
                'code' => 'license_expired',
                'expires_at' => $expires_at,
                'expired_since_days' => $expired_since_days,
                'expiry_timestamp' => $expiry_timestamp,
                'validation_timestamp' => current_time('mysql'),
                'requires_status_update' => true
            );
        }

        // Calculate days until expiry
        $days_until_expiry = $this->calculate_days_until_expiry($expiry_timestamp, $current_timestamp);

        // Check for expiry warning
        $warning_threshold = $this->get_expiry_warning_threshold($license, $options);
        $expiry_warning = $days_until_expiry <= $warning_threshold;

        if ($expiry_warning) {
            $this->statistics['warnings_triggered']++;
        }

        return array(
            'valid' => true,
            'days_until_expiry' => $days_until_expiry,
            'expiry_warning' => $expiry_warning,
            'warning_threshold' => $warning_threshold,
            'expires_at' => $expires_at,
            'expiry_timestamp' => $expiry_timestamp,
            'is_lifetime' => false,
            'validation_timestamp' => current_time('mysql')
        );
    }

    /**
     * Update expired license status in database
     * Extracted from main validator Step 4.2.3 - Basic status update logic
     *
     * @since 1.5.0-rc.2 (Extracted from 4.2.3)
     * @param array $license License data
     * @param array $options Update options
     * @return array Update result
     */
    public function update_expired_license_status($license, $options = array()) {
        global $wpdb;

        // Validate license data structure
        if (!isset($license['id']) || !isset($license['table_name'])) {
            return array(
                'success' => false,
                'error' => 'Invalid license data structure',
                'code' => 'invalid_license_data'
            );
        }

        // Check if table exists (safety check)
        if (!$this->table_exists($license['table_name'])) {
            return array(
                'success' => false,
                'error' => 'License table does not exist: ' . $license['table_name'],
                'code' => 'table_not_found'
            );
        }

        // Determine target status based on current status and business rules
        $current_status = $license['status'] ?? 'active';
        $target_status = $options['target_status'] ?? 'expired';

        // Validate status transition using business logic module
        if ($this->status_business) {
            $transition_validation = $this->status_business->validate_status_transition(
                $current_status,
                $target_status,
                $license
            );

            if (!$transition_validation['valid']) {
                return array(
                    'success' => false,
                    'error' => 'Status transition not allowed: ' . $transition_validation['error'],
                    'code' => 'transition_not_allowed',
                    'transition_details' => $transition_validation
                );
            }
        }

        // Perform the status update
        $updated = $wpdb->update(
            $license['table_name'],
            array('status' => $target_status),
            array('id' => $license['id']),
            array('%s'),
            array('%d')
        );

        if ($updated !== false) {
            $this->statistics['status_updates']++;

            // Log the automatic status update
            $this->log_automatic_status_update($license, $target_status, $options);

            return array(
                'success' => true,
                'updated_rows' => $updated,
                'old_status' => $current_status,
                'new_status' => $target_status,
                'license_id' => $license['id'],
                'table_name' => $license['table_name'],
                'update_timestamp' => current_time('mysql')
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Database update failed',
                'code' => 'db_update_failed',
                'wpdb_error' => $wpdb->last_error
            );
        }
    }

    /**
     * Get expiry warning threshold for a license
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $options Configuration options
     * @return int Warning threshold in days
     */
    public function get_expiry_warning_threshold($license, $options = array()) {
        // Check for license-specific threshold
        if (isset($license['expiry_warning_days']) && is_numeric($license['expiry_warning_days'])) {
            return (int) $license['expiry_warning_days'];
        }

        // Check for product-specific threshold
        if (isset($license['product_id'])) {
            $product_threshold = get_option('vd_license_expiry_warning_product_' . $license['product_id'], null);
            if ($product_threshold !== null && is_numeric($product_threshold)) {
                return (int) $product_threshold;
            }
        }

        // Check for options override
        if (isset($options['warning_threshold']) && is_numeric($options['warning_threshold'])) {
            return (int) $options['warning_threshold'];
        }

        // Return default threshold
        return $this->default_warning_threshold;
    }

    /**
     * Calculate days until expiry
     *
     * @since 1.5.0-rc.2
     * @param int $expiry_timestamp Expiry timestamp
     * @param int $current_timestamp Current timestamp (optional)
     * @return int Days until expiry
     */
    public function calculate_days_until_expiry($expiry_timestamp, $current_timestamp = null) {
        if ($current_timestamp === null) {
            $current_timestamp = current_time('timestamp');
        }

        return max(0, ceil(($expiry_timestamp - $current_timestamp) / (24 * 3600)));
    }

    /**
     * Check if license is lifetime license
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return bool True if lifetime license
     */
    public function is_lifetime_license($license) {
        $expires_at = $license['expires_at'] ?? null;
        return !$expires_at || $expires_at === '0000-00-00 00:00:00';
    }

    /**
     * Get comprehensive expiry analysis for a license
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $options Analysis options
     * @return array Comprehensive expiry analysis
     */
    public function get_expiry_analysis($license, $options = array()) {
        $expiry_validation = $this->validate_license_expiry_date($license, $options);

        $analysis = array(
            'license_id' => $license['id'] ?? 'unknown',
            'license_key' => $license['license_key'] ?? 'unknown',
            'expiry_validation' => $expiry_validation,
            'is_lifetime' => $this->is_lifetime_license($license),
            'warning_threshold' => $this->get_expiry_warning_threshold($license, $options),
            'analysis_timestamp' => current_time('mysql')
        );

        if (!$expiry_validation['valid']) {
            $analysis['requires_action'] = true;
            $analysis['recommended_action'] = 'update_status_to_expired';
        } elseif ($expiry_validation['expiry_warning']) {
            $analysis['requires_action'] = true;
            $analysis['recommended_action'] = 'send_expiry_warning';
        } else {
            $analysis['requires_action'] = false;
            $analysis['recommended_action'] = 'none';
        }

        return $analysis;
    }

    /**
     * Get module statistics
     *
     * @since 1.5.0-rc.2
     * @return array Module statistics
     */
    public function get_statistics() {
        return array_merge($this->statistics, array(
            'warning_threshold_default' => $this->default_warning_threshold,
            'last_reset' => get_option('vd_expiry_core_stats_reset', 'never')
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
            'validations_performed' => 0,
            'expired_detected' => 0,
            'warnings_triggered' => 0,
            'lifetime_licenses' => 0,
            'status_updates' => 0
        );
        update_option('vd_expiry_core_stats_reset', current_time('mysql'));
    }

    /**
     * Check if database table exists
     * Utility method for safety checks
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
     * Log automatic status update
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param string $new_status New status
     * @param array $options Update options
     * @return void
     */
    private function log_automatic_status_update($license, $new_status, $options = array()) {
        $log_data = array(
            'module' => self::MODULE_NAME,
            'action' => 'automatic_status_update',
            'license_id' => $license['id'] ?? 'unknown',
            'license_key' => substr($license['license_key'] ?? 'unknown', 0, 8) . '...',
            'old_status' => $license['status'] ?? 'unknown',
            'new_status' => $new_status,
            'reason' => 'expiry_core_update',
            'timestamp' => current_time('mysql'),
            'options' => $options
        );

        // Use WordPress logging if available
        if (function_exists('vd_debug_log')) {
            vd_debug_log('Expiry Core Status Update: ' . wp_json_encode($log_data));
        }

        // Store in database log if audit table exists
        $this->store_audit_log($log_data);
    }

    /**
     * Store audit log entry
     *
     * @since 1.5.0-rc.2
     * @param array $log_data Log data
     * @return void
     */
    private function store_audit_log($log_data) {
        global $wpdb;

        $audit_table = $wpdb->prefix . 'vd_license_audit_log';

        if ($this->table_exists($audit_table)) {
            $wpdb->insert(
                $audit_table,
                array(
                    'module' => $log_data['module'],
                    'action' => $log_data['action'],
                    'license_id' => $log_data['license_id'],
                    'old_status' => $log_data['old_status'],
                    'new_status' => $log_data['new_status'],
                    'details' => wp_json_encode($log_data),
                    'created_at' => $log_data['timestamp']
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
}
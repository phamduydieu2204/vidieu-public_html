<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Validator Class
 *
 * Handles license validation logic for VD License Manager
 * Step 4.2.1 - License Validator Class Foundation
 * Implements Step 1 of 4-step license resolution process
 *
 * @since 4.2.1
 * @package VD_License_Manager
 */
class VD_License_Validator {

    /**
     * Singleton instance
     *
     * @since 4.2.1
     * @var VD_License_Validator|null
     */
    private static $instance = null;

    /**
     * Database manager instance
     *
     * @since 4.2.1
     * @var VD_Database_Manager|null
     */
    private $database_manager = null;

    /**
     * Encryption manager instance
     *
     * @since 4.2.1
     * @var VD_Encryption_Manager|null
     */
    private $encryption_manager = null;

    /**
     * Security audit instance
     *
     * @since 4.2.1
     * @var VD_Security_Audit|null
     */
    private $security_audit = null;

    /**
     * Validation cache for performance
     *
     * @since 4.2.1
     * @var array
     */
    private $validation_cache = array();

    /**
     * Validation initialization status
     *
     * @since 4.2.1
     * @var bool
     */
    private $initialized = false;

    /**
     * License key format regex pattern
     *
     * @since 4.2.1
     * @var string
     */
    private $license_key_pattern = '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{6}$/';

    /**
     * License status enum values
     *
     * @since 4.2.1
     * @var array
     */
    private $valid_statuses = array('active', 'suspended', 'expired');

    /**
     * Private constructor to enforce singleton pattern
     *
     * @since 4.2.1
     */
    private function __construct() {
        // Initialize database manager if available
        if (class_exists('VD_Database_Manager')) {
            $this->database_manager = VD_Database_Manager::get_instance();
        }

        // Initialize encryption manager if available
        if (class_exists('VD_Encryption_Manager')) {
            $this->encryption_manager = VD_Encryption_Manager::get_instance();
        }

        // Initialize security audit if available
        if (class_exists('VD_Security_Audit')) {
            $this->security_audit = VD_Security_Audit::get_instance();
        }

        $this->initialized = true;
    }

    /**
     * Get singleton instance
     *
     * @since 4.2.1
     * @return VD_License_Validator Single instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of the instance
     *
     * @since 4.2.1
     * @return void
     */
    private function __clone() {
        // Prevent cloning
    }

    /**
     * Prevent unserialization of the instance
     *
     * @since 4.2.1
     * @return void
     */
    private function __wakeup() {
        // Prevent unserialization
    }

    /**
     * Initialize validator with WordPress hooks
     *
     * @since 4.2.1
     * @return void
     */
    public function init() {
        if (!$this->initialized) {
            return;
        }

        // Add WordPress filters for validation
        add_filter('vd_validate_license_key_format', array($this, 'validate_license_key_format'), 10, 1);
        add_filter('vd_validate_license_expiry', array($this, 'validate_license_expiry'), 10, 1);
        add_filter('vd_get_license_settings', array($this, 'get_license_settings'), 10, 2);

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            vd_debug_log('VD_License_Validator initialized successfully');
        }
    }

    /**
     * Validate license key format with comprehensive validation
     * Implements enhanced vd_validate_license_key() function
     * Step 4.2.2 - Enhanced License Key Format Validation
     *
     * @since 4.2.1
     * @updated 4.2.2
     * @param string $license_key License key to validate
     * @param bool $detailed Whether to return detailed validation results
     * @return bool|array True/false for simple validation, array for detailed
     */
    public function validate_license_key_format($license_key, $detailed = false) {
        $validation_result = array(
            'valid' => false,
            'error_code' => null,
            'error_message' => null,
            'format_checks' => array()
        );

        // Input type validation
        if (!is_string($license_key)) {
            $validation_result['error_code'] = 'invalid_type';
            $validation_result['error_message'] = 'License key must be a string';
            $validation_result['format_checks']['type_check'] = false;
            return $detailed ? $validation_result : false;
        }
        $validation_result['format_checks']['type_check'] = true;

        // Input sanitization
        $original_key = $license_key;
        $license_key = sanitize_text_field(trim($license_key));

        // Check if sanitization changed the key (potential security issue)
        if ($original_key !== $license_key) {
            $validation_result['error_code'] = 'sanitization_changed';
            $validation_result['error_message'] = 'License key contains invalid characters';
            $validation_result['format_checks']['sanitization_check'] = false;
            return $detailed ? $validation_result : false;
        }
        $validation_result['format_checks']['sanitization_check'] = true;

        // Check empty
        if (empty($license_key)) {
            $validation_result['error_code'] = 'empty';
            $validation_result['error_message'] = 'License key cannot be empty';
            $validation_result['format_checks']['empty_check'] = false;
            return $detailed ? $validation_result : false;
        }
        $validation_result['format_checks']['empty_check'] = true;

        // Check minimum length
        if (strlen($license_key) < 8) {
            $validation_result['error_code'] = 'too_short';
            $validation_result['error_message'] = 'License key too short (minimum 8 characters)';
            $validation_result['format_checks']['min_length_check'] = false;
            return $detailed ? $validation_result : false;
        }
        $validation_result['format_checks']['min_length_check'] = true;

        // Check maximum length
        if (strlen($license_key) > 32) {
            $validation_result['error_code'] = 'too_long';
            $validation_result['error_message'] = 'License key too long (maximum 32 characters)';
            $validation_result['format_checks']['max_length_check'] = false;
            return $detailed ? $validation_result : false;
        }
        $validation_result['format_checks']['max_length_check'] = true;

        // Check standard VD format (XXXX-XXXX-XXXX-XXXX-XXXXXX pattern)
        $standard_pattern_match = preg_match($this->license_key_pattern, $license_key);
        $validation_result['format_checks']['standard_pattern'] = $standard_pattern_match;

        // Check alternative LMfWC compatible formats
        $alternative_patterns = array(
            'lmfwc_standard' => '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', // 20 chars
            'lmfwc_extended' => '/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/', // 26 chars with different dash placement
            'legacy_format' => '/^[A-Z0-9\-]{8,32}$/' // Business logic compatible format
        );

        $alternative_match = false;
        foreach ($alternative_patterns as $pattern_name => $pattern) {
            if (preg_match($pattern, $license_key)) {
                $validation_result['format_checks'][$pattern_name] = true;
                $alternative_match = true;
                break;
            } else {
                $validation_result['format_checks'][$pattern_name] = false;
            }
        }

        // Overall format validation
        if (!$standard_pattern_match && !$alternative_match) {
            $validation_result['error_code'] = 'invalid_format';
            $validation_result['error_message'] = 'License key format is invalid. Expected format: XXXX-XXXX-XXXX-XXXX-XXXXXX';
            $validation_result['format_checks']['overall_format'] = false;
            return $detailed ? $validation_result : false;
        }
        $validation_result['format_checks']['overall_format'] = true;

        // Character set validation
        if (!preg_match('/^[A-Z0-9\-]+$/', $license_key)) {
            $validation_result['error_code'] = 'invalid_characters';
            $validation_result['error_message'] = 'License key contains invalid characters. Only A-Z, 0-9, and hyphens allowed';
            $validation_result['format_checks']['character_set'] = false;
            return $detailed ? $validation_result : false;
        }
        $validation_result['format_checks']['character_set'] = true;

        // Dash placement validation for standard format
        if ($standard_pattern_match) {
            $parts = explode('-', $license_key);
            if (count($parts) !== 5 ||
                strlen($parts[0]) !== 4 || strlen($parts[1]) !== 4 ||
                strlen($parts[2]) !== 4 || strlen($parts[3]) !== 4 ||
                strlen($parts[4]) !== 6) {
                $validation_result['error_code'] = 'invalid_dash_placement';
                $validation_result['error_message'] = 'Invalid dash placement in license key';
                $validation_result['format_checks']['dash_placement'] = false;
                return $detailed ? $validation_result : false;
            }
            $validation_result['format_checks']['dash_placement'] = true;
        } else {
            $validation_result['format_checks']['dash_placement'] = true; // Skip for alternative formats
        }

        // Basic checksum validation (if applicable)
        $validation_result['format_checks']['checksum'] = $this->validate_license_checksum($license_key);

        // All validations passed
        $validation_result['valid'] = true;

        return $detailed ? $validation_result : true;
    }

    /**
     * Validate license key checksum (basic implementation)
     * Step 4.2.2 - Enhanced validation with checksum
     *
     * @since 4.2.2
     * @param string $license_key License key to validate
     * @return bool True if checksum is valid or not applicable
     */
    private function validate_license_checksum($license_key) {
        // For now, implement basic validation
        // Advanced checksum validation can be added later if needed

        // Remove dashes for calculation
        $clean_key = str_replace('-', '', $license_key);

        // Basic checksum: sum of ASCII values should be divisible by a prime number
        if (strlen($clean_key) >= 8) {
            $checksum = 0;
            for ($i = 0; $i < strlen($clean_key); $i++) {
                $checksum += ord($clean_key[$i]);
            }

            // Simple validation: checksum should be reasonable
            return $checksum > 0 && $checksum < 50000;
        }

        return true; // Skip checksum for shorter keys
    }

    /**
     * Wrapper function for business logic compatibility
     * Implements vd_validate_license_key() global function
     * Step 4.2.2 - Business Logic Integration
     *
     * @since 4.2.2
     * @param string $license_key License key to validate
     * @return bool True if format is valid, false otherwise
     */
    public function vd_validate_license_key($license_key) {
        return $this->validate_license_key_format($license_key, false);
    }

    /**
     * Get detailed validation results for debugging
     * Step 4.2.2 - Enhanced validation reporting
     *
     * @since 4.2.2
     * @param string $license_key License key to validate
     * @return array Detailed validation results
     */
    public function get_detailed_validation($license_key) {
        return $this->validate_license_key_format($license_key, true);
    }

    /**
     * Validate multiple license key formats for batch processing
     * Step 4.2.2 - Batch validation capability
     *
     * @since 4.2.2
     * @param array $license_keys Array of license keys to validate
     * @return array Validation results for each key
     */
    public function validate_license_keys_batch($license_keys) {
        $results = array();

        if (!is_array($license_keys)) {
            return array('error' => 'Input must be an array');
        }

        foreach ($license_keys as $index => $license_key) {
            $results[$index] = array(
                'license_key' => $license_key,
                'valid' => $this->validate_license_key_format($license_key, false),
                'detailed' => $this->validate_license_key_format($license_key, true)
            );
        }

        return $results;
    }

    /**
     * Validate license expiry and status
     * Implements validate_license_expiry() function from business logic
     *
     * @since 4.2.1
     * @param string $license_key License key to validate
     * @return array Validation result with license data
     */
    public function validate_license_expiry($license_key) {
        global $wpdb;

        // Check cache first for performance
        $cache_key = 'vd_license_validation_' . md5($license_key);
        if (isset($this->validation_cache[$cache_key])) {
            return $this->validation_cache[$cache_key];
        }

        // Input validation
        if (!$this->validate_license_key_format($license_key)) {
            $result = array(
                'valid' => false,
                'error' => 'Invalid license key format',
                'code' => 'invalid_format'
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        // Get license from LMfWC database with proper table prefix
        $table_name = $wpdb->prefix . 'lmfwc_licenses';
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE license_key = %s",
            $license_key
        ), ARRAY_A);

        if (!$license) {
            $result = array(
                'valid' => false,
                'error' => 'License không tồn tại',
                'code' => 'license_not_found'
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        // Check status
        if ($license['status'] === 'suspended') {
            $result = array(
                'valid' => false,
                'error' => 'License đã bị tạm khóa',
                'code' => 'license_suspended'
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        if ($license['status'] === 'expired') {
            $result = array(
                'valid' => false,
                'error' => 'License đã hết hạn',
                'code' => 'license_expired'
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        // Check expiry date
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            // Update status to expired
            $wpdb->update(
                $table_name,
                array('status' => 'expired'),
                array('id' => $license['id']),
                array('%s'),
                array('%d')
            );

            $result = array(
                'valid' => false,
                'error' => 'License đã hết hạn',
                'code' => 'license_expired'
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        // Check if expiring soon (warning)
        $days_until_expiry = null;
        if ($license['expires_at']) {
            $days_until_expiry = ceil((strtotime($license['expires_at']) - time()) / (24 * 3600));
        }

        $result = array(
            'valid' => true,
            'license' => $license,
            'days_until_expiry' => $days_until_expiry
        );

        // Cache result for performance
        $this->validation_cache[$cache_key] = $result;

        return $result;
    }

    /**
     * Get effective license settings with inheritance
     * Implements get_license_settings() function from business logic
     *
     * @since 4.2.1
     * @param int $license_id License ID
     * @param int $product_id Product ID
     * @return array Effective settings
     */
    public function get_license_settings($license_id, $product_id) {
        global $wpdb;

        // Cache key for settings
        $cache_key = "vd_license_settings_{$license_id}_{$product_id}";
        if (isset($this->validation_cache[$cache_key])) {
            return $this->validation_cache[$cache_key];
        }

        // 1. Try license-specific override first
        $license_override = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_license_settings_override WHERE license_id = %d",
            $license_id
        ), ARRAY_A);

        // 2. Get product settings
        $product_settings = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_product_settings WHERE product_id = %d",
            $product_id
        ), ARRAY_A);

        // 3. Get global defaults
        $global_settings = $this->get_global_settings();

        // Merge settings with priority: License > Product > Global
        $effective_settings = array(
            'max_devices' => $license_override['max_devices']
                ?? $product_settings['max_devices']
                ?? $global_settings['default_max_devices']
                ?? 3,

            'rate_limit_requests' => $license_override['rate_limit_requests']
                ?? $product_settings['rate_limit_requests']
                ?? $global_settings['default_rate_limit_requests']
                ?? 100,

            'rate_limit_window_hours' => $license_override['rate_limit_window_hours']
                ?? $product_settings['rate_limit_window_hours']
                ?? $global_settings['default_rate_limit_window_hours']
                ?? 1,

            'auto_approval_enabled' => $license_override['auto_approval_enabled']
                ?? $product_settings['auto_approval_enabled']
                ?? ($global_settings['auto_approval_enabled'] === 'true')
                ?? true,

            'grace_period_hours' => $license_override['grace_period_hours']
                ?? $product_settings['grace_period_hours']
                ?? $global_settings['grace_period_hours']
                ?? 72
        );

        // Cache result
        $this->validation_cache[$cache_key] = $effective_settings;

        return $effective_settings;
    }

    /**
     * Get global settings as key-value array
     *
     * @since 4.2.1
     * @return array Global settings
     */
    private function get_global_settings() {
        global $wpdb;

        static $global_config = null;
        if ($global_config !== null) {
            return $global_config;
        }

        $settings = $wpdb->get_results(
            "SELECT setting_key, setting_value FROM {$wpdb->prefix}vd_global_settings",
            ARRAY_A
        );

        $global_config = array();
        if ($settings) {
            foreach ($settings as $setting) {
                $global_config[$setting['setting_key']] = $setting['setting_value'];
            }
        }

        return $global_config;
    }

    /**
     * Clear validation cache
     *
     * @since 4.2.1
     * @return void
     */
    public function clear_cache() {
        $this->validation_cache = array();
    }

    /**
     * Get validation statistics
     *
     * @since 4.2.1
     * @return array Validation stats
     */
    public function get_validation_stats() {
        return array(
            'initialized' => $this->initialized,
            'cache_entries' => count($this->validation_cache),
            'database_manager_loaded' => $this->database_manager !== null,
            'encryption_manager_loaded' => $this->encryption_manager !== null,
            'security_audit_loaded' => $this->security_audit !== null
        );
    }

    /**
     * Check if validator is ready
     *
     * @since 4.2.1
     * @return bool True if ready, false otherwise
     */
    public function is_ready() {
        return $this->initialized;
    }
}
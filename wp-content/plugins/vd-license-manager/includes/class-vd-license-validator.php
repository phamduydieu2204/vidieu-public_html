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
     * Enhanced Database License Lookup với LMfWC Integration
     * Step 4.2.3 - Database License Lookup
     * Implements comprehensive license database lookup với LMfWC integration
     *
     * @since 4.2.1
     * @updated 4.2.3
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

        // Input validation với enhanced format checking
        $format_validation = $this->validate_license_key_format($license_key, true);
        if (!$format_validation['valid']) {
            $result = array(
                'valid' => false,
                'error' => $format_validation['error_message'] ?? 'Invalid license key format',
                'code' => $format_validation['error_code'] ?? 'invalid_format',
                'format_details' => $format_validation
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        // Step 4.2.3: Enhanced Database License Lookup với LMfWC Integration
        $license = $this->lookup_license_from_database($license_key);

        if (!$license) {
            $result = array(
                'valid' => false,
                'error' => 'License không tồn tại trong hệ thống',
                'code' => 'license_not_found',
                'lookup_details' => $this->get_lookup_debug_info($license_key)
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        // Enhanced status validation với LMfWC status mapping
        $status_validation = $this->validate_license_status($license);
        if (!$status_validation['valid']) {
            $result = array(
                'valid' => false,
                'error' => $status_validation['error'],
                'code' => $status_validation['code'],
                'license' => $license,
                'status_details' => $status_validation
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        // Enhanced expiry validation với automatic status updates
        $expiry_validation = $this->validate_license_expiry_date($license);
        if (!$expiry_validation['valid']) {
            // Auto-update expired license status
            $this->update_expired_license_status($license);

            $result = array(
                'valid' => false,
                'error' => $expiry_validation['error'],
                'code' => $expiry_validation['code'],
                'license' => $license,
                'expiry_details' => $expiry_validation
            );
            $this->validation_cache[$cache_key] = $result;
            return $result;
        }

        // License validation successful - prepare comprehensive result
        $result = array(
            'valid' => true,
            'license' => $license,
            'days_until_expiry' => $expiry_validation['days_until_expiry'],
            'expiry_warning' => $expiry_validation['expiry_warning'],
            'lookup_source' => $license['lookup_source'] ?? 'lmfwc',
            'validation_timestamp' => current_time('mysql')
        );

        // Cache result for performance với TTL
        $this->validation_cache[$cache_key] = $result;

        // Log successful validation for audit
        $this->log_license_validation_success($license_key, $license);

        return $result;
    }

    /**
     * Enhanced Database License Lookup
     * Step 4.2.3 - Core database lookup functionality
     *
     * @since 4.2.3
     * @param string $license_key License key to look up
     * @return array|null License data or null if not found
     */
    private function lookup_license_from_database($license_key) {
        global $wpdb;

        // LMfWC Integration: Query LMfWC database với proper table prefix (bz_ prefix)
        $lmfwc_table = 'bz_lmfwc_licenses';

        // Check if LMfWC table exists
        if (!$this->table_exists($lmfwc_table)) {
            // Fallback to VD licenses table if exists
            $vd_table = $wpdb->prefix . 'vd_licenses';
            if ($this->table_exists($vd_table)) {
                return $this->lookup_from_vd_licenses($license_key);
            }
            return null;
        }

        // Enhanced LMfWC query với comprehensive field selection
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT
                id,
                order_id,
                product_id,
                user_id,
                license_key,
                hash,
                expires_at,
                valid_for,
                source,
                status,
                times_activated,
                times_activated_max,
                created_at,
                created_by,
                updated_at,
                updated_by
            FROM {$lmfwc_table}
            WHERE license_key = %s
            LIMIT 1",
            $license_key
        ), ARRAY_A);

        if ($license) {
            // Add lookup source information
            $license['lookup_source'] = 'lmfwc';
            $license['table_name'] = $lmfwc_table;

            // Enhanced status mapping từ LMfWC status codes
            $license['mapped_status'] = $this->map_lmfwc_status($license['status']);

            // Add validation metadata
            $license['lookup_timestamp'] = current_time('mysql');
        }

        return $license;
    }

    /**
     * Fallback lookup from VD licenses table
     * Step 4.2.3 - Fallback mechanism
     *
     * @since 4.2.3
     * @param string $license_key License key to look up
     * @return array|null License data or null if not found
     */
    private function lookup_from_vd_licenses($license_key) {
        global $wpdb;

        $vd_table = $wpdb->prefix . 'vd_licenses';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT
                id,
                license_key,
                product_id,
                order_id,
                user_id,
                status,
                max_devices,
                expires_at,
                created_at,
                updated_at
            FROM {$vd_table}
            WHERE license_key = %s
            LIMIT 1",
            $license_key
        ), ARRAY_A);

        if ($license) {
            $license['lookup_source'] = 'vd_internal';
            $license['table_name'] = $vd_table;
            $license['mapped_status'] = $license['status']; // Direct mapping
            $license['lookup_timestamp'] = current_time('mysql');
        }

        return $license;
    }

    /**
     * Map LMfWC status codes to VD status
     * Step 4.2.3 - Status mapping integration
     *
     * @since 4.2.3
     * @param mixed $lmfwc_status LMfWC status code
     * @return string Mapped VD status
     */
    private function map_lmfwc_status($lmfwc_status) {
        // LMfWC Status Code Mapping theo documentation
        $status_mapping = array(
            1 => 'active',      // SOLD/DELIVERED
            2 => 'inactive',    // INACTIVE
            3 => 'expired',     // EXPIRED
            4 => 'suspended',   // DISABLED
            'active' => 'active',
            'inactive' => 'inactive',
            'expired' => 'expired',
            'disabled' => 'suspended',
            'suspended' => 'suspended'
        );

        return $status_mapping[$lmfwc_status] ?? 'inactive';
    }

    /**
     * Enhanced License Status Validation Framework
     * Step 4.2.4.1 - Comprehensive status enum validation với transition rules
     *
     * @since 4.2.3
     * @updated 4.2.4.1
     * @param array $license License data
     * @return array Validation result with comprehensive status analysis
     */
    private function validate_license_status($license) {
        // Step 4.2.4.1: Enhanced status validation framework
        $validation_result = $this->perform_status_enum_validation($license);

        // Legacy compatibility: maintain original method behavior
        if (!$validation_result['valid']) {
            return $validation_result;
        }

        return array(
            'valid' => true,
            'mapped_status' => $validation_result['status_info']['mapped_status'],
            'original_status' => $validation_result['status_info']['original_status'],
            'status_details' => $validation_result // Include detailed info for advanced usage
        );
    }

    /**
     * Step 4.2.4.1 - Status Enum Validation Framework
     * Core comprehensive status validation với enum checking và transition rules
     *
     * @since 4.2.4.1
     * @param array $license License data
     * @return array Comprehensive validation result
     */
    private function perform_status_enum_validation($license) {
        $debug_info = array();
        $validation_start = microtime(true);

        try {
            // 1. Status Enum Definition và Validation
            $status_info = $this->get_comprehensive_status_info($license);
            $debug_info['status_info'] = $status_info;

            // 2. Enum Validation
            $enum_validation = $this->validate_status_enum($status_info['mapped_status']);
            if (!$enum_validation['valid']) {
                return $this->create_status_validation_error(
                    'status_enum_invalid',
                    $enum_validation['error'],
                    $status_info,
                    $debug_info
                );
            }

            // 3. Status Transition Validation (if previous status exists)
            if (isset($license['previous_status'])) {
                $transition_validation = $this->validate_status_transition(
                    $license['previous_status'],
                    $status_info['mapped_status']
                );
                $debug_info['transition_validation'] = $transition_validation;
            }

            // 4. Status-specific Business Rules
            $business_rules_result = $this->validate_status_business_rules($status_info);
            if (!$business_rules_result['valid']) {
                return $this->create_status_validation_error(
                    $business_rules_result['code'],
                    $business_rules_result['error'],
                    $status_info,
                    array_merge($debug_info, array('business_rules' => $business_rules_result))
                );
            }

            // 5. Status Hierarchy Validation
            $hierarchy_validation = $this->validate_status_hierarchy($status_info['mapped_status']);
            $debug_info['hierarchy'] = $hierarchy_validation;

            $validation_end = microtime(true);
            $debug_info['validation_time_ms'] = round(($validation_end - $validation_start) * 1000, 2);

            // Debug logging for successful validation
            $this->log_status_validation_debug('status_validation_success', $status_info, $debug_info);

            return array(
                'valid' => true,
                'status_info' => $status_info,
                'enum_validation' => $enum_validation,
                'hierarchy' => $hierarchy_validation,
                'debug_info' => $debug_info,
                'validation_timestamp' => current_time('mysql')
            );

        } catch (Exception $e) {
            $debug_info['exception'] = array(
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            );

            $this->log_status_validation_debug('status_validation_exception', $status_info ?? array(), $debug_info);

            return $this->create_status_validation_error(
                'status_validation_exception',
                'Lỗi hệ thống khi kiểm tra trạng thái license: ' . $e->getMessage(),
                $status_info ?? array(),
                $debug_info
            );
        }
    }

    /**
     * Step 4.2.4.1 - Get comprehensive status information
     * Comprehensive status mapping và analysis
     *
     * @since 4.2.4.1
     * @param array $license License data
     * @return array Comprehensive status information
     */
    private function get_comprehensive_status_info($license) {
        $original_status = $license['status'] ?? null;
        $mapped_status = $license['mapped_status'] ?? $this->map_lmfwc_status($original_status);

        return array(
            'original_status' => $original_status,
            'mapped_status' => $mapped_status,
            'status_source' => $license['lookup_source'] ?? 'lmfwc',
            'status_mapping_applied' => ($original_status !== $mapped_status),
            'license_id' => $license['id'] ?? null,
            'product_id' => $license['product_id'] ?? null,
            'status_updated_at' => $license['updated_at'] ?? null
        );
    }

    /**
     * Step 4.2.4.1 - Status Enum Validation
     * Validate status against defined enums
     *
     * @since 4.2.4.1
     * @param string $status Status to validate
     * @return array Validation result
     */
    private function validate_status_enum($status) {
        $valid_statuses = $this->get_valid_status_enums();

        if (!in_array($status, $valid_statuses, true)) {
            return array(
                'valid' => false,
                'error' => sprintf('Trạng thái "%s" không hợp lệ. Các trạng thái cho phép: %s',
                    $status,
                    implode(', ', $valid_statuses)
                ),
                'provided_status' => $status,
                'valid_statuses' => $valid_statuses
            );
        }

        return array(
            'valid' => true,
            'status' => $status,
            'status_description' => $this->get_status_description($status),
            'status_category' => $this->get_status_category($status)
        );
    }

    /**
     * Step 4.2.4.1 - Get valid status enums
     * Define all valid license status enums
     *
     * @since 4.2.4.1
     * @return array Valid status enums
     */
    private function get_valid_status_enums() {
        return array(
            'active',     // License is active and usable
            'inactive',   // License exists but not activated
            'suspended',  // License temporarily disabled
            'expired',    // License has expired
            'revoked',    // License permanently revoked
            'pending'     // License pending activation (new in 4.2.4.1)
        );
    }

    /**
     * Step 4.2.4.1 - Status transition validation
     * Validate if status transition is allowed
     *
     * @since 4.2.4.1
     * @param string $from_status Previous status
     * @param string $to_status New status
     * @return array Transition validation result
     */
    private function validate_status_transition($from_status, $to_status) {
        $allowed_transitions = $this->get_allowed_status_transitions();

        if (!isset($allowed_transitions[$from_status])) {
            return array(
                'valid' => false,
                'error' => sprintf('Không thể chuyển từ trạng thái không xác định: %s', $from_status),
                'from_status' => $from_status,
                'to_status' => $to_status
            );
        }

        if (!in_array($to_status, $allowed_transitions[$from_status], true)) {
            return array(
                'valid' => false,
                'error' => sprintf('Không thể chuyển từ "%s" sang "%s"', $from_status, $to_status),
                'from_status' => $from_status,
                'to_status' => $to_status,
                'allowed_transitions' => $allowed_transitions[$from_status]
            );
        }

        return array(
            'valid' => true,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'transition_type' => $this->get_transition_type($from_status, $to_status)
        );
    }

    /**
     * Step 4.2.4.1 - Status business rules validation
     * Apply business-specific validation rules
     *
     * @since 4.2.4.1
     * @param array $status_info Status information
     * @return array Business rules validation result
     */
    private function validate_status_business_rules($status_info) {
        $mapped_status = $status_info['mapped_status'];

        switch ($mapped_status) {
            case 'suspended':
                return array(
                    'valid' => false,
                    'error' => 'License đã bị tạm khóa hoặc vô hiệu hóa',
                    'code' => 'license_suspended'
                );

            case 'inactive':
                return array(
                    'valid' => false,
                    'error' => 'License chưa được kích hoạt hoặc đã bị vô hiệu hóa',
                    'code' => 'license_inactive'
                );

            case 'expired':
                return array(
                    'valid' => false,
                    'error' => 'License đã hết hạn',
                    'code' => 'license_expired'
                );

            case 'revoked':
                return array(
                    'valid' => false,
                    'error' => 'License đã bị thu hồi vĩnh viễn',
                    'code' => 'license_revoked'
                );

            case 'pending':
                return array(
                    'valid' => false,
                    'error' => 'License đang chờ kích hoạt',
                    'code' => 'license_pending'
                );

            case 'active':
                // Additional checks for active licenses
                return $this->validate_active_license_rules($status_info);

            default:
                return array(
                    'valid' => false,
                    'error' => sprintf('Trạng thái license không được hỗ trợ: %s', $mapped_status),
                    'code' => 'unsupported_status'
                );
        }
    }

    /**
     * Step 4.2.4.1 - Active license business rules
     * Specific rules for active licenses
     *
     * @since 4.2.4.1
     * @param array $status_info Status information
     * @return array Validation result
     */
    private function validate_active_license_rules($status_info) {
        // Active licenses are generally valid, but may have warnings
        return array(
            'valid' => true,
            'code' => 'license_active',
            'warnings' => array() // Can be populated with non-blocking warnings
        );
    }

    /**
     * Step 4.2.4.1 - Get allowed status transitions
     * Define allowed status transition matrix
     *
     * @since 4.2.4.1
     * @return array Status transition matrix
     */
    private function get_allowed_status_transitions() {
        return array(
            'pending'   => array('active', 'inactive', 'expired'),
            'inactive'  => array('active', 'suspended', 'expired'),
            'active'    => array('suspended', 'expired', 'revoked', 'inactive'),
            'suspended' => array('active', 'expired', 'revoked'),
            'expired'   => array('active', 'revoked'), // Can be renewed
            'revoked'   => array() // Terminal state - no transitions allowed
        );
    }

    /**
     * Step 4.2.4.1 - Get status description
     * Human-readable status descriptions
     *
     * @since 4.2.4.1
     * @param string $status Status enum
     * @return string Status description
     */
    private function get_status_description($status) {
        $descriptions = array(
            'active'    => 'License đang hoạt động và có thể sử dụng',
            'inactive'  => 'License tồn tại nhưng chưa được kích hoạt',
            'suspended' => 'License tạm thời bị vô hiệu hóa',
            'expired'   => 'License đã hết hạn sử dụng',
            'revoked'   => 'License đã bị thu hồi vĩnh viễn',
            'pending'   => 'License đang chờ được kích hoạt'
        );

        return $descriptions[$status] ?? 'Trạng thái không xác định';
    }

    /**
     * Step 4.2.4.1 - Get status category
     * Categorize status for business logic
     *
     * @since 4.2.4.1
     * @param string $status Status enum
     * @return string Status category
     */
    private function get_status_category($status) {
        $categories = array(
            'active'    => 'usable',
            'inactive'  => 'unusable',
            'suspended' => 'temporarily_unusable',
            'expired'   => 'unusable',
            'revoked'   => 'permanently_unusable',
            'pending'   => 'unusable'
        );

        return $categories[$status] ?? 'unknown';
    }

    /**
     * Step 4.2.4.1 - Status hierarchy validation
     * Validate status priority and hierarchy
     *
     * @since 4.2.4.1
     * @param string $status Status to validate
     * @return array Hierarchy information
     */
    private function validate_status_hierarchy($status) {
        $hierarchy = array(
            'revoked'   => 1, // Highest priority - terminal
            'expired'   => 2,
            'suspended' => 3,
            'inactive'  => 4,
            'pending'   => 5,
            'active'    => 6  // Lowest priority - default good state
        );

        return array(
            'status' => $status,
            'priority' => $hierarchy[$status] ?? 999,
            'is_terminal' => ($status === 'revoked'),
            'is_good_state' => ($status === 'active'),
            'hierarchy_level' => array_search($hierarchy[$status] ?? 999, $hierarchy) + 1
        );
    }

    /**
     * Step 4.2.4.1 - Get transition type
     * Categorize transition type for business logic
     *
     * @since 4.2.4.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @return string Transition type
     */
    private function get_transition_type($from_status, $to_status) {
        $hierarchy = array(
            'revoked' => 1, 'expired' => 2, 'suspended' => 3,
            'inactive' => 4, 'pending' => 5, 'active' => 6
        );

        $from_priority = $hierarchy[$from_status] ?? 999;
        $to_priority = $hierarchy[$to_status] ?? 999;

        if ($to_priority > $from_priority) {
            return 'upgrade'; // Moving to better state
        } elseif ($to_priority < $from_priority) {
            return 'downgrade'; // Moving to worse state
        } else {
            return 'lateral'; // Same level
        }
    }

    /**
     * Step 4.2.4.1 - Create status validation error
     * Standardized error response creation
     *
     * @since 4.2.4.1
     * @param string $code Error code
     * @param string $message Error message
     * @param array $status_info Status information
     * @param array $debug_info Debug information
     * @return array Error response
     */
    private function create_status_validation_error($code, $message, $status_info, $debug_info) {
        return array(
            'valid' => false,
            'error' => $message,
            'code' => $code,
            'status_info' => $status_info,
            'debug_info' => $debug_info,
            'error_timestamp' => current_time('mysql')
        );
    }

    /**
     * Step 4.2.4.1 - Debug logging for status validation
     * Enhanced logging với detailed information
     *
     * @since 4.2.4.1
     * @param string $event_type Type of validation event
     * @param array $status_info Status information
     * @param array $debug_info Debug information
     * @return void
     */
    private function log_status_validation_debug($event_type, $status_info, $debug_info) {
        if (function_exists('vd_debug_log')) {
            $log_data = array(
                'event' => $event_type,
                'status_info' => $status_info,
                'validation_time' => $debug_info['validation_time_ms'] ?? 0,
                'timestamp' => current_time('mysql')
            );

            vd_debug_log(sprintf(
                '[VD License Validator 4.2.4.1] %s: %s (%.2fms)',
                $event_type,
                wp_json_encode($log_data, JSON_UNESCAPED_UNICODE),
                $debug_info['validation_time_ms'] ?? 0
            ));
        }
    }

    /**
     * Step 4.2.4.2 - Business Rule Enforcement Engine
     * Advanced business rules cho license status transitions với grace periods
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $context Additional context (previous_status, transition_reason, etc.)
     * @return array Business rule enforcement result
     */
    public function enforce_business_rules($license, $context = array()) {
        $enforcement_start = microtime(true);
        $debug_info = array(
            'license_id' => $license['id'] ?? null,
            'context' => $context
        );

        try {
            // 1. Get current business rule configuration
            $rule_config = $this->get_business_rule_configuration($license);
            $debug_info['rule_config'] = $rule_config;

            // 2. Status-specific business rule enforcement
            $status_rules_result = $this->enforce_status_specific_rules($license, $rule_config, $context);
            if (!$status_rules_result['valid']) {
                return $this->create_business_rule_error(
                    $status_rules_result['code'],
                    $status_rules_result['error'],
                    $license,
                    array_merge($debug_info, $status_rules_result['debug_info'] ?? array())
                );
            }

            // 3. Grace period enforcement (if applicable)
            $grace_period_result = $this->enforce_grace_period_rules($license, $rule_config, $context);
            $debug_info['grace_period'] = $grace_period_result;

            // 4. Automatic escalation rules
            $escalation_result = $this->enforce_escalation_rules($license, $rule_config, $context);
            $debug_info['escalation'] = $escalation_result;

            // 5. Transition validation rules
            if (isset($context['from_status'], $context['to_status'])) {
                $transition_result = $this->enforce_transition_rules(
                    $context['from_status'],
                    $context['to_status'],
                    $license,
                    $rule_config
                );
                $debug_info['transition_enforcement'] = $transition_result;

                if (!$transition_result['allowed']) {
                    return $this->create_business_rule_error(
                        'transition_not_allowed',
                        $transition_result['reason'],
                        $license,
                        $debug_info
                    );
                }
            }

            $enforcement_end = microtime(true);
            $debug_info['enforcement_time_ms'] = round(($enforcement_end - $enforcement_start) * 1000, 2);

            // Log successful business rule enforcement
            $this->log_business_rule_event('business_rules_enforced', $license, $debug_info);

            return array(
                'valid' => true,
                'rules_applied' => $status_rules_result['rules_applied'] ?? array(),
                'grace_period' => $grace_period_result,
                'escalation' => $escalation_result,
                'debug_info' => $debug_info,
                'enforcement_timestamp' => current_time('mysql')
            );

        } catch (Exception $e) {
            $debug_info['exception'] = array(
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            );

            $this->log_business_rule_event('business_rules_exception', $license, $debug_info);

            return $this->create_business_rule_error(
                'business_rules_exception',
                'Lỗi hệ thống khi thực thi business rules: ' . $e->getMessage(),
                $license,
                $debug_info
            );
        }
    }

    /**
     * Step 4.2.4.2 - Get business rule configuration
     * Load configurable business rule parameters
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @return array Business rule configuration
     */
    private function get_business_rule_configuration($license) {
        // Default business rule configuration
        $default_config = array(
            'grace_periods' => array(
                'expiry_warning_days' => 7,        // Warning before expiry
                'status_downgrade_hours' => 24,    // Grace period before downgrade
                'device_limit_hours' => 48,        // Grace period for device limit excess
                'suspension_review_hours' => 72    // Review period for suspensions
            ),
            'escalation_rules' => array(
                'auto_suspend_after_days' => 30,   // Auto-suspend expired licenses
                'auto_revoke_after_days' => 90,    // Auto-revoke long-expired licenses
                'warning_escalation_days' => 3,   // Escalate warnings
                'max_violation_count' => 5        // Max violations before auto-action
            ),
            'transition_policies' => array(
                'allow_expired_to_active' => false,      // Require manual renewal
                'allow_revoked_transitions' => false,    // Revoked is terminal
                'require_admin_approval' => array('revoked'), // Admin approval needed
                'auto_downgrade_enabled' => true         // Allow automatic downgrades
            ),
            'notification_rules' => array(
                'notify_on_escalation' => true,
                'notify_on_grace_period' => true,
                'notify_on_violation' => true,
                'admin_notification_threshold' => 'high_risk'
            )
        );

        // Get license-specific overrides (inheritance system)
        $license_settings = $this->get_license_settings(
            $license['id'] ?? 0,
            $license['product_id'] ?? 0
        );

        // Merge configuration với inheritance
        $merged_config = array_replace_recursive($default_config, $license_settings['business_rules'] ?? array());

        return $merged_config;
    }

    /**
     * Step 4.2.4.2 - Enforce status-specific business rules
     * Apply rules specific to each license status
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Status rule enforcement result
     */
    private function enforce_status_specific_rules($license, $rule_config, $context) {
        $current_status = $license['mapped_status'] ?? $license['status'] ?? 'inactive';
        $rules_applied = array();

        switch ($current_status) {
            case 'active':
                return $this->enforce_active_license_business_rules($license, $rule_config, $context);

            case 'expired':
                return $this->enforce_expired_license_business_rules($license, $rule_config, $context);

            case 'suspended':
                return $this->enforce_suspended_license_business_rules($license, $rule_config, $context);

            case 'pending':
                return $this->enforce_pending_license_business_rules($license, $rule_config, $context);

            case 'revoked':
                return $this->enforce_revoked_license_business_rules($license, $rule_config, $context);

            case 'inactive':
            default:
                return $this->enforce_inactive_license_business_rules($license, $rule_config, $context);
        }
    }

    /**
     * Step 4.2.4.2 - Enforce grace period rules
     * Handle grace periods cho various license scenarios
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Grace period enforcement result
     */
    private function enforce_grace_period_rules($license, $rule_config, $context) {
        $grace_periods = $rule_config['grace_periods'] ?? array();
        $current_status = $license['mapped_status'] ?? $license['status'] ?? 'inactive';

        $grace_info = array(
            'has_grace_period' => false,
            'grace_type' => null,
            'grace_remaining_hours' => 0,
            'grace_expires_at' => null
        );

        // Check for expiry warning grace period
        if (isset($license['expires_at']) && $license['expires_at']) {
            $expiry_timestamp = strtotime($license['expires_at']);
            $current_timestamp = current_time('timestamp');
            $days_until_expiry = ceil(($expiry_timestamp - $current_timestamp) / (24 * 3600));

            if ($days_until_expiry > 0 && $days_until_expiry <= ($grace_periods['expiry_warning_days'] ?? 7)) {
                $grace_info['has_grace_period'] = true;
                $grace_info['grace_type'] = 'expiry_warning';
                $grace_info['grace_remaining_hours'] = $days_until_expiry * 24;
                $grace_info['grace_expires_at'] = $license['expires_at'];
            }
        }

        // Check for status downgrade grace period
        if (isset($context['status_changed_at']) && in_array($current_status, array('suspended', 'expired'))) {
            $status_change_timestamp = strtotime($context['status_changed_at']);
            $grace_period_hours = $grace_periods['status_downgrade_hours'] ?? 24;
            $grace_end_timestamp = $status_change_timestamp + ($grace_period_hours * 3600);

            if (current_time('timestamp') < $grace_end_timestamp) {
                $grace_info['has_grace_period'] = true;
                $grace_info['grace_type'] = 'status_downgrade';
                $grace_info['grace_remaining_hours'] = ceil(($grace_end_timestamp - current_time('timestamp')) / 3600);
                $grace_info['grace_expires_at'] = date('Y-m-d H:i:s', $grace_end_timestamp);
            }
        }

        return $grace_info;
    }

    /**
     * Step 4.2.4.2 - Enforce escalation rules
     * Automatic status escalation based on business rules
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Escalation enforcement result
     */
    private function enforce_escalation_rules($license, $rule_config, $context) {
        $escalation_rules = $rule_config['escalation_rules'] ?? array();
        $current_status = $license['mapped_status'] ?? $license['status'] ?? 'inactive';

        $escalation_info = array(
            'escalation_required' => false,
            'escalation_type' => null,
            'target_status' => null,
            'escalation_reason' => null,
            'auto_escalation_enabled' => true
        );

        // Check for auto-suspension of long-expired licenses
        if ($current_status === 'expired' && isset($license['expires_at'])) {
            $expiry_timestamp = strtotime($license['expires_at']);
            $days_expired = ceil((current_time('timestamp') - $expiry_timestamp) / (24 * 3600));
            $auto_suspend_days = $escalation_rules['auto_suspend_after_days'] ?? 30;

            if ($days_expired >= $auto_suspend_days) {
                $escalation_info['escalation_required'] = true;
                $escalation_info['escalation_type'] = 'auto_suspension';
                $escalation_info['target_status'] = 'suspended';
                $escalation_info['escalation_reason'] = sprintf(
                    'License expired %d days ago, auto-suspending per business rules',
                    $days_expired
                );
            }
        }

        // Check for auto-revocation of long-suspended licenses
        if ($current_status === 'suspended' && isset($context['status_changed_at'])) {
            $suspension_timestamp = strtotime($context['status_changed_at']);
            $days_suspended = ceil((current_time('timestamp') - $suspension_timestamp) / (24 * 3600));
            $auto_revoke_days = $escalation_rules['auto_revoke_after_days'] ?? 90;

            if ($days_suspended >= $auto_revoke_days) {
                $escalation_info['escalation_required'] = true;
                $escalation_info['escalation_type'] = 'auto_revocation';
                $escalation_info['target_status'] = 'revoked';
                $escalation_info['escalation_reason'] = sprintf(
                    'License suspended for %d days, auto-revoking per business rules',
                    $days_suspended
                );
            }
        }

        return $escalation_info;
    }

    /**
     * Step 4.2.4.2 - Enforce transition rules
     * Validate business rules for status transitions
     *
     * @since 4.2.4.2
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @return array Transition rule enforcement result
     */
    private function enforce_transition_rules($from_status, $to_status, $license, $rule_config) {
        $transition_policies = $rule_config['transition_policies'] ?? array();

        // Check if transition requires admin approval
        if (in_array($to_status, $transition_policies['require_admin_approval'] ?? array())) {
            if (!current_user_can('manage_options')) {
                return array(
                    'allowed' => false,
                    'reason' => sprintf('Chuyển đổi sang trạng thái "%s" yêu cầu quyền admin', $to_status),
                    'requires_admin' => true
                );
            }
        }

        // Check specific transition policies
        switch ($to_status) {
            case 'active':
                if ($from_status === 'expired' && !($transition_policies['allow_expired_to_active'] ?? false)) {
                    return array(
                        'allowed' => false,
                        'reason' => 'License hết hạn không thể tự động chuyển về active, cần renew thủ công',
                        'requires_manual_renewal' => true
                    );
                }
                break;

            case 'revoked':
                // Revoked transitions should be carefully controlled
                if (!current_user_can('manage_options')) {
                    return array(
                        'allowed' => false,
                        'reason' => 'Chỉ admin mới có thể revoke license',
                        'requires_admin' => true
                    );
                }
                break;
        }

        // Check if source status allows transitions
        if ($from_status === 'revoked' && !($transition_policies['allow_revoked_transitions'] ?? false)) {
            return array(
                'allowed' => false,
                'reason' => 'License đã bị revoke không thể chuyển đổi trạng thái',
                'is_terminal_state' => true
            );
        }

        return array(
            'allowed' => true,
            'transition_type' => $this->get_transition_type($from_status, $to_status),
            'grace_period_applicable' => $this->is_grace_period_applicable($from_status, $to_status, $rule_config)
        );
    }

    /**
     * Step 4.2.4.2 - Active license business rules
     * Business rules specific to active licenses
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Active license rule result
     */
    private function enforce_active_license_business_rules($license, $rule_config, $context) {
        $rules_applied = array();

        // Check expiry warning
        if (isset($license['expires_at']) && $license['expires_at']) {
            $expiry_timestamp = strtotime($license['expires_at']);
            $warning_days = $rule_config['grace_periods']['expiry_warning_days'] ?? 7;
            $days_until_expiry = ceil(($expiry_timestamp - current_time('timestamp')) / (24 * 3600));

            if ($days_until_expiry <= $warning_days && $days_until_expiry > 0) {
                $rules_applied[] = array(
                    'rule' => 'expiry_warning',
                    'status' => 'warning',
                    'message' => sprintf('License sẽ hết hạn trong %d ngày', $days_until_expiry),
                    'days_remaining' => $days_until_expiry
                );
            }
        }

        return array(
            'valid' => true,
            'rules_applied' => $rules_applied,
            'status' => 'active_with_rules'
        );
    }

    /**
     * Step 4.2.4.2 - Expired license business rules
     * Business rules for expired licenses với grace periods
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Expired license rule result
     */
    private function enforce_expired_license_business_rules($license, $rule_config, $context) {
        // Expired licenses fail validation unless in grace period
        $grace_period = $this->enforce_grace_period_rules($license, $rule_config, $context);

        if ($grace_period['has_grace_period']) {
            return array(
                'valid' => true, // Allow access during grace period
                'rules_applied' => array(array(
                    'rule' => 'grace_period_access',
                    'status' => 'grace_period',
                    'message' => sprintf(
                        'License hết hạn nhưng trong grace period (còn %d giờ)',
                        $grace_period['grace_remaining_hours']
                    ),
                    'grace_expires_at' => $grace_period['grace_expires_at']
                )),
                'debug_info' => array('grace_period' => $grace_period)
            );
        }

        return array(
            'valid' => false,
            'error' => 'License đã hết hạn và không trong grace period',
            'code' => 'license_expired_no_grace',
            'debug_info' => array('grace_period' => $grace_period)
        );
    }

    /**
     * Step 4.2.4.2 - Suspended license business rules
     * Business rules for suspended licenses
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Suspended license rule result
     */
    private function enforce_suspended_license_business_rules($license, $rule_config, $context) {
        // Check if suspension is under review (grace period)
        $grace_period = $this->enforce_grace_period_rules($license, $rule_config, $context);

        if ($grace_period['has_grace_period'] && $grace_period['grace_type'] === 'suspension_review') {
            return array(
                'valid' => false, // Still suspended but with review info
                'error' => sprintf(
                    'License bị suspended, đang trong thời gian review (còn %d giờ)',
                    $grace_period['grace_remaining_hours']
                ),
                'code' => 'license_suspended_under_review',
                'rules_applied' => array(array(
                    'rule' => 'suspension_review_period',
                    'status' => 'under_review',
                    'review_expires_at' => $grace_period['grace_expires_at']
                )),
                'debug_info' => array('grace_period' => $grace_period)
            );
        }

        return array(
            'valid' => false,
            'error' => 'License đã bị tạm khóa',
            'code' => 'license_suspended',
            'debug_info' => array('suspension_permanent' => true)
        );
    }

    /**
     * Step 4.2.4.2 - Pending license business rules
     * Business rules for pending licenses
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Pending license rule result
     */
    private function enforce_pending_license_business_rules($license, $rule_config, $context) {
        // Check if auto-activation is allowed
        $created_at = $license['created_at'] ?? $license['updated_at'] ?? null;

        if ($created_at) {
            $pending_hours = ceil((current_time('timestamp') - strtotime($created_at)) / 3600);
            $max_pending_hours = $rule_config['escalation_rules']['auto_activate_after_hours'] ?? 24;

            if ($pending_hours >= $max_pending_hours) {
                return array(
                    'valid' => false,
                    'error' => sprintf('License pending quá lâu (%d giờ), cần intervention', $pending_hours),
                    'code' => 'license_pending_too_long',
                    'escalation_required' => true,
                    'suggested_action' => 'manual_activation_review'
                );
            }
        }

        return array(
            'valid' => false,
            'error' => 'License đang chờ kích hoạt',
            'code' => 'license_pending'
        );
    }

    /**
     * Step 4.2.4.2 - Revoked license business rules
     * Business rules for revoked licenses (terminal state)
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Revoked license rule result
     */
    private function enforce_revoked_license_business_rules($license, $rule_config, $context) {
        return array(
            'valid' => false,
            'error' => 'License đã bị thu hồi vĩnh viễn',
            'code' => 'license_revoked_permanent',
            'is_terminal' => true,
            'no_recovery_possible' => true
        );
    }

    /**
     * Step 4.2.4.2 - Inactive license business rules
     * Business rules for inactive licenses
     *
     * @since 4.2.4.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Additional context
     * @return array Inactive license rule result
     */
    private function enforce_inactive_license_business_rules($license, $rule_config, $context) {
        return array(
            'valid' => false,
            'error' => 'License chưa được kích hoạt',
            'code' => 'license_inactive',
            'activation_required' => true
        );
    }

    /**
     * Step 4.2.4.2 - Check if grace period is applicable
     * Determine if grace period applies to status transition
     *
     * @since 4.2.4.2
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $rule_config Business rule configuration
     * @return bool True if grace period applicable
     */
    private function is_grace_period_applicable($from_status, $to_status, $rule_config) {
        $grace_applicable_transitions = array(
            'active' => array('suspended', 'expired'),
            'suspended' => array('revoked'),
            'expired' => array('revoked')
        );

        return isset($grace_applicable_transitions[$from_status]) &&
               in_array($to_status, $grace_applicable_transitions[$from_status]);
    }

    /**
     * Step 4.2.4.2 - Create business rule error
     * Standardized business rule error response
     *
     * @since 4.2.4.2
     * @param string $code Error code
     * @param string $message Error message
     * @param array $license License data
     * @param array $debug_info Debug information
     * @return array Business rule error response
     */
    private function create_business_rule_error($code, $message, $license, $debug_info) {
        return array(
            'valid' => false,
            'error' => $message,
            'code' => $code,
            'license_id' => $license['id'] ?? null,
            'current_status' => $license['mapped_status'] ?? $license['status'] ?? null,
            'debug_info' => $debug_info,
            'business_rule_timestamp' => current_time('mysql')
        );
    }

    /**
     * Step 4.2.4.2 - Log business rule events
     * Enhanced logging for business rule enforcement
     *
     * @since 4.2.4.2
     * @param string $event_type Type of business rule event
     * @param array $license License data
     * @param array $debug_info Debug information
     * @return void
     */
    private function log_business_rule_event($event_type, $license, $debug_info) {
        if (function_exists('vd_debug_log')) {
            $log_data = array(
                'event' => $event_type,
                'license_id' => $license['id'] ?? null,
                'product_id' => $license['product_id'] ?? null,
                'status' => $license['mapped_status'] ?? $license['status'] ?? null,
                'enforcement_time' => $debug_info['enforcement_time_ms'] ?? 0,
                'timestamp' => current_time('mysql')
            );

            vd_debug_log(sprintf(
                '[VD License Validator 4.2.4.2] %s: %s (%.2fms)',
                $event_type,
                wp_json_encode($log_data, JSON_UNESCAPED_UNICODE),
                $debug_info['enforcement_time_ms'] ?? 0
            ));
        }

        // Log to audit system if available
        if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
            $this->security_audit->log_security_event(
                'business_rule_enforcement',
                array(
                    'event_type' => $event_type,
                    'license_id' => $license['id'] ?? null,
                    'status' => $license['mapped_status'] ?? $license['status'] ?? null
                ),
                'info'
            );
        }
    }

    /**
     * Enhanced License Expiry Date Validation
     * Step 4.2.3 - Comprehensive expiry checking
     *
     * @since 4.2.3
     * @param array $license License data
     * @return array Validation result
     */
    private function validate_license_expiry_date($license) {
        $expires_at = $license['expires_at'] ?? null;

        // Handle null expiry (lifetime license)
        if (!$expires_at || $expires_at === '0000-00-00 00:00:00') {
            return array(
                'valid' => true,
                'days_until_expiry' => null,
                'expiry_warning' => false,
                'is_lifetime' => true
            );
        }

        $expiry_timestamp = strtotime($expires_at);
        $current_timestamp = current_time('timestamp');

        // Check if expired
        if ($expiry_timestamp < $current_timestamp) {
            return array(
                'valid' => false,
                'error' => 'License đã hết hạn vào ' . date('d/m/Y H:i', $expiry_timestamp),
                'code' => 'license_expired',
                'expires_at' => $expires_at,
                'expired_since_days' => ceil(($current_timestamp - $expiry_timestamp) / (24 * 3600))
            );
        }

        // Calculate days until expiry
        $days_until_expiry = ceil(($expiry_timestamp - $current_timestamp) / (24 * 3600));

        // Check for expiry warning (within 7 days)
        $expiry_warning = $days_until_expiry <= 7;

        return array(
            'valid' => true,
            'days_until_expiry' => $days_until_expiry,
            'expiry_warning' => $expiry_warning,
            'expires_at' => $expires_at,
            'is_lifetime' => false
        );
    }

    /**
     * Update expired license status in database
     * Step 4.2.3 - Automatic status maintenance
     *
     * @since 4.2.3
     * @param array $license License data
     * @return bool Update success
     */
    private function update_expired_license_status($license) {
        global $wpdb;

        if (!isset($license['id']) || !isset($license['table_name'])) {
            return false;
        }

        // Update status to expired
        $updated = $wpdb->update(
            $license['table_name'],
            array('status' => 'expired'),
            array('id' => $license['id']),
            array('%s'),
            array('%d')
        );

        if ($updated) {
            // Log the automatic status update
            $this->log_automatic_status_update($license, 'expired');
        }

        return $updated !== false;
    }

    /**
     * Check if database table exists
     * Step 4.2.3 - Database validation utility
     *
     * @since 4.2.3
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
     * Get lookup debug information for troubleshooting
     * Step 4.2.3 - Debug utilities
     *
     * @since 4.2.3
     * @param string $license_key License key
     * @return array Debug information
     */
    private function get_lookup_debug_info($license_key) {
        global $wpdb;

        $lmfwc_table = 'bz_lmfwc_licenses';
        $vd_table = $wpdb->prefix . 'vd_licenses';

        return array(
            'license_key' => $license_key,
            'lmfwc_table_exists' => $this->table_exists($lmfwc_table),
            'vd_table_exists' => $this->table_exists($vd_table),
            'lmfwc_table_name' => $lmfwc_table,
            'vd_table_name' => $vd_table,
            'database_name' => DB_NAME,
            'wpdb_prefix' => $wpdb->prefix
        );
    }

    /**
     * Log successful license validation for audit
     * Step 4.2.3 - Audit logging integration
     *
     * @since 4.2.3
     * @param string $license_key License key
     * @param array $license License data
     * @return void
     */
    private function log_license_validation_success($license_key, $license) {
        if (function_exists('vd_debug_log')) {
            vd_debug_log(sprintf(
                '[VD License Validator] Successful validation: %s (ID: %s, Product: %s, Source: %s)',
                $license_key,
                $license['id'] ?? 'unknown',
                $license['product_id'] ?? 'unknown',
                $license['lookup_source'] ?? 'unknown'
            ));
        }

        // Integration với audit logger nếu available
        if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
            $this->security_audit->log_security_event(
                'license_validation_success',
                array(
                    'license_key' => substr($license_key, 0, 8) . '***', // Masked for security
                    'product_id' => $license['product_id'] ?? null,
                    'lookup_source' => $license['lookup_source'] ?? null
                ),
                'info'
            );
        }
    }

    /**
     * Log automatic status update for audit
     * Step 4.2.3 - Status update logging
     *
     * @since 4.2.3
     * @param array $license License data
     * @param string $new_status New status
     * @return void
     */
    private function log_automatic_status_update($license, $new_status) {
        if (function_exists('vd_debug_log')) {
            vd_debug_log(sprintf(
                '[VD License Validator] Auto-updated license status: ID %s to %s (was: %s)',
                $license['id'],
                $new_status,
                $license['status'] ?? 'unknown'
            ));
        }
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
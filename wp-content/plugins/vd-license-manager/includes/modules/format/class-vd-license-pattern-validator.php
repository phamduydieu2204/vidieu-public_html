<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Pattern Validator
 *
 * Handles license key pattern validation, format checking, and regex matching
 * Extracted from main validator class for better modularity and memory efficiency
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 * @namespace VD\LicenseManager\Format
 */
class VD_License_Pattern_Validator {

    /**
     * Singleton instance
     *
     * @var VD_License_Pattern_Validator|null
     */
    private static $instance = null;

    /**
     * Standard VD license key pattern
     *
     * @var string
     */
    private $license_key_pattern = '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{6}$/';

    /**
     * Alternative license patterns for compatibility
     *
     * @var array
     */
    private $alternative_patterns = array();

    /**
     * Validation statistics
     *
     * @var array
     */
    private $validation_stats = array(
        'total_validations' => 0,
        'successful_validations' => 0,
        'failed_validations' => 0,
        'pattern_matches' => array(),
        'common_errors' => array()
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_patterns();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Pattern_Validator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize license patterns
     *
     * @return void
     */
    private function init_patterns() {
        $this->alternative_patterns = array(
            'lmfwc_standard' => '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', // 20 chars
            'lmfwc_extended' => '/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/', // 26 chars with different dash placement
            'legacy_format' => '/^[A-Z0-9\-]{8,32}$/', // Business logic compatible format
            'uuid_format' => '/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/i', // UUID format
            'simple_format' => '/^[A-Z0-9]{16,32}$/' // Simple alphanumeric format
        );
    }

    /**
     * Validate license key format with comprehensive validation
     *
     * @param string $license_key License key to validate
     * @param bool $detailed Whether to return detailed validation results
     * @return bool|array True/false for simple validation, array for detailed
     */
    public function validate_license_key_format($license_key, $detailed = false) {
        $this->validation_stats['total_validations']++;

        $validation_result = array(
            'valid' => false,
            'error_code' => null,
            'error_message' => null,
            'format_checks' => array(),
            'matched_pattern' => null,
            'validation_time' => 0
        );

        $start_time = microtime(true);

        try {
            // Input type validation
            if (!is_string($license_key)) {
                return $this->create_validation_error('invalid_type', 'License key must be a string', $validation_result, $detailed);
            }
            $validation_result['format_checks']['type_check'] = true;

            // Input sanitization
            $original_key = $license_key;
            $license_key = sanitize_text_field(trim($license_key));

            // Check if sanitization changed the key (potential security issue)
            if ($original_key !== $license_key) {
                return $this->create_validation_error('sanitization_changed', 'License key contains invalid characters', $validation_result, $detailed);
            }
            $validation_result['format_checks']['sanitization_check'] = true;

            // Check empty
            if (empty($license_key)) {
                return $this->create_validation_error('empty', 'License key cannot be empty', $validation_result, $detailed);
            }
            $validation_result['format_checks']['empty_check'] = true;

            // Check minimum length
            if (strlen($license_key) < 8) {
                return $this->create_validation_error('too_short', 'License key too short (minimum 8 characters)', $validation_result, $detailed);
            }
            $validation_result['format_checks']['min_length_check'] = true;

            // Check maximum length
            if (strlen($license_key) > 36) {
                return $this->create_validation_error('too_long', 'License key too long (maximum 36 characters)', $validation_result, $detailed);
            }
            $validation_result['format_checks']['max_length_check'] = true;

            // Check standard VD format first
            $standard_pattern_match = preg_match($this->license_key_pattern, $license_key);
            $validation_result['format_checks']['standard_pattern'] = $standard_pattern_match;

            if ($standard_pattern_match) {
                $validation_result['matched_pattern'] = 'vd_standard';
                $this->update_pattern_stats('vd_standard');
            }

            // Check alternative patterns if standard doesn't match
            $alternative_match = false;
            if (!$standard_pattern_match) {
                foreach ($this->alternative_patterns as $pattern_name => $pattern) {
                    if (preg_match($pattern, $license_key)) {
                        $validation_result['format_checks'][$pattern_name] = true;
                        $validation_result['matched_pattern'] = $pattern_name;
                        $alternative_match = true;
                        $this->update_pattern_stats($pattern_name);
                        break;
                    } else {
                        $validation_result['format_checks'][$pattern_name] = false;
                    }
                }
            }

            // Overall format validation
            if (!$standard_pattern_match && !$alternative_match) {
                return $this->create_validation_error('invalid_format', 'License key format is invalid. Expected format: XXXX-XXXX-XXXX-XXXX-XXXXXX', $validation_result, $detailed);
            }
            $validation_result['format_checks']['overall_format'] = true;

            // Character set validation
            if (!preg_match('/^[A-Z0-9\-]+$/i', $license_key)) {
                return $this->create_validation_error('invalid_characters', 'License key contains invalid characters. Only A-Z, 0-9, and hyphens allowed', $validation_result, $detailed);
            }
            $validation_result['format_checks']['character_set'] = true;

            // Dash placement validation for standard format
            if ($standard_pattern_match) {
                $dash_validation = $this->validate_dash_placement($license_key);
                $validation_result['format_checks']['dash_placement'] = $dash_validation['valid'];

                if (!$dash_validation['valid']) {
                    return $this->create_validation_error('invalid_dash_placement', $dash_validation['message'], $validation_result, $detailed);
                }
            } else {
                $validation_result['format_checks']['dash_placement'] = true; // Skip for alternative formats
            }

            // All validations passed
            $validation_result['valid'] = true;
            $this->validation_stats['successful_validations']++;

            return $detailed ? $validation_result : true;

        } catch (Exception $e) {
            return $this->create_validation_error('validation_exception', 'Validation failed: ' . $e->getMessage(), $validation_result, $detailed);
        } finally {
            $validation_result['validation_time'] = round((microtime(true) - $start_time) * 1000, 2);
        }
    }

    /**
     * Validate dash placement in license key
     *
     * @param string $license_key License key to validate
     * @return array Validation result
     */
    private function validate_dash_placement($license_key) {
        $parts = explode('-', $license_key);

        if (count($parts) !== 5) {
            return array(
                'valid' => false,
                'message' => 'Invalid number of dash-separated parts'
            );
        }

        $expected_lengths = array(4, 4, 4, 4, 6);
        for ($i = 0; $i < count($parts); $i++) {
            if (strlen($parts[$i]) !== $expected_lengths[$i]) {
                return array(
                    'valid' => false,
                    'message' => "Invalid length for part " . ($i + 1) . ": expected {$expected_lengths[$i]}, got " . strlen($parts[$i])
                );
            }
        }

        return array('valid' => true, 'message' => 'Dash placement is valid');
    }

    /**
     * Create validation error response
     *
     * @param string $error_code Error code
     * @param string $error_message Error message
     * @param array $validation_result Current validation result
     * @param bool $detailed Whether to return detailed result
     * @return bool|array Error response
     */
    private function create_validation_error($error_code, $error_message, &$validation_result, $detailed) {
        $validation_result['error_code'] = $error_code;
        $validation_result['error_message'] = $error_message;
        $validation_result['format_checks'][$error_code] = false;

        $this->validation_stats['failed_validations']++;
        $this->update_error_stats($error_code);

        return $detailed ? $validation_result : false;
    }

    /**
     * Update pattern match statistics
     *
     * @param string $pattern_name Pattern name
     * @return void
     */
    private function update_pattern_stats($pattern_name) {
        if (!isset($this->validation_stats['pattern_matches'][$pattern_name])) {
            $this->validation_stats['pattern_matches'][$pattern_name] = 0;
        }
        $this->validation_stats['pattern_matches'][$pattern_name]++;
    }

    /**
     * Update error statistics
     *
     * @param string $error_code Error code
     * @return void
     */
    private function update_error_stats($error_code) {
        if (!isset($this->validation_stats['common_errors'][$error_code])) {
            $this->validation_stats['common_errors'][$error_code] = 0;
        }
        $this->validation_stats['common_errors'][$error_code]++;
    }

    /**
     * Check if license key matches specific pattern
     *
     * @param string $license_key License key to check
     * @param string $pattern_name Pattern name to check against
     * @return bool True if matches, false otherwise
     */
    public function matches_pattern($license_key, $pattern_name) {
        if ($pattern_name === 'vd_standard') {
            return preg_match($this->license_key_pattern, $license_key);
        }

        if (isset($this->alternative_patterns[$pattern_name])) {
            return preg_match($this->alternative_patterns[$pattern_name], $license_key);
        }

        return false;
    }

    /**
     * Get supported patterns
     *
     * @return array Array of supported patterns
     */
    public function get_supported_patterns() {
        $patterns = array(
            'vd_standard' => array(
                'name' => 'VD Standard Format',
                'pattern' => $this->license_key_pattern,
                'example' => 'ABCD-EFGH-IJKL-MNOP-QRSTUV',
                'description' => 'Standard VD License Manager format'
            )
        );

        foreach ($this->alternative_patterns as $name => $pattern) {
            $patterns[$name] = array(
                'name' => ucwords(str_replace('_', ' ', $name)),
                'pattern' => $pattern,
                'description' => 'Alternative format for compatibility'
            );
        }

        return $patterns;
    }

    /**
     * Validate multiple license keys in batch
     *
     * @param array $license_keys Array of license keys to validate
     * @param bool $detailed Whether to return detailed results
     * @return array Batch validation results
     */
    public function validate_batch($license_keys, $detailed = false) {
        $results = array(
            'total_keys' => count($license_keys),
            'valid_keys' => 0,
            'invalid_keys' => 0,
            'results' => array(),
            'processing_time' => 0
        );

        $start_time = microtime(true);

        foreach ($license_keys as $index => $license_key) {
            $result = $this->validate_license_key_format($license_key, $detailed);
            $results['results'][$index] = $result;

            if (($detailed && $result['valid']) || (!$detailed && $result)) {
                $results['valid_keys']++;
            } else {
                $results['invalid_keys']++;
            }
        }

        $results['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);

        return $results;
    }

    /**
     * Get validation statistics
     *
     * @return array Validation statistics
     */
    public function get_stats() {
        return $this->validation_stats;
    }

    /**
     * Reset validation statistics
     *
     * @return void
     */
    public function reset_stats() {
        $this->validation_stats = array(
            'total_validations' => 0,
            'successful_validations' => 0,
            'failed_validations' => 0,
            'pattern_matches' => array(),
            'common_errors' => array()
        );
    }

    /**
     * Get module information
     *
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => 'VD License Pattern Validator',
            'version' => '1.5.0-rc.1',
            'namespace' => 'VD\\LicenseManager\\Format',
            'description' => 'Handles license key pattern validation and format checking',
            'dependencies' => array(),
            'supported_patterns' => array_keys($this->get_supported_patterns()),
            'statistics' => $this->validation_stats
        );
    }
}
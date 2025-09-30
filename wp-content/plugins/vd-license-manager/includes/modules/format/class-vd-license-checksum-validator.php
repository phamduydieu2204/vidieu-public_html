<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Checksum Validator
 *
 * Handles license key checksum validation, integrity checking, and hash calculations
 * Extracted from main validator class for better modularity and extensibility
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 * @namespace VD\LicenseManager\Format
 */
class VD_License_Checksum_Validator {

    /**
     * Singleton instance
     *
     * @var VD_License_Checksum_Validator|null
     */
    private static $instance = null;

    /**
     * Pattern validator dependency
     *
     * @var VD_License_Pattern_Validator|null
     */
    private $pattern_validator = null;

    /**
     * Checksum algorithms configuration
     *
     * @var array
     */
    private $checksum_algorithms = array();

    /**
     * Validation statistics
     *
     * @var array
     */
    private $validation_stats = array(
        'total_validations' => 0,
        'successful_validations' => 0,
        'failed_validations' => 0,
        'algorithm_usage' => array(),
        'common_errors' => array()
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_algorithms();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Checksum_Validator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize checksum algorithms
     *
     * @return void
     */
    private function init_algorithms() {
        $this->checksum_algorithms = array(
            'basic_ascii' => array(
                'name' => 'Basic ASCII Sum',
                'description' => 'Simple sum of ASCII values with range validation',
                'min_length' => 8,
                'max_value' => 50000,
                'enabled' => true
            ),
            'modulo_prime' => array(
                'name' => 'Modulo Prime Validation',
                'description' => 'ASCII sum modulo prime number validation',
                'min_length' => 8,
                'prime_number' => 97,
                'enabled' => true
            ),
            'crc32_check' => array(
                'name' => 'CRC32 Checksum',
                'description' => 'CRC32 hash-based validation',
                'min_length' => 12,
                'enabled' => true
            ),
            'luhn_algorithm' => array(
                'name' => 'Luhn Algorithm',
                'description' => 'Modified Luhn algorithm for alphanumeric strings',
                'min_length' => 8,
                'enabled' => false // Disabled by default, can be enabled later
            )
        );
    }

    /**
     * Set pattern validator dependency
     *
     * @param VD_License_Pattern_Validator $pattern_validator Pattern validator instance
     * @return void
     */
    public function set_pattern_validator($pattern_validator) {
        $this->pattern_validator = $pattern_validator;
    }

    /**
     * Validate license key checksum with comprehensive validation
     *
     * @param string $license_key License key to validate
     * @param bool $detailed Whether to return detailed validation results
     * @return bool|array True/false for simple validation, array for detailed
     */
    public function validate_license_checksum($license_key, $detailed = false) {
        $this->validation_stats['total_validations']++;

        $validation_result = array(
            'valid' => false,
            'error_code' => null,
            'error_message' => null,
            'checksum_checks' => array(),
            'algorithm_used' => null,
            'checksum_value' => null,
            'validation_time' => 0
        );

        $start_time = microtime(true);

        try {
            // Input validation
            if (!is_string($license_key)) {
                return $this->create_checksum_error('invalid_type', 'License key must be a string', $validation_result, $detailed);
            }

            $license_key = sanitize_text_field(trim($license_key));

            if (empty($license_key)) {
                return $this->create_checksum_error('empty', 'License key cannot be empty', $validation_result, $detailed);
            }

            // Basic ASCII checksum validation (default algorithm)
            $basic_result = $this->validate_basic_ascii_checksum($license_key);
            $validation_result['checksum_checks']['basic_ascii'] = $basic_result;

            if ($basic_result['valid']) {
                $validation_result['algorithm_used'] = 'basic_ascii';
                $validation_result['checksum_value'] = $basic_result['checksum_value'];
                $this->update_algorithm_stats('basic_ascii');
            }

            // Modulo prime validation (secondary check)
            $modulo_result = $this->validate_modulo_prime_checksum($license_key);
            $validation_result['checksum_checks']['modulo_prime'] = $modulo_result;

            if (!$basic_result['valid'] && $modulo_result['valid']) {
                $validation_result['algorithm_used'] = 'modulo_prime';
                $validation_result['checksum_value'] = $modulo_result['checksum_value'];
                $this->update_algorithm_stats('modulo_prime');
            }

            // CRC32 validation for longer keys
            if (strlen($license_key) >= 12) {
                $crc32_result = $this->validate_crc32_checksum($license_key);
                $validation_result['checksum_checks']['crc32'] = $crc32_result;

                if (!$basic_result['valid'] && !$modulo_result['valid'] && $crc32_result['valid']) {
                    $validation_result['algorithm_used'] = 'crc32';
                    $validation_result['checksum_value'] = $crc32_result['checksum_value'];
                    $this->update_algorithm_stats('crc32');
                }
            }

            // Determine overall validity
            $validation_result['valid'] = $basic_result['valid'] || $modulo_result['valid'] ||
                                        (isset($crc32_result) && $crc32_result['valid']);

            if ($validation_result['valid']) {
                $this->validation_stats['successful_validations']++;
            } else {
                return $this->create_checksum_error('checksum_failed', 'License key checksum validation failed', $validation_result, $detailed);
            }

            return $detailed ? $validation_result : true;

        } catch (Exception $e) {
            return $this->create_checksum_error('validation_exception', 'Checksum validation failed: ' . $e->getMessage(), $validation_result, $detailed);
        } finally {
            $validation_result['validation_time'] = round((microtime(true) - $start_time) * 1000, 2);
        }
    }

    /**
     * Basic ASCII checksum validation
     *
     * @param string $license_key License key to validate
     * @return array Validation result
     */
    private function validate_basic_ascii_checksum($license_key) {
        $clean_key = str_replace('-', '', $license_key);

        if (strlen($clean_key) < 8) {
            return array(
                'valid' => true, // Skip for short keys
                'checksum_value' => 0,
                'message' => 'Skipped for short keys'
            );
        }

        $checksum = 0;
        for ($i = 0; $i < strlen($clean_key); $i++) {
            $checksum += ord($clean_key[$i]);
        }

        $valid = $checksum > 0 && $checksum < 50000;

        return array(
            'valid' => $valid,
            'checksum_value' => $checksum,
            'message' => $valid ? 'Basic ASCII checksum valid' : 'Basic ASCII checksum failed'
        );
    }

    /**
     * Modulo prime checksum validation
     *
     * @param string $license_key License key to validate
     * @return array Validation result
     */
    private function validate_modulo_prime_checksum($license_key) {
        $clean_key = str_replace('-', '', $license_key);

        if (strlen($clean_key) < 8) {
            return array(
                'valid' => true,
                'checksum_value' => 0,
                'message' => 'Skipped for short keys'
            );
        }

        $checksum = 0;
        for ($i = 0; $i < strlen($clean_key); $i++) {
            $checksum += ord($clean_key[$i]);
        }

        $prime = $this->checksum_algorithms['modulo_prime']['prime_number'];
        $valid = ($checksum % $prime) !== 0; // Should not be divisible by prime

        return array(
            'valid' => $valid,
            'checksum_value' => $checksum,
            'modulo_result' => $checksum % $prime,
            'message' => $valid ? 'Modulo prime checksum valid' : 'Modulo prime checksum failed'
        );
    }

    /**
     * CRC32 checksum validation
     *
     * @param string $license_key License key to validate
     * @return array Validation result
     */
    private function validate_crc32_checksum($license_key) {
        $clean_key = str_replace('-', '', $license_key);

        if (strlen($clean_key) < 12) {
            return array(
                'valid' => true,
                'checksum_value' => 0,
                'message' => 'Skipped for short keys'
            );
        }

        $crc32_value = crc32($clean_key);
        $valid = $crc32_value !== 0; // CRC32 should not be zero

        return array(
            'valid' => $valid,
            'checksum_value' => $crc32_value,
            'message' => $valid ? 'CRC32 checksum valid' : 'CRC32 checksum failed'
        );
    }

    /**
     * Calculate multiple checksums for a license key
     *
     * @param string $license_key License key to calculate checksums for
     * @return array Array of checksum calculations
     */
    public function calculate_checksums($license_key) {
        $checksums = array();

        $checksums['basic_ascii'] = $this->validate_basic_ascii_checksum($license_key);
        $checksums['modulo_prime'] = $this->validate_modulo_prime_checksum($license_key);

        if (strlen($license_key) >= 12) {
            $checksums['crc32'] = $this->validate_crc32_checksum($license_key);
        }

        return $checksums;
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
            $result = $this->validate_license_checksum($license_key, $detailed);
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
     * Create checksum validation error response
     *
     * @param string $error_code Error code
     * @param string $error_message Error message
     * @param array $validation_result Current validation result
     * @param bool $detailed Whether to return detailed result
     * @return bool|array Error response
     */
    private function create_checksum_error($error_code, $error_message, &$validation_result, $detailed) {
        $validation_result['error_code'] = $error_code;
        $validation_result['error_message'] = $error_message;
        $validation_result['checksum_checks'][$error_code] = false;

        $this->validation_stats['failed_validations']++;
        $this->update_error_stats($error_code);

        return $detailed ? $validation_result : false;
    }

    /**
     * Update algorithm usage statistics
     *
     * @param string $algorithm_name Algorithm name
     * @return void
     */
    private function update_algorithm_stats($algorithm_name) {
        if (!isset($this->validation_stats['algorithm_usage'][$algorithm_name])) {
            $this->validation_stats['algorithm_usage'][$algorithm_name] = 0;
        }
        $this->validation_stats['algorithm_usage'][$algorithm_name]++;
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
     * Get supported checksum algorithms
     *
     * @return array Array of supported algorithms
     */
    public function get_supported_algorithms() {
        return $this->checksum_algorithms;
    }

    /**
     * Enable or disable a checksum algorithm
     *
     * @param string $algorithm_name Algorithm name
     * @param bool $enabled Whether to enable the algorithm
     * @return bool True on success, false if algorithm not found
     */
    public function set_algorithm_enabled($algorithm_name, $enabled) {
        if (!isset($this->checksum_algorithms[$algorithm_name])) {
            return false;
        }

        $this->checksum_algorithms[$algorithm_name]['enabled'] = $enabled;
        return true;
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
            'algorithm_usage' => array(),
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
            'name' => 'VD License Checksum Validator',
            'version' => '1.5.0-rc.1',
            'namespace' => 'VD\\LicenseManager\\Format',
            'description' => 'Handles license key checksum validation and integrity checking',
            'dependencies' => array('format.pattern_validator'),
            'supported_algorithms' => array_keys($this->checksum_algorithms),
            'statistics' => $this->validation_stats
        );
    }
}
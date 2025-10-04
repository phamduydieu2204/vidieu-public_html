<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Sanitizer Component
 *
 * Extracted utility functions for data sanitization from VD_License_Validator.
 * Implements Micro-Step 2B.1.2 - Data Sanitizer Implementation.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.2
 */
class DataSanitizer implements DataSanitizerInterface {

    /**
     * Component version
     *
     * @var string
     */
    const VERSION = '2B.1.2';

    /**
     * Sanitize status value
     *
     * Extracted from class-vd-license-validator.php:5080
     * Original method: sanitize_status_value()
     *
     * @param string $status Status value to sanitize
     * @return string Sanitized status value
     */
    public static function sanitize_status_value($status) {
        return strtolower(trim((string) $status));
    }

    /**
     * Sanitize context data
     *
     * Extracted from class-vd-license-validator.php:5093
     * Original method: sanitize_context_data()
     *
     * @param array $context Context data to sanitize
     * @return array Sanitized context data
     */
    public static function sanitize_context_data($context) {
        if (!is_array($context)) {
            return array();
        }

        $sanitized = array();

        foreach ($context as $key => $value) {
            // Sanitize key
            $clean_key = sanitize_key($key);

            // Sanitize value based on type
            if (is_string($value)) {
                $sanitized[$clean_key] = sanitize_text_field($value);
            } elseif (is_numeric($value)) {
                $sanitized[$clean_key] = is_float($value) ? (float) $value : (int) $value;
            } elseif (is_bool($value)) {
                $sanitized[$clean_key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$clean_key] = self::sanitize_context_data($value); // Recursive sanitization
            } else {
                // Convert other types to string and sanitize
                $sanitized[$clean_key] = sanitize_text_field((string) $value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize query string
     *
     * Extracted from class-vd-license-validator.php:5909
     * Original method: sanitize_query_string()
     *
     * @param string $query_string Query string to sanitize
     * @return string Sanitized query string
     */
    public static function sanitize_query_string($query_string) {
        // Remove sensitive parameters
        $sensitive_params = array('password', 'token', 'key', 'secret', 'api_key', 'auth');

        parse_str($query_string, $params);

        foreach ($sensitive_params as $sensitive_param) {
            foreach ($params as $param_name => $param_value) {
                if (stripos($param_name, $sensitive_param) !== false) {
                    $params[$param_name] = '[FILTERED]';
                }
            }
        }

        return http_build_query($params);
    }

    /**
     * Get component status
     *
     * @return array Component status information
     */
    public static function get_status() {
        return array(
            'component' => 'DataSanitizer',
            'version' => self::VERSION,
            'methods' => array(
                'sanitize_status_value',
                'sanitize_context_data',
                'sanitize_query_string'
            ),
            'extracted_from' => 'class-vd-license-validator.php',
            'extraction_lines' => array(
                'sanitize_status_value' => 5080,
                'sanitize_context_data' => 5093,
                'sanitize_query_string' => 5909
            ),
            'ready' => true
        );
    }

    /**
     * Test all sanitization methods
     *
     * @return array Test results
     */
    public static function run_tests() {
        $results = array();

        // Test sanitize_status_value
        $test_status = "  ACTIVE  ";
        $sanitized_status = self::sanitize_status_value($test_status);
        $results['sanitize_status_value'] = array(
            'input' => $test_status,
            'output' => $sanitized_status,
            'expected' => 'active',
            'passed' => ($sanitized_status === 'active')
        );

        // Test sanitize_context_data
        $test_context = array(
            'user_id' => '123',
            'action' => '<script>alert("test")</script>',
            'nested' => array(
                'level2' => 'value2'
            )
        );
        $sanitized_context = self::sanitize_context_data($test_context);
        $results['sanitize_context_data'] = array(
            'input' => $test_context,
            'output' => $sanitized_context,
            'passed' => (
                isset($sanitized_context['user_id']) &&
                $sanitized_context['user_id'] === 123 &&
                isset($sanitized_context['action']) &&
                strpos($sanitized_context['action'], '<script>') === false
            )
        );

        // Test sanitize_query_string
        $test_query = 'user=john&password=secret123&action=login';
        $sanitized_query = self::sanitize_query_string($test_query);
        $results['sanitize_query_string'] = array(
            'input' => $test_query,
            'output' => $sanitized_query,
            'passed' => (strpos($sanitized_query, '[FILTERED]') !== false)
        );

        return $results;
    }
}
<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Sanitizer Interface
 *
 * Defines the contract for data sanitization functionality.
 * Will be implemented in Micro-Step 2B.1.3.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.0
 */
interface DataSanitizerInterface {

    /**
     * Sanitize status value
     *
     * @param string $value Status value to sanitize
     * @return string Sanitized status value
     */
    public static function sanitize_status_value($value);

    /**
     * Sanitize context data
     *
     * @param array $data Context data to sanitize
     * @return array Sanitized context data
     */
    public static function sanitize_context_data($data);

    /**
     * Sanitize query string
     *
     * @param string $query Query string to sanitize
     * @return string Sanitized query string
     */
    public static function sanitize_query_string($query);
}
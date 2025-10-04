<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Response Builder Interface
 *
 * Defines the contract for response structure creation functionality.
 * Will be implemented in Micro-Step 2B.1.4.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.0
 */
interface ResponseBuilderInterface {

    /**
     * Create success response
     *
     * @param array $data Response data
     * @param string $message Success message
     * @return array Success response structure
     */
    public static function create_success_response($data = array(), $message = '');

    /**
     * Create error response
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param array $data Additional error data
     * @return array Error response structure
     */
    public static function create_error_response($message, $code = 0, $data = array());

    /**
     * Create history record structure
     *
     * @param array $data History record data
     * @return array Structured history record
     */
    public static function create_history_record_structure($data);

    /**
     * Create statistics structure
     *
     * @param array $data Statistics data
     * @return array Structured statistics
     */
    public static function create_statistics_structure($data);
}
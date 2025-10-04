<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * DateTime Helper Interface
 *
 * Defines the contract for datetime utility functions extracted from VD_License_Validator.
 * Implements Micro-Step 2B.1.4 - DateTime Helper Implementation.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.4
 */
interface DateTimeHelperInterface {

    /**
     * Validate date format
     *
     * @param string $date Date string to validate
     * @return bool True if valid date format
     */
    public static function is_valid_date($date);

    /**
     * Calculate days until expiry
     *
     * @param string $expiry_date Expiry date string
     * @param string $current_date Current date (optional, defaults to now)
     * @return int Days until expiry (negative if expired)
     */
    public static function calculate_days_until_expiry($expiry_date, $current_date = null);

    /**
     * Format grace period cutoff
     *
     * @param int $grace_hours Grace period in hours
     * @param string $from_time Base time (optional, defaults to now)
     * @return string Formatted cutoff date
     */
    public static function format_grace_cutoff($grace_hours, $from_time = null);

    /**
     * Get component status
     *
     * @return array Component status information
     */
    public static function get_status();

    /**
     * Run component tests
     *
     * @return array Test results
     */
    public static function run_tests();
}
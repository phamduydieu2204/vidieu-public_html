<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculation Helper Interface
 *
 * Defines the contract for calculation utility functions extracted from VD_License_Validator.
 * Implements Micro-Step 2B.1.5 - Calculation Helper Implementation.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.5
 */
interface CalculationHelperInterface {

    /**
     * Calculate execution time in milliseconds
     *
     * @param float $start_time Start time from microtime(true)
     * @param float $end_time End time from microtime(true) (optional, defaults to now)
     * @return float Execution time in milliseconds
     */
    public static function calculate_execution_time_ms($start_time, $end_time = null);

    /**
     * Calculate percentage with rounding
     *
     * @param int|float $part Part value
     * @param int|float $total Total value
     * @param int $decimals Number of decimal places (default: 1)
     * @return float Calculated percentage
     */
    public static function calculate_percentage($part, $total, $decimals = 1);

    /**
     * Calculate validation completeness percentage
     *
     * @param array $validation_pipeline Validation stages completed
     * @param int $total_stages Total validation stages (default: 5)
     * @return string Formatted percentage string
     */
    public static function calculate_validation_completeness($validation_pipeline, $total_stages = 5);

    /**
     * Calculate average changes per day
     *
     * @param array $date_data Array of date-based data
     * @param int $total_days Total days in period
     * @return float Average changes per day
     */
    public static function calculate_average_per_day($date_data, $total_days);

    /**
     * Round to specified decimal places
     *
     * @param float $value Value to round
     * @param int $precision Number of decimal places
     * @return float Rounded value
     */
    public static function safe_round($value, $precision = 2);

    /**
     * Calculate days between timestamps
     *
     * @param int $timestamp1 First timestamp
     * @param int $timestamp2 Second timestamp
     * @return int Days difference (absolute value)
     */
    public static function calculate_days_difference($timestamp1, $timestamp2);

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
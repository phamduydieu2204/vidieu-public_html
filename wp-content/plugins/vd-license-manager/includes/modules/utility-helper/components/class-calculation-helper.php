<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculation Helper Component
 *
 * Extracted utility functions for mathematical calculations from VD_License_Validator.
 * Implements Micro-Step 2B.1.5 - Calculation Helper Implementation.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.5
 */
class CalculationHelper implements CalculationHelperInterface {

    /**
     * Component version
     *
     * @var string
     */
    const VERSION = '2B.1.5';

    /**
     * Calculate execution time in milliseconds
     *
     * Extracted from multiple locations in class-vd-license-validator.php
     * Original pattern: round(($end_time - $start_time) * 1000, 2)
     *
     * @param float $start_time Start time from microtime(true)
     * @param float $end_time End time from microtime(true) (optional, defaults to now)
     * @return float Execution time in milliseconds
     */
    public static function calculate_execution_time_ms($start_time, $end_time = null) {
        if ($end_time === null) {
            $end_time = microtime(true);
        }

        if (!is_numeric($start_time) || !is_numeric($end_time)) {
            return 0.0;
        }

        return round(($end_time - $start_time) * 1000, 2);
    }

    /**
     * Calculate percentage with rounding
     *
     * Helper method for percentage calculations throughout the validator
     *
     * @param int|float $part Part value
     * @param int|float $total Total value
     * @param int $decimals Number of decimal places (default: 1)
     * @return float Calculated percentage
     */
    public static function calculate_percentage($part, $total, $decimals = 1) {
        if (!is_numeric($part) || !is_numeric($total) || $total == 0) {
            return 0.0;
        }

        $percentage = ($part / $total) * 100;
        return round($percentage, $decimals);
    }

    /**
     * Calculate validation completeness percentage
     *
     * Extracted from class-vd-license-validator.php:7413
     * Original method: calculate_validation_completeness()
     *
     * @param array $validation_pipeline Validation stages completed
     * @param int $total_stages Total validation stages (default: 5)
     * @return string Formatted percentage string
     */
    public static function calculate_validation_completeness($validation_pipeline, $total_stages = 5) {
        if (!is_array($validation_pipeline) || !is_numeric($total_stages) || $total_stages <= 0) {
            return '0%';
        }

        $completed_stages = count($validation_pipeline);
        $percentage = self::calculate_percentage($completed_stages, $total_stages, 1);

        return $percentage . '%';
    }

    /**
     * Calculate average changes per day
     *
     * Extracted logic from class-vd-license-validator.php:3886
     * Original calculation: round($average_changes_per_day, 2)
     *
     * @param array $date_data Array of date-based data
     * @param int $total_days Total days in period
     * @return float Average changes per day
     */
    public static function calculate_average_per_day($date_data, $total_days) {
        if (!is_array($date_data) || !is_numeric($total_days) || $total_days <= 0) {
            return 0.0;
        }

        $total_changes = 0;
        foreach ($date_data as $date => $changes) {
            if (is_numeric($changes)) {
                $total_changes += $changes;
            } elseif (is_array($changes)) {
                $total_changes += count($changes);
            }
        }

        $average = $total_changes / $total_days;
        return round($average, 2);
    }

    /**
     * Round to specified decimal places with safe handling
     *
     * Enhanced version of round() with error handling
     * Used throughout validator for consistent rounding
     *
     * @param float $value Value to round
     * @param int $precision Number of decimal places
     * @return float Rounded value
     */
    public static function safe_round($value, $precision = 2) {
        if (!is_numeric($value)) {
            return 0.0;
        }

        if (!is_int($precision) || $precision < 0) {
            $precision = 2;
        }

        return round((float) $value, $precision);
    }

    /**
     * Calculate days difference between timestamps
     *
     * Enhanced calculation for date differences
     * Based on patterns from validator expiry calculations
     *
     * @param int $timestamp1 First timestamp
     * @param int $timestamp2 Second timestamp
     * @return int Days difference (absolute value)
     */
    public static function calculate_days_difference($timestamp1, $timestamp2) {
        if (!is_numeric($timestamp1) || !is_numeric($timestamp2)) {
            return 0;
        }

        $difference = abs($timestamp2 - $timestamp1);
        return (int) ceil($difference / (24 * 3600));
    }

    /**
     * Calculate growth rate percentage
     *
     * Helper method for statistics growth calculations
     *
     * @param float $old_value Previous value
     * @param float $new_value Current value
     * @param int $decimals Number of decimal places
     * @return float Growth rate percentage
     */
    public static function calculate_growth_rate($old_value, $new_value, $decimals = 1) {
        if (!is_numeric($old_value) || !is_numeric($new_value) || $old_value == 0) {
            return 0.0;
        }

        $growth = (($new_value - $old_value) / $old_value) * 100;
        return round($growth, $decimals);
    }

    /**
     * Calculate statistics totals from array
     *
     * Helper method for aggregating statistics data
     *
     * @param array $data Array of numeric values
     * @param string $key Optional key to extract from sub-arrays
     * @return int Total sum
     */
    public static function calculate_total_from_array($data, $key = null) {
        if (!is_array($data)) {
            return 0;
        }

        $total = 0;
        foreach ($data as $item) {
            if ($key !== null && is_array($item) && isset($item[$key])) {
                $value = $item[$key];
            } else {
                $value = $item;
            }

            if (is_numeric($value)) {
                $total += $value;
            }
        }

        return $total;
    }

    /**
     * Calculate batch processing progress
     *
     * Helper for batch operation progress tracking
     *
     * @param int $processed Number of items processed
     * @param int $total Total items to process
     * @param int $batch_size Size of each batch
     * @return array Progress information
     */
    public static function calculate_batch_progress($processed, $total, $batch_size) {
        if (!is_numeric($processed) || !is_numeric($total) || !is_numeric($batch_size)) {
            return array(
                'percentage' => 0.0,
                'batches_completed' => 0,
                'batches_remaining' => 0,
                'items_remaining' => 0
            );
        }

        $percentage = self::calculate_percentage($processed, $total, 1);
        $batches_completed = $batch_size > 0 ? ceil($processed / $batch_size) : 0;
        $total_batches = $batch_size > 0 ? ceil($total / $batch_size) : 0;
        $batches_remaining = max(0, $total_batches - $batches_completed);
        $items_remaining = max(0, $total - $processed);

        return array(
            'percentage' => $percentage,
            'batches_completed' => $batches_completed,
            'batches_remaining' => $batches_remaining,
            'total_batches' => $total_batches,
            'items_remaining' => $items_remaining
        );
    }

    /**
     * Get component status
     *
     * @return array Component status information
     */
    public static function get_status() {
        return array(
            'component' => 'CalculationHelper',
            'version' => self::VERSION,
            'methods' => array(
                'calculate_execution_time_ms',
                'calculate_percentage',
                'calculate_validation_completeness',
                'calculate_average_per_day',
                'safe_round',
                'calculate_days_difference',
                'calculate_growth_rate',
                'calculate_total_from_array',
                'calculate_batch_progress'
            ),
            'extracted_from' => 'class-vd-license-validator.php',
            'extraction_lines' => array(
                'execution_time_calculations' => array(701, 2338, 3898, 4846, 5281),
                'percentage_calculations' => array(7413, 3886),
                'validation_completeness' => 7413,
                'rounding_operations' => array(701, 2338, 3886, 3898, 4846, 5281),
                'days_calculations' => array(1123, 1128, 1569, 4093)
            ),
            'ready' => true
        );
    }

    /**
     * Test all calculation helper methods
     *
     * @return array Test results
     */
    public static function run_tests() {
        $results = array();

        // Test calculate_execution_time_ms
        $start = microtime(true);
        usleep(1000); // 1ms
        $exec_time = self::calculate_execution_time_ms($start);
        $results['calculate_execution_time_ms'] = array(
            'input' => 'microtime test',
            'output' => $exec_time,
            'passed' => (
                is_float($exec_time) &&
                $exec_time > 0 &&
                $exec_time < 1000 // Should be less than 1 second
            )
        );

        // Test calculate_percentage
        $percentage = self::calculate_percentage(25, 100, 1);
        $results['calculate_percentage'] = array(
            'input' => array('part' => 25, 'total' => 100),
            'output' => $percentage,
            'passed' => ($percentage === 25.0)
        );

        // Test calculate_validation_completeness
        $pipeline = array('stage1', 'stage2', 'stage3');
        $completeness = self::calculate_validation_completeness($pipeline, 5);
        $results['calculate_validation_completeness'] = array(
            'input' => array('pipeline' => $pipeline, 'total' => 5),
            'output' => $completeness,
            'passed' => ($completeness === '60%')
        );

        // Test calculate_average_per_day
        $date_data = array(
            '2024-01-01' => 10,
            '2024-01-02' => 15,
            '2024-01-03' => 5
        );
        $average = self::calculate_average_per_day($date_data, 3);
        $results['calculate_average_per_day'] = array(
            'input' => array('data' => $date_data, 'days' => 3),
            'output' => $average,
            'passed' => ($average === 10.0) // (10+15+5)/3 = 10
        );

        // Test safe_round
        $rounded = self::safe_round(3.14159, 2);
        $results['safe_round'] = array(
            'input' => array('value' => 3.14159, 'precision' => 2),
            'output' => $rounded,
            'passed' => ($rounded === 3.14)
        );

        // Test calculate_days_difference
        $timestamp1 = strtotime('2024-01-01');
        $timestamp2 = strtotime('2024-01-11');
        $days_diff = self::calculate_days_difference($timestamp1, $timestamp2);
        $results['calculate_days_difference'] = array(
            'input' => array('ts1' => '2024-01-01', 'ts2' => '2024-01-11'),
            'output' => $days_diff,
            'passed' => ($days_diff === 10)
        );

        // Test calculate_growth_rate
        $growth = self::calculate_growth_rate(100, 120, 1);
        $results['calculate_growth_rate'] = array(
            'input' => array('old' => 100, 'new' => 120),
            'output' => $growth,
            'passed' => ($growth === 20.0) // 20% growth
        );

        // Test calculate_batch_progress
        $progress = self::calculate_batch_progress(150, 200, 50);
        $results['calculate_batch_progress'] = array(
            'input' => array('processed' => 150, 'total' => 200, 'batch' => 50),
            'output' => $progress,
            'passed' => (
                isset($progress['percentage']) && abs($progress['percentage'] - 75.0) < 0.001 &&
                isset($progress['batches_completed']) && $progress['batches_completed'] === 3 &&
                isset($progress['items_remaining']) && $progress['items_remaining'] === 50 &&
                isset($progress['batches_remaining']) && $progress['batches_remaining'] === 1 &&
                isset($progress['total_batches']) && $progress['total_batches'] === 4
            )
        );

        // Test calculate_total_from_array
        $test_array = array(10, 20, 30, 40);
        $total = self::calculate_total_from_array($test_array);
        $results['calculate_total_from_array'] = array(
            'input' => $test_array,
            'output' => $total,
            'passed' => ($total === 100)
        );

        return $results;
    }
}
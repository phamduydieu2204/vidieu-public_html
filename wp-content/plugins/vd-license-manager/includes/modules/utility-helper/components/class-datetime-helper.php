<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * DateTime Helper Component
 *
 * Extracted utility functions for datetime operations from VD_License_Validator.
 * Implements Micro-Step 2B.1.4 - DateTime Helper Implementation.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.4
 */
class DateTimeHelper implements DateTimeHelperInterface {

    /**
     * Component version
     *
     * @var string
     */
    const VERSION = '2B.1.4';

    /**
     * Validate date format
     *
     * Extracted from class-vd-license-validator.php:4038
     * Original method: is_valid_date()
     *
     * @param string $date Date string to validate
     * @return bool True if valid date format
     */
    public static function is_valid_date($date) {
        if (empty($date) || !is_string($date)) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Calculate days until expiry
     *
     * Extracted from class-vd-license-validator.php:1094
     * Original calculation: ceil(($expiry_timestamp - $current_timestamp) / (24 * 3600))
     *
     * @param string $expiry_date Expiry date string
     * @param string $current_date Current date (optional, defaults to now)
     * @return int Days until expiry (negative if expired)
     */
    public static function calculate_days_until_expiry($expiry_date, $current_date = null) {
        if (empty($expiry_date)) {
            return 0;
        }

        // Handle null expiry (lifetime license)
        if ($expiry_date === '0000-00-00 00:00:00' || $expiry_date === null) {
            return PHP_INT_MAX; // Effectively infinite
        }

        $expiry_timestamp = strtotime($expiry_date);
        if ($expiry_timestamp === false) {
            return 0;
        }

        $current_timestamp = $current_date ? strtotime($current_date) : current_time('timestamp');
        if ($current_timestamp === false) {
            $current_timestamp = current_time('timestamp');
        }

        return ceil(($expiry_timestamp - $current_timestamp) / (24 * 3600));
    }

    /**
     * Format grace period cutoff
     *
     * Extracted from class-vd-license-validator.php:1355
     * Original calculation: date('Y-m-d H:i:s', current_time('timestamp') - ($grace_period_hours * 3600))
     *
     * @param int $grace_hours Grace period in hours
     * @param string $from_time Base time (optional, defaults to now)
     * @return string Formatted cutoff date
     */
    public static function format_grace_cutoff($grace_hours, $from_time = null) {
        $grace_hours = (int) $grace_hours;
        if ($grace_hours < 0) {
            $grace_hours = 0;
        }

        $base_timestamp = $from_time ? strtotime($from_time) : current_time('timestamp');
        if ($base_timestamp === false) {
            $base_timestamp = current_time('timestamp');
        }

        $cutoff_timestamp = $base_timestamp - ($grace_hours * 3600);
        return date('Y-m-d H:i:s', $cutoff_timestamp);
    }

    /**
     * Check if date is within expiry warning period
     *
     * Extracted logic from class-vd-license-validator.php:1097
     * Original check: $days_until_expiry <= 7
     *
     * @param string $expiry_date Expiry date string
     * @param int $warning_days Warning threshold in days (default: 7)
     * @return bool True if within warning period
     */
    public static function is_within_expiry_warning($expiry_date, $warning_days = 7) {
        $days_until = self::calculate_days_until_expiry($expiry_date);

        // Already expired
        if ($days_until < 0) {
            return false;
        }

        return $days_until <= $warning_days;
    }

    /**
     * Calculate days since expiry
     *
     * Helper method for expired license processing
     * Based on logic from class-vd-license-validator.php:1535
     *
     * @param string $expiry_date Expiry date string
     * @param string $current_date Current date (optional, defaults to now)
     * @return int Days since expiry (0 if not expired)
     */
    public static function calculate_days_since_expiry($expiry_date, $current_date = null) {
        $days_until = self::calculate_days_until_expiry($expiry_date, $current_date);

        // Not expired yet
        if ($days_until >= 0) {
            return 0;
        }

        return abs($days_until);
    }

    /**
     * Format execution time in milliseconds
     *
     * Helper for performance monitoring
     * Based on pattern from class-vd-license-validator.php:667
     *
     * @param float $start_time Start time from microtime(true)
     * @param float $end_time End time from microtime(true) (optional, defaults to now)
     * @return float Execution time in milliseconds
     */
    public static function calculate_execution_time_ms($start_time, $end_time = null) {
        if ($end_time === null) {
            $end_time = microtime(true);
        }

        return round(($end_time - $start_time) * 1000, 2);
    }

    /**
     * Get component status
     *
     * @return array Component status information
     */
    public static function get_status() {
        return array(
            'component' => 'DateTimeHelper',
            'version' => self::VERSION,
            'methods' => array(
                'is_valid_date',
                'calculate_days_until_expiry',
                'format_grace_cutoff',
                'is_within_expiry_warning',
                'calculate_days_since_expiry',
                'calculate_execution_time_ms'
            ),
            'extracted_from' => 'class-vd-license-validator.php',
            'extraction_lines' => array(
                'is_valid_date' => 4038,
                'calculate_days_until_expiry' => 1094,
                'format_grace_cutoff' => 1355,
                'expiry_warning_logic' => 1097,
                'days_since_expiry_logic' => 1535,
                'execution_time_logic' => 667
            ),
            'ready' => true
        );
    }

    /**
     * Test all datetime helper methods
     *
     * @return array Test results
     */
    public static function run_tests() {
        $results = array();

        // Test is_valid_date
        $results['is_valid_date'] = array(
            'input' => array('2024-12-31', '2024-13-01', 'invalid', ''),
            'output' => array(
                '2024-12-31' => self::is_valid_date('2024-12-31'),
                '2024-13-01' => self::is_valid_date('2024-13-01'),
                'invalid' => self::is_valid_date('invalid'),
                'empty' => self::is_valid_date('')
            ),
            'passed' => (
                self::is_valid_date('2024-12-31') === true &&
                self::is_valid_date('2024-13-01') === false &&
                self::is_valid_date('invalid') === false &&
                self::is_valid_date('') === false
            )
        );

        // Test calculate_days_until_expiry
        $future_date = date('Y-m-d H:i:s', strtotime('+10 days'));
        $past_date = date('Y-m-d H:i:s', strtotime('-5 days'));
        $results['calculate_days_until_expiry'] = array(
            'input' => array('future' => $future_date, 'past' => $past_date),
            'output' => array(
                'future' => self::calculate_days_until_expiry($future_date),
                'past' => self::calculate_days_until_expiry($past_date),
                'lifetime' => self::calculate_days_until_expiry('0000-00-00 00:00:00')
            ),
            'passed' => (
                self::calculate_days_until_expiry($future_date) > 0 &&
                self::calculate_days_until_expiry($past_date) < 0 &&
                self::calculate_days_until_expiry('0000-00-00 00:00:00') === PHP_INT_MAX
            )
        );

        // Test format_grace_cutoff
        $test_cutoff = self::format_grace_cutoff(24);
        $results['format_grace_cutoff'] = array(
            'input' => 24,
            'output' => $test_cutoff,
            'passed' => (
                is_string($test_cutoff) &&
                strlen($test_cutoff) === 19 && // Y-m-d H:i:s format
                strtotime($test_cutoff) !== false
            )
        );

        // Test is_within_expiry_warning
        $warning_date = date('Y-m-d H:i:s', strtotime('+5 days'));
        $safe_date = date('Y-m-d H:i:s', strtotime('+15 days'));
        $results['is_within_expiry_warning'] = array(
            'input' => array('warning' => $warning_date, 'safe' => $safe_date),
            'output' => array(
                'warning' => self::is_within_expiry_warning($warning_date),
                'safe' => self::is_within_expiry_warning($safe_date)
            ),
            'passed' => (
                self::is_within_expiry_warning($warning_date) === true &&
                self::is_within_expiry_warning($safe_date) === false
            )
        );

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

        return $results;
    }
}
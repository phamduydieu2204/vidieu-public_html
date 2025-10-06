<?php
/**
 * DateTime helper functions for VD License Manager
 *
 * Provides utilities for date/time formatting and calculations
 *
 * @package VD_License_Manager
 * @subpackage Helpers
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD DateTime class
 *
 * Static helper methods for datetime operations
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_DateTime {

    /**
     * Convert datetime to relative time string
     *
     * @since 1.0.0
     * @param string $datetime MySQL datetime string
     * @return string Relative time string
     */
    public static function relative_time($datetime) {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return '';
        }

        $now = current_time('timestamp');
        $time = strtotime($datetime);

        if ($time === false) {
            return '';
        }

        $diff = abs($now - $time);
        $is_past = $time <= $now;
        $is_vietnamese = strpos(get_locale(), 'vi') === 0;

        if ($diff < 60) {
            return $is_vietnamese ? 'vừa xong' : 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $is_vietnamese ? $mins . ' phút trước' : $mins . ' minutes ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $is_vietnamese ? $hours . ' giờ trước' : $hours . ' hours ago';
        } else {
            $days = floor($diff / 86400);
            return $is_vietnamese ? $days . ' ngày trước' : $days . ' days ago';
        }
    }

    /**
     * Format datetime according to locale and timezone
     *
     * @since 1.0.0
     * @param string $datetime MySQL datetime string
     * @param string $format Format type ('default', 'date', 'time', 'long')
     * @return string Formatted datetime string
     */
    public static function format_datetime($datetime, $format = 'default') {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return '';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '';
        }

        $formats = array(
            'default' => 'd/m/Y H:i',
            'date' => 'd/m/Y',
            'time' => 'H:i',
            'long' => 'd/m/Y H:i:s'
        );

        $date_format = $formats[$format] ?? $formats['default'];

        return wp_date($date_format, $timestamp);
    }

    /**
     * Check if action is still within cooldown period
     *
     * @since 1.0.0
     * @param string $last_action_time MySQL datetime of last action
     * @param int $cooldown_seconds Cooldown period in seconds
     * @return array Cooldown status information
     */
    public static function is_within_cooldown($last_action_time, $cooldown_seconds) {
        if (!$last_action_time || $cooldown_seconds <= 0) {
            return array('ok' => true, 'ends_at' => null, 'remaining_seconds' => 0);
        }

        $last_action = strtotime($last_action_time);
        if ($last_action === false) {
            return array('ok' => true, 'ends_at' => null, 'remaining_seconds' => 0);
        }

        $now = current_time('timestamp');
        $cooldown_end = $last_action + $cooldown_seconds;
        $remaining = max(0, $cooldown_end - $now);

        return array(
            'ok' => $remaining === 0,
            'ends_at' => $remaining > 0 ? date('Y-m-d H:i:s', $cooldown_end) : null,
            'remaining_seconds' => $remaining
        );
    }

    /**
     * Calculate days remaining until datetime
     *
     * @since 1.0.0
     * @param string $datetime MySQL datetime string
     * @return int Days remaining (negative if past)
     */
    public static function days_until($datetime) {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return 0;
        }

        $target = strtotime($datetime);
        if ($target === false) {
            return 0;
        }

        return (int) ceil(($target - current_time('timestamp')) / 86400);
    }

    /**
     * Check if datetime has expired (passed)
     *
     * @since 1.0.0
     * @param string $datetime MySQL datetime string
     * @return bool True if expired
     */
    public static function is_expired($datetime) {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return true;
        }

        $timestamp = strtotime($datetime);
        return $timestamp === false || $timestamp <= current_time('timestamp');
    }

}
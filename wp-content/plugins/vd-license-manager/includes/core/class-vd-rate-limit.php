<?php
/**
 * Rate limiting logic for VD License Manager
 *
 * Implements sliding window rate limiting with transient caching
 *
 * @package VD_License_Manager
 * @subpackage Core
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Rate Limit class
 *
 * Manages rate limiting for license access using sliding window algorithm
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Rate_Limit {

    /**
     * Check if license has exceeded rate limit
     *
     * Uses sliding window algorithm to count hits in time window
     *
     * @since 1.0.0
     * @param int $license_id License ID to check
     * @return array Rate limit status information
     */
    public static function check($license_id) {
        global $wpdb;

        if (!is_numeric($license_id)) {
            return array(
                'ok' => false,
                'remaining' => 0,
                'retry_after' => 0,
                'reset_at' => ''
            );
        }

        $config = self::get_config($license_id);
        $window_start = current_time('mysql', true);
        $window_start_timestamp = strtotime($window_start) - $config['window_seconds'];

        // Count hits in current window from fetch log
        $hit_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}vd_account_fetch_log
             WHERE license_id = %d AND accessed_at >= %s",
            $license_id,
            date('Y-m-d H:i:s', $window_start_timestamp)
        ));

        $remaining = max(0, $config['max_hits'] - $hit_count);
        $is_allowed = $hit_count < $config['max_hits'];

        return array(
            'ok' => $is_allowed,
            'remaining' => $remaining,
            'retry_after' => $is_allowed ? 0 : $config['window_seconds'],
            'reset_at' => date('Y-m-d H:i:s', time() + $config['window_seconds'])
        );
    }

    /**
     * Increment rate limit counter for license
     *
     * Records hit timestamp using transient for performance
     *
     * @since 1.0.0
     * @param int $license_id License ID to increment
     * @return bool True on success
     */
    public static function increment($license_id) {
        if (!is_numeric($license_id)) {
            return false;
        }

        $config = self::get_config($license_id);
        $current_window = floor(time() / $config['window_seconds']);
        $transient_key = "vd_rate_{$license_id}_{$current_window}";

        // Get current count
        $current_count = (int) get_transient($transient_key);

        // Increment counter
        $new_count = $current_count + 1;

        // Store with expiration matching window duration
        $expiration = $config['window_seconds'] + 60; // Extra 60 seconds buffer
        set_transient($transient_key, $new_count, $expiration);

        // Clean up old transients (previous window)
        $prev_window = $current_window - 1;
        $prev_key = "vd_rate_{$license_id}_{$prev_window}";
        delete_transient($prev_key);

        return true;
    }

    /**
     * Reset rate limit counter for license
     *
     * Clears all transient counters for the license
     *
     * @since 1.0.0
     * @param int $license_id License ID to reset
     * @return bool True on success
     */
    public static function reset($license_id) {
        if (!is_numeric($license_id)) {
            return false;
        }

        $config = self::get_config($license_id);
        $current_window = floor(time() / $config['window_seconds']);

        // Delete current and previous window transients
        for ($i = 0; $i <= 2; $i++) {
            $window = $current_window - $i;
            $transient_key = "vd_rate_{$license_id}_{$window}";
            delete_transient($transient_key);
        }

        return true;
    }

    /**
     * Get rate limit configuration for license
     *
     * Retrieves config from database or returns defaults
     *
     * @since 1.0.0
     * @param int $license_id License ID to get config for
     * @return array Configuration array with window_seconds and max_hits
     */
    public static function get_config($license_id) {
        global $wpdb;

        if (!is_numeric($license_id)) {
            return array(
                'window_seconds' => VD_RATE_LIMIT_WINDOW,
                'max_hits' => VD_RATE_LIMIT_MAX_HITS
            );
        }

        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT window_seconds, max_hits
             FROM {$wpdb->prefix}vd_license_rate_limits
             WHERE license_id = %d",
            $license_id
        ));

        if (!$config) {
            return array(
                'window_seconds' => VD_RATE_LIMIT_WINDOW,
                'max_hits' => VD_RATE_LIMIT_MAX_HITS
            );
        }

        return array(
            'window_seconds' => (int) $config->window_seconds,
            'max_hits' => (int) $config->max_hits
        );
    }

    /**
     * Set rate limit configuration for license
     *
     * Creates or updates rate limit settings in database
     *
     * @since 1.0.0
     * @param int $license_id License ID to configure
     * @param int $window_seconds Time window in seconds
     * @param int $max_hits Maximum hits allowed in window
     * @return bool True on success
     */
    public static function set_config($license_id, $window_seconds, $max_hits) {
        global $wpdb;

        if (!is_numeric($license_id) || !is_numeric($window_seconds) || !is_numeric($max_hits)) {
            return false;
        }

        if ($window_seconds < 60 || $max_hits < 1) {
            return false;
        }

        $table_name = $wpdb->prefix . 'vd_license_rate_limits';

        $result = $wpdb->replace(
            $table_name,
            array(
                'license_id' => (int) $license_id,
                'window_seconds' => (int) $window_seconds,
                'max_hits' => (int) $max_hits,
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s')
        );

        return $result !== false;
    }
}
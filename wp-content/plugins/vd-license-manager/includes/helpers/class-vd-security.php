<?php
/**
 * Security helper functions for VD License Manager
 *
 * Provides security utilities for fingerprinting, validation, and data protection
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
 * VD Security class
 *
 * Static helper methods for security operations
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Security {

    /**
     * Generate fingerprint hash from device data
     *
     * Creates a consistent SHA-256 hash from device fingerprint data
     *
     * @since 1.0.0
     * @param array $data Fingerprint data (ua, os, browser, screen, etc.)
     * @return string|false 64-character hex string or false on error
     */
    public static function generate_fingerprint_hash($data) {
        if (!is_array($data) || empty($data)) {
            return false;
        }

        // Normalize and sort data for consistent hashing
        $normalized = array();
        foreach ($data as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $normalized[sanitize_key($key)] = sanitize_text_field($value);
            }
        }

        if (empty($normalized)) {
            return false;
        }

        ksort($normalized);
        $fingerprint_string = json_encode($normalized) . VD_FINGERPRINT_SALT;

        return hash('sha256', $fingerprint_string);
    }

    /**
     * Sanitize and validate license key
     *
     * @since 1.0.0
     * @param string $key License key to validate
     * @return string|false Sanitized license key or false if invalid
     */
    public static function sanitize_license_key($key) {
        if (!is_string($key)) {
            return false;
        }

        // Remove whitespace and convert to uppercase
        $key = strtoupper(trim($key));

        // Basic license key format validation (alphanumeric + hyphens)
        if (!preg_match('/^[A-Z0-9\-]+$/', $key)) {
            return false;
        }

        // Check minimum length (should be at least 10 characters)
        if (strlen($key) < 10) {
            return false;
        }

        return sanitize_text_field($key);
    }

    /**
     * Sanitize cookie string for storage
     *
     * @since 1.0.0
     * @param string $cookie_string Cookie data to sanitize
     * @return string|false Sanitized cookie string or false if invalid
     */
    public static function sanitize_cookie($cookie_string) {
        if (!is_string($cookie_string) || empty(trim($cookie_string))) {
            return false;
        }

        $cookie_string = trim($cookie_string);

        // Check if it's JSON format
        if (self::is_json($cookie_string)) {
            $decoded = json_decode($cookie_string, true);
            if (is_array($decoded)) {
                return wp_json_encode($decoded);
            }
        }

        // For Netscape format or other formats, basic sanitization
        return sanitize_textarea_field($cookie_string);
    }

    /**
     * Validate fingerprint hash format
     *
     * @since 1.0.0
     * @param string $fp Fingerprint hash to validate
     * @return bool True if valid 64-character hex string
     */
    public static function validate_fingerprint($fp) {
        if (!is_string($fp)) {
            return false;
        }

        // Check if it's exactly 64 characters and all hex
        return preg_match('/^[a-f0-9]{64}$/', $fp) === 1;
    }

    /**
     * Get real client IP address
     *
     * Handles various proxy configurations including Cloudflare
     *
     * @since 1.0.0
     * @return string|false Client IP address or false if unable to determine
     */
    public static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);

                // Handle comma-separated IPs (get first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }

                // Allow private IPs for development
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return false;
    }

    /**
     * Hash IP address for privacy compliance
     *
     * @since 1.0.0
     * @param string $ip IP address to hash
     * @param int $days_old Number of days old the IP is
     * @return string Original IP or hashed IP based on age
     */
    public static function hash_ip_for_privacy($ip, $days_old) {
        if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Hash IPs older than 90 days for GDPR compliance
        $privacy_threshold = apply_filters('vd_ip_privacy_threshold_days', 90);

        if ($days_old > $privacy_threshold) {
            return hash('sha256', $ip . VD_FINGERPRINT_SALT);
        }

        return $ip;
    }

    /**
     * Check if string is valid JSON
     *
     * @since 1.0.0
     * @param string $string String to check
     * @return bool True if valid JSON
     */
    private static function is_json($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Generate secure random token
     *
     * @since 1.0.0
     * @param int $length Token length (default 32)
     * @return string Random token
     */
    public static function generate_token($length = 32) {
        if (function_exists('wp_generate_password')) {
            return wp_generate_password($length, false, false);
        }

        return substr(hash('sha256', uniqid(mt_rand(), true)), 0, $length);
    }
}
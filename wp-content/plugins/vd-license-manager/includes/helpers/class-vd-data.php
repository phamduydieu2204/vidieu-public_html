<?php
/**
 * Data processing helper functions for VD License Manager
 *
 * Provides utilities for data parsing, formatting, and processing
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
 * VD Data class
 *
 * Static helper methods for data processing operations
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Data {

    /**
     * Parse user agent string to extract device information
     *
     * @since 1.0.0
     * @param string $ua User agent string
     * @return array Device information array
     */
    public static function parse_user_agent($ua) {
        if (!is_string($ua) || empty($ua)) {
            return array('os' => '', 'browser' => '', 'device' => '');
        }

        $ua = sanitize_text_field($ua);
        $result = array('os' => '', 'browser' => '', 'device' => '');

        // Detect OS
        if (preg_match('/Windows NT/', $ua)) {
            $result['os'] = 'Windows';
        } elseif (preg_match('/Mac OS X/', $ua)) {
            $result['os'] = 'macOS';
        } elseif (preg_match('/Android/', $ua)) {
            $result['os'] = 'Android';
        } elseif (preg_match('/iPhone OS/', $ua)) {
            $result['os'] = 'iOS';
        } elseif (preg_match('/Linux/', $ua)) {
            $result['os'] = 'Linux';
        }

        // Detect Browser
        if (preg_match('/Chrome/', $ua)) {
            $result['browser'] = 'Chrome';
        } elseif (preg_match('/Firefox/', $ua)) {
            $result['browser'] = 'Firefox';
        } elseif (preg_match('/Safari/', $ua) && !preg_match('/Chrome/', $ua)) {
            $result['browser'] = 'Safari';
        } elseif (preg_match('/Edge/', $ua)) {
            $result['browser'] = 'Edge';
        }

        // Detect Device
        if (preg_match('/iPhone|iPad/', $ua)) {
            $result['device'] = 'Mobile';
        } elseif (preg_match('/Android/', $ua)) {
            $result['device'] = 'Mobile';
        } else {
            $result['device'] = 'Desktop';
        }

        return $result;
    }

    /**
     * Get IP geolocation information
     *
     * @since 1.0.0
     * @param string $ip IP address to lookup
     * @return array|false Geolocation data or false on failure
     */
    public static function get_ip_geolocation($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        // Check cache first
        $cache_key = 'vd_geo_' . md5($ip);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Call IP API
        $api_url = 'http://ip-api.com/json/' . $ip . '?fields=status,country,countryCode,city';
        $response = wp_remote_get($api_url, array(
            'timeout' => 5,
            'user-agent' => 'VD License Manager/1.0'
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data) || $data['status'] !== 'success') {
            return false;
        }

        $geo_data = array(
            'country' => sanitize_text_field($data['country'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'countryCode' => sanitize_text_field($data['countryCode'] ?? '')
        );

        // Cache for 7 days
        set_transient($cache_key, $geo_data, VD_GEO_CACHE_DURATION);

        return $geo_data;
    }

    /**
     * Convert cookie between different formats
     *
     * @since 1.0.0
     * @param string $cookie Cookie data
     * @param string $from_format Source format (json|netscape|headers)
     * @param string $to_format Target format (json|netscape|headers)
     * @return string|false Converted cookie or false on failure
     */
    public static function format_cookie($cookie, $from_format, $to_format) {
        if (!is_string($cookie) || empty($cookie)) {
            return false;
        }

        if ($from_format === $to_format) {
            return $cookie;
        }

        // For JSON format
        if ($from_format === 'json' && $to_format === 'netscape') {
            $decoded = json_decode($cookie, true);
            return is_array($decoded) ? serialize($decoded) : false;
        }

        if ($from_format === 'netscape' && $to_format === 'json') {
            return wp_json_encode(array('raw' => $cookie));
        }

        // Default: return as-is for other conversions
        return $cookie;
    }

    /**
     * Generate TOTP code from secret
     *
     * @since 1.0.0
     * @param string $secret Base32 encoded secret
     * @return array|false TOTP data or false on failure
     */
    public static function generate_totp_code($secret) {
        if (!is_string($secret) || empty($secret)) {
            return false;
        }

        $secret = strtoupper(str_replace(' ', '', $secret));
        $binary_secret = self::base32_decode($secret);
        if ($binary_secret === false) {
            return false;
        }

        $time = floor(time() / 30);
        $time_bytes = pack('N*', 0) . pack('N*', $time);
        $hash = hash_hmac('sha1', $time_bytes, $binary_secret, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return array(
            'code' => sprintf('%06d', $code),
            'expires_in' => 30 - (time() % 30)
        );
    }

    /**
     * Format bytes into human readable string
     *
     * @since 1.0.0
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    public static function format_bytes($bytes) {
        if (!is_numeric($bytes) || $bytes < 0) {
            return '0 B';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = (int) $bytes;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }


    /**
     * Decode base32 string
     *
     * @since 1.0.0
     * @param string $input Base32 encoded string
     * @return string|false Decoded binary data or false
     */
    private static function base32_decode($input) {
        if (empty($input)) {
            return false;
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper($input);
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $c = $input[$i];
            if ($c === '=') {
                break;
            }
            $pos = strpos($alphabet, $c);
            if ($pos === false) {
                return false;
            }
            $v = ($v << 5) | $pos;
            $vbits += 5;
            if ($vbits >= 8) {
                $output .= chr(($v >> ($vbits - 8)) & 255);
                $vbits -= 8;
            }
        }

        return $output;
    }
}
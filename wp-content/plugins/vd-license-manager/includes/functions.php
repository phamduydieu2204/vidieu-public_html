<?php
/**
 * VD License Manager utility functions
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin option with default value
 *
 * @param string $option_name Option name
 * @param mixed $default Default value
 * @return mixed Option value or default
 */
function vd_get_option($option_name, $default = false) {
    return get_option('vd_license_manager_' . $option_name, $default);
}

/**
 * Update plugin option
 *
 * @param string $option_name Option name
 * @param mixed $value Option value
 * @return bool True on success, false on failure
 */
function vd_update_option($option_name, $value) {
    return update_option('vd_license_manager_' . $option_name, $value);
}

/**
 * Delete plugin option
 *
 * @param string $option_name Option name
 * @return bool True on success, false on failure
 */
function vd_delete_option($option_name) {
    return delete_option('vd_license_manager_' . $option_name);
}

/**
 * Check if current user has specific VD capability
 *
 * @param string $capability Capability to check
 * @return bool True if user has capability, false otherwise
 */
function vd_current_user_can($capability) {
    return current_user_can('vd_' . $capability);
}

/**
 * Validate license key format
 *
 * @param string $license_key License key to validate
 * @return bool True if valid format, false otherwise
 */
function vd_validate_license_key_format($license_key) {
    // Allow various formats for flexibility
    if (empty($license_key) || !is_string($license_key)) {
        return false;
    }

    // Max length check
    if (strlen($license_key) > 64) {
        return false;
    }

    // Basic format validation (alphanumeric with dashes)
    return preg_match('/^[A-Za-z0-9\-]+$/', $license_key);
}

/**
 * Validate device fingerprint format
 *
 * @param string $device_fp Device fingerprint to validate
 * @return bool True if valid format, false otherwise
 */
function vd_validate_device_fingerprint($device_fp) {
    // Should be 64-character hex string (SHA-256)
    return is_string($device_fp) && preg_match('/^[a-f0-9]{64}$/i', $device_fp);
}

/**
 * Get client IP address
 *
 * @return string Client IP address
 */
function vd_get_client_ip() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);

            // Validate IP and ensure it's not private/reserved
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Sanitize array recursively
 *
 * @param array $array Array to sanitize
 * @return array Sanitized array
 */
function vd_sanitize_array($array) {
    if (!is_array($array)) {
        return [];
    }

    $sanitized = [];
    foreach ($array as $key => $value) {
        $key = sanitize_key($key);

        if (is_array($value)) {
            $sanitized[$key] = vd_sanitize_array($value);
        } else {
            $sanitized[$key] = sanitize_text_field($value);
        }
    }

    return $sanitized;
}

/**
 * Log debug message if debug mode is enabled
 *
 * @param string $message Debug message
 * @param mixed $data Additional data to log
 */
function vd_debug_log($message, $data = null) {
    if (!defined('VD_DEBUG_MODE') || !VD_DEBUG_MODE) {
        return;
    }

    $log_message = '[VD License Manager] ' . $message;

    if ($data !== null) {
        $log_message .= ' | Data: ' . print_r($data, true);
    }

    error_log($log_message);
}

/**
 * Format datetime for display
 *
 * @param string|int $datetime Datetime string or timestamp
 * @param string $format Output format
 * @return string Formatted datetime
 */
function vd_format_datetime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) {
        return '';
    }

    if (is_numeric($datetime)) {
        $timestamp = $datetime;
    } else {
        $timestamp = strtotime($datetime);
    }

    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
}

/**
 * Get time difference in human readable format
 *
 * @param string|int $datetime Datetime string or timestamp
 * @return string Human readable time difference
 */
function vd_time_ago($datetime) {
    if (empty($datetime)) {
        return '';
    }

    if (is_numeric($datetime)) {
        $timestamp = $datetime;
    } else {
        $timestamp = strtotime($datetime);
    }

    if ($timestamp === false) {
        return '';
    }

    $diff = time() - $timestamp;

    // Use plain text if text domain not loaded yet, otherwise use translation
    if (!did_action('init')) {
        if ($diff < 60) {
            return sprintf('%d second%s ago', $diff, $diff !== 1 ? 's' : '');
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return sprintf('%d minute%s ago', $minutes, $minutes !== 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf('%d hour%s ago', $hours, $hours !== 1 ? 's' : '');
        } else {
            $days = floor($diff / 86400);
            return sprintf('%d day%s ago', $days, $days !== 1 ? 's' : '');
        }
    }

    if ($diff < 60) {
        return sprintf(_n('%d second ago', '%d seconds ago', $diff, VD_LM_TEXT_DOMAIN), $diff);
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return sprintf(_n('%d minute ago', '%d minutes ago', $minutes, VD_LM_TEXT_DOMAIN), $minutes);
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return sprintf(_n('%d hour ago', '%d hours ago', $hours, VD_LM_TEXT_DOMAIN), $hours);
    } else {
        $days = floor($diff / 86400);
        return sprintf(_n('%d day ago', '%d days ago', $days, VD_LM_TEXT_DOMAIN), $days);
    }
}

/**
 * Check if encryption key is properly configured
 *
 * @return bool True if encryption key is valid, false otherwise
 */
function vd_is_encryption_key_valid() {
    if (!defined('VD_ENCRYPTION_KEY') || empty(VD_ENCRYPTION_KEY)) {
        return false;
    }

    $key = VD_ENCRYPTION_KEY;

    // Handle base64 encoded keys
    if (strpos($key, 'base64:') === 0) {
        $decoded = base64_decode(substr($key, 7));
        return $decoded !== false && strlen($decoded) === 32;
    }

    // Direct key should be 32 bytes
    return strlen($key) === 32;
}

/**
 * Generate secure random string
 *
 * @param int $length String length
 * @return string Random string
 */
function vd_generate_random_string($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length / 2));
    }

    // Fallback for older PHP versions
    return bin2hex(openssl_random_pseudo_bytes($length / 2));
}

/**
 * Check if we're in WP CLI environment
 *
 * @return bool True if in WP CLI, false otherwise
 */
function vd_is_wp_cli() {
    return defined('WP_CLI') && WP_CLI;
}

/**
 * Check if we're in WordPress admin
 *
 * @return bool True if in admin, false otherwise
 */
function vd_is_admin() {
    return is_admin() && !vd_is_wp_cli();
}

/**
 * Check if current request is AJAX
 *
 * @return bool True if AJAX request, false otherwise
 */
function vd_is_ajax() {
    return wp_doing_ajax();
}

/**
 * Check if current request is REST API
 *
 * @return bool True if REST API request, false otherwise
 */
function vd_is_rest() {
    return defined('REST_REQUEST') && REST_REQUEST;
}
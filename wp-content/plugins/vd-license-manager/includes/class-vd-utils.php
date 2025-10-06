<?php
/**
 * Utility functions for VD License Manager
 *
 * Static utility methods for common operations
 *
 * @package VD_License_Manager
 * @subpackage Utilities
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Utils class
 *
 * Collection of static utility methods
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Utils {

    /**
     * Get license by license key
     *
     * @since 1.0.0
     * @param string $license_key License key to search for
     * @return object|false License object or false if not found
     */
    public static function get_license_by_key($license_key) {
        if (empty($license_key)) {
            return false;
        }

        global $wpdb;

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lmfwc_licenses WHERE license_key = %s",
            sanitize_text_field($license_key)
        ));

        return $license ?: false;
    }

    /**
     * Get all licenses for a product
     *
     * @since 1.0.0
     * @param int $product_id Product ID
     * @return array Array of license objects
     */
    public static function get_product_licenses($product_id) {
        if (empty($product_id) || !is_numeric($product_id)) {
            return array();
        }

        global $wpdb;

        $licenses = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lmfwc_licenses WHERE product_id = %d ORDER BY created_at DESC",
            (int) $product_id
        ), ARRAY_A);

        return $licenses ?: array();
    }

    /**
     * Format license key with dashes
     *
     * @since 1.0.0
     * @param string $key License key to format
     * @return string Formatted license key
     */
    public static function format_license_key($key) {
        $clean_key = preg_replace('/[^a-zA-Z0-9]/', '', $key);

        if (strlen($clean_key) === 16) {
            return substr($clean_key, 0, 4) . '-' .
                   substr($clean_key, 4, 4) . '-' .
                   substr($clean_key, 8, 4) . '-' .
                   substr($clean_key, 12, 4);
        }

        return $key;
    }

    /**
     * Mask sensitive data for display
     *
     * @since 1.0.0
     * @param string $data Data to mask
     * @param string $type Type of data (password, email)
     * @return string Masked data
     */
    public static function mask_sensitive_data($data, $type) {
        if (empty($data)) {
            return '';
        }

        switch ($type) {
            case 'password':
                if (strlen($data) <= 4) {
                    return str_repeat('*', strlen($data));
                }
                return substr($data, 0, 4) . str_repeat('*', strlen($data) - 4);

            case 'email':
                $parts = explode('@', $data);
                if (count($parts) !== 2) {
                    return $data;
                }
                $username = $parts[0];
                $domain = $parts[1];

                if (strlen($username) <= 2) {
                    return str_repeat('*', strlen($username)) . '@' . $domain;
                }

                return substr($username, 0, 2) . str_repeat('*', strlen($username) - 2) . '@' . $domain;

            default:
                return $data;
        }
    }

    /**
     * Export data to CSV and output
     *
     * @since 1.0.0
     * @param array $data Data to export
     * @param array $headers Column headers
     * @param string $filename Output filename
     */
    public static function export_to_csv($data, $headers, $filename) {
        if (empty($data) || !is_array($data)) {
            wp_die(__('No data to export', 'vd-license-manager'));
        }

        // Set headers for file download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . sanitize_file_name($filename));
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output handle
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write headers
        if (!empty($headers)) {
            fputcsv($output, $headers);
        }

        // Write data
        foreach ($data as $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Log debug message
     *
     * @since 1.0.0
     * @param string $message Message to log
     * @param array $context Additional context data
     */
    public static function log_debug($message, $context = array()) {
        if (!defined('VD_DEBUG') || !VD_DEBUG) {
            return;
        }

        $log_message = '[VD License Manager] ' . $message;

        if (!empty($context)) {
            $log_message .= ' Context: ' . wp_json_encode($context);
        }

        error_log($log_message);
    }

    /**
     * Get full table name with prefix
     *
     * @since 1.0.0
     * @param string $table_suffix Table suffix after 'vd_'
     * @return string Full table name
     */
    public static function get_table_name($table_suffix) {
        global $wpdb;
        return $wpdb->prefix . 'vd_' . $table_suffix;
    }

    /**
     * Validate license key format
     *
     * @since 1.0.0
     * @param string $key License key to validate
     * @return bool True if valid format
     */
    public static function is_valid_license_format($key) {
        $clean_key = preg_replace('/[^a-zA-Z0-9]/', '', $key);
        return strlen($clean_key) === 16 && ctype_alnum($clean_key);
    }

    /**
     * Get user IP address
     *
     * @since 1.0.0
     * @return string User IP address
     */
    public static function get_user_ip() {
        $ip_keys = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Format bytes to human readable
     *
     * @since 1.0.0
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    public static function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
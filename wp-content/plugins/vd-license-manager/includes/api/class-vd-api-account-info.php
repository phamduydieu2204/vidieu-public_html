<?php
/**
 * Account info API endpoint for VD License Manager
 *
 * Handles account information retrieval requests
 *
 * @package VD_License_Manager
 * @subpackage API
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD API Account Info class
 *
 * Processes account information requests via REST API
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_API_Account_Info {

    /**
     * Handle account info request
     *
     * Main endpoint handler for account information retrieval
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public static function handle($request) {
        try {
            // Extract license key from Authorization header
            $license_key = self::extract_license_key($request);
            if (is_wp_error($license_key)) {
                return new WP_REST_Response(array(
                    'ok' => false,
                    'error' => $license_key->get_error_message()
                ), 401);
            }

            // Extract device fingerprint from X-Device-Fingerprint header
            $device_fp = self::extract_device_fingerprint($request);
            if (is_wp_error($device_fp)) {
                return new WP_REST_Response(array(
                    'ok' => false,
                    'error' => $device_fp->get_error_message()
                ), 400);
            }

            // Call VD_Account_Info to get account information
            $result = VD_Account_Info::get_info($license_key, $device_fp);

            // Determine HTTP status code based on result
            $status_code = 200;
            if (!$result['ok']) {
                $error_message = $result['error'];
                if (strpos($error_message, 'Rate limit') !== false) {
                    $status_code = 429;
                } elseif (strpos($error_message, 'Device') !== false) {
                    $status_code = 403;
                } elseif (strpos($error_message, 'License') !== false) {
                    $status_code = 401;
                } else {
                    $status_code = 400;
                }
            }

            return new WP_REST_Response($result, $status_code);

        } catch (Exception $e) {
            error_log('VD API Account Info: ' . $e->getMessage());
            return new WP_REST_Response(array(
                'ok' => false,
                'error' => 'Internal server error'
            ), 500);
        }
    }

    /**
     * Extract license key from Authorization header
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return string|WP_Error License key or error
     */
    private static function extract_license_key($request) {
        $auth_header = $request->get_header('authorization');

        if (empty($auth_header)) {
            return new WP_Error('missing_auth', 'Authorization header is required');
        }

        // Parse Bearer token format
        if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return new WP_Error('invalid_auth_format', 'Authorization header must use Bearer format');
        }

        $license_key = trim($matches[1]);

        // Validate license key format
        $sanitized_key = VD_Security::sanitize_license_key($license_key);
        if (!$sanitized_key) {
            return new WP_Error('invalid_license', 'Invalid license key format');
        }

        return $sanitized_key;
    }

    /**
     * Extract device fingerprint from X-Device-Fingerprint header
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return string|WP_Error Device fingerprint or error
     */
    private static function extract_device_fingerprint($request) {
        $fp_header = $request->get_header('x-device-fingerprint');

        if (empty($fp_header)) {
            return new WP_Error('missing_fingerprint', 'X-Device-Fingerprint header is required');
        }

        $device_fp = sanitize_text_field($fp_header);

        // Validate fingerprint format
        if (!VD_Security::validate_fingerprint($device_fp)) {
            return new WP_Error('invalid_fingerprint', 'Invalid device fingerprint format');
        }

        return $device_fp;
    }
}
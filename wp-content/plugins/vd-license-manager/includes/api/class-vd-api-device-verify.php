<?php
/**
 * Device verification API endpoint for VD License Manager
 *
 * Handles device registration and verification requests
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
 * VD API Device Verify class
 *
 * Processes device verification requests via REST API
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_API_Device_Verify {

    /**
     * Handle device verification request
     *
     * Main endpoint handler for device verification
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response Response object
     */
    public static function handle($request) {
        try {
            // Extract and validate parameters
            $params = self::extract_parameters($request);
            if (is_wp_error($params)) {
                return self::format_error_response(400, $params->get_error_message());
            }

            // Generate fingerprint hash
            $fp_hash = VD_Security::generate_fingerprint_hash($params['fp']);
            if (!$fp_hash) {
                return self::format_error_response(400, 'Unable to generate device fingerprint');
            }

            // Save fingerprint data
            self::save_fingerprint($fp_hash, $params);

            // Verify license
            $license_result = VD_License_Verify::verify_license($params['license_key']);
            if (!$license_result['ok']) {
                return self::format_error_response(400, $license_result['error']);
            }

            $license_id = $license_result['license_id'];

            // Verify device
            $device_result = VD_License_Verify::verify_device($license_id, $fp_hash);

            // Check device limits
            $limit_result = VD_License_Verify::check_device_limit($license_id);

            // Check cooldown if needed
            $cooldown_result = VD_License_Verify::check_cooldown($license_id);

            // Log access attempt
            VD_License_Verify::log_access(
                $license_id,
                $fp_hash,
                'device_verify',
                $device_result['ok'] ? 'success' : 'denied',
                $device_result['error']
            );

            // Format and return response
            return self::format_success_response(array(
                'device_result' => $device_result,
                'limit_result' => $limit_result,
                'cooldown_result' => $cooldown_result,
                'license_result' => $license_result
            ));

        } catch (Exception $e) {
            error_log('VD API Device Verify: ' . $e->getMessage());
            return self::format_error_response(500, 'Internal server error');
        }
    }

    /**
     * Extract and validate request parameters
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return array|WP_Error Parameters array or error
     */
    private static function extract_parameters($request) {
        $license_key = $request->get_param('license_key');
        $fp_data = $request->get_param('fp');
        $user_agent = $request->get_param('ua');
        $os = $request->get_param('os');
        $screen = $request->get_param('screen');

        // Validate required parameters
        if (empty($license_key)) {
            return new WP_Error('missing_license', 'License key is required');
        }

        if (!is_array($fp_data) || empty($fp_data)) {
            return new WP_Error('missing_fingerprint', 'Fingerprint data is required');
        }

        // Sanitize fingerprint data
        $sanitized_fp = array();
        foreach ($fp_data as $key => $value) {
            $sanitized_key = sanitize_key($key);
            $sanitized_value = sanitize_text_field($value);
            if (!empty($sanitized_key) && !empty($sanitized_value)) {
                $sanitized_fp[$sanitized_key] = $sanitized_value;
            }
        }

        if (empty($sanitized_fp)) {
            return new WP_Error('invalid_fingerprint', 'Invalid fingerprint data');
        }

        return array(
            'license_key' => sanitize_text_field($license_key),
            'fp' => $sanitized_fp,
            'ua' => sanitize_text_field($user_agent),
            'os' => sanitize_text_field($os),
            'screen' => sanitize_text_field($screen)
        );
    }

    /**
     * Save fingerprint data to database
     *
     * @since 1.0.0
     * @param string $fp_hash Fingerprint hash
     * @param array $data Fingerprint data
     * @return bool True on success
     */
    private static function save_fingerprint($fp_hash, $data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_device_fingerprints';

        // Check if fingerprint exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT fp_hash FROM $table_name WHERE fp_hash = %s",
            $fp_hash
        ));

        $fp_data = array(
            'ua' => $data['ua'],
            'os' => $data['os'],
            'screen' => $data['screen']
        );

        if ($existing) {
            // Update existing record
            return $wpdb->update(
                $table_name,
                array(
                    'fp_data' => wp_json_encode($fp_data),
                    'last_seen' => current_time('mysql')
                ),
                array('fp_hash' => $fp_hash),
                array('%s', '%s'),
                array('%s')
            ) !== false;
        } else {
            // Insert new record
            return $wpdb->insert(
                $table_name,
                array(
                    'fp_hash' => $fp_hash,
                    'fp_data' => wp_json_encode($fp_data),
                    'first_seen' => current_time('mysql'),
                    'last_seen' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s')
            ) !== false;
        }
    }

    /**
     * Format success response
     *
     * @since 1.0.0
     * @param array $data Response data
     * @return WP_REST_Response Success response
     */
    private static function format_success_response($data) {
        $device_result = $data['device_result'];
        $limit_result = $data['limit_result'];
        $cooldown_result = $data['cooldown_result'];
        $license_result = $data['license_result'];

        $response = array(
            'ok' => true,
            'deviceStatus' => $device_result['status'],
            'remainingSlots' => $limit_result['remaining'],
            'cooldownEndsAt' => $cooldown_result['ends_at'],
            'license' => array(
                'expires_at' => $license_result['expires_at'],
                'days_remaining' => $license_result['days_remaining']
            ),
            'timestamp' => current_time('c')
        );

        $status_code = 200;
        if ($device_result['status'] === 'pending') {
            $status_code = 202; // Accepted but pending approval
        } elseif ($device_result['status'] === 'blocked') {
            $status_code = 403; // Forbidden
        }

        return new WP_REST_Response($response, $status_code);
    }

    /**
     * Format error response
     *
     * @since 1.0.0
     * @param int $code HTTP status code
     * @param string $message Error message
     * @return WP_REST_Response Error response
     */
    private static function format_error_response($code, $message) {
        $response = array(
            'ok' => false,
            'error' => sanitize_text_field($message),
            'timestamp' => current_time('c')
        );

        return new WP_REST_Response($response, $code);
    }
}
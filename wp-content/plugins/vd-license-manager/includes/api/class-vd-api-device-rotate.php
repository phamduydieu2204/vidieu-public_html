<?php
/**
 * Device rotation API endpoint for VD License Manager
 *
 * Handles device rotation requests with cooldown enforcement
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
 * VD API Device Rotate class
 *
 * Processes device rotation requests via REST API
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_API_Device_Rotate {

    /**
     * Handle device rotation request
     *
     * Main endpoint handler for device rotation
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response Response object
     */
    public static function handle($request) {
        global $wpdb;

        try {
            // Validate inputs
            $params = self::validate_inputs($request);
            if (is_wp_error($params)) {
                return new WP_REST_Response(array(
                    'ok' => false,
                    'error' => $params->get_error_message()
                ), 400);
            }

            // Verify license
            $license_result = VD_License_Verify::verify_license($params['license_key']);
            if (!$license_result['ok']) {
                return new WP_REST_Response(array(
                    'ok' => false,
                    'error' => $license_result['error']
                ), 401);
            }

            $license_id = $license_result['license_id'];

            // Check cooldown
            $cooldown_result = VD_License_Verify::check_cooldown($license_id);
            if (!$cooldown_result['ok']) {
                $remaining_seconds = $cooldown_result['remaining_seconds'];
                return new WP_REST_Response(array(
                    'ok' => false,
                    'error' => 'Cooldown not passed',
                    'cooldown_ends_at' => $cooldown_result['ends_at'],
                    'remaining_hours' => round($remaining_seconds / 3600, 1)
                ), 429);
            }

            // Validate old device ownership
            $old_device_check = self::validate_old_device($license_id, $params['old_device_fp']);
            if (is_wp_error($old_device_check)) {
                return new WP_REST_Response(array(
                    'ok' => false,
                    'error' => $old_device_check->get_error_message()
                ), 403);
            }

            // Start transaction
            $wpdb->query('START TRANSACTION');

            try {
                // Block old device
                $block_result = self::block_old_device($license_id, $params['old_device_fp'], $params['reason']);
                if (!$block_result) {
                    throw new Exception('Failed to block old device');
                }

                // Add new device as pending
                $add_result = self::add_new_device($license_id, $params['new_device_fp'], $params['reason']);
                if (!$add_result) {
                    throw new Exception('Failed to add new device');
                }

                // Commit transaction
                $wpdb->query('COMMIT');

                // Log rotation action
                VD_License_Verify::log_access(
                    $license_id,
                    $params['new_device_fp'],
                    'rotate',
                    'success',
                    'Device rotated from ' . substr($params['old_device_fp'], 0, 8) . '... to ' .
                    substr($params['new_device_fp'], 0, 8) . '... Reason: ' . $params['reason']
                );

                // Calculate next cooldown
                $next_cooldown = current_time('mysql');
                $next_cooldown_timestamp = strtotime($next_cooldown) + 86400; // 24 hours
                $next_cooldown_iso = gmdate('c', $next_cooldown_timestamp);

                return new WP_REST_Response(array(
                    'ok' => true,
                    'message' => 'Device rotation requested successfully',
                    'new_device_status' => 'pending',
                    'cooldown_until' => $next_cooldown_iso,
                    'timestamp' => current_time('c')
                ), 200);

            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }

        } catch (Exception $e) {
            error_log('VD API Device Rotate: ' . $e->getMessage());
            return new WP_REST_Response(array(
                'ok' => false,
                'error' => 'Internal server error'
            ), 500);
        }
    }

    /**
     * Validate input parameters
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return array|WP_Error Validated parameters or error
     */
    private static function validate_inputs($request) {
        $license_key = $request->get_param('license_key');
        $old_device_fp = $request->get_param('old_device_fp');
        $new_device_fp = $request->get_param('new_device_fp');
        $reason = $request->get_param('reason') ?: 'Device rotation requested';

        // Validate license key
        $sanitized_key = VD_Security::sanitize_license_key($license_key);
        if (!$sanitized_key) {
            return new WP_Error('invalid_license', 'Invalid license key format');
        }

        // Validate old device fingerprint
        if (!VD_Security::validate_fingerprint($old_device_fp)) {
            return new WP_Error('invalid_old_fp', 'Invalid old device fingerprint format');
        }

        // Validate new device fingerprint
        if (!VD_Security::validate_fingerprint($new_device_fp)) {
            return new WP_Error('invalid_new_fp', 'Invalid new device fingerprint format');
        }

        // Check fingerprints are different
        if ($old_device_fp === $new_device_fp) {
            return new WP_Error('same_device', 'Old and new device fingerprints cannot be the same');
        }

        return array(
            'license_key' => $sanitized_key,
            'old_device_fp' => sanitize_text_field($old_device_fp),
            'new_device_fp' => sanitize_text_field($new_device_fp),
            'reason' => sanitize_text_field($reason)
        );
    }

    /**
     * Validate old device ownership
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $old_device_fp Old device fingerprint
     * @return bool|WP_Error True if valid or error
     */
    private static function validate_old_device($license_id, $old_device_fp) {
        global $wpdb;

        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}vd_license_devices
             WHERE license_id = %d AND device_fp = %s",
            $license_id,
            $old_device_fp
        ));

        if (!$device) {
            return new WP_Error('device_not_found', 'Old device not found for this license');
        }

        if ($device->status !== 'approved') {
            return new WP_Error('device_not_approved', 'Old device is not approved for rotation');
        }

        return true;
    }

    /**
     * Block old device
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $old_device_fp Old device fingerprint
     * @param string $reason Rotation reason
     * @return bool True on success
     */
    private static function block_old_device($license_id, $old_device_fp, $reason) {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'vd_license_devices',
            array(
                'status' => 'blocked',
                'notes' => 'Rotated: ' . $reason,
                'updated_at' => current_time('mysql')
            ),
            array(
                'license_id' => $license_id,
                'device_fp' => $old_device_fp
            ),
            array('%s', '%s', '%s'),
            array('%d', '%s')
        ) !== false;
    }

    /**
     * Add new device as pending
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $new_device_fp New device fingerprint
     * @param string $reason Rotation reason
     * @return bool True on success
     */
    private static function add_new_device($license_id, $new_device_fp, $reason) {
        global $wpdb;

        return $wpdb->insert(
            $wpdb->prefix . 'vd_license_devices',
            array(
                'license_id' => $license_id,
                'device_fp' => $new_device_fp,
                'status' => 'pending',
                'notes' => 'Added via rotation: ' . $reason,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        ) !== false;
    }
}
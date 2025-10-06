<?php
/**
 * License verification logic for VD License Manager
 *
 * Handles license validation, device verification, and access control
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
 * VD License Verify class
 *
 * Core logic for license and device verification
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_License_Verify {

    /**
     * Verify license key validity and status
     *
     * @since 1.0.0
     * @param string $license_key License key to verify
     * @return array Verification result
     */
    public static function verify_license($license_key) {
        global $wpdb;

        // Sanitize license key
        $license_key = VD_Security::sanitize_license_key($license_key);
        if (!$license_key) {
            return array(
                'ok' => false,
                'error' => 'Invalid license key format'
            );
        }

        // Query LMFWC table
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT id, product_id, status, expires_at
             FROM {$wpdb->prefix}lmfwc_licenses
             WHERE license_key = %s",
            $license_key
        ));

        if (!$license) {
            return array(
                'ok' => false,
                'error' => 'License key not found'
            );
        }

        // Check license status
        if ($license->status !== 'active') {
            return array(
                'ok' => false,
                'error' => 'License is not active'
            );
        }

        // Check expiration
        if (VD_DateTime::is_expired($license->expires_at)) {
            return array(
                'ok' => false,
                'error' => 'License has expired'
            );
        }

        return array(
            'ok' => true,
            'license_id' => (int) $license->id,
            'product_id' => (int) $license->product_id,
            'expires_at' => $license->expires_at,
            'days_remaining' => VD_DateTime::days_until($license->expires_at),
            'error' => ''
        );
    }

    /**
     * Verify device fingerprint for license
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $device_fp Device fingerprint hash
     * @return array Device verification result
     */
    public static function verify_device($license_id, $device_fp) {
        global $wpdb;

        // Validate inputs
        if (!is_numeric($license_id) || !VD_Security::validate_fingerprint($device_fp)) {
            return array(
                'ok' => false,
                'status' => 'invalid',
                'error' => 'Invalid license ID or device fingerprint'
            );
        }

        $table_name = $wpdb->prefix . 'vd_license_devices';

        // Check if device exists for this license
        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT status, last_used_at
             FROM $table_name
             WHERE license_id = %d AND device_fp = %s",
            $license_id,
            $device_fp
        ));

        if (!$device) {
            // Device not found, create pending entry
            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'license_id' => $license_id,
                    'device_fp' => $device_fp,
                    'status' => 'pending'
                ),
                array('%d', '%s', '%s')
            );

            if ($inserted === false) {
                return array(
                    'ok' => false,
                    'status' => 'error',
                    'error' => 'Failed to register device'
                );
            }

            return array(
                'ok' => false,
                'status' => 'pending',
                'error' => 'Device pending approval'
            );
        }

        // Check device status
        if ($device->status === 'blocked') {
            return array(
                'ok' => false,
                'status' => 'blocked',
                'error' => 'Device is blocked'
            );
        }

        if ($device->status === 'pending') {
            return array(
                'ok' => false,
                'status' => 'pending',
                'error' => 'Device pending approval'
            );
        }

        if ($device->status === 'approved') {
            // Update last used time
            $wpdb->update(
                $table_name,
                array('last_used_at' => current_time('mysql')),
                array('license_id' => $license_id, 'device_fp' => $device_fp),
                array('%s'),
                array('%d', '%s')
            );

            return array(
                'ok' => true,
                'status' => 'approved',
                'error' => ''
            );
        }

        return array(
            'ok' => false,
            'status' => 'unknown',
            'error' => 'Unknown device status'
        );
    }

    /**
     * Check device limits for license
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @return array Device limit check result
     */
    public static function check_device_limit($license_id) {
        global $wpdb;

        if (!is_numeric($license_id)) {
            return array(
                'ok' => false,
                'used' => 0,
                'max' => 0,
                'remaining' => 0
            );
        }

        // Get device limits
        $limits = $wpdb->get_row($wpdb->prepare(
            "SELECT max_devices
             FROM {$wpdb->prefix}vd_license_device_limits
             WHERE license_id = %d",
            $license_id
        ));

        $max_devices = $limits ? (int) $limits->max_devices : 1;

        // Count approved devices
        $used_devices = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}vd_license_devices
             WHERE license_id = %d AND status = 'approved'",
            $license_id
        ));

        $remaining = max(0, $max_devices - $used_devices);

        return array(
            'ok' => $remaining > 0,
            'used' => $used_devices,
            'max' => $max_devices,
            'remaining' => $remaining
        );
    }

    /**
     * Check rotation cooldown for license
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @return array Cooldown check result
     */
    public static function check_cooldown($license_id) {
        global $wpdb;

        if (!is_numeric($license_id)) {
            return array(
                'ok' => true,
                'ends_at' => null,
                'remaining_seconds' => 0
            );
        }

        // Get cooldown settings
        $limits = $wpdb->get_row($wpdb->prepare(
            "SELECT rotation_cooldown_seconds
             FROM {$wpdb->prefix}vd_license_device_limits
             WHERE license_id = %d",
            $license_id
        ));

        $cooldown_seconds = $limits ? (int) $limits->rotation_cooldown_seconds : 86400;

        // Get last rotation time
        $last_rotation = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(accessed_at)
             FROM {$wpdb->prefix}vd_license_access_log
             WHERE license_id = %d AND action = 'rotate'",
            $license_id
        ));

        if (!$last_rotation) {
            return array(
                'ok' => true,
                'ends_at' => null,
                'remaining_seconds' => 0
            );
        }

        return VD_DateTime::is_within_cooldown($last_rotation, $cooldown_seconds);
    }

    /**
     * Log access attempt
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $device_fp Device fingerprint
     * @param string $action Action performed
     * @param string $status Result status
     * @param string $reason Optional reason/error message
     * @return bool True on success
     */
    public static function log_access($license_id, $device_fp, $action, $status, $reason = '') {
        global $wpdb;

        // Get client information
        $ip = VD_Security::get_client_ip();
        $geo_data = $ip ? VD_Data::get_ip_geolocation($ip) : false;

        // Prepare log data
        $log_data = array(
            'license_id' => (int) $license_id,
            'device_fp' => sanitize_text_field($device_fp),
            'action' => sanitize_text_field($action),
            'status' => sanitize_text_field($status),
            'reason' => sanitize_text_field($reason),
            'ip_address' => $ip,
            'ip_country' => $geo_data ? $geo_data['countryCode'] : '',
            'ip_city' => $geo_data ? $geo_data['city'] : '',
            'accessed_at' => current_time('mysql')
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'vd_license_access_log',
            $log_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result !== false;
    }
}
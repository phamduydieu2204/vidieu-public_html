<?php
/**
 * VD License Manager Device Manager
 *
 * Handles device validation, registration, and tracking for licenses.
 * Implements device fingerprinting, limits enforcement, and device management.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @since      1.0.0
 * @author     Vidieu Team <admin@vidieu.vn>
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Device Manager Class
 *
 * Manages device registration, validation, and tracking for license access.
 * Implements the device logic from FLOW_3_Customer_Access.xml
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Device_Manager {

    /**
     * Validate and register device for license access
     *
     * Implements the complete device validation logic from FLOW_3_Customer_Access.xml
     * including device limits, registration, and status checking.
     *
     * @since  1.0.0
     * @param  array  $license License data
     * @param  string $device_combined_id Device combined ID
     * @param  string $device_name User-friendly device name
     * @param  string $ip_address Client IP address
     * @param  string $user_agent Client user agent
     * @return array|WP_Error Device validation result or error
     */
    public function validate_and_register_device( $license, $device_combined_id, $device_name, $ip_address, $user_agent ) {
        global $wpdb;

        try {
            // Get license device configuration
            $device_config = $this->get_license_device_config( $license['id'] );
            $max_devices = $device_config['max_devices'] ?? 2;

            VD_LM_Logger_Service::info( 'Starting device validation', array(
                'license_id'         => $license['id'],
                'device_combined_id' => $device_combined_id,
                'max_devices'        => $max_devices
            ) );

            // Get all existing devices for this license
            $existing_devices = $this->get_license_devices( $license['id'] );

            // Check if current device already exists
            $current_device = $this->find_device_by_combined_id( $existing_devices, $device_combined_id );

            if ( $current_device ) {
                // Device exists - check status and update
                return $this->handle_existing_device( $current_device, $ip_address, $user_agent, $existing_devices );
            } else {
                // New device - check limits and register
                return $this->handle_new_device(
                    $license['id'],
                    $device_combined_id,
                    $device_name,
                    $ip_address,
                    $user_agent,
                    $existing_devices,
                    $max_devices
                );
            }

        } catch ( Exception $e ) {
            VD_LM_Logger_Service::error( 'Device validation error', array(
                'error'     => $e->getMessage(),
                'license_id' => $license['id'],
                'device_id' => $device_combined_id
            ) );

            return new WP_Error(
                'device_validation_error',
                __( 'Device validation failed', 'vd-license-manager' )
            );
        }
    }

    /**
     * Handle existing device access
     *
     * @since  1.0.0
     * @param  array  $current_device Current device data
     * @param  string $ip_address IP address
     * @param  string $user_agent User agent
     * @param  array  $all_devices All license devices
     * @return array Device result
     */
    private function handle_existing_device( $current_device, $ip_address, $user_agent, $all_devices ) {
        global $wpdb;

        // Check device status
        switch ( $current_device['status'] ) {
            case 'active':
                // Update last access information
                $this->update_device_access( $current_device['id'], $ip_address, $user_agent );

                VD_LM_Logger_Service::info( 'Existing active device access', array(
                    'device_id' => $current_device['id'],
                    'device_name' => $current_device['device_name']
                ) );

                return array(
                    'device_id'      => $current_device['id'],
                    'device_name'    => $current_device['device_name'],
                    'is_new_device'  => false,
                    'slot'           => $this->calculate_device_slot( $current_device, $all_devices ),
                    'registered_at'  => $current_device['registered_at'],
                    'devices_used'   => count( array_filter( $all_devices, function( $d ) {
                        return $d['status'] === 'active';
                    } ) )
                );

            case 'removed':
                // Removed device - treat as new device (re-registration allowed)
                VD_LM_Logger_Service::info( 'Removed device attempting re-registration', array(
                    'device_id' => $current_device['id'],
                    'removed_at' => $current_device['removed_at']
                ) );

                // Check if we can reactivate this device (respect device limits)
                $active_devices = array_filter( $all_devices, function( $d ) {
                    return $d['status'] === 'active';
                } );

                $max_devices = 2; // Get from config
                if ( count( $active_devices ) >= $max_devices ) {
                    return new WP_Error(
                        'device_limit_reached',
                        sprintf(
                            __( 'Maximum %d devices allowed. Remove an existing device first.', 'vd-license-manager' ),
                            $max_devices
                        )
                    );
                }

                // Reactivate the device
                $this->reactivate_device( $current_device['id'], $ip_address, $user_agent );

                return array(
                    'device_id'      => $current_device['id'],
                    'device_name'    => $current_device['device_name'],
                    'is_new_device'  => false,
                    'was_removed'    => true,
                    'slot'           => $this->calculate_device_slot( $current_device, $all_devices ),
                    'registered_at'  => current_time( 'mysql' ),
                    'devices_used'   => count( $active_devices ) + 1
                );

            case 'blocked':
                // Blocked device - deny access
                VD_LM_Logger_Service::warning( 'Blocked device attempted access', array(
                    'device_id' => $current_device['id'],
                    'blocked_reason' => $current_device['blocked_reason'],
                    'blocked_at' => $current_device['blocked_at']
                ) );

                return new WP_Error(
                    'device_blocked',
                    sprintf(
                        __( 'This device has been blocked. Reason: %s. Contact support if you believe this is an error.', 'vd-license-manager' ),
                        $current_device['blocked_reason'] ?? __( 'Violation of terms', 'vd-license-manager' )
                    )
                );

            default:
                return new WP_Error(
                    'device_invalid_status',
                    __( 'Device has invalid status', 'vd-license-manager' )
                );
        }
    }

    /**
     * Handle new device registration
     *
     * @since  1.0.0
     * @param  int    $license_id License ID
     * @param  string $device_combined_id Device combined ID
     * @param  string $device_name Device name
     * @param  string $ip_address IP address
     * @param  string $user_agent User agent
     * @param  array  $existing_devices Existing devices
     * @param  int    $max_devices Maximum allowed devices
     * @return array|WP_Error Device result or error
     */
    private function handle_new_device( $license_id, $device_combined_id, $device_name, $ip_address, $user_agent, $existing_devices, $max_devices ) {
        // Count active devices
        $active_devices = array_filter( $existing_devices, function( $d ) {
            return $d['status'] === 'active';
        } );

        $active_count = count( $active_devices );

        VD_LM_Logger_Service::info( 'New device registration attempt', array(
            'license_id'    => $license_id,
            'active_count'  => $active_count,
            'max_devices'   => $max_devices,
            'device_name'   => $device_name
        ) );

        // Check device limit
        if ( $active_count >= $max_devices ) {
            return new WP_Error(
                'device_limit_reached',
                sprintf(
                    __( 'Maximum %d devices allowed. Current active devices: %d. Please remove an existing device to add a new one.', 'vd-license-manager' ),
                    $max_devices,
                    $active_count
                )
            );
        }

        // Register new device
        $device_id = $this->register_new_device(
            $license_id,
            $device_combined_id,
            $device_name,
            $ip_address,
            $user_agent
        );

        if ( is_wp_error( $device_id ) ) {
            return $device_id;
        }

        // Calculate slot number (next available slot)
        $slot = $this->calculate_next_available_slot( $existing_devices );

        VD_LM_Logger_Service::info( 'New device registered successfully', array(
            'license_id' => $license_id,
            'device_id'  => $device_id,
            'slot'       => $slot
        ) );

        return array(
            'device_id'      => $device_id,
            'device_name'    => $device_name,
            'is_new_device'  => true,
            'slot'           => $slot,
            'registered_at'  => current_time( 'mysql' ),
            'devices_used'   => $active_count + 1
        );
    }

    /**
     * Get license device configuration
     *
     * @since  1.0.0
     * @param  int $license_id License ID
     * @return array Device configuration
     */
    private function get_license_device_config( $license_id ) {
        global $wpdb;

        // Get from license device limits table
        $limits_table = $wpdb->prefix . 'vd_license_device_limits';
        $config = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$limits_table} WHERE license_id = %d",
            $license_id
        ), ARRAY_A );

        if ( $config ) {
            return $config;
        }

        // Fallback to product configuration
        $license_table = $wpdb->prefix . 'vd_license_keys';
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT product_id, max_devices FROM {$license_table} WHERE id = %d",
            $license_id
        ), ARRAY_A );

        return array(
            'max_devices' => $license['max_devices'] ?? 2,
            'strict_mode' => 1,
            'allow_device_replacement' => 1,
            'device_cooldown_hours' => 24
        );
    }

    /**
     * Get all devices for a license
     *
     * @since  1.0.0
     * @param  int $license_id License ID
     * @return array List of devices
     */
    private function get_license_devices( $license_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vd_license_devices';

        $devices = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE license_id = %d ORDER BY registered_at ASC",
            $license_id
        ), ARRAY_A );

        return $devices ?: array();
    }

    /**
     * Find device by combined ID
     *
     * @since  1.0.0
     * @param  array  $devices Device list
     * @param  string $device_combined_id Device combined ID
     * @return array|null Device data or null
     */
    private function find_device_by_combined_id( $devices, $device_combined_id ) {
        foreach ( $devices as $device ) {
            if ( $device['device_id'] === $device_combined_id ) {
                return $device;
            }
        }
        return null;
    }

    /**
     * Update device access information
     *
     * @since 1.0.0
     * @param int    $device_id Device ID
     * @param string $ip_address IP address
     * @param string $user_agent User agent
     */
    private function update_device_access( $device_id, $ip_address, $user_agent ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vd_license_devices';

        $wpdb->update(
            $table,
            array(
                'last_access_at' => current_time( 'mysql' ),
                'registration_ip' => $ip_address,
                'access_count'   => new WP_Query( "access_count + 1" ), // Increment counter
                'updated_at'     => current_time( 'mysql' )
            ),
            array( 'id' => $device_id ),
            array( '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        // Fix increment counter with raw SQL
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$table} SET access_count = access_count + 1 WHERE id = %d",
            $device_id
        ) );
    }

    /**
     * Reactivate a removed device
     *
     * @since 1.0.0
     * @param int    $device_id Device ID
     * @param string $ip_address IP address
     * @param string $user_agent User agent
     */
    private function reactivate_device( $device_id, $ip_address, $user_agent ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vd_license_devices';

        $wpdb->update(
            $table,
            array(
                'status'         => 'active',
                'last_access_at' => current_time( 'mysql' ),
                'registration_ip' => $ip_address,
                'removed_at'     => null,
                'removed_by'     => null,
                'updated_at'     => current_time( 'mysql' )
            ),
            array( 'id' => $device_id ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        VD_LM_Logger_Service::info( 'Device reactivated', array(
            'device_id' => $device_id
        ) );
    }

    /**
     * Register new device
     *
     * @since  1.0.0
     * @param  int    $license_id License ID
     * @param  string $device_combined_id Device combined ID
     * @param  string $device_name Device name
     * @param  string $ip_address IP address
     * @param  string $user_agent User agent
     * @return int|WP_Error Device ID or error
     */
    private function register_new_device( $license_id, $device_combined_id, $device_name, $ip_address, $user_agent ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vd_license_devices';

        $result = $wpdb->insert(
            $table,
            array(
                'license_id'       => $license_id,
                'device_id'        => $device_combined_id,
                'device_name'      => $device_name,
                'registration_ip'  => $ip_address,
                'status'           => 'active',
                'first_access_at'  => current_time( 'mysql' ),
                'last_access_at'   => current_time( 'mysql' ),
                'access_count'     => 1,
                'registered_at'    => current_time( 'mysql' ),
                'updated_at'       => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( $result === false ) {
            VD_LM_Logger_Service::error( 'Failed to register device', array(
                'license_id' => $license_id,
                'device_id'  => $device_combined_id,
                'error'      => $wpdb->last_error
            ) );

            return new WP_Error(
                'device_registration_failed',
                __( 'Failed to register device', 'vd-license-manager' )
            );
        }

        return $wpdb->insert_id;
    }

    /**
     * Calculate device slot number
     *
     * @since  1.0.0
     * @param  array $current_device Current device
     * @param  array $all_devices All devices
     * @return int Slot number
     */
    private function calculate_device_slot( $current_device, $all_devices ) {
        // Sort devices by registration time
        usort( $all_devices, function( $a, $b ) {
            return strtotime( $a['registered_at'] ) - strtotime( $b['registered_at'] );
        } );

        // Find device position among active devices
        $slot = 1;
        foreach ( $all_devices as $device ) {
            if ( $device['status'] === 'active' ) {
                if ( $device['id'] === $current_device['id'] ) {
                    return $slot;
                }
                $slot++;
            }
        }

        return $slot;
    }

    /**
     * Calculate next available slot
     *
     * @since  1.0.0
     * @param  array $existing_devices Existing devices
     * @return int Next slot number
     */
    private function calculate_next_available_slot( $existing_devices ) {
        $active_devices = array_filter( $existing_devices, function( $d ) {
            return $d['status'] === 'active';
        } );

        return count( $active_devices ) + 1;
    }

    /**
     * Remove device by ID
     *
     * @since 1.0.0
     * @param int $device_id Device ID
     * @param int $removed_by User ID who removed device
     * @return bool|WP_Error Success or error
     */
    public function remove_device( $device_id, $removed_by ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vd_license_devices';

        $result = $wpdb->update(
            $table,
            array(
                'status'     => 'removed',
                'removed_at' => current_time( 'mysql' ),
                'removed_by' => $removed_by,
                'updated_at' => current_time( 'mysql' )
            ),
            array( 'id' => $device_id ),
            array( '%s', '%s', '%d', '%s' ),
            array( '%d' )
        );

        if ( $result === false ) {
            return new WP_Error(
                'device_removal_failed',
                __( 'Failed to remove device', 'vd-license-manager' )
            );
        }

        VD_LM_Logger_Service::info( 'Device removed', array(
            'device_id'  => $device_id,
            'removed_by' => $removed_by
        ) );

        return true;
    }

    /**
     * Block device by ID
     *
     * @since 1.0.0
     * @param int    $device_id Device ID
     * @param int    $blocked_by User ID who blocked device
     * @param string $reason Reason for blocking
     * @return bool|WP_Error Success or error
     */
    public function block_device( $device_id, $blocked_by, $reason ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vd_license_devices';

        $result = $wpdb->update(
            $table,
            array(
                'status'         => 'blocked',
                'blocked_at'     => current_time( 'mysql' ),
                'blocked_by'     => $blocked_by,
                'blocked_reason' => $reason,
                'updated_at'     => current_time( 'mysql' )
            ),
            array( 'id' => $device_id ),
            array( '%s', '%s', '%d', '%s', '%s' ),
            array( '%d' )
        );

        if ( $result === false ) {
            return new WP_Error(
                'device_blocking_failed',
                __( 'Failed to block device', 'vd-license-manager' )
            );
        }

        VD_LM_Logger_Service::info( 'Device blocked', array(
            'device_id'   => $device_id,
            'blocked_by'  => $blocked_by,
            'reason'      => $reason
        ) );

        return true;
    }

    /**
     * Get device statistics for a license
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @return array Device statistics
     */
    public function get_device_stats( $license_id ) {
        $devices = $this->get_license_devices( $license_id );
        $config = $this->get_license_device_config( $license_id );

        $stats = array(
            'total_devices'   => count( $devices ),
            'active_devices'  => 0,
            'removed_devices' => 0,
            'blocked_devices' => 0,
            'max_devices'     => $config['max_devices'],
            'available_slots' => 0
        );

        foreach ( $devices as $device ) {
            switch ( $device['status'] ) {
                case 'active':
                    $stats['active_devices']++;
                    break;
                case 'removed':
                    $stats['removed_devices']++;
                    break;
                case 'blocked':
                    $stats['blocked_devices']++;
                    break;
            }
        }

        $stats['available_slots'] = max( 0, $stats['max_devices'] - $stats['active_devices'] );

        return $stats;
    }
}
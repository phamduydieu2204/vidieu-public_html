<?php
/**
 * Account Capacity Manager
 *
 * Handles account capacity checks, race condition prevention,
 * and edge case management for license assignments.
 *
 * @package    VD_License_Manager
 * @subpackage Services
 * @since      1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Account Capacity Manager Class
 *
 * Provides atomic operations for capacity management, prevents over-assignment
 * through optimistic locking, and handles various edge cases in pool assignment.
 *
 * Key Features:
 * - Atomic capacity updates with race condition protection
 * - Real-time capacity calculation with accurate counting
 * - Account expiration and status validation
 * - Pool capacity monitoring and alerts
 * - Performance optimization with minimal database queries
 *
 * @since      1.1.0
 * @package    VD_License_Manager
 * @subpackage Services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Capacity_Manager {

    /**
     * Maximum retry attempts for atomic operations
     *
     * @since 1.1.0
     * @var int
     */
    const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Default account capacity if not specified
     *
     * @since 1.1.0
     * @var int
     */
    const DEFAULT_ACCOUNT_CAPACITY = 1;

    /**
     * Pool capacity warning threshold (percentage)
     *
     * @since 1.1.0
     * @var int
     */
    const CAPACITY_WARNING_THRESHOLD = 80;

    /**
     * Check if account can accommodate new license assignment
     *
     * Performs comprehensive checks including:
     * - Account existence and status
     * - Current capacity vs assigned licenses
     * - Account expiration
     * - Provider-specific limits
     *
     * @since 1.1.0
     * @param int $account_id Account ID to check
     * @param int $requested_capacity Number of licenses to assign (default: 1)
     * @return bool|WP_Error True if capacity available, WP_Error if not
     */
    public function check_account_capacity( $account_id, $requested_capacity = 1 ) {
        global $wpdb;

        // Validate input
        $account_id = absint( $account_id );
        $requested_capacity = absint( $requested_capacity );

        if ( $account_id <= 0 ) {
            return new WP_Error(
                'invalid_account_id',
                __( 'Invalid account ID provided', 'vd-license-manager' )
            );
        }

        if ( $requested_capacity <= 0 ) {
            return new WP_Error(
                'invalid_capacity_request',
                __( 'Requested capacity must be greater than zero', 'vd-license-manager' )
            );
        }

        // Get account with current usage in single query
        $account_data = $this->get_account_with_usage( $account_id );

        if ( ! $account_data ) {
            return new WP_Error(
                'account_not_found',
                __( 'Account not found or inactive', 'vd-license-manager' )
            );
        }

        // Check account status
        if ( $account_data->status !== 'active' ) {
            VD_LM_Logger_Service::warning( 'Capacity check: Account not active', array(
                'account_id' => $account_id,
                'status' => $account_data->status
            ) );

            return new WP_Error(
                'account_inactive',
                sprintf(
                    __( 'Account is not active (status: %s)', 'vd-license-manager' ),
                    $account_data->status
                )
            );
        }

        // Check account expiration
        if ( ! empty( $account_data->expires_at ) ) {
            $expires_at = strtotime( $account_data->expires_at );
            $now = current_time( 'timestamp' );

            if ( $expires_at <= $now ) {
                VD_LM_Logger_Service::warning( 'Capacity check: Account expired', array(
                    'account_id' => $account_id,
                    'expires_at' => $account_data->expires_at,
                    'current_time' => gmdate( 'Y-m-d H:i:s', $now )
                ) );

                return new WP_Error(
                    'account_expired',
                    sprintf(
                        __( 'Account expired on %s', 'vd-license-manager' ),
                        gmdate( 'Y-m-d H:i:s', $expires_at )
                    )
                );
            }
        }

        // Calculate available capacity
        $total_capacity = max( 1, intval( $account_data->capacity ) );
        $current_usage = intval( $account_data->current_usage );
        $available_capacity = $total_capacity - $current_usage;

        VD_LM_Logger_Service::debug( 'Capacity check details', array(
            'account_id' => $account_id,
            'total_capacity' => $total_capacity,
            'current_usage' => $current_usage,
            'available_capacity' => $available_capacity,
            'requested_capacity' => $requested_capacity
        ) );

        // Check if sufficient capacity available
        if ( $available_capacity < $requested_capacity ) {
            return new WP_Error(
                'insufficient_capacity',
                sprintf(
                    __( 'Account capacity exceeded. Available: %d, Requested: %d', 'vd-license-manager' ),
                    $available_capacity,
                    $requested_capacity
                )
            );
        }

        // Check if assignment would trigger warning threshold
        $new_usage_percentage = ( ( $current_usage + $requested_capacity ) / $total_capacity ) * 100;

        if ( $new_usage_percentage >= self::CAPACITY_WARNING_THRESHOLD ) {
            VD_LM_Logger_Service::warning( 'Account approaching capacity limit', array(
                'account_id' => $account_id,
                'usage_percentage' => round( $new_usage_percentage, 2 ),
                'threshold' => self::CAPACITY_WARNING_THRESHOLD
            ) );

            // Fire hook for capacity warning notifications
            do_action( 'vd_account_capacity_warning', $account_id, $new_usage_percentage );
        }

        return true;
    }

    /**
     * Atomically reserve capacity for license assignment
     *
     * Uses optimistic locking to prevent race conditions when multiple
     * licenses are being assigned simultaneously.
     *
     * @since 1.1.0
     * @param int $account_id Account ID
     * @param int $capacity_needed Number of licenses to reserve
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function reserve_account_capacity( $account_id, $capacity_needed = 1 ) {
        global $wpdb;

        $account_id = absint( $account_id );
        $capacity_needed = absint( $capacity_needed );

        // Pre-check capacity before attempting reservation
        $capacity_check = $this->check_account_capacity( $account_id, $capacity_needed );
        if ( is_wp_error( $capacity_check ) ) {
            return $capacity_check;
        }

        // Attempt atomic reservation with retry logic
        for ( $attempt = 1; $attempt <= self::MAX_RETRY_ATTEMPTS; $attempt++ ) {
            $reservation_result = $this->attempt_capacity_reservation( $account_id, $capacity_needed );

            if ( is_wp_error( $reservation_result ) ) {
                // Check if it's a recoverable error (race condition)
                $error_code = $reservation_result->get_error_code();

                if ( $error_code === 'capacity_race_condition' && $attempt < self::MAX_RETRY_ATTEMPTS ) {
                    VD_LM_Logger_Service::debug( 'Capacity reservation retry', array(
                        'account_id' => $account_id,
                        'attempt' => $attempt,
                        'max_attempts' => self::MAX_RETRY_ATTEMPTS
                    ) );

                    // Brief delay before retry
                    usleep( 100000 ); // 100ms
                    continue;
                }

                // Non-recoverable error or max attempts reached
                return $reservation_result;
            }

            // Success
            VD_LM_Logger_Service::info( 'Account capacity reserved successfully', array(
                'account_id' => $account_id,
                'capacity_reserved' => $capacity_needed,
                'attempt' => $attempt
            ) );

            return true;
        }

        // Should not reach here, but handle edge case
        return new WP_Error(
            'reservation_failed',
            __( 'Failed to reserve account capacity after maximum retry attempts', 'vd-license-manager' )
        );
    }

    /**
     * Release reserved capacity (rollback)
     *
     * Used when license assignment fails after capacity reservation.
     *
     * @since 1.1.0
     * @param int $account_id Account ID
     * @param int $capacity_to_release Number of licenses to release
     * @return bool Success
     */
    public function release_account_capacity( $account_id, $capacity_to_release = 1 ) {
        global $wpdb;

        $account_id = absint( $account_id );
        $capacity_to_release = absint( $capacity_to_release );

        if ( $account_id <= 0 || $capacity_to_release <= 0 ) {
            return false;
        }

        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';

        // Decrease current usage (don't allow negative values)
        $result = $wpdb->query( $wpdb->prepare(
            "UPDATE {$accounts_table}
            SET current_usage = GREATEST(0, current_usage - %d),
                updated_at = NOW()
            WHERE id = %d",
            $capacity_to_release,
            $account_id
        ) );

        if ( $result !== false ) {
            VD_LM_Logger_Service::info( 'Account capacity released', array(
                'account_id' => $account_id,
                'capacity_released' => $capacity_to_release
            ) );

            return true;
        }

        VD_LM_Logger_Service::error( 'Failed to release account capacity', array(
            'account_id' => $account_id,
            'capacity_to_release' => $capacity_to_release,
            'db_error' => $wpdb->last_error
        ) );

        return false;
    }

    /**
     * Get pool capacity statistics
     *
     * @since 1.1.0
     * @param int $pool_id Pool ID
     * @return array|WP_Error Pool capacity statistics
     */
    public function get_pool_capacity_stats( $pool_id ) {
        global $wpdb;

        $pool_id = absint( $pool_id );

        if ( $pool_id <= 0 ) {
            return new WP_Error(
                'invalid_pool_id',
                __( 'Invalid pool ID provided', 'vd-license-manager' )
            );
        }

        $pool_accounts_table = $wpdb->prefix . 'vd_pool_accounts';
        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        $stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                p.id as pool_id,
                p.name as pool_name,
                p.status as pool_status,
                COUNT(pa.account_id) as total_accounts,
                SUM(CASE WHEN a.status = 'active' THEN 1 ELSE 0 END) as active_accounts,
                SUM(CASE WHEN a.status = 'active' THEN a.capacity ELSE 0 END) as total_capacity,
                SUM(CASE WHEN a.status = 'active' THEN a.current_usage ELSE 0 END) as current_usage,
                COALESCE(
                    (SELECT COUNT(*)
                     FROM {$licenses_table} l
                     WHERE l.pool_id = p.id
                     AND l.status = 'active'),
                    0
                ) as assigned_licenses
            FROM {$wpdb->prefix}vd_pools p
            LEFT JOIN {$pool_accounts_table} pa ON p.id = pa.pool_id AND pa.status = 'active'
            LEFT JOIN {$accounts_table} a ON pa.account_id = a.id
            WHERE p.id = %d
            GROUP BY p.id, p.name, p.status",
            $pool_id
        ), ARRAY_A );

        if ( ! $stats ) {
            return new WP_Error(
                'pool_not_found',
                __( 'Pool not found', 'vd-license-manager' )
            );
        }

        // Calculate derived metrics
        $total_capacity = intval( $stats['total_capacity'] );
        $current_usage = intval( $stats['current_usage'] );
        $available_capacity = max( 0, $total_capacity - $current_usage );
        $utilization_percentage = $total_capacity > 0 ? ( $current_usage / $total_capacity ) * 100 : 0;

        $stats['available_capacity'] = $available_capacity;
        $stats['utilization_percentage'] = round( $utilization_percentage, 2 );
        $stats['is_at_capacity'] = $available_capacity <= 0;
        $stats['is_near_capacity'] = $utilization_percentage >= self::CAPACITY_WARNING_THRESHOLD;

        return $stats;
    }

    /**
     * Check for accounts with capacity discrepancies
     *
     * Identifies accounts where current_usage doesn't match actual license count.
     * Used for maintenance and debugging.
     *
     * @since 1.1.0
     * @return array Accounts with discrepancies
     */
    public function audit_capacity_discrepancies() {
        global $wpdb;

        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        $discrepancies = $wpdb->get_results(
            "SELECT
                a.id,
                a.account_login,
                a.provider,
                a.capacity,
                a.current_usage as recorded_usage,
                COALESCE(l.actual_usage, 0) as actual_usage,
                (a.current_usage - COALESCE(l.actual_usage, 0)) as discrepancy
            FROM {$accounts_table} a
            LEFT JOIN (
                SELECT
                    assigned_account_id,
                    COUNT(*) as actual_usage
                FROM {$licenses_table}
                WHERE status = 'active'
                AND assigned_account_id IS NOT NULL
                GROUP BY assigned_account_id
            ) l ON a.id = l.assigned_account_id
            WHERE a.status = 'active'
            AND (a.current_usage != COALESCE(l.actual_usage, 0))
            ORDER BY ABS(a.current_usage - COALESCE(l.actual_usage, 0)) DESC",
            ARRAY_A
        );

        VD_LM_Logger_Service::info( 'Capacity audit completed', array(
            'discrepancies_found' => count( $discrepancies )
        ) );

        return $discrepancies;
    }

    /**
     * Get account with current usage calculation
     *
     * @since 1.1.0
     * @access private
     * @param int $account_id Account ID
     * @return object|null Account data with current usage
     */
    private function get_account_with_usage( $account_id ) {
        global $wpdb;

        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT
                a.*,
                COALESCE(l.license_count, 0) as current_usage
            FROM {$accounts_table} a
            LEFT JOIN (
                SELECT
                    assigned_account_id,
                    COUNT(*) as license_count
                FROM {$licenses_table}
                WHERE status = 'active'
                AND assigned_account_id IS NOT NULL
                GROUP BY assigned_account_id
            ) l ON a.id = l.assigned_account_id
            WHERE a.id = %d
            AND a.status = 'active'",
            $account_id
        ) );
    }

    /**
     * Attempt atomic capacity reservation
     *
     * @since 1.1.0
     * @access private
     * @param int $account_id Account ID
     * @param int $capacity_needed Capacity to reserve
     * @return bool|WP_Error Success or error
     */
    private function attempt_capacity_reservation( $account_id, $capacity_needed ) {
        global $wpdb;

        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';

        // Atomic update with capacity check
        $result = $wpdb->query( $wpdb->prepare(
            "UPDATE {$accounts_table}
            SET current_usage = current_usage + %d,
                updated_at = NOW()
            WHERE id = %d
            AND status = 'active'
            AND (current_usage + %d) <= capacity
            AND (expires_at IS NULL OR expires_at > NOW())",
            $capacity_needed,
            $account_id,
            $capacity_needed
        ) );

        if ( $result === 0 ) {
            // No rows affected - capacity exceeded or account changed
            $current_account = $this->get_account_with_usage( $account_id );

            if ( ! $current_account ) {
                return new WP_Error(
                    'account_unavailable',
                    __( 'Account is no longer available for assignment', 'vd-license-manager' )
                );
            }

            $available = intval( $current_account->capacity ) - intval( $current_account->current_usage );

            if ( $available < $capacity_needed ) {
                return new WP_Error(
                    'capacity_race_condition',
                    sprintf(
                        __( 'Capacity no longer available (race condition). Available: %d, Needed: %d', 'vd-license-manager' ),
                        $available,
                        $capacity_needed
                    )
                );
            }

            return new WP_Error(
                'reservation_failed',
                __( 'Failed to reserve capacity due to account constraints', 'vd-license-manager' )
            );
        }

        if ( $result === false ) {
            return new WP_Error(
                'database_error',
                sprintf( __( 'Database error during capacity reservation: %s', 'vd-license-manager' ), $wpdb->last_error )
            );
        }

        return true;
    }
}
?>
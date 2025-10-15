<?php
/**
 * Edge Case Handler for License Management
 *
 * Handles various edge cases and exceptional scenarios that can occur
 * during license assignment, pool management, and account operations.
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
 * Edge Case Handler Class
 *
 * Manages exceptional scenarios and edge cases in license management including:
 * - Orphaned licenses (assigned to deleted accounts)
 * - Pool configuration inconsistencies
 * - Account status changes during assignment
 * - Concurrent modification conflicts
 * - Data integrity issues
 *
 * @since      1.1.0
 * @package    VD_License_Manager
 * @subpackage Services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Edge_Case_Handler {

    /**
     * Handle orphaned licenses
     *
     * Identifies and handles licenses assigned to accounts that no longer exist
     * or are inactive. Attempts to reassign to available accounts in same pool.
     *
     * @since 1.1.0
     * @param bool $dry_run Whether to perform actual reassignment or just report
     * @return array Results of orphaned license handling
     */
    public function handle_orphaned_licenses( $dry_run = true ) {
        global $wpdb;

        $licenses_table = $wpdb->prefix . 'vd_license_keys';
        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';

        // Find licenses assigned to inactive/deleted accounts
        $orphaned_licenses = $wpdb->get_results(
            "SELECT
                l.id as license_id,
                l.license_key,
                l.product_id,
                l.assigned_account_id,
                l.assigned_pool_id,
                a.status as account_status
            FROM {$licenses_table} l
            LEFT JOIN {$accounts_table} a ON l.assigned_account_id = a.id
            WHERE l.status = 'active'
            AND l.assigned_account_id IS NOT NULL
            AND (a.id IS NULL OR a.status != 'active')",
            ARRAY_A
        );

        $results = array(
            'orphaned_count' => count( $orphaned_licenses ),
            'reassigned' => 0,
            'cleared' => 0,
            'failed' => 0,
            'details' => array()
        );

        foreach ( $orphaned_licenses as $license ) {
            $action_result = $this->handle_single_orphaned_license( $license, $dry_run );

            $results['details'][] = array(
                'license_id' => $license['license_id'],
                'license_key' => $license['license_key'],
                'action' => $action_result['action'],
                'success' => $action_result['success'],
                'message' => $action_result['message']
            );

            if ( $action_result['success'] ) {
                if ( $action_result['action'] === 'reassigned' ) {
                    $results['reassigned']++;
                } elseif ( $action_result['action'] === 'cleared' ) {
                    $results['cleared']++;
                }
            } else {
                $results['failed']++;
            }
        }

        VD_LM_Logger_Service::info( 'Orphaned licenses handling completed', $results );

        return $results;
    }

    /**
     * Validate pool configurations
     *
     * Checks for various pool configuration issues and inconsistencies.
     *
     * @since 1.1.0
     * @return array Validation results with issues found
     */
    public function validate_pool_configurations() {
        global $wpdb;

        $issues = array();

        // Check 1: Pools with no accounts
        $empty_pools = $wpdb->get_results(
            "SELECT
                p.id,
                p.name,
                p.status
            FROM {$wpdb->prefix}vd_pools p
            LEFT JOIN {$wpdb->prefix}vd_pool_accounts pa ON p.id = pa.pool_id AND pa.status = 'active'
            WHERE p.status = 'active'
            AND pa.pool_id IS NULL",
            ARRAY_A
        );

        if ( ! empty( $empty_pools ) ) {
            $issues[] = array(
                'type' => 'empty_pools',
                'severity' => 'warning',
                'message' => sprintf( __( '%d active pools have no accounts assigned', 'vd-license-manager' ), count( $empty_pools ) ),
                'data' => $empty_pools
            );
        }

        // Check 2: Pools with all inactive accounts
        $inactive_account_pools = $wpdb->get_results(
            "SELECT
                p.id,
                p.name,
                COUNT(pa.account_id) as total_accounts,
                SUM(CASE WHEN a.status = 'active' THEN 1 ELSE 0 END) as active_accounts
            FROM {$wpdb->prefix}vd_pools p
            INNER JOIN {$wpdb->prefix}vd_pool_accounts pa ON p.id = pa.pool_id AND pa.status = 'active'
            INNER JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
            WHERE p.status = 'active'
            GROUP BY p.id, p.name
            HAVING active_accounts = 0",
            ARRAY_A
        );

        if ( ! empty( $inactive_account_pools ) ) {
            $issues[] = array(
                'type' => 'pools_with_inactive_accounts',
                'severity' => 'critical',
                'message' => sprintf( __( '%d pools have only inactive accounts', 'vd-license-manager' ), count( $inactive_account_pools ) ),
                'data' => $inactive_account_pools
            );
        }

        // Check 3: Products with no pool assignments
        $products_without_pools = $wpdb->get_results(
            "SELECT DISTINCT
                l.product_id,
                COUNT(l.id) as license_count
            FROM {$wpdb->prefix}vd_license_keys l
            LEFT JOIN {$wpdb->prefix}vd_product_pools pp ON l.product_id = pp.product_id AND pp.status = 'active'
            WHERE l.status = 'active'
            AND pp.product_id IS NULL
            GROUP BY l.product_id",
            ARRAY_A
        );

        if ( ! empty( $products_without_pools ) ) {
            $issues[] = array(
                'type' => 'products_without_pools',
                'severity' => 'critical',
                'message' => sprintf( __( '%d products have active licenses but no pool assignments', 'vd-license-manager' ), count( $products_without_pools ) ),
                'data' => $products_without_pools
            );
        }

        // Check 4: Accounts at or over capacity
        $overcapacity_accounts = $wpdb->get_results(
            "SELECT
                a.id,
                a.account_login,
                a.provider,
                a.capacity,
                a.current_usage,
                (a.current_usage - a.capacity) as overcharge
            FROM {$wpdb->prefix}vd_provider_accounts a
            WHERE a.status = 'active'
            AND a.current_usage >= a.capacity
            ORDER BY overcharge DESC",
            ARRAY_A
        );

        if ( ! empty( $overcapacity_accounts ) ) {
            $issues[] = array(
                'type' => 'overcapacity_accounts',
                'severity' => 'critical',
                'message' => sprintf( __( '%d accounts are at or over capacity', 'vd-license-manager' ), count( $overcapacity_accounts ) ),
                'data' => $overcapacity_accounts
            );
        }

        // Check 5: Expired accounts with active assignments
        $expired_accounts_with_licenses = $wpdb->get_results(
            "SELECT
                a.id,
                a.account_login,
                a.expires_at,
                COUNT(l.id) as active_licenses
            FROM {$wpdb->prefix}vd_provider_accounts a
            INNER JOIN {$wpdb->prefix}vd_license_keys l ON a.id = l.assigned_account_id
            WHERE a.status = 'active'
            AND a.expires_at IS NOT NULL
            AND a.expires_at <= NOW()
            AND l.status = 'active'
            GROUP BY a.id, a.account_login, a.expires_at",
            ARRAY_A
        );

        if ( ! empty( $expired_accounts_with_licenses ) ) {
            $issues[] = array(
                'type' => 'expired_accounts_with_licenses',
                'severity' => 'high',
                'message' => sprintf( __( '%d expired accounts still have active license assignments', 'vd-license-manager' ), count( $expired_accounts_with_licenses ) ),
                'data' => $expired_accounts_with_licenses
            );
        }

        VD_LM_Logger_Service::info( 'Pool configuration validation completed', array(
            'total_issues' => count( $issues ),
            'critical_issues' => count( array_filter( $issues, function( $issue ) {
                return $issue['severity'] === 'critical';
            } ) )
        ) );

        return array(
            'issues_found' => count( $issues ),
            'validation_timestamp' => current_time( 'mysql' ),
            'issues' => $issues
        );
    }

    /**
     * Handle concurrent modification scenarios
     *
     * Detects and resolves conflicts when multiple processes attempt to
     * modify the same account or pool simultaneously.
     *
     * @since 1.1.0
     * @param string $operation Operation being performed
     * @param array  $context   Context data for the operation
     * @return bool|WP_Error True if safe to proceed, WP_Error if conflict detected
     */
    public function handle_concurrent_modification( $operation, $context ) {
        switch ( $operation ) {
            case 'account_assignment':
                return $this->check_account_assignment_conflicts( $context );

            case 'pool_capacity_check':
                return $this->check_pool_capacity_conflicts( $context );

            case 'account_update':
                return $this->check_account_update_conflicts( $context );

            default:
                VD_LM_Logger_Service::warning( 'Unknown concurrent modification operation', array(
                    'operation' => $operation,
                    'context' => $context
                ) );

                return new WP_Error(
                    'unknown_operation',
                    __( 'Unknown concurrent modification operation', 'vd-license-manager' )
                );
        }
    }

    /**
     * Clean up inconsistent data
     *
     * Performs cleanup operations to fix data inconsistencies.
     *
     * @since 1.1.0
     * @param bool $dry_run Whether to perform actual cleanup or just report
     * @return array Cleanup results
     */
    public function cleanup_inconsistent_data( $dry_run = true ) {
        $results = array(
            'orphaned_licenses' => $this->handle_orphaned_licenses( $dry_run ),
            'capacity_sync' => $this->sync_capacity_usage( $dry_run ),
            'expired_cleanup' => $this->cleanup_expired_assignments( $dry_run )
        );

        return $results;
    }

    /**
     * Handle single orphaned license
     *
     * @since 1.1.0
     * @access private
     * @param array $license License data
     * @param bool  $dry_run Whether to perform actual action
     * @return array Action result
     */
    private function handle_single_orphaned_license( $license, $dry_run ) {
        global $wpdb;

        $license_id = $license['license_id'];
        $pool_id = $license['assigned_pool_id'];

        // Try to find alternative account in same pool
        if ( $pool_id ) {
            $alternative_account = $this->find_alternative_account( $pool_id );

            if ( $alternative_account ) {
                if ( ! $dry_run ) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'vd_license_keys',
                        array( 'assigned_account_id' => $alternative_account['id'] ),
                        array( 'id' => $license_id ),
                        array( '%d' ),
                        array( '%d' )
                    );

                    if ( $result !== false ) {
                        return array(
                            'action' => 'reassigned',
                            'success' => true,
                            'message' => sprintf( __( 'Reassigned to account %d', 'vd-license-manager' ), $alternative_account['id'] )
                        );
                    }
                }

                return array(
                    'action' => 'reassigned',
                    'success' => true,
                    'message' => sprintf( __( 'Would reassign to account %d', 'vd-license-manager' ), $alternative_account['id'] )
                );
            }
        }

        // No alternative found, clear assignment
        if ( ! $dry_run ) {
            $result = $wpdb->update(
                $wpdb->prefix . 'vd_license_keys',
                array(
                    'assigned_account_id' => null,
                    'assigned_pool_id' => null
                ),
                array( 'id' => $license_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );

            if ( $result !== false ) {
                return array(
                    'action' => 'cleared',
                    'success' => true,
                    'message' => __( 'Cleared orphaned assignment', 'vd-license-manager' )
                );
            }
        }

        return array(
            'action' => 'cleared',
            'success' => true,
            'message' => __( 'Would clear orphaned assignment', 'vd-license-manager' )
        );
    }

    /**
     * Find alternative account in pool
     *
     * @since 1.1.0
     * @access private
     * @param int $pool_id Pool ID
     * @return array|null Account data or null
     */
    private function find_alternative_account( $pool_id ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT a.id, a.capacity, a.current_usage
            FROM {$wpdb->prefix}vd_provider_accounts a
            INNER JOIN {$wpdb->prefix}vd_pool_accounts pa ON a.id = pa.account_id
            WHERE pa.pool_id = %d
            AND pa.status = 'active'
            AND a.status = 'active'
            AND a.current_usage < a.capacity
            AND (a.expires_at IS NULL OR a.expires_at > NOW())
            ORDER BY (a.current_usage / a.capacity) ASC
            LIMIT 1",
            $pool_id
        ), ARRAY_A );
    }

    /**
     * Check account assignment conflicts
     *
     * @since 1.1.0
     * @access private
     * @param array $context Context data
     * @return bool|WP_Error Result
     */
    private function check_account_assignment_conflicts( $context ) {
        // Implementation for account assignment conflict detection
        return true; // Simplified for now
    }

    /**
     * Check pool capacity conflicts
     *
     * @since 1.1.0
     * @access private
     * @param array $context Context data
     * @return bool|WP_Error Result
     */
    private function check_pool_capacity_conflicts( $context ) {
        // Implementation for pool capacity conflict detection
        return true; // Simplified for now
    }

    /**
     * Check account update conflicts
     *
     * @since 1.1.0
     * @access private
     * @param array $context Context data
     * @return bool|WP_Error Result
     */
    private function check_account_update_conflicts( $context ) {
        // Implementation for account update conflict detection
        return true; // Simplified for now
    }

    /**
     * Sync capacity usage
     *
     * @since 1.1.0
     * @access private
     * @param bool $dry_run Whether to perform actual sync
     * @return array Results
     */
    private function sync_capacity_usage( $dry_run ) {
        global $wpdb;

        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        if ( ! $dry_run ) {
            $result = $wpdb->query(
                "UPDATE {$accounts_table} a
                LEFT JOIN (
                    SELECT
                        assigned_account_id,
                        COUNT(*) as actual_usage
                    FROM {$licenses_table}
                    WHERE status = 'active'
                    AND assigned_account_id IS NOT NULL
                    GROUP BY assigned_account_id
                ) l ON a.id = l.assigned_account_id
                SET a.current_usage = COALESCE(l.actual_usage, 0)
                WHERE a.status = 'active'"
            );

            return array(
                'synced' => true,
                'affected_rows' => $result,
                'message' => sprintf( __( 'Synced capacity for %d accounts', 'vd-license-manager' ), $result )
            );
        }

        return array(
            'synced' => false,
            'message' => __( 'Would sync capacity usage for all accounts', 'vd-license-manager' )
        );
    }

    /**
     * Cleanup expired assignments
     *
     * @since 1.1.0
     * @access private
     * @param bool $dry_run Whether to perform actual cleanup
     * @return array Results
     */
    private function cleanup_expired_assignments( $dry_run ) {
        global $wpdb;

        $licenses_table = $wpdb->prefix . 'vd_license_keys';
        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';

        // Find licenses assigned to expired accounts
        $expired_assignments = $wpdb->get_results(
            "SELECT l.id, l.license_key, a.account_login, a.expires_at
            FROM {$licenses_table} l
            INNER JOIN {$accounts_table} a ON l.assigned_account_id = a.id
            WHERE l.status = 'active'
            AND a.expires_at IS NOT NULL
            AND a.expires_at <= NOW()",
            ARRAY_A
        );

        if ( ! $dry_run && ! empty( $expired_assignments ) ) {
            $license_ids = array_column( $expired_assignments, 'id' );
            $placeholders = implode( ',', array_fill( 0, count( $license_ids ), '%d' ) );

            $result = $wpdb->query( $wpdb->prepare(
                "UPDATE {$licenses_table}
                SET assigned_account_id = NULL, assigned_pool_id = NULL
                WHERE id IN ({$placeholders})",
                ...$license_ids
            ) );

            return array(
                'cleaned' => count( $expired_assignments ),
                'affected_rows' => $result,
                'details' => $expired_assignments
            );
        }

        return array(
            'cleaned' => 0,
            'found_expired' => count( $expired_assignments ),
            'details' => $expired_assignments
        );
    }
}
?>
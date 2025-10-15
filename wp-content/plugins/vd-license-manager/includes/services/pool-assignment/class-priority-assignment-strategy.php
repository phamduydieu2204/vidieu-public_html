<?php
/**
 * Priority Assignment Strategy
 *
 * Implements priority-based pool assignment following FLOW_2 specification.
 * Assigns pools in order of priority (lower number = higher priority).
 *
 * @package    VD_License_Manager
 * @subpackage Pool_Assignment
 * @since      1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Priority Assignment Strategy Class
 *
 * Selects pools based on priority order (ASC).
 * First pool with available capacity is selected.
 *
 * Algorithm:
 * 1. Sort pools by priority ASC (1, 2, 3, ...)
 * 2. For each pool in order:
 *    - Check if pool has available capacity
 *    - Check if pool has active accounts
 *    - Return first pool meeting criteria
 * 3. Return null if no pools available
 *
 * @since      1.1.0
 * @package    VD_License_Manager
 * @subpackage Pool_Assignment
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Priority_Assignment_Strategy implements VD_Pool_Assignment_Strategy {

    /**
     * Strategy name
     *
     * @since 1.1.0
     * @var string
     */
    const STRATEGY_NAME = 'priority';

    /**
     * Default minimum capacity threshold
     *
     * @since 1.1.0
     * @var int
     */
    const DEFAULT_MIN_CAPACITY = 1;

    /**
     * Select pool using priority strategy
     *
     * @since 1.1.0
     * @param int   $product_id      Product ID
     * @param array $available_pools Available pools with capacity data
     * @return array|null Selected pool or null
     */
    public function select_pool( $product_id, $available_pools ) {
        VD_LM_Logger_Service::info( 'Priority strategy: Starting pool selection', array(
            'product_id' => $product_id,
            'available_pools_count' => count( $available_pools ),
            'strategy' => self::STRATEGY_NAME
        ) );

        // Validate input
        $validation_result = $this->validate_pools( $available_pools );
        if ( is_wp_error( $validation_result ) ) {
            VD_LM_Logger_Service::error( 'Priority strategy: Pool validation failed', array(
                'error' => $validation_result->get_error_message(),
                'product_id' => $product_id
            ) );
            return null;
        }

        if ( empty( $available_pools ) ) {
            VD_LM_Logger_Service::warning( 'Priority strategy: No pools provided', array(
                'product_id' => $product_id
            ) );
            return null;
        }

        // Sort pools by priority (ASC - lower number = higher priority)
        $sorted_pools = $this->sort_pools_by_priority( $available_pools );

        VD_LM_Logger_Service::debug( 'Priority strategy: Pools sorted by priority', array(
            'sorted_pools' => array_map( function( $pool ) {
                return array(
                    'pool_id' => $pool['pool_id'],
                    'priority' => $pool['priority'],
                    'capacity' => $pool['capacity'],
                    'assigned' => $pool['assigned_count']
                );
            }, $sorted_pools )
        ) );

        // Select first pool with available capacity
        foreach ( $sorted_pools as $pool ) {
            $pool_score = $this->calculate_pool_score( $pool, $product_id );

            VD_LM_Logger_Service::debug( 'Priority strategy: Evaluating pool', array(
                'pool_id' => $pool['pool_id'],
                'priority' => $pool['priority'],
                'capacity' => $pool['capacity'],
                'assigned' => $pool['assigned_count'],
                'available' => $pool['capacity'] - $pool['assigned_count'],
                'score' => $pool_score
            ) );

            // Check if pool has available capacity
            if ( $pool_score > 0 ) {
                VD_LM_Logger_Service::info( 'Priority strategy: Pool selected', array(
                    'selected_pool_id' => $pool['pool_id'],
                    'priority' => $pool['priority'],
                    'available_capacity' => $pool['capacity'] - $pool['assigned_count'],
                    'product_id' => $product_id
                ) );

                return $pool;
            }

            VD_LM_Logger_Service::debug( 'Priority strategy: Pool skipped (no capacity)', array(
                'pool_id' => $pool['pool_id'],
                'priority' => $pool['priority'],
                'reason' => 'insufficient_capacity'
            ) );
        }

        // No pools available
        VD_LM_Logger_Service::warning( 'Priority strategy: No pools with available capacity', array(
            'product_id' => $product_id,
            'pools_evaluated' => count( $sorted_pools )
        ) );

        return null;
    }

    /**
     * Get strategy name
     *
     * @since 1.1.0
     * @return string Strategy name
     */
    public function get_strategy_name() {
        return self::STRATEGY_NAME;
    }

    /**
     * Validate pools array structure
     *
     * @since 1.1.0
     * @param array $pools Pools to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_pools( $pools ) {
        if ( ! is_array( $pools ) ) {
            return new WP_Error(
                'invalid_pools_type',
                __( 'Pools must be an array', 'vd-license-manager' )
            );
        }

        foreach ( $pools as $index => $pool ) {
            if ( ! is_array( $pool ) ) {
                return new WP_Error(
                    'invalid_pool_type',
                    sprintf( __( 'Pool at index %d must be an array', 'vd-license-manager' ), $index )
                );
            }

            // Check required fields
            $required_fields = array( 'pool_id', 'priority', 'capacity', 'assigned_count', 'status' );

            foreach ( $required_fields as $field ) {
                if ( ! isset( $pool[ $field ] ) ) {
                    return new WP_Error(
                        'missing_pool_field',
                        sprintf( __( 'Pool at index %d missing required field: %s', 'vd-license-manager' ), $index, $field )
                    );
                }
            }

            // Validate data types
            if ( ! is_numeric( $pool['pool_id'] ) || $pool['pool_id'] <= 0 ) {
                return new WP_Error(
                    'invalid_pool_id',
                    sprintf( __( 'Pool at index %d has invalid pool_id', 'vd-license-manager' ), $index )
                );
            }

            if ( ! is_numeric( $pool['priority'] ) || $pool['priority'] < 0 ) {
                return new WP_Error(
                    'invalid_priority',
                    sprintf( __( 'Pool at index %d has invalid priority', 'vd-license-manager' ), $index )
                );
            }

            if ( ! is_numeric( $pool['capacity'] ) || $pool['capacity'] < 0 ) {
                return new WP_Error(
                    'invalid_capacity',
                    sprintf( __( 'Pool at index %d has invalid capacity', 'vd-license-manager' ), $index )
                );
            }

            if ( ! is_numeric( $pool['assigned_count'] ) || $pool['assigned_count'] < 0 ) {
                return new WP_Error(
                    'invalid_assigned_count',
                    sprintf( __( 'Pool at index %d has invalid assigned_count', 'vd-license-manager' ), $index )
                );
            }

            if ( ! in_array( $pool['status'], array( 'active', 'inactive', 'full' ), true ) ) {
                return new WP_Error(
                    'invalid_status',
                    sprintf( __( 'Pool at index %d has invalid status', 'vd-license-manager' ), $index )
                );
            }
        }

        return true;
    }

    /**
     * Get configuration options for priority strategy
     *
     * @since 1.1.0
     * @return array Configuration options
     */
    public function get_config_options() {
        return array(
            'name' => __( 'Priority Assignment', 'vd-license-manager' ),
            'description' => __( 'Assigns licenses to pools in priority order (1 = highest priority)', 'vd-license-manager' ),
            'settings' => array(
                'min_capacity_threshold' => array(
                    'type' => 'number',
                    'label' => __( 'Minimum Capacity Threshold', 'vd-license-manager' ),
                    'description' => __( 'Minimum available capacity required to select a pool', 'vd-license-manager' ),
                    'default' => self::DEFAULT_MIN_CAPACITY,
                    'min' => 1,
                    'max' => 100
                ),
                'skip_full_pools' => array(
                    'type' => 'checkbox',
                    'label' => __( 'Skip Full Pools', 'vd-license-manager' ),
                    'description' => __( 'Automatically skip pools that are at full capacity', 'vd-license-manager' ),
                    'default' => true
                )
            )
        );
    }

    /**
     * Calculate pool priority score
     *
     * For priority strategy, score is simply based on available capacity.
     * Higher score = better choice for assignment.
     *
     * @since 1.1.0
     * @param array $pool Pool data
     * @param int   $product_id Product ID (unused in priority strategy)
     * @return float Priority score
     */
    public function calculate_pool_score( $pool, $product_id ) {
        // Basic validation
        if ( empty( $pool ) || ! isset( $pool['capacity'], $pool['assigned_count'], $pool['status'] ) ) {
            return 0;
        }

        // Only consider active pools
        if ( $pool['status'] !== 'active' ) {
            return 0;
        }

        // Calculate available capacity
        $available_capacity = intval( $pool['capacity'] ) - intval( $pool['assigned_count'] );

        // Return available capacity as score
        // Positive score = pool can accept assignments
        // Zero or negative score = pool is full or over-capacity
        return max( 0, $available_capacity );
    }

    /**
     * Sort pools by priority (ascending)
     *
     * @since 1.1.0
     * @access private
     * @param array $pools Pools to sort
     * @return array Sorted pools
     */
    private function sort_pools_by_priority( $pools ) {
        // Create a copy to avoid modifying original array
        $sorted_pools = $pools;

        // Sort by priority ASC (lower number = higher priority)
        usort( $sorted_pools, function( $a, $b ) {
            $priority_a = intval( $a['priority'] );
            $priority_b = intval( $b['priority'] );

            if ( $priority_a === $priority_b ) {
                // If same priority, prefer pool with more available capacity
                $available_a = intval( $a['capacity'] ) - intval( $a['assigned_count'] );
                $available_b = intval( $b['capacity'] ) - intval( $b['assigned_count'] );

                return $available_b - $available_a; // DESC for available capacity
            }

            return $priority_a - $priority_b; // ASC for priority
        } );

        return $sorted_pools;
    }

    /**
     * Get pools by priority for a product
     *
     * This is a helper method that can be used by the pool assignment service
     * to retrieve pools in the correct order for priority-based assignment.
     *
     * @since 1.1.0
     * @param int $product_id Product ID
     * @return array Pools sorted by priority
     */
    public function get_pools_by_priority( $product_id ) {
        global $wpdb;

        $pools_table = $wpdb->prefix . 'vd_pools';
        $product_pools_table = $wpdb->prefix . 'vd_product_pools';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        // Get pools with capacity calculation
        $pools = $wpdb->get_results( $wpdb->prepare( "
            SELECT
                p.id as pool_id,
                p.name as pool_name,
                p.status,
                pp.priority,
                COALESCE(
                    (SELECT SUM(a.capacity)
                     FROM {$wpdb->prefix}vd_pool_accounts pa
                     JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
                     WHERE pa.pool_id = p.id
                     AND pa.status = 'active'
                     AND a.status = 'active'),
                    0
                ) as capacity,
                COALESCE(
                    (SELECT COUNT(*)
                     FROM {$licenses_table} l
                     WHERE l.pool_id = p.id
                     AND l.status = 'active'),
                    0
                ) as assigned_count
            FROM {$pools_table} p
            INNER JOIN {$product_pools_table} pp ON p.id = pp.pool_id
            WHERE pp.product_id = %d
            AND pp.status = 'active'
            AND p.status = 'active'
            ORDER BY pp.priority ASC
        ", $product_id ), ARRAY_A );

        VD_LM_Logger_Service::debug( 'Priority strategy: Retrieved pools from database', array(
            'product_id' => $product_id,
            'pools_count' => count( $pools ),
            'pools' => $pools
        ) );

        return $pools;
    }
}
?>
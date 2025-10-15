<?php
/**
 * Balanced Assignment Strategy
 *
 * Implements load-balanced pool assignment following FLOW_2 specification.
 * Distributes licenses evenly across pools to optimize resource utilization.
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
 * Balanced Assignment Strategy Class
 *
 * Selects pools based on current load to achieve balanced distribution.
 * Pools with lower utilization are preferred.
 *
 * Algorithm:
 * 1. Calculate utilization percentage for each pool
 * 2. Sort pools by utilization ASC (least loaded first)
 * 3. Within same utilization, sort by priority ASC
 * 4. Return first pool with available capacity
 *
 * @since      1.1.0
 * @package    VD_License_Manager
 * @subpackage Pool_Assignment
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Balanced_Assignment_Strategy implements VD_Pool_Assignment_Strategy {

    /**
     * Strategy name
     *
     * @since 1.1.0
     * @var string
     */
    const STRATEGY_NAME = 'balanced';

    /**
     * Default load balancing weight
     *
     * @since 1.1.0
     * @var float
     */
    const DEFAULT_LOAD_WEIGHT = 0.7;

    /**
     * Default priority weight
     *
     * @since 1.1.0
     * @var float
     */
    const DEFAULT_PRIORITY_WEIGHT = 0.3;

    /**
     * Select pool using balanced strategy
     *
     * @since 1.1.0
     * @param int   $product_id      Product ID
     * @param array $available_pools Available pools with capacity data
     * @return array|null Selected pool or null
     */
    public function select_pool( $product_id, $available_pools ) {
        VD_LM_Logger_Service::info( 'Balanced strategy: Starting pool selection', array(
            'product_id' => $product_id,
            'available_pools_count' => count( $available_pools ),
            'strategy' => self::STRATEGY_NAME
        ) );

        // Validate input
        $validation_result = $this->validate_pools( $available_pools );
        if ( is_wp_error( $validation_result ) ) {
            VD_LM_Logger_Service::error( 'Balanced strategy: Pool validation failed', array(
                'error' => $validation_result->get_error_message(),
                'product_id' => $product_id
            ) );
            return null;
        }

        if ( empty( $available_pools ) ) {
            VD_LM_Logger_Service::warning( 'Balanced strategy: No pools provided', array(
                'product_id' => $product_id
            ) );
            return null;
        }

        // Calculate scores and sort pools
        $scored_pools = $this->calculate_and_sort_pools( $available_pools, $product_id );

        VD_LM_Logger_Service::debug( 'Balanced strategy: Pools scored and sorted', array(
            'scored_pools' => array_map( function( $pool ) {
                return array(
                    'pool_id' => $pool['pool_id'],
                    'priority' => $pool['priority'],
                    'utilization' => $pool['utilization_percent'],
                    'balance_score' => $pool['balance_score'],
                    'available' => $pool['capacity'] - $pool['assigned_count']
                );
            }, array_slice( $scored_pools, 0, 5 ) ) // Log first 5 for brevity
        ) );

        // Select best pool with available capacity
        foreach ( $scored_pools as $pool ) {
            $pool_score = $this->calculate_pool_score( $pool, $product_id );

            VD_LM_Logger_Service::debug( 'Balanced strategy: Evaluating pool', array(
                'pool_id' => $pool['pool_id'],
                'priority' => $pool['priority'],
                'utilization' => $pool['utilization_percent'] . '%',
                'balance_score' => $pool['balance_score'],
                'available_capacity' => $pool['capacity'] - $pool['assigned_count'],
                'final_score' => $pool_score
            ) );

            // Check if pool has available capacity
            if ( $pool_score > 0 ) {
                VD_LM_Logger_Service::info( 'Balanced strategy: Pool selected', array(
                    'selected_pool_id' => $pool['pool_id'],
                    'priority' => $pool['priority'],
                    'utilization' => $pool['utilization_percent'] . '%',
                    'balance_score' => $pool['balance_score'],
                    'available_capacity' => $pool['capacity'] - $pool['assigned_count'],
                    'product_id' => $product_id
                ) );

                return $pool;
            }

            VD_LM_Logger_Service::debug( 'Balanced strategy: Pool skipped (no capacity)', array(
                'pool_id' => $pool['pool_id'],
                'reason' => 'insufficient_capacity'
            ) );
        }

        // No pools available
        VD_LM_Logger_Service::warning( 'Balanced strategy: No pools with available capacity', array(
            'product_id' => $product_id,
            'pools_evaluated' => count( $scored_pools )
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

            // Validate data types and ranges
            if ( ! is_numeric( $pool['pool_id'] ) || $pool['pool_id'] <= 0 ) {
                return new WP_Error(
                    'invalid_pool_id',
                    sprintf( __( 'Pool at index %d has invalid pool_id', 'vd-license-manager' ), $index )
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
        }

        return true;
    }

    /**
     * Get configuration options for balanced strategy
     *
     * @since 1.1.0
     * @return array Configuration options
     */
    public function get_config_options() {
        return array(
            'name' => __( 'Balanced Assignment', 'vd-license-manager' ),
            'description' => __( 'Distributes licenses evenly across pools to optimize resource utilization', 'vd-license-manager' ),
            'settings' => array(
                'load_weight' => array(
                    'type' => 'number',
                    'label' => __( 'Load Balancing Weight', 'vd-license-manager' ),
                    'description' => __( 'How much to prioritize load balancing vs priority (0.0-1.0)', 'vd-license-manager' ),
                    'default' => self::DEFAULT_LOAD_WEIGHT,
                    'min' => 0.0,
                    'max' => 1.0,
                    'step' => 0.1
                ),
                'priority_weight' => array(
                    'type' => 'number',
                    'label' => __( 'Priority Weight', 'vd-license-manager' ),
                    'description' => __( 'How much to prioritize pool priority vs load balancing (0.0-1.0)', 'vd-license-manager' ),
                    'default' => self::DEFAULT_PRIORITY_WEIGHT,
                    'min' => 0.0,
                    'max' => 1.0,
                    'step' => 0.1
                ),
                'prefer_underutilized' => array(
                    'type' => 'checkbox',
                    'label' => __( 'Prefer Underutilized Pools', 'vd-license-manager' ),
                    'description' => __( 'Give bonus points to pools with low utilization', 'vd-license-manager' ),
                    'default' => true
                )
            )
        );
    }

    /**
     * Calculate pool balance score
     *
     * For balanced strategy, score considers both utilization and priority.
     * Lower utilization and higher priority result in higher scores.
     *
     * @since 1.1.0
     * @param array $pool Pool data
     * @param int   $product_id Product ID
     * @return float Balance score
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
        $capacity = intval( $pool['capacity'] );
        $assigned = intval( $pool['assigned_count'] );
        $available = $capacity - $assigned;

        // Must have available capacity
        if ( $available <= 0 ) {
            return 0;
        }

        // Return available capacity as base score
        // This will be enhanced by the balance score calculated in calculate_and_sort_pools
        return $available;
    }

    /**
     * Calculate balance scores and sort pools
     *
     * @since 1.1.0
     * @access private
     * @param array $pools Available pools
     * @param int   $product_id Product ID
     * @return array Sorted pools with balance scores
     */
    private function calculate_and_sort_pools( $pools, $product_id ) {
        $scored_pools = array();

        // Calculate balance scores for each pool
        foreach ( $pools as $pool ) {
            $scored_pool = $pool;

            // Calculate utilization percentage
            $capacity = intval( $pool['capacity'] );
            $assigned = intval( $pool['assigned_count'] );

            if ( $capacity > 0 ) {
                $utilization = ( $assigned / $capacity ) * 100;
            } else {
                $utilization = 0; // Empty pool
            }

            $scored_pool['utilization_percent'] = round( $utilization, 2 );

            // Calculate balance score
            $balance_score = $this->calculate_balance_score( $pool );
            $scored_pool['balance_score'] = $balance_score;

            $scored_pools[] = $scored_pool;
        }

        // Sort pools by balance score (DESC - higher is better)
        usort( $scored_pools, function( $a, $b ) {
            $score_diff = $b['balance_score'] - $a['balance_score'];

            if ( abs( $score_diff ) < 0.01 ) { // Scores are very close
                // Tie-breaker: prefer lower priority (higher priority)
                $priority_a = intval( $a['priority'] );
                $priority_b = intval( $b['priority'] );

                return $priority_a - $priority_b; // ASC for priority
            }

            return $score_diff > 0 ? 1 : -1;
        } );

        return $scored_pools;
    }

    /**
     * Calculate balance score for a single pool
     *
     * @since 1.1.0
     * @access private
     * @param array $pool Pool data
     * @return float Balance score
     */
    private function calculate_balance_score( $pool ) {
        $capacity = intval( $pool['capacity'] );
        $assigned = intval( $pool['assigned_count'] );
        $priority = intval( $pool['priority'] );

        // No capacity means no score
        if ( $capacity <= 0 ) {
            return 0;
        }

        // Calculate utilization (0.0 to 1.0)
        $utilization = $assigned / $capacity;

        // Calculate available capacity ratio (1.0 = full capacity available, 0.0 = no capacity)
        $available_ratio = max( 0, ( $capacity - $assigned ) / $capacity );

        // Load balancing component (higher score for lower utilization)
        $load_component = ( 1.0 - $utilization ) * self::DEFAULT_LOAD_WEIGHT;

        // Priority component (lower priority number = higher score)
        // Normalize priority (assuming priorities 1-10, invert for scoring)
        $normalized_priority = max( 0, ( 11 - $priority ) / 10 );
        $priority_component = $normalized_priority * self::DEFAULT_PRIORITY_WEIGHT;

        // Combine components
        $balance_score = $load_component + $priority_component;

        // Bonus for underutilized pools (< 50% utilization)
        if ( $utilization < 0.5 ) {
            $balance_score += 0.1;
        }

        // Penalty for overutilized pools (> 90% utilization)
        if ( $utilization > 0.9 ) {
            $balance_score -= 0.2;
        }

        // Ensure non-negative score
        return max( 0, $balance_score );
    }

    /**
     * Get pools with load balancing data for a product
     *
     * @since 1.1.0
     * @param int $product_id Product ID
     * @return array Pools with utilization data
     */
    public function get_pools_with_load_data( $product_id ) {
        global $wpdb;

        $pools_table = $wpdb->prefix . 'vd_pools';
        $product_pools_table = $wpdb->prefix . 'vd_product_pools';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        // Get pools with capacity and load calculation
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
                ) as assigned_count,
                ROUND(
                    CASE
                        WHEN COALESCE(
                            (SELECT SUM(a.capacity)
                             FROM {$wpdb->prefix}vd_pool_accounts pa
                             JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
                             WHERE pa.pool_id = p.id
                             AND pa.status = 'active'
                             AND a.status = 'active'),
                            0
                        ) > 0
                        THEN (
                            COALESCE(
                                (SELECT COUNT(*)
                                 FROM {$licenses_table} l
                                 WHERE l.pool_id = p.id
                                 AND l.status = 'active'),
                                0
                            ) / COALESCE(
                                (SELECT SUM(a.capacity)
                                 FROM {$wpdb->prefix}vd_pool_accounts pa
                                 JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
                                 WHERE pa.pool_id = p.id
                                 AND pa.status = 'active'
                                 AND a.status = 'active'),
                                1
                            )
                        ) * 100
                        ELSE 0
                    END,
                    2
                ) as utilization_percent
            FROM {$pools_table} p
            INNER JOIN {$product_pools_table} pp ON p.id = pp.pool_id
            WHERE pp.product_id = %d
            AND pp.status = 'active'
            AND p.status = 'active'
            ORDER BY utilization_percent ASC, pp.priority ASC
        ", $product_id ), ARRAY_A );

        VD_LM_Logger_Service::debug( 'Balanced strategy: Retrieved pools with load data', array(
            'product_id' => $product_id,
            'pools_count' => count( $pools ),
            'pools' => $pools
        ) );

        return $pools;
    }

    /**
     * Get utilization statistics for all pools
     *
     * @since 1.1.0
     * @return array Utilization statistics
     */
    public function get_utilization_stats() {
        global $wpdb;

        $pools_table = $wpdb->prefix . 'vd_pools';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        $stats = $wpdb->get_row( "
            SELECT
                COUNT(p.id) as total_pools,
                AVG(
                    CASE
                        WHEN COALESCE(
                            (SELECT SUM(a.capacity)
                             FROM {$wpdb->prefix}vd_pool_accounts pa
                             JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
                             WHERE pa.pool_id = p.id
                             AND pa.status = 'active'
                             AND a.status = 'active'),
                            0
                        ) > 0
                        THEN (
                            COALESCE(
                                (SELECT COUNT(*)
                                 FROM {$licenses_table} l
                                 WHERE l.pool_id = p.id
                                 AND l.status = 'active'),
                                0
                            ) / COALESCE(
                                (SELECT SUM(a.capacity)
                                 FROM {$wpdb->prefix}vd_pool_accounts pa
                                 JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
                                 WHERE pa.pool_id = p.id
                                 AND pa.status = 'active'
                                 AND a.status = 'active'),
                                1
                            )
                        ) * 100
                        ELSE 0
                    END
                ) as avg_utilization,
                MAX(
                    CASE
                        WHEN COALESCE(
                            (SELECT SUM(a.capacity)
                             FROM {$wpdb->prefix}vd_pool_accounts pa
                             JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
                             WHERE pa.pool_id = p.id
                             AND pa.status = 'active'
                             AND a.status = 'active'),
                            0
                        ) > 0
                        THEN (
                            COALESCE(
                                (SELECT COUNT(*)
                                 FROM {$licenses_table} l
                                 WHERE l.pool_id = p.id
                                 AND l.status = 'active'),
                                0
                            ) / COALESCE(
                                (SELECT SUM(a.capacity)
                                 FROM {$wpdb->prefix}vd_pool_accounts pa
                                 JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
                                 WHERE pa.pool_id = p.id
                                 AND pa.status = 'active'
                                 AND a.status = 'active'),
                                1
                            )
                        ) * 100
                        ELSE 0
                    END
                ) as max_utilization
            FROM {$pools_table} p
            WHERE p.status = 'active'
        ", ARRAY_A );

        return $stats;
    }
}
?>
<?php
/**
 * Pool Assignment Strategy Interface
 *
 * Defines the contract for pool assignment strategies.
 * Implements Strategy Pattern for different pool selection algorithms.
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
 * Pool Assignment Strategy Interface
 *
 * Defines methods that all pool assignment strategies must implement.
 * Supports Priority-based and Balanced assignment algorithms.
 *
 * @since      1.1.0
 * @package    VD_License_Manager
 * @subpackage Pool_Assignment
 * @author     Vidieu Team <admin@vidieu.vn>
 */
interface VD_Pool_Assignment_Strategy {

    /**
     * Select the best pool for license assignment
     *
     * @since 1.1.0
     * @param int   $product_id       Product ID requiring pool assignment
     * @param array $available_pools  Array of available pools with capacity data
     * @return array|null Selected pool data or null if no pool available
     */
    public function select_pool( $product_id, $available_pools );

    /**
     * Get strategy name for logging and debugging
     *
     * @since 1.1.0
     * @return string Strategy name
     */
    public function get_strategy_name();

    /**
     * Validate pools array structure
     *
     * @since 1.1.0
     * @param array $pools Pools array to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_pools( $pools );

    /**
     * Get strategy-specific configuration options
     *
     * @since 1.1.0
     * @return array Configuration options
     */
    public function get_config_options();

    /**
     * Calculate pool priority score for selection
     *
     * This method should implement the core algorithm for pool selection.
     * Different strategies will have different scoring mechanisms.
     *
     * @since 1.1.0
     * @param array $pool Pool data with capacity information
     * @param int   $product_id Product ID for context
     * @return float Priority score (higher = better choice)
     */
    public function calculate_pool_score( $pool, $product_id );
}
?>
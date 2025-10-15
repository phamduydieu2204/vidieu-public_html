<?php
/**
 * Pool Assignment Strategy Factory
 *
 * Creates appropriate pool assignment strategy instances.
 * Implements Factory Pattern for strategy selection.
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
 * Pool Assignment Strategy Factory Class
 *
 * Responsible for creating and configuring pool assignment strategy instances.
 * Supports multiple strategies and handles strategy validation.
 *
 * @since      1.1.0
 * @package    VD_License_Manager
 * @subpackage Pool_Assignment
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Pool_Assignment_Factory {

    /**
     * Available strategies
     *
     * @since 1.1.0
     * @var array
     */
    const AVAILABLE_STRATEGIES = array(
        'priority' => 'VD_Priority_Assignment_Strategy',
        'balanced' => 'VD_Balanced_Assignment_Strategy'
    );

    /**
     * Default strategy
     *
     * @since 1.1.0
     * @var string
     */
    const DEFAULT_STRATEGY = 'priority';

    /**
     * Create strategy instance
     *
     * @since 1.1.0
     * @param string $strategy_type Strategy type to create
     * @return VD_Pool_Assignment_Strategy|WP_Error Strategy instance or error
     */
    public static function create_strategy( $strategy_type = null ) {
        // Use default if no strategy specified
        if ( empty( $strategy_type ) ) {
            $strategy_type = self::DEFAULT_STRATEGY;
        }

        // Validate strategy type
        if ( ! self::is_valid_strategy( $strategy_type ) ) {
            VD_LM_Logger_Service::error( 'Pool Assignment Factory: Invalid strategy type', array(
                'requested_strategy' => $strategy_type,
                'available_strategies' => array_keys( self::AVAILABLE_STRATEGIES )
            ) );

            return new WP_Error(
                'invalid_strategy_type',
                sprintf(
                    __( 'Invalid strategy type: %s. Available strategies: %s', 'vd-license-manager' ),
                    $strategy_type,
                    implode( ', ', array_keys( self::AVAILABLE_STRATEGIES ) )
                )
            );
        }

        // Load strategy class
        $class_name = self::AVAILABLE_STRATEGIES[ $strategy_type ];
        $strategy_instance = self::load_strategy_class( $class_name, $strategy_type );

        if ( is_wp_error( $strategy_instance ) ) {
            return $strategy_instance;
        }

        VD_LM_Logger_Service::info( 'Pool Assignment Factory: Strategy created', array(
            'strategy_type' => $strategy_type,
            'class_name' => $class_name
        ) );

        return $strategy_instance;
    }

    /**
     * Get strategy based on product configuration
     *
     * @since 1.1.0
     * @param int $product_id Product ID
     * @return VD_Pool_Assignment_Strategy|WP_Error Strategy instance or error
     */
    public static function create_strategy_for_product( $product_id ) {
        // Get product's preferred strategy (if configured)
        $product_strategy = self::get_product_strategy( $product_id );

        if ( ! empty( $product_strategy ) && self::is_valid_strategy( $product_strategy ) ) {
            VD_LM_Logger_Service::debug( 'Pool Assignment Factory: Using product-specific strategy', array(
                'product_id' => $product_id,
                'strategy' => $product_strategy
            ) );

            return self::create_strategy( $product_strategy );
        }

        // Fallback to global default strategy
        $global_strategy = self::get_global_default_strategy();

        VD_LM_Logger_Service::debug( 'Pool Assignment Factory: Using global default strategy', array(
            'product_id' => $product_id,
            'strategy' => $global_strategy
        ) );

        return self::create_strategy( $global_strategy );
    }

    /**
     * Get all available strategies with their configurations
     *
     * @since 1.1.0
     * @return array Available strategies with metadata
     */
    public static function get_available_strategies() {
        $strategies = array();

        foreach ( self::AVAILABLE_STRATEGIES as $type => $class_name ) {
            $strategy_instance = self::create_strategy( $type );

            if ( ! is_wp_error( $strategy_instance ) ) {
                $config = $strategy_instance->get_config_options();
                $strategies[ $type ] = array(
                    'type' => $type,
                    'class' => $class_name,
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'settings' => $config['settings']
                );
            }
        }

        return $strategies;
    }

    /**
     * Validate strategy type
     *
     * @since 1.1.0
     * @param string $strategy_type Strategy type to validate
     * @return bool True if valid, false otherwise
     */
    public static function is_valid_strategy( $strategy_type ) {
        return ! empty( $strategy_type ) && array_key_exists( $strategy_type, self::AVAILABLE_STRATEGIES );
    }

    /**
     * Get strategy recommendations for a product
     *
     * @since 1.1.0
     * @param int $product_id Product ID
     * @return array Strategy recommendations
     */
    public static function get_strategy_recommendations( $product_id ) {
        $recommendations = array();

        // Get product pool data
        $pool_data = self::analyze_product_pools( $product_id );

        if ( empty( $pool_data ) ) {
            $recommendations[] = array(
                'type' => 'warning',
                'message' => __( 'No pools configured for this product', 'vd-license-manager' )
            );
            return $recommendations;
        }

        $pool_count = count( $pool_data );
        $total_capacity = array_sum( array_column( $pool_data, 'capacity' ) );
        $total_assigned = array_sum( array_column( $pool_data, 'assigned_count' ) );
        $avg_utilization = $total_capacity > 0 ? ( $total_assigned / $total_capacity ) * 100 : 0;

        // Recommend strategy based on pool characteristics
        if ( $pool_count === 1 ) {
            $recommendations[] = array(
                'strategy' => 'priority',
                'reason' => __( 'Single pool configuration - priority strategy is sufficient', 'vd-license-manager' ),
                'confidence' => 'high'
            );
        } elseif ( $pool_count >= 2 && $avg_utilization > 70 ) {
            $recommendations[] = array(
                'strategy' => 'balanced',
                'reason' => __( 'High utilization detected - balanced strategy recommended for load distribution', 'vd-license-manager' ),
                'confidence' => 'high'
            );
        } elseif ( $pool_count >= 3 ) {
            $recommendations[] = array(
                'strategy' => 'balanced',
                'reason' => __( 'Multiple pools available - balanced strategy optimizes resource utilization', 'vd-license-manager' ),
                'confidence' => 'medium'
            );
        } else {
            $recommendations[] = array(
                'strategy' => 'priority',
                'reason' => __( 'Simple configuration - priority strategy provides predictable assignment', 'vd-license-manager' ),
                'confidence' => 'medium'
            );
        }

        return $recommendations;
    }

    /**
     * Load strategy class and create instance
     *
     * @since 1.1.0
     * @access private
     * @param string $class_name Class name to load
     * @param string $strategy_type Strategy type for error reporting
     * @return VD_Pool_Assignment_Strategy|WP_Error Strategy instance or error
     */
    private static function load_strategy_class( $class_name, $strategy_type ) {
        // Auto-load strategy classes if needed
        if ( ! class_exists( $class_name ) ) {
            $file_mappings = array(
                'VD_Priority_Assignment_Strategy' => 'class-priority-assignment-strategy.php',
                'VD_Balanced_Assignment_Strategy' => 'class-balanced-assignment-strategy.php'
            );

            if ( isset( $file_mappings[ $class_name ] ) ) {
                $file_path = plugin_dir_path( __FILE__ ) . $file_mappings[ $class_name ];

                if ( file_exists( $file_path ) ) {
                    require_once $file_path;
                } else {
                    return new WP_Error(
                        'strategy_file_not_found',
                        sprintf( __( 'Strategy file not found: %s', 'vd-license-manager' ), $file_path )
                    );
                }
            }
        }

        // Check if class exists after loading
        if ( ! class_exists( $class_name ) ) {
            return new WP_Error(
                'strategy_class_not_found',
                sprintf( __( 'Strategy class not found: %s', 'vd-license-manager' ), $class_name )
            );
        }

        // Create instance
        try {
            $instance = new $class_name();

            // Verify instance implements correct interface
            if ( ! $instance instanceof VD_Pool_Assignment_Strategy ) {
                return new WP_Error(
                    'invalid_strategy_interface',
                    sprintf( __( 'Class %s does not implement VD_Pool_Assignment_Strategy interface', 'vd-license-manager' ), $class_name )
                );
            }

            return $instance;

        } catch ( Exception $e ) {
            return new WP_Error(
                'strategy_instantiation_failed',
                sprintf( __( 'Failed to create strategy instance: %s', 'vd-license-manager' ), $e->getMessage() )
            );
        }
    }

    /**
     * Get product-specific strategy configuration
     *
     * @since 1.1.0
     * @access private
     * @param int $product_id Product ID
     * @return string|null Product strategy or null
     */
    private static function get_product_strategy( $product_id ) {
        // Check if product has strategy configured in meta
        $product_strategy = get_post_meta( $product_id, '_vd_pool_assignment_strategy', true );

        if ( ! empty( $product_strategy ) && self::is_valid_strategy( $product_strategy ) ) {
            return $product_strategy;
        }

        // Check if product category has default strategy
        $product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

        foreach ( $product_categories as $category_id ) {
            $category_strategy = get_term_meta( $category_id, '_vd_pool_assignment_strategy', true );

            if ( ! empty( $category_strategy ) && self::is_valid_strategy( $category_strategy ) ) {
                return $category_strategy;
            }
        }

        return null;
    }

    /**
     * Get global default strategy
     *
     * @since 1.1.0
     * @access private
     * @return string Global default strategy
     */
    private static function get_global_default_strategy() {
        $global_strategy = get_option( 'vd_pool_assignment_default_strategy', self::DEFAULT_STRATEGY );

        if ( ! self::is_valid_strategy( $global_strategy ) ) {
            return self::DEFAULT_STRATEGY;
        }

        return $global_strategy;
    }

    /**
     * Analyze product pools for recommendations
     *
     * @since 1.1.0
     * @access private
     * @param int $product_id Product ID
     * @return array Pool analysis data
     */
    private static function analyze_product_pools( $product_id ) {
        global $wpdb;

        $pools_table = $wpdb->prefix . 'vd_pools';
        $product_pools_table = $wpdb->prefix . 'vd_product_pools';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        $pools = $wpdb->get_results( $wpdb->prepare( "
            SELECT
                p.id as pool_id,
                p.name as pool_name,
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

        return $pools;
    }

    /**
     * Register strategy for product
     *
     * @since 1.1.0
     * @param int    $product_id Product ID
     * @param string $strategy_type Strategy type
     * @return bool True on success, false on failure
     */
    public static function set_product_strategy( $product_id, $strategy_type ) {
        if ( ! self::is_valid_strategy( $strategy_type ) ) {
            return false;
        }

        $result = update_post_meta( $product_id, '_vd_pool_assignment_strategy', $strategy_type );

        VD_LM_Logger_Service::info( 'Pool Assignment Factory: Product strategy updated', array(
            'product_id' => $product_id,
            'strategy' => $strategy_type,
            'success' => $result
        ) );

        return $result;
    }

    /**
     * Get strategy performance metrics
     *
     * @since 1.1.0
     * @param string $strategy_type Strategy type
     * @param int    $days_back Number of days to analyze
     * @return array Performance metrics
     */
    public static function get_strategy_metrics( $strategy_type, $days_back = 7 ) {
        global $wpdb;

        if ( ! self::is_valid_strategy( $strategy_type ) ) {
            return array();
        }

        $licenses_table = $wpdb->prefix . 'vd_license_keys';
        $logs_table = $wpdb->prefix . 'vd_account_fetch_log';

        $metrics = array(
            'strategy' => $strategy_type,
            'period_days' => $days_back,
            'total_assignments' => 0,
            'successful_assignments' => 0,
            'failed_assignments' => 0,
            'avg_assignment_time' => 0,
            'success_rate' => 0
        );

        // Get assignment counts
        $assignment_data = $wpdb->get_row( $wpdb->prepare( "
            SELECT
                COUNT(*) as total_assignments,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_assignments,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_assignments,
                AVG(execution_time_ms) as avg_execution_time
            FROM {$logs_table}
            WHERE assignment_strategy = %s
            AND fetched_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $strategy_type, $days_back ), ARRAY_A );

        if ( $assignment_data ) {
            $metrics['total_assignments'] = intval( $assignment_data['total_assignments'] );
            $metrics['successful_assignments'] = intval( $assignment_data['successful_assignments'] );
            $metrics['failed_assignments'] = intval( $assignment_data['failed_assignments'] );
            $metrics['avg_assignment_time'] = round( floatval( $assignment_data['avg_execution_time'] ), 2 );

            if ( $metrics['total_assignments'] > 0 ) {
                $metrics['success_rate'] = round(
                    ( $metrics['successful_assignments'] / $metrics['total_assignments'] ) * 100,
                    2
                );
            }
        }

        return $metrics;
    }
}
?>
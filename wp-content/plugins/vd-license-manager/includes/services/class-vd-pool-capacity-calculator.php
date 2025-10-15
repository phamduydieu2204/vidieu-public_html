<?php
/**
 * Pool Capacity Calculator
 *
 * Provides real-time pool capacity calculations, caching, and monitoring
 * for efficient pool assignment and capacity management.
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
 * Pool Capacity Calculator Class
 *
 * Calculates pool capacities in real-time, manages capacity caching,
 * and provides detailed capacity analytics for pool management.
 *
 * Features:
 * - Real-time capacity calculation with account aggregation
 * - Intelligent caching with cache invalidation
 * - Capacity forecasting and trending
 * - Pool utilization analytics
 * - Performance optimization for large datasets
 *
 * @since      1.1.0
 * @package    VD_License_Manager
 * @subpackage Services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Pool_Capacity_Calculator {

    /**
     * Cache group for pool capacity data
     *
     * @since 1.1.0
     * @var string
     */
    const CACHE_GROUP = 'vd_pool_capacity';

    /**
     * Cache expiration time (1 hour)
     *
     * @since 1.1.0
     * @var int
     */
    const CACHE_EXPIRATION = 3600;

    /**
     * Cache expiration for real-time data (5 minutes)
     *
     * @since 1.1.0
     * @var int
     */
    const REALTIME_CACHE_EXPIRATION = 300;

    /**
     * Calculate total capacity for a pool
     *
     * Aggregates capacity from all active accounts in the pool.
     * Results are cached for performance optimization.
     *
     * @since 1.1.0
     * @param int  $pool_id Pool ID
     * @param bool $use_cache Whether to use cached results
     * @return array|WP_Error Pool capacity data or error
     */
    public function calculate_pool_capacity( $pool_id, $use_cache = true ) {
        $pool_id = absint( $pool_id );

        if ( $pool_id <= 0 ) {
            return new WP_Error(
                'invalid_pool_id',
                __( 'Invalid pool ID provided', 'vd-license-manager' )
            );
        }

        // Check cache first
        if ( $use_cache ) {
            $cache_key = "pool_capacity_{$pool_id}";
            $cached_data = wp_cache_get( $cache_key, self::CACHE_GROUP );

            if ( $cached_data !== false ) {
                VD_LM_Logger_Service::debug( 'Pool capacity retrieved from cache', array(
                    'pool_id' => $pool_id,
                    'cache_key' => $cache_key
                ) );

                return $cached_data;
            }
        }

        // Calculate capacity from database
        $capacity_data = $this->fetch_pool_capacity_from_database( $pool_id );

        if ( is_wp_error( $capacity_data ) ) {
            return $capacity_data;
        }

        // Cache the results
        if ( $use_cache ) {
            $cache_key = "pool_capacity_{$pool_id}";
            wp_cache_set( $cache_key, $capacity_data, self::CACHE_GROUP, self::CACHE_EXPIRATION );

            VD_LM_Logger_Service::debug( 'Pool capacity cached', array(
                'pool_id' => $pool_id,
                'cache_key' => $cache_key,
                'expiration' => self::CACHE_EXPIRATION
            ) );
        }

        return $capacity_data;
    }

    /**
     * Calculate capacity for multiple pools at once
     *
     * Optimized bulk calculation for multiple pools.
     *
     * @since 1.1.0
     * @param array $pool_ids Array of pool IDs
     * @param bool  $use_cache Whether to use cached results
     * @return array Pool capacities indexed by pool ID
     */
    public function calculate_multiple_pool_capacities( $pool_ids, $use_cache = true ) {
        if ( empty( $pool_ids ) || ! is_array( $pool_ids ) ) {
            return array();
        }

        // Sanitize pool IDs
        $pool_ids = array_map( 'absint', $pool_ids );
        $pool_ids = array_filter( $pool_ids, function( $id ) {
            return $id > 0;
        } );

        if ( empty( $pool_ids ) ) {
            return array();
        }

        $results = array();
        $uncached_pools = array();

        // Check cache for each pool
        if ( $use_cache ) {
            foreach ( $pool_ids as $pool_id ) {
                $cache_key = "pool_capacity_{$pool_id}";
                $cached_data = wp_cache_get( $cache_key, self::CACHE_GROUP );

                if ( $cached_data !== false ) {
                    $results[ $pool_id ] = $cached_data;
                } else {
                    $uncached_pools[] = $pool_id;
                }
            }
        } else {
            $uncached_pools = $pool_ids;
        }

        // Fetch uncached pools from database
        if ( ! empty( $uncached_pools ) ) {
            $bulk_data = $this->fetch_multiple_pools_capacity_from_database( $uncached_pools );

            foreach ( $bulk_data as $pool_id => $capacity_data ) {
                $results[ $pool_id ] = $capacity_data;

                // Cache individual results
                if ( $use_cache ) {
                    $cache_key = "pool_capacity_{$pool_id}";
                    wp_cache_set( $cache_key, $capacity_data, self::CACHE_GROUP, self::CACHE_EXPIRATION );
                }
            }
        }

        VD_LM_Logger_Service::debug( 'Multiple pool capacities calculated', array(
            'requested_pools' => count( $pool_ids ),
            'cached_results' => count( $results ) - count( $uncached_pools ),
            'database_queries' => count( $uncached_pools )
        ) );

        return $results;
    }

    /**
     * Get real-time pool utilization
     *
     * Provides current utilization statistics with minimal caching.
     *
     * @since 1.1.0
     * @param int $pool_id Pool ID
     * @return array|WP_Error Utilization data or error
     */
    public function get_realtime_pool_utilization( $pool_id ) {
        $pool_id = absint( $pool_id );

        if ( $pool_id <= 0 ) {
            return new WP_Error(
                'invalid_pool_id',
                __( 'Invalid pool ID provided', 'vd-license-manager' )
            );
        }

        // Use short-term cache for real-time data
        $cache_key = "pool_utilization_rt_{$pool_id}";
        $cached_data = wp_cache_get( $cache_key, self::CACHE_GROUP );

        if ( $cached_data !== false ) {
            return $cached_data;
        }

        global $wpdb;

        $utilization_data = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                p.id as pool_id,
                p.name as pool_name,
                p.status as pool_status,
                COUNT(DISTINCT pa.account_id) as total_accounts,
                SUM(CASE WHEN a.status = 'active' AND (a.expires_at IS NULL OR a.expires_at > NOW()) THEN 1 ELSE 0 END) as active_accounts,
                SUM(CASE WHEN a.status = 'active' AND (a.expires_at IS NULL OR a.expires_at > NOW()) THEN a.capacity ELSE 0 END) as total_capacity,
                SUM(CASE WHEN a.status = 'active' AND (a.expires_at IS NULL OR a.expires_at > NOW()) THEN
                    COALESCE((
                        SELECT COUNT(*)
                        FROM {$wpdb->prefix}vd_license_keys l
                        WHERE l.assigned_account_id = a.id
                        AND l.status = 'active'
                    ), 0)
                ELSE 0 END) as current_usage,
                NOW() as calculated_at
            FROM {$wpdb->prefix}vd_pools p
            LEFT JOIN {$wpdb->prefix}vd_pool_accounts pa ON p.id = pa.pool_id AND pa.status = 'active'
            LEFT JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
            WHERE p.id = %d
            GROUP BY p.id, p.name, p.status",
            $pool_id
        ), ARRAY_A );

        if ( ! $utilization_data ) {
            return new WP_Error(
                'pool_not_found',
                __( 'Pool not found', 'vd-license-manager' )
            );
        }

        // Calculate derived metrics
        $total_capacity = intval( $utilization_data['total_capacity'] );
        $current_usage = intval( $utilization_data['current_usage'] );
        $available_capacity = max( 0, $total_capacity - $current_usage );
        $utilization_percentage = $total_capacity > 0 ? ( $current_usage / $total_capacity ) * 100 : 0;

        $utilization_data['available_capacity'] = $available_capacity;
        $utilization_data['utilization_percentage'] = round( $utilization_percentage, 2 );
        $utilization_data['is_at_capacity'] = $available_capacity <= 0;
        $utilization_data['can_accept_assignments'] = $available_capacity > 0 && $utilization_data['active_accounts'] > 0;

        // Cache for short period
        wp_cache_set( $cache_key, $utilization_data, self::CACHE_GROUP, self::REALTIME_CACHE_EXPIRATION );

        VD_LM_Logger_Service::debug( 'Real-time pool utilization calculated', array(
            'pool_id' => $pool_id,
            'utilization_percentage' => $utilization_data['utilization_percentage'],
            'available_capacity' => $available_capacity
        ) );

        return $utilization_data;
    }

    /**
     * Calculate pool capacity forecast
     *
     * Predicts future capacity needs based on historical usage patterns.
     *
     * @since 1.1.0
     * @param int $pool_id Pool ID
     * @param int $forecast_days Number of days to forecast
     * @return array|WP_Error Forecast data or error
     */
    public function calculate_capacity_forecast( $pool_id, $forecast_days = 7 ) {
        $pool_id = absint( $pool_id );
        $forecast_days = absint( $forecast_days );

        if ( $pool_id <= 0 ) {
            return new WP_Error(
                'invalid_pool_id',
                __( 'Invalid pool ID provided', 'vd-license-manager' )
            );
        }

        if ( $forecast_days <= 0 || $forecast_days > 30 ) {
            return new WP_Error(
                'invalid_forecast_period',
                __( 'Forecast period must be between 1 and 30 days', 'vd-license-manager' )
            );
        }

        global $wpdb;

        // Get historical license assignment data
        $historical_data = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                DATE(created_at) as assignment_date,
                COUNT(*) as daily_assignments
            FROM {$wpdb->prefix}vd_license_keys
            WHERE pool_id = %d
            AND status = 'active'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY assignment_date DESC",
            $pool_id
        ), ARRAY_A );

        if ( empty( $historical_data ) ) {
            return new WP_Error(
                'insufficient_data',
                __( 'Insufficient historical data for forecasting', 'vd-license-manager' )
            );
        }

        // Calculate trends
        $daily_assignments = array_column( $historical_data, 'daily_assignments' );
        $average_daily_assignments = array_sum( $daily_assignments ) / count( $daily_assignments );
        $recent_trend = $this->calculate_trend( $daily_assignments );

        // Get current capacity
        $current_capacity = $this->get_realtime_pool_utilization( $pool_id );
        if ( is_wp_error( $current_capacity ) ) {
            return $current_capacity;
        }

        // Calculate forecast
        $forecast_data = array(
            'pool_id' => $pool_id,
            'forecast_period_days' => $forecast_days,
            'current_capacity' => $current_capacity['total_capacity'],
            'current_usage' => $current_capacity['current_usage'],
            'current_available' => $current_capacity['available_capacity'],
            'average_daily_assignments' => round( $average_daily_assignments, 2 ),
            'trend_direction' => $recent_trend['direction'],
            'trend_strength' => $recent_trend['strength'],
            'forecasted_usage' => array(),
            'capacity_warnings' => array()
        );

        // Project future usage
        for ( $day = 1; $day <= $forecast_days; $day++ ) {
            $projected_daily_rate = $average_daily_assignments * ( 1 + ( $recent_trend['slope'] * $day ) );
            $projected_daily_rate = max( 0, $projected_daily_rate ); // Don't allow negative

            $projected_cumulative = $current_capacity['current_usage'] + ( $projected_daily_rate * $day );
            $projected_available = $current_capacity['total_capacity'] - $projected_cumulative;

            $forecast_data['forecasted_usage'][] = array(
                'day' => $day,
                'date' => gmdate( 'Y-m-d', strtotime( "+{$day} days" ) ),
                'projected_usage' => round( $projected_cumulative ),
                'available_capacity' => max( 0, round( $projected_available ) ),
                'utilization_percentage' => $current_capacity['total_capacity'] > 0 ?
                    round( ( $projected_cumulative / $current_capacity['total_capacity'] ) * 100, 2 ) : 0
            );

            // Check for capacity warnings
            $utilization_percentage = $current_capacity['total_capacity'] > 0 ?
                ( $projected_cumulative / $current_capacity['total_capacity'] ) * 100 : 0;

            if ( $utilization_percentage >= 100 && $projected_available <= 0 ) {
                $forecast_data['capacity_warnings'][] = array(
                    'day' => $day,
                    'date' => gmdate( 'Y-m-d', strtotime( "+{$day} days" ) ),
                    'type' => 'capacity_exceeded',
                    'message' => sprintf( __( 'Pool capacity will be exceeded on day %d', 'vd-license-manager' ), $day )
                );
            } elseif ( $utilization_percentage >= 90 ) {
                $forecast_data['capacity_warnings'][] = array(
                    'day' => $day,
                    'date' => gmdate( 'Y-m-d', strtotime( "+{$day} days" ) ),
                    'type' => 'capacity_warning',
                    'message' => sprintf( __( 'Pool will be 90%% utilized on day %d', 'vd-license-manager' ), $day )
                );
            }
        }

        VD_LM_Logger_Service::debug( 'Pool capacity forecast calculated', array(
            'pool_id' => $pool_id,
            'forecast_days' => $forecast_days,
            'warnings_count' => count( $forecast_data['capacity_warnings'] )
        ) );

        return $forecast_data;
    }

    /**
     * Invalidate pool capacity cache
     *
     * @since 1.1.0
     * @param int|array $pool_ids Pool ID(s) to invalidate
     * @return bool Success
     */
    public function invalidate_cache( $pool_ids ) {
        if ( ! is_array( $pool_ids ) ) {
            $pool_ids = array( $pool_ids );
        }

        $invalidated = 0;

        foreach ( $pool_ids as $pool_id ) {
            $pool_id = absint( $pool_id );

            if ( $pool_id > 0 ) {
                // Invalidate capacity cache
                $capacity_key = "pool_capacity_{$pool_id}";
                wp_cache_delete( $capacity_key, self::CACHE_GROUP );

                // Invalidate real-time utilization cache
                $utilization_key = "pool_utilization_rt_{$pool_id}";
                wp_cache_delete( $utilization_key, self::CACHE_GROUP );

                $invalidated++;
            }
        }

        VD_LM_Logger_Service::debug( 'Pool capacity cache invalidated', array(
            'pools_invalidated' => $invalidated,
            'pool_ids' => $pool_ids
        ) );

        return $invalidated > 0;
    }

    /**
     * Get pool capacity analytics
     *
     * @since 1.1.0
     * @param int $pool_id Pool ID
     * @param int $days_back Number of days of history to analyze
     * @return array|WP_Error Analytics data or error
     */
    public function get_pool_analytics( $pool_id, $days_back = 30 ) {
        $pool_id = absint( $pool_id );
        $days_back = absint( $days_back );

        if ( $pool_id <= 0 ) {
            return new WP_Error(
                'invalid_pool_id',
                __( 'Invalid pool ID provided', 'vd-license-manager' )
            );
        }

        global $wpdb;

        // Get current capacity
        $current_capacity = $this->get_realtime_pool_utilization( $pool_id );
        if ( is_wp_error( $current_capacity ) ) {
            return $current_capacity;
        }

        // Get historical usage patterns
        $usage_history = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                DATE(created_at) as usage_date,
                COUNT(*) as daily_assignments,
                HOUR(created_at) as assignment_hour
            FROM {$wpdb->prefix}vd_license_keys
            WHERE pool_id = %d
            AND status = 'active'
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at), HOUR(created_at)
            ORDER BY usage_date DESC, assignment_hour ASC",
            $pool_id,
            $days_back
        ), ARRAY_A );

        // Calculate analytics
        $analytics = array(
            'pool_id' => $pool_id,
            'analysis_period_days' => $days_back,
            'current_status' => $current_capacity,
            'usage_patterns' => $this->analyze_usage_patterns( $usage_history ),
            'performance_metrics' => $this->calculate_performance_metrics( $pool_id, $days_back ),
            'recommendations' => array()
        );

        // Generate recommendations based on analytics
        $analytics['recommendations'] = $this->generate_capacity_recommendations( $analytics );

        return $analytics;
    }

    /**
     * Fetch pool capacity from database
     *
     * @since 1.1.0
     * @access private
     * @param int $pool_id Pool ID
     * @return array|WP_Error Capacity data or error
     */
    private function fetch_pool_capacity_from_database( $pool_id ) {
        global $wpdb;

        $capacity_data = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                p.id as pool_id,
                p.name as pool_name,
                p.status as pool_status,
                COUNT(pa.account_id) as total_accounts,
                SUM(CASE WHEN a.status = 'active' THEN a.capacity ELSE 0 END) as total_capacity,
                SUM(CASE WHEN a.status = 'active' THEN a.current_usage ELSE 0 END) as current_usage,
                NOW() as calculated_at
            FROM {$wpdb->prefix}vd_pools p
            LEFT JOIN {$wpdb->prefix}vd_pool_accounts pa ON p.id = pa.pool_id AND pa.status = 'active'
            LEFT JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
            WHERE p.id = %d
            GROUP BY p.id, p.name, p.status",
            $pool_id
        ), ARRAY_A );

        if ( ! $capacity_data ) {
            return new WP_Error(
                'pool_not_found',
                __( 'Pool not found', 'vd-license-manager' )
            );
        }

        // Calculate derived metrics
        $total_capacity = intval( $capacity_data['total_capacity'] );
        $current_usage = intval( $capacity_data['current_usage'] );
        $available_capacity = max( 0, $total_capacity - $current_usage );

        $capacity_data['available_capacity'] = $available_capacity;
        $capacity_data['utilization_percentage'] = $total_capacity > 0 ?
            round( ( $current_usage / $total_capacity ) * 100, 2 ) : 0;

        return $capacity_data;
    }

    /**
     * Fetch multiple pools capacity from database
     *
     * @since 1.1.0
     * @access private
     * @param array $pool_ids Pool IDs
     * @return array Capacity data indexed by pool ID
     */
    private function fetch_multiple_pools_capacity_from_database( $pool_ids ) {
        global $wpdb;

        if ( empty( $pool_ids ) ) {
            return array();
        }

        $placeholders = implode( ',', array_fill( 0, count( $pool_ids ), '%d' ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                p.id as pool_id,
                p.name as pool_name,
                p.status as pool_status,
                COUNT(pa.account_id) as total_accounts,
                SUM(CASE WHEN a.status = 'active' THEN a.capacity ELSE 0 END) as total_capacity,
                SUM(CASE WHEN a.status = 'active' THEN a.current_usage ELSE 0 END) as current_usage,
                NOW() as calculated_at
            FROM {$wpdb->prefix}vd_pools p
            LEFT JOIN {$wpdb->prefix}vd_pool_accounts pa ON p.id = pa.pool_id AND pa.status = 'active'
            LEFT JOIN {$wpdb->prefix}vd_provider_accounts a ON pa.account_id = a.id
            WHERE p.id IN ({$placeholders})
            GROUP BY p.id, p.name, p.status",
            ...$pool_ids
        ), ARRAY_A );

        $capacity_data = array();

        foreach ( $results as $row ) {
            $pool_id = intval( $row['pool_id'] );
            $total_capacity = intval( $row['total_capacity'] );
            $current_usage = intval( $row['current_usage'] );
            $available_capacity = max( 0, $total_capacity - $current_usage );

            $row['available_capacity'] = $available_capacity;
            $row['utilization_percentage'] = $total_capacity > 0 ?
                round( ( $current_usage / $total_capacity ) * 100, 2 ) : 0;

            $capacity_data[ $pool_id ] = $row;
        }

        return $capacity_data;
    }

    /**
     * Calculate trend from historical data
     *
     * @since 1.1.0
     * @access private
     * @param array $data_points Historical data points
     * @return array Trend analysis
     */
    private function calculate_trend( $data_points ) {
        if ( count( $data_points ) < 2 ) {
            return array(
                'direction' => 'stable',
                'strength' => 0,
                'slope' => 0
            );
        }

        $n = count( $data_points );
        $x_sum = array_sum( range( 1, $n ) );
        $y_sum = array_sum( $data_points );
        $xy_sum = 0;
        $x_squared_sum = 0;

        for ( $i = 0; $i < $n; $i++ ) {
            $x = $i + 1;
            $y = $data_points[ $i ];
            $xy_sum += $x * $y;
            $x_squared_sum += $x * $x;
        }

        $slope = ( $n * $xy_sum - $x_sum * $y_sum ) / ( $n * $x_squared_sum - $x_sum * $x_sum );

        $direction = 'stable';
        $strength = abs( $slope );

        if ( $slope > 0.1 ) {
            $direction = 'increasing';
        } elseif ( $slope < -0.1 ) {
            $direction = 'decreasing';
        }

        return array(
            'direction' => $direction,
            'strength' => round( $strength, 4 ),
            'slope' => round( $slope, 4 )
        );
    }

    /**
     * Analyze usage patterns
     *
     * @since 1.1.0
     * @access private
     * @param array $usage_history Historical usage data
     * @return array Usage pattern analysis
     */
    private function analyze_usage_patterns( $usage_history ) {
        // Implementation for usage pattern analysis
        return array(
            'peak_hours' => array(),
            'daily_averages' => array(),
            'weekly_patterns' => array()
        );
    }

    /**
     * Calculate performance metrics
     *
     * @since 1.1.0
     * @access private
     * @param int $pool_id Pool ID
     * @param int $days_back Days back to analyze
     * @return array Performance metrics
     */
    private function calculate_performance_metrics( $pool_id, $days_back ) {
        // Implementation for performance metrics calculation
        return array(
            'average_response_time' => 0,
            'assignment_success_rate' => 100,
            'capacity_efficiency' => 0
        );
    }

    /**
     * Generate capacity recommendations
     *
     * @since 1.1.0
     * @access private
     * @param array $analytics Analytics data
     * @return array Recommendations
     */
    private function generate_capacity_recommendations( $analytics ) {
        $recommendations = array();

        $utilization = $analytics['current_status']['utilization_percentage'];

        if ( $utilization >= 90 ) {
            $recommendations[] = array(
                'type' => 'capacity_warning',
                'priority' => 'high',
                'message' => __( 'Pool is at high utilization (90%+). Consider adding more accounts.', 'vd-license-manager' )
            );
        } elseif ( $utilization <= 20 ) {
            $recommendations[] = array(
                'type' => 'optimization',
                'priority' => 'low',
                'message' => __( 'Pool has low utilization. Consider reducing account capacity or reassigning accounts.', 'vd-license-manager' )
            );
        }

        return $recommendations;
    }
}
?>
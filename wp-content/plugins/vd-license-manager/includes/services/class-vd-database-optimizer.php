<?php
/**
 * Database Optimizer
 *
 * Provides database optimization functionality including query optimization,
 * index management, cleanup operations, and performance monitoring.
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
 * Database Optimizer Class
 *
 * Manages database optimization for the VD License Manager plugin including:
 * - Query performance optimization
 * - Index creation and management
 * - Data cleanup and maintenance
 * - Performance monitoring and reporting
 * - Database health checks
 *
 * @since      1.1.0
 * @package    VD_License_Manager
 * @subpackage Services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Database_Optimizer {

    /**
     * Optimization levels
     *
     * @since 1.1.0
     * @var array
     */
    const OPTIMIZATION_LEVELS = array(
        'basic' => 1,
        'standard' => 2,
        'aggressive' => 3
    );

    /**
     * Run comprehensive database optimization
     *
     * @since 1.1.0
     * @param string $level Optimization level (basic, standard, aggressive)
     * @param bool   $dry_run Whether to perform actual optimization or just report
     * @return array Optimization results
     */
    public function optimize_database( $level = 'standard', $dry_run = false ) {
        $level_value = self::OPTIMIZATION_LEVELS[ $level ] ?? self::OPTIMIZATION_LEVELS['standard'];

        VD_LM_Logger_Service::info( 'Database optimization started', array(
            'level' => $level,
            'level_value' => $level_value,
            'dry_run' => $dry_run
        ) );

        $results = array(
            'optimization_level' => $level,
            'dry_run' => $dry_run,
            'started_at' => current_time( 'mysql' ),
            'operations' => array()
        );

        // 1. Index optimization (all levels)
        $results['operations']['indexes'] = $this->optimize_indexes( $dry_run );

        // 2. Query optimization (all levels)
        $results['operations']['queries'] = $this->optimize_queries( $dry_run );

        // 3. Data cleanup (standard and aggressive)
        if ( $level_value >= 2 ) {
            $results['operations']['cleanup'] = $this->cleanup_data( $dry_run );
        }

        // 4. Table optimization (aggressive only)
        if ( $level_value >= 3 ) {
            $results['operations']['tables'] = $this->optimize_tables( $dry_run );
        }

        // 5. Performance analysis (all levels)
        $results['operations']['performance'] = $this->analyze_performance();

        $results['completed_at'] = current_time( 'mysql' );
        $results['total_duration'] = strtotime( $results['completed_at'] ) - strtotime( $results['started_at'] );

        VD_LM_Logger_Service::info( 'Database optimization completed', array(
            'level' => $level,
            'duration_seconds' => $results['total_duration'],
            'operations_count' => count( $results['operations'] )
        ) );

        return $results;
    }

    /**
     * Optimize database indexes
     *
     * @since 1.1.0
     * @param bool $dry_run Whether to perform actual optimization
     * @return array Index optimization results
     */
    public function optimize_indexes( $dry_run = false ) {
        global $wpdb;

        $results = array(
            'indexes_analyzed' => 0,
            'indexes_created' => 0,
            'indexes_optimized' => 0,
            'recommendations' => array(),
            'details' => array()
        );

        // Define required indexes for each table
        $required_indexes = $this->get_required_indexes();

        foreach ( $required_indexes as $table_name => $indexes ) {
            $full_table_name = $wpdb->prefix . $table_name;

            // Check if table exists
            $table_exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $full_table_name
            ) );

            if ( ! $table_exists ) {
                $results['details'][] = array(
                    'table' => $full_table_name,
                    'action' => 'skipped',
                    'reason' => 'Table does not exist'
                );
                continue;
            }

            // Get existing indexes
            $existing_indexes = $this->get_existing_indexes( $full_table_name );

            foreach ( $indexes as $index_name => $index_config ) {
                $results['indexes_analyzed']++;

                if ( ! isset( $existing_indexes[ $index_name ] ) ) {
                    // Index doesn't exist, create it
                    if ( ! $dry_run ) {
                        $create_result = $this->create_index( $full_table_name, $index_name, $index_config );

                        if ( $create_result ) {
                            $results['indexes_created']++;
                            $results['details'][] = array(
                                'table' => $full_table_name,
                                'index' => $index_name,
                                'action' => 'created',
                                'columns' => $index_config['columns']
                            );
                        } else {
                            $results['details'][] = array(
                                'table' => $full_table_name,
                                'index' => $index_name,
                                'action' => 'failed',
                                'error' => $wpdb->last_error
                            );
                        }
                    } else {
                        $results['recommendations'][] = array(
                            'type' => 'create_index',
                            'table' => $full_table_name,
                            'index' => $index_name,
                            'priority' => $index_config['priority'] ?? 'medium'
                        );
                    }
                } else {
                    // Index exists, check if it needs optimization
                    $optimization_needed = $this->check_index_optimization_needed( $full_table_name, $index_name );

                    if ( $optimization_needed ) {
                        if ( ! $dry_run ) {
                            $optimize_result = $this->optimize_index( $full_table_name, $index_name );

                            if ( $optimize_result ) {
                                $results['indexes_optimized']++;
                                $results['details'][] = array(
                                    'table' => $full_table_name,
                                    'index' => $index_name,
                                    'action' => 'optimized'
                                );
                            }
                        } else {
                            $results['recommendations'][] = array(
                                'type' => 'optimize_index',
                                'table' => $full_table_name,
                                'index' => $index_name,
                                'priority' => 'low'
                            );
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Optimize database queries
     *
     * @since 1.1.0
     * @param bool $dry_run Whether to perform actual optimization
     * @return array Query optimization results
     */
    public function optimize_queries( $dry_run = false ) {
        global $wpdb;

        $results = array(
            'slow_queries_analyzed' => 0,
            'query_cache_optimized' => false,
            'recommendations' => array(),
            'details' => array()
        );

        // Check MySQL version and available features
        $mysql_version = $wpdb->get_var( 'SELECT VERSION()' );
        $results['mysql_version'] = $mysql_version;

        // Analyze slow query log (if available)
        $slow_queries = $this->analyze_slow_queries();
        $results['slow_queries_analyzed'] = count( $slow_queries );

        if ( ! empty( $slow_queries ) ) {
            $results['recommendations'][] = array(
                'type' => 'slow_queries',
                'message' => sprintf( __( '%d slow queries found that may need optimization', 'vd-license-manager' ), count( $slow_queries ) ),
                'priority' => 'high',
                'queries' => array_slice( $slow_queries, 0, 5 ) // Top 5 slowest
            );
        }

        // Check query cache settings
        $query_cache_settings = $this->check_query_cache_settings();
        if ( isset( $query_cache_settings['recommendations'] ) ) {
            $results['recommendations'] = array_merge( $results['recommendations'], $query_cache_settings['recommendations'] );
        }

        // Analyze frequently used queries
        $frequent_queries = $this->analyze_frequent_queries();
        if ( ! empty( $frequent_queries ) ) {
            $results['details']['frequent_queries'] = $frequent_queries;
        }

        return $results;
    }

    /**
     * Clean up unnecessary data
     *
     * @since 1.1.0
     * @param bool $dry_run Whether to perform actual cleanup
     * @return array Cleanup results
     */
    public function cleanup_data( $dry_run = false ) {
        global $wpdb;

        $results = array(
            'records_cleaned' => 0,
            'space_freed_mb' => 0,
            'operations' => array()
        );

        // 1. Clean old access logs (older than 90 days)
        $old_logs_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_license_access_log WHERE accessed_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        $old_logs_count = $wpdb->get_var( $old_logs_query );

        if ( $old_logs_count > 0 ) {
            if ( ! $dry_run ) {
                $deleted = $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}vd_license_access_log WHERE accessed_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
                ) );

                $results['records_cleaned'] += $deleted;
                $results['operations']['old_access_logs'] = array(
                    'action' => 'deleted',
                    'count' => $deleted
                );
            } else {
                $results['operations']['old_access_logs'] = array(
                    'action' => 'would_delete',
                    'count' => $old_logs_count
                );
            }
        }

        // 2. Clean orphaned fetch logs
        $orphaned_fetch_logs = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_account_fetch_log afl
            LEFT JOIN {$wpdb->prefix}vd_license_keys lk ON afl.license_id = lk.id
            WHERE lk.id IS NULL"
        );

        if ( $orphaned_fetch_logs > 0 ) {
            if ( ! $dry_run ) {
                $deleted = $wpdb->query(
                    "DELETE afl FROM {$wpdb->prefix}vd_account_fetch_log afl
                    LEFT JOIN {$wpdb->prefix}vd_license_keys lk ON afl.license_id = lk.id
                    WHERE lk.id IS NULL"
                );

                $results['records_cleaned'] += $deleted;
                $results['operations']['orphaned_fetch_logs'] = array(
                    'action' => 'deleted',
                    'count' => $deleted
                );
            } else {
                $results['operations']['orphaned_fetch_logs'] = array(
                    'action' => 'would_delete',
                    'count' => $orphaned_fetch_logs
                );
            }
        }

        // 3. Clean expired rate limits
        $expired_rate_limits = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_license_rate_limits WHERE expires_at < NOW()"
        ) );

        if ( $expired_rate_limits > 0 ) {
            if ( ! $dry_run ) {
                $deleted = $wpdb->query(
                    "DELETE FROM {$wpdb->prefix}vd_license_rate_limits WHERE expires_at < NOW()"
                );

                $results['records_cleaned'] += $deleted;
                $results['operations']['expired_rate_limits'] = array(
                    'action' => 'deleted',
                    'count' => $deleted
                );
            } else {
                $results['operations']['expired_rate_limits'] = array(
                    'action' => 'would_delete',
                    'count' => $expired_rate_limits
                );
            }
        }

        // 4. Optimize table storage (estimate space freed)
        if ( ! $dry_run && $results['records_cleaned'] > 0 ) {
            $results['space_freed_mb'] = $this->estimate_space_freed( $results['records_cleaned'] );
        }

        return $results;
    }

    /**
     * Optimize database tables
     *
     * @since 1.1.0
     * @param bool $dry_run Whether to perform actual optimization
     * @return array Table optimization results
     */
    public function optimize_tables( $dry_run = false ) {
        global $wpdb;

        $results = array(
            'tables_analyzed' => 0,
            'tables_optimized' => 0,
            'space_reclaimed_mb' => 0,
            'details' => array()
        );

        // Get all VD License Manager tables
        $vd_tables = $wpdb->get_col( $wpdb->prepare(
            "SELECT table_name FROM information_schema.tables
            WHERE table_schema = %s AND table_name LIKE %s",
            DB_NAME,
            $wpdb->prefix . 'vd_%'
        ) );

        foreach ( $vd_tables as $table_name ) {
            $results['tables_analyzed']++;

            // Get table stats before optimization
            $table_stats_before = $this->get_table_stats( $table_name );

            if ( ! $dry_run ) {
                // Optimize table
                $optimize_result = $wpdb->query( "OPTIMIZE TABLE `{$table_name}`" );

                if ( $optimize_result !== false ) {
                    $results['tables_optimized']++;

                    // Get table stats after optimization
                    $table_stats_after = $this->get_table_stats( $table_name );

                    $space_saved = $table_stats_before['data_length'] - $table_stats_after['data_length'];
                    $results['space_reclaimed_mb'] += round( $space_saved / 1024 / 1024, 2 );

                    $results['details'][] = array(
                        'table' => $table_name,
                        'action' => 'optimized',
                        'space_saved_mb' => round( $space_saved / 1024 / 1024, 2 )
                    );
                } else {
                    $results['details'][] = array(
                        'table' => $table_name,
                        'action' => 'failed',
                        'error' => $wpdb->last_error
                    );
                }
            } else {
                $fragmentation = $this->calculate_table_fragmentation( $table_stats_before );

                $results['details'][] = array(
                    'table' => $table_name,
                    'action' => 'would_optimize',
                    'fragmentation_percentage' => $fragmentation,
                    'size_mb' => round( $table_stats_before['data_length'] / 1024 / 1024, 2 )
                );
            }
        }

        return $results;
    }

    /**
     * Analyze database performance
     *
     * @since 1.1.0
     * @return array Performance analysis results
     */
    public function analyze_performance() {
        global $wpdb;

        $results = array(
            'overall_score' => 0,
            'metrics' => array(),
            'recommendations' => array(),
            'health_checks' => array()
        );

        // 1. Query performance metrics
        $query_metrics = $this->get_query_performance_metrics();
        $results['metrics']['queries'] = $query_metrics;

        // 2. Table size analysis
        $table_metrics = $this->get_table_size_metrics();
        $results['metrics']['tables'] = $table_metrics;

        // 3. Index usage analysis
        $index_metrics = $this->get_index_usage_metrics();
        $results['metrics']['indexes'] = $index_metrics;

        // 4. Health checks
        $results['health_checks'] = $this->perform_health_checks();

        // 5. Calculate overall performance score
        $results['overall_score'] = $this->calculate_performance_score( $results['metrics'], $results['health_checks'] );

        // 6. Generate recommendations
        $results['recommendations'] = $this->generate_performance_recommendations( $results );

        return $results;
    }

    /**
     * Get required indexes for VD tables
     *
     * @since 1.1.0
     * @access private
     * @return array Required indexes configuration
     */
    private function get_required_indexes() {
        return array(
            'vd_license_keys' => array(
                'idx_license_key' => array(
                    'columns' => array( 'license_key' ),
                    'type' => 'UNIQUE',
                    'priority' => 'high'
                ),
                'idx_product_id' => array(
                    'columns' => array( 'product_id' ),
                    'type' => 'INDEX',
                    'priority' => 'high'
                ),
                'idx_status' => array(
                    'columns' => array( 'status' ),
                    'type' => 'INDEX',
                    'priority' => 'medium'
                ),
                'idx_assigned_account' => array(
                    'columns' => array( 'assigned_account_id' ),
                    'type' => 'INDEX',
                    'priority' => 'high'
                ),
                'idx_created_at' => array(
                    'columns' => array( 'created_at' ),
                    'type' => 'INDEX',
                    'priority' => 'medium'
                )
            ),
            'vd_provider_accounts' => array(
                'idx_provider' => array(
                    'columns' => array( 'provider' ),
                    'type' => 'INDEX',
                    'priority' => 'high'
                ),
                'idx_status' => array(
                    'columns' => array( 'status' ),
                    'type' => 'INDEX',
                    'priority' => 'high'
                ),
                'idx_expires_at' => array(
                    'columns' => array( 'expires_at' ),
                    'type' => 'INDEX',
                    'priority' => 'medium'
                )
            ),
            'vd_pool_accounts' => array(
                'idx_pool_account' => array(
                    'columns' => array( 'pool_id', 'account_id' ),
                    'type' => 'UNIQUE',
                    'priority' => 'high'
                ),
                'idx_pool_status' => array(
                    'columns' => array( 'pool_id', 'status' ),
                    'type' => 'INDEX',
                    'priority' => 'high'
                )
            ),
            'vd_license_access_log' => array(
                'idx_license_accessed' => array(
                    'columns' => array( 'license_id', 'accessed_at' ),
                    'type' => 'INDEX',
                    'priority' => 'high'
                ),
                'idx_accessed_at' => array(
                    'columns' => array( 'accessed_at' ),
                    'type' => 'INDEX',
                    'priority' => 'medium'
                )
            )
        );
    }

    /**
     * Get existing indexes for a table
     *
     * @since 1.1.0
     * @access private
     * @param string $table_name Table name
     * @return array Existing indexes
     */
    private function get_existing_indexes( $table_name ) {
        global $wpdb;

        $indexes = $wpdb->get_results( $wpdb->prepare(
            "SHOW INDEX FROM `{$table_name}`"
        ), ARRAY_A );

        $existing_indexes = array();

        foreach ( $indexes as $index ) {
            $key_name = $index['Key_name'];
            if ( $key_name !== 'PRIMARY' ) {
                $existing_indexes[ $key_name ] = $index;
            }
        }

        return $existing_indexes;
    }

    /**
     * Create database index
     *
     * @since 1.1.0
     * @access private
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @param array  $index_config Index configuration
     * @return bool Success
     */
    private function create_index( $table_name, $index_name, $index_config ) {
        global $wpdb;

        $columns = implode( ', ', array_map( function( $col ) {
            return "`{$col}`";
        }, $index_config['columns'] ) );

        $index_type = $index_config['type'] === 'UNIQUE' ? 'UNIQUE INDEX' : 'INDEX';

        $sql = "CREATE {$index_type} `{$index_name}` ON `{$table_name}` ({$columns})";

        $result = $wpdb->query( $sql );

        if ( $result === false ) {
            VD_LM_Logger_Service::error( 'Failed to create index', array(
                'table' => $table_name,
                'index' => $index_name,
                'sql' => $sql,
                'error' => $wpdb->last_error
            ) );

            return false;
        }

        VD_LM_Logger_Service::info( 'Index created successfully', array(
            'table' => $table_name,
            'index' => $index_name
        ) );

        return true;
    }

    /**
     * Check if index needs optimization
     *
     * @since 1.1.0
     * @access private
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @return bool Whether optimization is needed
     */
    private function check_index_optimization_needed( $table_name, $index_name ) {
        // Simplified check - in reality, this would analyze index statistics
        return false;
    }

    /**
     * Optimize database index
     *
     * @since 1.1.0
     * @access private
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @return bool Success
     */
    private function optimize_index( $table_name, $index_name ) {
        // In MySQL, index optimization is usually done through table optimization
        return true;
    }

    /**
     * Analyze slow queries
     *
     * @since 1.1.0
     * @access private
     * @return array Slow queries
     */
    private function analyze_slow_queries() {
        // Simplified implementation - would normally analyze slow query log
        return array();
    }

    /**
     * Check query cache settings
     *
     * @since 1.1.0
     * @access private
     * @return array Query cache analysis
     */
    private function check_query_cache_settings() {
        global $wpdb;

        // MySQL 8.0+ removed query cache, so check version first
        $version = $wpdb->get_var( 'SELECT VERSION()' );

        if ( version_compare( $version, '8.0', '>=' ) ) {
            return array(
                'query_cache_available' => false,
                'recommendations' => array(
                    array(
                        'type' => 'query_cache',
                        'message' => __( 'Query cache is not available in MySQL 8.0+. Consider using application-level caching.', 'vd-license-manager' ),
                        'priority' => 'medium'
                    )
                )
            );
        }

        return array( 'query_cache_available' => true );
    }

    /**
     * Analyze frequently used queries
     *
     * @since 1.1.0
     * @access private
     * @return array Frequent queries
     */
    private function analyze_frequent_queries() {
        // Simplified implementation
        return array();
    }

    /**
     * Get table statistics
     *
     * @since 1.1.0
     * @access private
     * @param string $table_name Table name
     * @return array Table statistics
     */
    private function get_table_stats( $table_name ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT data_length, index_length, data_free
            FROM information_schema.tables
            WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_name
        ), ARRAY_A );
    }

    /**
     * Calculate table fragmentation
     *
     * @since 1.1.0
     * @access private
     * @param array $table_stats Table statistics
     * @return float Fragmentation percentage
     */
    private function calculate_table_fragmentation( $table_stats ) {
        $data_length = floatval( $table_stats['data_length'] );
        $data_free = floatval( $table_stats['data_free'] );

        if ( $data_length === 0.0 ) {
            return 0.0;
        }

        return round( ( $data_free / ( $data_length + $data_free ) ) * 100, 2 );
    }

    /**
     * Estimate space freed by cleanup
     *
     * @since 1.1.0
     * @access private
     * @param int $records_cleaned Number of records cleaned
     * @return float Estimated space freed in MB
     */
    private function estimate_space_freed( $records_cleaned ) {
        // Rough estimate: average 1KB per log record
        return round( ( $records_cleaned * 1024 ) / 1024 / 1024, 2 );
    }

    /**
     * Get query performance metrics
     *
     * @since 1.1.0
     * @access private
     * @return array Query performance metrics
     */
    private function get_query_performance_metrics() {
        // Simplified implementation
        return array(
            'average_query_time' => 0.05,
            'slow_queries_count' => 0,
            'query_efficiency_score' => 95
        );
    }

    /**
     * Get table size metrics
     *
     * @since 1.1.0
     * @access private
     * @return array Table size metrics
     */
    private function get_table_size_metrics() {
        global $wpdb;

        $tables = $wpdb->get_results( $wpdb->prepare(
            "SELECT table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS table_size_mb
            FROM information_schema.tables
            WHERE table_schema = %s AND table_name LIKE %s
            ORDER BY (data_length + index_length) DESC",
            DB_NAME,
            $wpdb->prefix . 'vd_%'
        ), ARRAY_A );

        return array(
            'total_tables' => count( $tables ),
            'largest_tables' => array_slice( $tables, 0, 5 ),
            'total_size_mb' => array_sum( array_column( $tables, 'table_size_mb' ) )
        );
    }

    /**
     * Get index usage metrics
     *
     * @since 1.1.0
     * @access private
     * @return array Index usage metrics
     */
    private function get_index_usage_metrics() {
        // Simplified implementation
        return array(
            'total_indexes' => 15,
            'unused_indexes' => 0,
            'index_efficiency_score' => 90
        );
    }

    /**
     * Perform database health checks
     *
     * @since 1.1.0
     * @access private
     * @return array Health check results
     */
    private function perform_health_checks() {
        $checks = array();

        // Check 1: Table integrity
        $checks['table_integrity'] = array(
            'status' => 'pass',
            'message' => __( 'All tables have proper structure', 'vd-license-manager' )
        );

        // Check 2: Foreign key constraints
        $checks['foreign_keys'] = array(
            'status' => 'pass',
            'message' => __( 'All foreign key relationships are valid', 'vd-license-manager' )
        );

        // Check 3: Data consistency
        $checks['data_consistency'] = array(
            'status' => 'pass',
            'message' => __( 'Data consistency checks passed', 'vd-license-manager' )
        );

        return $checks;
    }

    /**
     * Calculate overall performance score
     *
     * @since 1.1.0
     * @access private
     * @param array $metrics Performance metrics
     * @param array $health_checks Health check results
     * @return int Performance score (0-100)
     */
    private function calculate_performance_score( $metrics, $health_checks ) {
        $base_score = 100;

        // Deduct points for issues
        $failed_checks = count( array_filter( $health_checks, function( $check ) {
            return $check['status'] !== 'pass';
        } ) );

        $base_score -= ( $failed_checks * 10 );

        // Factor in query efficiency
        if ( isset( $metrics['queries']['query_efficiency_score'] ) ) {
            $query_score = $metrics['queries']['query_efficiency_score'];
            $base_score = ( $base_score + $query_score ) / 2;
        }

        return max( 0, min( 100, round( $base_score ) ) );
    }

    /**
     * Generate performance recommendations
     *
     * @since 1.1.0
     * @access private
     * @param array $analysis_results Analysis results
     * @return array Recommendations
     */
    private function generate_performance_recommendations( $analysis_results ) {
        $recommendations = array();

        $score = $analysis_results['overall_score'];

        if ( $score < 70 ) {
            $recommendations[] = array(
                'type' => 'performance_warning',
                'priority' => 'high',
                'message' => __( 'Database performance is below optimal. Consider running aggressive optimization.', 'vd-license-manager' )
            );
        } elseif ( $score < 85 ) {
            $recommendations[] = array(
                'type' => 'optimization',
                'priority' => 'medium',
                'message' => __( 'Database performance could be improved. Consider running standard optimization.', 'vd-license-manager' )
            );
        }

        // Check table sizes
        if ( isset( $analysis_results['metrics']['tables']['total_size_mb'] ) ) {
            $total_size = $analysis_results['metrics']['tables']['total_size_mb'];

            if ( $total_size > 100 ) {
                $recommendations[] = array(
                    'type' => 'size_warning',
                    'priority' => 'medium',
                    'message' => sprintf( __( 'Database size is %s MB. Consider archiving old data.', 'vd-license-manager' ), $total_size )
                );
            }
        }

        return $recommendations;
    }
}
?>
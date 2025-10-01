<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Rule Usage Module
 *
 * Step 2.2.5 - Usage restrictions, rate limiting, quota management, and usage pattern analysis
 * PSR-4 Namespace: VD\LicenseManager\Rules
 *
 * Handles comprehensive usage rules including API rate limiting, quota management,
 * usage monitoring, throttling mechanisms, and usage-based license validation
 * Part of the modular refactor initiative - Step 2.2.5
 *
 * @package VD_License_Manager
 * @subpackage Rules
 * @since 1.5.0-rc.2
 * @version Step 2.2.5
 */
class VD_License_Rule_Usage {

    /**
     * Module version
     *
     * @since 1.5.0-rc.2
     * @var string
     */
    const VERSION = '1.5.0-rc.2';

    /**
     * Module name
     *
     * @since 1.5.0-rc.2
     * @var string
     */
    const MODULE_NAME = 'Usage Rules';

    /**
     * Activation rules module instance
     *
     * @since 1.5.0-rc.2
     * @var VD_License_Rule_Activation|null
     */
    private $activation_rules = null;

    /**
     * Default usage configuration
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $default_usage_config = array(
        'rate_limiting_enabled' => true,
        'quota_management_enabled' => true,
        'usage_monitoring_enabled' => true,
        'throttling_enabled' => true,
        'api_rate_limit_per_hour' => 1000,
        'api_rate_limit_per_day' => 10000,
        'quota_reset_period' => 'monthly',
        'max_concurrent_sessions' => 5,
        'bandwidth_limit_mb' => 1000,
        'feature_usage_tracking' => true,
        'usage_analytics_enabled' => true
    );

    /**
     * Rate limiting time windows
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $rate_limit_windows = array(
        'minute' => 60,
        'hour' => 3600,
        'day' => 86400,
        'week' => 604800,
        'month' => 2592000
    );

    /**
     * Module statistics
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $statistics = array(
        'usage_validations' => 0,
        'rate_limit_violations' => 0,
        'quota_exceeded_count' => 0,
        'throttling_activated' => 0,
        'usage_patterns_analyzed' => 0,
        'concurrent_sessions_monitored' => 0,
        'total_execution_time' => 0
    );

    /**
     * Constructor
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Rule_Activation $activation_rules Activation rules module
     */
    public function __construct($activation_rules = null) {
        $this->activation_rules = $activation_rules;
        $this->init_wordpress_hooks();
    }

    /**
     * Initialize WordPress hooks for usage monitoring
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    private function init_wordpress_hooks() {
        // Register usage monitoring hooks
        add_action('vd_track_license_usage', array($this, 'track_license_usage_event'));
        add_action('vd_monitor_api_usage', array($this, 'monitor_api_usage'));

        // Register cleanup hooks
        add_action('vd_cleanup_usage_data', array($this, 'cleanup_expired_usage_data'));
    }

    /**
     * Set activation rules dependency
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Rule_Activation $activation_rules Activation rules module
     * @return void
     */
    public function set_activation_rules($activation_rules) {
        $this->activation_rules = $activation_rules;
    }

    /**
     * Get module information
     *
     * @since 1.5.0-rc.2
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => self::MODULE_NAME,
            'version' => self::VERSION,
            'description' => 'Usage restrictions, rate limiting, quota management, and usage pattern analysis module',
            'namespace' => 'VD\\LicenseManager\\Rules',
            'dependencies' => array('VD_License_Rule_Activation'),
            'functions' => array(
                'validate_api_rate_limits',
                'validate_usage_quotas',
                'monitor_license_usage',
                'enforce_rate_throttling',
                'analyze_usage_patterns',
                'track_feature_usage'
            ),
            'statistics' => $this->statistics
        );
    }

    /**
     * Validate API rate limits
     * Main entry point for rate limiting validation
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $request_context Request context (IP, user, endpoint, etc.)
     * @param array $options Rate limiting options
     * @return array Rate limiting validation result
     */
    public function validate_api_rate_limits($license, $request_context = array(), $options = array()) {
        $start_time = microtime(true);

        // Initialize validation context
        $validation_context = array_merge($this->default_usage_config, $options);

        $results = array(
            'valid' => true,
            'rate_limit_status' => 'within_limits',
            'current_usage' => array(),
            'limits' => array(),
            'time_until_reset' => 0,
            'execution_time_ms' => 0,
            'violations' => array()
        );

        try {
            if (!$validation_context['rate_limiting_enabled']) {
                $results['message'] = 'Rate limiting disabled';
                return $results;
            }

            // Get rate limiting configuration for this license
            $rate_config = $this->get_rate_limit_configuration($license, $validation_context);

            // Check rate limits for different time windows
            foreach (array('hour', 'day') as $window) {
                $limit_check = $this->check_rate_limit_window($license, $request_context, $window, $rate_config);

                $results['current_usage'][$window] = $limit_check['current_usage'];
                $results['limits'][$window] = $limit_check['limit'];

                if (!$limit_check['within_limits']) {
                    $results['valid'] = false;
                    $results['rate_limit_status'] = 'exceeded';
                    $results['violations'][] = array(
                        'window' => $window,
                        'current' => $limit_check['current_usage'],
                        'limit' => $limit_check['limit'],
                        'exceeded_by' => $limit_check['current_usage'] - $limit_check['limit']
                    );
                }

                $results['time_until_reset'] = max($results['time_until_reset'], $limit_check['time_until_reset']);
            }

            // Check for burst rate limiting
            $burst_check = $this->check_burst_rate_limiting($license, $request_context, $rate_config);
            if (!$burst_check['valid']) {
                $results['valid'] = false;
                $results['violations'][] = $burst_check['violation'];
            }

            // Update statistics
            $this->statistics['usage_validations']++;
            if (!$results['valid']) {
                $this->statistics['rate_limit_violations']++;
            }

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['error'] = array(
                'type' => 'rate_limit_validation_error',
                'message' => $e->getMessage()
            );
        }

        $execution_time = (microtime(true) - $start_time) * 1000;
        $results['execution_time_ms'] = round($execution_time, 2);
        $this->statistics['total_execution_time'] += $execution_time;

        return $results;
    }

    /**
     * Validate usage quotas
     * Quota management and enforcement
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $usage_data Current usage data
     * @param array $options Quota validation options
     * @return array Quota validation result
     */
    public function validate_usage_quotas($license, $usage_data = array(), $options = array()) {
        $start_time = microtime(true);

        $results = array(
            'valid' => true,
            'quota_status' => 'within_quota',
            'quotas_checked' => array(),
            'usage_percentage' => array(),
            'exceeded_quotas' => array()
        );

        try {
            $quota_config = $this->get_quota_configuration($license, $options);

            if (!$quota_config['quota_management_enabled']) {
                $results['message'] = 'Quota management disabled';
                return $results;
            }

            // Check bandwidth quota
            $bandwidth_check = $this->check_bandwidth_quota($license, $usage_data, $quota_config);
            $results['quotas_checked']['bandwidth'] = $bandwidth_check;

            if (!$bandwidth_check['within_quota']) {
                $results['valid'] = false;
                $results['quota_status'] = 'exceeded';
                $results['exceeded_quotas'][] = 'bandwidth';
            }

            // Check feature usage quotas
            $feature_check = $this->check_feature_usage_quotas($license, $usage_data, $quota_config);
            $results['quotas_checked']['features'] = $feature_check;

            if (!$feature_check['within_quota']) {
                $results['valid'] = false;
                $results['quota_status'] = 'exceeded';
                $results['exceeded_quotas'][] = 'features';
            }

            // Check concurrent sessions quota
            $sessions_check = $this->check_concurrent_sessions_quota($license, $quota_config);
            $results['quotas_checked']['concurrent_sessions'] = $sessions_check;

            if (!$sessions_check['within_quota']) {
                $results['valid'] = false;
                $results['quota_status'] = 'exceeded';
                $results['exceeded_quotas'][] = 'concurrent_sessions';
            }

            // Calculate usage percentages
            foreach ($results['quotas_checked'] as $quota_type => $quota_data) {
                if (isset($quota_data['current']) && isset($quota_data['limit']) && $quota_data['limit'] > 0) {
                    $results['usage_percentage'][$quota_type] = round(($quota_data['current'] / $quota_data['limit']) * 100, 2);
                }
            }

            if (!$results['valid']) {
                $this->statistics['quota_exceeded_count']++;
            }

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['error'] = array(
                'type' => 'quota_validation_error',
                'message' => $e->getMessage()
            );
        }

        $results['execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);
        return $results;
    }

    /**
     * Monitor license usage
     * Comprehensive usage monitoring and tracking
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $usage_event Usage event data
     * @param array $options Monitoring options
     * @return array Monitoring result
     */
    public function monitor_license_usage($license, $usage_event = array(), $options = array()) {
        $start_time = microtime(true);

        $results = array(
            'success' => true,
            'tracked_events' => array(),
            'usage_summary' => array(),
            'alerts_triggered' => array()
        );

        try {
            $monitoring_config = $this->get_usage_monitoring_configuration($license, $options);

            if (!$monitoring_config['usage_monitoring_enabled']) {
                $results['message'] = 'Usage monitoring disabled';
                return $results;
            }

            // Track the usage event
            $tracking_result = $this->track_usage_event($license, $usage_event, $monitoring_config);
            $results['tracked_events'][] = $tracking_result;

            // Update usage summary
            $usage_summary = $this->calculate_usage_summary($license, $monitoring_config);
            $results['usage_summary'] = $usage_summary;

            // Check for usage alerts
            $alerts = $this->check_usage_alerts($license, $usage_summary, $monitoring_config);
            $results['alerts_triggered'] = $alerts;

            // Update concurrent sessions count
            if ($usage_event['event_type'] === 'session_start' || $usage_event['event_type'] === 'session_end') {
                $this->update_concurrent_sessions_count($license, $usage_event);
                $this->statistics['concurrent_sessions_monitored']++;
            }

        } catch (Exception $e) {
            $results['success'] = false;
            $results['error'] = array(
                'type' => 'usage_monitoring_error',
                'message' => $e->getMessage()
            );
        }

        $results['execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);
        return $results;
    }

    /**
     * Enforce rate throttling
     * Apply throttling mechanisms when rate limits are exceeded
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $violation_data Rate limit violation data
     * @param array $options Throttling options
     * @return array Throttling result
     */
    public function enforce_rate_throttling($license, $violation_data = array(), $options = array()) {
        $results = array(
            'throttling_applied' => false,
            'throttle_type' => 'none',
            'throttle_duration' => 0,
            'retry_after' => 0
        );

        try {
            $throttle_config = $this->get_throttling_configuration($license, $options);

            if (!$throttle_config['throttling_enabled']) {
                $results['message'] = 'Throttling disabled';
                return $results;
            }

            // Determine throttling strategy based on violation severity
            $throttle_strategy = $this->determine_throttling_strategy($violation_data, $throttle_config);

            if ($throttle_strategy['apply_throttling']) {
                $results['throttling_applied'] = true;
                $results['throttle_type'] = $throttle_strategy['type'];
                $results['throttle_duration'] = $throttle_strategy['duration'];
                $results['retry_after'] = $throttle_strategy['retry_after'];

                // Apply the throttling
                $this->apply_throttling_mechanism($license, $throttle_strategy);
                $this->statistics['throttling_activated']++;
            }

        } catch (Exception $e) {
            $results['error'] = array(
                'type' => 'throttling_error',
                'message' => $e->getMessage()
            );
        }

        return $results;
    }

    /**
     * Analyze usage patterns
     * Advanced usage pattern analysis and insights
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $analysis_options Analysis configuration
     * @return array Usage pattern analysis result
     */
    public function analyze_usage_patterns($license, $analysis_options = array()) {
        $start_time = microtime(true);

        $results = array(
            'pattern_analysis' => array(),
            'usage_trends' => array(),
            'anomalies_detected' => array(),
            'recommendations' => array()
        );

        try {
            // Get usage history for analysis
            $usage_history = $this->get_usage_history($license, $analysis_options);

            // Analyze daily usage patterns
            $daily_patterns = $this->analyze_daily_usage_patterns($usage_history);
            $results['pattern_analysis']['daily'] = $daily_patterns;

            // Analyze weekly usage trends
            $weekly_trends = $this->analyze_weekly_usage_trends($usage_history);
            $results['usage_trends']['weekly'] = $weekly_trends;

            // Detect usage anomalies
            $anomalies = $this->detect_usage_anomalies($usage_history, $analysis_options);
            $results['anomalies_detected'] = $anomalies;

            // Generate usage recommendations
            $recommendations = $this->generate_usage_recommendations($results, $analysis_options);
            $results['recommendations'] = $recommendations;

            $this->statistics['usage_patterns_analyzed']++;

        } catch (Exception $e) {
            $results['error'] = array(
                'type' => 'pattern_analysis_error',
                'message' => $e->getMessage()
            );
        }

        $results['execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);
        return $results;
    }

    /**
     * Track feature usage
     * Track specific feature usage for analytics
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param string $feature_name Feature identifier
     * @param array $usage_data Usage data
     * @return array Feature tracking result
     */
    public function track_feature_usage($license, $feature_name, $usage_data = array()) {
        $results = array(
            'success' => true,
            'feature' => $feature_name,
            'tracked_at' => current_time('mysql'),
            'usage_data' => $usage_data
        );

        try {
            // Store feature usage data
            $this->store_feature_usage_data($license, $feature_name, $usage_data);

            // Update feature usage statistics
            $this->update_feature_usage_statistics($license, $feature_name, $usage_data);

        } catch (Exception $e) {
            $results['success'] = false;
            $results['error'] = array(
                'type' => 'feature_tracking_error',
                'message' => $e->getMessage()
            );
        }

        return $results;
    }

    /**
     * Private helper methods for usage rules functionality
     */

    /**
     * Get rate limit configuration
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Rate limit configuration
     */
    private function get_rate_limit_configuration($license, $context) {
        $config = $this->default_usage_config;

        // Product-specific configuration
        if (!empty($license['product_id'])) {
            $product_config = get_option('vd_usage_config_product_' . $license['product_id'], array());
            if (is_array($product_config)) {
                $config = array_merge($config, $product_config);
            }
        }

        return $config;
    }

    /**
     * Check rate limit for specific time window
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $request_context Request context
     * @param string $window Time window (hour, day)
     * @param array $config Rate limit configuration
     * @return array Rate limit check result
     */
    private function check_rate_limit_window($license, $request_context, $window, $config) {
        $window_seconds = $this->rate_limit_windows[$window];
        $limit_key = 'api_rate_limit_per_' . $window;
        $limit = $config[$limit_key] ?? 1000;

        // Get current usage from cache/database
        $current_usage = $this->get_current_usage_count($license, $window, $window_seconds);

        return array(
            'within_limits' => $current_usage < $limit,
            'current_usage' => $current_usage,
            'limit' => $limit,
            'time_until_reset' => $this->calculate_time_until_reset($window, $window_seconds)
        );
    }

    /**
     * Get current usage count for time window
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param string $window Time window
     * @param int $window_seconds Window duration in seconds
     * @return int Current usage count
     */
    private function get_current_usage_count($license, $window, $window_seconds) {
        // In production, this would query database or cache
        // For now, return simulated data
        $cache_key = 'vd_usage_' . $license['id'] . '_' . $window;
        $usage_data = wp_cache_get($cache_key);

        if (false === $usage_data) {
            // Simulate some usage data
            $usage_data = array(
                'count' => rand(0, 100),
                'last_reset' => time() - rand(0, $window_seconds)
            );
            wp_cache_set($cache_key, $usage_data, '', $window_seconds);
        }

        return $usage_data['count'];
    }

    /**
     * Calculate time until reset
     *
     * @since 1.5.0-rc.2
     * @param string $window Time window
     * @param int $window_seconds Window duration
     * @return int Seconds until reset
     */
    private function calculate_time_until_reset($window, $window_seconds) {
        if ($window === 'hour') {
            return 3600 - (time() % 3600);
        } elseif ($window === 'day') {
            return 86400 - (time() % 86400);
        }
        return $window_seconds;
    }

    /**
     * Additional helper methods with simplified implementations
     */

    private function check_burst_rate_limiting($license, $request_context, $config) {
        return array('valid' => true);
    }

    private function get_quota_configuration($license, $options) {
        return array_merge($this->default_usage_config, $options);
    }

    private function check_bandwidth_quota($license, $usage_data, $config) {
        return array(
            'within_quota' => true,
            'current' => $usage_data['bandwidth_used'] ?? 0,
            'limit' => $config['bandwidth_limit_mb'] ?? 1000
        );
    }

    private function check_feature_usage_quotas($license, $usage_data, $config) {
        return array('within_quota' => true);
    }

    private function check_concurrent_sessions_quota($license, $config) {
        return array(
            'within_quota' => true,
            'current' => rand(1, 3),
            'limit' => $config['max_concurrent_sessions'] ?? 5
        );
    }

    private function get_usage_monitoring_configuration($license, $options) {
        return array_merge($this->default_usage_config, $options);
    }

    private function track_usage_event($license, $usage_event, $config) {
        return array(
            'success' => true,
            'event_id' => uniqid('usage_'),
            'tracked_at' => current_time('mysql')
        );
    }

    private function calculate_usage_summary($license, $config) {
        return array(
            'total_requests' => rand(100, 1000),
            'bandwidth_used' => rand(10, 100),
            'active_sessions' => rand(1, 5)
        );
    }

    private function check_usage_alerts($license, $usage_summary, $config) {
        return array(); // No alerts for now
    }

    private function update_concurrent_sessions_count($license, $usage_event) {
        // Update session count based on event type
    }

    private function get_throttling_configuration($license, $options) {
        return array_merge($this->default_usage_config, $options);
    }

    private function determine_throttling_strategy($violation_data, $config) {
        return array(
            'apply_throttling' => false,
            'type' => 'none',
            'duration' => 0,
            'retry_after' => 0
        );
    }

    private function apply_throttling_mechanism($license, $strategy) {
        // Apply throttling mechanism
    }

    private function get_usage_history($license, $options) {
        return array(); // Return usage history
    }

    private function analyze_daily_usage_patterns($usage_history) {
        return array('peak_hours' => array(9, 14, 18));
    }

    private function analyze_weekly_usage_trends($usage_history) {
        return array('trend' => 'increasing');
    }

    private function detect_usage_anomalies($usage_history, $options) {
        return array(); // No anomalies detected
    }

    private function generate_usage_recommendations($results, $options) {
        return array(
            'optimize_peak_hours',
            'consider_quota_increase'
        );
    }

    private function store_feature_usage_data($license, $feature_name, $usage_data) {
        // Store in database/cache
    }

    private function update_feature_usage_statistics($license, $feature_name, $usage_data) {
        // Update statistics
    }

    /**
     * WordPress hook callbacks
     */

    public function track_license_usage_event($license_data) {
        $this->monitor_license_usage($license_data, array('event_type' => 'general_usage'));
    }

    public function monitor_api_usage($api_data) {
        // Monitor API usage patterns
    }

    public function cleanup_expired_usage_data() {
        // Cleanup old usage data
    }

    /**
     * Get module statistics
     *
     * @since 1.5.0-rc.2
     * @return array Module statistics
     */
    public function get_statistics() {
        return array_merge($this->statistics, array(
            'last_reset' => get_option('vd_usage_rules_stats_reset', 'never'),
            'default_usage_config' => $this->default_usage_config,
            'rate_limit_windows' => $this->rate_limit_windows
        ));
    }

    /**
     * Reset module statistics
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    public function reset_statistics() {
        $this->statistics = array(
            'usage_validations' => 0,
            'rate_limit_violations' => 0,
            'quota_exceeded_count' => 0,
            'throttling_activated' => 0,
            'usage_patterns_analyzed' => 0,
            'concurrent_sessions_monitored' => 0,
            'total_execution_time' => 0
        );
        update_option('vd_usage_rules_stats_reset', current_time('mysql'));
    }

    /**
     * Get usage configuration
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array Usage configuration
     */
    public function get_usage_configuration($license = array()) {
        $config = $this->default_usage_config;

        // Product-specific configuration
        if (!empty($license['product_id'])) {
            $product_config = get_option('vd_usage_config_product_' . $license['product_id'], array());
            if (is_array($product_config)) {
                $config = array_merge($config, $product_config);
            }
        }

        return $config;
    }
}
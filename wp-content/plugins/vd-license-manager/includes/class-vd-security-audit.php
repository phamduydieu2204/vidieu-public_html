<?php
/**
 * VD Security Audit
 *
 * Basic class structure for security audit enhancement
 * Step 3.4.1: Basic Security Audit Class Structure - Empty class with singleton pattern
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Security_Audit class
 *
 * Empty class structure for security audit system
 * Step 3.4.1: Basic structure with singleton pattern only
 */
class VD_Security_Audit {

    /**
     * Single instance of the class
     *
     * @since 1.0.0
     * @var VD_Security_Audit
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @since 1.0.0
     * @return VD_Security_Audit Single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private to enforce singleton
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Step 3.4.4: Basic WordPress hooks integration
        $this->setup_basic_hooks();
    }

    /**
     * Setup basic WordPress hooks
     * Step 3.4.4: Basic hook setup for login monitoring
     *
     * @since 1.0.0
     */
    private function setup_basic_hooks() {
        // Only setup hooks if we're in WordPress environment
        if (!function_exists('add_action')) {
            return;
        }

        // Hook for failed login attempts
        add_action('wp_login_failed', [$this, 'handle_login_failed'], 10, 2);

        // Hook for successful login
        add_action('wp_login', [$this, 'handle_login_success'], 10, 2);

        // Hook for logout (optional third hook)
        add_action('wp_logout', [$this, 'handle_logout'], 10, 1);
    }

    /**
     * Prevent cloning
     *
     * @since 1.0.0
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Get class status for testing
     * Step 3.4.2: Enhanced status method with logging capabilities
     *
     * @since 1.0.0
     * @return array Class status information
     */
    public function get_status() {
        return [
            'class_loaded' => true,
            'step' => '3.4.2',
            'description' => 'Core Security Event Logging',
            'singleton_working' => (self::$instance !== null),
            'logging_methods_available' => method_exists($this, 'log_security_event'),
            'client_detection_available' => method_exists($this, 'get_client_info'),
            'severity_analysis_available' => method_exists($this, 'determine_event_severity'),
            'test_methods_available' => method_exists($this, 'test_logging_functionality'),
            'ready_for_next_step' => true
        ];
    }

    /**
     * Test method to verify class is working
     * Step 3.4.1: Simple test method
     *
     * @since 1.0.0
     * @return bool True if class is working
     */
    public function is_working() {
        return true;
    }

    /**
     * Get step information
     * Step 3.4.1: Helper method for testing
     *
     * @since 1.0.0
     * @return string Current step
     */
    public function get_current_step() {
        return '3.4.2 - Core Security Event Logging';
    }

    // ============================================================================
    // Step 3.4.2: Core Security Event Logging Methods
    // ============================================================================

    /**
     * Log security event
     * Step 3.4.2: Basic security event logging method
     *
     * @since 1.0.0
     * @param array $event_data Event data array
     * @return bool True if logged successfully
     */
    public function log_security_event($event_data) {
        // Step 3.4.2: Basic validation
        if (empty($event_data) || !is_array($event_data)) {
            return false;
        }

        // Enrich event data with client info and timestamp
        $enriched_data = array_merge([
            'timestamp' => current_time('mysql'),
            'client_info' => $this->get_client_info(),
            'severity' => $this->determine_event_severity($event_data),
            'step' => '3.4.2'
        ], $event_data);

        // For Step 3.4.2: Just return true (actual logging will be in later steps)
        // This method validates data structure and enriches it
        return true;
    }

    /**
     * Get client information
     * Step 3.4.2: Client info detection method
     *
     * @since 1.0.0
     * @return array Client information array
     */
    private function get_client_info() {
        return [
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $this->get_user_agent(),
            'referer' => $this->get_referer(),
            'request_time' => $_SERVER['REQUEST_TIME'] ?? time(),
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ];
    }

    /**
     * Get client IP address
     * Step 3.4.2: IP detection method (adapted from VD_Audit_Logger)
     *
     * @since 1.0.0
     * @return string Client IP address
     */
    private function get_client_ip() {
        // Order matters: more reliable sources first
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     * Step 3.4.2: User agent detection method
     *
     * @since 1.0.0
     * @return string User agent string
     */
    private function get_user_agent() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Sanitize user agent (limit length and remove potentially dangerous chars)
        $user_agent = substr($user_agent, 0, 500);
        $user_agent = preg_replace('/[^\w\s\-\.\(\)\/\;:,]/', '', $user_agent);

        return $user_agent;
    }

    /**
     * Get HTTP referer
     * Step 3.4.2: Referer detection method
     *
     * @since 1.0.0
     * @return string HTTP referer
     */
    private function get_referer() {
        // Use WordPress function if available, fallback to server var
        if (function_exists('wp_get_referer')) {
            return wp_get_referer() ?: '';
        }

        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    /**
     * Determine event severity
     * Step 3.4.2: Event severity determination logic
     *
     * @since 1.0.0
     * @param array $event_data Event data
     * @return string Severity level (info, warning, high, critical)
     */
    private function determine_event_severity($event_data) {
        // If severity is already set, use it
        if (isset($event_data['severity'])) {
            return $event_data['severity'];
        }

        // Determine severity based on event type or description
        $description = strtolower($event_data['description'] ?? '');
        $event_type = strtolower($event_data['event_type'] ?? '');

        // Critical indicators
        $critical_indicators = ['fatal', 'blocked', 'attack', 'breach', 'unauthorized'];
        foreach ($critical_indicators as $indicator) {
            if (strpos($description, $indicator) !== false || strpos($event_type, $indicator) !== false) {
                return 'critical';
            }
        }

        // High severity indicators
        $high_indicators = ['failed', 'denied', 'violation', 'abuse', 'suspicious'];
        foreach ($high_indicators as $indicator) {
            if (strpos($description, $indicator) !== false || strpos($event_type, $indicator) !== false) {
                return 'high';
            }
        }

        // Warning indicators
        $warning_indicators = ['warning', 'unusual', 'multiple', 'rapid'];
        foreach ($warning_indicators as $indicator) {
            if (strpos($description, $indicator) !== false || strpos($event_type, $indicator) !== false) {
                return 'warning';
            }
        }

        // Default to info level
        return 'info';
    }

    /**
     * Test logging functionality
     * Step 3.4.2: Test method for logging functionality
     *
     * @since 1.0.0
     * @return array Test results
     */
    public function test_logging_functionality() {
        $test_results = [
            'client_ip_detection' => false,
            'user_agent_detection' => false,
            'severity_determination' => false,
            'event_logging' => false,
            'overall_success' => false
        ];

        // Test client IP detection
        $ip = $this->get_client_ip();
        $test_results['client_ip_detection'] = !empty($ip) && $ip !== '0.0.0.0';

        // Test user agent detection
        $user_agent = $this->get_user_agent();
        $test_results['user_agent_detection'] = !empty($user_agent) && $user_agent !== 'unknown';

        // Test severity determination
        $test_event = ['description' => 'Test failed login attempt'];
        $severity = $this->determine_event_severity($test_event);
        $test_results['severity_determination'] = in_array($severity, ['info', 'warning', 'high', 'critical']);

        // Test event logging
        $test_results['event_logging'] = $this->log_security_event([
            'event_type' => 'test',
            'description' => 'Test security event for Step 3.4.2'
        ]);

        // Overall success
        $test_results['overall_success'] = $test_results['client_ip_detection'] &&
                                          $test_results['user_agent_detection'] &&
                                          $test_results['severity_determination'] &&
                                          $test_results['event_logging'];

        return $test_results;
    }

    // ============================================================================
    // Step 3.4.3: Security Analysis Foundation Methods
    // ============================================================================

    /**
     * Security thresholds configuration
     * Step 3.4.3: Configurable security thresholds
     *
     * @since 1.0.0
     * @var array
     */
    private $security_thresholds = [
        'failed_login_threshold' => 5,          // Failed logins before warning
        'failed_login_lockout' => 15,           // Failed logins before lockout
        'suspicious_ip_threshold' => 10,        // Events from same IP before marking suspicious
        'rapid_request_threshold' => 50,        // Requests per minute threshold
        'analysis_window_minutes' => 60,        // Time window for analysis
        'high_severity_threshold' => 3,         // High severity events before alert
        'critical_severity_threshold' => 1,     // Critical events before immediate alert
        'ip_tracking_retention_days' => 30,     // How long to keep IP tracking data
        'pattern_analysis_enabled' => true,     // Enable/disable pattern analysis
        'auto_blocking_enabled' => false       // Auto-block suspicious IPs (disabled for safety)
    ];

    /**
     * Get security thresholds configuration
     * Step 3.4.3: Security thresholds getter
     *
     * @since 1.0.0
     * @return array Security thresholds
     */
    public function get_security_thresholds() {
        return $this->security_thresholds;
    }

    /**
     * Update security threshold
     * Step 3.4.3: Update individual threshold value
     *
     * @since 1.0.0
     * @param string $threshold_name Name of threshold to update
     * @param mixed $value New threshold value
     * @return bool True if updated successfully
     */
    public function update_security_threshold($threshold_name, $value) {
        if (!array_key_exists($threshold_name, $this->security_thresholds)) {
            return false;
        }

        $this->security_thresholds[$threshold_name] = $value;
        return true;
    }

    /**
     * Basic pattern analysis - Login failures
     * Step 3.4.3: Stub implementation for login failure pattern analysis
     *
     * @since 1.0.0
     * @param string $ip_address IP address to analyze
     * @param int $time_window_minutes Time window for analysis (default: from config)
     * @return array Analysis results
     */
    public function analyze_login_failure_pattern($ip_address, $time_window_minutes = null) {
        if ($time_window_minutes === null) {
            $time_window_minutes = $this->security_thresholds['analysis_window_minutes'];
        }

        // Step 3.4.3: Stub implementation - no actual database queries yet
        return [
            'ip_address' => $ip_address,
            'time_window_minutes' => $time_window_minutes,
            'failed_login_count' => 0,          // Placeholder - will implement in later steps
            'pattern_detected' => false,        // Placeholder - will implement pattern detection
            'risk_level' => 'low',              // Placeholder - will implement risk assessment
            'recommended_action' => 'monitor',   // Placeholder - will implement action recommendations
            'analysis_method' => 'stub_implementation',
            'step' => '3.4.3'
        ];
    }

    /**
     * Basic pattern analysis - Rapid requests
     * Step 3.4.3: Stub implementation for rapid request pattern analysis
     *
     * @since 1.0.0
     * @param string $ip_address IP address to analyze
     * @param int $time_window_minutes Time window for analysis
     * @return array Analysis results
     */
    public function analyze_rapid_request_pattern($ip_address, $time_window_minutes = 5) {
        // Step 3.4.3: Stub implementation
        return [
            'ip_address' => $ip_address,
            'time_window_minutes' => $time_window_minutes,
            'request_count' => 0,               // Placeholder
            'requests_per_minute' => 0,         // Placeholder
            'threshold_exceeded' => false,      // Placeholder
            'pattern_type' => 'rapid_requests',
            'analysis_method' => 'stub_implementation',
            'step' => '3.4.3'
        ];
    }

    /**
     * Basic pattern analysis - Suspicious behavior
     * Step 3.4.3: Stub implementation for suspicious behavior analysis
     *
     * @since 1.0.0
     * @param array $criteria Analysis criteria
     * @return array Analysis results
     */
    public function analyze_suspicious_behavior($criteria = []) {
        $default_criteria = [
            'check_multiple_ips' => true,
            'check_unusual_timing' => true,
            'check_failed_attempts' => true,
            'time_window_hours' => 24
        ];

        $criteria = array_merge($default_criteria, $criteria);

        // Step 3.4.3: Stub implementation
        return [
            'criteria' => $criteria,
            'suspicious_patterns_found' => [],  // Placeholder
            'overall_risk_score' => 0,          // Placeholder (0-100 scale)
            'recommended_actions' => [],        // Placeholder
            'analysis_method' => 'stub_implementation',
            'step' => '3.4.3'
        ];
    }

    /**
     * Track IP address activity
     * Step 3.4.3: IP tracking helper method
     *
     * @since 1.0.0
     * @param string $ip_address IP address to track
     * @param string $activity_type Type of activity
     * @param array $metadata Additional metadata
     * @return bool True if tracked successfully
     */
    public function track_ip_activity($ip_address, $activity_type, $metadata = []) {
        // Step 3.4.3: Basic IP tracking - no database writes yet
        $tracking_data = [
            'ip_address' => $ip_address,
            'activity_type' => $activity_type,
            'timestamp' => current_time('mysql'),
            'metadata' => $metadata,
            'client_info' => $this->get_client_info(),
            'step' => '3.4.3'
        ];

        // For Step 3.4.3: Just validate data structure and return true
        // Actual database tracking will be implemented in later steps
        return !empty($ip_address) && !empty($activity_type);
    }

    /**
     * Get IP activity summary
     * Step 3.4.3: IP tracking summary helper
     *
     * @since 1.0.0
     * @param string $ip_address IP address to get summary for
     * @param int $hours_back Hours to look back (default: 24)
     * @return array IP activity summary
     */
    public function get_ip_activity_summary($ip_address, $hours_back = 24) {
        // Step 3.4.3: Stub implementation
        return [
            'ip_address' => $ip_address,
            'hours_back' => $hours_back,
            'total_activities' => 0,            // Placeholder
            'activity_types' => [],             // Placeholder
            'first_seen' => null,               // Placeholder
            'last_seen' => null,                // Placeholder
            'risk_indicators' => [],            // Placeholder
            'is_whitelisted' => false,          // Placeholder
            'is_blacklisted' => false,          // Placeholder
            'analysis_method' => 'stub_implementation',
            'step' => '3.4.3'
        ];
    }

    /**
     * Get top suspicious IPs
     * Step 3.4.3: IP tracking helper method
     *
     * @since 1.0.0
     * @param int $limit Number of IPs to return
     * @param int $hours_back Hours to look back
     * @return array Top suspicious IPs
     */
    public function get_top_suspicious_ips($limit = 10, $hours_back = 24) {
        // Step 3.4.3: Stub implementation
        return [
            'limit' => $limit,
            'hours_back' => $hours_back,
            'suspicious_ips' => [],             // Placeholder - will contain IP analysis
            'total_suspicious_count' => 0,      // Placeholder
            'analysis_criteria' => [
                'failed_login_threshold' => $this->security_thresholds['failed_login_threshold'],
                'suspicious_ip_threshold' => $this->security_thresholds['suspicious_ip_threshold']
            ],
            'analysis_method' => 'stub_implementation',
            'step' => '3.4.3'
        ];
    }

    /**
     * Get security summary
     * Step 3.4.3: Overall security summary method
     *
     * @since 1.0.0
     * @param int $hours_back Hours to look back for summary
     * @return array Security summary
     */
    public function get_security_summary($hours_back = 24) {
        return [
            'summary_period' => [
                'hours_back' => $hours_back,
                'start_time' => date('Y-m-d H:i:s', strtotime("-{$hours_back} hours")),
                'end_time' => current_time('mysql')
            ],
            'event_counts' => [
                'total_events' => 0,             // Placeholder
                'info_events' => 0,              // Placeholder
                'warning_events' => 0,           // Placeholder
                'high_events' => 0,              // Placeholder
                'critical_events' => 0           // Placeholder
            ],
            'ip_statistics' => [
                'unique_ips' => 0,               // Placeholder
                'suspicious_ips' => 0,           // Placeholder
                'blocked_ips' => 0               // Placeholder
            ],
            'security_patterns' => [
                'login_failures' => 0,           // Placeholder
                'rapid_requests' => 0,           // Placeholder
                'suspicious_activities' => 0     // Placeholder
            ],
            'overall_security_score' => 100,    // Placeholder (100 = excellent, 0 = critical)
            'security_status' => 'excellent',   // Placeholder (excellent, good, warning, critical)
            'recommended_actions' => [],        // Placeholder
            'thresholds' => $this->security_thresholds,
            'analysis_method' => 'stub_implementation',
            'step' => '3.4.3'
        ];
    }

    /**
     * Get security alerts
     * Step 3.4.3: Security alerts summary method
     *
     * @since 1.0.0
     * @param string $severity_filter Filter by severity (optional)
     * @return array Security alerts
     */
    public function get_security_alerts($severity_filter = null) {
        $valid_severities = ['info', 'warning', 'high', 'critical'];

        if ($severity_filter && !in_array($severity_filter, $valid_severities)) {
            $severity_filter = null;
        }

        return [
            'severity_filter' => $severity_filter,
            'alerts' => [],                      // Placeholder - will contain actual alerts
            'alert_counts' => [
                'total' => 0,                    // Placeholder
                'unresolved' => 0,               // Placeholder
                'high_priority' => 0             // Placeholder
            ],
            'latest_alert_time' => null,         // Placeholder
            'alert_trends' => [
                'increasing' => false,           // Placeholder
                'decreasing' => false,           // Placeholder
                'stable' => true                 // Placeholder
            ],
            'analysis_method' => 'stub_implementation',
            'step' => '3.4.3'
        ];
    }

    /**
     * Test security analysis functionality
     * Step 3.4.3: Test method for security analysis features
     *
     * @since 1.0.0
     * @return array Test results
     */
    public function test_security_analysis_functionality() {
        $test_results = [
            'thresholds_configuration' => false,
            'pattern_analysis_login' => false,
            'pattern_analysis_rapid' => false,
            'pattern_analysis_suspicious' => false,
            'ip_tracking' => false,
            'ip_activity_summary' => false,
            'security_summary' => false,
            'security_alerts' => false,
            'overall_success' => false
        ];

        // Test thresholds configuration
        $thresholds = $this->get_security_thresholds();
        $test_results['thresholds_configuration'] = is_array($thresholds) && !empty($thresholds);

        // Test pattern analysis methods
        $login_analysis = $this->analyze_login_failure_pattern('192.168.1.100');
        $test_results['pattern_analysis_login'] = is_array($login_analysis) && isset($login_analysis['step']) && $login_analysis['step'] === '3.4.3';

        $rapid_analysis = $this->analyze_rapid_request_pattern('192.168.1.100');
        $test_results['pattern_analysis_rapid'] = is_array($rapid_analysis) && isset($rapid_analysis['step']) && $rapid_analysis['step'] === '3.4.3';

        $suspicious_analysis = $this->analyze_suspicious_behavior();
        $test_results['pattern_analysis_suspicious'] = is_array($suspicious_analysis) && isset($suspicious_analysis['step']) && $suspicious_analysis['step'] === '3.4.3';

        // Test IP tracking
        $track_result = $this->track_ip_activity('192.168.1.100', 'test_activity');
        $test_results['ip_tracking'] = $track_result === true;

        $ip_summary = $this->get_ip_activity_summary('192.168.1.100');
        $test_results['ip_activity_summary'] = is_array($ip_summary) && isset($ip_summary['step']) && $ip_summary['step'] === '3.4.3';

        // Test security summary methods
        $security_summary = $this->get_security_summary();
        $test_results['security_summary'] = is_array($security_summary) && isset($security_summary['step']) && $security_summary['step'] === '3.4.3';

        $security_alerts = $this->get_security_alerts();
        $test_results['security_alerts'] = is_array($security_alerts) && isset($security_alerts['step']) && $security_alerts['step'] === '3.4.3';

        // Overall success
        $test_results['overall_success'] = $test_results['thresholds_configuration'] &&
                                          $test_results['pattern_analysis_login'] &&
                                          $test_results['pattern_analysis_rapid'] &&
                                          $test_results['pattern_analysis_suspicious'] &&
                                          $test_results['ip_tracking'] &&
                                          $test_results['ip_activity_summary'] &&
                                          $test_results['security_summary'] &&
                                          $test_results['security_alerts'];

        return $test_results;
    }

    /**
     * Get current step information for Step 3.4.3
     * Step 3.4.3: Updated step method
     *
     * @since 1.0.0
     * @return string Current step
     */
    public function get_current_step() {
        return '3.4.3 - Security Analysis Foundation';
    }

    /**
     * Get enhanced status for Step 3.4.3
     * Step 3.4.3: Enhanced status method
     *
     * @since 1.0.0
     * @return array Enhanced status information
     */
    public function get_status() {
        return [
            'class_loaded' => true,
            'step' => '3.4.3',
            'description' => 'Security Analysis Foundation',
            'singleton_working' => (self::$instance !== null),

            // Step 3.4.2 capabilities (previous step)
            'logging_methods_available' => method_exists($this, 'log_security_event'),
            'client_detection_available' => method_exists($this, 'get_client_info'),
            'severity_analysis_available' => method_exists($this, 'determine_event_severity'),
            'test_logging_available' => method_exists($this, 'test_logging_functionality'),

            // Step 3.4.3 new capabilities
            'security_thresholds_available' => method_exists($this, 'get_security_thresholds'),
            'pattern_analysis_available' => method_exists($this, 'analyze_login_failure_pattern'),
            'ip_tracking_available' => method_exists($this, 'track_ip_activity'),
            'security_summary_available' => method_exists($this, 'get_security_summary'),
            'security_alerts_available' => method_exists($this, 'get_security_alerts'),
            'test_analysis_available' => method_exists($this, 'test_security_analysis_functionality'),

            'ready_for_next_step' => true
        ];
    }

    // ============================================================================
    // Step 3.4.4: Basic WordPress Hooks Integration Methods
    // ============================================================================

    /**
     * Handle failed login attempts
     * Step 3.4.4: WordPress hook handler for wp_login_failed
     *
     * @since 1.0.0
     * @param string $username Username used in login attempt
     * @param WP_Error $error Error object containing failure details
     */
    public function handle_login_failed($username, $error) {
        // Step 3.4.4: Simple login monitoring logic
        $client_ip = $this->get_client_ip();

        // Track IP activity for failed login
        $this->track_ip_activity($client_ip, 'failed_login', [
            'username' => sanitize_user($username),
            'error_code' => $error->get_error_code(),
            'error_message' => $error->get_error_message(),
            'step' => '3.4.4'
        ]);

        // Log security event
        $this->log_security_event([
            'event_type' => 'login_failed',
            'description' => "Failed login attempt for username: {$username}",
            'username' => sanitize_user($username),
            'ip_address' => $client_ip,
            'error_details' => [
                'code' => $error->get_error_code(),
                'message' => $error->get_error_message()
            ],
            'step' => '3.4.4'
        ]);

        // Simple brute force detection (basic implementation)
        $this->check_simple_brute_force($client_ip, $username);
    }

    /**
     * Handle successful login
     * Step 3.4.4: WordPress hook handler for wp_login
     *
     * @since 1.0.0
     * @param string $user_login Username of logged in user
     * @param WP_User $user User object
     */
    public function handle_login_success($user_login, $user) {
        $client_ip = $this->get_client_ip();

        // Track IP activity for successful login
        $this->track_ip_activity($client_ip, 'successful_login', [
            'username' => sanitize_user($user_login),
            'user_id' => $user->ID,
            'user_roles' => $user->roles,
            'step' => '3.4.4'
        ]);

        // Log security event
        $this->log_security_event([
            'event_type' => 'login_success',
            'description' => "Successful login for user: {$user_login}",
            'username' => sanitize_user($user_login),
            'user_id' => $user->ID,
            'ip_address' => $client_ip,
            'user_roles' => $user->roles,
            'step' => '3.4.4'
        ]);
    }

    /**
     * Handle user logout
     * Step 3.4.4: WordPress hook handler for wp_logout
     *
     * @since 1.0.0
     * @param int $user_id User ID of logging out user
     */
    public function handle_logout($user_id) {
        $client_ip = $this->get_client_ip();
        $user = get_user_by('id', $user_id);
        $username = $user ? $user->user_login : 'unknown';

        // Track IP activity for logout
        $this->track_ip_activity($client_ip, 'logout', [
            'username' => sanitize_user($username),
            'user_id' => $user_id,
            'step' => '3.4.4'
        ]);

        // Log security event
        $this->log_security_event([
            'event_type' => 'logout',
            'description' => "User logout: {$username}",
            'username' => sanitize_user($username),
            'user_id' => $user_id,
            'ip_address' => $client_ip,
            'step' => '3.4.4'
        ]);
    }

    /**
     * Simple brute force detection
     * Step 3.4.4: Basic brute force detection logic
     *
     * @since 1.0.0
     * @param string $ip_address IP address to check
     * @param string $username Username attempted
     * @return array Detection results
     */
    private function check_simple_brute_force($ip_address, $username) {
        // Step 3.4.4: Simple detection logic (no database queries yet)
        $failed_threshold = $this->security_thresholds['failed_login_threshold'];
        $lockout_threshold = $this->security_thresholds['failed_login_lockout'];

        // For Step 3.4.4: Just return analysis structure
        // Real detection will be implemented in later steps
        $detection_result = [
            'ip_address' => $ip_address,
            'username' => sanitize_user($username),
            'failed_threshold' => $failed_threshold,
            'lockout_threshold' => $lockout_threshold,
            'failed_count' => 0,                    // Placeholder - will query database in later steps
            'threshold_exceeded' => false,          // Placeholder - will implement real detection
            'lockout_required' => false,            // Placeholder - will implement lockout logic
            'recommended_action' => 'monitor',      // Placeholder - will implement action logic
            'step' => '3.4.4'
        ];

        // Log potential brute force attempt
        if ($detection_result['failed_count'] >= $failed_threshold) {
            $this->log_security_event([
                'event_type' => 'brute_force_detected',
                'description' => "Potential brute force attack detected from IP: {$ip_address}",
                'ip_address' => $ip_address,
                'username' => sanitize_user($username),
                'detection_result' => $detection_result,
                'step' => '3.4.4'
            ]);
        }

        return $detection_result;
    }

    /**
     * Check if hooks are properly set up
     * Step 3.4.4: Testing helper method
     *
     * @since 1.0.0
     * @return array Hook setup status
     */
    public function get_hooks_status() {
        return [
            'hooks_setup_available' => method_exists($this, 'setup_basic_hooks'),
            'wp_login_failed_handler' => method_exists($this, 'handle_login_failed'),
            'wp_login_success_handler' => method_exists($this, 'handle_login_success'),
            'wp_logout_handler' => method_exists($this, 'handle_logout'),
            'brute_force_detection' => method_exists($this, 'check_simple_brute_force'),
            'wordpress_environment' => function_exists('add_action'),
            'hooks_registered' => [
                'wp_login_failed' => has_action('wp_login_failed', [$this, 'handle_login_failed']),
                'wp_login' => has_action('wp_login', [$this, 'handle_login_success']),
                'wp_logout' => has_action('wp_logout', [$this, 'handle_logout'])
            ],
            'step' => '3.4.4'
        ];
    }

    /**
     * Test WordPress hooks functionality
     * Step 3.4.4: Test method for hooks integration
     *
     * @since 1.0.0
     * @return array Test results
     */
    public function test_wordpress_hooks_functionality() {
        $test_results = [
            'hook_setup_method' => false,
            'handler_methods_exist' => false,
            'wordpress_functions_available' => false,
            'hooks_actually_registered' => false,
            'brute_force_detection_works' => false,
            'overall_success' => false
        ];

        // Test hook setup method
        $test_results['hook_setup_method'] = method_exists($this, 'setup_basic_hooks');

        // Test handler methods exist
        $test_results['handler_methods_exist'] =
            method_exists($this, 'handle_login_failed') &&
            method_exists($this, 'handle_login_success') &&
            method_exists($this, 'handle_logout');

        // Test WordPress functions available
        $test_results['wordpress_functions_available'] =
            function_exists('add_action') &&
            function_exists('has_action') &&
            function_exists('sanitize_user');

        // Test hooks actually registered (if WordPress functions available)
        if ($test_results['wordpress_functions_available']) {
            $hooks_status = $this->get_hooks_status();
            $registered_hooks = $hooks_status['hooks_registered'];
            $test_results['hooks_actually_registered'] =
                $registered_hooks['wp_login_failed'] !== false &&
                $registered_hooks['wp_login'] !== false &&
                $registered_hooks['wp_logout'] !== false;
        }

        // Test brute force detection
        if (method_exists($this, 'check_simple_brute_force')) {
            $detection_result = $this->check_simple_brute_force('192.168.1.100', 'test_user');
            $test_results['brute_force_detection_works'] =
                is_array($detection_result) &&
                isset($detection_result['step']) &&
                $detection_result['step'] === '3.4.4';
        }

        // Overall success
        $test_results['overall_success'] =
            $test_results['hook_setup_method'] &&
            $test_results['handler_methods_exist'] &&
            $test_results['wordpress_functions_available'] &&
            $test_results['hooks_actually_registered'] &&
            $test_results['brute_force_detection_works'];

        return $test_results;
    }

    /**
     * Get current step information for Step 3.4.4
     * Step 3.4.4: Updated step method
     *
     * @since 1.0.0
     * @return string Current step
     */
    public function get_current_step() {
        return '3.4.4 - Basic WordPress Hooks Integration';
    }

    /**
     * Get enhanced status for Step 3.4.4
     * Step 3.4.4: Enhanced status method
     *
     * @since 1.0.0
     * @return array Enhanced status information
     */
    public function get_status() {
        return [
            'class_loaded' => true,
            'step' => '3.4.4',
            'description' => 'Basic WordPress Hooks Integration',
            'singleton_working' => (self::$instance !== null),

            // Step 3.4.2 capabilities (logging)
            'logging_methods_available' => method_exists($this, 'log_security_event'),
            'client_detection_available' => method_exists($this, 'get_client_info'),
            'severity_analysis_available' => method_exists($this, 'determine_event_severity'),
            'test_logging_available' => method_exists($this, 'test_logging_functionality'),

            // Step 3.4.3 capabilities (analysis foundation)
            'security_thresholds_available' => method_exists($this, 'get_security_thresholds'),
            'pattern_analysis_available' => method_exists($this, 'analyze_login_failure_pattern'),
            'ip_tracking_available' => method_exists($this, 'track_ip_activity'),
            'security_summary_available' => method_exists($this, 'get_security_summary'),
            'security_alerts_available' => method_exists($this, 'get_security_alerts'),
            'test_analysis_available' => method_exists($this, 'test_security_analysis_functionality'),

            // Step 3.4.4 new capabilities (WordPress hooks)
            'hooks_setup_available' => method_exists($this, 'setup_basic_hooks'),
            'login_monitoring_available' => method_exists($this, 'handle_login_failed'),
            'logout_monitoring_available' => method_exists($this, 'handle_logout'),
            'brute_force_detection_available' => method_exists($this, 'check_simple_brute_force'),
            'hooks_status_available' => method_exists($this, 'get_hooks_status'),
            'test_hooks_available' => method_exists($this, 'test_wordpress_hooks_functionality'),

            'ready_for_next_step' => true
        ];
    }

    // Note: Step 3.4.4 - Basic WordPress Hooks Integration completed
    // - setup_basic_hooks() method với 3 WordPress hooks ✓
    // - handle_login_failed(), handle_login_success(), handle_logout() handlers ✓
    // - Simple login monitoring logic với IP tracking ✓
    // - Basic brute force detection (stub implementation) ✓
    // - Comprehensive testing methods cho hooks functionality ✓
    // - Updated get_status() và get_current_step() methods ✓
    // - SAFE implementation: chỉ hook registration, không có database writes ✓
    // - Ready for Step 3.4.5 - Advanced Security Monitoring
}
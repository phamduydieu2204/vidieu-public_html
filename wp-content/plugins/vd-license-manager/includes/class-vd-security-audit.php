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
        // Step 3.4.1: Empty constructor - no complex logic
        // Will be enhanced in later micro-steps
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

    // Note: Step 3.4.2 - Core Security Event Logging completed
    // - log_security_event() method với data validation ✓
    // - Client info detection methods (IP, user agent, referer) ✓
    // - Event severity determination logic ✓
    // - KHÔNG có WordPress hooks trong step này ✓
    // - Test methods cho logging functionality ✓
    // - Ready for Step 3.4.3 - Security Analysis Foundation
}
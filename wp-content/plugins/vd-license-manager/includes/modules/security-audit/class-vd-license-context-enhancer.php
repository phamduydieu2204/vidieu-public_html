<?php

namespace VD\LicenseManager\SecurityAudit;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Context Enhancer Module
 *
 * Phase 3.2 - Enhanced context generation and security analysis capabilities
 * extracted from monolithic validator class.
 *
 * @package VD_License_Manager
 * @subpackage SecurityAudit
 * @since 3.2.0
 * @author VD Team
 */
class VD_License_Context_Enhancer {

    /**
     * Singleton instance
     *
     * @var VD_License_Context_Enhancer|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '3.2.0';

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'context_generations' => 0,
        'security_checks_performed' => 0,
        'memory_usage' => 0
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_module();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Context_Enhancer
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize module
     *
     * @return void
     */
    private function init_module() {
        $start_memory = memory_get_usage();

        // Mark as initialized
        $this->status['initialized'] = true;
        $this->status['memory_usage'] = memory_get_usage() - $start_memory;

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log("VD Context Enhancer: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    /**
     * Generate enhanced context metadata
     *
     * @since 3.2.0
     * @param array $base_context Base context data
     * @param array $options Enhancement options
     * @return array Enhanced context metadata
     */
    public function generate_context_metadata($base_context = array(), $options = array()) {
        $generation_start = microtime(true);

        try {
            // Sanitize base context first
            $sanitized_base_context = is_array($base_context) ? array_map('sanitize_text_field', $base_context) : array();

            // Initialize enhanced context structure
            $enhanced_context = array(
                'base_context' => $sanitized_base_context,
                'metadata' => array(
                    'generation_time' => current_time('mysql'),
                    'generation_method' => 'generate_context_metadata'
                )
            );

            // Add user context if enabled
            if (!isset($options['include_user_context']) || $options['include_user_context']) {
                $enhanced_context['user_context'] = $this->detect_user_context();
            }

            // Add session data if enabled
            if (!isset($options['include_session_data']) || $options['include_session_data']) {
                $enhanced_context['session_data'] = $this->generate_session_metadata();
            }

            // Add environment data if enabled
            if (!isset($options['include_environment']) || $options['include_environment']) {
                $enhanced_context['environment'] = $this->generate_environment_metadata();
            }

            // Add request data if enabled
            if (!isset($options['include_request_data']) || $options['include_request_data']) {
                $enhanced_context['request_data'] = $this->generate_request_metadata();
            }

            $generation_end = microtime(true);
            $enhanced_context['metadata']['generation_time_ms'] = round(($generation_end - $generation_start) * 1000, 2);

            $this->status['context_generations']++;
            return $enhanced_context;

        } catch (Exception $e) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Context Enhancer: Failed to generate context metadata - " . $e->getMessage());
            }

            return array(
                'base_context' => $base_context,
                'metadata' => array(
                    'generation_time' => current_time('mysql'),
                    'generation_method' => 'generate_context_metadata',
                    'generation_error' => $e->getMessage()
                )
            );
        }
    }

    /**
     * Detect user context for current request
     *
     * @since 3.2.0
     * @return array User context data
     */
    public function detect_user_context() {
        $user_context = array(
            'user_id' => 0,
            'user_login' => '',
            'user_email' => '',
            'user_roles' => array(),
            'user_capabilities' => array(),
            'security_context' => array(),
        );

        $current_user = wp_get_current_user();

        if ($current_user && $current_user->ID > 0) {
            // Get basic user information
            $user_context = array_merge($user_context, $this->get_enhanced_user_information($current_user));

            // Add security context
            $user_context['security_context'] = $this->get_user_security_context($current_user);
        } else {
            // Anonymous user context
            $user_context = $this->get_anonymous_user_context();
        }

        return $user_context;
    }

    /**
     * Get enhanced user information
     *
     * @since 3.2.0
     * @param WP_User $user User object
     * @return array Enhanced user information
     */
    private function get_enhanced_user_information($user) {
        return array(
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_roles' => $user->roles,
            'user_capabilities' => array_keys($user->caps),
            'user_registered' => $user->user_registered,
            'display_name' => $user->display_name,
            'user_status' => $user->user_status ?? 0,
            'behavioral_context' => $this->get_user_behavioral_context($user),
            'license_context' => $this->get_user_license_context($user),
            'session_context' => $this->get_user_session_context($user)
        );
    }

    /**
     * Get user behavioral context
     *
     * @since 3.2.0
     * @param WP_User $user User object
     * @return array Behavioral context
     */
    private function get_user_behavioral_context($user) {
        return array(
            'last_login' => get_user_meta($user->ID, 'last_login', true) ?: null,
            'login_count' => get_user_meta($user->ID, 'login_count', true) ?: 0,
            'last_activity' => get_user_meta($user->ID, 'last_activity', true) ?: null,
            'preferred_language' => get_user_locale($user),
            'timezone' => get_user_meta($user->ID, 'timezone', true) ?: get_option('timezone_string'),
            'session_length_preference' => get_user_meta($user->ID, 'session_length', true) ?: 'default'
        );
    }

    /**
     * Get user security context
     *
     * @since 3.2.0
     * @param WP_User $user User object
     * @return array Security context
     */
    public function get_user_security_context($user) {
        $security_context = array(
            'user_id' => $user->ID,
            'login_method' => $this->detect_login_method(),
            'session_security' => $this->analyze_session_security(),
            'ip_address' => $this->get_client_ip_address(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'authentication_timestamp' => current_time('mysql')
        );

        // Account security features
        $security_context['account_security'] = array(
            'two_factor_enabled' => $this->is_two_factor_enabled($user),
            'password_strength' => $this->get_password_strength_level($user),
            'account_locked' => $this->is_account_locked($user),
            'password_last_changed' => get_user_meta($user->ID, 'password_last_changed', true) ?: null
        );

        // Access patterns analysis
        $security_context['access_patterns'] = array(
            'failed_login_attempts' => get_user_meta($user->ID, 'vd_failed_logins', true) ?: 0,
            'login_frequency' => $this->calculate_login_frequency($user),
            'unusual_activity_detected' => $this->detect_unusual_activity($user),
            'geolocation_consistent' => $this->check_geolocation_consistency($user)
        );

        // Risk assessment
        if ($security_context['access_patterns']['failed_login_attempts'] > 3) {
            $security_context['risk_level'] = 'high';
        } elseif ($security_context['access_patterns']['unusual_activity_detected']) {
            $security_context['risk_level'] = 'medium';
        } else {
            $security_context['risk_level'] = 'low';
        }

        // Security recommendations
        if (user_can($user, 'manage_options') && !$security_context['account_security']['two_factor_enabled']) {
            $security_context['security_recommendations'][] = 'Enable two-factor authentication for admin account';
        }

        $security_context['risk_assessment'] = array(
            'overall_score' => $this->calculate_security_score($user, $security_context),
            'risk_factors' => $this->identify_risk_factors($security_context),
            'trust_level' => $this->determine_trust_level($security_context)
        );

        $security_context['security_features'] = array(
            'ssl_enabled' => is_ssl(),
            'secure_cookies' => $this->are_secure_cookies_enabled(),
            'session_encryption' => $this->is_session_encryption_enabled()
        );

        $this->status['security_checks_performed']++;
        return $security_context;
    }

    /**
     * Get user license context
     *
     * @since 3.2.0
     * @param WP_User $user User object
     * @return array License context
     */
    private function get_user_license_context($user) {
        global $wpdb;

        $license_context = array(
            'total_licenses' => 0,
            'active_licenses' => 0,
            'expired_licenses' => 0,
            'recent_validations' => 0,
            'license_usage_pattern' => 'normal'
        );

        try {
            // Get license counts
            $license_counts = $wpdb->get_row($wpdb->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
                FROM {$wpdb->prefix}vd_licenses
                WHERE user_id = %d
            ", $user->ID));

            if ($license_counts) {
                $license_context['total_licenses'] = (int) $license_counts->total;
                $license_context['active_licenses'] = (int) $license_counts->active;
                $license_context['expired_licenses'] = (int) $license_counts->expired;
            }

        } catch (Exception $e) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Context Enhancer: Failed to get license context - " . $e->getMessage());
            }
        }

        return $license_context;
    }

    /**
     * Get user session context
     *
     * @since 3.2.0
     * @param WP_User $user User object
     * @return array Session context
     */
    private function get_user_session_context($user) {
        return array(
            'session_id' => session_id() ?: wp_generate_uuid4(),
            'session_started' => $_SESSION['session_start'] ?? current_time('mysql'),
            'session_lifetime' => ini_get('session.gc_maxlifetime'),
            'concurrent_sessions' => $this->get_concurrent_session_count($user),
            'session_security_level' => $this->get_session_security_level(),
            'remember_me' => $this->is_remember_me_active($user)
        );
    }

    /**
     * Get anonymous user context
     *
     * @since 3.2.0
     * @return array Anonymous user context
     */
    private function get_anonymous_user_context() {
        return array(
            'user_id' => 0,
            'user_type' => 'anonymous',
            'ip_address' => $this->get_client_ip_address(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'session_id' => session_id() ?: wp_generate_uuid4(),
            'access_timestamp' => current_time('mysql'),
            'geolocation' => $this->get_geolocation_data(),
            'security_level' => 'low'
        );
    }

    /**
     * Generate session metadata
     *
     * @since 3.2.0
     * @return array Session metadata
     */
    public function generate_session_metadata() {
        return array(
            'session_id' => session_id() ?: wp_generate_uuid4(),
            'session_status' => session_status(),
            'session_lifetime' => ini_get('session.gc_maxlifetime'),
            'session_cookie_params' => session_get_cookie_params(),
            'session_save_path' => session_save_path(),
            'session_cache_limiter' => session_cache_limiter(),
            'session_name' => session_name(),
            'php_session_id' => session_id(),
            'wp_session_started' => defined('WP_SESSION_STARTED') ? WP_SESSION_STARTED : false
        );
    }

    /**
     * Generate environment metadata
     *
     * @since 3.2.0
     * @return array Environment metadata
     */
    public function generate_environment_metadata() {
        return array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'request_time' => $_SERVER['REQUEST_TIME'] ?? time(),
            'request_time_float' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'timezone' => date_default_timezone_get(),
            'locale' => get_locale(),
            'is_ssl' => is_ssl(),
            'is_admin' => is_admin(),
            'is_multisite' => is_multisite()
        );
    }

    /**
     * Generate request metadata
     *
     * @since 3.2.0
     * @return array Request metadata
     */
    public function generate_request_metadata() {
        $request_data = array(
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'http_host' => $_SERVER['HTTP_HOST'] ?? '',
            'http_referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remote_addr' => $this->get_client_ip_address(),
            'request_time' => $_SERVER['REQUEST_TIME'] ?? time(),
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0
        );

        // Remove potential sensitive query parameters
        if (!empty($request_data['query_string'])) {
            $request_data['query_string'] = sanitize_text_field($request_data['query_string']);
        }

        return $request_data;
    }

    /**
     * Helper method to get client IP address
     *
     * @since 3.2.0
     * @return string Client IP address
     */
    private function get_client_ip_address() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Placeholder methods for security analysis
     */
    private function detect_login_method() { return 'standard'; }
    private function analyze_session_security() { return 'standard'; }
    private function is_two_factor_enabled($user) { return false; }
    private function get_password_strength_level($user) { return 'medium'; }
    private function is_account_locked($user) { return false; }
    private function calculate_login_frequency($user) { return 'normal'; }
    private function detect_unusual_activity($user) { return false; }
    private function check_geolocation_consistency($user) { return true; }
    private function calculate_security_score($user, $context) { return 75; }
    private function identify_risk_factors($context) { return array(); }
    private function determine_trust_level($context) { return 'medium'; }
    private function are_secure_cookies_enabled() { return is_ssl(); }
    private function is_session_encryption_enabled() { return true; }
    private function get_concurrent_session_count($user) { return 1; }
    private function get_session_security_level() { return 'standard'; }
    private function is_remember_me_active($user) { return false; }
    private function get_geolocation_data() { return array('country' => 'Unknown', 'city' => 'Unknown'); }

    /**
     * Get module status
     *
     * @return array Module status information
     */
    public function get_status() {
        return array_merge($this->status, array(
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__
        ));
    }

    /**
     * Module health check
     *
     * @return array Health check results
     */
    public function health_check() {
        $health = array(
            'status' => 'healthy',
            'checks' => array(),
            'warnings' => array(),
            'errors' => array()
        );

        if (!$this->status['initialized']) {
            $health['errors'][] = 'Context Enhancer not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        return $health;
    }
}
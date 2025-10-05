<?php

namespace VD\LicenseManager\UserAnalytics;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License User Security Analyzer
 *
 * Phase 4.1.2 - Extracted from monolithic validator class to provide
 * comprehensive user security analysis, authentication validation, and security scoring.
 *
 * @package VD_License_Manager
 * @subpackage UserAnalytics
 * @since 4.1.2
 * @author VD Team
 */
class VD_License_User_Security_Analyzer {

    /**
     * Singleton instance
     *
     * @var VD_License_User_Security_Analyzer|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '4.1.2';

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'security_checks_performed' => 0,
        'two_factor_validations' => 0,
        'security_scores_calculated' => 0,
        'session_analyses_performed' => 0,
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
     * @return VD_License_User_Security_Analyzer
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
            error_log("VD User Security Analyzer: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    // ==========================================
    // TWO-FACTOR AUTHENTICATION METHODS
    // ==========================================

    /**
     * Check two factor authentication status
     *
     * Phase 4.1.2 - 2FA verification
     *
     * @since 4.1.2
     * @param int $user_id User ID
     * @return bool Two factor status
     */
    public function check_two_factor_status($user_id) {
        $this->status['two_factor_validations']++;

        // Check for common 2FA plugins
        if (class_exists('Two_Factor_Core')) {
            return !empty(\Two_Factor_Core::get_enabled_providers_for_user($user_id));
        }

        // Check for other 2FA plugins
        $two_factor_meta = get_user_meta($user_id, '_two_factor_enabled', true);
        return !empty($two_factor_meta);
    }

    /**
     * Check account lock status
     *
     * Phase 4.1.2 - Account security status
     *
     * @since 4.1.2
     * @param int $user_id User ID
     * @return bool Account lock status
     */
    public function check_account_lock_status($user_id) {
        $locked = get_user_meta($user_id, 'vd_account_locked', true);
        return !empty($locked);
    }

    // ==========================================
    // SECURITY SCORING METHODS
    // ==========================================

    /**
     * Calculate security score for user
     *
     * Phase 4.1.2 - Security risk scoring
     *
     * @since 4.1.2
     * @param \WP_User $user User object
     * @param array $risk_factors Risk factors array
     * @return int Security score (0-100)
     */
    public function calculate_security_score($user, $risk_factors) {
        $this->status['security_scores_calculated']++;

        $score = 100;

        // Deduct points for risk factors
        $score -= count($risk_factors) * 15;

        // Add points for security features
        if ($this->check_two_factor_status($user->ID)) $score += 20;
        if (get_user_meta($user->ID, 'use_ssl', true) === '1') $score += 10;
        if (!empty($user->user_email)) $score += 5;

        return max(0, min(100, $score));
    }

    // ==========================================
    // SESSION SECURITY ANALYSIS METHODS
    // ==========================================

    /**
     * Count long running sessions
     *
     * Phase 4.1.2 - Session security analysis
     *
     * @since 4.1.2
     * @param array $sessions Session data
     * @return int Number of long running sessions
     */
    public function count_long_running_sessions($sessions) {
        $this->status['session_analyses_performed']++;

        $long_running = 0;
        $long_session_threshold = 30 * 24 * 60 * 60; // 30 days

        foreach ($sessions as $session) {
            $session_age = time() - $session['login'];
            if ($session_age > $long_session_threshold) {
                $long_running++;
            }
        }

        return $long_running;
    }

    /**
     * Validate user security context
     *
     * Phase 4.1.2 - Security context validation
     *
     * @since 4.1.2
     * @param array $security_context Security context data
     * @return array Validation result
     */
    public function validate_user_security_context($security_context) {
        $this->status['security_checks_performed']++;

        $validation_errors = array();

        // Login method validation
        if (!empty($security_context['login_method'])) {
            $allowed_methods = array('wordpress_native', 'oauth', 'ldap', 'custom');
            if (!in_array($security_context['login_method'], $allowed_methods)) {
                $validation_errors[] = 'Invalid login method in security context';
            }
        }

        // Session validation
        if (!empty($security_context['session_info'])) {
            $session_info = $security_context['session_info'];

            // Check session age
            if (isset($session_info['session_age']) && $session_info['session_age'] > 86400) { // 24 hours
                $validation_errors[] = 'Session age exceeds security threshold';
            }

            // Check for suspicious activity
            if (!empty($session_info['suspicious_activity'])) {
                $validation_errors[] = 'Suspicious activity detected in session';
            }
        }

        // IP validation
        if (!empty($security_context['ip_address'])) {
            if (!filter_var($security_context['ip_address'], FILTER_VALIDATE_IP)) {
                $validation_errors[] = 'Invalid IP address in security context';
            }
        }

        // Device validation
        if (!empty($security_context['device_info'])) {
            $device_info = $security_context['device_info'];

            // Check for known device
            if (isset($device_info['is_known_device']) && !$device_info['is_known_device']) {
                $validation_errors[] = 'Unknown device detected';
            }
        }

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors,
            'warnings' => $this->generate_security_warnings($security_context)
        );
    }

    // ==========================================
    // COMPREHENSIVE SECURITY ANALYSIS METHODS
    // ==========================================

    /**
     * Generate comprehensive security analysis
     *
     * Phase 4.1.2 - Comprehensive security analysis
     *
     * @since 4.1.2
     * @param int $user_id User ID
     * @param array $options Analysis options
     * @return array Comprehensive security analysis
     */
    public function generate_security_analysis($user_id, $options = array()) {
        $analysis_start = microtime(true);

        $default_options = array(
            'include_two_factor_analysis' => true,
            'include_account_security' => true,
            'include_session_analysis' => true,
            'include_security_scoring' => true
        );
        $options = array_merge($default_options, $options);

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return array(
                'error' => 'User not found',
                'user_id' => $user_id
            );
        }

        $analysis = array(
            'user_id' => $user_id,
            'username' => $user->user_login,
            'two_factor_analysis' => array(),
            'account_security' => array(),
            'session_analysis' => array(),
            'security_scoring' => array(),
            'metadata' => array(
                'generated_at' => current_time('c'),
                'module_version' => self::VERSION,
                'analysis_method' => 'generate_security_analysis'
            )
        );

        // Two-factor analysis
        if ($options['include_two_factor_analysis']) {
            $analysis['two_factor_analysis'] = array(
                'two_factor_enabled' => $this->check_two_factor_status($user_id),
                'available_providers' => $this->get_available_2fa_providers(),
                'recommendation' => $this->check_two_factor_status($user_id) ? 'maintain' : 'enable_2fa'
            );
        }

        // Account security
        if ($options['include_account_security']) {
            $analysis['account_security'] = array(
                'account_locked' => $this->check_account_lock_status($user_id),
                'password_strength' => $this->estimate_password_strength($user_id),
                'last_password_change' => get_user_meta($user_id, '_password_changed', true),
                'failed_login_attempts' => get_user_meta($user_id, '_failed_login_attempts', true) ?: 0
            );
        }

        // Session analysis
        if ($options['include_session_analysis']) {
            $session_manager = \WP_Session_Tokens::get_instance($user_id);
            $sessions = $session_manager->get_all();

            $analysis['session_analysis'] = array(
                'total_sessions' => count($sessions),
                'long_running_sessions' => $this->count_long_running_sessions($sessions),
                'session_security_score' => $this->calculate_session_security_score($sessions)
            );
        }

        // Security scoring
        if ($options['include_security_scoring']) {
            $risk_factors = $this->identify_risk_factors($user_id, $analysis);
            $analysis['security_scoring'] = array(
                'overall_score' => $this->calculate_security_score($user, $risk_factors),
                'risk_factors' => $risk_factors,
                'security_level' => $this->determine_security_level($this->calculate_security_score($user, $risk_factors))
            );
        }

        $analysis_end = microtime(true);
        $analysis['metadata']['analysis_time_ms'] = round(($analysis_end - $analysis_start) * 1000, 2);

        return $analysis;
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Generate security warnings based on context
     *
     * @param array $security_context Security context data
     * @return array Security warnings
     */
    private function generate_security_warnings($security_context) {
        $warnings = array();

        // Check for weak authentication
        if (empty($security_context['two_factor_enabled'])) {
            $warnings[] = 'Two-factor authentication not enabled';
        }

        // Check for old sessions
        if (!empty($security_context['session_info']['session_age']) &&
            $security_context['session_info']['session_age'] > 604800) { // 7 days
            $warnings[] = 'Session is older than recommended threshold';
        }

        return $warnings;
    }

    /**
     * Get available 2FA providers
     *
     * @return array Available 2FA providers
     */
    private function get_available_2fa_providers() {
        $providers = array();

        if (class_exists('Two_Factor_Core')) {
            $providers = array_keys(\Two_Factor_Core::get_providers());
        } else {
            $providers = array('fallback' => 'Basic 2FA support available');
        }

        return $providers;
    }

    /**
     * Estimate password strength
     *
     * @param int $user_id User ID
     * @return string Password strength estimate
     */
    private function estimate_password_strength($user_id) {
        // This is a simplified estimation
        $password_score = get_user_meta($user_id, '_password_strength_score', true);

        if ($password_score >= 80) return 'strong';
        if ($password_score >= 60) return 'medium';
        if ($password_score >= 40) return 'weak';
        return 'very_weak';
    }

    /**
     * Calculate session security score
     *
     * @param array $sessions Session data
     * @return int Session security score
     */
    private function calculate_session_security_score($sessions) {
        if (empty($sessions)) return 100;

        $score = 100;
        $long_running = $this->count_long_running_sessions($sessions);

        // Deduct points for long running sessions
        $score -= $long_running * 20;

        // Deduct points for too many sessions
        if (count($sessions) > 3) {
            $score -= (count($sessions) - 3) * 10;
        }

        return max(0, $score);
    }

    /**
     * Identify risk factors for user
     *
     * @param int $user_id User ID
     * @param array $analysis Current analysis data
     * @return array Risk factors
     */
    private function identify_risk_factors($user_id, $analysis) {
        $risk_factors = array();

        // Check for account lock
        if ($this->check_account_lock_status($user_id)) {
            $risk_factors[] = 'account_locked';
        }

        // Check for lack of 2FA
        if (!$this->check_two_factor_status($user_id)) {
            $risk_factors[] = 'no_two_factor';
        }

        // Check for multiple failed logins
        $failed_attempts = get_user_meta($user_id, '_failed_login_attempts', true) ?: 0;
        if ($failed_attempts > 3) {
            $risk_factors[] = 'multiple_failed_logins';
        }

        return $risk_factors;
    }

    /**
     * Determine security level based on score
     *
     * @param int $score Security score
     * @return string Security level
     */
    private function determine_security_level($score) {
        if ($score >= 80) return 'high';
        if ($score >= 60) return 'medium';
        if ($score >= 40) return 'low';
        return 'critical';
    }

    // ==========================================
    // MODULE STATUS & HEALTH METHODS
    // ==========================================

    /**
     * Get module status
     *
     * @return array Module status information
     */
    public function get_status() {
        return array_merge($this->status, array(
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'two_factor_core_available' => class_exists('Two_Factor_Core')
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

        // Check if initialized
        if (!$this->status['initialized']) {
            $health['errors'][] = 'User Security Analyzer not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        // Check Two-Factor Core integration
        if (!class_exists('Two_Factor_Core')) {
            $health['warnings'][] = 'Two-Factor Core plugin not available - using fallback methods';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Two-Factor Core integration available';
        }

        // Check memory usage
        if ($this->status['memory_usage'] > 262144) { // 256KB
            $health['warnings'][] = 'High memory usage: ' . number_format($this->status['memory_usage'] / 1024, 2) . ' KB';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Memory usage within limits';
        }

        return $health;
    }

    /**
     * Get debug information
     *
     * @return array Debug information
     */
    public function get_debug_info() {
        return array(
            'module' => 'User Security Analyzer',
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'status' => $this->status,
            'security_checks_performed' => $this->status['security_checks_performed'],
            'two_factor_validations' => $this->status['two_factor_validations'],
            'security_scores_calculated' => $this->status['security_scores_calculated'],
            'session_analyses_performed' => $this->status['session_analyses_performed'],
            'memory_usage' => $this->status['memory_usage'],
            'two_factor_core_available' => class_exists('Two_Factor_Core'),
            'initialized_at' => current_time('Y-m-d H:i:s'),
            'file_path' => __FILE__
        );
    }
}
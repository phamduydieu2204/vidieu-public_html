<?php

namespace VD\LicenseManager\Security;

/**
 * VD License Security Validator
 *
 * Comprehensive security validation and threat detection for license management
 * Handles IP validation, fraud prevention, suspicious activity detection,
 * and security scoring for license validation processes.
 *
 * @package VD\LicenseManager\Security
 * @since 1.5.0
 * @author VD License Manager
 */
class VD_License_Security_Validator {

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '1.5.0';

    /**
     * Module name
     *
     * @var string
     */
    const MODULE_NAME = 'Security Validator';

    /**
     * Dependencies required by this module
     *
     * @var array
     */
    private $dependencies = array(); // Standalone module

    /**
     * Module statistics
     *
     * @var array
     */
    private $stats = array(
        'validations_performed' => 0,
        'threats_detected' => 0,
        'security_scores_calculated' => 0,
        'compliance_checks' => 0,
        'start_time' => 0,
        'memory_usage' => 0
    );

    /**
     * Security configuration
     *
     * @var array
     */
    private $config = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->stats['start_time'] = microtime(true);
        $this->stats['memory_usage'] = memory_get_usage();
        $this->init_security_configuration();
    }

    /**
     * Initialize security configuration
     *
     * @return void
     */
    private function init_security_configuration() {
        $this->config = array(
            'threat_detection' => array(
                'ip_blacklist_enabled' => true,
                'rate_limiting_enabled' => true,
                'suspicious_activity_threshold' => 5,
                'max_failed_attempts' => 3,
                'lockout_duration' => 3600 // 1 hour
            ),
            'security_scoring' => array(
                'base_score' => 100,
                'risk_factor_penalty' => 15,
                'two_factor_bonus' => 20,
                'ssl_bonus' => 10,
                'email_verified_bonus' => 5
            ),
            'compliance' => array(
                'strict_mode' => false,
                'audit_logging' => true,
                'required_security_level' => 'medium'
            ),
            'fraud_prevention' => array(
                'device_fingerprinting' => true,
                'geolocation_checking' => false,
                'behavior_analysis' => true
            )
        );
    }

    /**
     * Get module information
     *
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => self::MODULE_NAME,
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'dependencies' => $this->dependencies,
            'file' => __FILE__,
            'size' => strlen(file_get_contents(__FILE__)) . ' bytes',
            'methods' => get_class_methods($this),
            'stats' => $this->get_module_stats()
        );
    }

    /**
     * Get module statistics
     *
     * @return array Current module statistics
     */
    public function get_module_stats() {
        $current_memory = memory_get_usage();
        $this->stats['memory_usage'] = $current_memory - $this->stats['memory_usage'];
        $this->stats['execution_time'] = microtime(true) - $this->stats['start_time'];

        return $this->stats;
    }

    /**
     * Get user security context
     *
     * Comprehensive security analysis for a WordPress user including
     * account security status, access patterns, and risk assessment.
     *
     * @param \WP_User $user WordPress user object
     * @return array User security analysis
     */
    public function get_user_security_context($user) {
        $this->stats['validations_performed']++;

        $security_context = array(
            'account_security' => array(),
            'access_patterns' => array(),
            'risk_assessment' => array(),
            'security_features' => array()
        );

        // Account security status
        $security_context['account_security'] = array(
            'password_strength' => 'unknown', // Would need additional plugin integration
            'two_factor_enabled' => $this->check_two_factor_status($user->ID),
            'email_verified' => !empty($user->user_email),
            'account_locked' => $this->check_account_lock_status($user->ID),
            'suspicious_activity' => $this->detect_suspicious_activity($user->ID)
        );

        // Access patterns
        $security_context['access_patterns'] = array(
            'admin_access' => is_admin(),
            'failed_login_attempts' => get_user_meta($user->ID, 'vd_failed_logins', true) ?: 0,
            'login_ip_consistency' => $this->analyze_login_ip_patterns($user->ID),
            'unusual_activity_detected' => $this->detect_unusual_activity($user->ID)
        );

        // Risk assessment
        $risk_factors = $this->analyze_risk_factors($user, $security_context);
        $security_context['risk_assessment'] = array(
            'risk_level' => $this->calculate_risk_level($risk_factors),
            'risk_factors' => $risk_factors,
            'security_score' => $this->calculate_security_score($user, $risk_factors)
        );

        // Security features availability
        $security_context['security_features'] = array(
            'ssl_required' => get_user_meta($user->ID, 'use_ssl', true) === '1',
            'admin_bar_disabled' => get_user_meta($user->ID, 'show_admin_bar_front', true) === 'false',
            'password_reset_required' => get_user_meta($user->ID, 'vd_password_reset_required', true) === '1'
        );

        return $security_context;
    }

    /**
     * Calculate security score for user
     *
     * @param \WP_User $user User object
     * @param array $risk_factors Risk factors array
     * @return int Security score (0-100)
     */
    public function calculate_security_score($user, $risk_factors) {
        $this->stats['security_scores_calculated']++;

        $score = $this->config['security_scoring']['base_score'];

        // Deduct points for risk factors
        $score -= count($risk_factors) * $this->config['security_scoring']['risk_factor_penalty'];

        // Add points for security features
        if ($this->check_two_factor_status($user->ID)) {
            $score += $this->config['security_scoring']['two_factor_bonus'];
        }

        if (get_user_meta($user->ID, 'use_ssl', true) === '1') {
            $score += $this->config['security_scoring']['ssl_bonus'];
        }

        if (!empty($user->user_email)) {
            $score += $this->config['security_scoring']['email_verified_bonus'];
        }

        return max(0, min(100, $score));
    }

    /**
     * Check two factor authentication status
     *
     * @param int $user_id User ID
     * @return bool Two factor status
     */
    public function check_two_factor_status($user_id) {
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
     * @param int $user_id User ID
     * @return bool Account lock status
     */
    public function check_account_lock_status($user_id) {
        $locked = get_user_meta($user_id, 'vd_account_locked', true);
        $lock_time = get_user_meta($user_id, 'vd_account_lock_time', true);

        // Check if lock has expired
        if ($locked && $lock_time) {
            $lockout_duration = $this->config['threat_detection']['lockout_duration'];
            if (time() - $lock_time > $lockout_duration) {
                // Unlock account
                delete_user_meta($user_id, 'vd_account_locked');
                delete_user_meta($user_id, 'vd_account_lock_time');
                return false;
            }
        }

        return !empty($locked);
    }

    /**
     * Detect suspicious activity for user
     *
     * @param int $user_id User ID
     * @return array Suspicious activity analysis
     */
    public function detect_suspicious_activity($user_id) {
        $activity = array(
            'detected' => false,
            'patterns' => array(),
            'score' => 0
        );

        // Check for rapid login attempts
        $failed_attempts = get_user_meta($user_id, 'vd_failed_logins', true) ?: 0;
        if ($failed_attempts > $this->config['threat_detection']['max_failed_attempts']) {
            $activity['detected'] = true;
            $activity['patterns'][] = 'excessive_failed_logins';
            $activity['score'] += 30;
        }

        // Check for unusual login times
        $last_login_time = get_user_meta($user_id, 'vd_last_login_time', true);
        if ($last_login_time) {
            $hour = date('H');
            if ($hour < 6 || $hour > 22) { // Outside normal hours
                $activity['patterns'][] = 'unusual_login_time';
                $activity['score'] += 10;
            }
        }

        // Check for multiple concurrent sessions
        $sessions = \WP_Session_Tokens::get_instance($user_id)->get_all();
        if (count($sessions) > 3) {
            $activity['patterns'][] = 'multiple_sessions';
            $activity['score'] += 15;
        }

        if ($activity['score'] > $this->config['threat_detection']['suspicious_activity_threshold']) {
            $activity['detected'] = true;
            $this->stats['threats_detected']++;
        }

        return $activity;
    }

    /**
     * Analyze login IP patterns for consistency
     *
     * @param int $user_id User ID
     * @return array IP pattern analysis
     */
    public function analyze_login_ip_patterns($user_id) {
        $ip_history = get_user_meta($user_id, 'vd_login_ip_history', true) ?: array();

        $analysis = array(
            'inconsistent_patterns' => false,
            'unique_ips' => 0,
            'geographical_spread' => false,
            'risk_score' => 0
        );

        if (empty($ip_history)) {
            return $analysis;
        }

        $unique_ips = array_unique(array_column($ip_history, 'ip'));
        $analysis['unique_ips'] = count($unique_ips);

        // Flag if too many different IPs in short time
        if (count($unique_ips) > 5) {
            $analysis['inconsistent_patterns'] = true;
            $analysis['risk_score'] += 25;
        }

        // Basic geographical analysis (simplified)
        $countries = array();
        foreach ($ip_history as $entry) {
            if (isset($entry['country']) && !in_array($entry['country'], $countries)) {
                $countries[] = $entry['country'];
            }
        }

        if (count($countries) > 2) {
            $analysis['geographical_spread'] = true;
            $analysis['risk_score'] += 20;
        }

        return $analysis;
    }

    /**
     * Detect unusual activity patterns
     *
     * @param int $user_id User ID
     * @return bool Whether unusual activity is detected
     */
    public function detect_unusual_activity($user_id) {
        // Check for rapid successive actions
        $recent_actions = get_user_meta($user_id, 'vd_recent_actions', true) ?: array();

        if (count($recent_actions) > 10) {
            $time_window = 300; // 5 minutes
            $recent_count = 0;

            foreach ($recent_actions as $action) {
                if (time() - $action['timestamp'] < $time_window) {
                    $recent_count++;
                }
            }

            if ($recent_count > 20) { // More than 20 actions in 5 minutes
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze risk factors for user
     *
     * @param \WP_User $user User object
     * @param array $security_context Security context
     * @return array Risk factors
     */
    private function analyze_risk_factors($user, $security_context) {
        $risk_factors = array();

        if ($security_context['access_patterns']['failed_login_attempts'] > 3) {
            $risk_factors[] = 'multiple_failed_logins';
        }

        if (count($user->roles) > 2) {
            $risk_factors[] = 'multiple_roles';
        }

        if (user_can($user, 'manage_options') && !$security_context['account_security']['two_factor_enabled']) {
            $risk_factors[] = 'admin_without_2fa';
        }

        if ($security_context['account_security']['suspicious_activity']['detected']) {
            $risk_factors[] = 'suspicious_activity_detected';
        }

        if ($security_context['access_patterns']['login_ip_consistency']['inconsistent_patterns']) {
            $risk_factors[] = 'inconsistent_ip_patterns';
        }

        return $risk_factors;
    }

    /**
     * Calculate risk level based on factors
     *
     * @param array $risk_factors Risk factors
     * @return string Risk level (low, medium, high)
     */
    private function calculate_risk_level($risk_factors) {
        $count = count($risk_factors);

        if ($count > 2) {
            return 'high';
        } elseif ($count > 0) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Validate user security context
     *
     * @param array $security_context Security context data
     * @return array Validation result
     */
    public function validate_user_security_context($security_context) {
        $this->stats['validations_performed']++;
        $validation_errors = array();

        // Login method validation
        if (!empty($security_context['login_method'])) {
            $allowed_methods = array('wordpress_native', 'oauth', 'ldap', 'custom');
            if (!in_array($security_context['login_method'], $allowed_methods)) {
                $validation_errors[] = 'Invalid login method in security context';
            }
        }

        // Session security validation
        if (!empty($security_context['session_security'])) {
            $allowed_levels = array('low', 'medium', 'high');
            if (!in_array($security_context['session_security'], $allowed_levels)) {
                $validation_errors[] = 'Invalid session security level';
            }
        }

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors
        );
    }

    /**
     * Validate security compliance
     *
     * @param array $license License data
     * @param array $security_context Security context
     * @return array Validation result
     */
    public function validate_security_compliance($license, $security_context) {
        $this->stats['compliance_checks']++;

        $validation_errors = array();

        // Check minimum security requirements
        if ($this->config['compliance']['strict_mode']) {
            if (!empty($security_context['risk_assessment']['risk_level']) &&
                $security_context['risk_assessment']['risk_level'] === 'high') {
                $validation_errors[] = 'High risk user - security compliance violation';
            }

            if (!empty($security_context['account_security']['account_locked']) &&
                $security_context['account_security']['account_locked']) {
                $validation_errors[] = 'Account locked - security compliance violation';
            }
        }

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors
        );
    }

    /**
     * Validate IP address and perform security analysis
     *
     * @param string $ip_address IP address to validate
     * @return array IP validation and security analysis
     */
    public function validate_ip_address($ip_address) {
        $this->stats['validations_performed']++;

        $result = array(
            'valid' => false,
            'ip' => $ip_address,
            'type' => 'unknown',
            'security_analysis' => array(),
            'risk_level' => 'low',
            'blocked' => false
        );

        // Basic IP validation
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return $result;
        }

        $result['valid'] = true;

        // Determine IP type
        if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $result['type'] = 'ipv4';
        } elseif (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $result['type'] = 'ipv6';
        }

        // Security analysis
        $result['security_analysis'] = array(
            'is_private' => filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false,
            'is_reserved' => filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false,
            'blacklisted' => $this->is_ip_blacklisted($ip_address),
            'reputation_score' => $this->get_ip_reputation_score($ip_address)
        );

        // Risk assessment
        if ($result['security_analysis']['blacklisted']) {
            $result['risk_level'] = 'high';
            $result['blocked'] = true;
            $this->stats['threats_detected']++;
        } elseif ($result['security_analysis']['reputation_score'] < 50) {
            $result['risk_level'] = 'medium';
        }

        return $result;
    }

    /**
     * Check if IP is blacklisted
     *
     * @param string $ip_address IP address
     * @return bool Whether IP is blacklisted
     */
    private function is_ip_blacklisted($ip_address) {
        $blacklist = get_option('vd_ip_blacklist', array());
        return in_array($ip_address, $blacklist);
    }

    /**
     * Get IP reputation score
     *
     * @param string $ip_address IP address
     * @return int Reputation score (0-100)
     */
    private function get_ip_reputation_score($ip_address) {
        // Simplified reputation scoring
        // In production, this would integrate with threat intelligence services

        $reputation_cache = get_transient('vd_ip_reputation_' . md5($ip_address));
        if ($reputation_cache !== false) {
            return $reputation_cache;
        }

        $score = 100; // Default good reputation

        // Check against known bad IP patterns
        if ($this->is_ip_blacklisted($ip_address)) {
            $score = 0;
        }

        // Cache the result for 1 hour
        set_transient('vd_ip_reputation_' . md5($ip_address), $score, 3600);

        return $score;
    }

    /**
     * Perform fraud detection analysis
     *
     * @param array $context Validation context including user, IP, device info
     * @return array Fraud detection results
     */
    public function detect_fraud($context) {
        $this->stats['validations_performed']++;

        $fraud_analysis = array(
            'fraud_detected' => false,
            'confidence_score' => 0,
            'indicators' => array(),
            'recommended_action' => 'allow'
        );

        $indicators = array();

        // IP-based fraud detection
        if (!empty($context['ip_address'])) {
            $ip_analysis = $this->validate_ip_address($context['ip_address']);
            if ($ip_analysis['risk_level'] === 'high') {
                $indicators[] = 'high_risk_ip';
                $fraud_analysis['confidence_score'] += 40;
            }
        }

        // User behavior analysis
        if (!empty($context['user_id'])) {
            $suspicious_activity = $this->detect_suspicious_activity($context['user_id']);
            if ($suspicious_activity['detected']) {
                $indicators[] = 'suspicious_user_behavior';
                $fraud_analysis['confidence_score'] += 30;
            }
        }

        // Device fingerprinting
        if (!empty($context['user_agent']) && $this->config['fraud_prevention']['device_fingerprinting']) {
            $device_risk = $this->analyze_device_fingerprint($context['user_agent']);
            if ($device_risk > 70) {
                $indicators[] = 'suspicious_device';
                $fraud_analysis['confidence_score'] += 20;
            }
        }

        $fraud_analysis['indicators'] = $indicators;

        // Determine if fraud is detected
        if ($fraud_analysis['confidence_score'] > 60) {
            $fraud_analysis['fraud_detected'] = true;
            $fraud_analysis['recommended_action'] = 'block';
            $this->stats['threats_detected']++;
        } elseif ($fraud_analysis['confidence_score'] > 30) {
            $fraud_analysis['recommended_action'] = 'review';
        }

        return $fraud_analysis;
    }

    /**
     * Analyze device fingerprint for suspicious patterns
     *
     * @param string $user_agent User agent string
     * @return int Risk score (0-100)
     */
    private function analyze_device_fingerprint($user_agent) {
        $risk_score = 0;

        // Check for missing or suspicious user agent
        if (empty($user_agent) || strlen($user_agent) < 10) {
            $risk_score += 50;
        }

        // Check for automation patterns
        $automation_patterns = array('bot', 'crawler', 'spider', 'scraper', 'automated');
        foreach ($automation_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                $risk_score += 30;
                break;
            }
        }

        // Check for outdated/uncommon browsers
        if (stripos($user_agent, 'MSIE') !== false || stripos($user_agent, 'Trident') !== false) {
            $risk_score += 10; // Old IE versions
        }

        return min(100, $risk_score);
    }

    /**
     * Get security configuration
     *
     * @return array Current security configuration
     */
    public function get_security_configuration() {
        return $this->config;
    }

    /**
     * Update security configuration
     *
     * @param array $new_config New configuration settings
     * @return bool Success status
     */
    public function update_security_configuration($new_config) {
        $this->config = array_merge($this->config, $new_config);
        return true;
    }
}
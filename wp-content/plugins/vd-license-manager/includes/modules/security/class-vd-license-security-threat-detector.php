<?php

namespace VD\LicenseManager\Security\Detection;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Security Threat Detector
 *
 * Advanced threat detection and security analysis module for identifying
 * suspicious activities, IP-based threats, fraud patterns, and anomaly detection.
 * Provides comprehensive threat intelligence and risk assessment capabilities.
 *
 * @package VD\LicenseManager\Security\Detection
 * @since 1.5.0-rc.1
 * @author VD License Manager Team
 */
class VD_License_Security_Threat_Detector {

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '1.5.0-rc.1';

    /**
     * Module name
     *
     * @var string
     */
    const MODULE_NAME = 'Security Threat Detector';

    /**
     * Threat severity levels
     *
     * @var array
     */
    const THREAT_SEVERITY = array(
        'MINIMAL' => 1,
        'LOW' => 2,
        'MEDIUM' => 3,
        'HIGH' => 4,
        'CRITICAL' => 5,
        'EMERGENCY' => 6
    );

    /**
     * Detection categories
     *
     * @var array
     */
    const DETECTION_CATEGORIES = array(
        'ip_based_threats',
        'user_behavior_anomalies',
        'authentication_attacks',
        'device_fingerprinting',
        'geographical_anomalies',
        'rate_limiting_violations',
        'fraud_patterns',
        'malicious_patterns'
    );

    /**
     * Singleton instance
     *
     * @var VD_License_Security_Threat_Detector|null
     */
    private static $instance = null;

    /**
     * Module statistics
     *
     * @var array
     */
    private $stats = array(
        'detections_performed' => 0,
        'threats_detected' => 0,
        'false_positives' => 0,
        'ip_analyses' => 0,
        'fraud_checks' => 0,
        'behavioral_analyses' => 0,
        'start_time' => 0,
        'memory_usage' => 0
    );

    /**
     * Threat detection configuration
     *
     * @var array
     */
    private $config = array();

    /**
     * Security validator dependency
     *
     * @var object|null
     */
    private $security_validator = null;

    /**
     * Event logger dependency
     *
     * @var object|null
     */
    private $event_logger = null;

    /**
     * Constructor
     */
    private function __construct() {
        $this->stats['start_time'] = microtime(true);
        $this->stats['memory_usage'] = memory_get_usage();
        $this->init_configuration();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Security_Threat_Detector
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize threat detection configuration
     *
     * @return void
     */
    private function init_configuration() {
        $this->config = array(
            'threat_detection' => array(
                'ip_blacklist_enabled' => true,
                'rate_limiting_enabled' => true,
                'suspicious_activity_threshold' => 5,
                'max_failed_attempts' => 3,
                'lockout_duration' => 3600,
                'geographical_analysis' => false,
                'device_fingerprinting' => true,
                'behavior_analysis' => true
            ),
            'risk_scoring' => array(
                'ip_risk_weight' => 40,
                'behavior_risk_weight' => 30,
                'device_risk_weight' => 20,
                'time_pattern_weight' => 10
            ),
            'thresholds' => array(
                'fraud_detection_threshold' => 60,
                'suspicious_threshold' => 30,
                'ip_reputation_threshold' => 50,
                'behavior_anomaly_threshold' => 5
            ),
            'cache' => array(
                'ip_reputation_cache_time' => 3600,
                'threat_cache_time' => 1800,
                'analysis_cache_time' => 900
            )
        );
    }

    /**
     * Set security validator dependency
     *
     * @param object $security_validator Security validator instance
     * @return void
     */
    public function set_security_validator($security_validator) {
        $this->security_validator = $security_validator;
    }

    /**
     * Set event logger dependency
     *
     * @param object $event_logger Event logger instance
     * @return void
     */
    public function set_event_logger($event_logger) {
        $this->event_logger = $event_logger;
    }

    /**
     * Detect suspicious activity for user
     *
     * @param int $user_id User ID
     * @return array Suspicious activity analysis
     */
    public function detect_suspicious_activity($user_id) {
        $this->stats['detections_performed']++;
        $this->stats['behavioral_analyses']++;

        $activity = array(
            'detected' => false,
            'patterns' => array(),
            'score' => 0,
            'severity' => 'MINIMAL',
            'timestamp' => current_time('timestamp'),
            'details' => array()
        );

        // Check for rapid login attempts
        $failed_attempts = get_user_meta($user_id, 'vd_failed_logins', true) ?: 0;
        if ($failed_attempts > $this->config['threat_detection']['max_failed_attempts']) {
            $activity['patterns'][] = 'excessive_failed_logins';
            $activity['score'] += 30;
            $activity['details']['failed_attempts'] = $failed_attempts;
        }

        // Check for unusual login times
        $last_login_time = get_user_meta($user_id, 'vd_last_login_time', true);
        if ($last_login_time) {
            $hour = date('H');
            if ($hour < 6 || $hour > 22) {
                $activity['patterns'][] = 'unusual_login_time';
                $activity['score'] += 10;
                $activity['details']['login_hour'] = $hour;
            }
        }

        // Check for multiple concurrent sessions
        $sessions = \WP_Session_Tokens::get_instance($user_id)->get_all();
        if (count($sessions) > 3) {
            $activity['patterns'][] = 'multiple_sessions';
            $activity['score'] += 15;
            $activity['details']['session_count'] = count($sessions);
        }

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

            if ($recent_count > 20) {
                $activity['patterns'][] = 'rapid_actions';
                $activity['score'] += 25;
                $activity['details']['rapid_actions_count'] = $recent_count;
            }
        }

        // Check for privilege escalation attempts
        $user = get_user_by('ID', $user_id);
        if ($user && count($user->roles) > 2) {
            $activity['patterns'][] = 'multiple_roles';
            $activity['score'] += 20;
            $activity['details']['roles_count'] = count($user->roles);
        }

        // Determine threat level
        if ($activity['score'] > $this->config['thresholds']['suspicious_threshold']) {
            $activity['detected'] = true;
            $this->stats['threats_detected']++;

            if ($activity['score'] > 50) {
                $activity['severity'] = 'HIGH';
            } elseif ($activity['score'] > 30) {
                $activity['severity'] = 'MEDIUM';
            } else {
                $activity['severity'] = 'LOW';
            }

            // Log threat detection
            $this->log_threat_detection('user_behavior_anomaly', $activity, $user_id);
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
        $this->stats['ip_analyses']++;

        $ip_history = get_user_meta($user_id, 'vd_login_ip_history', true) ?: array();

        $analysis = array(
            'inconsistent_patterns' => false,
            'unique_ips' => 0,
            'geographical_spread' => false,
            'risk_score' => 0,
            'threat_indicators' => array(),
            'analysis_details' => array()
        );

        if (empty($ip_history)) {
            return $analysis;
        }

        $unique_ips = array_unique(array_column($ip_history, 'ip'));
        $analysis['unique_ips'] = count($unique_ips);
        $analysis['analysis_details']['total_logins'] = count($ip_history);

        // Flag if too many different IPs in short time
        if (count($unique_ips) > 5) {
            $analysis['inconsistent_patterns'] = true;
            $analysis['risk_score'] += 25;
            $analysis['threat_indicators'][] = 'ip_hopping';
        }

        // Check for rapid IP changes
        $recent_ips = array();
        $time_window = 3600; // 1 hour
        foreach ($ip_history as $entry) {
            if (time() - $entry['timestamp'] < $time_window) {
                $recent_ips[] = $entry['ip'];
            }
        }

        if (count(array_unique($recent_ips)) > 3) {
            $analysis['threat_indicators'][] = 'rapid_ip_changes';
            $analysis['risk_score'] += 30;
        }

        // Geographical analysis (simplified)
        if ($this->config['threat_detection']['geographical_analysis']) {
            $countries = array();
            foreach ($ip_history as $entry) {
                if (isset($entry['country']) && !in_array($entry['country'], $countries)) {
                    $countries[] = $entry['country'];
                }
            }

            if (count($countries) > 2) {
                $analysis['geographical_spread'] = true;
                $analysis['risk_score'] += 20;
                $analysis['threat_indicators'][] = 'multi_country_access';
                $analysis['analysis_details']['countries_count'] = count($countries);
            }
        }

        return $analysis;
    }

    /**
     * Validate IP address and perform security analysis
     *
     * @param string $ip_address IP address to validate
     * @return array IP validation and security analysis
     */
    public function validate_ip_address($ip_address) {
        $this->stats['ip_analyses']++;

        $result = array(
            'valid' => false,
            'ip' => $ip_address,
            'type' => 'unknown',
            'security_analysis' => array(),
            'risk_level' => 'low',
            'threat_score' => 0,
            'blocked' => false,
            'threat_indicators' => array()
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
            'reputation_score' => $this->get_ip_reputation_score($ip_address),
            'rate_limited' => $this->check_rate_limiting($ip_address)
        );

        // Calculate threat score
        if ($result['security_analysis']['blacklisted']) {
            $result['threat_score'] += 100;
            $result['threat_indicators'][] = 'blacklisted_ip';
        }

        if ($result['security_analysis']['reputation_score'] < $this->config['thresholds']['ip_reputation_threshold']) {
            $reputation_penalty = (100 - $result['security_analysis']['reputation_score']) / 2;
            $result['threat_score'] += $reputation_penalty;
            $result['threat_indicators'][] = 'low_reputation';
        }

        if ($result['security_analysis']['rate_limited']) {
            $result['threat_score'] += 30;
            $result['threat_indicators'][] = 'rate_limited';
        }

        // Risk assessment
        if ($result['threat_score'] > 80) {
            $result['risk_level'] = 'critical';
            $result['blocked'] = true;
            $this->stats['threats_detected']++;
        } elseif ($result['threat_score'] > 60) {
            $result['risk_level'] = 'high';
        } elseif ($result['threat_score'] > 30) {
            $result['risk_level'] = 'medium';
        }

        // Log IP threat if significant
        if ($result['threat_score'] > 30) {
            $this->log_threat_detection('ip_threat', $result, null, $ip_address);
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
        $cache_key = 'vd_ip_reputation_' . md5($ip_address);
        $reputation_cache = get_transient($cache_key);

        if ($reputation_cache !== false) {
            return $reputation_cache;
        }

        $score = 100; // Default good reputation

        // Check against known bad IP patterns
        if ($this->is_ip_blacklisted($ip_address)) {
            $score = 0;
        } else {
            // Additional reputation checks could be added here
            // (external threat intelligence APIs, etc.)

            // Check if IP has been flagged before
            $previous_flags = get_option('vd_ip_flags_' . md5($ip_address), 0);
            if ($previous_flags > 0) {
                $score -= ($previous_flags * 10);
                $score = max(0, $score);
            }
        }

        // Cache the result
        set_transient($cache_key, $score, $this->config['cache']['ip_reputation_cache_time']);

        return $score;
    }

    /**
     * Check rate limiting for IP
     *
     * @param string $ip_address IP address
     * @return bool Whether IP is rate limited
     */
    private function check_rate_limiting($ip_address) {
        if (!$this->config['threat_detection']['rate_limiting_enabled']) {
            return false;
        }

        $cache_key = 'vd_rate_limit_' . md5($ip_address);
        $requests = get_transient($cache_key) ?: 0;

        // Rate limit: 100 requests per hour
        return $requests > 100;
    }

    /**
     * Perform fraud detection analysis
     *
     * @param array $context Validation context including user, IP, device info
     * @return array Fraud detection results
     */
    public function detect_fraud($context) {
        $this->stats['fraud_checks']++;

        $fraud_analysis = array(
            'fraud_detected' => false,
            'confidence_score' => 0,
            'indicators' => array(),
            'recommended_action' => 'allow',
            'risk_factors' => array(),
            'analysis_timestamp' => current_time('timestamp')
        );

        $indicators = array();

        // IP-based fraud detection
        if (!empty($context['ip_address'])) {
            $ip_analysis = $this->validate_ip_address($context['ip_address']);
            if ($ip_analysis['risk_level'] === 'high' || $ip_analysis['risk_level'] === 'critical') {
                $indicators[] = 'high_risk_ip';
                $fraud_analysis['confidence_score'] += $this->config['risk_scoring']['ip_risk_weight'];
                $fraud_analysis['risk_factors']['ip_risk'] = $ip_analysis;
            }
        }

        // User behavior analysis
        if (!empty($context['user_id'])) {
            $suspicious_activity = $this->detect_suspicious_activity($context['user_id']);
            if ($suspicious_activity['detected']) {
                $indicators[] = 'suspicious_user_behavior';
                $fraud_analysis['confidence_score'] += $this->config['risk_scoring']['behavior_risk_weight'];
                $fraud_analysis['risk_factors']['behavior_risk'] = $suspicious_activity;
            }

            // IP pattern analysis
            $ip_patterns = $this->analyze_login_ip_patterns($context['user_id']);
            if ($ip_patterns['risk_score'] > 30) {
                $indicators[] = 'suspicious_ip_patterns';
                $fraud_analysis['confidence_score'] += 15;
                $fraud_analysis['risk_factors']['ip_pattern_risk'] = $ip_patterns;
            }
        }

        // Device fingerprinting
        if (!empty($context['user_agent']) && $this->config['threat_detection']['device_fingerprinting']) {
            $device_risk = $this->analyze_device_fingerprint($context['user_agent']);
            if ($device_risk > 70) {
                $indicators[] = 'suspicious_device';
                $fraud_analysis['confidence_score'] += $this->config['risk_scoring']['device_risk_weight'];
                $fraud_analysis['risk_factors']['device_risk'] = $device_risk;
            }
        }

        $fraud_analysis['indicators'] = $indicators;

        // Determine if fraud is detected
        if ($fraud_analysis['confidence_score'] > $this->config['thresholds']['fraud_detection_threshold']) {
            $fraud_analysis['fraud_detected'] = true;
            $fraud_analysis['recommended_action'] = 'block';
            $this->stats['threats_detected']++;

            // Log fraud detection
            $this->log_threat_detection('fraud_detected', $fraud_analysis, $context['user_id'] ?? null);

        } elseif ($fraud_analysis['confidence_score'] > $this->config['thresholds']['suspicious_threshold']) {
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
    public function analyze_device_fingerprint($user_agent) {
        $risk_score = 0;

        // Check for missing or suspicious user agent
        if (empty($user_agent) || strlen($user_agent) < 10) {
            $risk_score += 50;
        }

        // Check for automation patterns
        $automation_patterns = array('bot', 'crawler', 'spider', 'scraper', 'automated', 'curl', 'wget');
        foreach ($automation_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                $risk_score += 40;
                break;
            }
        }

        // Check for outdated/uncommon browsers
        if (stripos($user_agent, 'MSIE') !== false || stripos($user_agent, 'Trident') !== false) {
            $risk_score += 15;
        }

        // Check for suspicious patterns
        $suspicious_patterns = array('python', 'java', 'perl', 'ruby', 'php');
        foreach ($suspicious_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                $risk_score += 30;
                break;
            }
        }

        return min(100, $risk_score);
    }

    /**
     * Log threat detection event
     *
     * @param string $threat_type Type of threat detected
     * @param array $threat_data Threat analysis data
     * @param int|null $user_id User ID if applicable
     * @param string|null $ip_address IP address if applicable
     * @return void
     */
    private function log_threat_detection($threat_type, $threat_data, $user_id = null, $ip_address = null) {
        if (!$this->event_logger) {
            return;
        }

        $severity = 'WARNING';
        if (isset($threat_data['severity'])) {
            $severity = $threat_data['severity'];
        } elseif (isset($threat_data['risk_level'])) {
            $severity = strtoupper($threat_data['risk_level']);
        }

        $metadata = array(
            'threat_type' => $threat_type,
            'threat_data' => $threat_data,
            'user_id' => $user_id,
            'ip_address' => $ip_address
        );

        $this->event_logger->log_event(
            $threat_type,
            $severity,
            'Security threat detected: ' . $threat_type,
            $metadata,
            'security_threat'
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
            'file' => __FILE__,
            'size' => strlen(file_get_contents(__FILE__)) . ' bytes',
            'methods' => get_class_methods($this),
            'stats' => $this->get_module_stats(),
            'dependencies' => array('security.validator', 'security.event_logger'),
            'detection_categories' => self::DETECTION_CATEGORIES,
            'threat_severities' => self::THREAT_SEVERITY
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
     * Get configuration
     *
     * @return array Current configuration
     */
    public function get_configuration() {
        return $this->config;
    }

    /**
     * Update configuration
     *
     * @param array $new_config Configuration updates
     * @return bool Success status
     */
    public function update_configuration($new_config) {
        $this->config = array_merge($this->config, $new_config);
        return true;
    }

    /**
     * Get threat detection summary
     *
     * @return array Threat detection summary
     */
    public function get_threat_summary() {
        return array(
            'total_detections' => $this->stats['detections_performed'],
            'threats_found' => $this->stats['threats_detected'],
            'detection_rate' => $this->stats['detections_performed'] > 0 ?
                round(($this->stats['threats_detected'] / $this->stats['detections_performed']) * 100, 2) : 0,
            'analysis_breakdown' => array(
                'ip_analyses' => $this->stats['ip_analyses'],
                'behavioral_analyses' => $this->stats['behavioral_analyses'],
                'fraud_checks' => $this->stats['fraud_checks']
            )
        );
    }
}
<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Activation Rules
 *
 * Handles comprehensive activation rules enforcement for license management
 * Extracted from monolithic validator in Step 2.1 (Phase 2) of refactor
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 * @namespace VD\LicenseManager\Rules
 */
class VD_License_Rule_Activation {

    /**
     * Singleton instance
     *
     * @var VD_License_Rule_Activation|null
     */
    private static $instance = null;

    /**
     * Status business logic module instance
     *
     * @since 1.5.0-rc.2
     * @var VD_License_Status_Business|null
     */
    private $status_business = null;

    /**
     * Activation rule enforcement statistics
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $stats = array(
        'activation_attempts' => 0,
        'activations_blocked' => 0,
        'device_limits_exceeded' => 0,
        'suspicious_activities_detected' => 0,
        'fingerprints_generated' => 0,
        'cross_device_violations' => 0
    );

    /**
     * Device categories cache
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $device_categories_cache = array();

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize module
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Rule_Activation
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set status business dependency
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Status_Business $status_business Status business instance
     * @return void
     */
    public function set_status_business($status_business) {
        $this->status_business = $status_business;
    }

    /**
     * Get status business dependency
     *
     * @since 1.5.0-rc.2
     * @return VD_License_Status_Business|null
     */
    public function get_status_business() {
        return $this->status_business;
    }

    /**
     * Validate product-level activation constraints
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $product_settings Product configuration
     * @return array Validation result
     */
    public function validate_product_level_constraints($license, $product_settings = array()) {
        $constraints_start = microtime(true);
        $debug_info = array(
            'license_id' => $license['id'] ?? null,
            'product_id' => $license['product_id'] ?? null
        );

        try {
            $this->stats['activation_attempts']++;

            // 1. Activation limits validation
            $activation_limit_result = $this->validate_activation_limits($license, $product_settings);
            if (!$activation_limit_result['valid']) {
                $this->stats['activations_blocked']++;
                return $this->create_activation_error(
                    'activation_limit_exceeded',
                    $activation_limit_result['error'],
                    $license,
                    array_merge($debug_info, $activation_limit_result['debug_info'] ?? array())
                );
            }

            // 2. Device limits validation
            $device_limit_result = $this->validate_device_limits($license, $product_settings);
            if (!$device_limit_result['valid']) {
                $this->stats['device_limits_exceeded']++;
                return $this->create_activation_error(
                    'device_limit_exceeded',
                    $device_limit_result['error'],
                    $license,
                    array_merge($debug_info, $device_limit_result['debug_info'] ?? array())
                );
            }

            // 3. Cross-device pattern validation
            $cross_device_result = $this->validate_cross_device_patterns($license);
            $debug_info['cross_device_analysis'] = $cross_device_result;

            if ($cross_device_result['violations_detected']) {
                $this->stats['cross_device_violations']++;
            }

            // 4. Suspicious activity check
            $security_result = $this->check_activation_security($license);
            $debug_info['security_analysis'] = $security_result;

            if ($security_result['suspicious_detected']) {
                $this->stats['suspicious_activities_detected']++;
            }

            $debug_info['validation_time_ms'] = round((microtime(true) - $constraints_start) * 1000, 2);

            return array(
                'valid' => true,
                'activation_allowed' => true,
                'debug_info' => $debug_info,
                'activation_limits' => $activation_limit_result,
                'device_limits' => $device_limit_result,
                'cross_device_analysis' => $cross_device_result,
                'security_analysis' => $security_result
            );

        } catch (Exception $e) {
            $debug_info['exception'] = $e->getMessage();

            return $this->create_activation_error(
                'activation_validation_exception',
                'Lỗi hệ thống khi validate activation constraints: ' . $e->getMessage(),
                $license,
                $debug_info
            );
        }
    }

    /**
     * Validate activation limits
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $product_settings Product configuration
     * @return array Activation limits validation result
     */
    public function validate_activation_limits($license, $product_settings) {
        $activation_count = intval($license['times_activated'] ?? 0);
        $activation_limit = intval($license['activations_limit'] ?? $product_settings['max_activations'] ?? 1);

        $debug_info = array(
            'activation_count' => $activation_count,
            'activation_limit' => $activation_limit,
            'remaining_activations' => max(0, $activation_limit - $activation_count)
        );

        // Check if activation limit exceeded
        if ($activation_count >= $activation_limit) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    'License đã đạt giới hạn activation (%d/%d). Không thể kích hoạt thêm.',
                    $activation_count,
                    $activation_limit
                ),
                'debug_info' => $debug_info
            );
        }

        return array(
            'valid' => true,
            'debug_info' => $debug_info,
            'activation_available' => true
        );
    }

    /**
     * Validate device limits
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $product_settings Product configuration
     * @return array Device limits validation result
     */
    public function validate_device_limits($license, $product_settings) {
        $license_settings = $this->get_license_activation_settings($license);

        $max_devices = intval($license_settings['max_devices'] ?? $product_settings['max_devices'] ?? 1);
        $current_devices = $this->get_current_device_count($license);

        $debug_info = array(
            'max_devices' => $max_devices,
            'current_devices' => $current_devices,
            'remaining_devices' => max(0, $max_devices - $current_devices),
            'license_settings' => $license_settings
        );

        // Check if device limit exceeded
        if ($current_devices >= $max_devices) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    'License đã đạt giới hạn thiết bị (%d/%d). Vui lòng deactivate thiết bị khác trước khi kích hoạt.',
                    $current_devices,
                    $max_devices
                ),
                'debug_info' => $debug_info
            );
        }

        return array(
            'valid' => true,
            'debug_info' => $debug_info,
            'device_activation_available' => true
        );
    }

    /**
     * Get license activation settings
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array License activation settings
     */
    public function get_license_activation_settings($license) {
        $license_id = $license['id'] ?? 0;
        $product_id = $license['product_id'] ?? 0;

        // Default activation settings
        $default_settings = array(
            'max_devices' => 1,
            'allow_device_switching' => true,
            'device_fingerprinting_enabled' => true,
            'cross_device_detection' => true,
            'security_validation_enabled' => true
        );

        // Get license-specific overrides
        $license_meta = get_post_meta($license_id, '_vd_activation_settings', true);
        if (!is_array($license_meta)) {
            $license_meta = array();
        }

        // Get product-level settings
        $product_meta = get_post_meta($product_id, '_vd_activation_settings', true);
        if (!is_array($product_meta)) {
            $product_meta = array();
        }

        // Merge with priority: license > product > default
        return array_replace_recursive($default_settings, $product_meta, $license_meta);
    }

    /**
     * Get current device count for license
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return int Current device count
     */
    public function get_current_device_count($license) {
        $license_key = $license['license_key'] ?? '';

        if (empty($license_key)) {
            return 0;
        }

        global $wpdb;

        // Count active devices from license activations
        $table_name = $wpdb->prefix . 'lmfwc_license_activations';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT label) FROM {$table_name}
             WHERE license_key = %s AND deactivated_at IS NULL",
            $license_key
        ));

        return intval($count);
    }

    /**
     * Analyze session devices from session data
     *
     * @since 1.5.0-rc.2
     * @param array $session_data Session information
     * @return array Device analysis result
     */
    public function analyze_session_devices($session_data) {
        $devices_start = microtime(true);

        $user_agents = $session_data['user_agents'] ?? array();
        $device_analysis = array(
            'total_devices' => 0,
            'device_types' => array(),
            'unique_fingerprints' => array(),
            'multi_device_detected' => false
        );

        if (empty($user_agents)) {
            return $device_analysis;
        }

        // Categorize each user agent
        foreach ($user_agents as $user_agent) {
            $device_type = $this->categorize_user_agent($user_agent);
            $fingerprint = $this->generate_device_fingerprint($user_agent);

            $device_analysis['device_types'][] = $device_type;
            $device_analysis['unique_fingerprints'][] = $fingerprint;
        }

        // Count unique devices
        $device_analysis['unique_fingerprints'] = array_unique($device_analysis['unique_fingerprints']);
        $device_analysis['total_devices'] = count($device_analysis['unique_fingerprints']);
        $device_analysis['device_types'] = array_count_values($device_analysis['device_types']);

        // Detect multi-device access
        $device_analysis['multi_device_detected'] = $device_analysis['total_devices'] > 1;

        $device_analysis['analysis_time_ms'] = round((microtime(true) - $devices_start) * 1000, 2);

        return $device_analysis;
    }

    /**
     * Categorize user agent into device type
     *
     * @since 1.5.0-rc.2
     * @param string $user_agent User agent string
     * @return string Device type (mobile, tablet, desktop, unknown)
     */
    public function categorize_user_agent($user_agent) {
        // Check cache first
        $cache_key = md5($user_agent);
        if (isset($this->device_categories_cache[$cache_key])) {
            return $this->device_categories_cache[$cache_key];
        }

        $device_type = 'unknown';
        $user_agent = strtolower($user_agent);

        // Mobile detection
        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $user_agent)) {
            $device_type = 'mobile';
        }
        // Tablet detection
        elseif (preg_match('/ipad|tablet|kindle|silk/i', $user_agent)) {
            $device_type = 'tablet';
        }
        // Desktop detection
        elseif (preg_match('/windows|macintosh|linux|chrome|firefox|safari|edge/i', $user_agent)) {
            $device_type = 'desktop';
        }

        // Cache result
        $this->device_categories_cache[$cache_key] = $device_type;

        return $device_type;
    }

    /**
     * Generate device fingerprint from user agent
     *
     * @since 1.5.0-rc.2
     * @param string $user_agent User agent string
     * @return string Device fingerprint
     */
    public function generate_device_fingerprint($user_agent) {
        $this->stats['fingerprints_generated']++;

        // Extract key components for fingerprinting
        $components = array(
            'user_agent' => $user_agent,
            'device_type' => $this->categorize_user_agent($user_agent),
            'ip_hash' => $this->get_client_ip_hash()
        );

        // Generate stable fingerprint
        return md5(wp_json_encode($components));
    }

    /**
     * Generate visitor fingerprint for anonymous users
     *
     * @since 1.5.0-rc.2
     * @param array $headers HTTP headers
     * @return string Visitor fingerprint
     */
    public function generate_visitor_fingerprint($headers = array()) {
        $this->stats['fingerprints_generated']++;

        if (empty($headers)) {
            $headers = getallheaders() ?: array();
        }

        $fingerprint_data = array(
            'user_agent' => $headers['User-Agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
            'accept_language' => $headers['Accept-Language'] ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'ip_hash' => $this->get_client_ip_hash()
        );

        return md5(wp_json_encode($fingerprint_data));
    }

    /**
     * Validate cross-device access patterns
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array Cross-device pattern analysis
     */
    public function validate_cross_device_patterns($license) {
        $pattern_start = microtime(true);

        $license_key = $license['license_key'] ?? '';
        $cross_device_analysis = array(
            'unique_devices_detected' => 0,
            'simultaneous_access_detected' => false,
            'cross_location_access' => false,
            'violations_detected' => false,
            'risk_level' => 'low'
        );

        if (empty($license_key)) {
            return $cross_device_analysis;
        }

        // Get recent activations for pattern analysis
        $recent_activations = $this->get_recent_activations($license_key);

        if (count($recent_activations) > 1) {
            $cross_device_analysis['unique_devices_detected'] = count($recent_activations);

            // Check for simultaneous access
            $cross_device_analysis['simultaneous_access_detected'] = $this->detect_simultaneous_access($recent_activations);

            // Check for cross-location access
            $cross_device_analysis['cross_location_access'] = $this->detect_cross_location_access($recent_activations);

            // Determine violations
            if ($cross_device_analysis['simultaneous_access_detected'] ||
                $cross_device_analysis['cross_location_access']) {
                $cross_device_analysis['violations_detected'] = true;
                $cross_device_analysis['risk_level'] = 'high';
            } elseif ($cross_device_analysis['unique_devices_detected'] > 2) {
                $cross_device_analysis['risk_level'] = 'medium';
            }
        }

        $cross_device_analysis['analysis_time_ms'] = round((microtime(true) - $pattern_start) * 1000, 2);

        return $cross_device_analysis;
    }

    /**
     * Check activation security
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array Security check result
     */
    public function check_activation_security($license) {
        $security_start = microtime(true);

        $security_result = array(
            'suspicious_detected' => false,
            'failed_attempts_count' => 0,
            'ip_reputation_score' => 100,
            'security_score' => 100,
            'risk_factors' => array()
        );

        // Check for suspicious activity
        $suspicious_activity = $this->check_suspicious_activity($license);
        if ($suspicious_activity['detected']) {
            $security_result['suspicious_detected'] = true;
            $security_result['failed_attempts_count'] = $suspicious_activity['failed_attempts'];
            $security_result['risk_factors'][] = 'excessive_failed_attempts';
            $security_result['security_score'] -= 30;
        }

        // Analyze IP patterns
        $ip_analysis = $this->analyze_login_ip_patterns($license);
        if ($ip_analysis['inconsistent_patterns']) {
            $security_result['risk_factors'][] = 'inconsistent_ip_patterns';
            $security_result['security_score'] -= 20;
        }

        $security_result['ip_reputation_score'] = $ip_analysis['reputation_score'];
        $security_result['security_score'] = max(0, min(100, $security_result['security_score']));

        $security_result['analysis_time_ms'] = round((microtime(true) - $security_start) * 1000, 2);

        return $security_result;
    }

    /**
     * Check for suspicious activity
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array Suspicious activity check result
     */
    public function check_suspicious_activity($license) {
        $license_key = $license['license_key'] ?? '';

        // Simple implementation - can be enhanced
        $failed_attempts = get_transient('vd_failed_activation_' . md5($license_key)) ?: 0;

        return array(
            'detected' => $failed_attempts > 5,
            'failed_attempts' => $failed_attempts,
            'threshold' => 5
        );
    }

    /**
     * Analyze login IP patterns
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array IP pattern analysis
     */
    public function analyze_login_ip_patterns($license) {
        $license_key = $license['license_key'] ?? '';

        // Simple implementation - can be enhanced with actual IP history
        $current_ip = $this->detect_client_ip();

        return array(
            'current_ip' => $current_ip,
            'inconsistent_patterns' => false,
            'reputation_score' => 100,
            'pattern_analysis' => 'stable'
        );
    }

    /**
     * Get recent activations for license
     *
     * @since 1.5.0-rc.2
     * @param string $license_key License key
     * @return array Recent activations
     */
    private function get_recent_activations($license_key) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'lmfwc_license_activations';
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE license_key = %s
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
             ORDER BY created_at DESC",
            $license_key
        ), ARRAY_A);

        return $results ?: array();
    }

    /**
     * Detect simultaneous access from activations
     *
     * @since 1.5.0-rc.2
     * @param array $activations Recent activations
     * @return bool True if simultaneous access detected
     */
    private function detect_simultaneous_access($activations) {
        // Simple implementation - check for overlapping time windows
        if (count($activations) < 2) {
            return false;
        }

        $time_window = 300; // 5 minutes

        for ($i = 0; $i < count($activations) - 1; $i++) {
            for ($j = $i + 1; $j < count($activations); $j++) {
                $time1 = strtotime($activations[$i]['created_at']);
                $time2 = strtotime($activations[$j]['created_at']);

                if (abs($time1 - $time2) < $time_window) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Detect cross-location access from activations
     *
     * @since 1.5.0-rc.2
     * @param array $activations Recent activations
     * @return bool True if cross-location access detected
     */
    private function detect_cross_location_access($activations) {
        // Simple implementation - would need actual IP geolocation
        $unique_sources = array();

        foreach ($activations as $activation) {
            $source = $activation['source'] ?? 'unknown';
            $unique_sources[] = $source;
        }

        return count(array_unique($unique_sources)) > 1;
    }

    /**
     * Detect client IP address
     *
     * @since 1.5.0-rc.2
     * @return string Client IP address
     */
    public function detect_client_ip() {
        $ip_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get client IP hash for fingerprinting
     *
     * @since 1.5.0-rc.2
     * @return string Hashed IP
     */
    private function get_client_ip_hash() {
        return md5($this->detect_client_ip());
    }

    /**
     * Create activation error response
     *
     * @since 1.5.0-rc.2
     * @param string $code Error code
     * @param string $message Error message
     * @param array $license License data
     * @param array $debug_info Debug information
     * @return array Activation error response
     */
    public function create_activation_error($code, $message, $license, $debug_info) {
        return array(
            'valid' => false,
            'error' => $message,
            'code' => $code,
            'license_id' => $license['id'] ?? null,
            'license_key' => $license['license_key'] ?? null,
            'debug_info' => $debug_info,
            'activation_timestamp' => current_time('mysql')
        );
    }

    /**
     * Get module statistics
     *
     * @since 1.5.0-rc.2
     * @return array Module statistics
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Reset module statistics
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    public function reset_stats() {
        $this->stats = array(
            'activation_attempts' => 0,
            'activations_blocked' => 0,
            'device_limits_exceeded' => 0,
            'suspicious_activities_detected' => 0,
            'fingerprints_generated' => 0,
            'cross_device_violations' => 0
        );
    }

    /**
     * Get module information
     *
     * @since 1.5.0-rc.2
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => 'VD License Activation Rules',
            'version' => '1.5.0-rc.2',
            'namespace' => 'VD\\LicenseManager\\Rules',
            'description' => 'Comprehensive activation rules enforcement for license management',
            'dependencies' => array('status.business'),
            'supports' => array(
                'activation_limits_validation',
                'device_limits_enforcement',
                'cross_device_detection',
                'device_fingerprinting',
                'suspicious_activity_detection',
                'security_validation'
            ),
            'statistics' => $this->get_stats()
        );
    }
}
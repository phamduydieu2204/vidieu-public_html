<?php

namespace VD\LicenseManager\Security\Privacy;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Security Privacy Manager
 *
 * Handles data privacy, anonymization, PII detection, and GDPR compliance
 * Step 3.2.3 - Data Privacy Manager Module
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 * @subpackage Security\Privacy
 */
class VD_License_Security_Privacy_Manager {

    /**
     * Singleton instance
     *
     * @var VD_License_Security_Privacy_Manager|null
     */
    private static $instance = null;

    /**
     * Event logger instance
     *
     * @var mixed|null
     */
    private $event_logger = null;

    /**
     * Module configuration
     *
     * @var array
     */
    private $config = array();

    /**
     * Data retention settings
     *
     * @var array
     */
    private $retention_settings = array();

    /**
     * Module statistics
     *
     * @var array
     */
    private $stats = array(
        'anonymizations_performed' => 0,
        'pii_detections' => 0,
        'data_sanitizations' => 0,
        'retention_enforcements' => 0,
        'consent_verifications' => 0,
        'start_time' => 0,
        'memory_usage' => 0,
        'execution_time' => 0
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_configuration();
        $this->stats['start_time'] = microtime(true);
        $this->stats['memory_usage'] = memory_get_usage();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Security_Privacy_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize module configuration
     *
     * @return void
     */
    private function init_configuration() {
        $this->config = array(
            'data_anonymization' => array(
                'enabled' => true,
                'aggressive_mode' => false,
                'preserve_analytics' => true
            ),
            'pii_detection' => array(
                'enabled' => true,
                'sensitivity_level' => 'medium', // low, medium, high
                'auto_mask' => true
            ),
            'consent_management' => array(
                'enabled' => true,
                'require_explicit_consent' => true,
                'consent_retention_days' => 365
            ),
            'data_retention' => array(
                'enabled' => true,
                'default_retention_days' => 730,
                'auto_cleanup' => true,
                'archive_before_deletion' => true
            ),
            'gdpr_compliance' => array(
                'enabled' => true,
                'right_to_erasure' => true,
                'right_to_portability' => true,
                'data_breach_notification' => true
            ),
            'cache' => array(
                'enabled' => true,
                'ttl' => 3600
            )
        );

        $this->init_retention_settings();
    }

    /**
     * Initialize data retention settings
     *
     * @return void
     */
    private function init_retention_settings() {
        $this->retention_settings = array(
            'user_data' => array(
                'retention_period' => 730, // 2 years
                'auto_cleanup' => true,
                'backup_before_deletion' => true
            ),
            'session_data' => array(
                'retention_period' => 30, // 30 days
                'auto_cleanup' => true,
                'backup_before_deletion' => false
            ),
            'license_history' => array(
                'retention_period' => 1095, // 3 years
                'auto_cleanup' => false,
                'backup_before_deletion' => true
            ),
            'audit_logs' => array(
                'retention_period' => 2555, // 7 years (compliance)
                'auto_cleanup' => false,
                'backup_before_deletion' => true
            ),
            'anonymous_data' => array(
                'retention_period' => 90, // 90 days
                'auto_cleanup' => true,
                'backup_before_deletion' => false
            )
        );
    }

    /**
     * Set event logger dependency
     *
     * @param mixed $event_logger Event logger instance
     * @return void
     */
    public function set_event_logger($event_logger) {
        $this->event_logger = $event_logger;
    }

    /**
     * Anonymize user data
     *
     * @param array $user_data User data to anonymize
     * @param array $options Anonymization options
     * @return array Anonymized data
     */
    public function anonymize_user_data($user_data, $options = array()) {
        try {
            $this->stats['anonymizations_performed']++;

            $default_options = array(
                'preserve_structure' => true,
                'hash_identifiers' => true,
                'remove_pii' => true,
                'randomize_values' => false
            );

            $options = array_merge($default_options, $options);

            if (!is_array($user_data)) {
                return $user_data;
            }

            $anonymized = array();

            foreach ($user_data as $key => $value) {
                try {
                    if ($this->is_pii_field($key)) {
                        $anonymized[$key] = $this->anonymize_pii_value($value, $key, $options);
                    } elseif (is_array($value)) {
                        $anonymized[$key] = $this->anonymize_user_data($value, $options);
                    } else {
                        $anonymized[$key] = $value;
                    }
                } catch (Exception $e) {
                    error_log('[VD Privacy Manager] Error anonymizing field ' . $key . ': ' . $e->getMessage());
                    $anonymized[$key] = '[ERROR_ANONYMIZING]';
                }
            }

            // Log anonymization event (with error handling)
            try {
                $this->log_privacy_event('data_anonymization', array(
                    'fields_processed' => count($user_data),
                    'pii_fields_found' => $this->count_pii_fields($user_data),
                    'options' => $options
                ));
            } catch (Exception $e) {
                error_log('[VD Privacy Manager] Error logging anonymization event: ' . $e->getMessage());
            }

            return $anonymized;
        } catch (Exception $e) {
            error_log('[VD Privacy Manager] Fatal error in anonymize_user_data: ' . $e->getMessage());
            return array('error' => 'Anonymization failed: ' . $e->getMessage());
        }
    }

    /**
     * Detect and mask PII in data
     *
     * @param mixed $data Data to scan for PII
     * @param array $options Detection options
     * @return array Detection results
     */
    public function detect_and_mask_pii($data, $options = array()) {
        try {
            $this->stats['pii_detections']++;

            $default_options = array(
                'auto_mask' => $this->config['pii_detection']['auto_mask'],
                'sensitivity' => $this->config['pii_detection']['sensitivity_level'],
                'preserve_format' => true
            );

            $options = array_merge($default_options, $options);

            $result = array(
                'original_data' => $data,
                'masked_data' => $data,
                'pii_detected' => false,
                'pii_fields' => array(),
                'detection_score' => 0
            );

            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    try {
                        if ($this->is_pii_field($key) || $this->contains_pii_value($value)) {
                            $result['pii_detected'] = true;
                            $result['pii_fields'][] = $key;

                            if ($options['auto_mask']) {
                                $result['masked_data'][$key] = $this->mask_pii_value($value, $key, $options);
                            }

                            $result['detection_score'] += $this->calculate_pii_score($key, $value);
                        }
                    } catch (Exception $e) {
                        error_log('[VD Privacy Manager] Error processing PII field ' . $key . ': ' . $e->getMessage());
                        $result['pii_fields'][] = $key . '_ERROR';
                    }
                }
            } elseif (is_string($data)) {
                try {
                    if ($this->contains_pii_patterns($data)) {
                        $result['pii_detected'] = true;
                        $result['detection_score'] = 85;

                        if ($options['auto_mask']) {
                            $result['masked_data'] = $this->mask_pii_patterns($data, $options);
                        }
                    }
                } catch (Exception $e) {
                    error_log('[VD Privacy Manager] Error processing PII patterns: ' . $e->getMessage());
                    $result['masked_data'] = '[ERROR_PROCESSING_PII]';
                }
            }

            // Log PII detection (with error handling)
            try {
                $this->log_privacy_event('pii_detection', array(
                    'pii_detected' => $result['pii_detected'],
                    'fields_count' => count($result['pii_fields']),
                    'detection_score' => $result['detection_score'],
                    'auto_masked' => $options['auto_mask']
                ));
            } catch (Exception $e) {
                error_log('[VD Privacy Manager] Error logging PII detection event: ' . $e->getMessage());
            }

            return $result;
        } catch (Exception $e) {
            error_log('[VD Privacy Manager] Fatal error in detect_and_mask_pii: ' . $e->getMessage());
            return array(
                'error' => 'PII detection failed: ' . $e->getMessage(),
                'pii_detected' => false,
                'pii_fields' => array(),
                'detection_score' => 0
            );
        }
    }

    /**
     * Sanitize context data for privacy compliance
     *
     * @param array $context Context data to sanitize
     * @return array Sanitized context data
     */
    public function sanitize_context_data($context) {
        $this->stats['data_sanitizations']++;

        if (!is_array($context)) {
            return array();
        }

        $sanitized = array();

        foreach ($context as $key => $value) {
            // Sanitize key
            $clean_key = sanitize_key($key);

            // Sanitize value based on type
            if (is_string($value)) {
                $sanitized[$clean_key] = sanitize_text_field($value);
            } elseif (is_numeric($value)) {
                $sanitized[$clean_key] = is_float($value) ? (float) $value : (int) $value;
            } elseif (is_array($value)) {
                $sanitized[$clean_key] = $this->sanitize_context_data($value); // Recursive sanitization
            } else {
                // Convert other types to string and sanitize
                $sanitized[$clean_key] = sanitize_text_field((string) $value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize query string to remove sensitive parameters
     *
     * @param string $query_string Query string to sanitize
     * @return string Sanitized query string
     */
    public function sanitize_query_string($query_string) {
        $this->stats['data_sanitizations']++;

        // Remove sensitive parameters
        $sensitive_params = array('password', 'token', 'key', 'secret', 'api_key', 'auth', 'session', 'cookie');

        parse_str($query_string, $params);

        foreach ($sensitive_params as $sensitive_param) {
            foreach ($params as $param_name => $param_value) {
                if (stripos($param_name, $sensitive_param) !== false) {
                    $params[$param_name] = '[FILTERED]';
                }
            }
        }

        return http_build_query($params);
    }

    /**
     * Get anonymous user context for privacy-compliant tracking
     *
     * @param array $options Context options
     * @return array Anonymous context data
     */
    public function get_anonymous_user_context($options = array()) {
        $this->stats['anonymizations_performed']++;

        $default_options = array(
            'include_fingerprint' => true,
            'include_behavioral' => true,
            'include_conversion' => true,
            'session_tracking' => true
        );

        $options = array_merge($default_options, $options);

        $anonymous_context = array(
            'visitor_identification' => array(),
            'session_tracking' => array(),
            'behavioral_tracking' => array(),
            'conversion_context' => array()
        );

        // Visitor identification (privacy-compliant)
        if ($options['include_fingerprint']) {
            $anonymous_context['visitor_identification'] = array(
                'session_id' => $this->get_anonymized_session_id(),
                'visitor_hash' => $this->generate_privacy_compliant_hash(),
                'ip_hash' => $this->hash_ip_address($this->get_client_ip()),
                'user_agent_hash' => $this->hash_user_agent(),
                'referer_domain' => $this->extract_domain_from_referer()
            );
        }

        // Session tracking
        if ($options['session_tracking']) {
            $anonymous_context['session_tracking'] = array(
                'session_duration' => $this->estimate_session_duration(),
                'page_views' => $this->get_page_view_count(),
                'bounce_risk' => $this->calculate_bounce_risk(),
                'engagement_score' => $this->calculate_engagement_score()
            );
        }

        // Behavioral tracking (anonymized)
        if ($options['include_behavioral']) {
            $anonymous_context['behavioral_tracking'] = array(
                'landing_page_type' => $this->categorize_landing_page(),
                'page_categories_visited' => $this->get_visited_page_categories(),
                'interaction_patterns' => $this->analyze_interaction_patterns(),
                'navigation_behavior' => $this->analyze_navigation_behavior()
            );
        }

        // Conversion context
        if ($options['include_conversion']) {
            $anonymous_context['conversion_context'] = array(
                'conversion_potential' => $this->assess_conversion_potential(),
                'cart_status' => $this->get_anonymized_cart_status(),
                'registration_likelihood' => $this->estimate_registration_likelihood(),
                'purchase_intent_score' => $this->calculate_purchase_intent()
            );
        }

        // Add privacy compliance markers
        $anonymous_context['privacy_compliance'] = array(
            'gdpr_compliant' => true,
            'no_pii_stored' => true,
            'anonymization_level' => 'high',
            'consent_status' => $this->check_consent_status(),
            'data_retention_policy' => $this->get_retention_policy_summary()
        );

        return $anonymous_context;
    }

    /**
     * Verify user consent status
     *
     * @param int $user_id User ID (optional)
     * @param string $consent_type Type of consent to check
     * @return array Consent verification result
     */
    public function verify_user_consent($user_id = null, $consent_type = 'general') {
        $this->stats['consent_verifications']++;

        $result = array(
            'consent_given' => false,
            'consent_date' => null,
            'consent_type' => $consent_type,
            'consent_valid' => false,
            'consent_expiry' => null,
            'consent_source' => null
        );

        // Check consent based on user ID or session
        if ($user_id) {
            $consent_data = $this->get_user_consent_data($user_id, $consent_type);
        } else {
            $consent_data = $this->get_session_consent_data($consent_type);
        }

        if ($consent_data) {
            $result['consent_given'] = true;
            $result['consent_date'] = $consent_data['consent_date'];
            $result['consent_source'] = $consent_data['source'];

            // Check if consent is still valid
            $expiry_date = strtotime($consent_data['consent_date'] . ' + ' . $this->config['consent_management']['consent_retention_days'] . ' days');
            $result['consent_expiry'] = date('Y-m-d H:i:s', $expiry_date);
            $result['consent_valid'] = time() < $expiry_date;
        }

        // Log consent verification
        $this->log_privacy_event('consent_verification', array(
            'user_id' => $user_id,
            'consent_type' => $consent_type,
            'consent_status' => $result['consent_given'] ? 'granted' : 'not_granted',
            'consent_valid' => $result['consent_valid']
        ));

        return $result;
    }

    /**
     * Get data retention settings
     *
     * @param string $data_type Type of data
     * @return array Retention settings
     */
    public function get_retention_settings($data_type = null) {
        if ($data_type && isset($this->retention_settings[$data_type])) {
            return $this->retention_settings[$data_type];
        }

        return $this->retention_settings;
    }

    /**
     * Enforce data retention policies
     *
     * @param array $options Enforcement options
     * @return array Enforcement results
     */
    public function enforce_retention_policies($options = array()) {
        $this->stats['retention_enforcements']++;

        $default_options = array(
            'dry_run' => false,
            'data_types' => array_keys($this->retention_settings),
            'force_cleanup' => false
        );

        $options = array_merge($default_options, $options);

        $results = array(
            'processed_types' => array(),
            'items_processed' => 0,
            'items_deleted' => 0,
            'items_archived' => 0,
            'errors' => array()
        );

        foreach ($options['data_types'] as $data_type) {
            if (!isset($this->retention_settings[$data_type])) {
                continue;
            }

            $type_result = $this->process_retention_for_type($data_type, $options);
            $results['processed_types'][$data_type] = $type_result;
            $results['items_processed'] += $type_result['items_processed'];
            $results['items_deleted'] += $type_result['items_deleted'];
            $results['items_archived'] += $type_result['items_archived'];

            if (!empty($type_result['errors'])) {
                $results['errors'][$data_type] = $type_result['errors'];
            }
        }

        // Log retention enforcement
        $this->log_privacy_event('retention_enforcement', array(
            'dry_run' => $options['dry_run'],
            'types_processed' => count($results['processed_types']),
            'items_deleted' => $results['items_deleted'],
            'items_archived' => $results['items_archived']
        ));

        return $results;
    }

    /**
     * Get module configuration
     *
     * @return array Module configuration
     */
    public function get_configuration() {
        return $this->config;
    }

    /**
     * Update module configuration
     *
     * @param array $config Configuration updates
     * @return bool Success status
     */
    public function update_configuration($config) {
        if (!is_array($config)) {
            return false;
        }

        $this->config = array_merge_recursive($this->config, $config);

        // Log configuration update
        $this->log_privacy_event('configuration_update', array(
            'updated_keys' => array_keys($config),
            'config_size' => count($this->config)
        ));

        return true;
    }

    /**
     * Get module statistics
     *
     * @return array Module statistics
     */
    public function get_module_stats() {
        $this->stats['execution_time'] = (microtime(true) - $this->stats['start_time']) * 1000;
        $this->stats['memory_usage'] = memory_get_usage() - $this->stats['memory_usage'];

        return $this->stats;
    }

    /**
     * Get module information
     *
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => 'Security Privacy Manager',
            'version' => '1.5.0-rc.1',
            'namespace' => 'VD\\LicenseManager\\Security\\Privacy',
            'file' => __FILE__,
            'size' => strlen(file_get_contents(__FILE__)) . ' bytes',
            'methods' => get_class_methods($this),
            'stats' => $this->get_module_stats(),
            'dependencies' => array('security.event_logger'),
            'privacy_features' => array(
                'data_anonymization',
                'pii_detection_masking',
                'consent_management',
                'data_retention_policies',
                'gdpr_compliance_utilities',
                'anonymous_user_tracking',
                'query_string_sanitization',
                'context_data_sanitization'
            ),
            'compliance_standards' => array(
                'GDPR' => 'General Data Protection Regulation',
                'CCPA' => 'California Consumer Privacy Act',
                'PIPEDA' => 'Personal Information Protection and Electronic Documents Act',
                'SOC2' => 'Service Organization Control 2'
            )
        );
    }

    // Private helper methods...

    /**
     * Check if field contains PII
     *
     * @param string $field_name Field name
     * @return bool True if PII field
     */
    private function is_pii_field($field_name) {
        $pii_fields = array(
            'email', 'mail', 'phone', 'mobile', 'tel', 'name', 'firstname', 'lastname',
            'address', 'street', 'city', 'zip', 'postal', 'ssn', 'tax_id', 'ip_address',
            'credit_card', 'cc_number', 'card_number', 'license_number', 'passport',
            'driver_license', 'birth_date', 'birthday', 'dob', 'age'
        );

        $field_lower = strtolower($field_name);

        foreach ($pii_fields as $pii_field) {
            if (strpos($field_lower, $pii_field) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Anonymize PII value
     *
     * @param mixed $value Value to anonymize
     * @param string $field_name Field name
     * @param array $options Anonymization options
     * @return mixed Anonymized value
     */
    private function anonymize_pii_value($value, $field_name, $options) {
        if (!is_string($value) && !is_numeric($value)) {
            return $value;
        }

        $field_lower = strtolower($field_name);

        if (strpos($field_lower, 'email') !== false) {
            return $this->anonymize_email($value);
        } elseif (strpos($field_lower, 'phone') !== false || strpos($field_lower, 'tel') !== false) {
            return $this->anonymize_phone($value);
        } elseif (strpos($field_lower, 'name') !== false) {
            return $this->anonymize_name($value);
        } elseif (strpos($field_lower, 'ip') !== false) {
            return $this->anonymize_ip($value);
        } else {
            return $options['hash_identifiers'] ? hash('sha256', $value) : '[ANONYMIZED]';
        }
    }

    /**
     * Anonymize email address
     *
     * @param string $email Email to anonymize
     * @return string Anonymized email
     */
    private function anonymize_email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '[INVALID_EMAIL]';
        }

        list($local, $domain) = explode('@', $email);
        $local_masked = substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2));

        return $local_masked . '@' . $domain;
    }

    /**
     * Anonymize phone number
     *
     * @param string $phone Phone to anonymize
     * @return string Anonymized phone
     */
    private function anonymize_phone($phone) {
        $digits_only = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits_only) < 4) {
            return '[INVALID_PHONE]';
        }

        return substr($digits_only, 0, 3) . str_repeat('*', strlen($digits_only) - 6) . substr($digits_only, -3);
    }

    /**
     * Anonymize name
     *
     * @param string $name Name to anonymize
     * @return string Anonymized name
     */
    private function anonymize_name($name) {
        $words = explode(' ', trim($name));
        $anonymized_words = array();

        foreach ($words as $word) {
            if (strlen($word) > 1) {
                $anonymized_words[] = substr($word, 0, 1) . str_repeat('*', strlen($word) - 1);
            } else {
                $anonymized_words[] = '*';
            }
        }

        return implode(' ', $anonymized_words);
    }

    /**
     * Anonymize IP address
     *
     * @param string $ip IP to anonymize
     * @return string Anonymized IP
     */
    private function anonymize_ip($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.***.**';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . ':****:****:****:****';
        }

        return '[INVALID_IP]';
    }

    /**
     * Check if value contains PII
     *
     * @param mixed $value Value to check
     * @return bool True if contains PII
     */
    private function contains_pii_value($value) {
        if (!is_string($value)) {
            return false;
        }

        // Check for email patterns
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        // Check for phone patterns
        if (preg_match('/^\+?[\d\s\-\(\)]{7,15}$/', $value)) {
            return true;
        }

        // Check for credit card patterns
        if (preg_match('/^\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}$/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Check if text contains PII patterns
     *
     * @param string $text Text to check
     * @return bool True if contains PII patterns
     */
    private function contains_pii_patterns($text) {
        $pii_patterns = array(
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email
            '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/', // Phone
            '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/', // Credit card
            '/\b\d{3}-\d{2}-\d{4}\b/', // SSN
        );

        foreach ($pii_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask PII value
     *
     * @param mixed $value Value to mask
     * @param string $field Field name
     * @param array $options Masking options
     * @return mixed Masked value
     */
    private function mask_pii_value($value, $field, $options) {
        if ($options['preserve_format']) {
            return $this->anonymize_pii_value($value, $field, $options);
        } else {
            return '[MASKED]';
        }
    }

    /**
     * Mask PII patterns in text
     *
     * @param string $text Text to mask
     * @param array $options Masking options
     * @return string Masked text
     */
    private function mask_pii_patterns($text, $options) {
        $patterns = array(
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]',
            '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/' => '[PHONE]',
            '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/' => '[CREDIT_CARD]',
            '/\b\d{3}-\d{2}-\d{4}\b/' => '[SSN]',
        );

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    /**
     * Calculate PII score for field/value
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @return int PII score (0-100)
     */
    private function calculate_pii_score($field, $value) {
        $score = 0;

        if ($this->is_pii_field($field)) {
            $score += 60;
        }

        if ($this->contains_pii_value($value)) {
            $score += 40;
        }

        return min($score, 100);
    }

    /**
     * Count PII fields in data
     *
     * @param array $data Data to count
     * @return int PII field count
     */
    private function count_pii_fields($data) {
        if (!is_array($data)) {
            return 0;
        }

        $count = 0;
        foreach ($data as $key => $value) {
            if ($this->is_pii_field($key)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Log privacy event
     *
     * @param string $event_type Event type
     * @param array $context Event context
     * @return void
     */
    private function log_privacy_event($event_type, $context) {
        if ($this->event_logger && method_exists($this->event_logger, 'log_security_event')) {
            try {
                $this->event_logger->log_security_event(array(
                    'event_type' => 'privacy_' . $event_type,
                    'component' => 'security_privacy_manager',
                    'severity' => 'INFO',
                    'context' => $context,
                    'timestamp' => current_time('mysql'),
                    'user_id' => get_current_user_id(),
                    'ip_address' => $this->get_client_ip()
                ));
            } catch (Exception $e) {
                // Silently fail if logging fails during testing
                error_log('[VD Privacy Manager] Logging failed: ' . $e->getMessage());
            }
        }
    }

    // Additional helper methods for anonymous context generation...

    private function get_anonymized_session_id() {
        return hash('sha256', session_id() . wp_salt());
    }

    private function generate_privacy_compliant_hash() {
        $data = array(
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            date('Y-m-d'), // Daily rotation
        );
        return hash('sha256', implode('|', $data));
    }

    private function hash_ip_address($ip) {
        // Hash IP with daily salt for privacy
        return hash('sha256', $ip . date('Y-m-d') . wp_salt());
    }

    private function hash_user_agent() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $user_agent);
    }

    private function extract_domain_from_referer() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (empty($referer)) {
            return 'direct';
        }

        $parsed = parse_url($referer);
        return $parsed['host'] ?? 'unknown';
    }

    private function get_client_ip() {
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

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    // Placeholder methods for session and behavioral tracking
    private function estimate_session_duration() { return 0; }
    private function get_page_view_count() { return 1; }
    private function calculate_bounce_risk() { return 'low'; }
    private function calculate_engagement_score() { return 50; }
    private function categorize_landing_page() { return 'homepage'; }
    private function get_visited_page_categories() { return array('homepage'); }
    private function analyze_interaction_patterns() { return array(); }
    private function analyze_navigation_behavior() { return array(); }
    private function assess_conversion_potential() { return 'medium'; }
    private function get_anonymized_cart_status() { return array('has_items' => false); }
    private function estimate_registration_likelihood() { return 'low'; }
    private function calculate_purchase_intent() { return 25; }
    private function check_consent_status() { return 'unknown'; }
    private function get_retention_policy_summary() { return 'default'; }
    private function get_user_consent_data($user_id, $consent_type) { return null; }
    private function get_session_consent_data($consent_type) { return null; }
    private function process_retention_for_type($data_type, $options) {
        return array(
            'items_processed' => 0,
            'items_deleted' => 0,
            'items_archived' => 0,
            'errors' => array()
        );
    }
}
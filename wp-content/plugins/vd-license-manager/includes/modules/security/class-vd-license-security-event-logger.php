<?php

namespace VD\LicenseManager\Security\Event;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Security Event Core Logger
 *
 * Foundation module for security event logging with standardized event handling,
 * severity classification, and metadata management. Provides core infrastructure
 * for all security audit logging functionality.
 *
 * @package VD\LicenseManager\Security\Event
 * @since 1.5.0-rc.1
 * @author VD License Manager Team
 */
class VD_License_Security_Event_Logger {

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
    const MODULE_NAME = 'Security Event Core Logger';

    /**
     * Event severity levels
     *
     * @var array
     */
    const SEVERITY_LEVELS = array(
        'DEBUG' => 0,
        'INFO' => 1,
        'NOTICE' => 2,
        'WARNING' => 3,
        'ERROR' => 4,
        'CRITICAL' => 5,
        'ALERT' => 6,
        'EMERGENCY' => 7
    );

    /**
     * Event categories
     *
     * @var array
     */
    const EVENT_CATEGORIES = array(
        'authentication',
        'authorization',
        'license_validation',
        'security_threat',
        'system_access',
        'data_access',
        'configuration_change',
        'audit_trail'
    );

    /**
     * Singleton instance
     *
     * @var VD_License_Security_Event_Logger|null
     */
    private static $instance = null;

    /**
     * Event buffer for batch processing
     *
     * @var array
     */
    private $event_buffer = array();

    /**
     * Module statistics
     *
     * @var array
     */
    private $stats = array(
        'events_logged' => 0,
        'events_buffered' => 0,
        'batch_writes' => 0,
        'validation_errors' => 0,
        'start_time' => 0,
        'memory_usage' => 0
    );

    /**
     * Event logger configuration
     *
     * @var array
     */
    private $config = array();

    /**
     * Security validator instance
     *
     * @var object|null
     */
    private $security_validator = null;

    /**
     * Constructor
     */
    private function __construct() {
        $this->stats['start_time'] = microtime(true);
        $this->stats['memory_usage'] = memory_get_usage();
        $this->init_configuration();
        $this->register_shutdown_handler();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Security_Event_Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize configuration
     *
     * @return void
     */
    private function init_configuration() {
        $this->config = array(
            'buffer_size' => 50,
            'auto_flush' => true,
            'log_retention_days' => 90,
            'log_compression' => false,
            'event_validation' => true,
            'metadata_sanitization' => true,
            'timezone' => get_option('timezone_string', 'UTC'),
            'date_format' => 'Y-m-d H:i:s',
            'enabled_severities' => array('DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'),
            'enabled_categories' => self::EVENT_CATEGORIES
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
     * Register shutdown handler for buffer flush
     *
     * @return void
     */
    private function register_shutdown_handler() {
        register_shutdown_function(array($this, 'flush_event_buffer'));
    }

    /**
     * Log a security event
     *
     * @param string $event_type Event type identifier
     * @param string $severity Event severity level
     * @param string $message Human-readable event message
     * @param array $metadata Additional event metadata
     * @param string $category Event category
     * @return bool Success status
     */
    public function log_event($event_type, $severity, $message, $metadata = array(), $category = 'audit_trail') {
        if (!$this->is_severity_enabled($severity) || !$this->is_category_enabled($category)) {
            return false;
        }

        $event = $this->create_event_structure($event_type, $severity, $message, $metadata, $category);

        if (!$event) {
            $this->stats['validation_errors']++;
            return false;
        }

        return $this->add_event_to_buffer($event);
    }

    /**
     * Create standardized event structure
     *
     * @param string $event_type Event type
     * @param string $severity Severity level
     * @param string $message Event message
     * @param array $metadata Event metadata
     * @param string $category Event category
     * @return array|false Event structure or false on validation failure
     */
    private function create_event_structure($event_type, $severity, $message, $metadata, $category) {
        if ($this->config['event_validation'] && !$this->validate_event_input($event_type, $severity, $message, $category)) {
            return false;
        }

        $current_time = current_time('timestamp');
        $event_id = $this->generate_event_id();

        $event = array(
            'event_id' => $event_id,
            'event_type' => sanitize_text_field($event_type),
            'severity' => strtoupper($severity),
            'severity_level' => self::SEVERITY_LEVELS[strtoupper($severity)],
            'category' => sanitize_text_field($category),
            'message' => wp_kses_post($message),
            'timestamp' => $current_time,
            'formatted_time' => date($this->config['date_format'], $current_time),
            'user_context' => $this->get_user_context(),
            'request_context' => $this->get_request_context(),
            'system_context' => $this->get_system_context(),
            'metadata' => $this->sanitize_metadata($metadata),
            'hash' => '' // Will be calculated after event creation
        );

        $event['hash'] = $this->calculate_event_hash($event);

        return $event;
    }

    /**
     * Validate event input parameters
     *
     * @param string $event_type Event type
     * @param string $severity Severity level
     * @param string $message Event message
     * @param string $category Event category
     * @return bool Validation result
     */
    private function validate_event_input($event_type, $severity, $message, $category) {
        if (empty($event_type) || !is_string($event_type) || strlen($event_type) > 100) {
            return false;
        }

        if (!isset(self::SEVERITY_LEVELS[strtoupper($severity)])) {
            return false;
        }

        if (empty($message) || !is_string($message) || strlen($message) > 1000) {
            return false;
        }

        if (!in_array($category, self::EVENT_CATEGORIES)) {
            return false;
        }

        return true;
    }

    /**
     * Generate unique event ID
     *
     * @return string Event ID
     */
    private function generate_event_id() {
        return 'evt_' . uniqid() . '_' . wp_generate_password(8, false);
    }

    /**
     * Get user context information
     *
     * @return array User context
     */
    private function get_user_context() {
        $context = array(
            'user_id' => 0,
            'user_login' => 'anonymous',
            'user_email' => '',
            'user_roles' => array(),
            'user_capabilities' => array(),
            'is_admin' => false
        );

        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $context = array(
                'user_id' => $current_user->ID,
                'user_login' => $current_user->user_login,
                'user_email' => $current_user->user_email,
                'user_roles' => $current_user->roles,
                'user_capabilities' => array_keys($current_user->allcaps),
                'is_admin' => current_user_can('manage_options')
            );
        }

        return $context;
    }

    /**
     * Get request context information
     *
     * @return array Request context
     */
    private function get_request_context() {
        return array(
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'session_id' => session_id() ?: '',
            'is_ajax' => wp_doing_ajax(),
            'is_rest' => defined('REST_REQUEST') && REST_REQUEST,
            'is_admin' => is_admin()
        );
    }

    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
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
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get system context information
     *
     * @return array System context
     */
    private function get_system_context() {
        return array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => defined('VD_LM_VERSION') ? VD_LM_VERSION : '1.0.0',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $this->stats['start_time'],
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        );
    }

    /**
     * Sanitize metadata array
     *
     * @param array $metadata Raw metadata
     * @return array Sanitized metadata
     */
    private function sanitize_metadata($metadata) {
        if (!is_array($metadata) || !$this->config['metadata_sanitization']) {
            return $metadata;
        }

        $sanitized = array();
        foreach ($metadata as $key => $value) {
            $clean_key = sanitize_key($key);
            if (is_string($value)) {
                $sanitized[$clean_key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized[$clean_key] = $this->sanitize_metadata($value);
            } elseif (is_numeric($value) || is_bool($value)) {
                $sanitized[$clean_key] = $value;
            } else {
                $sanitized[$clean_key] = wp_json_encode($value);
            }
        }

        return $sanitized;
    }

    /**
     * Calculate event hash for integrity verification
     *
     * @param array $event Event data
     * @return string Event hash
     */
    private function calculate_event_hash($event) {
        $hash_data = $event;
        unset($hash_data['hash']); // Remove hash field from calculation
        return hash('sha256', wp_json_encode($hash_data, JSON_SORT_KEYS));
    }

    /**
     * Add event to buffer
     *
     * @param array $event Event structure
     * @return bool Success status
     */
    private function add_event_to_buffer($event) {
        $this->event_buffer[] = $event;
        $this->stats['events_buffered']++;

        if ($this->config['auto_flush'] && count($this->event_buffer) >= $this->config['buffer_size']) {
            return $this->flush_event_buffer();
        }

        return true;
    }

    /**
     * Flush event buffer to storage
     *
     * @return bool Success status
     */
    public function flush_event_buffer() {
        if (empty($this->event_buffer)) {
            return true;
        }

        $success = $this->write_events_to_storage($this->event_buffer);

        if ($success) {
            $this->stats['events_logged'] += count($this->event_buffer);
            $this->stats['batch_writes']++;
            $this->event_buffer = array();
        }

        return $success;
    }

    /**
     * Write events to storage (placeholder for actual storage implementation)
     *
     * @param array $events Array of events to store
     * @return bool Success status
     */
    private function write_events_to_storage($events) {
        // This is a placeholder - actual storage will be implemented in Step 3.2.4
        // For now, we'll use error_log for basic logging
        foreach ($events as $event) {
            $log_entry = sprintf(
                '[%s] %s: %s - %s (User: %s, IP: %s)',
                $event['formatted_time'],
                $event['severity'],
                $event['event_type'],
                $event['message'],
                $event['user_context']['user_login'],
                $event['request_context']['ip_address']
            );
            error_log('[VD Security Event] ' . $log_entry);
        }

        return true;
    }

    /**
     * Check if severity level is enabled
     *
     * @param string $severity Severity level
     * @return bool Whether severity is enabled
     */
    private function is_severity_enabled($severity) {
        return in_array(strtoupper($severity), $this->config['enabled_severities']);
    }

    /**
     * Check if category is enabled
     *
     * @param string $category Event category
     * @return bool Whether category is enabled
     */
    private function is_category_enabled($category) {
        return in_array($category, $this->config['enabled_categories']);
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
            'dependencies' => array('security.validator'),
            'event_buffer_size' => count($this->event_buffer)
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
     * Get event buffer contents
     *
     * @return array Current event buffer
     */
    public function get_event_buffer() {
        return $this->event_buffer;
    }

    /**
     * Clear event buffer
     *
     * @return bool Success status
     */
    public function clear_event_buffer() {
        $this->event_buffer = array();
        $this->stats['events_buffered'] = 0;
        return true;
    }

    /**
     * Force flush and get buffered events
     *
     * @return array Events that were in buffer
     */
    public function force_flush_and_get_events() {
        $events = $this->event_buffer;
        $this->flush_event_buffer();
        return $events;
    }
}
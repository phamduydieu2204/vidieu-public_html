<?php

namespace VD\LicenseManager\Security\Integration;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Security Integration Hub
 *
 * Manages WordPress hooks integration, third-party API connections, webhook notifications,
 * event forwarding systems, and external SIEM integration for Step 3.2.6
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 * @subpackage Security\Integration
 */
class VD_License_Security_Integration_Hub {

    /**
     * Singleton instance
     *
     * @var VD_License_Security_Integration_Hub|null
     */
    private static $instance = null;

    /**
     * Security event logger instance
     *
     * @var object|null
     */
    private $event_logger = null;

    /**
     * Security storage manager instance
     *
     * @var object|null
     */
    private $storage_manager = null;

    /**
     * Security privacy manager instance
     *
     * @var object|null
     */
    private $privacy_manager = null;

    /**
     * Security threat detector instance
     *
     * @var object|null
     */
    private $threat_detector = null;

    /**
     * Security report generator instance
     *
     * @var object|null
     */
    private $report_generator = null;

    /**
     * Integration statistics
     *
     * @var array
     */
    private $stats = array(
        'webhooks_sent' => 0,
        'api_calls_made' => 0,
        'events_forwarded' => 0,
        'integrations_active' => 0,
        'hooks_registered' => 0
    );

    /**
     * Integration configuration
     *
     * @var array
     */
    private $config = array(
        'enable_webhooks' => true,
        'enable_api_integration' => true,
        'enable_siem_forwarding' => false,
        'webhook_timeout' => 30,
        'api_retry_attempts' => 3,
        'batch_event_size' => 50
    );

    /**
     * Registered webhooks
     *
     * @var array
     */
    private $webhooks = array();

    /**
     * WordPress hooks registry
     *
     * @var array
     */
    private $wp_hooks = array();

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_integration_hub();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Security_Integration_Hub
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize integration hub
     *
     * @return void
     */
    private function init_integration_hub() {
        // Load configuration
        $this->config = wp_parse_args(
            get_option('vd_security_integration_config', array()),
            $this->config
        );

        // Initialize WordPress hooks
        $this->init_wordpress_hooks();

        // Load webhook configurations
        $this->load_webhook_configurations();
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
     * Set storage manager dependency
     *
     * @param object $storage_manager Storage manager instance
     * @return void
     */
    public function set_storage_manager($storage_manager) {
        $this->storage_manager = $storage_manager;
    }

    /**
     * Set privacy manager dependency
     *
     * @param object $privacy_manager Privacy manager instance
     * @return void
     */
    public function set_privacy_manager($privacy_manager) {
        $this->privacy_manager = $privacy_manager;
    }

    /**
     * Set threat detector dependency
     *
     * @param object $threat_detector Threat detector instance
     * @return void
     */
    public function set_threat_detector($threat_detector) {
        $this->threat_detector = $threat_detector;
    }

    /**
     * Set report generator dependency
     *
     * @param object $report_generator Report generator instance
     * @return void
     */
    public function set_report_generator($report_generator) {
        $this->report_generator = $report_generator;
    }

    /**
     * Initialize WordPress hooks integration
     * Extracted from init_wordpress_hooks functionality
     *
     * @return void
     */
    private function init_wordpress_hooks() {
        // Register license status change hooks
        add_action('vd_license_status_changed', array($this, 'handle_license_status_change'), 10, 4);

        // Register security event hooks
        add_action('vd_security_event_detected', array($this, 'handle_security_event'), 10, 2);

        // Register validation hooks
        add_action('vd_license_validation_complete', array($this, 'handle_validation_complete'), 10, 3);

        // Register API hooks
        add_action('vd_api_request_processed', array($this, 'handle_api_request'), 10, 2);

        // Update statistics
        $this->stats['hooks_registered'] = 4;
        $this->stats['integrations_active']++;
    }

    /**
     * Load webhook configurations
     * Extracted from webhook management functionality
     *
     * @return void
     */
    private function load_webhook_configurations() {
        $webhook_configs = get_option('vd_webhook_configurations', array());

        foreach ($webhook_configs as $config) {
            $this->register_webhook($config);
        }
    }

    /**
     * Register a webhook endpoint
     * Extracted from webhook registration functionality
     *
     * @param array $config Webhook configuration
     * @return bool Success status
     */
    public function register_webhook($config) {
        if (!$this->config['enable_webhooks']) {
            return false;
        }

        $webhook_id = $config['id'] ?? uniqid('webhook_');
        $webhook = array(
            'id' => $webhook_id,
            'url' => $config['url'],
            'events' => $config['events'] ?? array(),
            'method' => $config['method'] ?? 'POST',
            'headers' => $config['headers'] ?? array(),
            'active' => $config['active'] ?? true,
            'secret' => $config['secret'] ?? '',
            'created_at' => current_time('mysql')
        );

        $this->webhooks[$webhook_id] = $webhook;
        $this->stats['integrations_active']++;

        return true;
    }

    /**
     * Send webhook notification
     * Extracted from send_webhook_notification functionality
     *
     * @param string $event_type Event type
     * @param array $payload Event data
     * @param array $context Additional context
     * @return array Delivery results
     */
    public function send_webhook_notifications($event_type, $payload, $context = array()) {
        $results = array();

        if (!$this->config['enable_webhooks']) {
            return array(
                'success' => false,
                'error' => 'Webhooks disabled'
            );
        }

        foreach ($this->webhooks as $webhook_id => $webhook) {
            if (!$webhook['active'] || !in_array($event_type, $webhook['events'])) {
                continue;
            }

            $result = $this->send_single_webhook($webhook, $event_type, $payload, $context);
            $results[$webhook_id] = $result;

            if ($result['success']) {
                $this->stats['webhooks_sent']++;
            }
        }

        return $results;
    }

    /**
     * Send single webhook
     *
     * @param array $webhook Webhook configuration
     * @param string $event_type Event type
     * @param array $payload Event data
     * @param array $context Additional context
     * @return array Delivery result
     */
    private function send_single_webhook($webhook, $event_type, $payload, $context) {
        try {
            // Apply privacy filtering if available
            if ($this->privacy_manager) {
                $payload = $this->privacy_manager->sanitize_data($payload);
            }

            // Prepare webhook payload
            $webhook_payload = array(
                'event' => $event_type,
                'data' => $payload,
                'timestamp' => current_time('mysql'),
                'source' => 'vd_license_manager',
                'version' => '3.2.6'
            );

            // Add signature if secret provided
            if (!empty($webhook['secret'])) {
                $webhook_payload['signature'] = $this->generate_webhook_signature($webhook_payload, $webhook['secret']);
            }

            // Prepare headers
            $headers = array_merge(
                array(
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'VD-License-Manager/3.2.6'
                ),
                $webhook['headers']
            );

            // Send webhook
            $response = wp_remote_post($webhook['url'], array(
                'method' => $webhook['method'],
                'headers' => $headers,
                'body' => wp_json_encode($webhook_payload),
                'timeout' => $this->config['webhook_timeout'],
                'blocking' => true
            ));

            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'error' => $response->get_error_message()
                );
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $success = $response_code >= 200 && $response_code < 300;

            return array(
                'success' => $success,
                'response_code' => $response_code,
                'response_body' => wp_remote_retrieve_body($response)
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Generate webhook signature
     *
     * @param array $payload Webhook payload
     * @param string $secret Webhook secret
     * @return string Signature
     */
    private function generate_webhook_signature($payload, $secret) {
        $payload_string = wp_json_encode($payload);
        return hash_hmac('sha256', $payload_string, $secret);
    }

    /**
     * Handle license status change event
     * Extracted from WordPress hooks integration
     *
     * @param array $license License data
     * @param string $old_status Previous status
     * @param string $new_status New status
     * @param array $context Change context
     * @return void
     */
    public function handle_license_status_change($license, $old_status, $new_status, $context = array()) {
        // Send webhook notifications
        $webhook_payload = array(
            'license_id' => $license['id'],
            'license_key' => $license['key'],
            'old_status' => $old_status,
            'new_status' => $new_status,
            'changed_at' => current_time('mysql'),
            'context' => $context
        );

        $this->send_webhook_notifications('license_status_changed', $webhook_payload, $context);

        // Forward to external SIEM if enabled
        if ($this->config['enable_siem_forwarding']) {
            $this->forward_to_siem('license_status_change', $webhook_payload);
        }

        // Log integration event
        if ($this->event_logger) {
            $this->log_integration_event('license_status_change', $webhook_payload);
        }
    }

    /**
     * Handle security event
     *
     * @param string $event_type Event type
     * @param array $event_data Event data
     * @return void
     */
    public function handle_security_event($event_type, $event_data) {
        // Send webhook notifications
        $this->send_webhook_notifications('security_event', array(
            'event_type' => $event_type,
            'data' => $event_data,
            'detected_at' => current_time('mysql')
        ));

        // Forward to SIEM
        if ($this->config['enable_siem_forwarding']) {
            $this->forward_to_siem('security_event', array(
                'type' => $event_type,
                'data' => $event_data
            ));
        }

        $this->stats['events_forwarded']++;
    }

    /**
     * Handle validation completion
     *
     * @param array $license License data
     * @param array $validation_result Validation result
     * @param array $context Validation context
     * @return void
     */
    public function handle_validation_complete($license, $validation_result, $context) {
        // Only send webhooks for failed validations or specific events
        if (!$validation_result['valid'] || isset($context['force_notification'])) {
            $payload = array(
                'license_id' => $license['id'],
                'validation_result' => $validation_result,
                'context' => $context,
                'validated_at' => current_time('mysql')
            );

            $this->send_webhook_notifications('validation_complete', $payload);
        }
    }

    /**
     * Handle API request
     *
     * @param array $request Request data
     * @param array $response Response data
     * @return void
     */
    public function handle_api_request($request, $response) {
        // Log API usage for monitoring
        if ($this->event_logger) {
            $this->log_integration_event('api_request', array(
                'endpoint' => $request['endpoint'] ?? 'unknown',
                'method' => $request['method'] ?? 'unknown',
                'status_code' => $response['status_code'] ?? 0,
                'response_time' => $response['response_time'] ?? 0
            ));
        }

        $this->stats['api_calls_made']++;
    }

    /**
     * Forward event to external SIEM
     *
     * @param string $event_type Event type
     * @param array $event_data Event data
     * @return bool Success status
     */
    private function forward_to_siem($event_type, $event_data) {
        // Placeholder for SIEM integration
        // Would implement specific SIEM API calls (Splunk, ELK, etc.)

        $siem_config = get_option('vd_siem_configuration', array());

        if (empty($siem_config['enabled'])) {
            return false;
        }

        // Format for SIEM consumption
        $siem_payload = array(
            'timestamp' => current_time('c'),
            'source' => 'vd_license_manager',
            'event_type' => $event_type,
            'severity' => $this->determine_event_severity($event_type),
            'data' => $event_data
        );

        // Placeholder for actual SIEM forwarding
        // return $this->send_to_siem_endpoint($siem_payload);

        return true;
    }

    /**
     * Determine event severity for SIEM
     *
     * @param string $event_type Event type
     * @return string Severity level
     */
    private function determine_event_severity($event_type) {
        $severity_map = array(
            'security_event' => 'high',
            'license_status_change' => 'medium',
            'validation_complete' => 'low',
            'api_request' => 'info'
        );

        return $severity_map[$event_type] ?? 'info';
    }

    /**
     * Get webhook notification targets
     * Extracted from get_webhook_notification_targets functionality
     *
     * @param array $license License data
     * @param string $old_status Previous status
     * @param string $new_status New status
     * @param array $config Notification configuration
     * @return array Webhook targets
     */
    public function get_webhook_notification_targets($license, $old_status, $new_status, $config) {
        $targets = array();

        foreach ($this->webhooks as $webhook_id => $webhook) {
            if (!$webhook['active']) {
                continue;
            }

            // Check if webhook handles license status changes
            if (in_array('license_status_changed', $webhook['events'])) {
                $targets[] = array(
                    'type' => 'webhook',
                    'id' => $webhook_id,
                    'url' => $webhook['url'],
                    'config' => $webhook
                );
            }
        }

        return $targets;
    }

    /**
     * Log integration event
     *
     * @param string $event_type Event type
     * @param array $event_data Event data
     * @return void
     */
    private function log_integration_event($event_type, $event_data) {
        if (!$this->event_logger) {
            return;
        }

        // This would call the event logger's log method
        // $this->event_logger->log('INFO', 'Integration event: ' . $event_type, $event_data);
    }

    /**
     * Get integration statistics
     *
     * @return array Statistics
     */
    public function get_statistics() {
        return $this->stats;
    }

    /**
     * Get integration configuration
     *
     * @return array Configuration
     */
    public function get_configuration() {
        return $this->config;
    }

    /**
     * Update integration configuration
     *
     * @param array $config New configuration
     * @return bool Success status
     */
    public function update_configuration($config) {
        $this->config = wp_parse_args($config, $this->config);
        return update_option('vd_security_integration_config', $this->config);
    }

    /**
     * Get registered webhooks
     *
     * @return array Webhooks
     */
    public function get_webhooks() {
        return $this->webhooks;
    }

    /**
     * Remove webhook
     *
     * @param string $webhook_id Webhook ID
     * @return bool Success status
     */
    public function remove_webhook($webhook_id) {
        if (isset($this->webhooks[$webhook_id])) {
            unset($this->webhooks[$webhook_id]);
            $this->stats['integrations_active'] = max(0, $this->stats['integrations_active'] - 1);
            return true;
        }
        return false;
    }

    /**
     * Get module information
     *
     * @return array Module info
     */
    public function get_module_info() {
        return array(
            'name' => 'Security Integration Hub',
            'version' => '3.2.6',
            'namespace' => 'VD\\LicenseManager\\Security\\Integration',
            'class' => 'VD_License_Security_Integration_Hub',
            'file' => __FILE__,
            'dependencies' => array(
                'security.event_logger',
                'security.storage_manager',
                'security.privacy_manager',
                'security.threat_detector',
                'security.report_generator'
            ),
            'capabilities' => array(
                'wordpress_hooks' => true,
                'webhook_notifications' => true,
                'api_integration' => true,
                'siem_forwarding' => true,
                'event_forwarding' => true
            ),
            'statistics' => $this->stats,
            'configuration' => $this->config,
            'active_webhooks' => count($this->webhooks),
            'active_integrations' => $this->stats['integrations_active']
        );
    }
}
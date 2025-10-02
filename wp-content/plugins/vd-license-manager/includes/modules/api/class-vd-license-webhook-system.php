<?php

namespace VD\LicenseManager\API;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Webhook System
 *
 * Step 4.2: Webhook System Module
 * Comprehensive webhook management system for license events
 * Extracted from monolithic validator for better organization and modularity
 *
 * @package VD\LicenseManager\API
 * @since 1.6.0
 * @author VD Team
 */
class VD_License_Webhook_System {

    /**
     * Singleton instance
     *
     * @var VD_License_Webhook_System|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '1.6.0';

    /**
     * Module name
     *
     * @var string
     */
    const MODULE_NAME = 'Webhook System';

    /**
     * Webhook configuration
     *
     * @var array
     */
    private $webhook_config = array();

    /**
     * Registered webhooks
     *
     * @var array
     */
    private $webhooks = array();

    /**
     * Webhook delivery queue
     *
     * @var array
     */
    private $delivery_queue = array();

    /**
     * Module statistics
     *
     * @var array
     */
    private $stats = array(
        'webhooks_registered' => 0,
        'webhooks_sent' => 0,
        'delivery_success' => 0,
        'delivery_failures' => 0,
        'total_attempts' => 0,
        'init_time' => 0,
        'memory_usage' => 0
    );

    /**
     * Event types supported
     *
     * @var array
     */
    private $supported_events = array(
        'license_status_changed',
        'license_activated',
        'license_deactivated',
        'license_expired',
        'license_suspended',
        'license_renewed',
        'license_created',
        'license_deleted'
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_webhook_system();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Webhook_System
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize webhook system
     *
     * @return void
     */
    private function init_webhook_system() {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        // Load default configuration
        $this->load_webhook_configuration();

        // Initialize hooks
        $this->init_wordpress_hooks();

        // Schedule cleanup
        $this->schedule_cleanup_tasks();

        // Update statistics
        $this->stats['init_time'] = (microtime(true) - $start_time) * 1000;
        $this->stats['memory_usage'] = memory_get_usage() - $start_memory;
    }

    /**
     * Load webhook configuration
     *
     * @return void
     */
    private function load_webhook_configuration() {
        $default_config = array(
            'enabled' => true,
            'retry_attempts' => 3,
            'retry_delay' => 300, // 5 minutes
            'timeout' => 30,
            'signature_secret' => wp_generate_password(32, false),
            'queue_enabled' => true,
            'batch_size' => 10,
            'rate_limit' => 100, // per hour
            'delivery_methods' => array('immediate', 'queued'),
            'content_types' => array('application/json', 'application/x-www-form-urlencoded'),
            'security' => array(
                'verify_ssl' => true,
                'signature_verification' => true,
                'ip_whitelist' => array(),
                'headers_validation' => true
            )
        );

        $this->webhook_config = wp_parse_args(
            get_option('vd_webhook_config', array()),
            $default_config
        );
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_wordpress_hooks() {
        // License event hooks
        add_action('vd_license_status_changed', array($this, 'handle_license_status_changed'), 10, 4);
        add_action('vd_license_activated', array($this, 'handle_license_activated'), 10, 2);
        add_action('vd_license_deactivated', array($this, 'handle_license_deactivated'), 10, 2);
        add_action('vd_license_expired', array($this, 'handle_license_expired'), 10, 2);

        // Webhook delivery hooks
        add_action('vd_webhook_deliver', array($this, 'deliver_webhook'), 10, 3);
        add_action('vd_webhook_retry', array($this, 'retry_webhook_delivery'), 10, 2);

        // Cleanup hooks
        add_action('vd_webhook_cleanup', array($this, 'cleanup_old_deliveries'));
    }

    /**
     * Register a webhook endpoint
     *
     * @param string $event Event type
     * @param string $url Webhook URL
     * @param array $options Webhook options
     * @return bool|string True on success, error message on failure
     */
    public function register_webhook($event, $url, $options = array()) {
        if (!$this->is_valid_event($event)) {
            return 'Invalid event type: ' . $event;
        }

        if (!$this->is_valid_url($url)) {
            return 'Invalid webhook URL: ' . $url;
        }

        $webhook_id = $this->generate_webhook_id($event, $url);

        $defaults = array(
            'name' => '',
            'description' => '',
            'active' => true,
            'events' => array($event),
            'headers' => array(),
            'auth_type' => 'none',
            'auth_credentials' => array(),
            'format' => 'json',
            'retry_attempts' => $this->webhook_config['retry_attempts'],
            'timeout' => $this->webhook_config['timeout'],
            'created_at' => current_time('mysql'),
            'last_used' => null
        );

        $webhook_config = wp_parse_args($options, $defaults);
        $webhook_config['url'] = $url;
        $webhook_config['id'] = $webhook_id;

        $this->webhooks[$webhook_id] = $webhook_config;
        $this->stats['webhooks_registered']++;

        // Save to database
        $this->save_webhook_configuration();

        return true;
    }

    /**
     * Send webhook notification
     *
     * @param string $event Event type
     * @param array $data Event data
     * @param array $context Additional context
     * @return array Delivery results
     */
    public function send_webhook_notification($event, $data, $context = array()) {
        if (!$this->webhook_config['enabled']) {
            return array(
                'success' => false,
                'message' => 'Webhook system is disabled'
            );
        }

        $webhooks = $this->get_webhooks_for_event($event);
        if (empty($webhooks)) {
            return array(
                'success' => true,
                'message' => 'No webhooks registered for event: ' . $event,
                'webhooks_found' => 0
            );
        }

        $results = array();
        foreach ($webhooks as $webhook) {
            if (!$webhook['active']) {
                continue;
            }

            $payload = $this->build_webhook_payload($event, $data, $context, $webhook);
            $delivery_result = $this->deliver_webhook_payload($webhook, $payload);

            $results[$webhook['id']] = $delivery_result;
            $this->stats['total_attempts']++;

            if ($delivery_result['success']) {
                $this->stats['delivery_success']++;
            } else {
                $this->stats['delivery_failures']++;
            }
        }

        $this->stats['webhooks_sent']++;

        return array(
            'success' => true,
            'event' => $event,
            'webhooks_processed' => count($results),
            'results' => $results
        );
    }

    /**
     * Build webhook payload
     *
     * @param string $event Event type
     * @param array $data Event data
     * @param array $context Context data
     * @param array $webhook Webhook configuration
     * @return array Webhook payload
     */
    private function build_webhook_payload($event, $data, $context, $webhook) {
        $payload = array(
            'event' => $event,
            'timestamp' => current_time('c'),
            'webhook_id' => $webhook['id'],
            'data' => $data,
            'context' => $context,
            'version' => self::VERSION
        );

        // Add signature if enabled
        if ($this->webhook_config['security']['signature_verification']) {
            $payload['signature'] = $this->generate_webhook_signature($payload, $webhook);
        }

        return $payload;
    }

    /**
     * Deliver webhook payload
     *
     * @param array $webhook Webhook configuration
     * @param array $payload Webhook payload
     * @return array Delivery result
     */
    private function deliver_webhook_payload($webhook, $payload) {
        $delivery_id = wp_generate_uuid4();

        if ($this->webhook_config['queue_enabled'] && !$this->is_high_priority_event($payload['event'])) {
            return $this->queue_webhook_delivery($webhook, $payload, $delivery_id);
        }

        return $this->send_immediate_webhook($webhook, $payload, $delivery_id);
    }

    /**
     * Send immediate webhook
     *
     * @param array $webhook Webhook configuration
     * @param array $payload Webhook payload
     * @param string $delivery_id Delivery ID
     * @return array Delivery result
     */
    private function send_immediate_webhook($webhook, $payload, $delivery_id) {
        $start_time = microtime(true);

        try {
            $headers = array_merge(
                array(
                    'Content-Type' => $webhook['format'] === 'json' ? 'application/json' : 'application/x-www-form-urlencoded',
                    'User-Agent' => 'VD-License-Manager-Webhook/' . self::VERSION,
                    'X-VD-Webhook-ID' => $webhook['id'],
                    'X-VD-Delivery-ID' => $delivery_id,
                    'X-VD-Event' => $payload['event'],
                    'X-VD-Timestamp' => $payload['timestamp']
                ),
                $webhook['headers']
            );

            // Add signature header
            if (isset($payload['signature'])) {
                $headers['X-VD-Signature'] = $payload['signature'];
            }

            // Prepare body
            $body = $webhook['format'] === 'json' ?
                json_encode($payload) :
                http_build_query($payload);

            // Add authentication
            if ($webhook['auth_type'] !== 'none') {
                $headers = $this->add_authentication_headers($headers, $webhook);
            }

            $response = wp_remote_post($webhook['url'], array(
                'timeout' => $webhook['timeout'],
                'headers' => $headers,
                'body' => $body,
                'sslverify' => $this->webhook_config['security']['verify_ssl'],
                'blocking' => true
            ));

            $execution_time = (microtime(true) - $start_time) * 1000;

            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'delivery_id' => $delivery_id,
                    'error' => $response->get_error_message(),
                    'execution_time' => $execution_time,
                    'retry_scheduled' => $this->schedule_webhook_retry($webhook, $payload, $delivery_id)
                );
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            $success = $status_code >= 200 && $status_code < 300;

            // Update webhook last used time
            if ($success) {
                $this->update_webhook_last_used($webhook['id']);
            }

            return array(
                'success' => $success,
                'delivery_id' => $delivery_id,
                'status_code' => $status_code,
                'response_body' => substr($response_body, 0, 1000), // Limit response size
                'execution_time' => $execution_time,
                'retry_scheduled' => !$success ? $this->schedule_webhook_retry($webhook, $payload, $delivery_id) : false
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'delivery_id' => $delivery_id,
                'error' => $e->getMessage(),
                'execution_time' => (microtime(true) - $start_time) * 1000,
                'retry_scheduled' => $this->schedule_webhook_retry($webhook, $payload, $delivery_id)
            );
        }
    }

    /**
     * Queue webhook delivery
     *
     * @param array $webhook Webhook configuration
     * @param array $payload Webhook payload
     * @param string $delivery_id Delivery ID
     * @return array Queue result
     */
    private function queue_webhook_delivery($webhook, $payload, $delivery_id) {
        $queue_item = array(
            'webhook' => $webhook,
            'payload' => $payload,
            'delivery_id' => $delivery_id,
            'attempts' => 0,
            'created_at' => current_time('mysql'),
            'scheduled_at' => current_time('mysql')
        );

        $this->delivery_queue[] = $queue_item;

        // Schedule background delivery
        wp_schedule_single_event(time() + 60, 'vd_webhook_deliver', array(
            $webhook['id'],
            $payload,
            $delivery_id
        ));

        return array(
            'success' => true,
            'delivery_id' => $delivery_id,
            'queued' => true,
            'message' => 'Webhook queued for delivery'
        );
    }

    /**
     * Generate webhook signature
     *
     * @param array $payload Webhook payload
     * @param array $webhook Webhook configuration
     * @return string Signature hash
     */
    private function generate_webhook_signature($payload, $webhook) {
        $secret = isset($webhook['signature_secret']) ?
            $webhook['signature_secret'] :
            $this->webhook_config['signature_secret'];

        $payload_string = is_array($payload) ? json_encode($payload) : $payload;
        return 'sha256=' . hash_hmac('sha256', $payload_string, $secret);
    }

    /**
     * Add authentication headers
     *
     * @param array $headers Existing headers
     * @param array $webhook Webhook configuration
     * @return array Updated headers
     */
    private function add_authentication_headers($headers, $webhook) {
        switch ($webhook['auth_type']) {
            case 'bearer':
                if (!empty($webhook['auth_credentials']['token'])) {
                    $headers['Authorization'] = 'Bearer ' . $webhook['auth_credentials']['token'];
                }
                break;

            case 'basic':
                if (!empty($webhook['auth_credentials']['username']) &&
                    !empty($webhook['auth_credentials']['password'])) {
                    $credentials = base64_encode(
                        $webhook['auth_credentials']['username'] . ':' .
                        $webhook['auth_credentials']['password']
                    );
                    $headers['Authorization'] = 'Basic ' . $credentials;
                }
                break;

            case 'api_key':
                if (!empty($webhook['auth_credentials']['key']) &&
                    !empty($webhook['auth_credentials']['header'])) {
                    $headers[$webhook['auth_credentials']['header']] = $webhook['auth_credentials']['key'];
                }
                break;
        }

        return $headers;
    }

    /**
     * Handle license status changed event
     *
     * @param array $license License data
     * @param string $old_status Old status
     * @param string $new_status New status
     * @param array $context Event context
     */
    public function handle_license_status_changed($license, $old_status, $new_status, $context = array()) {
        $event_data = array(
            'license' => $this->sanitize_license_data($license),
            'old_status' => $old_status,
            'new_status' => $new_status,
            'changed_at' => current_time('c')
        );

        $this->send_webhook_notification('license_status_changed', $event_data, $context);
    }

    /**
     * Handle license activated event
     *
     * @param array $license License data
     * @param array $context Event context
     */
    public function handle_license_activated($license, $context = array()) {
        $event_data = array(
            'license' => $this->sanitize_license_data($license),
            'activated_at' => current_time('c')
        );

        $this->send_webhook_notification('license_activated', $event_data, $context);
    }

    /**
     * Handle license deactivated event
     *
     * @param array $license License data
     * @param array $context Event context
     */
    public function handle_license_deactivated($license, $context = array()) {
        $event_data = array(
            'license' => $this->sanitize_license_data($license),
            'deactivated_at' => current_time('c')
        );

        $this->send_webhook_notification('license_deactivated', $event_data, $context);
    }

    /**
     * Handle license expired event
     *
     * @param array $license License data
     * @param array $context Event context
     */
    public function handle_license_expired($license, $context = array()) {
        $event_data = array(
            'license' => $this->sanitize_license_data($license),
            'expired_at' => current_time('c')
        );

        $this->send_webhook_notification('license_expired', $event_data, $context);
    }

    /**
     * Get webhooks for specific event
     *
     * @param string $event Event type
     * @return array Matching webhooks
     */
    private function get_webhooks_for_event($event) {
        $matching_webhooks = array();

        foreach ($this->webhooks as $webhook) {
            if (in_array($event, $webhook['events']) || in_array('*', $webhook['events'])) {
                $matching_webhooks[] = $webhook;
            }
        }

        return $matching_webhooks;
    }

    /**
     * Sanitize license data for webhook payload
     *
     * @param array $license License data
     * @return array Sanitized license data
     */
    private function sanitize_license_data($license) {
        $safe_fields = array(
            'id', 'key', 'status', 'product_id',
            'created_at', 'expires_at', 'activations_limit'
        );

        $sanitized = array();
        foreach ($safe_fields as $field) {
            if (isset($license[$field])) {
                $sanitized[$field] = $license[$field];
            }
        }

        return $sanitized;
    }

    // Helper methods
    private function is_valid_event($event) {
        return in_array($event, $this->supported_events) || $event === '*';
    }

    private function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function generate_webhook_id($event, $url) {
        return 'webhook_' . md5($event . $url . time());
    }

    private function is_high_priority_event($event) {
        $high_priority = array('license_activated', 'license_deactivated', 'license_expired');
        return in_array($event, $high_priority);
    }

    private function schedule_webhook_retry($webhook, $payload, $delivery_id) {
        if ($webhook['retry_attempts'] > 0) {
            wp_schedule_single_event(
                time() + $this->webhook_config['retry_delay'],
                'vd_webhook_retry',
                array($webhook['id'], $delivery_id)
            );
            return true;
        }
        return false;
    }

    private function update_webhook_last_used($webhook_id) {
        if (isset($this->webhooks[$webhook_id])) {
            $this->webhooks[$webhook_id]['last_used'] = current_time('mysql');
            $this->save_webhook_configuration();
        }
    }

    private function save_webhook_configuration() {
        update_option('vd_webhook_system', $this->webhooks);
        update_option('vd_webhook_config', $this->webhook_config);
    }

    private function schedule_cleanup_tasks() {
        if (!wp_next_scheduled('vd_webhook_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vd_webhook_cleanup');
        }
    }

    /**
     * Get module statistics
     *
     * @return array Module statistics
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Get webhook configuration
     *
     * @return array Webhook configuration
     */
    public function get_configuration() {
        return $this->webhook_config;
    }

    /**
     * Get registered webhooks
     *
     * @return array Registered webhooks
     */
    public function get_webhooks() {
        return $this->webhooks;
    }

    /**
     * Get supported events
     *
     * @return array Supported event types
     */
    public function get_supported_events() {
        return $this->supported_events;
    }

    /**
     * Get module version
     *
     * @return string Module version
     */
    public function get_version() {
        return self::VERSION;
    }

    /**
     * Get module name
     *
     * @return string Module name
     */
    public function get_module_name() {
        return self::MODULE_NAME;
    }

    /**
     * Check if webhook system is enabled
     *
     * @return bool True if enabled, false otherwise
     */
    public function is_enabled() {
        return $this->webhook_config['enabled'];
    }
}
<?php

namespace VD\LicenseManager\Integration;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Third-party Integration Manager
 *
 * Centralized management system for all third-party service integrations
 * including Helium10, Midjourney, Freepik, WooCommerce and external APIs
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

/**
 * Class VD_License_Integration_Manager
 *
 * Manages all third-party service integrations and external API communications
 */
class VD_License_Integration_Manager {

    /**
     * Singleton instance
     *
     * @var VD_License_Integration_Manager|null
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
    const MODULE_NAME = 'Third-party Integration Manager';

    /**
     * Supported providers
     *
     * @var array
     */
    private $supported_providers = array(
        'helium10' => array(
            'name' => 'Helium10',
            'auth_type' => 'credentials_2fa',
            'api_version' => 'v2',
            'endpoints' => array('account', 'tools', 'billing')
        ),
        'midjourney' => array(
            'name' => 'Midjourney',
            'auth_type' => 'discord_token',
            'api_version' => 'v1',
            'endpoints' => array('generate', 'upscale', 'variation')
        ),
        'freepik' => array(
            'name' => 'Freepik',
            'auth_type' => 'api_key',
            'api_version' => 'v1',
            'endpoints' => array('search', 'download', 'collections')
        ),
        'woocommerce' => array(
            'name' => 'WooCommerce',
            'auth_type' => 'internal',
            'api_version' => 'v3',
            'endpoints' => array('orders', 'products', 'customers')
        )
    );

    /**
     * Provider instances cache
     *
     * @var array
     */
    private $provider_instances = array();

    /**
     * Integration configuration
     *
     * @var array
     */
    private $config = array();

    /**
     * Statistics tracking
     *
     * @var array
     */
    private $stats = array(
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'providers_connected' => 0,
        'init_time' => 0,
        'memory_usage' => 0
    );

    /**
     * Constructor
     */
    private function __construct() {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        $this->init_configuration();
        $this->register_hooks();

        $this->stats['init_time'] = (microtime(true) - $start_time) * 1000;
        $this->stats['memory_usage'] = memory_get_usage() - $start_memory;
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Integration_Manager
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
        $this->config = wp_parse_args(get_option('vd_integration_config', array()), array(
            'enabled' => true,
            'default_timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 2,
            'cache_ttl' => 3600,
            'rate_limit' => array(
                'requests_per_minute' => 60,
                'requests_per_hour' => 1000
            ),
            'webhooks' => array(
                'enabled' => true,
                'signature_verification' => true,
                'ssl_verification' => true
            ),
            'logging' => array(
                'enabled' => true,
                'level' => 'info',
                'retention_days' => 30
            ),
            'providers' => array(
                'helium10' => array('enabled' => true, 'priority' => 1),
                'midjourney' => array('enabled' => true, 'priority' => 2),
                'freepik' => array('enabled' => true, 'priority' => 3),
                'woocommerce' => array('enabled' => true, 'priority' => 4)
            )
        ));
    }

    /**
     * Register WordPress hooks
     *
     * @return void
     */
    private function register_hooks() {
        add_action('init', array($this, 'init_providers'));
        add_action('wp_ajax_vd_test_integration', array($this, 'handle_integration_test'));
        add_action('wp_ajax_vd_provider_connect', array($this, 'handle_provider_connection'));
        add_action('wp_ajax_vd_provider_disconnect', array($this, 'handle_provider_disconnection'));

        // WooCommerce integration hooks
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
            add_action('woocommerce_product_purchased', array($this, 'handle_product_purchase'), 10, 2);
        }

        // License status hooks
        add_action('vd_license_status_changed', array($this, 'notify_providers'), 10, 3);
        add_action('vd_license_activated', array($this, 'provider_activation_sync'), 10, 2);
        add_action('vd_license_deactivated', array($this, 'provider_deactivation_sync'), 10, 2);
    }

    /**
     * Initialize provider connections
     *
     * @return void
     */
    public function init_providers() {
        foreach ($this->supported_providers as $provider_id => $provider_config) {
            if ($this->is_provider_enabled($provider_id)) {
                $this->load_provider($provider_id);
            }
        }

        $this->stats['providers_connected'] = count($this->provider_instances);
    }

    /**
     * Load specific provider
     *
     * @param string $provider_id Provider identifier
     * @return bool Success status
     */
    public function load_provider($provider_id) {
        if (!isset($this->supported_providers[$provider_id])) {
            return false;
        }

        if (isset($this->provider_instances[$provider_id])) {
            return true;
        }

        try {
            $provider_class = $this->get_provider_class_name($provider_id);

            if (!class_exists($provider_class)) {
                $this->load_provider_file($provider_id);
            }

            if (class_exists($provider_class)) {
                $this->provider_instances[$provider_id] = new $provider_class($this->config);
                return true;
            }
        } catch (Exception $e) {
            $this->log_error("Failed to load provider {$provider_id}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Get provider class name
     *
     * @param string $provider_id Provider identifier
     * @return string Class name
     */
    private function get_provider_class_name($provider_id) {
        $class_map = array(
            'helium10' => 'VD\\LicenseManager\\Integration\\Providers\\VD_License_Provider_Helium10',
            'midjourney' => 'VD\\LicenseManager\\Integration\\Providers\\VD_License_Provider_Midjourney',
            'freepik' => 'VD\\LicenseManager\\Integration\\Providers\\VD_License_Provider_Freepik',
            'woocommerce' => 'VD\\LicenseManager\\Integration\\WooCommerce\\VD_License_WooCommerce_Integration'
        );

        return isset($class_map[$provider_id]) ? $class_map[$provider_id] : '';
    }

    /**
     * Load provider file
     *
     * @param string $provider_id Provider identifier
     * @return void
     */
    private function load_provider_file($provider_id) {
        $file_map = array(
            'helium10' => 'providers/class-vd-license-provider-helium10.php',
            'midjourney' => 'providers/class-vd-license-provider-midjourney.php',
            'freepik' => 'providers/class-vd-license-provider-freepik.php',
            'woocommerce' => 'woocommerce/class-vd-license-woocommerce-integration.php'
        );

        if (isset($file_map[$provider_id])) {
            $file_path = plugin_dir_path(__FILE__) . $file_map[$provider_id];
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Check if provider is enabled
     *
     * @param string $provider_id Provider identifier
     * @return bool Enabled status
     */
    public function is_provider_enabled($provider_id) {
        return isset($this->config['providers'][$provider_id]['enabled'])
               && $this->config['providers'][$provider_id]['enabled'];
    }

    /**
     * Get provider instance
     *
     * @param string $provider_id Provider identifier
     * @return object|false Provider instance or false
     */
    public function get_provider($provider_id) {
        if (!isset($this->provider_instances[$provider_id])) {
            $this->load_provider($provider_id);
        }

        return isset($this->provider_instances[$provider_id])
               ? $this->provider_instances[$provider_id]
               : false;
    }

    /**
     * Send request to external provider
     *
     * @param string $provider_id Provider identifier
     * @param string $endpoint Endpoint name
     * @param array $data Request data
     * @param string $method HTTP method
     * @return array Response data
     */
    public function send_provider_request($provider_id, $endpoint, $data = array(), $method = 'POST') {
        $this->stats['total_requests']++;

        if (!$this->is_provider_enabled($provider_id)) {
            $this->stats['failed_requests']++;
            return array(
                'success' => false,
                'error' => "Provider {$provider_id} is not enabled"
            );
        }

        $provider = $this->get_provider($provider_id);
        if (!$provider) {
            $this->stats['failed_requests']++;
            return array(
                'success' => false,
                'error' => "Provider {$provider_id} not available"
            );
        }

        try {
            $response = $provider->send_request($endpoint, $data, $method);

            if ($response && isset($response['success']) && $response['success']) {
                $this->stats['successful_requests']++;
            } else {
                $this->stats['failed_requests']++;
            }

            return $response;
        } catch (Exception $e) {
            $this->stats['failed_requests']++;
            $this->log_error("Provider request failed: " . $e->getMessage());

            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Handle license status change notifications to providers
     *
     * @param string $license_key License key
     * @param string $old_status Old status
     * @param string $new_status New status
     * @return void
     */
    public function notify_providers($license_key, $old_status, $new_status) {
        $notification_data = array(
            'license_key' => $license_key,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'timestamp' => current_time('mysql'),
            'event_type' => 'license_status_changed'
        );

        foreach ($this->provider_instances as $provider_id => $provider) {
            if (method_exists($provider, 'handle_license_notification')) {
                $provider->handle_license_notification($notification_data);
            }
        }
    }

    /**
     * Handle provider activation sync
     *
     * @param string $license_key License key
     * @param array $license_data License data
     * @return void
     */
    public function provider_activation_sync($license_key, $license_data) {
        if (isset($license_data['provider']) && $license_data['provider']) {
            $provider = $this->get_provider($license_data['provider']);
            if ($provider && method_exists($provider, 'sync_activation')) {
                $provider->sync_activation($license_key, $license_data);
            }
        }
    }

    /**
     * Handle provider deactivation sync
     *
     * @param string $license_key License key
     * @param array $license_data License data
     * @return void
     */
    public function provider_deactivation_sync($license_key, $license_data) {
        if (isset($license_data['provider']) && $license_data['provider']) {
            $provider = $this->get_provider($license_data['provider']);
            if ($provider && method_exists($provider, 'sync_deactivation')) {
                $provider->sync_deactivation($license_key, $license_data);
            }
        }
    }

    /**
     * Handle WooCommerce order status change
     *
     * @param int $order_id Order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @return void
     */
    public function handle_order_status_change($order_id, $old_status, $new_status) {
        if ($this->is_provider_enabled('woocommerce')) {
            $wc_provider = $this->get_provider('woocommerce');
            if ($wc_provider && method_exists($wc_provider, 'handle_order_status_change')) {
                $wc_provider->handle_order_status_change($order_id, $old_status, $new_status);
            }
        }
    }

    /**
     * Handle WooCommerce product purchase
     *
     * @param int $product_id Product ID
     * @param int $user_id User ID
     * @return void
     */
    public function handle_product_purchase($product_id, $user_id) {
        if ($this->is_provider_enabled('woocommerce')) {
            $wc_provider = $this->get_provider('woocommerce');
            if ($wc_provider && method_exists($wc_provider, 'handle_product_purchase')) {
                $wc_provider->handle_product_purchase($product_id, $user_id);
            }
        }
    }

    /**
     * Handle provider connection via AJAX
     *
     * @return void
     */
    public function handle_provider_connection() {
        check_ajax_referer('vd_provider_action', 'nonce');

        $provider_id = sanitize_text_field($_POST['provider_id'] ?? '');
        $credentials = $_POST['credentials'] ?? array();

        if (empty($provider_id) || !isset($this->supported_providers[$provider_id])) {
            wp_send_json_error('Invalid provider ID');
            return;
        }

        $provider = $this->get_provider($provider_id);
        if (!$provider) {
            wp_send_json_error('Provider not available');
            return;
        }

        if (method_exists($provider, 'connect')) {
            $result = $provider->connect($credentials);
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['error']);
            }
        } else {
            wp_send_json_error('Provider does not support connection');
        }
    }

    /**
     * Handle provider disconnection via AJAX
     *
     * @return void
     */
    public function handle_provider_disconnection() {
        check_ajax_referer('vd_provider_action', 'nonce');

        $provider_id = sanitize_text_field($_POST['provider_id'] ?? '');

        if (empty($provider_id) || !isset($this->supported_providers[$provider_id])) {
            wp_send_json_error('Invalid provider ID');
            return;
        }

        $provider = $this->get_provider($provider_id);
        if (!$provider) {
            wp_send_json_error('Provider not available');
            return;
        }

        if (method_exists($provider, 'disconnect')) {
            $result = $provider->disconnect();
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['error']);
            }
        } else {
            wp_send_json_error('Provider does not support disconnection');
        }
    }

    /**
     * Get integration statistics
     *
     * @return array Statistics data
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Get supported providers
     *
     * @return array Supported providers
     */
    public function get_supported_providers() {
        return $this->supported_providers;
    }

    /**
     * Get current configuration
     *
     * @return array Configuration
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * Update configuration
     *
     * @param array $new_config New configuration
     * @return bool Update success
     */
    public function update_config($new_config) {
        $this->config = wp_parse_args($new_config, $this->config);
        return update_option('vd_integration_config', $this->config);
    }

    /**
     * Log error message
     *
     * @param string $message Error message
     * @return void
     */
    private function log_error($message) {
        if ($this->config['logging']['enabled']) {
            error_log('[VD Integration Manager] ' . $message);
        }
    }

    /**
     * Handle integration test via AJAX
     *
     * @return void
     */
    public function handle_integration_test() {
        wp_send_json_success(array(
            'message' => 'Integration Manager test passed',
            'module_loaded' => true,
            'class_name' => get_class($this),
            'version' => self::VERSION,
            'providers_loaded' => count($this->provider_instances),
            'supported_providers' => array_keys($this->supported_providers),
            'stats' => $this->get_stats(),
            'timestamp' => current_time('mysql')
        ));
    }
}
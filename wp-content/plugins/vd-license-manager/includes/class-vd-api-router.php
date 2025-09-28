<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD API Router Class
 *
 * Handles WordPress REST API routing for VD License Manager
 * Step 4.1.1 - Basic Router Class Structure
 *
 * @since 4.1.1
 * @package VD_License_Manager
 */
class VD_API_Router {

    /**
     * Singleton instance
     *
     * @since 4.1.1
     * @var VD_API_Router|null
     */
    private static $instance = null;

    /**
     * WordPress REST API namespace
     *
     * @since 4.1.1
     * @var string
     */
    private $namespace = 'vd/v1';

    /**
     * API version
     *
     * @since 4.1.1
     * @var string
     */
    private $version = '1';

    /**
     * Registered routes array
     *
     * @since 4.1.1
     * @var array
     */
    private $routes = array();

    /**
     * API Security manager instance
     *
     * @since 4.1.1
     * @var VD_API_Security|null
     */
    private $security_manager = null;

    /**
     * Request Validator instance
     *
     * @since 4.1.1
     * @var VD_Request_Validator|null
     */
    private $request_validator = null;

    /**
     * Router initialization status
     *
     * @since 4.1.1
     * @var bool
     */
    private $initialized = false;

    /**
     * Private constructor to enforce singleton pattern
     *
     * @since 4.1.1
     */
    private function __construct() {
        // Initialize security manager if available
        if (class_exists('VD_API_Security')) {
            $this->security_manager = VD_API_Security::get_instance();
        }

        // Initialize request validator if available
        if (class_exists('VD_Request_Validator')) {
            $this->request_validator = VD_Request_Validator::get_instance();
        }

        $this->initialized = true;
    }

    /**
     * Get singleton instance
     *
     * @since 4.1.1
     * @return VD_API_Router Single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning
     *
     * @since 4.1.1
     */
    private function __clone() {
        // Empty - prevent cloning
    }

    /**
     * Prevent unserialization
     *
     * @since 4.1.1
     * @throws Exception
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Get router status
     *
     * @since 4.1.1
     * @return string Router status
     */
    public function get_status() {
        return $this->initialized ? 'initialized' : 'pending';
    }

    /**
     * Check if router is working
     *
     * @since 4.1.1
     * @return bool True if working, false otherwise
     */
    public function is_working() {
        return $this->initialized && !is_null($this->security_manager);
    }

    /**
     * Get current implementation step
     *
     * @since 4.1.1
     * @return string Current step
     */
    public function get_current_step() {
        return '4.1.1';
    }

    /**
     * Get WordPress REST API namespace
     *
     * @since 4.1.1
     * @return string Namespace
     */
    public function get_namespace() {
        return $this->namespace;
    }

    /**
     * Get API version
     *
     * @since 4.1.1
     * @return string Version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get registered routes
     *
     * @since 4.1.1
     * @return array Registered routes
     */
    public function get_registered_routes() {
        return $this->routes;
    }

    /**
     * Get security manager instance
     *
     * @since 4.1.1
     * @return VD_API_Security|null Security manager instance
     */
    public function get_security_manager() {
        return $this->security_manager;
    }

    /**
     * Get request validator instance
     *
     * @since 4.1.1
     * @return VD_Request_Validator|null Request validator instance
     */
    public function get_request_validator() {
        return $this->request_validator;
    }

    /**
     * Test router functionality
     * Step 4.1.1 - Basic testing infrastructure
     *
     * @since 4.1.1
     * @return array Test results
     */
    public function test_router_functionality() {
        return array(
            'step' => $this->get_current_step(),
            'singleton_working' => (self::get_instance() === $this),
            'routes_registered' => count($this->routes),
            'security_available' => !is_null($this->security_manager),
            'validator_available' => !is_null($this->request_validator),
            'namespace' => $this->namespace,
            'version' => $this->version,
            'initialized' => $this->initialized,
            'working_status' => $this->is_working()
        );
    }

    /**
     * Initialize router - WordPress REST API integration
     * Step 4.1.3 - REST API Namespace Registration
     *
     * @since 4.1.3
     */
    public function init() {
        // Step 4.1.3 - Register REST API hooks
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register routes with WordPress REST API
     * Step 4.1.3 - REST API Namespace Registration
     *
     * @since 4.1.3
     */
    public function register_routes() {
        // Step 4.1.3 - Basic namespace registration
        // Register a test route to verify namespace accessibility
        register_rest_route($this->namespace, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_status_check'),
            'permission_callback' => '__return_true', // Public endpoint for testing
            'args' => array()
        ));

        // Step 4.1.3 - Register router info endpoint
        register_rest_route($this->namespace, '/router-info', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_router_info'),
            'permission_callback' => '__return_true', // Public endpoint for testing
            'args' => array()
        ));

        // Update routes array for tracking
        $this->routes = array(
            'GET /status' => 'Namespace status verification endpoint',
            'GET /router-info' => 'Router information và diagnostics endpoint'
        );
    }

    /**
     * Handle status check endpoint
     * Step 4.1.3 - Basic namespace verification
     *
     * @since 4.1.3
     * @param WP_REST_Request $request The REST request
     * @return WP_REST_Response Response object
     */
    public function handle_status_check($request) {
        $response_data = array(
            'success' => true,
            'message' => 'VD License Manager REST API namespace active',
            'namespace' => $this->namespace,
            'timestamp' => current_time('c'),
            'version' => $this->version,
            'step' => '4.1.3',
            'endpoints_registered' => count($this->routes)
        );

        return rest_ensure_response($response_data);
    }

    /**
     * Handle router info endpoint
     * Step 4.1.3 - Router diagnostics và information
     *
     * @since 4.1.3
     * @param WP_REST_Request $request The REST request
     * @return WP_REST_Response Response object
     */
    public function handle_router_info($request) {
        $router_status = $this->test_router_functionality();

        $response_data = array(
            'success' => true,
            'data' => array(
                'router_status' => $router_status,
                'namespace' => $this->namespace,
                'version' => $this->version,
                'registered_routes' => $this->routes,
                'security_manager_available' => !is_null($this->security_manager),
                'request_validator_available' => !is_null($this->request_validator),
                'wordpress_rest_api' => array(
                    'version' => rest_get_server()->get_index()['namespaces'] ?? 'unknown',
                    'available' => function_exists('register_rest_route')
                )
            ),
            'timestamp' => current_time('c'),
            'step' => '4.1.3'
        );

        return rest_ensure_response($response_data);
    }
}
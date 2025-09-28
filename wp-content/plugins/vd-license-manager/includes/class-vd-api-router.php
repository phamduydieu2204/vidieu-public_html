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

        // Step 4.1.4 - Core License Management Endpoints
        $this->register_core_license_endpoints();

        // Update routes array for tracking
        $this->routes = array(
            'GET /status' => 'Namespace status verification endpoint',
            'GET /router-info' => 'Router information và diagnostics endpoint',
            'POST /license/resolve-info' => 'Main license resolution endpoint',
            'POST /license/resolve-cookie' => 'Cookie-based license resolution endpoint',
            'GET /license/device-status' => 'Device status checking endpoint'
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
        // Step 4.1.3 - Simplified router info to avoid fatal errors
        try {
            $response_data = array(
                'success' => true,
                'data' => array(
                    'namespace' => $this->namespace,
                    'version' => $this->version,
                    'current_step' => $this->get_current_step(),
                    'routes_count' => count($this->routes),
                    'registered_routes' => $this->routes,
                    'initialized' => $this->initialized,
                    'basic_status' => 'Router operational'
                ),
                'timestamp' => current_time('c'),
                'step' => '4.1.3'
            );

            return rest_ensure_response($response_data);
        } catch (Exception $e) {
            // Fallback minimal response
            $fallback_data = array(
                'success' => false,
                'error' => 'Router info generation failed',
                'message' => $e->getMessage(),
                'timestamp' => current_time('c'),
                'step' => '4.1.3'
            );

            return rest_ensure_response($fallback_data);
        }
    }

    /**
     * Register core license management endpoints
     * Step 4.1.4 - Core Endpoint Definitions
     *
     * @since 4.1.4
     */
    private function register_core_license_endpoints() {
        // 1. Main license resolution endpoint
        register_rest_route($this->namespace, '/license/resolve-info', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_license_resolve_info'),
            'permission_callback' => '__return_true', // Public endpoint - will validate internally
            'args' => $this->get_license_resolve_args()
        ));

        // 2. Cookie-based license resolution endpoint
        register_rest_route($this->namespace, '/license/resolve-cookie', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_license_resolve_cookie'),
            'permission_callback' => '__return_true', // Public endpoint - will validate internally
            'args' => $this->get_cookie_resolve_args()
        ));

        // 3. Device status checking endpoint
        register_rest_route($this->namespace, '/license/device-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_device_status'),
            'permission_callback' => '__return_true', // Public endpoint - will validate internally
            'args' => $this->get_device_status_args()
        ));
    }

    /**
     * Get license resolve endpoint arguments
     * Step 4.1.4 - Request parameter validation
     *
     * @since 4.1.4
     * @return array Endpoint arguments
     */
    private function get_license_resolve_args() {
        return array(
            'license_key' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'License key to resolve',
                'pattern' => '^VD-[A-Z0-9]+-[0-9]{4}-[A-Z0-9]+$'
            ),
            'device_fingerprint' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'SHA256 device fingerprint (64 characters)',
                'pattern' => '^[a-f0-9]{64}$'
            ),
            'device_info' => array(
                'required' => false,
                'type' => 'object',
                'description' => 'Device information object'
            ),
            'client_ip' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Client IP address'
            ),
            'request_id' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Request tracking ID'
            )
        );
    }

    /**
     * Get cookie resolve endpoint arguments
     * Step 4.1.4 - Request parameter validation
     *
     * @since 4.1.4
     * @return array Endpoint arguments
     */
    private function get_cookie_resolve_args() {
        return array(
            'license_key' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'License key to resolve',
                'pattern' => '^VD-[A-Z0-9]+-[0-9]{4}-[A-Z0-9]+$'
            ),
            'device_fingerprint' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'SHA256 device fingerprint (64 characters)',
                'pattern' => '^[a-f0-9]{64}$'
            )
        );
    }

    /**
     * Get device status endpoint arguments
     * Step 4.1.4 - Request parameter validation
     *
     * @since 4.1.4
     * @return array Endpoint arguments
     */
    private function get_device_status_args() {
        return array(
            'license_key' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'License key',
                'pattern' => '^VD-[A-Z0-9]+-[0-9]{4}-[A-Z0-9]+$'
            ),
            'device_fingerprint' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'SHA256 device fingerprint (64 characters)',
                'pattern' => '^[a-f0-9]{64}$'
            )
        );
    }

    /**
     * Handle license resolve info endpoint
     * Step 4.1.4 - Main license resolution endpoint
     *
     * @since 4.1.4
     * @param WP_REST_Request $request The REST request
     * @return WP_REST_Response Response object
     */
    public function handle_license_resolve_info($request) {
        try {
            // Step 4.1.4 - Placeholder implementation
            $response_data = array(
                'success' => true,
                'message' => 'License resolve info endpoint ready',
                'data' => array(
                    'endpoint' => '/license/resolve-info',
                    'method' => 'POST',
                    'status' => 'placeholder_implementation',
                    'received_params' => array(
                        'license_key' => $request->get_param('license_key'),
                        'device_fingerprint' => $request->get_param('device_fingerprint'),
                        'has_device_info' => !is_null($request->get_param('device_info')),
                        'client_ip' => $request->get_param('client_ip'),
                        'request_id' => $request->get_param('request_id')
                    )
                ),
                'timestamp' => current_time('c'),
                'step' => '4.1.4'
            );

            return rest_ensure_response($response_data);
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => 'License resolve info failed',
                'message' => $e->getMessage(),
                'timestamp' => current_time('c'),
                'step' => '4.1.4'
            ));
        }
    }

    /**
     * Handle license resolve cookie endpoint
     * Step 4.1.4 - Cookie-based license resolution endpoint
     *
     * @since 4.1.4
     * @param WP_REST_Request $request The REST request
     * @return WP_REST_Response Response object
     */
    public function handle_license_resolve_cookie($request) {
        try {
            // Step 4.1.4 - Placeholder implementation
            $response_data = array(
                'success' => true,
                'message' => 'License resolve cookie endpoint ready',
                'data' => array(
                    'endpoint' => '/license/resolve-cookie',
                    'method' => 'POST',
                    'status' => 'placeholder_implementation',
                    'received_params' => array(
                        'license_key' => $request->get_param('license_key'),
                        'device_fingerprint' => $request->get_param('device_fingerprint')
                    )
                ),
                'timestamp' => current_time('c'),
                'step' => '4.1.4'
            );

            return rest_ensure_response($response_data);
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => 'License resolve cookie failed',
                'message' => $e->getMessage(),
                'timestamp' => current_time('c'),
                'step' => '4.1.4'
            ));
        }
    }

    /**
     * Handle device status endpoint
     * Step 4.1.4 - Device status checking endpoint
     *
     * @since 4.1.4
     * @param WP_REST_Request $request The REST request
     * @return WP_REST_Response Response object
     */
    public function handle_device_status($request) {
        try {
            // Step 4.1.4 - Placeholder implementation
            $response_data = array(
                'success' => true,
                'message' => 'Device status endpoint ready',
                'data' => array(
                    'endpoint' => '/license/device-status',
                    'method' => 'GET',
                    'status' => 'placeholder_implementation',
                    'received_params' => array(
                        'license_key' => $request->get_param('license_key'),
                        'device_fingerprint' => $request->get_param('device_fingerprint')
                    )
                ),
                'timestamp' => current_time('c'),
                'step' => '4.1.4'
            );

            return rest_ensure_response($response_data);
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => 'Device status check failed',
                'message' => $e->getMessage(),
                'timestamp' => current_time('c'),
                'step' => '4.1.4'
            ));
        }
    }

    /**
     * Get REST API index safely
     * Step 4.1.3 - Safe REST API index retrieval
     *
     * @since 4.1.3
     * @return string REST API version information
     */
    private function get_safe_rest_index() {
        try {
            $server = rest_get_server();
            if ($server && method_exists($server, 'get_index')) {
                $index = $server->get_index();
                if (is_array($index) && isset($index['namespaces'])) {
                    return 'v' . count($index['namespaces']) . ' namespaces';
                }
            }
            return 'REST API available';
        } catch (Exception $e) {
            return 'REST API error: ' . $e->getMessage();
        }
    }
}
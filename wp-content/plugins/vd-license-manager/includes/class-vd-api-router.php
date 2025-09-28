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
     * Step 4.1.5 - Enhanced parameter validation schema
     *
     * @since 4.1.5
     * @return array Endpoint arguments
     */
    private function get_license_resolve_args() {
        return array(
            'license_key' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'License key to resolve (format: VD-[PROVIDER]-[YEAR]-[CODE])',
                'minLength' => 10,
                'maxLength' => 50,
                'pattern' => '^VD-[A-Z0-9]+-[0-9]{4}-[A-Z0-9]+$',
                'validate_callback' => array($this, 'validate_license_key'),
                'sanitize_callback' => array($this, 'sanitize_license_key')
            ),
            'device_fingerprint' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'SHA256 device fingerprint (64 hex characters)',
                'minLength' => 64,
                'maxLength' => 64,
                'pattern' => '^[a-f0-9]{64}$',
                'validate_callback' => array($this, 'validate_device_fingerprint'),
                'sanitize_callback' => array($this, 'sanitize_device_fingerprint')
            ),
            'device_info' => array(
                'required' => false,
                'type' => 'object',
                'description' => 'Device information object với browser, OS, timezone, etc.',
                'validate_callback' => array($this, 'validate_device_info'),
                'sanitize_callback' => array($this, 'sanitize_device_info')
            ),
            'client_ip' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Client IP address (IPv4 hoặc IPv6)',
                'validate_callback' => array($this, 'validate_ip_address'),
                'sanitize_callback' => array($this, 'sanitize_ip_address')
            ),
            'request_id' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Request tracking ID (alphanumeric, underscore, dash)',
                'maxLength' => 100,
                'pattern' => '^[a-zA-Z0-9_-]+$',
                'validate_callback' => array($this, 'validate_request_id'),
                'sanitize_callback' => array($this, 'sanitize_request_id')
            )
        );
    }

    /**
     * Get cookie resolve endpoint arguments
     * Step 4.1.5 - Enhanced parameter validation schema
     *
     * @since 4.1.5
     * @return array Endpoint arguments
     */
    private function get_cookie_resolve_args() {
        return array(
            'license_key' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'License key to resolve (format: VD-[PROVIDER]-[YEAR]-[CODE])',
                'minLength' => 10,
                'maxLength' => 50,
                'pattern' => '^VD-[A-Z0-9]+-[0-9]{4}-[A-Z0-9]+$',
                'validate_callback' => array($this, 'validate_license_key'),
                'sanitize_callback' => array($this, 'sanitize_license_key')
            ),
            'device_fingerprint' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'SHA256 device fingerprint (64 hex characters)',
                'minLength' => 64,
                'maxLength' => 64,
                'pattern' => '^[a-f0-9]{64}$',
                'validate_callback' => array($this, 'validate_device_fingerprint'),
                'sanitize_callback' => array($this, 'sanitize_device_fingerprint')
            )
        );
    }

    /**
     * Get device status endpoint arguments
     * Step 4.1.5 - Enhanced parameter validation schema
     *
     * @since 4.1.5
     * @return array Endpoint arguments
     */
    private function get_device_status_args() {
        return array(
            'license_key' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'License key (format: VD-[PROVIDER]-[YEAR]-[CODE])',
                'minLength' => 10,
                'maxLength' => 50,
                'pattern' => '^VD-[A-Z0-9]+-[0-9]{4}-[A-Z0-9]+$',
                'validate_callback' => array($this, 'validate_license_key'),
                'sanitize_callback' => array($this, 'sanitize_license_key')
            ),
            'device_fingerprint' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'SHA256 device fingerprint (64 hex characters)',
                'minLength' => 64,
                'maxLength' => 64,
                'pattern' => '^[a-f0-9]{64}$',
                'validate_callback' => array($this, 'validate_device_fingerprint'),
                'sanitize_callback' => array($this, 'sanitize_device_fingerprint')
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
     * Validate license key format
     * Step 4.1.5 - License key validation
     *
     * @since 4.1.5
     * @param string $value License key value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_license_key($value, $request, $param) {
        if (!is_string($value)) {
            return new WP_Error('invalid_license_key', 'License key must be a string', array('status' => 400));
        }

        $value = trim($value);

        // Check length
        if (strlen($value) < 10 || strlen($value) > 50) {
            return new WP_Error('invalid_license_key_length', 'License key must be between 10-50 characters', array('status' => 400));
        }

        // Check format: VD-[PROVIDER]-[YEAR]-[CODE]
        if (!preg_match('/^VD-[A-Z0-9]+-[0-9]{4}-[A-Z0-9]+$/', $value)) {
            return new WP_Error('invalid_license_key_format', 'License key format invalid. Expected: VD-[PROVIDER]-[YEAR]-[CODE]', array('status' => 400));
        }

        // Additional security checks
        if (preg_match('/[<>"\']/', $value)) {
            return new WP_Error('invalid_license_key_chars', 'License key contains invalid characters', array('status' => 400));
        }

        return true;
    }

    /**
     * Sanitize license key
     * Step 4.1.5 - License key sanitization
     *
     * @since 4.1.5
     * @param string $value License key value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return string Sanitized license key
     */
    public function sanitize_license_key($value, $request, $param) {
        if (!is_string($value)) {
            return '';
        }

        // Remove whitespace and convert to uppercase
        $value = strtoupper(trim($value));

        // Remove any non-alphanumeric characters except dash
        $value = preg_replace('/[^A-Z0-9-]/', '', $value);

        return $value;
    }

    /**
     * Validate device fingerprint
     * Step 4.1.5 - Device fingerprint validation
     *
     * @since 4.1.5
     * @param string $value Device fingerprint value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_device_fingerprint($value, $request, $param) {
        if (!is_string($value)) {
            return new WP_Error('invalid_device_fingerprint', 'Device fingerprint must be a string', array('status' => 400));
        }

        $value = trim($value);

        // Must be exactly 64 characters
        if (strlen($value) !== 64) {
            return new WP_Error('invalid_device_fingerprint_length', 'Device fingerprint must be exactly 64 characters', array('status' => 400));
        }

        // Must be valid hex string
        if (!preg_match('/^[a-f0-9]{64}$/', $value)) {
            return new WP_Error('invalid_device_fingerprint_format', 'Device fingerprint must be 64 lowercase hex characters (SHA256)', array('status' => 400));
        }

        return true;
    }

    /**
     * Sanitize device fingerprint
     * Step 4.1.5 - Device fingerprint sanitization
     *
     * @since 4.1.5
     * @param string $value Device fingerprint value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return string Sanitized device fingerprint
     */
    public function sanitize_device_fingerprint($value, $request, $param) {
        if (!is_string($value)) {
            return '';
        }

        // Remove whitespace and convert to lowercase
        $value = strtolower(trim($value));

        // Keep only hex characters
        $value = preg_replace('/[^a-f0-9]/', '', $value);

        // Ensure exactly 64 characters
        if (strlen($value) > 64) {
            $value = substr($value, 0, 64);
        }

        return $value;
    }

    /**
     * Validate device info object
     * Step 4.1.5 - Device info validation
     *
     * @since 4.1.5
     * @param mixed $value Device info value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_device_info($value, $request, $param) {
        if ($value === null || $value === '') {
            return true; // Optional parameter
        }

        if (!is_array($value) && !is_object($value)) {
            return new WP_Error('invalid_device_info', 'Device info must be an object/array', array('status' => 400));
        }

        // Convert to array for validation
        $device_info = (array) $value;

        // Validate specific fields if present
        $allowed_fields = array(
            'browser', 'browser_version', 'os', 'os_version',
            'screen_resolution', 'timezone', 'language',
            'user_agent', 'ip', 'country'
        );

        foreach ($device_info as $key => $val) {
            // Check allowed fields
            if (!in_array($key, $allowed_fields)) {
                return new WP_Error('invalid_device_info_field', "Unknown device info field: {$key}", array('status' => 400));
            }

            // Basic sanitization check
            if (is_string($val) && (strlen($val) > 500 || preg_match('/[<>"]/', $val))) {
                return new WP_Error('invalid_device_info_value', "Invalid value for device info field: {$key}", array('status' => 400));
            }
        }

        return true;
    }

    /**
     * Sanitize device info object
     * Step 4.1.5 - Device info sanitization
     *
     * @since 4.1.5
     * @param mixed $value Device info value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return array Sanitized device info
     */
    public function sanitize_device_info($value, $request, $param) {
        if ($value === null || $value === '') {
            return array();
        }

        if (!is_array($value) && !is_object($value)) {
            return array();
        }

        $device_info = (array) $value;
        $sanitized = array();

        $allowed_fields = array(
            'browser', 'browser_version', 'os', 'os_version',
            'screen_resolution', 'timezone', 'language',
            'user_agent', 'ip', 'country'
        );

        foreach ($device_info as $key => $val) {
            if (in_array($key, $allowed_fields) && is_string($val)) {
                // Sanitize string values
                $sanitized[$key] = sanitize_text_field(substr($val, 0, 500));
            }
        }

        return $sanitized;
    }

    /**
     * Validate IP address
     * Step 4.1.5 - IP address validation
     *
     * @since 4.1.5
     * @param string $value IP address value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_ip_address($value, $request, $param) {
        if ($value === null || $value === '') {
            return true; // Optional parameter
        }

        if (!is_string($value)) {
            return new WP_Error('invalid_ip', 'IP address must be a string', array('status' => 400));
        }

        $value = trim($value);

        // Validate IPv4 or IPv6
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            return new WP_Error('invalid_ip_format', 'Invalid IP address format', array('status' => 400));
        }

        return true;
    }

    /**
     * Sanitize IP address
     * Step 4.1.5 - IP address sanitization
     *
     * @since 4.1.5
     * @param string $value IP address value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return string Sanitized IP address
     */
    public function sanitize_ip_address($value, $request, $param) {
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);

        // Basic IP sanitization
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }

        return '';
    }

    /**
     * Validate request ID
     * Step 4.1.5 - Request ID validation
     *
     * @since 4.1.5
     * @param string $value Request ID value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_request_id($value, $request, $param) {
        if ($value === null || $value === '') {
            return true; // Optional parameter
        }

        if (!is_string($value)) {
            return new WP_Error('invalid_request_id', 'Request ID must be a string', array('status' => 400));
        }

        $value = trim($value);

        if (strlen($value) > 100) {
            return new WP_Error('invalid_request_id_length', 'Request ID must be 100 characters or less', array('status' => 400));
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            return new WP_Error('invalid_request_id_format', 'Request ID can only contain alphanumeric characters, underscore, and dash', array('status' => 400));
        }

        return true;
    }

    /**
     * Sanitize request ID
     * Step 4.1.5 - Request ID sanitization
     *
     * @since 4.1.5
     * @param string $value Request ID value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return string Sanitized request ID
     */
    public function sanitize_request_id($value, $request, $param) {
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);

        // Keep only alphanumeric, underscore, and dash
        $value = preg_replace('/[^a-zA-Z0-9_-]/', '', $value);

        // Limit length
        if (strlen($value) > 100) {
            $value = substr($value, 0, 100);
        }

        return $value;
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
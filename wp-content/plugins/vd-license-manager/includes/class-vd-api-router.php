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

        // Step 4.1.6 - Security status endpoint
        register_rest_route($this->namespace, '/security-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_security_status'),
            'permission_callback' => '__return_true'
        ));

        // Step 4.1.8 - Error handling statistics endpoint
        register_rest_route($this->namespace, '/error-statistics', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_error_statistics'),
            'permission_callback' => '__return_true'
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
            // Step 4.1.6 - Security validation
            $security_check = $this->validate_request_security($request);
            if (is_wp_error($security_check)) {
                return $security_check;
            }

            // Step 4.1.7 - Enhanced placeholder implementation with proper API format
            $response_data = array(
                'success' => true,
                'data' => array(
                    'license' => array(
                        'id' => 12345,
                        'license_key' => $request->get_param('license_key') ?: 'VD-H10-2024-PLACEHOLDER',
                        'product_id' => 8210,
                        'status' => 'active',
                        'expires_at' => gmdate('Y-m-d\TH:i:sP', strtotime('+1 year')),
                        'max_devices' => 3,
                        'device_count' => 1
                    ),
                    'provider' => array(
                        'id' => 7,
                        'account_name' => 'placeholder-h10-account',
                        'provider' => 'helium10',
                        'share_type' => 'credentials_2fa'
                    ),
                    'content' => array(
                        'Email đăng nhập' => 'placeholder@helium10.com',
                        'Mật khẩu' => '[PLACEHOLDER_PASSWORD]',
                        'Mã 2FA' => '[PLACEHOLDER_2FA]',
                        'Cookie đăng nhập' => 'session_id=placeholder123; auth_token=placeholder456',
                        'Ngày hết hạn tài khoản' => gmdate('Y-m-d', strtotime('+1 year')),
                        'Trạng thái' => 'active',
                        'Ghi chú' => 'Placeholder implementation - Step 4.1.7'
                    ),
                    'device' => array(
                        'device_fingerprint' => $request->get_param('device_fingerprint') ?: 'placeholder_device_fingerprint',
                        'status' => 'approved',
                        'auto_approved' => true,
                        'first_seen' => current_time('c'),
                        'approved_at' => current_time('c'),
                        'last_access' => current_time('c'),
                        'access_count' => 1
                    ),
                    'rate_limit' => array(
                        'requests_remaining' => 49,
                        'window_reset' => gmdate('Y-m-d\TH:i:sP', strtotime('+1 hour')),
                        'retry_after' => null
                    ),
                    'meta' => array(
                        'content_version' => 1,
                        'response_time_ms' => 145,
                        'cached' => false,
                        'request_id' => $request->get_param('request_id') ?: 'req_' . uniqid(),
                        'step' => '4.1.7',
                        'implementation' => 'placeholder'
                    )
                ),
                'timestamp' => current_time('c')
            );

            return rest_ensure_response($response_data);
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => array(
                    'code' => 'RESOLVE_INFO_ERROR',
                    'message' => 'License resolve info request failed: ' . $e->getMessage(),
                    'details' => array(
                        'error_type' => 'processing_error',
                        'step' => '4.1.7',
                        'implementation' => 'placeholder'
                    ),
                    'retry_after' => null,
                    'request_id' => 'req_' . uniqid()
                ),
                'timestamp' => current_time('c')
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
            // Step 4.1.6 - Security validation
            $security_check = $this->validate_request_security($request);
            if (is_wp_error($security_check)) {
                return $security_check;
            }

            // Step 4.1.7 - Enhanced placeholder implementation for cookie endpoint
            $response_data = array(
                'success' => true,
                'data' => array(
                    'license' => array(
                        'id' => 12346,
                        'license_key' => $request->get_param('license_key') ?: 'VD-MJ-2024-PLACEHOLDER',
                        'product_id' => 8211,
                        'status' => 'active',
                        'expires_at' => gmdate('Y-m-d\TH:i:sP', strtotime('+1 year')),
                        'max_devices' => 5,
                        'device_count' => 2
                    ),
                    'provider' => array(
                        'id' => 8,
                        'account_name' => 'placeholder-mj-account',
                        'provider' => 'midjourney',
                        'share_type' => 'cookie_session'
                    ),
                    'content' => array(
                        'Discord Token' => '[PLACEHOLDER_DISCORD_TOKEN]',
                        'Session Cookie' => '__Secure-next-auth.session-token=placeholder_session_token_123456789',
                        'User Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
                        'Channel ID' => '1234567890123456789',
                        'Server ID' => '9876543210987654321',
                        'Subscription Status' => 'active',
                        'Trạng thái' => 'ready',
                        'Ghi chú' => 'Placeholder cookie implementation - Step 4.1.7'
                    ),
                    'device' => array(
                        'device_fingerprint' => $request->get_param('device_fingerprint') ?: 'placeholder_cookie_device_fp',
                        'status' => 'approved',
                        'auto_approved' => true,
                        'first_seen' => current_time('c'),
                        'approved_at' => current_time('c'),
                        'last_access' => current_time('c'),
                        'access_count' => 5
                    ),
                    'rate_limit' => array(
                        'requests_remaining' => 47,
                        'window_reset' => gmdate('Y-m-d\TH:i:sP', strtotime('+1 hour')),
                        'retry_after' => null
                    ),
                    'meta' => array(
                        'content_version' => 2,
                        'response_time_ms' => 132,
                        'cached' => false,
                        'request_id' => $request->get_param('request_id') ?: 'req_' . uniqid(),
                        'step' => '4.1.7',
                        'implementation' => 'placeholder',
                        'endpoint_type' => 'cookie_resolve'
                    )
                ),
                'timestamp' => current_time('c')
            );

            return rest_ensure_response($response_data);
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => array(
                    'code' => 'RESOLVE_COOKIE_ERROR',
                    'message' => 'License resolve cookie request failed: ' . $e->getMessage(),
                    'details' => array(
                        'error_type' => 'processing_error',
                        'step' => '4.1.7',
                        'implementation' => 'placeholder'
                    ),
                    'retry_after' => null,
                    'request_id' => 'req_' . uniqid()
                ),
                'timestamp' => current_time('c')
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
            // Step 4.1.6 - Security validation
            $security_check = $this->validate_request_security($request);
            if (is_wp_error($security_check)) {
                return $security_check;
            }

            // Step 4.1.7 - Enhanced placeholder implementation for device status
            $response_data = array(
                'success' => true,
                'data' => array(
                    'license_key' => $request->get_param('license_key') ?: 'VD-FP-2024-PLACEHOLDER',
                    'max_devices' => 3,
                    'devices' => array(
                        array(
                            'device_fingerprint' => $request->get_param('device_fingerprint') ?: 'placeholder_device_fp_1',
                            'device_info' => array(
                                'browser' => 'Chrome',
                                'os' => 'Windows',
                                'last_ip' => '127.0.0.1',
                                'country' => 'VN'
                            ),
                            'status' => 'approved',
                            'first_seen' => gmdate('Y-m-d\TH:i:sP', strtotime('-7 days')),
                            'last_access' => current_time('c'),
                            'access_count' => 25,
                            'auto_approved' => true
                        ),
                        array(
                            'device_fingerprint' => 'placeholder_device_fp_2',
                            'device_info' => array(
                                'browser' => 'Firefox',
                                'os' => 'macOS',
                                'last_ip' => '127.0.0.2',
                                'country' => 'VN'
                            ),
                            'status' => 'pending',
                            'first_seen' => gmdate('Y-m-d\TH:i:sP', strtotime('-1 day')),
                            'last_access' => gmdate('Y-m-d\TH:i:sP', strtotime('-1 day')),
                            'access_count' => 3,
                            'auto_approved' => false
                        )
                    ),
                    'current_device' => array(
                        'device_fingerprint' => $request->get_param('device_fingerprint') ?: 'placeholder_device_fp_1',
                        'status' => 'approved',
                        'is_current' => true,
                        'can_access' => true,
                        'last_verification' => current_time('c')
                    ),
                    'rate_limit' => array(
                        'requests_remaining' => 48,
                        'window_reset' => gmdate('Y-m-d\TH:i:sP', strtotime('+1 hour')),
                        'retry_after' => null
                    ),
                    'meta' => array(
                        'response_time_ms' => 98,
                        'cached' => false,
                        'request_id' => $request->get_param('request_id') ?: 'req_' . uniqid(),
                        'step' => '4.1.7',
                        'implementation' => 'placeholder',
                        'endpoint_type' => 'device_status'
                    )
                ),
                'timestamp' => current_time('c')
            );

            return rest_ensure_response($response_data);
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => array(
                    'code' => 'DEVICE_STATUS_ERROR',
                    'message' => 'Device status check failed: ' . $e->getMessage(),
                    'details' => array(
                        'error_type' => 'processing_error',
                        'step' => '4.1.7',
                        'implementation' => 'placeholder'
                    ),
                    'retry_after' => null,
                    'request_id' => 'req_' . uniqid()
                ),
                'timestamp' => current_time('c')
            ));
        }
    }

    /**
     * Handle security status endpoint
     * Step 4.1.6 - Security status information endpoint
     *
     * @since 4.1.6
     * @param WP_REST_Request $request The REST request
     * @return WP_REST_Response Response object
     */
    public function handle_security_status($request) {
        try {
            $security_status = $this->get_security_status();

            $response_data = array(
                'success' => true,
                'message' => 'Security status retrieved successfully',
                'data' => $security_status,
                'timestamp' => current_time('c'),
                'step' => '4.1.6'
            );

            return rest_ensure_response($response_data);
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => 'Security status retrieval failed',
                'message' => $e->getMessage(),
                'timestamp' => current_time('c'),
                'step' => '4.1.6'
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
     * Validate request security
     * Step 4.1.6 - Security validation for API requests
     *
     * @since 4.1.6
     * @param WP_REST_Request $request The REST request object
     * @return bool|WP_Error True if valid, WP_Error if security check fails
     */
    private function validate_request_security($request) {
        // If security manager not available, allow request (fallback)
        if (!$this->security_manager) {
            return true;
        }

        // Get request headers for authentication
        $headers = $request->get_headers();

        // Check for authentication methods in order of preference

        // 1. Bearer token authentication
        if (isset($headers['authorization'][0])) {
            $auth_header = $headers['authorization'][0];
            if (strpos($auth_header, 'Bearer ') === 0) {
                $token = substr($auth_header, 7);
                $result = $this->security_manager->validate_bearer_token($token);
                if ($result === true) {
                    return true;
                }
            }
        }

        // 2. API Key authentication
        if (isset($headers['x_api_key'][0])) {
            $api_key = $headers['x_api_key'][0];
            $result = $this->security_manager->validate_api_key($api_key);
            if ($result === true) {
                return true;
            }
        }

        // 3. WordPress nonce validation for internal requests
        if ($request->get_param('_wpnonce')) {
            $nonce = $request->get_param('_wpnonce');
            $result = $this->security_manager->validate_wp_nonce($nonce);
            if ($result === true) {
                return true;
            }
        }

        // 4. HMAC signature validation
        if (isset($headers['x_signature'][0]) && $request->get_body()) {
            $signature = $headers['x_signature'][0];
            $payload = $request->get_body();
            $result = $this->security_manager->validate_hmac_signature($signature, $payload);
            if ($result === true) {
                return true;
            }
        }

        // No valid authentication found
        return new WP_Error(
            'authentication_required',
            'Authentication required. Provide Bearer token, API key, nonce, or HMAC signature.',
            array('status' => 401)
        );
    }

    /**
     * Get security validation status
     * Step 4.1.6 - Security status reporting
     *
     * @since 4.1.6
     * @return array Security status information
     */
    public function get_security_status() {
        $status = array(
            'security_manager_available' => !is_null($this->security_manager),
            'request_validator_available' => !is_null($this->request_validator),
            'supported_auth_methods' => array(),
            'step' => '4.1.6'
        );

        if ($this->security_manager) {
            $status['supported_auth_methods'] = $this->security_manager->get_authentication_methods();
            $status['security_manager_status'] = $this->security_manager->get_status();
        }

        return $status;
    }

    /**
     * Create standardized API error response
     * Step 4.1.8 - Error handling infrastructure
     *
     * @since 4.1.8
     * @param string $error_code Error code identifier
     * @param string $message Human-readable error message
     * @param array $details Additional error context
     * @param int $http_status HTTP status code (default: 400)
     * @param int|null $retry_after Retry after seconds for rate limiting
     * @return WP_Error Standardized error response
     */
    private function create_api_error($error_code, $message, $details = [], $http_status = 400, $retry_after = null) {
        $error_data = array(
            'code' => $error_code,
            'message' => $message,
            'details' => array_merge([
                'step' => '4.1.8',
                'timestamp' => current_time('c'),
                'http_status' => $http_status
            ], $details),
            'retry_after' => $retry_after,
            'request_id' => 'req_' . uniqid()
        );

        // Log error if debug enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[VD API Error] ' . $error_code . ': ' . $message . ' | Details: ' . json_encode($details));
        }

        return new WP_Error($error_code, $message, array(
            'status' => $http_status,
            'error_data' => $error_data
        ));
    }

    /**
     * Format error response for REST API
     * Step 4.1.8 - Standardized error response formatting
     *
     * @since 4.1.8
     * @param WP_Error|string $error Error object or error code
     * @param string $message Optional error message if $error is string
     * @param array $details Optional error details
     * @param int $http_status HTTP status code
     * @return WP_REST_Response Formatted error response
     */
    private function format_error_response($error, $message = '', $details = [], $http_status = 400) {
        if (is_wp_error($error)) {
            $error_data = $error->get_error_data();
            $response_data = array(
                'success' => false,
                'error' => isset($error_data['error_data']) ? $error_data['error_data'] : array(
                    'code' => $error->get_error_code(),
                    'message' => $error->get_error_message(),
                    'details' => $details,
                    'retry_after' => null,
                    'request_id' => 'req_' . uniqid()
                ),
                'timestamp' => current_time('c')
            );
            $status = isset($error_data['status']) ? $error_data['status'] : $http_status;
        } else {
            $response_data = array(
                'success' => false,
                'error' => array(
                    'code' => $error,
                    'message' => $message,
                    'details' => array_merge([
                        'step' => '4.1.8',
                        'http_status' => $http_status
                    ], $details),
                    'retry_after' => null,
                    'request_id' => 'req_' . uniqid()
                ),
                'timestamp' => current_time('c')
            );
            $status = $http_status;
        }

        return rest_ensure_response($response_data)->set_status($status);
    }

    /**
     * Handle validation errors
     * Step 4.1.8 - Validation error handling
     *
     * @since 4.1.8
     * @param array $validation_errors Array of validation errors
     * @param string $context Validation context (e.g., 'license_key', 'device_fingerprint')
     * @return WP_Error Validation error response
     */
    private function handle_validation_errors($validation_errors, $context = 'request') {
        $error_details = array(
            'validation_context' => $context,
            'validation_errors' => $validation_errors,
            'error_count' => count($validation_errors)
        );

        return $this->create_api_error(
            'VALIDATION_ERROR',
            'Request validation failed. Please check your input parameters.',
            $error_details,
            400
        );
    }

    /**
     * Handle rate limiting errors
     * Step 4.1.8 - Rate limiting error handling
     *
     * @since 4.1.8
     * @param int $requests_made Number of requests made
     * @param int $requests_limit Request limit
     * @param int $window_reset_seconds Seconds until rate limit window resets
     * @return WP_Error Rate limit error response
     */
    private function handle_rate_limit_error($requests_made, $requests_limit, $window_reset_seconds) {
        $error_details = array(
            'requests_made' => $requests_made,
            'requests_limit' => $requests_limit,
            'window_reset_seconds' => $window_reset_seconds,
            'reset_time' => gmdate('Y-m-d\TH:i:sP', time() + $window_reset_seconds)
        );

        return $this->create_api_error(
            'RATE_LIMITED',
            "Rate limit exceeded. You've made {$requests_made}/{$requests_limit} requests. Try again in {$window_reset_seconds} seconds.",
            $error_details,
            429,
            $window_reset_seconds
        );
    }

    /**
     * Handle business logic errors
     * Step 4.1.8 - Business logic error handling
     *
     * @since 4.1.8
     * @param string $error_type Business error type
     * @param string $message Error message
     * @param array $business_context Business-specific context
     * @return WP_Error Business logic error response
     */
    private function handle_business_error($error_type, $message, $business_context = []) {
        $error_codes_map = array(
            'invalid_license' => array('code' => 'INVALID_LICENSE', 'status' => 404),
            'license_expired' => array('code' => 'LICENSE_EXPIRED', 'status' => 403),
            'device_limit_exceeded' => array('code' => 'DEVICE_LIMIT_EXCEEDED', 'status' => 403),
            'device_not_approved' => array('code' => 'DEVICE_NOT_APPROVED', 'status' => 403),
            'provider_unavailable' => array('code' => 'PROVIDER_UNAVAILABLE', 'status' => 503),
            'database_error' => array('code' => 'DATABASE_ERROR', 'status' => 500)
        );

        $error_info = isset($error_codes_map[$error_type]) ? $error_codes_map[$error_type] :
                      array('code' => 'BUSINESS_ERROR', 'status' => 400);

        $error_details = array_merge([
            'business_error_type' => $error_type,
            'business_context' => $business_context
        ], $business_context);

        return $this->create_api_error(
            $error_info['code'],
            $message,
            $error_details,
            $error_info['status']
        );
    }

    /**
     * Get error statistics
     * Step 4.1.8 - Error monitoring and statistics
     *
     * @since 4.1.8
     * @return array Error statistics
     */
    public function get_error_statistics() {
        // This would integrate with actual error logging/monitoring
        // For now, return placeholder statistics
        return array(
            'error_handling_version' => '4.1.8',
            'supported_error_types' => [
                'VALIDATION_ERROR',
                'RATE_LIMITED',
                'INVALID_LICENSE',
                'LICENSE_EXPIRED',
                'DEVICE_LIMIT_EXCEEDED',
                'DEVICE_NOT_APPROVED',
                'PROVIDER_UNAVAILABLE',
                'DATABASE_ERROR',
                'AUTHENTICATION_REQUIRED'
            ],
            'http_status_codes' => [400, 401, 403, 404, 429, 500, 503],
            'error_logging_enabled' => defined('WP_DEBUG') && WP_DEBUG,
            'last_updated' => current_time('c')
        );
    }

    /**
     * Handle error statistics endpoint
     * Step 4.1.8 - Error statistics endpoint handler
     *
     * @since 4.1.8
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Error statistics response
     */
    public function handle_error_statistics($request) {
        try {
            $statistics = $this->get_error_statistics();

            return $this->format_error_response(array(
                'success' => true,
                'data' => array(
                    'error_infrastructure' => $statistics,
                    'error_handling_methods' => [
                        'create_api_error',
                        'format_error_response',
                        'handle_validation_errors',
                        'handle_rate_limit_error',
                        'handle_business_error'
                    ],
                    'step_info' => array(
                        'current_step' => '4.1.8',
                        'feature' => 'Error Handling Infrastructure',
                        'status' => 'implemented'
                    )
                ),
                'timestamp' => current_time('c')
            ));
        } catch (Exception $e) {
            return $this->create_api_error(
                'ERROR_STATISTICS_ERROR',
                'Failed to retrieve error statistics: ' . $e->getMessage(),
                array('exception' => get_class($e)),
                500
            );
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
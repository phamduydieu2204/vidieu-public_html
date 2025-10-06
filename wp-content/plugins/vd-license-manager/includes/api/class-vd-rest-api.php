<?php
/**
 * REST API base class for VD License Manager
 *
 * Handles registration and validation for all API endpoints
 *
 * @package VD_License_Manager
 * @subpackage API
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD REST API class
 *
 * Base REST API functionality and route registration
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_REST_API {

    /**
     * Constructor
     *
     * Hooks into WordPress REST API initialization
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));

        if (VD_CORS_ENABLED) {
            add_action('rest_pre_serve_request', array($this, 'add_cors_headers'));
        }
    }

    /**
     * Register all REST API routes
     *
     * Registers 4 main endpoints for the VD License Manager API
     *
     * @since 1.0.0
     */
    public function register_routes() {
        $namespace = 'vd/v1';

        // Device verification endpoint
        register_rest_route($namespace, '/device/verify', array(
            'methods' => 'POST',
            'callback' => array('VD_API_Device_Verify', 'handle'),
            'permission_callback' => array($this, 'permission_callback'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_license_key'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'device_fp' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_fingerprint'),
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Account info retrieval endpoint
        register_rest_route($namespace, '/account/info', array(
            'methods' => 'GET',
            'callback' => array('VD_API_Account_Info', 'handle'),
            'permission_callback' => array($this, 'permission_callback'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_license_key'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'device_fp' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_fingerprint'),
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Access history endpoint
        register_rest_route($namespace, '/history', array(
            'methods' => 'GET',
            'callback' => array('VD_API_History', 'handle'),
            'permission_callback' => array($this, 'permission_callback'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_license_key'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'limit' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Device rotation endpoint
        register_rest_route($namespace, '/device/rotate', array(
            'methods' => 'POST',
            'callback' => array('VD_API_Device_Rotate', 'handle'),
            'permission_callback' => array($this, 'permission_callback'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_license_key'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'old_device_fp' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_fingerprint'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'new_device_fp' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_fingerprint'),
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }

    /**
     * Permission callback for API endpoints
     *
     * Validates basic permissions and rate limiting
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if allowed, WP_Error if denied
     */
    public function permission_callback($request) {
        // Basic IP rate limiting for API calls
        $client_ip = VD_Security::get_client_ip();
        if (!$client_ip) {
            return new WP_Error('invalid_ip', 'Unable to determine client IP', array('status' => 403));
        }

        // Check for basic license key parameter
        $license_key = $request->get_param('license_key');
        if (empty($license_key)) {
            return new WP_Error('missing_license', 'License key is required', array('status' => 400));
        }

        // Apply general API rate limiting (could be different from license-specific)
        $rate_key = 'vd_api_rate_' . md5($client_ip);
        $current_count = (int) get_transient($rate_key);

        if ($current_count >= 60) { // 60 requests per minute per IP
            return new WP_Error('rate_limit', 'API rate limit exceeded', array('status' => 429));
        }

        set_transient($rate_key, $current_count + 1, 60);
        return true;
    }

    /**
     * Validate license key parameter
     *
     * @since 1.0.0
     * @param string $value License key value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_license_key($value, $request, $param) {
        $sanitized = VD_Security::sanitize_license_key($value);

        if (!$sanitized) {
            return new WP_Error(
                'invalid_license_format',
                'Invalid license key format',
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Validate device fingerprint parameter
     *
     * @since 1.0.0
     * @param string $value Fingerprint value
     * @param WP_REST_Request $request Request object
     * @param string $param Parameter name
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_fingerprint($value, $request, $param) {
        if (!VD_Security::validate_fingerprint($value)) {
            return new WP_Error(
                'invalid_fingerprint',
                'Invalid device fingerprint format. Expected 64-character hex string',
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Add CORS headers to API responses
     *
     * @since 1.0.0
     * @param bool $served Whether the request has already been served
     * @return bool
     */
    public function add_cors_headers($served) {
        // Only add CORS headers for our API endpoints
        if (strpos($_SERVER['REQUEST_URI'], '/wp-json/vd/') === false) {
            return $served;
        }

        $origin = get_http_origin();
        $allowed_origins = VD_CORS_ORIGINS;

        if ($allowed_origins === '*') {
            header('Access-Control-Allow-Origin: *');
        } elseif ($origin && in_array($origin, explode(',', $allowed_origins))) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 3600');

        // Handle preflight OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit;
        }

        return $served;
    }
}
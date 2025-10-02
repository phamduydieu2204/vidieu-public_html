<?php

namespace VD\LicenseManager\API;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License API Framework
 *
 * Step 4.1: REST API Framework Module
 * Modular REST API framework following PSR-4 standards
 * Extracted from monolithic API router for better organization
 *
 * @package VD\LicenseManager\API
 * @since 1.6.0
 * @author VD Team
 */
class VD_License_API_Framework {

    /**
     * Singleton instance
     *
     * @var VD_License_API_Framework|null
     */
    private static $instance = null;

    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'vd-license/v1';

    /**
     * API version
     *
     * @var string
     */
    private $version = '1.0.0';

    /**
     * Registered routes
     *
     * @var array
     */
    private $routes = array();

    /**
     * Middleware stack
     *
     * @var array
     */
    private $middleware = array();

    /**
     * Framework initialization status
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Route validation rules
     *
     * @var array
     */
    private $validation_rules = array();

    /**
     * API statistics
     *
     * @var array
     */
    private $stats = array(
        'total_routes' => 0,
        'registered_routes' => 0,
        'middleware_count' => 0,
        'init_time' => 0,
        'memory_usage' => 0
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_framework();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_API_Framework
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize API framework
     *
     * @return void
     */
    private function init_framework() {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        // Register WordPress REST API hooks
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'setup_middleware'));

        // Initialize default validation rules
        $this->init_validation_rules();

        // Set default middleware
        $this->init_default_middleware();

        $this->initialized = true;

        // Update statistics
        $this->stats['init_time'] = (microtime(true) - $start_time) * 1000;
        $this->stats['memory_usage'] = memory_get_usage() - $start_memory;
    }

    /**
     * Initialize validation rules
     *
     * @return void
     */
    private function init_validation_rules() {
        $this->validation_rules = array(
            'license_key' => array(
                'type' => 'string',
                'required' => true,
                'min_length' => 10,
                'max_length' => 255,
                'pattern' => '/^[A-Za-z0-9\-_]+$/'
            ),
            'device_id' => array(
                'type' => 'string',
                'required' => false,
                'min_length' => 5,
                'max_length' => 100,
                'pattern' => '/^[A-Za-z0-9\-_]+$/'
            ),
            'status' => array(
                'type' => 'string',
                'required' => false,
                'allowed_values' => array('active', 'inactive', 'expired', 'pending', 'suspended')
            ),
            'limit' => array(
                'type' => 'integer',
                'required' => false,
                'min' => 1,
                'max' => 100,
                'default' => 20
            ),
            'offset' => array(
                'type' => 'integer',
                'required' => false,
                'min' => 0,
                'default' => 0
            )
        );
    }

    /**
     * Initialize default middleware
     *
     * @return void
     */
    private function init_default_middleware() {
        // Authentication middleware
        $this->add_middleware('authentication', array($this, 'authenticate_request'), 10);

        // Rate limiting middleware
        $this->add_middleware('rate_limiting', array($this, 'check_rate_limit'), 20);

        // Input validation middleware
        $this->add_middleware('validation', array($this, 'validate_request'), 30);

        // Security headers middleware
        $this->add_middleware('security_headers', array($this, 'add_security_headers'), 40);
    }

    /**
     * Register a new API route
     *
     * @param string $route Route path
     * @param array $args Route arguments
     * @return bool True on success, false on failure
     */
    public function register_route($route, $args = array()) {
        if (empty($route) || !is_array($args)) {
            return false;
        }

        $defaults = array(
            'methods' => 'GET',
            'callback' => null,
            'permission_callback' => '__return_true',
            'args' => array(),
            'validate_callback' => null
        );

        $route_config = wp_parse_args($args, $defaults);

        // Add validation callback if not provided
        if (null === $route_config['validate_callback']) {
            $route_config['validate_callback'] = array($this, 'validate_route_params');
        }

        $this->routes[$route] = $route_config;
        $this->stats['total_routes']++;

        return true;
    }

    /**
     * Register routes with WordPress REST API
     *
     * @return void
     */
    public function register_routes() {
        if (empty($this->routes)) {
            $this->register_default_routes();
        }

        foreach ($this->routes as $route => $config) {
            register_rest_route($this->namespace, $route, $config);
            $this->stats['registered_routes']++;
        }
    }

    /**
     * Register default API routes
     *
     * @return void
     */
    private function register_default_routes() {
        // Framework status endpoint
        $this->register_route('/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_framework_status'),
            'permission_callback' => '__return_true'
        ));

        // Framework info endpoint
        $this->register_route('/info', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_framework_info'),
            'permission_callback' => '__return_true'
        ));

        // License validation endpoint
        $this->register_route('/license/validate', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'validate_license_endpoint'),
            'permission_callback' => array($this, 'check_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_license_key_param')
                )
            )
        ));

        // License status endpoint
        $this->register_route('/license/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_license_status_endpoint'),
            'permission_callback' => array($this, 'check_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_license_key_param')
                )
            )
        ));

        // License list endpoint
        $this->register_route('/licenses', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_licenses_endpoint'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'limit' => array(
                    'default' => 20,
                    'validate_callback' => array($this, 'validate_limit_param')
                ),
                'offset' => array(
                    'default' => 0,
                    'validate_callback' => array($this, 'validate_offset_param')
                )
            )
        ));
    }

    /**
     * Add middleware to the stack
     *
     * @param string $name Middleware name
     * @param callable $callback Middleware callback
     * @param int $priority Priority (lower runs first)
     * @return bool True on success, false on failure
     */
    public function add_middleware($name, $callback, $priority = 50) {
        if (empty($name) || !is_callable($callback)) {
            return false;
        }

        $this->middleware[$name] = array(
            'callback' => $callback,
            'priority' => $priority
        );

        $this->stats['middleware_count']++;

        // Sort middleware by priority
        uasort($this->middleware, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return true;
    }

    /**
     * Setup middleware
     *
     * @return void
     */
    public function setup_middleware() {
        foreach ($this->middleware as $name => $config) {
            if (is_callable($config['callback'])) {
                add_filter('rest_pre_dispatch', $config['callback'], $config['priority'], 3);
            }
        }
    }

    /**
     * Authenticate API request
     *
     * @param mixed $result Pre-dispatch result
     * @param \WP_REST_Server $server REST server instance
     * @param \WP_REST_Request $request Request object
     * @return mixed
     */
    public function authenticate_request($result, $server, $request) {
        $route = $request->get_route();

        // Skip authentication for public endpoints
        if (in_array($route, array('/vd-license/v1/status', '/vd-license/v1/info'))) {
            return $result;
        }

        // Check for API key in headers
        $api_key = $request->get_header('X-VD-API-Key');
        if (empty($api_key)) {
            return new \WP_Error('missing_api_key', 'API key is required', array('status' => 401));
        }

        // Validate API key (implement your validation logic)
        if (!$this->validate_api_key($api_key)) {
            return new \WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
        }

        return $result;
    }

    /**
     * Check rate limit for request
     *
     * @param mixed $result Pre-dispatch result
     * @param \WP_REST_Server $server REST server instance
     * @param \WP_REST_Request $request Request object
     * @return mixed
     */
    public function check_rate_limit($result, $server, $request) {
        $client_ip = $this->get_client_ip();
        $rate_limit_key = 'vd_api_rate_limit_' . md5($client_ip);

        $current_count = get_transient($rate_limit_key);
        $limit = 100; // Requests per hour

        if (false === $current_count) {
            set_transient($rate_limit_key, 1, HOUR_IN_SECONDS);
        } else {
            if ($current_count >= $limit) {
                return new \WP_Error('rate_limit_exceeded', 'Rate limit exceeded', array('status' => 429));
            }
            set_transient($rate_limit_key, $current_count + 1, HOUR_IN_SECONDS);
        }

        return $result;
    }

    /**
     * Validate API request
     *
     * @param mixed $result Pre-dispatch result
     * @param \WP_REST_Server $server REST server instance
     * @param \WP_REST_Request $request Request object
     * @return mixed
     */
    public function validate_request($result, $server, $request) {
        $route = $request->get_route();
        $params = $request->get_params();

        // Validate based on route-specific rules
        $validation_errors = $this->validate_route_parameters($route, $params);

        if (!empty($validation_errors)) {
            return new \WP_Error('validation_failed', 'Validation failed', array(
                'status' => 400,
                'errors' => $validation_errors
            ));
        }

        return $result;
    }

    /**
     * Add security headers to response
     *
     * @param mixed $result Pre-dispatch result
     * @param \WP_REST_Server $server REST server instance
     * @param \WP_REST_Request $request Request object
     * @return mixed
     */
    public function add_security_headers($result, $server, $request) {
        // Add security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        return $result;
    }

    /**
     * Get framework status
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function get_framework_status($request) {
        return new \WP_REST_Response(array(
            'status' => 'active',
            'framework' => 'VD License API Framework',
            'version' => $this->version,
            'namespace' => $this->namespace,
            'timestamp' => current_time('timestamp'),
            'statistics' => $this->stats
        ), 200);
    }

    /**
     * Get framework information
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function get_framework_info($request) {
        return new \WP_REST_Response(array(
            'framework' => array(
                'name' => 'VD License API Framework',
                'version' => $this->version,
                'namespace' => $this->namespace,
                'initialized' => $this->initialized
            ),
            'routes' => array_keys($this->routes),
            'middleware' => array_keys($this->middleware),
            'validation_rules' => array_keys($this->validation_rules),
            'statistics' => $this->stats,
            'environment' => array(
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'rest_api_available' => function_exists('rest_get_server')
            )
        ), 200);
    }

    /**
     * Validate license endpoint
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function validate_license_endpoint($request) {
        $license_key = $request->get_param('license_key');

        // Validate license key (implement your logic here)
        $validation_result = $this->validate_license_key($license_key);

        if ($validation_result['valid']) {
            return new \WP_REST_Response($validation_result, 200);
        } else {
            return new \WP_REST_Response($validation_result, 400);
        }
    }

    /**
     * Get license status endpoint
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function get_license_status_endpoint($request) {
        $license_key = $request->get_param('license_key');

        // Get license status (implement your logic here)
        $status_data = $this->get_license_status_data($license_key);

        return new \WP_REST_Response($status_data, 200);
    }

    /**
     * Get licenses endpoint
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function get_licenses_endpoint($request) {
        $limit = $request->get_param('limit');
        $offset = $request->get_param('offset');

        // Get licenses list (implement your logic here)
        $licenses_data = $this->get_licenses_list($limit, $offset);

        return new \WP_REST_Response($licenses_data, 200);
    }

    /**
     * Validate route parameters
     *
     * @param string $route Route path
     * @param array $params Request parameters
     * @return array Validation errors
     */
    private function validate_route_parameters($route, $params) {
        $errors = array();

        foreach ($params as $param_name => $param_value) {
            if (isset($this->validation_rules[$param_name])) {
                $rule = $this->validation_rules[$param_name];
                $param_errors = $this->validate_parameter($param_name, $param_value, $rule);

                if (!empty($param_errors)) {
                    $errors[$param_name] = $param_errors;
                }
            }
        }

        return $errors;
    }

    /**
     * Validate individual parameter
     *
     * @param string $name Parameter name
     * @param mixed $value Parameter value
     * @param array $rule Validation rule
     * @return array Validation errors
     */
    private function validate_parameter($name, $value, $rule) {
        $errors = array();

        // Check required
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[] = "Parameter '{$name}' is required";
        }

        if (!empty($value)) {
            // Check type
            if (isset($rule['type'])) {
                $valid_type = $this->validate_parameter_type($value, $rule['type']);
                if (!$valid_type) {
                    $errors[] = "Parameter '{$name}' must be of type {$rule['type']}";
                }
            }

            // Check length for strings
            if (is_string($value)) {
                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[] = "Parameter '{$name}' must be at least {$rule['min_length']} characters";
                }
                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[] = "Parameter '{$name}' must not exceed {$rule['max_length']} characters";
                }
            }

            // Check numeric ranges
            if (is_numeric($value)) {
                if (isset($rule['min']) && $value < $rule['min']) {
                    $errors[] = "Parameter '{$name}' must be at least {$rule['min']}";
                }
                if (isset($rule['max']) && $value > $rule['max']) {
                    $errors[] = "Parameter '{$name}' must not exceed {$rule['max']}";
                }
            }

            // Check pattern
            if (isset($rule['pattern']) && is_string($value)) {
                if (!preg_match($rule['pattern'], $value)) {
                    $errors[] = "Parameter '{$name}' format is invalid";
                }
            }

            // Check allowed values
            if (isset($rule['allowed_values']) && !in_array($value, $rule['allowed_values'])) {
                $errors[] = "Parameter '{$name}' must be one of: " . implode(', ', $rule['allowed_values']);
            }
        }

        return $errors;
    }

    /**
     * Validate parameter type
     *
     * @param mixed $value Parameter value
     * @param string $expected_type Expected type
     * @return bool True if valid, false otherwise
     */
    private function validate_parameter_type($value, $expected_type) {
        switch ($expected_type) {
            case 'string':
                return is_string($value);
            case 'integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            case 'boolean':
                return is_bool($value) || in_array($value, array('true', 'false', '1', '0'), true);
            case 'array':
                return is_array($value);
            default:
                return true;
        }
    }

    /**
     * Validate route parameters callback
     *
     * @param array $params Request parameters
     * @param \WP_REST_Request $request Request object
     * @param string $key Parameter key
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    public function validate_route_params($params, $request, $key) {
        // Implementation for route parameter validation
        return true;
    }

    // Permission callbacks
    public function check_license_permission($request) {
        return current_user_can('read');
    }

    public function check_admin_permission($request) {
        return current_user_can('manage_options');
    }

    // Parameter validation callbacks
    public function validate_license_key_param($param, $request, $key) {
        return !empty($param) && is_string($param) && strlen($param) >= 10;
    }

    public function validate_limit_param($param, $request, $key) {
        return is_numeric($param) && $param > 0 && $param <= 100;
    }

    public function validate_offset_param($param, $request, $key) {
        return is_numeric($param) && $param >= 0;
    }

    // Helper methods
    private function validate_api_key($api_key) {
        // Implement API key validation logic
        return !empty($api_key);
    }

    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    private function validate_license_key($license_key) {
        // Implement license validation logic
        return array(
            'valid' => true,
            'license_key' => $license_key,
            'status' => 'active',
            'message' => 'License is valid'
        );
    }

    private function get_license_status_data($license_key) {
        // Implement license status retrieval logic
        return array(
            'license_key' => $license_key,
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'device_count' => 1,
            'max_devices' => 5
        );
    }

    private function get_licenses_list($limit, $offset) {
        // Implement licenses list retrieval logic
        return array(
            'licenses' => array(),
            'total' => 0,
            'limit' => $limit,
            'offset' => $offset
        );
    }

    /**
     * Get framework statistics
     *
     * @return array Framework statistics
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Get namespace
     *
     * @return string API namespace
     */
    public function get_namespace() {
        return $this->namespace;
    }

    /**
     * Get version
     *
     * @return string Framework version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Check if framework is initialized
     *
     * @return bool True if initialized, false otherwise
     */
    public function is_initialized() {
        return $this->initialized;
    }
}
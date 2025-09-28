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
     * Initialize router (placeholder for future steps)
     *
     * @since 4.1.1
     */
    public function init() {
        // Placeholder for WordPress integration
        // Will be implemented in step 4.1.3
    }

    /**
     * Register routes (placeholder for future steps)
     *
     * @since 4.1.1
     */
    public function register_routes() {
        // Placeholder for route registration
        // Will be implemented in step 4.1.4
    }
}
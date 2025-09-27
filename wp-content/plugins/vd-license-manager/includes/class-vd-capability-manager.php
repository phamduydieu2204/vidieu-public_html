<?php
/**
 * VD Capability Manager
 *
 * Manages WordPress user roles and capabilities for VD License Manager
 * Step 3.3.2: Capability Definitions - Data structures without WordPress registration
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Capability_Manager class
 *
 * Handles user roles, capabilities, and permission hierarchy for the plugin
 * Step 3.3.2: Capability definitions with data structures only
 */
class VD_Capability_Manager {

    /**
     * Single instance of the class
     *
     * @since 1.0.0
     * @var VD_Capability_Manager
     */
    private static $instance = null;

    /**
     * Plugin capabilities
     *
     * @since 1.0.0
     * @var array
     */
    private $capabilities = [];

    /**
     * Plugin roles
     *
     * @since 1.0.0
     * @var array
     */
    private $roles = [];

    /**
     * Get single instance
     *
     * @since 1.0.0
     * @return VD_Capability_Manager Single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private to enforce singleton
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Step 3.3.2: Initialize capability definitions (data structures only)
        $this->init_capabilities();
        $this->init_roles();
        // Note: No WordPress registration yet - just data structure initialization
    }

    /**
     * Prevent cloning
     *
     * @since 1.0.0
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Initialize plugin capabilities
     * Step 3.3.2: Define capability structures without WordPress registration
     *
     * @since 1.0.0
     */
    private function init_capabilities() {
        // Step 3.3.2: Define capability array structure only
        $this->capabilities = [
            // Core license management
            'manage_vd_licenses' => [
                'label' => __('Manage Licenses', VD_LM_TEXT_DOMAIN),
                'description' => __('Create, edit, delete and manage license keys', VD_LM_TEXT_DOMAIN),
                'level' => 'intermediate'
            ],
            'view_vd_licenses' => [
                'label' => __('View Licenses', VD_LM_TEXT_DOMAIN),
                'description' => __('View license information and statistics', VD_LM_TEXT_DOMAIN),
                'level' => 'basic'
            ],

            // Provider account management
            'manage_vd_providers' => [
                'label' => __('Manage Providers', VD_LM_TEXT_DOMAIN),
                'description' => __('Manage license provider accounts and configurations', VD_LM_TEXT_DOMAIN),
                'level' => 'advanced'
            ],
            'view_vd_providers' => [
                'label' => __('View Providers', VD_LM_TEXT_DOMAIN),
                'description' => __('View provider account information', VD_LM_TEXT_DOMAIN),
                'level' => 'basic'
            ],

            // Device management
            'manage_vd_devices' => [
                'label' => __('Manage Devices', VD_LM_TEXT_DOMAIN),
                'description' => __('Approve, reject and manage device registrations', VD_LM_TEXT_DOMAIN),
                'level' => 'intermediate'
            ],
            'view_vd_devices' => [
                'label' => __('View Devices', VD_LM_TEXT_DOMAIN),
                'description' => __('View device registration information', VD_LM_TEXT_DOMAIN),
                'level' => 'basic'
            ],

            // System settings
            'manage_vd_settings' => [
                'label' => __('Manage Settings', VD_LM_TEXT_DOMAIN),
                'description' => __('Configure system settings and preferences', VD_LM_TEXT_DOMAIN),
                'level' => 'advanced'
            ],

            // Audit and logs
            'view_vd_audit_logs' => [
                'label' => __('View Audit Logs', VD_LM_TEXT_DOMAIN),
                'description' => __('Access audit trails and system logs', VD_LM_TEXT_DOMAIN),
                'level' => 'intermediate'
            ],
            'manage_vd_audit_logs' => [
                'label' => __('Manage Audit Logs', VD_LM_TEXT_DOMAIN),
                'description' => __('Delete and export audit logs', VD_LM_TEXT_DOMAIN),
                'level' => 'advanced'
            ],

            // Reports and analytics
            'view_vd_reports' => [
                'label' => __('View Reports', VD_LM_TEXT_DOMAIN),
                'description' => __('Access license usage reports and analytics', VD_LM_TEXT_DOMAIN),
                'level' => 'basic'
            ],
            'export_vd_data' => [
                'label' => __('Export Data', VD_LM_TEXT_DOMAIN),
                'description' => __('Export license and usage data', VD_LM_TEXT_DOMAIN),
                'level' => 'intermediate'
            ]
        ];

        // Note: Step 3.3.2 - Data structures only, no WordPress capability registration
    }

    /**
     * Initialize plugin roles
     * Step 3.3.2: Define role structures without WordPress registration
     *
     * @since 1.0.0
     */
    private function init_roles() {
        // Step 3.3.2: Define role array structure only
        $this->roles = [
            'vd_license_viewer' => [
                'label' => __('VD License Viewer', VD_LM_TEXT_DOMAIN),
                'description' => __('Can view license information but cannot make changes', VD_LM_TEXT_DOMAIN),
                'capabilities' => [
                    'read',
                    'view_vd_licenses',
                    'view_vd_providers',
                    'view_vd_devices',
                    'view_vd_reports'
                ]
            ],

            'vd_license_operator' => [
                'label' => __('VD License Operator', VD_LM_TEXT_DOMAIN),
                'description' => __('Can manage licenses and devices', VD_LM_TEXT_DOMAIN),
                'capabilities' => [
                    'read',
                    'view_vd_licenses',
                    'manage_vd_licenses',
                    'view_vd_providers',
                    'view_vd_devices',
                    'manage_vd_devices',
                    'view_vd_audit_logs',
                    'view_vd_reports',
                    'export_vd_data'
                ]
            ],

            'vd_license_admin' => [
                'label' => __('VD License Administrator', VD_LM_TEXT_DOMAIN),
                'description' => __('Full access to all VD License Manager features', VD_LM_TEXT_DOMAIN),
                'capabilities' => [
                    'read',
                    'view_vd_licenses',
                    'manage_vd_licenses',
                    'view_vd_providers',
                    'manage_vd_providers',
                    'view_vd_devices',
                    'manage_vd_devices',
                    'manage_vd_settings',
                    'view_vd_audit_logs',
                    'manage_vd_audit_logs',
                    'view_vd_reports',
                    'export_vd_data'
                ]
            ]
        ];

        // Note: Step 3.3.2 - Data structures only, no WordPress role registration
    }

    /**
     * Get all plugin capabilities
     * Step 3.3.2: Basic getter method for capability access
     *
     * @since 1.0.0
     * @return array Plugin capabilities
     */
    public function get_capabilities() {
        return $this->capabilities;
    }

    /**
     * Get all plugin roles
     * Step 3.3.2: Basic getter method for role access
     *
     * @since 1.0.0
     * @return array Plugin roles
     */
    public function get_roles() {
        return $this->roles;
    }

    /**
     * Get capability count for testing
     * Step 3.3.2: Helper method for verification
     *
     * @since 1.0.0
     * @return int Number of capabilities defined
     */
    public function get_capability_count() {
        return count($this->capabilities);
    }

    /**
     * Get role count for testing
     * Step 3.3.2: Helper method for verification
     *
     * @since 1.0.0
     * @return int Number of roles defined
     */
    public function get_role_count() {
        return count($this->roles);
    }
}
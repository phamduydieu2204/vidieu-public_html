<?php
/**
 * VD Capability Manager
 *
 * Manages WordPress user roles and capabilities for VD License Manager
 * Step 3.3.4: Single Capability Test - Testing with one capability only
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
 * Step 3.3.4: Single capability test with WordPress integration
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
        // Step 3.3.3: Initialize capability definitions and setup hooks
        $this->init_capabilities();
        $this->init_roles();
        $this->setup_hooks();
        // Note: Hook structure created but no actual capability registration yet
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

    /**
     * Setup WordPress hooks
     * Step 3.3.3: Hook structure without actual capability registration
     *
     * @since 1.0.0
     */
    private function setup_hooks() {
        // Step 3.3.3: Add hooks for future capability management
        // Note: These hooks are created but methods don't register capabilities yet

        // Plugin activation/deactivation hooks (for future use)
        add_action('vd_license_manager_activated', [$this, 'add_capabilities']);
        add_action('vd_license_manager_deactivated', [$this, 'remove_capabilities']);

        // Admin init hook for capability management (for future use)
        add_action('admin_init', [$this, 'maybe_update_capabilities']);

        // User profile hooks (for future use)
        add_action('show_user_profile', [$this, 'show_user_capabilities']);
        add_action('edit_user_profile', [$this, 'show_user_capabilities']);

        // Capability check filters (for future use)
        add_filter('user_has_cap', [$this, 'maybe_grant_super_admin_caps'], 10, 4);

        // Note: All hooks created but target methods are placeholder/empty implementations
    }

    /**
     * Add capabilities to WordPress roles
     * Step 3.3.3: Placeholder method - no actual capability registration yet
     *
     * @since 1.0.0
     */
    public function add_capabilities() {
        // Step 3.3.4: Single capability test implementation
        // Test with ONLY 'view_vd_licenses' capability for Administrator role

        // Get Administrator role
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            // Log error if administrator role not found
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: Administrator role not found');
            }
            return;
        }

        // Add ONLY the test capability 'view_vd_licenses'
        $test_capability = 'view_vd_licenses';

        // Check if capability already exists to avoid duplicates
        if (!$admin_role->has_cap($test_capability)) {
            $admin_role->add_cap($test_capability);

            // Log successful capability addition
            if (function_exists('vd_debug_log')) {
                vd_debug_log("VD_Capability_Manager: Added capability '{$test_capability}' to Administrator role");
            }
        }

        // Log completion of capability addition
        if (class_exists('VD_Audit_Logger')) {
            VD_Audit_Logger::get_instance()->log_event([
                'action' => 'single_capability_added',
                'object_type' => 'capability',
                'object_id' => 0,
                'details' => [
                    'capability' => $test_capability,
                    'role' => 'administrator',
                    'step' => '3.3.4'
                ]
            ]);
        }

        // Note: Step 3.3.4 - Only ONE capability registered for testing
    }

    /**
     * Remove capabilities from WordPress roles
     * Step 3.3.3: Placeholder method - no actual capability removal yet
     *
     * @since 1.0.0
     */
    public function remove_capabilities() {
        // Step 3.3.4: Single capability test removal implementation
        // Remove ONLY 'view_vd_licenses' capability from Administrator role

        // Get Administrator role
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            // Log error if administrator role not found
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: Administrator role not found for removal');
            }
            return;
        }

        // Remove ONLY the test capability 'view_vd_licenses'
        $test_capability = 'view_vd_licenses';

        // Check if capability exists before removing
        if ($admin_role->has_cap($test_capability)) {
            $admin_role->remove_cap($test_capability);

            // Log successful capability removal
            if (function_exists('vd_debug_log')) {
                vd_debug_log("VD_Capability_Manager: Removed capability '{$test_capability}' from Administrator role");
            }
        }

        // Log completion of capability removal
        if (class_exists('VD_Audit_Logger')) {
            VD_Audit_Logger::get_instance()->log_event([
                'action' => 'single_capability_removed',
                'object_type' => 'capability',
                'object_id' => 0,
                'details' => [
                    'capability' => $test_capability,
                    'role' => 'administrator',
                    'step' => '3.3.4'
                ]
            ]);
        }

        // Note: Step 3.3.4 - Only ONE capability removed for testing
    }

    /**
     * Maybe update capabilities (for plugin updates)
     * Step 3.3.3: Placeholder method - no capability updates yet
     *
     * @since 1.0.0
     */
    public function maybe_update_capabilities() {
        // Step 3.3.3: Placeholder implementation
        // This method is called by admin_init but doesn't update capabilities yet
        // Will check for version changes in future steps

        // For now, just ensure hook is working
        // Note: No version checking or capability updates in this step
    }

    /**
     * Show user capabilities on profile page
     * Step 3.3.3: Placeholder method - no profile display yet
     *
     * @since 1.0.0
     * @param WP_User $user User object
     */
    public function show_user_capabilities($user) {
        // Step 3.3.3: Placeholder implementation
        // This method is called by user profile hooks but doesn't show capabilities yet
        // Will be implemented in Step 3.3.5 for complete system

        // For now, just a comment placeholder
        // Note: No actual user profile display in this step
    }

    /**
     * Grant super admin capabilities
     * Step 3.3.3: Placeholder method - no capability granting yet
     *
     * @since 1.0.0
     * @param array $allcaps All capabilities
     * @param array $caps Required capabilities
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array Modified capabilities
     */
    public function maybe_grant_super_admin_caps($allcaps, $caps, $args, $user) {
        // Step 3.3.3: Placeholder implementation
        // This method is called by user_has_cap filter but doesn't grant capabilities yet
        // Will be implemented in Step 3.3.5 for complete system

        // For now, just return unchanged capabilities
        return $allcaps;

        // Note: No actual capability granting in this step
    }

    /**
     * Check if hooks are properly set up
     * Step 3.3.3: Testing helper method
     *
     * @since 1.0.0
     * @return bool True if hooks are set up
     */
    public function are_hooks_setup() {
        // Check if main hooks are registered
        return (
            has_action('vd_license_manager_activated', [$this, 'add_capabilities']) &&
            has_action('vd_license_manager_deactivated', [$this, 'remove_capabilities']) &&
            has_action('admin_init', [$this, 'maybe_update_capabilities'])
        );
    }

    /**
     * Check if test capability is properly assigned
     * Step 3.3.4: Testing helper method for single capability verification
     *
     * @since 1.0.0
     * @return bool True if test capability is assigned to administrator
     */
    public function is_test_capability_assigned() {
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            return false;
        }

        return $admin_role->has_cap('view_vd_licenses');
    }

    /**
     * Check if current user has test capability
     * Step 3.3.4: Testing helper method for current user capability check
     *
     * @since 1.0.0
     * @return bool True if current user can view vd licenses
     */
    public function current_user_can_test_capability() {
        return current_user_can('view_vd_licenses');
    }

    /**
     * Get test capability status
     * Step 3.3.4: Testing helper method for capability status information
     *
     * @since 1.0.0
     * @return array Status information about test capability
     */
    public function get_test_capability_status() {
        $admin_role = get_role('administrator');
        $current_user = wp_get_current_user();

        return [
            'capability_name' => 'view_vd_licenses',
            'admin_role_exists' => ($admin_role !== null),
            'admin_role_has_capability' => $admin_role ? $admin_role->has_cap('view_vd_licenses') : false,
            'current_user_id' => $current_user->ID,
            'current_user_roles' => $current_user->roles,
            'current_user_can_capability' => current_user_can('view_vd_licenses'),
            'step' => '3.3.4'
        ];
    }
}
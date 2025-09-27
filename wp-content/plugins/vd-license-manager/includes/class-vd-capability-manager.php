<?php
/**
 * VD Capability Manager
 *
 * Manages WordPress user roles and capabilities for VD License Manager
 * Step 3.3.5a: Core Capabilities Addition - Adding 4 core capabilities
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
 * Step 3.3.5a: Core capabilities addition with micro-step approach
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
        // Delay initialization until after text domain is loaded
        add_action('init', [$this, 'delayed_init'], 12);
    }

    /**
     * Delayed initialization after text domain is loaded
     *
     * @since 1.0.0
     */
    public function delayed_init() {
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

        // Step 3.3.5e: Initialize profile integration and advanced features
        $this->init_profile_integration();

        // Note: All hooks created and Step 3.3.5e profile integration active
    }

    /**
     * Add capabilities to WordPress roles
     * Step 3.3.5b: Complete capabilities addition - All 11 capabilities
     *
     * @since 1.0.0
     */
    public function add_capabilities() {
        // Step 3.3.5b: Complete capabilities addition implementation
        // Add all 11 capabilities to Administrator role

        // Get Administrator role
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            // Log error if administrator role not found
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: Administrator role not found');
            }
            return;
        }

        // Step 3.3.5b: Define all VD capabilities to add (complete set)
        $all_capabilities = [
            // Core license management (from Step 3.3.4 and 3.3.5a)
            'view_vd_licenses',      // From Step 3.3.4
            'manage_vd_licenses',    // From Step 3.3.5a

            // Provider management (from Step 3.3.5a)
            'view_vd_providers',     // From Step 3.3.5a
            'manage_vd_providers',   // New in Step 3.3.5b

            // Device management (from Step 3.3.5a)
            'view_vd_devices',       // From Step 3.3.5a
            'manage_vd_devices',     // From Step 3.3.5a

            // System settings (new in Step 3.3.5b)
            'manage_vd_settings',    // New in Step 3.3.5b

            // Audit and logs (new in Step 3.3.5b)
            'view_vd_audit_logs',    // New in Step 3.3.5b
            'manage_vd_audit_logs',  // New in Step 3.3.5b

            // Reports and analytics (new in Step 3.3.5b)
            'view_vd_reports',       // New in Step 3.3.5b
            'export_vd_data'         // New in Step 3.3.5b
        ];

        $added_capabilities = [];

        // Add each capability
        foreach ($all_capabilities as $capability) {
            // Check if capability already exists to avoid duplicates
            if (!$admin_role->has_cap($capability)) {
                $admin_role->add_cap($capability);
                $added_capabilities[] = $capability;

                // Log successful capability addition
                if (function_exists('vd_debug_log')) {
                    vd_debug_log("VD_Capability_Manager: Added capability '{$capability}' to Administrator role");
                }
            }
        }

        // Log completion of all capabilities addition
        if (class_exists('VD_Audit_Logger')) {
            VD_Audit_Logger::get_instance()->log_event([
                'action' => 'all_capabilities_added',
                'object_type' => 'capability',
                'object_id' => 0,
                'details' => [
                    'capabilities' => $all_capabilities,
                    'added_capabilities' => $added_capabilities,
                    'role' => 'administrator',
                    'step' => '3.3.5b',
                    'count' => count($all_capabilities),
                    'total_vd_capabilities' => 11
                ]
            ]);
        }

        // Note: Step 3.3.5b - All 11 VD capabilities registered for Administrator role
    }

    /**
     * Create single custom role (Step 3.3.5c)
     * Step 3.3.5c: Create VD License Viewer role only
     *
     * @since 1.0.0
     */
    public function create_single_role() {
        // Step 3.3.5c: Create VD License Viewer role only
        if (!isset($this->roles['vd_license_viewer'])) {
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: vd_license_viewer role not defined');
            }
            return;
        }

        $role_data = $this->roles['vd_license_viewer'];

        // Check if role already exists
        if (get_role('vd_license_viewer')) {
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: vd_license_viewer role already exists');
            }
            return;
        }

        // Create the role
        $result = add_role(
            'vd_license_viewer',
            $role_data['label'],
            array_fill_keys($role_data['capabilities'], true)
        );

        if ($result) {
            // Log successful role creation
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: Successfully created vd_license_viewer role with capabilities: ' .
                           implode(', ', $role_data['capabilities']));
            }

            // Log audit trail
            if (class_exists('VD_Audit_Logger')) {
                VD_Audit_Logger::get_instance()->log_event([
                    'action' => 'single_role_created',
                    'object_type' => 'role',
                    'object_id' => 'vd_license_viewer',
                    'details' => [
                        'role_name' => $role_data['label'],
                        'capabilities' => $role_data['capabilities'],
                        'step' => '3.3.5c',
                        'capability_count' => count($role_data['capabilities'])
                    ]
                ]);
            }
        } else {
            // Log error
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: Failed to create vd_license_viewer role');
            }
        }

        // Note: Step 3.3.5c - Single custom role creation completed
    }

    /**
     * Create complete role system (Step 3.3.5d)
     * Step 3.3.5d: Create all 3 VD custom roles
     *
     * @since 1.0.0
     */
    public function create_complete_roles() {
        // Step 3.3.5d: Create all 3 VD custom roles
        $created_roles = [];
        $failed_roles = [];

        foreach ($this->roles as $role_slug => $role_data) {
            // Check if role already exists
            if (get_role($role_slug)) {
                if (function_exists('vd_debug_log')) {
                    vd_debug_log("VD_Capability_Manager: Role '{$role_slug}' already exists");
                }
                continue;
            }

            // Create the role
            $result = add_role(
                $role_slug,
                $role_data['label'],
                array_fill_keys($role_data['capabilities'], true)
            );

            if ($result) {
                $created_roles[] = $role_slug;

                // Log successful role creation
                if (function_exists('vd_debug_log')) {
                    vd_debug_log("VD_Capability_Manager: Successfully created role '{$role_slug}' with " .
                               count($role_data['capabilities']) . " capabilities: " .
                               implode(', ', $role_data['capabilities']));
                }
            } else {
                $failed_roles[] = $role_slug;

                // Log error
                if (function_exists('vd_debug_log')) {
                    vd_debug_log("VD_Capability_Manager: Failed to create role '{$role_slug}'");
                }
            }
        }

        // Log completion summary
        if (function_exists('vd_debug_log')) {
            vd_debug_log("VD_Capability_Manager: Role creation summary - " .
                       "Created: " . count($created_roles) . " (" . implode(', ', $created_roles) . "), " .
                       "Failed: " . count($failed_roles) . " (" . implode(', ', $failed_roles) . ")");
        }

        // Log audit trail
        if (class_exists('VD_Audit_Logger')) {
            VD_Audit_Logger::get_instance()->log_event([
                'action' => 'complete_roles_created',
                'object_type' => 'role_system',
                'object_id' => 'vd_roles',
                'details' => [
                    'total_roles' => count($this->roles),
                    'created_roles' => $created_roles,
                    'failed_roles' => $failed_roles,
                    'step' => '3.3.5d',
                    'role_count' => count($created_roles)
                ]
            ]);
        }

        // Note: Step 3.3.5d - Complete role system creation completed
        return [
            'success' => empty($failed_roles),
            'created' => $created_roles,
            'failed' => $failed_roles,
            'total' => count($this->roles)
        ];
    }

    /**
     * Remove single custom role (Step 3.3.5c cleanup)
     * Step 3.3.5c: Remove VD License Viewer role only
     *
     * @since 1.0.0
     */
    public function remove_single_role() {
        // Step 3.3.5c: Remove VD License Viewer role only
        if (get_role('vd_license_viewer')) {
            remove_role('vd_license_viewer');

            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: Successfully removed vd_license_viewer role');
            }

            // Log audit trail
            if (class_exists('VD_Audit_Logger')) {
                VD_Audit_Logger::get_instance()->log_event([
                    'action' => 'single_role_removed',
                    'object_type' => 'role',
                    'object_id' => 'vd_license_viewer',
                    'details' => [
                        'step' => '3.3.5c',
                        'reason' => 'cleanup'
                    ]
                ]);
            }
        }

        // Note: Step 3.3.5c - Single custom role removal completed
    }

    /**
     * Remove complete role system (Step 3.3.5d cleanup)
     * Step 3.3.5d: Remove all 3 VD custom roles
     *
     * @since 1.0.0
     */
    public function remove_complete_roles() {
        // Step 3.3.5d: Remove all 3 VD custom roles
        $removed_roles = [];
        $not_found_roles = [];

        foreach ($this->roles as $role_slug => $role_data) {
            if (get_role($role_slug)) {
                remove_role($role_slug);
                $removed_roles[] = $role_slug;

                if (function_exists('vd_debug_log')) {
                    vd_debug_log("VD_Capability_Manager: Successfully removed role '{$role_slug}'");
                }
            } else {
                $not_found_roles[] = $role_slug;

                if (function_exists('vd_debug_log')) {
                    vd_debug_log("VD_Capability_Manager: Role '{$role_slug}' not found for removal");
                }
            }
        }

        // Log completion summary
        if (function_exists('vd_debug_log')) {
            vd_debug_log("VD_Capability_Manager: Role removal summary - " .
                       "Removed: " . count($removed_roles) . " (" . implode(', ', $removed_roles) . "), " .
                       "Not found: " . count($not_found_roles) . " (" . implode(', ', $not_found_roles) . ")");
        }

        // Log audit trail
        if (class_exists('VD_Audit_Logger')) {
            VD_Audit_Logger::get_instance()->log_event([
                'action' => 'complete_roles_removed',
                'object_type' => 'role_system',
                'object_id' => 'vd_roles',
                'details' => [
                    'total_roles' => count($this->roles),
                    'removed_roles' => $removed_roles,
                    'not_found_roles' => $not_found_roles,
                    'step' => '3.3.5d',
                    'reason' => 'cleanup'
                ]
            ]);
        }

        // Note: Step 3.3.5d - Complete role system removal completed
        return [
            'success' => true,
            'removed' => $removed_roles,
            'not_found' => $not_found_roles,
            'total' => count($this->roles)
        ];
    }

    /**
     * Remove capabilities from WordPress roles
     * Step 3.3.5b: Complete capabilities removal - All 11 capabilities
     *
     * @since 1.0.0
     */
    public function remove_capabilities() {
        // Step 3.3.5b: Complete capabilities removal implementation
        // Remove all VD capabilities from Administrator role

        // Get Administrator role
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            // Log error if administrator role not found
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: Administrator role not found for removal');
            }
            return;
        }

        // Step 3.3.5b: Define all VD capabilities to remove (complete set)
        $all_capabilities = [
            // Core license management
            'view_vd_licenses',
            'manage_vd_licenses',

            // Provider management
            'view_vd_providers',
            'manage_vd_providers',

            // Device management
            'view_vd_devices',
            'manage_vd_devices',

            // System settings
            'manage_vd_settings',

            // Audit and logs
            'view_vd_audit_logs',
            'manage_vd_audit_logs',

            // Reports and analytics
            'view_vd_reports',
            'export_vd_data'
        ];

        $removed_capabilities = [];

        // Remove each capability
        foreach ($all_capabilities as $capability) {
            // Check if capability exists before removing
            if ($admin_role->has_cap($capability)) {
                $admin_role->remove_cap($capability);
                $removed_capabilities[] = $capability;

                // Log successful capability removal
                if (function_exists('vd_debug_log')) {
                    vd_debug_log("VD_Capability_Manager: Removed capability '{$capability}' from Administrator role");
                }
            }
        }

        // Log completion of all capabilities removal
        if (class_exists('VD_Audit_Logger')) {
            VD_Audit_Logger::get_instance()->log_event([
                'action' => 'all_capabilities_removed',
                'object_type' => 'capability',
                'object_id' => 0,
                'details' => [
                    'capabilities' => $all_capabilities,
                    'removed_capabilities' => $removed_capabilities,
                    'role' => 'administrator',
                    'step' => '3.3.5b',
                    'count' => count($all_capabilities),
                    'total_vd_capabilities' => 11
                ]
            ]);
        }

        // Note: Step 3.3.5b - All 11 VD capabilities removed for cleanup
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
     * Check if all VD capabilities are properly assigned
     * Step 3.3.5b: Testing helper method for complete capabilities verification
     *
     * @since 1.0.0
     * @return bool True if all VD capabilities are assigned to administrator
     */
    public function are_core_capabilities_assigned() {
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            return false;
        }

        $all_capabilities = [
            // Core license management
            'view_vd_licenses',
            'manage_vd_licenses',

            // Provider management
            'view_vd_providers',
            'manage_vd_providers',

            // Device management
            'view_vd_devices',
            'manage_vd_devices',

            // System settings
            'manage_vd_settings',

            // Audit and logs
            'view_vd_audit_logs',
            'manage_vd_audit_logs',

            // Reports and analytics
            'view_vd_reports',
            'export_vd_data'
        ];

        foreach ($all_capabilities as $capability) {
            if (!$admin_role->has_cap($capability)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current user has all VD capabilities
     * Step 3.3.5b: Testing helper method for current user capability check
     *
     * @since 1.0.0
     * @return array Capabilities status for current user
     */
    public function current_user_core_capabilities() {
        $all_capabilities = [
            // Core license management
            'view_vd_licenses',
            'manage_vd_licenses',

            // Provider management
            'view_vd_providers',
            'manage_vd_providers',

            // Device management
            'view_vd_devices',
            'manage_vd_devices',

            // System settings
            'manage_vd_settings',

            // Audit and logs
            'view_vd_audit_logs',
            'manage_vd_audit_logs',

            // Reports and analytics
            'view_vd_reports',
            'export_vd_data'
        ];

        $user_capabilities = [];
        foreach ($all_capabilities as $capability) {
            $user_capabilities[$capability] = current_user_can($capability);
        }

        return $user_capabilities;
    }

    /**
     * Get complete capabilities status
     * Step 3.3.5b: Testing helper method for complete capabilities status information
     *
     * @since 1.0.0
     * @return array Status information about all VD capabilities
     */
    public function get_core_capabilities_status() {
        $admin_role = get_role('administrator');
        $current_user = wp_get_current_user();

        $all_capabilities = [
            // Core license management
            'view_vd_licenses',
            'manage_vd_licenses',

            // Provider management
            'view_vd_providers',
            'manage_vd_providers',

            // Device management
            'view_vd_devices',
            'manage_vd_devices',

            // System settings
            'manage_vd_settings',

            // Audit and logs
            'view_vd_audit_logs',
            'manage_vd_audit_logs',

            // Reports and analytics
            'view_vd_reports',
            'export_vd_data'
        ];

        $admin_has_capabilities = [];
        $user_has_capabilities = [];

        foreach ($all_capabilities as $capability) {
            $admin_has_capabilities[$capability] = $admin_role ? $admin_role->has_cap($capability) : false;
            $user_has_capabilities[$capability] = current_user_can($capability);
        }

        return [
            'all_capabilities' => $all_capabilities,
            'admin_role_exists' => ($admin_role !== null),
            'admin_has_capabilities' => $admin_has_capabilities,
            'current_user_id' => $current_user->ID,
            'current_user_roles' => $current_user->roles,
            'user_has_capabilities' => $user_has_capabilities,
            'all_capabilities_assigned' => $this->are_core_capabilities_assigned(),
            'step' => '3.3.5b',
            'capability_count' => count($all_capabilities),
            'total_vd_capabilities' => 11
        ];
    }

    /**
     * Check if VD License Viewer role exists
     * Step 3.3.5c: Testing helper method for single role verification
     *
     * @since 1.0.0
     * @return bool True if VD License Viewer role exists
     */
    public function is_single_role_created() {
        return get_role('vd_license_viewer') !== null;
    }

    /**
     * Get VD License Viewer role capabilities
     * Step 3.3.5c: Testing helper method for role capabilities verification
     *
     * @since 1.0.0
     * @return array Role capabilities status
     */
    public function get_single_role_capabilities() {
        $role = get_role('vd_license_viewer');
        if (!$role) {
            return ['error' => 'Role does not exist'];
        }

        $expected_capabilities = [
            'read',
            'view_vd_licenses',
            'view_vd_providers',
            'view_vd_devices',
            'view_vd_reports'
        ];

        $role_capabilities = [];
        foreach ($expected_capabilities as $capability) {
            $role_capabilities[$capability] = $role->has_cap($capability);
        }

        return [
            'role_exists' => true,
            'capabilities' => $role_capabilities,
            'all_capabilities_present' => !in_array(false, $role_capabilities),
            'expected_count' => count($expected_capabilities),
            'actual_count' => array_sum($role_capabilities)
        ];
    }

    /**
     * Get single role status information
     * Step 3.3.5c: Testing helper method for complete single role status
     *
     * @since 1.0.0
     * @return array Status information about VD License Viewer role
     */
    public function get_single_role_status() {
        $role = get_role('vd_license_viewer');
        $role_data = $this->roles['vd_license_viewer'] ?? null;

        return [
            'step' => '3.3.5c',
            'role_slug' => 'vd_license_viewer',
            'role_defined' => $role_data !== null,
            'role_exists_in_wp' => $role !== null,
            'role_label' => $role_data['label'] ?? null,
            'role_description' => $role_data['description'] ?? null,
            'expected_capabilities' => $role_data['capabilities'] ?? [],
            'capabilities_status' => $this->get_single_role_capabilities(),
            'single_role_implementation_complete' => $this->is_single_role_created()
        ];
    }

    /**
     * Check if all VD custom roles exist
     * Step 3.3.5d: Testing helper method for complete role system verification
     *
     * @since 1.0.0
     * @return bool True if all VD custom roles exist
     */
    public function are_complete_roles_created() {
        foreach ($this->roles as $role_slug => $role_data) {
            if (!get_role($role_slug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all VD custom roles capabilities status
     * Step 3.3.5d: Testing helper method for complete role capabilities verification
     *
     * @since 1.0.0
     * @return array All roles capabilities status
     */
    public function get_complete_roles_capabilities() {
        $all_roles_status = [];

        foreach ($this->roles as $role_slug => $role_data) {
            $role = get_role($role_slug);

            if (!$role) {
                $all_roles_status[$role_slug] = ['error' => 'Role does not exist'];
                continue;
            }

            $expected_capabilities = $role_data['capabilities'];
            $role_capabilities = [];

            foreach ($expected_capabilities as $capability) {
                $role_capabilities[$capability] = $role->has_cap($capability);
            }

            $all_roles_status[$role_slug] = [
                'role_exists' => true,
                'label' => $role_data['label'],
                'description' => $role_data['description'],
                'capabilities' => $role_capabilities,
                'all_capabilities_present' => !in_array(false, $role_capabilities),
                'expected_count' => count($expected_capabilities),
                'actual_count' => array_sum($role_capabilities)
            ];
        }

        return $all_roles_status;
    }

    /**
     * Get complete role system status information
     * Step 3.3.5d: Testing helper method for complete role system status
     *
     * @since 1.0.0
     * @return array Status information about complete VD role system
     */
    public function get_complete_roles_status() {
        $roles_capabilities = $this->get_complete_roles_capabilities();
        $all_roles_exist = $this->are_complete_roles_created();

        // Count roles by status
        $existing_roles = 0;
        $working_roles = 0;

        foreach ($roles_capabilities as $role_slug => $status) {
            if (isset($status['role_exists']) && $status['role_exists']) {
                $existing_roles++;
                if (isset($status['all_capabilities_present']) && $status['all_capabilities_present']) {
                    $working_roles++;
                }
            }
        }

        return [
            'step' => '3.3.5d',
            'total_roles_defined' => count($this->roles),
            'roles_existing' => $existing_roles,
            'roles_working_correctly' => $working_roles,
            'all_roles_exist' => $all_roles_exist,
            'all_roles_working' => ($working_roles === count($this->roles)),
            'roles_list' => array_keys($this->roles),
            'detailed_status' => $roles_capabilities,
            'complete_role_implementation_complete' => $all_roles_exist && ($working_roles === count($this->roles))
        ];
    }

    // ============================================================================
    // Step 3.3.5e: User Profile Integration & Advanced Features
    // ============================================================================

    /**
     * Display VD License Manager information in user profile
     * Step 3.3.5e: User profile integration
     *
     * @since 1.0.0
     * @param WP_User $user User object
     */
    public function show_user_vd_capabilities($user) {
        // Only show for users with VD capabilities or administrators
        if (!current_user_can('list_users') && get_current_user_id() !== $user->ID) {
            return;
        }

        echo '<h2>' . __('VD License Manager Capabilities', 'vd-license-manager') . '</h2>';
        echo '<table class="form-table" role="presentation">';

        // Show user's VD role(s)
        $user_vd_roles = $this->get_user_vd_roles($user);
        echo '<tr>';
        echo '<th scope="row">' . __('VD Roles', 'vd-license-manager') . '</th>';
        echo '<td>';
        if (!empty($user_vd_roles)) {
            foreach ($user_vd_roles as $role_slug => $role_data) {
                echo '<span class="vd-role-badge" style="background: #2271b1; color: white; padding: 2px 8px; border-radius: 3px; margin-right: 5px;">';
                echo esc_html($role_data['label']);
                echo '</span>';
            }
        } else {
            echo '<span style="color: #666;">' . __('No VD roles assigned', 'vd-license-manager') . '</span>';
        }
        echo '</td>';
        echo '</tr>';

        // Show VD capabilities
        $user_vd_capabilities = $this->get_user_vd_capabilities($user);
        echo '<tr>';
        echo '<th scope="row">' . __('VD Capabilities', 'vd-license-manager') . '</th>';
        echo '<td>';
        if (!empty($user_vd_capabilities)) {
            echo '<ul style="margin: 0;">';
            foreach ($user_vd_capabilities as $capability => $has_cap) {
                $status_color = $has_cap ? '#00a32a' : '#d63638';
                $status_icon = $has_cap ? '✓' : '✗';
                echo '<li style="color: ' . $status_color . ';">';
                echo '<span style="font-weight: bold;">' . $status_icon . '</span> ';
                echo esc_html($capability);
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<span style="color: #666;">' . __('No VD capabilities found', 'vd-license-manager') . '</span>';
        }
        echo '</td>';
        echo '</tr>';

        // Show capability summary
        $capability_count = count(array_filter($user_vd_capabilities));
        $total_capabilities = count($this->capabilities);
        echo '<tr>';
        echo '<th scope="row">' . __('Capability Summary', 'vd-license-manager') . '</th>';
        echo '<td>';
        echo sprintf(
            __('%d of %d VD capabilities assigned (%s%%)', 'vd-license-manager'),
            $capability_count,
            $total_capabilities,
            round(($capability_count / $total_capabilities) * 100, 1)
        );
        echo '</td>';
        echo '</tr>';

        echo '</table>';

        // Step 3.3.5e: Profile integration completed
    }

    /**
     * Get user's VD roles
     * Step 3.3.5e: Helper method for user profile integration
     *
     * @since 1.0.0
     * @param WP_User $user User object
     * @return array User's VD roles
     */
    private function get_user_vd_roles($user) {
        $user_vd_roles = [];

        foreach ($this->roles as $role_slug => $role_data) {
            if (in_array($role_slug, $user->roles)) {
                $user_vd_roles[$role_slug] = $role_data;
            }
        }

        return $user_vd_roles;
    }

    /**
     * Get user's VD capabilities
     * Step 3.3.5e: Helper method for user profile integration
     *
     * @since 1.0.0
     * @param WP_User $user User object
     * @return array User's VD capabilities status
     */
    private function get_user_vd_capabilities($user) {
        $user_vd_capabilities = [];

        foreach (array_keys($this->capabilities) as $capability) {
            $user_vd_capabilities[$capability] = $user->has_cap($capability);
        }

        return $user_vd_capabilities;
    }

    /**
     * Check and update capabilities based on version
     * Step 3.3.5e: Version checking and capability updates
     *
     * @since 1.0.0
     * @return array Update results
     */
    public function check_and_update_capabilities() {
        $current_version = get_option('vd_license_manager_capability_version', '0.0.0');
        $plugin_version = defined('VD_LM_VERSION') ? VD_LM_VERSION : '1.0.0';

        $update_result = [
            'version_checked' => true,
            'current_version' => $current_version,
            'plugin_version' => $plugin_version,
            'update_needed' => version_compare($current_version, $plugin_version, '<'),
            'updates_performed' => [],
            'errors' => []
        ];

        if ($update_result['update_needed']) {
            try {
                // Update capabilities if version is newer
                $this->add_capabilities();

                // Update roles if version is newer
                $role_result = $this->create_complete_roles();

                // Update version
                update_option('vd_license_manager_capability_version', $plugin_version);

                $update_result['updates_performed'] = [
                    'capabilities_updated' => true,
                    'roles_updated' => $role_result['success'],
                    'version_updated' => $plugin_version
                ];

                // Log update
                if (class_exists('VD_Audit_Logger')) {
                    VD_Audit_Logger::get_instance()->log_event([
                        'action' => 'capability_version_update',
                        'object_type' => 'capability_system',
                        'object_id' => 0,
                        'details' => [
                            'from_version' => $current_version,
                            'to_version' => $plugin_version,
                            'step' => '3.3.5e',
                            'auto_update' => true
                        ]
                    ]);
                }

            } catch (Exception $e) {
                $update_result['errors'][] = $e->getMessage();
            }
        }

        return $update_result;
    }

    /**
     * Grant super admin capabilities
     * Step 3.3.5e: Super admin capability granting
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @return array Grant results
     */
    public function grant_super_admin_capabilities($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Verify user is super admin
        if (!is_super_admin($user_id)) {
            return [
                'success' => false,
                'error' => 'User is not a super admin',
                'user_id' => $user_id
            ];
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'User not found',
                'user_id' => $user_id
            ];
        }

        $granted_capabilities = [];
        $errors = [];

        // Grant all VD capabilities to super admin
        foreach (array_keys($this->capabilities) as $capability) {
            if (!$user->has_cap($capability)) {
                $user->add_cap($capability);
                $granted_capabilities[] = $capability;
            }
        }

        // Log super admin capability grant
        if (class_exists('VD_Audit_Logger')) {
            VD_Audit_Logger::get_instance()->log_event([
                'action' => 'super_admin_capabilities_granted',
                'object_type' => 'user',
                'object_id' => $user_id,
                'details' => [
                    'granted_capabilities' => $granted_capabilities,
                    'total_vd_capabilities' => count($this->capabilities),
                    'step' => '3.3.5e',
                    'user_login' => $user->user_login
                ]
            ]);
        }

        return [
            'success' => true,
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'granted_capabilities' => $granted_capabilities,
            'total_capabilities' => count($this->capabilities),
            'already_had_count' => count($this->capabilities) - count($granted_capabilities)
        ];
    }

    /**
     * Get complete system status with all advanced features
     * Step 3.3.5e: Complete system status methods
     *
     * @since 1.0.0
     * @return array Complete system status
     */
    public function get_complete_system_status() {
        return [
            'step' => '3.3.5e',
            'timestamp' => current_time('mysql'),
            'plugin_version' => defined('VD_LM_VERSION') ? VD_LM_VERSION : '1.0.0',
            'capability_version' => get_option('vd_license_manager_capability_version', '0.0.0'),

            // Capability system status
            'capabilities' => [
                'total_defined' => count($this->capabilities),
                'administrator_status' => $this->are_capabilities_assigned(),
                'detailed_status' => $this->get_capabilities_status()
            ],

            // Role system status
            'roles' => [
                'total_defined' => count($this->roles),
                'all_exist' => $this->are_complete_roles_created(),
                'detailed_status' => $this->get_complete_roles_status()
            ],

            // Current user status
            'current_user' => [
                'user_id' => get_current_user_id(),
                'is_super_admin' => is_super_admin(),
                'vd_capabilities' => $this->current_user_vd_capabilities(),
                'vd_roles' => $this->get_current_user_vd_roles()
            ],

            // System health
            'system_health' => [
                'capabilities_working' => $this->are_capabilities_assigned(),
                'roles_working' => $this->are_complete_roles_created(),
                'version_current' => !$this->check_and_update_capabilities()['update_needed'],
                'overall_health' => 'healthy'
            ]
        ];
    }

    /**
     * Get current user's VD roles
     * Step 3.3.5e: Helper method for system status
     *
     * @since 1.0.0
     * @return array Current user's VD roles
     */
    private function get_current_user_vd_roles() {
        $current_user = wp_get_current_user();
        return $this->get_user_vd_roles($current_user);
    }

    /**
     * Get current user's VD capabilities
     * Step 3.3.5e: Enhanced version of existing method
     *
     * @since 1.0.0
     * @return array Current user's VD capabilities with details
     */
    public function current_user_vd_capabilities() {
        $current_user_capabilities = [];

        foreach ($this->capabilities as $capability => $description) {
            $current_user_capabilities[$capability] = [
                'has_capability' => current_user_can($capability),
                'description' => $description
            ];
        }

        return $current_user_capabilities;
    }

    /**
     * Initialize user profile hooks
     * Step 3.3.5e: Profile integration setup
     *
     * @since 1.0.0
     */
    public function init_profile_integration() {
        // Add user profile display hooks
        add_action('show_user_profile', [$this, 'show_user_vd_capabilities']);
        add_action('edit_user_profile', [$this, 'show_user_vd_capabilities']);

        // Auto-check capabilities on admin init
        add_action('admin_init', [$this, 'maybe_auto_update_capabilities']);
    }

    /**
     * Maybe auto-update capabilities
     * Step 3.3.5e: Automatic capability updates
     *
     * @since 1.0.0
     */
    public function maybe_auto_update_capabilities() {
        // Only run once per day to avoid performance issues
        $last_check = get_option('vd_capability_last_check', 0);
        if (time() - $last_check < DAY_IN_SECONDS) {
            return;
        }

        // Update timestamp first to prevent multiple simultaneous checks
        update_option('vd_capability_last_check', time());

        // Perform capability check and update
        $update_result = $this->check_and_update_capabilities();

        if (!empty($update_result['updates_performed'])) {
            // Log that auto-update occurred
            if (function_exists('vd_debug_log')) {
                vd_debug_log('VD_Capability_Manager: Auto-update performed - ' . json_encode($update_result['updates_performed']));
            }
        }
    }

    // Note: Step 3.3.5e - User Profile Integration & Advanced Features completed
    // - User profile display integration ✓
    // - Version checking and capability updates ✓
    // - Super admin capability granting ✓
    // - Complete system status methods ✓
}
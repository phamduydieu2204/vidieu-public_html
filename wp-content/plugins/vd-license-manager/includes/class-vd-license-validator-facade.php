<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Validator Facade
 *
 * Facade pattern implementation to gradually replace monolithic VD_License_Validator
 * with extracted modular components while maintaining backward compatibility.
 *
 * Step 5.1.11 - Monolithic Validator Replacement with Facade Pattern
 *
 * @package VD_License_Manager
 * @version 1.0.0
 * @since 2025-01-03
 */
class VD_License_Validator_Facade {

    /**
     * Singleton instance
     *
     * @var VD_License_Validator_Facade|null
     */
    private static $instance = null;

    /**
     * Extracted module instances
     *
     * @var array
     */
    private $modules = [];

    /**
     * Legacy validator instance (for methods not yet migrated)
     *
     * @var VD_License_Validator|null
     */
    private $legacy_validator = null;

    /**
     * Migration status tracking
     *
     * @var array
     */
    private $migration_status = [];

    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->initialize_modules();
        $this->initialize_legacy_validator();
        $this->track_migration_status();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Validator_Facade
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize extracted modules
     */
    private function initialize_modules() {
        // Load module files
        $module_files = [
            'validation_utils' => 'modules/validator/class-vd-license-validation-utils.php',
            'expiry_processor' => 'modules/validator/class-vd-license-expiry-processor.php',
            'status_controller' => 'modules/validator/class-vd-license-status-transition-controller.php',
            'orchestrator' => 'modules/validator/class-vd-license-validation-orchestrator.php'
        ];

        foreach ($module_files as $key => $file) {
            $full_path = plugin_dir_path(__FILE__) . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }

        // Initialize module instances
        try {
            $this->modules['validation_utils'] = class_exists('VD_License_Validation_Utils')
                ? VD_License_Validation_Utils::get_instance()
                : null;

            $this->modules['expiry_processor'] = class_exists('VD_License_Expiry_Processor')
                ? VD_License_Expiry_Processor::get_instance()
                : null;

            $this->modules['status_controller'] = class_exists('VD_License_Status_Transition_Controller')
                ? VD_License_Status_Transition_Controller::get_instance()
                : null;

            $this->modules['orchestrator'] = class_exists('VD_License_Validation_Orchestrator')
                ? VD_License_Validation_Orchestrator::get_instance()
                : null;

        } catch (Exception $e) {
            error_log("VD Validator Facade: Failed to initialize modules - " . $e->getMessage());
        }
    }

    /**
     * Initialize legacy validator for fallback
     */
    private function initialize_legacy_validator() {
        if (class_exists('VD_License_Validator')) {
            try {
                $this->legacy_validator = VD_License_Validator::get_instance();
            } catch (Exception $e) {
                error_log("VD Validator Facade: Failed to initialize legacy validator - " . $e->getMessage());
            }
        }
    }

    /**
     * Track migration status for each method
     */
    private function track_migration_status() {
        $this->migration_status = [
            // Validation Utils Module Methods
            'validate_license_key_format' => 'MIGRATED',
            'get_global_settings' => 'MIGRATED',
            'get_lookup_debug_info' => 'MIGRATED',
            'get_memory_usage_info' => 'MIGRATED',
            'create_validation_error' => 'MIGRATED',
            'generate_validation_statistics' => 'MIGRATED',
            'test_database_connectivity' => 'MIGRATED',
            'table_exists' => 'MIGRATED',

            // Expiry Processor Module Methods
            'validate_license_expiry' => 'MIGRATED',
            'update_expired_license_statuses' => 'MIGRATED',
            'schedule_automatic_updates' => 'MIGRATED',

            // Status Transition Controller Module Methods
            'send_status_change_notification' => 'MIGRATED',
            'track_status_history' => 'MIGRATED',
            'get_status_history' => 'MIGRATED',
            'get_status_statistics' => 'MIGRATED',

            // Orchestrator Module Methods
            'vd_validate_license_key' => 'MIGRATED',
            'get_detailed_validation' => 'MIGRATED',
            'validate_license_keys_batch' => 'MIGRATED',

            // Legacy Methods (still using monolithic validator)
            'init' => 'LEGACY',
            'enforce_business_rules' => 'LEGACY',
            'clear_cache' => 'LEGACY',
            'is_ready' => 'LEGACY',
            'get_validation_status' => 'LEGACY',
            'apply_advanced_validation_rules' => 'LEGACY'
        ];
    }

    /**
     * Method delegation with fallback mechanism
     */
    public function __call($method, $args) {
        // Check if method is migrated
        if (isset($this->migration_status[$method]) && $this->migration_status[$method] === 'MIGRATED') {
            return $this->call_migrated_method($method, $args);
        }

        // Fallback to legacy validator
        if ($this->legacy_validator && method_exists($this->legacy_validator, $method)) {
            return call_user_func_array([$this->legacy_validator, $method], $args);
        }

        // Method not found
        throw new BadMethodCallException("Method {$method} not found in VD_License_Validator_Facade");
    }

    /**
     * Call migrated method from appropriate module
     */
    private function call_migrated_method($method, $args) {
        // Validation Utils methods
        if (in_array($method, [
            'validate_license_key_format', 'get_global_settings', 'get_lookup_debug_info',
            'get_memory_usage_info', 'create_validation_error', 'generate_validation_statistics',
            'test_database_connectivity', 'table_exists'
        ])) {
            if ($this->modules['validation_utils']) {
                return call_user_func_array([$this->modules['validation_utils'], $method], $args);
            }
        }

        // Expiry Processor methods
        if (in_array($method, [
            'validate_license_expiry', 'update_expired_license_statuses', 'schedule_automatic_updates'
        ])) {
            if ($this->modules['expiry_processor']) {
                return call_user_func_array([$this->modules['expiry_processor'], $method], $args);
            }
        }

        // Status Transition Controller methods
        if (in_array($method, [
            'send_status_change_notification', 'track_status_history',
            'get_status_history', 'get_status_statistics'
        ])) {
            if ($this->modules['status_controller']) {
                return call_user_func_array([$this->modules['status_controller'], $method], $args);
            }
        }

        // Orchestrator methods
        if (in_array($method, [
            'vd_validate_license_key', 'get_detailed_validation', 'validate_license_keys_batch'
        ])) {
            if ($this->modules['orchestrator']) {
                return call_user_func_array([$this->modules['orchestrator'], $method], $args);
            }
        }

        // Fallback to legacy if module not available
        if ($this->legacy_validator && method_exists($this->legacy_validator, $method)) {
            return call_user_func_array([$this->legacy_validator, $method], $args);
        }

        throw new RuntimeException("Failed to execute method {$method} - no available implementation");
    }

    // ===== EXPLICITLY DEFINED MIGRATED METHODS =====

    /**
     * Validate license key format
     * Migrated to: VD_License_Validation_Utils
     */
    public function validate_license_key_format($license_key, $detailed = false) {
        if ($this->modules['validation_utils']) {
            return $this->modules['validation_utils']->validate_license_key_format($license_key);
        }
        return $this->legacy_validator ? $this->legacy_validator->validate_license_key_format($license_key, $detailed) : false;
    }

    /**
     * Validate license expiry
     * Migrated to: VD_License_Expiry_Processor
     */
    public function validate_license_expiry($license_key) {
        if ($this->modules['expiry_processor']) {
            return $this->modules['expiry_processor']->validate_license_expiry_date($license_key);
        }
        return $this->legacy_validator ? $this->legacy_validator->validate_license_expiry($license_key) : false;
    }

    /**
     * Update expired license statuses
     * Migrated to: VD_License_Expiry_Processor
     */
    public function update_expired_license_statuses($options = array()) {
        if ($this->modules['expiry_processor']) {
            return $this->modules['expiry_processor']->update_expired_license_statuses($options);
        }
        return $this->legacy_validator ? $this->legacy_validator->update_expired_license_statuses($options) : false;
    }

    /**
     * Validate license key (main validation method)
     * Migrated to: VD_License_Validation_Orchestrator
     */
    public function vd_validate_license_key($license_key) {
        if ($this->modules['orchestrator']) {
            return $this->modules['orchestrator']->vd_validate_license_key($license_key);
        }
        return $this->legacy_validator ? $this->legacy_validator->vd_validate_license_key($license_key) : false;
    }

    /**
     * Get detailed validation
     * Migrated to: VD_License_Validation_Orchestrator
     */
    public function get_detailed_validation($license_key) {
        if ($this->modules['orchestrator']) {
            return $this->modules['orchestrator']->get_detailed_validation($license_key);
        }
        return $this->legacy_validator ? $this->legacy_validator->get_detailed_validation($license_key) : false;
    }

    /**
     * Send status change notification
     * Migrated to: VD_License_Status_Transition_Controller
     */
    public function send_status_change_notification($license, $old_status, $new_status, $context = array()) {
        if ($this->modules['status_controller']) {
            return $this->modules['status_controller']->send_status_change_notification($license, $old_status, $new_status, $context);
        }
        return $this->legacy_validator ? $this->legacy_validator->send_status_change_notification($license, $old_status, $new_status, $context) : false;
    }

    // ===== UTILITY METHODS =====

    /**
     * Get migration status report
     */
    public function get_migration_status() {
        $migrated_count = count(array_filter($this->migration_status, function($status) {
            return $status === 'MIGRATED';
        }));
        $total_count = count($this->migration_status);
        $legacy_count = $total_count - $migrated_count;

        return [
            'total_methods' => $total_count,
            'migrated_methods' => $migrated_count,
            'legacy_methods' => $legacy_count,
            'migration_percentage' => round(($migrated_count / $total_count) * 100, 2),
            'modules_loaded' => [
                'validation_utils' => $this->modules['validation_utils'] !== null,
                'expiry_processor' => $this->modules['expiry_processor'] !== null,
                'status_controller' => $this->modules['status_controller'] !== null,
                'orchestrator' => $this->modules['orchestrator'] !== null
            ],
            'legacy_validator_available' => $this->legacy_validator !== null,
            'detailed_status' => $this->migration_status
        ];
    }

    /**
     * Test facade functionality
     */
    public function test_facade_functionality() {
        $results = [];

        // Test migrated methods
        $test_methods = [
            'validate_license_key_format' => ['VD-TEST-XXXX-XXXX'],
            'get_memory_usage_info' => [],
            'test_database_connectivity' => []
        ];

        foreach ($test_methods as $method => $args) {
            try {
                $result = call_user_func_array([$this, $method], $args);
                $results[$method] = [
                    'status' => 'SUCCESS',
                    'result' => $result
                ];
            } catch (Exception $e) {
                $results[$method] = [
                    'status' => 'ERROR',
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'facade_test_results' => $results,
            'migration_status' => $this->get_migration_status(),
            'timestamp' => current_time('Y-m-d H:i:s')
        ];
    }

    /**
     * Initialize method for compatibility
     */
    public function init() {
        // Delegate to legacy validator for now
        if ($this->legacy_validator) {
            return $this->legacy_validator->init();
        }
        return true;
    }

    // Prevent cloning and serialization
    private function __clone() {}
    private function __wakeup() {}
}
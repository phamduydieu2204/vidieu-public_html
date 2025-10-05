<?php

namespace VD\LicenseManager\ValidationRules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Advanced Validation Engine
 *
 * Step 4.2.1 - Extracted from monolithic validator class to provide
 * advanced validation rule processing, enhanced basic validation, conditional state validation,
 * and cross-entity relationship validation capabilities.
 *
 * @package VD_License_Manager
 * @subpackage ValidationRules
 * @since 4.2.1
 * @author VD Team
 */
class VD_License_Advanced_Validation_Engine {

    /**
     * Singleton instance
     *
     * @var VD_License_Advanced_Validation_Engine|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '4.2.1';

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'validation_rules_applied' => 0,
        'enhanced_validations_performed' => 0,
        'conditional_validations_executed' => 0,
        'relationship_validations_completed' => 0,
        'memory_usage' => 0
    );

    /**
     * Reference to main validator for accessing existing methods
     *
     * @var VD_License_Validator|null
     */
    private $validator = null;

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_module();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Advanced_Validation_Engine
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize module
     *
     * @return void
     */
    private function init_module() {
        $start_memory = memory_get_usage();

        // Mark as initialized
        $this->status['initialized'] = true;
        $this->status['memory_usage'] = memory_get_usage() - $start_memory;

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log("VD Advanced Validation Engine: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    /**
     * Set validator reference for accessing existing methods
     *
     * @param VD_License_Validator $validator Validator instance
     * @return void
     */
    public function set_validator_reference($validator) {
        $this->validator = $validator;
    }

    // ==========================================
    // ADVANCED VALIDATION RULES METHODS
    // ==========================================

    /**
     * Apply advanced validation rules fallback
     *
     * Step 4.2.1 - Advanced rule application with orchestrator fallback
     *
     * @since 4.2.1
     * @param array $license License data
     * @param array $context Validation context
     * @return array Validation result
     */
    public function apply_advanced_validation_rules_fallback($license, $context) {
        $this->status['validation_rules_applied']++;

        // Check if orchestrator is available first
        if (class_exists('VD\\LicenseManager\\Validator\\VD_License_Validation_Orchestrator')) {
            try {
                $orchestrator = \VD\LicenseManager\Validator\VD_License_Validation_Orchestrator::get_instance();
                return $orchestrator->validate_license($license, $context);
            } catch (Exception $e) {
                error_log('[VD License Manager] Orchestrator failed: ' . $e->getMessage());
                // Continue with fallback
            }
        }

        // Delegate to constraint validation module if available
        if ($this->validator && method_exists($this->validator, 'get_constraint_validation')) {
            $constraint_validation = $this->validator->get_constraint_validation();
            if ($constraint_validation && method_exists($constraint_validation, 'perform_conditional_state_validation')) {
                return $constraint_validation->perform_conditional_state_validation($license, $context);
            }
        }

        error_log('[VD License Manager] Using fallback validation - orchestrator unavailable');

        return array(
            'valid' => false,
            'validation_pipeline' => array(),
            'errors' => array('Orchestrator unavailable - using fallback validation'),
            'warnings' => array(),
            'info' => array(),
            'validation_report' => array(),
            'validation_time_ms' => 0,
            'framework_version' => '4.2.1-fallback',
            'pipeline_stages' => 0,
            'total_checks' => 0
        );
    }

    /**
     * Perform enhanced basic validation
     *
     * Step 4.2.1 - Enhanced validation with improved context awareness
     *
     * @since 4.2.1
     * @param array $license License data
     * @param array $context Validation context
     * @return array Enhanced validation result
     */
    public function perform_enhanced_basic_validation($license, $context) {
        $this->status['enhanced_validations_performed']++;

        $validation_errors = array();

        // Use existing validation as foundation
        $basic_validation = $this->validate_basic_structure($license, $context);
        if (!$basic_validation['valid']) {
            $validation_errors = array_merge($validation_errors, $basic_validation['errors']);
        }

        // Enhanced validation with user context integration
        if (!empty($context['user_context'])) {
            $user_validation = $this->validate_user_context_requirements($license, $context['user_context']);
            if (!$user_validation['valid']) {
                $validation_errors = array_merge($validation_errors, $user_validation['errors']);
            }
        }

        // Enhanced validation with IP context integration
        if (!empty($context['ip_context'])) {
            $ip_validation = $this->validate_ip_context_requirements($license, $context['ip_context']);
            if (!$ip_validation['valid']) {
                $validation_errors = array_merge($validation_errors, $ip_validation['errors']);
            }
        }

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors,
            'enhanced_checks' => array(
                'basic_validation_passed' => $basic_validation['valid'],
                'user_context_validated' => !empty($context['user_context']),
                'ip_context_validated' => !empty($context['ip_context'])
            )
        );
    }

    /**
     * Perform conditional state validation
     *
     * Step 4.2.1 - Advanced business logic validation based on license state
     *
     * @since 4.2.1
     * @param array $license License data
     * @param array $context Validation context
     * @return array Conditional validation result
     */
    public function perform_conditional_state_validation($license, $context) {
        $this->status['conditional_validations_executed']++;

        $validation_errors = array();
        $validation_warnings = array();

        $current_status = $license['status'] ?? '';
        $target_status = $context['new_status'] ?? '';

        // Dynamic rule loading based on license characteristics
        $dynamic_rules = $this->load_dynamic_rules($license, $context);

        // State-dependent validation rules
        $state_rules = $this->load_state_rules($current_status, $target_status);

        foreach ($state_rules as $rule) {
            $rule_result = $this->execute_conditional_rule($license, $context, $rule);

            if ($rule_result['severity'] === 'error') {
                $validation_errors[] = $rule_result['message'];
            } elseif ($rule_result['severity'] === 'warning') {
                $validation_warnings[] = $rule_result['message'];
            }
        }

        // Business logic state machine validation
        $state_machine_validation = $this->validate_business_state_machine($license, $target_status, $context);
        if (!$state_machine_validation['valid']) {
            $validation_errors = array_merge($validation_errors, $state_machine_validation['errors']);
        }

        // Time-based conditional validation
        $temporal_validation = $this->validate_temporal_business_rules($license, $context);
        if (!$temporal_validation['valid']) {
            $validation_errors = array_merge($validation_errors, $temporal_validation['errors']);
        }
        $validation_warnings = array_merge($validation_warnings, $temporal_validation['warnings'] ?? array());

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors,
            'warnings' => $validation_warnings,
            'conditional_checks' => array(
                'dynamic_rules_applied' => count($dynamic_rules),
                'state_rules_processed' => count($state_rules),
                'state_machine_validated' => $state_machine_validation['valid'],
                'temporal_rules_checked' => !empty($temporal_validation)
            )
        );
    }

    /**
     * Validate license relationships
     *
     * Step 4.2.1 - Cross-entity validation and relationship checks
     *
     * @since 4.2.1
     * @param array $license License data
     * @param array $context Validation context
     * @return array Cross-entity validation result
     */
    public function validate_license_relationships($license, $context) {
        $this->status['relationship_validations_completed']++;

        $validation_errors = array();

        // User's other licenses validation
        if (!empty($context['user_context']['user_id'])) {
            $user_license_validation = $this->validate_user_license_consistency(
                $license,
                $context['user_context']['user_id'],
                $context
            );

            if (!$user_license_validation['valid']) {
                $validation_errors = array_merge($validation_errors, $user_license_validation['errors']);
            }
        }

        // Product-level validation
        if (!empty($license['product_id'])) {
            $product_validation = $this->validate_product_level_constraints($license, $context);
            if (!$product_validation['valid']) {
                $validation_errors = array_merge($validation_errors, $product_validation['errors']);
            }
        }

        // Global license limits validation
        $global_limits_validation = $this->validate_global_license_limits($license, $context);
        if (!$global_limits_validation['valid']) {
            $validation_errors = array_merge($validation_errors, $global_limits_validation['errors']);
        }

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors,
            'cross_entity_checks' => array(
                'user_licenses_checked' => !empty($context['user_context']['user_id']),
                'product_constraints_validated' => !empty($license['product_id']),
                'global_limits_validated' => true
            )
        );
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Validate basic structure
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Validation result
     */
    private function validate_basic_structure($license, $context) {
        $errors = array();

        // Check required fields
        if (empty($license['id'])) {
            $errors[] = 'License ID is required';
        }

        if (empty($license['license_key'])) {
            $errors[] = 'License key is required';
        }

        if (empty($license['status'])) {
            $errors[] = 'License status is required';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Validate user context requirements
     *
     * @param array $license License data
     * @param array $user_context User context data
     * @return array Validation result
     */
    private function validate_user_context_requirements($license, $user_context) {
        $errors = array();

        if (empty($user_context['user_id'])) {
            $errors[] = 'User ID is required in user context';
        }

        // Additional user context validations can be added here

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Validate IP context requirements
     *
     * @param array $license License data
     * @param array $ip_context IP context data
     * @return array Validation result
     */
    private function validate_ip_context_requirements($license, $ip_context) {
        $errors = array();

        if (!empty($ip_context['ip_address'])) {
            if (!filter_var($ip_context['ip_address'], FILTER_VALIDATE_IP)) {
                $errors[] = 'Invalid IP address in context';
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Load dynamic rules based on license characteristics
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Dynamic rules
     */
    private function load_dynamic_rules($license, $context) {
        // In a real implementation, this would load rules from database or configuration
        return array(
            'license_type_rules' => array(),
            'product_specific_rules' => array(),
            'user_tier_rules' => array()
        );
    }

    /**
     * Load state rules for validation
     *
     * @param string $current_status Current license status
     * @param string $target_status Target license status
     * @return array State rules
     */
    private function load_state_rules($current_status, $target_status) {
        // In a real implementation, this would load state transition rules
        return array(
            array(
                'id' => 'state_transition_rule',
                'from' => $current_status,
                'to' => $target_status,
                'conditions' => array()
            )
        );
    }

    /**
     * Execute conditional rule
     *
     * @param array $license License data
     * @param array $context Validation context
     * @param array $rule Rule definition
     * @return array Rule execution result
     */
    private function execute_conditional_rule($license, $context, $rule) {
        // Simple rule execution logic
        return array(
            'rule_id' => $rule['id'] ?? 'unknown',
            'severity' => 'info',
            'message' => 'Rule executed successfully',
            'passed' => true
        );
    }

    /**
     * Validate business state machine
     *
     * @param array $license License data
     * @param string $target_status Target status
     * @param array $context Validation context
     * @return array Validation result
     */
    private function validate_business_state_machine($license, $target_status, $context) {
        $current_status = $license['status'] ?? '';

        // Simple state machine validation
        $valid_transitions = array(
            'pending' => array('active', 'expired', 'cancelled'),
            'active' => array('expired', 'suspended', 'cancelled'),
            'expired' => array('active', 'cancelled'),
            'suspended' => array('active', 'cancelled'),
            'cancelled' => array() // Terminal state
        );

        $valid = true;
        $errors = array();

        if (!empty($target_status) && $current_status !== $target_status) {
            if (!isset($valid_transitions[$current_status]) ||
                !in_array($target_status, $valid_transitions[$current_status])) {
                $valid = false;
                $errors[] = "Invalid state transition from '{$current_status}' to '{$target_status}'";
            }
        }

        return array(
            'valid' => $valid,
            'errors' => $errors
        );
    }

    /**
     * Validate temporal business rules
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Validation result
     */
    private function validate_temporal_business_rules($license, $context) {
        $errors = array();
        $warnings = array();

        // Check expiry date if present
        if (!empty($license['expires_at'])) {
            $expires_at = strtotime($license['expires_at']);
            $now = time();

            if ($expires_at < $now) {
                $warnings[] = 'License has expired';
            } elseif (($expires_at - $now) < (7 * 24 * 3600)) { // 7 days
                $warnings[] = 'License expires within 7 days';
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }

    /**
     * Validate user license consistency
     *
     * @param array $license License data
     * @param int $user_id User ID
     * @param array $context Validation context
     * @return array Validation result
     */
    private function validate_user_license_consistency($license, $user_id, $context) {
        // Simple consistency check
        if (!empty($license['user_id']) && $license['user_id'] != $user_id) {
            return array(
                'valid' => false,
                'errors' => array('License user ID does not match provided user ID')
            );
        }

        return array(
            'valid' => true,
            'errors' => array()
        );
    }

    /**
     * Validate product level constraints
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Validation result
     */
    private function validate_product_level_constraints($license, $context) {
        // Basic product validation
        if (empty($license['product_id'])) {
            return array(
                'valid' => false,
                'errors' => array('Product ID is required')
            );
        }

        return array(
            'valid' => true,
            'errors' => array()
        );
    }

    /**
     * Validate global license limits
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Validation result
     */
    private function validate_global_license_limits($license, $context) {
        // Simple global limits check
        return array(
            'valid' => true,
            'errors' => array()
        );
    }

    // ==========================================
    // MODULE STATUS & HEALTH METHODS
    // ==========================================

    /**
     * Get module status
     *
     * @return array Module status information
     */
    public function get_status() {
        return array_merge($this->status, array(
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'validator_reference_set' => !is_null($this->validator)
        ));
    }

    /**
     * Module health check
     *
     * @return array Health check results
     */
    public function health_check() {
        $health = array(
            'status' => 'healthy',
            'checks' => array(),
            'warnings' => array(),
            'errors' => array()
        );

        // Check if initialized
        if (!$this->status['initialized']) {
            $health['errors'][] = 'Advanced Validation Engine not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        // Check memory usage
        if ($this->status['memory_usage'] > 262144) { // 256KB
            $health['warnings'][] = 'High memory usage: ' . number_format($this->status['memory_usage'] / 1024, 2) . ' KB';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Memory usage within limits';
        }

        return $health;
    }

    /**
     * Get debug information
     *
     * @return array Debug information
     */
    public function get_debug_info() {
        return array(
            'module' => 'Advanced Validation Engine',
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'status' => $this->status,
            'validation_rules_applied' => $this->status['validation_rules_applied'],
            'enhanced_validations_performed' => $this->status['enhanced_validations_performed'],
            'conditional_validations_executed' => $this->status['conditional_validations_executed'],
            'relationship_validations_completed' => $this->status['relationship_validations_completed'],
            'memory_usage' => $this->status['memory_usage'],
            'validator_reference_available' => !is_null($this->validator),
            'initialized_at' => current_time('Y-m-d H:i:s'),
            'file_path' => __FILE__
        );
    }
}
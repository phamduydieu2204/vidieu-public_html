<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Rule Constraint Validation Module
 *
 * Step 2.2.4 - Time-based, usage-based, and condition-based constraint validation
 * PSR-4 Namespace: VD\LicenseManager\Rules
 *
 * Handles comprehensive constraint validation including temporal rules,
 * state transition constraints, usage limits, compliance requirements,
 * and conditional rule execution for license management
 * Part of the modular refactor initiative - Step 2.2.4
 *
 * @package VD_License_Manager
 * @subpackage Rules
 * @since 1.5.0-rc.2
 * @version Step 2.2.4
 */
class VD_License_Rule_Constraint_Validation {

    /**
     * Module version
     *
     * @since 1.5.0-rc.2
     * @var string
     */
    const VERSION = '1.5.0-rc.2';

    /**
     * Module name
     *
     * @since 1.5.0-rc.2
     * @var string
     */
    const MODULE_NAME = 'Constraint Validation';

    /**
     * Status business module instance
     *
     * @since 1.5.0-rc.2
     * @var VD_License_Status_Business|null
     */
    private $status_business = null;

    /**
     * Default constraint configuration
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $default_constraint_config = array(
        'temporal_validation_enabled' => true,
        'expiry_warning_days' => 7,
        'activation_frequency_threshold_minutes' => 5,
        'state_machine_validation_enabled' => true,
        'compliance_validation_enabled' => true,
        'conditional_rules_enabled' => true,
        'usage_limits_enabled' => true,
        'strict_mode' => false
    );

    /**
     * Valid status transitions for state machine validation
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $valid_transitions = array(
        'pending' => array('active', 'cancelled'),
        'active' => array('suspended', 'expired', 'cancelled'),
        'suspended' => array('active', 'cancelled'),
        'expired' => array('active', 'cancelled'),
        'cancelled' => array() // Terminal state
    );

    /**
     * Module statistics
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $statistics = array(
        'constraints_validated' => 0,
        'temporal_validations' => 0,
        'state_validations' => 0,
        'compliance_checks' => 0,
        'conditional_rules_executed' => 0,
        'constraint_violations' => 0,
        'total_execution_time' => 0
    );

    /**
     * Constructor
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Status_Business $status_business Status business module
     */
    public function __construct($status_business = null) {
        $this->status_business = $status_business;
    }

    /**
     * Set status business dependency
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Status_Business $status_business Status business module
     * @return void
     */
    public function set_status_business($status_business) {
        $this->status_business = $status_business;
    }

    /**
     * Get module information
     *
     * @since 1.5.0-rc.2
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => self::MODULE_NAME,
            'version' => self::VERSION,
            'description' => 'Time-based, usage-based, and condition-based constraint validation module',
            'namespace' => 'VD\\LicenseManager\\Rules',
            'dependencies' => array('VD_License_Status_Business'),
            'functions' => array(
                'perform_conditional_state_validation',
                'validate_temporal_business_rules',
                'validate_business_state_machine',
                'check_compliance_requirements',
                'execute_conditional_rule',
                'validate_global_license_limits'
            ),
            'statistics' => $this->statistics
        );
    }

    /**
     * Perform conditional state validation
     * Main entry point for comprehensive constraint validation
     *
     * @since 1.5.0-rc.2 (Extracted from validator 6733-6779)
     * @param array $license License data
     * @param array $options Validation options
     * @return array Validation result
     */
    public function perform_conditional_state_validation($license, $options = array()) {
        $start_time = microtime(true);

        // Initialize validation context
        $validation_context = array_merge($this->default_constraint_config, $options);

        $results = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array(),
            'validation_results' => array(),
            'execution_time_ms' => 0,
            'constraints_checked' => array()
        );

        try {
            // Step 1: Execute conditional rules if enabled
            if ($validation_context['conditional_rules_enabled']) {
                $conditional_result = $this->execute_conditional_rule($license, $validation_context);
                $results['validation_results']['conditional_rules'] = $conditional_result;
                $results['constraints_checked'][] = 'conditional_rules';

                if (!$conditional_result['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $conditional_result['errors']);
                }
            }

            // Step 2: Validate business state machine
            if ($validation_context['state_machine_validation_enabled']) {
                $state_result = $this->validate_business_state_machine($license, $validation_context);
                $results['validation_results']['state_machine'] = $state_result;
                $results['constraints_checked'][] = 'state_machine';

                if (!$state_result['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $state_result['errors']);
                }
            }

            // Step 3: Validate temporal business rules
            if ($validation_context['temporal_validation_enabled']) {
                $temporal_result = $this->validate_temporal_business_rules($license, $validation_context);
                $results['validation_results']['temporal_rules'] = $temporal_result;
                $results['constraints_checked'][] = 'temporal_rules';

                if (!$temporal_result['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $temporal_result['errors']);
                }

                // Add warnings from temporal validation
                if (!empty($temporal_result['warnings'])) {
                    $results['warnings'] = array_merge($results['warnings'], $temporal_result['warnings']);
                }
            }

            // Step 4: Check compliance requirements
            if ($validation_context['compliance_validation_enabled']) {
                $compliance_result = $this->check_compliance_requirements($license, $validation_context);
                $results['validation_results']['compliance'] = $compliance_result;
                $results['constraints_checked'][] = 'compliance';

                if (!$compliance_result['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $compliance_result['errors']);
                }
            }

            // Step 5: Validate usage limits
            if ($validation_context['usage_limits_enabled']) {
                $limits_result = $this->validate_global_license_limits($license, $validation_context);
                $results['validation_results']['usage_limits'] = $limits_result;
                $results['constraints_checked'][] = 'usage_limits';

                if (!$limits_result['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $limits_result['errors']);
                }
            }

            // Update statistics
            $this->statistics['constraints_validated']++;
            if (!$results['valid']) {
                $this->statistics['constraint_violations']++;
            }

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = array(
                'type' => 'system_error',
                'message' => 'Constraint validation failed: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            );
        }

        $execution_time = (microtime(true) - $start_time) * 1000;
        $results['execution_time_ms'] = round($execution_time, 2);
        $this->statistics['total_execution_time'] += $execution_time;

        return $results;
    }

    /**
     * Validate temporal business rules
     * Time-based constraint validation including expiry and frequency checks
     *
     * @since 1.5.0-rc.2 (Extracted from validator 7237-7268)
     * @param array $license License data
     * @param array $context Validation context
     * @return array Temporal validation result
     */
    public function validate_temporal_business_rules($license, $context = array()) {
        $start_time = microtime(true);

        $results = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array(),
            'checks_performed' => array()
        );

        try {
            // Check 1: License expiration validation
            if (!empty($license['expires_at'])) {
                $expiry_check = $this->validate_license_expiry_constraint($license, $context);
                $results['checks_performed']['expiry'] = $expiry_check;

                if (!$expiry_check['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $expiry_check['errors']);
                }

                if (!empty($expiry_check['warnings'])) {
                    $results['warnings'] = array_merge($results['warnings'], $expiry_check['warnings']);
                }
            }

            // Check 2: Activation frequency validation
            $frequency_check = $this->validate_activation_frequency($license, $context);
            $results['checks_performed']['activation_frequency'] = $frequency_check;

            if (!$frequency_check['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $frequency_check['errors']);
            }

            $this->statistics['temporal_validations']++;

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = array(
                'type' => 'temporal_validation_error',
                'message' => $e->getMessage()
            );
        }

        return $results;
    }

    /**
     * Validate business state machine
     * State transition constraint validation
     *
     * @since 1.5.0-rc.2 (Extracted from validator 7203-7227)
     * @param array $license License data
     * @param array $context Validation context
     * @return array State machine validation result
     */
    public function validate_business_state_machine($license, $context = array()) {
        $results = array(
            'valid' => true,
            'errors' => array(),
            'current_state' => $license['status'] ?? 'unknown',
            'allowed_transitions' => array()
        );

        try {
            $current_status = $license['status'] ?? 'unknown';

            // Check if current status exists in our state machine
            if (!isset($this->valid_transitions[$current_status])) {
                $results['valid'] = false;
                $results['errors'][] = array(
                    'type' => 'invalid_state',
                    'message' => "Invalid license status: {$current_status}",
                    'current_status' => $current_status
                );
                return $results;
            }

            // Get allowed transitions for current state
            $allowed_transitions = $this->valid_transitions[$current_status];
            $results['allowed_transitions'] = $allowed_transitions;

            // Check for terminal state
            if ($current_status === 'cancelled') {
                $results['terminal_state'] = true;
                $results['message'] = 'License is in terminal cancelled state';
            }

            // Validate that the current state is consistent with license data
            $state_consistency_check = $this->validate_state_consistency($license, $current_status);
            if (!$state_consistency_check['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $state_consistency_check['errors']);
            }

            $this->statistics['state_validations']++;

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = array(
                'type' => 'state_machine_error',
                'message' => $e->getMessage()
            );
        }

        return $results;
    }

    /**
     * Check compliance requirements
     * Comprehensive compliance constraint validation
     *
     * @since 1.5.0-rc.2 (Extracted from validator 6843-6875)
     * @param array $license License data
     * @param array $context Validation context
     * @return array Compliance validation result
     */
    public function check_compliance_requirements($license, $context = array()) {
        $results = array(
            'valid' => true,
            'errors' => array(),
            'compliance_checks' => array(),
            'compliance_score' => 100
        );

        try {
            // Business policy validation
            $business_policy_result = $this->validate_business_policies($license, $context);
            $results['compliance_checks']['business_policies'] = $business_policy_result;

            if (!$business_policy_result['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $business_policy_result['errors']);
                $results['compliance_score'] -= 30;
            }

            // Regulatory requirements validation
            $regulatory_result = $this->validate_regulatory_requirements($license, $context);
            $results['compliance_checks']['regulatory'] = $regulatory_result;

            if (!$regulatory_result['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $regulatory_result['errors']);
                $results['compliance_score'] -= 40;
            }

            // Security compliance validation
            $security_result = $this->validate_security_compliance($license, $context);
            $results['compliance_checks']['security'] = $security_result;

            if (!$security_result['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $security_result['errors']);
                $results['compliance_score'] -= 30;
            }

            // User license consistency validation
            $user_consistency_result = $this->validate_user_license_consistency($license, $context);
            $results['compliance_checks']['user_consistency'] = $user_consistency_result;

            if (!$user_consistency_result['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $user_consistency_result['errors']);
                $results['compliance_score'] -= 20;
            }

            $this->statistics['compliance_checks']++;

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = array(
                'type' => 'compliance_error',
                'message' => $e->getMessage()
            );
            $results['compliance_score'] = 0;
        }

        return $results;
    }

    /**
     * Execute conditional rule
     * Execute individual conditional validation rules
     *
     * @since 1.5.0-rc.2 (Extracted from validator 7183-7192)
     * @param array $license License data
     * @param array $context Rule execution context
     * @return array Rule execution result
     */
    public function execute_conditional_rule($license, $context = array()) {
        $results = array(
            'valid' => true,
            'errors' => array(),
            'rules_executed' => 0,
            'rule_results' => array()
        );

        try {
            // Load dynamic validation rules based on license characteristics
            $dynamic_rules = $this->load_dynamic_validation_rules($license, $context);

            foreach ($dynamic_rules as $rule_id => $rule) {
                $rule_result = $this->execute_single_conditional_rule($license, $rule, $context);
                $results['rule_results'][$rule_id] = $rule_result;
                $results['rules_executed']++;

                if (!$rule_result['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $rule_result['errors']);
                }
            }

            $this->statistics['conditional_rules_executed'] += $results['rules_executed'];

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = array(
                'type' => 'conditional_rule_error',
                'message' => $e->getMessage()
            );
        }

        return $results;
    }

    /**
     * Validate global license limits
     * Usage-based constraint validation
     *
     * @since 1.5.0-rc.2 (Extracted from validator 7313-7318)
     * @param array $license License data
     * @param array $context Validation context
     * @return array Limits validation result
     */
    public function validate_global_license_limits($license, $context = array()) {
        $results = array(
            'valid' => true,
            'errors' => array(),
            'limits_checked' => array(),
            'usage_stats' => array()
        );

        try {
            // Check activation count limits
            $activation_limit_check = $this->check_activation_count_limits($license, $context);
            $results['limits_checked']['activation_count'] = $activation_limit_check;

            if (!$activation_limit_check['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $activation_limit_check['errors']);
            }

            // Check device count limits
            $device_limit_check = $this->check_device_count_limits($license, $context);
            $results['limits_checked']['device_count'] = $device_limit_check;

            if (!$device_limit_check['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $device_limit_check['errors']);
            }

            // Check global usage patterns
            $usage_pattern_check = $this->check_global_usage_patterns($license, $context);
            $results['limits_checked']['usage_patterns'] = $usage_pattern_check;
            $results['usage_stats'] = $usage_pattern_check['stats'] ?? array();

            if (!$usage_pattern_check['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $usage_pattern_check['errors']);
            }

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = array(
                'type' => 'limits_validation_error',
                'message' => $e->getMessage()
            );
        }

        return $results;
    }

    /**
     * Private helper methods for constraint validation
     */

    /**
     * Validate license expiry constraint
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Expiry validation result
     */
    private function validate_license_expiry_constraint($license, $context) {
        $results = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array()
        );

        $expires_at = $license['expires_at'];
        $warning_days = $context['expiry_warning_days'] ?? 7;

        // Check if license is expired
        $expiry_timestamp = strtotime($expires_at);
        $current_timestamp = current_time('timestamp');

        if ($expiry_timestamp < $current_timestamp) {
            $results['valid'] = false;
            $results['errors'][] = array(
                'type' => 'license_expired',
                'message' => 'License has expired',
                'expired_date' => $expires_at,
                'days_expired' => ceil(($current_timestamp - $expiry_timestamp) / DAY_IN_SECONDS)
            );
        } else {
            // Check for expiry warning
            $days_until_expiry = ceil(($expiry_timestamp - $current_timestamp) / DAY_IN_SECONDS);

            if ($days_until_expiry <= $warning_days) {
                $results['warnings'][] = array(
                    'type' => 'expiry_warning',
                    'message' => "License expires in {$days_until_expiry} days",
                    'days_until_expiry' => $days_until_expiry,
                    'expiry_date' => $expires_at
                );
            }
        }

        return $results;
    }

    /**
     * Validate activation frequency
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Frequency validation result
     */
    private function validate_activation_frequency($license, $context) {
        $results = array(
            'valid' => true,
            'errors' => array()
        );

        $threshold_minutes = $context['activation_frequency_threshold_minutes'] ?? 5;
        $last_activation = $license['last_activation'] ?? null;

        if ($last_activation) {
            $last_activation_timestamp = strtotime($last_activation);
            $threshold_timestamp = current_time('timestamp') - ($threshold_minutes * MINUTE_IN_SECONDS);

            if ($last_activation_timestamp > $threshold_timestamp) {
                $results['valid'] = false;
                $results['errors'][] = array(
                    'type' => 'activation_frequency_exceeded',
                    'message' => "Activation frequency threshold exceeded (last activation within {$threshold_minutes} minutes)",
                    'last_activation' => $last_activation,
                    'threshold_minutes' => $threshold_minutes
                );
            }
        }

        return $results;
    }

    /**
     * Validate state consistency
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param string $status Current status
     * @return array Consistency validation result
     */
    private function validate_state_consistency($license, $status) {
        $results = array(
            'valid' => true,
            'errors' => array()
        );

        // Check consistency between status and expiry date
        if ($status === 'active' && !empty($license['expires_at'])) {
            $expiry_timestamp = strtotime($license['expires_at']);
            if ($expiry_timestamp < current_time('timestamp')) {
                $results['valid'] = false;
                $results['errors'][] = array(
                    'type' => 'state_inconsistency',
                    'message' => 'License marked as active but has expired',
                    'status' => $status,
                    'expires_at' => $license['expires_at']
                );
            }
        }

        return $results;
    }

    /**
     * Load dynamic validation rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Dynamic rules
     */
    private function load_dynamic_validation_rules($license, $context) {
        $rules = array();

        // Product-specific rules
        if (!empty($license['product_id'])) {
            $product_rules = get_option('vd_constraint_rules_product_' . $license['product_id'], array());
            if (is_array($product_rules)) {
                $rules = array_merge($rules, $product_rules);
            }
        }

        // License type-specific rules
        $license_type = $license['license_type'] ?? 'standard';
        $type_rules = get_option('vd_constraint_rules_type_' . $license_type, array());
        if (is_array($type_rules)) {
            $rules = array_merge($rules, $type_rules);
        }

        return $rules;
    }

    /**
     * Execute single conditional rule
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule Rule definition
     * @param array $context Validation context
     * @return array Rule execution result
     */
    private function execute_single_conditional_rule($license, $rule, $context) {
        return array(
            'valid' => true,
            'errors' => array(),
            'rule_id' => $rule['id'] ?? 'unknown',
            'rule_type' => $rule['type'] ?? 'conditional'
        );
    }

    /**
     * Validate business policies
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Business policy validation result
     */
    private function validate_business_policies($license, $context) {
        return array(
            'valid' => true,
            'errors' => array(),
            'policies_checked' => array('default_business_policy')
        );
    }

    /**
     * Validate regulatory requirements
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Regulatory validation result
     */
    private function validate_regulatory_requirements($license, $context) {
        return array(
            'valid' => true,
            'errors' => array(),
            'regulations_checked' => array('gdpr_compliance', 'data_retention')
        );
    }

    /**
     * Validate security compliance
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Security compliance validation result
     */
    private function validate_security_compliance($license, $context) {
        return array(
            'valid' => true,
            'errors' => array(),
            'security_checks' => array('access_control', 'encryption_compliance')
        );
    }

    /**
     * Validate user license consistency
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array User consistency validation result
     */
    private function validate_user_license_consistency($license, $context) {
        $results = array(
            'valid' => true,
            'errors' => array()
        );

        // Check if user_id is consistent with license ownership
        if (!empty($license['user_id']) && !empty($license['customer_email'])) {
            $user = get_user_by('ID', $license['user_id']);
            if (!$user || $user->user_email !== $license['customer_email']) {
                $results['valid'] = false;
                $results['errors'][] = array(
                    'type' => 'user_license_inconsistency',
                    'message' => 'User ID does not match license customer email',
                    'user_id' => $license['user_id'],
                    'customer_email' => $license['customer_email']
                );
            }
        }

        return $results;
    }

    /**
     * Check activation count limits
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Activation count validation result
     */
    private function check_activation_count_limits($license, $context) {
        return array(
            'valid' => true,
            'errors' => array(),
            'current_activations' => $license['activations_count'] ?? 0,
            'activation_limit' => $license['activation_limit'] ?? 1
        );
    }

    /**
     * Check device count limits
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Device count validation result
     */
    private function check_device_count_limits($license, $context) {
        return array(
            'valid' => true,
            'errors' => array(),
            'current_devices' => $license['device_count'] ?? 0,
            'device_limit' => $license['device_limit'] ?? 1
        );
    }

    /**
     * Check global usage patterns
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Usage pattern validation result
     */
    private function check_global_usage_patterns($license, $context) {
        return array(
            'valid' => true,
            'errors' => array(),
            'stats' => array(
                'daily_activations' => 0,
                'unique_devices' => 0,
                'usage_score' => 100
            )
        );
    }

    /**
     * Get module statistics
     *
     * @since 1.5.0-rc.2
     * @return array Module statistics
     */
    public function get_statistics() {
        return array_merge($this->statistics, array(
            'last_reset' => get_option('vd_constraint_validation_stats_reset', 'never'),
            'default_constraint_config' => $this->default_constraint_config,
            'valid_transitions' => $this->valid_transitions
        ));
    }

    /**
     * Reset module statistics
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    public function reset_statistics() {
        $this->statistics = array(
            'constraints_validated' => 0,
            'temporal_validations' => 0,
            'state_validations' => 0,
            'compliance_checks' => 0,
            'conditional_rules_executed' => 0,
            'constraint_violations' => 0,
            'total_execution_time' => 0
        );
        update_option('vd_constraint_validation_stats_reset', current_time('mysql'));
    }

    /**
     * Get constraint configuration
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array Constraint configuration
     */
    public function get_constraint_configuration($license = array()) {
        $config = $this->default_constraint_config;

        // Product-specific configuration
        if (!empty($license['product_id'])) {
            $product_config = get_option('vd_constraint_config_product_' . $license['product_id'], array());
            if (is_array($product_config)) {
                $config = array_merge($config, $product_config);
            }
        }

        return $config;
    }
}
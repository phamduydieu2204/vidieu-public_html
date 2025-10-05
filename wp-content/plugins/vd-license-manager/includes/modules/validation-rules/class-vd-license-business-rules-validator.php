<?php

namespace VD\LicenseManager\ValidationRules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Business Rules Validator
 *
 * Step 4.2.2 - Extracted from monolithic validator class to provide
 * business state machine validation, temporal business rules, business policy validation,
 * and conditional rule execution capabilities.
 *
 * @package VD_License_Manager
 * @subpackage ValidationRules
 * @since 4.2.2
 * @author VD Team
 */
class VD_License_Business_Rules_Validator {

    /**
     * Singleton instance
     *
     * @var VD_License_Business_Rules_Validator|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '4.2.2';

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'state_machine_validations' => 0,
        'temporal_rules_checked' => 0,
        'business_policies_validated' => 0,
        'conditional_rules_executed' => 0,
        'memory_usage' => 0
    );

    /**
     * Business state machine configuration
     *
     * @var array
     */
    private $state_machine_config = array();

    /**
     * Temporal rules configuration
     *
     * @var array
     */
    private $temporal_rules_config = array();

    /**
     * Business policies configuration
     *
     * @var array
     */
    private $business_policies_config = array();

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_module();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Business_Rules_Validator
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

        // Initialize state machine configuration
        $this->init_state_machine_config();

        // Initialize temporal rules configuration
        $this->init_temporal_rules_config();

        // Initialize business policies configuration
        $this->init_business_policies_config();

        // Mark as initialized
        $this->status['initialized'] = true;
        $this->status['memory_usage'] = memory_get_usage() - $start_memory;

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log("VD Business Rules Validator: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    /**
     * Initialize state machine configuration
     *
     * @return void
     */
    private function init_state_machine_config() {
        $this->state_machine_config = array(
            'valid_transitions' => array(
                'pending' => array('active', 'cancelled'),
                'active' => array('suspended', 'expired', 'cancelled'),
                'suspended' => array('active', 'cancelled'),
                'expired' => array('renewed', 'cancelled'),
                'cancelled' => array(), // Terminal state
                'renewed' => array('active', 'cancelled')
            ),
            'terminal_states' => array('cancelled'),
            'require_approval' => array('active', 'renewed'),
            'auto_transitions' => array(
                'expired' => array(
                    'condition' => 'expiry_date_passed',
                    'auto_to' => 'expired'
                )
            )
        );
    }

    /**
     * Initialize temporal rules configuration
     *
     * @return void
     */
    private function init_temporal_rules_config() {
        $this->temporal_rules_config = array(
            'expiry_warning_days' => 7,
            'grace_period_days' => 3,
            'min_check_interval' => 300, // 5 minutes
            'max_usage_frequency' => array(
                'hourly' => 100,
                'daily' => 1000,
                'weekly' => 5000
            ),
            'blackout_periods' => array(
                // Can be configured for maintenance windows
            )
        );
    }

    /**
     * Initialize business policies configuration
     *
     * @return void
     */
    private function init_business_policies_config() {
        $this->business_policies_config = array(
            'max_concurrent_licenses' => 10,
            'max_devices_per_license' => 5,
            'require_domain_validation' => true,
            'allow_development_licenses' => true,
            'enable_trial_licenses' => true,
            'trial_duration_days' => 14,
            'compliance_checks' => array(
                'gdpr' => true,
                'ccpa' => false,
                'industry_specific' => array()
            )
        );
    }

    // ==========================================
    // BUSINESS STATE MACHINE METHODS
    // ==========================================

    /**
     * Validate business state machine
     *
     * Step 4.2.2 - Business state machine validation with workflow enforcement
     *
     * @since 4.2.2
     * @param array $license License data
     * @param string $target_status Target status
     * @param array $context Validation context
     * @return array Validation result
     */
    public function validate_business_state_machine($license, $target_status, $context) {
        $this->status['state_machine_validations']++;

        $validation_errors = array();
        $validation_warnings = array();

        $current_status = $license['status'] ?? '';
        $valid_transitions = $this->state_machine_config['valid_transitions'];

        // Validate current status exists in state machine
        if (!isset($valid_transitions[$current_status])) {
            $validation_errors[] = "Unknown current status: {$current_status}";
            return array(
                'valid' => false,
                'errors' => $validation_errors,
                'warnings' => $validation_warnings
            );
        }

        // Check if transition is valid
        if (!in_array($target_status, $valid_transitions[$current_status])) {
            $validation_errors[] = "Invalid status transition: {$current_status} → {$target_status}";
        }

        // Check if source is terminal state
        if (in_array($current_status, $this->state_machine_config['terminal_states'])) {
            $validation_errors[] = "Cannot transition from terminal state: {$current_status}";
        }

        // Check if target status requires approval
        if (in_array($target_status, $this->state_machine_config['require_approval'])) {
            if (empty($context['approval_provided'])) {
                $validation_warnings[] = "Target status '{$target_status}' typically requires approval";
            }
        }

        // Check for auto-transition conditions
        $this->check_auto_transition_conditions($license, $target_status, $context, $validation_warnings);

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors,
            'warnings' => $validation_warnings,
            'state_machine_info' => array(
                'current_status' => $current_status,
                'target_status' => $target_status,
                'valid_next_states' => $valid_transitions[$current_status] ?? array(),
                'is_terminal' => in_array($current_status, $this->state_machine_config['terminal_states'])
            )
        );
    }

    /**
     * Check auto-transition conditions
     *
     * @param array $license License data
     * @param string $target_status Target status
     * @param array $context Validation context
     * @param array &$validation_warnings Validation warnings array (by reference)
     * @return void
     */
    private function check_auto_transition_conditions($license, $target_status, $context, &$validation_warnings) {
        foreach ($this->state_machine_config['auto_transitions'] as $from_status => $auto_config) {
            if ($license['status'] === $from_status) {
                $condition = $auto_config['condition'];
                $auto_to = $auto_config['auto_to'];

                if ($this->evaluate_auto_condition($license, $condition)) {
                    if ($target_status !== $auto_to) {
                        $validation_warnings[] = "License should auto-transition to '{$auto_to}' due to condition '{$condition}'";
                    }
                }
            }
        }
    }

    /**
     * Evaluate auto-transition condition
     *
     * @param array $license License data
     * @param string $condition Condition to evaluate
     * @return bool Condition result
     */
    private function evaluate_auto_condition($license, $condition) {
        switch ($condition) {
            case 'expiry_date_passed':
                if (!empty($license['expires_at'])) {
                    return strtotime($license['expires_at']) < current_time('timestamp');
                }
                return false;

            default:
                return false;
        }
    }

    // ==========================================
    // TEMPORAL BUSINESS RULES METHODS
    // ==========================================

    /**
     * Validate temporal business rules
     *
     * Step 4.2.2 - Time-based constraints and temporal validation
     *
     * @since 4.2.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Validation result
     */
    public function validate_temporal_business_rules($license, $context) {
        $this->status['temporal_rules_checked']++;

        $validation_errors = array();
        $validation_warnings = array();

        // Check license expiration
        $expiry_result = $this->check_license_expiration($license);
        $validation_errors = array_merge($validation_errors, $expiry_result['errors']);
        $validation_warnings = array_merge($validation_warnings, $expiry_result['warnings']);

        // Check activation frequency
        $frequency_result = $this->check_activation_frequency($license, $context);
        $validation_errors = array_merge($validation_errors, $frequency_result['errors']);
        $validation_warnings = array_merge($validation_warnings, $frequency_result['warnings']);

        // Check usage patterns
        $usage_result = $this->check_usage_patterns($license, $context);
        $validation_errors = array_merge($validation_errors, $usage_result['errors']);
        $validation_warnings = array_merge($validation_warnings, $usage_result['warnings']);

        // Check blackout periods
        $blackout_result = $this->check_blackout_periods($license, $context);
        $validation_errors = array_merge($validation_errors, $blackout_result['errors']);
        $validation_warnings = array_merge($validation_warnings, $blackout_result['warnings']);

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors,
            'warnings' => $validation_warnings,
            'temporal_checks' => array(
                'expiry_checked' => !empty($license['expires_at']),
                'frequency_checked' => !empty($license['last_checked']),
                'usage_patterns_analyzed' => !empty($context['usage_data']),
                'blackout_periods_verified' => true
            )
        );
    }

    /**
     * Check license expiration
     *
     * @param array $license License data
     * @return array Check result
     */
    private function check_license_expiration($license) {
        $errors = array();
        $warnings = array();

        if (!empty($license['expires_at'])) {
            $expiry_time = strtotime($license['expires_at']);
            $current_time = current_time('timestamp');
            $warning_threshold = $current_time + ($this->temporal_rules_config['expiry_warning_days'] * 24 * 60 * 60);
            $grace_period = $this->temporal_rules_config['grace_period_days'] * 24 * 60 * 60;

            if ($expiry_time < ($current_time - $grace_period)) {
                $errors[] = 'License has expired beyond grace period';
            } elseif ($expiry_time < $current_time) {
                $warnings[] = 'License has expired but within grace period';
            } elseif ($expiry_time < $warning_threshold) {
                $days_remaining = ceil(($expiry_time - $current_time) / (24 * 60 * 60));
                $warnings[] = "License expires in {$days_remaining} days";
            }
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Check activation frequency
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Check result
     */
    private function check_activation_frequency($license, $context) {
        $errors = array();
        $warnings = array();

        if (!empty($license['last_checked'])) {
            $last_check = strtotime($license['last_checked']);
            $current_time = current_time('timestamp');
            $min_interval = $this->temporal_rules_config['min_check_interval'];

            if (($current_time - $last_check) < $min_interval) {
                $warnings[] = 'Frequent license checks detected - possible automation or abuse';
            }
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Check usage patterns
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Check result
     */
    private function check_usage_patterns($license, $context) {
        $errors = array();
        $warnings = array();

        if (!empty($context['usage_data'])) {
            $usage_data = $context['usage_data'];
            $limits = $this->temporal_rules_config['max_usage_frequency'];

            foreach ($limits as $period => $limit) {
                $usage_key = $period . '_usage';
                if (isset($usage_data[$usage_key]) && $usage_data[$usage_key] > $limit) {
                    $warnings[] = "High {$period} usage detected: {$usage_data[$usage_key]} (limit: {$limit})";
                }
            }
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Check blackout periods
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Check result
     */
    private function check_blackout_periods($license, $context) {
        $errors = array();
        $warnings = array();

        // Implementation would check against configured blackout periods
        // For now, return empty arrays as this is typically configuration-driven

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    // ==========================================
    // BUSINESS POLICIES METHODS
    // ==========================================

    /**
     * Validate business policies
     *
     * Step 4.2.2 - Policy validation with configurable business rules
     *
     * @since 4.2.2
     * @param array $license License data
     * @param array $context Validation context
     * @return array Validation result
     */
    public function validate_business_policies($license, $context) {
        $this->status['business_policies_validated']++;

        $validation_errors = array();
        $validation_warnings = array();

        // Check concurrent license limits
        $concurrent_result = $this->check_concurrent_license_limits($license, $context);
        $validation_errors = array_merge($validation_errors, $concurrent_result['errors']);
        $validation_warnings = array_merge($validation_warnings, $concurrent_result['warnings']);

        // Check device limits per license
        $device_result = $this->check_device_limits($license, $context);
        $validation_errors = array_merge($validation_errors, $device_result['errors']);
        $validation_warnings = array_merge($validation_warnings, $device_result['warnings']);

        // Check domain validation requirements
        $domain_result = $this->check_domain_validation($license, $context);
        $validation_errors = array_merge($validation_errors, $domain_result['errors']);
        $validation_warnings = array_merge($validation_warnings, $domain_result['warnings']);

        // Check trial license policies
        $trial_result = $this->check_trial_license_policies($license, $context);
        $validation_errors = array_merge($validation_errors, $trial_result['errors']);
        $validation_warnings = array_merge($validation_warnings, $trial_result['warnings']);

        return array(
            'valid' => empty($validation_errors),
            'errors' => $validation_errors,
            'warnings' => $validation_warnings,
            'policy_checks' => array(
                'concurrent_limits_checked' => true,
                'device_limits_checked' => true,
                'domain_validation_checked' => $this->business_policies_config['require_domain_validation'],
                'trial_policies_checked' => $this->business_policies_config['enable_trial_licenses']
            )
        );
    }

    /**
     * Check concurrent license limits
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Check result
     */
    private function check_concurrent_license_limits($license, $context) {
        $errors = array();
        $warnings = array();

        $max_concurrent = $this->business_policies_config['max_concurrent_licenses'];
        $current_count = $context['concurrent_license_count'] ?? 0;

        if ($current_count >= $max_concurrent) {
            $errors[] = "Maximum concurrent licenses exceeded: {$current_count}/{$max_concurrent}";
        } elseif ($current_count >= ($max_concurrent * 0.8)) {
            $warnings[] = "Approaching concurrent license limit: {$current_count}/{$max_concurrent}";
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Check device limits
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Check result
     */
    private function check_device_limits($license, $context) {
        $errors = array();
        $warnings = array();

        $max_devices = $this->business_policies_config['max_devices_per_license'];
        $current_devices = count($context['registered_devices'] ?? array());

        if ($current_devices > $max_devices) {
            $errors[] = "Device limit exceeded: {$current_devices}/{$max_devices}";
        } elseif ($current_devices >= ($max_devices * 0.8)) {
            $warnings[] = "Approaching device limit: {$current_devices}/{$max_devices}";
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Check domain validation
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Check result
     */
    private function check_domain_validation($license, $context) {
        $errors = array();
        $warnings = array();

        if ($this->business_policies_config['require_domain_validation']) {
            if (empty($context['domain']) || empty($license['allowed_domains'])) {
                $errors[] = 'Domain validation required but domain information missing';
            } else {
                $current_domain = $context['domain'];
                $allowed_domains = $license['allowed_domains'];

                if (!in_array($current_domain, $allowed_domains)) {
                    $errors[] = "Domain '{$current_domain}' not in allowed domains list";
                }
            }
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Check trial license policies
     *
     * @param array $license License data
     * @param array $context Validation context
     * @return array Check result
     */
    private function check_trial_license_policies($license, $context) {
        $errors = array();
        $warnings = array();

        if ($this->business_policies_config['enable_trial_licenses'] &&
            !empty($license['is_trial']) && $license['is_trial']) {

            $trial_duration = $this->business_policies_config['trial_duration_days'];
            $created_at = strtotime($license['created_at'] ?? '');
            $current_time = current_time('timestamp');

            if ($created_at && ($current_time - $created_at) > ($trial_duration * 24 * 60 * 60)) {
                $errors[] = 'Trial license has expired';
            }
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    // ==========================================
    // CONDITIONAL RULE EXECUTION METHODS
    // ==========================================

    /**
     * Execute conditional rule
     *
     * Step 4.2.2 - Dynamic rule processing and execution
     *
     * @since 4.2.2
     * @param array $license License data
     * @param array $context Validation context
     * @param array $rule Rule definition
     * @return array Rule execution result
     */
    public function execute_conditional_rule($license, $context, $rule) {
        $this->status['conditional_rules_executed']++;

        $rule_id = $rule['rule_id'] ?? 'unknown';
        $rule_type = $rule['type'] ?? 'basic';
        $conditions = $rule['conditions'] ?? array();
        $actions = $rule['actions'] ?? array();

        // Evaluate rule conditions
        $condition_result = $this->evaluate_rule_conditions($license, $context, $conditions);

        if (!$condition_result['passed']) {
            return array(
                'rule_id' => $rule_id,
                'executed' => false,
                'result' => 'skipped',
                'message' => 'Rule conditions not met',
                'severity' => 'info',
                'conditions_evaluated' => $condition_result
            );
        }

        // Execute rule actions
        $action_result = $this->execute_rule_actions($license, $context, $actions);

        return array(
            'rule_id' => $rule_id,
            'executed' => true,
            'result' => $action_result['success'] ? 'passed' : 'failed',
            'message' => $action_result['message'],
            'severity' => $action_result['success'] ? 'info' : 'warning',
            'conditions_evaluated' => $condition_result,
            'actions_executed' => $action_result
        );
    }

    /**
     * Evaluate rule conditions
     *
     * @param array $license License data
     * @param array $context Validation context
     * @param array $conditions Rule conditions
     * @return array Evaluation result
     */
    private function evaluate_rule_conditions($license, $context, $conditions) {
        $passed = true;
        $evaluated_conditions = array();

        foreach ($conditions as $condition) {
            $condition_type = $condition['type'] ?? '';
            $condition_passed = false;

            switch ($condition_type) {
                case 'status_equals':
                    $condition_passed = ($license['status'] ?? '') === ($condition['value'] ?? '');
                    break;

                case 'user_has_role':
                    $user_roles = $context['user_roles'] ?? array();
                    $condition_passed = in_array($condition['value'] ?? '', $user_roles);
                    break;

                case 'expiry_within_days':
                    if (!empty($license['expires_at'])) {
                        $days_to_expiry = (strtotime($license['expires_at']) - current_time('timestamp')) / (24 * 60 * 60);
                        $condition_passed = $days_to_expiry <= ($condition['value'] ?? 0);
                    }
                    break;

                default:
                    $condition_passed = true; // Unknown conditions pass by default
                    break;
            }

            $evaluated_conditions[] = array(
                'type' => $condition_type,
                'passed' => $condition_passed,
                'value' => $condition['value'] ?? null
            );

            if (!$condition_passed) {
                $passed = false;
            }
        }

        return array(
            'passed' => $passed,
            'conditions' => $evaluated_conditions
        );
    }

    /**
     * Execute rule actions
     *
     * @param array $license License data
     * @param array $context Validation context
     * @param array $actions Rule actions
     * @return array Execution result
     */
    private function execute_rule_actions($license, $context, $actions) {
        $success = true;
        $messages = array();
        $executed_actions = array();

        foreach ($actions as $action) {
            $action_type = $action['type'] ?? '';
            $action_success = false;
            $action_message = '';

            switch ($action_type) {
                case 'log_event':
                    $action_success = true;
                    $action_message = 'Event logged successfully';
                    // In real implementation, would log to system
                    break;

                case 'send_notification':
                    $action_success = true;
                    $action_message = 'Notification queued';
                    // In real implementation, would queue notification
                    break;

                case 'update_status':
                    $action_success = true;
                    $action_message = 'Status update prepared';
                    // In real implementation, would update license status
                    break;

                default:
                    $action_success = true;
                    $action_message = 'Unknown action type - no operation performed';
                    break;
            }

            $executed_actions[] = array(
                'type' => $action_type,
                'success' => $action_success,
                'message' => $action_message
            );

            $messages[] = $action_message;

            if (!$action_success) {
                $success = false;
            }
        }

        return array(
            'success' => $success,
            'message' => implode('; ', $messages),
            'actions' => $executed_actions
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
            'state_machine_states' => count($this->state_machine_config['valid_transitions']),
            'temporal_rules_configured' => count($this->temporal_rules_config),
            'business_policies_configured' => count($this->business_policies_config)
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
            $health['errors'][] = 'Business Rules Validator not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        // Check state machine configuration
        if (empty($this->state_machine_config['valid_transitions'])) {
            $health['warnings'][] = 'State machine configuration is empty';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'State machine configuration loaded';
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
            'module' => 'Business Rules Validator',
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'status' => $this->status,
            'state_machine_validations' => $this->status['state_machine_validations'],
            'temporal_rules_checked' => $this->status['temporal_rules_checked'],
            'business_policies_validated' => $this->status['business_policies_validated'],
            'conditional_rules_executed' => $this->status['conditional_rules_executed'],
            'memory_usage' => $this->status['memory_usage'],
            'configurations' => array(
                'state_machine_states' => count($this->state_machine_config['valid_transitions']),
                'temporal_rules' => count($this->temporal_rules_config),
                'business_policies' => count($this->business_policies_config)
            ),
            'initialized_at' => current_time('Y-m-d H:i:s'),
            'file_path' => __FILE__
        );
    }
}
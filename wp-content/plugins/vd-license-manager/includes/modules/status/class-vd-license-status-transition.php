<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Status Transition Manager
 *
 * Handles license status transitions, validation, and business rules
 * Extracted from monolithic validator in Step 1.7 of refactor
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */
class VD_License_Status_Transition {

    /**
     * Singleton instance
     *
     * @var VD_License_Status_Transition|null
     */
    private static $instance = null;

    /**
     * Status enum module instance
     *
     * @since 1.5.0-rc.1
     * @var VD_License_Status_Enum|null
     */
    private $status_enum = null;

    /**
     * Transition policies configuration
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $transition_policies = array(
        'require_admin_approval' => array('revoked', 'suspended'),
        'allow_expired_to_active' => false,
        'allow_revoked_transitions' => false,
        'grace_period_enabled' => true,
        'auto_transition_enabled' => true,
        'audit_transitions' => true
    );

    /**
     * Status hierarchy levels (for transition type determination)
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $status_hierarchy = array(
        'revoked'   => 1, // Highest priority (terminal)
        'expired'   => 2,
        'suspended' => 3,
        'active'    => 4,
        'inactive'  => 5,
        'pending'   => 6  // Lowest priority
    );

    /**
     * Grace period applicable transitions
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $grace_applicable_transitions = array(
        'expired' => array('active', 'suspended'),
        'suspended' => array('active'),
        'inactive' => array('active')
    );

    /**
     * Allowed automatic transitions configuration
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $automatic_transitions = array(
        'pending_to_active' => array(
            'type' => 'activation',
            'requires_audit' => true,
            'constraints' => array()
        ),
        'active_to_expired' => array(
            'type' => 'expiration',
            'requires_audit' => true,
            'constraints' => array('expiry_date_passed')
        ),
        'expired_to_active' => array(
            'type' => 'renewal',
            'requires_audit' => true,
            'constraints' => array('payment_verified')
        ),
        'inactive_to_active' => array(
            'type' => 'activation',
            'requires_audit' => false,
            'constraints' => array()
        )
    );

    /**
     * Module statistics
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $stats = array(
        'transitions_validated' => 0,
        'transitions_enforced' => 0,
        'automatic_transitions' => 0,
        'blocked_transitions' => 0,
        'grace_periods_applied' => 0
    );

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize module
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Status_Transition
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set status enum dependency
     *
     * @since 1.5.0-rc.1
     * @param VD_License_Status_Enum $status_enum Status enum instance
     * @return void
     */
    public function set_status_enum($status_enum) {
        $this->status_enum = $status_enum;
    }

    /**
     * Validate status hierarchy
     *
     * @since 1.5.0-rc.1
     * @param string $status Status to validate
     * @return array Hierarchy validation result
     */
    public function validate_status_hierarchy($status) {
        if (!$this->status_enum) {
            return array(
                'valid' => false,
                'error' => 'Status enum dependency not set',
                'error_code' => 'dependency_missing'
            );
        }

        // Validate status is valid enum first
        $enum_validation = $this->status_enum->validate_status_enum($status);
        if (!$enum_validation['valid']) {
            return $enum_validation;
        }

        $hierarchy_level = $this->get_status_hierarchy_level($status);
        $status_category = $this->status_enum->get_status_category($status);

        return array(
            'valid' => true,
            'status' => $status,
            'hierarchy_level' => $hierarchy_level,
            'hierarchy_priority' => $this->get_hierarchy_priority_name($hierarchy_level),
            'status_category' => $status_category,
            'is_terminal' => $this->status_enum->is_status_terminal($status),
            'transition_count' => count($this->status_enum->get_allowed_transitions($status))
        );
    }

    /**
     * Get transition type between two statuses
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @return string Transition type
     */
    public function get_transition_type($from_status, $to_status) {
        $from_level = $this->get_status_hierarchy_level($from_status);
        $to_level = $this->get_status_hierarchy_level($to_status);

        if ($to_level < $from_level) {
            return 'degradation'; // Moving to higher priority (usually negative)
        } elseif ($to_level > $from_level) {
            return 'improvement'; // Moving to lower priority (usually positive)
        } else {
            return 'lateral'; // Same level
        }
    }

    /**
     * Enforce transition rules with business logic
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $rule_config Rule configuration
     * @return array Enforcement result
     */
    public function enforce_transition_rules($from_status, $to_status, $license, $rule_config = array()) {
        $this->stats['transitions_enforced']++;

        // Merge with default policies
        $policies = array_merge($this->transition_policies, $rule_config['transition_policies'] ?? array());

        // Check if transition requires admin approval
        if (in_array($to_status, $policies['require_admin_approval'] ?? array())) {
            if (!$this->has_admin_approval($license, $from_status, $to_status)) {
                $this->stats['blocked_transitions']++;
                return array(
                    'allowed' => false,
                    'reason' => "Transition to '{$to_status}' requires admin approval",
                    'requires_action' => 'admin_approval',
                    'error_code' => 'admin_approval_required'
                );
            }
        }

        // Check specific transition policies
        if ($to_status === 'active') {
            // Special rules for activating licenses
            if ($from_status === 'expired' && !($policies['allow_expired_to_active'] ?? false)) {
                $this->stats['blocked_transitions']++;
                return array(
                    'allowed' => false,
                    'reason' => 'Expired licenses cannot be automatically reactivated',
                    'requires_action' => 'manual_renewal',
                    'error_code' => 'expired_reactivation_blocked'
                );
            }
        }

        // Revoked transitions should be carefully controlled
        if ($from_status === 'revoked' && !($policies['allow_revoked_transitions'] ?? false)) {
            $this->stats['blocked_transitions']++;
            return array(
                'allowed' => false,
                'reason' => 'Revoked licenses cannot be transitioned',
                'requires_action' => 'manual_restoration',
                'error_code' => 'revoked_transition_blocked'
            );
        }

        // Check if source status allows transitions
        if ($from_status === 'revoked' && !($policies['allow_revoked_transitions'] ?? false)) {
            $this->stats['blocked_transitions']++;
            return array(
                'allowed' => false,
                'reason' => 'Revoked status is terminal',
                'requires_action' => 'none',
                'error_code' => 'terminal_status'
            );
        }

        // All checks passed
        return array(
            'allowed' => true,
            'reason' => 'Transition allowed by business rules',
            'transition_type' => $this->get_transition_type($from_status, $to_status),
            'requires_audit' => $policies['audit_transitions'] ?? true,
            'policies_applied' => array_keys($policies)
        );
    }

    /**
     * Check if grace period applies to status transition
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @return bool True if grace period applies
     */
    public function is_grace_period_applicable($from_status, $to_status, $license = array()) {
        if (!$this->transition_policies['grace_period_enabled']) {
            return false;
        }

        $applicable = isset($this->grace_applicable_transitions[$from_status]) &&
                     in_array($to_status, $this->grace_applicable_transitions[$from_status]);

        if ($applicable) {
            $this->stats['grace_periods_applied']++;
        }

        return $applicable;
    }

    /**
     * Validate automatic status transition
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $options Transition options
     * @return array Validation result
     */
    public function validate_automatic_status_transition($from_status, $to_status, $license, $options = array()) {
        $this->stats['automatic_transitions']++;

        if (!$this->transition_policies['auto_transition_enabled']) {
            return array(
                'valid' => false,
                'error' => 'Automatic transitions are disabled',
                'error_code' => 'auto_transition_disabled'
            );
        }

        // Get allowed automatic transitions
        $transition_key = $from_status . '_to_' . $to_status;

        if (!isset($this->automatic_transitions[$transition_key])) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    'Automatic transition from %s to %s is not allowed',
                    $from_status,
                    $to_status
                ),
                'error_code' => 'automatic_transition_not_allowed'
            );
        }

        $transition_config = $this->automatic_transitions[$transition_key];

        // Validate constraints if any
        if (!empty($transition_config['constraints'])) {
            foreach ($transition_config['constraints'] as $constraint) {
                $constraint_result = $this->validate_transition_constraint($constraint, $license, $options);
                if (!$constraint_result['valid']) {
                    return $constraint_result;
                }
            }
        }

        return array(
            'valid' => true,
            'transition_config' => $transition_config,
            'transition_type' => $transition_config['type'] ?? 'automatic',
            'requires_audit' => $transition_config['requires_audit'] ?? true,
            'constraints_validated' => count($transition_config['constraints'] ?? array())
        );
    }

    /**
     * Validate transition constraint
     *
     * @since 1.5.0-rc.1
     * @param string $constraint Constraint type
     * @param array $license License data
     * @param array $options Transition options
     * @return array Validation result
     */
    public function validate_transition_constraint($constraint, $license, $options) {
        switch ($constraint) {
            case 'expiry_date_passed':
                $expiry_date = $license['expires_at'] ?? null;
                if (!$expiry_date) {
                    return array(
                        'valid' => false,
                        'error' => 'License expiry date not found',
                        'error_code' => 'missing_expiry_date'
                    );
                }

                $is_expired = strtotime($expiry_date) < time();
                return array(
                    'valid' => $is_expired,
                    'error' => $is_expired ? null : 'License has not expired yet',
                    'error_code' => $is_expired ? null : 'not_expired'
                );

            case 'payment_verified':
                $payment_verified = $options['payment_verified'] ?? false;
                return array(
                    'valid' => $payment_verified,
                    'error' => $payment_verified ? null : 'Payment verification required',
                    'error_code' => $payment_verified ? null : 'payment_verification_required'
                );

            case 'admin_approval':
                $admin_approved = $options['admin_approved'] ?? false;
                return array(
                    'valid' => $admin_approved,
                    'error' => $admin_approved ? null : 'Admin approval required',
                    'error_code' => $admin_approved ? null : 'admin_approval_required'
                );

            default:
                return array(
                    'valid' => true,
                    'error' => null,
                    'warning' => "Unknown constraint: {$constraint}"
                );
        }
    }

    /**
     * Get comprehensive transition analysis
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $options Analysis options
     * @return array Comprehensive analysis
     */
    public function analyze_transition($from_status, $to_status, $license = array(), $options = array()) {
        $analysis = array(
            'transition_key' => $from_status . '_to_' . $to_status,
            'transition_type' => $this->get_transition_type($from_status, $to_status),
            'hierarchy_change' => array(
                'from_level' => $this->get_status_hierarchy_level($from_status),
                'to_level' => $this->get_status_hierarchy_level($to_status),
                'direction' => $this->get_transition_type($from_status, $to_status)
            )
        );

        // Basic transition validation
        if ($this->status_enum) {
            $analysis['basic_validation'] = $this->status_enum->validate_status_transition($from_status, $to_status);
        }

        // Business rules enforcement
        $analysis['business_rules'] = $this->enforce_transition_rules(
            $from_status,
            $to_status,
            $license,
            $options
        );

        // Grace period check
        $analysis['grace_period'] = array(
            'applicable' => $this->is_grace_period_applicable($from_status, $to_status, $license),
            'enabled' => $this->transition_policies['grace_period_enabled']
        );

        // Automatic transition check
        $analysis['automatic_transition'] = $this->validate_automatic_status_transition(
            $from_status,
            $to_status,
            $license,
            $options
        );

        // Risk assessment
        $analysis['risk_assessment'] = $this->assess_transition_risk($from_status, $to_status, $license);

        return $analysis;
    }

    /**
     * Assess transition risk level
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @return array Risk assessment
     */
    private function assess_transition_risk($from_status, $to_status, $license) {
        $risk_factors = array();
        $risk_level = 'low';

        // High risk transitions
        $high_risk_transitions = array(
            'revoked_to_active', 'revoked_to_inactive',
            'expired_to_active', 'suspended_to_active'
        );

        $transition_key = $from_status . '_to_' . $to_status;

        if (in_array($transition_key, $high_risk_transitions)) {
            $risk_level = 'high';
            $risk_factors[] = 'High-impact status transition';
        }

        // Terminal status transitions
        if ($this->status_enum && $this->status_enum->is_status_terminal($from_status)) {
            $risk_level = 'critical';
            $risk_factors[] = 'Transition from terminal status';
        }

        // Downgrade transitions
        if ($this->get_transition_type($from_status, $to_status) === 'degradation') {
            $risk_level = ($risk_level === 'low') ? 'medium' : $risk_level;
            $risk_factors[] = 'Status degradation transition';
        }

        return array(
            'risk_level' => $risk_level,
            'risk_factors' => $risk_factors,
            'requires_review' => in_array($risk_level, array('high', 'critical')),
            'requires_audit' => $risk_level !== 'low'
        );
    }

    /**
     * Check if admin approval exists for transition
     *
     * @since 1.5.0-rc.1
     * @param array $license License data
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @return bool True if admin approval exists
     */
    private function has_admin_approval($license, $from_status, $to_status) {
        // Check if approval is provided in license data
        $approvals = $license['admin_approvals'] ?? array();
        $transition_key = $from_status . '_to_' . $to_status;

        return isset($approvals[$transition_key]) && $approvals[$transition_key] === true;
    }

    /**
     * Get status hierarchy level
     *
     * @since 1.5.0-rc.1
     * @param string $status Status to check
     * @return int Hierarchy level
     */
    private function get_status_hierarchy_level($status) {
        return $this->status_hierarchy[$status] ?? 999;
    }

    /**
     * Get hierarchy priority name
     *
     * @since 1.5.0-rc.1
     * @param int $level Hierarchy level
     * @return string Priority name
     */
    private function get_hierarchy_priority_name($level) {
        $priorities = array(
            1 => 'critical',
            2 => 'high',
            3 => 'medium',
            4 => 'normal',
            5 => 'low',
            6 => 'lowest'
        );

        return $priorities[$level] ?? 'unknown';
    }

    /**
     * Get allowed automatic transitions
     *
     * @since 1.5.0-rc.1
     * @return array Automatic transitions configuration
     */
    public function get_allowed_automatic_transitions() {
        return $this->automatic_transitions;
    }

    /**
     * Get transition policies
     *
     * @since 1.5.0-rc.1
     * @return array Transition policies
     */
    public function get_transition_policies() {
        return $this->transition_policies;
    }

    /**
     * Update transition policies
     *
     * @since 1.5.0-rc.1
     * @param array $policies New policies
     * @return void
     */
    public function update_transition_policies($policies) {
        $this->transition_policies = array_merge($this->transition_policies, $policies);
    }

    /**
     * Get module statistics
     *
     * @since 1.5.0-rc.1
     * @return array Module statistics
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Get module information
     *
     * @since 1.5.0-rc.1
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => 'VD License Status Transition Manager',
            'version' => '1.5.0-rc.1',
            'namespace' => 'VD\\LicenseManager\\Status',
            'description' => 'Handles license status transitions, validation, and business rules',
            'dependencies' => array('status.enum'),
            'supports' => array(
                'transition_validation',
                'business_rules',
                'automatic_transitions',
                'grace_periods',
                'risk_assessment',
                'hierarchy_validation'
            ),
            'statistics' => $this->get_stats(),
            'transition_policies' => $this->transition_policies,
            'automatic_transitions_count' => count($this->automatic_transitions),
            'grace_applicable_transitions_count' => array_sum(array_map('count', $this->grace_applicable_transitions))
        );
    }

    /**
     * Reset module statistics
     *
     * @since 1.5.0-rc.1
     * @return void
     */
    public function reset_stats() {
        $this->stats = array(
            'transitions_validated' => 0,
            'transitions_enforced' => 0,
            'automatic_transitions' => 0,
            'blocked_transitions' => 0,
            'grace_periods_applied' => 0
        );
    }
}
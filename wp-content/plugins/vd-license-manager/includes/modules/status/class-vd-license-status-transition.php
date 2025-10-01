<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Status Transition Manager (Simplified Version)
 *
 * Simplified version for debugging - contains only essential methods
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
     * Constructor
     */
    private function __construct() {
        // Simple initialization
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
     * Get status enum dependency
     *
     * @since 1.5.0-rc.1
     * @return VD_License_Status_Enum|null
     */
    public function get_status_enum() {
        return $this->status_enum;
    }

    /**
     * Enforce transition rules
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @return array Transition rule enforcement result
     */
    public function enforce_transition_rules($from_status, $to_status, $license, $rule_config = array()) {
        $transition_policies = isset($rule_config['transition_policies']) ? $rule_config['transition_policies'] : array();

        // Business rule: active -> revoked requires admin approval
        if ($from_status === 'active' && $to_status === 'revoked') {
            if (!current_user_can('manage_options')) {
                return array(
                    'allowed' => false,
                    'reason' => 'Chỉ admin mới có thể revoke license',
                    'requires_admin' => true
                );
            }
        }

        // Business rule: expired -> active requires manual renewal (not auto allowed)
        if ($from_status === 'expired' && $to_status === 'active') {
            $allow_expired_to_active = isset($transition_policies['allow_expired_to_active'])
                                     ? $transition_policies['allow_expired_to_active']
                                     : false;
            if (!$allow_expired_to_active) {
                return array(
                    'allowed' => false,
                    'reason' => 'License hết hạn không thể tự động chuyển về active, cần renew thủ công',
                    'requires_manual_renewal' => true
                );
            }
        }

        // Business rule: revoked is terminal state
        if ($from_status === 'revoked') {
            $allow_revoked_transitions = isset($transition_policies['allow_revoked_transitions'])
                                        ? $transition_policies['allow_revoked_transitions']
                                        : false;
            if (!$allow_revoked_transitions) {
                return array(
                    'allowed' => false,
                    'reason' => 'License đã bị revoke không thể chuyển đổi trạng thái',
                    'is_terminal_state' => true
                );
            }
        }

        // Default: allow transition
        return array(
            'allowed' => true,
            'transition_type' => 'manual',
            'message' => 'Transition allowed'
        );
    }

    /**
     * Validate automatic status transition
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $options Update options
     * @return array Transition validation result
     */
    public function validate_automatic_status_transition($from_status, $to_status, $license, $options = array()) {
        // Simple implementation
        return array(
            'valid' => true,
            'transition_type' => 'automatic',
            'message' => 'Automatic transition allowed (simplified version)'
        );
    }

    /**
     * Get allowed automatic transitions
     *
     * @since 1.5.0-rc.1
     * @return array Allowed automatic transitions
     */
    public function get_allowed_automatic_transitions() {
        // Simple implementation
        return array(
            'active_to_expired' => array(
                'type' => 'expiration',
                'requires_audit' => true
            ),
            'pending_to_active' => array(
                'type' => 'activation',
                'requires_audit' => false
            )
        );
    }

    /**
     * Validate transition constraint
     *
     * @since 1.5.0-rc.1
     * @param string $constraint Constraint to validate
     * @param array $license License data
     * @param array $options Update options
     * @return array Constraint validation result
     */
    public function validate_transition_constraint($constraint, $license, $options) {
        // Simple implementation
        return array(
            'valid' => true,
            'constraint' => $constraint,
            'message' => 'Constraint validation passed (simplified version)'
        );
    }

    /**
     * Validate status transition
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @return array Transition validation result
     */
    public function validate_status_transition($from_status, $to_status) {
        if ($this->status_enum) {
            return $this->status_enum->validate_status_transition($from_status, $to_status);
        }

        // Fallback simple validation
        return array(
            'valid' => true,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'message' => 'Transition validated (simplified version)'
        );
    }
}
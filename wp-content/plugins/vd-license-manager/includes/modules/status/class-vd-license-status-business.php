<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Status Business Logic
 *
 * Handles comprehensive business rules enforcement for license status management
 * Extracted from monolithic validator in Step 1.8 (Phase 2) of refactor
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 * @namespace VD\LicenseManager\Status
 */
class VD_License_Status_Business {

    /**
     * Singleton instance
     *
     * @var VD_License_Status_Business|null
     */
    private static $instance = null;

    /**
     * Status enum module instance
     *
     * @since 1.5.0-rc.2
     * @var VD_License_Status_Enum|null
     */
    private $status_enum = null;

    /**
     * Status transition module instance
     *
     * @since 1.5.0-rc.2
     * @var VD_License_Status_Transition|null
     */
    private $status_transition = null;

    /**
     * Business rule configuration cache
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $rule_config_cache = array();

    /**
     * Business rule enforcement statistics
     *
     * @since 1.5.0-rc.2
     * @var array
     */
    private $stats = array(
        'rules_enforced' => 0,
        'rules_violated' => 0,
        'grace_periods_applied' => 0,
        'escalations_triggered' => 0,
        'transitions_blocked' => 0
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
     * @return VD_License_Status_Business
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
     * @since 1.5.0-rc.2
     * @param VD_License_Status_Enum $status_enum Status enum instance
     * @return void
     */
    public function set_status_enum($status_enum) {
        $this->status_enum = $status_enum;
    }

    /**
     * Set status transition dependency
     *
     * @since 1.5.0-rc.2
     * @param VD_License_Status_Transition $status_transition Status transition instance
     * @return void
     */
    public function set_status_transition($status_transition) {
        $this->status_transition = $status_transition;
    }

    /**
     * Get status enum dependency
     *
     * @since 1.5.0-rc.2
     * @return VD_License_Status_Enum|null
     */
    public function get_status_enum() {
        return $this->status_enum;
    }

    /**
     * Get status transition dependency
     *
     * @since 1.5.0-rc.2
     * @return VD_License_Status_Transition|null
     */
    public function get_status_transition() {
        return $this->status_transition;
    }

    /**
     * Enforce comprehensive business rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $context Context information
     * @return array Business rule enforcement result
     */
    public function enforce_business_rules($license, $context = array()) {
        $enforcement_start = microtime(true);
        $debug_info = array(
            'license_id' => $license['id'] ?? null,
            'context' => $context
        );

        try {
            // 1. Get current business rule configuration
            $rule_config = $this->get_business_rule_configuration($license);
            $debug_info['rule_config'] = $rule_config;

            // 2. Status-specific business rule enforcement
            $status_rules_result = $this->enforce_status_specific_rules($license, $rule_config, $context);
            if (!$status_rules_result['valid']) {
                return $this->create_business_rule_error(
                    $status_rules_result['code'],
                    $status_rules_result['error'],
                    $license,
                    array_merge($debug_info, $status_rules_result['debug_info'] ?? array())
                );
            }

            // 3. Grace period enforcement (if applicable)
            $grace_period_result = $this->enforce_grace_period_rules($license, $rule_config, $context);
            $debug_info['grace_period'] = $grace_period_result;

            // 4. Automatic escalation rules
            $escalation_result = $this->enforce_escalation_rules($license, $rule_config, $context);
            $debug_info['escalation'] = $escalation_result;

            // 5. Transition validation rules
            if (isset($context['from_status'], $context['to_status'])) {
                $transition_result = $this->enforce_transition_rules(
                    $context['from_status'],
                    $context['to_status'],
                    $license,
                    $rule_config
                );
                $debug_info['transition_enforcement'] = $transition_result;

                if (!$transition_result['allowed']) {
                    return $this->create_business_rule_error(
                        'transition_not_allowed',
                        $transition_result['reason'],
                        $license,
                        $debug_info
                    );
                }
            }

            // Update statistics
            $this->stats['rules_enforced']++;

            // Log successful business rule enforcement
            $this->log_business_rule_event('business_rules_enforced', $license, $debug_info);

            return array(
                'valid' => true,
                'enforcement_time' => (microtime(true) - $enforcement_start) * 1000,
                'rules_applied' => $status_rules_result['rules_applied'] ?? array(),
                'debug_info' => $debug_info
            );

        } catch (Exception $e) {
            $debug_info['exception'] = $e->getMessage();
            $this->log_business_rule_event('business_rules_exception', $license, $debug_info);

            return $this->create_business_rule_error(
                'business_rules_exception',
                'Lỗi hệ thống khi thực thi business rules: ' . $e->getMessage(),
                $license,
                $debug_info
            );
        }
    }

    /**
     * Get business rule configuration
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array Business rule configuration
     */
    public function get_business_rule_configuration($license) {
        $license_id = $license['id'] ?? null;

        // Check cache first
        if (isset($this->rule_config_cache[$license_id])) {
            return $this->rule_config_cache[$license_id];
        }

        // Default business rule configuration
        $default_config = array(
            'grace_periods' => array(
                'expired_grace_days' => 7,
                'suspended_grace_hours' => 24,
                'pending_timeout_days' => 30
            ),
            'escalation_rules' => array(
                'auto_suspend_after_days' => 7,
                'auto_revoke_after_days' => 30,
                'notification_thresholds' => array(3, 7, 14)
            ),
            'transition_policies' => array(
                'require_admin_approval' => array('revoked'),
                'allow_expired_to_active' => false,
                'allow_revoked_transitions' => false,
                'allow_automatic_escalation' => true
            ),
            'status_specific_rules' => array(
                'active' => array('check_expiry_warnings' => true),
                'expired' => array('grace_period_applicable' => true),
                'suspended' => array('auto_escalation_enabled' => true),
                'pending' => array('timeout_enabled' => true),
                'revoked' => array('permanent_state' => true),
                'inactive' => array('activation_required' => true)
            )
        );

        // Get license-specific settings
        $license_settings = $this->get_license_settings($license);
        $merged_config = array_replace_recursive($default_config, $license_settings['business_rules'] ?? array());

        // Cache the configuration
        $this->rule_config_cache[$license_id] = $merged_config;

        return $merged_config;
    }

    /**
     * Enforce status-specific business rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Status-specific rule enforcement result
     */
    public function enforce_status_specific_rules($license, $rule_config, $context) {
        $current_status = $license['mapped_status'] ?? $license['status'] ?? 'unknown';

        switch ($current_status) {
            case 'active':
                return $this->enforce_active_license_business_rules($license, $rule_config, $context);

            case 'expired':
                return $this->enforce_expired_license_business_rules($license, $rule_config, $context);

            case 'suspended':
                return $this->enforce_suspended_license_business_rules($license, $rule_config, $context);

            case 'pending':
                return $this->enforce_pending_license_business_rules($license, $rule_config, $context);

            case 'revoked':
                return $this->enforce_revoked_license_business_rules($license, $rule_config, $context);

            default:
                return $this->enforce_inactive_license_business_rules($license, $rule_config, $context);
        }
    }

    /**
     * Enforce active license business rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Active license rule enforcement result
     */
    public function enforce_active_license_business_rules($license, $rule_config, $context) {
        $rules_applied = array();

        // Check expiry warning
        if (isset($license['expires_at']) && $license['expires_at']) {
            $expiry_timestamp = strtotime($license['expires_at']);
            $warning_days = $rule_config['grace_periods']['expired_grace_days'] ?? 7;
            $days_until_expiry = ceil(($expiry_timestamp - current_time('timestamp')) / (24 * 3600));

            if ($days_until_expiry <= $warning_days && $days_until_expiry > 0) {
                $rules_applied[] = array(
                    'rule' => 'expiry_warning',
                    'status' => 'warning',
                    'message' => sprintf('License sẽ hết hạn trong %d ngày', $days_until_expiry),
                    'days_remaining' => $days_until_expiry
                );
            }
        }

        return array(
            'valid' => true,
            'rules_applied' => $rules_applied,
            'status' => 'active_with_rules'
        );
    }

    /**
     * Enforce expired license business rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Expired license rule enforcement result
     */
    public function enforce_expired_license_business_rules($license, $rule_config, $context) {
        $rules_applied = array();

        // Check grace period
        if (isset($license['expires_at'])) {
            $grace_days = $rule_config['grace_periods']['expired_grace_days'] ?? 7;
            $expiry_timestamp = strtotime($license['expires_at']);
            $days_expired = ceil((current_time('timestamp') - $expiry_timestamp) / (24 * 3600));

            if ($days_expired <= $grace_days) {
                $rules_applied[] = array(
                    'rule' => 'grace_period_active',
                    'status' => 'grace',
                    'message' => sprintf('License trong thời gian ân hạn (%d/%d ngày)', $days_expired, $grace_days),
                    'grace_remaining' => $grace_days - $days_expired
                );
            } else {
                $rules_applied[] = array(
                    'rule' => 'grace_period_expired',
                    'status' => 'expired',
                    'message' => 'License đã hết hạn và vượt quá thời gian ân hạn',
                    'days_over_grace' => $days_expired - $grace_days
                );
            }
        }

        return array(
            'valid' => true,
            'rules_applied' => $rules_applied,
            'status' => 'expired_with_grace_evaluation'
        );
    }

    /**
     * Enforce suspended license business rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Suspended license rule enforcement result
     */
    public function enforce_suspended_license_business_rules($license, $rule_config, $context) {
        $rules_applied = array();

        // Check auto-escalation to revoked
        if ($rule_config['escalation_rules']['auto_revoke_after_days'] ?? false) {
            $suspend_date = $license['last_status_change'] ?? $license['updated_at'] ?? null;
            if ($suspend_date) {
                $days_suspended = ceil((current_time('timestamp') - strtotime($suspend_date)) / (24 * 3600));
                $auto_revoke_days = $rule_config['escalation_rules']['auto_revoke_after_days'];

                if ($days_suspended >= $auto_revoke_days) {
                    $rules_applied[] = array(
                        'rule' => 'auto_revoke_eligible',
                        'status' => 'escalation_ready',
                        'message' => sprintf('License suspended %d ngày, đủ điều kiện auto-revoke', $days_suspended),
                        'days_suspended' => $days_suspended,
                        'escalation_threshold' => $auto_revoke_days
                    );
                }
            }
        }

        return array(
            'valid' => true,
            'rules_applied' => $rules_applied,
            'status' => 'suspended_with_escalation_check'
        );
    }

    /**
     * Enforce pending license business rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Pending license rule enforcement result
     */
    public function enforce_pending_license_business_rules($license, $rule_config, $context) {
        $rules_applied = array();

        // Check timeout
        $timeout_days = $rule_config['grace_periods']['pending_timeout_days'] ?? 30;
        $created_date = $license['created_at'] ?? null;

        if ($created_date) {
            $days_pending = ceil((current_time('timestamp') - strtotime($created_date)) / (24 * 3600));

            if ($days_pending >= $timeout_days) {
                $rules_applied[] = array(
                    'rule' => 'pending_timeout',
                    'status' => 'timeout',
                    'message' => sprintf('License pending quá %d ngày, cần xử lý', $days_pending),
                    'days_pending' => $days_pending,
                    'timeout_threshold' => $timeout_days
                );
            }
        }

        return array(
            'valid' => true,
            'rules_applied' => $rules_applied,
            'status' => 'pending_with_timeout_check'
        );
    }

    /**
     * Enforce revoked license business rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Revoked license rule enforcement result
     */
    public function enforce_revoked_license_business_rules($license, $rule_config, $context) {
        return array(
            'valid' => true,
            'rules_applied' => array(
                array(
                    'rule' => 'permanent_revocation',
                    'status' => 'terminal',
                    'message' => 'License đã bị thu hồi vĩnh viễn'
                )
            ),
            'status' => 'revoked_terminal'
        );
    }

    /**
     * Enforce inactive license business rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Inactive license rule enforcement result
     */
    public function enforce_inactive_license_business_rules($license, $rule_config, $context) {
        return array(
            'valid' => true,
            'rules_applied' => array(
                array(
                    'rule' => 'activation_required',
                    'status' => 'requires_activation',
                    'message' => 'License cần được kích hoạt'
                )
            ),
            'status' => 'inactive_awaiting_activation'
        );
    }

    /**
     * Enforce grace period rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Grace period enforcement result
     */
    public function enforce_grace_period_rules($license, $rule_config, $context) {
        if (!$this->status_transition) {
            return array('applicable' => false, 'reason' => 'Status transition module not available');
        }

        $current_status = $license['mapped_status'] ?? $license['status'] ?? 'unknown';
        $grace_config = $rule_config['grace_periods'] ?? array();

        // Check if grace period applies to current status
        $grace_applicable = $this->is_grace_period_applicable($current_status, $grace_config);

        if ($grace_applicable) {
            $this->stats['grace_periods_applied']++;
            return array(
                'applicable' => true,
                'grace_config' => $grace_config,
                'current_status' => $current_status
            );
        }

        return array('applicable' => false, 'current_status' => $current_status);
    }

    /**
     * Enforce escalation rules
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @param array $context Context information
     * @return array Escalation enforcement result
     */
    public function enforce_escalation_rules($license, $rule_config, $context) {
        $escalation_config = $rule_config['escalation_rules'] ?? array();

        if (!($escalation_config['auto_escalation_enabled'] ?? true)) {
            return array('enabled' => false, 'reason' => 'Auto escalation disabled');
        }

        $current_status = $license['mapped_status'] ?? $license['status'] ?? 'unknown';
        $escalation_result = array('enabled' => true, 'current_status' => $current_status, 'actions' => array());

        // Check for escalation triggers
        if ($current_status === 'expired') {
            $auto_suspend_days = $escalation_config['auto_suspend_after_days'] ?? 7;
            $days_expired = $this->calculate_days_since_expiry($license);

            if ($days_expired >= $auto_suspend_days) {
                $escalation_result['actions'][] = array(
                    'action' => 'auto_suspend',
                    'trigger' => 'expired_timeout',
                    'days_expired' => $days_expired,
                    'threshold' => $auto_suspend_days
                );
                $this->stats['escalations_triggered']++;
            }
        }

        return $escalation_result;
    }

    /**
     * Enforce transition rules (delegate to Status Transition module)
     *
     * @since 1.5.0-rc.2
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $rule_config Business rule configuration
     * @return array Transition enforcement result
     */
    public function enforce_transition_rules($from_status, $to_status, $license, $rule_config) {
        if ($this->status_transition) {
            return $this->status_transition->enforce_transition_rules($from_status, $to_status, $license, $rule_config);
        }

        // Fallback if module not available
        return array(
            'allowed' => false,
            'reason' => 'Status transition module not available',
            'error_code' => 'dependency_missing'
        );
    }

    /**
     * Create business rule error
     *
     * @since 1.5.0-rc.2
     * @param string $code Error code
     * @param string $message Error message
     * @param array $license License data
     * @param array $debug_info Debug information
     * @return array Business rule error response
     */
    public function create_business_rule_error($code, $message, $license, $debug_info) {
        $this->stats['rules_violated']++;

        return array(
            'valid' => false,
            'error' => $message,
            'code' => $code,
            'license_id' => $license['id'] ?? null,
            'current_status' => $license['mapped_status'] ?? $license['status'] ?? null,
            'debug_info' => $debug_info,
            'business_rule_timestamp' => current_time('mysql')
        );
    }

    /**
     * Log business rule events
     *
     * @since 1.5.0-rc.2
     * @param string $event_type Type of business rule event
     * @param array $license License data
     * @param array $debug_info Debug information
     * @return void
     */
    public function log_business_rule_event($event_type, $license, $debug_info) {
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log(sprintf(
                'VD Business Rules [%s]: License %s - %s',
                $event_type,
                $license['id'] ?? 'unknown',
                wp_json_encode($debug_info, JSON_UNESCAPED_UNICODE)
            ));
        }

        // Hook for external logging systems
        do_action('vd_license_business_rule_event', $event_type, $license, $debug_info);
    }

    /**
     * Get license settings (placeholder for license-specific configuration)
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return array License settings
     */
    private function get_license_settings($license) {
        // This would typically fetch from database or cache
        // For now, return empty array (use defaults)
        return array();
    }

    /**
     * Check if grace period is applicable
     *
     * @since 1.5.0-rc.2
     * @param string $status Current status
     * @param array $grace_config Grace period configuration
     * @return bool True if grace period applicable
     */
    private function is_grace_period_applicable($status, $grace_config) {
        $grace_applicable_statuses = array('expired', 'suspended');
        return in_array($status, $grace_applicable_statuses) && !empty($grace_config);
    }

    /**
     * Calculate days since license expiry
     *
     * @since 1.5.0-rc.2
     * @param array $license License data
     * @return int Days since expiry
     */
    private function calculate_days_since_expiry($license) {
        if (!isset($license['expires_at']) || !$license['expires_at']) {
            return 0;
        }

        $expiry_timestamp = strtotime($license['expires_at']);
        return max(0, ceil((current_time('timestamp') - $expiry_timestamp) / (24 * 3600)));
    }

    /**
     * Get module statistics
     *
     * @since 1.5.0-rc.2
     * @return array Module statistics
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Reset module statistics
     *
     * @since 1.5.0-rc.2
     * @return void
     */
    public function reset_stats() {
        $this->stats = array(
            'rules_enforced' => 0,
            'rules_violated' => 0,
            'grace_periods_applied' => 0,
            'escalations_triggered' => 0,
            'transitions_blocked' => 0
        );
    }

    /**
     * Get module information
     *
     * @since 1.5.0-rc.2
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => 'VD License Status Business Logic',
            'version' => '1.5.0-rc.2',
            'namespace' => 'VD\\LicenseManager\\Status',
            'description' => 'Comprehensive business rules enforcement for license status management',
            'dependencies' => array('status.enum', 'status.transition'),
            'supports' => array(
                'business_rules_enforcement',
                'status_specific_rules',
                'grace_period_management',
                'escalation_rules',
                'transition_validation',
                'rule_configuration'
            ),
            'statistics' => $this->get_stats()
        );
    }
}
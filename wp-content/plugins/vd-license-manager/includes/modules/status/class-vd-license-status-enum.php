<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Status Enum Validator
 *
 * Handles license status enumeration, validation, and business rules
 * Extracted from monolithic validator in Step 1.6 of refactor
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */
class VD_License_Status_Enum {

    /**
     * Singleton instance
     *
     * @var VD_License_Status_Enum|null
     */
    private static $instance = null;

    /**
     * Valid license status enums
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $valid_statuses = array(
        'active',     // License is active and usable
        'inactive',   // License exists but not activated
        'suspended',  // License temporarily disabled
        'expired',    // License has expired
        'revoked',    // License permanently revoked
        'pending'     // License pending activation
    );

    /**
     * Status transition matrix
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $status_transitions = array(
        'pending'   => array('active', 'inactive', 'expired'),
        'inactive'  => array('active', 'suspended', 'expired'),
        'active'    => array('suspended', 'expired', 'revoked', 'inactive'),
        'suspended' => array('active', 'expired', 'revoked'),
        'expired'   => array('active', 'revoked'), // Can be renewed
        'revoked'   => array() // Terminal state - no transitions allowed
    );

    /**
     * Status descriptions
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $status_descriptions = array(
        'active'    => 'License đang hoạt động và có thể sử dụng',
        'inactive'  => 'License tồn tại nhưng chưa được kích hoạt',
        'suspended' => 'License tạm thời bị vô hiệu hóa',
        'expired'   => 'License đã hết hạn sử dụng',
        'revoked'   => 'License đã bị thu hồi vĩnh viễn',
        'pending'   => 'License đang chờ được kích hoạt'
    );

    /**
     * Status categories for business logic
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $status_categories = array(
        'active'    => 'usable',
        'inactive'  => 'unusable',
        'suspended' => 'temporarily_unusable',
        'expired'   => 'unusable',
        'revoked'   => 'permanently_unusable',
        'pending'   => 'unusable'
    );

    /**
     * Status hierarchy (priority levels)
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
     * Module statistics
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $stats = array(
        'validations_performed' => 0,
        'transitions_validated' => 0,
        'enum_checks' => 0,
        'category_lookups' => 0
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
     * @return VD_License_Status_Enum
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Validate status against defined enums
     *
     * @since 1.5.0-rc.1
     * @param string $status Status to validate
     * @return array Validation result
     */
    public function validate_status_enum($status) {
        $this->stats['enum_checks']++;

        if (empty($status)) {
            return array(
                'valid' => false,
                'error' => 'Status không được để trống',
                'error_code' => 'empty_status',
                'provided_status' => $status,
                'valid_statuses' => $this->valid_statuses
            );
        }

        if (!in_array($status, $this->valid_statuses, true)) {
            return array(
                'valid' => false,
                'error' => sprintf('Trạng thái "%s" không hợp lệ. Các trạng thái cho phép: %s',
                    $status,
                    implode(', ', $this->valid_statuses)
                ),
                'error_code' => 'invalid_status_enum',
                'provided_status' => $status,
                'valid_statuses' => $this->valid_statuses
            );
        }

        return array(
            'valid' => true,
            'status' => $status,
            'status_description' => $this->get_status_description($status),
            'status_category' => $this->get_status_category($status),
            'status_hierarchy_level' => $this->get_status_hierarchy_level($status)
        );
    }

    /**
     * Comprehensive status validation with business rules
     *
     * @since 1.5.0-rc.1
     * @param array $license License data
     * @return array Validation result
     */
    public function perform_status_enum_validation($license) {
        $this->stats['validations_performed']++;

        if (empty($license) || !is_array($license)) {
            return array(
                'valid' => false,
                'error' => 'License data không hợp lệ',
                'error_code' => 'invalid_license_data'
            );
        }

        $status_info = $this->extract_status_from_license($license);
        if (!$status_info['valid']) {
            return $status_info;
        }

        $mapped_status = $status_info['mapped_status'];

        // Validate enum
        $enum_validation = $this->validate_status_enum($mapped_status);
        if (!$enum_validation['valid']) {
            return array(
                'valid' => false,
                'error' => $enum_validation['error'],
                'error_code' => 'status_enum_invalid',
                'status_info' => $status_info,
                'enum_validation' => $enum_validation
            );
        }

        // Apply business rules based on status
        $business_validation = $this->validate_status_business_rules($mapped_status, $status_info);

        return array_merge($business_validation, array(
            'status_info' => $status_info,
            'enum_validation' => $enum_validation
        ));
    }

    /**
     * Extract and normalize status from license data
     *
     * @since 1.5.0-rc.1
     * @param array $license License data
     * @return array Status extraction result
     */
    private function extract_status_from_license($license) {
        $raw_status = null;

        // Try multiple possible status fields
        if (isset($license['status'])) {
            $raw_status = $license['status'];
        } elseif (isset($license['license_status'])) {
            $raw_status = $license['license_status'];
        } elseif (isset($license['lmfwc_status'])) {
            $raw_status = $license['lmfwc_status'];
        }

        if (empty($raw_status)) {
            return array(
                'valid' => false,
                'error' => 'Không tìm thấy thông tin trạng thái trong license data',
                'error_code' => 'missing_status',
                'available_fields' => array_keys($license)
            );
        }

        // Normalize status (lowercase, trim)
        $mapped_status = strtolower(trim($raw_status));

        return array(
            'valid' => true,
            'raw_status' => $raw_status,
            'mapped_status' => $mapped_status,
            'status_source' => $this->determine_status_source($license)
        );
    }

    /**
     * Determine the source of status information
     *
     * @since 1.5.0-rc.1
     * @param array $license License data
     * @return string Status source
     */
    private function determine_status_source($license) {
        if (isset($license['lookup_source'])) {
            return $license['lookup_source'];
        }

        if (isset($license['lmfwc_status'])) {
            return 'lmfwc';
        }

        return 'unknown';
    }

    /**
     * Validate status business rules
     *
     * @since 1.5.0-rc.1
     * @param string $mapped_status Normalized status
     * @param array $status_info Status extraction info
     * @return array Validation result
     */
    private function validate_status_business_rules($mapped_status, $status_info) {
        switch ($mapped_status) {
            case 'inactive':
                return array(
                    'valid' => false,
                    'error' => 'License chưa được kích hoạt',
                    'error_code' => 'license_inactive',
                    'can_activate' => true
                );

            case 'suspended':
                return array(
                    'valid' => false,
                    'error' => 'License tạm thời bị vô hiệu hóa',
                    'error_code' => 'license_suspended',
                    'can_reactivate' => true
                );

            case 'expired':
                return array(
                    'valid' => false,
                    'error' => 'License đã hết hạn sử dụng',
                    'error_code' => 'license_expired',
                    'can_renew' => true
                );

            case 'revoked':
                return array(
                    'valid' => false,
                    'error' => 'License đã bị thu hồi vĩnh viễn',
                    'error_code' => 'license_revoked',
                    'can_restore' => false
                );

            case 'pending':
                return array(
                    'valid' => false,
                    'error' => 'License đang chờ kích hoạt',
                    'error_code' => 'license_pending',
                    'can_activate' => true
                );

            case 'active':
                return $this->validate_active_license_rules($status_info);

            default:
                return array(
                    'valid' => false,
                    'error' => sprintf('Trạng thái license không được hỗ trợ: %s', $mapped_status),
                    'error_code' => 'unsupported_status'
                );
        }
    }

    /**
     * Validate active license specific rules
     *
     * @since 1.5.0-rc.1
     * @param array $status_info Status information
     * @return array Validation result
     */
    private function validate_active_license_rules($status_info) {
        return array(
            'valid' => true,
            'error_code' => 'license_active',
            'warnings' => array(), // Can be populated with non-blocking warnings
            'additional_checks' => array(
                'expiry_check_required' => true,
                'device_limit_check_required' => true
            )
        );
    }

    /**
     * Validate status transition
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Current status
     * @param string $to_status Target status
     * @return array Transition validation result
     */
    public function validate_status_transition($from_status, $to_status) {
        $this->stats['transitions_validated']++;

        // Validate both statuses are valid enums
        $from_validation = $this->validate_status_enum($from_status);
        if (!$from_validation['valid']) {
            return array(
                'valid' => false,
                'error' => 'Status nguồn không hợp lệ: ' . $from_validation['error'],
                'error_code' => 'invalid_from_status'
            );
        }

        $to_validation = $this->validate_status_enum($to_status);
        if (!$to_validation['valid']) {
            return array(
                'valid' => false,
                'error' => 'Status đích không hợp lệ: ' . $to_validation['error'],
                'error_code' => 'invalid_to_status'
            );
        }

        // Check if transition is allowed
        $allowed_transitions = $this->status_transitions[$from_status] ?? array();

        if (!in_array($to_status, $allowed_transitions, true)) {
            return array(
                'valid' => false,
                'error' => sprintf('Không thể chuyển từ "%s" sang "%s"', $from_status, $to_status),
                'error_code' => 'invalid_transition',
                'from_status' => $from_status,
                'to_status' => $to_status,
                'allowed_transitions' => $allowed_transitions
            );
        }

        return array(
            'valid' => true,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'transition_type' => $this->get_transition_type($from_status, $to_status),
            'requires_approval' => $this->transition_requires_approval($from_status, $to_status)
        );
    }

    /**
     * Get all valid status enums
     *
     * @since 1.5.0-rc.1
     * @return array Valid status enums
     */
    public function get_valid_status_enums() {
        return $this->valid_statuses;
    }

    /**
     * Get status description
     *
     * @since 1.5.0-rc.1
     * @param string $status Status enum
     * @return string Status description
     */
    public function get_status_description($status) {
        return $this->status_descriptions[$status] ?? 'Trạng thái không xác định';
    }

    /**
     * Get status category
     *
     * @since 1.5.0-rc.1
     * @param string $status Status enum
     * @return string Status category
     */
    public function get_status_category($status) {
        $this->stats['category_lookups']++;
        return $this->status_categories[$status] ?? 'unknown';
    }

    /**
     * Get status hierarchy level
     *
     * @since 1.5.0-rc.1
     * @param string $status Status enum
     * @return int Hierarchy level (lower = higher priority)
     */
    public function get_status_hierarchy_level($status) {
        return $this->status_hierarchy[$status] ?? 999;
    }

    /**
     * Get allowed transitions for a status
     *
     * @since 1.5.0-rc.1
     * @param string $status Current status
     * @return array Allowed target statuses
     */
    public function get_allowed_transitions($status) {
        return $this->status_transitions[$status] ?? array();
    }

    /**
     * Get transition type
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @return string Transition type
     */
    private function get_transition_type($from_status, $to_status) {
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
     * Check if transition requires approval
     *
     * @since 1.5.0-rc.1
     * @param string $from_status Source status
     * @param string $to_status Target status
     * @return bool True if approval required
     */
    private function transition_requires_approval($from_status, $to_status) {
        // Critical transitions that require approval
        $critical_transitions = array(
            'active' => array('revoked', 'suspended'),
            'suspended' => array('revoked'),
            'expired' => array('active') // Renewal
        );

        return isset($critical_transitions[$from_status]) &&
               in_array($to_status, $critical_transitions[$from_status], true);
    }

    /**
     * Check if status is usable for license operations
     *
     * @since 1.5.0-rc.1
     * @param string $status Status to check
     * @return bool True if status allows license usage
     */
    public function is_status_usable($status) {
        return $this->get_status_category($status) === 'usable';
    }

    /**
     * Check if status is terminal (no further transitions)
     *
     * @since 1.5.0-rc.1
     * @param string $status Status to check
     * @return bool True if status is terminal
     */
    public function is_status_terminal($status) {
        return empty($this->get_allowed_transitions($status));
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
            'name' => 'VD License Status Enum Validator',
            'version' => '1.5.0-rc.1',
            'namespace' => 'VD\\LicenseManager\\Status',
            'description' => 'Handles license status enumeration, validation, and business rules',
            'dependencies' => array(),
            'supports' => array(
                'status_validation',
                'enum_checking',
                'transition_validation',
                'business_rules',
                'hierarchy_management'
            ),
            'statistics' => $this->get_stats(),
            'valid_statuses' => $this->valid_statuses,
            'status_categories' => array_unique(array_values($this->status_categories)),
            'total_transitions' => array_sum(array_map('count', $this->status_transitions))
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
            'validations_performed' => 0,
            'transitions_validated' => 0,
            'enum_checks' => 0,
            'category_lookups' => 0
        );
    }
}
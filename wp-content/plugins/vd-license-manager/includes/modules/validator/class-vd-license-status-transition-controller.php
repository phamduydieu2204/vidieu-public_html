<?php

namespace VD\LicenseManager\Validator;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Status Transition Controller
 *
 * Extracted status transition functionality from monolithic VD_License_Validator
 * Handles license status validation, transitions, and change notifications
 *
 * Step 5.1.4: Validator Refactoring - Status Transition Controller Extraction
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */
class VD_License_Status_Transition_Controller {

    /**
     * Singleton instance
     *
     * @var VD_License_Status_Transition_Controller|null
     */
    private static $instance = null;

    /**
     * Valid license status enums
     *
     * @var array
     */
    private $valid_status_enums = array(
        'active', 'inactive', 'pending', 'expired', 'suspended', 'revoked'
    );

    /**
     * Status transition rules matrix
     *
     * @var array
     */
    private $transition_rules = array(
        'pending' => array('active', 'expired', 'revoked'),
        'active' => array('inactive', 'expired', 'suspended', 'revoked'),
        'inactive' => array('active', 'expired', 'revoked'),
        'expired' => array('active', 'suspended', 'revoked'),
        'suspended' => array('active', 'revoked'),
        'revoked' => array() // Terminal status - no transitions allowed
    );

    /**
     * Status descriptions for human-readable output
     *
     * @var array
     */
    private $status_descriptions = array(
        'pending' => 'Đang chờ kích hoạt',
        'active' => 'Đang hoạt động',
        'inactive' => 'Tạm ngừng',
        'expired' => 'Đã hết hạn',
        'suspended' => 'Bị tạm khóa',
        'revoked' => 'Đã thu hồi'
    );

    /**
     * Status categories for business logic
     *
     * @var array
     */
    private $status_categories = array(
        'pending' => 'provisional',
        'active' => 'operational',
        'inactive' => 'operational',
        'expired' => 'non_operational',
        'suspended' => 'non_operational',
        'revoked' => 'terminated'
    );

    /**
     * Get singleton instance
     *
     * @return VD_License_Status_Transition_Controller
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Prevent cloning
     */
    private function __clone() {
        // Prevent cloning
    }

    /**
     * Prevent unserialization
     */
    private function __wakeup() {
        // Prevent unserialization
    }

    /**
     * Validate status transition
     *
     * Extracted from VD_License_Validator::validate_status_transition()
     * Step 5.1.4 - Status transition validation logic extraction
     *
     * @since 1.6.0
     * @param string $from_status Current status
     * @param string $to_status Target status
     * @return array Validation result
     */
    public function validate_status_transition($from_status, $to_status) {
        // Validate input statuses
        if (!in_array($from_status, $this->valid_status_enums)) {
            return array(
                'valid' => false,
                'error' => "Invalid source status: {$from_status}",
                'error_code' => 'invalid_source_status'
            );
        }

        if (!in_array($to_status, $this->valid_status_enums)) {
            return array(
                'valid' => false,
                'error' => "Invalid target status: {$to_status}",
                'error_code' => 'invalid_target_status'
            );
        }

        // Same status check
        if ($from_status === $to_status) {
            return array(
                'valid' => false,
                'error' => 'Source and target status are identical',
                'error_code' => 'identical_status'
            );
        }

        // Check transition rules
        $allowed_transitions = $this->transition_rules[$from_status] ?? array();
        $is_allowed = in_array($to_status, $allowed_transitions);

        if (!$is_allowed) {
            return array(
                'valid' => false,
                'error' => "Transition from {$from_status} to {$to_status} is not allowed",
                'error_code' => 'forbidden_transition',
                'from_status' => $from_status,
                'to_status' => $to_status,
                'allowed_transitions' => $allowed_transitions
            );
        }

        return array(
            'valid' => true,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'transition_type' => $this->get_transition_type($from_status, $to_status)
        );
    }

    /**
     * Get allowed status transitions for a given status
     *
     * Extracted from VD_License_Validator::get_allowed_status_transitions()
     * Step 5.1.4 - Transition rules extraction
     *
     * @since 1.6.0
     * @param string $status Current status (optional)
     * @return array Allowed transitions
     */
    public function get_allowed_status_transitions($status = null) {
        if ($status !== null) {
            return $this->transition_rules[$status] ?? array();
        }

        return $this->transition_rules;
    }

    /**
     * Get status description
     *
     * Extracted from VD_License_Validator::get_status_description()
     * Step 5.1.4 - Status description utility extraction
     *
     * @since 1.6.0
     * @param string $status Status enum
     * @return string Status description
     */
    public function get_status_description($status) {
        return $this->status_descriptions[$status] ?? 'Trạng thái không xác định';
    }

    /**
     * Get status category
     *
     * Extracted from VD_License_Validator::get_status_category()
     * Step 5.1.4 - Status categorization extraction
     *
     * @since 1.6.0
     * @param string $status Status enum
     * @return string Status category
     */
    public function get_status_category($status) {
        return $this->status_categories[$status] ?? 'unknown';
    }

    /**
     * Validate automatic status transition
     *
     * Extracted from VD_License_Validator::validate_automatic_status_transition()
     * Step 5.1.4 - Automatic transition validation extraction
     *
     * @since 1.6.0
     * @param string $from_status Current status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $options Validation options
     * @return array Validation result
     */
    public function validate_automatic_status_transition($from_status, $to_status, $license, $options) {
        // Basic transition validation
        $basic_validation = $this->validate_status_transition($from_status, $to_status);
        if (!$basic_validation['valid']) {
            return $basic_validation;
        }

        // Business rules validation
        $business_validation = $this->validate_status_business_rules($to_status, $license, $options);
        if (!$business_validation['valid']) {
            return $business_validation;
        }

        // Automatic transition specific rules
        $auto_validation = $this->validate_automatic_transition_rules($from_status, $to_status, $license, $options);
        if (!$auto_validation['valid']) {
            return $auto_validation;
        }

        return array(
            'valid' => true,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'transition_type' => 'automatic',
            'validation_passed' => array(
                'basic' => true,
                'business_rules' => true,
                'automatic_rules' => true
            )
        );
    }

    /**
     * Execute automatic status update
     *
     * Extracted from VD_License_Validator::execute_automatic_status_update()
     * Step 5.1.4 - Status update execution extraction
     *
     * @since 1.6.0
     * @param array $license License data
     * @param string $new_status New status to set
     * @param array $options Update options
     * @return array Update execution result
     */
    public function execute_automatic_status_update($license, $new_status, $options) {
        global $wpdb;

        try {
            $update_data = array(
                'status' => $new_status,
                'last_status_change' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );

            $where = array('id' => $license['id']);
            $where_format = array('%d');

            // Optimistic locking - ensure status hasn't changed since we read it
            if (!($options['force_update'] ?? false)) {
                $where['status'] = $license['status'];
                if (isset($license['updated_at'])) {
                    $where['updated_at'] = $license['updated_at'];
                    $where_format[] = '%s';
                    $where_format[] = '%s';
                } else {
                    $where_format[] = '%s';
                }
            }

            // Determine table name
            $table_name = isset($license['table_name']) && $license['table_name'] === 'lmfwc_licenses'
                ? 'bz_lmfwc_licenses'
                : $wpdb->prefix . 'vd_licenses';

            $result = $wpdb->update(
                $table_name,
                $update_data,
                $where,
                array('%s', '%s', '%s'),
                $where_format
            );

            if ($result === false) {
                return array(
                    'success' => false,
                    'error' => 'Database update failed: ' . $wpdb->last_error,
                    'error_code' => 'database_update_failed'
                );
            }

            if ($result === 0) {
                return array(
                    'success' => false,
                    'error' => 'No rows updated - possible concurrent modification',
                    'error_code' => 'concurrent_modification',
                    'warning' => 'Status may have been changed by another process'
                );
            }

            // Update related tables if needed
            $related_update_result = $this->update_related_tables_for_status_change($license['id'], $new_status, $options);

            return array(
                'success' => true,
                'rows_affected' => $result,
                'previous_status' => $license['status'],
                'new_status' => $new_status,
                'table_name' => $table_name,
                'related_updates' => $related_update_result,
                'update_timestamp' => $update_data['last_status_change']
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => 'Status update exception: ' . $e->getMessage(),
                'error_code' => 'update_exception'
            );
        }
    }

    /**
     * Update related tables for status change
     *
     * Extracted from VD_License_Validator::update_related_tables_for_status_change()
     * Step 5.1.4 - Related table updates extraction
     *
     * @since 1.6.0
     * @param int $license_id License ID
     * @param string $new_status New status
     * @param array $options Update options
     * @return array Update result
     */
    public function update_related_tables_for_status_change($license_id, $new_status, $options) {
        $results = array(
            'product_statistics_updated' => false,
            'audit_log_created' => false,
            'notifications_queued' => false,
            'errors' => array()
        );

        try {
            // Update product statistics
            if ($options['update_statistics'] ?? true) {
                $stats_result = $this->update_product_statistics_for_status_change($license_id, $new_status);
                $results['product_statistics_updated'] = $stats_result['success'] ?? false;
                if (!$results['product_statistics_updated']) {
                    $results['errors'][] = 'Failed to update product statistics';
                }
            }

            // Create audit log entry
            if ($options['audit_enabled'] ?? true) {
                $audit_result = $this->create_status_change_audit_log($license_id, $new_status, $options);
                $results['audit_log_created'] = $audit_result['success'] ?? false;
                if (!$results['audit_log_created']) {
                    $results['errors'][] = 'Failed to create audit log';
                }
            }

            // Queue notifications if enabled
            if ($options['notifications_enabled'] ?? true) {
                $notification_result = $this->queue_status_change_notifications($license_id, $new_status, $options);
                $results['notifications_queued'] = $notification_result['success'] ?? false;
                if (!$results['notifications_queued']) {
                    $results['errors'][] = 'Failed to queue notifications';
                }
            }

        } catch (Exception $e) {
            $results['errors'][] = 'Related table update exception: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Send status change notification
     *
     * Extracted from VD_License_Validator::send_status_change_notification()
     * Step 5.1.4 - Notification system extraction
     *
     * @since 1.6.0
     * @param array $license License data
     * @param string $old_status Previous status
     * @param string $new_status Current status
     * @param array $context Notification context
     * @return array Notification result
     */
    public function send_status_change_notification($license, $old_status, $new_status, $context = array()) {
        $start_time = microtime(true);

        // Initialize notification context
        $notification_context = array_merge(array(
            'change_type' => 'status_change',
            'triggered_by' => 'system',
            'notification_enabled' => true,
            'priority' => 'normal',
            'retry_enabled' => true,
            'queue_enabled' => true
        ), $context);

        $results = array(
            'notifications_sent' => 0,
            'notifications_queued' => 0,
            'notifications_failed' => 0,
            'execution_time_ms' => 0,
            'notifications' => array(),
            'errors' => array()
        );

        try {
            // Check if notifications are enabled for this change
            $notification_config = $this->get_notification_configuration($license, $old_status, $new_status, $notification_context);

            if (!$notification_config['enabled']) {
                $results['message'] = 'Notifications disabled for this status change';
                return $results;
            }

            // Prepare notification data
            $notification_data = $this->prepare_status_change_notification_data($license, $old_status, $new_status, $notification_context);

            // Send or queue notifications based on configuration
            if ($notification_context['queue_enabled'] && !$notification_config['immediate']) {
                $queue_result = $this->queue_status_change_notifications($license['id'], $new_status, $notification_context);
                $results['notifications_queued'] = $queue_result['queued_count'] ?? 0;
            } else {
                $send_result = $this->send_immediate_status_notifications($notification_data, $notification_config);
                $results['notifications_sent'] = $send_result['sent_count'] ?? 0;
                $results['notifications_failed'] = $send_result['failed_count'] ?? 0;
                $results['notifications'] = $send_result['notifications'] ?? array();
                $results['errors'] = $send_result['errors'] ?? array();
            }

        } catch (Exception $e) {
            $results['notifications_failed']++;
            $results['errors'][] = array(
                'error' => $e->getMessage(),
                'timestamp' => current_time('mysql')
            );
        }

        // Calculate execution time
        $results['execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);

        return $results;
    }

    /**
     * Validate status business rules
     *
     * Step 5.1.4 - Business rules validation extraction
     *
     * @since 1.6.0
     * @param string $status Status to validate
     * @param array $license License data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_status_business_rules($status, $license, $options) {
        switch ($status) {
            case 'suspended':
                return array(
                    'valid' => false,
                    'error' => 'License đã bị tạm khóa hoặc vô hiệu hóa',
                    'code' => 'license_suspended'
                );

            case 'revoked':
                return array(
                    'valid' => false,
                    'error' => 'License đã bị thu hồi và không thể sử dụng',
                    'code' => 'license_revoked'
                );

            case 'expired':
                // Additional expiry validation can be added here
                return array('valid' => true);

            default:
                return array('valid' => true);
        }
    }

    /**
     * Validate automatic transition rules
     *
     * Step 5.1.4 - Automatic transition rules extraction
     *
     * @since 1.6.0
     * @param string $from_status Current status
     * @param string $to_status Target status
     * @param array $license License data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_automatic_transition_rules($from_status, $to_status, $license, $options) {
        // Escalation rule: only allow escalation to more restrictive status
        $escalation_hierarchy = array(
            'pending' => 0,
            'active' => 1,
            'inactive' => 2,
            'expired' => 3,
            'suspended' => 4,
            'revoked' => 5
        );

        $from_level = $escalation_hierarchy[$from_status] ?? 0;
        $to_level = $escalation_hierarchy[$to_status] ?? 0;

        // For automatic transitions, generally only allow escalation (except for reactivation)
        if ($to_level < $from_level && !($options['allow_reactivation'] ?? false)) {
            return array(
                'valid' => false,
                'error' => 'Automatic de-escalation not allowed without explicit permission',
                'error_code' => 'automatic_deescalation_forbidden'
            );
        }

        return array('valid' => true);
    }

    /**
     * Get transition type based on status change
     *
     * Step 5.1.4 - Transition type classification
     *
     * @since 1.6.0
     * @param string $from_status Current status
     * @param string $to_status Target status
     * @return string Transition type
     */
    private function get_transition_type($from_status, $to_status) {
        $from_category = $this->get_status_category($from_status);
        $to_category = $this->get_status_category($to_status);

        if ($from_category === 'operational' && $to_category === 'non_operational') {
            return 'deactivation';
        } elseif ($from_category === 'non_operational' && $to_category === 'operational') {
            return 'reactivation';
        } elseif ($to_category === 'terminated') {
            return 'termination';
        } elseif ($from_category === 'provisional' && $to_category === 'operational') {
            return 'activation';
        } else {
            return 'status_change';
        }
    }

    /**
     * Get notification configuration for status change
     *
     * Step 5.1.4 - Notification configuration utility
     *
     * @since 1.6.0
     * @param array $license License data
     * @param string $old_status Previous status
     * @param string $new_status Current status
     * @param array $context Notification context
     * @return array Notification configuration
     */
    private function get_notification_configuration($license, $old_status, $new_status, $context) {
        // Default configuration
        $config = array(
            'enabled' => true,
            'immediate' => false,
            'channels' => array('email'),
            'priority' => 'normal'
        );

        // High priority for critical status changes
        if (in_array($new_status, array('suspended', 'revoked'))) {
            $config['priority'] = 'high';
            $config['immediate'] = true;
        }

        // Low priority for routine changes
        if ($new_status === 'expired') {
            $config['priority'] = 'low';
        }

        return $config;
    }

    /**
     * Update product statistics for status change
     *
     * Step 5.1.4 - Statistics update utility
     *
     * @since 1.6.0
     * @param int $license_id License ID
     * @param string $new_status New status
     * @return array Update result
     */
    private function update_product_statistics_for_status_change($license_id, $new_status) {
        // Placeholder for product statistics update
        // In a real implementation, this would update product usage statistics
        return array(
            'success' => true,
            'statistics_updated' => array(
                'license_id' => $license_id,
                'new_status' => $new_status,
                'timestamp' => current_time('mysql')
            )
        );
    }

    /**
     * Create audit log for status change
     *
     * Step 5.1.4 - Audit logging utility
     *
     * @since 1.6.0
     * @param int $license_id License ID
     * @param string $new_status New status
     * @param array $options Update options
     * @return array Audit log result
     */
    private function create_status_change_audit_log($license_id, $new_status, $options) {
        // Placeholder for audit log creation
        // In a real implementation, this would create an audit trail entry
        return array(
            'success' => true,
            'audit_log_id' => 'AL_' . time() . '_' . $license_id,
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Queue status change notifications
     *
     * Step 5.1.4 - Notification queuing utility
     *
     * @since 1.6.0
     * @param int $license_id License ID
     * @param string $new_status New status
     * @param array $options Notification options
     * @return array Queue result
     */
    private function queue_status_change_notifications($license_id, $new_status, $options) {
        // Placeholder for notification queuing
        // In a real implementation, this would queue notifications for background processing
        return array(
            'success' => true,
            'queued_count' => 1,
            'queue_id' => 'NQ_' . time() . '_' . $license_id
        );
    }

    /**
     * Prepare notification data for status change
     *
     * Step 5.1.4 - Notification data preparation utility
     *
     * @since 1.6.0
     * @param array $license License data
     * @param string $old_status Previous status
     * @param string $new_status Current status
     * @param array $context Notification context
     * @return array Notification data
     */
    private function prepare_status_change_notification_data($license, $old_status, $new_status, $context) {
        return array(
            'license_key' => substr($license['license_key'], 0, 8) . '...',
            'license_id' => $license['id'],
            'old_status' => $old_status,
            'new_status' => $new_status,
            'old_status_description' => $this->get_status_description($old_status),
            'new_status_description' => $this->get_status_description($new_status),
            'transition_type' => $this->get_transition_type($old_status, $new_status),
            'timestamp' => current_time('mysql'),
            'context' => $context
        );
    }

    /**
     * Send immediate status notifications
     *
     * Step 5.1.4 - Immediate notification sending utility
     *
     * @since 1.6.0
     * @param array $notification_data Notification data
     * @param array $config Notification configuration
     * @return array Send result
     */
    private function send_immediate_status_notifications($notification_data, $config) {
        // Placeholder for immediate notification sending
        // In a real implementation, this would send notifications via configured channels
        return array(
            'sent_count' => 1,
            'failed_count' => 0,
            'notifications' => array(
                array(
                    'channel' => 'email',
                    'status' => 'sent',
                    'timestamp' => current_time('mysql')
                )
            ),
            'errors' => array()
        );
    }

    /**
     * Get valid status enums
     *
     * Step 5.1.4 - Status validation utility
     *
     * @since 1.6.0
     * @return array Valid license status values
     */
    public function get_valid_status_enums() {
        return $this->valid_status_enums;
    }

    /**
     * Get all status descriptions
     *
     * Step 5.1.4 - Status descriptions utility
     *
     * @since 1.6.0
     * @return array Status descriptions
     */
    public function get_all_status_descriptions() {
        return $this->status_descriptions;
    }

    /**
     * Get all status categories
     *
     * Step 5.1.4 - Status categories utility
     *
     * @since 1.6.0
     * @return array Status categories
     */
    public function get_all_status_categories() {
        return $this->status_categories;
    }
}
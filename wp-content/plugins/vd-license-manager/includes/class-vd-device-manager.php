<?php
/**
 * VD License Manager - Device Manager
 *
 * Handles device registration, approval, and management
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * VD_Device_Manager class
 *
 * Step 2.6: Device management for license validation
 */
class VD_Device_Manager {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Table name (without prefix)
     */
    private $table_name = 'bz_vd_device_requests';

    /**
     * Full table name (with prefix)
     */
    private $full_table_name;

    /**
     * License core instance
     */
    private $license_core;

    /**
     * Private constructor
     */
    private function __construct() {
        global $wpdb;
        $this->full_table_name = $wpdb->prefix . $this->table_name;

        // Initialize license core
        if (class_exists('VD_License_Core')) {
            $this->license_core = VD_License_Core::get_instance();
        }
    }

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register new device for a license
     *
     * @param string $license_key License key
     * @param array $device_data Device information
     * @return array Registration result
     */
    public function register_device($license_key, $device_data) {
        global $wpdb;

        // Validate license
        if (!$this->license_core) {
            return $this->error_response('License manager not available');
        }

        $license = $this->license_core->get_license_by_key($license_key);
        if (!$license) {
            return $this->error_response('Invalid license key');
        }

        if ($license->status !== 'active') {
            return $this->error_response('License is not active');
        }

        // Check if license has expired
        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            return $this->error_response('License has expired');
        }

        // Validate device data
        $validation_result = $this->validate_device_data($device_data);
        if (is_wp_error($validation_result)) {
            return $this->error_response($validation_result->get_error_message());
        }

        // Check device limit
        $current_devices = $this->get_active_device_count($license->id);
        if ($current_devices >= $license->device_limit) {
            return $this->error_response('Device limit exceeded for this license');
        }

        // Check if device already exists
        $existing_device = $this->get_device_by_fingerprint($license->id, $device_data['device_fingerprint']);
        if ($existing_device) {
            if ($existing_device->status === 'approved' || $existing_device->status === 'auto_approved') {
                return $this->success_response('Device already registered and approved', [
                    'device_id' => $existing_device->id,
                    'status' => $existing_device->status
                ]);
            } else {
                return $this->error_response('Device registration pending approval');
            }
        }

        // Calculate approval threshold for auto-approval
        $approval_threshold = $this->calculate_approval_threshold($license, $device_data);

        // Determine initial status
        $auto_approval_threshold = get_option('vd_license_manager_auto_approval_threshold', 25.0);
        $initial_status = ($approval_threshold >= $auto_approval_threshold) ? 'auto_approved' : 'pending';

        // Prepare device data for insertion
        $insert_data = [
            'license_id' => $license->id,
            'device_name' => sanitize_text_field($device_data['device_name']),
            'device_fingerprint' => sanitize_text_field($device_data['device_fingerprint']),
            'device_info' => maybe_serialize($device_data['device_info'] ?? []),
            'request_ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'status' => $initial_status,
            'approval_threshold' => $approval_threshold,
            'expires_at' => $this->calculate_device_expiration($license),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // If auto-approved, set approval details
        if ($initial_status === 'auto_approved') {
            $insert_data['approved_by'] = 'system_auto';
            $insert_data['approved_at'] = current_time('mysql');
        }

        // Insert device request
        $result = $wpdb->insert(
            $this->full_table_name,
            $insert_data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            error_log('[VD License Manager] Step 2.6 - Device registration failed: ' . $wpdb->last_error);
            return $this->error_response('Device registration failed');
        }

        $device_id = $wpdb->insert_id;

        // Log the registration
        $this->log_device_action($device_id, 'registration', 'Device registered', [
            'license_key' => $license_key,
            'approval_threshold' => $approval_threshold,
            'auto_approved' => ($initial_status === 'auto_approved')
        ]);

        error_log('[VD License Manager] Step 2.6 - Device registered successfully: ID ' . $device_id . ', Status: ' . $initial_status);

        return $this->success_response('Device registered successfully', [
            'device_id' => $device_id,
            'status' => $initial_status,
            'approval_threshold' => $approval_threshold,
            'requires_approval' => ($initial_status === 'pending')
        ]);
    }

    /**
     * Approve a pending device request
     *
     * @param int $device_id Device request ID
     * @param string $approved_by Who approved the device
     * @return bool True on success, false on failure
     */
    public function approve_device($device_id, $approved_by = '') {
        global $wpdb;

        if (!is_numeric($device_id) || $device_id <= 0) {
            return false;
        }

        // Get device request
        $device = $this->get_device_request($device_id);
        if (!$device) {
            return false;
        }

        if ($device->status !== 'pending') {
            error_log('[VD License Manager] Step 2.6 - Cannot approve device with status: ' . $device->status);
            return false;
        }

        // Check device limit again
        $license = $this->license_core->get_license($device->license_id);
        if (!$license) {
            return false;
        }

        $current_devices = $this->get_active_device_count($device->license_id);
        if ($current_devices >= $license->device_limit) {
            error_log('[VD License Manager] Step 2.6 - Cannot approve device: device limit exceeded');
            return false;
        }

        // Update device status
        $result = $wpdb->update(
            $this->full_table_name,
            [
                'status' => 'approved',
                'approved_by' => $approved_by ?: wp_get_current_user()->user_login ?: 'admin',
                'approved_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $device_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            error_log('[VD License Manager] Step 2.6 - Device approval failed: ' . $wpdb->last_error);
            return false;
        }

        // Log the approval
        $this->log_device_action($device_id, 'approval', 'Device approved manually', [
            'approved_by' => $approved_by ?: 'admin'
        ]);

        error_log('[VD License Manager] Step 2.6 - Device ID ' . $device_id . ' approved successfully');
        return true;
    }

    /**
     * Reject a pending device request
     *
     * @param int $device_id Device request ID
     * @param string $reason Rejection reason
     * @return bool True on success, false on failure
     */
    public function reject_device($device_id, $reason = '') {
        global $wpdb;

        if (!is_numeric($device_id) || $device_id <= 0) {
            return false;
        }

        // Update device status
        $result = $wpdb->update(
            $this->full_table_name,
            [
                'status' => 'rejected',
                'rejection_reason' => sanitize_text_field($reason),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $device_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            error_log('[VD License Manager] Step 2.6 - Device rejection failed: ' . $wpdb->last_error);
            return false;
        }

        // Log the rejection
        $this->log_device_action($device_id, 'rejection', 'Device rejected', [
            'reason' => $reason
        ]);

        error_log('[VD License Manager] Step 2.6 - Device ID ' . $device_id . ' rejected successfully');
        return true;
    }

    /**
     * Get device requests with pagination and filters
     *
     * @param array $args Query arguments
     * @return array Array with 'devices' and 'total_count'
     */
    public function get_device_requests($args = []) {
        global $wpdb;

        // Default arguments
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'license_id' => 0,
            'status' => '',
            'search' => '',
            'order_by' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);

        // Build WHERE clause
        $where_conditions = ['1=1'];
        $where_values = [];

        if (!empty($args['license_id'])) {
            $where_conditions[] = 'license_id = %d';
            $where_values[] = $args['license_id'];
        }

        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where_conditions[] = '(device_name LIKE %s OR device_fingerprint LIKE %s OR request_ip LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Validate order by
        $allowed_order_by = ['id', 'license_id', 'device_name', 'status', 'created_at', 'approved_at'];
        if (!in_array($args['order_by'], $allowed_order_by)) {
            $args['order_by'] = 'created_at';
        }

        // Validate order
        $args['order'] = strtoupper($args['order']);
        if (!in_array($args['order'], ['ASC', 'DESC'])) {
            $args['order'] = 'DESC';
        }

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->full_table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total_count = $wpdb->get_var($count_sql);

        // Get devices
        $sql = "SELECT * FROM {$this->full_table_name}
                WHERE {$where_clause}
                ORDER BY {$args['order_by']} {$args['order']}
                LIMIT %d OFFSET %d";

        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        $devices = $wpdb->get_results($wpdb->prepare($sql, $where_values));

        return [
            'devices' => $devices,
            'total_count' => $total_count
        ];
    }

    /**
     * Get device request by ID
     *
     * @param int $device_id Device ID
     * @return object|null Device object or null if not found
     */
    public function get_device_request($device_id) {
        global $wpdb;

        if (!is_numeric($device_id) || $device_id <= 0) {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->full_table_name} WHERE id = %d",
                $device_id
            )
        );
    }

    /**
     * Get device by fingerprint for a specific license
     *
     * @param int $license_id License ID
     * @param string $fingerprint Device fingerprint
     * @return object|null Device object or null if not found
     */
    private function get_device_by_fingerprint($license_id, $fingerprint) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->full_table_name} WHERE license_id = %d AND device_fingerprint = %s ORDER BY created_at DESC LIMIT 1",
                $license_id,
                $fingerprint
            )
        );
    }

    /**
     * Get count of active devices for a license
     *
     * @param int $license_id License ID
     * @return int Active device count
     */
    private function get_active_device_count($license_id) {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->full_table_name} WHERE license_id = %d AND status IN ('approved', 'auto_approved')",
                $license_id
            )
        );
    }

    /**
     * Calculate approval threshold for automatic approval
     *
     * @param object $license License object
     * @param array $device_data Device data
     * @return float Approval threshold percentage
     */
    private function calculate_approval_threshold($license, $device_data) {
        $threshold = 0.0;

        // Factor 1: License age (older licenses get higher threshold)
        $license_age_days = (time() - strtotime($license->created_at)) / (24 * 60 * 60);
        if ($license_age_days > 30) {
            $threshold += 20.0;
        } elseif ($license_age_days > 7) {
            $threshold += 10.0;
        }

        // Factor 2: Owner information completeness
        if (!empty($license->owner_name) && !empty($license->owner_email)) {
            $threshold += 15.0;
        }

        // Factor 3: Device information completeness
        if (!empty($device_data['device_name']) && strlen($device_data['device_name']) > 5) {
            $threshold += 10.0;
        }

        // Factor 4: IP reputation (simplified - in production would check against threat databases)
        $ip = $this->get_client_ip();
        if (!$this->is_suspicious_ip($ip)) {
            $threshold += 15.0;
        }

        // Factor 5: User agent analysis
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($this->is_legitimate_user_agent($user_agent)) {
            $threshold += 10.0;
        }

        // Factor 6: Previous approvals for this license
        $previous_devices = $this->get_active_device_count($license->id);
        if ($previous_devices === 0) {
            $threshold += 15.0; // First device gets bonus
        }

        return min(100.0, $threshold);
    }

    /**
     * Calculate device expiration date
     *
     * @param object $license License object
     * @return string|null Expiration date or null
     */
    private function calculate_device_expiration($license) {
        if ($license->expires_at) {
            return $license->expires_at;
        }

        // Default to 1 year from now if license doesn't expire
        return date('Y-m-d H:i:s', strtotime('+1 year'));
    }

    /**
     * Validate device data
     *
     * @param array $data Device data
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_device_data($data) {
        $errors = [];

        if (empty($data['device_name'])) {
            $errors[] = 'Device name is required';
        }

        if (empty($data['device_fingerprint'])) {
            $errors[] = 'Device fingerprint is required';
        }

        if (!empty($data['device_name']) && strlen($data['device_name']) > 255) {
            $errors[] = 'Device name is too long';
        }

        if (!empty($data['device_fingerprint']) && strlen($data['device_fingerprint']) > 1000) {
            $errors[] = 'Device fingerprint is too long';
        }

        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(', ', $errors));
        }

        return true;
    }

    /**
     * Check if IP is suspicious (simplified check)
     *
     * @param string $ip IP address
     * @return bool True if suspicious
     */
    private function is_suspicious_ip($ip) {
        // Simplified checks - in production would use threat intelligence
        $suspicious_patterns = [
            '/^10\./',     // Private network
            '/^192\.168\./', // Private network
            '/^172\./',    // Private network (simplified)
            '/^127\./',    // Localhost
        ];

        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $ip)) {
                return false; // Actually these are not suspicious, just private
            }
        }

        return false; // Default to not suspicious
    }

    /**
     * Check if user agent appears legitimate
     *
     * @param string $user_agent User agent string
     * @return bool True if appears legitimate
     */
    private function is_legitimate_user_agent($user_agent) {
        if (empty($user_agent)) {
            return false;
        }

        // Check for common legitimate browsers
        $legitimate_patterns = [
            '/Chrome/',
            '/Firefox/',
            '/Safari/',
            '/Edge/',
            '/Opera/',
        ];

        foreach ($legitimate_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get client IP address
     *
     * @return string Client IP
     */
    private function get_client_ip() {
        $ip_fields = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                $ip = $_SERVER[$field];
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '127.0.0.1';
    }

    /**
     * Log device action
     *
     * @param int $device_id Device ID
     * @param string $action Action type
     * @param string $description Action description
     * @param array $metadata Additional metadata
     */
    private function log_device_action($device_id, $action, $description, $metadata = []) {
        global $wpdb;

        // Insert into access logs table
        $log_data = [
            'device_request_id' => $device_id,
            'action_type' => 'device_register',
            'action_details' => maybe_serialize(array_merge(['action' => $action, 'description' => $description], $metadata)),
            'request_ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'response_status' => 'success',
            'created_at' => current_time('mysql')
        ];

        $wpdb->insert(
            $wpdb->prefix . 'bz_vd_access_logs',
            $log_data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Create success response array
     *
     * @param string $message Success message
     * @param array $data Additional data
     * @return array Success response
     */
    private function success_response($message, $data = []) {
        return array_merge([
            'success' => true,
            'message' => $message
        ], $data);
    }

    /**
     * Create error response array
     *
     * @param string $message Error message
     * @return array Error response
     */
    private function error_response($message) {
        return [
            'success' => false,
            'error' => $message
        ];
    }

    /**
     * Get device statistics
     *
     * @return array Statistics array
     */
    public function get_device_stats() {
        global $wpdb;

        $stats = [];

        // Total devices
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->full_table_name}");

        // Status breakdown
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->full_table_name} GROUP BY status"
        );

        foreach ($status_counts as $status) {
            $stats['by_status'][$status->status] = $status->count;
        }

        // Pending approvals
        $stats['pending_approval'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->full_table_name} WHERE status = 'pending'"
        );

        // Recent registrations (last 24 hours)
        $stats['recent'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->full_table_name} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-24 hours'))
            )
        );

        return $stats;
    }

    /**
     * Get table name
     *
     * @return string Full table name
     */
    public function get_table_name() {
        return $this->full_table_name;
    }
}
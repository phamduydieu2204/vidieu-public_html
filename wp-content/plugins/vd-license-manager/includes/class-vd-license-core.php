<?php
/**
 * VD License Manager - License Core Operations
 *
 * Handles basic CRUD operations for license management
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * VD_License_Core class
 *
 * Step 2.4: Basic CRUD operations for bz_vd_licenses table
 */
class VD_License_Core {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Table name (without prefix)
     */
    private $table_name = 'bz_vd_licenses';

    /**
     * Full table name (with prefix)
     */
    private $full_table_name;

    /**
     * Private constructor
     */
    private function __construct() {
        global $wpdb;
        $this->full_table_name = $wpdb->prefix . $this->table_name;
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
     * CREATE: Add new license
     *
     * @param array $license_data License data array
     * @return int|false License ID on success, false on failure
     */
    public function create_license($license_data) {
        global $wpdb;

        // Validate required fields
        $validation_result = $this->validate_license_data($license_data, 'create');
        if (is_wp_error($validation_result)) {
            error_log('[VD License Manager] Step 2.4 - Create validation failed: ' . $validation_result->get_error_message());
            return false;
        }

        // Prepare data for insertion
        $insert_data = $this->prepare_license_data($license_data);

        // Insert into database
        $result = $wpdb->insert(
            $this->full_table_name,
            $insert_data,
            $this->get_data_format($insert_data)
        );

        if ($result === false) {
            error_log('[VD License Manager] Step 2.4 - Database insert failed: ' . $wpdb->last_error);
            return false;
        }

        $license_id = $wpdb->insert_id;
        error_log('[VD License Manager] Step 2.4 - License created successfully with ID: ' . $license_id);

        return $license_id;
    }

    /**
     * READ: Get license by ID
     *
     * @param int $license_id License ID
     * @return object|null License object or null if not found
     */
    public function get_license($license_id) {
        global $wpdb;

        if (!is_numeric($license_id) || $license_id <= 0) {
            return null;
        }

        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->full_table_name} WHERE id = %d",
                $license_id
            )
        );

        return $license;
    }

    /**
     * READ: Get license by key
     *
     * @param string $license_key License key
     * @return object|null License object or null if not found
     */
    public function get_license_by_key($license_key) {
        global $wpdb;

        if (empty($license_key)) {
            return null;
        }

        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->full_table_name} WHERE license_key = %s",
                $license_key
            )
        );

        return $license;
    }

    /**
     * READ: Get licenses with pagination and filters
     *
     * @param array $args Query arguments
     * @return array Array with 'licenses' and 'total_count'
     */
    public function get_licenses($args = []) {
        global $wpdb;

        // Default arguments
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'status' => '',
            'product_id' => 0,
            'search' => '',
            'order_by' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);

        // Build WHERE clause
        $where_conditions = ['1=1'];
        $where_values = [];

        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if (!empty($args['product_id'])) {
            $where_conditions[] = 'product_id = %d';
            $where_values[] = $args['product_id'];
        }

        if (!empty($args['search'])) {
            $where_conditions[] = '(license_key LIKE %s OR owner_name LIKE %s OR owner_email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Validate order by
        $allowed_order_by = ['id', 'license_key', 'product_id', 'status', 'created_at', 'updated_at', 'expires_at'];
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

        // Get licenses
        $sql = "SELECT * FROM {$this->full_table_name}
                WHERE {$where_clause}
                ORDER BY {$args['order_by']} {$args['order']}
                LIMIT %d OFFSET %d";

        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        $licenses = $wpdb->get_results($wpdb->prepare($sql, $where_values));

        return [
            'licenses' => $licenses,
            'total_count' => $total_count
        ];
    }

    /**
     * UPDATE: Update license data
     *
     * @param int $license_id License ID
     * @param array $license_data Updated data
     * @return bool True on success, false on failure
     */
    public function update_license($license_id, $license_data) {
        global $wpdb;

        if (!is_numeric($license_id) || $license_id <= 0) {
            return false;
        }

        // Check if license exists
        if (!$this->license_exists($license_id)) {
            error_log('[VD License Manager] Step 2.4 - License ID ' . $license_id . ' not found for update');
            return false;
        }

        // Validate data
        $validation_result = $this->validate_license_data($license_data, 'update');
        if (is_wp_error($validation_result)) {
            error_log('[VD License Manager] Step 2.4 - Update validation failed: ' . $validation_result->get_error_message());
            return false;
        }

        // Prepare data for update
        $update_data = $this->prepare_license_data($license_data, false);

        // Always update the updated_at timestamp
        $update_data['updated_at'] = current_time('mysql');

        // Update database
        $result = $wpdb->update(
            $this->full_table_name,
            $update_data,
            ['id' => $license_id],
            $this->get_data_format($update_data),
            ['%d']
        );

        if ($result === false) {
            error_log('[VD License Manager] Step 2.4 - Database update failed: ' . $wpdb->last_error);
            return false;
        }

        error_log('[VD License Manager] Step 2.4 - License ID ' . $license_id . ' updated successfully');
        return true;
    }

    /**
     * DELETE: Delete license (soft delete by setting status to inactive)
     *
     * @param int $license_id License ID
     * @param bool $hard_delete Whether to permanently delete
     * @return bool True on success, false on failure
     */
    public function delete_license($license_id, $hard_delete = false) {
        global $wpdb;

        if (!is_numeric($license_id) || $license_id <= 0) {
            return false;
        }

        // Check if license exists
        if (!$this->license_exists($license_id)) {
            error_log('[VD License Manager] Step 2.4 - License ID ' . $license_id . ' not found for deletion');
            return false;
        }

        if ($hard_delete) {
            // Hard delete - permanently remove from database
            $result = $wpdb->delete(
                $this->full_table_name,
                ['id' => $license_id],
                ['%d']
            );

            $action = 'permanently deleted';
        } else {
            // Soft delete - set status to inactive
            $result = $wpdb->update(
                $this->full_table_name,
                [
                    'status' => 'inactive',
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $license_id],
                ['%s', '%s'],
                ['%d']
            );

            $action = 'marked as inactive';
        }

        if ($result === false) {
            error_log('[VD License Manager] Step 2.4 - License deletion failed: ' . $wpdb->last_error);
            return false;
        }

        error_log('[VD License Manager] Step 2.4 - License ID ' . $license_id . ' ' . $action . ' successfully');
        return true;
    }

    /**
     * Validate license data
     *
     * @param array $data License data
     * @param string $operation 'create' or 'update'
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_license_data($data, $operation = 'create') {
        $errors = [];

        // Required fields for creation
        if ($operation === 'create') {
            if (empty($data['license_key'])) {
                $errors[] = 'License key is required';
            }

            if (empty($data['product_id']) || !is_numeric($data['product_id'])) {
                $errors[] = 'Valid product ID is required';
            }
        }

        // Validate license key format and uniqueness
        if (!empty($data['license_key'])) {
            if (strlen($data['license_key']) < 8) {
                $errors[] = 'License key must be at least 8 characters long';
            }

            // Check for uniqueness (only for new licenses or when changing key)
            if ($operation === 'create' || !empty($data['license_key'])) {
                $existing = $this->get_license_by_key($data['license_key']);
                if ($existing) {
                    $errors[] = 'License key already exists';
                }
            }
        }

        // Validate email format
        if (!empty($data['owner_email']) && !is_email($data['owner_email'])) {
            $errors[] = 'Invalid email format';
        }

        // Validate status
        if (!empty($data['status'])) {
            $allowed_statuses = ['active', 'inactive', 'expired', 'suspended'];
            if (!in_array($data['status'], $allowed_statuses)) {
                $errors[] = 'Invalid status value';
            }
        }

        // Validate device limit
        if (isset($data['device_limit'])) {
            if (!is_numeric($data['device_limit']) || $data['device_limit'] < 1 || $data['device_limit'] > 100) {
                $errors[] = 'Device limit must be between 1 and 100';
            }
        }

        // Validate expiration date
        if (!empty($data['expires_at'])) {
            $timestamp = strtotime($data['expires_at']);
            if ($timestamp === false) {
                $errors[] = 'Invalid expiration date format';
            }
        }

        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(', ', $errors));
        }

        return true;
    }

    /**
     * Prepare license data for database operations
     *
     * @param array $data Raw data
     * @param bool $include_timestamps Whether to include created_at/updated_at
     * @return array Prepared data
     */
    private function prepare_license_data($data, $include_timestamps = true) {
        $prepared = [];

        // Allowed fields mapping
        $field_map = [
            'license_key' => 'license_key',
            'product_id' => 'product_id',
            'owner_name' => 'owner_name',
            'owner_email' => 'owner_email',
            'status' => 'status',
            'device_limit' => 'device_limit',
            'expires_at' => 'expires_at'
        ];

        foreach ($field_map as $input_key => $db_key) {
            if (array_key_exists($input_key, $data)) {
                $prepared[$db_key] = $data[$input_key];
            }
        }

        // Set defaults for creation
        if ($include_timestamps) {
            $prepared['created_at'] = current_time('mysql');
            $prepared['updated_at'] = current_time('mysql');
        }

        // Set default values
        if (!isset($prepared['status'])) {
            $prepared['status'] = 'active';
        }

        if (!isset($prepared['device_limit'])) {
            $prepared['device_limit'] = 3; // Default from plugin settings
        }

        // Sanitize string fields
        if (isset($prepared['owner_name'])) {
            $prepared['owner_name'] = sanitize_text_field($prepared['owner_name']);
        }

        if (isset($prepared['owner_email'])) {
            $prepared['owner_email'] = sanitize_email($prepared['owner_email']);
        }

        return $prepared;
    }

    /**
     * Get data format array for wpdb operations
     *
     * @param array $data Data array
     * @return array Format array
     */
    private function get_data_format($data) {
        $format = [];

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'id':
                case 'product_id':
                case 'device_limit':
                    $format[] = '%d';
                    break;
                case 'created_at':
                case 'updated_at':
                case 'expires_at':
                    $format[] = '%s';
                    break;
                default:
                    $format[] = '%s';
                    break;
            }
        }

        return $format;
    }

    /**
     * Check if license exists
     *
     * @param int $license_id License ID
     * @return bool True if exists, false otherwise
     */
    private function license_exists($license_id) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->full_table_name} WHERE id = %d",
                $license_id
            )
        );

        return $count > 0;
    }

    /**
     * Get license statistics
     *
     * @return array Statistics array
     */
    public function get_license_stats() {
        global $wpdb;

        $stats = [];

        // Total licenses
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->full_table_name}");

        // Status breakdown
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->full_table_name} GROUP BY status"
        );

        foreach ($status_counts as $status) {
            $stats['by_status'][$status->status] = $status->count;
        }

        // Recent licenses (last 30 days)
        $stats['recent'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->full_table_name} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );

        // Expiring soon (next 30 days)
        $stats['expiring_soon'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->full_table_name}
                 WHERE expires_at IS NOT NULL
                 AND expires_at BETWEEN %s AND %s
                 AND status = 'active'",
                current_time('mysql'),
                date('Y-m-d H:i:s', strtotime('+30 days'))
            )
        );

        return $stats;
    }

    /**
     * Generate unique license key
     *
     * @param int $length Key length
     * @return string Generated license key
     */
    public function generate_license_key($length = 16) {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude confusing characters
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[wp_rand(0, strlen($characters) - 1)];
        }

        // Add separators for readability
        $formatted_key = '';
        for ($i = 0; $i < strlen($key); $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $formatted_key .= '-';
            }
            $formatted_key .= $key[$i];
        }

        // Ensure uniqueness
        if ($this->get_license_by_key($formatted_key)) {
            return $this->generate_license_key($length); // Regenerate if exists
        }

        return $formatted_key;
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
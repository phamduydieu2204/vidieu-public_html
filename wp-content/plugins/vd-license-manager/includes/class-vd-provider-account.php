<?php
/**
 * VD License Manager - Provider Account Manager
 *
 * Handles provider account management with encryption
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * VD_Provider_Account class
 *
 * Step 2.6: Provider account management with encryption support
 */
class VD_Provider_Account {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Table name (without prefix)
     */
    private $table_name = 'vd_provider_accounts';

    /**
     * Full table name (with prefix)
     */
    private $full_table_name;

    /**
     * Encryption manager instance
     */
    private $encryption_manager;

    /**
     * Private constructor
     */
    private function __construct() {
        global $wpdb;
        $this->full_table_name = $wpdb->prefix . $this->table_name;

        // Initialize encryption manager
        if (class_exists('VD_Encryption_Manager')) {
            $this->encryption_manager = VD_Encryption_Manager::get_instance();
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
     * CREATE: Add new provider account with encryption
     *
     * @param array $account_data Provider account data
     * @return int|false Account ID on success, false on failure
     */
    public function create_provider_account($account_data) {
        global $wpdb;

        // Validate required fields
        $validation_result = $this->validate_account_data($account_data, 'create');
        if (is_wp_error($validation_result)) {
            error_log('[VD License Manager] Step 2.6 - Provider creation validation failed: ' . $validation_result->get_error_message());
            return false;
        }

        // Encrypt sensitive data
        $encrypted_data = $this->encrypt_sensitive_data($account_data);
        if (!$encrypted_data) {
            error_log('[VD License Manager] Step 2.6 - Provider account encryption failed');
            return false;
        }

        // Prepare data for insertion
        $insert_data = $this->prepare_account_data($encrypted_data);

        // Insert into database
        $result = $wpdb->insert(
            $this->full_table_name,
            $insert_data,
            $this->get_data_format($insert_data)
        );

        if ($result === false) {
            error_log('[VD License Manager] Step 2.6 - Provider account database insert failed: ' . $wpdb->last_error);
            return false;
        }

        $account_id = $wpdb->insert_id;

        // Log audit trail for account creation
        $this->log_audit_trail($account_id, 'creation', 'Provider account created', [
            'account_name' => $account_data['account_name'] ?? '',
            'product_id' => $account_data['product_id'] ?? 0
        ]);

        error_log('[VD License Manager] Step 2.6 - Provider account created successfully with ID: ' . $account_id);

        return $account_id;
    }

    /**
     * READ: Get provider account by ID (decrypts sensitive data)
     *
     * @param int $account_id Account ID
     * @param bool $decrypt_sensitive Whether to decrypt sensitive fields
     * @return object|null Account object or null if not found
     */
    public function get_provider_account($account_id, $decrypt_sensitive = true) {
        global $wpdb;

        if (!is_numeric($account_id) || $account_id <= 0) {
            return null;
        }

        $account = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->full_table_name} WHERE id = %d",
                $account_id
            )
        );

        if (!$account) {
            return null;
        }

        // Decrypt sensitive data if requested
        if ($decrypt_sensitive) {
            $account = $this->decrypt_account_data($account);
        }

        return $account;
    }

    /**
     * READ: Get provider accounts with filters and pagination
     *
     * @param array $args Query arguments
     * @param bool $decrypt_sensitive Whether to decrypt sensitive fields
     * @return array Array with 'accounts' and 'total_count'
     */
    public function get_provider_accounts($args = [], $decrypt_sensitive = false) {
        global $wpdb;

        // Default arguments
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'product_id' => 0,
            'account_status' => '',
            'search' => '',
            'health_score_min' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);

        // Build WHERE clause
        $where_conditions = ['1=1'];
        $where_values = [];

        if (!empty($args['product_id'])) {
            $where_conditions[] = 'product_id = %d';
            $where_values[] = $args['product_id'];
        }

        if (!empty($args['account_status'])) {
            $where_conditions[] = 'account_status = %s';
            $where_values[] = $args['account_status'];
        }

        if (!empty($args['search'])) {
            $where_conditions[] = 'account_name LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        if ($args['health_score_min'] > 0) {
            $where_conditions[] = 'health_score >= %f';
            $where_values[] = $args['health_score_min'];
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Validate order by
        $allowed_order_by = ['id', 'account_name', 'product_id', 'account_status', 'health_score', 'created_at', 'updated_at'];
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

        // Get accounts
        $sql = "SELECT * FROM {$this->full_table_name}
                WHERE {$where_clause}
                ORDER BY {$args['order_by']} {$args['order']}
                LIMIT %d OFFSET %d";

        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        $accounts = $wpdb->get_results($wpdb->prepare($sql, $where_values));

        // Decrypt sensitive data if requested
        if ($decrypt_sensitive && !empty($accounts)) {
            foreach ($accounts as &$account) {
                $account = $this->decrypt_account_data($account);
            }
        }

        return [
            'accounts' => $accounts,
            'total_count' => $total_count
        ];
    }

    /**
     * UPDATE: Update provider account with encryption
     *
     * @param int $account_id Account ID
     * @param array $account_data Updated data
     * @return bool True on success, false on failure
     */
    public function update_provider_account($account_id, $account_data) {
        global $wpdb;

        if (!is_numeric($account_id) || $account_id <= 0) {
            return false;
        }

        // Check if account exists
        if (!$this->account_exists($account_id)) {
            error_log('[VD License Manager] Step 2.6 - Provider account ID ' . $account_id . ' not found for update');
            return false;
        }

        // Get current account for audit trail
        $old_account = $this->get_provider_account($account_id, false); // Don't decrypt for comparison

        // Validate data
        $validation_result = $this->validate_account_data($account_data, 'update');
        if (is_wp_error($validation_result)) {
            error_log('[VD License Manager] Step 2.6 - Provider update validation failed: ' . $validation_result->get_error_message());
            return false;
        }

        // Encrypt sensitive data if provided
        $encrypted_data = $account_data;
        if ($this->has_sensitive_data($account_data)) {
            $encrypted_data = $this->encrypt_sensitive_data($account_data);
            if (!$encrypted_data) {
                error_log('[VD License Manager] Step 2.6 - Provider account encryption failed during update');
                return false;
            }
        }

        // Prepare data for update
        $update_data = $this->prepare_account_data($encrypted_data, false);

        // Always update the updated_at timestamp
        $update_data['updated_at'] = current_time('mysql');

        // Update database
        $result = $wpdb->update(
            $this->full_table_name,
            $update_data,
            ['id' => $account_id],
            $this->get_data_format($update_data),
            ['%d']
        );

        if ($result === false) {
            error_log('[VD License Manager] Step 2.6 - Provider account database update failed: ' . $wpdb->last_error);
            return false;
        }

        // Log audit trail for update
        $this->log_audit_trail($account_id, 'update', 'Provider account updated', [
            'fields_updated' => array_keys($update_data)
        ]);

        error_log('[VD License Manager] Step 2.6 - Provider account ID ' . $account_id . ' updated successfully');
        return true;
    }

    /**
     * DELETE: Deactivate provider account (soft delete)
     *
     * @param int $account_id Account ID
     * @param bool $hard_delete Whether to permanently delete
     * @return bool True on success, false on failure
     */
    public function delete_provider_account($account_id, $hard_delete = false) {
        global $wpdb;

        if (!is_numeric($account_id) || $account_id <= 0) {
            return false;
        }

        // Check if account exists
        if (!$this->account_exists($account_id)) {
            error_log('[VD License Manager] Step 2.6 - Provider account ID ' . $account_id . ' not found for deletion');
            return false;
        }

        if ($hard_delete) {
            // Hard delete - permanently remove from database
            $result = $wpdb->delete(
                $this->full_table_name,
                ['id' => $account_id],
                ['%d']
            );

            $action = 'permanently deleted';
            $audit_action = 'deletion';
        } else {
            // Soft delete - set status to inactive
            $result = $wpdb->update(
                $this->full_table_name,
                [
                    'account_status' => 'inactive',
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $account_id],
                ['%s', '%s'],
                ['%d']
            );

            $action = 'deactivated';
            $audit_action = 'deactivation';
        }

        if ($result === false) {
            error_log('[VD License Manager] Step 2.6 - Provider account deletion failed: ' . $wpdb->last_error);
            return false;
        }

        // Log audit trail
        $this->log_audit_trail($account_id, $audit_action, "Provider account $action", []);

        error_log('[VD License Manager] Step 2.6 - Provider account ID ' . $account_id . ' ' . $action . ' successfully');
        return true;
    }

    /**
     * Encrypt sensitive data fields
     *
     * @param array $data Account data
     * @return array|false Encrypted data or false on failure
     */
    private function encrypt_sensitive_data($data) {
        if (!$this->encryption_manager) {
            error_log('[VD License Manager] Step 2.6 - Encryption manager not available');
            return false;
        }

        $encrypted_data = $data;
        $sensitive_fields = ['email', 'password', 'cookies', 'two_factor_secret'];

        foreach ($sensitive_fields as $field) {
            if (!empty($data[$field])) {
                try {
                    $encrypted_data[$field] = $this->encryption_manager->encrypt($data[$field]);
                    if ($encrypted_data[$field] === false) {
                        error_log('[VD License Manager] Step 2.6 - Failed to encrypt field: ' . $field);
                        return false;
                    }
                } catch (Exception $e) {
                    error_log('[VD License Manager] Step 2.6 - Encryption exception for field ' . $field . ': ' . $e->getMessage());
                    return false;
                }
            }
        }

        return $encrypted_data;
    }

    /**
     * Decrypt account sensitive data
     *
     * @param object $account Account object
     * @return object Decrypted account object
     */
    private function decrypt_account_data($account) {
        if (!$this->encryption_manager || !$account) {
            return $account;
        }

        $sensitive_fields = ['email', 'password', 'cookies', 'two_factor_secret'];

        foreach ($sensitive_fields as $field) {
            if (!empty($account->$field)) {
                try {
                    $decrypted = $this->encryption_manager->decrypt($account->$field);
                    if ($decrypted !== false) {
                        $account->$field = $decrypted;
                    }
                } catch (Exception $e) {
                    error_log('[VD License Manager] Step 2.6 - Decryption exception for field ' . $field . ': ' . $e->getMessage());
                    // Keep encrypted value if decryption fails
                }
            }
        }

        return $account;
    }

    /**
     * Check if data contains sensitive fields
     *
     * @param array $data Data to check
     * @return bool True if contains sensitive data
     */
    private function has_sensitive_data($data) {
        $sensitive_fields = ['email', 'password', 'cookies', 'two_factor_secret'];

        foreach ($sensitive_fields as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate provider account data
     *
     * @param array $data Account data
     * @param string $operation 'create' or 'update'
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_account_data($data, $operation = 'create') {
        $errors = [];

        // Required fields for creation
        if ($operation === 'create') {
            if (empty($data['product_id']) || !is_numeric($data['product_id'])) {
                $errors[] = 'Valid product ID is required';
            }

            if (empty($data['account_name'])) {
                $errors[] = 'Account name is required';
            }

            if (empty($data['email'])) {
                $errors[] = 'Email is required';
            }

            if (empty($data['password'])) {
                $errors[] = 'Password is required';
            }
        }

        // Validate email format
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors[] = 'Invalid email format';
        }

        // Validate account status
        if (!empty($data['account_status'])) {
            $allowed_statuses = ['active', 'inactive', 'suspended', 'banned'];
            if (!in_array($data['account_status'], $allowed_statuses)) {
                $errors[] = 'Invalid account status';
            }
        }

        // Validate health score
        if (isset($data['health_score'])) {
            if (!is_numeric($data['health_score']) || $data['health_score'] < 0 || $data['health_score'] > 100) {
                $errors[] = 'Health score must be between 0 and 100';
            }
        }

        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(', ', $errors));
        }

        return true;
    }

    /**
     * Prepare account data for database operations
     *
     * @param array $data Raw data
     * @param bool $include_timestamps Whether to include created_at/updated_at
     * @return array Prepared data
     */
    private function prepare_account_data($data, $include_timestamps = true) {
        $prepared = [];

        // Allowed fields mapping
        $field_map = [
            'product_id' => 'product_id',
            'account_name' => 'account_name',
            'email' => 'email',
            'password' => 'password',
            'cookies' => 'cookies',
            'two_factor_secret' => 'two_factor_secret',
            'account_status' => 'account_status',
            'health_score' => 'health_score',
            'last_health_check' => 'last_health_check',
            'total_assignments' => 'total_assignments',
            'active_assignments' => 'active_assignments'
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
        if (!isset($prepared['account_status'])) {
            $prepared['account_status'] = 'active';
        }

        if (!isset($prepared['health_score'])) {
            $prepared['health_score'] = 100.00;
        }

        if (!isset($prepared['total_assignments'])) {
            $prepared['total_assignments'] = 0;
        }

        if (!isset($prepared['active_assignments'])) {
            $prepared['active_assignments'] = 0;
        }

        // Sanitize string fields
        if (isset($prepared['account_name'])) {
            $prepared['account_name'] = sanitize_text_field($prepared['account_name']);
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
                case 'total_assignments':
                case 'active_assignments':
                    $format[] = '%d';
                    break;
                case 'health_score':
                    $format[] = '%f';
                    break;
                case 'created_at':
                case 'updated_at':
                case 'last_health_check':
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
     * Check if provider account exists
     *
     * @param int $account_id Account ID
     * @return bool True if exists, false otherwise
     */
    private function account_exists($account_id) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->full_table_name} WHERE id = %d",
                $account_id
            )
        );

        return $count > 0;
    }

    /**
     * Log audit trail for provider account operations
     *
     * @param int $account_id Account ID
     * @param string $action Action type
     * @param string $description Action description
     * @param array $metadata Additional metadata
     */
    private function log_audit_trail($account_id, $action, $description, $metadata = []) {
        global $wpdb;

        // Insert into credential audit table
        $audit_data = [
            'provider_account_id' => $account_id,
            'audit_type' => $action,
            'performed_by' => wp_get_current_user()->user_login ?? 'system',
            'action_description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'severity' => 'medium',
            'created_at' => current_time('mysql')
        ];

        $wpdb->insert(
            $wpdb->prefix . 'vd_credential_audit',
            $audit_data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get provider account statistics
     *
     * @return array Statistics array
     */
    public function get_provider_stats() {
        global $wpdb;

        $stats = [];

        // Total accounts
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->full_table_name}");

        // Status breakdown
        $status_counts = $wpdb->get_results(
            "SELECT account_status, COUNT(*) as count FROM {$this->full_table_name} GROUP BY account_status"
        );

        foreach ($status_counts as $status) {
            $stats['by_status'][$status->account_status] = $status->count;
        }

        // Average health score
        $stats['avg_health_score'] = $wpdb->get_var(
            "SELECT AVG(health_score) FROM {$this->full_table_name} WHERE account_status = 'active'"
        );

        // Accounts with low health (< 50)
        $stats['low_health'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->full_table_name} WHERE health_score < 50 AND account_status = 'active'"
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
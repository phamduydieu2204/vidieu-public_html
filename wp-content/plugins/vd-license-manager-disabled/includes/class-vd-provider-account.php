<?php
defined('ABSPATH') || exit;

class VD_Provider_Account {

    private static $instance = null;
    private $db_manager;
    private $encryption_manager;

    private function __construct() {
        $this->db_manager = VD_Database_Manager::get_instance();
        $this->encryption_manager = VD_Encryption_Manager::get_instance();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create_provider_account($account_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_provider_accounts';

        $defaults = array(
            'product_id' => 0,
            'account_name' => '',
            'email' => '',
            'password' => '',
            'cookies' => '',
            'two_factor_secret' => '',
            'account_status' => 'active',
            'health_score' => 100.0,
            'last_health_check' => current_time('mysql', true),
            'total_assignments' => 0,
            'active_assignments' => 0,
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true)
        );

        $account_data = wp_parse_args($account_data, $defaults);

        $account_data['product_id'] = absint($account_data['product_id']);
        $account_data['account_name'] = sanitize_text_field($account_data['account_name']);
        $account_data['email'] = sanitize_email($account_data['email']);
        $account_data['account_status'] = sanitize_text_field($account_data['account_status']);
        $account_data['health_score'] = floatval($account_data['health_score']);
        $account_data['total_assignments'] = absint($account_data['total_assignments']);
        $account_data['active_assignments'] = absint($account_data['active_assignments']);

        if (empty($account_data['account_name'])) {
            return new WP_Error('empty_account_name', 'Account name is required');
        }

        if (empty($account_data['email'])) {
            return new WP_Error('empty_email', 'Email is required');
        }

        if ($this->account_exists($account_data['product_id'], $account_data['email'])) {
            return new WP_Error('duplicate_account', 'Account with this email already exists for this product');
        }

        $encrypted_credentials = $this->encryption_manager->encrypt_provider_credentials(array(
            'email' => $account_data['email'],
            'password' => $account_data['password'],
            'cookies' => $account_data['cookies'],
            'two_factor_secret' => $account_data['two_factor_secret']
        ));

        $account_data['email'] = $encrypted_credentials['email'];
        $account_data['password'] = $encrypted_credentials['password'];
        $account_data['cookies'] = $encrypted_credentials['cookies'];
        $account_data['two_factor_secret'] = $encrypted_credentials['two_factor_secret'];

        $result = $wpdb->insert(
            $table_name,
            $account_data,
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s',
                '%f', '%s', '%d', '%d', '%s', '%s'
            )
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', 'Failed to create provider account: ' . $wpdb->last_error);
        }

        $account_id = $wpdb->insert_id;

        $this->log_credential_access('account_created', $account_id, array(
            'account_name' => $account_data['account_name'],
            'product_id' => $account_data['product_id']
        ));

        return $account_id;
    }

    public function get_provider_account($account_id, $decrypt_credentials = false) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_provider_accounts';
        $account_id = absint($account_id);

        if ($account_id <= 0) {
            return false;
        }

        $account = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $account_id
            ),
            ARRAY_A
        );

        if (!$account) {
            return false;
        }

        if ($decrypt_credentials) {
            $decrypted_credentials = $this->encryption_manager->decrypt_provider_credentials(array(
                'email' => $account['email'],
                'password' => $account['password'],
                'cookies' => $account['cookies'],
                'two_factor_secret' => $account['two_factor_secret']
            ));

            $account['email'] = $decrypted_credentials['email'];
            $account['password'] = $decrypted_credentials['password'];
            $account['cookies'] = $decrypted_credentials['cookies'];
            $account['two_factor_secret'] = $decrypted_credentials['two_factor_secret'];

            $this->log_credential_access('credentials_accessed', $account_id, array(
                'account_name' => $account['account_name'],
                'accessed_fields' => array('email', 'password', 'cookies', 'two_factor_secret')
            ));
        }

        return $account;
    }

    public function update_provider_account($account_id, $update_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_provider_accounts';
        $account_id = absint($account_id);

        if ($account_id <= 0) {
            return new WP_Error('invalid_account_id', 'Invalid account ID');
        }

        $existing_account = $this->get_provider_account($account_id);
        if (!$existing_account) {
            return new WP_Error('account_not_found', 'Provider account not found');
        }

        $allowed_fields = array(
            'account_name', 'email', 'password', 'cookies', 'two_factor_secret',
            'account_status', 'health_score', 'total_assignments', 'active_assignments'
        );

        $sanitized_data = array();
        $format = array();
        $credential_fields = array('email', 'password', 'cookies', 'two_factor_secret');
        $updated_credentials = array();

        foreach ($update_data as $field => $value) {
            if (!in_array($field, $allowed_fields)) {
                continue;
            }

            if (in_array($field, $credential_fields)) {
                $updated_credentials[$field] = $value;
            } else {
                switch ($field) {
                    case 'account_name':
                    case 'account_status':
                        $sanitized_data[$field] = sanitize_text_field($value);
                        $format[] = '%s';
                        break;
                    case 'health_score':
                        $sanitized_data[$field] = floatval($value);
                        $format[] = '%f';
                        break;
                    case 'total_assignments':
                    case 'active_assignments':
                        $sanitized_data[$field] = absint($value);
                        $format[] = '%d';
                        break;
                }
            }
        }

        if (!empty($updated_credentials)) {
            $existing_credentials = $this->encryption_manager->decrypt_provider_credentials(array(
                'email' => $existing_account['email'],
                'password' => $existing_account['password'],
                'cookies' => $existing_account['cookies'],
                'two_factor_secret' => $existing_account['two_factor_secret']
            ));

            $new_credentials = wp_parse_args($updated_credentials, $existing_credentials);
            $encrypted_credentials = $this->encryption_manager->encrypt_provider_credentials($new_credentials);

            foreach ($credential_fields as $field) {
                if (isset($encrypted_credentials[$field])) {
                    $sanitized_data[$field] = $encrypted_credentials[$field];
                    $format[] = '%s';
                }
            }
        }

        $sanitized_data['updated_at'] = current_time('mysql', true);
        $format[] = '%s';

        if (empty($sanitized_data)) {
            return new WP_Error('no_update_data', 'No valid update data provided');
        }

        $result = $wpdb->update(
            $table_name,
            $sanitized_data,
            array('id' => $account_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', 'Failed to update provider account: ' . $wpdb->last_error);
        }

        $this->log_credential_access('account_updated', $account_id, array(
            'updated_fields' => array_keys($sanitized_data),
            'credential_fields_updated' => array_keys($updated_credentials)
        ));

        return true;
    }

    public function delete_provider_account($account_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_provider_accounts';
        $account_id = absint($account_id);

        if ($account_id <= 0) {
            return new WP_Error('invalid_account_id', 'Invalid account ID');
        }

        $existing_account = $this->get_provider_account($account_id);
        if (!$existing_account) {
            return new WP_Error('account_not_found', 'Provider account not found');
        }

        $wpdb->query('START TRANSACTION');

        try {
            $assignments_table = $wpdb->prefix . 'bz_license_assignments';
            $active_assignments = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $assignments_table WHERE provider_account_id = %d AND status = 'active'",
                    $account_id
                )
            );

            if ($active_assignments > 0) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('active_assignments', 'Cannot delete provider account with active assignments');
            }

            $wpdb->update(
                $assignments_table,
                array('status' => 'terminated'),
                array('provider_account_id' => $account_id),
                array('%s'),
                array('%d')
            );

            $result = $wpdb->delete(
                $table_name,
                array('id' => $account_id),
                array('%d')
            );

            if ($result === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('db_delete_error', 'Failed to delete provider account: ' . $wpdb->last_error);
            }

            $wpdb->query('COMMIT');

            $this->log_credential_access('account_deleted', $account_id, array(
                'account_name' => $existing_account['account_name']
            ));

            return true;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('transaction_error', 'Failed to delete provider account: ' . $e->getMessage());
        }
    }

    public function get_provider_accounts($args = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_provider_accounts';

        $defaults = array(
            'product_id' => 0,
            'account_status' => '',
            'decrypt_credentials' => false,
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1');
        $where_values = array();

        if (!empty($args['product_id'])) {
            $where_clauses[] = 'product_id = %d';
            $where_values[] = absint($args['product_id']);
        }

        if (!empty($args['account_status'])) {
            $where_clauses[] = 'account_status = %s';
            $where_values[] = sanitize_text_field($args['account_status']);
        }

        $where_sql = implode(' AND ', $where_clauses);

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'created_at DESC';
        }

        $limit = absint($args['limit']);
        $offset = absint($args['offset']);

        $sql = "SELECT * FROM $table_name WHERE $where_sql ORDER BY $orderby LIMIT $limit OFFSET $offset";

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        $accounts = $wpdb->get_results($sql, ARRAY_A);

        if ($accounts && $args['decrypt_credentials']) {
            foreach ($accounts as &$account) {
                $decrypted_credentials = $this->encryption_manager->decrypt_provider_credentials(array(
                    'email' => $account['email'],
                    'password' => $account['password'],
                    'cookies' => $account['cookies'],
                    'two_factor_secret' => $account['two_factor_secret']
                ));

                $account['email'] = $decrypted_credentials['email'];
                $account['password'] = $decrypted_credentials['password'];
                $account['cookies'] = $decrypted_credentials['cookies'];
                $account['two_factor_secret'] = $decrypted_credentials['two_factor_secret'];
            }

            $this->log_credential_access('bulk_credentials_accessed', null, array(
                'account_count' => count($accounts),
                'product_id' => $args['product_id']
            ));
        }

        return $accounts ? $accounts : array();
    }

    public function account_exists($product_id, $email) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_provider_accounts';
        $product_id = absint($product_id);
        $email = sanitize_email($email);

        $encrypted_email = $this->encryption_manager->encrypt($email);

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE product_id = %d AND email = %s",
                $product_id,
                $encrypted_email
            )
        );

        return $count > 0;
    }

    public function update_health_score($account_id, $health_score) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_provider_accounts';
        $account_id = absint($account_id);
        $health_score = floatval($health_score);

        $result = $wpdb->update(
            $table_name,
            array(
                'health_score' => $health_score,
                'last_health_check' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true)
            ),
            array('id' => $account_id),
            array('%f', '%s', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    public function update_assignment_counts($account_id) {
        global $wpdb;

        $assignments_table = $wpdb->prefix . 'bz_license_assignments';
        $accounts_table = $wpdb->prefix . 'bz_provider_accounts';

        $account_id = absint($account_id);

        $total_assignments = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $assignments_table WHERE provider_account_id = %d",
                $account_id
            )
        );

        $active_assignments = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $assignments_table WHERE provider_account_id = %d AND status = 'active'",
                $account_id
            )
        );

        $result = $wpdb->update(
            $accounts_table,
            array(
                'total_assignments' => $total_assignments,
                'active_assignments' => $active_assignments,
                'updated_at' => current_time('mysql', true)
            ),
            array('id' => $account_id),
            array('%d', '%d', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    private function log_credential_access($action, $account_id, $data = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_credential_audit';

        $log_data = array(
            'provider_account_id' => $account_id,
            'admin_user_id' => get_current_user_id(),
            'action_type' => sanitize_text_field($action),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'accessed_fields' => wp_json_encode(isset($data['accessed_fields']) ? $data['accessed_fields'] : array()),
            'action_details' => wp_json_encode($data),
            'timestamp' => current_time('mysql', true)
        );

        $wpdb->insert(
            $table_name,
            $log_data,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
}
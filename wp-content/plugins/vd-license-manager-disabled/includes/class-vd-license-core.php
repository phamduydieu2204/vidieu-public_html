<?php
defined('ABSPATH') || exit;

class VD_License_Core {

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

    public function create_license($license_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_licenses';

        $defaults = array(
            'license_key' => '',
            'product_id' => 0,
            'owner_name' => '',
            'owner_email' => '',
            'status' => 'active',
            'device_limit' => 3,
            'expires_at' => null,
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true)
        );

        $license_data = wp_parse_args($license_data, $defaults);

        $license_data['license_key'] = sanitize_text_field($license_data['license_key']);
        $license_data['product_id'] = absint($license_data['product_id']);
        $license_data['owner_name'] = sanitize_text_field($license_data['owner_name']);
        $license_data['owner_email'] = sanitize_email($license_data['owner_email']);
        $license_data['status'] = sanitize_text_field($license_data['status']);
        $license_data['device_limit'] = absint($license_data['device_limit']);

        if (empty($license_data['license_key'])) {
            return new WP_Error('empty_license_key', 'License key is required');
        }

        if ($this->license_key_exists($license_data['license_key'])) {
            return new WP_Error('duplicate_license_key', 'License key already exists');
        }

        $result = $wpdb->insert(
            $table_name,
            $license_data,
            array(
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s'
            )
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', 'Failed to create license: ' . $wpdb->last_error);
        }

        $license_id = $wpdb->insert_id;

        $this->log_action('license_created', array(
            'license_id' => $license_id,
            'license_key' => $license_data['license_key'],
            'product_id' => $license_data['product_id']
        ));

        return $license_id;
    }

    public function get_license($license_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_licenses';
        $license_id = absint($license_id);

        if ($license_id <= 0) {
            return false;
        }

        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $license_id
            ),
            ARRAY_A
        );

        return $license;
    }

    public function get_license_by_key($license_key) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_licenses';
        $license_key = sanitize_text_field($license_key);

        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE license_key = %s",
                $license_key
            ),
            ARRAY_A
        );

        return $license;
    }

    public function update_license($license_id, $update_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_licenses';
        $license_id = absint($license_id);

        if ($license_id <= 0) {
            return new WP_Error('invalid_license_id', 'Invalid license ID');
        }

        $existing_license = $this->get_license($license_id);
        if (!$existing_license) {
            return new WP_Error('license_not_found', 'License not found');
        }

        $allowed_fields = array(
            'product_id', 'owner_name', 'owner_email', 'status',
            'device_limit', 'expires_at', 'updated_at'
        );

        $sanitized_data = array();
        $format = array();

        foreach ($update_data as $field => $value) {
            if (!in_array($field, $allowed_fields)) {
                continue;
            }

            switch ($field) {
                case 'product_id':
                case 'device_limit':
                    $sanitized_data[$field] = absint($value);
                    $format[] = '%d';
                    break;
                case 'owner_name':
                case 'status':
                    $sanitized_data[$field] = sanitize_text_field($value);
                    $format[] = '%s';
                    break;
                case 'owner_email':
                    $sanitized_data[$field] = sanitize_email($value);
                    $format[] = '%s';
                    break;
                case 'expires_at':
                    $sanitized_data[$field] = $value;
                    $format[] = '%s';
                    break;
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
            array('id' => $license_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', 'Failed to update license: ' . $wpdb->last_error);
        }

        $this->log_action('license_updated', array(
            'license_id' => $license_id,
            'updated_fields' => array_keys($sanitized_data),
            'license_key' => $existing_license['license_key']
        ));

        return true;
    }

    public function delete_license($license_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_licenses';
        $license_id = absint($license_id);

        if ($license_id <= 0) {
            return new WP_Error('invalid_license_id', 'Invalid license ID');
        }

        $existing_license = $this->get_license($license_id);
        if (!$existing_license) {
            return new WP_Error('license_not_found', 'License not found');
        }

        $wpdb->query('START TRANSACTION');

        try {
            $assignments_table = $wpdb->prefix . 'bz_license_assignments';
            $wpdb->delete(
                $assignments_table,
                array('license_id' => $license_id),
                array('%d')
            );

            $devices_table = $wpdb->prefix . 'bz_device_requests';
            $wpdb->delete(
                $devices_table,
                array('license_id' => $license_id),
                array('%d')
            );

            $result = $wpdb->delete(
                $table_name,
                array('id' => $license_id),
                array('%d')
            );

            if ($result === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('db_delete_error', 'Failed to delete license: ' . $wpdb->last_error);
            }

            $wpdb->query('COMMIT');

            $this->log_action('license_deleted', array(
                'license_id' => $license_id,
                'license_key' => $existing_license['license_key']
            ));

            return true;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('transaction_error', 'Failed to delete license: ' . $e->getMessage());
        }
    }

    public function license_key_exists($license_key) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_licenses';
        $license_key = sanitize_text_field($license_key);

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE license_key = %s",
                $license_key
            )
        );

        return $count > 0;
    }

    public function get_licenses($args = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_licenses';

        $defaults = array(
            'status' => '',
            'product_id' => 0,
            'owner_email' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1');
        $where_values = array();

        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = sanitize_text_field($args['status']);
        }

        if (!empty($args['product_id'])) {
            $where_clauses[] = 'product_id = %d';
            $where_values[] = absint($args['product_id']);
        }

        if (!empty($args['owner_email'])) {
            $where_clauses[] = 'owner_email = %s';
            $where_values[] = sanitize_email($args['owner_email']);
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

        $licenses = $wpdb->get_results($sql, ARRAY_A);

        return $licenses ? $licenses : array();
    }

    public function count_licenses($args = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_licenses';

        $where_clauses = array('1=1');
        $where_values = array();

        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = sanitize_text_field($args['status']);
        }

        if (!empty($args['product_id'])) {
            $where_clauses[] = 'product_id = %d';
            $where_values[] = absint($args['product_id']);
        }

        if (!empty($args['owner_email'])) {
            $where_clauses[] = 'owner_email = %s';
            $where_values[] = sanitize_email($args['owner_email']);
        }

        $where_sql = implode(' AND ', $where_clauses);
        $sql = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        return $wpdb->get_var($sql);
    }

    private function log_action($action, $data = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_access_logs';

        $log_data = array(
            'timestamp' => current_time('mysql', true),
            'license_id' => isset($data['license_id']) ? $data['license_id'] : null,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'action_type' => sanitize_text_field($action),
            'action_data' => wp_json_encode($data),
            'source' => 'admin'
        );

        $wpdb->insert(
            $table_name,
            $log_data,
            array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
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
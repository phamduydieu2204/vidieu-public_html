<?php
defined('ABSPATH') || exit;

class VD_Device_Manager {

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

    public function register_device($license_id, $device_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';
        $license_id = absint($license_id);

        if ($license_id <= 0) {
            return new WP_Error('invalid_license_id', 'Invalid license ID');
        }

        $license_core = VD_License_Core::get_instance();
        $license = $license_core->get_license($license_id);

        if (!$license) {
            return new WP_Error('license_not_found', 'License not found');
        }

        if ($license['status'] !== 'active') {
            return new WP_Error('license_inactive', 'License is not active');
        }

        $defaults = array(
            'device_name' => '',
            'device_fingerprint' => '',
            'ip_address' => '',
            'user_agent' => '',
            'os_info' => '',
            'browser_info' => ''
        );

        $device_data = wp_parse_args($device_data, $defaults);

        $device_data['device_name'] = sanitize_text_field($device_data['device_name']);
        $device_data['device_fingerprint'] = sanitize_text_field($device_data['device_fingerprint']);
        $device_data['ip_address'] = sanitize_text_field($device_data['ip_address']);
        $device_data['user_agent'] = sanitize_text_field($device_data['user_agent']);
        $device_data['os_info'] = sanitize_text_field($device_data['os_info']);
        $device_data['browser_info'] = sanitize_text_field($device_data['browser_info']);

        if (empty($device_data['device_fingerprint'])) {
            return new WP_Error('empty_fingerprint', 'Device fingerprint is required');
        }

        $hashed_fingerprint = $this->encryption_manager->hash_device_fingerprint($device_data['device_fingerprint']);

        if ($this->device_exists($license_id, $hashed_fingerprint)) {
            return new WP_Error('device_exists', 'Device already registered for this license');
        }

        $current_device_count = $this->count_approved_devices($license_id);
        if ($current_device_count >= $license['device_limit']) {
            return new WP_Error('device_limit_exceeded', 'Device limit exceeded for this license');
        }

        $risk_score = $this->calculate_risk_score($device_data, $license_id);
        $auto_approval_threshold = floatval(get_option('vd_license_manager_auto_approval_threshold', 25.0));

        $approval_status = ($risk_score <= $auto_approval_threshold) ? 'approved' : 'pending';

        $insert_data = array(
            'license_id' => $license_id,
            'device_name' => $device_data['device_name'],
            'device_fingerprint' => $hashed_fingerprint,
            'ip_address' => $device_data['ip_address'],
            'user_agent' => $device_data['user_agent'],
            'os_info' => $device_data['os_info'],
            'browser_info' => $device_data['browser_info'],
            'risk_score' => $risk_score,
            'approval_status' => $approval_status,
            'requested_at' => current_time('mysql', true),
            'approved_at' => ($approval_status === 'approved') ? current_time('mysql', true) : null,
            'approved_by' => ($approval_status === 'approved') ? 0 : null
        );

        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s',
                '%f', '%s', '%s', '%s', '%d'
            )
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', 'Failed to register device: ' . $wpdb->last_error);
        }

        $device_id = $wpdb->insert_id;

        $this->log_device_action('device_registered', $device_id, array(
            'license_id' => $license_id,
            'device_name' => $device_data['device_name'],
            'risk_score' => $risk_score,
            'auto_approved' => ($approval_status === 'approved')
        ));

        return array(
            'device_id' => $device_id,
            'approval_status' => $approval_status,
            'risk_score' => $risk_score,
            'auto_approved' => ($approval_status === 'approved')
        );
    }

    public function approve_device($device_id, $admin_user_id = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';
        $device_id = absint($device_id);

        if ($device_id <= 0) {
            return new WP_Error('invalid_device_id', 'Invalid device ID');
        }

        $device = $this->get_device($device_id);
        if (!$device) {
            return new WP_Error('device_not_found', 'Device not found');
        }

        if ($device['approval_status'] === 'approved') {
            return new WP_Error('already_approved', 'Device is already approved');
        }

        if ($device['approval_status'] === 'rejected') {
            return new WP_Error('device_rejected', 'Cannot approve a rejected device');
        }

        $license_core = VD_License_Core::get_instance();
        $license = $license_core->get_license($device['license_id']);

        if (!$license) {
            return new WP_Error('license_not_found', 'Associated license not found');
        }

        $current_device_count = $this->count_approved_devices($device['license_id']);
        if ($current_device_count >= $license['device_limit']) {
            return new WP_Error('device_limit_exceeded', 'Device limit exceeded for this license');
        }

        $admin_user_id = $admin_user_id ?: get_current_user_id();

        $result = $wpdb->update(
            $table_name,
            array(
                'approval_status' => 'approved',
                'approved_at' => current_time('mysql', true),
                'approved_by' => $admin_user_id
            ),
            array('id' => $device_id),
            array('%s', '%s', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', 'Failed to approve device: ' . $wpdb->last_error);
        }

        $this->log_device_action('device_approved', $device_id, array(
            'license_id' => $device['license_id'],
            'device_name' => $device['device_name'],
            'admin_user_id' => $admin_user_id
        ));

        return true;
    }

    public function reject_device($device_id, $reason = '', $admin_user_id = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';
        $device_id = absint($device_id);

        if ($device_id <= 0) {
            return new WP_Error('invalid_device_id', 'Invalid device ID');
        }

        $device = $this->get_device($device_id);
        if (!$device) {
            return new WP_Error('device_not_found', 'Device not found');
        }

        if ($device['approval_status'] === 'rejected') {
            return new WP_Error('already_rejected', 'Device is already rejected');
        }

        $admin_user_id = $admin_user_id ?: get_current_user_id();

        $result = $wpdb->update(
            $table_name,
            array(
                'approval_status' => 'rejected',
                'rejection_reason' => sanitize_text_field($reason),
                'approved_at' => current_time('mysql', true),
                'approved_by' => $admin_user_id
            ),
            array('id' => $device_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', 'Failed to reject device: ' . $wpdb->last_error);
        }

        $this->log_device_action('device_rejected', $device_id, array(
            'license_id' => $device['license_id'],
            'device_name' => $device['device_name'],
            'rejection_reason' => $reason,
            'admin_user_id' => $admin_user_id
        ));

        return true;
    }

    public function revoke_device($device_id, $reason = '', $admin_user_id = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';
        $device_id = absint($device_id);

        if ($device_id <= 0) {
            return new WP_Error('invalid_device_id', 'Invalid device ID');
        }

        $device = $this->get_device($device_id);
        if (!$device) {
            return new WP_Error('device_not_found', 'Device not found');
        }

        if ($device['approval_status'] !== 'approved') {
            return new WP_Error('device_not_approved', 'Can only revoke approved devices');
        }

        $admin_user_id = $admin_user_id ?: get_current_user_id();

        $result = $wpdb->update(
            $table_name,
            array(
                'approval_status' => 'revoked',
                'rejection_reason' => sanitize_text_field($reason),
                'revoked_at' => current_time('mysql', true),
                'revoked_by' => $admin_user_id
            ),
            array('id' => $device_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', 'Failed to revoke device: ' . $wpdb->last_error);
        }

        $this->log_device_action('device_revoked', $device_id, array(
            'license_id' => $device['license_id'],
            'device_name' => $device['device_name'],
            'revocation_reason' => $reason,
            'admin_user_id' => $admin_user_id
        ));

        return true;
    }

    public function get_device($device_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';
        $device_id = absint($device_id);

        if ($device_id <= 0) {
            return false;
        }

        $device = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $device_id
            ),
            ARRAY_A
        );

        return $device;
    }

    public function get_devices($args = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';

        $defaults = array(
            'license_id' => 0,
            'approval_status' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'requested_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1');
        $where_values = array();

        if (!empty($args['license_id'])) {
            $where_clauses[] = 'license_id = %d';
            $where_values[] = absint($args['license_id']);
        }

        if (!empty($args['approval_status'])) {
            $where_clauses[] = 'approval_status = %s';
            $where_values[] = sanitize_text_field($args['approval_status']);
        }

        $where_sql = implode(' AND ', $where_clauses);

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'requested_at DESC';
        }

        $limit = absint($args['limit']);
        $offset = absint($args['offset']);

        $sql = "SELECT * FROM $table_name WHERE $where_sql ORDER BY $orderby LIMIT $limit OFFSET $offset";

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        $devices = $wpdb->get_results($sql, ARRAY_A);

        return $devices ? $devices : array();
    }

    public function device_exists($license_id, $device_fingerprint) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';
        $license_id = absint($license_id);

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE license_id = %d AND device_fingerprint = %s AND approval_status IN ('approved', 'pending')",
                $license_id,
                $device_fingerprint
            )
        );

        return $count > 0;
    }

    public function count_approved_devices($license_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';
        $license_id = absint($license_id);

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE license_id = %d AND approval_status = 'approved'",
                $license_id
            )
        );

        return intval($count);
    }

    public function is_device_approved($license_id, $device_fingerprint) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';
        $license_id = absint($license_id);
        $hashed_fingerprint = $this->encryption_manager->hash_device_fingerprint($device_fingerprint);

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE license_id = %d AND device_fingerprint = %s AND approval_status = 'approved'",
                $license_id,
                $hashed_fingerprint
            )
        );

        return $count > 0;
    }

    private function calculate_risk_score($device_data, $license_id) {
        $risk_score = 0.0;

        $same_ip_devices = $this->count_devices_by_ip($device_data['ip_address']);
        if ($same_ip_devices > 5) {
            $risk_score += 30.0;
        } elseif ($same_ip_devices > 2) {
            $risk_score += 15.0;
        }

        $same_ua_devices = $this->count_devices_by_user_agent($device_data['user_agent']);
        if ($same_ua_devices > 10) {
            $risk_score += 20.0;
        } elseif ($same_ua_devices > 5) {
            $risk_score += 10.0;
        }

        if (empty($device_data['device_name']) || strlen($device_data['device_name']) < 3) {
            $risk_score += 15.0;
        }

        if (empty($device_data['os_info']) || empty($device_data['browser_info'])) {
            $risk_score += 10.0;
        }

        $vpn_indicators = array('vpn', 'proxy', 'tor', 'anonymous');
        foreach ($vpn_indicators as $indicator) {
            if (stripos($device_data['user_agent'], $indicator) !== false) {
                $risk_score += 25.0;
                break;
            }
        }

        $license_devices = $this->count_approved_devices($license_id);
        if ($license_devices === 0) {
            $risk_score -= 5.0;
        }

        return max(0.0, min(100.0, $risk_score));
    }

    private function count_devices_by_ip($ip_address) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT license_id) FROM $table_name WHERE ip_address = %s AND approval_status = 'approved'",
                $ip_address
            )
        );

        return intval($count);
    }

    private function count_devices_by_user_agent($user_agent) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_device_requests';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT license_id) FROM $table_name WHERE user_agent = %s AND approval_status = 'approved'",
                $user_agent
            )
        );

        return intval($count);
    }

    private function log_device_action($action, $device_id, $data = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bz_access_logs';

        $log_data = array(
            'timestamp' => current_time('mysql', true),
            'license_id' => isset($data['license_id']) ? $data['license_id'] : null,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'action_type' => sanitize_text_field($action),
            'action_data' => wp_json_encode(array_merge($data, array('device_id' => $device_id))),
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
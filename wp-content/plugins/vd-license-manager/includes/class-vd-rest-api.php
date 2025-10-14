<?php
/**
 * VD License Manager REST API Handler
 *
 * Provides REST API endpoints for customer license access.
 * Implements comprehensive license validation, device tracking,
 * pool assignment, and credential management.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes
 * @since      1.0.0
 * @author     Vidieu Team <admin@vidieu.vn>
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VD_REST_API {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('vd/v1', '/license/access', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_license_access'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        // Validate format: XXXX-XXXX-XXXX-XXXX
                        return preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $param);
                    }
                ),
                'device_fingerprint' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'device_token' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'device_combined_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'device_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'user_agent' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }

    /**
     * Handle license access request
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_license_access($request) {
        global $wpdb;

        // Start timing for performance logging
        $start_time = microtime(true);

        // Extract parameters
        $license_key = $request->get_param('license_key');
        $device_fingerprint = $request->get_param('device_fingerprint') ?: 'unknown';
        $device_token = $request->get_param('device_token') ?: 'unknown';
        $device_combined_id = $request->get_param('device_combined_id') ?: 'unknown';
        $device_name = $request->get_param('device_name') ?: 'Unknown Device';
        $user_agent = $request->get_param('user_agent') ?: $_SERVER['HTTP_USER_AGENT'];

        // Get client IP
        $client_ip = $this->get_client_ip();

        // Log access attempt (before validation)
        $log_id = $this->log_access_attempt($license_key, $device_combined_id, $client_ip, 'initiated');

        try {
            // Step 1: Validate License Key
            $license = $this->validate_license($license_key);

            if (is_wp_error($license)) {
                $this->update_access_log($log_id, 'failed', $license->get_error_code());
                return $license;
            }

            // Step 2: Get Product Config
            $config = $this->get_product_config($license['product_id']);

            if (is_wp_error($config)) {
                $this->update_access_log($log_id, 'failed', 'config_not_found');
                return $config;
            }

            // Step 3: Check Daily Request Limit
            $rate_limit_check = $this->check_rate_limit($license['id'], $config['max_requests_per_day']);

            if (is_wp_error($rate_limit_check)) {
                $this->update_access_log($log_id, 'blocked', 'rate_limit_exceeded');
                return $rate_limit_check;
            }

            // Step 4: Check VPS (if enabled)
            if ($config['block_vps'] == 1) {
                $vps_check = $this->check_vps($client_ip);

                if (is_wp_error($vps_check)) {
                    $this->update_access_log($log_id, 'blocked', 'vps_detected');
                    return $vps_check;
                }
            }

            // Step 5: Handle Device Tracking
            $device_check = $this->handle_device_tracking(
                $license['id'],
                $device_combined_id,
                $device_fingerprint,
                $device_token,
                $device_name,
                $user_agent,
                $client_ip,
                $config['max_devices_per_license']
            );

            if (is_wp_error($device_check)) {
                $this->update_access_log($log_id, 'blocked', $device_check->get_error_code());
                return $device_check;
            }

            // Step 6: Assign Pool/Account (if first time)
            if (empty($license['assigned_pool_id'])) {
                $assignment = $this->assign_pool_to_license($license['id'], $license['product_id']);

                if (is_wp_error($assignment)) {
                    $this->update_access_log($log_id, 'failed', 'pool_assignment_failed');
                    return $assignment;
                }

                // Reload license data after assignment
                $license = $this->get_license_by_id($license['id']);
            }

            // Step 7: Get Provider Account Credentials
            $account = $this->get_provider_account($license['assigned_account_id']);

            if (is_wp_error($account)) {
                $this->update_access_log($log_id, 'failed', 'account_not_found');
                return $account;
            }

            // Step 8: Build Response with Dynamic Fields
            $credentials = $this->build_credentials_response($account, $config['response_fields']);

            // Step 9: Get Device List
            $devices = $this->get_license_devices($license['id']);

            // Step 10: Update Access Log (Success)
            $this->update_access_log($log_id, 'success', null);

            // Calculate response time
            $response_time = round((microtime(true) - $start_time) * 1000, 2);

            // Build successful response
            return new WP_REST_Response(array(
                'success' => true,
                'license' => array(
                    'key' => $license['license_key'],
                    'status' => $license['status'],
                    'expires_at' => $license['expires_at'],
                    'product_name' => get_the_title($license['product_id']),
                    'days_remaining' => $this->calculate_days_remaining($license['expires_at'])
                ),
                'credentials' => $credentials,
                'devices' => $devices,
                'usage' => array(
                    'devices_used' => count($devices),
                    'devices_allowed' => $config['max_devices_per_license'],
                    'requests_today' => $this->count_today_requests($license['id']),
                    'requests_allowed' => $config['max_requests_per_day'],
                    'reset_at' => $this->get_next_reset_time()
                ),
                'meta' => array(
                    'response_time_ms' => $response_time,
                    'server_time' => current_time('mysql')
                )
            ), 200);

        } catch (Exception $e) {
            error_log('VD REST API Error: ' . $e->getMessage());
            $this->update_access_log($log_id, 'error', 'exception');

            return new WP_Error(
                'server_error',
                'Đã xảy ra lỗi máy chủ. Vui lòng thử lại sau.',
                array('status' => 500)
            );
        }
    }

    /**
     * Validate license key
     *
     * @param string $license_key
     * @return array|WP_Error
     */
    private function validate_license($license_key) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_license_keys';

        // Normalize license key (remove hyphens, uppercase)
        $normalized_key = str_replace('-', '', strtoupper(trim($license_key)));

        error_log('VD REST API: Looking up license key: ' . $license_key);
        error_log('VD REST API: Normalized key: ' . $normalized_key);

        // Try to find by plain text license key (fast lookup)
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE REPLACE(UPPER(license_key_plain), '-', '') = %s",
            $normalized_key
        ), ARRAY_A);

        if (!$license) {
            return new WP_Error(
                'invalid_license',
                'Mã license không tồn tại trong hệ thống.',
                array('status' => 404)
            );
        }

        // Check if active
        if ($license['status'] !== 'active') {
            return new WP_Error(
                'license_inactive',
                'Mã license không còn hoạt động.',
                array('status' => 403)
            );
        }

        // Check expiration
        if (!empty($license['expires_at'])) {
            $expires_timestamp = strtotime($license['expires_at']);
            if ($expires_timestamp < time()) {
                return new WP_Error(
                    'license_expired',
                    'Mã license đã hết hạn vào ' . date('d/m/Y', $expires_timestamp),
                    array('status' => 403)
                );
            }
        }

        return $license;
    }

    /**
     * Get product configuration
     *
     * @param int $product_id
     * @return array|WP_Error
     */
    private function get_product_config($product_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_product_share_configs';

        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE product_id = %d",
            $product_id
        ), ARRAY_A);

        if (!$config) {
            return new WP_Error(
                'config_not_found',
                'Không tìm thấy cấu hình cho sản phẩm này.',
                array('status' => 500)
            );
        }

        // Decode JSON fields
        $config['response_fields'] = !empty($config['response_fields'])
            ? json_decode($config['response_fields'], true)
            : array();

        return $config;
    }

    /**
     * Check rate limit
     *
     * @param int $license_id
     * @param int $max_requests_per_day
     * @return bool|WP_Error
     */
    private function check_rate_limit($license_id, $max_requests_per_day) {
        $today_count = $this->count_today_requests($license_id);

        if ($today_count >= $max_requests_per_day) {
            $reset_time = $this->get_next_reset_time();

            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    'Bạn đã vượt quá giới hạn %d yêu cầu mỗi ngày. Đặt lại vào lúc %s',
                    $max_requests_per_day,
                    $reset_time
                ),
                array('status' => 429)
            );
        }

        return true;
    }

    /**
     * Count today's requests
     *
     * @param int $license_id
     * @return int
     */
    private function count_today_requests($license_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_license_access_log';
        $today_start = date('Y-m-d 00:00:00');

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name
             WHERE license_id = %d
             AND authentication_result = 'success'
             AND accessed_at >= %s",
            $license_id,
            $today_start
        ));
    }

    /**
     * Get next reset time
     *
     * @return string
     */
    private function get_next_reset_time() {
        $tomorrow = strtotime('tomorrow midnight');
        return date('H:i:s d/m/Y', $tomorrow);
    }

    /**
     * Check VPS
     *
     * @param string $ip
     * @return bool|WP_Error
     */
    private function check_vps($ip) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_datacenter_ip_ranges';

        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));

        if (!$table_exists) {
            // Table doesn't exist, skip VPS check
            return true;
        }

        $ip_long = ip2long($ip);

        if (!$ip_long) {
            // Invalid IP, allow access
            return true;
        }

        $is_vps = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name
             WHERE %d BETWEEN ip_start_long AND ip_end_long
             LIMIT 1",
            $ip_long
        ));

        if ($is_vps) {
            return new WP_Error(
                'vps_blocked',
                sprintf(
                    'Truy cập từ VPS/Datacenter bị chặn. IP: %s, Nhà cung cấp: %s',
                    $ip,
                    $is_vps->provider_name ?? 'Unknown'
                ),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Handle device tracking
     *
     * @param int $license_id
     * @param string $device_combined_id
     * @param string $device_fingerprint
     * @param string $device_token
     * @param string $device_name
     * @param string $user_agent
     * @param string $client_ip
     * @param int $max_devices
     * @return bool|WP_Error
     */
    private function handle_device_tracking($license_id, $device_combined_id, $device_fingerprint,
                                           $device_token, $device_name, $user_agent,
                                           $client_ip, $max_devices) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_license_devices';

        // Check if device exists
        $existing_device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name
             WHERE license_id = %d AND device_combined_id = %s",
            $license_id,
            $device_combined_id
        ), ARRAY_A);

        if ($existing_device) {
            // Update existing device
            $wpdb->update(
                $table_name,
                array(
                    'last_access_at' => current_time('mysql'),
                    'last_ip' => $client_ip,
                    'access_count' => $existing_device['access_count'] + 1
                ),
                array('id' => $existing_device['id']),
                array('%s', '%s', '%d'),
                array('%d')
            );

            return true;
        }

        // Check device limit
        $device_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE license_id = %d",
            $license_id
        ));

        if ($device_count >= $max_devices) {
            return new WP_Error(
                'device_limit_reached',
                sprintf(
                    'Đã đạt giới hạn %d thiết bị. Vui lòng xóa thiết bị cũ để thêm mới.',
                    $max_devices
                ),
                array('status' => 403)
            );
        }

        // Add new device
        $wpdb->insert(
            $table_name,
            array(
                'license_id' => $license_id,
                'device_fingerprint' => $device_fingerprint,
                'device_token' => $device_token,
                'device_combined_id' => $device_combined_id,
                'device_name' => $device_name,
                'user_agent' => $user_agent,
                'first_access_ip' => $client_ip,
                'last_ip' => $client_ip,
                'first_access_at' => current_time('mysql'),
                'last_access_at' => current_time('mysql'),
                'access_count' => 1,
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        return true;
    }

    /**
     * Assign pool to license (first-time access)
     *
     * @param int $license_id
     * @param int $product_id
     * @return bool|WP_Error
     */
    private function assign_pool_to_license($license_id, $product_id) {
        global $wpdb;

        $pools_table = $wpdb->prefix . 'vd_product_pools';
        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';

        // Get available pool (least-filled, active)
        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT pp.*, pa.id as account_id
             FROM $pools_table pp
             INNER JOIN $accounts_table pa ON pp.account_id = pa.id
             WHERE pp.product_id = %d
             AND pp.status = 'active'
             AND pa.status = 'active'
             AND pp.assigned_count < pp.pool_capacity
             ORDER BY pp.priority ASC, pp.assigned_count ASC
             LIMIT 1",
            $product_id
        ), ARRAY_A);

        if (!$pool) {
            return new WP_Error(
                'no_pool_available',
                'Không có pool nào khả dụng. Vui lòng liên hệ admin.',
                array('status' => 503)
            );
        }

        // Update license with pool assignment
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        $updated = $wpdb->update(
            $licenses_table,
            array(
                'assigned_pool_id' => $pool['id'],
                'assigned_account_id' => $pool['account_id'],
                'pool_assigned_at' => current_time('mysql')
            ),
            array('id' => $license_id),
            array('%d', '%d', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error(
                'assignment_failed',
                'Không thể gán pool. Vui lòng thử lại.',
                array('status' => 500)
            );
        }

        // Increment pool assigned_count
        $wpdb->query($wpdb->prepare(
            "UPDATE $pools_table
             SET assigned_count = assigned_count + 1
             WHERE id = %d",
            $pool['id']
        ));

        return true;
    }

    /**
     * Get license by ID
     *
     * @param int $license_id
     * @return array|null
     */
    private function get_license_by_id($license_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_license_keys';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $license_id
        ), ARRAY_A);
    }

    /**
     * Get provider account
     *
     * @param int $account_id
     * @return array|WP_Error
     */
    private function get_provider_account($account_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_provider_accounts';

        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $account_id
        ), ARRAY_A);

        if (!$account) {
            return new WP_Error(
                'account_not_found',
                'Không tìm thấy tài khoản. Vui lòng liên hệ admin.',
                array('status' => 500)
            );
        }

        return $account;
    }

    /**
     * Build credentials response based on response_fields config
     *
     * @param array $account
     * @param array $response_fields
     * @return array
     */
    private function build_credentials_response($account, $response_fields) {
        $credentials = array();

        if (empty($response_fields)) {
            // Fallback: return basic fields if no config
            return array(
                'account_login' => $account['account_login'] ?? '',
                'login_password' => $account['login_password'] ?? ''
            );
        }

        // Sort by order
        usort($response_fields, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });

        // Build response based on configured fields
        foreach ($response_fields as $field) {
            $key = $field['key'];

            if (isset($account[$key]) && !empty($account[$key])) {
                $credentials[$key] = $account[$key];
            }
        }

        return $credentials;
    }

    /**
     * Get license devices
     *
     * @param int $license_id
     * @return array
     */
    private function get_license_devices($license_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_license_devices';

        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT
                device_name,
                device_combined_id,
                first_access_at,
                last_access_at,
                last_ip,
                access_count,
                status
             FROM $table_name
             WHERE license_id = %d
             ORDER BY last_access_at DESC",
            $license_id
        ), ARRAY_A);

        return $devices ?: array();
    }

    /**
     * Calculate days remaining until expiration
     *
     * @param string $expires_at
     * @return int
     */
    private function calculate_days_remaining($expires_at) {
        if (empty($expires_at)) {
            return -1; // No expiration
        }

        $expires_timestamp = strtotime($expires_at);
        $now = time();

        $diff = $expires_timestamp - $now;

        return max(0, ceil($diff / 86400));
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];

                // Handle multiple IPs (proxy chain)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Log access attempt
     *
     * @param string $license_key
     * @param string $device_combined_id
     * @param string $client_ip
     * @param string $status
     * @return int Log ID
     */
    private function log_access_attempt($license_key, $device_combined_id, $client_ip, $status) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_license_access_log';

        // Get license ID
        $license_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}vd_license_keys WHERE license_key = %s",
            $license_key
        ));

        $wpdb->insert(
            $table_name,
            array(
                'license_id' => $license_id ?: 0,
                'license_key' => $license_key,
                'device_id' => $device_combined_id,
                'ip_address' => $client_ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'endpoint' => '/license/access',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'response_status' => 200,
                'authentication_result' => $status,
                'accessed_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        return $wpdb->insert_id;
    }

    /**
     * Update access log
     *
     * @param int $log_id
     * @param string $status
     * @param string|null $error_code
     */
    private function update_access_log($log_id, $status, $error_code = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_license_access_log';

        $wpdb->update(
            $table_name,
            array(
                'authentication_result' => $status,
                'error_code' => $error_code
            ),
            array('id' => $log_id),
            array('%s', '%s'),
            array('%d')
        );
    }

}

// Initialize
new VD_REST_API();
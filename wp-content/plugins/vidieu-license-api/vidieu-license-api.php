<?php
/**
 * Plugin Name: Vidieu License API
 * Description: Custom REST API endpoint for license validation from Google Apps Script
 * Version: 1.0.0
 * Author: Vidieu.vn
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VidieuLicenseAPI {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('vidieu/v1', '/validate-license', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_license'),
            'permission_callback' => '__return_true', // Allow public access
        ));
    }

    public function validate_license($request) {
        // Thêm logging để debug
        error_log('=== VIDIEU LICENSE API DEBUG START ===');

        try {
            // Lấy dữ liệu từ request
            $license_key = sanitize_text_field($request->get_param('license_key'));
            $email = sanitize_email($request->get_param('email'));
            $allowed_product_ids = $request->get_param('allowed_product_ids');
            $source = sanitize_text_field($request->get_param('source'));

            error_log('Request params: ' . json_encode([
                'license_key' => substr($license_key, 0, 8) . '***' . substr($license_key, -4),
                'email' => $email,
                'allowed_product_ids' => $allowed_product_ids,
                'source' => $source
            ]));

            // Validate input
            if (empty($license_key)) {
                error_log('FAILED: Empty license key');
                return $this->error_response('Empty license key');
            }

            if (empty($email)) {
                error_log('FAILED: Empty email');
                return $this->error_response('Empty email');
            }

            if (!is_array($allowed_product_ids) || empty($allowed_product_ids)) {
                error_log('FAILED: Invalid allowed_product_ids');
                return $this->error_response('Invalid product IDs');
            }

            if (!str_starts_with($license_key, 'PPC')) {
                error_log('FAILED: License key does not start with PPC');
                return $this->error_response('Invalid license key format');
            }

            error_log('Input validation passed, getting license data...');
            $license_data = $this->get_license_data($license_key);
            error_log('License data result: ' . json_encode($license_data));

            if (!$license_data['success']) {
                error_log('FAILED: License data retrieval failed');
                return $this->error_response('License not found');
            }

            // Fix double nesting - LMfWC trả về data.data
            $license = $license_data['data']['data'];
            error_log('License productId: ' . $license['productId']);
            error_log('Allowed product IDs: ' . json_encode($allowed_product_ids));

            if (!in_array($license['productId'], $allowed_product_ids)) {
                error_log('FAILED: Product ID not in allowed list');
                return $this->error_response('Product ID mismatch');
            }

            error_log('Checking license expiry...');
            $expiry_check = $this->check_license_expiry($license);
            error_log('Expiry check result: ' . json_encode($expiry_check));

            if (!$expiry_check['valid']) {
                error_log('FAILED: License expired or invalid expiry');
                return $this->error_response('License expired', $expiry_check['status']);
            }

            error_log('Checking activation status...');
            $activation_check = $this->check_activation_status($license_key, $license, $email);
            error_log('Activation check result: ' . json_encode($activation_check));

            if ($activation_check['success']) {
                error_log('SUCCESS: License validation completed');
                return $this->success_response($license, 'License validated successfully', $activation_check['status']);
            } else {
                error_log('FAILED: Activation check failed');
                return $this->error_response('Activation failed', $activation_check['status']);
            }

        } catch (Exception $e) {
            error_log('EXCEPTION: ' . $e->getMessage());
            return $this->error_response('System error: ' . $e->getMessage());
        }
    }

    private function get_license_data($license_key) {
        // Consumer credentials được lưu bảo mật trên server
        $consumer_key = $this->get_consumer_key();
        $consumer_secret = $this->get_consumer_secret();

        error_log('Consumer key: ' . substr($consumer_key, 0, 10) . '...');

        $url = "https://vidieu.vn/wp-json/lmfwc/v2/licenses/{$license_key}?consumer_key={$consumer_key}&consumer_secret={$consumer_secret}";
        error_log('LMfWC API URL: ' . $url);

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            error_log('WP Error: ' . $response->get_error_message());
            return array(
                'success' => false,
                'message' => 'WP Error: ' . $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        error_log('LMfWC Response Code: ' . $response_code);
        error_log('LMfWC Response Body: ' . $response_body);

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'HTTP ' . $response_code
            );
        }

        $license_data = json_decode($response_body, true);

        if (!$license_data) {
            error_log('Failed to parse JSON response');
            return array(
                'success' => false,
                'message' => 'Invalid JSON'
            );
        }

        error_log('License data parsed successfully');
        return array(
            'success' => true,
            'data' => $license_data
        );
    }

    private function check_license_expiry($license) {
        if (empty($license['expiresAt'])) {
            return array(
                'valid' => false,
                'status' => 'error',
                'message' => 'License không có ngày hết hạn hợp lệ. Vui lòng kiểm tra lại.'
            );
        }

        $expiry_date = strtotime($license['expiresAt']);
        $current_date = time();

        error_log('Expiry date: ' . $license['expiresAt'] . ' (timestamp: ' . $expiry_date . ')');
        error_log('Current date: ' . date('Y-m-d H:i:s') . ' (timestamp: ' . $current_date . ')');

        if ($current_date > $expiry_date) {
            return array(
                'valid' => false,
                'status' => 'warning',
                'message' => 'License đã hết hạn. Vui lòng gia hạn để tiếp tục sử dụng.'
            );
        }

        return array('valid' => true);
    }

    private function check_activation_status($license_key, $license, $current_email) {
        $activation_data = isset($license['activationData']) ? $license['activationData'] : array();
        error_log('Activation data count: ' . count($activation_data));

        // Nếu chưa có activation data -> kích hoạt tự động
        if (empty($activation_data)) {
            $activation_result = $this->activate_license($license_key, $current_email);

            if ($activation_result['success']) {
                return array(
                    'success' => true,
                    'status' => 'success',
                    'message' => 'License được kích hoạt thành công với email hiện tại.'
                );
            } else {
                // Fallback: Cho phép sử dụng nhưng cảnh báo
                return array(
                    'success' => true,
                    'status' => 'warning',
                    'message' => 'Không thể kích hoạt License tự động, nhưng License vẫn hợp lệ. Bạn có thể tiếp tục sử dụng.\n\nLý do: ' . $activation_result['message']
                );
            }
        }

        // Có activation data -> kiểm tra email
        $registered_email = null;
        foreach ($activation_data as $activation) {
            if (!empty($activation['label'])) {
                $registered_email = $activation['label'];
                break;
            }
        }

        if ($registered_email) {
            if (strtolower($current_email) === strtolower($registered_email)) {
                return array(
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Xác thực thành công.'
                );
            } else {
                return array(
                    'success' => false,
                    'status' => 'error',
                    'message' => "Email không trùng khớp. License này đã được kích hoạt cho email: {$registered_email}"
                );
            }
        }

        return array(
            'success' => false,
            'status' => 'error',
            'message' => 'Không thể xác định trạng thái kích hoạt của license.'
        );
    }

    private function activate_license($license_key, $email) {
        $consumer_key = $this->get_consumer_key();
        $consumer_secret = $this->get_consumer_secret();

        $url = "https://vidieu.vn/wp-json/lmfwc/v2/licenses/activate/{$license_key}?consumer_key={$consumer_key}&consumer_secret={$consumer_secret}&label=" . urlencode($email);

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array('success' => false, 'message' => '');
        }

        return array('success' => true);
    }

    private function get_consumer_key() {
        // Lấy từ WordPress options hoặc constants
        return defined('VIDIEU_CONSUMER_KEY') ? VIDIEU_CONSUMER_KEY : get_option('vidieu_consumer_key', 'ck_947cc6b4c800204a32bff1159497999486560ee3');
    }

    private function get_consumer_secret() {
        // Lấy từ WordPress options hoặc constants
        return defined('VIDIEU_CONSUMER_SECRET') ? VIDIEU_CONSUMER_SECRET : get_option('vidieu_consumer_secret', 'cs_f01741effc56456935c132758acf8b855a13c664');
    }

    private function success_response($license_data, $message = '', $status = 'success') {
        return new WP_REST_Response(array(
            'success' => true,
            'status' => $status,
            'message' => $message,
            'data' => $license_data
        ), 200);
    }

    private function error_response($message, $status = 'error') {
        return new WP_REST_Response(array(
            'success' => false,
            'status' => $status,
            'message' => $message,
            'data' => null
        ), 200); // Return 200 to avoid client-side errors
    }
}

// Initialize the plugin
new VidieuLicenseAPI();
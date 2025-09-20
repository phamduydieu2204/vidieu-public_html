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
        try {
            // Lấy dữ liệu từ request
            $license_key = sanitize_text_field($request->get_param('license_key'));
            $email = sanitize_email($request->get_param('email'));
            $allowed_product_ids = $request->get_param('allowed_product_ids');
            $source = sanitize_text_field($request->get_param('source'));

            // Validate input
            if (empty($license_key) || empty($email) || !is_array($allowed_product_ids) || empty($allowed_product_ids) || !str_starts_with($license_key, 'PPC')) {
                return $this->error_response('');
            }

            $license_data = $this->get_license_data($license_key);

            if (!$license_data['success']) {
                return $this->error_response('');
            }

            $license = $license_data['data'];

            if (!in_array($license['product_id'], $allowed_product_ids)) {
                return $this->error_response('');
            }

            $expiry_check = $this->check_license_expiry($license);
            if (!$expiry_check['valid']) {
                return $this->error_response('', $expiry_check['status']);
            }

            $activation_check = $this->check_activation_status($license_key, $license, $email);

            if ($activation_check['success']) {
                return $this->success_response($license, '', $activation_check['status']);
            } else {
                return $this->error_response('', $activation_check['status']);
            }

        } catch (Exception $e) {
            return $this->error_response('');
        }
    }

    private function get_license_data($license_key) {
        // Consumer credentials được lưu bảo mật trên server
        $consumer_key = $this->get_consumer_key();
        $consumer_secret = $this->get_consumer_secret();

        $url = "https://vidieu.vn/wp-json/lmfwc/v2/licenses/{$license_key}?consumer_key={$consumer_key}&consumer_secret={$consumer_secret}";

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => ''
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => ''
            );
        }

        $license_data = json_decode(wp_remote_retrieve_body($response), true);

        if (!$license_data) {
            return array(
                'success' => false,
                'message' => ''
            );
        }

        return array(
            'success' => true,
            'data' => $license_data
        );
    }

    private function check_license_expiry($license) {
        if (empty($license['expires_at'])) {
            return array(
                'valid' => false,
                'status' => 'error',
                'message' => 'License không có ngày hết hạn hợp lệ. Vui lòng kiểm tra lại.'
            );
        }

        $expiry_date = strtotime($license['expires_at']);
        $current_date = time();

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
        $activation_data = isset($license['activation_data']) ? $license['activation_data'] : array();

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
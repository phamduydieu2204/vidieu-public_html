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

            // Log request để debug
            error_log('Vidieu License API Request: ' . json_encode([
                'license_key' => substr($license_key, 0, 8) . '***' . substr($license_key, -4),
                'email' => $email,
                'allowed_product_ids' => $allowed_product_ids,
                'source' => $source
            ]));

            // Validate input
            if (empty($license_key)) {
                return $this->error_response('Thiếu License Key trong request.');
            }

            if (empty($email)) {
                return $this->error_response('Thiếu email trong request.');
            }

            if (!is_array($allowed_product_ids) || empty($allowed_product_ids)) {
                return $this->error_response('Danh sách Product ID không hợp lệ.');
            }

            // Kiểm tra prefix PPC
            if (!str_starts_with($license_key, 'PPC')) {
                return $this->error_response('License Key không hợp lệ. Key này không thuộc phần mềm PPC Amazon.');
            }

            // Lấy thông tin license từ LMfWC API
            $license_data = $this->get_license_data($license_key);

            if (!$license_data['success']) {
                return $this->error_response($license_data['message']);
            }

            $license = $license_data['data'];

            // Kiểm tra product ID
            if (!in_array($license['product_id'], $allowed_product_ids)) {
                return $this->error_response(
                    'License này không dành cho các sản phẩm được hỗ trợ (Product IDs: ' .
                    implode(', ', $allowed_product_ids) . ').'
                );
            }

            // Kiểm tra hạn sử dụng
            $expiry_check = $this->check_license_expiry($license);
            if (!$expiry_check['valid']) {
                return $this->error_response($expiry_check['message'], $expiry_check['status']);
            }

            // Kiểm tra và xử lý activation
            $activation_check = $this->check_activation_status($license_key, $license, $email);

            if ($activation_check['success']) {
                return $this->success_response(
                    $license,
                    $activation_check['message'],
                    $activation_check['status']
                );
            } else {
                return $this->error_response(
                    $activation_check['message'],
                    $activation_check['status']
                );
            }

        } catch (Exception $e) {
            error_log('Vidieu License API Error: ' . $e->getMessage());
            return $this->error_response('Có lỗi xảy ra khi xử lý license. Vui lòng thử lại sau.');
        }
    }

    private function get_license_data($license_key) {
        $consumer_key = "ck_947cc6b4c800204a32bff1159497999486560ee3";
        $consumer_secret = "cs_f01741effc56456935c132758acf8b855a13c664";

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
                'message' => 'Không thể kết nối đến máy chủ License. Vui lòng thử lại sau.'
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        error_log('LMfWC API Response: ' . json_encode([
            'code' => $response_code,
            'body' => $response_body
        ]));

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'License không tồn tại trên hệ thống.'
            );
        }

        $license_data = json_decode($response_body, true);

        if (!$license_data) {
            return array(
                'success' => false,
                'message' => 'Dữ liệu license không hợp lệ.'
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
        $consumer_key = "ck_947cc6b4c800204a32bff1159497999486560ee3";
        $consumer_secret = "cs_f01741effc56456935c132758acf8b855a13c664";

        $url = "https://vidieu.vn/wp-json/lmfwc/v2/licenses/activate/{$license_key}?consumer_key={$consumer_key}&consumer_secret={$consumer_secret}&label=" . urlencode($email);

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Không thể kích hoạt license - lỗi kết nối.'
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        error_log('License Activation Response: ' . json_encode([
            'code' => $response_code,
            'body' => $response_body
        ]));

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'Không thể kích hoạt license - mã lỗi: ' . $response_code
            );
        }

        return array('success' => true);
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
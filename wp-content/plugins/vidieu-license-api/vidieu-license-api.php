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
        error_log('License API called');

        try {
            $license_key = sanitize_text_field($request->get_param('license_key'));
            $email = sanitize_email($request->get_param('email'));
            $allowed_product_ids = $request->get_param('allowed_product_ids');
            $source = sanitize_text_field($request->get_param('source'));

            error_log('Params: ' . json_encode([
                'license_key' => substr($license_key, 0, 8) . '***',
                'email' => $email,
                'product_ids' => $allowed_product_ids
            ]));

            if (empty($license_key)) {
                error_log('Empty license key');
                return $this->error_response('Thiếu License Key. Vui lòng nhập license key vào ô D4 trước khi tiếp tục.');
            }

            if (empty($email)) {
                error_log('Empty email');
                return $this->error_response('Không thể xác định email người dùng. Vui lòng đăng nhập lại Google Apps Script.');
            }

            if (!is_array($allowed_product_ids) || empty($allowed_product_ids)) {
                error_log('Invalid product IDs');
                return $this->error_response('Lỗi cấu hình hệ thống. Vui lòng liên hệ hỗ trợ.');
            }

            if (!str_starts_with($license_key, 'PPC')) {
                error_log('Invalid license format');
                return $this->error_response('License Key không hợp lệ. Key này không thuộc phần mềm PPC Amazon.');
            }

            $license_data = $this->get_license_data($license_key);

            if (!$license_data['success']) {
                error_log('License data failed');
                return $this->error_response('License Key không tồn tại trên hệ thống. Vui lòng kiểm tra lại.');
            }

            $license = $license_data['data']['data'];
            error_log('Product ID: ' . $license['productId'] . ', Expires: ' . $license['expiresAt']);

            if (!in_array($license['productId'], $allowed_product_ids)) {
                error_log('Product ID mismatch');
                return $this->error_response('License này không dành cho phần mềm này. Vui lòng sử dụng license key phù hợp.');
            }

            $expiry_check = $this->check_license_expiry($license);
            if (!$expiry_check['valid']) {
                error_log('License expired, status: ' . $expiry_check['status']);
                return $this->error_response($expiry_check['message'], $expiry_check['status']);
            }

            $activation_check = $this->check_activation_status($license_key, $license, $email);

            if ($activation_check['success']) {
                error_log('License validation SUCCESS');
                return $this->success_response($license, $activation_check['message'], $activation_check['status']);
            } else {
                error_log('Activation check failed');
                return $this->error_response($activation_check['message'], $activation_check['status']);
            }

        } catch (Exception $e) {
            return $this->error_response('');
        }
    }

    private function get_license_data($license_key) {
        $consumer_key = $this->get_consumer_key();
        $consumer_secret = $this->get_consumer_secret();

        $url = "https://vidieu.vn/wp-json/lmfwc/v2/licenses/{$license_key}?consumer_key={$consumer_key}&consumer_secret={$consumer_secret}";

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array('success' => false, 'message' => '');
        }

        $license_data = json_decode(wp_remote_retrieve_body($response), true);

        if (!$license_data) {
            return array('success' => false, 'message' => '');
        }

        return array('success' => true, 'data' => $license_data);
    }

    private function check_license_expiry($license) {
        if (empty($license['expiresAt'])) {
            return array(
                'valid' => false,
                'status' => 'error',
                'message' => 'License không có ngày hết hạn hợp lệ. Vui lòng liên hệ hỗ trợ để kiểm tra.'
            );
        }

        if (time() > strtotime($license['expiresAt'])) {
            $expiry_date = date('d/m/Y H:i', strtotime($license['expiresAt']));
            return array(
                'valid' => false,
                'status' => 'warning',
                'message' => "License đã hết hạn vào {$expiry_date}. Vui lòng gia hạn để tiếp tục sử dụng."
            );
        }

        return array('valid' => true);
    }

    private function check_activation_status($license_key, $license, $current_email) {
        $activation_data = isset($license['activationData']) ? $license['activationData'] : array();

        // Nếu chưa có activation data -> kích hoạt tự động
        if (empty($activation_data)) {
            $activation_result = $this->activate_license($license_key, $current_email);

            if ($activation_result['success']) {
                return array(
                    'success' => true,
                    'status' => 'success',
                    'message' => "License đã được kích hoạt thành công cho email: {$current_email}"
                );
            } else {
                // Fallback: Cho phép sử dụng nhưng cảnh báo
                return array(
                    'success' => true,
                    'status' => 'warning',
                    'message' => "Không thể kích hoạt license tự động, nhưng bạn vẫn có thể sử dụng.\n\nVui lòng liên hệ hỗ trợ nếu gặp vấn đề."
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
                    'message' => 'Xác thực license thành công. Chào mừng bạn trở lại!'
                );
            } else {
                return array(
                    'success' => false,
                    'status' => 'error',
                    'message' => "Email không trùng khớp với license này.\n\nEmail đăng ký license: {$registered_email}\nEmail hiện tại: {$current_email}\n\nVui lòng sử dụng đúng email đã đăng ký hoặc liên hệ hỗ trợ."
                );
            }
        }

        return array(
            'success' => false,
            'status' => 'error',
            'message' => 'Không thể xác định trạng thái kích hoạt của license. Vui lòng liên hệ hỗ trợ.'
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
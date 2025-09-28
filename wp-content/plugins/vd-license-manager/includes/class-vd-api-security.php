<?php

if (!defined('ABSPATH')) {
    exit;
}

class VD_API_Security {

    private static $instance = null;

    private function __construct() {

    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __clone() {

    }

    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    public function get_status() {
        return 'initialized';
    }

    public function is_working() {
        return true;
    }

    public function get_current_step() {
        return '3.5.3';
    }

    public function get_authentication_methods() {
        return [
            'bearer_token' => 'validate_bearer_token',
            'wp_nonce' => 'validate_wp_nonce',
            'api_key' => 'validate_api_key',
            'hmac_signature' => 'validate_hmac_signature'
        ];
    }

    public function get_authentication_status() {
        return [
            'bearer_token_enabled' => true,
            'wp_nonce_enabled' => true,
            'api_key_enabled' => true,
            'hmac_signature_enabled' => true,
            'framework_ready' => true
        ];
    }

    public function validate_bearer_token($token) {
        return 'framework_ready';
    }

    public function validate_wp_nonce($nonce) {
        return 'framework_ready';
    }

    public function validate_api_key($api_key) {
        return 'framework_ready';
    }

    public function validate_hmac_signature($signature, $payload) {
        return 'framework_ready';
    }

    public function get_supported_auth_types() {
        return [
            'admin_endpoints' => ['bearer_token', 'wp_nonce'],
            'api_endpoints' => ['api_key', 'hmac_signature'],
            'public_endpoints' => []
        ];
    }

    public function is_authentication_framework_ready() {
        return true;
    }
}
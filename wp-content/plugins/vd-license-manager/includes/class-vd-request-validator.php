<?php

if (!defined('ABSPATH')) {
    exit;
}

class VD_Request_Validator {

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
        return '3.5.6';
    }

    public function get_validation_methods() {
        return [
            'license_key' => 'validate_license_key',
            'device_fingerprint' => 'validate_device_fingerprint',
            'device_info' => 'validate_device_info',
            'rate_limit' => 'validate_rate_limit',
            'request_data' => 'validate_request_data'
        ];
    }
}
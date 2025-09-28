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
        return '3.5.1';
    }
}
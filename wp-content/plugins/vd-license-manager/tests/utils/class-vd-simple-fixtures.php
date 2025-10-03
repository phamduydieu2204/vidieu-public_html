<?php
/**
 * Simple Test Fixtures - Sample data for testing
 *
 * Simplified version that works without WordPress test suite
 *
 * @package VD_License_Manager
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple Test Fixtures for Integration Testing
 */
class VD_Simple_Fixtures {

    /**
     * Get sample license data
     */
    public function getSampleLicense() {
        return [
            'license_key' => 'VD-TEST-' . uniqid(),
            'product_id' => 1,
            'user_id' => 1,
            'status' => 'active',
            'activation_limit' => 5,
            'activation_count' => 2,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get sample user data
     */
    public function getSampleUser() {
        return [
            'ID' => 1,
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_registered' => date('Y-m-d H:i:s'),
            'display_name' => 'Test User'
        ];
    }

    /**
     * Get sample product data
     */
    public function getSampleProduct() {
        return [
            'id' => 1,
            'name' => 'Test Product',
            'sku' => 'TEST-PRODUCT-001',
            'price' => 99.99,
            'status' => 'publish'
        ];
    }

    /**
     * Get sample activation data
     */
    public function getSampleActivation() {
        return [
            'license_key' => 'VD-TEST-' . uniqid(),
            'device_id' => 'device-' . uniqid(),
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'activated_at' => date('Y-m-d H:i:s'),
            'last_used' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get sample API request data
     */
    public function getSampleApiRequest() {
        return [
            'action' => 'validate_license',
            'license_key' => 'VD-TEST-' . uniqid(),
            'product_id' => 1,
            'domain' => 'example.com',
            'ip' => '192.168.1.100',
            'timestamp' => time()
        ];
    }

    /**
     * Get sample validation rules
     */
    public function getSampleValidationRules() {
        return [
            'format' => [
                'pattern' => '^VD-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$',
                'length' => 19,
                'required_prefix' => 'VD-'
            ],
            'activation' => [
                'max_activations' => 5,
                'check_domain' => true,
                'check_ip' => false,
                'allow_localhost' => true
            ],
            'security' => [
                'rate_limit' => 100,
                'time_window' => 3600,
                'require_ssl' => false
            ]
        ];
    }

    /**
     * Get sample error responses
     */
    public function getSampleErrors() {
        return [
            'invalid_license' => 'License key is invalid or expired',
            'activation_limit' => 'Maximum activations reached',
            'invalid_domain' => 'Domain not authorized for this license',
            'rate_limit' => 'Too many requests, please try again later',
            'server_error' => 'Internal server error occurred'
        ];
    }

    /**
     * Get sample cache data
     */
    public function getSampleCacheData() {
        return [
            'license_validation_cache' => [
                'key' => 'vd_license_val_' . md5('test-license'),
                'value' => ['status' => 'valid', 'expires' => time() + 3600],
                'expiration' => 3600
            ],
            'user_activation_cache' => [
                'key' => 'vd_user_act_1',
                'value' => ['count' => 2, 'devices' => ['device1', 'device2']],
                'expiration' => 1800
            ]
        ];
    }

    /**
     * Get sample webhook data
     */
    public function getSampleWebhookData() {
        return [
            'event' => 'license.activated',
            'license_key' => 'VD-TEST-' . uniqid(),
            'user_id' => 1,
            'product_id' => 1,
            'timestamp' => time(),
            'data' => [
                'domain' => 'example.com',
                'ip' => '192.168.1.100',
                'device_id' => 'device-123'
            ]
        ];
    }

    /**
     * Generate random test data
     */
    public function generateRandomData($type = 'license') {
        switch ($type) {
            case 'license':
                return $this->getSampleLicense();
            case 'user':
                return $this->getSampleUser();
            case 'product':
                return $this->getSampleProduct();
            case 'activation':
                return $this->getSampleActivation();
            default:
                return ['type' => $type, 'generated_at' => date('Y-m-d H:i:s')];
        }
    }
}
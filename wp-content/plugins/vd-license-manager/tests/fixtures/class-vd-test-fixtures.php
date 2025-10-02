<?php

namespace VD\LicenseManager\Tests\Fixtures;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Test Fixtures
 *
 * Comprehensive test data generator for all 25 modules across 7 categories
 * Provides realistic sample data for testing all components
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */
class VD_Test_Fixtures {

    /**
     * Singleton instance
     *
     * @var VD_Test_Fixtures|null
     */
    private static $instance = null;

    /**
     * Sample data cache
     *
     * @var array
     */
    private $data_cache = array();

    /**
     * Get singleton instance
     *
     * @return VD_Test_Fixtures
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get sample license data
     *
     * @param string $type License type (valid, expired, suspended, etc.)
     * @return array Sample license data
     */
    public function get_license_data($type = 'valid') {
        $base_data = array(
            'license_key' => $this->generate_license_key(),
            'user_id' => 123,
            'product_id' => 456,
            'activation_limit' => 3,
            'times_activated' => 1,
            'created_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'provider' => 'helium10',
            'provider_account_id' => 789
        );

        switch ($type) {
            case 'expired':
                $base_data['expires_at'] = date('Y-m-d H:i:s', strtotime('-1 month'));
                $base_data['status'] = 'expired';
                break;
            case 'suspended':
                $base_data['status'] = 'suspended';
                $base_data['suspended_at'] = current_time('mysql');
                break;
            case 'maxed_out':
                $base_data['times_activated'] = $base_data['activation_limit'];
                $base_data['status'] = 'active';
                break;
            case 'valid':
            default:
                $base_data['status'] = 'active';
                break;
        }

        return $base_data;
    }

    /**
     * Get sample provider data for all supported providers
     *
     * @param string $provider Provider name (helium10, midjourney, freepik, woocommerce)
     * @return array Provider configuration data
     */
    public function get_provider_data($provider = 'helium10') {
        $providers = array(
            'helium10' => array(
                'id' => 1,
                'name' => 'Helium10',
                'account_name' => 'test-h10-account',
                'provider' => 'helium10',
                'share_type' => 'credentials_2fa',
                'status' => 'active',
                'credentials' => array(
                    'email' => 'test@helium10.com',
                    'password' => '[TEST_PASSWORD]',
                    '2fa_secret' => '[TEST_2FA_SECRET]',
                    'cookie_data' => 'session_id=test123; auth_token=test456'
                ),
                'settings' => array(
                    'auto_renewal' => true,
                    'notification_email' => 'admin@test.com',
                    'usage_limit' => 1000
                )
            ),
            'midjourney' => array(
                'id' => 2,
                'name' => 'Midjourney',
                'account_name' => 'test-mj-account',
                'provider' => 'midjourney',
                'share_type' => 'discord_token',
                'status' => 'active',
                'credentials' => array(
                    'discord_token' => '[TEST_DISCORD_TOKEN]',
                    'session_cookie' => '__Secure-next-auth.session-token=test_session_123',
                    'user_agent' => 'Mozilla/5.0 (Test Browser)',
                    'channel_id' => '1234567890123456789',
                    'server_id' => '9876543210987654321'
                ),
                'settings' => array(
                    'fast_mode' => true,
                    'private_mode' => false,
                    'max_concurrent' => 3
                )
            ),
            'freepik' => array(
                'id' => 3,
                'name' => 'Freepik',
                'account_name' => 'test-freepik-account',
                'provider' => 'freepik',
                'share_type' => 'api_key',
                'status' => 'active',
                'credentials' => array(
                    'api_key' => '[TEST_FREEPIK_API_KEY]',
                    'user_id' => 'test_user_12345',
                    'subscription_type' => 'premium'
                ),
                'settings' => array(
                    'download_limit' => 100,
                    'quality' => 'high',
                    'format' => 'jpg'
                )
            ),
            'woocommerce' => array(
                'id' => 4,
                'name' => 'WooCommerce',
                'account_name' => 'test-wc-integration',
                'provider' => 'woocommerce',
                'share_type' => 'internal',
                'status' => 'active',
                'credentials' => array(
                    'consumer_key' => '[TEST_WC_CONSUMER_KEY]',
                    'consumer_secret' => '[TEST_WC_CONSUMER_SECRET]',
                    'api_version' => 'v3'
                ),
                'settings' => array(
                    'auto_sync' => true,
                    'sync_interval' => 'hourly',
                    'order_status_sync' => true
                )
            )
        );

        return isset($providers[$provider]) ? $providers[$provider] : $providers['helium10'];
    }

    /**
     * Get sample security event data
     *
     * @param string $severity Event severity level
     * @return array Security event data
     */
    public function get_security_event_data($severity = 'info') {
        return array(
            'event_type' => 'license_validation',
            'severity' => $severity,
            'category' => 'authentication',
            'message' => 'License validation attempted',
            'details' => array(
                'license_key' => substr($this->generate_license_key(), 0, 8) . '...',
                'user_ip' => '192.168.1.100',
                'user_agent' => 'Test User Agent',
                'validation_result' => 'success'
            ),
            'context' => array(
                'user_id' => 123,
                'session_id' => 'test_session_456',
                'request_id' => 'req_' . uniqid(),
                'endpoint' => '/wp-admin/admin-ajax.php'
            ),
            'timestamp' => current_time('mysql'),
            'hash' => hash('sha256', 'test_event_' . time())
        );
    }

    /**
     * Get sample API request data
     *
     * @param string $endpoint API endpoint
     * @return array API request data
     */
    public function get_api_request_data($endpoint = '/api/v1/license/validate') {
        return array(
            'endpoint' => $endpoint,
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer test_token_123',
                'User-Agent' => 'VD-License-Manager/1.6.0'
            ),
            'body' => array(
                'license_key' => $this->generate_license_key(),
                'domain' => 'test.example.com',
                'product_id' => 456
            ),
            'response' => array(
                'status' => 200,
                'data' => array(
                    'valid' => true,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                    'activations_left' => 2
                )
            ),
            'performance' => array(
                'response_time' => 45,
                'memory_usage' => 2048000,
                'queries_count' => 3
            )
        );
    }

    /**
     * Get sample webhook data
     *
     * @param string $event Webhook event type
     * @return array Webhook data
     */
    public function get_webhook_data($event = 'license_status_changed') {
        return array(
            'id' => 'webhook_' . uniqid(),
            'event' => $event,
            'url' => 'https://test.example.com/webhook',
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-VD-Signature' => 'sha256=test_signature',
                'X-VD-Event' => $event
            ),
            'payload' => array(
                'license_key' => substr($this->generate_license_key(), 0, 8) . '...',
                'old_status' => 'active',
                'new_status' => 'expired',
                'timestamp' => current_time('mysql'),
                'user_id' => 123
            ),
            'delivery' => array(
                'attempts' => 1,
                'last_attempt' => current_time('mysql'),
                'next_attempt' => null,
                'status' => 'pending'
            ),
            'config' => array(
                'timeout' => 30,
                'retry_attempts' => 3,
                'retry_delay' => 300
            )
        );
    }

    /**
     * Get sample database query data
     *
     * @param string $type Query type (select, insert, update, delete)
     * @return array Database query data
     */
    public function get_database_query_data($type = 'select') {
        $queries = array(
            'select' => array(
                'sql' => 'SELECT * FROM wp_vd_licenses WHERE status = %s',
                'params' => array('active'),
                'execution_time' => 12.5,
                'rows_affected' => 150
            ),
            'insert' => array(
                'sql' => 'INSERT INTO wp_vd_licenses (license_key, user_id, status) VALUES (%s, %d, %s)',
                'params' => array($this->generate_license_key(), 123, 'active'),
                'execution_time' => 8.2,
                'rows_affected' => 1
            ),
            'update' => array(
                'sql' => 'UPDATE wp_vd_licenses SET status = %s WHERE id = %d',
                'params' => array('expired', 456),
                'execution_time' => 5.1,
                'rows_affected' => 1
            ),
            'delete' => array(
                'sql' => 'DELETE FROM wp_vd_licenses WHERE status = %s AND expires_at < %s',
                'params' => array('expired', date('Y-m-d H:i:s', strtotime('-1 year'))),
                'execution_time' => 15.3,
                'rows_affected' => 25
            )
        );

        return isset($queries[$type]) ? $queries[$type] : $queries['select'];
    }

    /**
     * Get sample performance metrics
     *
     * @return array Performance metrics data
     */
    public function get_performance_metrics() {
        return array(
            'response_times' => array(
                'avg' => 35.5,
                'min' => 12.1,
                'max' => 89.3,
                'p95' => 65.2,
                'p99' => 78.9
            ),
            'memory_usage' => array(
                'current' => 2048000,
                'peak' => 3145728,
                'limit' => 134217728
            ),
            'database' => array(
                'queries_count' => 15,
                'total_time' => 145.6,
                'slow_queries' => 2
            ),
            'cache' => array(
                'hits' => 89,
                'misses' => 11,
                'hit_ratio' => 89.0
            ),
            'errors' => array(
                'count' => 0,
                'last_error' => null
            )
        );
    }

    /**
     * Generate sample license key
     *
     * @return string Generated license key
     */
    private function generate_license_key() {
        return 'VD-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-' .
               strtoupper(substr(md5(uniqid()), 0, 8)) . '-' .
               strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * Get sample data for any module category
     *
     * @param string $category Module category (format, database, status, rules, security, api, integration)
     * @return array Sample data for the category
     */
    public function get_category_data($category) {
        $category_data = array(
            'format' => array(
                'valid_patterns' => array('VD-XXXXXXXX-XXXXXXXX-XXXXXXXX', 'LM-XXXX-XXXX-XXXX'),
                'invalid_patterns' => array('INVALID-KEY', '123456', 'SHORT'),
                'checksums' => array('md5', 'sha256', 'crc32')
            ),
            'database' => array(
                'tables' => array('wp_vd_licenses', 'wp_vd_activations', 'wp_vd_providers'),
                'queries' => $this->get_database_query_data(),
                'cache_keys' => array('vd_license_123', 'vd_provider_456', 'vd_stats_daily')
            ),
            'status' => array(
                'statuses' => array('active', 'expired', 'suspended', 'pending', 'cancelled'),
                'transitions' => array('pending->active', 'active->expired', 'active->suspended'),
                'business_rules' => array('auto_expire', 'grace_period', 'renewal_reminder')
            ),
            'rules' => array(
                'activation_limits' => array(1, 3, 5, 10, -1),
                'expiry_periods' => array('30days', '1year', '2years', 'lifetime'),
                'usage_constraints' => array('domain_limit', 'ip_restriction', 'user_limit')
            ),
            'security' => array(
                'events' => $this->get_security_event_data(),
                'threats' => array('brute_force', 'invalid_license', 'suspicious_activity'),
                'privacy_data' => array('pii_detected', 'anonymized', 'encrypted')
            ),
            'api' => array(
                'endpoints' => array('/api/v1/license/validate', '/api/v1/webhook', '/api/v1/provider'),
                'requests' => $this->get_api_request_data(),
                'authentication' => array('bearer_token', 'api_key', 'oauth')
            ),
            'integration' => array(
                'providers' => array('helium10', 'midjourney', 'freepik', 'woocommerce'),
                'provider_data' => $this->get_provider_data(),
                'webhooks' => $this->get_webhook_data()
            )
        );

        return isset($category_data[$category]) ? $category_data[$category] : array();
    }

    /**
     * Generate bulk test data
     *
     * @param string $type Data type to generate
     * @param int $count Number of records to generate
     * @return array Bulk test data
     */
    public function generate_bulk_data($type, $count = 10) {
        $bulk_data = array();

        for ($i = 0; $i < $count; $i++) {
            switch ($type) {
                case 'licenses':
                    $bulk_data[] = $this->get_license_data();
                    break;
                case 'providers':
                    $providers = array('helium10', 'midjourney', 'freepik', 'woocommerce');
                    $bulk_data[] = $this->get_provider_data($providers[$i % 4]);
                    break;
                case 'security_events':
                    $severities = array('info', 'warning', 'error', 'critical');
                    $bulk_data[] = $this->get_security_event_data($severities[$i % 4]);
                    break;
                case 'api_requests':
                    $endpoints = array('/api/v1/license/validate', '/api/v1/webhook', '/api/v1/provider');
                    $bulk_data[] = $this->get_api_request_data($endpoints[$i % 3]);
                    break;
                default:
                    $bulk_data[] = array('id' => $i + 1, 'data' => 'sample_' . $i);
                    break;
            }
        }

        return $bulk_data;
    }

    /**
     * Clear data cache
     *
     * @return void
     */
    public function clear_cache() {
        $this->data_cache = array();
    }
}
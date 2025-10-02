<?php

namespace VD\LicenseManager\Tests\Mocks;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Test Mocks
 *
 * Mock objects for external services and WordPress functions
 * Enables isolated testing without external dependencies
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */
class VD_Test_Mocks {

    /**
     * Singleton instance
     *
     * @var VD_Test_Mocks|null
     */
    private static $instance = null;

    /**
     * Mock responses cache
     *
     * @var array
     */
    private $mock_responses = array();

    /**
     * Get singleton instance
     *
     * @return VD_Test_Mocks
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Mock WordPress HTTP API requests
     *
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array Mock response
     */
    public function mock_wp_remote_request($url, $args = array()) {
        // Mock Helium10 API responses
        if (strpos($url, 'helium10') !== false) {
            return $this->get_helium10_mock_response($url, $args);
        }

        // Mock Midjourney API responses
        if (strpos($url, 'midjourney') !== false || strpos($url, 'discord') !== false) {
            return $this->get_midjourney_mock_response($url, $args);
        }

        // Mock Freepik API responses
        if (strpos($url, 'freepik') !== false) {
            return $this->get_freepik_mock_response($url, $args);
        }

        // Mock WooCommerce API responses
        if (strpos($url, 'wc-api') !== false || strpos($url, 'wp-json/wc') !== false) {
            return $this->get_woocommerce_mock_response($url, $args);
        }

        // Mock webhook deliveries
        if (strpos($url, 'webhook') !== false) {
            return $this->get_webhook_mock_response($url, $args);
        }

        // Default successful response
        return array(
            'response' => array(
                'code' => 200,
                'message' => 'OK'
            ),
            'body' => wp_json_encode(array(
                'success' => true,
                'message' => 'Mock response',
                'data' => array()
            ))
        );
    }

    /**
     * Get Helium10 API mock response
     *
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array Mock response
     */
    private function get_helium10_mock_response($url, $args) {
        // Mock login response
        if (strpos($url, 'login') !== false) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    'success' => true,
                    'token' => 'mock_h10_token_123',
                    'expires_in' => 3600,
                    'user' => array(
                        'id' => 'mock_user_123',
                        'email' => 'test@helium10.com',
                        'subscription' => 'platinum'
                    )
                ))
            );
        }

        // Mock tools access response
        if (strpos($url, 'tools') !== false) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    'success' => true,
                    'tools' => array(
                        'cerebro' => array('enabled' => true, 'usage' => 450, 'limit' => 1000),
                        'magnet' => array('enabled' => true, 'usage' => 250, 'limit' => 500),
                        'frankenstein' => array('enabled' => true, 'usage' => 100, 'limit' => 200)
                    ),
                    'account_status' => 'active'
                ))
            );
        }

        // Default Helium10 response
        return array(
            'response' => array('code' => 200, 'message' => 'OK'),
            'body' => wp_json_encode(array(
                'success' => true,
                'message' => 'Helium10 mock response',
                'account_status' => 'active'
            ))
        );
    }

    /**
     * Get Midjourney API mock response
     *
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array Mock response
     */
    private function get_midjourney_mock_response($url, $args) {
        // Mock Discord API response
        if (strpos($url, 'discord') !== false) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    'id' => 'mock_discord_user_123',
                    'username' => 'testuser',
                    'discriminator' => '1234',
                    'avatar' => 'mock_avatar_hash',
                    'verified' => true,
                    'guilds' => array(
                        array(
                            'id' => 'midjourney_server_id',
                            'name' => 'Midjourney',
                            'permissions' => '68608'
                        )
                    )
                ))
            );
        }

        // Mock image generation response
        if (strpos($url, 'generate') !== false || strpos($url, 'imagine') !== false) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    'success' => true,
                    'job_id' => 'mock_job_' . uniqid(),
                    'status' => 'in_progress',
                    'estimated_time' => 60,
                    'queue_position' => 3,
                    'fast_mode' => true
                ))
            );
        }

        // Default Midjourney response
        return array(
            'response' => array('code' => 200, 'message' => 'OK'),
            'body' => wp_json_encode(array(
                'success' => true,
                'message' => 'Midjourney mock response',
                'status' => 'connected'
            ))
        );
    }

    /**
     * Get Freepik API mock response
     *
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array Mock response
     */
    private function get_freepik_mock_response($url, $args) {
        // Mock search response
        if (strpos($url, 'search') !== false) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    'success' => true,
                    'data' => array(
                        'total' => 1500,
                        'page' => 1,
                        'per_page' => 20,
                        'results' => array(
                            array(
                                'id' => 'mock_image_1',
                                'title' => 'Mock Test Image 1',
                                'url' => 'https://mock.freepik.com/image1.jpg',
                                'thumbnail' => 'https://mock.freepik.com/thumb1.jpg',
                                'tags' => array('test', 'mock', 'sample')
                            )
                        )
                    ),
                    'usage' => array(
                        'downloads_used' => 45,
                        'downloads_limit' => 100
                    )
                ))
            );
        }

        // Mock download response
        if (strpos($url, 'download') !== false) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    'success' => true,
                    'download_url' => 'https://mock.freepik.com/download/image123.jpg',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                    'remaining_downloads' => 55
                ))
            );
        }

        // Default Freepik response
        return array(
            'response' => array('code' => 200, 'message' => 'OK'),
            'body' => wp_json_encode(array(
                'success' => true,
                'message' => 'Freepik mock response',
                'subscription_status' => 'premium'
            ))
        );
    }

    /**
     * Get WooCommerce API mock response
     *
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array Mock response
     */
    private function get_woocommerce_mock_response($url, $args) {
        // Mock orders response
        if (strpos($url, 'orders') !== false) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    array(
                        'id' => 123,
                        'status' => 'completed',
                        'total' => '99.99',
                        'date_created' => current_time('c'),
                        'billing' => array(
                            'email' => 'customer@test.com',
                            'first_name' => 'Test',
                            'last_name' => 'Customer'
                        ),
                        'line_items' => array(
                            array(
                                'id' => 456,
                                'name' => 'VD License - Helium10',
                                'product_id' => 789,
                                'quantity' => 1,
                                'total' => '99.99'
                            )
                        )
                    )
                ))
            );
        }

        // Mock products response
        if (strpos($url, 'products') !== false) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    array(
                        'id' => 789,
                        'name' => 'VD License - Helium10',
                        'type' => 'simple',
                        'status' => 'publish',
                        'price' => '99.99',
                        'meta_data' => array(
                            array(
                                'key' => 'vd_license_provider',
                                'value' => 'helium10'
                            )
                        )
                    )
                ))
            );
        }

        // Default WooCommerce response
        return array(
            'response' => array('code' => 200, 'message' => 'OK'),
            'body' => wp_json_encode(array(
                'success' => true,
                'message' => 'WooCommerce mock response'
            ))
        );
    }

    /**
     * Get webhook mock response
     *
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array Mock response
     */
    private function get_webhook_mock_response($url, $args) {
        // Simulate different webhook response scenarios
        $success_rate = 90; // 90% success rate

        if (rand(1, 100) <= $success_rate) {
            return array(
                'response' => array('code' => 200, 'message' => 'OK'),
                'body' => wp_json_encode(array(
                    'success' => true,
                    'message' => 'Webhook received successfully',
                    'timestamp' => current_time('c')
                ))
            );
        } else {
            // Mock failure responses
            $error_codes = array(400, 500, 503, 404);
            $error_code = $error_codes[array_rand($error_codes)];

            return array(
                'response' => array('code' => $error_code, 'message' => 'Error'),
                'body' => wp_json_encode(array(
                    'success' => false,
                    'error' => 'Mock webhook error',
                    'code' => $error_code
                ))
            );
        }
    }

    /**
     * Mock WordPress database operations
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return mixed Mock database result
     */
    public function mock_database_query($query, $params = array()) {
        // Mock license validation queries
        if (strpos($query, 'wp_vd_licenses') !== false && strpos($query, 'SELECT') !== false) {
            return array(
                (object) array(
                    'id' => 123,
                    'license_key' => 'VD-MOCK123-MOCK456-MOCK789',
                    'status' => 'active',
                    'user_id' => 456,
                    'product_id' => 789,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                    'created_at' => current_time('mysql')
                )
            );
        }

        // Mock insert/update operations
        if (strpos($query, 'INSERT') !== false || strpos($query, 'UPDATE') !== false) {
            return 1; // Affected rows
        }

        // Mock delete operations
        if (strpos($query, 'DELETE') !== false) {
            return rand(0, 5); // Random affected rows
        }

        return array(); // Default empty result
    }

    /**
     * Mock WordPress user functions
     *
     * @param string $function Function name
     * @param array $args Function arguments
     * @return mixed Mock result
     */
    public function mock_wp_user_function($function, $args = array()) {
        switch ($function) {
            case 'get_current_user_id':
                return 123;

            case 'get_userdata':
                return (object) array(
                    'ID' => $args[0] ?? 123,
                    'user_login' => 'testuser',
                    'user_email' => 'test@example.com',
                    'user_registered' => current_time('mysql'),
                    'roles' => array('administrator')
                );

            case 'current_user_can':
                return true; // Mock admin permissions

            default:
                return null;
        }
    }

    /**
     * Mock WordPress option functions
     *
     * @param string $function Function name
     * @param array $args Function arguments
     * @return mixed Mock result
     */
    public function mock_wp_option_function($function, $args = array()) {
        switch ($function) {
            case 'get_option':
                $option_name = $args[0] ?? '';
                $default = $args[1] ?? false;

                // Mock VD License Manager options
                if (strpos($option_name, 'vd_') === 0) {
                    return array(
                        'enabled' => true,
                        'version' => '1.6.0',
                        'last_update' => current_time('mysql')
                    );
                }

                return $default;

            case 'update_option':
                return true; // Always successful

            case 'delete_option':
                return true; // Always successful

            default:
                return false;
        }
    }

    /**
     * Set custom mock response for specific URL
     *
     * @param string $url URL pattern
     * @param array $response Mock response
     * @return void
     */
    public function set_mock_response($url, $response) {
        $this->mock_responses[$url] = $response;
    }

    /**
     * Clear all mock responses
     *
     * @return void
     */
    public function clear_mock_responses() {
        $this->mock_responses = array();
    }

    /**
     * Get mock response for URL if set
     *
     * @param string $url Request URL
     * @return array|null Mock response or null
     */
    public function get_mock_response($url) {
        foreach ($this->mock_responses as $pattern => $response) {
            if (strpos($url, $pattern) !== false) {
                return $response;
            }
        }
        return null;
    }
}
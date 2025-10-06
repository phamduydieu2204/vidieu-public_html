<?php
/**
 * Account information retrieval for VD License Manager
 *
 * Handles secure retrieval of provider account information for licenses
 *
 * @package VD_License_Manager
 * @subpackage Core
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Account Info class
 *
 * Retrieves and formats provider account information for valid licenses
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Account_Info {

    /**
     * Get account information for license
     *
     * Main method that orchestrates all verification and data retrieval steps
     *
     * @since 1.0.0
     * @param string $license_key License key to verify
     * @param string $device_fp Device fingerprint hash
     * @return array Response with account info or error
     */
    public static function get_info($license_key, $device_fp) {
        try {
            // Step 1: Verify license
            $license_result = self::verify_license($license_key);
            if (is_wp_error($license_result)) {
                return array('ok' => false, 'error' => $license_result->get_error_message());
            }

            $license_id = $license_result['license_id'];
            $product_id = $license_result['product_id'];

            // Step 2: Verify device
            $device_result = self::verify_device($license_id, $device_fp);
            if (is_wp_error($device_result)) {
                return array('ok' => false, 'error' => $device_result->get_error_message());
            }

            // Step 3: Check rate limit
            $rate_result = self::check_rate_limit($license_id);
            if (is_wp_error($rate_result)) {
                return array('ok' => false, 'error' => $rate_result->get_error_message());
            }

            // Step 4: Get account assignment
            $provider_account_id = self::get_assignment($license_id);
            if (is_wp_error($provider_account_id)) {
                return array('ok' => false, 'error' => $provider_account_id->get_error_message());
            }

            // Step 5: Get account data
            $account_data = self::get_account_data($provider_account_id);
            if (is_wp_error($account_data)) {
                return array('ok' => false, 'error' => $account_data->get_error_message());
            }

            // Step 6: Get share configuration
            $share_config = self::get_share_config($product_id);

            // Step 7: Filter fields
            $filtered_account = self::filter_fields($account_data, $share_config['fields']);

            // Step 8: Log fetch
            self::log_fetch($license_id, $device_fp, $provider_account_id, array_keys($filtered_account));

            // Step 9: Format response
            return self::format_response($license_result, $filtered_account, $share_config['instructions']);

        } catch (Exception $e) {
            error_log('VD Account Info: Error - ' . $e->getMessage());
            return array('ok' => false, 'error' => 'Internal server error');
        }
    }

    /**
     * Verify license key validity
     *
     * @since 1.0.0
     * @param string $license_key License key to verify
     * @return array|WP_Error License data or error
     */
    private static function verify_license($license_key) {
        $result = VD_License_Verify::verify_license($license_key);

        if (!$result['ok']) {
            return new WP_Error('license_invalid', $result['error']);
        }

        return $result;
    }

    /**
     * Verify device fingerprint
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $device_fp Device fingerprint
     * @return array|WP_Error Device verification result or error
     */
    private static function verify_device($license_id, $device_fp) {
        $result = VD_License_Verify::verify_device($license_id, $device_fp);

        if (!$result['ok']) {
            return new WP_Error('device_invalid', $result['error']);
        }

        return $result;
    }

    /**
     * Check rate limit for license
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @return bool|WP_Error True if allowed or error
     */
    private static function check_rate_limit($license_id) {
        $result = VD_Rate_Limit::check($license_id);

        if (!$result['ok']) {
            return new WP_Error('rate_limit', 'Rate limit exceeded. Try again in ' . $result['retry_after'] . ' seconds');
        }

        VD_Rate_Limit::increment($license_id);
        return true;
    }

    /**
     * Get account assignment for license
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @return int|WP_Error Provider account ID or error
     */
    private static function get_assignment($license_id) {
        $account_id = VD_Assignment::get_account_for_license($license_id);

        if (!$account_id) {
            return new WP_Error('no_assignment', 'No account assigned to this license');
        }

        return $account_id;
    }

    /**
     * Get provider account data
     *
     * @since 1.0.0
     * @param int $provider_account_id Provider account ID
     * @return array|WP_Error Account data or error
     */
    private static function get_account_data($provider_account_id) {
        global $wpdb;

        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_provider_accounts WHERE account_id = %d AND is_active = 1",
            $provider_account_id
        ), ARRAY_A);

        if (!$account) {
            return new WP_Error('account_not_found', 'Provider account not found or inactive');
        }

        // Decode JSON fields
        if (!empty($account['cookie_data'])) {
            $account['cookie'] = json_decode($account['cookie_data'], true);
        }
        if (!empty($account['extra_data'])) {
            $account['extra'] = json_decode($account['extra_data'], true);
        }

        return $account;
    }

    /**
     * Get product share configuration
     *
     * @since 1.0.0
     * @param int $product_id Product ID
     * @return array Share configuration with fields and instructions
     */
    private static function get_share_config($product_id) {
        global $wpdb;

        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT share_fields, instructions FROM {$wpdb->prefix}vd_product_share_configs WHERE product_id = %d",
            $product_id
        ));

        $default_fields = array('cookie');
        $default_instructions = 'Use the provided account information as configured.';

        if (!$config) {
            return array(
                'fields' => $default_fields,
                'instructions' => $default_instructions
            );
        }

        $fields = json_decode($config->share_fields, true);
        if (!is_array($fields) || empty($fields)) {
            $fields = $default_fields;
        }

        return array(
            'fields' => $fields,
            'instructions' => $config->instructions ?: $default_instructions
        );
    }

    /**
     * Filter account data to allowed fields
     *
     * @since 1.0.0
     * @param array $account_data Full account data
     * @param array $allowed_fields Allowed field names
     * @return array Filtered account data
     */
    private static function filter_fields($account_data, $allowed_fields) {
        $filtered = array();

        foreach ($allowed_fields as $field) {
            $field = sanitize_key($field);
            if (isset($account_data[$field])) {
                $filtered[$field] = $account_data[$field];
            }
        }

        return $filtered;
    }

    /**
     * Log account fetch attempt
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $device_fp Device fingerprint
     * @param int $provider_account_id Provider account ID
     * @param array $fields Fields accessed
     * @return bool True on success
     */
    private static function log_fetch($license_id, $device_fp, $provider_account_id, $fields) {
        global $wpdb;

        $ip = VD_Security::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        return $wpdb->insert(
            $wpdb->prefix . 'vd_account_fetch_log',
            array(
                'license_id' => $license_id,
                'provider_account_id' => $provider_account_id,
                'device_fp' => $device_fp,
                'fields_accessed' => wp_json_encode($fields),
                'ip_address' => $ip,
                'user_agent' => $user_agent,
                'status' => 'success',
                'accessed_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        ) !== false;
    }

    /**
     * Format final response
     *
     * @since 1.0.0
     * @param array $license_data License verification data
     * @param array $account_info Filtered account information
     * @param string $instructions Usage instructions
     * @return array Formatted response
     */
    private static function format_response($license_data, $account_info, $instructions) {
        return array(
            'ok' => true,
            'license' => array(
                'license_id' => $license_data['license_id'],
                'product_id' => $license_data['product_id'],
                'expires_at' => $license_data['expires_at'],
                'days_remaining' => $license_data['days_remaining']
            ),
            'account_info' => $account_info,
            'instructions' => sanitize_text_field($instructions),
            'timestamp' => current_time('c')
        );
    }
}
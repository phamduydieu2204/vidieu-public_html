<?php
/**
 * Email Handler Class
 *
 * Handles sending email notifications to customers after license assignment
 * including account credentials, HTML/plain text templates, and delivery tracking.
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * VD License Manager Email Handler
 *
 * Manages email delivery for license credential notifications
 * including template loading, variable substitution, and delivery tracking.
 */
class VD_LM_Email_Handler {

    /**
     * Send license key email to customer
     *
     * Sends simple email with license key and portal link only.
     * Customer accesses portal to view account credentials.
     *
     * @since 1.0.0
     * @param array $email_data {
     *     Email data for template variables
     *
     *     @type string $customer_name Customer's full name
     *     @type string $customer_email Customer's email address
     *     @type string $product_name Product name
     *     @type string $license_key License key from LMfWC
     *     @type int    $max_devices Maximum devices allowed
     *     @type int    $validity_days License validity in days
     *     @type string $expiry_date License expiration date (formatted)
     *     @type string $portal_url License portal URL
     *     @type string $order_id WooCommerce order ID
     *     @type string $site_name WordPress site name
     *     @type string $site_url WordPress site URL
     * }
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function send_credentials_email($email_data) {
        // Validate required email data (NO PASSWORD FIELDS!)
        $required_fields = [
            'customer_name', 'customer_email', 'product_name', 'license_key'
        ];

        foreach ($required_fields as $field) {
            if (empty($email_data[$field])) {
                return new WP_Error(
                    'missing_email_field',
                    sprintf(__('Missing required email field: %s', 'vd-license-manager'), $field)
                );
            }
        }

        // Decrypt license key from LMfWC before using in email
        $decrypted_license_key = $email_data['license_key'];

        // LMfWC stores keys encrypted - try to decrypt
        if (function_exists('lmfwc_decrypt')) {
            $decrypted_license_key = lmfwc_decrypt($email_data['license_key']);
            error_log('VD Email: Decrypted license key using lmfwc_decrypt()');
        } elseif (class_exists('LicenseManagerForWooCommerce\Crypto')) {
            try {
                $crypto = new \LicenseManagerForWooCommerce\Crypto();
                $decrypted_license_key = $crypto->decrypt($email_data['license_key']);
                error_log('VD Email: Decrypted license key using Crypto class');
            } catch (Exception $e) {
                error_log('VD Email: Failed to decrypt license key: ' . $e->getMessage());
            }
        }

        // If still encrypted (starts with 'def'), log warning
        if (substr($decrypted_license_key, 0, 3) === 'def') {
            error_log('VD Email: WARNING - License key still appears encrypted: ' . substr($decrypted_license_key, 0, 20) . '...');
        } else {
            error_log('VD Email: License key decrypted successfully: ' . $decrypted_license_key);
        }

        // Update email data with decrypted license key
        $email_data['license_key'] = $decrypted_license_key;

        // Set default values for optional fields
        $email_data = wp_parse_args($email_data, [
            'max_devices' => 1,
            'validity_days' => 0,
            'expiry_date' => 'Trọn đời',
            'portal_url' => home_url('/license-portal/'),
            'order_id' => 'N/A',
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url()
        ]);

        // Load HTML email template
        $html_content = $this->load_email_template('license-credentials', $email_data);
        if (is_wp_error($html_content)) {
            return $html_content;
        }

        // Load plain text email template
        $plain_content = $this->load_email_template('license-credentials-plain', $email_data);
        if (is_wp_error($plain_content)) {
            // If plain text fails, use fallback
            $plain_content = $this->generate_plain_text_fallback($email_data);
        }

        // Prepare email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $email_data['site_name'] . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . get_option('admin_email')
        ];

        // Email subject
        $subject = sprintf(
            '[%s] 🔑 License Key - %s - Đơn #%s',
            $email_data['site_name'],
            $email_data['product_name'],
            $email_data['order_id']
        );

        // Send email
        $email_sent = wp_mail(
            $email_data['customer_email'],
            $subject,
            $html_content,
            $headers
        );

        // Log email attempt
        $this->log_email_attempt($email_data, $email_sent);

        // Track email in database if sent successfully
        if ($email_sent) {
            $this->track_email_delivery($email_data['license_key']);

            // Add order note if order ID is provided
            if (!empty($email_data['order_id']) && $email_data['order_id'] !== 'N/A') {
                $this->add_order_note($email_data['order_id'], $email_data);
            }

            return true;
        } else {
            return new WP_Error(
                'email_send_failed',
                __('Failed to send credentials email', 'vd-license-manager')
            );
        }
    }

    /**
     * Load email template with variable substitution
     *
     * @since 1.0.0
     * @param string $template_name Template filename without .php extension
     * @param array  $variables Template variables for substitution
     * @return string|WP_Error Template content on success, WP_Error on failure
     */
    private function load_email_template($template_name, $variables) {
        $template_path = VD_PLUGIN_DIR . 'admin/email-templates/' . $template_name . '.php';

        if (!file_exists($template_path)) {
            return new WP_Error(
                'template_not_found',
                sprintf(__('Email template not found: %s', 'vd-license-manager'), $template_name)
            );
        }

        // Extract variables for use in template
        extract($variables, EXTR_SKIP);

        // Capture template output
        ob_start();
        include $template_path;
        $content = ob_get_clean();

        if (empty($content)) {
            return new WP_Error(
                'template_empty',
                sprintf(__('Email template is empty: %s', 'vd-license-manager'), $template_name)
            );
        }

        return $content;
    }

    /**
     * Generate plain text fallback content
     *
     * @since 1.0.0
     * @param array $email_data Email data
     * @return string Plain text content
     */
    private function generate_plain_text_fallback($email_data) {
        $content = sprintf(
            __("Hi %s,\n\nYour %s account is ready!\n\nCredentials:\nLicense Key: %s\nAccount Login: %s\nAccount Password: %s\n\nBest regards,\n%s", 'vd-license-manager'),
            $email_data['customer_name'],
            $email_data['product_name'],
            $email_data['license_key'],
            $email_data['account_login'],
            $email_data['account_password'],
            $email_data['site_name']
        );

        return $content;
    }

    /**
     * Log email delivery attempt
     *
     * @since 1.0.0
     * @param array $email_data Email data
     * @param bool  $success Whether email was sent successfully
     */
    private function log_email_attempt($email_data, $success) {
        $log_message = sprintf(
            'License credentials email %s for license %s to %s',
            $success ? 'sent successfully' : 'failed to send',
            $email_data['license_key'],
            $email_data['customer_email']
        );

        $log_context = [
            'license_key' => $email_data['license_key'],
            'customer_email' => $email_data['customer_email'],
            'product_name' => $email_data['product_name'],
            'order_id' => $email_data['order_id'],
            'success' => $success
        ];

        if ($success) {
            error_log('[VD_LM] ' . $log_message . ' - Context: ' . wp_json_encode($log_context));
        } else {
            error_log('[VD_LM] ERROR: ' . $log_message . ' - Context: ' . wp_json_encode($log_context));
        }
    }

    /**
     * Track email delivery in database
     *
     * Updates the license record with email sent timestamp
     *
     * @since 1.0.0
     * @param string $license_key License key to update
     */
    private function track_email_delivery($license_key) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_license_keys';

        $result = $wpdb->update(
            $table_name,
            ['email_sent_at' => current_time('mysql')],
            ['license_key' => $license_key],
            ['%s'],
            ['%s']
        );

        if ($result === false) {
            error_log('[VD_LM] Failed to update email_sent_at for license: ' . $license_key);
        }
    }

    /**
     * Add order note about email delivery
     *
     * @since 1.0.0
     * @param string $order_id WooCommerce order ID
     * @param array  $email_data Email data
     */
    private function add_order_note($order_id, $email_data) {
        if (!function_exists('wc_get_order')) {
            return; // WooCommerce not available
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return; // Order not found
        }

        $note = sprintf(
            __('License credentials email sent to %s for license %s (%s)', 'vd-license-manager'),
            $email_data['customer_email'],
            $email_data['license_key'],
            $email_data['product_name']
        );

        $order->add_order_note($note);
    }

    /**
     * Send test email (for admin testing)
     *
     * @since 1.0.0
     * @param string $test_email Email address to send test to
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function send_test_email($test_email) {
        if (!is_email($test_email)) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address provided', 'vd-license-manager')
            );
        }

        $test_data = [
            'customer_name' => 'Khách Hàng Test',
            'customer_email' => $test_email,
            'product_name' => 'Sản Phẩm Test',
            'license_key' => 'TEST-1234-5678-9012',
            'max_devices' => 3,
            'validity_days' => 365,
            'expiry_date' => date('d/m/Y', strtotime('+365 days')),
            'portal_url' => home_url('/license-portal/'),
            'order_id' => 'TEST-001',
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url()
        ];

        return $this->send_credentials_email($test_data);
    }

    /**
     * Send license renewal reminder email
     *
     * @since 1.0.0
     * @param int $license_id License ID from bz_vd_license_keys
     * @return bool True on success, false on failure
     */
    public static function send_renewal_reminder($license_id) {
        error_log('VD Email: === SENDING RENEWAL REMINDER ===');
        error_log('VD Email: License ID: ' . $license_id);

        try {
            global $wpdb;

            // Get license data
            $license = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vd_license_keys WHERE id = %d",
                $license_id
            ), ARRAY_A);

            if (!$license) {
                error_log('VD Email: License not found: ' . $license_id);
                return false;
            }

            // Check expiry status
            $expires_at = strtotime($license['expires_at']);
            $now = current_time('timestamp');

            // Allow sending reminders for expired licenses (not days remaining)
            $is_expired = $expires_at <= $now;

            if ($is_expired) {
                error_log('VD Email: License expired, sending expiry notification');
            } else {
                error_log('VD Email: License not yet expired, skipping reminder');
                return false;
            }

            // Get product
            $product = wc_get_product($license['product_id']);
            if (!$product) {
                error_log('VD Email: Product not found: ' . $license['product_id']);
                return false;
            }

            // Get customer info
            $customer_email = $license['customer_email'];

            // Try to get customer name from order
            $customer_name = 'Quý khách';
            if ($license['order_id']) {
                $order = wc_get_order($license['order_id']);
                if ($order) {
                    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                }
            }

            // Decrypt license key from LMfWC
            $decrypted_license_key = $license['license_key'];

            // LMfWC stores keys encrypted - try to decrypt
            if (function_exists('lmfwc_decrypt')) {
                $decrypted_license_key = lmfwc_decrypt($license['license_key']);
                error_log('VD Email (Renewal): Decrypted license key using lmfwc_decrypt()');
            } elseif (class_exists('LicenseManagerForWooCommerce\Crypto')) {
                try {
                    $crypto = new \LicenseManagerForWooCommerce\Crypto();
                    $decrypted_license_key = $crypto->decrypt($license['license_key']);
                    error_log('VD Email (Renewal): Decrypted license key using Crypto class');
                } catch (Exception $e) {
                    error_log('VD Email (Renewal): Failed to decrypt license key: ' . $e->getMessage());
                }
            }

            // If still encrypted (starts with 'def'), log warning
            if (substr($decrypted_license_key, 0, 3) === 'def') {
                error_log('VD Email (Renewal): WARNING - License key still appears encrypted: ' . substr($decrypted_license_key, 0, 20) . '...');
            } else {
                error_log('VD Email (Renewal): License key decrypted successfully: ' . $decrypted_license_key);
            }

            // Prepare template variables
            $template_vars = array(
                'customer_name' => trim($customer_name),
                'product_name' => $product->get_name(),
                'license_key' => $decrypted_license_key,
                'expiry_date' => date_i18n('d/m/Y', $expires_at),
                'renewal_url' => $product->get_permalink(),
                'portal_url' => home_url('/license-portal/'),
                'site_name' => get_bloginfo('name'),
                'site_url' => home_url()
            );

            error_log('VD Email: Renewal reminder vars prepared for: ' . $customer_email);

            // Get email content
            $html_content = self::get_email_template('license-renewal-reminder', $template_vars);

            // Email headers
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
            );

            // Subject
            $subject = sprintf(
                '[%s] ⏰ License %s đã hết hạn - Cần gia hạn',
                get_bloginfo('name'),
                $product->get_name()
            );

            error_log('VD Email: Sending renewal reminder to: ' . $customer_email);
            error_log('VD Email: Subject: ' . $subject);

            // Send email
            $sent = wp_mail($customer_email, $subject, $html_content, $headers);

            if ($sent) {
                error_log('VD Email: ✅ Renewal reminder sent successfully to ' . $customer_email);

                // Record email sent
                $wpdb->update(
                    $wpdb->prefix . 'vd_license_keys',
                    array(
                        'renewal_reminder_sent_at' => current_time('mysql')
                    ),
                    array('id' => $license_id),
                    array('%s'),
                    array('%d')
                );

                return true;
            } else {
                error_log('VD Email: ❌ Failed to send renewal reminder to ' . $customer_email);
                return false;
            }

        } catch (Exception $e) {
            error_log('VD Email: Exception in renewal reminder: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email template content
     *
     * @since 1.0.0
     * @param string $template_name Template name without .php extension
     * @param array $variables Template variables
     * @return string Template content
     */
    private static function get_email_template($template_name, $variables) {
        $template_path = VD_PLUGIN_DIR . 'admin/email-templates/' . $template_name . '.php';

        if (!file_exists($template_path)) {
            error_log('VD Email: Template not found: ' . $template_path);
            return 'Email template not found.';
        }

        // Extract variables for use in template
        extract($variables, EXTR_SKIP);

        // Capture template output
        ob_start();
        include $template_path;
        $content = ob_get_clean();

        return $content;
    }
}
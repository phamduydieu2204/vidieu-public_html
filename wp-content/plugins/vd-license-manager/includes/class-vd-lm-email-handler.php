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
     * Send license credentials email to customer
     *
     * Loads email templates, substitutes variables, and sends both HTML
     * and plain text versions of the credentials email.
     *
     * @since 1.0.0
     * @param array $email_data {
     *     Email data for template variables
     *
     *     @type string $customer_name Customer's full name
     *     @type string $customer_email Customer's email address
     *     @type string $product_name Product name
     *     @type string $license_key License key from LMfWC
     *     @type string $account_login Account username/email
     *     @type string $account_password Decrypted account password
     *     @type int    $max_devices Maximum devices allowed
     *     @type int    $validity_days License validity in days
     *     @type string $expiry_date License expiration date (formatted)
     *     @type int    $max_requests_per_day API rate limit
     *     @type string $order_id WooCommerce order ID
     *     @type string $site_name WordPress site name
     *     @type string $site_url WordPress site URL
     * }
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function send_credentials_email($email_data) {
        // Validate required email data
        $required_fields = [
            'customer_name', 'customer_email', 'product_name',
            'license_key', 'account_login', 'account_password'
        ];

        foreach ($required_fields as $field) {
            if (empty($email_data[$field])) {
                return new WP_Error(
                    'missing_email_field',
                    sprintf(__('Missing required email field: %s', 'vd-license-manager'), $field)
                );
            }
        }

        // Set default values for optional fields
        $email_data = wp_parse_args($email_data, [
            'max_devices' => 1,
            'validity_days' => 0,
            'expiry_date' => __('Lifetime', 'vd-license-manager'),
            'max_requests_per_day' => 1000,
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
            __('Your %s Account Credentials - Order #%s', 'vd-license-manager'),
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
            'customer_name' => 'Test Customer',
            'customer_email' => $test_email,
            'product_name' => 'Test Product',
            'license_key' => 'TEST-1234-5678-9012',
            'account_login' => 'test@example.com',
            'account_password' => 'TestPassword123',
            'max_devices' => 3,
            'validity_days' => 365,
            'expiry_date' => date('F j, Y', strtotime('+365 days')),
            'max_requests_per_day' => 1000,
            'order_id' => 'TEST-001',
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url()
        ];

        return $this->send_credentials_email($test_data);
    }
}
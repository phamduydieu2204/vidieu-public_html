<?php
/**
 * Notification Service
 *
 * Handles email notifications and other communication methods for the plugin.
 * Manages license delivery emails, admin notifications, and system alerts.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Notification Service Class
 *
 * Provides email notification functionality for various plugin events.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Notification_Service {

    /**
     * Email templates directory
     *
     * @since  1.0.0
     * @access private
     * @var    string $templates_dir Path to email templates
     */
    private static $templates_dir = null;

    /**
     * Initialize the service
     *
     * @since 1.0.0
     */
    public static function init() {
        self::$templates_dir = VD_PLUGIN_DIR . 'templates/emails/';
    }

    /**
     * Send license credentials to customer
     *
     * @since 1.0.0
     * @param array $license_data {
     *     License and account data
     *
     *     @type string $license_key    License key
     *     @type string $customer_email Customer email
     *     @type string $customer_name  Customer name
     *     @type array  $credentials    Account credentials
     *     @type string $product_name   Product name
     *     @type string $expires_at     Expiration date
     * }
     * @return bool True on success, false on failure
     */
    public static function send_license_credentials( $license_data ) {
        try {
            $to = $license_data['customer_email'];
            $subject = sprintf(
                __( 'Your %s License Credentials', 'vd-license-manager' ),
                $license_data['product_name']
            );

            // Load email template
            $template_content = self::load_template( 'license-delivery', $license_data );

            if ( ! $template_content ) {
                VD_LM_Logger_Service::error( 'Failed to load license delivery email template' );
                return false;
            }

            // Prepare email headers
            $headers = self::get_email_headers();

            // Send email
            $sent = wp_mail( $to, $subject, $template_content, $headers );

            if ( $sent ) {
                VD_LM_Logger_Service::info( 'License credentials email sent', array(
                    'license_key' => $license_data['license_key'],
                    'customer_email' => $to,
                ) );
            } else {
                VD_LM_Logger_Service::error( 'Failed to send license credentials email', array(
                    'license_key' => $license_data['license_key'],
                    'customer_email' => $to,
                ) );
            }

            return $sent;

        } catch ( Exception $e ) {
            VD_LM_Logger_Service::error( 'Exception sending license credentials email', array(
                'error' => $e->getMessage(),
                'license_key' => isset( $license_data['license_key'] ) ? $license_data['license_key'] : 'unknown',
            ) );
            return false;
        }
    }

    /**
     * Send pool capacity warning to admin
     *
     * @since 1.0.0
     * @param array $pool_data {
     *     Pool data
     *
     *     @type int    $pool_id        Pool ID
     *     @type string $pool_name      Pool name
     *     @type int    $capacity       Total capacity
     *     @type int    $assigned_count Current assigned count
     *     @type string $product_name   Product name
     * }
     * @return bool True on success, false on failure
     */
    public static function send_pool_capacity_warning( $pool_data ) {
        try {
            $admin_email = get_option( 'admin_email' );
            $subject = sprintf(
                __( 'Pool Capacity Warning: %s', 'vd-license-manager' ),
                $pool_data['pool_name']
            );

            // Calculate usage percentage
            $usage_percentage = round( ( $pool_data['assigned_count'] / $pool_data['capacity'] ) * 100, 2 );

            $pool_data['usage_percentage'] = $usage_percentage;

            // Load email template
            $template_content = self::load_template( 'pool-capacity-warning', $pool_data );

            if ( ! $template_content ) {
                VD_LM_Logger_Service::error( 'Failed to load pool capacity warning email template' );
                return false;
            }

            // Prepare email headers
            $headers = self::get_email_headers();

            // Send email
            $sent = wp_mail( $admin_email, $subject, $template_content, $headers );

            if ( $sent ) {
                VD_LM_Logger_Service::info( 'Pool capacity warning email sent', array(
                    'pool_id' => $pool_data['pool_id'],
                    'usage_percentage' => $usage_percentage,
                ) );
            } else {
                VD_LM_Logger_Service::error( 'Failed to send pool capacity warning email', array(
                    'pool_id' => $pool_data['pool_id'],
                ) );
            }

            return $sent;

        } catch ( Exception $e ) {
            VD_LM_Logger_Service::error( 'Exception sending pool capacity warning email', array(
                'error' => $e->getMessage(),
                'pool_id' => isset( $pool_data['pool_id'] ) ? $pool_data['pool_id'] : 'unknown',
            ) );
            return false;
        }
    }

    /**
     * Send account expiring notification to admin
     *
     * @since 1.0.0
     * @param array $account_data {
     *     Account data
     *
     *     @type int    $account_id      Account ID
     *     @type string $account_login   Account login
     *     @type string $provider        Provider name
     *     @type string $expires_at      Expiration date
     *     @type int    $days_remaining  Days until expiration
     * }
     * @return bool True on success, false on failure
     */
    public static function send_account_expiring_notification( $account_data ) {
        try {
            $admin_email = get_option( 'admin_email' );
            $subject = sprintf(
                __( 'Account Expiring Soon: %s (%s)', 'vd-license-manager' ),
                $account_data['account_login'],
                $account_data['provider']
            );

            // Load email template
            $template_content = self::load_template( 'account-expiring', $account_data );

            if ( ! $template_content ) {
                VD_LM_Logger_Service::error( 'Failed to load account expiring email template' );
                return false;
            }

            // Prepare email headers
            $headers = self::get_email_headers();

            // Send email
            $sent = wp_mail( $admin_email, $subject, $template_content, $headers );

            if ( $sent ) {
                VD_LM_Logger_Service::info( 'Account expiring notification sent', array(
                    'account_id' => $account_data['account_id'],
                    'days_remaining' => $account_data['days_remaining'],
                ) );
            } else {
                VD_LM_Logger_Service::error( 'Failed to send account expiring notification', array(
                    'account_id' => $account_data['account_id'],
                ) );
            }

            return $sent;

        } catch ( Exception $e ) {
            VD_LM_Logger_Service::error( 'Exception sending account expiring notification', array(
                'error' => $e->getMessage(),
                'account_id' => isset( $account_data['account_id'] ) ? $account_data['account_id'] : 'unknown',
            ) );
            return false;
        }
    }

    /**
     * Load email template
     *
     * @since  1.0.0
     * @access private
     * @param  string $template_name Template name (without .php extension)
     * @param  array  $data         Data to pass to template
     * @return string|false         Template content or false on failure
     */
    private static function load_template( $template_name, $data = array() ) {
        $template_file = self::$templates_dir . $template_name . '.php';

        // Check if template file exists
        if ( ! file_exists( $template_file ) ) {
            // Create a simple fallback template
            return self::create_fallback_template( $template_name, $data );
        }

        // Extract data to variables
        extract( $data );

        // Start output buffering
        ob_start();

        // Include template file
        include $template_file;

        // Get the content
        $content = ob_get_clean();

        return $content;
    }

    /**
     * Create fallback template when template file doesn't exist
     *
     * @since  1.0.0
     * @access private
     * @param  string $template_name Template name
     * @param  array  $data         Template data
     * @return string               Simple HTML template
     */
    private static function create_fallback_template( $template_name, $data ) {
        $site_name = get_bloginfo( 'name' );
        $site_url = home_url();

        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $html .= '<h2>VD License Manager</h2>';

        switch ( $template_name ) {
            case 'license-delivery':
                $html .= '<h3>' . esc_html__( 'Your License Credentials', 'vd-license-manager' ) . '</h3>';
                $html .= '<p>' . sprintf( __( 'Hello %s,', 'vd-license-manager' ), esc_html( $data['customer_name'] ) ) . '</p>';
                $html .= '<p>' . esc_html__( 'Your license key:', 'vd-license-manager' ) . ' <strong>' . esc_html( $data['license_key'] ) . '</strong></p>';
                $html .= '<p>' . esc_html__( 'Product:', 'vd-license-manager' ) . ' ' . esc_html( $data['product_name'] ) . '</p>';

                if ( ! empty( $data['credentials'] ) ) {
                    $html .= '<h4>' . esc_html__( 'Account Credentials:', 'vd-license-manager' ) . '</h4>';
                    $html .= '<ul>';
                    foreach ( $data['credentials'] as $key => $value ) {
                        $html .= '<li><strong>' . esc_html( ucfirst( $key ) ) . ':</strong> ' . esc_html( $value ) . '</li>';
                    }
                    $html .= '</ul>';
                }
                break;

            case 'pool-capacity-warning':
                $html .= '<h3>' . esc_html__( 'Pool Capacity Warning', 'vd-license-manager' ) . '</h3>';
                $html .= '<p>' . sprintf( __( 'Pool "%s" is at %s%% capacity.', 'vd-license-manager' ),
                    esc_html( $data['pool_name'] ),
                    esc_html( $data['usage_percentage'] )
                ) . '</p>';
                $html .= '<p>' . sprintf( __( 'Current usage: %d of %d accounts assigned.', 'vd-license-manager' ),
                    $data['assigned_count'],
                    $data['capacity']
                ) . '</p>';
                break;

            case 'account-expiring':
                $html .= '<h3>' . esc_html__( 'Account Expiring Soon', 'vd-license-manager' ) . '</h3>';
                $html .= '<p>' . sprintf( __( 'Account "%s" for provider "%s" will expire in %d days.', 'vd-license-manager' ),
                    esc_html( $data['account_login'] ),
                    esc_html( $data['provider'] ),
                    $data['days_remaining']
                ) . '</p>';
                $html .= '<p>' . sprintf( __( 'Expiration date: %s', 'vd-license-manager' ), esc_html( $data['expires_at'] ) ) . '</p>';
                break;
        }

        $html .= '<hr>';
        $html .= '<p><small>' . sprintf( __( 'This email was sent by %s', 'vd-license-manager' ), '<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a>' ) . '</small></p>';
        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Get email headers
     *
     * @since  1.0.0
     * @access private
     * @return array Email headers
     */
    private static function get_email_headers() {
        $from_email = defined( 'WPMS_MAIL_FROM' ) ? WPMS_MAIL_FROM : get_option( 'admin_email' );
        $from_name = defined( 'WPMS_MAIL_FROM_NAME' ) ? WPMS_MAIL_FROM_NAME : get_bloginfo( 'name' );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );

        return $headers;
    }

    /**
     * Test email configuration
     *
     * @since 1.0.0
     * @param string $test_email Email address to send test to
     * @return bool True on success, false on failure
     */
    public static function test_email_configuration( $test_email = null ) {
        if ( empty( $test_email ) ) {
            $test_email = get_option( 'admin_email' );
        }

        $subject = __( 'VD License Manager - Email Test', 'vd-license-manager' );
        $message = __( 'This is a test email from VD License Manager plugin. If you received this email, the email configuration is working correctly.', 'vd-license-manager' );

        $headers = self::get_email_headers();

        $sent = wp_mail( $test_email, $subject, $message, $headers );

        if ( $sent ) {
            VD_LM_Logger_Service::info( 'Test email sent successfully', array(
                'test_email' => $test_email,
            ) );
        } else {
            VD_LM_Logger_Service::error( 'Failed to send test email', array(
                'test_email' => $test_email,
            ) );
        }

        return $sent;
    }

    /**
     * Check if email configuration is valid
     *
     * @since 1.0.0
     * @return bool True if configuration appears valid
     */
    public static function is_email_configured() {
        // Check if from email is configured
        $from_email = defined( 'WPMS_MAIL_FROM' ) ? WPMS_MAIL_FROM : get_option( 'admin_email' );

        if ( empty( $from_email ) || ! is_email( $from_email ) ) {
            return false;
        }

        // Basic configuration appears valid
        return true;
    }
}

// Initialize the service
VD_LM_Notification_Service::init();
<?php
/**
 * Email templates and sending for VD License Manager
 *
 * Handles email notifications for various system events
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
 * VD Emails class
 *
 * Manages email notifications and templates
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Emails {

    /**
     * Send email with HTML template
     *
     * @since 1.0.0
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $message Email message
     * @param array $headers Email headers
     * @return bool Success status
     */
    public static function send_email($to, $subject, $message, $headers = array()) {
        // Get admin email settings
        $from_email = get_option('admin_email');
        $from_name = get_bloginfo('name');

        // Default headers
        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $from_name, $from_email)
        );

        $headers = array_merge($default_headers, $headers);

        try {
            $result = wp_mail($to, $subject, $message, $headers);

            if ($result) {
                VD_Logger::log(sprintf('Email sent successfully to: %s, Subject: %s',
                                     is_array($to) ? implode(', ', $to) : $to,
                                     $subject), 'info');
            } else {
                VD_Logger::log(sprintf('Email failed to send to: %s, Subject: %s',
                                     is_array($to) ? implode(', ', $to) : $to,
                                     $subject), 'error');
            }

            return $result;

        } catch (Exception $e) {
            VD_Logger::log('Email sending exception: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Notify admin of device rotation request
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param array $device_info Device information
     * @return bool Success status
     */
    public static function notify_device_rotation_request($license_id, $device_info) {
        global $wpdb;

        // Get license information
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lmfwc_licenses WHERE id = %d",
            $license_id
        ), ARRAY_A);

        if (!$license) {
            VD_Logger::log("Email: License {$license_id} not found for rotation notification", 'error');
            return false;
        }

        $admin_email = get_option('vd_admin_email', get_option('admin_email'));
        $license_key = substr($license['license_key'], 0, 20) . '...';

        $subject = sprintf(__('[%s] Device Rotation Request - License %s', 'vd-license-manager'),
                          get_bloginfo('name'),
                          $license_key);

        $template_data = array(
            'title' => __('Device Rotation Request', 'vd-license-manager'),
            'license_key' => $license['license_key'],
            'license_key_short' => $license_key,
            'current_device' => $device_info['current'] ?? array(),
            'new_device' => $device_info['new'] ?? array(),
            'reason' => $device_info['reason'] ?? __('User requested rotation', 'vd-license-manager'),
            'requested_at' => current_time('mysql'),
            'admin_link' => admin_url('admin.php?page=vd-devices&license_id=' . $license_id)
        );

        $message = self::get_email_template('device_rotation', $template_data);

        return self::send_email($admin_email, $subject, $message);
    }

    /**
     * Notify admin of expiring account
     *
     * @since 1.0.0
     * @param int $account_id Account ID
     * @param int $days_remaining Days until expiration
     * @return bool Success status
     */
    public static function notify_account_expiring($account_id, $days_remaining) {
        global $wpdb;

        // Get account information
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_provider_accounts WHERE account_id = %d",
            $account_id
        ), ARRAY_A);

        if (!$account) {
            VD_Logger::log("Email: Account {$account_id} not found for expiry notification", 'error');
            return false;
        }

        // Get active licenses count
        $active_licenses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_cookie_assignments
             WHERE provider_account_id = %d AND status = 'active'",
            $account_id
        ));

        $admin_email = get_option('vd_admin_email', get_option('admin_email'));

        $subject = sprintf(__('[%s] Account Expiring in %d Days - %s', 'vd-license-manager'),
                          get_bloginfo('name'),
                          $days_remaining,
                          $account['name']);

        $template_data = array(
            'title' => sprintf(__('Account Expiring in %d Days', 'vd-license-manager'), $days_remaining),
            'account_name' => $account['name'],
            'provider' => ucfirst($account['provider']),
            'account_login' => $account['account_login'],
            'expires_at' => $account['expires_at'],
            'expires_formatted' => VD_DateTime::format_datetime($account['expires_at']),
            'days_remaining' => $days_remaining,
            'active_licenses' => $active_licenses,
            'capacity' => $account['capacity'],
            'admin_link' => admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $account_id)
        );

        $message = self::get_email_template('account_expiring', $template_data);

        return self::send_email($admin_email, $subject, $message);
    }

    /**
     * Notify admin of high pool capacity
     *
     * @since 1.0.0
     * @param int $pool_id Pool ID
     * @param float $usage_percent Usage percentage
     * @return bool Success status
     */
    public static function notify_capacity_high($pool_id, $usage_percent) {
        global $wpdb;

        // Get pool information with product details
        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT pp.*, p.post_title as product_name
             FROM {$wpdb->prefix}vd_product_pools pp
             LEFT JOIN {$wpdb->prefix}posts p ON pp.product_id = p.ID
             WHERE pp.pool_id = %d",
            $pool_id
        ), ARRAY_A);

        if (!$pool) {
            VD_Logger::log("Email: Pool {$pool_id} not found for capacity notification", 'error');
            return false;
        }

        // Get detailed capacity information
        $capacity_info = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(ca.assignment_id) as active_assignments,
                SUM(pa.capacity) as total_capacity,
                SUM(pa.capacity) - COUNT(ca.assignment_id) as available_slots
             FROM {$wpdb->prefix}vd_pool_accounts poa
             LEFT JOIN {$wpdb->prefix}vd_provider_accounts pa ON poa.provider_account_id = pa.account_id AND pa.is_active = 1
             LEFT JOIN {$wpdb->prefix}vd_cookie_assignments ca ON pa.account_id = ca.provider_account_id AND ca.status = 'active'
             WHERE poa.pool_id = %d AND poa.is_active = 1",
            $pool_id
        ), ARRAY_A);

        $admin_email = get_option('vd_admin_email', get_option('admin_email'));

        $subject = sprintf(__('[%s] Pool Capacity High (%d%%) - %s', 'vd-license-manager'),
                          get_bloginfo('name'),
                          round($usage_percent),
                          $pool['name']);

        $template_data = array(
            'title' => sprintf(__('Pool Capacity High (%d%%)', 'vd-license-manager'), round($usage_percent)),
            'pool_name' => $pool['name'],
            'product_name' => $pool['product_name'] ?? __('Unknown Product', 'vd-license-manager'),
            'usage_percent' => round($usage_percent, 1),
            'active_assignments' => $capacity_info['active_assignments'] ?? 0,
            'total_capacity' => $capacity_info['total_capacity'] ?? 0,
            'available_slots' => $capacity_info['available_slots'] ?? 0,
            'strategy' => ucfirst($pool['strategy'] ?? 'unknown'),
            'admin_link' => admin_url('admin.php?page=vd-product-pools&action=edit&id=' . $pool_id)
        );

        $message = self::get_email_template('capacity_high', $template_data);

        return self::send_email($admin_email, $subject, $message);
    }

    /**
     * Get HTML email template
     *
     * @since 1.0.0
     * @param string $type Template type
     * @param array $data Template data
     * @return string HTML email content
     */
    private static function get_email_template($type, $data) {
        $base_template = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{title}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background: #0073aa; color: #fff; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        .button { display: inline-block; background: #0073aa; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 10px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table th, .info-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .info-table th { background: #f8f9fa; font-weight: bold; }
        .alert { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .status-high { color: #dc3545; font-weight: bold; }
        .status-medium { color: #fd7e14; font-weight: bold; }
        .status-low { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{title}</h1>
        </div>
        <div class="content">
            {content}
        </div>
        <div class="footer">
            <p>This is an automated notification from {site_name}</p>
            <p>VD License Manager Plugin</p>
        </div>
    </div>
</body>
</html>';

        $content = '';

        switch ($type) {
            case 'device_rotation':
                $content = '
                    <div class="alert">
                        <strong>Device rotation request received</strong><br>
                        A customer has requested to rotate their device for license access.
                    </div>

                    <table class="info-table">
                        <tr><th>License Key</th><td>' . esc_html($data['license_key']) . '</td></tr>
                        <tr><th>Requested At</th><td>' . esc_html(VD_DateTime::format_datetime($data['requested_at'])) . '</td></tr>
                        <tr><th>Reason</th><td>' . esc_html($data['reason']) . '</td></tr>
                    </table>

                    <h3>Current Device</h3>
                    <table class="info-table">
                        <tr><th>OS</th><td>' . esc_html($data['current_device']['os'] ?? 'Unknown') . '</td></tr>
                        <tr><th>Browser</th><td>' . esc_html($data['current_device']['browser'] ?? 'Unknown') . '</td></tr>
                        <tr><th>Last Used</th><td>' . esc_html($data['current_device']['last_used'] ?? 'Unknown') . '</td></tr>
                    </table>

                    <h3>New Device Requested</h3>
                    <table class="info-table">
                        <tr><th>OS</th><td>' . esc_html($data['new_device']['os'] ?? 'Unknown') . '</td></tr>
                        <tr><th>Browser</th><td>' . esc_html($data['new_device']['browser'] ?? 'Unknown') . '</td></tr>
                    </table>

                    <p><a href="' . esc_url($data['admin_link']) . '" class="button">Review in Admin</a></p>';
                break;

            case 'account_expiring':
                $status_class = $data['days_remaining'] <= 3 ? 'status-high' : ($data['days_remaining'] <= 7 ? 'status-medium' : 'status-low');
                $content = '
                    <div class="alert">
                        <strong>Account expiring soon</strong><br>
                        One of your provider accounts will expire in <span class="' . $status_class . '">' . $data['days_remaining'] . ' days</span>.
                    </div>

                    <table class="info-table">
                        <tr><th>Account Name</th><td>' . esc_html($data['account_name']) . '</td></tr>
                        <tr><th>Provider</th><td>' . esc_html($data['provider']) . '</td></tr>
                        <tr><th>Account Login</th><td>' . esc_html($data['account_login']) . '</td></tr>
                        <tr><th>Expires At</th><td><span class="' . $status_class . '">' . esc_html($data['expires_formatted']) . '</span></td></tr>
                        <tr><th>Active Licenses</th><td>' . intval($data['active_licenses']) . '</td></tr>
                        <tr><th>Capacity</th><td>' . intval($data['capacity']) . '</td></tr>
                    </table>

                    <p><strong>Action Required:</strong> Please renew this account or update the expiration date to avoid service interruption.</p>

                    <p><a href="' . esc_url($data['admin_link']) . '" class="button">Edit Account</a></p>';
                break;

            case 'capacity_high':
                $status_class = $data['usage_percent'] >= 95 ? 'status-high' : 'status-medium';
                $content = '
                    <div class="alert">
                        <strong>Pool capacity warning</strong><br>
                        Pool capacity is at <span class="' . $status_class . '">' . $data['usage_percent'] . '%</span> usage.
                    </div>

                    <table class="info-table">
                        <tr><th>Pool Name</th><td>' . esc_html($data['pool_name']) . '</td></tr>
                        <tr><th>Product</th><td>' . esc_html($data['product_name']) . '</td></tr>
                        <tr><th>Strategy</th><td>' . esc_html($data['strategy']) . '</td></tr>
                        <tr><th>Usage</th><td><span class="' . $status_class . '">' . $data['usage_percent'] . '%</span></td></tr>
                        <tr><th>Active Assignments</th><td>' . intval($data['active_assignments']) . '</td></tr>
                        <tr><th>Total Capacity</th><td>' . intval($data['total_capacity']) . '</td></tr>
                        <tr><th>Available Slots</th><td>' . intval($data['available_slots']) . '</td></tr>
                    </table>

                    <p><strong>Recommendation:</strong> Consider adding more provider accounts to this pool to handle additional license assignments.</p>

                    <p><a href="' . esc_url($data['admin_link']) . '" class="button">Manage Pool</a></p>';
                break;

            default:
                $content = '<p>Unknown email template type.</p>';
        }

        // Replace template variables
        $replacements = array(
            '{title}' => esc_html($data['title'] ?? 'VD License Manager Notification'),
            '{content}' => $content,
            '{site_name}' => get_bloginfo('name')
        );

        return str_replace(array_keys($replacements), array_values($replacements), $base_template);
    }

    /**
     * Send welcome email to new license holder
     *
     * @since 1.0.0
     * @param string $license_key License key
     * @param string $customer_email Customer email
     * @return bool Success status
     */
    public static function send_welcome_email($license_key, $customer_email) {
        $subject = sprintf(__('[%s] License Activated - %s', 'vd-license-manager'),
                          get_bloginfo('name'),
                          substr($license_key, 0, 20) . '...');

        $portal_url = home_url('/license-portal/'); // Adjust based on your portal page

        $template_data = array(
            'title' => __('License Activated Successfully', 'vd-license-manager'),
            'license_key' => $license_key,
            'portal_url' => $portal_url,
            'support_email' => get_option('admin_email')
        );

        $content = '
            <p>Thank you for your purchase! Your license has been activated successfully.</p>
            <table class="info-table">
                <tr><th>License Key</th><td>' . esc_html($license_key) . '</td></tr>
                <tr><th>Portal Access</th><td><a href="' . esc_url($portal_url) . '">Access Portal</a></td></tr>
            </table>
            <p>Use your license key to access the customer portal where you can view your account information and manage your devices.</p>
            <p>If you need assistance, please contact support at ' . esc_html($template_data['support_email']) . '</p>';

        $base_template = self::get_email_template('welcome', $template_data);
        $message = str_replace('{content}', $content, $base_template);

        return self::send_email($customer_email, $subject, $message);
    }
}
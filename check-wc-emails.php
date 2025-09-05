<?php
/**
 * Check WooCommerce Email Settings
 */

require_once('wp-load.php');

// Check if user is logged in as admin
if (!current_user_can('manage_options')) {
    wp_die('Bạn cần đăng nhập với quyền admin để sử dụng tính năng này.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Check WooCommerce Email Settings - Vidieu.vn</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .status-enabled { color: green; font-weight: bold; }
        .status-disabled { color: red; font-weight: bold; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        .test-button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 0; }
        .test-button:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="container">
        <h1>WooCommerce Email Settings Checker</h1>
        
        <?php
        // Check WP Mail SMTP settings
        $wpms_options = get_option('wp_mail_smtp', array());
        $do_not_send = isset($wpms_options['general']['do_not_send']) && $wpms_options['general']['do_not_send'];
        
        if ($do_not_send) {
            echo '<div class="error"><strong>⚠️ CRITICAL:</strong> WP Mail SMTP "Do Not Send" is ENABLED! All emails are blocked!</div>';
        } else {
            echo '<div class="success">✅ WP Mail SMTP is configured to send emails</div>';
        }
        
        // Check if WPMS_DO_NOT_SEND constant
        if (defined('WPMS_DO_NOT_SEND') && WPMS_DO_NOT_SEND) {
            echo '<div class="error"><strong>⚠️ CRITICAL:</strong> WPMS_DO_NOT_SEND constant is TRUE! All emails are blocked!</div>';
        }
        ?>
        
        <h2>WooCommerce Email Templates Status</h2>
        <table>
            <thead>
                <tr>
                    <th>Email Type</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Recipient</th>
                    <th>Trigger</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Get all WooCommerce emails
                $mailer = WC()->mailer();
                $emails = $mailer->get_emails();
                
                foreach ($emails as $email_id => $email) {
                    $enabled = $email->is_enabled();
                    $status_class = $enabled ? 'status-enabled' : 'status-disabled';
                    $status_text = $enabled ? 'ENABLED' : 'DISABLED';
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($email->title) . '</td>';
                    echo '<td><code>' . esc_html($email_id) . '</code></td>';
                    echo '<td class="' . $status_class . '">' . $status_text . '</td>';
                    echo '<td>' . esc_html($email->recipient ? $email->recipient : 'Customer') . '</td>';
                    echo '<td>' . esc_html($email->description) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        
        <h2>Email Configuration</h2>
        <?php
        $from_email = get_option('woocommerce_email_from_address');
        $from_name = get_option('woocommerce_email_from_name');
        ?>
        <p><strong>WooCommerce From Email:</strong> <code><?php echo esc_html($from_email); ?></code></p>
        <p><strong>WooCommerce From Name:</strong> <code><?php echo esc_html($from_name); ?></code></p>
        <p><strong>WordPress From Email (after filters):</strong> <code><?php echo esc_html(apply_filters('wp_mail_from', '')); ?></code></p>
        <p><strong>WordPress From Name (after filters):</strong> <code><?php echo esc_html(apply_filters('wp_mail_from_name', '')); ?></code></p>
        
        <h2>Recent Orders</h2>
        <?php
        // Get recent orders
        $args = array(
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        $orders = wc_get_orders($args);
        
        if ($orders) {
            echo '<table>';
            echo '<thead><tr><th>Order #</th><th>Date</th><th>Status</th><th>Customer Email</th><th>Total</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($orders as $order) {
                echo '<tr>';
                echo '<td>#' . $order->get_id() . '</td>';
                echo '<td>' . $order->get_date_created()->format('Y-m-d H:i:s') . '</td>';
                echo '<td>' . $order->get_status() . '</td>';
                echo '<td>' . $order->get_billing_email() . '</td>';
                echo '<td>' . wc_price($order->get_total()) . '</td>';
                echo '<td>';
                echo '<form method="post" style="display:inline;">';
                echo '<input type="hidden" name="test_order_id" value="' . $order->get_id() . '">';
                echo '<input type="submit" name="trigger_new_order" value="Trigger New Order Email" class="test-button">';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>No orders found.</p>';
        }
        
        // Handle email trigger
        if (isset($_POST['trigger_new_order']) && isset($_POST['test_order_id'])) {
            $order_id = intval($_POST['test_order_id']);
            
            echo '<div class="warning">Attempting to trigger emails for Order #' . $order_id . '...</div>';
            
            // Trigger new order email
            WC()->mailer()->get_emails()['WC_Email_New_Order']->trigger($order_id);
            
            // Trigger customer processing email
            WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger($order_id);
            
            echo '<div class="success">Email triggers sent! Check your email and the debug logs.</div>';
        }
        ?>
        
        <h2>Debug Logs</h2>
        <p>Debug logs are being saved to: <code><?php echo WP_CONTENT_DIR; ?>/wc-email-debug/</code></p>
        
        <?php
        // Show recent log entries
        $debug_dir = WP_CONTENT_DIR . '/wc-email-debug';
        if (is_dir($debug_dir)) {
            $log_files = glob($debug_dir . '/*.log');
            if ($log_files) {
                echo '<h3>Available Log Files:</h3>';
                echo '<ul>';
                foreach ($log_files as $log_file) {
                    echo '<li>' . basename($log_file) . ' (' . date('Y-m-d H:i:s', filemtime($log_file)) . ')</li>';
                }
                echo '</ul>';
            }
        }
        ?>
        
        <h2>Quick Actions</h2>
        <p><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=email'); ?>" class="test-button">Go to WooCommerce Email Settings</a></p>
        <p><a href="<?php echo admin_url('admin.php?page=wp-mail-smtp'); ?>" class="test-button">Go to WP Mail SMTP Settings</a></p>
        <p><a href="test-email.php" class="test-button">Test Basic Email Sending</a></p>
        
        <p><a href="<?php echo admin_url(); ?>">← Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
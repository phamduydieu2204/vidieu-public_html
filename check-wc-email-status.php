<?php
/**
 * Check WooCommerce Email Templates Status
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Access denied. Please login as admin.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>WooCommerce Email Templates Status</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .enabled { color: green; font-weight: bold; }
        .disabled { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .warning { background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 20px 0; }
        .success { background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0; }
        button { background: #007cba; color: white; padding: 8px 15px; border: none; cursor: pointer; }
        .test-form { display: inline-block; margin: 0 5px; }
    </style>
</head>
<body>
    <h1>WooCommerce Email Templates Status Check</h1>
    
    <?php
    // Message handling
    if (isset($_GET['enabled'])) {
        echo '<div class="success">✓ Email template "' . esc_html($_GET['enabled']) . '" has been enabled!</div>';
    }
    if (isset($_GET['triggered'])) {
        echo '<div class="success">✓ Email "' . esc_html($_GET['triggered']) . '" has been triggered for order #' . intval($_GET['order']) . '!</div>';
    }
    ?>
    
    <div class="warning">
        <strong>Important:</strong> If emails are not sending even though everything looks correct, the issue might be:
        <ul>
            <li>Email templates are DISABLED in WooCommerce</li>
            <li>Order status transitions are not triggering emails</li>
            <li>Email queue is stuck or delayed</li>
        </ul>
    </div>
    
    <h2>Email Templates Status</h2>
    <table>
        <thead>
            <tr>
                <th>Email Type</th>
                <th>ID</th>
                <th>Enabled</th>
                <th>Recipient</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            
            foreach ($emails as $email_id => $email) {
                $enabled = $email->is_enabled();
                $status_class = $enabled ? 'enabled' : 'disabled';
                $status_text = $enabled ? 'YES' : 'NO';
                $recipient = $email->is_customer_email() ? 'Customer' : ($email->recipient ? $email->recipient : 'Admin');
                
                echo '<tr>';
                echo '<td><strong>' . esc_html($email->title) . '</strong></td>';
                echo '<td><code>' . esc_html($email_id) . '</code></td>';
                echo '<td class="' . $status_class . '">' . $status_text . '</td>';
                echo '<td>' . esc_html($recipient) . '</td>';
                echo '<td>' . esc_html($email->description) . '</td>';
                echo '<td>';
                
                // Enable button if disabled
                if (!$enabled) {
                    echo '<form method="post" class="test-form">';
                    echo '<input type="hidden" name="enable_email" value="' . esc_attr($email_id) . '">';
                    echo '<button type="submit">Enable</button>';
                    echo '</form>';
                }
                
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
    
    <?php
    // Handle enable email
    if (isset($_POST['enable_email'])) {
        $email_id = sanitize_text_field($_POST['enable_email']);
        $email_settings_key = str_replace('WC_Email_', 'woocommerce_', strtolower($email_id)) . '_settings';
        
        $settings = get_option($email_settings_key, array());
        $settings['enabled'] = 'yes';
        update_option($email_settings_key, $settings);
        
        echo '<script>window.location.href = "?enabled=' . urlencode($email_id) . '";</script>';
        exit;
    }
    ?>
    
    <h2>Recent Orders - Test Email Triggers</h2>
    <?php
    $orders = wc_get_orders(array(
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if ($orders) {
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Order #</th>';
        echo '<th>Date</th>';
        echo '<th>Status</th>';
        echo '<th>Customer</th>';
        echo '<th>Email</th>';
        echo '<th>Actions</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($orders as $order) {
            echo '<tr>';
            echo '<td><strong>#' . $order->get_id() . '</strong></td>';
            echo '<td>' . $order->get_date_created()->format('Y-m-d H:i:s') . '</td>';
            echo '<td>' . ucfirst($order->get_status()) . '</td>';
            echo '<td>' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</td>';
            echo '<td>' . $order->get_billing_email() . '</td>';
            echo '<td>';
            
            // Trigger different emails based on order status
            echo '<form method="post" class="test-form">';
            echo '<input type="hidden" name="trigger_email" value="' . $order->get_id() . '">';
            echo '<select name="email_type">';
            echo '<option value="new_order">New Order (Admin)</option>';
            echo '<option value="processing">Processing (Customer)</option>';
            echo '<option value="completed">Completed (Customer)</option>';
            echo '<option value="on_hold">On Hold (Customer)</option>';
            echo '</select>';
            echo '<button type="submit">Send</button>';
            echo '</form>';
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    // Handle trigger email
    if (isset($_POST['trigger_email']) && isset($_POST['email_type'])) {
        $order_id = intval($_POST['trigger_email']);
        $email_type = sanitize_text_field($_POST['email_type']);
        
        $emails = WC()->mailer()->get_emails();
        
        switch ($email_type) {
            case 'new_order':
                $emails['WC_Email_New_Order']->trigger($order_id);
                break;
            case 'processing':
                $emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                break;
            case 'completed':
                $emails['WC_Email_Customer_Completed_Order']->trigger($order_id);
                break;
            case 'on_hold':
                $emails['WC_Email_Customer_On_Hold_Order']->trigger($order_id);
                break;
        }
        
        echo '<script>window.location.href = "?triggered=' . urlencode($email_type) . '&order=' . $order_id . '";</script>';
        exit;
    }
    ?>
    
    <h2>Email Sending Checklist</h2>
    <ol>
        <li>✅ WP Mail SMTP Force From Email: <strong class="enabled">ENABLED</strong></li>
        <li>✅ From Email: <strong>admin@vidieu.vn</strong></li>
        <li>✅ SMTP Authentication: <strong class="enabled">Working</strong></li>
        <li>❓ WooCommerce Email Templates: <strong>Check table above</strong></li>
        <li>❓ Order Status Emails: <strong>Test with recent orders above</strong></li>
    </ol>
    
    <p>
        <a href="check-wpms-settings.php">Check SMTP Settings</a> | 
        <a href="check-email-hooks.php">Check Email Hooks</a> |
        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=email'); ?>">WooCommerce Email Settings</a>
    </p>
</body>
</html>
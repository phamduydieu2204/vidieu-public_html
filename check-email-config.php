<?php
/**
 * Check Email Configuration
 */

require_once('wp-load.php');

// Check if user is logged in as admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Please login as admin.');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Configuration Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Email Configuration Check</h1>
    
    <?php
    // 1. Check WP Mail SMTP settings
    echo '<h2>1. WP Mail SMTP Settings</h2>';
    $wpms_options = get_option('wp_mail_smtp', array());
    if (!empty($wpms_options)) {
        $do_not_send = isset($wpms_options['general']['do_not_send']) && $wpms_options['general']['do_not_send'];
        echo '<p>Do Not Send: <span class="' . ($do_not_send ? 'error' : 'success') . '">' . ($do_not_send ? 'ENABLED (BAD!)' : 'Disabled (Good)') . '</span></p>';
        
        if (isset($wpms_options['mail']['mailer'])) {
            echo '<p>Mailer: ' . esc_html($wpms_options['mail']['mailer']) . '</p>';
        }
    } else {
        echo '<p class="info">WP Mail SMTP options not found</p>';
    }
    
    // 2. Check WooCommerce email settings
    echo '<h2>2. WooCommerce Email Settings</h2>';
    echo '<p>From Email: <code>' . get_option('woocommerce_email_from_address') . '</code></p>';
    echo '<p>From Name: <code>' . get_option('woocommerce_email_from_name') . '</code></p>';
    
    // 3. Check if WooCommerce emails are enabled
    echo '<h2>3. WooCommerce Email Templates</h2>';
    echo '<table>';
    echo '<tr><th>Email Type</th><th>Enabled</th><th>Recipient</th></tr>';
    
    if (class_exists('WooCommerce')) {
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();
        
        foreach ($emails as $email) {
            $enabled = $email->is_enabled() ? '<span class="success">Yes</span>' : '<span class="error">No</span>';
            $recipient = $email->recipient ? $email->recipient : 'Customer';
            echo '<tr>';
            echo '<td>' . esc_html($email->title) . '</td>';
            echo '<td>' . $enabled . '</td>';
            echo '<td>' . esc_html($recipient) . '</td>';
            echo '</tr>';
        }
    }
    echo '</table>';
    
    // 4. Test email sending
    echo '<h2>4. Email Send Test</h2>';
    
    if (isset($_POST['send_test'])) {
        $to = sanitize_email($_POST['test_email']);
        $subject = 'Test Email from ' . get_bloginfo('name');
        $message = 'This is a test email sent at ' . date('Y-m-d H:i:s');
        
        $result = wp_mail($to, $subject, $message);
        
        if ($result) {
            echo '<p class="success">✓ Test email sent successfully to ' . esc_html($to) . '</p>';
        } else {
            echo '<p class="error">✗ Failed to send test email</p>';
            
            // Check for PHPMailer errors
            global $phpmailer;
            if (isset($phpmailer->ErrorInfo) && !empty($phpmailer->ErrorInfo)) {
                echo '<p class="error">Error: ' . esc_html($phpmailer->ErrorInfo) . '</p>';
            }
        }
    }
    ?>
    
    <form method="post">
        <label>Send test email to:</label>
        <input type="email" name="test_email" value="vidieu.amz@gmail.com" required>
        <button type="submit" name="send_test">Send Test Email</button>
    </form>
    
    <h2>5. Recent Orders (Last 5)</h2>
    <?php
    $recent_orders = wc_get_orders(array(
        'limit' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if ($recent_orders) {
        echo '<table>';
        echo '<tr><th>Order ID</th><th>Date</th><th>Status</th><th>Customer Email</th><th>Action</th></tr>';
        
        foreach ($recent_orders as $order) {
            echo '<tr>';
            echo '<td>#' . $order->get_id() . '</td>';
            echo '<td>' . $order->get_date_created()->format('Y-m-d H:i:s') . '</td>';
            echo '<td>' . $order->get_status() . '</td>';
            echo '<td>' . $order->get_billing_email() . '</td>';
            echo '<td>';
            echo '<form method="post" style="display:inline;">';
            echo '<input type="hidden" name="resend_order_id" value="' . $order->get_id() . '">';
            echo '<button type="submit" name="resend_email">Resend Emails</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Handle resend email
        if (isset($_POST['resend_email']) && isset($_POST['resend_order_id'])) {
            $order_id = intval($_POST['resend_order_id']);
            
            // Trigger emails
            WC()->mailer()->get_emails()['WC_Email_New_Order']->trigger($order_id);
            WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger($order_id);
            
            echo '<p class="success">✓ Emails triggered for Order #' . $order_id . '</p>';
        }
    }
    ?>
    
    <h2>6. PHP Mail Configuration</h2>
    <pre><?php
    echo 'PHP Version: ' . phpversion() . "\n";
    echo 'Sendmail Path: ' . ini_get('sendmail_path') . "\n";
    echo 'SMTP: ' . ini_get('SMTP') . "\n";
    echo 'smtp_port: ' . ini_get('smtp_port') . "\n";
    ?></pre>
    
    <p><a href="<?php echo admin_url(); ?>">← Back to Admin</a></p>
</body>
</html>
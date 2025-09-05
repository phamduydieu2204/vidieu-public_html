<?php
/**
 * Check WP Mail SMTP Settings Details
 */

require_once('wp-load.php');

// Check admin access
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Please login as admin.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>WP Mail SMTP Settings Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; }
        .box { background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px; }
        pre { background: #333; color: #fff; padding: 15px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .important { background: #fff3cd; padding: 5px; }
    </style>
</head>
<body>
    <h1>WP Mail SMTP Settings Detailed Check</h1>
    
    <?php
    // Get WP Mail SMTP options
    $wpms_options = get_option('wp_mail_smtp', array());
    
    echo '<h2>1. WP Mail SMTP Configuration</h2>';
    echo '<div class="box">';
    
    // Check mailer type
    $mailer = isset($wpms_options['mail']['mailer']) ? $wpms_options['mail']['mailer'] : 'mail';
    echo '<p><strong>Mailer Type:</strong> ' . esc_html($mailer) . '</p>';
    
    // Check From Email settings
    $from_email = isset($wpms_options['mail']['from_email']) ? $wpms_options['mail']['from_email'] : '';
    $from_email_force = isset($wpms_options['mail']['from_email_force']) && $wpms_options['mail']['from_email_force'];
    $from_name = isset($wpms_options['mail']['from_name']) ? $wpms_options['mail']['from_name'] : '';
    $from_name_force = isset($wpms_options['mail']['from_name_force']) && $wpms_options['mail']['from_name_force'];
    
    echo '<p><strong>From Email:</strong> <code>' . esc_html($from_email) . '</code></p>';
    echo '<p><strong>Force From Email:</strong> <span class="' . ($from_email_force ? 'success' : 'error') . '">' . 
         ($from_email_force ? '✓ ENABLED' : '✗ DISABLED') . '</span></p>';
    
    echo '<p><strong>From Name:</strong> <code>' . esc_html($from_name) . '</code></p>';
    echo '<p><strong>Force From Name:</strong> <span class="' . ($from_name_force ? 'success' : 'error') . '">' . 
         ($from_name_force ? '✓ ENABLED' : '✗ DISABLED') . '</span></p>';
    
    // Check Do Not Send
    $do_not_send = isset($wpms_options['general']['do_not_send']) && $wpms_options['general']['do_not_send'];
    echo '<p><strong>Do Not Send (Block all emails):</strong> <span class="' . ($do_not_send ? 'error' : 'success') . '">' . 
         ($do_not_send ? '✗ ENABLED - ALL EMAILS BLOCKED!' : '✓ Disabled') . '</span></p>';
    
    // SMTP Settings
    if ($mailer === 'smtp') {
        echo '<h3>SMTP Configuration:</h3>';
        $smtp = isset($wpms_options['smtp']) ? $wpms_options['smtp'] : array();
        echo '<ul>';
        echo '<li>Host: <code>' . (isset($smtp['host']) ? esc_html($smtp['host']) : 'Not set') . '</code></li>';
        echo '<li>Port: <code>' . (isset($smtp['port']) ? esc_html($smtp['port']) : 'Not set') . '</code></li>';
        echo '<li>Encryption: <code>' . (isset($smtp['encryption']) ? esc_html($smtp['encryption']) : 'None') . '</code></li>';
        echo '<li>Authentication: ' . (isset($smtp['auth']) && $smtp['auth'] ? '✓ Enabled' : '✗ Disabled') . '</li>';
        echo '<li>Username: <code>' . (isset($smtp['user']) ? esc_html($smtp['user']) : 'Not set') . '</code></li>';
        echo '<li>Password: ' . (isset($smtp['pass']) && !empty($smtp['pass']) ? '✓ Set' : '✗ Not set') . '</li>';
        echo '</ul>';
    }
    
    echo '</div>';
    
    // Check filters
    echo '<h2>2. Active Email Filters</h2>';
    echo '<div class="box">';
    
    $from_email_filtered = apply_filters('wp_mail_from', '');
    $from_name_filtered = apply_filters('wp_mail_from_name', '');
    
    echo '<p><strong>wp_mail_from filter result:</strong> <code>' . esc_html($from_email_filtered) . '</code></p>';
    echo '<p><strong>wp_mail_from_name filter result:</strong> <code>' . esc_html($from_name_filtered) . '</code></p>';
    
    // Check for other plugins interfering
    global $wp_filter;
    
    echo '<h3>Hooks on wp_mail_from:</h3>';
    if (isset($wp_filter['wp_mail_from'])) {
        echo '<pre>';
        foreach ($wp_filter['wp_mail_from'] as $priority => $hooks) {
            foreach ($hooks as $hook) {
                $function = $hook['function'];
                if (is_array($function)) {
                    if (is_object($function[0])) {
                        echo "Priority $priority: " . get_class($function[0]) . '::' . $function[1] . "\n";
                    } else {
                        echo "Priority $priority: " . implode('::', $function) . "\n";
                    }
                } else {
                    echo "Priority $priority: " . $function . "\n";
                }
            }
        }
        echo '</pre>';
    } else {
        echo '<p>No hooks found</p>';
    }
    
    echo '</div>';
    
    // WooCommerce Settings
    echo '<h2>3. WooCommerce Email Settings</h2>';
    echo '<div class="box">';
    echo '<p><strong>WC From Email:</strong> <code>' . get_option('woocommerce_email_from_address', '') . '</code></p>';
    echo '<p><strong>WC From Name:</strong> <code>' . get_option('woocommerce_email_from_name', '') . '</code></p>';
    echo '</div>';
    
    // Test Email
    echo '<h2>4. Test Email Send</h2>';
    echo '<div class="box">';
    
    if (isset($_POST['send_test'])) {
        $test_email = sanitize_email($_POST['test_email']);
        
        // Show what will be sent
        echo '<h3>Email Details:</h3>';
        echo '<ul>';
        echo '<li>To: <code>' . esc_html($test_email) . '</code></li>';
        echo '<li>From (before send): <code>' . esc_html(apply_filters('wp_mail_from', '')) . '</code></li>';
        echo '<li>From Name (before send): <code>' . esc_html(apply_filters('wp_mail_from_name', '')) . '</code></li>';
        echo '</ul>';
        
        // Send test
        $result = wp_mail($test_email, 'Test from WP Mail SMTP Check', 'Test email sent at ' . date('Y-m-d H:i:s'));
        
        if ($result) {
            echo '<p class="success">✓ Email sent successfully!</p>';
        } else {
            echo '<p class="error">✗ Email failed to send</p>';
            global $phpmailer;
            if (!empty($phpmailer->ErrorInfo)) {
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
    </div>
    
    <h2>5. Recommendations</h2>
    <div class="box important">
        <?php if (!$from_email_force): ?>
            <p class="error">⚠️ <strong>Force From Email is DISABLED!</strong> This is likely why emails are not working properly.</p>
            <p>Go to WP Mail SMTP → Settings → From Email and enable "Force From Email"</p>
        <?php endif; ?>
        
        <?php if ($from_email !== 'admin@vidieu.vn'): ?>
            <p class="warning">⚠️ From Email is not set to admin@vidieu.vn (current: <?php echo esc_html($from_email); ?>)</p>
        <?php endif; ?>
        
        <?php if (get_option('woocommerce_email_from_address') !== 'admin@vidieu.vn'): ?>
            <p class="warning">⚠️ WooCommerce From Email is not admin@vidieu.vn</p>
            <p>Go to WooCommerce → Settings → Emails → Email sender options</p>
        <?php endif; ?>
    </div>
    
    <p><a href="<?php echo admin_url('admin.php?page=wp-mail-smtp'); ?>">Go to WP Mail SMTP Settings</a> | 
       <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=email'); ?>">Go to WooCommerce Email Settings</a></p>
</body>
</html>
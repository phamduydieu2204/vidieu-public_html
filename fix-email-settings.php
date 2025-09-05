<?php
/**
 * Fix Email Settings - Update WooCommerce email configuration
 */

require_once('wp-load.php');

// Check if user is logged in as admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Please login as admin.');
}

// Update email settings
$updated = false;
$message = '';

// Force update WooCommerce email settings
update_option('woocommerce_email_from_address', 'admin@vidieu.vn');
update_option('woocommerce_email_from_name', 'Vidieu.vn');

// Also update admin email recipients
$admin_emails = array(
    'woocommerce_new_order_settings',
    'woocommerce_cancelled_order_settings',
    'woocommerce_failed_order_settings'
);

foreach ($admin_emails as $email_setting) {
    $settings = get_option($email_setting, array());
    if (isset($settings['recipient']) && $settings['recipient'] == 'order@vidieu.vn') {
        $settings['recipient'] = 'admin@vidieu.vn';
        update_option($email_setting, $settings);
    }
}

$updated = true;
$message = 'Email settings have been updated!';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Email Settings</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Fix Email Settings</h1>
    
    <?php if ($updated): ?>
        <div class="success">
            <strong>✓ Success!</strong> <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>Updated Settings:</h3>
        <ul>
            <li>From Email: <code>admin@vidieu.vn</code> (was: order@vidieu.vn)</li>
            <li>From Name: <code>Vidieu.vn</code></li>
            <li>Admin notification emails: Changed to <code>admin@vidieu.vn</code></li>
        </ul>
    </div>
    
    <h3>Current Settings:</h3>
    <ul>
        <li>WooCommerce From Email: <code><?php echo get_option('woocommerce_email_from_address'); ?></code></li>
        <li>WooCommerce From Name: <code><?php echo get_option('woocommerce_email_from_name'); ?></code></li>
    </ul>
    
    <h3>Next Steps:</h3>
    <ol>
        <li>Go to <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=email'); ?>">WooCommerce → Settings → Emails</a></li>
        <li>Click on "Email sender options"</li>
        <li>Verify "From address" is <code>admin@vidieu.vn</code></li>
        <li>For each email template (New order, Processing order, etc.), make sure they are enabled</li>
    </ol>
    
    <p>
        <a href="check-email-config.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Check Email Config</a>
        <a href="<?php echo admin_url(); ?>" style="margin-left: 10px;">Back to Admin</a>
    </p>
</body>
</html>
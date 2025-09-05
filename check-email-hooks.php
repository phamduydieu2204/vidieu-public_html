<?php
/**
 * Check all email-related hooks and filters
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Access denied.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Hooks Analysis</title>
    <style>
        body { font-family: monospace; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .hook-group { background: #f5f5f5; padding: 15px; margin: 20px 0; border-left: 4px solid #007cba; }
        .priority-high { color: red; }
        .priority-low { color: green; }
        pre { background: #333; color: #0f0; padding: 10px; overflow-x: auto; }
        .warning { background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <h1>Email Hooks & Filters Analysis</h1>
    
    <?php
    global $wp_filter;
    
    // Important email-related hooks
    $email_hooks = array(
        'wp_mail' => 'Main WordPress mail function',
        'wp_mail_from' => 'Filter From email address',
        'wp_mail_from_name' => 'Filter From name',
        'wp_mail_content_type' => 'Filter email content type',
        'wp_mail_charset' => 'Filter email charset',
        'phpmailer_init' => 'PHPMailer initialization',
        'woocommerce_email_from_address' => 'WooCommerce From address',
        'woocommerce_email_from_name' => 'WooCommerce From name',
        'woocommerce_email_enabled_new_order' => 'New order email enabled',
        'woocommerce_email_enabled_customer_processing_order' => 'Processing order email enabled',
        'woocommerce_mail_callback' => 'WooCommerce mail callback',
        'wp_mail_failed' => 'Mail failed action'
    );
    
    echo '<div class="warning">';
    echo '<h3>MU-Plugin Status:</h3>';
    if (file_exists(WPMU_PLUGIN_DIR . '/woocommerce-email-fix.php')) {
        echo '<p>✓ woocommerce-email-fix.php is ACTIVE</p>';
    } elseif (file_exists(WPMU_PLUGIN_DIR . '/woocommerce-email-fix.php.backup')) {
        echo '<p>✗ woocommerce-email-fix.php is DISABLED (renamed to .backup)</p>';
    } else {
        echo '<p>✗ woocommerce-email-fix.php NOT FOUND</p>';
    }
    echo '</div>';
    
    foreach ($email_hooks as $hook => $description) {
        echo '<div class="hook-group">';
        echo '<h2>' . esc_html($hook) . '</h2>';
        echo '<p><em>' . esc_html($description) . '</em></p>';
        
        if (isset($wp_filter[$hook])) {
            echo '<pre>';
            foreach ($wp_filter[$hook] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $function_name = '';
                    
                    if (is_array($callback['function'])) {
                        if (is_object($callback['function'][0])) {
                            $function_name = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                        } else {
                            $function_name = implode('::', $callback['function']);
                        }
                    } elseif (is_object($callback['function']) && get_class($callback['function']) === 'Closure') {
                        $function_name = 'Closure';
                    } else {
                        $function_name = $callback['function'];
                    }
                    
                    $priority_class = $priority > 100 ? 'priority-high' : 'priority-low';
                    echo '<span class="' . $priority_class . '">Priority ' . $priority . '</span>: ' . $function_name . "\n";
                }
            }
            echo '</pre>';
        } else {
            echo '<p>No hooks registered</p>';
        }
        echo '</div>';
    }
    
    // Check for specific issues
    echo '<div class="hook-group">';
    echo '<h2>Potential Issues Found:</h2>';
    
    $issues = array();
    
    // Check if WP Mail SMTP is overriding
    if (class_exists('WPMailSMTP\Processor')) {
        $wpms_options = get_option('wp_mail_smtp', array());
        if (!isset($wpms_options['mail']['from_email_force']) || !$wpms_options['mail']['from_email_force']) {
            $issues[] = 'WP Mail SMTP "Force From Email" is not enabled';
        }
    }
    
    // Check for conflicting plugins
    $active_plugins = get_option('active_plugins', array());
    $email_plugins = array_filter($active_plugins, function($plugin) {
        return strpos($plugin, 'mail') !== false || strpos($plugin, 'smtp') !== false;
    });
    
    if (count($email_plugins) > 1) {
        $issues[] = 'Multiple email/SMTP plugins detected: ' . implode(', ', $email_plugins);
    }
    
    if (empty($issues)) {
        echo '<p class="priority-low">✓ No obvious issues found</p>';
    } else {
        foreach ($issues as $issue) {
            echo '<p class="priority-high">⚠️ ' . esc_html($issue) . '</p>';
        }
    }
    echo '</div>';
    ?>
    
    <p><a href="check-wpms-settings.php">Check WP Mail SMTP Settings</a> | 
       <a href="<?php echo admin_url(); ?>">Back to Admin</a></p>
</body>
</html>
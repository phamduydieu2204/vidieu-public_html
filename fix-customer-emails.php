<?php
/**
 * Fix Customer Email Issues
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Access denied.');
}

// Enable important customer emails
if (isset($_POST['fix_emails'])) {
    // Customer Processing Order - QUAN TRỌNG!
    $processing_settings = get_option('woocommerce_customer_processing_order_settings', array());
    $processing_settings['enabled'] = 'yes';
    update_option('woocommerce_customer_processing_order_settings', $processing_settings);
    
    // Customer Completed Order
    $completed_settings = get_option('woocommerce_customer_completed_order_settings', array());
    $completed_settings['enabled'] = 'yes';
    update_option('woocommerce_customer_completed_order_settings', $completed_settings);
    
    // Customer On Hold Order
    $on_hold_settings = get_option('woocommerce_customer_on_hold_order_settings', array());
    $on_hold_settings['enabled'] = 'yes';
    update_option('woocommerce_customer_on_hold_order_settings', $on_hold_settings);
    
    $fixed = true;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Customer Email Issues</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; margin: 20px 0; }
        button { background: #28a745; color: white; padding: 10px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .enabled { color: green; font-weight: bold; }
        .disabled { color: red; font-weight: bold; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Fix WooCommerce Customer Email Issues</h1>
    
    <?php if (isset($fixed)): ?>
        <div class="success">
            ✓ Customer email templates have been enabled! Please test by placing a new order.
        </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>Vấn đề hiện tại:</h3>
        <ul>
            <li>Email cho Admin (New Order) - ✅ Hoạt động</li>
            <li>Email cho Khách hàng - ❌ Không gửi</li>
        </ul>
    </div>
    
    <h2>Customer Email Templates Status:</h2>
    <?php
    $customer_emails = array(
        'customer_processing_order' => 'Processing Order (Đang xử lý)',
        'customer_completed_order' => 'Order Complete (Hoàn thành)',
        'customer_on_hold_order' => 'Order On-hold (Chờ xử lý)',
        'customer_refunded_order' => 'Order Refunded (Hoàn tiền)',
        'customer_failed_order' => 'Failed Order (Thất bại)'
    );
    
    echo '<table>';
    echo '<tr><th>Email Template</th><th>Status</th><th>Description</th></tr>';
    
    foreach ($customer_emails as $key => $title) {
        $option_name = 'woocommerce_' . $key . '_settings';
        $settings = get_option($option_name, array());
        $enabled = isset($settings['enabled']) && $settings['enabled'] === 'yes';
        
        echo '<tr>';
        echo '<td><strong>' . $title . '</strong></td>';
        echo '<td class="' . ($enabled ? 'enabled' : 'disabled') . '">' . ($enabled ? 'ENABLED' : 'DISABLED') . '</td>';
        echo '<td>';
        
        switch ($key) {
            case 'customer_processing_order':
                echo 'Gửi khi khách đặt hàng thành công (QUAN TRỌNG!)';
                break;
            case 'customer_completed_order':
                echo 'Gửi khi đơn hàng hoàn thành';
                break;
            case 'customer_on_hold_order':
                echo 'Gửi khi đơn hàng chờ thanh toán';
                break;
            default:
                echo 'Email thông báo cho khách hàng';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    ?>
    
    <form method="post">
        <button type="submit" name="fix_emails">Enable All Customer Email Templates</button>
    </form>
    
    <h2>Test Send Customer Email:</h2>
    <?php
    if (isset($_POST['test_email'])) {
        $test_email = sanitize_email($_POST['test_email']);
        $order_id = intval($_POST['order_id']);
        
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                // Force customer email
                $order->set_billing_email($test_email);
                $order->save();
                
                // Trigger customer processing email
                $emails = WC()->mailer()->get_emails();
                $emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                
                echo '<div class="success">✓ Customer email sent to ' . esc_html($test_email) . ' for order #' . $order_id . '</div>';
            }
        }
    }
    
    // Get recent order
    $recent_orders = wc_get_orders(array('limit' => 1));
    $recent_order = $recent_orders ? $recent_orders[0] : null;
    
    if ($recent_order):
    ?>
        <form method="post">
            <p>Test with Order #<?php echo $recent_order->get_id(); ?></p>
            <input type="hidden" name="order_id" value="<?php echo $recent_order->get_id(); ?>">
            <label>Send customer email to:</label>
            <input type="email" name="test_email" required placeholder="test@example.com">
            <button type="submit">Send Test Customer Email</button>
        </form>
    <?php endif; ?>
    
    <h2>Checklist:</h2>
    <ol>
        <li>Enable customer email templates (click button above)</li>
        <li>Go to <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=email'); ?>">WooCommerce → Settings → Emails</a></li>
        <li>Click on "Processing order" email</li>
        <li>Make sure "Enable this email notification" is checked</li>
        <li>Save changes</li>
        <li>Test by placing a new order</li>
    </ol>
    
    <div class="info">
        <h3>Lưu ý về Payment Methods:</h3>
        <ul>
            <li><strong>COD/Bank Transfer:</strong> Trigger "Processing" email ngay</li>
            <li><strong>Online Payment:</strong> Chỉ trigger khi payment confirmed</li>
            <li><strong>Manual Orders:</strong> Cần change status manually</li>
        </ul>
    </div>
    
    <p>
        <a href="check-wc-email-status.php">Check All Email Status</a> | 
        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=email&section=wc_email_customer_processing_order'); ?>">Edit Processing Email</a>
    </p>
</body>
</html>
<?php
/**
 * Debug script for order 7777
 * Run: wp eval-file debug-order-7777.php
 */

require_once('wp-load.php');

$order_id = 7777;
$order = wc_get_order($order_id);

if (!$order) {
    echo "Order #$order_id không tồn tại\n";
    exit;
}

echo "=== DEBUG ORDER #$order_id ===\n\n";
echo "Payment Method ID: " . $order->get_payment_method() . "\n";
echo "Payment Method Title: " . $order->get_payment_method_title() . "\n";
echo "Order Status: " . $order->get_status() . "\n";
echo "Order Total: " . $order->get_total() . " " . $order->get_currency() . "\n";

echo "\n=== ACTIVE HOOKS ON THANKYOU PAGE ===\n\n";
global $wp_filter;

if (isset($wp_filter['woocommerce_thankyou'])) {
    foreach ($wp_filter['woocommerce_thankyou'] as $priority => $hooks) {
        foreach ($hooks as $hook) {
            $function_name = is_array($hook['function']) 
                ? (is_object($hook['function'][0]) 
                    ? get_class($hook['function'][0]) . '->' . $hook['function'][1] 
                    : $hook['function'][0] . '::' . $hook['function'][1])
                : $hook['function'];
            echo "Priority $priority: $function_name\n";
        }
    }
}

echo "\n=== ACTIVE HOOKS ON WP_FOOTER ===\n\n";
if (isset($wp_filter['wp_footer'])) {
    foreach ($wp_filter['wp_footer'] as $priority => $hooks) {
        foreach ($hooks as $hook) {
            if (is_array($hook['function']) && isset($hook['function'][1])) {
                $function_name = $hook['function'][1];
                if (strpos($function_name, 'viet') !== false || strpos($function_name, 'qr') !== false || strpos($function_name, 'bacs') !== false) {
                    echo "Priority $priority: $function_name\n";
                }
            } elseif (is_string($hook['function'])) {
                if (strpos($hook['function'], 'viet') !== false || strpos($hook['function'], 'qr') !== false || strpos($hook['function'], 'bacs') !== false) {
                    echo "Priority $priority: {$hook['function']}\n";
                }
            }
        }
    }
}

echo "\n=== PHÂN TÍCH ===\n\n";
if ($order->get_payment_method() === 'bacs') {
    echo "❗ Order dùng BACS - VietQR cũ sẽ hiển thị\n";
    echo "→ File: woocommerce-vietqr-integration.php\n";
    echo "→ Hook: vidieu_reorganize_bacs_with_qr (wp_footer)\n";
} elseif ($order->get_payment_method() === 'vcb-gateway-mh') {
    echo "✓ Order dùng VCB-MH\n";
    echo "→ Plugin VCB-MH sẽ hiển thị QR\n";
    echo "→ Hook: Vcb_Gateway_MH->thankyou_page (woocommerce_thankyou)\n";
    
    // Check VCB settings
    $vcb_settings = get_option('vcb_gw_settings', []);
    if ($vcb_settings) {
        echo "\nVCB Settings:\n";
        echo "- Account Phone: " . ($vcb_settings['account']['phone'] ?? 'Not set') . "\n";
        echo "- Account Name: " . ($vcb_settings['account']['name'] ?? 'Not set') . "\n";
        echo "- Prefix: " . ($vcb_settings['prefix'] ?? '') . "\n";
    }
}

echo "\n=== GIẢI PHÁP ===\n\n";
echo "1. Kiểm tra file functions.php đã comment out:\n";
echo "   // require_once get_stylesheet_directory() . '/woocommerce-vietqr-integration.php';\n";
echo "\n2. Clear cache:\n";
echo "   - Browser cache (Ctrl+F5)\n";
echo "   - WordPress cache (nếu có)\n";
echo "\n3. Nếu vẫn thấy QR cũ, kiểm tra:\n";
echo "   - Payment method của order\n";
echo "   - File woocommerce-vietqr-integration.php còn active không\n";
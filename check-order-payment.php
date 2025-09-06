<?php
/**
 * Script kiểm tra payment method của order
 * Usage: wp eval-file check-order-payment.php
 */

require_once('wp-load.php');

$order_id = 7775;
$order = wc_get_order($order_id);

if (!$order) {
    echo "Order #$order_id không tồn tại\n";
    exit;
}

echo "=== THÔNG TIN ORDER #$order_id ===\n\n";
echo "Payment Method: " . $order->get_payment_method() . "\n";
echo "Payment Method Title: " . $order->get_payment_method_title() . "\n";
echo "Order Status: " . $order->get_status() . "\n";
echo "Order Total: " . $order->get_total() . " " . $order->get_currency() . "\n";
echo "Customer Email: " . $order->get_billing_email() . "\n";
echo "Order Date: " . $order->get_date_created()->format('Y-m-d H:i:s') . "\n";

echo "\n=== AVAILABLE PAYMENT GATEWAYS ===\n\n";
$payment_gateways = WC()->payment_gateways()->payment_gateways();
foreach ($payment_gateways as $gateway_id => $gateway) {
    $enabled = $gateway->enabled === 'yes' ? '✓' : '✗';
    echo "[$enabled] $gateway_id: " . $gateway->title . "\n";
}

echo "\n=== PHÂN TÍCH ===\n\n";
if ($order->get_payment_method() === 'bacs') {
    echo "❗ Order này dùng BACS payment method\n";
    echo "→ File woocommerce-vietqr-integration.php sẽ hiển thị VietQR cũ\n";
    echo "→ Plugin VCB-MH KHÔNG được sử dụng cho order này\n";
} elseif ($order->get_payment_method() === 'vcb-gateway-mh') {
    echo "✓ Order này dùng VCB-MH payment method\n";
    echo "→ Plugin VCB-MH sẽ xử lý hiển thị QR\n";
    echo "→ File woocommerce-vietqr-integration.php KHÔNG can thiệp\n";
} else {
    echo "? Order này dùng payment method khác: " . $order->get_payment_method() . "\n";
}

echo "\n=== GIẢI PHÁP ===\n\n";
echo "1. Nếu muốn dùng VCB-MH cho order mới:\n";
echo "   - Vào WooCommerce → Settings → Payments\n";
echo "   - Disable 'Direct bank transfer' (BACS)\n";
echo "   - Enable 'Vietcombank Gateway MH'\n\n";
echo "2. Nếu muốn update VietQR cũ để hỗ trợ cả 2:\n";
echo "   - Sửa file woocommerce-vietqr-integration.php\n";
echo "   - Thêm điều kiện check cả vcb-gateway-mh\n";

// Check if VCB-MH is properly configured
echo "\n=== KIỂM TRA CẤU HÌNH VCB-MH ===\n\n";
if (isset($payment_gateways['vcb-gateway-mh'])) {
    $vcb_gateway = $payment_gateways['vcb-gateway-mh'];
    echo "VCB-MH Gateway: " . ($vcb_gateway->enabled === 'yes' ? 'Đã bật' : 'Chưa bật') . "\n";
    
    // Try to get settings
    $settings = get_option('woocommerce_vcb-gateway-mh_settings', array());
    if (!empty($settings)) {
        echo "Title: " . ($settings['title'] ?? 'N/A') . "\n";
        echo "Description: " . ($settings['description'] ?? 'N/A') . "\n";
    }
} else {
    echo "❗ VCB-MH Gateway chưa được cài đặt hoặc chưa kích hoạt\n";
}
<?php
/**
 * Check VCB-MH configuration
 * Run: wp eval-file check-vcb-config.php
 */

require_once('wp-load.php');

echo "=== KIỂM TRA CẤU HÌNH VCB-MH ===\n\n";

// 1. Check if plugin is active
$active_plugins = get_option('active_plugins');
$vcb_active = in_array('vcb-mh/vcb-mh.php', $active_plugins);
echo "1. Plugin VCB-MH: " . ($vcb_active ? "✓ ACTIVE" : "✗ NOT ACTIVE") . "\n";

// 2. Check payment gateway
$gateways = WC()->payment_gateways()->payment_gateways();
if (isset($gateways['vcb-gateway-mh'])) {
    $vcb_gateway = $gateways['vcb-gateway-mh'];
    echo "2. VCB Gateway: ✓ FOUND\n";
    echo "   - Enabled: " . ($vcb_gateway->enabled === 'yes' ? 'YES' : 'NO') . "\n";
    echo "   - Title: " . $vcb_gateway->title . "\n";
    echo "   - ID: " . $vcb_gateway->id . "\n";
} else {
    echo "2. VCB Gateway: ✗ NOT FOUND\n";
}

// 3. Check VCB settings
echo "\n3. VCB Settings (vcb_gw_settings):\n";
$vcb_settings = get_option('vcb_gw_settings');
if ($vcb_settings) {
    if (isset($vcb_settings['account'])) {
        echo "   - Account Phone: " . ($vcb_settings['account']['phone'] ?? 'NOT SET') . "\n";
        echo "   - Account Name: " . ($vcb_settings['account']['name'] ?? 'NOT SET') . "\n";
    } else {
        echo "   - Account: NOT CONFIGURED\n";
    }
    echo "   - Prefix: " . ($vcb_settings['prefix'] ?? '') . "\n";
    echo "   - Suffix: " . ($vcb_settings['subfix'] ?? '') . "\n";
    echo "   - Currency Rate: " . ($vcb_settings['currency_rate'] ?? '1') . "\n";
} else {
    echo "   ❗ NO SETTINGS FOUND\n";
}

// 4. Check WooCommerce gateway settings
echo "\n4. WooCommerce VCB Gateway Settings:\n";
$wc_vcb_settings = get_option('woocommerce_vcb-gateway-mh_settings');
if ($wc_vcb_settings) {
    echo "   - Enabled: " . ($wc_vcb_settings['enabled'] ?? 'no') . "\n";
    echo "   - Title: " . ($wc_settings['title'] ?? '') . "\n";
    echo "   - Description: " . ($wc_settings['description'] ?? '') . "\n";
} else {
    echo "   ❗ NO WC SETTINGS FOUND\n";
}

// 5. Check recent order
echo "\n5. Order 7782 Details:\n";
$order = wc_get_order(7782);
if ($order) {
    echo "   - Payment Method: " . $order->get_payment_method() . "\n";
    echo "   - Total: " . $order->get_total() . "\n";
    echo "   - Currency: " . $order->get_currency() . "\n";
    
    // Build expected QR URL
    $total = intval($order->get_total());
    $phone = $vcb_settings['account']['phone'] ?? '';
    $prefix = $vcb_settings['prefix'] ?? '';
    $suffix = $vcb_settings['subfix'] ?? '';
    $note = $prefix . '7782' . $suffix;
    
    if ($phone) {
        $expected_qr = "https://api.vietqr.io/970436/{$phone}/{$total}/{$note}/qr_only.jpg";
        echo "\n   Expected QR URL:\n   " . $expected_qr . "\n";
    }
} else {
    echo "   Order not found\n";
}

// 6. Solutions
echo "\n=== GIẢI PHÁP ===\n\n";
echo "Nếu không thấy cấu hình:\n";
echo "1. Vào WP Admin → VCB Gateway\n";
echo "2. Nhập thông tin:\n";
echo "   - Số điện thoại: 0821000013390\n";
echo "   - Tên tài khoản: PHAM DUY DIEU\n";
echo "   - Prefix: Vidieuvn\n";
echo "3. Save settings\n";
echo "\nNếu đã cấu hình nhưng không hiển thị:\n";
echo "- Clear browser cache\n";
echo "- Check browser console for errors\n";
echo "- Kiểm tra MU-plugins đã active\n";
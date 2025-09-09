<?php
/**
 * Script khẩn cấp để fix không hiển thị QR
 * Run: php fix-no-qr-display.php
 */

require_once('wp-load.php');

echo "=== KIỂM TRA VẤN ĐỀ KHÔNG HIỂN THỊ QR ===\n\n";

// 1. Check active hooks
echo "1. CHECKING ACTIVE HOOKS:\n";
global $wp_filter;

echo "\nwoocommerce_thankyou hooks:\n";
if (isset($wp_filter['woocommerce_thankyou'])) {
    foreach ($wp_filter['woocommerce_thankyou'] as $priority => $hooks) {
        foreach ($hooks as $hook) {
            $function_name = is_array($hook['function']) 
                ? (is_object($hook['function'][0]) 
                    ? get_class($hook['function'][0]) . '->' . $hook['function'][1] 
                    : $hook['function'][0] . '::' . $hook['function'][1])
                : $hook['function'];
            echo "  Priority $priority: $function_name\n";
        }
    }
} else {
    echo "  NO HOOKS FOUND!\n";
}

echo "\nwp_footer hooks (related to QR):\n";
if (isset($wp_filter['wp_footer'])) {
    foreach ($wp_filter['wp_footer'] as $priority => $hooks) {
        foreach ($hooks as $hook) {
            if (is_array($hook['function']) && isset($hook['function'][1])) {
                $function_name = $hook['function'][1];
                if (stripos($function_name, 'qr') !== false || stripos($function_name, 'viet') !== false) {
                    echo "  Priority $priority: $function_name\n";
                }
            } elseif (is_string($hook['function'])) {
                if (stripos($hook['function'], 'qr') !== false || stripos($hook['function'], 'viet') !== false) {
                    echo "  Priority $priority: {$hook['function']}\n";
                }
            }
        }
    }
}

// 2. Check payment gateways
echo "\n2. PAYMENT GATEWAYS STATUS:\n";
$gateways = WC()->payment_gateways()->payment_gateways();
foreach ($gateways as $id => $gateway) {
    if ($id === 'bacs' || $id === 'vcb-gateway-mh') {
        $enabled = $gateway->enabled === 'yes' ? '✓ ENABLED' : '✗ DISABLED';
        echo "  $id: $enabled - {$gateway->title}\n";
    }
}

// 3. Check recent order
echo "\n3. CHECK ORDER 7780:\n";
$order = wc_get_order(7780);
if ($order) {
    echo "  Payment method: " . $order->get_payment_method() . "\n";
    echo "  Payment title: " . $order->get_payment_method_title() . "\n";
    echo "  Status: " . $order->get_status() . "\n";
} else {
    echo "  Order not found\n";
}

// 4. Check file includes
echo "\n4. CHECK FILE INCLUDES:\n";
$functions_content = file_get_contents(get_stylesheet_directory() . '/functions.php');
if (strpos($functions_content, "require_once get_stylesheet_directory() . '/woocommerce-vietqr-integration.php'") !== false) {
    if (strpos($functions_content, "// require_once get_stylesheet_directory() . '/woocommerce-vietqr-integration.php'") !== false) {
        echo "  ✓ VietQR integration is COMMENTED OUT\n";
    } else {
        echo "  ❗ VietQR integration is STILL ACTIVE\n";
    }
} else {
    echo "  ✓ VietQR integration NOT FOUND in functions.php\n";
}

// 5. Check MU plugins
echo "\n5. MU-PLUGINS:\n";
$mu_plugins = glob(WPMU_PLUGIN_DIR . '/*.php');
foreach ($mu_plugins as $plugin) {
    echo "  - " . basename($plugin) . "\n";
}

// 6. Solutions
echo "\n=== GIẢI PHÁP ===\n\n";
echo "NGUYÊN NHÂN: Order 7780 được tạo qua 'elessi_simple_checkout' với payment method 'bacs'\n";
echo "nhưng VietQR integration đã bị disable, và VCB-MH không handle 'bacs'\n\n";

echo "GIẢI PHÁP:\n";
echo "1. Enable lại VietQR cũ tạm thời:\n";
echo "   - Bỏ comment // trước require_once VietQR integration\n\n";

echo "2. HOẶC update checkout để dùng VCB-MH:\n";
echo "   - Sửa woocommerce-ajax-checkout-bypass.php\n";
echo "   - Đổi 'payment_method' => 'bacs' thành 'payment_method' => 'vcb-gateway-mh'\n\n";

echo "3. HOẶC tạo order mới với checkout chuẩn:\n";
echo "   - Disable woocommerce-ajax-checkout-bypass.php\n";
echo "   - Dùng checkout form đầy đủ\n";
echo "   - Chọn payment method VCB-MH\n";

// 7. Check VCB-MH settings
echo "\n=== VCB-MH SETTINGS ===\n";
$vcb_settings = get_option('vcb_gw_settings');
if ($vcb_settings) {
    echo "Account Phone: " . ($vcb_settings['account']['phone'] ?? 'NOT SET') . "\n";
    echo "Account Name: " . ($vcb_settings['account']['name'] ?? 'NOT SET') . "\n";
    echo "Prefix: " . ($vcb_settings['prefix'] ?? '') . "\n";
} else {
    echo "❗ VCB-MH chưa được cấu hình!\n";
}
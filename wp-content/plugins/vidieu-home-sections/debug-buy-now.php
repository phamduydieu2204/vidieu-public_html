<?php
/**
 * Debug script to check Buy Now button status
 * 
 * Usage: Access this file directly in browser:
 * https://vidieu.vn/wp-content/plugins/vidieu-home-sections/debug-buy-now.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please login as admin.');
}

echo "<h1>Buy Now Button Debug Information</h1>";
echo "<pre style='background: #f5f5f5; padding: 20px;'>";

// 1. Check if Buy Now is enabled in settings
$options = get_option('vidieu_home_options', array());
$buy_now_enabled = isset($options['enable_buy_now']) ? $options['enable_buy_now'] : false;

echo "1. BUY NOW SETTINGS:\n";
echo "   - Option name: vidieu_home_options\n";
echo "   - Buy Now enabled: " . ($buy_now_enabled ? 'YES ✓' : 'NO ✗') . "\n";
echo "   - Full options: " . print_r($options, true) . "\n\n";

// 2. Check if Buy Now class is active
echo "2. BUY NOW CLASS STATUS:\n";
if (class_exists('VD_Buy_Now')) {
    $buy_now = VD_Buy_Now::get_instance();
    echo "   - Class loaded: YES ✓\n";
    echo "   - Is enabled method: " . ($buy_now->is_enabled() ? 'YES ✓' : 'NO ✗') . "\n";
} else {
    echo "   - Class loaded: NO ✗\n";
}
echo "\n";

// 3. Check filter status
echo "3. FILTER STATUS:\n";
$filter_value = apply_filters('vidieu_is_rendering_products', false);
echo "   - vidieu_is_rendering_products filter: " . ($filter_value ? 'TRUE' : 'FALSE') . "\n";
echo "   - Note: This filter must return TRUE for buttons to show\n\n";

// 4. Check if scripts/styles are enqueued
echo "4. ASSETS STATUS:\n";
global $wp_scripts, $wp_styles;

$scripts = array('vd-buynow-simple', 'vd-home-script', 'vd-buy-now-no-scroll');
foreach ($scripts as $script) {
    $registered = isset($wp_scripts->registered[$script]);
    echo "   - Script '$script': " . ($registered ? 'REGISTERED' : 'NOT REGISTERED') . "\n";
}

$styles = array('vd-buynow-simple');
foreach ($styles as $style) {
    $registered = isset($wp_styles->registered[$style]);
    echo "   - Style '$style': " . ($registered ? 'REGISTERED' : 'NOT REGISTERED') . "\n";
}

// 5. Instructions to enable
echo "\n5. HOW TO ENABLE BUY NOW:\n";
echo "   a) Go to WordPress Admin → Settings → Vidieu Home Sections\n";
echo "   b) Look for 'Buy Now Settings' section\n";
echo "   c) Check 'Enable Buy Now Button' checkbox\n";
echo "   d) Save changes\n\n";

echo "   OR run this in your theme/plugin:\n";
echo "   \$options = get_option('vidieu_home_options', array());\n";
echo "   \$options['enable_buy_now'] = true;\n";
echo "   update_option('vidieu_home_options', \$options);\n\n";

// 6. Quick enable button (if admin)
if (current_user_can('manage_options') && !$buy_now_enabled) {
    echo "6. QUICK ACTIONS:\n";
    echo '   <form method="post" style="display: inline;">';
    echo '   <input type="hidden" name="enable_buy_now_action" value="1">';
    echo '   <button type="submit" style="background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;">Enable Buy Now Now</button>';
    echo '   </form>';
    
    if (isset($_POST['enable_buy_now_action'])) {
        $options['enable_buy_now'] = true;
        update_option('vidieu_home_options', $options);
        echo "\n\n   ✓ Buy Now has been ENABLED! Refresh the page to see updated status.";
    }
}

echo "</pre>";

// Add CSS for better display
echo '<style>
body { font-family: monospace; margin: 20px; }
h1 { color: #23282d; }
pre { line-height: 1.6; }
</style>';
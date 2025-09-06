<?php
/**
 * Test script for V2 Stepped Optimization
 * Check status and results after safe deployment
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Admin access required');
}

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>V2 Stepped Optimization Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>';

echo "<h1>V2 Stepped Optimization Test Results</h1>";
echo "<p>Test time: " . date('Y-m-d H:i:s') . "</p>";

// Test results
$results = array();

// 1. Check MU-plugin status
echo "<h2>1. MU-Plugin Status</h2>";
$mu_plugin_safe = WPMU_PLUGIN_DIR . '/fix-404-resources-safe.php';
$mu_plugin_old = WPMU_PLUGIN_DIR . '/fix-404-resources.php';
$mu_plugin_off = WPMU_PLUGIN_DIR . '/fix-404-resources.off';

if (file_exists($mu_plugin_safe)) {
    echo "<p class='success'>✅ Safe MU-Plugin active: fix-404-resources-safe.php</p>";
    $results['mu_plugin'] = 'safe_active';
} elseif (file_exists($mu_plugin_old)) {
    echo "<p class='warning'>⚠️ Old MU-Plugin active (may cause issues)</p>";
    $results['mu_plugin'] = 'old_active';
} elseif (file_exists($mu_plugin_off)) {
    echo "<p class='error'>❌ MU-Plugin disabled (.off extension)</p>";
    $results['mu_plugin'] = 'disabled';
} else {
    echo "<p class='error'>❌ No MU-Plugin found</p>";
    $results['mu_plugin'] = 'missing';
}

// 2. Check V2 class status
echo "<h2>2. V2 Class Status</h2>";
if (class_exists('Vidieu_Dup_Requests_Guard_V2_Stepped')) {
    echo "<p class='success'>✅ V2 Stepped class loaded (safe version)</p>";
    $results['v2_class'] = 'stepped';
    
    // Check feature flags
    echo "<h3>Feature Flags:</h3>";
    echo "<ul>";
    echo "<li>Basic Optimization: " . (Vidieu_Dup_Requests_Guard_V2_Stepped::ENABLE_BASIC_OPTIMIZATION ? '✅ Enabled' : '❌ Disabled') . "</li>";
    echo "<li>Nuclear reCAPTCHA: " . (Vidieu_Dup_Requests_Guard_V2_Stepped::ENABLE_NUCLEAR_RECAPTCHA ? '✅ Enabled' : '❌ Disabled') . "</li>";
    echo "<li>Cart/Checkout Whitelist: " . (Vidieu_Dup_Requests_Guard_V2_Stepped::ENABLE_CART_CHECKOUT_WHITELIST ? '✅ Enabled' : '❌ Disabled') . "</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ V2 Stepped class NOT loaded</p>";
    $results['v2_class'] = 'not_loaded';
}

// 3. Quick 404 test
echo "<h2>3. 404 Fix Test</h2>";
$test_404_urls = array(
    '/wp-content/themes/elessi-theme/assets/fonts/main-font.woff2' => 'Font file',
    '/wp-content/themes/elessi-theme/style.min.css' => 'Minified CSS'
);

foreach ($test_404_urls as $path => $desc) {
    $url = home_url($path);
    $response = wp_remote_head($url, array('timeout' => 5));
    $code = wp_remote_retrieve_response_code($response);
    
    if (in_array($code, array(200, 301, 302))) {
        echo "<p class='success'>✅ $desc: HTTP $code (fixed)</p>";
        $results['404_' . md5($path)] = 'fixed';
    } else {
        echo "<p class='error'>❌ $desc: HTTP $code (still broken)</p>";
        $results['404_' . md5($path)] = 'broken';
    }
}

// 4. JavaScript test for reCAPTCHA
echo "<h2>4. reCAPTCHA Test (Client-side)</h2>";
echo "<div id='recaptcha-test'>Testing...</div>";
echo "<script>
// Count reCAPTCHA resources
setTimeout(function() {
    var scripts = document.getElementsByTagName('script');
    var recaptchaCount = 0;
    var recaptchaSources = [];
    
    for (var i = 0; i < scripts.length; i++) {
        if (scripts[i].src && (scripts[i].src.includes('recaptcha') || scripts[i].src.includes('gstatic'))) {
            recaptchaCount++;
            recaptchaSources.push(scripts[i].src);
        }
    }
    
    var testDiv = document.getElementById('recaptcha-test');
    if (recaptchaCount <= 1) {
        testDiv.innerHTML = '<span class=\"success\">✅ reCAPTCHA count: ' + recaptchaCount + ' (optimized)</span>';
    } else {
        testDiv.innerHTML = '<span class=\"error\">❌ reCAPTCHA count: ' + recaptchaCount + ' (needs optimization)</span>';
    }
    
    if (recaptchaSources.length > 0) {
        testDiv.innerHTML += '<br>Sources found:<ul>';
        recaptchaSources.forEach(function(src) {
            testDiv.innerHTML += '<li>' + src.substring(0, 80) + '...</li>';
        });
        testDiv.innerHTML += '</ul>';
    }
}, 1000);
</script>";

// 5. Performance metrics
echo "<h2>5. Current Page Metrics</h2>";
global $wp_scripts, $wp_styles;

$script_count = is_object($wp_scripts) ? count($wp_scripts->queue) : 0;
$style_count = is_object($wp_styles) ? count($wp_styles->queue) : 0;

echo "<table>";
echo "<tr><th>Metric</th><th>Count</th><th>Target</th></tr>";
echo "<tr><td>Scripts in queue</td><td>$script_count</td><td>< 50</td></tr>";
echo "<tr><td>Styles in queue</td><td>$style_count</td><td>< 40</td></tr>";
echo "</table>";

// 6. Route-specific recommendations
echo "<h2>6. Route-Specific Tests Needed</h2>";
echo "<p>Please manually test these routes:</p>";
echo "<table>";
echo "<tr><th>Route</th><th>URL</th><th>Target Requests</th><th>Check</th></tr>";
echo "<tr><td>Home</td><td><a href='" . home_url() . "' target='_blank'>" . home_url() . "</a></td><td>< 120</td><td>Network tab</td></tr>";
echo "<tr><td>Cart</td><td><a href='" . wc_get_cart_url() . "' target='_blank'>" . wc_get_cart_url() . "</a></td><td>< 150</td><td>Whitelist active?</td></tr>";
echo "<tr><td>Checkout</td><td><a href='" . wc_get_checkout_url() . "' target='_blank'>" . wc_get_checkout_url() . "</a></td><td>< 180</td><td>Payment works?</td></tr>";
echo "<tr><td>Contact</td><td><a href='" . home_url('/contact/') . "' target='_blank'>" . home_url('/contact/') . "</a></td><td>< 100</td><td>Form works?</td></tr>";
echo "</table>";

// 7. Admin instructions
echo "<h2>7. Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Enable Safe MU-Plugin:</strong><br><code>mv fix-404-resources.off fix-404-resources-safe.php</code></li>";
echo "<li><strong>Test each route</strong> with Network tab open (filter: status-code:404)</li>";
echo "<li><strong>Check reCAPTCHA:</strong><br>Console: <code>performance.getEntriesByType('resource').filter(e=>e.name.includes('recaptcha')).length</code></li>";
echo "<li><strong>Monitor admin footer</strong> for optimization report (view source)</li>";
echo "</ol>";

// Kill switch reminder
echo "<h2>8. Emergency Rollback</h2>";
echo "<pre>";
echo "// Add to wp-config.php if issues occur:\n";
echo "define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);\n";
echo "\n// Or disable MU-plugin:\n";
echo "mv fix-404-resources-safe.php fix-404-resources-safe.off";
echo "</pre>";

echo "</body></html>";

// Save test results
$results_file = dirname(__FILE__) . '/outputs/test-v2-stepped-' . date('Y-m-d-His') . '.json';
file_put_contents($results_file, json_encode($results, JSON_PRETTY_PRINT));
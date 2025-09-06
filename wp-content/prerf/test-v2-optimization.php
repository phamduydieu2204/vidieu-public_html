<?php
/**
 * Test script for V2 Optimization
 * Run this to verify optimizations are working
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Admin access required');
}

// Test results array
$test_results = array();

echo "<h1>V2 Optimization Test Results</h1>";
echo "<p>Testing at: " . date('Y-m-d H:i:s') . "</p>";

// 1. Check if MU-plugin is active
echo "<h2>1. MU-Plugin Status</h2>";
$mu_plugin = WPMU_PLUGIN_DIR . '/fix-404-resources.php';
if (file_exists($mu_plugin)) {
    echo "✅ MU-Plugin exists at: $mu_plugin<br>";
    $test_results['mu_plugin'] = 'active';
} else {
    echo "❌ MU-Plugin NOT FOUND at: $mu_plugin<br>";
    $test_results['mu_plugin'] = 'missing';
}

// 2. Check V2 Enhanced class
echo "<h2>2. V2 Enhanced Class Status</h2>";
if (class_exists('Vidieu_Dup_Requests_Guard_V2_Enhanced')) {
    echo "✅ V2 Enhanced class loaded<br>";
    $test_results['v2_class'] = 'loaded';
} elseif (class_exists('Vidieu_Dup_Requests_Guard_V2')) {
    echo "⚠️ V2 class loaded (not Enhanced)<br>";
    $test_results['v2_class'] = 'v2_basic';
} elseif (class_exists('Vidieu_Dup_Requests_Guard')) {
    echo "⚠️ V1 class loaded<br>";
    $test_results['v2_class'] = 'v1';
} else {
    echo "❌ No optimization class loaded<br>";
    $test_results['v2_class'] = 'none';
}

// 3. Test 404 fixes
echo "<h2>3. Testing 404 Fixes</h2>";
$test_urls = array(
    '/wp-content/themes/elessi-theme/assets/fonts/main-font.woff2',
    '/wp-content/themes/elessi-theme/style.min.css'
);

foreach ($test_urls as $url) {
    $full_url = home_url($url);
    $response = wp_remote_head($full_url);
    $code = wp_remote_retrieve_response_code($response);
    
    if ($code == 200 || $code == 301 || $code == 302) {
        echo "✅ $url - Status: $code<br>";
        $test_results['404_' . basename($url)] = 'fixed';
    } else {
        echo "❌ $url - Status: $code<br>";
        $test_results['404_' . basename($url)] = 'still_404';
    }
}

// 4. Check reCAPTCHA on different pages
echo "<h2>4. reCAPTCHA Check</h2>";
$pages_to_check = array(
    '/' => 'Home',
    '/contact/' => 'Contact', 
    '/cart/' => 'Cart',
    '/checkout/' => 'Checkout'
);

foreach ($pages_to_check as $path => $name) {
    $url = home_url($path);
    $response = wp_remote_get($url);
    $body = wp_remote_retrieve_body($response);
    
    // Count reCAPTCHA occurrences
    $recaptcha_count = substr_count($body, 'recaptcha');
    $grecaptcha_count = substr_count($body, 'grecaptcha');
    $total = $recaptcha_count + $grecaptcha_count;
    
    if ($total <= 2) {
        echo "✅ $name page: $total reCAPTCHA references<br>";
    } else {
        echo "❌ $name page: $total reCAPTCHA references (should be ≤2)<br>";
    }
    
    $test_results['recaptcha_' . $name] = $total;
}

// 5. Check kill switch
echo "<h2>5. Kill Switch Status</h2>";
if (defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION) {
    echo "⚠️ Kill switch is ACTIVE - optimizations disabled<br>";
    $test_results['kill_switch'] = 'active';
} else {
    echo "✅ Kill switch inactive - optimizations enabled<br>";
    $test_results['kill_switch'] = 'inactive';
}

// 6. Performance metrics
echo "<h2>6. Performance Metrics (estimates)</h2>";
global $wp_scripts, $wp_styles;

$script_count = count($wp_scripts->queue);
$style_count = count($wp_styles->queue);

echo "Scripts in queue: $script_count<br>";
echo "Styles in queue: $style_count<br>";

// Summary
echo "<h2>Test Summary</h2>";
$passed = 0;
$failed = 0;

foreach ($test_results as $test => $result) {
    if (in_array($result, ['active', 'loaded', 'fixed', 'inactive']) || 
        (strpos($test, 'recaptcha') !== false && $result <= 2)) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "<h3>Results: $passed passed, $failed failed</h3>";

// Save results
$results_file = dirname(__FILE__) . '/outputs/test-results-' . date('Y-m-d-His') . '.json';
file_put_contents($results_file, json_encode($test_results, JSON_PRETTY_PRINT));
echo "<p>Results saved to: $results_file</p>";

// Recommendations
echo "<h2>Recommendations</h2>";
if ($test_results['mu_plugin'] !== 'active') {
    echo "⚠️ Create MU-plugin for 404 fixes<br>";
}
if ($test_results['v2_class'] !== 'loaded') {
    echo "⚠️ Ensure V2 Enhanced class is loading<br>";
}
if (isset($test_results['404_main-font.woff2']) && $test_results['404_main-font.woff2'] !== 'fixed') {
    echo "⚠️ Font 404 still not fixed - check MU-plugin<br>";
}

echo "<hr>";
echo "<p><strong>Next steps:</strong> Collect new HAR files and compare metrics</p>";
<?php
/**
 * Analyze Cart/Checkout Scripts and Styles
 * Generate whitelist based on actual handles
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
    <title>Cart/Checkout Asset Analysis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .essential { background: #d4f4dd; }
        .maybe { background: #fff3cd; }
        .remove { background: #f8d7da; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>';

echo '<h1>Cart/Checkout Asset Analysis</h1>';
echo '<p>Analyze which scripts and styles are actually loaded on cart/checkout pages</p>';

// Function to analyze assets
function analyze_page_assets($page_name, $url) {
    global $wp_scripts, $wp_styles;
    
    echo "<h2>$page_name Page Analysis</h2>";
    echo "<p>URL: <a href='$url' target='_blank'>$url</a></p>";
    
    // Get page content
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        echo '<p>Error fetching page</p>';
        return;
    }
    
    // Current loaded scripts
    echo '<h3>Currently Loaded Scripts</h3>';
    echo '<table>';
    echo '<tr><th>Handle</th><th>Source</th><th>Dependencies</th><th>Recommendation</th></tr>';
    
    $scripts_data = array();
    
    foreach ($wp_scripts->queue as $handle) {
        if (isset($wp_scripts->registered[$handle])) {
            $script = $wp_scripts->registered[$handle];
            $src = $script->src ?? 'inline';
            $deps = implode(', ', $script->deps ?? array());
            
            // Determine source
            $source = 'unknown';
            if (strpos($src, 'wp-includes') !== false) {
                $source = 'WordPress Core';
            } elseif (strpos($src, 'woocommerce') !== false) {
                $source = 'WooCommerce';
            } elseif (strpos($src, 'elessi') !== false) {
                $source = 'Elessi Theme';
            } elseif (strpos($src, 'elementor') !== false) {
                $source = 'Elementor';
            } elseif (strpos($src, 'plugins/') !== false) {
                preg_match('/plugins\/([^\/]+)/', $src, $matches);
                $source = 'Plugin: ' . ($matches[1] ?? 'unknown');
            }
            
            // Recommendation
            $recommendation = 'remove';
            $class = 'remove';
            
            // Essential patterns
            $essential_patterns = array(
                'jquery', 'woocommerce', 'wc-cart', 'wc-checkout', 
                'selectWoo', 'js-cookie', 'wp-i18n'
            );
            
            foreach ($essential_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false) {
                    $recommendation = 'keep';
                    $class = 'essential';
                    break;
                }
            }
            
            // Maybe keep
            if (strpos($handle, 'elessi') !== false || strpos($handle, 'payment') !== false) {
                $recommendation = 'maybe';
                $class = 'maybe';
            }
            
            echo "<tr class='$class'>";
            echo "<td>$handle</td>";
            echo "<td>$source</td>";
            echo "<td>$deps</td>";
            echo "<td>$recommendation</td>";
            echo '</tr>';
            
            $scripts_data[] = array(
                'handle' => $handle,
                'source' => $source,
                'recommendation' => $recommendation
            );
        }
    }
    
    echo '</table>';
    
    // Current loaded styles
    echo '<h3>Currently Loaded Styles</h3>';
    echo '<table>';
    echo '<tr><th>Handle</th><th>Source</th><th>Dependencies</th><th>Recommendation</th></tr>';
    
    $styles_data = array();
    
    foreach ($wp_styles->queue as $handle) {
        if (isset($wp_styles->registered[$handle])) {
            $style = $wp_styles->registered[$handle];
            $src = $style->src ?? 'inline';
            $deps = implode(', ', $style->deps ?? array());
            
            // Determine source
            $source = 'unknown';
            if (strpos($src, 'wp-includes') !== false) {
                $source = 'WordPress Core';
            } elseif (strpos($src, 'woocommerce') !== false) {
                $source = 'WooCommerce';
            } elseif (strpos($src, 'elessi') !== false) {
                $source = 'Elessi Theme';
            } elseif (strpos($src, 'elementor') !== false) {
                $source = 'Elementor';
            } elseif (strpos($src, 'plugins/') !== false) {
                preg_match('/plugins\/([^\/]+)/', $src, $matches);
                $source = 'Plugin: ' . ($matches[1] ?? 'unknown');
            }
            
            // Recommendation
            $recommendation = 'remove';
            $class = 'remove';
            
            // Essential patterns
            $essential_patterns = array(
                'woocommerce', 'elessi-style'
            );
            
            foreach ($essential_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false) {
                    $recommendation = 'keep';
                    $class = 'essential';
                    break;
                }
            }
            
            echo "<tr class='$class'>";
            echo "<td>$handle</td>";
            echo "<td>$source</td>";
            echo "<td>$deps</td>";
            echo "<td>$recommendation</td>";
            echo '</tr>';
            
            $styles_data[] = array(
                'handle' => $handle,
                'source' => $source,
                'recommendation' => $recommendation
            );
        }
    }
    
    echo '</table>';
    
    return array(
        'scripts' => $scripts_data,
        'styles' => $styles_data
    );
}

// Analyze Cart page
$cart_data = analyze_page_assets('Cart', wc_get_cart_url());

// Analyze Checkout page
$checkout_data = analyze_page_assets('Checkout', wc_get_checkout_url());

// Generate recommended whitelist
echo '<h2>Recommended Whitelist Code</h2>';
echo '<pre>';
echo '// Cart Scripts Whitelist
$cart_scripts_whitelist = array(
    // Core
    \'jquery\',
    \'jquery-core\',
    \'jquery-migrate\',
    \'js-cookie\',
    
    // WooCommerce
    \'woocommerce\',
    \'wc-add-to-cart\',
    \'wc-cart-fragments\',
    \'selectWoo\',
    \'wc-country-select\',
    \'wc-address-i18n\',
    
    // i18n
    \'wp-i18n\',
    
    // Theme
    \'elessi-theme-js\'
);

// Checkout Scripts Whitelist
$checkout_scripts_whitelist = array(
    // Core
    \'jquery\',
    \'jquery-core\',
    \'jquery-migrate\',
    \'js-cookie\',
    
    // WooCommerce
    \'woocommerce\',
    \'wc-cart-fragments\',
    \'wc-checkout\',
    \'selectWoo\',
    \'wc-country-select\',
    \'wc-address-i18n\',
    \'wc-password-strength-meter\',
    
    // i18n
    \'wp-i18n\',
    
    // Theme
    \'elessi-theme-js\'
);

// Cart Styles Whitelist
$cart_styles_whitelist = array(
    \'woocommerce-general\',
    \'woocommerce-layout\',
    \'woocommerce-smallscreen\',
    \'elessi-style\',
    \'elessi-style-child\'
);

// Checkout Styles Whitelist
$checkout_styles_whitelist = array(
    \'woocommerce-general\',
    \'woocommerce-layout\',
    \'woocommerce-smallscreen\',
    \'elessi-style\',
    \'elessi-style-child\'
);
</pre>';

// Domain analysis
echo '<h2>Domain Analysis Instructions</h2>';
echo '<p>To analyze domains making requests:</p>';
echo '<ol>';
echo '<li>Open Cart or Checkout page</li>';
echo '<li>Open Browser Console (F12)</li>';
echo '<li>Run this code:</li>';
echo '</ol>';
echo '<pre>';
echo "// Domain analysis code
var resources = performance.getEntriesByType('resource');
var domains = {};

resources.forEach(function(r) {
    var url = new URL(r.name);
    var domain = url.hostname;
    domains[domain] = (domains[domain] || 0) + 1;
});

// Sort by count
var sorted = Object.entries(domains).sort((a,b) => b[1] - a[1]);

console.table(sorted.map(d => ({Domain: d[0], Requests: d[1]})));
console.log('Total requests:', resources.length);
";
echo '</pre>';

// Output buffer domains to block
echo '<h2>Domains to Block via Output Buffer</h2>';
echo '<ul>';
$block_domains = array(
    'elementor',
    'uael',
    'revslider',
    'instagram',
    'yith',
    'analytics',
    'googletagmanager',
    'facebook',
    'twitter',
    'pinterest',
    'tiktok',
    'snapchat'
);
foreach ($block_domains as $domain) {
    echo "<li>$domain</li>";
}
echo '</ul>';

echo '</body></html>';
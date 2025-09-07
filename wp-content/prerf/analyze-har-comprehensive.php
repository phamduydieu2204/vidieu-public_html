<?php
/**
 * Comprehensive HAR Analysis for All 7 Routes
 * Analyzes: home, product, post, contact, cart, checkout, order-received
 */

// Define routes and HAR files
$routes = array(
    'Home' => 'trang-chu.har',
    'Product' => 'trang-san-pham.har', 
    'Post' => 'trang-bai-viet.har',
    'Contact' => 'trang-contact.har',
    'Cart' => 'trang-cart.har',
    'Checkout' => 'trang-checkout.har',
    'Order-received' => 'trang-order-received.har'
);

// Analysis results
$results = array();

// Analyze each route
foreach ($routes as $route => $har_file) {
    $har_path = __DIR__ . '/inputs/' . $har_file;
    
    if (!file_exists($har_path)) {
        echo "Warning: HAR file not found for $route\n";
        continue;
    }
    
    $har_content = file_get_contents($har_path);
    $har_data = json_decode($har_content, true);
    
    if (!$har_data || !isset($har_data['log']['entries'])) {
        echo "Warning: Invalid HAR format for $route\n";
        continue;
    }
    
    $entries = $har_data['log']['entries'];
    
    // Initialize counters
    $stats = array(
        'total_requests' => count($entries),
        'total_size' => 0,
        'total_time' => 0,
        'errors_404' => 0,
        'errors_other' => 0,
        'ajax_requests' => 0,
        'css_files' => 0,
        'js_files' => 0,
        'image_files' => 0,
        'font_files' => 0,
        'recaptcha_loads' => 0,
        'domains' => array(),
        'scripts_detail' => array(),
        'styles_detail' => array(),
        'blocked_domains_found' => array()
    );
    
    // Blocked domains to check
    $blocked_domains = array(
        'elementor', 'uael', 'revslider', 'instagram',
        'yith', 'facebook', 'twitter', 'analytics',
        'googletagmanager', 'hotjar', 'mixpanel'
    );
    
    // Analyze each request
    foreach ($entries as $entry) {
        $request = $entry['request'];
        $response = $entry['response'];
        $url = $request['url'];
        $method = $request['method'];
        $status = $response['status'];
        $size = $response['content']['size'] ?? 0;
        $time = $entry['time'] ?? 0;
        
        // Update totals
        $stats['total_size'] += $size;
        $stats['total_time'] += $time;
        
        // Check status
        if ($status == 404) {
            $stats['errors_404']++;
            $stats['404_urls'][] = parse_url($url, PHP_URL_PATH);
        } elseif ($status >= 400) {
            $stats['errors_other']++;
        }
        
        // Check if AJAX
        $headers = array_column($request['headers'], 'value', 'name');
        if (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] === 'XMLHttpRequest') {
            $stats['ajax_requests']++;
        }
        
        // Categorize by type
        $path = parse_url($url, PHP_URL_PATH);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        
        if ($ext === 'css' || strpos($url, '.css?') !== false) {
            $stats['css_files']++;
            $stats['styles_detail'][] = basename($path);
        } elseif ($ext === 'js' || strpos($url, '.js?') !== false) {
            $stats['js_files']++;
            $stats['scripts_detail'][] = basename($path);
            
            // Check for reCAPTCHA
            if (strpos($url, 'recaptcha') !== false || strpos($url, 'grecaptcha') !== false) {
                $stats['recaptcha_loads']++;
                $stats['recaptcha_urls'][] = $url;
            }
        } elseif (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'))) {
            $stats['image_files']++;
        } elseif (in_array($ext, array('woff', 'woff2', 'ttf', 'eot'))) {
            $stats['font_files']++;
        }
        
        // Track domains
        $host = parse_url($url, PHP_URL_HOST);
        if ($host) {
            if (!isset($stats['domains'][$host])) {
                $stats['domains'][$host] = 0;
            }
            $stats['domains'][$host]++;
            
            // Check blocked domains
            foreach ($blocked_domains as $blocked) {
                if (strpos($host, $blocked) !== false || strpos($url, $blocked) !== false) {
                    $stats['blocked_domains_found'][$blocked] = true;
                }
            }
        }
    }
    
    // Sort domains by request count
    arsort($stats['domains']);
    $stats['blocked_domains_found'] = array_keys($stats['blocked_domains_found']);
    
    // Convert size to KB
    $stats['total_size_kb'] = round($stats['total_size'] / 1024, 2);
    $stats['total_time_ms'] = round($stats['total_time'], 2);
    
    $results[$route] = $stats;
}

// Generate output
$output = array(
    'analysis_date' => date('Y-m-d H:i:s'),
    'routes' => $results,
    'summary_table' => array(),
    'focus_routes' => array('Cart', 'Checkout', 'Order-received'),
    'recommendations' => array()
);

// Create summary table
echo "=== ROUTE × TOTALS ANALYSIS ===\n\n";
echo sprintf("%-15s | %-8s | %-10s | %-8s | %-5s | %-5s | %-8s | %-8s | %-10s\n",
    "Route", "Requests", "Size (KB)", "Time (ms)", "404s", "AJAX", "CSS", "JS", "reCAPTCHA"
);
echo str_repeat("-", 100) . "\n";

foreach ($results as $route => $stats) {
    echo sprintf("%-15s | %-8d | %-10.2f | %-8.2f | %-5d | %-5d | %-8d | %-8d | %-10d\n",
        $route,
        $stats['total_requests'],
        $stats['total_size_kb'],
        $stats['total_time_ms'],
        $stats['errors_404'],
        $stats['ajax_requests'],
        $stats['css_files'],
        $stats['js_files'],
        $stats['recaptcha_loads']
    );
    
    $output['summary_table'][$route] = array(
        'requests' => $stats['total_requests'],
        'size_kb' => $stats['total_size_kb'],
        'time_ms' => $stats['total_time_ms'],
        'errors_404' => $stats['errors_404'],
        'ajax' => $stats['ajax_requests'],
        'css' => $stats['css_files'],
        'js' => $stats['js_files'],
        'recaptcha' => $stats['recaptcha_loads']
    );
}

echo "\n\n=== FOCUS ROUTES DETAIL (Cart, Checkout, Order-received) ===\n";

foreach (array('Cart', 'Checkout', 'Order-received') as $route) {
    if (!isset($results[$route])) continue;
    
    $stats = $results[$route];
    echo "\n--- $route Page ---\n";
    echo "Total Requests: " . $stats['total_requests'] . "\n";
    echo "Target: " . ($route === 'Cart' ? '<150' : ($route === 'Checkout' ? '<180' : '<160')) . "\n";
    echo "Status: " . ($route === 'Cart' && $stats['total_requests'] < 150 ? 'PASS' : 
                      ($route === 'Checkout' && $stats['total_requests'] < 180 ? 'PASS' :
                      ($route === 'Order-received' && $stats['total_requests'] < 160 ? 'PASS' : 'FAIL'))) . "\n";
    
    if (!empty($stats['404_urls'])) {
        echo "\n404 Errors:\n";
        foreach (array_unique($stats['404_urls']) as $url) {
            echo "  - $url\n";
        }
    }
    
    if (!empty($stats['blocked_domains_found'])) {
        echo "\nBlocked Domains Still Loading:\n";
        foreach ($stats['blocked_domains_found'] as $domain) {
            echo "  - $domain\n";
        }
    }
    
    echo "\nTop 10 Domains by Request Count:\n";
    $domain_count = 0;
    foreach ($stats['domains'] as $domain => $count) {
        echo sprintf("  %-40s : %d requests\n", $domain, $count);
        if (++$domain_count >= 10) break;
    }
    
    // Extract handles for whitelist
    echo "\nScripts Found (" . $stats['js_files'] . " total):\n";
    $script_handles = array();
    foreach (array_unique($stats['scripts_detail']) as $script) {
        // Try to extract handle from filename
        $handle = str_replace(array('.min.js', '.js'), '', $script);
        $handle = preg_replace('/\?.*$/', '', $handle);
        
        // Categorize
        $category = 'other';
        if (strpos($script, 'jquery') !== false) $category = 'jquery';
        elseif (strpos($script, 'wc-') !== false || strpos($script, 'woocommerce') !== false) $category = 'woocommerce';
        elseif (strpos($script, 'select') !== false) $category = 'woocommerce';
        elseif (strpos($script, 'elessi') !== false) $category = 'theme';
        elseif (strpos($script, 'wp-') !== false) $category = 'wordpress';
        elseif (strpos($script, 'elementor') !== false) $category = 'elementor';
        
        $script_handles[$category][] = $handle;
    }
    
    foreach ($script_handles as $category => $handles) {
        echo "  [$category]: " . implode(', ', array_unique($handles)) . "\n";
    }
}

// Save detailed results
$json_output = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents(__DIR__ . '/outputs/har-analysis-comprehensive.json', $json_output);

echo "\n\n=== WHITELIST RECOMMENDATIONS ===\n";
echo "Based on actual data from Cart/Checkout/Order-received:\n\n";

// Generate recommended whitelist
$essential_scripts = array(
    '// Core WordPress & jQuery',
    "'jquery'",
    "'jquery-core'", 
    "'jquery-migrate'",
    "'jquery-blockui'",
    "'js-cookie'",
    "'underscore'",
    "'wp-util'",
    '',
    '// WooCommerce Core',
    "'woocommerce'",
    "'wc-cart-fragments'",
    "'wc-add-to-cart'",
    "'wc-single-product'",
    "'accounting'",
    "'round'",
    '',
    '// WooCommerce Cart',
    "'wc-cart'",
    "'wc-country-select'",
    "'wc-address-i18n'",
    '',
    '// WooCommerce Checkout', 
    "'wc-checkout'",
    "'wc-password-strength-meter'",
    "'wc-credit-card-form'",
    "'jquery-payment'",
    '',
    '// SelectWoo',
    "'selectWoo'",
    "'select2'",
    '',
    '// i18n',
    "'wp-i18n'",
    "'wp-hooks'",
    "'wp-polyfill'",
    '',
    '// Theme Essentials Only',
    "'elessi-theme-js'",
    "'elessi-functions-js'"
);

echo "```php\n";
echo "\$essential_scripts = array(\n";
foreach ($essential_scripts as $script) {
    echo "    $script\n";
}
echo ");\n```\n";

echo "\n=== ANALYSIS COMPLETE ===\n";
echo "Results saved to: outputs/har-analysis-comprehensive.json\n";
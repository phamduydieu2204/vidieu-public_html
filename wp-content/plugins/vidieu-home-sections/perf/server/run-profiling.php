<?php
/**
 * Performance Profiling Runner
 * 
 * Run this script to automatically profile all tracked routes
 * Usage: php run-profiling.php
 */

// Ensure we're running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "=== Vidieu Performance Profiling Runner ===\n\n";

// Routes to test
$routes = [
    'https://vidieu.vn/' => 'home',
    'https://vidieu.vn/san-pham/' => 'shop',
    'https://vidieu.vn/bai-viet/' => 'blog',
    'https://vidieu.vn/contact/' => 'contact',
    'https://vidieu.vn/cart/' => 'cart',
    'https://vidieu.vn/checkout/' => 'checkout',
    'https://vidieu.vn/my-account/' => 'account',
    'https://vidieu.vn/product/bao-cao-loi-nhuan-sau-ban-hang-amazon-fba/' => 'product'
];

// Number of requests per route
$requests_per_route = 3;

echo "Testing " . count($routes) . " routes with {$requests_per_route} requests each...\n\n";

// User agent to simulate real browser
$user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

// Test each route
foreach ($routes as $url => $name) {
    echo "Testing {$name}: {$url}\n";
    
    for ($i = 1; $i <= $requests_per_route; $i++) {
        echo "  Request {$i}/{$requests_per_route}... ";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Add cookie to simulate logged-in user for account pages
        if (in_array($name, ['cart', 'checkout', 'account'])) {
            curl_setopt($ch, CURLOPT_COOKIE, 'wordpress_test_cookie=WP+Cookie+check');
        }
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $total_time = $end_time - $start_time;
        
        curl_close($ch);
        
        if ($http_code == 200) {
            echo "OK (Time: " . number_format($total_time, 2) . "s)\n";
        } else {
            echo "ERROR (HTTP {$http_code})\n";
        }
        
        // Small delay between requests
        if ($i < $requests_per_route) {
            sleep(1);
        }
    }
    
    echo "\n";
    
    // Delay between routes
    sleep(2);
}

echo "\nProfiling complete!\n";
echo "Check the log files in: wp-content/plugins/vidieu-home-sections/perf/server/logs/\n";
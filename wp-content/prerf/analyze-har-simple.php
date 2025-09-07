<?php
/**
 * Simple HAR Analysis for Vidieu.vn
 * Focus on key metrics for optimization
 */

$routes = array(
    'Home' => 'trang-chu.har',
    'Product' => 'trang-san-pham.har',
    'Post' => 'trang-bai-viet.har',
    'Contact' => 'trang-contact.har',
    'Cart' => 'trang-cart.har',
    'Checkout' => 'trang-checkout.har',
    'Order-received' => 'trang-order-received.har'
);

$results = array();
$blocked_domains = array('elementor', 'uael', 'revslider', 'instagram', 'yith', 'facebook', 'twitter', 'analytics', 'googletagmanager');

foreach ($routes as $route => $har_file) {
    $har_path = __DIR__ . '/inputs/' . $har_file;
    if (!file_exists($har_path)) continue;
    
    $content = file_get_contents($har_path);
    $data = json_decode($content, true);
    if (!$data) continue;
    
    $entries = $data['log']['entries'] ?? array();
    
    $stats = array(
        'total' => count($entries),
        'size_kb' => 0,
        'time_ms' => 0,
        '404' => 0,
        'css' => 0,
        'js' => 0,
        'recaptcha' => 0,
        'domains' => array(),
        'blocked_found' => array()
    );
    
    foreach ($entries as $entry) {
        $url = $entry['request']['url'] ?? '';
        $status = $entry['response']['status'] ?? 0;
        $size = $entry['response']['content']['size'] ?? 0;
        $time = $entry['time'] ?? 0;
        
        $stats['size_kb'] += $size / 1024;
        $stats['time_ms'] += $time;
        
        if ($status == 404) $stats['404']++;
        
        if (strpos($url, '.css') !== false) $stats['css']++;
        elseif (strpos($url, '.js') !== false) $stats['js']++;
        
        if (strpos($url, 'recaptcha') !== false || strpos($url, 'grecaptcha') !== false) {
            $stats['recaptcha']++;
        }
        
        $host = parse_url($url, PHP_URL_HOST);
        if ($host) {
            $stats['domains'][$host] = ($stats['domains'][$host] ?? 0) + 1;
            
            foreach ($blocked_domains as $blocked) {
                if (strpos($url, $blocked) !== false) {
                    $stats['blocked_found'][$blocked] = true;
                }
            }
        }
    }
    
    $stats['size_kb'] = round($stats['size_kb'], 2);
    $stats['time_ms'] = round($stats['time_ms'], 2);
    $stats['blocked_found'] = array_keys($stats['blocked_found']);
    arsort($stats['domains']);
    
    $results[$route] = $stats;
}

// Output results
echo "=== ROUTE × TOTALS ANALYSIS ===\n\n";
echo "Route          | Requests | Size(KB) | Time(ms) | 404s | CSS | JS  | reCAPTCHA\n";
echo "---------------|----------|----------|----------|------|-----|-----|----------\n";

foreach ($results as $route => $stats) {
    printf("%-14s | %8d | %8.2f | %8.2f | %4d | %3d | %3d | %9d\n",
        $route, $stats['total'], $stats['size_kb'], $stats['time_ms'],
        $stats['404'], $stats['css'], $stats['js'], $stats['recaptcha']
    );
}

echo "\n\n=== FOCUS ROUTES (Cart, Checkout, Order-received) ===\n";

foreach (array('Cart', 'Checkout', 'Order-received') as $route) {
    if (!isset($results[$route])) continue;
    
    $stats = $results[$route];
    $target = $route === 'Cart' ? 150 : ($route === 'Checkout' ? 180 : 160);
    $status = $stats['total'] < $target ? 'PASS' : 'FAIL';
    
    echo "\n$route: {$stats['total']} requests (Target: <$target) - $status\n";
    
    if (!empty($stats['blocked_found'])) {
        echo "Blocked domains still loading: " . implode(', ', $stats['blocked_found']) . "\n";
    }
    
    echo "Top domains: ";
    $top = array_slice($stats['domains'], 0, 5, true);
    foreach ($top as $domain => $count) {
        echo "$domain($count) ";
    }
    echo "\n";
}

// Save JSON
$output = array(
    'date' => date('Y-m-d H:i:s'),
    'results' => $results
);
file_put_contents(__DIR__ . '/outputs/har-analysis-simple.json', json_encode($output, JSON_PRETTY_PRINT));
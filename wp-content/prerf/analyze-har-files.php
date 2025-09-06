<?php
/**
 * HAR File Analysis Script
 * Analyzes HAR files for performance metrics
 */

// HAR files to analyze
$harFiles = [
    'home' => 'trang-chu.har',
    'products' => 'trang-san-pham.har', 
    'post' => 'trang-bai-viet.har',
    'contact' => 'trang-contact.har',
    'cart' => 'trang-cart.har',
    'checkout' => 'trang-checkout.har'
];

$results = [];

foreach ($harFiles as $route => $filename) {
    $filePath = __DIR__ . '/inputs/' . $filename;
    
    if (!file_exists($filePath)) {
        echo "HAR file not found: $filePath\n";
        continue;
    }
    
    $harContent = file_get_contents($filePath);
    $harData = json_decode($harContent, true);
    
    if (!$harData || !isset($harData['log']['entries'])) {
        echo "Invalid HAR format for: $filename\n";
        continue;
    }
    
    $entries = $harData['log']['entries'];
    $totalRequests = count($entries);
    
    // Initialize counters
    $errors404 = [];
    $recaptchaCount = 0;
    $cssCount = 0;
    $jsCount = 0;
    $duplicates = [];
    $urlCounts = [];
    
    // Analyze each request
    foreach ($entries as $entry) {
        $url = $entry['request']['url'];
        $status = $entry['response']['status'];
        $mimeType = $entry['response']['content']['mimeType'] ?? '';
        
        // Count URLs for duplicate detection
        $urlCounts[$url] = ($urlCounts[$url] ?? 0) + 1;
        
        // Check for 404 errors
        if ($status == 404) {
            $errors404[] = parse_url($url, PHP_URL_PATH) ?: $url;
        }
        
        // Check for reCAPTCHA and gstatic
        if (stripos($url, 'recaptcha') !== false || stripos($url, 'gstatic.com') !== false) {
            $recaptchaCount++;
        }
        
        // Count CSS files
        if (stripos($mimeType, 'css') !== false || 
            preg_match('/\.css(\?|$)/', $url)) {
            $cssCount++;
        }
        
        // Count JS files
        if (stripos($mimeType, 'javascript') !== false || 
            stripos($mimeType, 'ecmascript') !== false ||
            preg_match('/\.js(\?|$)/', $url)) {
            $jsCount++;
        }
    }
    
    // Find duplicates
    foreach ($urlCounts as $url => $count) {
        if ($count > 1) {
            $duplicates[$url] = $count;
        }
    }
    
    $results[$route] = [
        'total_requests' => $totalRequests,
        '404_errors' => [
            'count' => count($errors404),
            'files' => $errors404
        ],
        'recaptcha_loads' => $recaptchaCount,
        'css_files' => $cssCount,
        'js_files' => $jsCount,
        'duplicate_requests' => [
            'count' => count($duplicates),
            'details' => $duplicates
        ]
    ];
}

// Previous V2 baseline data
$previousV2 = [
    '404_errors' => '2-4',
    'recaptcha_loads' => '14-27',
    'cart_requests' => 251,
    'checkout_requests' => 242
];

// Target metrics
$targets = [
    '404_errors' => 0,
    'recaptcha_loads' => 1,
    'cart_requests' => '<150',
    'checkout_requests' => '<180'
];

// Display results
echo "\n=== HAR FILE ANALYSIS RESULTS ===\n\n";

foreach ($results as $route => $metrics) {
    echo "--- $route ---\n";
    echo "Total Requests: {$metrics['total_requests']}\n";
    echo "404 Errors: {$metrics['404_errors']['count']}";
    if (!empty($metrics['404_errors']['files'])) {
        echo " (Files: " . implode(', ', array_slice($metrics['404_errors']['files'], 0, 3));
        if (count($metrics['404_errors']['files']) > 3) {
            echo ", +" . (count($metrics['404_errors']['files']) - 3) . " more";
        }
        echo ")";
    }
    echo "\n";
    echo "reCAPTCHA Loads: {$metrics['recaptcha_loads']}\n";
    echo "CSS Files: {$metrics['css_files']}\n";
    echo "JS Files: {$metrics['js_files']}\n";
    echo "Duplicate Requests: {$metrics['duplicate_requests']['count']}\n";
    echo "\n";
}

// Comparison Summary
echo "=== COMPARISON WITH TARGETS ===\n\n";
echo "Previous V2 Baseline:\n";
echo "- 404 Errors: 2-4\n";
echo "- reCAPTCHA: 14-27\n";
echo "- Cart: 251 requests\n";
echo "- Checkout: 242 requests\n\n";

echo "Current Results:\n";
$all404s = 0;
$allRecaptcha = 0;
foreach ($results as $route => $metrics) {
    $all404s += $metrics['404_errors']['count'];
    $allRecaptcha = max($allRecaptcha, $metrics['recaptcha_loads']);
}
echo "- 404 Errors: $all404s (Target: 0) " . ($all404s == 0 ? "✓ MET" : "✗ NOT MET") . "\n";
echo "- reCAPTCHA: $allRecaptcha (Target: 1) " . ($allRecaptcha <= 1 ? "✓ MET" : "✗ NOT MET") . "\n";

if (isset($results['cart'])) {
    $cartTotal = $results['cart']['total_requests'];
    echo "- Cart: $cartTotal requests (Target: <150) " . ($cartTotal < 150 ? "✓ MET" : "✗ NOT MET") . "\n";
}

if (isset($results['checkout'])) {
    $checkoutTotal = $results['checkout']['total_requests'];
    echo "- Checkout: $checkoutTotal requests (Target: <180) " . ($checkoutTotal < 180 ? "✓ MET" : "✗ NOT MET") . "\n";
}

// Save results to JSON
$outputData = [
    'analysis_date' => date('Y-m-d H:i:s'),
    'results' => $results,
    'comparison' => [
        'previous_v2' => $previousV2,
        'targets' => $targets,
        'target_met' => [
            '404_errors' => $all404s == 0,
            'recaptcha_loads' => $allRecaptcha <= 1,
            'cart_requests' => isset($results['cart']) ? $results['cart']['total_requests'] < 150 : null,
            'checkout_requests' => isset($results['checkout']) ? $results['checkout']['total_requests'] < 180 : null
        ]
    ]
];

file_put_contents(__DIR__ . '/har-analysis-output.json', json_encode($outputData, JSON_PRETTY_PRINT));
echo "\nDetailed results saved to: har-analysis-output.json\n";
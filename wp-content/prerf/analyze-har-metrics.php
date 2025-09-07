<?php
/**
 * Analyze HAR files for performance metrics
 */

function analyzeHarFile($filePath) {
    $harContent = file_get_contents($filePath);
    $harData = json_decode($harContent, true);
    
    if (!$harData || !isset($harData['log']['entries'])) {
        return null;
    }
    
    $metrics = [
        'total_requests' => 0,
        '404_errors' => 0,
        'css_files' => 0,
        'js_files' => 0,
        'recaptcha_loads' => 0,
        'blocked_domains' => [],
        'file_path' => basename($filePath)
    ];
    
    $blockedDomains = [
        'elementor' => 0,
        'yith' => 0,
        'revslider' => 0,
        'revolution' => 0,
        'slider' => 0
    ];
    
    foreach ($harData['log']['entries'] as $entry) {
        $metrics['total_requests']++;
        
        $url = $entry['request']['url'] ?? '';
        $status = $entry['response']['status'] ?? 0;
        $mimeType = $entry['response']['content']['mimeType'] ?? '';
        
        // Check 404 errors
        if ($status == 404) {
            $metrics['404_errors']++;
        }
        
        // Count CSS files
        if (strpos($mimeType, 'css') !== false || strpos($url, '.css') !== false) {
            $metrics['css_files']++;
        }
        
        // Count JS files
        if (strpos($mimeType, 'javascript') !== false || strpos($url, '.js') !== false) {
            $metrics['js_files']++;
        }
        
        // Check for reCAPTCHA
        if (stripos($url, 'recaptcha') !== false || stripos($url, 'grecaptcha') !== false) {
            $metrics['recaptcha_loads']++;
        }
        
        // Check for blocked domains that are still loading
        foreach ($blockedDomains as $domain => $count) {
            if (stripos($url, $domain) !== false) {
                $blockedDomains[$domain]++;
            }
        }
    }
    
    // Filter out domains with 0 hits
    $metrics['blocked_domains'] = array_filter($blockedDomains);
    
    return $metrics;
}

// Analyze all HAR files
$harDir = '/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html/wp-content/prerf/inputs/';
$harFiles = glob($harDir . '*.har');

$results = [];
$focusPages = ['trang-cart.har', 'trang-checkout.har', 'trang-order-received.har'];

// Analyze each HAR file
foreach ($harFiles as $harFile) {
    $basename = basename($harFile);
    $metrics = analyzeHarFile($harFile);
    
    if ($metrics) {
        $results[$basename] = $metrics;
    }
}

// Output results
echo "=== HAR Files Analysis Results ===\n\n";

// Summary table for Cart, Checkout, and Order-received pages
echo "### Focus: Cart, Checkout, and Order-received Pages\n\n";
echo "| Page | Total Requests | 404 Errors | CSS Files | JS Files | reCAPTCHA | Blocked Domains Still Loading |\n";
echo "|------|----------------|------------|-----------|----------|-----------|------------------------------|\n";

foreach ($focusPages as $page) {
    if (isset($results[$page])) {
        $m = $results[$page];
        $blockedDomainsStr = '';
        if (!empty($m['blocked_domains'])) {
            $blockedDomainsStr = [];
            foreach ($m['blocked_domains'] as $domain => $count) {
                $blockedDomainsStr[] = "$domain($count)";
            }
            $blockedDomainsStr = implode(', ', $blockedDomainsStr);
        }
        
        echo sprintf("| %-20s | %-14d | %-10d | %-9d | %-8d | %-9d | %-28s |\n",
            str_replace('.har', '', $m['file_path']),
            $m['total_requests'],
            $m['404_errors'],
            $m['css_files'],
            $m['js_files'],
            $m['recaptcha_loads'],
            $blockedDomainsStr ?: 'None'
        );
    }
}

echo "\n### All Pages Summary\n\n";
echo "| Page | Total Requests | 404 Errors | CSS Files | JS Files | reCAPTCHA | Blocked Domains Still Loading |\n";
echo "|------|----------------|------------|-----------|----------|-----------|------------------------------|\n";

foreach ($results as $page => $m) {
    $blockedDomainsStr = '';
    if (!empty($m['blocked_domains'])) {
        $blockedDomainsStr = [];
        foreach ($m['blocked_domains'] as $domain => $count) {
            $blockedDomainsStr[] = "$domain($count)";
        }
        $blockedDomainsStr = implode(', ', $blockedDomainsStr);
    }
    
    echo sprintf("| %-20s | %-14d | %-10d | %-9d | %-8d | %-9d | %-28s |\n",
        str_replace('.har', '', $m['file_path']),
        $m['total_requests'],
        $m['404_errors'],
        $m['css_files'],
        $m['js_files'],
        $m['recaptcha_loads'],
        $blockedDomainsStr ?: 'None'
    );
}

// Detailed 404 errors for focus pages
echo "\n### 404 Errors Details for Focus Pages\n\n";
foreach ($focusPages as $page) {
    $harFile = $harDir . $page;
    if (file_exists($harFile)) {
        $harContent = file_get_contents($harFile);
        $harData = json_decode($harContent, true);
        
        $errors404 = [];
        foreach ($harData['log']['entries'] as $entry) {
            if (($entry['response']['status'] ?? 0) == 404) {
                $errors404[] = $entry['request']['url'];
            }
        }
        
        if (!empty($errors404)) {
            echo "**" . str_replace('.har', '', $page) . ":**\n";
            foreach ($errors404 as $url) {
                echo "- $url\n";
            }
            echo "\n";
        }
    }
}
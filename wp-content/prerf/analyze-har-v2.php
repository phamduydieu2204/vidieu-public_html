<?php
/**
 * HAR Analysis Tool V2 - Enhanced for 6 routes
 * Phân tích HAR files với cart/checkout
 */

class HarAnalyzerV2 {
    private $harData;
    private $requests = [];
    private $duplicates = [];
    private $route;
    
    public function __construct($route) {
        $this->route = $route;
    }
    
    public function loadHar($harFile) {
        $content = file_get_contents($harFile);
        $this->harData = json_decode($content, true);
        if (!$this->harData || !isset($this->harData['log']['entries'])) {
            throw new Exception("Invalid HAR file format");
        }
        return $this;
    }
    
    public function analyze() {
        $this->requests = [];
        $this->duplicates = [];
        
        foreach ($this->harData['log']['entries'] as $entry) {
            $url = $entry['request']['url'];
            $urlParts = parse_url($url);
            $cleanUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'];
            
            // Skip external analytics/tracking
            if (strpos($urlParts['host'], 'google-analytics') !== false ||
                strpos($urlParts['host'], 'googletagmanager') !== false ||
                strpos($urlParts['host'], 'facebook') !== false) {
                continue;
            }
            
            $type = $this->getResourceType($entry);
            $status = $entry['response']['status'];
            $size = $entry['response']['bodySize'] ?? 0;
            $transferSize = $entry['response']['_transferSize'] ?? $size;
            
            // Get initiator
            $initiator = 'unknown';
            if (isset($entry['_initiator'])) {
                if (isset($entry['_initiator']['url'])) {
                    $initiator = $entry['_initiator']['url'];
                } elseif (isset($entry['_initiator']['type'])) {
                    $initiator = $entry['_initiator']['type'];
                }
            }
            
            // Check cache headers
            $cacheControl = '';
            foreach ($entry['response']['headers'] as $header) {
                if (strtolower($header['name']) === 'cache-control') {
                    $cacheControl = $header['value'];
                    break;
                }
            }
            
            $key = $cleanUrl . '|' . $type;
            
            if (!isset($this->requests[$key])) {
                $this->requests[$key] = [
                    'url' => $cleanUrl,
                    'full_url' => $url,
                    'type' => $type,
                    'status' => $status,
                    'size' => $size,
                    'transfer_size' => $transferSize,
                    'occurrences' => 0,
                    'initiators' => [],
                    'cache_control' => $cacheControl,
                    'query_strings' => []
                ];
            }
            
            $this->requests[$key]['occurrences']++;
            $this->requests[$key]['initiators'][] = $initiator;
            
            if (isset($urlParts['query'])) {
                $this->requests[$key]['query_strings'][] = $urlParts['query'];
            }
        }
        
        // Find duplicates
        foreach ($this->requests as $key => $data) {
            if ($data['occurrences'] > 1) {
                $this->duplicates[$key] = $data;
            }
        }
        
        return $this;
    }
    
    private function getResourceType($entry) {
        $mimeType = $entry['response']['content']['mimeType'] ?? '';
        $url = $entry['request']['url'];
        
        if (strpos($mimeType, 'javascript') !== false || preg_match('/\.js(\?|$)/', $url)) {
            return 'js';
        } elseif (strpos($mimeType, 'css') !== false || preg_match('/\.css(\?|$)/', $url)) {
            return 'css';
        } elseif (strpos($mimeType, 'font') !== false || preg_match('/\.(woff2?|ttf|eot)(\?|$)/', $url)) {
            return 'font';
        } elseif (strpos($mimeType, 'image') !== false || preg_match('/\.(jpg|jpeg|png|gif|svg|ico|webp)(\?|$)/', $url)) {
            return 'image';
        } elseif (strpos($mimeType, 'html') !== false) {
            return 'html';
        } elseif (strpos($mimeType, 'json') !== false || strpos($url, 'admin-ajax.php') !== false) {
            return 'json';
        } else {
            return 'other';
        }
    }
    
    public function getStats() {
        $stats = [
            'route' => $this->route,
            'total_requests' => count($this->harData['log']['entries']),
            'unique_requests' => count($this->requests),
            'duplicate_groups' => count($this->duplicates),
            'total_duplicate_requests' => 0,
            'wasted_bytes' => 0,
            'errors_404' => 0,
            'errors_other' => 0,
            'types' => [],
            'admin_ajax_calls' => 0,
            'recaptcha_loads' => 0
        ];
        
        // Count by type
        $typeCount = [];
        foreach ($this->requests as $data) {
            $type = $data['type'];
            if (!isset($typeCount[$type])) {
                $typeCount[$type] = 0;
            }
            $typeCount[$type] += $data['occurrences'];
            
            // Count errors
            if ($data['status'] == 404) {
                $stats['errors_404']++;
            } elseif ($data['status'] >= 400) {
                $stats['errors_other']++;
            }
            
            // Count admin-ajax
            if (strpos($data['url'], 'admin-ajax.php') !== false) {
                $stats['admin_ajax_calls'] += $data['occurrences'];
            }
            
            // Count reCAPTCHA
            if (strpos($data['url'], 'recaptcha') !== false || 
                strpos($data['url'], 'gstatic.com') !== false) {
                $stats['recaptcha_loads'] += $data['occurrences'];
            }
        }
        
        $stats['types'] = $typeCount;
        
        // Calculate duplicates
        foreach ($this->duplicates as $data) {
            $wastedRequests = $data['occurrences'] - 1;
            $stats['total_duplicate_requests'] += $wastedRequests;
            $stats['wasted_bytes'] += ($data['transfer_size'] * $wastedRequests);
        }
        
        return $stats;
    }
    
    public function getDuplicateDetails() {
        $details = [];
        
        foreach ($this->duplicates as $key => $data) {
            $wastedRequests = $data['occurrences'] - 1;
            $wastedBytes = $data['transfer_size'] * $wastedRequests;
            
            $details[] = [
                'url' => $data['url'],
                'type' => $data['type'],
                'occurrences' => $data['occurrences'],
                'wasted_requests' => $wastedRequests,
                'wasted_bytes' => $wastedBytes,
                'initiators' => array_unique($data['initiators'])
            ];
        }
        
        // Sort by wasted bytes
        usort($details, function($a, $b) {
            return $b['wasted_bytes'] - $a['wasted_bytes'];
        });
        
        return $details;
    }
    
    public function getErrors() {
        $errors = [];
        
        foreach ($this->requests as $data) {
            if ($data['status'] >= 400) {
                $errors[] = [
                    'url' => $data['url'],
                    'status' => $data['status'],
                    'type' => $data['type'],
                    'occurrences' => $data['occurrences']
                ];
            }
        }
        
        return $errors;
    }
}

// Run analysis for all 6 routes
$routes = [
    'home' => 'trang-chu.har',
    'products' => 'trang-san-pham.har',
    'post' => 'trang-bai-viet.har',
    'contact' => 'trang-contact.har',
    'cart' => 'trang-cart.har',
    'checkout' => 'trang-checkout.har'
];

$inputDir = dirname(__FILE__) . '/inputs/';
$outputDir = dirname(__FILE__) . '/outputs/';

$allStats = [];
$allDuplicates = [];
$allErrors = [];

foreach ($routes as $route => $filename) {
    $harPath = $inputDir . $filename;
    
    if (!file_exists($harPath)) {
        echo "Warning: Missing HAR file for $route\n";
        continue;
    }
    
    try {
        $analyzer = new HarAnalyzerV2($route);
        $analyzer->loadHar($harPath)->analyze();
        
        $stats = $analyzer->getStats();
        $duplicates = $analyzer->getDuplicateDetails();
        $errors = $analyzer->getErrors();
        
        $allStats[$route] = $stats;
        $allDuplicates[$route] = $duplicates;
        $allErrors[$route] = $errors;
        
        // Print summary
        echo "\n=== $route ===\n";
        echo "Total requests: {$stats['total_requests']}\n";
        echo "Unique requests: {$stats['unique_requests']}\n";
        echo "Duplicate groups: {$stats['duplicate_groups']}\n";
        echo "Wasted requests: {$stats['total_duplicate_requests']}\n";
        echo "404 errors: {$stats['errors_404']}\n";
        echo "Other errors: {$stats['errors_other']}\n";
        echo "Admin AJAX calls: {$stats['admin_ajax_calls']}\n";
        echo "reCAPTCHA loads: {$stats['recaptcha_loads']}\n";
        
        if (isset($stats['types']['css'])) {
            echo "CSS files: {$stats['types']['css']}\n";
        }
        if (isset($stats['types']['js'])) {
            echo "JS files: {$stats['types']['js']}\n";
        }
        
    } catch (Exception $e) {
        echo "Error analyzing $route: " . $e->getMessage() . "\n";
    }
}

// Save results
$results = [
    'analysis_date' => date('Y-m-d H:i:s'),
    'stats' => $allStats,
    'duplicates' => $allDuplicates,
    'errors' => $allErrors
];

file_put_contents($outputDir . 'har-analysis-v2.json', json_encode($results, JSON_PRETTY_PRINT));

echo "\n\nAnalysis complete. Results saved to har-analysis-v2.json\n";
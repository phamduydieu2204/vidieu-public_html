<?php
/**
 * HAR File Analyzer
 * Analyzes HAR files for duplicate requests, errors, and resource optimization
 */

class HarAnalyzer {
    private $harData;
    private $fileName;
    
    public function __construct($harFile) {
        $this->fileName = basename($harFile, '.har');
        $content = file_get_contents($harFile);
        $this->harData = json_decode($content, true);
        
        if (!$this->harData || !isset($this->harData['log']['entries'])) {
            throw new Exception("Invalid HAR file format");
        }
    }
    
    public function analyze() {
        $entries = $this->harData['log']['entries'];
        $analysis = [
            'fileName' => $this->fileName,
            'totalRequests' => count($entries),
            'duplicateRequests' => [],
            'errorResponses' => [],
            'preloadDuplicates' => [],
            'resourcesByType' => [],
            'wastedBytes' => 0,
            'fontDuplicates' => [],
            'queryStringDuplicates' => []
        ];
        
        // Group requests by URL and analyze
        $urlGroups = [];
        $baseUrlGroups = []; // Group by base URL without query string
        $preloadUrls = [];
        $fontUrls = [];
        
        foreach ($entries as $entry) {
            $request = $entry['request'];
            $response = $entry['response'];
            $url = $request['url'];
            $method = $request['method'];
            
            // Parse URL
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
            $queryString = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
            
            // Check for preload/preconnect in request headers
            $isPreload = false;
            foreach ($request['headers'] as $header) {
                if (strtolower($header['name']) === 'purpose' && $header['value'] === 'prefetch') {
                    $isPreload = true;
                    break;
                }
            }
            
            // Determine resource type
            $resourceType = $this->getResourceType($url, $response);
            
            // Track URLs
            if (!isset($urlGroups[$url])) {
                $urlGroups[$url] = [];
            }
            $urlGroups[$url][] = [
                'method' => $method,
                'status' => $response['status'],
                'size' => $response['bodySize'] + $response['headersSize'],
                'mimeType' => $response['content']['mimeType'] ?? 'unknown',
                'resourceType' => $resourceType,
                'isPreload' => $isPreload
            ];
            
            // Track base URLs (without query string)
            if (!isset($baseUrlGroups[$baseUrl])) {
                $baseUrlGroups[$baseUrl] = [];
            }
            $baseUrlGroups[$baseUrl][] = [
                'fullUrl' => $url,
                'queryString' => $queryString,
                'status' => $response['status'],
                'size' => $response['bodySize'] + $response['headersSize']
            ];
            
            // Track preloads
            if ($isPreload) {
                $preloadUrls[$url] = true;
            }
            
            // Track fonts
            if ($resourceType === 'font') {
                if (!isset($fontUrls[$url])) {
                    $fontUrls[$url] = 0;
                }
                $fontUrls[$url]++;
            }
            
            // Track errors (4xx and 5xx status codes)
            if ($response['status'] >= 400) {
                $analysis['errorResponses'][] = [
                    'url' => $url,
                    'status' => $response['status'],
                    'statusText' => $response['statusText'] ?? '',
                    'resourceType' => $resourceType
                ];
            }
            
            // Count resources by type
            if (!isset($analysis['resourcesByType'][$resourceType])) {
                $analysis['resourcesByType'][$resourceType] = [
                    'count' => 0,
                    'totalSize' => 0
                ];
            }
            $analysis['resourcesByType'][$resourceType]['count']++;
            $analysis['resourcesByType'][$resourceType]['totalSize'] += $response['bodySize'] + $response['headersSize'];
        }
        
        // Find duplicate requests (exact same URL)
        foreach ($urlGroups as $url => $requests) {
            if (count($requests) > 1) {
                $totalSize = 0;
                foreach ($requests as $req) {
                    $totalSize += $req['size'];
                }
                $wastedSize = $totalSize - $requests[0]['size']; // All but first are wasted
                
                $analysis['duplicateRequests'][] = [
                    'url' => $url,
                    'count' => count($requests),
                    'resourceType' => $requests[0]['resourceType'],
                    'totalSize' => $totalSize,
                    'wastedBytes' => $wastedSize,
                    'requests' => $requests
                ];
                
                $analysis['wastedBytes'] += $wastedSize;
            }
            
            // Check if this URL was also preloaded
            if (isset($preloadUrls[$url]) && count($requests) > 1) {
                $analysis['preloadDuplicates'][] = [
                    'url' => $url,
                    'timesLoaded' => count($requests),
                    'resourceType' => $requests[0]['resourceType']
                ];
            }
        }
        
        // Find query string duplicates (same base URL, different query strings)
        foreach ($baseUrlGroups as $baseUrl => $requests) {
            if (count($requests) > 1) {
                $uniqueQueryStrings = [];
                foreach ($requests as $req) {
                    $uniqueQueryStrings[$req['queryString']] = true;
                }
                
                // Only report if there are different query strings
                if (count($uniqueQueryStrings) > 1) {
                    $analysis['queryStringDuplicates'][] = [
                        'baseUrl' => $baseUrl,
                        'variants' => array_map(function($req) {
                            return [
                                'url' => $req['fullUrl'],
                                'queryString' => $req['queryString'],
                                'size' => $req['size']
                            ];
                        }, $requests)
                    ];
                }
            }
        }
        
        // Find font duplicates
        foreach ($fontUrls as $url => $count) {
            if ($count > 1) {
                $analysis['fontDuplicates'][] = [
                    'url' => $url,
                    'timesLoaded' => $count
                ];
            }
        }
        
        return $analysis;
    }
    
    private function getResourceType($url, $response) {
        $mimeType = $response['content']['mimeType'] ?? '';
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        
        // Check by MIME type first
        if (strpos($mimeType, 'javascript') !== false || strpos($mimeType, 'ecmascript') !== false) {
            return 'js';
        }
        if (strpos($mimeType, 'css') !== false) {
            return 'css';
        }
        if (strpos($mimeType, 'image/') !== false) {
            return 'image';
        }
        if (strpos($mimeType, 'font/') !== false || strpos($mimeType, 'application/font') !== false) {
            return 'font';
        }
        if (strpos($mimeType, 'text/html') !== false) {
            return 'html';
        }
        if (strpos($mimeType, 'application/json') !== false) {
            return 'json';
        }
        
        // Check by extension
        switch (strtolower($extension)) {
            case 'js':
            case 'mjs':
                return 'js';
            case 'css':
                return 'css';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'svg':
            case 'webp':
            case 'ico':
                return 'image';
            case 'woff':
            case 'woff2':
            case 'ttf':
            case 'otf':
            case 'eot':
                return 'font';
            case 'html':
            case 'htm':
                return 'html';
            case 'json':
                return 'json';
            case 'xml':
                return 'xml';
            default:
                return 'other';
        }
    }
    
    public function formatReport($analysis) {
        $report = "\n=== HAR Analysis Report: {$analysis['fileName']} ===\n\n";
        
        // Summary
        $report .= "SUMMARY:\n";
        $report .= "- Total Requests: {$analysis['totalRequests']}\n";
        $report .= "- Duplicate Requests: " . count($analysis['duplicateRequests']) . "\n";
        $report .= "- Error Responses (4xx/5xx): " . count($analysis['errorResponses']) . "\n";
        $report .= "- Total Wasted Bytes: " . number_format($analysis['wastedBytes']) . " bytes (" . $this->formatBytes($analysis['wastedBytes']) . ")\n\n";
        
        // Resources by Type
        $report .= "RESOURCES BY TYPE:\n";
        foreach ($analysis['resourcesByType'] as $type => $data) {
            $report .= sprintf("- %s: %d requests, %s\n", 
                strtoupper($type), 
                $data['count'], 
                $this->formatBytes($data['totalSize'])
            );
        }
        $report .= "\n";
        
        // Duplicate Requests
        if (!empty($analysis['duplicateRequests'])) {
            $report .= "DUPLICATE REQUESTS:\n";
            foreach ($analysis['duplicateRequests'] as $dup) {
                $report .= sprintf("- %s\n  Type: %s, Loaded: %d times, Wasted: %s\n", 
                    $this->truncateUrl($dup['url']),
                    $dup['resourceType'],
                    $dup['count'],
                    $this->formatBytes($dup['wastedBytes'])
                );
            }
            $report .= "\n";
        }
        
        // Query String Duplicates
        if (!empty($analysis['queryStringDuplicates'])) {
            $report .= "QUERY STRING DUPLICATES (same file, different parameters):\n";
            foreach ($analysis['queryStringDuplicates'] as $dup) {
                $report .= "- Base URL: " . $this->truncateUrl($dup['baseUrl']) . "\n";
                foreach ($dup['variants'] as $variant) {
                    $report .= sprintf("  * %s (%s)\n", 
                        $variant['queryString'] ?: '[no query string]',
                        $this->formatBytes($variant['size'])
                    );
                }
            }
            $report .= "\n";
        }
        
        // Preload Duplicates
        if (!empty($analysis['preloadDuplicates'])) {
            $report .= "PRELOAD DUPLICATES:\n";
            foreach ($analysis['preloadDuplicates'] as $dup) {
                $report .= sprintf("- %s (Type: %s, Loaded: %d times)\n", 
                    $this->truncateUrl($dup['url']),
                    $dup['resourceType'],
                    $dup['timesLoaded']
                );
            }
            $report .= "\n";
        }
        
        // Font Duplicates
        if (!empty($analysis['fontDuplicates'])) {
            $report .= "FONT DUPLICATES:\n";
            foreach ($analysis['fontDuplicates'] as $font) {
                $report .= sprintf("- %s (Loaded: %d times)\n", 
                    $this->truncateUrl($font['url']),
                    $font['timesLoaded']
                );
            }
            $report .= "\n";
        }
        
        // Error Responses
        if (!empty($analysis['errorResponses'])) {
            $report .= "ERROR RESPONSES:\n";
            foreach ($analysis['errorResponses'] as $error) {
                $report .= sprintf("- %d %s: %s (Type: %s)\n", 
                    $error['status'],
                    $error['statusText'],
                    $this->truncateUrl($error['url']),
                    $error['resourceType']
                );
            }
            $report .= "\n";
        }
        
        return $report;
    }
    
    private function formatBytes($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
    
    private function truncateUrl($url, $maxLength = 80) {
        if (strlen($url) <= $maxLength) return $url;
        
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $filename = basename($path);
        
        if (strlen($filename) < 40) {
            return '...' . substr($url, -($maxLength - 3));
        } else {
            return '...' . substr($filename, -($maxLength - 3));
        }
    }
}

// Main execution
$harFiles = [
    'trang-chu.har',
    'trang-san-pham.har',
    'trang-bai-viet.har',
    'trang-contact.har'
];

$inputDir = __DIR__ . '/inputs/';
$allReports = '';

foreach ($harFiles as $harFile) {
    $filePath = $inputDir . $harFile;
    
    if (!file_exists($filePath)) {
        echo "Warning: HAR file not found: $filePath\n";
        continue;
    }
    
    try {
        $analyzer = new HarAnalyzer($filePath);
        $analysis = $analyzer->analyze();
        $report = $analyzer->formatReport($analysis);
        
        echo $report;
        $allReports .= $report;
        
    } catch (Exception $e) {
        echo "Error analyzing $harFile: " . $e->getMessage() . "\n";
    }
}

// Save combined report
file_put_contents(__DIR__ . '/har-analysis-report.txt', $allReports);
echo "\nFull report saved to: " . __DIR__ . '/har-analysis-report.txt\n";
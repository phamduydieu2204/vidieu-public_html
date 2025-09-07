<?php
/**
 * Parse PSI JSON files and extract SEO audit issues
 * 
 * @package Vidieu_SEO_Tools
 * @since 1.0.0
 */

// Define paths
$base_path = dirname(dirname(dirname(__FILE__)));
$input_dir = $base_path . '/wp-content/prerf/inputs/';
$output_dir = $base_path . '/wp-content/perf/seo/';

// Ensure output directory exists
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// PSI files to analyze
$psi_files = [
    'home' => $input_dir . 'psi-home-desktop.json',
    'product' => $input_dir . 'psi-product-desktop.json',
    'post' => $input_dir . 'psi-post-desktop.json'
];

// SEO-related audit IDs to check
$seo_audit_ids = [
    'meta-description',
    'document-title',
    'canonical',
    'viewport',
    'crawlable-anchors',
    'link-text',
    'tap-targets',
    'font-size',
    'hreflang',
    'robots-txt',
    'is-crawlable',
    'structured-data',
    'image-alt',
    'html-has-lang',
    'html-lang-valid',
    'valid-lang',
    'http-status-code'
];

// Parse PSI data
$audit_results = [];

foreach ($psi_files as $page_type => $file_path) {
    if (!file_exists($file_path)) {
        echo "Warning: $file_path not found\n";
        continue;
    }
    
    $json_content = file_get_contents($file_path);
    $psi_data = json_decode($json_content, true);
    
    if (!$psi_data || !isset($psi_data['lighthouseResult']['audits'])) {
        echo "Error: Invalid PSI data in $file_path\n";
        continue;
    }
    
    $audits = $psi_data['lighthouseResult']['audits'];
    $final_url = $psi_data['lighthouseResult']['finalUrl'] ?? 'N/A';
    
    // Extract SEO category score
    $seo_score = null;
    if (isset($psi_data['lighthouseResult']['categories']['seo'])) {
        $seo_score = round($psi_data['lighthouseResult']['categories']['seo']['score'] * 100);
    }
    
    $audit_results[$page_type] = [
        'url' => $final_url,
        'seo_score' => $seo_score,
        'issues' => []
    ];
    
    // Check each SEO audit
    foreach ($seo_audit_ids as $audit_id) {
        if (!isset($audits[$audit_id])) {
            continue;
        }
        
        $audit = $audits[$audit_id];
        $score = $audit['score'] ?? null;
        
        // Only include failed or warning audits
        if ($score !== null && $score < 1) {
            $status = $score === 0 ? 'FAIL' : 'WARN';
            
            $audit_results[$page_type]['issues'][] = [
                'audit_id' => $audit_id,
                'title' => $audit['title'] ?? $audit_id,
                'status' => $status,
                'score' => $score,
                'description' => $audit['description'] ?? '',
                'details' => extract_audit_details($audit)
            ];
        }
    }
}

// Extract relevant details from audit
function extract_audit_details($audit) {
    $details = [];
    
    if (isset($audit['details']['items'])) {
        foreach ($audit['details']['items'] as $item) {
            if (isset($item['node'])) {
                $details[] = [
                    'selector' => $item['node']['selector'] ?? '',
                    'snippet' => $item['node']['snippet'] ?? '',
                    'explanation' => $item['node']['explanation'] ?? ''
                ];
            } else {
                // For simple items
                $details[] = $item;
            }
        }
    }
    
    return $details;
}

// Generate Markdown report
$markdown = "# PSI SEO Audit Report\n";
$markdown .= "**Generated**: " . date('Y-m-d H:i:s') . "\n\n";
$markdown .= "## Summary\n\n";
$markdown .= "| Page Type | URL | SEO Score | Issues Count |\n";
$markdown .= "|-----------|-----|-----------|-------------|\n";

foreach ($audit_results as $page_type => $result) {
    $issues_count = count($result['issues']);
    $score_display = $result['seo_score'] !== null ? $result['seo_score'] . '/100' : 'N/A';
    $markdown .= "| " . ucfirst($page_type) . " | " . $result['url'] . " | " . $score_display . " | " . $issues_count . " |\n";
}

$markdown .= "\n## Detailed Issues by Page Type\n\n";

foreach ($audit_results as $page_type => $result) {
    $markdown .= "### " . ucfirst($page_type) . " Page\n\n";
    
    if (empty($result['issues'])) {
        $markdown .= "✅ No SEO issues found!\n\n";
        continue;
    }
    
    $markdown .= "| Audit | Status | Description | Details |\n";
    $markdown .= "|-------|--------|-------------|----------|\n";
    
    foreach ($result['issues'] as $issue) {
        $details_str = '';
        if (!empty($issue['details'])) {
            $details_items = [];
            foreach ($issue['details'] as $detail) {
                if (is_array($detail)) {
                    if (isset($detail['snippet'])) {
                        $details_items[] = '`' . substr(strip_tags($detail['snippet']), 0, 60) . '...`';
                    } elseif (isset($detail['selector'])) {
                        $details_items[] = $detail['selector'];
                    }
                }
            }
            $details_str = implode(', ', array_slice($details_items, 0, 3));
            if (count($details_items) > 3) {
                $details_str .= ' ...';
            }
        }
        
        $markdown .= "| " . $issue['title'] . " | " . $issue['status'] . " | " . 
                     substr($issue['description'], 0, 100) . "... | " . $details_str . " |\n";
    }
    
    $markdown .= "\n";
}

// Add recommendations section
$markdown .= "## Priority Recommendations\n\n";

// Check for common issues across all pages
$common_issues = [];
foreach ($seo_audit_ids as $audit_id) {
    $found_in_pages = [];
    foreach ($audit_results as $page_type => $result) {
        foreach ($result['issues'] as $issue) {
            if ($issue['audit_id'] === $audit_id) {
                $found_in_pages[] = $page_type;
            }
        }
    }
    if (count($found_in_pages) > 1) {
        $common_issues[$audit_id] = $found_in_pages;
    }
}

if (!empty($common_issues)) {
    $markdown .= "### Common Issues (affecting multiple pages)\n\n";
    foreach ($common_issues as $audit_id => $pages) {
        $markdown .= "- **$audit_id**: Found in " . implode(', ', $pages) . " pages\n";
    }
    $markdown .= "\n";
}

// Add specific recommendations based on issues found
$markdown .= "### Implementation Priority\n\n";
$markdown .= "1. **Meta Tags**: Ensure all pages have unique title and description tags\n";
$markdown .= "2. **Canonical URLs**: Add proper canonical tags to prevent duplicate content issues\n";
$markdown .= "3. **Structured Data**: Implement JSON-LD for WebSite, Organization, BreadcrumbList, Product, and Article\n";
$markdown .= "4. **Accessibility**: Add aria-labels to icon-only links and alt text to images\n";
$markdown .= "5. **Mobile**: Ensure proper viewport meta tag and tap target sizes\n";

// Write report
file_put_contents($output_dir . 'psi-seo-audit.md', $markdown);

echo "SEO audit report generated: " . $output_dir . "psi-seo-audit.md\n";

// Also output JSON for programmatic use
file_put_contents($output_dir . 'psi-seo-audit.json', json_encode($audit_results, JSON_PRETTY_PRINT));

echo "JSON data saved: " . $output_dir . "psi-seo-audit.json\n";
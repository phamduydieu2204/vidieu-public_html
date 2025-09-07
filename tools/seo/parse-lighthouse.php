<?php
/**
 * Parse Lighthouse/PSI JSON files and extract SEO audit gaps
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

// Find all JSON files with 'lighthouse' or 'psi' prefix
$json_files = glob($input_dir . '*.json');
$lighthouse_files = array();

foreach ($json_files as $file) {
    $filename = basename($file);
    if (preg_match('/^(lighthouse|psi).*\.json$/i', $filename)) {
        // Determine page type from filename
        $page_type = 'unknown';
        if (strpos($filename, 'home') !== false) {
            $page_type = 'home';
        } elseif (strpos($filename, 'product') !== false) {
            $page_type = 'product';
        } elseif (strpos($filename, 'post') !== false) {
            $page_type = 'post';
        }
        
        $lighthouse_files[$page_type] = $file;
    }
}

// Process each file
$seo_gaps = array();
$summary_data = array();

foreach ($lighthouse_files as $page_type => $file_path) {
    $json_content = file_get_contents($file_path);
    $data = json_decode($json_content, true);
    
    if (!$data) {
        echo "Error parsing JSON from $file_path\n";
        continue;
    }
    
    // Extract SEO score and audits
    $seo_score = null;
    $seo_audits = array();
    
    // Handle both Lighthouse and PSI data structures
    if (isset($data['lighthouseResult'])) {
        // PSI format
        $lighthouse_data = $data['lighthouseResult'];
        $audits = $lighthouse_data['audits'] ?? array();
        $categories = $lighthouse_data['categories'] ?? array();
        $final_url = $lighthouse_data['finalUrl'] ?? $data['finalUrl'] ?? '';
    } else {
        // Direct Lighthouse format
        $audits = $data['audits'] ?? array();
        $categories = $data['categories'] ?? array();
        $final_url = $data['requestedUrl'] ?? '';
    }
    
    // Get SEO score
    if (isset($categories['seo']['score'])) {
        $seo_score = round($categories['seo']['score'] * 100);
    }
    
    // Get SEO-specific audits
    $seo_audit_refs = array();
    if (isset($categories['seo']['auditRefs'])) {
        foreach ($categories['seo']['auditRefs'] as $ref) {
            $seo_audit_refs[] = $ref['id'];
        }
    }
    
    // Fallback: common SEO audit IDs
    if (empty($seo_audit_refs)) {
        $seo_audit_refs = array(
            'meta-description',
            'document-title',
            'link-text',
            'crawlable-anchors',
            'is-crawlable',
            'robots-txt',
            'image-alt',
            'hreflang',
            'canonical',
            'font-size',
            'tap-targets',
            'viewport',
            'structured-data',
            'html-has-lang',
            'html-lang-valid'
        );
    }
    
    // Process each SEO audit
    foreach ($seo_audit_refs as $audit_id) {
        if (!isset($audits[$audit_id])) {
            continue;
        }
        
        $audit = $audits[$audit_id];
        $score = $audit['score'] ?? null;
        
        // Skip passing audits
        if ($score === null || $score == 1) {
            continue;
        }
        
        // Categorize priority
        $priority = 'P2'; // Default: warning
        if ($score === 0) {
            // Critical failures
            if (in_array($audit_id, ['meta-description', 'document-title', 'is-crawlable', 'robots-txt'])) {
                $priority = 'P0';
            } else {
                $priority = 'P1';
            }
        } elseif ($score < 0.9) {
            $priority = 'P1';
        }
        
        $seo_audits[] = array(
            'id' => $audit_id,
            'title' => $audit['title'] ?? $audit_id,
            'description' => $audit['description'] ?? '',
            'score' => $score,
            'priority' => $priority,
            'details' => extract_details($audit)
        );
    }
    
    // Sort by priority
    usort($seo_audits, function($a, $b) {
        $priority_order = array('P0' => 0, 'P1' => 1, 'P2' => 2);
        return $priority_order[$a['priority']] <=> $priority_order[$b['priority']];
    });
    
    $seo_gaps[$page_type] = array(
        'url' => $final_url,
        'seo_score' => $seo_score,
        'audits' => $seo_audits
    );
    
    $summary_data[$page_type] = array(
        'score' => $seo_score,
        'p0_count' => count(array_filter($seo_audits, function($a) { return $a['priority'] === 'P0'; })),
        'p1_count' => count(array_filter($seo_audits, function($a) { return $a['priority'] === 'P1'; })),
        'p2_count' => count(array_filter($seo_audits, function($a) { return $a['priority'] === 'P2'; }))
    );
}

// Extract audit details
function extract_details($audit) {
    $details_summary = array();
    
    if (isset($audit['details']['items']) && is_array($audit['details']['items'])) {
        foreach ($audit['details']['items'] as $index => $item) {
            if ($index >= 3) break; // Limit to first 3 items
            
            if (isset($item['node'])) {
                $details_summary[] = array(
                    'selector' => $item['node']['selector'] ?? '',
                    'snippet' => isset($item['node']['snippet']) ? strip_tags($item['node']['snippet']) : ''
                );
            } elseif (isset($item['url'])) {
                $details_summary[] = $item['url'];
            } elseif (is_string($item)) {
                $details_summary[] = $item;
            }
        }
        
        if (count($audit['details']['items']) > 3) {
            $details_summary[] = '... and ' . (count($audit['details']['items']) - 3) . ' more';
        }
    }
    
    return $details_summary;
}

// Generate Markdown report
$markdown = "# Lighthouse SEO Gap Analysis\n";
$markdown .= "**Generated**: " . date('Y-m-d H:i:s') . "\n";
$markdown .= "**Target**: SEO Score ≥ 95 (aiming for 100)\n\n";

// Summary table
$markdown .= "## Summary\n\n";
$markdown .= "| Page Type | Current Score | P0 Issues | P1 Issues | P2 Issues | Gap to 95 |\n";
$markdown .= "|-----------|---------------|-----------|-----------|-----------|------------|\n";

foreach ($summary_data as $page_type => $summary) {
    $gap = max(0, 95 - ($summary['score'] ?? 0));
    $score_display = $summary['score'] !== null ? $summary['score'] . '/100' : 'N/A';
    $markdown .= sprintf("| %-9s | %-13s | %-9d | %-9d | %-9d | %-10d |\n",
        ucfirst($page_type),
        $score_display,
        $summary['p0_count'],
        $summary['p1_count'],
        $summary['p2_count'],
        $gap
    );
}

$markdown .= "\n## Detailed SEO Gaps by Page Type\n\n";
$markdown .= "**Priority Levels**:\n";
$markdown .= "- P0: Critical failures (must fix)\n";
$markdown .= "- P1: Important issues (should fix)\n";
$markdown .= "- P2: Warnings (nice to fix)\n\n";

// Detailed issues by page type
foreach ($seo_gaps as $page_type => $data) {
    $markdown .= "### " . ucfirst($page_type) . " Page\n";
    $markdown .= "**URL**: " . $data['url'] . "\n";
    $markdown .= "**SEO Score**: " . ($data['seo_score'] ?? 'N/A') . "/100\n\n";
    
    if (empty($data['audits'])) {
        $markdown .= "✅ No SEO issues found!\n\n";
        continue;
    }
    
    $markdown .= "| Priority | Audit | Score | Issue | Details |\n";
    $markdown .= "|----------|-------|-------|-------|----------|\n";
    
    foreach ($data['audits'] as $audit) {
        $details_str = '';
        if (!empty($audit['details'])) {
            $detail_items = array();
            foreach ($audit['details'] as $detail) {
                if (is_array($detail) && isset($detail['snippet'])) {
                    $detail_items[] = substr($detail['snippet'], 0, 50) . '...';
                } elseif (is_string($detail)) {
                    $detail_items[] = substr($detail, 0, 50);
                }
            }
            $details_str = implode('; ', $detail_items);
        }
        
        $markdown .= sprintf("| %-8s | %-40s | %-5.2f | %-80s | %-50s |\n",
            $audit['priority'],
            substr($audit['title'], 0, 40),
            $audit['score'],
            substr($audit['description'], 0, 80) . '...',
            $details_str
        );
    }
    
    $markdown .= "\n";
}

// Common issues across pages
$markdown .= "## Common Issues Analysis\n\n";

$common_issues = array();
foreach ($seo_gaps as $page_type => $data) {
    foreach ($data['audits'] as $audit) {
        $audit_id = $audit['id'];
        if (!isset($common_issues[$audit_id])) {
            $common_issues[$audit_id] = array(
                'title' => $audit['title'],
                'pages' => array(),
                'priority' => $audit['priority']
            );
        }
        $common_issues[$audit_id]['pages'][] = $page_type;
    }
}

// Filter to show only issues on multiple pages
$common_issues = array_filter($common_issues, function($issue) {
    return count($issue['pages']) > 1;
});

if (!empty($common_issues)) {
    $markdown .= "| Issue | Priority | Affected Pages |\n";
    $markdown .= "|-------|----------|----------------|\n";
    
    foreach ($common_issues as $audit_id => $issue) {
        $markdown .= sprintf("| %-40s | %-8s | %s |\n",
            $issue['title'],
            $issue['priority'],
            implode(', ', $issue['pages'])
        );
    }
    $markdown .= "\n";
}

// Action plan
$markdown .= "## Recommended Action Plan\n\n";
$markdown .= "### P0 - Critical (Immediate Fix)\n";
$has_p0 = false;
foreach ($seo_gaps as $page_type => $data) {
    $p0_audits = array_filter($data['audits'], function($a) { return $a['priority'] === 'P0'; });
    if (!empty($p0_audits)) {
        $has_p0 = true;
        foreach ($p0_audits as $audit) {
            $markdown .= "- **" . $audit['title'] . "** (" . $page_type . "): " . $audit['id'] . "\n";
        }
    }
}
if (!$has_p0) {
    $markdown .= "- No P0 issues found ✓\n";
}

$markdown .= "\n### P1 - Important (High Priority)\n";
$has_p1 = false;
foreach ($seo_gaps as $page_type => $data) {
    $p1_audits = array_filter($data['audits'], function($a) { return $a['priority'] === 'P1'; });
    if (!empty($p1_audits)) {
        $has_p1 = true;
        foreach ($p1_audits as $audit) {
            $markdown .= "- **" . $audit['title'] . "** (" . $page_type . "): " . $audit['id'] . "\n";
        }
    }
}
if (!$has_p1) {
    $markdown .= "- No P1 issues found ✓\n";
}

$markdown .= "\n### P2 - Warnings (Nice to Have)\n";
$markdown .= "Focus on P0 and P1 first. P2 issues can be addressed if needed to reach 100.\n";

// Write report
file_put_contents($output_dir . 'lighthouse-seo-gap.md', $markdown);
echo "SEO gap analysis report generated: " . $output_dir . "lighthouse-seo-gap.md\n";

// Also save as JSON for programmatic use
file_put_contents($output_dir . 'lighthouse-seo-gap.json', json_encode($seo_gaps, JSON_PRETTY_PRINT));
echo "JSON data saved: " . $output_dir . "lighthouse-seo-gap.json\n";
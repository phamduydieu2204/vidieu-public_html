<?php
/**
 * Parse Lighthouse SEO Results
 * Analyze all JSON files and identify SEO issues by URL
 * 
 * @package Vidieu_SEO_Tools
 * @since 1.0.0
 */

$base_path = dirname(dirname(dirname(__FILE__)));
$json_dir = $base_path . '/wp-content/prerf/inputs/';
$output_dir = $base_path . '/wp-content/perf/seo/';

// Create output directory if needed
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// Priority mapping
$priority_map = [
    'crawlable-anchors' => 'P0',
    'link-text' => 'P0',
    'meta-description' => 'P0',
    'tap-targets' => 'P1',
    'image-alt' => 'P1',
    'robots-txt' => 'P1',
    'hreflang' => 'P2',
    'canonical' => 'P2',
    'structured-data' => 'P2'
];

$all_results = [];
$summary_by_page = [];
$issues_by_audit = [];

// Get all JSON files
$json_files = glob($json_dir . '*.json');

if (empty($json_files)) {
    echo "No JSON files found in: $json_dir\n";
    exit(1);
}

echo "Lighthouse SEO Parser\n";
echo "=====================\n\n";
echo "Analyzing " . count($json_files) . " JSON files...\n\n";

foreach ($json_files as $json_file) {
    $page_name = basename($json_file, '.json');
    echo "Processing: $page_name\n";
    
    $json_content = file_get_contents($json_file);
    $data = json_decode($json_content, true);
    
    if (!$data || !isset($data['categories']['seo'])) {
        echo "  - Invalid or missing SEO data\n";
        continue;
    }
    
    $seo = $data['categories']['seo'];
    $url = $data['finalUrl'] ?? $data['requestedUrl'] ?? 'Unknown';
    $score = round($seo['score'] * 100);
    
    $summary_by_page[$page_name] = [
        'url' => $url,
        'score' => $score,
        'p0_count' => 0,
        'p1_count' => 0,
        'p2_count' => 0,
        'issues' => []
    ];
    
    // Analyze audits
    foreach ($seo['auditRefs'] as $auditRef) {
        if ($auditRef['weight'] == 0) continue;
        
        $audit_id = $auditRef['id'];
        $audit = $data['audits'][$audit_id] ?? null;
        
        if (!$audit) continue;
        
        // Skip passed audits
        if ($audit['score'] >= 1) continue;
        
        $priority = $priority_map[$audit_id] ?? 'P2';
        $score_val = $audit['score'] ?? 0;
        
        // Count by priority
        if ($priority == 'P0') {
            $summary_by_page[$page_name]['p0_count']++;
        } elseif ($priority == 'P1') {
            $summary_by_page[$page_name]['p1_count']++;
        } else {
            $summary_by_page[$page_name]['p2_count']++;
        }
        
        // Store issue details
        $issue_details = [
            'page' => $page_name,
            'url' => $url,
            'audit_id' => $audit_id,
            'title' => $audit['title'] ?? 'Unknown',
            'description' => $audit['description'] ?? '',
            'score' => $score_val,
            'priority' => $priority,
            'details' => []
        ];
        
        // Extract specific details
        if (isset($audit['details']['items'])) {
            foreach ($audit['details']['items'] as $item) {
                if (isset($item['node'])) {
                    $issue_details['details'][] = $item['node']['snippet'] ?? $item['node']['selector'] ?? 'Unknown element';
                } elseif (isset($item['source'])) {
                    $issue_details['details'][] = $item['source']['snippet'] ?? $item['source']['url'] ?? 'Unknown source';
                }
            }
        }
        
        $summary_by_page[$page_name]['issues'][] = $issue_details;
        
        // Group by audit for cross-page analysis
        if (!isset($issues_by_audit[$audit_id])) {
            $issues_by_audit[$audit_id] = [
                'title' => $audit['title'] ?? 'Unknown',
                'description' => $audit['description'] ?? '',
                'priority' => $priority,
                'affected_pages' => []
            ];
        }
        $issues_by_audit[$audit_id]['affected_pages'][] = $page_name;
    }
}

// Generate report
$report = "# Lighthouse SEO Analysis Report\n";
$report .= "**Generated**: " . date('Y-m-d H:i:s') . "\n";
$report .= "**Target**: SEO Score ≥ 95\n\n";

$report .= "## Summary by Page Type\n\n";
$report .= "| Page | URL | Score | P0 | P1 | P2 | Status |\n";
$report .= "|------|-----|-------|----|----|----|---------|\n";

foreach ($summary_by_page as $page => $info) {
    $status = $info['score'] >= 95 ? '✅' : '❌';
    $report .= sprintf("| %s | %s | %d | %d | %d | %d | %s |\n",
        $page,
        substr($info['url'], 0, 50) . (strlen($info['url']) > 50 ? '...' : ''),
        $info['score'],
        $info['p0_count'],
        $info['p1_count'],
        $info['p2_count'],
        $status
    );
}

$report .= "\n## Issues by Audit (Cross-Page Analysis)\n\n";

// Sort by priority
$p0_audits = [];
$p1_audits = [];
$p2_audits = [];

foreach ($issues_by_audit as $audit_id => $info) {
    if ($info['priority'] == 'P0') {
        $p0_audits[$audit_id] = $info;
    } elseif ($info['priority'] == 'P1') {
        $p1_audits[$audit_id] = $info;
    } else {
        $p2_audits[$audit_id] = $info;
    }
}

// P0 Issues
if (!empty($p0_audits)) {
    $report .= "### P0 - Critical Issues (Must Fix)\n\n";
    foreach ($p0_audits as $audit_id => $info) {
        $report .= "#### " . $info['title'] . " (`$audit_id`)\n";
        $report .= $info['description'] . "\n\n";
        $report .= "**Affected pages**: " . implode(', ', array_unique($info['affected_pages'])) . "\n\n";
    }
}

// P1 Issues  
if (!empty($p1_audits)) {
    $report .= "### P1 - Important Issues\n\n";
    foreach ($p1_audits as $audit_id => $info) {
        $report .= "#### " . $info['title'] . " (`$audit_id`)\n";
        $report .= "**Affected pages**: " . implode(', ', array_unique($info['affected_pages'])) . "\n\n";
    }
}

// P2 Issues
if (!empty($p2_audits)) {
    $report .= "### P2 - Minor Issues\n\n";
    foreach ($p2_audits as $audit_id => $info) {
        $report .= "#### " . $info['title'] . " (`$audit_id`)\n";
        $report .= "**Affected pages**: " . implode(', ', array_unique($info['affected_pages'])) . "\n\n";
    }
}

// Detailed issues by page
$report .= "\n## Detailed Issues by Page\n\n";

foreach ($summary_by_page as $page => $info) {
    if (empty($info['issues'])) continue;
    
    $report .= "### $page\n";
    $report .= "**URL**: " . $info['url'] . "\n";
    $report .= "**Score**: " . $info['score'] . "/100\n\n";
    
    foreach ($info['issues'] as $issue) {
        $report .= "- **[{$issue['priority']}] {$issue['title']}** (score: {$issue['score']})\n";
        if (!empty($issue['details'])) {
            $report .= "  Examples:\n";
            foreach (array_slice($issue['details'], 0, 3) as $detail) {
                $report .= "  - `" . str_replace("\n", " ", substr($detail, 0, 100)) . "`\n";
            }
            if (count($issue['details']) > 3) {
                $report .= "  - ...and " . (count($issue['details']) - 3) . " more\n";
            }
        }
        $report .= "\n";
    }
}

// Gap analysis
$report .= "\n## Gap Analysis to 95+ Score\n\n";

$needs_fix = [];
foreach ($summary_by_page as $page => $info) {
    if ($info['score'] < 95) {
        $gap = 95 - $info['score'];
        $needs_fix[$page] = [
            'gap' => $gap,
            'p0' => $info['p0_count'],
            'p1' => $info['p1_count']
        ];
    }
}

if (!empty($needs_fix)) {
    $report .= "Pages needing fixes:\n\n";
    foreach ($needs_fix as $page => $fix_info) {
        $report .= "- **$page**: {$fix_info['gap']} point gap";
        if ($fix_info['p0'] > 0) {
            $report .= " (Fix {$fix_info['p0']} P0 issues first!)";
        }
        $report .= "\n";
    }
} else {
    $report .= "✅ All pages meet the 95+ target!\n";
}

// Recommendations
$report .= "\n## Action Items\n\n";

if (!empty($p0_audits)) {
    $report .= "1. **Fix all P0 issues immediately**:\n";
    foreach ($p0_audits as $audit_id => $info) {
        $report .= "   - `$audit_id`: " . $info['title'] . "\n";
    }
    $report .= "\n";
}

if (!empty($p1_audits)) {
    $report .= "2. **Address P1 issues**:\n";
    foreach ($p1_audits as $audit_id => $info) {
        $report .= "   - `$audit_id`: " . $info['title'] . "\n";
    }
    $report .= "\n";
}

$report .= "3. **Testing**:\n";
$report .= "   - Clear all caches\n";
$report .= "   - Run Lighthouse on each page type\n";
$report .= "   - Verify 95+ scores\n";

// Write report
$output_file = $output_dir . 'lighthouse-seo-analysis-' . date('Y-m-d-His') . '.md';
file_put_contents($output_file, $report);

echo "\n✅ Analysis complete!\n";
echo "Report written to: $output_file\n\n";

// Also update the main gap file
file_put_contents($output_dir . 'lighthouse-seo-gap-latest.md', $report);

// Print summary
echo "Summary:\n";
echo "--------\n";
foreach ($summary_by_page as $page => $info) {
    printf("%-15s: %3d/100", $page, $info['score']);
    if ($info['score'] >= 95) {
        echo " ✅";
    } else {
        echo " ❌ (P0: {$info['p0_count']}, P1: {$info['p1_count']}, P2: {$info['p2_count']})";
    }
    echo "\n";
}

echo "\nKey findings:\n";
if (!empty($p0_audits)) {
    echo "- " . count($p0_audits) . " P0 (critical) audit types failing\n";
}
if (!empty($p1_audits)) {
    echo "- " . count($p1_audits) . " P1 (important) audit types failing\n";
}
if (!empty($p2_audits)) {
    echo "- " . count($p2_audits) . " P2 (minor) audit types failing\n";
}
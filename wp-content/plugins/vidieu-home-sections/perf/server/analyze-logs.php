<?php
/**
 * Log Analyzer
 * 
 * Analyzes performance logs and generates reports
 * Usage: php analyze-logs.php
 */

// Ensure we're running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "=== Vidieu Performance Log Analyzer ===\n\n";

$log_dir = dirname(__FILE__) . '/logs';
$reports_dir = dirname(__FILE__);

// Check if logs directory exists
if (!is_dir($log_dir)) {
    die("Log directory not found: {$log_dir}\n");
}

// Initialize analysis data
$query_analysis = [];
$bootstrap_analysis = [];

// Process query logs
$query_files = glob($log_dir . '/*-queries.log');
foreach ($query_files as $file) {
    $content = file_get_contents($file);
    
    // Extract route name
    $route = basename($file, '-queries.log');
    
    // Parse query data
    if (preg_match_all('/Type: (\w+) - Count: (\d+) - Time: ([\d.]+)s/', $content, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $type = $matches[1][$i];
            $count = intval($matches[2][$i]);
            $time = floatval($matches[3][$i]);
            
            if (!isset($query_analysis[$route])) {
                $query_analysis[$route] = [];
            }
            
            $query_analysis[$route][$type] = [
                'count' => $count,
                'time' => $time
            ];
        }
    }
    
    // Extract slow queries
    if (preg_match_all('/- Time: ([\d.]+)s\n\s+Query: (.+)\n\s+Caller: (.+)/', $content, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $time = floatval($matches[1][$i]);
            if ($time > 0.05) { // Queries slower than 50ms
                if (!isset($query_analysis[$route]['slow_queries'])) {
                    $query_analysis[$route]['slow_queries'] = [];
                }
                
                $query_analysis[$route]['slow_queries'][] = [
                    'time' => $time,
                    'query' => $matches[2][$i],
                    'caller' => $matches[3][$i]
                ];
            }
        }
    }
}

// Process bootstrap logs
$bootstrap_files = glob($log_dir . '/*-bootstrap.log');
foreach ($bootstrap_files as $file) {
    $content = file_get_contents($file);
    
    // Extract route name
    $route = basename($file, '-bootstrap.log');
    
    // Parse bootstrap data
    if (preg_match_all('/Hook: ([\w_]+)\nTime: ([\d.]+)s\nMemory: ([\d.]+) MB/', $content, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $hook = $matches[1][$i];
            $time = floatval($matches[2][$i]);
            $memory = floatval($matches[3][$i]);
            
            if (!isset($bootstrap_analysis[$route])) {
                $bootstrap_analysis[$route] = [];
            }
            
            $bootstrap_analysis[$route][$hook] = [
                'time' => $time,
                'memory' => $memory
            ];
        }
    }
}

// Generate slow queries report
$slow_queries_report = "# Slow Queries Analysis\n\n";
$slow_queries_report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Aggregate slow queries across routes
$all_slow_queries = [];
foreach ($query_analysis as $route => $data) {
    if (isset($data['slow_queries'])) {
        foreach ($data['slow_queries'] as $query) {
            $query['route'] = $route;
            $all_slow_queries[] = $query;
        }
    }
}

// Sort by time
usort($all_slow_queries, function($a, $b) {
    return $b['time'] <=> $a['time'];
});

// Top 20 slowest queries
$slow_queries_report .= "## Top 20 Slowest Queries\n\n";
$slow_queries_report .= "| Time (s) | Route | Query | Caller |\n";
$slow_queries_report .= "|----------|--------|--------|--------|\n";

$shown = 0;
foreach ($all_slow_queries as $query) {
    if ($shown >= 20) break;
    
    $slow_queries_report .= sprintf(
        "| %.4f | %s | %s | %s |\n",
        $query['time'],
        $query['route'],
        substr($query['query'], 0, 80) . (strlen($query['query']) > 80 ? '...' : ''),
        $query['caller']
    );
    $shown++;
}

// Query patterns
$slow_queries_report .= "\n## Common Slow Query Patterns\n\n";
$patterns = [
    'options' => ['pattern' => 'wp_options', 'count' => 0, 'total_time' => 0],
    'postmeta' => ['pattern' => 'postmeta', 'count' => 0, 'total_time' => 0],
    'terms' => ['pattern' => 'wp_term', 'count' => 0, 'total_time' => 0],
    'woocommerce' => ['pattern' => 'woocommerce_', 'count' => 0, 'total_time' => 0]
];

foreach ($all_slow_queries as $query) {
    foreach ($patterns as $name => &$pattern) {
        if (strpos($query['query'], $pattern['pattern']) !== false) {
            $pattern['count']++;
            $pattern['total_time'] += $query['time'];
        }
    }
}

foreach ($patterns as $name => $pattern) {
    if ($pattern['count'] > 0) {
        $slow_queries_report .= sprintf(
            "- **%s**: %d queries, %.4fs total time\n",
            ucfirst($name),
            $pattern['count'],
            $pattern['total_time']
        );
    }
}

// Recommendations
$slow_queries_report .= "\n## Recommendations\n\n";
$slow_queries_report .= "Based on the analysis, here are the optimization recommendations:\n\n";

if ($patterns['options']['count'] > 5) {
    $slow_queries_report .= "1. **Options Queries**: High number of options queries detected. Consider:\n";
    $slow_queries_report .= "   - Implementing object caching (Redis/Memcached)\n";
    $slow_queries_report .= "   - Reducing autoloaded options\n";
    $slow_queries_report .= "   - Batching get_option calls\n\n";
}

if ($patterns['postmeta']['count'] > 10) {
    $slow_queries_report .= "2. **Post Meta Queries**: Many postmeta queries detected. Consider:\n";
    $slow_queries_report .= "   - Using get_post_meta with single=false to batch queries\n";
    $slow_queries_report .= "   - Adding indexes on frequently queried meta_keys\n";
    $slow_queries_report .= "   - Caching post meta in transients\n\n";
}

if ($patterns['woocommerce']['count'] > 0) {
    $slow_queries_report .= "3. **WooCommerce Queries**: WooCommerce-related slow queries found. Consider:\n";
    $slow_queries_report .= "   - Enabling WooCommerce query monitor\n";
    $slow_queries_report .= "   - Optimizing product queries with proper indexes\n";
    $slow_queries_report .= "   - Using transient caching for expensive operations\n\n";
}

// Write slow queries report
file_put_contents($reports_dir . '/slow-queries.md', $slow_queries_report);
echo "Generated: slow-queries.md\n";

// Generate analysis report
$analysis_report = "# Performance Analysis - Phase 1\n\n";
$analysis_report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

$analysis_report .= "## TTFB Analysis by Route\n\n";
$analysis_report .= "| Route | Total Queries | Query Time (s) | Bootstrap Time (s) |\n";
$analysis_report .= "|-------|---------------|----------------|--------------------|\n";

foreach ($query_analysis as $route => $data) {
    $total_queries = 0;
    $total_time = 0;
    
    foreach ($data as $type => $info) {
        if ($type !== 'slow_queries' && isset($info['count'])) {
            $total_queries += $info['count'];
            $total_time += $info['time'];
        }
    }
    
    $bootstrap_time = 0;
    if (isset($bootstrap_analysis[$route]['template_redirect'])) {
        $bootstrap_time = $bootstrap_analysis[$route]['template_redirect']['time'];
    }
    
    $analysis_report .= sprintf(
        "| %s | %d | %.4f | %.4f |\n",
        $route,
        $total_queries,
        $total_time,
        $bootstrap_time
    );
}

$analysis_report .= "\n## Bottleneck Identification\n\n";
$analysis_report .= "### 1. Database Queries\n\n";

// Find routes with most queries
$routes_by_queries = [];
foreach ($query_analysis as $route => $data) {
    $total = 0;
    foreach ($data as $type => $info) {
        if ($type !== 'slow_queries' && isset($info['count'])) {
            $total += $info['count'];
        }
    }
    $routes_by_queries[$route] = $total;
}
arsort($routes_by_queries);

$analysis_report .= "Routes with most queries:\n";
foreach (array_slice($routes_by_queries, 0, 3, true) as $route => $count) {
    $analysis_report .= "- **{$route}**: {$count} queries\n";
}

$analysis_report .= "\n### 2. Bootstrap Performance\n\n";
$analysis_report .= "Hooks taking significant time during bootstrap:\n";

// Analyze bootstrap hooks
$slow_hooks = [];
foreach ($bootstrap_analysis as $route => $hooks) {
    foreach ($hooks as $hook => $data) {
        if ($data['time'] > 0.1) { // Hooks taking more than 100ms
            $slow_hooks[] = [
                'route' => $route,
                'hook' => $hook,
                'time' => $data['time']
            ];
        }
    }
}

usort($slow_hooks, function($a, $b) {
    return $b['time'] <=> $a['time'];
});

foreach (array_slice($slow_hooks, 0, 5) as $hook) {
    $analysis_report .= sprintf(
        "- **%s** on %s: %.4fs\n",
        $hook['hook'],
        $hook['route'],
        $hook['time']
    );
}

$analysis_report .= "\n## Optimization Strategy\n\n";
$analysis_report .= "### Phase 1 Targets\n\n";
$analysis_report .= "1. **Query Optimization**\n";
$analysis_report .= "   - Implement query result caching for repeated queries\n";
$analysis_report .= "   - Add database indexes for slow meta queries\n";
$analysis_report .= "   - Batch similar queries where possible\n\n";

$analysis_report .= "2. **Route-based Loading**\n";
$analysis_report .= "   - Conditionally load WooCommerce features only where needed\n";
$analysis_report .= "   - Defer non-critical hooks on non-commerce pages\n";
$analysis_report .= "   - Optimize autoloaded options\n\n";

$analysis_report .= "3. **Caching Strategy**\n";
$analysis_report .= "   - Implement transient caching for expensive operations\n";
$analysis_report .= "   - Use object caching for frequently accessed data\n";
$analysis_report .= "   - Cache rendered HTML fragments where appropriate\n";

// Write analysis report
file_put_contents($reports_dir . '/analysis-phase1.md', $analysis_report);
echo "Generated: analysis-phase1.md\n";

echo "\nAnalysis complete!\n";
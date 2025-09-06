<?php
/**
 * Script Analysis Tool
 * 
 * Analyzes which scripts are being deferred/async
 * and their impact on performance
 * 
 * Usage: Add to theme footer temporarily to analyze
 */

if (!defined('VIDIEU_PERF_DEFER_JS') || !VIDIEU_PERF_DEFER_JS) {
    echo "<!-- Script analysis: VIDIEU_PERF_DEFER_JS is not enabled -->\n";
    return;
}

add_action('wp_footer', function() {
    global $wp_scripts;
    
    if (!$wp_scripts || !isset($wp_scripts->registered)) {
        return;
    }
    
    $analysis = [
        'total' => 0,
        'deferred' => [],
        'async' => [],
        'normal' => [],
        'critical' => [],
    ];
    
    $defer_js = Vidieu_Defer_JS::get_instance();
    
    foreach ($wp_scripts->registered as $handle => $script) {
        $analysis['total']++;
        
        // Check if script is enqueued
        if (!wp_script_is($handle, 'enqueued')) {
            continue;
        }
        
        $src = $script->src;
        if (empty($src)) {
            continue; // Skip inline scripts
        }
        
        // Categorize script
        $is_critical = in_array($handle, [
            'jquery-core', 'jquery-migrate', 'jquery',
            'wc-checkout', 'wc-cart', 'wc-cart-fragments'
        ]);
        
        $script_info = [
            'handle' => $handle,
            'src' => $src,
            'deps' => $script->deps,
            'ver' => $script->ver,
        ];
        
        if ($is_critical) {
            $analysis['critical'][] = $script_info;
        } else {
            // This is a simplified check - in reality the class method determines this
            if (strpos($handle, 'google-analytics') !== false || 
                strpos($handle, 'gtag') !== false ||
                strpos($handle, 'pixel') !== false) {
                $analysis['async'][] = $script_info;
            } else {
                $analysis['deferred'][] = $script_info;
            }
        }
    }
    
    // Output analysis as HTML comment
    echo "\n<!-- Script Defer Analysis\n";
    echo "Total scripts: {$analysis['total']}\n";
    echo "Critical (not deferred): " . count($analysis['critical']) . "\n";
    echo "Deferred: " . count($analysis['deferred']) . "\n";
    echo "Async: " . count($analysis['async']) . "\n\n";
    
    echo "Critical Scripts:\n";
    foreach ($analysis['critical'] as $script) {
        echo "  - {$script['handle']}\n";
    }
    
    echo "\nDeferred Scripts:\n";
    foreach ($analysis['deferred'] as $script) {
        echo "  - {$script['handle']}\n";
        if (!empty($script['deps'])) {
            echo "    Dependencies: " . implode(', ', $script['deps']) . "\n";
        }
    }
    
    echo "\nAsync Scripts:\n";
    foreach ($analysis['async'] as $script) {
        echo "  - {$script['handle']}\n";
    }
    
    echo "\nPerformance Impact Estimate:\n";
    $deferred_count = count($analysis['deferred']);
    $tbt_reduction = $deferred_count * 15; // ~15ms per deferred script
    $inp_improvement = $deferred_count * 5; // ~5ms INP improvement per script
    
    echo "Estimated TBT reduction: ~{$tbt_reduction}ms\n";
    echo "Estimated INP improvement: ~{$inp_improvement}ms\n";
    
    echo "-->\n";
}, 999);
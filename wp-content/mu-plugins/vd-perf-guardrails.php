<?php
/**
 * Plugin Name: Vidieu Performance Guardrails
 * Description: Feature flags for performance optimizations to enable safe rollback
 * Version: 1.1.0
 * Author: Vidieu.vn
 * 
 * This MU-plugin provides centralized feature flags for performance optimizations.
 * Each flag can be enabled/disabled to control specific optimizations.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Feature flag for Critical CSS optimization
 * When enabled, allows inline critical CSS for above-the-fold content
 * @since 1.0.0
 */
if (!defined('VIDIEU_PERF_CRITICAL_CSS')) {
    define('VIDIEU_PERF_CRITICAL_CSS', false);
}

/**
 * Feature flag for JavaScript deferral optimization
 * When enabled, allows deferring non-critical JavaScript files
 * @since 1.0.0
 */
if (!defined('VIDIEU_PERF_DEFER_JS')) {
    define('VIDIEU_PERF_DEFER_JS', false);
}

/**
 * Feature flag for Font optimization
 * When enabled, allows font preloading and optimization strategies
 * @since 1.0.0
 */
if (!defined('VIDIEU_PERF_FONTS')) {
    define('VIDIEU_PERF_FONTS', false);
}

/**
 * Feature flag for WooCommerce-specific optimizations
 * When enabled, allows WooCommerce performance tweaks
 * @since 1.0.0
 */
if (!defined('VIDIEU_PERF_WC')) {
    define('VIDIEU_PERF_WC', false);
}

/**
 * Phase 1: Server-side Performance Flags
 */

/**
 * Feature flag for Query logging
 * When enabled, logs MySQL queries for performance analysis
 * @since 1.1.0
 */
if (!defined('VIDIEU_PERF_LOG_QUERIES')) {
    define('VIDIEU_PERF_LOG_QUERIES', false);
}

/**
 * Feature flag for Bootstrap logging
 * When enabled, logs heavy hooks/actions during WordPress bootstrap
 * @since 1.1.0
 */
if (!defined('VIDIEU_PERF_LOG_BOOTSTRAP')) {
    define('VIDIEU_PERF_LOG_BOOTSTRAP', false);
}

/**
 * Feature flag for Route-based conditionals
 * When enabled, conditionally loads WooCommerce features based on current route
 * @since 1.1.0
 */
if (!defined('VIDIEU_PERF_ROUTE_CONDITIONALS')) {
    define('VIDIEU_PERF_ROUTE_CONDITIONALS', false);
}

/**
 * Hook to allow runtime flag status reporting (optional)
 */
add_action('init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG && is_admin()) {
        add_action('admin_notices', function() {
            if (current_user_can('manage_options')) {
                $flags = [
                    'VIDIEU_PERF_CRITICAL_CSS' => VIDIEU_PERF_CRITICAL_CSS,
                    'VIDIEU_PERF_DEFER_JS' => VIDIEU_PERF_DEFER_JS,
                    'VIDIEU_PERF_FONTS' => VIDIEU_PERF_FONTS,
                    'VIDIEU_PERF_WC' => VIDIEU_PERF_WC,
                    'VIDIEU_PERF_LOG_QUERIES' => VIDIEU_PERF_LOG_QUERIES,
                    'VIDIEU_PERF_LOG_BOOTSTRAP' => VIDIEU_PERF_LOG_BOOTSTRAP,
                    'VIDIEU_PERF_ROUTE_CONDITIONALS' => VIDIEU_PERF_ROUTE_CONDITIONALS,
                ];
                
                // Only show if any flag is enabled
                $any_enabled = array_filter($flags);
                if (!empty($any_enabled)) {
                    echo '<div class="notice notice-info is-dismissible"><p><strong>Vidieu Performance Flags:</strong> ';
                    foreach ($flags as $flag => $status) {
                        if ($status) {
                            echo sprintf('%s: <span style="color: green;">ON</span> ', $flag);
                        }
                    }
                    echo '</p></div>';
                }
            }
        });
    }
});
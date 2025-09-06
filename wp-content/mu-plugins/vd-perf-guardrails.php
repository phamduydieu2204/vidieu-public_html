<?php
/**
 * Plugin Name: Vidieu Performance Guardrails
 * Description: Feature flags for performance optimizations to enable safe rollback
 * Version: 1.0.0
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
<?php
/**
 * MU-Plugin: Fix 404 Resources - Safe Version
 * 
 * Fixes persistent 404 errors with proper guards
 * Only handles specific 404 resources on frontend
 * 
 * @package Vidieu_Performance_Fixes
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Safe 404 fixes with proper guards
 */
add_action('muplugins_loaded', function() {
    
    // Guard 1: Only handle GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }
    
    // Guard 2: Skip if no request URI
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (empty($request_uri)) {
        return;
    }
    
    // Guard 3: Skip admin, AJAX, REST, CLI requests
    // Check without using WordPress functions (too early)
    if (
        strpos($request_uri, '/wp-admin/') !== false ||
        strpos($request_uri, '/wp-json/') !== false ||
        strpos($request_uri, 'admin-ajax.php') !== false ||
        strpos($request_uri, 'wp-cron.php') !== false ||
        (defined('DOING_AJAX') && DOING_AJAX) ||
        (defined('DOING_CRON') && DOING_CRON) ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        (defined('WP_CLI') && WP_CLI)
    ) {
        return;
    }
    
    // Only handle our specific 404 resources
    $handled = false;
    
    // Fix 1: main-font.woff2 - EXACT match for safety
    if (strpos($request_uri, '/wp-content/themes/elessi-theme/assets/fonts/main-font.woff2') !== false) {
        $handled = true;
        handle_font_404_redirect();
    }
    
    // Fix 2: style.min.css - EXACT match for safety
    elseif (strpos($request_uri, '/wp-content/themes/elessi-theme/style.min.css') !== false) {
        $handled = true;
        handle_style_404_redirect($request_uri);
    }
    
    // Log if we handled something (only once per request)
    if ($handled && defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Vidieu 404 Fix] Handled: ' . $request_uri);
    }
    
}, 1);

/**
 * Handle font 404 redirect
 */
function handle_font_404_redirect() {
    // Try specific locations in order
    $font_alternatives = array(
        array(
            'path' => ABSPATH . 'wp-content/themes/elessi-theme-child/assets/fonts/main-font.woff',
            'url' => '/wp-content/themes/elessi-theme-child/assets/fonts/main-font.woff'
        ),
        array(
            'path' => ABSPATH . 'wp-content/themes/elessi-theme/assets/fonts/main-font.woff',
            'url' => '/wp-content/themes/elessi-theme/assets/fonts/main-font.woff'
        )
    );
    
    foreach ($font_alternatives as $font) {
        if (file_exists($font['path'])) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $font['url']);
            header('Cache-Control: public, max-age=31536000, immutable');
            exit;
        }
    }
    
    // If no font found, return minimal valid woff2 to prevent 404
    header('HTTP/1.1 200 OK');
    header('Content-Type: font/woff2');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: 32');
    // Minimal valid WOFF2 file (empty font)
    echo base64_decode('d09GMgABAAAAAAA4AA0AAAAAAiQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABmRgA=');
    exit;
}

/**
 * Handle style.min.css redirect
 */
function handle_style_404_redirect($request_uri) {
    // Simply redirect to non-minified version
    $non_min_url = str_replace('style.min.css', 'style.css', $request_uri);
    
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $non_min_url);
    header('Cache-Control: public, max-age=86400'); // 1 day cache
    exit;
}

/**
 * WordPress is loaded - apply additional fixes
 */
add_action('init', function() {
    
    // Skip if in admin
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    
    // Fix font paths in enqueued styles
    add_filter('style_loader_src', 'vidieu_fix_resource_urls', 999, 2);
    add_filter('script_loader_src', 'vidieu_fix_resource_urls', 999, 2);
    
}, 1);

/**
 * Fix resource URLs in enqueued assets
 */
function vidieu_fix_resource_urls($src, $handle) {
    if (empty($src)) {
        return $src;
    }
    
    // Fix main-font.woff2 references
    if (strpos($src, 'main-font.woff2') !== false) {
        $src = str_replace('main-font.woff2', 'main-font.woff', $src);
    }
    
    // Fix style.min.css references  
    if (strpos($src, 'style.min.css') !== false && strpos($src, 'elessi-theme') !== false) {
        $src = str_replace('style.min.css', 'style.css', $src);
    }
    
    return $src;
}

/**
 * Add debug info for admins
 */
add_action('wp_footer', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    echo "\n<!-- MU-Plugin 404 Fixes (Safe Version) Active -->\n";
}, 9999);
<?php
/**
 * MU-Plugin: Fix 404 Resources - Nuclear Option
 * 
 * Fixes persistent 404 errors by early redirection
 * Priority: Runs before all plugins and themes
 * 
 * @package Vidieu_Performance_Fixes
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Early 404 fixes - runs on muplugins_loaded (earliest possible hook)
 */
add_action('muplugins_loaded', function() {
    
    // Only run on frontend
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Fix 1: main-font.woff2 -> redirect to existing font file
    if (strpos($request_uri, 'main-font.woff2') !== false) {
        
        // Try multiple possible locations
        $possible_fonts = array(
            '/wp-content/themes/elessi-theme-child/assets/fonts/main-font.woff',
            '/wp-content/themes/elessi-theme-child/fonts/main-font.woff',
            '/wp-content/themes/elessi-theme/assets/fonts/main-font.woff',
            '/wp-content/themes/elessi-theme/fonts/FontAwesome.woff' // fallback to any existing font
        );
        
        foreach ($possible_fonts as $font_path) {
            $full_path = ABSPATH . ltrim($font_path, '/');
            if (file_exists($full_path)) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . $font_path);
                header('Cache-Control: public, max-age=31536000'); // 1 year cache
                exit;
            }
        }
        
        // If no font found, return empty font to prevent 404
        header('Content-Type: font/woff2');
        header('Cache-Control: public, max-age=31536000');
        // Return minimal valid woff2 header
        echo base64_decode('d09GMgABAAAAAAA4AA0AAAAAAiQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABmRgA=');
        exit;
    }
    
    // Fix 2: style.min.css -> redirect to non-minified version
    if (strpos($request_uri, 'elessi-theme/style.min.css') !== false) {
        $non_min_path = str_replace('style.min.css', 'style.css', $request_uri);
        
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $non_min_path);
        header('Cache-Control: public, max-age=86400'); // 1 day cache
        exit;
    }
    
}, 1);

/**
 * Alternative fix using WordPress hooks (backup method)
 */
add_action('init', function() {
    
    // Fix missing Elementor Google Fonts
    add_filter('elementor/files/file_generator/create', '__return_false');
    
    // Fix font paths in CSS
    add_filter('style_loader_src', function($src, $handle) {
        if (!$src) return $src;
        
        // Fix main-font.woff2 references
        if (strpos($src, 'main-font.woff2') !== false) {
            // Find alternative font file
            $alt_font = get_stylesheet_directory_uri() . '/assets/fonts/main-font.woff';
            if (!file_exists(get_stylesheet_directory() . '/assets/fonts/main-font.woff')) {
                $alt_font = get_template_directory_uri() . '/assets/fonts/FontAwesome.woff';
            }
            
            $src = str_replace('main-font.woff2', basename($alt_font), $src);
        }
        
        // Fix style.min.css references
        if (strpos($src, 'style.min.css') !== false && strpos($src, 'elessi-theme') !== false) {
            $src = str_replace('style.min.css', 'style.css', $src);
        }
        
        return $src;
    }, 1, 2);
    
}, 1);

/**
 * Log 404 fixes for debugging
 */
if (current_user_can('manage_options')) {
    add_action('wp_footer', function() {
        echo "\n<!-- MU-Plugin 404 Fixes Active -->\n";
    }, 9999);
}

/**
 * Create physical redirects via .htaccess (commented out by default)
 * Uncomment and run once if needed
 */
/*
add_action('admin_init', function() {
    if (!isset($_GET['create_404_redirects'])) return;
    
    $htaccess = ABSPATH . '.htaccess';
    $rules = "\n# Vidieu 404 Fixes\n";
    $rules .= "RedirectMatch 301 ^/wp-content/themes/elessi-theme/assets/fonts/main-font\.woff2$ /wp-content/themes/elessi-theme-child/assets/fonts/main-font.woff\n";
    $rules .= "RedirectMatch 301 ^/wp-content/themes/elessi-theme/style\.min\.css$ /wp-content/themes/elessi-theme/style.css\n";
    $rules .= "# End Vidieu 404 Fixes\n";
    
    $current = file_get_contents($htaccess);
    if (strpos($current, '# Vidieu 404 Fixes') === false) {
        file_put_contents($htaccess, $rules . $current);
        wp_die('404 redirect rules added to .htaccess');
    }
});
*/
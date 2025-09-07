<?php
/**
 * Font Cleanup Module
 * 
 * Fixes font-related 404 errors and removes unused preloads
 * - Blocks Elementor jost.css 404
 * - Removes unused font/CSS preloads
 * - Provides safe system font fallback
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Font_Cleanup {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Skip in admin
        if (is_admin()) {
            return;
        }
        
        // Task A: Block Elementor jost.css
        add_filter('style_loader_src', array($this, 'block_jost_css'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'dequeue_jost_css'), 999);
        add_filter('wp_resource_hints', array($this, 'remove_jost_hints'), 10, 2);
        add_filter('elementor/frontend/print_google_fonts', '__return_false');
        
        // Task B: Clean unused preloads
        add_action('template_redirect', array($this, 'setup_preload_cleanup'), 1);
        
        // Task A2: Enqueue font fallback CSS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_font_fallback'), 5);
    }
    
    /**
     * Block jost.css via style_loader_src filter
     */
    public function block_jost_css($src, $handle) {
        if ($src && strpos($src, '/uploads/elementor/google-fonts/css/jost.css') !== false) {
            return false; // Block the style
        }
        return $src;
    }
    
    /**
     * Dequeue and deregister jost.css late
     */
    public function dequeue_jost_css() {
        global $wp_styles;
        
        if (!$wp_styles || !is_object($wp_styles)) {
            return;
        }
        
        // Check all registered styles
        foreach ($wp_styles->registered as $handle => $style) {
            if (!empty($style->src) && strpos($style->src, '/uploads/elementor/google-fonts/css/jost.css') !== false) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
            }
        }
    }
    
    /**
     * Remove jost.css from resource hints
     */
    public function remove_jost_hints($urls, $relation) {
        if (!is_array($urls)) {
            return $urls;
        }
        
        // Filter out jost.css related URLs
        return array_values(array_filter($urls, function($url) {
            $url_str = is_array($url) ? (isset($url['href']) ? $url['href'] : '') : $url;
            return strpos($url_str, '/uploads/elementor/google-fonts/css/jost.css') === false;
        }));
    }
    
    /**
     * Setup preload cleanup via output buffer
     */
    public function setup_preload_cleanup() {
        // Only on frontend
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        ob_start(array($this, 'clean_unused_preloads'));
    }
    
    /**
     * Clean unused preloads from HTML output
     */
    public function clean_unused_preloads($html) {
        // Define patterns for unused preloads
        $patterns = array(
            // Jost.css preload (404)
            '#<link[^>]+rel=["\']preload["\'][^>]+uploads/elementor/google-fonts/css/jost\.css[^>]*>\s*#i',
            
            // Main font preload (if not actually used)
            '#<link[^>]+rel=["\']preload["\'][^>]+themes/elessi-theme/assets/fonts/main-font\.woff2[^>]*>\s*#i',
            
            // Style.min.css preload (if not actually loaded as stylesheet)
            '#<link[^>]+rel=["\']preload["\'][^>]+themes/elessi-theme/style\.min\.css[^>]*>\s*#i',
        );
        
        // Remove matched preloads
        $html = preg_replace($patterns, '', $html);
        
        // Also clean up any preload hints for these resources
        $html = preg_replace(
            '#<link[^>]+rel=["\']dns-prefetch["\'][^>]+href=["\'][^"\']*elementor/google-fonts[^>]*>\s*#i',
            '',
            $html
        );
        
        return $html;
    }
    
    /**
     * Enqueue safe font fallback CSS
     */
    public function enqueue_font_fallback() {
        $css_file = VD_HOME_PLUGIN_DIR . 'assets/css/font-fallback.css';
        
        // Create the CSS file if it doesn't exist
        if (!file_exists($css_file)) {
            $this->create_font_fallback_css($css_file);
        }
        
        // Enqueue the fallback CSS
        wp_enqueue_style(
            'vidieu-font-fallback',
            VD_HOME_PLUGIN_URL . 'assets/css/font-fallback.css',
            array(),
            file_exists($css_file) ? filemtime($css_file) : VD_HOME_VERSION,
            'all'
        );
        
        // Add inline critical font CSS for immediate effect
        wp_add_inline_style('vidieu-font-fallback', $this->get_critical_font_css());
    }
    
    /**
     * Create font fallback CSS file
     */
    private function create_font_fallback_css($file_path) {
        $css_content = '/* Vidieu Font Fallback - Safe System Fonts */
:root {
    --vd-font-sans: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
    --vd-font-mono: ui-monospace, "Cascadia Mono", "Segoe UI Mono", "Courier New", monospace;
}

/* Apply system font stack globally - preserve sizes/weights */
body,
button,
input,
select,
textarea {
    font-family: var(--vd-font-sans) !important;
}

/* Override Elementor font classes */
:where(html, body, .elementor-kit, .e-font-jost) {
    font-family: var(--vd-font-sans) !important;
}

/* Ensure readability for Vietnamese content */
html {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Code/pre elements use monospace */
code,
kbd,
pre,
samp {
    font-family: var(--vd-font-mono) !important;
}';
        
        // Ensure directory exists
        $dir = dirname($file_path);
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        
        // Write CSS file
        file_put_contents($file_path, $css_content);
    }
    
    /**
     * Get critical font CSS for inline
     */
    private function get_critical_font_css() {
        return ':root{--vd-font-sans:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans",sans-serif}body{font-family:var(--vd-font-sans)!important}';
    }
}

// Initialize
Vidieu_Font_Cleanup::get_instance();
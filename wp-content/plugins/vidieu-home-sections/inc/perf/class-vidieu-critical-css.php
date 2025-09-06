<?php
/**
 * Vidieu Critical CSS Loader
 * 
 * Inline critical CSS for above-the-fold content
 * Only active when VIDIEU_PERF_CRITICAL_CSS flag is enabled
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Critical_CSS {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Critical CSS directory path
     */
    private $css_dir;
    
    /**
     * Route to CSS file mapping
     */
    private $route_map = [
        'home' => 'home.css',
        'shop' => 'archive-product.css',
        'product' => 'single-product.css',
        'post' => 'single-post.css',
        'contact' => 'page-contact.css',
        'cart' => 'cart.css',
        'checkout' => 'checkout.css',
        'account' => 'my-account.css',
    ];
    
    /**
     * Current route type
     */
    private $current_route = '';
    
    /**
     * Get singleton instance
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
        // Only run if flag is enabled and on frontend
        if (!defined('VIDIEU_PERF_CRITICAL_CSS') || !VIDIEU_PERF_CRITICAL_CSS) {
            return;
        }
        
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Set CSS directory path
        $this->css_dir = WP_CONTENT_DIR . '/plugins/vidieu-home-sections/perf/critical-css/';
        
        // Hook early in wp_head to inline critical CSS
        add_action('wp_head', [$this, 'inline_critical_css'], 2);
    }
    
    /**
     * Inline critical CSS based on current route
     */
    public function inline_critical_css() {
        // Detect current route
        $this->detect_route();
        
        // Get CSS file for current route
        $css_file = $this->get_css_file();
        
        if (!$css_file || !file_exists($css_file)) {
            return;
        }
        
        // Read and inline CSS
        $css_content = file_get_contents($css_file);
        
        if (empty($css_content)) {
            return;
        }
        
        // Minify CSS (basic minification)
        $css_content = $this->minify_css($css_content);
        
        // Output inline CSS
        echo "\n<!-- Vidieu Critical CSS - {$this->current_route} -->\n";
        echo "<style id=\"vidieu-critical-css\">\n";
        echo $css_content;
        echo "\n</style>\n";
        echo "<!-- End Vidieu Critical CSS -->\n\n";
    }
    
    /**
     * Detect current route type
     */
    private function detect_route() {
        // Homepage
        if (is_front_page() || is_home()) {
            $this->current_route = 'home';
            return;
        }
        
        // WooCommerce routes
        if (function_exists('is_shop') && is_shop()) {
            $this->current_route = 'shop';
            return;
        }
        
        if (function_exists('is_product') && is_product()) {
            $this->current_route = 'product';
            return;
        }
        
        if (function_exists('is_cart') && is_cart()) {
            $this->current_route = 'cart';
            return;
        }
        
        if (function_exists('is_checkout') && is_checkout()) {
            $this->current_route = 'checkout';
            return;
        }
        
        if (function_exists('is_account_page') && is_account_page()) {
            $this->current_route = 'account';
            return;
        }
        
        // Product categories/tags
        if (function_exists('is_product_category') && (is_product_category() || is_product_tag())) {
            $this->current_route = 'shop';
            return;
        }
        
        // Blog post
        if (is_single() && get_post_type() === 'post') {
            $this->current_route = 'post';
            return;
        }
        
        // Contact page
        if (is_page()) {
            $page = get_queried_object();
            if ($page && ($page->post_name === 'contact' || $page->post_name === 'lien-he')) {
                $this->current_route = 'contact';
                return;
            }
        }
        
        // Default: no critical CSS
        $this->current_route = '';
    }
    
    /**
     * Get CSS file path for current route
     */
    private function get_css_file() {
        if (empty($this->current_route) || !isset($this->route_map[$this->current_route])) {
            return false;
        }
        
        return $this->css_dir . $this->route_map[$this->current_route];
    }
    
    /**
     * Basic CSS minification
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove whitespace around specific characters
        $css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $css);
        $css = preg_replace('/\s+([{}|:;,])/', '$1', $css);
        
        // Remove trailing semicolon before closing brace
        $css = str_replace(';}', '}', $css);
        
        // Remove leading/trailing whitespace
        $css = trim($css);
        
        return $css;
    }
    
    /**
     * Get current route (for debugging)
     */
    public function get_current_route() {
        return $this->current_route;
    }
}

// Initialize Critical CSS loader
Vidieu_Critical_CSS::get_instance();